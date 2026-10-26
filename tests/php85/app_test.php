<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Run separately from framework_test.php: these stubs avoid database/network access.
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
define('BASEPATH', __DIR__ . '/../../system/');
$root = dirname(__DIR__, 2);

function app_assert($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function base_url($path = '') { return 'http://localhost/gfc/' . $path; }
function date_format_exp() { return ''; }
function get_report_folder() { return ''; }

#[AllowDynamicProperties]
class MY_Controller
{
    public function __construct()
    {
        $this->input = new class {
            public $values = [];
            public function post($name) { return $this->values[$name] ?? null; }
        };
        $this->load = new class {
            public function model($name) {}
        };
        $this->session = new class {
            public $values = [];
            public function set_userdata($name, $value) { $this->values[$name] = $value; }
        };
    }
}
class MY_Model {}

require $root . '/application/controllers/Login.php';
class LoginCompatibilityProbe extends Login
{
    public $logoutCalls = 0;
    public function logout() { $this->logoutCalls++; }
}
$login = new LoginCompatibilityProbe();
app_assert($login->user_id === '' && $login->user_pwd === '', 'An initial login GET must accept absent POST values.');
$login->check_session();
$login->user = (object) [];
$login->check_session();
$login->user->id = null;
$login->check_session();
app_assert($login->logoutCalls === 0, 'Absent session user/id must not trigger logout or warnings.');
foreach (['', 0, 123] as $id) {
    $login->user->id = $id;
    $login->check_session();
}
app_assert($login->logoutCalls === 3, 'Preserve the existing isset behavior for every non-null id.');

require $root . '/application/modules/settings/controllers/Sysmenus.php';
require $root . '/application/modules/projects/models/Projects_model.php';
require $root . '/application/modules/collection_offices/controllers/Collection_companies.php';
foreach ([['Sysmenus', '_get_structure', ['parent', 'fun'], 2],
          ['Projects_model', 'get_project_items', ['sql', 'offset', 'row'], 3],
          ['Collection_companies', '_postedData_update', ['sub_ser', 'company_ser', 'type'], 3]] as $case) {
    $method = new ReflectionMethod($case[0], $case[1]);
    app_assert($method->getNumberOfRequiredParameters() === $case[3], 'Required argument count changed.');
    $names = array_map(function ($p) { return $p->getName(); }, $method->getParameters());
    app_assert(array_slice($names, 0, count($case[2])) === $case[2], 'Parameter names/order changed.');
}
$companies = (new ReflectionClass('Collection_companies'))->newInstanceWithoutConstructor();
$update = $companies->_postedData_update(null, 42, 'update');
app_assert($update[0]['value'] === null && $update[1]['value'] === 42, 'Explicit null and positional update bindings must be preserved.');
$create = $companies->_postedData_update(null, 42, 'create');
app_assert(!isset($create[0]) && $create[1]['value'] === 42, 'Create bindings must preserve the previous argument mapping.');

function view_header_values($path, $variables)
{
    extract($variables, EXTR_SKIP);
    $source = file_get_contents($path);
    $end = strpos($source, '?>');
    app_assert($end !== false, 'Expected a view PHP header.');
    eval('?>' . substr($source, 0, $end + 2));
    return get_defined_vars();
}
foreach (['gis', '28042020_gis'] as $module) {
    foreach (['change_password.php' => 'users_data', 'add_switch.php' => 'switch_data', 'add_newtransformer.php' => 'transformer_data'] as $view => $dataName) {
        $path = $root . '/application/modules/' . $module . '/views/' . $view;
        foreach ([true, false] as $isCreate) {
            foreach ([[], [['id' => 7]]] as $rows) {
                $values = view_header_values($path, ['isCreate' => $isCreate, $dataName => $rows, 'action' => 'index']);
                $expected = $isCreate || !$rows ? [] : $rows[0];
                app_assert($values['rs'] === $expected, 'GIS create/edit row selection changed.');
            }
        }
    }
}
foreach (['technical', '030416technical_delete'] as $module) {
    $path = $root . '/application/modules/' . $module . '/views/workorderassignment_show.php';
    foreach ([1, 2, 3, 4] as $state) {
        foreach (['adopt', 'feedbackRegisterAdopt', 'feedbackadopt', 'index'] as $action) {
            $values = view_header_values($path, ['result' => [['WORK_ORDER_CASE' => $state]], 'action' => $action]);
            $allowed = [1 => 'adopt', 2 => 'feedbackRegisterAdopt', 3 => 'feedbackadopt'];
            app_assert($values['CanAdopt'] === (isset($allowed[$state]) && $allowed[$state] === $action), 'Work order approval visibility changed.');
        }
    }
}
foreach (['technical/views/workorderassignment_page.php' => 'WORK_ORDER_CASE',
          '030416technical_delete/views/workorderassignment_page.php' => 'WORK_ORDER_CASE',
          'projects/views/project_close_request_page.php' => 'PROJECT_CLOSE_CASE'] as $view => $stateKey) {
    $source = file_get_contents($root . '/application/modules/' . $view);
    app_assert(preg_match('/class="btn btn-xs <\?=\s*(.*?)\s*\?>/s', $source, $match) === 1, 'Expected the status color expression.');
    // Preserve PHP 7's left-associative results, including blue for state 2.
    foreach ([-1 => 'yellow', 1 => 'yellow', 2 => 'blue', 3 => 'blue', 4 => 'red'] as $state => $expected) {
        $row = [$stateKey => $state];
        app_assert(eval('return ' . $match[1] . ';') === $expected, 'Legacy status color behavior changed.');
    }
}
echo "Application PHP compatibility checks passed.\n";
