<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

// Describe available report fields.
function payment_req_report_config($context = 'general')
{
    $context = $context === 'statement' ? 'statement' : 'general';
    $definitions = [
        'seq' => ['م', 'sequence', null],
        'req_no' => ['رقم الطلب', 'text', 'REQ_NO'],
        'month' => ['الشهر', 'month', 'THE_MONTH'],
        'req_type' => ['نوع الطلب', 'text', 'REQ_TYPE_NAME'],
        'calc_basis' => ['أساس الاحتساب', 'text', 'CALCULATION_BASIS'],
        'emp_no' => ['رقم الموظف', 'text', 'EMP_NO'],
        'emp_name' => ['اسم الموظف', 'text', 'EMP_NAME'],
        'branch' => ['المقر', 'text', 'BRANCH_NAME'],
        'amount' => ['المبلغ', 'money', 'REPORT_AMOUNT'],
        'pay_type' => ['بند الصرف', 'text', 'PAY_TYPE_NAME'],
        'detail_status' => ['حالة سطر الموظف', 'status', 'DETAIL_STATUS_NAME'],
        'request_status' => ['حالة الطلب', 'status', 'STATUS_NAME'],
        'pay_date' => ['تاريخ الصرف', 'text', 'PAY_DATE'],
        'note' => ['ملاحظة', 'text', 'NOTE'],
    ];
    $ids = $context === 'statement'
        ? ['seq','req_no','month','req_type','calc_basis','amount','pay_type','detail_status','pay_date','note']
        : ['seq','req_no','month','req_type','calc_basis','emp_no','emp_name','branch','amount','detail_status','request_status','pay_date'];
    $compact = $context === 'statement'
        ? ['seq','req_no','month','req_type','amount','detail_status']
        : ['seq','req_no','month','emp_no','emp_name','amount','detail_status'];
    $columns = [];
    foreach ($ids as $id) {
        $definition = $definitions[$id];
        $columns[] = ['id' => $id, 'label' => $definition[0], 'kind' => $definition[1], 'field' => $definition[2]];
    }
    return ['context' => $context, 'columns' => $columns,
        'presets' => ['compact' => $compact, 'detailed' => $ids],
        'default_options' => ['columns' => $ids, 'mode' => 'all', 'allowance' => 'all', 'detail_status' => 'all', 'measure' => 'total'],
        'measure_options' => [
            ['value' => 'total', 'label' => 'الإجمالي'],
            ['value' => 'base', 'label' => 'المبلغ دون 325'],
            ['value' => 'allowance', 'label' => 'علاوة 325 فقط']],
        'mode_options' => [
            ['value' => 'all', 'label' => 'جميع أسس الاحتساب'],
            ['value' => 'salary', 'label' => 'نوع 2: نسبة من الراتب'],
            ['value' => 'remaining', 'label' => 'نوع 2: نسبة من المتبقي']],
        'allowance_options' => [
            ['value' => 'all', 'label' => 'جميع حالات 325'],
            ['value' => 'with', 'label' => 'يتضمن مبلغ 325'],
            ['value' => 'without', 'label' => 'دون مبلغ 325'],
            ['value' => 'unknown', 'label' => 'مكوّنات 325 غير متوفرة']],
        'detail_status_options' => [
            ['value' => 'all', 'label' => 'جميع حالات السطور'],
            ['value' => '0', 'label' => 'مسودة'], ['value' => '1', 'label' => 'معتمد'],
            ['value' => '2', 'label' => 'منفّذ للصرف'], ['value' => '3', 'label' => 'معتمد جزئياً'],
            ['value' => '4', 'label' => 'محتسب'], ['value' => '9', 'label' => 'ملغى']]];
}

// Normalize report option values.
function payment_req_report_options($context, $params)
{
    $config = payment_req_report_config($context);
    $options = $config['default_options'];
    if (array_key_exists('report_columns', $params) && $params['report_columns'] !== null) {
        $requested = is_string($params['report_columns']) ? array_map('trim', explode(',', $params['report_columns'])) : [];
        $requested = array_map(function ($id) { return in_array($id, ['base', 'allowance', 'total'], true) ? 'amount' : $id; }, $requested);
        $options['columns'] = array_values(array_intersect($options['columns'], $requested));
        if (!$options['columns']) $options['columns'] = $config['presets']['compact'];
    }
    foreach (['mode', 'allowance', 'detail_status', 'measure'] as $key) {
        $value = $params['report_' . $key] ?? $options[$key];
        if (is_scalar($value) && in_array((string)$value, array_column($config[$key . '_options'], 'value'), true)) {
            $options[$key] = (string)$value;
        }
    }
    return $options;
}

// Read monetary values in cents.
function payment_req_report_cents($value)
{
    if ($value === null || !is_numeric($value) || !is_finite((float)$value)
        || abs((float)$value) >= PHP_INT_MAX / 100) return null;
    return (int)round((float)$value * 100);
}

// Sum without unknown-value substitution.
function payment_req_report_add_cents(&$sum, $value)
{
    $cents = payment_req_report_cents($value);
    if ($sum === null || $cents === null
        || ($cents > 0 && $sum > PHP_INT_MAX - $cents)
        || ($cents < 0 && $sum < PHP_INT_MIN - $cents)) $sum = null;
    else $sum += $cents;
}

