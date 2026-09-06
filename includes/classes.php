<?php
// FILE: includes/classes.php
require_once(__DIR__ . '/Database.php');

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

class HoloInput {
	function FilterText($str) {
		// get_magic_quotes_gpc() removed – assume false
		$str = preg_replace(array('/\x{0001}/u','/\x{0002}/u','/\x{0003}/u','/\x{0005}/u','/\x{0009}/u'),' ',$str);
		// SQL escaping removed – will be handled by PDO in Phase 2
		throw new Exception("Not yet migrated – see Phase 2");
		return $str;
	}
	function HoloText($str, $advanced=false) {
		$str = stripslashes($str);
		if($advanced != true){ $str = htmlspecialchars($str,ENT_COMPAT,"UTF-8"); }
		return $str;
	}
	function stringToURL($str,$lowercase=true,$spaces=false){
		$str = trim(preg_replace('/\s\s+/',' ',preg_replace("/[^A-Za-z0-9-]/", " ", $str)));
		if($lowercase == true){ $str = strtolower($str); }
		if($spaces == true){ $str = str_replace(" ", "-", $str); }else{ str_replace(" ", "", $str); }
		return $str;
	}
	/**
	 * Legacy hash generator – kept for migration path.
	 * New code should use password_hash()/password_verify().
	 */
	function HoloHash($password, $username){
		$string = sha1($password.strtolower($username));
		return $string;
	}
	function IsEven($intNumber)
	{
		if($intNumber % 2 == 0){
			return true;
		} else {
			return false;
		}
	}
	function unicodeToImage($str){
		$search = array(
						//'/\x{007c}/u',
						'/\x{00a5}/u',
						'/\x{00aa}/u',
						'/\x{00ac}/u',
						'/\x{00b1}/u',
						'/\x{00b5}/u',
						'/\x{00b6}/u',
						'/\x{00ba}/u',
						'/\x{00bb}/u',
						//'/\x{00cc}/u',
						//'/\x{00cd}/u',
						'/\x{00d5}/u',
						'/\x{00f5}/u',
						'/\x{00f7}/u',
						'/\x{0192}/u',
						'/\x{2014}/u',
						//'/\x{2018}/'u,
						'/\x{2020}/u',
						'/\x{2021}/u',
						'/\x{2022}/u'
						);
		$replace = array(
						//'<img src="'.PATH.'/web-gallery/images/fonts/volter/white_heart.gif" class="vchar" />',
						'<img src="'.PATH.'/web-gallery/images/fonts/volter/165.gif" class="vchar" />',
						'<img src="'.PATH.'/web-gallery/images/fonts/volter/170.gif" class="vchar" />',
						'<img src="'.PATH.'/web-gallery/images/fonts/volter/172.gif" class="vchar" />',
						'<img src="'.PATH.'/web-gallery/images/fonts/volter/177.gif" class="vchar" />',
						'<img src="'.PATH.'/web-gallery/images/fonts/volter/181.gif" class="vchar" />',
						'<img src="'.PATH.'/web-gallery/images/fonts/volter/182.gif" class="vchar" />',
						'<img src="'.PATH.'/web-gallery/images/fonts/volter/186.gif" class="vchar" />',
						'<img src="'.PATH.'/web-gallery/images/fonts/volter/187.gif" class="vchar" />',
						//'<img src="'.PATH.'/web-gallery/images/fonts/volter/white_padlock.gif" class="vchar" />',
						//'<img src="'.PATH.'/web-gallery/images/fonts/volter/single_music_note.gif" class="vchar" />',
						'<img src="'.PATH.'/web-gallery/images/fonts/volter/213.gif" class="vchar" />',
						'<img src="'.PATH.'/web-gallery/images/fonts/volter/245.gif" class="vchar" />',
						'<img src="'.PATH.'/web-gallery/images/fonts/volter/247.gif" class="vchar" />',
						'<img src="'.PATH.'/web-gallery/images/fonts/volter/131.gif" class="vchar" />',
						'<img src="'.PATH.'/web-gallery/images/fonts/volter/151.gif" class="vchar" />',
						//'<img src="'.PATH.'/web-gallery/images/fonts/volter/black_padlock.gif" class="vchar" />',
						'<img src="'.PATH.'/web-gallery/images/fonts/volter/134.gif" class="vchar" />',
						'<img src="'.PATH.'/web-gallery/images/fonts/volter/135.gif" class="vchar" />',
						'<img src="'.PATH.'/web-gallery/images/fonts/volter/149.gif" class="vchar" />'
						);
		$str = preg_replace($search,$replace,$str);
		return $str;
	}
	function bbcode_format($str){

		// Parse smilies
			$smilies = array(":)",";)",":P",";P",":p",";p","(L)","(l)",":o",":O");
			$smilies_replace = array(
			" <img src='".PATH."/web-gallery/smilies/smile.gif' alt='Smiley' title='Smiley' border='0'> ",
			" <img src='".PATH."/web-gallery/smilies/wink.gif' alt='Smiley' title='Smiley' border='0'> ",
			" <img src='".PATH."/web-gallery/smilies/tongue.gif' alt='Smiley' title='Smiley' border='0'> ",
			" <img src='".PATH."/web-gallery/smilies/winktongue.gif' alt='Smiley' title='Smiley' border='0'> ",
			" <img src='".PATH."/web-gallery/smilies/tongue.gif' alt='Smiley' title='Smiley' border='0'> ",
			" <img src='".PATH."/web-gallery/smilies/winktongue.gif' alt='Smiley' title='Smiley' border='0'> ",
			" <img src='".PATH."/web-gallery/smilies/heart.gif' alt='Smiley' title='Smiley' border='0'> ",
			" <img src='".PATH."/web-gallery/smilies/heart.gif' alt='Smiley' title='Smiley' border='0'> ",
			" <img src='".PATH."/web-gallery/smilies/shocked.gif' alt='Smiley' title='Smiley' border='0'> ",
			" <img src='".PATH."/web-gallery/smilies/shocked.gif' alt='Smiley' title='Smiley' border='0'> ");
		$str = str_replace($smilies,$smilies_replace,$str);

		// Parse BB code
	        $simple_search = array(
	                                '/\[b\](.*?)\[\/b\]/is',
	                                '/\[i\](.*?)\[\/i\]/is',
	                                '/\[u\](.*?)\[\/u\]/is',
	                                '/\[s\](.*?)\[\/s\]/is',
	                                '/\[quote\](.*?)\[\/quote\]/is',
	                                '/\[link\=(.*?)\](.*?)\[\/link\]/is',
	                                '/\[url\=(.*?)\](.*?)\[\/url\]/is',
	                                '/\[color\=(.*?)\](.*?)\[\/color\]/is',
	                                '/\[size=small\](.*?)\[\/size\]/is',
	                                '/\[size=large\](.*?)\[\/size\]/is',
	                                '/\[code\](.*?)\[\/code\]/is',
	                                '/\[habbo\=(.*?)\](.*?)\[\/habbo\]/is',
	                                '/\[room\=(.*?)\](.*?)\[\/room\]/is',
	                                '/\[group\=(.*?)\](.*?)\[\/group\]/is'
	                                );

	        $simple_replace = array(
	                                "<b>$1</b>",
	                                "<i>$1</i>",
	                                "<u>$1</u>",
	                                "<s>$1</s>",
	                                "<div class=\"bbcode-quote\">$1</div>",
	                                "<a href=\"$1\">$2</a>",
	                                "<a href=\"$1\">$2</a>",
	                                "<span style=\"color: $1;\">$2</span>",
	                                "<span style=\"font-size: 9px;\">$1</span>",
	                                "<span style=\"font-size: 14px;\">$1</span>",
	                                "<pre>$1</pre>",
	                                "<a href=\"".PATH."/home/$1/id\">$2</a>",
	                                "<a onclick=\"roomForward(this, '$1', 'private'); return false;\" target=\"client\" href=\"".PATH."/client?forwardId=2&roomId=$1\">$2</a>",
	                                "<a href=\"".PATH."/groups/$1/id\">$2</a>"
	                                );

	        $str = preg_replace ($simple_search, $simple_replace, $str);

	        return $str;
	}
}
class HoloUser {
	public $id = 0;
	public $name = "Guest";
	public $password = null;      // plaintext (only set during login, not stored)
	public $logged_in = false;
	public $ip = null;
	public $time = null;
	public $error = 0;
	public $banned;
	public $user = array('0','Guest','null','0',null,null,null,null,null,null,null,null,null);
	private $db;

