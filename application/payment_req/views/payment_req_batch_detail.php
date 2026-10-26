<?php
$MODULE_NAME = 'payment_req';
$TB_NAME     = 'payment_req';
$history_url = base_url("$MODULE_NAME/$TB_NAME/batch_history");
$export_url  = base_url("$MODULE_NAME/$TB_NAME/export_bank_file");
$export_wallet_acc_url = base_url("$MODULE_NAME/$TB_NAME/export_wallet_accountant");
$print_url   = base_url("$MODULE_NAME/$TB_NAME/batch_print");
$compare_url = base_url("$MODULE_NAME/$TB_NAME/batch_compare_print");
$full_xlsx_url = base_url("$MODULE_NAME/$TB_NAME/batch_full_export");
$req_att_url   = base_url("$MODULE_NAME/$TB_NAME/batch_req_attachments_json");
$att_upload_url = base_url("archive/archive/upload_file");
$att_delete_url = base_url("archive/archive/delete_file");
$att_download_base = base_url("archive/download/");

$bi = $batch_info;
$st = (int)($bi['STATUS'] ?? 0);
$batch_id = $bi['BATCH_ID'] ?? 0;
$batch_no = $bi['BATCH_NO'] ?? '';
$rows = isset($detail_rows) && is_array($detail_rows) ? $detail_rows : [];
$trend_rows = isset($batch_trend) && is_array($batch_trend) ? $batch_trend : [];
$emp_status_by_month = isset($emp_status_by_month) && is_array($emp_status_by_month) ? $emp_status_by_month : [];
$batch_months_list   = isset($batch_months) && is_array($batch_months) ? $batch_months : [];
$req_att_count_map   = isset($req_attachments_count) && is_array($req_attachments_count) ? $req_attachments_count : [];

// 🆕 خريطة req_no → req_id للوصل بين الـ badges والـ modal
$reqs_no_to_id = [];
if (isset($batch_reqs) && is_array($batch_reqs)) {
    foreach ($batch_reqs as $_rq) {
        $_no = $_rq['REQ_NO'] ?? '';
        $_id = (int)($_rq['REQ_ID'] ?? 0);
        if ($_no && $_id) $reqs_no_to_id[$_no] = $_id;
    }
}

// 🆕 endpoints للمقارنة
$trend_url       = base_url("$MODULE_NAME/$TB_NAME/batch_trend_json");
$diff_url        = base_url("$MODULE_NAME/$TB_NAME/batch_diff_json");
$diff_export_url = base_url("$MODULE_NAME/$TB_NAME/batch_diff_export");

$status_labels = [];
foreach ($batch_status_cons as $s) { $status_labels[(int)$s['CON_NO']] = $s['CON_NAME']; }

$can_pay     = HaveAccess(base_url("$MODULE_NAME/$TB_NAME/batch_pay_action"));
$can_cancel  = HaveAccess(base_url("$MODULE_NAME/$TB_NAME/batch_cancel_action"));
$can_reverse = HaveAccess(base_url("$MODULE_NAME/$TB_NAME/batch_reverse_pay_action"));

echo AntiForgeryToken();
// 🆕 تحميل CSS الخاص بصفحة batch_detail (موحَّد في ملف واحد)
?>
<link rel="stylesheet" href="<?= base_url() ?>assets-n/css/modules/payment_req/batch_detail.css" type="text/css" />

<div class="page-header">
    <div><h1 class="page-title"><?= $title ?></h1></div>
    <div class="ms-auto pageheader-btn">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url("$MODULE_NAME/$TB_NAME") ?>">صرف الرواتب</a></li>
            <li class="breadcrumb-item"><a href="<?= $history_url ?>">سجل الدفعات</a></li>
            <li class="breadcrumb-item active"><?= $batch_no ?></li>
        </ol>
    </div>
</div>

