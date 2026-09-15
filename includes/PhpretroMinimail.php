<?php
require_once __DIR__.'/PhpretroLiveSync.php';
require_once __DIR__.'/PhpretroPolarisCms.php';

class PhpretroMinimailError extends RuntimeException {}

class PhpretroMinimail
{
    public const PAGE_SIZE = 10;
    public const SUBJECT_MAX = 100;
    public const BODY_MAX = 4096;
    public const RECIPIENT_MAX = 50;

    public function __construct(public Database $db, public int $actor, public PhpretroLiveSync $sync) {}

    public function transaction(callable $action): mixed
    {
        $this->db->execute('START TRANSACTION');
        try {
            $result = $action();
            $this->db->execute('COMMIT');
            return $result;
        } catch (Throwable $error) {
            $this->db->execute('ROLLBACK');
            throw $error;
        }
    }

    public function need(bool $ok, string $message, int $status = 400): void
    {
        if (!$ok) { throw new PhpretroMinimailError($message, $status); }
    }

    public function user(int $id): ?array
    {
        if ($id < 1) { return null; }
        return $this->db->fetchRow('SELECT id, username, look, online FROM users WHERE id = ?', [$id]) ?: null;
    }

    public function isFriend(int $from, int $to): bool
    {
        if ($from < 1 || $to < 1 || $from === $to) { return false; }
        return (bool) $this->db->fetchColumn(
            'SELECT id FROM messenger_friendships WHERE user_one_id = ? AND user_two_id = ? LIMIT 1',
            [$from, $to]
        );
    }

    public function message(int $id, bool $lock = false): array
    {
        $this->need($id > 0, 'Invalid message.', 400);
        $row = $this->db->fetchRow('SELECT id, sender_id, recipient_id, subject, body, conversation_id, sent_at, read_at, deleted FROM phpretro_minimail WHERE id = ?'.($lock ? ' FOR UPDATE' : ''), [$id]);
        $this->need((bool) $row, 'Message not found.', 404);
        $this->need((int) $row['sender_id'] === $this->actor || (int) $row['recipient_id'] === $this->actor, 'Not permitted.', 403);
        return $row;
    }

    public function unreadCount(): int
    {
        return (int) $this->db->fetchColumn('SELECT COUNT(*) FROM phpretro_minimail WHERE recipient_id = ? AND deleted = 0 AND read_at IS NULL', [$this->actor]);
    }

    public function folderCount(string $label, int $conversationId = 0, bool $unreadOnly = false): int
    {
        return match ($label) {
            'sent' => (int) $this->db->fetchColumn('SELECT COUNT(*) FROM phpretro_minimail WHERE sender_id = ?', [$this->actor]),
            'trash' => (int) $this->db->fetchColumn('SELECT COUNT(*) FROM phpretro_minimail WHERE recipient_id = ? AND deleted = 1', [$this->actor]),
            'conversation' => (int) $this->db->fetchColumn(
                'SELECT COUNT(*) FROM phpretro_minimail WHERE conversation_id = ? AND conversation_id > 0 AND (sender_id = ? OR recipient_id = ?)',
                [$conversationId, $this->actor, $this->actor]
            ),
            default => (int) $this->db->fetchColumn(
                $unreadOnly
                    ? 'SELECT COUNT(*) FROM phpretro_minimail WHERE recipient_id = ? AND deleted = 0 AND read_at IS NULL'
                    : 'SELECT COUNT(*) FROM phpretro_minimail WHERE recipient_id = ? AND deleted = 0',
                [$this->actor]
            ),
        };
    }

