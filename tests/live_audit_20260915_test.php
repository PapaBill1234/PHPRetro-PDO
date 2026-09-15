<?php
/** Live XAMPP audit 2026-09-15 regression tests. No database. Run: php tests/live_audit_20260915_test.php */
$root = dirname(__DIR__);
chdir($root);

$assertions = 0;
function check(bool $condition, string $message): void {
    global $assertions;
    if (!$condition) { throw new RuntimeException($message); }
    $assertions++;
}

$hkFiles = [
    'about.php', 'alerts.php', 'auditlog.php', 'banners.php', 'bans.php', 'cache.php',
    'campaigns.php', 'catalogue.php', 'collectables.php', 'dashboard.php', 'faq.php',
    'help.php', 'index.php', 'logout.php', 'logs.php', 'maintenance.php', 'news.php',
    'newsletter.php', 'recommended.php', 'reports.php', 'search.php', 'settings.php',
    'staffsessions.php', 'twofactor.php', 'updates.php', 'users.php', 'vouchers.php',
];
foreach ($hkFiles as $file) {
    $source = file_get_contents($root.'/housekeeping/'.$file);
    check($source !== false, $file.' is readable');
    check(!preg_match("/require_once __DIR__ \. '\/\.\.\/includes\/[^']+\.php'\)/", $source), $file.' has no leftover require parenthesis');
    check(str_contains($source, "require_once __DIR__ . '/../includes/core.php'"), $file.' requires core via __DIR__');
    check(!preg_match("/require_once\s*\(\s*['\"]\.\.\/includes\//", $source), $file.' does not require includes via chdir-relative ../includes');
}

$database = file_get_contents($root.'/includes/Database.php');
check(str_contains($database, 'function __serialize'), 'Database excludes PDO from session serialization');
check(str_contains($database, 'function __unserialize'), 'Database reconnects on session restore');
check(!str_contains($database, 'function __sleep'), 'Database does not use deprecated __sleep');

$classes = file_get_contents($root.'/includes/classes.php');
check(str_contains($classes, 'function __serialize'), 'HoloUser excludes Database/PDO from session serialization');
check(str_contains($classes, "unset(\$data['db'])"), 'HoloUser serialize drops the private Database');
check(str_contains($classes, '$this->db = new Database()'), 'HoloUser unserialize reconstructs Database');
check(str_contains($classes, "if(\$cacheImages !== \"0\" && \$cacheImages !== \"1\"){ \$cacheImages = \"0\"; }"), 'avatarURL defaults missing site_cache_images to a renderer URL');
check(str_contains($classes, '$URL = "http://www.habbo.co.uk/habbo-imaging/avatarimage?figure="'), 'avatarURL always assigns a renderer URL before cache branches');
check(str_contains($classes, "'site_cache_images'=>'1'"), 'HoloSettings defaults site_cache_images to the installer value');
check(str_contains($classes, "'email_verify_enabled'=>'0'"), 'HoloSettings defaults email_verify_enabled to the installer value');

$account = file_get_contents($root.'/account.php');
check(preg_match('/\$user = new HoloUser\([\s\S]*?\$_SESSION\[\'user\'\] = \$user;/', $account) === 1, 'account.php still stores a session user after a successful login');
$submit = [];
check(preg_match('/case "submit":([\s\S]*?)break;/m', $account, $submit) === 1, 'account.php submit case is present');
$beforeError = substr($submit[1], 0, strpos($submit[1], 'if($user->error > 0)'));
check(!str_contains($beforeError, "\$_SESSION['user'] = \$user"), 'account.php does not store HoloUser before authentication succeeds');
check(str_contains($submit[1], "if(!empty(\$login_error))"), 'failed login still has an error path');
check(str_contains($submit[1], "\$_SESSION['user'] = \$user"), 'successful login still stores the authenticated user');

$reauth = file_get_contents($root.'/reauthenticate.php');
check(str_contains($reauth, "if(\$user->error > 0)"), 'reauthenticate checks login errors');
$beforeReauthError = substr($reauth, 0, strpos($reauth, 'if($user->error > 0)'));
check(!str_contains($beforeReauthError, "\$_SESSION['user'] = \$user"), 'reauthenticate does not store HoloUser before success');
check(str_contains($reauth, "\$_SESSION['user'] = \$user"), 'reauthenticate stores the user after success');

$captcha = file_get_contents($root.'/captcha/php-captcha.inc.php');
check(str_contains($captcha, 'function __construct('), 'PhpCaptcha uses a PHP 5+ constructor');
check(!preg_match('/function PhpCaptcha\s*\(/', $captcha), 'PhpCaptcha no longer uses a PHP 4 constructor name');
check(str_contains($captcha, 'parent::__construct('), 'PhpCaptchaColour calls parent::__construct');
check(str_contains($captcha, 'if ($chars < 1)'), 'CalculateSpacing refuses to divide by zero');
check(str_contains($captcha, 'session_status() !== PHP_SESSION_ACTIVE'), 'captcha does not start an already-active session');

