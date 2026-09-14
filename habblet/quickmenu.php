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
$lang->addLocale('quickmenu');
$key = habbletText($_GET, 'key');
if ($key === 'friends_all') {
    $rows = $db->fetchAll('SELECT DISTINCT u.id, u.username, u.online, COALESCE(s.hide_online, 0) AS hide_online FROM messenger_friendships f JOIN users u ON u.id = f.user_two_id LEFT JOIN users_settings s ON s.user_id = u.id WHERE f.user_one_id = ? ORDER BY u.username, u.id', [(int) $user->id]);
    foreach (['online', 'offline'] as $status) {
        echo '<ul id="'.$status.'-friends">';
        foreach ($rows as $index => $row) {
            $online = $row['online'] !== '0' && (string) $row['hide_online'] !== '1';
            if ($online !== ($status === 'online')) { continue; }
            echo '<li class="'.($index % 2 ? 'odd' : 'even').'"><a href="'.PATH.'/home/'.rawurlencode($row['username']).'">'.$input->HoloText($row['username']).'</a></li>';
        }
        echo '</ul>';
    }
    if (!$rows) { echo '<ul id="quickmenu-friends"><li>'.$lang->loc['no.friends'].'</li></ul>'; }
} elseif ($key === 'groups') {
    // GuildRank: OWNER=0, ADMIN=1, MEMBER=2, REQUESTED=3, DELETED=4.
    $rows = $db->fetchAll('SELECT DISTINCT g.id, g.name, g.room_id, g.user_id, m.level_id, COALESCE(s.guild_id, 0) AS favorite_id FROM guilds_members m JOIN guilds g ON g.id = m.guild_id LEFT JOIN users_settings s ON s.user_id = m.user_id WHERE m.user_id = ? AND m.level_id IN (0, 1, 2) ORDER BY g.name, g.id', [(int) $user->id]);
    echo '<ul id="quickmenu-groups">';
    foreach ($rows as $row) {
        echo '<li>';
        if ((int) $row['room_id'] > 0) { echo '<a class="group-room" href="'.PATH.'/client?forwardId=2&amp;roomId='.(int) $row['room_id'].'"></a>'; }
        if ((int) $row['favorite_id'] === (int) $row['id']) { echo '<div class="favourite-group"></div>'; }
        if ((int) $row['user_id'] === (int) $user->id) { echo '<div class="owned-group"></div>'; }
        elseif ((int) $row['level_id'] === 1) { echo '<div class="admin-group"></div>'; }
        echo '<a href="'.PATH.'/groups/'.(int) $row['id'].'/id">'.$input->HoloText($row['name']).'</a></li>';
    }
    if (!$rows) { echo '<li>'.$lang->loc['no.groups'].'</li>'; }
    echo '</ul>';
} elseif ($key === 'rooms') {
    $rows = $db->fetchAll('SELECT id, name FROM rooms WHERE owner_id = ? ORDER BY name, id', [(int) $user->id]);
    echo '<ul id="quickmenu-rooms">';
    foreach ($rows as $row) {
        echo '<li><a id="room-navigation-link_'.(int) $row['id'].'" href="'.PATH.'/client?forwardId=2&amp;roomId='.(int) $row['id'].'">'.$input->HoloText($row['name']).'</a></li>';
    }
    if (!$rows) { echo '<li>'.$lang->loc['no.rooms'].'</li>'; }
    echo '</ul>';
} else { http_response_code(400); echo 'Unknown menu.'; }
