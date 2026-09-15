<?php
$lang->addLocale('minimail.loadmessages');
$unread = $mail->unreadCount();
$emptyCopy = match ($label) {
    'sent' => $lang->loc['no.sent.messages'],
    'trash' => $lang->loc['no.deleted.messages'],
    'conversation' => $lang->loc['no.conversation.messages'],
    default => $unreadOnly ? $lang->loc['no.unread.messages'] : $lang->loc['no.messages'],
};
$startnum = $total === 0 ? 0 : $offset + 1;
$endnum = min($offset + PhpretroMinimail::PAGE_SIZE, $total);
$nav = $total === 0 ? '' : htmlspecialchars((string) $startnum, ENT_QUOTES, 'UTF-8').' - '.htmlspecialchars((string) $endnum, ENT_QUOTES, 'UTF-8').' of '.htmlspecialchars((string) $total, ENT_QUOTES, 'UTF-8');
if ($offset > 0) { $nav = ' <a href="#" class="newer">'.$lang->loc['newer'].'</a> '.$nav; }
if ($endnum < $total) { $nav .= ' <a href="#" class="older">'.$lang->loc['older'].'</a>'; }
?>
<a href="#" class="new-button compose"><b><?php echo $lang->loc['compose']; ?></b><i></i></a>
<div class="clearfix labels nostandard">
<ul class="box-tabs">
<li<?php if ($label === 'inbox') { echo ' class="selected"'; } ?>><a href="#" label="inbox"><?php echo $lang->loc['inbox']; ?><?php if ($unread > 0) { echo ' ('.$unread.')'; } ?></a><span class="tab-spacer"></span></li>
<li<?php if ($label === 'sent') { echo ' class="selected"'; } ?>><a href="#" label="sent"><?php echo $lang->loc['sent']; ?></a><span class="tab-spacer"></span></li>
<li<?php if ($label === 'trash') { echo ' class="selected"'; } ?>><a href="#" label="trash"><?php echo $lang->loc['trash']; ?></a><span class="tab-spacer"></span></li>
</ul>
</div>
<div id="message-list" class="label-<?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>">
<div class="new-buttons clearfix">
<div class="labels inbox-refresh"><a href="#" class="new-button green-button" label="inbox" style="float: left; margin: 0"><b><?php echo $lang->loc['refresh']; ?></b><i></i></a></div>
<?php if ($label === 'trash') { ?>
<div class="labels"><a href="#" class="new-button empty-trash" style="float: left; margin: 0 0 0 8px"><b><?php echo $lang->loc['empty.trash']; ?></b><i></i></a></div>
<?php } ?>
</div>
<div style="clear: both; height: 1px"></div>
<div class="navigation">
<?php if ($label === 'inbox') { ?>
<div class="unread-selector"><input type="checkbox" class="unread-only"<?php if ($unreadOnly) { echo ' checked'; } ?>/> <?php echo $lang->loc['only.unread']; ?></div>
<?php } ?>
<?php if ($label === 'conversation') { ?><p><?php echo $lang->loc['reading.conversation']; ?></p><?php } ?>
<?php if ($rows === []) { ?>
<p class="no-messages"><?php echo $emptyCopy; ?></p>
<?php } else { ?>
<p><?php echo $nav; ?></p>
<?php } ?>
<div class="progress"></div>
</div>
<?php foreach ($rows as $row) {
    $status = $row['read_at'] === null ? 'unread' : 'read';
    $stamp = (int) $row['sent_at'];
    $iso = date('Y-m-d\TH:i:s', $stamp);
    $pretty = date('M j, Y g:i:s A', $stamp);
    $figure = $user->avatarURL($row['look'], 's,9,2,sml,1,0');
?>
<div class="message-item <?php echo $status; ?> " id="msg-<?php echo (int) $row['id']; ?>">
<div class="message-preview" status="<?php echo $status; ?>">
<span class="message-tstamp" isotime="<?php echo htmlspecialchars($iso, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($pretty, ENT_QUOTES, 'UTF-8'); ?>">
<?php echo htmlspecialchars($pretty, ENT_QUOTES, 'UTF-8'); ?>
</span>
<img src="<?php echo htmlspecialchars($figure, ENT_QUOTES, 'UTF-8'); ?>" />
<span class="message-sender" title="<?php echo $input->HoloText($row['username']); ?>"><?php echo $input->HoloText($row['username']); ?></span>
<span class="message-subject" title="<?php echo $input->HoloText($row['subject']); ?>">&ldquo;<?php echo $input->HoloText($row['subject']); ?>&rdquo;</span>
</div>
<div class="message-body" style="display: none;">
<div class="contents"></div>
<div class="message-body-bottom"></div>
</div>
</div>
<?php } ?>
<div class="navigation">
<div class="progress"></div>
<?php if ($rows !== []) { ?><p><?php echo $nav; ?></p><?php } ?>
<?php if ($label === 'sent') { ?><p><?php echo $lang->loc['messages.30.days']; ?></p><?php } ?>
</div>
</div>
