<?php
$page['dir'] = '\\housekeeping';
$page['housekeeping'] = true;
if(($_GET['do'] ?? '') == "savedetails" || ($_GET['do'] ?? '') == "savebadges"){
$page['rank'] = 7;
}else{
$page['rank'] = 6;
}
require_once('../includes/core.php');
require_once('./includes/hksession.php');
$database = new Database(); $e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); $notice = ''; $action = $_GET['do'] ?? 'list';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'savedetails') {
    $id = (int) ($_POST['id'] ?? 0); $before = $database->fetchRow('SELECT credits FROM users WHERE id = ?', [$id]);
    if ($before === false) { $notice = 'User not found.'; }
    else { $credits = (int) ($_POST['credits'] ?? $before['credits']); $database->execute('UPDATE users SET mail = ?, rank = ?, credits = ?, pixels = ?, points = ? WHERE id = ?', [trim((string) ($_POST['mail'] ?? '')), (int) ($_POST['rank'] ?? 1), $credits, (int) ($_POST['pixels'] ?? 0), (int) ($_POST['points'] ?? 0), $id]);
        if ($credits !== (int) $before['credits']) { $database->execute('INSERT INTO phpretro_transactions (user_id, type, amount, balance_after, description, reference_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)', [$id, 'admin_grant', $credits - (int) $before['credits'], $credits, 'Housekeeping credit adjustment', (string) $id, time()]); }
        $notice = 'User updated.';
    } $action = 'edit';
}
$user = null; if ($action === 'edit') { $user = $database->fetchRow('SELECT id, username, mail, rank, credits, pixels, points, online FROM users WHERE id = ?', [(int) ($_GET['id'] ?? $_POST['id'] ?? 0)]); }
$term = trim((string) ($_GET['q'] ?? '')); $users = $term === '' ? $database->fetchAll('SELECT id, username, mail, rank, credits, pixels, points, online FROM users ORDER BY id DESC LIMIT 100') : $database->fetchAll('SELECT id, username, mail, rank, credits, pixels, points, online FROM users WHERE username LIKE ? ORDER BY username LIMIT 100', ['%' . $term . '%']);
$page['name'] = 'Users'; $page['category'] = 'users'; require_once('./templates/housekeeping_header.php');
?>
<div class="page_title"><span class="page_name">Users</span></div><div class="page_main"><div class="center"><?php if($notice !== '') { ?><div class="clean-ok"><?php echo $e($notice); ?></div><?php } ?><?php if ($user !== false && $user !== null) { ?><form method="post" action="<?php echo PATH; ?>/housekeeping/users?do=savedetails"><input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>"><p><?php echo $e($user['username']); ?></p><label>Email</label><br><input name="mail" value="<?php echo $e($user['mail']); ?>"><br><label>Rank</label><br><input name="rank" type="number" value="<?php echo (int) $user['rank']; ?>"><br><label>Credits</label><br><input name="credits" type="number" value="<?php echo (int) $user['credits']; ?>"><br><label>Pixels</label><br><input name="pixels" type="number" value="<?php echo (int) $user['pixels']; ?>"><br><label>Points</label><br><input name="points" type="number" value="<?php echo (int) $user['points']; ?>"><br><button>Save</button></form><?php } else { ?><form method="get"><input type="text" name="q" value="<?php echo $e($term); ?>"><button>Search</button></form><table><tr><th>ID</th><th>User</th><th>Email</th><th>Rank</th><th>Credits</th><th>Pixels</th><th>Points</th><th>Online</th></tr><?php foreach($users as $row){?><tr><td><?php echo(int)$row['id'];?></td><td><a href="<?php echo PATH;?>/housekeeping/users?do=edit&id=<?php echo(int)$row['id'];?>"><?php echo $e($row['username']);?></a></td><td><?php echo $e($row['mail']);?></td><td><?php echo(int)$row['rank'];?></td><td><?php echo(int)$row['credits'];?></td><td><?php echo(int)$row['pixels'];?></td><td><?php echo(int)$row['points'];?></td><td><?php echo $e($row['online']);?></td></tr><?php }?></table><?php } ?></div></div><?php require_once('./templates/housekeeping_footer.php'); ?>