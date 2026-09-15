<form action="#" method="post" id="group-settings-form">

  <div id="group-settings">
    <p>Custom URLs and room moves are unavailable here. Use the game client for room changes.</p>
    <div id="group-settings-data" class="group-settings-pane">
      <div id="group-logo">
        <img src="<?php echo PATH; ?>/web-gallery/images/groups/group_icon.gif" />
      </div>
      <div id="group-identity-area">
        <div id="group-name-area">
          <div id="group_name_message_error" class="error"></div>
          <label for="group_name" id="group_name_text"><?php echo $lang->loc['edit.group.name']; ?>:</label>
          <input type="text" name="group_name" id="group_name" onKeyUp="GroupUtils.validateGroupElements('group_name', 30, '<?php echo addslashes($lang->loc['group.name.limit.reached']); ?>');" value="<?php echo $input->HoloText($group['name']); ?>"/><br />
        </div>

        <div id="group-url-area">
          <div id="group_url_message_error" class="error"></div>
            <label for="group_url" id="group_url_text"><?php echo $lang->loc['edit.group.url']; ?>:</label><br/>
			<?php if($noalias == true){ ?>

            <input type="text" disabled="disabled" name="group_url" id="group_url" onKeyUp="GroupUtils.validateGroupElements('group_url', 30, '<?php echo addslashes($lang->loc['url.limit.reached']); ?>');" value="<?php echo $input->HoloText($alias); ?>"/><br />
            <input type="hidden" name="group_url_edited" id="group_url_edited" value="1"/>

			<?php }else{ ?>

			<span id="group_url_text"><a href="<?php echo PATH; ?>/groups/<?php echo $input->HoloText($alias); ?>">/groups/<?php echo $input->HoloText($alias); ?></a></span><br/>
            <input type="hidden" name="group_url" id="group_url" value="<?php echo $input->HoloText($alias); ?>"/>
            <input type="hidden" name="group_url_edited" id="group_url_edited" value="0"/>

			<?php } ?>
          </div>
        </div>

        <div id="group-description-area">
          <div id="group_description_message_error" class="error"></div>
          <label for="group_description" id="description_text"><?php echo $lang->loc['edit.text'] ?>:</label>
          <span id="description_chars_left">
            <label for="characters_left"><?php echo $lang->loc['characters.left']; ?>:</label>
            <input id="group_description-counter" type="text" value="210" size="3" readonly="readonly" class="amount" />
          </span>
          <textarea name="group_description" id="group_description" onKeyUp="GroupUtils.validateGroupElements('group_description', 250, '<?php echo addslashes($lang->loc['description.limit.reached']); ?>');"><?php echo $input->HoloText($group['description']); ?></textarea>
        </div>
      </div>
      <div id="group-settings-type" class="group-settings-pane group-settings-selection">
        <label for="group_type"><?php echo $lang->loc['edit.group.type']; ?>:</label>
        <input type="radio" name="group_type" id="group_type" value="0"<?php if($group['state'] == 0){ ?> checked="checked"<?php } if($group['state'] == 3){ ?> disabled="disabled"<?php } ?> />
        <div class="description">
          <div class="group-type-normal"><?php echo $lang->loc['regular']; ?></div>
          <p>Anyone can join. 50,000 member limit.</p>
        </div>
        <input type="radio" name="group_type" id="group_type" value="1"<?php if($group['state'] == 1){ ?> checked="checked"<?php } if($group['state'] == 3){ ?> disabled="disabled"<?php } ?> />
        <div class="description">
          <div class="group-type-exclusive"><?php echo $lang->loc['exclusive']; ?></div>
          <p><?php echo $lang->loc['exclusive.desc']; ?></p>
        </div>
        <input type="radio" name="group_type" id="group_type" value="2"<?php if($group['state'] == 2){ ?> checked="checked"<?php } if($group['state'] == 3){ ?> disabled="disabled"<?php } ?> />
        <div class="description">
          <div class="group-type-private"><?php echo $lang->loc['private']; ?></div>
          <p><?php echo $lang->loc['private.desc']; ?></p>
        </div>
        <input type="radio" name="group_type" id="group_type" value="3"<?php if($group['state'] == 3){ ?> checked="checked"<?php } if($group['state'] == 3){ ?> disabled="disabled"<?php } ?> />
        <div class="description">
          <div class="group-type-large">Large</div>
          <p>Anyone can join. 50,000 member limit.</p>
          <p class="description-note"><?php echo $lang->loc['unlimited.note']; ?></p>
        </div>
        <input type="hidden" id="initial_group_type" value="<?php echo (int) $group['state']; ?>">
      </div>
    </div>


    <div id="forum-settings" style="display: none;">

      <div id="forum-settings-type" class="group-settings-pane group-settings-selection">
        <label for="forum_type"><?php echo $lang->loc['edit.forum.type']; ?>:</label>
        <input type="radio" name="forum_type" id="forum_type" value="0"<?php if($readOption == 0){ ?> checked="checked"<?php } ?> />
        <div class="description">
          <?php echo $lang->loc['public.forum']; ?><br />
          <p><?php echo $lang->loc['public.forum.desc']; ?></p>
        </div>
        <input type="radio" name="forum_type" id="forum_type" value="1"<?php if($readOption == 1){ ?> checked="checked"<?php } ?> />
        <div class="description">
          <?php echo $lang->loc['private.forum']; ?><br />
          <p><?php echo $lang->loc['private.forum.desc']; ?></p>
        </div>
      </div>

      <div id="forum-settings-topics" class="group-settings-pane group-settings-selection">
        <label for="new_topic_permission"><?php echo $lang->loc['edit.new.thread']; ?>:</label>
        <input type="radio" name="new_topic_permission" id="new_topic_permission" value="2"<?php if($postOption == 2){ ?> checked="checked"<?php } ?> />
        <div class="description">
          <?php echo $lang->loc['admin']; ?><br />
          <p><?php echo $lang->loc['admin.desc']; ?></p>
        </div>
        <input type="radio" name="new_topic_permission" id="new_topic_permission" value="1"<?php if($postOption == 1){ ?> checked="checked"<?php } ?> />
        <div class="description">
          <?php echo $lang->loc['members']; ?><br />
          <p><?php echo $lang->loc['members.desc']; ?></p>
        </div>
        <input type="radio" name="new_topic_permission" id="new_topic_permission" value="0"<?php if($postOption == 0){ ?> checked="checked"<?php } ?> />
        <div class="description">
          <?php echo $lang->loc['everyone']; ?><br />
          <p><?php echo $lang->loc['everyone.desc']; ?></p>
        </div>
      </div>
    </div>


    <div id="room-settings" style="display: none;">
      <label><?php echo $lang->loc['select.group.room']; ?>:</label>
      <div id="room-settings-id" class="group-settings-pane-wide group-settings-selection">
        <input type="radio" name="roomId" value="<?php echo (int) $group['room_id']; ?>" checked="checked" style="display:none;" />
        <ul>
          <li><input type="radio" disabled="disabled" name="roomId" value=""<?php if($group['room_id'] == "0"){ ?> checked="checked"<?php } ?> /><div><?php echo $lang->loc['no.room']; ?></div></li>