// Select displayed money without mutation.
function payment_req_report_amount($row, $measure)
{
    $total = $row['TOTAL_PAYMENT'] ?? null;
    if ($measure === 'total') return $total;
    $type = (int)($row['REQ_TYPE'] ?? 0);
    if (in_array($type, [3, 5], true)) return $measure === 'allowance' ? 0.0 : $total;
    if (!in_array($type, [1, 2, 4], true) || empty($row['PAYMENT_BREAKDOWN_AVAILABLE'])) return null;
    $totalCents = payment_req_report_cents($total);
    $allowanceCents = payment_req_report_cents($row['PAYMENT_ALLOWANCE_325'] ?? null);
    if ($totalCents === null || $allowanceCents === null || $allowanceCents < 0 || $allowanceCents > $totalCents) return null;
    return ($measure === 'allowance' ? $allowanceCents : $totalCents - $allowanceCents) / 100;
}

// Filter stored rows consistently.
function payment_req_build_report($rows, $context, $params = [])
{
    $rows = is_array($rows) ? $rows : [];
    $config = payment_req_report_config($context);
    $options = payment_req_report_options($context, $params);
    $measureLabel = array_column($config['measure_options'], 'label', 'value')[$options['measure']];
    $filtered = [];
    $sums = ['total' => 0, 'base' => 0, 'allowance' => 0, 'amount' => 0];
    $byStatus = [];
    foreach ($rows as $row) {
        $type = (int)($row['REQ_TYPE'] ?? 0);
        if ($options['mode'] !== 'all') {
            $storedMode = array_key_exists('TYPE2_REMAINING_MODE', $row) ? ($row['TYPE2_REMAINING_MODE'] ?? 0) : null;
            if ($type !== 2 || !is_numeric($storedMode)
                || (float)$storedMode !== ($options['mode'] === 'remaining' ? 1.0 : 0.0)) continue;
        }
        $applicable = !empty($row['PAYMENT_COMPONENTS_APPLICABLE']);
        $known = !empty($row['PAYMENT_BREAKDOWN_AVAILABLE']);
        $allowance = $row['PAYMENT_ALLOWANCE_325'] ?? null;
        if ($options['allowance'] === 'with' && !($applicable && $known && $allowance > 0)) continue;
        if ($options['allowance'] === 'without' && !(in_array($type, [3, 5], true) || ($applicable && $known && $allowance == 0))) continue;
        if ($options['allowance'] === 'unknown' && !($applicable && !$known)) continue;
        $status = isset($row['DETAIL_STATUS']) && is_scalar($row['DETAIL_STATUS']) ? (string)$row['DETAIL_STATUS'] : 'unknown';
        if ($options['detail_status'] !== 'all' && $status !== $options['detail_status']) continue;
        $row['REPORT_AMOUNT'] = payment_req_report_amount($row, $options['measure']);
        $filtered[] = $row;
        payment_req_report_add_cents($sums['amount'], $row['REPORT_AMOUNT']);
        payment_req_report_add_cents($sums['total'], $row['TOTAL_PAYMENT'] ?? null);
        if ($applicable) {
            payment_req_report_add_cents($sums['base'], $known ? ($row['BASE_PAYMENT'] ?? null) : null);
            payment_req_report_add_cents($sums['allowance'], $known ? $allowance : null);
        }
        if (!isset($byStatus[$status])) $byStatus[$status] = ['count' => 0, 'total' => 0, 'amount' => 0];
        $byStatus[$status]['count']++;
        payment_req_report_add_cents($byStatus[$status]['total'], $row['TOTAL_PAYMENT'] ?? null);
        payment_req_report_add_cents($byStatus[$status]['amount'], $row['REPORT_AMOUNT']);
    }
    foreach ($sums as &$sum) if ($sum !== null) $sum /= 100;
    unset($sum);
    foreach ($byStatus as &$entry) {
        if ($entry['total'] !== null) $entry['total'] /= 100;
        if ($entry['amount'] !== null) $entry['amount'] /= 100;
    }
    unset($entry);
    $labels = [$measureLabel];
    foreach (['mode', 'allowance', 'detail_status'] as $key) {
        if ($options[$key] === 'all') continue;
        foreach ($config[$key . '_options'] as $item) if ($item['value'] === $options[$key]) $labels[] = $item['label'];
    }
    $columns = array_values(array_filter($config['columns'], function ($column) use ($options) {
        return in_array($column['id'], $options['columns'], true);
    }));
    foreach ($columns as &$column) if ($column['id'] === 'amount') $column['label'] = $measureLabel;
    unset($column);
    return ['rows' => $filtered, 'report' => ['options' => $options, 'columns' => $columns,
        'summary' => ['count' => count($filtered), 'total' => $sums['total'], 'base' => $sums['base'],
            'allowance' => $sums['allowance'], 'amount' => $sums['amount'], 'amount_label' => $measureLabel,
            'measure' => $options['measure'], 'by_status' => (object)$byStatus],
        'measure' => ['id' => $options['measure'], 'label' => $measureLabel],
        'source_count' => count($rows), 'filter_label' => implode(' · ', $labels)]];
}

// Format shared report cell values.
function payment_req_report_cell($row, $column, $sequence)
{
    if ($column['kind'] === 'sequence') return $sequence;
    $value = $row[$column['field']] ?? null;
    if ($column['kind'] === 'money') return $value === null ? 'غير متوفر' : $value;
    if ($column['kind'] === 'month') {
        $month = (string)$value;
        return preg_match('/^[0-9]{6}$/D', $month) ? substr($month, 4, 2) . '/' . substr($month, 0, 4) : $month;
    }
    return $value ?? '';
}
