<?php
// FILE: community.php
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

$page['allow_guests'] = true;
require_once('./includes/core.php');
require_once('./includes/session.php');
$data = new community_sql;
$lang->addLocale("community.community");

$page['id'] = "community";
$page['name'] = $lang->loc['pagename.community'];
$page['bodyid'] = "home";
$page['cat'] = "community";

$db = new Database();

require_once('./templates/community_header.php');
?>

<div id="container">
	<div id="content" style="position: relative" class="clearfix">
    <div id="column1" class="column">

				<div class="habblet-container ">		
						<div class="cbb clearfix green ">

<div class="box-tabs-container clearfix">
    <h2><?php echo $lang->loc['rooms']; ?></h2>
    <ul class="box-tabs">
        <li id="tab-0-0-1"><a href="#"><?php echo $lang->loc['top.rated']; ?></a><span class="tab-spacer"></span></li>
        <li id="tab-0-0-2" class="selected"><a href="#"><?php echo $lang->loc['recommanded.rooms']; ?></a><span class="tab-spacer"></span></li>
    </ul>
</div>
    <div id="tab-0-0-1-content"  style="display: none">

    		<div class="progressbar"><img src="<?php echo PATH; ?>/web-gallery/images/progress_bubbles.gif" alt="" width="29" height="6" /></div>
    		<a href="<?php echo PATH; ?>/habblet/proxy.php?hid=h120" class="tab-ajax"></a>
    </div>
    <div id="tab-0-0-2-content" >

<div id="rooms-habblet-list-container-h119" class="recommendedrooms-lite-habblet-list-container">
        <ul class="habblet-list">
		
