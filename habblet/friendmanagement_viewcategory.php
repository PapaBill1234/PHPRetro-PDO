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
$pagenum = max(1, min(100000, habbletInt($_GET, 'pageNumber', 1)));
$pagesize = habbletInt($_GET, 'pageSize', 30);
if (!in_array($pagesize, [30, 50, 100], true)) { $pagesize = 30; }
$search = habbletText($_POST, 'searchString');
$lang->addLocale("friendmanagement");

$friendcount = (int) $db->fetchColumn('SELECT COUNT(DISTINCT u.id) FROM messenger_friendships f JOIN users u ON u.id = f.user_two_id WHERE f.user_one_id = ? AND u.username LIKE ?', [(int) $user->id, '%'.$search.'%']);
$pagenum = min($pagenum, max(1, (int) ceil($friendcount / $pagesize)));
if($friendcount < 1){ echo "<p class=\"last\" style=\"padding-top: 11px\">".$lang->loc['no.friends']."</p>"; }else{
?>
                        <div id="friend-list" class="clearfix">
<div id="friend-list-header-container" class="clearfix">
    <div id="friend-list-header">
        <div class="page-limit">
            <div class="big-icons friend-header-icon"><?php echo $lang->loc['friends']; ?>
                <br /><?php echo $lang->loc['show']; ?>

           		<?php if($pagesize == 30){ ?>
                30 |
                <a class="category-limit" id="pagelimit-50">50</a> |
                <a class="category-limit" id="pagelimit-100">100</a>
                <?php }elseif($pagesize == 50){ ?>
                <a class="category-limit" id="pagelimit-30">30</a> |
				50 |
                <a class="category-limit" id="pagelimit-100">100</a>
                <?php }elseif($pagesize == 100){ ?>
                <a class="category-limit" id="pagelimit-30">30</a> |
				<a class="category-limit" id="pagelimit-50">50</a> |
                100
                <?php } ?>
            </div>
        </div>
    </div>
	<div id="friend-list-paging">
	<?php
	if($friendcount > $pagesize){
		if($pagenum <> 1){
		$pageminus = $pagenum - 1;
		echo "<a href=\"#\" class=\"friend-list-page\" id=\"page-".$pageminus."\">&lt;&lt;</a> |";
		}

		$pages = ceil($friendcount / $pagesize);

		if($pages == 1){

		echo "1";

		}else{

		$n = 0;

		while ($n < $pages) {
			$n++;
			if($n == $pagenum){
			echo $n." |";
			} else {
			echo "<a href=\"#\" class=\"friend-list-page\" id=\"page-".$n."\">".$n."</a> |";
			}
		}

		if($pagenum <> $pages){
		$pageplus = $pagenum + 1;
		echo "<a href=\"#\" class=\"friend-list-page\" id=\"page-".$pageplus."\">&gt;&gt;</a>";
		}
		}
	}
	?>

        </div>
    </div>


<form id="friend-list-form">
    <table id="friend-list-table" border="0" cellpadding="0" cellspacing="0">
        <thead>
            <tr class="friend-list-header">
                <th class="friend-select" />
                <th class="friend-name">

                    <a class="sort"><?php echo $lang->loc['name']; ?></a>
                </th>
                <th class="friend-login">
                    <a class="sort"><?php echo $lang->loc['last.logon']; ?></a>
                </th>
                <th class="friend-remove"><?php echo $lang->loc['remove']; ?></th>
            </tr>

        </thead>
        <tbody>
           <?php
		   $i = 0;
		   $offset = $pagesize * $pagenum;
		   $offset = $offset - $pagesize;
		   $rows = $db->fetchAll('SELECT DISTINCT u.id, u.username, u.last_online FROM messenger_friendships f JOIN users u ON u.id = f.user_two_id WHERE f.user_one_id = ? AND u.username LIKE ? ORDER BY u.username, u.id LIMIT ? OFFSET ?', [(int) $user->id, '%'.$search.'%', $pagesize, $offset]);
		   foreach ($rows as $row) {
		           $i++;

		           if($input->IsEven($i)){
		               $even = "even";
		           } else {
		               $even = "odd";
		           }

printf("   <tr class=\"%s\">
               <td><input type=\"checkbox\" name=\"friendList[]\" value=\"%s\" /></td>
               <td class=\"friend-name\">
                %s
               </td>
               <td class=\"friend-login\" title=\"%s\">%s</td>
               <td class=\"friend-remove\"><div id=\"remove-friend-button-%s\" class=\"friendmanagement-small-icons friendmanagement-remove remove-friend\"></div></td>
           </tr>\n", $even, $row['id'], $input->HoloText($row['username']), date('n/j/y g:i A',(int) $row['last_online']), date('n/j/y g:i A',(int) $row['last_online']), $row['id']);
		   }
		?>
        </tbody>
    </table>
    <a class="select-all" id="friends-select-all" href="#"><?php echo $lang->loc['select.all']; ?></a> |
    <a class="deselect-all" href=#" id="friends-deselect-all"><?php echo $lang->loc['deselect.all']; ?></a>
</form>

<div id="category-options" class="clearfix">
<?php /* <select id="category-list-select" name="category-list">
    <option value="0">Friends</option>
</select> */ ?>
<div class="friend-del"><a class="new-button red-button cancel-icon" href="#" id="delete-friends"><b><span></span><?php echo $lang->loc['delete.selected.friends']; ?></b><i></i></a></div>
<?php /* <div class="friend-move"><a class="new-button" href="#" id="move-friend-button"><b><span></span>Move</b><i></i></a></div></div> */ ?>
<?php } ?>
