<?php
require_once __DIR__.'/habblet.php';
require_once __DIR__.'/PhpretroGroupUrls.php';

/** Expected request failures; database failures deliberately remain errors. */
class HabbletGroupError extends RuntimeException {}

function habbletGroupRun(callable $action): void
{
    habbletRequireUser();
    try { $action(); }
    catch (HabbletGroupError $error) {
        if ($error->getCode() === 501) { habbletUnavailable($error->getMessage()); }
        else {
            http_response_code($error->getCode());
            echo '<p>'.htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8').'</p>';
        }
    }
}

function habbletGroupRender(string $template, array $values): void
{
    global $user, $input, $settings, $lang;
    extract($values, EXTR_SKIP);
    require __DIR__.'/habblet-templates/'.$template.'.php';
}

/** Native Polaris queries. Rank values are never inferred from legacy numeric ranks. */
class HabbletGroups
{
    public function __construct(public Database $db, public int $actor) {}

    public function need(bool $condition, string $message = 'Not permitted.', int $status = 403): void
    {
        if (!$condition) { throw new HabbletGroupError($message, $status); }
    }

    public function transaction(callable $action): mixed
    {
        $this->db->execute('START TRANSACTION');
        try { $result = $action(); $this->db->execute('COMMIT'); return $result; }
        catch (Throwable $error) { $this->db->execute('ROLLBACK'); throw $error; }
    }

    public function group(int $id, bool $lock = false): array
    {
        $this->need($id > 0, 'Invalid group.', 400);
        $sql = 'SELECT id, user_id, name, description, room_id, state, badge, date_created, forum, read_forum, post_messages, post_threads, mod_forum FROM guilds WHERE id = ?';
        $group = $this->db->fetchRow($sql.($lock ? ' FOR UPDATE' : ''), [$id]);
        $this->need((bool) $group, 'Group not found.', 404);
        return $group;
    }

    public function rank(int $group, ?int $uid = null): ?int
    {
        $rows = $this->db->fetchAll('SELECT level_id FROM guilds_members WHERE guild_id = ? AND user_id = ?', [$group, $uid ?? $this->actor]);
        // Ambiguous duplicate membership is not an authorization grant.
        return count($rows) === 1 ? (int) $rows[0]['level_id'] : null;
    }

    public function owner(array $group): bool { return (int) $group['user_id'] === $this->actor; }
    public function admin(array $group): bool { return $this->owner($group) || $this->rank((int) $group['id']) === 1; }
    public function allowed(array $group, string $permission): bool
    {
        $rank = $this->rank((int) $group['id']);
        return match ($group[$permission]) {
            'EVERYONE' => true,
            'MEMBERS' => $this->owner($group) || in_array($rank, [0, 1, 2], true),
            'ADMINS' => $this->admin($group),
            'OWNER' => $this->owner($group),
            default => false,
        };
    }

    public function forum(array $group): void
    {
        $this->need($group['forum'] === '1' && $this->allowed($group, 'read_forum'), 'Forum is unavailable to this account.');
    }

    public function thread(array $group, int $id, bool $lock = false): array
    {
        $this->forum($group);
        $thread = $this->db->fetchRow('SELECT id, guild_id, opener_id, subject, posts_count, created_at, updated_at, state, pinned, locked, admin_id FROM guilds_forums_threads WHERE id = ? AND guild_id = ?'.($lock ? ' FOR UPDATE' : ''), [$id, $group['id']]);
        $this->need((bool) $thread, 'Topic not found in this group.', 404);
        // Both 0 (new) and 1 (restored) are visible in Polaris. Hidden states never leak here.
        $this->need(in_array((int) $thread['state'], [0, 1], true), 'Topic is hidden.', 403);
        return $thread;
    }

    public function canReply(array $group, array $thread): bool
    {
        return !(int) $thread['locked'] && $this->allowed($group, 'post_messages');
    }

    public function captcha(): void
    {
        global $settings;
        $expected = $_SESSION['register-captcha-bubble'] ?? '';
        if ($settings->find('site_capcha') !== '0' && (!is_string($expected) || $expected === '' || !hash_equals(strtolower($expected), strtolower(habbletText($_POST, 'captcha'))))) {
            header('X-JSON: {"captchaError":"true"}');
            throw new HabbletGroupError('Incorrect security code.', 400);
        }
        unset($_SESSION['register-captcha-bubble']);
    }

