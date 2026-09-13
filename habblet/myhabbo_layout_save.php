<?php
$page['dir'] = '\\habblet'; require_once('../includes/core.php'); require_once('./includes/session.php');
$database = new Database();
$allowed = ['profile', 'guestbook', 'highscores', 'badges', 'friends', 'groups', 'rooms', 'traxplayer', 'rating'];
$widgetKey = (string) ($_POST['widget_key'] ?? ''); $column = (int) ($_POST['column_number'] ?? 1); $position = max(0, (int) ($_POST['position'] ?? 0)); $visible = isset($_POST['visible']) ? 1 : 0;
if (!in_array($widgetKey, $allowed, true) || $column < 1 || $column > 3) { http_response_code(422); exit('Invalid layout entry.'); }
$database->execute('DELETE FROM phpretro_myhabbo_layouts WHERE user_id = ? AND widget_key = ?', [$user->id, $widgetKey]);
$database->execute('UPDATE phpretro_myhabbo_layouts SET position = position + 1 WHERE user_id = ? AND column_number = ? AND position >= ? ORDER BY position DESC', [$user->id, $column, $position]);
$database->execute('INSERT INTO phpretro_myhabbo_layouts (user_id, column_number, widget_key, position, visible) VALUES (?, ?, ?, ?, ?)', [$user->id, $column, $widgetKey, $position, $visible]);
header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok' => true]);