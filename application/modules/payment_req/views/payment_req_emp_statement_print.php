<?php
$emp = isset($emp_info) && is_array($emp_info) ? $emp_info : [];
$sum = isset($summary)  && is_array($summary)  ? $summary  : [];
$accs    = isset($accounts) && is_array($accounts) ? $accounts : [];
$pen     = isset($pending)  && is_array($pending)  ? $pending  : [];
$bats    = isset($batches)  && is_array($batches)  ? $batches  : [];
$rqs     = isset($rows)     && is_array($rows)     ? $rows     : [];

if (!function_exists('_esp_fmt')) {
    function _esp_fmt($n){ return number_format((float)$n, 2, '.', ','); }
    function _esp_e($s){ return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }
    function _esp_money($n){ return $n === null || !is_numeric($n) || !is_finite((float)$n) ? 'غير متوفر' : _esp_fmt($n); }
}
if (!function_exists('payment_req_report_cell')) require_once __DIR__ . '/../helpers/payment_req_report_helper.php';
$request_report = isset($report) && is_array($report) ? $report : payment_req_build_report($rqs, 'statement')['report'];
$request_columns = $request_report['columns'];
$request_landscape = count($request_columns) > 8;
$request_summary = $request_report['summary'];
$request_measure_label = $request_report['measure']['label'] ?? ($request_summary['amount_label'] ?? 'المبلغ');
$request_money_columns = array_filter($request_columns, function($column){ return $column['kind'] === 'money'; });
$request_status_labels = ['0'=>'مسودة','1'=>'معتمد','2'=>'منفّذ للصرف','3'=>'معتمد جزئياً','4'=>'محتسب','9'=>'ملغى','unknown'=>'حالة غير متوفرة'];

// الحالة المركّبة للموظف
$st = (int)($emp['DISPLAY_STATUS'] ?? 1);
$st_map = [
    1 => ['label' => 'فعّال',       'color' => '#15803d', 'bg' => '#d1fae5'],
    0 => ['label' => 'متقاعد',      'color' => '#9a3412', 'bg' => '#ffedd5'],
    2 => ['label' => 'متوفى',       'color' => '#991b1b', 'bg' => '#fee2e2'],
    4 => ['label' => 'حساب مغلق',   'color' => '#92400e', 'bg' => '#fef3c7'],
];
$st_meta = $st_map[$st] ?? $st_map[1];

// أرقام ملخص الأرصدة — من PAYMENT_REQ_SUMMARY_GET (DUES_*)
$bal_basic   = (float)($sum['DUES_BASE']       ?? 0);
$bal_add     = (float)($sum['DUES_ADD']        ?? 0);
$bal_ded     = (float)($sum['DUES_DED']        ?? 0);
$bal_held    = (float)($sum['DUES_RESERVED']   ?? 0);
$bal_paid    = (float)($sum['DUES_PAID']       ?? 0);
$bal_avail   = (float)($sum['DUES_AVAILABLE']  ?? 0);

