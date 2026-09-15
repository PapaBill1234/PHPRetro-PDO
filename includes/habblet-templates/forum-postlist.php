    <div class="postlist-header clearfix">
<?php if($canReply){ ?>
                    <a href="#" id="create-post-message" class="create-post-link verify-email"><?php echo $lang->loc['post.reply']; ?></a>
                    <input type="hidden" id="email-verfication-ok" value="<?php echo (int) $viewer['mail_verified'] == 1 ? "1" : "0"; ?>"/>
<?php }elseif((int) $threadrow['locked'] === 1){ ?>
<span class="topic-closed"><img src="<?php echo PATH; ?>/web-gallery/images/groups/status_closed.gif" title="<?php echo $lang->loc['closed.thread']; ?>"> <?php echo $lang->loc['closed.thread']; ?></span>
<?php }
if($moderator || $threadrow['opener_id'] == $user->id){
?>
                <a href="#" id="edit-topic-settings" class="edit-topic-settings-link"><?php echo $lang->loc['edit.thread']; ?> &raquo;</a>
                <input type="hidden" id="settings_dialog_header" value="<?php echo $lang->loc['edit.thread.settings']; ?>"/>
<?php } ?>
<?php
$end = 9; if(($pagenum + $end) > $pages){ $end = $pages - $pagenum; }
$links = "";
if($pages == 0){ $links = "0"; }else{
	if($pagenum != 1){ $links .= "<a href=\"".habbletGroupURL($threadrow['guild_id'])."/discussions/".$threadrow['id']."/id/page/".($pagenum - 1)."\" >&lt;&lt;</a>\n"; }
	$links .= $pagenum."\n";
	$i = 0; while($i < $end){ $i++; $links .= "<a href=\"".habbletGroupURL($threadrow['guild_id'])."/discussions/".$threadrow['id']."/id/page/".($pagenum + $i)."\">".($pagenum + $i)."</a>\n"; }
	if($pagenum + 9 < $pages){ $links .= "<a href=\"".habbletGroupURL($threadrow['guild_id'])."/discussions/".$threadrow['id']."/id/page/".($pagenum + 1)."\" >&gt;&gt;</a>\n"; }
}
$offset = ($pagenum - 1) * 10;
?>
        <div class="page-num-list">
            <input type="hidden" id="current-page" value="<?php echo $pagenum; ?>"/>
    <?php echo $lang->loc['view.page'] ?>:
<?php echo $links; ?>        </div>
    </div>
<table border="0" cellpadding="0" cellspacing="0" width="100%" class="group-postlist-list" id="group-postlist-list">
<?php
$i = 0;
foreach($comments as $row){
$author = $groups->author((int) $row['user_id']);
$online = $author['online'] === '1' ? 'online_anim' : 'offline';
$posts = $author['posts'];
if($row['id'] == $firstid){ $row['title'] = $threadrow['subject']; }else{ $row['title'] = "RE: ".$threadrow['subject']; }
if($input->IsEven($i)){ $even = "even"; }else{ $even = "odd"; }
?>

<tr class="post-list-index-<?php echo $even; ?>">
	<td class="post-list-row-container">
		<a href="<?php echo PATH; ?>/home/<?php echo $author['id']; ?>/id" class="post-list-creator-link post-list-creator-info"><?php echo $input->HoloText($author['username']); ?></a>

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
        <div class="post-list-motto post-list-creator-info"><?php echo $input->HoloText($author['motto']); ?></div>
	</td>
	<td class="post-list-message" valign="top" colspan="2">
                    <?php if($canReply){ ?><a href="#" class="quote-post-link verify-email" id="quote-post-<?php echo $row['id']; ?>-message"><?php echo $lang->loc['quote'] ?></a><?php } ?>
                    <?php if(($row['user_id'] == $user->id || $moderator) && (int) $threadrow['locked'] === 0 && in_array((int) $row['state'], [0, 1], true)){ ?><a href="#" class="edit-post-link verify-email" id="edit-post-<?php echo $row['id']; ?>-message"><?php echo $lang->loc['edit']; ?></a><?php } ?>
        <span class="post-list-message-header"><?php echo $input->HoloText($row['title']); ?></span><br />
        <span class="post-list-message-time"><?php echo date('M j, Y (g:i A)',$row['created_at']); ?></span>
        <div class="post-list-report-element">
                <?php if($row['user_id'] != $user->id){ ?><a href="#" id="report-post-<?php echo $row['id']; ?>" class="create-report-button report-post"></a><?php } ?>

        </div>
        <div class="post-list-content-element">

            <?php echo habbletForumText($row['message']); ?>
                <input type="hidden" id="<?php echo $row['id']; ?>-message" value="<?php echo htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8'); ?>" />
        </div>
        <div>
        </div>

	</td>
</tr>

<?php if($i == 0){ ?>

	<tr class="postlist-leaderboard">
	    <td colspan="3">    <div class="habblet ad-forum-leaderboard">

    </div>
</td>
	</tr>

<?php } $i++; }
$lang->addLocale("groups.discussion.newtopic");
$lang->addLocale("ajax.buttons"); ?>
<tr id="new-post-entry-message" style="display:none;">

	<td class="new-post-entry-label"><div class="new-post-entry-label" id="new-post-entry-label"><?php echo $lang->loc['post']; ?>:</div></td>
	<td colspan="2">
		<table border="0" cellpadding="0" cellspacing="0" style="margin: 5px; width: 98%;">
		<tr>
		<td>
		<input type="hidden" id="edit-type" />

		<input type="hidden" id="post-id"  />
        <a href="#" class="preview-post-link" id="post-form-preview"><?php echo $lang->loc['preview']; ?> &raquo;</a>
        <input type="hidden" id="spam-message" value="<?php echo $lang->loc['spam.detected']; ?>"/>
		<textarea id="post-message" class="new-post-entry-message" rows="5" name="message" ></textarea>
    <script type="text/javascript">
        bbcodeToolbar = new Control.TextArea.ToolBar.BBCode("post-message");
        bbcodeToolbar.toolbar.toolbar.id = "bbcode_toolbar";
		<?php $colors = explode("|",$lang->loc['colors']); ?>
        var colors = { "red" : ["#d80000", "<?php echo addslashes($colors[0]); ?>"],
            "orange" : ["#fe6301", "<?php echo addslashes($colors[1]); ?>"],
            "yellow" : ["#ffce00", "<?php echo addslashes($colors[2]); ?>"],
            "green" : ["#6cc800", "<?php echo addslashes($colors[3]); ?>"],
            "cyan" : ["#00c6c4", "<?php echo addslashes($colors[4]); ?>"],
            "blue" : ["#0070d7", "<?php echo addslashes($colors[5]); ?>"],
            "gray" : ["#828282", "<?php echo addslashes($colors[6]); ?>"],
            "black" : ["#000000", "<?php echo addslashes($colors[7]); ?>"]
        };
        bbcodeToolbar.addColorSelect("<?php echo addslashes($lang->loc['colors.desc']); ?>", colors, false);
    </script>
