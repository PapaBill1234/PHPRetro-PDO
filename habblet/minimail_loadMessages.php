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
if (($page['bypass'] ?? false) !== true) {
    habbletRequireUser();
}
require_once __DIR__.'/../includes/PhpretroMinimail.php';
$mail = phpretroMinimail();
$label = $label ?? habbletText($_POST, 'label', 'inbox');
if (!in_array($label, ['inbox', 'sent', 'trash', 'conversation'], true)) { $label = 'inbox'; }
$offset = isset($start) ? max(0, (int) $start) : max(0, habbletInt($_POST, 'start'));
$conversationId = isset($conversationid) ? (int) $conversationid : habbletInt($_POST, 'conversationId');
$unreadOnly = (isset($unread) ? (string) $unread : habbletText($_POST, 'unreadOnly')) === 'true';
if (($page['bypass'] ?? false) !== true) {
    phpretroMinimailXjson(['totalMessages' => $mail->folderCount($label === 'inbox' ? 'inbox' : $label, $conversationId, false)]);
}
$total = $mail->folderCount($label, $conversationId, $unreadOnly);
$rows = $mail->list($label, $offset, $conversationId, $unreadOnly);
require __DIR__.'/../includes/habblet-templates/minimail-list.php';
