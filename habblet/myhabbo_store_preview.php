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
if (($page['bypass'] ?? false) !== true) {
    habbletRequireUser();
    require_once __DIR__.'/../includes/PhpretroHomes.php';
    $homes = phpretroHomes();
}
$lang->addLocale('homes.store.preview');
$row = $homes->catalogue(habbletInt($_POST, 'productId'));
if (!$row) { echo '<p>Unknown product.</p>'; return; }
$credits = (int) $db->fetchColumn('SELECT credits FROM users WHERE id = ?', [(int) $user->id]);
$notEnough = ($credits - (int) $row['price']) < 0;
$inHotel = $homes->inHotel();
$payload = ['itemCount' => (int) $row['amount'], 'previewCssClass' => $homes->itemCss($row['type'], $row['data'], true), 'titleKey' => $input->HoloText($row['name'])];
if ($row['type'] === 'background') { $payload['bgCssClass'] = $homes->itemCss('background', $row['data']); }
if (($page['bypass'] ?? false) !== true) { phpretroHomesJsonHeader([$payload]); }
?>
<h4 title=""></h4>
<div id="webstore-preview-box"></div>
<div id="webstore-preview-price">
<?php echo $lang->loc['price']; ?>:<br /><b>
	<?php echo (int) $row['price']; ?> <?php echo $lang->loc['credit']; ?>
</b>
</div>
<div id="webstore-preview-purse">
<?php echo $lang->loc['you.have']; ?>:<br /><b><?php echo $credits; ?> <?php echo $lang->loc['credit']; ?></b><br />
<?php if ($notEnough) { ?><span class="webstore-preview-error"><?php echo $lang->loc['not.enough.credits']; ?></span><br /><?php } ?>
<?php if ($inHotel) { ?><span class="webstore-preview-error">Leave the hotel first. Credits are deducted from your hotel account and would be overwritten while you are online.</span><br /><?php } ?>
<a href="<?php echo PATH; ?>/credits" target=_blank><?php echo $lang->loc['get.credits']; ?></a>
</div>
<div id="webstore-preview-purchase" class="clearfix">
	<div class="clearfix">
		<?php if ($notEnough || $inHotel) { ?><a href="#" class="new-button disabled-button" disabled="disabled" id="webstore-purchase-disabled"><?php } else { ?><a href="#" class="new-button" id="webstore-purchase"><?php } ?><b><?php echo $lang->loc['purchase']; ?></b><i></i></a>
	</div>
</div>
<span id="webstore-preview-bg-text" style="display: none"><?php echo $lang->loc['preview']; ?></span>
