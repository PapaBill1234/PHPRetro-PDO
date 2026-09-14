<?php
$root = dirname(__DIR__);
require_once $root.'/includes/config.php';
$sourceDsn = getenv('DB_DSN') ?: '';
if (!str_starts_with($sourceDsn, 'mysql:')) { throw new RuntimeException('Set DB_DSN to a MySQL DSN.'); }
$testName = 'phpretro_web_homes_test_'.bin2hex(random_bytes(6));
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
    foreach (['users', 'users_settings', 'users_badges', 'messenger_friendships', 'rooms', 'guilds', 'guilds_members'] as $table) {
        if (!preg_match('/CREATE TABLE IF NOT EXISTS `'.preg_quote($table, '/').'` \(.*?\) ENGINE=.*?;/s', $schema, $match)) {
            throw new RuntimeException('Missing verified table '.$table);
        }
        $db->execute($match[0]);
    }
    $custom = file_get_contents($root.'/migrations/001_custom_tables.sql');
    foreach (['phpretro_myhabbo_layouts', 'phpretro_myhabbo_guestbook'] as $table) {
        preg_match('/CREATE TABLE IF NOT EXISTS `'.$table.'` \(.*?\) ENGINE=.*?;/s', $custom, $match);
        $db->execute($match[0]);
    }
    $migration = preg_replace('/^\s*--.*$/m', '', file_get_contents($root.'/migrations/003_web_minimail.sql')) ?? '';
    foreach (array_filter(array_map('trim', explode(';', $migration))) as $sql) { if ($sql !== '') { $db->execute($sql); } }
    $alter = preg_replace('/^\s*--.*$/m', '', file_get_contents($root.'/migrations/004_web_homes.sql')) ?? '';
    foreach (array_filter(array_map('trim', explode(';', $alter))) as $sql) { if ($sql !== '') { $db->execute($sql); } }
    $urls = preg_replace('/^\s*--.*$/m', '', file_get_contents($root.'/migrations/005_web_group_urls.sql')) ?? '';
    foreach (array_filter(array_map('trim', explode(';', $urls))) as $sql) { if ($sql !== '') { $db->execute($sql); } }
    foreach (range(1, 3) as $id) {
        $name = $id === 2 ? 'Bob<script>' : 'User'.$id;
        $db->execute('INSERT INTO users (id, username, password, account_created, ip_register, ip_current, motto, look) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [$id, $name, '', 100, '127.0.0.1', '127.0.0.1', 'motto', 'look']);
    }
    $db->execute('INSERT INTO users_settings (user_id, tags, hide_online, guild_id) VALUES (1, ?, ?, ?), (2, ?, ?, ?)', ['music;', '0', 1, 'art;', '0', 0]);
    $db->execute('INSERT INTO messenger_friendships (user_one_id, user_two_id) VALUES (1, 2), (2, 1)');
    $db->execute('INSERT INTO users_badges (user_id, slot_id, badge_code) VALUES (1, 1, ?)', ['TEST']);
    $db->execute('INSERT INTO rooms (id, owner_id, owner_name, name, description) VALUES (1, 1, ?, ?, ?)', ['User1', 'My Room', 'Desc']);
    $db->execute('INSERT INTO guilds (id, user_id, name, date_created) VALUES (1, 1, ?, ?)', ['Crew', 100]);
    $db->execute('INSERT INTO guilds_members (guild_id, user_id, level_id) VALUES (1, 1, 0)');
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

    $added = endpoint('myhabbo_widget_add.php', ['widget_key' => 'guestbookwidget', 'column_number' => '1']);
    check($added[1] === 200 && str_contains($added[0], 'GuestbookWidget'), 'Guestbook widget added');
    $guestbookId = (int) $db->fetchColumn('SELECT id FROM phpretro_myhabbo_layouts WHERE widget_key = ?', ['guestbookwidget']);
    check($guestbookId > 0, 'Layout row stored');
    check($db->fetchColumn('SELECT synced_at FROM phpretro_myhabbo_layouts WHERE id = ?', [$guestbookId]) === null, 'synced_at stays null');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_emulator_outbox WHERE event_type = ?', ['homes.widget_added']) === 1, 'Add records outbox');

    $profile = endpoint('myhabbo_widget_add.php', ['widget_key' => 'profilewidget']);
    check($profile[1] === 200 && str_contains($profile[0], htmlspecialchars('User1', ENT_COMPAT, 'UTF-8')), 'Profile widget renders');
    $profileId = (int) $db->fetchColumn('SELECT id FROM phpretro_myhabbo_layouts WHERE widget_key = ?', ['profilewidget']);
    check(endpoint('myhabbo_widget_delete.php', ['widgetId' => $profileId])[1] === 403, 'Profile widget cannot be deleted');

    check(endpoint('myhabbo_widget_add.php', ['widget_key' => 'traxplayerwidget'])[1] === 501, 'Trax widget stays unavailable');
    check(endpoint('myhabbo_widget_add.php', ['widget_key' => 'ratingwidget'])[1] === 501, 'Rating widget stays unavailable');
    check(endpoint('myhabbo_widget_add.php', ['widgetId' => '12'])[1] === 400, 'Catalogue item ids are not guessed');
    check(endpoint('groups_widgets.php')[1] === 501, 'Group homes stay unavailable');
    check(endpoint('myhabbo_guestbook_configure.php')[1] === 501, 'Guestbook privacy stays unavailable');

    $note = endpoint('myhabbo_guestbook_add.php', ['widgetId' => $guestbookId, 'message' => 'Hi <b>']);
    check($note[1] === 200 && str_contains($note[0], htmlspecialchars('Hi <b>', ENT_COMPAT, 'UTF-8')), 'Guestbook escapes message');
    $entryId = (int) $db->fetchColumn('SELECT id FROM phpretro_myhabbo_guestbook WHERE profile_user_id = 1');
    $list = endpoint('myhabbo_guestbook_list.php', ['widgetId' => $guestbookId]);
    check(str_contains($list[0], 'guestbook-entry-'.$entryId), 'Guestbook list uses layout widget id');

    asUser(2);
    check(endpoint('myhabbo_guestbook_remove.php', ['entryId' => $entryId])[1] === 403, 'Stranger cannot delete guestbook');
    asUser(1);
    check(endpoint('myhabbo_guestbook_remove.php', ['entryId' => $entryId])[1] === 200, 'Owner can delete guestbook');
    check($db->fetchColumn('SELECT id FROM phpretro_myhabbo_guestbook WHERE id = ?', [$entryId]) === false, 'Guestbook row deleted');

    $friendsWidget = endpoint('myhabbo_widget_add.php', ['widget_key' => 'friends']);
    check($friendsWidget[1] === 200, 'Short widget key aliases work');
    $friendsId = (int) $db->fetchColumn('SELECT id FROM phpretro_myhabbo_layouts WHERE widget_key = ?', ['friendswidget']);
    $friends = endpoint('myhabbo_avatarlist_friendsearchpaging.php', ['widgetId' => $friendsId, 'searchString' => 'Bob']);
    check(str_contains($friends[0], htmlspecialchars('Bob<script>', ENT_COMPAT, 'UTF-8')), 'Friends paging escapes names');
    check(str_contains($friends[0], 'avatar-list-'.$friendsId.'-2'), 'Friends paging uses layout widget id');

    $badges = endpoint('myhabbo_widget_add.php', ['widget_key' => 'badgeswidget', 'column_number' => '2']);
    check(str_contains($badges[0], 'TEST'), 'Badges widget reads users_badges');
    $groups = endpoint('myhabbo_widget_add.php', ['widget_key' => 'groupswidget']);
    check(str_contains($groups[0], 'Crew'), 'Groups widget reads guilds_members');
    $rooms = endpoint('myhabbo_widget_add.php', ['widget_key' => 'roomswidget']);
    check(str_contains($rooms[0], 'My Room'), 'Rooms widget reads owned rooms');
    $scores = endpoint('myhabbo_widget_add.php', ['widget_key' => 'highscoreswidget']);
    check($scores[1] === 200 && (str_contains($scores[0], 'No high scores') || str_contains($scores[0], 'high')), 'High scores has no PolarIS store');

    endpoint('myhabbo_homes.php', [], ['type' => 'startSession', 'id' => '1']);
    check(($_SESSION['page_edit'] ?? '') === 'home', 'Start session is website-owned');
    $badgesId = (int) $db->fetchColumn('SELECT id FROM phpretro_myhabbo_layouts WHERE widget_key = ?', ['badgeswidget']);
    endpoint('myhabbo_homes.php', ['type' => 'save', 'widgets' => $badgesId.':500,120,1'], ['type' => 'save']);
    check((int) $db->fetchColumn('SELECT column_number FROM phpretro_myhabbo_layouts WHERE id = ?', [$badgesId]) === 2, 'Save maps x to column');
    check(($_SESSION['page_edit'] ?? '') === '', 'Save ends edit session');

    asUser(2);
    check(endpoint('myhabbo_widget_delete.php', ['widgetId' => $guestbookId])[1] === 403, 'Cannot delete another home widget');
    asUser(1);
    endpoint('myhabbo_widget_delete.php', ['widgetId' => $guestbookId]);
    check($db->fetchColumn('SELECT id FROM phpretro_myhabbo_layouts WHERE id = ?', [$guestbookId]) === false, 'Owner can delete non-profile widget');

    echo "PASS: $assertions assertions; website homes on disposable MariaDB.\n";
} finally {
    restore_error_handler();
    $admin->exec('DROP DATABASE `'.$testName.'`');
    putenv('DB_DSN='.$sourceDsn);
}
