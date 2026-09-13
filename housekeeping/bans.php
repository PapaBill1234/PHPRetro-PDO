<?php
$page['dir'] = '\\housekeeping';
$page['housekeeping'] = true;
$page['rank'] = 5;
require_once('../includes/core.php');
require_once('./includes/hksession.php');
require_once('../includes/AdminAudit.php');
$database = new Database(); $e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); $notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'unban') { $banId = (int) ($_POST['id'] ?? 0); $database->execute('DELETE FROM bans WHERE id = ?', [$banId]); AdminAudit::log($database, (int) $user->id, 'ban_removed', 'ban', $banId); $notice = 'Ban removed.'; }
$rows = $database->fetchAll('SELECT id, user_id, ip, timestamp, ban_expire, ban_reason, type FROM bans ORDER BY timestamp DESC LIMIT 200');
ob_start(); ?><table><tr><th>ID</th><th>User</th><th>IP</th><th>Issued</th><th>Expires</th><th>Reason</th><th>Type</th><th>Action</th></tr><?php foreach ($rows as $row) { ?><tr><td><?php echo (int) $row['id']; ?></td><td><?php echo (int) $row['user_id']; ?></td><td><?php echo $e($row['ip']); ?></td><td><?php echo date('Y-m-d H:i', (int) $row['timestamp']); ?></td><td><?php echo (int) $row['ban_expire'] > 0 ? date('Y-m-d H:i', (int) $row['ban_expire']) : 'Permanent'; ?></td><td><?php echo $e($row['ban_reason']); ?></td><td><?php echo $e($row['type']); ?></td><td><form method="post"><input type="hidden" name="action" value="unban"><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>"><button>Remove</button></form></td></tr><?php } ?></table><?php $content=ob_get_clean();
$page['name'] = 'Bans'; $page['category'] = 'tools'; require_once('./templates/housekeeping_header.php');
?>
<div class="page_title"><span class="page_name">Bans</span></div><div class="page_main"><div class="center"><?php if ($notice !== '') { ?><div class="clean-ok"><?php echo $e($notice); ?></div><?php } ?><?php echo $content; ?></div></div><?php require_once('./templates/housekeeping_footer.php'); ?>
