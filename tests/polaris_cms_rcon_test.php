<?php
$root = dirname(__DIR__);
require_once $root.'/includes/config.php';
$sourceDsn = getenv('DB_DSN') ?: '';
if (!str_starts_with($sourceDsn, 'mysql:')) { throw new RuntimeException('Set DB_DSN to a MySQL DSN.'); }
$testName = 'phpretro_polaris_cms_rcon_'.bin2hex(random_bytes(6));
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
    require_once $root.'/includes/PhpretroPolarisCms.php';
    require_once $root.'/includes/PhpretroMinimail.php';
    require_once $root.'/includes/PhpretroWebRestorations.php';
    $db = new Database();
    $serverdb = $db;
    $schema = file_get_contents($root.'/references/schema/CleanDB.sql');
    foreach (['users', 'users_settings', 'messenger_friendships', 'messenger_friendrequests', 'rooms', 'vouchers'] as $table) {
        if (!preg_match('/CREATE TABLE IF NOT EXISTS `'.preg_quote($table, '/').'` \(.*?\) ENGINE=.*?;/s', $schema, $match)) {
            throw new RuntimeException('Missing verified table '.$table);
        }
        $db->execute($match[0]);
    }
    preg_match('/CREATE TABLE IF NOT EXISTS phpretro_user_reports \(.*?\) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;/s', file_get_contents($root.'/migrations/002_admin_features.sql'), $match);
    $db->execute($match[0]);
    $minimail = preg_replace('/^\s*--.*$/m', '', file_get_contents($root.'/migrations/003_web_minimail.sql')) ?? '';
    foreach (array_filter(array_map('trim', explode(';', $minimail))) as $sql) {
        if ($sql !== '') { $db->execute($sql); }
    }
    preg_match('/CREATE TABLE IF NOT EXISTS `phpretro_object_reports` \(.*?\) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;/s', file_get_contents($root.'/migrations/006_restore_remaining.sql'), $match);
    $db->execute($match[0]);
    foreach (range(1, 3) as $id) {
        $db->execute('INSERT INTO users (id, username, password, account_created, ip_register, ip_current) VALUES (?, ?, ?, ?, ?, ?)', [$id, 'User'.$id, '', 100, '127.0.0.1', '127.0.0.1']);
    }
    $db->execute('INSERT INTO rooms (id, owner_id, owner_name, name, description, guild_id) VALUES (1, 1, ?, ?, ?, 0)', ['User1', 'Room One', '']);
    $db->execute('INSERT INTO messenger_friendships (user_one_id, user_two_id) VALUES (1, 2), (2, 1)');
    chdir($root);
    $input = new HoloInput();
    $settings = new HoloSettings();
    $settings->cache['site_language'] = 'en';
    $settings->cache['site_allow_guests'] = '1';
    $settings->cache['site_session_time'] = '20';
    $lang = new HoloLocale();
    $user = new class {
        public int $id = 1;
        public bool $logged_in = true;
        public int $error = 0;
        public string $ip = '127.0.0.1';
        public string $name = 'User1';
        public int $time;
        public function __construct() { $this->time = time(); }
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
    function asUser(int $id): void { global $user; $user->id = $id; $user->name = 'User'.$id; $user->logged_in = true; $user->error = 0; }
    function capturingCms(array &$captured, string $response = '{"status":0,"message":""}'): PhpretroPolarisCms {
        return new PhpretroPolarisCms(
            'http://127.0.0.1:30000',
            'cms-main',
            's3cret',
            '127.0.0.1',
            3001,
            static function (string $url, string $body, array $headers) use (&$captured, $response): string {
                $captured[] = ['transport' => 'cms', 'url' => $url, 'body' => $body, 'headers' => $headers];
                return $response;
            },
            static function (string $host, int $port, string $body) use (&$captured, $response): string {
                $captured[] = ['transport' => 'rcon', 'host' => $host, 'port' => $port, 'body' => $body];
                return $response;
            }
        );
    }

    $unconfigured = new PhpretroPolarisCms();
    check($unconfigured->configured() === false, 'Empty env is unconfigured');
    check($unconfigured->tryCommand('updatecatalog') === null, 'Unconfigured tryCommand is a no-op');
    try {
        $unconfigured->command('updatecatalog');
        check(false, 'Unconfigured command must throw');
    } catch (PhpretroPolarisCmsError $error) {
        check($error->getCode() === 503, 'Unconfigured command is 503');
    }

    $cmsOnly = new PhpretroPolarisCms('http://127.0.0.1:30000', 'cms-main', 's3cret');
    check($cmsOnly->cmsConfigured() && !$cmsOnly->rconConfigured(), 'CMS needs url+key+secret');
    check($cmsOnly->commandUrl() === 'http://127.0.0.1:30000/api/cms/command', 'CMS URL appends /api/cms/command');
    $body = $cmsOnly->encode('updatecatalog', []);
    check($body === '{"key":"updatecatalog","data":{}}', 'updatecatalog data is an object');
    $headers = $cmsOnly->cmsHeaders($body, '1700000000', 'abc123');
    check($headers['X-Cms-Signature'] === hash_hmac('sha256', "cms-main\n1700000000\nabc123\n".$body, 's3cret'), 'HMAC is keyId\\ntimestamp\\nnonce\\nrawBody');

    $captured = [];
    $cms = capturingCms($captured);
    PhpretroPolarisCms::setInstance($cms);
    $ok = $cms->command('updatecatalog', []);
    check($ok['status'] === 0 && $ok['transport'] === 'cms', 'CMS transport is preferred when configured');
    check(count($captured) === 1 && $captured[0]['transport'] === 'cms', 'RCON is not used when CMS is configured');
    check($captured[0]['url'] === 'http://127.0.0.1:30000/api/cms/command', 'CMS posts to command path');
    $signed = $captured[0]['headers'];
    check($signed['X-Cms-Signature'] === hash_hmac('sha256', "cms-main\n".$signed['X-Cms-Timestamp']."\n".$signed['X-Cms-Nonce']."\n".$captured[0]['body'], 's3cret'), 'Live request HMAC matches PolarIS formula');

    $rconCaptured = [];
    $rcon = new PhpretroPolarisCms('', '', '', '127.0.0.1', 3001, null, static function (string $host, int $port, string $body) use (&$rconCaptured): string {
        $rconCaptured[] = compact('host', 'port', 'body');
        return '{"status":0,"message":""}';
    });
    $rconResult = $rcon->hotelAlert('Hello hotel');
    check($rconResult['transport'] === 'rcon' && count($rconCaptured) === 1, 'RCON is used when CMS is not configured');
    check(str_contains($rconCaptured[0]['body'], '"hotelalert"') && str_contains($rconCaptured[0]['body'], '"Hello hotel"'), 'RCON payload is {key,data}');
    check($rcon->reloadCatalogNotice() === 'PolarIS catalog reloaded.', 'Catalog reload notice on success');
    check($unconfigured->reloadCatalogNotice() === 'PolarIS catalog cache not reloaded (CMS/RCON not configured).', 'Catalog reload notice when unconfigured');

    $captured = [];
    $offline = capturingCms($captured, '{"status":2,"message":""}');
    $offlineResult = $offline->alertUser(1, 'Hi');
    check($offlineResult['status'] === PhpretroPolarisCms::HABBO_NOT_FOUND, 'alertuser status 2 is HABBO_NOT_FOUND (offline or missing)');

    $captured = [];
    PhpretroPolarisCms::setInstance(capturingCms($captured));
    $mail = new PhpretroMinimail($db, 1, new PhpretroLiveSync($db));
    $ids = $mail->send([2], 'Evidence subject', 'Evidence body');
    asUser(2);
    endpoint('minimail_report.php', ['messageId' => $ids[0]]);
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_user_reports WHERE reporter_id = 2 AND reported_user_id = 1') === 1, 'Minimail report still writes phpretro_user_reports');
    check(count($captured) === 1 && str_contains($captured[0]['body'], '"modticket"'), 'Minimail report fires modticket');
    $ticket = json_decode($captured[0]['body'], true);
    check((int) $ticket['data']['sender_id'] === 2 && (int) $ticket['data']['reported_id'] === 1, 'modticket uses reporter and reported user ids');
    check($ticket['data']['reported_room_id'] === 0, 'Website reports do not invent a room id');
    check(str_contains($ticket['data']['message'], 'Evidence subject') && str_contains($ticket['data']['message'], 'Evidence body'), 'modticket message includes minimail evidence');

    $captured = [];
    PhpretroPolarisCms::setInstance(capturingCms($captured));
    asUser(1);
    $userReport = endpoint('report_user.php', ['reported_user_id' => '2', 'reason' => 'spam', 'evidence' => 'said spam']);
    check($userReport[1] === 200 && str_contains($userReport[0], '"ok":true'), 'report_user still succeeds');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_user_reports WHERE reporter_id = 1 AND reported_user_id = 2') === 1, 'report_user writes phpretro_user_reports');
    check(count($captured) === 1 && str_contains($captured[0]['body'], '"modticket"'), 'report_user fires modticket');
    $userTicket = json_decode($captured[0]['body'], true);
    check($userTicket['data']['sender_username'] === 'User1' && $userTicket['data']['reported_username'] === 'User2', 'modticket includes PolarIS usernames');

    $beforeObject = count($captured);
    $object = endpoint('mod_add_report.php', ['objectId' => '1'], ['type' => 'room']);
    check($object[0] === 'SUCCESS', 'Object/room reports stay on phpretro_object_reports');
    check(count($captured) === $beforeObject, 'Object reports do not fire modticket');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_object_reports WHERE reporter_id = 1') === 1, 'Object report stored');

    PhpretroPolarisCms::setInstance(new PhpretroPolarisCms());
    asUser(3);
    $offlineReport = endpoint('report_user.php', ['reported_user_id' => '1', 'reason' => 'later', 'evidence' => 'offline hotel']);
    check($offlineReport[1] === 200, 'Unconfigured PolarIS still stores the website report');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_user_reports WHERE reporter_id = 3') === 1, 'Website report is independent of PolarIS');

    $redeem = endpoint('ajax_redeemvoucher.php', ['voucherCode' => 'TEST']);
    check($redeem[1] === 501, 'Website voucher redeem stays 501');
    check(str_contains($redeem[0], 'hotel client'), 'Redeem still tells the user to use the client');

    $alerts = file_get_contents($root.'/housekeeping/alerts.php');
    check(!str_contains($alerts, 'PREFIX') && !str_contains($alerts, 'SendMUSData'), 'alerts.php dropped PREFIX and SendMUSData');
    check(str_contains($alerts, 'alertUser') && str_contains($alerts, 'hotelAlert'), 'alerts.php uses PolarIS alertuser/hotelalert');
    check(!str_contains($alerts, 'phpretro_alerts') && !preg_match('/INSERT INTO .+alerts/', $alerts), 'alerts.php does not persist an alerts table');
    $vouchers = file_get_contents($root.'/housekeeping/vouchers.php');
    check(str_contains($vouchers, 'reloadCatalogNotice'), 'vouchers.php reloads PolarIS catalog after writes');
    check(str_contains(file_get_contents($root.'/includes/PhpretroPolarisCms.php'), "'updatecatalog'"), 'Catalog reload uses PolarIS updatecatalog');
    check(str_contains($schema, 'CREATE TABLE IF NOT EXISTS `vouchers`'), 'Housekeeping still writes PolarIS vouchers');
    check(!str_contains($schema, 'CREATE TABLE IF NOT EXISTS `alerts`'), 'PolarIS has no alerts table');

    echo "PASS: $assertions assertions; PolarIS CMS/RCON wiring on disposable MariaDB.\n";
} finally {
    restore_error_handler();
    PhpretroPolarisCms::setInstance(null);
    $admin->exec('DROP DATABASE `'.$testName.'`');
    putenv('DB_DSN='.$sourceDsn);
}