    public function text(string $key, int $max): string
    {
        $text = trim(habbletText($_POST, $key));
        $this->need($text !== '' && mb_strlen($text, 'UTF-8') <= $max, 'Invalid '.$key.'.', 400);
        return $text;
    }

    public function author(int $id): array
    {
        return $this->db->fetchRow('SELECT u.id, u.username, u.look, u.motto, CASE WHEN s.hide_online = \'1\' THEN \'0\' ELSE u.online END AS online, u.mail_verified, COALESCE(s.forums_post_count, 0) AS posts, (SELECT b.badge_code FROM users_badges b WHERE b.user_id = u.id AND b.slot_id > 0 ORDER BY b.slot_id, b.id LIMIT 1) AS badge_code FROM users u LEFT JOIN users_settings s ON s.user_id = u.id WHERE u.id = ?', [$id]) ?: ['id' => $id, 'username' => 'Unknown user', 'look' => '', 'motto' => '', 'online' => '0', 'mail_verified' => '0', 'posts' => 0, 'badge_code' => null];
    }

    public function post(array $group, ?array $thread): int
    {
        $this->forum($group);
        $this->need($thread ? $this->canReply($group, $thread) : $this->allowed($group, 'post_threads'));
        $this->need($this->author($this->actor)['mail_verified'] === '1', 'Verify your email before posting.');
        $this->need((bool) $this->db->fetchColumn('SELECT user_id FROM users_settings WHERE user_id = ?', [$this->actor]), 'Account settings are missing.', 409);
        $message = $this->text('message', 4000);
        $subject = $thread ? '' : $this->text('topicName', 32);
        $this->captcha();
        $now = time();
        if (!$thread) {
            $this->db->execute('INSERT INTO guilds_forums_threads (guild_id, opener_id, subject, created_at, updated_at) VALUES (?, ?, ?, ?, ?)', [$group['id'], $this->actor, $subject, $now, $now]);
            $thread = ['id' => (int) $this->db->insertId()];
        }
        $this->db->execute('INSERT INTO guilds_forums_comments (thread_id, user_id, message, created_at) VALUES (?, ?, ?, ?)', [$thread['id'], $this->actor, $message, $now]);
        $this->db->execute('UPDATE guilds_forums_threads SET posts_count = posts_count + 1, updated_at = ? WHERE id = ? AND guild_id = ?', [$now, $thread['id'], $group['id']]);
        $this->db->execute('UPDATE users_settings SET forums_post_count = forums_post_count + 1 WHERE user_id = ?', [$this->actor]);
        return (int) $thread['id'];
    }

    public function editPost(array $group, array $thread, int $id): void
    {
        $post = $this->db->fetchRow('SELECT user_id, state FROM guilds_forums_comments WHERE id = ? AND thread_id = ? FOR UPDATE', [$id, $thread['id']]);
        $this->need((bool) $post, 'Post not found in this topic.', 404);
        $this->need($this->canReply($group, $thread) && in_array((int) $post['state'], [0, 1], true));
        $this->need((int) $post['user_id'] === $this->actor || $this->allowed($group, 'mod_forum'));
        $message = $this->text('message', 4000);
        $this->captcha();
        $this->db->execute('UPDATE guilds_forums_comments SET message = ? WHERE id = ? AND thread_id = ?', [$message, $id, $thread['id']]);
        // Polaris has no comment edit timestamp/history column.
    }

    public function topicSettings(array $group, array $thread): void
    {
        $moderator = $this->allowed($group, 'mod_forum');
        $this->need($moderator || (int) $thread['opener_id'] === $this->actor);
        $subject = $this->text('topicName', 32);
        $closed = habbletInt($_POST, 'topicClosed', -1);
        $sticky = habbletInt($_POST, 'topicSticky', -1);
        $this->need(in_array($closed, [0, 1], true) && in_array($sticky, [0, 1], true), 'Invalid topic settings.', 400);
        if (!$moderator) { $closed = (int) $thread['locked']; $sticky = (int) $thread['pinned']; }
        $this->db->execute('UPDATE guilds_forums_threads SET subject = ?, locked = ?, pinned = ? WHERE id = ? AND guild_id = ?', [$subject, $closed, $sticky, $thread['id'], $group['id']]);
    }

