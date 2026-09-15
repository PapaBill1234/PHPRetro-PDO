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

require_once __DIR__.'/../includes/habblet.php';
habbletRequireUser();
require_once __DIR__.'/../includes/PhpretroMinimail.php';
$mail = phpretroMinimail();
$lang->addLocale('minimail.report.confirm');
$lang->addLocale('ajax.buttons');
$row = phpretroMinimailRun(static fn() => $mail->message(habbletInt($_POST, 'messageId')));
if ($row === null) { return; }
if ((int) $row['sender_id'] === (int) $user->id) {
?>
<ul class="error"><li><?php echo $lang->loc['report.error.1']; ?></li></ul>
<p><a href="#" class="new-button cancel-report"><b><?php echo $lang->loc['cancel']; ?></b><i></i></a></p>
<?php
    return;
}
$sender = $mail->user((int) $row['sender_id']);
?>
<p><?php echo $lang->loc['report.1']; ?> <b><?php echo $input->HoloText($row['subject']); ?></b> <?php echo $lang->loc['report.2']; ?> <b><?php echo $input->HoloText($sender['username'] ?? ''); ?></b> <?php echo $lang->loc['report.3']; ?></p>
<p>
<a href="#" class="new-button cancel-report"><b><?php echo $lang->loc['cancel']; ?></b><i></i></a>
<a href="#" class="new-button send-report"><b><?php echo $lang->loc['send.report']; ?></b><i></i></a>
</p>
