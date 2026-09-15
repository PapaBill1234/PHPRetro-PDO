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
$searchResults = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    Csrf::requireValid();
    $query = trim((string) ($_POST['query'] ?? ''));
    if ($query !== '') {
        $like = '%'.$query.'%';
        $searchResults = $database->fetchAll('SELECT id, name FROM rooms WHERE name LIKE ? OR description LIKE ? ORDER BY id DESC LIMIT 50', [$like, $like]);
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['search'])) {
    Csrf::requireValid();
    $id = (int) ($_POST['id'] ?? 0);
    $affected = 0;
    if ($action === 'delete') {
        $affected = $database->execute('DELETE FROM phpretro_recommended WHERE id = ?', [$id]);
        $notice = $affected > 0 ? 'Recommended entry removed.' : 'Recommended entry not found.';
    } else {
        $type = (string) ($_POST['type'] ?? 'group');
        if (!in_array($type, ['group', 'room'], true)) {
            $type = 'group';
        }
        $sponsored = isset($_POST['sponsered']) && (string) $_POST['sponsered'] === '1' ? '1' : '0';
        $recId = (int) ($_POST['rec_id'] ?? 0);
        if ($type === 'group' && $recId < 1) {
            $alias = trim((string) ($_POST['rec_id'] ?? ''));
            if ($alias !== '') {
                $guild = $database->fetchRow('SELECT guild_id FROM phpretro_group_url_aliases WHERE alias = ? LIMIT 1', [$alias]);
                if ($guild === false) {
                    $guild = $database->fetchRow('SELECT id AS guild_id FROM guilds WHERE name = ? LIMIT 1', [$alias]);
                }
                $recId = $guild !== false ? (int) $guild['guild_id'] : 0;
            }
        }
        if ($recId < 1) {
            $notice = 'A valid room or group id is required.';
        } elseif ($type === 'room' && $sponsored !== '0') {
            $notice = 'Rooms can only be staff picks.';
        } elseif ($id > 0) {
            $affected = $database->execute('UPDATE phpretro_recommended SET rec_id = ?, type = ?, sponsered = ? WHERE id = ?', [$recId, $type, $sponsored, $id]);
            $notice = $affected > 0 ? 'Recommended entry updated.' : 'Recommended entry unchanged or not found.';
        } else {
            $affected = $database->execute('INSERT INTO phpretro_recommended (rec_id, type, sponsered) VALUES (?, ?, ?)', [$recId, $type, $sponsored]);
            $id = (int) $database->insertId();
            $notice = 'Recommended entry created.';
        }
    }
    if ($affected > 0) {
        AdminAudit::log($database, (int) $user->id, 'recommended_'.$action, 'recommended', $id);
    }
    $action = 'list';
}
$item = ['id' => 0, 'rec_id' => (int) ($_GET['recid'] ?? 0), 'type' => !empty($_GET['recid']) ? 'room' : 'group', 'sponsered' => '0'];
if ($action === 'edit') {
    $loaded = $database->fetchRow('SELECT id, rec_id, type, sponsered FROM phpretro_recommended WHERE id = ?', [(int) ($_GET['id'] ?? 0)]);
    if ($loaded !== false) {
        $item = $loaded;
    }
}
ob_start();
if ($action === 'create' || $action === 'edit') {
    ?><form method="post"><?php echo Csrf::field(); ?><input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>"><label>Room / group id</label><br><input name="rec_id" value="<?php echo $e($item['rec_id']); ?>"><br><label>Type</label><br><select name="type"><option value="group"<?php echo $item['type'] === 'group' ? ' selected' : ''; ?>>Group</option><option value="room"<?php echo $item['type'] === 'room' ? ' selected' : ''; ?>>Room</option></select><br><label>Location</label><br><select name="sponsered"><option value="0"<?php echo (string) $item['sponsered'] === '0' ? ' selected' : ''; ?>>Staff picks</option><option value="1"<?php echo (string) $item['sponsered'] === '1' ? ' selected' : ''; ?>>Recommended</option></select><br><button>Save</button></form><?php
} else {
    $rows = $database->fetchAll('SELECT id, rec_id, type, sponsered FROM phpretro_recommended ORDER BY sponsered ASC, id ASC');
    ?><p><a href="<?php echo PATH; ?>/housekeeping/recommended?do=create">New recommended</a></p><table><tr><th>ID</th><th>Type</th><th>Sponsored</th><th>Actions</th></tr><?php foreach ($rows as $row) { ?><tr><td><?php echo $e($row['rec_id']); ?></td><td><?php echo $e($row['type']); ?></td><td><?php echo $e($row['sponsered']); ?></td><td><a href="<?php echo PATH; ?>/housekeeping/recommended?do=edit&id=<?php echo (int) $row['id']; ?>">Edit</a><form style="display:inline" method="post" action="<?php echo PATH; ?>/housekeeping/recommended?do=delete"><?php echo Csrf::field(); ?><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>"><button>Delete</button></form></td></tr><?php } ?></table><?php
}
$content = ob_get_clean();
$page['name'] = 'Recommended';
$page['category'] = 'tools';
$page['scrollbar'] = true;
require_once('./templates/housekeeping_header.php');
?>
<div class="page_title"><span class="page_name">Recommended</span></div>
<div class="page_main"><table border="0" cellpadding="0" cellspacing="0" height="100%"><tbody><tr height="100%"><td class="page_main_left">
<form method="post"><?php echo Csrf::field(); ?><div class="text"><p>Search rooms by name or description.</p>
<?php if ($searchResults) { ?><table><?php foreach ($searchResults as $row) { ?><tr><td><a href="<?php echo PATH; ?>/housekeeping/recommended?do=create&recid=<?php echo (int) $row['id']; ?>"><?php echo $e($row['name']); ?></a></td><td><?php echo (int) $row['id']; ?></td></tr><?php } ?></table><?php } ?>
<input type="text" name="query"><button type="submit" name="search" value="1">Search</button></div></form>
</td><td class="page_main_right"><div class="center"><?php if ($notice !== '') { ?><div class="clean-ok"><?php echo $e($notice); ?></div><?php } ?><?php echo $content; ?></div></td></tr></tbody></table></div>
<?php require_once('./templates/housekeeping_footer.php'); ?>
