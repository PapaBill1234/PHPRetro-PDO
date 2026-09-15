<?php
require_once __DIR__.'/PhpretroLiveSync.php';

class PhpretroHomesError extends RuntimeException {}

class PhpretroHomes
{
    public const USER_WIDGETS = ['profilewidget', 'guestbookwidget', 'highscoreswidget', 'badgeswidget', 'friendswidget', 'groupswidget', 'roomswidget'];
    public const BLOCKED_WIDGETS = ['traxplayerwidget', 'ratingwidget'];
    public const ALIASES = [
        'profile' => 'profilewidget', 'guestbook' => 'guestbookwidget', 'highscores' => 'highscoreswidget',
        'badges' => 'badgeswidget', 'friends' => 'friendswidget', 'groups' => 'groupswidget', 'rooms' => 'roomswidget',
        'traxplayer' => 'traxplayerwidget', 'rating' => 'ratingwidget',
    ];

    public function __construct(public Database $db, public int $actor, public PhpretroLiveSync $sync) {}

    public function need(bool $ok, string $message, int $status = 400): void
    {
        if (!$ok) { throw new PhpretroHomesError($message, $status); }
    }

    public function key(string $key): string
    {
        $key = strtolower(trim($key));
        return self::ALIASES[$key] ?? $key;
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

    public function profile(int $userId): array
    {
        $this->need($userId > 0, 'Invalid profile.', 400);
        $row = $this->db->fetchRow(
            "SELECT u.id, u.username, u.motto, u.look, u.account_created, u.last_online, u.online, COALESCE(s.hide_online, '0') AS hide_online, COALESCE(s.tags, '') AS tags, COALESCE(s.guild_id, 0) AS guild_id FROM users u LEFT JOIN users_settings s ON s.user_id = u.id WHERE u.id = ?",
            [$userId]
        );
        $this->need((bool) $row, 'Profile not found.', 404);
        return $row;
    }

    public function layouts(int $userId): array
    {
        return $this->db->fetchAll('SELECT id, user_id, column_number, widget_key, position, visible FROM phpretro_myhabbo_layouts WHERE user_id = ? AND visible = 1 ORDER BY column_number ASC, position ASC, id ASC', [$userId]);
    }

    public function displayLayouts(int $userId): array
    {
        $rows = $this->layouts($userId);
        if ($rows !== []) { return $rows; }
        return [['id' => 0, 'user_id' => $userId, 'column_number' => 1, 'widget_key' => 'profilewidget', 'position' => 0, 'visible' => 1]];
    }

    public function widget(int $id, bool $lock = false): array
    {
        $this->need($id > 0, 'Invalid widget.', 400);
        $row = $this->db->fetchRow('SELECT id, user_id, column_number, widget_key, position, visible FROM phpretro_myhabbo_layouts WHERE id = ?'.($lock ? ' FOR UPDATE' : ''), [$id]);
        $this->need((bool) $row, 'Widget not found.', 404);
        return $row;
    }

    public function requireOwner(array $widget): void
    {
        $this->need((int) $widget['user_id'] === $this->actor, 'Not permitted.', 403);
    }

    public function add(string $key, int $column = 1): array
    {
        $key = $this->key($key);
        $this->need(!in_array($key, self::BLOCKED_WIDGETS, true), 'This widget is unavailable.', 501);
        $this->need(in_array($key, self::USER_WIDGETS, true), 'Unknown widget.', 400);
        $column = $column === 2 ? 2 : 1;
        return $this->transaction(function () use ($key, $column) {
            $existing = $this->db->fetchRow('SELECT id FROM phpretro_myhabbo_layouts WHERE user_id = ? AND widget_key = ? LIMIT 1', [$this->actor, $key]);
            $this->need(!$existing, 'Widget already placed.', 409);
            $position = (int) $this->db->fetchColumn('SELECT COALESCE(MAX(position), -1) FROM phpretro_myhabbo_layouts WHERE user_id = ? AND column_number = ?', [$this->actor, $column]) + 1;
            $this->db->execute('INSERT INTO phpretro_myhabbo_layouts (user_id, column_number, widget_key, position, visible) VALUES (?, ?, ?, ?, 1)', [$this->actor, $column, $key, $position]);
            $id = (int) $this->db->insertId();
            $this->sync->recordAndNotify('homes.widget_added', ['layout_id' => $id, 'user_id' => $this->actor, 'widget_key' => $key]);
            return $this->widget($id);
        });
    }

    public function delete(int $id): void
    {
        $this->transaction(function () use ($id) {
            $widget = $this->widget($id, true);
            $this->requireOwner($widget);
            $this->need($widget['widget_key'] !== 'profilewidget', 'The profile widget cannot be removed.', 403);
            $this->db->execute('DELETE FROM phpretro_myhabbo_layouts WHERE id = ? AND user_id = ?', [$id, $this->actor]);
            $this->sync->recordAndNotify('homes.widget_deleted', ['layout_id' => $id, 'user_id' => $this->actor, 'widget_key' => $widget['widget_key']]);
        });
    }

    public function place(int $id, int $column, int $position): void
    {
        $column = $column === 2 ? 2 : 1;
        $position = max(0, $position);
        $this->transaction(function () use ($id, $column, $position) {
            $widget = $this->widget($id, true);
            $this->requireOwner($widget);
            $this->db->execute('UPDATE phpretro_myhabbo_layouts SET position = position + 1 WHERE user_id = ? AND column_number = ? AND position >= ? AND id <> ?', [$this->actor, $column, $position, $id]);
            $this->db->execute('UPDATE phpretro_myhabbo_layouts SET column_number = ?, position = ?, synced_at = NULL WHERE id = ? AND user_id = ?', [$column, $position, $id, $this->actor]);
            $this->sync->recordAndNotify('homes.widget_moved', ['layout_id' => $id, 'user_id' => $this->actor, 'column_number' => $column, 'position' => $position]);
        });
    }

    public function saveCoordinates(string $raw): void
    {
        $this->transaction(function () use ($raw) {
            foreach (explode('/', $raw) as $piece) {
                if ($piece === '') { continue; }
                $bits = explode(':', $piece, 2);
                $id = (int) $bits[0];
                if ($id < 1 || !isset($bits[1])) { continue; }
                $coords = explode(',', $bits[1]);
                $x = (int) ($coords[0] ?? 0);
                $y = (int) ($coords[1] ?? 0);
                $column = $x >= 450 ? 2 : 1;
                $position = max(0, (int) floor($y / 50));
                $widget = $this->db->fetchRow('SELECT id, user_id FROM phpretro_myhabbo_layouts WHERE id = ? FOR UPDATE', [$id]);
                if (!$widget || (int) $widget['user_id'] !== $this->actor) { continue; }
                $this->db->execute('UPDATE phpretro_myhabbo_layouts SET column_number = ?, position = ?, synced_at = NULL WHERE id = ? AND user_id = ?', [$column, $position, $id, $this->actor]);
            }
            $this->sync->recordAndNotify('homes.layout_saved', ['user_id' => $this->actor]);
        });
    }

    public function guestbook(int $widgetId): array
    {
        $widget = $this->widget($widgetId);
        $this->need($widget['widget_key'] === 'guestbookwidget', 'Not a guestbook.', 400);
        return $widget;
    }

    public function guestbookEntries(int $profileUserId, int $offset = 0, int $limit = 20): array
    {
        $limit = min(50, max(1, $limit));
        $offset = max(0, $offset);
        return $this->db->fetchAll(
            'SELECT g.id, g.profile_user_id, g.author_user_id, g.message, g.created_at, u.username, u.look, u.online FROM phpretro_myhabbo_guestbook g JOIN users u ON u.id = g.author_user_id WHERE g.profile_user_id = ? ORDER BY g.id DESC LIMIT ? OFFSET ?',
            [$profileUserId, $limit, $offset]
        );
    }

    public function addGuestbook(int $widgetId, string $message): array
    {
        $widget = $this->guestbook($widgetId);
        $message = trim($message);
        $this->need($this->actor > 0, 'Sign in required.', 401);
        $this->need($message !== '' && mb_strlen($message, 'UTF-8') <= 1000, 'Message must be between 1 and 1000 characters.', 400);
        return $this->transaction(function () use ($widget, $message) {
            $now = time();
            $this->db->execute('INSERT INTO phpretro_myhabbo_guestbook (profile_user_id, author_user_id, message, created_at) VALUES (?, ?, ?, ?)', [(int) $widget['user_id'], $this->actor, $message, $now]);
            $id = (int) $this->db->insertId();
            $this->sync->recordAndNotify('homes.guestbook_added', ['entry_id' => $id, 'profile_user_id' => (int) $widget['user_id'], 'author_user_id' => $this->actor]);
            return $this->db->fetchRow('SELECT g.id, g.profile_user_id, g.author_user_id, g.message, g.created_at, u.username, u.look, u.online FROM phpretro_myhabbo_guestbook g JOIN users u ON u.id = g.author_user_id WHERE g.id = ?', [$id]);
        });
    }

    public function removeGuestbook(int $entryId): void
    {
        $this->transaction(function () use ($entryId) {
            $this->need($entryId > 0, 'Invalid entry.', 400);
            $row = $this->db->fetchRow('SELECT id, profile_user_id, author_user_id FROM phpretro_myhabbo_guestbook WHERE id = ? FOR UPDATE', [$entryId]);
            $this->need((bool) $row, 'Entry not found.', 404);
            $this->need((int) $row['author_user_id'] === $this->actor || (int) $row['profile_user_id'] === $this->actor, 'Not permitted.', 403);
            $this->db->execute('DELETE FROM phpretro_myhabbo_guestbook WHERE id = ?', [$entryId]);
            $this->sync->recordAndNotify('homes.guestbook_removed', ['entry_id' => $entryId, 'user_id' => $this->actor]);
        });
    }

    public function friends(int $userId, string $search = '', int $offset = 0, int $limit = 20): array
    {
        $limit = min(50, max(1, $limit));
        $offset = max(0, $offset);
        $search = trim($search);
        $sql = 'SELECT u.id, u.username, u.look, u.account_created FROM messenger_friendships f JOIN users u ON u.id = f.user_two_id WHERE f.user_one_id = ?';
        $params = [$userId];
        if ($search !== '') {
            $sql .= ' AND LOCATE(?, u.username) > 0';
            $params[] = $search;
        }
        $sql .= ' ORDER BY u.username, u.id LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;
        return $this->db->fetchAll($sql, $params);
    }

    public function friendCount(int $userId, string $search = ''): int
    {
        $search = trim($search);
        if ($search === '') {
            return (int) $this->db->fetchColumn('SELECT COUNT(*) FROM messenger_friendships WHERE user_one_id = ?', [$userId]);
        }
        return (int) $this->db->fetchColumn('SELECT COUNT(*) FROM messenger_friendships f JOIN users u ON u.id = f.user_two_id WHERE f.user_one_id = ? AND LOCATE(?, u.username) > 0', [$userId, $search]);
    }

    public function badges(int $userId): array
    {
        return $this->db->fetchAll('SELECT badge_code FROM users_badges WHERE user_id = ? ORDER BY slot_id > 0 DESC, slot_id, id', [$userId]);
    }

    public function groups(int $userId): array
    {
        return $this->db->fetchAll('SELECT g.id, g.name, g.badge, m.level_id FROM guilds_members m JOIN guilds g ON g.id = m.guild_id WHERE m.user_id = ? AND m.level_id IN (0, 1, 2) ORDER BY g.name, g.id', [$userId]);
    }

    public function rooms(int $userId): array
    {
        return $this->db->fetchAll('SELECT id, name, description FROM rooms WHERE owner_id = ? ORDER BY id', [$userId]);
    }
}

function phpretroHomes(): PhpretroHomes
{
    global $db, $user;
    static $homes;
    if (!$homes || $homes->actor !== (int) $user->id) {
        $homes = new PhpretroHomes($db, (int) $user->id, new PhpretroLiveSync($db));
    }
    return $homes;
}

function phpretroHomesRun(callable $action): mixed
{
    try { return $action(); }
    catch (PhpretroHomesError $error) {
        if ($error->getCode() === 501) { habbletUnavailable($error->getMessage()); }
        else {
            http_response_code($error->getCode() ?: 400);
            echo '<p>'.htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8').'</p>';
        }
        return null;
    }
}
