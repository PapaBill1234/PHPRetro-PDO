<?php
$page['allow_guests'] = false; require_once('./includes/core.php'); require_once('./includes/session.php');
require_once('./includes/habblet.php');
require_once('./includes/PhpretroMinimail.php');
$database = new Database(); $profile = $database->fetchRow('SELECT username, motto, look, gender, credits, pixels, points, last_login FROM users WHERE id = ?', [$user->id]);
if ($profile === false) { http_response_code(404); exit('Account not found.'); }
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$mail = phpretroMinimail();
$friendCount = (int) $database->fetchColumn('SELECT COUNT(*) FROM messenger_friendships WHERE user_one_id = ?', [$user->id]);
$inboxCount = $mail->folderCount('inbox');
$lang->addLocale('widget.minimail');
$lang->addLocale('minimail.loadmessages');
$page['id'] = 'me'; $page['name'] = 'My profile'; $page['bodyid'] = 'home'; $page['cat'] = 'community'; require_once('./templates/community_header.php');
?>
<div id="container"><div id="content" class="clearfix">
<div id="column1" class="column">
<div class="habblet-container"><div class="cbb clearfix default"><h2 class="title">Welcome, <?php echo $escape($profile['username']); ?></h2><div class="box-content"><p><?php echo $escape($profile['motto']); ?></p><ul><li>Credits: <?php echo (int) $profile['credits']; ?></li><li>Pixels: <?php echo (int) $profile['pixels']; ?></li><li>Points: <?php echo (int) $profile['points']; ?></li><li>Last login: <?php echo (int) $profile['last_login'] > 0 ? date('Y-m-d H:i', (int) $profile['last_login']) : 'Never'; ?></li></ul><p><a href="<?php echo PATH; ?>/profile">Edit profile</a> · <a href="<?php echo PATH; ?>/home/<?php echo rawurlencode((string) $profile['username']); ?>">My home</a></p></div></div></div>
<div class="habblet-container minimail" id="mail">
<div class="cbb clearfix blue">
<h2 class="title"><?php echo $lang->loc['my.messages']; ?></h2>
<div id="minimail">
<div class="minimail-contents">
<?php
$page['bypass'] = true;
$label = 'inbox';
require './habblet/minimail_loadMessages.php';
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
new MiniMail({ pageSize: 10, total: <?php echo $inboxCount; ?>, friendCount: <?php echo $friendCount; ?>, maxRecipients: 50, messageMaxLength: 20, bodyMaxLength: 4096, secondLevel: <?php echo $friendCount === 0 ? 'true' : 'false'; ?>});
</script>
</div></div>
</div>
</div></div>
<?php require_once('./templates/community_footer.php'); ?>
