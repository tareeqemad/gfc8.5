<?php
// Run with php -n.
if (PHP_SAPI !== 'cli' || extension_loaded('oci8')) {
    fwrite(STDERR, "Use PHP CLI with -n; OCI must be disabled.\n");
    exit(1);
}

define('BASEPATH', __DIR__);
define('SQLT_CHR', 1);
define('SQLT_INT', 3);
define('SQLT_RSET', 116);
define('OCI_B_CURSOR', 116);
define('OCI_DEFAULT', 0);
define('OCI_FETCHSTATEMENT_BY_ROW', 16);

#[\AllowDynamicProperties]
class CI_Model {}
class FinancialBindingState
{
    public static $calls = [];
    public static $checks = 0;
}

function log_message($level, $message) {}
function oci_new_connect() { throw new RuntimeException('Database access is forbidden.'); }
function oci_parse($connection, $sql) { return (object)['sql' => $sql, 'bindings' => []]; }
function oci_new_cursor($connection) { return (object)['cursor' => true]; }
// Mirror OCI8's non-nullable integer parameters to catch PHP 8.x argument regressions.
function oci_bind_by_name($statement, $name, &$value, $length, int $type)
{
    $statement->bindings[$name] =& $value;
    return true;
}
function oci_execute($statement, $mode)
{
    if (isset($statement->cursor)) return true;
    $values = [];
    $references = [];
    foreach ($statement->bindings as $name => $value) {
        $values[$name] = $value;
        $reference = ReflectionReference::fromArrayElement($statement->bindings, $name);
        $references[$name] = $reference ? $reference->getId() : 'unshared:' . $name;
    }
    FinancialBindingState::$calls[] = ['sql' => $statement->sql, 'values' => $values, 'references' => $references];
    $message = strpos($statement->sql, 'PAYMENT_REQ_BULK_CREATE(') !== false ? '18|1|0|0' : '1';
    if (strpos($statement->sql, 'PAYMENT_REQ_BATCH_PAY(') !== false) $message = '18|3|0|PAID';
    foreach ($statement->bindings as &$value) {
        if ($value === 'MSG_OUT' || $value === 'MSG') $value = $message;
        elseif ($value === 'NEW_ID') $value = 91;
    }
    return true;
}
function oci_fetch_all($cursor, &$rows, int $skip, int $maximum, $flags) { $rows = []; return 0; }
function oci_commit($connection) { return true; }

$root = dirname(__DIR__, 2);
require $root . '/application/core/MY_Model.php';
require $root . '/application/libraries/DBConn.php';
require $root . '/application/modules/root/models/New_rmodel.php';
require $argv[1] ?? $root . '/application/modules/payment_req/models/Payment_req_model.php';

$connection = (new ReflectionClass('DBConn'))->newInstanceWithoutConstructor();
// Select the existing model reader branch explicitly. This fixture must not
// depend on private deployment defaults or the developer ZIP's placeholders.
$connection->user = 'GFC_PAK';
$model = (new ReflectionClass('Payment_req_model'))->newInstanceWithoutConstructor();
$reader = (new ReflectionClass('New_rmodel'))->newInstanceWithoutConstructor();
$connectionProperty = new ReflectionProperty('MY_Model', 'conn');
if (PHP_VERSION_ID < 80100) $connectionProperty->setAccessible(true);
$connectionProperty->setValue($model, $connection);
$connectionProperty->setValue($reader, $connection);
$model->New_rmodel = $reader;

