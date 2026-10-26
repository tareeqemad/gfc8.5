<?php
$t   = isset($totals) && is_array($totals) ? $totals : [];
$bi  = isset($batch_info) && is_array($batch_info) ? $batch_info : [];
$ei  = isset($emp_info)   && is_array($emp_info)   ? $emp_info   : [];
$rqs = isset($requests)     && is_array($requests)     ? $requests     : [];
$dst = isset($destinations) && is_array($destinations) ? $destinations : [];

$batch_no   = $bi['BATCH_NO']   ?? ('#' . ($batch_id ?? '?'));
$batch_date = $bi['BATCH_DATE'] ?? '';
$pay_date   = $bi['PAY_DATE']   ?? '';

$emp_name   = $ei['EMP_NAME'] ?? '';
$emp_id     = $ei['NAT_ID'] ?? ($ei['ID_NO'] ?? ($ei['EMP_ID'] ?? ''));
$emp_branch = $ei['BRANCH_NAME'] ?? '';

$tot_req    = (float)($t['requests']     ?? 0);
$tot_dest   = (float)($t['destinations'] ?? 0);
$sum_bank   = (float)($t['sum_bank']     ?? 0);
$sum_wallet = (float)($t['sum_wallet']   ?? 0);
$sum_benef  = (float)($t['sum_benef']    ?? 0);
$match      = !empty($t['match']);

