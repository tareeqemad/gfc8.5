<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once(APPPATH . 'third_party/phpoffice/vendor/autoload.php');
require_once __DIR__ . '/../helpers/payment_req_report_helper.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Payment_req extends MY_Controller
{
    var $PKG_NAME       = "GFC_PAK.DISBURSEMENT_PKG";
    var $MODEL_NAME     = "Payment_req_model";
    var $PAGE_URL       = "payment_req/payment_req/get_page";

    function __construct()
    {
        parent::__construct();
        $this->load->helper('payroll_access');
        require_payroll_access();
        $this->load->model($this->MODEL_NAME);
        $this->load->model('settings/gcc_branches_model');
        $this->load->model('payroll_data/salary_dues_types_model');

        $this->approve_url       = base_url('payment_req/payment_req/approve');
        $this->pay_url           = base_url('payment_req/payment_req/do_pay');
        $this->delete_url        = base_url('payment_req/payment_req/delete');
        $this->create_url        = base_url('payment_req/payment_req/create');
        $this->approve_batch_url = base_url('payment_req/payment_req/approve_batch');
        $this->pay_batch_url     = base_url('payment_req/payment_req/pay_batch');
        $this->import_url        = base_url('payment_req/payment_req/import_excel');
    }

    // ==================== INDEX ====================
    function index($page = 1, $branch_no = -1, $the_month = -1, $emp_no = -1, $status = -1)
    {
        $data['title']     = 'صرف الرواتب والمستحقات';
        $data['content']   = 'payment_req_index';
        $data['page']      = $page;
        $data['branch_no'] = $branch_no;
        $data['the_month'] = $the_month;
        $data['emp_no']    = $emp_no;
        $data['status']    = $status;
        $this->_lookup($data, 'list');
        $this->load->view('template/template1', $data);
    }

    // ==================== PAGINATED LIST ====================
    // ─────────────────────────────────────────────────────────────────
    // بناء WHERE SQL للفلاتر — دالتان لكل وضع عرض
    // ─────────────────────────────────────────────────────────────────
    //
    // الفرق بين detail و master:
    //   • detail: صف لكل (طلب × موظف) — فلتر D.EMP_NO أو فرع الموظف
    //             يُطبّق مباشرة على الصف.
    //   • master: صف لكل طلب — لو فلترنا D.EMP_NO='508' مباشرة، الطلب
    //             يظهر فقط لو 508 هو في صفوف الديتيل المُختارة. لكن في
    //             master الـ JOIN قد يجيب كل صفوف الديتيل، فنحتاج EXISTS
    //             للتأكد أن "هذا الطلب فيه الموظف المطلوب".

    /**
     * فلاتر detail (الافتراضي) — تُطبَّق على D مباشرة.
     *   $f: ['branch_no','emp_no','the_month','req_type','status']
     */
    private function _build_where_detail($f)
    {
        $w = '';
        if (!empty($f['branch_no'])) $w .= " AND EMP_PKG.GET_EMP_BRANCH(D.EMP_NO) = '{$f['branch_no']}' ";
        if (!empty($f['emp_no']))    $w .= " AND D.EMP_NO   = '{$f['emp_no']}' ";
        if (!empty($f['the_month'])) $w .= " AND M.THE_MONTH = '{$f['the_month']}' ";
        if (!empty($f['req_type']))  $w .= " AND M.REQ_TYPE  = '{$f['req_type']}' ";
        if (!empty($f['pay_type']))  $w .= " AND M.PAY_TYPE  = '{$f['pay_type']}' ";  // 🆕 البيان (المستوى الثاني)
        if (!empty($f['note'])) {  // 🆕 بحث في ملاحظات الطلب
            $nf = str_replace("'", "''", trim($f['note']));
            $w .= " AND UPPER(M.NOTE) LIKE UPPER('%{$nf}%') ";
        }
        // 🆕 فلتر الرقابة (متّسق مع master)
        if (isset($f['raqaba']) && $f['raqaba'] !== '' && $f['raqaba'] !== '-1') {
            $rq = (int)$f['raqaba'];
            if ($rq === 0) {
                // بانتظار الرقابة = معتمد محاسبياً (1 أو 3) ولسا ما تقرر رقابياً — استثنِ المسودات
                $w .= " AND NVL(M.RAQABA_STATUS, 0) = 0 AND M.STATUS IN (1, 3) ";
            } else {
                $w .= " AND NVL(M.RAQABA_STATUS, 0) = {$rq} ";
            }
        }
        $w .= $this->_status_clause($f['status'] ?? '');
        return $w;
    }

    /**
     * فلاتر master — فلاتر الموظف تُلَفّ بـ EXISTS لأن الطلب يحتوي عدة موظفين.
     *   $f: نفس مفاتيح _build_where_detail
     */
    private function _build_where_master($f)
    {
        $w = '';
        if (!empty($f['branch_no'])) {
            $w .= " AND EXISTS (SELECT 1 FROM GFC.PAYMENT_REQ_DETAIL_TB DD
                                 WHERE DD.REQ_ID = M.REQ_ID
                                   AND EMP_PKG.GET_EMP_BRANCH(DD.EMP_NO) = '{$f['branch_no']}') ";
        }
        if (!empty($f['emp_no'])) {
            $w .= " AND EXISTS (SELECT 1 FROM GFC.PAYMENT_REQ_DETAIL_TB DD
                                 WHERE DD.REQ_ID = M.REQ_ID
                                   AND DD.EMP_NO = '{$f['emp_no']}') ";
        }
        // فلاتر الطلب نفسه (على M مباشرة) — مش محتاجة EXISTS
        if (!empty($f['the_month'])) $w .= " AND M.THE_MONTH = '{$f['the_month']}' ";
        if (!empty($f['req_type']))  $w .= " AND M.REQ_TYPE  = '{$f['req_type']}' ";
        if (!empty($f['pay_type']))  $w .= " AND M.PAY_TYPE  = '{$f['pay_type']}' ";  // 🆕 البيان (المستوى الثاني)
        if (!empty($f['note'])) {  // 🆕 بحث في ملاحظات الطلب
            $nf = str_replace("'", "''", trim($f['note']));
            $w .= " AND UPPER(M.NOTE) LIKE UPPER('%{$nf}%') ";
        }
        // 🆕 فلتر حالة الرقابة (0=بانتظار، 1=معتمد، 2=مرفوض)
        if (isset($f['raqaba']) && $f['raqaba'] !== '' && $f['raqaba'] !== '-1') {
            $rq = (int)$f['raqaba'];
            if ($rq === 0) {
                // بانتظار الرقابة = معتمد محاسبياً (1 أو 3) ولسا ما تقرر رقابياً — استثنِ المسودات
                $w .= " AND NVL(M.RAQABA_STATUS, 0) = 0 AND M.STATUS IN (1, 3) ";
            } else {
                $w .= " AND NVL(M.RAQABA_STATUS, 0) = {$rq} ";
            }
        }
        $w .= $this->_status_clause($f['status'] ?? '');
        return $w;
    }

    /**
     * يبني جزء الـ STATUS (يدعم قيمة واحدة أو CSV مثل "0,1,3").
     */
    private function _status_clause($status)
    {
        if ($status === null || $status === '') return '';
        if (strpos($status, ',') !== false) {
            return " AND M.STATUS IN ({$status}) ";
        }
        return " AND M.STATUS = '{$status}' ";
    }

    function get_page($page = 1, $branch_no = -1, $the_month = -1, $emp_no = -1, $status = -1)
    {
        $this->load->library('pagination');

        // ───── (1) اقرأ الفلاتر من POST/GET ─────
        $filters = [
            'branch_no' => $this->check_vars($branch_no, 'branch_no'),
            'emp_no'    => $this->check_vars($emp_no,    'emp_no'),
            'the_month' => $this->check_vars($the_month, 'the_month'),
            'req_type'  => $this->check_vars(-1,         'req_type'),
            'pay_type'  => $this->check_vars(-1,         'pay_type'),
            'status'    => $this->check_vars($status,    'status'),
        ];
        // 🆕 فلتر الرقابة — قراءة مباشرة (check_vars يعامل '0' كفارغ)
        $raqaba_f = $this->input->post('raqaba');
        if ($raqaba_f === null) $raqaba_f = $this->input->get('raqaba');
        $filters['raqaba'] = ($raqaba_f === '' || $raqaba_f === null) ? '-1' : $raqaba_f;

        // 🆕 فلتر الملاحظات — نص حر (بحث LIKE في M.NOTE)
        $filters['note'] = trim((string)($this->input->post('note') ?: $this->input->get('note') ?: ''));

        // المستخدم خارج المقر الرئيسي → مفروض يشوف فرعه فقط (override للـ branch filter)
        if ($this->user->branch != 1) {
            $filters['branch_no'] = $this->user->branch;
        }

        // ───── (2) ابني WHERE لكل وضع عرض ─────
        // detail: فلاتر مباشرة على D (الموظف) لأن كل صف بيمثل موظف
        // master: فلاتر تخص الموظف لازم تتلف بـ EXISTS عشان تطابق أي موظف في الطلب
        $where_detail = $this->_build_where_detail($filters);
        $where_master = $this->_build_where_master($filters);

        $view_mode = $this->input->post('view_mode') ?: $this->input->get('view_mode') ?: 'detail';

        // ───── (3) جلب الإجماليات (chips) — دائماً من detail لأن الإجمالي = مجموع كل الصرف ─────
        $totals = $this->{$this->MODEL_NAME}->get_totals($where_detail);

        // ───── (4) عَدّ الصفوف للعرض الحالي ─────
        if ($view_mode === 'master') {
            // عدد الطلبات الفريدة (مش الديتيل)
            // 🆕 العدّ من الماستر مباشرة بنفس فلتر القائمة ($where_master) — بدون JOIN على الديتيل
            //    وإلا الطلبات بصفر موظفين (مسودة) تُستثنى من العدّ بينما تظهر في القائمة (تضارب 13 vs 15)
            $cnt_rs = $this->get_table_count(
                "(SELECT M.REQ_ID
                    FROM GFC.PAYMENT_REQ_TB M
                   WHERE 1=1 {$where_master}) X WHERE 1=1"
            );
            $total_rows = (is_array($cnt_rs) && count($cnt_rs) > 0) ? intval($cnt_rs[0]['NUM_ROWS']) : 0;
        } else {
            $total_rows = (int)($totals['count'] ?? 0);
        }

        $config['base_url']         = base_url($this->PAGE_URL);
        $config['use_page_numbers'] = TRUE;
        $config['total_rows']       = $total_rows;
        $config['per_page']         = $this->page_size;
        $config['num_links']        = 20;
        $config['cur_page']         = $page;
        $config['full_tag_open']    = '<div class="pagination-container"><ul class="pagination">';
        $config['full_tag_close']   = '</ul></div>';
        $config['first_tag_open']   = $config['last_tag_open'] = $config['next_tag_open'] = $config['prev_tag_open'] = $config['num_tag_open'] = '<li>';
        $config['first_tag_close']  = $config['last_tag_close'] = $config['next_tag_close'] = $config['prev_tag_close'] = $config['num_tag_close'] = '</li>';
        $config['cur_tag_open']     = '<li class="active"><span><b>';
        $config['cur_tag_close']    = '</b></span></li>';

        $this->pagination->initialize($config);

        $offset = ($page - 1) * $config['per_page'];
        $row    = $page * $config['per_page'];

        if ($view_mode === 'master') {
            $data['page_rows'] = $this->{$this->MODEL_NAME}->get_list_master($where_master, $offset, $row);
        } else {
            $data['page_rows'] = $this->{$this->MODEL_NAME}->get_list($where_detail, $offset, $row);
        }

        $data['offset']     = $offset + 1;
        $data['page']       = $page;
        $data['totals']     = $totals;
        $data['total_rows'] = $total_rows;
        $data['view_mode']  = $view_mode;

        $this->load->view('payment_req_page', $data);
    }

    // ==================== CREATE (master only) ====================
    function create()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->_post_validation(false);
            // فحص التكرار يتم داخل Oracle procedure — PAYMENT_REQ_INSERT
            $res = $this->{$this->MODEL_NAME}->create($this->_postedData('create'), $this->_include_allowances(), $this->_type2_remaining_mode());
            if (is_numeric($res) && intval($res) > 0) { echo intval($res); }
            else { $this->print_error($res); }
        } else {
            $data['title']    = 'صرف رواتب - إنشاء طلب';
            $data['isCreate'] = true;
            $data['action']   = 'index';
            $data['content']  = 'payment_req_show';
            $this->_lookup($data);
            $this->load->view('template/template1', $data);
        }
    }

    // ==================== GET ====================
    function get($id)
    {
        $result = $this->{$this->MODEL_NAME}->get($id);
        if (!(count($result) == 1)) die('get');

        $data['master_tb_data'] = $result;
        $data['detail_rows']    = $this->{$this->MODEL_NAME}->get_details($id);
        $data['log_rows']       = $this->{$this->MODEL_NAME}->get_log($id);
        $data['isCreate']       = false;
        $data['can_edit']       = (isset($result[0]['STATUS']) && $result[0]['STATUS'] == 0) ? 1 : 0;
        $data['action']         = 'edit';
        $data['content']        = 'payment_req_show';
        $data['title']          = 'صرف رواتب - تفاصيل الطلب';

        // معاينة التوزيع: لكل موظف، حساباته والمبلغ المتوقّع لكل حساب
        // (يعرض inline في جدول الموظفين)
        $this->load->model('payment_accounts/payment_accounts_model');
        $preview_rows = $this->payment_accounts_model->accounts_preview_by_req((int)$id);
        if (is_array($preview_rows)) {
            array_walk_recursive($preview_rows, function (&$val) {
                if (is_string($val)) { $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
            });
        }
        // نُجمّع الـ rows حسب EMP_NO + نحسب summary لكل موظف
        $accounts_map  = []; // emp_no => list of accounts
        $emp_summary   = []; // emp_no => {is_active, acc_cnt, inactive_acc_cnt, benef_total, benef_linked, alloc_total}
        foreach ((array)$preview_rows as $r) {
            $eno = (int)($r['EMP_NO'] ?? 0);
            if (!$eno) continue;
            if (!isset($accounts_map[$eno])) $accounts_map[$eno] = [];
            $accounts_map[$eno][] = $r;
            // summary مرة واحدة لكل موظف (الـ aggregate columns ثابتة عبر صفوفه)
            if (!isset($emp_summary[$eno])) {
                $emp_summary[$eno] = [
                    'is_active'         => (int)($r['EMP_IS_ACTIVE']     ?? 1),
                    'display_status'    => (int)($r['EMP_DISPLAY_STATUS'] ?? 1),  // 1=فعّال, 0=متقاعد, 2=متوفى, 4=حساب مغلق
                    'req_amount'        => (float)($r['REQ_AMOUNT']      ?? 0),
                    'acc_cnt'           => (int)($r['ACC_CNT']           ?? 0),
                    'inactive_acc_cnt'  => (int)($r['INACTIVE_ACC_CNT']  ?? 0),
                    'benef_total'       => (int)($r['BENEF_TOTAL']       ?? 0),
                    'benef_linked'      => (int)($r['BENEF_LINKED']      ?? 0),
                    'is_overage'        => (int)($r['IS_OVERAGE']        ?? 0),
                    // 🆕 تفاصيل التجاوز — للعرض في الواجهة لو IS_OVERAGE=1
                    'overage_cause'     => (int)($r['OVERAGE_CAUSE']     ?? 0),  // 1=ثابتة، 2=نسب، 3=معاً
                    'overage_fixed_sum' => (float)($r['OVERAGE_FIXED_SUM'] ?? 0),
                    'overage_pct_sum'   => (float)($r['OVERAGE_PCT_SUM']   ?? 0),
                    'overage_rest_cnt'  => (int)($r['OVERAGE_REST_CNT']  ?? 0),
                    'alloc_total'       => 0,
                    'desired_total'     => 0,
                    'inactive_reasons'  => [],
                ];
            }
            // المبلغ المُوزّع (للحسابات النشطة فقط) — هذا ما سيتم صرفه فعلاً
            if (!empty($r['ACC_ID']) && (int)($r['ACC_IS_ACTIVE'] ?? 1) === 1) {
                $emp_summary[$eno]['alloc_total']   += (float)($r['ALLOC_AMOUNT']  ?? 0);
                $emp_summary[$eno]['desired_total'] += (float)($r['DESIRED_AMT']   ?? 0);
            }
            // أسباب التجميد للحسابات الموقوفة
            if (!empty($r['ACC_ID']) && (int)($r['ACC_IS_ACTIVE'] ?? 1) === 0 && !empty($r['INACTIVE_REASON_NAME'])) {
                $reason = $r['INACTIVE_REASON_NAME'];
                if (!in_array($reason, $emp_summary[$eno]['inactive_reasons'])) {
                    $emp_summary[$eno]['inactive_reasons'][] = $reason;
                }
            }
        }
        $data['accounts_map'] = $accounts_map;
        $data['emp_summary']  = $emp_summary;

        // 🆕 ملفات الإكسل المستوردة لهذا الطلب (لو الطلب أُنشئ عبر استيراد)
        $data['import_files'] = [];
        $rs_id = (int)$id;   // ← الـ id متاح كـ parameter للـ method
        if ($rs_id > 0) {
            $this->load->model('attachments/attachment_model');
            $imp = $this->attachment_model->get_list($rs_id, 'payment_req_import', 0);
            $data['import_files'] = is_array($imp) ? $imp : [];
        }

        $this->_lookup($data);
        $this->load->view('template/template1', $data);
    }

    // ==================== EDIT (master only) ====================
    function edit()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->_post_validation(true);
            $res = $this->{$this->MODEL_NAME}->edit($this->_postedData());
            // النتيجة: "1" = تم | "1|رسالة" = تم مع تنبيه | "2|رسالة" = تغيير نوع
            $parts = explode('|', $res);
            if ($parts[0] === '1' || $parts[0] === '2') {
                echo $res;
            } else {
                $this->print_error($res);
            }
        }
    }

    // ==================== DETAIL CRUD (AJAX) ====================
    function detail_add()
    {
        $req_id     = $this->p_req_id;
        $emp_no     = $this->p_emp_no;
        try { $req_amount = $this->_requested_amount($this->p_req_amount); }
        catch (\InvalidArgumentException $e) { echo json_encode(['ok' => false, 'msg' => $e->getMessage()]); return; }
        $note       = $this->p_note ?: '';
        if (!$req_id) { echo json_encode(['ok' => false, 'msg' => 'REQ_ID required']); return; }
        if (!$emp_no) { echo json_encode(['ok' => false, 'msg' => 'EMP_NO required']); return; }
        // ⚠️ فحص التكرار يتم داخل DETAIL_INSERT (proc) — وهو يحترم استثناء نوع 5
        //    (V_TYPE <> C_REQ_TYPE_BENEFITS). لا نكرره هنا عبر emp_in_other_request/
        //    CHECK_DUPLICATE_EMP لأنها لا تستثني نوع 5 فتمنع الإضافة اليدوية خطأً.
        $res = $this->{$this->MODEL_NAME}->detail_add($req_id, $emp_no, $req_amount, $note);
        if (is_numeric($res) && intval($res) > 0) {
            echo json_encode(['ok' => true, 'detail_id' => intval($res)]);
        } else {
            echo json_encode(['ok' => false, 'msg' => $res]);
        }
    }

    function detail_update()
    {
        $detail_id  = $this->p_detail_id;
        try { $req_amount = $this->_requested_amount($this->p_req_amount); }
        catch (\InvalidArgumentException $e) { echo json_encode(['ok' => false, 'msg' => $e->getMessage()]); return; }
        $note       = $this->p_note ?: '';
        if (!$detail_id) { echo json_encode(['ok' => false, 'msg' => 'DETAIL_ID required']); return; }
        $res = $this->{$this->MODEL_NAME}->detail_update($detail_id, $req_amount, $note);
        echo json_encode(['ok' => ($res == '1'), 'msg' => $res]);
    }

    function detail_preview_single()
    {
        $req_id     = $this->p_req_id;
        $emp_no     = $this->p_emp_no;
        try { $req_amount = $this->_requested_amount($this->p_req_amount); }
        catch (\InvalidArgumentException $e) { echo json_encode(['ok' => false, 'msg' => $e->getMessage()]); return; }
        if (!$req_id || !$emp_no) { echo json_encode(['ok' => false, 'msg' => 'بيانات ناقصة']); return; }
        $res = $this->{$this->MODEL_NAME}->detail_preview_single($req_id, $emp_no, $req_amount);
        $parts = explode('|', (string)$res);
        if ($parts[0] === 'ERR') {
            echo json_encode(['ok' => false, 'msg' => $parts[1] ?? 'خطأ']);
        } else {
            $storedType = null;
            if (count($parts) === 5) {
                $master = $this->{$this->MODEL_NAME}->get($req_id);
                $storedType = (int)($master[0]['REQ_TYPE'] ?? 0);
            }
            try { echo json_encode($this->_parse_detail_preview($parts, $storedType)); }
            catch (\UnexpectedValueException $e) { echo json_encode(['ok' => false, 'msg' => $e->getMessage()]); }
        }
    }

    private function _parse_detail_preview($parts, $storedType = null)
    {
        // Production type 5 intentionally returns only the employee name and zeros.
        if ($storedType === 5 && count($parts) === 5 && $parts[2] === '') {
            foreach ([0, 1, 3] as $index) {
                if (!is_numeric($parts[$index]) || (float)$parts[$index] !== 0.0) {
                    throw new \UnexpectedValueException('بيانات معاينة الاستحقاقات غير صحيحة.');
                }
            }
            return ['ok' => true, 'calc_amount' => 0.0, 'dues_avail' => 0.0, 'limit_flag' => '',
                'sal_323' => 0.0, 'emp_name' => $parts[4], 'monthly_components_applicable' => false,
                'allowance_325_amount' => null, 'original_323' => null, 'prior_base_amount' => null,
                'remaining_base' => null, 'available_325' => null, 'base_payment' => null,
                'remaining_after' => null, 'type2_remaining_mode' => 0, 'include_allowances' => 0];
        }
        $numeric = [0 => 'calc_amount', 1 => 'dues_avail', 3 => 'sal_323',
            5 => 'allowance_325_amount', 6 => 'original_323', 7 => 'prior_base_amount',
            8 => 'remaining_base', 9 => 'available_325', 10 => 'base_payment', 11 => 'remaining_after'];
        $result = ['ok' => true, 'limit_flag' => $parts[2] ?? '', 'emp_name' => $parts[4] ?? ''];
        foreach ($numeric as $index => $name) {
            if (!isset($parts[$index]) || !is_numeric($parts[$index]) || !is_finite((float)$parts[$index])) {
                throw new \UnexpectedValueException('بيانات المعاينة غير مكتملة؛ تحقق من تحديث حزمة الصرف.');
            }
            $result[$name] = (float)$parts[$index];
        }
        foreach ([12 => 'type2_remaining_mode', 13 => 'include_allowances'] as $index => $name) {
            if (!isset($parts[$index]) || !in_array((string)$parts[$index], ['0', '1'], true)) {
                throw new \UnexpectedValueException('أساس المعاينة غير متوفر؛ تحقق من تحديث حزمة الصرف.');
            }
            $result[$name] = (int)$parts[$index];
        }
        if ($result['calc_amount'] < 0 || $result['allowance_325_amount'] < 0 || $result['base_payment'] < 0
            || round($result['calc_amount'] * 100) !== round(($result['base_payment'] + $result['allowance_325_amount']) * 100)) {
            throw new \UnexpectedValueException('مكوّنات مبلغ المعاينة غير متطابقة؛ أعد المعاينة بعد التحقق من حزمة الصرف.');
        }
        return $result;
    }

    function detail_delete()
    {
        $detail_id = $this->p_detail_id;
        if (!$detail_id) { echo json_encode(['ok' => false, 'msg' => 'DETAIL_ID required']); return; }
        $res = $this->{$this->MODEL_NAME}->detail_delete($detail_id);
        echo json_encode(['ok' => ($res == '1'), 'msg' => $res]);
    }

    // 🆕 حذف/إلغاء اعتماد جماعي لعدة موظفين من نفس الطلب
    function detail_delete_bulk()
    {
        $ids_raw = $this->input->post('detail_ids') ?: $this->input->get('detail_ids');
        $ids_raw = preg_replace('/[^0-9,]/', '', (string)$ids_raw);
        $ids_raw = trim($ids_raw, ',');
        if ($ids_raw === '') {
            echo json_encode(['ok' => false, 'msg' => 'لا يوجد موظفين محدّدين']); return;
        }
        $ids = array_filter(array_map('intval', explode(',', $ids_raw)), function($v){ return $v > 0; });
        if (count($ids) === 0) {
            echo json_encode(['ok' => false, 'msg' => 'IDs غير صحيحة']); return;
        }

        $ok_count = 0;
        $fail = [];
        foreach ($ids as $did) {
            try {
                $res = $this->{$this->MODEL_NAME}->detail_delete($did);
                if ($res == '1') {
                    $ok_count++;
                } else {
                    $fail[] = ['detail_id' => $did, 'msg' => mb_substr((string)$res, 0, 200)];
                }
            } catch (Exception $e) {
                $fail[] = ['detail_id' => $did, 'msg' => mb_substr($e->getMessage(), 0, 200)];
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'ok'         => true,
            'ok_count'   => $ok_count,
            'fail_count' => count($fail),
            'fail'       => $fail,
            'msg'        => "تم حذف/إلغاء اعتماد {$ok_count} موظف" . (count($fail) > 0 ? " (فشل " . count($fail) . ")" : ''),
        ]);
    }

    // =========================================================
    // DETAIL — تعيين override توزيع لسطر معين (قبل الاحتساب)
    // POST: detail_id, provider_type (NULL/1/2), acc_id (NULL أو رقم حساب)
    // =========================================================
    // 🆕 public_ prefix → لا تتطلب صلاحيات menu (متاحة لمن يفتح صفحة الطلب)
    /**
     * 🆕 فحص أهلية المحدّدين قبل تطبيق "بنك" أو "محفظة" bulk:
     * يفحص هل كل موظف عنده على الأقل حساب نشط من النوع المطلوب.
     * يستخدم في bulkSetOverride لإظهار من ليس مؤهلاً.
     *
     * Input (POST): detail_ids (CSV)، provider_type (1=بنك, 2=محفظة)
     * Output: { ok, eligible:[detail_id,...], ineligible:[{detail_id, emp_no, emp_name}] }
     */
    function public_bulk_eligibility_check()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $req_id  = (int)$this->input->post('req_id');
            $ids_raw = trim((string)$this->input->post('detail_ids'));
            $pt      = (int)$this->input->post('provider_type');
            if (!$req_id || !$ids_raw || !in_array($pt, [1, 2], true)) {
                echo json_encode(['ok' => false, 'msg' => 'بارامترات ناقصة']);
                return;
            }
            $ids = array_filter(array_map('intval', explode(',', $ids_raw)));
            if (empty($ids)) {
                echo json_encode(['ok' => false, 'msg' => 'لا توجد IDs صالحة']);
                return;
            }

            // Build detail_id → {emp_no, emp_name} map (from req details — single query)
            $details = $this->{$this->MODEL_NAME}->get_details($req_id);
            $map = [];
            if (is_array($details)) {
                foreach ($details as $d) {
                    $did = (int)($d['DETAIL_ID'] ?? 0);
                    if ($did) {
                        $map[$did] = [
                            'emp_no'   => (int)($d['EMP_NO'] ?? 0),
                            'emp_name' => (string)($d['EMP_NAME'] ?? ''),
                        ];
                    }
                }
            }

            // Cache emp_no → has_provider_type لتجنب الاستعلامات المكررة لنفس الموظف
            $emp_has = [];

            $eligible = []; $ineligible = [];
            foreach ($ids as $did) {
                $info = $map[$did] ?? null;
                if (!$info) {
                    $ineligible[] = ['detail_id' => $did, 'emp_no' => 0, 'emp_name' => '(غير معروف)'];
                    continue;
                }
                $emp_no   = $info['emp_no'];
                $emp_name = $info['emp_name'];

                if (!isset($emp_has[$emp_no])) {
                    $accs = $this->{$this->MODEL_NAME}->emp_accounts_list($emp_no, 1);
                    $has = false;
                    if (is_array($accs)) {
                        foreach ($accs as $a) {
                            if ((int)($a['PROVIDER_TYPE'] ?? 0) === $pt) { $has = true; break; }
                        }
                    }
                    $emp_has[$emp_no] = $has;
                }

                if ($emp_has[$emp_no]) $eligible[] = $did;
                else                   $ineligible[] = ['detail_id' => $did, 'emp_no' => $emp_no, 'emp_name' => $emp_name];
            }

            // ترتيب أبجدي للمعروضين (لإلحاق إذا كثر العدد)
            usort($ineligible, function($a, $b){ return strcmp($a['emp_name'] ?? '', $b['emp_name'] ?? ''); });

            echo json_encode([
                'ok'         => true,
                'eligible'   => $eligible,
                'ineligible' => $ineligible,
                'pt_label'   => $pt === 2 ? 'محفظة' : 'بنك',
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode(['ok' => false, 'msg' => 'خطأ: ' . $e->getMessage()]);
        }
    }

    /**
     * 🆕 إصلاح ذكي — يبني خطة لكل تفصيل بناءً على استراتيجية يختارها المحاسب
     *
     * Strategies:
     *   - 'wallet_first' (default): عنده محفظة → محفظة. عنده بنك فقط → بنك.
     *   - 'bank_first': عنده بنك → بنك. عنده محفظة فقط → محفظة.
     *   - 'default': يمسح override (provider_type=null) → split عادي حسب حسابات الموظف
     *
     * Input (POST): req_id، detail_ids (CSV — اختياري)، strategy
     * Output: {ok, plan: [...], summary: {to_wallet, to_bank, to_default, no_change, no_accounts}}
     * action: "to_wallet" | "to_bank" | "to_default" | "no_change" | "no_accounts"
     */
    function public_bulk_smart_plan()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $req_id   = (int)$this->input->post('req_id');
            $ids_raw  = trim((string)$this->input->post('detail_ids'));
            $strategy = trim((string)$this->input->post('strategy'));
            if ($strategy === '' || !in_array($strategy, ['wallet_first', 'bank_first', 'default'], true)) {
                $strategy = 'wallet_first';
            }
            if (!$req_id) {
                echo json_encode(['ok' => false, 'msg' => 'req_id مطلوب']);
                return;
            }

            $details = $this->{$this->MODEL_NAME}->get_details($req_id);
            if (!is_array($details)) $details = [];

            $filter_ids = null;
            if ($ids_raw !== '') {
                $filter_ids = array_flip(array_filter(array_map('intval', explode(',', $ids_raw))));
            }

            $emp_caps = []; // emp_no => ['has_wallet'=>bool, 'has_bank'=>bool]
            $plan = [];
            $sum  = [
                'to_wallet'   => 0,
                'to_bank'     => 0,
                'to_default'  => 0,
                'no_change'   => 0,
                'no_accounts' => 0,
            ];

            foreach ($details as $d) {
                $did = (int)($d['DETAIL_ID'] ?? 0);
                if (!$did) continue;
                if ($filter_ids !== null && !isset($filter_ids[$did])) continue;
                if ((int)($d['DETAIL_STATUS'] ?? 0) === 9) continue;

                $emp_no   = (int)($d['EMP_NO'] ?? 0);
                $emp_name = (string)($d['EMP_NAME'] ?? '');
                $cur_pt   = $d['OVERRIDE_PROVIDER_TYPE'] ?? null;
                if ($cur_pt !== null && $cur_pt !== '') $cur_pt = (int)$cur_pt; else $cur_pt = null;

                // قرر الـ proposed_pt حسب الاستراتيجية
                $proposed = null;   // null = مسح override (افتراضي/split)
                $skip     = false;

                if ($strategy === 'default') {
                    // ارجع للسلوك الافتراضي (split على كل الحسابات)
                    $proposed = null;
                } else {
                    // استراتيجية wallet_first أو bank_first → محتاج حسابات الموظف
                    if (!isset($emp_caps[$emp_no])) {
                        $accs = $this->{$this->MODEL_NAME}->emp_accounts_list($emp_no, 1);
                        $hw = false; $hb = false;
                        if (is_array($accs)) {
                            foreach ($accs as $a) {
                                $pt = (int)($a['PROVIDER_TYPE'] ?? 0);
                                if ($pt === 2) $hw = true;
                                elseif ($pt === 1) $hb = true;
                            }
                        }
                        $emp_caps[$emp_no] = ['has_wallet' => $hw, 'has_bank' => $hb];
                    }
                    $caps = $emp_caps[$emp_no];

                    if (!$caps['has_wallet'] && !$caps['has_bank']) {
                        $skip = true;   // لا حسابات أصلاً
                    } elseif ($strategy === 'wallet_first') {
                        $proposed = $caps['has_wallet'] ? 2 : 1;
                    } else { // bank_first
                        $proposed = $caps['has_bank']   ? 1 : 2;
                    }
                }

                // قرر الـ action
                $action = 'no_change';
                if ($skip) {
                    $action = 'no_accounts';
                    $sum['no_accounts']++;
                } elseif ($proposed === $cur_pt) {
                    $sum['no_change']++;
                } elseif ($proposed === null) {
                    $action = 'to_default';
                    $sum['to_default']++;
                } elseif ($proposed === 2) {
                    $action = 'to_wallet';
                    $sum['to_wallet']++;
                } else {
                    $action = 'to_bank';
                    $sum['to_bank']++;
                }

                $plan[] = [
                    'detail_id'    => $did,
                    'emp_no'       => $emp_no,
                    'emp_name'     => $emp_name,
                    'current_pt'   => $cur_pt,
                    'proposed_pt'  => $proposed,
                    'action'       => $action,
                ];
            }

            echo json_encode([
                'ok'       => true,
                'strategy' => $strategy,
                'plan'     => $plan,
                'summary'  => $sum,
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode(['ok' => false, 'msg' => 'خطأ: ' . $e->getMessage()]);
        }
    }

    function public_detail_set_override()
    {
        // ⚠️ ضمان response JSON دائماً (حتى عند أي خطأ) — يمنع SyntaxError في الـ frontend
        header('Content-Type: application/json; charset=utf-8');
        try {
            $detail_id     = (int)$this->input->post('detail_id');
            $provider_type = $this->input->post('provider_type');
            $acc_id        = $this->input->post('acc_id');

            // normalize: empty string → null
            if ($provider_type === '' || $provider_type === '0') $provider_type = null;
            if ($acc_id === '' || $acc_id === '0')               $acc_id        = null;

            if (!$detail_id) {
                echo json_encode(['ok' => false, 'msg' => 'DETAIL_ID مطلوب']);
                return;
            }

            $res = $this->{$this->MODEL_NAME}->detail_set_override($detail_id, $provider_type, $acc_id);
            echo json_encode(['ok' => ($res == '1'), 'msg' => $res]);
        } catch (\Exception $e) {
            log_message('error', 'detail_set_override exception: ' . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'خطأ في السيرفر: ' . $e->getMessage()]);
        }
    }

    // =========================================================
    // توزيع مخصّص للطلب على عدة حسابات بنِسَب
    // =========================================================
    function public_detail_set_split()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $detail_id = (int)$this->input->post('detail_id');
            $splits    = trim((string)$this->input->post('splits')); // 'acc:pct,acc:pct'
            if (!$detail_id) {
                echo json_encode(['ok' => false, 'msg' => 'DETAIL_ID مطلوب']);
                return;
            }
            $res = $this->{$this->MODEL_NAME}->detail_set_split($detail_id, $splits);
            echo json_encode(['ok' => ($res == '1'), 'msg' => $res]);
        } catch (\Exception $e) {
            log_message('error', 'detail_set_split exception: ' . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'خطأ في السيرفر: ' . $e->getMessage()]);
        }
    }

    function public_detail_clear_split()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $detail_id = (int)$this->input->post('detail_id');
            if (!$detail_id) {
                echo json_encode(['ok' => false, 'msg' => 'DETAIL_ID مطلوب']);
                return;
            }
            $res = $this->{$this->MODEL_NAME}->detail_clear_split($detail_id);
            echo json_encode(['ok' => ($res == '1'), 'msg' => $res]);
        } catch (\Exception $e) {
            log_message('error', 'detail_clear_split exception: ' . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'خطأ في السيرفر: ' . $e->getMessage()]);
        }
    }

    // =========================================================
    // EMP ACCOUNTS — للـ override modal (بدون batch_id)
    // GET/POST: emp_no
    // =========================================================
    function public_emp_accounts_json()
    {
        $emp_no = (int)$this->input->get_post('emp_no');
        if (!$emp_no) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'EMP_NO مطلوب', 'data' => []]);
            return;
        }
        $rows = $this->{$this->MODEL_NAME}->emp_accounts_list($emp_no, 1);
        if (is_array($rows)) {
            array_walk_recursive($rows, function (&$val) {
                if (is_string($val)) { $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
            });
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'data' => is_array($rows) ? $rows : []]);
    }

    function detail_list()
    {
        $req_id = $this->p_req_id ?: $this->input->get('req_id');
        if (!$req_id) { echo json_encode(['ok' => false]); return; }

        $rows   = $this->{$this->MODEL_NAME}->get_details($req_id);
        $master = $this->{$this->MODEL_NAME}->get($req_id);
        $logs   = $this->{$this->MODEL_NAME}->get_log($req_id);

        $master_info = (is_array($master) && count($master) > 0) ? $master[0] : [];
        $total = 0; $cnt = 0;
        if (is_array($rows)) {
            foreach ($rows as $r) {
                if ((int)($r['DETAIL_STATUS'] ?? 0) != 9) {
                    $total += (float)($r['REQ_AMOUNT'] ?? 0);
                    $cnt++;
                }
            }
        }

        echo json_encode([
            'ok'     => true,
            'data'   => is_array($rows) ? $rows : [],
            'master' => [
                'STATUS'       => $master_info['STATUS'] ?? 0,
                'STATUS_NAME'  => $master_info['STATUS_NAME'] ?? '',
                'EMP_COUNT'    => $cnt,
                'TOTAL_AMOUNT' => $total,
            ],
            'logs'   => is_array($logs) ? $logs : [],
        ]);
    }

    // ==================== APPROVE ====================
    function approve()
    {
        if (!HaveAccess(base_url('payment_req/payment_req/approve'))) { echo 'ليس لديك صلاحية'; return; }
        $req_id = $this->p_req_id;
        if (!$req_id) { echo 'معرف الطلب مطلوب'; return; }
        echo $this->{$this->MODEL_NAME}->approve($req_id);
    }

    // ==================== PAY ====================
    function do_pay()
    {
        if (!HaveAccess(base_url('payment_req/payment_req/do_pay'))) { echo 'ليس لديك صلاحية'; return; }
        $req_id = $this->p_req_id;
        if (!$req_id) { echo 'معرف الطلب مطلوب'; return; }
        // 🛡️ بوابة الرقابة: لا صرف مباشر إلا بعد اعتماد الرقابة
        $pending = $this->{$this->MODEL_NAME}->raqaba_pending_for_reqs((string)$req_id);
        if (!empty($pending)) { echo 'لا يمكن الصرف — الطلب لم يُعتمد من الرقابة'; return; }
        echo $this->{$this->MODEL_NAME}->pay($req_id);
    }

    // ==================== DELETE (Cancel) ====================
    function delete()
    {
        // إلغاء الطلب (STATUS=9 مع حفظ السجل) — صلاحية "صرف رواتب - إلغاء طلب"
        if (!HaveAccess(base_url('payment_req/payment_req/delete'))) { echo 'ليس لديك صلاحية'; return; }
        $req_id      = $this->p_req_id;
        $cancel_note = $this->p_cancel_note;
        if (!$req_id) { echo 'معرف الطلب مطلوب'; return; }
        echo $this->{$this->MODEL_NAME}->cancel($req_id, ($cancel_note ?: 'Canceled'));
    }

    // ==================== UNAPPROVE (إلغاء الاعتماد) ====================
    function unapprove()
    {
        if (!HaveAccess(base_url('payment_req/payment_req/unapprove'))) {
            echo json_encode(['ok' => false, 'msg' => 'ليس لديك صلاحية']); return;
        }
        $req_id = $this->p_req_id;
        $note   = $this->p_note ?: '';
        if (!$req_id) { echo json_encode(['ok' => false, 'msg' => 'معرف الطلب مطلوب']); return; }
        $res = $this->{$this->MODEL_NAME}->unapprove($req_id, $note);
        header('Content-Type: application/json');
        echo json_encode(['ok' => ($res == '1'), 'msg' => ($res == '1') ? 'تم إلغاء الاعتماد' : $res]);
    }

    // ==================== 🆕 اعتماد الرقابة ====================
    function raqaba_approve()
    {
        if (!HaveAccess(base_url('payment_req/payment_req/raqaba_approve'))) {
            echo json_encode(['ok' => false, 'msg' => 'ليس لديك صلاحية الرقابة']); return;
        }
        $req_id = $this->p_req_id;
        $note   = $this->p_note ?: '';
        if (!$req_id) { echo json_encode(['ok' => false, 'msg' => 'معرف الطلب مطلوب']); return; }
        $res = $this->{$this->MODEL_NAME}->raqaba_approve($req_id, $note);
        header('Content-Type: application/json');
        echo json_encode(['ok' => ($res == '1'), 'msg' => ($res == '1') ? 'تم اعتماد الرقابة' : $res]);
    }

    // ==================== 🆕 رفض الرقابة ====================
    function raqaba_reject()
    {
        if (!HaveAccess(base_url('payment_req/payment_req/raqaba_reject'))) {
            echo json_encode(['ok' => false, 'msg' => 'ليس لديك صلاحية الرقابة']); return;
        }
        $req_id = $this->p_req_id;
        $note   = trim((string)($this->p_note ?: $this->input->post('note')));
        if (!$req_id) { echo json_encode(['ok' => false, 'msg' => 'معرف الطلب مطلوب']); return; }
        if ($note === '') { echo json_encode(['ok' => false, 'msg' => 'سبب الرفض مطلوب']); return; }
        $res = $this->{$this->MODEL_NAME}->raqaba_reject($req_id, $note);
        header('Content-Type: application/json');
        echo json_encode(['ok' => ($res == '1'), 'msg' => ($res == '1') ? 'تم رفض الرقابة' : $res]);
    }

    // ==================== 🆕 تراجع الرقابة ====================
    function raqaba_undo()
    {
        if (!HaveAccess(base_url('payment_req/payment_req/raqaba_undo'))) {
            echo json_encode(['ok' => false, 'msg' => 'ليس لديك صلاحية الرقابة']); return;
        }
        $req_id = $this->p_req_id;
        if (!$req_id) { echo json_encode(['ok' => false, 'msg' => 'معرف الطلب مطلوب']); return; }
        $res = $this->{$this->MODEL_NAME}->raqaba_undo($req_id);
        header('Content-Type: application/json');
        echo json_encode(['ok' => ($res == '1'), 'msg' => ($res == '1') ? 'تم التراجع عن قرار الرقابة' : $res]);
    }

    // ==================== DELETE REQUEST (حذف فعلي) ====================
    function delete_request()
    {
        // حذف نهائي (مسودات فقط) — صلاحية "صرف رواتب - حذف نهائي" المنفصلة
        if (!HaveAccess(base_url('payment_req/payment_req/delete_request'))) {
            echo json_encode(['ok' => false, 'msg' => 'ليس لديك صلاحية الحذف النهائي']); return;
        }
        $req_id = $this->p_req_id;
        if (!$req_id) { echo json_encode(['ok' => false, 'msg' => 'معرف الطلب مطلوب']); return; }

        $res = $this->{$this->MODEL_NAME}->delete_request($req_id);

        header('Content-Type: application/json');
        if (strpos($res, '1|') === 0) {
            $parts = explode('|', $res);
            echo json_encode(['ok' => true, 'msg' => 'تم حذف الطلب و ' . ($parts[1] ?? 0) . ' موظف']);
        } else {
            echo json_encode(['ok' => false, 'msg' => $res]);
        }
    }

    // ==================== DETAIL APPROVE (partial) ====================
    function detail_approve()
    {
        if (!HaveAccess(base_url('payment_req/payment_req/approve'))) {
            echo json_encode(['ok' => false, 'msg' => 'ليس لديك صلاحية']); return;
        }
        $ids = $this->p_detail_ids;
        if (!$ids) { echo json_encode(['ok' => false, 'msg' => 'لم يتم تحديد موظفين']); return; }
        $res = $this->{$this->MODEL_NAME}->detail_approve($ids);
        if ($res == '1') {
            echo json_encode(['ok' => true, 'msg' => 'تم الاعتماد']);
        } elseif (strpos($res, '1|') === 0) {
            echo json_encode(['ok' => true, 'msg' => substr($res, 2), 'partial' => true]);
        } else {
            echo json_encode(['ok' => false, 'msg' => $res]);
        }
    }

    // ==================== DETAIL PAY (partial) ====================
    function detail_pay()
    {
        if (!HaveAccess(base_url('payment_req/payment_req/do_pay'))) {
            echo json_encode(['ok' => false, 'msg' => 'ليس لديك صلاحية']); return;
        }
        $ids = $this->p_detail_ids;
        if (!$ids) { echo json_encode(['ok' => false, 'msg' => 'لم يتم تحديد موظفين']); return; }
        $res = $this->{$this->MODEL_NAME}->detail_pay($ids);
        if ($res == '1') {
            echo json_encode(['ok' => true, 'msg' => 'تم الصرف']);
        } elseif (strpos($res, '1|') === 0) {
            echo json_encode(['ok' => true, 'msg' => substr($res, 2), 'partial' => true]);
        } else {
            echo json_encode(['ok' => false, 'msg' => $res]);
        }
    }

    // Stored request amounts only; never recalculate historical allocations here.
    private function _payment_report_rows($rows)
    {
        if (!is_array($rows)) return [];
        foreach ($rows as &$row) {
            $type = (int)($row['REQ_TYPE'] ?? 0);
            $applicable = in_array($type, [1, 2, 4], true);
            $total = $this->_report_number($row, 'REQ_AMOUNT');
            $allowance = array_key_exists('ALLOWANCE_325_AMOUNT', $row)
                ? ($row['ALLOWANCE_325_AMOUNT'] === null ? 0.0 : $this->_report_number($row, 'ALLOWANCE_325_AMOUNT')) : null;
            $valid = $applicable && $total !== null && $total >= 0 && $allowance !== null
                && $allowance >= 0 && $allowance <= $total;
            $row['PAYMENT_COMPONENTS_APPLICABLE'] = $applicable;
            $row['PAYMENT_BREAKDOWN_AVAILABLE'] = $valid;
            $row['TOTAL_PAYMENT'] = $total;
            $row['BASE_PAYMENT'] = $valid ? round($total - $allowance, 2) : null;
            $row['PAYMENT_ALLOWANCE_325'] = $valid ? round($allowance, 2) : null;
            $row['CALCULATION_BASIS'] = '—';
            if ($type === 2) {
                $mode = $this->_report_number($row, 'TYPE2_REMAINING_MODE');
                if ($mode === null && array_key_exists('TYPE2_REMAINING_MODE', $row)
                    && $row['TYPE2_REMAINING_MODE'] === null) $mode = 0.0;
                if ($mode === 0.0 || $mode === 1.0) {
                    $basis = $mode === 1.0 ? 'من المتبقي' : 'من الراتب';
                    $percent = $this->_report_number($row, 'PERCENT_VAL');
                    $row['CALCULATION_BASIS'] = ($percent !== null && $percent > 0 && $percent <= 100
                        ? rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.') . '% ' : 'نسبة ') . $basis;
                    if ($mode === 1.0) {
                        $include = $this->_report_number($row, 'INCLUDE_ALLOWANCES');
                        $row['CALCULATION_BASIS'] .= $include === 1.0 ? ' (323 + 325)' : ($include === 0.0 ? ' (323)' : '');
                    }
                } else {
                    $row['CALCULATION_BASIS'] = 'أساس الاحتساب غير متوفر';
                }
            }
        }
        unset($row);
        return $rows;
    }

    private function _report_number($row, $key)
    {
        if (!isset($row[$key]) || !is_numeric($row[$key]) || !is_finite((float)$row[$key])) return null;
        return (float)$row[$key];
    }

    private function _report_component_export($row, $key)
    {
        if (empty($row['PAYMENT_COMPONENTS_APPLICABLE'])) return '—';
        return !empty($row['PAYMENT_BREAKDOWN_AVAILABLE']) ? $row[$key] : 'غير متوفر';
    }

    // Preserve zero-valued report filters.
    private function _report_input($name, $default = null)
    {
        return $this->{'p_' . $name} ?? $this->{'q_' . $name} ?? $default;
    }

    // Read shared report options.
    private function _report_selection($rows, $context, $source = null)
    {
        if (is_array($rows)) array_walk_recursive($rows, function (&$value) {
            if (is_string($value)) $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        });
        $params = [];
        foreach (['columns', 'mode', 'allowance', 'detail_status', 'measure'] as $key) {
            $params['report_' . $key] = $this->_report_input('report_' . $key);
        }
        $result = payment_req_build_report($this->_payment_report_rows($rows), $context, $params);
        if ($source === null) $source = $context === 'general' ? $this->_report_month_query()
            : ['emp_no' => $this->_report_input('emp_no'), 'month_from' => $this->_report_input('month_from'), 'month_to' => $this->_report_input('month_to')];
        $result['report']['source_filters'] = $source;
        $result['report']['source_label'] = $this->_report_source_label($source);
        $result['report']['filter_label'] = implode(' · ', array_filter([$result['report']['source_label'], $result['report']['filter_label']], 'strlen'));
        return $result;
    }

    // Describe source query filters.
    private function _report_source_label($source)
    {
        $month = function ($value) {
            if ($value === null || $value === '') return 'غير محدد';
            return payment_req_report_cell(['THE_MONTH' => $value], ['kind' => 'month', 'id' => 'month', 'field' => 'THE_MONTH'], 0);
        };
        $labels = [];
        if (!empty($source['month_from']) || !empty($source['month_to'])) {
            $labels[] = 'الفترة: ' . $month($source['month_from'] ?? null) . ' — ' . $month($source['month_to'] ?? null);
        }
        if (isset($source['emp_no'])) $labels[] = 'الموظف رقم ' . (int)$source['emp_no'];
        if (!empty($source['branch_no'])) $labels[] = 'المقر رقم ' . (int)$source['branch_no'];
        if (!empty($source['req_type'])) {
            $types = [1 => 'راتب كامل', 2 => 'دفعة راتب', 3 => 'تسديد مستحقات', 4 => 'مستحقات الشهر', 5 => 'استحقاقات'];
            $labels[] = 'نوع الطلب: ' . ($types[$source['req_type'] ?? 0] ?? 'الكل');
        }
        if (isset($source['status'])) {
            $statuses = array_column(payment_req_report_config()['detail_status_options'], 'label', 'value');
            $labels[] = 'حالة الطلب: ' . ($source['status'] === null ? 'الكل' : ($statuses[(string)$source['status']] ?? 'غير معروفة'));
        }
        return implode(' · ', $labels);
    }

    // Share secured monthly query filters.
    private function _report_month_query()
    {
        $optional = function ($value) { return $value === '' || $value === '-1' || $value === -1 ? null : $value; };
        $month = $optional($this->_report_input('month_from')) ?: $optional($this->_report_input('the_month'));
        return ['month_from' => $month, 'month_to' => $optional($this->_report_input('month_to')) ?: $month,
            'branch_no' => (int)$this->user->branch === 1 ? ($optional($this->_report_input('branch_no')) ?: null) : $this->user->branch,
            'req_type' => $optional($this->_report_input('req_type')) ?: null, 'status' => $optional($this->_report_input('status'))];
    }

    // Print through the existing permission.
    private function _report_monthly_print()
    {
        $query = $this->_report_month_query();
        if (!$query['month_from']) { show_error('الشهر مطلوب'); return; }
        $result = $this->_report_selection($this->{$this->MODEL_NAME}->get_report(
            $query['month_from'], $query['month_to'], $query['branch_no'], $query['req_type'], $query['status']), 'general');
        $this->load->view('payment_req_report_print', ['rows' => $result['rows'], 'report' => $result['report'],
            'report_config' => payment_req_report_config('general'), 'query' => $query]);
    }

    // Export the selected report snapshot.
    private function _write_report_xlsx($result, $title, $filename)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($title . ' - ' . $result['report']['measure']['label']);
        $sheet->setRightToLeft(true);
        $columns = $result['report']['columns'];
        $summary = $result['report']['summary'];
        $lastCol = chr(64 + count($columns));
        foreach ($columns as $index => $column) $sheet->setCellValue(chr(65 + $index) . '1', $column['label']);
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastCol}1")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1e293b');
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->getColor()->setRGB('FFFFFF');
        $rowNum = 2;
        foreach ($result['rows'] as $index => $row) {
            foreach ($columns as $position => $column) {
                $value = payment_req_report_cell($row, $column, $index + 1);
                $cell = chr(65 + $position) . $rowNum;
                if (($column['kind'] === 'money' && is_numeric($value)) || $column['kind'] === 'sequence') $sheet->setCellValue($cell, $value);
                else $sheet->setCellValueExplicit($cell, (string)$value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $rowNum++;
        }
        $labelWritten = false;
        foreach ($columns as $position => $column) {
            $letter = chr(65 + $position);
            if ($column['id'] === 'amount') {
                $sheet->setCellValue($letter . $rowNum, $summary['amount'] ?? 'غير متوفر');
                $sheet->getStyle($letter . '2:' . $letter . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
            } elseif (!$labelWritten) {
                $sheet->setCellValue($letter . $rowNum, $summary['amount_label'] . ' (' . $summary['count'] . ')');
                $labelWritten = true;
            }
            $sheet->getColumnDimension($letter)->setAutoSize(true);
        }
        $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastCol}{$rowNum}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $metadata = ['الفلاتر: ' . $result['report']['filter_label'],
            'عدد الصفوف: ' . $summary['count'] . ' من ' . $result['report']['source_count'],
            $summary['amount_label'] . ': ' . ($summary['amount'] === null ? 'غير متوفر' : number_format($summary['amount'], 2, '.', ','))];
        $statuses = array_column(payment_req_report_config()['detail_status_options'], 'label', 'value');
        foreach ($summary['by_status'] as $code => $subtotal) {
            $metadata[] = ($statuses[$code] ?? 'غير معروفة') . ' (' . $subtotal['count'] . '): '
                . ($subtotal['amount'] === null ? 'غير متوفر' : number_format($subtotal['amount'], 2, '.', ','));
        }
        if (!$result['rows']) $metadata[] = 'لا توجد صفوف تطابق الفلاتر المختارة.';
        foreach ($metadata as $line) {
            $rowNum++;
            $sheet->setCellValue('A' . $rowNum, $line);
            if (count($columns) > 1) $sheet->mergeCells("A{$rowNum}:{$lastCol}{$rowNum}");
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    // ==================== REPORT — تقرير شهري ====================
    function report_monthly()
    {
        if ($this->_report_input('report_format') === 'print') { $this->_report_monthly_print(); return; }
        $data['title'] = 'تقرير صرف الرواتب والمستحقات';
        $data['content'] = 'payment_req_report';
        $data['report_config'] = payment_req_report_config('general');
        $this->_lookup($data, 'list');
        $this->load->view('template/template1', $data);
    }

    function report_monthly_data()
    {
        $query = $this->_report_month_query();
        if (!$query['month_from']) { echo json_encode(['ok' => false, 'msg' => 'الشهر مطلوب']); return; }
        $result = $this->_report_selection($this->{$this->MODEL_NAME}->get_report(
            $query['month_from'], $query['month_to'], $query['branch_no'], $query['req_type'], $query['status']), 'general');
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'data' => $result['rows'], 'count' => count($result['rows']), 'report' => $result['report']]);
    }

    function report_monthly_export()
    {
        $query = $this->_report_month_query();
        if (!$query['month_from']) { show_error('الشهر مطلوب'); return; }
        $result = $this->_report_selection($this->{$this->MODEL_NAME}->get_report(
            $query['month_from'], $query['month_to'], $query['branch_no'], $query['req_type'], $query['status']), 'general');
        $this->_write_report_xlsx($result, 'تقرير الصرف', 'تقرير_صرف_' . preg_replace('/[^0-9]/', '', (string)$query['month_from']) . '.xlsx');
    }

    // ==================== تقرير التزام المستحقات ====================
    function dues_liability()
    {
        $data['title']   = 'التزام المستحقات';
        $data['content'] = 'payment_req_dues_liability';
        $this->_lookup($data, 'list');
        $this->load->view('template/template1', $data);
    }

    function dues_liability_data()
    {
        $part      = $this->p_part ?: 'summary';
        $branch_no = ($this->user->branch == 1) ? ($this->p_branch_no ?: null) : $this->user->branch;

        switch ($part) {
            case 'movement':
                $rows = $this->{$this->MODEL_NAME}->liab_movement($this->p_month_from ?: null, $this->p_month_to ?: null, $branch_no);
                break;
            case 'branches':
                // توزيع المقرات للمقر الرئيسي فقط — مستخدم الفرع نطاقه فرعه أصلاً
                if ($this->user->branch != 1) { $rows = []; break; }
                $rows = $this->{$this->MODEL_NAME}->liab_by_branch();
                break;
            case 'employees':
                $rows = $this->{$this->MODEL_NAME}->liab_employees($branch_no, $this->p_emp_no ?: null);
                break;
            case 'emp_monthly':
                if (!$this->p_emp_no) { echo json_encode(['ok' => false, 'msg' => 'رقم الموظف مطلوب']); return; }
                $rows = $this->{$this->MODEL_NAME}->liab_emp_monthly($this->p_emp_no);
                break;
            default: // summary
                $rows = $this->{$this->MODEL_NAME}->liab_summary($branch_no);
        }

        if (is_array($rows)) {
            array_walk_recursive($rows, function (&$val) {
                if (is_string($val)) { $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
            });
        }

        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'data' => is_array($rows) ? $rows : [], 'count' => is_array($rows) ? count($rows) : 0]);
    }

    function dues_liability_export()
    {
        $branch_no  = ($this->user->branch == 1) ? $this->check_vars(-1, 'branch_no') : $this->user->branch;
        $month_from = $this->check_vars(-1, 'month_from');
        $month_to   = $this->check_vars(-1, 'month_to');

        $movement  = $this->{$this->MODEL_NAME}->liab_movement($month_from ?: null, $month_to ?: null, $branch_no ?: null);
        $employees = $this->{$this->MODEL_NAME}->liab_employees($branch_no ?: null, null);
        $branches  = ($this->user->branch == 1) ? $this->{$this->MODEL_NAME}->liab_by_branch() : [];

        if ((!is_array($employees) || count($employees) === 0) && (!is_array($movement) || count($movement) === 0)) {
            echo '<script>alert("لا توجد بيانات للتصدير"); history.back();</script>'; return;
        }

        $spreadsheet = new Spreadsheet();

        $hdrStyle = function ($sheet, $lastCol) {
            $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
            $sheet->getStyle("A1:{$lastCol}1")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('1e293b');
            $sheet->getStyle("A1:{$lastCol}1")->getFont()->getColor()->setRGB('FFFFFF');
        };
        $fmtMonth = function ($m) {
            $m = (string)$m;
            if ($m === '0' || $m === '') return 'غير مؤرّخ';
            return strlen($m) == 6 ? substr($m, 4, 2) . '/' . substr($m, 0, 4) : $m;
        };

        // ورقة 1: الحركة الشهرية
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('الحركة الشهرية');
        $sheet->setRightToLeft(true);
        $headers = ['الشهر', 'رصيد افتتاحي', 'استحقاق (بنود 48)', 'إضافات', 'تسديدات', 'صافي الحركة', 'رصيد ختامي'];
        $col = 'A';
        foreach ($headers as $h) { $sheet->setCellValue($col . '1', $h); $col++; }
        $hdrStyle($sheet, 'G');
        $rowNum = 2;
        foreach ((array)$movement as $r) {
            $sheet->setCellValue('A' . $rowNum, $fmtMonth($r['MON'] ?? ''));
            $sheet->setCellValue('B' . $rowNum, (float)($r['OPENING'] ?? 0));
            $sheet->setCellValue('C' . $rowNum, (float)($r['ACCRUED'] ?? 0));
            $sheet->setCellValue('D' . $rowNum, (float)($r['ADDS'] ?? 0));
            $sheet->setCellValue('E' . $rowNum, (float)($r['DEDS'] ?? 0));
            $sheet->setCellValue('F' . $rowNum, (float)($r['NET'] ?? 0));
            $sheet->setCellValue('G' . $rowNum, (float)($r['CLOSING'] ?? 0));
            $rowNum++;
        }
        if ($rowNum > 2) $sheet->getStyle('B2:G' . ($rowNum - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        foreach (range('A', 'G') as $c) { $sheet->getColumnDimension($c)->setAutoSize(true); }

        // ورقة 2: أرصدة الموظفين
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('أرصدة الموظفين');
        $sheet2->setRightToLeft(true);
        $headers2 = ['م', 'رقم الموظف', 'اسم الموظف', 'المقر', 'الأساس (رواتب)', 'إضافات', 'تسديدات', 'الرصيد الدفتري', 'محجوز بطلبات', 'المتاح'];
        $col = 'A';
        foreach ($headers2 as $h) { $sheet2->setCellValue($col . '1', $h); $col++; }
        $hdrStyle($sheet2, 'J');
        $rowNum = 2; $count = 1;
        foreach ((array)$employees as $r) {
            $sheet2->setCellValue('A' . $rowNum, $count);
            $sheet2->setCellValue('B' . $rowNum, (int)($r['EMP_NO'] ?? 0));
            $sheet2->setCellValue('C' . $rowNum, $r['EMP_NAME'] ?? '');
            $sheet2->setCellValue('D' . $rowNum, $r['BRANCH_NAME'] ?? '');
            $sheet2->setCellValue('E' . $rowNum, (float)($r['BASE_AMT'] ?? 0));
            $sheet2->setCellValue('F' . $rowNum, (float)($r['ADD_AMT'] ?? 0));
            $sheet2->setCellValue('G' . $rowNum, (float)($r['DED_AMT'] ?? 0));
            $sheet2->setCellValue('H' . $rowNum, (float)($r['BOOK_BALANCE'] ?? 0));
            $sheet2->setCellValue('I' . $rowNum, (float)($r['RESERVED'] ?? 0));
            $sheet2->setCellValue('J' . $rowNum, (float)($r['AVAILABLE'] ?? 0));
            $count++; $rowNum++;
        }
        if ($rowNum > 2) $sheet2->getStyle('E2:J' . ($rowNum - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        foreach (range('A', 'J') as $c) { $sheet2->getColumnDimension($c)->setAutoSize(true); }

        // ورقة 3: المقرات (المقر الرئيسي فقط)
        if (is_array($branches) && count($branches) > 0) {
            $sheet3 = $spreadsheet->createSheet();
            $sheet3->setTitle('حسب المقر');
            $sheet3->setRightToLeft(true);
            $headers3 = ['المقر', 'عدد أصحاب الرصيد', 'الرصيد الدفتري', 'محجوز', 'المتاح'];
            $col = 'A';
            foreach ($headers3 as $h) { $sheet3->setCellValue($col . '1', $h); $col++; }
            $hdrStyle($sheet3, 'E');
            $rowNum = 2;
            foreach ($branches as $r) {
                $sheet3->setCellValue('A' . $rowNum, $r['BR_NAME'] ?? '');
                $sheet3->setCellValue('B' . $rowNum, (int)($r['EMP_COUNT'] ?? 0));
                $sheet3->setCellValue('C' . $rowNum, (float)($r['BOOK_BALANCE'] ?? 0));
                $sheet3->setCellValue('D' . $rowNum, (float)($r['TOTAL_RESERVED'] ?? 0));
                $sheet3->setCellValue('E' . $rowNum, (float)($r['TOTAL_AVAILABLE'] ?? 0));
                $rowNum++;
            }
            if ($rowNum > 2) $sheet3->getStyle('C2:E' . ($rowNum - 1))->getNumberFormat()->setFormatCode('#,##0.00');
            foreach (range('A', 'E') as $c) { $sheet3->getColumnDimension($c)->setAutoSize(true); }
        }

        $spreadsheet->setActiveSheetIndex(0);
        $filename = 'التزام_المستحقات_' . date('Ymd') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ==================== STATEMENT — كشف حساب موظف ====================
    function emp_statement($emp_no = null)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' || $this->input->is_ajax_request()) {
            $emp_no    = $this->p_emp_no ?: $emp_no;
            $month_from = $this->p_month_from ?: null;
            $month_to   = $this->p_month_to   ?: null;
            if (!$emp_no) { echo json_encode(['ok' => false, 'msg' => 'رقم الموظف مطلوب']); return; }

            // البيانات الأساسية (الموجودة)
            $rows     = $this->{$this->MODEL_NAME}->get_emp_statement($emp_no, $month_from, $month_to);
            $summary  = $this->{$this->MODEL_NAME}->get_summary($emp_no, null);

            // 🆕 بيانات إضافية للكشف الشامل
            $accounts = $this->{$this->MODEL_NAME}->emp_accounts_list($emp_no, 0);  // كل الحسابات
            $pending  = $this->{$this->MODEL_NAME}->emp_pending_batches($emp_no);    // الدفعات المحتسبة
            $batches  = $this->{$this->MODEL_NAME}->emp_all_batches($emp_no);        // 🆕 كل الدفعات + توزيع

            // معلومات الموظف من DATA.EMPLOYEES + الحالة المركّبة
            $emp_info = $this->{$this->MODEL_NAME}->get_emp_info($emp_no);

            if (is_array($rows))     array_walk_recursive($rows,     function (&$v) { if (is_string($v)) $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8'); });
            if (is_array($accounts)) array_walk_recursive($accounts, function (&$v) { if (is_string($v)) $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8'); });
            if (is_array($pending))  array_walk_recursive($pending,  function (&$v) { if (is_string($v)) $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8'); });
            if (is_array($batches))  array_walk_recursive($batches,  function (&$v) { if (is_string($v)) $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8'); });
            if (is_array($emp_info)) array_walk_recursive($emp_info, function (&$v) { if (is_string($v)) $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8'); });

            $selection = $this->_report_selection($rows, 'statement', ['emp_no' => $emp_no, 'month_from' => $month_from, 'month_to' => $month_to]);
            $rows = $selection['rows'];
            header('Content-Type: application/json');
            echo json_encode([
                'ok'       => true,
                'data'     => is_array($rows)     ? $rows     : [],
                'summary'  => is_array($summary)  && count($summary)  > 0 ? $summary[0]  : [],
                'accounts' => is_array($accounts) ? $accounts : [],
                'pending'  => is_array($pending)  ? $pending  : [],
                'batches'  => is_array($batches)  ? $batches  : [],   // 🆕
                'emp_info' => is_array($emp_info) && count($emp_info) > 0 ? $emp_info[0] : [],
                'count'    => is_array($rows) ? count($rows) : 0,
                'report'   => $selection['report']
            ]);
            return;
        }

        $data['title']   = 'كشف حساب موظف';
        $data['content'] = 'payment_req_emp_statement';
        $data['report_config'] = payment_req_report_config('statement');
        $data['emp_no']  = $emp_no;
        $this->_lookup($data, 'list');
        $this->load->view('template/template1', $data);
    }

    // ====================================================================
    // 🆕 emp_statement_print — صفحة A4 احترافية لكشف الموظف الشامل
    // ====================================================================
    function emp_statement_print($emp_no = null, $month_from = null, $month_to = null)
    {
        $emp_no = (int)($emp_no ?: $this->_report_input('emp_no'));
        $month_from = $this->_report_input('month_from', $month_from);
        $month_to = $this->_report_input('month_to', $month_to);
        if (!$emp_no) { show_error('رقم الموظف مطلوب'); return; }

        if ($month_from === '0' || $month_from === '-' || $month_from === '') $month_from = null;
        if ($month_to   === '0' || $month_to   === '-' || $month_to   === '') $month_to   = null;

        // كل البيانات اللازمة (نفس مصادر emp_statement JSON)
        $rows     = $this->{$this->MODEL_NAME}->get_emp_statement($emp_no, $month_from, $month_to);
        $summary  = $this->{$this->MODEL_NAME}->get_summary($emp_no, null);
        $accounts = $this->{$this->MODEL_NAME}->emp_accounts_list($emp_no, 0);
        $pending  = $this->{$this->MODEL_NAME}->emp_pending_batches($emp_no);
        $batches  = $this->{$this->MODEL_NAME}->emp_all_batches($emp_no);
        $emp_info = $this->{$this->MODEL_NAME}->get_emp_info($emp_no);

        foreach ([&$rows, &$accounts, &$pending, &$batches, &$emp_info] as &$arr) {
            if (is_array($arr)) {
                array_walk_recursive($arr, function (&$v) {
                    if (is_string($v)) $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8');
                });
            }
        }
        unset($arr);

        $selection = $this->_report_selection($rows, 'statement', ['emp_no' => $emp_no, 'month_from' => $month_from, 'month_to' => $month_to]);
        $data = [
            'report' => $selection['report'],
            'report_config' => payment_req_report_config('statement'),
            'emp_no'     => $emp_no,
            'month_from' => $month_from,
            'month_to'   => $month_to,
            'rows'       => $selection['rows'],
            'summary'    => is_array($summary) && count($summary) > 0 ? $summary[0] : [],
            'accounts'   => is_array($accounts) ? $accounts : [],
            'pending'    => is_array($pending) ? $pending : [],
            'batches'    => is_array($batches) ? $batches : [],
            'emp_info'   => is_array($emp_info) && count($emp_info) > 0 ? $emp_info[0] : [],
        ];
        $this->load->view('payment_req_emp_statement_print', $data);
    }

    function emp_statement_export()
    {
        $emp_no = $this->_report_input('emp_no');
        $month_from = $this->_report_input('month_from') ?: null;
        $month_to = $this->_report_input('month_to') ?: null;
        if (!$emp_no) { show_error('رقم الموظف مطلوب'); return; }
        $result = $this->_report_selection($this->{$this->MODEL_NAME}->get_emp_statement($emp_no, $month_from, $month_to), 'statement');
        $this->_write_report_xlsx($result, 'كشف حساب', 'كشف_حساب_' . (int)$emp_no . '.xlsx');
    }

    // ==================== BANK CSV ====================
    function export_bank_csv()
    {
        // ندعم GET (الرابط من الزر) و POST (التوافق مع NMVC القديم)
        $req_id = $this->input->get('req_id') ?: $this->input->post('req_id') ?: $this->p_req_id;
        $req_id = (int)$req_id;
        if (!$req_id) { echo '<script>alert("معرف الطلب مطلوب"); history.back();</script>'; return; }

        // البيانات الأساسية للموظفين
        $details = $this->{$this->MODEL_NAME}->get_details($req_id);
        if (!is_array($details) || count($details) === 0) {
            echo '<script>alert("لا توجد بيانات"); history.back();</script>'; return;
        }

        // 🏦 بيانات التوزيع: كل (موظف + حساب) → سطر CSV واحد بكل التفاصيل البنكية
        $this->load->model('payment_accounts/payment_accounts_model');
        $preview_rows = $this->payment_accounts_model->accounts_preview_by_req((int)$req_id);
        if (is_array($preview_rows)) {
            array_walk_recursive($preview_rows, function (&$val) {
                if (is_string($val)) { $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
            });
        }

        // فهرس بالموظف لتسهيل الوصول
        $emp_index = [];
        foreach ($details as $d) {
            $eno = (int)($d['EMP_NO'] ?? 0);
            if ($eno) $emp_index[$eno] = $d;
        }

        $filename = 'bank_transfer_PR' . str_pad((int)$req_id, 5, '0', STR_PAD_LEFT) . '_' . date('Ymd') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        echo "\xEF\xBB\xBF"; // BOM for Excel UTF-8 (RTL display)
        $out = fopen('php://output', 'w');

        // Header — كل أعمدة الحركة البنكية
        fputcsv($out, [
            '#',
            'رقم الموظف',
            'اسم الموظف',
            'المقر',
            'نوع المستلم',
            'المستلم',
            'العلاقة',
            'البنك / المحفظة',
            'فرع البنك',
            'رقم الحساب',
            'IBAN',
            'رقم هوية صاحب الحساب',
            'هاتف صاحب الحساب',
            'المبلغ'
        ], ',', '"', '\\');

        $n = 0; $total = 0;
        $emp_clean_cache = [];
        foreach ((array)$preview_rows as $r) {
            $eno = (int)($r['EMP_NO'] ?? 0);
            if (!$eno) continue;
            // تخطّى الحسابات الموقوفة وغير المخصص لها مبلغ
            if ((int)($r['ACC_IS_ACTIVE'] ?? 1) === 0) continue;
            $alloc = (float)($r['ALLOC_AMOUNT'] ?? 0);
            if ($alloc <= 0) continue;

            $d = $emp_index[$eno] ?? [];
            $emp_name = $d['EMP_NAME'] ?? '';
            $branch_name = $d['BRANCH_NAME'] ?? '';

            // تحديد نوع المستلم
            $type_label = 'الموظف';
            $recipient = $emp_name;
            $rel = '';
            $owner_clean = trim($r['OWNER_NAME'] ?? '');
            $emp_clean = isset($emp_clean_cache[$eno]) ? $emp_clean_cache[$eno] : ($emp_clean_cache[$eno] = trim($emp_name));

            if (!empty($r['BENEFICIARY_ID'])) {
                $type_label = 'وريث';
                $recipient = $r['BENEF_NAME'] ?? $owner_clean;
                $rel = $r['BENEF_REL_NAME'] ?? '';
            } elseif ($owner_clean && $owner_clean !== $emp_clean) {
                $type_label = 'صاحب حساب';
                $recipient = $owner_clean;
                $rel = 'حساب باسم آخر';
            }

            $n++;
            $total += $alloc;
            fputcsv($out, [
                $n,
                $eno,
                $emp_name,
                $branch_name,
                $type_label,
                $recipient,
                $rel,
                $r['PROVIDER_NAME']    ?? '',
                $r['PROV_BRANCH_NAME'] ?? '',
                $r['ACCOUNT_NO']       ?? '',  // ACCOUNT_NO أو WALLET_NUMBER (الـ procedure تستخدم NVL)
                $r['IBAN']             ?? '',
                $r['OWNER_ID_NO']      ?? '',
                $r['OWNER_PHONE']      ?? '',
                number_format($alloc, 2, '.', '')
            ], ',', '"', '\\');
        }

        // صف الإجمالي
        fputcsv($out, ['', '', '', '', '', '', '', '', '', '', '', '', 'الإجمالي', number_format($total, 2, '.', '')], ',', '"', '\\');

        fclose($out);
        exit;
    }

    // ==================== SUMMARY (AJAX) ====================
    function public_get_summary()
    {
        $emp_no    = $this->p_emp_no;
        $the_month = $this->p_the_month;
        if (!$emp_no) { echo json_encode(['ok' => false, 'msg' => 'رقم الموظف مطلوب']); return; }
        $the_month = ($the_month != '' && $the_month != null) ? $the_month : null;
        $result    = $this->{$this->MODEL_NAME}->get_summary($emp_no, $the_month);
        echo json_encode(['ok' => true, 'data' => $result]);
    }

    // Read-only history for the type-2 preview and saved request.
    function public_employee_month_payments()
    {
        header('Content-Type: application/json; charset=UTF-8');
        if (!HaveAccess($this->create_url) && !HaveAccess(base_url($this->PAGE_URL)) &&
            !HaveAccess(base_url('payment_req/payment_req/get'))) {
            echo json_encode(['ok' => false, 'msg' => 'ليس لديك صلاحية عرض دفعات الموظف.']);
            return;
        }
        $emp_no = $this->input->post('emp_no');
        $req_id = $this->input->post('req_id');
        $month = $this->input->post('the_month');
        if (!is_scalar($emp_no) || !preg_match('/^[1-9][0-9]{0,9}$/D', (string)$emp_no) ||
            ($req_id !== null && $req_id !== '' && (!is_scalar($req_id) || !preg_match('/^[0-9]{1,10}$/D', (string)$req_id)))) {
            echo json_encode(['ok' => false, 'msg' => 'رقم الموظف أو الطلب غير صحيح.']);
            return;
        }
        $emp_no = (int)$emp_no;
        $req_id = (int)$req_id;
        try {
            if ($req_id > 0) {
                $master = $this->{$this->MODEL_NAME}->get($req_id);
                if (!is_array($master) || empty($master[0]) || (int)($master[0]['REQ_TYPE'] ?? 0) !== 2) {
                    echo json_encode(['ok' => false, 'msg' => 'تعذر العثور على طلب صرف من النوع 2.']);
                    return;
                }
                // Saved requests always use their own month, even if the browser sends another.
                $month = $master[0]['THE_MONTH'] ?? null;
            }
            if (!is_scalar($month) || !preg_match('/^20[0-9]{2}(0[1-9]|1[0-2])$/D', (string)$month)) {
                echo json_encode(['ok' => false, 'msg' => 'يرجى تحديد شهر صحيح لعرض الدفعات.']);
                return;
            }
            $month = (int)$month;
            $result = $this->{$this->MODEL_NAME}->get_emp_month_payments($emp_no, $month);
            if (!is_array($result) || trim((string)($result['msg'] ?? '')) !== '1' || !is_array($result['rows'] ?? null)) {
                echo json_encode(['ok' => false, 'msg' => 'تعذر تحميل دفعات الموظف. حاول مرة أخرى.']);
                return;
            }
            echo json_encode(array_merge(['ok' => true], $this->_employee_month_payments_data($result['rows'], $emp_no, $month, $req_id)));
        } catch (\Exception $e) {
            log_message('error', 'public_employee_month_payments: ' . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'تعذر تحميل دفعات الموظف. تأكد من تحديث الباكج ثم حاول مرة أخرى.']);
        }
    }

    private function _employee_month_payments_data($rows, $emp_no, $month, $req_id)
    {
        $data = [];
        $request_ids = [];
        // Add integer cents so the cards agree exactly with their displayed rows.
        $totals = ['paid_amount' => 0, 'reserved_amount' => 0,
            'paid_base_amount' => 0, 'reserved_base_amount' => 0,
            'paid_allowance_amount' => 0, 'reserved_allowance_amount' => 0];
        $status_names = [0 => 'مسودة', 1 => 'معتمد', 2 => 'مصروف', 3 => 'معتمد جزئياً', 4 => 'محتسب ضمن دفعة'];
        $type_names = [1 => 'راتب كامل', 2 => 'دفعة من الراتب', 4 => 'مستحقات حسب الشهر'];
        foreach ($rows as $row) {
            // Missing metadata means the production package has not yet been updated.
            foreach (['EMP_NO', 'REQ_ID', 'THE_MONTH', 'REQ_TYPE', 'STATUS', 'DETAIL_STATUS', 'REQ_AMOUNT',
                'PERCENT_VAL', 'TYPE2_REMAINING_MODE', 'ALLOWANCE_325_AMOUNT'] as $field) {
                if (!is_array($row) || !array_key_exists($field, $row)) {
                    throw new \UnexpectedValueException('Employee payment history is missing ' . $field);
                }
            }
            $id = (int)$row['REQ_ID'];
            $type = (int)$row['REQ_TYPE'];
            $status = (int)$row['DETAIL_STATUS'];
            if ((int)$row['EMP_NO'] !== (int)$emp_no || (int)$row['THE_MONTH'] !== (int)$month ||
                $id <= 0 || $id === (int)$req_id || !isset($type_names[$type]) ||
                (int)$row['STATUS'] === 9 || $status === 9) continue;
            if (!is_numeric($row['REQ_AMOUNT']) ||
                ($row['ALLOWANCE_325_AMOUNT'] !== null && !is_numeric($row['ALLOWANCE_325_AMOUNT']))) {
                throw new \UnexpectedValueException('Employee payment history has an invalid amount');
            }
            $amount = (int)round((float)$row['REQ_AMOUNT'] * 100);
            $allowance = (int)round((float)($row['ALLOWANCE_325_AMOUNT'] ?? 0) * 100);
            $base = max($amount - $allowance, 0);
            // Detail status 4 still reserves funds; only status 2 is actually paid.
            $bucket = $status === 2 ? 'paid' : 'reserved';
            $totals[$bucket . '_amount'] += $amount;
            $totals[$bucket . '_base_amount'] += $base;
            $totals[$bucket . '_allowance_amount'] += $allowance;
            $request_ids[$id] = true;
            $remaining_mode = (int)$row['TYPE2_REMAINING_MODE'] === 1 ? 1 : 0;
            $percent = $type === 2 && is_numeric($row['PERCENT_VAL']) && (float)$row['PERCENT_VAL'] > 0 &&
                (float)$row['PERCENT_VAL'] <= 100 ? (float)$row['PERCENT_VAL'] : null;
            $data[] = [
                'req_id' => $id, 'req_no' => (string)($row['REQ_NO'] ?? $id),
                'req_type' => $type, 'req_type_name' => $type_names[$type],
                'percent_val' => $percent,
                'calculation_basis' => $type === 2 ? ($remaining_mode ? 'نسبة من المتبقي' : 'نسبة من الراتب') : $type_names[$type],
                'type2_remaining_mode' => $remaining_mode, 'detail_status' => $status,
                'status_name' => $status_names[$status] ?? 'محجوز', 'bucket' => $bucket,
                'amount' => $amount / 100, 'base_amount' => $base / 100, 'allowance_325_amount' => $allowance / 100,
                'entry_date' => (string)($row['ENTRY_DATE'] ?? ''), 'pay_date' => (string)($row['PAY_DATE'] ?? ''),
                'request_url' => base_url('payment_req/payment_req/get/' . $id),
            ];
        }
        foreach ($totals as &$total) $total /= 100;
        unset($total);
        $totals['request_count'] = count($request_ids);
        return ['data' => $data, 'summary' => $totals, 'emp_no' => (int)$emp_no,
            'the_month' => (int)$month, 'current_req_id' => (int)$req_id];
    }

    // ==================== APPROVE BATCH ====================
    function approve_batch()
    {
        if (!HaveAccess(base_url('payment_req/payment_req/approve'))) {
            echo json_encode(['ok' => false, 'msg' => 'ليس لديك صلاحية']); return;
        }
        $the_month = $this->p_the_month;
        $req_ids   = $this->p_req_ids ?: null;
        if (!$the_month) { echo json_encode(['ok' => false, 'msg' => 'الشهر مطلوب']); return; }
        $result = $this->{$this->MODEL_NAME}->approve_batch($the_month, $req_ids);
        echo json_encode($result);
    }

    // ==================== PAY BATCH ====================
    function pay_batch()
    {
        if (!HaveAccess(base_url('payment_req/payment_req/do_pay'))) {
            echo json_encode(['ok' => false, 'msg' => 'ليس لديك صلاحية']); return;
        }
        $the_month = $this->p_the_month;
        $req_ids   = $this->p_req_ids ?: null;
        if (!$the_month) { echo json_encode(['ok' => false, 'msg' => 'الشهر مطلوب']); return; }
        $result = $this->{$this->MODEL_NAME}->pay_batch($the_month, $req_ids);
        echo json_encode($result);
    }

    // ==================== تجهيز الصرف — الشاشة ====================
    function batch()
    {
        $data['title']   = 'تجهيز دفعة الصرف';
        $data['content'] = 'payment_req_batch';
        $this->_lookup($data, 'minimal');
        $this->load->view('template/template1', $data);
    }

    // ==================== 🆕 خريطة الموظفين للطلبات (لشاشة احتساب الصرف) ====================
    // يُستدعى مرة واحدة بعد البحث — يعطي JS قائمة EMP_NOs لكل REQ_ID
    // وبكذا يقدر JS يحسب الموظفين الفريدين + التكرار client-side في كل check/uncheck
    function req_emps_for_batch_json()
    {
        $req_ids = trim((string)$this->input->get_post('req_ids'));
        // sanitize: digits + comma only
        $req_ids = preg_replace('/[^0-9,]/', '', $req_ids);
        $req_ids = trim($req_ids, ',');

        header('Content-Type: application/json');
        if ($req_ids === '') {
            echo json_encode(['ok' => true, 'mapping' => new stdClass(), 'total_rows' => 0, 'unique_emps' => 0]);
            return;
        }

        try {
            $rows = $this->{$this->MODEL_NAME}->req_emps_by_ids($req_ids);
            // mapping: { req_id: [emp_no, ...] }
            $mapping = [];
            $all_emps = [];
            foreach ($rows as $r) {
                $rid = (int)($r['REQ_ID'] ?? 0);
                $emp = (int)($r['EMP_NO'] ?? 0);
                if (!$rid || !$emp) continue;
                if (!isset($mapping[$rid])) $mapping[$rid] = [];
                $mapping[$rid][] = $emp;
                $all_emps[$emp] = true;
            }
            echo json_encode([
                'ok'          => true,
                'mapping'     => $mapping ?: new stdClass(),
                'total_rows'  => count($rows),
                'unique_emps' => count($all_emps),
            ]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'msg' => 'خطأ: ' . $e->getMessage()]);
        }
    }

    // ==================== سجل الدفعات ====================
    function batch_history()
    {
        $data['title']   = 'سجل الدفعات';
        $data['content'] = 'payment_req_batch_history';
        $data['batch_rows'] = $this->{$this->MODEL_NAME}->batch_history_list();
        $this->load->model('settings/constant_details_model');
        $data['batch_status_cons'] = $this->constant_details_model->get_list(542);
        $this->_lookup($data, 'minimal');
        $this->load->view('template/template1', $data);
    }

    function batch_history_json()
    {
        $rows = $this->{$this->MODEL_NAME}->batch_history_list();
        if (is_array($rows)) {
            array_walk_recursive($rows, function (&$val) {
                if (is_string($val)) { $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
            });
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'rows' => is_array($rows) ? $rows : []]);
    }

    function batch_detail($id)
    {
        $batch_rows = $this->{$this->MODEL_NAME}->batch_history_list();
        $batch_info = null;
        if (is_array($batch_rows)) {
            foreach ($batch_rows as $b) {
                if ((int)($b['BATCH_ID'] ?? 0) == (int)$id) { $batch_info = $b; break; }
            }
        }
        if (!$batch_info) { redirect('payment_req/payment_req/batch_history'); return; }

        $detail_rows = $this->{$this->MODEL_NAME}->batch_history_get((int)$id);
        if (is_array($detail_rows)) {
            array_walk_recursive($detail_rows, function (&$val) {
                if (is_string($val)) { $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
            });
        }

        // جلب بيانات الطلبات المشمولة (الشهور + الأنواع)
        $req_ids_str = $batch_info['REQ_IDS'] ?? '';
        $batch_reqs = [];
        if ($req_ids_str) {
            $where = " AND M.REQ_ID IN ($req_ids_str) ";
            $reqs = $this->{$this->MODEL_NAME}->get_list_master($where, 0, 100);
            if (is_array($reqs)) {
                array_walk_recursive($reqs, function (&$val) {
                    if (is_string($val)) { $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
                });
                $batch_reqs = $reqs;
            }
        }

        // ملخص حسب البنك/المحفظة (snapshot-aware)
        $bank_summary = $this->{$this->MODEL_NAME}->batch_bank_summary((int)$id);
        if (is_array($bank_summary)) {
            array_walk_recursive($bank_summary, function (&$val) {
                if (is_string($val)) { $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
            });
        }

        // 🆕 المستفيدون: قائمة flat لكل (موظف + حساب) — منظور المستلم الفعلي
        // (نفس البيانات التي تُصدّر للبنوك — snapshot-aware)
        $recipients_raw = $this->{$this->MODEL_NAME}->batch_bank_export((int)$id);
        if (is_array($recipients_raw)) {
            array_walk_recursive($recipients_raw, function (&$val) {
                if (is_string($val)) { $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
            });
        }

        // 🆕 حساب الأسطر بعد الدمج لكل بنك — يطابق منطق export_bank_file:
        //   • بنوك (PROVIDER_TYPE=1): دمج بـ IBAN > ACCOUNT_NO > ACC_ID > OWNER_ID
        //   • محفظات (PROVIDER_TYPE=2): كل صف يبقى منفصلاً (لا دمج)
        // النتيجة: bank_merged_counts[master_bank_no] = عدد الأسطر التي ستظهر في كشف البنك
        $bank_merged_counts = [];   // mbno => post-merge line count
        if (is_array($recipients_raw)) {
            $seen_keys = [];        // mbno => set of dedup keys
            foreach ($recipients_raw as $r) {
                $mbno = (int)($r['MASTER_BANK_NO'] ?? 0);
                $ptp  = (int)($r['PROVIDER_TYPE']  ?? 1);

                if (!isset($seen_keys[$mbno])) $seen_keys[$mbno] = [];
                if (!isset($bank_merged_counts[$mbno])) $bank_merged_counts[$mbno] = 0;

                if ($ptp === 2) {
                    // محفظات: كل صف منفصل
                    $bank_merged_counts[$mbno]++;
                    continue;
                }

                // بنوك: دمج بحسب الوجهة
                $iban  = trim((string)($r['IBAN']        ?? ''));
                $accNo = trim((string)($r['ACCOUNT_NO']  ?? ''));
                $acc   = (int)($r['ACC_ID']              ?? 0);
                $ownId = trim((string)($r['OWNER_ID_NO'] ?? ''));

                $destId = '';
                if ($iban !== '')      $destId = 'IB:' . $iban;
                elseif ($accNo !== '') $destId = 'AC:' . $accNo;
                elseif ($acc > 0)      $destId = 'ID:' . $acc;
                else                   $destId = 'OW:' . $ownId;

                if (!isset($seen_keys[$mbno][$destId])) {
                    $seen_keys[$mbno][$destId] = 1;
                    $bank_merged_counts[$mbno]++;
                }
            }
        }

        // 🆕 Trend: آخر 6 دفعات (الحالية + 5 سابقات) للمقارنة
        $batch_trend = $this->{$this->MODEL_NAME}->batch_trend((int)$id, 6);
        if (is_array($batch_trend)) {
            array_walk_recursive($batch_trend, function (&$val) {
                if (is_string($val)) { $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
            });
        }

        // 🆕 قائمة الدفعات المتاحة للمقارنة (كل الدفعات عدا الحالية والملغية) + اختيار اليوزر (?prev=)
        $compare_options = [];
        if (is_array($batch_rows)) {
            foreach ($batch_rows as $b) {
                $bid = (int)($b['BATCH_ID'] ?? 0);
                if ($bid === (int)$id || $bid <= 0) continue;
                if ((int)($b['STATUS'] ?? 0) === 9) continue;
                $compare_options[] = [
                    'BATCH_ID'       => $bid,
                    'BATCH_NO'       => $b['BATCH_NO'] ?? '',
                    'ENTRY_DATE'     => $b['ENTRY_DATE'] ?? '',
                    'TOTAL_AMOUNT'   => (float)($b['TOTAL_AMOUNT'] ?? 0),
                    'EMP_COUNT'      => (int)($b['EMP_COUNT'] ?? 0),
                    'MOVEMENT_COUNT' => (int)($b['MOVEMENT_COUNT'] ?? 0),
                ];
            }
        }
        $sel_prev    = (int)$this->input->get('prev');
        $valid_ids   = array_map(function ($o) { return (int)$o['BATCH_ID']; }, $compare_options);
        $cmp_prev_id = ($sel_prev > 0 && in_array($sel_prev, $valid_ids, true)) ? $sel_prev : 0;

        // 🆕 حالة كل (موظف+شهر) في الدفعة — للفلتر الزمني (فعّال/غير فعّال حسب الشهر)
        $emp_month_rows = $this->{$this->MODEL_NAME}->batch_emp_month_statuses((int)$id);
        $emp_status_by_month = [];   // emp_no => { month => status }
        $batch_months_set    = [];   // [202509, 202605, ...]
        if (is_array($emp_month_rows)) {
            foreach ($emp_month_rows as $r) {
                $eno = (int)($r['EMP_NO'] ?? 0);
                $mon = (string)($r['THE_MONTH'] ?? '');
                $st  = (int)($r['EMP_STATUS'] ?? 1);
                if (!$eno || $mon === '') continue;
                if (!isset($emp_status_by_month[$eno])) $emp_status_by_month[$eno] = [];
                $emp_status_by_month[$eno][$mon] = $st;
                $batch_months_set[$mon] = 1;
            }
        }
        $batch_months = array_keys($batch_months_set);
        sort($batch_months);

        // 🆕 عدد المرفقات لكل طلب في الدفعة (للأيقونة بجانب PR-XXXXX)
        $this->load->model('attachments/attachment_model');
        $req_attachments_count = [];
        foreach ($batch_reqs as $rq) {
            $rid = (int)($rq['REQ_ID'] ?? 0);
            if (!$rid) continue;
            $list = $this->attachment_model->get_list($rid, 'payment_req');
            $req_attachments_count[$rid] = is_array($list) ? count($list) : 0;
        }

        // 🆕 جلب الـ covers المرتبطة بهذه الدفعة (عبر BATCH_ID + MASTER_BANK_NO على PAYMENT_COVER_TB)
        // النتيجة: map<master_bank_no, ['cover_id'=>X, 'fp_id'=>Y, 'amount'=>...]>
        $cover_rows = $this->{$this->MODEL_NAME}->cover_find_all_by_batch((int)$id);
        $covers_by_bank = [];
        if (is_array($cover_rows)) {
            foreach ($cover_rows as $cr) {
                $mbno = (int)($cr['MASTER_BANK_NO'] ?? 0);
                if ($mbno <= 0) continue;
                $covers_by_bank[$mbno] = [
                    'cover_id'      => (int)($cr['COVER_ID']      ?? 0),
                    'fp_id'         => (int)($cr['FP_ID']         ?? 0),  // > 0 = لديه سند صرف
                    'amount'        => (float)($cr['TOTAL_AMOUNT'] ?? 0),
                    'bank_name'     => $cr['BANK_NAME']           ?? '',
                    'check_bank_id' => (int)($cr['CHECK_BANK_ID'] ?? 0),  // 🆕 لتعبئة dropdown سند الصرف
                ];
            }
        }

        $this->load->model('settings/constant_details_model');
        $data['title']         = 'تفاصيل الدفعة — ' . ($batch_info['BATCH_NO'] ?? $id);
        $data['content']       = 'payment_req_batch_detail';
        $data['batch_info']    = $batch_info;
        $data['detail_rows']   = is_array($detail_rows) ? $detail_rows : [];
        $data['bank_summary']  = is_array($bank_summary) ? $bank_summary : [];
        $data['recipients']    = is_array($recipients_raw) ? $recipients_raw : [];
        $data['bank_merged_counts'] = $bank_merged_counts;  // mbno => عدد الأسطر بعد الدمج
        $data['batch_reqs']    = $batch_reqs;
        $data['batch_trend']   = is_array($batch_trend) ? $batch_trend : [];
        $data['compare_options'] = $compare_options;
        $data['cmp_prev_id']     = $cmp_prev_id;
        $data['emp_status_by_month'] = $emp_status_by_month;
        $data['batch_months']  = $batch_months;
        $data['req_attachments_count'] = $req_attachments_count;
        $data['batch_status_cons'] = $this->constant_details_model->get_list(542);
        $data['covers_by_bank'] = $covers_by_bank;   // 🆕
        $this->_lookup($data, 'minimal');
        $this->load->view('template/template1', $data);
    }

    // ============================================================
    // 🆕 PAYMENT COVER من جهة الدفعة — كل العمليات هنا (ما نلامس payment module)
    // ============================================================

    /**
     * إنشاء (أو فتح) نموذج صرف لبنك معيّن في دفعة
     *   - idempotent: لو موجود → redirect للقديم
     *   - لو غير موجود → INSERT مباشر في PAYMENT_COVER_TB مع BATCH_ID + MASTER_BANK_NO
     *   - النتيجة: redirect إلى /payment/payment_cover/get/{id}
     *
     * URL: /payment_req/payment_req/cover_create_from_batch/{batch_id}/{master_bank_no}
     */
    function cover_create_from_batch($batch_id = 0, $master_bank_no = 0)
    {
        $batch_id       = (int)$batch_id;
        $master_bank_no = (int)$master_bank_no;

        if ($batch_id <= 0) { show_error('معرّف الدفعة غير صحيح', 400); return; }

        // 0) idempotency
        $existing = $this->{$this->MODEL_NAME}->cover_find_by_batch($batch_id, $master_bank_no);
        if ($existing) {
            redirect('payment/payment_cover/get/' . (int)$existing);
            return;
        }

        // 1) قراءة بيانات الدفعة
        $batch_rows = $this->{$this->MODEL_NAME}->batch_history_list();
        $batch_info = null;
        if (is_array($batch_rows)) {
            foreach ($batch_rows as $b) {
                if ((int)($b['BATCH_ID'] ?? 0) === $batch_id) { $batch_info = $b; break; }
            }
        }
        if (!$batch_info) { show_error('الدفعة غير موجودة', 404); return; }

        // 2) قراءة ملخص البنوك ومطابقة البنك المطلوب
        $bank_summary = $this->{$this->MODEL_NAME}->batch_bank_summary($batch_id);
        $bank_data = null;
        if (is_array($bank_summary)) {
            foreach ($bank_summary as $b) {
                if ((int)($b['MASTER_BANK_NO'] ?? 0) === $master_bank_no) {
                    $bank_data = $b; break;
                }
            }
        }
        if (!$bank_data) { show_error('لم يتم العثور على هذا البنك في الدفعة', 404); return; }

        // 3) تجهيز البيانات
        $batch_no     = $batch_info['BATCH_NO']      ?? '';
        $bank_name    = $bank_data['BANK_NAME']      ?? '';
        $total_amount = (float)($bank_data['TOTAL_AMOUNT'] ?? 0);
        $emp_count    = (int)($bank_data['EMP_COUNT']    ?? 0);
        $is_wallet    = (int)($bank_data['PROVIDER_TYPE'] ?? 1) === 2;

        $cover_text = $is_wallet
            ? "صرف رواتب الدفعة {$batch_no} — {$bank_name} ({$emp_count} موظف)"
            : "تحويل رواتب الدفعة {$batch_no} لـ {$bank_name} ({$emp_count} موظف)";

        $invoice_date = $batch_info['BATCH_DATE'] ?? date('d/m/Y');
        if ($invoice_date && strpos($invoice_date, '/') === false && strpos($invoice_date, '-') !== false) {
            $invoice_date = date('d/m/Y', strtotime($invoice_date));
        }

        // 4) INSERT عبر model
        try {
            $new_id = $this->{$this->MODEL_NAME}->cover_create_from_batch(
                $batch_id, $master_bank_no, $bank_name, $total_amount,
                $cover_text, $batch_no, $invoice_date
            );
        } catch (Exception $e) {
            show_error('فشل إنشاء نموذج الصرف: ' . $e->getMessage(), 500);
            return;
        }

        // 5) redirect لصفحة العرض/التعديل
        redirect('payment/payment_cover/get/' . (int)$new_id);
    }

    /**
     * حذف نموذج صرف مرتبط بدفعة (للاستخدام عند فك الاحتساب)
     *   - يفحص العلاقة بـ BATCH_ID + MASTER_BANK_NO
     *   - يرفض الحذف لو في سند صرف منشأ
     *
     * URL: /payment_req/payment_req/cover_delete_from_batch/{batch_id}/{master_bank_no}
     * Returns JSON
     */
    function cover_delete_from_batch($batch_id = 0, $master_bank_no = 0)
    {
        header('Content-Type: application/json');
        $batch_id       = (int)$batch_id;
        $master_bank_no = (int)$master_bank_no;

        if ($batch_id <= 0) {
            echo json_encode(['ok' => 0, 'msg' => 'معرّف الدفعة غير صحيح']);
            return;
        }

        $result = $this->{$this->MODEL_NAME}->cover_delete_from_batch($batch_id, $master_bank_no);
        echo json_encode([
            'ok'       => $result['ok'] ? 1 : 0,
            'msg'      => $result['msg'] ?? '',
            'cover_id' => $result['cover_id'] ?? null,
            'fp_id'    => $result['fp_id'] ?? null,
        ]);
    }

    // 🆕 endpoint AJAX لجلب مرفقات طلب معيّن (يستخدم في batch_detail)
    function batch_req_attachments_json()
    {
        $req_id = (int)$this->input->get_post('req_id');
        if (!$req_id) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'req_id مفقود']);
            return;
        }
        $this->load->model('attachments/attachment_model');
        $rows = $this->attachment_model->get_list($req_id, 'payment_req');
        if (is_array($rows)) {
            array_walk_recursive($rows, function (&$v) {
                if (is_string($v)) $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8');
            });
        }
        // أضف معلومة "هل المستخدم الحالي رفعه" لإظهار زر الحذف
        $cur_user_id = method_exists($this, 'IsAuthorized') && function_exists('get_curr_user') ? (int)get_curr_user()->id : 0;
        $out = [];
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $r['IS_MINE'] = ((int)($r['ENTRY_USER'] ?? 0) === $cur_user_id) ? 1 : 0;
                $out[] = $r;
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'rows' => $out]);
    }

    function batch_history_data($id)
    {
        $rows = $this->{$this->MODEL_NAME}->batch_history_get((int)$id);
        if (is_array($rows)) {
            array_walk_recursive($rows, function (&$val) {
                if (is_string($val)) { $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
            });
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'data' => is_array($rows) ? $rows : []]);
    }

    /**
     * 🆕 تقرير طباعة شامل لدفعة معينة (محاسب — A4 RTL)
     * يحوي: رأس الدفعة + الشهور/الطلبات/الأنواع + تفصيل المقر بأنواع الصرف +
     *       ملخص حسب البنك/المحفظة + قسم التواقيع
     */
    function batch_print($id)
    {
        $batch_rows = $this->{$this->MODEL_NAME}->batch_history_list();
        $batch_info = null;
        if (is_array($batch_rows)) {
            foreach ($batch_rows as $b) {
                if ((int)($b['BATCH_ID'] ?? 0) == (int)$id) { $batch_info = $b; break; }
            }
        }
        if (!$batch_info) { redirect('payment_req/payment_req/batch_history'); return; }

        $detail_rows = $this->{$this->MODEL_NAME}->batch_history_get((int)$id);
        if (is_array($detail_rows)) {
            array_walk_recursive($detail_rows, function (&$val) {
                if (is_string($val)) { $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
            });
        }

        $req_ids_str = $batch_info['REQ_IDS'] ?? '';
        $batch_reqs = [];
        if ($req_ids_str) {
            $where = " AND M.REQ_ID IN ($req_ids_str) ";
            $reqs = $this->{$this->MODEL_NAME}->get_list_master($where, 0, 100);
            if (is_array($reqs)) {
                array_walk_recursive($reqs, function (&$val) {
                    if (is_string($val)) { $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
                });
                $batch_reqs = $reqs;
            }
        }

        $bank_summary = $this->{$this->MODEL_NAME}->batch_bank_summary((int)$id);
        if (is_array($bank_summary)) {
            array_walk_recursive($bank_summary, function (&$val) {
                if (is_string($val)) { $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
            });
        }

        // 🆕 تفصيل حسب البيان (PAY_TYPE)
        $pay_type_summary = $this->{$this->MODEL_NAME}->batch_pay_type_summary((int)$id);
        if (is_array($pay_type_summary)) {
            array_walk_recursive($pay_type_summary, function (&$val) {
                if (is_string($val)) { $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
            });
        }

        $this->load->model('settings/constant_details_model');
        $data['title']         = 'طباعة الدفعة — ' . ($batch_info['BATCH_NO'] ?? $id);
        $data['batch_info']    = $batch_info;
        $data['detail_rows']   = is_array($detail_rows) ? $detail_rows : [];
        $data['bank_summary']  = is_array($bank_summary) ? $bank_summary : [];
        $data['pay_type_summary'] = is_array($pay_type_summary) ? $pay_type_summary : [];
        $data['batch_reqs']    = $batch_reqs;
        $data['batch_status_cons'] = $this->constant_details_model->get_list(542);
        $data['user_name']     = $this->session->userdata('user_name') ?? '';

        // عرض مباشر بدون template (نظافة للطباعة)
        $this->load->view('payment_req_batch_print', $data);
    }

    /**
     * 🆕 تقرير المقارنات (A4): الموظفون المستثنون + السبب، توزيع الوجهة
     *     (بنك/محفظة/وريث)، لوحة أرقام علوية (KPIs)، ومقارنة بالشهر السابق.
     */
    // يجمع كل بيانات تقرير المقارنات (مشترك بين الطباعة وتصدير Excel)
    private function _batch_compare_data($id)
    {
        $id = (int)$id;
        $batch_rows = $this->{$this->MODEL_NAME}->batch_history_list();
        $batch_info = null;
        if (is_array($batch_rows)) {
            foreach ($batch_rows as $b) {
                if ((int)($b['BATCH_ID'] ?? 0) === $id) { $batch_info = $b; break; }
            }
        }
        if (!$batch_info) { return null; }

        $clean = function ($arr) {
            if (!is_array($arr)) return [];
            array_walk_recursive($arr, function (&$v) {
                if (is_string($v)) { $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8'); }
            });
            return $arr;
        };

        // الطلبات (لِـ KPIs: عدد الطلبات + المقرات + الأنواع + الشهور)
        $batch_reqs = [];
        $req_ids_str = trim((string)($batch_info['REQ_IDS'] ?? ''));
        if ($req_ids_str !== '') {
            $batch_reqs = $clean($this->{$this->MODEL_NAME}->get_list_master(" AND M.REQ_ID IN ($req_ids_str) ", 0, 500));
        }

        $bank_summary = $clean($this->{$this->MODEL_NAME}->batch_bank_summary($id));
        $dest_summary = $clean($this->{$this->MODEL_NAME}->batch_dest_summary($id));
        $excluded     = $clean($this->{$this->MODEL_NAME}->batch_excluded_emps($id));
        $trend        = $clean($this->{$this->MODEL_NAME}->batch_trend($id, 6));
        $detail_rows  = $clean($this->{$this->MODEL_NAME}->batch_history_get((int)$id));

        // قائمة الدفعات المتاحة للمقارنة (كل الدفعات عدا الحالية والملغية) — للـ dropdown
        $compare_options = [];
        if (is_array($batch_rows)) {
            foreach ($batch_rows as $b) {
                $bid = (int)($b['BATCH_ID'] ?? 0);
                if ($bid === $id || $bid <= 0) continue;
                if ((int)($b['STATUS'] ?? 0) === 9) continue;
                $compare_options[] = [
                    'BATCH_ID'     => $bid,
                    'BATCH_NO'     => $b['BATCH_NO'] ?? '',
                    'ENTRY_DATE'   => $b['ENTRY_DATE'] ?? '',
                    'TOTAL_AMOUNT' => (float)($b['TOTAL_AMOUNT'] ?? 0),
                    'EMP_COUNT'    => (int)($b['EMP_COUNT'] ?? 0),
                    'STATUS'       => (int)($b['STATUS'] ?? 0),
                    'STATUS_NAME'  => $b['STATUS_NAME'] ?? '',
                ];
            }
        }

        // الدفعة السابقة الافتراضية من الـ trend (أول صف غير IS_CURRENT)
        $prev = null;
        foreach ($trend as $t) {
            if ((int)($t['IS_CURRENT'] ?? 0) !== 1) { $prev = $t; break; }
        }
        $auto_prev_id = $prev ? (int)($prev['BATCH_ID'] ?? 0) : 0;

        // اختيار اليوزر (?prev=) يتجاوز الافتراضي إذا كان دفعة صحيحة
        $sel_prev = (int)$this->input->get('prev');
        $valid_ids = array_map(function ($o) { return (int)$o['BATCH_ID']; }, $compare_options);
        $prev_id = ($sel_prev > 0 && in_array($sel_prev, $valid_ids, true)) ? $sel_prev : $auto_prev_id;

        // بيانات الدفعة المقارَنة (للبطاقات: المبلغ + عدد الموظفين السابق)
        $prev_no = ''; $prev_info = null;
        foreach ($compare_options as $o) {
            if ((int)$o['BATCH_ID'] === $prev_id) { $prev_no = $o['BATCH_NO']; $prev_info = $o; break; }
        }

        // ملخص الفروقات (يُحسب من batch_diff per-employee — نفس منطق Excel)
        $diff = ['new'=>0,'left'=>0,'changed'=>0,'same'=>0,'inc'=>0.0,'dec'=>0.0,'amt_new'=>0.0,'amt_left'=>0.0];
        $diff_rows = [];
        if ($prev_id > 0) {
            $diff_rows = $clean($this->{$this->MODEL_NAME}->batch_diff($id, $prev_id));
            foreach ($diff_rows as $r) {
                $ct = $r['CHANGE_TYPE'] ?? ''; $d = (float)($r['DIFF'] ?? 0);
                if     ($ct === 'NEW')     { $diff['new']++;     $diff['amt_new']  += (float)($r['CURRENT_AMOUNT']  ?? 0); }
                elseif ($ct === 'LEFT')    { $diff['left']++;    $diff['amt_left'] += (float)($r['PREVIOUS_AMOUNT'] ?? 0); }
                elseif ($ct === 'CHANGED') { $diff['changed']++; if ($d > 0) $diff['inc'] += $d; else $diff['dec'] += abs($d); }
                elseif ($ct === 'SAME')    { $diff['same']++; }
            }
        }

        // 🆕 نفس التجميعات للدفعة المرجعية — كل قسم بالتقرير يصير مقارنة جنباً لجنب
        $prev_batch_info = null; $prev_reqs = []; $prev_bank = []; $prev_dest = []; $prev_details = [];
        if ($prev_id > 0) {
            if (is_array($batch_rows)) {
                foreach ($batch_rows as $b) {
                    if ((int)($b['BATCH_ID'] ?? 0) === $prev_id) { $prev_batch_info = $b; break; }
                }
            }
            $prev_req_ids = trim((string)($prev_batch_info['REQ_IDS'] ?? ''));
            if ($prev_req_ids !== '') {
                $prev_reqs = $clean($this->{$this->MODEL_NAME}->get_list_master(" AND M.REQ_ID IN ($prev_req_ids) ", 0, 500));
            }
            $prev_bank    = $clean($this->{$this->MODEL_NAME}->batch_bank_summary($prev_id));
            $prev_dest    = $clean($this->{$this->MODEL_NAME}->batch_dest_summary($prev_id));
            $prev_details = $clean($this->{$this->MODEL_NAME}->batch_history_get($prev_id));
        }

        // 🆕 ⑨ المقارنة حسب البند — البروسيجران يقارنان الدفعتين داخلياً
        //    (المرجعية null ⇒ وضع دفعة واحدة: كل البنود تظهر NEW والمرجعي أصفار)
        $pt_ref_id       = ($prev_id > 0) ? $prev_id : null;
        $pay_type_diff   = $clean($this->{$this->MODEL_NAME}->batch_pay_type_diff($id, $pt_ref_id));
        $pay_type_emps   = $clean($this->{$this->MODEL_NAME}->batch_pay_type_emp_diff($id, $pt_ref_id));

        // 🆕 ⑫ المقارنة حسب الشهر — المبلغ محصور بالدفعة عبر LINK.
        //    لا يمكن اشتقاقه من batch_reqs: مبالغ الماستر تشمل تفاصيل خارج الدفعة.
        $month_diff      = $clean($this->{$this->MODEL_NAME}->batch_month_diff($id, $pt_ref_id));

        // 🆕 ⑤ أنواع الطلبات — الأنواع الخمسة مفصولة فعلياً.
        //    detail_rows تدمج النوعين 3 و4 في AMT_TYPE3، فلا تصلح لجدول خماسي.
        $req_type_cur    = $clean($this->{$this->MODEL_NAME}->batch_pay_type_summary($id));
        $req_type_ref    = ($prev_id > 0) ? $clean($this->{$this->MODEL_NAME}->batch_pay_type_summary($prev_id)) : [];

        // 🆕 ⑩ حالة الموظف لكل شهر — للدفعتين معاً.
        //    بدون نسخة المرجعية يبقى الخارجون (LEFT) بلا حالة.
        $emp_status_cur  = $clean($this->{$this->MODEL_NAME}->batch_emp_month_statuses($id));
        $emp_status_ref  = ($prev_id > 0) ? $clean($this->{$this->MODEL_NAME}->batch_emp_month_statuses($prev_id)) : [];
        // تسطيح: رقم الموظف => آخر حالة معروفة (الحالة واحدة لكل موظف عملياً)
        $flatten_status = function ($rows) {
            $map = [];
            foreach ((array)$rows as $r) {
                $emp = (string)($r['EMP_NO'] ?? '');
                if ($emp === '') continue;
                $map[$emp] = (int)($r['EMP_STATUS'] ?? $r['DISPLAY_STATUS'] ?? 1);
            }
            return $map;
        };
        $emp_status_map = $flatten_status($emp_status_ref) + [];
        foreach ($flatten_status($emp_status_cur) as $k_emp => $v_st) { $emp_status_map[$k_emp] = $v_st; }

        $data = [];
        $data['title']        = 'تقرير المقارنات — ' . ($batch_info['BATCH_NO'] ?? $id);
        $data['batch_info']   = $batch_info;
        $data['batch_reqs']   = $batch_reqs;
        $data['bank_summary'] = $bank_summary;
        $data['dest_summary'] = $dest_summary;
        $data['excluded']     = $excluded;
        $data['trend']        = $trend;
        $data['prev_id']      = $prev_id;
        $data['prev_no']      = $prev_no;
        $data['prev_info']    = $prev_info;
        $data['compare_options'] = $compare_options;
        $data['cur_batch_id'] = $id;
        $data['diff']         = $diff;
        $data['detail_rows']  = $detail_rows;
        $data['diff_rows']    = $diff_rows;
        // 🆕 بيانات الدفعة المرجعية لكل الأبعاد
        $data['prev_batch_info'] = $prev_batch_info;
        $data['prev_reqs']       = $prev_reqs;
        $data['prev_bank']       = $prev_bank;
        $data['prev_dest']       = $prev_dest;
        $data['prev_details']    = $prev_details;
        // 🆕 ⑨ المقارنة حسب البند: ملخص البنود + تفصيل (موظف × بند)
        $data['pay_type_diff']   = $pay_type_diff;
        $data['pay_type_emps']   = $pay_type_emps;
        // 🆕 ⑩ ⑪ ⑫ + إصلاح ⑤
        $data['month_diff']      = $month_diff;
        $data['req_type_cur']    = $req_type_cur;
        $data['req_type_ref']    = $req_type_ref;
        $data['emp_status_map']  = $emp_status_map;
        // عدد أسماء الموظفين بكل فئة بقسم الموظفين (اختيار المستخدم من شريط التقرير)
        $topn_sel = (int)$this->input->get('topn');
        $data['topn'] = in_array($topn_sel, [10, 25, 50, 9999], true) ? $topn_sel : 25;
        // ⑨ اختيار المستخدم: عرض كل موظفي البند أم من تغيّر مبلغه فقط (الافتراضي: الكل)
        $pt_mode_sel = trim((string)$this->input->get('ptmode'));
        $data['pt_mode'] = in_array($pt_mode_sel, ['all', 'changed'], true) ? $pt_mode_sel : 'all';
        // ⑨ البند المختار للتفصيل: "isBenefit:payType" (مثل 1:305) أو 'all'
        // الصيغة فقط هنا — الفيو يتحقق أن البند موجود فعلاً في بيانات الدفعتين
        $pt_pick_sel = trim((string)$this->input->get('paytype'));
        $data['pt_pick'] = preg_match('/^[01]:\d{1,10}$/', $pt_pick_sel) ? $pt_pick_sel : 'all';
        $data['user_name']    = $this->session->userdata('user_name') ?? '';

        return $data;
    }

    function batch_compare_print($id)
    {
        $data = $this->_batch_compare_data($id);
        if (!$data) { redirect('payment_req/payment_req/batch_history'); return; }
        $this->load->view('payment_req_batch_compare_print', $data);
    }

    // تصدير المقارنة إلى Excel — أوراق = أبعاد المقارنة، والموظفون كاملون بلا سقف
    function batch_compare_export($id)
    {
        $data = $this->_batch_compare_data($id);
        if (!$data) { echo '<script>alert("الدفعة غير موجودة"); history.back();</script>'; return; }

        // 🆕 التصدير يطابق اختيارات الشريط: الأقسام المفعّلة + البند + وضع الموظفين
        // (سقف "أسماء الموظفين" لا يُطبَّق عمداً — الإكسل ملف تدقيق يشمل الجميع)
        $secs_param = trim((string)$this->input->get('secs'));
        $secs_on    = ($secs_param === '')
            ? range(1, 12)
            : array_values(array_filter(array_map('intval', explode(',', $secs_param))));
        $want_sec = function ($sec_no) use ($secs_on) { return in_array((int)$sec_no, $secs_on, true); };
        $pt_pick  = (string)($data['pt_pick'] ?? 'all');
        $pt_only_changed = (($data['pt_mode'] ?? 'all') === 'changed');

        $cur_batch        = $data['batch_info'];
        $has_ref_batch  = (int)$data['prev_id'] > 0;
        $ref_batch_meta     = $data['prev_info'];
        $ref_batch      = $data['prev_batch_info'];
        $cur_batch_no  = $cur_batch['BATCH_NO'] ?? '';
        $ref_batch_no   = $data['prev_no'] ?? '';

        // ═══ تجميعات (نفس منطق الـ view) ═══
        $aggregate_detail_rows = function ($rows) {
            $type = [1=>['amt'=>0.0,'emp'=>0], 2=>['amt'=>0.0,'emp'=>0], 3=>['amt'=>0.0,'emp'=>0], 4=>['amt'=>0.0,'emp'=>0]];
            $branch = [];
            foreach ((array)$rows as $row) {
                for ($kpi = 1; $kpi <= 4; $kpi++) {
                    $type_amt = (float)($row['AMT_TYPE' . $kpi] ?? 0);
                    if ($type_amt != 0) { $type[$kpi]['amt'] += $type_amt; $type[$kpi]['emp']++; }
                }
                $branch_name = $row['BRANCH_NAME'] ?? 'غير محدد';
                if (!isset($branch[$branch_name])) $branch[$branch_name] = ['count' => 0, 'amt' => 0.0];
                $branch[$branch_name]['count']++;
                $branch[$branch_name]['amt'] += (float)($row['TOTAL_AMOUNT'] ?? 0);
            }
            return [$type, $branch];
        };
        $group_by_bank = function ($rows) {
            $map = [];
            foreach ((array)$rows as $row) {
                $kpi = trim((string)($row['BANK_NAME'] ?? 'غير محدد'));
                if ($kpi === '') $kpi = 'غير محدد';
                if (!isset($map[$kpi])) $map[$kpi] = ['emp' => 0, 'amt' => 0.0, 'ptype' => (int)($row['PROVIDER_TYPE'] ?? 1)];
                $map[$kpi]['emp'] += (int)($row['EMP_COUNT'] ?? 0);
                $map[$kpi]['amt'] += (float)($row['TOTAL_AMOUNT'] ?? 0);
            }
            return $map;
        };
        $group_by_dest = function ($rows) {
            $map = [];
            foreach ((array)$rows as $row) {
                $kpi = trim((string)($row['CATEGORY'] ?? ''));
                if ($kpi === '') continue;
                $map[$kpi] = ['emp' => (int)($row['EMP_COUNT'] ?? 0), 'trx' => (int)($row['TRX_COUNT'] ?? 0), 'amt' => (float)($row['TOTAL_AMOUNT'] ?? 0)];
            }
            return $map;
        };
        $count_reqs_by_type = function ($rows) {
            $cur_row = [1=>0, 2=>0, 3=>0, 4=>0];
            foreach ((array)$rows as $req_row) {
                $req_type = (int)($req_row['REQ_TYPE'] ?? 0);
                if ($req_type === 5) $req_type = 4; elseif ($req_type === 4) $req_type = 3;
                if (isset($cur_row[$req_type])) $cur_row[$req_type]++;
            }
            return $cur_row;
        };
        // نفس ترتيب التقرير المطبوع: تنازلياً بمبلغ الدفعة الحالية ثم المرجعية
        $merge_row_keys = function ($cur_map, $ref_map) {
            $keys = array_keys($cur_map);
            foreach (array_keys($ref_map) as $key) { if (!in_array($key, $keys, true)) $keys[] = $key; }
            usort($keys, function ($key_a, $key_b) use ($cur_map, $ref_map) {
                $amt_a = isset($cur_map[$key_a]) ? (float)$cur_map[$key_a]['amt'] : -1;
                $amt_b = isset($cur_map[$key_b]) ? (float)$cur_map[$key_b]['amt'] : -1;
                if ($amt_a === $amt_b) {
                    $amt_a = isset($ref_map[$key_a]) ? (float)$ref_map[$key_a]['amt'] : 0;
                    $amt_b = isset($ref_map[$key_b]) ? (float)$ref_map[$key_b]['amt'] : 0;
                }
                return $amt_b <=> $amt_a;
            });
            return $keys;
        };

        list($cur_by_type, $cur_by_branch) = $aggregate_detail_rows($data['detail_rows']);
        list($ref_by_type, $ref_by_branch) = $aggregate_detail_rows($data['prev_details']);
        $cur_by_bank = $group_by_bank($data['bank_summary']);
        $ref_by_bank = $group_by_bank($data['prev_bank']);
        $cur_by_dest = $group_by_dest($data['dest_summary']);
        $ref_by_dest = $group_by_dest($data['prev_dest']);
        $cur_req_count_by_type = $count_reqs_by_type($data['batch_reqs']);
        $ref_req_count_by_type = $count_reqs_by_type($data['prev_reqs']);

        $req_type_labels = [1 => 'راتب كامل', 2 => 'راتب جزئي', 3 => 'دفعة من المستحقات', 4 => 'استحقاقات وإضافات'];
        // تسميات الأعمدة = أرقام الدفعات الفعلية
        $lbl_cur_batch = $cur_batch_no !== '' ? $cur_batch_no : 'الحالية';
        $lbl_ref_batch = $has_ref_batch && $ref_batch_no !== '' ? $ref_batch_no : 'المرجعية';
        $change_type_labels = ['NEW' => 'جديد', 'LEFT' => 'خرج', 'CHANGED' => 'تغيّر', 'SAME' => 'ثابت'];

        $spreadsheet = new Spreadsheet();
        $style_header_row = function ($sheet, $lastCol, $row = 1) {
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('1e293b');
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->getColor()->setRGB('FFFFFF');
        };
        $autosize_columns = function ($sheet, $lastCol) {
            foreach (range('A', $lastCol) as $cur_row) { $sheet->getColumnDimension($cur_row)->setAutoSize(true); }
        };
        // رأس تعريفي موحّد بأول كل ورقة
        $sheet_info_line = 'الدفعة: ' . $cur_batch_no . ' (' . ($cur_batch['STATUS_NAME'] ?? '') . ')'
                   . ($has_ref_batch ? ' — المرجعية: ' . $ref_batch_no . ' (' . (($ref_batch_meta['STATUS_NAME'] ?? '')) . ')' : ' — بلا دفعة مرجعية');
        // توثيق المرشّحات داخل الملف — لئلا يُقرأ تصديرٌ مُفلتَر على أنه شامل
        if ($pt_pick !== 'all') {
            $pt_pick_name = '';
            foreach ((array)($data['pay_type_diff'] ?? []) as $row) {
                if ((((int)($row['IS_BENEFIT'] ?? 0)) . ':' . (int)($row['PAY_TYPE'] ?? 0)) === $pt_pick) {
                    $pt_pick_name = trim((string)($row['PAY_TYPE_NAME'] ?? '')); break;
                }
            }
            if ($pt_pick_name !== '') $sheet_info_line .= ' — البند: ' . $pt_pick_name;
        }
        if ($pt_only_changed) $sheet_info_line .= ' — موظفو البنود: المتغيّرون فقط';

        // صيغ الأرقام حسب نوع العمود — مطابقة للتقرير المطبوع (n_format للمبالغ، fmt_int للأعداد)
        $NUM_FORMATS = ['money' => '#,##0.00', 'int' => '#,##0', 'pct' => '0.0', 'txt' => null,
                         // مبلغ يعرض شرطة بدل 0.00 — يقلّل ضجيج الجداول العريضة
                         'money_dash' => '#,##0.00;-#,##0.00;"–"'];

        // ═══ ورقة 1: الملخص — نفس مؤشرات القسم ① بالتقرير ═══
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('الملخص'); $sheet->setRightToLeft(true);
        $sheet->setCellValue('A1', $sheet_info_line);
        $sheet->mergeCells('A1:E1');
        $kpi_headers = ['البيان', $lbl_cur_batch, $lbl_ref_batch, 'الفرق', 'النسبة %'];
        $col_letter = 'A';
        foreach ($kpi_headers as $header_txt) { $sheet->setCellValue($col_letter . '2', $header_txt); $col_letter++; }
        $style_header_row($sheet, 'E', 2);

        $cur_total_amt = (float)($cur_batch['TOTAL_AMOUNT'] ?? 0);
        $cur_emp_count = (int)($cur_batch['EMP_COUNT'] ?? 0);
        $ref_meta      = $ref_batch ?: $ref_batch_meta;
        $ref_total_amt = $has_ref_batch ? (float)($ref_meta['TOTAL_AMOUNT'] ?? 0) : 0;
        $ref_emp_count = $has_ref_batch ? (int)($ref_meta['EMP_COUNT'] ?? 0) : 0;
        $cur_req_count = count((array)$data['batch_reqs']);
        $ref_req_count = count((array)$data['prev_reqs']);
        // أنواع الطلبات والشهور المغطاة (نفس حساب الـ view)
        $distinct_of = function ($rows, $col) {
            $vals = [];
            foreach ((array)$rows as $row) { $val = $row[$col] ?? ''; if ($val !== '') $vals[$val] = 1; }
            return count($vals);
        };

        //            البيان                 قيمة الحالية                       قيمة المرجعية (null = لا تُقارن)      مبلغ؟
        $kpi_rows = [
            ['إجمالي مبلغ الصرف',  $cur_total_amt,                              $has_ref_batch ? $ref_total_amt : null, true],
            ['عدد الموظفين',       $cur_emp_count,                              $has_ref_batch ? $ref_emp_count : null, false],
            ['عدد الحركات',        (int)($cur_batch['MOVEMENT_COUNT'] ?? 0),
                                   $has_ref_batch ? (int)(is_array($ref_batch) ? ($ref_batch['MOVEMENT_COUNT'] ?? 0) : 0) : null, false],
            ['عدد الطلبات',        $cur_req_count,                              $has_ref_batch ? $ref_req_count : null, false],
            ['متوسط الصرف للموظف', $cur_emp_count > 0 ? $cur_total_amt / $cur_emp_count : 0,
                                   $has_ref_batch ? ($ref_emp_count > 0 ? $ref_total_amt / $ref_emp_count : 0) : null, true],
            ['أنواع الطلبات',      $distinct_of($data['batch_reqs'], 'REQ_TYPE_NAME'),
                                   $has_ref_batch ? $distinct_of($data['prev_reqs'], 'REQ_TYPE_NAME') : null, false],
            ['الشهور المغطاة',     $distinct_of($data['batch_reqs'], 'THE_MONTH'),
                                   $has_ref_batch ? $distinct_of($data['prev_reqs'], 'THE_MONTH') : null, false],
            ['موظفون مستثنون',     count((array)$data['excluded']),             null, false],
        ];
        $kpi_no_pct = ['أنواع الطلبات', 'الشهور المغطاة'];
        $row_num = 3;
        foreach ($kpi_rows as $kpi_row) {
            list($kpi_label, $kpi_cur_val, $kpi_ref_val, $kpi_is_money) = $kpi_row;
            $fmt = $kpi_is_money ? $NUM_FORMATS['money'] : $NUM_FORMATS['int'];
            $sheet->setCellValue('A' . $row_num, $kpi_label);
            $sheet->setCellValue('B' . $row_num, $kpi_cur_val);
            $sheet->getStyle('B' . $row_num)->getNumberFormat()->setFormatCode($fmt);
            if ($kpi_ref_val !== null) {
                $sheet->setCellValue('C' . $row_num, $kpi_ref_val);
                $sheet->setCellValue('D' . $row_num, $kpi_cur_val - $kpi_ref_val);
                $sheet->getStyle('C' . $row_num . ':D' . $row_num)->getNumberFormat()->setFormatCode($fmt);
                // نفس قاعدة الشاشة: عدّات الفئات بلا نسبة — ٤→٣ ليست −25% مفيدة.
                // لولا ذلك لاختلف الإكسل عن التقرير في نفس الصف.
                if ((float)$kpi_ref_val != 0.0 && !in_array($kpi_label, $kpi_no_pct, true)) {
                    $sheet->setCellValue('E' . $row_num, round(($kpi_cur_val - $kpi_ref_val) / abs($kpi_ref_val) * 100, 1));
                    $sheet->getStyle('E' . $row_num)->getNumberFormat()->setFormatCode($NUM_FORMATS['pct']);
                }
            }
            $row_num++;
        }
        $autosize_columns($sheet, 'E');

        // ═══ مولّد أوراق المقارنة الموحّد ═══
        //   $col_types: نوع كل عمود (txt|int|money|pct) — يحدّد صيغة الرقم
        //   $sum_cols : فهارس الأعمدة التي يُجمعها صف "الإجمالي" (مطابقة لصفوف الإجمالي بالتقرير)
        //   $freeze_at: خلية التجميد — 'A3' يثبّت الرؤوس، 'D3' يثبّت الهوية أيضاً (أوراق عريضة)
        $add_compare_sheet = function ($title, $headers, $col_types, $sum_cols, $rows_fn, $freeze_at = 'A3')
            use ($spreadsheet, $style_header_row, $autosize_columns, $sheet_info_line, $NUM_FORMATS) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($title); $sheet->setRightToLeft(true);
            // حرف العمود عبر PhpSpreadsheet: chr(64+n) ينكسر بعد Z (أوراق عريضة)
            $col_at = function ($idx0) { return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx0 + 1); };
            $last_col = $col_at(count($headers) - 1);
            $sheet->setCellValue('A1', $sheet_info_line);
            $sheet->mergeCells('A1:' . $last_col . '1');
            $col_letter = 'A';
            foreach ($headers as $header_txt) { $sheet->setCellValue($col_letter . '2', $header_txt); $col_letter++; }
            $style_header_row($sheet, $last_col, 2);
            // رؤوس ملفوفة على سطرين: أسماء البنود طويلة
            $sheet->getStyle('A2:' . $last_col . '2')->getAlignment()->setWrapText(true);
            $sheet->getRowDimension(2)->setRowHeight(32);
            // تجميد: الرؤوس (وأعمدة الهوية في الأوراق العريضة) تبقى ظاهرة أثناء التمرير
            $sheet->freezePane($freeze_at);

            $row_num = 3;
            $totals  = array_fill_keys($sum_cols, 0.0);
            foreach ($rows_fn() as $row) {
                $col_letter = 'A';
                foreach ($row as $col_idx => $cell_val) {
                    $sheet->setCellValue($col_letter . $row_num, $cell_val);
                    if (in_array($col_idx, $sum_cols, true)) $totals[$col_idx] += (float)$cell_val;
                    $col_letter++;
                }
                $row_num++;
            }
            $data_last_row = $row_num - 1;

            // صف الإجمالي (فقط لو في بيانات) — يطابق صف total-row بالتقرير المطبوع
            if ($data_last_row >= 3 && !empty($sum_cols)) {
                $sheet->setCellValue('A' . $row_num, 'الإجمالي');
                foreach ($sum_cols as $col_idx) {
                    $sheet->setCellValue($col_at($col_idx) . $row_num, $totals[$col_idx]);
                }
                $sheet->getStyle('A' . $row_num . ':' . $last_col . $row_num)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row_num . ':' . $last_col . $row_num)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('e2e8f0');
                $row_num++;
            }

            // صيغة الأرقام لكل عمود حسب نوعه
            if ($data_last_row >= 3) {
                foreach ($col_types as $col_idx => $col_type) {
                    $fmt = $NUM_FORMATS[$col_type] ?? null;
                    if ($fmt === null) continue;
                    $letter = $col_at($col_idx);
                    $sheet->getStyle($letter . '3:' . $letter . ($row_num - 1))->getNumberFormat()->setFormatCode($fmt);
                }
            }
            $autosize_columns($sheet, $last_col);
            if ($data_last_row >= 3) $sheet->setAutoFilter('A2:' . $last_col . $data_last_row);
            return $sheet;
        };

        // ═══ ورقة 2: الفروع ═══
        if ($want_sec(6)) $add_compare_sheet(
            'الفروع',
            ['المقر', 'موظفون - ' . $lbl_cur_batch, 'موظفون - ' . $lbl_ref_batch, 'مبلغ ' . $lbl_cur_batch, 'مبلغ ' . $lbl_ref_batch, 'الفرق'],
            ['txt', 'int', 'int', 'money', 'money', 'money'],
            [1, 2, 3, 4, 5],
            function () use ($merge_row_keys, $cur_by_branch, $ref_by_branch) {
                $rows_out = [];
                foreach ($merge_row_keys($cur_by_branch, $ref_by_branch) as $branch_name) {
                    $cur_row = $cur_by_branch[$branch_name] ?? ['count' => 0, 'amt' => 0.0];
                    $ref_row = $ref_by_branch[$branch_name] ?? ['count' => 0, 'amt' => 0.0];
                    $rows_out[] = [$branch_name, $cur_row['count'], $ref_row['count'],
                                   $cur_row['amt'], $ref_row['amt'], $cur_row['amt'] - $ref_row['amt']];
                }
                return $rows_out;
            });

        // ═══ ورقة 3: البنوك ═══
        if ($want_sec(7)) $add_compare_sheet(
            'البنوك',
            ['النوع', 'البنك - المحفظة', 'موظفون - ' . $lbl_cur_batch, 'موظفون - ' . $lbl_ref_batch, 'مبلغ ' . $lbl_cur_batch, 'مبلغ ' . $lbl_ref_batch, 'الفرق'],
            ['txt', 'txt', 'int', 'int', 'money', 'money', 'money'],
            [4, 5, 6],   // الموظفون لا يُجمعون: الموزّع يظهر في كل بنك
            function () use ($merge_row_keys, $cur_by_bank, $ref_by_bank) {
                $rows_out = [];
                foreach ($merge_row_keys($cur_by_bank, $ref_by_bank) as $bank_name) {
                    $cur_row = $cur_by_bank[$bank_name] ?? ['emp' => 0, 'amt' => 0.0, 'ptype' => 1];
                    $ref_row = $ref_by_bank[$bank_name] ?? ['emp' => 0, 'amt' => 0.0, 'ptype' => $cur_row['ptype']];
                    $provider_type = isset($cur_by_bank[$bank_name]) ? $cur_row['ptype'] : $ref_row['ptype'];
                    $rows_out[] = [$provider_type === 2 ? 'محفظة' : 'بنك', $bank_name,
                                   $cur_row['emp'], $ref_row['emp'],
                                   $cur_row['amt'], $ref_row['amt'], $cur_row['amt'] - $ref_row['amt']];
                }
                return $rows_out;
            });

        // ═══ ورقة 4: نوع الوجهة ═══
        if ($want_sec(8)) $add_compare_sheet(
            'الوجهة',
            ['الوجهة', 'موظفون - ' . $lbl_cur_batch, 'موظفون - ' . $lbl_ref_batch, 'حركات - ' . $lbl_cur_batch, 'حركات - ' . $lbl_ref_batch, 'مبلغ ' . $lbl_cur_batch, 'مبلغ ' . $lbl_ref_batch, 'الفرق'],
            ['txt', 'int', 'int', 'int', 'int', 'money', 'money', 'money'],
            [3, 4, 5, 6, 7],   // الموظفون لا يُجمعون (الموزّع بين بنك ومحفظة يظهر بالوجهتين)؛ الحركات تُجمع
            function () use ($merge_row_keys, $cur_by_dest, $ref_by_dest) {
                $rows_out = [];
                foreach ($merge_row_keys($cur_by_dest, $ref_by_dest) as $category) {
                    $cur_row = $cur_by_dest[$category] ?? ['emp' => 0, 'trx' => 0, 'amt' => 0.0];
                    $ref_row = $ref_by_dest[$category] ?? ['emp' => 0, 'trx' => 0, 'amt' => 0.0];
                    $rows_out[] = [$category, $cur_row['emp'], $ref_row['emp'], $cur_row['trx'], $ref_row['trx'],
                                   $cur_row['amt'], $ref_row['amt'], $cur_row['amt'] - $ref_row['amt']];
                }
                return $rows_out;
            });

        // ═══ ورقة 5: الطلبات — الأنواع الخمسة مفصولة (مطابقة للقسم ⑤) ═══
        //     المصدر ملخّص البنود المحصور بالدفعة، لا أعمدة AMT_TYPE* التي تدمج 3+4.
        //     لا عمود "موظفون": غير قابل للجمع عبر البنود داخل النوع الواحد.
        $rt_sum_x = function ($rows) {
            $out = [];
            foreach ((array)$rows as $r) {
                $rt = (int)($r['REQ_TYPE'] ?? 0);
                if ($rt <= 0) continue;
                if (!isset($out[$rt])) $out[$rt] = ['name' => (string)($r['REQ_TYPE_NAME'] ?? ''), 'amt' => 0.0, 'trx' => 0];
                $out[$rt]['amt'] += (float)($r['TOTAL_AMOUNT'] ?? 0);
                $out[$rt]['trx'] += (int)($r['TRX_COUNT'] ?? 0);
                if ($out[$rt]['name'] === '' && !empty($r['REQ_TYPE_NAME'])) $out[$rt]['name'] = (string)$r['REQ_TYPE_NAME'];
            }
            return $out;
        };
        $rt_cnt_x = function ($rows) {
            $out = [];
            foreach ((array)$rows as $r) {
                $rt = (int)($r['REQ_TYPE'] ?? 0);
                if ($rt <= 0) continue;
                if (!isset($out[$rt])) $out[$rt] = ['n' => 0, 'name' => (string)($r['REQ_TYPE_NAME'] ?? '')];
                $out[$rt]['n']++;
            }
            return $out;
        };
        $rt_cur_x  = $rt_sum_x($data['req_type_cur'] ?? []);
        $rt_ref_x  = $rt_sum_x($data['req_type_ref'] ?? []);
        $rt_creq_x = $rt_cnt_x($data['batch_reqs'] ?? []);
        $rt_rreq_x = $rt_cnt_x($data['prev_reqs'] ?? []);
        $rt_names_x = [1 => 'راتب كامل', 2 => 'دفعة من الراتب', 3 => 'دفعة من المستحقات',
                       4 => 'مستحقات حسب الشهر', 5 => 'استحقاقات وإضافات'];
        if ($want_sec(4)) $add_compare_sheet(
            'الطلبات',
            ['نوع الطلب', 'طلبات - ' . $lbl_cur_batch, 'طلبات - ' . $lbl_ref_batch, 'حركات - ' . $lbl_cur_batch, 'حركات - ' . $lbl_ref_batch, 'مبلغ ' . $lbl_cur_batch, 'مبلغ ' . $lbl_ref_batch, 'الفرق', 'الحالة'],
            ['txt', 'int', 'int', 'int', 'int', 'money', 'money', 'money', 'txt'],
            [1, 2, 3, 4, 5, 6, 7],
            function () use ($rt_cur_x, $rt_ref_x, $rt_creq_x, $rt_rreq_x, $rt_names_x, $has_ref_batch) {
                $keys = array_unique(array_merge(array_keys($rt_cur_x), array_keys($rt_ref_x),
                                                 array_keys($rt_creq_x), array_keys($rt_rreq_x)));
                sort($keys);
                $rows_out = [];
                foreach ($keys as $rt) {
                    $c_amt = (float)($rt_cur_x[$rt]['amt'] ?? 0);
                    $p_amt = (float)($rt_ref_x[$rt]['amt'] ?? 0);
                    $c_req = (int)($rt_creq_x[$rt]['n'] ?? 0);
                    $p_req = (int)($rt_rreq_x[$rt]['n'] ?? 0);
                    if ($c_amt == 0.0 && $p_amt == 0.0 && $c_req === 0 && $p_req === 0) continue;
                    $nm = '';
                    foreach ([$rt_cur_x, $rt_ref_x, $rt_creq_x, $rt_rreq_x] as $src) {
                        if (!empty($src[$rt]['name'])) { $nm = $src[$rt]['name']; break; }
                    }
                    if ($nm === '') $nm = $rt_names_x[$rt] ?? ('نوع ' . $rt);
                    $state = '';
                    if ($has_ref_batch) {
                        if ($p_amt == 0.0 && $p_req === 0)      $state = 'جديد';
                        elseif ($c_amt == 0.0 && $c_req === 0)  $state = 'اختفى';
                    }
                    $rows_out[] = [$nm, $c_req, $p_req,
                                   (int)($rt_cur_x[$rt]['trx'] ?? 0), (int)($rt_ref_x[$rt]['trx'] ?? 0),
                                   $c_amt, $p_amt, $c_amt - $p_amt, $state];
                }
                return $rows_out;
            });

        // ═══ ورقة ⑩: حركة الموظفين + حالة الموظف ═══
        if ($has_ref_batch && $want_sec(2)) {
            $churn_lbls_x  = ['NEW' => 'دخلوا', 'LEFT' => 'خرجوا', 'CHANGED' => 'تغيّر مبلغهم', 'SAME' => 'بلا تغيير'];
            $st_lbls_x     = [1 => 'فعّال', 0 => 'متقاعد', 2 => 'متوفى', 4 => 'حساب مغلق'];
            $st_map_x      = (array)($data['emp_status_map'] ?? []);
            $diff_rows_x   = (array)($data['diff_rows'] ?? []);
            $add_compare_sheet(
                'حركة الموظفين والحالة',
                array_merge(['الحركة', 'عدد الموظفين', 'المبلغ'], array_values($st_lbls_x)),
                array_merge(['txt', 'int', 'money'], array_fill(0, count($st_lbls_x), 'int')),
                range(1, 2 + count($st_lbls_x) + 1),   // عدد الموظفين + المبلغ + أعمدة الحالات
                function () use ($churn_lbls_x, $st_lbls_x, $st_map_x, $diff_rows_x) {
                    $cnt = []; $amt = []; $cross = [];
                    foreach (array_keys($churn_lbls_x) as $k) { $cnt[$k] = 0; $amt[$k] = 0.0; $cross[$k] = []; }
                    foreach ($diff_rows_x as $r) {
                        $ct = (string)($r['CHANGE_TYPE'] ?? '');
                        if (!isset($cnt[$ct])) continue;
                        $cnt[$ct]++;
                        $amt[$ct] += ($ct === 'LEFT') ? (float)($r['PREVIOUS_AMOUNT'] ?? 0) : (float)($r['CURRENT_AMOUNT'] ?? 0);
                        $st = $st_map_x[(string)($r['EMP_NO'] ?? '')] ?? 1;
                        if (!isset($cross[$ct][$st])) $cross[$ct][$st] = 0;
                        $cross[$ct][$st]++;
                    }
                    $rows_out = [];
                    foreach ($churn_lbls_x as $k => $lbl) {
                        $row = [$lbl, $cnt[$k], $amt[$k]];
                        foreach (array_keys($st_lbls_x) as $st_k) { $row[] = (int)($cross[$k][$st_k] ?? 0); }
                        $rows_out[] = $row;
                    }
                    return $rows_out;
                });

            // أسماء الداخلين والخارجين — ورقة مستقلة تُسهّل المتابعة
            $add_compare_sheet(
                'دخلوا وخرجوا',
                ['الحركة', 'رقم الموظف', 'الاسم', 'المقر', 'الحالة', 'المبلغ'],
                ['txt', 'txt', 'txt', 'txt', 'txt', 'money'],
                [5],
                function () use ($st_lbls_x, $st_map_x, $diff_rows_x) {
                    $rows_out = [];
                    foreach (['NEW' => 'دخل', 'LEFT' => 'خرج'] as $k => $lbl) {
                        foreach ($diff_rows_x as $r) {
                            if ((string)($r['CHANGE_TYPE'] ?? '') !== $k) continue;
                            $st = $st_map_x[(string)($r['EMP_NO'] ?? '')] ?? 1;
                            $rows_out[] = [$lbl,
                                           (string)($r['EMP_NO'] ?? ''), (string)($r['EMP_NAME'] ?? ''),
                                           (string)($r['BRANCH_NAME'] ?? ''), $st_lbls_x[$st] ?? '',
                                           ($k === 'LEFT') ? (float)($r['PREVIOUS_AMOUNT'] ?? 0) : (float)($r['CURRENT_AMOUNT'] ?? 0)];
                        }
                    }
                    return $rows_out;
                }, 'D3');
        }

        // ═══ ورقة ⑪: توزيع الحركات ═══
        if ($want_sec(5)) {
            $mov_dist_x = function ($rows) {
                $d = [1 => 0, 2 => 0, 3 => 0];
                foreach ((array)$rows as $r) {
                    $sc = (int)($r['SPLIT_COUNT'] ?? 0);
                    if ($sc <= 0) $sc = 1;              // ACC_ID فارغ = حركة قديمة واحدة
                    $d[$sc <= 1 ? 1 : ($sc === 2 ? 2 : 3)]++;
                }
                return $d;
            };
            $mv_c = $mov_dist_x($data['detail_rows'] ?? []);
            $mv_r = $mov_dist_x($data['prev_details'] ?? []);
            $add_compare_sheet(
                'توزيع الحركات',
                ['عدد الحركات للموظف', 'موظفون - ' . $lbl_cur_batch, 'موظفون - ' . $lbl_ref_batch, 'الفرق'],
                ['txt', 'int', 'int', 'int'],
                [1, 2, 3],
                function () use ($mv_c, $mv_r) {
                    $lbls = [1 => 'حركة واحدة', 2 => 'حركتان', 3 => 'ثلاث حركات فأكثر'];
                    $rows_out = [];
                    foreach ($lbls as $k => $lbl) {
                        $rows_out[] = [$lbl, (int)$mv_c[$k], (int)$mv_r[$k], (int)$mv_c[$k] - (int)$mv_r[$k]];
                    }
                    return $rows_out;
                });
        }

        // ═══ ورقة ⑫: الشهور ═══
        if ($want_sec(3) && !empty($data['month_diff'])) {
            $month_rows_x = (array)$data['month_diff'];
            $add_compare_sheet(
                'الشهور',
                ['الشهر', 'طلبات - ' . $lbl_cur_batch, 'طلبات - ' . $lbl_ref_batch, 'موظفون - ' . $lbl_cur_batch, 'موظفون - ' . $lbl_ref_batch, 'مبلغ ' . $lbl_cur_batch, 'مبلغ ' . $lbl_ref_batch, 'الفرق', 'الحالة'],
                ['txt', 'int', 'int', 'int', 'int', 'money', 'money', 'money', 'txt'],
                [1, 2, 5, 6, 7],
                function () use ($month_rows_x) {
                    $state_lbl = ['NEW' => 'جديد', 'LEFT' => 'اختفى'];
                    $rows_out = [];
                    foreach ($month_rows_x as $mr) {
                        $ct = (string)($mr['CHANGE_TYPE'] ?? '');
                        $rows_out[] = [
                            (string)($mr['MONTH_LABEL'] ?? $mr['THE_MONTH'] ?? ''),
                            (int)($mr['CUR_REQ_CNT'] ?? 0), (int)($mr['REF_REQ_CNT'] ?? 0),
                            (int)($mr['CUR_EMP_CNT'] ?? 0), (int)($mr['REF_EMP_CNT'] ?? 0),
                            (float)($mr['CUR_AMOUNT'] ?? 0), (float)($mr['REF_AMOUNT'] ?? 0),
                            (float)($mr['CUR_AMOUNT'] ?? 0) - (float)($mr['REF_AMOUNT'] ?? 0),
                            $state_lbl[$ct] ?? '',
                        ];
                    }
                    return $rows_out;
                });
        }

        // ═══ ورقة 6: حركة الموظفين بين الدفعتين (ملخّص القسم ⑥) ═══
        if ($has_ref_batch && $want_sec(9)) {
            $emp_diff_totals = (array)$data['diff'];
            $add_compare_sheet(
                'حركة الموظفين',
                ['الحركة', 'عدد الموظفين', 'المبلغ'],
                ['txt', 'int', 'money'],
                [],
                function () use ($emp_diff_totals, $cur_total_amt, $ref_total_amt) {
                    $increase_amt = (float)($emp_diff_totals['inc'] ?? 0);
                    $decrease_amt = (float)($emp_diff_totals['dec'] ?? 0);
                    return [
                        ['موظفون جدد',    (int)($emp_diff_totals['new'] ?? 0),     (float)($emp_diff_totals['amt_new'] ?? 0)],
                        ['موظفون خرجوا',  (int)($emp_diff_totals['left'] ?? 0),    -(float)($emp_diff_totals['amt_left'] ?? 0)],
                        ['تغيّر مبلغهم',  (int)($emp_diff_totals['changed'] ?? 0), $increase_amt - $decrease_amt],
                        ['— منها زيادات', '',                                      $increase_amt],
                        ['— منها نقصان',  '',                                      -$decrease_amt],
                        ['بدون تغيير',    (int)($emp_diff_totals['same'] ?? 0),    0],
                        // نفس صف الإجمالي بالتقرير المطبوع
                        ['صافي التغيير في المبلغ', '', $cur_total_amt - $ref_total_amt],
                    ];
                });
        }

        // ═══ ورقة 7: الموظفون (كاملون — كل صفوف الفرق بلا سقف) ═══
        if ($want_sec(9)) $add_compare_sheet(
            'الموظفون',
            ['رقم الموظف', 'اسم الموظف', 'المقر', 'الحالة', 'مبلغ ' . $lbl_cur_batch, 'مبلغ ' . $lbl_ref_batch, 'الفرق', 'النسبة %'],
            ['txt', 'txt', 'txt', 'txt', 'money', 'money', 'money', 'pct'],  // رقم الموظف معرّف لا كمية
            [4, 5, 6],
            function () use ($data, $change_type_labels) {
                // نفس ترتيب التقرير المطبوع: جدد ← خرجوا ← زيادات ← نقصان ← بدون تغيير
                $buckets = ['NEW' => [], 'LEFT' => [], 'UP' => [], 'DOWN' => [], 'SAME' => []];
                foreach ((array)$data['diff_rows'] as $row) {
                    $change_type = $row['CHANGE_TYPE'] ?? '';
                    if     ($change_type === 'NEW')  $buckets['NEW'][]  = $row;
                    elseif ($change_type === 'LEFT') $buckets['LEFT'][] = $row;
                    elseif ($change_type === 'CHANGED') {
                        if ((float)($row['DIFF'] ?? 0) > 0) $buckets['UP'][] = $row;
                        else                               $buckets['DOWN'][] = $row;
                    } else $buckets['SAME'][] = $row;
                }
                usort($buckets['NEW'],  function ($a, $b) { return (float)$b['CURRENT_AMOUNT']  <=> (float)$a['CURRENT_AMOUNT']; });
                usort($buckets['LEFT'], function ($a, $b) { return (float)$b['PREVIOUS_AMOUNT'] <=> (float)$a['PREVIOUS_AMOUNT']; });
                usort($buckets['UP'],   function ($a, $b) { return (float)$b['DIFF'] <=> (float)$a['DIFF']; });
                usort($buckets['DOWN'], function ($a, $b) { return (float)$a['DIFF'] <=> (float)$b['DIFF']; });
                usort($buckets['SAME'], function ($a, $b) { return (float)$b['CURRENT_AMOUNT']  <=> (float)$a['CURRENT_AMOUNT']; });

                $rows_out = [];
                foreach ($buckets as $bucket_rows) {
                    foreach ($bucket_rows as $row) {
                        $cur_amt = (float)($row['CURRENT_AMOUNT'] ?? 0);
                        $ref_amt = (float)($row['PREVIOUS_AMOUNT'] ?? 0);
                        // نفس منطق النسبة بالتقرير: قيمة الباكج، وإلا تُحتسب
                        $pct = $row['DIFF_PCT'] ?? null;
                        if ($pct === null || $pct === '') {
                            $pct = ($ref_amt != 0.0) ? round(($cur_amt - $ref_amt) / abs($ref_amt) * 100, 1) : '';
                        } else {
                            $pct = round((float)$pct, 1);
                        }
                        $rows_out[] = [
                            (string)($row['EMP_NO'] ?? ''), $row['EMP_NAME'] ?? '', $row['BRANCH_NAME'] ?? '',
                            $change_type_labels[$row['CHANGE_TYPE'] ?? ''] ?? ($row['CHANGE_TYPE'] ?? ''),
                            $cur_amt, $ref_amt, (float)($row['DIFF'] ?? 0), $pct,
                        ];
                    }
                }
                return $rows_out;
            });

        // ═══ ورقة 8: المستثنون ═══
        if ($want_sec(10)) $add_compare_sheet(
            'المستثنون',
            ['رقم الموظف', 'اسم الموظف', 'المقر', 'عدد الطلبات', 'المبلغ المطلوب', 'السبب'],
            ['txt', 'txt', 'txt', 'int', 'money', 'txt'],  // رقم الموظف معرّف لا كمية
            [4],                                           // التقرير يجمع المبلغ فقط
            function () use ($data) {
                $rows_out = [];
                foreach ((array)$data['excluded'] as $row) {
                    $rows_out[] = [(string)($row['EMP_NO'] ?? ''), $row['EMP_NAME'] ?? '', $row['BRANCH_NAME'] ?? '',
                                   (int)($row['REQ_COUNT'] ?? 0), (float)($row['REQ_AMOUNT'] ?? 0), $row['REASON'] ?? ''];
                }
                return $rows_out;
            });

        // ═══ ورقة 9: ملخص البنود ═══
        // الموظفون لا يُجمعون: الموظف الواحد قد يأخذ أكثر من بند في نفس الدفعة
        if ($want_sec(12)) $add_compare_sheet(
            'البنود',
            ['البند', 'نوع الطلب', 'موظفون - ' . $lbl_cur_batch, 'موظفون - ' . $lbl_ref_batch,
             'مبلغ ' . $lbl_cur_batch, 'مبلغ ' . $lbl_ref_batch, 'الفرق', 'النسبة %', 'الحالة'],
            ['txt', 'txt', 'int', 'int', 'money', 'money', 'money', 'pct', 'txt'],
            [4, 5, 6],
            function () use ($data, $pt_pick) {
                $change_labels = ['NEW' => 'جديد', 'LEFT' => 'لم يتكرر', 'CHANGED' => 'تغيّر', 'SAME' => 'ثابت'];
                // نوع الطلب لكل بند — من طلبات الدفعتين (الطلب ببند واحد فمصدره قاطع)
                $req_types_by_pt = [];
                foreach ([$data['batch_reqs'] ?? [], $data['prev_reqs'] ?? []] as $req_list) {
                    foreach ((array)$req_list as $req_row) {
                        $req_pt = $req_row['PAY_TYPE'] ?? null;
                        if ($req_pt === null || $req_pt === '') continue;
                        $req_map_key = ((int)($req_row['REQ_TYPE'] ?? 0) === 5 ? 1 : 0) . ':' . (int)$req_pt;
                        $req_type_nm = trim((string)($req_row['REQ_TYPE_NAME'] ?? ''));
                        if ($req_type_nm !== '') $req_types_by_pt[$req_map_key][$req_type_nm] = 1;
                    }
                }
                $rows_out = [];
                foreach ((array)($data['pay_type_diff'] ?? []) as $row) {
                    // احترام اختيار البند من شريط التقرير
                    if ($pt_pick !== 'all'
                        && (((int)($row['IS_BENEFIT'] ?? 0)) . ':' . (int)($row['PAY_TYPE'] ?? 0)) !== $pt_pick) continue;
                    $pt_name = trim((string)($row['PAY_TYPE_NAME'] ?? ''));
                    if ($pt_name === '') $pt_name = 'بند رقم ' . (int)($row['PAY_TYPE'] ?? 0);
                    $pt_cur = (float)($row['CUR_AMOUNT'] ?? 0);
                    $pt_ref = (float)($row['REF_AMOUNT'] ?? 0);
                    $pt_is_ben  = ((int)($row['IS_BENEFIT'] ?? 0) === 1);
                    $pt_map_key = ($pt_is_ben ? 1 : 0) . ':' . (int)($row['PAY_TYPE'] ?? 0);
                    $pt_types   = array_keys($req_types_by_pt[$pt_map_key] ?? []);
                    if (empty($pt_types)) $pt_types = [$pt_is_ben ? 'استحقاقات وإضافات' : 'بند مستحقات'];
                    $rows_out[] = [
                        $pt_name,
                        implode('، ', $pt_types),
                        (int)($row['CUR_EMP_CNT'] ?? 0), (int)($row['REF_EMP_CNT'] ?? 0),
                        $pt_cur, $pt_ref, $pt_cur - $pt_ref,
                        // النسبة محسوبة محلياً عند غياب قيمة الباكج (المرجعي صفر)
                        ($row['DIFF_PCT'] !== null && $row['DIFF_PCT'] !== '')
                            ? (float)$row['DIFF_PCT']
                            : ($pt_ref != 0.0 ? round(($pt_cur - $pt_ref) / abs($pt_ref) * 100, 1) : ''),
                        $change_labels[$row['CHANGE_TYPE'] ?? ''] ?? '',
                    ];
                }
                return $rows_out;
            });

        // ═══ ورقة 10: البنود حسب الموظف — عرضياً (سطر لكل موظف، البنود أعمدة) ═══
        if ($want_sec(12)) {
        // كل بند عمودان متجاوران (الحالية/المرجعية) ليقع الفرق تحت العين مباشرة،
        // ثم إجمالي الموظف والفرق. أسهل للمقارنة من الشكل الطولي (سطر لكل بند).
        $pt_cols = [];   // مفتاح البند => اسمه، بترتيب التقرير (الأكبر مبلغاً أولاً)
        foreach ((array)($data['pay_type_diff'] ?? []) as $row) {
            $pt_col_key  = ((int)($row['IS_BENEFIT'] ?? 0)) . ':' . (int)($row['PAY_TYPE'] ?? 0);
            if ($pt_pick !== 'all' && $pt_col_key !== $pt_pick) continue;   // البند المختار فقط
            $pt_col_name = trim((string)($row['PAY_TYPE_NAME'] ?? ''));
            if ($pt_col_name === '') $pt_col_name = 'بند رقم ' . (int)($row['PAY_TYPE'] ?? 0);
            $pt_cols[$pt_col_key] = $pt_col_name;
        }

        $wide_headers   = ['رقم الموظف', 'اسم الموظف', 'المقر'];
        $wide_col_types = ['txt', 'txt', 'txt'];
        $wide_sum_cols  = [];
        $wide_idx = 3;
        foreach ($pt_cols as $pt_col_name) {
            $wide_headers[]   = $pt_col_name . ' · ' . $lbl_cur_batch;
            $wide_headers[]   = $pt_col_name . ' · ' . $lbl_ref_batch;
            // شرطة بدل 0.00: أغلب الخانات صفرية (الموظف لا يأخذ كل البنود)
            $wide_col_types[] = 'money_dash';
            $wide_col_types[] = 'money_dash';
            $wide_sum_cols[]  = $wide_idx;
            $wide_sum_cols[]  = $wide_idx + 1;
            $wide_idx += 2;
        }
        // ببند واحد: أعمدة "الإجمالي" تكرار حرفي لأعمدة البند — نكتفي بالفرق
        $wide_single = (count($pt_cols) === 1);
        if (!$wide_single) {
            $wide_headers[]   = 'الإجمالي · ' . $lbl_cur_batch;
            $wide_headers[]   = 'الإجمالي · ' . $lbl_ref_batch;
            array_push($wide_col_types, 'money', 'money');
            array_push($wide_sum_cols, $wide_idx, $wide_idx + 1);
            $wide_idx += 2;
        }
        $wide_headers[]   = 'الفرق';
        $wide_col_types[] = 'money';
        $wide_sum_cols[]  = $wide_idx;

        $wide_sheet = $add_compare_sheet(
            'البنود حسب الموظف',
            $wide_headers, $wide_col_types, $wide_sum_cols,
            function () use ($data, $pt_cols, $pt_only_changed, $wide_single) {
                // شبكة الموظفين: لكل موظف خانة لكل بند (المبلغ في الدفعتين)
                $emp_grid = [];
                foreach ((array)($data['pay_type_emps'] ?? []) as $row) {
                    $emp_no = (int)($row['EMP_NO'] ?? 0);
                    if ($emp_no <= 0) continue;
                    $cell_key = ((int)($row['IS_BENEFIT'] ?? 0)) . ':' . (int)($row['PAY_TYPE'] ?? 0);
                    if (!isset($pt_cols[$cell_key])) continue;                       // بند خارج الاختيار
                    if ($pt_only_changed && ($row['CHANGE_TYPE'] ?? '') === 'SAME') continue;
                    if (!isset($emp_grid[$emp_no])) {
                        $emp_grid[$emp_no] = [
                            'name'   => $row['EMP_NAME'] ?? '',
                            'branch' => $row['BRANCH_NAME'] ?? '',
                            'cells'  => [], 'tot_cur' => 0.0, 'tot_ref' => 0.0,
                        ];
                    }
                    $cell_cur = (float)($row['CUR_AMOUNT'] ?? 0);
                    $cell_ref = (float)($row['REF_AMOUNT'] ?? 0);
                    $emp_grid[$emp_no]['cells'][$cell_key] = [$cell_cur, $cell_ref];
                    $emp_grid[$emp_no]['tot_cur'] += $cell_cur;
                    $emp_grid[$emp_no]['tot_ref'] += $cell_ref;
                }
                // الأكبر حركةً أولاً — نفس منطق ترتيب التقرير المطبوع
                uasort($emp_grid, function ($emp_a, $emp_b) {
                    $diff_a = abs($emp_a['tot_cur'] - $emp_a['tot_ref']);
                    $diff_b = abs($emp_b['tot_cur'] - $emp_b['tot_ref']);
                    if ($diff_a == $diff_b) return $emp_b['tot_cur'] <=> $emp_a['tot_cur'];
                    return $diff_b <=> $diff_a;
                });

                $rows_out = [];
                foreach ($emp_grid as $emp_no => $emp_row) {
                    $line = [(string)$emp_no, $emp_row['name'], $emp_row['branch']];
                    foreach (array_keys($pt_cols) as $pt_col_key) {
                        $cell = $emp_row['cells'][$pt_col_key] ?? [0.0, 0.0];
                        $line[] = $cell[0];
                        $line[] = $cell[1];
                    }
                    if (!$wide_single) {
                        $line[] = $emp_row['tot_cur'];
                        $line[] = $emp_row['tot_ref'];
                    }
                    $line[] = $emp_row['tot_cur'] - $emp_row['tot_ref'];
                    $rows_out[] = $line;
                }
                return $rows_out;
            },
            'D3');   // تجميد: رقم/اسم/مقر الموظف يبقون ظاهرين أثناء التمرير يميناً

        // تمييز مجموعات البنود — ثلاث طبقات تتكامل:
        //   (1) إطار حول كل بند   = الفصل الحقيقي (يعمل حتى بالطباعة أبيض/أسود)
        //   (2) تظليل خفيف متناوب = تتبّع المجموعة أفقياً
        //   (3) عمود المرجعية رمادي = تمييز فوري بين "الحالية" و"المرجعية" داخل المجموعة
        // ⚠️ التظليل يبدأ من صف البيانات (3) لا من صف الرؤوس — رؤوس الجدول نصّها أبيض
        //    على خلفية داكنة، وتظليلها يجعل النص غير مقروء [[نفس فخ .pt-total سابقاً]]
        if ($wide_sheet) {
            $band_at = function ($idx0) { return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx0 + 1); };
            $band_last_row = $wide_sheet->getHighestRow();
            // صف "الإجمالي" له تظليله الرمادي الخاص — نستثنيه من تظليل المجموعات
            // كي لا يخرج مخطّطاً؛ الإطار يبقى شاملاً له ليغلق الصندوق
            $band_has_total  = ((string)$wide_sheet->getCell('A' . $band_last_row)->getValue() === 'الإجمالي');
            $band_data_last  = $band_has_total ? ($band_last_row - 1) : $band_last_row;
            $band_idx = 3;
            $band_no  = 0;
            foreach ($pt_cols as $ignored_name) {
                $band_head = $band_at($band_idx) . '2:' . $band_at($band_idx + 1) . $band_last_row;
                $band_data = $band_at($band_idx) . '3:' . $band_at($band_idx + 1) . $band_data_last;
                $band_ref  = $band_at($band_idx + 1) . '3:' . $band_at($band_idx + 1) . $band_data_last;

                if ($band_no % 2 === 1) {
                    $wide_sheet->getStyle($band_data)
                        ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('eff6ff');   // أزرق هادئ — الإطار هو الفاصل
                }
                // عمود الدفعة المرجعية بلون باهت: الرقم الحالي يبقى هو البارز
                $wide_sheet->getStyle($band_ref)->getFont()->getColor()->setRGB('64748b');
                // إطار المجموعة (يشمل الرأس ليكتمل الصندوق)
                $wide_sheet->getStyle($band_head)->getBorders()->getOutline()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM)
                    ->getColor()->setRGB('475569');
                $band_idx += 2; $band_no++;
            }
            // صندوق أعمدة الهوية (المجمّدة) ليكتمل الفصل عند حدّ التجميد
            $wide_sheet->getStyle('A2:C' . $band_last_row)->getBorders()->getOutline()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM)
                ->getColor()->setRGB('475569');
        }

        }   // نهاية شرط القسم ⑨ (ورقة 10 العرضية)

        $spreadsheet->setActiveSheetIndex(0);
        $filename = 'مقارنة_' . preg_replace('/[^A-Za-z0-9\-]/', '', $cur_batch_no)
                  . ($has_ref_batch ? '_مقابل_' . preg_replace('/[^A-Za-z0-9\-]/', '', $ref_batch_no) : '') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    function batch_emp_accounts_json()
    {
        $batch_id = (int)$this->input->get_post('batch_id');
        $emp_no   = (int)$this->input->get_post('emp_no');
        $rows = $this->{$this->MODEL_NAME}->batch_emp_accounts($batch_id, $emp_no);
        if (is_array($rows)) {
            array_walk_recursive($rows, function (&$val) {
                if (is_string($val)) { $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
            });
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'data' => is_array($rows) ? $rows : []]);
    }

    // ============================================================
    // 🆕 مقارنة الدفعات: Trend + Diff + Diff Summary + Excel Export
    // ============================================================
    function batch_trend_json()
    {
        $batch_id = (int)$this->input->get_post('batch_id');
        $limit    = (int)$this->input->get_post('limit') ?: 6;
        $rows = $this->{$this->MODEL_NAME}->batch_trend($batch_id, $limit);
        if (is_array($rows)) {
            array_walk_recursive($rows, function (&$val) {
                if (is_string($val)) { $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
            });
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'rows' => is_array($rows) ? $rows : []]);
    }

    function batch_diff_json()
    {
        $cur = (int)$this->input->get_post('cur_batch_id');
        $prv = (int)$this->input->get_post('prv_batch_id');
        if (!$cur || !$prv) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'يجب تحديد دفعتين للمقارنة']);
            return;
        }
        $summary = $this->{$this->MODEL_NAME}->batch_diff_summary($cur, $prv);
        $rows    = $this->{$this->MODEL_NAME}->batch_diff($cur, $prv);
        foreach ([&$summary, &$rows] as &$d) {
            if (is_array($d)) {
                array_walk_recursive($d, function (&$val) {
                    if (is_string($val)) { $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
                });
            }
        }
        header('Content-Type: application/json');
        echo json_encode([
            'ok'      => true,
            'summary' => is_array($summary) && !empty($summary) ? $summary[0] : [],
            'rows'    => is_array($rows) ? $rows : [],
        ]);
    }

    function batch_diff_export()
    {
        $cur = (int)$this->input->get('cur_batch_id');
        $prv = (int)$this->input->get('prv_batch_id');
        if (!$cur || !$prv) { show_error('يجب تحديد دفعتين'); return; }

        $rows = $this->{$this->MODEL_NAME}->batch_diff($cur, $prv);
        if (!is_array($rows)) $rows = [];

        // أسماء الدفعات للملف
        $list = $this->{$this->MODEL_NAME}->batch_history_list();
        $cur_no = $cur; $prv_no = $prv;
        if (is_array($list)) {
            foreach ($list as $b) {
                if ((int)($b['BATCH_ID'] ?? 0) === $cur) $cur_no = $b['BATCH_NO'] ?? $cur;
                if ((int)($b['BATCH_ID'] ?? 0) === $prv) $prv_no = $b['BATCH_NO'] ?? $prv;
            }
        }

        $sp = new Spreadsheet();
        $sp->getProperties()->setTitle("Diff $cur_no vs $prv_no");

        // ─── ورقة 1: ملخص ───
        $s = $sp->getActiveSheet();
        $s->setTitle('ملخص');
        $s->setRightToLeft(true);
        $cnt_new = 0; $cnt_left = 0; $cnt_changed = 0; $cnt_same = 0;
        $amt_new = 0; $amt_left = 0; $amt_inc = 0; $amt_dec = 0;
        foreach ($rows as $r) {
            $ct = $r['CHANGE_TYPE'] ?? '';
            $diff = (float)($r['DIFF'] ?? 0);
            if ($ct === 'NEW')     { $cnt_new++;     $amt_new  += (float)($r['CURRENT_AMOUNT'] ?? 0); }
            if ($ct === 'LEFT')    { $cnt_left++;    $amt_left += (float)($r['PREVIOUS_AMOUNT'] ?? 0); }
            if ($ct === 'CHANGED') { $cnt_changed++; if ($diff > 0) $amt_inc += $diff; else $amt_dec += abs($diff); }
            if ($ct === 'SAME')    { $cnt_same++; }
        }
        $s->fromArray([
            ['ملخص مقارنة الدفعات'],
            [],
            ['الدفعة الحالية',    $cur_no],
            ['الدفعة المقارَنة',  $prv_no],
            [],
            ['الفئة', 'العدد', 'المبلغ'],
            ['موظفون جدد',         $cnt_new,     $amt_new],
            ['موظفون خرجوا',       $cnt_left,    $amt_left],
            ['موظفون تغيّر مبلغهم', $cnt_changed, $amt_inc - $amt_dec],
            ['موظفون بدون تغيير',   $cnt_same,    0],
            [],
            ['زيادات',  '', $amt_inc],
            ['تخفيضات', '', $amt_dec],
            ['صافي التغيير', '', $amt_inc - $amt_dec],
        ], null, 'A1');
        foreach (range('A','C') as $col) $s->getColumnDimension($col)->setWidth(28);
        $s->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $s->getStyle('A6:C6')->getFont()->setBold(true);
        $s->getStyle('A6:C6')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1E40AF');
        $s->getStyle('A6:C6')->getFont()->getColor()->setRGB('FFFFFF');

        // ─── ورقة 2: جدد ───
        $sn = $sp->createSheet();
        $sn->setTitle('جدد');
        $this->_diff_sheet_fill($sn, $rows, 'NEW', ['#', 'رقم الموظف', 'الاسم', 'المقر', 'مبلغ الدفعة الحالية']);

        // ─── ورقة 3: خرجوا ───
        $sl = $sp->createSheet();
        $sl->setTitle('خرجوا');
        $this->_diff_sheet_fill($sl, $rows, 'LEFT', ['#', 'رقم الموظف', 'الاسم', 'المقر', 'مبلغ الدفعة السابقة']);

        // ─── ورقة 4: تعديلات ───
        $sc = $sp->createSheet();
        $sc->setTitle('تعديلات');
        $this->_diff_sheet_fill($sc, $rows, 'CHANGED', ['#', 'رقم الموظف', 'الاسم', 'المقر', 'الحالي', 'السابق', 'الفرق', 'النسبة %']);

        // إخراج
        $fname = "diff_{$cur_no}_vs_{$prv_no}.xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        header('Cache-Control: max-age=0');
        $w = new Xlsx($sp);
        $w->save('php://output');
        exit;
    }

    // ============================================================
    // 🆕 تصدير شامل للدفعة — 3 أوراق:
    //    1) الموظفون (سطر لكل موظف بكل التفاصيل)
    //    2) المستفيدون (سطر لكل مستلم + بنك + هوية + IBAN)
    //    3) ملخص البنوك
    // ============================================================
    function batch_full_export($id)
    {
        $id = (int)$id;
        if (!$id) { show_error('batch_id مفقود'); return; }

        $batch_rows = $this->{$this->MODEL_NAME}->batch_history_list();
        $batch_info = null;
        if (is_array($batch_rows)) {
            foreach ($batch_rows as $b) {
                if ((int)($b['BATCH_ID'] ?? 0) === $id) { $batch_info = $b; break; }
            }
        }
        if (!$batch_info) { show_error('الدفعة غير موجودة'); return; }
        $batch_no = $batch_info['BATCH_NO'] ?? $id;
        $batch_date = $batch_info['ENTRY_DATE'] ?? '';
        $batch_status_name = $batch_info['STATUS_NAME'] ?? '';

        // ─── البيانات ───
        $emps = $this->{$this->MODEL_NAME}->batch_history_get($id);
        if (is_array($emps)) {
            array_walk_recursive($emps, function (&$v) { if (is_string($v)) $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8'); });
        } else $emps = [];

        $rcps = $this->{$this->MODEL_NAME}->batch_bank_export($id);
        if (is_array($rcps)) {
            array_walk_recursive($rcps, function (&$v) { if (is_string($v)) $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8'); });
        } else $rcps = [];

        $bsm = $this->{$this->MODEL_NAME}->batch_bank_summary($id);
        if (is_array($bsm)) {
            array_walk_recursive($bsm, function (&$v) { if (is_string($v)) $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8'); });
        } else $bsm = [];

        // 🆕 ترتيب أبجدي عربي مع توحيد الألف (إ/أ/آ → ا، ى → ي، ة → ه)
        $norm_ar = function ($s) {
            return strtr(trim((string)$s), [
                'إ' => 'ا', 'أ' => 'ا', 'آ' => 'ا',
                'ى' => 'ي', 'ة' => 'ه',
            ]);
        };
        // اسم الموظفين أبجدياً
        usort($emps, function ($a, $b) use ($norm_ar) {
            return strcmp($norm_ar($a['EMP_NAME'] ?? ''), $norm_ar($b['EMP_NAME'] ?? ''));
        });
        // اسم المستلم: وريث > مالك حساب > الموظف نفسه
        $rcp_display_name = function ($r) {
            if (!empty($r['BENEFICIARY_ID'])) return trim((string)($r['BENEF_NAME'] ?? ''));
            $own = trim((string)($r['OWNER_NAME'] ?? ''));
            return $own !== '' ? $own : trim((string)($r['EMP_NAME'] ?? ''));
        };
        usort($rcps, function ($a, $b) use ($norm_ar, $rcp_display_name) {
            return strcmp($norm_ar($rcp_display_name($a)), $norm_ar($rcp_display_name($b)));
        });

        // خريطة حالة الموظف من recipients (display_status) — للشيت 1
        $emp_status_map = [];
        foreach ($rcps as $r) {
            $eno = (int)($r['EMP_NO'] ?? 0);
            if ($eno && !isset($emp_status_map[$eno])) {
                $emp_status_map[$eno] = (int)($r['EMP_DISPLAY_STATUS'] ?? 1);
            }
        }
        $status_label = function ($s) {
            switch ((int)$s) {
                case 1: return 'فعّال';
                case 0: return 'متقاعد';
                case 2: return 'متوفى';
                case 4: return 'حساب مغلق';
                default: return '-';
            }
        };

        // ─── ابني الـ Spreadsheet ───
        $sp = new Spreadsheet();
        $sp->getProperties()->setTitle("تفاصيل دفعة $batch_no");

        // ════════════════════════════════════════
        // Sheet 1: الموظفون
        // ════════════════════════════════════════
        $s1 = $sp->getActiveSheet();
        $s1->setTitle('الموظفون');
        $s1->setRightToLeft(true);

        // Header info
        $s1->fromArray([['تفاصيل الدفعة — الموظفون']], null, 'A1');
        $s1->fromArray([['رقم الدفعة', $batch_no, 'التاريخ', $batch_date, 'الحالة', $batch_status_name]], null, 'A2');
        $s1->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $s1->mergeCells('A1:O1');
        $s1->getStyle('A2:F2')->getFont()->setBold(true)->setSize(10);

        // Headers row 4
        $hdrs1 = ['#', 'رقم الموظف', 'الاسم', 'الحالة', 'المقر', 'الطلبات',
                 'راتب كامل', 'راتب جزئي', 'دفعة من المستحقات', 'استحقاقات وإضافات',
                 'الإجمالي', 'حسابات نشطة', 'حسابات موقوفة', 'ورثة', 'توزيع', 'ملاحظات'];
        $s1->fromArray($hdrs1, null, 'A4');
        $hr1 = 'A4:' . $this->_col(count($hdrs1)) . '4';
        $s1->getStyle($hr1)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $s1->getStyle($hr1)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1E40AF');
        $s1->getStyle($hr1)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $r = 5; $i = 0;
        $sum_t1 = 0; $sum_t2 = 0; $sum_t3 = 0; $sum_t4 = 0; $sum_amt = 0;
        foreach ($emps as $e) {
            $i++;
            $eno   = (int)($e['EMP_NO'] ?? 0);
            $bcnt  = (int)($e['BENEF_COUNT'] ?? 0);
            $acnt  = (int)($e['ACTIVE_ACC_COUNT'] ?? 0);
            $icnt  = (int)($e['INACTIVE_ACC_COUNT'] ?? 0);
            $scnt  = (int)($e['SPLIT_COUNT'] ?? 0);
            $t1 = (float)($e['AMT_TYPE1'] ?? 0);
            $t2 = (float)($e['AMT_TYPE2'] ?? 0);
            $t3 = (float)($e['AMT_TYPE3'] ?? 0);
            $t4 = (float)($e['AMT_TYPE4'] ?? 0);
            $tot = (float)($e['TOTAL_AMOUNT'] ?? 0);
            $sum_t1 += $t1; $sum_t2 += $t2; $sum_t3 += $t3; $sum_t4 += $t4; $sum_amt += $tot;
            $notes = [];
            if ($bcnt > 0 && $scnt == 0) $notes[] = '⚠ على الحساب القديم';
            if ($acnt > 1)               $notes[] = $acnt . ' حسابات نشطة';
            if ($icnt > 0)               $notes[] = $icnt . ' حساب موقوف';
            $s1->fromArray([
                $i,
                $eno,
                $e['EMP_NAME'] ?? '',
                $status_label($emp_status_map[$eno] ?? 1),
                $e['BRANCH_NAME'] ?? '',
                $e['REQ_NOS'] ?? '',
                $t1, $t2, $t3, $t4, $tot,
                $acnt,
                $icnt,
                $bcnt,
                $scnt,
                implode(' · ', $notes),
            ], null, "A$r");
            $r++;
        }
        // إجمالي
        $s1->fromArray([
            '', '', 'الإجمالي العام', '', '', '',
            $sum_t1, $sum_t2, $sum_t3, $sum_t4, $sum_amt,
            '', '', '', '', ''
        ], null, "A$r");
        $totRange = "A$r:" . $this->_col(count($hdrs1)) . $r;
        $s1->getStyle($totRange)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $s1->getStyle($totRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1E293B');

        // currency formatting cols G..K (7..11)
        for ($cc = 7; $cc <= 11; $cc++) {
            $s1->getStyle($this->_col($cc) . '5:' . $this->_col($cc) . $r)
               ->getNumberFormat()->setFormatCode('#,##0.00');
        }
        // widths
        $w1 = [5, 10, 28, 11, 14, 22, 13, 13, 16, 18, 14, 10, 10, 8, 8, 28];
        foreach ($w1 as $idx => $w) $s1->getColumnDimension($this->_col($idx + 1))->setWidth($w);
        $s1->freezePane('A5');

        // ════════════════════════════════════════
        // Sheet 2: المستفيدون
        // ════════════════════════════════════════
        $s2 = $sp->createSheet();
        $s2->setTitle('المستفيدون');
        $s2->setRightToLeft(true);

        $s2->fromArray([['تفاصيل الدفعة — المستفيدون (الحركات البنكية الفعلية)']], null, 'A1');
        $s2->fromArray([['رقم الدفعة', $batch_no, 'التاريخ', $batch_date]], null, 'A2');
        $s2->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $s2->mergeCells('A1:N1');
        $s2->getStyle('A2:D2')->getFont()->setBold(true)->setSize(10);

        $hdrs2 = ['#', 'رقم الموظف', 'اسم الموظف', 'المقر',
                  'نوع المستلم', 'اسم المستلم', 'العلاقة', 'رقم الهوية',
                  'البنك / المحفظة', 'الفرع', 'رقم الحساب', 'IBAN',
                  'رقم الجوال', 'المبلغ'];
        $s2->fromArray($hdrs2, null, 'A4');
        $hr2 = 'A4:' . $this->_col(count($hdrs2)) . '4';
        $s2->getStyle($hr2)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $s2->getStyle($hr2)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('166534');
        $s2->getStyle($hr2)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // اسم الموظف الأصلي + المقر — من الشيت 1
        $emp_extra = [];
        foreach ($emps as $e) {
            $eno = (int)($e['EMP_NO'] ?? 0);
            if ($eno) $emp_extra[$eno] = ['name' => $e['EMP_NAME'] ?? '', 'branch' => $e['BRANCH_NAME'] ?? ''];
        }

        $r2 = 5; $j = 0; $sum_rcp = 0;
        foreach ($rcps as $rR) {
            $eno   = (int)($rR['EMP_NO'] ?? 0);
            $alloc = (float)($rR['TOTAL_AMOUNT'] ?? 0);
            if ($alloc <= 0) continue;
            $j++;
            $sum_rcp += $alloc;

            $owner = trim($rR['OWNER_NAME'] ?? '');
            $eName = trim($rR['EMP_NAME'] ?? ($emp_extra[$eno]['name'] ?? ''));
            $type = 'الموظف نفسه'; $relation = '';
            if (!empty($rR['BENEFICIARY_ID'])) {
                $type     = 'وريث';
                $relation = $rR['BENEF_REL_NAME'] ?? '';
                $rcpName  = trim($rR['BENEF_NAME'] ?? $owner);
            } elseif ($owner !== '' && $owner !== $eName) {
                $type     = 'صاحب حساب';
                $rcpName  = $owner;
            } else {
                $rcpName  = $eName;
            }
            $is_wallet = (int)($rR['PROVIDER_TYPE'] ?? 1) === 2;
            $bank_name = $rR['MASTER_BANK_NAME'] ?? '';
            $bank_branch = $is_wallet ? '' : ($rR['BANK_BRANCH_NAME'] ?? '');
            $iban      = $rR['IBAN'] ?? '';
            $id_no     = $rR['OWNER_ID_NO'] ?? '';
            $owner_phone = $rR['OWNER_PHONE'] ?? '';

            // 🆕 منطق المحفظة: رقم الحساب = رقم الجوال (المحفظة = هاتف)
            //    ترتيب fallback: WALLET_NUMBER → OWNER_PHONE → BANK_ACCOUNT (للأمان)
            if ($is_wallet) {
                $w = trim((string)($rR['WALLET_NUMBER'] ?? ''));
                if ($w === '' || $w === '0') $w = trim((string)$owner_phone);
                if ($w === '' || $w === '0') $w = trim((string)($rR['BANK_ACCOUNT'] ?? ''));
                // ضمان صفر بادئ (الجوال الفلسطيني يبدأ بـ 0)
                if ($w !== '' && substr($w, 0, 1) !== '0' && strlen($w) === 9) $w = '0' . $w;
                $acc_no = $w;
                $phone  = $w;   // نفس الرقم في عمود الجوال للوضوح
            } else {
                // بنك: BANK_ACCOUNT أولاً (snapshot)، ثم ACCOUNT_NO live
                $a = trim((string)($rR['BANK_ACCOUNT'] ?? ''));
                if ($a === '' || $a === '0') $a = trim((string)($rR['ACCOUNT_NO'] ?? ''));
                $acc_no = $a;
                $phone  = $owner_phone;
            }

            $s2->fromArray([
                $j,
                $eno,
                $eName,
                $emp_extra[$eno]['branch'] ?? '',
                $type,
                $rcpName,
                $relation,
                (string)$id_no,           // رقم الهوية كنص لتجنّب تحويل صفرات بادئة
                $bank_name,
                $bank_branch,
                (string)$acc_no,
                (string)$iban,
                (string)$phone,
                $alloc,
            ], null, "A$r2");
            // فرض التنسيق "نص" لعمود الهوية والحساب والـ IBAN والجوال
            foreach (['H','K','L','M'] as $cc) {
                $s2->getStyle($cc . $r2)->getNumberFormat()->setFormatCode('@');
            }
            $r2++;
        }
        // إجمالي
        $s2->fromArray([
            '', '', '', '', '', '', '', '', '', '', '', '', 'الإجمالي', $sum_rcp
        ], null, "A$r2");
        $tot2Range = "A$r2:" . $this->_col(count($hdrs2)) . $r2;
        $s2->getStyle($tot2Range)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $s2->getStyle($tot2Range)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1E293B');
        $s2->getStyle($this->_col(14) . '5:' . $this->_col(14) . $r2)
           ->getNumberFormat()->setFormatCode('#,##0.00');

        $w2 = [5, 10, 26, 14, 14, 26, 12, 14, 24, 18, 18, 28, 14, 13];
        foreach ($w2 as $idx => $w) $s2->getColumnDimension($this->_col($idx + 1))->setWidth($w);
        $s2->freezePane('A5');

        // ════════════════════════════════════════
        // Sheet 3: ملخص البنوك
        // ════════════════════════════════════════
        $s3 = $sp->createSheet();
        $s3->setTitle('ملخص البنوك');
        $s3->setRightToLeft(true);

        $s3->fromArray([['ملخص حسب البنك / المحفظة — دفعة ' . $batch_no]], null, 'A1');
        $s3->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $s3->mergeCells('A1:H1');

        $hdrs3 = ['#', 'البنك / المحفظة', 'النوع', 'الفروع', 'الموظفون', 'الحركات', 'الإجمالي', 'النسبة %'];
        $s3->fromArray($hdrs3, null, 'A3');
        $hr3 = 'A3:' . $this->_col(count($hdrs3)) . '3';
        $s3->getStyle($hr3)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $s3->getStyle($hr3)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('6D28D9');
        $s3->getStyle($hr3)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $bs_total = 0;
        foreach ($bsm as $br) $bs_total += (float)($br['TOTAL_AMOUNT'] ?? 0);
        $r3 = 4; $k = 0;
        foreach ($bsm as $br) {
            $k++;
            $is_wallet = ((int)($br['PROVIDER_TYPE'] ?? 1)) === 2;
            $br_amt    = (float)($br['TOTAL_AMOUNT'] ?? 0);
            $pct       = $bs_total > 0 ? round(($br_amt / $bs_total) * 100, 2) : 0;
            $s3->fromArray([
                $k,
                $br['BANK_NAME'] ?? '',
                $is_wallet ? 'محفظة' : 'بنك',
                (int)($br['BRANCH_COUNT'] ?? 0),
                (int)($br['EMP_COUNT'] ?? 0),
                (int)($br['TRANSACTION_COUNT'] ?? 0),
                $br_amt,
                $pct,
            ], null, "A$r3");
            $r3++;
        }
        $s3->fromArray([
            '', 'الإجمالي العام', '', '', '', '', $bs_total, 100
        ], null, "A$r3");
        $tot3Range = "A$r3:" . $this->_col(count($hdrs3)) . $r3;
        $s3->getStyle($tot3Range)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $s3->getStyle($tot3Range)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1E293B');
        $s3->getStyle('G4:G' . $r3)->getNumberFormat()->setFormatCode('#,##0.00');
        $s3->getStyle('H4:H' . $r3)->getNumberFormat()->setFormatCode('0.00"%"');
        $w3 = [5, 32, 10, 8, 12, 12, 16, 10];
        foreach ($w3 as $idx => $w) $s3->getColumnDimension($this->_col($idx + 1))->setWidth($w);

        // الورقة الافتراضية المفتوحة
        $sp->setActiveSheetIndex(0);

        // ─── إخراج ─────────────────────────────────
        $fname = "تفاصيل_دفعة_{$batch_no}.xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        header('Cache-Control: max-age=0');
        $w = new Xlsx($sp);
        $w->save('php://output');
        exit;
    }

    private function _col($n)
    {
        // تحويل رقم عمود (1-based) إلى حرف Excel (A, B, ..., Z, AA, AB, ...)
        $r = '';
        while ($n > 0) {
            $m = ($n - 1) % 26;
            $r = chr(65 + $m) . $r;
            $n = (int)(($n - 1) / 26);
        }
        return $r;
    }

    private function _diff_sheet_fill($s, $rows, $filter_type, $headers)
    {
        $s->setRightToLeft(true);
        $s->fromArray($headers, null, 'A1');
        $s->getStyle('A1:' . chr(64 + count($headers)) . '1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $s->getStyle('A1:' . chr(64 + count($headers)) . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1E40AF');
        $r = 2; $i = 0;
        foreach ($rows as $row) {
            if (($row['CHANGE_TYPE'] ?? '') !== $filter_type) continue;
            $i++;
            if ($filter_type === 'NEW') {
                $s->fromArray([$i, $row['EMP_NO'], $row['EMP_NAME'], $row['BRANCH_NAME'], (float)$row['CURRENT_AMOUNT']], null, "A$r");
            } elseif ($filter_type === 'LEFT') {
                $s->fromArray([$i, $row['EMP_NO'], $row['EMP_NAME'], $row['BRANCH_NAME'], (float)$row['PREVIOUS_AMOUNT']], null, "A$r");
            } else { // CHANGED
                $s->fromArray([$i, $row['EMP_NO'], $row['EMP_NAME'], $row['BRANCH_NAME'],
                    (float)$row['CURRENT_AMOUNT'], (float)$row['PREVIOUS_AMOUNT'],
                    (float)$row['DIFF'], $row['DIFF_PCT']], null, "A$r");
            }
            $r++;
        }
        for ($c = 0; $c < count($headers); $c++) {
            $s->getColumnDimension(chr(65 + $c))->setWidth($c < 1 ? 6 : ($c < 2 ? 12 : ($c < 4 ? 28 : 16)));
        }
    }

    function batch_preview_validation_json()
    {
        $req_ids     = trim((string)$this->input->post('req_ids'));
        $exclude_ids = trim((string)$this->input->post('exclude_detail_ids'));
        if (!$req_ids) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'لم تُحدّد طلبات']);
            return;
        }

        $rows = $this->{$this->MODEL_NAME}->batch_preview_validation($req_ids, $exclude_ids);
        if (is_array($rows)) {
            array_walk_recursive($rows, function (&$val) {
                if (is_string($val)) { $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
            });
        }

        // إجماليات
        $rows = is_array($rows) ? $rows : [];
        $ok = $warn = $err = 0; $total_amt = 0; $err_amt = 0;
        foreach ($rows as $r) {
            $total_amt += (float)($r['TOTAL_AMOUNT'] ?? 0);
            switch ($r['STATUS'] ?? '') {
                case 'OK':   $ok++;   break;
                case 'WARN': $warn++; break;
                case 'ERR':  $err++;  $err_amt += (float)($r['TOTAL_AMOUNT'] ?? 0); break;
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'ok'         => true,
            'data'       => $rows,
            'totals'     => [
                'employees'    => count($rows),
                'ok'           => $ok,
                'warn'         => $warn,
                'err'          => $err,
                'total_amount' => $total_amt,
                'err_amount'   => $err_amt,
                'safe_amount'  => $total_amt - $err_amt,
            ],
        ]);
    }

    /**
     * batch_compute_preview_json — يرجع التوزيع التفصيلي (per emp+account) لاحتساب الصرف
     * يستخدم في شاشة "تجهيز الصرف" بعد ضغط زر "احتساب" مع طريقة 2 (split)
     * بدون كتابة في DB — للعرض فقط
     */
    function batch_compute_preview_json()
    {
        $req_ids     = trim((string)$this->input->post('req_ids'));
        $exclude_ids = trim((string)$this->input->post('exclude_detail_ids'));
        if (!$req_ids) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'لم تُحدّد طلبات']);
            return;
        }

        $rows = $this->{$this->MODEL_NAME}->batch_compute_preview($req_ids, $exclude_ids);
        if (is_array($rows)) {
            array_walk_recursive($rows, function (&$val) {
                if (is_string($val)) { $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
            });
        }
        $rows = is_array($rows) ? $rows : [];

        // تجميع لكل موظف: الحالة + المجموع المخصّص + عدد الحسابات
        $emps = [];                         // emp_no => aggregated
        $ok = $warn = $err = 0;
        $total_amt = 0; $err_amt = 0;
        foreach ($rows as $r) {
            $eno = $r['EMP_NO'] ?? null;
            if (!$eno) continue;
            if (!isset($emps[$eno])) {
                $emps[$eno] = [
                    'emp_no'        => $eno,
                    'emp_name'      => $r['EMP_NAME'] ?? '',
                    'branch_name'   => $r['BRANCH_NAME'] ?? '',
                    'is_active'     => (int)($r['EMP_IS_ACTIVE'] ?? 0),
                    'total_amount'  => (float)($r['TOTAL_AMOUNT'] ?? 0),
                    'req_count'     => (int)($r['REQ_COUNT'] ?? 0),
                    'detail_ids'    => $r['DETAIL_IDS'] ?? '',
                    'acc_cnt'       => (int)($r['ACC_CNT'] ?? 0),
                    'status'        => $r['EMP_STATUS'] ?? 'OK',
                    'issue'         => $r['ISSUE'] ?? null,
                    'alloc_total'   => 0,
                    'accounts'      => [],
                ];
                $total_amt += (float)($r['TOTAL_AMOUNT'] ?? 0);
                switch ($r['EMP_STATUS'] ?? '') {
                    case 'OK':   $ok++;   break;
                    case 'WARN': $warn++; break;
                    case 'ERR':  $err++;  $err_amt += (float)($r['TOTAL_AMOUNT'] ?? 0); break;
                }
            }
            // إضافة سطر الحساب (لو فيه ACC_ID)
            if (!empty($r['ACC_ID'])) {
                $emps[$eno]['accounts'][] = [
                    'acc_id'        => (int)$r['ACC_ID'],
                    'split_type'    => (int)($r['SPLIT_TYPE'] ?? 0),
                    'split_value'   => (float)($r['SPLIT_VALUE'] ?? 0),
                    'is_default'    => (int)($r['IS_DEFAULT'] ?? 0),
                    'iban'          => $r['IBAN'] ?? '',
                    'account_no'    => $r['ACCOUNT_NO'] ?? '',
                    'wallet_number' => $r['WALLET_NUMBER'] ?? '',
                    'owner_name'    => $r['OWNER_NAME'] ?? '',
                    'owner_id_no'   => $r['OWNER_ID_NO'] ?? '',
                    'beneficiary_id'=> $r['BENEFICIARY_ID'] ?? null,
                    'benef_name'    => $r['BENEF_NAME'] ?? '',
                    'benef_rel'     => $r['BENEF_REL_NAME'] ?? '',
                    'provider_name' => $r['PROVIDER_NAME'] ?? '',
                    'provider_type' => (int)($r['PROVIDER_TYPE'] ?? 1),
                    'prov_branch'   => $r['PROV_BRANCH_NAME'] ?? '',
                    'alloc_amount'  => (float)($r['ALLOC_AMOUNT'] ?? 0),
                ];
                $emps[$eno]['alloc_total'] += (float)($r['ALLOC_AMOUNT'] ?? 0);
            }
        }

        // 🆕 جلب حالة الموظف لكل شهر (للفلتر "حسب الشهر")
        // يستخدم GET_EMP_DISPLAY_STATUS — المنطق الصحيح (مش IS_ACTIVE الثابت)
        $status_by_month = [];   // emp_no => { month => display_status }
        $months_set = [];
        $st_default_counts = [1=>0, 0=>0, 2=>0, 4=>0];   // العدّ بالحالة الحالية
        try {
            $status_rows = $this->{$this->MODEL_NAME}->emp_status_by_month_for_reqs($req_ids);
            foreach ($status_rows as $sr) {
                $eno = (int)($sr['EMP_NO'] ?? 0);
                $mn  = (string)($sr['THE_MONTH'] ?? '');
                $st  = (int)($sr['DISPLAY_STATUS'] ?? 1);
                if (!$eno || !$mn) continue;
                if (!isset($status_by_month[$eno])) $status_by_month[$eno] = [];
                $status_by_month[$eno][$mn] = $st;
                $months_set[$mn] = true;
            }
        } catch (Exception $e) {
            log_message('error', '[batch_compute_preview_json] status_by_month failed: ' . $e->getMessage());
        }
        $months_list = array_keys($months_set);
        rsort($months_list);   // الأحدث أولاً

        // ربط كل emp بـ status_map + display_status الافتراضية (من آخر شهر له)
        foreach ($emps as $eno => &$e) {
            $map = $status_by_month[$eno] ?? [];
            $e['status_map'] = $map;
            // الحالة الافتراضية = من آخر شهر متاح للموظف، أو الـ IS_ACTIVE الحالية كـ fallback
            $default_st = 1;
            if (count($map) > 0) {
                $latest_month = max(array_keys($map));
                $default_st = (int)$map[$latest_month];
            } else {
                $default_st = (int)($e['is_active'] ?? 1) === 1 ? 1 : 0;
            }
            $e['display_status'] = $default_st;
            if (isset($st_default_counts[$default_st])) $st_default_counts[$default_st]++;
        }
        unset($e);

        header('Content-Type: application/json');
        echo json_encode([
            'ok'      => true,
            'data'    => array_values($emps),
            'totals'  => [
                'employees'    => count($emps),
                'ok'           => $ok,
                'warn'         => $warn,
                'err'          => $err,
                'total_amount' => $total_amt,
                'err_amount'   => $err_amt,
                'safe_amount'  => $total_amt - $err_amt,
                // 🆕 عدّاد الحالة الافتراضية (يظهر في الفلتر)
                'st_active'    => $st_default_counts[1] ?? 0,
                'st_inactive'  => ($st_default_counts[0] ?? 0) + ($st_default_counts[2] ?? 0) + ($st_default_counts[4] ?? 0),
            ],
            // 🆕 قائمة الأشهر المتاحة في الطلبات المحددة (للقائمة المنسدلة)
            'months'  => $months_list,
        ]);
    }

    function batch_refresh_split_action()
    {
        if (!HaveAccess(base_url('payment_req/payment_req/batch_refresh_split_action'))) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'لا توجد صلاحية']);
            return;
        }
        $batch_id = (int)$this->input->post('batch_id');
        $res = $this->{$this->MODEL_NAME}->batch_refresh_split($batch_id);
        $msg = $res['msg'];
        $ok  = (substr((string)$msg, 0, 1) === '1');
        $missing = '';
        if ($ok && strpos($msg, '|') !== false) {
            $missing = substr($msg, 2);
        }
        header('Content-Type: application/json');
        echo json_encode([
            'ok'      => $ok,
            'changed' => (int)$res['changed'],
            'missing' => $missing,
            'msg'     => $ok
                ? ('تم إعادة احتساب التوزيع — ' . (int)$res['changed'] . ' حركة' . ($missing ? ' (موظفون بدون حسابات: ' . $missing . ')' : ''))
                : $msg
        ]);
    }

    // =========================================================
    // BATCH DETAIL REDIST — إعادة توزيع موظف معيّن في دفعة محتسبة
    // POST: batch_id, emp_no, provider_type (NULL/1/2), acc_id
    // =========================================================
    function batch_detail_redist_action()
    {
        if (!HaveAccess(base_url('payment_req/payment_req/batch_detail_redist_action'))) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'لا توجد صلاحية']);
            return;
        }
        $batch_id      = (int)$this->input->post('batch_id');
        $emp_no        = (int)$this->input->post('emp_no');
        $provider_type = $this->input->post('provider_type');
        $acc_id        = $this->input->post('acc_id');

        if ($provider_type === '' || $provider_type === '0') $provider_type = null;
        if ($acc_id === '' || $acc_id === '0')               $acc_id        = null;

        if (!$batch_id || !$emp_no) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'BATCH_ID و EMP_NO مطلوبين']);
            return;
        }

        $res = $this->{$this->MODEL_NAME}->batch_detail_redist($batch_id, $emp_no, $provider_type, $acc_id);
        $ok  = (trim((string)$res) === '1');
        header('Content-Type: application/json');
        echo json_encode(['ok' => $ok, 'msg' => $ok ? 'تم إعادة التوزيع' : $res]);
    }

    function batch_reverse_pay_action()
    {
        if (!HaveAccess(base_url('payment_req/payment_req/batch_reverse_pay_action'))) {
            echo json_encode(['ok' => false, 'msg' => 'ليس لديك صلاحية']); return;
        }
        $batch_id = (int)$this->input->post('batch_id');
        if (!$batch_id) { echo json_encode(['ok' => false, 'msg' => 'رقم الدفعة مطلوب']); return; }
        $res = $this->{$this->MODEL_NAME}->batch_reverse_pay($batch_id);
        $parts = explode('|', $res);
        header('Content-Type: application/json');
        if ($parts[0] === '1') {
            echo json_encode(['ok' => true, 'msg' => $parts[1] ?? 'تم عكس الصرف']);
        } else {
            echo json_encode(['ok' => false, 'msg' => $res]);
        }
    }

    function batch_cancel_action()
    {
        if (!HaveAccess(base_url('payment_req/payment_req/batch_cancel_action'))) {
            echo json_encode(['ok' => false, 'msg' => 'ليس لديك صلاحية']); return;
        }
        $batch_id = (int)$this->input->post('batch_id');
        if (!$batch_id) { echo json_encode(['ok' => false, 'msg' => 'رقم الدفعة مطلوب']); return; }
        $res = $this->{$this->MODEL_NAME}->batch_cancel($batch_id);
        header('Content-Type: application/json');

        // 🆕 الرسالة الجديدة قد تكون '1' أو '1|covers_deleted=N|covers_kept_with_fp=M'
        $is_ok = (strpos((string)$res, '1') === 0);
        $msg   = 'تم فك الاحتساب';

        if ($is_ok && strpos((string)$res, '|') !== false) {
            // قراءة إحصائية نماذج الصرف
            $covers_deleted = 0; $covers_kept = 0;
            if (preg_match('/covers_deleted=(\d+)/', $res, $m)) $covers_deleted = (int)$m[1];
            if (preg_match('/covers_kept_with_fp=(\d+)/', $res, $m)) $covers_kept = (int)$m[1];
            if ($covers_deleted > 0 || $covers_kept > 0) {
                $parts = ['تم فك الاحتساب'];
                if ($covers_deleted > 0) {
                    $parts[] = '✓ حُذِف ' . $covers_deleted . ' نموذج صرف مرتبط';
                }
                if ($covers_kept > 0) {
                    $parts[] = '⚠️ تُرك ' . $covers_kept . ' نموذج صرف (لها سند صرف منشأ)';
                }
                $msg = implode(' — ', $parts);
            }
        } elseif (!$is_ok) {
            $msg = $res;
        }

        echo json_encode(['ok' => $is_ok, 'msg' => $msg]);
    }

    // ==================== احتساب الصرف — بيانات المعاينة ====================
    function batch_data()
    {
        $req_ids = $this->p_req_ids;
        if (!$req_ids) { echo json_encode(['ok' => false, 'msg' => 'يجب تحديد طلبات']); return; }

        $result = $this->{$this->MODEL_NAME}->batch_preview($req_ids);

        // تحذيرات: طلبات فيها موظفين غير معتمدين
        $warnings = $this->{$this->MODEL_NAME}->batch_warnings($req_ids);

        if (is_array($result['rows'])) {
            array_walk_recursive($result['rows'], function (&$val) {
                if (is_string($val)) { $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
            });
        }
        $result['warnings'] = $warnings;
        header('Content-Type: application/json');
        echo json_encode($result);
    }

    // ==================== تجهيز الصرف — اعتماد التجهيز ====================
    function batch_confirm_action()
    {
        if (!HaveAccess(base_url('payment_req/payment_req/batch_confirm_action'))) {
            echo json_encode(['ok' => false, 'msg' => 'ليس لديك صلاحية']); return;
        }
        $req_ids = $this->input->post('req_ids') ?: $this->p_req_ids;
        $exclude_ids = $this->input->post('exclude_detail_ids') ?: '';
        // طريقة الصرف: 1=قديم (افتراضي) | 2=جديد (PAYMENT_ACCOUNTS + split)
        $disburse_method = (int)($this->input->post('disburse_method') ?: 1);
        if (!$req_ids) { echo json_encode(['ok' => false, 'msg' => 'يجب تحديد طلبات']); return; }

        // 🛡️ بوابة الرقابة: لا احتساب إلا بعد اعتماد الرقابة لكل الطلبات
        $pending = $this->{$this->MODEL_NAME}->raqaba_pending_for_reqs($req_ids);
        if (!empty($pending)) {
            $nos = array_map(function ($r) { return $r['REQ_NO'] ?? $r['REQ_ID']; }, $pending);
            echo json_encode([
                'ok'  => false,
                'msg' => 'لا يمكن الاحتساب — طلبات لم تُعتمد من الرقابة: ' . implode('، ', $nos),
            ]);
            return;
        }

        $result = $this->{$this->MODEL_NAME}->batch_confirm($req_ids, $exclude_ids, $disburse_method);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    // ==================== تجهيز الصرف — تنفيذ الصرف ====================
    function batch_pay_action()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!HaveAccess(base_url('payment_req/payment_req/batch_pay_action'))) {
            echo json_encode(['ok' => false, 'msg' => 'ليس لديك صلاحية']); return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'msg' => 'الطلب يجب أن يكون POST']); return;
        }
        $batch_id = (int)$this->input->post('batch_id');
        if ($batch_id <= 0) { echo json_encode(['ok' => false, 'msg' => 'رقم الدفعة مطلوب']); return; }
        try {
            echo json_encode($this->{$this->MODEL_NAME}->batch_pay($batch_id));
        } catch (\Throwable $e) {
            log_message('error', '[batch_pay_action] ' . $e->getMessage());
            echo json_encode(['ok' => false, 'batch_id' => $batch_id, 'state' => 'UNCONFIRMED',
                'msg' => 'تعذر التأكد من نتيجة الصرف؛ افتح تفاصيل الدفعة وتحقق من حالتها قبل إعادة المحاولة']);
        }
    }

    // ==================== تجهيز الصرف — تصدير CSV ====================
    function batch_export_csv()
    {
        $req_ids = $this->input->get('req_ids') ?: $this->p_req_ids;
        if (!$req_ids) { echo '<script>alert("يجب تحديد طلبات"); history.back();</script>'; return; }

        $result = $this->{$this->MODEL_NAME}->batch_preview($req_ids);

        $rows = $result['rows'] ?? [];
        if (!is_array($rows) || count($rows) === 0) {
            echo '<script>alert("لا توجد بيانات"); history.back();</script>'; return;
        }

        $filename = 'كشف_احتساب_الصرف_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['#', 'رقم الموظف', 'اسم الموظف', 'المقر', 'البنك الرئيسي', 'فرع البنك', 'IBAN', 'رقم الحساب', 'رقم الطلب', 'نوع الطلب', 'الشهر', 'المبلغ'], ',', '"', '\\');

        $n = 0;
        foreach ($rows as $d) {
            $n++;
            fputcsv($out, [
                $n,
                $d['EMP_NO']              ?? '',
                $d['EMP_NAME']            ?? '',
                $d['BRANCH_NAME']         ?? '',
                $d['MASTER_BANK_NAME']    ?? '',
                $d['BANK_NAME']           ?? '',
                $d['IBAN']                ?? '',
                $d['BANK_ACCOUNT']        ?? $d['ACCOUNT_BANK_EMAIL'] ?? '',
                $d['REQ_NO']              ?? '',
                $d['REQ_TYPE_NAME']       ?? '',
                $d['THE_MONTH']           ?? '',
                number_format((float)($d['REQ_AMOUNT'] ?? 0), 2, '.', ''),
            ], ',', '"', '\\');
        }
        fclose($out);
        exit;
    }

    // ==================== تصدير ملف البنك — Excel ====================
    // قائمة البنوك بالدفعة (AJAX)
    function export_bank_list()
    {
        $batch_id = (int)($this->input->get('batch_id') ?: $this->input->post('batch_id') ?: $this->p_batch_id);
        if (!$batch_id) { header('Content-Type: application/json'); echo json_encode(['ok' => false, 'msg' => 'رقم الدفعة مطلوب']); return; }

        $rows = $this->{$this->MODEL_NAME}->batch_bank_export($batch_id, null);
        if (!is_array($rows) || count($rows) === 0) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'لا توجد بيانات — تأكد من تنفيذ procedure BATCH_BANK_EXPORT']); return;
        }

        $banks = [];
        foreach ($rows as $r) {
            $bk = $r['MASTER_BANK_NO'] ?? 0;
            if (!isset($banks[$bk])) $banks[$bk] = ['no' => $bk, 'name' => $r['MASTER_BANK_NAME'] ?? 'غير محدد', 'count' => 0, 'total' => 0];
            $banks[$bk]['count']++;
            $banks[$bk]['total'] += (float)($r['TOTAL_AMOUNT'] ?? 0);
        }

        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'banks' => array_values($banks)]);
    }

    // تصدير ملف بنك واحد (Excel)
    // ========================================================
    // 🆕 EXPORT WALLET (للمحاسب) — دمج بالـ ID + إبراز المكرر
    // ========================================================
    // يُستخدم لمحافظ مثل "بال باي" — المحاسب يشوف نسخة مدمجة
    // بدل النسخة الخام للبنك (اللي ممكن فيها نفس الشخص بسطرين)
    function export_wallet_accountant()
    {
        $batch_id       = (int)($this->input->get('batch_id') ?: $this->p_batch_id);
        $master_bank_no = (int)($this->input->get('master_bank_no') ?: $this->p_master_bank_no);

        if (!$batch_id || !$master_bank_no) {
            echo '<script>alert("بيانات ناقصة"); history.back();</script>'; return;
        }

        $rows = $this->{$this->MODEL_NAME}->batch_bank_export($batch_id, $master_bank_no);
        if (!is_array($rows) || count($rows) === 0) {
            echo '<script>alert("لا توجد بيانات"); history.back();</script>'; return;
        }

        $batchNo = $rows[0]['BATCH_NO'] ?? $batch_id;
        $bankName = $rows[0]['MASTER_BANK_NAME'] ?? '';
        $isWallet = (int)($rows[0]['PROVIDER_TYPE'] ?? 1) === 2;

        // ═══════════════ التجميع حسب رقم الهوية ═══════════════
        // groups: id_no => ['name', 'phone', 'amount', 'count', 'lines'=>[الأسطر الأصلية]]
        $groups = [];
        $no_id_rows = [];   // بدون هوية → سطر مستقل لكل واحد
        foreach ($rows as $r) {
            $id    = trim((string)($r['OWNER_ID_NO'] ?? $r['EMP_ID'] ?? ''));
            $name  = trim((string)($r['OWNER_NAME']  ?? $r['EMP_NAME'] ?? ''));
            $phone = trim((string)($r['WALLET_NUMBER'] ?? $r['OWNER_PHONE'] ?? ''));
            $amt   = (float)($r['TOTAL_AMOUNT'] ?? 0);
            $emp   = (string)($r['EMP_NO'] ?? '');

            if ($id === '') {
                $no_id_rows[] = ['name'=>$name, 'phone'=>$phone, 'amount'=>$amt, 'emp_no'=>$emp];
                continue;
            }
            if (!isset($groups[$id])) {
                $groups[$id] = [
                    'name'   => $name,
                    'phone'  => $phone,
                    'amount' => 0,
                    'count'  => 0,
                    'emps'   => [],
                    'phones' => [],
                ];
            }
            $groups[$id]['amount'] += $amt;
            $groups[$id]['count']++;
            if ($emp)   $groups[$id]['emps'][$emp] = true;
            if ($phone) $groups[$id]['phones'][$phone] = true;
        }

        // إحصائيات
        $unique_ids = count($groups);
        $total_orig = count($rows);
        $merged_ids = 0; $merged_lines = 0;
        foreach ($groups as $g) {
            if ($g['count'] > 1) { $merged_ids++; $merged_lines += $g['count']; }
        }
        $total_amount = 0;
        foreach ($groups as $g) $total_amount += $g['amount'];
        foreach ($no_id_rows as $r) $total_amount += $r['amount'];

        // ═══════════════ بناء الـ Excel ═══════════════
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('للمحاسب — مدمج');
        $sheet->setRightToLeft(true);

        // ── R1: عنوان ──
        $sheet->setCellValue('A1', '📋 كشف ' . $bankName . ' (للمحاسب — مدمج بالهوية) — دفعة ' . $batchNo);
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1e293b');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // ── R2: ملخّص الدمج ──
        $summary  = '📊 الأصل: ' . $total_orig . ' حركة';
        $summary .= '   ·   بعد الدمج: ' . ($unique_ids + count($no_id_rows)) . ' سطر';
        if ($merged_ids > 0) {
            $summary .= '   ·   ⚠️ ' . $merged_ids . ' هوية مكررة (دمج ' . $merged_lines . ' حركة)';
        }
        if (count($no_id_rows) > 0) {
            $summary .= '   ·   ' . count($no_id_rows) . ' بدون هوية (لم تُدمج)';
        }
        $sheet->setCellValue('A2', $summary);
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('fef9c3');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(22);

        // ── R3: Headers ──
        $headers = ['#', 'رقم الهوية', 'الاسم', 'الجوال', 'المبلغ', 'عدد الحركات', 'الحالة'];
        $col = 'A';
        foreach ($headers as $h) { $sheet->setCellValue($col . '3', $h); $col++; }
        $sheet->getStyle('A3:G3')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A3:G3')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('334155');
        $sheet->getStyle('A3:G3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(3)->setRowHeight(22);

        // ── البيانات ──
        $row = 4; $n = 0;
        foreach ($groups as $id => $g) {
            $n++;
            $isMerged = $g['count'] > 1;
            $status = $isMerged
                ? '🔁 مدمج (' . $g['count'] . ' حركة)'
                : '✓ سطر واحد';
            $phone = $g['phone'];
            if (count($g['phones']) > 1) $phone = implode(' / ', array_keys($g['phones']));

            $sheet->setCellValue('A' . $row, $n);
            $sheet->setCellValueExplicit('B' . $row, $id, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $row, $g['name']);
            $sheet->setCellValueExplicit('D' . $row, (string)$phone, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('E' . $row, $g['amount']);
            $sheet->setCellValue('F' . $row, $g['count']);
            $sheet->setCellValue('G' . $row, $status);

            if ($isMerged) {
                // تلوين السطر الأصفر الفاتح + bold للحالة
                $sheet->getStyle('A' . $row . ':G' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('fefce8');
                $sheet->getStyle('G' . $row)->getFont()->setBold(true)->getColor()->setRGB('92400e');
                $sheet->getStyle('F' . $row)->getFont()->setBold(true);
            }
            $row++;
        }

        // ── سطور بدون هوية ──
        if (count($no_id_rows) > 0) {
            $sheet->setCellValue('A' . $row, '⚠️ سطور بدون هوية (لم تُدمج)');
            $sheet->mergeCells('A' . $row . ':G' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->getColor()->setRGB('991b1b');
            $sheet->getStyle('A' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('fee2e2');
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $row++;
            foreach ($no_id_rows as $r) {
                $n++;
                $sheet->setCellValue('A' . $row, $n);
                $sheet->setCellValue('B' . $row, '—');
                $sheet->setCellValue('C' . $row, $r['name']);
                $sheet->setCellValueExplicit('D' . $row, (string)$r['phone'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue('E' . $row, $r['amount']);
                $sheet->setCellValue('F' . $row, 1);
                $sheet->setCellValue('G' . $row, '⚠️ بدون هوية');
                $sheet->getStyle('A' . $row . ':G' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('fef2f2');
                $row++;
            }
        }

        // ── سطر الإجمالي ──
        $totRow = $row;
        $sheet->setCellValue('A' . $totRow, 'الإجمالي');
        $sheet->mergeCells('A' . $totRow . ':D' . $totRow);
        $sheet->setCellValue('E' . $totRow, $total_amount);
        $sheet->setCellValue('F' . $totRow, $total_orig . ' حركة → ' . ($unique_ids + count($no_id_rows)) . ' سطر');
        $sheet->mergeCells('F' . $totRow . ':G' . $totRow);
        $sheet->getStyle('A' . $totRow . ':G' . $totRow)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A' . $totRow . ':G' . $totRow)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1e293b');
        $sheet->getStyle('A' . $totRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('F' . $totRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // ── Formatting ──
        $sheet->getStyle('E4:E' . $totRow)->getNumberFormat()->setFormatCode('#,##0.00');
        foreach (['A','B','C','D','E','F','G'] as $c) $sheet->getColumnDimension($c)->setAutoSize(true);
        $sheet->getStyle('A3:G' . $totRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->freezePane('A4');

        // ── حفظ ──
        $tag = $merged_ids > 0 ? '_مدمج' : '';
        $filename = 'محاسب_' . preg_replace('/[^\w؀-ۿ]+/u', '_', $bankName) . '_' . $batchNo . $tag . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ====================================================================
    // 🆕 _build_batch_emps_summary — يبني ملخصاً لكل موظف في الدفعة
    // ====================================================================
    // يُستخدم من 3 endpoints: web view (داخل batch_detail)، Excel، Print All
    //
    // النتيجة: array لكل موظف:
    //   emp_no, emp_name, emp_id, branch, status, total,
    //   bank_total, wallet_total, benef_total,
    //   destinations[] (وجهات مدموجة كما يستلمها البنك)
    //
    // مصدر البيانات: batch_bank_export → نجمّع لكل موظف ونصنّف
    private function _build_batch_emps_summary($batch_id)
    {
        $rows = $this->{$this->MODEL_NAME}->batch_bank_export((int)$batch_id);
        if (!is_array($rows)) return [];

        $emps = [];   // emp_no => summary
        foreach ($rows as $r) {
            $eno = (int)($r['EMP_NO'] ?? 0);
            if (!$eno) continue;

            // تصنيف الوجهة
            $ptp = (int)($r['PROVIDER_TYPE'] ?? 1);
            $is_benef = !empty($r['BENEFICIARY_ID']);
            $category = $is_benef ? 'BENEF' : ($ptp === 2 ? 'WALLET' : 'BANK');

            // مفتاح الدمج (نفس منطق ملف البنك)
            if ($category === 'WALLET') {
                $dest_key = 'W:' . count($emps[$eno]['_dest_seen'] ?? []) . '_' . uniqid();
            } elseif ($category === 'BENEF') {
                $dest_key = 'B:' . ($r['BENEFICIARY_ID'] ?? uniqid());
            } else {
                $iban = trim((string)($r['IBAN'] ?? ''));
                $acc  = trim((string)($r['ACCOUNT_NO'] ?? $r['BANK_ACCOUNT'] ?? ''));
                $dest_key = 'BK:' . ($iban !== '' ? $iban : $acc);
            }

            $amt = (float)($r['TOTAL_AMOUNT'] ?? 0);

            // تهيئة الموظف لأول مرة
            if (!isset($emps[$eno])) {
                $emps[$eno] = [
                    'emp_no'       => $eno,
                    'emp_name'     => $r['EMP_NAME']   ?? '',
                    'emp_id'       => $r['EMP_ID']     ?? '',
                    'branch'       => '',
                    'status'       => (int)($r['EMP_DISPLAY_STATUS'] ?? 1),
                    'total'        => 0.0,
                    'bank_total'   => 0.0,
                    'wallet_total' => 0.0,
                    'benef_total'  => 0.0,
                    'destinations' => [],
                    '_dest_seen'   => [],
                ];
            }

            $emps[$eno]['total'] += $amt;
            if      ($category === 'BANK')   $emps[$eno]['bank_total']   += $amt;
            elseif  ($category === 'WALLET') $emps[$eno]['wallet_total'] += $amt;
            else                             $emps[$eno]['benef_total']  += $amt;

            // دمج/إضافة الوجهة
            if (!isset($emps[$eno]['_dest_seen'][$dest_key])) {
                $emps[$eno]['_dest_seen'][$dest_key] = count($emps[$eno]['destinations']);
                $emps[$eno]['destinations'][] = [
                    'category'  => $category,
                    'label'     => $r['MASTER_BANK_NAME'] ?? '',
                    'sub_label' => $is_benef
                                   ? ($r['BENEF_REL_NAME'] ?? '')
                                   : ($r['BANK_BRANCH_NAME'] ?? ''),
                    'account'   => $r['IBAN'] ?? ($r['BANK_ACCOUNT'] ?? ($r['WALLET_NUMBER'] ?? '')),
                    'owner'     => $r['OWNER_NAME'] ?? '',
                    'amount'    => 0.0,
                    'merged'    => 0,
                ];
            }
            $idx = $emps[$eno]['_dest_seen'][$dest_key];
            $emps[$eno]['destinations'][$idx]['amount'] += $amt;
            $emps[$eno]['destinations'][$idx]['merged'] += 1;
        }

        // تنظيف + ترتيب أبجدي بالاسم
        foreach ($emps as &$e) unset($e['_dest_seen']);
        unset($e);

        $list = array_values($emps);
        if (class_exists('\Collator')) {
            $col = new \Collator('ar');
            usort($list, function($a, $b) use ($col){
                return $col->compare($a['emp_name'], $b['emp_name']);
            });
        }
        return $list;
    }

    // ====================================================================
    // 🆕 (A) batch_emps_summary_excel — تصدير Excel: صف لكل موظف
    // ====================================================================
    function batch_emps_summary_excel($batch_id = null)
    {
        $batch_id = (int)$batch_id;
        if (!$batch_id) { show_error('batch_id مطلوب'); return; }

        $emps = $this->_build_batch_emps_summary($batch_id);
        if (empty($emps)) {
            echo '<script>alert("لا توجد بيانات"); history.back();</script>'; return;
        }

        // معلومات الدفعة للترويسة
        $batch_info = [];
        $batch_rows = $this->{$this->MODEL_NAME}->batch_history_list();
        if (is_array($batch_rows)) {
            foreach ($batch_rows as $b) {
                if ((int)($b['BATCH_ID'] ?? 0) === $batch_id) { $batch_info = $b; break; }
            }
        }
        $batch_no = $batch_info['BATCH_NO'] ?? ('#' . $batch_id);

        require_once APPPATH . 'third_party/phpoffice/vendor/autoload.php';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('ملخص الموظفين');
        $sheet->setRightToLeft(true);

        // R1: العنوان (مدمج)
        $sheet->setCellValue('A1', 'ملخص توزيع المبالغ لكل موظف — الدفعة ' . $batch_no);
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['name'=>'Arial','size'=>14,'bold'=>true,'color'=>['rgb'=>'FFFFFF']],
            'fill' => ['fillType'=>\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,'startColor'=>['rgb'=>'1e293b']],
            'alignment' => ['horizontal'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,'vertical'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // R2: Headers
        $headers = ['#','رقم الموظف','اسم الموظف','رقم الهوية','الحالة','الإجمالي','للبنك','للمحفظة','للوريث','تفاصيل الوجهات'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue([$i + 1, 2], $h);
        }
        $sheet->getStyle('A2:J2')->applyFromArray([
            'font' => ['name'=>'Arial','size'=>11,'bold'=>true,'color'=>['rgb'=>'FFFFFF']],
            'fill' => ['fillType'=>\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,'startColor'=>['rgb'=>'334155']],
            'alignment' => ['horizontal'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,'vertical'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders'=>['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(22);

        $status_names = [1=>'فعّال', 0=>'متقاعد', 2=>'متوفى', 4=>'حساب مغلق'];

        // R3+: البيانات
        $row = 3; $idx = 0; $tot = 0; $tot_b = 0; $tot_w = 0; $tot_bn = 0;
        foreach ($emps as $e) {
            $idx++;
            // بناء نص "تفاصيل الوجهات" — سطر لكل وجهة
            $dest_lines = [];
            foreach ($e['destinations'] as $d) {
                $cat_label = $d['category'] === 'WALLET' ? '📱'
                          : ($d['category'] === 'BENEF'  ? '👥' : '🏦');
                $merge_tag = $d['merged'] > 1 ? ' [دمج ' . $d['merged'] . ']' : '';
                $dest_lines[] = $cat_label . ' ' . $d['label']
                             . ($d['sub_label'] ? ' - ' . $d['sub_label'] : '')
                             . $merge_tag . ': ' . number_format($d['amount'], 2);
            }
            $dest_text = implode("\n", $dest_lines);

            $sheet->setCellValue([1, $row], $idx);
            $sheet->setCellValue([2, $row], $e['emp_no']);
            $sheet->setCellValue([3, $row], $e['emp_name']);
            $sheet->setCellValueExplicit('D' . $row, (string)$e['emp_id'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue([5, $row], $status_names[$e['status']] ?? '—');
            $sheet->setCellValue([6, $row], (float)$e['total']);
            $sheet->setCellValue([7, $row], (float)$e['bank_total']);
            $sheet->setCellValue([8, $row], (float)$e['wallet_total']);
            $sheet->setCellValue([9, $row], (float)$e['benef_total']);
            $sheet->setCellValue([10, $row], $dest_text);
            $sheet->getStyle('J' . $row)->getAlignment()->setWrapText(true);

            $tot    += $e['total'];
            $tot_b  += $e['bank_total'];
            $tot_w  += $e['wallet_total'];
            $tot_bn += $e['benef_total'];
            $row++;
        }
        $lastRow = $row - 1;

        // تنسيق البيانات
        $sheet->getStyle('A3:J' . $lastRow)->applyFromArray([
            'font' => ['name'=>'Arial','size'=>10],
            'borders' => ['allBorders'=>['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,'color'=>['rgb'=>'cbd5e1']]],
            'alignment' => ['vertical'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('F3:I' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');

        // صف الإجمالي
        $totRow = $row;
        $sheet->setCellValue('A' . $totRow, 'الإجمالي');
        $sheet->mergeCells('A' . $totRow . ':E' . $totRow);
        $sheet->setCellValue('F' . $totRow, $tot);
        $sheet->setCellValue('G' . $totRow, $tot_b);
        $sheet->setCellValue('H' . $totRow, $tot_w);
        $sheet->setCellValue('I' . $totRow, $tot_bn);
        $sheet->getStyle('A' . $totRow . ':J' . $totRow)->applyFromArray([
            'font' => ['name'=>'Arial','size'=>12,'bold'=>true,'color'=>['rgb'=>'FFFFFF']],
            'fill' => ['fillType'=>\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,'startColor'=>['rgb'=>'1e293b']],
            'alignment' => ['horizontal'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,'vertical'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('F' . $totRow . ':I' . $totRow)->getNumberFormat()->setFormatCode('#,##0.00');

        // عرض الأعمدة
        $widths = [5, 11, 32, 14, 12, 13, 13, 13, 13, 45];
        foreach ($widths as $i => $w) {
            $sheet->getColumnDimension(chr(65 + $i))->setWidth($w);
        }
        $sheet->freezePane('A3');

        $filename = 'ملخص_الموظفين_' . preg_replace('/[^\w؀-ۿ]+/u', '_', $batch_no) . '_' . date('Ymd') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ====================================================================
    // 🆕 (B) batch_emps_summary_print_all — طباعة A4: كشف لكل موظف
    // ====================================================================
    // كل موظف صفحة واحدة (page-break). جاهز للطباعة الجماعية للأرشيف.
    function batch_emps_summary_print_all($batch_id = null)
    {
        $batch_id = (int)$batch_id;
        if (!$batch_id) { show_error('batch_id مطلوب'); return; }

        // معلومات الدفعة
        $batch_info = [];
        $batch_rows = $this->{$this->MODEL_NAME}->batch_history_list();
        if (is_array($batch_rows)) {
            foreach ($batch_rows as $b) {
                if ((int)($b['BATCH_ID'] ?? 0) === $batch_id) { $batch_info = $b; break; }
            }
        }

        // كشف كل موظف على حدة (نستدعي _build_emp_breakdown لكل موظف)
        $emps = $this->_build_batch_emps_summary($batch_id);
        $emp_details = [];
        foreach ($emps as $e) {
            $rows = $this->{$this->MODEL_NAME}->emp_batch_breakdown($batch_id, $e['emp_no']);
            if (!is_array($rows)) $rows = [];

            // 🆕 جلب فرع الموظف (المقر) من get_emp_info (لأن view التصدير ما يحويه)
            $ei = $this->{$this->MODEL_NAME}->get_emp_info($e['emp_no']);
            $emp_branch = (is_array($ei) && count($ei) > 0) ? ($ei[0]['BRANCH_NAME'] ?? '') : '';
            $reqs = []; $dst_raw = []; $tot_req = 0;
            foreach ($rows as $r) {
                $rt = $r['ROW_TYPE'] ?? '';
                $amt = (float)($r['AMOUNT'] ?? 0);
                if ($rt === 'REQ') {
                    $reqs[] = [
                        'req_no'    => $r['LABEL'] ?? '',
                        'month'     => $r['SUB_LABEL'] ?? '',
                        'type_name' => $r['TYPE_NAME'] ?? '',
                        'amount'    => $amt,
                    ];
                    $tot_req += $amt;
                } elseif ($rt === 'DEST') {
                    $dst_raw[] = [
                        'category'  => $r['CATEGORY']  ?? 'BANK',
                        'type_name' => $r['TYPE_NAME'] ?? '',
                        'label'     => $r['LABEL']     ?? '',
                        'sub_label' => $r['SUB_LABEL'] ?? '',
                        'account'   => $r['ACCOUNT']   ?? '',
                        'owner'     => $r['OWNER']     ?? '',
                        'amount'    => $amt,
                    ];
                }
            }
            // 🆕 دمج الوجهات بالمفتاح الفعلي (تقرير الموظف — كل وجهة بسطر مع المجموع)
            $grouped = []; $wSeq = 0; $bSeq = 0;
            foreach ($dst_raw as $d) {
                $acc = trim((string)$d['account']);
                if      ($d['category'] === 'WALLET') $key = 'W:' . ($acc !== '' ? $acc : ('seq:' . (++$wSeq)));
                elseif  ($d['category'] === 'BENEF')  $key = 'B:' . ($acc !== '' ? $acc : ('seq:' . (++$bSeq)));
                else                                  $key = 'BK:' . ($acc !== '' ? $acc : ('L:' . $d['label']));
                if (!isset($grouped[$key])) {
                    $grouped[$key] = $d;
                    $grouped[$key]['amount'] = 0.0;
                    $grouped[$key]['merged_count'] = 0;
                }
                $grouped[$key]['amount']       += $d['amount'];
                $grouped[$key]['merged_count'] += 1;
            }
            $destinations = array_values($grouped);
            $tot_dest = array_sum(array_column($destinations, 'amount'));

            $emp_details[] = [
                'emp_no'       => $e['emp_no'],
                'emp_name'     => $e['emp_name'],
                'emp_id'       => $e['emp_id'],
                'branch'       => $emp_branch,    // 🆕 من get_emp_info
                'requests'     => $reqs,
                'destinations' => $destinations,
                'totals' => [
                    'requests'     => $tot_req,
                    'destinations' => $tot_dest,
                    'sum_bank'     => $e['bank_total'],
                    'sum_wallet'   => $e['wallet_total'],
                    'sum_benef'    => $e['benef_total'],
                    'match'        => abs($tot_req - $tot_dest) < 0.01,
                ],
            ];
        }

        $data = [
            'batch_info'   => $batch_info,
            'batch_id'     => $batch_id,
            'emp_details'  => $emp_details,
        ];
        $this->load->view('payment_req_emp_breakdown_print_all', $data);
    }

    // ====================================================================
    // 🆕 emp_batch_breakdown_print — صفحة A4 قابلة للطباعة (كشف الموظف)
    // ====================================================================
    // URL: /payment_req/payment_req/emp_batch_breakdown_print/<batch_id>/<emp_no>
    function emp_batch_breakdown_print($batch_id = null, $emp_no = null)
    {
        $batch_id = (int)$batch_id;
        $emp_no   = (int)$emp_no;
        if (!$batch_id || !$emp_no) { show_error('batch_id و emp_no مطلوبان'); return; }

        // (1) بيانات الدفعة (من batch_history_list نفس ما يفعل batch_detail)
        $batch_info = [];
        $batch_rows = $this->{$this->MODEL_NAME}->batch_history_list();
        if (is_array($batch_rows)) {
            foreach ($batch_rows as $b) {
                if ((int)($b['BATCH_ID'] ?? 0) === $batch_id) {
                    $batch_info = $b;
                    break;
                }
            }
        }

        // (2) بيانات الموظف
        $ei = $this->{$this->MODEL_NAME}->get_emp_info($emp_no);
        $emp_info = (is_array($ei) && count($ei) > 0) ? $ei[0] : [];

        // (3) الـ breakdown (طلبات + وجهات — يطبّق نفس منطق الدمج)
        $rows = $this->{$this->MODEL_NAME}->emp_batch_breakdown($batch_id, $emp_no);
        if (!is_array($rows)) $rows = [];

        $requests = [];   $dest_raw = [];   $tot_req = 0;
        foreach ($rows as $r) {
            $rt  = $r['ROW_TYPE'] ?? '';
            $amt = (float)($r['AMOUNT'] ?? 0);
            if ($rt === 'REQ') {
                $requests[] = [
                    'req_no'    => $r['LABEL']     ?? '',
                    'month'     => $r['SUB_LABEL'] ?? '',
                    'type_name' => $r['TYPE_NAME'] ?? '',
                    'amount'    => $amt,
                ];
                $tot_req += $amt;
            } elseif ($rt === 'DEST') {
                $dest_raw[] = [
                    'category'  => $r['CATEGORY']  ?? 'BANK',
                    'type_name' => $r['TYPE_NAME'] ?? '',
                    'label'     => $r['LABEL']     ?? '',
                    'sub_label' => $r['SUB_LABEL'] ?? '',
                    'account'   => $r['ACCOUNT']   ?? '',
                    'owner'     => $r['OWNER']     ?? '',
                    'amount'    => $amt,
                ];
            }
        }

        // 🆕 دمج الوجهات بالمفتاح الفعلي (تقرير الموظف — كل وجهة بسطر مع المجموع)
        $grouped = []; $wSeq = 0; $bSeq = 0;
        foreach ($dest_raw as $d) {
            $acc = trim((string)$d['account']);
            if      ($d['category'] === 'WALLET') $key = 'W:' . ($acc !== '' ? $acc : ('seq:' . (++$wSeq)));
            elseif  ($d['category'] === 'BENEF')  $key = 'B:' . ($acc !== '' ? $acc : ('seq:' . (++$bSeq)));
            else                                  $key = 'BK:' . ($acc !== '' ? $acc : ('L:' . $d['label']));
            if (!isset($grouped[$key])) {
                $grouped[$key] = $d;
                $grouped[$key]['amount'] = 0.0;
                $grouped[$key]['merged_count'] = 0;
            }
            $grouped[$key]['amount']       += $d['amount'];
            $grouped[$key]['merged_count'] += 1;
        }
        $destinations = array_values($grouped);

        $tot_dest = 0; $sum_bank = 0; $sum_wallet = 0; $sum_benef = 0;
        foreach ($destinations as $d) {
            $amt = (float)$d['amount'];
            $tot_dest += $amt;
            if      ($d['category'] === 'BANK')   $sum_bank   += $amt;
            elseif  ($d['category'] === 'WALLET') $sum_wallet += $amt;
            elseif  ($d['category'] === 'BENEF')  $sum_benef  += $amt;
        }

        $data = [
            'batch_id'     => $batch_id,
            'emp_no'       => $emp_no,
            'batch_info'   => $batch_info,
            'emp_info'     => $emp_info,
            'requests'     => $requests,
            'destinations' => $destinations,
            'totals' => [
                'requests'     => $tot_req,
                'destinations' => $tot_dest,
                'sum_bank'     => $sum_bank,
                'sum_wallet'   => $sum_wallet,
                'sum_benef'    => $sum_benef,
                'match'        => abs($tot_req - $tot_dest) < 0.01,
            ],
        ];

        $this->load->view('payment_req_emp_breakdown_print', $data);
    }

    // ====================================================================
    // 🆕 emp_batch_breakdown_json — كشف توزيع موظف في دفعة (Modal)
    // ====================================================================
    // للمحاسب: "الموظف X عنده 3000ج من 4 طلبات. وين راحت؟"
    //  → بيرجع: الطلبات (مع مبلغ كل واحد) + الوجهات (بنك/محفظة/وريث + مبلغ كل واحدة)
    function emp_batch_breakdown_json()
    {
        $batch_id = (int)($this->input->get('batch_id') ?: $this->input->post('batch_id'));
        $emp_no   = (int)($this->input->get('emp_no')   ?: $this->input->post('emp_no'));

        if (!$batch_id || !$emp_no) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'batch_id و emp_no مطلوبان']);
            return;
        }

        $rows = $this->{$this->MODEL_NAME}->emp_batch_breakdown($batch_id, $emp_no);
        if (!is_array($rows)) $rows = [];

        // فصل الصفوف إلى requests و destinations
        $requests     = [];   // الطلبات المساهمة
        $dest_raw     = [];   // الوجهات الخام (قبل الدمج)
        $tot_req = 0;

        foreach ($rows as $r) {
            $rt   = $r['ROW_TYPE'] ?? '';
            $amt  = (float)($r['AMOUNT'] ?? 0);

            if ($rt === 'REQ') {
                $requests[] = [
                    'req_no'    => $r['LABEL']     ?? '',
                    'month'     => $r['SUB_LABEL'] ?? '',
                    'type_name' => $r['TYPE_NAME'] ?? '',
                    'amount'    => $amt,
                ];
                $tot_req += $amt;
            } elseif ($rt === 'DEST') {
                $dest_raw[] = [
                    'category'  => $r['CATEGORY']  ?? 'BANK',
                    'type_name' => $r['TYPE_NAME'] ?? '',
                    'label'     => $r['LABEL']     ?? '',
                    'sub_label' => $r['SUB_LABEL'] ?? '',
                    'account'   => $r['ACCOUNT']   ?? '',
                    'owner'     => $r['OWNER']     ?? '',
                    'amount'    => $amt,
                ];
            }
        }

        // 🆕 دمج الوجهات بنفس منطق ملف البنك:
        //    • بنك: دمج بـ IBAN/الحساب (لو نفس الموظف عنده وجهتين بنفس IBAN → حركة واحدة)
        //    • محفظة: لا دمج (كل صف منفصل)
        //    • وريث/مستفيد: لا دمج (كل وريث له هويته الخاصة)
        $grouped_dest = [];
        $walletSeq = 0;
        $benefSeq  = 0;
        foreach ($dest_raw as $d) {
            // 🆕 تقرير الموظف: ندمج كل وجهة بمفتاحها الفعلي (رقم الحساب/المحفظة/المستفيد)
            //    لأن الموظف يهمه: "كم سيصل لكل وجهة" (مش "كم تحويل سيستلم البنك")
            //    الـ بنك/المحفظة قد يستلمون تحويلات منفصلة في ملف التصدير،
            //    لكن نتيجة كل التحويلات تذهب لنفس الحساب → نظهرها كصف واحد بالمجموع.
            $cat = $d['category'];
            $account = trim((string)$d['account']);
            if ($cat === 'WALLET') {
                // دمج بنفس رقم المحفظة (لو فاضي، نستخدم مفتاح فريد كـ fallback)
                $key = 'W:' . ($account !== '' ? $account : ('seq:' . (++$walletSeq)));
            } elseif ($cat === 'BENEF') {
                // المستفيدون: ندمج بهوية المستفيد (account = IBAN/حساب المستفيد)
                $key = 'B:' . ($account !== '' ? $account : ('seq:' . (++$benefSeq)));
            } else {
                // بنوك: دمج بالـ destination (IBAN > account > label)
                $key = 'BK:' . ($account !== '' ? $account : ('L:' . $d['label']));
            }

            if (!isset($grouped_dest[$key])) {
                $grouped_dest[$key] = $d;
                $grouped_dest[$key]['amount']        = 0.0;
                $grouped_dest[$key]['merged_count']  = 0;
            }
            $grouped_dest[$key]['amount']       += $d['amount'];
            $grouped_dest[$key]['merged_count'] += 1;
        }
        $destinations = array_values($grouped_dest);

        // إحصاءات إجمالية
        $tot_dest = 0; $sum_bank = 0; $sum_wallet = 0; $sum_benef = 0;
        foreach ($destinations as $d) {
            $amt = (float)$d['amount'];
            $tot_dest += $amt;
            if      ($d['category'] === 'BANK')   $sum_bank   += $amt;
            elseif  ($d['category'] === 'WALLET') $sum_wallet += $amt;
            elseif  ($d['category'] === 'BENEF')  $sum_benef  += $amt;
        }

        // معلومات الموظف الأساسية (من DATA.EMPLOYEES)
        $emp_info = $this->{$this->MODEL_NAME}->get_emp_info($emp_no);
        $emp_info = (is_array($emp_info) && count($emp_info) > 0) ? $emp_info[0] : [];

        header('Content-Type: application/json');
        echo json_encode([
            'ok'           => true,
            'emp_no'       => $emp_no,
            'emp_name'     => $emp_info['EMP_NAME']    ?? '',
            'emp_id'       => $emp_info['NAT_ID']      ?? ($emp_info['ID_NO'] ?? ''),
            'emp_branch'   => $emp_info['BRANCH_NAME'] ?? '',   // 🆕 المقر
            'batch_id'     => $batch_id,
            'requests'     => $requests,
            'destinations' => $destinations,
            'totals' => [
                'requests'     => $tot_req,
                'destinations' => $tot_dest,
                'sum_bank'     => $sum_bank,
                'sum_wallet'   => $sum_wallet,
                'sum_benef'    => $sum_benef,
                'match'        => abs($tot_req - $tot_dest) < 0.01,  // تحقق المطابقة
            ],
        ]);
    }

    // ====================================================================
    // 🆕 export_merge_audit — تقرير دمج الحركات لبنك معيّن
    // ====================================================================
    // يعرض كل حركات البنك مع تجميعها حسب مفتاح الدمج (IBAN/حساب)
    // المحاسب يشوف بوضوح أي حركات اندمجت معاً في الكشف البنكي.
    // URL: /payment_req/payment_req/export_merge_audit?batch_id=X&master_bank_no=Y
    function export_merge_audit()
    {
        $batch_id       = (int)($this->input->get('batch_id') ?: 0);
        $master_bank_no = (int)($this->input->get('master_bank_no') ?: 0);
        if (!$batch_id || !$master_bank_no) {
            echo '<script>alert("بيانات ناقصة"); history.back();</script>'; return;
        }

        $rows = $this->{$this->MODEL_NAME}->batch_bank_export($batch_id, $master_bank_no);
        if (!is_array($rows) || count($rows) === 0) {
            echo '<script>alert("لا توجد بيانات"); history.back();</script>'; return;
        }

        $bankName = $rows[0]['MASTER_BANK_NAME'] ?? 'البنك';
        $batchNo  = $rows[0]['BATCH_NO'] ?? $batch_id;
        $isWallet = (int)($rows[0]['PROVIDER_TYPE'] ?? 1) === 2;

        // تجميع حسب مفتاح الدمج (نفس منطق export_bank_file)
        $groups = [];   // key => array of original rows
        foreach ($rows as $r) {
            if ($isWallet) {
                // محفظات: لا دمج — كل صف بمفتاح فريد
                $key = 'W:' . count($groups) . '_' . uniqid();
            } else {
                $iban  = trim((string)($r['IBAN']        ?? ''));
                $accNo = trim((string)($r['ACCOUNT_NO']  ?? ''));
                $acc   = (int)($r['ACC_ID']              ?? 0);
                $ownId = trim((string)($r['OWNER_ID_NO'] ?? ''));
                $destId = '';
                if ($iban !== '')      $destId = 'IB:' . $iban;
                elseif ($accNo !== '') $destId = 'AC:' . $accNo;
                elseif ($acc > 0)      $destId = 'ID:' . $acc;
                else                   $destId = 'OW:' . $ownId;
                $key = $destId;
            }
            if (!isset($groups[$key])) $groups[$key] = [];
            $groups[$key][] = $r;
        }

        // فرز المجموعات: المدموجة (>1 صف) أولاً، ثم بقية الصفوف
        $mergedGroups = [];
        $singleGroups = [];
        foreach ($groups as $k => $g) {
            if (count($g) > 1) $mergedGroups[$k] = $g;
            else               $singleGroups[$k] = $g;
        }

        // إحصائيات
        $tot_orig    = count($rows);                   // إجمالي قبل الدمج
        $tot_after   = count($groups);                 // عدد الأسطر بعد الدمج
        $tot_saved   = $tot_orig - $tot_after;         // عدد المدموج
        $tot_merged_groups = count($mergedGroups);     // عدد مجموعات الدمج

        require_once APPPATH . 'third_party/phpoffice/vendor/autoload.php';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('تدقيق الدمج');
        $sheet->setRightToLeft(true);

        // R1: العنوان
        $sheet->setCellValue('A1', 'تدقيق دمج الحركات — ' . $bankName);
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['name'=>'Arial','size'=>14,'bold'=>true,'color'=>['rgb'=>'FFFFFF']],
            'fill' => ['fillType'=>\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,'startColor'=>['rgb'=>'1e293b']],
            'alignment' => ['horizontal'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,'vertical'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // R2: ملخص الدمج
        $summary = 'دفعة: ' . $batchNo . ' | حركات قبل الدمج: ' . $tot_orig
                 . ' | أسطر للبنك: ' . $tot_after
                 . ' | تم دمج: ' . $tot_saved . ' صف في ' . $tot_merged_groups . ' مجموعة';
        $sheet->setCellValue('A2', $summary);
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['name'=>'Arial','size'=>10,'bold'=>true],
            'fill' => ['fillType'=>\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,'startColor'=>['rgb'=>'fef3c7']],
            'alignment' => ['horizontal'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,'vertical'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(22);

        // R3: Headers
        $headers = ['#', 'حالة', 'مجموعة', 'الموظف الأصلي', 'اسم المستلم', 'رقم الهوية', 'الحساب / IBAN', 'الفرع', 'المبلغ'];
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue($col . '3', $h);
        }
        $sheet->getStyle('A3:I3')->applyFromArray([
            'font' => ['name'=>'Arial','size'=>11,'bold'=>true,'color'=>['rgb'=>'FFFFFF']],
            'fill' => ['fillType'=>\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,'startColor'=>['rgb'=>'334155']],
            'alignment' => ['horizontal'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,'vertical'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders'=>['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        ]);

        // R4+: البيانات
        $row = 4; $idx = 0; $grpIdx = 0;
        $writeGroup = function($g, $isMerged) use ($sheet, &$row, &$idx, &$grpIdx) {
            $grpIdx++;
            $startRow = $row;
            $count = count($g);
            $groupTotal = 0;
            foreach ($g as $r) {
                $idx++;
                $sheet->setCellValue('A' . $row, $idx);
                $sheet->setCellValue('B' . $row, $isMerged ? '🔗 مدموج' : '✓ مفرد');
                $sheet->setCellValue('C' . $row, $isMerged ? ('#' . $grpIdx . ' (' . $count . ')') : '—');
                $sheet->setCellValue('D' . $row, $r['EMP_NO'] . ' - ' . ($r['EMP_NAME'] ?? ''));
                $sheet->setCellValue('E' . $row, $r['OWNER_NAME'] ?? '');
                $sheet->setCellValueExplicit('F' . $row, (string)($r['OWNER_ID_NO'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $iban  = trim((string)($r['IBAN'] ?? ''));
                $accNo = trim((string)($r['BANK_ACCOUNT'] ?? $r['ACCOUNT_NO'] ?? ''));
                $sheet->setCellValueExplicit('G' . $row, $iban !== '' ? $iban : $accNo, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue('H' . $row, $r['BANK_BRANCH_NAME'] ?? '—');
                $sheet->setCellValue('I' . $row, (float)($r['TOTAL_AMOUNT'] ?? 0));
                $groupTotal += (float)($r['TOTAL_AMOUNT'] ?? 0);
                $row++;
            }
            // تلوين خلفية مجموعات الدمج
            if ($isMerged && $count > 1) {
                $sheet->getStyle('A' . $startRow . ':I' . ($row - 1))->applyFromArray([
                    'fill' => ['fillType'=>\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,'startColor'=>['rgb'=>'ecfdf5']],
                ]);
                // صف فاصل بمجموع المجموعة
                $sheet->setCellValue('A' . $row, '');
                $sheet->setCellValue('H' . $row, 'مجموع المجموعة:');
                $sheet->setCellValue('I' . $row, $groupTotal);
                $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                    'font' => ['name'=>'Arial','size'=>10,'bold'=>true,'color'=>['rgb'=>'065f46']],
                    'fill' => ['fillType'=>\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,'startColor'=>['rgb'=>'a7f3d0']],
                    'borders' => ['top'=>['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,'color'=>['rgb'=>'065f46']],'bottom'=>['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,'color'=>['rgb'=>'065f46']]],
                ]);
                $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $row++;
            }
        };

        // أولاً: المدموجة (مرتبة من الأكثر دمجاً)
        uasort($mergedGroups, function($a, $b){ return count($b) - count($a); });
        foreach ($mergedGroups as $g) $writeGroup($g, true);
        // ثانياً: المفردة
        foreach ($singleGroups as $g) $writeGroup($g, false);

        $lastDataRow = $row - 1;

        // ── تنسيق البيانات ──
        if ($lastDataRow >= 4) {
            $sheet->getStyle('A4:I' . $lastDataRow)->applyFromArray([
                'font' => ['name'=>'Arial','size'=>10],
                'alignment' => ['vertical'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders'=>['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,'color'=>['rgb'=>'cbd5e1']]],
            ]);
            $sheet->getStyle('I4:I' . $lastDataRow)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('A4:C' . $lastDataRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('I4:I' . $lastDataRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        }

        // ── عرض الأعمدة ──
        $widths = [5, 12, 11, 28, 26, 14, 30, 22, 13];
        foreach ($widths as $i => $w) {
            $sheet->getColumnDimension(chr(65 + $i))->setWidth($w);
        }
        $sheet->freezePane('A4');

        $filename = 'تدقيق_دمج_' . preg_replace('/[^\w؀-ۿ]+/u', '_', $bankName) . '_' . $batchNo . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    function export_bank_file()
    {
        $batch_id       = (int)($this->input->get('batch_id') ?: $this->p_batch_id);
        $master_bank_no = (int)($this->input->get('master_bank_no') ?: $this->p_master_bank_no);

        // 🆕 شهر الصرف اليدوي (إدخال المحاسب) — صيغة YYYYMM (مثل 202605)
        //    لو ما تم تمريره → نستخدم PAY_DATE/BATCH_DATE من الدفعة
        $manual_month = trim((string)$this->input->get('the_month') ?? '');
        if (!preg_match('/^\d{6}$/', $manual_month)) {
            $manual_month = '';  // غير صالح → تجاهل
        }

        // الأصل: BATCH_BANK_EXPORT (المُجمّع — موجود منذ البداية)
        $rows  = $this->{$this->MODEL_NAME}->batch_bank_export($batch_id, $master_bank_no ?: null);

        if (!is_array($rows) || count($rows) === 0) {
            echo '<script>alert("لا توجد بيانات"); history.back();</script>'; return;
        }

        // 🆕 لو محفظة → حاول الـ RAW (صف لكل طلب) فقط لو الـ proc مُتاحة
        //    استخدام wallet_raw يتطلّب نشر BATCH_WALLET_EXPORT_RAW أولاً في DB
        $isWalletExport = ($master_bank_no > 0)
                       && ((int)($rows[0]['PROVIDER_TYPE'] ?? 1) === 2);
        $useRawWallet = $isWalletExport && (int)$this->input->get('raw') === 1;  // مُعطّل افتراضياً للأمان

        if ($useRawWallet) {
            try {
                $rawRows = $this->{$this->MODEL_NAME}->batch_wallet_export_raw($batch_id, $master_bank_no);
                if (is_array($rawRows) && count($rawRows) > 0) {
                    $rows = $rawRows;
                }
            } catch (\Throwable $e) {
                error_log('[export_bank_file] wallet_raw failed: ' . $e->getMessage());
            }
        }

        // ═══════════════════════════════════════════════════════════════
        // تعريف صيغ التصدير بناءً على نماذج البنوك الفعلية
        // ═══════════════════════════════════════════════════════════════
        // Format A — Header + IBAN (الصيغة الأشيع)
        //   فلسطين (89) + افتراضي للباقي: الأردن (35)، القاهرة عمان (50)، العربي (70)، الوطني الإسلامي (4444)
        $format_a_banks = [89, 35, 50, 70, 4444];
        // Format B — ID + BRANCH (الإسلامي الفلسطيني فقط — بعد فصل الاستثمار)
        //   الإسلامي الفلسطيني (81) — BRANCH_NO من PRINT_NO تبع الفرع
        $format_b_banks = [81];
        // 🆕 Format INV — بنك الاستثمار (76) — قالب خاص بعنوان رواتب + شهر + إجمالي
        $format_inv_banks = [76];
        // 🆕 Format AISB — مُعطّل: نُقل البنك الإسلامي العربي (30) للقالب الجديد الموحّد (QUDS)
        $format_aisb_banks = [];
        // 🆕 Format QUDS — القالب الجديد الموحّد (LTR، عناوين إنجليزية، Company IBAN، 7 أعمدة، Sum=المبلغ المصروف)
        //    بنك القدس (82) + البنك الإسلامي العربي (30)
        $format_quds_banks = [82, 30];

        $batchNo = $rows[0]['BATCH_NO'] ?? $batch_id;

        // 🆕 الأصل: البنك يستقبل سطر واحد لكل **وجهة بنكية** (IBAN/حساب)
        //    حتى لو نفس IBAN فيه ACC_IDs مختلفة (مثلاً: نفس الشخص موظف + مستفيد)
        //    → نجمّعهم في سطر واحد لأن البنك يشوف وجهة واحدة (IBAN واحد).
        //
        //    أولوية مفتاح التجميع (من الأعم للأخص):
        //      1. IBAN  (الأعم — البنك يعرفه)
        //      2. ACCOUNT_NO
        //      3. ACC_ID (internal)
        //      4. OWNER_ID (fallback نادر)
        //
        //    ⚠️ المحفظات (PROVIDER_TYPE=2): لا تُدمج هنا — كل صف يبقى منفصلاً.
        //    الدمج للمحفظات يقرّره المحاسب لاحقاً عبر `?merge=1` داخل buildSheet.
        $grouped = [];
        $walletIdx = 0;
        foreach ($rows as $r) {
            $bk    = (int)($r['MASTER_BANK_NO'] ?? 0);
            $providerType = (int)($r['PROVIDER_TYPE'] ?? 1);

            // المحفظات: مفتاح فريد لكل صف — لا دمج خارجي
            if ($providerType === 2) {
                $walletIdx++;
                $key = $bk . '|WROW:' . $walletIdx;
                $grouped[$key] = $r;
                $grouped[$key]['TOTAL_AMOUNT'] = (float)($r['TOTAL_AMOUNT'] ?? 0);
                $grouped[$key]['_MERGED_COUNT'] = 1;
                continue;
            }

            // البنوك: دمج بحسب الوجهة (IBAN > Account > ACC_ID > OWNER_ID)
            $acc   = (int)($r['ACC_ID'] ?? 0);
            $iban  = trim((string)($r['IBAN'] ?? ''));
            $accNo = trim((string)($r['ACCOUNT_NO'] ?? ''));
            $ownId = trim((string)($r['OWNER_ID_NO'] ?? ''));

            $destId = '';
            if ($iban !== '')      $destId = 'IB:' . $iban;
            elseif ($accNo !== '') $destId = 'AC:' . $accNo;
            elseif ($acc > 0)      $destId = 'ID:' . $acc;
            else                   $destId = 'OW:' . $ownId;

            $key = $bk . '|' . $destId;

            if (!isset($grouped[$key])) {
                $grouped[$key] = $r;
                $grouped[$key]['TOTAL_AMOUNT'] = 0.0;
                $grouped[$key]['_MERGED_COUNT'] = 0;
            }
            $grouped[$key]['TOTAL_AMOUNT'] += (float)($r['TOTAL_AMOUNT'] ?? 0);
            $grouped[$key]['_MERGED_COUNT']++;
        }
        $rows = array_values($grouped);

        // تجميع حسب البنك
        $banks = [];
        foreach ($rows as $r) {
            $bk = $r['MASTER_BANK_NO'] ?? 0;
            if (!isset($banks[$bk])) $banks[$bk] = ['name' => $r['MASTER_BANK_NAME'] ?? 'غير محدد', 'rows' => []];
            $banks[$bk]['rows'][] = $r;
        }

        // helper: بناء sheet لبنك/محفظة بصيغة مطابقة لقالب البنك بدقة
        $buildSheet = function($sheet, $bkNo, $empRows) use ($format_a_banks, $format_b_banks, $format_inv_banks, $format_aisb_banks, $format_quds_banks, $manual_month) {
            // ── إعدادات عامة لكل الصيغ ──
            $sheet->setRightToLeft(true);

            // تمييز نوع الصيغة من أول صف:
            $providerType = (int)($empRows[0]['PROVIDER_TYPE'] ?? 1);
            $isWallet     = ($providerType === 2);
            $isFormatINV  = !$isWallet && in_array($bkNo, $format_inv_banks);
            $isFormatAISB = !$isWallet && !$isFormatINV && in_array($bkNo, $format_aisb_banks);
            $isFormatQUDS = !$isWallet && !$isFormatINV && !$isFormatAISB && in_array($bkNo, $format_quds_banks);
            $isFormatA    = !$isWallet && !$isFormatINV && !$isFormatAISB && !$isFormatQUDS && in_array($bkNo, $format_a_banks);
            $isFormatB    = !$isWallet && !$isFormatINV && !$isFormatAISB && !$isFormatQUDS && in_array($bkNo, $format_b_banks);
            // افتراضي لو ما صنّفنا → Format A
            if (!$isWallet && !$isFormatINV && !$isFormatAISB && !$isFormatQUDS && !$isFormatA && !$isFormatB) $isFormatA = true;

            // 🆕 ترتيب أبجدي حسب اسم المستلم (OWNER_NAME / EMP_NAME) — Arabic-aware
            // يعتمد على Collator من intl لو متاح، وإلا fallback لـ strcmp
            $_sortName = function($a, $b){
                $nA = trim((string)($a['OWNER_NAME'] ?? $a['EMP_NAME'] ?? ''));
                $nB = trim((string)($b['OWNER_NAME'] ?? $b['EMP_NAME'] ?? ''));
                return strcmp($nA, $nB);
            };
            if (class_exists('\Collator')) {
                $_col = new \Collator('ar');
                $_sortName = function($a, $b) use ($_col){
                    $nA = trim((string)($a['OWNER_NAME'] ?? $a['EMP_NAME'] ?? ''));
                    $nB = trim((string)($b['OWNER_NAME'] ?? $b['EMP_NAME'] ?? ''));
                    return $_col->compare($nA, $nB);
                };
            }
            usort($empRows, $_sortName);

            // ───────── Imports for styling ─────────
            $borderThin = new \PhpOffice\PhpSpreadsheet\Style\Border();
            $border = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ];
            $fontHeader = ['font' => ['name' => 'Arial', 'size' => 12]];
            $fontData   = ['font' => ['name' => 'Arial', 'size' => 11]];

            if ($isWallet) {
                // ═══════════════ Wallet (محفظة) — تنسيق PALPAY مُطابق لـ تنسيق بال بي.xlsx ═══════════════
                // قالب PALPAY:
                //   اسم Sheet: "شركة توزيع الكهرباء"
                //   R1: A1 = "كشف المستفيدين - شركة توزيع كهرباء محافظات قطاع غزة" (Arial 11 Bold)
                //   R2: Headers (8 أعمدة، Arial 11 Bold):
                //       A=الترتيب | B=اسم المستفيد - صاحب المحفظة | C=رقم بطاقة التعريف
                //       D=رقم جوال المحفظة | E=المحافظة | F=المدينة | G=المبلغ بالشيكل | H=العملة
                //   R3+: بيانات (Almarai 10)
                //   آخر: G = =SUM(G3:Glast) (Arial 11)

                // 🆕 لو merge=1 → جمّع الحركات بنفس (رقم الجوال + رقم الهوية) قبل الكتابة
                //    الدمج يضم الصفوف اللي عندها نفس **الجوال + الهوية** في صف واحد
                //    (لو واحد منهم مختلف → يبقى كل واحد بصف مستقل لضمان دقّة بال باي)
                $doMerge = (int)($_GET['merge'] ?? $_POST['merge'] ?? 0) === 1;
                if ($doMerge) {
                    $groups = [];   // (phone|id) → row
                    $noKeyRows = [];
                    foreach ($empRows as $r) {
                        $id    = trim((string)($r['OWNER_ID_NO'] ?? $r['EMP_ID']  ?? ''));
                        $phone = trim((string)($r['WALLET_NUMBER'] ?? $r['OWNER_PHONE'] ?? ''));
                        // لازم يكون عنده **الاثنين** للدمج (هوية + جوال)
                        if ($id === '' || $phone === '') {
                            $noKeyRows[] = $r;
                            continue;
                        }
                        $key = $phone . '|' . $id;
                        if (!isset($groups[$key])) {
                            $groups[$key] = $r;
                            $groups[$key]['TOTAL_AMOUNT'] = 0.0;
                        }
                        $groups[$key]['TOTAL_AMOUNT'] += (float)($r['TOTAL_AMOUNT'] ?? 0);
                    }
                    // إعادة البناء: المدموجين أولاً، ثم اللي بدون مفتاح
                    $empRows = array_merge(array_values($groups), $noKeyRows);
                    usort($empRows, $_sortName);
                }

                // اسم الـ Sheet (مطابق للقالب)
                $sheet->setTitle('شركة توزيع الكهرباء');

                // عرض الأعمدة (مطابق للقالب بدقة)
                $sheet->getColumnDimension('A')->setWidth(5.4);    // الترتيب
                $sheet->getColumnDimension('B')->setWidth(26.9);   // اسم المستفيد
                $sheet->getColumnDimension('C')->setWidth(13.6);   // رقم بطاقة التعريف
                $sheet->getColumnDimension('D')->setWidth(18.6);   // رقم جوال المحفظة
                $sheet->getColumnDimension('E')->setWidth(13.6);   // المحافظة
                $sheet->getColumnDimension('F')->setWidth(9.3);    // المدينة
                $sheet->getColumnDimension('G')->setWidth(13.0);   // المبلغ بالشيكل (وُسّع شوي لاستيعاب 99,999.99)
                $sheet->getColumnDimension('H')->setWidth(12.4);   // العملة

                // ─── ارتفاع الصفوف (مطابق للقالب) ───
                $sheet->getRowDimension(1)->setRowHeight(25.8);
                $sheet->getRowDimension(2)->setRowHeight(24.75);

                // ─── R1: عنوان الكشف (مدمج A1:H1، centered) ───
                $sheet->setCellValue('A1', 'كشف المستفيدين - شركة توزيع كهرباء محافظات قطاع غزة');
                $sheet->mergeCells('A1:H1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['name'=>'Arial','size'=>11,'bold'=>true],
                    'alignment' => [
                        'horizontal'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'bottom' => ['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM],
                    ],
                ]);

                // ─── R2: Headers (Arial 11 Bold + برتقالي #F4B084 + medium borders + center) ───
                $sheet->setCellValue('A2', 'الترتيب');
                $sheet->setCellValue('B2', 'اسم المستفيد - صاحب المحفظة');
                $sheet->setCellValue('C2', 'رقم بطاقة التعريف');
                $sheet->setCellValue('D2', 'رقم جوال المحفظة');
                $sheet->setCellValue('E2', 'المحافظة');
                $sheet->setCellValue('F2', 'المدينة');
                $sheet->setCellValue('G2', 'المبلغ بالشيكل');
                $sheet->setCellValue('H2', 'العملة ');
                $sheet->getStyle('A2:H2')->applyFromArray([
                    'font' => ['name'=>'Arial','size'=>11,'bold'=>true],
                    'fill' => [
                        'fillType'=>\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor'=>['rgb'=>'F4B084'],  // theme accent2 + tint 0.4
                    ],
                    'alignment' => [
                        'horizontal'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders'=>['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM],
                    ],
                ]);

                // ─── R3+: البيانات (Almarai 10) ───
                $row = 3; $n = 0; $totalAmtWlt = 0.0;
                foreach ($empRows as $r) {
                    $n++;
                    $owner_name = trim($r['OWNER_NAME']  ?? $r['EMP_NAME'] ?? '');
                    $owner_id   = trim($r['OWNER_ID_NO'] ?? $r['EMP_ID']  ?? '');
                    $phone      = trim((string)($r['WALLET_NUMBER'] ?? $r['OWNER_PHONE'] ?? ''));
                    $amt        = (float)($r['TOTAL_AMOUNT'] ?? 0);
                    $totalAmtWlt += $amt;

                    $sheet->setCellValue('A' . $row, $n);                                            // الترتيب
                    $sheet->setCellValue('B' . $row, $owner_name);                                   // اسم المستفيد
                    if ($owner_id !== '' && ctype_digit($owner_id)) {
                        $sheet->setCellValue('C' . $row, (int)$owner_id);
                    } else {
                        $sheet->setCellValueExplicit('C' . $row, $owner_id, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    }
                    if ($phone !== '' && ctype_digit($phone) && $phone[0] !== '0') {
                        $sheet->setCellValue('D' . $row, (int)$phone);
                    } else {
                        $sheet->setCellValueExplicit('D' . $row, $phone, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    }
                    $sheet->setCellValue('E' . $row, 'قطاع غزة');                                    // المحافظة (ثابت)
                    $sheet->setCellValue('F' . $row, 'غزة');                                          // المدينة (ثابت)
                    $sheet->setCellValue('G' . $row, $amt);                                           // المبلغ
                    $sheet->setCellValue('H' . $row, 'ILS');                                          // العملة

                    // ارتفاع 22.2 لكل صف بيانات (مطابق للقالب)
                    $sheet->getRowDimension($row)->setRowHeight(22.2);

                    $row++;
                }
                $lastDataRow = $row - 1;

                // ─── تنسيق البيانات: Almarai 10 + medium borders + center alignment ───
                if ($lastDataRow >= 3) {
                    $sheet->getStyle('A3:H' . $lastDataRow)->applyFromArray([
                        'font' => ['name'=>'Almarai','size'=>10],
                        'alignment' => [
                            'horizontal'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'vertical'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders'=>['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM],
                        ],
                    ]);
                    // عمود B (اسم المستفيد) → محاذاة لليمين (مطابق للقالب)
                    $sheet->getStyle('B3:B' . $lastDataRow)->getAlignment()
                          ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    // تنسيق رقمي للمبلغ
                    $sheet->getStyle('G3:G' . $lastDataRow)->getNumberFormat()->setFormatCode('0.00');
                }

                // ─── Row آخر: G = الإجمالي (قيمة محسوبة، ليس formula — لتجنب PhpSpreadsheet notice) ───
                if ($lastDataRow >= 3) {
                    $totRow = $row;
                    $sheet->setCellValue('G' . $totRow, $totalAmtWlt);
                    $sheet->getStyle('G' . $totRow)->applyFromArray([
                        'font' => ['name'=>'Arial','size'=>11],
                        'alignment' => [
                            'horizontal'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'vertical'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                    $sheet->getStyle('G' . $totRow)->getNumberFormat()->setFormatCode('0.00');
                }

            } elseif ($isFormatQUDS) {
                // ═══════════════ Format QUDS — القالب الجديد الموحّد (82 القدس + 30 الإسلامي العربي) ═══════════════
                // مطابق لقوالب "بنك القدس - معدل.xlsx" / "البنك الإسلامي العربي - معدل.xlsx" — LTR، عناوين إنجليزية، 7 أعمدة:
                //   R1: A=Company Name  | B=Gaza Electricity Distribution
                //   R2: A=Company IBAN  | B=<COMPANY_IBAN من المزود>
                //   R3: A=Remarks       | B=<YYYYMM شهر الصرف>
                //   R4: A=Details List
                //   R5: A..G headers (Sequence|Benficiary Name|Benficiary Name|Beneficiary ID|Beneficiary IBAN|Sum|currency)
                //   R6+: بيانات — F=Sum=المبلغ المصروف، G=currency=ILS
                // ملاحظة: القالب LTR (الترتيب من اليسار) — نلغي RTL لهذا الكشف فقط.

                $sheet->setRightToLeft(false);

                // عرض الأعمدة
                $sheet->getColumnDimension('A')->setWidth(13.0);   // Sequence
                $sheet->getColumnDimension('B')->setWidth(30.0);   // اسم عربي
                $sheet->getColumnDimension('C')->setWidth(28.0);   // اسم إنجليزي
                $sheet->getColumnDimension('D')->setWidth(15.0);   // Beneficiary ID
                $sheet->getColumnDimension('E')->setWidth(32.0);   // Beneficiary IBAN
                $sheet->getColumnDimension('F')->setWidth(12.0);   // Sum
                $sheet->getColumnDimension('G')->setWidth(10.0);   // currency

                // شهر الصرف (YYYYMM)
                if ($manual_month !== '') {
                    $monthYM = $manual_month;
                } else {
                    $batchDate = !empty($empRows[0]['PAY_DATE'])
                                  ? $empRows[0]['PAY_DATE']
                                  : (!empty($empRows[0]['BATCH_DATE'])
                                        ? $empRows[0]['BATCH_DATE']
                                        : date('Ymd'));
                    $monthYM = date('Ym', strtotime($batchDate));
                }

                // COMPANY_IBAN من المزود (PAYMENT_PROVIDERS_TB.COMPANY_IBAN) — حساب الشركة الموحّد (بنك فلسطين)
                // fallback لو فاضي أو placeholder (فيه '?') — نفس الحساب للقدس + الإسلامي العربي
                $cIban = trim($empRows[0]['COMPANY_IBAN'] ?? '');
                if ($cIban === '' || strpos($cIban, '?') !== false) $cIban = 'PS92PALS045105274280993003000';

                // R1-R4: معلومات الشركة (Label في A، القيمة في B)
                $sheet->setCellValue('A1', 'Company Name');
                $sheet->setCellValue('B1', 'Gaza Electricity Distribution');
                $sheet->setCellValue('A2', 'Company IBAN');
                $sheet->setCellValueExplicit('B2', $cIban, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue('A3', 'Remarks');
                // بنك القدس لا يريد الشهر — تُترك Remarks فاضية (B3)
                $sheet->setCellValue('A4', 'Details List');

                // R5: Headers (7 أعمدة) — نصوص مطابقة للقالب حرفياً
                $sheet->setCellValue('A5', 'Sequence');
                $sheet->setCellValue('B5', 'Benficiary Name');
                $sheet->setCellValue('C5', 'Benficiary Name');
                $sheet->setCellValue('D5', 'Beneficiary ID');
                $sheet->setCellValue('E5', 'Beneficiary IBAN');
                $sheet->setCellValue('F5', 'Sum');
                $sheet->setCellValue('G5', 'currency');

                // R6+ : البيانات
                $row = 6; $n = 0; $totalAmt = 0.0;
                foreach ($empRows as $r) {
                    $n++;
                    $owner_name    = trim($r['OWNER_NAME']    ?? $r['EMP_NAME'] ?? '');
                    $owner_name_en = trim($r['OWNER_NAME_EN'] ?? '');
                    $owner_id      = trim($r['OWNER_ID_NO']   ?? $r['EMP_ID']  ?? '');
                    $iban          = trim($r['IBAN']          ?? '');
                    $amt           = (float)($r['TOTAL_AMOUNT'] ?? 0);

                    $sheet->setCellValue('A' . $row, $n);
                    $sheet->setCellValue('B' . $row, $owner_name);
                    $sheet->setCellValue('C' . $row, $owner_name_en);
                    $sheet->setCellValueExplicit('D' . $row, (string)$owner_id, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit('E' . $row, (string)$iban,     \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValue('F' . $row, $amt);     // Sum = المبلغ المصروف
                    $sheet->setCellValue('G' . $row, 'ILS');
                    $totalAmt += $amt;
                    $row++;
                }
                $lastDataRow = $row - 1;

                // تنسيق: Calibri 11 (مطلوب من بنك القدس)، رؤوس bold
                $lastRow = max(5, $lastDataRow);
                $sheet->getStyle('A1:G' . $lastRow)->getFont()->setName('Calibri')->setSize(11);
                $sheet->getStyle('A1:A4')->getFont()->setBold(true);   // عناوين معلومات الشركة
                $sheet->getStyle('A5:G5')->getFont()->setBold(true);   // رؤوس الجدول
                $sheet->getStyle('A5:G5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                // ⬛ تظليل رمادي على قسم "Details List" فقط: صف 4 (Details List) + صف 5 (رؤوس الجدول)
                //    — مش على صفوف معلومات الشركة (1-3) مطابقةً للقالب
                $sheet->getStyle('A4:G5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D9D9D9');
                // حدود: معلومات الشركة (A1:B3) + جدول التفاصيل (A4 لآخر صف) — بدون حدود على الفراغ يمين الأعلى
                $sheet->getStyle('A1:B3')->applyFromArray([
                    'borders' => ['allBorders'=>['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                ]);
                $sheet->getStyle('A4:G' . $lastRow)->applyFromArray([
                    'borders' => ['allBorders'=>['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                ]);

                if ($lastDataRow >= 6) {
                    $sheet->getStyle('A6:A' . $lastDataRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('F6:F' . $lastDataRow)->getNumberFormat()->setFormatCode('###0.00');
                }

            } elseif ($isFormatA) {
                // ═══════════════ Format A — Header + IBAN ═══════════════
                // (فلسطين + افتراضي للأردن، القاهرة عمان، العربي، الوطني الإسلامي)

                // عرض الأعمدة (مطابق للقالب)
                $sheet->getColumnDimension('A')->setWidth(15.6);
                $sheet->getColumnDimension('B')->setWidth(30.6);
                $sheet->getColumnDimension('C')->setWidth(15.6);
                $sheet->getColumnDimension('D')->setWidth(50.6);
                $sheet->getColumnDimension('E')->setWidth(15.6);
                // F: عرض افتراضي

                // ارتفاع الصفوف الـ 5 الأولى
                for ($r = 1; $r <= 5; $r++) $sheet->getRowDimension($r)->setRowHeight(15.5);

                // R1: اسم الشركة
                $sheet->setCellValue('A1', 'اسم الشركة   ');
                $sheet->setCellValue('D1', ' شركة توزيع كهرباء محافظات  غزة   ');

                // ⚠️ R2 (ايبان الشركة) أُزيل بناءً على طلب البنك — الـ IBAN كان خطأ
                //    ولا يمثل حساباً للشركة. الترقيم استمر من R3 = الملاحظات.

                // R2: الملاحظات (شهر الصرف YYYYMM) — كانت R3
                // الأولوية: إدخال المحاسب اليدوي → PAY_DATE → BATCH_DATE → اليوم
                $sheet->setCellValue('A2', ' الملاحظات   ');
                if ($manual_month !== '') {
                    $monthYM = $manual_month;
                } else {
                    $batchDate = !empty($empRows[0]['PAY_DATE'])
                                  ? $empRows[0]['PAY_DATE']
                                  : (!empty($empRows[0]['BATCH_DATE'])
                                        ? $empRows[0]['BATCH_DATE']
                                        : date('Ymd'));
                    $monthYM = date('Ym', strtotime($batchDate));
                }
                $sheet->setCellValueExplicit('D2', $monthYM, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

                // R3: تفاصيل الحوالة (في B فقط) — كانت R4
                $sheet->setCellValue('B3', ' تفاصيل الحواله   ');

                // R4: Headers — كانت R5
                $sheet->setCellValue('A4', 'الترتيب  ');
                $sheet->setCellValue('B4', 'اسم المستفيد');
                $sheet->setCellValue('C4', 'رقم بطاقه التعريف');
                $sheet->setCellValue('D4', 'ايبان المستفيد');
                $sheet->setCellValue('E4', 'المبلغ');
                $sheet->setCellValue('F4', 'العملة');

                // Font 12 على الـ header rows (1-4)
                $sheet->getStyle('A1:F4')->applyFromArray($fontHeader);

                // R5+ : البيانات (مع borders thin) — كانت R6+
                $row = 5; $n = 0; $totalAmt = 0.0;
                foreach ($empRows as $r) {
                    $n++;
                    $owner_name = trim($r['OWNER_NAME'] ?? $r['EMP_NAME'] ?? '');
                    $owner_id   = trim($r['OWNER_ID_NO'] ?? $r['EMP_ID'] ?? '');
                    $amt        = (float)($r['TOTAL_AMOUNT'] ?? 0);
                    $sheet->setCellValue('A' . $row, $n);
                    $sheet->setCellValue('B' . $row, $owner_name);
                    $sheet->setCellValueExplicit('C' . $row, (string)$owner_id, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit('D' . $row, (string)($r['IBAN'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValue('E' . $row, $amt);
                    $sheet->setCellValue('F' . $row, 'ILS');
                    $totalAmt += $amt;
                    $row++;
                }
                if ($row > 5) {
                    $sheet->getStyle('A5:F' . ($row - 1))->applyFromArray($fontData);
                    $sheet->getStyle('A5:F' . ($row - 1))->applyFromArray($border); // BORDERS
                    $sheet->getStyle('E5:E' . ($row - 1))->getNumberFormat()->setFormatCode('###0.00');

                    // 🆕 صف الإجمالي
                    $totRow = $row;
                    $sheet->setCellValue('A' . $totRow, 'الإجمالي');
                    $sheet->mergeCells('A' . $totRow . ':D' . $totRow);
                    $sheet->setCellValue('E' . $totRow, $totalAmt);
                    $sheet->setCellValue('F' . $totRow, 'ILS');
                    $sheet->getStyle('A' . $totRow . ':F' . $totRow)->applyFromArray([
                        'font' => ['name'=>'Arial','size'=>12,'bold'=>true,'color'=>['rgb'=>'FFFFFF']],
                        'fill' => ['fillType'=>\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor'=>['rgb'=>'1e293b']],
                        'alignment' => [
                            'horizontal'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                            'vertical'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => ['allBorders'=>['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color'=>['rgb'=>'1e293b']]],
                    ]);
                    $sheet->getStyle('E' . $totRow)->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle('E' . $totRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle('F' . $totRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getRowDimension($totRow)->setRowHeight(25);
                }

            } elseif ($isFormatAISB) {
                // ═══════════════ Format AISB — البنك الإسلامي العربي (30) ═══════════════
                // قالب البنك (8 أعمدة):
                //   R1: A=اسم الشركة | E=شركة كهرباء توزيع المحافظات غزة
                //   R2: A=الملاحظات   | E=YYYYMM
                //   R3: B=تفاصيل الحوالة
                //   R4: A..H = headers (الترتيب|اسم المستفيد|اسم المستفيد بالإنجليزي|رقم بطاقة|رقم الحساب|ايبان|المبلغ|العملة)
                //   R5+: بيانات (مع borders)
                //   آخر: G=الإجمالي

                // عرض الأعمدة (مطابق للقالب)
                $sheet->getColumnDimension('A')->setWidth(5.1);
                $sheet->getColumnDimension('B')->setWidth(20.5);
                $sheet->getColumnDimension('C')->setWidth(36.7);
                $sheet->getColumnDimension('D')->setWidth(12.9);
                $sheet->getColumnDimension('E')->setWidth(11.4);
                $sheet->getColumnDimension('F')->setWidth(30.1);
                $sheet->getColumnDimension('G')->setWidth(10.6);
                $sheet->getColumnDimension('H')->setWidth(5);

                // الـ COMPANY_IBAN من المزود
                $cIban = $empRows[0]['COMPANY_IBAN'] ?? '';
                // شهر الصرف
                // الأولوية: إدخال المحاسب اليدوي → PAY_DATE → BATCH_DATE → اليوم
                if ($manual_month !== '') {
                    $monthYM = $manual_month;
                } else {
                    $batchDate = !empty($empRows[0]['PAY_DATE'])
                                  ? $empRows[0]['PAY_DATE']
                                  : (!empty($empRows[0]['BATCH_DATE'])
                                        ? $empRows[0]['BATCH_DATE']
                                        : date('Ymd'));
                    $monthYM = date('Ym', strtotime($batchDate));
                }

                // ─── Row 1: اسم الشركة | (شركة كهرباء توزيع المحافظات غزة) ───
                $sheet->setCellValue('A1', 'اسم الشركة   ');
                $sheet->setCellValue('E1', ' شركة توزيع كهرباء المحافظات  غزة   ');
                $sheet->getStyle('A1')->applyFromArray(['font' => ['name'=>'Arial','size'=>11]]);
                $sheet->getStyle('E1')->applyFromArray(['font' => ['name'=>'Arial','size'=>11]]);

                // ─── Row 2: الملاحظات | YYYYMM ───
                $sheet->setCellValue('A2', ' الملاحظات   ');
                $sheet->setCellValueExplicit('E2', $monthYM, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->getStyle('A2:E2')->applyFromArray(['font' => ['name'=>'Arial','size'=>11]]);

                // ─── Row 3: تفاصيل الحوالة ───
                $sheet->setCellValue('B3', ' تفاصيل الحواله   ');
                $sheet->getStyle('B3')->applyFromArray(['font' => ['name'=>'Arial','size'=>11]]);

                // ─── Row 4: Headers (8 أعمدة) ───
                $sheet->setCellValue('A4', 'الترتيب');
                $sheet->setCellValue('B4', 'اسم المستفيد');
                $sheet->setCellValue('C4', 'اسم المستفيد باللغة الانجليزية');
                $sheet->setCellValue('D4', 'رقم بطاقة التعريف');
                $sheet->setCellValue('E4', 'رقم الحساب');
                $sheet->setCellValue('F4', 'ايبان المستفيد');
                $sheet->setCellValue('G4', 'المبلغ');
                $sheet->setCellValue('H4', 'العملة');
                $sheet->getStyle('A4:H4')->applyFromArray([
                    'font' => ['name'=>'Arial','size'=>11,'bold'=>true],
                ]);

                // ─── Row 5+: البيانات (مع borders thin) ───
                $row = 5; $n = 0; $totalAmt = 0.0;
                foreach ($empRows as $r) {
                    $n++;
                    $owner_name    = trim($r['OWNER_NAME']    ?? $r['EMP_NAME'] ?? '');
                    // 🆕 الاسم بالإنجليزي — لاحقاً يأتي من PAYMENT_ACCOUNTS_TB.OWNER_NAME_EN
                    //    حالياً نتركه فاضي + fallback من حقول محتملة
                    $owner_name_en = trim($r['OWNER_NAME_EN'] ?? $r['EMP_NAME_EN'] ?? $r['NAME_EN'] ?? '');
                    $owner_id      = trim($r['OWNER_ID_NO']   ?? $r['EMP_ID']   ?? '');
                    $acct_no       = trim($r['BANK_ACCOUNT']  ?? $r['ACCOUNT_NO'] ?? '');
                    $iban          = trim($r['IBAN']          ?? '');
                    $amt           = (float)($r['TOTAL_AMOUNT'] ?? 0);

                    $sheet->setCellValue('A' . $row, $n);                                                                              // الترتيب
                    $sheet->setCellValue('B' . $row, $owner_name);                                                                     // اسم عربي
                    $sheet->setCellValue('C' . $row, $owner_name_en);                                                                  // اسم إنجليزي
                    $sheet->setCellValueExplicit('D' . $row, (string)$owner_id, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING); // رقم بطاقة
                    $sheet->setCellValueExplicit('E' . $row, (string)$acct_no,  \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING); // رقم الحساب
                    $sheet->setCellValueExplicit('F' . $row, (string)$iban,     \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING); // IBAN
                    $sheet->setCellValue('G' . $row, $amt);                                                                            // المبلغ
                    $sheet->setCellValue('H' . $row, 'ILS');                                                                           // العملة
                    $totalAmt += $amt;
                    $row++;
                }
                if ($row > 5) {
                    // borders + font للبيانات
                    $sheet->getStyle('A5:H' . ($row - 1))->applyFromArray([
                        'font' => ['name'=>'Arial','size'=>11],
                        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                    ]);
                    // تنسيق رقمي للمبلغ
                    $sheet->getStyle('G5:G' . ($row - 1))->getNumberFormat()->setFormatCode('###0.00');

                    // ─── Row N: الإجمالي (G column فقط، كما في القالب) ───
                    $totRow = $row;
                    $sheet->setCellValue('G' . $totRow, $totalAmt);
                    $sheet->getStyle('G' . $totRow)->applyFromArray([
                        'font' => ['name'=>'Arial','size'=>11,'underline'=>\PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLE],
                    ]);
                    $sheet->getStyle('G' . $totRow)->getNumberFormat()->setFormatCode('###0.00');
                }

            } elseif ($isFormatINV) {
                // ═══════════════ Format INV — بنك الاستثمار (76) ═══════════════
                // قالب البنك (مُطابق لـ تنسيق الاستثمار.xlsx من البنك):
                //   R1: شركة توزيع كهرباء محافظات غزة | Gaza Electricity Distribution
                //   R2: الرواتب والأجور                | Salaries & Wages
                //   R3: كشف البنك
                //   R4: عن شهر YYYYMM
                //   R5: رقم الموظف | اسم الموظف | اسم البنك | رقم الحساب | رقم الهوية | صافي الراتب
                //   R6+: بيانات
                //   آخر: إجمالي (count في A، total في F)

                // عرض الأعمدة (مطابق للقالب)
                $sheet->getColumnDimension('A')->setWidth(11.0);   // رقم الموظف
                $sheet->getColumnDimension('B')->setWidth(28.0);   // اسم الموظف
                $sheet->getColumnDimension('C')->setWidth(18.0);   // اسم البنك
                $sheet->getColumnDimension('D')->setWidth(13.0);   // رقم الحساب
                $sheet->getColumnDimension('E')->setWidth(13.0);   // رقم الهوية
                $sheet->getColumnDimension('F')->setWidth(13.0);   // صافي الراتب

                // ارتفاع الصفوف 1-5
                $sheet->getRowDimension(1)->setRowHeight(15.6);
                $sheet->getRowDimension(2)->setRowHeight(15.6);
                $sheet->getRowDimension(3)->setRowHeight(17.4);
                $sheet->getRowDimension(4)->setRowHeight(15.6);
                $sheet->getRowDimension(5)->setRowHeight(15.6);

                // شهر الصرف
                // الأولوية: إدخال المحاسب اليدوي → PAY_DATE → BATCH_DATE → اليوم
                if ($manual_month !== '') {
                    $monthYM = $manual_month;
                } else {
                    $batchDate = !empty($empRows[0]['PAY_DATE'])
                                  ? $empRows[0]['PAY_DATE']
                                  : (!empty($empRows[0]['BATCH_DATE'])
                                        ? $empRows[0]['BATCH_DATE']
                                        : date('Ymd'));
                    $monthYM = date('Ym', strtotime($batchDate));
                }

                // ─── Row 1: شركة توزيع كهرباء محافظات غزة | Gaza Electricity Distribution ───
                $sheet->setCellValue('A1', 'شركة توزيع كهرباء محافظات غزة');
                $sheet->setCellValue('E1', 'Gaza Electricity Distribution');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['name'=>'Arial','size'=>12,'bold'=>true],
                ]);
                $sheet->getStyle('E1')->applyFromArray([
                    'font' => ['name'=>'Arial','size'=>12,'bold'=>true],
                ]);

                // ─── Row 2: الرواتب والأجور | Salaries & Wages ───
                $sheet->setCellValue('A2', 'الرواتب والأجور');
                $sheet->setCellValue('E2', 'Salaries & Wages');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['name'=>'Arial','size'=>12,'bold'=>true],
                ]);
                $sheet->getStyle('E2')->applyFromArray([
                    'font' => ['name'=>'Arial','size'=>12,'bold'=>true],
                ]);

                // ─── Row 3: كشف البنك ───
                $sheet->setCellValue('C3', 'كشف البنك');
                $sheet->getStyle('C3')->applyFromArray([
                    'font' => ['name'=>'Arial','size'=>14,'bold'=>true],
                ]);

                // ─── Row 4: عن شهر YYYYMM ───
                $sheet->setCellValueExplicit('C4', 'عن شهر' . $monthYM, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->getStyle('C4')->applyFromArray([
                    'font' => ['name'=>'Arial','size'=>12,'bold'=>true],
                ]);

                // ─── Row 5: Headers (Bold + Underline) ───
                $sheet->setCellValue('A5', 'رقم الموظف');
                $sheet->setCellValue('B5', 'اسم الموظف');
                $sheet->setCellValue('C5', 'اسم البنك');
                $sheet->setCellValue('D5', 'رقم الحساب');
                $sheet->setCellValue('E5', 'رقم الهوية');
                $sheet->setCellValue('F5', 'صافي الراتب');
                $sheet->getStyle('A5:F5')->applyFromArray([
                    'font' => ['name'=>'Arial','size'=>12,'bold'=>true,'underline'=>\PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLE],
                ]);

                // ─── Row 6+ : البيانات ───
                $row = 6; $n = 0; $totalAmt = 0.0;
                foreach ($empRows as $r) {
                    $n++;
                    $emp_no     = (int)($r['EMP_NO'] ?? 0);
                    $owner_name = trim($r['OWNER_NAME'] ?? $r['EMP_NAME'] ?? '');
                    $owner_id   = trim($r['OWNER_ID_NO'] ?? $r['EMP_ID'] ?? '');
                    $acct_no    = trim($r['BANK_ACCOUNT'] ?? $r['ACCOUNT_NO'] ?? '');
                    $amt        = (float)($r['TOTAL_AMOUNT'] ?? 0);

                    $sheet->setCellValue('A' . $row, $emp_no);                                                                              // رقم الموظف
                    $sheet->setCellValue('B' . $row, $owner_name);                                                                          // اسم الموظف
                    $sheet->setCellValue('C' . $row, 'الإستثمار الفلسطيني');                                                                // اسم البنك (من القالب)
                    $sheet->setCellValueExplicit('D' . $row, (string)$acct_no, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);       // رقم الحساب
                    $sheet->setCellValueExplicit('E' . $row, (string)$owner_id, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);      // رقم الهوية
                    $sheet->setCellValue('F' . $row, $amt);                                                                                 // صافي الراتب
                    $totalAmt += $amt;
                    $row++;
                }
                // تنسيق رقمي للمبلغ
                if ($row > 6) {
                    $sheet->getStyle('F6:F' . ($row - 1))->getNumberFormat()->setFormatCode('###0.00');
                }

                // ─── Row N: الإجمالي (count, total) ───
                if ($row > 6) {
                    $totRow = $row;
                    $sheet->setCellValue('A' . $totRow, $n);            // عدد الموظفين
                    $sheet->setCellValue('F' . $totRow, $totalAmt);     // إجمالي صافي الراتب
                    $sheet->getStyle('A' . $totRow)->applyFromArray([
                        'font' => ['name'=>'Arial','size'=>12,'bold'=>true,'underline'=>\PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLE],
                    ]);
                    $sheet->getStyle('F' . $totRow)->applyFromArray([
                        'font' => ['name'=>'Arial','size'=>12,'bold'=>true,'underline'=>\PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLE],
                    ]);
                    $sheet->getStyle('F' . $totRow)->getNumberFormat()->setFormatCode('###0.00');
                }

            } elseif ($isFormatB) {
                // ═══════════════ Format B — الإسلامي الفلسطيني (81) ═══════════════
                // قالب البنك (مُطابق لـ تنسيق الاسلامي الفلسطيني.xlsx — 8 أعمدة):
                //   A: ID_NO1     (رقم الهوية)
                //   B: BANK       (دائماً 81)
                //   C: BRANCH_NO  (PRINT_NO تبع الفرع)
                //   D: ACNT_NO    (رقم الحساب)
                //   E: NET_EARN   (صافي الراتب)
                //   F: MONTH      (YYYY-MM من PAY_DATE)
                //   G: EMP_NAME   (اسم الموظف)
                //   H: LED_CODE   (دائماً 3101)

                // عرض الأعمدة
                $sheet->getColumnDimension('A')->setWidth(13.0);
                $sheet->getColumnDimension('B')->setWidth(8.0);
                $sheet->getColumnDimension('C')->setWidth(12.0);
                $sheet->getColumnDimension('D')->setWidth(12.0);
                $sheet->getColumnDimension('E')->setWidth(11.0);
                $sheet->getColumnDimension('F')->setWidth(10.0);
                $sheet->getColumnDimension('G')->setWidth(28.0);
                $sheet->getColumnDimension('H')->setWidth(11.0);
                $sheet->getRowDimension(1)->setRowHeight(15.5);

                // R1: Headers (Arial 12 Bold Underline)
                $sheet->setCellValue('A1', 'ID_NO1');
                $sheet->setCellValue('B1', 'BANK');
                $sheet->setCellValue('C1', 'BRANCH_NO');
                $sheet->setCellValue('D1', 'ACNT_NO');
                $sheet->setCellValue('E1', 'NET_EARN');
                $sheet->setCellValue('F1', 'MONTH');
                $sheet->setCellValue('G1', 'EMP_NAME');
                $sheet->setCellValue('H1', 'LED_CODE');
                $sheet->getStyle('A1:H1')->applyFromArray($fontHeader);

                // شهر الصرف بصيغة YYYY-MM (مطابق لقالب البنك: "2025-08")
                // الأولوية: إدخال المحاسب اليدوي (YYYYMM → يُحوَّل لـ YYYY-MM) → PAY_DATE → BATCH_DATE → اليوم
                if ($manual_month !== '') {
                    // المحاسب أدخل YYYYMM → نحوّله لـ YYYY-MM (الصيغة المطلوبة من البنك)
                    $monthYMDash = substr($manual_month, 0, 4) . '-' . substr($manual_month, 4, 2);
                } else {
                    $batchDate = !empty($empRows[0]['PAY_DATE'])
                                  ? $empRows[0]['PAY_DATE']
                                  : (!empty($empRows[0]['BATCH_DATE'])
                                        ? $empRows[0]['BATCH_DATE']
                                        : date('Ymd'));
                    $monthYMDash = date('Y-m', strtotime($batchDate));
                }

                // R2+ : البيانات
                $row = 2;
                foreach ($empRows as $r) {
                    $owner_name = trim($r['OWNER_NAME'] ?? $r['EMP_NAME'] ?? '');
                    $owner_id   = trim($r['OWNER_ID_NO'] ?? $r['EMP_ID'] ?? '');
                    // BRANCH_NO: من PRINT_NO تبع الفرع (للإسلامي الفلسطيني)
                    //   لو NULL أو 0 → 0
                    $branchNo = (int)($r['BRANCH_PRINT_NO'] ?? 0);
                    $sheet->setCellValueExplicit('A' . $row, (string)$owner_id, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValue('B' . $row, 81);                                                                                       // BANK = 81
                    $sheet->setCellValue('C' . $row, $branchNo);                                                                                // BRANCH_NO
                    $sheet->setCellValueExplicit('D' . $row, (string)($r['BANK_ACCOUNT'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING); // ACNT_NO
                    $sheet->setCellValue('E' . $row, (float)($r['TOTAL_AMOUNT'] ?? 0));                                                         // NET_EARN
                    $sheet->setCellValueExplicit('F' . $row, $monthYMDash, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);                // MONTH (YYYY-MM)
                    $sheet->setCellValue('G' . $row, $owner_name);                                                                              // EMP_NAME
                    $sheet->setCellValue('H' . $row, 3101);                                                                                     // LED_CODE = 3101
                    $row++;
                }
                if ($row > 2) {
                    $sheet->getStyle('A2:H' . ($row - 1))->applyFromArray($fontData);
                    $sheet->getStyle('E2:E' . ($row - 1))->getNumberFormat()->setFormatCode('###0.00');
                }
            }
        };

        if ($master_bank_no && count($banks) === 1) {
            // بنك واحد → ملف واحد بـ sheet واحد
            $bkNo = array_key_first($banks);
            $bkData = $banks[$bkNo];
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            // 🆕 اسم الـ sheet: "ورقة1" (مطابق لقوالب البنوك)
            $sheet->setTitle('ورقة1');
            $buildSheet($sheet, $bkNo, $bkData['rows']);
            $filename = $bkData['name'] . '_' . $batchNo . '_' . date('Ymd') . '.xlsx';
        } else {
            // كل البنوك → ملف فيه sheet لكل بنك
            $spreadsheet = new Spreadsheet();
            $spreadsheet->removeSheetByIndex(0);
            foreach ($banks as $bkNo => $bkData) {
                $sheet = $spreadsheet->createSheet();
                $sheet->setTitle(mb_substr($bkData['name'], 0, 31));
                $buildSheet($sheet, $bkNo, $bkData['rows']);
            }
            $filename = 'تصدير_بنوك_' . $batchNo . '_' . date('Ymd') . '.xlsx';
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ==================== TOTALS (AJAX) ====================
    function get_totals()
    {
        $the_month = $this->p_the_month;
        $emp_no    = $this->p_emp_no;
        $req_type  = $this->p_req_type;
        $status    = $this->p_status;
        $branch_no = $this->p_branch_no;

        $has_filter = ($the_month || $emp_no || ($status !== null && $status !== '') || $branch_no || $req_type);
        if (!$has_filter) {
            echo json_encode(['ok' => false, 'msg' => 'يجب تحديد فلتر']); return;
        }

        $where_sql = '';
        if ($this->user->branch == 1) {
            if ($branch_no) $where_sql .= " AND EMP_PKG.GET_EMP_BRANCH(D.EMP_NO)='{$branch_no}' ";
        } else {
            $where_sql .= " AND EMP_PKG.GET_EMP_BRANCH(D.EMP_NO)='{$this->user->branch}' ";
        }
        if ($emp_no)    $where_sql .= " AND D.EMP_NO='{$emp_no}' ";
        if ($the_month) $where_sql .= " AND M.THE_MONTH='{$the_month}' ";
        if ($req_type)  $where_sql .= " AND M.REQ_TYPE='{$req_type}' ";
        if ($status !== null && $status !== '') $where_sql .= " AND M.STATUS='{$status}' ";

        $result = $this->{$this->MODEL_NAME}->get_totals($where_sql);
        $branches_data = [];
        if ($this->user->branch == 1 && !$branch_no) {
            $br = $this->{$this->MODEL_NAME}->get_totals_branch($where_sql);
            if (isset($br['ok']) && $br['ok']) { $branches_data = $br['branches']; }
        }

        $result['branches'] = $branches_data;
        header('Content-Type: application/json');
        echo json_encode($result);
    }

    private function _month_report_breakdown($row)
    {
        $result = ['breakdown_available' => true];
        foreach (['TOTAL_BASE_USED', 'TOTAL_ALLOWANCE_USED', 'TOTAL_CASH_USED',
                  'TOTAL_PAID_BASE', 'TOTAL_PAID_ALLOWANCE', 'TOTAL_SALARY_323', 'TOTAL_ALLOWANCE_325'] as $key) {
            $value = $this->_report_number($row, $key);
            $result[strtolower($key)] = $value;
            if ($value === null) $result['breakdown_available'] = false;
        }
        return $result;
    }

    // ==================== CHECK MONTH STATUS ====================
    function check_month_status()
    {
        $month = intval($this->input->post('month'));
        if (!$month) { echo json_encode(['status' => 'error']); return; }

        $r = $this->{$this->MODEL_NAME}->get_month_status($month);

        $emp_count  = intval($r['EMP_COUNT'] ?? 0);
        $total_net  = floatval($r['TOTAL_NET'] ?? 0);
        $total_323  = floatval($r['TOTAL_323'] ?? 0);
        $total_draft    = floatval($r['TOTAL_DRAFT'] ?? 0);
        $total_approved = floatval($r['TOTAL_APPROVED'] ?? 0);
        $total_paid     = floatval($r['TOTAL_PAID'] ?? 0);

        header('Content-Type: application/json');
        echo json_encode(array_merge([
            'status'          => 'success',
            'calculated'      => ($emp_count > 0),
            'emp_count'       => $emp_count,
            'total_net'       => $total_net,
            'total_323'       => $total_323,
            'source'          => $r['SOURCE'] ?? 'none',
            'total_draft'     => $total_draft,
            'total_approved'  => $total_approved,
            'total_paid'      => $total_paid,
            // فصل المنفّذ نقد/مُرحّل — null لو البروسيجر القديم لسا منشور (الواجهة تتجاهله)
            'total_paid_cash'    => isset($r['TOTAL_PAID_CASH'])    ? floatval($r['TOTAL_PAID_CASH'])    : null,
            'total_paid_carried' => isset($r['TOTAL_PAID_CARRIED']) ? floatval($r['TOTAL_PAID_CARRIED']) : null,
            'total_base_used'    => isset($r['TOTAL_BASE_USED']) ? floatval($r['TOTAL_BASE_USED']) : null,
            // مصدر الرواتب (ملف 48) — null لو البروسيجر القديم لسا منشور (الواجهة تتجاهله)
            'salary_month_emps'  => isset($r['SALARY_MONTH_EMPS'])  ? intval($r['SALARY_MONTH_EMPS'])  : null,
            'last_salary_month'  => isset($r['LAST_SALARY_MONTH'])  ? intval($r['LAST_SALARY_MONTH'])  : null,
            'total_available' => max($total_323 - (isset($r['TOTAL_BASE_USED'])
                ? floatval($r['TOTAL_BASE_USED']) : $total_draft + $total_approved + $total_paid), 0),
            'req_count'       => intval($r['REQ_COUNT'] ?? 0)
        ], $this->_month_report_breakdown($r)));
    }

    // ==================== 🆕 META UPDATE ====================
    // تعديل البند + الملاحظات من داخل الطلب المحفوظ (مسودة فقط)
    // الصلاحية مربوطة بصلاحية إنشاء الطلبات (نفس مرساة diagnose_excel_emps)
    function master_meta_update()
    {
        if (!HaveAccess(base_url('payment_req/payment_req/create'))) { echo 'ليس لديك صلاحية'; return; }
        $req_id    = (int)$this->input->post('req_id');
        $pay_type  = trim((string)$this->input->post('pay_type'));
        $note      = trim((string)$this->input->post('note'));
        $the_month = trim((string)$this->input->post('the_month'));
        if (!$req_id) { echo 'معرف الطلب مطلوب'; return; }
        echo $this->{$this->MODEL_NAME}->req_meta_update(
            $req_id,
            ($pay_type !== '' ? $pay_type : null),
            ($note !== '' ? $note : null),
            ($the_month !== '' ? (int)$the_month : null)
        );
    }

    // ==================== 🆕 DIAGNOSE EXCEL EMPS ====================
    // يستخدمه wizParseExcel() لإظهار السبب الحقيقي لأي موظف
    // قبل المعاينة (تكرار، غير محتسب، رصيد صفر، متقاعد، ...إلخ)
    // POST: emp_nos (CSV), the_month, req_type, req_id (optional)
    function diagnose_excel_emps()
    {
        if (!HaveAccess(base_url('payment_req/payment_req/bulk_create'))) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'ليس لديك صلاحية']); return;
        }
        $emp_nos   = trim((string)$this->input->post('emp_nos'));
        $the_month = (int)$this->input->post('the_month');
        $req_type  = (int)$this->input->post('req_type');
        $req_id    = (int)$this->input->post('req_id'); // 0 = طلب جديد

        if ($emp_nos === '') {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'لا يوجد موظفين للتشخيص']); return;
        }
        if ($the_month <= 0 || $req_type <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'الشهر ونوع الطلب مطلوبان']); return;
        }

        $result = $this->{$this->MODEL_NAME}->diagnose_excel_emps($emp_nos, $the_month, $req_type, $req_id, $this->_type2_remaining_mode());
        $msg  = $result['msg']  ?? '';
        $rows = $result['rows'] ?? [];

        if ($msg !== '1' && $msg !== '') {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => $msg]); return;
        }

        if (is_array($rows) && count($rows) > 0) {
            array_walk_recursive($rows, function (&$val) {
                if (is_string($val)) { $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
            });
        }

        // ملخص جاهز للـ UI
        $summary = ['OK' => 0, 'WARNING' => 0, 'ERROR' => 0, 'ALLOWED' => 0];
        foreach ($rows as $r) {
            $st = (string)($r['STATUS'] ?? '');
            if ($st === 'OK')                              $summary['OK']++;
            elseif (strpos($st, 'WARNING_') === 0)         $summary['WARNING']++;
            elseif (strpos($st, 'ERROR_') === 0)           $summary['ERROR']++;
            if ((int)($r['ALLOWED'] ?? 0) === 1)           $summary['ALLOWED']++;
        }

        header('Content-Type: application/json');
        $json = json_encode([
            'ok'      => true,
            'data'    => $rows,
            'summary' => $summary,
            'count'   => count($rows),
        ]);
        echo $json ?: json_encode(['ok' => false, 'msg' => json_last_error_msg()]);
    }

    // ==================== BULK PREVIEW ====================
    function bulk_preview()
    {
        if (!HaveAccess(base_url('payment_req/payment_req/bulk_create'))) {
            echo json_encode(['ok' => false, 'msg' => 'ليس لديك صلاحية']); return;
        }
        $params = $this->_bulk_params();

        $result = $this->{$this->MODEL_NAME}->bulk_preview($params);

        $msg  = $result['msg']  ?? '';
        $rows = $result['rows'] ?? [];

        if ($msg !== '1' && $msg !== '') {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => $msg]); return;
        }

        if ((int)$params['req_type'] === 4) {
            try {
                $rows = $this->_type4_preview_rows($rows, $params,
                    $this->input->post('emp_amounts'), $this->input->post('emp_nos'));
            } catch (\InvalidArgumentException $e) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'msg' => $e->getMessage()]); return;
            }
        }

        if (is_array($rows) && count($rows) > 0) {
            array_walk_recursive($rows, function (&$val) {
                if (is_string($val)) { $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
            });
        }

        header('Content-Type: application/json');
        $json = json_encode([
            'ok'    => true, 'data'  => $rows,
            'count' => count($rows), 'params' => $params,
        ]);
        echo $json ?: json_encode(['ok' => false, 'msg' => json_last_error_msg()]);
    }

    // ==================== BULK CREATE ====================
    function bulk_create()
    {
        if (!HaveAccess(base_url('payment_req/payment_req/bulk_create'))) {
            echo json_encode(['ok' => false, 'msg' => 'ليس لديك صلاحية']); return;
        }
        $params         = $this->_bulk_params();
        $params['note'] = $this->p_note ?: 'bulk';
        // فحص التكرار يتم داخل Oracle procedure — PAYMENT_REQ_INSERT
        $result = $this->{$this->MODEL_NAME}->bulk_create($params);
        header('Content-Type: application/json');
        echo json_encode($result);
    }

    // ==================== EXPORT EXCEL — حسب فلاتر شاشة البحث ====================
    // يدعم: branch_no, emp_no, the_month, req_type, status (واحد أو قائمة بفواصل), view_mode (master|detail)
    // 🆕 req_ids (اختياري): قائمة طلبات مفصولة بفواصل — لو تم تمريرها تتجاوز كل الفلاتر الأخرى
    // يقرأ من GET أو POST (نُستدعى عادة بـ GET في new tab)
    function export_excel()
    {
        $branch_no = $this->input->get_post('branch_no');
        $emp_no    = $this->input->get_post('emp_no');
        $the_month = $this->input->get_post('the_month');
        $req_type  = $this->input->get_post('req_type');
        $pay_type  = $this->input->get_post('pay_type');
        $status    = $this->input->get_post('status');
        $req_ids   = $this->input->get_post('req_ids');
        $view_mode = $this->input->get_post('view_mode') ?: 'detail';
        $source    = $this->input->get_post('source'); // batch | (default = index)
        $is_master = ($view_mode === 'master');
        $is_batch  = ($source === 'batch');

        // عناوين حسب السياق (batch vs index)
        $sheet_title  = $is_batch ? 'الطلبات الجاهزة للاحتساب' : 'طلبات الصرف';
        $main_title   = $is_batch ? '📋 الطلبات الجاهزة للاحتساب — حسب فلاتر البحث' : '📊 طلبات الصرف — حسب فلاتر البحث';
        $file_prefix  = $is_batch ? 'طلبات_جاهزة_للاحتساب' : 'طلبات_صرف';

        // normalize empties to null
        foreach (['branch_no','emp_no','the_month','req_type','pay_type','req_ids'] as $k) {
            if ($$k === '' || $$k === false || $$k === '-1') $$k = null;
        }

        // sanitize digits
        if ($branch_no !== null) $branch_no = preg_replace('/[^0-9]/', '', $branch_no);
        if ($emp_no    !== null) $emp_no    = preg_replace('/[^0-9]/', '', $emp_no);
        if ($the_month !== null) $the_month = preg_replace('/[^0-9]/', '', $the_month);
        if ($req_type  !== null) $req_type  = preg_replace('/[^0-9]/', '', $req_type);
        if ($pay_type  !== null) $pay_type  = preg_replace('/[^0-9]/', '', $pay_type);

        // sanitize req_ids (digits + comma)
        if ($req_ids !== null) {
            $req_ids = preg_replace('/[^0-9,]/', '', (string)$req_ids);
            $req_ids = trim($req_ids, ',');
            if ($req_ids === '') $req_ids = null;
        }

        // sanitize status (digits + comma only)
        if ($status !== null && $status !== '') {
            $status = preg_replace('/[^0-9,]/', '', (string)$status);
            $status = trim($status, ',');
            if ($status === '') $status = null;
        } else {
            $status = null;
        }

        $where_sql = '';
        if ($this->user->branch == 1) {
            $where_sql .= ($branch_no != null) ? " AND EMP_PKG.GET_EMP_BRANCH(D.EMP_NO)='{$branch_no}'" : '';
        } else {
            $where_sql .= " AND EMP_PKG.GET_EMP_BRANCH(D.EMP_NO)='{$this->user->branch}'";
        }
        $where_sql .= ($emp_no != null)    ? " AND D.EMP_NO='{$emp_no}'" : '';
        $where_sql .= ($the_month != null) ? " AND M.THE_MONTH='{$the_month}'" : '';
        $where_sql .= ($req_type != null)  ? " AND M.REQ_TYPE='{$req_type}'" : '';
        $where_sql .= ($pay_type != null)  ? " AND M.PAY_TYPE='{$pay_type}'" : '';  // 🆕 البيان
        if ($status !== null && $status !== '') {
            $where_sql .= (strpos($status, ',') !== false)
                ? " AND M.STATUS IN ({$status}) "
                : " AND M.STATUS='{$status}' ";
        }
        // 🆕 لو في req_ids: نضيفها كـ IN clause (تتقاطع مع باقي الفلاتر)
        if ($req_ids !== null) {
            $where_sql .= " AND M.REQ_ID IN ({$req_ids}) ";
        }
        // 🆕 فلتر اعتماد الرقابة (0/1/2)
        $raqaba_f = $this->input->get_post('raqaba');
        if ($raqaba_f !== null && $raqaba_f !== '' && $raqaba_f !== '-1') {
            if ((int)$raqaba_f === 0) {
                // بانتظار الرقابة = معتمد محاسبياً (1 أو 3) ولسا ما تقرر رقابياً — استثنِ المسودات
                $where_sql .= " AND NVL(M.RAQABA_STATUS, 0) = 0 AND M.STATUS IN (1, 3) ";
            } else {
                $where_sql .= " AND NVL(M.RAQABA_STATUS, 0) = " . (int)$raqaba_f . " ";
            }
        }
        // 🆕 فلتر الملاحظات (بحث LIKE في M.NOTE)
        $note_f = trim((string)$this->input->get_post('note'));
        if ($note_f !== '') {
            $nf = str_replace("'", "''", $note_f);
            $where_sql .= " AND UPPER(M.NOTE) LIKE UPPER('%{$nf}%') ";
        }

        // جلب البيانات (master أو detail حسب طريقة العرض الحالية)
        if ($is_master) {
            $master_where = str_replace('D.EMP_NO', '(SELECT MIN(DD.EMP_NO) FROM GFC.PAYMENT_REQ_DETAIL_TB DD WHERE DD.REQ_ID=M.REQ_ID)', $where_sql);
            $master_where = str_replace("EMP_PKG.GET_EMP_BRANCH(D.EMP_NO)", "EMP_PKG.GET_EMP_BRANCH((SELECT MIN(DD.EMP_NO) FROM GFC.PAYMENT_REQ_DETAIL_TB DD WHERE DD.REQ_ID=M.REQ_ID))", $master_where);
            $rows = $this->{$this->MODEL_NAME}->get_list_master($master_where, 0, 999999);
        } else {
            $rows = $this->{$this->MODEL_NAME}->get_list($where_sql, 0, 999999);
        }

        if (!is_array($rows) || count($rows) === 0) {
            echo '<script>alert("لا توجد بيانات للتصدير"); history.back();</script>'; return;
        }

        // إجمالي حسب الفلاتر (للهيدر)
        $totals = $this->{$this->MODEL_NAME}->get_totals($where_sql);
        $t_amount = (float)($totals['amount'] ?? 0);
        $t_count  = (int)($totals['count'] ?? 0);

        $status_map = [0 => 'مسودة', 1 => 'معتمد', 2 => 'منفّذ للصرف', 3 => 'معتمد جزئي', 4 => 'محتسب', 9 => 'ملغى'];

        // 🆕 نص حالة اعتماد الرقابة — الاسم من الثابت 543
        $raqaba_txt = function ($st, $rqs, $name) {
            $rqs = (int)$rqs;
            if ($rqs == 1 || $rqs == 2 || in_array((int)$st, [1, 3])) return $name;
            return '—';
        };

        // وصف الفلاتر للهيدر
        $filter_parts = [];
        if ($the_month) {
            $mn = (int)substr($the_month, 4, 2);
            $mname = ['', 'يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'][$mn] ?? $the_month;
            $filter_parts[] = "الشهر: {$mname} " . substr($the_month, 0, 4);
        }
        if ($branch_no) {
            $br_name = $this->New_rmodel->general_get('SETTING_PKG', 'GCC_BRANCHES_TB_GET', [
                array('name'=>':P_NO','value'=>(int)$branch_no,'type'=>SQLT_INT,'length'=>-1),
                array('name'=>':REF_CUR_OUT','value'=>'cursor','type'=>OCI_B_CURSOR),
                array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>SQLT_CHR,'length'=>200),
            ]);
            $filter_parts[] = "المقر: " . (is_array($br_name) && count($br_name) > 0 ? ($br_name[0]['NAME'] ?? $branch_no) : $branch_no);
        }
        if ($emp_no)   $filter_parts[] = "الموظف: {$emp_no}";
        if ($req_type) $filter_parts[] = "نوع الطلب: {$req_type}";
        if ($status !== null && $status !== '') {
            $names = array_map(function($s) use ($status_map) { return $status_map[(int)$s] ?? $s; }, explode(',', $status));
            $filter_parts[] = "الحالة: " . implode(' / ', $names);
        }
        if ($req_ids !== null) {
            $req_count = count(explode(',', $req_ids));
            $filter_parts[] = "طلبات محدّدة: {$req_count}";
        }
        $filter_line = $filter_parts ? implode(' · ', $filter_parts) : 'بدون فلتر';

        // ==== 🆕 SUMMARY METRICS (موظفين فريدين + توزيع حسب النوع + الأشهر) ====
        $sum_by_type  = [];  // {name: [count, amount]}
        $sum_by_month = [];  // {month: [count, amount]}
        $sum_unique   = 0;
        $sum_total_rows = 0;
        $sum_dup_count  = 0;

        if ($is_master) {
            // group by type + month من الصفوف
            foreach ($rows as $r) {
                $tn = $r['REQ_TYPE_NAME'] ?? '—';
                $mn = $r['THE_MONTH'] ?? '—';
                $am = (float)($r['TOTAL_AMOUNT'] ?? $r['REQ_AMOUNT'] ?? 0);
                if (!isset($sum_by_type[$tn]))  $sum_by_type[$tn]  = ['count'=>0,'amount'=>0];
                if (!isset($sum_by_month[$mn])) $sum_by_month[$mn] = ['count'=>0,'amount'=>0];
                $sum_by_type[$tn]['count']++;   $sum_by_type[$tn]['amount']  += $am;
                $sum_by_month[$mn]['count']++;  $sum_by_month[$mn]['amount'] += $am;
            }
            // الموظفين الفريدين عبر استعلام واحد
            $all_req_ids = array_filter(array_map(function($r){ return (int)($r['REQ_ID'] ?? 0); }, $rows));
            if (count($all_req_ids) > 0) {
                $emp_rows = $this->{$this->MODEL_NAME}->req_emps_by_ids(implode(',', $all_req_ids));
                $emp_count_map = [];
                foreach ($emp_rows as $er) {
                    $e = (int)($er['EMP_NO'] ?? 0);
                    if (!$e) continue;
                    if (!isset($emp_count_map[$e])) $emp_count_map[$e] = 0;
                    $emp_count_map[$e]++;
                    $sum_total_rows++;
                }
                $sum_unique = count($emp_count_map);
                foreach ($emp_count_map as $c) { if ($c > 1) $sum_dup_count++; }
            }
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheet_title);
        $sheet->setRightToLeft(true);

        // ==== HEADER BLOCK (3 rows) ====
        if ($is_master) {
            $headers = ['م', 'رقم الطلب', 'عدد الموظفين', 'الشهر', 'نوع الطلب', 'المبلغ', 'الحالة', 'المنشئ', 'تاريخ الإنشاء', 'رقم الدفعة', 'تاريخ الصرف', 'اعتماد الرقابة', 'المعتمد', 'ملاحظات', 'شهر الصرف'];
        } else {
            $headers = ['م', 'رقم الطلب', 'رقم الموظف', 'اسم الموظف', 'المقر', 'الشهر', 'نوع الطلب', 'المبلغ', 'الحالة', 'المنشئ', 'تاريخ الإنشاء', 'رقم الدفعة', 'اعتماد الرقابة', 'المعتمد', 'ملاحظات', 'شهر الصرف'];
        }
        $colsCount = count($headers);
        $lastCol = $colsCount > 26 ? 'A' . chr(64 + $colsCount - 26) : chr(64 + $colsCount);

        // Row 1: العنوان
        $sheet->setCellValue('A1', $main_title);
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1e293b');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Row 2: الفلاتر + الإجمالي
        $sheet->setCellValue('A2', 'الفلاتر: ' . $filter_line);
        $halfMid = $colsCount > 4 ? chr(64 + intval($colsCount / 2)) : 'C';
        $sheet->mergeCells("A2:{$halfMid}2");
        $totalCell = chr(ord($halfMid) + 1);
        $sheet->setCellValue("{$totalCell}2", 'الإجمالي: ' . number_format($t_amount, 2) . '   ·   عدد: ' . number_format($t_count));
        $sheet->mergeCells("{$totalCell}2:{$lastCol}2");
        $sheet->getStyle("A2:{$lastCol}2")->getFont()->setSize(10)->setBold(true);
        $sheet->getStyle("A2:{$lastCol}2")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('f1f5f9');
        $sheet->getStyle("A2:{$lastCol}2")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle("{$totalCell}2")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("{$totalCell}2")->getFont()->getColor()->setRGB('15803d');
        $sheet->getRowDimension(2)->setRowHeight(22);

        $cur = 3;  // مؤشر الصف الحالي (لتسهيل إدراج ملخّص متغيّر الحجم)

        // ==== 🆕 SUMMARY BLOCK (Master view فقط) ====
        if ($is_master && $sum_unique > 0) {
            // عنوان القسم
            $sheet->setCellValue('A' . $cur, '📋 ملخّص الاختيار');
            $sheet->mergeCells("A{$cur}:{$lastCol}{$cur}");
            $sheet->getStyle("A{$cur}")->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle("A{$cur}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('4338ca');
            $sheet->getStyle("A{$cur}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension($cur)->setRowHeight(22);
            $cur++;

            // الموظفين الفريدين
            $dup_note = $sum_dup_count > 0 ? "  ⚠️ {$sum_dup_count} موظف مكرّر في أكثر من طلب" : '';
            $sheet->setCellValue('A' . $cur, "👥 الموظفين الفريدين: {$sum_unique}   ·   عدد الأسطر الكلي: {$sum_total_rows}{$dup_note}");
            $sheet->mergeCells("A{$cur}:{$lastCol}{$cur}");
            $sheet->getStyle("A{$cur}")->getFont()->setSize(10)->setBold(true);
            $sheet->getStyle("A{$cur}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('eef2ff');
            if ($sum_dup_count > 0) $sheet->getStyle("A{$cur}")->getFont()->getColor()->setRGB('92400e');
            $sheet->getStyle("A{$cur}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension($cur)->setRowHeight(20);
            $cur++;

            // توزيع حسب النوع
            if (count($sum_by_type) > 0) {
                $parts = [];
                foreach ($sum_by_type as $tn => $v) {
                    $parts[] = "{$tn} [{$v['count']}] = " . number_format($v['amount'], 2);
                }
                $sheet->setCellValue('A' . $cur, '🟦 توزيع حسب النوع:   ' . implode('   ·   ', $parts));
                $sheet->mergeCells("A{$cur}:{$lastCol}{$cur}");
                $sheet->getStyle("A{$cur}")->getFont()->setSize(10);
                $sheet->getStyle("A{$cur}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('f8fafc');
                $sheet->getStyle("A{$cur}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($cur)->setRowHeight(20);
                $cur++;
            }

            // تنبيه: أشهر مختلطة
            if (count($sum_by_month) > 1) {
                $monParts = [];
                foreach ($sum_by_month as $mn => $v) {
                    $mtxt = (strlen($mn) == 6) ? substr($mn, 4, 2) . '/' . substr($mn, 0, 4) : $mn;
                    $monParts[] = "{$mtxt} ({$v['count']})";
                }
                $sheet->setCellValue('A' . $cur, "⚠️ تنبيه: الطلبات تشمل " . count($sum_by_month) . " شهور مختلفة — " . implode(' · ', $monParts));
                $sheet->mergeCells("A{$cur}:{$lastCol}{$cur}");
                $sheet->getStyle("A{$cur}")->getFont()->setSize(10)->setBold(true)->getColor()->setRGB('991b1b');
                $sheet->getStyle("A{$cur}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('fef2f2');
                $sheet->getStyle("A{$cur}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($cur)->setRowHeight(20);
                $cur++;
            }

            // سطر فاصل فارغ
            $cur++;
        }

        // ==== رؤوس الأعمدة ====
        $headerRow = $cur;
        $col = 'A';
        foreach ($headers as $h) { $sheet->setCellValue($col . $headerRow, $h); $col++; }
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('334155');
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($headerRow)->setRowHeight(22);
        $cur++;

        // ==== DATA ROWS ====
        $dataStart = $cur;
        $rowNum = $cur; $count = 1;
        foreach ($rows as $row) {
            $st  = (int)($row['STATUS'] ?? 0);
            $thm = $row['THE_MONTH'] ?? '';
            if (strlen($thm) == 6) { $thm = substr($thm, 4, 2) . '/' . substr($thm, 0, 4); }

            if ($is_master) {
                $sheet->setCellValue('A' . $rowNum, $count);
                $sheet->setCellValue('B' . $rowNum, $row['REQ_NO'] ?? '');
                $sheet->setCellValue('C' . $rowNum, (int)($row['EMP_COUNT'] ?? 0));
                $sheet->setCellValue('D' . $rowNum, $thm);
                $sheet->setCellValue('E' . $rowNum, $row['REQ_TYPE_NAME'] ?? '');
                $sheet->setCellValue('F' . $rowNum, (float)($row['TOTAL_AMOUNT'] ?? $row['REQ_AMOUNT'] ?? 0));
                $sheet->setCellValue('G' . $rowNum, $row['STATUS_NAME'] ?? ($status_map[$st] ?? ''));
                $sheet->setCellValue('H' . $rowNum, $row['ENTRY_USER_NAME'] ?? '');
                $sheet->setCellValue('I' . $rowNum, $row['ENTRY_DATE'] ?? '');
                $sheet->setCellValue('J' . $rowNum, $row['BATCH_NO'] ?? '');
                $sheet->setCellValue('K' . $rowNum, $row['PAY_DATE'] ?? '');
                $sheet->setCellValue('L' . $rowNum, $raqaba_txt($st, $row['RAQABA_STATUS'] ?? 0, $row['RAQABA_STATUS_NAME'] ?? '')); // 🆕 اعتماد الرقابة
                $sheet->setCellValue('M' . $rowNum, $row['RAQABA_USER_NAME'] ?? ''); // 🆕 المعتمد
                $sheet->setCellValue('N' . $rowNum, $row['NOTE'] ?? ($row['MASTER_NOTE'] ?? '')); // 🆕 ملاحظات الطلب
                $pm = trim((string)($row['PAY_DATE'] ?? '')); $pm = (strlen($pm) >= 10) ? substr($pm, 3) : '';
                $sheet->setCellValue('O' . $rowNum, $pm); // 🆕 شهر الصرف (من تاريخ الصرف)
            } else {
                $sheet->setCellValue('A' . $rowNum, $count);
                $sheet->setCellValue('B' . $rowNum, $row['REQ_NO'] ?? '');
                $sheet->setCellValue('C' . $rowNum, $row['EMP_NO'] ?? '');
                $sheet->setCellValue('D' . $rowNum, $row['EMP_NAME'] ?? '');
                $sheet->setCellValue('E' . $rowNum, $row['BRANCH_NAME'] ?? '');
                $sheet->setCellValue('F' . $rowNum, $thm);
                $sheet->setCellValue('G' . $rowNum, $row['REQ_TYPE_NAME'] ?? '');
                $sheet->setCellValue('H' . $rowNum, (float)($row['REQ_AMOUNT'] ?? 0));
                $sheet->setCellValue('I' . $rowNum, $row['STATUS_NAME'] ?? ($status_map[$st] ?? ''));
                $sheet->setCellValue('J' . $rowNum, $row['ENTRY_USER_NAME'] ?? '');
                $sheet->setCellValue('K' . $rowNum, $row['ENTRY_DATE'] ?? '');
                $sheet->setCellValue('L' . $rowNum, $row['BATCH_NO'] ?? '');
                $sheet->setCellValue('M' . $rowNum, $raqaba_txt($st, $row['RAQABA_STATUS'] ?? 0, $row['RAQABA_STATUS_NAME'] ?? '')); // 🆕 اعتماد الرقابة
                $sheet->setCellValue('N' . $rowNum, $row['RAQABA_USER_NAME'] ?? ''); // 🆕 المعتمد
                $sheet->setCellValue('O' . $rowNum, $row['MASTER_NOTE'] ?? ($row['NOTE'] ?? '')); // 🆕 ملاحظات الطلب
                $pm = trim((string)($row['PAY_DATE'] ?? '')); $pm = (strlen($pm) >= 10) ? substr($pm, 3) : '';
                $sheet->setCellValue('P' . $rowNum, $pm); // 🆕 شهر الصرف (من تاريخ الصرف)
            }

            if ($st == 9) { $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->getFont()->getColor()->setRGB('dc2626'); }
            $count++; $rowNum++;
        }

        // ==== TOTALS ROW ====
        $totRow = $rowNum;
        $amountCol = $is_master ? 'F' : 'H';
        $sheet->setCellValue('A' . $totRow, 'الإجمالي');
        $sheet->mergeCells('A' . $totRow . ':' . chr(ord($amountCol) - 1) . $totRow);
        $sheet->setCellValue($amountCol . $totRow, $t_amount);
        $sheet->getStyle("A{$totRow}:{$lastCol}{$totRow}")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A{$totRow}:{$lastCol}{$totRow}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1e293b');
        $sheet->getStyle('A' . $totRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

        // ==== FORMATTING ====
        $sheet->getStyle("{$amountCol}{$dataStart}:{$amountCol}{$totRow}")->getNumberFormat()->setFormatCode('#,##0.00');
        foreach (range('A', $lastCol) as $c) { $sheet->getColumnDimension($c)->setAutoSize(true); }
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$totRow}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle("A{$dataStart}:{$lastCol}" . ($totRow - 1))->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // freeze header (يحتاج صف بعد آخر صف عناوين)
        $sheet->freezePane('A' . $dataStart);

        $filename = $file_prefix . '_' . ($the_month ?: date('Ym')) . '_' . date('His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ==================== BULK EXPORT EXCEL ====================
    function export_bulk_preview()
    {
        $params = $this->_bulk_params();
        $result = $this->{$this->MODEL_NAME}->bulk_preview($params);

        $rows = $result['rows'] ?? [];
        if (!is_array($rows) || count($rows) === 0) {
            echo '<script>alert("لا توجد بيانات للتصدير"); history.back();</script>'; return;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('معاينة جماعي');
        $sheet->setRightToLeft(true);

        $withAllowances = (int)$params['req_type'] === 1 && $params['include_allowances'] === 1;
        $fromRemaining = (int)$params['type2_remaining_mode'] === 1;
        $headers = ['م', 'رقم الموظف', 'اسم الموظف', 'المقر', 'صافي الراتب', 'المبلغ المحتسب', 'مبلغ الصرف', 'موجود مسبقا'];
        if ($withAllowances) {
            $headers[4] = 'الراتب المحوّل مستحقات (323)';
            $headers[5] = 'الإشرافية والمخاطرة (325)';
        }
        if ($fromRemaining) {
            $headers = ['م', 'رقم الموظف', 'اسم الموظف', 'المقر', 'أصل مستحقات الشهر (323)',
                'المصروف والمحجوز من 323', 'المتبقي من 323', 'المتبقي المتاح من 325', 'النسبة %',
                'حصة 323 من الدفعة', 'حصة 325 من الدفعة', 'مبلغ الصرف', 'متبقي أساس الاحتساب بعد الدفعة', 'سبب الاستبعاد'];
        }
        $lastCol = $fromRemaining ? 'N' : 'H';
        $col = 'A';
        foreach ($headers as $h) { $sheet->setCellValue($col . '1', $h); $col++; }

        $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true);
        $sheet->getStyle('A1:' . $lastCol . '1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('1e293b');
        $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->getColor()->setRGB('FFFFFF');

        $rowNum = 2; $count = 1;
        foreach ($rows as $row) {
            $sheet->setCellValue('A' . $rowNum, $count);
            $sheet->setCellValue('B' . $rowNum, $row['EMP_NO'] ?? '');
            $sheet->setCellValue('C' . $rowNum, $row['EMP_NAME'] ?? '');
            $sheet->setCellValue('D' . $rowNum, $row['BRANCH_NAME'] ?? '');
            if ($fromRemaining) {
                $values = [
                    (float)($row['ORIGINAL_323'] ?? 0), (float)($row['PRIOR_BASE_AMOUNT'] ?? 0),
                    (float)($row['REMAINING_BASE'] ?? 0), (float)($row['AVAILABLE_325'] ?? 0),
                    (float)$params['percent_val'], (float)($row['BASE_PAYMENT'] ?? 0),
                    (float)($row['ALLOWANCE_325_AMOUNT'] ?? 0), (float)($row['CALC_AMOUNT'] ?? 0),
                    (float)($row['REMAINING_AFTER'] ?? 0), $row['SKIP_REASON'] ?? '',
                ];
                foreach ($values as $index => $value) { $sheet->setCellValue(chr(ord('E') + $index) . $rowNum, $value); }
                $count++; $rowNum++;
                continue;
            }
            $sheet->setCellValue('E' . $rowNum, (float)($row[$withAllowances ? 'CON_323_AMOUNT' : 'NET_SALARY'] ?? 0));
            $sheet->setCellValue('F' . $rowNum, (float)($row[$withAllowances ? 'ALLOWANCE_325_AMOUNT' : 'CALC_AMOUNT'] ?? 0));
            $sheet->setCellValue('G' . $rowNum, (float)($row['CALC_AMOUNT'] ?? 0));
            $sheet->setCellValue('H' . $rowNum, ((int)($row['HAS_EXISTING'] ?? 0)) > 0 ? 'نعم' : 'لا');
            $count++; $rowNum++;
        }

        if ($rowNum > 2) {
            $sheet->getStyle('E2:' . ($fromRemaining ? 'M' : 'G') . ($rowNum - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        }
        foreach (range('A', $lastCol) as $c) { $sheet->getColumnDimension($c)->setAutoSize(true); }
        $sheet->getStyle('A1:' . $lastCol . ($rowNum - 1))->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $filename = 'معاينة_جماعي_' . ($params['the_month'] ?? date('Ym')) . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ==================== IMPORT ====================
    private function _import_validate_params()
    {
        $the_month   = trim((string)$this->p_the_month);
        $req_type    = trim((string)$this->p_req_type);
        if ($the_month === '' || strlen($the_month) != 6 || !ctype_digit($the_month)) return 'يجب ادخال الشهر بصيغة YYYYMM';
        if (!$req_type)    return 'يجب اختيار نوع الطلب';
        if ((int)$req_type === 2 && !(int)$this->p_req_id
            && (!is_numeric($this->p_percent_val) || (float)$this->p_percent_val <= 0 || (float)$this->p_percent_val > 100)) {
            return 'لإنشاء دفعة راتب من Excel، افتح إنشاء طلب وحدد النسبة وطريقة الاحتساب ثم استورد الموظفين';
        }
        return null;
    }

    private function _import_upload_file()
    {
        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] != 0) return [null, 'يجب اختيار ملف Excel'];
        $ext = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx','xls','csv'], true)) return [null, 'نوع الملف غير مسموح'];
        $config['upload_path'] = FCPATH . 'uploads/tmp/';
        $config['allowed_types'] = '*'; $config['max_size'] = 10240; $config['encrypt_name'] = true;
        if (!is_dir($config['upload_path'])) @mkdir($config['upload_path'], 0777, true);
        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('excel_file')) return [null, $this->upload->display_errors('', '')];
        return [$this->upload->data('full_path'), null];
    }

    private function _import_parse_file($fullPath, $note, $req_type = null, $the_month = null)
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        } catch (\Exception $e) { return [null, null, 'فشل قراءة الملف: ' . $e->getMessage()]; }
        if (count($rows) < 2) return [null, null, 'الملف فارغ'];

        $isCalculatedSalary = in_array((int)$req_type, [1, 2], true);
        // 🆕 نقرأ الصفوف الخام أولاً، ثم نُجمّعها حسب EMP_NO (دمج التكرار)
        $rawLines = []; $errors = []; $rowNum = 0;

        foreach ($rows as $r) {
            $rowNum++;
            if ($rowNum == 1) continue;   // تخطي الـ header
            $empNo = isset($r['A']) ? trim((string)$r['A']) : '';
            if ($isCalculatedSalary) {
                $rowNote = (int)$req_type === 1 ? trim((string)($r['B'] ?? '')) : trim((string)($r['C'] ?? ''));
                // Type 2 is calculated by Oracle; an Excel amount never overrides it.
                if ($empNo === '') continue;
            } else {
                $amount  = isset($r['B']) ? trim((string)$r['B']) : '';
                $rowNote = isset($r['C']) ? trim((string)$r['C']) : '';
                if ($empNo === '' && $amount === '') continue;
            }
            if ($empNo === '' || !ctype_digit($empNo)) { $errors[] = "صف {$rowNum}: رقم الموظف غير صحيح"; continue; }
            if ($isCalculatedSalary) {
                $rawLines[] = ['row' => $rowNum, 'EMP_NO' => (int)$empNo, 'AMOUNT' => 0, 'NOTE' => $rowNote];
            } else {
                if ((int)$req_type === 4) {
                    try { $amountNum = $this->_type4_requested_amount($amount); }
                    catch (\InvalidArgumentException $e) { $errors[] = "صف {$rowNum}: " . $e->getMessage(); continue; }
                } else {
                    $amountNum = floatval(str_replace(',', '', $amount));
                    if ($amountNum <= 0) { $errors[] = "صف {$rowNum}: المبلغ يجب أن يكون أكبر من صفر"; continue; }
                }
                $rawLines[] = ['row' => $rowNum, 'EMP_NO' => (int)$empNo, 'AMOUNT' => $amountNum, 'NOTE' => $rowNote];
            }
        }

        // 🆕 تجميع الصفوف لكل موظف — للـ DB سطر واحد بالإجمالي
        // الأصل (كل سطر منفصل) محفوظ:
        //   1. في الإكسل المرفوع كـ attachment على الطلب
        //   2. في PAYMENT_REQ_IMP_LINE_TB (relational — قابل للاستعلام)
        $byEmp = [];   // EMP_NO ⮕ ['lines' => [...full raw...], 'total' => float]
        foreach ($rawLines as $rl) {
            $eno = $rl['EMP_NO'];
            if (!isset($byEmp[$eno])) {
                $byEmp[$eno] = ['lines' => [], 'total' => 0.0];
            }
            $byEmp[$eno]['lines'][] = $rl;       // 🆕 نحفظ السطر الخام بكامله
            $byEmp[$eno]['total']  += (float)$rl['AMOUNT'];
        }

        // 🆕 بناء $items النهائية — صف واحد لكل موظف + breadcrumb للدمج
        $items = [];
        $merged_emps_count = 0;   // عدد الموظفين الذين تم دمجهم (>1 سطر)
        $merged_rows_count = 0;   // إجمالي الصفوف اللي تم دمجها
        foreach ($byEmp as $eno => $info) {
            $cnt        = count($info['lines']);
            $excelRows  = array_column($info['lines'], 'row');
            $rowNotes   = array_filter(array_column($info['lines'], 'NOTE'), function ($n) { return $n !== ''; });
            if ((int)$req_type === 4 && $info['total'] > 0
                && in_array(0, array_column($info['lines'], 'AMOUNT'))) {
                $errors[] = 'الموظف ' . $eno . ': لا تجمع كامل المتبقي ومبلغاً محدداً في نفس الملف';
                continue;
            }

            if ($cnt > 1) {
                $merged_emps_count++;
                $merged_rows_count += $cnt;
                $rowsList = implode('، ', array_slice($excelRows, 0, 7));
                if ($cnt > 7) $rowsList .= '...';
                $finalNote = '[دمج ' . $cnt . ' صفوف Excel: ' . $rowsList . ']';
                if (!empty($rowNotes)) {
                    $uniqueNotes = array_unique($rowNotes);
                    $finalNote .= ' ' . implode(' | ', array_slice($uniqueNotes, 0, 3));
                }
            } else {
                $finalNote = !empty($rowNotes) ? reset($rowNotes) : $note;
            }

            $items[] = [
                'row'        => $excelRows[0],
                'EMP_NO'     => $eno,
                'REQ_AMOUNT' => $isCalculatedSalary ? 0 : round($info['total'], 2),
                'NOTE'       => $finalNote,
                'merged'     => $cnt > 1 ? 1 : 0,    // 🆕 flag للـ UI
                'merge_cnt'  => $cnt,                 // 🆕 عدد الصفوف الأصلية
                'merge_rows' => $excelRows,           // 🆕 أرقام الصفوف الأصلية
                'raw_lines'  => $info['lines'],       // 🆕 السطور الخام كاملة (للحفظ في sub-table)
            ];
        }

        return [
            $items,
            $errors,
            null,
            // 🆕 ملخص الدمج (الـ caller يستخدمه في preview JSON)
            [
                'merged_emps' => $merged_emps_count,
                'merged_rows' => $merged_rows_count,
                'total_raw'   => count($rawLines),
                'total_emps'  => count($items),
            ]
        ];
    }

    function import_excel()
    {
        // 🆕 ضمان رد JSON نظيف — نلتقط أي warning/notice/echo + DBConn print_r
        @ini_set('display_errors', '0');
        // نبدأ buffer جديد دائماً (CI عنده buffer خاص — لا نعتمد على ob_get_level)
        ob_start();
        header('Content-Type: application/json; charset=utf-8');
        @set_time_limit(600);  // 10 دقايق لـ ملفات كبيرة

        $_finish = function($data) {
            // ننظف كل الـ buffers المفتوحة (CI + buffer تبعنا)
            while (ob_get_level() > 0) {
                $stray = ob_get_clean();
                if ($stray && strlen(trim($stray)) > 0) {
                    log_message('error', '[import_excel] stray output: ' . substr($stray, 0, 2000));
                }
            }
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        };

        try {
            if (!HaveAccess(base_url('payment_req/payment_req/import_excel'))) {
                $_finish(['ok' => false, 'msg' => 'ليس لديك صلاحية']);
            }
            if ($_SERVER['REQUEST_METHOD'] != 'POST') {
                $_finish(['ok' => false, 'msg' => 'Invalid request']);
            }
            $req_id = $this->p_req_id ? (int)$this->p_req_id : 0;
            if ($req_id <= 0) {
                $_finish(['ok' => false, 'msg' => 'أنشئ طلب الصرف وحدد طريقة الاحتساب أولاً، ثم استورد الملف من داخل الطلب.']);
            }
            $err = $this->_import_validate_params();
            if ($err) { $_finish(['ok' => false, 'msg' => $err]); }

            $the_month   = trim((string)$this->p_the_month);
            $req_type    = trim((string)$this->p_req_type);
            $note        = $this->p_note ?: 'استيراد Excel';

            $file_token = $this->input->post('file_token');
            $fullPath   = null;
            if ($file_token) {
                $tmpPath = FCPATH . 'uploads/tmp/' . basename($file_token);
                if (!file_exists($tmpPath)) { $_finish(['ok' => false, 'msg' => 'انتهت صلاحية الملف']); }
                $fullPath = $tmpPath;
            } else {
                list($fullPath, $uploadErr) = $this->_import_upload_file();
                if ($uploadErr) { $_finish(['ok' => false, 'msg' => $uploadErr]); }
            }

            // 🆕 نقرأ اسم الملف الأصلي قبل ما نستخدم الـ tmp path
            $originalName = $file_token
                ? ('imported_' . date('Ymd_His') . '.xlsx')   // لو جاي عبر file_token، الاسم الأصلي ضايع
                : (isset($_FILES['file']['name']) ? $_FILES['file']['name'] : 'imported.xlsx');

            $parseRes = $this->_import_parse_file($fullPath, $note, $req_type, $the_month);
            list($items, $parseErrors, $parseErr) = [$parseRes[0], $parseRes[1], $parseRes[2]];
            $mergeInfo = isset($parseRes[3]) ? $parseRes[3] : null;

            if ($parseErr) { @unlink($fullPath); $_finish(['ok' => false, 'msg' => $parseErr]); }
            if (empty($items)) { @unlink($fullPath); $_finish(['ok' => false, 'msg' => 'لا يوجد بيانات صالحة', 'parse_errors' => $parseErrors]); }

            // 🆕 2. حفظ ملف الإكسل الأصلي أولاً — نحتاج ATTACHMENT_ID لربط البنود به
            $attachSaved   = false;
            $attachment_id = 0;
            try {
                list($attachSaved, $attachment_id) = $this->_save_import_attachment($fullPath, $originalName, $req_id, $mergeInfo);
            } catch (\Exception $e) {
                log_message('error', 'Import attachment save failed: ' . $e->getMessage());
            }

            // 3. Add details + import lines
            $inserted = 0; $insertErrors = []; $linesInserted = 0;
            $entryUser = isset($this->user) ? (int)$this->user->emp_no : null;
            if ((int)$req_type === 4) {
                $items = $this->_type4_import_preview($items, $req_id, $the_month);
            }

            // 🆕 تتبّع: لو الـ procedure ما اتحدثت بعد، نعرض رسالة واضحة
            $dup_block_count = 0;

            foreach ($items as $it) {
                if (!empty($it['PREVIEW_ERROR'])) {
                    $insertErrors[] = ['row' => $it['row'], 'EMP_NO' => $it['EMP_NO'], 'msg' => $it['PREVIEW_ERROR']];
                    continue;
                }
                try {
                    // 🆕 allow_dup=true → نسمح بالموظفين المكررين في طلبات أخرى
                    // (المحاسب شافهم في المعاينة)
                    $res = $this->{$this->MODEL_NAME}->detail_add($req_id, $it['EMP_NO'], $it['REQ_AMOUNT'], $it['NOTE'], true);
                } catch (\Exception $e) {
                    log_message('error', 'detail_add threw for emp ' . $it['EMP_NO'] . ': ' . $e->getMessage());
                    $insertErrors[] = ['row' => $it['row'], 'EMP_NO' => $it['EMP_NO'], 'msg' => mb_substr($e->getMessage(), 0, 200)];
                    continue;
                }
                // 🆕 إذا الـ proc لسا قديمة وترفض الموظف بسبب التكرار، نتتبّعها
                if (is_string($res) && mb_strpos($res, 'موجود في طلب آخر') !== false) {
                    $dup_block_count++;
                }
                if (is_numeric($res) && (int)$res > 0) {
                    $inserted++;
                    $detail_id = (int)$res;

                    // 🆕 حفظ البنود الأصلية في الجدول الفرعي — للمراجعة لاحقاً
                    $rawLines = $it['raw_lines'] ?? [];
                    $line_no  = 0;
                    foreach ($rawLines as $rl) {
                        $line_no++;
                        try {
                            $r = $this->{$this->MODEL_NAME}->import_line_add(
                                $detail_id,
                                $line_no,
                                (int)($rl['row'] ?? 0),
                                (float)($rl['AMOUNT'] ?? 0),
                                (string)($rl['NOTE'] ?? ''),
                                $attachment_id ?: null,
                                $entryUser
                            );
                            if (!empty($r['new_id'])) { $linesInserted++; }
                        } catch (\Exception $e) {
                            log_message('error', 'Import line insert failed for emp ' . $it['EMP_NO'] . ': ' . $e->getMessage());
                        }
                    }
                } else {
                    $insertErrors[] = ['row' => $it['row'], 'EMP_NO' => $it['EMP_NO'], 'msg' => mb_substr((string)$res, 0, 200)];
                }
            }

            @unlink($fullPath);

            // 🆕 لو الـ proc القديمة رفضت موظفين بسبب التكرار، نخبر المستخدم بوضوح
            $proc_warning = null;
            if ($dup_block_count > 0) {
                $proc_warning = 'تنبيه فني: الـ procedure على Oracle لم يتم تحديثها بعد. '
                              . "تم رفض {$dup_block_count} موظف بسبب 'موجود في طلب آخر'. "
                              . 'يجب تشغيل ملف database/payment_req/03_pkg_body.sql على Oracle ليقبل التكرار.';
                log_message('error', '[import_excel] OLD PROC IN USE: ' . $proc_warning);
            }

            $_finish([
                'ok' => true, 'req_id' => $req_id, 'inserted' => $inserted,
                'total_in_file' => count($items), 'parse_errors' => $parseErrors, 'insert_errors' => $insertErrors,
                'merge_info'      => $mergeInfo,
                'attach_saved'    => $attachSaved,
                'attachment_id'   => $attachment_id,
                'lines_inserted'  => $linesInserted,
                'proc_warning'    => $proc_warning,
            ]);

        } catch (\Throwable $e) {
            // 🆕 يلتقط أي fatal/Exception ويرجعه كـ JSON بدل HTML error page
            log_message('error', '[import_excel] fatal: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $_finish(['ok' => false, 'msg' => 'خطأ في السيرفر: ' . $e->getMessage()]);
        }
    }

    /**
     * 🆕 حفظ ملف الإكسل المستورد كـ attachment على الطلب.
     * الفئة (CATEGORY) = 'payment_req_import' و IDENTITY = REQ_ID
     * يرجّع: [bool $saved, int $attachment_id]
     *
     * يستخدم attachment_model الموجود (الـ procedure GFC_ATTACHMENT_TB_INSERT).
     */
    private function _save_import_attachment($fullPath, $originalName, $req_id, $mergeInfo)
    {
        if (!file_exists($fullPath)) return [false, 0];

        $localDir = FCPATH . 'uploads/payment_req_imports/';
        if (!is_dir($localDir)) { @mkdir($localDir, 0755, true); }

        $ext       = pathinfo($originalName, PATHINFO_EXTENSION) ?: 'xlsx';
        $stored    = 'req_' . (int)$req_id . '_' . date('Ymd_His') . '_' . substr(md5(uniqid('', true)), 0, 8) . '.' . $ext;
        $destPath  = $localDir . $stored;
        if (!@copy($fullPath, $destPath)) {
            log_message('error', 'Import attach: copy failed to ' . $destPath);
            return [false, 0];
        }

        $note = 'ملف إكسل مستورد';
        if (is_array($mergeInfo) && (int)($mergeInfo['merged_emps'] ?? 0) > 0) {
            $note .= ' (دمج ' . (int)$mergeInfo['merged_emps'] . ' موظف من ' . (int)$mergeInfo['total_raw'] . ' صف)';
        }

        try {
            $this->load->model('attachments/attachment_model');
            // 🔧 الصيغة الصحيحة — _extract_data يتوقع array of arrays مع keys: name, value, type, length
            $data = [
                ['name' => 'IDENTITY',   'value' => (int)$req_id,                                        'type' => '', 'length' => -1],
                ['name' => 'CATEGORY',   'value' => 'payment_req_import',                                'type' => '', 'length' => -1],
                ['name' => 'FILE_NAME',  'value' => $originalName,                                       'type' => '', 'length' => -1],
                ['name' => 'FILE_PATH',  'value' => $stored,                                             'type' => '', 'length' => -1],
                ['name' => 'NOTE',       'value' => $note,                                               'type' => '', 'length' => -1],
                ['name' => 'ENTRY_USER', 'value' => isset($this->user) ? (int)$this->user->emp_no : 0,   'type' => '', 'length' => -1],
            ];
            $this->attachment_model->create($data);
            // ATTACHMENT_ID اختياري — البنود تنحفظ بدونه برضو (NULL)
            return [true, 0];
        } catch (\Exception $e) {
            log_message('error', 'Import attach: model->create failed: ' . $e->getMessage());
            return [false, 0];
        }
    }

    /**
     * 🆕 حفظ السطور الخام من الإكسل في PAYMENT_REQ_IMP_LINE_TB
     * يُستدعى من الـ wizard بعد ما تنتهي إضافة الموظفين عبر detail_add.
     *
     * POST:
     *   - req_id (NUMBER)
     *   - lines  (JSON array of {row, emp_no, amount, note})
     *   - attachment_id (NUMBER, optional)
     */
    function public_save_import_lines_for_req()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo json_encode(['ok' => false, 'msg' => 'POST only']); return;
        }
        $req_id        = (int)$this->input->post('req_id');
        $lines_json    = (string)$this->input->post('lines');
        $attachment_id = (int)$this->input->post('attachment_id') ?: null;

        if ($req_id <= 0) {
            echo json_encode(['ok' => false, 'msg' => 'req_id مطلوب']); return;
        }

        $lines = json_decode($lines_json, true);
        if (!is_array($lines) || empty($lines)) {
            echo json_encode(['ok' => false, 'msg' => 'lines فارغ أو غير صالح']); return;
        }

        // ترتيب البنود لكل موظف (LINE_NO تتزايد لكل موظف)
        $line_no_per_emp = [];
        $entry_user      = isset($this->user) ? (int)$this->user->emp_no : null;
        $inserted        = 0;
        $skipped         = 0;
        $errors          = [];

        foreach ($lines as $ln) {
            $emp_no = (int)($ln['emp_no'] ?? 0);
            $amount = (float)($ln['amount'] ?? 0);
            $row    = (int)($ln['row']    ?? 0);
            $note   = (string)($ln['note'] ?? '');

            if (!$emp_no || $amount <= 0) { $skipped++; continue; }

            $line_no_per_emp[$emp_no] = ($line_no_per_emp[$emp_no] ?? 0) + 1;

            try {
                // 🆕 الـ procedure تحلّ DETAIL_ID داخلياً من (REQ_ID + EMP_NO)
                $r = $this->{$this->MODEL_NAME}->import_line_add_by_emp(
                    $req_id,
                    $emp_no,
                    $line_no_per_emp[$emp_no],
                    $row,
                    $amount,
                    $note,
                    $attachment_id,
                    $entry_user
                );
                if (!empty($r['new_id'])) {
                    $inserted++;
                } else {
                    $skipped++;
                    if (!empty($r['msg'])) $errors[] = 'EMP=' . $emp_no . ': ' . $r['msg'];
                }
            } catch (\Exception $e) {
                $skipped++;
                $errors[] = 'EMP=' . $emp_no . ': ' . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            log_message('error', 'save_import_lines errors: ' . implode(' | ', array_slice($errors, 0, 5)));
        }

        echo json_encode([
            'ok'       => $inserted > 0,
            'inserted' => $inserted,
            'skipped'  => $skipped,
            'total'    => count($lines),
            'msg'      => 'تم حفظ ' . $inserted . ' بند' . ($skipped > 0 ? ' (تم تجاوز ' . $skipped . ')' : ''),
        ]);
    }

    /**
     * 🆕 رفع ملف Excel وربطه بطلب موجود (يُستدعى من wizBulkCreate بعد إنشاء الطلب)
     * POST: req_id + excel_file
     */
    function public_save_import_file_for_req()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo json_encode(['ok' => false, 'msg' => 'POST only']); return;
        }
        $req_id = (int)$this->input->post('req_id');
        if ($req_id <= 0) {
            echo json_encode(['ok' => false, 'msg' => 'req_id مطلوب']); return;
        }
        if (empty($_FILES['excel_file']['name'])) {
            echo json_encode(['ok' => false, 'msg' => 'لا يوجد ملف']); return;
        }

        // رفع الملف لـ uploads/tmp/ أولاً
        list($fullPath, $uploadErr) = $this->_import_upload_file();
        if ($uploadErr) {
            echo json_encode(['ok' => false, 'msg' => $uploadErr]); return;
        }

        $originalName = $_FILES['excel_file']['name'] ?: 'import.xlsx';

        // حفظ كـ attachment على الطلب
        $attachSaved = false; $attachment_id = 0;
        try {
            list($attachSaved, $attachment_id) = $this->_save_import_attachment($fullPath, $originalName, $req_id, null);
        } catch (\Exception $e) {
            log_message('error', 'Wizard import attach failed: ' . $e->getMessage());
        }
        @unlink($fullPath);

        echo json_encode([
            'ok' => $attachSaved,
            'attachment_id' => $attachment_id,
            'msg' => $attachSaved ? 'تم حفظ الملف' : 'فشل حفظ الملف'
        ]);
    }

    /**
     * 🆕 جلب البنود الأصلية لتفصيلة موظف (للـ modal في صفحة الطلب)
     * GET param: detail_id
     * يضيف FILE_NAME/FILE_PATH من attachment_model (لأن procedure ما يقدر يعمل JOIN عبر role)
     */
    function public_get_import_lines_json()
    {
        $detail_id = (int)$this->input->get_post('detail_id');
        if (!$detail_id) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'detail_id مطلوب']);
            return;
        }
        try {
            $rows = $this->{$this->MODEL_NAME}->import_lines_get($detail_id);
            if (!is_array($rows)) $rows = [];

            // 🆕 إثراء البنود ببيانات الملف من attachment_model
            $att_cache = [];   // ATTACHMENT_ID → ['FILE_NAME', 'FILE_PATH']
            if (!empty($rows)) {
                $this->load->model('attachments/attachment_model');
                $att_ids = array_unique(array_filter(array_map(function ($r) {
                    return (int)($r['ATTACHMENT_ID'] ?? 0);
                }, $rows)));
                foreach ($att_ids as $aid) {
                    if (!$aid) continue;
                    try {
                        $att = $this->attachment_model->get($aid);
                        if (is_array($att) && isset($att[0])) {
                            $att_cache[$aid] = [
                                'FILE_NAME' => $att[0]['FILE_NAME'] ?? '',
                                'FILE_PATH' => $att[0]['FILE_PATH'] ?? '',
                            ];
                        } elseif (is_array($att) && isset($att['FILE_NAME'])) {
                            $att_cache[$aid] = [
                                'FILE_NAME' => $att['FILE_NAME'] ?? '',
                                'FILE_PATH' => $att['FILE_PATH'] ?? '',
                            ];
                        }
                    } catch (\Exception $e) {
                        // تجاهل — خلّ FILE_NAME فارغ
                    }
                }
                foreach ($rows as &$r) {
                    $aid = (int)($r['ATTACHMENT_ID'] ?? 0);
                    $r['FILE_NAME'] = $att_cache[$aid]['FILE_NAME'] ?? '';
                    $r['FILE_PATH'] = $att_cache[$aid]['FILE_PATH'] ?? '';
                }
                unset($r);
            }

            // تنظيف encoding
            array_walk_recursive($rows, function (&$val) {
                if (is_string($val)) { $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8'); }
            });
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'data' => $rows, 'count' => count($rows)]);
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 🆕 تنزيل ملف الإكسل المستورد لطلب معين (المسؤول/منشئ الطلب فقط)
     */
    function public_download_import_file($req_id = 0, $file_path = null)
    {
        $req_id = (int)$req_id;
        if (!$req_id || !$file_path) { show_error('Missing parameters', 400); return; }

        $file_path = basename($file_path);   // أمان: نحصر على basename
        $fullPath  = FCPATH . 'uploads/payment_req_imports/' . $file_path;
        if (!file_exists($fullPath)) { show_404(); return; }

        // نتأكد إن الملف فعلاً مرتبط بالطلب
        $this->load->model('attachments/attachment_model');
        $atts = $this->attachment_model->get_list($req_id, 'payment_req_import', 0);
        $found = false; $originalName = $file_path;
        if (is_array($atts)) {
            foreach ($atts as $a) {
                if (($a['FILE_PATH'] ?? '') === $file_path) {
                    $found = true;
                    $originalName = $a['FILE_NAME'] ?? $file_path;
                    break;
                }
            }
        }
        if (!$found) { show_error('Not authorized', 403); return; }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $originalName . '"');
        header('Content-Length: ' . filesize($fullPath));
        header('Cache-Control: max-age=0');
        readfile($fullPath);
        exit;
    }

    function import_preview()
    {
        // 🆕 ضمان رد JSON نظيف — نلتقط أي warning/echo + DBConn print_r
        @ini_set('display_errors', '0');
        ob_start();
        header('Content-Type: application/json; charset=utf-8');
        @set_time_limit(300);

        $_finish = function($data) {
            while (ob_get_level() > 0) {
                $stray = ob_get_clean();
                if ($stray && strlen(trim($stray)) > 0) {
                    log_message('error', '[import_preview] stray output: ' . substr($stray, 0, 2000));
                }
            }
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        };

        try {
            if (!HaveAccess(base_url('payment_req/payment_req/import_excel'))) {
                $_finish(['ok' => false, 'msg' => 'ليس لديك صلاحية']);
            }
            if ($_SERVER['REQUEST_METHOD'] != 'POST') {
                $_finish(['ok' => false, 'msg' => 'Invalid request']);
            }
            $err = $this->_import_validate_params();
            if ($err) { $_finish(['ok' => false, 'msg' => $err]); }

            list($fullPath, $uploadErr) = $this->_import_upload_file();
            if ($uploadErr) { $_finish(['ok' => false, 'msg' => $uploadErr]); }

            $the_month = trim((string)$this->p_the_month);
            $req_type  = trim((string)$this->p_req_type);
            $note      = $this->p_note ?: 'استيراد Excel';
            $parseRes = $this->_import_parse_file($fullPath, $note, $req_type, $the_month);
            list($items, $parseErrors, $parseErr) = [$parseRes[0], $parseRes[1], $parseRes[2]];
            $mergeInfo = isset($parseRes[3]) ? $parseRes[3] : null;

            if ($parseErr) { @unlink($fullPath); $_finish(['ok' => false, 'msg' => $parseErr]); }
            if (empty($items)) { @unlink($fullPath); $_finish(['ok' => false, 'msg' => 'لا يوجد بيانات صالحة', 'parse_errors' => $parseErrors]); }

            // 🆕 كشف الموظفين المكررين في طلبات أخرى لنفس الشهر+النوع
            // المحاسب يشوفهم في المعاينة، والاستيراد يقبلهم (allow_dup=true)
            // ⚠️ نوع 5 (استحقاقات وإضافات) يسمح بتكرار الموظف عبر الطلبات/البنود لنفس الشهر —
            //    نفس الكارف-أوت الموجود في DETAIL_INSERT و DIAGNOSE_EXCEL_EMPS (V_TYPE <> C_REQ_TYPE_BENEFITS).
            //    بدون هذا الشرط، IMPORT_FIND_DUP_EMPS يطلّع تحذير "في طلب آخر" خاطئ لنوع 5.
            $cur_req_id = $this->p_req_id ? (int)$this->p_req_id : null;
            $previewErrorCount = 0;
            if ((int)$req_type === 4) {
                $items = $this->_type4_import_preview($items, $cur_req_id, $the_month);
                foreach ($items as $it) if (!empty($it['PREVIEW_ERROR'])) $previewErrorCount++;
            }
            $dup_map = [];   // EMP_NO ⮕ ['REQ_NO', 'REQ_NO']
            try {
                $emp_nos_list = array_map(function($it){ return (int)$it['EMP_NO']; }, $items);
                $emp_nos_csv  = implode(',', array_filter($emp_nos_list));
                if ($emp_nos_csv && $the_month && $req_type && !in_array((int)$req_type, [4, 5], true)) {
                    $dup_rows = $this->{$this->MODEL_NAME}->import_find_dup_emps($emp_nos_csv, $the_month, $req_type, $cur_req_id, $this->_type2_remaining_mode());
                    foreach ($dup_rows as $dr) {
                        $e = (int)($dr['EMP_NO'] ?? 0);
                        $rn = (string)($dr['REQ_NO'] ?? '');
                        if (!$e) continue;
                        if (!isset($dup_map[$e])) $dup_map[$e] = [];
                        if ($rn !== '' && !in_array($rn, $dup_map[$e])) $dup_map[$e][] = $rn;
                    }
                }
            } catch (Exception $e) {
                log_message('error', '[import_preview] dup check failed: ' . $e->getMessage());
            }

            // إضافة flag على كل item يحتوي على معلومات التكرار
            $dup_count = 0;
            foreach ($items as &$it) {
                $eno = (int)$it['EMP_NO'];
                if (isset($dup_map[$eno]) && count($dup_map[$eno]) > 0) {
                    $it['has_other_req'] = 1;
                    $it['other_req_nos'] = $dup_map[$eno];
                    $dup_count++;
                } else {
                    $it['has_other_req'] = 0;
                }
            }
            unset($it);

            $file_token = basename($fullPath);
            $_finish([
                'ok' => true, 'file_token' => $file_token,
                'valid_count' => count($items) - $previewErrorCount, 'error_count' => count($parseErrors) + $previewErrorCount,
                'total_rows' => count($items) + count($parseErrors),
                'items' => $items, 'parse_errors' => $parseErrors,
                'merge_info' => $mergeInfo,
                // 🆕 ملخص الموظفين المكررين (سيظهر بانر في Step 2)
                'dup_count' => $dup_count,
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[import_preview] fatal: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $_finish(['ok' => false, 'msg' => 'خطأ في السيرفر: ' . $e->getMessage()]);
        }
    }

    public function download_template()
    {
        $isPartialSalary = (int)$this->input->get('req_type') === 2;
        $isMonthlyDues = (int)$this->input->get('req_type') === 4;
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('استيراد طلبات صرف');
        $sheet->setCellValue('A1', 'EMP_NO');
        $sheet->setCellValue('A2', 12345);
        if (!$isPartialSalary) {
            $sheet->setCellValue('B1', 'REQ_AMOUNT'); $sheet->setCellValue('C1', 'NOTE');
            $sheet->setCellValue('B2', $isMonthlyDues ? '' : 500.00);
            $sheet->setCellValue('C2', $isMonthlyDues ? 'كامل المتبقي' : 'دفعة');
        }
        $lastCol = $isPartialSalary ? 'A' : 'C';
        $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true);
        foreach (range('A', $lastCol) as $c) $sheet->getColumnDimension($c)->setAutoSize(true);
        $filename = 'قالب_استيراد_' . date('Y-m-d') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ==================== HELPERS ====================
    private function _type4_import_preview($items, $reqId, $theMonth)
    {
        $byEmp = []; $existing = [];
        if ($reqId) {
            foreach ((array)$this->{$this->MODEL_NAME}->get_details($reqId) as $detail) {
                $existing[(int)$detail['EMP_NO']] = true;
            }
        }
        if (!$reqId) {
            $params = $this->_bulk_params();
            $params['the_month'] = $theMonth;
            $params['req_type'] = 4;
            $params['req_amount'] = null;
            $amounts = [];
            foreach ($items as $it) $amounts[(int)$it['EMP_NO']] = $it['REQ_AMOUNT'];
            $result = $this->{$this->MODEL_NAME}->bulk_preview($params);
            if (!in_array((string)($result['msg'] ?? ''), ['', '1'], true)) {
                throw new \InvalidArgumentException((string)$result['msg']);
            }
            $rows = $this->_type4_preview_rows($result['rows'] ?? [], $params,
                json_encode($amounts), implode(',', array_keys($amounts)));
            foreach ($rows as $row) $byEmp[(int)$row['EMP_NO']] = $row;
        }
        foreach ($items as &$it) {
            if (isset($existing[(int)$it['EMP_NO']])) {
                $row = ['SKIP_REASON' => 'الموظف موجود مسبقاً في هذا الطلب'];
            } elseif ($reqId) {
                $parts = explode('|', (string)$this->{$this->MODEL_NAME}->detail_preview_single(
                    $reqId, $it['EMP_NO'], $it['REQ_AMOUNT']));
                if (($parts[0] ?? '') === 'ERR') {
                    $row = ['SKIP_REASON' => $parts[1] ?? 'تعذر احتساب مبلغ الصرف'];
                } elseif (count($parts) < 14 || !is_numeric($parts[0])) {
                    throw new \InvalidArgumentException('يلزم تحديث باكج الصرف لعرض مستحقات الشهر');
                } else {
                    $row = ['CALC_AMOUNT' => (float)$parts[0], 'ALLOWANCE_325_AMOUNT' => (float)$parts[5],
                        'REMAINING_BASE' => (float)$parts[8], 'AVAILABLE_325' => (float)$parts[9],
                        'BASE_PAYMENT' => (float)$parts[10], 'REMAINING_AFTER' => (float)$parts[11], 'SKIP_REASON' => ''];
                }
            } else {
                $row = $byEmp[(int)$it['EMP_NO']] ?? ['SKIP_REASON' => 'لا توجد بيانات للموظف'];
            }
            $it['CALC_AMOUNT'] = (float)($row['CALC_AMOUNT'] ?? 0);
            $it['ALLOWANCE_325_AMOUNT'] = (float)($row['ALLOWANCE_325_AMOUNT'] ?? 0);
            $it['BASE_PAYMENT'] = (float)($row['BASE_PAYMENT'] ?? 0);
            $it['REMAINING_AFTER'] = (float)($row['REMAINING_AFTER'] ?? 0);
            $it['PREVIEW_ERROR'] = (string)($row['SKIP_REASON'] ?? '');
        }
        unset($it);
        return $items;
    }

    private function _include_allowances()
    {
        return (in_array((int)$this->p_req_type, [1, 4], true) || $this->_type2_remaining_mode() === 1)
            && (string)($this->p_include_allowances ?? '0') === '1' ? 1 : 0;
    }

    // Type 4: zero/blank means the full selected monthly balance. Invalid input must
    // never silently become that sentinel (especially a negative Excel amount).
    private function _type4_requested_amount($value)
    {
        return $this->_requested_amount($value);
    }

    private function _requested_amount($value)
    {
        if ($value === null || (is_string($value) && trim($value) === '')) return 0.0;
        if (is_string($value) && !preg_match('/^(?:(?:[0-9]+|[0-9]{1,3}(?:,[0-9]{3})+)(?:\.[0-9]*)?|\.[0-9]+)$/D', trim($value))) {
            throw new \InvalidArgumentException('مبلغ الطلب غير صالح');
        }
        $number = is_string($value) ? str_replace(',', '', trim($value)) : $value;
        if (!is_numeric($number) || !is_finite((float)$number) || (float)$number < 0
            || (float)$number > PHP_INT_MAX / 100) {
            throw new \InvalidArgumentException('مبلغ الطلب غير صالح');
        }
        $rounded = round((float)$number, 2);
        if ((float)$number > 0 && $rounded <= 0) {
            throw new \InvalidArgumentException('المبلغ يجب ألا يقل عن 0.01');
        }
        return $rounded;
    }

    private function _type4_preview_rows($rows, $params, $amountsJson = null, $empNos = null)
    {
        $amounts = [];
        if ($amountsJson !== null && $amountsJson !== '') {
            $decoded = json_decode((string)$amountsJson, true);
            if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('مبالغ الموظفين غير صالحة');
            }
            foreach ($decoded as $emp => $amount) {
                if (!ctype_digit((string)$emp) || (int)$emp <= 0) {
                    throw new \InvalidArgumentException('رقم الموظف غير صحيح');
                }
                $amounts[(int)$emp] = $this->_type4_requested_amount($amount);
            }
        }
        $selected = null;
        if ($empNos !== null && trim((string)$empNos) !== '') {
            $selected = [];
            foreach (explode(',', (string)$empNos) as $emp) {
                $emp = trim($emp);
                if (!ctype_digit($emp) || (int)$emp <= 0) {
                    throw new \InvalidArgumentException('رقم الموظف غير صحيح');
                }
                $selected[(int)$emp] = true;
            }
        }
        $default = $this->_type4_requested_amount($params['req_amount'] ?? null);
        $out = []; $found = [];
        foreach ($rows as $row) {
            $emp = (int)($row['EMP_NO'] ?? 0);
            if ($selected !== null && !isset($selected[$emp])) continue;
            foreach (['REMAINING_BASE', 'AVAILABLE_325', 'SALARY_AVAILABLE', 'ELIGIBILITY_REASON'] as $field) {
                if (!array_key_exists($field, $row)) {
                    throw new \InvalidArgumentException('يلزم تحديث باكج الصرف لعرض مستحقات الشهر');
                }
            }
            // Budgets arrive from Oracle truncated to cents; use integer cents
            // for the same proportional allocation used by PREPARE_REQUEST_VALUES.
            $base = max((int)round((float)$row['REMAINING_BASE'] * 100), 0);
            $allowance = max((int)round((float)$row['AVAILABLE_325'] * 100), 0);
            $budget = $base + $allowance;
            $requested = array_key_exists($emp, $amounts) ? $amounts[$emp] : $default;
            $cash = $requested > 0 ? min((int)round($requested * 100), $budget) : $budget;
            $allowanceCash = $budget > 0 ? min($allowance,
                max($cash - $base, (int)round($cash * $allowance / $budget))) : 0;
            $row['PERCENT_BASE'] = $budget / 100;
            $row['BASE_PAYMENT'] = ($cash - $allowanceCash) / 100;
            $row['ALLOWANCE_325_AMOUNT'] = $allowanceCash / 100;
            $row['CALC_AMOUNT'] = $cash / 100;
            $row['REMAINING_AFTER'] = ($budget - $cash) / 100;
            $row['REQUESTED_AMOUNT'] = $requested;
            $row['SKIP_REASON'] = (string)$row['ELIGIBILITY_REASON'];
            if ($row['SKIP_REASON'] === '' && $cash <= 0) {
                $row['SKIP_REASON'] = 'لا يوجد متبقي متاح لهذا الشهر';
            } elseif ($row['SKIP_REASON'] === '' && $cash / 100 > (float)$row['SALARY_AVAILABLE']) {
                $row['SKIP_REASON'] = 'رصيد المستحقات المتاح لا يكفي لمبلغ الصرف';
            }
            $found[$emp] = true;
            $out[] = $row;
        }
        if ($selected !== null) {
            foreach ($selected as $emp => $_) {
                if (isset($found[$emp])) continue;
                $out[] = ['EMP_NO' => $emp, 'EMP_NAME' => '', 'BRANCH_NAME' => '',
                    'CALC_AMOUNT' => 0, 'HAS_EXISTING' => 0,
                    'SKIP_REASON' => 'لا توجد بيانات راتب للموظف ضمن الشهر والفلاتر المحددة'];
            }
        }
        return $out;
    }

    private function _type2_remaining_mode()
    {
        return (int)$this->p_req_type === 2 && (string)($this->p_type2_remaining_mode ?? '0') === '1' ? 1 : 0;
    }

    private function _bulk_params()
    {
        return array(
            'the_month'    => $this->p_the_month,
            'req_type'     => $this->p_req_type,
            'include_allowances' => $this->_include_allowances(),
            'type2_remaining_mode' => $this->_type2_remaining_mode(),
            'calc_method'  => $this->p_calc_method ?: null,
            'percent_val'  => $this->p_percent_val ?: null,
            'req_amount'   => $this->p_req_amount  ?: null,
            'pay_type'     => $this->p_pay_type    ?: null,
            'sal_from'     => $this->p_sal_from    ?: null,
            'sal_to'       => $this->p_sal_to      ?: null,
            'branch_no'    => $this->p_branch_no   ?: null,
            'l_value'      => $this->_type2_remaining_mode() ? 0 : ($this->p_l_value === '' ? null : $this->p_l_value),
            'h_value'      => $this->_type2_remaining_mode() ? 999999999 : ($this->p_h_value === '' ? null : $this->p_h_value),
            'entry_date'   => $this->p_entry_date  ?: null,
            // 🆕 شهر فلتر اختياري — يستخدم للنوع 3 (دفعة من المستحقات) لجلب الموظفين فقط
            'filter_month' => $this->p_filter_month ?: null,
        );
    }

    function check_vars($var, $c_var)
    {
        $p_var = $this->{'p_'.$c_var};
        $var = ($p_var) ? $p_var : $var;
        $var = $var == -1 ? null : $var;
        return $var;
    }

    // ==================== بنود الاستحقاقات (نوع 5) ====================
    function get_benefit_items_json()
    {
        $rows = $this->{$this->MODEL_NAME}->get_benefit_items();
        header('Content-Type: application/json');
        echo json_encode(is_array($rows) ? $rows : []);
    }

    function _post_validation($isEdit = false)
    {
        if ($isEdit && !$this->p_req_id) $this->print_error('REQ_ID مطلوب للتعديل');
        if (!$this->p_req_type)           $this->print_error('يجب اختيار نوع الطلب');

        $rt = intval($this->p_req_type);

        // الشهر مطلوب لكل الأنواع ما عدا النوع 3 (دفعة من المستحقات — لا معنى مالي للشهر)
        if ($rt != 3 && !$this->p_the_month) $this->print_error('الشهر مطلوب');

        // نوع 1: راتب كامل
        if ($rt == 1) {
            if (!$this->p_pay_type) $this->print_error('بند المستحقات مطلوب');
        }
        // نوع 2: دفعة من الراتب — نسبة فقط
        if ($rt == 2) {
            if (!$this->p_pay_type)    $this->print_error('بند المستحقات مطلوب');
            if (!is_numeric($this->p_percent_val) || (float)$this->p_percent_val <= 0 || (float)$this->p_percent_val > 100) {
                $this->print_error('النسبة يجب أن تكون أكبر من صفر وحتى 100');
            }
            if (!$this->_type2_remaining_mode()) {
                if ($this->p_l_value === null || $this->p_l_value === '') $this->print_error('الحد الأدنى مطلوب');
                if ($this->p_h_value === null || $this->p_h_value === '') $this->print_error('الحد الأعلى مطلوب');
            }
        }
        // نوع 3: دفعة من المستحقات
        if ($rt == 3) {
            if (!$this->p_pay_type)    $this->print_error('بند المستحقات مطلوب');
            if (!$this->p_calc_method) $this->print_error('طريقة الاحتساب مطلوبة');
            if (intval($this->p_calc_method) == 1 && !$this->p_percent_val) $this->print_error('النسبة مطلوبة');
            // المبلغ الموحد إجباري فقط في same mode (مبلغ ثابت)؛ في diff اليوزر بيدخل لكل موظف على حدة
            $am3 = $this->p_amount_mode ?: 'same';
            if (intval($this->p_calc_method) == 2 && $am3 === 'same' && !$this->p_req_amount) {
                $this->print_error('المبلغ مطلوب');
            }
        }
        // نوع 4: مستحقات حسب الشهر
        if ($rt == 4) {
            if (!$this->p_pay_type) $this->print_error('بند المستحقات مطلوب');
            // المبلغ الموحد اختياري في same (لو فاضي = احتساب تلقائي للمتبقي)
            // diff: اليوزر بيدخل لكل موظف على حدة
        }
        // نوع 5: استحقاقات وإضافات
        if ($rt == 5) {
            if (!$this->p_pay_type) $this->print_error('بند الاستحقاق مطلوب');
        }
    }

    function _postedData($typ = null)
    {
        $the_month_val = ($this->p_the_month != '' ? $this->p_the_month : date('Ym'));
        $filter_month  = ($this->p_filter_month ?: null);
        if ($typ == 'create') {
            // INSERT: P_THE_MONTH, P_REQ_TYPE, P_CALC_METHOD, P_PERCENT_VAL, P_PAY_TYPE, P_NOTE, P_MSG_OUT, P_FILTER_MONTH
            return array(
                array('name' => 'THE_MONTH',    'value' => $the_month_val,               'type' => '', 'length' => -1),
                array('name' => 'REQ_TYPE',     'value' => $this->p_req_type,            'type' => '', 'length' => -1),
                array('name' => 'CALC_METHOD',  'value' => $this->p_calc_method ?: null, 'type' => '', 'length' => -1),
                array('name' => 'PERCENT_VAL',  'value' => $this->p_percent_val ?: null, 'type' => '', 'length' => -1),
                array('name' => 'L_VALUE',      'value' => ($this->p_l_value === '' ? null : $this->p_l_value),     'type' => '', 'length' => -1),
                array('name' => 'H_VALUE',      'value' => ($this->p_h_value === '' ? null : $this->p_h_value),     'type' => '', 'length' => -1),
                array('name' => 'PAY_TYPE',     'value' => $this->p_pay_type ?: null,    'type' => '', 'length' => -1),
                array('name' => 'ENTRY_DATE',   'value' => $this->p_entry_date ?: null,  'type' => '', 'length' => -1),
                array('name' => 'NOTE',         'value' => $this->p_note,                'type' => '', 'length' => -1),
                array('name' => 'FILTER_MONTH', 'value' => $filter_month,                'type' => '', 'length' => -1),
            );
        }
        // UPDATE: P_REQ_ID, P_THE_MONTH, P_REQ_TYPE, P_CALC_METHOD, P_PERCENT_VAL, P_PAY_TYPE, P_NOTE, P_MSG_OUT, P_FILTER_MONTH
        return array(
            array('name' => 'REQ_ID',       'value' => $this->p_req_id,              'type' => '', 'length' => -1),
            array('name' => 'THE_MONTH',    'value' => $the_month_val,               'type' => '', 'length' => -1),
            array('name' => 'REQ_TYPE',     'value' => $this->p_req_type,            'type' => '', 'length' => -1),
            array('name' => 'CALC_METHOD',  'value' => $this->p_calc_method ?: null, 'type' => '', 'length' => -1),
            array('name' => 'PERCENT_VAL',  'value' => $this->p_percent_val ?: null, 'type' => '', 'length' => -1),
            array('name' => 'L_VALUE',      'value' => ($this->p_l_value === '' ? null : $this->p_l_value),     'type' => '', 'length' => -1),
            array('name' => 'H_VALUE',      'value' => ($this->p_h_value === '' ? null : $this->p_h_value),     'type' => '', 'length' => -1),
            array('name' => 'PAY_TYPE',     'value' => $this->p_pay_type ?: null,    'type' => '', 'length' => -1),
            array('name' => 'NOTE',         'value' => $this->p_note,                'type' => '', 'length' => -1),
            array('name' => 'FILTER_MONTH', 'value' => $filter_month,                'type' => '', 'length' => -1),
        );
    }

    function _lookup(&$data, $level = 'full')
    {
        add_css('combotree.css');
        $data['branches'] = $this->gcc_branches_model->get_all();

        if ($level === 'minimal') return;  // batch, history — بس فروع

        $data['pay_type_tree_url'] = base_url('payroll_data/salary_dues_types/public_get_tree_json');
        $this->load->model('settings/constant_details_model');
        $data['req_type_cons']    = $this->constant_details_model->get_list(538);
        $data['calc_method_cons'] = $this->constant_details_model->get_list(540);
        $data['status_cons']      = $this->constant_details_model->get_list(541);

        // تحميل قائمة الموظفين (للفلتر + الإضافة) — الفعّالون من جدول الموظفين مباشرة
        // (المصدر السابق شجرة الهيكلية HR_EMPS_STRUCTURE_TB كان يُسقط أي موظف غير مضاف للشجرة)
        $this->load->model('root/rmodel');
        $data['emp_no_cons'] = $this->rmodel->getAll('SALARYFORM', 'GET_ALL_ACTIVE_EMPLOYEES');
    }
}