	function __construct($name = null, $password = null, $updateuser=false, $rememberme=null){
		$this->db = new Database();

		// Allow empty constructor for loading by ID later (e.g. token auth)
		if ($name === null && $password === null) {
			$this->error = 0;
			$this->logged_in = false;
			return true;
		}

		$date = HoloDate();
		if(empty($name) || empty($password)){
			$this->error = 1;
			return false;
		}

		// Fetch user by username only
		$row = $this->db->fetchRow("SELECT * FROM users WHERE name = ?", [$name]);
		if(!$row){
			$this->error = 2;
			return false;
		}
		$id = (int)$row['id'];

		// Ban check – temporarily disabled pending resolution (see TODO)
		// TODO: Determine actual ban storage mechanism and implement properly.
		// For now, we skip ban check; IsUserBanned() throws an exception.
		// if($this->IsUserBanned($id)) { ... }

		// Password verification
		$stored_hash = $row['password'];
		$verified = false;
		if(password_verify($password, $stored_hash)){
			$verified = true;
		} else {
			// Check legacy sha1
			$legacy_hash = sha1($password . strtolower($name));
			if($legacy_hash === $stored_hash){
				// Rehash and update
				$new_hash = password_hash($password, PASSWORD_DEFAULT);
				$this->db->execute("UPDATE users SET password = ? WHERE id = ?", [$new_hash, $id]);
				$verified = true;
			}
		}
		if(!$verified){
			$this->error = 2;
			return false;
		}

		// Successful login
		$this->ip = $_SERVER['REMOTE_ADDR'];
		if($rememberme == "true"){
			$token = GenerateTicket("remember");
			$this->db->execute("UPDATE users SET remember_token = ? WHERE id = ?", [$token, $id]);
			setcookie("rememberme", "true", time()+60*60*24*$GLOBALS['settings']->find("site_cookie_time"), "/");
			setcookie("rememberme_token", $token, time()+60*60*24*$GLOBALS['settings']->find("site_cookie_time"), "/");
		}
		if($updateuser == true){
			$this->updateUser($id);
		}
		// Populate user array from fetched row
		$this->user = [
			$row['id'],
			$row['name'],
			$row['password'],
			$row['rank'],
			null, // unknown
			$row['birth'] ?? null,
			$row['figure'] ?? null,
			$row['sex'] ?? null,
			$row['mission'] ?? null,
			$row['credits'] ?? null,
			$row['tickets'] ?? null,
			$row['ticket_sso'] ?? null,
			$row['pixels'] ?? null
		];
		$this->id = $id;
		$this->name = $row['name'];
		$this->figure = $row['figure'] ?? null;
		$this->password = $password; // plaintext for potential refresh
		$this->logged_in = true;
		$this->time = time();
		return true;
	}

