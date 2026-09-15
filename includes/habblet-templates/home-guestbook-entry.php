<?php
$online = (($entry['online'] ?? '0') === '1') ? 'online' : 'offline';
?>
<li id="guestbook-entry-<?php echo (int) $entry['id']; ?>" class="guestbook-entry">
<div class="guestbook-author">
<img src="<?php echo $user->avatarURL($entry['look'], 's,4,4,,1,0'); ?>" alt="<?php echo $input->HoloText($entry['username']); ?>" title="<?php echo $input->HoloText($entry['username']); ?>"/>
</div>
<div class="guestbook-actions">
<?php if ((int) $user->id === (int) $entry['author_user_id'] || (int) $user->id === (int) $entry['profile_user_id']) { ?>
<img src="<?php echo PATH; ?>/web-gallery/images/myhabbo/buttons/delete_entry_button.gif" id="gbentry-delete-<?php echo (int) $entry['id']; ?>" class="gbentry-delete" style="cursor:pointer" alt=""/>
<?php } ?>
</div>
<div class="guestbook-message">
<div class="<?php echo $online; ?>">
<a href="<?php echo PATH; ?>/home/<?php echo rawurlencode($entry['username']); ?>"><?php echo $input->HoloText($entry['username']); ?></a>
</div>
<p><?php echo $input->bbcode_format($input->HoloText($entry['message'])); ?></p>
</div>
<div class="guestbook-cleaner">&nbsp;</div>
<div class="guestbook-entry-footer metadata"><?php echo date('M j, Y g:i:s A', (int) $entry['created_at']); ?></div>
</li>