<?php $this->load->view('payment_req_batch_payment_feedback'); ?>
<div class="row"><div class="col-lg-12"><div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fa fa-bank me-2"></i><?= $title ?></h3>
        <div class="ms-auto bd-toolbar">
            <?php if ($st == 0 && $can_pay): ?>
                <!-- الإجراءات الأساسية للدفعة المحتسبة -->
                <div class="bd-btn-group bd-group-primary">
                    <button class="bd-btn bd-btn-pay" onclick="payBatch()" title="تنفيذ صرف الدفعة">
                        <i class="fa fa-money"></i><span>تنفيذ الصرف</span>
                    </button>
                    <?php $can_refresh = HaveAccess(base_url("$MODULE_NAME/$TB_NAME/batch_refresh_split_action")); if ($can_refresh): ?>
                        <button class="bd-btn bd-btn-refresh" onclick="refreshSplit()"
                                title="تحديث بيانات الحسابات والبنوك من الإعدادات الحالية — لا يحدث تلقائياً">
                            <i class="fa fa-refresh"></i><span>تحديث البيانات</span>
                        </button>
                    <?php endif; ?>
                    <button class="bd-btn bd-btn-cancel" onclick="cancelBatch()" title="فك احتساب الدفعة">
                        <i class="fa fa-undo"></i><span>فك الاحتساب</span>
                    </button>
                </div>
            <?php elseif ($st == 2 && $can_reverse): ?>
                <div class="bd-btn-group bd-group-primary">
                    <button class="bd-btn bd-btn-reverse" onclick="reverseBatch()" title="عكس صرف الدفعة">
                        <i class="fa fa-reply-all"></i><span>عكس الصرف</span>
                    </button>
                </div>
            <?php elseif ($st == 9): ?>
                <span class="bd-tag-cancelled">
                    <i class="fa fa-ban"></i> دفعة ملغاة — للعرض فقط
                </span>
            <?php endif; ?>

            <!-- إجراءات مساعدة -->
            <div class="bd-btn-group bd-group-utility">
                <a class="bd-btn bd-btn-fullxlsx" href="<?= $full_xlsx_url ?>/<?= $batch_id ?>" title="تصدير شامل: موظفون + مستفيدون + بنوك">
                    <i class="fa fa-file-excel-o"></i><span>تصدير شامل</span>
                </a>
                <a class="bd-btn bd-btn-export" href="<?= $export_url ?>?batch_id=<?= $batch_id ?>" title="تصدير كشف لكل البنوك">
                    <i class="fa fa-bank"></i><span>تصدير كل البنوك</span>
                </a>
                <?php /* مخفي مؤقتاً — سيُعاد لاحقاً
                <a class="bd-btn" style="background:#10b981;color:#fff"
                   href="<?= base_url("$MODULE_NAME/$TB_NAME/batch_emps_summary_excel/" . $batch_id) ?>"
                   title="ملف Excel: صف لكل موظف بإجماليه وتوزيعه على البنك/المحفظة/الوريث">
                    <i class="fa fa-users"></i><span>Excel ملخص الموظفين</span>
                </a>
                */ ?>
                <!-- 🆕 (B) كشوف A4 جماعية: صفحة طباعة لكل موظف -->
                <a class="bd-btn" style="background:#8b5cf6;color:#fff"
                   href="<?= base_url("$MODULE_NAME/$TB_NAME/batch_emps_summary_print_all/" . $batch_id) ?>"
                   target="_blank"
                   title="طباعة كشف A4 لكل موظف — صفحة لكل واحد جاهزة للتوقيع">
                    <i class="fa fa-print"></i><span>طباعة كشوف الموظفين</span>
                </a>
                <button class="bd-btn bd-btn-print" onclick="printReport()" title="طباعة الدفعة">
                    <i class="fa fa-print"></i><span>طباعة</span>
                </button>
                <button class="bd-btn" style="background:#0ea5e9;color:#fff" onclick="printCompareReport()" title="تقرير المقارنات: KPIs + توزيع الوجهة + المستثنون + مقارنة بالسابقة">
                    <i class="fa fa-bar-chart"></i><span>تقرير المقارنات</span>
                </button>
                <a class="bd-btn bd-btn-back" href="<?= $history_url ?>" title="العودة لسجل الدفعات">
                    <i class="fa fa-arrow-right"></i><span>رجوع</span>
                </a>
            </div>
        </div>
    </div>

    <div class="card-body">

        <!-- معلومات الدفعة -->
        <div class="pr-row">
            <div class="pr-card c-total">
                <div class="c-label"><i class="fa fa-hashtag"></i> رقم الدفعة</div>
                <div class="c-val"><?= $batch_no ?></div>
            </div>
            <div class="pr-card">
                <div class="c-label"><i class="fa fa-calendar"></i> تاريخ الإنشاء</div>
                <div class="c-val" style="font-size:.85rem"><?= $bi['ENTRY_DATE'] ?? '' ?></div>
                <div class="c-cnt"><?= $bi['ENTRY_USER_NAME'] ?? '' ?></div>
            </div>
            <div class="pr-card">
                <div class="c-label"><i class="fa fa-users"></i> الموظفين</div>
                <div class="c-val"><span id="topEmpCount"><?= $bi['EMP_COUNT'] ?? 0 ?></span><span id="topEmpCountFiltered" style="display:none;color:#9a3412;font-size:.7em;font-weight:700"></span></div>
                <div class="c-cnt"><?= $bi['MOVEMENT_COUNT'] ?? 0 ?> حركة</div>
            </div>
            <div class="pr-card" style="background:#f0fdf4;border-color:#86efac">
                <div class="c-label"><i class="fa fa-money"></i> إجمالي الصرف</div>
                <div class="c-val" style="color:#059669"><span id="topGrandTotal" data-orig="<?= (float)($bi['TOTAL_AMOUNT'] ?? 0) ?>"><?= n_format((float)($bi['TOTAL_AMOUNT'] ?? 0)) ?></span><div id="topGrandTotalSub" style="font-size:.65rem;color:#9a3412;font-weight:700;display:none;margin-top:.1rem"></div></div>
            </div>
            <div class="pr-card">
                <div class="c-label"><i class="fa fa-info-circle"></i> الحالة</div>
                <div class="c-val"><span class="bh-status s<?= $st ?>"><?= $status_labels[$st] ?? '' ?></span></div>
                <?php if ($st == 2 && !empty($bi['PAY_DATE'])): ?>
                    <div class="c-cnt"><?= $bi['PAY_DATE'] ?> — <?= $bi['PAY_USER_NAME'] ?? '' ?></div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (count($rows) > 0):
            // ═══════════════════════════════════════════════════════════
            // مرحلة 1: بناء emp_attrs من $rows (BATCH_HISTORY_GET) — للـ badges فقط
            // ═══════════════════════════════════════════════════════════
            $emp_attrs = [];
            $total_amt = 0;
            $sum_t1 = 0; $sum_t2 = 0; $sum_t3 = 0; $sum_t4 = 0;
            $all_req_nos = [];
            $by_branch = [];

            // 🆕 emp_display_status من $recipients (PAYMENT_BATCH_BANK_VW يعرّضه عبر GET_EMP_DISPLAY_STATUS)
            // 🔧 إصلاح بق الـ 938: العدّ يكون من $rows (الموظفون الحقيقيون = 810)
            //    وليس من $recipients (اللي بحتوي مستفيدين/زوجات/أبناء = 1021 سجل بـ EMP_NOs مختلفة).
            //    نستخدم $recipients فقط لبناء الـ map (emp_no → display_status).
            $emp_disp_map = [];   // emp_no => display_status (1/0/2/4)
            $rcp_for_status = isset($recipients) && is_array($recipients) ? $recipients : [];
            foreach ($rcp_for_status as $_rR) {
                $_eno = (int)($_rR['EMP_NO'] ?? 0);
                if (!$_eno || isset($emp_disp_map[$_eno])) continue;
                $emp_disp_map[$_eno] = (int)($_rR['EMP_DISPLAY_STATUS'] ?? 1);
            }
            // 🔧 العدّ من $rows فقط (الموظفون الفعليون في الدفعة)
            $emp_st_counts = [1=>0, 0=>0, 2=>0, 4=>0];
            foreach ($rows as $_eR) {
                $_eno = (int)($_eR['EMP_NO'] ?? 0);
                if (!$_eno) continue;
                $st = $emp_disp_map[$_eno] ?? 1;
                if (isset($emp_st_counts[$st])) $emp_st_counts[$st]++;
            }

            foreach ($rows as $r) {
                $eno = (int)($r['EMP_NO'] ?? 0);
                if (!$eno) continue;
                $emp_attrs[$eno] = [
                    'emp_name'        => $r['EMP_NAME'] ?? '',
                    'branch_name'     => $r['BRANCH_NAME'] ?? '',
                    'benef_count'     => (int)($r['BENEF_COUNT'] ?? 0),
                    'active_acc_count'=> (int)($r['ACTIVE_ACC_COUNT'] ?? 0),
                    'split_count'     => (int)($r['SPLIT_COUNT'] ?? 0),
                    'inactive_acc_count' => (int)($r['INACTIVE_ACC_COUNT'] ?? 0),
                    'req_nos'         => $r['REQ_NOS'] ?? '',
                    'total_amount'    => (float)($r['TOTAL_AMOUNT'] ?? 0),
                    'amt_t1'          => (float)($r['AMT_TYPE1'] ?? 0),
                    'amt_t2'          => (float)($r['AMT_TYPE2'] ?? 0),
                    'amt_t3'          => (float)($r['AMT_TYPE3'] ?? 0),
                    'amt_t4'          => (float)($r['AMT_TYPE4'] ?? 0),
                    'display_status'  => $emp_disp_map[$eno] ?? 1,
                ];
                $total_amt += (float)($r['TOTAL_AMOUNT'] ?? 0);
                $sum_t1 += (float)($r['AMT_TYPE1'] ?? 0);
                $sum_t2 += (float)($r['AMT_TYPE2'] ?? 0);
                $sum_t3 += (float)($r['AMT_TYPE3'] ?? 0);
                $sum_t4 += (float)($r['AMT_TYPE4'] ?? 0);
                if (!empty($r['REQ_NOS'])) {
                    foreach (explode(', ', $r['REQ_NOS']) as $rn) { $all_req_nos[trim($rn)] = 1; }
                }
                $emp_br = $r['BRANCH_NAME'] ?? 'غير محدد';
                if (!isset($by_branch[$emp_br])) $by_branch[$emp_br] = [
                    'count' => 0, 'total' => 0,
                    't1' => 0, 't2' => 0, 't3' => 0, 't4' => 0,
                ];
                $by_branch[$emp_br]['count']++;
                $by_branch[$emp_br]['total'] += (float)($r['TOTAL_AMOUNT'] ?? 0);
                $by_branch[$emp_br]['t1']    += (float)($r['AMT_TYPE1']   ?? 0);
                $by_branch[$emp_br]['t2']    += (float)($r['AMT_TYPE2']   ?? 0);
                $by_branch[$emp_br]['t3']    += (float)($r['AMT_TYPE3']   ?? 0);
                $by_branch[$emp_br]['t4']    += (float)($r['AMT_TYPE4']   ?? 0);
            }
            uasort($by_branch, function($a, $b) { return $b['total'] <=> $a['total']; });

            // ═══════════════════════════════════════════════════════════
            // مرحلة 2: بناء $banks من $recipients (PAYMENT_BATCH_BANK_VW)
            // = نفس مصدر الجدول العلوي → الأرقام مطابقة 100%
            // التجميع: bank → branch → emp (= 1 row لكل موظف داخل البنك)
            // ═══════════════════════════════════════════════════════════
            $banks = [];
            $rcp_src = isset($recipients) && is_array($recipients) ? $recipients : [];
            foreach ($rcp_src as $rR) {
                $eno  = (int)($rR['EMP_NO'] ?? 0);
                $amt  = (float)($rR['TOTAL_AMOUNT'] ?? 0);
                if (!$eno || $amt <= 0) continue;
                $bk   = $rR['MASTER_BANK_NAME'] ?? 'غير محدد';      // اسم البنك المستلِم (snapshot)
                $mbno = (int)($rR['MASTER_BANK_NO'] ?? 0);
                $is_wallet = (int)($rR['PROVIDER_TYPE'] ?? 1) === 2;
                // 🆕 المحافظ ما إلها فروع → نخلي كل موظفي المحفظة في قائمة واحدة
                //    (BANK_BRANCH_NAME للمحافظ بيرجع للبنك القديم في HR — تصنيف غير مفيد)
                if ($is_wallet) {
                    $br = '';   // مفتاح موحّد لكل المحافظ
                } else {
                    $br = $rR['BANK_BRANCH_NAME'] ?? 'غير محدد';
                }

                if (!isset($banks[$bk])) $banks[$bk] = [
                    'branches' => [], 'total' => 0, 'count' => 0, 'master_bank_no' => $mbno,
                    'is_wallet' => $is_wallet ? 1 : 0,
                    'with_benef' => 0, 'with_multi_acc' => 0, 'with_no_split' => 0,
                    'with_inactive_acc' => 0, 'with_split' => 0,
                    'emps_seen' => [],
                ];
                if (!isset($banks[$bk]['branches'][$br])) {
                    $banks[$bk]['branches'][$br] = ['emps' => [], 'total' => 0];
                }

                // تجميع 1 row لكل موظف داخل (بنك+فرع)
                if (!isset($banks[$bk]['branches'][$br]['emps'][$eno])) {
                    $atr = $emp_attrs[$eno] ?? [];
                    $banks[$bk]['branches'][$br]['emps'][$eno] = [
                        'emp_no'             => $eno,
                        'emp_name'           => $rR['EMP_NAME'] ?? ($atr['emp_name'] ?? ''),
                        'branch_name'        => $atr['branch_name'] ?? '',
                        'req_nos'            => $atr['req_nos'] ?? '',
                        'benef_count'        => (int)($atr['benef_count'] ?? 0),
                        'active_acc_count'   => (int)($atr['active_acc_count'] ?? 0),
                        'inactive_acc_count' => (int)($atr['inactive_acc_count'] ?? 0),
                        'split_count'        => (int)($atr['split_count'] ?? 0),
                        'amount'             => 0,            // مجموع كل المستلمين لهذا الموظف داخل هذا البنك
                        'recipient_count'    => 0,            // كم مستلم/حساب داخل البنك (للموظف)
                        'is_wallet'          => $is_wallet ? 1 : 0,
                        'display_status'     => (int)($emp_disp_map[$eno] ?? 1),
                        // 🆕 emp-level totals (نفس القيم لكل صف للموظف عبر البنوك)
                        'emp_total'          => (float)($atr['total_amount'] ?? 0),
                        'amt_t1'             => (float)($atr['amt_t1'] ?? 0),
                        'amt_t2'             => (float)($atr['amt_t2'] ?? 0),
                        'amt_t3'             => (float)($atr['amt_t3'] ?? 0),
                        'amt_t4'             => (float)($atr['amt_t4'] ?? 0),
                    ];
                }
                $banks[$bk]['branches'][$br]['emps'][$eno]['amount']          += $amt;
                $banks[$bk]['branches'][$br]['emps'][$eno]['recipient_count']++;
                $banks[$bk]['branches'][$br]['total'] += $amt;
                $banks[$bk]['total'] += $amt;

                // عدّ الموظفين الفريدين per bank (= نفس COUNT(DISTINCT EMP_NO) في الجدول العلوي)
                if (!isset($banks[$bk]['emps_seen'][$eno])) {
                    $banks[$bk]['emps_seen'][$eno] = true;
                    $banks[$bk]['count']++;
                    $atr = $emp_attrs[$eno] ?? [];
                    if (($atr['benef_count'] ?? 0) > 0)        $banks[$bk]['with_benef']++;
                    if (($atr['active_acc_count'] ?? 0) > 1)   $banks[$bk]['with_multi_acc']++;
                    if (($atr['benef_count'] ?? 0) > 0 && ($atr['split_count'] ?? 0) == 0) $banks[$bk]['with_no_split']++;
                    if (($atr['inactive_acc_count'] ?? 0) > 0) $banks[$bk]['with_inactive_acc']++;
                    if (($atr['split_count'] ?? 0) > 1)        $banks[$bk]['with_split']++;
                }
            }
            uasort($banks, function($a, $b) { return $b['total'] <=> $a['total']; });

            // الشهور والأنواع من الطلبات
            $all_months = []; $all_types = [];
            $reqs_list = isset($batch_reqs) ? $batch_reqs : [];
            foreach ($reqs_list as $rq) {
                $m = $rq['THE_MONTH'] ?? '';
                if ($m) $all_months[$m] = 1;
                $rt = $rq['REQ_TYPE_NAME'] ?? '';
                if ($rt) $all_types[$rt] = 1;
            }
            ksort($all_months);
        ?>

        <!-- ملخص الطلبات والشهور -->
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:.75rem 1rem;margin-bottom:.75rem;font-size:.82rem">
            <div class="row">
                <div class="col-md-4">
                    <div class="text-muted mb-1"><i class="fa fa-calendar me-1"></i> <strong>الشهور:</strong></div>
                    <?php foreach ($all_months as $m => $v): $mf = strlen($m)==6 ? substr($m,4,2).'/'.substr($m,0,4) : $m; ?>
                        <span class="badge bg-primary me-1"><?= $mf ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="col-md-4">
                    <div class="text-muted mb-1">
                        <i class="fa fa-file-text-o me-1"></i> <strong>الطلبات:</strong> (<?= count($all_req_nos) ?>)
                        <small class="text-muted ms-1" style="font-size:.7rem">— اضغط على أي طلب لعرض/إضافة المرفقات <i class="fa fa-paperclip"></i></small>
                    </div>
                    <?php foreach ($all_req_nos as $rn => $v):
                        $rid_for = (int)($reqs_no_to_id[$rn] ?? 0);
                        $cnt_for = (int)($req_att_count_map[$rid_for] ?? 0);
                    ?>
                        <?php if ($rid_for > 0): ?>
                        <button type="button" class="pr-att-btn" onclick="openBatchAttachments(<?= $rid_for ?>, '<?= htmlspecialchars($rn, ENT_QUOTES) ?>')"
                                data-rid="<?= $rid_for ?>" title="عرض / إضافة مرفقات للطلب <?= htmlspecialchars($rn) ?>">
                            <span class="pr-att-no"><?= $rn ?></span>
                            <i class="fa fa-paperclip pr-att-icon"></i>
                            <span class="pr-att-cnt<?= $cnt_for > 0 ? ' has-att' : '' ?>" id="prAttCnt_<?= $rid_for ?>"><?= $cnt_for ?></span>
                        </button>
                        <?php else: ?>
                        <span class="badge bg-light text-dark me-1" style="border:1px solid #e2e8f0"><?= $rn ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <div class="col-md-4">
                    <div class="text-muted mb-1"><i class="fa fa-tags me-1"></i> <strong>الأنواع:</strong></div>
                    <?php foreach ($all_types as $rt => $v): ?>
                        <span class="badge bg-info text-white me-1"><?= $rt ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php
        // ────────────────────────────────────────────────
        // 🆕 بطاقة Trend: مقارنة الدفعة الحالية مع الدفعات السابقة
        // ────────────────────────────────────────────────
        $cur_trend  = null;
        $prev_trend = [];
        foreach ($trend_rows as $tr) {
            if ((int)($tr['IS_CURRENT'] ?? 0) === 1) $cur_trend = $tr;
            else $prev_trend[] = $tr;
        }
        $has_prev = count($prev_trend) > 0;
        $first_prev = $has_prev ? $prev_trend[0] : null;

        // 🆕 الدفعة المقارَنة المختارة من اليوزر (?prev=) تتجاوز الافتراضية
        $cmp_opts = isset($compare_options) && is_array($compare_options) ? $compare_options : [];
        $cmp_pid  = isset($cmp_prev_id) ? (int)$cmp_prev_id : 0;
        if ($cmp_pid > 0) {
            foreach ($cmp_opts as $o) {
                if ((int)$o['BATCH_ID'] === $cmp_pid) {
                    $first_prev = [
                        'BATCH_ID'       => $o['BATCH_ID'],
                        'BATCH_NO'       => $o['BATCH_NO'],
                        'ENTRY_DATE_STR' => $o['ENTRY_DATE'],
                        'TOTAL_AMOUNT'   => $o['TOTAL_AMOUNT'],
                        'EMP_COUNT'      => $o['EMP_COUNT'],
                        'MOVEMENT_COUNT' => $o['MOVEMENT_COUNT'],
                    ];
                    $has_prev = true;
                    break;
                }
            }
        }
        $cmp_cur_pid = $first_prev ? (int)($first_prev['BATCH_ID'] ?? 0) : 0;
        ?>
        <?php if ($cur_trend): ?>
        <div class="trend-card mb-3">
            <div class="trend-head">
                <span class="trend-title"><i class="fa fa-line-chart"></i> مقارنة وتاريخ الدفعات</span>
                <?php if (!empty($cmp_opts)): ?>
                <label class="d-flex align-items-center gap-1 mb-0 me-2" style="font-size:.8rem">
                    <span class="text-muted">قارن مع:</span>
                    <select onchange="cmpReload(this.value)" class="form-select form-select-sm" style="width:auto;min-width:220px">
                        <?php foreach ($cmp_opts as $o): $oid = (int)$o['BATCH_ID'];
                            $lbl = htmlspecialchars($o['BATCH_NO']) . ' — ' . n_format($o['TOTAL_AMOUNT']) . ' — ' . (int)$o['EMP_COUNT'] . ' موظف'; ?>
                        <option value="<?= $oid ?>" <?= ($oid === $cmp_cur_pid) ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php endif; ?>
                <?php if ($has_prev): ?>
                <button type="button" class="btn btn-sm btn-outline-primary trend-toggle" onclick="toggleTrendTable()">
                    <i class="fa fa-table"></i> <span id="trendToggleLbl">عرض الجدول</span>
                </button>
                <button type="button" class="btn btn-sm btn-primary" onclick="openDiffModal()">
                    <i class="fa fa-exchange"></i> تفاصيل الفروقات
                </button>
                <?php elseif (empty($cmp_opts)): ?>
                <span class="text-muted" style="font-size:.78rem"><i class="fa fa-info-circle"></i> هذه أول دفعة في النظام — لا توجد دفعة سابقة للمقارنة</span>
                <?php else: ?>
                <span class="text-muted" style="font-size:.78rem"><i class="fa fa-hand-o-up"></i> اختر دفعة من القائمة للمقارنة</span>
                <?php endif; ?>
            </div>

            <?php if ($has_prev): ?>
            <!-- مقارنة سريعة (4 بطاقات صغيرة) -->
            <div class="trend-quick">
                <?php
                $cur_amt  = (float)($cur_trend['TOTAL_AMOUNT'] ?? 0);
                $cur_emp  = (int)($cur_trend['EMP_COUNT']    ?? 0);
                $cur_mov  = (int)($cur_trend['MOVEMENT_COUNT'] ?? 0);
                $prv_amt  = (float)($first_prev['TOTAL_AMOUNT'] ?? 0);
                $prv_emp  = (int)($first_prev['EMP_COUNT']    ?? 0);
                $prv_mov  = (int)($first_prev['MOVEMENT_COUNT'] ?? 0);
                $prv_no   = $first_prev['BATCH_NO'] ?? '';
                $diff_amt = $cur_amt - $prv_amt;
                $diff_emp = $cur_emp - $prv_emp;
                $diff_mov = $cur_mov - $prv_mov;
                $pct_amt  = $prv_amt > 0 ? round(($diff_amt / $prv_amt) * 100, 2) : 0;
                $pct_emp  = $prv_emp > 0 ? round(($diff_emp / $prv_emp) * 100, 2) : 0;
                $cls_amt = $diff_amt > 0 ? 'up' : ($diff_amt < 0 ? 'down' : 'eq');
                $cls_emp = $diff_emp > 0 ? 'up' : ($diff_emp < 0 ? 'down' : 'eq');
                $arr_amt = $diff_amt > 0 ? '⬆' : ($diff_amt < 0 ? '⬇' : '─');
                $arr_emp = $diff_emp > 0 ? '⬆' : ($diff_emp < 0 ? '⬇' : '─');
                ?>
                <div class="tq-card">
                    <div class="tq-lbl">المقارنة مع</div>
                    <div class="tq-val"><?= htmlspecialchars($prv_no) ?></div>
                    <div class="tq-sub"><?= htmlspecialchars($first_prev['ENTRY_DATE_STR'] ?? '') ?></div>
                </div>
                <div class="tq-card tq-<?= $cls_amt ?>">
                    <div class="tq-lbl"><i class="fa fa-money"></i> الإجمالي</div>
                    <div class="tq-val"><?= $arr_amt ?> <?= ($diff_amt >= 0 ? '+' : '') . n_format($diff_amt) ?></div>
                    <div class="tq-sub"><?= ($pct_amt >= 0 ? '+' : '') . $pct_amt ?>%</div>
                </div>
                <div class="tq-card tq-<?= $cls_emp ?>">
                    <div class="tq-lbl"><i class="fa fa-users"></i> الموظفون</div>
                    <div class="tq-val"><?= $arr_emp ?> <?= ($diff_emp >= 0 ? '+' : '') . n_format($diff_emp) ?></div>
                    <div class="tq-sub"><?= ($pct_emp >= 0 ? '+' : '') . $pct_emp ?>%</div>
                </div>
                <div class="tq-card">
                    <div class="tq-lbl"><i class="fa fa-exchange"></i> الحركات</div>
                    <div class="tq-val"><?= $diff_mov >= 0 ? '+' : '' ?><?= n_format($diff_mov) ?></div>
                    <div class="tq-sub">حالياً <?= n_format($cur_mov) ?></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- جدول آخر 6 دفعات (مخفي افتراضياً) -->
            <div id="trendTableBox" style="display:none;margin-top:10px">
                <table class="table table-bordered table-sm" style="font-size:.78rem;background:#fff">
                    <thead style="background:#f1f5f9">
                        <tr>
                            <th style="width:34px">#</th>
                            <th>رقم الدفعة</th>
                            <th>تاريخ الإنشاء</th>
                            <th>تاريخ التنفيذ</th>
                            <th>الحالة</th>
                            <th>الشهور</th>
                            <th class="text-center">الموظفون</th>
                            <th class="text-center">الحركات</th>
                            <th class="text-end">الإجمالي</th>
                            <th class="text-end">الفرق %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 0; foreach ($trend_rows as $tr): $i++;
                            $is_cur   = (int)($tr['IS_CURRENT'] ?? 0) === 1;
                            $st_no    = (int)($tr['STATUS'] ?? 0);
                            $tr_amt   = (float)($tr['TOTAL_AMOUNT'] ?? 0);
                            // الفرق مقابل الدفعة الحالية
                            $diff_pct = '';
                            if (!$is_cur && $cur_amt > 0) {
                                $d = $tr_amt - $cur_amt;
                                $p = $cur_amt > 0 ? round(($d / $cur_amt) * 100, 2) : 0;
                                $diff_pct = ($p >= 0 ? '+' : '') . $p . '%';
                            }
                        ?>
                        <tr<?= $is_cur ? ' style="background:#fef9c3;font-weight:700"' : '' ?>>
                            <td><?= $i ?><?= $is_cur ? ' ⭐' : '' ?></td>
                            <td><?= htmlspecialchars($tr['BATCH_NO'] ?? '') ?></td>
                            <td><?= htmlspecialchars($tr['ENTRY_DATE_STR'] ?? '') ?></td>
                            <td><?= htmlspecialchars($tr['PAY_DATE_STR'] ?? '') ?: '<span class="text-muted">—</span>' ?></td>
                            <td><span class="bh-status s<?= $st_no ?>" style="font-size:.7rem"><?= htmlspecialchars($tr['STATUS_NAME'] ?? '') ?></span></td>
                            <td style="font-size:.72rem"><?= htmlspecialchars($tr['MONTHS_COVERED'] ?? '') ?></td>
                            <td class="text-center"><?= n_format((int)($tr['EMP_COUNT'] ?? 0)) ?></td>
                            <td class="text-center"><?= n_format((int)($tr['MOVEMENT_COUNT'] ?? 0)) ?></td>
                            <td class="text-end"><strong><?= n_format($tr_amt) ?></strong></td>
                            <td class="text-end" style="color:<?= strpos($diff_pct, '-') === 0 ? '#dc2626' : (strpos($diff_pct, '+') === 0 ? '#16a34a' : '#64748b') ?>"><?= $diff_pct ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- 🆕 فلتر موحّد — يتحكّم بكل الصفحة (الإجماليات العلوية + لوحة الموظفين السفلية) -->
        <div class="unified-filter mb-3">
            <div class="uf-inner">
                <div class="uf-label">
                    <i class="fa fa-filter"></i>
                    <strong>تصفية الموظفين:</strong>
                </div>
                <div class="btn-group" role="group" aria-label="فلتر حالة الموظف">
                    <input type="radio" class="btn-check" name="unifiedFilter" id="uf_all" value="all" checked
                           onchange="applyUnifiedFilter('all')">
                    <label class="btn btn-outline-primary" for="uf_all">
                        <i class="fa fa-globe"></i> الكل
                        <span class="uf-count"><?= (int)($emp_st_counts[1] ?? 0) + (int)($emp_st_counts[0] ?? 0) + (int)($emp_st_counts[2] ?? 0) + (int)($emp_st_counts[4] ?? 0) ?></span>
                    </label>

                    <input type="radio" class="btn-check" name="unifiedFilter" id="uf_active" value="active"
                           onchange="applyUnifiedFilter('active')">
                    <label class="btn btn-outline-success" for="uf_active">
                        <i class="fa fa-check-circle"></i> فعّال فقط
                        <span class="uf-count" id="ufCntActive"><?= (int)($emp_st_counts[1] ?? 0) ?></span>
                    </label>

                    <input type="radio" class="btn-check" name="unifiedFilter" id="uf_inactive" value="inactive"
                           onchange="applyUnifiedFilter('inactive')">
                    <label class="btn btn-outline-warning" for="uf_inactive">
                        <i class="fa fa-pause-circle"></i> غير فعّال
                        <span class="uf-count" id="ufCntInactive"><?= (int)($emp_st_counts[0] ?? 0) + (int)($emp_st_counts[2] ?? 0) + (int)($emp_st_counts[4] ?? 0) ?></span>
                    </label>
                </div>

                <?php if (count($batch_months_list) >= 1): ?>
                <!-- 🆕 اختيار الشهر للحالة (فعّال/غير فعّال حسب الشهر) -->
                <div class="uf-month-box">
                    <label class="form-label mb-0" style="font-size:.8rem;font-weight:700;color:#1e3a8a">
                        <i class="fa fa-calendar"></i> الحالة حسب شهر:
                    </label>
                    <select id="ufMonthSelect" class="form-select form-select-sm" style="width:auto;min-width:160px"
                            onchange="applyMonthSelection(this.value)">
                        <option value="">— الحالة الحالية —</option>
                        <?php foreach ($batch_months_list as $m):
                            $mf = strlen($m) === 6 ? substr($m, 4, 2) . '/' . substr($m, 0, 4) : $m;
                        ?>
                        <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($mf) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <!-- 🆕 أزرار تصدير منفصلة للفعّالين والغير فعّالين -->
                <div class="d-flex gap-1 align-items-center">
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="exportEmpsByStatus('active')" title="تصدير الموظفين الفعّالين (حسب الشهر المختار)">
                        <i class="fa fa-file-excel-o"></i> فعّالين
                    </button>
                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="exportEmpsByStatus('inactive')" title="تصدير الموظفين غير الفعّالين (متقاعد/متوفى/حساب مغلق)">
                        <i class="fa fa-file-excel-o"></i> غير فعّالين
                    </button>
                </div>

                <span id="ufBadge" class="ms-auto text-muted" style="font-size:.78rem">
                    <i class="fa fa-info-circle"></i> الفلتر يطبّق على كل الصفحة (الإجماليات + قوائم البنوك)
                </span>
            </div>
        </div>

        <!-- 🆕 تفصيل حسب المقر — تصميم محسّن -->
        <div class="pt-section">
            <div class="pt-section-head">
                <span class="pt-title"><i class="fa fa-building"></i> تفصيل الصرف حسب المقر</span>
                <span class="text-muted" style="font-size:.72rem">(<?= count($by_branch) ?> مقرات · <?= n_format($total_amt) ?> ش.ج)</span>
                <div class="pt-actions">
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="exportMbranchTable()" title="تصدير الجدول إلى Excel">
                        <i class="fa fa-file-excel-o"></i> Excel
                    </button>
                </div>
            </div>
            <div class="table-responsive">
            <table class="pt-tbl" id="mbranchBreakdownTable">
                <thead>
                    <tr>
                        <th>المقر</th>
                        <th class="text-center" style="width:80px">موظفين</th>
                        <th class="num col-t1" style="width:115px">راتب كامل</th>
                        <th class="num col-t2" style="width:115px">راتب جزئي</th>
                        <th class="num col-t3" style="width:130px">دفعة المستحقات</th>
                        <th class="num col-t4" style="width:140px">استحقاقات وإضافات</th>
                        <th class="num" style="width:135px">الإجمالي</th>
                    </tr>
                </thead>
                <tbody id="mbranchBreakdownBody">
                    <?php foreach ($by_branch as $brName => $brInfo):
                        $br_pct = $total_amt > 0 ? round(($brInfo['total'] / $total_amt) * 100, 1) : 0;
                    ?>
                    <tr class="mbr-row" data-mbr="<?= htmlspecialchars($brName, ENT_QUOTES) ?>">
                        <td>
                            <span class="mbr-name">
                                <span class="mbr-icon"><i class="fa fa-map-marker"></i></span>
                                <?= htmlspecialchars($brName) ?>
                            </span>
                        </td>
                        <td class="text-center mbr-count"><?= $brInfo['count'] ?></td>
                        <td class="num col-t1 mbr-t1<?= $brInfo['t1'] <= 0 ? ' text-muted' : '' ?>" data-orig="<?= (float)$brInfo['t1'] ?>"><?= n_format($brInfo['t1']) ?></td>
                        <td class="num col-t2 mbr-t2<?= $brInfo['t2'] <= 0 ? ' text-muted' : '' ?>" data-orig="<?= (float)$brInfo['t2'] ?>"><?= n_format($brInfo['t2']) ?></td>
                        <td class="num col-t3 mbr-t3<?= $brInfo['t3'] <= 0 ? ' text-muted' : '' ?>" data-orig="<?= (float)$brInfo['t3'] ?>"><?= n_format($brInfo['t3']) ?></td>
                        <td class="num col-t4 mbr-t4<?= $brInfo['t4'] <= 0 ? ' text-muted' : '' ?>" data-orig="<?= (float)$brInfo['t4'] ?>"><?= n_format($brInfo['t4']) ?></td>
                        <td class="num amount-cell mbr-total">
                            <span class="mbr-total-num"><?= n_format($brInfo['total']) ?></span>
                            <span class="pt-bar"><i class="mbr-bar-fill" style="width:<?= $br_pct ?>%"></i></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr id="mbranchTotalRow" class="pt-total">
                        <td>الإجمالي العام</td>
                        <td class="text-center" id="mbrTotalCount"><?= count($rows) ?></td>
                        <td class="num" id="mbrTotalT1" data-orig="<?= (float)$sum_t1 ?>"><?= n_format($sum_t1) ?></td>
                        <td class="num" id="mbrTotalT2" data-orig="<?= (float)$sum_t2 ?>"><?= n_format($sum_t2) ?></td>
                        <td class="num" id="mbrTotalT3" data-orig="<?= (float)$sum_t3 ?>"><?= n_format($sum_t3) ?></td>
                        <td class="num" id="mbrTotalT4" data-orig="<?= (float)$sum_t4 ?>"><?= n_format($sum_t4) ?></td>
                        <td class="num" id="mbrTotalAmt"><?= n_format($total_amt) ?></td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>

        <?php
        $bs_rows = isset($bank_summary) && is_array($bank_summary) ? $bank_summary : [];
        $bs_total_amt = 0; $bs_total_emp = 0; $bs_total_trx = 0; $bs_total_merged = 0;
        $_bmc_for_tot = isset($bank_merged_counts) && is_array($bank_merged_counts) ? $bank_merged_counts : [];
        foreach ($bs_rows as $br) {
            $bs_total_amt += (float)($br['TOTAL_AMOUNT'] ?? 0);
            $bs_total_emp += (int)($br['EMP_COUNT']    ?? 0);
            $_tx = (int)($br['TRANSACTION_COUNT'] ?? 0);
            $bs_total_trx += $_tx;
            $_mbno = (int)($br['MASTER_BANK_NO'] ?? 0);
            $bs_total_merged += (int)($_bmc_for_tot[$_mbno] ?? $_tx);
        }
        $batch_emp_count   = (int)($bi['EMP_COUNT'] ?? 0);
        $cross_bank_extra  = max(0, $bs_total_emp - $batch_emp_count);
        ?>
        <!-- 🆕 ملخص حسب البنك / المحفظة — تصميم محسّن -->
        <div class="pt-section">
            <div class="pt-section-head">
                <span class="pt-title"><i class="fa fa-bank"></i> ملخص حسب البنك / المحفظة</span>
                <span class="text-muted" style="font-size:.72rem">(<?= count($bs_rows) ?> جهات · <?= n_format($bs_total_amt) ?> ش.ج)</span>
            </div>
            <div class="table-responsive">
            <table class="pt-tbl">
                <thead>
                    <tr>
                        <th style="width:30px">#</th>
                        <th>البنك / المحفظة</th>
                        <th class="text-center" style="width:70px">النوع</th>
                        <th class="text-center" style="width:60px">فروع</th>
                        <th class="text-center" style="width:75px">موظفون</th>
                        <th class="text-center" style="width:75px" title="عدد الصفوف في تفاصيل الدفعة (قبل الدمج)">حركات</th>
                        <th class="text-center" style="width:90px" title="عدد الأسطر التي ستظهر في كشف البنك (بعد دمج الـ IBAN/الوجهة)">أسطر للبنك</th>
                        <th class="num" style="width:160px">الإجمالي</th>
                        <th class="text-center" style="width:280px">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($bs_rows)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-3">لا توجد حركات</td></tr>
                <?php else: $bIdx = 0;
                    $bmc = isset($bank_merged_counts) && is_array($bank_merged_counts) ? $bank_merged_counts : [];
                    foreach ($bs_rows as $br): $bIdx++;
                    $is_wallet = ((int)($br['PROVIDER_TYPE'] ?? 1)) == 2;
                    $type_label = $is_wallet ? 'محفظة' : 'بنك';
                    $bs_mbno = (int)($br['MASTER_BANK_NO'] ?? 0);
                    $bs_bnm  = $br['BANK_NAME'] ?? '';
                    $br_amt  = (float)($br['TOTAL_AMOUNT'] ?? 0);
                    $br_pct  = $bs_total_amt > 0 ? round(($br_amt / $bs_total_amt) * 100, 1) : 0;
                    $br_trx  = (int)($br['TRANSACTION_COUNT'] ?? 0);
                    $br_merged = (int)($bmc[$bs_mbno] ?? $br_trx);  // بعد الدمج (للبنوك) أو نفسه (للمحفظات)
                    $br_saved  = max(0, $br_trx - $br_merged);      // عدد الصفوف المدموجة
                ?>
                <tr class="bs-row" data-bs-mbno="<?= $bs_mbno ?>" data-bs-bank="<?= htmlspecialchars($bs_bnm, ENT_QUOTES) ?>">
                    <td class="text-muted"><?= $bIdx ?></td>
                    <td>
                        <span class="bk-name">
                            <span class="bk-icon <?= $is_wallet ? 'wallet' : 'bank' ?>">
                                <i class="fa <?= $is_wallet ? 'fa-mobile' : 'fa-bank' ?>"></i>
                            </span>
                            <?= htmlspecialchars($bs_bnm) ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="bk-type-pill <?= $is_wallet ? 'wallet' : 'bank' ?>"><?= $type_label ?></span>
                    </td>
                    <td class="text-center"><span class="bk-count-pill bs-branch-count" data-orig="<?= (int)($br['BRANCH_COUNT'] ?? 0) ?>"><?= (int)($br['BRANCH_COUNT'] ?? 0) ?></span></td>
                    <td class="text-center"><span class="bk-count-pill bs-emp-count" data-orig="<?= (int)($br['EMP_COUNT'] ?? 0) ?>"><?= (int)($br['EMP_COUNT'] ?? 0) ?></span></td>
                    <td class="text-center">
                        <?php
                        $audit_url = base_url("$MODULE_NAME/$TB_NAME/export_merge_audit?batch_id=" . $batch_id . "&master_bank_no=" . $bs_mbno);
                        ?>
                        <a href="<?= $audit_url ?>" target="_blank"
                           class="bk-count-pill bs-trx-count" style="text-decoration:none;cursor:pointer"
                           title="اضغط لتنزيل ملف Excel يوضّح كل حركة + ما اندمج معها"
                           data-orig="<?= $br_trx ?>"><?= $br_trx ?> <i class="fa fa-download" style="font-size:.65rem;opacity:.6"></i></a>
                    </td>
                    <td class="text-center">
                        <?php if ($is_wallet): ?>
                            <span class="bk-count-pill" style="background:#ede9fe;color:#6d28d9" title="المحفظة: كل صف يبقى منفصلاً (لا دمج)"><?= $br_merged ?></span>
                        <?php elseif ($br_saved > 0): ?>
                            <a href="<?= $audit_url ?>" target="_blank" class="bk-count-pill" style="background:#dcfce7;color:#166534;font-weight:700;text-decoration:none;cursor:pointer"
                                  title="تم دمج <?= $br_saved ?> صف لنفس الوجهة (IBAN/حساب) — اضغط لرؤية التفاصيل"><?= $br_merged ?>
                                <small style="opacity:.7;font-weight:500">↓<?= $br_saved ?></small>
                            </a>
                        <?php else: ?>
                            <span class="bk-count-pill" style="background:#f1f5f9;color:#475569" title="لا يوجد دمج — كل حركة سطر مستقل"><?= $br_merged ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="num amount-cell">
                        <span class="bs-amt" data-orig="<?= $br_amt ?>"><?= n_format($br_amt) ?></span>
                        <span class="pt-bar"><i class="bs-bar-fill" style="width:<?= $br_pct ?>%;background:linear-gradient(90deg,<?= $is_wallet ? '#a855f7,#6d28d9' : '#3b82f6,#1e40af' ?>)"></i></span>
                        <span class="bk-pct">حصّة: <strong><?= $br_pct ?>%</strong></span>
                    </td>
                    <td class="text-center">
                        <?php
                        $bs_mbno_int = (int)($br['MASTER_BANK_NO'] ?? 0);
                        $bs_linked_cover = ($bs_mbno_int > 0 && isset($covers_by_bank) && is_array($covers_by_bank) && isset($covers_by_bank[$bs_mbno_int]))
                                           ? $covers_by_bank[$bs_mbno_int] : null;
                        $bs_cover_create_url = base_url('payment_req/payment_req/cover_create_from_batch/' . (int)$batch_id . '/' . $bs_mbno_int);
                        ?>
                        <div class="btn-group btn-group-sm" role="group" style="display:inline-flex;gap:3px;flex-wrap:wrap;justify-content:center">

                            <?php if ($bs_mbno_int > 0): ?>
                            <!-- تصدير Excel — منطق مختلف حسب نوع المزود: -->
                            <!--   • بنك   → askMonthAndExport (يسأل عن الشهر فقط) -->
                            <!--   • محفظة → exportWalletAsk (يسأل عن الدمج فقط — بدون شهر) -->
                            <?php if ($is_wallet): ?>
                                <button type="button"
                                        onclick="exportWalletAsk(<?= $batch_id ?>, <?= $bs_mbno_int ?>, '<?= htmlspecialchars($bs_bnm, ENT_QUOTES) ?>'); return false;"
                                        class="btn btn-sm btn-outline-success p-0 px-1"
                                        title="تصدير كشف المحفظة — يسأل عن الدمج">
                                    <i class="fa fa-file-excel-o"></i>
                                </button>
                            <?php else: ?>
                                <button type="button"
                                        onclick="askMonthAndExport(<?= $batch_id ?>, <?= $bs_mbno_int ?>, 1); return false;"
                                        class="btn btn-sm btn-outline-success p-0 px-1"
                                        title="تصدير الكشف للبنك — يسأل عن الشهر">
                                    <i class="fa fa-file-excel-o"></i>
                                </button>
                            <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($bs_linked_cover): ?>
                                <!-- ✅ نموذج موجود → فتحه + سند صرف -->
                                <a href="<?= base_url('payment/payment_cover/get/' . (int)$bs_linked_cover['cover_id']) ?>"
                                   class="btn btn-sm btn-success p-0 px-2"
                                   title="نموذج صرف #<?= (int)$bs_linked_cover['cover_id'] ?>"
                                   target="_blank">
                                   <i class="fa fa-check-circle"></i> نموذج #<?= (int)$bs_linked_cover['cover_id'] ?>
                                </a>

                                <?php
                                $bs_fp_id = (int)$bs_linked_cover['fp_id'];
                                $bs_cover_id = (int)$bs_linked_cover['cover_id'];
                                if ($bs_fp_id > 0):
                                    // سند صرف موجود → فتح للتعديل
                                ?>
                                <a href="<?= base_url('payment/financial_payment/get/' . $bs_fp_id . '/edit/1') ?>"
                                   class="btn btn-sm btn-warning p-0 px-2"
                                   title="فتح سند الصرف #<?= $bs_fp_id ?>"
                                   target="_blank">
                                   <i class="fa fa-file-text"></i> سند #<?= $bs_fp_id ?>
                                </a>
                                <?php else:
                                    // لا يوجد سند صرف → زر إنشاء + auto-select البنك المسحوب عليه
                                    $bs_check_bank_id = (int)($bs_linked_cover['check_bank_id'] ?? 0);
                                ?>
                                <button type="button"
                                        class="btn btn-sm btn-primary p-0 px-2"
                                        title="إنشاء سند صرف — البنك المسحوب عليه يُختار تلقائياً<?= $bs_check_bank_id > 0 ? ' (مطابق باسم البنك)' : ' (لم يُلتقَط — اختر يدوياً)' ?>"
                                        onclick="fpCreateWithBankPreselect(<?= $bs_cover_id ?>, <?= $bs_check_bank_id ?>);">
                                   <i class="fa fa-plus-circle"></i> سند صرف
                                </button>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger p-0 px-1"
                                        title="حذف نموذج الصرف (مفيد عند فك الاحتساب)"
                                        onclick="coverDeleteFromBatch(<?= (int)$batch_id ?>, <?= $bs_mbno_int ?>, '<?= htmlspecialchars($bs_bnm, ENT_QUOTES) ?>', <?= $bs_cover_id ?>);">
                                    <i class="fa fa-trash"></i>
                                </button>
                                <?php endif; ?>

                            <?php elseif ($bs_mbno_int > 0): ?>
                                <!-- لا يوجد نموذج بعد → زر إنشاء -->
                                <a href="<?= $bs_cover_create_url ?>"
                                   class="btn btn-sm btn-outline-primary p-0 px-2"
                                   title="إنشاء نموذج صرف لـ <?= htmlspecialchars($bs_bnm, ENT_QUOTES) ?>"
                                   target="_blank">
                                   <i class="fa fa-file-text-o"></i> نموذج صرف
                                </a>
                            <?php endif; ?>

                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                <tr id="bsTotalRow" class="pt-total">
                    <td colspan="4">الإجمالي العام</td>
                    <td class="text-center" id="bsTotalEmpCell" title="مجموع الـ column = <?= $bs_total_emp ?> · الموظفين الفعليين = <?= $batch_emp_count ?><?php if ($cross_bank_extra > 0): ?> · <?= $cross_bank_extra ?> موظف عندهم split على أكثر من بنك<?php endif; ?>">
                        <span id="bsTotalEmpSum" data-orig="<?= $bs_total_emp ?>"><?= $bs_total_emp ?></span>
                        <?php if ($cross_bank_extra > 0): ?>
                        <small id="bsTotalEmpReal" style="font-weight:500;color:#fbbf24;font-size:.7rem" data-orig="<?= $batch_emp_count ?>">(<?= $batch_emp_count ?> فعلي)</small>
                        <?php endif; ?>
                    </td>
                    <td class="text-center" id="bsTotalTrx" data-orig="<?= $bs_total_trx ?>"><?= $bs_total_trx ?></td>
                    <td class="text-center" id="bsTotalMerged" data-orig="<?= $bs_total_merged ?>"
                        title="مجموع الأسطر التي ستُرسَل للبنوك بعد دمج IBAN<?php if ($bs_total_trx > $bs_total_merged): ?> — تم توفير <?= $bs_total_trx - $bs_total_merged ?> سطر بالدمج<?php endif; ?>">
                        <?= $bs_total_merged ?>
                        <?php if ($bs_total_trx > $bs_total_merged): ?>
                            <small style="font-weight:500;color:#10b981;font-size:.7rem">↓<?= $bs_total_trx - $bs_total_merged ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="num" id="bsTotalAmt" data-orig="<?= (float)$bs_total_amt ?>"><?= n_format($bs_total_amt) ?></td>
                    <td></td>
                </tr>
                </tbody>
            </table>
            </div>
        </div>

        <?php
        // ═══════════════════════════════════════════════════════════
        // بناء قائمة المستفيدين (recipients) لـ Tab 2
        // ═══════════════════════════════════════════════════════════
        $rcp_rows = isset($recipients) && is_array($recipients) ? $recipients : [];
        $rcp_self = 0; $rcp_benef = 0; $rcp_other = 0;
        $rcp_bank_count = 0; $rcp_wallet_count = 0;
        $rcp_total_amt = 0;
        $rcp_clean = [];
        $emp_branch_idx = [];
        // عدّ سطور كل موظف لاكتشاف split
        $emp_row_count = [];
        foreach ($rcp_rows as $tmp) {
            $tno = (int)($tmp['EMP_NO'] ?? 0);
            if (!$tno) continue;
            $emp_row_count[$tno] = ($emp_row_count[$tno] ?? 0) + 1;
        }
        $split_emp_set = []; // emp_no => true لكل موظف عنده split
        foreach ($emp_row_count as $eno => $cnt) {
            if ($cnt > 1) $split_emp_set[$eno] = true;
        }
        $stat_split_emps = count($split_emp_set);

        foreach ($rows as $rEmp) {
            $eno = (int)($rEmp['EMP_NO'] ?? 0);
            if ($eno) $emp_branch_idx[$eno] = $rEmp['BRANCH_NAME'] ?? '';
        }
        foreach ($rcp_rows as $rR) {
            $eno = (int)($rR['EMP_NO'] ?? 0);
            $alloc = (float)($rR['TOTAL_AMOUNT'] ?? 0);
            if ($alloc <= 0) continue;
            $owner = trim($rR['OWNER_NAME'] ?? '');
            $emp_name = trim($rR['EMP_NAME'] ?? '');
            $type = 'self'; $rcp_label = 'الموظف نفسه';
            $rcp_color = '#059669'; $rcp_bg = '#dcfce7';
            $recipient_name = $emp_name;
            if (!empty($rR['BENEFICIARY_ID'])) {
                $type = 'benef';
                $recipient_name = $rR['BENEF_NAME'] ?? $owner;
                $rcp_label = 'وريث - ' . ($rR['BENEF_REL_NAME'] ?? '');
                $rcp_color = '#6d28d9'; $rcp_bg = '#f5f3ff';
                $rcp_benef++;
            } elseif ($owner && $owner !== $emp_name) {
                $type = 'other';
                $recipient_name = $owner;
                $rcp_label = 'صاحب حساب';
                $rcp_color = '#9a3412'; $rcp_bg = '#fff7ed';
                $rcp_other++;
            } else {
                $rcp_self++;
            }
            $is_wallet = (int)($rR['PROVIDER_TYPE'] ?? 1) === 2;
            if ($is_wallet) $rcp_wallet_count++; else $rcp_bank_count++;
            $iban = $rR['IBAN'] ?? '';
            $iban_short = $iban ? (substr($iban, 0, 8) . '…' . substr($iban, -4)) : '';
            $acc_no = $rR['BANK_ACCOUNT'] ?? ($rR['ACCOUNT_NO'] ?? ($rR['WALLET_NUMBER'] ?? ''));
            $rcp_clean[] = [
                'emp_no'        => $eno,
                'emp_name'      => $emp_name,
                'emp_branch'    => $emp_branch_idx[$eno] ?? '',
                'recipient'     => $recipient_name,
                'rel_label'     => $rcp_label,
                'type'          => $type,
                'type_color'    => $rcp_color,
                'type_bg'       => $rcp_bg,
                'master_bank'   => $rR['MASTER_BANK_NAME'] ?? '',
                'provider_type' => (int)($rR['PROVIDER_TYPE'] ?? 1),
                'prov_branch'   => $rR['BANK_BRANCH_NAME'] ?? '',
                'iban'          => $iban,
                'iban_short'    => $iban_short,
                'account_no'    => $acc_no,
                'owner_phone'   => $rR['OWNER_PHONE'] ?? '',
                'amount'        => $alloc,
                // 🆕 flag: هل الموظف عنده split (= أكثر من سطر/مستلم)
                'is_split_emp'  => isset($split_emp_set[$eno]) ? 1 : 0,
                'is_wallet'     => $is_wallet ? 1 : 0,
            ];
            $rcp_total_amt += $alloc;
        }
        usort($rcp_clean, function($a, $b){
            if ($a['emp_no'] !== $b['emp_no']) return $a['emp_no'] <=> $b['emp_no'];
            $order = ['self'=>1, 'other'=>2, 'benef'=>3];
            return ($order[$a['type']] ?? 9) <=> ($order[$b['type']] ?? 9);
        });
        ?>

        <?php
        // عدد الموظفين الفريدين الظاهرين في Tab 2 (= عدد الموظفين الفعليين، رغم تعدد الحركات)
        $rcp_distinct_emps = count(array_unique(array_column($rcp_clean, 'emp_no')));
        $extra_movements   = max(0, count($rcp_clean) - $rcp_distinct_emps);
        ?>
        <!-- ═══════════════════ Tabs ═══════════════════ -->
        <ul class="nav nav-tabs mb-3" id="bdTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="bd-tab-emps-btn" data-bs-toggle="tab" data-bs-target="#bd-tab-emps" type="button"
                        title="عدد الموظفين الفريدين في الدفعة">
                    <i class="fa fa-users me-1"></i> الموظفون
                    <span class="badge bg-primary ms-1"><?= count($rows) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="bd-tab-rcp-btn" data-bs-toggle="tab" data-bs-target="#bd-tab-rcp" type="button"
                        title="عدد التحويلات البنكية الفعلية (موظف عنده split على عدة بنوك يظهر أكثر من مرة)">
                    <i class="fa fa-exchange me-1"></i> الحركات البنكية
                    <span class="badge bg-success ms-1"><?= count($rcp_clean) ?></span>
                    <?php if ($extra_movements > 0): ?>
                    <small class="ms-1" style="font-size:.65rem;color:#9a3412;font-weight:600">(+<?= $extra_movements ?> split)</small>
                    <?php endif; ?>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="bdTabsContent">
        <!-- ─────── Tab 1: الموظفون ─────── -->
        <div class="tab-pane fade show active" id="bd-tab-emps" role="tabpanel">

        <!-- Legend ألوان حالات الموظفين + فلتر -->
        <div class="emp-legend mb-2 d-flex align-items-center flex-wrap gap-2">
            <span class="emp-legend-title"><i class="fa fa-paint-brush me-1"></i> دلالة الألوان:</span>
            <span class="emp-legend-item">
                <span class="emp-legend-swatch" style="background:#fff;border:1px solid #cbd5e1"></span>
                <span style="color:#15803d;font-weight:700"><i class="fa fa-check-circle"></i> فعّال</span>
                <small id="cnt-st-1">(<?= (int)($emp_st_counts[1] ?? 0) ?>)</small>
            </span>
            <span class="emp-legend-item">
                <span class="emp-legend-swatch" style="background:#ffedd5"></span>
                <span style="color:#9a3412;font-weight:700"><i class="fa fa-clock-o"></i> متقاعد</span>
                <small id="cnt-st-0">(<?= (int)($emp_st_counts[0] ?? 0) ?>)</small>
            </span>
            <span class="emp-legend-item">
                <span class="emp-legend-swatch" style="background:#fee2e2"></span>
                <span style="color:#991b1b;font-weight:700"><i class="fa fa-times-circle"></i> متوفى</span>
                <small id="cnt-st-2">(<?= (int)($emp_st_counts[2] ?? 0) ?>)</small>
            </span>
            <span class="emp-legend-item">
                <span class="emp-legend-swatch" style="background:#fef3c7"></span>
                <span style="color:#92400e;font-weight:700"><i class="fa fa-ban"></i> حساب مغلق</span>
                <small id="cnt-st-4">(<?= (int)($emp_st_counts[4] ?? 0) ?>)</small>
            </span>
            <span class="ms-auto text-muted" style="font-size:.7rem">
                <i class="fa fa-info-circle"></i> استخدم الفلتر العلوي (أعلى الصفحة) للتحكّم بكل الأرقام والقوائم معاً
            </span>
        </div>

        <?php $bIdx = 0; foreach ($banks as $bankName => $bankData): $bIdx++;
            $multi_branch = count($bankData['branches']) > 1;
        ?>
        <div class="bank-section" id="bank_<?= $bIdx ?>" data-bank-name="<?= htmlspecialchars($bankName, ENT_QUOTES) ?>" data-mbno="<?= (int)($bankData['master_bank_no'] ?? 0) ?>">
            <div class="bank-header">
                <div onclick="$(this).parent().next().toggle()" style="flex:1;cursor:pointer">
                    <i class="fa fa-bank me-1"></i>
                    <strong><?= $bankName ?></strong>
                    <span class="text-muted ms-2 bs-meta" style="font-size:.75rem"><span class="bs-emp-cnt" data-orig="<?= $bankData['count'] ?>"><?= $bankData['count'] ?></span> موظف<?php if ($multi_branch): ?> — <span class="bs-grp-cnt" data-orig="<?= count($bankData['branches']) ?>"><?= count($bankData['branches']) ?></span> مجموعة توزيع<?php endif; ?></span>
                    <?php
                    $b_alerts = [];
                    if (!empty($bankData['with_split']))         $b_alerts[] = '<span class="bsum bsum-split" title="موظفون مبلغهم موزّع على أكثر من حساب/بنك"><i class="fa fa-sitemap"></i> ' . $bankData['with_split'] . ' بتوزيع</span>';
                    if (!empty($bankData['with_benef']))         $b_alerts[] = '<span class="bsum bsum-benef">'    . $bankData['with_benef']        . ' بورث</span>';
                    if (!empty($bankData['with_multi_acc']))     $b_alerts[] = '<span class="bsum bsum-multi">'    . $bankData['with_multi_acc']    . ' بحسابات متعددة</span>';
                    if (!empty($bankData['with_no_split']))      $b_alerts[] = '<span class="bsum bsum-warn">⚠ '    . $bankData['with_no_split']     . ' بدون توزيع</span>';
                    if (!empty($bankData['with_inactive_acc']))  $b_alerts[] = '<span class="bsum bsum-inactive">' . $bankData['with_inactive_acc']. ' بحساب موقوف</span>';
                    if ($b_alerts) echo '<span class="ms-2 d-inline-flex gap-1 flex-wrap">' . implode('', $b_alerts) . '</span>';
                    ?>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <?php if ($multi_branch): ?>
                    <span class="bank-tools" onclick="event.stopPropagation()">
                        <button type="button" class="btn-mini" data-act="collapse-branches" title="اطوي كل الفروع"><i class="fa fa-compress"></i> اطوي الكل</button>
                        <button type="button" class="btn-mini" data-act="expand-branches" title="افتح كل الفروع"><i class="fa fa-expand"></i> افتح الكل</button>
                    </span>
                    <?php endif; ?>
                    <span class="amt bs-total-amt" data-orig="<?= (float)$bankData['total'] ?>"><?= n_format($bankData['total']) ?></span>
                    <?php if (!empty($bankData['is_wallet'])): ?>
                    <!-- 🆕 محفظة: زر يسأل عن الدمج قبل التصدير -->
                    <button type="button" class="btn btn-sm btn-outline-success"
                            onclick="event.stopPropagation(); exportWalletAsk(<?= (int)$batch_id ?>, <?= (int)$bankData['master_bank_no'] ?>, '<?= htmlspecialchars($bankName, ENT_QUOTES) ?>')"
                            title="تصدير <?= $bankName ?> لـ بال باي">
                        <i class="fa fa-file-excel-o"></i>
                    </button>
                    <a href="<?= $export_wallet_acc_url ?>?batch_id=<?= $batch_id ?>&master_bank_no=<?= $bankData['master_bank_no'] ?>"
                       class="btn btn-sm btn-outline-warning"
                       title="كشف للمحاسب — مدمج بالهوية مع إبراز التكرار + ملخّص"
                       onclick="event.stopPropagation()">
                       <i class="fa fa-user-circle-o"></i> للمحاسب
                    </a>
                    <?php else: ?>
                    <!-- بنك عادي: تصدير مع سؤال عن الشهر -->
                    <button type="button" onclick="event.stopPropagation(); askMonthAndExport(<?= $batch_id ?>, <?= (int)$bankData['master_bank_no'] ?>, 1); return false;" class="btn btn-sm btn-outline-success" title="تصدير <?= $bankName ?> — نموذج البنك"><i class="fa fa-file-excel-o"></i></button>
                    <?php endif; ?>
                    <?php
                        // 🆕 حالة نموذج الصرف لهذا البنك:
                        //   • لو غير منشأ → زر "إنشاء نموذج صرف" (أزرق)
                        //   • لو منشأ بدون سند صرف → زر "فتح" (أخضر) + زر "حذف" (أحمر)
                        //   • لو منشأ ومعه سند صرف → زر "فتح" فقط (مقفل، لا يُحذف)
                        // الـ endpoints عندنا (payment_req) — payment module ما يتأثر
                        $cover_create_url = base_url('payment_req/payment_req/cover_create_from_batch/' . (int)$batch_id . '/' . (int)$bankData['master_bank_no']);
                        $cover_delete_url = base_url('payment_req/payment_req/cover_delete_from_batch/' . (int)$batch_id . '/' . (int)$bankData['master_bank_no']);
                        $bank_mbno = (int)$bankData['master_bank_no'];
                        $linked_cover = isset($covers_by_bank) && is_array($covers_by_bank) && isset($covers_by_bank[$bank_mbno])
                                        ? $covers_by_bank[$bank_mbno] : null;
                        if ($linked_cover):
                            $cover_open_url = base_url('payment/payment_cover/get/' . (int)$linked_cover['cover_id']);
                            $has_fp = (int)$linked_cover['fp_id'] > 0;
                    ?>
                    <a href="<?= $cover_open_url ?>"
                       class="btn btn-sm btn-success"
                       title="فتح نموذج الصرف #<?= (int)$linked_cover['cover_id'] ?><?= $has_fp ? ' (يحتوي سند صرف #' . (int)$linked_cover['fp_id'] . ')' : '' ?>"
                       onclick="event.stopPropagation()"
                       target="_blank">
                       <i class="fa fa-check-circle"></i> نموذج #<?= (int)$linked_cover['cover_id'] ?>
                    </a>
                    <?php if (!$has_fp): ?>
                    <button type="button"
                            class="btn btn-sm btn-outline-danger"
                            title="حذف نموذج الصرف المرتبط (مفيد لو فك الاحتساب)"
                            onclick="event.stopPropagation(); coverDeleteFromBatch(<?= (int)$batch_id ?>, <?= $bank_mbno ?>, '<?= htmlspecialchars($bankName, ENT_QUOTES) ?>', <?= (int)$linked_cover['cover_id'] ?>);">
                        <i class="fa fa-trash"></i>
                    </button>
                    <?php else: ?>
                    <span class="badge bg-warning text-dark"
                          title="لا يمكن الحذف — يوجد سند صرف #<?= (int)$linked_cover['fp_id'] ?>"
                          onclick="event.stopPropagation()" style="cursor:help">
                        🔒 سند #<?= (int)$linked_cover['fp_id'] ?>
                    </span>
                    <?php endif; ?>

                    <?php elseif (HaveAccess($cover_create_url)): ?>
                    <a href="<?= $cover_create_url ?>"
                       class="btn btn-sm btn-outline-primary"
                       title="إنشاء نموذج صرف لـ <?= htmlspecialchars($bankName, ENT_QUOTES) ?>"
                       onclick="event.stopPropagation()"
                       target="_blank">
                       <i class="fa fa-file-text-o"></i> نموذج صرف
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="bank-body">
                <?php foreach ($bankData['branches'] as $brName => $brData):
                    // ترتيب الموظفين داخل الفرع حسب المبلغ تنازلياً
                    $br_emps = $brData['emps'];
                    uasort($br_emps, function($a, $b){ return $b['amount'] <=> $a['amount']; });
                    $br_emp_count = count($br_emps);
                ?>
                <div class="branch-block" data-branch-name="<?= htmlspecialchars($brName, ENT_QUOTES) ?>">
                <?php if ($multi_branch): ?>
                <div class="branch-header" data-toggle-branch>
                    <i class="fa fa-chevron-down br-chev"></i>
                    <i class="fa fa-map-marker"></i>
                    <span class="br-name"><?= htmlspecialchars($brName) ?></span>
                    <span class="br-meta"><span class="br-emp-cnt" data-orig="<?= $br_emp_count ?>"><?= $br_emp_count ?></span> موظف</span>
                    <span class="br-amt" data-orig="<?= (float)$brData['total'] ?>"><?= n_format($brData['total']) ?></span>
                </div>
                <?php endif; ?>
                <div class="branch-body">
                <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0 emp-tbl">
                    <thead><tr>
                        <th style="width:30px">#</th>
                        <th style="width:80px">رقم الموظف</th>
                        <th>الاسم</th>
                        <th style="width:160px">حالة الحساب</th>
                        <th style="width:130px">المقر</th>
                        <th>الطلبات</th>
                        <th class="text-end" style="width:110px">المبلغ</th>
                    </tr></thead>
                    <tbody>
                    <?php $n = 0; foreach ($br_emps as $eRow): $n++;
                        $emp   = (int)$eRow['emp_no'];
                        $bcnt  = (int)$eRow['benef_count'];
                        $acnt  = (int)$eRow['active_acc_count'];
                        $icnt  = (int)$eRow['inactive_acc_count'];
                        $scnt  = (int)$eRow['split_count'];
                        $rcnt  = (int)$eRow['recipient_count'];   // كم مستلم/حساب لهذا الموظف داخل البنك
                        $needs_attention = ($bcnt > 0 && $scnt == 0) || $acnt > 1 || $icnt > 0;
                        $row_class = $needs_attention ? 'row-attention' : '';
                    ?>
                    <?php
                        // 🆕 هل في override نشط على هذا الموظف؟ (يبحث في recipients عن أي سطر له override)
                        $emp_ovr_pt = null; $emp_ovr_acc = null; $emp_has_ovr = false;
                        if (!empty($recipients) && is_array($recipients)) {
                            foreach ($recipients as $rcp) {
                                if ((int)($rcp['EMP_NO'] ?? 0) === $emp) {
                                    $pt  = $rcp['OVERRIDE_PROVIDER_TYPE'] ?? null;
                                    $acc = $rcp['OVERRIDE_ACC_ID'] ?? null;
                                    if (($pt !== null && $pt !== '') || $acc) {
                                        $emp_has_ovr = true;
                                        $emp_ovr_pt  = ($pt !== null && $pt !== '') ? (int)$pt : null;
                                        $emp_ovr_acc = $acc ? (int)$acc : null;
                                        break;
                                    }
                                }
                            }
                        }
                        $can_redist = ($st == 0);  // فقط لما الدفعة محتسبة

                        // 🆕 لون الصف حسب حالة الموظف (4 حالات)
                        $emp_disp = (int)($eRow['display_status'] ?? 1);
                        $row_style = '';
                        if ($emp_disp === 2)      $row_style = 'background:#fee2e2'; // متوفى
                        elseif ($emp_disp === 4)  $row_style = 'background:#fef3c7'; // حساب مغلق
                        elseif ($emp_disp === 0)  $row_style = 'background:#ffedd5'; // متقاعد
                    ?>
                    <?php
                        // 🆕 خريطة حالات الموظف لكل شهر في الدفعة (للفلتر الزمني)
                        $emp_st_map_json = '';
                        if (!empty($emp_status_by_month[$emp])) {
                            $emp_st_map_json = htmlspecialchars(
                                json_encode($emp_status_by_month[$emp], JSON_UNESCAPED_UNICODE),
                                ENT_QUOTES);
                        }
                    ?>
                    <tr class="emp-row <?= $row_class ?>"
                        data-emp="<?= $emp ?>"
                        data-emp-status="<?= $emp_disp ?>"
                        data-emp-status-default="<?= $emp_disp ?>"
                        data-emp-status-map='<?= $emp_st_map_json ?>'
                        data-amount="<?= (float)$eRow['amount'] ?>"
                        data-emp-total="<?= (float)($eRow['emp_total'] ?? 0) ?>"
                        data-emp-t1="<?= (float)($eRow['amt_t1'] ?? 0) ?>"
                        data-emp-t2="<?= (float)($eRow['amt_t2'] ?? 0) ?>"
                        data-emp-t3="<?= (float)($eRow['amt_t3'] ?? 0) ?>"
                        data-emp-t4="<?= (float)($eRow['amt_t4'] ?? 0) ?>"
                        data-rcpt-count="<?= $rcnt ?>"
                        data-mbranch="<?= htmlspecialchars($eRow['branch_name'] ?: 'غير محدد', ENT_QUOTES) ?>"
                        <?= $row_style ? ' style="' . $row_style . '"' : '' ?>>
                        <td class="text-center">
                            <?php if ($acnt > 1 || $bcnt > 0 || $rcnt > 1): ?>
                                <button type="button" class="btn-expand" title="عرض التوزيع">
                                    <i class="fa fa-plus-square-o"></i>
                                </button>
                            <?php else: ?>
                                <span class="text-muted"><?= $n ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-bold"><?= $emp ?></td>
                        <td>
                            <?= htmlspecialchars($eRow['emp_name']) ?>
                            <?php if ($emp_disp === 2): ?>
                                <span class="b-tag" style="background:#fee2e2;color:#991b1b;font-weight:700"><i class="fa fa-times-circle"></i> متوفى</span>
                            <?php elseif ($emp_disp === 4): ?>
                                <span class="b-tag" style="background:#fef3c7;color:#92400e;font-weight:700"><i class="fa fa-ban"></i> حساب مغلق</span>
                            <?php elseif ($emp_disp === 0): ?>
                                <span class="b-tag" style="background:#ffedd5;color:#9a3412;font-weight:700"><i class="fa fa-clock-o"></i> متقاعد</span>
                            <?php endif; ?>
                            <?php if ($rcnt > 1): ?>
                                <span class="b-tag b-multi" title="مبلغ الموظف داخل هذا البنك موزّع على <?= $rcnt ?> حساب/مستلم"><i class="fa fa-sitemap"></i> <?= $rcnt ?> حسابات</span>
                            <?php endif; ?>
                            <?php if ($bcnt > 0): ?>
                                <span class="b-tag b-benef" title="الموظف عنده <?= $bcnt ?> ورث"><i class="fa fa-users"></i> <?= $bcnt ?> ورث</span>
                            <?php endif; ?>
                            <?php if ($icnt > 0): ?>
                                <span class="b-tag b-inact" title="<?= $icnt ?> حساب موقوف"><i class="fa fa-pause"></i> <?= $icnt ?> موقوف</span>
                            <?php endif; ?>
                            <?php if ($emp_has_ovr):
                                $ob = $emp_ovr_acc ? '#fef3c7' : ($emp_ovr_pt === 2 ? '#f5f3ff' : '#dbeafe');
                                $of = $emp_ovr_acc ? '#92400e' : ($emp_ovr_pt === 2 ? '#6d28d9' : '#1e40af');
                                $oi = $emp_ovr_acc ? 'fa-bullseye' : ($emp_ovr_pt === 2 ? 'fa-mobile' : 'fa-bank');
                                $ol = $emp_ovr_acc ? 'حساب محدد' : ($emp_ovr_pt === 2 ? 'محفظة فقط' : 'بنك فقط');
                            ?>
                                <span class="b-tag" title="توزيع مخصص: <?= $ol ?>" style="background:<?= $ob ?>;color:<?= $of ?>;font-weight:700">
                                    <i class="fa <?= $oi ?>"></i> <?= $ol ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($bcnt > 0 && $scnt == 0): ?>
                                <span class="b-tag b-warn" title="موظف عنده ورثة لكن المبلغ على الحساب القديم — راجع التوزيع">
                                    ⚠ على الحساب القديم
                                </span>
                            <?php elseif ($scnt > 0): ?>
                                <span class="b-tag b-ok" title="مبلغ موزّع على <?= $scnt ?> حساب">
                                    <i class="fa fa-check-circle"></i> توزيع (<?= $scnt ?>)
                                </span>
                            <?php else: ?>
                                <span class="text-muted" style="font-size:.7rem">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:.75rem"><?= htmlspecialchars($eRow['branch_name']) ?></td>
                        <td style="font-size:.72rem"><?= htmlspecialchars($eRow['req_nos']) ?></td>
                        <td class="text-end">
                            <span class="amt"><?= n_format((float)$eRow['amount']) ?></span>
                            <?php if ($can_redist): ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-1 btn-redist"
                                        data-emp="<?= $emp ?>"
                                        data-emp-name="<?= htmlspecialchars($eRow['emp_name'], ENT_QUOTES) ?>"
                                        data-amount="<?= (float)$eRow['amount'] ?>"
                                        data-pt="<?= $emp_ovr_pt !== null ? (int)$emp_ovr_pt : '' ?>"
                                        data-acc="<?= $emp_ovr_acc ? (int)$emp_ovr_acc : '' ?>"
                                        title="إعادة توزيع المبلغ على حسابات الموظف">
                                    <i class="fa fa-random"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if ($br_emp_count > 1): ?>
                    <tr class="branch-foot-row" style="background:#f0fdf4;font-weight:700"><td colspan="6" class="text-end">إجمالي <?= $multi_branch ? htmlspecialchars($brName) : htmlspecialchars($bankName) ?> (<span class="bf-emp-cnt" data-orig="<?= $br_emp_count ?>"><?= $br_emp_count ?></span> موظف)</td><td class="text-end bf-amt" data-orig="<?= (float)$brData['total'] ?>"><?= n_format($brData['total']) ?></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                </div>
                </div><!-- /.branch-body -->
                </div><!-- /.branch-block -->
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        </div><!-- /bd-tab-emps -->

        <!-- ─────── Tab 2: المستفيدون ─────── -->
        <div class="tab-pane fade" id="bd-tab-rcp" role="tabpanel">

            <!-- إحصائيات -->
            <div class="rcp-stats mb-2">
                <div class="rcp-stat">
                    <div class="rs-lbl"><i class="fa fa-list-ol"></i> إجمالي الحركات</div>
                    <div class="rs-val"><?= count($rcp_clean) ?></div>
                    <div class="rs-sub">لـ <?= $rcp_distinct_emps ?> موظف<?php if ($extra_movements > 0): ?> · <span style="color:#9a3412">+<?= $extra_movements ?> بـ split</span><?php endif; ?></div>
                </div>
                <div class="rcp-stat" style="background:#eff6ff;border-color:#bfdbfe">
                    <div class="rs-lbl"><i class="fa fa-bank"></i> بنوك / محافظ</div>
                    <div class="rs-val"><?= $rcp_bank_count + $rcp_wallet_count ?></div>
                    <div class="rs-sub"><?= $rcp_bank_count ?> بنك<?php if ($rcp_wallet_count > 0): ?> + <?= $rcp_wallet_count ?> محفظة<?php endif; ?></div>
                </div>
                <div class="rcp-stat c-self">
                    <div class="rs-lbl"><i class="fa fa-user"></i> الموظف يقبض لنفسه</div>
                    <div class="rs-val"><?= $rcp_self ?></div>
                </div>
                <div class="rcp-stat c-benef">
                    <div class="rs-lbl"><i class="fa fa-users"></i> ورثة / مستفيدون</div>
                    <div class="rs-val"><?= $rcp_benef ?></div>
                </div>
                <?php if ($rcp_other > 0): ?>
                <div class="rcp-stat c-owner">
                    <div class="rs-lbl"><i class="fa fa-id-card"></i> صاحب حساب مختلف</div>
                    <div class="rs-val"><?= $rcp_other ?></div>
                </div>
                <?php endif; ?>
                <div class="rcp-stat c-amt">
                    <div class="rs-lbl"><i class="fa fa-money"></i> إجمالي المبلغ</div>
                    <div class="rs-val"><?= n_format($rcp_total_amt) ?></div>
                </div>
            </div>

            <!-- 🆕 فلاتر بنمط شاشات الـ index — كلها ظاهرة دائماً، layout أفقي -->
            <div class="rcp-filter-block mb-2">
                <div class="row g-2">
                    <div class="form-group col-md-2">
                        <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;color:#475569">
                            <i class="fa fa-hashtag" style="color:#3b82f6"></i> رقم الموظف
                        </label>
                        <input type="text" id="bdRcpFilterEmpNo" class="form-control form-control-sm" placeholder="مثال: 29">
                    </div>
                    <div class="form-group col-md-3">
                        <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;color:#475569">
                            <i class="fa fa-user" style="color:#3b82f6"></i> اسم الموظف
                        </label>
                        <input type="text" id="bdRcpFilterEmpName" class="form-control form-control-sm" placeholder="جزء من الاسم">
                    </div>
                    <div class="form-group col-md-2">
                        <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;color:#475569">
                            <i class="fa fa-bank" style="color:#3b82f6"></i> البنك / المحفظة
                        </label>
                        <select id="bdRcpFilterBank" class="form-control">
                            <option value="">— الكل —</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;color:#475569">
                            <i class="fa fa-sitemap" style="color:#3b82f6"></i> الفرع
                        </label>
                        <select id="bdRcpFilterBranch" class="form-control">
                            <option value="">— الكل —</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;color:#475569">
                            <i class="fa fa-tag" style="color:#3b82f6"></i> نوع المستلم
                        </label>
                        <select id="bdRcpFilterType" class="form-control">
                            <option value="">— الكل —</option>
                            <option value="self">الموظف نفسه</option>
                            <option value="benef">ورثة فقط</option>
                            <option value="other">صاحب حساب مختلف</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mt-1">
                    <div class="form-group col-md-4">
                        <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;color:#475569">
                            <i class="fa fa-balance-scale" style="color:#3b82f6"></i> شرط المبلغ (1)
                        </label>
                        <div class="input-group">
                            <select id="bdRcpFilterOp1" class="form-control" style="max-width:140px">
                                <option value="">— بدون —</option>
                                <option value=">">أكبر من (&gt;)</option>
                                <option value=">=">أكبر أو يساوي (≥)</option>
                                <option value="<">أصغر من (&lt;)</option>
                                <option value="<=">أصغر أو يساوي (≤)</option>
                                <option value="=">يساوي (=)</option>
                                <option value="!=">لا يساوي (≠)</option>
                            </select>
                            <input type="number" id="bdRcpFilterVal1" class="form-control" placeholder="القيمة" min="0" step="any">
                        </div>
                    </div>
                    <div class="form-group col-md-4">
                        <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;color:#475569">
                            <i class="fa fa-balance-scale" style="color:#9333ea"></i> شرط المبلغ (2) — اختياري
                        </label>
                        <div class="input-group">
                            <select id="bdRcpFilterOp2" class="form-control" style="max-width:140px">
                                <option value="">— بدون —</option>
                                <option value=">">أكبر من (&gt;)</option>
                                <option value=">=">أكبر أو يساوي (≥)</option>
                                <option value="<">أصغر من (&lt;)</option>
                                <option value="<=">أصغر أو يساوي (≤)</option>
                                <option value="=">يساوي (=)</option>
                                <option value="!=">لا يساوي (≠)</option>
                            </select>
                            <input type="number" id="bdRcpFilterVal2" class="form-control" placeholder="القيمة" min="0" step="any">
                        </div>
                    </div>
                </div>
                <hr class="my-2">
                <div class="d-flex gap-1 flex-wrap">
                    <button type="button" class="btn btn-primary" id="bdRcpSearchBtn">
                        <i class="fa fa-search"></i> استعلام
                    </button>
                    <button type="button" class="btn btn-cyan-light" id="bdRcpAdvClear">
                        <i class="fa fa-eraser"></i> تفريغ الحقول
                    </button>
                    <button type="button" class="btn btn-success" id="bdRcpExportBtn" title="تصدير Excel للنتائج المعروضة">
                        <i class="fa fa-file-excel-o"></i> تصدير Excel
                    </button>
                </div>
                <!-- inputs مخفية (للتوافق مع _bdRcpFilter القديم) -->
                <input type="hidden" id="bdRcpSearch" value="">
                <input type="hidden" id="bdRcpSearchClear" value="">
            </div>

            <!-- جدول المستفيدين (lazy build للأداء) -->
            <div class="table-responsive">
            <table class="table table-bordered table-sm rcp-tbl mb-0" id="bdRcpTable">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px">#</th>
                        <th>الموظف الأصلي</th>
                        <th>المقر</th>
                        <th style="width:90px">النوع</th>
                        <th>المستلِم</th>
                        <th>البنك / المحفظة</th>
                        <th>الفرع</th>
                        <th>الحساب / IBAN</th>
                        <th class="text-end" style="width:120px">المبلغ</th>
                    </tr>
                </thead>
                <tbody id="bdRcpBody">
                    <tr id="bdRcpLoadingRow"><td colspan="9" class="text-center py-4 text-muted">
                        <i class="fa fa-info-circle"></i> اضغط تبويب "المستفيدون" لعرض التفاصيل
                    </td></tr>
                </tbody>
                <tfoot>
                <tr style="background:#1e293b;color:#fff;font-weight:800">
                    <td colspan="8" class="text-end">الإجمالي</td>
                    <td class="text-end" id="bdRcpTotalCell" style="direction:ltr"><?= n_format($rcp_total_amt) ?></td>
                </tr>
                </tfoot>
            </table>
            </div>
            <div id="bdRcpFilterInfo" class="mt-2 text-muted text-center" style="font-size:.78rem;display:none"></div>
        </div><!-- /bd-tab-rcp -->

        </div><!-- /tab-content -->

        <!-- بيانات Tab 2 كـ JSON (lazy build) -->
        <script type="application/json" id="bdRcpData"><?= json_encode($rcp_clean, JSON_UNESCAPED_UNICODE) ?></script>

        <?php else: ?>
        <div class="alert alert-light text-center text-muted py-4">
            <i class="fa fa-inbox fa-2x d-block mb-2" style="opacity:.4"></i> لا توجد تفاصيل
        </div>
        <?php endif; ?>

    </div>
