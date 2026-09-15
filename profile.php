<?php
$page['allow_guests'] = false; require_once('./includes/core.php'); require_once('./includes/session.php');
$database = new Database(); $notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $motto = trim((string) ($_POST['motto'] ?? '')); $look = trim((string) ($_POST['look'] ?? '')); $gender = (string) ($_POST['gender'] ?? 'M');
    if (mb_strlen($motto) > 127 || mb_strlen($look) > 256 || !in_array($gender, ['M', 'F'], true)) { $notice = 'Invalid profile details.'; }
    else { $database->execute('UPDATE users SET motto = ?, look = ?, gender = ? WHERE id = ?', [$motto, $look, $gender, $user->id]); $notice = 'Profile updated.'; }
}
$profile = $database->fetchRow('SELECT username, mail, motto, look, gender FROM users WHERE id = ?', [$user->id]);
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$page['id'] = 'profile'; $page['name'] = 'Edit profile'; $page['bodyid'] = 'home'; $page['cat'] = 'home'; require_once('./templates/community_header.php');
?>
<div id="container"><div id="content" class="clearfix"><div id="column1" class="column"><div class="habblet-container"><div class="cbb clearfix default"><h2 class="title">Edit profile</h2><div class="box-content"><?php if ($notice !== '') { ?><p><?php echo $escape($notice); ?></p><?php } ?><p>Account: <?php echo $escape($profile['username']); ?></p><p>Email: <?php echo $escape($profile['mail']); ?></p><form method="post"><?php echo Csrf::field(); ?><label>Motto</label><br><input name="motto" maxlength="127" value="<?php echo $escape($profile['motto']); ?>"><br><label>Figure</label><br><input name="look" maxlength="256" value="<?php echo $escape($profile['look']); ?>"><br><label>Gender</label><br><select name="gender"><option value="M"<?php echo $profile['gender'] === 'M' ? ' selected' : ''; ?>>M</option><option value="F"<?php echo $profile['gender'] === 'F' ? ' selected' : ''; ?>>F</option></select><br><button type="submit">Save</button></form></div></div></div></div></div></div>
<?php require_once('./templates/community_footer.php'); ?>