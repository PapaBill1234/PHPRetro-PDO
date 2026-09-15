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
require_once(__DIR__.'/../includes/PhpretroWebRestorations.php');
$lang->addLocale('ajax.buttons');
$item = phpretroWebRestorations()->currentCollectible();
$name = $item ? $item['name'] : 'No collectible this month.';
?>
<p><?php echo $input->HoloText($name); ?></p>
<p>This records a website claim. Furniture is not granted from PolarIS here.</p>
<?php if ($item) { ?>
<p><a href="#" class="new-button" id="collectibles-purchase"><b><?php echo $lang->loc['ok'] ?? 'Purchase'; ?></b><i></i></a></p>
<?php } ?>
<p><a href="#" class="new-button" id="collectibles-close"><b><?php echo $lang->loc['cancel']; ?></b><i></i></a></p>