    public function list(string $label, int $offset, int $conversationId = 0, bool $unreadOnly = false): array
    {
        $offset = max(0, $offset);
        $limit = self::PAGE_SIZE;
        $sql = match ($label) {
            'sent' => 'SELECT m.id, m.sender_id, m.recipient_id, m.subject, m.body, m.conversation_id, m.sent_at, m.read_at, m.deleted, u.username, u.look FROM phpretro_minimail m JOIN users u ON u.id = m.recipient_id WHERE m.sender_id = ? ORDER BY m.id DESC LIMIT ? OFFSET ?',
            'trash' => 'SELECT m.id, m.sender_id, m.recipient_id, m.subject, m.body, m.conversation_id, m.sent_at, m.read_at, m.deleted, u.username, u.look FROM phpretro_minimail m JOIN users u ON u.id = m.sender_id WHERE m.recipient_id = ? AND m.deleted = 1 ORDER BY m.id DESC LIMIT ? OFFSET ?',
            'conversation' => 'SELECT m.id, m.sender_id, m.recipient_id, m.subject, m.body, m.conversation_id, m.sent_at, m.read_at, m.deleted, u.username, u.look FROM phpretro_minimail m JOIN users u ON u.id = m.sender_id WHERE m.conversation_id = ? AND m.conversation_id > 0 AND (m.sender_id = ? OR m.recipient_id = ?) ORDER BY m.id DESC LIMIT ? OFFSET ?',
            default => $unreadOnly
                ? 'SELECT m.id, m.sender_id, m.recipient_id, m.subject, m.body, m.conversation_id, m.sent_at, m.read_at, m.deleted, u.username, u.look FROM phpretro_minimail m JOIN users u ON u.id = m.sender_id WHERE m.recipient_id = ? AND m.deleted = 0 AND m.read_at IS NULL ORDER BY m.id DESC LIMIT ? OFFSET ?'
                : 'SELECT m.id, m.sender_id, m.recipient_id, m.subject, m.body, m.conversation_id, m.sent_at, m.read_at, m.deleted, u.username, u.look FROM phpretro_minimail m JOIN users u ON u.id = m.sender_id WHERE m.recipient_id = ? AND m.deleted = 0 ORDER BY m.id DESC LIMIT ? OFFSET ?',
        };
        $params = match ($label) {
            'conversation' => [$conversationId, $this->actor, $this->actor, $limit, $offset],
            default => [$this->actor, $limit, $offset],
        };
        return $this->db->fetchAll($sql, $params);
    }

    public function send(array $recipientIds, string $subject, string $body, int $replyTo = 0): array
    {
        $subject = trim($subject);
        $body = trim($body);
        $this->need(mb_strlen($subject, 'UTF-8') <= self::SUBJECT_MAX, 'Subject is too long.', 400);
        $this->need(mb_strlen($body, 'UTF-8') <= self::BODY_MAX, 'Message is too long.', 400);
        $ids = [];
        foreach ($recipientIds as $id) {
            $id = (int) $id;
            if ($id > 0 && $id !== $this->actor && !in_array($id, $ids, true)) { $ids[] = $id; }
        }
        return $this->transaction(function () use ($ids, $subject, $body, $replyTo) {
            $conversationId = 0;
            if ($replyTo > 0) {
                $original = $this->message($replyTo, true);
                $this->need((int) $original['recipient_id'] === $this->actor, 'Not permitted.', 403);
                $conversationId = (int) $original['conversation_id'];
                if ($conversationId === 0) {
                    $conversationId = (int) $this->db->fetchColumn('SELECT COALESCE(MAX(conversation_id), 0) FROM phpretro_minimail FOR UPDATE') + 1;
                    $this->db->execute('UPDATE phpretro_minimail SET conversation_id = ? WHERE id = ?', [$conversationId, $original['id']]);
                }
                $ids = [(int) $original['sender_id']];
                $subject = 'Re: '.$original['subject'];
            }
            $this->need($ids !== [], 'Choose a recipient.', 400);
            $this->need(count($ids) <= self::RECIPIENT_MAX, 'Too many recipients.', 400);
            $now = time();
            $created = [];
            foreach ($ids as $to) {
                if (!$this->user($to) || !$this->isFriend($this->actor, $to)) { continue; }
                $this->db->execute(
                    'INSERT INTO phpretro_minimail (sender_id, recipient_id, subject, body, conversation_id, sent_at) VALUES (?, ?, ?, ?, ?, ?)',
                    [$this->actor, $to, $subject, $body, $conversationId, $now]
                );
                $id = (int) $this->db->insertId();
                $this->sync->recordAndNotify('minimail.sent', [
                    'message_id' => $id,
                    'sender_id' => $this->actor,
                    'recipient_id' => $to,
                    'conversation_id' => $conversationId,
                ]);
                $created[] = $id;
            }
            $this->need($created !== [], 'Choose a recipient.', 400);
            return $created;
        });
    }

    public function markRead(int $id): array
    {
        return $this->transaction(function () use ($id) {
            $row = $this->message($id, true);
            if ((int) $row['recipient_id'] === $this->actor && $row['read_at'] === null) {
                $now = time();
                $this->db->execute('UPDATE phpretro_minimail SET read_at = ? WHERE id = ? AND recipient_id = ? AND read_at IS NULL', [$now, $id, $this->actor]);
                $this->sync->recordAndNotify('minimail.read', ['message_id' => $id, 'user_id' => $this->actor]);
                $row['read_at'] = (string) $now;
            }
            return $row;
        });
    }

