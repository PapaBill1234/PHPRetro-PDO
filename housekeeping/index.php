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

$page['dir'] = '\housekeeping';
$page['housekeeping'] = true;
$page['rank'] = 5;
require_once __DIR__ . '/../includes/core.php';
require_once __DIR__ . '/../includes/Totp.php';
require_once __DIR__ . '/../includes/AdminAudit.php';

if($user->id > 0){ header('Location: '.PATH.'/housekeeping/dashboard'); exit; }

$username = '';
if(!isset($_SESSION['login'])){
	$_SESSION['login']['enabled'] = true;
	$_SESSION['login']['tries'] = 0;
}

$lang->addLocale("housekeeping.login");

if(!empty($_POST['username'])){
	Csrf::requireValid();
	$username = trim((string) ($_POST['username'] ?? ''));
	$password = (string) ($_POST['password'] ?? '');
	
	$user = new HoloUser($username,$password,true,false);
	
	$lang->addLocale("landing.login");
	
	if($user->error > 0){
		$login_error = $lang->loc['error.'.$user->error];
		if($user->error == 3){
			$login_error = $lang->loc['banned.1'].$user->banned['reason'].$lang->loc['banned.2'].$user->banned['expire'].".";
		}
	}
	if(($_SESSION['login']['tries'] ?? 0) > 4 && $settings->find("site_capcha") == "1"){
		if(((($_SESSION['register-captcha-bubble'] ?? '') == strtolower((string) ($_POST['bean_captchaResponse'] ?? $_POST['captcha'] ?? ''))) && !empty($_SESSION['register-captcha-bubble'])) || $settings->find("site_capcha") == "0") {
			unset($_SESSION['register-captcha-bubble']);
		}else{
			$login_error = $lang->loc['error.captcha'] ?? 'Invalid captcha code.';
		}
	}
	if(empty($login_error)){
        $phase5bDb = new Database();
        $twoFactorRank = (int) ($phase5bDb->fetchColumn('SELECT setting_value FROM phpretro_site_settings WHERE setting_key = ?', ['staff_2fa_rank']) ?: 5);
        $totp = $phase5bDb->fetchRow('SELECT secret_base32, enabled FROM phpretro_staff_totp WHERE user_id = ?', [(int) $user->id]);
        if ((int) $user->user('rank') >= $twoFactorRank && (!$totp || (int) $totp['enabled'] !== 1)) {
            $_SESSION['staff_2fa_pending_user'] = $user; $_SESSION['staff_2fa_pending_at'] = time(); unset($_SESSION['login']);
            header('Location: '.PATH.'/housekeeping/twofactor'); exit;
        } elseif ((int) $user->user('rank') >= $twoFactorRank && !Totp::verify($totp['secret_base32'], trim((string) ($_POST['totp_code'] ?? '')))) {
            $login_error = 'Invalid authenticator code.';
        } else {
            unset($_SESSION['login']); $_SESSION['hk_user'] = $user;
            $hash = hash('sha256', session_id()); $phase5bDb->execute('INSERT INTO phpretro_staff_sessions (user_id, session_hash, ip, created_at, last_activity) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE last_activity = VALUES(last_activity), ip = VALUES(ip), revoked_at = NULL', [(int) $user->id, $hash, $_SERVER['REMOTE_ADDR'] ?? '', time(), time()]);
            AdminAudit::log($phase5bDb, (int) $user->id, 'staff_login', 'staff_session', null);
            header('Location: '.PATH.'/housekeeping/dashboard'); exit;
        }
	}
    if(!empty($login_error)){
		$_SESSION['login']['tries'] = (int) ($_SESSION['login']['tries'] ?? 0) + 1;
	}
}

$page['name'] = $lang->loc['pagename.login'];
$page['category'] = "login";
require_once('./templates/housekeeping_header.php');
?>
<div class="page_title">
 <img src="<?php echo PATH; ?>/housekeeping/images/icons/cfh.gif" class="pticon">
 <span class="page_name_shadow"><?php echo $lang->loc['pagename.login']; ?></span>

 <span class="page_name"><?php echo $lang->loc['pagename.login']; ?></span>
</div>
<div class="page_main">

<table border="0" cellpadding="0" cellspacing="0" height="100%">
<tbody>
<tr height="100%">
<td class="page_main_left">
<div class="left_date"><?php echo date('l F j, Y | g:iA'); ?></div>
<div class="hr"></div>
<div class="loginuser"><?php echo $lang->loc['please.log.in']; ?></div>
<div class="text">
<form id="loginform" action="<?php echo PATH; ?>/housekeeping/" method="post"><?php echo Csrf::field(); ?>
<strong><?php echo $lang->loc['username']; ?>:</strong><br />
<input type="text" size="20" name="username" id="namefield" value="<?php echo $input->HoloText($username); ?>" /><br />
<strong><?php echo $lang->loc['password']; ?>:</strong><br />
<input type="password" size="20" name="password" value="" /><br />
<strong>Authenticator code (staff):</strong><br />
<input type="text" size="20" name="totp_code" inputmode="numeric" maxlength="6" value="" />
<?php if(($_SESSION['login']['tries'] ?? 0) > 4 && $settings->find("site_capcha") == "1"){ ?>
<strong><?php echo $lang->loc['captcha']; ?>:</strong><br />
<img id="captcha" src="<?php echo PATH; ?>/captcha.jpg?t=<?php echo time(); ?>" alt="" width="200" height="50" /><br /><br />
<input type="text" name="captcha" id="captcha-code" value="" /><br />
<?php } ?>
<div class="button left"><input type="submit" value="<?php echo $lang->loc['submit']; ?>"></input></div>
</form>
</div>
<div class="hr"></div>
<div class="text">
<?php echo $lang->loc['if.forgot.password']; ?> <a href="<?php echo PATH; ?>/account/password/forgot"><?php echo $lang->loc['recovery.tool']; ?></a> <?php echo $lang->loc['contact.admin']; ?>
</div>
 </td>
 <td class="page_main_right">
<?php if(!empty($login_error)){ ?>
	<div class="center">
		<div class="clean-error"><?php echo $login_error; ?></div>
	</div>
<?php } ?>
  <div class="login_top">
  <img src="<?php echo PATH; ?>/housekeeping/images/workman_habbo_down.gif" /><br />
  PHPRetro Version <?php echo $version['version']." ".$version['status']; ?>
  </div>
 </td>
</tr>
</tbody>
</table>

</div>
<?php require_once('./templates/housekeeping_footer.php'); ?>