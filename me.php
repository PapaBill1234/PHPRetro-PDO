<?php
$page['allow_guests'] = false;
require_once('./includes/core.php');
require_once('./includes/session.php');
require_once('./includes/habblet.php');
require_once('./includes/PhpretroMinimail.php');

$database = new Database();
$lang->addLocale('home.me');

$page['id'] = 'me';
$page['name'] = $lang->loc['pagename.me'];
$page['bodyid'] = 'home';
$page['cat'] = 'home';

$mail = phpretroMinimail();
$userId = (int) $user->id;
$friendCount = (int) $database->fetchColumn('SELECT COUNT(*) FROM messenger_friendships WHERE user_one_id = ? OR user_two_id = ?', [$userId, $userId]);
$inboxCount = $mail->folderCount('inbox');
$lastLogin = (int) $database->fetchColumn('SELECT last_login FROM users WHERE id = ?', [$userId]);
$myTags = habbletUserTags($database, $userId);
$tagCount = count($myTags);
$hotelView = $settings->find('site_hotel_image');
if ($hotelView === '' || $hotelView === false) {
	$hotelView = 'htlview_gb.png';
}

require_once('./templates/community_header.php');
?>

<div id="container">
	<div id="content">
    <div id="column1" class="column">
				<div class="habblet-container ">

						<div id="new-personal-info" style="background-image:url(<?php echo PATH; ?>/web-gallery/v2/images/personal_info/hotel_views/<?php echo htmlspecialchars((string) $hotelView, ENT_QUOTES, 'UTF-8'); ?>)">
	<div class="enter-hotel-btn">
<?php if(HotelStatus() == "online"){ ?>
		<div class="open enter-btn">
				<a href="<?php echo PATH; ?>/client" target="client" onclick="openOrFocusHabbo(this); return false;"><?php echo $lang->loc['enter.short']; ?><i></i></a>
			<b></b>
		</div>
<?php } else { ?>
<div class="closed enter-btn">
	<span><?php echo $lang->loc['closed.short']; ?></span>
	<b></b>
</div>
<?php } ?>
	</div>

	<div id="habbo-plate">
		<a href="<?php echo PATH; ?>/profile">
			<img alt="<?php echo $input->HoloText($user->name); ?>" src="<?php echo $user->avatarURL("self","b,3,3,sml,1,0"); ?>" width="64" height="110" />
		</a>
	</div>

	<div id="habbo-info">
		<div id="motto-container" class="clearfix">
			<strong><?php echo $input->HoloText($user->name); ?>:</strong>
			<div>
				<span title="<?php echo $lang->loc['change.motto']; ?>"><?php if($user->user("mission") != ""){ echo $input->unicodeToImage($input->HoloText($user->user("mission"))); } else { echo $lang->loc['change.motto']; } ?></span>
				<p style="display: none"><input type="text" length="30" name="motto" value="<?php echo $input->HoloText($user->user("mission")); ?>"/></p>
			</div>
		</div>
		<div id="motto-links" style="display: none"><a href="#" id="motto-cancel"><?php echo $lang->loc['cancel']; ?></a></div>
	</div>

	<ul id="link-bar" class="clearfix">
		<li class="change-looks"><a href="<?php echo PATH; ?>/profile"><?php echo $lang->loc['change.looks']; ?> &raquo;</a></li>
		<li class="credits">
			<a href="<?php echo PATH; ?>/credits"><?php echo (int) $user->user("credits"); ?></a> <?php echo $lang->loc['credits']; ?>
		</li>
		<li class="club">
                	<a href="<?php echo PATH; ?>/club"><?php if( !$user->IsHCMember("self") ){ echo $lang->loc['join.club']." &raquo;</a>"; } else { echo $user->HCDaysLeft("self") . " </a>".$lang->loc['hc.days']; }?>
		</li>
		    <li class="activitypoints">
			    <a href="<?php echo PATH; ?>/credits/pixels"><?php echo (int) $user->user("pixels"); ?></a> <?php echo $lang->loc['pixels']; ?>
		    </li>
	</ul>

    <div id="habbo-feed">
        <ul id="feed-items">

