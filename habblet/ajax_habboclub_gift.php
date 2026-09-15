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
$lang->addLocale('club.gifts');
$features = phpretroWebRestorations();
$month = habbletInt($_POST, 'month') ?: habbletInt($_GET, 'month') ?: (int) date('n');
$gift = $features->clubGift($month);
?>
<div id="hc-gift-catalog">
<?php if (!$gift) { ?>
<p>No website gift listed for this month. Claim club gifts in the hotel client.</p>
<?php } else { ?>
<p><?php echo $input->HoloText($gift['name']); ?></p>
<?php if ($gift['image'] !== '') { ?><p><img src="<?php echo $input->HoloText($gift['image']); ?>" alt="" /></p><?php } ?>
<p><?php echo nl2br($input->HoloText($gift['description'])); ?></p>
<p>Preview only. PolarIS club gifts are claimed in the hotel client.</p>
<?php } ?>
</div>
