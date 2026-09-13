<?php
$page['allow_guests'] = false; require_once('./includes/core.php'); require_once('./includes/session.php');
$database = new Database(); $profile = $database->fetchRow('SELECT username, motto, look, gender, credits, pixels, points, last_login FROM users WHERE id = ?', [$user->id]);
if ($profile === false) { http_response_code(404); exit('Account not found.'); }
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$page['id'] = 'me'; $page['name'] = 'My profile'; $page['bodyid'] = 'home'; $page['cat'] = 'community'; require_once('./templates/community_header.php');
?>
<div id="container"><div id="content" class="clearfix"><div id="column1" class="column"><div class="habblet-container"><div class="cbb clearfix default"><h2 class="title">Welcome, <?php echo $escape($profile['username']); ?></h2><div class="box-content"><p><?php echo $escape($profile['motto']); ?></p><ul><li>Credits: <?php echo (int) $profile['credits']; ?></li><li>Pixels: <?php echo (int) $profile['pixels']; ?></li><li>Points: <?php echo (int) $profile['points']; ?></li><li>Last login: <?php echo (int) $profile['last_login'] > 0 ? date('Y-m-d H:i', (int) $profile['last_login']) : 'Never'; ?></li></ul><p><a href="<?php echo PATH; ?>/profile">Edit profile</a></p></div></div></div></div></div></div>
<?php require_once('./templates/community_footer.php'); ?>