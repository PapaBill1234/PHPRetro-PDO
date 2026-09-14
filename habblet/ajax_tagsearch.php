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
habbletRequireUser();
$search = habbletText($_POST, 'tag', habbletText($_GET, 'tag'));
$pagenum = max(1, min(100000, habbletInt($_POST, 'pageNumber', habbletInt($_GET, 'pageNumber', 1))));
$count = habbletTagCount($db, $search);
$pages = max(1, (int) ceil($count / 10));
$pagenum = min($pagenum, $pages);
$rows = $count === 0 ? [] : $db->fetchAll("SELECT u.id, u.username, u.look, u.motto FROM users_settings s JOIN users u ON u.id = s.user_id WHERE LOCATE(CONCAT(';', ?, ';'), CONCAT(';', s.tags, ';')) > 0 ORDER BY u.id DESC LIMIT ? OFFSET ?", [$search, 10, ($pagenum - 1) * 10]);
?>
<div id="tag-search-habblet-container"><form action="<?php echo PATH; ?>/tag/search" class="search-box">
<input type="text" name="tag" id="search_query" value="<?php echo $input->HoloText($search); ?>" /><button type="submit">Search</button></form>
<p class="search-result-count"><?php echo $count; ?> users. Group tags are unavailable.</p>
<table class="search-result"><tbody>
<?php foreach ($rows as $row) { ?>
<tr><td><img src="<?php echo $user->avatarURL($row['look'], 's,4,4,sml,1,0'); ?>" alt="" /></td>
<td><a class="result-title" href="<?php echo PATH.'/home/'.rawurlencode($row['username']); ?>"><?php echo $input->HoloText($row['username']); ?></a><br />
<span class="result-description"><?php echo $input->HoloText($row['motto']); ?></span></td></tr>
<?php } ?></tbody></table><p class="search-result-navigation">
<?php for ($n = max(1, $pagenum - 4); $n <= min($pages, $pagenum + 4); $n++) { ?>
<a href="<?php echo PATH.'/tag/'.rawurlencode($search).'?pageNumber='.$n; ?>"><?php echo $n; ?></a>
<?php } ?></p></div>
