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
$lang->addLocale('minimail.sentmessage');
$created = phpretroMinimailRun(static function () use ($mail, $lang) {
    $ids = array_filter(array_map('intval', explode(',', habbletText($_POST, 'recipientIds'))));
    $mail->send($ids, habbletText($_POST, 'subject'), habbletText($_POST, 'body'), habbletInt($_POST, 'messageId'));
    phpretroMinimailXjson(['message' => $lang->loc['sent.message'], 'totalMessages' => $mail->folderCount('inbox')]);
    return true;
});
if ($created === null) { return; }
$page['bypass'] = true;
$label = 'inbox';
require __DIR__.'/minimail_loadMessages.php';
