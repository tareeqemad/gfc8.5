<?php
// Offline regression + real-view fixtures. OCI and application bootstrap are forbidden.
// Windows: php -n -d extension=C:/xampp744/php/ext/php_mbstring.dll tests/payment_req/report_contract_test.php --render --output=ABSOLUTE_DIRECTORY
if (PHP_SAPI !== 'cli' || extension_loaded('oci8')) {
    fwrite(STDERR, "Run PHP CLI with -n and mbstring; OCI must be disabled.\n");
    exit(1);
}
$root = dirname(__DIR__, 2);
define('BASEPATH', $root . '/system/');
define('APPPATH', $root . '/application/');
function base_url($path = '') { return '../../../' . $path; }
function AntiForgeryToken() { return ''; }
// Use the production script queue: content renders before template1 loads jQuery.
class ReportFixtureConfig {
    private $items = ['scripts'=>'', 'header_js'=>[], 'header_css'=>[]];
    public function item($name) { return $this->items[$name] ?? null; }
    public function set_item($name, $value) { $this->items[$name] = $value; }
}
class ReportFixtureHeaderLoader {
    public function driver($name) { if ($name !== 'minify') throw new RuntimeException('Unexpected driver'); }
}
$reportHeaderCi = (object)['config'=>new ReportFixtureConfig(), 'load'=>new ReportFixtureHeaderLoader()];
function &get_instance() { global $reportHeaderCi; return $reportHeaderCi; }
require APPPATH . 'helpers/css_js_meta_helper.php';
function HaveAccess($url) { return true; }
function log_message($level, $message) {}
#[\AllowDynamicProperties]
class MY_Controller {
    public function check_vars($default, $name) { return $this->{'p_' . $name} ?? null; }
}
require APPPATH . 'modules/payment_req/controllers/Payment_req.php';

function fixtureRows() {
    $common = ['EMP_NO' => 990001, 'EMP_NAME' => 'موظف تجريبي — بيانات مصطنعة', 'BRANCH_NAME' => 'مقر تجريبي',
        'THE_MONTH' => '202610', 'PERCENT_VAL' => 50, 'CALC_METHOD' => 1, 'TYPE2_REMAINING_MODE' => 0,
        'INCLUDE_ALLOWANCES' => 0, 'ALLOWANCE_325_AMOUNT' => 0, 'STATUS' => 3, 'STATUS_NAME' => 'معتمد جزئياً',
        'PAY_TYPE_NAME' => 'صرف تجريبي', 'NOTE' => 'بيانات اختبار فقط', 'PAY_DATE' => '2026-10-21'];
    $cases = [
        ['REQ_TYPE' => 2, 'REQ_TYPE_NAME' => 'دفعة راتب', 'REQ_AMOUNT' => '1250.00', 'ALLOWANCE_325_AMOUNT' => '250.00', 'TYPE2_REMAINING_MODE' => 1, 'INCLUDE_ALLOWANCES' => 1, 'DETAIL_STATUS' => 2, 'DETAIL_STATUS_NAME' => 'منفّذ للصرف'],
        ['REQ_TYPE' => 2, 'REQ_TYPE_NAME' => 'دفعة راتب', 'REQ_AMOUNT' => '600.00', 'DETAIL_STATUS' => 0, 'DETAIL_STATUS_NAME' => 'مسودة'],
        ['REQ_TYPE' => 4, 'REQ_TYPE_NAME' => 'مستحقات الشهر', 'REQ_AMOUNT' => '1100.00', 'ALLOWANCE_325_AMOUNT' => '100.00', 'DETAIL_STATUS' => 4, 'DETAIL_STATUS_NAME' => 'محتسب'],
        ['REQ_TYPE' => 3, 'REQ_TYPE_NAME' => 'تسديد مستحقات', 'REQ_AMOUNT' => '300.00', 'DETAIL_STATUS' => 2, 'DETAIL_STATUS_NAME' => 'منفّذ للصرف'],
        ['REQ_TYPE' => 5, 'REQ_TYPE_NAME' => 'استحقاقات', 'REQ_AMOUNT' => '150.00', 'DETAIL_STATUS' => 1, 'DETAIL_STATUS_NAME' => 'معتمد'],
        ['REQ_TYPE' => 1, 'REQ_TYPE_NAME' => 'راتب كامل', 'REQ_AMOUNT' => '2200.00', 'ALLOWANCE_325_AMOUNT' => '200.00', 'DETAIL_STATUS' => 2, 'DETAIL_STATUS_NAME' => 'منفّذ للصرف'],
    ];
    foreach ($cases as $i => &$row) $row = $row + ['REQ_ID' => 9001 + $i, 'REQ_NO' => 'TEST-' . (9001 + $i)] + $common;
    unset($row);
    return $cases;
}
class ReportFixtureModel {
    public $writes = 0;
    public $lastReportArgs = [];
    public $reportRows = null;
    public $storedType = 2;
    public $preview = '1250|5000||4000|Test|250|4000|2000|2000|500|1000|1250|1|1';
    public function get($reqId) { return [['REQ_ID' => $reqId, 'REQ_TYPE' => $this->storedType]]; }
    public function get_report(...$args) { $this->lastReportArgs = $args; return $this->reportRows ?? fixtureRows(); }
    public function get_emp_statement(...$args) { return fixtureRows(); }
    public function get_summary(...$args) { return []; }
    public function emp_accounts_list(...$args) { return []; }
    public function emp_pending_batches(...$args) { return []; }
    public function emp_all_batches(...$args) { return []; }
    public function get_emp_info(...$args) { return [['EMP_NO' => 990001, 'EMP_NAME' => 'موظف تجريبي — بيانات مصطنعة', 'DISPLAY_STATUS' => 1]]; }
    public function detail_add(...$args) { $this->writes++; return '1'; }
    public function detail_update(...$args) { $this->writes++; return '1'; }
    public function detail_preview_single(...$args) { return $this->preview; }
    public function __call($name, $args) { throw new RuntimeException('Unexpected model call: ' . $name); }
}
class ReportFixtureInput { public function is_ajax_request() { return true; } }
$controller = (new ReflectionClass('Payment_req'))->newInstanceWithoutConstructor();
$controller->user = (object)['branch' => 1, 'id' => 991001];
$controller->Payment_req_model = new ReportFixtureModel();
$controller->input = new ReportFixtureInput();
$controller->p_emp_no = 990001;
$controller->p_month_from = '202610';
$controller->p_month_to = '202610';
$controller->p_the_month = '202610';
$controller->p_branch_no = '';
$controller->p_req_type = '';
$controller->p_status = '';
$controller->p_note = '';
$controller->p_req_id = 9001;
$controller->p_detail_id = 9901;
$controller->p_report_columns = null;
$controller->p_report_mode = 'all';
$controller->p_report_allowance = 'all';
$controller->p_report_detail_status = 'all';
$controller->p_report_measure = 'total';
$_SERVER['REQUEST_METHOD'] = 'POST';

