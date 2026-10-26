<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * حارس طبقة حماية شاشات الرواتب (whitelist وسيط).
 * يُستدعى في أول كونستركتر كنترولرات الرواتب (payment_req / payment_accounts).
 * لو المستخدم غير مسموح → صفحة "الشاشة تحت الصيانة" + exit (قبل أي تحميل بيانات).
 *
 * مستقل تماماً عن نظام الصلاحيات القائم (USER_MENUS_TB) — لا يلمسه.
 */
if (!function_exists('require_payroll_access')) {
    function require_payroll_access()
    {
        // HMVC keeps the authenticated user on MY_Controller, not the shared CI super-object.
        $CI = get_my_instance();
        $user_id = (isset($CI->user) && isset($CI->user->id)) ? (int)$CI->user->id : 0;

        $CI->load->model('payroll_data/payroll_access_model');

        // مسموح → كمّل عادي
        if ($CI->payroll_access_model->is_allowed($user_id)) {
            return;
        }

        // غير مسموح → احجب
        payroll_access_block();
    }
}

if (!function_exists('payroll_access_block')) {
    function payroll_access_block()
    {
        $CI = get_instance();

        // طلب AJAX → JSON (عشان ما تتكسر الواجهة)
        if (isset($CI->input) && $CI->input->is_ajax_request()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array(
                'ok'          => false,
                'maintenance' => true,
                'msg'         => 'الشاشة تحت الصيانة حالياً، يرجى المحاولة لاحقاً.'
            ), JSON_UNESCAPED_UNICODE);
            exit;
        }

        // طلب عادي → صفحة صيانة مموّهة
        http_response_code(503);
        echo '<!DOCTYPE html><html dir="rtl" lang="ar"><head><meta charset="utf-8">'
           . '<meta name="viewport" content="width=device-width, initial-scale=1">'
           . '<title>تحت الصيانة</title>'
           . '<style>'
           . 'body{margin:0;font-family:"Segoe UI",Tahoma,Arial,sans-serif;background:#0f172a;'
           . 'color:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh}'
           . '.card{text-align:center;max-width:480px;padding:48px 32px}'
           . '.ic{font-size:64px;line-height:1;margin-bottom:20px}'
           . 'h1{font-size:22px;margin:0 0 12px;color:#f1f5f9}'
           . 'p{font-size:15px;color:#94a3b8;line-height:1.9;margin:0}'
           . '.bar{margin-top:28px;height:6px;border-radius:6px;background:#1e293b;overflow:hidden}'
           . '.bar i{display:block;height:100%;width:40%;border-radius:6px;'
           . 'background:linear-gradient(90deg,#3b82f6,#06b6d4);animation:mv 1.6s ease-in-out infinite}'
           . '@keyframes mv{0%{margin-right:0}50%{margin-right:60%}100%{margin-right:0}}'
           . '</style></head><body><div class="card">'
           . '<div class="ic">🛠️</div>'
           . '<h1>الشاشة تحت الصيانة حالياً</h1>'
           . '<p>نعتذر عن الإزعاج، يتم حالياً تحديث هذه الشاشة.<br>يرجى المحاولة لاحقاً أو مراجعة مسؤول النظام.</p>'
           . '<div class="bar"><i></i></div>'
           . '</div></body></html>';
        exit;
    }
}
