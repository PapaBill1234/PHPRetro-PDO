<?php
require_once __DIR__.'/PhpretroLiveSync.php';

class PhpretroWebRestorationsError extends RuntimeException {}

class PhpretroWebRestorations
{
    public const OBJECT_TYPES = ['name', 'room', 'motto', 'stickie', 'animator', 'habbomovie', 'groupname', 'url', 'groupdesc', 'guestbook', 'discussionpost'];

    public function __construct(public Database $db, public int $actor, public PhpretroLiveSync $sync) {}

    public function need(bool $ok, string $message, int $status = 400): void
    {
        if (!$ok) { throw new PhpretroWebRestorationsError($message, $status); }
    }

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

    public function currentCollectible(): ?array
    {
        $month = mktime(0, 0, 0, (int) date('m'), 1, (int) date('Y'));
        $row = $this->db->fetchRow('SELECT id, name, description, image, time FROM phpretro_collectibles WHERE time = ?', [$month]);
        return $row ?: null;
    }

    public function purchaseCollectible(): array
    {
        $this->need($this->actor > 0, 'Sign in required.', 401);
        $item = $this->currentCollectible();
        $this->need((bool) $item, 'No collectible this month.', 404);
        return $this->transaction(function () use ($item) {
            $existing = $this->db->fetchRow('SELECT id FROM phpretro_collectible_purchases WHERE user_id = ? AND collectible_id = ? FOR UPDATE', [$this->actor, $item['id']]);
            if ($existing) { return ['id' => (int) $existing['id'], 'duplicate' => true, 'item' => $item]; }
            $this->db->execute(
                'INSERT INTO phpretro_collectible_purchases (user_id, collectible_id, created_at) VALUES (?, ?, ?)',
                [$this->actor, $item['id'], time()]
            );
            $id = (int) $this->db->insertId();
            $this->sync->recordAndNotify('collectibles.purchased', ['purchase_id' => $id, 'user_id' => $this->actor, 'collectible_id' => (int) $item['id']]);
            return ['id' => $id, 'duplicate' => false, 'item' => $item];
        });
    }

    public function clubGift(int $month): ?array
    {
        if ($month < 1 || $month > 12) { $month = (int) date('n'); }
        $row = $this->db->fetchRow('SELECT month, name, image, description FROM phpretro_club_gifts WHERE month = ?', [$month]);
        return $row ?: null;
    }

    public function hasClub(): bool
    {
        $expires = $this->db->fetchColumn('SELECT club_expire_timestamp FROM users_settings WHERE user_id = ?', [$this->actor]);
        return $expires !== false && (int) $expires > time();
    }

    public function dismissFeed(string $key): void
    {
        $this->need($this->actor > 0, 'Sign in required.', 401);
        $key = substr(trim($key), 0, 64);
        $this->need($key !== '', 'Missing feed item.', 400);
        $this->transaction(function () use ($key) {
            $this->db->execute(
                'INSERT INTO phpretro_feed_dismissals (user_id, item_key, dismissed_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE dismissed_at = VALUES(dismissed_at), synced_at = NULL',
                [$this->actor, $key, time()]
            );
            $this->sync->recordAndNotify('feed.dismissed', ['user_id' => $this->actor, 'item_key' => $key]);
        });
    }

    public function reportObject(string $type, int $objectId): string
    {
        $this->need($this->actor > 0, 'Sign in required.', 401);
        $type = strtolower(trim($type));
        $this->need(in_array($type, self::OBJECT_TYPES, true), 'Unknown object type.', 400);
        $this->need($objectId > 0, 'Invalid object.', 400);
        if ($type === 'room') {
            $this->need((bool) $this->db->fetchColumn('SELECT id FROM rooms WHERE id = ?', [$objectId]), 'Room not found.', 404);
        }
        return $this->transaction(function () use ($type, $objectId) {
            $open = $this->db->fetchColumn(
                'SELECT id FROM phpretro_object_reports WHERE reporter_id = ? AND object_type = ? AND object_id = ? AND status = ?',
                [$this->actor, $type, $objectId, 'open']
            );
            if ($open) { return 'SPAM'; }
            $this->db->execute(
                'INSERT INTO phpretro_object_reports (reporter_id, object_type, object_id, reason, evidence, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$this->actor, $type, $objectId, $type, 'objectId='.$objectId, 'open', time()]
            );
            $this->sync->recordAndNotify('reports.object', [
                'report_id' => (int) $this->db->insertId(),
                'reporter_id' => $this->actor,
                'object_type' => $type,
                'object_id' => $objectId,
            ]);
            return 'SUCCESS';
        });
    }
}

function phpretroWebRestorations(): PhpretroWebRestorations
{
    global $db, $user;
    static $features;
    if (!$features || $features->actor !== (int) $user->id) {
        $features = new PhpretroWebRestorations($db, (int) $user->id, new PhpretroLiveSync($db));
    }
    return $features;
}

function phpretroWebRun(callable $action): mixed
{
    try { return $action(); }
    catch (PhpretroWebRestorationsError $error) {
        http_response_code($error->getCode() ?: 400);
        echo '<p>'.htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8').'</p>';
        return null;
    }
}