<?php
$i = 0;
foreach($rooms as $row){
	if($input->IsEven($i)){ $even = " class=\"even\""; }else{ $even = ""; }
?>

		  <li<?php echo $even; ?>>
            <input type="radio" disabled="disabled" name="roomId" value="<?php echo $row['id']; ?>"<?php if($group['room_id'] == $row['id']){ ?> checked="checked"<?php } ?> />
            <a href="<?php echo PATH; ?>/client?forwardId=2&amp;roomId=<?php echo $row['id']; ?>" onclick="HabboClient.roomForward(this, '<?php echo $row['id']; ?>', 'private'); return false;" target="client" class="room-enter"><?php echo $lang->loc['enter']; ?></a>
            <div>
              <?php echo $input->unicodeToImage($input->HoloText($row['name'])); ?><br />
              <span class="room-description"><?php echo $input->unicodeToImage($input->HoloText($row['description'])); ?></span>
            </div>
          </li>

<?php $i++; } ?>
        </ul>
      </div>
    </div>

    <div id="group-button-area">
      <a href="#" id="group-settings-update-button" class="new-button" onclick="showGroupSettingsConfirmation('<?php echo $group['id']; ?>');">
        <b><?php echo $lang->loc['save.changes']; ?></b><i></i>
      </a>
      <a id="group-delete-button" href="#" class="new-button red-button" onclick="openGroupActionDialog('/groups/actions/confirm_delete_group', '/groups/actions/delete_group', null , '<?php echo $group['id']; ?>', null);">
        <b><?php echo $lang->loc['delete.group']; ?></b><i></i>
      </a>
      <a href="#" id="group-settings-close-button" class="new-button" onclick="closeGroupSettings(); return false;"><b><?php echo $lang->loc['cancel']; ?></b><i></i></a>
    </div>
  </div>
</form>

<div class="clear"></div>

<script type="text/javascript" language="JavaScript">
    L10N.put("group.settings.title.text", "<?php echo addslashes($lang->loc['edit.group.settings']); ?>");
    L10N.put("group.settings.group_type_change_warning.normal", "<?php echo addslashes($lang->loc['confirm.change.group.type']); ?> <strong\><?php echo addslashes($lang->loc['normal.confirm']); ?></strong\>?");
    L10N.put("group.settings.group_type_change_warning.exclusive", "<?php echo addslashes($lang->loc['confirm.change.group.type']); ?> <strong \><?php echo addslashes($lang->loc['exclusive.confirm']); ?></strong\>?");
    L10N.put("group.settings.group_type_change_warning.closed", "<?php echo addslashes($lang->loc['confirm.change.group.type']); ?> <strong\><?php echo addslashes($lang->loc['private.confirm']); ?></strong\>?");
    L10N.put("group.settings.group_type_change_warning.large", "<?php echo addslashes($lang->loc['confirm.change.group.type']); ?> <strong\><?php echo addslashes($lang->loc['large.confirm']); ?></strong\>? <?php echo addslashes($lang->loc['large.confirm.warning']); ?>");
    L10N.put("myhabbo.groups.confirmation_ok", "<?php echo addslashes($lang->loc['ok']); ?>");
    L10N.put("myhabbo.groups.confirmation_cancel", "<?php echo addslashes($lang->loc['cancel']); ?>");
    switchGroupSettingsTab(null, "group");
</script>
