<?php
/** Run with DB_DSN/DB_USER/DB_PASS for a MariaDB account allowed to create a scratch DB.
 * Uses only CREATE definitions from the checked-in schema, never the source seed data.
 * The application database is never selected by the test connection.
 */
$root = dirname(__DIR__);
require_once $root.'/includes/config.php';
$sourceDsn = getenv('DB_DSN') ?: '';
if (!str_starts_with($sourceDsn, 'mysql:')) { throw new RuntimeException('Set DB_DSN to a MySQL DSN.'); }
$testName = 'phpretro_phase6_batch3_test_'.bin2hex(random_bytes(6));
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
    $tables = ['users', 'users_settings', 'rooms', 'guilds'];
    foreach ($tables as $table) {
        if (!preg_match('/CREATE TABLE IF NOT EXISTS `'.preg_quote($table, '/').'` \(.*?\) ENGINE=.*?;/s', $schema, $match)) {
            throw new RuntimeException('Missing verified table '.$table);
        }
        $db->execute($match[0]);
    }
    foreach (range(1, 8) as $id) {
        $name = $id === 2 ? 'Bob<script>' : 'User'.$id;
        $db->execute('INSERT INTO users (id, username, password, account_created, ip_register, ip_current, credits) VALUES (?, ?, ?, ?, ?, ?, ?)', [$id, $name, '', 100, '127.0.0.1', '127.0.0.1', 2500]);
    }
    $db->execute('INSERT INTO users_settings (user_id, tags) VALUES (1, ?), (2, ?)', ['music;games;', 'music;']);
    $db->execute('INSERT INTO rooms (id, owner_id, owner_name, name, description) VALUES (1, 1, ?, ?, ?), (2, 2, ?, ?, ?)', ['User1', '<script>Room', 'A room', 'Bob<script>', 'Quiet', '']);
    $db->execute('INSERT INTO guilds (id, user_id, name, date_created) VALUES (1, 1, ?, ?), (2, 2, ?, ?)', ['Guild<script>', 100, 'Crew', 100]);
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
    function escaped(string $value): string {
        return htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
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

    check(endpoint('myhabbo_tag_add.php', ['accountId' => '1', 'tagName' => 'art'])[0] === 'valid', 'Own tag inserted');
    check($db->fetchColumn('SELECT tags FROM users_settings WHERE user_id = 1') === 'music;games;art', 'Tags persist as Polaris semicolon list');
    check(endpoint('myhabbo_tag_add.php', ['accountId' => '1', 'tagName' => 'ART'])[0] === 'invalidtag', 'Duplicate tag is case-insensitive');
    check(endpoint('myhabbo_tag_add.php', ['accountId' => '2', 'tagName' => 'art'])[0] === 'invalidtag', 'Foreign accountId rejected');
    check($db->fetchColumn('SELECT tags FROM users_settings WHERE user_id = 2') === 'music;', 'Other user tags unchanged');
    foreach (['', str_repeat('x', 21), 'bad;tag', "' OR 1=1 --", 'hello world!', '<script>'] as $tag) {
        check(endpoint('myhabbo_tag_add.php', ['accountId' => '1', 'tagName' => $tag])[0] === 'invalidtag', 'Rejected tag '.substr($tag, 0, 12));
    }
    check($db->fetchColumn('SELECT tags FROM users_settings WHERE user_id = 1') === 'music;games;art', 'Invalid tags never written');
    check(endpoint('myhabbo_tag_add.php', ['accountId' => '1', 'tagName' => ['art']])[0] === 'invalidtag', 'Array tag rejected');
    asUser(3);
    check(endpoint('myhabbo_tag_add.php', ['accountId' => '3', 'tagName' => 'solo'])[0] === 'invalidtag', 'Missing settings row is not invented');
    asUser(1);

    $packed = implode(';', array_fill(0, 12, str_repeat('a', 20)));
    $db->execute('UPDATE users_settings SET tags = ? WHERE user_id = 1', [$packed]);
    check(endpoint('myhabbo_tag_add.php', ['accountId' => '1', 'tagName' => str_repeat('b', 20)])[0] === 'invalidtag', 'Packed tags respect varchar(255)');
    $db->execute('UPDATE users_settings SET tags = ? WHERE user_id = 1', [implode(';', array_map(static fn(int $n): string => 't'.$n, range(1, 20)))]);
    check(endpoint('myhabbo_tag_add.php', ['accountId' => '1', 'tagName' => 'extra'])[0] === 'invalidtag', 'Legacy 20-tag web limit');
    $db->execute('UPDATE users_settings SET tags = ? WHERE user_id = 1', ['music;games;art']);

    $list = endpoint('myhabbo_tag_list.php')[0];
    check(str_contains($list, 'id="profile-tags-container"'), 'Tag list DOM retained');
    check(str_contains($list, 'class="tag"') && str_contains($list, 'music'), 'Own tags rendered');
    check(str_contains($list, 'class="tag-delete-link"'), 'Own tag delete control rendered');
    $removed = endpoint('myhabbo_tag_remove.php', ['accountId' => '1', 'tagName' => 'games'])[0];
    check(str_contains($removed, 'music') && str_contains($removed, 'art') && !str_contains($removed, 'games'), 'Removed tag omitted from list');
    check($db->fetchColumn('SELECT tags FROM users_settings WHERE user_id = 1') === 'music;art', 'Removed tag deleted from settings');
    endpoint('myhabbo_tag_remove.php', ['accountId' => '2', 'tagName' => 'music']);
    check($db->fetchColumn('SELECT tags FROM users_settings WHERE user_id = 2') === 'music;', 'Foreign remove is a no-op');
    endpoint('myhabbo_tag_remove.php', ['accountId' => '1', 'tagName' => 'missing']);
    check($db->fetchColumn('SELECT tags FROM users_settings WHERE user_id = 1') === 'music;art', 'Missing tag remove is a no-op');
    endpoint('myhabbo_tag_remove.php', ['accountId' => '1', 'tagName' => 'MUSIC']);
    check($db->fetchColumn('SELECT tags FROM users_settings WHERE user_id = 1') === 'art', 'Remove is case-insensitive');
    $emptyTags = endpoint('myhabbo_tag_remove.php', ['accountId' => '1', 'tagName' => 'art'])[0];
    check(str_contains($emptyTags, 'No tags.'), 'Empty tag list copy retained');
    check($db->fetchColumn('SELECT tags FROM users_settings WHERE user_id = 1') === '', 'Last tag removal persists empty string');

    $users = endpoint('myhabbo_linktool_search.php', [], ['query' => 'Bob', 'scope' => '1']);
    check($users[1] === 200 && str_contains($users[0], escaped('Bob<script>')), 'User linktool escapes names');
    check(str_contains($users[0], 'type="habbo"') && str_contains($users[0], 'value="2"'), 'User linktool uses native id');
    $rooms = endpoint('myhabbo_linktool_search.php', [], ['query' => '<script>Room', 'scope' => '2']);
    check(str_contains($rooms[0], escaped('<script>Room')) && str_contains($rooms[0], 'type="room"'), 'Room linktool escapes names');
    $groups = endpoint('myhabbo_linktool_search.php', [], ['query' => 'Guild', 'scope' => '3']);
    check(str_contains($groups[0], escaped('Guild<script>')) && str_contains($groups[0], 'type="group"'), 'Group linktool searches guilds.name');
    $wild = endpoint('myhabbo_linktool_search.php', [], ['query' => '%', 'scope' => '1'])[0];
    check(!str_contains($wild, 'User1') && !str_contains($wild, 'Bob'), 'Linktool wildcards stay literal');
    $injected = endpoint('myhabbo_linktool_search.php', [], ['query' => "' OR 1=1 --", 'scope' => '1'])[0];
    check(!str_contains($injected, 'User1'), 'Linktool injection stays data');
    $empty = endpoint('myhabbo_linktool_search.php', [], ['query' => '', 'scope' => '1'])[0];
    check(str_contains($empty, 'Click on link below') && !str_contains($empty, 'class="linktool-result"'), 'Empty query returns no rows');
    $badScope = endpoint('myhabbo_linktool_search.php', [], ['query' => 'User', 'scope' => '9'])[0];
    check(!str_contains($badScope, 'class="linktool-result"'), 'Scope allowlist');
    check(endpoint('myhabbo_linktool_search.php', [], ['query' => 'User', 'scope' => []])[1] === 200, 'Malformed scope rejected without error');

    $blocked = [
        'groups_widgets.php',
        'myhabbo_guestbook_configure.php',
        'myhabbo_noteeditor_place.php',
        'myhabbo_rating_rate.php',
        'myhabbo_rating_reset_ratings.php',
        'myhabbo_sticker_place_sticker.php',
        'myhabbo_sticker_remove_sticker.php',
        'myhabbo_stickie_delete.php',
        'myhabbo_stickie_edit.php',
        'myhabbo_store_inventory.php',
        'myhabbo_store_inventory_items.php',
        'myhabbo_store_inventory_preview.php',
        'myhabbo_store_items.php',
        'myhabbo_store_main.php',
        'myhabbo_store_preview.php',
        'myhabbo_store_purchase.php',
        'myhabbo_store_purchase_confirm.php',
        'myhabbo_tag_addgrouptag.php',
        'myhabbo_tag_listgrouptags.php',
        'myhabbo_tag_removegrouptag.php',
        'myhabbo_traxplayer_select_song.php',
        'trax_song.php',
    ];
    $beforeCredits = $db->fetchAll('SELECT id, credits FROM users ORDER BY id');
    $beforeTags = $db->fetchAll('SELECT user_id, tags FROM users_settings ORDER BY user_id');
    foreach ($blocked as $file) {
        check(endpoint($file, ['widgetId' => "' OR 1=1", 'selectedId' => '1', 'message' => 'x', 'tagName' => 'crew', 'groupId' => '1'])[1] === 501, $file.' unavailable');
    }
    check($beforeCredits === $db->fetchAll('SELECT id, credits FROM users ORDER BY id'), 'Unavailable store does not debit credits');
    check($beforeTags === $db->fetchAll('SELECT user_id, tags FROM users_settings ORDER BY user_id'), 'Unavailable group tags do not write user tags');
    check(str_contains(file_get_contents($root.'/habblet/myhabbo_homes.php'), "\$page['no_ajax'] = true"), 'Homes session keeps legacy full-page GET/POST');
    check(str_contains(file_get_contents($root.'/habblet/trax_song.php'), "\$page['no_ajax'] = true"), 'Trax song URL is not an XHR habblet');
    echo "Batch 3: {$assertions} assertions passed.\n";
} finally {
    restore_error_handler();
    $admin->exec('DROP DATABASE `'.$testName.'`');
    putenv('DB_DSN='.$sourceDsn);
}
