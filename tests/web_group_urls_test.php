<?php
/** Run with DB_DSN/DB_USER/DB_PASS for a MariaDB account allowed to create a scratch DB. */
$root = dirname(__DIR__);
require_once $root.'/includes/config.php';
$sourceDsn = getenv('DB_DSN') ?: '';
if (!str_starts_with($sourceDsn, 'mysql:')) { throw new RuntimeException('Set DB_DSN to a MySQL DSN.'); }
$testName = 'phpretro_web_group_urls_test_'.bin2hex(random_bytes(6));
$serverDsn = preg_replace('/dbname=[^;]+;?/', '', $sourceDsn);
$admin = new PDO($serverDsn, getenv('DB_USER'), getenv('DB_PASS'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$admin->exec('CREATE DATABASE `'.$testName.'` CHARACTER SET utf8mb4');
$assertions = 0;
try {
    putenv('DB_DSN='.$serverDsn.';dbname='.$testName);
    define('IN_HOLOCMS', true);
    define('PATH', '/PHPRetro-PDO');
    define('SHORTNAME', 'PHPRetro');
    define('FULLNAME', 'PHPRetro');
    require_once $root.'/includes/classes.php';
    require_once $root.'/includes/habblet.php';
    require_once $root.'/includes/PhpretroGroupUrls.php';
    $db = new Database();
    $serverdb = $db;
    $schema = file_get_contents($root.'/references/schema/CleanDB.sql');
    foreach (['users', 'users_settings', 'guilds', 'guilds_members', 'rooms'] as $table) {
        if (!preg_match('/CREATE TABLE IF NOT EXISTS `'.preg_quote($table, '/').'` \(.*?\) ENGINE=.*?;/s', $schema, $match)) {
            throw new RuntimeException('Missing verified table '.$table);
        }
        $db->execute($match[0]);
    }
    foreach (['003_web_minimail.sql', '005_web_group_urls.sql'] as $file) {
        $migration = preg_replace('/^\s*--.*$/m', '', file_get_contents($root.'/migrations/'.$file)) ?? '';
        foreach (array_filter(array_map('trim', explode(';', $migration))) as $sql) {
            if ($sql !== '') { $db->execute($sql); }
        }
    }
    foreach (range(1, 3) as $id) {
        $name = $id === 2 ? 'Bob<script>' : 'User'.$id;
        $db->execute('INSERT INTO users (id, username, password, account_created, ip_register, ip_current) VALUES (?, ?, ?, ?, ?, ?)', [$id, $name, '', 100, '127.0.0.1', '127.0.0.1']);
        $db->execute('INSERT INTO users_settings (user_id) VALUES (?)', [$id]);
    }
    $db->execute('INSERT INTO guilds (id, user_id, name, date_created, forum, read_forum, post_messages, post_threads) VALUES (1, 1, ?, ?, ?, ?, ?, ?), (2, 2, ?, ?, ?, ?, ?, ?)', ['Crew', 100, '1', 'MEMBERS', 'MEMBERS', 'MEMBERS', 'Other', 100, '1', 'MEMBERS', 'MEMBERS', 'MEMBERS']);
    $db->execute('INSERT INTO guilds_members (guild_id, user_id, level_id) VALUES (1, 1, 0), (2, 2, 0)');
    chdir($root);
    $input = new HoloInput();
    $settings = new HoloSettings();
    $settings->cache['site_language'] = 'en';
    $settings->cache['site_allow_guests'] = '1';
    $settings->cache['site_session_time'] = '20';
    $settings->cache['site_c_images_path'] = '/images/';
    $settings->cache['site_badges_path'] = 'badges/';
    $lang = new HoloLocale();
    $user = new class {
        public int $id = 1;
        public bool $logged_in = true;
        public int $error = 0;
        public string $ip = '127.0.0.1';
        public string $name = 'User1';
        public int $time;
        public array $user = [];
        public function __construct() { $this->time = time(); }
        public function avatarURL($figure, $style) { return '/fixture-avatar.png'; }
    };
    $_SESSION = ['user' => $user, 'page' => '/test'];
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['REQUEST_URI'] = '/test';
    $page = ['allow_guests' => false];
    set_error_handler(static function ($severity, $message, $file, $line) {
        if (error_reporting() & $severity) { throw new ErrorException($message, 0, $severity, $file, $line); }
        return false;
    });
    function check(bool $condition, string $message): void {
        global $assertions;
        if (!$condition) { throw new RuntimeException($message); }
        $assertions++;
    }
    function endpoint(string $name, array $post = [], array $get = []): array {
        global $root, $db, $serverdb, $user, $page, $settings, $input, $lang;
        parse_str(http_build_query($post), $_POST);
        $_GET = $get; $page = ['allow_guests' => false];
        $lang = new HoloLocale();
        http_response_code(200);
        ob_start();
        try { include $root.'/habblet/'.$name; return [ob_get_contents(), http_response_code()]; }
        finally { ob_end_clean(); }
    }
    function asUser(int $id): void { global $user; $user->id = $id; $user->logged_in = true; $user->error = 0; }
    function callAction(string $action, array $post = []): array {
        return endpoint('groups_actions_'.$action.'.php', ['groupId' => 1, ...$post]);
    }

    if (!preg_match('/CREATE TABLE IF NOT EXISTS `guilds` \(.*?\) ENGINE=.*?;/s', $schema, $guildMatch)) {
        throw new RuntimeException('Missing guilds table');
    }
    check(!preg_match('/`(?:alias|name_seo)`/', $guildMatch[0]), 'PolarIS guilds have no alias column');

    $form = callAction('group_settings');
    check($form[1] === 200 && str_contains($form[0], 'id="group_url"'), 'Settings form has URL field');
    check(!str_contains($form[0], 'disabled="disabled" name="group_url"'), 'Unset alias input is enabled');
    check(str_contains($form[0], 'Room moves are unavailable here'), 'Room banner kept');
    check(!str_contains($form[0], 'Custom URLs and room moves'), 'URL unavailability banner removed');

    $check = callAction('check_group_url', ['url' => 'cool-name']);
    check($check[1] === 200 && str_contains($check[0], 'cool-name') && !str_starts_with($check[0], 'ERROR '), 'Valid URL confirmation');
    check(str_contains($check[0], 'You can not alter it later on.'), 'Cannot-alter warning shown');

    foreach (['actions', 'id', 'discussions', 'home', '123', 'cool_name', 'cool name', str_repeat('a', 31), "' OR 1=1 --", ''] as $bad) {
        $result = callAction('check_group_url', ['url' => $bad]);
        check($result[1] === 200 && str_starts_with($result[0], 'ERROR '), 'Rejected URL: '.$bad);
    }

    asUser(2);
    check(callAction('check_group_url', ['url' => 'cool-name'])[1] === 403, 'Non-owner cannot check URL');
    asUser(1);

    $save = ['name' => 'Crew', 'description' => 'Desc', 'type' => 0, 'forumType' => 1, 'newTopicPermission' => 1, 'roomId' => 0];
    check(callAction('update_group_settings', [...$save, 'url' => 'cool_name'])[1] === 400, 'Invalid alias is not saved');
    check($db->fetchColumn('SELECT alias FROM phpretro_group_url_aliases WHERE guild_id = 1') === false, 'Failed claim writes no row');
    check($db->fetchColumn('SELECT name FROM guilds WHERE id = 1') === 'Crew', 'Invalid alias rolls back name too');

    $ok = callAction('update_group_settings', [...$save, 'url' => 'Cool-Name']);
    check($ok[1] === 200, 'Valid alias saved with settings');
    check($db->fetchColumn('SELECT alias FROM phpretro_group_url_aliases WHERE guild_id = 1') === 'Cool-Name', 'Alias stored exactly');
    check($db->fetchColumn('SELECT synced_at FROM phpretro_group_url_aliases WHERE guild_id = 1') === null, 'synced_at stays null');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_emulator_outbox WHERE event_type = ? AND status = ?', ['groups.alias_claimed', 'pending']) === 1, 'Claim records pending outbox');
    check(habbletGroupURL(1) === PATH.'/groups/Cool-Name', 'habbletGroupURL uses alias');
    check(phpretroGroupPath(2) === PATH.'/groups/2/id', 'Unclaimed guild stays numeric');
    check(phpretroGroupUrls()->resolve('Cool-Name') === 1, 'resolve finds guild');
    check(phpretroRequestGuildId(['alias' => 'Cool-Name']) === 1, 'Request helper resolves alias');
    check(phpretroRequestGuildId(['id' => '1']) === 1, 'Request helper prefers numeric id');
    check(phpretroRequestGuildId(['alias' => 'missing']) === 0, 'Unknown alias is zero');

    $locked = callAction('group_settings');
    check(str_contains($locked[0], '/groups/Cool-Name') && str_contains($locked[0], 'name="group_url_edited" id="group_url_edited" value="0"'), 'Claimed alias is read-only');

    check(callAction('update_group_settings', [...$save, 'url' => 'other-name'])[1] === 200, 'Re-save with a different URL is ignored');
    check($db->fetchColumn('SELECT alias FROM phpretro_group_url_aliases WHERE guild_id = 1') === 'Cool-Name', 'Cannot alter claimed alias');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_group_url_aliases') === 1, 'No second alias row');

    asUser(2);
    $taken = endpoint('groups_actions_check_group_url.php', ['groupId' => 2, 'url' => 'Cool-Name']);
    check($taken[1] === 200 && str_starts_with($taken[0], 'ERROR '), 'Taken alias is ERROR for the other owner');
    check(endpoint('groups_actions_update_group_settings.php', ['groupId' => 2, 'url' => 'Cool-Name', ...$save, 'name' => 'Other'])[1] === 400, 'Taken alias is not claimed');
    check(endpoint('groups_actions_update_group_settings.php', ['groupId' => 2, 'url' => 'other-crew', ...$save, 'name' => 'Other'])[1] === 200, 'Other guild can claim a free alias');
    check(phpretroGroupUrls()->resolve('other-crew') === 2, 'Second guild alias resolves');
    asUser(1);

    $badge = callAction('show_badge_editor');
    check($badge[1] === 200 && str_contains($badge[0], 'habblet-client-handoff'), 'Badge editor is client-handoff');
    check(!str_contains($badge[0], 'BadgeEditor.swf'), 'Badge editor does not embed Flash');
    check(callAction('update_group_settings', [...$save, 'url' => 'Cool-Name', 'roomId' => 999])[1] === 501, 'Room transfer stays unavailable');

    check(str_contains(file_get_contents($root.'/groups.php'), 'phpretroRequestGuildId()'), 'groups.php resolves aliases');
    check(str_contains(file_get_contents($root.'/discussions.php'), 'phpretroRequestGuildId()'), 'discussions.php resolves aliases');
    check(str_contains(file_get_contents($root.'/.htaccess'), 'groups.php?alias='), '.htaccess already routes /groups/{alias}');

    echo "PASS: $assertions assertions; website group URLs on disposable MariaDB.\n";
} finally {
    restore_error_handler();
    $admin->exec('DROP DATABASE `'.$testName.'`');
    putenv('DB_DSN='.$sourceDsn);
}
