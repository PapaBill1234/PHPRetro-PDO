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
$lang->addLocale("register.ajax.errors");

if (!isset($db) || !($db instanceof Database)) {
	$db = new Database();
}

function namecheckHeader(array $payload): void {
	header('X-JSON: '.json_encode($payload, JSON_UNESCAPED_UNICODE));
}

$name = habbletText($_POST, 'name');
$filter = preg_replace("/[^a-z\d\-=\?!@:\.]/i", "", $name);

if($name === ''){
	namecheckHeader(["registration_name" => $lang->loc['ajax.error.5']]);
} elseif($filter != $name){
	namecheckHeader(["registration_name" => $lang->loc['ajax.error.3']]);
} elseif(strlen($name) > 24){
	namecheckHeader(["registration_name" => $lang->loc['ajax.error.4']]);
} elseif(strnatcasecmp(substr($name, 0, 4), "MOD-") === 0){
	namecheckHeader(["registration_name" => $lang->loc['ajax.error.5']]);
} elseif($db->fetchColumn('SELECT COUNT(*) FROM users WHERE username = ?', [$name]) > 0){
	namecheckHeader(["registration_name" => $lang->loc['ajax.error.2']]);
} else {
	header('X-JSON: {}');
}

?>
