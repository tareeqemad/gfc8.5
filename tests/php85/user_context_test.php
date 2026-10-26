<?php
/** Offline regression for shared HMVC identity and menu-permission helpers. */
if (PHP_SAPI !== 'cli' || extension_loaded('oci8')) {
    fwrite(STDERR, "Run with PHP CLI -n; OCI must be disabled.\n");
    exit(1);
}
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
define('BASEPATH', dirname(__DIR__, 2) . '/system/');

// MY_Controller normally obtains this identity from the session. Do not boot
// the application here: its constructor opens Oracle and logs session activity.
class MY_Controller
{
    public static $active;
    public static function &get_instance() { return self::$active; }
}
class MenuPermissionContextFixture
{
    public $calls = [];
    public $permissionCount = '1';
    public function check_permission($userId, $url, $alternateUrl)
    {
        $this->calls[] = [$userId, $url, $alternateUrl];
        return $userId === 42 ? $this->permissionCount : '0';
    }
}
function user_context_fixture($userId, $notifications)
{
    return (object) [
        'user' => (object) ['id' => $userId, 'branch' => 3],
        'notify_Count' => $notifications,
        'user_menus_model' => new MenuPermissionContextFixture(),
    ];
}
$userContextCore = user_context_fixture(42, 999);
$userContextCoreReads = 0;
function &get_instance()
{
    global $userContextCore, $userContextCoreReads;
    ++$userContextCoreReads;
    return $userContextCore;
}
function base_url($uri = '')
{
    return 'http://127.0.0.1:8084/gfc/' . ltrim($uri, '/');
}
require dirname(__DIR__, 2) . '/application/helpers/my_security_helper.php';

$checks = 0;
$failures = [];
function user_context_check($actual, $expected, $name)
{
    global $checks, $failures;
    ++$checks;
    if ($actual !== $expected) {
        $failures[] = $name . ': expected ' . var_export($expected, true)
            . ', got ' . var_export($actual, true);
    }
}

$allowed = user_context_fixture(42, 8);
$denied = user_context_fixture(7, 2);
$guest = user_context_fixture(null, 0); // User::$id is null before login.
$zero = user_context_fixture(0, 0);
$route = 'payment_req/payment_req';
$contexts = [
    'allowed user, empty core identity' => [$allowed, null, true],
    'denied user, privileged core identity' => [$denied, 42, false],
    'guest, privileged core identity' => [$guest, 42, false],
    'zero user, privileged core identity' => [$zero, 42, false],
    'allowed user, unrelated core identity' => [$allowed, 7, true],
];
foreach ($contexts as $name => [$controller, $coreUserId, $expected]) {
    MY_Controller::$active = $controller;
    $userContextCore->user->id = $coreUserId;
    user_context_check(get_my_instance() === $controller, true, $name . ' returns module controller');
    user_context_check(get_curr_user() === $controller->user, true, $name . ' returns module user');
    user_context_check(get_notify_count(), $controller->notify_Count, $name . ' returns module notification count');
    user_context_check(HaveAccess(base_url($route)), $expected, $name . ' preserves allow/deny result');
    user_context_check(end($controller->user_menus_model->calls), [$controller->user->id, $route, $route], $name . ' sends actual ID and both URLs');
}

// Repeated switches ensure helpers do not retain a previous privileged module.
foreach ([$allowed, $denied, $allowed, $guest, $allowed, $zero, $denied] as $index => $controller) {
    MY_Controller::$active = $controller;
    user_context_check(HaveAccess($route), $controller->user->id === 42, 'context switch ' . $index . ' uses current permission');
    user_context_check(get_curr_user() === $controller->user, true, 'context switch ' . $index . ' uses current user');
    user_context_check(get_notify_count(), $controller->notify_Count, 'context switch ' . $index . ' uses current notifications');
}

MY_Controller::$active = $allowed;
$urlCases = [
    ['http://127.0.0.1:8084/gfc/payment_req//payment_req/', null, 'payment_req/payment_req', 'payment_req/payment_req/'],
    ['http://127.0.0.1:8084/gfc/payment_req//payment_req/', 'http://127.0.0.1:8084/gfc/payment_req//payment_req/index/', 'payment_req/payment_req', 'payment_req/payment_req/index/'],
    ['stores//classes/create', 'stores//classes/index?branch=3', 'stores/classes/create', 'stores/classes/index?branch=3'],
    ['stores/classes/?page=2', null, 'stores/classes/?page=2', 'stores/classes/?page=2'],
];
foreach ($urlCases as $index => [$url, $alternate, $expectedUrl, $expectedAlternate]) {
    user_context_check(HaveAccess($url, $alternate), true, 'URL case ' . $index . ' retains allowed result');
    user_context_check(end($allowed->user_menus_model->calls), [42, $expectedUrl, $expectedAlternate], 'URL case ' . $index . ' preserves permission URL contract');
}

// Preserve the existing positive-count rule, including string Oracle results.
foreach (['0', '-1', '', null, '1', '2'] as $count) {
    $allowed->user_menus_model->permissionCount = $count;
    user_context_check(HaveAccess($route), intval($count) > 0, 'permission count ' . var_export($count, true));
}
$allowed->user_menus_model->permissionCount = '1';

// Accessors return the active properties by reference, as legacy callers expect.
$currentUser =& get_curr_user();
$currentUser->branch = 5;
user_context_check($allowed->user->branch, 5, 'user reference remains tied to module property');
user_context_check($userContextCore->user->branch, 3, 'user reference never changes core user');
$notifications =& get_notify_count();
$notifications = 13;
user_context_check($allowed->notify_Count, 13, 'notification reference remains tied to module property');
user_context_check($userContextCore->notify_Count, 999, 'notification reference never changes core state');
unset($currentUser, $notifications);

user_context_check($userContextCoreReads, 0, 'identity and permission helpers never request shared core');
user_context_check($userContextCore->user_menus_model->calls, [], 'core permission model is never consulted');
if ($failures) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}
echo "PASS: {$checks} shared user context checks; no application bootstrap or Oracle connection.\n";
