<?php
$page['dir'] = '\\housekeeping';
$page['housekeeping'] = true;
$page['rank'] = 5;
require_once('../includes/core.php');
require_once('./includes/hksession.php');
$database = new Database();
$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$roomId = (int) ($_GET['roomid'] ?? 0);
$searchResults = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    Csrf::requireValid();
    $query = trim((string) ($_POST['query'] ?? ''));
    if ($query !== '') {
        $like = '%'.$query.'%';
        $searchResults = $database->fetchAll('SELECT id, name FROM rooms WHERE name LIKE ? OR description LIKE ? ORDER BY id DESC LIMIT 50', [$like, $like]);
    }
}
if ($roomId > 0) {
    $rows = $database->fetchAll('SELECT u.username, c.room_id, c.timestamp, c.message FROM chatlogs_room c LEFT JOIN users u ON u.id = c.user_from_id WHERE c.room_id = ? ORDER BY c.timestamp DESC LIMIT 1000', [$roomId]);
} else {
    $rows = $database->fetchAll('SELECT u.username, c.room_id, c.timestamp, c.message FROM chatlogs_room c LEFT JOIN users u ON u.id = c.user_from_id ORDER BY c.timestamp DESC LIMIT 1000');
}
$page['name'] = 'Logs';
$page['category'] = 'dashboard';
$page['scrollbar'] = true;
require_once('./templates/housekeeping_header.php');
?>
<div class="page_title"><span class="page_name">Logs</span></div>
<div class="page_main"><table border="0" cellpadding="0" cellspacing="0" height="100%"><tbody><tr height="100%"><td class="page_main_left">
<form method="post"><?php echo Csrf::field(); ?>
<div class="text"><p>Search rooms, then open chat logs for that room. Source is PolarIS <code>chatlogs_room</code>.</p>
<?php if ($searchResults) { ?><table><?php foreach ($searchResults as $row) { ?><tr><td><a href="<?php echo PATH; ?>/housekeeping/logs?roomid=<?php echo (int) $row['id']; ?>"><?php echo $e($row['name']); ?></a></td><td><?php echo (int) $row['id']; ?></td></tr><?php } ?></table><?php } ?>
<input type="text" name="query"><button type="submit" name="search" value="1">Search</button></div></form>
</td><td class="page_main_right"><div class="center">
<table><tr><th>User</th><th>Message</th><th>Room</th><th>Time</th></tr>
<?php foreach ($rows as $row) { ?><tr><td><?php echo $e($row['username'] ?? ''); ?></td><td><?php echo $e($row['message']); ?></td><td><?php echo (int) $row['room_id']; ?></td><td><?php echo $e(date('Y-m-d H:i:s', (int) $row['timestamp'])); ?></td></tr><?php } ?>
</table>
</div></td></tr></tbody></table></div>
<?php require_once('./templates/housekeeping_footer.php'); ?>
