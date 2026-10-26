<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * طبقة حماية شاشات الرواتب (whitelist وسيط).
 * يعتمد على GFC_PAK.PAYROLL_ACCESS_PKG فقط (معزول عن نظام الصلاحيات).
 */
class Payroll_access_model extends MY_Model
{
    var $PKG_NAME = "GFC_PAK.PAYROLL_ACCESS_PKG";

    function __construct()
    {
        parent::__construct();
        $this->load->model('Root/New_rmodel');
    }

    /** هل المستخدم مسموح يشوف شاشات الرواتب؟ (الحارس) */
    function is_allowed($user_no)
    {
        $params = array(
            array('name' => ':P_USER_NO', 'value' => (int)$user_no, 'type' => '',       'length' => -1),
            array('name' => ':P_MSG_OUT', 'value' => 'MSG',         'type' => SQLT_CHR, 'length' => 10),
        );
        $r = $this->conn->excuteProcedures($this->PKG_NAME, 'CHECK_ALLOWED', $params);
        return isset($r['MSG']) && trim($r['MSG']) === '1';
    }

    /** هل المستخدم مدير القائمة؟ */
    function is_manager($user_no)
    {
        $params = array(
            array('name' => ':P_USER_NO', 'value' => (int)$user_no, 'type' => '',       'length' => -1),
            array('name' => ':P_MSG_OUT', 'value' => 'MSG',         'type' => SQLT_CHR, 'length' => 10),
        );
        $r = $this->conn->excuteProcedures($this->PKG_NAME, 'CHECK_MANAGER', $params);
        return isset($r['MSG']) && trim($r['MSG']) === '1';
    }

    /** قائمة المرشّحين: USER_NO + HAS_PERM + IN_WHITELIST + IS_MANAGER + NOTE */
    function list_candidates()
    {
        $params = array(
            array('name' => ':P_REF_CUR_OUT', 'value' => 'cursor', 'type' => OCI_B_CURSOR),
            array('name' => ':P_MSG_OUT',     'value' => 'MSG',    'type' => SQLT_CHR, 'length' => 500),
        );
        return $this->New_rmodel->general_get($this->PKG_NAME, 'LIST_CANDIDATES', $params);
    }

    /** منح (إضافة/إعادة تفعيل) */
    function grant($user_no, $by)
    {
        $params = array(
            array('name' => ':P_USER_NO', 'value' => (int)$user_no, 'type' => '',       'length' => -1),
            array('name' => ':P_BY',      'value' => $by,           'type' => '',       'length' => -1),
            array('name' => ':P_MSG_OUT', 'value' => 'MSG',         'type' => SQLT_CHR, 'length' => 500),
        );
        $r = $this->conn->excuteProcedures($this->PKG_NAME, 'GRANT_ACCESS', $params);
        return isset($r['MSG']) ? $r['MSG'] : '0';
    }

    /** حجب (STATUS=0) — لا يحجب مدير */
    function revoke($user_no, $by)
    {
        $params = array(
            array('name' => ':P_USER_NO', 'value' => (int)$user_no, 'type' => '',       'length' => -1),
            array('name' => ':P_BY',      'value' => $by,           'type' => '',       'length' => -1),
            array('name' => ':P_MSG_OUT', 'value' => 'MSG',         'type' => SQLT_CHR, 'length' => 500),
        );
        $r = $this->conn->excuteProcedures($this->PKG_NAME, 'REVOKE_ACCESS', $params);
        return isset($r['MSG']) ? $r['MSG'] : '0';
    }
}
