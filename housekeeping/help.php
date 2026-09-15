<?php
$page['dir'] = '\\housekeeping';
$page['housekeeping'] = true;
$page['rank'] = 5;
require_once('../includes/core.php');
require_once('./includes/hksession.php');
require_once('../includes/PhpretroHelpdesk.php');
require_once('../includes/AdminAudit.php');
$lang->addLocale('housekeeping.help');
$lang->addLocale('housekeeping.help.display');
$lang->addLocale('housekeeping.help.remove');
$desk = phpretroHelpdesk();
$action = $_GET['do'] ?? 'list';
$notice = '';
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'remove') { Csrf::requireValid();
    $id = (int) ($_POST['id'] ?? 0);
    $desk->remove($id);
    AdminAudit::log(new Database(), (int) $user->id, 'help_remove', 'helpdesk', $id);
    $notice = $lang->loc['message.help.removed'] ?? 'Ticket removed.';
    $action = 'list';
} elseif ($action === 'pickup') {
    $id = (int) ($_GET['id'] ?? 0);
    try {
        $desk->pickup($id, (int) $user->id);
        AdminAudit::log(new Database(), (int) $user->id, 'help_pickup', 'helpdesk', $id);
        $notice = $lang->loc['message.help.picked.up'] ?? 'Ticket picked up.';
    } catch (PhpretroHelpdeskError $error) {
        $notice = $error->getMessage();
    }
    $action = 'list';
}
$page['name'] = $lang->loc['pagename.help'] ?? 'Help';
$page['category'] = 'users';
require_once('./templates/housekeeping_header.php');
$rows = $desk->list();
$removeId = (int) ($_GET['id'] ?? 0);
?>
<div class="page_title"><span class="page_name"><?php echo $escape($page['name']); ?></span></div>
<div class="page_main"><div class="center">
<?php if ($notice !== '') { ?><div class="clean-ok"><?php echo $escape($notice); ?></div><?php } ?>
<?php if ($action === 'remove' && $removeId > 0) { ?>
<form method="post" action="<?php echo PATH; ?>/housekeeping/help?do=remove"><?php echo Csrf::field(); ?>
<input type="hidden" name="id" value="<?php echo $removeId; ?>">
<p>Remove this ticket?</p>
<button type="submit" name="remove" value="1"><?php echo $escape($lang->loc['remove'] ?? 'Remove'); ?></button>
</form>
<?php } else { ?>
<table>
<tr><th>Subject</th><th>Message</th><th>Username</th><th>Email</th><th>Date</th><th>Status</th><th>Actions</th></tr>
<?php foreach ($rows as $row) { ?>
<tr>
<td><?php echo $escape($row['subject']); ?></td>
<td><?php echo $escape(mb_substr((string) $row['message'], 0, 120)); ?></td>
<td><?php echo $escape($row['username']); ?></td>
<td><?php echo $escape($row['email']); ?></td>
<td><?php echo date('n/j/Y g:i A', (int) $row['created_at']); ?></td>
<td><?php echo $escape($row['status']); ?></td>
<td>
<a href="<?php echo PATH; ?>/housekeeping/help?do=pickup&id=<?php echo (int) $row['id']; ?>">Pick up</a>
<a href="<?php echo PATH; ?>/housekeeping/help?do=remove&id=<?php echo (int) $row['id']; ?>">Remove</a>
</td>
</tr>
<?php } ?>
</table>
<?php } ?>
</div></div>
<?php require_once('./templates/housekeeping_footer.php'); ?>
