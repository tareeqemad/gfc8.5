<?php
/** Real CI/MX loader + MY_Controller, with disposable modules and an inert DBConn. */
if (PHP_SAPI !== 'cli' || extension_loaded('oci8')) {
    fwrite(STDERR, "Run PHP CLI -n; OCI must be disabled.\n");
    exit(1);
}
$scenarios = ['guest', 'member', 'preloaded', 'defined', 'module_override'];
if (!isset($argv[1])) {
    foreach ($scenarios as $scenario) {
        $pipes = [];
        $process = proc_open([PHP_BINARY, '-n', __FILE__, $scenario], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) throw new RuntimeException('Could not start isolated test process.');
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);
        echo $output;
        if ($errors !== '') fwrite(STDERR, $errors);
        if ($status !== 0) exit($status);
    }
    echo "PASS: all isolated controller connection scenarios; real CI/MX, OCI disabled, no database/network.\n";
    exit;
}
$scenario = $argv[1];
if (!in_array($scenario, $scenarios, true)) throw new InvalidArgumentException('Unknown scenario.');
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
    return false;
});
$root = dirname(__DIR__, 2);
$fixtureRoot = $root . '/temp/php85-controller-connection-' . bin2hex(random_bytes(6));
mkdir($fixtureRoot, 0700, true);
define('BASEPATH', $root . '/system/');
define('APPPATH', $fixtureRoot . '/application/');
define('VIEWPATH', APPPATH . 'views/');
define('FCPATH', $fixtureRoot . '/');
define('EXT', '.php');
define('ENVIRONMENT', 'testing');
define('UTF8_ENABLED', false);
$checks = 0;
function log_message($level, $message) {}
function show_error($message, $status = 500, $heading = '') { throw new RuntimeException($message); }
function &get_instance() { return CI_Controller::get_instance(); }
function base_url($path = '') { return 'http://localhost/offline-fixture/' . $path; }
function current_url() { return base_url('login'); }
function redirect(...$args) { throw new RuntimeException('Unexpected redirect in connection fixture.'); }
function connection_check($condition, $message) {
    ++$GLOBALS['checks'];
    if (!$condition) throw new RuntimeException($GLOBALS['scenario'] . ': ' . $message);
}
function connection_file($relative, $source) {
    $path = APPPATH . $relative;
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0700, true);
    file_put_contents($path, $source);
}
class ConnectionFixtureSession {
    public $values = ['__AntiForgeryToken' => 'fixture-token', 'db_instance' => 'fixture-A'];
    public function userdata($key) { return $this->values[$key] ?? null; }
    public function set_userdata($key, $value) { $this->values[$key] = $value; }
    public function unset_userdata($key) { unset($this->values[$key]); }
    public function all_userdata() { return $this->values; }
    public function sess_update() {}
}
try {
    connection_file('config/config.php', '<?php $config = ' . var_export([
        'base_url' => 'http://localhost/offline-fixture/', 'subclass_prefix' => 'MY_',
        'enable_query_strings' => false, 'permitted_uri_chars' => 'a-z 0-9~%.:_\-',
        'url_suffix' => '', 'charset' => 'UTF-8', 'language' => 'english',
        'rewrite_short_tags' => false, 'compress_output' => false, 'log_threshold' => 0,
    ], true) . ';');
    connection_file('config/autoload.php', '<?php $autoload = array_fill_keys(["packages", "config", "helper", "language", "drivers", "libraries", "model"], []);');
    connection_file('config/mimes.php', '<?php $mimes = [];');
    connection_file('config/routes.php', '<?php $route = ["default_controller" => "fixture/login/index", "404_override" => "", "translate_uri_dashes" => true];');
    connection_file('core/MY_Router.php', '<?php require_once ' . var_export($root . '/application/third_party/MX/Router.php', true) . '; class MY_Router extends MX_Router {}');
    connection_file('core/MY_Loader.php', '<?php require_once ' . var_export($root . '/application/third_party/MX/Loader.php', true) . '; class MY_Loader extends MX_Loader {}');
    connection_file('modules/fixture/controllers/Login.php', <<<'PHP'
<?php
class Login extends MY_Controller {
    public function nested() { return Modules::load('fixture/inner'); }
}
PHP);
    connection_file('modules/fixture/controllers/Inner.php', '<?php class Inner extends MY_Controller {}');
    $dbStub = <<<'PHP'
<?php
class DBConn {
    public static $constructions = 0;
    public $serial;
    public $identity;
    public $params;
    public $calls = [];
    public function __construct($params = null) {
        $this->serial = ++self::$constructions;
        $session = get_instance()->session;
        $user = $session->userdata('user_data');
        $this->identity = [$user ? $user->id : 0, $user ? $user->username : '', $session->userdata('db_instance')];
        $this->params = $params;
    }
}
PHP;
    connection_file('libraries/DBConn.php', $dbStub);
    if ($scenario === 'module_override') {
        connection_file('modules/fixture/libraries/DBConn.php', $dbStub);
        connection_file('modules/fixture/config/dbconn.php', '<?php $config = ["source" => "module-config"];');
    }
    connection_file('libraries/User_agent.php', '<?php class User_agent { public function browser() { return "Chrome"; } public function version() { return "130.0"; } }');
    connection_file('modules/settings/models/User_menus_model.php', <<<'PHP'
<?php
class User_menus_model extends MY_Model {
    public $checks = [];
    public function check_permission($user, $one, $two) { $this->checks[] = [$user, $one, $two]; return 1; }
    public function connection() { return $this->conn; }
}
PHP);
    connection_file('modules/settings/models/System_info_model.php', <<<'PHP'
<?php
class System_info_model extends MY_Model {
    public $notifications = [];
    public function user_notification_update($user, $url) { $this->notifications[] = [$user, $url]; }
    public function get_table_count($sql) { return [['NUM_ROWS' => 0]]; }
}
PHP);
    connection_file('modules/settings/models/Sys_sessions_model.php', <<<'PHP'
<?php
class Sys_sessions_model extends MY_Model {
    public $statusChecks = [];
    public $updates = [];
    public function get_status($user) { $this->statusChecks[] = $user; return [['STATUS' => 1]]; }
    public function create_edit($params) { $this->updates[] = $params; return 1; }
}
PHP);
    connection_file('modules/fixture/models/Nested_model.php', '<?php class Nested_model extends MY_Model { public function connection() { return $this->conn; } }');

    require BASEPATH . 'core/Common.php';
    $_SERVER['argv'] = ['controller_connection_test.php', 'fixture', 'login', 'index'];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = $_POST = [];
    unset($_SERVER['QUERY_STRING'], $_SERVER['HTTP_USER_AGENT']);
    $CFG =& load_class('Config', 'core');
    $LANG =& load_class('Lang', 'core');
    $URI =& load_class('URI', 'core');
    $RTR =& load_class('Router', 'core');
    $OUT =& load_class('Output', 'core');
    require BASEPATH . 'core/Controller.php';
    require BASEPATH . 'core/Model.php';
    require $root . '/application/third_party/MX/Controller.php';
    require $root . '/application/core/Oracle_error.php';
    require $root . '/application/core/User.php';
    require $root . '/application/core/MY_Controller.php';
    require $root . '/application/core/MY_Model.php';
    require $root . '/application/helpers/my_security_helper.php';
    $app = CI::$APP;
    $app->session = new ConnectionFixtureSession();
    $app->input = new class { public function is_ajax_request() { return false; } };
    $user = new User();
    $user->id = 42;
    $user->branch = 3;
    $user->username = 'fixture-user-A';
    if ($scenario !== 'guest') {
        $app->session->values['user_data'] = $user;
        $app->session->values['system_id'] = 1;
    }
    $preloaded = null;
    if ($scenario === 'preloaded') {
        $app->load->library('DBConn');
        $preloaded = $app->dbconn;
        $preloaded->calls[] = 'already-used';
    } elseif ($scenario === 'defined') {
        require APPPATH . 'libraries/DBConn.php';
    }
    $controller = Modules::load('fixture/login');
    $expectedCount = in_array($scenario, ['guest', 'member'], true) ? 1 : 2;
    connection_check(DBConn::$constructions === $expectedCount,
        'Expected ' . $expectedCount . ' connection construction(s), got ' . DBConn::$constructions . '.');
    connection_check($controller instanceof MY_Controller && $controller->load instanceof MX_Loader, 'Real controller/loader stack was not used.');
    connection_check(isset($app->dbconn) && $controller->dbconn === $app->dbconn, 'Loader library alias was lost.');
    connection_check($controller->user_menus_model->connection() === $controller->conn, 'Model did not bind to its owning controller connection.');
    connection_check($controller->AntiForgeryToken() === 'fixture-token', 'Session token changed.');
    connection_check(count($controller->user_menus_model->checks) === 1, 'Controller permission check was skipped or duplicated.');
    connection_check(count($controller->system_info_model->notifications) === 1, 'Notification update was skipped or duplicated.');
    connection_check($controller->conn->identity === [$scenario === 'guest' ? 0 : 42, $scenario === 'guest' ? '' : 'fixture-user-A', 'fixture-A'], 'Connection used the wrong user/database context.');
    if ($expectedCount === 1) {
        connection_check($controller->conn === $app->dbconn, 'Fresh unused loader connection was not reused.');
    } else {
        connection_check($controller->conn !== $app->dbconn, 'Existing/overridden connection was reused unexpectedly.');
    }
    if ($scenario !== 'guest') {
        connection_check($controller->user === $user, 'Authenticated identity changed.');
        connection_check($controller->sys_sessions_model->statusChecks === [42], 'Session status check changed.');
        connection_check(count($controller->sys_sessions_model->updates) === 1, 'Session audit update changed.');
    }
    if ($scenario === 'preloaded') {
        connection_check($app->dbconn === $preloaded && $preloaded->calls === ['already-used'], 'Preloaded library was replaced or changed.');
    }
    if ($scenario === 'module_override') {
        connection_check($app->dbconn->params === ['source' => 'module-config'], 'Module config resolution changed.');
        connection_check($controller->conn->params === null, 'Direct connection unexpectedly adopted module library parameters.');
    }
    if ($scenario === 'member') {
        $originalConnection = $controller->conn;
        $next = new User();
        $next->id = 7;
        $next->branch = 6;
        $next->username = 'fixture-user-B';
        $app->session->values['user_data'] = $next;
        $app->session->values['db_instance'] = 'fixture-B';
        $nested = $controller->nested();
        connection_check(DBConn::$constructions === 2, 'Nested controller did not create exactly one fresh connection.');
        connection_check($nested->conn !== $originalConnection && $nested->conn !== $app->dbconn, 'Nested controller borrowed another connection.');
        connection_check($controller->conn === $originalConnection && $app->dbconn === $originalConnection, 'Nested construction replaced the original connection or library alias.');
        connection_check($nested->conn->identity === [7, 'fixture-user-B', 'fixture-B'], 'Nested connection used stale user/database context.');
        connection_check($nested->user === $next, 'Nested authenticated identity was stale.');
        $nested->load->model('fixture/nested_model');
        connection_check($nested->nested_model->connection() === $nested->conn, 'New nested model bound to a different controller connection.');
        connection_check(Modules::load('fixture/inner') === $nested && DBConn::$constructions === 2, 'Reusing the MX registry created another connection.');
        connection_check(count($nested->user_menus_model->checks) === 2, 'Nested endpoint permission check was not preserved.');
        connection_check(count($nested->sys_sessions_model->updates) === 2, 'Nested session audit update was not preserved.');
    }
    foreach (get_included_files() as $included) {
        connection_check(str_replace('\\', '/', $included) !== str_replace('\\', '/', $root . '/application/libraries/DBConn.php'), 'Real Oracle connection library escaped fixture isolation.');
    }
} finally {
    $resolved = realpath($fixtureRoot);
    $prefix = realpath($root . '/temp') . DIRECTORY_SEPARATOR . 'php85-controller-connection-';
    if ($resolved !== false && strpos($resolved, $prefix) === 0) {
        $entries = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($resolved, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($entries as $entry) $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        rmdir($resolved);
    }
}
echo 'PASS: ' . $scenario . ' (' . $checks . ' checks), PHP ' . PHP_VERSION . PHP_EOL;
