<?php
/**
 * 🖨️  Print report for a payment batch (A4, RTL, print-optimized)
 *      مصمّم للمحاسب: رأس الدفعة + تفصيل المقر + ملخص البنوك + التواقيع
 */
$bi = $batch_info;
$st = (int)($bi['STATUS'] ?? 0);
$batch_id = $bi['BATCH_ID'] ?? 0;
$batch_no = $bi['BATCH_NO'] ?? '';
$rows = isset($detail_rows) && is_array($detail_rows) ? $detail_rows : [];
$bs_rows = isset($bank_summary) && is_array($bank_summary) ? $bank_summary : [];
$reqs_list = isset($batch_reqs) && is_array($batch_reqs) ? $batch_reqs : [];

// ─── Status label ──────────────────────────────────────────────
$status_labels = [];
foreach (($batch_status_cons ?? []) as $s) { $status_labels[(int)$s['CON_NO']] = $s['CON_NAME']; }
$status_text = $status_labels[$st] ?? '';

// ─── Aggregations ──────────────────────────────────────────────
$total_amt = 0; $sum_t1 = 0; $sum_t2 = 0; $sum_t3 = 0; $sum_t4 = 0;
$by_branch = [];
$all_req_nos = [];
foreach ($rows as $r) {
    $eno = (int)($r['EMP_NO'] ?? 0);
    if (!$eno) continue;
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

$bs_total_amt = 0; $bs_total_emp = 0; $bs_total_trx = 0; $bs_total_branches = 0;
foreach ($bs_rows as $br) {
    $bs_total_amt += (float)($br['TOTAL_AMOUNT'] ?? 0);
    $bs_total_emp += (int)($br['EMP_COUNT']    ?? 0);
    $bs_total_trx += (int)($br['TRANSACTION_COUNT'] ?? 0);
    $bs_total_branches += (int)($br['BRANCH_COUNT'] ?? 0);
}

$all_months = []; $all_types = [];
foreach ($reqs_list as $rq) {
    $m = $rq['THE_MONTH'] ?? '';
    if ($m) $all_months[$m] = 1;
    $rt = $rq['REQ_TYPE_NAME'] ?? '';
    if ($rt) $all_types[$rt] = 1;
}
ksort($all_months);

$emp_count = (int)($bi['EMP_COUNT'] ?? 0);
$mov_count = (int)($bi['MOVEMENT_COUNT'] ?? 0);
$print_date = date('d/m/Y H:i');
// تنسيق عدد صحيح (بدون منازل عشرية) — للعدّ: موظفين/فروع/حركات
$nint = function ($v) { return number_format((int)$v, 0, '.', ','); };
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير الدفعة <?= htmlspecialchars($batch_no) ?></title>
    <link rel="stylesheet" href="<?= base_url() ?>assets-n/css/modules/payment_req/batch_print.css" type="text/css" />
</head>
<body>

<div class="print-toolbar">
    <button onclick="window.print()">🖨️ طباعة</button>
    <button class="sec" onclick="window.close()">إغلاق</button>
</div>

<div class="page">

    <!-- Letterhead -->
    <div class="letterhead">
        <div class="lh-right">
            <img src="<?= base_url() ?>assets-n/images/brand/gedco-logo.png" alt="logo">
            <div class="co">
                شركة توزيع كهرباء محافظات غزة
                <small>الإدارة المالية — دائرة الحسابات العامة</small>
            </div>
        </div>
        <div class="lh-left">
            <strong>تقرير دفعة صرف</strong><br>
            تاريخ الطباعة: <?= $print_date ?><br>
            <?php if (!empty($user_name)): ?>طُبع بواسطة: <?= htmlspecialchars($user_name) ?><?php endif; ?>
        </div>
    </div>

    <div class="doc-title">
        تفاصيل الدفعة <?= htmlspecialchars($batch_no) ?>
        <small>كشف شامل للصرف على الموظفين والبنوك والمحافظ</small>
    </div>

    <!-- Top info grid -->
    <div class="info-grid">
        <div class="info-card dark">
            <div class="lbl">رقم الدفعة</div>
            <div class="val"><?= htmlspecialchars($batch_no) ?></div>
        </div>
        <div class="info-card">
            <div class="lbl">تاريخ الإنشاء</div>
            <div class="val" style="font-size:10pt"><?= htmlspecialchars($bi['ENTRY_DATE'] ?? '') ?></div>
            <div style="font-size:8pt;color:#64748b;margin-top:1px"><?= htmlspecialchars($bi['ENTRY_USER_NAME'] ?? '') ?></div>
        </div>
        <div class="info-card">
            <div class="lbl">عدد الموظفين / الحركات</div>
            <div class="val"><?= $nint($emp_count) ?> / <?= $nint($mov_count) ?></div>
        </div>
        <div class="info-card highlight">
            <div class="lbl">إجمالي مبلغ الصرف</div>
            <div class="val"><?= n_format($total_amt) ?></div>
        </div>
    </div>

    <!-- Status + Pay info -->
    <div class="meta-line">
        <strong>حالة الدفعة:</strong>
        <span class="status-pill s<?= $st ?>"><?= htmlspecialchars($status_text) ?></span>
        <?php if ($st == 2 && !empty($bi['PAY_DATE'])): ?>
            &nbsp;&nbsp;<strong>تاريخ التنفيذ:</strong> <?= htmlspecialchars($bi['PAY_DATE']) ?>
            <?php if (!empty($bi['PAY_USER_NAME'])): ?> — منفّذ بواسطة: <?= htmlspecialchars($bi['PAY_USER_NAME']) ?><?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Months / Requests / Types -->
    <div class="section">
        <div class="meta-line">
            <strong>الشهور المغطاة:</strong>
            <?php if (empty($all_months)): ?>
                <span style="color:#94a3b8">—</span>
            <?php else: foreach ($all_months as $m => $v): $mf = strlen($m)==6 ? substr($m,4,2).'/'.substr($m,0,4) : $m; ?>
                <span class="tag month"><?= htmlspecialchars($mf) ?></span>
            <?php endforeach; endif; ?>
        </div>
        <div class="meta-line">
            <strong>الطلبات (<?= count($all_req_nos) ?>):</strong>
            <?php if (empty($all_req_nos)): ?>
                <span style="color:#94a3b8">—</span>
            <?php else: foreach ($all_req_nos as $rn => $v): ?>
                <span class="tag req"><?= htmlspecialchars($rn) ?></span>
            <?php endforeach; endif; ?>
        </div>
        <div class="meta-line">
            <strong>أنواع الصرف:</strong>
            <?php if (empty($all_types)): ?>
                <span style="color:#94a3b8">—</span>
            <?php else: foreach ($all_types as $rt => $v): ?>
                <span class="tag type"><?= htmlspecialchars($rt) ?></span>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Breakdown by location (مقر) -->
    <div class="section">
        <div class="section-title">
            تفصيل الصرف حسب المقر — تقسيم الأنواع
        </div>
        <table class="rpt">
            <thead>
                <tr>
                    <th style="width:30px">#</th>
                    <th>المقر</th>
                    <th style="width:60px">موظفين</th>
                    <th style="width:90px">راتب كامل</th>
                    <th style="width:90px">راتب جزئي</th>
                    <th style="width:100px">دفعة من المستحقات</th>
                    <th style="width:110px">استحقاقات وإضافات</th>
                    <th style="width:100px">الإجمالي</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($by_branch)): ?>
                <tr><td colspan="8" class="tbl-empty">لا توجد بيانات</td></tr>
            <?php else: $i = 0; foreach ($by_branch as $brName => $brInfo): $i++; ?>
                <tr>
                    <td class="ctr"><?= $i ?></td>
                    <td><?= htmlspecialchars($brName) ?></td>
                    <td class="ctr"><?= $nint($brInfo['count']) ?></td>
                    <td class="num"><?= $brInfo['t1'] > 0 ? n_format($brInfo['t1']) : '—' ?></td>
                    <td class="num"><?= $brInfo['t2'] > 0 ? n_format($brInfo['t2']) : '—' ?></td>
                    <td class="num"><?= $brInfo['t3'] > 0 ? n_format($brInfo['t3']) : '—' ?></td>
                    <td class="num"><?= $brInfo['t4'] > 0 ? n_format($brInfo['t4']) : '—' ?></td>
                    <td class="num"><strong><?= n_format($brInfo['total']) ?></strong></td>
                </tr>
            <?php endforeach; endif; ?>
            <tr class="total-row">
                <td colspan="2">الإجمالي العام</td>
                <td class="ctr"><?= $nint(count($rows)) ?></td>
                <td class="num"><?= $sum_t1 > 0 ? n_format($sum_t1) : '—' ?></td>
                <td class="num"><?= $sum_t2 > 0 ? n_format($sum_t2) : '—' ?></td>
                <td class="num"><?= $sum_t3 > 0 ? n_format($sum_t3) : '—' ?></td>
                <td class="num"><?= $sum_t4 > 0 ? n_format($sum_t4) : '—' ?></td>
                <td class="num"><?= n_format($total_amt) ?></td>
            </tr>
            </tbody>
        </table>
    </div>

    <!-- Bank / Wallet summary -->
    <div class="section">
        <div class="section-title">
            ملخص الصرف حسب البنك / المحفظة
        </div>
        <table class="rpt">
            <thead>
                <tr>
                    <th style="width:30px">#</th>
                    <th>الجهة المستلِمة</th>
                    <th style="width:60px">النوع</th>
                    <th style="width:60px">فروع</th>
                    <th style="width:70px">موظفين</th>
                    <th style="width:80px">الحركات</th>
                    <th style="width:110px">المبلغ</th>
                    <th style="width:60px">النسبة</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($bs_rows)): ?>
                <tr><td colspan="8" class="tbl-empty">لا توجد حركات بنكية</td></tr>
            <?php else: $i = 0; foreach ($bs_rows as $br): $i++;
                $is_wallet = ((int)($br['PROVIDER_TYPE'] ?? 1)) == 2;
                $type_label = $is_wallet ? 'محفظة' : 'بنك';
                $br_amt = (float)($br['TOTAL_AMOUNT'] ?? 0);
                $pct = $bs_total_amt > 0 ? ($br_amt / $bs_total_amt) * 100 : 0;
            ?>
                <tr>
                    <td class="ctr"><?= $i ?></td>
                    <td><strong><?= htmlspecialchars($br['BANK_NAME'] ?? '') ?></strong></td>
                    <td class="ctr"><?= $type_label ?></td>
                    <td class="ctr"><?= $nint($br['BRANCH_COUNT'] ?? 0) ?></td>
                    <td class="ctr"><?= $nint($br['EMP_COUNT']    ?? 0) ?></td>
                    <td class="ctr"><?= $nint($br['TRANSACTION_COUNT'] ?? 0) ?></td>
                    <td class="num"><strong><?= n_format($br_amt) ?></strong></td>
                    <td class="num"><?= number_format($pct, 1) ?>%</td>
                </tr>
            <?php endforeach; endif; ?>
            <tr class="total-row">
                <td colspan="3">الإجمالي العام</td>
                <td class="ctr"><?= $nint($bs_total_branches) ?></td>
                <td class="ctr"><?= $nint($emp_count) ?></td>
                <td class="ctr"><?= $nint($bs_total_trx) ?></td>
                <td class="num"><?= n_format($bs_total_amt) ?></td>
                <td class="num">100.0%</td>
            </tr>
            </tbody>
        </table>
        <?php if ($bs_total_emp > $emp_count): ?>
        <div style="font-size:8.5pt;color:#9a3412;margin-top:2px">
            * مجموع الموظفين في الجدول (<?= $nint($bs_total_emp) ?>) أكبر من الموظفين الفعليين (<?= $nint($emp_count) ?>)
            بسبب وجود <?= $nint($bs_total_emp - $emp_count) ?> موظف لديهم تقسيم على أكثر من بنك.
        </div>
        <?php endif; ?>
    </div>

    <!-- 🆕 المستوى الثالث: الطلبات حسب كل نوع -->
    <?php
    $rq_rows   = isset($batch_reqs) && is_array($batch_reqs) ? $batch_reqs : [];
    $rq_total  = 0.0;
    $rq_groups = [];   // مجمّع حسب نوع الطلب
    foreach ($rq_rows as $r) {
        $amt = (float)($r['REQ_AMOUNT'] ?? ($r['TOTAL_AMOUNT'] ?? 0));
        $rq_total += $amt;
        $gk = trim((string)($r['REQ_TYPE_NAME'] ?? ''));
        if ($gk === '') { $gk = '—'; }
        if (!isset($rq_groups[$gk])) { $rq_groups[$gk] = ['rows' => [], 'total' => 0.0, 'cnt' => 0]; }
        $rq_groups[$gk]['rows'][]  = $r;
        $rq_groups[$gk]['total']  += $amt;
        $rq_groups[$gk]['cnt']    += (int)($r['EMP_COUNT'] ?? 0);
    }
    uasort($rq_groups, function ($a, $b) { return $b['total'] <=> $a['total']; });
    foreach ($rq_groups as &$_g) {
        usort($_g['rows'], function ($a, $b) {
            return strcmp((string)($a['REQ_NO'] ?? ''), (string)($b['REQ_NO'] ?? ''));
        });
    }
    unset($_g);
    $rq_emp_total = array_sum(array_map(function ($g) { return $g['cnt']; }, $rq_groups));
    ?>
    <div class="section">
        <div class="section-title">
            تفصيل الطلبات حسب النوع
        </div>
        <table class="rpt">
            <thead>
                <tr>
                    <th style="width:30px">#</th>
                    <th style="width:95px">رقم الطلب</th>
                    <th>بيان الطلب</th>
                    <th style="width:60px">موظفين</th>
                    <th style="width:110px">المبلغ</th>
                    <th style="width:60px">النسبة</th>
                    <th style="width:200px">الملاحظات</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rq_rows)): ?>
                <tr><td colspan="7" class="tbl-empty">لا توجد طلبات</td></tr>
            <?php else: $i = 0; foreach ($rq_groups as $gName => $g):
                $gpct = $rq_total > 0 ? ($g['total'] / $rq_total) * 100 : 0;
            ?>
                <tr>
                    <td colspan="7" style="background:#1e293b;color:#fff;font-weight:700;font-size:9pt">
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <span>نوع الطلب — <?= htmlspecialchars($gName) ?></span>
                            <span><?= $nint(count($g['rows'])) ?> طلب · <?= n_format($g['total']) ?> · <?= number_format($gpct, 1) ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php foreach ($g['rows'] as $r): $i++;
                    $amt   = (float)($r['REQ_AMOUNT'] ?? ($r['TOTAL_AMOUNT'] ?? 0));
                    $pct   = $rq_total > 0 ? ($amt / $rq_total) * 100 : 0;
                    $bayan = trim((string)($r['PAY_TYPE_NAME'] ?? ''));
                    if ($bayan === '') { $bayan = '—'; }
                    $note  = trim((string)($r['NOTE'] ?? ''));
                ?>
                <tr>
                    <td class="ctr"><?= $i ?></td>
                    <td class="ctr"><strong><?= htmlspecialchars($r['REQ_NO'] ?? '') ?></strong></td>
                    <td><?= htmlspecialchars($bayan) ?></td>
                    <td class="ctr"><?= $nint($r['EMP_COUNT'] ?? 0) ?></td>
                    <td class="num"><strong><?= n_format($amt) ?></strong></td>
                    <td class="num"><?= number_format($pct, 1) ?>%</td>
                    <td style="font-size:8.5pt"><?= htmlspecialchars($note) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endforeach; endif; ?>
            <tr class="total-row">
                <td colspan="3">الإجمالي العام (<?= $nint(count($rq_rows)) ?> طلب)</td>
                <td class="ctr"><?= $nint($rq_emp_total) ?></td>
                <td class="num"><?= n_format($rq_total) ?></td>
                <td class="num">100.0%</td>
                <td></td>
            </tr>
            </tbody>
        </table>
    </div>

    <!-- Signatures -->
    <div class="sig-section">
        <div class="section-title">التواقيع والاعتمادات</div>
        <div class="sig-grid">
            <div class="sig-box">
                <div class="role">المحاسب — أعدّ الدفعة</div>
                <div class="name"><?= htmlspecialchars($bi['ENTRY_USER_NAME'] ?? '') ?></div>
            </div>
            <div class="sig-box">
                <div class="role">المدير المالي — اعتماد</div>
                <div class="name">&nbsp;</div>
            </div>
            <div class="sig-box">
                <div class="role">المدير العام — موافقة</div>
                <div class="name">&nbsp;</div>
            </div>
        </div>
    </div>

    <div class="footer">
        <span>دفعة <?= htmlspecialchars($batch_no) ?> · <?= $emp_count ?> موظف · <?= n_format($total_amt) ?> ش.ج</span>
        <span>طُبع في <?= $print_date ?></span>
    </div>

</div>

<script>
// طباعة فورية لو URL يحوي ?auto=1
(function(){
    var qs = window.location.search || '';
    if (qs.indexOf('auto=1') !== -1) {
        window.addEventListener('load', function(){
            setTimeout(function(){ window.print(); }, 300);
        });
    }
})();
</script>

</body>
</html>
