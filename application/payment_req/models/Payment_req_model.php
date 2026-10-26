<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Payment_req_model extends MY_Model
{
    var $PKG_NAME         = "GFC_PAK.DISBURSEMENT_PKG";
    var $BATCH_PKG_NAME   = "GFC_PAK.DISBURSEMENT_BATCH_PKG";
    var $COMPARE_PKG_NAME = "GFC_PAK.BATCH_COMPARE_PKG";
    var $LIAB_PKG_NAME    = "GFC_PAK.DUES_LIABILITY_PKG";
    var $TABLE_NAME       = "PAYMENT_REQ_TB";

    function __construct()
    {
        parent::__construct();
        $this->load->model('Root/New_rmodel');
    }

    // =========================================================
    // MASTER — GET single
    // =========================================================
    function get($req_id = 0)
    {
        $params = array(
            array('name' => ':P_REQ_ID',    'value' => $req_id,   'type' => SQLT_INT, 'length' => -1),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',  'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT', 'type' => SQLT_CHR, 'length' => -1),
        );
        return $this->New_rmodel->general_get($this->PKG_NAME, 'PAYMENT_REQ_GET', $params);
    }

    // =========================================================
    // MASTER — CREATE (returns REQ_ID or error)
    // =========================================================
    function create($data, $include_allowances = 0, $type2_remaining_mode = 0)
    {
        $params = array();
        $this->_extract_data($params, $data);
        foreach ($params as &$param) {
            // PHP truncates float array keys; DBConn uses each value as a key.
            if (is_float($param['value'])) $param['value'] = (string)$param['value'];
        }
        unset($param);
        // Append after MSG_OUT to preserve the original Oracle argument positions.
        // DBConn shares bind storage for equal values. Match the extracted
        // CHAR inputs so calc_method=1 and these flags cannot alias INT/CHAR.
        $params[] = array('name' => ':P_INCLUDE_ALLOWANCES', 'value' => (int)$include_allowances, 'type' => '', 'length' => -1);
        $params[] = array('name' => ':P_TYPE2_REMAINING_MODE', 'value' => (int)$type2_remaining_mode === 1 ? 1 : 0, 'type' => '', 'length' => -1);
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'PAYMENT_REQ_INSERT', $params);
        return $result['MSG_OUT'];
    }

    // =========================================================
    // MASTER — UPDATE
    // =========================================================
    function edit($data)
    {
        $params = array();
        $this->_extract_data($params, $data);
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'PAYMENT_REQ_UPDATE', $params);
        return $result['MSG_OUT'];
    }

    // =========================================================
    // MASTER — APPROVE
    // =========================================================
    function approve($req_id)
    {
        $params = array(
            array('name' => ':P_REQ_ID',  'value' => $req_id,   'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MSG_OUT', 'value' => 'MSG_OUT', 'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'PAYMENT_REQ_APPROVE', $params);
        return $result['MSG_OUT'];
    }

    // =========================================================
    // MASTER — PAY
    // =========================================================
    function pay($req_id)
    {
        $params = array(
            array('name' => ':P_REQ_ID',  'value' => $req_id,   'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MSG_OUT', 'value' => 'MSG_OUT', 'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'PAYMENT_REQ_PAY', $params);
        return $result['MSG_OUT'];
    }

    // =========================================================
    // MASTER — CANCEL
    // =========================================================
    function cancel($req_id, $cancel_note = 'Cancelled')
    {
        $params = array(
            array('name' => ':P_REQ_ID',      'value' => $req_id,      'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_CANCEL_NOTE', 'value' => $cancel_note, 'type' => SQLT_CHR, 'length' => 200),
            array('name' => ':P_MSG_OUT',     'value' => 'MSG_OUT',    'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'PAYMENT_REQ_CANCEL', $params);
        return $result['MSG_OUT'];
    }

    // =========================================================
    // MASTER — DELETE (حذف فعلي — مسودة ومعتمد فقط)
    // =========================================================
    function delete_request($req_id)
    {
        if ($this->conn->user != 'GFC_PAK') {
            $this->conn = new DBConn();
        }
        $params = array(
            array('name' => ':P_REQ_ID',  'value' => $req_id,   'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MSG_OUT', 'value' => 'MSG_OUT',  'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'PAYMENT_REQ_DELETE', $params);
        return $result['MSG_OUT'];
    }

    // =========================================================
    // MASTER — LIST (paginated)
    // =========================================================
    function get_list($where_sql, $offset, $page_size)
    {
        $params = array(
            array('name' => ':P_INSQL',     'value' => $where_sql, 'type' => SQLT_CHR, 'length' => 32767),
            array('name' => ':P_OFFSET',    'value' => $offset,    'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_PAGE_SIZE', 'value' => $page_size, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',   'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',  'type' => SQLT_CHR, 'length' => -1),
        );
        return $this->New_rmodel->general_get($this->PKG_NAME, 'PAYMENT_REQ_LIST', $params);
    }

    function get_list_master($where_sql, $offset, $page_size)
    {
        $params = array(
            array('name' => ':P_INSQL',     'value' => $where_sql, 'type' => SQLT_CHR, 'length' => 32767),
            array('name' => ':P_OFFSET',    'value' => $offset,    'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_PAGE_SIZE', 'value' => $page_size, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',   'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',  'type' => SQLT_CHR, 'length' => -1),
        );
        return $this->New_rmodel->general_get($this->PKG_NAME, 'PAYMENT_REQ_LIST_MASTER', $params);
    }

    // =========================================================
    // DETAIL — ADD employee to request
    // =========================================================
    // $allow_other_req_dup: لو true → نسمح بالموظف حتى لو موجود في طلب آخر لنفس الشهر+النوع
    //                       (يُستخدم في الاستيراد بعد ما المحاسب يشوف التكرار في المعاينة)
    function detail_add($req_id, $emp_no, $req_amount, $note = '', $allow_other_req_dup = false)
    {
        $params = array(
            // ⚠️ النوع '' (مش SQLT_INT) مقصود: DBConn يخزّن الـ bind بمفتاح = القيمة،
            // فلو REQ_ID == REQ_AMOUNT (مثلاً 18) يتصادموا على نفس الـ zval. مع SQLT_INT+SQLT_CHR
            // يتهرّس الـ integer → الـ proc تستلم garbage → "الطلب غير موجود". توحيد النوع لـ ''
            // (string-inferred) يخلّي التصادم غير ضار. (شوف feedback_php_oracle_gotchas نقطة ١)
            array('name' => ':P_REQ_ID',              'value' => (int)$req_id,                     'type' => '',       'length' => -1),
            array('name' => ':P_EMP_NO',              'value' => (int)$emp_no,                     'type' => '',       'length' => -1),
            array('name' => ':P_REQ_AMOUNT',          'value' => (float)$req_amount,               'type' => SQLT_CHR, 'length' => -1),
            array('name' => ':P_NOTE',                'value' => $note ?: null,                    'type' => SQLT_CHR, 'length' => 500),
            array('name' => ':P_MSG_OUT',             'value' => 'MSG_OUT',                        'type' => SQLT_CHR, 'length' => 4000),
            array('name' => ':P_ALLOW_OTHER_REQ_DUP', 'value' => $allow_other_req_dup ? 1 : 0,     'type' => '',       'length' => -1),
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'DETAIL_INSERT', $params);
        // 🔧 TEMP DIAG — احذفه بعد التشخيص
        log_message('error', '[DIAG detail_add] req_id=' . var_export($req_id, true)
            . ' emp_no=' . var_export($emp_no, true)
            . ' amount=' . var_export($req_amount, true)
            . ' allow=' . ($allow_other_req_dup ? 1 : 0)
            . ' | MSG_OUT=' . var_export($result['MSG_OUT'] ?? '(missing)', true)
            . ' | FULL=' . substr(json_encode($result, JSON_UNESCAPED_UNICODE), 0, 800));
        return $result['MSG_OUT'];
    }

    // =========================================================
    // 🆕 IMPORT LINE — ADD سطر أصلي من الإكسل بعد دمجه في detail
    // =========================================================
    function import_line_add($detail_id, $line_no, $excel_row, $amount, $note, $attachment_id, $entry_user)
    {
        $params = array(
            // ⚠️ النوع '' للـ INT الإدخالية لتفادي تصادم القيم مع P_AMOUNT (CHR) في DBConn
            array('name' => ':P_DETAIL_ID',     'value' => (int)$detail_id,                'type' => '',       'length' => -1),
            array('name' => ':P_LINE_NO',       'value' => (int)$line_no,                  'type' => '',       'length' => -1),
            array('name' => ':P_EXCEL_ROW_NUM', 'value' => $excel_row !== null ? (int)$excel_row : null, 'type' => '',       'length' => -1),
            array('name' => ':P_AMOUNT',        'value' => (float)$amount,                 'type' => SQLT_CHR, 'length' => -1),
            array('name' => ':P_ORIGINAL_NOTE', 'value' => $note !== '' ? $note : null,    'type' => SQLT_CHR, 'length' => 500),
            array('name' => ':P_ATTACHMENT_ID', 'value' => $attachment_id ?: null,         'type' => '',       'length' => -1),
            array('name' => ':P_ENTRY_USER',   'value' => $entry_user ?: null,             'type' => '',       'length' => -1),
            array('name' => ':P_NEW_ID_OUT',   'value' => 'NEW_ID',                        'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MSG_OUT',      'value' => 'MSG',                           'type' => SQLT_CHR, 'length' => 500),
        );
        // الـ procedure standalone (مش جزء من PKG)
        $result = $this->conn->excuteProcedures(null, 'PAYMENT_REQ_IMP_LINE_ADD', $params);
        return [
            'new_id' => isset($result['NEW_ID']) ? (int)$result['NEW_ID'] : 0,
            'msg'    => $result['MSG'] ?? '',
        ];
    }

    // =========================================================
    // 🆕 IMPORT LINE — ADD بإيجاد DETAIL_ID داخلياً من (REQ_ID + EMP_NO)
    //     يستخدمها الـ wizard لما يحفظ السطور بعد إنشاء الطلب
    // =========================================================
    function import_line_add_by_emp($req_id, $emp_no, $line_no, $excel_row, $amount, $note, $attachment_id, $entry_user)
    {
        $params = array(
            // ⚠️ النوع '' للـ INT الإدخالية لتفادي تصادم القيم مع P_AMOUNT (CHR) في DBConn
            array('name' => ':P_REQ_ID',        'value' => (int)$req_id,                                 'type' => '',       'length' => -1),
            array('name' => ':P_EMP_NO',        'value' => (int)$emp_no,                                 'type' => '',       'length' => -1),
            array('name' => ':P_LINE_NO',       'value' => (int)$line_no,                                'type' => '',       'length' => -1),
            array('name' => ':P_EXCEL_ROW_NUM', 'value' => $excel_row !== null ? (int)$excel_row : null, 'type' => '',       'length' => -1),
            array('name' => ':P_AMOUNT',        'value' => (float)$amount,                               'type' => SQLT_CHR, 'length' => -1),
            array('name' => ':P_ORIGINAL_NOTE', 'value' => $note !== '' ? $note : null,                  'type' => SQLT_CHR, 'length' => 500),
            array('name' => ':P_ATTACHMENT_ID', 'value' => $attachment_id ?: null,                       'type' => '',       'length' => -1),
            array('name' => ':P_ENTRY_USER',    'value' => $entry_user ?: null,                          'type' => '',       'length' => -1),
            array('name' => ':P_NEW_ID_OUT',    'value' => 'NEW_ID',                                     'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MSG_OUT',       'value' => 'MSG',                                        'type' => SQLT_CHR, 'length' => 500),
        );
        $result = $this->conn->excuteProcedures(null, 'PAYMENT_REQ_IMP_LN_ADD_BY_EMP', $params);
        return [
            'new_id' => isset($result['NEW_ID']) ? (int)$result['NEW_ID'] : 0,
            'msg'    => $result['MSG'] ?? '',
        ];
    }

    // =========================================================
    // 🆕 IMPORT LINES — GET بنود الموظف الأصلية
    // =========================================================
    function import_lines_get($detail_id)
    {
        $params = array(
            array('name' => ':P_DETAIL_ID',  'value' => (int)$detail_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',         'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',        'type' => SQLT_CHR, 'length' => -1),
        );
        return $this->New_rmodel->general_get(null, 'PAYMENT_REQ_IMP_LINES_GET', $params);
    }


    // =========================================================
    // DETAIL — UPDATE employee row
    // =========================================================
    function detail_update($detail_id, $req_amount, $note = '')
    {
        $params = array(
            array('name' => ':P_DETAIL_ID',  'value' => (int)$detail_id,    'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_REQ_AMOUNT', 'value' => (float)$req_amount, 'type' => SQLT_CHR, 'length' => -1),
            array('name' => ':P_NOTE',       'value' => $note ?: null,      'type' => SQLT_CHR, 'length' => 500),
            array('name' => ':P_MSG_OUT',    'value' => 'MSG_OUT',          'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'DETAIL_UPDATE', $params);
        return $result['MSG_OUT'];
    }

    // =========================================================
    // DETAIL — DELETE employee from request
    // =========================================================
    function detail_delete($detail_id)
    {
        $params = array(
            array('name' => ':P_DETAIL_ID', 'value' => (int)$detail_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MSG_OUT',   'value' => 'MSG_OUT',       'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'DETAIL_DELETE', $params);
        return $result['MSG_OUT'];
    }

    // =========================================================
    // DETAIL — SET OVERRIDE distribution
    //   $provider_type: NULL=افتراضي, 1=بنك فقط, 2=محفظة فقط
    //   $acc_id: حساب محدد (يطغى على PROVIDER_TYPE)
    // =========================================================
    function detail_set_override($detail_id, $provider_type = null, $acc_id = null)
    {
        // نمرّر القيم كـ string — Oracle يحوّل '' إلى NULL تلقائياً
        // ونتجنّب مشكلة SQLT_INT اللي يحوّل NULL إلى 0
        $pt_val  = ($provider_type === '' || $provider_type === null) ? '' : (string)(int)$provider_type;
        $acc_val = ($acc_id === '' || $acc_id === null)               ? '' : (string)(int)$acc_id;

        $params = array(
            array('name' => ':P_DETAIL_ID',     'value' => (int)$detail_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_PROVIDER_TYPE', 'value' => $pt_val,         'type' => SQLT_CHR, 'length' => 20),
            array('name' => ':P_ACC_ID',        'value' => $acc_val,        'type' => SQLT_CHR, 'length' => 20),
            array('name' => ':P_MSG_OUT',       'value' => 'MSG_OUT',       'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'DETAIL_SET_OVERRIDE', $params);
        return $result['MSG_OUT'];
    }

    // =========================================================
    // DETAIL — توزيع مخصّص على عدة حسابات بنِسَب (REQ_DETAIL_SPLIT_PKG)
    // =========================================================
    function detail_set_split($detail_id, $splits)
    {
        // $splits = سلسلة 'acc_id:pct,acc_id:pct'  مثال '101:75,102:25'
        $params = array(
            array('name' => ':P_DETAIL_ID', 'value' => (int)$detail_id,   'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_SPLITS',    'value' => (string)$splits,    'type' => SQLT_CHR, 'length' => 4000),
            array('name' => ':P_MSG_OUT',   'value' => 'MSG_OUT',          'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures('GFC_PAK.REQ_DETAIL_SPLIT_PKG', 'DETAIL_SET_SPLIT', $params);
        return $result['MSG_OUT'];
    }

    function detail_clear_split($detail_id)
    {
        $params = array(
            array('name' => ':P_DETAIL_ID', 'value' => (int)$detail_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MSG_OUT',   'value' => 'MSG_OUT',       'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures('GFC_PAK.REQ_DETAIL_SPLIT_PKG', 'DETAIL_CLEAR_SPLIT', $params);
        return $result['MSG_OUT'];
    }

    function req_get_splits($req_id)
    {
        $params = array(
            array('name' => ':P_REQ_ID',      'value' => (int)$req_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_REF_CUR_OUT', 'value' => 'cursor',     'type' => OCI_B_CURSOR),
            array('name' => ':P_MSG_OUT',     'value' => 'MSG_OUT',    'type' => SQLT_CHR, 'length' => 4000),
        );
        return $this->New_rmodel->general_get('GFC_PAK.REQ_DETAIL_SPLIT_PKG', 'REQ_GET_SPLITS', $params);
    }

    // =========================================================
    // DETAIL — APPROVE selected employees
    // =========================================================
    function detail_approve($detail_ids)
    {
        $params = array(
            array('name' => ':P_DETAIL_IDS', 'value' => $detail_ids, 'type' => SQLT_CHR, 'length' => 32767),
            array('name' => ':P_MSG_OUT',    'value' => 'MSG_OUT',   'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'DETAIL_APPROVE', $params);
        return $result['MSG_OUT'];
    }

    // =========================================================
    // DETAIL — PAY selected employees
    // =========================================================
    function detail_pay($detail_ids)
    {
        $params = array(
            array('name' => ':P_DETAIL_IDS', 'value' => $detail_ids, 'type' => SQLT_CHR, 'length' => 32767),
            array('name' => ':P_MSG_OUT',    'value' => 'MSG_OUT',   'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'DETAIL_PAY', $params);
        return $result['MSG_OUT'];
    }

    // =========================================================
    // DETAIL — LIST employees in a request
    // =========================================================
    function get_details($req_id)
    {
        $params = array(
            array('name' => ':P_REQ_ID',    'value' => (int)$req_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',     'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',    'type' => SQLT_CHR, 'length' => -1),
        );
        return $this->New_rmodel->general_get($this->PKG_NAME, 'DETAIL_LIST', $params);
    }

    function get_log($req_id)
    {
        $params = array(
            array('name' => ':P_REQ_ID',    'value' => (int)$req_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',     'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',    'type' => SQLT_CHR, 'length' => -1),
        );
        return $this->New_rmodel->general_get($this->PKG_NAME, 'PAYMENT_REQ_LOG_LIST', $params);
    }

    // =========================================================
    // SUMMARY
    // =========================================================
    function get_summary($emp_no, $the_month = null)
    {
        $params = array(
            array('name' => ':P_EMP_NO',    'value' => $emp_no,    'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_THE_MONTH', 'value' => $the_month, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',   'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',  'type' => SQLT_CHR, 'length' => -1),
        );
        return $this->New_rmodel->general_get($this->PKG_NAME, 'PAYMENT_REQ_SUMMARY_GET', $params);
    }

    // =========================================================
    // TOTALS
    // =========================================================
    function get_totals($where_sql)
    {
        $params = array(
            array('name' => ':P_INSQL',     'value' => $where_sql, 'type' => SQLT_CHR, 'length' => 32767),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',   'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',  'type' => SQLT_CHR, 'length' => -1),
        );
        try {
            $rows = $this->New_rmodel->general_get($this->PKG_NAME, 'PAYMENT_REQ_TOTALS', $params);
            $r = (is_array($rows) && count($rows) > 0) ? $rows[0] : [];
            return array(
                'ok'               => true,
                'count'            => (int)($r['CNT'] ?? 0),
                'amount'           => (float)($r['TOTAL_AMOUNT'] ?? 0),
                'active_amount'    => (float)($r['ACTIVE_AMOUNT'] ?? 0),
                'active_count'     => (int)($r['ACTIVE_COUNT'] ?? 0),
                'cancelled_amount' => (float)($r['CANCELLED_AMOUNT'] ?? 0),
                'cancelled_count'  => (int)($r['CANCELLED_COUNT'] ?? 0),
                't1_amount'  => (float)($r['T1_AMOUNT'] ?? 0), 't1_count'  => (int)($r['T1_COUNT'] ?? 0),
                't2_amount'  => (float)($r['T2_AMOUNT'] ?? 0), 't2_count'  => (int)($r['T2_COUNT'] ?? 0),
                't3_amount'  => (float)($r['T3_AMOUNT'] ?? 0), 't3_count'  => (int)($r['T3_COUNT'] ?? 0),
                's0_amount'  => (float)($r['S0_AMOUNT'] ?? 0), 's0_count'  => (int)($r['S0_COUNT'] ?? 0),
                's1_amount'  => (float)($r['S1_AMOUNT'] ?? 0), 's1_count'  => (int)($r['S1_COUNT'] ?? 0),
                's2_amount'  => (float)($r['S2_AMOUNT'] ?? 0), 's2_count'  => (int)($r['S2_COUNT'] ?? 0),
            );
        } catch (Exception $e) {
            return array('ok' => false, 'count' => 0, 'amount' => 0,
                         'active_amount' => 0, 'cancelled_amount' => 0,
                         'active_count' => 0, 'cancelled_count' => 0);
        }
    }

    // =========================================================
    // TOTALS BRANCH
    // =========================================================
    function get_totals_branch($where_sql)
    {
        $params = array(
            array('name' => ':P_INSQL',     'value' => $where_sql, 'type' => SQLT_CHR, 'length' => 32767),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',   'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',  'type' => SQLT_CHR, 'length' => -1),
        );
        try {
            $rows = $this->New_rmodel->general_get($this->PKG_NAME, 'PAYMENT_REQ_TOTALS_BRANCH', $params);
            return array('ok' => true, 'branches' => is_array($rows) ? $rows : []);
        } catch (Exception $e) {
            return array('ok' => false, 'branches' => []);
        }
    }

    // =========================================================
    // 🆕 EMP_STATUS_BY_MONTH_FOR_REQS
    // يرجع لكل (موظف، شهر) في الطلبات المحددة: حالة الموظف عبر GET_EMP_DISPLAY_STATUS
    // يُستخدم في معاينة الاحتساب لفلتر حسب الشهر
    // =========================================================
    function emp_status_by_month_for_reqs($req_ids_csv)
    {
        $params = array(
            array('name' => ':P_REQ_IDS',   'value' => (string)$req_ids_csv, 'type' => SQLT_CHR,    'length' => 32767),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',             'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',            'type' => SQLT_CHR,    'length' => -1),
        );
        try {
            $rows = $this->New_rmodel->general_get($this->PKG_NAME, 'EMP_STATUS_BY_MONTH_FOR_REQS', $params);
            return is_array($rows) ? $rows : array();
        } catch (Exception $e) {
            return array();
        }
    }

    // =========================================================
    // 🆕 IMPORT_FIND_DUP_EMPS — كشف الموظفين المكررين في طلبات أخرى
    // يُستخدم في import_preview قبل التنفيذ
    // =========================================================
    function import_find_dup_emps($emp_nos_csv, $the_month, $req_type, $req_id = null, $type2_remaining_mode = 0)
    {
        $params = array(
            array('name' => ':P_EMP_NOS',   'value' => (string)$emp_nos_csv,   'type' => SQLT_CHR,    'length' => 32767),
            array('name' => ':P_THE_MONTH', 'value' => (int)$the_month,        'type' => SQLT_INT,    'length' => -1),
            array('name' => ':P_REQ_TYPE',  'value' => (int)$req_type,         'type' => SQLT_INT,    'length' => -1),
            array('name' => ':P_REQ_ID',    'value' => $req_id ? (int)$req_id : null, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':REF_CUR_OUT', 'value' => 'CUR_RES',              'type' => 'cursor'),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',              'type' => SQLT_CHR,    'length' => -1),
            array('name' => ':P_TYPE2_REMAINING_MODE', 'value' => (int)$req_type === 2 && (int)$type2_remaining_mode === 1 ? 1 : 0, 'type' => SQLT_INT, 'length' => -1),
        );
        try {
            $result = $this->New_rmodel->general_get($this->PKG_NAME, 'IMPORT_FIND_DUP_EMPS', $params, 0);
            $rows = $result['CUR_RES'] ?? [];
            return is_array($rows) ? $rows : array();
        } catch (Exception $e) {
            return array();
        }
    }

    // =========================================================
    // 🆕 EMPS BY REQ_IDS — خريطة الموظفين لكل طلب
    // يُستخدم في شاشة احتساب الصرف لاحتساب:
    //   • الموظفين الفريدين عبر الطلبات المحددة
    //   • الموظفين المتكررين في أكثر من طلب
    // =========================================================
    function req_emps_by_ids($req_ids_csv)
    {
        $params = array(
            array('name' => ':P_REQ_IDS',   'value' => (string)$req_ids_csv, 'type' => SQLT_CHR,   'length' => 32767),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',             'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',            'type' => SQLT_CHR,   'length' => -1),
        );
        try {
            $rows = $this->New_rmodel->general_get($this->PKG_NAME, 'PAYMENT_REQ_EMPS_BY_IDS', $params);
            return is_array($rows) ? $rows : array();
        } catch (Exception $e) {
            return array();
        }
    }

    // =========================================================
    // 🆕 DIAGNOSE_EXCEL_EMPS — تشخيص موظفي Excel قبل الإضافة
    // =========================================================
    // يرجع لكل موظف STATUS + REASON_AR + التفاصيل:
    //   STATUS:
    //     • OK                    → جاهز للإضافة
    //     • WARNING_INACTIVE      → متقاعد — يحق له لكن نبّه المحاسب
    //     • ERROR_NOT_FOUND       → رقم موظف غير موجود
    //     • ERROR_DUPLICATE       → في طلب آخر نفس الشهر/النوع
    //     • ERROR_ALREADY_IN_REQ  → مضاف بالفعل في نفس الطلب
    //     • ERROR_NO_CALC         → غير محتسب (DATA.ADMIN)
    //     • ERROR_NO_CALC2        → غير محتسب (PAYMENT_REQ_ADMIN_CALC_TB) — نوع 2
    //     • ERROR_NO_323          → لا يوجد بند 323 — نوع 1/4
    //     • ERROR_NO_BALANCE      → رصيد المستحقات صفر — نوع 3
    //   ALLOWED: 1 = يسمح بالإضافة (OK + WARNING), 0 = ممنوع
    function diagnose_excel_emps($emp_nos_csv, $the_month, $req_type, $req_id = 0, $type2_remaining_mode = 0)
    {
        $params = array(
            array('name' => ':P_EMP_NOS',     'value' => (string)$emp_nos_csv,        'type' => SQLT_CHR,    'length' => 32767),
            array('name' => ':P_THE_MONTH',   'value' => (int)$the_month,             'type' => SQLT_INT,    'length' => -1),
            array('name' => ':P_REQ_TYPE',    'value' => (int)$req_type,              'type' => SQLT_INT,    'length' => -1),
            array('name' => ':P_REQ_ID',      'value' => (int)($req_id ?: 0),         'type' => SQLT_INT,    'length' => -1),
            array('name' => ':P_REF_CUR_OUT', 'value' => 'CUR_RES',                   'type' => 'cursor'),
            array('name' => ':P_MSG_OUT',    'value' => 'MSG_OUT',                   'type' => SQLT_CHR,    'length' => 4000),
            array('name' => ':P_TYPE2_REMAINING_MODE', 'value' => (int)$req_type === 2 && (int)$type2_remaining_mode === 1 ? 1 : 0, 'type' => SQLT_INT, 'length' => -1),
        );
        try {
            // proc داخل DISBURSEMENT_PKG (GFC_PAK schema)
            $result = $this->New_rmodel->general_get($this->PKG_NAME, 'DIAGNOSE_EXCEL_EMPS', $params, 0);
            $rows = $result['CUR_RES'] ?? [];
            return array('msg' => $result['MSG_OUT'] ?? 'تعذّر قراءة نتيجة التشخيص', 'rows' => is_array($rows) ? $rows : array());
        } catch (Exception $e) {
            return array('msg' => $e->getMessage(), 'rows' => array());
        }
    }

    // =========================================================
    // BATCH APPROVE
    // =========================================================
    function approve_batch($the_month, $req_ids = null)
    {
        $params = array(
            array('name' => ':P_THE_MONTH', 'value' => (int)$the_month,  'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_REQ_IDS',   'value' => $req_ids ?: null, 'type' => SQLT_CHR, 'length' => 32767),
            array('name' => ':P_MSG_OUT',   'value' => 'MSG_OUT',        'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'PAYMENT_REQ_APPROVE_BATCH', $params);
        return $this->_parse_batch_result($result['MSG_OUT'] ?? '0|0');
    }

    // =========================================================
    // BATCH PAY
    // =========================================================
    function pay_batch($the_month, $req_ids = null)
    {
        $params = array(
            array('name' => ':P_THE_MONTH', 'value' => (int)$the_month,  'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_REQ_IDS',   'value' => $req_ids ?: null, 'type' => SQLT_CHR, 'length' => 32767),
            array('name' => ':P_MSG_OUT',   'value' => 'MSG_OUT',        'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'PAYMENT_REQ_PAY_BATCH', $params);
        return $this->_parse_batch_result($result['MSG_OUT'] ?? '0|0');
    }

    // =========================================================
    // MONTH WALLET TOTALS (for check_month_status)
    // =========================================================
    // =========================================================
    // MONTH STATUS
    // =========================================================
    function get_month_status($month)
    {
        $p = array(
            array('name' => ':P_THE_MONTH', 'value' => (int)$month,    'type' => SQLT_INT, 'length' => -1),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',       'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',      'type' => SQLT_CHR, 'length' => 4000),
        );
        try {
            $rows = $this->New_rmodel->general_get($this->PKG_NAME, 'GET_MONTH_STATUS', $p, 1);
            return (is_array($rows) && count($rows) > 0) ? $rows[0] : [];
        } catch (Exception $e) {
            return [];
        }
    }

    // تعديل خفيف لوصف الطلب: البند + الملاحظات (مسودة فقط — بدون لمس الموظفين)
    // type '' لكل المدخلات: NULL يمر NULL + لا تصادم قيم INT/CHR (gotcha #1/#6)
    function req_meta_update($req_id, $pay_type, $note, $the_month = null)
    {
        $params = array(
            array('name' => ':P_REQ_ID',    'value' => $req_id,    'type' => '', 'length' => -1),
            array('name' => ':P_PAY_TYPE',  'value' => $pay_type,  'type' => '', 'length' => -1),
            array('name' => ':P_NOTE',      'value' => $note,      'type' => '', 'length' => -1),
            array('name' => ':P_THE_MONTH', 'value' => $the_month, 'type' => '', 'length' => -1),
            array('name' => ':P_MSG_OUT',   'value' => 'MSG_OUT',  'type' => SQLT_CHR, 'length' => 500),
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'REQ_META_UPDATE', $params);
        return $result['MSG_OUT'] ?? 'خطأ غير معروف';
    }

    // =========================================================
    // BULK PREVIEW
    // =========================================================
    private function _bulk_number($params, $key, $fallback)
    {
        $value = $params[$key] ?? null;
        if ($value === null || $value === '') return $fallback;
        // Keep 0.5 distinct from 0 in DBConn's value-keyed bind storage.
        return is_float($value) ? (string)$value : $value;
    }

    private function _remaining_mode($params)
    {
        return (int)($params['req_type'] ?? 0) === 2 && (string)($params['type2_remaining_mode'] ?? '0') === '1' ? 1 : 0;
    }

    private function _allowances_option($params)
    {
        return (in_array((int)($params['req_type'] ?? 0), [1, 4], true) || $this->_remaining_mode($params) === 1)
            && (string)($params['include_allowances'] ?? '0') === '1' ? 1 : 0;
    }

    function bulk_preview($params)
    {
        // Keep all scalar inputs CHAR-compatible: repeated 0/1 values share
        // DBConn storage, including flags, limits and decimal percentages.
        $p = array(
            array('name' => ':P_THE_MONTH',   'value' => (int)$params['the_month'],           'type' => '', 'length' => -1),
            array('name' => ':P_REQ_TYPE',    'value' => (int)$params['req_type'],            'type' => '', 'length' => -1),
            array('name' => ':P_CALC_METHOD', 'value' => (int)($params['calc_method'] ?: -1), 'type' => '', 'length' => -1),
            array('name' => ':P_PERCENT_VAL', 'value' => $this->_bulk_number($params, 'percent_val', -2), 'type' => SQLT_CHR, 'length' => -1),
            array('name' => ':P_REQ_AMOUNT',  'value' => $this->_bulk_number($params, 'req_amount', -3), 'type' => SQLT_CHR, 'length' => -1),
            array('name' => ':P_PAY_TYPE',    'value' => (int)($params['pay_type']    ?: -4), 'type' => '', 'length' => -1),
            array('name' => ':P_SAL_FROM',    'value' => $this->_bulk_number($params, 'sal_from', -5), 'type' => SQLT_CHR, 'length' => -1),
            array('name' => ':P_SAL_TO',      'value' => $this->_bulk_number($params, 'sal_to', -6), 'type' => SQLT_CHR, 'length' => -1),
            array('name' => ':P_BRANCH_NO',   'value' => (int)($params['branch_no']   ?: -7), 'type' => '', 'length' => -1),
            array('name' => ':P_L_VALUE',     'value' => $this->_bulk_number($params, 'l_value', -8), 'type' => SQLT_CHR, 'length' => -1),
            array('name' => ':P_H_VALUE',     'value' => $this->_bulk_number($params, 'h_value', -9), 'type' => SQLT_CHR, 'length' => -1),
            // 🆕 شهر فلتر اختياري (للنوع 3 — لا يربط مالياً)
            array('name' => ':P_FILTER_MONTH','value' => (int)($params['filter_month'] ?: 0), 'type' => '', 'length' => -1),
            array('name' => ':REF_CUR_OUT',   'value' => 'CUR_RES', 'type' => 'cursor'),
            array('name' => ':MSG_OUT',       'value' => 'MSG_OUT', 'type' => SQLT_CHR, 'length' => 4000),
            array('name' => ':P_INCLUDE_ALLOWANCES', 'value' => $this->_allowances_option($params), 'type' => '', 'length' => -1),
            array('name' => ':P_TYPE2_REMAINING_MODE', 'value' => $this->_remaining_mode($params), 'type' => '', 'length' => -1),
        );
        try {
            // Preserve both OUT values and the optional argument after MSG_OUT.
            $result = $this->New_rmodel->general_get($this->PKG_NAME, 'PAYMENT_REQ_BULK_PREVIEW', $p, 0);
            return array('msg' => $result['MSG_OUT'] ?? 'تعذّر قراءة نتيجة المعاينة',
                         'rows' => is_array($result['CUR_RES'] ?? null) ? $result['CUR_RES'] : []);
        } catch (Exception $e) {
            return array('msg' => $e->getMessage(), 'rows' => []);
        }
    }

    // =========================================================
    // BULK CREATE (1 master + N details)
    // =========================================================
    function bulk_create($params)
    {
        if ($this->conn->user != 'GFC_PAK') {
            $this->conn = new DBConn();
        }

        // Use the same bind types as preview and selected/Excel creation.
        $p = array(
            array('name' => ':P_THE_MONTH',   'value' => (int)$params['the_month'],           'type' => '', 'length' => -1),
            array('name' => ':P_REQ_TYPE',    'value' => (int)$params['req_type'],            'type' => '', 'length' => -1),
            array('name' => ':P_CALC_METHOD', 'value' => (int)($params['calc_method'] ?: -1), 'type' => '', 'length' => -1),
            array('name' => ':P_PERCENT_VAL', 'value' => $this->_bulk_number($params, 'percent_val', -2), 'type' => SQLT_CHR, 'length' => -1),
            array('name' => ':P_REQ_AMOUNT',  'value' => $this->_bulk_number($params, 'req_amount', -3), 'type' => SQLT_CHR, 'length' => -1),
            array('name' => ':P_PAY_TYPE',    'value' => (int)($params['pay_type']    ?: -4), 'type' => '', 'length' => -1),
            array('name' => ':P_SAL_FROM',    'value' => $this->_bulk_number($params, 'sal_from', -5), 'type' => SQLT_CHR, 'length' => -1),
            array('name' => ':P_SAL_TO',      'value' => $this->_bulk_number($params, 'sal_to', -6), 'type' => SQLT_CHR, 'length' => -1),
            array('name' => ':P_BRANCH_NO',   'value' => (int)($params['branch_no']   ?: -7), 'type' => '', 'length' => -1),
            array('name' => ':P_L_VALUE',     'value' => $this->_bulk_number($params, 'l_value', -8), 'type' => SQLT_CHR, 'length' => -1),
            array('name' => ':P_H_VALUE',     'value' => $this->_bulk_number($params, 'h_value', -9), 'type' => SQLT_CHR, 'length' => -1),
            array('name' => ':P_NOTE',        'value' => $params['note'] ?: 'N/A',            'type' => SQLT_CHR, 'length' => 500),
            array('name' => ':P_MSG_OUT',     'value' => 'MSG_OUT',                            'type' => SQLT_CHR, 'length' => 4000),
            // 🆕 شهر فلتر اختياري (للنوع 3)
            array('name' => ':P_FILTER_MONTH','value' => (int)($params['filter_month'] ?: 0), 'type' => '', 'length' => -1),
            array('name' => ':P_INCLUDE_ALLOWANCES', 'value' => $this->_allowances_option($params), 'type' => '', 'length' => -1),
            array('name' => ':P_TYPE2_REMAINING_MODE', 'value' => $this->_remaining_mode($params), 'type' => '', 'length' => -1),
        );
        try {
            $result = $this->conn->excuteProcedures($this->PKG_NAME, 'PAYMENT_REQ_BULK_CREATE', $p);
            return $this->_parse_bulk_result($result['MSG_OUT'] ?? '0|0|0|0');
        } catch (Exception $e) {
            return array('ok' => false, 'msg' => $e->getMessage());
        }
    }

    // =========================================================
    // Private — parse batch result 'SUCCESS|ERRORS'
    // =========================================================
    private function _parse_batch_result($msg)
    {
        $parts   = explode('|', $msg);
        $success = (int)($parts[0] ?? 0);
        $errors  = (int)($parts[1] ?? 0);

        if ($success === 0 && $errors === 0) {
            return array(
                'ok' => false, 'success' => 0, 'errors' => 0,
                'msg' => 'لا توجد سجلات مؤهلة لهذه العملية',
            );
        }

        return array(
            'ok'      => ($success > 0),
            'success' => $success,
            'errors'  => $errors,
            'msg'     => $errors > 0
                ? "تمت العملية على {$success} طلب - فشل {$errors}"
                : "تمت العملية بنجاح على {$success} طلب",
        );
    }

    // =========================================================
    // Private — parse bulk create result 'REQ_ID|SUCCESS|SKIPPED|ERRORS'
    // =========================================================
    private function _parse_bulk_result($msg)
    {
        $parts   = explode('|', $msg);
        $req_id  = (int)($parts[0] ?? 0);
        $success = (int)($parts[1] ?? 0);
        $skipped = (int)($parts[2] ?? 0);
        $errors  = (int)($parts[3] ?? 0);

        return array(
            'ok'      => ($req_id > 0),
            'req_id'  => $req_id,
            'success' => $success,
            'skipped' => $skipped,
            'errors'  => $errors,
            'msg'     => $req_id > 0
                ? "تم إنشاء الطلب #{$req_id} يضم {$success} موظف" .
                    ($skipped > 0 ? " - تخطي {$skipped}" : '') .
                    ($errors  > 0 ? " - فشل {$errors}" : '')
                : (isset($parts[4]) ? $parts[4] : 'فشل الإنشاء'),
        );
    }

    // =========================================================
    // DISBURSEMENT BATCH — تجهيز الصرف: معاينة
    // =========================================================
    function batch_preview($req_ids)
    {
        $params = array(
            array('name' => ':P_REQ_IDS',   'value' => $req_ids, 'type' => SQLT_CHR, 'length' => 32767),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',  'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',  'type' => SQLT_CHR, 'length' => 4000),
        );
        try {
            $rows = $this->New_rmodel->general_get($this->BATCH_PKG_NAME, 'PAYMENT_REQ_BATCH_PREVIEW', $params);
            return array('ok' => true, 'rows' => is_array($rows) ? $rows : []);
        } catch (Exception $e) {
            return array('ok' => false, 'rows' => [], 'msg' => $e->getMessage());
        }
    }

    // =========================================================
    // DISBURSEMENT BATCH — تحذيرات: طلبات فيها موظفين غير معتمدين
    // =========================================================
    function batch_warnings($req_ids)
    {
        $safe_ids = implode(',', array_map('intval', explode(',', $req_ids)));
        if (!$safe_ids) return [];

        // نستخدم get_list_master الموجود لجلب بيانات الطلبات
        // ثم نقارن عدد الموظفين الكلي مع المعتمدين من preview
        // أبسط: نمرر على كل طلب ونجلب تفاصيله
        $warnings = [];
        $ids = array_map('intval', explode(',', $safe_ids));
        foreach ($ids as $rid) {
            if ($rid <= 0) continue;
            try {
                $details = $this->get_details($rid);
                if (!is_array($details)) continue;
                $draft = 0; $approved = 0; $total = 0;
                foreach ($details as $d) {
                    $st = (int)($d['DETAIL_STATUS'] ?? 0);
                    if ($st == 9) continue; // skip cancelled
                    $total++;
                    if ($st == 0) $draft++;
                    if ($st == 1) $approved++;
                }
                if ($draft > 0) {
                    $req = $this->get($rid);
                    $reqRow = (is_array($req) && count($req) > 0) ? $req[0] : [];
                    $warnings[] = [
                        'REQ_ID' => $rid,
                        'REQ_NO' => $reqRow['REQ_NO'] ?? 'PR-' . str_pad($rid, 5, '0', STR_PAD_LEFT),
                        'DRAFT_COUNT' => $draft,
                        'APPROVED_COUNT' => $approved,
                        'TOTAL_COUNT' => $total,
                    ];
                }
            } catch (Exception $e) { continue; }
        }
        return $warnings;
    }

    // =========================================================
    // DISBURSEMENT BATCH — خطوة 1: اعتماد التجهيز
    // Result: BATCH_ID|EMP_COUNT|0   أو   0|0|1|error
    // =========================================================
    function batch_confirm($req_ids, $exclude_detail_ids = '', $disburse_method = 1)
    {
        if ($this->conn->user != 'GFC_PAK') {
            $this->conn = new DBConn();
        }
        // 1=قديم (DATA.EMPLOYEES) | 2=جديد (PAYMENT_ACCOUNTS_TB + split)
        $method = in_array((int)$disburse_method, [1, 2], true) ? (int)$disburse_method : 1;
        $params = array(
            array('name' => ':P_REQ_IDS',            'value' => $req_ids,                      'type' => SQLT_CHR, 'length' => 4000),
            array('name' => ':P_EXCLUDE_DETAIL_IDS', 'value' => $exclude_detail_ids ?: null,   'type' => SQLT_CHR, 'length' => 32767),
            array('name' => ':P_DISBURSE_METHOD',    'value' => $method,                       'type' => '',       'length' => -1),
            array('name' => ':P_MSG_OUT',            'value' => 'MSG_OUT',                     'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures($this->BATCH_PKG_NAME, 'PAYMENT_REQ_BATCH_CONFIRM', $params);
        return $this->_parse_batch_id_result($result['MSG_OUT'] ?? '0|0|0');
    }

    // =========================================================
    // DISBURSEMENT BATCH — خطوة 2: تنفيذ الصرف
    // Result: BATCH_ID|SUCCESS|ERRORS
    // =========================================================
    function batch_pay($batch_id)
    {
        if ($this->conn->user != 'GFC_PAK') {
            $this->conn = new DBConn();
        }
        $params = array(
            array('name' => ':P_BATCH_ID', 'value' => (int)$batch_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MSG_OUT',  'value' => 'MSG_OUT',       'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures($this->BATCH_PKG_NAME, 'PAYMENT_REQ_BATCH_PAY', $params);
        return $this->_parse_batch_pay_result($result['MSG_OUT'] ?? '', $batch_id);
    }

    private function _parse_batch_pay_result($msg, $requestedBatchId = 0)
    {
        $parts = explode('|', (string)$msg);
        $numeric = count($parts) >= 3 && ctype_digit($parts[0]) && ctype_digit($parts[1]) && ctype_digit($parts[2]);
        $batchId = $numeric ? (int)$parts[0] : 0;
        $success = $numeric ? (int)$parts[1] : 0;
        $errors = $numeric ? (int)$parts[2] : 1;
        $marker = $parts[3] ?? '';
        $known = in_array($marker, ['PAID', 'ALREADY_PAID', 'ROLLED_BACK'], true);
        $reason = $numeric ? implode('|', array_slice($parts, $known ? 4 : 3)) : trim((string)$msg);
        $paid = $numeric && $batchId > 0 && $errors === 0
            && (($marker === 'PAID' && $success > 0) || ($marker === 'ALREADY_PAID' && $success === 0)
                || (count($parts) === 3 && $success > 0));
        $state = $paid ? ($marker === 'ALREADY_PAID' ? 'ALREADY_PAID' : 'PAID')
            : ($numeric && $batchId === 0 && $success === 0 && $errors > 0 && $marker === 'ROLLED_BACK' ? 'ROLLED_BACK' : 'UNCONFIRMED');
        if ($paid) {
            $message = $state === 'ALREADY_PAID' ? 'الدفعة منفّذة مسبقاً؛ لم تُكرر أي حركة صرف'
                : 'تم تنفيذ صرف الدفعة بالكامل (' . $success . ' موظف)';
        } elseif ($state === 'ROLLED_BACK') {
            $message = 'لم يتم تنفيذ صرف الدفعة؛ أُلغيت جميع حركات هذه المحاولة';
        } else {
            $message = 'تعذر تأكيد اكتمال صرف الدفعة؛ راجع حالتها وتفاصيلها قبل إعادة المحاولة';
            if ($numeric && $success > 0 && $errors > 0) {
                $reason = 'النتيجة الواردة: ' . $success . ' ناجح و' . $errors . ' فاشل. ' . $reason;
            }
        }
        $details = $reason === '' ? [] : preg_split('/\r\n|\r|\n/', $reason, -1, PREG_SPLIT_NO_EMPTY);
        return ['ok' => $paid, 'batch_id' => $batchId ?: (int)$requestedBatchId,
            'success' => $paid ? $success : 0, 'errors' => $paid ? 0 : max($errors, 1),
            'state' => $state, 'msg' => $message, 'failure_details' => $details];
    }

    // =========================================================
    // Private — parse BATCH_ID|SUCCESS|ERRORS result
    // =========================================================
    private function _parse_batch_id_result($msg)
    {
        $parts    = explode('|', $msg);
        $batch_id = (int)($parts[0] ?? 0);
        $success  = (int)($parts[1] ?? 0);
        $errors   = (int)($parts[2] ?? 0);

        if ($batch_id === 0 && $success === 0 && $errors === 0) {
            return array(
                'ok' => false, 'batch_id' => 0, 'success' => 0, 'errors' => 0,
                'msg' => 'لا توجد سجلات مؤهلة',
            );
        }

        $err_detail = isset($parts[3]) ? $parts[3] : '';
        return array(
            'ok'       => ($batch_id > 0),
            'batch_id' => $batch_id,
            'success'  => $success,
            'errors'   => $errors,
            'msg'      => $batch_id > 0
                ? "دفعة رقم PB-" . str_pad($batch_id, 5, '0', STR_PAD_LEFT) . " ({$success} موظف)"
                    . ($errors > 0 ? " — فشل {$errors}" : '')
                : ($err_detail ?: 'فشلت العملية'),
        );
    }

    // =========================================================
    // REPORT — تقرير شهري
    // =========================================================
    function get_report($month_from, $month_to = null, $branch_no = null, $req_type = null, $status = null)
    {
        $params = array(
            array('name' => ':P_MONTH_FROM', 'value' => (int)$month_from,        'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MONTH_TO',   'value' => $month_to ? (int)$month_to : null, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_BRANCH_NO',  'value' => $branch_no ?: null,      'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_REQ_TYPE',   'value' => $req_type ?: null,       'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_STATUS',     'value' => ($status !== null && $status !== '') ? (int)$status : null, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':REF_CUR_OUT',  'value' => 'cursor',  'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',      'value' => 'MSG_OUT', 'type' => SQLT_CHR, 'length' => 4000),
        );
        return $this->New_rmodel->general_get($this->PKG_NAME, 'PAYMENT_REQ_REPORT', $params);
    }

    // =========================================================
    // تقرير التزام المستحقات (بند 323) — DUES_LIABILITY_PKG
    // =========================================================
    function liab_summary($branch_no = null)
    {
        $params = array(
            array('name' => ':P_BRANCH_NO',  'value' => $branch_no ?: null, 'type' => '', 'length' => -1),
            array('name' => ':REF_CUR_OUT',  'value' => 'cursor',  'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',      'value' => 'MSG_OUT', 'type' => SQLT_CHR, 'length' => 4000),
        );
        return $this->New_rmodel->general_get($this->LIAB_PKG_NAME, 'LIAB_SUMMARY', $params);
    }

    function liab_movement($month_from = null, $month_to = null, $branch_no = null)
    {
        $params = array(
            array('name' => ':P_MONTH_FROM', 'value' => $month_from ? (int)$month_from : null, 'type' => '', 'length' => -1),
            array('name' => ':P_MONTH_TO',   'value' => $month_to ? (int)$month_to : null,     'type' => '', 'length' => -1),
            array('name' => ':P_BRANCH_NO',  'value' => $branch_no ?: null, 'type' => '', 'length' => -1),
            array('name' => ':REF_CUR_OUT',  'value' => 'cursor',  'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',      'value' => 'MSG_OUT', 'type' => SQLT_CHR, 'length' => 4000),
        );
        return $this->New_rmodel->general_get($this->LIAB_PKG_NAME, 'LIAB_MOVEMENT', $params);
    }

    function liab_by_branch()
    {
        $params = array(
            array('name' => ':REF_CUR_OUT',  'value' => 'cursor',  'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',      'value' => 'MSG_OUT', 'type' => SQLT_CHR, 'length' => 4000),
        );
        return $this->New_rmodel->general_get($this->LIAB_PKG_NAME, 'LIAB_BY_BRANCH', $params);
    }

    function liab_employees($branch_no = null, $emp_no = null)
    {
        $params = array(
            array('name' => ':P_BRANCH_NO',  'value' => $branch_no ?: null, 'type' => '', 'length' => -1),
            array('name' => ':P_EMP_NO',     'value' => $emp_no ? (int)$emp_no : null, 'type' => '', 'length' => -1),
            array('name' => ':REF_CUR_OUT',  'value' => 'cursor',  'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',      'value' => 'MSG_OUT', 'type' => SQLT_CHR, 'length' => 4000),
        );
        return $this->New_rmodel->general_get($this->LIAB_PKG_NAME, 'LIAB_EMPLOYEES', $params);
    }

    function liab_emp_monthly($emp_no)
    {
        $params = array(
            array('name' => ':P_EMP_NO',     'value' => (int)$emp_no, 'type' => '', 'length' => -1),
            array('name' => ':REF_CUR_OUT',  'value' => 'cursor',  'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',      'value' => 'MSG_OUT', 'type' => SQLT_CHR, 'length' => 4000),
        );
        return $this->New_rmodel->general_get($this->LIAB_PKG_NAME, 'LIAB_EMP_MONTHLY', $params);
    }

    // =========================================================
    // STATEMENT — كشف حساب موظف
    // =========================================================
    function get_emp_statement($emp_no, $month_from = null, $month_to = null)
    {
        $params = array(
            array('name' => ':P_EMP_NO',     'value' => (int)$emp_no,                        'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MONTH_FROM', 'value' => $month_from ? (int)$month_from : null, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MONTH_TO',   'value' => $month_to ? (int)$month_to : null,     'type' => SQLT_INT, 'length' => -1),
            array('name' => ':REF_CUR_OUT',  'value' => 'cursor',  'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',      'value' => 'MSG_OUT', 'type' => SQLT_CHR, 'length' => 4000),
        );
        return $this->New_rmodel->general_get($this->PKG_NAME, 'EMP_STATEMENT', $params);
    }

    // Keep the procedure message: a failed lookup must not look like no payments.
    function get_emp_month_payments($emp_no, $month)
    {
        $params = array(
            array('name' => ':P_EMP_NO',      'value' => (int)$emp_no, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MONTH_FROM',  'value' => (int)$month,  'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MONTH_TO',    'value' => (int)$month,  'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_REF_CUR_OUT', 'value' => 'CUR_RES', 'type' => 'cursor'),
            array('name' => ':P_MSG_OUT',     'value' => 'MSG_OUT', 'type' => SQLT_CHR, 'length' => 4000),
        );
        try {
            $result = $this->New_rmodel->general_get($this->PKG_NAME, 'EMP_STATEMENT', $params, 0);
            if (!is_array($result) || !isset($result['CUR_RES']) || !is_array($result['CUR_RES'])) {
                return array('msg' => 'تعذر قراءة دفعات الموظف.', 'rows' => array());
            }
            return array('msg' => $result['MSG_OUT'] ?? 'تعذر قراءة دفعات الموظف.', 'rows' => $result['CUR_RES']);
        } catch (\Exception $e) {
            log_message('error', 'get_emp_month_payments: ' . $e->getMessage());
            return array('msg' => 'تعذر قراءة دفعات الموظف.', 'rows' => array());
        }
    }

    // =========================================================
    // BENEFIT ITEMS — بنود الاستحقاقات (نوع 5)
    // =========================================================
    function get_benefit_items()
    {
        $params = array(
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',  'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',  'type' => SQLT_CHR, 'length' => -1),
        );
        return $this->New_rmodel->general_get($this->PKG_NAME, 'GET_BENEFIT_ITEMS', $params);
    }

    // =========================================================
    // BATCH HISTORY — list + get
    // =========================================================
    function batch_history_list()
    {
        $params = array(
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',  'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',  'type' => SQLT_CHR, 'length' => 4000),
        );
        return $this->New_rmodel->general_get($this->BATCH_PKG_NAME, 'BATCH_HISTORY_LIST', $params);
    }

    function unapprove($req_id, $note = '')
    {
        $params = array(
            array('name' => ':P_REQ_ID', 'value' => (int)$req_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_NOTE',   'value' => $note ?: null, 'type' => SQLT_CHR, 'length' => 500),
            array('name' => ':P_MSG_OUT','value' => 'MSG_OUT',      'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'PAYMENT_REQ_UNAPPROVE', $params);
        return $result['MSG_OUT'];
    }

    // =========================================================
    // 🆕 RAQABA — اعتماد/رفض/تراجع الرقابة
    // =========================================================
    function raqaba_approve($req_id, $note = '')
    {
        $params = array(
            array('name' => ':P_REQ_ID',  'value' => (int)$req_id,   'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_NOTE',    'value' => $note ?: null,  'type' => SQLT_CHR, 'length' => 500),
            array('name' => ':P_MSG_OUT', 'value' => 'MSG_OUT',      'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'RAQABA_APPROVE', $params);
        return $result['MSG_OUT'];
    }

    function raqaba_reject($req_id, $note)
    {
        $params = array(
            array('name' => ':P_REQ_ID',  'value' => (int)$req_id,  'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_NOTE',    'value' => (string)$note, 'type' => SQLT_CHR, 'length' => 500),
            array('name' => ':P_MSG_OUT', 'value' => 'MSG_OUT',     'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'RAQABA_REJECT', $params);
        return $result['MSG_OUT'];
    }

    function raqaba_undo($req_id)
    {
        $params = array(
            array('name' => ':P_REQ_ID',  'value' => (int)$req_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MSG_OUT', 'value' => 'MSG_OUT',    'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'RAQABA_UNDO', $params);
        return $result['MSG_OUT'];
    }

    // يرجع الطلبات غير المعتمدة رقابياً (للبوابة قبل الاحتساب/الصرف)
    function raqaba_pending_for_reqs($req_ids_csv)
    {
        $params = array(
            array('name' => ':P_REQ_IDS',   'value' => (string)$req_ids_csv, 'type' => SQLT_CHR,  'length' => 32767),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',             'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',            'type' => SQLT_CHR,  'length' => -1),
        );
        try {
            $rows = $this->New_rmodel->general_get($this->PKG_NAME, 'RAQABA_PENDING_FOR_REQS', $params);
            return is_array($rows) ? $rows : array();
        } catch (Exception $e) {
            return array();
        }
    }

    function batch_reverse_pay($batch_id)
    {
        if ($this->conn->user != 'GFC_PAK') {
            $this->conn = new DBConn();
        }
        $params = array(
            array('name' => ':P_BATCH_ID', 'value' => (int)$batch_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MSG_OUT',  'value' => 'MSG_OUT',       'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures($this->BATCH_PKG_NAME, 'BATCH_REVERSE_PAY', $params);
        return $result['MSG_OUT'];
    }

    function batch_cancel($batch_id)
    {
        $params = array(
            array('name' => ':P_BATCH_ID', 'value' => (int)$batch_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MSG_OUT',  'value' => 'MSG_OUT',       'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures($this->BATCH_PKG_NAME, 'BATCH_CANCEL', $params);
        return $result['MSG_OUT'];
    }

    function batch_history_get($batch_id)
    {
        $params = array(
            array('name' => ':P_BATCH_ID',  'value' => (int)$batch_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',       'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',       'type' => SQLT_CHR, 'length' => 4000),
        );
        return $this->New_rmodel->general_get($this->BATCH_PKG_NAME, 'BATCH_HISTORY_GET', $params);
    }

    // تفصيل صرف الدفعة حسب البيان (PAY_TYPE) — لتقرير الطباعة
    function batch_pay_type_summary($batch_id)
    {
        $params = array(
            array('name' => ':P_BATCH_ID',    'value' => (int)$batch_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_REF_CUR_OUT', 'value' => 'cursor',       'type' => OCI_B_CURSOR),
            array('name' => ':P_MSG_OUT',     'value' => 'MSG_OUT',      'type' => SQLT_CHR, 'length' => 4000),
        );
        return $this->New_rmodel->general_get('GFC_PAK.BATCH_REPORT_PKG', 'PAY_TYPE_SUMMARY', $params);
    }

    // توزيع الوجهة: بنك / محفظة / وريث (لتقرير المقارنات)
    function batch_dest_summary($batch_id)
    {
        $params = array(
            array('name' => ':P_BATCH_ID',    'value' => (int)$batch_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_REF_CUR_OUT', 'value' => 'cursor',       'type' => OCI_B_CURSOR),
            array('name' => ':P_MSG_OUT',     'value' => 'MSG_OUT',      'type' => SQLT_CHR, 'length' => 4000),
        );
        return $this->New_rmodel->general_get('GFC_PAK.BATCH_REPORT_PKG', 'BATCH_DEST_SUMMARY', $params);
    }

    // الموظفون المستثنون من الدفعة + السبب
    function batch_excluded_emps($batch_id)
    {
        $params = array(
            array('name' => ':P_BATCH_ID',    'value' => (int)$batch_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_REF_CUR_OUT', 'value' => 'cursor',       'type' => OCI_B_CURSOR),
            array('name' => ':P_MSG_OUT',     'value' => 'MSG_OUT',      'type' => SQLT_CHR, 'length' => 4000),
        );
        return $this->New_rmodel->general_get('GFC_PAK.BATCH_REPORT_PKG', 'BATCH_EXCLUDED_EMPS', $params);
    }

    function batch_emp_accounts($batch_id, $emp_no)
    {
        $params = array(
            array('name' => ':P_BATCH_ID',  'value' => (int)$batch_id, 'type' => SQLT_INT,    'length' => -1),
            array('name' => ':P_EMP_NO',    'value' => (int)$emp_no,   'type' => SQLT_INT,    'length' => -1),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',       'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',      'type' => SQLT_CHR,    'length' => 4000),
        );
        return $this->New_rmodel->general_get($this->BATCH_PKG_NAME, 'BATCH_EMP_ACCOUNTS_GET', $params);
    }

    // =========================================================
    // 🆕 BATCH COMPARE — Trend / Diff / Diff Summary
    // =========================================================
    function batch_trend($batch_id, $limit = 6)
    {
        $params = array(
            array('name' => ':P_BATCH_ID',  'value' => (int)$batch_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_LIMIT',     'value' => (int)$limit,    'type' => SQLT_INT, 'length' => -1),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',       'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',      'type' => SQLT_CHR, 'length' => 4000),
        );
        return $this->New_rmodel->general_get($this->COMPARE_PKG_NAME, 'BATCH_TREND', $params);
    }

    function batch_diff($cur_batch_id, $prv_batch_id)
    {
        $params = array(
            array('name' => ':P_CUR_BATCH_ID', 'value' => (int)$cur_batch_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_PRV_BATCH_ID', 'value' => (int)$prv_batch_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':REF_CUR_OUT',    'value' => 'cursor',           'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',        'value' => 'MSG_OUT',          'type' => SQLT_CHR, 'length' => 4000),
        );
        return $this->New_rmodel->general_get($this->COMPARE_PKG_NAME, 'BATCH_DIFF', $params);
    }

    function batch_diff_summary($cur_batch_id, $prv_batch_id)
    {
        $params = array(
            array('name' => ':P_CUR_BATCH_ID', 'value' => (int)$cur_batch_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_PRV_BATCH_ID', 'value' => (int)$prv_batch_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':REF_CUR_OUT',    'value' => 'cursor',           'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',        'value' => 'MSG_OUT',          'type' => SQLT_CHR, 'length' => 4000),
        );
        return $this->New_rmodel->general_get($this->COMPARE_PKG_NAME, 'BATCH_DIFF_SUMMARY', $params);
    }

    // 🆕 ⑨ مقارنة حسب البند — ملخص: لكل بند أعداد ومبالغ الدفعتين + الفرق
    //    الدفعة المرجعية اختيارية (null = وضع دفعة واحدة → كل البنود NEW)
    function batch_pay_type_diff($cur_batch_id, $ref_batch_id = null)
    {
        $params = array(
            array('name' => ':P_CUR_BATCH_ID', 'value' => (int)$cur_batch_id, 'type' => SQLT_INT, 'length' => -1),
            // type '' للمرجعية: NULL يمر NULL ولا يتحوّل صفراً [[gotcha #6]]
            array('name' => ':P_REF_BATCH_ID', 'value' => ($ref_batch_id ? (int)$ref_batch_id : null), 'type' => '', 'length' => -1),
            array('name' => ':REF_CUR_OUT',    'value' => 'cursor',           'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',        'value' => 'MSG_OUT',          'type' => SQLT_CHR, 'length' => 4000),
        );
        // تدهور آمن لبروسيجر قد لا يكون منشوراً بعد.
        // ⚠️ لا يكفي try/catch: DBConn::error_handeling يطبع نص خطأ أوراكل ويعود،
        //    ولا يرمي استثناءً إطلاقاً — فالنص كان يسبق <!DOCTYPE> ويفسد التصدير.
        //    لذلك نبتلع المخرجات ونفحص النوع بدل انتظار استثناء لا يأتي.
        ob_start();
        try {
            $res = $this->New_rmodel->general_get($this->COMPARE_PKG_NAME, 'BATCH_PAY_TYPE_DIFF', $params);
        } catch (Exception $e) {
            $res = null;
        }
        ob_end_clean();
        return is_array($res) ? $res : [];
    }

    // Batch comparison by month: amount scoped to the batch via LINK
    function batch_month_diff($cur_batch_id, $ref_batch_id = null)
    {
        $params = array(
            array('name' => ':P_CUR_BATCH_ID', 'value' => (int)$cur_batch_id, 'type' => SQLT_INT, 'length' => -1),
            // type '' للمرجعية: NULL يمر NULL ولا يتحوّل صفراً [[gotcha #6]]
            array('name' => ':P_REF_BATCH_ID', 'value' => ($ref_batch_id ? (int)$ref_batch_id : null), 'type' => '', 'length' => -1),
            array('name' => ':REF_CUR_OUT',    'value' => 'cursor',           'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',        'value' => 'MSG_OUT',          'type' => SQLT_CHR, 'length' => 4000),
        );
        // تدهور آمن لبروسيجر قد لا يكون منشوراً بعد.
        // ⚠️ لا يكفي try/catch: DBConn::error_handeling يطبع نص خطأ أوراكل ويعود،
        //    ولا يرمي استثناءً إطلاقاً — فالنص كان يسبق <!DOCTYPE> ويفسد التصدير.
        //    لذلك نبتلع المخرجات ونفحص النوع بدل انتظار استثناء لا يأتي.
        ob_start();
        try {
            $res = $this->New_rmodel->general_get($this->COMPARE_PKG_NAME, 'BATCH_MONTH_DIFF', $params);
        } catch (Exception $e) {
            $res = null;
        }
        ob_end_clean();
        return is_array($res) ? $res : [];
    }

    // 🆕 ⑨ مقارنة حسب البند — تفصيل (موظف × بند) بين الدفعتين
    function batch_pay_type_emp_diff($cur_batch_id, $ref_batch_id = null)
    {
        $params = array(
            array('name' => ':P_CUR_BATCH_ID', 'value' => (int)$cur_batch_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_REF_BATCH_ID', 'value' => ($ref_batch_id ? (int)$ref_batch_id : null), 'type' => '', 'length' => -1),
            array('name' => ':REF_CUR_OUT',    'value' => 'cursor',           'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',        'value' => 'MSG_OUT',          'type' => SQLT_CHR, 'length' => 4000),
        );
        // نفس العلة: DBConn يطبع خطأ أوراكل ولا يرميه — نبتلع المخرجات ونفحص النوع
        ob_start();
        try {
            $res = $this->New_rmodel->general_get($this->COMPARE_PKG_NAME, 'BATCH_PAY_TYPE_EMP_DIFF', $params);
        } catch (Exception $e) {
            $res = null;
        }
        ob_end_clean();
        return is_array($res) ? $res : [];
    }

    // 🆕 حالة كل (موظف+شهر) داخل الدفعة (للفلتر الزمني)
    function batch_emp_month_statuses($batch_id)
    {
        $params = array(
            array('name' => ':P_BATCH_ID',  'value' => (int)$batch_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',       'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',      'type' => SQLT_CHR, 'length' => 4000),
        );
        return $this->New_rmodel->general_get($this->COMPARE_PKG_NAME, 'BATCH_EMP_MONTH_STATUSES', $params);
    }

    // =========================================================
    // EMP INFO — معلومات الموظف الأساسية للكشف الشامل
    // اسم + مقر + IS_ACTIVE + الحالة المركّبة (1/0/2/4)
    // =========================================================
    function get_emp_info($emp_no)
    {
        $params = array(
            array('name' => ':P_EMP_NO',     'value' => (int)$emp_no,  'type' => SQLT_INT,    'length' => -1),
            array('name' => ':REF_CUR_OUT',  'value' => 'cursor',      'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',      'value' => 'MSG_OUT',     'type' => SQLT_CHR,    'length' => 4000),
        );
        return $this->New_rmodel->general_get($this->PKG_NAME, 'GET_EMP_INFO', $params);
    }

    // =========================================================
    // EMP ACCOUNTS — للـ override modal (بدون batch_id)
    // يستخدم PAYMENT_ACCOUNTS_PKG.ACCOUNTS_LIST
    // =========================================================
    function emp_accounts_list($emp_no, $only_active = 1)
    {
        $params = array(
            array('name' => ':P_EMP_NO',      'value' => (int)$emp_no,    'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_ONLY_ACTIVE', 'value' => (int)$only_active,'type'=> SQLT_INT, 'length' => -1),
            array('name' => ':REF_CUR_OUT',   'value' => 'cursor',        'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',       'value' => 'MSG_OUT',       'type' => SQLT_CHR, 'length' => 4000),
        );
        return $this->New_rmodel->general_get('PAYMENT_ACCOUNTS_PKG', 'ACCOUNTS_LIST', $params);
    }

    // =========================================================
    // BATCH DETAIL REDIST — إعادة توزيع موظف في دفعة محتسبة
    // =========================================================
    function batch_detail_redist($batch_id, $emp_no, $provider_type = null, $acc_id = null)
    {
        $pt_val  = ($provider_type === '' || $provider_type === null) ? null : (int)$provider_type;
        $acc_val = ($acc_id === '' || $acc_id === null) ? null : (int)$acc_id;
        $params = array(
            array('name' => ':P_BATCH_ID',     'value' => (int)$batch_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_EMP_NO',       'value' => (int)$emp_no,   'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_PROVIDER_TYPE','value' => $pt_val,        'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_ACC_ID',       'value' => $acc_val,       'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MSG_OUT',      'value' => 'MSG_OUT',      'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures($this->BATCH_PKG_NAME, 'BATCH_DETAIL_REDIST', $params);
        return $result['MSG_OUT'];
    }

    function batch_bank_summary($batch_id)
    {
        $params = array(
            array('name' => ':P_BATCH_ID',  'value' => (int)$batch_id, 'type' => SQLT_INT,    'length' => -1),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',       'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',      'type' => SQLT_CHR,    'length' => 4000),
        );
        return $this->New_rmodel->general_get($this->BATCH_PKG_NAME, 'BATCH_BANK_SUMMARY_GET', $params);
    }

    // ============================================================
    // 🆕 PAYMENT_COVER LINKING — ربط نموذج الصرف مع الدفعة
    // ============================================================
    // كل العمليات عبر procedures في DISBURSEMENT_BATCH_PKG
    // (نفس نمط المشروع: $this->conn->excuteProcedures + $this->New_rmodel->general_get)
    // ============================================================

    function cover_find_by_batch($batch_id, $master_bank_no)
    {
        $params = array(
            array('name' => ':P_BATCH_ID',       'value' => (int)$batch_id,       'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MASTER_BANK_NO', 'value' => (int)$master_bank_no, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_COVER_ID_OUT',   'value' => 'COVER_ID',           'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MSG_OUT',        'value' => 'MSG_OUT',            'type' => SQLT_CHR, 'length' => 500),
        );
        $result = $this->conn->excuteProcedures($this->BATCH_PKG_NAME, 'COVER_FIND_BY_BATCH', $params);
        $cover_id = (int)($result['COVER_ID'] ?? 0);
        return $cover_id > 0 ? $cover_id : null;
    }

    function cover_find_all_by_batch($batch_id)
    {
        $params = array(
            array('name' => ':P_BATCH_ID',  'value' => (int)$batch_id, 'type' => SQLT_INT,    'length' => -1),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',       'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',      'type' => SQLT_CHR,    'length' => 500),
        );
        $rows = $this->New_rmodel->general_get($this->BATCH_PKG_NAME, 'COVER_FIND_ALL_BY_BATCH', $params);
        return is_array($rows) ? $rows : array();
    }

    function cover_create_from_batch($batch_id, $master_bank_no, $bank_name, $total_amount, $cover_text, $invoice_id, $invoice_date)
    {
        $params = array(
            array('name' => ':P_BATCH_ID',       'value' => (int)$batch_id,        'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MASTER_BANK_NO', 'value' => (int)$master_bank_no,  'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_BANK_NAME',      'value' => (string)$bank_name,    'type' => '',       'length' => -1),
            array('name' => ':P_TOTAL_AMOUNT',   'value' => (float)$total_amount,  'type' => '',       'length' => -1),
            array('name' => ':P_COVER_TYPE',     'value' => (string)$cover_text,   'type' => '',       'length' => -1),
            array('name' => ':P_INVOICE_ID',     'value' => (string)$invoice_id,   'type' => '',       'length' => -1),
            array('name' => ':P_INVOICE_DATE',   'value' => (string)$invoice_date, 'type' => '',       'length' => -1),
            array('name' => ':P_NEW_COVER_ID',   'value' => 'NEW_ID',              'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MSG_OUT',        'value' => 'MSG_OUT',             'type' => SQLT_CHR, 'length' => 500),
        );
        $result = $this->conn->excuteProcedures($this->BATCH_PKG_NAME, 'COVER_CREATE_FROM_BATCH', $params);
        $new_id = (int)($result['NEW_ID'] ?? 0);
        $msg    = (string)($result['MSG_OUT'] ?? '');

        // الـ proc يرجع: '1' أو '1|existing' لو نجح
        if ($new_id > 0 && (strpos($msg, '1') === 0)) {
            return $new_id;
        }
        throw new Exception('فشل إنشاء نموذج الصرف: ' . $msg);
    }

    function cover_delete_from_batch($batch_id, $master_bank_no)
    {
        $params = array(
            array('name' => ':P_BATCH_ID',       'value' => (int)$batch_id,       'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MASTER_BANK_NO', 'value' => (int)$master_bank_no, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_OK_OUT',         'value' => 'OK',                 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_COVER_ID_OUT',   'value' => 'COVER_ID',           'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_FP_ID_OUT',      'value' => 'FP_ID',              'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MSG_OUT',        'value' => 'MSG_OUT',            'type' => SQLT_CHR, 'length' => 500),
        );
        $result = $this->conn->excuteProcedures($this->BATCH_PKG_NAME, 'COVER_DELETE_FROM_BATCH', $params);
        return [
            'ok'       => (int)($result['OK'] ?? 0) === 1,
            'msg'      => (string)($result['MSG_OUT'] ?? ''),
            'cover_id' => (int)($result['COVER_ID'] ?? 0),
            'fp_id'    => (int)($result['FP_ID'] ?? 0),
        ];
    }

    // الدفعات المحتسبة (غير منفّذة) لموظف معين — للتنبيه عند تعديل الحساب
    function emp_pending_batches($emp_no)
    {
        $params = array(
            array('name' => ':P_EMP_NO',    'value' => (int)$emp_no, 'type' => SQLT_INT,    'length' => -1),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',     'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',    'type' => SQLT_CHR,    'length' => 500),
        );
        return $this->New_rmodel->general_get($this->BATCH_PKG_NAME, 'EMP_PENDING_BATCHES', $params);
    }

    function batch_preview_validation($req_ids, $exclude_ids = '')
    {
        $params = array(
            array('name' => ':P_REQ_IDS',            'value' => $req_ids,     'type' => SQLT_CHR,    'length' => -1),
            array('name' => ':P_EXCLUDE_DETAIL_IDS', 'value' => $exclude_ids, 'type' => SQLT_CHR,    'length' => -1),
            array('name' => ':REF_CUR_OUT',          'value' => 'cursor',     'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',              'value' => 'MSG_OUT',    'type' => SQLT_CHR,    'length' => 4000),
        );
        return $this->New_rmodel->general_get($this->BATCH_PKG_NAME, 'BATCH_PREVIEW', $params);
    }

    /**
     * BATCH_COMPUTE_PREVIEW: التوزيع التفصيلي للعرض في شاشة الاحتساب
     * يرجع لكل (موظف+حساب) سطر مع المبلغ المخصّص + STATUS + بيانات الحساب
     * بدون كتابة في DB — للعرض فقط
     */
    function batch_compute_preview($req_ids, $exclude_ids = '')
    {
        $params = array(
            array('name' => ':P_REQ_IDS',            'value' => $req_ids,     'type' => SQLT_CHR,    'length' => -1),
            array('name' => ':P_EXCLUDE_DETAIL_IDS', 'value' => $exclude_ids, 'type' => SQLT_CHR,    'length' => -1),
            array('name' => ':REF_CUR_OUT',          'value' => 'cursor',     'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',              'value' => 'MSG_OUT',    'type' => SQLT_CHR,    'length' => 4000),
        );
        return $this->New_rmodel->general_get($this->BATCH_PKG_NAME, 'BATCH_COMPUTE_PREVIEW', $params);
    }

    function batch_refresh_split($batch_id)
    {
        $params = array(
            array('name' => ':P_BATCH_ID',    'value' => (int)$batch_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_CHANGED_OUT', 'value' => 'CHG',          'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MSG_OUT',     'value' => 'MSG',          'type' => SQLT_CHR, 'length' => 500),
        );
        $result = $this->conn->excuteProcedures($this->BATCH_PKG_NAME, 'BATCH_REFRESH_SPLIT', $params);
        return [
            'msg'     => $result['MSG'] ?? '',
            'changed' => intval($result['CHG'] ?? 0),
        ];
    }

    // =========================================================
    // BANK EXPORT — تصدير بيانات الدفعة للبنك
    // =========================================================
    function batch_bank_export($batch_id, $master_bank_no = null)
    {
        $params = array(
            array('name' => ':P_BATCH_ID',       'value' => (int)$batch_id,                          'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MASTER_BANK_NO', 'value' => $master_bank_no ? (int)$master_bank_no : -1, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':REF_CUR_OUT',      'value' => 'cursor',                                'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',          'value' => 'MSG_OUT',                                'type' => SQLT_CHR, 'length' => 4000),
        );
        return $this->New_rmodel->general_get($this->BATCH_PKG_NAME, 'BATCH_BANK_EXPORT', $params);
    }

    // 🆕 كشف PALPAY بدون تجميع — صف لكل طلب أصلي (للمحفظات فقط)
    // المشكلة: BATCH_DETAIL_TB يخزّن صف مجمّع لكل (emp × destination) →
    //          موظف عنده 50 طلب يظهر بصف واحد بقيمة المجموع.
    // الحل: نقرأ من PAYMENT_REQ_DETAIL_TB (المصدر) ونرجع صف لكل طلب.
    function batch_wallet_export_raw($batch_id, $master_bank_no = null)
    {
        $params = array(
            array('name' => ':P_BATCH_ID',       'value' => (int)$batch_id,                          'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MASTER_BANK_NO', 'value' => $master_bank_no ? (int)$master_bank_no : -1, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':REF_CUR_OUT',      'value' => 'cursor',                                'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',          'value' => 'MSG_OUT',                                'type' => SQLT_CHR, 'length' => 4000),
        );
        return $this->New_rmodel->general_get($this->BATCH_PKG_NAME, 'BATCH_WALLET_EXPORT_RAW', $params);
    }

    // 🆕 كشف توزيع موظف في دفعة: من 3000ج → كم للبنك + كم للوريث + كم للمحفظة
    function emp_batch_breakdown($batch_id, $emp_no)
    {
        $params = array(
            array('name' => ':P_BATCH_ID',  'value' => (int)$batch_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_EMP_NO',    'value' => (int)$emp_no,   'type' => SQLT_INT, 'length' => -1),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',       'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',      'type' => SQLT_CHR, 'length' => 4000),
        );
        return $this->New_rmodel->general_get($this->BATCH_PKG_NAME, 'EMP_BATCH_BREAKDOWN', $params);
    }

    // 🆕 كل دفعات الموظف مع توزيعها (بنك/محفظة/وريث)
    function emp_all_batches($emp_no)
    {
        $params = array(
            array('name' => ':P_EMP_NO',    'value' => (int)$emp_no, 'type' => SQLT_INT,    'length' => -1),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor',     'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT',     'value' => 'MSG_OUT',    'type' => SQLT_CHR,    'length' => 4000),
        );
        return $this->New_rmodel->general_get($this->BATCH_PKG_NAME, 'EMP_ALL_BATCHES', $params);
    }

    // =========================================================
    // DETAIL — PREVIEW single employee (no insert)
    // =========================================================
    function detail_preview_single($req_id, $emp_no, $req_amount = 0)
    {
        $params = array(
            array('name' => ':P_REQ_ID',     'value' => (int)$req_id,       'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_EMP_NO',     'value' => (int)$emp_no,       'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_REQ_AMOUNT', 'value' => (float)$req_amount, 'type' => SQLT_CHR, 'length' => -1),
            array('name' => ':P_MSG_OUT',    'value' => 'MSG_OUT',          'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'DETAIL_PREVIEW_SINGLE', $params);
        return $result['MSG_OUT'];
    }

    // =========================================================
    // DUPLICATE CHECK — active request with same month+type
    // =========================================================
    function count_active_requests($the_month, $req_type)
    {
        $params = array(
            array('name' => ':P_THE_MONTH', 'value' => (int)$the_month, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_REQ_TYPE',  'value' => (int)$req_type,  'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MSG_OUT',   'value' => 'MSG_OUT',       'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'CHECK_DUPLICATE_REQUEST', $params);
        return (int)($result['MSG_OUT'] ?? 0);
    }

    // =========================================================
    // DUPLICATE CHECK — employee in another active request (same month+type)
    // =========================================================
    function emp_in_other_request($emp_no, $req_id)
    {
        $params = array(
            array('name' => ':P_EMP_NO',  'value' => (int)$emp_no, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_REQ_ID',  'value' => (int)$req_id, 'type' => SQLT_INT, 'length' => -1),
            array('name' => ':P_MSG_OUT', 'value' => 'MSG_OUT',    'type' => SQLT_CHR, 'length' => 4000),
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'CHECK_DUPLICATE_EMP', $params);
        $msg = $result['MSG_OUT'] ?? '';
        return ($msg && $msg !== '0') ? $msg : null;
    }
}
