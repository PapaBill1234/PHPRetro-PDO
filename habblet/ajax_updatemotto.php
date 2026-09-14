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
$motto = habbletText($_POST, 'motto');
if (isset($_POST['motto']) && is_string($_POST['motto']) && strlen($motto) <= 38) {
    $db->execute('UPDATE users SET motto = ? WHERE id = ?', [$motto, (int) $user->id]);
    $user->user[8] = $motto;
    $_SESSION['user'] = $user;
}
echo $input->HoloText((string) $db->fetchColumn('SELECT motto FROM users WHERE id = ?', [(int) $user->id]));
// No legacy MUS packet: a supported Polaris live-session integration is still needed.
