<?php
// FILE: collectables.php
$page['allow_guests'] = true;
require_once('./includes/core.php');
require_once('./includes/session.php');
$lang->addLocale("credits.collectables");

$page['id'] = "collectables";
$page['name'] = $lang->loc['pagename.collectables'];
$page['bodyid'] = "home";
$page['cat'] = "credits";
require_once('./templates/community_header.php');

$db = new Database();
$currentTime = mktime(0, 0, 0, (int) date('m'), 1, (int) date('Y'));
$nextTime = strtotime('+1 month', $currentTime);
$currentCollectable = $db->fetchRow(
    "SELECT name, description, image, time FROM phpretro_collectibles WHERE time = ? LIMIT 1",
    [$currentTime]
);
$nocollectable = $currentCollectable === false;
$row = $currentCollectable ?: [
    'name' => $lang->loc['no.collectables'],
    'description' => $lang->loc['no.collectables.desc'],
    'image' => '',
    'time' => $currentTime,
];
$rows = $db->fetchAll(
    "SELECT name, description, image, time FROM phpretro_collectibles WHERE time < ? ORDER BY time DESC",
    [$currentTime]
);
?>
<div id="container">
    <div id="content" style="position: relative" class="clearfix">
    <div id="column1" class="column">
                <div class="habblet-container " id="collectible-current">
                        <div class="cbb clearfix gray ">
                            <h2 class="title"><?php echo $lang->loc['current.collectables']; ?></h2>
                            <div id="collectible-current-content" class="clearfix">
        <div id="collectibles-current-img" style="background-image: url(<?php echo HoloUrl(str_replace("%path%", PATH, $row['image'])); ?>)"></div>
        <h4><?php echo $input->HoloText($row['name']); ?></h4>
        <p><?php echo date('F Y', $currentTime); ?></p>
        <p class="last"><?php echo $input->HoloText($row['description']); ?></p>
        <?php if ($user->id != "0" && !$nocollectable) { ?>
        <p id="collectibles-purchase">
            <a href="#" class="new-button collectibles-purchase-current"><b><?php echo $lang->loc['purchase']; ?></b><i></i></a>
            <span class="collectibles-timeleft"><?php echo $lang->loc['time.left']; ?>: <span id="collectibles-timeleft-value"></span></span>
        </p>
        <?php } ?>
    </div>
<?php if ($user->id != "0" && !$nocollectable) { ?>
<script type="text/javascript">
L10N.put("collectibles.purchase.title", "<?php echo $lang->loc['confirm.purchase.collectables']; ?>");
L10N.put("time.days", "{0}d");
L10N.put("time.hours", "{0}h");
L10N.put("time.minutes", "{0}min");
L10N.put("time.seconds", "{0}s");
Collectibles.init(<?php echo $nextTime - time(); ?>);
</script>
<?php } ?>
                    </div>
                </div>
                <script type="text/javascript">if (!$(document.body).hasClassName('process-template')) { Rounder.init(); }</script>
                <div class="habblet-container ">
                        <div class="cbb clearfix red ">
                            <h2 class="title"><?php echo $lang->loc['showroom']; ?></h2>
                            <ul id="collectibles-list">
<?php foreach ($rows as $index => $showroomRow) { $even = $input->IsEven($index + 1) ? "even" : "odd"; ?>
    <li class="<?php echo $even; ?> clearfix">
        <div class="collectibles-prodimg" style="background-image: url(<?php echo HoloUrl(str_replace("%path%", PATH, $showroomRow['image'])); ?>)"></div>
        <h4><?php echo date('F Y', (int) $showroomRow['time']); ?>: <?php echo $input->HoloText($showroomRow['name']); ?></h4>
        <p class="collectibles-proddesc last"><?php echo $input->HoloText($showroomRow['description']); ?></p>
    </li>
<?php } ?>
</ul>
                    </div>
                </div>
                <script type="text/javascript">if (!$(document.body).hasClassName('process-template')) { Rounder.init(); }</script>
</div>
<div id="column2" class="column">
                <div class="habblet-container ">
                        <div class="cbb clearfix red ">
                            <h2 class="title"><?php echo $lang->loc['what.are.collectables']; ?></h2>
                            <div id="collectibles-instructions" class="box-content"><?php echo $lang->loc['collectables.desc']; ?></div>
                    </div>
                </div>
                <script type="text/javascript">if (!$(document.body).hasClassName('process-template')) { Rounder.init(); }</script>
                <div class="habblet-container ">
                        <div class="cbb clearfix red ">
                            <h2 class="title"><?php echo $lang->loc['invest.in.collectables']; ?></h2>
                            <div class="box-content">
<p class="collectibles-value-intro"><img src="<?php echo PATH; ?>/web-gallery/v2/images/collectibles/ukplane.png" alt="" width="79" height="47" /><?php echo $lang->loc['collect.collectables']; ?></p>
<p class="clear last"><img src="<?php echo PATH; ?>/web-gallery/v2/images/collectibles/chart.png" alt="" width="272" height="117" /></p>
</div>
                    </div>
                </div>
                <script type="text/javascript">if (!$(document.body).hasClassName('process-template')) { Rounder.init(); }</script>
</div>
<?php require_once('./templates/community_footer.php'); ?>