    public function trash(int $id): string
    {
        return $this->transaction(function () use ($id) {
            $row = $this->message($id, true);
            $this->need((int) $row['recipient_id'] === $this->actor, 'Not permitted.', 403);
            if ((int) $row['deleted'] === 1) {
                $this->db->execute('DELETE FROM phpretro_minimail WHERE id = ? AND recipient_id = ? AND deleted = 1', [$id, $this->actor]);
                $this->sync->recordAndNotify('minimail.purged', ['message_id' => $id, 'user_id' => $this->actor]);
                return 'deleted';
            }
            $this->db->execute('UPDATE phpretro_minimail SET deleted = 1, deleted_at = ? WHERE id = ? AND recipient_id = ?', [time(), $id, $this->actor]);
            $this->sync->recordAndNotify('minimail.trashed', ['message_id' => $id, 'user_id' => $this->actor]);
            return 'trashed';
        });
    }

    public function undelete(int $id): void
    {
        $this->transaction(function () use ($id) {
            $row = $this->message($id, true);
            $this->need((int) $row['recipient_id'] === $this->actor && (int) $row['deleted'] === 1, 'Not permitted.', 403);
            $this->db->execute('UPDATE phpretro_minimail SET deleted = 0, deleted_at = NULL WHERE id = ? AND recipient_id = ?', [$id, $this->actor]);
            $this->sync->recordAndNotify('minimail.undeleted', ['message_id' => $id, 'user_id' => $this->actor]);
        });
    }

    public function emptyTrash(): int
    {
        return $this->transaction(function () {
            $ids = array_map('intval', array_column($this->db->fetchAll('SELECT id FROM phpretro_minimail WHERE recipient_id = ? AND deleted = 1 FOR UPDATE', [$this->actor]), 'id'));
            $count = $this->db->execute('DELETE FROM phpretro_minimail WHERE recipient_id = ? AND deleted = 1', [$this->actor]);
            if ($ids) {
                $this->sync->recordAndNotify('minimail.trash_emptied', ['user_id' => $this->actor, 'message_ids' => $ids]);
            }
            return $count;
        });
    }

    public function report(int $id): void
    {
        $payload = $this->transaction(function () use ($id) {
            $row = $this->message($id, true);
            $this->need((int) $row['recipient_id'] === $this->actor, 'Not permitted.', 403);
            $this->need((int) $row['sender_id'] !== $this->actor, 'You cannot report your own messages.', 400);
            $evidence = $row['subject']."\n\n".$row['body'];
            $this->db->execute(
                'INSERT INTO phpretro_user_reports (reporter_id, reported_user_id, reason, evidence, status, action_notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$this->actor, (int) $row['sender_id'], 'minimail', $evidence, 'open', '', time()]
            );
            $this->db->execute('DELETE FROM messenger_friendships WHERE (user_one_id = ? AND user_two_id = ?) OR (user_one_id = ? AND user_two_id = ?)', [$this->actor, $row['sender_id'], $row['sender_id'], $this->actor]);
            $this->db->execute('DELETE FROM messenger_friendrequests WHERE (user_from_id = ? AND user_to_id = ?) OR (user_from_id = ? AND user_to_id = ?)', [$this->actor, $row['sender_id'], $row['sender_id'], $this->actor]);
            $this->db->execute('DELETE FROM phpretro_minimail WHERE id = ? AND recipient_id = ?', [$id, $this->actor]);
            $this->sync->recordAndNotify('minimail.reported', [
                'message_id' => $id,
                'reporter_id' => $this->actor,
                'reported_user_id' => (int) $row['sender_id'],
            ]);
            return [
                'sender_id' => $this->actor,
                'reported_id' => (int) $row['sender_id'],
                'evidence' => $evidence,
            ];
        });
        PhpretroPolarisCms::instance()->notifyUserReport($this->db, $payload['sender_id'], $payload['reported_id'], 'minimail', $payload['evidence']);
    }
}

function phpretroMinimail(): PhpretroMinimail
{
    global $db, $user;
    static $mail;
    if (!$mail || $mail->actor !== (int) $user->id) {
        $mail = new PhpretroMinimail($db, (int) $user->id, new PhpretroLiveSync($db));
    }
    return $mail;
}

function phpretroMinimailRun(callable $action): mixed
{
    try { return $action(); }
    catch (PhpretroMinimailError $error) {
        http_response_code($error->getCode() ?: 400);
        echo '<p>'.htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8').'</p>';
        return null;
    }
}

function phpretroMinimailXjson(array $data): void
{
    header('X-JSON: '.json_encode($data, JSON_UNESCAPED_UNICODE));
}
