<?php
// Isolated framework checks: no Oracle connection or application writes.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return false;
});

define('BASEPATH', dirname(__DIR__, 2).'/system/');
define('APPPATH', dirname(__DIR__, 2).'/application/');
define('VIEWPATH', APPPATH.'views/');
define('ENVIRONMENT', 'testing');
define('UTF8_ENABLED', false);

$settings = array(
    'enable_query_strings' => false, 'permitted_uri_chars' => 'a-z 0-9~%.:_\-',
    'subclass_prefix' => 'MY_', 'sess_expiration' => 7200, 'sess_cookie_name' => 'php85_test',
    'cookie_path' => '/', 'cookie_domain' => '', 'cookie_secure' => false,
    'sess_match_ip' => false, 'sess_save_path' => sys_get_temp_dir(),
    'charset' => 'UTF-8', 'global_xss_filtering' => false,
    'csrf_protection' => false, 'standardize_newlines' => false,
);
$registry = array();
function config_item($name) { global $settings; return isset($settings[$name]) ? $settings[$name] : null; }
function is_php($version) { return version_compare(PHP_VERSION, $version, '>='); }
function is_cli() { return true; }
function log_message($level, $message) {}
function &is_loaded() { static $loaded = array(); return $loaded; }
function &load_class($name, $directory = 'libraries') { global $registry; return $registry[$name]; }
function &get_instance() { return CI_Controller::get_instance(); }
function check($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
require BASEPATH.'core/Common.php';

class TestConfig {
    public function item($name) { return config_item($name); }
}
$registry['Config'] = new TestConfig();
require BASEPATH.'core/URI.php';
$_SERVER['argv'] = array('framework_test.php', 'login', 'index');
$registry['URI'] = new CI_URI();
check($registry['URI']->segment(1) === 'login', 'CLI URI parsing');
require BASEPATH.'core/Router.php';
$router = new CI_Router();
check($router->uri === $registry['URI'], 'Router URI injection');

require BASEPATH.'core/Loader.php';
class TestLoader extends CI_Loader {
    public function initialize() {}
    public function language($files, $lang = '') { return $this; }
}
$registry['Loader'] = new TestLoader();
require BASEPATH.'core/Controller.php';
require BASEPATH.'core/Model.php';
$controller = new CI_Controller();
$controller->test_service = new stdClass();
$registry['Loader']->test_service = $controller->test_service;
$model = new CI_Model();
$model->test_dependency = $controller->test_service;
check($model->test_service === $controller->test_service, 'Model CI dependency access');
require BASEPATH.'core/Exceptions.php';
$exceptions = new CI_Exceptions();
check($exceptions->levels[E_WARNING] === 'Warning', 'Exception level map');
require APPPATH.'core/MY_Class.php';
$row = new MY_Class(array('EMP_NO' => 123, 'EMP_NAME' => 'Test'));
check($row->EMP_NO === 123, 'Dynamic query result columns');
require APPPATH.'core/User.php';
$user = new User();
$user->emp_no = 123;
$user->dep_side = 4;
$user->conflict_interest = 0;
check(unserialize(serialize($user))->emp_no === 123, 'Authenticated user fields survive session serialization');
require BASEPATH.'libraries/Profiler.php';
$profiler = new CI_Profiler(array('queries' => false));
$profiler->set_sections(array('queries' => true, 'config' => false));
require BASEPATH.'libraries/Table.php';
$table = new CI_Table(array('table_open' => '<table class="test">'));
$tableHtml = $table->generate(array(array('Heading'), array('Value')));
check(strpos($tableHtml, '<table class="test">') !== false && strpos($tableHtml, 'Value') !== false, 'Partial table template');

require BASEPATH.'libraries/Session/Session.php';
require BASEPATH.'libraries/Session/CI_Session_driver_interface.php';
require BASEPATH.'libraries/Session/Session_driver.php';
require BASEPATH.'libraries/Session/PHP8SessionWrapper.php';
foreach (array('files', 'database', 'redis', 'memcached') as $name) {
    require BASEPATH.'libraries/Session/drivers/Session_'.$name.'_driver.php';
}
class TestSessionConfiguration extends CI_Session {
    public function __construct() {}
    public function configureForTest(&$params) { $this->_configure($params); return $this->_sid_regexp; }
}
$params = array();
$session = new TestSessionConfiguration();
$sidPattern = $session->configureForTest($params);
$sessionId = session_create_id();
check(preg_match('/^'.$sidPattern.'$/', $sessionId) === 1, 'Generated session ID matches validator');

$directory = sys_get_temp_dir().'/gfc-php85-session-'.bin2hex(random_bytes(6));
mkdir($directory);
$params = array('save_path' => $directory, 'cookie_name' => 'php85_test',
    'cookie_path' => '/', 'cookie_domain' => '', 'cookie_secure' => false,
    'cookie_samesite' => 'Lax', 'match_ip' => false, '_sid_regexp' => $sidPattern);
$driver = new CI_Session_files_driver($params);
try {
    check($driver->open($directory, 'php85_test') === true, 'Session open');
    check($driver->read($sessionId) === '', 'Empty session read');
    check($driver->write($sessionId, 'user|s:4:"test";') === true, 'Session write');
    check($driver->close() === true, 'Session close');
    check($driver->read($sessionId) === 'user|s:4:"test";', 'Session round trip');
    check($driver->close() === true, 'Session close after read');
    check($driver->validateId($sessionId), 'Existing session validation');
    check($driver->destroy($sessionId) === true, 'Session destroy and cookie expiration');
    check(!$driver->validateId($sessionId), 'Destroyed session absent');
    check($driver->gc(7200) !== false, 'Session garbage collection');

    // Exercise the CI 3.1.13 wrapper through PHP itself, not only direct driver calls.
    $nativeDriver = new CI_Session_files_driver($params);
    $nativeWrapper = new CI_SessionWrapper($nativeDriver);
    check(session_set_save_handler($nativeWrapper, true), 'Register PHP 8 session wrapper');
    ini_set('session.use_strict_mode', '1');
    $unknownSessionId = session_create_id();
    session_id($unknownSessionId);
    check(session_start(), 'Native PHP session starts through wrapper');
    $nativeSessionId = session_id();
    check($nativeSessionId !== $unknownSessionId, 'Strict mode rejects an unrecognized session ID');
    $_SESSION['employee'] = array('id' => '001234', 'name' => 'كهرباء');
    check(session_write_close(), 'Native PHP session write and close');
    check($nativeWrapper->validateId($nativeSessionId), 'Wrapper validates the persisted session ID');

    $sessionFiles = glob($directory.'/*');
    check(count($sessionFiles) === 1, 'Native session stored in the isolated fixture directory');
    $nativeSessionFile = $sessionFiles[0];
    $nativeSessionContents = file_get_contents($nativeSessionFile);
    $oldTimestamp = time() - 120;
    touch($nativeSessionFile, $oldTimestamp);
    check($nativeWrapper->updateTimestamp($nativeSessionId, $nativeSessionContents), 'Wrapper updates an unchanged session timestamp');
    clearstatcache(true, $nativeSessionFile);
    check(filemtime($nativeSessionFile) > $oldTimestamp, 'Session timestamp refreshed');
    check(file_get_contents($nativeSessionFile) === $nativeSessionContents, 'Timestamp refresh preserves stored session data');

    session_id($nativeSessionId);
    $_SESSION = array();
    check(session_start(), 'Native session reopens through wrapper');
    check($_SESSION['employee'] === array('id' => '001234', 'name' => 'كهرباء'), 'Native session round trip preserves Arabic and leading zeros');
    check(session_destroy(), 'Native session destroy through wrapper');
    check(!$nativeWrapper->validateId($nativeSessionId), 'Native session record removed');
} finally {
    if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
    if (isset($nativeDriver)) { $nativeDriver->close(); }
    foreach (glob($directory.'/*') as $file) { unlink($file); }
    rmdir($directory);
}

require BASEPATH.'libraries/Upload.php';
class TestUploadMime extends CI_Upload {
    public function __construct() {}
    public function detect($file) { $this->_file_mime_type(array('tmp_name' => $file)); return $this->file_type; }
}
if (extension_loaded('fileinfo')) {
    $upload = new TestUploadMime();
    check($upload->detect(__FILE__) === 'text/x-php', 'Fileinfo object MIME detection');
}

if (extension_loaded('gd')) {
    require BASEPATH.'libraries/Image_lib.php';
    $imageDirectory = sys_get_temp_dir().'/gfc-php85-image-'.bin2hex(random_bytes(6));
    mkdir($imageDirectory);
    try {
        $source = imagecreatetruecolor(8, 8);
        imagepng($source, $imageDirectory.'/source.png');
        unset($source);
        $image = new CI_Image_lib(array('source_image' => $imageDirectory.'/source.png',
            'new_image' => $imageDirectory.'/resized.png', 'width' => 4, 'height' => 4));
        check($image->resize(), 'GD image resize');
        $dimensions = getimagesize($imageDirectory.'/resized.png');
        check($dimensions[0] === 4 && $dimensions[1] === 4, 'Resized image dimensions');
    } finally {
        foreach (glob($imageDirectory.'/*') as $file) { unlink($file); }
        rmdir($imageDirectory);
    }
}

echo 'PASS: framework bootstrap, dependency injection, PHP 8 session wrapper/strict IDs/timestamps/round trip, profiler/table, MIME and GD (PHP '.PHP_VERSION.")\n";
