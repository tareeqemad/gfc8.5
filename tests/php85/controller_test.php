<?php
// Exercise the real MY_Controller with isolated dependencies; never load DBConn/Oracle.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$root = dirname(__DIR__, 2);
define('BASEPATH', $root . '/system/');
define('APPPATH', 'application/');
define('FCPATH', $root . '/');
define('EXT', '.php');

function base_url($path = '') { return 'http://localhost/gfc/' . $path; }
function current_url() { return base_url('login'); }
function redirect(...$args) { throw new RuntimeException('Unexpected redirect in controller fixture.'); }
function controller_check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}

class ControllerTestSession {
    public $values = ['__AntiForgeryToken' => 'fixture-token'];
    public function userdata($key) { return $this->values[$key] ?? null; }
    public function set_userdata($key, $value) { $this->values[$key] = $value; }
    public function unset_userdata($key) { unset($this->values[$key]); }
    public function all_userdata() { return $this->values; }
    public function sess_update() {}
}
#[AllowDynamicProperties]
class MX_Controller {
    public static $fixtureSession;
    public function __construct() {
        $this->session = self::$fixtureSession;
        $this->load = new class {
            public function library($name) {}
            public function model($name) {}
        };
        $this->router = new class {
            public $class = 'login';
            public $method = 'index';
            public function fetch_module() { return ''; }
        };
        $this->input = new class { public function is_ajax_request() { return false; } };
        $this->uri = new class { public function uri_string() { return 'login'; } };
        $this->agent = new class {
            public function browser() { return 'Chrome'; }
            public function version() { return '130.0'; }
        };
        $this->user_menus_model = new class {
            public function check_permission(...$args) { return 1; }
        };
        $this->system_info_model = new class {
            public function user_notification_update(...$args) {}
            public function get_table_count($sql) { return [['NUM_ROWS' => 0]]; }
        };
        $this->sys_sessions_model = new class {
            public $updates = [];
            public function get_status($id) { return [['STATUS' => 1]]; }
            public function create_edit($params) { $this->updates[] = $params; return 1; }
        };
    }
}
class DBConn {} // A deliberately inert stand-in: the real connection class is never loaded.
require $root . '/application/core/Oracle_error.php';
require $root . '/application/core/User.php';
require $root . '/application/core/MY_Controller.php';
class ControllerCompatibilityProbe extends MY_Controller {
    public $errors = [];
    public function print_error($message) { $this->errors[] = $message; }
}

$_GET = $_POST = [];
unset($_SERVER['QUERY_STRING'], $_SERVER['HTTP_USER_AGENT'], $_SERVER['REQUEST_METHOD']);
MX_Controller::$fixtureSession = new ControllerTestSession();
$guest = new ControllerCompatibilityProbe();
controller_check($guest->_current_url() === 'login', 'Missing query string must leave the route intact.');
controller_check($guest->_is_posted() === false, 'Missing request method is not POST.');
controller_check($guest->AntiForgeryToken() === 'fixture-token', 'Existing session token changed.');

$_SERVER['QUERY_STRING'] = 'mode=edit&page=2';
controller_check($guest->_current_url() === 'login?mode=edit&page=2', 'Query string was changed.');
$_SERVER['REQUEST_METHOD'] = 'POST';
controller_check($guest->_is_posted() === true, 'POST recognition changed.');
$_SERVER['REQUEST_METHOD'] = 'GET';
controller_check($guest->_is_posted() === false, 'GET was treated as POST.');

foreach (['1', 1, '{"msg":1}'] as $success) $guest->Is_success($success);
controller_check($guest->errors === [], 'Success responses must remain successful.');
foreach ([null, '', 'not successful'] as $failure) $guest->Is_success($failure);
controller_check($guest->errors === [null, '', 'not successful'], 'Failure responses changed or became successful.');
controller_check($guest->_oracle_error_message(null) === null, 'Null error response must retain its value.');

$user = new User();
$user->id = 42;
$user->branch = 1;
MX_Controller::$fixtureSession = new ControllerTestSession();
MX_Controller::$fixtureSession->values['user_data'] = $user;
MX_Controller::$fixtureSession->values['system_id'] = 1;
$member = new ControllerCompatibilityProbe();
controller_check(count($member->sys_sessions_model->updates) === 1, 'Session audit update was skipped.');
$agentRow = array_values(array_filter($member->sys_sessions_model->updates[0], static fn($row) => $row['name'] === 'USER_AGENT'));
controller_check($agentRow[0]['value'] === '', 'Absent optional User-Agent must use an empty value.');
controller_check($member->user === $user && $member->notify_Count === 0, 'Authenticated user or notification state changed.');

echo 'PASS: MY_Controller guest/member initialization, request metadata, session token and success/error handling; isolated dependencies, no Oracle.' . PHP_EOL;
