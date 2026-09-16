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
$lang->addLocale('redeem.voucher');
header('X-PHPRetro-Feature: client-handoff');
$credits = (int) $db->fetchColumn('SELECT credits FROM users WHERE id = ?', [(int) $user->id]);
$code = habbletText($_POST, 'voucherCode', habbletText($_GET, 'voucherCode'));
?>
<ul>
    <li class="even icon-purse">
        <div><?php echo $lang->loc['you.have']; ?>:</div>
        <span class="purse-balance-amount"><?php echo $credits.' '.$lang->loc['coins']; ?></span>
        <div class="purse-tx"><a href="<?php echo PATH; ?>/credits/history"><?php echo $lang->loc['transactions']; ?></a></div>
    </li>
    <li class="odd">
        <div class="box-content">
            <div><?php echo $lang->loc['enter.voucher']; ?>:</div>
            <input type="text" name="voucherCode" value="<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>" id="purse-habblet-redeemcode-string" class="redeemcode" />
            <a href="#" id="purse-redeemcode-button" class="new-button purse-icon" style="float:left"><b><span></span><?php echo $lang->loc['redeem']; ?></b><i></i></a>
        </div>
    </li>
</ul>
<div id="purse-redeem-result">
<div class="habblet-client-handoff">
<p><b>Redeem this voucher in the hotel</b></p>
<p>This website cannot grant credits or catalogue items. Redeem the code in the hotel.</p>
<p><?php echo habbletClientOpenButton(); ?></p>
</div>
</div>