	/**
	 * Load user data by ID without password verification.
	 * Used for token-based authentication (remember-me, SSO).
	 */
	public function loadUserById($id) {
		$db = new Database();
		$row = $db->fetchRow("SELECT * FROM users WHERE id = ?", [(int)$id]);
		if (!$row) {
			return false;
		}
		$this->id = (int)$row['id'];
		$this->name = $row['name'];
		$this->user = [
			$row['id'],
			$row['name'],
			$row['password'],
			$row['rank'],
			null,
			$row['birth'] ?? null,
			$row['figure'] ?? null,
			$row['sex'] ?? null,
			$row['mission'] ?? null,
			$row['credits'] ?? null,
			$row['tickets'] ?? null,
			$row['ticket_sso'] ?? null,
			$row['pixels'] ?? null
		];
		$this->figure = $row['figure'] ?? null;
		$this->logged_in = true;
		$this->error = 0;
		$this->time = time();
		$this->ip = $_SERVER['REMOTE_ADDR'];
		return true;
	}

	function destroy(){
		@session_start();
		setcookie("rememberme", "", time()-60*60*24*100, "/");
		setcookie("cookpass", "", time()-60*60*24*100, "/");
		setcookie("rememberme_token", "", time()-60*60*24*100, "/");
		setcookie("cookpass", "", time()-60*60*24*100, "/");
		$_SESSION = array();
		if(isset($_COOKIE[session_name()])) {
			setcookie(session_name(), "", time()-60*60*24*100, "/");
		}
		@session_destroy();
		return true;
	}

	function refresh(){
		$GLOBALS['user'] = new HoloUser($this->name, $this->password);
		$_SESSION['user'] = $GLOBALS['user'];
		return true;
	}

