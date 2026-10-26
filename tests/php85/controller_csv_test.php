<?php
/** Exercise the real controller CSV exports with in-memory data; never bootstrap Oracle. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$root = dirname(__DIR__, 2);
if (isset($argv[1])) {
    define('BASEPATH', $root . '/system/');
    define('APPPATH', $root . '/application/');
    class MY_Controller {
        public $input;
        public $load;
        public $Payment_req_model;
        public $payment_accounts_model;
    }
    require APPPATH . 'modules/payment_req/controllers/Payment_req.php';
    $controller = (new ReflectionClass('Payment_req'))->newInstanceWithoutConstructor();
    $controller->input = new class {
        public function get($key) { return $key === 'req_id' ? '7' : '7,8'; }
        public function post($key) { return null; }
    };
    $controller->load = new class {
        public function model($name) {
            if ($name !== 'payment_accounts/payment_accounts_model') throw new RuntimeException('Unexpected model load.');
        }
    };
    $controller->Payment_req_model = new class {
        public function get_details($reqId) {
            if ($reqId !== 7) throw new RuntimeException('Unexpected request ID.');
            return [['EMP_NO' => 101, 'EMP_NAME' => 'أحمد, "التجربة"', 'BRANCH_NAME' => 'غزة']];
        }
        public function batch_preview($reqIds) {
            if ($reqIds !== '7,8') throw new RuntimeException('Unexpected request IDs.');
            return ['rows' => [[
                'EMP_NO' => 101, 'EMP_NAME' => 'أحمد, "التجربة"', 'BRANCH_NAME' => 'غزة',
                'MASTER_BANK_NAME' => 'البنك', 'BANK_NAME' => 'الفرع', 'IBAN' => 'PS0000123',
                'BANK_ACCOUNT' => '0012345', 'REQ_NO' => '00007', 'REQ_TYPE_NAME' => 'راتب',
                'THE_MONTH' => '202610', 'REQ_AMOUNT' => '125.50',
            ]]];
        }
    };
    $controller->payment_accounts_model = new class {
        public function accounts_preview_by_req($reqId) {
            if ($reqId !== 7) throw new RuntimeException('Unexpected account preview ID.');
            $base = [
                'EMP_NO' => 101, 'PROVIDER_NAME' => 'البنك', 'PROV_BRANCH_NAME' => 'الفرع',
                'ACCOUNT_NO' => '0012345', 'IBAN' => 'PS0000123', 'OWNER_ID_NO' => '000000001',
                'OWNER_PHONE' => '0590000000', 'ACC_IS_ACTIVE' => 1,
            ];
            return [
                $base + ['ALLOC_AMOUNT' => '125.50'],
                $base + ['ALLOC_AMOUNT' => '20.25', 'BENEFICIARY_ID' => 9, 'BENEF_NAME' => 'مستفيد \\ تجريبي', 'BENEF_REL_NAME' => 'ابن'],
                array_replace($base, ['ALLOC_AMOUNT' => 50, 'ACC_IS_ACTIVE' => 0]),
                $base + ['ALLOC_AMOUNT' => 0],
            ];
        }
    };
    if (!in_array($argv[1], ['export_bank_csv', 'batch_export_csv'], true)) throw new RuntimeException('Unknown export.');
    $controller->{$argv[1]}();
    throw new RuntimeException('Export did not finish normally.');
}

$checks = 0;
$check = static function ($condition, $message) use (&$checks) {
    ++$checks;
    if (!$condition) throw new RuntimeException($message);
};
$run = static function ($method) use ($check) {
    $process = proc_open([PHP_BINARY, '-d', 'error_reporting=-1', '-d', 'display_errors=1', __FILE__, $method], [
        0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
    ], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Could not start export test.');
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($process);
    $check($status === 0 && $errors === '', $method . ' emitted errors: ' . $errors . $output);
    $check(str_starts_with($output, "\xEF\xBB\xBF"), $method . ' lost the Excel UTF-8 BOM.');
    $stream = fopen('php://temp', 'w+');
    fwrite($stream, substr($output, 3));
    rewind($stream);
    $rows = [];
    while (($row = fgetcsv($stream, 0, ',', '"', '\\')) !== false) $rows[] = $row;
    fclose($stream);
    return $rows;
};

$bank = $run('export_bank_csv');
$check(count($bank) === 4, 'Bank export must contain a header, two allocated accounts and a total.');
foreach ($bank as $row) $check(count($row) === 14, 'Bank CSV column count changed.');
$check($bank[0][1] === 'رقم الموظف', 'Arabic bank header changed.');
$check($bank[1][2] === 'أحمد, "التجربة"', 'Comma/quote escaping changed.');
$check($bank[1][9] === '0012345' && $bank[1][11] === '000000001', 'Account/identity leading zeros changed.');
$check($bank[1][13] === '125.50', 'Bank amount precision changed.');
$check($bank[2][4] === 'وريث' && $bank[2][5] === 'مستفيد \\ تجريبي', 'Beneficiary/backslash data changed.');
$check($bank[3][12] === 'الإجمالي' && $bank[3][13] === '145.75', 'Bank CSV total/filtering changed.');

$batch = $run('batch_export_csv');
$check(count($batch) === 2 && count($batch[0]) === 12 && count($batch[1]) === 12, 'Batch CSV shape changed.');
$check($batch[1][2] === 'أحمد, "التجربة"', 'Batch Arabic/quote escaping changed.');
$check($batch[1][7] === '0012345' && $batch[1][8] === '00007', 'Batch leading zeros changed.');
$check($batch[1][11] === '125.50', 'Batch amount precision changed.');
echo "PASS: {$checks} controller CSV checks; PHP " . PHP_VERSION . "; no database connection.\n";
