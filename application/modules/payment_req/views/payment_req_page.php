<?php
$MODULE_NAME = 'payment_req';
$TB_NAME     = 'payment_req';
$count       = $offset;
$vm          = isset($view_mode) ? $view_mode : 'detail';

$get_url     = base_url("$MODULE_NAME/$TB_NAME/get");
$delete_url  = base_url("$MODULE_NAME/$TB_NAME/delete");

$status_map = [
    0 => ['label'=>'مسودة',        'icon'=>'fa-pencil',       'bg'=>'#fef3c7','color'=>'#92400e'],
    1 => ['label'=>'معتمد',        'icon'=>'fa-check',        'bg'=>'#dbeafe','color'=>'#1e40af'],
    2 => ['label'=>'منفّذ للصرف',   'icon'=>'fa-check-circle', 'bg'=>'#d1fae5','color'=>'#065f46'],
    3 => ['label'=>'معتمد جزئي',   'icon'=>'fa-check-circle', 'bg'=>'#e0e7ff','color'=>'#3730a3'],
    4 => ['label'=>'محتسب',         'icon'=>'fa-calculator',   'bg'=>'#ccfbf1','color'=>'#0f766e'],
    9 => ['label'=>'ملغى',         'icon'=>'fa-ban',          'bg'=>'#fee2e2','color'=>'#991b1b'],
];

$has_actions = HaveAccess($delete_url) || HaveAccess(base_url("$MODULE_NAME/$TB_NAME/get"));
$is_master   = ($vm === 'master');
// 🆕 بعد دمج (رقم الدفعة+تاريخ الصرف+شهر الصرف) بعمود "الصرف" ونقل المعتمد تحت شارة الرقابة
$colspan = ($is_master ? 13 : 14) + ($has_actions ? 1 : 0);

// 🆕 helper: شارة حالة اعتماد الرقابة — الاسم من الثابت 543
if (!function_exists('raqaba_badge_html')) {
    function raqaba_badge_html($st, $rqs, $is_cancelled, $rq_name) {
        if ($is_cancelled) return '<span style="color:#cbd5e1">—</span>';
        $rqs = (int)$rqs;
        if ($rqs == 1) return '<span class="b-status" style="background:#d1fae5;color:#065f46"><i class="fa fa-shield"></i> '.$rq_name.'</span>';
        if ($rqs == 2) return '<span class="b-status" style="background:#fee2e2;color:#991b1b"><i class="fa fa-reply"></i> '.$rq_name.'</span>';
        // 0 = بانتظار — فقط لو الطلب معتمد محاسبياً (1 أو 3)
        // 🆕 بنفسجي فاتح بدل الأصفر — تمييزاً عن "مسودة" (حالة مختلفة: معتمد محاسبياً وبانتظار جهة ثانية)
        if (in_array((int)$st, [1, 3])) return '<span class="b-status" style="background:#f3e8ff;color:#6b21a8"><i class="fa fa-clock-o"></i> '.$rq_name.'</span>';
        return '<span style="color:#cbd5e1">—</span>'; // مسودة/غيرها
    }
}
?>

<link rel="stylesheet" href="<?= base_url() ?>assets-n/css/modules/payment_req/page.css" type="text/css" />
<style>
/* 🆕 ضبط العرض على zoom 100%: الخلايا المدمجة لا تلتف أبداً (رقم الطلب/الشهر/الشارات/التواريخ)
   والأعمدة النصية فقط (.wrap) يُسمح لها بالالتفاف — الفائض يتصرّف عبر تمرير table-responsive */
#page_tb td, #page_tb th { white-space: nowrap; vertical-align: middle; }
#page_tb td.wrap { white-space: normal; }
#page_tb .b-status, #page_tb .b-req-type { white-space: nowrap; display: inline-block; }
</style>

<?php
// 🆕 إجمالي المبلغ حسب الفلاتر — chip صغير + شريط إجماليات حسب الحالة (من PAYMENT_REQ_TOTALS)
$T = isset($totals) && is_array($totals) ? $totals : [];
$t_amount = (float)($T['amount'] ?? 0);
$t_s0 = (float)($T['s0_amount'] ?? 0);          // مسودة
$t_s1 = (float)($T['s1_amount'] ?? 0);          // معتمد
$t_s2 = (float)($T['s2_amount'] ?? 0);          // منفّذ للصرف
$t_cx = (float)($T['cancelled_amount'] ?? 0);   // ملغى
?>

