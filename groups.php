<?php
$page['allow_guests'] = true;
$page['no_column3'] = true;
$page['discussion'] = false;
$page['concurrent_editing'] = false;
require_once('./includes/core.php');
require_once('./includes/session.php');
require_once('./includes/habblet.php');
require_once('./includes/habblet_groups.php');
require_once('./includes/PhpretroGroupUrls.php');
require_once('./includes/PhpretroHomes.php');
$lang->addLocale("home.homes");
$lang->addLocale("community.groups");
if (!function_exists('millisecondsToMinutes')) {
    function millisecondsToMinutes($int) { return (int) floor(((int) $int) / 60000); }
}

$db = new Database();
$homes = phpretroHomes();
$groupId = phpretroRequestGuildId();
$guild = $groupId > 0 ? $db->fetchRow(
    "SELECT g.id, g.user_id, g.name, g.description, g.room_id, g.state, g.rights, g.badge, g.date_created, u.username AS owner_username, u.rank AS owner_rank FROM guilds g LEFT JOIN users u ON u.id = g.user_id WHERE g.id = ? LIMIT 1",
    [$groupId]
) : false;
if (!$guild) { $lang->clearLocale; require_once('./error.php'); exit; }

$membership = $user->id != "0" ? $db->fetchRow(
    "SELECT level_id FROM guilds_members WHERE guild_id = ? AND user_id = ? LIMIT 1",
    [(int) $guild['id'], (int) $user->id]
) : false;

$isOwner = (int) $user->id === (int) $guild['user_id'];
$page['edit'] = $isOwner && ((int) ($_SESSION['group_page_edit'] ?? 0) === (int) $guild['id']);
$page['id'] = "home";
$page['type'] = "groups";
$page['name'] = $input->HoloText($guild['name']) . $lang->loc['pagename.groups'];
$page['bodyid'] = $page['edit'] ? "editmode" : "viewmode";
$page['cat'] = "community";
$grouprow = [(int) $guild['id'], $guild['name']];
$userrow = [(int) $guild['user_id'], $guild['owner_username'] ?: '', (int) ($guild['owner_rank'] ?? 1)];
$memberrow = [0, 0, $isOwner ? 3 : ((int) ($membership['level_id'] ?? -1) === 1 ? 2 : 0)];
$timeout = ['expire' => 1800000, 'twominutes' => 0];
require_once('./templates/community_header.php');
$columns = [1 => [], 2 => []];
foreach ($homes->displayGroupLayouts((int) $guild['id']) as $row) {
    $col = ((int) $row['column_number'] === 2) ? 2 : 1;
    $columns[$col][] = $row;
}
$placed = $homes->placedItems(0, (int) $guild['id']);
$background = $homes->backgroundClass(0, (int) $guild['id']);
?>
<div id="mypage-wrapper" class="cbb blue">
<div class="box-tabs-container box-tabs-left clearfix">
<?php if (!$page['edit'] && $isOwner) { ?>
	<a href="<?php echo PATH; ?>/groups/actions/startEditingSession/<?php echo (int) $guild['id']; ?>" id="edit-button" class="new-button dark-button edit-icon" style="float:left"><b><span></span><?php echo $lang->loc['edit'] ?? 'Edit'; ?></b><i></i></a>
<?php } ?>
	<h2 class="page-owner"><?php echo $input->HoloText($guild['name']); ?></h2>
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
        require './includes/habblet-templates/group-widget.php';
    }
}
?>
</div>
<?php if ($page['edit']) { ?></div><?php } ?>
<?php require_once('./templates/myhabbo_footer.php'); ?>
