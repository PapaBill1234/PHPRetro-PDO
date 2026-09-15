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
$page['edit'] = true;
$placed = phpretroHomesRun(static fn() => $homes->placeSticker(
    habbletInt($_POST, 'selectedStickerId'),
    habbletInt($_POST, 'zindex', 1),
    habbletText($_POST, 'placeAll') === 'true'
));
if ($placed === null) { return; }
$ids = [];
foreach ($placed as $item) {
    $ids[] = (string) $item['id'];
    require __DIR__.'/../includes/habblet-templates/home-sticker.php';
}
phpretroHomesJsonHeader($ids);
