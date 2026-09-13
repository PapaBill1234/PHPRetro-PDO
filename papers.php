<?php
require_once('./includes/core.php'); $lang->addLocale('landing.papers');
$requested = (string) ($_GET['page'] ?? 'privacy');
if ($requested === 'disclaimer') { $title = $lang->loc['disclaimer']; $content = $settings->find('paper_disclaimer'); } else { $title = $lang->loc['privacy.policy']; $content = $settings->find('paper_privacy'); }
$page['name'] = $title; $page['bodyid'] = 'landing'; require_once('./templates/login_header.php');
?>
<div id="process-content"><div id="terms" class="box-content"><div class="tos-header"><b><?php echo HoloText($title); ?></b></div><div class="tos-item"><?php echo $content; ?></div></div></div>
<?php require_once('./templates/login_footer.php'); ?>