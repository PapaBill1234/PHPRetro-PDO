<?php
$page['allow_guests'] = true; require_once('./includes/core.php'); require_once('./includes/session.php');
$lang->addLocale('community.tags'); $page['id'] = 'tags'; $page['name'] = $lang->loc['pagename.tags']; $page['bodyid'] = 'tags'; $page['cat'] = 'community'; require_once('./templates/community_header.php');
?>
<div id="container"><div id="content" class="clearfix"><div id="column1" class="column"><div class="habblet-container"><div class="cbb clearfix default"><h2 class="title"><?php echo $lang->loc['pagename.tags']; ?></h2><div class="box-content"><p>Tag search, tag clouds, matches, and fights are not available because Polaris has no tags table and no project-owned replacement schema is defined.</p></div></div></div></div></div></div>
<?php require_once('./templates/community_footer.php'); ?>