<?php
// FILE: groups.php
$page['allow_guests'] = true;
$page['no_column3'] = true;
require_once('./includes/core.php');
require_once('./includes/session.php');
$lang->addLocale("home.homes");
$lang->addLocale("community.groups");

$db = new Database();
$groupId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$guild = $groupId > 0 ? $db->fetchRow(
    "SELECT g.id, g.user_id, g.name, g.description, g.room_id, g.state, g.rights, g.badge, g.date_created, u.username AS owner_username FROM guilds g LEFT JOIN users u ON u.id = g.user_id WHERE g.id = ? LIMIT 1",
    [$groupId]
) : false;
if (!$guild) { $lang->clearLocale; require_once('./error.php'); exit; }

$membership = $user->id != "0" ? $db->fetchRow(
    "SELECT level_id FROM guilds_members WHERE guild_id = ? AND user_id = ? LIMIT 1",
    [(int) $guild['id'], (int) $user->id]
) : false;

$page['id'] = "home";
$page['type'] = "groups";
$page['name'] = $input->HoloText($guild['name']) . $lang->loc['pagename.groups'];
$page['bodyid'] = "viewmode";
$page['cat'] = "community";
require_once('./templates/community_header.php');
?>
<div id="container"><div id="content" class="clearfix">
<div id="column1" class="column"><div class="habblet-container"><div class="cbb clearfix blue">
<h2 class="title"><?php echo $input->HoloText($guild['name']); ?></h2>
<div class="box-content">
<?php if ($guild['badge'] !== '') { ?><p><img src="<?php echo $input->HoloText($guild['badge']); ?>" alt="" /></p><?php } ?>
<p><?php echo nl2br($input->HoloText($guild['description'])); ?></p>
<p><?php echo $lang->loc['page.owner']; ?>: <?php echo $input->HoloText($guild['owner_username'] ?: ''); ?></p>
<?php if ($membership) { ?><p><?php echo $lang->loc['member']; ?> (<?php echo (int) $membership['level_id']; ?>)</p><?php } ?>
</div></div></div></div>
<div id="column2" class="column"><div class="habblet-container"><div class="cbb clearfix default">
<h2 class="title"><?php echo $lang->loc['pagename.groups']; ?></h2>
<div class="box-content"><p><?php echo $lang->loc['groups.desc']; ?></p></div>
</div></div></div>
</div></div>
<?php require_once('./templates/community_footer.php'); ?>