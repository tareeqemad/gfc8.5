<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
$login_preview = !empty($login_preview);
$reset_mode = isset($reset) && (int) $reset === 1;
$supported_browser = isset($browser) && $browser === 'true';
$escape = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$login_action = $login_preview ? '#' : base_url('login/check_user');
$reset_action = $login_preview ? '#' : base_url('login/reset');
$icon = static function ($name) {
    $paths = array(
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
        'lock' => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V6a4 4 0 0 1 8 0v4m-4 5v2"/>',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'arrow' => '<path d="M19 12H5m6-6-6 6 6 6"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'shield' => '<path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6l8-3Z"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 11h18m-14 4h2m4 0h2"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>',
        'play' => '<circle cx="12" cy="12" r="9"/><path d="m10 8 6 4-6 4V8Z"/>',
        'chevron' => '<path d="m8 10 4 4 4-4"/>',
        'bolt' => '<path d="m13 2-9 12h7l-1 8 10-13h-7l1-7Z"/>',
        'book' => '<path d="M12 5v16M3 3c4 0 6 0 9 2 3-2 5-2 9-2v16c-4 0-6 0-9 2-3-2-5-2-9-2V3Z"/>',
    );
    return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'.$paths[$name].'</svg>';
};
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#10263d">
    <title><?= $escape(isset($title) ? $title : 'تسجيل الدخول | النظام الموحد'); ?></title>
    <link rel="icon" href="<?= base_url() ?>assets/img/gedcologo.png" type="image/png">
    <link rel="stylesheet" href="<?= base_url() ?>assets/css/login-portal.css?v=20261022-5">