</div></div></div>

<!-- 🆕 Modal: مرفقات طلب صرف (داخل الدفعة) — متاح لكل حالات الدفعة -->
<div class="modal fade" id="prAttachModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:#0f172a;color:#fff">
        <h5 class="modal-title" style="font-size:1rem;font-weight:700">
          <i class="fa fa-paperclip me-1"></i> مرفقات الطلب <span id="prAttReqNo"></span>
          <span id="prAttCount" class="badge bg-warning text-dark ms-1" style="font-size:.7rem"></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <!-- نموذج الرفع -->
        <form id="prAttUploadForm" enctype="multipart/form-data" class="p-2 mb-3" style="background:#f0f9ff;border:1px solid #7dd3fc;border-radius:8px">
            <input type="hidden" name="identity" id="prAttIdentity">
            <input type="hidden" name="category" value="payment_req">
            <input type="hidden" name="upload_type" value="attachment">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <strong class="text-primary"><i class="fa fa-upload"></i> رفع مرفق جديد:</strong>
                <input type="file" name="file" class="form-control form-control-sm" style="width:auto;flex:1;min-width:200px" required>
                <button type="button" class="btn btn-success btn-sm" onclick="submitPrAttach()">
                    <i class="fa fa-cloud-upload"></i> رفع
                </button>
            </div>
            <div id="prAttUploadProgress" style="display:none;margin-top:6px">
                <div class="progress" style="height:6px"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div></div>
            </div>
            <small class="text-muted d-block mt-1" style="font-size:.7rem">
                المسموح: PDF / Word / Excel / صور / ZIP — حتى 150MB
            </small>
        </form>

        <!-- قائمة المرفقات -->
        <div id="prAttListBox">
            <div class="text-center text-muted py-4">
                <i class="fa fa-spinner fa-spin fa-2x"></i>
                <div style="margin-top:8px">جاري التحميل...</div>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
      </div>
    </div>
  </div>
