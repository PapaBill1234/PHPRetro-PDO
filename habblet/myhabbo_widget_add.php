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
require_once __DIR__.'/../includes/PhpretroHomes.php';
$homes = phpretroHomes();
$key = habbletText($_POST, 'widgetType') ?: habbletText($_POST, 'widget_key') ?: habbletText($_POST, 'widgetId');
$widget = phpretroHomesRun(static fn() => $homes->add($key, habbletInt($_POST, 'column_number', 1)));
if ($widget === null) { return; }
header('X-JSON: '.json_encode([(string) $widget['id']]));
$page['edit'] = true;
$page['bypass'] = true;
require __DIR__.'/myhabbo_widgets.php';