function check($condition, $message)
{
    FinancialBindingState::$checks++;
    if (!$condition) throw new RuntimeException($message);
}
function lastCall()
{
    return FinancialBindingState::$calls[count(FinancialBindingState::$calls) - 1];
}
function checkAmount($name, $expected, $identifiers = [])
{
    $call = lastCall();
    $actual = $call['values'][$name];
    check(is_string($actual) && is_numeric($actual) && (float)$actual === (float)$expected, $name . ' lost its decimal value');
    foreach ($identifiers as $idName => $idValue) {
        check($call['values'][$idName] === $idValue, $idName . ' changed during binding');
        check($call['references'][$name] !== $call['references'][$idName], $name . ' shares storage with ' . $idName);
    }
}
function masterData($percent, $update = false)
{
    $values = ['THE_MONTH' => 202610, 'REQ_TYPE' => 2, 'CALC_METHOD' => 1,
        'PERCENT_VAL' => $percent, 'L_VALUE' => 0, 'H_VALUE' => 18.75,
        'PAY_TYPE' => 12, 'ENTRY_DATE' => null, 'NOTE' => '', 'FILTER_MONTH' => null];
    if ($update) {
        unset($values['ENTRY_DATE']);
        $values = ['REQ_ID' => 18] + $values;
    }
    $data = [];
    foreach ($values as $name => $value) $data[] = ['name' => $name, 'value' => $value, 'type' => '', 'length' => -1];
    return $data;
}
function bulkData($amount)
{
    return ['the_month' => 202610, 'req_type' => 2, 'calc_method' => 1, 'percent_val' => 0.5,
        'req_amount' => $amount, 'pay_type' => 12, 'sal_from' => 0, 'sal_to' => 18.75,
        'branch_no' => null, 'l_value' => 0, 'h_value' => null, 'filter_month' => null,
        'note' => '', 'include_allowances' => 1, 'type2_remaining_mode' => 1];
}