</div>

<!-- 🆕 Modal: كشف توزيع موظف في الدفعة (الطلبات + الوجهات) -->
<div class="modal fade" id="empBreakdownModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header" style="background:#1e293b;color:#fff">
        <h5 class="modal-title" style="font-size:1rem;font-weight:700">
          <i class="fa fa-sitemap me-1"></i> كشف توزيع الموظف في الدفعة
          <span id="ebmEmpInfo" class="ms-2" style="font-weight:500;opacity:.85;font-size:.85rem"></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">

        <!-- ملخص بطاقات: إجمالي + توزيع -->
        <div id="ebmSummary" class="p-3" style="background:#f8fafc;border-bottom:2px solid #e2e8f0"></div>

        <!-- جدول الطلبات (المصدر) -->
        <div class="p-3 pt-2 pb-2">
            <h6 class="mb-2" style="font-size:.85rem;color:#1e40af">
                <i class="fa fa-arrow-left"></i> الطلبات المساهمة <small class="text-muted">(من أين جاء المبلغ)</small>
            </h6>
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead style="background:#eff6ff">
                        <tr>
                            <th style="width:30px">#</th>
                            <th>رقم الطلب</th>
                            <th>الشهر</th>
                            <th>نوع الطلب</th>
                            <th class="text-end" style="width:120px">المبلغ المعتمد</th>
                        </tr>
                    </thead>
                    <tbody id="ebmReqBody"></tbody>
                    <tfoot>
                        <tr style="background:#1e40af;color:#fff;font-weight:700">
                            <td colspan="4" class="text-end">إجمالي المبلغ المُستحَق</td>
                            <td class="text-end" id="ebmReqTotal" style="direction:ltr">0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- مؤشر التحقق -->
        <div id="ebmMatchBadge" class="text-center py-2" style="background:#f0fdf4">
            <i class="fa fa-arrow-down" style="font-size:1.2rem;color:#065f46"></i>
        </div>

        <!-- جدول حسابات الإيداع والتحويل -->
        <div class="p-3 pt-2 pb-3">
            <h6 class="mb-2" style="font-size:.85rem;color:#065f46">
                <i class="fa fa-arrow-right"></i> حسابات الإيداع والتحويل <small class="text-muted">— تفصيل المبالغ المُحوَّلة (بنوك / محافظ / مستفيدين)</small>
            </h6>
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead style="background:#ecfdf5">
                        <tr>
                            <th style="width:30px">#</th>
                            <th style="width:90px">النوع</th>
                            <th>جهة الإيداع (بنك / محفظة / مستفيد)</th>
                            <th>صاحب الحساب</th>
                            <th>رقم الحساب / IBAN / الجوال</th>
                            <th class="text-end" style="width:120px">المبلغ المُحوَّل</th>
                        </tr>
                    </thead>
                    <tbody id="ebmDestBody"></tbody>
                    <tfoot>
                        <tr style="background:#065f46;color:#fff;font-weight:700">
                            <td colspan="5" class="text-end">إجمالي المبلغ المُحوَّل</td>
                            <td class="text-end" id="ebmDestTotal" style="direction:ltr">0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

      </div>
      <div class="modal-footer" style="background:#f8fafc">
        <button type="button" id="ebmPrintBtn" class="btn btn-primary" onclick="empBreakdownPrint()" disabled>
            <i class="fa fa-print"></i> طباعة كشف الموظف (A4)
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
      </div>
    </div>
  </div>
</div>

<!-- 🆕 Modal: تفاصيل الفروقات بين دفعتين -->
<div class="modal fade" id="diffModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header" style="background:#075985;color:#fff">
        <h5 class="modal-title" style="font-size:1rem;font-weight:700">
          <i class="fa fa-exchange me-1"></i> تفاصيل الفروقات بين الدفعات
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <!-- اختيار الدفعة المرجعية -->
        <div class="d-flex align-items-center gap-2 mb-3 p-2" style="background:#f0f9ff;border:1px solid #7dd3fc;border-radius:8px">
            <strong style="font-size:.85rem">المقارنة:</strong>
            <span class="badge bg-warning text-dark"><?= htmlspecialchars($batch_no) ?> (الحالية)</span>
            <span class="text-muted">vs</span>
            <select id="diffPrvSelect" class="form-select form-select-sm" style="width:auto;min-width:200px">
                <?php foreach (($cmp_opts ?? []) as $o): $oid = (int)$o['BATCH_ID']; ?>
                <option value="<?= $oid ?>" <?= ($oid === ($cmp_cur_pid ?? 0)) ? 'selected' : '' ?>><?= htmlspecialchars($o['BATCH_NO']) ?> — <?= htmlspecialchars($o['ENTRY_DATE']) ?> (<?= n_format((int)$o['EMP_COUNT']) ?> موظف)</option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="btn btn-sm btn-primary" onclick="loadDiff()">
                <i class="fa fa-search"></i> فحص
            </button>
            <button type="button" class="btn btn-sm btn-outline-success ms-auto" id="diffExportBtn" onclick="exportDiff()" disabled>
                <i class="fa fa-file-excel-o"></i> تصدير Excel
            </button>
        </div>

        <!-- Loading -->
        <div id="diffLoading" style="display:none;text-align:center;padding:30px;color:#0284c7">
            <i class="fa fa-spinner fa-spin fa-2x"></i>
            <div style="margin-top:8px">جاري تحميل الفروقات...</div>
        </div>

        <!-- Body -->
        <div id="diffBody" style="display:none">
            <!-- بطاقات الإحصائيات -->
            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <div class="diff-stat" style="background:#dcfce7;border:1px solid #86efac;color:#15803d">
                        <div class="ds-lbl"><i class="fa fa-plus-circle"></i> موظفون جدد</div>
                        <div class="ds-val" id="dsCntNew">0</div>
                        <div class="ds-sub"><span id="dsAmtNew">0.00</span> ش.ج</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="diff-stat" style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c">
                        <div class="ds-lbl"><i class="fa fa-minus-circle"></i> موظفون خرجوا</div>
                        <div class="ds-val" id="dsCntLeft">0</div>
                        <div class="ds-sub"><span id="dsAmtLeft">0.00</span> ش.ج</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="diff-stat" style="background:#fef3c7;border:1px solid #fcd34d;color:#92400e">
                        <div class="ds-lbl"><i class="fa fa-pencil"></i> تغيّر مبلغهم</div>
                        <div class="ds-val" id="dsCntChanged">0</div>
                        <div class="ds-sub">صافي: <span id="dsAmtChangedNet">0.00</span></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="diff-stat" style="background:#f1f5f9;border:1px solid #cbd5e1;color:#475569">
                        <div class="ds-lbl"><i class="fa fa-check"></i> بدون تغيير</div>
                        <div class="ds-val" id="dsCntSame">0</div>
                        <div class="ds-sub">&nbsp;</div>
                    </div>
                </div>
            </div>

            <!-- زيادة/تخفيض -->
            <div class="d-flex gap-2 mb-3">
                <div class="flex-fill" style="background:#f0fdf4;border:1px solid #86efac;border-radius:6px;padding:6px 10px;text-align:center;font-size:.8rem">
                    <i class="fa fa-arrow-up text-success"></i> <strong>زيادات</strong>: <span id="dsAmtIncreased">0.00</span> ش.ج
                </div>
                <div class="flex-fill" style="background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;padding:6px 10px;text-align:center;font-size:.8rem">
                    <i class="fa fa-arrow-down text-danger"></i> <strong>تخفيضات</strong>: <span id="dsAmtDecreased">0.00</span> ش.ج
                </div>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs" id="diffTabs">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#diff-tab-changed" type="button">تعديلات <span class="badge bg-warning text-dark ms-1" id="diffTabBadgeChanged">0</span></button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#diff-tab-new" type="button">جدد <span class="badge bg-success ms-1" id="diffTabBadgeNew">0</span></button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#diff-tab-left" type="button">خرجوا <span class="badge bg-danger ms-1" id="diffTabBadgeLeft">0</span></button></li>
            </ul>
            <div class="tab-content" style="max-height:50vh;overflow-y:auto;border:1px solid #cbd5e1;border-top:0;padding:8px;border-radius:0 0 6px 6px">
                <div class="tab-pane fade show active" id="diff-tab-changed">
                    <table class="table table-sm table-bordered" style="font-size:.78rem">
                        <thead class="table-light"><tr>
                            <th style="width:30px">#</th><th style="width:80px">الرقم</th><th>الاسم</th><th style="width:130px">المقر</th>
                            <th class="text-end" style="width:110px">الحالي</th><th class="text-end" style="width:110px">السابق</th>
                            <th class="text-end" style="width:100px">الفرق</th><th class="text-end" style="width:80px">%</th>
                        </tr></thead>
                        <tbody id="diffTbodyChanged"></tbody>
                    </table>
                </div>
                <div class="tab-pane fade" id="diff-tab-new">
                    <table class="table table-sm table-bordered" style="font-size:.78rem">
                        <thead class="table-light"><tr>
                            <th style="width:30px">#</th><th style="width:80px">الرقم</th><th>الاسم</th><th style="width:130px">المقر</th>
                            <th class="text-end" style="width:110px">المبلغ</th>
                        </tr></thead>
                        <tbody id="diffTbodyNew"></tbody>
                    </table>
                </div>
                <div class="tab-pane fade" id="diff-tab-left">
                    <table class="table table-sm table-bordered" style="font-size:.78rem">
                        <thead class="table-light"><tr>
                            <th style="width:30px">#</th><th style="width:80px">الرقم</th><th>الاسم</th><th style="width:130px">المقر</th>
                            <th class="text-end" style="width:110px">المبلغ السابق</th>
                        </tr></thead>
                        <tbody id="diffTbodyLeft"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div id="diffEmpty" style="display:none;text-align:center;padding:30px;color:#94a3b8">
            <i class="fa fa-info-circle fa-2x"></i>
            <div style="margin-top:8px">اختر دفعة مرجعية ثم اضغط "فحص" لعرض الفروقات</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
      </div>
    </div>
  </div>
