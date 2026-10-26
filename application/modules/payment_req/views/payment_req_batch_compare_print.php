<?php
/**
 * 📊 تقرير المقارنات لدفعة صرف (A4, RTL, print-optimized)
 *
 * الأقسام: ① الأعداد والحركات ② الفروع ③ البنوك ④ نوع الوجهة
 *          ⑤ الطلبات (نوع/عدد/مبلغ) ⑥ الموظفون ⑦ المستثنون ⑧ سجل الدفعات
 *          ⑨ البنود (ملخص لكل بند + تفصيل موظف × بند)
 *          — كل قسم يعرض الدفعتين جنباً لجنب + الفرق + النسبة.
 *
 * ملاحظة ⑨: مفتاح البند = (رقم البند + مصدره)، لأن نفس الرقم يعني بنداً مختلفاً
 *          حسب نوع الطلب: نوع 5 (استحقاقات وإضافات) يقرأ من جدول الثوابت،
 *          وباقي الأنواع من شجرة بنود المستحقات.
 *
 * معجم الأسماء:
 *   cur_*  = الدفعة الحالية (موضوع التقرير)      مثل: $cur_total_amt, $cur_by_bank
 *   ref_*  = الدفعة المرجعية (المختارة بـ"قارن مع") مثل: $ref_total_amt, $ref_by_bank
 *   *_by_* = تجميعة مفتاحها الاسم/الفئة            مثل: $cur_by_branch['غزة']['amt']
 *   emp_*  = أرقام مقارنة الموظفين (من BATCH_DIFF) مثل: $emp_new_count, $emp_diff_rows
 *   lbl_*  = تسمية عمود = رقم الدفعة الفعلي        مثل: $lbl_cur_batch
 *   render_* / group_* / merge_* / fmt_* = دوال مساعدة (closures)
 */
// ─── الدفعة الحالية (موضوع التقرير) ─────────────────────────────
$cur_batch       = $batch_info;
$cur_batch_no    = $cur_batch['BATCH_NO'] ?? '';
$report_batch_id = (int)($cur_batch_id ?? ($cur_batch['BATCH_ID'] ?? 0)); // لروابط الشريط

$cur_dest_rows   = isset($dest_summary) && is_array($dest_summary) ? $dest_summary : [];
$cur_bank_rows   = isset($bank_summary) && is_array($bank_summary) ? $bank_summary : [];
$cur_req_rows    = isset($batch_reqs)   && is_array($batch_reqs)   ? $batch_reqs   : [];
$excluded_rows   = isset($excluded)     && is_array($excluded)     ? $excluded     : [];
$trend_rows      = isset($trend)        && is_array($trend)        ? $trend        : [];
$emp_diff_totals = isset($diff)         && is_array($diff)         ? $diff         : []; // ملخّص الفروقات من الكنترولر

// ─── الدفعة المرجعية (المختارة من "قارن مع") ────────────────────
$ref_batch       = isset($prev_batch_info) && is_array($prev_batch_info) ? $prev_batch_info : null;
$ref_req_rows    = isset($prev_reqs)       && is_array($prev_reqs)       ? $prev_reqs       : [];
$ref_bank_rows   = isset($prev_bank)       && is_array($prev_bank)       ? $prev_bank       : [];
$ref_dest_rows   = isset($prev_dest)       && is_array($prev_dest)       ? $prev_dest       : [];
$ref_detail_rows = isset($prev_details)    && is_array($prev_details)    ? $prev_details    : [];

$ref_batch_id    = isset($prev_id) ? (int)$prev_id : 0;
$ref_batch_meta  = isset($prev_info) && is_array($prev_info) ? $prev_info : null; // صف الدفعة من قائمة المقارنة
$ref_batch_no    = isset($prev_no) ? $prev_no : '';
$has_ref_batch   = $ref_batch_id > 0;

$fmt_int = function ($num) { return number_format((int)$num, 0, '.', ','); };

// تسميات الأعمدة = أرقام الدفعات الفعلية (بدل "الحالية"/"المرجعية")
$lbl_cur_batch = htmlspecialchars($cur_batch_no);
$lbl_ref_batch = htmlspecialchars($ref_batch_no);

// ─── مؤشرات الدفعة الحالية ──────────────────────────────────────
$cur_total_amt   = (float)($cur_batch['TOTAL_AMOUNT'] ?? 0);
$cur_emp_count   = (int)($cur_batch['EMP_COUNT'] ?? 0);
$cur_mov_count   = (int)($cur_batch['MOVEMENT_COUNT'] ?? 0);
$cur_req_count   = count($cur_req_rows);
$cur_avg_per_emp = $cur_emp_count > 0 ? $cur_total_amt / $cur_emp_count : 0;
$excluded_count  = count($excluded_rows);

$cur_type_names = []; $cur_month_keys = [];
foreach ($cur_req_rows as $req_row) {
    $type_name = $req_row['REQ_TYPE_NAME'] ?? ''; if ($type_name !== '') $cur_type_names[$type_name] = 1;
    $month_key = $req_row['THE_MONTH'] ?? '';     if ($month_key !== '') $cur_month_keys[$month_key] = 1;
}
$cur_type_count  = count($cur_type_names);
$cur_month_count = count($cur_month_keys);

// ─── مؤشرات الدفعة المرجعية ─────────────────────────────────────
$ref_total_amt   = $ref_batch ? (float)($ref_batch['TOTAL_AMOUNT'] ?? 0) : ($ref_batch_meta ? (float)($ref_batch_meta['TOTAL_AMOUNT'] ?? 0) : 0);
$ref_emp_count   = $ref_batch ? (int)($ref_batch['EMP_COUNT'] ?? 0)      : ($ref_batch_meta ? (int)($ref_batch_meta['EMP_COUNT'] ?? 0)      : 0);
$ref_mov_count   = $ref_batch ? (int)($ref_batch['MOVEMENT_COUNT'] ?? 0) : 0;
$ref_req_count   = count($ref_req_rows);
$ref_avg_per_emp = $ref_emp_count > 0 ? $ref_total_amt / $ref_emp_count : 0;

$ref_type_names = []; $ref_month_keys = [];
foreach ($ref_req_rows as $req_row) {
    $type_name = $req_row['REQ_TYPE_NAME'] ?? ''; if ($type_name !== '') $ref_type_names[$type_name] = 1;
    $month_key = $req_row['THE_MONTH'] ?? '';     if ($month_key !== '') $ref_month_keys[$month_key] = 1;
}
$ref_type_count  = count($ref_type_names);
$ref_month_count = count($ref_month_keys);

// ─── تجميع النوع + المقر (للدفعتين) من صفوف التفاصيل ─────────────
$req_type_labels = [
    1 => ['lbl' => 'راتب كامل',         'cls' => 'cat-bank'],
    2 => ['lbl' => 'راتب جزئي',         'cls' => 'cat-bank'],
    3 => ['lbl' => 'دفعة من المستحقات', 'cls' => 'cat-wallet'],
    4 => ['lbl' => 'استحقاقات وإضافات', 'cls' => 'cat-heir'],
];
$aggregate_detail_rows = function ($rows) {
    $by_type   = [1=>['amt'=>0.0,'emp'=>0], 2=>['amt'=>0.0,'emp'=>0], 3=>['amt'=>0.0,'emp'=>0], 4=>['amt'=>0.0,'emp'=>0]];
    $by_branch = [];
    foreach ($rows as $row) {
        for ($type_no = 1; $type_no <= 4; $type_no++) {
            $type_amt = (float)($row['AMT_TYPE' . $type_no] ?? 0);
            if ($type_amt != 0) { $by_type[$type_no]['amt'] += $type_amt; $by_type[$type_no]['emp']++; }
        }
        $branch_name = $row['BRANCH_NAME'] ?? 'غير محدد';
        if (!isset($by_branch[$branch_name])) $by_branch[$branch_name] = ['count' => 0, 'amt' => 0.0];
        $by_branch[$branch_name]['count']++;
        $by_branch[$branch_name]['amt'] += (float)($row['TOTAL_AMOUNT'] ?? 0);
    }
    return [$by_type, $by_branch];
};
$cur_detail_rows = isset($detail_rows) && is_array($detail_rows) ? $detail_rows : [];
list($cur_by_type, $cur_by_branch) = $aggregate_detail_rows($cur_detail_rows);
list($ref_by_type, $ref_by_branch) = $aggregate_detail_rows($ref_detail_rows);

// عدد الطلبات حسب النوع (للقسم ⑤)
$count_reqs_by_type = function ($rows) {
    $counts = [1=>0, 2=>0, 3=>0, 4=>0];
    foreach ($rows as $req_row) {
        $req_type = (int)($req_row['REQ_TYPE'] ?? 0);
        // أنواع الطلبات 3/4/5 تُصرف كلها ضمن أعمدة AMT_TYPE3/4 — نطابق نفس التبويب
        if ($req_type === 5) $req_type = 4;
        elseif ($req_type === 4) $req_type = 3;
        if (isset($counts[$req_type])) $counts[$req_type]++;
    }
    return $counts;
};
$cur_req_count_by_type = $count_reqs_by_type($cur_req_rows);
$ref_req_count_by_type = $count_reqs_by_type($ref_req_rows);

// ─── دمج مفاتيح الفروع + البنوك + الوجهة (حالية ∪ مرجعية) ────────
$merge_row_keys = function ($cur_map, $ref_map, $amt_key) {
    $keys = array_keys($cur_map);
    foreach (array_keys($ref_map) as $key) { if (!in_array($key, $keys, true)) $keys[] = $key; }
    usort($keys, function ($key_a, $key_b) use ($cur_map, $ref_map, $amt_key) {
        $amt_a = isset($cur_map[$key_a]) ? (float)$cur_map[$key_a][$amt_key] : -1;
        $amt_b = isset($cur_map[$key_b]) ? (float)$cur_map[$key_b][$amt_key] : -1;
        if ($amt_a === $amt_b) {            // تعادل في الحالية → رتّب بمبلغ المرجعية
            $amt_a = isset($ref_map[$key_a]) ? (float)$ref_map[$key_a][$amt_key] : 0;
            $amt_b = isset($ref_map[$key_b]) ? (float)$ref_map[$key_b][$amt_key] : 0;
        }
        return $amt_b <=> $amt_a;
    });
    return $keys;
};

// خرائط البنوك حسب الاسم
$group_by_bank = function ($rows) {
    $map = [];
    foreach ($rows as $row) {
        $bank_name = trim((string)($row['BANK_NAME'] ?? 'غير محدد'));
        if ($bank_name === '') $bank_name = 'غير محدد';
        if (!isset($map[$bank_name])) $map[$bank_name] = ['emp' => 0, 'trx' => 0, 'amt' => 0.0, 'ptype' => (int)($row['PROVIDER_TYPE'] ?? 1)];
        $map[$bank_name]['emp'] += (int)($row['EMP_COUNT'] ?? 0);
        $map[$bank_name]['trx'] += (int)($row['TRANSACTION_COUNT'] ?? 0);
        $map[$bank_name]['amt'] += (float)($row['TOTAL_AMOUNT'] ?? 0);
    }
    return $map;
};
$cur_by_bank = $group_by_bank($cur_bank_rows);
$ref_by_bank = $group_by_bank($ref_bank_rows);

// خرائط الوجهة حسب الفئة
$group_by_dest = function ($rows) {
    $map = [];
    foreach ($rows as $row) {
        $category = trim((string)($row['CATEGORY'] ?? ''));
        if ($category === '') continue;
        $map[$category] = [
            'emp' => (int)($row['EMP_COUNT'] ?? 0),
            'trx' => (int)($row['TRX_COUNT'] ?? 0),
            'amt' => (float)($row['TOTAL_AMOUNT'] ?? 0),
            'ord' => (int)($row['CAT_ORDER'] ?? 9),
        ];
    }
    return $map;
};
$cur_by_dest = $group_by_dest($cur_dest_rows);
$ref_by_dest = $group_by_dest($ref_dest_rows);