    public function deleteTopic(array $group, array $thread): void
    {
        $this->need($this->allowed($group, 'mod_forum'));
        // GuildForumModerateThreadEvent.deleteThread: permanent deletion, not a guessed state.
        $this->db->execute('DELETE FROM guilds_forums_comments WHERE thread_id = ?', [$thread['id']]);
        $this->db->execute('DELETE FROM guilds_forums_threads WHERE id = ? AND guild_id = ?', [$thread['id'], $group['id']]);
    }

    public function join(array $group): bool
    {
        $this->need(!in_array((int) $group['state'], [2, 4], true), 'This group is closed.');
        $this->need(!$this->db->fetchColumn('SELECT id FROM guilds_members WHERE guild_id = ? AND user_id = ?', [$group['id'], $this->actor]), 'Membership or request already exists.', 409);
        // Lock the account as well as the guild, serializing CMS joins to different guilds.
        $this->db->fetchRow('SELECT id FROM users WHERE id = ? FOR UPDATE', [$this->actor]);
        $this->need((int) $this->db->fetchColumn('SELECT COUNT(*) FROM guilds_members WHERE user_id = ?', [$this->actor]) < 100, 'Group membership limit reached.', 409);
        $this->need((int) $this->db->fetchColumn('SELECT COUNT(*) FROM guilds_members WHERE guild_id = ? AND level_id IN (0, 1, 2)', [$group['id']]) < 50000, 'Group is full.', 409);
        $pending = (int) $group['state'] === 1;
        if ($pending) {
            $this->need((int) $this->db->fetchColumn('SELECT COUNT(*) FROM guilds_members WHERE guild_id = ? AND level_id = 3', [$group['id']]) < 100, 'Group request limit reached.', 409);
        }
        $this->db->execute('INSERT INTO guilds_members (guild_id, user_id, level_id, member_since) VALUES (?, ?, ?, ?)', [$group['id'], $this->actor, $pending ? 3 : 2, time()]);
        return $pending;
    }

    public function targets(): array
    {
        $raw = explode(',', habbletText($_POST, 'targetIds'));
        $this->need(count($raw) <= 100, 'Too many targets.', 400);
        $targets = [];
        foreach ($raw as $value) {
            $id = habbletInt(['id' => trim($value)], 'id');
            $this->need($id > 0, 'Invalid member.', 400); $targets[$id] = $id;
        }
        return array_values($targets);
    }

    public function memberAction(array $group, string $action, array $targets): void
    {
        $this->need(in_array($action, ['give_rights', 'revoke_rights'], true) ? $this->owner($group) : $this->admin($group));
        foreach ($targets as $target) {
            $rank = $this->rank((int) $group['id'], $target);
            $this->need($target !== (int) $group['user_id'] && $rank !== 0, 'The owner cannot be changed here.');
            $expected = match ($action) { 'accept', 'decline' => [3], 'give_rights' => [2], 'revoke_rights' => [1], 'remove' => $this->owner($group) ? [1, 2] : [2] };
            $this->need(in_array($rank, $expected, true), 'Member state does not match this action.', 409);
            if ($action === 'accept') {
                $this->db->fetchRow('SELECT id FROM users WHERE id = ? FOR UPDATE', [$target]);
                $this->need((int) $this->db->fetchColumn('SELECT COUNT(*) FROM guilds_members WHERE user_id = ?', [$target]) < 100, 'Member group limit reached.', 409);
                $this->need((int) $this->db->fetchColumn('SELECT COUNT(*) FROM guilds_members WHERE guild_id = ? AND level_id IN (0, 1, 2)', [$group['id']]) < 50000, 'Group is full.', 409);
                $this->db->execute('UPDATE guilds_members SET level_id = 2, member_since = ? WHERE guild_id = ? AND user_id = ? AND level_id = 3', [time(), $group['id'], $target]);
            } elseif (in_array($action, ['remove', 'decline'], true)) {
                $this->removeMember($group, $target);
            } else {
                $this->db->execute('UPDATE guilds_members SET level_id = ? WHERE guild_id = ? AND user_id = ? AND level_id = ?', [$action === 'give_rights' ? 1 : 2, $group['id'], $target, $rank]);
            }
        }
    }

    public function removeMember(array $group, int $target): void
    {
        $this->need($target !== (int) $group['user_id'] && in_array($this->rank((int) $group['id'], $target), [1, 2, 3], true));
        $this->db->execute('DELETE FROM guilds_members WHERE guild_id = ? AND user_id = ? AND level_id IN (1, 2, 3)', [$group['id'], $target]);
        $this->db->execute('UPDATE users_settings SET guild_id = 0 WHERE user_id = ? AND guild_id = ?', [$target, $group['id']]);
    }

