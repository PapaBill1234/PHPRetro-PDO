<?php
$root = dirname(__DIR__);
require_once $root.'/includes/config.php';
$sourceDsn = getenv('DB_DSN') ?: '';
if (!str_starts_with($sourceDsn, 'mysql:')) { throw new RuntimeException('Set DB_DSN to a MySQL DSN.'); }
$testName = 'phpretro_restore_remaining_'.bin2hex(random_bytes(6));
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
    require_once $root.'/includes/PhpretroWebRestorations.php';
    require_once $root.'/includes/PhpretroHelpdesk.php';
    require_once $root.'/includes/PhpretroMinimail.php';
    require_once $root.'/includes/PhpretroHomes.php';
    $db = new Database();
    $serverdb = $db;
    $schema = file_get_contents($root.'/references/schema/CleanDB.sql');
    foreach (['users', 'users_settings', 'messenger_friendships', 'messenger_friendrequests', 'rooms', 'room_rights', 'guilds', 'guilds_members', 'guilds_forums_threads', 'guilds_forums_comments'] as $table) {
        if (!preg_match('/CREATE TABLE IF NOT EXISTS `'.preg_quote($table, '/').'` \(.*?\) ENGINE=.*?;/s', $schema, $match)) {
            throw new RuntimeException('Missing verified table '.$table);
        }
        $db->execute($match[0]);
    }
    if (!preg_match('/CREATE TABLE IF NOT EXISTS `guilds` \(.*?\) ENGINE=.*?;/s', $schema, $guildMatch)) {
        throw new RuntimeException('Missing guilds');
    }
    $custom = file_get_contents($root.'/migrations/001_custom_tables.sql');
    preg_match('/CREATE TABLE IF NOT EXISTS `phpretro_collectibles` \(.*?\) ENGINE=.*?;/s', $custom, $match);
    $db->execute($match[0]);
    preg_match('/CREATE TABLE IF NOT EXISTS `phpretro_myhabbo_layouts` \(.*?\) ENGINE=.*?;/s', $custom, $match);
    $db->execute($match[0]);
    preg_match('/CREATE TABLE IF NOT EXISTS `phpretro_myhabbo_guestbook` \(.*?\) ENGINE=.*?;/s', $custom, $match);
    $db->execute($match[0]);
    preg_match('/CREATE TABLE IF NOT EXISTS phpretro_user_reports \(.*?\) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;/s', file_get_contents($root.'/migrations/002_admin_features.sql'), $match);
    $db->execute($match[0]);
    foreach (['003_web_minimail.sql', '004_web_homes.sql', '005_web_group_urls.sql', '006_restore_remaining.sql'] as $file) {
        $migration = preg_replace('/^\s*--.*$/m', '', file_get_contents($root.'/migrations/'.$file)) ?? '';
        foreach (array_filter(array_map('trim', explode(';', $migration))) as $sql) {
            if ($sql !== '') { $db->execute($sql); }
        }
    }
    foreach (range(1, 3) as $id) {
        $db->execute('INSERT INTO users (id, username, password, account_created, ip_register, ip_current, mail, credits) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [$id, 'User'.$id, '', 100, '127.0.0.1', '127.0.0.1', 'user'.$id.'@example.com', 50]);
        $db->execute('INSERT INTO users_settings (user_id, club_expire_timestamp) VALUES (?, ?)', [$id, $id === 1 ? time() + 86400 : 0]);
    }
    $db->execute('INSERT INTO rooms (id, owner_id, owner_name, name, description, guild_id) VALUES (1, 1, ?, ?, ?, 0), (2, 2, ?, ?, ?, 0)', ['User1', 'Room One', '', 'User2', 'Room Two', '']);
    $db->execute('INSERT INTO room_rights (room_id, user_id) VALUES (1, 3)');
    $month = mktime(0, 0, 0, (int) date('m'), 1, (int) date('Y'));
    $db->execute('INSERT INTO phpretro_collectibles (name, description, image, time) VALUES (?, ?, ?, ?)', ['Rare <b>', 'A rare', '/img.png', $month]);
    $db->execute('INSERT INTO phpretro_club_gifts (month, name, image, description) VALUES (?, ?, ?, ?)', [(int) date('n'), 'Club Gift <x>', '/gift.png', 'A gift']);
    chdir($root);
    $input = new HoloInput();
    $settings = new HoloSettings();
    $settings->cache['site_language'] = 'en';
    $settings->cache['site_allow_guests'] = '1';
    $settings->cache['site_session_time'] = '20';
    $settings->cache['site_capcha'] = '0';
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

    $guildCreate = $guildMatch[0];
    check(!preg_match('/`alias`|`name_seo`/', $guildCreate), 'PolarIS guilds have no alias column');
    check(!str_contains($schema, 'CREATE TABLE IF NOT EXISTS `help`'), 'PolarIS has no help table');
    check(str_contains($schema, 'CREATE TABLE IF NOT EXISTS `support_tickets`'), 'PolarIS support_tickets exists as the in-game mod tool');

    $mail = new PhpretroMinimail($db, 1, new PhpretroLiveSync($db));
    $db->execute('INSERT INTO messenger_friendships (user_one_id, user_two_id) VALUES (1, 2), (2, 1)');
    $ids = $mail->send([2], 'Evidence subject', 'Evidence body <b>');
    $messageId = $ids[0];
    asUser(2);
    endpoint('minimail_report.php', ['messageId' => $messageId]);
    $evidence = (string) $db->fetchColumn('SELECT evidence FROM phpretro_user_reports WHERE reporter_id = 2');
    check(str_contains($evidence, 'Evidence subject') && str_contains($evidence, 'Evidence body <b>'), 'Minimail report copies subject and body before delete');
    check($db->fetchColumn('SELECT id FROM phpretro_minimail WHERE id = ?', [$messageId]) === false, 'Reported copy is deleted after evidence is stored');
    asUser(1);

    $homesSql = file_get_contents($root.'/includes/PhpretroHomes.php').file_get_contents($root.'/home.php');
    check(!preg_match('/INSERT INTO users_settings|UPDATE users_settings/', $homesSql), 'Homes code never writes users_settings');

    $homes = phpretroHomes();
    $widget = $homes->add('guestbookwidget', 1);
    asUser(2);
    $entry = endpoint('myhabbo_guestbook_add.php', ['widgetId' => $widget['id'], 'message' => 'Hello guestbook']);
    check($entry[1] === 200, 'Logged-in non-friend can post while privacy configure is 501');
    asUser(1);

    $desk = new PhpretroHelpdesk($db, new PhpretroLiveSync($db));
    $ticketId = $desk->submit(1, 'User1', 'user1@example.com', '127.0.0.1', 'Help me', 'Something broke', 0);
    check($ticketId > 0, 'Helpdesk ticket stored');
    check($db->fetchColumn('SELECT synced_at FROM phpretro_helpdesk_tickets WHERE id = ?', [$ticketId]) === null, 'Helpdesk synced_at stays null');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_emulator_outbox WHERE event_type = ?', ['helpdesk.submitted']) === 1, 'Helpdesk records outbox');
    $desk->pickup($ticketId, 1);
    check($db->fetchColumn('SELECT status FROM phpretro_helpdesk_tickets WHERE id = ?', [$ticketId]) === 'picked', 'Staff can pick up a ticket');
    check(str_contains(file_get_contents($root.'/iot.php'), 'phpretroHelpdesk'), 'IOT submits through the helpdesk helper');
    check(str_contains(file_get_contents($root.'/housekeeping/help.php'), 'phpretro_helpdesk_tickets') || str_contains(file_get_contents($root.'/housekeeping/help.php'), 'phpretroHelpdesk'), 'Housekeeping help uses the CMS table');

    $confirm = endpoint('ajax_collectiblesConfirm.php');
    check($confirm[1] === 200 && str_contains($confirm[0], 'id="collectibles-purchase"'), 'Collectible confirm has purchase control');
    $buy = endpoint('ajax_collectiblesPurchase.php');
    check($buy[1] === 200 && str_contains($buy[0], htmlspecialchars('Rare <b>', ENT_COMPAT, 'UTF-8')), 'Collectible purchase escapes name');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_collectible_purchases WHERE user_id = 1') === 1, 'Collectible purchase stored once');
    endpoint('ajax_collectiblesPurchase.php');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_collectible_purchases WHERE user_id = 1') === 1, 'Duplicate collectible claim is ignored');
    check((int) $db->fetchColumn('SELECT credits FROM users WHERE id = 1') === 50, 'Collectible claim does not debit PolarIS credits');

    $gift = endpoint('ajax_habboclub_gift.php', ['month' => (string) date('n')]);
    check($gift[1] === 200 && str_contains($gift[0], htmlspecialchars('Club Gift <x>', ENT_COMPAT, 'UTF-8')), 'Club gift preview escapes');
    check(str_contains($gift[0], 'Preview only'), 'Club gift is preview-only');
    check((int) $db->fetchColumn('SELECT hc_gifts_claimed FROM users_settings WHERE user_id = 1') === 0 || $db->fetchColumn('SELECT hc_gifts_claimed FROM users_settings WHERE user_id = 1') === '0', 'Club gift preview does not write hc_gifts_claimed');

    check(endpoint('ajax_removeFeedItem.php', ['feedItemIndex' => 'hc-reminder'])[1] === 200, 'Feed item dismissed');
    check($db->fetchColumn('SELECT item_key FROM phpretro_feed_dismissals WHERE user_id = 1') === 'hc-reminder', 'Dismissal key stored');
    check(endpoint('ajax_removeFeedItem.php', ['feedItemIndex' => "' OR 1=1"])[1] === 200, 'Dismissal injection is literal');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_feed_dismissals WHERE user_id = 1') === 2, 'Injected key stored as data');

    $report = endpoint('mod_add_report.php', ['objectId' => '1'], ['type' => 'room']);
    check($report[0] === 'SUCCESS', 'Room report succeeds');
    check($db->fetchColumn('SELECT object_type FROM phpretro_object_reports WHERE reporter_id = 1') === 'room', 'Object report uses its own table');
    check(endpoint('mod_add_report.php', ['objectId' => '1'], ['type' => 'room'])[0] === 'SPAM', 'Duplicate object report is SPAM');
    check(endpoint('mod_add_report.php', ['objectId' => '99'], ['type' => 'room'])[1] === 404, 'Unknown room is not reported');
    check(endpoint('mod_add_report.php', ['objectId' => '1'], ['type' => 'stickie'])[0] === 'SUCCESS', 'Stickie reports do not require a user id');
    check(endpoint('mod_add_report.php', ['objectId' => '1'], ['type' => 'nonesuch'])[1] === 400, 'Unknown object type rejected');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_user_reports WHERE reporter_id = 1') === 0, 'Object reports do not write user reports');

    $bought = endpoint('grouppurchase_purchase_ajax.php', ['name' => 'New Crew', 'description' => 'Hello']);
    check($bought[1] === 200 && str_contains($bought[0], 'placeholder'), 'Purchase mentions placeholder badge');
    check($db->fetchColumn('SELECT badge FROM guilds WHERE name = ?', ['New Crew']) === 'b001010', 'Placeholder badge stored');
    check((int) $db->fetchColumn('SELECT color_one FROM guilds WHERE name = ?', ['New Crew']) === 1, 'Placeholder color_one is CleanDB first symbol color id');
    check((int) $db->fetchColumn('SELECT credits FROM users WHERE id = 1') === 40, 'Purchase charges PolarIS 10 credits');
    check((int) $db->fetchColumn('SELECT guild_id FROM rooms WHERE id = 1') > 0, 'Owned room attached');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM room_rights WHERE room_id = 1') === 0, 'Room rights cleared');
    check((int) $db->fetchColumn('SELECT level_id FROM guilds_members WHERE guild_id = (SELECT id FROM guilds WHERE name = ?) AND user_id = 1', ['New Crew']) === 0, 'Owner membership level 0');
    asUser(2);
    check(endpoint('grouppurchase_purchase_ajax.php', ['name' => 'No Club', 'description' => 'x'])[1] === 403, 'Club required');
    asUser(1);
    check(endpoint('grouppurchase_purchase_ajax.php', ['name' => '', 'description' => 'x'])[1] === 400, 'Empty name rejected');

    $edit = endpoint('groups_actions_startEditingSession.php', ['groupId' => (string) $db->fetchColumn('SELECT id FROM guilds WHERE name = ?', ['New Crew'])]);
    check($edit[1] === 302 && ($_SESSION['group_page_edit'] ?? 0) > 0, 'Editing session is website-owned');
    endpoint('groups_actions_cancelEditingSession.php', ['groupId' => (string) ($_SESSION['group_page_edit'] ?? 0)]);
    check(!isset($_SESSION['group_page_edit']), 'Cancel clears the website session');
    check(endpoint('groups_actions_show_badge_editor.php', ['groupId' => '1'])[1] === 501, 'Badge editor stays 501');

    echo "PASS: $assertions assertions; remaining restorations on disposable MariaDB.\n";
} finally {
    restore_error_handler();
    $admin->exec('DROP DATABASE `'.$testName.'`');
    putenv('DB_DSN='.$sourceDsn);
}