</head>
<body class="gedco-login" data-preview="<?= $login_preview ? 'true' : 'false'; ?>">
<div class="portal-shell">
    <main class="portal-main">
        <header class="portal-header">
            <div class="portal-brand">
                <div class="brand-mark"><img src="<?= base_url() ?>assets/images/bi-imgs/logo-w-1.png" alt="شعار كهرباء غزة" width="64" height="68"></div>
                <div><div class="brand-name">شركة توزيع كهرباء غزة</div><h1>النظام الموحد</h1></div>
            </div>
        </header>
        <section class="access-card" aria-labelledby="access-title">
            <?php if ($supported_browser): ?>
            <h2 id="access-title"><?= $reset_mode ? 'استعادة كلمة المرور' : 'تسجيل الدخول'; ?></h2>
            <p class="access-description" id="access-description"<?= !$reset_mode ? ' hidden' : ''; ?>><?= $reset_mode ? 'أدخل بريدك الإلكتروني المسجّل لاستلام كلمة مرور جديدة.' : ''; ?></p>
            <?php if (isset($msg) && $msg !== ''): ?><div class="form-message" role="alert"><?= $msg; ?></div><?php endif; ?>
            <div class="form-message preview-message" id="preview-message" role="status" hidden></div>
            <form id="loginForm" class="access-form" method="post" action="<?= $escape($reset_mode ? $reset_action : $login_action); ?>" data-login-action="<?= $escape($login_action); ?>" data-reset-action="<?= $escape($reset_action); ?>" data-year-url="<?= $login_preview ? '' : $escape(base_url('login/index')); ?>" data-reset="<?= $reset_mode ? '1' : '0'; ?>">
                <fieldset id="login" class="login-fields"<?= $reset_mode ? ' hidden disabled' : ''; ?>>
                    <legend class="sr-only">بيانات تسجيل الدخول</legend>
                    <div class="field-group">
                        <label for="txt_user_id">اسم المستخدم</label>
                        <div class="input-wrap"><?= $icon('user'); ?><input type="text" name="user_id" id="txt_user_id" placeholder="أدخل اسم المستخدم" maxlength="15" autocomplete="username" autocapitalize="none" spellcheck="false" required data-val="true" data-val-required="يرجى إدخال اسم المستخدم" aria-describedby="user-error"></div>
                        <span id="user-error" class="field-error" data-valmsg-for="user_id" data-valmsg-replace="true"></span>
                    </div>
                    <div class="field-group">
                        <div class="field-label-row"><label for="txt_user_pwd">كلمة المرور</label><button type="button" id="show-reset" class="recovery-link" aria-controls="reset">نسيت كلمة المرور؟</button></div>
                        <div class="input-wrap password-wrap"><?= $icon('lock'); ?><input type="password" name="user_pwd" id="txt_user_pwd" placeholder="أدخل كلمة المرور" maxlength="32" autocomplete="current-password" required data-val="true" data-val-required="يرجى إدخال كلمة المرور" aria-describedby="password-error"><button type="button" id="toggle-password" class="password-toggle" aria-label="إظهار كلمة المرور" aria-pressed="false" aria-controls="txt_user_pwd"><?= $icon('eye'); ?></button></div>
                        <span id="password-error" class="field-error" data-valmsg-for="user_pwd" data-valmsg-replace="true"></span>
                        <p class="caps-hint" id="caps-hint" role="status" hidden>مفتاح الحروف الكبيرة Caps Lock مفعّل.</p>
                    </div>
                    <div class="field-group year-field"><label for="db_year">سنة العمل</label><div class="input-wrap select-wrap"><?= $icon('calendar'); ?><select name="db_year" id="db_year"><option value="0">السنة الحالية</option></select><?= $icon('chevron'); ?></div></div>
                    <button type="submit" class="primary-button"><span>تسجيل الدخول</span><?= $icon('arrow'); ?></button>
                </fieldset>
                <fieldset id="reset" class="login-fields"<?= !$reset_mode ? ' hidden disabled' : ''; ?>>
                    <legend class="sr-only">استعادة كلمة المرور</legend>
                    <div class="field-group"><label for="txt_email">البريد الإلكتروني</label><div class="input-wrap"><?= $icon('mail'); ?><input type="email" name="email" id="txt_email" placeholder="name@gedco.ps" maxlength="50" autocomplete="email" inputmode="email" dir="ltr" required data-val="true" data-val-required="يرجى إدخال البريد الإلكتروني" aria-describedby="email-error access-description"></div><span id="email-error" class="field-error" data-valmsg-for="email" data-valmsg-replace="true"></span></div>
                    <button type="submit" class="primary-button"><span>إرسال الطلب</span><?= $icon('arrow'); ?></button>
                    <button type="button" id="back-login" class="back-button"><?= $icon('arrow'); ?> العودة إلى تسجيل الدخول</button>
                </fieldset>
                <?= AntiForgeryToken(); ?>
                <input type="hidden" name="action" value="do_login">
            </form>
            <details class="tutorial-panel" id="tutorial-panel">
                <summary><span><?= $icon('book'); ?> شروحات الاستخدام</span><?= $icon('chevron'); ?></summary>
                <div class="tutorial-content"><label for="vid_name">الشروحات المرئية</label><div class="tutorial-row"><select id="vid_name">
                    <option value="0">اختر الشرح المناسب</option>
                    <optgroup label="تسجيل الدخول"><option value="01_login">تسجيل الدخول وتغيير كلمة المرور</option></optgroup>
                    <optgroup label="برنامج المساهمات"><option value="02_contrib_branches">المساهمات - مقرات</option><option value="03_contrib_gaza">المساهمات - غزة</option><option value="04_contrib_tech">المساهمات - الفنية</option></optgroup>
                    <optgroup label="القبض المركزي"><option value="05_central_receipts_teller">القبض المركزي - الجزء الخاص بالمحصّل</option></optgroup>
                </select><button type="button" id="show-tutorial" class="tutorial-button" data-tutorial-url="<?= $escape(base_url().'tutorial/tutorial_show.php'); ?>" disabled><?= $icon('play'); ?> عرض</button></div></div>
            </details>
            <?php else: ?>
            <h2 id="access-title"><?= isset($browser) && $browser === 'version' ? 'يرجى تحديث المتصفح' : 'المتصفح غير مدعوم'; ?></h2>
            <p class="access-description">للوصول إلى النظام، افتح الصفحة باستخدام إصدار حديث من أحد المتصفحات التالية.</p>
            <div class="browser-links"><a href="https://www.google.com/chrome/" target="_blank" rel="noopener noreferrer">Google Chrome <?= $icon('arrow'); ?></a><a href="https://www.mozilla.org/ar/firefox/new/" target="_blank" rel="noopener noreferrer">Mozilla Firefox <?= $icon('arrow'); ?></a></div>
            <?php endif; ?>
        </section>
    </main>
    <footer class="portal-footer"><span>شركة توزيع كهرباء غزة © <?= date('Y'); ?></span><?php if ($login_preview): ?><span>معاينة التصميم · دون اتصال بقاعدة البيانات</span><?php endif; ?></footer>
</div>
<script src="<?= base_url() ?>assets/js/jquery-1.11.1.min.js"></script>
<script src="<?= base_url() ?>assets/js/jqueryval.min.js"></script>
<?= put_headers('js'); ?>
<script src="<?= base_url() ?>assets/js/login-portal.js?v=20261022-3" defer></script>
</body>
</html>