<?php if ($t_amount > 0): ?>
<div class="pr-total-chip">
  <i class="fa fa-money"></i>
  <span class="lbl">إجمالي المبلغ:</span>
  <span class="val"><?= n_format($t_amount) ?></span>
  <?php if ($t_s1 > 0): ?><span style="background:#dbeafe;color:#1e40af;padding:2px 10px;border-radius:12px;font-size:.72rem;font-weight:700;margin-inline-start:.45rem;white-space:nowrap"><i class="fa fa-check"></i> معتمد: <?= n_format($t_s1) ?></span><?php endif; ?>
  <?php if ($t_s0 > 0): ?><span style="background:#fef3c7;color:#92400e;padding:2px 10px;border-radius:12px;font-size:.72rem;font-weight:700;margin-inline-start:.3rem;white-space:nowrap"><i class="fa fa-pencil"></i> مسودة: <?= n_format($t_s0) ?></span><?php endif; ?>
  <?php if ($t_s2 > 0): ?><span style="background:#d1fae5;color:#065f46;padding:2px 10px;border-radius:12px;font-size:.72rem;font-weight:700;margin-inline-start:.3rem;white-space:nowrap"><i class="fa fa-check-circle"></i> منفّذ للصرف: <?= n_format($t_s2) ?></span><?php endif; ?>
  <?php if ($t_cx > 0): ?><span style="background:#fee2e2;color:#991b1b;padding:2px 10px;border-radius:12px;font-size:.72rem;font-weight:700;margin-inline-start:.3rem;white-space:nowrap"><i class="fa fa-ban"></i> ملغى: <?= n_format($t_cx) ?></span><?php endif; ?>
</div>
<?php endif; ?>

<?php if (!is_array($page_rows) || count($page_rows) === 0): ?>
    <div class="alert alert-light text-center text-muted py-4"><i class="fa fa-inbox fa-2x d-block mb-2" style="opacity:.4"></i>لا توجد طلبات</div>
