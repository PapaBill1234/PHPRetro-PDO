<?php
$page['dir'] = '\\housekeeping';
$page['housekeeping'] = true;
$page['rank'] = 5;
require_once __DIR__ . '/../includes/core.php');
require_once('./includes/hksession.php');
require_once('../includes/AdminAudit.php');
$database = new Database();
$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$notice = '';
$action = $_GET['do'] ?? 'list';
$types = ['1' => 'Sticker', '4' => 'Background'];
$placements = ['-1' => 'Groups only', '0' => 'Anywhere', '1' => 'Homes only'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $id = (int) ($_POST['id'] ?? 0);
    $affected = 0;
    if ($action === 'delete') {
        $affected = $database->execute('DELETE FROM phpretro_homes_catalogue WHERE id = ?', [$id]);
        $notice = $affected > 0 ? 'Item removed.' : 'Item not found.';
    } else {
        $type = (string) ($_POST['type'] ?? '1');
        if (!isset($types[$type])) {
            $type = '1';
        }
        $where = (string) ($_POST['where'] ?? '0');
        if (!isset($placements[$where])) {
            $where = '0';
        }
        $price = (int) ($_POST['price'] ?? 0);
        $amount = (int) ($_POST['amount'] ?? 0);
        $minrank = (int) ($_POST['minrank'] ?? 1);
        $category = trim((string) ($_POST['category'] ?? ''));
        $v = [trim((string) ($_POST['name'] ?? '')), trim((string) ($_POST['desc'] ?? '')), $type, trim((string) ($_POST['data'] ?? '')), $price, $amount, $category, $minrank, $where];
        if ($v[3] === '' || $price < 0 || $amount < 1 || $minrank < 1) {
            $notice = 'Data, a numeric price, amount, and min rank are required.';
        } elseif ($id > 0) {
            $affected = $database->execute('UPDATE phpretro_homes_catalogue SET name = ?, `desc` = ?, `type` = ?, data = ?, price = ?, amount = ?, category = ?, minrank = ?, `where` = ? WHERE id = ?', [...$v, $id]);
            $notice = $affected > 0 ? 'Item updated.' : 'Item unchanged or not found.';
        } else {
            $affected = $database->execute('INSERT INTO phpretro_homes_catalogue (name, `desc`, `type`, data, price, amount, category, minrank, `where`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', $v);
            $id = (int) $database->insertId();
            $notice = 'Item created.';
        }
    }
    if ($affected > 0) {
        AdminAudit::log($database, (int) $user->id, 'catalogue_'.$action, 'homes_catalogue', $id);
    }
    $action = 'list';
}
$item = ['id' => 0, 'name' => '', 'desc' => '', 'type' => '1', 'data' => '', 'price' => 2, 'amount' => 1, 'category' => '', 'minrank' => 1, 'where' => '0'];
if ($action === 'edit') {
    $loaded = $database->fetchRow('SELECT id, name, `desc`, `type`, data, price, amount, category, minrank, `where` FROM phpretro_homes_catalogue WHERE id = ?', [(int) ($_GET['id'] ?? 0)]);
    if ($loaded !== false) {
        $item = $loaded;
    }
}
ob_start();
if ($action === 'create' || $action === 'edit') {
    ?><form method="post"><?php echo Csrf::field(); ?><input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>"><label>Name</label><br><input name="name" value="<?php echo $e($item['name']); ?>"><br><label>Description</label><br><input name="desc" value="<?php echo $e($item['desc']); ?>"><br><label>Type</label><br><select name="type"><?php foreach ($types as $value => $label) { ?><option value="<?php echo $e($value); ?>"<?php echo (string) $item['type'] === (string) $value ? ' selected' : ''; ?>><?php echo $e($label); ?></option><?php } ?></select><br><label>Data</label><br><input name="data" value="<?php echo $e($item['data']); ?>"><br><label>Price</label><br><input type="number" name="price" value="<?php echo (int) $item['price']; ?>"><br><label>Amount</label><br><input type="number" name="amount" value="<?php echo (int) $item['amount']; ?>"><br><label>Min rank</label><br><input type="number" name="minrank" value="<?php echo (int) $item['minrank']; ?>"><br><label>Where</label><br><select name="where"><?php foreach ($placements as $value => $label) { ?><option value="<?php echo $e($value); ?>"<?php echo (string) $item['where'] === (string) $value ? ' selected' : ''; ?>><?php echo $e($label); ?></option><?php } ?></select><br><label>Category</label><br><input name="category" value="<?php echo $e($item['category']); ?>"><br><button>Save</button></form><?php
} else {
    $rows = $database->fetchAll('SELECT id, name, `type`, data, category FROM phpretro_homes_catalogue ORDER BY `type`, category, name, id');
    ?><p><a href="<?php echo PATH; ?>/housekeeping/catalogue?do=create">New item</a></p><table><tr><th>Type</th><th>Name</th><th>Data</th><th>Category</th><th>Actions</th></tr><?php foreach ($rows as $row) { ?><tr><td><?php echo $e($types[(string) $row['type']] ?? $row['type']); ?></td><td><?php echo $e($row['name']); ?></td><td><?php echo $e($row['data']); ?></td><td><?php echo $e($row['category']); ?></td><td><a href="<?php echo PATH; ?>/housekeeping/catalogue?do=edit&id=<?php echo (int) $row['id']; ?>">Edit</a><form style="display:inline" method="post" action="<?php echo PATH; ?>/housekeeping/catalogue?do=delete"><?php echo Csrf::field(); ?><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>"><button>Delete</button></form></td></tr><?php } ?></table><?php
}
$content = ob_get_clean();
$page['name'] = 'Catalogue';
$page['category'] = 'tools';
require_once('./templates/housekeeping_header.php');
?>
<div class="page_title"><span class="page_name">Catalogue</span></div><div class="page_main"><div class="center"><?php if ($notice !== '') { ?><div class="clean-ok"><?php echo $e($notice); ?></div><?php } ?><?php echo $content; ?></div></div><?php require_once('./templates/housekeeping_footer.php'); ?>
