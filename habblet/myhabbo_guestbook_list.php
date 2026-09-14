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
if (($page['bypass'] ?? false) !== true) { habbletRequireUser(); }
require_once __DIR__.'/../includes/PhpretroHomes.php';
$homes = phpretroHomes();
$widget = phpretroHomesRun(static fn() => $homes->guestbook(habbletInt($_POST, 'widgetId') ?: (int) ($widget ?? 0)));
if ($widget === null) { return; }
$entries = $homes->guestbookEntries((int) $widget['user_id'], max(0, (habbletInt($_POST, 'start') ?: 0)));
foreach ($entries as $entry) { require __DIR__.'/../includes/habblet-templates/home-guestbook-entry.php'; }
