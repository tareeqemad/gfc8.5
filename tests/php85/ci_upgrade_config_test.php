<?php
/** Offline CI upgrade config/consumer checks. Optional first argument is a staged CI root. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$project = dirname(__DIR__, 2);
$target = isset($argv[1]) ? realpath($argv[1]) : $project;
if ($target === false) throw new RuntimeException('CI target directory does not exist.');
define('BASEPATH', $target . '/system/');
define('APPPATH', $target . '/application/');
define('ENVIRONMENT', 'testing');
$checks = 0;
function upgrade_config_check($condition, $message)
{
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    ++$checks;
}
function log_message($level, $message) {}
function &get_instance() { static $instance; $instance ??= new stdClass(); return $instance; }
require BASEPATH . 'core/Common.php';

// Both modern CI's return contract and legacy direct include callers must see the map.
$returned = include APPPATH . 'config/mimes.php';
upgrade_config_check(is_array($returned) && isset($mimes) && $returned === $mimes,
    'MIME config must return an array and retain its legacy $mimes assignment.');
$loaded = get_mimes();
upgrade_config_check($loaded === $returned, 'Actual CI get_mimes() must load the complete config.');
foreach (['docx' => 'inode/x-empty', 'xlsx' => 'application/octet-stream', 'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
          'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'] as $extension => $mime) {
    upgrade_config_check(in_array($mime, (array)($loaded[$extension] ?? []), true),
        $extension . ': required local/official MIME alias is missing.');
}
require BASEPATH . 'libraries/Upload.php';
$upload = new CI_Upload();
$upload->allowed_types = ['csv', 'xls', 'xlsx', 'docx', 'pdf', 'pptx'];
foreach ([
    ['xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
    ['xlsx', 'application/zip'], ['xlsx', 'application/octet-stream'],
    ['docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    ['docx', 'inode/x-empty'], ['xls', 'application/vnd.ms-excel'],
    ['csv', 'text/csv'], ['csv', 'text/plain'], ['pdf', 'application/pdf'],
    ['pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
] as [$extension, $mime]) {
    $upload->file_ext = '.' . $extension;
    $upload->file_type = $mime;
    upgrade_config_check($upload->is_allowed_filetype(), $extension . ': actual Upload rejected a supported MIME type.');
}
$upload->file_ext = '.xlsx';
$upload->file_type = 'text/html';
upgrade_config_check(!$upload->is_allowed_filetype(), 'Mismatched XLSX content type must still be rejected.');
$upload->file_ext = '.php';
$upload->file_type = 'application/x-httpd-php';
upgrade_config_check(!$upload->is_allowed_filetype(), 'The MIME map must not override the upload extension allowlist.');

require BASEPATH . 'libraries/User_agent.php';
function upgrade_agent($userAgent)
{
    $_SERVER['HTTP_USER_AGENT'] = $userAgent;
    return new CI_User_agent();
}
$chrome = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.7339.0 Safari/537.36';
$firefox = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0';
$safari = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/605.1.15';
$opera = 'Opera/9.80 (Windows NT 6.1; U; en) Presto/2.12.388 Version/12.16';
$ie = 'Mozilla/5.0 (Windows NT 6.1; Trident/7.0; rv:11.0) like Gecko';
$browserCases = [
    [$chrome, 'Chrome', '140.0.7339.0'],
    [$firefox, 'Firefox', '140.0'],
    [$chrome . ' Edg/140.0.0.0', 'Edge', '140.0.0.0'],
    [$chrome . ' EdgA/140.0.0.0', 'Edge', '140.0.0.0'],
    [$safari . ' EdgiOS/140.0.0.0', 'Edge', '140.0.0.0'],
    [$chrome . ' OPR/122.0.0.0', 'Opera', '122.0.0.0'],
    [$safari, 'Safari', '605.1.15'],
    [$opera, 'Opera', '12.16'], [$ie, 'Internet Explorer', '11.0'],
];
foreach ($browserCases as [$ua, $brand, $version]) {
    $agent = upgrade_agent($ua);
    upgrade_config_check($agent->browser() === $brand && $agent->version() === $version,
        $brand . ': specific browser detection must precede embedded Chrome/Mozilla tokens.');
}
upgrade_config_check(upgrade_agent($chrome)->platform() === 'Windows 10', 'Modern Windows platform entry must be active.');
upgrade_config_check(upgrade_agent('Mozilla/5.0 (Windows NT 6.0) Firefox/140.0')->platform() === 'Windows Longhorn',
    'The existing local Windows NT 6.0 display label must be retained.');
upgrade_config_check(upgrade_agent('askjeeves/1.0')->robot() === 'AskJeeves', 'Legacy local robot token must remain recognized.');
upgrade_config_check(upgrade_agent('ask jeeves/1.0')->robot() === 'Ask Jeeves', 'Official robot token must remain recognized.');

// Execute the real browser gate without the controller constructor, session or database.
class MY_Controller { public $load; public $agent; }
require $project . '/application/controllers/Login.php';
$login = (new ReflectionClass('Login'))->newInstanceWithoutConstructor();
$login->load = new class { public function library($name) {} };
foreach ([
    [$chrome, 'true'], [$firefox, 'true'], [$safari, 'true'],
    [$chrome . ' Edg/140.0.0.0', 'true'], [$chrome . ' OPR/122.0.0.0', 'true'],
    [$safari . ' EdgiOS/140.0.0.0', 'true'],
    [str_replace('Safari/605.1.15', 'Safari/499.1.15', $safari) . ' EdgiOS/140.0.0.0', 'version'],
    [strtolower($chrome . ' Edg/140.0.0.0'), 'true'],
    [strtolower($chrome . ' OPR/122.0.0.0'), 'true'],
    [strtolower($safari . ' EdgiOS/140.0.0.0'), 'true'],
    [str_replace('Chrome/140.0.7339.0', 'Chrome/34.0.0.0', $chrome), 'version'],
    [str_replace('Chrome/140.0.7339.0', 'Chrome/34.0.0.0', $chrome) . ' Edg/140.0.0.0', 'version'],
    [$opera, 'browser'], [$ie, 'browser'],
] as [$ua, $expected]) {
    $login->agent = upgrade_agent($ua);
    upgrade_config_check($login->check_browser() === $expected,
        'Actual login gate changed for ' . $login->agent->browser() . ' / ' . $login->agent->version());
}
echo 'CI upgrade config checks: ' . $checks . ' passed on PHP ' . PHP_VERSION . ".\n";