try {
    foreach ([0.5, '0.50', 1.5, '1.50', 18.75, '18.75', 18, 18.0, '18', '18.00', 0, '0', 0.0, '1e-2', null, ''] as $amount) {
        foreach ([false, true] as $allowDuplicate) {
            $model->detail_add(18, 508, $amount, '', $allowDuplicate);
            checkAmount(':P_REQ_AMOUNT', $amount, [':P_REQ_ID' => 18, ':P_EMP_NO' => 508,
                ':P_ALLOW_OTHER_REQ_DUP' => $allowDuplicate ? 1 : 0]);
            check(count(lastCall()['values']) === 6, 'DETAIL_INSERT argument count changed');
        }
        $model->detail_update(18, $amount);
        checkAmount(':P_REQ_AMOUNT', $amount, [':P_DETAIL_ID' => 18]);
        $model->detail_preview_single(18, 508, $amount);
        checkAmount(':P_REQ_AMOUNT', $amount, [':P_REQ_ID' => 18, ':P_EMP_NO' => 508]);
        $model->import_line_add(18, 1, 18, $amount, '', 18, 1);
        checkAmount(':P_AMOUNT', $amount, [':P_DETAIL_ID' => 18, ':P_LINE_NO' => 1,
            ':P_EXCEL_ROW_NUM' => 18, ':P_ATTACHMENT_ID' => 18, ':P_ENTRY_USER' => 1]);
        $model->import_line_add_by_emp(18, 508, 1, 18, $amount, '', 18, 1);
        checkAmount(':P_AMOUNT', $amount, [':P_REQ_ID' => 18, ':P_EMP_NO' => 508,
            ':P_LINE_NO' => 1, ':P_ATTACHMENT_ID' => 18, ':P_ENTRY_USER' => 1]);
        check($model->cover_create_from_batch(18, 18, 'Bank', $amount, 'Cover', 'invoice', '2026-10-21') === 91,
            'Cover return contract changed');
        checkAmount(':P_TOTAL_AMOUNT', $amount, [':P_BATCH_ID' => 18, ':P_MASTER_BANK_NO' => 18]);
    }

    foreach ([0.5, 1.5, 18.75, 18.0, 0] as $percent) {
        $model->create(masterData($percent), 1, 1);
        checkAmount(':PERCENT_VAL_in', $percent, [':CALC_METHOD_in' => 1, ':P_INCLUDE_ALLOWANCES' => 1]);
        check(count(lastCall()['values']) === 13, 'PAYMENT_REQ_INSERT argument count changed');
        $model->edit(masterData($percent, true));
        checkAmount(':PERCENT_VAL_in', $percent, [':REQ_ID_in' => 18, ':CALC_METHOD_in' => 1]);
        check(count(lastCall()['values']) === 11, 'PAYMENT_REQ_UPDATE argument count changed');
    }

    foreach (['bulk_preview', 'bulk_create'] as $method) {
        $model->$method(bulkData(1.5));
        checkAmount(':P_REQ_AMOUNT', 1.5, [':P_INCLUDE_ALLOWANCES' => 1, ':P_TYPE2_REMAINING_MODE' => 1]);
        checkAmount(':P_PERCENT_VAL', 0.5, [':P_FILTER_MONTH' => 0]);
        checkAmount(':P_L_VALUE', 0, [':P_FILTER_MONTH' => 0]);
        checkAmount(':P_H_VALUE', -9);
        check(count(lastCall()['values']) === 16, $method . ' argument count changed');
        foreach (['percent_val' => -2, 'req_amount' => -3, 'sal_from' => -5, 'sal_to' => -6, 'l_value' => -8, 'h_value' => -9] as $field => $fallback) {
            foreach ([null, ''] as $empty) {
                $params = bulkData(1.5);
                $params[$field] = $empty;
                $model->$method($params);
                checkAmount(':P_' . strtoupper($field), $fallback);
            }
        }
    }

    foreach ([null, ''] as $empty) {
        $model->detail_add(18, 508, $empty);
        checkAmount(':P_REQ_AMOUNT', 0, [':P_ALLOW_OTHER_REQ_DUP' => 0]);
        $model->create(masterData($empty));
        check(lastCall()['values'][':PERCENT_VAL_in'] === null, 'Missing master percentage became zero');
        $model->edit(masterData($empty, true));
        check(lastCall()['values'][':PERCENT_VAL_in'] === null, 'Missing edited percentage became zero');
    }
    $precise = '12345678901234567890.1234';
    $model->detail_update(18, $precise);
    check(lastCall()['values'][':P_REQ_AMOUNT'] === $precise, 'Numeric text lost precision');
    $model->detail_update(18, -18.75);
    checkAmount(':P_REQ_AMOUNT', -18.75, [':P_DETAIL_ID' => 18]);

    foreach (['invalid', '18oops', '1,50', ' ', '1e999', INF, NAN, [], false, true] as $invalid) {
        $before = count(FinancialBindingState::$calls);
        check($model->detail_add(18, 508, $invalid) === 'المبلغ غير صالح', 'Invalid detail add amount accepted');
        check($model->detail_update(18, $invalid) === 'المبلغ غير صالح', 'Invalid detail update amount accepted');
        check($model->detail_preview_single(18, 508, $invalid) === 'المبلغ غير صالح', 'Invalid preview amount accepted');
        check($model->import_line_add(18, 1, 18, $invalid, '', 18, 1) === ['new_id' => 0, 'msg' => 'المبلغ غير صالح'], 'Invalid import amount accepted');
        check($model->import_line_add_by_emp(18, 508, 1, 18, $invalid, '', 18, 1) === ['new_id' => 0, 'msg' => 'المبلغ غير صالح'], 'Invalid wizard import amount accepted');
        check($model->create(masterData($invalid)) === 'قيمة مالية غير صالحة', 'Invalid master percentage accepted');
        check($model->edit(masterData($invalid, true)) === 'قيمة مالية غير صالحة', 'Invalid edited percentage accepted');
        check($model->bulk_preview(bulkData($invalid)) === ['msg' => 'قيمة مالية غير صالحة', 'rows' => []], 'Invalid bulk preview amount accepted');
        check($model->bulk_create(bulkData($invalid)) === ['ok' => false, 'msg' => 'قيمة مالية غير صالحة'], 'Invalid bulk create amount accepted');
        $rejected = false;
        try { $model->cover_create_from_batch(18, 18, 'Bank', $invalid, 'Cover', 'invoice', '2026-10-21'); }
        catch (Exception $error) { $rejected = true; }
        check($rejected, 'Invalid cover amount accepted');
        check(count(FinancialBindingState::$calls) === $before, 'Invalid input reached OCI');
    }
    foreach (['percent_val', 'req_amount', 'sal_from', 'sal_to', 'l_value', 'h_value'] as $field) {
        foreach (['bulk_preview', 'bulk_create'] as $method) {
            $params = bulkData(0.5);
            $params[$field] = 'invalid';
            $before = count(FinancialBindingState::$calls);
            $result = $model->$method($params);
            check($result['msg'] === 'قيمة مالية غير صالحة', $method . ' accepted invalid ' . $field);
            check(count(FinancialBindingState::$calls) === $before, 'Invalid ' . $field . ' reached OCI');
        }
    }
    $paid = $model->batch_pay(18);
    check($paid['ok'] && $paid['success'] === 3 && strpos($paid['msg'], '3 سجل صرف') !== false, 'Batch count label is inaccurate');
    echo 'PASS: ' . FinancialBindingState::$checks . " checks; real model and DBConn with OCI stubs, no database.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'FAIL: ' . $error->getMessage() . "\n");
    exit(1);
}
