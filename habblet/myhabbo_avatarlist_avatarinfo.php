<?php
/*================================================================+\
|| # PHPRetro - An extendable virtual hotel site and management
|+==================================================================
|| # Copyright (C) 2009 Yifan Lu. All rights reserved.
|| # http://www.yifanlu.com
|| # Parts Copyright (C) 2009 Meth0d. All rights reserved.
|| # http://www.meth0d.org
|| # All images, scripts, and layouts
|| # Copyright (C) 2009 Sulake Ltd. All rights reserved.
|+==================================================================
|| # PHPRetro is provided "as is" and comes without
|| # warrenty of any kind. PHPRetro is free software!
|| # License: GNU Public License 3.0
|| # http://opensource.org/licenses/gpl-license.php
\+================================================================*/

require_once(__DIR__.'/../includes/habblet.php');
$lang->addLocale('avatarinfo');
$row = $db->fetchRow('SELECT id, username, look, account_created, online FROM users WHERE id = ?', [habbletInt($_POST, 'anAccountId')]);
if ($row === false) { http_response_code(404); echo 'Account not found.'; return; }
$badge = $db->fetchColumn('SELECT badge_code FROM users_badges WHERE user_id = ? AND slot_id > 0 ORDER BY slot_id, id LIMIT 1', [(int) $row['id']]);
$home = PATH.'/home/'.rawurlencode($row['username']);
?>
<div class="avatar-list-info-container"><div class="avatar-info-basic clearfix">
<div class="avatar-list-info-close-container"><a href="#" class="avatar-list-info-close" id="avatar-list-info-close-<?php echo (int) $row['id']; ?>"></a></div>
<div class="avatar-info-image">
<?php if ($badge !== false && preg_match('/^[A-Za-z0-9_]+$/D', $badge)) { ?>
<img src="<?php echo $input->HoloText($settings->find('site_c_images_path').$settings->find('site_badges_path').$badge.'.gif'); ?>" alt="" />
<?php } ?>
<img src="<?php echo $user->avatarURL($row['look'], 'b,4,4,,1,0'); ?>" alt="<?php echo $input->HoloText($row['username']); ?>" /></div>
<h4><a href="<?php echo $home; ?>"><?php echo $input->HoloText($row['username']); ?></a></h4>
<p><img src="<?php echo PATH; ?>/web-gallery/images/myhabbo/profile/habbo_<?php echo $row['online'] === '0' ? 'offline' : 'online_anim'; ?>.gif" alt="" /></p>
<p><?php echo $lang->loc['created.on']; ?>: <b><?php echo date('M j, Y', (int) $row['account_created']); ?></b></p>
<p><a href="<?php echo $home; ?>" class="arrow"><?php echo $lang->loc['view.habbos.page']; ?></a></p>
</div></div>