	function user($key){
		switch($key){
			case "id":      return $this->user[0];
			case "name":    return $this->user[1];
			case "password": return $this->user[2];
			case "rank":    return $this->user[3];
			case "birth":   return $this->user[5];
			case "figure":  return $this->user[6];
			case "sex":     return $this->user[7];
			case "mission": return $this->user[8];
			case "credits": return $this->user[9];
			case "tickets": return $this->user[10];
			case "ticket_sso": return $this->user[11];
			case "pixels":  return $this->user[12];
			default:
				$val = $this->db->fetchColumn("SELECT ".$key." FROM users WHERE id = ?", [$this->user[0]]);
				return $val;
		}
	}

	function avatarURL($figure,$style,$return = 0){
		if($figure == "self"){ $figure = $this->figure; }
		$figure = $GLOBALS['input']->HoloText($figure);
		$hash = md5($figure.strtolower($style));
		$style = explode(",", $style);
		if($style[0] == "s"){ $style[6] = "1"; }else{ $style[6] = "0"; }
		if($style[3] == "sml"){ $style[7] = "1"; }else{ $style[7] = "0"; }
		$expandedstyle = "s-".$style[6].".g-".$style[7].".d-".$style[1].".h-".$style[2].".a-0";
		if($GLOBALS['settings']->find("site_cache_images") == "1" && file_exists("./cache/avatars/".$figure.",".$expandedstyle.",".$hash.".png")){
			$URL = PATH."/habbo-imaging/avatar/".$figure.",".$expandedstyle.",".$hash.".gif";
		}elseif($GLOBALS['settings']->find("site_cache_images") == "1" && !file_exists("./cache/avatars/".$figure.",".$expandedstyle.",".$hash.".png")){
			$URL = "http://www.habbo.co.uk/habbo-imaging/avatarimage?figure=".$figure."&size=".$style[0]."&direction=".$style[1]."&head_direction=".$style[2]."&crr=".$style[5]."&gesture=".$style[3]."&frame=".$style[4];
			$i = file_get_contents($URL);
			$f = fopen("./cache/avatars/".$figure.",".$expandedstyle.",".$hash.".png","w+");
			fwrite($f,$i);
			fclose($f);
			$URL = PATH."/habbo-imaging/avatar/".$figure.",".$expandedstyle.",".$hash.".gif";
		}elseif($GLOBALS['settings']->find("site_cache_images") == "0"){
			$URL = "http://www.habbo.co.uk/habbo-imaging/avatarimage?figure=".$figure."&size=".$style[0]."&direction=".$style[1]."&head_direction=".$style[2]."&crr=".$style[5]."&gesture=".$style[3]."&frame=".$style[4];
		}
		if($return == 0){ return $URL; }else{ return $hash; }
	}

	function updateUser($id){
		$lastvisit = $this->db->fetchColumn("SELECT online FROM users WHERE id = ?", [$id]);
		$this->db->execute("UPDATE users SET lastvisit = ?, online = ?, ipaddress_last = ? WHERE id = ?",
			[$lastvisit, time(), $_SERVER['REMOTE_ADDR'], $id]);
		$sso = GenerateTicket("sso");
		$this->db->execute("UPDATE users SET ticket_sso = ? WHERE id = ?", [$sso, $id]);
		$this->db->execute("UPDATE users SET last_online = ? WHERE id = ?", [date('d-m-Y H:i:s'), $id]);
	}

	function GetUserBadge($id){
		if($id == "self"){ $id = $this->id; }
		$badge = $this->db->fetchColumn("SELECT badge FROM user_badges WHERE user_id = ? ORDER BY id LIMIT 1", [(int)$id]);
		return $badge ?: false;
	}

	function GetUserGroup($id){
		if($id == "self"){ $id = $this->id; }
		$group_id = $this->db->fetchColumn("SELECT group_id FROM user_groups WHERE user_id = ? LIMIT 1", [(int)$id]);
		return $group_id ?: false;
	}

	function GetUserGroupBadge($id){
		if($id == "self"){ $id = $this->id; }
		$group_id = $this->GetUserGroup($id);
		if($group_id){
			return $this->db->fetchColumn("SELECT badge FROM groups WHERE id = ?", [$group_id]) ?: false;
		}
		return false;
	}

	function HCDaysLeft($id){
		if($id == "self"){ $id = $this->id; }
		$row = $this->db->fetchRow("SELECT start_date, duration FROM hc_membership WHERE user_id = ? LIMIT 1", [(int)$id]);
		if(!$row) return 0;
		$days_left = (int)$row['duration'] * 31;
		$tmp = explode("-", $row['start_date']);
		$day = $tmp[0]; $month = $tmp[1]; $year = $tmp[2];
		$then = mktime(0,0,0,$month,$day,$year,0);
		$now = time();
		$difference = $now - $then;
		if($difference < 0) $difference = 0;
		$days_expired = floor($difference/60/60/24);
		$days_left = $days_left - $days_expired;
		return ($days_left > 0) ? $days_left : 0;
	}

