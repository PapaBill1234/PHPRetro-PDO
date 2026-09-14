<div class="groups-info-basic">
	<div class="groups-info-close-container"><a href="#" class="groups-info-close"></a></div>

	<div class="groups-info-icon"><a href="<?php echo habbletGroupURL($group['id']); ?>"><img src="<?php echo PATH; ?>/web-gallery/images/groups/group_icon.gif" /></a></div>
	<h4><a href="<?php echo habbletGroupURL($group['id']); ?>"><?php echo $input->HoloText($group['name']); ?></a></h4>
	    <img id="groupname-<?php echo $group['id']; ?>-report" class="report-button report-gn"
			alt="report"
			src="<?php echo PATH; ?>/web-gallery/images/myhabbo/buttons/report_button.gif"
			style="display: none;" />

	<p>
<?php if($favorite){ ?><img src="<?php echo PATH; ?>/web-gallery/images/groups/favourite_group_icon.gif" width="15" height="15" class="groups-list-icon" alt="<?php echo $lang->loc['favorite']; ?>" title="<?php echo $lang->loc['favorite']; ?>" /><?php } ?>
<?php if((int) $group['user_id'] === $ownerid){ ?><img src="<?php echo PATH; ?>/web-gallery/images/groups/owner_icon.gif" width="15" height="15" class="groups-list-icon" alt="<?php echo $lang->loc['owner']; ?>" title="<?php echo $lang->loc['owner']; ?>" /><?php } ?>
<?php if($rank === 1 && (int) $group['user_id'] !== $ownerid){ ?><img src="<?php echo PATH; ?>/web-gallery/images/groups/administrator_icon.gif" width="15" height="15" class="groups-list-icon" alt="<?php echo $lang->loc['admin'] ?>" title="<?php echo $lang->loc['admin']; ?>" /><?php } ?>
<?php echo $lang->loc['group.created']; ?>:<br />
<b><?php echo date('M j, Y', (int) $group['date_created']); ?></b>
	</p>

	<div class="groups-info-description"><?php echo $input->HoloText($group['description']); ?></div>
	    <img id="groupdesc-<?php echo $group['id']; ?>-report" class="report-button report-gd"
	        alt="report"
	        src="<?php echo PATH; ?>/web-gallery/images/myhabbo/buttons/report_button.gif"
            style="display: none;" />
</div>