$register = file_get_contents($root.'/register.php');
check(!str_contains($register, "(int) \$settings->find('register_start_credits')"), 'register does not cast a missing start-credits setting to zero');
check(str_contains($register, "INSERT INTO users_settings (user_id) VALUES (?)"), 'register creates a users_settings row for website features');
check(str_contains($register, "if (\$startCredits !== '' && preg_match('/^-?\\d+$/', \$startCredits))"), 'register only writes credits when a numeric setting is present');

$session = file_get_contents($root.'/includes/session.php');
check(str_contains($session, "empty(\$page['dir'])"), 'guest redirect saves REQUEST_URI when page dir is empty');
check(str_contains($session, "\$_SESSION['page'] ?? ''"), 'guest redirect never reads an undefined session page key');

$core = file_get_contents($root.'/includes/core.php');
check(str_contains($core, "'discussion' => false"), 'core initializes optional discussion page key');
check(str_contains($core, "'no_column3' => false"), 'core initializes optional no_column3 page key');
check(str_contains($core, "'scrollbar' => false"), 'core initializes housekeeping scrollbar page key');
check(str_contains($core, "'second_scrollbar' => false"), 'core initializes housekeeping second_scrollbar page key');

$forgot = file_get_contents($root.'/forgot.php');
$email = file_get_contents($root.'/email.php');
check(!preg_match('/^\s*session_start\s*\(/m', $forgot), 'forgot.php does not start a duplicate session');
check(!preg_match('/^\s*session_start\s*\(/m', $email), 'email.php does not start a duplicate session');

$functions = file_get_contents($root.'/includes/functions.php');
check(str_contains($functions, 'function HoloOptionalWebGalleryTag'), 'optional web-gallery tags are omitted when the file is missing or empty');
foreach (['templates/login_header.php', 'templates/community_header.php', 'templates/faq_header.php'] as $template) {
    $html = file_get_contents($root.'/'.$template);
    check(str_contains($html, "HoloOptionalWebGalleryTag('web-gallery/styles/local/com.css'"), $template.' emits local CSS only when the file exists');
    check(str_contains($html, "HoloOptionalWebGalleryTag('web-gallery/js/local/com.js'"), $template.' emits local JS only when the file exists');
    check(!str_contains($html, 'src="<?php echo PATH; ?>/web-gallery/js/local/com.js"'), $template.' does not hardcode the missing local JS path');
    check(!str_contains($html, 'href="<?php echo PATH; ?>/web-gallery/styles/local/com.css"'), $template.' does not hardcode optional local CSS');
}

$warnings = [];
set_error_handler(static function (int $severity, string $message, string $file, int $line) use (&$warnings): bool {
    $warnings[] = $message.' in '.$file.':'.$line;
    return true;
});
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once $root.'/captcha/php-captcha.inc.php';
$captchaObject = new PhpCaptcha(['./captcha/monofont.ttf'], 200, 60);
$captchaObject->SetWidth(200);
check((int) $captchaObject->iSpacing > 0, 'PhpCaptcha spacing is calculated after construction');
$captchaObject->SetNumChars(0);
$captchaObject->SetWidth(200);
check((int) $captchaObject->iSpacing > 0, 'PhpCaptcha spacing never divides by zero');
$zeroWarnings = array_filter($warnings, static fn(string $message): bool => str_contains($message, 'Division by zero'));
check($zeroWarnings === [], 'PhpCaptcha emits no division-by-zero warnings: '.implode('; ', $zeroWarnings));
restore_error_handler();

define('IN_HOLOCMS', true);
define('PATH', '/PHPRetro-PDO');
require_once $root.'/includes/functions.php';
check(HoloOptionalWebGalleryTag('', 'js') === '', 'empty optional JS filename is omitted');
check(HoloOptionalWebGalleryTag('web-gallery/js/local/com.js', 'js') === '', 'missing local JS file is omitted');
$css = HoloOptionalWebGalleryTag('web-gallery/styles/local/com.css', 'css');
check(str_contains($css, '/web-gallery/styles/local/com.css'), 'existing local CSS file is still emitted');
check(!str_contains($css, '/web-gallery/styles/"'), 'existing CSS tag does not point at the styles directory');
check(HoloOptionalWebGalleryTag('../includes/core.php', 'js') === '', 'path traversal is rejected');

$newsletter = file_get_contents($root.'/housekeeping/newsletter.php');
check(str_contains($newsletter, "\$_GET['do'] ?? ''"), 'newsletter does not read an undefined do query key');
check(!str_contains($newsletter, 'HoloMail::htmlToMessage'), 'newsletter does not call instance HoloMail methods statically');
check(str_contains($newsletter, '(new HoloMail)->htmlToMessage'), 'newsletter loads the body template through a HoloMail instance');

$header = file_get_contents($root.'/templates/housekeeping_header.php');
check(str_contains($header, "!empty(\$page['scrollbar'])"), 'housekeeping header does not read an undefined scrollbar key');
check(str_contains($header, "!empty(\$page['second_scrollbar'])"), 'housekeeping header does not read an undefined second_scrollbar key');
check(!str_contains($header, "\$page['scrollbar'] == true"), 'housekeeping header no longer compares scrollbar with == true');

echo 'PASS: '.$assertions.' assertions; live audit 2026-09-15 blockers have source-level regressions covered.'."\n";
