<?php
/** Group tags on phpretro_guild_tags. Never writes users_settings.tags.
 * Run with DB_DSN/DB_USER/DB_PASS for a MariaDB account allowed to create a scratch DB.
 */
$root = dirname(__DIR__);
require_once $root.'/includes/config.php';
$sourceDsn = getenv('DB_DSN') ?: '';
if (!str_starts_with($sourceDsn, 'mysql:')) { throw new RuntimeException('Set DB_DSN to a MySQL DSN.'); }
$testName = 'phpretro_guild_tags_'.bin2hex(random_bytes(6));
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
    require_once $root.'/includes/PhpretroHomes.php';
    $db = new Database();
    $serverdb = $db;
    $schema = file_get_contents($root.'/references/schema/CleanDB.sql');
    foreach (['users', 'users_settings', 'rooms', 'guilds', 'guilds_members'] as $table) {
        if (!preg_match('/CREATE TABLE IF NOT EXISTS `'.preg_quote($table, '/').'` \(.*?\) ENGINE=.*?;/s', $schema, $match)) {
            throw new RuntimeException('Missing verified table '.$table);
        }
        $db->execute($match[0]);
    }
    $custom = file_get_contents($root.'/migrations/001_custom_tables.sql');
    foreach (['phpretro_myhabbo_layouts'] as $table) {
        if (!preg_match('/CREATE TABLE IF NOT EXISTS `'.$table.'` \(.*?\) ENGINE=.*?;/s', $custom, $match)) {
            throw new RuntimeException('Missing custom table '.$table);
        }
        $db->execute($match[0]);
    }
    foreach (['003_web_minimail.sql', '007_restore_remaining_501s.sql', '009_guild_tags.sql'] as $file) {
        $migration = preg_replace('/^\s*--.*$/m', '', file_get_contents($root.'/migrations/'.$file)) ?? '';
        foreach (array_filter(array_map('trim', explode(';', $migration))) as $sql) {
            if ($sql !== '') { $db->execute($sql); }
        }
    }
    foreach (range(1, 4) as $id) {
        $db->execute('INSERT INTO users (id, username, password, account_created, ip_register, ip_current, motto, look, credits, online) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$id, 'User'.$id, '', 100, '127.0.0.1', '127.0.0.1', 'motto', 'look', 50, '0']);
        $db->execute('INSERT INTO users_settings (user_id, tags) VALUES (?, ?)', [$id, $id === 1 ? 'music;' : '']);
    }
    $db->execute('INSERT INTO rooms (id, owner_id, owner_name, name, description, guild_id) VALUES (1, 1, ?, ?, ?, 0)', ['User1', 'Room One', '']);
    $db->execute('INSERT INTO guilds (id, user_id, name, description, date_created, room_id) VALUES (1, 1, ?, ?, ?, 1), (2, 2, ?, ?, ?, 0)', ['Crew', 'desc', 100, 'Other', '', 100]);
    $db->execute('INSERT INTO guilds_members (guild_id, user_id, level_id) VALUES (1, 1, 0), (1, 2, 1), (1, 3, 2), (2, 2, 0)');
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
    function userTags(): array {
        global $db;
        return $db->fetchAll('SELECT user_id, tags FROM users_settings ORDER BY user_id');
    }

    check($db->fetchColumn("SHOW TABLES LIKE 'phpretro_guild_tags'") !== false, '009 created phpretro_guild_tags');
    check($db->fetchColumn("SHOW COLUMNS FROM guilds LIKE 'tags'") === false, 'PolarIS guilds still has no tags column');

    $empty = endpoint('myhabbo_tag_listgrouptags.php', ['groupId' => '1']);
    check($empty[1] === 200 && str_contains($empty[0], 'No tags.'), 'Empty group list shows No tags.');
    check(!str_contains($empty[0], 'habblet-unavailable'), 'List is not 501');

    $beforeTags = userTags();
    check(endpoint('myhabbo_tag_addgrouptag.php', ['groupId' => '1', 'tagName' => 'Crew'])[0] === 'valid', 'Owner add returns valid');
    check($db->fetchColumn('SELECT tag FROM phpretro_guild_tags WHERE guild_id = 1') === 'crew', 'Stored lowercase');
    check(endpoint('myhabbo_tag_addgrouptag.php', ['groupId' => '1', 'tagName' => 'CREW'])[0] === 'invalidtag', 'Duplicate is case-insensitive');
    asUser(2);
    check(endpoint('myhabbo_tag_addgrouptag.php', ['groupId' => '1', 'tagName' => 'music'])[0] === 'valid', 'Admin can add');
    asUser(3);
    check(endpoint('myhabbo_tag_addgrouptag.php', ['groupId' => '1', 'tagName' => 'member'])[0] === 'invalidtag', 'Member cannot add');
    asUser(4);
    check(endpoint('myhabbo_tag_addgrouptag.php', ['groupId' => '1', 'tagName' => 'stranger'])[0] === 'invalidtag', 'Stranger cannot add');
    asUser(1);
    check(endpoint('myhabbo_tag_addgrouptag.php', ['groupId' => '2', 'tagName' => 'crew'])[0] === 'invalidtag', 'Owner cannot tag a foreign guild');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_guild_tags WHERE guild_id = 2') === 0, 'Foreign guild unchanged');
    foreach (['', str_repeat('x', 21), 'bad;tag', "' OR 1=1 --", 'hello world!', '<script>'] as $tag) {
        check(endpoint('myhabbo_tag_addgrouptag.php', ['groupId' => '1', 'tagName' => $tag])[0] === 'invalidtag', 'Rejected group tag '.substr($tag, 0, 12));
    }
    check(endpoint('myhabbo_tag_addgrouptag.php', ['groupId' => '1', 'tagName' => ['crew']])[0] === 'invalidtag', 'Array tag rejected');
    check(endpoint('myhabbo_tag_addgrouptag.php', ['groupId' => '0', 'tagName' => 'crew'])[0] === 'invalidtag', 'Missing group rejected');
    check($beforeTags === userTags(), 'Group tag writes never touch users_settings.tags');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_guild_tags WHERE guild_id = 1') === 2, 'Only owner/admin tags stored');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_emulator_outbox WHERE event_type = ?', ['guild.tag_added']) === 2, 'Add records outbox');

    $list = endpoint('myhabbo_tag_listgrouptags.php', ['groupId' => '1']);
    check(str_contains($list[0], 'class="tag"') && str_contains($list[0], 'crew') && str_contains($list[0], 'music'), 'List renders stored tags');
    check(str_contains($list[0], 'class="tag-delete-link"'), 'Owner list has delete controls');
    check(!str_contains($list[0], 'class="tag-add-link"'), 'Owner list has no add-to-me controls');
    asUser(3);
    $memberList = endpoint('myhabbo_tag_listgrouptags.php', ['groupId' => '1']);
    check(str_contains($memberList[0], 'class="tag-add-link"'), 'Member list has add-to-me controls');
    check(!str_contains($memberList[0], 'class="tag-delete-link"'), 'Member list has no delete controls');
    $beforeCount = (int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_guild_tags WHERE guild_id = 1');
    endpoint('myhabbo_tag_removegrouptag.php', ['groupId' => '1', 'tagName' => 'crew']);
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_guild_tags WHERE guild_id = 1') === $beforeCount, 'Member remove is a no-op');
    asUser(1);
    $removed = endpoint('myhabbo_tag_removegrouptag.php', ['groupId' => '1', 'tagName' => 'MUSIC']);
    check($removed[1] === 200 && str_contains($removed[0], 'crew') && !str_contains($removed[0], 'music'), 'Owner remove is case-insensitive and re-lists');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_guild_tags WHERE guild_id = 1') === 1, 'Removed tag deleted');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_emulator_outbox WHERE event_type = ?', ['guild.tag_removed']) === 1, 'Remove records outbox');
    endpoint('myhabbo_tag_removegrouptag.php', ['groupId' => '1', 'tagName' => 'crew']);
    $cleared = endpoint('myhabbo_tag_listgrouptags.php', ['groupId' => '1']);
    check(str_contains($cleared[0], 'No tags.'), 'Last removal shows No tags.');
    check($beforeTags === userTags(), 'Removes still do not write users_settings.tags');

    for ($n = 1; $n <= 20; $n++) {
        check(endpoint('myhabbo_tag_addgrouptag.php', ['groupId' => '1', 'tagName' => 't'.$n])[0] === 'valid', 'Fill tag '.$n);
    }
    check(endpoint('myhabbo_tag_addgrouptag.php', ['groupId' => '1', 'tagName' => 'extra'])[0] === 'taglimit', '21st tag is taglimit');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_guild_tags WHERE guild_id = 1') === 20, 'Cap is 20');

    $search = endpoint('ajax_tagsearch.php', ['tag' => 't1', 'pageNumber' => '1']);
    check(str_contains($search[0], '0 users.'), 'Search keeps users. count');
    check(str_contains($search[0], '1 groups.'), 'Search reports group hits');
    check(str_contains($search[0], 'Crew') && str_contains($search[0], '/groups/1/id'), 'Search lists matching group');
    $partial = endpoint('ajax_tagsearch.php', ['tag' => 't', 'pageNumber' => '1']);
    check(str_contains($partial[0], '0 groups.') && !str_contains($partial[0], 'search-result-groups'), 'Group search is exact match');
    $injected = endpoint('ajax_tagsearch.php', ['tag' => "' OR 1=1 --", 'pageNumber' => '1']);
    check(str_contains($injected[0], '0 groups.'), 'Group search injection stays data');
    $case = endpoint('ajax_tagsearch.php', ['tag' => 'T1', 'pageNumber' => '1']);
    check(str_contains($case[0], '1 groups.'), 'Group search is case-insensitive');

    $_SESSION['group_page_edit'] = 1;
    $info = endpoint('groups_widgets.php', ['widgetType' => 'groupinfowidget']);
    check($info[1] === 200 && str_contains($info[0], 'GroupInfoWidget'), 'Group info widget renders');
    check(str_contains($info[0], 'id="profile-tag-list"'), 'Widget has JS list hook');
    check(str_contains($info[0], 'id="profile-add-tag"') && str_contains($info[0], 'id="profile-add-tag-input"'), 'Owner widget has add form');
    check(str_contains($info[0], 'new GroupInfoWidget(1, 1)'), 'Widget binds GroupInfoWidget(guildId, userId)');
    check(str_contains($info[0], 'class="tag-delete-link"'), 'Owner widget lists delete controls');
    $guildHelpers = substr(file_get_contents($root.'/includes/habblet.php'), (int) strpos(file_get_contents($root.'/includes/habblet.php'), 'function habbletCanEditGuildTags'));
    check(!str_contains($guildHelpers, 'users_settings'), 'Guild-tag helpers never write users_settings');
    $habblets = file_get_contents($root.'/habblet/myhabbo_tag_addgrouptag.php').file_get_contents($root.'/habblet/myhabbo_tag_listgrouptags.php').file_get_contents($root.'/habblet/myhabbo_tag_removegrouptag.php');
    check(!str_contains($habblets, 'users_settings'), 'Group-tag habblets never mention users_settings');

    echo "PASS: $assertions assertions; group tags on disposable MariaDB.\n";
} finally {
    restore_error_handler();
    $admin->exec('DROP DATABASE `'.$testName.'`');
    putenv('DB_DSN='.$sourceDsn);
}
