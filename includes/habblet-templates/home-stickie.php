<?php
$id = (int) $item['id'];
$skin = preg_replace('/[^a-z0-9_]/', '', (string) ($item['skin'] ?: 'defaultskin'));
$edit = !empty($page['edit']);
$html = $homes->rank() > 5;
$note = $input->bbcode_format($input->HoloText((string) $item['data'], $html));
?>
<div class="movable stickie n_skin_<?php echo $skin; ?>-c" style=" left: <?php echo (int) $item['x']; ?>px; top: <?php echo (int) $item['y']; ?>px; z-index: <?php echo (int) $item['z']; ?>;" id="stickie-<?php echo $id; ?>">
	<div class="n_skin_<?php echo $skin; ?>" >
		<div class="stickie-header">
			<h3>
<?php if ($edit) { ?>
<img src="<?php echo PATH; ?>/web-gallery/images/myhabbo/icon_edit.gif" width="19" height="18" class="edit-button" id="stickie-<?php echo $id; ?>-edit" />
<script language="JavaScript" type="text/javascript">
Event.observe("stickie-<?php echo $id; ?>-edit", "click", function(e) { openEditMenu(e, <?php echo $id; ?>, "stickie", "stickie-<?php echo $id; ?>-edit"); }, false);
</script>
<?php } ?>
			</h3>
			<div class="clear"></div>
		</div>
		<div class="stickie-body">
			<div class="stickie-content">
				<div class="stickie-markup"><?php echo $note; ?></div>
				<div class="stickie-footer">
				</div>
			</div>
		</div>
	</div>
</div>
