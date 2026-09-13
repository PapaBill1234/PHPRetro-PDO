<?php
// FILE: home.php
$page['allow_guests'] = true;
$page['no_column3'] = true;
require_once('./includes/core.php');
require_once('./includes/session.php');
$lang->addLocale("home.homes");

$db = new Database();
$userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$username = isset($_GET['name']) ? trim((string) $_GET['name']) : '';
if ($userId > 0) {
    $profile = $db->fetchRow("SELECT id, username, motto, look, rank, last_online FROM users WHERE id = ? LIMIT 1", [$userId]);
} elseif ($username !== '') {
    $profile = $db->fetchRow("SELECT id, username, motto, look, rank, last_online FROM users WHERE username = ? LIMIT 1", [$username]);
} else {
    $profile = false;
}
if (!$profile) { $lang->clearLocale; require_once('./error.php'); exit; }

$page['id'] = "home";
$page['type'] = "user";
$page['name'] = $input->HoloText($profile['username']);
$page['bodyid'] = "viewmode";
$page['cat'] = "community";
require_once('./templates/community_header.php');
?>
<div id="container"><div id="content" class="clearfix">
<div id="column1" class="column"><div class="habblet-container"><div class="cbb clearfix blue">
<h2 class="title"><?php echo $input->HoloText($profile['username']); ?></h2>
<div class="box-content"><p><?php echo nl2br($input->HoloText($profile['motto'])); ?></p><p><?php echo $input->HoloText($profile['look']); ?></p></div>
</div></div></div>
<div id="column2" class="column"><div class="habblet-container"><div class="cbb clearfix default">
<h2 class="title"><?php echo $lang->loc['pagename.home']; ?></h2>
<div class="box-content"></div>
</div></div></div>
</div></div>
<?php require_once('./templates/community_footer.php'); ?>