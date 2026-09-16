<?php
// FILE: includes/functions.php
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

if(!defined("IN_HOLOCMS")) { header("Location: ".PATH); exit; }

function HoloDate(){
	$date = array();
	$date['H'] = date('H');
	$date['i'] = date('i');
	$date['s'] = date('s');
	$date['m'] = date('m');
	$date['d'] = date('d');
	$date['Y'] = date('Y');
	$date['y'] = date('y');
	$date['j'] = date('j');
	$date['n'] = date('n');
	$date['today'] = $date['d'];
	$date['month'] = $date['m'];
	$date['year'] = $date['Y'];
	$date['date_normal'] = date('d-m-Y');
	$date['date_reversed'] = date('Y-m-d');
	$date['date_full'] = date('d-m-Y H:i:s');
	$date['date_time'] = date('H:i:s');
	$date['date_hc'] = $date['j']."-".$date['n']."-".$date['Y'];
	$date['regdate'] = $date['date_normal'];
	return $date;
}
function HoloText($str, $advanced=false){
	if (isset($GLOBALS['input']) && is_object($GLOBALS['input']) && method_exists($GLOBALS['input'], 'HoloText')) {
		return $GLOBALS['input']->HoloText($str, $advanced);
	}
	$str = (string) $str;
	return $advanced ? $str : htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
function HoloUrl($url): string {
	$url = trim((string) $url);
	if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) { return ''; }
	$parts = parse_url($url);
	if ($parts === false || (isset($parts['scheme']) && !in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true))) { return ''; }
	if (!isset($parts['scheme']) && !str_starts_with($url, '/') && !preg_match('/^[A-Za-z0-9._-]+(?:\/[A-Za-z0-9._-]+)*$/', $url)) { return ''; }
	return htmlspecialchars(str_replace(['(', ')'], ['%28', '%29'], $url), ENT_QUOTES, 'UTF-8');
}
function HoloJson($value): string {
	return json_encode((string) $value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?: '""';
}
function HoloOptionalWebGalleryTag($relativeFile, $type){
	$relativeFile = ltrim(str_replace('\\', '/', (string) $relativeFile), '/');
	if ($relativeFile === '' || str_contains($relativeFile, '..') || !str_starts_with($relativeFile, 'web-gallery/')) {
		return '';
	}
	$disk = getcwd() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeFile);
	if (!is_file($disk)) {
		return '';
	}
	$url = rtrim((string) PATH, '/') . '/' . $relativeFile;
	$safe = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
	if ($type === 'css') {
		return '<link rel="stylesheet" href="'.$safe.'" type="text/css" />'."\n";
	}
	if ($type === 'js') {
		return '<script src="'.$safe.'" type="text/javascript"></script>'."\n";
	}
	return '';
}
function GenerateTicket($type = "sso",$length = 0){
switch($type){
case "sso":
	$data = GenerateTicket("random",8)."-".GenerateTicket("random",4)."-".GenerateTicket("random",4)."-".GenerateTicket("random",4)."-".GenerateTicket("random",12);
	return $data;
case "remember":
	$data = GenerateTicket("random",6)."-".bin2hex(random_bytes(10))."-".bin2hex(random_bytes(10));
	return $data;
case "random":
	if($length < 1) return '';
	$bytes = ceil($length / 2);
	$hex = bin2hex(random_bytes($bytes));
	return substr($hex, 0, $length);
default:
	return '';
}
}
function SendMUSData($data){
$ip = $GLOBALS['settings']->find("hotel_ip");
$port = $GLOBALS['settings']->find("hotel_mus");

if(!is_numeric($port)){ return false; }

$sock = socket_create(AF_INET, SOCK_STREAM, getprotobyname('tcp'));
socket_connect($sock, $ip, $port);

	if(!is_resource($sock)){
		return false;
	} else {
		socket_send($sock, $data, strlen($data), MSG_DONTROUTE);
		return true;
	}
	
socket_close($sock);
}
function GetOnlineCount(){
	$db = new Database();
	return $db->fetchColumn("SELECT COUNT(*) FROM users WHERE online > ?", [time() - 300]); // approximate
}
function HotelStatus(){
	$status = array('online' => 'online', 'check' => 0, 'bypass' => false);
	if($GLOBALS['settings']->find("site_status_image") == 2){
		$store = CacheFactory::instance();
		$cached = $store->get('hotel:status');
		if (is_array($cached)) {
			$status = array_merge($status, $cached);
		}
		if((($status['check'] + (60*30)) < time()) || ($status['bypass'] ?? false) === true){
			$fp = @fsockopen($GLOBALS['settings']->find("hotel_ip"), $GLOBALS['settings']->find("hotel_mus"), $errno, $errstr, 1);
			if($fp){
				$status['online'] = "online";
				@fclose($fp);
			} else {
				$status['online'] = "offline";
			}
			$status['check'] = time();
			$store->set('hotel:status', $status, 60 * 30);
		}
	}elseif($GLOBALS['settings']->find("site_status_image") == 1){
		$fp = @fsockopen($GLOBALS['settings']->find("hotel_ip"), $GLOBALS['settings']->find("hotel_mus"), $errno, $errstr, 1);
		if($fp){
			$status['online'] = "online";
			fclose($fp);
		} else {
			$status['online'] = "offline";
		}
	}else{
		$status['online'] = "online";
	}
	return $status['online'];
}
function formatItem($type,$data,$pre){
	$str = "";

	switch($type){
		case 1: $str = $str . "s_"; break;
		case 2: $str = $str . "w_"; break;
		case 3: $str = $str . "commodity_"; break; // =S
		case 4: $str = $str . "b_"; break;
	}

	$str = $str . $data;

	if($pre == true){ $str = $str . "_pre"; }

	return $str;
}
function groupURL($id){
	require_once __DIR__.'/PhpretroGroupUrls.php';
	return phpretroGroupPath((int) $id);
}
?>