<?php
if(!$user->IsHCMember("self")){ ?>
<li id="feed-item-hc-reminder">
    <a href="#" class="remove-feed-item" id="remove-hc-reminder" title="<?php echo $lang->loc['remove.hc.notice']; ?>"><?php echo $lang->loc['remove.hc.notice']; ?></a>

	<div>
			<?php echo $lang->loc['hc.subscribe.question']; ?>
	</div>
	<div id="hc-reminder-buttons" class="clearfix">
		<a href="#" class="new-button" id="hc-reminder-1" title="31 <?php echo $lang->loc['days']; ?>, 20 <?php echo $lang->loc['credits']; ?>"><b>1 <?php echo $lang->loc['months']; ?></b><i></i></a>
		<a href="#" class="new-button" id="hc-reminder-2" title="93 <?php echo $lang->loc['days']; ?>, 50 <?php echo $lang->loc['credits']; ?>"><b>3 <?php echo $lang->loc['months']; ?></b><i></i></a>
		<a href="#" class="new-button" id="hc-reminder-3" title="186 <?php echo $lang->loc['days']; ?>, 80 <?php echo $lang->loc['credits']; ?>"><b>6 <?php echo $lang->loc['months']; ?></b><i></i></a>
	</div>

</li>
<script type="text/javascript">
L10N.put("subscription.title", "<?php echo addslashes($lang->loc['habbo.club']); ?>");
</script>
<?php
}

if((int) $user->user("rank") > 4){
    $helpCount = (int) $database->fetchColumn("SELECT COUNT(*) FROM phpretro_helpdesk_tickets WHERE status = 'open'");
    if($helpCount > 0){
            echo "            <li class=\"small\" id=\"feed-group-discussion\">
                <strong>".$lang->loc['staff.messages']."</strong><br />".$lang->loc['there.are']." <strong><a href=\"".PATH."/housekeeping/help\" target=\"_self\">".$helpCount."</a></strong> ".$lang->loc['help.quries']."
            </li>";
    }
}

$dob = (string) $user->user("birth");
$bits = explode("-", $dob);
if(count($bits) >= 2 && $bits[0] !== '' && ctype_digit((string) $bits[0]) && ctype_digit((string) $bits[1]) && (int) $bits[0] === (int) date('j') && (int) $bits[1] === (int) date('n')){
?>
			<li id="feed-birthday">
			    <div>
			            <?php echo $lang->loc['happy.birthday'].", ".$input->HoloText($user->name)."!"; ?>
			    </div>
			</li>
<?php
}

$friendRequests = (int) $database->fetchColumn('SELECT COUNT(*) FROM messenger_friendrequests WHERE user_to_id = ?', [$userId]);
if($friendRequests > 0){ ?>
			<li id="feed-notification">
				<?php echo $lang->loc['you.have']; ?> <a href="<?php echo PATH; ?>/client" onclick="HabboClient.openOrFocus(this); return false;"><?php echo $friendRequests; ?> <?php echo $lang->loc['friend.requests']; ?></a> <?php echo $lang->loc['waiting']; ?>
			</li>
<?php }

$cutoff = time() - 1801;
$onlineFriends = $database->fetchAll(
	'SELECT DISTINCT u.username FROM users u JOIN messenger_friendships f ON (f.user_one_id = u.id OR f.user_two_id = u.id) WHERE (f.user_one_id = ? OR f.user_two_id = ?) AND u.id != ? AND u.last_online > ? ORDER BY u.username',
	[$userId, $userId, $userId, $cutoff]
);
$onlineCount = count($onlineFriends);
if($onlineCount > 0){
?>
			<li id="feed-friends">
				<?php echo $lang->loc['you.have']; ?> <strong><?php echo $onlineCount; ?></strong> <?php echo $lang->loc['friends.online']; ?>
				<span>
			<?php
				$i = 0;
				foreach($onlineFriends as $friend){
					$i++;
					echo $input->HoloText($friend['username']);
					if($i < $onlineCount){ echo ", "; }
					echo "\n";
				} ?>
				</span>
			</li>
<?php }

$groupUpdates = $database->fetchAll(
	'SELECT g.id, g.name FROM guilds_members m JOIN guilds g ON g.id = m.guild_id JOIN guilds_forums_threads t ON t.guild_id = g.id WHERE m.user_id = ? AND t.updated_at > ? AND t.state IN (0, 1) GROUP BY g.id, g.name ORDER BY MAX(t.updated_at) DESC',
	[$userId, $lastLogin]
);
if($groupUpdates){
$groups = '';
foreach($groupUpdates as $groupRow){
	$groups .= "\n<a href=\"".groupURL((int) $groupRow['id'])."\">".$input->HoloText($groupRow['name'])."</a>, ";
}
$groups = substr($groups, 0, -2);
?>
            <li class="small" id="feed-group-discussion">
            	<strong><?php echo count($groupUpdates); ?></strong> <?php echo $lang->loc['groups.new.messages']; ?>:
            	<span><?php echo $groups; ?>
            	</span>
            </li>
<?php } ?>

            <li class="small" id="feed-lastlogin">
                <?php echo $lang->loc['last.online']; ?>:
                <?php echo $lastLogin > 0 ? date('M j, Y g:i:s A', $lastLogin) : 'Never'; ?>
            </li>


        </ul>
    </div>
    <p class="last"></p>
