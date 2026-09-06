<?php
// FILE: discussions.php
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

$page['allow_guests'] = true;
$page['no_column3'] = true;
require_once('./includes/core.php');
require_once('./includes/session.php');
$data = new home_sql;
$lang->addLocale("home.homes");
$lang->addLocale("community.groups");
$lang->addLocale("groups.discussion");
$lang->addLocale("ajax.buttons");

$db = new Database();

function millisecondsToMinutes($int){
	return ceil($int / 60000);
}
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if(isset($_GET['alias']) && !isset($_GET['id'])){
	$alias = $_GET['alias']; // assume string
	$group_id = $db->fetchColumn("SELECT id FROM groups WHERE name_seo = ?", [$alias]);
	if($group_id){
		$id = (int)$group_id;
	}else{
		$id = 0;
	}
}

$grouprow = $db->fetchRow("SELECT id, name, name_seo, description, type, allow_members, owner, owner_name, forum_enabled, forum_type, tag FROM groups WHERE id = ?", [$id]);
if(!$grouprow){ $lang->clearLocale; require_once('./error.php'); exit; }

$memberrow = $db->fetchRow("SELECT user_id, group_id, rank, is_favorite FROM user_groups WHERE user_id = ? AND group_id = ?", [$user->id, $grouprow['id']]);
if(!$memberrow){
	$memberrow = ['user_id'=>null, 'group_id'=>null, 'rank'=>0, 'is_favorite'=>0];
}

$page['id'] = "home";
$page['type'] = "groups";
$page['name'] = $input->HoloText($grouprow['name']).$lang->loc['pagename.groups'];
$page['cat'] = "community";
$page['discussion'] = true;

require_once('./templates/community_header.php');
?>
<div id="container">
	<div id="content" style="position: relative" class="clearfix">
    <div id="mypage-wrapper" class="cbb blue">
<div class="box-tabs-container box-tabs-left clearfix">
	<?php if($memberrow['rank'] > 1 && $page['edit'] != true){ ?><a href="#" id="myhabbo-group-tools-button" class="new-button dark-button edit-icon" style="float:left"><b><span></span><?php echo $lang->loc['edit']; ?></b><i></i></a><?php } ?>
	<div class="myhabbo-view-tools">
				<?php if($memberrow['user_id'] != "" && $memberrow['is_favorite'] != 1){ ?>
					<a href="#" id="select-favorite-button"><?php echo $lang->loc['make.favorite']; ?></a>
				<?php }elseif($memberrow['user_id'] != "" && $memberrow['is_favorite'] == 1){ ?>
					<a href="#" id="deselect-favorite-button"><?php echo $lang->loc['remove.favorite']; ?></a>
				<?php } ?>
				<?php if($memberrow['user_id'] == ""){ ?>
					<?php if(($grouprow['allow_members'] == 0 || $grouprow['allow_members'] == 1 || $grouprow['allow_members'] == 3) && $user->id != 0){ ?><a href="<?php echo PATH; ?>/groups/actions/join?groupId=<?php echo $grouprow['id']; ?>" id="join-group-button"><?php if($grouprow['allow_members'] == 0){ echo $lang->loc['join']; }else{ echo $lang->loc['request.membership']; } ?></a><?php } ?>
				<?php }elseif($memberrow['rank'] < 3 && $memberrow['is_favorite'] != 1){ ?>
					<a href="<?php echo PATH; ?>/groups/actions/leave?groupId=<?php echo $grouprow['id']; ?>" id="leave-group-button"><?php echo $lang->loc['leave.group']; ?></a>
				<?php } ?>
	</div>
    <h2 class="page-owner">
    	<?php echo $input->HoloText($grouprow['name']); ?>
			<?php if($grouprow['allow_members'] == 1){ ?><img src="<?php echo PATH; ?>/web-gallery/images/groups/status_exclusive_big.gif" width="18" height="16" alt="<?php echo $lang->loc['exclusive.group']; ?>" title="<?php echo $lang->loc['exclusive.group']; ?>" class="header-bar-group-status" /><?php } ?>
			<?php if($grouprow['allow_members'] == 2){ ?><img src="<?php echo PATH; ?>/web-gallery/images/groups/status_closed_big.gif" width="18" height="16" alt="<?php echo $lang->loc['closed.group']; ?>" title="<?php echo $lang->loc['closed.group']; ?>" class="header-bar-group-status" /><?php } ?>
    </h2>
    <ul class="box-tabs">
        <li><a href="<?php echo groupURL($grouprow['id']); ?>"><?php echo $lang->loc['front.page']; ?></a><span class="tab-spacer"></span></li>
        <li class="selected"><a href="<?php echo groupURL($grouprow['id']); ?>/discussions"><?php echo $lang->loc['discussion.forum']; ?><?php if($grouprow['forum_enabled'] == 1){ ?> <img src="<?php echo PATH; ?>/web-gallery/images/grouptabs/privatekey.png" title="<?php echo $lang->loc['private.forum']; ?>" alt="<?php echo $lang->loc['private.forum']; ?>" /><?php } ?></a><span class="tab-spacer"></span></li>
    </ul>
