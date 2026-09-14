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
$lang->addLocale('community.tags');
$tag1 = habbletText($_POST, 'tag1');
$tag2 = habbletText($_POST, 'tag2');
$count1 = habbletTagCount($db, $tag1);
$count2 = habbletTagCount($db, $tag2);
$end = $count1 === $count2 ? 0 : ($count1 > $count2 ? 2 : 1);
?>
<div id="fightResultCount" class="fight-result-count">
<?php echo $lang->loc[$end === 0 ? 'tie' : 'winner']; ?><br />
<?php echo $input->HoloText($tag1).' ('.$count1.') hits'; ?><br />
<?php echo $input->HoloText($tag2).' ('.$count2.') hits'; ?><p>User tags only; group tags are unavailable.</p></div>
<div class="fight-image"><img src="<?php echo PATH; ?>/web-gallery/images/tagfight/tagfight_end_<?php echo $end; ?>.gif" alt="" name="fightanimation" id="fightanimation" />
<a id="tag-fight-button-new" href="#" class="new-button" onclick="TagFight.newFight(); return false;"><b><?php echo $lang->loc['again']; ?></b><i></i></a>
<a id="tag-fight-button" href="#" style="display:none" class="new-button" onclick="TagFight.init(); return false;"><b><?php echo $lang->loc['fight']; ?></b><i></i></a></div>
