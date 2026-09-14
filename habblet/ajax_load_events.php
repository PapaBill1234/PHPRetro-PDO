<?php
/*================================================================+\
|| # PHPRetro - An extendable virtual hotel site and management
|+==================================================================
|| # Copyright (C) 2009 Yifan Lu. All rights reserved.
|| # http://www.yifanlu.com
|| # Parts Copyright (C) 2009 Meth0d. All rights reserved.
|| # http://www.meth0d.org
|| # All images, scripts, and layouts
|| # Copyright (C) 2009 Sulake Ltd. All rights reserved.
|+==================================================================
|| # PHPRetro is provided "as is" and comes without
|| # warrenty of any kind. PHPRetro is free software!
|| # License: GNU Public License 3.0
|| # http://opensource.org/licenses/gpl-license.php
\+================================================================*/

require_once(__DIR__.'/../includes/habblet.php');
habbletRequireUser();
$lang->addLocale('events.loadevents');
$category = habbletInt($_POST, 'eventTypeId', 1);
if ($category < 1 || $category > 11) { $category = 1; }
$rows = $db->fetchAll('SELECT p.room_id, p.title, p.description, p.start_timestamp, r.owner_name, r.users, r.users_max FROM room_promotions p JOIN rooms r ON r.id = p.room_id WHERE p.category = ? AND p.start_timestamp <= ? AND p.end_timestamp > ? ORDER BY p.start_timestamp DESC, p.room_id', [$category, time(), time()]);
?>
<ul class="habblet-list">
<?php foreach ($rows as $index => $row) {
    $ratio = (int) $row['users'] / max(1, (int) $row['users_max']);
    $fill = $ratio >= .99 ? 5 : ($ratio > .65 ? 4 : ($ratio > .32 ? 3 : ($ratio > 0 ? 2 : 1)));
?>
<li class="<?php echo $index % 2 ? 'odd' : 'even'; ?> room-occupancy-<?php echo $fill; ?>" roomid="<?php echo (int) $row['room_id']; ?>">
<div><span class="event-name"><a href="<?php echo PATH.'/client?forwardId=2&amp;roomId='.(int) $row['room_id']; ?>"><?php echo $input->HoloText($row['title']); ?></a></span>
<span class="event-owner"> by <a href="<?php echo PATH.'/home/'.rawurlencode($row['owner_name']); ?>"><?php echo $input->HoloText($row['owner_name']); ?></a></span>
<p><?php echo $input->HoloText($row['description']); ?> (<span class="event-date"><?php echo date('M j, Y H:i', (int) $row['start_timestamp']); ?></span>)</p></div></li>
<?php } ?></ul>
