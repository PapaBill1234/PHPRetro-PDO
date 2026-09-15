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

define("IN_HOLOCMS", TRUE);
$page = $page ?? array();
$page += array('dir' => '', 'no_ajax' => false, 'bypass_user_check' => false, 'housekeeping' => false, 'id' => '', 'new_landing' => false, 'discussion' => false, 'no_column3' => false, 'allow_guests' => false, 'rank' => '', 'name' => '', 'bodyid' => '', 'type' => '', 'cat' => '', 'category' => '');

if(strpos($_SERVER['SERVER_SOFTWARE'],"Win") == false){ $page['dir'] = str_replace('\\','/',$page['dir']); }
chdir(str_replace($page['dir'], "", getcwd()));

if(@ini_get('date.timezone') == null && function_exists("date_default_timezone_get")){ @date_default_timezone_set("America/Los_Angeles"); }

if(strpos($page['dir'],'habblet') && (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') && $page['no_ajax'] != true){ header('Location: ../'); exit; }

if(!file_exists('./.env')){ header('Location: ./install/'); exit; }
require_once('./includes/config.php');
define("PREFIX", 'phpretro_');
require_once('./includes/classes.php');
$db = new Database();
$serverdb = $db;
$settings = new HoloSettings;
$input = new HoloInput;
$lang = new HoloLocale;

session_start();

require_once('./includes/Csrf.php');
Csrf::boot();

define("PATH", $settings->find("site_path"));
define("SHORTNAME", $settings->find("site_shortname"));
define("FULLNAME", $settings->find("site_name"));
//define("DEBUG", true); //Uncomment this line to show detailed database error messages.

require('./includes/data/holograph.php');
$core = new core_sql;
require('./includes/functions.php');
require('./includes/version.php');

if($page['housekeeping'] != true){ if(is_object($_SESSION['user'] ?? null)){ $user = $_SESSION['user']; }else{ $user = new HoloUser(null,null); } }else{ if(is_object($_SESSION['hk_user'] ?? null)){ $user = $_SESSION['hk_user']; }else{ $user = new HoloUser(null,null); } }

if($page['housekeeping'] == true && isset($_SESSION['hk_user']) && is_object($_SESSION['hk_user'])) {
    try {
        $staffSession = (new Database())->fetchRow('SELECT id FROM phpretro_staff_sessions WHERE user_id = ? AND session_hash = ? AND revoked_at IS NULL', [(int) $_SESSION['hk_user']->id, hash('sha256', session_id())]);
        if ($staffSession === false) { unset($_SESSION['hk_user']); session_destroy(); header('Location: '.PATH.'/housekeeping/'); exit; }
        (new Database())->execute('UPDATE phpretro_staff_sessions SET last_activity = ? WHERE id = ?', [time(), (int) $staffSession['id']]);
    } catch (Throwable $exception) { /* TODO: fail closed after migrations are mandatory; this compatibility path currently permits the request. */ }
}

if($user->error == 1 && $page['bypass_user_check'] != true && ($_COOKIE['rememberme'] ?? '') == "true" && $page['housekeeping'] != true){ $_SESSION['page'] = $_SERVER["REQUEST_URI"]; header("Location: ".PATH."/security_check_token"); }

$phase5bMaintenance = $settings->find('maintenance_mode') === '1';
if(($settings->find("site_closed") == "1" || $phase5bMaintenance) && $page['id'] != "maintenance" && $page['housekeeping'] != true && $user->user("rank") < 5){
	header("Location: ".PATH."/maintenance"); exit;
}
?>
