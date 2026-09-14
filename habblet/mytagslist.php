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
// Read the emulator's user tags; legacy group tags/add/remove need batch 3.
$tags = empty($user->logged_in) ? [] : habbletUserTags($db, (int) $user->id);
?>
<div class="habblet" id="my-tags-list"><ul class="tag-list">
<?php foreach ($tags as $tag) { ?>
<li><a class="tag" href="<?php echo PATH.'/tag/'.rawurlencode($tag); ?>"><?php echo $input->HoloText($tag); ?></a></li>
<?php } ?></ul><p>Manage your tags in the hotel client.</p></div>
