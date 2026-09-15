<?php
$page['dir'] = '\\habblet'; $page['allow_guests'] = true;
require_once __DIR__.'/../includes/habblet.php';
require_once __DIR__.'/../includes/PhpretroHomes.php';
$homes = phpretroHomes();
$profileUserId = habbletInt($_GET, 'user_id', (int) $user->id);
$layouts = $homes->layouts($profileUserId);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($layouts);
