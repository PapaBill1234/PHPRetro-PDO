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

if (!defined("IN_HOLOCMS")) { header("Location: ".PATH."/"); exit; }

/*

	Please do not remove the copyright infomation below.  Doing so shows disrespct towards all HoloCMS/PHPRetro developers.
	You may remove any links though (you selfish jerk)
	
	The developers of this project do this for free with no personal gain, if you remove the copyright, you are breaking the license.
	If I find too many people doing so, then either 1) the project will be discontinued, or 2) the project will be closed-source

*/
$lang->addLocale("footer");
?>
<script type="text/javascript">
HabboView.run();

</script>

<?php if(empty($page['no_column3'])){ ?>

<div id="column3" class="column">
				<div class="habblet-container ">
						<div class="ad-container">
<?php $banners = [];
try { $banners = $db->fetchAll('SELECT text, banner, url, status, advanced, html FROM phpretro_banners WHERE status = ? ORDER BY sort_order ASC, id ASC', ['1']); } catch (Throwable $exception) { $banners = []; }
foreach ($banners as $row) { ?>
<?php if($row['advanced'] == "1"){
echo $input->HoloText($row['html'], true)."\n<br />\n";
}else{ ?>
<?php if(!empty($row['banner']) && ($bannerUrl = HoloUrl($row['banner'])) !== '') { ?><a target="_blank" rel="noopener noreferrer" href="<?php echo HoloUrl($row['url']); ?>"><img src="<?php echo $bannerUrl; ?>" alt=""></a><br /><?php } ?>
<?php if(!empty($row['text']) && ($linkUrl = HoloUrl($row['url'])) !== '') { ?><a target="_blank" rel="noopener noreferrer" href="<?php echo $linkUrl; ?>"><?php echo $input->HoloText($row['text']); ?></a><br /><?php } ?>
<?php } ?>
<?php } ?>
						</div>
				</div>
				<script type="text/javascript">if (!$(document.body).hasClassName('process-template')) { Rounder.init(); }</script>
</div>
<?php }
$sql = [];
try { $sql = $db->fetchAll('SELECT id, question FROM phpretro_faq WHERE active = 1 ORDER BY sort_order ASC, id ASC'); } catch (Throwable $exception) { $sql = []; }
$content = "";
foreach ($sql as $row){
	$content = $content." | <a href=\"".PATH."/help/".$row['id']."\" target=\"_new\">".$input->HoloText($row['question'])."</a>";
}
?>

<!--[if lt IE 7]>
<script type="text/javascript">
Pngfix.doPngImageFix();
</script>
<![endif]-->
    </div>
<div id="footer">
	<p><a href="<?php echo PATH; ?>/" target="_self"><?php echo $lang->loc['link.homepage']; ?></a> | <a href="<?php echo PATH; ?>/papers/disclaimer" target="_self"><?php echo $lang->loc['link.disclaimer']; ?></a> | <a href="<?php echo PATH; ?>/papers/privacy" target="_self"><?php echo $lang->loc['link.privacy']; ?></a><?php echo $content; ?></p>
	<?php /*@@* DO NOT EDIT OR REMOVE THE LINE BELOW WHATSOEVER! *@@ You ARE allowed to remove the links though*/ ?>
	<p>Powered by <a href="http://www.phpretro.com/">PHPRetro</a><br /><?php echo $lang->loc['copyright.habbo']; ?></p>
	<?php /*@@* DO NOT EDIT OR REMOVE THE LINE ABOVE WHATSOEVER! *@@ You ARE allowed to remove the links though*/ ?>
</div></div>

</div>

<script type="text/javascript">
HabboView.run();
</script>

<?php echo $settings->find("site_tracking"); ?>

</body>
</html>