</div>

<script type="text/javascript">
    HabboView.add(function() {
        L10N.put("personal_info.motto_editor.spamming", "<?php echo addslashes($lang->loc['no.spam']); ?>");
        PersonalInfo.init("");
    });
</script>


                </div>
                <script type="text/javascript">if (!$(document.body).hasClassName('process-template')) { Rounder.init(); }</script>

<?php $lang->addLocale("widget.campaigns"); ?>
				<div class="habblet-container ">
						<div class="cbb clearfix orange ">


							<h2 class="title"><?php echo $lang->loc['hot.campaigns']; ?>
							</h2>
						<div id="hotcampaigns-habblet-list-container">
    <ul id="hotcampaigns-habblet-list">
<?php
$i = 0;
$campaigns = $database->fetchAll("SELECT name, `desc`, image, url FROM phpretro_campaigns WHERE visible = '1' ORDER BY sort_order ASC, id DESC");
foreach($campaigns as $row){
if($input->IsEven($i)){ $even = "even"; }else{ $even = "odd"; }
?>

        <li class="<?php echo $even; ?>">
            <div class="hotcampaign-container">
                <a href="<?php echo str_replace("%path%",PATH,$row['url']); ?>"><img src="<?php echo str_replace("%path%",PATH,$row['image']); ?>" align="left" alt="" /></a>
                <h3><?php echo $input->HoloText($row['name'],true); ?></h3>
                <p><?php echo $input->HoloText($row['desc'],true); ?></p>

                <p class="link"><a href="<?php echo str_replace("%path%",PATH,$row['url']); ?>"><?php echo $lang->loc['go.there']; ?> &raquo;</a></p>
            </div>
        </li>

<?php $i++; } ?>
    </ul>
</div>


					</div>
				</div>
				<script type="text/javascript">if (!$(document.body).hasClassName('process-template')) { Rounder.init(); }</script>

<?php $lang->addLocale("widget.minimail"); $lang->addLocale("minimail.loadmessages"); ?>
<div class="habblet-container minimail" id="mail">
                        <div class="cbb clearfix blue ">

                            <h2 class="title"><?php echo $lang->loc['my.messages']; ?>
                            </h2>
                        <div id="minimail">
    <div class="minimail-contents">
		<?php
		$page['bypass'] = true;
		$label = "inbox";
		require('./habblet/minimail_loadMessages.php');
		?>
	    </div>
		<div id="message-compose-wait"></div>
	    <form style="display: none" id="message-compose">
	        <div><?php echo $lang->loc['to']; ?></div>
	        <div id="message-recipients-container" class="input-text" style="width: 426px; margin-bottom: 1em">
	        	<input type="text" value="" id="message-recipients" />
	        	<div class="autocomplete" id="message-recipients-auto">
	        		<div class="default" style="display: none;"><?php echo $lang->loc['type.name']; ?></div>
	        		<ul class="feed" style="display: none;"></ul>

	        	</div>
	        </div>
	        <div><?php echo $lang->loc['subject']; ?><br/>
	        <input type="text" style="margin: 5px 0" id="message-subject" class="message-text" maxlength="100" tabindex="2" />
	        </div>
	        <div><?php echo $lang->loc['message']; ?><br/>
	        <textarea style="margin: 5px 0" rows="5" cols="10" id="message-body" class="message-text" tabindex="3"></textarea>

	        </div>
	        <div class="new-buttons clearfix">
	            <a href="#" class="new-button preview"><b><?php echo $lang->loc['preview']; ?></b><i></i></a>
	            <a href="#" class="new-button send"><b><?php echo $lang->loc['send']; ?></b><i></i></a>
	        </div>
	    </form>
	</div>
		<script type="text/javascript">
		L10N.put("minimail.compose", "<?php echo addslashes($lang->loc['compose']); ?>").put("minimail.cancel", "<?php echo addslashes($lang->loc['cancel']); ?>")
			.put("bbcode.colors.red", "<?php echo addslashes($lang->loc['red']); ?>").put("bbcode.colors.orange", "<?php echo addslashes($lang->loc['orange']); ?>")
	    	.put("bbcode.colors.yellow", "<?php echo addslashes($lang->loc['yellow']); ?>").put("bbcode.colors.green", "<?php echo addslashes($lang->loc['green']); ?>")
	    	.put("bbcode.colors.cyan", "<?php echo addslashes($lang->loc['cyan']); ?>").put("bbcode.colors.blue", "<?php echo addslashes($lang->loc['blue']); ?>")
	    	.put("bbcode.colors.gray", "<?php echo addslashes($lang->loc['gray']); ?>").put("bbcode.colors.black", "<?php echo addslashes($lang->loc['black']); ?>")
	    	.put("minimail.empty_body.confirm", "<?php echo addslashes($lang->loc['empty.message']); ?>")
	    	.put("bbcode.colors.label", "<?php echo addslashes($lang->loc['color']); ?>").put("linktool.find.label", " ")
	    	.put("linktool.scope.habbos", "<?php echo addslashes($lang->loc['habbos']); ?>").put("linktool.scope.rooms", "<?php echo addslashes($lang->loc['rooms']); ?>")
	    	.put("linktool.scope.groups", "<?php echo addslashes($lang->loc['groups']); ?>").put("minimail.report.title", "<?php echo addslashes($lang->loc['report']); ?>");

	    L10N.put("date.pretty.just_now", "<?php echo addslashes($lang->loc['just.now']); ?>");
	    L10N.put("date.pretty.one_minute_ago", "1 <?php echo addslashes($lang->loc['minute'].' '.$lang->loc['ago']); ?>");
	    L10N.put("date.pretty.minutes_ago", "{0} <?php echo addslashes($lang->loc['minutes'].' '.$lang->loc['ago']); ?>");
	    L10N.put("date.pretty.one_hour_ago", "1 <?php echo addslashes($lang->loc['hour'].' '.$lang->loc['ago']); ?>");
	    L10N.put("date.pretty.hours_ago", "{0} <?php echo addslashes($lang->loc['hours'].' '.$lang->loc['ago']); ?>");
	    L10N.put("date.pretty.yesterday", "<?php echo addslashes($lang->loc['yesterday']); ?>");
	    L10N.put("date.pretty.days_ago", "{0} <?php echo addslashes($lang->loc['days'].' '.$lang->loc['ago']); ?>");
	    L10N.put("date.pretty.one_week_ago", "1 <?php echo addslashes($lang->loc['week'].' '.$lang->loc['ago']); ?>");
	    L10N.put("date.pretty.weeks_ago", "{0} <?php echo addslashes($lang->loc['weeks'].' '.$lang->loc['ago']); ?>");
		new MiniMail({ pageSize: 10,
		   total: <?php echo (int) $inboxCount; ?>,
		   friendCount: <?php echo (int) $friendCount; ?>,
		   maxRecipients: 50,
		   messageMaxLength: 20,
		   bodyMaxLength: 4096,
		   secondLevel: <?php echo $friendCount === 0 ? 'true' : 'false'; ?>});
	</script>
	</div></div>
    <script type="text/javascript">if (!$(document.body).hasClassName('process-template')) { Rounder.init(); }</script>

