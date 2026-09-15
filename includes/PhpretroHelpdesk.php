<?php
require_once __DIR__.'/PhpretroLiveSync.php';

class PhpretroHelpdeskError extends RuntimeException {}

class PhpretroHelpdesk
{
    public function __construct(public Database $db, public PhpretroLiveSync $sync) {}

    public function need(bool $ok, string $message, int $status = 400): void
    {
        if (!$ok) { throw new PhpretroHelpdeskError($message, $status); }
    }

    public function submit(?int $userId, string $username, string $email, string $ip, string $subject, string $message, int $roomId = 0): int
    {
        $username = substr(trim($username), 0, 25);
        $email = substr(trim($email), 0, 255);
        $subject = substr(trim($subject), 0, 50);
        $message = trim($message);
        $this->need($subject !== '' && $message !== '', 'Please fill in all fields', 400);
        $this->need($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL), 'Invalid email', 400);
        if ($userId && $userId > 0) {
            $row = $this->db->fetchRow('SELECT id, username, mail FROM users WHERE id = ?', [$userId]);
            if ($row) {
                $username = $username !== '' ? $username : (string) $row['username'];
                $email = $email !== '' ? $email : (string) $row['mail'];
            }
        } elseif ($username !== '') {
            $found = $this->db->fetchRow('SELECT id, mail FROM users WHERE username = ?', [$username]);
            if ($found) {
                $userId = (int) $found['id'];
                if ($email === '') { $email = (string) $found['mail']; }
            }
        }
        $this->db->execute(
            'INSERT INTO phpretro_helpdesk_tickets (user_id, username, email, ip, subject, message, room_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$userId ?: null, $username, $email, substr($ip, 0, 45), $subject, $message, max(0, $roomId), 'open', time()]
        );
        $id = (int) $this->db->insertId();
        $this->sync->recordAndNotify('helpdesk.submitted', ['ticket_id' => $id, 'user_id' => $userId ?: 0]);
        return $id;
    }

    public function list(): array
    {
        return $this->db->fetchAll(
            'SELECT id, user_id, username, email, ip, subject, message, room_id, status, picked_by, created_at FROM phpretro_helpdesk_tickets ORDER BY FIELD(status, \'open\', \'picked\', \'closed\'), created_at DESC'
        );
    }

    public function one(int $id): ?array
    {
        $row = $this->db->fetchRow(
            'SELECT id, user_id, username, email, ip, subject, message, room_id, status, picked_by, created_at FROM phpretro_helpdesk_tickets WHERE id = ?',
            [$id]
        );
        return $row ?: null;
    }

    public function pickup(int $id, int $staffId): void
    {
        $this->need($id > 0 && $staffId > 0, 'Invalid ticket.', 400);
        $this->need((bool) $this->one($id), 'Ticket not found.', 404);
        $this->db->execute('UPDATE phpretro_helpdesk_tickets SET status = ?, picked_by = ?, synced_at = NULL WHERE id = ?', ['picked', $staffId, $id]);
        $this->sync->recordAndNotify('helpdesk.picked', ['ticket_id' => $id, 'staff_id' => $staffId]);
    }

    public function remove(int $id): void
    {
        $this->need($id > 0, 'Invalid ticket.', 400);
        $this->need((bool) $this->one($id), 'Ticket not found.', 404);
        $this->db->execute('DELETE FROM phpretro_helpdesk_tickets WHERE id = ?', [$id]);
        $this->sync->recordAndNotify('helpdesk.removed', ['ticket_id' => $id]);
    }
}

function phpretroHelpdesk(): PhpretroHelpdesk
{
    global $db;
    static $desk;
    if (!$desk) { $desk = new PhpretroHelpdesk($db, new PhpretroLiveSync($db)); }
    return $desk;
}