</div>

<!-- ═════════ Modal: إعادة توزيع موظف معيّن — فقط والدفعة محتسبة (st=0) ═════════ -->
<?php if ($st == 0): ?>
<div class="modal fade" id="redistModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:#f8fafc;border-bottom:2px solid #e2e8f0">
        <h5 class="modal-title" style="font-size:1rem;font-weight:700">
          <i class="fa fa-random text-primary me-1"></i> إعادة توزيع المبلغ
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="rdt_emp_no">

        <div class="mb-3" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:.6rem .9rem">
          <div style="font-size:.78rem;color:#1e40af;font-weight:600">الموظف:</div>
          <div id="rdt_emp_label" style="font-weight:700;font-size:.9rem"></div>
          <div style="font-size:.78rem;color:#475569;margin-top:.2rem">
            <i class="fa fa-money me-1"></i> المبلغ في هذه الدفعة: <b id="rdt_amount_label" style="color:#1e40af"></b>
          </div>
          <div style="font-size:.7rem;color:#9a3412;margin-top:.3rem">
            <i class="fa fa-info-circle"></i> سيُعاد توزيع المبلغ كاملاً على الحسابات المختارة. لا يمكن تنفيذ هذا بعد صرف الدفعة.
          </div>
        </div>

        <label class="fw-bold mb-2" style="font-size:.85rem">طريقة التوزيع:</label>
        <div class="rdt-options">
          <label class="rdt-opt">
            <input type="radio" name="rdt_mode" value="default" checked>
            <span class="rdt-opt-card">
              <i class="fa fa-cogs rdt-opt-icon" style="color:#64748b"></i>
              <div>
                <div class="rdt-opt-title">افتراضي</div>
                <div class="rdt-opt-desc">حسب توزيع الموظف الثابت في حساباته</div>
              </div>
            </span>
          </label>
          <label class="rdt-opt">
            <input type="radio" name="rdt_mode" value="bank">
            <span class="rdt-opt-card">
              <i class="fa fa-bank rdt-opt-icon" style="color:#1e40af"></i>
              <div>
                <div class="rdt-opt-title">بنك فقط</div>
                <div class="rdt-opt-desc">صرف كامل المبلغ على حسابات البنك (تجاهل المحافظ)</div>
              </div>
            </span>
          </label>
          <label class="rdt-opt">
            <input type="radio" name="rdt_mode" value="wallet">
            <span class="rdt-opt-card">
              <i class="fa fa-mobile rdt-opt-icon" style="color:#6d28d9"></i>
              <div>
                <div class="rdt-opt-title">محفظة فقط</div>
                <div class="rdt-opt-desc">صرف كامل المبلغ على المحافظ (تجاهل البنوك)</div>
              </div>
            </span>
          </label>
          <label class="rdt-opt">
            <input type="radio" name="rdt_mode" value="specific">
            <span class="rdt-opt-card">
              <i class="fa fa-bullseye rdt-opt-icon" style="color:#9a3412"></i>
              <div>
                <div class="rdt-opt-title">حساب محدد</div>
                <div class="rdt-opt-desc">100% للحساب المختار</div>
              </div>
            </span>
          </label>
        </div>

        <div id="rdt_specific_box" class="mt-3" style="display:none">
          <label class="fw-bold mb-1" style="font-size:.8rem">اختر الحساب:</label>
          <div id="rdt_acc_loading" class="text-center text-muted py-3" style="font-size:.8rem">
            <i class="fa fa-spinner fa-spin"></i> جاري التحميل...
          </div>
          <div id="rdt_acc_list" class="rdt-acc-list" style="display:none"></div>
          <div id="rdt_acc_empty" class="text-center text-muted py-3" style="font-size:.8rem;display:none">
            <i class="fa fa-info-circle"></i> لا يوجد حسابات نشطة
          </div>
        </div>
      </div>
      <div class="modal-footer" style="background:#f8fafc">
        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">إلغاء</button>
        <button type="button" class="btn btn-primary btn-sm" id="rdtSaveBtn"><i class="fa fa-save me-1"></i> حفظ التوزيع الجديد</button>
      </div>
    </div>
  </div>
</div>

<?php endif; ?>

<?php
$pay_url_js      = base_url("$MODULE_NAME/$TB_NAME/batch_pay_action");
$cancel_url_js   = base_url("$MODULE_NAME/$TB_NAME/batch_cancel_action");
$reverse_url_js  = base_url("$MODULE_NAME/$TB_NAME/batch_reverse_pay_action");
$refresh_url_js  = base_url("$MODULE_NAME/$TB_NAME/batch_refresh_split_action");
$redist_url_js   = base_url("$MODULE_NAME/$TB_NAME/batch_detail_redist_action");
$emp_acc_url     = base_url("$MODULE_NAME/$TB_NAME/batch_emp_accounts_json");
$emp_acc_simple  = base_url("$MODULE_NAME/$TB_NAME/emp_accounts_json");
$emp_brk_url     = base_url("$MODULE_NAME/$TB_NAME/emp_batch_breakdown_json");
$emp_brk_print   = base_url("$MODULE_NAME/$TB_NAME/emp_batch_breakdown_print");

$scripts = <<<SCRIPT
<script type="text/javascript">
function nf(n){ return parseFloat(n||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }

// 🆕 الموظف الحالي المعروض في الـ Modal (يُستخدم في الطباعة)
var _ebmCurrentEmp = null;

// 🆕 كشف توزيع موظف في الدفعة — يفتح Modal يبيّن:
//    (1) الطلبات المساهمة (من أين جاء المبلغ)
//    (2) الوجهات (وين راحت — بنك/محفظة/وريث)
function empBreakdownShow(empNo){
    _ebmCurrentEmp = empNo;
    var url = '{$emp_brk_url}?batch_id={$batch_id}&emp_no=' + empNo;
    var modalEl = document.getElementById('empBreakdownModal');
    if (!modalEl) { alert('Modal غير موجود'); return; }

    // إظهار المودال مع spinner أثناء التحميل
    \$('#ebmEmpInfo').text('— جاري التحميل...');
    \$('#ebmSummary').html('<div class="text-center py-4 text-muted"><i class="fa fa-spinner fa-spin fa-2x"></i></div>');
    \$('#ebmReqBody').html('<tr><td colspan="5" class="text-center text-muted py-3">جاري التحميل...</td></tr>');
    \$('#ebmDestBody').html('<tr><td colspan="6" class="text-center text-muted py-3">جاري التحميل...</td></tr>');
    \$('#ebmReqTotal').text('0.00');
    \$('#ebmDestTotal').text('0.00');
    \$('#ebmPrintBtn').prop('disabled', true);

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();

    \$.get(url)
    .done(function(resp){
        var j = (typeof resp === 'string') ? JSON.parse(resp) : resp;
        if (!j.ok) { _bdEbmError(j.msg || 'خطأ في الجلب'); return; }
        _bdEbmRender(j);
        \$('#ebmPrintBtn').prop('disabled', false);   // فعّل زر الطباعة بعد التحميل
    })
    .fail(function(){ _bdEbmError('فشل الاتصال بالخادم'); });
}

// 🆕 فتح صفحة الطباعة A4 للموظف الحالي في الـ Modal
function empBreakdownPrint(){
    if (!_ebmCurrentEmp) { return; }
    var url = '{$emp_brk_print}/{$batch_id}/' + _ebmCurrentEmp + '?auto=1';
    window.open(url, '_blank');
}

function _bdEbmError(msg){
    \$('#ebmEmpInfo').text('');
    \$('#ebmSummary').html('<div class="alert alert-danger m-3"><i class="fa fa-exclamation-circle"></i> ' + _bdEbmEsc(msg) + '</div>');
    \$('#ebmReqBody').html('<tr><td colspan="5" class="text-center text-danger py-3">—</td></tr>');
    \$('#ebmDestBody').html('<tr><td colspan="6" class="text-center text-danger py-3">—</td></tr>');
}

function _bdEbmEsc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function _bdEbmFmt(n){ return parseFloat(n||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }

function _bdEbmRender(j){
    // ─── عنوان: الموظف ───
    var idTxt = j.emp_id ? (' · هوية ' + j.emp_id) : '';
    var brTxt = j.emp_branch ? (' · المقر: ' + j.emp_branch) : '';
    \$('#ebmEmpInfo').text('— ' + j.emp_no + ' · ' + (j.emp_name || '') + idTxt + brTxt);

    // ─── بطاقات الملخص ───
    var t = j.totals || {};
    var totReq  = parseFloat(t.requests     || 0);
    var totDest = parseFloat(t.destinations || 0);
    var sumBank   = parseFloat(t.sum_bank   || 0);
    var sumWallet = parseFloat(t.sum_wallet || 0);
    var sumBenef  = parseFloat(t.sum_benef  || 0);
    var match = !!t.match;

    var cards =
        '<div class="row g-2">' +
            '<div class="col-md-3">' +
                '<div style="background:#fff;border:2px solid #1e40af;border-radius:8px;padding:.7rem;text-align:center">' +
                    '<div style="font-size:.7rem;color:#1e40af;font-weight:600">إجمالي المُستحَق (' + j.requests.length + ' طلب)</div>' +
                    '<div style="font-size:1.3rem;font-weight:800;color:#1e293b;direction:ltr">' + _bdEbmFmt(totReq) + '</div>' +
                '</div>' +
            '</div>' +
            '<div class="col-md-3">' +
                '<div style="background:#fff;border:2px solid #1e40af;border-radius:8px;padding:.7rem;text-align:center">' +
                    '<div style="font-size:.7rem;color:#1e40af;font-weight:600"><i class="fa fa-bank"></i> تحويلات بنكية</div>' +
                    '<div style="font-size:1.2rem;font-weight:700;color:#1e293b;direction:ltr">' + _bdEbmFmt(sumBank) + '</div>' +
                '</div>' +
            '</div>' +
            '<div class="col-md-3">' +
                '<div style="background:#fff;border:2px solid #6d28d9;border-radius:8px;padding:.7rem;text-align:center">' +
                    '<div style="font-size:.7rem;color:#6d28d9;font-weight:600"><i class="fa fa-mobile"></i> تحويلات محافظ</div>' +
                    '<div style="font-size:1.2rem;font-weight:700;color:#1e293b;direction:ltr">' + _bdEbmFmt(sumWallet) + '</div>' +
                '</div>' +
            '</div>' +
            '<div class="col-md-3">' +
                '<div style="background:#fff;border:2px solid #d97706;border-radius:8px;padding:.7rem;text-align:center">' +
                    '<div style="font-size:.7rem;color:#d97706;font-weight:600"><i class="fa fa-users"></i> إيداع مستفيدين / ورثة</div>' +
                    '<div style="font-size:1.2rem;font-weight:700;color:#1e293b;direction:ltr">' + _bdEbmFmt(sumBenef) + '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
    \$('#ebmSummary').html(cards);

    // ─── شارة التحقق ───
    if (match) {
        \$('#ebmMatchBadge').html('<i class="fa fa-check-circle" style="color:#065f46;font-size:1.2rem"></i> <span style="color:#065f46;font-weight:600;font-size:.85rem">المبالغ متطابقة ✓</span>');
        \$('#ebmMatchBadge').css('background', '#f0fdf4');
    } else {
        var diff = (totReq - totDest).toFixed(2);
        \$('#ebmMatchBadge').html('<i class="fa fa-exclamation-triangle" style="color:#991b1b;font-size:1.2rem"></i> <span style="color:#991b1b;font-weight:600;font-size:.85rem">فرق: ' + diff + ' — راجع البيانات</span>');
        \$('#ebmMatchBadge').css('background', '#fef2f2');
    }

    // ─── جدول الطلبات ───
    var reqHtml = '';
    if (!j.requests || j.requests.length === 0) {
        reqHtml = '<tr><td colspan="5" class="text-center text-muted py-3">لا توجد طلبات لهذا الموظف في الدفعة</td></tr>';
    } else {
        for (var i = 0; i < j.requests.length; i++) {
            var r = j.requests[i];
            reqHtml += '<tr>'
                    + '<td class="text-center text-muted">' + (i+1) + '</td>'
                    + '<td><b>' + _bdEbmEsc(r.req_no) + '</b></td>'
                    + '<td><small>' + _bdEbmEsc(r.month) + '</small></td>'
                    + '<td><span class="badge" style="background:#e0e7ff;color:#3730a3">' + _bdEbmEsc(r.type_name) + '</span></td>'
                    + '<td class="text-end fw-bold" style="color:#1e40af;direction:ltr">' + _bdEbmFmt(r.amount) + '</td>'
                    + '</tr>';
        }
    }
    \$('#ebmReqBody').html(reqHtml);
    \$('#ebmReqTotal').text(_bdEbmFmt(totReq));

    // ─── جدول الوجهات ───
    var destHtml = '';
    if (!j.destinations || j.destinations.length === 0) {
        destHtml = '<tr><td colspan="6" class="text-center text-muted py-3">لا توجد وجهات</td></tr>';
    } else {
        for (var k = 0; k < j.destinations.length; k++) {
            var d = j.destinations[k];
            var icon, bg, fg;
            if (d.category === 'WALLET')      { icon = 'fa-mobile'; bg = '#ede9fe'; fg = '#6d28d9'; }
            else if (d.category === 'BENEF')  { icon = 'fa-users';  bg = '#fef3c7'; fg = '#d97706'; }
            else                              { icon = 'fa-bank';   bg = '#dbeafe'; fg = '#1e40af'; }

            // شارة الدمج: لو هذه الوجهة تجمع أكثر من حركة، نوضّح للمحاسب
            var mergedBadge = '';
            if (parseInt(d.merged_count) > 1) {
                mergedBadge = ' <span class="badge ms-1" style="background:#dcfce7;color:#166534;font-size:.65rem"'
                            + ' title="هذه الوجهة تجمع ' + d.merged_count + ' حركات داخلية → البنك يستلمها كحركة واحدة">'
                            + '🔗 دمج ' + d.merged_count + '</span>';
            }
            destHtml += '<tr>'
                     + '<td class="text-center text-muted">' + (k+1) + '</td>'
                     + '<td><span class="badge" style="background:' + bg + ';color:' + fg + '"><i class="fa ' + icon + '"></i> ' + _bdEbmEsc(d.type_name) + '</span></td>'
                     + '<td><b>' + _bdEbmEsc(d.label) + '</b>' + mergedBadge
                     +     (d.sub_label ? '<br><small class="text-muted">' + _bdEbmEsc(d.sub_label) + '</small>' : '')
                     + '</td>'
                     + '<td>' + _bdEbmEsc(d.owner || '—') + '</td>'
                     + '<td style="font-family:monospace;direction:ltr;font-size:.75rem">' + _bdEbmEsc(d.account || '—') + '</td>'
                     + '<td class="text-end fw-bold" style="color:#065f46;direction:ltr">' + _bdEbmFmt(d.amount) + '</td>'
                     + '</tr>';
        }
    }
    \$('#ebmDestBody').html(destHtml);
    \$('#ebmDestTotal').text(_bdEbmFmt(totDest));
}

// 🆕 سؤال المحاسب: دمج أم لا؟ ثم تصدير
function exportWalletAsk(batchId, mbno, bankName){
    var baseUrl = '{$export_url}?batch_id=' + batchId + '&master_bank_no=' + mbno;
    var html =
        '<div style="text-align:right;direction:rtl;font-size:.88rem;line-height:1.6">' +
            '<div style="margin-bottom:.7rem">' +
                'هل تريد <b>دمج</b> الحركات المكررة لنفس <b>رقم الهوية</b> في الكشف المرحَّل إلى <b>' + _bdEsc(bankName) + '</b>؟' +
            '</div>' +
            '<div style="background:#fef9c3;border:1px solid #fde68a;border-radius:6px;padding:.5rem .7rem;color:#78350f;font-size:.78rem">' +
                '<i class="fa fa-info-circle"></i> الدمج يجمع المبالغ لكل موظف بحركة واحدة في الملف (مفيد لو نفس الشخص له عدة طلبات).' +
            '</div>' +
        '</div>';

    if (typeof Swal === 'undefined') {
        var ans = confirm('دمج الحركات المكررة لنفس الهوية قبل التصدير؟');
        window.open(baseUrl + (ans ? '&merge=1' : ''), '_blank');
        return;
    }

    Swal.fire({
        title: '📤 تصدير محفظة بال باي',
        html: html,
        icon: 'question',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: '<i class="fa fa-compress"></i> نعم، ادمج',
        denyButtonText: '<i class="fa fa-list"></i> لا، كما هو',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: '#10b981',
        denyButtonColor: '#3b82f6',
        cancelButtonColor: '#94a3b8',
        reverseButtons: true,
        focusConfirm: false,
        width: 500
    }).then(function(res){
        if (res.isConfirmed)      window.open(baseUrl + '&merge=1', '_blank');
        else if (res.isDenied)    window.open(baseUrl, '_blank');
    });
}

function _bdEsc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// 🆕 سؤال المحاسب عن شهر الصرف قبل تصدير الكشف البنكي
// providerType: 1=بنك (يسأل بس عن الشهر) | 2=محفظة (يسأل عن الدمج + الشهر)
function askMonthAndExport(batchId, mbno, providerType){
    var baseUrl = '{$export_url}?batch_id=' + batchId + '&master_bank_no=' + mbno;

    // الشهر الافتراضي = الشهر الحالي (YYYYMM)
    var now = new Date();
    var defaultMonth = now.getFullYear().toString() + String(now.getMonth()+1).padStart(2,'0');

    if (typeof Swal === 'undefined') {
        var m = prompt('أدخل شهر الصرف بصيغة YYYYMM (مثال: 202605):', defaultMonth);
        if (m && /^\\d{6}$/.test(m)) {
            window.open(baseUrl + '&the_month=' + m, '_blank');
        } else if (m === '') {
            window.open(baseUrl, '_blank');  // بدون شهر يدوي
        }
        return;
    }

    Swal.fire({
        title: '📅 شهر الصرف للكشف البنكي',
        html:
            '<div style="text-align:right;direction:rtl;font-size:.88rem;line-height:1.6">' +
                '<div style="margin-bottom:.7rem">' +
                    'أدخل شهر الصرف الذي سيظهر في الكشف البنكي بصيغة <b>YYYYMM</b>:' +
                '</div>' +
                '<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:.5rem .7rem;color:#1e40af;font-size:.78rem;margin-bottom:.5rem">' +
                    '<i class="fa fa-info-circle"></i> مثال: <code style="background:#fff;padding:1px 6px;border-radius:3px">202605</code> = مايو 2026' +
                '</div>' +
                '<div style="background:#fef9c3;border:1px solid #fde68a;border-radius:6px;padding:.5rem .7rem;color:#78350f;font-size:.74rem">' +
                    '💡 اتركه فارغاً لاستخدام تاريخ الدفعة الحالي تلقائياً' +
                '</div>' +
            '</div>',
        input: 'text',
        inputValue: defaultMonth,
        inputAttributes: { maxlength: 6, pattern: '\\\\d{6}', placeholder: 'مثال: 202605', style: 'direction:ltr;text-align:center;font-size:1.2rem;letter-spacing:3px' },
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-file-excel-o"></i> متابعة التصدير',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#94a3b8',
        reverseButtons: true,
        width: 500,
        preConfirm: function(value){
            value = String(value || '').trim();
            if (value !== '' && !/^\\d{6}$/.test(value)) {
                Swal.showValidationMessage('الصيغة غير صحيحة — يجب أن يكون 6 أرقام (YYYYMM)');
                return false;
            }
            return value;
        }
    }).then(function(res){
        if (!res.isConfirmed) return;
        var m = res.value || '';
        var url = baseUrl + (m !== '' ? '&the_month=' + m : '');

        // لو محفظة → سؤال إضافي عن الدمج
        if (providerType === 2) {
            askMergeAndOpen(url);
        } else {
            window.open(url, '_blank');
        }
    });
}

// سؤال الدمج للمحفظات (يُستدعى من askMonthAndExport)
function askMergeAndOpen(url){
    if (typeof Swal === 'undefined') {
        var ans = confirm('دمج الحركات المكررة لنفس الهوية قبل التصدير؟');
        window.open(url + (ans ? '&merge=1' : ''), '_blank');
        return;
    }
    Swal.fire({
        title: '🔁 تجميع الدفعات في كشف بال باي؟',
        html:
            '<div style="text-align:right;direction:rtl;font-size:.88rem;line-height:1.7">' +
                '<div style="margin-bottom:.7rem">' +
                    'هل تريد <b>تجميع الدفعات</b> أم إبقاء الكشف كما هو؟' +
                '</div>' +
                '<div style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:6px;padding:.55rem .75rem;color:#065f46;font-size:.78rem;margin-bottom:.45rem">' +
                    '<b><i class="fa fa-compress"></i> نعم، اجمع:</b> الصفوف اللي عندها نفس <b>رقم الجوال</b> و <b>رقم الهوية</b> تتجمع في صف واحد بمبلغ مجموع.' +
                '</div>' +
                '<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:.55rem .75rem;color:#1e40af;font-size:.78rem">' +
                    '<b><i class="fa fa-list"></i> لا، كما هو:</b> كل حركة بصف مستقل (مفيد للتدقيق التفصيلي).' +
                '</div>' +
            '</div>',
        icon: 'question',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: '<i class="fa fa-compress"></i> نعم، اجمع',
        denyButtonText: '<i class="fa fa-list"></i> لا، كما هو',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: '#10b981',
        denyButtonColor: '#3b82f6',
        cancelButtonColor: '#94a3b8',
        reverseButtons: true,
        width: 520
    }).then(function(res){
        if (res.isConfirmed)      window.open(url + '&merge=1', '_blank');
        else if (res.isDenied)    window.open(url, '_blank');
    });
}

function payBatch(){
    var nl = String.fromCharCode(10);
    var msg = 'تأكيد تنفيذ صرف الدفعة {$batch_no}' + nl + nl
            + '⚠️ سيتم:' + nl
            + '  • ترحيل كل المبالغ لجدول المستحقات' + nl
            + '  • تثبيت التوزيع الحالي (لا يمكن التعديل بعد التنفيذ)' + nl + nl
            + 'تأكد من مراجعة التوزيع قبل المتابعة.' + nl
            + 'هل أنت متأكد؟';
    if(!confirm(msg)) return;
    paymentBatchExecute({url:'{$pay_url_js}', batchId:{$batch_id}, buttons:'.bd-btn-pay',
        onPaid:function(){ location.reload(); }
    });
}

// إعادة احتساب التوزيع (قبل التنفيذ — بعد تعديل حسابات دون تغيير المبالغ)
function refreshSplit(){
    var nl = String.fromCharCode(10);
    var msg = 'إعادة احتساب توزيع الدفعة {$batch_no}؟' + nl + nl
            + '✅ يُعاد احتساب توزيع المبالغ على حسابات الموظفين بناءً على الإعدادات الحالية' + nl
            + '⚠️ المبلغ الإجمالي لكل موظف يبقى ثابتاً' + nl + nl
            + 'استخدم هذا الزر لو عدّلت الحسابات (IBAN، صاحب الحساب، split) دون تغيير المبالغ.' + nl + nl
            + 'متابعة؟';
    if(!confirm(msg)) return;
    get_data('{$refresh_url_js}', {batch_id: {$batch_id}}, function(resp){
        var j = (typeof resp === 'string') ? JSON.parse(resp) : resp;
        if(j.ok){
            var msg2 = j.msg;
            if (j.missing) msg2 += nl + '⚠️ موظفون بدون حسابات: ' + j.missing;
            success_msg('تم', msg2);
            setTimeout(function(){ location.reload(); }, 1200);
        } else { danger_msg('خطأ', j.msg); }
    }, 'json');
}

function cancelBatch(){
    var nl = String.fromCharCode(10);
    var msg = 'فك احتساب الدفعة {$batch_no}؟' + nl + nl
            + 'سيتم:' + nl
            + '• إرجاع الموظفين لحالة "معتمد"' + nl
            + '• وضع الدفعة بحالة "ملغاة" (تبقى في السجل للتسلسل)' + nl
            + '• لا يمكن استرجاع الدفعة بعد الإلغاء — احتسب من جديد لو احتجت';
    if(!confirm(msg)) return;
    get_data('{$cancel_url_js}', {batch_id: {$batch_id}}, function(resp){
        var j = (typeof resp === 'string') ? JSON.parse(resp) : resp;
        if(j.ok){ success_msg('تم', 'تم فك الاحتساب'); window.location = '{$history_url}'; }
        else { danger_msg('خطأ', j.msg); }
    }, 'json');
}

function reverseBatch(){
    if(!confirm('عكس صرف الدفعة {$batch_no}؟\\n\\nسيتم حذف سجلات المستحقات وإرجاع الموظفين.\\nهل أنت متأكد؟')) return;
    if(!confirm('تأكيد نهائي — متابعة؟')) return;
    get_data('{$reverse_url_js}', {batch_id: {$batch_id}}, function(resp){
        var j = (typeof resp === 'string') ? JSON.parse(resp) : resp;
        if(j.ok){ success_msg('تم', j.msg); location.reload(); }
        else { danger_msg('خطأ', j.msg); }
    }, 'json');
}

function printReport(){
    // افتح تقرير الطباعة الشامل في تبويب جديد — يحوي زر طباعة + إغلاق
    window.open('{$print_url}/{$batch_id}', '_blank');
}

function printCompareReport(){
    // افتح تقرير المقارنات (KPIs + توزيع الوجهة + المستثنون + مقارنة بالسابقة) في تبويب جديد
    window.open('{$compare_url}/{$batch_id}', '_blank');
}

// ════════════════════════════════════════════════════════════
// 🆕 Trend / Diff — مقارنة الدفعات
// ════════════════════════════════════════════════════════════
function toggleTrendTable(){
    var box = document.getElementById('trendTableBox');
    var lbl = document.getElementById('trendToggleLbl');
    if (!box) return;
    if (box.style.display === 'none') { box.style.display = ''; if (lbl) lbl.textContent = 'إخفاء الجدول'; }
    else                              { box.style.display = 'none'; if (lbl) lbl.textContent = 'عرض الجدول'; }
}

function cmpReload(prev){
    var u = new URL(window.location.href);
    u.searchParams.set('prev', prev);
    window.location.href = u.toString();
}

function openDiffModal(){
    \$('#diffEmpty').show();
    \$('#diffBody').hide();
    \$('#diffLoading').hide();
    \$('#diffExportBtn').prop('disabled', true);
    var modal = new bootstrap.Modal(document.getElementById('diffModal'));
    modal.show();
}

function _diffNF(n){
    n = Math.round((+n || 0) * 100) / 100;
    return n.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
}

function loadDiff(){
    var prv = parseInt(\$('#diffPrvSelect').val(), 10);
    if (!prv) { return; }
    \$('#diffEmpty').hide();
    \$('#diffBody').hide();
    \$('#diffLoading').show();
    \$('#diffExportBtn').prop('disabled', true);

    get_data('{$diff_url}', { cur_batch_id: {$batch_id}, prv_batch_id: prv }, function(j){
        \$('#diffLoading').hide();
        if (!j || !j.ok) {
            danger_msg('خطأ', (j && j.msg) ? j.msg : 'تعذّر تحميل الفروقات');
            \$('#diffEmpty').show();
            return;
        }
        renderDiff(j.summary || {}, j.rows || []);
        \$('#diffBody').show();
        \$('#diffExportBtn').prop('disabled', false).data('prv', prv);
    });
}

function renderDiff(sm, rows){
    \$('#dsCntNew').text(parseInt(sm.CNT_NEW || 0, 10).toLocaleString('en-US'));
    \$('#dsCntLeft').text(parseInt(sm.CNT_LEFT || 0, 10).toLocaleString('en-US'));
    \$('#dsCntChanged').text(parseInt(sm.CNT_CHANGED || 0, 10).toLocaleString('en-US'));
    \$('#dsCntSame').text(parseInt(sm.CNT_SAME || 0, 10).toLocaleString('en-US'));
    \$('#dsAmtNew').text(_diffNF(sm.AMT_NEW || 0));
    \$('#dsAmtLeft').text(_diffNF(sm.AMT_LEFT || 0));
    \$('#dsAmtChangedNet').text(_diffNF(sm.AMT_CHANGED_NET || 0));
    \$('#dsAmtIncreased').text(_diffNF(sm.AMT_INCREASED || 0));
    \$('#dsAmtDecreased').text(_diffNF(sm.AMT_DECREASED || 0));

    \$('#diffTabBadgeNew').text(sm.CNT_NEW || 0);
    \$('#diffTabBadgeLeft').text(sm.CNT_LEFT || 0);
    \$('#diffTabBadgeChanged').text(sm.CNT_CHANGED || 0);

    var tNew = '', tLeft = '', tChg = '';
    var iN = 0, iL = 0, iC = 0;
    rows.forEach(function(r){
        var ct = r.CHANGE_TYPE;
        var name = r.EMP_NAME || '';
        var br   = r.BRANCH_NAME || '';
        if (ct === 'NEW') {
            iN++;
            tNew += '<tr><td>' + iN + '</td><td>' + r.EMP_NO + '</td><td>' + name + '</td><td>' + br + '</td>'
                  + '<td class="text-end"><strong>' + _diffNF(r.CURRENT_AMOUNT) + '</strong></td></tr>';
        } else if (ct === 'LEFT') {
            iL++;
            tLeft += '<tr><td>' + iL + '</td><td>' + r.EMP_NO + '</td><td>' + name + '</td><td>' + br + '</td>'
                  + '<td class="text-end"><strong>' + _diffNF(r.PREVIOUS_AMOUNT) + '</strong></td></tr>';
        } else if (ct === 'CHANGED') {
            iC++;
            var diff = parseFloat(r.DIFF) || 0;
            var pct  = r.DIFF_PCT;
            var clr  = diff > 0 ? '#15803d' : (diff < 0 ? '#b91c1c' : '#64748b');
            var arr  = diff > 0 ? '⬆' : (diff < 0 ? '⬇' : '─');
            tChg += '<tr><td>' + iC + '</td><td>' + r.EMP_NO + '</td><td>' + name + '</td><td>' + br + '</td>'
                  + '<td class="text-end">' + _diffNF(r.CURRENT_AMOUNT) + '</td>'
                  + '<td class="text-end text-muted">' + _diffNF(r.PREVIOUS_AMOUNT) + '</td>'
                  + '<td class="text-end" style="color:' + clr + ';font-weight:700">' + arr + ' ' + (diff >= 0 ? '+' : '') + _diffNF(diff) + '</td>'
                  + '<td class="text-end" style="color:' + clr + '">' + (pct !== null && pct !== undefined ? pct + '%' : '—') + '</td></tr>';
        }
    });
    \$('#diffTbodyNew').html(tNew     || '<tr><td colspan="5" class="text-center text-muted py-3">— لا يوجد —</td></tr>');
    \$('#diffTbodyLeft').html(tLeft   || '<tr><td colspan="5" class="text-center text-muted py-3">— لا يوجد —</td></tr>');
    \$('#diffTbodyChanged').html(tChg || '<tr><td colspan="8" class="text-center text-muted py-3">— لا يوجد —</td></tr>');
}

function exportDiff(){
    var prv = \$('#diffExportBtn').data('prv');
    if (!prv) return;
    window.open('{$diff_export_url}?cur_batch_id={$batch_id}&prv_batch_id=' + prv, '_blank');
}

// ════════════ إعادة توزيع موظف معيّن ════════════
var _redistUrl    = "{$redist_url_js}";
var _empAccSimple = "{$emp_acc_simple}";
var _rdtAccCache  = {};
var _rdtSelectedAcc = null;

\$(document).on('click', '.btn-redist', function(e){
    e.stopPropagation();
    var \$b = \$(this);
    var emp = \$b.data('emp');
    var name = \$b.data('emp-name') || '';
    var amt = parseFloat(\$b.data('amount')) || 0;
    var pt = \$b.data('pt');
    var acc = \$b.data('acc');

    \$('#rdt_emp_no').val(emp);
    \$('#rdt_emp_label').text(emp + ' — ' + name);
    \$('#rdt_amount_label').text(nf(amt));
    _rdtSelectedAcc = acc || null;

    var mode = 'default';
    if (acc)            mode = 'specific';
    else if (pt == 1)   mode = 'bank';
    else if (pt == 2)   mode = 'wallet';
    \$('input[name="rdt_mode"][value="' + mode + '"]').prop('checked', true).trigger('change');

    var modal = new bootstrap.Modal(document.getElementById('redistModal'));
    modal.show();
});

\$(document).on('change', 'input[name="rdt_mode"]', function(){
    var mode = \$(this).val();
    if (mode === 'specific') {
        \$('#rdt_specific_box').show();
        _rdtLoadAccs();
    } else {
        \$('#rdt_specific_box').hide();
    }
});

function _rdtLoadAccs(){
    var emp = \$('#rdt_emp_no').val();
    if (!emp) return;
    if (_rdtAccCache[emp]) { _rdtRenderAccs(_rdtAccCache[emp]); return; }
    \$('#rdt_acc_loading').show(); \$('#rdt_acc_list').hide(); \$('#rdt_acc_empty').hide();
    get_data(_empAccSimple, {emp_no: emp}, function(resp){
        try {
            var j = (typeof resp === 'string') ? JSON.parse(resp) : resp;
            var rows = (j && j.data) ? j.data : [];
            _rdtAccCache[emp] = rows;
            _rdtRenderAccs(rows);
        } catch(e){
            \$('#rdt_acc_loading').hide();
            \$('#rdt_acc_empty').show().text('خطأ: ' + e.message);
        }
    }, 'json');
}

function _rdtRenderAccs(rows){
    \$('#rdt_acc_loading').hide();
    if (!rows || rows.length === 0) { \$('#rdt_acc_empty').show(); \$('#rdt_acc_list').hide(); return; }
    var html = '';
    rows.forEach(function(a){
        var isWallet = parseInt(a.PROVIDER_TYPE) === 2;
        var isDef    = parseInt(a.IS_DEFAULT) === 1;
        var icon     = isWallet ? 'fa-mobile' : 'fa-bank';
        var iconCol  = isWallet ? '#6d28d9' : '#1e40af';
        var accNum   = a.ACCOUNT_NO || a.WALLET_NUMBER || '—';
        var sel = (_rdtSelectedAcc && parseInt(_rdtSelectedAcc) === parseInt(a.ACC_ID)) ? ' selected' : '';
        html += '<div class="rdt-acc-item' + sel + '" data-acc="' + a.ACC_ID + '">';
        html += '<i class="fa ' + icon + ' ai-icon" style="color:' + iconCol + '"></i>';
        html += '<div class="ai-info">';
        html += '<div class="ai-prov">' + escHtml(a.PROVIDER_NAME || '') +
                (isDef ? ' <span class="ai-tag">⭐ افتراضي</span>' : '') + '</div>';
        html += '<div class="ai-line">#' + escHtml(accNum) +
                (a.BANK_BRANCH_NAME ? ' — ' + escHtml(a.BANK_BRANCH_NAME) : '') + '</div>';
        if (a.OWNER_NAME) {
            html += '<div class="ai-owner"><i class="fa fa-user-o"></i> ' + escHtml(a.OWNER_NAME) +
                    (a.REL_NAME ? ' (' + escHtml(a.REL_NAME) + ')' : '') + '</div>';
        }
        html += '</div></div>';
    });
    \$('#rdt_acc_list').html(html).show();
    \$('#rdt_acc_empty').hide();
}

\$(document).on('click', '.rdt-acc-item', function(){
    \$('.rdt-acc-item').removeClass('selected');
    \$(this).addClass('selected');
    _rdtSelectedAcc = \$(this).data('acc');
});

\$(document).on('click', '#rdtSaveBtn', function(){
    var emp = \$('#rdt_emp_no').val();
    if (!emp) return;
    var mode = \$('input[name="rdt_mode"]:checked').val();
    var pt = '', acc = '';
    if (mode === 'bank')        pt = 1;
    else if (mode === 'wallet') pt = 2;
    else if (mode === 'specific') {
        if (!_rdtSelectedAcc) { danger_msg('خطأ','اختر حساب من القائمة أولاً'); return; }
        acc = _rdtSelectedAcc;
    }

    get_data(_redistUrl, {batch_id: {$batch_id}, emp_no: emp, provider_type: pt, acc_id: acc}, function(resp){
        try {
            var j = (typeof resp === 'string') ? JSON.parse(resp) : resp;
            if (j.ok) {
                success_msg('تم', j.msg || 'تم إعادة التوزيع');
                bootstrap.Modal.getInstance(document.getElementById('redistModal')).hide();
                setTimeout(function(){ location.reload(); }, 800);
            } else {
                danger_msg('خطأ', j.msg);
            }
        } catch(e){ danger_msg('خطأ', e.message); }
    }, 'json');
});

// ════════════ توسيع صف الموظف لعرض توزيع الحسابات ════════════
var _empAccUrl = "{$emp_acc_url}";
var _accCache  = {};
var _batchId   = {$batch_id};

function escHtml(s){
    return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    });
}

