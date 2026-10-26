(function () {
    'use strict';
    var form = document.getElementById('loginForm');
    if (!form) return;
    var preview = document.body.getAttribute('data-preview') === 'true';
    var login = document.getElementById('login');
    var reset = document.getElementById('reset');
    var password = document.getElementById('txt_user_pwd');
    var toggle = document.getElementById('toggle-password');
    var previewMessage = document.getElementById('preview-message');
    function changeForm(isReset, moveFocus) {
        login.hidden = isReset;
        login.disabled = isReset;
        reset.hidden = !isReset;
        reset.disabled = !isReset;
        form.action = form.getAttribute(isReset ? 'data-reset-action' : 'data-login-action');
        document.getElementById('access-title').textContent = isReset ? 'استعادة كلمة المرور' : 'تسجيل الدخول';
        var description = document.getElementById('access-description');
        description.textContent = isReset ? 'أدخل بريدك الإلكتروني المسجّل لاستلام كلمة مرور جديدة.' : '';
        description.hidden = !isReset;
        previewMessage.hidden = true;
        if (moveFocus) document.getElementById(isReset ? 'txt_email' : 'txt_user_id').focus();
    }
    document.getElementById('show-reset').addEventListener('click', function () { changeForm(true, true); });
    document.getElementById('back-login').addEventListener('click', function () { changeForm(false, true); });
    changeForm(form.getAttribute('data-reset') === '1', false);
    toggle.addEventListener('click', function () {
        var visible = password.type === 'password';
        password.type = visible ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', visible ? 'true' : 'false');
        toggle.setAttribute('aria-label', visible ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
        password.focus();
    });
    function capsLock(event) { document.getElementById('caps-hint').hidden = !(event.getModifierState && event.getModifierState('CapsLock')); }
    password.addEventListener('keyup', capsLock);
    password.addEventListener('keydown', capsLock);
    password.addEventListener('blur', function () { document.getElementById('caps-hint').hidden = true; });
    Array.prototype.forEach.call(form.querySelectorAll('input[required]'), function (input) {
        input.addEventListener('invalid', function () {
            input.setCustomValidity(input.validity.valueMissing ? input.getAttribute('data-val-required') : (input.validity.typeMismatch ? 'يرجى إدخال بريد إلكتروني صحيح' : ''));
        });
        input.addEventListener('input', function () { input.setCustomValidity(''); });
    });
    form.addEventListener('submit', function (event) {
        if (preview) {
            event.preventDefault();
            previewMessage.textContent = 'هذه معاينة للتصميم فقط. لم يتم إرسال بيانات أو تنفيذ تسجيل دخول أو استرجاع كلمة مرور.';
            previewMessage.hidden = false;
        }
    });
    // Keep the existing year-selection request on the real login page only.
    function changeYear() {
        var url = form.getAttribute('data-year-url');
        if (!preview && url && window.jQuery) window.jQuery.post(url, { db_year: document.getElementById('db_year').value });
    }
    document.getElementById('db_year').addEventListener('change', changeYear);
    changeYear();
    var videos = document.getElementById('vid_name');
    var showVideo = document.getElementById('show-tutorial');
    videos.addEventListener('change', function () { showVideo.disabled = videos.value === '0'; });
    showVideo.addEventListener('click', function () {
        if (videos.value === '0') return;
        window.open(showVideo.getAttribute('data-tutorial-url') + '?vm=' + encodeURIComponent(videos.value), '_blank', 'noopener');
    });
}());
