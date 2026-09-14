<div id="group-memberlist-members-list">

<form method="post" action="#" onsubmit="return false;">
<ul class="habblet-list two-cols clearfix">
<?php $i = 0; $n = 0; foreach($members as $row){
if($input->IsEven($i)){ $side = "left"; }else{ $side = "right"; $n++; }
if($input->IsEven($n)){ $even = "even"; }else{ $even = "odd"; }
if(($row['online'] === '1')){ $online = "online"; }else{ $online = "offline"; }
?>

    <li class="<?php echo $even; ?> <?php echo $online; ?> <?php echo $side; ?>">
	<div class="item" style="padding-left: 5px; padding-bottom: 4px;">
		<div style="float: right; width: 16px; height: 16px; margin-top: 1px">
				<?php if((int) $row['level_id'] === 0){ ?><img src="<?php echo PATH; ?>/web-gallery/images/groups/owner_icon.gif" width="15" height="15" alt="<?php echo $lang->loc['owner']; ?>" title="<?php echo $lang->loc['owner']; ?>" /><?php } ?>
				<?php if((int) $row['level_id'] === 1){ $type = "a"; ?><img src="<?php echo PATH; ?>/web-gallery/images/groups/administrator_icon.gif" width="15" height="15" alt="<?php echo $lang->loc['administrator']; ?>" title="<?php echo $lang->loc['administrator']; ?>" /><?php } ?>
				<?php if((int) $row['level_id'] >= 2){ $type = "m"; } ?>
			</div>
				<input type="checkbox" <?php if($row['user_id'] == $group['user_id']){ ?>disabled="disabled" <?php }else{ ?>id="group-memberlist-<?php echo $type; ?>-<?php echo $row['user_id']; ?>" <?php } ?>style="margin: 0; padding: 0; vertical-align: middle"/>
	    <a class="home-page-link" href="<?php echo PATH; ?>/home/<?php echo $input->HoloText($row['username']); ?>"><span><?php echo $input->HoloText($row['username']); ?></span></a>
        </div>
    </li>

<?php $i++; } ?>
<?php if(!$input->IsEven($i)){ ?><li class="<?php echo $even; ?> right"><div class="item">&nbsp;</div></li><?php } ?>
</ul>

</form>
</div>
<div id="member-list-pagenumbers">
<?php
if($count['search'] == 0){ echo "0 - 0"; }else{
$at = $pagenum - 1;
$at = $at * 12;
$at = $at + 1;
$to = $offset + 12;
if($to > $count['search']){ $to = $count['search']; }
if($limit == 0){ $to = $count['search']; }
$totalpages = max(1, (int) ceil($count['search'] / 12));
if($count['search'] > 0){ echo $at; ?> - <?php echo $to; ?> / <?php echo $count['search']; }else{ echo "0 / 0"; } ?>
</div>
<div id="member-list-paging" style="display:none;">
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
<input type="hidden" id="pageNumberMemberList" value="<?php echo $pagenum; ?>"/>
<input type="hidden" id="totalPagesMemberList" value="<?php echo $totalpages; ?>"/>
</div>
