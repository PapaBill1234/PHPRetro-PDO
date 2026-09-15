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
$privacy = phpretroHomesRun(static fn() => $homes->configureGuestbook(habbletInt($_POST, 'widgetId')));
if ($privacy === null) { return; }
header('Content-Type: text/javascript; charset=utf-8');
?>
var el = $("guestbook-type");
if (el) {
	if (el.hasClassName("public")) {
		el.className = "private";
		new Effect.Pulsate(el,
			{ duration: 1.0, afterFinish : function() { Element.setOpacity(el, 1); } }
		);
	} else {
		new Effect.Pulsate(el,
			{ duration: 1.0, afterFinish : function() { Element.setOpacity(el, 0); el.className = "public"; } }
		);
	}
}
