<?php
$page['allow_guests'] = true; require_once('./includes/core.php');
$database = new Database(); $faqs = $database->fetchAll('SELECT category, question, answer FROM phpretro_faq WHERE active = 1 ORDER BY category, sort_order, id');
$groups = []; foreach ($faqs as $faq) { $groups[$faq['category']][] = $faq; }
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$page['id'] = 'help'; $page['name'] = 'Help'; $page['bodyid'] = 'home'; $page['cat'] = 'community'; require_once('./templates/community_header.php');
?>
<div id="container"><div id="content" class="clearfix"><div id="column1" class="column"><div class="habblet-container"><div class="cbb clearfix default"><h2 class="title">Help and FAQ</h2><div class="box-content"><?php if ($groups === []) { ?><p>No FAQ entries are available yet.</p><?php } foreach ($groups as $category => $entries) { ?><h3><?php echo $escape($category); ?></h3><?php foreach ($entries as $faq) { ?><h4><?php echo $escape($faq['question']); ?></h4><p><?php echo nl2br($escape($faq['answer'])); ?></p><?php } } ?></div></div></div></div></div></div>
<?php require_once('./templates/community_footer.php'); ?>