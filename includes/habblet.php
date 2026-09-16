<?php
// Bootstrap shared by the Phase 6 handlers. Also works when included by a page.
if (!defined('IN_HOLOCMS')) {
    $page = array_merge(['dir' => '\\habblet', 'allow_guests' => true], $page ?? []);
    chdir(__DIR__.'/..');
    require_once(__DIR__.'/core.php');
}

if (!class_exists('Csrf')) {
    require_once __DIR__.'/Csrf.php';
}
Csrf::protectPost();

function habbletText(array $values, string $key, string $default = ''): string {
    return isset($values[$key]) && is_string($values[$key]) ? $values[$key] : $default;
}

function habbletInt(array $values, string $key, int $default = 0): int {
    $value = $values[$key] ?? null;
    if (!is_int($value) && !is_string($value)) { return $default; }
    $parsed = filter_var($value, FILTER_VALIDATE_INT);
    return $parsed === false ? $default : $parsed;
}

function habbletRequireUser(): void {
    global $user, $page, $settings, $serverdb, $core;
    Csrf::protectPost();
    $page['allow_guests'] = false;
    $_SESSION['page'] = $_SESSION['page'] ?? ($_SERVER['REQUEST_URI'] ?? '/');
    require(__DIR__.'/session.php');
    if (empty($user->logged_in) || (int) $user->id < 1 || $user->error > 0) {
        http_response_code(403);
        exit('Please sign in again.');
    }
}

function habbletUnavailable(string $message): void {
    http_response_code(501);
    header('X-PHPRetro-Feature: unavailable');
    echo '<p class="habblet-unavailable">'.htmlspecialchars($message, ENT_QUOTES, 'UTF-8').'</p>';
}

// The schema has no email_friendrequest preference. Do not send unsolicited mail.
function habbletRequestFriend(Database $database, int $from, int $to): string {
    if ($to < 1 || $from === $to) { return 'habbo.error.3'; }
    $database->execute('START TRANSACTION');
    try {
        // Serialize requests for this pair; lock in a stable order.
        $users = $database->fetchAll('SELECT id FROM users WHERE id IN (?, ?) ORDER BY id FOR UPDATE', [$from, $to]);
        if (count($users) !== 2) {
            $result = 'habbo.error.4';
        } elseif ($database->fetchColumn('SELECT id FROM messenger_friendships WHERE (user_one_id = ? AND user_two_id = ?) OR (user_one_id = ? AND user_two_id = ?) LIMIT 1', [$from, $to, $to, $from])) {
            $result = 'habbo.error.1';
        } elseif ($database->fetchColumn('SELECT id FROM messenger_friendrequests WHERE (user_from_id = ? AND user_to_id = ?) OR (user_from_id = ? AND user_to_id = ?) LIMIT 1', [$from, $to, $to, $from])) {
            $result = 'habbo.error.2';
        } elseif ($database->fetchColumn('SELECT block_friendrequests FROM users_settings WHERE user_id = ?', [$to]) === '1') {
            $result = 'Friend requests are disabled for this account.';
        } else {
            $database->execute('INSERT INTO messenger_friendrequests (user_from_id, user_to_id) VALUES (?, ?)', [$from, $to]);
            $result = 'habbo.success';
        }
        $database->execute('COMMIT');
        return $result;
    } catch (Throwable $exception) {
        $database->execute('ROLLBACK');
        throw $exception;
    }
}


function habbletUserTags(Database $database, int $userId): array {
    $tags = $database->fetchColumn('SELECT tags FROM users_settings WHERE user_id = ?', [$userId]);
    return array_values(array_unique(array_filter(explode(';', (string) $tags), static fn(string $tag): bool => $tag !== '')));
}

function habbletTagCount(Database $database, string $tag): int {
    if ($tag === '' || str_contains($tag, ';')) { return 0; }
    return (int) $database->fetchColumn("SELECT COUNT(*) FROM users_settings s JOIN users u ON u.id = s.user_id WHERE LOCATE(CONCAT(';', ?, ';'), CONCAT(';', s.tags, ';')) > 0", [$tag]);
}

function habbletValidUserTag(string $tag): bool {
    global $input;
    if ($tag === '' || strlen($tag) > 20 || str_contains($tag, ';')) { return false; }
    return strnatcasecmp($tag, $input->stringToURL($input->HoloText($tag))) === 0;
}

