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

$lang->addLocale("searchhabbos.search");

if(isset($_POST['searchString'])) {
$pageNumber = max(1, min(100000, habbletInt($_POST, 'pageNumber', 1)));
$i = 0;
$search = habbletText($_POST, 'searchString');
$count = (int) $db->fetchColumn('SELECT COUNT(*) FROM users WHERE username LIKE ?', ['%'.$search.'%']);
$pages = ceil($count / 10);
if($pageNumber == null){ $pageNumber = 1; }
$limit = 10;
$offset = $pageNumber - 1;
$offset = $offset * 10;
$rows = $db->fetchAll('SELECT username, look, id, last_online, online FROM users WHERE username LIKE ? ORDER BY username, id LIMIT ? OFFSET ?', ['%'.$search.'%', $limit, $offset]);
if(count($rows) > 0) {
echo '<ul class="habblet-list">';
foreach($rows as $row) {
        $i++;

        if($input->IsEven($i)){
            $even = "odd";
        } else {
            $even = "even";
        }
        if($row['online'] !== '0'){
            $online = "online";
        }else{
            $online = "offline";
        }
        ?>

              <li class="<?php echo $even." ".$online; ?>" homeurl="<?php echo PATH; ?>/home/<?php echo $input->HoloText($row['username']); ?>" style="background-image: url(<?php echo $user->avatarURL($row['look'],"s,2,2,sml,1,0"); ?>)">
                        <div class="item">
                            <b><?php echo $input->HoloText($row['username']); ?></b><br />

                        </div>
                        <div class="lastlogin">
                            <b><?php echo $lang->loc['last.visit']; ?></b><br />
                                <span title="<?php echo date('n/j/y g:i A',$row['last_online']); ?>"><?php echo date('n/j/y g:i A',$row['last_online']); ?></span>
                        </div>
                        <div class="tools">
                                <a href="#" class="add" avatarid="<?php echo $row['id']; ?>" title="<?php echo $lang->loc['send.request']; ?>"></a>
                        </div>
                        <div class="clear"></div>
                    </li>

<?php           } ?>
                                <div id="habblet-paging-avatar-habblet-list-container">
        <p id="avatar-habblet-list-container-list-paging" class="paging-navigation">
                         <?php if($pageNumber > 1) { ?><a href="#" class="avatar-habblet-list-container-list-paging-link" id="avatar-habblet-list-container-list-previous">&laquo;</a><?php } else { ?><span class="disabled">&laquo;</span><?php } ?>
        <?php
        $i = 0;
        $n = $pages;
        while ($i <> $n){
            $i++;
            if ($i < $pageNumber + 8){
                if($i == $pageNumber){ echo "<span class=\"current\">".$i."</span>\n";
                } else {
                    if ($i + 4 >= $pageNumber && $pageNumber + 4 >= $i){
                        echo "<a href=\"#\" class=\"avatar-habblet-list-container-list-paging-link\" id=\"avatar-habblet-list-container-list-page-".$i."\">".$i."</a>\n";
                    }
                }
            }
        }
        ?>
        <?php if($pageNumber < $pages) { ?><a href="#" class="avatar-habblet-list-container-list-paging-link" id="avatar-habblet-list-container-list-next">&raquo;</a><?php }else{ ?><span class="disabled">&raquo;</span><?php } ?>
                    </p>
        <input type="hidden" id="avatar-habblet-list-container-pageNumber" value="<?php echo $pageNumber; ?>"/>
        <input type="hidden" id="avatar-habblet-list-container-totalPages" value="<?php echo $pages; ?>"/>
    </div>
                <?php
            }else{
            echo "<div class=\"box-content\">
                ".$lang->loc['not.found']." <br>
       </div>";
        }
}

?>
