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
$lang->addLocale('minimail.report');
if (phpretroMinimailRun(static function () use ($mail) { $mail->report(habbletInt($_POST, 'messageId')); return true; }) === null) { return; }
$page['bypass'] = true;
$label = habbletText($_POST, 'label', 'inbox');
$start = habbletInt($_POST, 'start');
$message = $lang->loc['report.message'];
require __DIR__.'/minimail_loadMessages.php';
