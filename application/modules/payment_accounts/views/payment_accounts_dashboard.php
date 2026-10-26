<?php
$MODULE_NAME = 'payment_accounts';
$TB_NAME     = 'payment_accounts';

$emps_url      = base_url("$MODULE_NAME/$TB_NAME");
$health_url    = base_url("$MODULE_NAME/$TB_NAME/health_check");
$providers_url = base_url("$MODULE_NAME/$TB_NAME/providers");
$branches_url  = base_url("$MODULE_NAME/$TB_NAME/branches");
$bulk_url      = base_url("$MODULE_NAME/$TB_NAME/bulk_import");
$batch_url     = base_url('payment_req/payment_req/batch');
$history_url   = base_url('payment_req/payment_req/batch_history');

$h = is_array($health ?? null) ? $health : [];
$tot_errs   = (int)($h['emp_no_acc']??0) + (int)($h['acc_no_iban']??0) + (int)($h['prov_incomplete']??0);
$tot_warns  = (int)($h['benef_unlinked']??0) + (int)($h['acc_inactive_only']??0) + (int)($h['benef_expired']??0);
$tot_info   = (int)($h['emp_dup_default']??0);
$tot_issues = $tot_errs + $tot_warns + $tot_info;

$recent = is_array($recent_batches ?? null) ? $recent_batches : [];

$status_map = [
    0 => ['label'=>'محتسبة',  'color'=>'#1e40af','bg'=>'#dbeafe','icon'=>'fa-check'],
    2 => ['label'=>'منفّذة',   'color'=>'#15803d','bg'=>'#dcfce7','icon'=>'fa-check-circle'],
    9 => ['label'=>'ملغاة',   'color'=>'#991b1b','bg'=>'#fee2e2','icon'=>'fa-ban'],
];

// تحديد أهم خطوة قادمة بناء على حالة النظام
$next_step = null;
if (($h['prov_incomplete'] ?? 0) > 0) {
    $next_step = ['icon'=>'fa-bank', 'color'=>'#dc2626',
        'title'=>'أكمل بيانات المزودين',
        'desc' => $h['prov_incomplete'] . ' مزود بإعدادات ناقصة (IBAN/Format) — يلزم لتصدير ملفات البنوك',
        'action_label'=>'فتح المزودين', 'action_url'=>$providers_url];
} elseif (($h['emp_no_acc'] ?? 0) > 0) {
    $next_step = ['icon'=>'fa-user-plus', 'color'=>'#dc2626',
        'title'=>'أضف حسابات للموظفين الفعّالين',
        'desc'=> $h['emp_no_acc'] . ' موظف فعّال بدون حسابات نشطة — لن يصرفوا في الدفعة الجديدة',
        'action_label'=>'فتح فحص الصحة', 'action_url'=>$health_url];
} elseif (($h['benef_unlinked'] ?? 0) > 0) {
    $next_step = ['icon'=>'fa-link', 'color'=>'#d97706',
        'title'=>'اربط حسابات المستفيدين',
        'desc'=> $h['benef_unlinked'] . ' موظف عنده حسابات غير مربوطة بمستفيدين',
        'action_label'=>'ربط تلقائي للكل', 'action_url'=>$health_url];
} elseif (($h['acc_no_iban'] ?? 0) > 0) {
    $next_step = ['icon'=>'fa-warning', 'color'=>'#dc2626',
        'title'=>'أصلح IBAN الناقصة',
        'desc'=> $h['acc_no_iban'] . ' حساب بنكي بـ IBAN غير صحيح — تصدير البنوك سيفشل',
        'action_label'=>'فتح الفحص الشامل', 'action_url'=>$health_url];
} elseif (empty($recent)) {
    $next_step = ['icon'=>'fa-money', 'color'=>'#16a34a',
        'title'=>'النظام جاهز — ابدأ احتساب أول دفعة',
        'desc'=>'كل البيانات سليمة. تقدر تنشئ طلبات الصرف وتحتسب دفعة جديدة',
        'action_label'=>'احتساب دفعة', 'action_url'=>$batch_url];
}

echo AntiForgeryToken();
?>