// فترة الكشف
$period_label = '';
if (!empty($month_from) && !empty($month_to)) $period_label = "من $month_from إلى $month_to";
elseif (!empty($month_from)) $period_label = "من $month_from";
elseif (!empty($month_to))   $period_label = "حتى $month_to";
else $period_label = 'كل الفترات';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title>كشف حساب الموظف <?= _esp_e($emp_no) ?> — <?= _esp_e($request_measure_label) ?></title>
<style>
    @page { size: A4 <?= $request_landscape ? 'landscape' : 'portrait' ?>; margin: 12mm 14mm; }
    * { box-sizing: border-box; }
    body { font-family: 'Cairo','Tahoma','Arial',sans-serif; color:#1e293b; font-size: 10.5pt; line-height:1.45; background:#fff; margin:0; padding:0; }

    .toolbar { background:#0f172a; color:#fff; padding:10px 16px; display:flex; justify-content:space-between; align-items:center; }
    .toolbar button { background:#3b82f6; color:#fff; border:0; padding:6px 14px; border-radius:6px; cursor:pointer; font-size:.9rem; }
    .toolbar button:hover { background:#2563eb; }
    .toolbar .close-btn { background:#64748b; }

    .page { max-width: <?= $request_landscape ? '297' : '210' ?>mm; margin: 0 auto; padding: 8mm 6mm; }

    /* Letterhead */
    .letterhead { display:flex; justify-content:space-between; align-items:center; border-bottom: 2.5px solid #1e293b; padding-bottom:8px; margin-bottom:12px; }
    .lh-right { display:flex; align-items:center; gap:12px; }
    .lh-right img { height:54px; }
    .co { font-size:13pt; font-weight:800; line-height:1.2; }
    .co small { display:block; font-size:9pt; font-weight:500; color:#64748b; margin-top:2px; }
    .lh-left { text-align:left; font-size:8.5pt; color:#64748b; }
    .lh-left .doc-no { font-size:9.5pt; color:#1e293b; font-weight:700; margin-bottom:2px; }

    .doc-title { text-align:center; font-size:13pt; font-weight:800; background:#1e293b; color:#fff; padding:6px; margin:6px 0 10px; border-radius:4px; }

    /* Employee card */
    .emp-card { display:grid; grid-template-columns: auto 1fr auto; gap:14px; align-items:center; padding:10px 14px; background:#f8fafc; border:1.5px solid #cbd5e1; border-radius:8px; margin-bottom:10px; }
    .emp-avatar { width:54px; height:54px; border-radius:50%; background:linear-gradient(135deg,#3b82f6,#1e40af); color:#fff; display:flex; align-items:center; justify-content:center; font-size:20pt; font-weight:800; }
    .emp-main .emp-name { font-size:13pt; font-weight:800; color:#0f172a; }
    .emp-main .emp-sub { font-size:8.5pt; color:#64748b; margin-top:3px; display:flex; gap:14px; flex-wrap:wrap; }
    .emp-main .emp-sub b { color:#0f172a; font-weight:700; }
    .emp-status { padding:4px 12px; border-radius:14px; font-size:9pt; font-weight:700; }

    /* Section */
    .sec { margin-bottom:10px; page-break-inside: avoid; }
    .sec-title { background:#eff6ff; border-right:3px solid #1e40af; padding:5px 10px; font-size:10pt; font-weight:700; color:#1e40af; margin-bottom:5px; display:flex; justify-content:space-between; align-items:center; }
    .sec-title.bat   { background:#ecfdf5; border-right-color:#065f46; color:#065f46; }
    .sec-title.acc   { background:#fef3c7; border-right-color:#92400e; color:#92400e; }
    .sec-title.req   { background:#f3e8ff; border-right-color:#6d28d9; color:#6d28d9; }
    .sec-title.pend  { background:#fef9c3; border-right-color:#a16207; color:#a16207; }
    .sec-title small { font-weight:500; opacity:.7; font-size:8pt; }

    /* Balance cards */
    .bal-row { display:grid; grid-template-columns: repeat(6, 1fr); gap:5px; }
    .bal-card { border:1.5px solid; border-radius:5px; padding:6px; text-align:center; background:#fff; }
    .bal-lbl  { font-size:7.5pt; font-weight:600; margin-bottom:2px; }
    .bal-val  { font-size:11pt; font-weight:800; color:#0f172a; direction:ltr; }
    .b-basic { border-color:#1e40af; } .b-basic .bal-lbl { color:#1e40af; }
    .b-add   { border-color:#15803d; } .b-add   .bal-lbl { color:#15803d; }
    .b-ded   { border-color:#dc2626; } .b-ded   .bal-lbl { color:#dc2626; }
    .b-held  { border-color:#a16207; } .b-held  .bal-lbl { color:#a16207; }
    .b-paid  { border-color:#64748b; } .b-paid  .bal-lbl { color:#64748b; }
    .b-avail { border-color:#065f46; background:#f0fdf4; } .b-avail .bal-lbl { color:#065f46; } .b-avail .bal-val { color:#065f46; }

    /* Tables */
    table { width:100%; border-collapse: collapse; font-size:8.8pt; }
    table th, table td { border:1px solid #cbd5e1; padding:4px 6px; }
    table th { background:#f1f5f9; font-weight:700; font-size:8.2pt; color:#334155; text-align:center; }
    table tfoot td { background:#1e293b; color:#fff; font-weight:800; font-size:9pt; }
    table .num { text-align:left; direction:ltr; font-family: 'Consolas','Courier New',monospace; }
    table .ctr { text-align:center; }
    .request-table { font-size:8.5pt; table-layout:fixed; }
    .request-table.compact { font-size:8.8pt; }
    .request-table th, .request-table td { padding:3px; overflow-wrap:anywhere; }
    .request-table th { font-size:8pt; }
    .request-table .basis { font-size:7pt; color:#64748b; margin-top:3px; }
    .request-summary { display:grid; grid-template-columns:repeat(2,1fr); gap:5px; margin:6px 0; }
    .request-summary div { border:1px solid #cbd5e1; border-radius:4px; padding:6px; text-align:center; font-size:8pt; }
    .request-summary b { display:block; font-size:11pt; direction:ltr; margin-top:3px; }
    .request-statuses { font-size:7.5pt; display:flex; flex-wrap:wrap; gap:6px 14px; margin:5px 0; }

    .badge { display:inline-block; padding:1px 6px; border-radius:7px; font-size:7.5pt; font-weight:700; }
    .b-bnk { background:#dbeafe; color:#1e40af; }
    .b-wlt { background:#ede9fe; color:#6d28d9; }
    .b-bn  { background:#fef3c7; color:#d97706; }
    .b-1   { background:#d1fae5; color:#065f46; }   /* فعّال */
    .b-2   { background:#fee2e2; color:#991b1b; }   /* متوفى */
    .b-0   { background:#ffedd5; color:#9a3412; }   /* متقاعد */
    .b-9   { background:#fee2e2; color:#991b1b; }   /* ملغى */

    /* Accounts */
    .acc-grid { display:grid; grid-template-columns: 1fr 1fr; gap:6px; }
    .acc-card { border:1px solid #cbd5e1; border-radius:5px; padding:7px 9px; background:#fff; font-size:8.5pt; }
    .acc-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:3px; }
    .acc-bank { font-weight:700; color:#0f172a; }
    .acc-bank .ic { margin-left:4px; }
    .acc-tag  { font-size:7.5pt; padding:1px 6px; border-radius:8px; }
    .acc-line { color:#475569; margin-top:2px; }
    .acc-line .lbl { color:#94a3b8; font-size:7.5pt; }
    .acc-line.mono { font-family:Consolas,monospace; direction:ltr; }

    /* Footer */
    .signatures { display:grid; grid-template-columns: 1fr 1fr 1fr; gap:14px; margin-top:14px; padding-top:10px; border-top:1.5px dashed #94a3b8; }
    .sig-box { text-align:center; }
    .sig-box .sig-lbl { font-size:9pt; color:#475569; font-weight:600; margin-bottom:25px; }
    .sig-box .sig-line { border-top:1.5px solid #1e293b; padding-top:3px; font-size:8pt; color:#64748b; }

    .foot-note { margin-top:8px; padding:5px 10px; font-size:7.5pt; color:#64748b; text-align:center; border-top:1px dashed #cbd5e1; }

    @media print {
        .toolbar, .no-print { display:none !important; }
        body { background:#fff; }
        .page { max-width: 100%; padding: 0; }
    }
</style>
</head>
<body>

<div class="toolbar no-print">
    <strong>كشف حساب الموظف <?= _esp_e($emp_no) ?></strong>
    <div>
        <button onclick="window.print()">🖨 طباعة</button>
        <button class="close-btn" onclick="window.close()">إغلاق</button>
    </div>
</div>

<div class="page">

    <!-- Letterhead -->
    <div class="letterhead">
        <div class="lh-right">
            <div class="co">
                شركة توزيع كهرباء محافظات غزة
                <small>الإدارة المالية — دائرة الحسابات العامة</small>
            </div>
        </div>
        <div class="lh-left">
            <div class="doc-no">كشف حساب موظف</div>
            <div>الفترة: <?= _esp_e($period_label) ?></div>
            <div>تاريخ الطباعة: <?= date('Y-m-d H:i') ?></div>
        </div>
    </div>

    <div class="doc-title">كشف حساب الموظف — <?= _esp_e($request_measure_label) ?></div>

    <!-- Employee Card -->
    <div class="emp-card">
        <div class="emp-avatar"><?= _esp_e(mb_substr($emp['EMP_NAME'] ?? '?', 0, 1)) ?></div>
        <div class="emp-main">
            <div class="emp-name"><?= _esp_e($emp['EMP_NAME'] ?? '—') ?> <span style="color:#94a3b8;font-size:10pt">— رقم <?= _esp_e($emp_no) ?></span></div>
            <div class="emp-sub">
                <?php if (!empty($emp['NAT_ID'])): ?><span>الهوية: <b><?= _esp_e($emp['NAT_ID']) ?></b></span><?php endif; ?>
                <?php if (!empty($emp['BRANCH_NAME'])): ?><span>المقر: <b><?= _esp_e($emp['BRANCH_NAME']) ?></b></span><?php endif; ?>
                <?php if (!empty($emp['HR_BANK_NAME'])): ?><span>بنك HR: <b><?= _esp_e($emp['HR_BANK_NAME']) ?></b></span><?php endif; ?>
                <?php if (!empty($emp['TEL'])): ?><span>الجوال: <b dir="ltr"><?= _esp_e($emp['TEL']) ?></b></span><?php endif; ?>
            </div>
        </div>
        <div class="emp-status" style="background:<?= $st_meta['bg'] ?>;color:<?= $st_meta['color'] ?>"><?= _esp_e($st_meta['label']) ?></div>
    </div>

    <div class="sec">
        <div class="sec-title req">ملخص الطلبات — <?= _esp_e($request_measure_label) ?> <small><?= _esp_e($period_label) ?></small></div>
        <p style="font-size:8pt"><?= _esp_e($request_report['filter_label'] ?? '') ?> — عرض <?= (int)$request_summary['count'] ?> من <?= (int)$request_report['source_count'] ?> سجل في الفترة.</p>
        <div class="request-summary">
            <div>عدد الطلبات<b><?= (int)$request_summary['count'] ?></b></div>
            <div><?= _esp_e($request_measure_label) ?><b><?= _esp_money($request_summary['amount'] ?? null) ?></b></div>
        </div>
        <div class="request-statuses">
            <?php foreach ((array)($request_summary['by_status'] ?? []) as $code => $entry): ?>
                <span><?= _esp_e($request_status_labels[(string)$code] ?? 'حالة غير متوفرة') ?>: <?= (int)$entry['count'] ?> — <?= _esp_money($entry['amount'] ?? null) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <!-- ملخص الأرصدة -->
    <div class="sec">
        <div class="sec-title">⚖ ملخص الأرصدة <small>كل الفترات</small></div>
        <div class="bal-row">
            <div class="bal-card b-basic"><div class="bal-lbl">الأساسي</div><div class="bal-val"><?= _esp_fmt($bal_basic) ?></div></div>
            <div class="bal-card b-add">  <div class="bal-lbl">+ إضافات</div><div class="bal-val"><?= _esp_fmt($bal_add) ?></div></div>
            <div class="bal-card b-ded">  <div class="bal-lbl">− خصومات</div><div class="bal-val"><?= _esp_fmt($bal_ded) ?></div></div>
            <div class="bal-card b-held"> <div class="bal-lbl">محجوز</div><div class="bal-val"><?= _esp_fmt($bal_held) ?></div></div>
            <div class="bal-card b-paid"> <div class="bal-lbl">مصروف سابقاً</div><div class="bal-val"><?= _esp_fmt($bal_paid) ?></div></div>
            <div class="bal-card b-avail"><div class="bal-lbl">المتاح للصرف</div><div class="bal-val"><?= _esp_fmt($bal_avail) ?></div></div>
        </div>
    </div>

    <!-- حسابات الصرف -->
    <?php if (!empty($accs)): ?>
    <div class="sec">
        <div class="sec-title acc">💳 حسابات الصرف — كل الفترات <small>(<?= count($accs) ?>)</small></div>
        <div class="acc-grid">
            <?php foreach ($accs as $a):
                $isWlt = (int)($a['PROVIDER_TYPE'] ?? 1) === 2;
                $isDef = (int)($a['IS_DEFAULT'] ?? 0) === 1;
                $accNum = $a['ACCOUNT_NO'] ?? ($a['WALLET_NUMBER'] ?? '—');
            ?>
            <div class="acc-card">
                <div class="acc-head">
                    <span class="acc-bank">
                        <span class="ic"><?= $isWlt ? '📱' : '🏦' ?></span>
                        <?= _esp_e($a['PROVIDER_NAME'] ?? '—') ?>
                        <?php if ($isDef): ?><span style="color:#f59e0b">★</span><?php endif; ?>
                    </span>
                    <?php
                    $split_label = ['1'=>'نسبة','2'=>'مبلغ ثابت','3'=>'كامل الباقي'][(int)($a['SPLIT_TYPE'] ?? 3)] ?? '';
                    $split_val = (float)($a['SPLIT_VALUE'] ?? 0);
                    ?>
                    <span class="acc-tag" style="background:<?= $isWlt ? '#ede9fe' : '#dbeafe' ?>;color:<?= $isWlt ? '#6d28d9' : '#1e40af' ?>">
                        <?= _esp_e($split_label) ?><?php if ($split_val > 0): ?>: <?= _esp_fmt($split_val) ?><?php endif; ?>
                    </span>
                </div>
                <div class="acc-line mono"><span class="lbl">#</span> <?= _esp_e($accNum) ?>
                    <?php if (!empty($a['BANK_BRANCH_NAME'])): ?> — <?= _esp_e($a['BANK_BRANCH_NAME']) ?><?php endif; ?>
                </div>
                <?php if (!empty($a['IBAN']) && !$isWlt): ?>
                    <div class="acc-line mono"><span class="lbl">IBAN:</span> <?= _esp_e($a['IBAN']) ?></div>
                <?php endif; ?>
                <?php if (!empty($a['OWNER_NAME']) && $a['OWNER_NAME'] !== ($emp['EMP_NAME'] ?? '')): ?>
                    <div class="acc-line">المالك: <b><?= _esp_e($a['OWNER_NAME']) ?></b>
                        <?php if (!empty($a['REL_NAME'])): ?> (<?= _esp_e($a['REL_NAME']) ?>)<?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- الدفعات والتوزيع — تُستثنى الملغاة (BATCH_STATUS=9) لأنها لم تُصرف فعلاً -->
    <?php
    $bats_real = array_values(array_filter($bats, function ($b) {
        return (int)($b['BATCH_STATUS'] ?? 0) !== 9;
    }));
    ?>
    <?php if (!empty($bats_real)): ?>
    <div class="sec">
        <div class="sec-title bat">🗂 الدفعات والتوزيع — كل الفترات <small>(<?= count($bats_real) ?>)</small></div>
        <table>
            <thead>
                <tr>
                    <th style="width:24px">#</th>
                    <th style="width:80px">رقم الدفعة</th>
                    <th style="width:75px">الإنشاء</th>
                    <th style="width:75px">الصرف</th>
                    <th style="width:60px">الحالة</th>
                    <th class="num" style="width:85px">الإجمالي</th>
                    <th class="num" style="width:80px">للبنك</th>
                    <th class="num" style="width:80px">للمحفظة</th>
                    <th class="num" style="width:80px">للوريث</th>
                </tr>
            </thead>
            <tbody>
                <?php $tb=0;$tBnk=0;$tWlt=0;$tBn=0;
                foreach ($bats_real as $i => $b):
                    $bst = (int)($b['BATCH_STATUS'] ?? 0);
                    $stCls = 'b-' . $bst;
                    $tb   += (float)($b['TOTAL_AMOUNT'] ?? 0);
                    $tBnk += (float)($b['BANK_AMT']     ?? 0);
                    $tWlt += (float)($b['WALLET_AMT']   ?? 0);
                    $tBn  += (float)($b['BENEF_AMT']    ?? 0);
                ?>
                <tr>
                    <td class="ctr"><?= $i + 1 ?></td>
                    <td><b><?= _esp_e($b['BATCH_NO'] ?? '') ?></b></td>
                    <td class="ctr"><?= _esp_e($b['BATCH_DATE'] ?? '—') ?></td>
                    <td class="ctr"><?= _esp_e($b['PAY_DATE']   ?? '—') ?></td>
                    <td class="ctr"><span class="badge <?= $stCls ?>"><?= _esp_e($b['STATUS_NAME'] ?? '—') ?></span></td>
                    <td class="num"><?= _esp_fmt($b['TOTAL_AMOUNT'] ?? 0) ?></td>
                    <td class="num" style="color:#1e40af"><?= ((float)($b['BANK_AMT'] ?? 0) > 0) ? _esp_fmt($b['BANK_AMT']) : '—' ?></td>
                    <td class="num" style="color:#6d28d9"><?= ((float)($b['WALLET_AMT'] ?? 0) > 0) ? _esp_fmt($b['WALLET_AMT']) : '—' ?></td>
                    <td class="num" style="color:#d97706"><?= ((float)($b['BENEF_AMT'] ?? 0) > 0) ? _esp_fmt($b['BENEF_AMT']) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align:left">الإجمالي</td>
                    <td class="num"><?= _esp_fmt($tb)   ?></td>
                    <td class="num"><?= _esp_fmt($tBnk) ?></td>
                    <td class="num"><?= _esp_fmt($tWlt) ?></td>
                    <td class="num"><?= _esp_fmt($tBn)  ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>

    <!-- الدفعات المحتسبة (محجوز — لم تُنفّذ بعد) -->
    <?php if (!empty($pen)): ?>
    <div class="sec">
        <div class="sec-title pend">⏳ دفعات محتسبة لم تُنفّذ بعد — كل الفترات <small>(<?= count($pen) ?>)</small></div>
        <table>
            <thead><tr><th style="width:24px">#</th><th>رقم الدفعة</th><th>التاريخ</th><th class="num" style="width:110px">المبلغ المحجوز</th></tr></thead>
            <tbody>
                <?php foreach ($pen as $i => $p): ?>
                <tr>
                    <td class="ctr"><?= $i + 1 ?></td>
                    <td><b><?= _esp_e($p['BATCH_NO'] ?? '') ?></b></td>
                    <td class="ctr"><?= _esp_e($p['BATCH_DATE'] ?? '—') ?></td>
                    <td class="num"><?= _esp_fmt($p['EMP_AMOUNT'] ?? 0) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- سجل الطلبات -->
    <div class="sec">
        <div class="sec-title req">📋 سجل الطلبات — <?= _esp_e($request_measure_label) ?> <small>(<?= (int)$request_summary['count'] ?>) — <?= _esp_e($period_label) ?></small></div>
        <table class="request-table<?= count($request_columns) <= 8 ? ' compact' : '' ?>">
            <thead>
                <tr>
                    <?php foreach ($request_columns as $column): ?>
                    <th<?= $column['kind'] === 'sequence' ? ' style="width:24px"' : '' ?>><?= _esp_e($column['label']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rqs as $i => $r): ?>
                <tr>
                    <?php foreach ($request_columns as $column):
                        $value = payment_req_report_cell($r, $column, $i + 1);
                        $money = $column['kind'] === 'money'; ?>
                    <td class="<?= $money ? 'num' : ($column['kind'] === 'sequence' ? 'ctr' : '') ?>"><?= $money && is_numeric($value) ? _esp_money($value) : _esp_e($value) ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($rqs)): ?><tr><td colspan="<?= count($request_columns) ?>" class="ctr">لا توجد طلبات مطابقة للفترة وخيارات العرض المختارة.</td></tr><?php endif; ?>
            </tbody>
            <tfoot>
                <tr><td colspan="<?= count($request_columns) ?>">المجموع (<?= _esp_e($request_measure_label) ?>) — <?= (int)$request_summary['count'] ?> سجل</td></tr>
                <?php if ($request_money_columns): ?><tr>
                    <?php foreach ($request_columns as $column): ?>
                    <td<?= $column['kind'] === 'money' ? ' class="num"' : '' ?>><?= $column['kind'] === 'money' ? _esp_money($request_summary['amount'] ?? null) : '' ?></td>
                    <?php endforeach; ?>
                </tr><?php endif; ?>
            </tfoot>
        </table>
    </div>

    <!-- التوقيعات -->
    <div class="signatures">
        <div class="sig-box"><div class="sig-lbl">المُحاسب</div><div class="sig-line">الاسم والتوقيع</div></div>
        <div class="sig-box"><div class="sig-lbl">مدير الإدارة المالية</div><div class="sig-line">الاسم والتوقيع</div></div>
        <div class="sig-box"><div class="sig-lbl">الموظف</div><div class="sig-line">الاسم والتوقيع — اطّلعت</div></div>
    </div>

    <div class="foot-note">
        طُبع بتاريخ <?= date('Y-m-d H:i') ?>
    </div>

</div>

<script>
    (function(){
        var u = new URL(window.location.href);
        if (u.searchParams.get('auto') === '1' || <?= json_encode(isset($_POST['auto']) && (string)$_POST['auto'] === '1') ?>) {
            setTimeout(function(){ window.print(); }, 400);
        }
    })();
</script>

</body>
</html>