// ═══ ⑤ أنواع الطلبات — الأنواع الخمسة مفصولة ═══════════════════
//     لا تُشتق من AMT_TYPE* في detail_rows: هناك النوعان 3 و4 مدموجان
//     في AMT_TYPE3 والنوع 5 يسكن AMT_TYPE4 — فلا يمكن بناء جدول خماسي منها.
//     المصدر الصحيح: PAY_TYPE_SUMMARY (مجمّع على PAY_TYPE + REQ_TYPE، محصور
//     بالدفعة عبر LINK). نعيد تجميعه على REQ_TYPE وحده.
//     ⚠️ المبلغ والحركات يُجمعان بأمان (فئات متنافية). عدد الموظفين لا يُجمع —
//        موظف واحد قد يظهر في بندين داخل نفس النوع فيُحسب مرتين. لذلك لا نعرضه.
$rt_sum_rows = function ($rows) {
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
// عدد الطلبات: من قائمة الطلبات نفسها — صف واحد لكل طلب، فالعدّ دقيق
$rt_count_reqs = function ($rows) {
    $out = [];
    foreach ((array)$rows as $r) {
        $rt = (int)($r['REQ_TYPE'] ?? 0);
        if ($rt <= 0) continue;
        if (!isset($out[$rt])) $out[$rt] = ['n' => 0, 'name' => (string)($r['REQ_TYPE_NAME'] ?? '')];
        $out[$rt]['n']++;
    }
    return $out;
};
$rt_cur      = $rt_sum_rows(isset($req_type_cur) ? $req_type_cur : []);
$rt_ref      = $rt_sum_rows(isset($req_type_ref) ? $req_type_ref : []);
$rt_cur_reqs = $rt_count_reqs($cur_req_rows);
$rt_ref_reqs = $rt_count_reqs($ref_req_rows);
// أسماء احتياطية لو لم يصل الاسم من الـDB
$rt_fallback_names = [
    1 => 'راتب كامل', 2 => 'دفعة من الراتب', 3 => 'دفعة من المستحقات',
    4 => 'مستحقات حسب الشهر', 5 => 'استحقاقات وإضافات',
];
$rt_all_keys = array_unique(array_merge(
    array_keys($rt_cur), array_keys($rt_ref), array_keys($rt_cur_reqs), array_keys($rt_ref_reqs)
));
sort($rt_all_keys);
$rt_name_of = function ($rt) use ($rt_cur, $rt_ref, $rt_cur_reqs, $rt_ref_reqs, $rt_fallback_names) {
    foreach ([$rt_cur, $rt_ref, $rt_cur_reqs, $rt_ref_reqs] as $src) {
        if (!empty($src[$rt]['name'])) return $src[$rt]['name'];
    }
    return $rt_fallback_names[$rt] ?? ('نوع ' . $rt);
};

// ═══ ⑩ حركة الموظفين — من diff_rows، والحالة من خريطة الكنترولر ═══
//     المعادلة التي تُغلق: الحالية = NEW+CHANGED+SAME ، المرجعية = LEFT+CHANGED+SAME
$churn_labels = ['NEW' => 'دخلوا', 'LEFT' => 'خرجوا', 'CHANGED' => 'تغيّر مبلغهم', 'SAME' => 'بلا تغيير'];
$emp_status_labels = [1 => 'فعّال', 0 => 'متقاعد', 2 => 'متوفى', 4 => 'حساب مغلق'];
$status_map  = (isset($emp_status_map) && is_array($emp_status_map)) ? $emp_status_map : [];
$churn_rows  = (isset($diff_rows) && is_array($diff_rows)) ? $diff_rows : [];
$churn_cross = [];   // CHANGE_TYPE => status => count
$churn_count = ['NEW'=>0,'LEFT'=>0,'CHANGED'=>0,'SAME'=>0];
$churn_amt   = ['NEW'=>0.0,'LEFT'=>0.0,'CHANGED'=>0.0,'SAME'=>0.0];
$churn_named = ['NEW'=>[], 'LEFT'=>[]];   // الأسماء تهم للداخلين والخارجين فقط
foreach ($churn_rows as $cr) {
    $ct = (string)($cr['CHANGE_TYPE'] ?? '');
    if (!isset($churn_count[$ct])) continue;
    $churn_count[$ct]++;
    $churn_amt[$ct] += ($ct === 'LEFT') ? (float)($cr['PREVIOUS_AMOUNT'] ?? 0) : (float)($cr['CURRENT_AMOUNT'] ?? 0);
    $st = $status_map[(string)($cr['EMP_NO'] ?? '')] ?? 1;
    if (!isset($churn_cross[$ct])) $churn_cross[$ct] = [];
    if (!isset($churn_cross[$ct][$st])) $churn_cross[$ct][$st] = 0;
    $churn_cross[$ct][$st]++;
    if (isset($churn_named[$ct])) $churn_named[$ct][] = $cr;
}
$churn_cur_total = $churn_count['NEW'] + $churn_count['CHANGED'] + $churn_count['SAME'];
$churn_ref_total = $churn_count['LEFT'] + $churn_count['CHANGED'] + $churn_count['SAME'];

// ═══ ⑪ توزيع الحركات — كم موظف له كم حركة ═══════════════════════
//     SPLIT_COUNT = عدد سطور التوزيع للموظف = عدد حركاته.
//     ⚠️ SPLIT_COUNT = 0 تعني ACC_ID فارغ (حركة بالطريقة القديمة) لا صفر حركات،
//        فنعاملها كحركة واحدة ونرفع علماً لو خرج المجموع عن MOVEMENT_COUNT.
$mov_bucket_of = function ($n) { $n = (int)$n; if ($n <= 1) return 1; if ($n === 2) return 2; return 3; };
$mov_dist_of = function ($rows) use ($mov_bucket_of) {
    $d = [1=>0, 2=>0, 3=>0]; $sum = 0; $legacy = 0;
    foreach ((array)$rows as $r) {
        $sc = (int)($r['SPLIT_COUNT'] ?? 0);
        if ($sc <= 0) { $legacy++; $sc = 1; }
        $d[$mov_bucket_of($sc)]++;
        $sum += $sc;
    }
    return ['dist' => $d, 'sum' => $sum, 'legacy' => $legacy, 'emps' => count((array)$rows)];
};
$mov_cur = $mov_dist_of($cur_detail_rows);
$mov_ref = $mov_dist_of($ref_detail_rows);
$mov_bucket_labels = [1 => 'حركة واحدة', 2 => 'حركتان', 3 => 'ثلاث حركات فأكثر'];
// التسوية: SPLIT_COUNT يَعدّ أسطر التوزيع ذات الحساب فقط
// (COUNT(CASE WHEN ACC_ID IS NOT NULL ...))، بينما MOVEMENT_COUNT يَعدّ كل الأسطر.
// فالنقص الطبيعي = أسطر بلا حساب صرف محدد (الطريقة القديمة) — خبر لا خطأ.
// الخطأ الحقيقي الوحيد هو أن يزيد المحسوب عن المسجّل: ذلك مستحيل منطقياً.
$mov_cur_legacy = max($cur_mov_count - $mov_cur['sum'], 0);
$mov_ref_legacy = $has_ref_batch ? max($ref_mov_count - $mov_ref['sum'], 0) : 0;
$mov_cur_ok = ($cur_mov_count === 0) || ($mov_cur['sum'] <= $cur_mov_count);
$mov_ref_ok = (!$has_ref_batch) || ($ref_mov_count === 0) || ($mov_ref['sum'] <= $ref_mov_count);

// ═══ ⑫ الشهور — من البروسيجر (المبلغ محصور بالدفعة) ═════════════
$month_rows = (isset($month_diff) && is_array($month_diff)) ? $month_diff : [];

// ═══ ⓿ الخلاصة — جُمل مولّدة من الأرقام نفسها، لا مكتوبة بيد ═══════
//     الغاية: المجلس يقرأ السبب لا الجداول. كل جملة مشتقة من قسم لاحق،
//     فإن تغيّر الرقم تغيّرت الجملة معه.
$sum_lines = [];   // جمل الشرح
$sum_flags = [];   // إشارات تستدعي انتباهاً (تُعرض بالأحمر)

$sum_amt_diff = $cur_total_amt - $ref_total_amt;
$sum_amt_pct  = $ref_total_amt != 0.0 ? round($sum_amt_diff / abs($ref_total_amt) * 100, 1) : null;

if ($has_ref_batch) {
    // ١) حركة المبلغ مقابل حركة الطلبات
    $sum_dir = $sum_amt_diff > 0.009 ? 'ارتفع' : ($sum_amt_diff < -0.009 ? 'انخفض' : 'لم يتغيّر');
    $ln = 'إجمالي الصرف <b>' . $sum_dir . '</b> بمقدار <b>' . n_format(abs($sum_amt_diff)) . '</b>'
        . ($sum_amt_pct !== null ? ' (' . ($sum_amt_pct > 0 ? '+' : '') . $sum_amt_pct . '%)' : '')
        . ' مقارنةً بالدفعة ' . $lbl_ref_batch . '.';
    if ($cur_req_count != $ref_req_count) {
        $ln .= ' وعدد الطلبات ' . ($cur_req_count < $ref_req_count ? 'انخفض' : 'ارتفع')
             . ' من ' . $fmt_int($ref_req_count) . ' إلى ' . $fmt_int($cur_req_count) . '.';
    }
    $sum_lines[] = $ln;

    // ٢) حركة الموظفين — الرسالة الأهم: التساوي لا يعني الأشخاص أنفسهم
    $n_in = (int)($churn_count['NEW'] ?? 0); $n_out = (int)($churn_count['LEFT'] ?? 0);
    if ($n_in > 0 || $n_out > 0) {
        $ln2 = 'عدد الموظفين ' . ($cur_emp_count == $ref_emp_count
                ? '<b>ثابت عند ' . $fmt_int($cur_emp_count) . '</b> — لكنهم ليسوا الأشخاص أنفسهم: '
                : 'تغيّر من ' . $fmt_int($ref_emp_count) . ' إلى ' . $fmt_int($cur_emp_count) . ': ')
             . '<b>' . $fmt_int($n_in) . '</b> دخلوا بـ ' . n_format($churn_amt['NEW'] ?? 0)
             . ' و<b>' . $fmt_int($n_out) . '</b> خرجوا بـ ' . n_format($churn_amt['LEFT'] ?? 0) . '.';
        $sum_lines[] = $ln2;
    } elseif ($cur_emp_count == $ref_emp_count) {
        $sum_lines[] = 'الموظفون أنفسهم في الدفعتين (' . $fmt_int($cur_emp_count) . ') — لا دخول ولا خروج.';
    }

    // ٣) الشهور الداخلة والخارجة
    $m_new = []; $m_left = [];
    foreach ($month_rows as $mr) {
        $lbl = (string)($mr['MONTH_LABEL'] ?? $mr['THE_MONTH'] ?? '');
        if (($mr['CHANGE_TYPE'] ?? '') === 'NEW')  $m_new[]  = $lbl;
        if (($mr['CHANGE_TYPE'] ?? '') === 'LEFT') $m_left[] = $lbl;
    }
    if ($m_new || $m_left) {
        $p = [];
        if ($m_new)  $p[] = 'دخل ' . (count($m_new) > 1 ? 'شهور' : 'شهر') . ' <b>' . implode('، ', $m_new) . '</b>';
        if ($m_left) $p[] = 'خرج ' . (count($m_left) > 1 ? 'شهور' : 'شهر') . ' <b>' . implode('، ', $m_left) . '</b>';
        $sum_lines[] = 'على مستوى الشهور: ' . implode('، و', $p) . '.';
    }

    // ٤) أنواع الطلبات الداخلة والخارجة
    $t_new = []; $t_left = [];
    foreach ($rt_all_keys as $tk) {
        $c_a = (float)($rt_cur[$tk]['amt'] ?? 0); $p_a = (float)($rt_ref[$tk]['amt'] ?? 0);
        $c_r = (int)($rt_cur_reqs[$tk]['n'] ?? 0); $p_r = (int)($rt_ref_reqs[$tk]['n'] ?? 0);
        if ($c_a == 0.0 && $p_a == 0.0 && $c_r === 0 && $p_r === 0) continue;
        if ($p_a == 0.0 && $p_r === 0) $t_new[]  = $rt_name_of($tk);
        if ($c_a == 0.0 && $c_r === 0) $t_left[] = $rt_name_of($tk);
    }
    if ($t_new || $t_left) {
        $p = [];
        if ($t_new)  $p[] = 'ظهر <b>' . implode('، ', $t_new) . '</b>';
        if ($t_left) $p[] = 'اختفى <b>' . implode('، ', $t_left) . '</b>';
        $sum_lines[] = 'على مستوى أنواع الطلبات: ' . implode('، و', $p) . '.';
    }

    // ٥) الحركات مقابل الموظفين — تفسير الفارق قبل أن يُساء فهمه
    if ($cur_mov_count > $cur_emp_count) {
        $sum_lines[] = 'عدد الحركات (' . $fmt_int($cur_mov_count) . ') أكبر من عدد الموظفين ('
                     . $fmt_int($cur_emp_count) . ') بفارق <b>' . $fmt_int($cur_mov_count - $cur_emp_count)
                     . '</b> — سببه توزيع بعض الموظفين على أكثر من حساب، لا موظفين إضافيين.';
    }
} else {
    $sum_lines[] = 'هذه أول دفعة في النظام — لا توجد دفعة مرجعية للمقارنة، وتُعرض أرقام الدفعة الحالية فقط.';
}

// ─── إشارات تستدعي انتباهاً ────────────────────────────────────
$sum_bad_status = 0;
foreach (['NEW', 'CHANGED', 'SAME'] as $ck) {
    foreach ($churn_cross[$ck] ?? [] as $st_k => $st_n) {
        if ((int)$st_k !== 1) $sum_bad_status += (int)$st_n;
    }
}
if ($sum_bad_status > 0) {
    $sum_flags[] = '<b>' . $fmt_int($sum_bad_status) . '</b> موظف في هذه الدفعة حالته ليست «فعّال» '
                 . '(متقاعد / متوفى / حساب مغلق) — راجع القسم الخاص بحركة الموظفين.';
}
if ($excluded_count > 0) {
    $sum_flags[] = '<b>' . $fmt_int($excluded_count) . '</b> موظف استُبعد من الصرف في هذه الدفعة — السبب مفصّل في قسم المستثنين.';
}
if (!$mov_cur_ok || !$mov_ref_ok) {
    // يزيد المحسوب عن المسجّل = خلل بيانات حقيقي، لا أسطر قديمة
    $sum_flags[] = 'عدد الحركات المحسوب من الموظفين يتجاوز المسجّل على الدفعة — خلل في بيانات التوزيع، راجعه قبل الصرف.';
}
$sum_month_total = 0.0;
foreach ($month_rows as $mr) { $sum_month_total += (float)($mr['CUR_AMOUNT'] ?? 0); }
if (!empty($month_rows) && abs($sum_month_total - $cur_total_amt) > 0.01) {
    $sum_flags[] = 'مجموع مبالغ الشهور (' . n_format($sum_month_total) . ') لا يساوي إجمالي الدفعة ('
                 . n_format($cur_total_amt) . ') — فرق ' . n_format(abs($sum_month_total - $cur_total_amt))
                 . ' يعني أسطر توزيع بلا ربط بطلب.';
}

// ─── المستثنون ──────────────────────────────────────────────────
$excluded_total_amt = 0;
foreach ($excluded_rows as $exc_row) { $excluded_total_amt += (float)($exc_row['REQ_AMOUNT'] ?? 0); }

// ─── حركة الموظفين بين الدفعتين (ملخّص محسوب في الكنترولر من BATCH_DIFF) ──
$emp_new_count     = (int)($emp_diff_totals['new'] ?? 0);
$emp_left_count    = (int)($emp_diff_totals['left'] ?? 0);
$emp_changed_count = (int)($emp_diff_totals['changed'] ?? 0);
$emp_same_count    = (int)($emp_diff_totals['same'] ?? 0);
$emp_increase_amt  = (float)($emp_diff_totals['inc'] ?? 0);
$emp_decrease_amt  = (float)($emp_diff_totals['dec'] ?? 0);
$emp_new_amt       = (float)($emp_diff_totals['amt_new'] ?? 0);
$emp_left_amt      = (float)($emp_diff_totals['amt_left'] ?? 0);
$emp_net_change    = $emp_increase_amt - $emp_decrease_amt;

// ─── فروق الإجمالي بين الدفعتين ─────────────────────────────────
$total_amt_diff = $cur_total_amt - $ref_total_amt;
$emp_count_diff = $cur_emp_count - $ref_emp_count;
$total_amt_pct  = $ref_total_amt > 0 ? round($total_amt_diff / $ref_total_amt * 100, 1) : 0;

$print_date = date('d/m/Y H:i');
$dest_cat_class = [1 => 'cat-bank', 2 => 'cat-wallet', 3 => 'cat-heir'];

// ─── تصنيف الموظفين لأربع فئات (صفوف BATCH_DIFF) ────────────────
$emp_diff_rows  = isset($diff_rows) && is_array($diff_rows) ? $diff_rows : [];
$emps_new       = [];   // بالحالية فقط
$emps_left      = [];   // بالمرجعية فقط
$emps_increased = [];   // زاد مبلغه
$emps_decreased = [];   // نقص مبلغه
foreach ($emp_diff_rows as $row) {
    $change_type = $row['CHANGE_TYPE'] ?? '';
    if      ($change_type === 'NEW')  $emps_new[]  = $row;
    elseif  ($change_type === 'LEFT') $emps_left[] = $row;
    elseif  ($change_type === 'CHANGED') {
        if ((float)($row['DIFF'] ?? 0) > 0) $emps_increased[] = $row;
        else                                $emps_decreased[] = $row;
    }
}
$top_n = isset($topn) ? max(5, (int)$topn) : 25;   // سقف الأسماء لكل فئة (من شريط التقرير)

// ─── ⑨ تجهيز بيانات البنود (مبكّراً: الشريط يحتاج قائمة البنود قبل رسم القسم) ───
// ⑨ وضع عرض موظفي البنود: 'all' كل الموظفين | 'changed' من تغيّر مبلغه فقط
$pt_mode = (isset($pt_mode) && $pt_mode === 'changed') ? 'changed' : 'all';
$pt_rows     = is_array($pay_type_diff ?? null) ? $pay_type_diff : [];
$pt_emp_rows = is_array($pay_type_emps ?? null) ? $pay_type_emps : [];
// مفتاح البند = (مصدره + رقمه): نفس الرقم يعني بنداً مختلفاً حسب نوع الطلب
$pt_key = function ($row) {
    return (int)($row['IS_BENEFIT'] ?? 0) . ':' . (int)($row['PAY_TYPE'] ?? 0);
};
$pt_name_of = function ($row) {
    $name = trim((string)($row['PAY_TYPE_NAME'] ?? ''));
    return $name !== '' ? $name : ('بند رقم ' . (int)($row['PAY_TYPE'] ?? 0));
};
// قائمة البنود للمنتقي + التحقق من اختيار المستخدم (?paytype=)
$pt_options = [];
foreach ($pt_rows as $pt_row) { $pt_options[$pt_key($pt_row)] = $pt_name_of($pt_row); }
$pt_pick = trim((string)($pt_pick ?? ''));
if ($pt_pick === '' || !isset($pt_options[$pt_pick])) $pt_pick = 'all';
// نوع الطلب الذي جاء منه كل بند — الطلب ببند واحد، فمصدره قاطع.
// يُبنى من طلبات الدفعتين معاً (بند اختفى من الحالية يبقى معروفاً من المرجعية).
$req_types_by_pt = [];
foreach ([$batch_reqs ?? [], $prev_reqs ?? []] as $req_list) {
    foreach ((array)$req_list as $req_row) {
        $req_pt = $req_row['PAY_TYPE'] ?? null;
        if ($req_pt === null || $req_pt === '') continue;
        $req_map_key = ((int)($req_row['REQ_TYPE'] ?? 0) === 5 ? 1 : 0) . ':' . (int)$req_pt;
        $req_type_nm = trim((string)($req_row['REQ_TYPE_NAME'] ?? ''));
        if ($req_type_nm === '') continue;
        $req_types_by_pt[$req_map_key][$req_type_nm] = 1;   // مفتاح = تفريد بلا تكرار
    }
}
usort($emps_increased, function ($row_a, $row_b) { return (float)$row_b['DIFF'] <=> (float)$row_a['DIFF']; });
usort($emps_decreased, function ($row_a, $row_b) { return (float)$row_a['DIFF'] <=> (float)$row_b['DIFF']; });
usort($emps_new,       function ($row_a, $row_b) { return (float)$row_b['CURRENT_AMOUNT']  <=> (float)$row_a['CURRENT_AMOUNT']; });
usort($emps_left,      function ($row_a, $row_b) { return (float)$row_b['PREVIOUS_AMOUNT'] <=> (float)$row_a['PREVIOUS_AMOUNT']; });

// ─── مساعدات عرض الخلايا ────────────────────────────────────────
// خلية فرق (مبلغ أو عدد): ملوّنة بالاتجاه مع إشارة
$render_diff_cell = function ($cur_val, $ref_val, $is_money = true) use ($fmt_int) {
    $diff = $cur_val - $ref_val;
    $dir_class = $diff > 0.009 ? 'up' : ($diff < -0.009 ? 'down' : 'eq');
    $diff_txt  = $is_money ? n_format(abs($diff)) : $fmt_int(abs($diff));
    $sign      = $diff > 0.009 ? '+' : ($diff < -0.009 ? '−' : '');
    return '<td class="num ' . $dir_class . '">' . ($sign === '' ? '—' : $sign . $diff_txt) . '</td>';
};
// خلية نسبة التغيّر عن الدفعة المرجعية
$render_pct_cell = function ($cur_val, $ref_val) {
    if ($ref_val == 0.0) return '<td class="ctr eq">' . ($cur_val != 0.0 ? 'جديد' : '—') . '</td>';
    $pct_val = round(($cur_val - $ref_val) / abs($ref_val) * 100, 1);
    $dir_class = $pct_val > 0 ? 'up' : ($pct_val < 0 ? 'down' : 'eq');
    return '<td class="ctr ' . $dir_class . '">' . ($pct_val > 0 ? '+' : '') . $pct_val . '%</td>';
};
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير المقارنات — <?= htmlspecialchars($cur_batch_no) ?></title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: "Tahoma","Segoe UI",sans-serif; color:#1e293b; background:#f1f5f9; direction:rtl; font-size:11pt; }
    .print-toolbar { position:sticky; top:0; background:#0f172a; padding:8px 16px; display:flex; gap:8px; z-index:9; }
    .print-toolbar button { padding:6px 18px; border:0; border-radius:6px; background:#2563eb; color:#fff; font-size:10pt; cursor:pointer; font-family:inherit; }
    .print-toolbar button.sec { background:#475569; }
    .print-toolbar button.mini { padding:3px 10px; font-size:8.5pt; }
    .sec-picker { display:flex; align-items:center; gap:6px; flex-wrap:wrap; width:100%; padding-top:5px; border-top:1px solid #1e293b; }
    .sec-chip { display:flex; align-items:center; gap:4px; background:#1d4ed8; color:#fff; border-radius:14px; padding:3px 10px; font-size:8.5pt; font-weight:700; cursor:pointer; user-select:none; }
    .sec-chip.off { background:#334155; color:#94a3b8; text-decoration:line-through; }
    .sec-chip input { accent-color:#fff; cursor:pointer; }
    .page { width:210mm; min-height:297mm; margin:12px auto; background:#fff; padding:14mm 12mm; box-shadow:0 2px 14px rgba(0,0,0,.12); }

    /* Letterhead */
    .letterhead { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #0f172a; padding-bottom:8px; margin-bottom:10px; }
    .lh-right { display:flex; align-items:center; gap:10px; }
    .lh-right .co { font-weight:700; font-size:12pt; line-height:1.5; }
    .lh-right .co small { display:block; font-weight:400; font-size:8.5pt; color:#475569; }
    .lh-left { text-align:left; font-size:8.5pt; color:#475569; line-height:1.6; }
    .doc-title { text-align:center; font-size:15pt; font-weight:800; color:#0f172a; margin:6px 0 12px; }
    .doc-title small { display:block; font-size:9pt; font-weight:400; color:#64748b; margin-top:2px; }

    /* Section */
    .section { margin-bottom:14px; }
    .section-title { font-size:11.5pt; font-weight:800; color:#0f172a; background:#e2e8f0; border-right:4px solid #2563eb; padding:5px 10px; margin-bottom:8px; border-radius:3px; }
    .section-title .vs { font-weight:400; font-size:9pt; color:#475569; }

    /* Tables */
    table.rpt { width:100%; border-collapse:collapse; font-size:9.5pt; }
    table.rpt th, table.rpt td { border:1px solid #cbd5e1; padding:5px 7px; }
    table.rpt thead th { background:#0f172a; color:#fff; font-weight:700; font-size:9pt; }
    table.rpt thead th.prvh { background:#334155; }
    table.rpt tbody tr:nth-child(even) { background:#f8fafc; }
    table.rpt td.num { text-align:left; font-variant-numeric:tabular-nums; direction:ltr; }
    table.rpt td.ctr, table.rpt th.ctr { text-align:center; }
    table.rpt td.prv { background:#f1f5f9; color:#475569; }
    table.rpt tr.total-row td { background:#e2e8f0; font-weight:800; }
    table.rpt tr.total-row td.prv { background:#cbd5e1; }
    .tbl-empty { text-align:center; color:#94a3b8; padding:14px; font-style:italic; }
    .tbl-note { font-size:8pt; color:#64748b; margin-top:3px; line-height:1.5; }
    .pill { display:inline-block; padding:1px 9px; border-radius:10px; font-size:8.5pt; font-weight:700; }
    .cat-bank   { background:#dbeafe; color:#1d4ed8; }
    .cat-wallet { background:#dcfce7; color:#15803d; }
    .cat-heir   { background:#fef3c7; color:#b45309; }
    .reason-no  { color:#b91c1c; font-weight:700; }
    .reason-x   { color:#9a3412; }

    /* Comparison cards */
    .cmp-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:7px; margin-bottom:10px; }
    .cmp { border:1px solid #e2e8f0; border-radius:7px; padding:8px 10px; text-align:center; background:#f8fafc; }
    .cmp .lbl { font-size:8pt; color:#64748b; font-weight:600; }
    .cmp .val { font-size:13pt; font-weight:800; margin-top:2px; }
    .cmp .sub { font-size:8pt; margin-top:2px; font-weight:700; }
    .up   { color:#059669; }
    .down { color:#dc2626; }
    .eq   { color:#64748b; }
    .note-box { background:#fffbeb; border:1px solid #fde68a; color:#92400e; padding:8px 10px; border-radius:6px; font-size:9pt; }

    @media print {
        body { background:#fff; }
        .print-toolbar { display:none; }
        .page { box-shadow:none; margin:0; width:auto; padding:0; }
        .section { page-break-inside:avoid; }
        .section.allow-break { page-break-inside:auto; }
        @page { size:A4; margin:10mm; }
    }
</style>
</head>
<body>

<div class="print-toolbar" style="flex-wrap:wrap">
    <button onclick="window.print()">🖨️ طباعة</button>
    <button style="background:#059669" onclick="exportExcel()">📊 تصدير Excel</button>
    <button class="sec" onclick="window.close()">إغلاق</button>
    <?php $compare_opts = isset($compare_options) && is_array($compare_options) ? $compare_options : []; ?>
    <?php if (!empty($compare_opts)): ?>
    <label style="display:flex; align-items:center; gap:6px; color:#cbd5e1; font-size:10pt">
        قارن مع:
        <select id="cmpSelect" onchange="reloadReport()"
                style="padding:5px 8px; border-radius:6px; border:0; font-family:inherit; font-size:10pt">
            <?php foreach ($compare_opts as $opt):
                $opt_batch_id = (int)$opt['BATCH_ID'];
                $opt_label = htmlspecialchars($opt['BATCH_NO'])
                      . (!empty($opt['STATUS_NAME']) ? ' [' . htmlspecialchars($opt['STATUS_NAME']) . ']' : '')
                      . ' — ' . n_format((float)$opt['TOTAL_AMOUNT'])
                      . ' — ' . $fmt_int($opt['EMP_COUNT']) . ' موظف'
                      . (!empty($opt['ENTRY_DATE']) ? ' — ' . htmlspecialchars($opt['ENTRY_DATE']) : '');
            ?>
                <option value="<?= $opt_batch_id ?>" <?= ($opt_batch_id === (int)$ref_batch_id) ? 'selected' : '' ?>><?= $opt_label ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php endif; ?>
    <label style="display:flex; align-items:center; gap:6px; color:#cbd5e1; font-size:10pt">
        أسماء الموظفين:
        <select id="topnSelect" onchange="reloadReport()"
                style="padding:5px 8px; border-radius:6px; border:0; font-family:inherit; font-size:10pt">
            <?php foreach ([10 => '10', 25 => '25', 50 => '50', 9999 => 'الكل'] as $tv => $tl): ?>
                <option value="<?= $tv ?>" <?= ((int)$top_n === $tv || ($tv === 9999 && $top_n >= 9999)) ? 'selected' : '' ?>><?= $tl ?> لكل فئة</option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php if (!empty($pt_options)): ?>
    <label style="display:flex; align-items:center; gap:6px; color:#cbd5e1; font-size:10pt"
           title="قسم ⑨: ركّز التفصيل على بند واحد (مثل بدل المواصلات)">
        البند:
        <select id="ptSelect" onchange="reloadReport()"
                style="padding:5px 8px; border-radius:6px; border:0; font-family:inherit; font-size:10pt; max-width:230px">
            <option value="all" <?= $pt_pick === 'all' ? 'selected' : '' ?>>كل البنود</option>
            <?php foreach ($pt_options as $pt_val => $pt_lbl): ?>
                <option value="<?= htmlspecialchars($pt_val) ?>" <?= $pt_pick === $pt_val ? 'selected' : '' ?>><?= htmlspecialchars($pt_lbl) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php endif; ?>
    <label style="display:flex; align-items:center; gap:6px; color:#cbd5e1; font-size:10pt"
           title="قسم ⑨: هل تعرض كل الموظفين على البند أم من تغيّر مبلغه فقط؟">
        موظفو البنود:
        <select id="ptModeSelect" onchange="reloadReport()"
                style="padding:5px 8px; border-radius:6px; border:0; font-family:inherit; font-size:10pt">
            <option value="all"     <?= $pt_mode === 'all'     ? 'selected' : '' ?>>الكل</option>
            <option value="changed" <?= $pt_mode === 'changed' ? 'selected' : '' ?>>المتغيّرون فقط</option>
        </select>
    </label>

    <!-- 🎛️ اختيار أقسام التقرير — المعروض هو المطبوع، والاختيار محفوظ للمرة القادمة -->
    <div class="sec-picker">
        <span style="color:#94a3b8; font-size:9pt">أقسام التقرير:</span>
        <?php
        $section_labels = [
            1  => 'الأعداد والحركات',
            2  => 'حركة الموظفين',
            3  => 'الشهور',
            4  => 'أنواع الطلبات',
            5  => 'توزيع الحركات',
            6  => 'الفروع',
            7  => 'البنوك',
            8  => 'نوع الوجهة',
            9  => 'الموظفون',
            10 => 'المستثنون',
            11 => 'سجل الدفعات',
            12 => 'البنود',
        ];
        $section_labels = [0 => 'الخلاصة'] + $section_labels;
        foreach ($section_labels as $sn => $sl): ?>
        <label class="sec-chip" id="chip-<?= $sn ?>">
            <input type="checkbox" checked data-sec="sec-<?= $sn ?>" onchange="applySecs(true)"> <?= $sn ?>. <?= $sl ?>
        </label>
        <?php endforeach; ?>
        <button class="sec mini" onclick="setAllSecs(true)">الكل</button>
        <button class="sec mini" onclick="setAllSecs(false)">بلا</button>
        <span style="width:1px; height:20px; background:#cbd5e1; margin:0 6px"></span>
        <span style="font-size:.72rem; color:#64748b; align-self:center">جاهز لـ:</span>
        <button class="sec mini" onclick="applyPreset('board')" title="الخلاصة والأعداد فقط — صفحة واحدة">مجلس الإدارة</button>
        <button class="sec mini" onclick="applyPreset('finance')" title="الخلاصة والأعداد وما يفسّرها">قسم المالية</button>
        <button class="sec mini" onclick="applyPreset('audit')" title="كل الأقسام">تدقيق كامل</button>
    </div>
</div>
<script>
function reloadReport(){
    var base = '<?= base_url("payment_req/payment_req/batch_compare_print/" . $report_batch_id) ?>';
    var prevEl   = document.getElementById('cmpSelect');
    var topnEl   = document.getElementById('topnSelect');
    var ptModeEl = document.getElementById('ptModeSelect');
    var ptEl     = document.getElementById('ptSelect');
    var qs = [];
    if (prevEl)   qs.push('prev='    + encodeURIComponent(prevEl.value));
    if (topnEl)   qs.push('topn='    + encodeURIComponent(topnEl.value));
    if (ptModeEl) qs.push('ptmode='  + encodeURIComponent(ptModeEl.value));
    if (ptEl)     qs.push('paytype=' + encodeURIComponent(ptEl.value));
    window.location = base + (qs.length ? '?' + qs.join('&') : '');
}

// التصدير يحترم اختيارات الشريط: الدفعة المرجعية + البند + وضع الموظفين + الأقسام المفعّلة
// (سقف "أسماء الموظفين" لا يُطبَّق — الإكسل ملف تدقيق يشمل الجميع)
function exportExcel(){
    var base = '<?= base_url("payment_req/payment_req/batch_compare_export/" . $report_batch_id) ?>';
    var prevEl   = document.getElementById('cmpSelect');
    var ptModeEl = document.getElementById('ptModeSelect');
    var ptEl     = document.getElementById('ptSelect');
    var qs = [];
    if (prevEl)   qs.push('prev='    + encodeURIComponent(prevEl.value));
    if (ptModeEl) qs.push('ptmode='  + encodeURIComponent(ptModeEl.value));
    if (ptEl)     qs.push('paytype=' + encodeURIComponent(ptEl.value));

    var secs = [];
    document.querySelectorAll('.sec-chip input[data-sec]').forEach(function(box){
        if (box.checked) secs.push(String(box.getAttribute('data-sec')).replace('sec-', ''));
    });
    if (secs.length) qs.push('secs=' + encodeURIComponent(secs.join(',')));

    window.location = base + (qs.length ? '?' + qs.join('&') : '');
}

// ── اختيار الأقسام: عرض/إخفاء + حفظ التفضيل بالمتصفح ──
var SEC_STORE = 'gfc_cmp_report_secs_v1';

function applySecs(save){
    var boxes = document.querySelectorAll('.sec-chip input[data-sec]');
    var state = {};
    boxes.forEach(function(b){
        var el = document.getElementById(b.getAttribute('data-sec'));
        if (el) el.style.display = b.checked ? '' : 'none';
        b.parentElement.classList.toggle('off', !b.checked);
        state[b.getAttribute('data-sec')] = b.checked;
    });
    if (save) { try { localStorage.setItem(SEC_STORE, JSON.stringify(state)); } catch(e){} }
}

function setAllSecs(on){
    document.querySelectorAll('.sec-chip input[data-sec]').forEach(function(b){ b.checked = on; });
    applySecs(true);
}

// ثلاث جماهير لنفس التقرير — الأقسام بأرقامها كما في شريط الاختيار.
// التدقيق = الكل، فلا نعدّده حتى لا ننسى قسماً جديداً مستقبلاً.
var SEC_PRESETS = {
    board:   [0, 1],                       // المجلس: الخلاصة + الأعداد
    finance: [0, 1, 2, 3, 4, 5],           // المالية: + ما يفسّر الأعداد
};
function applyPreset(name){
    var wanted = SEC_PRESETS[name] || null;
    document.querySelectorAll('.sec-chip input[data-sec]').forEach(function(b){
        var n = parseInt(String(b.getAttribute('data-sec')).replace('sec-', ''), 10);
        b.checked = wanted ? (wanted.indexOf(n) > -1) : true;
    });
    applySecs(true);
}

(function restoreSecs(){
    var state = null;
    try { state = JSON.parse(localStorage.getItem(SEC_STORE) || 'null'); } catch(e){}
    if (state) {
        document.querySelectorAll('.sec-chip input[data-sec]').forEach(function(b){
            var k = b.getAttribute('data-sec');
            if (k in state) b.checked = !!state[k];
        });
    }
    applySecs(false);
})();
</script>

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
            <strong>تقرير المقارنات</strong><br>
            تاريخ الطباعة: <?= $print_date ?><br>
            <?php if (!empty($user_name)): ?>طُبع بواسطة: <?= htmlspecialchars($user_name) ?><?php endif; ?>
        </div>
    </div>

    <div class="doc-title">
        تقرير المقارنات للدفعة <?= htmlspecialchars($cur_batch_no) ?><?= !empty($cur_batch['STATUS_NAME']) ? ' <span style="font-size:10pt;color:#475569">[' . htmlspecialchars($cur_batch['STATUS_NAME']) . ']</span>' : '' ?>
        <?php if ($has_ref_batch): ?>مقابل <?= htmlspecialchars($ref_batch_no) ?><?= !empty($ref_batch_meta['STATUS_NAME']) ? ' <span style="font-size:10pt;color:#475569">[' . htmlspecialchars($ref_batch_meta['STATUS_NAME']) . ']</span>' : '' ?><?php endif; ?>
        <small>الخلاصة • الأعداد والحركات • حركة الموظفين • الشهور • أنواع الطلبات • توزيع الحركات • الفروع • البنوك • نوع الوجهة • الموظفون • المستثنون • سجل الدفعات • البنود</small>
    </div>

    <?php if (!$has_ref_batch): ?>
        <div class="note-box" style="margin-bottom:12px">هذه أول دفعة في النظام — لا توجد دفعة مرجعية، تُعرض أرقام الدفعة الحالية فقط.</div>
    <?php elseif ((int)($ref_batch_meta['STATUS'] ?? 0) !== 2): ?>
        <div class="note-box" style="margin-bottom:12px">⚠️ تنبيه: الدفعة المرجعية <?= htmlspecialchars($ref_batch_no) ?> <b>غير منفّذة بعد</b> (<?= htmlspecialchars($ref_batch_meta['STATUS_NAME'] ?? '') ?>) — أرقامها قابلة للتغيير حتى الصرف، خذ المقارنة على هذا الأساس.</div>
    <?php endif; ?>

    <!-- ════════ ⓿ الخلاصة ════════ -->
    <div class="section" id="sec-0">
        <div class="section-title">⓿ الخلاصة</div>

        <div class="cmp" style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:8px">
            <div style="flex:1; min-width:150px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:8px 10px">
                <div style="font-size:8pt; color:#475569">إجمالي الصرف — <?= $lbl_cur_batch ?></div>
                <div style="font-size:14pt; font-weight:800; color:#1e40af; direction:ltr"><?= n_format($cur_total_amt) ?></div>
                <?php if ($has_ref_batch): ?>
                <div class="sub" style="color:<?= $sum_amt_diff > 0.009 ? '#047857' : ($sum_amt_diff < -0.009 ? '#b91c1c' : '#64748b') ?>">
                    <?= $sum_amt_diff > 0.009 ? '▲ +' : ($sum_amt_diff < -0.009 ? '▼ −' : '') ?><?= n_format(abs($sum_amt_diff)) ?>
                    <?= $sum_amt_pct !== null ? ' (' . ($sum_amt_pct > 0 ? '+' : '') . $sum_amt_pct . '%)' : '' ?>
                </div>
                <?php endif; ?>
            </div>
            <div style="flex:1; min-width:150px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:8px 10px">
                <div style="font-size:8pt; color:#475569">الموظفون</div>
                <div style="font-size:14pt; font-weight:800; color:#047857; direction:ltr"><?= $fmt_int($cur_emp_count) ?></div>
                <?php if ($has_ref_batch): ?>
                <div class="sub" style="color:#475569">
                    دخل <?= $fmt_int($churn_count['NEW'] ?? 0) ?> · خرج <?= $fmt_int($churn_count['LEFT'] ?? 0) ?>
                    · الصافي <?= $fmt_int(($churn_count['NEW'] ?? 0) - ($churn_count['LEFT'] ?? 0)) ?>
                </div>
                <?php endif; ?>
            </div>
            <div style="flex:1; min-width:150px; background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:8px 10px">
                <div style="font-size:8pt; color:#475569">الحركات البنكية</div>
                <div style="font-size:14pt; font-weight:800; color:#92400e; direction:ltr"><?= $fmt_int($cur_mov_count) ?></div>
                <div class="sub" style="color:#475569">
                    <?= $fmt_int($cur_month_count) ?> شهر · <?= $fmt_int($cur_type_count) ?> نوع طلب
                </div>
            </div>
        </div>

        <?php if (!empty($sum_lines)): ?>
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:9px 12px; font-size:9.5pt; line-height:1.85">
            <?php foreach ($sum_lines as $sl): ?>
            <div style="margin-bottom:3px">• <?= $sl ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($sum_flags)): ?>
        <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; border-radius:8px; padding:9px 12px; font-size:9.5pt; line-height:1.85; margin-top:8px">
            <div style="font-weight:800; margin-bottom:3px">يستدعي الانتباه</div>
            <?php foreach ($sum_flags as $sf): ?>
            <div style="margin-bottom:3px">⚠️ <?= $sf ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ════════ ① مقارنة الأعداد والحركات ════════ -->
    <div class="section" id="sec-1">
        <div class="section-title">① مقارنة الأعداد والحركات
            <?php if ($has_ref_batch): ?><span class="vs"><?= htmlspecialchars($cur_batch_no) ?> مقابل <?= htmlspecialchars($ref_batch_no) ?></span><?php endif; ?>
        </div>
        <table class="rpt">
            <thead>
                <tr>
                    <th>البيان</th>
                    <th class="ctr" style="width:120px"><?= $lbl_cur_batch ?></th>
                    <?php if ($has_ref_batch): ?>
                    <th class="ctr prvh" style="width:120px"><?= $lbl_ref_batch ?></th>
                    <th class="ctr" style="width:110px">الفرق</th>
                    <th class="ctr" style="width:70px">النسبة</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $kpi_rows = [
                    ['إجمالي مبلغ الصرف',     $cur_total_amt,   $ref_total_amt,   true],
                    ['عدد الموظفين',          $cur_emp_count,   $ref_emp_count,   false],
                    ['عدد الحركات',           $cur_mov_count,   $ref_mov_count,   false],
                    ['عدد الطلبات',           $cur_req_count,   $ref_req_count,   false],
                    ['متوسط الصرف للموظف',    $cur_avg_per_emp,     $ref_avg_per_emp,     true],
                    ['أنواع الطلبات',         $cur_type_count,  $ref_type_count,  false],
                    ['الشهور المغطاة',        $cur_month_count, $ref_month_count, false],
                ];
                foreach ($kpi_rows as $kpi_row):
                    list($kpi_label, $kpi_cur_val, $kpi_ref_val, $kpi_is_money) = $kpi_row;
                    // عدّات فئات: الفرق مفيد، والنسبة مضلّلة (نوع واحد من 4 = −25%)
                    $kpi_no_pct = ['أنواع الطلبات', 'الشهور المغطاة'];
                ?>
                <tr>
                    <td style="font-weight:700"><?= $kpi_label ?></td>
                    <td class="num"><?= $kpi_is_money ? n_format($kpi_cur_val) : $fmt_int($kpi_cur_val) ?></td>
                    <?php if ($has_ref_batch): ?>
                    <td class="num prv"><?= $kpi_is_money ? n_format($kpi_ref_val) : $fmt_int($kpi_ref_val) ?></td>
                    <?= $render_diff_cell($kpi_cur_val, $kpi_ref_val, $kpi_is_money) ?>
                    <?php if (in_array($kpi_label, $kpi_no_pct, true)): ?>
                    <td class="ctr eq" title="نسبة مئوية على عدد فئات صغير تضلّل أكثر مما تفيد">—</td>
                    <?php else: ?>
                    <?= $render_pct_cell($kpi_cur_val, $kpi_ref_val) ?>
                    <?php endif; ?>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td style="font-weight:700">موظفون مستثنون</td>
                    <td class="num"><?= $fmt_int($excluded_count) ?></td>
                    <?php if ($has_ref_batch): ?><td class="num prv">—</td><td class="num eq">—</td><td class="ctr eq">—</td><?php endif; ?>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ════════ ② حركة الموظفين (داخل / خارج / ثابت) + الحالة ════════ -->
    <div class="section allow-break" id="sec-2">
        <div class="section-title">② حركة الموظفين بين الدفعتين + حالة الموظف</div>
        <?php if (!$has_ref_batch): ?>
            <div class="note-box">لا توجد دفعة مرجعية — الحركة تحتاج دفعتين للمقارنة.</div>
        <?php elseif (empty($churn_rows)): ?>
            <div class="note-box">لا توجد بيانات مقارنة بين الدفعتين.</div>
        <?php else: ?>
        <div class="note-box" style="margin-bottom:6px">
            تساوي العددين في القسم ① لا يعني أنهم الأشخاص أنفسهم. المعادلة التي تُغلق:
            <b><?= $lbl_cur_batch ?></b> = دخلوا + تغيّر مبلغهم + بلا تغيير
            &nbsp;•&nbsp; <b><?= $lbl_ref_batch ?></b> = خرجوا + تغيّر مبلغهم + بلا تغيير.
        </div>
        <table class="rpt">
            <thead>
                <tr>
                    <th style="width:120px">الحركة</th>
                    <th class="ctr" style="width:70px">موظفون</th>
                    <th class="ctr" style="width:110px">المبلغ</th>
                    <?php foreach ($emp_status_labels as $st_k => $st_lbl): ?>
                    <th class="ctr" style="width:70px"><?= $st_lbl ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($churn_labels as $ck => $clbl):
                    $c_n = (int)($churn_count[$ck] ?? 0);
                ?>
                <tr>
                    <td>
                        <span class="pill <?= $ck === 'NEW' ? 'cat-heir' : ($ck === 'LEFT' ? 'cat-wallet' : 'cat-bank') ?>"><?= $clbl ?></span>
                    </td>
                    <td class="ctr" style="font-weight:700"><?= $fmt_int($c_n) ?></td>
                    <td class="num"><?= n_format($churn_amt[$ck] ?? 0) ?></td>
                    <?php foreach ($emp_status_labels as $st_k => $st_lbl):
                        $cell = (int)($churn_cross[$ck][$st_k] ?? 0); ?>
                    <td class="ctr"<?= ($cell > 0 && $st_k != 1) ? ' style="color:#b45309;font-weight:700"' : '' ?>><?= $cell > 0 ? $fmt_int($cell) : '—' ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td>التحقق</td>
                    <td class="ctr" colspan="<?= 2 + count($emp_status_labels) ?>" style="text-align:right">
                        <?= $lbl_cur_batch ?> = <?= $fmt_int($churn_cur_total) ?> موظف
                        &nbsp;|&nbsp; <?= $lbl_ref_batch ?> = <?= $fmt_int($churn_ref_total) ?> موظف
                        &nbsp;|&nbsp; الصافي = <?= $fmt_int($churn_count['NEW'] - $churn_count['LEFT']) ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php foreach (['NEW' => 'الداخلون الجدد', 'LEFT' => 'الخارجون'] as $nk => $nlbl):
            if (empty($churn_named[$nk])) continue; ?>
        <div class="section-title" style="margin-top:10px; font-size:10pt"><?= $nlbl ?> (<?= $fmt_int(count($churn_named[$nk])) ?>)</div>
        <table class="rpt">
            <thead><tr>
                <th style="width:60px">الرقم</th><th>الاسم</th><th style="width:130px">المقر</th>
                <th class="ctr" style="width:80px">الحالة</th><th class="ctr" style="width:110px">المبلغ</th>
            </tr></thead>
            <tbody>
                <?php $nshown = 0; foreach ($churn_named[$nk] as $nr): if ($nshown++ >= (int)$topn) break;
                    $n_st = $status_map[(string)($nr['EMP_NO'] ?? '')] ?? 1; ?>
                <tr>
                    <td><?= htmlspecialchars((string)($nr['EMP_NO'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($nr['EMP_NAME'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($nr['BRANCH_NAME'] ?? '')) ?></td>
                    <td class="ctr"<?= $n_st != 1 ? ' style="color:#b45309;font-weight:700"' : '' ?>><?= $emp_status_labels[$n_st] ?? '—' ?></td>
                    <td class="num"><?= n_format($nk === 'LEFT' ? ($nr['PREVIOUS_AMOUNT'] ?? 0) : ($nr['CURRENT_AMOUNT'] ?? 0)) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (count($churn_named[$nk]) > (int)$topn): ?>
                <tr><td colspan="5" class="tbl-empty">عُرض <?= $fmt_int($topn) ?> من <?= $fmt_int(count($churn_named[$nk])) ?> — غيّر عدد الأسماء من شريط التقرير</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ════════ ③ الشهور المغطاة + ما يخص كل شهر ════════ -->
    <div class="section allow-break" id="sec-3">
        <div class="section-title">③ الشهور المغطاة — الطلبات والمبالغ لكل شهر</div>
        <?php if (empty($month_rows)): ?>
            <div class="note-box">لا تتوفر بيانات الشهور (البروسيجر غير منشور أو لا توجد أسطر مربوطة بالدفعة).</div>
        <?php else: ?>
        <div class="note-box" style="margin-bottom:6px">
            المبالغ هنا محصورة بما دخل الدفعة فعلاً — لا بمبالغ الطلبات الكاملة.
            فمجموع عمود المبلغ يجب أن يساوي إجمالي الدفعة.
        </div>
        <table class="rpt">
            <thead>
                <tr>
                    <th style="width:90px">الشهر</th>
                    <th class="ctr" style="width:60px">طلبات<br><?= $lbl_cur_batch ?></th>
                    <?php if ($has_ref_batch): ?><th class="ctr prvh" style="width:60px">طلبات<br><?= $lbl_ref_batch ?></th><?php endif; ?>
                    <th class="ctr" style="width:70px">موظفون<br><?= $lbl_cur_batch ?></th>
                    <?php if ($has_ref_batch): ?><th class="ctr prvh" style="width:70px">موظفون<br><?= $lbl_ref_batch ?></th><?php endif; ?>
                    <th class="ctr" style="width:110px">مبلغ <?= $lbl_cur_batch ?></th>
                    <?php if ($has_ref_batch): ?>
                    <th class="ctr prvh" style="width:110px">مبلغ <?= $lbl_ref_batch ?></th>
                    <th class="ctr" style="width:95px">الفرق</th>
                    <th class="ctr" style="width:70px">الحالة</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $m_cur_amt = 0.0; $m_ref_amt = 0.0; $m_cur_req = 0; $m_ref_req = 0;
                foreach ($month_rows as $mr):
                    $m_cur_amt += (float)($mr['CUR_AMOUNT'] ?? 0);
                    $m_ref_amt += (float)($mr['REF_AMOUNT'] ?? 0);
                    $m_cur_req += (int)($mr['CUR_REQ_CNT'] ?? 0);
                    $m_ref_req += (int)($mr['REF_REQ_CNT'] ?? 0);
                    $m_state = (string)($mr['CHANGE_TYPE'] ?? '');
                ?>
                <tr>
                    <td><span class="pill cat-bank"><?= htmlspecialchars((string)($mr['MONTH_LABEL'] ?? $mr['THE_MONTH'] ?? '')) ?></span></td>
                    <td class="ctr"><?= $fmt_int($mr['CUR_REQ_CNT'] ?? 0) ?></td>
                    <?php if ($has_ref_batch): ?><td class="ctr prv"><?= $fmt_int($mr['REF_REQ_CNT'] ?? 0) ?></td><?php endif; ?>
                    <td class="ctr"><?= $fmt_int($mr['CUR_EMP_CNT'] ?? 0) ?></td>
                    <?php if ($has_ref_batch): ?><td class="ctr prv"><?= $fmt_int($mr['REF_EMP_CNT'] ?? 0) ?></td><?php endif; ?>
                    <td class="num"><?= n_format($mr['CUR_AMOUNT'] ?? 0) ?></td>
                    <?php if ($has_ref_batch): ?>
                    <td class="num prv"><?= n_format($mr['REF_AMOUNT'] ?? 0) ?></td>
                    <?= $render_diff_cell((float)($mr['CUR_AMOUNT'] ?? 0), (float)($mr['REF_AMOUNT'] ?? 0)) ?>
                    <td class="ctr">
                        <?php if ($m_state === 'NEW'): ?><span class="pill cat-heir">جديد</span>
                        <?php elseif ($m_state === 'LEFT'): ?><span class="pill cat-wallet">اختفى</span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td>الإجمالي</td>
                    <td class="ctr"><?= $fmt_int($m_cur_req) ?></td>
                    <?php if ($has_ref_batch): ?><td class="ctr prv"><?= $fmt_int($m_ref_req) ?></td><?php endif; ?>
                    <td class="ctr">—</td>
                    <?php if ($has_ref_batch): ?><td class="ctr prv">—</td><?php endif; ?>
                    <td class="num"><?= n_format($m_cur_amt) ?></td>
                    <?php if ($has_ref_batch): ?>
                    <td class="num prv"><?= n_format($m_ref_amt) ?></td>
                    <?= $render_diff_cell($m_cur_amt, $m_ref_amt) ?>
                    <td class="ctr">—</td>
                    <?php endif; ?>
                </tr>
            </tbody>
        </table>
        <?php if (abs($m_cur_amt - $cur_total_amt) > 0.01): ?>
        <div class="note-box" style="margin-top:6px">
            ⚠️ مجموع مبالغ الشهور (<?= n_format($m_cur_amt) ?>) لا يساوي إجمالي الدفعة (<?= n_format($cur_total_amt) ?>).
            الفرق <?= n_format(abs($m_cur_amt - $cur_total_amt)) ?> يعني أسطراً في توزيع الدفعة بلا ربط بطلب — راجعها قبل الصرف.
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- ════════ ④ مقارنة حسب الطلبات (نوع / عدد / مبلغ) ════════ -->
    <div class="section" id="sec-4">
        <div class="section-title">④ مقارنة حسب نوع الطلب (الأنواع الخمسة)</div>
        <div class="note-box" style="margin-bottom:6px">
            المبالغ والحركات من ملخّص البنود المحصور بالدفعة — لا من أعمدة التفاصيل التي
            تدمج «دفعة من المستحقات» مع «مستحقات حسب الشهر» في خانة واحدة.
            عدد الموظفين لا يُعرض هنا لأنه غير قابل للجمع عبر البنود (الموظف قد يرد في بندين داخل النوع نفسه).
        </div>
        <table class="rpt">
            <thead>
                <tr>
                    <th>نوع الطلب</th>
                    <th class="ctr" style="width:60px">طلبات<br><?= $lbl_cur_batch ?></th>
                    <?php if ($has_ref_batch): ?><th class="ctr prvh" style="width:60px">طلبات<br><?= $lbl_ref_batch ?></th><?php endif; ?>
                    <th class="ctr" style="width:65px">حركات<br><?= $lbl_cur_batch ?></th>
                    <?php if ($has_ref_batch): ?><th class="ctr prvh" style="width:65px">حركات<br><?= $lbl_ref_batch ?></th><?php endif; ?>
                    <th class="ctr" style="width:105px">مبلغ <?= $lbl_cur_batch ?></th>
                    <?php if ($has_ref_batch): ?>
                    <th class="ctr prvh" style="width:105px">مبلغ <?= $lbl_ref_batch ?></th>
                    <th class="ctr" style="width:95px">الفرق</th>
                    <th class="ctr" style="width:70px">الحالة</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $sum_cur_amt = 0.0; $sum_ref_amt = 0.0; $sum_cur_reqs = 0; $sum_ref_reqs = 0;
                $sum_cur_trx = 0;   $sum_ref_trx = 0;   $has_type_rows = false;
                foreach ($rt_all_keys as $tk):
                    $c_amt  = (float)($rt_cur[$tk]['amt'] ?? 0);
                    $p_amt  = (float)($rt_ref[$tk]['amt'] ?? 0);
                    $c_trx  = (int)($rt_cur[$tk]['trx'] ?? 0);
                    $p_trx  = (int)($rt_ref[$tk]['trx'] ?? 0);
                    $c_reqs = (int)($rt_cur_reqs[$tk]['n'] ?? 0);
                    $p_reqs = (int)($rt_ref_reqs[$tk]['n'] ?? 0);
                    // لا نُسقط نوعاً مبلغه صفر: قد تكون كل طلباته مستثناة، وهذا بذاته خبر
                    if ($c_amt == 0.0 && $p_amt == 0.0 && $c_reqs === 0 && $p_reqs === 0) continue;
                    $has_type_rows = true;
                    $sum_cur_amt += $c_amt;  $sum_ref_amt += $p_amt;
                    $sum_cur_reqs += $c_reqs; $sum_ref_reqs += $p_reqs;
                    $sum_cur_trx += $c_trx;  $sum_ref_trx += $p_trx;
                    $rt_state = (!$has_ref_batch) ? ''
                              : (($p_amt == 0.0 && $p_reqs === 0) ? 'NEW'
                              : (($c_amt == 0.0 && $c_reqs === 0) ? 'LEFT' : ''));
                ?>
                    <tr>
                        <td>
                            <span class="pill cat-bank"><?= htmlspecialchars($rt_name_of($tk)) ?></span>
                            <?php if ($c_amt == 0.0 && $c_reqs > 0): ?>
                            <small style="color:#b45309"> (طلبات بلا صرف في هذه الدفعة)</small>
                            <?php endif; ?>
                        </td>
                        <td class="ctr"><?= $fmt_int($c_reqs) ?></td>
                        <?php if ($has_ref_batch): ?><td class="ctr prv"><?= $fmt_int($p_reqs) ?></td><?php endif; ?>
                        <td class="ctr"><?= $fmt_int($c_trx) ?></td>
                        <?php if ($has_ref_batch): ?><td class="ctr prv"><?= $fmt_int($p_trx) ?></td><?php endif; ?>
                        <td class="num"><?= n_format($c_amt) ?></td>
                        <?php if ($has_ref_batch): ?>
                        <td class="num prv"><?= n_format($p_amt) ?></td>
                        <?= $render_diff_cell($c_amt, $p_amt) ?>
                        <td class="ctr">
                            <?php if ($rt_state === 'NEW'): ?><span class="pill cat-heir">جديد</span>
                            <?php elseif ($rt_state === 'LEFT'): ?><span class="pill cat-wallet">اختفى</span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$has_type_rows): ?>
                    <tr><td colspan="<?= $has_ref_batch ? 9 : 4 ?>" class="tbl-empty">لا توجد بيانات صرف</td></tr>
                <?php else: ?>
                    <tr class="total-row">
                        <td>الإجمالي</td>
                        <td class="ctr"><?= $fmt_int($sum_cur_reqs) ?></td>
                        <?php if ($has_ref_batch): ?><td class="ctr prv"><?= $fmt_int($sum_ref_reqs) ?></td><?php endif; ?>
                        <td class="ctr"><?= $fmt_int($sum_cur_trx) ?></td>
                        <?php if ($has_ref_batch): ?><td class="ctr prv"><?= $fmt_int($sum_ref_trx) ?></td><?php endif; ?>
                        <td class="num"><?= n_format($sum_cur_amt) ?></td>
                        <?php if ($has_ref_batch): ?>
                        <td class="num prv"><?= n_format($sum_ref_amt) ?></td>
                        <?= $render_diff_cell($sum_cur_amt, $sum_ref_amt) ?>
                        <td class="ctr">—</td>
                        <?php endif; ?>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ════════ ⑤ توزيع الحركات: كم موظف له كم حركة ════════ -->
    <div class="section allow-break" id="sec-5">
        <div class="section-title">⑤ توزيع الحركات — كم موظف له كم حركة</div>
        <div class="note-box" style="margin-bottom:6px">
            الحركة = سطر تحويل بنكي واحد (موظف × حساب) داخل وجهة صرف واحدة.
            زيادة الحركات عن عدد الموظفين سببها التوزيع على أكثر من حساب أو توجيه خاص لبعض الطلبات
            — <b>وليست موظفين إضافيين</b>.
        </div>
        <table class="rpt">
            <thead>
                <tr>
                    <th>عدد الحركات للموظف</th>
                    <th class="ctr" style="width:90px">موظفون<br><?= $lbl_cur_batch ?></th>
                    <?php if ($has_ref_batch): ?><th class="ctr prvh" style="width:90px">موظفون<br><?= $lbl_ref_batch ?></th>
                    <th class="ctr" style="width:80px">الفرق</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mov_bucket_labels as $bk => $blbl):
                    $c_b = (int)($mov_cur['dist'][$bk] ?? 0);
                    $r_b = (int)($mov_ref['dist'][$bk] ?? 0); ?>
                <tr>
                    <td><span class="pill cat-bank"><?= $blbl ?></span></td>
                    <td class="ctr" style="font-weight:700"><?= $fmt_int($c_b) ?></td>
                    <?php if ($has_ref_batch): ?><td class="ctr prv"><?= $fmt_int($r_b) ?></td>
                    <?= $render_diff_cell($c_b, $r_b, false) ?><?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td>الإجمالي</td>
                    <td class="ctr"><?= $fmt_int($mov_cur['emps']) ?> موظف / <?= $fmt_int($mov_cur['sum']) ?> حركة</td>
                    <?php if ($has_ref_batch): ?>
                    <td class="ctr prv"><?= $fmt_int($mov_ref['emps']) ?> موظف / <?= $fmt_int($mov_ref['sum']) ?> حركة</td>
                    <td class="ctr">—</td>
                    <?php endif; ?>
                </tr>
            </tbody>
        </table>
        <?php if (!$mov_cur_ok || !$mov_ref_ok): ?>
        <div class="note-box" style="margin-top:6px; background:#fef2f2; border-color:#fecaca; color:#991b1b">
            ⚠️ عدد الحركات المحسوب من الموظفين (<?= $fmt_int($mov_cur['sum']) ?>) يتجاوز المسجّل على الدفعة
            (<?= $fmt_int($cur_mov_count) ?>) — خلل في بيانات التوزيع، راجعه قبل الصرف.
        </div>
        <?php elseif ($mov_cur_legacy > 0 || $mov_ref_legacy > 0): ?>
        <div class="note-box" style="margin-top:6px">
            تسوية: التوزيع أعلاه يَعدّ الحركات ذات حساب الصرف المحدد.
            <?php if ($mov_cur_legacy > 0): ?>
            في <?= $lbl_cur_batch ?> <b><?= $fmt_int($mov_cur_legacy) ?></b> حركة على الحساب القديم (بلا حساب محدد)
            — <?= $fmt_int($mov_cur['sum']) ?> + <?= $fmt_int($mov_cur_legacy) ?> = <?= $fmt_int($cur_mov_count) ?> المسجّل على الدفعة.
            <?php endif; ?>
            <?php if ($has_ref_batch && $mov_ref_legacy > 0): ?>
            وفي <?= $lbl_ref_batch ?> <b><?= $fmt_int($mov_ref_legacy) ?></b> حركة.
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ════════ ⑥ مقارنة الإجماليات حسب الفروع ════════ -->
    <div class="section" id="sec-6">
        <div class="section-title">⑥ مقارنة الإجماليات حسب الفروع (المقرات)</div>
        <table class="rpt">
            <thead>
                <tr>
                    <th class="ctr" style="width:30px">#</th>
                    <th>المقر</th>
                    <th class="ctr" style="width:70px">موظفون<br><?= $lbl_cur_batch ?></th>
                    <?php if ($has_ref_batch): ?><th class="ctr prvh" style="width:70px">موظفون<br><?= $lbl_ref_batch ?></th><?php endif; ?>
                    <th class="ctr" style="width:110px">مبلغ <?= $lbl_cur_batch ?></th>
                    <?php if ($has_ref_batch): ?>
                    <th class="ctr prvh" style="width:110px">مبلغ <?= $lbl_ref_batch ?></th>
                    <th class="ctr" style="width:100px">الفرق</th>
                    <th class="ctr" style="width:65px">النسبة</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $branch_keys = $merge_row_keys($cur_by_branch, $ref_by_branch, 'amt');
                if (empty($branch_keys)): ?>
                    <tr><td colspan="<?= $has_ref_batch ? 8 : 4 ?>" class="tbl-empty">لا توجد بيانات صرف</td></tr>
                <?php else:
                    $sum_cur_amt = 0.0; $sum_ref_amt = 0.0; $sum_cur_count = 0; $sum_ref_count = 0; $i = 0;
                    foreach ($branch_keys as $key):
                        $i++;
                        $cur_row = $cur_by_branch[$key] ?? ['count' => 0, 'amt' => 0.0];
                        $ref_row = $ref_by_branch[$key] ?? ['count' => 0, 'amt' => 0.0];
                        $sum_cur_amt += $cur_row['amt']; $sum_ref_amt += $ref_row['amt'];
                        $sum_cur_count += $cur_row['count']; $sum_ref_count += $ref_row['count'];
                ?>
                    <tr>
                        <td class="ctr"><?= $i ?></td>
                        <td><?= htmlspecialchars($key) ?></td>
                        <td class="ctr"><?= $fmt_int($cur_row['count']) ?></td>
                        <?php if ($has_ref_batch): ?><td class="ctr prv"><?= $fmt_int($ref_row['count']) ?></td><?php endif; ?>
                        <td class="num"><?= n_format($cur_row['amt']) ?></td>
                        <?php if ($has_ref_batch): ?>
                        <td class="num prv"><?= n_format($ref_row['amt']) ?></td>
                        <?= $render_diff_cell($cur_row['amt'], $ref_row['amt']) ?>
                        <?= $render_pct_cell($cur_row['amt'], $ref_row['amt']) ?>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                    <tr class="total-row">
                        <td class="ctr" colspan="2">الإجمالي</td>
                        <td class="ctr"><?= $fmt_int($sum_cur_count) ?></td>
                        <?php if ($has_ref_batch): ?><td class="ctr prv"><?= $fmt_int($sum_ref_count) ?></td><?php endif; ?>
                        <td class="num"><?= n_format($sum_cur_amt) ?></td>
                        <?php if ($has_ref_batch): ?>
                        <td class="num prv"><?= n_format($sum_ref_amt) ?></td>
                        <?= $render_diff_cell($sum_cur_amt, $sum_ref_amt) ?>
                        <?= $render_pct_cell($sum_cur_amt, $sum_ref_amt) ?>
                        <?php endif; ?>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ════════ ⑦ مقارنة الإجماليات حسب البنوك ════════ -->
    <div class="section" id="sec-7">
        <div class="section-title">⑦ مقارنة الإجماليات حسب البنوك والمحافظ</div>
        <table class="rpt">
            <thead>
                <tr>
                    <th class="ctr" style="width:30px">#</th>
                    <th>البنك / المحفظة</th>
                    <th class="ctr" style="width:70px">موظفون<br><?= $lbl_cur_batch ?></th>
                    <?php if ($has_ref_batch): ?><th class="ctr prvh" style="width:70px">موظفون<br><?= $lbl_ref_batch ?></th><?php endif; ?>
                    <th class="ctr" style="width:110px">مبلغ <?= $lbl_cur_batch ?></th>
                    <?php if ($has_ref_batch): ?>
                    <th class="ctr prvh" style="width:110px">مبلغ <?= $lbl_ref_batch ?></th>
                    <th class="ctr" style="width:100px">الفرق</th>
                    <th class="ctr" style="width:65px">النسبة</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $bank_keys = $merge_row_keys($cur_by_bank, $ref_by_bank, 'amt');
                if (empty($bank_keys)): ?>
                    <tr><td colspan="<?= $has_ref_batch ? 8 : 4 ?>" class="tbl-empty">لا توجد بيانات صرف</td></tr>
                <?php else:
                    $sum_cur_amt = 0.0; $sum_ref_amt = 0.0; $sum_cur_count = 0; $sum_ref_count = 0; $i = 0;
                    foreach ($bank_keys as $key):
                        $i++;
                        $cur_row = $cur_by_bank[$key] ?? ['emp' => 0, 'trx' => 0, 'amt' => 0.0, 'ptype' => 1];
                        $ref_row = $ref_by_bank[$key] ?? ['emp' => 0, 'trx' => 0, 'amt' => 0.0, 'ptype' => $cur_row['ptype']];
                        $provider_type = isset($cur_by_bank[$key]) ? $cur_row['ptype'] : $ref_row['ptype'];
                        $sum_cur_amt += $cur_row['amt']; $sum_ref_amt += $ref_row['amt'];
                        $sum_cur_count += $cur_row['emp']; $sum_ref_count += $ref_row['emp'];
                ?>
                    <tr>
                        <td class="ctr"><?= $i ?></td>
                        <td>
                            <span class="pill <?= $provider_type === 2 ? 'cat-wallet' : 'cat-bank' ?>"><?= $provider_type === 2 ? 'محفظة' : 'بنك' ?></span>
                            <?= htmlspecialchars($key) ?>
                        </td>
                        <td class="ctr"><?= $fmt_int($cur_row['emp']) ?></td>
                        <?php if ($has_ref_batch): ?><td class="ctr prv"><?= $fmt_int($ref_row['emp']) ?></td><?php endif; ?>
                        <td class="num"><?= n_format($cur_row['amt']) ?></td>
                        <?php if ($has_ref_batch): ?>
                        <td class="num prv"><?= n_format($ref_row['amt']) ?></td>
                        <?= $render_diff_cell($cur_row['amt'], $ref_row['amt']) ?>
                        <?= $render_pct_cell($cur_row['amt'], $ref_row['amt']) ?>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                    <tr class="total-row">
                        <td class="ctr" colspan="2">الإجمالي</td>
                        <!-- الموظف الموزّع على أكثر من بنك يظهر في كل بنك → جمع العمود لا يعطي عدداً حقيقياً -->
                        <td class="ctr">—</td>
                        <?php if ($has_ref_batch): ?><td class="ctr prv">—</td><?php endif; ?>
                        <td class="num"><?= n_format($sum_cur_amt) ?></td>
                        <?php if ($has_ref_batch): ?>
                        <td class="num prv"><?= n_format($sum_ref_amt) ?></td>
                        <?= $render_diff_cell($sum_cur_amt, $sum_ref_amt) ?>
                        <?= $render_pct_cell($sum_cur_amt, $sum_ref_amt) ?>
                        <?php endif; ?>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="tbl-note">
            ⓘ عدد الموظفين لا يُجمع — الموظف الموزّع على أكثر من بنك يظهر في كل بنك.
            العدد الفعلي: <b><?= $lbl_cur_batch ?> = <?= $fmt_int($cur_emp_count) ?></b> موظف<?= $has_ref_batch ? ' · <b>' . $lbl_ref_batch . ' = ' . $fmt_int($ref_emp_count) . '</b> موظف' : '' ?>.
        </div>
    </div>

    <!-- ════════ ⑧ مقارنة الإجماليات حسب نوع الوجهة ════════ -->
    <div class="section" id="sec-8">
        <div class="section-title">⑧ مقارنة الإجماليات حسب نوع الوجهة (بنك / محفظة / وريث)</div>
        <table class="rpt">
            <thead>
                <tr>
                    <th>الوجهة</th>
                    <th class="ctr" style="width:65px">موظفون<br><?= $lbl_cur_batch ?></th>
                    <?php if ($has_ref_batch): ?><th class="ctr prvh" style="width:65px">موظفون<br><?= $lbl_ref_batch ?></th><?php endif; ?>
                    <th class="ctr" style="width:65px">حركات<br><?= $lbl_cur_batch ?></th>
                    <?php if ($has_ref_batch): ?><th class="ctr prvh" style="width:65px">حركات<br><?= $lbl_ref_batch ?></th><?php endif; ?>
                    <th class="ctr" style="width:105px">مبلغ <?= $lbl_cur_batch ?></th>
                    <?php if ($has_ref_batch): ?>
                    <th class="ctr prvh" style="width:105px">مبلغ <?= $lbl_ref_batch ?></th>
                    <th class="ctr" style="width:95px">الفرق</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $dest_keys = $merge_row_keys($cur_by_dest, $ref_by_dest, 'amt');
                if (empty($dest_keys)): ?>
                    <tr><td colspan="<?= $has_ref_batch ? 8 : 4 ?>" class="tbl-empty">لا توجد بيانات صرف</td></tr>
                <?php else:
                    $sum_cur_amt = 0.0; $sum_ref_amt = 0.0;
                    foreach ($dest_keys as $key):
                        $cur_row = $cur_by_dest[$key] ?? ['emp' => 0, 'trx' => 0, 'amt' => 0.0, 'ord' => 9];
                        $ref_row = $ref_by_dest[$key] ?? ['emp' => 0, 'trx' => 0, 'amt' => 0.0, 'ord' => $cur_row['ord']];
                        $cat_order = isset($cur_by_dest[$key]) ? $cur_row['ord'] : $ref_row['ord'];
                        $pill_class = $dest_cat_class[$cat_order] ?? 'cat-bank';
                        $sum_cur_amt += $cur_row['amt']; $sum_ref_amt += $ref_row['amt'];
                ?>
                    <tr>
                        <td><span class="pill <?= $pill_class ?>"><?= htmlspecialchars($key) ?></span></td>
                        <td class="ctr"><?= $fmt_int($cur_row['emp']) ?></td>
                        <?php if ($has_ref_batch): ?><td class="ctr prv"><?= $fmt_int($ref_row['emp']) ?></td><?php endif; ?>
                        <td class="ctr"><?= $fmt_int($cur_row['trx']) ?></td>
                        <?php if ($has_ref_batch): ?><td class="ctr prv"><?= $fmt_int($ref_row['trx']) ?></td><?php endif; ?>
                        <td class="num"><?= n_format($cur_row['amt']) ?></td>
                        <?php if ($has_ref_batch): ?>
                        <td class="num prv"><?= n_format($ref_row['amt']) ?></td>
                        <?= $render_diff_cell($cur_row['amt'], $ref_row['amt']) ?>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                    <tr class="total-row">
                        <td>الإجمالي</td>
                        <!-- الموظف الموزّع (بنك + محفظة) يظهر في الوجهتين → لا يُجمع؛ الحركات تُجمع طبيعياً -->
                        <td class="ctr">—</td>
                        <?php if ($has_ref_batch): ?><td class="ctr prv">—</td><?php endif; ?>
                        <td class="ctr"><?= $fmt_int(array_sum(array_map(function($dest_row){return $dest_row['trx'];}, $cur_by_dest))) ?></td>
                        <?php if ($has_ref_batch): ?><td class="ctr prv"><?= $fmt_int(array_sum(array_map(function($dest_row){return $dest_row['trx'];}, $ref_by_dest))) ?></td><?php endif; ?>
                        <td class="num"><?= n_format($sum_cur_amt) ?></td>
                        <?php if ($has_ref_batch): ?>
                        <td class="num prv"><?= n_format($sum_ref_amt) ?></td>
                        <?= $render_diff_cell($sum_cur_amt, $sum_ref_amt) ?>
                        <?php endif; ?>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="tbl-note">
            ⓘ عدد الموظفين لا يُجمع — الموظف الموزّع بين بنك ومحفظة يظهر في الوجهتين (لذلك مجموع الأعمدة أكبر من عدد موظفي الدفعة).
            العدد الفعلي: <b><?= $lbl_cur_batch ?> = <?= $fmt_int($cur_emp_count) ?></b> موظف<?= $has_ref_batch ? ' · <b>' . $lbl_ref_batch . ' = ' . $fmt_int($ref_emp_count) . '</b> موظف' : '' ?>.
        </div>
    </div>

    <div class="section allow-break" id="sec-9">
        <div class="section-title">⑨ مقارنة حسب الموظفين<?= $has_ref_batch ? ' <span class="vs">' . $lbl_cur_batch . ' مقابل ' . $lbl_ref_batch . '</span>' : '' ?></div>

        <?php if (!$has_ref_batch): ?>
            <div class="note-box">لا توجد دفعة مرجعية — لا يمكن عرض مقارنة الموظفين.</div>
        <?php else: ?>
            <!-- بطاقات مقارنة سريعة -->
            <div class="cmp-grid">
                <div class="cmp">
                    <div class="lbl">إجمالي الصرف — <?= $lbl_cur_batch ?></div>
                    <div class="val"><?= n_format($cur_total_amt) ?></div>
                </div>
                <div class="cmp">
                    <div class="lbl">إجمالي الصرف — <?= $lbl_ref_batch ?></div>
                    <div class="val" style="color:#64748b"><?= n_format($ref_total_amt) ?></div>
                </div>
                <div class="cmp">
                    <div class="lbl">فرق المبلغ</div>
                    <?php $amt_dir_class = $total_amt_diff > 0 ? 'up' : ($total_amt_diff < 0 ? 'down' : 'eq'); ?>
                    <div class="val <?= $amt_dir_class ?>"><?= ($total_amt_diff >= 0 ? '+' : '') . n_format($total_amt_diff) ?></div>
                    <div class="sub <?= $amt_dir_class ?>"><?= ($total_amt_pct >= 0 ? '+' : '') . $total_amt_pct ?>%</div>
                </div>
                <div class="cmp">
                    <div class="lbl">فرق عدد الموظفين</div>
                    <?php $emp_dir_class = $emp_count_diff > 0 ? 'up' : ($emp_count_diff < 0 ? 'down' : 'eq'); ?>
                    <div class="val <?= $emp_dir_class ?>"><?= ($emp_count_diff >= 0 ? '+' : '') . $fmt_int($emp_count_diff) ?></div>
                    <div class="sub eq"><?= $fmt_int($ref_emp_count) ?> ← <?= $fmt_int($cur_emp_count) ?></div>
                </div>
            </div>

            <!-- جدول حركة الموظفين (جدد/خرجوا/تغيّروا) -->
            <table class="rpt" style="margin-bottom:10px">
                <thead>
                    <tr>
                        <th>حركة الموظفين بين الدفعتين</th>
                        <th class="ctr" style="width:90px">العدد</th>
                        <th class="ctr" style="width:140px">المبلغ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="pill cat-wallet">موظفون جدد</span></td>
                        <td class="ctr"><?= $fmt_int($emp_new_count) ?></td>
                        <td class="num up">+<?= n_format($emp_new_amt) ?></td>
                    </tr>
                    <tr>
                        <td><span class="pill cat-heir">موظفون خرجوا</span></td>
                        <td class="ctr"><?= $fmt_int($emp_left_count) ?></td>
                        <td class="num down">−<?= n_format($emp_left_amt) ?></td>
                    </tr>
                    <tr>
                        <td><span class="pill cat-bank">تغيّر مبلغهم</span></td>
                        <td class="ctr"><?= $fmt_int($emp_changed_count) ?></td>
                        <td class="num <?= $emp_net_change >= 0 ? 'up' : 'down' ?>"><?= ($emp_net_change >= 0 ? '+' : '−') . n_format(abs($emp_net_change)) ?></td>
                    </tr>
                    <tr>
                        <td>بدون تغيير</td>
                        <td class="ctr"><?= $fmt_int($emp_same_count) ?></td>
                        <td class="num">—</td>
                    </tr>
                    <tr class="total-row">
                        <td>صافي التغيير في المبلغ (مقابل <?= $lbl_ref_batch ?>)</td>
                        <td class="ctr">—</td>
                        <td class="num <?= $total_amt_diff >= 0 ? 'up' : 'down' ?>"><?= ($total_amt_diff >= 0 ? '+' : '') . n_format($total_amt_diff) ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- قوائم الموظفين بالأسماء -->
            <?php
            // جدول موظفين موحّد: مبلغ الدفعتين + الفرق + النسبة (نفس بنية جداول الإجماليات)
            $render_emp_diff_table = function ($title, $rows, $fmt_int, $top_n, $lbl_cur_batch, $lbl_ref_batch) {
                $cnt = count($rows);
                if ($cnt === 0) return;
                $shown = array_slice($rows, 0, $top_n);
                $rest_cur_amt = 0.0; $rest_ref_amt = 0.0;
                if ($cnt > $top_n) {
                    foreach (array_slice($rows, $top_n) as $rest_row) {
                        $rest_cur_amt += (float)($rest_row['CURRENT_AMOUNT'] ?? 0);
                        $rest_ref_amt += (float)($rest_row['PREVIOUS_AMOUNT'] ?? 0);
                    }
                }
                // خلية الفرق ملوّنة + نسبة التغير (DIFF_PCT من الباكج، وإلا تُحتسب)
                $pct_txt = function ($cur_amt, $ref_amt, $pct_from_pkg) {
                    if ($pct_from_pkg !== null && $pct_from_pkg !== '') {
                        return ((float)$pct_from_pkg > 0 ? '+' : '') . round((float)$pct_from_pkg, 1) . '%';
                    }
                    if ($ref_amt == 0.0) return $cur_amt != 0.0 ? 'جديد' : '—';
                    if ($cur_amt == 0.0) return '−100%';
                    $pct_val = round(($cur_amt - $ref_amt) / abs($ref_amt) * 100, 1);
                    return ($pct_val > 0 ? '+' : '') . $pct_val . '%';
                };
                ?>
                <table class="rpt" style="margin-bottom:8px">
                    <thead>
                        <tr>
                            <th colspan="8" style="text-align:right"><?= $title ?> <span style="font-weight:400;color:#cbd5e1">(<?= $fmt_int($cnt) ?> موظف)</span></th>
                        </tr>
                        <tr>
                            <th class="ctr" style="width:30px">#</th>
                            <th class="ctr" style="width:70px">رقم الموظف</th>
                            <th>الاسم</th>
                            <th style="width:100px">المقر</th>
                            <th class="ctr" style="width:100px">مبلغ <?= $lbl_cur_batch ?></th>
                            <th class="ctr prvh" style="width:100px">مبلغ <?= $lbl_ref_batch ?></th>
                            <th class="ctr" style="width:95px">الفرق</th>
                            <th class="ctr" style="width:60px">النسبة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 0; foreach ($shown as $row): $i++;
                            $row_cur_amt = (float)($row['CURRENT_AMOUNT'] ?? 0);
                            $row_ref_amt = (float)($row['PREVIOUS_AMOUNT'] ?? 0);
                            $row_diff = $row_cur_amt - $row_ref_amt;
                            $dir_class = $row_diff > 0.009 ? 'up' : ($row_diff < -0.009 ? 'down' : 'eq');
                        ?>
                        <tr>
                            <td class="ctr"><?= $i ?></td>
                            <td class="ctr"><?= htmlspecialchars($row['EMP_NO'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['EMP_NAME'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['BRANCH_NAME'] ?? '') ?></td>
                            <td class="num"><?= $row_cur_amt != 0.0 ? n_format($row_cur_amt) : '—' ?></td>
                            <td class="num prv"><?= $row_ref_amt != 0.0 ? n_format($row_ref_amt) : '—' ?></td>
                            <td class="num <?= $dir_class ?>"><?= ($row_diff > 0.009 ? '+' : ($row_diff < -0.009 ? '−' : '')) . n_format(abs($row_diff)) ?></td>
                            <td class="ctr <?= $dir_class ?>"><?= $pct_txt($row_cur_amt, $row_ref_amt, $row['DIFF_PCT'] ?? null) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if ($cnt > $top_n): $rest_diff = $rest_cur_amt - $rest_ref_amt; ?>
                        <tr class="total-row">
                            <td colspan="4">… و<?= $fmt_int($cnt - $top_n) ?> موظف آخرين</td>
                            <td class="num"><?= $rest_cur_amt != 0.0 ? n_format($rest_cur_amt) : '—' ?></td>
                            <td class="num prv"><?= $rest_ref_amt != 0.0 ? n_format($rest_ref_amt) : '—' ?></td>
                            <td class="num <?= $rest_diff >= 0 ? 'up' : 'down' ?>"><?= ($rest_diff >= 0 ? '+' : '−') . n_format(abs($rest_diff)) ?></td>
                            <td class="ctr">—</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php
            };

            if ($emps_increased || $emps_decreased || $emps_new || $emps_left):
            ?>
            <div style="font-weight:800;color:#0f172a;margin:4px 0 6px">تفاصيل التغيّرات بالأسماء (حتى <?= $fmt_int($top_n) ?> لكل فئة — الأكبر أولاً)</div>
            <?php
                $render_emp_diff_table('موظفون جدد (غير موجودين في ' . $lbl_ref_batch . ')', $emps_new,  $fmt_int, $top_n, $lbl_cur_batch, $lbl_ref_batch);
                $render_emp_diff_table('موظفون خرجوا (كانوا في ' . $lbl_ref_batch . ')',     $emps_left, $fmt_int, $top_n, $lbl_cur_batch, $lbl_ref_batch);
                $render_emp_diff_table('أكبر الزيادات',                               $emps_increased,   $fmt_int, $top_n, $lbl_cur_batch, $lbl_ref_batch);
                $render_emp_diff_table('أكبر النقصانات',                              $emps_decreased, $fmt_int, $top_n, $lbl_cur_batch, $lbl_ref_batch);
            endif;
            ?>

        <?php endif; ?>
    </div>

    <!-- ════════ ⑩ الموظفون المستثنون + السبب (الدفعة الحالية) ════════ -->
    <div class="section" id="sec-10">
        <div class="section-title">⑩ الموظفون المستثنون من الصرف + السبب (الدفعة <?= $lbl_cur_batch ?>)</div>
        <table class="rpt">
            <thead>
                <tr>
                    <th class="ctr" style="width:35px">#</th>
                    <th class="ctr" style="width:80px">رقم الموظف</th>
                    <th>الاسم</th>
                    <th style="width:130px">المقر</th>
                    <th class="ctr" style="width:60px">طلبات</th>
                    <th class="ctr" style="width:110px">المبلغ المطلوب</th>
                    <th style="width:180px">السبب</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($excluded_rows)): ?>
                    <tr><td colspan="7" class="tbl-empty">✓ لا يوجد موظفون مستثنون — كل موظفي الطلبات صُرف لهم</td></tr>
                <?php else: $i = 0; foreach ($excluded_rows as $exc_row):
                    $i++;
                    $reason = $exc_row['REASON'] ?? '';
                    $reason_class   = (mb_strpos($reason, 'لا يوجد') !== false) ? 'reason-no' : 'reason-x';
                ?>
                    <tr>
                        <td class="ctr"><?= $i ?></td>
                        <td class="ctr"><?= htmlspecialchars($exc_row['EMP_NO'] ?? '') ?></td>
                        <td><?= htmlspecialchars($exc_row['EMP_NAME'] ?? '') ?></td>
                        <td><?= htmlspecialchars($exc_row['BRANCH_NAME'] ?? '') ?></td>
                        <td class="ctr"><?= $fmt_int($exc_row['REQ_COUNT'] ?? 0) ?></td>
                        <td class="num"><?= n_format((float)($exc_row['REQ_AMOUNT'] ?? 0)) ?></td>
                        <td class="<?= $reason_class ?>"><?= htmlspecialchars($reason) ?></td>
                    </tr>
                <?php endforeach; ?>
                    <tr class="total-row">
                        <td class="ctr" colspan="4">الإجمالي — <?= $fmt_int($excluded_count) ?> موظف</td>
                        <td class="ctr">&nbsp;</td>
                        <td class="num"><?= n_format($excluded_total_amt) ?></td>
                        <td>&nbsp;</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ════════ ⑪ سجل آخر الدفعات (الترند) ════════ -->
    <div class="section" id="sec-11">
        <div class="section-title">⑪ سجل آخر الدفعات</div>
        <?php $trend_list = $trend_rows; ?>
        <table class="rpt">
            <thead>
                <tr>
                    <th class="ctr" style="width:35px">#</th>
                    <th>الدفعة</th>
                    <th style="width:100px">الحالة</th>
                    <th style="width:110px">التاريخ</th>
                    <th class="ctr" style="width:80px">الموظفون</th>
                    <th class="ctr" style="width:130px">الإجمالي</th>
                    <th class="ctr" style="width:115px">الفرق عن السابقة</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($trend_list)): ?>
                    <tr><td colspan="7" class="tbl-empty">لا يوجد سجل دفعات</td></tr>
                <?php else: foreach ($trend_list as $idx => $trend_row):
                    $is_current_batch = (int)($trend_row['IS_CURRENT'] ?? 0) === 1;
                    $trend_amt  = (float)($trend_row['TOTAL_AMOUNT'] ?? 0);
                    $older_amt  = isset($trend_list[$idx + 1]) ? (float)($trend_list[$idx + 1]['TOTAL_AMOUNT'] ?? 0) : null;
                    $trend_delta  = $older_amt !== null ? $trend_amt - $older_amt : null;
                    $dir_class   = $trend_delta === null ? 'eq' : ($trend_delta > 0 ? 'up' : ($trend_delta < 0 ? 'down' : 'eq'));
                ?>
                    <tr<?= $is_current_batch ? ' style="background:#ecfdf5;font-weight:700"' : '' ?>>
                        <td class="ctr"><?= $idx + 1 ?></td>
                        <td><?= htmlspecialchars($trend_row['BATCH_NO'] ?? '') ?><?= $is_current_batch ? ' <span class="pill cat-wallet">الحالية</span>' : '' ?></td>
                        <td><?= htmlspecialchars($trend_row['STATUS_NAME'] ?? '') ?></td>
                        <td><?= htmlspecialchars($trend_row['ENTRY_DATE_STR'] ?? '') ?></td>
                        <td class="ctr"><?= $fmt_int($trend_row['EMP_COUNT'] ?? 0) ?></td>
                        <td class="num"><?= n_format($trend_amt) ?></td>
                        <td class="num <?= $dir_class ?>"><?= $trend_delta === null ? '—' : (($trend_delta >= 0 ? '+' : '') . n_format($trend_delta)) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ════════ ⑫ مقارنة حسب البند ════════ -->
    <div class="section allow-break" id="sec-12">
        <div class="section-title">⑫ مقارنة حسب البند<?= $pt_pick !== 'all' ? ' — ' . htmlspecialchars($pt_options[$pt_pick]) : '' ?><?= $has_ref_batch ? ' <span class="vs">' . $lbl_cur_batch . ' مقابل ' . $lbl_ref_batch . '</span>' : '' ?></div>

        <?php
        // ─── ⑨ العرض (التجهيز في مقدمة الملف — الشريط يحتاج قائمة البنود مبكراً) ───
        // تفصيل الموظفين مجمّعاً تحت كل بند — الجميع (الغاية: كم أخذ كل موظف على هذا
        // البند في كل دفعة، لا الحركة فقط). الترتيب من الباكج: الأكبر فرقاً أولاً ثم
        // الثابتون برقم الموظف — فتبقى الحركة في الأعلى دون إخفاء أحد.
        // القرار لليوزر من شريط التقرير: "الكل" أو "المتغيّرون فقط" (?ptmode=)
        $pt_only_changed = ($pt_mode === 'changed');
        $emps_by_pt = [];
        $changed_by_pt = [];
        $total_by_pt = [];
        // تفكيك حركة كل بند لفئات متنافية: جديد + لم يتكرر + تغيّر + بلا تغيير = الإجمالي
        // (يُحسب قبل فلترة العرض ليبقى الملخص كاملاً مهما كان وضع العرض)
        $stats_by_pt = [];
        foreach ($pt_emp_rows as $emp_row) {
            $row_key = $pt_key($emp_row);
            $row_change = $emp_row['CHANGE_TYPE'] ?? '';
            $is_changed = ($row_change !== 'SAME');
            $total_by_pt[$row_key] = ($total_by_pt[$row_key] ?? 0) + 1;
            $stats_by_pt[$row_key][$row_change] = ($stats_by_pt[$row_key][$row_change] ?? 0) + 1;
            if ($is_changed) $changed_by_pt[$row_key] = ($changed_by_pt[$row_key] ?? 0) + 1;
            if ($pt_only_changed && !$is_changed) continue;
            $emps_by_pt[$row_key][] = $emp_row;
        }
        // اختيار البند من الشريط يفلتر الملخص والتفصيل معاً — فيطابق عنوان القسم
        $pt_visible_rows = [];
        foreach ($pt_rows as $pt_row) {
            if ($pt_pick !== 'all' && $pt_key($pt_row) !== $pt_pick) continue;
            $pt_visible_rows[] = $pt_row;
        }
        $pt_sum_cur = 0.0; $pt_sum_ref = 0.0;
        foreach ($pt_visible_rows as $pt_row) {
            $pt_sum_cur += (float)($pt_row['CUR_AMOUNT'] ?? 0);
            $pt_sum_ref += (float)($pt_row['REF_AMOUNT'] ?? 0);
        }
        ?>

        <?php if (empty($pt_rows)): ?>
            <div class="note-box">لا توجد بنود صرف في هذه الدفعة.</div>
        <?php else: ?>

        <!-- ⑨-أ ملخص البنود -->
        <table class="rpt">
            <thead>
                <tr>
                    <th>البند</th>
                    <th class="ctr" style="width:65px">موظفون<br><?= $lbl_cur_batch ?></th>
                    <?php if ($has_ref_batch): ?><th class="ctr prvh" style="width:65px">موظفون<br><?= $lbl_ref_batch ?></th><?php endif; ?>
                    <th class="ctr" style="width:105px">مبلغ <?= $lbl_cur_batch ?></th>
                    <?php if ($has_ref_batch): ?>
                    <th class="ctr prvh" style="width:105px">مبلغ <?= $lbl_ref_batch ?></th>
                    <th class="ctr" style="width:95px">الفرق</th>
                    <th class="ctr" style="width:65px">النسبة</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pt_visible_rows as $pt_row):
                    $pt_cur = (float)($pt_row['CUR_AMOUNT'] ?? 0);
                    $pt_ref = (float)($pt_row['REF_AMOUNT'] ?? 0);
                    $pt_name = $pt_name_of($pt_row);
                    $pt_change = $pt_row['CHANGE_TYPE'] ?? '';
                ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($pt_name) ?>
                            <?php
                            $pt_is_ben = ((int)($pt_row['IS_BENEFIT'] ?? 0) === 1);
                            $pt_types  = array_keys($req_types_by_pt[$pt_key($pt_row)] ?? []);
                            if (empty($pt_types) && $pt_is_ben) $pt_types = ['استحقاقات وإضافات'];
                            foreach ($pt_types as $pt_type_nm): ?>
                                <span class="pill" style="background:<?= $pt_is_ben ? '#ede9fe' : '#e0f2fe' ?>;color:<?= $pt_is_ben ? '#5b21b6' : '#075985' ?>"
                                      title="نوع الطلب الذي جاء منه هذا البند"><?= htmlspecialchars($pt_type_nm) ?></span>
                            <?php endforeach; ?>
                            <?php if ($has_ref_batch && $pt_change === 'NEW'): ?>
                                <span class="pill" style="background:#dcfce7;color:#166534">جديد</span>
                            <?php elseif ($has_ref_batch && $pt_change === 'LEFT'): ?>
                                <span class="pill" style="background:#fee2e2;color:#991b1b">لم يتكرر</span>
                            <?php endif; ?>
                        </td>
                        <td class="ctr"><?= $fmt_int($pt_row['CUR_EMP_CNT'] ?? 0) ?></td>
                        <?php if ($has_ref_batch): ?><td class="ctr prv"><?= $fmt_int($pt_row['REF_EMP_CNT'] ?? 0) ?></td><?php endif; ?>
                        <td class="num"><?= n_format($pt_cur) ?></td>
                        <?php if ($has_ref_batch): ?>
                        <td class="num prv"><?= n_format($pt_ref) ?></td>
                        <?= $render_diff_cell($pt_cur, $pt_ref) ?>
                        <?= $render_pct_cell($pt_cur, $pt_ref) ?>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($pt_visible_rows) > 1): /* ببند واحد الإجمالي = نفس الصف */ ?>
                <tr class="total-row">
                    <td>الإجمالي</td>
                    <td class="ctr">—</td>
                    <?php if ($has_ref_batch): ?><td class="ctr prv">—</td><?php endif; ?>
                    <td class="num"><?= n_format($pt_sum_cur) ?></td>
                    <?php if ($has_ref_batch): ?>
                    <td class="num prv"><?= n_format($pt_sum_ref) ?></td>
                    <?= $render_diff_cell($pt_sum_cur, $pt_sum_ref) ?>
                    <?= $render_pct_cell($pt_sum_cur, $pt_sum_ref) ?>
                    <?php endif; ?>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if ($pt_pick !== 'all'): ?>
        <div class="tbl-note" style="color:#1e40af">
            معروض بند واحد من أصل <?= $fmt_int(count($pt_rows)) ?> بنود في هذه المقارنة —
            اختر «كل البنود» من شريط التقرير للصورة الكاملة.
        </div>
        <?php endif; ?>
        <div class="tbl-note">
            عدد الموظفين لا يُجمع: الموظف الواحد قد يأخذ أكثر من بند في نفس الدفعة، فجمع الأعمدة يكرّره.
            المبالغ تُجمع لأن كل مبلغ يخص بنداً واحداً.
        </div>

        <?php if ($has_ref_batch): ?>
        <!-- ⑨-ب تفصيل الموظفين لكل بند (المتغيّرون فقط) -->
        <?php foreach ($pt_visible_rows as $pt_row):
            $pt_k = $pt_key($pt_row);
            $pt_emps = $emps_by_pt[$pt_k] ?? [];
            if (empty($pt_emps)) continue;
            $pt_name = $pt_name_of($pt_row);
            $pt_shown = array_slice($pt_emps, 0, $top_n);
            $pt_hidden = count($pt_emps) - count($pt_shown);
        ?>
            <div style="margin-top:14px">
                <div style="font-weight:700; font-size:10pt; margin-bottom:4px; color:#1e293b">
                    <?= htmlspecialchars($pt_name) ?>
                    <?php $pt_changed = $changed_by_pt[$pt_k] ?? 0; $pt_total = $total_by_pt[$pt_k] ?? 0; ?>
                    <span style="font-weight:400; color:#64748b; font-size:9pt">
                        <?php if ($pt_only_changed): ?>
                            — <?= $fmt_int($pt_changed) ?> تغيّر مبلغه من أصل <?= $fmt_int($pt_total) ?> موظف
                        <?php else: ?>
                            — <?= $fmt_int($pt_total) ?> موظف
                            <?php if ($pt_changed > 0): ?>(<?= $fmt_int($pt_changed) ?> تغيّر مبلغه)<?php else: ?>(بلا تغيير)<?php endif; ?>
                        <?php endif; ?>
                    </span>
                </div>
                <?php if ($has_ref_batch):
                    $pt_st = $stats_by_pt[$pt_k] ?? [];
                    $pt_chips = [
                        ['NEW',     'جديد على البند', '#dcfce7', '#166534'],
                        ['LEFT',    'لم يتكرر',        '#fee2e2', '#991b1b'],
                        ['CHANGED', 'تغيّر مبلغه',      '#fef3c7', '#92400e'],
                        ['SAME',    'بلا تغيير',        '#e2e8f0', '#334155'],
                    ];
                ?>
                <div style="margin-bottom:5px; display:flex; gap:6px; flex-wrap:wrap; align-items:center">
                    <?php foreach ($pt_chips as $chip): $chip_n = (int)($pt_st[$chip[0]] ?? 0); if ($chip_n === 0) continue; ?>
                        <span class="pill" style="background:<?= $chip[2] ?>;color:<?= $chip[3] ?>"><?= $fmt_int($chip_n) ?> <?= $chip[1] ?></span>
                    <?php endforeach; ?>
                    <span style="font-size:8pt; color:#94a3b8">الفئات متنافية — مجموعها <?= $fmt_int($pt_total) ?></span>
                </div>
                <?php endif; ?>
                <table class="rpt">
                    <thead>
                        <tr>
                            <th style="width:55px" class="ctr">الرقم</th>
                            <th>الموظف</th>
                            <th style="width:90px">المقر</th>
                            <th class="ctr" style="width:100px"><?= $lbl_cur_batch ?></th>
                            <th class="ctr prvh" style="width:100px"><?= $lbl_ref_batch ?></th>
                            <th class="ctr" style="width:95px">الفرق</th>
                            <th class="ctr" style="width:65px">النسبة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pt_shown as $emp_row):
                            $emp_cur = (float)($emp_row['CUR_AMOUNT'] ?? 0);
                            $emp_ref = (float)($emp_row['REF_AMOUNT'] ?? 0);
                        ?>
                            <tr>
                                <td class="ctr"><?= (int)($emp_row['EMP_NO'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string)($emp_row['EMP_NAME'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($emp_row['BRANCH_NAME'] ?? '')) ?></td>
                                <td class="num"><?= n_format($emp_cur) ?></td>
                                <td class="num prv"><?= n_format($emp_ref) ?></td>
                                <?= $render_diff_cell($emp_cur, $emp_ref) ?>
                                <?= $render_pct_cell($emp_cur, $emp_ref) ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($pt_hidden > 0): ?>
                    <div class="tbl-note">
                        عُرض أول <?= $fmt_int(count($pt_shown)) ?> موظف مرتّبين بحجم الفرق (الأكبر أولاً، ثم من لم يتغيّر مبلغهم برقم الموظف)
                        — بقي <?= $fmt_int($pt_hidden) ?>. لعرض عدد أكبر غيّر "أعلى N" أعلى التقرير، والتصدير إلى Excel يشمل الجميع.
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php else: ?>
            <div class="note-box">لا توجد دفعة مرجعية — يُعرض ملخص بنود الدفعة الحالية فقط.</div>
        <?php endif; ?>

        <?php endif; ?>
    </div>

    <!-- ════════ سطر الاعتماد والتواقيع ════════ -->
    <div class="section" style="margin-top:22px; page-break-inside:avoid">
        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:24px; text-align:center; font-size:9.5pt">
            <div>
                <div style="font-weight:700; margin-bottom:6px">أعدّه</div>
                <div style="height:30px; color:#0f172a"><?= !empty($user_name) ? htmlspecialchars($user_name) : '' ?></div>
                <div style="border-top:1px solid #475569; padding-top:4px; color:#64748b">الاسم / التوقيع</div>
            </div>
            <div>
                <div style="font-weight:700; margin-bottom:6px">راجعه</div>
                <div style="height:30px">&nbsp;</div>
                <div style="border-top:1px solid #475569; padding-top:4px; color:#64748b">الاسم / التوقيع</div>
            </div>
            <div>
                <div style="font-weight:700; margin-bottom:6px">اعتمده</div>
                <div style="height:30px">&nbsp;</div>
                <div style="border-top:1px solid #475569; padding-top:4px; color:#64748b">الاسم / التوقيع</div>
            </div>
        </div>
    </div>

</div>
</body>
</html>
