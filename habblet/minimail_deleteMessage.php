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
habbletRequireUser();
require_once __DIR__.'/../includes/PhpretroMinimail.php';
$mail = phpretroMinimail();
$lang->addLocale('minimail.deletemessage');
$result = phpretroMinimailRun(static fn() => $mail->trash(habbletInt($_POST, 'messageId')));
if ($result === null) { return; }
$label = habbletText($_POST, 'label', 'inbox');
if (!in_array($label, ['inbox', 'sent', 'trash', 'conversation'], true)) { $label = 'inbox'; }
$message = $result === 'deleted' ? $lang->loc['delete.error.1'] : $lang->loc['delete.error.2'];
phpretroMinimailXjson(['message' => $message, 'totalMessages' => $mail->folderCount($label === 'trash' ? 'trash' : 'inbox')]);
$page['bypass'] = true;
$start = habbletInt($_POST, 'start');
$conversationid = habbletInt($_POST, 'conversationId');
require __DIR__.'/minimail_loadMessages.php';
