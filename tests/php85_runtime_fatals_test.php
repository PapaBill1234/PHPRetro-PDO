<?php
/** PHP 8.5 runtime-fatal regression tests. No database. Run: php tests/php85_runtime_fatals_test.php */
$root = dirname(__DIR__);
chdir($root);

$assertions = 0;
function check(bool $condition, string $message): void {
    global $assertions;
    if (!$condition) { throw new RuntimeException($message); }
    $assertions++;
}

$warnings = [];
set_error_handler(static function (int $severity, string $message, string $file, int $line) use (&$warnings): bool {
    $warnings[] = $message.' in '.$file.':'.$line;
    return true;
});

define('IN_HOLOCMS', true);
define('PATH', '');
require_once $root.'/includes/functions.php';
require_once $root.'/includes/classes.php';

$date = HoloDate();
check(isset($date['y']) && $date['y'] === date('y'), 'HoloDate exposes y');
check($date['date_reversed'] === date('Y-m-d'), 'HoloDate date_reversed uses date()');
check($date['date_full'] === date('d-m-Y H:i:s'), 'HoloDate date_full uses date()');
check(HoloText('<b>') === htmlspecialchars('<b>', ENT_COMPAT, 'UTF-8'), 'global HoloText escapes HTML');
check(HoloText('<b>', true) === '<b>', 'global HoloText advanced mode does not escape');

$generator = new HoloFigureCheck();
foreach (['M', 'F'] as $gender) {
    for ($i = 0; $i < 3; $i++) {
        $figure = $generator->generateFigure(false, $gender);
        check(is_array($figure) && count($figure) === 2, 'generateFigure returns [look, gender]');
        check($figure[1] === $gender, 'generateFigure preserves gender '.$gender);
        check(is_string($figure[0]) && $figure[0] !== '', 'generateFigure returns a look string');
        check(preg_match('/^[a-z]{2}-\d+/', $figure[0]) === 1, 'generateFigure look starts with a set type');
    }
}

$failWarnings = array_filter($warnings, static fn(string $message): bool => str_contains($message, 'Undefined variable $fail') || str_contains($message, 'Undefined array key'));
check($failWarnings === [], 'generateFigure emits no $fail/array-key warnings: '.implode('; ', $failWarnings));

restore_error_handler();

$account = file_get_contents($root.'/account.php');
check(!str_contains($account, 'session_is_registered'), 'account.php no longer calls session_is_registered');
check(str_contains($account, "addLocale(\"landing.login\")"), 'account.php loads landing.login before pagename.home');
check(str_contains($account, "\$_GET['var1'] ?? ''"), 'account.php uses null-safe GET keys');
check(str_contains($account, "\$_GET['origin'] ?? ''"), 'account.php logout does not read an undefined origin key');

$papers = file_get_contents($root.'/papers.php');
check(str_contains($papers, '$input->HoloText($title)'), 'papers.php calls HoloInput::HoloText');
check(!preg_match('/echo HoloText\(\$title\)/', $papers), 'papers.php does not call the missing global HoloText at the old site');

$core = file_get_contents($root.'/includes/core.php');
check(str_contains($core, "\$_SESSION['hk_user'] ?? null"), 'core.php null-safes hk_user');

$hkIndex = file_get_contents($root.'/housekeeping/index.php');
check(str_contains($hkIndex, "__DIR__ . '/../includes/Totp.php'"), 'housekeeping login requires Totp via __DIR__');
check(!str_contains($hkIndex, "require_once('../includes/Totp.php')"), 'housekeeping login no longer uses chdir-relative Totp path');
check(!preg_match("/require_once __DIR__ \. '\/\.\.\/includes\/[^']+\.php'\)/", $hkIndex), 'housekeeping login has no leftover require parenthesis');

$hkSession = file_get_contents($root.'/includes/hksession.php');
check(!str_contains($hkSession, 'fetch_row'), 'hksession rank check no longer uses mysql-style fetch_row');
check(str_contains($hkSession, 'SELECT rank FROM users WHERE id = ?'), 'hksession rank check is a bound PolarIS query');

$register = file_get_contents($root.'/register.php');
check(str_contains($register, 'RegistrationForm.Validator._checkName()'), 'register page auto-runs namecheck on blur');
check(str_contains($register, "\$_POST['randomFigure']"), 'register submit accepts the no-Flash figure radios');

echo 'PASS: '.$assertions.' assertions; PHP 8.5 runtime fatals for register/login/housekeeping/privacy.'."\n";
