<?php
/** Offline regression: payroll authorization must use the active HMVC user's identity. */
if (PHP_SAPI !== 'cli' || extension_loaded('oci8')) {
    fwrite(STDERR, "Run with PHP CLI -n; OCI must be disabled.\n");
    exit(1);
}
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$cases = [
    'module_allowed_core_guest' => [42, null, false, true],
    'module_allowed_core_denied' => [42, 7, false, true],
    'module_denied_core_allowed' => [7, 42, false, false],
    'module_guest_core_allowed' => [null, 42, false, false],
    'module_zero_core_allowed' => [0, 42, false, false],
    'ajax_module_denied_core_allowed' => [7, 42, true, false],
    'ajax_module_guest_core_allowed' => [null, 42, true, false],
];

// Execute the real exit-based denial response in a subprocess for each case.
if (isset($argv[1]) && $argv[1] === '--case') {
    $case = $argv[2] ?? '';
    if (!isset($cases[$case])) {
        throw new RuntimeException('Unknown fixture case.');
    }
    [$moduleUser, $coreUser, $ajax] = $cases[$case];
    define('BASEPATH', dirname(__DIR__, 2) . '/system/');

    class PayrollContextModelFixture
    {
        public $checked = [];
        public function is_allowed($id)
        {
            $this->checked[] = $id;
            return $id === 42;
        }
    }
    class PayrollContextLoaderFixture
    {
        public $models = [];
        public function model($name) { $this->models[] = $name; }
    }
    class PayrollContextInputFixture
    {
        public $ajax;
        public function __construct($ajax) { $this->ajax = $ajax; }
        public function is_ajax_request() { return $this->ajax; }
    }
    function payroll_context_fixture($id, $input)
    {
        $controller = new stdClass();
        if ($id !== null) { $controller->user = (object)['id' => $id]; }
        $controller->input = $input;
        $controller->load = new PayrollContextLoaderFixture();
        $controller->payroll_access_model = new PayrollContextModelFixture();
        return $controller;
    }

    // MX keeps a core CI instance and an active module controller separately.
    // Request/input belongs to the core and is shared with the module.
    $input = new PayrollContextInputFixture($ajax);
    $payrollCore = payroll_context_fixture($coreUser, $input);
    $payrollModule = payroll_context_fixture($moduleUser, $input);
    function &get_instance() { global $payrollCore; return $payrollCore; }
    function &get_my_instance() { global $payrollModule; return $payrollModule; }
    $returned = false;
    register_shutdown_function(static function () use (&$returned, $payrollCore, $payrollModule) {
        fwrite(STDERR, 'PAYROLL_FIXTURE:' . json_encode([
            'returned' => $returned,
            'status' => http_response_code(),
            'core_checked' => $payrollCore->payroll_access_model->checked,
            'module_checked' => $payrollModule->payroll_access_model->checked,
            'core_loads' => $payrollCore->load->models,
            'module_loads' => $payrollModule->load->models,
        ]) . "\n");
    });
    require dirname(__DIR__, 2) . '/application/helpers/payroll_access_helper.php';
    require_payroll_access();
    $returned = true;
    exit(0);
}

$checks = 0;
$failures = [];
function payroll_context_check($actual, $expected, $name)
{
    global $checks, $failures;
    ++$checks;
    if ($actual !== $expected) {
        $failures[] = $name . ': expected ' . var_export($expected, true)
            . ', got ' . var_export($actual, true);
    }
}
foreach ($cases as $name => [$moduleUser, $coreUser, $ajax, $allowed]) {
    $process = proc_open([PHP_BINARY, '-n', __FILE__, '--case', $name], [
        0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
    ], $pipes);
    if (!is_resource($process)) { throw new RuntimeException('Cannot start isolated fixture.'); }
    fclose($pipes[0]);
    $body = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($process);
    payroll_context_check($exit, 0, $name . ' exits cleanly');
    preg_match('/^PAYROLL_FIXTURE:(.+)$/m', $errors, $matches);
    $result = isset($matches[1]) ? json_decode($matches[1], true) : [];
    payroll_context_check(trim(preg_replace('/^PAYROLL_FIXTURE:.+$/m', '', $errors)), '', $name . ' has no PHP warning/error');
    payroll_context_check($result['returned'] ?? null, $allowed, $name . ' allow/deny result');
    payroll_context_check($result['module_checked'] ?? null, [$moduleUser ?? 0], $name . ' checks active module identity');
    payroll_context_check($result['core_checked'] ?? null, [], $name . ' never borrows core identity');
    payroll_context_check($result['module_loads'] ?? null, ['payroll_data/payroll_access_model'], $name . ' loads active module model');
    payroll_context_check($result['core_loads'] ?? null, [], $name . ' does not load through unrelated core');
    if ($allowed) {
        payroll_context_check($body, '', $name . ' allows without output');
        payroll_context_check($result['status'] ?? null, false, $name . ' leaves status untouched');
    } elseif ($ajax) {
        $response = json_decode($body, true);
        payroll_context_check($response['ok'] ?? null, false, $name . ' AJAX failure flag');
        payroll_context_check($response['maintenance'] ?? null, true, $name . ' AJAX maintenance flag');
        payroll_context_check($response['msg'] ?? null, 'الشاشة تحت الصيانة حالياً، يرجى المحاولة لاحقاً.', $name . ' preserves AJAX message');
        payroll_context_check($result['status'] ?? null, false, $name . ' preserves existing AJAX status behavior');
    } else {
        payroll_context_check($result['status'] ?? null, 503, $name . ' responds with maintenance status');
        payroll_context_check(strpos($body, '<!DOCTYPE html>') === 0, true, $name . ' renders maintenance HTML');
        payroll_context_check(strpos($body, 'الشاشة تحت الصيانة حالياً') !== false, true, $name . ' preserves maintenance message');
    }
}
if ($failures) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}
echo "PASS: {$checks} payroll access context checks; no application bootstrap or Oracle connection.\n";