<link rel="stylesheet" href="<?= base_url() ?>assets-n/css/modules/payment_accounts/dashboard.css" type="text/css" />
    <div class="page-header">
        <div><h1 class="page-title"><?= $title ?></h1></div>
        <div class="ms-auto pageheader-btn">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0);">الرواتب والمستحقات</a></li>
                <li class="breadcrumb-item active">لوحة التحكم</li>
            </ol>
        </div>
    </div>

    <!-- ═══ Hero — الخطوة المقترحة ═══ -->
    <?php if ($next_step): ?>
    <div class="dash-hero" style="border-color:<?= $next_step['color'] ?>33">
        <div class="hero-icon" style="background:<?= $next_step['color'] ?>15;color:<?= $next_step['color'] ?>">
            <i class="fa <?= $next_step['icon'] ?>"></i>
        </div>
        <div class="hero-body">
            <small class="hero-label">الخطوة الموصى بها</small>
            <h4 class="hero-title"><?= $next_step['title'] ?></h4>
            <p class="hero-desc"><?= $next_step['desc'] ?></p>
        </div>
        <div class="hero-action">
            <a href="<?= $next_step['action_url'] ?>" class="btn btn-lg" style="background:<?= $next_step['color'] ?>;color:#fff">
                <?= $next_step['action_label'] ?> <i class="fa fa-arrow-left ms-1"></i>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══ روابط سريعة ═══ -->
    <div class="quick-links mb-3">
        <a href="<?= $batch_url ?>" class="ql-card ql-primary">
            <i class="fa fa-money"></i>
            <div><div class="t">احتساب دفعة</div><small>إنشاء واعتماد دفعة جديدة</small></div>
        </a>
        <a href="<?= $emps_url ?>" class="ql-card ql-info">
            <i class="fa fa-users"></i>
            <div><div class="t">الموظفون</div><small><?= number_format((int)($h['total_emps']??0)) ?> موظف</small></div>
        </a>
        <a href="<?= $health_url ?>" class="ql-card ql-danger">
            <i class="fa fa-heartbeat"></i>
            <div>
                <div class="t">فحص الصحة
                    <?php if ($tot_issues > 0): ?>
                        <span class="qb"><?= $tot_issues ?></span>
                    <?php endif; ?>
                </div>
                <small><?= $tot_issues === 0 ? 'كل شي سليم' : "$tot_issues مشكلة" ?></small>
            </div>
        </a>
        <a href="<?= $providers_url ?>" class="ql-card ql-warn">
            <i class="fa fa-bank"></i>
            <div><div class="t">المزودون</div><small>البنوك والمحافظ</small></div>
        </a>
        <a href="<?= $bulk_url ?>" class="ql-card ql-purple">
            <i class="fa fa-upload"></i>
            <div><div class="t">استيراد Excel</div><small>إضافة حسابات جماعية</small></div>
        </a>
    </div>

    <!-- ═══ صف الإحصائيات + حالة النظام ═══ -->
    <div class="row mb-3">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-body py-3">
                    <h6 class="dash-section-title">
                        <i class="fa fa-bar-chart text-primary"></i> إحصائيات النظام
                    </h6>
                    <div class="dash-stats">
                        <div class="ds-card ds-emp">
                            <i class="fa fa-users ds-icon"></i>
                            <div class="ds-num"><?= number_format((int)($h['total_emps']??0)) ?></div>
                            <div class="ds-lbl">موظف</div>
                        </div>
                        <div class="ds-card ds-acc">
                            <i class="fa fa-credit-card ds-icon"></i>
                            <div class="ds-num"><?= number_format((int)($h['total_accounts']??0)) ?></div>
                            <div class="ds-lbl">حساب</div>
                        </div>
                        <div class="ds-card ds-bnf">
                            <i class="fa fa-user-circle-o ds-icon"></i>
                            <div class="ds-num"><?= number_format((int)($h['total_benef']??0)) ?></div>
                            <div class="ds-lbl">مستفيد</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center mb-2">
                        <h6 class="dash-section-title mb-0">
                            <i class="fa fa-heartbeat text-danger"></i> حالة النظام
                        </h6>
                        <a href="<?= $health_url ?>" class="ms-auto btn btn-sm btn-outline-secondary">
                            <i class="fa fa-external-link"></i> فحص شامل
                        </a>
                    </div>

                    <?php if ($tot_issues === 0): ?>
                        <div class="health-clean">
                            <i class="fa fa-check-circle"></i>
                            <div>
                                <div class="t1">كل البيانات سليمة</div>
                                <div class="t2">جاهز للصرف</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="health-summary">
                            <?php if ($tot_errs > 0): ?>
                                <a href="<?= $health_url ?>" class="hs-row hs-err">
                                    <i class="fa fa-times-circle"></i>
                                    <span class="hs-num"><?= $tot_errs ?></span>
                                    <span class="hs-lbl">مشكلة حرجة</span>
                                </a>
                            <?php endif; ?>
                            <?php if ($tot_warns > 0): ?>
                                <a href="<?= $health_url ?>" class="hs-row hs-warn">
                                    <i class="fa fa-exclamation-triangle"></i>
                                    <span class="hs-num"><?= $tot_warns ?></span>
                                    <span class="hs-lbl">تحذير</span>
                                </a>
                            <?php endif; ?>
                            <?php if ($tot_info > 0): ?>
                                <a href="<?= $health_url ?>" class="hs-row hs-info">
                                    <i class="fa fa-info-circle"></i>
                                    <span class="hs-num"><?= $tot_info ?></span>
                                    <span class="hs-lbl">معلوماتي</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ آخر الدفعات ═══ -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center mb-2">
                        <h6 class="dash-section-title mb-0">
                            <i class="fa fa-history text-info"></i> آخر الدفعات
                        </h6>
                        <a href="<?= $history_url ?>" class="ms-auto btn btn-sm btn-outline-secondary">
                            <i class="fa fa-list"></i> سجل كامل
                        </a>
                    </div>

                    <?php if (empty($recent)): ?>
                        <div class="empty-state">
                            <i class="fa fa-inbox"></i>
                            <h5>لا توجد دفعات بعد</h5>
                            <p>ابدأ بإنشاء طلبات الصرف ثم احتسب أول دفعة</p>
                            <a href="<?= $batch_url ?>" class="btn btn-success">
                                <i class="fa fa-money me-1"></i> ابدأ احتساب دفعة
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0" style="font-size:.82rem">
                                <thead class="table-light">
                                    <tr>
                                        <th>رقم الدفعة</th>
                                        <th>التاريخ</th>
                                        <th class="text-center">الموظفون</th>
                                        <th class="text-end">المبلغ</th>
                                        <th>الطريقة</th>
                                        <th>الحالة</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent as $r):
                                        $st = (int)($r['STATUS'] ?? 0);
                                        $sm = $status_map[$st] ?? $status_map[0];
                                        $method = (int)($r['DISBURSE_METHOD'] ?? 1);
                                        $bid = (int)($r['BATCH_ID'] ?? 0);
                                    ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($r['BATCH_NO'] ?? '') ?></td>
                                        <td><?= $r['BATCH_DATE'] ?? '' ?></td>
                                        <td class="text-center"><?= (int)($r['EMP_COUNT'] ?? 0) ?></td>
                                        <td class="text-end fw-bold"><?= n_format((float)($r['TOTAL_AMOUNT'] ?? 0)) ?></td>
                                        <td>
                                            <?php if ($method == 2): ?>
                                                <span class="badge" style="background:#dcfce7;color:#166534">جديدة</span>
                                            <?php else: ?>
                                                <span class="badge" style="background:#f1f5f9;color:#64748b">قديمة</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="background:<?= $sm['bg'] ?>;color:<?= $sm['color'] ?>;padding:.2em .65em;border-radius:5px;font-weight:700;font-size:.72rem">
                                                <i class="fa <?= $sm['icon'] ?>"></i> <?= $sm['label'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?= base_url('payment_req/payment_req/batch_detail/'.$bid) ?>"
                                               class="btn btn-sm btn-outline-primary p-0 px-2" title="فتح">
                                                <i class="fa fa-external-link"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

