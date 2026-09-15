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
$label = habbletText($_GET, 'label', 'inbox');
if (!in_array($label, ['inbox', 'sent', 'trash', 'conversation'], true)) { $label = 'inbox'; }
$row = phpretroMinimailRun(static function () use ($mail, $label) {
    $id = habbletInt($_GET, 'messageId');
    return $label === 'inbox' ? $mail->markRead($id) : $mail->message($id);
});
if ($row === null) { return; }
require __DIR__.'/../includes/habblet-templates/minimail-message.php';