<?php $lang->addLocale("widget.searchhabbos"); ?>
				<div class="habblet-container ">
						<div class="cbb clearfix default ">
<div class="box-tabs-container clearfix">
    <h2><?php echo $lang->loc['habbos']; ?></h2>
    <ul class="box-tabs">
        <li id="tab-0-4-1"><a href="#"><?php echo $lang->loc['search.habbos']; ?></a><span class="tab-spacer"></span></li>

        <li id="tab-0-4-2" class="selected"><a href="#"><?php echo $lang->loc['invite.friends']; ?></a><span class="tab-spacer"></span></li>
    </ul>
</div>
    <div id="tab-0-4-1-content"  style="display: none">
<div class="habblet-content-info">
    <a name="habbo-search"><?php echo $lang->loc['type.in.name']; ?></a>
</div>
<div id="habbo-search-error-container" style="display: none;"><div id="habbo-search-error" class="rounded rounded-red"></div></div>
<br clear="all"/>
<div id="avatar-habblet-list-search">
    <input type="text" id="avatar-habblet-search-string"/>

    <a href="#" id="avatar-habblet-search-button" class="new-button"><b><?php echo $lang->loc['search']; ?></b><i></i></a>
</div>

<br clear="all"/>

<div id="avatar-habblet-content">
<div id="avatar-habblet-list-container" class="habblet-list-container">
        <ul class="habblet-list">
        </ul>

</div>
<script type="text/javascript">
    L10N.put("habblet.search.error.search_string_too_long", "<?php echo addslashes($lang->loc['search.too.long']); ?>");
    L10N.put("habblet.search.error.search_string_too_short", "<?php echo addslashes($lang->loc['search.too.short']); ?>");
    L10N.put("habblet.search.add_friend.title", "<?php echo addslashes($lang->loc['add.to.friends']); ?>");
	new HabboSearchHabblet(2, 30);

</script>

</div>

<script type="text/javascript">
    Rounder.addCorners($("habbo-search-error"), 8, 8);
