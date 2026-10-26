<?php
/** Check the same default Oracle account/endpoint used before application login. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
if (!extension_loaded('oci8')) {
    fwrite(STDERR, "OCI8 is not loaded in this PHP runtime.\n");
    exit(1);
}

define('BASEPATH', dirname(__DIR__, 2) . '/system/');
require dirname(__DIR__, 2) . '/application/libraries/DBConn.php';
// Read existing settings without bootstrapping the application or its models.
// Credentials are used only for this configured Oracle endpoint and never printed.
$settings = (new ReflectionClass('DBConn'))->getDefaultProperties();
$started = microtime(true);
$connection = @oci_new_connect($settings['user'], $settings['pass'], $settings['instance'], $settings['encode']);
unset($settings);
if ($connection === false) {
    $error = oci_error();
    fwrite(STDERR, 'Oracle connection failed: ORA-' . str_pad((string) $error['code'], 5, '0', STR_PAD_LEFT) . ". Check GEDCO VPN/company-network access.\n");
    exit(1);
}

$statement = oci_parse($connection, 'SELECT 1 AS CONNECTION_OK FROM DUAL');
try {
    if ($statement === false || !oci_execute($statement, OCI_NO_AUTO_COMMIT)) {
        fwrite(STDERR, "Connected, but the read-only validation query failed.\n");
        exit(1);
    }
    $row = oci_fetch_assoc($statement);
    if ((int) ($row['CONNECTION_OK'] ?? 0) !== 1) {
        fwrite(STDERR, "Unexpected Oracle validation result.\n");
        exit(1);
    }
    echo 'PASS: Oracle login and SELECT 1 FROM DUAL; PHP ' . PHP_VERSION . ', OCI8 ' . phpversion('oci8')
        . ', ' . round(microtime(true) - $started, 2) . "s. No data changed.\n";
} finally {
    if ($statement !== false) oci_free_statement($statement);
    oci_close($connection);
}
