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
Csrf::protectPost();
habbletRequireUser();
require_once(__DIR__.'/../includes/PhpretroWebRestorations.php');
$lang->addLocale('ajax.buttons');
$result = phpretroWebRun(static fn() => phpretroWebRestorations()->purchaseCollectible());
if ($result === null) { return; }
$label = $result['duplicate'] ? 'Already claimed this month.' : 'Claim recorded. Delivery waits for a PolarIS worker.';
?>
<p><?php echo $input->HoloText($result['item']['name']); ?></p>
<p><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></p>
<p><a href="#" class="new-button" id="collectibles-close"><b><?php echo $lang->loc['ok'] ?? 'Done'; ?></b><i></i></a></p>
