<?php
// XML parsing/serialization fixtures only: no sockets, application bootstrap or Oracle.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$root = dirname(__DIR__, 2);
define('BASEPATH', $root . '/system/');
function log_message($level, $message) {}
function is_php($version) { return version_compare(PHP_VERSION, $version, '>='); }
function show_error($message) { throw new RuntimeException($message); }
require BASEPATH . 'libraries/Xmlrpc.php';
require BASEPATH . 'libraries/Xmlrpcs.php';
$checks = 0;
function rpc_check($condition, $message) {
    ++$GLOBALS['checks'];
    if (!$condition) throw new RuntimeException($message);
}
function rpc_response($xml) {
    $stream = fopen('php://memory', 'r+');
    fwrite($stream, "HTTP/1.1 200 OK\r\nContent-Type: text/xml\r\n\r\n" . $xml);
    rewind($stream);
    try { return (new XML_RPC_Message('test'))->parseResponse($stream); }
    finally { fclose($stream); }
}

$expected = new XML_RPC_Values('مرحبا & 00123', 'string');
$response = rpc_response((new XML_RPC_Response($expected))->prepare_response());
rpc_check($response->faultCode() === 0, 'Valid response was rejected.');
rpc_check($response->value()->scalarval() === 'مرحبا & 00123', 'Unicode/entity response changed.');
rpc_check(count($response->headers) === 2, 'HTTP response headers were lost.');

$struct = new XML_RPC_Values([
    'code' => new XML_RPC_Values('00123', 'string'),
    'amount' => new XML_RPC_Values(12.5, 'double'),
    'active' => new XML_RPC_Values(true, 'boolean'),
], 'struct');
$response = rpc_response((new XML_RPC_Response($struct))->prepare_response());
$response->xss_clean = false;
$decoded = $response->xmlrpc_decoder($response->value());
rpc_check($decoded === ['code' => '00123', 'amount' => 12.5, 'active' => 1], 'Structured response changed: ' . var_export($decoded, true));
$fault = rpc_response((new XML_RPC_Response(0, 17, 'fixture error'))->prepare_response());
rpc_check($fault->faultCode() === 17 && $fault->faultString() === 'fixture error', 'Fault response changed.');
rpc_check(rpc_response('<methodResponse>')->faultCode() != 0, 'Malformed response was accepted.');
rpc_check(rpc_response('<methodResponse><params></params></methodResponse>')->faultCode() != 0, 'A response with no value was accepted.');
rpc_check(rpc_response('<methodResponse><unexpected /></methodResponse>')->faultCode() != 0, 'An unknown response element was accepted.');

$server = new CI_Xmlrpcs();
$server->xss_clean = false;
$response = $server->parseRequest('<methodCall><methodName>system.listMethods</methodName><params></params></methodCall>');
rpc_check($response->faultCode() === 0, 'Local XML-RPC system method failed.');
$methods = $response->xmlrpc_decoder($response->value());
rpc_check(in_array('system.listMethods', $methods, true) && count($methods) === 4, 'System method list changed.');
rpc_check($server->parseRequest('<methodCall><methodName>unknown</methodName></methodCall>')->faultCode() != 0, 'Unknown method was accepted.');
rpc_check($server->parseRequest('<methodCall>')->faultCode() != 0, 'Malformed request was accepted.');
rpc_check($server->parseRequest('<methodCall><unexpected /></methodCall>')->faultCode() != 0, 'An unknown request element was accepted.');

$timezone = date_default_timezone_get();
try {
    date_default_timezone_set('Etc/GMT-2');
    $time = gmmktime(3, 4, 5, 1, 2, 2020);
    // Preserve this legacy encoder's existing branch selection: false=GMT, true=local.
    rpc_check($expected->iso8601_encode($time) === '20200102T03:04:05', 'GMT timestamp encoding changed.');
    rpc_check($expected->iso8601_encode($time, true) === '20200102T05:04:05', 'Local timestamp selection changed.');
    rpc_check($response->iso8601_decode('20200102T03:04:05', true) === $time, 'UTC date decoding changed.');
} finally { date_default_timezone_set($timezone); }
echo "PASS: {$checks} XML-RPC client/server parsing, errors and date checks; no network or database.\n";
