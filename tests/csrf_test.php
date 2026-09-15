<?php
/** CSRF unit tests. No database. Run: php tests/csrf_test.php */
$_SESSION = [];
$_POST = [];
$_SERVER['REQUEST_METHOD'] = 'GET';
require_once dirname(__DIR__).'/includes/Csrf.php';

$assertions = 0;
function check(bool $condition, string $message): void {
    global $assertions;
    if (!$condition) { throw new RuntimeException($message); }
    $assertions++;
}

$token = Csrf::token();
check(preg_match('/^[0-9a-f]{64}$/', $token) === 1, 'token is 32 bytes hex');
check(Csrf::token() === $token, 'token is stable for the session');
check(Csrf::verify() === false, 'empty POST fails verify');
check(Csrf::verify('not-the-token') === false, 'wrong token fails verify');
check(Csrf::verify($token) === true, 'exact token verifies via hash_equals path');

$_POST[Csrf::FIELD] = $token;
check(Csrf::verify() === true, 'POST body csrf_token is the uniform submitted token');
$_POST[Csrf::FIELD] = $token.'x';
check(Csrf::verify() === false, 'tampered POST body fails');
$_POST[Csrf::FIELD] = ['array'];
check(Csrf::submittedToken() === '', 'non-string POST body is rejected');

$field = Csrf::field();
check(str_contains($field, 'name="csrf_token"'), 'hidden input uses csrf_token');
check(str_contains($field, $token), 'hidden input contains the session token');
check(!str_contains($field, 'not-the-token'), 'hidden input does not leak a different token');

check(Csrf::isStateChangingRequest() === false, 'GET is not state-changing');
$_SERVER['REQUEST_METHOD'] = 'POST';
check(Csrf::isStateChangingRequest() === true, 'POST is state-changing');
$_SERVER['REQUEST_METHOD'] = 'PUT';
check(Csrf::isStateChangingRequest() === true, 'PUT is state-changing');

$script = Csrf::hookScript();
check(str_contains($script, 'PHPRetroCsrfToken'), 'hook script exposes the token to Prototype/XHR');
check(str_contains($script, 'csrf_token'), 'hook script appends csrf_token to POST bodies');
check(!str_contains($script, 'X-CSRF-Token'), 'hook script does not use a custom header');

echo 'PASS: '.$assertions.' assertions; CSRF token generate/verify/field/hook.'."\n";