function _ebp_fmt($n){ return number_format((float)$n, 2, '.', ','); }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title>كشف الموظف <?= htmlspecialchars($emp_no) ?> — الدفعة <?= htmlspecialchars($batch_no) ?></title>
<style>
    @page { size: A4; margin: 12mm 14mm; }
    * { box-sizing: border-box; }
    body { font-family: 'Cairo','Tahoma','Arial',sans-serif; color:#1e293b; font-size: 12pt; line-height:1.5; background:#fff; margin:0; padding:0; }

    .toolbar { background:#0f172a; color:#fff; padding:10px 16px; display:flex; justify-content:space-between; align-items:center; }
    .toolbar button { background:#3b82f6; color:#fff; border:0; padding:6px 14px; border-radius:6px; cursor:pointer; font-size:.9rem; }
    .toolbar button:hover { background:#2563eb; }
    .toolbar .close-btn { background:#64748b; }

    .page { max-width: 210mm; margin: 0 auto; padding: 8mm 6mm; }

    /* Letterhead */
    .letterhead { display:flex; justify-content:space-between; align-items:center; border-bottom: 2.5px solid #1e293b; padding-bottom:8px; margin-bottom:14px; }
    .lh-right { display:flex; align-items:center; gap:12px; }
    .lh-right img { height: 56px; }
    .co { font-size:14pt; font-weight:800; line-height:1.2; }
    .co small { display:block; font-size:9pt; font-weight:500; color:#64748b; margin-top:2px; }
    .lh-left { text-align:left; font-size:9pt; color:#64748b; }
    .lh-left .doc-no { font-size:10pt; color:#1e293b; font-weight:700; margin-bottom:2px; }

    /* Title */
    .doc-title { text-align:center; font-size:13pt; font-weight:800; background:#1e293b; color:#fff; padding:8px; margin:8px 0 14px; border-radius:4px; }

    /* Info rows */
    .info-grid { display:grid; grid-template-columns: 1fr 1fr; gap:6px 14px; padding:10px 12px; background:#f8fafc; border:1px solid #cbd5e1; border-radius:6px; margin-bottom:12px; }
    .info-grid .row { display:flex; gap:6px; align-items:baseline; }
    .info-grid .lbl { font-size:9pt; color:#64748b; font-weight:600; min-width:80px; }
    .info-grid .val { font-size:10pt; color:#0f172a; font-weight:700; }

    /* Summary cards */
    .sum-cards { display:grid; grid-template-columns: repeat(4, 1fr); gap:6px; margin-bottom:12px; }
    .sum-card { border:1.5px solid; border-radius:6px; padding:8px; text-align:center; background:#fff; }
    .sc-lbl { font-size:8pt; font-weight:600; margin-bottom:2px; }
    .sc-val { font-size:13pt; font-weight:800; color:#0f172a; direction:ltr; }
    .sc-tot   { border-color:#1e293b; }     .sc-tot   .sc-lbl { color:#1e293b; }
    .sc-bank  { border-color:#1e40af; }     .sc-bank  .sc-lbl { color:#1e40af; }
    .sc-wlt   { border-color:#6d28d9; }     .sc-wlt   .sc-lbl { color:#6d28d9; }
    .sc-benf  { border-color:#d97706; }     .sc-benf  .sc-lbl { color:#d97706; }

    /* Section */
    .sec { margin-bottom: 14px; }
    .sec-title { background:#eff6ff; border-right:4px solid #1e40af; padding:5px 10px; font-size:10pt; font-weight:700; color:#1e40af; margin-bottom:5px; }
    .sec-title.dest { background:#ecfdf5; border-right-color:#065f46; color:#065f46; }
    .sec-sub { font-size:8.5pt; font-weight:500; opacity:.7; }

    /* Tables */
    table { width:100%; border-collapse: collapse; font-size:9.5pt; }
    table th, table td { border:1px solid #cbd5e1; padding:5px 7px; }
    table th { background:#f1f5f9; font-weight:700; font-size:9pt; color:#334155; }
    table tfoot td { background:#1e293b; color:#fff; font-weight:800; font-size:10pt; }
    table .num { text-align:left; direction:ltr; font-family: 'Consolas','Courier New',monospace; }
    table .ctr { text-align:center; }
    .badge-mini { display:inline-block; background:#dbeafe; color:#1e40af; padding:1px 6px; border-radius:8px; font-size:8pt; font-weight:700; }
    .badge-wlt  { background:#ede9fe; color:#6d28d9; }
    .badge-benf { background:#fef3c7; color:#d97706; }
    .badge-merge { background:#dcfce7; color:#166534; font-size:7.5pt; margin-right:4px; }

    /* Match indicator */
    .match-banner { padding:6px 10px; border-radius:5px; font-size:10pt; font-weight:700; text-align:center; margin: 8px 0; }
    .match-ok   { background:#f0fdf4; color:#065f46; border:1px solid #a7f3d0; }
    .match-fail { background:#fef2f2; color:#991b1b; border:1px solid #fca5a5; }

    /* Signature area */
    .signatures { display:grid; grid-template-columns: 1fr 1fr 1fr; gap:14px; margin-top:18px; padding-top:10px; border-top:1.5px dashed #94a3b8; }
    .sig-box { text-align:center; }
    .sig-box .sig-lbl { font-size:9pt; color:#475569; font-weight:600; margin-bottom:30px; }
    .sig-box .sig-line { border-top:1.5px solid #1e293b; padding-top:3px; font-size:8pt; color:#64748b; }

    .foot-note { margin-top:10px; padding:6px 10px; font-size:8pt; color:#64748b; text-align:center; border-top:1px dashed #cbd5e1; }

    @media print {
        .toolbar, .no-print { display:none !important; }
        body { background:#fff; }
        .page { max-width: 100%; padding: 0; }
        .sum-cards { page-break-inside: avoid; }
        .sec { page-break-inside: avoid; }
        .signatures { page-break-inside: avoid; }
    }
</style>
</head>
<body>

<div class="toolbar no-print">
    <strong><i></i> كشف توزيع — الموظف <?= htmlspecialchars($emp_no) ?> / الدفعة <?= htmlspecialchars($batch_no) ?></strong>
    <div>
        <button onclick="window.print()"><i></i> 🖨 طباعة</button>
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
            <div class="doc-no">رقم الدفعة: <?= htmlspecialchars($batch_no) ?></div>
            <div>تاريخ الإنشاء: <?= htmlspecialchars($batch_date ?: '—') ?></div>
            <?php if (!empty($pay_date)): ?>
            <div>تاريخ الصرف: <?= htmlspecialchars($pay_date) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="doc-title">كشف توزيع مبلغ الصرف للموظف</div>

    <!-- معلومات الموظف -->
    <div class="info-grid">
        <div class="row"><span class="lbl">اسم الموظف:</span> <span class="val"><?= htmlspecialchars($emp_name) ?></span></div>
        <div class="row"><span class="lbl">رقم الموظف:</span> <span class="val"><?= htmlspecialchars($emp_no) ?></span></div>
        <div class="row"><span class="lbl">رقم الهوية:</span> <span class="val"><?= htmlspecialchars($emp_id) ?></span></div>
        <div class="row"><span class="lbl">المقر:</span> <span class="val"><?= htmlspecialchars($emp_branch ?: '—') ?></span></div>
    </div>

    <!-- بطاقات الملخص -->
    <div class="sum-cards">
        <div class="sum-card sc-tot">
            <div class="sc-lbl">إجمالي المُستحَق (<?= count($rqs) ?> طلب)</div>
            <div class="sc-val"><?= _ebp_fmt($tot_req) ?></div>
        </div>
        <div class="sum-card sc-bank">
            <div class="sc-lbl">🏦 تحويلات بنكية</div>
            <div class="sc-val"><?= _ebp_fmt($sum_bank) ?></div>
        </div>
        <div class="sum-card sc-wlt">
            <div class="sc-lbl">📱 تحويلات محافظ</div>
            <div class="sc-val"><?= _ebp_fmt($sum_wallet) ?></div>
        </div>
        <div class="sum-card sc-benf">
            <div class="sc-lbl">👥 إيداع مستفيدين / ورثة</div>
            <div class="sc-val"><?= _ebp_fmt($sum_benef) ?></div>
        </div>
    </div>

    <!-- الطلبات المساهمة -->
    <div class="sec">
        <div class="sec-title">
            📋 الطلبات المساهمة <span class="sec-sub">— مصدر المبلغ المُستحَق</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width:30px">#</th>
                    <th>رقم الطلب</th>
                    <th style="width:100px">الشهر</th>
                    <th>نوع الطلب</th>
                    <th style="width:110px" class="num">المبلغ المعتمد</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rqs)): ?>
                    <tr><td colspan="5" class="ctr" style="color:#94a3b8;padding:14px">لا توجد طلبات</td></tr>
                <?php else: foreach ($rqs as $i => $r): ?>
                    <tr>
                        <td class="ctr"><?= $i + 1 ?></td>
                        <td><b><?= htmlspecialchars($r['req_no']) ?></b></td>
                        <td class="ctr"><?= htmlspecialchars($r['month']) ?></td>
                        <td><span class="badge-mini"><?= htmlspecialchars($r['type_name']) ?></span></td>
                        <td class="num"><?= _ebp_fmt($r['amount']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <tfoot>
                <tr><td colspan="4" style="text-align:left">إجمالي المُستحَق</td>
                    <td class="num"><?= _ebp_fmt($tot_req) ?></td></tr>
            </tfoot>
        </table>
    </div>

    <!-- Match indicator -->
    <?php if ($match): ?>
        <div class="match-banner match-ok">✓ المبالغ متطابقة — مجموع الطلبات يساوي مجموع الوجهات</div>
    <?php else: ?>
        <div class="match-banner match-fail">⚠ فرق بين الطلبات والوجهات: <?= _ebp_fmt($tot_req - $tot_dest) ?> — يُرجى المراجعة</div>
    <?php endif; ?>

    <!-- حسابات الإيداع والتحويل -->
    <div class="sec">
        <div class="sec-title dest">
            🏦 حسابات الإيداع والتحويل <span class="sec-sub">— تفصيل المبالغ المُحوَّلة للبنوك والمحافظ والمستفيدين</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width:30px">#</th>
                    <th style="width:90px">النوع</th>
                    <th>جهة الإيداع (بنك / محفظة / مستفيد)</th>
                    <th>صاحب الحساب</th>
                    <th>رقم الحساب / IBAN / الجوال</th>
                    <th style="width:110px" class="num">المبلغ المُحوَّل</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dst)): ?>
                    <tr><td colspan="6" class="ctr" style="color:#94a3b8;padding:14px">لا توجد وجهات</td></tr>
                <?php else: foreach ($dst as $i => $d):
                    $cat = $d['category'];
                    if ($cat === 'WALLET')      { $badgeClass = 'badge-wlt';  $icon = '📱'; }
                    elseif ($cat === 'BENEF')   { $badgeClass = 'badge-benf'; $icon = '👥'; }
                    else                        { $badgeClass = '';            $icon = '🏦'; }
                ?>
                    <tr>
                        <td class="ctr"><?= $i + 1 ?></td>
                        <td><span class="badge-mini <?= $badgeClass ?>"><?= $icon ?> <?= htmlspecialchars($d['type_name']) ?></span></td>
                        <td>
                            <b><?= htmlspecialchars($d['label']) ?></b>
                            <?php if ((int)($d['merged_count'] ?? 0) > 1): ?>
                                <span class="badge-mini badge-merge">🔗 دمج <?= (int)$d['merged_count'] ?></span>
                            <?php endif; ?>
                            <?php if (!empty($d['sub_label'])): ?>
                                <br><small style="color:#64748b"><?= htmlspecialchars($d['sub_label']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($d['owner'] ?: '—') ?></td>
                        <td class="num" style="font-size:8.5pt"><?= htmlspecialchars($d['account'] ?: '—') ?></td>
                        <td class="num"><?= _ebp_fmt($d['amount']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <tfoot>
                <tr><td colspan="5" style="text-align:left">إجمالي المُحوَّل</td>
                    <td class="num"><?= _ebp_fmt($tot_dest) ?></td></tr>
            </tfoot>
        </table>
    </div>

    <!-- التوقيعات -->
    <div class="signatures">
        <div class="sig-box">
            <div class="sig-lbl">المُحاسب</div>
            <div class="sig-line">الاسم والتوقيع</div>
        </div>
        <div class="sig-box">
            <div class="sig-lbl">مدير الإدارة المالية</div>
            <div class="sig-line">الاسم والتوقيع</div>
        </div>
        <div class="sig-box">
            <div class="sig-lbl">الموظف المُستلِم</div>
            <div class="sig-line">الاسم والتوقيع — اطّلعت ووافقت</div>
        </div>
    </div>

    <div class="foot-note">
        طُبع بتاريخ <?= date('Y-m-d H:i') ?> — كشف الموظف <?= htmlspecialchars($emp_no) ?> ضمن الدفعة <?= htmlspecialchars($batch_no) ?>
    </div>

</div>

<script>
    // طباعة تلقائية لو طُلب عبر URL parameter ?auto=1
    (function(){
        var u = new URL(window.location.href);
        if (u.searchParams.get('auto') === '1') {
            setTimeout(function(){ window.print(); }, 350);
        }
    })();
</script>

</body>
</html>
