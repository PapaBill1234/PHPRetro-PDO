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
$friends = $_POST['friendList'] ?? [$_POST['friendId'] ?? null];
if (!is_array($friends) || count($friends) > 100) { http_response_code(400); echo 'Invalid friend list.'; return; }
foreach ($friends as $friend) {
    $id = habbletInt(['id' => $friend], 'id');
    if ($id > 0) {
        $db->execute('DELETE FROM messenger_friendships WHERE (user_one_id = ? AND user_two_id = ?) OR (user_one_id = ? AND user_two_id = ?)', [(int) $user->id, $id, $id, (int) $user->id]);
    }
}
$_GET['pageNumber'] = 1;
require(__DIR__.'/friendmanagement_viewcategory.php');
