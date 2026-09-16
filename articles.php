<?php
// FILE: articles.php
$page['allow_guests'] = true;
require_once('./includes/core.php');
require_once('./includes/session.php');
$lang->addLocale("community.news");

$db = new Database();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$archive = isset($_GET['archive']) && $_GET['archive'] === 'true';
$category = isset($_GET['category']) ? trim(rawurldecode((string) $_GET['category'])) : '';
$pageNumber = isset($_GET['pageNumber']) ? max(1, (int) $_GET['pageNumber']) : 1;
$perPage = 20;

if ($id > 0) {
    $newsRow = $db->fetchRow(
        "SELECT id, title, summary, story, author, categories, images, time FROM phpretro_news WHERE id = ? LIMIT 1",
        [$id]
    );
} else {
    $newsRow = $db->fetchRow(
        "SELECT id, title, summary, story, author, categories, images, time FROM phpretro_news ORDER BY time DESC, id DESC LIMIT 1"
    );
}

if ($archive) {
    $archiveCount = (int) $db->fetchColumn("SELECT COUNT(*) FROM phpretro_news");
    $archivePages = max(1, (int) ceil($archiveCount / $perPage));
    $pageNumber = min($pageNumber, $archivePages);
    $newsList = $db->fetchAll(
        "SELECT id, title, time FROM phpretro_news ORDER BY time DESC, id DESC LIMIT 20 OFFSET ?",
        [($pageNumber - 1) * $perPage]
    );
} elseif ($category !== '') {
    $newsList = $db->fetchAll(
        "SELECT id, title, time FROM phpretro_news
         WHERE FIND_IN_SET(?, REPLACE(categories, ', ', ',')) > 0
         ORDER BY time DESC, id DESC",
        [$category]
    );
} else {
    $newsList = $db->fetchAll(
        "SELECT id, title, time FROM phpretro_news ORDER BY time DESC, id DESC LIMIT 20"
    );
}

$page['id'] = "news";
$page['name'] = $lang->loc['pagename.news'] . ($newsRow ? ' - ' . $input->HoloText($newsRow['title']) : '');
$page['bodyid'] = "news";
$page['cat'] = "community";
require_once('./templates/community_header.php');
?>
<div id="container"><div id="content" style="position: relative" class="clearfix">
<div id="column1" class="column"><div class="habblet-container"><div class="cbb clearfix default"><h2 class="title"><?php echo $lang->loc['pagename.news']; ?></h2>
<div id="article-archive">
<?php if ($archive) { ?>
<div id="article-paging" class="clearfix">
    <?php if ($pageNumber < $archivePages) { ?><a href="<?php echo PATH; ?>/articles?archive=true&amp;pageNumber=<?php echo $pageNumber + 1; ?>" class="older">&lt;&lt; <?php echo $lang->loc['older']; ?></a><?php } ?>
    <?php if ($pageNumber > 1) { ?><a href="<?php echo PATH; ?>/articles?archive=true&amp;pageNumber=<?php echo $pageNumber - 1; ?>" class="newer"><?php echo $lang->loc['newer']; ?> &gt;&gt;</a><?php } ?>
</div>
<?php } ?>
<ul>
<?php foreach ($newsList as $newsItem) { ?>
    <li><a href="<?php echo PATH; ?>/articles?id=<?php echo (int) $newsItem['id']; ?>" class="article-<?php echo (int) $newsItem['id']; ?>"><?php echo $input->HoloText($newsItem['title']); ?>&nbsp;&raquo;</a></li>
<?php } ?>
</ul>
<?php if (!$archive && $category === '') { ?><a href="<?php echo PATH; ?>/articles?archive=true"><?php echo $lang->loc['more.news']; ?> &raquo;</a><?php } ?>
</div></div></div></div>
<div id="column2" class="column"><div class="habblet-container"><div class="cbb clearfix notitle"><div id="article-wrapper">
<?php if ($newsRow) {
    $categories = array_filter(array_map('trim', explode(',', $newsRow['categories'])), static function ($value) { return $value !== ''; });
    $images = array_filter(array_map('trim', explode(',', $newsRow['images'])), static function ($value) { return $value !== ''; });
?>
<h2><?php echo $input->HoloText($newsRow['title']); ?></h2>
<div class="article-meta"><?php echo $lang->loc['posted'] . ' ' . date('M j, Y', (int) $newsRow['time']); ?>
<?php if ($categories) { ?> —
<?php foreach ($categories as $index => $newsCategory) { ?><?php if ($index > 0) { echo ', '; } ?><a href="<?php echo PATH; ?>/articles?category=<?php echo rawurlencode($newsCategory); ?>"><?php echo $input->HoloText($newsCategory); ?></a><?php } ?>
<?php } ?></div>
<?php if ($images && ($imageUrl = HoloUrl($images[0])) !== '') { ?><img src="<?php echo $imageUrl; ?>" class="article-image" alt="" /><?php } ?>
<p class="summary"><?php echo nl2br($input->HoloText($newsRow['summary'])); ?></p>
<div class="article-body"><p><?php echo nl2br($input->HoloText($newsRow['story'])); ?></p>
<div class="article-author">- <?php echo $input->HoloText($newsRow['author']); ?></div>
<?php if (count($images) > 1) { ?><div class="article-images clearfix">
<?php foreach (array_slice($images, 1) as $image) { if (($imageUrl = HoloUrl($image)) === '') { continue; } ?><a href="<?php echo $imageUrl; ?>" style="background-image: url(<?php echo $imageUrl; ?>); background-position: 0 0"></a><?php } ?>
</div><?php } ?>
<script type="text/javascript">
document.observe("dom:loaded", function() {
    $$('a.article-<?php echo (int) $newsRow['id']; ?>').each(function(a) { a.replace(a.innerHTML); });
    $$('.article-images a').each(function(a) {
        Event.observe(a, 'click', function(e) { Event.stop(e); Overlay.lightbox(a.href, "<?php echo $lang->loc['image.loading']; ?>"); });
    });
});
</script>
</div>
<?php } else { ?><p><?php echo $lang->loc['no.news']; ?></p><?php } ?>
</div></div></div></div></div></div>
<script type="text/javascript">HabboView.run();</script>
<?php require_once('./templates/community_footer.php'); ?>
