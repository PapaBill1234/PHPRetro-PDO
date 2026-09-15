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
$lang->addLocale('homes.store.inventory.items');
$type = $homes->storeType(habbletText($_POST, 'type') ?: 'stickers');
$items = $homes->inventory($type);
if ($type === 'widget') {
?>
<ul id="inventory-item-list">
<?php foreach ($items as $row) {
    $disabled = $homes->widgetPlaced($row['data']);
?>
	<li id="inventory-item-p-<?php echo (int) $row['id']; ?>" title="<?php echo $input->HoloText($row['name']); ?>" class="webstore-widget-item<?php echo $disabled ? ' webstore-widget-disabled' : ''; ?>">
		<div class="webstore-item-preview <?php echo $homes->itemCss('widget', $row['data'], true); ?>">
			<div class="webstore-item-mask"></div>
		</div>
		<div class="webstore-widget-description">
			<h3><?php echo $input->HoloText($row['name']); ?></h3>
			<p><?php echo $input->HoloText($row['description']); ?></p>
		</div>
	</li>
<?php } ?>
</ul>
<?php
    return;
}
if ($items === []) {
?>
<div class="webstore-frank">
	<div class="blackbubble"><div class="blackbubble-body">
<p><b><?php echo $lang->loc['inventory.empty']; ?></b></p>
<p><?php echo $lang->loc['how.to.purchase.items']; ?></p>
		<div class="clear"></div>
		</div></div>
	<div class="blackbubble-bottom"><div class="blackbubble-bottom-body">
			<img src="<?php echo PATH; ?>/web-gallery/images/box-scale/bubble_tail_small.gif" alt="" width="12" height="21" class="invitation-tail" />
		</div></div>
	<div class="webstore-frank-image"><img src="<?php echo PATH; ?>/web-gallery/images/frank/sorry.gif" alt="" width="57" height="88" /></div>
</div>
<?php } ?>
<ul id="inventory-item-list">
<?php
$i = 0;
foreach ($items as $row) {
    $i++;
?>
	<li id="inventory-item-<?php echo (int) $row['id']; ?>" title="<?php echo $input->HoloText($row['name']); ?>">
		<div class="webstore-item-preview <?php echo $homes->itemCss($row['type'], $row['data'], true); ?>">
			<div class="webstore-item-mask">
				<?php if ((int) $row['quantity'] > 1) { ?><div class="webstore-item-count"><div>x<?php echo (int) $row['quantity']; ?></div></div><?php } ?>
			</div>
		</div>
	</li>
<?php }
$max = phpretroHomesPadList($i);
for ($n = 0; $n < $max; $n++) { ?>
	<li class="webstore-item-empty"></li>
<?php } ?>
</ul>