<div id="linktool-inline">
    <div id="linktool-scope">
        <label for="linktool-query-input"><?php echo $lang->loc['create.link.to']; ?>:</label>

        <input type="radio" name="scope" class="linktool-scope" value="1" checked="checked"/><?php echo $lang->loc['habbos']; ?>
        <input type="radio" name="scope" class="linktool-scope" value="2"/><?php echo $lang->loc['rooms']; ?>
        <input type="radio" name="scope" class="linktool-scope" value="3"/><?php echo $lang->loc['groups']; ?>&nbsp;
    </div>
    <div class="linktool-input">
        <input id="linktool-query" type="text" size="30" name="query" value=""/>
        <input id="linktool-find" class="search" type="submit" title="<?php echo $lang->loc['find']; ?>" value=""/>
    </div>
    <div class="clear" style="height: 0;"><!-- --></div>

    <div id="linktool-results" style="display: none">
    </div>
    <script type="text/javascript">
        linkTool = new LinkTool(bbcodeToolbar.textarea);
    </script>
</div>
	    <div id="discussion-captcha">
<h3>
<label for="bean_captcha" class="registration-text"><?php echo $lang->loc['type.security.code']; ?></label>
</h3>

<div id="captcha-code-error"></div>

<p></p>

<div class="register-label" id="captcha-reload">
    <p>
        <img src="<?php echo PATH; ?>/web-gallery/v2/images/shared_icons/reload_icon.gif" width="15" height="15" alt=""/>
        <a id="captcha-reload-link" href="#"><?php echo $lang->loc['cant.read.code']; ?></a>
    </p>
</div>

<script type="text/javascript">
document.observe("dom:loaded", function() {
    Event.observe($("captcha-reload"), "click", function(e) {Utils.reloadCaptcha()});
});
</script>

<p id="captcha-container">
</p>

<p>
<input type="text" name="captcha" id="captcha-code" value="" class="registration-text required-captcha" />
</p>
</div>
        <div class="button-area">
            <a id="post-form-cancel" class="new-button red-button cancel-icon" href="#"><b><span></span><?php echo $lang->loc['cancel']; ?></b><i></i></a>
            <a id="post-form-save" class="new-button green-button save-icon" href="#"><b><span></span><?php echo $lang->loc['save']; ?></b><i></i></a>
        </div>

        </td>
        </tr>
        </table>
	</td>
</tr>
</table>
<div id="new-post-preview" style="display:none;">
</div>
    <div class="postlist-footer clearfix">
<?php if($canReply){ ?>
                    <a href="#" id="create-post-message" class="create-post-link verify-email"><?php echo $lang->loc['post.reply']; ?></a>
<?php }elseif((int) $threadrow['locked'] === 1){ ?>
<span class="topic-closed"><img src="<?php echo PATH; ?>/web-gallery/images/groups/status_closed.gif" title="<?php echo $lang->loc['closed.thread']; ?>"> <?php echo $lang->loc['closed.thread']; ?></span>
<?php }elseif($user->id == 0){ ?>
<p style="padding: 0 10px 10px 10px">
<?php echo $lang->loc['requires.login'] ?>
<a href="<?php echo PATH; ?>/"><?php echo $lang->loc['sign.in.now']; ?></a>
<?php } ?>

</p>        <div class="page-num-list">
    <?php echo $lang->loc['view.page']; ?>:
<?php echo $links; ?>        </div>
    </div>

<script type="text/javascript">
L10N.put("myhabbo.discussion.error.topic_name_empty", "<?php echo addslashes($lang->loc['topic.name.empty']); ?>");
L10N.put("register.error.security_code", "<?php echo addslashes($lang->loc['invalid.capcha']); ?>");
Discussions.initialize("<?php echo $group['id']; ?>", "<?php echo ''; ?>", "<?php echo $threadrow['id']; ?>");
Discussions.captchaPublicKey = "<?php echo time(); ?>";
Discussions.captchaUrl = "<?php echo PATH; ?>/captcha.jpg?t=";
</script>