<?php else: ?>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <small class="text-muted"><?= isset($total_rows) ? $total_rows : count($page_rows) ?> نتيجة</small>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-<?= $is_master ? 'primary' : 'outline-secondary' ?>" onclick="switchView('master')" title="عرض الطلبات"><i class="fa fa-list"></i> طلبات</button>
            <button type="button" class="btn btn-<?= !$is_master ? 'primary' : 'outline-secondary' ?>" onclick="switchView('detail')" title="عرض الموظفين"><i class="fa fa-users"></i> موظفين</button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered" id="page_tb" data-container="container">
            <thead class="table-light">
            <tr>
                <th style="width:36px">م</th>
                <th>رقم الطلب</th>
                <?php if ($is_master): ?>
                    <th class="text-center">عدد الموظفين</th>
                <?php else: ?>
                    <th>الموظف</th>
                    <th>المقر</th>
                <?php endif; ?>
                <th>الشهر</th>
                <th>نوع الطلب</th>
                <th>البيان</th>
                <th class="text-end">المبلغ</th>
                <th>الحالة</th>
                <th>المنشئ</th>
                <th>تاريخ الإنشاء</th>
                <!-- 🆕 عمود موحّد: رقم الدفعة · شهر الصرف (بدل 3 أعمدة كانت فاضية بمعظم الصفوف) -->
                <th class="text-center">الصرف</th>
                <th class="text-center"><i class="fa fa-shield text-success"></i> اعتماد الرقابة</th>
                <th>ملاحظات</th>
                <?php if ($has_actions): ?>
                    <th class="text-center" style="width:100px"></th>
                <?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php if ($page > 1): ?>
                <tr><td colspan="<?= $colspan ?>" id="page-<?= $page ?>" class="page-sector" data-page="<?= $page ?>"></td></tr>
            <?php endif; ?>
            <?php foreach ($page_rows as $row):
                $st  = (int)($row['STATUS'] ?? 0);
                $stm = $status_map[$st] ?? $status_map[0];
                $rid = $row['REQ_ID'] ?? '';

                $thm = $row['THE_MONTH'] ?? '';
                if (strlen($thm)==6) { $mn=(int)substr($thm,4,2); $thm=months($mn).' '.substr($thm,0,4); }

                $amt = isset($row['REQ_AMOUNT']) ? n_format((float)$row['REQ_AMOUNT']) : '0';
                $is_cancelled = ($st == 9);
                $is_active = (int)($row['IS_ACTIVE'] ?? 1);
                $row_class = $is_cancelled ? 'cancelled' : '';
                $emp_no   = $row['EMP_NO'] ?? '';
                $emp_name = $row['EMP_NAME'] ?? '';
                $branch   = $row['BRANCH_NAME'] ?? '';
                $row_bg   = '';
                if (!$is_master && !$is_cancelled && $is_active != 1) {
                    $row_bg = 'background:#fef2f2;'; // أحمر خفيف — غير فعال
                } elseif (!$is_master && !$is_cancelled && $is_active == 1) {
                    $row_bg = ''; // فعال — بدون لون (أبيض عادي)
                }
                ?>
                <tr class="<?= $row_class ?>" style="<?= $row_bg ?>"
                    ondblclick="javascript:get_to_link('<?= base_url("$MODULE_NAME/$TB_NAME/get/$rid") ?>');">
                    <td class="text-center text-muted"><?= $count ?></td>
                    <td><span style="font-weight:600;color:#64748b"><?= $row['REQ_NO'] ?? '' ?></span></td>
                    <?php if ($is_master): ?>
                        <td class="text-center fw-bold"><?= $row['EMP_COUNT'] ?? 0 ?></td>
                    <?php else: ?>
                        <td class="wrap" style="min-width:130px">
                            <span class="fw-bold"><?= $emp_no ?></span>
                            <span class="text-muted d-block" style="font-size:.78rem"><?= $emp_name ?><?php if ($is_active != 1): ?> <span style="background:#fee2e2;color:#991b1b;padding:1px 5px;border-radius:4px;font-size:.6rem;font-weight:600">غير فعال</span><?php endif; ?></span>
                        </td>
                        <td class="wrap" style="font-size:.78rem;max-width:110px"><?= $branch ?></td>
                    <?php endif; ?>
                    <td><?= $thm ?></td>
                    <td><span class="b-req-type"><?= $row['REQ_TYPE_NAME'] ?? '' ?></span></td>
                    <td class="wrap" style="font-size:.78rem;color:#475569;max-width:170px;min-width:130px" title="<?= htmlspecialchars((string)($row['PAY_TYPE_NAME'] ?? '')) ?>"><?php if (!empty($row['PAY_TYPE_NAME'])): ?><span style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.4"><?= htmlspecialchars($row['PAY_TYPE_NAME']) ?></span><?php else: ?><span style="color:#cbd5e1">—</span><?php endif; ?></td>
                    <td class="text-end"><?php
                        // 🆕 مسودة فاضية (0 موظف) — بادج تنبيه بدل 0.00 الباهت
                        if ($is_master && $st === 0 && (int)($row['EMP_COUNT'] ?? 0) === 0): ?>
                        <span style="background:#ffedd5;color:#c2410c;padding:2px 8px;border-radius:10px;font-size:.7rem;font-weight:700;white-space:nowrap"><i class="fa fa-exclamation-circle"></i> فارغ — أكمل الطلب</span>
                        <?php else: ?><span class="amt"><?= $is_master ? n_format((float)($row['TOTAL_AMOUNT'] ?? 0)) : $amt ?></span><?php endif; ?></td>
                    <td class="text-center">
                        <span class="b-status" style="background:<?= $stm['bg'] ?>;color:<?= $stm['color'] ?>">
                            <i class="fa <?= $stm['icon'] ?>"></i> <?= $row['STATUS_NAME'] ?? $stm['label'] ?>
                        </span>
                    </td>
                    <td class="wrap" style="font-size:.75rem;color:#64748b;max-width:130px;min-width:100px" title="<?= htmlspecialchars((string)($row['ENTRY_USER_NAME'] ?? '')) ?>"><span style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.4"><?= $row['ENTRY_USER_NAME'] ?? '' ?></span></td>
                    <td style="font-size:.75rem;color:#64748b"><?= $row['ENTRY_DATE'] ?? '' ?></td>
                    <!-- 🆕 عمود الصرف الموحّد: chip (رقم الدفعة · شهر الصرف) — يظهر فقط عند وجود دفعة -->
                    <td class="text-center"><?php
                        $batch_id = $row['BATCH_ID'] ?? '';
                        $batch_no = $row['BATCH_NO'] ?? '';
                        $pd = trim((string)($row['PAY_DATE'] ?? ''));
                        $pay_mn = (strlen($pd) >= 10) ? substr($pd, 3) : '';
                        if ($batch_no):
                    ?><a href="<?= base_url("$MODULE_NAME/$TB_NAME/batch_detail/$batch_id") ?>" onclick="event.stopPropagation()"
                          style="display:inline-flex;align-items:center;gap:.3rem;background:#f5f3ff;border:1px solid #ddd6fe;color:#6d28d9;padding:2px 8px;border-radius:10px;font-size:.72rem;font-weight:700;text-decoration:none;white-space:nowrap"
                          title="<?= $pd !== '' ? 'تاريخ الصرف: ' . $pd : 'محتسبة ضمن دفعة — لم تُصرف بعد' ?>">
                            <i class="fa fa-university"></i> <?= $batch_no ?><?php if ($pay_mn !== ''): ?> <span style="color:#a78bfa">·</span> <?= $pay_mn ?><?php endif; ?>
                        </a><?php else: ?><span class="text-muted" style="font-size:.72rem">—</span><?php endif; ?></td>
                    <!-- 🆕 اعتماد الرقابة + اسم المراقب تحت الشارة (بدل عمود مستقل) -->
                    <td class="text-center" style="font-size:.7rem">
                        <?= raqaba_badge_html($st, $row['RAQABA_STATUS'] ?? 0, $is_cancelled, $row['RAQABA_STATUS_NAME'] ?? '') ?>
                        <?php if (!empty($row['RAQABA_USER_NAME']) && in_array((int)($row['RAQABA_STATUS'] ?? 0), [1, 2])): ?>
                            <span class="d-block" style="font-size:.66rem;color:#64748b;margin-top:1px"><?= htmlspecialchars($row['RAQABA_USER_NAME']) ?></span>
                        <?php endif; ?>
                    </td>
                    <!-- 🆕 كولوم الملاحظات — مقصوص بسطرين + tooltip بالنص الكامل -->
                    <?php $note_txt = $row['NOTE'] ?? ($row['MASTER_NOTE'] ?? ''); ?>
                    <td class="wrap" style="font-size:.75rem;color:#475569;max-width:200px;min-width:120px" title="<?= htmlspecialchars((string)$note_txt) ?>"><?php if ($note_txt !== '' && $note_txt !== null): ?><span style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.4;white-space:normal"><?= htmlspecialchars((string)$note_txt) ?></span><?php else: ?><span style="color:#cbd5e1">—</span><?php endif; ?></td>
                    <?php if ($has_actions): ?>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <?php if ($st == 0 && HaveAccess($delete_url)): ?>
                                    <button class="btn btn-sm btn-outline-danger" title="حذف الطلب" onclick="event.stopPropagation();delete_req(this,<?=$rid?>)"><i class="fa fa-trash"></i></button>
                                <?php endif; ?>
                                <?php if (in_array($st, [1, 3]) && (int)($row['RAQABA_STATUS'] ?? 0) != 1 && HaveAccess($delete_url)): ?>
                                    <button class="btn btn-sm btn-outline-danger" title="إلغاء الطلب" onclick="event.stopPropagation();cancel_req(this,<?=$rid?>)"><i class="fa fa-ban"></i></button>
                                <?php endif; ?>
                                <a class="btn btn-sm btn-outline-secondary" title="عرض" href="<?= base_url("$MODULE_NAME/$TB_NAME/get/$rid") ?>" onclick="event.stopPropagation()"><i class="fa fa-eye"></i></a>
                            </div>
                        </td>
                    <?php endif; ?>
                </tr>
                <?php $count++; endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php echo $this->pagination->create_links(); ?>

<script>
    if (typeof reBind === 'function') reBind();
</script>
