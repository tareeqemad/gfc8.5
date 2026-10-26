<?php
// Generate only synthetic images in a unique test-owned directory; no application bootstrap.
if (PHP_SAPI !== 'cli' || extension_loaded('oci8') || !extension_loaded('gd')) {
    fwrite(STDERR, "Run PHP CLI -n with only the GD extension enabled.\n");
    exit(1);
}
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$root = dirname(__DIR__, 2);
define('BASEPATH', $root . '/system/');
function log_message($level, $message) {}
function is_really_writable($path) {
    $probe = $path . '.write-probe';
    if (file_put_contents($probe, '') === false) return false;
    return unlink($probe);
}
require $root . '/system/helpers/captcha_helper.php';
$directory = $root . '/temp/php85-captcha-test-' . bin2hex(random_bytes(6)) . '/';
mkdir($directory, 0700, true);
$checks = 0;
function captcha_check($condition, $message) {
    global $checks;
    ++$checks;
    if (!$condition) throw new RuntimeException($message);
}
try {
    // Files unrelated to generated CAPTCHAs must never be interpreted as timestamps.
    file_put_contents($directory . 'keep.txt', 'fixture');
    file_put_contents($directory . 'logo.png', 'fixture');
    $expired = (microtime(true) - 9000) . '.png';
    file_put_contents($directory . $expired, 'fixture');
    foreach ([
        ['word' => 'ABC1234'],
        ['word' => 'TTF1234', 'font_path' => $root . '/assets-n/iconfonts/font-awesome/fonts/fontawesome-webfont.ttf'],
        ['pool' => '123456789', 'word_length' => 7],
    ] as $case) {
        $result = create_captcha($case + ['img_path' => $directory, 'img_url' => '/synthetic-captcha/', 'img_width' => 151, 'img_height' => 31]);
        captcha_check(is_array($result), 'Image generation must succeed.');
        captcha_check(isset($case['word']) ? $result['word'] === $case['word'] : preg_match('/^[1-9]{7}$/D', $result['word']) === 1, 'Supplied/generated CAPTCHA word changed.');
        captcha_check(is_file($directory . $result['filename']), 'Image was not written.');
        $dimensions = getimagesize($directory . $result['filename']);
        captcha_check($dimensions[0] === 151 && $dimensions[1] === 31, 'Image dimensions changed.');
        captcha_check(str_contains($result['image'], '/synthetic-captcha/' . $result['filename']), 'Returned HTML lost the local image URL.');
    }
    captcha_check(!is_file($directory . $expired), 'Expired generated image was not removed.');
    captcha_check(is_file($directory . 'keep.txt') && is_file($directory . 'logo.png'), 'An unrelated file was removed.');
    captcha_check(create_captcha(['img_path' => '', 'img_url' => '/synthetic-captcha/']) === false, 'Missing image path must still be rejected.');
} finally {
    // This directory was created above and is never reused from user input.
    foreach (glob($directory . '*') as $file) if (is_file($file)) unlink($file);
    rmdir($directory);
}
echo 'PASS: ' . $checks . ' CAPTCHA checks (bitmap/TTF/random words, image dimensions and expiry); E_ALL, OCI disabled.' . PHP_EOL;
