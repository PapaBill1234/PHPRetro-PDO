<?php
$page['allow_guests'] = true;
require_once('./includes/core.php');
require_once('./includes/PhpretroHelpdesk.php');
$lang->addLocale('iot');
$lang->addLocale('iot.errors');
$lang->addLocale('iot.step1');
$lang->addLocale('iot.step3');
$lang->addLocale('iot.step-1');
$error = '';
$sent = false;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    Csrf::requireValid();
    $username = trim((string) ($_POST['username'] ?? ($user->logged_in ? ($user->name ?? '') : '')));
    $email = trim((string) ($_POST['email'] ?? ''));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));
    $roomId = (int) ($_POST['roomid'] ?? 0);
    try {
        $actor = !empty($user->logged_in) ? (int) $user->id : 0;
        phpretroHelpdesk()->submit($actor > 0 ? $actor : null, $username, $email, (string) ($_SERVER['REMOTE_ADDR'] ?? ''), $subject, $message, $roomId);
        $sent = true;
    } catch (PhpretroHelpdeskError $exception) {
        $error = $exception->getMessage();
    }
}
require_once('./templates/iot_header.php');
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div id="main-content-process"><div class="portlet"><div class="portlet-body-process"><div class="imaindiv">
<?php if ($sent) { ?>
<h2><?php echo $escape($lang->loc['thank.you']); ?></h2>
<p><?php echo $escape($lang->loc['thank.you.message']); ?></p>
<?php } else { ?>
<h2><?php echo $escape($lang->loc['help.tool']); ?></h2>
<p><?php echo $escape($lang->loc['step.3.info']); ?></p>
<?php if ($error !== '') { ?><p class="error"><?php echo $escape($error); ?></p><?php } ?>
<form method="post" action="<?php echo PATH; ?>/iot/go"><?php echo Csrf::field(); ?>
<p><label><?php echo $escape($lang->loc['name'] ?? SHORTNAME.' name'); ?></label><br>
<input name="username" maxlength="25" value="<?php echo $escape($_POST['username'] ?? (!empty($user->logged_in) ? ($user->name ?? '') : '')); ?>"></p>
<p><label><?php echo $escape($lang->loc['email']); ?></label><br>
<input name="email" maxlength="255" value="<?php echo $escape($_POST['email'] ?? ''); ?>"></p>
<p><label><?php echo $escape($lang->loc['subject']); ?></label><br>
<input name="subject" maxlength="50" value="<?php echo $escape($_POST['subject'] ?? ''); ?>"></p>
<p><label><?php echo $escape($lang->loc['message']); ?></label><br>
<textarea name="message" rows="8" cols="50"><?php echo $escape($_POST['message'] ?? ''); ?></textarea></p>
<p><button type="submit"><?php echo $escape($lang->loc['send']); ?></button></p>
</form>
<?php } ?>
</div></div></div></div>
<?php require_once('./templates/iot_footer.php'); ?>