</script>    </div>
    <div id="tab-0-4-2-content" >
<div id="friend-invitation-habblet-container" class="box-content">
    <div style="display: none">
    <div id="invitation-form" class="clearfix">
        <textarea name="invitation_message" id="invitation_message" class="invitation-message"><?php echo $lang->loc['come.hang.out']; ?>
- <?php echo $input->HoloText($user->name); ?></textarea>
        <div id="invitation-email">
            <div class="invitation-input">1.<input  onkeypress="$('invitation_recipient2').enable()" type="text" name="invitation_recipients" id="invitation_recipient1" value="<?php echo $lang->loc['friends.email']; ?>" class="invitation-input" />

            </div>
            <div class="invitation-input">2.<input disabled onkeypress="$('invitation_recipient3').enable()" type="text" name="invitation_recipients" id="invitation_recipient2" value="<?php echo $lang->loc['friends.email']; ?>" class="invitation-input" />
            </div>
            <div class="invitation-input">3.<input disabled  type="text" name="invitation_recipients" id="invitation_recipient3" value="<?php echo $lang->loc['friends.email']; ?>" class="invitation-input" />
            </div>
        </div>
        <div class="clear"></div>
        <div class="fielderror" id="invitation_message_error" style="display: none;"><div class="rounded"></div></div>

    </div>

    <div class="invitation-buttons clearfix" id="invitation_buttons">
		<a  class="new-button" id="send-friend-invite-button" href="#"><b><?php echo $lang->loc['invite.friends']; ?></b><i></i></a>
    </div>

    <hr/>
    </div>
    <div id="invitation-link-container">
        <h3><?php echo $lang->loc['enjoy.more']; ?></h3>

        <div class="copytext">
            <p><?php echo $lang->loc['invite.desc']; ?>
<?php if($settings->find("register_referral_rewards") != "0"){ ?><?php echo " ".$lang->loc['reward.text']; ?> <?php echo $settings->find("register_referral_rewards"); ?> <?php echo $lang->loc['credits']; ?>.<?php } ?></p>
        </div>
        <div class="invitation-buttons clearfix">
            <a  class="new-button" id="getlink-friend-invite-button" href="#"><b><?php echo $lang->loc['invite.button']; ?></b><i></i></a>
        </div>
    </div>
</div>
<script type="text/javascript">
    L10N.put("invitation.button.invite", "<?php echo addslashes($lang->loc['invite.friends']); ?>");
    L10N.put("invitation.form.recipient", "<?php echo addslashes($lang->loc['friends.email']); ?>");
    L10N.put("invitation.error.message_too_long", "invitation.error.message_limit");
    inviteFriendHabblet = new InviteFriendHabblet(500);
    $("friend-invitation-habblet-container").select(".fielderror .rounded").each(function(el) {
        Rounder.addCorners(el, 8, 8);
    });

</script>    </div>

					</div>
				</div>
				<script type="text/javascript">if (!$(document.body).hasClassName('process-template')) { Rounder.init(); }</script>

<?php $lang->addLocale("widget.events"); ?>
<?php $categories = explode("|", $lang->loc['events.categories']); ?>
<div class="habblet-container ">
						<div class="cbb clearfix darkred ">

							<h2 class="title"><?php echo $lang->loc['events']; ?>
							</h2>
						<div id="current-events">
	<div class="category-selector">
	<p><?php echo $lang->loc['browse.events']; ?></p>
	<select id="event-category">
		<option value="1"><?php echo htmlspecialchars($categories[0] ?? '', ENT_QUOTES, 'UTF-8'); ?></option>
		<option value="2"><?php echo htmlspecialchars($categories[1] ?? '', ENT_QUOTES, 'UTF-8'); ?></option>
		<option value="3"><?php echo htmlspecialchars($categories[2] ?? '', ENT_QUOTES, 'UTF-8'); ?></option>
		<option value="4"><?php echo htmlspecialchars($categories[3] ?? '', ENT_QUOTES, 'UTF-8'); ?></option>
		<option value="5"><?php echo htmlspecialchars($categories[4] ?? '', ENT_QUOTES, 'UTF-8'); ?></option>
		<option value="6"><?php echo htmlspecialchars($categories[5] ?? '', ENT_QUOTES, 'UTF-8'); ?></option>
		<option value="7"><?php echo htmlspecialchars($categories[6] ?? '', ENT_QUOTES, 'UTF-8'); ?></option>
		<option value="8"><?php echo htmlspecialchars($categories[7] ?? '', ENT_QUOTES, 'UTF-8'); ?></option>
		<option value="9"><?php echo htmlspecialchars($categories[8] ?? '', ENT_QUOTES, 'UTF-8'); ?></option>
		<option value="10"><?php echo htmlspecialchars($categories[9] ?? '', ENT_QUOTES, 'UTF-8'); ?></option>
		<option value="11"><?php echo htmlspecialchars($categories[10] ?? '', ENT_QUOTES, 'UTF-8'); ?></option>
	</select>
	</div>
	<div id="event-list">

