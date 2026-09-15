<?php
require_once __DIR__.'/../includes/habblet.php';
Csrf::protectPost();
habbletRequireUser();
require_once __DIR__.'/../includes/PhpretroPolarisCms.php';
global $db, $user;
$reported = habbletInt($_POST, 'reported_user_id');
$reason = trim(habbletText($_POST, 'reason'));
$evidence = trim(habbletText($_POST, 'evidence'));
if ($reported < 1 || $reported === (int) $user->id || $reason === '' || $evidence === '') {
    http_response_code(422);
    exit('Invalid report');
}
if ($db->fetchColumn('SELECT id FROM users WHERE id = ?', [$reported]) === false) {
    http_response_code(404);
    exit('User not found');
}
$db->execute(
    'INSERT INTO phpretro_user_reports (reporter_id, reported_user_id, reason, evidence, status, action_notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
    [(int) $user->id, $reported, $reason, $evidence, 'open', '', time()]
);
PhpretroPolarisCms::instance()->notifyUserReport($db, (int) $user->id, $reported, $reason, $evidence);
header('Content-Type: application/json');
echo json_encode(['ok' => true]);