function habbletAddUserTag(Database $database, int $userId, string $tag): string {
    $tag = trim($tag);
    if (!habbletValidUserTag($tag)) { return 'invalidtag'; }
    $tag = strtolower($tag);
    $database->execute('START TRANSACTION');
    try {
        $row = $database->fetchRow('SELECT tags FROM users_settings WHERE user_id = ? FOR UPDATE', [$userId]);
        if (!$row) {
            $database->execute('ROLLBACK');
            return 'invalidtag';
        }
        $tags = array_values(array_filter(explode(';', (string) $row['tags']), static fn(string $existing): bool => $existing !== ''));
        foreach ($tags as $existing) {
            if (strcasecmp($existing, $tag) === 0) {
                $database->execute('COMMIT');
                return 'invalidtag';
            }
        }
        if (count($tags) > 19) {
            $database->execute('COMMIT');
            return 'invalidtag';
        }
        $tags[] = $tag;
        $packed = implode(';', $tags);
        if (strlen($packed) > 255) {
            $database->execute('ROLLBACK');
            return 'invalidtag';
        }
        $database->execute('UPDATE users_settings SET tags = ? WHERE user_id = ? LIMIT 1', [$packed, $userId]);
        $database->execute('COMMIT');
        return 'valid';
    } catch (Throwable $exception) {
        $database->execute('ROLLBACK');
        throw $exception;
    }
}

function habbletRemoveUserTag(Database $database, int $userId, string $tag): void {
    $tag = trim($tag);
    if ($tag === '' || str_contains($tag, ';')) { return; }
    $database->execute('START TRANSACTION');
    try {
        $row = $database->fetchRow('SELECT tags FROM users_settings WHERE user_id = ? FOR UPDATE', [$userId]);
        if (!$row) {
            $database->execute('ROLLBACK');
            return;
        }
        $tags = [];
        $removed = false;
        foreach (explode(';', (string) $row['tags']) as $existing) {
            if ($existing === '') { continue; }
            if (strcasecmp($existing, $tag) === 0) { $removed = true; continue; }
            $tags[] = $existing;
        }
        if ($removed) {
            $database->execute('UPDATE users_settings SET tags = ? WHERE user_id = ? LIMIT 1', [implode(';', $tags), $userId]);
        }
        $database->execute('COMMIT');
    } catch (Throwable $exception) {
        $database->execute('ROLLBACK');
        throw $exception;
    }
}

function habbletCanEditGuildTags(Database $database, int $guildId, int $userId): bool {
    if ($guildId < 1 || $userId < 1) { return false; }
    $owner = (int) $database->fetchColumn('SELECT user_id FROM guilds WHERE id = ?', [$guildId]);
    if ($owner === $userId) { return true; }
    $level = $database->fetchColumn('SELECT level_id FROM guilds_members WHERE guild_id = ? AND user_id = ?', [$guildId, $userId]);
    return $level !== false && (int) $level === 1;
}

function habbletGuildTags(Database $database, int $guildId): array {
    if ($guildId < 1) { return []; }
    return array_column($database->fetchAll('SELECT tag FROM phpretro_guild_tags WHERE guild_id = ? ORDER BY tag ASC, id ASC', [$guildId]), 'tag');
}

function habbletGuildTagCount(Database $database, string $tag): int {
    $tag = strtolower(trim($tag));
    if ($tag === '' || str_contains($tag, ';')) { return 0; }
    return (int) $database->fetchColumn('SELECT COUNT(*) FROM phpretro_guild_tags WHERE tag = ?', [$tag]);
}

function habbletGuildsForTag(Database $database, string $tag, int $limit = 20): array {
    $tag = strtolower(trim($tag));
    if ($tag === '' || str_contains($tag, ';')) { return []; }
    $limit = max(1, min(50, $limit));
    return $database->fetchAll(
        'SELECT g.id, g.name, g.description, g.badge FROM phpretro_guild_tags t JOIN guilds g ON g.id = t.guild_id WHERE t.tag = ? ORDER BY g.id DESC LIMIT ?',
        [$tag, $limit]
    );
}