<?php $page['bypass'] = true; require_once('./habblet/ajax_load_events.php'); ?>

	</div>
</div>
<script type="text/javascript">
	document.observe('dom:loaded', function() {
		CurrentRoomEvents.init();
	});
</script>


					</div>
				</div>
				<script type="text/javascript">if (!$(document.body).hasClassName('process-template')) { Rounder.init(); }</script>

</div>
				<script type='text/javascript'>if (!$(document.body).hasClassName('process-template')) { Rounder.init(); }</script>
<div id="column2" class="column">
<?php
$newsRows = $database->fetchAll("SELECT id, title, summary, images, time FROM phpretro_news ORDER BY time DESC, id DESC LIMIT 5");
$news = [];
foreach ($newsRows as $row) {
	$images = array_values(array_filter(array_map('trim', explode(',', (string) $row['images']))));
	$news[] = [
		'id' => (int) $row['id'],
		'title' => $input->HoloText($row['title'], true),
		'title_safe' => $input->stringToURL($input->HoloText($row['title'], true), true, true),
		'summary' => nl2br($input->HoloText($row['summary'], true)),
		'header_image' => $images[0] ?? '',
		'date' => date('M j, Y', (int) $row['time']),
	];
}
while (count($news) < 5) {
	$news[] = ['id' => 0, 'title' => '', 'title_safe' => '', 'summary' => '', 'header_image' => '', 'date' => ''];
}
$lang->addLocale("widget.news"); ?>
				<div class="habblet-container news-promo">
						<div class="cbb clearfix notitle ">

						<div id="newspromo">
        <div id="topstories">
	        <div class="topstory" style="background-image: url(<?php echo $news[0]['header_image']; ?>)">
	            <h4><?php echo $lang->loc['latest.news']; ?></a></h4>
	            <h3><a href="<?php echo PATH."/articles?id=".$news[0]['id']; ?>"><?php echo $news[0]['title']; ?></a></h3>
	            <p class="summary">
	            <?php echo $news[0]['summary']; ?>
	            </p>
	            <p>
	                <a href="<?php echo PATH."/articles?id=".$news[0]['id']; ?>"><?php echo $lang->loc['read.more']; ?></a>
	            </p>
	        </div>
	        <div class="topstory" style="background-image: url(<?php echo $news[1]['header_image']; ?>); display: none">
	            <h4><?php echo $lang->loc['latest.news']; ?></a></h4>
	            <h3><a href="<?php echo PATH."/articles?id=".$news[1]['id']; ?>"><?php echo $news[1]['title']; ?></a></h3>
	            <p class="summary">
	            <?php echo $news[1]['summary']; ?>
	            </p>
	            <p>
	                <a href="<?php echo PATH."/articles?id=".$news[1]['id']; ?>"><?php echo $lang->loc['read.more']; ?></a>
	            </p>
	        </div>
	        <div class="topstory" style="background-image: url(<?php echo $news[2]['header_image']; ?>); display: none">
	            <h4><?php echo $lang->loc['latest.news']; ?></a></h4>
	            <h3><a href="<?php echo PATH."/articles?id=".$news[2]['id']; ?>"><?php echo $news[2]['title']; ?></a></h3>
	            <p class="summary">
	            <?php echo $news[2]['summary']; ?>
	            </p>
	            <p>
	                <a href="<?php echo PATH."/articles?id=".$news[2]['id']; ?>"><?php echo $lang->loc['read.more']; ?></a>
	            </p>
	        </div>
            <div id="topstories-nav" style="display: none"><a href="#" class="prev"><?php echo $lang->loc['news.previous']; ?></a><span>1</span> / 3<a href="#" class="next"><?php echo $lang->loc['news.next']; ?></a></div>
        </div>
        <ul class="widelist">
            <li class="even">
                <a href="<?php echo PATH."/articles?id=".$news[3]['id']; ?>"><?php echo $news[3]['title']; ?></a><div class="newsitem-date"><?php echo $news[3]['date']; ?></div>
            </li>
            <li class="odd">
                <a href="<?php echo PATH."/articles?id=".$news[4]['id']; ?>"><?php echo $news[4]['title']; ?></a><div class="newsitem-date"><?php echo $news[4]['date']; ?></div>
            </li>
            <li class="last"><a href="<?php echo PATH; ?>/articles"><?php echo $lang->loc['news.more']; ?></a></li>
        </ul>
