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
$lang->addLocale('homes.store.items');
$type = $homes->storeType(habbletText($_POST, 'categoryId') ?: '1');
$categoryId = habbletInt($_POST, 'subCategoryId');
$items = $homes->storeItems($type, $categoryId);
if ($items === []) {
?>
<div class="webstore-frank">
	<div class="blackbubble"><div class="blackbubble-body">
<p><b><?php echo $lang->loc['no.items.in.store']; ?></b></p>
<p><?php echo $lang->loc['watch.this.space']; ?></p>
		<div class="clear"></div>
		</div></div>
	<div class="blackbubble-bottom"><div class="blackbubble-bottom-body">
			<img src="<?php echo PATH; ?>/web-gallery/images/box-scale/bubble_tail_small.gif" alt="" width="12" height="21" class="invitation-tail" />
		</div></div>
	<div class="webstore-frank-image"><img src="<?php echo PATH; ?>/web-gallery/images/frank/hello.gif" alt="" width="76" height="86" /></div>
</div>
<?php } ?>
<ul id="webstore-item-list">
<?php
$i = 0;
foreach ($items as $row) {
    $i++;
?>
	<li id="webstore-item-<?php echo (int) $row['id']; ?>" title="<?php echo $input->HoloText($row['name']); ?>">
		<div class="webstore-item-preview <?php echo $homes->itemCss($row['type'], $row['data'], true); ?>">
			<div class="webstore-item-mask">
				<?php if ((int) $row['amount'] > 1) { ?><div class="webstore-item-count"><div>x<?php echo (int) $row['amount']; ?></div></div><?php } ?>
			</div>
		</div>
	</li>
<?php }
$max = phpretroHomesPadList($i);
for ($n = 0; $n < $max; $n++) { ?>
	<li class="webstore-item-empty"></li>
<?php } ?>
</ul>
