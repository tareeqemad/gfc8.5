<?php
$bi  = isset($batch_info) && is_array($batch_info) ? $batch_info : [];
$emp_details = isset($emp_details) && is_array($emp_details) ? $emp_details : [];

$batch_no   = $bi['BATCH_NO']   ?? ('#' . ($batch_id ?? '?'));
$batch_date = $bi['BATCH_DATE'] ?? '';
$pay_date   = $bi['PAY_DATE']   ?? '';

function _ebpa_fmt($n){ return number_format((float)$n, 2, '.', ','); }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title>كشف الموظفين — الدفعة <?= htmlspecialchars($batch_no) ?></title>
<style>
    @page { size: A4; margin: 12mm 14mm; }
    * { box-sizing: border-box; }
    body { font-family: 'Cairo','Tahoma','Arial',sans-serif; color:#1e293b; font-size: 11pt; line-height:1.45; background:#fff; margin:0; padding:0; }

    .toolbar { background:#0f172a; color:#fff; padding:10px 16px; display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; z-index:10; }
    .toolbar button { background:#3b82f6; color:#fff; border:0; padding:6px 14px; border-radius:6px; cursor:pointer; font-size:.9rem; }
    .toolbar button:hover { background:#2563eb; }
    .toolbar .close-btn { background:#64748b; }
    .toolbar .info { font-size:.85rem; opacity:.85; }

    .page { max-width: 210mm; margin: 0 auto; padding: 8mm 6mm; page-break-after: always; }
    .page:last-child { page-break-after: auto; }

    .letterhead { display:flex; justify-content:space-between; align-items:center; border-bottom: 2.5px solid #1e293b; padding-bottom:8px; margin-bottom:12px; }
    .lh-right { display:flex; align-items:center; gap:12px; }
    .lh-right img { height: 50px; }
    .co { font-size:12pt; font-weight:800; line-height:1.2; }
    .co small { display:block; font-size:8.5pt; font-weight:500; color:#64748b; margin-top:2px; }
    .lh-left { text-align:left; font-size:8.5pt; color:#64748b; }
    .lh-left .doc-no { font-size:9.5pt; color:#1e293b; font-weight:700; margin-bottom:2px; }

    .doc-title { text-align:center; font-size:12pt; font-weight:800; background:#1e293b; color:#fff; padding:6px; margin:6px 0 10px; border-radius:4px; }

    .info-grid { display:grid; grid-template-columns: 1fr 1fr; gap:5px 14px; padding:8px 12px; background:#f8fafc; border:1px solid #cbd5e1; border-radius:5px; margin-bottom:10px; }
    .info-grid .row { display:flex; gap:6px; align-items:baseline; }
    .info-grid .lbl { font-size:8.5pt; color:#64748b; font-weight:600; min-width:75px; }
    .info-grid .val { font-size:9.5pt; color:#0f172a; font-weight:700; }

    .sum-cards { display:grid; grid-template-columns: repeat(4, 1fr); gap:5px; margin-bottom:10px; }
    .sum-card { border:1.5px solid; border-radius:5px; padding:6px; text-align:center; background:#fff; }
    .sc-lbl { font-size:7.5pt; font-weight:600; margin-bottom:2px; }
    .sc-val { font-size:12pt; font-weight:800; color:#0f172a; direction:ltr; }
    .sc-tot   { border-color:#1e293b; }     .sc-tot   .sc-lbl { color:#1e293b; }
    .sc-bank  { border-color:#1e40af; }     .sc-bank  .sc-lbl { color:#1e40af; }
    .sc-wlt   { border-color:#6d28d9; }     .sc-wlt   .sc-lbl { color:#6d28d9; }
    .sc-benf  { border-color:#d97706; }     .sc-benf  .sc-lbl { color:#d97706; }

    .sec { margin-bottom: 10px; }
    .sec-title { background:#eff6ff; border-right:3px solid #1e40af; padding:4px 8px; font-size:9.5pt; font-weight:700; color:#1e40af; margin-bottom:4px; }
    .sec-title.dest { background:#ecfdf5; border-right-color:#065f46; color:#065f46; }

    table { width:100%; border-collapse: collapse; font-size:9pt; }
    table th, table td { border:1px solid #cbd5e1; padding:4px 6px; }
    table th { background:#f1f5f9; font-weight:700; font-size:8.5pt; color:#334155; }
    table tfoot td { background:#1e293b; color:#fff; font-weight:800; font-size:9.5pt; }
    table .num { text-align:left; direction:ltr; font-family: 'Consolas','Courier New',monospace; }
    table .ctr { text-align:center; }
    .badge-mini { display:inline-block; background:#dbeafe; color:#1e40af; padding:1px 5px; border-radius:7px; font-size:7.5pt; font-weight:700; }
    .badge-wlt  { background:#ede9fe; color:#6d28d9; }
    .badge-benf { background:#fef3c7; color:#d97706; }
    .badge-merge { background:#dcfce7; color:#166534; font-size:7pt; margin-right:3px; }

    .match-banner { padding:4px 8px; border-radius:4px; font-size:9pt; font-weight:700; text-align:center; margin: 5px 0; }
    .match-ok   { background:#f0fdf4; color:#065f46; border:1px solid #a7f3d0; }
    .match-fail { background:#fef2f2; color:#991b1b; border:1px solid #fca5a5; }

    .signatures { display:grid; grid-template-columns: 1fr 1fr 1fr; gap:12px; margin-top:12px; padding-top:8px; border-top:1.5px dashed #94a3b8; }
    .sig-box { text-align:center; }
    .sig-box .sig-lbl { font-size:8.5pt; color:#475569; font-weight:600; margin-bottom:22px; }
    .sig-box .sig-line { border-top:1.5px solid #1e293b; padding-top:2px; font-size:7.5pt; color:#64748b; }

    .foot-note { margin-top:6px; padding:4px 8px; font-size:7.5pt; color:#64748b; text-align:center; }

    @media print {
        .toolbar, .no-print { display:none !important; }
        body { background:#fff; }
        .page { max-width: 100%; padding: 0; }
        .sum-cards, .sec, .signatures { page-break-inside: avoid; }
    }
</style>
</head>
<body>

<div class="toolbar no-print">
    <span><strong>طباعة كشوف الموظفين — الدفعة <?= htmlspecialchars($batch_no) ?></strong>
          <span class="info"> · <?= count($emp_details) ?> موظف</span></span>
    <div>
        <button onclick="window.print()">🖨 طباعة كل الكشوف</button>
        <button class="close-btn" onclick="window.close()">إغلاق</button>
    </div>
</div>

<?php foreach ($emp_details as $emp):
    $rqs = $emp['requests']; $dst = $emp['destinations']; $t = $emp['totals'];
?>

<div class="page">
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

    <div class="info-grid">
        <div class="row"><span class="lbl">اسم الموظف:</span> <span class="val"><?= htmlspecialchars($emp['emp_name']) ?></span></div>
        <div class="row"><span class="lbl">رقم الموظف:</span> <span class="val"><?= htmlspecialchars($emp['emp_no']) ?></span></div>
        <div class="row"><span class="lbl">رقم الهوية:</span> <span class="val"><?= htmlspecialchars($emp['emp_id']) ?></span></div>
        <div class="row"><span class="lbl">المقر:</span> <span class="val"><?= htmlspecialchars($emp['branch'] ?: '—') ?></span></div>
    </div>

    <div class="sum-cards">
        <div class="sum-card sc-tot">
            <div class="sc-lbl">إجمالي المُستحَق (<?= count($rqs) ?> طلب)</div>
            <div class="sc-val"><?= _ebpa_fmt($t['requests']) ?></div>
        </div>
        <div class="sum-card sc-bank">
            <div class="sc-lbl">🏦 تحويلات بنكية</div>
            <div class="sc-val"><?= _ebpa_fmt($t['sum_bank']) ?></div>
        </div>
        <div class="sum-card sc-wlt">
            <div class="sc-lbl">📱 تحويلات محافظ</div>
            <div class="sc-val"><?= _ebpa_fmt($t['sum_wallet']) ?></div>
        </div>
        <div class="sum-card sc-benf">
            <div class="sc-lbl">👥 إيداع مستفيدين / ورثة</div>
            <div class="sc-val"><?= _ebpa_fmt($t['sum_benef']) ?></div>
        </div>
    </div>

    <div class="sec">
        <div class="sec-title">📋 الطلبات المساهمة</div>
        <table>
            <thead><tr><th style="width:25px">#</th><th>رقم الطلب</th><th style="width:80px">الشهر</th><th>النوع</th><th style="width:90px" class="num">المبلغ</th></tr></thead>
            <tbody>
                <?php if (empty($rqs)): ?>
                    <tr><td colspan="5" class="ctr" style="color:#94a3b8">لا توجد طلبات</td></tr>
                <?php else: foreach ($rqs as $i => $r): ?>
                    <tr>
                        <td class="ctr"><?= $i + 1 ?></td>
                        <td><b><?= htmlspecialchars($r['req_no']) ?></b></td>
                        <td class="ctr"><?= htmlspecialchars($r['month']) ?></td>
                        <td><span class="badge-mini"><?= htmlspecialchars($r['type_name']) ?></span></td>
                        <td class="num"><?= _ebpa_fmt($r['amount']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <tfoot><tr><td colspan="4" style="text-align:left">إجمالي المُستحَق</td>
                <td class="num"><?= _ebpa_fmt($t['requests']) ?></td></tr></tfoot>
        </table>
    </div>

    <?php if ($t['match']): ?>
        <div class="match-banner match-ok">✓ المبالغ متطابقة</div>
    <?php else: ?>
        <div class="match-banner match-fail">⚠ فرق: <?= _ebpa_fmt($t['requests'] - $t['destinations']) ?></div>
    <?php endif; ?>

    <div class="sec">
        <div class="sec-title dest">🏦 حسابات الإيداع والتحويل — تفصيل المبالغ المُحوَّلة</div>
        <table>
            <thead><tr><th style="width:25px">#</th><th style="width:75px">النوع</th><th>جهة الإيداع</th><th>صاحب الحساب</th><th>رقم الحساب / IBAN / الجوال</th><th style="width:90px" class="num">المبلغ</th></tr></thead>
            <tbody>
                <?php if (empty($dst)): ?>
                    <tr><td colspan="6" class="ctr" style="color:#94a3b8">لا توجد وجهات</td></tr>
                <?php else: foreach ($dst as $i => $d):
                    $cat = $d['category'];
                    if ($cat === 'WALLET')      { $bc = 'badge-wlt';  $ic = '📱'; }
                    elseif ($cat === 'BENEF')   { $bc = 'badge-benf'; $ic = '👥'; }
                    else                        { $bc = '';            $ic = '🏦'; }
                ?>
                    <tr>
                        <td class="ctr"><?= $i + 1 ?></td>
                        <td><span class="badge-mini <?= $bc ?>"><?= $ic ?> <?= htmlspecialchars($d['type_name']) ?></span></td>
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
                        <td class="num" style="font-size:8pt"><?= htmlspecialchars($d['account'] ?: '—') ?></td>
                        <td class="num"><?= _ebpa_fmt($d['amount']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <tfoot><tr><td colspan="5" style="text-align:left">إجمالي المُحوَّل</td>
                <td class="num"><?= _ebpa_fmt($t['destinations']) ?></td></tr></tfoot>
        </table>
    </div>

    <div class="signatures">
        <div class="sig-box"><div class="sig-lbl">المُحاسب</div><div class="sig-line">الاسم والتوقيع</div></div>
        <div class="sig-box"><div class="sig-lbl">مدير الإدارة المالية</div><div class="sig-line">الاسم والتوقيع</div></div>
        <div class="sig-box"><div class="sig-lbl">الموظف المُستلِم</div><div class="sig-line">الاسم والتوقيع</div></div>
    </div>

    <div class="foot-note">
        طُبع بتاريخ <?= date('Y-m-d H:i') ?> — كشف الموظف <?= htmlspecialchars($emp['emp_no']) ?> ضمن الدفعة <?= htmlspecialchars($batch_no) ?>
    </div>
</div>

<?php endforeach; ?>

<script>
    (function(){
        var u = new URL(window.location.href);
        if (u.searchParams.get('auto') === '1') {
            setTimeout(function(){ window.print(); }, 500);
        }
    })();
</script>

</body>
</html>