</div>
<script type="text/javascript">
	document.observe("dom:loaded", function() { NewsPromo.init(); });
</script>
					</div>

				</div>
				<script type="text/javascript">if (!$(document.body).hasClassName('process-template')) { Rounder.init(); }</script>

<?php $lang->addLocale("widget.staffpicks"); ?>
				<div class="habblet-container ">
						<div class="cbb clearfix red ">
<div class="box-tabs-container clearfix">
    <h2><?php echo $lang->loc['staff.picks']; ?></h2>
    <ul class="box-tabs">
        <li id="tab-1-3-1"><a href="#"><?php echo $lang->loc['rooms']; ?></a><span class="tab-spacer"></span></li>
        <li id="tab-1-3-2" class="selected"><a href="#"><?php echo $lang->loc['groups']; ?></a><span class="tab-spacer"></span></li>
    </ul>

</div>
    <div id="tab-1-3-1-content"  style="display: none">
    		<div class="progressbar"><img src="<?php echo PATH; ?>/web-gallery/images/progress_bubbles.gif" alt="" width="29" height="6" /></div>
    		<a href="<?php echo PATH; ?>/habblet/proxy?hid=h21" class="tab-ajax"></a>
    </div>
    <div id="tab-1-3-2-content" >
<div id="staffpicks-groups-habblet-list-container" class="habblet-list-container groups-list">
    <ul class="habblet-list two-cols clearfix">
<?php
$staffGroups = $database->fetchAll("SELECT g.id, g.name, g.badge FROM phpretro_recommended r JOIN guilds g ON g.id = r.rec_id WHERE r.type = 'group' AND r.sponsered = '0' ORDER BY r.id ASC");
$i = 0;
foreach($staffGroups as $row){
	if($input->IsEven($i)){
		$even = "even left";
	} else {
		$even = "even right";
	}
?>

        <li class="<?php echo $even; ?>" style="background-image: url(<?php echo PATH; ?>/habbo-imaging/badge/<?php echo rawurlencode((string) $row['badge']); ?>.gif)">
            <a class="item" href="<?php echo groupURL((int) $row['id']); ?>"><?php echo $input->HoloText($row['name']); ?></a>
        </li>
<?php $i++; } ?>
    </ul>

</div>
    </div>

					</div>
				</div>
				<script type="text/javascript">if (!$(document.body).hasClassName('process-template')) { Rounder.init(); }</script>

<?php $lang->addLocale("widget.recommended"); ?>
<div class="habblet-container ">
                        <div class="cbb clearfix blue ">

                            <h2 class="title"><?php echo $lang->loc['recommended']; ?>
                            </h2>
                        <div id="promogroups-habblet-list-container" class="habblet-list-container groups-list">
    <ul class="habblet-list two-cols clearfix">
    <?php
	$recommended = $database->fetchAll("SELECT g.id, g.name, g.badge, g.room_id FROM phpretro_recommended r JOIN guilds g ON g.id = r.rec_id WHERE r.type = 'group' AND r.sponsered = '1' ORDER BY r.id ASC");
	$i = 0;
    foreach($recommended as $row) {
        if($input->IsEven($i)){
            $even = "even left";
        } else {
            $even = "even right";
        }
    ?>
            <li class="<?php echo $even; ?>" style="background-image: url(<?php echo PATH; ?>/habbo-imaging/badge/<?php echo rawurlencode((string) $row['badge']); ?>.gif)">
        <?php if((int) $row['room_id'] !== 0) { ?><a href="<?php echo PATH; ?>/client?forwardId=2&roomId=<?php echo (int) $row['room_id']; ?>" onclick="HabboClient.roomForward(this, '<?php echo (int) $row['room_id']; ?>', 'private'); return false;" target="client" class="group-room"></a><?php } ?>
            <a class="item" href="<?php echo groupURL((int) $row['id']); ?>"><?php echo $input->HoloText($row['name']); ?></a>
            </li>
            <?php $i++; } ?>
    </ul>
</div>


                    </div>
                </div>
                <script type="text/javascript">if (!$(document.body).hasClassName('process-template')) { Rounder.init(); }</script>

<?php $lang->addLocale("widget.tags"); ?>
				<div class="habblet-container ">
						<div class="cbb clearfix green ">
<div class="box-tabs-container clearfix">
    <h2><?php echo $lang->loc['tags']; ?></h2>
    <ul class="box-tabs">
        <li id="tab-1-5-1"><a href="#"><?php echo $lang->loc['habbos.like']; ?>...</a><span class="tab-spacer"></span></li>

        <li id="tab-1-5-2" class="selected"><a href="#"><?php echo $lang->loc['my.tags']; ?></a><span class="tab-spacer"></span></li>
    </ul>
