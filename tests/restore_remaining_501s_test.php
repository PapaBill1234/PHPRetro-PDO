<?php
$root = dirname(__DIR__);
require_once $root.'/includes/config.php';
$sourceDsn = getenv('DB_DSN') ?: '';
if (!str_starts_with($sourceDsn, 'mysql:')) { throw new RuntimeException('Set DB_DSN to a MySQL DSN.'); }
$testName = 'phpretro_restore_remaining_501s_'.bin2hex(random_bytes(6));
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
    foreach (['users', 'users_settings', 'messenger_friendships', 'rooms', 'guilds', 'guilds_members'] as $table) {
        if (!preg_match('/CREATE TABLE IF NOT EXISTS `'.preg_quote($table, '/').'` \(.*?\) ENGINE=.*?;/s', $schema, $match)) {
            throw new RuntimeException('Missing verified table '.$table);
        }
        $db->execute($match[0]);
    }
    $custom = file_get_contents($root.'/migrations/001_custom_tables.sql');
    foreach (['phpretro_collectibles', 'phpretro_myhabbo_layouts', 'phpretro_myhabbo_guestbook', 'phpretro_transactions'] as $table) {
        if (!preg_match('/CREATE TABLE IF NOT EXISTS `'.$table.'` \(.*?\) ENGINE=.*?;/s', $custom, $match)) {
            throw new RuntimeException('Missing custom table '.$table);
        }
        $db->execute($match[0]);
    }
    foreach (['003_web_minimail.sql', '004_web_homes.sql', '005_web_group_urls.sql', '006_restore_remaining.sql', '007_restore_remaining_501s.sql', '009_guild_tags.sql'] as $file) {
        $migration = preg_replace('/^\s*--.*$/m', '', file_get_contents($root.'/migrations/'.$file)) ?? '';
        foreach (array_filter(array_map('trim', explode(';', $migration))) as $sql) {
            if ($sql !== '') { $db->execute($sql); }
        }
    }
    foreach (range(1, 3) as $id) {
        $db->execute('INSERT INTO users (id, username, password, account_created, ip_register, ip_current, motto, look, credits, online) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$id, 'User'.$id, '', 100, '127.0.0.1', '127.0.0.1', 'motto', 'look', 50, '0']);
        $db->execute('INSERT INTO users_settings (user_id, club_expire_timestamp) VALUES (?, ?)', [$id, $id === 1 ? time() + 86400 : 0]);
    }
    $db->execute('INSERT INTO messenger_friendships (user_one_id, user_two_id) VALUES (1, 2), (2, 1)');
    $db->execute('INSERT INTO rooms (id, owner_id, owner_name, name, description, guild_id) VALUES (1, 1, ?, ?, ?, 0), (2, 1, ?, ?, ?, 0)', ['User1', 'Room One', '', 'User1', 'Room Two', '']);
    $db->execute('INSERT INTO guilds (id, user_id, name, date_created, room_id) VALUES (1, 1, ?, ?, 1)', ['Crew', 100]);
    $db->execute('INSERT INTO guilds_members (guild_id, user_id, level_id) VALUES (1, 1, 0), (1, 2, 2)');
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

    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_homes_catalogue') >= 1, 'Catalogue seed applied');
    check($db->fetchColumn('SELECT data FROM phpretro_homes_catalogue WHERE id = 109') === 'ratingwidget', 'Rating catalogue id 109');
    check($db->fetchColumn("SHOW COLUMNS FROM phpretro_myhabbo_layouts LIKE 'privacy'") !== false, 'Layouts privacy column exists');
    check($db->fetchColumn("SHOW COLUMNS FROM phpretro_myhabbo_layouts LIKE 'guild_id'") !== false, 'Layouts guild_id column exists');

    $rating = endpoint('myhabbo_widget_add.php', ['widget_key' => 'ratingwidget']);
    check($rating[1] === 200 && str_contains($rating[0], 'RatingWidget'), 'Rating widget renders');
    $ratingId = (int) $db->fetchColumn('SELECT id FROM phpretro_myhabbo_layouts WHERE widget_key = ? AND guild_id = 0', ['ratingwidget']);
    check($ratingId > 0, 'Rating layout stored');
    check(endpoint('myhabbo_rating_rate.php', [], ['ownerId' => '1', 'ratingId' => (string) $ratingId, 'givenRate' => '5'])[1] === 400, 'Owner cannot vote for self');
    asUser(2);
    $voted = endpoint('myhabbo_rating_rate.php', [], ['ownerId' => '1', 'ratingId' => (string) $ratingId, 'givenRate' => '5']);
    check($voted[1] === 200 && str_contains($voted[0], 'id="rating-main"'), 'Friend can rate 1-5');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_home_ratings WHERE profile_user_id = 1 AND rater_id = 2') === 1, 'Rating stored once');
    check((int) $db->fetchColumn('SELECT rating FROM phpretro_home_ratings WHERE profile_user_id = 1 AND rater_id = 2') === 5, 'Rating value stored');
    endpoint('myhabbo_rating_rate.php', [], ['ownerId' => '1', 'ratingId' => (string) $ratingId, 'givenRate' => '1']);
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_home_ratings WHERE profile_user_id = 1 AND rater_id = 2') === 1, 'Second vote is ignored');
    check((int) $db->fetchColumn('SELECT rating FROM phpretro_home_ratings WHERE profile_user_id = 1 AND rater_id = 2') === 5, 'Existing rating is not overwritten');
    asUser(3);
    check(endpoint('myhabbo_rating_rate.php', [], ['ownerId' => '1', 'ratingId' => (string) $ratingId, 'givenRate' => '4'])[1] === 200, 'Non-friend can rate a public home');
    asUser(1);
    $reset = endpoint('myhabbo_rating_reset_ratings.php', [], ['ownerId' => '1', 'ratingId' => (string) $ratingId]);
    check($reset[1] === 200, 'Owner can reset ratings');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_home_ratings WHERE profile_user_id = 1') === 0, 'Reset deletes votes');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_emulator_outbox WHERE event_type = ?', ['homes.ratings_reset']) === 1, 'Reset records outbox');

    $guestbook = endpoint('myhabbo_widget_add.php', ['widget_key' => 'guestbookwidget']);
    $guestbookId = (int) $db->fetchColumn('SELECT id FROM phpretro_myhabbo_layouts WHERE widget_key = ? AND guild_id = 0', ['guestbookwidget']);
    check($guestbookId > 0, 'Guestbook widget stored');
    $privacy = endpoint('myhabbo_guestbook_configure.php', ['widgetId' => (string) $guestbookId]);
    check($privacy[1] === 200 && str_contains($privacy[0], 'guestbook-type'), 'Configure returns original JS pulse');
    check($db->fetchColumn('SELECT privacy FROM phpretro_myhabbo_layouts WHERE id = ?', [$guestbookId]) === 'private', 'Privacy toggles to private');
    asUser(3);
    check(endpoint('myhabbo_guestbook_add.php', ['widgetId' => (string) $guestbookId, 'message' => 'nope'])[1] === 403, 'Non-friend cannot post to private guestbook');
    asUser(2);
    $friendPost = endpoint('myhabbo_guestbook_add.php', ['widgetId' => (string) $guestbookId, 'message' => 'hi friend']);
    check($friendPost[1] === 200 && str_contains($friendPost[0], 'hi friend'), 'Friend can post to private guestbook');
    asUser(1);
    endpoint('myhabbo_guestbook_configure.php', ['widgetId' => (string) $guestbookId]);
    check($db->fetchColumn('SELECT privacy FROM phpretro_myhabbo_layouts WHERE id = ?', [$guestbookId]) === 'public', 'Privacy toggles back to public');

    check(endpoint('habboclub_habboclub_reminder_remove.php')[1] === 200, 'Club reminder dismisses');
    check($db->fetchColumn('SELECT item_key FROM phpretro_feed_dismissals WHERE user_id = 1') === 'hc-reminder', 'Reminder key is hc-reminder');

    $main = endpoint('myhabbo_store_main.php');
    check($main[1] === 200 && str_contains($main[0], 'id="webstore-items"'), 'Store catalogue dialog renders');
    $confirm = endpoint('myhabbo_store_purchase_confirm.php', ['productId' => '116']);
    check(str_contains($confirm[0], 'signed out of the hotel'), 'Purchase confirm notes the offline credit debit');
    $buy = endpoint('myhabbo_store_purchase.php', ['selectedId' => '116']);
    check($buy[1] === 200 && str_contains($buy[0], 'OK'), 'Offline sticker purchase succeeds');
    check((int) $db->fetchColumn('SELECT credits FROM users WHERE id = 1') === 49, 'Store debit PolarIS credits while offline');
    check((int) $db->fetchColumn('SELECT COUNT(*) FROM phpretro_homes_items WHERE user_id = 1 AND catalogue_id = 116') === 1, 'Sticker lands in website inventory');
    check((int) $db->fetchColumn('SELECT amount FROM phpretro_transactions WHERE user_id = 1 AND type = ?', ['homes_store']) === -1, 'Store writes phpretro_transactions');
    $stickerId = (int) $db->fetchColumn('SELECT id FROM phpretro_homes_items WHERE user_id = 1 AND catalogue_id = 116');
    $place = endpoint('myhabbo_sticker_place_sticker.php', ['selectedStickerId' => (string) $stickerId, 'zindex' => '3']);
    check($place[1] === 200 && str_contains($place[0], 'id="sticker-'.$stickerId.'"'), 'Placed sticker HTML uses sticker-{id}');
    check((int) $db->fetchColumn('SELECT placed FROM phpretro_homes_items WHERE id = ?', [$stickerId]) === 1, 'Sticker marked placed');
    check(endpoint('myhabbo_sticker_remove_sticker.php', ['stickerId' => (string) $stickerId])[1] === 200, 'Sticker removed');
    check((int) $db->fetchColumn('SELECT placed FROM phpretro_homes_items WHERE id = ?', [$stickerId]) === 0, 'Removed sticker returns to inventory');

    $db->execute("UPDATE users SET online = '1' WHERE id = 1");
    $onlineBuy = endpoint('myhabbo_store_purchase.php', ['selectedId' => '117']);
    check($onlineBuy[1] === 409 && str_contains($onlineBuy[0], 'Leave the hotel first'), 'Online purchase is refused');
    check(str_contains($onlineBuy[0], 'id="webstore-confirm-cancel"'), 'Online purchase error keeps the cancel control');
    check((int) $db->fetchColumn('SELECT credits FROM users WHERE id = 1') === 49, 'Online refusal does not debit credits');
    $db->execute("UPDATE users SET online = '2' WHERE id = 1");
    check(endpoint('myhabbo_store_purchase.php', ['selectedId' => '117'])[1] === 409, 'Online=2 is also in-hotel');
    $db->execute("UPDATE users SET online = '0' WHERE id = 1");

    $notes = endpoint('myhabbo_store_purchase.php', ['selectedId' => '114']);
    check($notes[1] === 200, 'Note pack purchased');
    check((int) $db->fetchColumn("SELECT COUNT(*) FROM phpretro_homes_items WHERE user_id = 1 AND item_type = 'stickie'") === 5, 'Note pack grants amount 5');
    $note = endpoint('myhabbo_noteeditor_place.php', ['noteText' => 'Hello <b>note', 'skin' => '1']);
    check($note[1] === 200 && str_contains($note[0], 'stickie-'), 'Note place renders stickie HTML');
    check(str_contains($note[0], htmlspecialchars('Hello <b>note', ENT_COMPAT, 'UTF-8')), 'Note text is escaped');
    $noteId = (int) $db->fetchColumn("SELECT id FROM phpretro_homes_items WHERE user_id = 1 AND item_type = 'stickie' AND placed = 1");
    $edited = endpoint('myhabbo_stickie_edit.php', ['stickieId' => (string) $noteId, 'skinId' => '4']);
    check($edited[1] === 200 && str_contains($edited[0], 'n_skin_noteitskin'), 'Note skin updates');
    check(endpoint('myhabbo_stickie_delete.php', ['stickieId' => (string) $noteId])[1] === 200, 'Note deleted');
    check((int) $db->fetchColumn('SELECT placed FROM phpretro_homes_items WHERE id = ?', [$noteId]) === 0, 'Deleted note returns to inventory');

    $_SESSION['group_page_edit'] = 1;
    $groupWidget = endpoint('groups_widgets.php', ['widgetType' => 'guestbookwidget']);
    check($groupWidget[1] === 200 && str_contains($groupWidget[0], 'GuestbookWidget'), 'Group guestbook widget added');
    $groupGuestbookId = (int) $db->fetchColumn('SELECT id FROM phpretro_myhabbo_layouts WHERE guild_id = 1 AND widget_key = ?', ['guestbookwidget']);
    check($groupGuestbookId > 0, 'Group widget shares layouts table');
    check((int) $db->fetchColumn('SELECT user_id FROM phpretro_myhabbo_layouts WHERE id = ?', [$groupGuestbookId]) === 1, 'Group widget user_id is the guild owner');
    $info = endpoint('groups_widgets.php', ['widgetType' => 'groupinfowidget']);
    check($info[1] === 200 && str_contains($info[0], 'GroupInfoWidget'), 'Group info widget renders');
    check(str_contains($info[0], 'No tags.'), 'Empty group tags show No tags.');
    check(str_contains($info[0], 'id="profile-tag-list"') && str_contains($info[0], 'new GroupInfoWidget'), 'Group info binds tag widget');
    $members = endpoint('groups_widgets.php', ['widgetType' => 'memberwidget']);
    check($members[1] === 200 && str_contains($members[0], 'MemberWidget'), 'Members widget renders');
    endpoint('myhabbo_guestbook_configure.php', ['widgetId' => (string) $groupGuestbookId]);
    check($db->fetchColumn('SELECT privacy FROM phpretro_myhabbo_layouts WHERE id = ?', [$groupGuestbookId]) === 'private', 'Group guestbook privacy is members-only');
    asUser(3);
    check(endpoint('myhabbo_guestbook_add.php', ['widgetId' => (string) $groupGuestbookId, 'message' => 'outsider'])[1] === 403, 'Non-member cannot post to private group guestbook');
    asUser(2);
    $memberPost = endpoint('myhabbo_guestbook_add.php', ['widgetId' => (string) $groupGuestbookId, 'message' => 'member hello']);
    check($memberPost[1] === 200, 'Member can post to private group guestbook');
    $groupEntryId = (int) $db->fetchColumn('SELECT id FROM phpretro_group_guestbook WHERE guild_id = 1');
    asUser(3);
    check(endpoint('myhabbo_guestbook_remove.php', ['entryId' => (string) $groupEntryId, 'widgetId' => (string) $groupGuestbookId])[1] === 403, 'Stranger cannot delete group guestbook');
    asUser(1);
    check(endpoint('myhabbo_guestbook_remove.php', ['entryId' => (string) $groupEntryId, 'widgetId' => (string) $groupGuestbookId])[1] === 200, 'Owner can delete group guestbook');
    $save = endpoint('groups_actions_saveEditingSession.php', ['widgets' => $groupGuestbookId.':10,20,1']);
    check($save[1] === 200 && str_contains($save[0], 'waitAndGo'), 'Group save uses session and waitAndGo');
    check(!isset($_SESSION['group_page_edit']), 'Group save clears the website session');

    $blocked = [
        'habboclub_habboclub_subscribe.php',
        'myhabbo_traxplayer_select_song.php',
        'trax_song.php',
        'ajax_redeemvoucher.php',
        'groups_actions_show_badge_editor.php',
    ];
    $beforeCredits = $db->fetchAll('SELECT id, credits FROM users ORDER BY id');
    foreach ($blocked as $file) {
        check(endpoint($file, ['groupId' => '1', 'tagName' => 'crew', 'code' => 'TEST'])[1] === 501, $file.' stays 501');
    }
    check(endpoint('myhabbo_widget_add.php', ['widget_key' => 'traxplayerwidget'])[1] === 501, 'Trax player widget stays 501');
    check(endpoint('groups_actions_update_group_settings.php', [
        'groupId' => '1',
        'name' => 'Crew',
        'description' => 'x',
        'type' => '0',
        'forumType' => '0',
        'newTopicPermission' => '0',
        'roomId' => '2',
        'url' => '',
    ])[1] === 501, 'Room transfer stays 501');
    check($beforeCredits === $db->fetchAll('SELECT id, credits FROM users ORDER BY id'), 'Leftover 501s do not debit credits');
    check((int) $db->fetchColumn('SELECT room_id FROM guilds WHERE id = 1') === 1, 'Room transfer 501 does not rewrite guilds.room_id');

    $homePhp = file_get_contents($root.'/home.php');
    $groupsPhp = file_get_contents($root.'/groups.php');
    check(str_contains($homePhp, 'id="playground"') && str_contains($homePhp, 'id="mypage-bg"'), 'User home renders playground');
    check(str_contains($groupsPhp, 'displayGroupLayouts') && str_contains($groupsPhp, 'id="playground"'), 'Group home renders playground');
    check(!preg_match('/INSERT INTO users_settings|UPDATE users_settings/', file_get_contents($root.'/includes/PhpretroHomes.php').$homePhp), 'Homes code never writes users_settings');

    echo "PASS: $assertions assertions; remaining 501 restorations on disposable MariaDB.\n";
} finally {
    restore_error_handler();
    $admin->exec('DROP DATABASE `'.$testName.'`');
    putenv('DB_DSN='.$sourceDsn);
}