<?php
$i = 0;
// select1 expects limit and offset
$rows = $db->fetchAll("SELECT id, name, owner_id, owner_name, users, users_max, is_public FROM rooms WHERE is_staff_picked = '1' ORDER BY users DESC LIMIT ? OFFSET ?", [5, 0]);
foreach ($rows as $row) {
	$i++;
	if($input->IsEven($i)){
		$even = "odd";
	} else {
		$even = "even";
	}
	$users_now = (int)$row['users'];
	$users_max = (int)$row['users_max'];
	if($users_max == 0) $users_max = 1;
	$count = ($users_now / $users_max) * 100;
	if($count >= 99){
		$room_fill = 5;
	} elseif($count > 65){
		$room_fill = 4;
	} elseif($count > 32){
		$room_fill = 3;
	} elseif($count > 0){
		$room_fill = 2;
	} else {
		$room_fill = 1;
	}
	
	$owner_name = $row['owner_name'];
	$owner_display = $row['owner_name'];

printf("<li class=\"%s\">
    <span class=\"clearfix enter-room-link room-occupancy-%s\" title=\"".$lang->loc['go.to.room']."\" roomid=\"%s\">
	    <span class=\"room-enter\">".$lang->loc['enter']."</span>
	    <span class=\"room-name\">%s</span>
	    <span class=\"room-description\">%s</span>
		<span class=\"room-owner\">".$lang->loc['owner'].": <a href=\"".PATH."/home/%s\">%s</a></span>
    </span>
</li>\n", $even, $room_fill, $row['id'], $input->unicodeToImage($input->HoloText($row['username'])), $input->unicodeToImage($input->HoloText($row['username'])), $owner_display, $owner_display);
}
?>

        </ul>
            <div id="room-more-data-h119" style="display: none">
                <ul class="habblet-list room-more-data">

<?php
$i = 0;
$rows2 = $db->fetchAll("SELECT id, name, owner_id, owner_name, users, users_max, is_public FROM rooms WHERE is_staff_picked = '1' ORDER BY users DESC LIMIT ? OFFSET ?", [15, 5]);
foreach ($rows2 as $row) {
	$i++;
	if($input->IsEven($i)){
		$even = "odd";
	} else {
		$even = "even";
	}
	$users_now = (int)$row['users'];
	$users_max = (int)$row['users_max'];
	if($users_max == 0) $users_max = 1;
	$count = ($users_now / $users_max) * 100;
	if($count >= 99){
		$room_fill = 5;
	} elseif($count > 65){
		$room_fill = 4;
	} elseif($count > 32){
		$room_fill = 3;
	} elseif($count > 0){
		$room_fill = 2;
	} else {
		$room_fill = 1;
	}
	
	$owner_name = $row['owner_name'];
	$owner_display = $row['owner_name'];

printf("<li class=\"%s\">
    <span class=\"clearfix enter-room-link room-occupancy-%s\" title=\"".$lang->loc['go.to.room']."\" roomid=\"%s\">
	    <span class=\"room-enter\">".$lang->loc['enter.room']."</span>
	    <span class=\"room-name\">%s</span>
	    <span class=\"room-description\">%s</span>
		<span class=\"room-owner\">".$lang->loc['owner'].": <a href=\"".PATH."/home/%s\">%s</a></span>
    </span>
</li>", $even, $room_fill, $row['id'], $input->unicodeToImage($input->HoloText($row['username'])), $input->unicodeToImage($input->HoloText($row['username'])), $owner_display, $owner_display);
}
?>

                </ul>
            </div>
            <div class="clearfix">
                <a href="#" class="room-toggle-more-data" id="room-toggle-more-data-h119"><?php echo $lang->loc['show.more.rooms']; ?></a>
            </div>
</div>
<script type="text/javascript">
L10N.put("show.more", "<?php echo $lang->loc['show.more.rooms']; ?>");
L10N.put("show.less", "<?php echo $lang->loc['show.less.rooms']; ?>");
var roomListHabblet_h119 = new RoomListHabblet("rooms-habblet-list-container-h119", "room-toggle-more-data-h119", "room-more-data-h119");
</script>    </div>

					</div>
				</div>
				<script type="text/javascript">if (!$(document.body).hasClassName('process-template')) { Rounder.init(); }</script>
				
				<div class="habblet-container ">		
						<div class="cbb clearfix blue ">
<div class="box-tabs-container clearfix">
    <h2><?php echo $lang->loc['groups']; ?></h2>
    <ul class="box-tabs">
        <li id="tab-0-1-1"><a href="#"><?php echo $lang->loc['hot.groups']; ?></a><span class="tab-spacer"></span></li>
        <li id="tab-0-1-2" class="selected"><a href="#"><?php echo $lang->loc['recent.topics']; ?></a><span class="tab-spacer"></span></li>
    </ul>

</div>
    <div id="tab-0-1-1-content"  style="display: none">
    		<div class="progressbar"><img src="<?php echo PATH; ?>/web-gallery/images/progress_bubbles.gif" alt="" width="29" height="6" /></div>
    		<a href="<?php echo PATH; ?>/habblet/proxy.php?hid=h122" class="tab-ajax"></a>
    </div>
    <div id="tab-0-1-2-content" >

<ul class="active-discussions-toplist">
<?php
$i = 0;
$rows = $db->fetchAll("SELECT id, guild_id, subject, posts_count FROM guilds_forums_threads ORDER BY updated_at DESC LIMIT 10");
foreach($rows as $row) {
	$i++;
	if($input->IsEven($i)){
		$even = "even";
	} else {
		$even = "odd";
	}
	$posts = (int) $row['posts_count'];
	$pages = ceil($posts / 10);
	$pagelink = "<a href=\"".groupURL($row['guild_id'])."/discussions/".$row['id']."/id/page/1\" class=\"topiclist-page-link secondary\">1</a>";
	if($pages > 4){
		$pageat = $pages - 2;
		$pagelink .= "\n...";
		while($pageat <= $pages){
			$pagelink .= " <a href=\"".groupURL($row['guild_id'])."/discussions/".$row['id']."/id/page/".$pageat."\" class=\"topiclist-page-link secondary\">".$pageat."</a>";
			$pageat++;
		}
	}elseif($pages != 1){
		$pageat = 2;
		while($pageat <= $pages){
			$pagelink .= " <a href=\"".groupURL($row['guild_id'])."/discussions/".$row['id']."/id/page/".$pageat."\" class=\"topiclist-page-link secondary\">".$pageat."</a>";
			$pageat++;
		}
	}

	printf("<li class=\"%s\" >
	<a href=\"".groupURL($row['guild_id'])."/discussions/%s/id\" class=\"topic\">
		<span>%s</span>

	</a>
	<div class=\"topic-info post-icon\">
		<span class=\"grey\">(</span>
			 %s
		 <span class=\"grey\">)</span>
	 </div>
</li>", $even, $row['id'], $input->HoloText($row['subject']), $pagelink);
}
?>

</ul>
<div id="active-discussions-toplist-hidden-h121" style="display: none">
    <ul class="active-discussions-toplist">
<?php
$i = 0;
$rows2 = $db->fetchAll("SELECT id, guild_id, subject, posts_count FROM guilds_forums_threads ORDER BY updated_at DESC LIMIT 40 OFFSET 10");
foreach($rows2 as $row) {
	$i++;
	if($input->IsEven($i)){
		$even = "even";
	} else {
		$even = "odd";
	}
	$posts = (int) $row['posts_count'];
	$pages = ceil($posts / 10);
	$pagelink = "<a href=\"".groupURL($row['guild_id'])."/discussions/".$row['id']."/id/page/1\" class=\"topiclist-page-link secondary\">1</a>";
	if($pages > 4){
		$pageat = $pages - 2;
		$pagelink .= "\n...";
		while($pageat <= $pages){
			$pagelink .= " <a href=\"".groupURL($row['guild_id'])."/discussions/".$row['id']."/id/page/".$pageat."\" class=\"topiclist-page-link secondary\">".$pageat."</a>";
			$pageat++;
		}
	}elseif($pages != 1){
		$pageat = 2;
		while($pageat <= $pages){
			$pagelink .= " <a href=\"".groupURL($row['guild_id'])."/discussions/".$row['id']."/id/page/".$pageat."\" class=\"topiclist-page-link secondary\">".$pageat."</a>";
			$pageat++;
		}
	}

	printf("<li class=\"%s\" >
	<a href=\"".groupURL($row['guild_id'])."/discussions/%s/id\" class=\"topic\">
		<span>%s</span>

	</a>
	<div class=\"topic-info post-icon\">
		<span class=\"grey\">(</span>
			 %s
		 <span class=\"grey\">)</span>
	 </div>
</li>", $even, $row['id'], $input->HoloText($row['subject']), $pagelink);
}
?>

</ul>

</div>
<div class="clearfix">
    <a href="#" class="discussions-toggle-more-data secondary" id="discussions-toggle-more-data-h121"><?php echo $lang->loc['show.more.discussions']; ?></a>
</div>
<script type="text/javascript">
L10N.put("show.more.discussions", "<?php echo $lang->loc['show.more.discussions']; ?>");
L10N.put("show.less.discussions", "<?php echo $lang->loc['show.less.discussions']; ?>");
var discussionMoreDataHelper = new MoreDataHelper("discussions-toggle-more-data-h121", "active-discussions-toplist-hidden-h121","discussions");
</script>
    </div>

					</div>
				</div>
				<script type="text/javascript">if (!$(document.body).hasClassName('process-template')) { Rounder.init(); }</script>
				
				<div class="habblet-container ">		
						<div class="cbb clearfix activehomes ">
	
							<h2 class="title"><?php echo $lang->loc['random.habbos']; ?>
							</h2>
						<div id="homes-habblet-list-container" class="habblet-list-container">
	<img class="active-habbo-imagemap" src="<?php echo PATH; ?>/web-gallery/v2/images/activehomes/transparent_area.gif" width="435px" height="230px" usemap="#habbomap" />

<?php
$i = 0;
// select3 returns random users
$rows = $db->fetchAll("SELECT id, username, account_day_of_birth, motto, look FROM users ORDER BY RAND() LIMIT 18");
foreach($rows as $row) {
$i++;
$list_id = $i - 1;

if($user->IsUserOnline($row['id']) == true){
	$status = "online";
} else {
	$status = "offline";
}

printf("        <div id=\"active-habbo-data-%s\" class=\"active-habbo-data\">
                    <div class=\"active-habbo-data-container\">
                        <div class=\"active-name %s\">%s</div>
                        ".$lang->loc['habbo.created.on'].": %s
                            <p class=\"moto\">%s</p>
                    </div>
                </div>
                <input type=\"hidden\" id=\"active-habbo-url-%s\" value=\"".PATH."/home/%s\"/>
                <input type=\"hidden\" id=\"active-habbo-image-%s\" class=\"active-habbo-image\" value=\"".$user->avatarURL($row['look'],"b,4,4,sml,1,0")."\n\" />", $list_id, $status, $row['username'], $row['account_day_of_birth'], $input->HoloText($row['motto']), $list_id, $row['username'], $list_id);
}
?>



            <div id="placeholder-container">
                    <div id="active-habbo-image-placeholder-0" class="active-habbo-image-placeholder"></div>
                    <div id="active-habbo-image-placeholder-1" class="active-habbo-image-placeholder"></div>
                    <div id="active-habbo-image-placeholder-2" class="active-habbo-image-placeholder"></div>
                    <div id="active-habbo-image-placeholder-3" class="active-habbo-image-placeholder"></div>
                    <div id="active-habbo-image-placeholder-4" class="active-habbo-image-placeholder"></div>
                    <div id="active-habbo-image-placeholder-5" class="active-habbo-image-placeholder"></div>
                    <div id="active-habbo-image-placeholder-6" class="active-habbo-image-placeholder"></div>
                    <div id="active-habbo-image-placeholder-7" class="active-habbo-image-placeholder"></div>
                    <div id="active-habbo-image-placeholder-8" class="active-habbo-image-placeholder"></div>
                    <div id="active-habbo-image-placeholder-9" class="active-habbo-image-placeholder"></div>
                    <div id="active-habbo-image-placeholder-10" class="active-habbo-image-placeholder"></div>
                    <div id="active-habbo-image-placeholder-11" class="active-habbo-image-placeholder"></div>
                    <div id="active-habbo-image-placeholder-12" class="active-habbo-image-placeholder"></div>
                    <div id="active-habbo-image-placeholder-13" class="active-habbo-image-placeholder"></div>
                    <div id="active-habbo-image-placeholder-14" class="active-habbo-image-placeholder"></div>
                    <div id="active-habbo-image-placeholder-15" class="active-habbo-image-placeholder"></div>
                    <div id="active-habbo-image-placeholder-16" class="active-habbo-image-placeholder"></div>
                    <div id="active-habbo-image-placeholder-17" class="active-habbo-image-placeholder"></div>
            </div>
    </div>

    <map id="habbomap" name="habbomap">
            <area id="imagemap-area-0" shape="rect" coords="55,53,95,103" href="#" alt=""/>
            <area id="imagemap-area-1" shape="rect" coords="120,53,160,103" href="#" alt=""/>
            <area id="imagemap-area-2" shape="rect" coords="185,53,225,103" href="#" alt=""/>
            <area id="imagemap-area-3" shape="rect" coords="250,53,290,103" href="#" alt=""/>
            <area id="imagemap-area-4" shape="rect" coords="315,53,355,103" href="#" alt=""/>
            <area id="imagemap-area-5" shape="rect" coords="380,53,420,103" href="#" alt=""/>
            <area id="imagemap-area-6" shape="rect" coords="28,103,68,153" href="#" alt=""/>
            <area id="imagemap-area-7" shape="rect" coords="93,103,133,153" href="#" alt=""/>
            <area id="imagemap-area-8" shape="rect" coords="158,103,198,153" href="#" alt=""/>
            <area id="imagemap-area-9" shape="rect" coords="223,103,263,153" href="#" alt=""/>
            <area id="imagemap-area-10" shape="rect" coords="288,103,328,153" href="#" alt=""/>
            <area id="imagemap-area-11" shape="rect" coords="353,103,393,153" href="#" alt=""/>
            <area id="imagemap-area-12" shape="rect" coords="55,153,95,203" href="#" alt=""/>
            <area id="imagemap-area-13" shape="rect" coords="120,153,160,203" href="#" alt=""/>
            <area id="imagemap-area-14" shape="rect" coords="185,153,225,203" href="#" alt=""/>
            <area id="imagemap-area-15" shape="rect" coords="250,153,290,203" href="#" alt=""/>
            <area id="imagemap-area-16" shape="rect" coords="315,153,355,203" href="#" alt=""/>
            <area id="imagemap-area-17" shape="rect" coords="380,153,420,203" href="#" alt=""/>
    </map>
<script type="text/javascript">
    var activeHabbosHabblet = new ActiveHabbosHabblet();
    document.observe("dom:loaded", function() { activeHabbosHabblet.generateRandomImages(); });
</script>


					</div>
				</div>
				<script type="text/javascript">if (!$(document.body).hasClassName('process-template')) { Rounder.init(); }</script>

</div>
<div id="column2" class="column">

<?php $rows = $db->fetchAll("SELECT id, title, text, button_text, button_type, button_link, image FROM hotelview_news ORDER BY id DESC LIMIT 5");
$i = 0;
$news = [];
foreach ($rows as $row) {
    $row['summary'] = nl2br($input->HoloText($row['text'], true));
    $row['title'] = $input->HoloText($row['title'], true);
    $row['title_safe'] = $input->stringToURL($row['title'], true, true);
    $row['header_image'] = $input->HoloText($row['image'], true);
    $row['date'] = '';
    $news[$i++] = $row;
}
while (count($news) < 5) {
    $news[] = ['id' => 0, 'title' => '', 'title_safe' => '', 'summary' => '', 'header_image' => '', 'date' => ''];
}
$lang->addLocale("widget.news"); ?>
				<div class="habblet-container news-promo">
						<div class="cbb clearfix notitle ">
						<div id="newspromo">
        <div id="topstories">
	        <div class="topstory" style="background-image: url(<?php echo $news[0]['header_image']; ?>)">
	            <h4><?php echo $lang->loc['latest.news']; ?></a></h4>
	            <h3><a href="<?php echo PATH."/articles/".$news[0]['id']."-".$news[0]['title_safe']; ?>"><?php echo $news[0]['title']; ?></a></h3>
	            <p class="summary">
	            <?php echo $news[0]['summary']; ?>
	            </p>
	            <p>
	                <a href="<?php echo PATH."/articles/".$news[0]['id']."-".$news[0]['title_safe']; ?>"><?php echo $lang->loc['read.more']; ?></a>
	            </p>
	        </div>
	        <div class="topstory" style="background-image: url(<?php echo $news[1]['header_image']; ?>); display: none">
	            <h4><?php echo $lang->loc['latest.news']; ?></a></h4>
	            <h3><a href="<?php echo PATH."/articles/".$news[1]['id']."-".$news[1]['title_safe']; ?>"><?php echo $news[1]['title']; ?></a></h3>
	            <p class="summary">
	            <?php echo $news[1]['summary']; ?>
	            </p>
	            <p>
	                <a href="<?php echo PATH."/articles/".$news[1]['id']."-".$news[1]['title_safe']; ?>"><?php echo $lang->loc['read.more']; ?></a>
	            </p>
	        </div>
	        <div class="topstory" style="background-image: url(<?php echo $news[2]['header_image']; ?>); display: none">
	            <h4><?php echo $lang->loc['latest.news']; ?></a></h4>
	            <h3><a href="<?php echo PATH."/articles/".$news[2]['id']."-".$news[2]['title_safe']; ?>"><?php echo $news[2]['title']; ?></a></h3>
	            <p class="summary">
	            <?php echo $news[2]['summary']; ?>
	            </p>
	            <p>
	                <a href="<?php echo PATH."/articles/".$news[2]['id']."-".$news[2]['title_safe']; ?>"><?php echo $lang->loc['read.more']; ?></a>
	            </p>
	        </div>
            <div id="topstories-nav" style="display: none"><a href="#" class="prev"><?php echo $lang->loc['news.previous']; ?></a><span>1</span> / 3<a href="#" class="next"><?php echo $lang->loc['news.next']; ?></a></div>
        </div>
        <ul class="widelist">
            <li class="even">
                <a href="<?php echo PATH."/articles/".$news[3]['id']."-".$news[3]['title_safe']; ?>"><?php echo $news[3]['title']; ?></a><div class="newsitem-date"><?php echo $news[3]['date']; ?></div>
            </li>
            <li class="odd">
                <a href="<?php echo PATH."/articles/".$news[4]['id']."-".$news[4]['title_safe']; ?>"><?php echo $news[4]['title']; ?></a><div class="newsitem-date"><?php echo $news[3]['date']; ?></div>
            </li>
            <li class="last"><a href="<?php echo PATH; ?>/articles"><?php echo $lang->loc['news.more']; ?></a></li>
        </ul>
</div>
<script type="text/javascript">
	document.observe("dom:loaded", function() { NewsPromo.init(); });
</script>
					</div>

				</div>
				<script type="text/javascript">if (!$(document.body).hasClassName('process-template')) { Rounder.init(); }</script>

				<div class="habblet-container ">		
						<div class="cbb clearfix green ">
	
							<h2 class="title"><?php echo $lang->loc['tags']; ?>
							</h2>
						<div class="habblet box-content">

<?php
$lang->addLocale("ajax.tags");
$rows = []; // Polaris stores room tags as a denormalized rooms.tags field; no tag cloud query is available.
if(count($rows) < 1){ echo $lang->loc['no.tags']; }else{
echo "	    <ul class=\"tag-list\">";
	$tag_arr = [];
	foreach($rows as $row){
		$tag_arr[$row['tag']] = $row['quantity'];
	}
	ksort($tag_arr);
		
	$max_qty = max($tag_arr);
	$min_qty = min($tag_arr);
	$spread = $max_qty - $min_qty;

	if($spread == 0){ $spread = 1; }

	$step = (200 - 100)/($spread);

	foreach($tag_arr as $key => $value){
	    $size = 100 + (($value - $min_qty) * $step);
	    $size = ceil($size);
	    echo "<li><a href=\"".PATH."/tag/".strtolower($input->HoloText($key))."\" class=\"tag\" style=\"font-size:".$size."%\">".trim(strtolower($key))."</a> </li>\n";
	}

echo "</ul>";
} ?>

    <div class="tag-search-form">
<form name="tag_search_form" action="<?php echo PATH; ?>/tag/search" class="search-box">

    <input type="text" name="tag" id="search_query" value="" class="search-box-query" style="float: left"/>
	<a onclick="$(this).up('form').submit(); return false;" href="#" class="new-button search-icon" style="float: left"><b><span></span></b><i></i></a>	
</form>    </div>
</div>
	
						
					</div>
				</div>
				<script type="text/javascript">if (!$(document.body).hasClassName('process-template')) { Rounder.init(); }</script>

</div>

<?php require_once('./templates/community_footer.php'); ?>