<?php
/** Offline view contract checks. Never loads application controllers or Oracle. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return false;
});
require dirname(__DIR__, 2) . '/tools/preview-login.php';

$checks = 0;
function login_view_check($condition, $message)
{
    global $checks;
    ++$checks;
    if (!$condition) throw new RuntimeException($message);
}

function login_view_dom($html)
{
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    return new DOMXPath($document);
}

$live = gfc_render_login_view([], false);
$xpath = login_view_dom($live);
$form = $xpath->query('//form[@id="loginForm"]')->item(0);
login_view_check($form !== null, 'Login form missing.');
login_view_check(strtolower($form->getAttribute('method')) === 'post', 'Login must remain a POST.');
login_view_check($form->getAttribute('action') === '/gfc/login/check_user', 'Live login endpoint changed.');
foreach ([['user_id', 'txt_user_id', '15'], ['user_pwd', 'txt_user_pwd', '32'], ['email', 'txt_email', '50']] as [$name, $id, $maximum]) {
    $input = $xpath->query('//input[@name="' . $name . '"]')->item(0);
    login_view_check($input !== null && $input->getAttribute('id') === $id, 'Input contract changed: ' . $name);
    login_view_check($input->getAttribute('maxlength') === $maximum, 'Input limit changed: ' . $name);
}
login_view_check($xpath->query('//input[@name="user_pwd" and @type="password"]')->length === 1, 'Password must be masked initially.');
login_view_check($xpath->query('//select[@name="db_year"]//option[@value="0"]')->length === 1, 'Current database selection must remain value 0.');
login_view_check($xpath->query('//input[@name="action" and @value="do_login"]')->length === 1, 'Login action field missing.');
login_view_check($xpath->query('//input[@name="__AntiForgeryToken" and @value="offline-preview-token"]')->length === 1, 'AntiForgeryToken helper output missing.');
login_view_check($GLOBALS['gfc_login_preview_token_calls'] === 1, 'Anti-forgery helper must run for the form.');

$preview = login_view_dom(gfc_render_login_view());
login_view_check($preview->query('//form[@id="loginForm"]')->item(0)->getAttribute('action') === '#', 'Offline preview must not target authentication.');
foreach (['browser', 'version'] as $browserState) {
    $browser = login_view_dom(gfc_render_login_view(['browser' => $browserState]));
    login_view_check($browser->query('//input[@name="user_id" or @name="user_pwd"]')->length === 0, 'Unsupported-browser branch must not expose login inputs.');
}

$message = 'رسالة اختبار الدخول';
login_view_check(strpos(gfc_render_login_view(['msg' => $message]), $message) !== false, 'Server feedback was lost.');
$reset = login_view_dom(gfc_render_login_view(['reset' => 1]));
login_view_check($reset->query('//input[@name="email"]')->length === 1, 'Reset state lost its email field.');
login_view_check($reset->query('//form[@id="loginForm"]')->item(0)->getAttribute('action') === '#', 'Reset preview must not target password recovery.');

echo "PASS: $checks login view contract checks; no application bootstrap, Oracle or network calls.\n";
