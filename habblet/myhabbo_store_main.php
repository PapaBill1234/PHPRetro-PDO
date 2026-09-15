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
$stickers = $homes->storeCategories('sticker');
$backgrounds = $homes->storeCategories('background');
$notes = $homes->storeCategories('note');
$firstCategory = (int) (($stickers[0]['category_id'] ?? 0));
$items = $homes->storeItems('sticker', $firstCategory);
$first = $items[0] ?? null;
if ($first) {
    phpretroHomesJsonHeader([[$lang->loc['inventory'], $lang->loc['web.store']], [['itemCount' => (int) $first['amount'], 'previewCssClass' => $homes->itemCss('sticker', $first['data'], true), 'titleKey' => '']]]);
} else {
    phpretroHomesJsonHeader([[$lang->loc['inventory'], $lang->loc['web.store']], []]);
}
$selectedSub = habbletInt($_POST, 'subCategoryId', $firstCategory);
$typeLabels = ['sticker' => 'stickers', 'background' => 'backgrounds', 'note' => 'stickie_notes'];
$typeIds = ['sticker' => 1, 'background' => 4, 'note' => 3];
?>
<div style="position: relative;">
<div id="webstore-categories-container">
	<h4><?php echo $lang->loc['categories']; ?>:</h4>
	<div id="webstore-categories">
<ul class="purchase-main-category">
		<li id="maincategory-1-stickers" class="selected-main-category webstore-selected-main">
			<div><?php echo $lang->loc['stickers']; ?></div>
			<ul class="purchase-subcategory-list" id="main-category-items-1">
<?php foreach ($stickers as $i => $row) { ?>
				<li id="subcategory-1-<?php echo (int) $row['category_id']; ?>-stickers" class="subcategory<?php echo ($selectedSub === (int) $row['category_id'] || $i === 0) ? '-selected' : ''; ?>">
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
		<div id="webstore-items">
		<?php
		$page['bypass'] = true;
		$_POST['categoryId'] = '1';
		$_POST['subCategoryId'] = (string) $firstCategory;
		require __DIR__.'/myhabbo_store_items.php';
		?>
		</div>
	</div>
	<div id="webstore-preview-container">
		<div id="webstore-preview-default"></div>
		<div id="webstore-preview">
		<?php
		$page['bypass'] = true;
		$_POST['productId'] = (string) ($first['id'] ?? 0);
		require __DIR__.'/myhabbo_store_preview.php';
		?>
		</div>
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
		<div id="inventory-items"><ul id="inventory-item-list">
<?php for ($n = 0; $n < 20; $n++) { ?><li class="webstore-item-empty"></li><?php } ?>
</ul></div>
	</div>
	<div id="inventory-preview-container">
		<div id="inventory-preview-default"></div>
		<div id="inventory-preview"></div>
	</div>
</div>
<div id="webstore-close-container">
	<div class="clearfix"><a href="#" id="webstore-close" class="new-button"><b><?php echo $lang->loc['close'] ?? 'Close'; ?></b><i></i></a></div>
</div>
</div>