function habbletAddGuildTag(Database $database, int $guildId, int $userId, string $tag): string {
    $tag = trim($tag);
    if ($guildId < 1 || $userId < 1 || !habbletValidUserTag($tag)) { return 'invalidtag'; }
    $tag = strtolower($tag);
    $database->execute('START TRANSACTION');
    try {
        $guild = $database->fetchRow('SELECT id, user_id FROM guilds WHERE id = ? FOR UPDATE', [$guildId]);
        if (!$guild) {
            $database->execute('ROLLBACK');
            return 'invalidtag';
        }
        $level = $database->fetchColumn('SELECT level_id FROM guilds_members WHERE guild_id = ? AND user_id = ?', [$guildId, $userId]);
        $canEdit = (int) $guild['user_id'] === $userId || ($level !== false && (int) $level === 1);
        if (!$canEdit) {
            $database->execute('ROLLBACK');
            return 'invalidtag';
        }
        $count = (int) $database->fetchColumn('SELECT COUNT(*) FROM phpretro_guild_tags WHERE guild_id = ?', [$guildId]);
        if ($count >= 20) {
            $database->execute('COMMIT');
            return 'taglimit';
        }
        $exists = $database->fetchColumn('SELECT id FROM phpretro_guild_tags WHERE guild_id = ? AND tag = ?', [$guildId, $tag]);
        if ($exists) {
            $database->execute('COMMIT');
            return 'invalidtag';
        }
        $database->execute(
            'INSERT INTO phpretro_guild_tags (guild_id, tag, created_by_user_id, created_at) VALUES (?, ?, ?, ?)',
            [$guildId, $tag, $userId, time()]
        );
        require_once __DIR__.'/PhpretroLiveSync.php';
        (new PhpretroLiveSync($database))->recordAndNotify('guild.tag_added', [
            'guild_id' => $guildId,
            'tag' => $tag,
            'user_id' => $userId,
        ]);
        $database->execute('COMMIT');
        return 'valid';
    } catch (PDOException $exception) {
        $database->execute('ROLLBACK');
        if ((string) $exception->getCode() === '23000') { return 'invalidtag'; }
        throw $exception;
    } catch (Throwable $exception) {
        $database->execute('ROLLBACK');
        throw $exception;
    }
}

function habbletRemoveGuildTag(Database $database, int $guildId, int $userId, string $tag): void {
    $tag = strtolower(trim($tag));
    if ($guildId < 1 || $userId < 1 || $tag === '' || str_contains($tag, ';')) { return; }
    $database->execute('START TRANSACTION');
    try {
        $guild = $database->fetchRow('SELECT id, user_id FROM guilds WHERE id = ? FOR UPDATE', [$guildId]);
        if (!$guild) {
            $database->execute('ROLLBACK');
            return;
        }
        $level = $database->fetchColumn('SELECT level_id FROM guilds_members WHERE guild_id = ? AND user_id = ?', [$guildId, $userId]);
        $canEdit = (int) $guild['user_id'] === $userId || ($level !== false && (int) $level === 1);
        if (!$canEdit) {
            $database->execute('ROLLBACK');
            return;
        }
        $deleted = $database->execute('DELETE FROM phpretro_guild_tags WHERE guild_id = ? AND tag = ?', [$guildId, $tag]);
        if ($deleted > 0) {
            require_once __DIR__.'/PhpretroLiveSync.php';
            (new PhpretroLiveSync($database))->recordAndNotify('guild.tag_removed', [
                'guild_id' => $guildId,
                'tag' => $tag,
                'user_id' => $userId,
            ]);
        }
        $database->execute('COMMIT');
    } catch (Throwable $exception) {
        $database->execute('ROLLBACK');
        throw $exception;
    }
}

function habbletRenderGuildTags(Database $database, int $guildId, bool $canEdit): void {
    global $input, $lang, $user;
    $tags = habbletGuildTags($database, $guildId);
    $loggedIn = !empty($user->logged_in) && (int) $user->id > 0;
    if ($tags === []) {
        echo $lang->loc['no.tags'] ?? 'No tags.';
        return;
    }
    foreach ($tags as $tag) {
        echo '<span class="tag-search-rowholder">';
        echo '<a href="'.PATH.'/tag/'.rawurlencode($tag).'" class="tag">'.$input->HoloText($tag).'</a>';
        if ($canEdit) {
            echo '<img border="0" class="tag-delete-link" onMouseOver="this.src=\''.PATH.'/web-gallery/images/buttons/tags/tag_button_delete_hi.gif\'" onMouseOut="this.src=\''.PATH.'/web-gallery/images/buttons/tags/tag_button_delete.gif\'" src="'.PATH.'/web-gallery/images/buttons/tags/tag_button_delete.gif" />';
        } elseif ($loggedIn) {
            echo '<img border="0" class="tag-add-link" onMouseOver="this.src=\''.PATH.'/web-gallery/images/buttons/tags/tag_button_add_hi.gif\'" onMouseOut="this.src=\''.PATH.'/web-gallery/images/buttons/tags/tag_button_add.gif\'" src="'.PATH.'/web-gallery/images/buttons/tags/tag_button_add.gif" />';
        }
        echo '</span>';
    }
    echo '<img id="tag-img-added" border="0" class="tag-none-link" src="'.PATH.'/web-gallery/images/buttons/tags/tag_button_added.gif" style="display:none"/>';
}

