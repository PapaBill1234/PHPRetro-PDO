<?php
$page['dir'] = '\\housekeeping';
$page['housekeeping'] = true;
$page['rank'] = 7;
require_once __DIR__ . '/../includes/core.php');
require_once('./includes/hksession.php');
require_once('../includes/AdminAudit.php');
$database = new Database();
$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$notice = '';
$existing = [];
foreach ($database->fetchAll('SELECT setting_key, setting_value FROM phpretro_site_settings ORDER BY setting_key') as $row) {
    $existing[$row['setting_key']] = $row['setting_value'];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $changed = 0;
    foreach ($existing as $key => $current) {
        if (!array_key_exists($key, $_POST)) {
            continue;
        }
        $value = trim((string) $_POST[$key]);
        if ($value === $current) {
            continue;
        }
        $changed += $database->execute('UPDATE phpretro_site_settings SET setting_value = ?, updated_by = ?, updated_at = ? WHERE setting_key = ?', [$value, (int) $user->id, time(), $key]);
    }
    if ($changed > 0) {
        $settings->generateCache();
        AdminAudit::log($database, (int) $user->id, 'site_settings_updated', 'site_setting', null, (string) $changed.' keys');
        $notice = 'Settings saved.';
        $existing = [];
        foreach ($database->fetchAll('SELECT setting_key, setting_value FROM phpretro_site_settings ORDER BY setting_key') as $row) {
            $existing[$row['setting_key']] = $row['setting_value'];
        }
    } else {
        $notice = 'No settings changed.';
    }
}
$page['name'] = 'Site settings';
$page['category'] = 'settings';
require_once('./templates/housekeeping_header.php');
?>
<div class="page_title"><span class="page_name">Site settings</span></div>
<div class="page_main"><div class="center">
<?php if ($notice !== '') { ?><div class="clean-ok"><?php echo $e($notice); ?></div><?php } ?>
<p>Only keys already in <code>phpretro_site_settings</code> can be changed. New keys are not created from this form.</p>
<form method="post"><?php echo Csrf::field(); ?>
<?php foreach ($existing as $key => $value) { ?>
<label for="setting-<?php echo $e($key); ?>"><?php echo $e($key); ?></label><br>
<input id="setting-<?php echo $e($key); ?>" name="<?php echo $e($key); ?>" value="<?php echo $e($value); ?>"><br>
<?php } ?>
<button>Save</button>
</form>
</div></div>
<?php require_once('./templates/housekeeping_footer.php'); ?>
