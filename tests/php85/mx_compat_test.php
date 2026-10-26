<?php
/** Exercise the real CI/MX stack against disposable modules, never the application bootstrap. */
if (PHP_SAPI !== 'cli' || extension_loaded('oci8')) {
    fwrite(STDERR, "Run PHP CLI -n; OCI must be disabled.\n");
    exit(1);
}
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
    return false;
});
$root = dirname(__DIR__, 2);
$fixtureRoot = $root . '/temp/php85-mx-test-' . bin2hex(random_bytes(6));
mkdir($fixtureRoot, 0700, true);
define('BASEPATH', $root . '/system/');
define('APPPATH', $fixtureRoot . '/application/');
define('VIEWPATH', APPPATH . 'views/');
define('FCPATH', $fixtureRoot . '/');
define('ENVIRONMENT', 'testing');
define('UTF8_ENABLED', false);
$checks = 0;
$logs = [];
function log_message($level, $message) { $GLOBALS['logs'][] = [$level, $message]; }
function show_error($message, $status = 500, $heading = '') { throw new RuntimeException($message); }
function &get_instance() { return CI_Controller::get_instance(); }
function mx_check($condition, $message) {
    ++$GLOBALS['checks'];
    if (!$condition) throw new RuntimeException($message);
}
function mx_file($relative, $source) {
    $path = APPPATH . $relative;
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0700, true);
    file_put_contents($path, $source);
}
try {
    mx_file('config/config.php', '<?php $config = ' . var_export([
        'base_url' => 'http://localhost/offline-fixture/', 'subclass_prefix' => 'MY_',
        'enable_query_strings' => false,
        'permitted_uri_chars' => 'a-z 0-9~%.:_\-', 'url_suffix' => '',
        'charset' => 'UTF-8', 'language' => 'english', 'rewrite_short_tags' => false,
        'compress_output' => false, 'log_threshold' => 0,
    ], true) . ';');
    mx_file('config/autoload.php', '<?php $autoload = array_fill_keys(["packages", "config", "helper", "language", "drivers", "libraries", "model"], []);');
    mx_file('config/mimes.php', '<?php $mimes = [];');
    mx_file('config/routes.php', <<<'PHP'
<?php
$route['default_controller'] = 'mxfixture/screen/index';
$route['404_override'] = '';
$route['translate_uri_dashes'] = true;
PHP);
    // These wrappers load the production MX sources; the synthetic APPPATH keeps real autoload/DB out.
    mx_file('core/MY_Router.php', '<?php require_once ' . var_export($root . '/application/third_party/MX/Router.php', true) . '; class MY_Router extends MX_Router {}');
    mx_file('core/MY_Loader.php', '<?php require_once ' . var_export($root . '/application/third_party/MX/Loader.php', true) . '; class MY_Loader extends MX_Loader {}');
    mx_file('modules/mxfixture/controllers/Screen.php', <<<'PHP'
<?php
class Screen extends MX_Controller {
    public static $constructions = 0;
    public $initial;
    public $user;
    public function __construct($initial = null) {
        parent::__construct();
        self::$constructions++;
        $this->initial = $initial;
    }
    public function index() { echo 'module-index'; }
    public function show($id) { return 'record:' . $id; }
    public function render($name) { $this->load->view('card', ['name' => $name]); }
    public function returned($value) { echo 'discarded-buffer'; return $value; }
}
PHP);
    mx_file('modules/mxfixture/controllers/nested/Detail.php', '<?php class Detail extends MX_Controller { public function index() { return "nested-controller"; } }');
    mx_file('controllers/Rootprobe.php', '<?php class Rootprobe extends MX_Controller { public function index() { return "root-controller"; } }');
    mx_file('modules/mxfixture/models/Fixture_model.php', <<<'PHP'
<?php
class Fixture_model extends CI_Model {
    public function label($id) { return $this->fixture_service->prefix . ':' . $id; }
}
PHP);
    mx_file('models/Global_model.php', '<?php class Global_model extends CI_Model { public function label() { return "global-model"; } }');
    mx_file('modules/secondary/models/Secondary_model.php', '<?php class Secondary_model extends CI_Model { public function label() { return "secondary-model"; } }');
    mx_file('modules/mxfixture/libraries/Fixture_service.php', '<?php class Fixture_service { public $prefix; public function __construct($config) { $this->prefix = $config["prefix"]; } }');
    mx_file('modules/mxfixture/config/fixture_service.php', '<?php $config = ["prefix" => "module"];');
    mx_file('modules/mxfixture/config/feature.php', '<?php $config = ["enabled" => true, "fixture_title" => "اختبار"];');
    mx_file('modules/mxfixture/config/autoload.php', '<?php $autoload = ["helper" => ["mx_fixture"]];');
    mx_file('modules/mxfixture/config/routes.php', <<<'PHP'
<?php
$route['mxfixture/item/(:num)'] = 'screen/show/$1';
$route['mxfixture/action'] = ['get' => 'screen/read', 'post' => 'screen/write'];
PHP);
    mx_file('modules/mxfixture/helpers/mx_fixture_helper.php', '<?php function mx_fixture_label($value) { return "helper:" . $value; }');
    mx_file('modules/mxfixture/language/english/fixture_lang.php', '<?php $lang = ["fixture_greeting" => "Hello fixture"];');
    mx_file('modules/mxfixture/views/card.php', '<span><?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?>|<?= $this->fixture_model->label(7) ?>|<?= mx_fixture_label("ok") ?></span>');
    mx_file('modules/mxfixture/views/identity.php', '<?php echo json_encode([$this->user->id, $this->user->branch, isset($this->user->id), !empty($this->user->id)]);');
    mx_file('views/fallback.php', '<div>global:<?= $value ?></div>');

    require BASEPATH . 'core/Common.php';
    $_SERVER['argv'] = ['mx_compat_test.php', 'mxfixture', 'item', '42'];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $CFG =& load_class('Config', 'core');
    $LANG =& load_class('Lang', 'core');
    $URI =& load_class('URI', 'core');
    $RTR =& load_class('Router', 'core');
    $OUT =& load_class('Output', 'core');
    require BASEPATH . 'core/Controller.php';
    require BASEPATH . 'core/Model.php';
    // Base.php creates the real CI super-object using only the fixture configuration above.
    require $root . '/application/third_party/MX/Controller.php';
    $app = CI::$APP;
    mx_check($app instanceof CI_Controller && get_instance() === $app, 'CI singleton was not installed by MX Base.');
    mx_check($app->load instanceof MX_Loader && $app->router instanceof MX_Router, 'MX Loader/Router extensions were not selected.');
    mx_check($app->config instanceof MX_Config && $app->lang instanceof MX_Lang, 'MX Config/Lang references were lost.');
    mx_check($RTR->fetch_module() === 'mxfixture' && $RTR->fetch_class() === 'screen' && $RTR->fetch_method() === 'show', 'Module numeric route did not resolve.');
    mx_check($URI->rsegment(3) === '42', 'Module route parameter was not preserved.');
    mx_check(mx_fixture_label('autoload') === 'helper:autoload', 'Module autoload helper was not loaded.');

    $controller = Modules::load(['mxfixture/screen' => ['fixture' => 1]]);
    mx_check($controller instanceof Screen && $controller->initial === ['fixture' => 1], 'Module constructor arguments were lost.');
    mx_check($controller->load !== $app->load && $controller->load instanceof MX_Loader, 'Controller loader was not cloned.');
    mx_check(Modules::load('mxfixture/screen') === $controller && Screen::$constructions === 1, 'Module registry did not reuse its controller.');
    mx_check(Modules::run('mxfixture/screen/index') === 'module-index', 'HMVC buffered output changed.');
    mx_check(Modules::run('mxfixture/screen/show', '00123') === 'record:00123', 'HMVC method argument or return changed.');
    foreach ([0, false, ''] as $value) mx_check(Modules::run('mxfixture/screen/returned', $value) === $value, 'Falsey explicit returns must win over buffered output.');
    mx_check(Modules::run('mxfixture/screen/missing_method') === null, 'Missing method must fail without executing another action.');

    $controller->load->library('fixture_service');
    mx_check($controller->fixture_service->prefix === 'module', 'Module library config/default alias was not applied.');
    $controller->load->model('fixture_model');
    mx_check($controller->fixture_model instanceof CI_Model && $controller->fixture_model->label('001') === 'module:001', 'Module model/global dependency injection changed.');
    $originalModel = $controller->fixture_model;
    $controller->load->model('fixture_model');
    mx_check($controller->fixture_model === $originalModel, 'Loading an existing model created a second instance.');
    $controller->load->model('secondary/secondary_model', 'other_model');
    mx_check($controller->other_model->label() === 'secondary-model', 'Explicit cross-module model alias failed.');
    $controller->load->model('global_model', 'global_fixture');
    mx_check($controller->global_fixture->label() === 'global-model', 'Fallback to the CI application model loader failed.');
    mx_check($controller->load->view('card', (object)['name' => 'A&B'], true) === '<span>A&amp;B|module:7|helper:ok</span>', 'Module view variables or dependency access changed.');
    // Module views execute inside MX_Loader and must resolve their owning controller's user.
    $app->user = (object)['id' => 999, 'branch' => 1];
    $controller->user = (object)['id' => 42, 'branch' => 5];
    mx_check($controller->load->view('identity', [], true) === '[42,5,true,true]', 'View borrowed the core identity or lost nested isset/empty checks.');
    $controller->user = (object)['id' => 0, 'branch' => 0];
    mx_check($controller->load->view('identity', [], true) === '[0,0,true,false]', 'Guest view inherited the core identity.');
    $controller->user = (object)['id' => 7, 'branch' => 2];
    mx_check($controller->load->view('identity', [], true) === '[7,2,true,true]', 'View retained a previous user or branch.');
    unset($app->user);
    mx_check(Modules::run('mxfixture/screen/render', 'nested') === '<span>nested|module:7|helper:ok</span>', 'HMVC nested view buffering changed.');
    mx_check($controller->load->view('fallback', ['value' => 'ok'], true) === '<div>global:ok</div>', 'Application view fallback failed.');
    $controller->load->view('card', ['name' => 'output']);
    mx_check($OUT->get_output() === '<span>output|module:7|helper:ok</span>', 'Unbuffered view did not append to CI Output.');
    $controller->load->config('feature', true);
    mx_check($CFG->item('enabled', 'feature') === true && $CFG->item('fixture_title', 'feature') === 'اختبار', 'Module config sections changed.');
    $controller->load->language('fixture', 'english');
    mx_check($LANG->line('fixture_greeting') === 'Hello fixture', 'Module language resolution failed.');

    mx_check(Modules::parse_routes('mxfixture', 'mxfixture/action') === ['mxfixture', 'screen', 'read'], 'GET module route changed.');
    $_SERVER['REQUEST_METHOD'] = 'POST';
    mx_check(Modules::parse_routes('mxfixture', 'mxfixture/action') === ['mxfixture', 'screen', 'write'], 'POST module route changed.');
    $_SERVER['REQUEST_METHOD'] = 'DELETE';
    mx_check(Modules::parse_routes('mxfixture', 'mxfixture/action') === null, 'A route with an unsupported HTTP verb was selected.');
    unset($_SERVER['REQUEST_METHOD']);
    mx_check(Modules::parse_routes('mxfixture', 'mxfixture/action') === null, 'Missing request method must use the CLI route branch.');
    mx_check(Modules::run('mxfixture/nested/detail/index') === 'nested-controller', 'Nested module controller resolution failed.');
    mx_check(Modules::run('rootprobe/index') === 'root-controller', 'Root controller lookup after a module retained the module directory.');
    mx_check(Modules::find('absent', 'mxfixture', 'models/') === [false, 'absent'], 'Missing model resolution changed.');
    mx_check(Modules::load('missing_module/missing_controller') === null, 'A missing module should not instantiate a controller.');
    $URI->uri_string = '';
    $URI->segments = [];
    $defaultRouter = new MY_Router();
    mx_check($defaultRouter->fetch_module() === 'mxfixture' && $defaultRouter->fetch_class() === 'screen'
        && $defaultRouter->fetch_method() === 'index', 'Default module/controller route failed.');
    $URI->uri_string = 'mxfixture/screen/my-method';
    $URI->segments = [1 => 'mxfixture', 2 => 'screen', 3 => 'my-method'];
    $dashRouter = new MY_Router();
    mx_check($dashRouter->fetch_method() === 'my_method', 'Module method dash translation changed.');
    mx_check(!class_exists('DBConn', false) && !class_exists('CI_DB', false), 'A database component was loaded by the fixture.');
    $applicationPrefix = str_replace('\\', '/', $root . '/application/');
    $mxPrefix = $applicationPrefix . 'third_party/MX/';
    foreach (get_included_files() as $included) {
        $path = str_replace('\\', '/', $included);
        mx_check(strncmp($path, $applicationPrefix, strlen($applicationPrefix)) !== 0
            || strncmp($path, $mxPrefix, strlen($mxPrefix)) === 0, 'Real application code escaped fixture isolation: ' . $path);
    }
} finally {
    // Delete only this test-created tree after verifying its resolved workspace boundary.
    $resolved = realpath($fixtureRoot);
    $prefix = realpath($root . '/temp') . DIRECTORY_SEPARATOR . 'php85-mx-test-';
    if ($resolved !== false && strncmp($resolved, $prefix, strlen($prefix)) === 0) {
        $entries = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($resolved, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($entries as $entry) $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        rmdir($resolved);
    }
}
echo 'PASS: ' . $checks . ' CI/MX module routing, controller registry, loader/model/view/config/language checks under E_ALL; synthetic application, OCI disabled.' . PHP_EOL;
