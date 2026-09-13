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

require_once('./includes/core.php');
$lang->addLocale("landing.email");

$page['name'] = $lang->loc['pagename.email.verify'];
$page['bodyid'] = "";

session_start();

require_once('./templates/login_header.php');

$db = new Database();
$sucess = "0";
if (isset($_GET['token'])) {
    $token = (string) $_GET['token'];
    if (preg_match('/^[a-f0-9]{64}$/', $token)) {
        $now = time();
        $tokenRow = $db->fetchRow(
            "SELECT id, user_id FROM phpretro_email_verification_tokens WHERE token_hash = ? AND expires_at > ? AND used_at IS NULL LIMIT 1",
            [hash('sha256', $token), $now]
        );
        if ($tokenRow && $db->execute(
            "UPDATE phpretro_email_verification_tokens SET used_at = ? WHERE id = ? AND used_at IS NULL",
            [$now, (int) $tokenRow['id']]
        ) === 1) {
            $db->execute("UPDATE users SET mail_verified = '1' WHERE id = ?", [(int) $tokenRow['user_id']]);
            $sucess = "1";
        }
    }
}

if($sucess == "1"){
?>
			<div id="process-content">
	        	<div id ="email-verified-container">
    <div class="cbb clearfix green">
        <h2 class="title heading"><?php echo $lang->loc['email.verify.success']; ?></h2>
    	<div class="box-content">

            <ul>
	            <li><?php echo $lang->loc['email.verify.success.message']; ?></li>
            </ul>
            <a href="<?php echo PATH; ?>/"><?php echo $lang->loc['continue.verify']; ?></a>
    	</div>
    </div>
</div>
<?php }elseif($sucess == "2"){ ?>
			<div id="process-content">
	        	<div id ="email-verified-container">
    <div class="cbb clearfix green">
        <h2 class="title heading"><?php echo $lang->loc['email.verify.success.message']; ?></h2>
    	<div class="box-content">

            <ul>
	            <li><?php echo $lang->loc['email.verify.removed']; ?></li>
            </ul>
            <a href="<?php echo PATH; ?>/"><?php echo $lang->loc['continue.verify']; ?></a>
    	</div>
    </div>
</div>
<?php }else{ ?>
			<div id="process-content">
	        	<div id ="email-verified-container">
    <div class="cbb clearfix red">
        <h2 class="title heading"><?php echo $lang->loc['email.verify.error']; ?></h2>

    	<div class="box-content">
            <ul>
	            <li><?php echo $lang->loc['email.verify.error.message']; ?></li>
            </ul>
            <a href="<?php echo PATH; ?>/"><?php echo $lang->loc['continue.verify']; ?></a>
    	</div>
    </div>
</div>
<?php }
require_once('./templates/login_footer.php');
?>