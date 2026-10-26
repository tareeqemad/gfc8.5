<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * شاشة إدارة طبقة حماية شاشات الرواتب (whitelist وسيط).
 *
 * • الـ actions بادئتها "public_" → تتجاوز فحص الصلاحية العام في MY_Controller
 *   (عبر _exclude_pages) — فلا نحتاج لمسة على نظام الصلاحيات.
 * • الأمان الحقيقي: is_manager في الكونستركتر — فقط المدراء (1710/109...) يمرّون.
 *
 * الرابط: base_url('payroll_data/payroll_access/public_manage')
 */
class Payroll_access extends MY_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('payroll_access_model');

        $uid = (isset($this->user) && isset($this->user->id)) ? (int)$this->user->id : 0;
        if (!$this->payroll_access_model->is_manager($uid)) {
            http_response_code(403);
            echo '<div style="font-family:Tahoma;direction:rtl;text-align:center;padding:60px;color:#b91c1c">'
               . '<h3>غير مصرّح</h3><p>هذه الشاشة مخصّصة لمدراء صلاحية الاطلاع على الرواتب فقط.</p></div>';
            exit;
        }
    }

    // ===== شاشة الإدارة =====
    function public_manage()
    {
        $cands = $this->payroll_access_model->list_candidates();
        if (!is_array($cands)) $cands = array();

        // أسماء المستخدمين من نظام المستخدمين القائم (يعمل باسم المستخدم المتصل)
        $this->load->model('settings/users_model');
        $users = $this->users_model->get_all('');
        $umap = array();
        foreach ((array)$users as $u) {
            if (isset($u['ID'])) $umap[(int)$u['ID']] = $u;
        }
        foreach ($cands as $i => $c) {
            $id = (int)$c['USER_NO'];
            $cands[$i]['USER_NAME'] = isset($umap[$id]['USER_NAME']) ? $umap[$id]['USER_NAME'] : ('#' . $id);
            $cands[$i]['EMP_NO']    = isset($umap[$id]['EMP_NO'])    ? $umap[$id]['EMP_NO']    : '';
        }

        $data['rows']       = $cands;
        $data['toggle_url'] = base_url('payroll_data/payroll_access/public_toggle');
        $data['title']      = 'إدارة صلاحية الاطلاع على الرواتب';
        $data['content']    = 'payroll_access_index';
        $this->load->view('template/template1', $data);
    }

    // ===== منح/حجب (AJAX) =====
    function public_toggle()
    {
        $user_no = (int)$this->input->post('user_no');
        $action  = $this->input->post('action');
        $by = (isset($this->user) && isset($this->user->id)) ? (int)$this->user->id : null;

        if ($user_no <= 0) { echo 'مستخدم غير صالح'; return; }

        if ($action === 'grant') {
            echo $this->payroll_access_model->grant($user_no, $by);
        } else if ($action === 'revoke') {
            echo $this->payroll_access_model->revoke($user_no, $by);
        } else {
            echo 'إجراء غير معروف';
        }
    }
}
