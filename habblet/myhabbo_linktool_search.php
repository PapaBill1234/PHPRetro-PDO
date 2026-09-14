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
$lang->addLocale('linktool');
$query = habbletText($_GET, 'query');
$scope = habbletInt($_GET, 'scope');
$type = [1 => 'habbo', 2 => 'room', 3 => 'group'][$scope] ?? '';
$rows = [];
if ($type !== '' && $query !== '') {
    $rows = match ($scope) {
        1 => $db->fetchAll('SELECT id, username AS name FROM users WHERE LOCATE(?, username) > 0 ORDER BY username, id LIMIT ?', [$query, 5]),
        2 => $db->fetchAll('SELECT id, name FROM rooms WHERE LOCATE(?, name) > 0 ORDER BY name, id LIMIT ?', [$query, 5]),
        3 => $db->fetchAll('SELECT id, name FROM guilds WHERE LOCATE(?, name) > 0 ORDER BY name, id LIMIT ?', [$query, 5]),
    };
}
?>
<ul>
	<li><?php echo $lang->loc['linktool.add']; ?></li>
<?php foreach ($rows as $row) { ?>
    <li><a href="#" class="linktool-result" type="<?php echo $type; ?>" value="<?php echo (int) $row['id']; ?>" title="<?php echo $input->HoloText($row['name']); ?>"><?php echo $input->HoloText($row['name']); ?></a></li>
<?php } ?>
</ul>
