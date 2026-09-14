<?php
$lang->addLocale('widget.minimail');
$sender = $mail->user((int) $row['sender_id']);
$recipient = $mail->user((int) $row['recipient_id']);
?>
<ul class="message-headers">
<li><a href="#" class="report" title="<?php echo $lang->loc['report']; ?>"></a></li>
<li><b><?php echo $lang->loc['subject']; ?>:</b> <?php echo $input->HoloText($row['subject']); ?></li>
<li><b><?php echo $lang->loc['from']; ?>:</b> <?php echo $input->HoloText($sender['username'] ?? ''); ?></li>
<li><b><?php echo $lang->loc['to']; ?>:</b> <?php echo $input->HoloText($recipient['username'] ?? ''); ?></li>
</ul>
<div class="body-text"><?php echo $input->bbcode_format($input->HoloText($row['body'])); ?><br></div>
<div class="reply-controls">
<div>
<div class="new-buttons clearfix">
<?php if ((int) $row['conversation_id'] > 0) { ?>
<a href="#" class="related-messages" id="rel-<?php echo (int) $row['conversation_id']; ?>" title="<?php echo $lang->loc['show.conversation']; ?>"></a>
<?php } ?>
<?php if ($label === 'trash') { ?>
<a href="#" class="new-button undelete"><b><?php echo $lang->loc['undelete']; ?></b><i></i></a>
<a href="#" class="new-button red-button delete"><b><?php echo $lang->loc['delete']; ?></b><i></i></a>
<?php } elseif ($label === 'inbox') { ?>
<a href="#" class="new-button red-button delete"><b><?php echo $lang->loc['delete']; ?></b><i></i></a>
<a href="#" class="new-button reply"><b><?php echo $lang->loc['reply']; ?></b><i></i></a>
<?php } ?>
</div>
</div>
<div style="display: none;">
<textarea rows="5" cols="10" class="message-text"></textarea><br>
<div class="new-buttons clearfix">
<a href="#" class="new-button cancel-reply"><b><?php echo $lang->loc['cancel']; ?></b><i></i></a>
<a href="#" class="new-button preview"><b><?php echo $lang->loc['preview']; ?></b><i></i></a>
<a href="#" class="new-button send-reply"><b><?php echo $lang->loc['send']; ?></b><i></i></a>
</div>
</div>
</div>
