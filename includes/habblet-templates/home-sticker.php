<?php
$id = (int) $item['id'];
$css = $homes->itemCss('sticker', $item['catalogue_data']);
$edit = !empty($page['edit']);
?>
    <div class="movable sticker <?php echo htmlspecialchars($css, ENT_QUOTES, 'UTF-8'); ?>" style="left: <?php echo (int) $item['x']; ?>px; top: <?php echo (int) $item['y']; ?>px; z-index: <?php echo (int) $item['z']; ?>" id="sticker-<?php echo $id; ?>">
<?php if ($edit) { ?>
<img src="<?php echo PATH; ?>/web-gallery/images/myhabbo/icon_edit.gif" width="19" height="18" class="edit-button" id="sticker-<?php echo $id; ?>-edit" />
<script language="JavaScript" type="text/javascript">
Event.observe("sticker-<?php echo $id; ?>-edit", "click", function(e) { openEditMenu(e, <?php echo $id; ?>, "sticker", "sticker-<?php echo $id; ?>-edit"); }, false);
</script>
<?php } ?>
    </div>
