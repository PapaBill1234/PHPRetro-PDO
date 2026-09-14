<?php
$page['dir'] = '\\habblet'; $page['allow_guests'] = true;
require_once __DIR__.'/../includes/habblet.php';
require_once __DIR__.'/../includes/PhpretroHomes.php';
$homes = phpretroHomes();
$profileUserId = habbletInt($_REQUEST, 'profile_user_id');
if ($profileUserId < 1) { http_response_code(422); exit('Invalid profile.'); }
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    habbletRequireUser();
    if (habbletText($_POST, 'action') === 'delete') {
        phpretroHomesRun(static fn() => $homes->removeGuestbook(habbletInt($_POST, 'entry_id')));
    } else {
        $homes->need((int) $user->id > 0, 'Sign in required.', 401);
        $message = trim(habbletText($_POST, 'message'));
        $homes->need($message !== '' && mb_strlen($message, 'UTF-8') <= 1000, 'Message must be between 1 and 1000 characters.', 422);
        $homes->transaction(function () use ($homes, $profileUserId, $message) {
            $homes->db->execute('INSERT INTO phpretro_myhabbo_guestbook (profile_user_id, author_user_id, message, created_at) VALUES (?, ?, ?, ?)', [$profileUserId, $homes->actor, $message, time()]);
            $homes->sync->recordAndNotify('homes.guestbook_added', ['profile_user_id' => $profileUserId, 'author_user_id' => $homes->actor, 'entry_id' => (int) $homes->db->insertId()]);
        });
    }
}
$entries = $homes->guestbookEntries($profileUserId, 0, 50);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($entries);
