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
$lang->addLocale('tags.ajax');
$lang->addLocale('ajax.buttons');
$tags = empty($user->logged_in) ? [] : habbletUserTags($db, (int) $user->id);
?>
<div id="profile-tags-container">
<?php if ($tags === []) { echo $lang->loc['no.tags']; } else { foreach ($tags as $tag) { ?>
    <span class="tag-search-rowholder">
        <a href="<?php echo PATH; ?>/tag/<?php echo rawurlencode($tag); ?>" class="tag"><?php echo $input->HoloText($tag); ?></a><img border="0" class="tag-delete-link" onMouseOver="this.src='<?php echo PATH; ?>/web-gallery/images/buttons/tags/tag_button_delete_hi.gif'" onMouseOut="this.src='<?php echo PATH; ?>/web-gallery/images/buttons/tags/tag_button_delete.gif'" src="<?php echo PATH; ?>/web-gallery/images/buttons/tags/tag_button_delete.gif" />
    </span>
<?php } ?>
    <img id="tag-img-added" border="0" class="tag-none-link" src="<?php echo PATH; ?>/web-gallery/images/buttons/tags/tag_button_added.gif" style="display:none"/>
<?php } ?>
</div>
<script type="text/javascript">
    document.observe("dom:loaded", function() {
        TagHelper.setTexts({
            buttonText: "<?php echo addslashes($lang->loc['ok']); ?>",
            tagLimitText: "<?php echo addslashes($lang->loc['tags.limit']); ?>"
        });
    });
</script>
