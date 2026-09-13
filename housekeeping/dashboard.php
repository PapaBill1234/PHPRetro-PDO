<?php
$page['dir'] = '\\housekeeping'; $page['housekeeping'] = true; $page['rank'] = 5;
require_once('../includes/core.php'); require_once('./includes/hksession.php');
$database = new Database();
$statistics = ['users'=>(int) $database->fetchColumn('SELECT COUNT(*) FROM users'),'rooms'=>(int) $database->fetchColumn('SELECT COUNT(*) FROM rooms'),'bans'=>(int) $database->fetchColumn('SELECT COUNT(*) FROM bans'),'online'=>(int) $database->fetchColumn("SELECT COUNT(*) FROM users WHERE online = '1'")];
$page['name'] = 'Dashboard'; $page['category'] = 'dashboard'; require_once('./templates/housekeeping_header.php');
?>
<div class="page_title"><span class="page_name">Dashboard</span></div><div class="page_main"><div class="center"><h2>Hotel statistics</h2><ul><li>Users: <?php echo $statistics['users']; ?></li><li>Rooms: <?php echo $statistics['rooms']; ?></li><li>Active bans: <?php echo $statistics['bans']; ?></li><li>Online users: <?php echo $statistics['online']; ?></li></ul><p>Remote update checking is disabled: the previous plain-HTTP serialized response was unsafe.</p></div></div>
<?php require_once('./templates/housekeeping_footer.php'); ?>