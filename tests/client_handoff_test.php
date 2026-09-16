<?php
/** Club / voucher / badge editor are HTTP 200 client-handoff pages.
 * They must not charge PolarIS, grant club, write voucher_history, or save guilds.badge.
 * Run with DB_DSN/DB_USER/DB_PASS for a MariaDB account allowed to create a scratch DB.
 */
$root = dirname(__DIR__);
require_once $root.'/includes/config.php';
$sourceDsn = getenv('DB_DSN') ?: '';
if (!str_starts_with($sourceDsn, 'mysql:')) { throw new RuntimeException('Set DB_DSN to a MySQL DSN.'); }
$testName = 'phpretro_client_handoff_'.bin2hex(random_bytes(6));
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
    foreach (['users', 'users_settings', 'users_subscriptions', 'rooms', 'guilds', 'guilds_members', 'vouchers', 'voucher_history'] as $table) {
        if (!preg_match('/CREATE TABLE IF NOT EXISTS `'.preg_quote($table, '/').'` \(.*?\) ENGINE=.*?;/s', $schema, $match)) {
            throw new RuntimeException('Missing verified table '.$table);
        }
        $db->execute($match[0]);
    }
    foreach (range(1, 3) as $id) {
        $db->execute('INSERT INTO users (id, username, password, account_created, ip_register, ip_current, motto, look, credits, online) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$id, 'User'.$id, '', 100, '127.0.0.1', '127.0.0.1', 'motto', 'look', 50, '0']);
        $db->execute('INSERT INTO users_settings (user_id) VALUES (?)', [$id]);
    }
    $db->execute('INSERT INTO rooms (id, owner_id, owner_name, name, description, guild_id) VALUES (1, 1, ?, ?, ?, 0)', ['User1', 'Room One', '']);
    $db->execute('INSERT INTO guilds (id, user_id, name, description, date_created, room_id, badge) VALUES (1, 1, ?, ?, ?, 1, ?)', ['Crew', 'desc', 100, 'b001010']);
    $db->execute('INSERT INTO guilds_members (guild_id, user_id, level_id) VALUES (1, 1, 0), (1, 2, 1), (1, 3, 2)');
    $db->execute('INSERT INTO vouchers (id, code, credits, points, points_type, catalog_item_id, amount, `limit`) VALUES (1, ?, 25, 0, 0, 0, 1, -1)', ['TESTCODE']);
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
        public function GiveHC($id, $months) { throw new RuntimeException('GiveHC must not be called'); }
        public function user($key = null) { return $key === null ? $this->user : ($this->user[$key] ?? null); }
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
        if (function_exists('header_remove')) { header_remove(); }
        http_response_code(200);
        ob_start();
        try {
            include $root.'/habblet/'.$name;
            return [ob_get_contents(), http_response_code(), headers_list()];
        } finally { ob_end_clean(); }
    }
    function asUser(int $id): void { global $user; $user->id = $id; $user->logged_in = true; $user->error = 0; $user->name = 'User'.$id; }
    function headerHas(array $headers, string $needle): bool {
        foreach ($headers as $header) {
            if (stripos($header, $needle) !== false) { return true; }
        }
        return false;
    }
    function snapshot(): array {
        global $db;
        return [
            'credits' => $db->fetchAll('SELECT id, credits FROM users ORDER BY id'),
            'subs' => (int) $db->fetchColumn('SELECT COUNT(*) FROM users_subscriptions'),
            'vouchers' => $db->fetchAll('SELECT id, code, credits, amount FROM vouchers ORDER BY id'),
            'history' => (int) $db->fetchColumn('SELECT COUNT(*) FROM voucher_history'),
            'badge' => $db->fetchColumn('SELECT badge FROM guilds WHERE id = 1'),
        ];
    }

    $club = file_get_contents($root.'/club.php');
    check(!str_contains($club, 'subscribe1'), 'club.php has no subscribe1 buy button');
    check(!str_contains($club, 'habboclub.js'), 'club.php does not load habboclub.js');
    check(!preg_match('/\b20\b.*coins|\b50\b.*coins|\b80\b.*coins/i', $club), 'club.php does not advertise fake 20/50/80 prices');
    check(str_contains($club, 'Buy it from the hotel catalog'), 'club.php points at the hotel catalog');
    check(str_contains($club, 'Open hotel'), 'club.php has Open hotel');

    $subscribeSrc = file_get_contents($root.'/habblet/habboclub_habboclub_subscribe.php');
    $confirmSrc = file_get_contents($root.'/habblet/habboclub_habboclub_confirm.php');
    $redeemSrc = file_get_contents($root.'/habblet/ajax_redeemvoucher.php');
    $actionsSrc = file_get_contents($root.'/includes/habblet_groups_actions.php');
    check(!str_contains($subscribeSrc, 'GiveHC') && !str_contains($confirmSrc, 'GiveHC'), 'Club habblets do not call GiveHC');
    check(!str_contains($subscribeSrc, 'X-JSON') && !str_contains($subscribeSrc, 'daysLeft'), 'Subscribe does not send X-JSON daysLeft');
    check(!str_contains($redeemSrc, 'voucher_history') && !str_contains($redeemSrc, 'UPDATE users SET credits'), 'Redeem does not write PolarIS credits or history');
    check(!str_contains($actionsSrc, 'BadgeEditor.swf'), 'Badge editor action does not embed Flash');
    check(str_contains($actionsSrc, "throw new HabbletGroupError('Use the game client to edit group badges."), 'update_group_badge stays 501');

    $before = snapshot();
    $confirm = endpoint('habboclub_habboclub_confirm.php', ['optionNumber' => '99']);
    check($confirm[1] === 200, 'Confirm is 200 even with a bogus optionNumber');
    check(str_contains($confirm[0], 'habblet-client-handoff') && str_contains($confirm[0], 'Buy club in the hotel'), 'Confirm is handoff HTML');
    check(headerHas($confirm[2], 'X-PHPRetro-Feature: client-handoff'), 'Confirm sends client-handoff header');
    check(!headerHas($confirm[2], 'X-JSON'), 'Confirm does not send X-JSON');
    check(str_contains($confirm[0], 'id="client"') || str_contains($confirm[0], '/client'), 'Confirm has Open hotel');

    $subscribe = endpoint('habboclub_habboclub_subscribe.php', ['optionNumber' => '1']);
    check($subscribe[1] === 200 && str_contains($subscribe[0], 'habblet-client-handoff'), 'Subscribe is client-handoff');
    check(headerHas($subscribe[2], 'X-PHPRetro-Feature: client-handoff'), 'Subscribe sends client-handoff header');
    check(!headerHas($subscribe[2], 'X-JSON') && !headerHas($subscribe[2], 'daysLeft'), 'Subscribe does not fake daysLeft');
    check(!str_contains($subscribe[0], 'habblet-unavailable'), 'Subscribe is not 501 HTML');

    $redeem = endpoint('ajax_redeemvoucher.php', ['voucherCode' => 'TESTCODE"><script>']);
    check($redeem[1] === 200 && str_contains($redeem[0], 'habblet-client-handoff'), 'Redeem is client-handoff');
    check(headerHas($redeem[2], 'X-PHPRetro-Feature: client-handoff'), 'Redeem sends client-handoff header');
    check(str_contains($redeem[0], 'id="purse-habblet-redeemcode-string"'), 'Redeem keeps the purse input id');
    check(str_contains($redeem[0], 'id="purse-redeemcode-button"'), 'Redeem keeps the purse button id');
    check(str_contains($redeem[0], htmlspecialchars('TESTCODE"><script>', ENT_QUOTES, 'UTF-8')), 'Redeem echoes the code escaped');
    check(!str_contains($redeem[0], 'TESTCODE"><script>'), 'Redeem does not reflect raw voucher HTML');
    check(str_contains($redeem[0], 'purse-balance-amount') && str_contains($redeem[0], '50 Coins'), 'Redeem still shows the current purse balance');

    $badge = endpoint('groups_actions_show_badge_editor.php', ['groupId' => '1']);
    check($badge[1] === 200 && str_contains($badge[0], 'habblet-client-handoff'), 'show_badge_editor is client-handoff');
    check(headerHas($badge[2], 'X-PHPRetro-Feature: client-handoff'), 'show_badge_editor sends client-handoff header');
    check(!str_contains($badge[0], 'BadgeEditor.swf') && !str_contains($badge[0], '.swf'), 'show_badge_editor does not embed Flash');

    $update = endpoint('groups_actions_update_group_badge.php', ['groupId' => '1', 'code' => 'b002020']);
    check($update[1] === 501 && str_contains($update[0], 'habblet-unavailable'), 'update_group_badge stays 501');
    check(headerHas($update[2], 'X-PHPRetro-Feature: unavailable'), 'update_group_badge sends unavailable header');

    asUser(2);
    $adminBadge = endpoint('groups_actions_show_badge_editor.php', ['groupId' => '1']);
    check($adminBadge[1] === 403, 'Non-owner cannot open the badge handoff');
    asUser(3);
    $memberBadge = endpoint('groups_actions_show_badge_editor.php', ['groupId' => '1']);
    check($memberBadge[1] === 403, 'Member cannot open the badge handoff');
    asUser(1);

    $after = snapshot();
    check($before['credits'] === $after['credits'], 'Handoffs do not debit users.credits');
    check($after['subs'] === 0, 'Handoffs do not write users_subscriptions');
    check($before['vouchers'] === $after['vouchers'], 'Handoffs do not consume vouchers');
    check($after['history'] === 0, 'Handoffs do not write voucher_history');
    check($after['badge'] === 'b001010', 'Handoffs do not write guilds.badge');

    echo "PASS: $assertions assertions; club/voucher/badge client-handoff on disposable MariaDB.\n";
} finally {
    restore_error_handler();
    $admin->exec('DROP DATABASE `'.$testName.'`');
    putenv('DB_DSN='.$sourceDsn);
}
