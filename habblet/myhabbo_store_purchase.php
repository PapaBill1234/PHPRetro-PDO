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

require_once __DIR__.'/../includes/habblet.php';
habbletRequireUser();
require_once __DIR__.'/../includes/PhpretroHomes.php';
$homes = phpretroHomes();
$lang->addLocale('homes.store.purchase');
$lang->addLocale('ajax.buttons');
try {
    $id = $homes->purchase(habbletInt($_POST, 'selectedId'));
} catch (PhpretroHomesError $error) {
    http_response_code($error->getCode() ?: 400);
?>
<p>
<?php echo htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8'); ?><br />
</p>
<p>
<a href="#" class="new-button" id="webstore-confirm-cancel"><b><?php echo $lang->loc['cancel'] ?? 'Cancel'; ?></b><i></i></a>
</p>
<div class="clear"></div>
<?php
    return;
}
phpretroHomesJsonHeader($id);
echo 'OK';
