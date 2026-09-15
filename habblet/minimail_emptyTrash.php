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

require_once __DIR__.'/../includes/habblet.php';
Csrf::protectPost();
habbletRequireUser();
require_once __DIR__.'/../includes/PhpretroMinimail.php';
$mail = phpretroMinimail();
$lang->addLocale('minimail.emptytrash');
if (phpretroMinimailRun(static fn() => $mail->emptyTrash()) === null) { return; }
phpretroMinimailXjson(['message' => $lang->loc['trash.message'], 'totalMessages' => $mail->folderCount('trash')]);
$page['bypass'] = true;
$label = 'trash';
require __DIR__.'/minimail_loadMessages.php';
