<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../includes/Csrf.php';
Csrf::token();
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    Csrf::requireValid();
}
require_once __DIR__ . '/../includes/Database.php';
foreach (['db_dsn' => 'DB_DSN', 'db_user' => 'DB_USER', 'db_pass' => 'DB_PASS', 'site_name' => 'SITE_NAME', 'site_url' => 'SITE_URL'] as $field => $environment) {
    if (isset($_POST[$field])) { $_SESSION[$field] = trim((string) $_POST[$field]); }
    if (isset($_SESSION[$field]) && $_SESSION[$field] !== '') { putenv($environment . '=' . $_SESSION[$field]); }
}
function statements(string $sql): array { $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? ''; return array_values(array_filter(array_map('trim', explode(';', $sql)))); }
function writeLocalEnvironment(): void {
    $required = ['db_dsn' => 'DB_DSN', 'db_user' => 'DB_USER', 'db_pass' => 'DB_PASS'];
    foreach ($required as $sessionKey => $label) { if (!array_key_exists($sessionKey, $_SESSION)) { throw new RuntimeException($label . ' is required. Return to the Database step.'); } }
    $lines = [];
    foreach ($required as $sessionKey => $label) { $lines[] = $label . '="' . str_replace(['\\', '"', "\r", "\n"], ['\\\\', '\\"', '', ''], (string) $_SESSION[$sessionKey]) . '"'; }
    $lines[] = 'CACHE_DRIVER="file"';
    if (file_put_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env', implode(PHP_EOL, $lines) . PHP_EOL, LOCK_EX) === false) { throw new RuntimeException('Could not write .env. Check the PHPRetro folder permissions.'); }
}
$step = max(1, min(6, (int) ($_SESSION['polaris_step'] ?? 1))); $error = null; $messages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'next';
    if ($action === 'back') { $step = max(1, $step - 1); }
    elseif ($step < 6) {
        try {
            if ($step === 2) { $db = new Database(); if ($db->fetchColumn('SELECT 1 FROM users LIMIT 1') === false) throw new RuntimeException('Polaris users table was not found. Import CleanDB.sql first.'); }
            $step++;
        } catch(Throwable $e) { $error = $e->getMessage(); }
    } elseif ($step === 6 && $action === 'install') {
        try {
            $db = new Database();
            $db->execute('CREATE TABLE IF NOT EXISTS phpretro_schema_migrations (filename VARCHAR(255) NOT NULL PRIMARY KEY, applied_at INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            foreach (glob(__DIR__ . '/../migrations/*.sql') ?: [] as $file) {
                $name = basename($file);
                if ($db->fetchColumn('SELECT filename FROM phpretro_schema_migrations WHERE filename=?', [$name])) { $messages[] = "$name already applied."; continue; }
                foreach (statements((string) file_get_contents($file)) as $sql) { $db->execute($sql); }
                $db->execute('INSERT INTO phpretro_schema_migrations (filename, applied_at) VALUES (?,?)', [$name, time()]); $messages[] = "$name applied.";
            }
            $path = parse_url((string) ($_SESSION['site_url'] ?? ''), PHP_URL_PATH) ?: rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
            foreach (['site_name' => ($_SESSION['site_name'] ?? 'PHPRetro'), 'site_shortname' => ($_SESSION['site_name'] ?? 'PHPRetro'), 'site_path' => rtrim($path, '/'), 'site_language' => 'en', 'site_closed' => '0', 'hotel_server' => 'polaris'] as $key => $value) {
                $db->execute('INSERT INTO phpretro_site_settings (setting_key, setting_value, updated_by, updated_at) VALUES (?,?,NULL,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_at=VALUES(updated_at)', [$key, $value, time()]);
            }
            writeLocalEnvironment(); $_SESSION['polaris_complete'] = true; $messages[] = 'Installation complete. You can now open the site.';
        } catch(Throwable $e) { $error = $e->getMessage(); }
    }
    $_SESSION['polaris_step'] = $step;
}$titles=[1=>'Introduction',2=>'Check',3=>'Database',4=>'Settings',5=>'Administrator',6=>'Install'];
$text=[1=>'Welcome to PHPRetro. This installer keeps the original flow while protecting Polaris.',2=>'Check the PHP 8.5 PDO connection and verified Polaris database.',3=>'Database credentials come from DB_DSN, DB_USER, and DB_PASS.',4=>'Site settings are handled by the modern configuration layer.',5=>'Use an existing Polaris staff account for administration.',6=>'Apply PHPRetro-owned migrations. Polaris tables are never changed.'];
require __DIR__ . '/installer_header.php';
?>
<div id="container"><div class="cbb process-template-box clearfix"><div id="content"><div id="header" class="clearfix"><h1><a href="#"></a></h1><ul class="stats"><li class="stats-online"><span class="stats-fig"><?php echo $step; ?>/6 <?php echo $titles[$step]; ?></span></li></ul></div><div class="process-content"><form method="post"><?php echo Csrf::field(); ?><div id="installer-column-left"><div id="installer-section-left"><?php echo htmlspecialchars($text[$step],ENT_QUOTES,'UTF-8'); ?></div></div><div id="installer-column-right"><div id="installer-section-right"><div class="rounded rounded-blue"><h2 class="heading"><?php echo $titles[$step]; ?></h2><fieldset id="installer-fieldset"><?php if($error): ?><p class="error"><?php echo htmlspecialchars($error,ENT_QUOTES,'UTF-8'); ?></p><?php endif; foreach($messages as $m): ?><p><?php echo htmlspecialchars($m,ENT_QUOTES,'UTF-8'); ?></p><?php endforeach; if($step===2): ?><p>Validates PHP 8.5, PDO MySQL, and Polaris <code>users</code>.</p><?php elseif($step===3): ?><label>PDO DSN</label><input class="installer-input" name="db_dsn" value="<?php echo htmlspecialchars($_SESSION['db_dsn'] ?? '',ENT_QUOTES,'UTF-8'); ?>"><label>Database user</label><input class="installer-input" name="db_user" value="<?php echo htmlspecialchars($_SESSION['db_user'] ?? '',ENT_QUOTES,'UTF-8'); ?>"><label>Password</label><input class="installer-input" type="password" name="db_pass"><?php elseif($step===4): ?><label>Site name</label><input class="installer-input" name="site_name" value="Retro Hotel"><label>Site URL</label><input class="installer-input" name="site_url" value="http://127.0.0.1/PHPRetro-PDO"><?php elseif($step===5): ?><label>Administrator username</label><input class="installer-input" name="admin_username"><label>Administrator email</label><input class="installer-input" name="admin_email" type="email"><?php elseif($step===6): ?><p><?php echo !empty($_SESSION['polaris_complete']) ? 'Installation complete. You can now open the site.' : 'Apply PHPRetro-owned migrations. Polaris tables are never changed.'; ?></p><?php endif; ?></fieldset></div></div><div id="installer-buttons"><?php if($step < 6 && $step > 1): ?><button class="back" name="action" value="back">Back</button><?php endif; ?><?php if($step < 6): ?><button class="continue" name="action" value="next">Continue</button><?php elseif(empty($_SESSION['polaris_complete'])): ?><button class="continue" name="action" value="install">Install</button><?php endif; ?></div></div></form></div><?php require __DIR__ . '/installer_footer.php'; ?>
