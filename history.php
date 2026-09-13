<?php
// FILE: history.php
require_once('./includes/core.php');
require_once('./includes/session.php');
$lang->addLocale("credits.history");
$page['id'] = "history";
$page['name'] = $lang->loc['pagename.history'];
$page['bodyid'] = "home";
$page['cat'] = "credits";
require_once('./templates/community_header.php');
?>
<div id="container"><div id="content" class="clearfix"><div id="column1" class="column">
<div class="habblet-container"><div class="cbb clearfix default"><h2 class="title"><?php echo $lang->loc['pagename.history']; ?></h2>
<div class="box-content"><p><?php echo $lang->loc['pagename.history']; ?></p></div>
</div></div></div></div></div>
<?php require_once('./templates/community_footer.php'); ?>