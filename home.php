<?php
$page['allow_guests'] = true;
$page['no_column3'] = true;
$page['discussion'] = false;
$page['concurrent_editing'] = false;
require_once './includes/core.php';
require_once './includes/session.php';
require_once './includes/habblet.php';
require_once './includes/habblet_groups.php';
require_once './includes/PhpretroHomes.php';
$lang->addLocale('home.homes');
$homes = phpretroHomes();
$userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$username = isset($_GET['name']) ? trim((string) $_GET['name']) : '';
if ($userId > 0) {
    $profile = $db->fetchRow('SELECT id, username, motto, look, rank, last_online FROM users WHERE id = ? LIMIT 1', [$userId]);
} elseif ($username !== '') {
    $profile = $db->fetchRow('SELECT id, username, motto, look, rank, last_online FROM users WHERE username = ? LIMIT 1', [$username]);
} else {
    $profile = false;
}
if (!$profile) { $lang->clearLocale; require_once './error.php'; exit; }
$userrow = [(int) $profile['id'], $profile['username'], (int) $profile['rank']];
$page['edit'] = ((int) $user->id === (int) $profile['id']) && (($_SESSION['page_edit'] ?? '') === 'home');
$page['id'] = 'home';
$page['type'] = 'home';
$page['name'] = $input->HoloText($profile['username']);
$page['bodyid'] = $page['edit'] ? 'editmode' : 'viewmode';
$page['cat'] = 'home';
require_once './templates/community_header.php';
$columns = [1 => [], 2 => []];
foreach ($homes->displayLayouts((int) $profile['id']) as $row) {
    $col = ((int) $row['column_number'] === 2) ? 2 : 1;
    $columns[$col][] = $row;
}
$placed = $homes->placedItems((int) $profile['id']);
$background = $homes->backgroundClass((int) $profile['id']);
?>
<div id="mypage-wrapper" class="cbb blue">
<div class="box-tabs-container box-tabs-left clearfix">
<?php if (!$page['edit'] && (int) $user->id === (int) $profile['id']) { ?>
	<a href="<?php echo PATH; ?>/myhabbo/startSession/<?php echo (int) $profile['id']; ?>" id="edit-button" class="new-button dark-button edit-icon" style="float:left"><b><span></span><?php echo $lang->loc['edit'] ?? 'Edit'; ?></b><i></i></a>
<?php } ?>
	<h2 class="page-owner"><?php echo $input->HoloText($profile['username']); ?></h2>
	<ul class="box-tabs"></ul>
</div>
<div id="mypage-content">
<?php if ($page['edit']) { ?>
<div id="top-toolbar" class="clearfix">
	<ul>
		<li><a href="#" id="inventory-button"><?php echo $lang->loc['inventory'] ?? 'Inventory'; ?></a></li>
		<li><a href="#" id="webstore-button"><?php echo $lang->loc['web.store'] ?? 'Web Store'; ?></a></li>
	</ul>
	<form action="#" method="get" style="width: 50%">
		<a id="cancel-button" class="new-button red-button cancel-icon" href="#"><b><span></span><?php echo $lang->loc['cancel.editing'] ?? 'Cancel'; ?></b><i></i></a>
		<a id="save-button" class="new-button green-button save-icon" href="#"><b><span></span><?php echo $lang->loc['save.changes'] ?? 'Save'; ?></b><i></i></a>
	</form>
</div>
<?php } ?>
<div id="mypage-bg" class="<?php echo htmlspecialchars($background, ENT_QUOTES, 'UTF-8'); ?>">
<?php if ($page['edit']) { ?><div id="playground-outer"><?php } ?>
<div id="playground">
<?php
foreach ($placed as $item) {
    if ($item['item_type'] === 'stickie') { require './includes/habblet-templates/home-stickie.php'; }
    elseif ($item['item_type'] === 'sticker') { require './includes/habblet-templates/home-sticker.php'; }
}
foreach ([1, 2] as $column) {
    foreach ($columns[$column] as $widget) {
        if (in_array($widget['widget_key'], PhpretroHomes::BLOCKED_WIDGETS, true)) { continue; }
        $page['bypass'] = true;
        require './includes/habblet-templates/home-widget.php';
    }
}
?>
</div>
<?php if ($page['edit']) { ?></div><?php } ?>
<?php require_once './templates/myhabbo_footer.php'; ?>
