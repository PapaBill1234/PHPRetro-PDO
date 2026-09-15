<?php
$page['dir'] = '\\housekeeping';
$page['housekeeping'] = true;
$page['rank'] = 5;
require_once __DIR__ . '/../includes/core.php';
require_once('./includes/hksession.php');
require_once('../includes/AdminAudit.php');
$database = new Database();
$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$notice = '';
$action = $_GET['do'] ?? 'list';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $id = (int) ($_POST['id'] ?? 0);
    $affected = 0;
    if ($action === 'delete') {
        $affected = $database->execute('DELETE FROM phpretro_campaigns WHERE id = ?', [$id]);
        $notice = $affected > 0 ? 'Campaign removed.' : 'Campaign not found.';
    } else {
        $visible = isset($_POST['visible']) && (string) $_POST['visible'] === '1' ? '1' : '0';
        $v = [trim((string) ($_POST['name'] ?? '')), trim((string) ($_POST['desc'] ?? '')), str_replace('%path%', PATH, trim((string) ($_POST['image'] ?? ''))), str_replace('%path%', PATH, trim((string) ($_POST['url'] ?? ''))), $visible, max(1, (int) ($_POST['sort_order'] ?? 0))];
        if ($v[0] === '' || $v[2] === '') {
            $notice = 'Name and image are required.';
        } elseif ($id > 0) {
            $affected = $database->execute('UPDATE phpretro_campaigns SET name = ?, `desc` = ?, image = ?, url = ?, visible = ?, sort_order = ? WHERE id = ?', [...$v, $id]);
            $notice = $affected > 0 ? 'Campaign updated.' : 'Campaign unchanged or not found.';
        } else {
            $affected = $database->execute('INSERT INTO phpretro_campaigns (name, `desc`, image, url, visible, sort_order) VALUES (?, ?, ?, ?, ?, ?)', $v);
            $id = (int) $database->insertId();
            $notice = 'Campaign created.';
        }
    }
    if ($affected > 0) {
        AdminAudit::log($database, (int) $user->id, 'campaign_'.$action, 'campaign', $id);
    }
    $action = 'list';
}
$item = ['id' => 0, 'name' => '', 'desc' => '', 'image' => '', 'url' => '', 'visible' => '1', 'sort_order' => 1];
if ($action === 'edit') {
    $loaded = $database->fetchRow('SELECT id, name, `desc`, image, url, visible, sort_order FROM phpretro_campaigns WHERE id = ?', [(int) ($_GET['id'] ?? 0)]);
    if ($loaded !== false) {
        $item = $loaded;
    }
}
ob_start();
if ($action === 'create' || $action === 'edit') {
    ?><form method="post"><?php echo Csrf::field(); ?><input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>"><label>Name</label><br><input name="name" value="<?php echo $e($item['name']); ?>"><br><label>Description</label><br><input name="desc" value="<?php echo $e($item['desc']); ?>"><br><label>Image URL</label><br><input name="image" value="<?php echo $e($item['image']); ?>"><br><label>Link URL</label><br><input name="url" value="<?php echo $e($item['url']); ?>"><br><label>Order</label><br><input type="number" name="sort_order" value="<?php echo (int) $item['sort_order']; ?>"><br><label><input type="checkbox" name="visible" value="1"<?php echo (string) $item['visible'] === '1' ? ' checked' : ''; ?>> Visible</label><br><button>Save</button></form><?php
} else {
    $rows = $database->fetchAll('SELECT id, name, visible, sort_order FROM phpretro_campaigns ORDER BY sort_order ASC, id ASC');
    ?><p><a href="<?php echo PATH; ?>/housekeeping/campaigns?do=create">New campaign</a></p><table><tr><th>Order</th><th>Name</th><th>Visible</th><th>Actions</th></tr><?php foreach ($rows as $row) { ?><tr><td><?php echo (int) $row['sort_order']; ?></td><td><?php echo $e($row['name']); ?></td><td><?php echo (string) $row['visible'] === '1' ? 'On' : 'Off'; ?></td><td><a href="<?php echo PATH; ?>/housekeeping/campaigns?do=edit&id=<?php echo (int) $row['id']; ?>">Edit</a><form style="display:inline" method="post" action="<?php echo PATH; ?>/housekeeping/campaigns?do=delete"><?php echo Csrf::field(); ?><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>"><button>Delete</button></form></td></tr><?php } ?></table><?php
}
$content = ob_get_clean();
$page['name'] = 'Campaigns';
$page['category'] = 'tools';
require_once('./templates/housekeeping_header.php');
?>
<div class="page_title"><span class="page_name">Campaigns</span></div><div class="page_main"><div class="center"><?php if ($notice !== '') { ?><div class="clean-ok"><?php echo $e($notice); ?></div><?php } ?><?php echo $content; ?></div></div><?php require_once('./templates/housekeeping_footer.php'); ?>
