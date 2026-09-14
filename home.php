<?php
$page['allow_guests'] = true;
$page['no_column3'] = true;
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
$page['edit'] = ((int) $user->id === (int) $profile['id']) && (($_SESSION['page_edit'] ?? '') === 'home');
$page['id'] = 'home';
$page['type'] = 'user';
$page['name'] = $input->HoloText($profile['username']);
$page['bodyid'] = $page['edit'] ? 'editmode' : 'viewmode';
$page['cat'] = 'community';
require_once './templates/community_header.php';
$columns = [1 => [], 2 => []];
foreach ($homes->displayLayouts((int) $profile['id']) as $row) {
    $col = ((int) $row['column_number'] === 2) ? 2 : 1;
    $columns[$col][] = $row;
}
?>
<div id="container"><div id="content" class="clearfix">
<?php if ((int) $user->id === (int) $profile['id']) { ?>
<p class="home-edit">
<?php if ($page['edit']) { ?>
<a href="<?php echo PATH; ?>/myhabbo/save">Save</a> · <a href="<?php echo PATH; ?>/myhabbo/cancel/<?php echo (int) $profile['id']; ?>">Cancel</a>
<?php } else { ?>
<a href="<?php echo PATH; ?>/myhabbo/startSession/<?php echo (int) $profile['id']; ?>">Edit home</a>
<?php } ?>
</p>
<?php } ?>
<?php foreach ([1, 2] as $column) { ?>
<div id="column<?php echo $column; ?>" class="column">
<?php foreach ($columns[$column] as $widget) {
    if (in_array($widget['widget_key'], PhpretroHomes::BLOCKED_WIDGETS, true)) { continue; }
    if ((int) $widget['id'] === 0) {
        echo '<div class="habblet-container"><div class="cbb clearfix blue"><h2 class="title">'.$input->HoloText($profile['username']).'</h2><div class="box-content"><p>'.$input->HoloText($profile['motto']).'</p></div></div></div>';
        continue;
    }
    $page['bypass'] = true;
    require './includes/habblet-templates/home-widget.php';
} ?>
</div>
<?php } ?>
</div></div>
<?php require_once './templates/community_footer.php'; ?>
