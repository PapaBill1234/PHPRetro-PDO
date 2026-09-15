<?php
require_once __DIR__.'/PhpretroLiveSync.php';
require_once __DIR__.'/PhpretroGroupUrls.php';

class PhpretroHomesError extends RuntimeException {}

class PhpretroHomes
{
    public const USER_WIDGETS = ['profilewidget', 'guestbookwidget', 'highscoreswidget', 'badgeswidget', 'friendswidget', 'groupswidget', 'roomswidget', 'ratingwidget'];
    public const GROUP_WIDGETS = ['groupinfowidget', 'guestbookwidget', 'memberwidget'];
    public const BLOCKED_WIDGETS = ['traxplayerwidget'];
    public const ALIASES = [
        'profile' => 'profilewidget', 'guestbook' => 'guestbookwidget', 'highscores' => 'highscoreswidget',
        'badges' => 'badgeswidget', 'friends' => 'friendswidget', 'groups' => 'groupswidget', 'rooms' => 'roomswidget',
        'traxplayer' => 'traxplayerwidget', 'rating' => 'ratingwidget',
        'groupinfo' => 'groupinfowidget', 'members' => 'memberwidget', 'member' => 'memberwidget',
    ];
    public const STORE_TYPES = [
        'stickers' => 'sticker', 'backgrounds' => 'background', 'notes' => 'note',
        'stickie_notes' => 'note', 'widgets' => 'widget',
    ];
    public const SKINS = [
        1 => 'defaultskin', 2 => 'speechbubbleskin', 3 => 'metalskin', 4 => 'noteitskin',
        5 => 'notepadskin', 6 => 'goldenskin', 7 => 'hc_machineskin', 8 => 'hc_pillowskin', 9 => 'default',
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

    public function rank(): int
    {
        return (int) ($this->db->fetchColumn('SELECT rank FROM users WHERE id = ?', [$this->actor]) ?: 0);
    }

    public function hasClub(): bool
    {
        $expires = $this->db->fetchColumn('SELECT club_expire_timestamp FROM users_settings WHERE user_id = ?', [$this->actor]);
        return $expires !== false && (int) $expires > time();
    }

    public function inHotel(): bool
    {
        $online = $this->db->fetchColumn('SELECT online FROM users WHERE id = ?', [$this->actor]);
        return $online === '1' || $online === '2';
    }

    public function editingGuild(): int
    {
        return (int) ($_SESSION['group_page_edit'] ?? 0);
    }

    public function placement(): string
    {
        return $this->editingGuild() > 0 ? 'groups' : 'homes';
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
        return $this->db->fetchAll('SELECT id, user_id, guild_id, column_number, widget_key, position, visible, privacy FROM phpretro_myhabbo_layouts WHERE user_id = ? AND guild_id = 0 AND visible = 1 ORDER BY column_number ASC, position ASC, id ASC', [$userId]);
    }

    public function displayLayouts(int $userId): array
    {
        $rows = $this->layouts($userId);
        if ($rows !== []) { return $rows; }
        return [['id' => 0, 'user_id' => $userId, 'guild_id' => 0, 'column_number' => 1, 'widget_key' => 'profilewidget', 'position' => 0, 'visible' => 1, 'privacy' => 'public', 'scope' => 'user']];
    }

    public function groupLayouts(int $guildId): array
    {
        return $this->db->fetchAll('SELECT id, user_id, guild_id, column_number, widget_key, position, visible, privacy FROM phpretro_myhabbo_layouts WHERE guild_id = ? AND visible = 1 ORDER BY column_number ASC, position ASC, id ASC', [$guildId]);
    }

    public function displayGroupLayouts(int $guildId): array
    {
        $rows = $this->groupLayouts($guildId);
        if ($rows !== []) { return $rows; }
        return [['id' => 0, 'guild_id' => $guildId, 'column_number' => 1, 'widget_key' => 'groupinfowidget', 'position' => 0, 'visible' => 1, 'privacy' => 'public', 'scope' => 'group']];
    }

    public function widget(int $id, bool $lock = false): array
    {
        $this->need($id > 0, 'Invalid widget.', 400);
        $lockSql = $lock ? ' FOR UPDATE' : '';
        $row = $this->db->fetchRow('SELECT id, user_id, guild_id, column_number, widget_key, position, visible, privacy FROM phpretro_myhabbo_layouts WHERE id = ?'.$lockSql, [$id]);
        $this->need((bool) $row, 'Widget not found.', 404);
        $row['scope'] = (int) $row['guild_id'] > 0 ? 'group' : 'user';
        return $row;
    }

    public function requireOwner(array $widget): void
    {
        if (($widget['scope'] ?? 'user') === 'group') {
            $this->need($this->canEditGroup((int) $widget['guild_id']), 'Not permitted.', 403);
            return;
        }
        $this->need((int) $widget['user_id'] === $this->actor, 'Not permitted.', 403);
    }

    public function canEditGroup(int $guildId): bool
    {
        if ($guildId < 1 || $this->actor < 1) { return false; }
        $owner = (int) $this->db->fetchColumn('SELECT user_id FROM guilds WHERE id = ?', [$guildId]);
        if ($owner === $this->actor) { return true; }
        $level = $this->db->fetchColumn('SELECT level_id FROM guilds_members WHERE guild_id = ? AND user_id = ?', [$guildId, $this->actor]);
        return $level !== false && (int) $level === 1;
    }

    public function groupMember(int $guildId, int $userId): bool
    {
        $level = $this->db->fetchColumn('SELECT level_id FROM guilds_members WHERE guild_id = ? AND user_id = ?', [$guildId, $userId]);
        return $level !== false && in_array((int) $level, [0, 1, 2], true);
    }

    public function areFriends(int $a, int $b): bool
    {
        if ($a === $b) { return true; }
        return (bool) $this->db->fetchColumn(
            'SELECT id FROM messenger_friendships WHERE user_one_id = ? AND user_two_id = ? LIMIT 1',
            [$a, $b]
        );
    }

    public function add(string $key, int $column = 1): array
    {
        $guildId = $this->editingGuild();
        if ($guildId > 0) { return $this->addGroup($guildId, $key, $column); }
        $key = $this->resolveWidgetKey($key, 'homes');
        $this->need(!in_array($key, self::BLOCKED_WIDGETS, true), 'This widget is unavailable.', 501);
        $this->need(in_array($key, self::USER_WIDGETS, true), 'Unknown widget.', 400);
        $column = $column === 2 ? 2 : 1;
        return $this->transaction(function () use ($key, $column) {
            $existing = $this->db->fetchRow('SELECT id FROM phpretro_myhabbo_layouts WHERE user_id = ? AND guild_id = 0 AND widget_key = ? LIMIT 1', [$this->actor, $key]);
            $this->need(!$existing, 'Widget already placed.', 409);
            $position = (int) $this->db->fetchColumn('SELECT COALESCE(MAX(position), -1) FROM phpretro_myhabbo_layouts WHERE user_id = ? AND guild_id = 0 AND column_number = ?', [$this->actor, $column]) + 1;
            $this->db->execute('INSERT INTO phpretro_myhabbo_layouts (user_id, guild_id, column_number, widget_key, position, visible, privacy) VALUES (?, 0, ?, ?, ?, 1, ?)', [$this->actor, $column, $key, $position, 'public']);
            $id = (int) $this->db->insertId();
            $this->sync->recordAndNotify('homes.widget_added', ['layout_id' => $id, 'user_id' => $this->actor, 'widget_key' => $key]);
            return $this->widget($id);
        });
    }

    public function addGroup(int $guildId, string $key, int $column = 1): array
    {
        $this->need($this->canEditGroup($guildId), 'Not permitted.', 403);
        $key = $this->resolveWidgetKey($key, 'groups');
        $this->need(!in_array($key, self::BLOCKED_WIDGETS, true), 'This widget is unavailable.', 501);
        $this->need(in_array($key, self::GROUP_WIDGETS, true), 'Unknown widget.', 400);
        $column = $column === 2 ? 2 : 1;
        return $this->transaction(function () use ($guildId, $key, $column) {
            $ownerId = (int) $this->db->fetchColumn('SELECT user_id FROM guilds WHERE id = ?', [$guildId]);
            $this->need($ownerId > 0, 'Group not found.', 404);
            $existing = $this->db->fetchRow('SELECT id FROM phpretro_myhabbo_layouts WHERE guild_id = ? AND widget_key = ? LIMIT 1', [$guildId, $key]);
            $this->need(!$existing, 'Widget already placed.', 409);
            $position = (int) $this->db->fetchColumn('SELECT COALESCE(MAX(position), -1) FROM phpretro_myhabbo_layouts WHERE guild_id = ? AND column_number = ?', [$guildId, $column]) + 1;
            $this->db->execute('INSERT INTO phpretro_myhabbo_layouts (user_id, guild_id, column_number, widget_key, position, visible, privacy) VALUES (?, ?, ?, ?, ?, 1, ?)', [$ownerId, $guildId, $column, $key, $position, 'public']);
            $id = (int) $this->db->insertId();
            $this->sync->recordAndNotify('homes.group_widget_added', ['layout_id' => $id, 'guild_id' => $guildId, 'widget_key' => $key, 'user_id' => $this->actor]);
            return $this->widget($id);
        });
    }

    public function resolveWidgetKey(string $key, string $placement): string
    {
        $key = trim($key);
        if ($key !== '' && ctype_digit($key)) {
            $row = $this->db->fetchRow('SELECT data, type, placement FROM phpretro_homes_catalogue WHERE id = ?', [(int) $key]);
            $this->need((bool) $row && $row['type'] === 'widget', 'Unknown widget.', 400);
            $this->need($row['placement'] === $placement || $row['placement'] === 'anywhere', 'Unknown widget.', 400);
            $key = $row['data'];
        }
        return $this->key($key);
    }

    public function delete(int $id): void
    {
        $this->transaction(function () use ($id) {
            $widget = $this->widget($id, true);
            $this->requireOwner($widget);
            $locked = ($widget['scope'] ?? 'user') === 'group' ? 'groupinfowidget' : 'profilewidget';
            $this->need($widget['widget_key'] !== $locked, 'This widget cannot be removed.', 403);
            if (($widget['scope'] ?? 'user') === 'group') {
                $this->db->execute('DELETE FROM phpretro_myhabbo_layouts WHERE id = ? AND guild_id = ?', [$id, (int) $widget['guild_id']]);
                $this->sync->recordAndNotify('homes.group_widget_deleted', ['layout_id' => $id, 'guild_id' => (int) $widget['guild_id'], 'widget_key' => $widget['widget_key']]);
                return;
            }
            $this->db->execute('DELETE FROM phpretro_myhabbo_layouts WHERE id = ? AND user_id = ? AND guild_id = 0', [$id, $this->actor]);
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
            if (($widget['scope'] ?? 'user') === 'group') {
                $guildId = (int) $widget['guild_id'];
                $this->db->execute('UPDATE phpretro_myhabbo_layouts SET position = position + 1 WHERE guild_id = ? AND column_number = ? AND position >= ? AND id <> ?', [$guildId, $column, $position, $id]);
                $this->db->execute('UPDATE phpretro_myhabbo_layouts SET column_number = ?, position = ?, synced_at = NULL WHERE id = ? AND guild_id = ?', [$column, $position, $id, $guildId]);
                $this->sync->recordAndNotify('homes.group_widget_moved', ['layout_id' => $id, 'guild_id' => $guildId, 'column_number' => $column, 'position' => $position]);
                return;
            }
            $this->db->execute('UPDATE phpretro_myhabbo_layouts SET position = position + 1 WHERE user_id = ? AND guild_id = 0 AND column_number = ? AND position >= ? AND id <> ?', [$this->actor, $column, $position, $id]);
            $this->db->execute('UPDATE phpretro_myhabbo_layouts SET column_number = ?, position = ?, synced_at = NULL WHERE id = ? AND user_id = ? AND guild_id = 0', [$column, $position, $id, $this->actor]);
            $this->sync->recordAndNotify('homes.widget_moved', ['layout_id' => $id, 'user_id' => $this->actor, 'column_number' => $column, 'position' => $position]);
        });
    }

    public function saveCoordinates(string $raw): void
    {
        $this->saveLayout(['widgets' => $raw]);
    }

    public function saveLayout(array $post): void
    {
        $guildId = $this->editingGuild();
        $this->transaction(function () use ($post, $guildId) {
            $this->saveWidgetCoords((string) ($post['widgets'] ?? ''), $guildId);
            $this->saveItemCoords((string) ($post['stickers'] ?? ''), 'sticker', $guildId);
            $this->saveItemCoords((string) ($post['stickienotes'] ?? ''), 'stickie', $guildId);
            $background = trim((string) ($post['background'] ?? ''));
            if ($background !== '') { $this->saveBackground($background, $guildId); }
            $event = $guildId > 0 ? 'homes.group_layout_saved' : 'homes.layout_saved';
            $this->sync->recordAndNotify($event, $guildId > 0 ? ['guild_id' => $guildId, 'user_id' => $this->actor] : ['user_id' => $this->actor]);
        });
    }

    private function saveWidgetCoords(string $raw, int $guildId): void
    {
        foreach ($this->parseCoords($raw) as $id => $coords) {
            $column = $coords['x'] >= 450 ? 2 : 1;
            $position = max(0, (int) floor($coords['y'] / 50));
            if ($guildId > 0) {
                $widget = $this->db->fetchRow('SELECT id, guild_id FROM phpretro_myhabbo_layouts WHERE id = ? FOR UPDATE', [$id]);
                if (!$widget || (int) $widget['guild_id'] !== $guildId) { continue; }
                $this->db->execute('UPDATE phpretro_myhabbo_layouts SET column_number = ?, position = ?, synced_at = NULL WHERE id = ? AND guild_id = ?', [$column, $position, $id, $guildId]);
                continue;
            }
            $widget = $this->db->fetchRow('SELECT id, user_id, guild_id FROM phpretro_myhabbo_layouts WHERE id = ? FOR UPDATE', [$id]);
            if (!$widget || (int) $widget['user_id'] !== $this->actor || (int) $widget['guild_id'] !== 0) { continue; }
            $this->db->execute('UPDATE phpretro_myhabbo_layouts SET column_number = ?, position = ?, synced_at = NULL WHERE id = ? AND user_id = ?', [$column, $position, $id, $this->actor]);
        }
    }

    private function saveItemCoords(string $raw, string $type, int $guildId): void
    {
        foreach ($this->parseCoords($raw) as $id => $coords) {
            $item = $this->db->fetchRow('SELECT id, user_id, guild_id, placed FROM phpretro_homes_items WHERE id = ? AND item_type = ? FOR UPDATE', [$id, $type]);
            if (!$item || (int) $item['placed'] !== 1) { continue; }
            if ($guildId > 0) {
                if ((int) $item['guild_id'] !== $guildId) { continue; }
            } else {
                if ((int) $item['user_id'] !== $this->actor || (int) $item['guild_id'] !== 0) { continue; }
            }
            $this->db->execute('UPDATE phpretro_homes_items SET x = ?, y = ?, z = ?, synced_at = NULL WHERE id = ?', [$coords['x'], $coords['y'], $coords['z'], $id]);
        }
    }

    private function saveBackground(string $raw, int $guildId): void
    {
        $bits = explode(':', $raw, 2);
        $id = (int) $bits[0];
        if ($id < 1) { return; }
        $item = $this->db->fetchRow('SELECT id, user_id, catalogue_id, item_type FROM phpretro_homes_items WHERE id = ? FOR UPDATE', [$id]);
        if (!$item || $item['item_type'] !== 'background' || (int) $item['user_id'] !== $this->actor) { return; }
        $this->db->execute(
            'UPDATE phpretro_homes_items SET placed = 0, guild_id = 0, synced_at = NULL WHERE item_type = ? AND placed = 1 AND '.($guildId > 0 ? 'guild_id = ?' : 'user_id = ? AND guild_id = 0'),
            $guildId > 0 ? ['background', $guildId] : ['background', $this->actor]
        );
        $this->db->execute('UPDATE phpretro_homes_items SET placed = 1, guild_id = ?, x = 0, y = 0, z = 0, synced_at = NULL WHERE id = ? AND user_id = ?', [$guildId, $id, $this->actor]);
        $this->sync->recordAndNotify('homes.background_placed', ['item_id' => $id, 'user_id' => $this->actor, 'guild_id' => $guildId]);
    }

    private function parseCoords(string $raw): array
    {
        $out = [];
        foreach (explode('/', $raw) as $piece) {
            if ($piece === '') { continue; }
            $bits = explode(':', $piece, 2);
            $id = (int) $bits[0];
            if ($id < 1 || !isset($bits[1])) { continue; }
            $coords = explode(',', $bits[1]);
            $out[$id] = ['x' => (int) ($coords[0] ?? 0), 'y' => (int) ($coords[1] ?? 0), 'z' => (int) ($coords[2] ?? 0)];
        }
        return $out;
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

    public function guestbookEntriesForWidget(array $widget, int $offset = 0, int $limit = 20): array
    {
        if (($widget['scope'] ?? 'user') === 'group') {
            $limit = min(50, max(1, $limit));
            $offset = max(0, $offset);
            return $this->db->fetchAll(
                'SELECT g.id, g.guild_id, g.author_user_id, g.message, g.created_at, u.username, u.look, u.online FROM phpretro_group_guestbook g JOIN users u ON u.id = g.author_user_id WHERE g.guild_id = ? ORDER BY g.id DESC LIMIT ? OFFSET ?',
                [(int) $widget['guild_id'], $limit, $offset]
            );
        }
        return $this->guestbookEntries((int) $widget['user_id'], $offset, $limit);
    }

    public function addGuestbook(int $widgetId, string $message): array
    {
        $widget = $this->guestbook($widgetId);
        $message = trim($message);
        $this->need($this->actor > 0, 'Sign in required.', 401);
        $this->need($message !== '' && mb_strlen($message, 'UTF-8') <= 1000, 'Message must be between 1 and 1000 characters.', 400);
        $privacy = ($widget['privacy'] ?? 'public') === 'private' ? 'private' : 'public';
        if (($widget['scope'] ?? 'user') === 'group') {
            $guildId = (int) $widget['guild_id'];
            if ($privacy === 'private') {
                $this->need($this->groupMember($guildId, $this->actor), 'Only group members can post to this guestbook.', 403);
            }
        } elseif ($privacy === 'private') {
            $this->need($this->areFriends((int) $widget['user_id'], $this->actor), 'Only friends can post to this guestbook.', 403);
        }
        return $this->transaction(function () use ($widget, $message) {
            $now = time();
            if (($widget['scope'] ?? 'user') === 'group') {
                $this->db->execute('INSERT INTO phpretro_group_guestbook (guild_id, author_user_id, message, created_at) VALUES (?, ?, ?, ?)', [(int) $widget['guild_id'], $this->actor, $message, $now]);
                $id = (int) $this->db->insertId();
                $this->sync->recordAndNotify('homes.group_guestbook_added', ['entry_id' => $id, 'guild_id' => (int) $widget['guild_id'], 'author_user_id' => $this->actor]);
                $row = $this->db->fetchRow('SELECT g.id, g.guild_id, g.author_user_id, g.message, g.created_at, u.username, u.look, u.online FROM phpretro_group_guestbook g JOIN users u ON u.id = g.author_user_id WHERE g.id = ?', [$id]);
                $row['profile_user_id'] = 0;
                return $row;
            }
            $this->db->execute('INSERT INTO phpretro_myhabbo_guestbook (profile_user_id, author_user_id, message, created_at) VALUES (?, ?, ?, ?)', [(int) $widget['user_id'], $this->actor, $message, $now]);
            $id = (int) $this->db->insertId();
            $this->sync->recordAndNotify('homes.guestbook_added', ['entry_id' => $id, 'profile_user_id' => (int) $widget['user_id'], 'author_user_id' => $this->actor]);
            return $this->db->fetchRow('SELECT g.id, g.profile_user_id, g.author_user_id, g.message, g.created_at, u.username, u.look, u.online FROM phpretro_myhabbo_guestbook g JOIN users u ON u.id = g.author_user_id WHERE g.id = ?', [$id]);
        });
    }

    public function removeGuestbook(int $entryId, ?int $widgetId = null): void
    {
        $this->transaction(function () use ($entryId, $widgetId) {
            $this->need($entryId > 0, 'Invalid entry.', 400);
            if ($widgetId) {
                $widget = $this->guestbook($widgetId);
                if (($widget['scope'] ?? 'user') === 'group') {
                    $this->removeGroupGuestbookRow($entryId);
                    return;
                }
            }
            $row = $this->db->fetchRow('SELECT id, profile_user_id, author_user_id FROM phpretro_myhabbo_guestbook WHERE id = ? FOR UPDATE', [$entryId]);
            if (!$row) {
                $this->removeGroupGuestbookRow($entryId);
                return;
            }
            $this->need((int) $row['author_user_id'] === $this->actor || (int) $row['profile_user_id'] === $this->actor, 'Not permitted.', 403);
            $this->db->execute('DELETE FROM phpretro_myhabbo_guestbook WHERE id = ?', [$entryId]);
            $this->sync->recordAndNotify('homes.guestbook_removed', ['entry_id' => $entryId, 'user_id' => $this->actor]);
        });
    }

    private function removeGroupGuestbookRow(int $entryId): void
    {
        $row = $this->db->fetchRow('SELECT id, guild_id, author_user_id FROM phpretro_group_guestbook WHERE id = ? FOR UPDATE', [$entryId]);
        $this->need((bool) $row, 'Entry not found.', 404);
        $this->need((int) $row['author_user_id'] === $this->actor || $this->canEditGroup((int) $row['guild_id']), 'Not permitted.', 403);
        $this->db->execute('DELETE FROM phpretro_group_guestbook WHERE id = ?', [$entryId]);
        $this->sync->recordAndNotify('homes.group_guestbook_removed', ['entry_id' => $entryId, 'user_id' => $this->actor]);
    }

    public function configureGuestbook(int $widgetId): string
    {
        return $this->transaction(function () use ($widgetId) {
            $widget = $this->guestbook($widgetId);
            $this->requireOwner($widget);
            $next = ($widget['privacy'] ?? 'public') === 'private' ? 'public' : 'private';
            $this->db->execute('UPDATE phpretro_myhabbo_layouts SET privacy = ?, synced_at = NULL WHERE id = ?', [$next, $widgetId]);
            $this->sync->recordAndNotify('homes.guestbook_privacy', ['layout_id' => $widgetId, 'privacy' => $next, 'user_id' => $this->actor, 'scope' => $widget['scope'] ?? 'user']);
            return $next;
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

    public function guild(int $guildId): array
    {
        $this->need($guildId > 0, 'Invalid group.', 400);
        $row = $this->db->fetchRow(
            "SELECT g.id, g.user_id, g.name, g.description, g.room_id, g.state, g.badge, g.date_created, u.username AS owner_username FROM guilds g LEFT JOIN users u ON u.id = g.user_id WHERE g.id = ?",
            [$guildId]
        );
        $this->need((bool) $row, 'Group not found.', 404);
        return $row;
    }

    public function guildMembers(int $guildId, int $offset = 0, int $limit = 50): array
    {
        $limit = min(50, max(1, $limit));
        $offset = max(0, $offset);
        return $this->db->fetchAll(
            'SELECT u.id, u.username, u.look, m.level_id FROM guilds_members m JOIN users u ON u.id = m.user_id WHERE m.guild_id = ? AND m.level_id IN (0, 1, 2) ORDER BY m.level_id ASC, u.username, u.id LIMIT ? OFFSET ?',
            [$guildId, $limit, $offset]
        );
    }

    public function guildMemberCount(int $guildId): int
    {
        return (int) $this->db->fetchColumn('SELECT COUNT(*) FROM guilds_members WHERE guild_id = ? AND level_id IN (0, 1, 2)', [$guildId]);
    }

    public function rate(int $ownerId, int $widgetId, int $rating): array
    {
        $this->need($this->actor > 0, 'Sign in required.', 401);
        $this->need($ownerId > 0 && $ownerId !== $this->actor, 'You cannot vote for yourself.', 400);
        $this->need($rating >= 1 && $rating <= 5, 'Rating must be between 1 and 5.', 400);
        $widget = $this->widget($widgetId);
        $this->need($widget['widget_key'] === 'ratingwidget' && (int) $widget['user_id'] === $ownerId, 'Not a rating widget.', 400);
        $this->transaction(function () use ($ownerId, $rating) {
            $existing = $this->db->fetchRow('SELECT id FROM phpretro_home_ratings WHERE profile_user_id = ? AND rater_id = ? FOR UPDATE', [$ownerId, $this->actor]);
            if ($existing) { return; }
            $this->db->execute('INSERT INTO phpretro_home_ratings (profile_user_id, rater_id, rating, created_at) VALUES (?, ?, ?, ?)', [$ownerId, $this->actor, $rating, time()]);
            $this->sync->recordAndNotify('homes.rated', ['profile_user_id' => $ownerId, 'rater_id' => $this->actor, 'rating' => $rating]);
        });
        return $this->ratingSummary($ownerId);
    }

    public function resetRatings(int $ownerId, int $widgetId): array
    {
        $this->need($ownerId === $this->actor, 'Not permitted.', 403);
        $widget = $this->widget($widgetId);
        $this->need($widget['widget_key'] === 'ratingwidget' && (int) $widget['user_id'] === $ownerId, 'Not a rating widget.', 400);
        $this->transaction(function () use ($ownerId) {
            $this->db->execute('DELETE FROM phpretro_home_ratings WHERE profile_user_id = ?', [$ownerId]);
            $this->sync->recordAndNotify('homes.ratings_reset', ['profile_user_id' => $ownerId, 'user_id' => $this->actor]);
        });
        return $this->ratingSummary($ownerId);
    }

    public function ratingSummary(int $ownerId): array
    {
        $row = $this->db->fetchRow('SELECT COUNT(*) AS total, COALESCE(SUM(rating), 0) AS tally, SUM(CASE WHEN rating > 3 THEN 1 ELSE 0 END) AS high FROM phpretro_home_ratings WHERE profile_user_id = ?', [$ownerId]) ?: ['total' => 0, 'tally' => 0, 'high' => 0];
        $total = (int) $row['total'];
        $mine = $this->actor > 0 ? (int) $this->db->fetchColumn('SELECT COUNT(*) FROM phpretro_home_ratings WHERE profile_user_id = ? AND rater_id = ?', [$ownerId, $this->actor]) : 0;
        $average = $total === 0 ? 0.0 : round(((int) $row['tally']) / $total, 1);
        return [
            'total' => $total,
            'high' => (int) $row['high'],
            'average' => $average,
            'px' => (int) ceil(($average * 150) / 5),
            'mine' => $mine > 0,
            'owner' => $ownerId === $this->actor,
        ];
    }

    public function storeType(string $raw): string
    {
        $raw = strtolower(trim($raw));
        return self::STORE_TYPES[$raw] ?? match ($raw) {
            '1', 'sticker' => 'sticker',
            '2', 'widget' => 'widget',
            '3', 'note' => 'note',
            '4', 'background' => 'background',
            default => 'sticker',
        };
    }

    public function catalogue(int $id): ?array
    {
        if ($id < 1) { return null; }
        return $this->db->fetchRow('SELECT id, name, description, type, data, price, amount, category, category_id, min_rank, placement FROM phpretro_homes_catalogue WHERE id = ?', [$id]) ?: null;
    }

    public function storeCategories(string $type): array
    {
        $placement = $this->placement();
        return $this->db->fetchAll(
            'SELECT category_id, category FROM phpretro_homes_catalogue WHERE type = ? AND min_rank <= ? AND (placement = ? OR placement = ?) GROUP BY category_id, category ORDER BY category ASC, category_id ASC',
            [$type, $this->rank(), $placement, 'anywhere']
        );
    }

    public function storeItems(string $type, int $categoryId): array
    {
        $placement = $this->placement();
        $sql = 'SELECT id, name, description, type, data, price, amount, category, category_id, min_rank, placement FROM phpretro_homes_catalogue WHERE type = ? AND min_rank <= ? AND (placement = ? OR placement = ?)';
        $params = [$type, $this->rank(), $placement, 'anywhere'];
        if ($categoryId > 0) {
            $sql .= ' AND category_id = ?';
            $params[] = $categoryId;
        }
        $sql .= ' ORDER BY id DESC';
        return $this->db->fetchAll($sql, $params);
    }

    public function itemCss(string $type, string $data, bool $preview = false): string
    {
        $prefix = match ($type) {
            'sticker', '1' => 's_',
            'widget', '2' => 'w_',
            'note', '3' => 'commodity_',
            'background', '4' => 'b_',
            default => '',
        };
        return $prefix.$data.($preview ? '_pre' : '');
    }

    public function purchase(int $catalogueId): int
    {
        $this->need($this->actor > 0, 'Sign in required.', 401);
        $item = $this->catalogue($catalogueId);
        $this->need((bool) $item, 'Unknown product.', 404);
        $this->need($item['type'] !== 'widget', "You're not allowed to purchase this.", 400);
        $this->need((int) $item['min_rank'] <= $this->rank(), "You're not allowed to purchase this.", 400);
        $placement = $this->placement();
        $this->need($item['placement'] === 'anywhere' || $item['placement'] === $placement, "You're not allowed to purchase this.", 400);
        return $this->transaction(function () use ($item) {
            $user = $this->db->fetchRow('SELECT id, credits, online FROM users WHERE id = ? FOR UPDATE', [$this->actor]);
            $this->need((bool) $user, 'Sign in required.', 401);
            $this->need($user['online'] !== '1' && $user['online'] !== '2', 'Leave the hotel first. PolarIS has no take-credits command, so buying while online would be overwritten when you disconnect.', 409);
            if (in_array($item['type'], ['background', 'widget'], true)) {
                $owned = $this->db->fetchColumn('SELECT id FROM phpretro_homes_items WHERE user_id = ? AND catalogue_id = ? LIMIT 1', [$this->actor, $item['id']]);
                $this->need(!$owned, 'You already own this item.', 409);
            }
            $price = (int) $item['price'];
            $credits = (int) $user['credits'];
            $this->need($credits >= $price, "You don't have enough Coins, To get more please visit the Coin Pages.", 400);
            $amount = max(1, (int) $item['amount']);
            $itemType = $item['type'] === 'note' ? 'stickie' : $item['type'];
            $firstId = 0;
            for ($i = 0; $i < $amount; $i++) {
                $this->db->execute(
                    'INSERT INTO phpretro_homes_items (user_id, guild_id, catalogue_id, item_type, skin, data, x, y, z, placed) VALUES (?, 0, ?, ?, ?, ?, 0, 0, 0, 0)',
                    [$this->actor, $item['id'], $itemType, '', '']
                );
                if ($firstId === 0) { $firstId = (int) $this->db->insertId(); }
            }
            if ($price > 0) {
                $this->db->execute('UPDATE users SET credits = credits - ? WHERE id = ? AND credits >= ?', [$price, $this->actor, $price]);
                $balance = $credits - $price;
                $this->db->execute(
                    'INSERT INTO phpretro_transactions (user_id, type, amount, balance_after, description, reference_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [$this->actor, 'homes_store', -$price, $balance, 'MyHabbo store: '.$item['name'], (string) $item['id'], time()]
                );
            }
            $this->sync->recordAndNotify('homes.store_purchased', [
                'user_id' => $this->actor,
                'catalogue_id' => (int) $item['id'],
                'item_id' => $firstId,
                'amount' => $amount,
                'price' => $price,
            ]);
            return $firstId;
        });
    }

    public function inventory(string $type): array
    {
        $placement = $this->placement();
        if ($type === 'widget') {
            return $this->db->fetchAll(
                'SELECT id, name, description, type, data, price, amount, category, category_id, min_rank, placement FROM phpretro_homes_catalogue WHERE type = ? AND min_rank <= ? AND (placement = ? OR placement = ?) ORDER BY id DESC',
                ['widget', $this->rank(), $placement, 'anywhere']
            );
        }
        $sqlAddon = $type === 'background' ? '' : 'AND i.placed = 0 ';
        return $this->db->fetchAll(
            'SELECT MIN(i.id) AS id, i.catalogue_id, c.data, c.name, c.type, COUNT(i.id) AS quantity FROM phpretro_homes_items i JOIN phpretro_homes_catalogue c ON c.id = i.catalogue_id WHERE i.user_id = ? AND c.type = ? '.$sqlAddon.'AND (c.placement = ? OR c.placement = ?) GROUP BY i.catalogue_id, c.data, c.name, c.type ORDER BY id DESC',
            [$this->actor, $type, $placement, 'anywhere']
        );
    }

    public function inventoryItem(int $id): ?array
    {
        if ($id < 1) { return null; }
        return $this->db->fetchRow(
            'SELECT i.id, i.user_id, i.guild_id, i.catalogue_id, i.item_type, i.skin, i.data, i.x, i.y, i.z, i.placed, c.name, c.type, c.data AS catalogue_data, c.description FROM phpretro_homes_items i JOIN phpretro_homes_catalogue c ON c.id = i.catalogue_id WHERE i.id = ?',
            [$id]
        ) ?: null;
    }

    public function widgetPlaced(string $key): bool
    {
        $guildId = $this->editingGuild();
        if ($guildId > 0) {
            return (bool) $this->db->fetchColumn('SELECT id FROM phpretro_myhabbo_layouts WHERE guild_id = ? AND widget_key = ? LIMIT 1', [$guildId, $key]);
        }
        return (bool) $this->db->fetchColumn('SELECT id FROM phpretro_myhabbo_layouts WHERE user_id = ? AND guild_id = 0 AND widget_key = ? LIMIT 1', [$this->actor, $key]);
    }

    public function placedItems(int $userId = 0, int $guildId = 0): array
    {
        if ($guildId > 0) {
            return $this->db->fetchAll(
                'SELECT i.id, i.user_id, i.guild_id, i.catalogue_id, i.item_type, i.skin, i.data, i.x, i.y, i.z, c.data AS catalogue_data, c.type FROM phpretro_homes_items i JOIN phpretro_homes_catalogue c ON c.id = i.catalogue_id WHERE i.guild_id = ? AND i.placed = 1 ORDER BY i.z ASC, i.id ASC',
                [$guildId]
            );
        }
        return $this->db->fetchAll(
            'SELECT i.id, i.user_id, i.guild_id, i.catalogue_id, i.item_type, i.skin, i.data, i.x, i.y, i.z, c.data AS catalogue_data, c.type FROM phpretro_homes_items i JOIN phpretro_homes_catalogue c ON c.id = i.catalogue_id WHERE i.user_id = ? AND i.guild_id = 0 AND i.placed = 1 ORDER BY i.z ASC, i.id ASC',
            [$userId]
        );
    }

    public function backgroundClass(int $userId = 0, int $guildId = 0): string
    {
        $items = $this->placedItems($userId, $guildId);
        foreach ($items as $item) {
            if ($item['item_type'] === 'background') { return $this->itemCss('background', $item['catalogue_data']); }
        }
        return $guildId > 0 ? 'b_bg_colour_08' : 'b_bg_pattern_abstract2';
    }

    public function widgetStyle(array $widget): string
    {
        $left = ((int) ($widget['column_number'] ?? 1) === 2) ? 450 : 25;
        $top = max(0, (int) ($widget['position'] ?? 0)) * 50 + 10;
        $z = max(1, (int) ($widget['position'] ?? 0) + 1);
        return 'left: '.$left.'px; top: '.$top.'px; z-index: '.$z;
    }

    public function placeSticker(int $itemId, int $z, bool $all = false): array
    {
        $this->need($this->actor > 0, 'Sign in required.', 401);
        return $this->transaction(function () use ($itemId, $z, $all) {
            $first = $this->db->fetchRow('SELECT id, user_id, catalogue_id, item_type, placed FROM phpretro_homes_items WHERE id = ? FOR UPDATE', [$itemId]);
            $this->need((bool) $first && (int) $first['user_id'] === $this->actor && $first['item_type'] === 'sticker' && (int) $first['placed'] === 0, 'Sticker not found.', 404);
            $ids = [$itemId];
            if ($all) {
                $rest = $this->db->fetchAll('SELECT id FROM phpretro_homes_items WHERE user_id = ? AND catalogue_id = ? AND item_type = ? AND placed = 0 AND id <> ? ORDER BY id', [$this->actor, $first['catalogue_id'], 'sticker', $itemId]);
                foreach ($rest as $row) { $ids[] = (int) $row['id']; }
            }
            $guildId = $this->editingGuild();
            $placed = [];
            $left = 20;
            $top = 30;
            foreach ($ids as $index => $id) {
                $this->db->execute('UPDATE phpretro_homes_items SET placed = 1, guild_id = ?, x = ?, y = ?, z = ?, synced_at = NULL WHERE id = ? AND user_id = ?', [$guildId, $left, $top + ($index * 4), $z + $index, $id, $this->actor]);
                $placed[] = $this->inventoryItem($id);
            }
            $this->sync->recordAndNotify('homes.sticker_placed', ['item_ids' => $ids, 'user_id' => $this->actor, 'guild_id' => $guildId]);
            return $placed;
        });
    }

    public function removeSticker(int $itemId): void
    {
        $this->transaction(function () use ($itemId) {
            $item = $this->db->fetchRow('SELECT id, user_id, guild_id, item_type FROM phpretro_homes_items WHERE id = ? FOR UPDATE', [$itemId]);
            $this->need((bool) $item && $item['item_type'] === 'sticker', 'Sticker not found.', 404);
            $this->need((int) $item['user_id'] === $this->actor || ($item['guild_id'] > 0 && $this->canEditGroup((int) $item['guild_id'])), 'Not permitted.', 403);
            $this->db->execute('UPDATE phpretro_homes_items SET placed = 0, guild_id = 0, x = 0, y = 0, z = 0, synced_at = NULL WHERE id = ?', [$itemId]);
            $this->sync->recordAndNotify('homes.sticker_removed', ['item_id' => $itemId, 'user_id' => $this->actor]);
        });
    }

    public function skinName(int $skinId): string
    {
        $name = self::SKINS[$skinId] ?? 'defaultskin';
        if (in_array($skinId, [7, 8], true) && !$this->hasClub()) { return 'defaultskin'; }
        if ($skinId === 9 && $this->rank() <= 5) { return 'defaultskin'; }
        return $name;
    }

    public function placeNote(string $text, int $skinId): array
    {
        $this->need($this->actor > 0, 'Sign in required.', 401);
        $text = trim($text);
        $max = $this->rank() > 5 ? 20000 : 500;
        $this->need($text !== '' && mb_strlen($text, 'UTF-8') <= $max, 'Note must be between 1 and '.$max.' characters.', 400);
        $skin = $this->skinName($skinId);
        return $this->transaction(function () use ($text, $skin) {
            $item = $this->db->fetchRow(
                "SELECT i.id FROM phpretro_homes_items i JOIN phpretro_homes_catalogue c ON c.id = i.catalogue_id WHERE i.user_id = ? AND i.placed = 0 AND i.item_type = 'stickie' AND c.data = 'stickienote' ORDER BY i.id ASC LIMIT 1 FOR UPDATE",
                [$this->actor]
            );
            $this->need((bool) $item, 'You have no notes in inventory.', 404);
            $z = (int) $this->db->fetchColumn('SELECT COALESCE(MAX(z), 0) FROM phpretro_homes_items WHERE user_id = ? AND placed = 1', [$this->actor]) + 2;
            $guildId = $this->editingGuild();
            $this->db->execute('UPDATE phpretro_homes_items SET placed = 1, guild_id = ?, skin = ?, data = ?, x = 10, y = 10, z = ?, synced_at = NULL WHERE id = ?', [$guildId, $skin, $text, $z, $item['id']]);
            $this->sync->recordAndNotify('homes.note_placed', ['item_id' => (int) $item['id'], 'user_id' => $this->actor, 'guild_id' => $guildId]);
            return $this->inventoryItem((int) $item['id']);
        });
    }

    public function editNote(int $itemId, int $skinId): array
    {
        $skin = $this->skinName($skinId);
        return $this->transaction(function () use ($itemId, $skin) {
            $item = $this->db->fetchRow('SELECT id, user_id, guild_id, item_type FROM phpretro_homes_items WHERE id = ? FOR UPDATE', [$itemId]);
            $this->need((bool) $item && $item['item_type'] === 'stickie', 'Note not found.', 404);
            $this->need((int) $item['user_id'] === $this->actor || ($item['guild_id'] > 0 && $this->canEditGroup((int) $item['guild_id'])), 'Not permitted.', 403);
            $this->db->execute('UPDATE phpretro_homes_items SET skin = ?, synced_at = NULL WHERE id = ?', [$skin, $itemId]);
            $this->sync->recordAndNotify('homes.note_edited', ['item_id' => $itemId, 'user_id' => $this->actor, 'skin' => $skin]);
            return $this->inventoryItem($itemId);
        });
    }

    public function deleteNote(int $itemId): void
    {
        $this->transaction(function () use ($itemId) {
            $item = $this->db->fetchRow('SELECT id, user_id, guild_id, item_type FROM phpretro_homes_items WHERE id = ? FOR UPDATE', [$itemId]);
            $this->need((bool) $item && $item['item_type'] === 'stickie', 'Note not found.', 404);
            $this->need((int) $item['user_id'] === $this->actor || ($item['guild_id'] > 0 && $this->canEditGroup((int) $item['guild_id'])), 'Not permitted.', 403);
            $this->db->execute('UPDATE phpretro_homes_items SET placed = 0, guild_id = 0, skin = ?, data = ?, x = 0, y = 0, z = 0, synced_at = NULL WHERE id = ?', ['', '', $itemId]);
            $this->sync->recordAndNotify('homes.note_deleted', ['item_id' => $itemId, 'user_id' => $this->actor]);
        });
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

function phpretroHomesJsonHeader(mixed $payload): void
{
    header('X-JSON: '.json_encode($payload, JSON_UNESCAPED_UNICODE));
}

function phpretroHomesPadList(int $count): int
{
    if ($count < 20) { return 20 - $count; }
    $mod = $count % 4;
    return $mod === 0 ? 0 : 4 - $mod;
}
