<?php
require_once __DIR__.'/../includes/habblet.php';
Csrf::protectPost();
habbletRequireUser();
require_once __DIR__.'/../includes/PhpretroHomes.php';
$homes = phpretroHomes();
$key = $homes->key(habbletText($_POST, 'widget_key'));
$column = habbletInt($_POST, 'column_number', 1);
$position = max(0, habbletInt($_POST, 'position'));
$visible = habbletInt($_POST, 'visible', 1) === 1 ? 1 : 0;
if (in_array($key, PhpretroHomes::BLOCKED_WIDGETS, true)) { habbletUnavailable('This widget is unavailable.'); return; }
if (!in_array($key, PhpretroHomes::USER_WIDGETS, true) || $column < 1 || $column > 2) { http_response_code(422); exit('Invalid layout entry.'); }
$homes->transaction(function () use ($homes, $key, $column, $position, $visible) {
    $homes->db->execute('DELETE FROM phpretro_myhabbo_layouts WHERE user_id = ? AND widget_key = ?', [$homes->actor, $key]);
    $homes->db->execute('INSERT INTO phpretro_myhabbo_layouts (user_id, column_number, widget_key, position, visible) VALUES (?, ?, ?, ?, ?)', [$homes->actor, $column, $key, $position, $visible]);
    $homes->sync->recordAndNotify('homes.layout_saved', ['user_id' => $homes->actor, 'widget_key' => $key]);
});
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true]);