function invokePrivate($name, ...$args) {
    global $controller;
    $method = new ReflectionMethod('Payment_req', $name);
    if (PHP_VERSION_ID < 80100) $method->setAccessible(true);
    return $method->invokeArgs($controller, $args);
}
function check($condition, $message) {
    global $checks;
    $checks++;
    if (!$condition) throw new RuntimeException($message);
}
function captureEndpoint($name) {
    global $controller;
    ob_start(); $controller->$name(); return ob_get_clean();
}
function childOutput($mode, $case = null) {
    global $outDir;
    $tmp = $outDir;
    if (!is_dir($tmp)) mkdir($tmp, 0777, true);
    $args = [PHP_BINARY, '-n', '-d', 'sys_temp_dir=' . $tmp];
    if (PHP_OS_FAMILY === 'Windows') $args = array_merge($args, ['-d', 'extension=' . dirname(PHP_BINARY) . '/ext/php_mbstring.dll']);
    $args = array_merge($args, [__FILE__, $mode, '--output=' . $outDir]);
    if ($case !== null) $args[] = '--case=' . $case;
    $process = proc_open($args, [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Cannot start offline export fixture');
    fclose($pipes[0]); $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $error = stream_get_contents($pipes[2]); fclose($pipes[2]);
    if (proc_close($process) !== 0 || trim($error) !== '') throw new RuntimeException($error !== '' ? $error : substr($output, 0, 1500));
    return $output;
}

$mode = $argv[1] ?? '';
$outDir = sys_get_temp_dir() . '/gfc-payment-req-report-fixtures';
foreach (array_slice($argv, 1) as $arg) {
    if (strpos($arg, '--output=') === 0) $outDir = rtrim(substr($arg, 9), '/\\');
    if ($arg === '--case=remaining') {
        $controller->p_report_mode = 'remaining';
        $controller->p_report_columns = 'total,req_no,allowance';
        $controller->p_report_measure = 'allowance';
    }
    if ($arg === '--case=draft') {
        $controller->p_report_detail_status = '0';
        $controller->p_report_columns = 'req_no,total,detail_status';
    }
    if (preg_match('/^--case=measure-(total|base|allowance)$/', $arg, $match)) {
        $controller->p_report_measure=$match[1];
        $controller->p_report_columns='req_no,amount';
    }
}
if (!is_dir($outDir)) mkdir($outDir, 0777, true);
if ($mode === '--export-report') $controller->report_monthly_export();
if ($mode === '--export-statement') $controller->emp_statement_export();
if ($mode === '--import-new') { $controller->p_req_id = 0; $controller->import_excel(); }

$checks = 0;
$rows = invokePrivate('_payment_report_rows', fixtureRows());
check($rows[0]['BASE_PAYMENT'] === 1000.0 && $rows[0]['PAYMENT_ALLOWANCE_325'] === 250.0 && $rows[0]['TOTAL_PAYMENT'] === 1250.0, '325 was counted twice or lost');
check($rows[0]['CALCULATION_BASIS'] === '50% من المتبقي (323 + 325)', 'Remaining basis incorrect');
check($rows[1]['CALCULATION_BASIS'] === '50% من الراتب', 'Ordinary type 2 changed');
check($rows[2]['BASE_PAYMENT'] === 1000.0 && $rows[2]['PAYMENT_ALLOWANCE_325'] === 100.0, 'Type 4 stored split changed');
foreach ([3, 4] as $index) check(!$rows[$index]['PAYMENT_COMPONENTS_APPLICABLE'] && $rows[$index]['BASE_PAYMENT'] === null, 'Nonmonthly cash mislabeled 323');
check(array_sum(array_column($rows, 'TOTAL_PAYMENT')) === 5600.0, 'Cash total drift');
check(array_sum(array_column($rows, 'BASE_PAYMENT')) === 4600.0 && array_sum(array_column($rows, 'PAYMENT_ALLOWANCE_325')) === 550.0, 'Component totals drift');
$legacy = fixtureRows()[0]; unset($legacy['ALLOWANCE_325_AMOUNT'], $legacy['TYPE2_REMAINING_MODE']);
$legacyResult = invokePrivate('_payment_report_rows', [$legacy])[0];
check(!$legacyResult['PAYMENT_BREAKDOWN_AVAILABLE'] && $legacyResult['BASE_PAYMENT'] === null && $legacyResult['TOTAL_PAYMENT'] === 1250.0, 'Missing fields silently became zero');
check($legacyResult['CALCULATION_BASIS'] === 'أساس الاحتساب غير متوفر', 'Missing mode mislabeled ordinary');
$invalid = fixtureRows()[0]; $invalid['ALLOWANCE_325_AMOUNT'] = 1500;
check(!invokePrivate('_payment_report_rows', [$invalid])[0]['PAYMENT_BREAKDOWN_AVAILABLE'], 'Impossible split silently clamped');
$legacy = fixtureRows()[1]; $legacy['ALLOWANCE_325_AMOUNT'] = null;
check(invokePrivate('_payment_report_rows', [$legacy])[0]['PAYMENT_ALLOWANCE_325'] === 0.0, 'Legacy NULL allowance must match database NVL semantics');
$monthly = invokePrivate('_month_report_breakdown', ['TOTAL_BASE_USED' => '4600.00']);
check($monthly['breakdown_available'] === false && $monthly['total_allowance_used'] === null && $monthly['total_base_used'] === 4600.0, 'Missing monthly package fields must stay unavailable');
$monthlySource = array_fill_keys(['TOTAL_BASE_USED','TOTAL_ALLOWANCE_USED','TOTAL_CASH_USED','TOTAL_PAID_BASE','TOTAL_PAID_ALLOWANCE','TOTAL_SALARY_323','TOTAL_ALLOWANCE_325'], '0');
check(invokePrivate('_month_report_breakdown', $monthlySource)['breakdown_available'], 'Valid zero month mistaken for missing deployment');
foreach ([null, '', '0', '0.00'] as $input) check(invokePrivate('_requested_amount', $input) === 0.0, 'Full-balance sentinel changed');
check(invokePrivate('_requested_amount', '1,250.25') === 1250.25, 'Currency grouping lost');
check(invokePrivate('_requested_amount', '.50') === 0.5, 'Leading decimal point rejected');
check(invokePrivate('_requested_amount', '1.') === 1.0, 'Trailing decimal point rejected');
foreach (['-1', '1,2', 'bad', [], '1e309', '0.004'] as $input) {
    $controller->p_req_amount = $input;
    foreach (['detail_add', 'detail_update', 'detail_preview_single'] as $endpoint) {
        $response = json_decode(captureEndpoint($endpoint), true);
        check($response['ok'] === false, $endpoint . ' accepted invalid money');
    }
}
check($controller->Payment_req_model->writes === 0, 'Invalid money reached a writer');
$controller->p_req_amount = '1250.00';
$preview = json_decode(captureEndpoint('detail_preview_single'), true);
check($preview['ok'] && $preview['calc_amount'] == 1250 && $preview['base_payment'] == 1000 && $preview['allowance_325_amount'] == 250, 'Preview split lost');
$controller->Payment_req_model->storedType = 3;
$controller->Payment_req_model->preview = '300|5000||4000|Test|0|4000|0|0|0|300|0|0|0';
$duesPreview = json_decode(captureEndpoint('detail_preview_single'), true);
check($duesPreview['ok'] && $duesPreview['calc_amount'] === 300 && $duesPreview['base_payment'] === 300
    && $duesPreview['allowance_325_amount'] === 0 && $duesPreview['type2_remaining_mode'] === 0, 'Type 3 full preview contract broke');
$controller->Payment_req_model->storedType = 5;
$controller->Payment_req_model->preview = '0|0||0|موظف استحقاقات تجريبي';
$benefitsPreview = json_decode(captureEndpoint('detail_preview_single'), true);
check($benefitsPreview['ok'] && $benefitsPreview['emp_name'] === 'موظف استحقاقات تجريبي'
    && $benefitsPreview['calc_amount'] === 0 && $benefitsPreview['monthly_components_applicable'] === false
    && $benefitsPreview['allowance_325_amount'] === null && $benefitsPreview['base_payment'] === null, 'Production type 5 short preview broke');
$controller->Payment_req_model->storedType = 2;
$controller->p_req_type = 5;
$wrongTypePreview = json_decode(captureEndpoint('detail_preview_single'), true);
check($wrongTypePreview['ok'] === false, 'Posted type accepted an incomplete ordinary-type preview');
$controller->p_req_type = '';
try { invokePrivate('_parse_detail_preview', ['1250','5000','','4000','Test','250']); check(false, 'Old preview contract passed'); }
catch (UnexpectedValueException $expected) { check(true, 'Old preview rejected'); }
$report = json_decode(captureEndpoint('report_monthly_data'), true);
$statement = json_decode(captureEndpoint('emp_statement'), true);
check($report['data'] === $statement['data'], 'Report and statement JSON differ');
foreach ($report['data'] as $index=>$row) {
    check($row['REPORT_AMOUNT']==$rows[$index]['TOTAL_PAYMENT'], 'Default report measure is not total');
    unset($row['REPORT_AMOUNT']);
    check($row==$rows[$index], 'Report changed saved values');
}
check($report['report']['summary']['total'] == 5600 && $report['report']['summary']['base'] == 4600
    && $report['report']['summary']['allowance'] == 550, 'Default filtered totals drift');
$defaultReport = $report;
$controller->p_report_mode = 'remaining';
$controller->p_report_columns = 'total,req_no,allowance,unknown,total';
$remaining = json_decode(captureEndpoint('report_monthly_data'), true);
check(count($remaining['data']) === 1 && $remaining['data'][0]['REQ_NO'] === 'TEST-9001', 'Remaining filter must select only stored type2 remaining mode');
check($remaining['report']['summary']['total'] == 1250 && $remaining['report']['summary']['allowance'] == 250, 'Remaining filter totals stale');
check($remaining['report']['options']['columns'] === ['req_no','amount'], 'Legacy column migration/order/deduplication failed');
$remainingStatement = json_decode(captureEndpoint('emp_statement'), true);
check($remainingStatement['data'] === $remaining['data'] && $remainingStatement['report']['summary'] === $remaining['report']['summary'], 'Statement and general filter totals diverge');
$controller->p_report_mode = 'salary';
$salary = json_decode(captureEndpoint('report_monthly_data'), true);
check(count($salary['data']) === 1 && $salary['report']['summary']['total'] == 600, 'Salary mode filter included other types');
$controller->p_report_mode = 'all';
$controller->p_report_allowance = 'with';
$with325 = json_decode(captureEndpoint('report_monthly_data'), true);
check(count($with325['data']) === 3 && $with325['report']['summary']['total'] == 4550
    && $with325['report']['summary']['base'] == 4000 && $with325['report']['summary']['allowance'] == 550, 'Actual325 filtering or totals wrong');
$controller->p_report_columns = 'req_no';
$hiddenMoney = json_decode(captureEndpoint('report_monthly_data'), true);
check($hiddenMoney['report']['summary'] === $with325['report']['summary'], 'Hiding money columns changed financial totals');
$controller->p_report_allowance = 'without';
$without325 = json_decode(captureEndpoint('report_monthly_data'), true);
check(count($without325['data']) === 3 && $without325['report']['summary']['total'] == 1050, 'Without325 must retain nonmonthly cash');
$controller->p_report_allowance = 'all';
$controller->p_report_detail_status = '0';
$controller->p_status = '0';
$controller->p_branch_no = 1;
$controller->user->branch = 7;
$draft = json_decode(captureEndpoint('report_monthly_data'), true);
check(count($draft['data']) === 1 && $draft['report']['summary']['total'] == 600, 'Status0 was dropped');
check($controller->Payment_req_model->lastReportArgs[2] == 7 && $controller->Payment_req_model->lastReportArgs[4] === '0', 'Report options bypassed branch scope or dropped master status0');
$controller->user->branch = 1;
$controller->p_status = '';
$controller->p_report_mode = 'remaining';
check(json_decode(captureEndpoint('report_monthly_data'), true)['report']['summary']['total'] == 0, 'Empty filter intersection must total0');
$controller->p_report_mode = 'all';
$controller->p_report_detail_status = 'all';
$controller->p_report_columns = null;
$unknownRows = fixtureRows(); unset($unknownRows[0]['ALLOWANCE_325_AMOUNT']);
$controller->Payment_req_model->reportRows = $unknownRows;
$unknownTotals = json_decode(captureEndpoint('report_monthly_data'), true);
check($unknownTotals['report']['summary']['total'] == 5600 && $unknownTotals['report']['summary']['base'] === null
    && $unknownTotals['report']['summary']['allowance'] === null, 'Unknown components silently became0');
$controller->p_report_allowance = 'unknown';
$unknownOnly = json_decode(captureEndpoint('report_monthly_data'), true);
check(count($unknownOnly['data']) === 1 && $unknownOnly['report']['summary']['total'] == 1250, 'Unknown325 filter lost applicable missing data');
$controller->Payment_req_model->reportRows = null;
$controller->p_report_allowance = 'all';
$smallRows = fixtureRows();
foreach ($smallRows as &$small) { $small['REQ_AMOUNT'] = '0.10'; $small['ALLOWANCE_325_AMOUNT'] = 0; }
unset($small);
$controller->Payment_req_model->reportRows = $smallRows;
$smallTotals = json_decode(captureEndpoint('report_monthly_data'), true);
check($smallTotals['report']['summary']['total'] === 0.6, 'Cent aggregation drift');
$controller->Payment_req_model->reportRows = null;
$invalidOptions = payment_req_build_report($rows, 'general', ['report_columns'=>['total'],
    'report_mode'=>['remaining'], 'report_allowance'=>'<script>', 'report_detail_status'=>false]);
check($invalidOptions['report']['options']['columns'] === payment_req_report_config('general')['presets']['compact'], 'Malformed columns did not fall back safely');
check($invalidOptions['report']['options']['mode'] === 'all' && $invalidOptions['report']['options']['allowance'] === 'all', 'Malformed filters accepted');
$missingMode = fixtureRows()[0]; unset($missingMode['TYPE2_REMAINING_MODE']);
$unknownMode = payment_req_build_report(invokePrivate('_payment_report_rows',[$missingMode]),'general',['report_mode'=>'salary']);
check($unknownMode['rows'] === [], 'Missing mode inferred as salary');
$controller->p_report_columns='total';
$controller->p_report_allowance='with';
$controller->p_report_detail_status='9';
$emptyReport=json_decode(captureEndpoint('report_monthly_data'),true);
check($emptyReport['data']===[] && $emptyReport['report']['summary']['total']==0
    && $emptyReport['report']['summary']['allowance']==0, 'Empty report retains stale totals');
$controller->p_report_columns=null;
$controller->p_report_allowance='all';
$controller->p_report_detail_status='all';
$measureCases = [
    'total'=>['label'=>'الإجمالي','sum'=>5600,'paid'=>3750,'rows'=>[1250,600,1100,300,150,2200]],
    'base'=>['label'=>'المبلغ دون 325','sum'=>5050,'paid'=>3300,'rows'=>[1000,600,1000,300,150,2000]],
    'allowance'=>['label'=>'علاوة 325 فقط','sum'=>550,'paid'=>450,'rows'=>[250,0,100,0,0,200]],
];
foreach ($measureCases as $measure=>$expected) {
    $controller->p_report_measure=$measure;
    foreach (['report_monthly_data','emp_statement'] as $endpoint) {
        $selected=json_decode(captureEndpoint($endpoint),true);
        check(count($selected['data'])===6, 'Choosing a measure must not silently filter rows');
        check($selected['report']['summary']['amount']==$expected['sum'], $measure.' chosen sum wrong');
        check($selected['report']['summary']['by_status']['2']['amount']==$expected['paid'], $measure.' status subtotal stayed cash');
        check($selected['report']['summary']['amount_label']===$expected['label'], 'Selected amount label wrong');
        $moneyColumns=array_values(array_filter($selected['report']['columns'],function($c){return $c['kind']==='money';}));
        check(count($moneyColumns)===1 && $moneyColumns[0]['label']===$expected['label'], 'Wrong money columns/headings');
        foreach ($selected['data'] as $i=>$row) {
            check($row['REPORT_AMOUNT']==$expected['rows'][$i], $measure.' row amount wrong');
            unset($row['REPORT_AMOUNT']);
            check($row==$rows[$i], $measure.' modified stored or normalized financial fields');
        }
    }
}
$controller->p_report_allowance='with';
foreach (['total'=>4550,'base'=>4000,'allowance'=>550] as $measure=>$expected) {
    $controller->p_report_measure=$measure;
    $selected=json_decode(captureEndpoint('report_monthly_data'),true);
    check(count($selected['data'])===3 && $selected['report']['summary']['amount']==$expected, 'Measure and325rowfilter conflated');
}
$controller->p_report_allowance='all';
$controller->Payment_req_model->reportRows=$unknownRows;
foreach (['base','allowance'] as $measure) {
    $controller->p_report_measure=$measure;
    $selected=json_decode(captureEndpoint('report_monthly_data'),true);
    check($selected['report']['summary']['amount']===null && $selected['data'][0]['REPORT_AMOUNT']===null, 'Unknown selected amount became zero');
}
$controller->Payment_req_model->reportRows=null;
foreach (['invalid',[],1] as $badMeasure) {
    $controller->p_report_measure=$badMeasure;
    $selected=json_decode(captureEndpoint('report_monthly_data'),true);
    check($selected['report']['options']['measure']==='total' && $selected['report']['summary']['amount']==5600, 'Invalid measure did not safely default');
}
$controller->p_report_measure='total';
check($controller->Payment_req_model->writes===0, 'Report measure reached a write operation');
$import = json_decode(childOutput('--import-new'), true);
check($import['ok'] === false && strpos($import['msg'], 'أنشئ طلب الصرف') !== false, 'Legacy import created an incomplete master');

foreach (['report' => ['I1'=>'الإجمالي','I2'=>1250,'E2'=>'50% من المتبقي (323 + 325)','I5'=>300],
          'statement' => ['F1'=>'الإجمالي','F2'=>1250,'E2'=>'50% من المتبقي (323 + 325)','F5'=>300]] as $name => $expected) {
    $file = $outDir . '/' . $name . '.xlsx';
    file_put_contents($file, childOutput('--export-' . $name));
    $zip = new ZipArchive(); check($zip->open($file) === true, 'Export is not a valid XLSX');
    $sheetXml = new DOMDocument(); $sheetXml->loadXML($zip->getFromName('xl/worksheets/sheet1.xml'));
    $stringsXml = new DOMDocument(); $stringsXml->loadXML($zip->getFromName('xl/sharedStrings.xml'));
    $strings = []; foreach ($stringsXml->getElementsByTagName('si') as $item) $strings[] = $item->textContent;
    $cells = [];
    foreach ($sheetXml->getElementsByTagName('c') as $cell) {
        $valueNode = $cell->getElementsByTagName('v')->item(0);
        if ($valueNode) $cells[$cell->getAttribute('r')] = $cell->getAttribute('t') === 's' ? $strings[(int)$valueNode->textContent] : $valueNode->textContent;
    }
    foreach ($expected as $cell => $value) check(isset($cells[$cell]) && $cells[$cell] == $value, $name . ' Excel mismatch at ' . $cell);
    $zip->close();
}
foreach (['report','statement'] as $name) {
    foreach (['remaining' => ['A2'=>'TEST-9001','B1'=>'علاوة 325 فقط','B2'=>250],
              'draft' => ['A2'=>'TEST-9002','B2'=>600,'C2'=>'مسودة'],
              'measure-total'=>['B1'=>'الإجمالي','B2'=>1250,'B5'=>300,'B8'=>5600],
              'measure-base'=>['B1'=>'المبلغ دون 325','B2'=>1000,'B5'=>300,'B8'=>5050],
              'measure-allowance'=>['B1'=>'علاوة 325 فقط','B2'=>250,'B5'=>0,'B8'=>550]] as $case => $expected) {
        $file = $outDir . '/' . $name . '-' . $case . '.xlsx';
        file_put_contents($file, childOutput('--export-' . $name, $case));
        $zip = new ZipArchive(); check($zip->open($file) === true, 'Selected export is not XLSX');
        $sheetXml = new DOMDocument(); $sheetXml->loadXML($zip->getFromName('xl/worksheets/sheet1.xml'));
        $stringsXml = new DOMDocument(); $stringsXml->loadXML($zip->getFromName('xl/sharedStrings.xml'));
        $strings = []; foreach ($stringsXml->getElementsByTagName('si') as $item) $strings[] = $item->textContent;
        $cells = [];
        foreach ($sheetXml->getElementsByTagName('c') as $cell) {
            $valueNode = $cell->getElementsByTagName('v')->item(0);
            if ($valueNode) $cells[$cell->getAttribute('r')] = $cell->getAttribute('t') === 's' ? $strings[(int)$valueNode->textContent] : $valueNode->textContent;
            check(!preg_match('/^[D-Z]/', $cell->getAttribute('r')), 'Unselected column leaked into export');
        }
        foreach ($expected as $cell => $value) check(isset($cells[$cell]) && $cells[$cell] == $value, $name . ' selected Excel mismatch at ' . $cell);
        if (strpos($case,'measure-')!==0) check(!in_array('TEST-9006', $cells, true), 'Filtered record leaked into Excel');
        $zip->close();
    }
}

class ReportFixtureViews {
    public $user;
    public $scripts = '';
    public function __construct() { $this->user = (object)['branch' => 1, 'id' => 991001]; }
    public function view($name, $data) { echo $this->render($name, $data); }
    public function render($name, $data) {
        global $reportHeaderCi;
        $reportHeaderCi->config->set_item('scripts','');
        extract($data); ob_start(); require APPPATH . 'modules/payment_req/views/' . $name . '.php';
        $html = ob_get_clean();
        $this->scripts = put_headers('js');
        return $html;
    }
}
$views = new ReportFixtureViews();
$controller->load=$views;
$controller->p_report_format='print';
$controller->p_report_mode='remaining';
$controller->p_report_columns='req_no,allowance,total';
$controller->p_report_measure='allowance';
$controller->p_status='0';
$controller->user->branch=7;
$generalPrinted=captureEndpoint('report_monthly');
check($controller->Payment_req_model->lastReportArgs[2]==7 && $controller->Payment_req_model->lastReportArgs[4]==='0', 'Printing dropped branch/master status0');
check(strpos($generalPrinted,'TEST-9001')!==false && strpos($generalPrinted,'TEST-9002')===false
    && strpos($generalPrinted,'1,250.00')===false && strpos($generalPrinted,'250.00')!==false
    && strpos($generalPrinted,'علاوة 325 فقط')!==false, 'Monthly print ignored measure or report filter');
file_put_contents($outDir.'/report-print-remaining.html',$generalPrinted);
$controller->p_report_format=null;
$controller->p_report_mode='all';
$controller->p_report_columns=null;
$controller->p_report_measure='total';
$controller->p_status='';
$controller->user->branch=1;
$printData = ['rows' => $statement['data'], 'emp_no' => 990001, 'month_from' => '202610', 'month_to' => '202610',
    'emp_info' => ['EMP_NO' => 990001, 'EMP_NAME' => 'موظف تجريبي — بيانات مصطنعة', 'DISPLAY_STATUS' => 1],
    'summary' => [], 'accounts' => [], 'pending' => [], 'batches' => [],
    'report' => $statement['report'], 'report_config' => payment_req_report_config('statement')];
$printed = $views->render('payment_req_emp_statement_print', $printData);
check(strpos($printed, 'size: A4 landscape') !== false, 'Wide statement must print landscape');
$dom = new DOMDocument(); @$dom->loadHTML('<?xml encoding="UTF-8">' . $printed);
$xp = new DOMXPath($dom);
$first = $xp->query('//table[contains(@class,"request-table")]/tbody/tr[1]/td');
check($first->length === 10 && trim($first->item(5)->textContent) === '1,250.00', 'A4 selected amount mismatch');
$foot = $xp->query('//table[contains(@class,"request-table")]/tfoot/tr/td');
$footText = ''; foreach ($foot as $cell) $footText .= $cell->textContent . '|';
check(strpos($footText,'4,600.00') === false && strpos($footText,'550.00') === false && strpos($footText,'5,600.00') !== false, 'A4 selected total mismatch');
file_put_contents($outDir . '/statement-a4.html', $printed);
$filteredPrintData = $printData;
$selectedPrintReport = payment_req_build_report($rows, 'statement', ['report_mode'=>'remaining',
    'report_measure'=>'allowance', 'report_columns'=>'req_no,amount']);
$filteredPrintData['rows'] = $selectedPrintReport['rows'];
$filteredPrintData['report'] = $selectedPrintReport['report'];
$filteredPrint = $views->render('payment_req_emp_statement_print', $filteredPrintData);
check(strpos($filteredPrint, 'size: A4 portrait') !== false, 'Narrow statement must print portrait');
$filteredDom = new DOMDocument(); @$filteredDom->loadHTML('<?xml encoding="UTF-8">' . $filteredPrint);
$filteredXp = new DOMXPath($filteredDom);
$filteredCells = $filteredXp->query('//table[contains(@class,"request-table")]/tbody/tr[1]/td');
check($filteredCells->length === 2 && trim($filteredCells->item(0)->textContent) === 'TEST-9001'
    && trim($filteredCells->item(1)->textContent) === '250.00'
    && strpos($filteredPrint, '1,250.00') === false, 'A4 ignored selected measure, columns or filters');
check(strpos($filteredPrint,'TEST-9006') === false, 'Filtered request leaked into A4');
file_put_contents($outDir . '/statement-a4-remaining.html', $filteredPrint);
foreach (['total'=>['الإجمالي','1,250.00','5,600.00'], 'base'=>['المبلغ دون 325','1,000.00','5,050.00'],
          'allowance'=>['علاوة 325 فقط','250.00','550.00']] as $measure=>$expected) {
    $built=payment_req_build_report($rows,'statement',['report_measure'=>$measure,'report_columns'=>'req_no,amount']);
    $measurePrintData=$printData;
    $measurePrintData['rows']=$built['rows'];
    $measurePrintData['report']=$built['report'];
    $measurePrinted=$views->render('payment_req_emp_statement_print',$measurePrintData);
    $measureDom=new DOMDocument(); @$measureDom->loadHTML('<?xml encoding="UTF-8">'.$measurePrinted);
    $measureXp=new DOMXPath($measureDom);
    check(trim($measureXp->query('//table[contains(@class,"request-table")]/thead/tr/th[2]')->item(0)->textContent)===$expected[0], 'A4 measure header mismatch');
    check(trim($measureXp->query('//table[contains(@class,"request-table")]/tbody/tr[1]/td[2]')->item(0)->textContent)===$expected[1], 'A4 measure row mismatch');
    check(trim($measureXp->query('//table[contains(@class,"request-table")]/tfoot/tr/td[2]')->item(0)->textContent)===$expected[2], 'A4 measure footer mismatch');
    file_put_contents($outDir.'/statement-a4-'.$measure.'.html',$measurePrinted);
    $controller->p_report_format='print';
    $controller->p_report_measure=$measure;
    $controller->p_report_columns='req_no,amount';
    $generalMeasurePrinted=captureEndpoint('report_monthly');
    check(strpos($generalMeasurePrinted,$expected[0])!==false && strpos($generalMeasurePrinted,$expected[1])!==false
        && strpos($generalMeasurePrinted,$expected[2])!==false, 'General print measure mismatch');
    file_put_contents($outDir.'/report-print-'.$measure.'.html',$generalMeasurePrinted);
}
$controller->p_report_format=null;
$controller->p_report_measure='total';
$controller->p_report_columns=null;
file_put_contents($outDir . '/report-rows.json', json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
if ($mode === '--render') {
    if (!is_dir($outDir . '/fixture-assets')) mkdir($outDir . '/fixture-assets', 0777, true);
    copy($root . '/assets/da3em/vendor/bootstrap/css/bootstrap.rtl.min.css', $outDir . '/fixture-assets/bootstrap.rtl.min.css');
    copy($root . '/assets-n/js/jquery.min.js', $outDir . '/fixture-assets/jquery.min.js');
    // GCC_BRANCH_TB_GET_ALL returns NO/NAME; report row BRANCH_NAME is a different contract.
    $data = ['title' => 'معاينة تقرير الصرف — بيانات اختبار', 'branches' => [
            ['NO'=>1, 'NAME'=>'مقر تجريبي'], ['NO'=>7, 'NAME'=>'مقر تجريبي <أ> & "ب"']],
        'req_type_cons' => [['CON_NO'=>2, 'CON_NAME'=>'دفعة راتب']],
        'status_cons' => [['CON_NO'=>0, 'CON_NAME'=>'مسودة'], ['CON_NO'=>2, 'CON_NAME'=>'منفذ']],
        'emp_no_cons' => [['EMP_NO' => 990001, 'EMP_NAME' => 'موظف تجريبي']], 'emp_no' => null];
    foreach (['payment_req_report'=>'rpt_branch','payment_req_dues_liability'=>'dl_branch'] as $lookupView=>$selectId) {
        $lookupHtml=$views->render($lookupView,$data+['report_config'=>payment_req_report_config('general')]);
        $lookupDom=new DOMDocument(); @$lookupDom->loadHTML('<?xml encoding="UTF-8">'.$lookupHtml);
        $lookupXp=new DOMXPath($lookupDom);
        $branchOptions=$lookupXp->query('//select[@id="'.$selectId.'"]/option');
        check($branchOptions->length===3, $lookupView.': branch options missing');
        foreach ($data['branches'] as $index=>$branch) {
            $option=$branchOptions->item($index+1);
            check($option->getAttribute('value')===(string)$branch['NO'], $lookupView.': branch ID must use NO');
            check($option->textContent===$branch['NAME'], $lookupView.': branch label must use NAME and escape HTML');
            check($option->getElementsByTagName('*')->length===0, $lookupView.': branch name must not create HTML');
        }
        if ($lookupView==='payment_req_report') {
            check($lookupXp->query('//select[@id="rpt_req_type"]/option[@value="2"]')->item(0)->textContent==='دفعة راتب', 'Request type lookup mismatch');
            check($lookupXp->query('//select[@id="rpt_status"]/option[@value="0"]')->item(0)->textContent==='مسودة', 'Status zero lookup mismatch');
        }
        $views->user->branch=7;
        $restrictedHtml=$views->render($lookupView,$data+['report_config'=>payment_req_report_config('general')]);
        check(strpos($restrictedHtml,'id="'.$selectId.'"')===false, $lookupView.': branch selector must remain HQ-only');
        $views->user->branch=1;
    }
    $shell = '<!doctype html><html lang="ar" dir="rtl"><meta charset="utf-8"><link rel="stylesheet" href="fixture-assets/bootstrap.rtl.min.css"><style>body{padding:24px;font-family:Tahoma,Arial;background:#f6f7fb}.rpt-card{padding:12px;border-radius:8px;min-width:150px}.rpt-card .val{font-size:22px;font-weight:bold}.rpt-tbl td,.stmt-tbl td{vertical-align:middle}.es-sec h6{margin-top:16px}table{background:white}</style><body><p class="alert alert-warning">معاينة محلية ببيانات مصطنعة فقط — لا اتصال بالإنتاج. تنسيق القالب الأساسي تقريبي.</p>';
    $jqueryFooter = '<script src="fixture-assets/jquery.min.js"></script><script>$.fn.select2=function(){return this;};function danger_msg(){}</script>';
    foreach (['general'=>'report', 'statement'=>'statement'] as $context=>$fileName) {
        $config = payment_req_report_config($context);
        $cache = [];
        foreach (['total','base','allowance'] as $measure) {
        foreach (['all','salary','remaining'] as $filterMode) {
            foreach (['all','with','without','unknown'] as $filterAllowance) {
                foreach (['all','0','1','2','3','4','9'] as $filterStatus) {
                    $built = payment_req_build_report($rows, $context, ['report_measure'=>$measure, 'report_mode'=>$filterMode,
                        'report_allowance'=>$filterAllowance, 'report_detail_status'=>$filterStatus]);
                    $cache[$measure.'|'.$filterMode.'|'.$filterAllowance.'|'.$filterStatus] = ['ok'=>true,
                        'data'=>$built['rows'], 'report'=>$built['report'], 'count'=>count($built['rows']),
                        'emp_info'=>$printData['emp_info'], 'summary'=>['DUES_AVAILABLE'=>9876.50],
                        'accounts'=>[], 'pending'=>[], 'batches'=>[]];
                }
            }
        }
        }
        $fixture = ['config'=>$config, 'responses'=>$cache];
        file_put_contents($outDir . '/' . $fileName . '-responses.json', json_encode($fixture, JSON_UNESCAPED_UNICODE));
        $json = json_encode($fixture, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
        $fixtureScript = '<script>var reportFixture=' . $json . ';</script>' . <<<'JS'
<script>
$.ajax = function(options) {
    var deferred=$.Deferred(), params=options.data||{};
    var key=[params.report_measure||'total',params.report_mode||'all',params.report_allowance||'all',params.report_detail_status||'all'].join('|');
    var response=JSON.parse(JSON.stringify(reportFixture.responses[key]));
    var columns=String(params.report_columns||'').split(',');
    if(columns[0]) {
        response.report.columns=response.report.columns.filter(function(c){return columns.indexOf(c.id)!==-1;});
        response.report.options.columns=response.report.columns.map(function(c){return c.id;});
    }
    var timer=setTimeout(function(){
        if(options.success) options.success(response);
        deferred.resolve(response);
        if(options.complete) options.complete();
    },80);
    var request=deferred.promise();
    request.abort=function(){clearTimeout(timer);if(options.error)options.error(request,'abort');deferred.reject(request,'abort');if(options.complete)options.complete();};
    return request;
};
$.post=function(url,params,callback){return $.ajax({url:url,data:params,success:callback});};
HTMLFormElement.prototype.submit=function(){
    var data={};Array.prototype.forEach.call(this.elements,function(e){if(e.name)data[e.name]=e.value;});
    var element=document.getElementById('fixture-submission');
    if(!element){element=document.createElement('pre');element.id='fixture-submission';document.body.appendChild(element);}
    element.textContent='PREVIEW ONLY '+this.action+'\n'+JSON.stringify(data,null,2);
};
</script>
JS;
        $data['report_config']=$config;
        $data['emp_no']=$context==='statement'?990001:null;
        $view=$context==='general'?'payment_req_report':'payment_req_emp_statement';
        $rendered=$views->render($view,$data);
        $deferredScripts=$views->scripts;
        $initializer=$context==='general'?'(function($)':'var _stmtOptions';
        check(strpos($rendered,$initializer)===false, 'jQuery-dependent initializer ran before template footer');
        check(strpos($deferredScripts,$initializer)!==false, 'Report initializer was not registered with sec_scripts');
        $start=$context==='general'?"$(function(){ $('#rpt_month_from,#rpt_month_to').val('202610'); $('#rpt_filter_form').trigger('submit'); });":"";
        file_put_contents($outDir . '/' . $fileName . '.html',$shell.$rendered.$jqueryFooter.$fixtureScript.$deferredScripts.'<script>'.$start.'</script></body></html>');
        if($context==='general') {
            $views->user->id=991002;
            $otherUser=$views->render($view,$data);
            file_put_contents($outDir.'/report-other-user.html',$shell.$otherUser.$jqueryFooter.$fixtureScript.$views->scripts.'<script>'.$start.'</script></body></html>');
            $views->user->id=991001;
        }
    }
}
echo "PASS: $checks checks; offline HTML/A4/Excel fixtures in $outDir\n";
