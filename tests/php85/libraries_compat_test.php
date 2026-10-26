<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Exercise local library behavior with E_ALL; no application bootstrap or network.
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return false;
});

$root = dirname(__DIR__, 2);
define('BASEPATH', $root . '/system/');
define('APPPATH', $root . '/application/');
require APPPATH . 'config/constants.php';
$cachePath = $root . '/temp/php85-library-' . bin2hex(random_bytes(6)) . '/';
mkdir($cachePath);
$libraryCi = new stdClass();
$libraryCi->config = new class($cachePath) {
    private $path;
    public function __construct($path) { $this->path = $path; }
    public function item($name) {
        return ['cache_expiration' => 5, 'cache_path' => $this->path, 'encryption_key' => 'offline-library-fixture'][$name] ?? null;
    }
};
$libraryCi->load = new class {
    public function config($name, $sections) {}
    public function get_package_paths($includeBase = false) { return [APPPATH, BASEPATH]; }
};
function &get_instance() { return $GLOBALS['libraryCi']; }
function config_item($key) { return $key === 'subclass_prefix' ? 'MY_' : null; }
function log_message($level, $message) {}
function show_error($message) { throw new RuntimeException($message); }
function is_really_writable($path) {
    // The fixture directory was just created and written above. Windows may
    // report is_writable() false for directories; CI itself uses a write probe.
    return $path === $GLOBALS['cachePath'] && is_dir($path);
}

require BASEPATH . 'libraries/Driver.php';
require APPPATH . 'libraries/Minify/Minify.php';
require APPPATH . 'libraries/Minify/drivers/Minify_js.php';
require APPPATH . 'libraries/Minify/drivers/Minify_css.php';
require APPPATH . 'libraries/Encrypt.php';
require APPPATH . 'libraries/Mcrypt.php';
require APPPATH . 'libraries/Scache.php';

$checks = 0;
$failures = [];
function library_check($label, $callback) {
    global $checks, $failures;
    $checks++;
    try {
        if ($callback() !== true) { throw new RuntimeException('Unexpected result'); }
    } catch (Throwable $error) {
        $failures[] = $label . ': ' . $error->getMessage();
    }
}

try {
    foreach ([
        ['', ''],
        [' ', ''],
        ['// end of file comment', ''],
        ['/* comment */', ''],
        ['var total = 7; // no final newline', 'var total=7;'],
        ["var total = 7;\n", 'var total=7;'],
        ['var label = "كهرباء غزة";', 'var label="كهرباء غزة";'],
        ['var match = /[a-z\/]+/;', 'var match=/[a-z\/]+/;'],
    ] as $index => [$input, $expected]) {
        library_check('JS minification case ' . $index, function () use ($input, $expected) {
            return trim(JSMin::minify($input)) === $expected;
        });
    }
    foreach (["var text = 'unfinished", 'var expression = /unfinished', 'var expression = /[unfinished', '/* unfinished'] as $input) {
        library_check('JS invalid input reports its parse exception', function () use ($input) {
            try { JSMin::minify($input); } catch (JSMinException $expected) { return true; }
            return false;
        });
    }
    $css = new Minify_css();
    library_check('CSS compaction preserves quoted contents', function () use ($css) {
        return trim($css->min('/* comment */ .item { color: red; content: "a b"; }')) === '.item{color:red;content:"a b";}';
    });
    library_check('CSS aggregate strips its duplicate charset', function () use ($css) {
        return trim($css->min('@charset "utf-8"; .item { color: blue; }', true, true)) === '.item{color:blue;}';
    });
    library_check('CSS uncompressed mode', function () use ($css) {
        return trim($css->min(' .item { color: red; } ', false)) === '.item { color: red; }';
    });
    $minify = new Minify();
    library_check('CI driver loads JS by its public name', function () use ($minify) {
        return trim($minify->js->min('var x = 1;')) === 'var x=1;';
    });
    library_check('CI driver loads CSS by its public name', function () use ($minify) {
        return trim($minify->css->min('a { color: red; }')) === 'a{color:red;}';
    });
    $cssFixture = $cachePath . 'fixture.css';
    file_put_contents($cssFixture, '@charset "utf-8"; a { color: red; }');
    library_check('Combined CSS files retain exactly one charset', function () use ($minify, $cssFixture) {
        $combined = $minify->combine_files([$cssFixture], 'css');
        return substr_count($combined, '@charset') === 1 && str_contains($combined, 'a{color:red;}');
    });

    $cache = new Scache();
    library_check('Cache missing key', function () use ($cache) { return $cache->read('missing') === false; });
    library_check('Cache round trip with Arabic HTML', function () use ($cache) {
        $value = '<p>شركة الكهرباء 001234</p>';
        $cache->write('fixture', $value);
        // Existing cache format retains its separator newline before the payload.
        return $cache->read('fixture') === "\n" . $value;
    });
    library_check('Cache clear', function () use ($cache) {
        $cache->clear('fixture');
        return $cache->read('fixture') === false;
    });
    library_check('Expired cache is rejected', function () use ($cache) {
        $cache->expiration = -1;
        $cache->write('expired', 'old');
        return $cache->read('expired') === false;
    });

    $encrypt = new CI_Encrypt();
    foreach (['', '001234', 'شركة الكهرباء'] as $input) {
        library_check('Encryption round trip', function () use ($encrypt, $input) {
            return $encrypt->decode($encrypt->encode($input)) === $input;
        });
    }
    library_check('Encryption rejects invalid data', function () use ($encrypt) { return $encrypt->decode('!invalid!') === false; });
    library_check('Encryption rejects wrong key', function () use ($encrypt) { return $encrypt->decode($encrypt->encode('value'), 'wrong') === false; });
    library_check('Encryption rejects modified ciphertext', function () use ($encrypt) {
        $data = base64_decode($encrypt->encode('value'));
        $data[strlen($data) - 1] = chr(ord($data[strlen($data) - 1]) ^ 1);
        return $encrypt->decode(base64_encode($data)) === false;
    });
    library_check('Legacy OpenSSL wrapper round trip', function () {
        $key = str_repeat('k', 32);
        $iv = mcrypt_create_iv(mcrypt_get_iv_size('AES-256-CBC', 'cbc'));
        return mcrypt_decrypt('AES-256-CBC', $key, mcrypt_encrypt('AES-256-CBC', $key, 'fixture', 'cbc', $iv), 'cbc', $iv) === 'fixture';
    });
} finally {
    foreach (glob($cachePath . '*') as $path) { unlink($path); }
    rmdir($cachePath);
}

if ($failures) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}
echo 'PASS: ' . $checks . ' library behavior checks on PHP ' . PHP_VERSION . "; E_ALL, no network/database\n";
