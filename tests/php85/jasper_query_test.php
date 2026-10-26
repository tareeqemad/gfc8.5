<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

// Exercise report parameter encoding without connecting to Jasper or Oracle.
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return false;
});

require dirname(__DIR__, 2) . '/vendor/jaspersoft/rest-client/src/Jaspersoft/Tool/Util.php';

$cases = [
    ['empty parameters', [], ''],
    ['boolean parameters', ['enabled' => true, 'disabled' => false], 'enabled=true&disabled=false'],
    ['repeated report parameters', ['ids' => ['001', '002']], 'ids=001&ids=002'],
    ['null omission and empty value', ['omit' => null, 'empty' => ''], 'empty='],
    ['escaping', ['search' => 'a&b + c'], 'search=a%26b+%2B+c'],
    ['numeric key', [7 => 'value'], '7=value'],
    ['Arabic parameter', ['label' => 'ع'], 'label=%D8%B9'],
    ['associative array key', ['filter' => ['name' => 'x']], 'filter%5Bname%5D=x'],
    ['multi-digit repeated index', ['ids' => [10 => '010', 11 => '011']], 'ids=010&ids=011'],
];

foreach ($cases as [$label, $params, $expected]) {
    $actual = \Jaspersoft\Tool\Util::query_suffix($params);
    if ($actual !== $expected) {
        throw new RuntimeException('Jasper parameter encoding failed: ' . $label);
    }
}

restore_error_handler();
echo 'PASS: ' . count($cases) . " Jasper query encoding cases without warnings or network access\n";
