<?php
$page['dir'] = '\\housekeeping';
$page['housekeeping'] = true;
$page['rank'] = 5;
require_once('../includes/core.php');
require_once('./includes/hksession.php');
require_once('../includes/AdminAudit.php');
require_once('../includes/PhpretroPolarisCms.php');
$database = new Database(); $e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); $notice = ''; $action = $_GET['do'] ?? 'list';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = (int) ($_POST['id'] ?? 0);
  $affected = 0;
  if ($action === 'delete') { $affected = $database->execute('DELETE FROM vouchers WHERE id = ?', [$id]); $notice = $affected > 0 ? 'Voucher removed.' : 'Voucher not found.'; }
  else {
    $v = [trim((string) ($_POST['code'] ?? '')), (int) ($_POST['credits'] ?? 0), (int) ($_POST['points'] ?? 0), (int) ($_POST['points_type'] ?? 0), (int) ($_POST['catalog_item_id'] ?? 0), max(1, (int) ($_POST['amount'] ?? 1)), (int) ($_POST['redemption_limit'] ?? -1)];
    if ($v[0] === '' || strlen($v[0]) > 10) { $notice = 'Code is required and must be no longer than 10 characters.'; }
    elseif ($id > 0) { $affected = $database->execute('UPDATE vouchers SET code=?, credits=?, points=?, points_type=?, catalog_item_id=?, amount=?, `limit`=? WHERE id=?', [$v[0],$v[1],$v[2],$v[3],$v[4],$v[5],$v[6],$id]); $notice = $affected > 0 ? 'Voucher updated.' : 'Voucher unchanged or not found.'; }
    else { $affected = $database->execute('INSERT INTO vouchers (code, credits, points, points_type, catalog_item_id, amount, `limit`) VALUES (?, ?, ?, ?, ?, ?, ?)', $v); $id = (int) $database->insertId(); $notice = 'Voucher created.'; }
  }
  if ($affected > 0) {
    AdminAudit::log($database, (int) $user->id, 'voucher_'.$action, 'voucher', $id);
    $notice .= ' '.PhpretroPolarisCms::instance()->reloadCatalogNotice();
  }
  $action = 'list';
}
$voucher = ['id'=>0,'code'=>'','credits'=>0,'points'=>0,'points_type'=>0,'catalog_item_id'=>0,'amount'=>1,'redemption_limit'=>-1];
if ($action === 'edit') { $loaded = $database->fetchRow('SELECT id, code, credits, points, points_type, catalog_item_id, amount, `limit` AS redemption_limit FROM vouchers WHERE id=?', [(int) ($_GET['id'] ?? 0)]); if ($loaded !== false) { $voucher = $loaded; } }
ob_start();
if ($action === 'create' || $action === 'edit') { ?><form method="post"><input type="hidden" name="id" value="<?php echo (int) $voucher['id']; ?>"><?php foreach (['code'=>'Code','credits'=>'Credits','points'=>'Points','points_type'=>'Points type','catalog_item_id'=>'Catalog item ID','amount'=>'Amount','redemption_limit'=>'Redemption limit (-1 unlimited)'] as $field => $label) { ?><label><?php echo $label; ?></label><br><input name="<?php echo $field; ?>" <?php echo $field === 'code' ? 'maxlength="10"' : 'type="number"'; ?> value="<?php echo $e($voucher[$field]); ?>"><br><?php } ?><button>Save</button></form><?php
} else { $rows = $database->fetchAll('SELECT id, code, credits, points, points_type, catalog_item_id, amount, `limit` AS redemption_limit FROM vouchers ORDER BY id DESC'); ?><p><a href="<?php echo PATH; ?>/housekeeping/vouchers?do=create">New voucher</a></p><table><tr><th>Code</th><th>Credits</th><th>Points</th><th>Catalog item</th><th>Amount</th><th>Limit</th><th>Actions</th></tr><?php foreach ($rows as $row) { ?><tr><td><?php echo $e($row['code']); ?></td><td><?php echo (int) $row['credits']; ?></td><td><?php echo (int) $row['points']; ?></td><td><?php echo (int) $row['catalog_item_id']; ?></td><td><?php echo (int) $row['amount']; ?></td><td><?php echo (int) $row['redemption_limit']; ?></td><td><a href="<?php echo PATH; ?>/housekeeping/vouchers?do=edit&id=<?php echo (int) $row['id']; ?>">Edit</a><form style="display:inline" method="post" action="<?php echo PATH; ?>/housekeeping/vouchers?do=delete"><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>"><button>Delete</button></form></td></tr><?php } ?></table><?php
}
$content = ob_get_clean(); $page['name'] = 'Vouchers'; $page['category'] = 'tools'; require_once('./templates/housekeeping_header.php');
?><div class="page_title"><span class="page_name">Vouchers</span></div><div class="page_main"><div class="center"><?php if ($notice !== '') { ?><div class="clean-ok"><?php echo $e($notice); ?></div><?php } echo $content; ?></div></div><?php require_once('./templates/housekeeping_footer.php'); ?>
