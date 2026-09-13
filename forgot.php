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
$lang->addLocale("landing.forgot");

$page['name'] = $lang->loc['pagename.forgot.password'];
$page['bodyid'] = "";

session_start();

require_once('./templates/login_header.php');
$db = new Database();
$mailer = new HoloMail;
if (isset($_POST['actionForgot'])) {
    $lang->addLocale("forgot.email");
    $forgotName = trim((string) ($_POST['forgottenpw-username'] ?? ''));
    $forgotMail = trim((string) ($_POST['forgottenpw-email'] ?? ''));
    $account = $db->fetchRow("SELECT id, username, mail FROM users WHERE username = ? AND mail = ? AND mail_verified = '1' LIMIT 1", [$forgotName, $forgotMail]);
    if ($account) {
        $password = bin2hex(random_bytes(8));
        $db->execute("UPDATE users SET password = ? WHERE id = ?", [password_hash($password, PASSWORD_DEFAULT), (int) $account['id']]);
        $success = $lang->loc['forgot.mail.send'];
        $html = '<h1>' . $lang->loc['forgot.mail.header'] . '</h1><p>' . $lang->loc['hello'] . ' <b>' . $input->HoloText($account['username']) . '</b>' . $lang->loc['your.password'] . '<br /><b>' . $password . '</b><br />' . $lang->loc['please.change'] . '</p>';
        $mailer->sendSimpleMessage($account['mail'], $lang->loc['forgot.mail.subject'], $html);
    } else { $result = $lang->loc['forgot.error.invalid']; }
} elseif (isset($_POST['actionList'])) {
    $lang->addLocale("forgot.email");
    $forgotMail = trim((string) ($_POST['ownerEmailAddress'] ?? ''));
    $accounts = $db->fetchAll("SELECT username FROM users WHERE mail = ? ORDER BY username ASC", [$forgotMail]);
    if ($accounts) {
        $safeMail = $input->HoloText($forgotMail);
        $plainText = SHORTNAME . "\n\n" . $lang->loc['list.of.accounts'] . "\n\n" . $lang->loc['hello'] . ' ' . $forgotMail . ",\n\n" . $lang->loc['forgot.email.all.accounts'] . $forgotMail . ":\n";
        $html = $lang->loc['list.of.accounts'] . '<p>' . $lang->loc['hello'] . ' <b>' . $safeMail . '</b></p><p>' . $lang->loc['forgot.email.all.accounts'] . ' <b>' . $safeMail . '</b>:</p><blockquote>';
        foreach ($accounts as $account) {
            $plainText .= $lang->loc['account'] . ': ' . $account['username'] . "\n\n";
            $html .= '<p>' . $lang->loc['account'] . ': <b>' . $input->HoloText($account['username']) . '</b><br /></p>';
        }
        $plainText .= "\n\n\n* * *\n\n" . $lang->loc['forgot.email.footer'];
        $mailer->sendSimpleMessage($forgotMail, $lang->loc['forgot.name.subject'], $html . '</blockquote>', $plainText);
        $success = $lang->loc['forgot.mail.send'];
    } else { $result2 = $lang->loc['forgot.error.invalid']; }
}

if(!isset($success)){
?>
<style type="text/css">
		div.left-column { float: left; width: 50% }
		div.right-column { float: right; width: 49% }
		label { display: block }
		input { width: 98% }
		input.process-button { width: auto; float: right }
	</style>

			<div id="process-content">
	        	<div class="left-column">
<div class="cbb clearfix">
    <h2 class="title"><?php echo $lang->loc['forgot.pass']; ?></h2>
    <div class="box-content">
	<?php if(!empty($result)){ ?>
	    <div class="rounded rounded-red">
                <?php echo $result; ?> <br />
        </div>
        <div class="clear"></div>
	<?php } ?>

        <p><?php echo $lang->loc['forgot.pass.content']; ?></p>

        <div class="clear"></div>

        <form method="post" action="forgot" id="forgottenpw-form">
            <p>
            <label for="forgottenpw-username"><?php echo $lang->loc['forgot.username']; ?></label>
            <input type="text" name="forgottenpw-username" id="forgottenpw-username" value="" />
            </p>

            <p>
            <label for="forgottenpw-email"><?php echo $lang->loc['forgot.email']; ?></label>
            <input type="text" name="forgottenpw-email" id="forgottenpw-email" value="" />
            </p>

            <p>
            <input type="submit" value="<?php echo $lang->loc['forgot.button']; ?>" name="actionForgot" class="submit process-button" id="forgottenpw-submit" />
            </p>
            <input type="hidden" value="default" name="origin" />
        </form>
    </div>
</div>

</div>


<div class="right-column">

<div class="cbb clearfix">
    <h2 class="title"><?php echo $lang->loc['forgot.name']; ?></h2>
    <div class="box-content">
	<?php if(!empty($result2)){ ?>
	    <div class="rounded rounded-red">
                <?php echo $result2; ?> <br />
        </div>
        <div class="clear"></div>
	<?php } ?>

        <p><?php echo $lang->loc['forgot.name.message']; ?></p>

        <div class="clear"></div>

        <form method="post" action="forgot" id="accountlist-form">
            <p>

            <label for="accountlist-owner-email"><?php echo $lang->loc['forgot.email']; ?></label>
            <input type="text" name="ownerEmailAddress" id="accountlist-owner-email" value="" />
            </p>

            <p>
            <input type="submit" value="<?php echo $lang->loc['forgot.button.get.accounts']; ?>" name="actionList" class="submit process-button" id="accountlist-submit" />
            </p>
            <input type="hidden" value="default" name="origin" />
        </form>

    </div>
</div>

<div class="cbb clearfix">
    <h2 class="title"><?php echo $lang->loc['forgot.false.alarm']; ?></h2>
    <div class="box-content">
        <p><?php echo $lang->loc['forgot.false.alarm.content']; ?></p>
        <p><a href="<?php echo PATH; ?>/"><?php echo $lang->loc['forgot.back']; ?> &raquo;</a></p>
    </div>
</div>

</div>

<?php

}else{
?>
			<div id="process-content">
	        	<div class="cbb clearfix">
    <h2 class="title"><?php echo $lang->loc['forgot.success.header']; ?></h2>
    <div class="box-content">
    <p><?php echo $success; ?></p>
    <p><a href="<?php echo PATH; ?>/">Back to homepage &raquo;</a></p>

    </div>
</div>

<?php
}
require('./templates/login_footer.php');

?>
