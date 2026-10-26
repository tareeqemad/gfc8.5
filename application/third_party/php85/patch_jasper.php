<?php
/**
 * Reproducible PHP 8.5 fixes for the pinned jaspersoft/rest-client 2.0.0.
 * Composer invokes this after install/update. Do not edit ignored vendor files
 * by hand: these exact replacements also verify the expected upstream source.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$project = dirname(__DIR__, 3);
$patches = [
    'Client/Client.php' => [
        ['protected $hostname;', "protected \$hostname;\n    protected \$serverUrl;"],
    ],
    'Tool/RESTRequest.php' => [
        ['protected $response_info;', "protected \$response_info;\n    protected \$response_headers;"],
        ['curl_close($ch);', '$ch = null;'],
        ['curl_close($curlHandle);', '$curlHandle = null;'],
        ['(integer) $expectedCode', '(int) $expectedCode'],
        ['sendBinary($url, $expectedCodes = array(200), $body', 'sendBinary($url, $expectedCodes, $body'],
    ],
    'Tool/Util.php' => [
        ["http_build_query(\$params, null, '&')", "http_build_query(\$params, '', '&')"],
    ],
    'Service/RepositoryService.php' => [
        ['RepositorySearchCriteria $criteria = null', '?RepositorySearchCriteria $criteria = null'],
    ],
];

foreach ($patches as $relative => $replacements) {
    $path = $project . '/vendor/jaspersoft/rest-client/src/Jaspersoft/' . $relative;
    $original = file_get_contents($path);
    if ($original === false) {
        throw new RuntimeException('Cannot read Jasper source: ' . $relative);
    }
    $updated = str_replace("\r\n", "\n", $original);
    foreach ($replacements as [$before, $after]) {
        if (strpos($updated, $after) !== false) {
            continue;
        }
        if (strpos($updated, $before) === false) {
            throw new RuntimeException('Unexpected Jasper source; review PHP 8.5 patch for ' . $relative);
        }
        $updated = str_replace($before, $after, $updated);
    }
    if (strpos($original, "\r\n") !== false) {
        $updated = str_replace("\n", "\r\n", $updated);
    }
    if ($updated !== $original && file_put_contents($path, $updated) === false) {
        throw new RuntimeException('Cannot write Jasper compatibility patch: ' . $relative);
    }
}

echo "Jasper 2.0.0 PHP 8.5 compatibility patch applied.\n";
