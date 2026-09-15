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

$page['no_ajax'] = true;
require_once __DIR__.'/../includes/habblet.php';
habbletRequireUser();
require_once __DIR__.'/../includes/PhpretroHomes.php';
$homes = phpretroHomes();
$type = habbletText($_GET, 'type') ?: habbletText($_POST, 'type');
$id = habbletInt($_GET, 'id', (int) $user->id);
$username = $homes->profile((int) $user->id)['username'];
if ($type === 'startSession') {
    if ($id !== (int) $user->id) { http_response_code(403); exit('Not permitted.'); }
    $_SESSION['page_edit'] = 'home';
    $homes->sync->recordAndNotify('homes.session_start', ['user_id' => (int) $user->id]);
} elseif ($type === 'cancel') {
    unset($_SESSION['page_edit']);
    $homes->sync->recordAndNotify('homes.session_cancel', ['user_id' => (int) $user->id]);
} elseif ($type === 'save') {
    phpretroHomesRun(static fn() => $homes->saveLayout($_POST));
    unset($_SESSION['page_edit']);
    echo "<script language=\"JavaScript\" type=\"text/javascript\">waitAndGo('".PATH.'/home/'.rawurlencode($username)."');</script>";
    return;
}
header('Location: '.PATH.'/home/'.rawurlencode($username));
return;
