<?php
$page['allow_guests'] = true; require_once('./includes/core.php'); require_once('./includes/session.php');
$lang->addLocale('credits.club'); $lang->addLocale('credits.testwardrobe');
$profile = null; if ($user->id > 0) { $database = new Database(); $profile = $database->fetchRow('SELECT look, gender FROM users WHERE id = ?', [$user->id]); }
$page['id'] = 'tryout'; $page['name'] = $lang->loc['pagename.club']; $page['bodyid'] = 'home'; $page['cat'] = 'credits'; require_once('./templates/community_header.php');
?>
<div id="container"><div id="content" class="clearfix"><div id="column1" class="column"><div class="habblet-container"><div class="cbb clearfix red"><h2 class="title"><?php echo $lang->loc['test.wardrobe']; ?></h2><div class="box-content"><p><?php echo $lang->loc['test.club'][0]; ?></p><?php if ($profile !== null) { ?><p>Your current figure: <?php echo HoloText($profile['look']); ?> (<?php echo HoloText($profile['gender']); ?>)</p><?php } else { ?><p><?php echo $lang->loc['please.sign.in.club']; ?></p><?php } ?><p><a href="<?php echo PATH; ?>/profile"><?php echo $lang->loc['test.club'][2]; ?></a></p></div></div></div></div></div></div>
<?php require_once('./templates/community_footer.php'); ?>