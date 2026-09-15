<?php
/** Run with DB_DSN/DB_USER/DB_PASS for a MariaDB account allowed to create a scratch DB. */
$root = dirname(__DIR__);
require_once $root.'/includes/config.php';
$sourceDsn = getenv('DB_DSN') ?: '';
if (!str_starts_with($sourceDsn, 'mysql:')) { throw new RuntimeException('Set DB_DSN to a MySQL DSN.'); }
$testName = 'phpretro_web_minimail_test_'.bin2hex(random_bytes(6));
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
    require_once $root.'/includes/PhpretroMinimail.php';
    $db = new Database();
    $serverdb = $db;
    $schema = file_get_contents($root.'/references/schema/CleanDB.sql');
    foreach (['users', 'users_settings', 'messenger_friendships', 'messenger_friendrequests'] as $table) {
        if (!preg_match('/CREATE TABLE IF NOT EXISTS `'.preg_quote($table, '/').'` \(.*?\) ENGINE=.*?;/s', $schema, $match)) {
            throw new RuntimeException('Missing verified table '.$table);
        }
        $db->execute($match[0]);
    }
    preg_match('/CREATE TABLE IF NOT EXISTS phpretro_user_reports \(.*?\) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;/s', file_get_contents($root.'/migrations/002_admin_features.sql'), $match);
    if (!$match) { throw new RuntimeException('Missing phpretro_user_reports'); }
    $db->execute($match[0]);
    $migration = preg_replace('/^\s*--.*$/m', '', file_get_contents($root.'/migrations/003_web_minimail.sql')) ?? '';
    foreach (array_filter(array_map('trim', explode(';', $migration))) as $sql) {
        if ($sql !== '') { $db->execute($sql); }
    }
    foreach (range(1, 4) as $id) {
        $name = $id === 2 ? 'Bob<script>' : 'User'.$id;
        $db->execute('INSERT INTO users (id, username, password, account_created, ip_register, ip_current, look) VALUES (?, ?, ?, ?, ?, ?, ?)', [$id, $name, '', 100, '127.0.0.1', '127.0.0.1', 'look-'.$id]);
    }
    foreach ([2, 3] as $friend) {
        $db->execute('INSERT INTO messenger_friendships (user_one_id, user_two_id) VALUES (?, ?), (?, ?)', [1, $friend, $friend, 1]);
    }
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
    function asUser(int $id): void { global $user; $user->id = $id; $user->name = 'User'.$id; $user->logged_in = true; $user->error = 0; }

    $empty = endpoint('minimail_loadMessages.php', ['label' => 'inbox']);
    check($empty[1] === 200 && str_contains($empty[0], 'No messages'), 'Empty inbox copy');
    check(str_contains($empty[0], 'label="inbox"') && str_contains($empty[0], 'label="sent"') && str_contains($empty[0], 'label="trash"'), 'Folder tabs retained');

    $denied = endpoint('minimail_sendMessage.php', ['recipientIds' => '4', 'subject' => 'Hi', 'body' => 'Hello']);
    check($denied[1] === 400, 'Non-friend send rejected');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_minimail') === 0, 'Non-friend leaves no row');

    $sent = endpoint('minimail_sendMessage.php', ['recipientIds' => '2,3', 'subject' => 'Hello <b>', 'body' => 'Body & more']);
    check($sent[1] === 200, 'Friend send succeeds');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_minimail') === 2, 'One row per recipient');
    check($db->fetchColumn('SELECT subject FROM phpretro_minimail WHERE recipient_id = 2') === 'Hello <b>', 'Subject stored literally');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_emulator_outbox WHERE event_type = ?', ['minimail.sent']) === 2, 'Send records outbox rows');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_emulator_outbox WHERE status = ? AND processed_at IS NULL', ['pending']) === 2, 'Notify is a no-op; rows stay pending');
    check($db->fetchColumn('SELECT synced_at FROM phpretro_minimail WHERE recipient_id = 2') === null, 'synced_at stays null');
    asUser(4);
    check(endpoint('minimail_loadMessage.php', [], ['messageId' => '1'])[1] === 403, 'Unrelated user cannot open mail');
    asUser(1);

    $injected = endpoint('minimail_sendMessage.php', ['recipientIds' => "' OR 1=1 --", 'subject' => 'x', 'body' => 'y']);
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_minimail') === 2, 'Recipient injection stays data');

    asUser(2);
    $inbox = endpoint('minimail_loadMessages.php', ['label' => 'inbox']);
    check(str_contains($inbox[0], htmlspecialchars('Hello <b>', ENT_COMPAT, 'UTF-8')) && str_contains($inbox[0], 'id="msg-'), 'Inbox escapes subject');
    check(str_contains($inbox[0], '(1)'), 'Unread inbox count');
    $open = endpoint('minimail_loadMessage.php', [], ['messageId' => '1', 'label' => 'inbox']);
    check($open[1] === 200 && str_contains($open[0], htmlspecialchars('Body & more', ENT_COMPAT, 'UTF-8')), 'Opened body escaped');
    check($db->fetchColumn('SELECT read_at FROM phpretro_minimail WHERE id = 1') !== null, 'Inbox open marks read');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_emulator_outbox WHERE event_type = ?', ['minimail.read']) === 1, 'Read records outbox');

    $reply = endpoint('minimail_sendMessage.php', ['messageId' => '1', 'body' => 'Thanks']);
    check($reply[1] === 200, 'Reply succeeds');
    check($db->fetchColumn('SELECT subject FROM phpretro_minimail WHERE recipient_id = 1 ORDER BY id DESC LIMIT 1') === 'Re: Hello <b>', 'Reply subject uses original');
    check((int) $db->fetchColumn('SELECT conversation_id FROM phpretro_minimail WHERE id = 1') > 0, 'Reply allocates conversation id');

    endpoint('minimail_deleteMessage.php', ['messageId' => '1', 'label' => 'inbox']);
    check((int) $db->fetchColumn('SELECT deleted FROM phpretro_minimail WHERE id = 1') === 1, 'Inbox delete moves to trash');
    $trash = endpoint('minimail_loadMessages.php', ['label' => 'trash']);
    check(str_contains($trash[0], 'id="msg-1"'), 'Trash lists deleted copy');
    endpoint('minimail_undeleteMessage.php', ['messageId' => '1']);
    check((int) $db->fetchColumn('SELECT deleted FROM phpretro_minimail WHERE id = 1') === 0, 'Undelete restores inbox');
    endpoint('minimail_deleteMessage.php', ['messageId' => '1', 'label' => 'inbox']);
    endpoint('minimail_deleteMessage.php', ['messageId' => '1', 'label' => 'trash']);
    check($db->fetchColumn('SELECT id FROM phpretro_minimail WHERE id = 1') === false, 'Second delete purges');

    $confirmOwn = endpoint('minimail_confirmReport.php', ['messageId' => (int) $db->fetchColumn('SELECT id FROM phpretro_minimail WHERE sender_id = 2 LIMIT 1')]);
    check(str_contains($confirmOwn[0], 'You can\'t report your own messages.') || str_contains($confirmOwn[0], 'own messages'), 'Cannot report own copy');

    asUser(3);
    $msg = (int) $db->fetchColumn('SELECT id FROM phpretro_minimail WHERE recipient_id = 3 LIMIT 1');
    $confirm = endpoint('minimail_confirmReport.php', ['messageId' => $msg]);
    check(str_contains($confirm[0], htmlspecialchars('Hello <b>', ENT_COMPAT, 'UTF-8')), 'Report confirm escapes subject');
    endpoint('minimail_report.php', ['messageId' => $msg, 'label' => 'inbox']);
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_user_reports WHERE reporter_id = 3 AND reported_user_id = 1') === 1, 'Report writes phpretro_user_reports');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM messenger_friendships WHERE (user_one_id = 1 AND user_two_id = 3) OR (user_one_id = 3 AND user_two_id = 1)') === 0, 'Report unfriends both directions');
    check($db->fetchColumn('SELECT id FROM phpretro_minimail WHERE id = ?', [$msg]) === false, 'Report deletes the recipient copy');

    asUser(1);
    $otherTrash = (int) $db->fetchColumn('SELECT id FROM phpretro_minimail WHERE recipient_id = 2 AND deleted = 1 LIMIT 1');
    if ($otherTrash < 1) {
        $db->execute('INSERT INTO phpretro_minimail (sender_id, recipient_id, subject, body, sent_at, deleted, deleted_at) VALUES (1, 2, ?, ?, ?, 1, ?)', ['Keep', 'x', time(), time()]);
        $otherTrash = (int) $db->insertId();
    }
    $left = (int) $db->fetchColumn('SELECT id FROM phpretro_minimail WHERE recipient_id = 1 LIMIT 1');
    if ($left > 0) { endpoint('minimail_deleteMessage.php', ['messageId' => $left, 'label' => 'inbox']); }
    endpoint('minimail_emptyTrash.php');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_minimail WHERE recipient_id = 1 AND deleted = 1') === 0, 'Empty trash purges own deleted mail');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_minimail WHERE id = ?', [$otherTrash]) === 1, 'Empty trash is recipient-scoped');

    asUser(2);
    check(endpoint('minimail_loadMessage.php', [], ['messageId' => $otherTrash])[1] === 200, 'Owner can still open remaining mail');
    check(endpoint('minimail_deleteMessage.php', ['messageId' => '99999'])[1] === 404, 'Missing message 404');
    $preview = endpoint('minimail_preview.php', ['body' => '<b>hi</b>']);
    check(str_contains($preview[0], htmlspecialchars('<b>hi</b>', ENT_COMPAT, 'UTF-8')), 'Preview escapes body');

    check((int) $db->fetchColumn("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ('messenger_messages','messenger_members','messenger_offline')") === 0, 'PolarIS messenger tables were never created for this feature');

    echo "PASS: $assertions assertions; website minimail on disposable MariaDB.\n";
} finally {
    restore_error_handler();
    $admin->exec('DROP DATABASE `'.$testName.'`');
    putenv('DB_DSN='.$sourceDsn);
}
