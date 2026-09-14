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

require_once __DIR__.'/../includes/habblet.php';
require_once __DIR__.'/../includes/PhpretroHomes.php';
if (($page['bypass'] ?? false) !== true) { habbletRequireUser(); }
$homes = phpretroHomes();
$widgetid = isset($widgetid) ? (int) $widgetid : habbletInt($_POST, 'widgetId');
$widget = phpretroHomesRun(static fn() => $homes->widget($widgetid));
if ($widget === null) { return; }
$search = isset($search) ? (string) $search : habbletText($_POST, 'searchString');
$pagenum = isset($pagenum) ? max(1, (int) $pagenum) : max(1, habbletInt($_POST, 'pageNumber', 1));
$ownerId = (int) $widget['user_id'];
$count = $homes->friendCount($ownerId, $search);
$offset = ($pagenum - 1) * 20;
$friends = $homes->friends($ownerId, $search, $offset, 20);
$lang->addLocale('friendswidget.friendslist');
?>
<div class="avatar-widget-list-container">
<ul id="avatar-list-list" class="avatar-widget-list">
<?php if ($count === 0 && $search === '') { echo $lang->loc['no.friends'] ?? 'No friends.'; } ?>
<?php foreach ($friends as $friend) { ?>
<li id="avatar-list-<?php echo $widgetid; ?>-<?php echo (int) $friend['id']; ?>" title="<?php echo $input->HoloText($friend['username']); ?>">
<div class="avatar-list-open"><a href="#" id="avatar-list-open-link-<?php echo $widgetid; ?>-<?php echo (int) $friend['id']; ?>" class="avatar-list-open-link"></a></div>
<div class="avatar-list-avatar"><img src="<?php echo $user->avatarURL($friend['look'], 's,2,2,sml,1,0'); ?>" alt="" /></div>
<h4><a href="<?php echo PATH; ?>/home/<?php echo rawurlencode($friend['username']); ?>"><?php echo $input->HoloText($friend['username']); ?></a></h4>
<p class="avatar-list-birthday"><?php echo $friend['account_created'] ? date('d-m-Y', (int) $friend['account_created']) : ''; ?></p>
</li>
<?php } ?>
</ul>
<div id="avatar-list-info" class="avatar-list-info">
<div class="avatar-list-info-close-container"><a href="#" class="avatar-list-info-close"></a></div>
<div class="avatar-list-info-container"></div>
</div>
</div>
<div id="avatar-list-paging">
<?php
$from = $count === 0 ? 0 : $offset + 1;
$to = min($offset + 20, $count);
echo $from.' - '.$to.' / '.$count;
?>
<input type="hidden" id="pageNumber" value="<?php echo (int) $pagenum; ?>"/>
<input type="hidden" id="avatarlist-search-widget" value="<?php echo (int) $widgetid; ?>"/>
</div>
