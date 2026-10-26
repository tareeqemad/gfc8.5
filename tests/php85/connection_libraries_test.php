<?php
// Run with php -n so the stand-ins below cannot contact Oracle.
if (PHP_SAPI !== 'cli' || extension_loaded('oci8')) {
    fwrite(STDERR, "Use PHP CLI with -n; OCI must be disabled.\n");
    exit(1);
}
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
define('BASEPATH', __DIR__);
define('SQLT_CHR', 1);
define('SQLT_RSET', 116);
define('OCI_B_CURSOR', 116);
define('OCI_DEFAULT', 0);
define('OCI_FETCHSTATEMENT_BY_ROW', 16);

$bindings = [];
$fetches = [];
$checks = 0;
function oci_new_connect() { throw new RuntimeException('Database access forbidden.'); }
function oci_parse($connection, $sql) { return (object) ['sql' => $sql]; }
function oci_new_cursor($connection) { return (object) ['cursor' => true]; }
// Use OCI's non-nullable scalar types to catch null argument regressions.
function oci_bind_by_name($statement, $name, &$value, int $length, int $type) {
    $GLOBALS['bindings'][$name] = ['value' => $value, 'length' => $length, 'type' => $type];
    return true;
}
function oci_bind_array_by_name($statement, $name, &$values, int $size, int $length, int $type) {
    $GLOBALS['bindings'][$name] = ['value' => $values, 'size' => $size, 'length' => $length, 'type' => $type];
    return true;
}
function oci_execute($statement, $mode) { return true; }
function oci_fetch_all($cursor, &$rows, int $skip, int $maximum, int $flags) {
    $rows = [['ID' => 42, 'LABEL' => 'كهرباء']];
    $GLOBALS['fetches'][] = [$skip, $maximum, $flags];
    return 1;
}
function oci_commit($connection) { return true; }
function connection_library_check($condition, $label) {
    $GLOBALS['checks']++;
    if (!$condition) { throw new RuntimeException($label); }
}

$root = dirname(__DIR__, 2);
require $root . '/application/libraries/DBConn.php';
require $root . '/application/core/GISConn.php';
require $root . '/application/core/BillsConn.php';
foreach (['DBConn', 'GISConn', 'BillsConn'] as $class) {
    $connection = (new ReflectionClass($class))->newInstanceWithoutConstructor();
    $bindings = [];
    $fetches = [];
    $result = $connection->excuteProcedures('OFFLINE_FIXTURE', 'READ_DATA', [
        ['name' => ':nullable', 'value' => null, 'length' => 20, 'type' => ''],
        ['name' => ':identifier', 'value' => '001234', 'length' => 20, 'type' => SQLT_CHR],
        ['name' => ':decimal', 'value' => 18.75, 'length' => 20, 'type' => SQLT_CHR],
        ['name' => ':items', 'value' => ['001', '002'], 'length' => 20, 'type' => ''],
        ['name' => ':empty_items', 'value' => [], 'length' => 20, 'type' => ''],
        ['name' => ':rows', 'value' => 'result_rows', 'length' => -1, 'type' => 'cur'],
    ]);
    connection_library_check(array_key_exists('', $result) && $result[''] === null, $class . ' preserves null bind value');
    connection_library_check($bindings[':nullable']['type'] === 0, $class . ' default scalar type retains zero');
    connection_library_check($bindings[':identifier']['value'] === '001234' && $bindings[':identifier']['type'] === SQLT_CHR, $class . ' explicit string binding');
    connection_library_check($result[18] === 18.75 && $bindings[':decimal']['value'] === 18.75, $class . ' retains decimal value and historical integer output key');
    connection_library_check($bindings[':items'] === ['value' => ['001', '002'], 'size' => 2, 'length' => -1, 'type' => 0], $class . ' array binding');
    connection_library_check($bindings[':empty_items'] === ['value' => [], 'size' => 500, 'length' => 500, 'type' => 0], $class . ' empty array binding');
    connection_library_check($result['result_rows'] === [['ID' => 42, 'LABEL' => 'كهرباء']] && $fetches === [[0, 0, OCI_FETCHSTATEMENT_BY_ROW]], $class . ' cursor fetch');
}
echo 'PASS: ' . $checks . ' DBConn/GISConn/BillsConn binding checks; constructors skipped, OCI stubs, no database/network.' . PHP_EOL;
