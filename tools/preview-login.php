<?php
/** Render the real login view locally without bootstrapping CodeIgniter or Oracle. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('BASEPATH', dirname(__DIR__) . '/system/');

function base_url($path = '')
{
    return '/gfc/' . ltrim($path, '/');
}

function AntiForgeryToken()
{
    $GLOBALS['gfc_login_preview_token_calls'] = ($GLOBALS['gfc_login_preview_token_calls'] ?? 0) + 1;
    return '<input type="hidden" name="__AntiForgeryToken" value="offline-preview-token">';
}

function put_headers($type)
{
    return '';
}

function gfc_render_login_view(array $state = [], $preview = true)
{
    $title = $state['title'] ?? 'دخول المستخدمين | شركة توزيع كهرباء غزة';
    $browser = $state['browser'] ?? 'true';
    $reset = $state['reset'] ?? 0;
    $msg = $state['msg'] ?? null;
    $login_preview = (bool) $preview;

    ob_start();
    try {
        require dirname(__DIR__) . '/application/views/login_index.php';
        return ob_get_clean();
    } catch (Throwable $error) {
        ob_end_clean();
        throw $error;
    }
}

// Requiring this file from the render test only defines the isolated helpers.
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $directory = dirname(__DIR__) . '/temp/login-preview';
    if (!is_dir($directory) && !mkdir($directory, 0777, true)) {
        throw new RuntimeException('Cannot create the local login preview directory.');
    }
    $states = [
        'index.html' => [],
        'reset.html' => ['reset' => 1],
        'error.html' => ['msg' => 'اسم المستخدم أو كلمة المرور غير صحيحة. يرجى المحاولة مرة أخرى.'],
        'browser.html' => ['browser' => 'browser'],
    ];
    foreach ($states as $name => $state) {
        if (file_put_contents($directory . '/' . $name, gfc_render_login_view($state)) === false) {
            throw new RuntimeException('Cannot write login preview: ' . $name);
        }
    }
    if (file_put_contents($directory . '/.htaccess', "Require local\n") === false) {
        throw new RuntimeException('Cannot restrict the preview to local requests.');
    }
    echo "Local preview: http://127.0.0.1:8084/gfc/temp/login-preview/index.html\n";
    echo "Variants: reset.html, error.html, browser.html. No database or VPN connection was made.\n";
}
