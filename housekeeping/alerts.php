<?php
$page['dir'] = '\housekeeping';
$page['housekeeping'] = true;
$type = ($_GET['type'] ?? '') === 'mass' ? 'mass' : 'single';
$page['rank'] = $type === 'mass' ? 7 : 6;
require_once __DIR__ . '/../includes/core.php';
require_once('./includes/hksession.php');
require_once __DIR__ . '/../includes/AdminAudit.php';
require_once __DIR__ . '/../includes/PhpretroPolarisCms.php';
$lang->addLocale('housekeeping.alerts');
$lang->addLocale('housekeeping.alerts.create');
$lang->addLocale('housekeeping.alerts.display');
$page['name'] = $lang->loc['pagename.alerts'];
$page['category'] = 'users';
$database = new Database();
$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$cms = PhpretroPolarisCms::instance();
$error = '';
$message = '';
$userid = (int) ($_POST['userid'] ?? $_GET['userid'] ?? 0);
$alert = trim((string) ($_POST['alert'] ?? $_GET['alert'] ?? ''));
$searchResults = '<table border="0" cellspacing="0" cellpadding="0"><tr><td><b>'.$e($lang->loc['search.desc']).'</b></td></tr></table>';

if (isset($_POST['search'])) {
    Csrf::requireValid();
    $query = trim((string) ($_POST['query'] ?? ''));
    $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $query).'%';
    $rows = $query === '' ? [] : $database->fetchAll('SELECT id, username FROM users WHERE username LIKE ? LIMIT 50', [$like]);
    $searchResults = "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
    foreach ($rows as $i => $row) {
        $even = ($i % 2) === 0 ? 'even' : 'odd';
        $searchResults .= '<tr id="'.$even.'"><td><a href="'.PATH.'/housekeeping/alerts?type=single&do=create&userid='.(int) $row['id'].'">'.$e($row['username']).'</a></td><td class="selectid">'.(int) $row['id']."</td></tr>\n";
    }
    $searchResults .= '</table>';
}

if (isset($_POST['save'])) {
    Csrf::requireValid();
    if ($alert === '') { $error = $lang->loc['error.no.alert']; }
    elseif ($type === 'single' && $userid < 1) { $error = $lang->loc['error.no.userid']; }
    elseif (!$cms->configured()) { $error = $lang->loc['error.polaris.unconfigured']; }
    else {
        try {
            if ($type === 'single') {
                if ($database->fetchColumn('SELECT id FROM users WHERE id = ?', [$userid]) === false) {
                    $error = $lang->loc['error.user.missing'];
                } else {
                    $result = $cms->alertUser($userid, $alert);
                    if ($result['status'] === PhpretroPolarisCms::HABBO_NOT_FOUND) {
                        $error = $lang->loc['error.user.offline'];
                    } elseif ($result['status'] !== PhpretroPolarisCms::STATUS_OK) {
                        $error = $lang->loc['error.polaris.failed'].' '.($result['message'] !== '' ? $result['message'] : 'status '.$result['status']);
                    } else {
                        AdminAudit::log($database, (int) $user->id, 'alert_user', 'user', $userid, $alert);
                        $message = $lang->loc['message.alert.sent'];
                    }
                }
            } else {
                $result = $cms->hotelAlert($alert);
                if ($result['status'] !== PhpretroPolarisCms::STATUS_OK) {
                    $error = $lang->loc['error.polaris.failed'].' '.($result['message'] !== '' ? $result['message'] : 'status '.$result['status']);
                } else {
                    AdminAudit::log($database, (int) $user->id, 'alert_hotel', 'hotel', null, $alert);
                    $message = $lang->loc['message.hotel.alert.sent'];
                }
            }
        } catch (PhpretroPolarisCmsError $thrown) {
            $error = $lang->loc['error.polaris.failed'].' '.$thrown->getMessage();
        }
    }
    if ($error !== '') { $_GET['do'] = 'create'; }
}

$do = $_GET['do'] ?? '';
if ($do === 'create') {
    $icon = 'alerts.png';
    $description = $type === 'single' ? $lang->loc['alerts.create.single.desc'] : $lang->loc['alerts.create.mass.desc'];
    $content = '';
    if ($error !== '') { $content .= '<div class="clean-error">'.$e($error).'</div>'; }
    $content .= '<div class="settings"><form name="settings" action="'.PATH.'/housekeeping/alerts?type='.$e($type).'&do=create" method="POST">'.Csrf::field();
    if ($type === 'single') {
        $content .= '<label for="userid">'.$e($lang->loc['userid']).':</label><br /><input type="text" name="userid" value="'.$e((string) ($userid ?: '')).'" title="'.$e($lang->loc['userid.desc']).'" /><br />';
    }
    $content .= '<label for="alert">'.$e($lang->loc['alert']).':</label><br /><textarea name="alert" title="'.$e($lang->loc['alert.desc']).'">'.$e($alert).'</textarea><br />';
    $content .= '<p>'.$e($lang->loc['hotel.only']).'</p>';
    $content .= '<div class="button"><input type="submit" name="save" value="'.$e($lang->loc['save']).'" /></div></form></div>';
} else {
    $icon = 'alerts.png';
    $description = $lang->loc['alerts.display.desc'];
    $content = '';
    if ($message !== '') { $content .= '<div class="clean-ok">'.$e($message).'</div>'; }
    if ($error !== '') { $content .= '<div class="clean-error">'.$e($error).'</div>'; }
    $content .= '<div class="contentdisplay"><p>'.$e($lang->loc['alerts.no.persist']).'</p>';
    $content .= '<div class="button"><input type="button" value="'.$e($lang->loc['new.mass']).'" onclick="window.location.href=\''.PATH.'/housekeeping/alerts?type=mass&do=create\'"></input></div>';
    $content .= '<div class="button"><input type="button" value="'.$e($lang->loc['new.single']).'" onclick="window.location.href=\''.PATH.'/housekeeping/alerts?type=single&do=create\'"></input></div></div>';
}

require_once('./templates/housekeeping_header.php');
?>
<div class="page_title">
 <img src="<?php echo PATH; ?>/housekeeping/images/icons/<?php echo $icon; ?>" class="pticon">
 <span class="page_name_shadow"><?php echo $lang->loc['pagename.alerts']; ?></span>
 <span class="page_name"><?php echo $lang->loc['pagename.alerts']; ?></span>
</div>
<div class="page_main">
<table border="0" cellpadding="0" cellspacing="0" height="100%">
<tbody>
<tr height="100%" />
<td class="page_main_left">
<div class="left_date"><?php echo date('l F j, Y | g:iA'); ?></div>
<div class="hr"></div>
<div class="text">
 <p><?php echo $description; ?></p>
 </div>
<form name="users_search" action="<?php echo PATH; ?>/housekeeping/alerts" method="POST"><?php echo Csrf::field(); ?>
 <div class="text">
   <div class="user_listview_bg">
    <div id="listview">
     <div class="Scroller-Container">
<?php echo $searchResults; ?>
     </div>
    </div>
   </div>
<div class="searcuser">
<input type="text" name="query" id="searchname" value="">
<button type="submit" name="search" id="button_search"><?php echo $lang->loc['search']; ?></button>
</div>
</div>
</form>
</td>
 <td class="page_main_right">
<div class="center">
<?php echo $content; ?>
</div>
 </td>
</tr>
</tbody>
</table>
</div>
<?php require_once('./templates/housekeeping_footer.php'); ?>
