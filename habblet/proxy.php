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
$hid = habbletText($_GET, 'hid');
if ($hid === 'h120' || $hid === 'h21') {
    if ($hid === 'h120') {
        $rows = $db->fetchAll('SELECT r.id, r.name, r.description, r.owner_name, r.users, r.users_max, COUNT(v.user_id) AS votes FROM rooms r LEFT JOIN room_votes v ON v.room_id = r.id WHERE r.is_public = ? AND r.state <> ? GROUP BY r.id, r.name, r.description, r.owner_name, r.users, r.users_max ORDER BY votes DESC, r.id LIMIT 20', ['0', 'invisible']);
    } else {
        $rows = $db->fetchAll('SELECT id, name, description, owner_name, users, users_max FROM rooms WHERE is_staff_picked = ? AND state <> ? ORDER BY id LIMIT 20', ['1', 'invisible']);
    }
    echo '<div id="'.($hid === 'h120' ? 'rooms-habblet-list-container-h120' : 'staffpicks-rooms-habblet-list-container').'" class="habblet-list-container"><ul class="habblet-list">';
    foreach ($rows as $row) {
        $ratio = (int) $row['users'] / max(1, (int) $row['users_max']);
        $fill = $ratio >= .99 ? 5 : ($ratio > .65 ? 4 : ($ratio > .32 ? 3 : ($ratio > 0 ? 2 : 1)));
        echo '<li class="room-occupancy-'.$fill.'" roomid="'.(int) $row['id'].'"><a class="room-name" href="'.PATH.'/client?forwardId=2&amp;roomId='.(int) $row['id'].'">'.$input->HoloText($row['name']).'</a><p>'.$input->HoloText($row['description']).'</p><span class="room-owner">'.$input->HoloText($row['owner_name']).'</span></li>';
    }
    echo '</ul></div>';
} elseif ($hid === 'h122' || $hid === 'groups') {
    $rows = $db->fetchAll('SELECT g.id, g.name, COUNT(m.id) AS members FROM guilds g JOIN guilds_members m ON m.guild_id = g.id AND m.level_id IN (0, 1, 2) GROUP BY g.id, g.name ORDER BY members DESC, g.id LIMIT 50');
    echo '<div id="'.($hid === 'groups' ? 'groups-habblet-list-container' : 'hotgroups-habblet-list-container').'" class="habblet-list-container groups-list"><ul class="habblet-list">';
    foreach ($rows as $row) {
        echo '<li><a class="item" href="'.PATH.'/groups/'.(int) $row['id'].'/id">'.$input->HoloText($row['name']).'</a></li>';
    }
    echo '</ul></div>';
    // Legacy badge image generation and group purchase are separate batch-2 work.
} elseif ($hid === 'h24') {
    $counts = [];
    foreach ($db->fetchAll('SELECT s.tags FROM users_settings s JOIN users u ON u.id = s.user_id') as $row) {
        foreach (array_unique(array_filter(explode(';', $row['tags']))) as $tag) {
            $tag = strtolower($tag);
            $counts[$tag] = ($counts[$tag] ?? 0) + 1;
        }
    }
    arsort($counts);
    echo '<div class="habblet box-content"><ul class="tag-list">';
    foreach (array_slice($counts, 0, 20, true) as $tag => $count) {
        echo '<li><a class="tag" href="'.PATH.'/tag/'.rawurlencode($tag).'">'.$input->HoloText($tag).'</a> ('.$count.')</li>';
    }
    echo '</ul><p>User tags only; group tags are unavailable.</p></div>';
} else { http_response_code(400); echo 'Unknown widget.'; }