function _splitLabel(t, v){
    t = parseInt(t) || 3;
    if(t === 1) return 'نسبة ' + (parseFloat(v)||0) + '%';
    if(t === 2) return 'مبلغ ثابت ' + nf(v);
    return 'كامل الباقي';
}

function _renderAccCards(rows){
    if(!rows || rows.length === 0){
        return '<div class="text-muted text-center py-2" style="font-size:.78rem">لا توجد حسابات للموظف في هذا النظام</div>';
    }
    var html = '<div class="acc-mini">';
    rows.forEach(function(a){
        var isWallet = parseInt(a.PROVIDER_TYPE) === 2;
        var isInact  = parseInt(a.IS_ACTIVE) !== 1;
        var isDef    = parseInt(a.IS_DEFAULT) === 1;
        var amt      = parseFloat(a.BATCH_AMOUNT) || 0;

        var cls = 'acc-card';
        if(isInact) cls += ' is-inactive';
        if(isDef)   cls += ' is-default';

        html += '<div class="' + cls + '">';
        html += '<div class="ac-head">';
        html += '<span class="ac-prov ' + (isWallet ? 'wallet' : 'bank') + '"><i class="fa fa-' +
                (isWallet ? 'mobile' : 'bank') + '"></i>' + escHtml(a.PROVIDER_NAME || '');
        if(isDef)   html += ' <span class="b-tag b-ok">⭐ افتراضي</span>';
        if(isInact) html += ' <span class="b-tag b-inact">موقوف</span>';
        html += '</span>';
        html += '<span class="ac-amt' + (amt <= 0 ? ' zero' : '') + '">' + nf(amt) + '</span>';
        html += '</div>';

        var accNum = a.ACCOUNT_DISP || a.ACCOUNT_NO || a.WALLET_NUMBER || '—';
        html += '<div class="ac-line"><i class="fa fa-hashtag"></i> ' + escHtml(accNum);
        if(a.BANK_BRANCH_NAME) html += ' — ' + escHtml(a.BANK_BRANCH_NAME);
        html += '</div>';

        if(a.IBAN && !isWallet){
            html += '<div class="ac-line">' + escHtml(a.IBAN) + '</div>';
        }

        if(a.OWNER_NAME){
            var ownerLine = a.OWNER_NAME;
            if(a.REL_NAME) ownerLine += ' (' + escHtml(a.REL_NAME) + ')';
            if(a.OWNER_ID_NO) ownerLine += ' — هوية ' + escHtml(a.OWNER_ID_NO);
            html += '<div class="ac-owner"><i class="fa fa-user-o"></i> ' + ownerLine + '</div>';
        }

        html += '<div class="ac-split">التوزيع: <b>' + _splitLabel(a.SPLIT_TYPE, a.SPLIT_VALUE) +
                '</b> · ترتيب: <b>' + (a.SPLIT_ORDER || 0) + '</b>';
        if(amt <= 0 && !isInact){
            html += ' · <span style="color:#dc2626;font-weight:600">⚠ لم يستلم في هذه الدفعة</span>';
        }
        html += '</div>';

        html += '</div>'; // .acc-card
    });
    html += '</div>'; // .acc-mini
    return html;
}

// ════════════ طي/فتح فرع البنك (داخل bank-section) ════════════
\$(document).on('click', '[data-toggle-branch]', function(e){
    var \$h = \$(this);
    var \$blk = \$h.closest('.branch-block');
    \$blk.toggleClass('is-collapsed');
    \$h.toggleClass('collapsed');
});

// اطوي / افتح كل فروع بنك
\$(document).on('click', '.bank-tools .btn-mini', function(e){
    e.stopPropagation();
    var act = \$(this).data('act');
    var \$section = \$(this).closest('.bank-section');
    if (act === 'collapse-branches') {
        \$section.find('.branch-block').addClass('is-collapsed');
        \$section.find('.branch-header').addClass('collapsed');
    } else if (act === 'expand-branches') {
        \$section.find('.branch-block').removeClass('is-collapsed');
        \$section.find('.branch-header').removeClass('collapsed');
    }
});

\$(document).on('click', '.btn-expand', function(e){
    e.stopPropagation();
    var \$btn = \$(this);
    var \$row = \$btn.closest('tr.emp-row');
    var emp  = \$row.data('emp');
    var \$next = \$row.next('.emp-detail-row');

    // toggle
    if(\$next.length){
        \$next.toggle();
        \$btn.toggleClass('expanded');
        \$btn.find('i').toggleClass('fa-plus-square-o fa-minus-square-o');
        return;
    }

    var colspan = \$row.find('td').length;
    var \$detailRow = \$('<tr class="emp-detail-row"><td colspan="' + colspan +
        '"><div class="text-muted text-center py-2"><i class="fa fa-spinner fa-spin"></i> جاري التحميل...</div></td></tr>');
    \$row.after(\$detailRow);
    \$btn.addClass('expanded');
    \$btn.find('i').removeClass('fa-plus-square-o').addClass('fa-minus-square-o');

    if(_accCache[emp]){
        \$detailRow.find('td').html(_renderAccCards(_accCache[emp]));
        return;
    }

    get_data(_empAccUrl, { batch_id: _batchId, emp_no: emp }, function(resp){
        var j = (typeof resp === 'string') ? JSON.parse(resp) : resp;
        var rows = (j && j.data) ? j.data : [];
        _accCache[emp] = rows;
        \$detailRow.find('td').html(_renderAccCards(rows));
    }, 'json');
});

// ═══════════════════════════════════════════════════════════
// Tab 2 (المستفيدون) — Lazy Build + Search + Filter + Export
// ═══════════════════════════════════════════════════════════
var _bdRcpBuilt = false;
var _bdRcpData = null;
var _bdRcpSearchTimer;

function _bdRcpLoadData(){
    if (_bdRcpData !== null) return _bdRcpData;
    var el = document.getElementById('bdRcpData');
    if (!el) return [];
    try { _bdRcpData = JSON.parse(el.textContent || el.innerText || '[]'); }
    catch(e) { _bdRcpData = []; }
    return _bdRcpData;
}

function _bdRcpFmt(n){
    return Number(n||0).toLocaleString('en-US',{minimumFractionDigits:2, maximumFractionDigits:2});
}

