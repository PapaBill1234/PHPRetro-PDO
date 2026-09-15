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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $id = (int) ($_POST['id'] ?? 0);
    $affected = 0;
    if ($action === 'delete') {
        $affected = $database->execute('DELETE FROM phpretro_banners WHERE id = ?', [$id]);
        $notice = $affected > 0 ? 'Banner removed.' : 'Banner not found.';
    } else {
        $html = (string) ($_POST['html'] ?? '');
        $advanced = $html !== '' ? '1' : '0';
        $status = isset($_POST['status']) && (string) $_POST['status'] === '1' ? '1' : '0';
        $v = [trim((string) ($_POST['text'] ?? '')), str_replace('%path%', PATH, trim((string) ($_POST['banner'] ?? ''))), str_replace('%path%', PATH, trim((string) ($_POST['url'] ?? ''))), $status, $advanced, $html, max(1, (int) ($_POST['sort_order'] ?? 0))];
        if ($v[0] === '' && $v[1] === '' && $html === '') {
            $notice = 'Text, image, or HTML is required.';
        } elseif ($id > 0) {
            $affected = $database->execute('UPDATE phpretro_banners SET text = ?, banner = ?, url = ?, status = ?, advanced = ?, html = ?, sort_order = ? WHERE id = ?', [...$v, $id]);
            $notice = $affected > 0 ? 'Banner updated.' : 'Banner unchanged or not found.';
        } else {
            $affected = $database->execute('INSERT INTO phpretro_banners (text, banner, url, status, advanced, html, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)', $v);
            $id = (int) $database->insertId();
            $notice = 'Banner created.';
        }
    }
    if ($affected > 0) {
        AdminAudit::log($database, (int) $user->id, 'banner_'.$action, 'banner', $id);
    }
    $action = 'list';
}
$item = ['id' => 0, 'text' => '', 'banner' => '', 'url' => '', 'status' => '1', 'advanced' => '0', 'html' => '', 'sort_order' => 1];
if ($action === 'edit') {
    $loaded = $database->fetchRow('SELECT id, text, banner, url, status, advanced, html, sort_order FROM phpretro_banners WHERE id = ?', [(int) ($_GET['id'] ?? 0)]);
    if ($loaded !== false) {
        $item = $loaded;
    }
}
ob_start();
if ($action === 'create' || $action === 'edit') {
    ?><form method="post"><?php echo Csrf::field(); ?><input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>"><label>Text</label><br><input name="text" value="<?php echo $e($item['text']); ?>"><br><label>Image URL</label><br><input name="banner" value="<?php echo $e($item['banner']); ?>"><br><label>Link URL</label><br><input name="url" value="<?php echo $e($item['url']); ?>"><br><label>HTML (advanced)</label><br><textarea name="html"><?php echo $e($item['html']); ?></textarea><br><label>Order</label><br><input type="number" name="sort_order" value="<?php echo (int) $item['sort_order']; ?>"><br><label><input type="checkbox" name="status" value="1"<?php echo (string) $item['status'] === '1' ? ' checked' : ''; ?>> Visible</label><br><button>Save</button></form><?php
} else {
    $rows = $database->fetchAll('SELECT id, text, banner, status, advanced, sort_order FROM phpretro_banners ORDER BY sort_order ASC, id ASC');
    ?><p><a href="<?php echo PATH; ?>/housekeeping/banners?do=create">New banner</a></p><table><tr><th>Order</th><th>Data</th><th>Visible</th><th>Actions</th></tr><?php foreach ($rows as $row) { ?><tr><td><?php echo (int) $row['sort_order']; ?></td><td><?php echo (string) $row['advanced'] === '1' ? 'HTML' : $e($row['banner'] !== '' ? $row['banner'] : $row['text']); ?></td><td><?php echo (string) $row['status'] === '1' ? 'On' : 'Off'; ?></td><td><a href="<?php echo PATH; ?>/housekeeping/banners?do=edit&id=<?php echo (int) $row['id']; ?>">Edit</a><form style="display:inline" method="post" action="<?php echo PATH; ?>/housekeeping/banners?do=delete"><?php echo Csrf::field(); ?><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>"><button>Delete</button></form></td></tr><?php } ?></table><?php
}
$content = ob_get_clean();
$page['name'] = 'Banners';
$page['category'] = 'tools';
require_once('./templates/housekeeping_header.php');
?>
<div class="page_title"><span class="page_name">Banners</span></div><div class="page_main"><div class="center"><?php if ($notice !== '') { ?><div class="clean-ok"><?php echo $e($notice); ?></div><?php } ?><?php echo $content; ?></div></div><?php require_once('./templates/housekeeping_footer.php'); ?>
