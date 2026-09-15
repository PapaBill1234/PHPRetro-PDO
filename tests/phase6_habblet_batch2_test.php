<?php
/** Run with DB_DSN/DB_USER/DB_PASS for a MariaDB account allowed to create a scratch DB.
 * Uses only CREATE definitions from the checked-in schema, never the source seed data.
 * The application database is never selected by the test connection.
 */
$root = dirname(__DIR__);
require_once $root.'/includes/config.php';
$sourceDsn = getenv('DB_DSN') ?: '';
if (!str_starts_with($sourceDsn, 'mysql:')) { throw new RuntimeException('Set DB_DSN to a MySQL DSN.'); }
$testName = 'phpretro_phase6_batch2_test_'.bin2hex(random_bytes(6));
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
    $tables = ['users', 'users_settings', 'users_badges', 'guilds', 'guilds_members', 'guilds_forums_threads', 'guilds_forums_comments', 'guild_forum_views', 'rooms', 'items', 'room_rights'];
    foreach ($tables as $table) {
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
    foreach (range(1, 30) as $id) {
        $db->execute('INSERT INTO users (id, username, password, account_created, ip_register, ip_current, mail_verified) VALUES (?, ?, ?, ?, ?, ?, ?)', [$id, $id === 2 ? 'Admin<script>' : 'User'.$id, '', 100, '127.0.0.1', '127.0.0.1', '1']);
        $db->execute('INSERT INTO users_settings (user_id) VALUES (?)', [$id]);
    }
    foreach ([1, 2, 3] as $id) {
        $db->execute('INSERT INTO guilds (id, user_id, name, date_created, forum, read_forum, post_messages, post_threads) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [$id, $id === 2 ? 6 : 1, $id === 1 ? 'Guild<script>' : 'Guild'.$id, 100, '1', 'MEMBERS', 'MEMBERS', 'MEMBERS']);
    }
    foreach ([[1,1,0], [1,2,1], [1,3,2], [1,4,3], [1,5,4], [2,6,0]] as $m) {
        $db->execute('INSERT INTO guilds_members (guild_id, user_id, level_id, member_since) VALUES (?, ?, ?, ?)', [...$m, 100]);
    }
    chdir($root);
    $input = new HoloInput();
    // Load the real language files through HoloLocale, with the required setting.
    $settings = new HoloSettings();
    $settings->cache['site_capcha'] = '0';
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
        parse_str(http_build_query($post), $_POST);
        $_GET = $get; $page = ['allow_guests' => false];
        $lang = new HoloLocale();
        http_response_code(200);
        ob_start();
        try { include $root.'/habblet/'.$name; return [ob_get_contents(), http_response_code()]; }
        finally { ob_end_clean(); }
    }
    function asUser(int $id): void { global $user; $user->id = $id; }
    function callAction(string $action, array $post = []): array {
        return endpoint('groups_actions_'.$action.'.php', ['groupId' => 1, ...$post]);
    }
    function forumAction(string $action, array $post = []): array {
        return endpoint('discussions_actions_'.$action.'.php', ['groupId' => 1, ...$post]);
    }
    function memberAction(string $action, string $targets = '4'): array {
        return endpoint('myhabbo_groups_batch_'.$action.'.php', ['groupId' => 1, 'targetIds' => $targets]);
    }
    // Missing mappings must not charge, write layouts, aliases or incompatible badges.
    foreach (['show_badge_editor','update_group_badge'] as $action) {
        check(callAction($action)[1] === 501, $action.' unavailable explicitly');
    }
    foreach (['startEditingSession','saveEditingSession','cancelEditingSession'] as $action) {
        check(str_contains(file_get_contents($root.'/habblet/groups_actions_'.$action.'.php'), "\$page['no_ajax'] = true"), $action.' keeps legacy full-page GET/POST');
    }
    check(callAction('startEditingSession')[1] === 302, 'startEditingSession is a website edit session');
    $saved = callAction('saveEditingSession');
    check($saved[1] === 200 && str_contains($saved[0], 'waitAndGo'), 'saveEditingSession persists layout then waitAndGo');
    check(callAction('cancelEditingSession')[1] === 302, 'cancelEditingSession is a website edit session');
    check(endpoint('grouppurchase_purchase_ajax.php', ['name' => 'Purchase', 'description' => 'Test'])[1] === 403, 'Purchase without club is rejected');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM guilds') === 3, 'Failed purchase creates no group');
    check(callAction('group_settings')[1] === 200, 'Owner settings form renders');
    check(str_contains(callAction('group_settings')[0], 'id="group-settings-form"'), 'Group settings DOM retained');
    $settingsHtml = callAction('group_settings')[0];
    check(str_contains($settingsHtml, '50,000 member limit'), 'Native 50,000 group limit shown');
    check(!str_contains($settingsHtml, '5000 member limit'), 'Legacy 5,000 label is not shown');
    check(!str_contains($settingsHtml, 'No membership limit'), 'Legacy unlimited membership claim is not shown');
    check(str_contains($settingsHtml, 'group-type-large'), 'Large group CSS class retained');
    check(str_contains(callAction('confirm_delete_group')[0], 'Guild&lt;script&gt;'), 'Delete confirmation escaped');
    check(callAction('confirm_select_favorite', ['targetAccountId' => 1])[1] === 200, 'Favorite confirmation');
    check(callAction('select_favorite', ['targetAccountId' => 1])[0] === 'OK', 'Favorite success contract');
    check((int) $db->fetchColumn('SELECT guild_id FROM users_settings WHERE user_id = 1') === 1, 'Favorite stored in settings');
    callAction('deselect_favorite', ['targetAccountId' => 1, 'groupId' => 2]);
    check((int) $db->fetchColumn('SELECT guild_id FROM users_settings WHERE user_id = 1') === 1, 'Unrelated favorite preserved');
    callAction('deselect_favorite', ['targetAccountId' => 1]);
    check((int) $db->fetchColumn('SELECT guild_id FROM users_settings WHERE user_id = 1') === 0, 'Deselect scoped');
    check(callAction('select_favorite', ['targetAccountId' => 2])[1] === 403, 'Cannot change another favorite');
    check(callAction('leave')[1] === 403, 'Owner cannot leave');
    foreach (['accept','decline','give_rights','remove','revoke_rights'] as $action) {
        check(memberAction('confirm_'.$action)[1] === 200, 'Confirmation '.$action);
    }
    check(memberAction('accept')[0] === 'OK', 'Accept pending');
    check((int) $db->fetchColumn('SELECT level_id FROM guilds_members WHERE guild_id = 1 AND user_id = 4') === 2, 'Accepted level is native member');
    check(memberAction('give_rights')[0] === 'OK', 'Give admin');
    check((int) $db->fetchColumn('SELECT level_id FROM guilds_members WHERE guild_id = 1 AND user_id = 4') === 1, 'Native admin level');
    check(memberAction('revoke_rights')[0] === 'OK', 'Revoke admin');
    check(memberAction('remove', '1')[1] === 403, 'Protect owner');
    check(memberAction('remove', '4,999')[1] === 409, 'Invalid multi-target action fails');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM guilds_members WHERE guild_id = 1 AND user_id = 4') === 1, 'Multi-target failure rolls back first target');
    check(memberAction('remove', '4 OR 1=1')[1] === 400, 'Target injection rejected');
    $db->execute('UPDATE users_settings SET guild_id = 1 WHERE user_id = 4');
    check(memberAction('remove')[0] === 'OK', 'Remove member');
    check((int) $db->fetchColumn('SELECT guild_id FROM users_settings WHERE user_id = 4') === 0, 'Removal clears only matching favorite');
    asUser(2);
    check(memberAction('give_rights', '3')[1] === 403, 'Admin cannot grant admin');
    check(callAction('group_settings')[1] === 403, 'Admin cannot edit owner settings');
    check(callAction('delete_group')[1] === 403, 'Admin cannot delete guild');
    check(memberAction('remove', '1')[1] === 403, 'Admin cannot remove owner');
    asUser(3);
    check(memberAction('accept')[1] === 403, 'Native member rank does not become legacy admin');
    check(endpoint('myhabbo_groups_memberlist.php', ['groupId' => 1, 'pending' => 'true'])[1] === 403, 'Pending list protected');
    asUser(5);
    check(callAction('join')[1] === 409, 'Blocked member cannot rejoin');
    check(callAction('leave')[1] === 403, 'Blocked member cannot erase block by leaving');
    check(callAction('select_favorite', ['targetAccountId' => 5])[1] === 403, 'Blocked rank not favorite eligible');
    asUser(4);
    check(callAction('join')[1] === 200, 'Open group join');
    check(callAction('join')[1] === 409, 'Duplicate join rejected');
    check(callAction('leave')[1] === 200, 'Member leave');
    $db->execute('UPDATE guilds SET state = 1 WHERE id = 1');
    check(callAction('join')[1] === 200, 'Exclusive group request');
    check((int) $db->fetchColumn('SELECT level_id FROM guilds_members WHERE guild_id = 1 AND user_id = 4') === 3, 'Native pending level');
    check(callAction('select_favorite', ['targetAccountId' => 4])[1] === 403, 'Pending not favorite eligible');
    asUser(1);
    check(memberAction('decline')[0] === 'OK', 'Decline only pending request');
    $db->execute('UPDATE guilds SET state = 2 WHERE id = 1');
    asUser(4); check(callAction('join')[1] === 403, 'Closed group rejects join');
    $db->execute('UPDATE guilds SET state = 0 WHERE id = 1');
    asUser(1);
    $form = ['name' => "Guild's name", 'description' => 'A & B', 'type' => 0, 'url' => '', 'forumType' => 1, 'newTopicPermission' => 1, 'roomId' => 0];
    check(callAction('update_group_settings', $form)[1] === 200, 'Settings save');
    check($db->fetchColumn('SELECT name FROM guilds WHERE id = 1') === "Guild's name", 'Apostrophe stored literally');
    check(callAction('check_group_url', ['url' => 'cool-name'])[1] === 200, 'Owner can check a group URL');
    check(str_starts_with(callAction('check_group_url', ['url' => 'cool-name'])[0], 'ERROR ') === false, 'Valid URL is not an ERROR payload');
    check(str_starts_with(callAction('check_group_url', ['url' => 'actions'])[0], 'ERROR '), 'Reserved URL is ERROR');
    check(callAction('update_group_settings', [...$form, 'url' => 'cool-name'])[1] === 200, 'Website alias is saved');
    check($db->fetchColumn('SELECT alias FROM phpretro_group_url_aliases WHERE guild_id = 1') === 'cool-name', 'Alias stored in phpretro table');
    check(callAction('update_group_settings', [...$form, 'url' => 'other-name'])[1] === 200, 'Existing alias cannot be altered');
    check($db->fetchColumn('SELECT alias FROM phpretro_group_url_aliases WHERE guild_id = 1') === 'cool-name', 'Original alias kept');
    check(callAction('update_group_settings', [...$form, 'roomId' => 999])[1] === 501, 'No guessed room transfer');
    check(callAction('update_group_settings', [...$form, 'description' => str_repeat('a', 251)])[1] === 400, 'Actual description bound');
    check(callAction('update_group_settings', [...$form, 'name' => '😀'])[1] === 400, 'Latin1 incompatibility rejected');
    $db->execute("UPDATE guilds SET post_messages = 'OWNER' WHERE id = 1");
    callAction('update_group_settings', $form);
    check($db->fetchColumn('SELECT post_messages FROM guilds WHERE id = 1') === 'OWNER', 'Topic setting does not overwrite message permission');
    $db->execute("UPDATE guilds SET post_messages = 'MEMBERS' WHERE id = 1");
    $db->execute("UPDATE guilds SET read_forum = 'ADMINS' WHERE id = 1");
    check(callAction('group_settings')[1] === 501, 'Unrepresentable native settings flagged');
    $db->execute("UPDATE guilds SET read_forum = 'MEMBERS' WHERE id = 1");
    $out = endpoint('myhabbo_groups_memberlist.php', ['groupId' => 1, 'searchString' => 'Admin'])[0];
    check(str_contains($out, 'Admin&lt;script&gt;') && str_contains($out, 'administrator_icon.gif'), 'Member list escapes and maps admin');
    check(!str_contains(endpoint('myhabbo_groups_memberlist.php', ['groupId' => 1, 'searchString' => "' OR 1=1"])[0], 'Admin&lt;script&gt;'), 'Member search injection stays literal');
    check(str_contains(endpoint('myhabbo_groups_groupinfo.php', ['groupId' => 1, 'ownerId' => 1])[0], 'groups-info-basic'), 'Group info DOM');
    check(endpoint('myhabbo_avatarlist_membersearchpaging.php', ['widgetId' => 999])[1] === 400, 'Widget ID does not infer group identity');
    check(str_contains(endpoint('myhabbo_avatarlist_membersearchpaging.php', ['_groupspage.requested.group' => 1, 'widgetId' => 99])[0], 'avatar-list-99-1'), 'Widget uses actual JS group parameter');
    check(endpoint('myhabbo_groups_memberlist.php', ['groupId' => 1])[1] === 200, 'Full member list owner label');
    foreach (range(7, 30) as $memberId) {
        $db->execute('INSERT INTO guilds_members (guild_id, user_id, level_id, member_since) VALUES (1, ?, 3, 100)', [$memberId]);
    }
    $pendingHtml = endpoint('myhabbo_groups_memberlist.php', ['groupId' => 1, 'pending' => 'true', 'pageNumber' => 2])[0];
    check(str_contains($pendingHtml, 'id="pageNumberMemberList" value="2"') && str_contains($pendingHtml, 'id="totalPagesMemberList" value="2"'), 'Pending list uses 12-row pages consistently');
    check(endpoint('myhabbo_groups_memberlist.php', ['groupId' => 1, 'pending' => 'true', 'searchString' => 'no match'])[1] === 200, 'Empty pending list has defined paging');
    $db->execute('UPDATE guilds_members SET level_id = 2 WHERE guild_id = 1 AND user_id >= 7');
    check(str_contains(endpoint('myhabbo_avatarlist_membersearchpaging.php', ['_groupspage.requested.group' => 1, 'widgetId' => 99, 'pageNumber' => 2])[0], 'id="pageNumber" value="2"'), 'Widget native LIMIT/OFFSET second page');
    check(endpoint('myhabbo_avatarlist_membersearchpaging.php', ['_groupspage.requested.group' => 1, 'searchString' => 'no match'])[1] === 200, 'Empty widget list');
    check(forumAction('newtopic')[1] === 200, 'New-topic form');
    check(forumAction('previewtopic', ['topicName' => 'Preview', 'message' => '[b]bold[/b] <script>x</script>'])[1] === 200, 'Topic preview');
    $post = ['topicName' => "Topic's title", 'message' => "[b]Hello[/b] ' \ test <script>bad</script>", 'page' => 1];
    $result = forumAction('savetopic', $post);
    check($result[1] === 200 && preg_match('~/discussions/(\d+)/id$~', $result[0], $match) === 1, 'Create topic returns numeric group topic URL');
    $topic = (int) $match[1];
    $first = (int) $db->fetchColumn('SELECT id FROM guilds_forums_comments WHERE thread_id = ?', [$topic]);
    check((int) $db->fetchColumn('SELECT posts_count FROM guilds_forums_threads WHERE id = ?', [$topic]) === 1, 'First post count');
    check((int) $db->fetchColumn('SELECT forums_post_count FROM users_settings WHERE user_id = 1') === 1, 'Account post counter');
    check($db->fetchColumn('SELECT message FROM guilds_forums_comments WHERE id = ?', [$first]) === $post['message'], 'Message preserved literally');
    check(forumAction('previewpost', ['topicId' => $topic, 'message' => 'Preview reply'])[1] === 200, 'Reply preview');
    check(forumAction('opentopicsettings', ['topicId' => $topic])[1] === 200, 'Topic settings form');
    asUser(3);
    $db->execute("UPDATE users SET online = '1' WHERE id = 3");
    $db->execute("UPDATE users_settings SET hide_online = '1' WHERE user_id = 3");
    $hiddenAuthor = forumAction('savepost', ['topicId' => $topic, 'message' => 'Hidden online status', 'page' => 1]);
    check($hiddenAuthor[1] === 200 && str_contains($hiddenAuthor[0], 'habbo_offline.gif') && !str_contains($hiddenAuthor[0], 'habbo_online_anim.gif'), 'Forum author honors hide_online');
    $db->execute("UPDATE users_settings SET hide_online = '0' WHERE user_id = 3");
    $reply = forumAction('savepost', ['topicId' => $topic, 'message' => 'Member reply', 'page' => -1]);
    check($reply[1] === 200 && str_contains($reply[0], 'group-postlist-list'), 'Reply renders original list DOM');
    check(str_contains($reply[0], '<b>Hello</b>') && !str_contains($reply[0], '<script>bad</script>'), 'BBCode preserved with raw HTML escaped');
    $second = (int) $db->fetchColumn('SELECT MAX(id) FROM guilds_forums_comments WHERE thread_id = ?', [$topic]);
    check(forumAction('updatepost', ['topicId' => $topic, 'postId' => $second, 'message' => 'Edited by author'])[1] === 200, 'Member edits own post');
    check(forumAction('updatepost', ['topicId' => $topic, 'postId' => $first, 'message' => 'Attack'])[1] === 403, 'Member cannot edit another post');
    check(forumAction('deletetopic', ['topicId' => $topic])[1] === 403, 'Member cannot delete topic');
    check(forumAction('opentopicsettings', ['topicId' => $topic])[1] === 403, 'Non-opener cannot edit topic');
    asUser(1);
    check(forumAction('deletepost', ['topicId' => $topic, 'postId' => $second])[1] === 200, 'Guild-admin hide uses state 10');
    check((int) $db->fetchColumn('SELECT state FROM guilds_forums_comments WHERE id = ?', [$second]) === 10, 'Hidden comment state is 10');
    check((int) $db->fetchColumn('SELECT admin_id FROM guilds_forums_comments WHERE id = ?', [$second]) === 1, 'Hide records admin_id');
    check(forumAction('savetopicsettings', ['topicId' => $topic, 'topicName' => 'Closed topic', 'topicClosed' => 1, 'topicSticky' => 1])[1] === 200, 'Moderator settings write');
    check(forumAction('savepost', ['topicId' => $topic, 'message' => 'Locked reply'])[1] === 403, 'Locked topic rejects replies');
    check((int) $db->fetchColumn('SELECT pinned FROM guilds_forums_threads WHERE id = ?', [$topic]) === 1, 'Native pinned mapping');
    forumAction('savetopicsettings', ['topicId' => $topic, 'topicName' => 'Open topic', 'topicClosed' => 0, 'topicSticky' => 0]);
    $settings->cache['site_capcha'] = '1';
    check(forumAction('savepost', ['topicId' => $topic, 'message' => 'Bad captcha'])[1] === 400, 'Captcha enforced');
    $_SESSION['register-captcha-bubble'] = 'AbCd';
    check(forumAction('savepost', ['topicId' => $topic, 'message' => 'Good captcha', 'captcha' => 'abcd'])[1] === 200, 'Captcha case-insensitive success');
    check(!isset($_SESSION['register-captcha-bubble']), 'Captcha consumed');
    $settings->cache['site_capcha'] = '0';
    asUser(6);
    $other = forumAction('savetopic', ['groupId' => 2, 'topicName' => 'Other guild', 'message' => 'Private other content']);
    preg_match('~/discussions/(\d+)/id$~', $other[0], $match); $otherTopic = (int) $match[1];
    $otherPost = (int) $db->fetchColumn('SELECT id FROM guilds_forums_comments WHERE thread_id = ?', [$otherTopic]);
    asUser(1);
    foreach (['savepost','updatepost','savetopicsettings','deletepost','deletetopic','opentopicsettings','previewpost'] as $action) {
        check(forumAction($action, ['topicId' => $otherTopic, 'postId' => $otherPost, 'message' => 'Cross guild', 'topicName' => 'Cross', 'topicClosed' => 0, 'topicSticky' => 0])[1] === 404, 'Cross-group denied: '.$action);
    }
    check(forumAction('updatepost', ['topicId' => $topic, 'postId' => $otherPost, 'message' => 'Cross post'])[1] === 404, 'Post scoped to topic');
    $db->execute('UPDATE guilds_forums_comments SET state = 10, message = ? WHERE id = ?', ['HIDDEN SECRET', $second]);
    $html = forumAction('savepost', ['topicId' => $topic, 'message' => 'Visible reply', 'page' => 1])[0];
    check(!str_contains($html, 'HIDDEN SECRET'), 'Hidden comment absent from display and hidden inputs');
    $db->execute('UPDATE guilds_forums_threads SET state = 20 WHERE id = ?', [$topic]);
    check(forumAction('previewpost', ['topicId' => $topic, 'message' => 'No leak'])[1] === 403, 'Hidden topic protected');
    $db->execute('UPDATE guilds_forums_threads SET state = 1 WHERE id = ?', [$topic]);
    check(forumAction('previewpost', ['topicId' => $topic, 'message' => 'Restored'])[1] === 200, 'Restored topic visible');
    foreach (['EVERYONE' => [1,2,3,4,5], 'MEMBERS' => [1,2,3], 'ADMINS' => [1,2], 'OWNER' => [1]] as $permission => $allowedIds) {
        $db->execute("UPDATE guilds SET read_forum = 'EVERYONE', post_threads = ?, post_messages = ? WHERE id = 1", [$permission, $permission]);
        foreach ([1,2,3,4,5] as $actorId) {
            asUser($actorId);
            $status = in_array($actorId, $allowedIds, true) ? 200 : 403;
            check(forumAction('newtopic')[1] === $status, $permission.' new topics for actor '.$actorId);
            check(forumAction('previewpost', ['topicId' => $topic, 'message' => 'Permission preview'])[1] === $status, $permission.' replies for actor '.$actorId);
        }
    }
    asUser(1);
    $db->execute("UPDATE guilds SET read_forum = 'MEMBERS', post_threads = 'MEMBERS', post_messages = 'MEMBERS', mod_forum = 'OWNER' WHERE id = 1");
    asUser(2);
    check(forumAction('deletetopic', ['topicId' => $topic])[1] === 403, 'Owner-only moderation excludes admin');
    asUser(1);
    $db->execute("UPDATE users SET mail_verified = '0' WHERE id = 1");
    check(forumAction('savepost', ['topicId' => $topic, 'message' => 'Unverified'])[1] === 403, 'Fresh verified-email gate');
    $db->execute("UPDATE users SET mail_verified = '1' WHERE id = 1");
    check(forumAction('savepost', ['topicId' => $topic, 'message' => []])[1] === 400, 'Array message rejected');
    foreach (range(1, 12) as $number) { forumAction('savepost', ['topicId' => $topic, 'message' => 'Pagination '.$number, 'page' => -1]); }
    $pageHtml = forumAction('savepost', ['topicId' => $topic, 'message' => 'Last page', 'page' => -1])[0];
    check(str_contains($pageHtml, 'id="current-page" value="2"'), 'Forum last-page pagination');
    check(!str_contains($pageHtml, "Topic's title"), 'First-page content not loaded into second page');
    $pageHtml = forumAction('savepost', ['topicId' => $topic, 'message' => 'Clamped page', 'page' => -9])[0];
    check(str_contains($pageHtml, 'id="current-page" value="1"'), 'Negative forum page clamped');
    foreach (['[url=javascript:alert(1)]X[/url]', "[room=1');alert(1);//]X[/room]", '[color=red;background:url(x)]X[/color]'] as $attack) {
        $safe = habbletForumText($attack);
        check(!str_contains($safe, 'javascript:') && !str_contains($safe, 'alert(') && !str_contains($safe, 'background:'), 'Unsafe BBCode parameter removed');
    }
    check(forumAction('deletetopic', ['topicId' => $topic])[0] === 'SUCCESS', 'Topic deletion success contract');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM guilds_forums_comments WHERE thread_id = ?', [$topic]) === 0, 'Topic comments removed');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM guilds_forums_comments WHERE thread_id = ?', [$otherTopic]) === 1, 'Other group comments preserved');
    $db->execute('INSERT INTO rooms (id, guild_id, owner_id, name, description) VALUES (1, 1, 1, ?, ?)', ['Room', '']);
    $db->execute('INSERT INTO items (id, guild_id) VALUES (1, 1), (2, 2)');
    $db->execute('INSERT INTO guild_forum_views (user_id, guild_id, timestamp) VALUES (1, 1, 1)');
    $db->execute('UPDATE users_settings SET guild_id = 1 WHERE user_id = 3');
    check(callAction('delete_group')[1] === 200, 'Owner deletes guild');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM guilds WHERE id = 1') === 0, 'Guild removed');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM guilds_members WHERE guild_id = 1') === 0, 'Guild membership cleanup');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM guild_forum_views WHERE guild_id = 1') === 0, 'Forum view cleanup');
    check((int) $db->fetchColumn('SELECT guild_id FROM rooms WHERE id = 1') === 0, 'Room link cleared');
    check((int) $db->fetchColumn('SELECT guild_id FROM items WHERE id = 1') === 0, 'Furniture link cleared');
    check((int) $db->fetchColumn('SELECT guild_id FROM items WHERE id = 2') === 2, 'Other furniture link preserved');
    check((int) $db->fetchColumn('SELECT guild_id FROM users_settings WHERE user_id = 3') === 0, 'Favorite cleanup');
    check(callAction('join', ['groupId' => '2 OR 1=1'])[1] === 400, 'Group ID injection rejected');
    check(callAction('join', ['groupId' => []])[1] === 400, 'Array ID rejected');
    echo "Batch 2: {$assertions} assertions passed.\n";
} finally {
    restore_error_handler();
    $admin->exec('DROP DATABASE `'.$testName.'`');
    putenv('DB_DSN='.$sourceDsn);
}
