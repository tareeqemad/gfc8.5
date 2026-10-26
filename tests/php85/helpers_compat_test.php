<?php
// Real helpers, isolated data/loader fixtures, and no application or Oracle bootstrap.
if (PHP_SAPI !== 'cli' || extension_loaded('oci8')) {
    fwrite(STDERR, "Run with PHP CLI -n; OCI must be disabled.\n");
    exit(1);
}
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$root = dirname(__DIR__, 2);
define('BASEPATH', $root . '/system/');
$checks = 0;
$failures = [];
function helper_check($name, $callback, $expected) {
    global $checks, $failures;
    ++$checks;
    try {
        $actual = $callback();
        if ($actual !== $expected) throw new RuntimeException('Unexpected result: ' . var_export($actual, true));
    } catch (Throwable $error) {
        $failures[] = $name . ': ' . $error->getMessage();
    }
}
class HelperFixtureConfig {
    public $items = ['scripts' => '', 'header_js' => [], 'header_css' => []];
    public function item($key) { return $this->items[$key] ?? null; }
    public function set_item($key, $value) { $this->items[$key] = $value; }
}
class MY_Controller {
    public static $fixture;
    public static function &get_instance() { return self::$fixture; }
}
function &get_instance() { return MY_Controller::$fixture; }
function base_url($path = '') { return 'http://localhost/gfc/' . ltrim($path, '/'); }
function helper_fixture_callback($level, $alternate, $id) { return null; }
MY_Controller::$fixture = new class {
    public $config;
    public $load;
    public $session;
    public $user;
    public $notify_Count = 7;
    public $database_name = 'GFC';
    public $user_menus_model;
    public $payroll_access_model;
    public $rows = [];
    public function get_user_info($id) { return $this->rows; }
};
$ci = get_instance();
$ci->config = new HelperFixtureConfig();
$ci->load = new class {
    public function driver($name) { if ($name !== 'minify') throw new RuntimeException('Unexpected driver'); }
    public function library($name) { if ($name !== 'session') throw new RuntimeException('Unexpected library'); }
    public function model($name) { if ($name !== 'payroll_data/payroll_access_model') throw new RuntimeException('Unexpected model'); }
};
$ci->session = new class { public function userdata($key) { return $key === '__AntiForgeryToken' ? 'fixture-token' : null; } };
$ci->user = (object)['id' => 42];
$ci->user_menus_model = new class {
    public $allowed = 1;
    public $args = [];
    public function check_permission(...$args) { $this->args = $args; return $this->allowed; }
};
$ci->payroll_access_model = new class {
    public $checked = null;
    public function is_allowed($id) { $this->checked = $id; return true; }
};
foreach (glob($root . '/application/helpers/*_helper.php') as $helper) require $helper;
require $root . '/application/modules/payment_req/helpers/payment_req_report_helper.php';

