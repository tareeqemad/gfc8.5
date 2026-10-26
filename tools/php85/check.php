<?php
/** Read-only deployment checks; run with the PHP binary serving GFC. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$failures = 0;
$check = static function ($ok, $message) use (&$failures) {
    echo ($ok ? '[OK] ' : '[FAIL] ') . $message . PHP_EOL;
    $failures += $ok ? 0 : 1;
};

$check(PHP_VERSION_ID >= 80512 && PHP_VERSION_ID < 80600, 'PHP 8.5.12 or a newer 8.5 security release; running ' . PHP_VERSION);
echo 'Binary: ' . PHP_BINARY . PHP_EOL;
echo 'Configuration: ' . (php_ini_loaded_file() ?: '(none)') . PHP_EOL;
foreach (array('bcmath', 'ctype', 'curl', 'dom', 'fileinfo', 'filter', 'gd', 'iconv', 'libxml', 'mbstring', 'oci8', 'openssl', 'session', 'simplexml', 'xml', 'xmlreader', 'xmlwriter', 'zip', 'zlib') as $extension) {
    $check(extension_loaded($extension), 'Extension ' . $extension);
}
if (extension_loaded('oci8')) {
    echo 'OCI8: ' . phpversion('oci8') . '; Oracle client: ' . oci_client_version() . PHP_EOL;
}

$root = dirname(__DIR__, 2);
$moduleStyles = array();
$moduleFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
    $root . '/application/modules', FilesystemIterator::SKIP_DOTS
));
foreach ($moduleFiles as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }
    preg_match_all('~assets-n/css/modules/[a-zA-Z0-9_./-]+\.css~',
        file_get_contents($file->getPathname()), $matches);
    foreach ($matches[0] as $asset) {
        $moduleStyles[$asset] = true;
    }
}
ksort($moduleStyles);
foreach (array_keys($moduleStyles) as $asset) {
    $check(is_file($root . '/' . $asset) && filesize($root . '/' . $asset) > 0,
        'Module stylesheet included in deployment: ' . $asset);
}

$autoload = $root . '/vendor/autoload.php';
$check(is_file($autoload), 'Composer dependencies installed at vendor/autoload.php');
if (is_file($autoload)) {
    require $autoload;
    foreach (array('PhpOffice\\PhpSpreadsheet\\Spreadsheet', 'Jaspersoft\\Client\\Client') as $class) {
        $check(class_exists($class), 'Autoload ' . $class);
    }
}

echo 'No database connection or application request was made.' . PHP_EOL;
exit($failures ? 1 : 0);
