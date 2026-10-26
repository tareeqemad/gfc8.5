<?php
/** Compile active source without executing it or connecting to Oracle. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$root = dirname(__DIR__, 2);
$files = array($root . '/index.php', $root . '/barcode.php');
foreach (array('application', 'system', 'barcode', 'tests', 'tools/php85') as $directory) {
    $filter = new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS),
        static function ($entry) use ($root) {
            $path = str_replace('\\', '/', $entry->getPathname());
            $base = str_replace('\\', '/', $root);
            // Retained historical sources are replaced by root Composer packages.
            if (str_starts_with($path, $base . '/application/third_party/PhpSpreadsheet')) return false;
            $legacy = $base . '/application/third_party/phpoffice/vendor/';
            if (str_starts_with($path, $legacy) && $path !== $legacy . 'autoload.php') return false;
            return true;
        }
    );
    $iterator = new RecursiveIteratorIterator($filter);
    foreach ($iterator as $file) {
        if (strtolower($file->getExtension()) === 'php') {
            $files[] = $file->getPathname();
        }
    }
}
$run = static function ($batch) {
    $command = array_merge(array(PHP_BINARY, '-n', '-d', 'short_open_tag=0', '-d', 'error_reporting=-1', '-l'), $batch);
    $process = proc_open($command, array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('redirect', 1)), $pipes);
    if (!is_resource($process)) throw new RuntimeException('Could not start PHP lint.');
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    return array(proc_close($process), $output);
};
$failures = 0;
foreach (array_chunk($files, 40) as $batch) {
    list($status, $output) = $run($batch);
    if ($status === 0 && substr_count($output, 'No syntax errors detected in ') === count($batch)
        && trim(preg_replace('/^No syntax errors detected in .*\R?/m', '', $output)) === '') continue;
    // A compile error may stop multi-file lint; check every file in that batch.
    foreach ($batch as $file) {
        list($status, $output) = $run(array($file));
        $diagnostics = trim(preg_replace('/^No syntax errors detected in .*\R?/m', '', $output));
        if ($status !== 0 || $diagnostics !== '') {
            echo $file . PHP_EOL . $diagnostics . PHP_EOL;
            ++$failures;
        }
    }
}
echo count($files) . ' active PHP files compiled; ' . $failures . ' files with errors or deprecations. Runtime behavior requires separate tests.' . PHP_EOL;
exit($failures ? 1 : 0);