    public function favorite(array $group, bool $select): void
    {
        $this->need(habbletInt($_POST, 'targetAccountId') === $this->actor);
        $this->need((bool) $this->db->fetchColumn('SELECT user_id FROM users_settings WHERE user_id = ?', [$this->actor]), 'Account settings are missing.', 409);
        if ($select) {
            $this->need($this->owner($group) || in_array($this->rank((int) $group['id']), [0, 1, 2], true));
            $this->db->execute('UPDATE users_settings SET guild_id = ? WHERE user_id = ?', [$group['id'], $this->actor]);
        } else { $this->db->execute('UPDATE users_settings SET guild_id = 0 WHERE user_id = ? AND guild_id = ?', [$this->actor, $group['id']]); }
    }

    public function settings(array $group): void
    {
        $this->need($this->owner($group));
        $room = habbletInt($_POST, 'roomId');
        $this->need($room === (int) $group['room_id'], 'Move group rooms in the game client; this form cannot perform Polaris room-rights updates.', 501);
        $name = $this->text('name', 30);
        $description = habbletText($_POST, 'description');
        $this->need(mb_strlen($description, 'UTF-8') <= 250, 'Description is too long.', 400);
        // guilds uses latin1 in CleanDB. Reject unrepresentable text before any write.
        $this->need(mb_convert_encoding(mb_convert_encoding($name.$description, 'Windows-1252', 'UTF-8'), 'UTF-8', 'Windows-1252') === $name.$description, 'Group text contains characters unsupported by the Polaris guilds charset.', 400);
        $state = habbletInt($_POST, 'type', -1);
        $read = habbletInt($_POST, 'forumType', -1);
        $post = habbletInt($_POST, 'newTopicPermission', -1);
        $this->need(in_array($state, [0, 1, 2, 3], true) && in_array($read, [0, 1], true) && in_array($post, [0, 1, 2], true), 'Invalid group settings.', 400);
        $this->need($group['read_forum'] !== 'ADMINS' && $group['post_threads'] !== 'OWNER' && (int) $group['state'] !== 4, 'These Polaris settings cannot be represented by the legacy form.', 501);
        $url = habbletText($_POST, 'url');
        $urls = new PhpretroGroupUrls($this->db, new PhpretroLiveSync($this->db));
        if ($url !== '' && $urls->forGuild((int) $group['id']) === '') {
            $this->need($urls->valid($url) && !$urls->taken($url), 'This url name contains invalid characters or is already taken. It will not be saved.', 400);
        }
        $this->db->execute('UPDATE guilds SET name = ?, description = ?, state = ?, read_forum = ?, post_threads = ? WHERE id = ? AND user_id = ?', [$name, $description, $state, ['EVERYONE', 'MEMBERS'][$read], ['EVERYONE', 'MEMBERS', 'ADMINS'][$post], $group['id'], $this->actor]);
        if ($url !== '') {
            try { $urls->claim((int) $group['id'], $this->actor, $url); }
            catch (InvalidArgumentException $error) { throw new HabbletGroupError($error->getMessage(), 400); }
        }
    }

    public function deleteGroup(array $group): void
    {
        $this->need($this->owner($group));
        // Exact persisted cleanup in Polaris GuildManager.deleteGuild (see phase report).
        $id = $group['id'];
        $this->db->execute('UPDATE users_settings SET guild_id = 0 WHERE guild_id = ?', [$id]);
        $this->db->execute('DELETE FROM guild_forum_views WHERE guild_id = ?', [$id]);
        $this->db->execute('DELETE c FROM guilds_forums_comments c INNER JOIN guilds_forums_threads t ON c.thread_id = t.id WHERE t.guild_id = ?', [$id]);
        $this->db->execute('DELETE FROM guilds_forums_threads WHERE guild_id = ?', [$id]);
        $this->db->execute('DELETE FROM guilds_members WHERE guild_id = ?', [$id]);
        $this->db->execute('UPDATE rooms SET guild_id = 0 WHERE guild_id = ?', [$id]);
        $this->db->execute('UPDATE items SET guild_id = 0 WHERE guild_id = ?', [$id]);
        $this->db->execute('DELETE FROM guilds WHERE id = ? AND user_id = ?', [$id, $this->actor]);
    }
}
