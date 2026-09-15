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
$lang->addLocale('homes.widget.rating');
$ownerId = habbletInt($_GET, 'ownerId');
$widgetId = habbletInt($_GET, 'ratingId');
$given = habbletInt($_GET, 'givenRate');
if (($page['bypass'] ?? false) !== true && $given >= 1) {
    if (phpretroHomesRun(static fn() => $homes->rate($ownerId, $widgetId, $given)) === null) { return; }
}
if ($ownerId < 1) { $ownerId = habbletInt($_POST, 'ownerId'); }
if ($widgetId < 1) { $widgetId = habbletInt($_POST, 'widgetId'); }
require __DIR__.'/../includes/habblet-templates/home-rating.php';
