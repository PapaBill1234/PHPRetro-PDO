<?php
$page['dir'] = '\\habblet'; $page['allow_guests'] = true; require_once('../includes/core.php'); require_once('./includes/session.php');
$database = new Database(); $profileUserId = (int) ($_GET['user_id'] ?? $user->id);
$layouts = $database->fetchAll('SELECT column_number, widget_key, position, visible FROM phpretro_myhabbo_layouts WHERE user_id = ? AND visible = 1 ORDER BY column_number ASC, position ASC', [$profileUserId]);
header('Content-Type: application/json; charset=utf-8'); echo json_encode($layouts);