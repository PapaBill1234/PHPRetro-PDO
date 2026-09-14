<div class="avatar-widget-list-container">
<ul id="avatar-list-list" class="avatar-widget-list">
<?php if($count == 0 && $search == ""){ echo $lang->loc['no.members']; }else{ ?>

<?php foreach($members as $frow){ ?>

	<li id="avatar-list-<?php echo $widgetid; ?>-<?php echo $frow['user_id']; ?>" title="<?php echo $input->HoloText($frow['username']); ?>"><div class="avatar-list-open"><a href="#" id="avatar-list-open-link-<?php echo $widgetid; ?>-<?php echo $frow['user_id']; ?>" class="avatar-list-open-link"></a></div>
<div class="avatar-list-avatar"><img src="<?php echo $user->avatarURL($frow['look'],"s,2,2,sml,1,0"); ?>" alt="" /></div>
<h4><a href="<?php echo PATH; ?>/home/<?php echo $input->HoloText($frow['username']); ?>"><?php echo $input->HoloText($frow['username']); ?></a></h4>
<p class="avatar-list-birthday"><?php echo date('M j, Y', (int) $frow['account_created']); ?></p>
<p>
<?php if((int) $frow['level_id'] === 0){ ?><img src="<?php echo PATH; ?>/web-gallery/images/groups/owner_icon.gif" alt="" class="avatar-list-groupstatus" /><?php } ?>
<?php if((int) $frow['level_id'] === 1){ ?><img src="<?php echo PATH; ?>/web-gallery/images/groups/administrator_icon.gif" alt="" class="avatar-list-groupstatus" /><?php } ?>
<?php if((int) $frow['favorite_id'] === $groupid){ ?><img src="<?php echo PATH; ?>/web-gallery/images/groups/favourite_group_icon.gif" alt="" class="avatar-list-groupstatus" /><?php } ?>
</p></li>

<?php } ?>
</ul>
<?php } ?>

<div id="avatar-list-info" class="avatar-list-info">
<div class="avatar-list-info-close-container"><a href="#" class="avatar-list-info-close"></a></div>
<div class="avatar-list-info-container"></div>
</div>

</div>

<div id="avatar-list-paging">
<?php
if($count == 0){ echo "0 - 0"; }else{
$at = $pagenum - 1;
$at = $at * 20;
$at = $at + 1;
$to = $offset + 20;
if($to > $count){ $to = $count; }
$totalpages = ceil($count / 20);
?>
    <?php echo $at; ?> - <?php echo $to; ?> / <?php echo $count; ?>
    <br/>
	<?php if($pagenum != 1){ ?>
    <a href="#" class="avatar-list-paging-link" id="avatarlist-search-first" ><?php echo $lang->loc['first']; ?></a> |
    <a href="#" class="avatar-list-paging-link" id="avatarlist-search-previous" >&lt;&lt;</a> |
	<?php }else{ ?>
	<?php echo $lang->loc['first']; ?> |
    &lt;&lt; |
	<?php } ?>
	<?php if($pagenum != $totalpages){ ?>
    <a href="#" class="avatar-list-paging-link" id="avatarlist-search-next" >&gt;&gt;</a> |
    <a href="#" class="avatar-list-paging-link" id="avatarlist-search-last" ><?php echo $lang->loc['last']; ?></a>
	<?php }else{ ?>
	&gt;&gt; |
    <?php echo $lang->loc['last']; ?>
	<?php } ?>
<?php } ?>
<input type="hidden" id="pageNumber" value="<?php echo $pagenum; ?>"/>
<input type="hidden" id="totalPages" value="<?php echo $totalpages; ?>"/>
</div>

<script type="text/javascript">
<?php if($bypass){ ?>
document.observe("dom:loaded", function() {
	window.widget<?php echo $widgetid; ?> = new MemberWidget('<?php echo $groupid; ?>', '<?php echo $widgetid; ?>');
});
<?php } ?>
</script>
