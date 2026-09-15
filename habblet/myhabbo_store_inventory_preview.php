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
$lang->addLocale('homes.store.inventory.preview');
$type = $homes->storeType(habbletText($_POST, 'type') ?: 'stickers');
$id = habbletInt($_POST, 'itemId');
if ($type === 'widget') {
    $row = $homes->catalogue($id);
    if (!$row) { echo '<p>Unknown item.</p>'; return; }
    $jsonType = '"Widget"';
    $preview = 'null';
    $title = $row['description'];
    $css = $homes->itemCss('widget', $row['data'], true);
    $count = 1;
} else {
    $row = $homes->inventoryItem($id);
    if (!$row) { echo '<p>Unknown item.</p>'; return; }
    $jsonType = $type === 'background' ? '"Background"' : ($type === 'note' ? '"WebCommodity"' : '"Sticker"');
    $preview = $type === 'note' ? 'null' : '"'.$homes->itemCss($row['type'], $row['catalogue_data']).'"';
    $title = $row['description'] ?: $row['name'];
    $css = $homes->itemCss($row['type'], $row['catalogue_data'], true);
    $qty = $homes->db->fetchColumn('SELECT COUNT(*) FROM phpretro_homes_items WHERE user_id = ? AND catalogue_id = ? AND '.($type === 'background' ? '1=1' : 'placed = 0'), [(int) $user->id, $row['catalogue_id']]);
    $count = (int) $qty;
}
if (($page['bypass'] ?? false) !== true) {
    header('X-JSON: ["'.$css.'",'.$preview.','.json_encode($title).','.$jsonType.','.($type === 'widget' ? '"true"' : 'null').','.$count.']');
}
?>
<h4>&nbsp;</h4>
<div id="inventory-preview-box"></div>
<div id="inventory-preview-place" class="clearfix">
	<div class="clearfix">
		<a href="#" class="new-button" id="inventory-place"><b><?php echo $lang->loc['place']; ?></b><i></i></a>
	</div>
</div>
