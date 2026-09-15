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
$lang->addLocale('homes.store');
$lang->addLocale('ajax.buttons');
$type = habbletText($_POST, 'type') ?: 'stickers';
$items = $homes->inventory($homes->storeType($type));
$first = $items[0] ?? null;
if ($first && ($first['type'] ?? '') !== 'widget') {
    $cssType = $first['type'] ?? $homes->storeType($type);
    phpretroHomesJsonHeader([[$lang->loc['inventory'], $lang->loc['web.store']], [$homes->itemCss($cssType, $first['data'], true), $homes->itemCss($cssType, $first['data']), $input->HoloText($first['name']), $lang->loc['stickers'], null, (int) ($first['quantity'] ?? 1)]]);
} else {
    phpretroHomesJsonHeader([[$lang->loc['inventory'], $lang->loc['web.store']], []]);
}
$stickers = $homes->storeCategories('sticker');
$backgrounds = $homes->storeCategories('background');
$notes = $homes->storeCategories('note');
?>
<div style="position: relative;">
<div id="webstore-categories-container">
	<h4><?php echo $lang->loc['categories']; ?>:</h4>
	<div id="webstore-categories">
<ul class="purchase-main-category">
		<li id="maincategory-1-stickers" class="selected-main-category webstore-selected-main">
			<div><?php echo $lang->loc['stickers']; ?></div>
			<ul class="purchase-subcategory-list" id="main-category-items-1">
<?php foreach ($stickers as $row) { ?>
				<li id="subcategory-1-<?php echo (int) $row['category_id']; ?>-stickers" class="subcategory">
					<div><?php echo $input->HoloText($row['category']); ?></div>
				</li>
<?php } ?>
			</ul>
		</li>
		<li id="maincategory-4-backgrounds" class="main-category">
			<div><?php echo $lang->loc['backgrounds']; ?></div>
			<ul class="purchase-subcategory-list" id="main-category-items-4">
<?php foreach ($backgrounds as $row) { ?>
				<li id="subcategory-4-<?php echo (int) $row['category_id']; ?>-backgrounds" class="subcategory">
					<div><?php echo $input->HoloText($row['category']); ?></div>
				</li>
<?php } ?>
			</ul>
		</li>
		<li id="maincategory-3-stickie_notes" class="main-category-no-subcategories">
			<div><?php echo $lang->loc['notes']; ?></div>
			<ul class="purchase-subcategory-list" id="main-category-items-3">
<?php foreach ($notes as $row) { ?>
				<li id="subcategory-3-<?php echo (int) $row['category_id']; ?>-stickie_notes" class="subcategory">
					<div><?php echo $input->HoloText($row['category']); ?></div>
				</li>
<?php } ?>
			</ul>
		</li>
</ul>
	</div>
</div>
<div id="webstore-content-container">
	<div id="webstore-items-container">
		<h4><?php echo $lang->loc['select.item.by.clicking']; ?></h4>
		<div id="webstore-items"><ul id="webstore-item-list">
<?php for ($n = 0; $n < 20; $n++) { ?><li class="webstore-item-empty"></li><?php } ?>
</ul></div>
	</div>
	<div id="webstore-preview-container">
		<div id="webstore-preview-default"></div>
		<div id="webstore-preview"></div>
	</div>
</div>
<div id="inventory-categories-container">
	<h4><?php echo $lang->loc['categories']; ?>:</h4>
	<div id="inventory-categories">
<ul class="purchase-main-category">
	<li id="inv-cat-stickers" class="selected-main-category-no-subcategories"><div><?php echo $lang->loc['stickers']; ?></div></li>
	<li id="inv-cat-backgrounds" class="main-category-no-subcategories"><div><?php echo $lang->loc['backgrounds']; ?></div></li>
	<li id="inv-cat-widgets" class="main-category-no-subcategories"><div><?php echo $lang->loc['widgets']; ?></div></li>
	<li id="inv-cat-notes" class="main-category-no-subcategories"><div><?php echo $lang->loc['notes']; ?></div></li>
</ul>
	</div>
</div>
<div id="inventory-content-container">
	<div id="inventory-items-container">
		<h4><?php echo $lang->loc['select.item.by.clicking']; ?></h4>
		<div id="inventory-items">
		<?php
		$page['bypass'] = true;
		$_POST['type'] = $type;
		require __DIR__.'/myhabbo_store_inventory_items.php';
		?>
		</div>
	</div>
	<div id="inventory-preview-container">
		<div id="inventory-preview-default"></div>
		<div id="inventory-preview">
		<?php
		$page['bypass'] = true;
		$_POST['itemId'] = (string) ($first['id'] ?? 0);
		$_POST['type'] = $type;
		require __DIR__.'/myhabbo_store_inventory_preview.php';
		?>
		</div>
	</div>
</div>
<div id="webstore-close-container">
	<div class="clearfix"><a href="#" id="webstore-close" class="new-button"><b><?php echo $lang->loc['close'] ?? 'Close'; ?></b><i></i></a></div>
</div>
</div>
