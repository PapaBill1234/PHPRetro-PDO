<?php
$widgetId = (int) $widget['id'];
$key = $widget['widget_key'];
$guild = $homes->guild((int) $widget['guild_id']);
$edit = !empty($page['edit']);
$privacy = ($widget['privacy'] ?? 'public') === 'private' ? 'private' : 'public';
$class = match ($key) {
    'guestbookwidget' => 'GuestbookWidget',
    'memberwidget' => 'MemberWidget',
    default => 'GroupInfoWidget',
};
$lang->addLocale(match ($key) {
    'guestbookwidget' => 'homes.widget.guestbook',
    'memberwidget' => 'groups.widget.members',
    default => 'groups.widget.info',
});
?>
<div class="movable widget <?php echo $class; ?>" id="widget-<?php echo $widgetId; ?>" style="<?php echo $homes->widgetStyle($widget); ?>">
<div class="w_skin_defaultskin">
<div class="widget-corner" id="widget-<?php echo $widgetId; ?>-handle">
<div class="widget-headline"><h3>
<?php if ($edit) { ?>
<img src="<?php echo PATH; ?>/web-gallery/images/myhabbo/icon_edit.gif" width="19" height="18" class="edit-button" id="widget-<?php echo $widgetId; ?>-edit" />
<?php } ?>
<span class="header-left">&nbsp;</span><span class="header-middle"><?php
echo match ($key) {
    'guestbookwidget' => $lang->loc['my.guestbook'] ?? 'Guestbook',
    'memberwidget' => $lang->loc['members.of.group'] ?? 'Members of this group',
    default => $lang->loc['group.info'] ?? 'Group Info',
};
?></span><span class="header-right">&nbsp;</span></h3>
</div>
</div>
<div class="widget-body"><div class="widget-content">
<?php if ($key === 'groupinfowidget') { ?>
<?php if ($guild['badge'] !== '') { ?><div class="group-info-icon"><img src="<?php echo $input->HoloText($guild['badge']); ?>" alt="" /></div><?php } ?>
<h4><?php echo $input->HoloText($guild['name']); ?></h4>
<p><?php echo $lang->loc['created.on'] ?? 'Created on'; ?>: <b><?php echo $guild['date_created'] ? date('d-m-Y', (int) $guild['date_created']) : ''; ?></b></p>
<p><b><?php echo $homes->guildMemberCount((int) $guild['id']); ?></b> <?php echo $lang->loc['users.in.group'] ?? 'users in group'; ?></p>
<?php if ((int) $guild['room_id'] > 0) { ?>
<p><a href="<?php echo PATH; ?>/client?forwardId=2&roomId=<?php echo (int) $guild['room_id']; ?>" onclick="HabboClient.roomForward(this, '<?php echo (int) $guild['room_id']; ?>', 'private'); return false;" target="client" class="group-info-room">Room</a></p>
<?php } ?>
<div class="group-info-description"><?php echo nl2br($input->HoloText($guild['description'])); ?></div>
<p><?php echo $lang->loc['no.tags'] ?? 'No tags.'; ?></p>
<?php } elseif ($key === 'guestbookwidget') {
    $entries = $homes->guestbookEntriesForWidget($widget);
    foreach ($entries as &$entry) {
        $entry['profile_user_id'] = 0;
        $entry['can_delete'] = (int) $user->id === (int) $entry['author_user_id'] || $homes->canEditGroup((int) $guild['id']);
    }
    unset($entry);
?>
<div id="guestbook-type" class="<?php echo $privacy; ?>">
<div id="guestbook-wrapper" class="gb-<?php echo $privacy === 'private' ? 'private' : 'public'; ?>">
<ul class="guestbook-entries" id="guestbook-entry-container">
<?php if ($entries === []) { ?><div id="guestbook-empty-notes"><?php echo $lang->loc['guestbook.no.entries'] ?? 'No entries.'; ?></div><?php } ?>
<?php foreach ($entries as $entry) { $page['bypass'] = true; require __DIR__.'/home-guestbook-entry.php'; } ?>
</ul>
</div>
</div>
<?php if (!$edit && (int) $user->id > 0 && ($privacy !== 'private' || $homes->groupMember((int) $guild['id'], (int) $user->id))) { ?>
<div class="guestbook-toolbar clearfix">
<a href="#" class="new-button envelope-icon" id="guestbook-open-dialog"><b><span></span><?php echo $lang->loc['new.message'] ?? 'New message'; ?></b><i></i></a>
</div>
<?php } ?>
<?php } elseif ($key === 'memberwidget') {
    $members = $homes->guildMembers((int) $guild['id']);
    if ($members === []) { echo $lang->loc['no.members'] ?? 'This Group has no members.'; }
    else {
        echo '<ul class="member-list">';
        foreach ($members as $member) {
            echo '<li><a href="'.PATH.'/home/'.rawurlencode($member['username']).'">'.$input->HoloText($member['username']).'</a></li>';
        }
        echo '</ul>';
    }
} ?>
<div class="clear"></div>
</div></div>
</div>
</div>
