<table border="0" cellpadding="0" cellspacing="0" width="100%" class="group-postlist-list" id="group-postlist-list">
<tr class="post-list-index-preview">
	<td class="post-list-row-container">
	<?php if(($author['online'] === '1') == true){ $online = "online_anim"; }else{ $online = "offline"; } ?>
		<a href="<?php echo PATH; ?>/home/<?php echo $user->id; ?>/id" class="post-list-creator-link post-list-creator-info"><?php echo $input->HoloText($author['username']); ?></a>
            <img alt="<?php echo $online; ?>" src="<?php echo PATH; ?>/web-gallery/images/myhabbo/habbo_<?php echo $online; ?>.gif" />
		<div class="post-list-posts post-list-creator-info"><?php echo $lang->loc['message']; ?>: <?php echo $posts; ?></div>
		<div class="clearfix">
            <div class="post-list-creator-avatar"><img src="<?php echo $user->avatarURL($author['look'],"b,2,2,,1,0"); ?>" alt="" /></div>
            <div class="post-list-group-badge">

            </div>
            <div class="post-list-avatar-badge">
				<?php if($author['badge_code']){ ?><img src="<?php echo htmlspecialchars($settings->find('site_c_images_path').$settings->find('site_badges_path').rawurlencode($author['badge_code']).'.gif', ENT_QUOTES, 'UTF-8'); ?>" /><?php } ?>
			</div>
        </div>
        <div class="post-list-motto post-list-creator-info">
			<?php if($author['motto'] != ""){ echo $input->unicodeToImage($input->HoloText($author['motto'])); } ?>
		</div>
	</td>
	<td class="post-list-message" valign="top" colspan="2">
            <a href="#" id="edit-post-message" class="resume-edit-link">&laquo; <?php echo $lang->loc['edit']; ?></a>
        <span class="post-list-message-header"> <?php echo $name; ?></span><br />
        <span class="post-list-message-time"><?php echo date('M j, Y (g:i A)'); ?></span>
        <div class="post-list-report-element">
        </div>
        <div class="post-list-content-element">
            <?php echo $message; ?>
        </div>
        <div>
                <?php if($settings->find("site_capcha") == "1"){ ?><div id="discussion-captcha-preview"></div><?php } ?>
                <div class="button-area">
		            <a id="topic-form-cancel-preview" class="new-button red-button cancel-icon" href="#"><b><span></span><?php echo $lang->loc['cancel']; ?></b><i></i></a>
		            <a id="topic-form-save-preview" class="new-button green-button save-icon" href="#"><b><span></span><?php echo $lang->loc['save']; ?></b><i></i></a>
		        </div>
        </div>
	</td>
</tr>
</table>
