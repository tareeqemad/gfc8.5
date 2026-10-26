<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
$escape = function ($value) { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); };
$money = function ($value) { return $value === null ? 'غير متوفر' : number_format((float)$value, 2, '.', ','); };
$columns = $report['columns'];
$totals = $report['summary'];
$statuses = array_column($report_config['detail_status_options'], 'label', 'value');
$landscape = count($columns) > 8;
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>تقرير الصرف — <?= $escape($report['measure']['label']) ?></title>
    <style>
        @page { size: A4 <?= $landscape ? 'landscape' : 'portrait' ?>; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font: 10pt Tahoma, Arial, sans-serif; color: #172033; }
        .toolbar { margin: 14px 0; display: flex; gap: 8px; }
        button { padding: 8px 18px; cursor: pointer; border: 1px solid #cbd5e1; border-radius: 5px; background: #f8fafc; }
        h1 { margin: 0 0 8px; font-size: 17pt; }
        .muted { color: #526176; font-size: 9pt; line-height: 1.7; }
        .summary { display: flex; gap: 16px; flex-wrap: wrap; margin: 14px 0; padding: 10px; background: #f1f5f9; }
        .summary b { display: block; padding-top: 4px; }
        table { width: 100%; border-collapse: collapse; font-size: <?= count($columns) > 11 ? '7.5' : '9' ?>pt; }
        th, td { border: 1px solid #cbd5e1; padding: 5px; text-align: right; overflow-wrap: anywhere; }
        th { background: #e8edf5; }
        thead { display: table-header-group; }
        tr { break-inside: avoid; }
        .num { direction: ltr; text-align: right; white-space: nowrap; }
        tfoot { font-weight: bold; background: #f1f5f9; }
        .empty { text-align: center; padding: 28px; color: #526176; }
        @media screen { body { padding: 24px; max-width: 1400px; margin: auto; } }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
<div class="toolbar"><button onclick="window.print()">طباعة</button><button onclick="window.close()">إغلاق</button></div>
<h1>تقرير الصرف — <?= $escape($report['measure']['label']) ?></h1>
<div class="muted"><?= $escape($report['filter_label']) ?></div>
<div class="summary">
    <div>عدد الصفوف<b><?= $escape($totals['count']) ?> من <?= $escape($report['source_count']) ?></b></div>
    <div><?= $escape($totals['amount_label']) ?><b class="num"><?= $money($totals['amount']) ?></b></div>
</div>
<?php if ($totals['count'] > 0): ?><p class="muted"><?php $parts = []; foreach ($totals['by_status'] as $code => $subtotal) {
    $parts[] = $escape($statuses[$code] ?? 'غير معروفة') . ' (' . $escape($subtotal['count']) . '): ' . $money($subtotal['amount']);
} echo implode(' · ', $parts); ?></p><?php endif; ?>
<table>
    <thead><tr><?php foreach ($columns as $column): ?><th><?= $escape($column['label']) ?></th><?php endforeach; ?></tr></thead>
    <tbody>
    <?php foreach ($rows as $index => $row): ?>
        <tr><?php foreach ($columns as $column):
            $value = payment_req_report_cell($row, $column, $index + 1);
            if ($column['kind'] === 'money' && is_numeric($value)) $value = $money($value);
        ?><td class="<?= $column['kind'] === 'money' ? 'num' : '' ?>"><?= $escape($value) ?></td><?php endforeach; ?></tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td class="empty" colspan="<?= count($columns) ?>">لا توجد صفوف تطابق الفلاتر المختارة.</td></tr><?php endif; ?>
    </tbody>
    <tfoot><tr><?php $labelWritten = false; foreach ($columns as $column): ?>
        <td class="<?= $column['kind'] === 'money' ? 'num' : '' ?>"><?php
            if ($column['id'] === 'amount') echo $money($totals['amount']);
            elseif (!$labelWritten) { echo $escape($totals['amount_label']); $labelWritten = true; }
        ?></td>
    <?php endforeach; ?></tr></tfoot>
</table>
</body>
</html>
