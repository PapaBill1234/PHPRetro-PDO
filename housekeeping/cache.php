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

$page['dir'] = '\housekeeping';
$page['housekeeping'] = true;
$page['rank'] = 7;
require_once __DIR__ . '/../includes/core.php');
require_once('./includes/hksession.php');
$data = new housekeeping_sql;
$lang->addLocale("housekeeping.cache");

$driver = CacheFactory::activeDriver();
if ($settings->checkCache()) {
	$message = $lang->loc['error.3'];
	$code = "yellow";
	$settings->generateCache();
} else {
	$message = $lang->loc['up.to.date'].' ('.$driver.')';
	$code = "ok";
}

$page['name'] = $lang->loc['pagename.cache'];
$page['category'] = "settings";
require_once('./templates/housekeeping_header.php');
?>
<div class="page_title">
 <img src="<?php echo PATH; ?>/housekeeping/images/icons/cache.png" class="pticon">
 <span class="page_name_shadow"><?php echo $lang->loc['pagename.cache']; ?></span>

 <span class="page_name"><?php echo $lang->loc['pagename.cache']; ?></span>
</div>
<div class="page_main">

<table border="0" cellpadding="0" cellspacing="0" height="100%">
<tbody>
<tr height="100%" />
<td class="page_main_left">
<div class="left_date"><?php echo date('l F j, Y | g:iA'); ?></div>
<div class="hr"></div>
<div class="text">
<p><?php echo $lang->loc['cache.desc']; ?></p>
</div>
<div class="hr"></div>
<div class="text">
<p><?php echo $lang->loc['visit.the']; ?> <a href="<?php echo PATH; ?>/housekeeping/settings/site"><?php echo $lang->loc['settings.page']; ?></a> <?php echo $lang->loc['to.change']; ?></p>
</div>
</td>
 <td class="page_main_right">
<div class="center">
<div class="clean-<?php echo $code; ?>"><?php echo $message; ?></div>
</div>
 </td>
</tr>
</tbody>
</table>

</div>
<?php require_once('./templates/housekeeping_footer.php'); ?>