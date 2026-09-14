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
$rows = $db->fetchAll('SELECT DISTINCT u.id, u.username AS name FROM messenger_friendships f JOIN users u ON u.id = f.user_two_id WHERE f.user_one_id = ? ORDER BY u.username, u.id', [(int) $user->id]);
$recipients = array_map(static fn(array $row): array => ['id' => (int) $row['id'], 'name' => $row['name']], $rows);
echo "/*-secure-\n".json_encode($recipients, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)."\n */";
