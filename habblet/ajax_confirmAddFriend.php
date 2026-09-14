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
$lang->addLocale('searchhabbos.confirmaddfriend');
$lang->addLocale('ajax.buttons');
$name = $db->fetchColumn('SELECT username FROM users WHERE id = ?', [habbletInt($_POST, 'accountId')]);
if ($name === false) { http_response_code(404); echo 'Account not found.'; return; }
?>
<p><?php echo $lang->loc['confirm.add'].' '.$input->HoloText($name).' '.$lang->loc['to.friend.list']; ?></p>
<p><a href="#" class="new-button done"><b><?php echo $lang->loc['cancel']; ?></b><i></i></a>
<a href="#" class="new-button add-continue"><b><?php echo $lang->loc['continue']; ?></b><i></i></a></p>
