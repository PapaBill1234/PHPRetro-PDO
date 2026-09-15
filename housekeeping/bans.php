<?php
$page['dir'] = '\\housekeeping';
$page['housekeeping'] = true;
$page['rank'] = 5;
require_once __DIR__ . '/../includes/core.php';
require_once('./includes/hksession.php');
require_once __DIR__ . '/../includes/AdminAudit.php';
$database = new Database();
// TODO: wrap this batch in a transaction so a mid-loop failure cannot leave partial bans.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bulk_ban') { Csrf::requireValid(); foreach (array_values(array_filter(array_map('intval', (array) ($_POST['user_ids'] ?? [])))) as $bulkUserId) { $target = $database->fetchRow('SELECT id, ip_current, machine_id FROM users WHERE id = ?', [$bulkUserId]); if ($target !== false) { $database->execute('INSERT INTO bans (user_id, ip, machine_id, user_staff_id, timestamp, ban_expire, ban_reason, type, cfh_topic) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', [$bulkUserId, $target['ip_current'], $target['machine_id'], (int) $user->id, time(), (int) ($_POST['ban_expire'] ?? 0), trim((string) ($_POST['ban_reason'] ?? 'Bulk staff action')), 'account', '']); AdminAudit::log($database, (int) $user->id, 'bulk_ban', 'user', $bulkUserId, trim((string) ($_POST['ban_reason'] ?? 'Bulk staff action'))); } } }
$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); $notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'unban') { Csrf::requireValid(); $banId = (int) ($_POST['id'] ?? 0); $database->execute('DELETE FROM bans WHERE id = ?', [$banId]); AdminAudit::log($database, (int) $user->id, 'ban_removed', 'ban', $banId); $notice = 'Ban removed.'; }
$rows = $database->fetchAll('SELECT id, user_id, ip, timestamp, ban_expire, ban_reason, type FROM bans ORDER BY timestamp DESC LIMIT 200');
ob_start(); ?><form method="post"><?php echo Csrf::field(); ?><input type="hidden" name="action" value="bulk_ban"><input name="ban_reason" placeholder="Reason"><input name="ban_expire" type="number" value="0" placeholder="Expiry timestamp"><button>Ban selected</button><table><tr><th>Select</th><th>ID</th><th>User</th><th>IP</th><th>Issued</th><th>Expires</th><th>Reason</th><th>Type</th><th>Action</th></tr><?php foreach ($rows as $row) { ?><tr><td><input type="checkbox" name="user_ids[]" value="<?php echo (int) $row['user_id']; ?>"></td><td><?php echo (int) $row['id']; ?></td><td><?php echo (int) $row['user_id']; ?></td><td><?php echo $e($row['ip']); ?></td><td><?php echo date('Y-m-d H:i', (int) $row['timestamp']); ?></td><td><?php echo (int) $row['ban_expire'] > 0 ? date('Y-m-d H:i', (int) $row['ban_expire']) : 'Permanent'; ?></td><td><?php echo $e($row['ban_reason']); ?></td><td><?php echo $e($row['type']); ?></td><td><form method="post"><?php echo Csrf::field(); ?><input type="hidden" name="action" value="unban"><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>"><button>Remove</button></form></td></tr><?php } ?></table></form><?php $content=ob_get_clean();
$page['name'] = 'Bans'; $page['category'] = 'tools'; require_once('./templates/housekeeping_header.php');
?>
<div class="page_title"><span class="page_name">Bans</span></div><div class="page_main"><div class="center"><?php if ($notice !== '') { ?><div class="clean-ok"><?php echo $e($notice); ?></div><?php } ?><?php echo $content; ?></div></div><?php require_once('./templates/housekeeping_footer.php'); ?>
