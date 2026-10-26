<?php
/**
 * Check controller inheritance on the current PHP binary without bootstrapping CI.
 * Only class declarations/imports are evaluated; constructors/actions are never run.
 * Top-level requires are intentionally excluded: MX/Base.php creates a CI instance.
 * Optional: --files path/to/controller.php [...] checks only listed discovered files.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

function controller_declarations(string $file): array
{
    $tokens = token_get_all(file_get_contents($file), TOKEN_PARSE);
    $text = static fn($token): string => is_array($token) ? $token[1] : $token;
    $ignored = static fn($token): bool => is_array($token)
        && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);
    $namespace = '';
    $imports = '';
    $namespaceDepth = 0;
    $depth = 0;
    $declarations = [];
    $count = count($tokens);
    for ($i = 0; $i < $count; ++$i) {
        $token = $tokens[$i];
        $id = is_array($token) ? $token[0] : null;
        if ($depth === 0 && $id === T_NAMESPACE) {
            $namespace = '';
            $imports = '';
            while (++$i < $count && !in_array($tokens[$i], [';', '{'], true)) {
                $namespace .= $text($tokens[$i]);
            }
            $namespace = trim($namespace);
            if (($tokens[$i] ?? null) === '{') {
                $depth = $namespaceDepth = 1;
            }
            continue;
        }
        if ($depth === $namespaceDepth && $id === T_USE) {
            do {
                $imports .= $text($tokens[$i]);
            } while ($tokens[$i++] !== ';' && $i < $count);
            --$i;
            $imports .= "\n";
            continue;
        }
        if ($depth === $namespaceDepth
            && in_array($id, [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
            $nameIndex = $i + 1;
            while (isset($tokens[$nameIndex]) && $ignored($tokens[$nameIndex])) ++$nameIndex;
            if (!is_array($tokens[$nameIndex] ?? null) || $tokens[$nameIndex][0] !== T_STRING) {
                continue; // ::class/anonymous classes are not declarations to load.
            }
            $start = $i;
            // Keep visibility modifiers and attributes, including AllowDynamicProperties.
            for ($back = $i - 1; $back >= 0; --$back) {
                $previous = $tokens[$back];
                if ($ignored($previous) || (is_array($previous)
                    && in_array($previous[0], [T_ABSTRACT, T_FINAL, T_READONLY], true))) {
                    $start = $back;
                    continue;
                }
                if ($previous === ']') {
                    $brackets = 1;
                    while (--$back >= 0) {
                        if ($tokens[$back] === ']') ++$brackets;
                        if ($tokens[$back] === '[' || (is_array($tokens[$back]) && $tokens[$back][0] === T_ATTRIBUTE)) {
                            if (--$brackets === 0) break;
                        }
                    }
                    if ($back < 0) throw new RuntimeException('Unbalanced declaration attribute: ' . $file);
                    $start = $back;
                    continue;
                }
                break;
            }
            $end = $i;
            while ($end < $count && $tokens[$end] !== '{') ++$end;
            $classDepth = 1;
            while (++$end < $count) {
                if ($tokens[$end] === '{' || (is_array($tokens[$end])
                    && in_array($tokens[$end][0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) ++$classDepth;
                if ($tokens[$end] === '}' && --$classDepth === 0) break;
            }
            if ($end >= $count) throw new RuntimeException('Unbalanced declaration body: ' . $file);
            $source = implode('', array_map($text, array_slice($tokens, $start, $end - $start + 1)));
            $declarations[] = [
                'name' => ltrim($namespace . '\\' . $tokens[$nameIndex][1], '\\'),
                'source' => 'namespace ' . $namespace . " {\n" . $imports . $source . "\n}",
            ];
            $i = $end;
            continue;
        }
        if ($token === '{') ++$depth;
        if ($token === '}') {
            --$depth;
            if ($namespaceDepth && $depth === 0) {
                $namespace = $imports = '';
                $namespaceDepth = 0;
            }
        }
    }
    return $declarations;
}

$root = dirname(__DIR__, 2);
if (($argv[1] ?? '') === '--worker') {
    define('BASEPATH', $root . '/system/');
    define('APPPATH', $root . '/application/');
    define('FCPATH', $root . '/');
    define('ENVIRONMENT', 'testing');
    error_reporting(E_ALL);
    set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });
    try {
        foreach (['system/core/Controller.php', 'application/third_party/MX/Controller.php', 'application/core/MY_Controller.php',
            'system/core/Model.php', 'application/core/MY_Model.php'] as $base) {
            foreach (controller_declarations($root . '/' . $base) as $declaration) eval($declaration['source']);
        }
        $declarations = controller_declarations($argv[2]);
        foreach ($declarations as $declaration) eval($declaration['source']);
        $controllers = 0;
        foreach ($declarations as $declaration) {
            if (is_subclass_of($declaration['name'], 'CI_Controller') || is_subclass_of($declaration['name'], 'MX_Controller')) {
                ++$controllers;
            }
        }
        echo json_encode(['status' => 'ok', 'declarations' => count($declarations), 'controllers' => $controllers]) . PHP_EOL;
    } catch (Throwable $error) {
        fwrite(STDERR, get_class($error) . ': ' . $error->getMessage() . PHP_EOL);
        exit(1);
    }
    exit;
}

$files = [];
foreach (['application/controllers', 'application/modules'] as $directory) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (strtolower($file->getExtension()) === 'php'
            && str_contains(str_replace('\\', '/', $file->getPathname()), '/controllers/')) {
            $files[] = $file->getPathname();
        }
    }
}
sort($files);
if (($argv[1] ?? '') === '--files') {
    $selected = array_map('realpath', array_slice($argv, 2));
    if (!$selected || in_array(false, $selected, true)) {
        fwrite(STDERR, 'Provide existing controller-scope PHP files after --files.' . PHP_EOL);
        exit(2);
    }
    $available = array_map('realpath', $files);
    foreach ($selected as $file) {
        if (!in_array($file, $available, true)) {
            fwrite(STDERR, 'Not in recursive controller scope: ' . $file . PHP_EOL);
            exit(2);
        }
    }
    $files = array_values(array_unique($selected));
}
$failures = $declarations = $controllers = $skipped = 0;
foreach ($files as $file) {
    // -n excludes php.ini extensions (including OCI8); each file has its own class namespace/process.
    $command = [PHP_BINARY, '-n', '-d', 'error_reporting=-1', __FILE__, '--worker', $file];
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['redirect', 1]], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Could not start declaration worker.');
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $status = proc_close($process);
    $result = json_decode(trim($output), true);
    $relative = str_replace('\\', '/', substr($file, strlen($root) + 1));
    if ($status !== 0 || ($result['status'] ?? null) !== 'ok') {
        ++$failures;
        echo $relative . ': ' . trim($output) . PHP_EOL;
    } elseif ($result['declarations'] === 0) {
        ++$skipped;
        echo $relative . ': skipped (no top-level class declaration)' . PHP_EOL;
    } else {
        $declarations += $result['declarations'];
        $controllers += $result['controllers'];
        if ($result['controllers'] < $result['declarations']) {
            echo $relative . ': ' . ($result['declarations'] - $result['controllers']) . ' non-controller declaration(s) checked' . PHP_EOL;
        }
    }
}
echo PHP_VERSION . ': ' . count($files) . ' files in recursive controller scope; ' . $controllers . ' controller declarations and '
    . ($declarations - $controllers) . ' other declarations checked; '
    . $failures . ' failures; ' . $skipped . ' skipped. Constructors/actions/includes were not executed.' . PHP_EOL;
exit($failures ? 1 : 0);
