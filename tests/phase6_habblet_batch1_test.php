<?php
/** Run with DB_DSN/DB_USER/DB_PASS for a MariaDB account allowed to create a scratch DB.
 * Uses only CREATE definitions from the checked-in schema, never the source seed data.
 * The application database is never selected by the test connection.
 */
$root = dirname(__DIR__);
require_once $root.'/includes/config.php';
$sourceDsn = getenv('DB_DSN') ?: '';
if (!str_starts_with($sourceDsn, 'mysql:')) { throw new RuntimeException('Set DB_DSN to a MySQL DSN.'); }
$testName = 'phpretro_phase6_test_'.bin2hex(random_bytes(6));
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
    $db = new Database();
    $serverdb = $db;
    $schema = file_get_contents($root.'/references/schema/CleanDB.sql');
    $tables = ['users', 'users_settings', 'users_badges', 'users_wardrobe', 'messenger_friendships', 'messenger_friendrequests', 'rooms', 'room_votes', 'room_promotions', 'guilds', 'guilds_members'];
    foreach ($tables as $table) {
        if (!preg_match('/CREATE TABLE IF NOT EXISTS `'.preg_quote($table, '/').'` \(.*?\) ENGINE=.*?;/s', $schema, $match)) {
            throw new RuntimeException('Missing verified table '.$table);
        }
        $db->execute($match[0]);
    }
    $custom = file_get_contents($root.'/migrations/001_custom_tables.sql');
    preg_match('/CREATE TABLE IF NOT EXISTS `phpretro_collectibles` \(.*?\) ENGINE=.*?;/s', $custom, $match);
    $db->execute($match[0]);
    foreach (['003_web_minimail.sql', '006_restore_remaining.sql'] as $file) {
        $migration = preg_replace('/^\s*--.*$/m', '', file_get_contents($root.'/migrations/'.$file)) ?? '';
        foreach (array_filter(array_map('trim', explode(';', $migration))) as $sql) {
            if ($sql !== '') { $db->execute($sql); }
        }
    }
    foreach (range(1, 36) as $id) {
        $name = $id === 2 ? 'Bob<script>' : sprintf('User%02d', $id);
        $db->execute('INSERT INTO users (id, username, password, account_created, ip_register, ip_current, motto, online) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [$id, $name, '', 100, '127.0.0.1', '127.0.0.1', 'original', $id === 2 ? '1' : '0']);
    }
    // Test-only fixtures in the scratch database, not CMS writes to users_settings.
    $db->execute('INSERT INTO users_settings (user_id, tags, block_friendrequests, club_expire_timestamp) VALUES (?, ?, ?, ?), (?, ?, ?, ?), (?, ?, ?, ?)', [1, 'music;games;', '0', 0, 2, 'music;art;', '0', 0, 3, 'musicbox;', '1', 0]);
    foreach ([2, 3] as $friend) {
        $db->execute('INSERT INTO messenger_friendships (user_one_id, user_two_id) VALUES (?, ?), (?, ?)', [1, $friend, $friend, 1]);
    }
    $db->execute('INSERT INTO messenger_friendships (user_one_id, user_two_id) VALUES (2, 4), (4, 2)');
    $db->execute('INSERT INTO rooms (id, owner_id, owner_name, name, description, users_max, is_staff_picked) VALUES (1, 1, ?, ?, ?, 0, ?)', ['User01', '<script>Room</script>', 'A & B', '1']);
    $db->execute('INSERT INTO room_votes (room_id, user_id) VALUES (1, 2)');
    $db->execute('INSERT INTO room_promotions (room_id, title, description, category, start_timestamp, end_timestamp) VALUES (1, ?, ?, 1, ?, ?)', ['Live <event>', 'Description', time() - 10, time() + 100]);
    foreach ([1, 2, 3, 4] as $id) {
        $db->execute('INSERT INTO guilds (id, user_id, name, date_created) VALUES (?, 1, ?, ?)', [$id, 'Guild'.$id, time()]);
        $db->execute('INSERT INTO guilds_members (guild_id, user_id, level_id) VALUES (?, 1, ?)', [$id, $id - 1]);
    }
    $db->execute('INSERT INTO users_badges (user_id, slot_id, badge_code) VALUES (2, 1, ?)', ['TEST']);
    $db->execute('INSERT INTO phpretro_collectibles (name, description, image, time) VALUES (?, ?, ?, ?)', ['Collectible <rare>', '', '', mktime(0, 0, 0, (int) date('m'), 1, (int) date('Y'))]);
    chdir($root);
    $input = new HoloInput();
    // Load the real language files through HoloLocale, with the required setting.
    $settings = new HoloSettings();
    $settings->cache['site_language'] = 'en';
    $settings->cache['site_allow_guests'] = '1';
    $settings->cache['site_session_time'] = '20';
    $settings->cache['site_c_images_path'] = '/images/';
    $settings->cache['site_badges_path'] = 'badges/';
    $lang = new HoloLocale();
    // Session fixture isolates the already-existing login/serialization implementation.
    $user = new class {
        public int $id = 1;
        public bool $logged_in = true;
        public int $error = 0;
        public string $ip = '127.0.0.1';
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
        $_POST = $post; $_GET = $get; $page = ['allow_guests' => false];
        http_response_code(200);
        ob_start();
        try { include $root.'/habblet/'.$name; return [ob_get_contents(), http_response_code()]; }
        finally { ob_end_clean(); }
    }
    $out = endpoint('minimail_recipients.php')[0];
    check(str_contains($out, 'Bob\\u003Cscript\\u003E'), 'Recipients JSON escapes markup');
    check(!str_contains($out, 'User04'), 'Recipients only contain own friendships');
    endpoint('ajax_addFriend.php', ['accountId' => '4']);
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM messenger_friendrequests WHERE user_from_id = 1 AND user_to_id = 4') === 1, 'Friend request inserted with correct direction');
    endpoint('myhabbo_friends_add.php', ['accountId' => '4']);
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM messenger_friendrequests') === 1, 'Duplicate request suppressed');
    foreach (['1', '99999', "4 OR 1=1", ['4']] as $id) { endpoint('ajax_addFriend.php', ['accountId' => $id]); }
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM messenger_friendrequests') === 1, 'Malformed/self/missing targets never inserted');
    $db->execute('DELETE FROM messenger_friendships WHERE user_one_id = 1 AND user_two_id = 3 OR user_one_id = 3 AND user_two_id = 1');
    endpoint('ajax_addFriend.php', ['accountId' => '3']);
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM messenger_friendrequests') === 1, 'Blocked requests respected');
    check(endpoint('ajax_confirmAddFriend.php', ['accountId' => 99999])[1] === 404, 'Missing confirmation target');
    check(str_contains(endpoint('ajax_confirmAddFriend.php', ['accountId' => 2])[0], 'Bob&lt;script&gt;'), 'Friend confirmation escaped');
    endpoint('ajax_updatemotto.php', ['motto' => "it's <safe> \\ text"]);
    check($db->fetchColumn('SELECT motto FROM users WHERE id = 1') === "it's <safe> \\ text", 'Motto bound literally');
    check($db->fetchColumn('SELECT motto FROM users WHERE id = 2') === 'original', 'Motto limited to owner');
    endpoint('ajax_updatemotto.php', ['motto' => str_repeat('x', 39)]);
    check($db->fetchColumn('SELECT motto FROM users WHERE id = 1') !== str_repeat('x', 39), 'Motto length gate');
    check(str_contains(endpoint('friendmanagement_viewcategory.php', ['searchString' => 'Bob'], ['pageSize' => '30'])[0], 'Bob&lt;script&gt;'), 'Friend-list filtering and escaping');
    check(str_contains(endpoint('habbosearchcontent.php', ['searchString' => 'User', 'pageNumber' => '2'])[0], 'User12'), 'Search pagination executes');
    check(!str_contains(endpoint('habbosearchcontent.php', ['searchString' => "' OR 1=1 --"])[0], 'User01'), 'Search injection stays data');
    check(str_contains(endpoint('ajax_password.php', ['password' => 'abcdef'])[0], 'passwordsuccess'), 'Password validator contract');
    check(str_contains(endpoint('ajax_password.php', ['password' => []])[0], 'passwordtooshort'), 'Malformed password rejected');
    check(str_contains(endpoint('ajax_emailcheck.php', ['email' => 'test@example.com'])[0], 'email_chars_ok'), 'Email validator contract');
    endpoint('ajax_namecheck.php', ['name' => 'User01']);
    endpoint('ajax_namecheck.php', ['name' => "' OR 1=1 --"]);
    check(str_contains(endpoint('myhabbo_avatarlist_avatarinfo.php', ['anAccountId' => 2])[0], 'Bob&lt;script&gt;'), 'Avatar lookup escaped');
    check(endpoint('myhabbo_avatarlist_avatarinfo.php', ['anAccountId' => 99999])[1] === 404, 'Missing avatar 404');
    check(str_contains(endpoint('ajax_load_events.php', ['eventTypeId' => '1'])[0], 'Live &lt;event&gt;'), 'Live event display');
    $db->execute('UPDATE room_promotions SET end_timestamp = ?', [time() - 1]);
    check(!str_contains(endpoint('ajax_load_events.php', ['eventTypeId' => '1'])[0], 'Live'), 'Expired event excluded');
    foreach (['h120', 'h21', 'h122', 'groups', 'h24'] as $hid) { check(endpoint('proxy.php', [], ['hid' => $hid])[1] === 200, 'Proxy '.$hid); }
    check(endpoint('proxy.php', [], ['hid' => 'nope'])[1] === 400, 'Proxy allowlist');
    check(str_contains(endpoint('quickmenu.php', [], ['key' => 'friends_all'])[0], 'Bob&lt;script&gt;'), 'Quick friends');
    $groups = endpoint('quickmenu.php', [], ['key' => 'groups'])[0];
    check(str_contains($groups, 'Guild3') && !str_contains($groups, 'Guild4'), 'Pending guild membership excluded');
    check(str_contains(endpoint('quickmenu.php', [], ['key' => 'rooms'])[0], '&lt;script&gt;Room'), 'Own rooms escaped');
    check(endpoint('quickmenu.php', [], ['key' => []])[1] === 400, 'Menu allowlist malformed input');
    check(habbletTagCount($db, 'music') === 2, 'Tag counts require whole tag');
    check(habbletTagCount($db, "' OR 1=1 --") === 0, 'Tag SQL injection stays data');
    check(str_contains(endpoint('ajax_tagfight.php', ['tag1' => 'music', 'tag2' => 'art'])[0], 'music (2)'), 'Tag fight user counts');
    check(str_contains(endpoint('ajax_tagmatch.php', ['friendName' => 'Bob<script>'])[0], '50 %'), 'Tag overlap uses intersection');
    check(str_contains(endpoint('ajax_tagsearch.php', ['tag' => 'music', 'pageNumber' => '1'])[0], '2 users.'), 'Tag search bound pagination');
    check(str_contains(endpoint('mytagslist.php')[0], 'music'), 'Own user tags');
    check(str_contains(endpoint('ajax_collectiblesConfirm.php')[0], 'Collectible &lt;rare&gt;'), 'Custom collectible metadata escaped');
    $figure = 'hd-180-1.ch-876-62.lg-280-62.sh-300-62';
    check(endpoint('wardrobeStore.php', ['slot' => '1', 'figure' => $figure, 'gender' => 'M'])[1] === 200, 'Wardrobe insert validates');
    check($db->fetchColumn('SELECT look FROM users_wardrobe WHERE user_id = 1 AND slot_id = 1') === $figure, 'Wardrobe mapped columns');
    endpoint('wardrobeStore.php', ['slot' => '1', 'figure' => $figure, 'gender' => 'M']);
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM users_wardrobe') === 1, 'Wardrobe updates existing slot');
    check(endpoint('wardrobeStore.php', ['slot' => "1 OR 1=1", 'figure' => $figure, 'gender' => 'M'])[1] === 400, 'Wardrobe malformed slot');
    endpoint('friendmanagement_deletefriends.php', ['friendList' => ['2', '4']]);
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM messenger_friendships WHERE user_one_id = 1 OR user_two_id = 1') === 0, 'Both friendship directions deleted');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM messenger_friendships WHERE user_one_id = 2 AND user_two_id = 4') === 1, 'Unrelated friendships preserved');
    check(str_contains(endpoint('minimail_recipients.php')[0], '[]'), 'Empty recipient JSON valid');
    $blocked = ['ajax_redeemvoucher.php', 'habboclub_habboclub_reminder_remove.php', 'habboclub_habboclub_subscribe.php'];
    $before = $db->fetchAll('SELECT id, credits FROM users ORDER BY id');
    foreach ($blocked as $file) { check(endpoint($file, ['messageId' => "' OR 1=1", 'objectId' => '2'])[1] === 501, $file.' unavailable'); }
    check($before === $db->fetchAll('SELECT id, credits FROM users ORDER BY id'), 'Unavailable purchases do not debit balances');
    $claim = endpoint('ajax_collectiblesPurchase.php');
    check($claim[1] === 200 && str_contains($claim[0], 'Collectible &lt;rare&gt;'), 'Collectible claim records website purchase');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_collectible_purchases') === 1, 'Collectible purchase stored');
    check($before === $db->fetchAll('SELECT id, credits FROM users ORDER BY id'), 'Collectible claim does not debit PolarIS credits');
    check(endpoint('ajax_habboclub_gift.php', ['month' => '1'])[1] === 200, 'Club gift preview');
    check(endpoint('ajax_removeFeedItem.php', ['feedItemIndex' => '3'])[1] === 200, 'Feed dismissal');
    check(endpoint('mod_add_report.php', ['objectId' => '1'], ['type' => 'room'])[1] === 200, 'Room object report');
    check(str_contains(endpoint('mod_add_report.php', ['objectId' => '1'], ['type' => 'room'])[0], 'SPAM'), 'Duplicate object report is SPAM');
    restore_error_handler();
    echo "PASS: $assertions assertions; all 37 batch-1 endpoints exercised against disposable MariaDB schema.\n";
} finally {
    $admin->exec('DROP DATABASE `'.$testName.'`');
    putenv('DB_DSN='.$sourceDsn);
}