foreach ([
    ['short nullable name', fn() => get_short_user_name(null), ' '],
    ['short full name', fn() => get_short_user_name('  أحمد محمد حسن  '), 'أحمد حسن'],
    ['null identity', fn() => check_identity(null), false],
    ['numeric identity', fn() => check_identity(123456782), true],
    ['bad identity checksum', fn() => check_identity('123456789'), false],
    ['null search', fn() => add_percent_sign(null), '%%'],
    ['search words', fn() => add_percent_sign(' أحمد محمد '), '%أحمد%محمد%'],
    ['null date', fn() => char_to_time(null), false],
    ['display date', fn() => char_to_time('22/10/2026'), strtotime('2026-10-22')],
    ['null spacer', fn() => replace_d_dsh(null), '&nbsp;'],
    ['spacer width', fn() => replace_d_dsh('12'), '&nbsp;&nbsp;&nbsp;'],
    ['null number', fn() => n_format(null), '0.00'],
    ['numeric string format', fn() => n_format('1234.5'), '1,234.50'],
    ['negative format', fn() => n_format(-1234.5), '-1,234.50'],
    ['null decimal', fn() => remove_decimal(null), ''],
    ['decimal separators', fn() => remove_decimal('1,234.50'), '1234.50'],
    ['null URL', fn() => clear_url(null), ''],
    ['file URL', fn() => clear_url('my file(1).pdf'), 'my_file_1_.pdf'],
    ['default planning case', fn() => project_case(3), 'التخطيط'],
    ['default planning manager', fn() => project_case(4), 'مدير التخطيط'],
    ['SCADA case', fn() => project_case(3, 'ISC-1'), 'السكادا'],
    ['SCADA manager', fn() => project_case(4, 'ISC-1'), 'مدير التحكم و التشغيل'],
    ['null sort', fn() => sort_dir('ID', null), null],
    ['descending sort', fn() => sort_dir('ID', 'ID DESC'), 'desc'],
    ['ascending sort', fn() => sort_dir('ID', 'id asc'), 'asc'],
    ['empty suffix', fn() => endsWith(null, null), true],
    ['matched suffix', fn() => endsWith('abc', 'bc'), true],
    ['long suffix', fn() => endsWith('a', 'ba'), false],
    ['zero case decoding', fn() => encryption_case(0), 1],
    ['null case decoding', fn() => encryption_case(null), 1],
    ['encoded case decoding', fn() => encryption_case(123456), 4],
    ['Arabic money', fn() => convertNumberToArabicWords(1250.75), 'ألف ومئتان وخمسون شيكل وسبعون وخمسة أغورة'],
    ['date range', fn() => getDatesFromRange('2026-10-22', '2026-10-23'), ['2026-10-22', '2026-10-23']],
    ['empty tree', fn() => generate_list([], [], '<li>{NAME}</li>'), ''],
    ['nullable tree cell', fn() => generate_list([['NAME' => null]], [], '<li>{NAME}</li>'), '<li></li>'],
    ['nullable scalar tree cell', fn() => generate_list([null], [], '<li>{CONTENT}</li>'), '<li></li>'],
    ['nullable tree callback', fn() => generate_list([['id' => 1]], ['functions' => [['helper_fixture_callback', 'helper_fixture_callback']]], '<li>{HELPER_FIXTURE_CALLBACK}</li>'), '<li></li>'],
    ['nested tree', fn() => generate_list([['NAME' => 'A', 'children' => [['NAME' => 'B']]]], [], '<li>{NAME}{SUBS}</li>'), '<li>A<li>B</li></li>'],
    ['empty user result', fn() => get_user_name(42), ''],
    ['CSRF token', fn() => AntiForgeryToken(), '<input name="__AntiForgeryToken" type="hidden" value="fixture-token" />'],
    ['current user', fn() => get_curr_user()->id, 42],
    ['notifications', fn() => get_notify_count(), 7],
    ['permission allow', fn() => HaveAccess(base_url('x/y/')), true],
    ['permission arguments', fn() => $ci->user_menus_model->args, [42, 'x/y', 'x/y/']],
    ['report nullable money', fn() => payment_req_report_cents(null), null],
    ['report cents', fn() => payment_req_report_cents('12.35'), 1235],
    ['report date cell', fn() => payment_req_report_cell(['M' => '202610'], ['field' => 'M', 'kind' => 'month'], 1), '10/2026'],
    ['report null text', fn() => payment_req_report_cell(['N' => null], ['field' => 'N', 'kind' => 'text'], 1), ''],
] as [$name, $callback, $expected]) helper_check($name, $callback, $expected);
$ci->rows = [['USER_NAME' => null]];
helper_check('nullable user name', fn() => get_user_name(42), ' ');
$ci->rows = [['USER_NAME' => 'أحمد محمد حسن']];
helper_check('user name', fn() => get_user_name(42), 'أحمد حسن');
$ci->user_menus_model->allowed = 0;
helper_check('permission deny', fn() => HaveAccess('x/y'), false);
helper_check('payroll allow', fn() => require_payroll_access(), null);
helper_check('payroll checked user', fn() => $ci->payroll_access_model->checked, 42);
add_css(['base.css', 'theme.css']);
add_js('app.js');
sec_scripts('<script>fixture();</script>');
helper_check('CSS queue', fn() => put_headers('css'), '<link rel="stylesheet" href="http://localhost/gfc/assets/css/base.css" type="text/css" />' . "\n" . '<link rel="stylesheet" href="http://localhost/gfc/assets/css/theme.css" type="text/css" />' . "\n<script>fixture();</script>");
helper_check('JS queue', fn() => put_headers('js'), '<script type="text/javascript" src="http://localhost/gfc/assets/js/app.js"></script>' . "\n<script>fixture();</script>");

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    fwrite(STDERR, count($failures) . '/' . $checks . " helper checks failed.\n");
    exit(1);
}
echo 'PASS: ' . $checks . ' helper compatibility checks under E_ALL; OCI disabled, no database/network bootstrap.' . PHP_EOL;