</div>
    <div id="tab-1-5-1-content"  style="display: none">
    		<div class="progressbar"><img src="<?php echo PATH; ?>/web-gallery/images/progress_bubbles.gif" alt="" width="29" height="6" /></div>
    		<a href="<?php echo PATH; ?>/habblet/proxy?hid=h24" class="tab-ajax"></a>
    </div>
    <div id="tab-1-5-2-content" >
		<div id="my-tag-info" class="habblet-content-info">
		<?php if($tagCount > 19){ echo $lang->loc['tag.limit']; } elseif($tagCount == 0){ echo $lang->loc['tag.none']; }else{ echo $lang->loc['tag.keep.going']; } ?>
		    </div>
<div class="box-content">

<?php
$page['bypass'] = true;
require_once('./habblet/mytagslist.php');
?>

<script type="text/javascript">
document.observe("dom:loaded", function() {
    TagHelper.setTexts({
        tagLimitText: "<?php echo addslashes($lang->loc['tag.limit']); ?>",
        invalidTagText: "<?php echo addslashes($lang->loc['tag.invalid']); ?>",
        buttonText: "<?php echo addslashes($lang->loc['ok']); ?>"
    });
        TagHelper.init('<?php echo (int) $user->id; ?>');
});
</script>
    </div>

					</div>
				</div>
				<script type="text/javascript">if (!$(document.body).hasClassName('process-template')) { Rounder.init(); }</script>

<?php $lang->addLocale("widget.groups"); ?>
<div class="habblet-container ">
                        <div class="cbb clearfix blue ">
<div class="box-tabs-container clearfix">
    <h2><?php echo $lang->loc['groups']; ?></h2>
    <ul class="box-tabs">
        <li id="tab-2-1"><a href="#"><?php echo $lang->loc['hot.groups']; ?></a><span class="tab-spacer"></span></li>
        <li id="tab-2-2" class="selected"><a href="#"><?php echo $lang->loc['my.groups']; ?></a><span class="tab-spacer"></span></li>
    </ul>
</div>
    <div id="tab-2-1-content"  style="display: none">
    		<div class="progressbar"><img src="<?php echo PATH; ?>/web-gallery/images/progress_bubbles.gif" alt="" width="29" height="6" /></div>
    		<a href="<?php echo PATH; ?>/habblet/proxy?hid=groups" class="tab-ajax"></a>
    </div>
    <div id="tab-2-2-content" >


         <div id="groups-habblet-info" class="habblet-content-info">
                <?php echo $lang->loc['groups.info']; ?>
         </div>

    <div id="groups-habblet-list-container" class="habblet-list-container groups-list">

<?php
$myGroups = $database->fetchAll('SELECT g.id, g.name, g.badge FROM guilds_members m JOIN guilds g ON g.id = m.guild_id WHERE m.user_id = ? ORDER BY g.id ASC', [$userId]);

echo "\n    <ul class=\"habblet-list two-cols clearfix\">";

$i = 0; $rights = 0; $lefts = 0;

foreach($myGroups as $row){

	if($input->IsEven($i)){
		$pos = "right";
		$rights++;
	} else {
		$pos = "left";
		$lefts++;
	}

	if($input->IsEven($lefts)){
		$oddeven = "odd";
	} else {
		$oddeven = "even";
	}

	echo "            <li class=\"".$oddeven." ".$pos."\" style=\"background-image: url(".PATH."/habbo-imaging/badge/".rawurlencode((string) $row['badge']).".gif)\">\n            	\n                \n                <a class=\"item\" href=\"".groupURL((int) $row['id'])."\">".$input->HoloText($row['name'])."</a>\n            </li>";
	$i++;
}

$rights_should_be = $lefts;
if($rights !== $rights_should_be){
	echo "<li class=\"".$oddeven." right\"><div class=\"item\">&nbsp;</div></li>";
}

echo "\n    </ul>";
?>

		<div class="habblet-button-row clearfix"><a class="new-button" id="purchase-group-button" href="#"><b><?php echo $lang->loc['buy.group']; ?></b><i></i></a></div>
    </div>

    <div id="groups-habblet-group-purchase-button" class="habblet-list-container"></div>

<script type="text/javascript">
    $("purchase-group-button").observe("click", function(e) { Event.stop(e); GroupPurchase.open(); });
</script>





    </div>

					</div>
				</div>
				<script type="text/javascript">if (!$(document.body).hasClassName('process-template')) { Rounder.init(); }</script>

</div>

<script type="text/javascript">
	HabboView.add(LoginFormUI.init);
</script>
<?php
require('./templates/community_footer.php');
?>
