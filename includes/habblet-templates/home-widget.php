<?php
$widgetId = (int) $widget['id'];
$key = $widget['widget_key'];
$owner = $homes->profile((int) $widget['user_id']);
$edit = !empty($page['edit']);
$online = $owner['hide_online'] === '1' ? false : $owner['online'] === '1';
$class = match ($key) {
    'guestbookwidget' => 'GuestbookWidget',
    'highscoreswidget' => 'HighScoresWidget',
    'badgeswidget' => 'BadgesWidget',
    'friendswidget' => 'FriendsWidget',
    'groupswidget' => 'GroupsWidget',
    'roomswidget' => 'RoomsWidget',
    default => 'ProfileWidget',
};
$lang->addLocale(match ($key) {
    'guestbookwidget' => 'homes.widget.guestbook',
    'highscoreswidget' => 'homes.widget.highscore',
    'badgeswidget' => 'homes.widget.badges',
    'friendswidget' => 'homes.widget.friends',
    'groupswidget' => 'homes.widget.groups',
    'roomswidget' => 'homes.widget.rooms',
    default => 'homes.widget.profile',
});
?>
<div class="movable widget <?php echo $class; ?>" id="widget-<?php echo $widgetId; ?>">
<div class="w_skin_defaultskin">
<div class="widget-corner" id="widget-<?php echo $widgetId; ?>-handle">
<div class="widget-headline"><h3>
<?php if ($edit) { ?>
<img src="<?php echo PATH; ?>/web-gallery/images/myhabbo/icon_edit.gif" width="19" height="18" class="edit-button" id="widget-<?php echo $widgetId; ?>-edit" />
<?php } ?>
<span class="header-left">&nbsp;</span><span class="header-middle"><?php
echo match ($key) {
    'guestbookwidget' => $lang->loc['my.guestbook'] ?? 'My Guestbook',
    'highscoreswidget' => $lang->loc['high.scores'] ?? 'High Scores',
    'badgeswidget' => $lang->loc['badges.achievements'] ?? 'Badges',
    'friendswidget' => ($lang->loc['my.friends'] ?? 'My Friends').' ('.$homes->friendCount((int) $owner['id']).')',
    'groupswidget' => $lang->loc['my.groups'] ?? 'My Groups',
    'roomswidget' => $lang->loc['my.rooms'] ?? 'My Rooms',
    default => $lang->loc['my.profile'] ?? 'My Profile',
};
?></span><span class="header-right">&nbsp;</span></h3>
</div>
</div>
<div class="widget-body"><div class="widget-content">
<?php if ($key === 'profilewidget') { ?>
<div class="profile-info">
<div class="name" style="float: left"><span class="name-text"><?php echo $input->HoloText($owner['username']); ?></span></div>
<br class="clear" />
<img alt="<?php echo $online ? 'online' : 'offline'; ?>" src="<?php echo PATH; ?>/web-gallery/images/myhabbo/profile/habbo_<?php echo $online ? 'online_anim' : 'offline'; ?>.gif" />
<div class="birthday text"><?php echo $lang->loc['habbo.created.on'] ?? 'Created on'; ?>:</div>
<div class="birthday date"><?php echo $owner['account_created'] ? date('d-m-Y', (int) $owner['account_created']) : ''; ?></div>
<div class="profile-figure"><img alt="<?php echo $input->HoloText($owner['username']); ?>" src="<?php echo $user->avatarURL($owner['look'], 'b,4,4,,1,0'); ?>" /></div>
<?php if ($owner['motto'] !== '') { ?><div class="profile-motto"><?php echo $input->HoloText($owner['motto']); ?></div><?php } ?>
<div id="profile-tags-container">
<?php
$tags = array_values(array_filter(explode(';', (string) $owner['tags'])));
if ($tags === []) { echo $lang->loc['no.tags'] ?? 'No tags.'; }
foreach ($tags as $tag) {
    echo '<span class="tag-search-rowholder"><a href="'.PATH.'/tag/'.rawurlencode($tag).'" class="tag">'.$input->HoloText($tag).'</a></span>';
}
?>
</div>
</div>
<?php } elseif ($key === 'guestbookwidget') {
    $entries = $homes->guestbookEntries((int) $owner['id']);
    $lang->addLocale('homes.widget.guestbook');
?>
<div id="guestbook-wrapper" class="gb-public">
<ul class="guestbook-entries" id="guestbook-entry-container">
<?php if ($entries === []) { ?><div id="guestbook-empty-notes"><?php echo $lang->loc['guestbook.no.entries'] ?? 'No entries.'; ?></div><?php } ?>
<?php foreach ($entries as $entry) { $page['bypass'] = true; require __DIR__.'/home-guestbook-entry.php'; } ?>
</ul>
</div>
<?php if (!$edit && (int) $user->id > 0) { ?>
<div class="guestbook-toolbar clearfix">
<a href="#" class="new-button envelope-icon" id="guestbook-open-dialog"><b><span></span><?php echo $lang->loc['new.message'] ?? 'New message'; ?></b><i></i></a>
</div>
<?php } ?>
<?php } elseif ($key === 'highscoreswidget') { ?>
<table><tr><td><?php echo $lang->loc['no.high.scores'] ?? 'No high scores.'; ?></td></tr></table>
<?php } elseif ($key === 'badgeswidget') {
    $badges = $homes->badges((int) $owner['id']);
    if ($badges === []) { echo $lang->loc['no.badges'] ?? 'No badges.'; }
    else {
        echo '<ul class="clearfix">';
        foreach ($badges as $badge) {
            echo '<li style="background-image: url('.htmlspecialchars($settings->find('site_c_images_path').$settings->find('site_badges_path').rawurlencode($badge['badge_code']).'.gif', ENT_QUOTES, 'UTF-8').')"></li>';
        }
        echo '</ul>';
    }
} elseif ($key === 'friendswidget') { ?>
<div id="avatar-list-search">
<input type="text" style="float:left;" id="avatarlist-search-string"/>
<a class="new-button" style="float:left;" id="avatarlist-search-button"><b><?php echo $lang->loc['search'] ?? 'Search'; ?></b><i></i></a>
</div>
<br clear="all"/>
<div id="avatarlist-content">
<?php
$widgetid = $widgetId;
$search = '';
$pagenum = 1;
require __DIR__.'/../../habblet/myhabbo_avatarlist_friendsearchpaging.php';
?>
</div>
<?php } elseif ($key === 'groupswidget') {
    $groups = $homes->groups((int) $owner['id']);
    if ($groups === []) { echo $lang->loc['no.groups'] ?? 'No groups.'; }
    else {
        echo '<ul class="groups-list">';
        foreach ($groups as $group) {
            echo '<li><a href="'.habbletGroupURL((int) $group['id']).'">'.$input->HoloText($group['name']).'</a></li>';
        }
        echo '</ul>';
    }
} elseif ($key === 'roomswidget') {
    $rooms = $homes->rooms((int) $owner['id']);
    if ($rooms === []) { echo $lang->loc['no.rooms'] ?? 'No rooms.'; }
    else {
        echo '<ul class="rooms-list">';
        foreach ($rooms as $room) {
            echo '<li>'.$input->HoloText($room['name']).'</li>';
        }
        echo '</ul>';
    }
} ?>
<div class="clear"></div>
</div></div>
</div>
</div>
