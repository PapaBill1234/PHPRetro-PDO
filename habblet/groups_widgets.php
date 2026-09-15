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
require_once __DIR__.'/../includes/habblet_groups.php';
require_once __DIR__.'/../includes/PhpretroHomes.php';
if (($page['bypass'] ?? false) !== true) { habbletRequireUser(); }
$homes = phpretroHomes();
$key = habbletText($_POST, 'widgetType') ?: habbletText($_POST, 'widget_key');
if ($key !== '' && ($page['bypass'] ?? false) !== true) {
    $widget = phpretroHomesRun(static fn() => $homes->add($key, habbletInt($_POST, 'column_number', 1)));
    if ($widget === null) { return; }
    header('X-JSON: '.json_encode([(string) $widget['id']]));
} else {
    if (isset($widget) && is_array($widget)) { $widgetId = (int) $widget['id']; }
    else { $widgetId = isset($widget) ? (int) $widget : habbletInt($_POST, 'widgetId'); }
    $widget = phpretroHomesRun(static fn() => $homes->widget($widgetId));
    if ($widget === null) { return; }
}
if (in_array($widget['widget_key'], PhpretroHomes::BLOCKED_WIDGETS, true)) {
    habbletUnavailable('This widget is unavailable.');
    return;
}
require __DIR__.'/../includes/habblet-templates/group-widget.php';
