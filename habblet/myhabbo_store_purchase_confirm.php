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
$lang->addLocale('homes.store.purchase.confirm');
$lang->addLocale('ajax.buttons');
$row = $homes->catalogue(habbletInt($_POST, 'productId'));
if (!$row) { echo '<p>Unknown product.</p>'; return; }
?>
<div class="webstore-item-preview <?php echo $homes->itemCss($row['type'], $row['data'], true); ?>">
	<div class="webstore-item-mask"></div>
</div>
<p>
<?php echo $lang->loc['store.purchase.confirm']; ?>
</p>
<p>You must be signed out of the hotel. Credits are deducted from your hotel account and will show in-game the next time you enter. The item is added to your website inventory immediately.</p>
<p class="new-buttons">
<a href="#" class="new-button" id="webstore-confirm-cancel"><b><?php echo $lang->loc['cancel'] ?? 'Cancel'; ?></b><i></i></a>
<a href="#" class="new-button" id="webstore-confirm-submit"><b><?php echo $lang->loc['continue'] ?? 'Continue'; ?></b><i></i></a>
</p>
<div class="clear"></div>