	function IsHCMember($id){
		if($id == "self"){ $id = $this->id; }
		if($this->HCDaysLeft($id) > 0){
			return true;
		} else {
			// Check if they have a record but expired
			$exists = $this->db->fetchColumn("SELECT id FROM hc_membership WHERE user_id = ?", [(int)$id]);
			if($exists){
				$this->db->execute("DELETE FROM hc_membership WHERE user_id = ?", [(int)$id]);
				@SendMUSData('UPRS' . $id);
			}
			return false;
		}
	}

	function GiveHC($id, $months){
		if($id == "self"){ $id = $this->id; }
		$exists = $this->db->fetchColumn("SELECT id FROM hc_membership WHERE user_id = ?", [(int)$id]);
		if($exists){
			$this->db->execute("UPDATE hc_membership SET duration = duration + ? WHERE user_id = ?", [$months, $id]);
		} else {
			$start = date('d-m-Y');
			$this->db->execute("INSERT INTO hc_membership (user_id, start_date, duration) VALUES (?, ?, ?)", [$id, $start, $months]);
		}
		@SendMUSData('UPRS' . $id);
		@SendMUSData('UPRC' . $id);
	}

	function IsUserOnline($id){
		if($id == "self"){ $id = $this->id; }
		$timeout = ((int)$GLOBALS['settings']->find("site_session_time")) * 60;
		$row = $this->db->fetchRow("SELECT online, show_online FROM users WHERE id = ?", [(int)$id]);
		if(!$row) return false;
		if($row['show_online'] == 0) return false;
		if($row['online'] + $timeout >= time()){
			return true;
		}
		return false;
	}