</div>
<?php if(isset($_GET['thread'])){
$page['discussion.post'] = true;
$lang->addLocale("groups.discussion.showtopic");
$lang->addLocale("groups.discussion.topic");
$threadid = (int) $_GET['thread'];
$threadrow = $db->fetchRow("SELECT * FROM forum_threads WHERE id = ? LIMIT 1", [$threadid]);
if(!$threadrow){ $lang->clearLocale; require_once('./error.php'); exit; }
if(!isset($_SESSION['threadviewed'][$threadrow['id']]) || $_SESSION['threadviewed'][$threadrow['id']] != true){ 
    $db->execute("UPDATE forum_threads SET views = views + 1 WHERE id = ?", [$threadrow['id']]);
    $_SESSION['threadviewed'][$threadrow['id']] = true; 
}
?>
	<div id="mypage-content">
        <table border="0" cellpadding="0" cellspacing="0" width="100%" class="content-1col">
            <tr>
                <td valign="top" style="width: 750px;" class="habboPage-col rightmost">
                    <div id="discussionbox">
                        <div id="group-postlist-container">

    <div class="postlist-header clearfix">
<?php if($user->id != 0 && ((($grouprow['forum_type'] == 2 && $memberrow['rank'] > 1) || ($grouprow['forum_type'] == 1 && $memberrow['rank'] > 0)) || ($grouprow['forum_type'] == 0)) && $threadrow['open'] == "1"){ ?>
                    <a href="#" id="create-post-message" class="create-post-link verify-email"><?php echo $lang->loc['post.reply']; ?></a>
                    <input type="hidden" id="email-verfication-ok" value="<?php echo (int) $user->user("email_verified") == 1 ? "1" : "0"; ?>"/>
<?php }elseif($threadrow['open'] == "0"){ ?>
<span class="topic-closed"><img src="<?php echo PATH; ?>/web-gallery/images/groups/status_closed.gif" title="<?php echo $lang->loc['closed.thread']; ?>"> <?php echo $lang->loc['closed.thread']; ?></span>
<?php }
if($memberrow['rank'] > 1 || $threadrow['starterid'] == $user->id){
?>
                <a href="#" id="edit-topic-settings" class="edit-topic-settings-link"><?php echo $lang->loc['edit.thread']; ?> &raquo;</a>
                <input type="hidden" id="settings_dialog_header" value="<?php echo $lang->loc['edit.thread.settings']; ?>"/>
<?php } ?>
<?php
if(isset($_GET['page'])){ $pagenum = (int) $_GET['page']; }else{ $pagenum = 1; }
$total = $db->fetchColumn("SELECT COUNT(*) FROM forum_posts WHERE threadid = ?", [$threadrow['id']]);
$pages = ceil($total / 10);
if($pagenum == "-1"){ $pagenum = $pages; }
$end = 9; if(($pagenum + $end) > $pages){ $end = $pages - $pagenum; }
$links = "";
if($pages == 0){ $links = "0"; }else{
	if($pagenum != 1){ $links .= "<a href=\"".groupURL($threadrow['groupid'])."/discussions/".$threadrow['id']."/id/page/".($pagenum - 1)."\" >&lt;&lt;</a>\n"; }
	$links .= $pagenum."\n";
	$i = 0; while($i < $end){ $i++; $links .= "<a href=\"".groupURL($threadrow['groupid'])."/discussions/".$threadrow['id']."/id/page/".($pagenum + $i)."\">".($pagenum + $i)."</a>\n"; }
	if($pagenum + 9 < $pages){ $links .= "<a href=\"".groupURL($threadrow['groupid'])."/discussions/".$threadrow['id']."/id/page/".($pagenum + 1)."\" >&gt;&gt;</a>\n"; }
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
$rows = $db->fetchAll("SELECT * FROM forum_posts WHERE threadid = ? ORDER BY time ASC LIMIT ? OFFSET ?", [$threadrow['id'], 10, $offset]);
$firstid = $db->fetchColumn("SELECT id FROM forum_posts WHERE threadid = ? ORDER BY time ASC LIMIT 1", [$threadrow['id']]);
$i = 0;
foreach($rows as $row){
$posterrow = $db->fetchRow("SELECT id, name, figure FROM users WHERE id = ?", [$row['posterid']]);
if(!$posterrow){ continue; }
if($user->IsUserOnline($posterrow['id']) == true){ $online = "online_anim"; }else{ $online = "offline"; }
$posts = $db->fetchColumn("SELECT COUNT(id) FROM forum_posts WHERE posterid = ?", [$posterrow['id']]);
if($row['id'] == $firstid){ $row['title'] = $threadrow['title']; }else{ $row['title'] = "RE: ".$threadrow['title']; }
if($input->IsEven($i)){ $even = "even"; }else{ $even = "odd"; }
?>

<tr class="post-list-index-<?php echo $even; ?>">
	<td class="post-list-row-container">
		<a href="<?php echo PATH; ?>/home/<?php echo $posterrow['id']; ?>/id" class="post-list-creator-link post-list-creator-info"><?php echo $input->HoloText($posterrow['name']); ?></a>

            <img alt="<?php echo $online; ?>" src="<?php echo PATH; ?>/web-gallery/images/myhabbo/habbo_<?php echo $online; ?>.gif" />
		<div class="post-list-posts post-list-creator-info"><?php echo $lang->loc['message']; ?>: <?php echo $posts; ?></div>
		<div class="clearfix">
            <div class="post-list-creator-avatar"><img src="<?php echo $user->avatarURL($posterrow['figure'],"b,2,2,,1,0"); ?>" alt="" /></div>
            <div class="post-list-group-badge">
                <?php if($user->GetUserGroup($posterrow['id']) != false){ ?><a href="<?php echo groupURL($user->GetUserGroup($posterrow['id'])); ?>"><img src="<?php echo PATH; ?>/habbo-imaging/badge/<?php echo $user->GetUserGroupBadge($posterrow['id']); ?>.gif" /></a><?php } ?>
            </div>
            <div class="post-list-avatar-badge">
				<?php if($user->GetUserBadge($posterrow['id']) != false){ ?><img src="<?php echo $settings->find("site_c_images_path").$settings->find("site_badges_path").$user->GetUserBadge($posterrow['id']).".gif"; ?>" /><?php } ?>
			</div>
        </div>
        <div class="post-list-motto post-list-creator-info"><?php $input->unicodeToImage($user->user("mission")); ?></div>
	</td>
	<td class="post-list-message" valign="top" colspan="2">
                    <?php if($user->id != 0 && ((($grouprow['forum_type'] == 2 && $memberrow['rank'] > 1) || ($grouprow['forum_type'] == 1 && $memberrow['rank'] > 0)) || ($grouprow['forum_type'] == 0)) && $threadrow['open'] == "1"){ ?><a href="#" class="quote-post-link verify-email" id="quote-post-<?php echo $row['id']; ?>-message"><?php echo $lang->loc['quote'] ?></a><?php } ?>
                    <?php if(($row['posterid'] == $user->id || $memberrow['rank'] > 1) && $threadrow['open'] == "1" && $row['message'] != $lang->loc['post.deleted']){ ?><a href="#" class="edit-post-link verify-email" id="edit-post-<?php echo $row['id']; ?>-message"><?php echo $lang->loc['edit']; ?></a><?php } ?>
        <span class="post-list-message-header"><?php echo $input->HoloText($row['title']); ?></span><br />
        <span class="post-list-message-time"><?php echo date('M j, Y (g:i A)',$row['time']); ?></span>
        <div class="post-list-report-element">
                <?php if($row['posterid'] != $user->id){ ?><a href="#" id="report-post-<?php echo $row['id']; ?>" class="create-report-button report-post"></a><?php } ?>
				<?php if(($row['posterid'] == $user->id || $memberrow['rank'] > 1) && $threadrow['open'] == "1"){ ?><a href="#" id="delete-post-<?php echo $row['id']; ?>" class="delete-button delete-post"></a><?php } ?>
        </div>
        <div class="post-list-content-element">
			<?php if($row['edit_time'] != 0){ ?><span class="post-list-message-edited"><?php echo $lang->loc['last.edited']; ?>: <?php echo date('M j, Y (g:i A)',$row['edit_time']); ?></span><br /><?php } ?>
            <?php echo $input->bbcode_format(nl2br($input->HoloText($row['message']))); ?>
                <input type="hidden" id="<?php echo $row['id']; ?>-message" value="<?php echo $input->HoloText($row['message']); ?>" />
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

<?php if($settings->find("site_capcha") == "1"){ ?>
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
<?php } ?>

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
<?php if($user->id != 0 && ((($grouprow['forum_type'] == 2 && $memberrow['rank'] > 1) || ($grouprow['forum_type'] == 1 && $memberrow['rank'] > 0)) || ($grouprow['forum_type'] == 0)) && $threadrow['open'] == "1"){ ?>
                    <a href="#" id="create-post-message" class="create-post-link verify-email"><?php echo $lang->loc['post.reply']; ?></a>
<?php }elseif($threadrow['open'] == "0"){ ?>
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
Discussions.initialize("<?php echo $grouprow['id']; ?>", "<?php echo $grouprow['tag']; ?>", "<?php echo $threadrow['id']; ?>");
Discussions.captchaPublicKey = "<?php echo time(); ?>";
Discussions.captchaUrl = "<?php echo PATH; ?>/captcha.jpg?t=";
</script>
</div>
                    </div>
					
                </td>
                <td style="width: 4px;"></td>

                <td valign="top" style="width: 164px;">

<?php }else{ ?>
	<div id="mypage-content">
        <table border="0" cellpadding="0" cellspacing="0" width="100%" class="content-1col">
            <tr>
                <td valign="top" style="width: 750px;" class="habboPage-col rightmost">
                    <div id="discussionbox">
<?php if($grouprow['forum_enabled'] == 1 && $memberrow['rank'] < 1){ ?>
<div class="box-content">

<h1><?php echo $lang->loc['error.occurred']; ?></h1>

<p>
        <?php echo $lang->loc['view.denied']; ?> <br />
</p>

</div>
<?php }else{
$lang->addLocale("groups.discussion.threads");
?>
<div id="group-topiclist-container">

<div class="topiclist-header clearfix">
<?php if($user->id == 0 && $grouprow['forum_type'] == 0){ ?>
            <?php echo $lang->loc['sign.in.to.post']; ?>
<?php }elseif((($grouprow['forum_type'] == 2 && $memberrow['rank'] > 1) || ($grouprow['forum_type'] == 1 && $memberrow['rank'] > 0)) || ($grouprow['forum_type'] == 0)){ ?>
        <input type="hidden" id="email-verfication-ok" value="<?php echo (int) $user->user("email_verified") == 1 ? "1" : "0"; ?>"/>
        <a href="#" id="newtopic-upper" class="new-button verify-email newtopic-icon" style="float:left"><b><span></span><?php echo $lang->loc['new.post']; ?></b><i></i></a>
<?php } ?>
<?php
if(isset($_GET['page']) && is_numeric($_GET['page'])){ $pagenum = (int) $_GET['page']; }else{ $pagenum = 1; }
$total = $db->fetchColumn("SELECT COUNT(*) FROM forum_threads WHERE groupid = ?", [$id]);
$pages = ceil($total / 10);
$end = 9; if(($pagenum + $end) > $pages){ $end = $pages - $pagenum; }
$links = "";
if($pages == 0){ $links = "0"; }else{
	if($pagenum != 1){ $links .= "<a href=\"".groupURL($grouprow['id'])."/discussions/page/".($pagenum - 1)."\" >&lt;&lt;</a>\n"; }
	$links .= $pagenum."\n";
	$i = 0; while($i < $end){ $i++; $links .= "<a href=\"".groupURL($grouprow['id'])."/discussions/page/".($pagenum + $i)."\">".($pagenum + $i)."</a>\n"; }
	if($pagenum + 9 < $pages){ $links .= "<a href=\"".groupURL($grouprow['id'])."/discussions/page/".($pagenum + 1)."\" >&gt;&gt;</a>\n"; }
}
$offset = ($pagenum - 1) * 10;
?>
    <div class="page-num-list">
    <?php echo $lang->loc['view.page']; ?>:
<?php echo $links; ?>    </div>
</div>
<table class="group-topiclist" border="0" cellpadding="0" cellspacing="0" id="group-topiclist-list">
	<tr class="topiclist-columncaption">
		<td class="topiclist-columncaption-topic"><?php echo $lang->loc['thread.and.first.poster']; ?></td>
		<td class="topiclist-columncaption-lastpost"><?php echo $lang->loc['last.post']; ?></td>
		<td class="topiclist-columncaption-replies"><?php echo $lang->loc['replies']; ?></td>
		<td class="topiclist-columncaption-views"><?php echo $lang->loc['views']; ?></td>
	</tr>
	
<?php
$rows = $db->fetchAll("SELECT * FROM forum_threads WHERE groupid = ? ORDER BY sticky ASC, time DESC LIMIT ? OFFSET ?", [$id, 10, $offset]);
$i = 0;
foreach($rows as $row){
if($input->IsEven($i)){ $even = "even"; }else{ $even = "odd"; }
$lastpost = $db->fetchRow("SELECT id,posterid,time FROM forum_posts WHERE threadid = ? ORDER BY time DESC LIMIT 1", [$row['id']]);
$firstpost = $db->fetchRow("SELECT id,posterid,time FROM forum_posts WHERE threadid = ? ORDER BY time ASC LIMIT 1", [$row['id']]);
$replies = $db->fetchColumn("SELECT COUNT(*) FROM forum_posts WHERE threadid = ?", [$row['id']]) - 1;
$newthread = false;
if($lastpost && $lastpost['time'] > $user->user("online")){ $new = true; if($replies == 0){ $newthread = true; } }else{ $new = false; }
$lastposter = $db->fetchRow("SELECT id, name FROM users WHERE id = ?", [$lastpost['posterid']]);
$threadstarter = $db->fetchRow("SELECT id, name FROM users WHERE id = ?", [$row['starterid']]);

$posts = $replies + 1;
$pages = ceil($posts / 10);
$pagelink = "<a href=\"".groupURL($row['groupid'])."/discussions/".$row['id']."/id/page/1\" class=\"topiclist-page-link\">1</a>\n";
if($pages > 4){
	$pageat = $pages - 2;
	$pagelink .= "...\n";
	while($pageat <= $pages){
		$pagelink .= " <a href=\"".groupURL($row['groupid'])."/discussions/".$row['id']."/id/page/".$pageat."\" class=\"topiclist-page-link\">".$pageat."</a>\n";
		$pageat++;
	}
}elseif($pages != 1){
	$pageat = 2;
	while($pageat <= $pages){
		$pagelink .= " <a href=\"".groupURL($row['groupid'])."/discussions/".$row['id']."/id/page/".$pageat."\" class=\"topiclist-page-link\">".$pageat."</a>\n";
		$pageat++;
	}
}

$lastposttoday = false;
$firstposttoday = false;
if($lastpost && $lastpost['time'] > (time() - 60*60*24)){ $lastposttoday = true; }
if($firstpost && $firstpost['time'] > (time() - 60*60*24)){ $firstposttoday = true; }
?>
	<tr class="topiclist-row-<?php echo $even; ?>">
		<td class="topiclist-rowtopic" valign="top">
			<div class="topiclist-row-content">
			<a class="topiclist-link <?php if($row['sticky'] == "1"){ ?>icon icon-sticky<?php }elseif($newthread == true){ ?>icon icon-new<?php } ?>" href="<?php echo groupURL($id); ?>/discussions/<?php echo $row['id']; ?>/id"><?php echo $input->HoloText($row['title']); ?></a>
				<?php if($row['open'] == "0"){ ?><span class="topiclist-row-topicsticky"><img src="<?php echo PATH; ?>/web-gallery/images/groups/status_closed.gif" title="<?php echo $lang->loc['closed']; ?>" alt="<?php echo $lang->loc['closed']; ?>"></span><?php } ?>
			(<?php echo $lang->loc['page']; ?>
                    <?php echo $pagelink; ?>
            )
			<br />
			<span><a class="topiclist-row-openername" href="<?php echo PATH; ?>/home/<?php echo $input->HoloText($threadstarter['name']); ?>"><?php echo $input->HoloText($threadstarter['name']); ?></a></span>
			
				<span class="latestpost<?php if($firstposttoday == true){ ?>-today<?php } ?>"><?php if($firstposttoday == true){ echo $lang->loc['today']; }else{ echo date('M n, Y',$firstpost['time']); } ?></span>
			<span class="latestpost">(<?php echo date('g:i A',$firstpost['time']); ?>)</span>
				<?php if($new == true){ ?><span class="topiclist-row-topicnew"><?php echo $lang->loc['new']; ?> <img src="<?php echo PATH; ?>/web-gallery/images/discussions/New_arrow.gif" alt="<?php echo $lang->loc['new']; ?>"/></span><?php } ?>
			</div>

		</td>
		<td class="topiclist-lastpost" valign="top">
		    <a class="lastpost-page-link" href="<?php echo groupURL($id); ?>/discussions/<?php echo $row['id']; ?>/id/page/<?php echo $pages; ?>">
				<span class="lastpost<?php if($lastposttoday == true){ ?>-today<?php } ?>"><?php if($lastposttoday == true){ echo $lang->loc['today']; }else{ echo date('M n, Y',$lastpost['time']); } ?></span>
            <span class="lastpost">(<?php echo date('g:i A',$lastpost['time']); ?>)</span></a><br />
			<span class="topiclist-row-writtenby"><?php echo $lang->loc['by']; ?>:</span> <a class="topiclist-row-openername" href="<?php echo PATH; ?>/home/<?php echo $input->HoloText($lastposter['name']); ?>"><?php echo $input->HoloText($lastposter['name']); ?></a>

		</td>
 		<td class="topiclist-replies" valign="top"><?php echo $replies; ?></td>
 		<td class="topiclist-views" valign="top"><?php echo $row['views']; ?></td>
	</tr>
<?php
$i++;
}
?>
</table>
<div class="topiclist-footer clearfix">
<?php if($user->id != 0 && ((($grouprow['forum_type'] == 2 && $memberrow['rank'] > 1) || ($grouprow['forum_type'] == 1 && $memberrow['rank'] > 0)) || ($grouprow['forum_type'] == 0))){ ?>
        <a href="#" id="newtopic-lower" class="new-button verify-email newtopic-icon" style="float:left"><b><span></span><?php echo $lang->loc['new.post']; ?></b><i></i></a>
<?php } ?>

    <div class="page-num-list">
    <?php echo $lang->loc['view.page']; ?>:
<?php echo $links; ?>    </div>
</div>
</div>

<script type="text/javascript">
L10N.put("myhabbo.discussion.error.topic_name_empty", "<?php echo addslashes($lang->loc['topic.name.empty']); ?>");
L10N.put("register.error.security_code", "<?php echo addslashes($lang->loc['invalid.capcha']); ?>");
Discussions.initialize("<?php echo $grouprow['id']; ?>", "<?php echo $input->HoloText($grouprow['tag']); ?>", null);
Discussions.captchaPublicKey = "<?php echo time(); ?>";
Discussions.captchaUrl = "<?php echo PATH; ?>/captcha.jpg?t=";
</script>
<?php } ?>
                    </div>
					
                </td>
                <td style="width: 4px;"></td>
                <td valign="top" style="width: 164px;">

<?php } ?>
</div>
<?php require_once('./templates/myhabbo_footer.php'); ?>