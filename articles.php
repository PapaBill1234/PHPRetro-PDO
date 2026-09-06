<?php
// FILE: articles.php
$page['allow_guests'] = true;
require_once('./includes/core.php');
require_once('./includes/session.php');
$lang->addLocale("community.news");

$db = new Database();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id > 0) {
    $newsRow = $db->fetchRow(
        "SELECT id, title, text, button_text, button_type, button_link, image FROM hotelview_news WHERE id = ?",
        [$id]
    );
} else {
    $newsRow = $db->fetchRow(
        "SELECT id, title, text, button_text, button_type, button_link, image FROM hotelview_news ORDER BY id DESC LIMIT 1"
    );
}
$announcements = $db->fetchAll(
    "SELECT id, title FROM hotelview_news ORDER BY id DESC LIMIT 20"
);
$page['id'] = "news";
$page['name'] = $lang->loc['pagename.news'] . ($newsRow ? ' - ' . $input->HoloText($newsRow['title']) : '');
$page['bodyid'] = "news";
$page['cat'] = "community";
require_once('./templates/community_header.php');
?>
<div id="container"><div id="content" style="position: relative" class="clearfix">
<div id="column1" class="column"><div class="habblet-container"><div class="cbb clearfix default"><h2 class="title"><?php echo $lang->loc['pagename.news']; ?></h2>
<div id="article-archive"><ul>
<?php foreach ($announcements as $announcement) { ?>
    <li><a href="<?php echo PATH; ?>/articles/<?php echo (int) $announcement['id']; ?>" class="article-<?php echo (int) $announcement['id']; ?>"><?php echo $input->HoloText($announcement['title']); ?>&nbsp;&raquo;</a></li>
<?php } ?>
</ul></div></div></div></div>
<div id="column2" class="column"><div class="habblet-container"><div class="cbb clearfix notitle"><div id="article-wrapper">
<?php if ($newsRow) { ?>
<h2><?php echo $input->HoloText($newsRow['title']); ?></h2>
<?php if ($newsRow['image'] !== '') { ?><img src="<?php echo $input->HoloText($newsRow['image']); ?>" class="article-image" alt="" /><?php } ?>
<div class="article-body"><p><?php echo nl2br($input->HoloText($newsRow['text'])); ?></p>
<?php if ($newsRow['button_text'] !== '' && $newsRow['button_link'] !== '') { ?>
<a class="new-button" href="<?php echo $input->HoloText($newsRow['button_link']); ?>" data-button-type="<?php echo $input->HoloText($newsRow['button_type']); ?>"><b><?php echo $input->HoloText($newsRow['button_text']); ?></b><i></i></a>
<?php } ?></div>
<?php } else { ?><p><?php echo $lang->loc['no.news']; ?></p><?php } ?>
</div></div></div></div></div></div>
<script type="text/javascript">HabboView.run();</script>
<?php require_once('./templates/community_footer.php'); ?>
