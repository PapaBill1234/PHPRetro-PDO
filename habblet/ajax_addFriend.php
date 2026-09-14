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
$lang->addLocale('searchhabbos.addfriend');
$result = habbletRequestFriend($db, (int) $user->id, habbletInt($_POST, 'accountId'));
$message = $lang->loc[$result] ?? $result;
?>
<div id="avatar-habblet-dialog-body" class="topdialog-body"><ul>
<li><?php echo $input->HoloText($message); ?></li></ul>
<p><a href="#" class="new-button done"><b><?php echo $lang->loc['done']; ?></b><i></i></a></p></div>
