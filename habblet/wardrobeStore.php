<?php
/*================================================================+\
|| # PHPRetro - An extendable virtual hotel site and management
|+==================================================================
|| # Copyright (C) 2009 Yifan Lu. All rights reserved.
|| # http://www.yifanlu.com
|| # Parts Copyright (C) 2009 Meth0d. All rights reserved.
|| # http://www.meth0d.org
|| # All images, scripts, and layouts
|| # Copyright (C) 2009 Sulake Ltd. All rights reserved.
|+==================================================================
|| # PHPRetro is provided "as is" and comes without
|| # warrenty of any kind. PHPRetro is free software!
|| # License: GNU Public License 3.0
|| # http://opensource.org/licenses/gpl-license.php
\+================================================================*/

require_once(__DIR__.'/../includes/habblet.php');
habbletRequireUser();
$slot = habbletInt($_POST, 'slot');
$figure = habbletText($_POST, 'figure');
$gender = habbletText($_POST, 'gender');
if ($slot < 1 || $slot > 5 || strlen($figure) > 256 || !preg_match('/^[a-z]{2}-[0-9]+-[0-9]*(?:\.[a-z]{2}-[0-9]+-[0-9]*)*$/D', $figure) || !in_array($gender, ['M', 'F'], true)) {
    http_response_code(400); echo 'Invalid outfit.'; return;
}
$club = (int) $db->fetchColumn('SELECT club_expire_timestamp FROM users_settings WHERE user_id = ?', [(int) $user->id]) > time();
$check = new HoloFigureCheck($figure, $gender, $club);
if ($check->error > 0) { http_response_code(400); echo 'Invalid outfit.'; return; }
$db->execute('START TRANSACTION');
try {
    // There is no unique (user_id, slot_id) key. Lock the owner before upserting.
    $db->fetchColumn('SELECT id FROM users WHERE id = ? FOR UPDATE', [(int) $user->id]);
    $existing = $db->fetchColumn('SELECT id FROM users_wardrobe WHERE user_id = ? AND slot_id = ? ORDER BY id LIMIT 1', [(int) $user->id, $slot]);
    if ($existing !== false) {
        $db->execute('UPDATE users_wardrobe SET look = ?, gender = ? WHERE user_id = ? AND slot_id = ?', [$figure, $gender, (int) $user->id, $slot]);
    } else {
        $db->execute('INSERT INTO users_wardrobe (user_id, slot_id, look, gender) VALUES (?, ?, ?, ?)', [(int) $user->id, $slot, $figure, $gender]);
    }
    $db->execute('COMMIT');
} catch (Throwable $exception) {
    $db->execute('ROLLBACK');
    throw $exception;
}
header('X-JSON: '.json_encode(['u' => $user->avatarURL($figure, 's,4,4,sml,1,0'), 'f' => $figure, 'g' => ord($gender)]));