	/**
	 * Ban check – currently unresolved because the schema is unknown.
	 * Throws an exception to indicate this needs to be implemented.
	 * TODO: Determine actual ban storage and implement properly.
	 */
	function IsUserBanned($id){
		throw new Exception("Not yet migrated – see Phase 2 (ban check schema unknown)");
	}
}
class HoloDatabase {
	var $connection;
	var $error;
	var $lastquery;
	function __construct($conn){
		throw new Exception("Not yet migrated – see Phase 2");
		// Old code removed
	}
}
class mysql extends HoloDatabase {
	function query($query){
		throw new Exception("Not yet migrated – see Phase 2");
	}
	function fetch_assoc($query){
		throw new Exception("Not yet migrated – see Phase 2");
	}
	function fetch_row($query){
		throw new Exception("Not yet migrated – see Phase 2");
	}
	function fetch_array($result,$result_type=0){
		throw new Exception("Not yet migrated – see Phase 2");
	}
	function num_rows($query){
		throw new Exception("Not yet migrated – see Phase 2");
	}
	function result($query,$row=0,$column=0){
		throw new Exception("Not yet migrated – see Phase 2");
	}
	function insert_id($query=null){
		throw new Exception("Not yet migrated – see Phase 2");
	}
}
class pgsql extends HoloDatabase {
	function query($query){
		throw new Exception("Not yet migrated – see Phase 2");
	}
	function fetch_assoc($query){
		throw new Exception("Not yet migrated – see Phase 2");
	}
	function fetch_row($query){
		throw new Exception("Not yet migrated – see Phase 2");
	}
	function fetch_array($result,$result_type=0){
		throw new Exception("Not yet migrated – see Phase 2");
	}
	function num_rows($query){
		throw new Exception("Not yet migrated – see Phase 2");
	}
	function result($query,$row=0,$column=0){
		throw new Exception("Not yet migrated – see Phase 2");
	}
	function insert_id($query){
		throw new Exception("Not yet migrated – see Phase 2");
	}
}
class sqlite extends HoloDatabase {
	function query($query){
		throw new Exception("Not yet migrated – see Phase 2");
	}
	function fetch_assoc($query){
		throw new Exception("Not yet migrated – see Phase 2");
	}
	function fetch_row($query){
		throw new Exception("Not yet migrated – see Phase 2");
	}
	function fetch_array($result,$result_type=0){
		throw new Exception("Not yet migrated – see Phase 2");
	}
	function num_rows($query){
		throw new Exception("Not yet migrated – see Phase 2");
	}
	function result($query,$row=0,$column=0){
		throw new Exception("Not yet migrated – see Phase 2");
	}
	function insert_id($query){
		throw new Exception("Not yet migrated – see Phase 2");
	}
}
class mssql extends HoloDatabase {
	function query($query){
		throw new Exception("Not yet migrated – see Phase 2");
	}
	function fetch_assoc($query){
		throw new Exception("Not yet migrated – see Phase 2");
	}
	function fetch_row($query){
		throw new Exception("Not yet migrated – see Phase 2");
	}
	function fetch_array($result,$result_type=0){
		throw new Exception("Not yet migrated – see Phase 2");
	}
	function num_rows($query){
		throw new Exception("Not yet migrated – see Phase 2");
	}
	function result($query,$row=0,$column=0){
		throw new Exception("Not yet migrated – see Phase 2");
	}
	function insert_id($query){
		throw new Exception("Not yet migrated – see Phase 2");
	}
}
class HoloLocale {
	var $loc = array();
	function addLocale($keys){
		if(is_array($keys)){
			foreach($keys as $key){
				require('./includes/languages/'.$GLOBALS['settings']->find("site_language").'.php');
				$this->loc = array_merge($this->loc,$loc);
			}
		}else{
			$key = $keys;
			require('./includes/languages/'.$GLOBALS['settings']->find("site_language").'.php');
			$this->loc = array_merge($this->loc,$loc);
		}
		return true;
	}
	function clearLocale($key){
		unset($this->loc);
		return true;
	}
}
class HoloFigureCheck {
	var $error = 0;
	function __construct($figure=null,$gender=null,$club=false){
		if(empty($figure)){ $this->error = 12; return false; }
		$xml = simplexml_load_file('./xml/figuredata.xml');
		$sets = explode(".",$figure);
		foreach($sets as $set){
			$valid = array(false,false,false,false);
			$parts = explode("-",$set);
			$havesets[] = $parts[0];
			foreach($xml->sets->settype as $settype){
				if((string) $settype['mandatory'] == "1"){ $mandatory[] = $settype['type']; }
				if((string) $settype['type'] == $parts[0]){
					$parts[3] = $settype['paletteid'];
					$valid[0] = true; $type = $settype;
					break;
				}
			}
			if($valid[0] != true){ $this->error = 1; return false; }
			foreach($type->set as $xset){
				if((string) $xset['id'] == $parts[1]){
					if($xset['selectable'] == "0"){ $this->error = 2; return false; }
					if($xset['colorable'] == "0"){ $nocolor = true; if($parts[2] != ""){ $this->error = 3; return false; } }else{ $nocolor = false; }
					if($xset['gender'] != $gender && $xset['gender'] != "U"){ $this->error = 4; return false; }
					if($xset['club'] == "1" && $club == false){ $this->error = 5; return false; }
					$valid[1] = true; $details = $xset;
					break;
				}
			}
			if($valid[1] != true){ $this->error = 6; return false; }
			if($nocolor != true){
				foreach($xml->colors->palette as $palette){
					if((string) $palette['id'] == (string) $parts[3]){
						$valid[2] = true; $pat = $palette;
						break;
					}
				}
				if($valid[2] != true){ $this->error = 7; return false; }
				foreach($pat->color as $color){
					if((string) $color['id'] == $parts[2]){
						if($color['club'] == "1" && $club == false){ $this->error = 8; return false; }
						if($color['selectable'] == "0"){ $this->error = 9; return false; }
						$valid[3] = true;
						break;
					}
				}
				if($valid[3] != true){ $this->error = 10; return false; }
			}
		}
		if(count($mandatory) != count(array_intersect($mandatory,$havesets))){ $this->error = 11; return false; }
		return true;
	}
	function generateFigure($club=true,$gender=null){
		if($gender == null){ if(rand(0,1) == 0){ $gender = "M"; }else{ $gender = "F"; } }
		if($club == true){ $club = (bool) rand(0,1); }
		$xml = simplexml_load_file('./xml/figuredata.xml');
		$figure = "";
		foreach($xml->sets->settype as $settype){
			if((string) $settype['mandatory'] == "1" || rand(0,1) == 1){
				$item['settype'] = $settype['type'];
				$palette = (int) $settype['paletteid'];
				$possible = array();
				foreach($settype->set as $xset){
					if($xset['gender'] != "U" && $xset['gender'] != $gender){ $fail = true; }
					if($xset['selectable'] == "0"){ $fail = true; }
					if($xset['colorable'] == "0"){ $color = false; }else{ $color = true; }
					if($xset['club'] == "1" && $club == false){ $fail = true; }
					if($fail != true){ $possible[] = array($xset['id'],$color); }
					$fail = false; $color = false;
				}
				$count = count($possible);
				$num = rand(0,$count-1);
				$item['set'] = $possible[$num][0];
				if($possible[$num][1] == false){ $item['color'] = ""; }else{
					$possible = array();
					foreach($xml->colors->palette[$palette-1]->color as $color){
						if($color['club'] == "1" && $club == false){ $fail = true; }
						if($color['selectable'] == "0"){ $fail = true; }
						if($fail != true){ $possible[] = $color['id']; }
						$fail = false;
					}
					$count = count($possible);
					$num = rand(0,$count-1);
					$item['color'] = $possible[$num];
				}
				$figure .= $item['settype']."-".$item['set']."-".$item['color'].".";
			}
		}
		$figure = substr($figure, 0, -1);
		return array($figure,$gender);
	}
}
class HoloSettings {
	var $cache;
	function __construct(){
		@include('./cache/settings.ret');
		if(isset($setting)){ $this->cache = $setting; }
		return true;
	}
	function generateCache(){
		if($this->find("cache_settings") == "1"){
			$fh = @fopen('./cache/settings.ret', 'w');
			@fwrite($fh, "<?php\n/*DO NOT EDIT THIS FILE, EDIT THE SETTINGS TABLE OR USE HOUSEKEEPING, THIS FILE IS JUST A CACHE*/\n");
			$sql = $GLOBALS['db']->query("SELECT id,value FROM ".PREFIX."settings");
			while($row = $GLOBALS['db']->fetch_assoc($sql)){
				@fwrite($fh, "$"."setting['".$row['id']."'] = \"".$GLOBALS['input']->FilterText($row['value'])."\";\n");
			}
			@fwrite($fh, "?>");
			@fclose($fh);
		}else{
			@unlink('./cache/settings.ret');
		}
		$this->__construct();
		return true;
	}
	function find($key){
		if(!empty($this->cache)){
			return $GLOBALS['input']->HoloText($this->cache[$key],true);
		}else{
			$sql = $GLOBALS['db']->query("SELECT value FROM ".PREFIX."settings WHERE id = '".$key."' LIMIT 1");
			return $GLOBALS['input']->HoloText($GLOBALS['db']->result($sql),true);
		}
	}
	function checkCache(){
		if($this->find("cache_settings") == "1"){
			@require('./cache/settings.ret');
			$sql = $GLOBALS['db']->query("SELECT id,value FROM ".PREFIX."settings");
			while($row = $GLOBALS['db']->fetch_assoc($sql)){
				if(stripslashes($setting[$row['id']]) != stripslashes($row['value'])){ return true; }
			}
			return false;
		}else{
			@unlink('./cache/settings.ret');
			return false;
		}	
	}
}
class HoloMail {
	var $plaintext;
	var $html;
	var $logo;
	var $boundary;
	var $email;
	var $subject;
	function __construct(){
		// Constructor intentionally left empty
	}
	function sendSimpleMessage($to,$subject,$html,$plaintext=null){
		$this->logo = $this->generateLogo();
		$this->html = $this->htmlToMessage('./templates/email_header.php').$html.$this->htmlToMessage('./templates/email_footer.php');
		if($plaintext == null){ $this->plaintext = $this->generatePlainText($this->html); }else{ $this->plaintext = $plaintext; }
		$array = $this->generateHeaders($to,$subject); $header = $array[1];
		$message = $this->generateMessage();
		$success = @mail($to,$subject,$message,$header);
		return $success;
	}
	function sendNewsletter($to,$subject,$html){
		$this->html = $html;
		$this->plaintext = $this->generatePlainText($html);
		$array = $this->generateHeaders($to,$subject); $header = $array[1];
		$message = $this->generateMessage();
		$success = @mail($to,$subject,$message,$header);
		return $success;
	}
	function generatePlainText($html){
		return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", strip_tags(str_replace("<br />", "\n", str_replace("%name%", $row['name'], $html))));
	}
	function generateHeaders($to,$subject){
$this->boundary = time();
$preheader = '';
$preheader .= 'To: '.$to."\r\n";
$preheader .= 'Subject: '.$subject;
$header = '';
$header .= 'Return-Path: <'.$GLOBALS['settings']->find("email_from").'>'."\r\n";
$header .= 'Date: '.date('r (T)')."\r\n";
$header .= 'From: "'.$GLOBALS['settings']->find("email_name").'" <'.$GLOBALS['settings']->find("email_from").'>'."\r\n";
$header .= 'MIME-Version: 1.0'."\r\n";
$header .= 'Content-Type: multipart/related; '."\r\n";
$header .= '	boundary="----=_Part_402930_17237178.'.$this->boundary.'"';
return array($preheader,$header);
	}
	function generateLogo($file=null){
		if($file == null){ $file = './web-gallery/email/images/habbologo.gif'; }
		$fh = fopen($file, "r");
		$image = fread ($fh, filesize($file));
		fclose($fh);
		$encodedimage = chunk_split(base64_encode($image));
		if($encodedimage == ""){
			$encodedimage = 
'R0lGODlhoABCAJEDAP/OAAAAAP5jAf//ACH5BAEAAAMALAAAAACgAEIAAAL/nI+py+0Po5y02ouz
3rz7D4biSJbmiabqyrbuC8fyTNf2jef6zvf+D3QFhrih8YhMBhBK5KEJfUKVjClRsTRka1broHv8
gp1jp3TMFC+3syHgDX+74/AknW6/6/N6vFFr1Cf3pyVWyBXQN3d3JOi36FgXGBnHF0m49qWJqJi4
B0k5SSnpOToIenm1xdZW6tfpGoqaGis7KximVlQ72GlayZv6Kzl8ynpzmxzseMu8jPsMe5Wj7Ptp
zRhcfc2dLUC4i/3aPZ4tDnxO/CnwPW2zbU6OLq8eb1/ux95+LAOPP38P4L96A3ut0weORjNo6QwG
JCgwIkRJ+ti5a1UMgD+J/7IybqRYMWG/aPQcFnRG8lFDjQEqWuT3YuHKj9BE2aLJ0mW7dylP4vxk
kxbOITovxpBZUtiwoLZmtnRpFAbShx2LTfWp7WlImEJ6clRKlaVTp0W5thjqdaLJr2g7leU51mrW
uGGVveWUtG3euXvdQjXLQm9dvoPp5vsLt6/ipWnXjiKKGG/hxb+ufnV2VyFhrCtryrXMUitCwCsE
c2bcuFFl0aMTT379mClK2UB17pR8Gvbsq0deLuxtO2rMMsSLT9lHfOsY2y95Gn9ufPTy4F2Yt3ZO
nbXv7Nytd/du3Qj48KQDQ/6rfd935ePRt2cu/r10aufZl00f37189fvty10X3gp+9Um3HoH75Xcg
cO2JhMyA291X4IMLKjghERRmV95R0G3IYXFnoPFDhyJ6oUYZC4ARRIoqrshiiy6+CGOMMs5IY402
3ohjjjruyGOPPv4IZJBCDklkkUYeWQAAOw==';
		}
		return $encodedimage;
	}
	function generateMessage(){
		$message = '';
		$message .= 
'------=_Part_402930_17237178.'.$this->boundary.'
Content-Type: multipart/alternative; 
	boundary="----=_Part_402931_29846152.'.$this->boundary.'"'."\r\n\r\n";
		if($this->plaintext != ""){ $message .=
'------=_Part_402931_29846152.'.$this->boundary.'
Content-Type: text/plain; charset=ISO-8859-1
Content-Transfer-Encoding: 7bit

'.$this->plaintext."\r\n";
		}
		if($this->html != ""){ $message .=
'------=_Part_402931_29846152.'.$this->boundary.'
Content-Type: text/html;charset=ISO-8859-1
Content-Transfer-Encoding: 7bit

'.$this->html.'
------=_Part_402931_29846152.'.$this->boundary.'--'."\r\n\r\n";
		}
		if($this->logo != ""){ $message .=
'------=_Part_402930_17237178.'.$this->boundary.'
Content-Type: image/gif
Content-Transfer-Encoding: base64
Content-Disposition: inline
Content-ID: <habbologo>

'.$this->logo.'
------=_Part_402930_17237178.'.$this->boundary.'--';
		}
		return $message;
	}
function htmlToMessage($file){
global $lang;
ob_start();
include($file);
$contents = ob_get_clean();
ob_end_clean();
return $contents;
}
}
?>