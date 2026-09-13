<?php
// FILE: help.php
$page['allow_guests'] = true;
require_once('./includes/core.php');
$lang->addLocale("community.help");
$page['id'] = "help";
$page['name'] = $lang->loc['pagename.help'];
$page['bodyid'] = "home";
$page['cat'] = "community";
require_once('./templates/community_header.php');
?>
<div id="container"><div id="content" class="clearfix"><div id="column1" class="column">
<div class="habblet-container"><div class="cbb clearfix default"><h2 class="title"><?php echo $lang->loc['pagename.help']; ?></h2>
<div class="box-content"><p><?php echo $lang->loc['pagename.help']; ?></p></div>
</div></div></div></div></div>
<?php require_once('./templates/community_footer.php'); ?>