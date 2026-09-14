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

require_once(__DIR__.'/../includes/habblet.php');
habbletRequireUser();
$lang->addLocale('ajax.buttons');
$month = mktime(0, 0, 0, (int) date('m'), 1, (int) date('Y'));
$name = $db->fetchColumn('SELECT name FROM phpretro_collectibles WHERE time = ?', [$month]);
// The CMS table is descriptive only: it has no catalog item ID or purchase price.
?>
<p><?php echo $input->HoloText($name === false ? 'No collectible this month.' : $name); ?></p>
<p>Collectible purchases are unavailable.</p>
<p><a href="#" class="new-button" id="collectibles-close"><b><?php echo $lang->loc['cancel']; ?></b><i></i></a></p>
