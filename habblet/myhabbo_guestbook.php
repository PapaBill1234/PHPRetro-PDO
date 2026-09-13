<?php
$page['dir'] = '\\habblet'; $page['allow_guests'] = true; require_once('../includes/core.php'); require_once('./includes/session.php');
$database = new Database(); $profileUserId = (int) ($_REQUEST['profile_user_id'] ?? 0);
if ($profileUserId < 1) { http_response_code(422); exit('Invalid profile.'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($user->id < 1) { http_response_code(401); exit('Sign in required.'); }
    if (($_POST['action'] ?? 'add') === 'delete') {
        $database->execute('DELETE FROM phpretro_myhabbo_guestbook WHERE id = ? AND (author_user_id = ? OR profile_user_id = ?)', [(int) ($_POST['entry_id'] ?? 0), $user->id, $user->id]);
    } else {
        $message = trim((string) ($_POST['message'] ?? ''));
        if ($message === '' || mb_strlen($message) > 1000) { http_response_code(422); exit('Message must be between 1 and 1000 characters.'); }
        $database->execute('INSERT INTO phpretro_myhabbo_guestbook (profile_user_id, author_user_id, message, created_at) VALUES (?, ?, ?, ?)', [$profileUserId, $user->id, $message, time()]);
    }
}
$entries = $database->fetchAll('SELECT guestbook.id, guestbook.author_user_id, guestbook.message, guestbook.created_at, users.username FROM phpretro_myhabbo_guestbook AS guestbook JOIN users ON users.id = guestbook.author_user_id WHERE guestbook.profile_user_id = ? ORDER BY guestbook.created_at DESC, guestbook.id DESC LIMIT 50', [$profileUserId]);
header('Content-Type: application/json; charset=utf-8'); echo json_encode($entries);