function _bdRcpEsc(s){
    if (s == null) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function _bdRcpBuild(){
    if (_bdRcpBuilt) return;
    var data = _bdRcpLoadData();
    var \$body = \$('#bdRcpBody');
    if (!data || data.length === 0) {
        \$body.html('<tr><td colspan="9" class="text-center py-4 text-muted">لا يوجد حركات للعرض</td></tr>');
        _bdRcpBuilt = true;
        return;
    }
    if (data.length > 200) {
        \$body.html('<tr><td colspan="9" class="text-center py-4 text-muted"><i class="fa fa-spinner fa-spin"></i> جاري بناء ' + data.length + ' حركة...</td></tr>');
    }
    setTimeout(function(){
        var html = '';
        for (var i = 0; i < data.length; i++) {
            var r = data[i];
            var idx = i + 1;
            var isWallet = r.provider_type === 2;
            var search = ((r.emp_no||'') + ' ' + (r.emp_name||'') + ' ' + (r.recipient||'') + ' ' +
                          (r.rel_label||'') + ' ' + (r.master_bank||'') + ' ' + (r.prov_branch||'') + ' ' +
                          (r.account_no||'') + ' ' + (r.iban||'')).toLowerCase();
            var typeIcon, typeText;
            if (r.type === 'self')      { typeIcon = 'user';    typeText = 'الموظف'; }
            else if (r.type === 'benef'){ typeIcon = 'users';   typeText = 'وريث'; }
            else                        { typeIcon = 'id-card'; typeText = 'صاحب حساب'; }

            // 🆕 data attrs إضافية للفلاتر التفصيلية
            var bankSlug = String(r.master_bank || '').toLowerCase();
            var branchSlug = String(r.prov_branch || '').toLowerCase();
            var empNameLower = String(r.emp_name || '').toLowerCase();
            html += '<tr class="rcp-row"'
                  + ' data-search="' + _bdRcpEsc(search) + '"'
                  + ' data-type="' + r.type + '"'
                  + ' data-emp="' + r.emp_no + '"'
                  + ' data-emp-name="' + _bdRcpEsc(empNameLower) + '"'
                  + ' data-bank="' + _bdRcpEsc(bankSlug) + '"'
                  + ' data-branch="' + _bdRcpEsc(branchSlug) + '"'
                  + ' data-amount="' + (parseFloat(r.amount) || 0) + '">';
            html += '<td class="text-center text-muted">' + idx + '</td>';
            html += '<td>'
                  + '<a href="javascript:void(0)" onclick="empBreakdownShow(' + r.emp_no + ')" class="fw-bold" '
                  + '   title="عرض تفاصيل توزيع الموظف في الدفعة"'
                  + '   style="text-decoration:none;color:#1e40af">'
                  + '   ' + r.emp_no + ' <i class="fa fa-search-plus" style="font-size:.65rem;opacity:.6"></i>'
                  + '</a>'
                  + '<br><small class="text-muted">' + _bdRcpEsc(r.emp_name) + '</small></td>';
            html += '<td style="font-size:.75rem">' + _bdRcpEsc(r.emp_branch || '—') + '</td>';
            html += '<td><span class="rcp-type" style="background:' + r.type_bg + ';color:' + r.type_color + '"><i class="fa fa-' + typeIcon + '"></i> ' + typeText + '</span></td>';
            html += '<td><b>' + _bdRcpEsc(r.recipient) + '</b>';
            if (r.type !== 'self') html += '<br><small class="text-muted">' + _bdRcpEsc(r.rel_label) + '</small>';
            html += '</td>';
            html += '<td><i class="fa fa-' + (isWallet ? 'mobile' : 'bank') + '" style="color:' + (isWallet ? '#6d28d9' : '#1e40af') + '"></i> ' + _bdRcpEsc(r.master_bank) + '</td>';
            // الفرع: للبنوك فقط — المحفظات ما إلها فروع، نعرض شُرَط
            html += '<td style="font-size:.75rem">' + (isWallet ? '<span class="text-muted">—</span>' : _bdRcpEsc(r.prov_branch || '—')) + '</td>';
            html += '<td style="font-family:monospace;direction:ltr;font-size:.74rem">';
            if (r.account_no) html += '<div>#' + _bdRcpEsc(r.account_no) + '</div>';
            if (r.iban)       html += '<div title="' + _bdRcpEsc(r.iban) + '" class="text-muted">' + r.iban_short + '</div>';
            html += '</td>';
            html += '<td class="text-end fw-bold" style="color:#059669;direction:ltr">' + _bdRcpFmt(r.amount) + '</td>';
            html += '</tr>';
        }
        \$body.html(html);
        _bdRcpBuilt = true;
    }, 30);
}

// 🆕 helper: يطبّق مقارنة amount حسب operator
function _bdRcpAmtMatch(amount, op, val){
    if (!op || val === null || isNaN(val)) return true;   // فلتر معطّل
    switch(op){
        case '>':  return amount >  val;
        case '>=': return amount >= val;
        case '<':  return amount <  val;
        case '<=': return amount <= val;
        case '=':  return amount === val;
        case '!=': return amount !== val;
    }
    return true;
}

function _bdRcpFilter(){
    var q          = (\$('#bdRcpSearch').val() || '').trim().toLowerCase();
    var typeFilter = \$('#bdRcpFilterType').val() || '';
    // 🆕 فلاتر تفصيلية
    var fEmpNo   = (\$('#bdRcpFilterEmpNo').val()   || '').trim();
    var fEmpName = (\$('#bdRcpFilterEmpName').val() || '').trim().toLowerCase();
    var fBank    = (\$('#bdRcpFilterBank').val()    || '').toLowerCase();
    var fBranch  = (\$('#bdRcpFilterBranch').val()  || '').toLowerCase();
    // 🆕 شرطين مرنين للمبلغ (operator + value)
    var fOp1  = \$('#bdRcpFilterOp1').val()  || '';
    var fVal1 = \$('#bdRcpFilterVal1').val(); fVal1 = (fVal1 === '' ? null : parseFloat(fVal1));
    var fOp2  = \$('#bdRcpFilterOp2').val()  || '';
    var fVal2 = \$('#bdRcpFilterVal2').val(); fVal2 = (fVal2 === '' ? null : parseFloat(fVal2));

    var \$rows = \$('#bdRcpBody tr.rcp-row');
    var visible = 0; var totalAmt = 0;
    \$rows.each(function(){
        var \$r = \$(this);
        var search   = \$r.attr('data-search')   || '';
        var type     = \$r.attr('data-type')     || '';
        var empNo    = \$r.attr('data-emp')      || '';
        var empName  = \$r.attr('data-emp-name') || '';
        var bank     = \$r.attr('data-bank')     || '';
        var branch   = \$r.attr('data-branch')   || '';
        var amount   = parseFloat(\$r.attr('data-amount')) || 0;

        var ok = true;
        if (q && search.indexOf(q) === -1) ok = false;
        if (ok && typeFilter && type !== typeFilter) ok = false;
        if (ok && fEmpNo && empNo.indexOf(fEmpNo) === -1) ok = false;
        if (ok && fEmpName && empName.indexOf(fEmpName) === -1) ok = false;
        if (ok && fBank && bank !== fBank) ok = false;
        if (ok && fBranch && branch !== fBranch) ok = false;
        // 🆕 شرطين مبلغ
        if (ok && fOp1 && fVal1 !== null && !_bdRcpAmtMatch(amount, fOp1, fVal1)) ok = false;
        if (ok && fOp2 && fVal2 !== null && !_bdRcpAmtMatch(amount, fOp2, fVal2)) ok = false;

        if (ok) {
            \$r.removeClass('row-hidden');
            visible++;
            totalAmt += amount;
        } else {
            \$r.addClass('row-hidden');
        }
    });
    var idx = 0;
    \$rows.not('.row-hidden').each(function(){ idx++; \$(this).find('td:first').text(idx); });
    \$('#bdRcpTotalCell').text(_bdRcpFmt(totalAmt));

    var hasAmt1 = (fOp1 && fVal1 !== null);
    var hasAmt2 = (fOp2 && fVal2 !== null);
    var hasAnyFilter = q || typeFilter || fEmpNo || fEmpName || fBank || fBranch || hasAmt1 || hasAmt2;
    if (hasAnyFilter) {
        var parts = [];
        if (q)        parts.push('بحث: <b>"' + _bdRcpEsc(q) + '"</b>');
        if (fEmpNo)   parts.push('موظف: <b>' + _bdRcpEsc(fEmpNo) + '</b>');
        if (fEmpName) parts.push('اسم: <b>' + _bdRcpEsc(fEmpName) + '</b>');
        if (fBank)    parts.push('بنك: <b>' + _bdRcpEsc(fBank) + '</b>');
        if (fBranch)  parts.push('فرع: <b>' + _bdRcpEsc(fBranch) + '</b>');
        if (hasAmt1)  parts.push('المبلغ ' + fOp1 + ' <b>' + _bdRcpFmt(fVal1) + '</b>');
        if (hasAmt2)  parts.push('المبلغ ' + fOp2 + ' <b>' + _bdRcpFmt(fVal2) + '</b>');
        \$('#bdRcpFilterInfo').show().html(
            '<i class="fa fa-filter"></i> عرض <b>' + visible + '</b> من <b>' + \$rows.length + '</b> حركة' +
            (parts.length ? ' — ' + parts.join(' · ') : '') +
            ' — مجموع المعروض: <b>' + _bdRcpFmt(totalAmt) + '</b>'
        );
    } else {
        \$('#bdRcpFilterInfo').hide();
    }
}

// 🆕 ملء dropdowns البنك/الفرع من البيانات المعروضة (تلقائياً بعد البناء)
function _bdRcpPopulateFilterDropdowns(){
    var banks = {}, branches = {};
    \$('#bdRcpBody tr.rcp-row').each(function(){
        var b = \$(this).attr('data-bank') || '';
        var br = \$(this).attr('data-branch') || '';
        if (b) banks[b] = true;
        if (br && br !== '—') branches[br] = true;
    });
    var \$bSel = \$('#bdRcpFilterBank');
    var \$brSel = \$('#bdRcpFilterBranch');
    \$bSel.find('option:not(:first)').remove();
    \$brSel.find('option:not(:first)').remove();
    Object.keys(banks).sort().forEach(function(b){
        \$bSel.append('<option value="' + _bdRcpEsc(b) + '">' + _bdRcpEsc(b) + '</option>');
    });
    Object.keys(branches).sort().forEach(function(br){
        \$brSel.append('<option value="' + _bdRcpEsc(br) + '">' + _bdRcpEsc(br) + '</option>');
    });
}

\$(document).on('shown.bs.tab', '#bd-tab-rcp-btn', function(){
    if (!_bdRcpBuilt) {
        _bdRcpBuild();
        setTimeout(_bdRcpPopulateFilterDropdowns, 100);
    }
});
// 🆕 الفلتر يطبّق فقط عند ضغط زر "استعلام" (مثل شاشات index)
\$(document).on('click', '#bdRcpSearchBtn', _bdRcpFilter);

// Enter داخل أي input من الفلاتر = استعلام (UX)
\$(document).on('keydown', '#bdRcpFilterEmpNo, #bdRcpFilterEmpName, #bdRcpFilterVal1, #bdRcpFilterVal2', function(e){
    if (e.which === 13 || e.keyCode === 13) { e.preventDefault(); _bdRcpFilter(); }
});

// 🆕 تفريغ كل الفلاتر + إعادة عرض الكل
\$(document).on('click', '#bdRcpAdvClear', function(){
    \$('#bdRcpFilterEmpNo, #bdRcpFilterEmpName, #bdRcpFilterVal1, #bdRcpFilterVal2').val('');
    \$('#bdRcpFilterBank, #bdRcpFilterBranch, #bdRcpFilterType, #bdRcpFilterOp1, #bdRcpFilterOp2').val('');
    _bdRcpFilter();
});

\$(document).on('click', '#bdRcpExportBtn', function(){
    if (typeof XLSX === 'undefined') { danger_msg('خطأ', 'مكتبة Excel غير محمّلة'); return; }

    // 🔧 إصلاح: نخزّن allData بـ index عشان نجيب السطر الصحيح (مش أول match فقط)
    var allData = _bdRcpLoadData();
    // بناء map: index_in_allData → row data
    // الترتيب في الـ DOM يطابق الترتيب الأصلي في allData
    var domRowsAll = \$('#bdRcpBody tr.rcp-row').toArray();   // كل الصفوف بنفس ترتيب allData

    // 🆕 جمع معلومات الفلاتر الفعّالة (للهيدر)
    var fInfo = [];
    var q = (\$('#bdRcpSearch').val()||'').trim();           if (q)   fInfo.push('بحث: "' + q + '"');
    var t = \$('#bdRcpFilterType').val();                     if (t)   fInfo.push('النوع: ' + (t==='self'?'الموظف':(t==='benef'?'ورثة':'صاحب حساب')));
    var fe = (\$('#bdRcpFilterEmpNo').val()||'').trim();      if (fe)  fInfo.push('رقم الموظف: ' + fe);
    var fn = (\$('#bdRcpFilterEmpName').val()||'').trim();    if (fn)  fInfo.push('اسم: ' + fn);
    var fb = \$('#bdRcpFilterBank').val();                    if (fb)  fInfo.push('البنك: ' + fb);
    var fbr = \$('#bdRcpFilterBranch').val();                 if (fbr) fInfo.push('الفرع: ' + fbr);
    // 🆕 شرطين مرنين للمبلغ
    var fop1 = \$('#bdRcpFilterOp1').val(); var fval1 = \$('#bdRcpFilterVal1').val();
    if (fop1 && fval1 !== '') fInfo.push('المبلغ ' + fop1 + ' ' + fval1);
    var fop2 = \$('#bdRcpFilterOp2').val(); var fval2 = \$('#bdRcpFilterVal2').val();
    if (fop2 && fval2 !== '') fInfo.push('المبلغ ' + fop2 + ' ' + fval2);

    // 🆕 صفوف الهيدر (3 صفوف معلوماتية)
    var data = [];
    data.push(['تفصيل المستفيدين — دفعة {$batch_no}','','','','','','','','','','','','']);
    if (fInfo.length > 0) {
        data.push(['الفلاتر المطبّقة: ' + fInfo.join('  ·  '),'','','','','','','','','','','','']);
    } else {
        data.push(['(بدون فلاتر — كل المستفيدين)','','','','','','','','','','','','']);
    }
    data.push([]);   // صف فاصل
    data.push(['#','رقم الموظف','اسم الموظف','المقر','نوع المستلم','المستلم','العلاقة','البنك/المحفظة','الفرع','رقم الحساب','IBAN','هاتف صاحب الحساب','المبلغ']);

    var idx = 0; var totalAmt = 0;
    // 🔧 استخدم الـ index من الـ DOM — يطابق ترتيب allData تماماً
    for (var di = 0; di < domRowsAll.length; di++) {
        var \$r = \$(domRowsAll[di]);
        if (\$r.hasClass('row-hidden')) continue;   // تخطّى المخفي (الفلتر)
        var dr = allData[di];                       // نفس الـ index
        if (!dr) continue;
        idx++;
        var amt = parseFloat(dr.amount) || 0;
        totalAmt += amt;
        var typeText = dr.type === 'self' ? 'الموظف' : (dr.type === 'benef' ? 'وريث' : 'صاحب حساب');
        // الفرع: فاضي للمحفظات (provider_type=2)
        var isWltExp = (parseInt(dr.provider_type) === 2);
        var branchExp = isWltExp ? '' : (dr.prov_branch || '');
        data.push([idx, dr.emp_no, dr.emp_name, dr.emp_branch, typeText, dr.recipient, dr.rel_label,
                   dr.master_bank, branchExp, dr.account_no, dr.iban, dr.owner_phone, amt]);
    }
    // صف الإجمالي
    data.push(['','','','','','','','','','','','الإجمالي (' + idx + ' حركة)', totalAmt]);

    var wb = XLSX.utils.book_new();
    var ws = XLSX.utils.aoa_to_sheet(data);
    if (!ws['!views']) ws['!views'] = [{RTL:true}];
    ws['!cols'] = [{wch:5},{wch:10},{wch:28},{wch:14},{wch:12},{wch:24},{wch:14},{wch:18},{wch:14},{wch:14},{wch:24},{wch:14},{wch:14}];
    // 🆕 merge للهيدر (الصف 1 و 2 يمتدوا على كل الأعمدة)
    if (!ws['!merges']) ws['!merges'] = [];
    ws['!merges'].push({s:{r:0,c:0},e:{r:0,c:12}});
    ws['!merges'].push({s:{r:1,c:0},e:{r:1,c:12}});
    XLSX.utils.book_append_sheet(wb, ws, 'المستفيدون');

    // 🆕 اسم الملف يبيّن الفلاتر (إن وُجدت)
    var filterTag = fInfo.length > 0 ? '_مفلتر' : '';
    XLSX.writeFile(wb, 'مستفيدون_{$batch_no}' + filterTag + '_' + idx + '_حركة.xlsx');
});

// ════════════════════════════════════════════════════════════
// 🆕 فلتر موحّد — يتحكّم بكل الصفحة (إجماليات + قوائم بنوك)
//     filter: 'all' | 'active' | 'inactive'
//     active   = حالة الموظف = 1 (فعّال)
//     inactive = حالة الموظف ∈ {0,2,4} (متقاعد + متوفى + مغلق)
// ════════════════════════════════════════════════════════════
function _fmt(n){
    n = Math.round((+n || 0) * 100) / 100;
    return n.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
}
function _fmtInt(n){ return (+n || 0).toLocaleString('en-US'); }

// 💎 الـ shim القديم — نتركه للتوافق إذا فيه استدعاء قديم
function applyEmpStatusFilter(status){
    // ربط القديم (1/0/2/4) بالموحّد (active/inactive/all)
    var f = 'all';
    if (status === '1')      f = 'active';
    else if (status === '0' || status === '2' || status === '4') f = 'inactive';
    applyUnifiedFilter(f);
}

// ════════════════════════════════════════════════════════════
// 🆕 مرفقات الطلبات داخل الدفعة (modal)
// ════════════════════════════════════════════════════════════
var _prAttCurrentRid = 0;
var _prAttCurrentNo  = '';

function openBatchAttachments(req_id, req_no){
    _prAttCurrentRid = parseInt(req_id, 10);
    _prAttCurrentNo  = String(req_no || '');
    \$('#prAttReqNo').text(_prAttCurrentNo);
    \$('#prAttIdentity').val(_prAttCurrentRid);
    \$('#prAttListBox').html(
        '<div class="text-center text-muted py-4">' +
        '<i class="fa fa-spinner fa-spin fa-2x"></i>' +
        '<div style="margin-top:8px">جاري التحميل...</div>' +
        '</div>'
    );
    \$('#prAttUploadForm')[0].reset();
    \$('#prAttIdentity').val(_prAttCurrentRid);  // reset فقد الـ hidden
    var modal = new bootstrap.Modal(document.getElementById('prAttachModal'));
    modal.show();
    _prAttLoad();
}

function _prAttLoad(){
    if (!_prAttCurrentRid) return;
    get_data('{$req_att_url}', { req_id: _prAttCurrentRid }, function(j){
        if (!j || !j.ok) {
            \$('#prAttListBox').html('<div class="alert alert-danger">' + ((j && j.msg) || 'تعذّر التحميل') + '</div>');
            return;
        }
        var rows = j.rows || [];
        \$('#prAttCount').text(rows.length + ' مرفق');
        // حدّث العدّاد على الزر الأصلي
        var cntEl = document.getElementById('prAttCnt_' + _prAttCurrentRid);
        if (cntEl) {
            cntEl.textContent = rows.length;
            if (rows.length > 0) cntEl.classList.add('has-att');
            else cntEl.classList.remove('has-att');
        }
        if (rows.length === 0) {
            \$('#prAttListBox').html(
                '<div class="alert alert-light text-center text-muted py-4">' +
                '<i class="fa fa-inbox fa-2x d-block mb-2" style="opacity:.4"></i>' +
                'لا توجد مرفقات بعد لهذا الطلب — ارفع أول مرفق من الأعلى' +
                '</div>'
            );
            return;
        }
        var html = '<div class="table-responsive"><table class="table table-bordered table-sm" style="font-size:.82rem">';
        html += '<thead class="table-light"><tr><th style="width:35px">#</th><th>اسم الملف</th><th style="width:160px">رفعه</th><th style="width:130px">التاريخ</th><th style="width:110px;text-align:center">إجراءات</th></tr></thead><tbody>';
        rows.forEach(function(r, i){
            var dl = '{$att_download_base}' + r.FILE_PATH + '/' + encodeURIComponent(r.FILE_NAME || '');
            html += '<tr id="prAttRow_' + r.ID + '">';
            html += '<td>' + (i+1) + '</td>';
            html += '<td><a href="' + dl + '" target="_blank"><i class="fa fa-file me-1 text-primary"></i> ' + (r.FILE_NAME || '') + '</a></td>';
            html += '<td>' + (r.ENTRY_USER_NAME || r.ENTRY_USER || '') + '</td>';
            html += '<td>' + (r.ENTRY_DATE || '') + '</td>';
            html += '<td class="text-center">';
            html += '<a href="' + dl + '" target="_blank" class="btn btn-sm btn-outline-primary" title="تحميل"><i class="fa fa-download"></i></a> ';
            if (r.IS_MINE == 1) {
                html += '<button class="btn btn-sm btn-outline-danger" onclick="deletePrAttach(' + r.ID + ')" title="حذف"><i class="fa fa-trash"></i></button>';
            }
            html += '</td>';
            html += '</tr>';
        });
        html += '</tbody></table></div>';
        \$('#prAttListBox').html(html);
    });
}

function submitPrAttach(){
    if (!_prAttCurrentRid) return;
    var form = document.getElementById('prAttUploadForm');
    var fileInput = form.querySelector('input[type=file]');
    if (!fileInput.files || fileInput.files.length === 0) {
        danger_msg('تنبيه', 'اختر ملفاً للرفع'); return;
    }
    var fd = new FormData(form);
    fd.set('identity', _prAttCurrentRid);
    fd.set('category', 'payment_req');
    fd.set('upload_type', 'attachment');
    \$('#prAttUploadProgress').show();
    \$.ajax({
        url: '{$att_upload_url}',
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success: function(resp){
            \$('#prAttUploadProgress').hide();
            // 🆕 السيرفر يرجع رقم ID على النجاح، أو نص خطأ على الفشل (نفس منطق payment_req_show)
            var msg = String(resp).trim();
            if (/^\\d+\$/.test(msg) && parseInt(msg, 10) > 0) {
                success_msg('تم', 'تم رفع المرفق');
                form.reset();
                _prAttLoad();
            } else if (msg.indexOf('بنجاح') >= 0) {
                success_msg('تم', 'تم رفع المرفق');
                form.reset();
                _prAttLoad();
            } else {
                danger_msg('خطأ', msg || 'فشل الرفع');
            }
        },
        error: function(xhr){
            \$('#prAttUploadProgress').hide();
            danger_msg('خطأ', 'فشل الاتصال بالسيرفر' + (xhr && xhr.status ? ' (HTTP ' + xhr.status + ')' : ''));
        }
    });
}

function deletePrAttach(att_id){
    if (!confirm('حذف هذا المرفق؟ لا يمكن استرجاعه.')) return;
    get_data('{$att_delete_url}', { id: att_id }, function(resp){
        var msg = String(resp).trim();
        if (msg === '1' || msg === 'true' || msg.indexOf('success') !== -1) {
            success_msg('تم', 'تم حذف المرفق');
            _prAttLoad();
        } else {
            danger_msg('خطأ', msg || 'فشل الحذف');
        }
    }, 'html');
}

// 🆕 تصدير جدول "تفصيل حسب المقر" إلى Excel — يصدّر الصفوف الظاهرة فقط
function exportMbranchTable(){
    var tbl = document.getElementById('mbranchBreakdownTable');
    if (!tbl) return;
    if (typeof XLSX === 'undefined') { danger_msg('خطأ', 'مكتبة Excel غير محمّلة'); return; }

    // معلومات الدفعة + الفلتر النشط
    var batchNo = '{$batch_no}';
    var filterChecked = document.querySelector('input[name="unifiedFilter"]:checked');
    var fVal = filterChecked ? filterChecked.value : 'all';
    var fLbl = fVal === 'active' ? 'الفعّالين فقط' : (fVal === 'inactive' ? 'غير الفعّالين فقط' : 'الكل');
    var monSel = document.getElementById('ufMonthSelect');
    var monVal = monSel ? monSel.value : '';
    var monTxt = '';
    if (monVal && monVal.length === 6) monTxt = monVal.substr(4,2) + '/' + monVal.substr(0,4);

    var data = [];
    data.push(['تفصيل الصرف حسب المقر — دفعة ' + batchNo]);
    data.push(['التصفية: ' + fLbl + (monTxt ? ' (حسب شهر ' + monTxt + ')' : '')]);
    data.push(['تاريخ التصدير: ' + new Date().toLocaleString('en-GB')]);
    data.push([]);
    // headers
    data.push(['#', 'المقر', 'موظفين', 'راتب كامل', 'راتب جزئي', 'دفعة من المستحقات', 'استحقاقات وإضافات', 'الإجمالي']);

    var n = 0, totN = 0, totT1 = 0, totT2 = 0, totT3 = 0, totT4 = 0, totAmt = 0;
    document.querySelectorAll('tr.mbr-row').forEach(function(tr){
        if (tr.style.display === 'none') return;
        n++;
        var name = (tr.querySelector('td:nth-child(1)') || {}).textContent || '';
        var c    = parseInt((tr.querySelector('.mbr-count')||{}).textContent.replace(/[^0-9-]/g,'')||0, 10);
        var t1   = _parseAmt((tr.querySelector('.mbr-t1')||{}).textContent);
        var t2   = _parseAmt((tr.querySelector('.mbr-t2')||{}).textContent);
        var t3   = _parseAmt((tr.querySelector('.mbr-t3')||{}).textContent);
        var t4   = _parseAmt((tr.querySelector('.mbr-t4')||{}).textContent);
        var tot  = _parseAmt((tr.querySelector('.mbr-total')||{}).textContent);
        data.push([n, name.trim(), c, t1, t2, t3, t4, tot]);
        totN += c; totT1 += t1; totT2 += t2; totT3 += t3; totT4 += t4; totAmt += tot;
    });
    data.push(['', 'الإجمالي العام', totN, totT1, totT2, totT3, totT4, totAmt]);

    var wb = XLSX.utils.book_new();
    var ws = XLSX.utils.aoa_to_sheet(data);
    if (!ws['!views']) ws['!views'] = [{RTL: true}];
    ws['!cols'] = [{wch:5},{wch:24},{wch:10},{wch:14},{wch:14},{wch:18},{wch:20},{wch:14}];
    // merge للعنوان والـ subtitle
    if (!ws['!merges']) ws['!merges'] = [];
    ws['!merges'].push({s:{r:0,c:0},e:{r:0,c:7}});
    ws['!merges'].push({s:{r:1,c:0},e:{r:1,c:7}});
    ws['!merges'].push({s:{r:2,c:0},e:{r:2,c:7}});
    XLSX.utils.book_append_sheet(wb, ws, 'تفصيل المقر');
    XLSX.writeFile(wb, 'تفصيل_المقر_' + batchNo + '.xlsx');
}

function _parseAmt(s){
    if (!s) return 0;
    var v = parseFloat(String(s).replace(/[,\\s]/g, '').replace(/[^0-9.\\-]/g, ''));
    return isNaN(v) ? 0 : v;
}

// 🆕 اختيار شهر للتقييم — يحدّث data-emp-status على كل صف بحالة الشهر
//    ثم يعيد تطبيق الفلتر الحالي (الكل/فعّال/غير فعّال)
function applyMonthSelection(month){
    // 🔧 dedup: نختار صف واحد فقط لكل emp_no (لو في تكرار بسبب فروع مكررة أو غيرها)
    var allRows = document.querySelectorAll('tr.emp-row[data-emp-status]');
    var seen = {};
    var rows = [];
    allRows.forEach(function(tr){
        var emp = tr.getAttribute('data-emp');
        if (!emp || seen[emp]) return;
        seen[emp] = true;
        rows.push(tr);
    });

    var cnt = {1:0, 0:0, 2:0, 4:0};
    rows.forEach(function(tr){
        var st = tr.getAttribute('data-emp-status-default') || '1';
        if (month) {
            var raw = tr.getAttribute('data-emp-status-map') || '';
            if (raw) {
                try {
                    var m = JSON.parse(raw);
                    if (m && m[month] !== undefined && m[month] !== null) {
                        st = String(m[month]);
                    }
                } catch(e) {}
            }
        }
        // طبّق الـ status على كل الصفوف اللي بنفس emp_no (لو في تكرار)
        var emp = tr.getAttribute('data-emp');
        document.querySelectorAll('tr.emp-row[data-emp="' + emp + '"]').forEach(function(t){
            t.setAttribute('data-emp-status', st);
        });
        if (cnt[st] !== undefined) cnt[st]++;
    });

    // 🆕 Validation — لازم sum(cnt) = unique emp count
    var sum = cnt['1'] + cnt['0'] + cnt['2'] + cnt['4'];
    if (sum !== rows.length) {
        console.warn('[applyMonthSelection] count mismatch: sum=' + sum + ' but unique emps=' + rows.length);
    }

    // حدّث عدّادات أزرار الفلتر (فعّال/غير فعّال) بناءً على الشهر المختار
    var nActive   = cnt['1'] || 0;
    var nInactive = (cnt['0']||0) + (cnt['2']||0) + (cnt['4']||0);
    var elA = document.getElementById('ufCntActive');   if (elA) elA.textContent = nActive.toLocaleString('en-US');
    var elI = document.getElementById('ufCntInactive'); if (elI) elI.textContent = nInactive.toLocaleString('en-US');

    // أعد تطبيق الفلتر الحالي
    var checked = document.querySelector('input[name="unifiedFilter"]:checked');
    var current = checked ? checked.value : 'all';
    applyUnifiedFilter(current);
}

// 🆕 تصدير الموظفين حسب الحالة (فعّال / غير فعّال) — حسب الشهر المختار
function exportEmpsByStatus(statusType){
    // dedup: نأخذ صف واحد فقط لكل emp_no
    var allRows = document.querySelectorAll('tr.emp-row[data-emp]');
    var seen = {};
    var matches = [];
    allRows.forEach(function(tr){
        var emp = tr.getAttribute('data-emp');
        if (!emp || seen[emp]) return;
        seen[emp] = true;
        var st = tr.getAttribute('data-emp-status') || '1';
        var include = false;
        if (statusType === 'active')   include = (st === '1');
        else if (statusType === 'inactive') include = (st !== '1' && st !== '');
        if (include) matches.push(tr);
    });

    if (matches.length === 0) {
        if (typeof warning_msg === 'function') warning_msg('تنبيه','لا يوجد موظفين بهذه الحالة');
        else alert('لا يوجد موظفين');
        return;
    }

    // معرفة الشهر المختار
    var ms = document.getElementById('ufMonthSelect');
    var monthLabel = (ms && ms.value) ? (ms.options[ms.selectedIndex].text) : 'الحالة الحالية';

    // بناء CSV
    var statusLabel = {1:'فعّال', 0:'متقاعد', 2:'متوفى', 4:'حساب مغلق'};
    var rows = [['EMP_NO', 'الاسم', 'المقر', 'الحالة', 'المبلغ']];
    matches.forEach(function(tr){
        var emp = tr.getAttribute('data-emp');
        var tds = tr.querySelectorAll('td');
        // الترتيب حسب thead: # | رقم الموظف | الاسم | حالة الحساب | المقر | الطلبات | المبلغ
        var name = tds[2] ? (tds[2].innerText || '').trim() : '';
        var branch = tds[4] ? (tds[4].innerText || '').trim() : '';
        var amt = tds[6] ? (tds[6].innerText || '').trim() : '';
        var st = tr.getAttribute('data-emp-status') || '1';
        rows.push([emp, name, branch, statusLabel[st] || st, amt]);
    });

    // CSV with BOM
    var csv = '﻿' + rows.map(function(r){
        return r.map(function(c){
            var s = String(c == null ? '' : c).replace(/\\s+/g, ' ').trim();
            if (s.indexOf(',') !== -1 || s.indexOf('"') !== -1 || s.indexOf('\\n') !== -1) {
                s = '"' + s.replace(/"/g, '""') + '"';
            }
            return s;
        }).join(',');
    }).join('\\r\\n');

    var typeLabel = (statusType === 'active') ? 'فعالين' : 'غير_فعالين';
    var fileName = 'موظفين_' + typeLabel + '_' + monthLabel.replace(/[\\/\\s]/g,'_') + '_' + new Date().getTime() + '.csv';
    var blob = new Blob([csv], {type:'text/csv;charset=utf-8'});
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = fileName;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);

    if (typeof success_msg === 'function') success_msg('تم', 'تم تصدير ' + matches.length + ' موظف');
}

function applyUnifiedFilter(filter){
    var rows = document.querySelectorAll('tr.emp-row[data-emp-status]');
    var isAll = (filter === 'all');

    // طبقة المطابقة: فعّال = status==1، غير فعّال = 0/2/4
    function _match(s){
        if (isAll) return true;
        if (filter === 'active')   return s === '1';
        if (filter === 'inactive') return s !== '1' && s !== '';
        return true;
    }

    // ─── المرحلة 1: إظهار/إخفاء صفوف الموظفين السفلية ───
    var visibleCount = 0;
    var totalCount   = rows.length;
    rows.forEach(function(tr){
        var s = tr.getAttribute('data-emp-status') || '1';
        var show = _match(s);
        tr.style.display = show ? '' : 'none';
        if (show) visibleCount++;
        var emp = tr.getAttribute('data-emp');
        if (emp) {
            var expanded = document.querySelector('tr.emp-expanded[data-emp="' + emp + '"]');
            if (expanded && !show) expanded.style.display = 'none';
        }
    });

    // ─── المرحلة 2: أعد حساب جميع التجميعات من الصفوف الظاهرة ───
    var bankAgg    = {};   // bankSectionId -> {amt, emps:Set, branches:Set}
    var branchAgg  = {};   // bankSectionId|branchName -> {amt, emps:Set}
    var bsRowAgg   = {};   // bsKey -> {amt, emps:Set, branches:Set}
    var mbranchAgg = {};   // مقر -> {emps:Set, amt, t1, t2, t3, t4}
    var grandSet   = {};
    var grandAmt   = 0;

    rows.forEach(function(tr){
        if (tr.style.display === 'none') return;
        var section = tr.closest('.bank-section');
        var blk     = tr.closest('.branch-block');
        if (!section || !blk) return;

        var secId  = section.id || '';
        var bnm    = section.getAttribute('data-bank-name') || '';
        var mbno   = section.getAttribute('data-mbno') || '0';
        var brName = blk.getAttribute('data-branch-name') || '';
        var emp    = tr.getAttribute('data-emp') || '0';
        var amt    = parseFloat(tr.getAttribute('data-amount')) || 0;
        var t1     = parseFloat(tr.getAttribute('data-emp-t1')) || 0;
        var t2     = parseFloat(tr.getAttribute('data-emp-t2')) || 0;
        var t3     = parseFloat(tr.getAttribute('data-emp-t3')) || 0;
        var t4     = parseFloat(tr.getAttribute('data-emp-t4')) || 0;
        var mbr    = tr.getAttribute('data-mbranch') || 'غير محدد';

        // bank-section (سفلي)
        if (!bankAgg[secId]) bankAgg[secId] = {amt:0, emps:{}, branches:{}};
        bankAgg[secId].amt += amt;
        bankAgg[secId].emps[emp] = true;
        bankAgg[secId].branches[brName] = true;

        // branch-block (سفلي)
        var bk = secId + '|' + brName;
        if (!branchAgg[bk]) branchAgg[bk] = {amt:0, emps:{}};
        branchAgg[bk].amt += amt;
        branchAgg[bk].emps[emp] = true;

        // ملخص البنوك (علوي - Table 2)
        var bsKey = mbno !== '0' ? ('m_' + mbno) : ('n_' + bnm);
        if (!bsRowAgg[bsKey]) bsRowAgg[bsKey] = {amt:0, emps:{}, branches:{}};
        bsRowAgg[bsKey].amt += amt;
        bsRowAgg[bsKey].emps[emp] = true;
        bsRowAgg[bsKey].branches[brName] = true;

        // المقر (علوي - Table 1)
        if (!mbranchAgg[mbr]) mbranchAgg[mbr] = {emps:{}, amt:0, t1:0, t2:0, t3:0, t4:0};
        mbranchAgg[mbr].amt += amt;
        if (!mbranchAgg[mbr].emps[emp]) {
            mbranchAgg[mbr].emps[emp] = true;
            mbranchAgg[mbr].t1 += t1;
            mbranchAgg[mbr].t2 += t2;
            mbranchAgg[mbr].t3 += t3;
            mbranchAgg[mbr].t4 += t4;
        }

        grandSet[emp] = true;
        grandAmt += amt;
    });

    // ─── المرحلة 3: تطبيق على الـ DOM ───

    // (A) bank-section السفلي
    document.querySelectorAll('.bank-section').forEach(function(sec){
        var id = sec.id || '';
        var a = bankAgg[id];
        if (!a || Object.keys(a.emps).length === 0) {
            sec.style.display = 'none';
            return;
        }
        sec.style.display = '';
        var empCntEl = sec.querySelector('.bs-emp-cnt');
        if (empCntEl) empCntEl.textContent = _fmtInt(Object.keys(a.emps).length);
        var grpCntEl = sec.querySelector('.bs-grp-cnt');
        if (grpCntEl) grpCntEl.textContent = _fmtInt(Object.keys(a.branches).length);
        var amtEl = sec.querySelector('.bs-total-amt');
        if (amtEl) amtEl.textContent = _fmt(a.amt);
    });

    // (B) branch-block السفلي
    document.querySelectorAll('.branch-block').forEach(function(blk){
        var section = blk.closest('.bank-section');
        var secId   = section ? section.id : '';
        var brName  = blk.getAttribute('data-branch-name') || '';
        var bk      = secId + '|' + brName;
        var b       = branchAgg[bk];
        if (!b || Object.keys(b.emps).length === 0) {
            blk.style.display = 'none';
            return;
        }
        blk.style.display = '';
        var n = Object.keys(b.emps).length;
        var emp1 = blk.querySelector('.br-emp-cnt'); if (emp1) emp1.textContent = _fmtInt(n);
        var amt1 = blk.querySelector('.br-amt');     if (amt1) amt1.textContent = _fmt(b.amt);
        var foot = blk.querySelector('tr.branch-foot-row');
        if (foot) {
            foot.style.display = (n > 1) ? '' : 'none';
            var fc = blk.querySelector('.bf-emp-cnt'); if (fc) fc.textContent = _fmtInt(n);
            var fa = blk.querySelector('.bf-amt');     if (fa) fa.textContent = _fmt(b.amt);
        }
    });

    // (C) Table 1: المقر
    var mbrTotalCount = 0, mbrT1 = 0, mbrT2 = 0, mbrT3 = 0, mbrT4 = 0, mbrTotalAmt = 0;
    var _mbrAmts = [];   // [{tr, amt}] — لتحديث شرائط النسبة بعد الاحتساب
    document.querySelectorAll('tr.mbr-row').forEach(function(tr){
        var mbr = tr.getAttribute('data-mbr') || '';
        var a = mbranchAgg[mbr];
        if (!a || Object.keys(a.emps).length === 0) {
            tr.style.display = 'none';
            return;
        }
        tr.style.display = '';
        var n = Object.keys(a.emps).length;
        var c = tr.querySelector('.mbr-count'); if (c) c.textContent = _fmtInt(n);
        ['t1','t2','t3','t4'].forEach(function(k){
            var el = tr.querySelector('.mbr-' + k);
            if (!el) return;
            var v = a[k] || 0;
            el.textContent = _fmt(v);   // صفر بدل — حسب طلب المحاسب
            // قوّة الصفر باهت بصرياً
            if (v <= 0) el.classList.add('text-muted'); else el.classList.remove('text-muted');
        });
        var totNum = tr.querySelector('.mbr-total-num');
        if (totNum) totNum.textContent = _fmt(a.amt);
        mbrTotalCount += n;
        mbrT1 += a.t1; mbrT2 += a.t2; mbrT3 += a.t3; mbrT4 += a.t4;
        mbrTotalAmt += a.amt;
        _mbrAmts.push({tr: tr, amt: a.amt});
    });
    // 🆕 تحديث شرائط النسبة بناءً على الإجمالي الجديد
    _mbrAmts.forEach(function(item){
        var bar = item.tr.querySelector('.mbr-bar-fill');
        if (bar) {
            var pct = mbrTotalAmt > 0 ? (item.amt / mbrTotalAmt * 100) : 0;
            bar.style.width = pct + '%';
        }
    });
    var mc = document.getElementById('mbrTotalCount'); if (mc) mc.textContent = _fmtInt(mbrTotalCount);
    var mt1 = document.getElementById('mbrTotalT1'); if (mt1) mt1.textContent = _fmt(mbrT1);
    var mt2 = document.getElementById('mbrTotalT2'); if (mt2) mt2.textContent = _fmt(mbrT2);
    var mt3 = document.getElementById('mbrTotalT3'); if (mt3) mt3.textContent = _fmt(mbrT3);
    var mt4 = document.getElementById('mbrTotalT4'); if (mt4) mt4.textContent = _fmt(mbrT4);
    var mta = document.getElementById('mbrTotalAmt'); if (mta) mta.textContent = _fmt(mbrTotalAmt);

    // (D) Table 2: ملخص حسب البنك
    var bsTotalEmps = 0, bsTotalAmt = 0;
    var _bsAmts = [];   // [{tr, amt}] للشرائط
    document.querySelectorAll('tr.bs-row').forEach(function(tr){
        var mbno = tr.getAttribute('data-bs-mbno') || '0';
        var bnm  = tr.getAttribute('data-bs-bank') || '';
        var key  = mbno !== '0' ? ('m_' + mbno) : ('n_' + bnm);
        var a    = bsRowAgg[key];
        if (!a || Object.keys(a.emps).length === 0) {
            tr.style.display = 'none';
            return;
        }
        tr.style.display = '';
        var n  = Object.keys(a.emps).length;
        var bn = Object.keys(a.branches).length;
        var brc = tr.querySelector('.bs-branch-count'); if (brc) brc.textContent = _fmtInt(bn);
        var bec = tr.querySelector('.bs-emp-count');    if (bec) bec.textContent = _fmtInt(n);
        var bam = tr.querySelector('.bs-amt');          if (bam) bam.textContent = _fmt(a.amt);
        // عدد الحركات (bs-trx-count) يبقى ثابت — الفلتر بعدد الموظفين
        bsTotalEmps += n;
        bsTotalAmt  += a.amt;
        _bsAmts.push({tr: tr, amt: a.amt});
    });
    // 🆕 شرائط النسبة + نص الـ %
    _bsAmts.forEach(function(item){
        var bar = item.tr.querySelector('.bs-bar-fill');
        var pctEl = item.tr.querySelector('.bk-pct strong');
        var pct = bsTotalAmt > 0 ? (item.amt / bsTotalAmt * 100) : 0;
        if (bar) bar.style.width = pct + '%';
        if (pctEl) pctEl.textContent = pct.toFixed(1) + '%';
    });
    var bsEmpSum = document.getElementById('bsTotalEmpSum'); if (bsEmpSum) bsEmpSum.textContent = _fmtInt(bsTotalEmps);
    var bsEmpReal = document.getElementById('bsTotalEmpReal');
    if (bsEmpReal) {
        if (isAll) {
            var o = bsEmpReal.getAttribute('data-orig');
            if (o) bsEmpReal.textContent = '(' + o + ' فعلي)';
        } else {
            bsEmpReal.textContent = '(' + _fmtInt(Object.keys(grandSet).length) + ' فعلي)';
        }
    }
    var bsAmtEl = document.getElementById('bsTotalAmt'); if (bsAmtEl) bsAmtEl.textContent = _fmt(bsTotalAmt);

    // (E) الكرت العلوي
    var top = document.getElementById('topGrandTotal');
    if (top) top.textContent = _fmt(grandAmt);
    var topSub = document.getElementById('topGrandTotalSub');
    if (topSub) {
        if (isAll) {
            topSub.style.display = 'none';
        } else {
            var orig = parseFloat(top.getAttribute('data-orig')) || 0;
            topSub.textContent = '⤷ مفلتر من ' + _fmt(orig);
            topSub.style.display = '';
        }
    }
    var tef = document.getElementById('topEmpCountFiltered');
    if (tef) {
        if (isAll) {
            tef.style.display = 'none';
        } else {
            tef.textContent = ' (' + _fmtInt(Object.keys(grandSet).length) + ' ظاهر)';
            tef.style.display = '';
        }
    }

    // (F) شارة الفلتر الموحّد
    var ufBadge = document.getElementById('ufBadge');
    if (ufBadge) {
        // اقرأ الشهر المختار (لو فيه) لإظهاره في الشارة
        var monSel = document.getElementById('ufMonthSelect');
        var monVal = monSel ? monSel.value : '';
        var monTxt = '';
        if (monVal && monVal.length === 6) {
            monTxt = ' (حسب شهر ' + monVal.substr(4,2) + '/' + monVal.substr(0,4) + ')';
        }
        if (isAll) {
            if (monVal) {
                ufBadge.innerHTML = '<i class="fa fa-calendar text-primary"></i> الحالة معتمدة على شهر <strong>' + monVal.substr(4,2) + '/' + monVal.substr(0,4) + '</strong> — كل الموظفين ظاهرين';
            } else {
                ufBadge.innerHTML = '<i class="fa fa-info-circle"></i> الفلتر يطبّق على كل الصفحة (الإجماليات + قوائم البنوك)';
            }
            ufBadge.style.color = '';
        } else {
            var lbl = filter === 'active' ? 'فعّال' : 'غير فعّال';
            ufBadge.innerHTML = '<i class="fa fa-check-circle text-success"></i> الفلتر مُطبَّق: <strong>' + lbl + '</strong>' + monTxt + ' — ' + _fmtInt(Object.keys(grandSet).length) + ' موظف ظاهر';
            ufBadge.style.color = '#1e3a8a';
        }
    }
}

// alias قديم للتوافق
function applyTopSummaryFilter(filter){ applyUnifiedFilter(filter); }

// 🆕 إنشاء سند صرف من نموذج cover + اختيار "البنك المسحوب عليه" تلقائياً
//    - يفتح نافذة جديدة على /payment/financial_payment/create/{coverId}
//    - JS يقعد ينتظر الـ dropdown يتعبأ ثم يضبط القيمة (same-origin → DOM access مسموح)
//    - لو bankId = 0 → يفتح بدون auto-select (المستخدم يختار يدوياً)
function fpCreateWithBankPreselect(coverId, bankId){
    var url = 'PLACEHOLDER_FP_CREATE_URL/' + coverId;
    var w   = window.open(url, '_blank');
    if (!w) { alert('الرجاء السماح بالنوافذ المنبثقة'); return; }
    if (!bankId || bankId <= 0) return;   // ما في تطابق → خل المستخدم يختار

    // الـ dropdown يتعبأ عبر AJAX بعد load → نقعد نحاول كل 200ms
    var attempts = 0;
    var maxAttempts = 75;     // ~15 ثانية
    var poll = setInterval(function(){
        attempts++;
        if (attempts > maxAttempts || w.closed) { clearInterval(poll); return; }
        try {
            var doc = w.document;
            if (!doc) return;
            var sel = doc.getElementById('dp_check_bank_id');
            if (!sel || sel.options.length === 0) return;   // لسا ما اتعبأ
            // افحص لو القيمة ضمن الـ options
            var found = false;
            for (var i = 0; i < sel.options.length; i++){
                if (parseInt(sel.options[i].value) === bankId){
                    sel.value = sel.options[i].value;
                    found = true; break;
                }
            }
            if (found){
                // trigger change handler (jQuery)
                if (w.jQuery) {
                    w.jQuery(sel).trigger('change');
                } else {
                    var ev = doc.createEvent('HTMLEvents');
                    ev.initEvent('change', true, false);
                    sel.dispatchEvent(ev);
                }
                clearInterval(poll);
            }
        } catch(e) { /* الـ window لسا ما load — نكمّل المحاولة */ }
    }, 200);
}

// 🆕 حذف نموذج صرف مرتبط بالدفعة (يستخدم العلامة الآلية [BATCH:id:mbno])
//    يفحص أولاً لو في سند صرف مرتبط → يرفض الحذف
function coverDeleteFromBatch(batchId, mbno, bankName, coverId){
    var msg = 'هل تريد حذف نموذج الصرف #' + coverId + ' الخاص بـ ' + bankName + '؟\\n\\n'
            + 'هذا مفيد لو تم فك احتساب الدفعة وتريد إعادة تجهيز النموذج.';
    if (!confirm(msg)) return;
    var url = 'PLACEHOLDER_DELETE_URL/' + batchId + '/' + mbno;
    get_data(url, {}, function(resp){
        var j = (typeof resp === 'string') ? JSON.parse(resp) : resp;
        if (j && j.ok == 1) {
            success_msg('تم حذف نموذج الصرف #' + coverId);
            setTimeout(function(){ location.reload(); }, 800);
        } else {
            alert((j && j.msg) ? j.msg : 'فشل الحذف');
        }
    }, 'json');
}
</script>
SCRIPT;
$delete_url_root = base_url('payment_req/payment_req/cover_delete_from_batch');
$fp_create_root  = rtrim(base_url('payment/financial_payment/create'), '/');
$scripts = str_replace('PLACEHOLDER_DELETE_URL', $delete_url_root, $scripts);
$scripts = str_replace('PLACEHOLDER_FP_CREATE_URL', $fp_create_root, $scripts);
sec_scripts($scripts);
?>
