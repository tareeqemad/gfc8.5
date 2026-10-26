<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تقرير تحليل البصمات</title>

<link rel="stylesheet" href="<?=base_url()?>assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">

<style>

:root{
    --primary:#2563eb;
    --primary-dark:#1d4ed8;
    --primary-50:#eff6ff;
    --primary-100:#dbeafe;
    --ink:#0f172a;
    --slate-600:#475569;
    --slate-500:#64748b;
    --slate-400:#94a3b8;
    --slate-200:#e2e8f0;
    --slate-100:#f1f5f9;
    --slate-50:#f8fafc;
    --green:#15803d; --green-bg:#dcfce7;
    --rose:#be123c;  --rose-bg:#ffe4e6;
    --amber:#b45309; --amber-bg:#fef3c7;
    --indigo:#4338ca;--indigo-bg:#e0e7ff;
    --teal:#0f766e;  --teal-bg:#ccfbf1;
}

*{box-sizing:border-box;}

body{
    font-family:'Tajawal', Tahoma, sans-serif;
    direction:rtl;
    background:linear-gradient(180deg,#eef2f7 0%,#e6ebf2 100%);
    margin:0;
    padding:28px 16px 60px;
    color:var(--ink);
    -webkit-print-color-adjust:exact;
    print-color-adjust:exact;
}

.page-wrap{max-width:980px;margin:0 auto;}

.toolbar{
    display:flex;
    justify-content:center;
    gap:12px;
    background:#fff;
    border-radius:14px;
    padding:14px 18px;
    margin-bottom:22px;
    box-shadow:0 1px 2px rgba(15,23,42,.04), 0 10px 24px -12px rgba(15,23,42,.18);
}

.btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 24px;
    border-radius:10px;
    border:none;
    font-family:inherit;
    font-size:14.5px;
    font-weight:700;
    cursor:pointer;
    transition:.15s ease;
}
.btn svg{width:16px;height:16px;}
.btn-primary{background:var(--primary);color:#fff;box-shadow:0 6px 14px -6px rgba(37,99,235,.55);}
.btn-primary:hover{background:var(--primary-dark);}
.btn-secondary{background:#fff;color:var(--slate-600);border:1.5px solid var(--slate-200);}
.btn-secondary:hover{background:var(--slate-50);}
.btn-success{background:var(--green);color:#fff;box-shadow:0 6px 14px -6px rgba(21,128,61,.55);text-decoration:none;}
.btn-success:hover{background:#166534;}

.report{
    background:#fff;
    border-radius:20px;
    padding:38px 40px 46px;
    box-shadow:0 1px 2px rgba(15,23,42,.04), 0 24px 48px -24px rgba(15,23,42,.2);
}

.brand{
    text-align:center;
    padding-bottom:22px;
    border-bottom:1px solid var(--slate-100);
    margin-bottom:28px;
}
.brand-icon{
    width:62px;height:62px;border-radius:50%;
    background:linear-gradient(135deg,var(--primary),var(--primary-dark));
    display:flex;align-items:center;justify-content:center;
    margin:0 auto 14px;
    box-shadow:0 10px 18px -6px rgba(37,99,235,.5);
}
.brand-icon svg{width:28px;height:28px;color:#fff;}
.brand h1{font-size:21px;font-weight:800;margin:0 0 5px;color:var(--ink);}
.brand p{font-size:13.5px;color:var(--slate-500);margin:0;font-weight:500;}

.info-grid{display:flex;gap:18px;margin-bottom:30px;}
.info-card{flex:1 1 0;min-width:0;border:1px solid var(--slate-200);border-radius:14px;overflow:hidden;}
.info-card-header{
    display:flex;align-items:center;gap:8px;
    background:var(--primary-50);
    padding:12px 16px;
    border-bottom:1px solid var(--slate-200);
}
.info-card-header svg{width:17px;height:17px;color:var(--primary);}
.info-card-header span{font-size:14.5px;font-weight:700;color:var(--primary-dark);}
.info-body{padding:4px 16px 2px;}
.info-row{
    display:flex;justify-content:space-between;align-items:center;
    padding:10px 0;border-bottom:1px solid var(--slate-100);gap:12px;
}
.info-row:last-child{border-bottom:none;}
.info-label{font-size:13px;color:var(--slate-500);font-weight:500;white-space:nowrap;}
.info-value{font-size:14px;color:var(--ink);font-weight:700;}

.badge{display:inline-block;padding:3px 13px;border-radius:999px;font-size:12.5px;font-weight:700;white-space:nowrap;}
.badge-blue{background:var(--primary-100);color:var(--primary-dark);}
.badge-indigo{background:var(--indigo-bg);color:var(--indigo);}
.badge-amber{background:var(--amber-bg);color:var(--amber);}

.section-header{display:flex;align-items:center;gap:8px;margin:4px 0 16px;}
.section-header svg{width:17px;height:17px;color:var(--primary);}
.section-header span{font-size:15px;font-weight:700;color:var(--ink);}

.metrics-grid{display:flex;flex-wrap:wrap;gap:14px;margin-bottom:32px;}
.metric-card{
    flex:1 1 0;min-width:130px;
    border:1px solid var(--slate-200);
    border-top:3px solid var(--mc);
    border-radius:14px;
    padding:18px 10px 16px;
    text-align:center;
    background:var(--slate-50);
}
.metric-icon{
    width:38px;height:38px;border-radius:50%;
    display:flex;align-items:center;justify-content:center;
    margin:0 auto 10px;background:var(--mc-bg);
}
.metric-icon svg{width:18px;height:18px;color:var(--mc);}
.metric-label{font-size:12.5px;color:var(--slate-500);font-weight:600;margin-bottom:6px;}
.metric-value{font-size:25px;font-weight:800;color:var(--ink);line-height:1.1;}
.metric-unit{font-size:11px;color:var(--slate-400);margin-top:3px;font-weight:500;}

.table-wrap{border:1px solid var(--slate-200);border-radius:14px;overflow:hidden;}
table.main-table{width:100%;border-collapse:collapse;font-size:13.5px;}
.main-table thead th{
    background:linear-gradient(135deg,var(--primary),var(--primary-dark));
    color:#fff;padding:12px 10px;font-size:13px;font-weight:700;border:none;text-align:center;
}
.main-table tbody td{padding:11px 10px;border-bottom:1px solid var(--slate-100);color:var(--ink);text-align:center;}
.main-table tbody tr:last-child td{border-bottom:none;}
.main-table tbody tr:nth-child(even){background:var(--slate-50);}
.main-table tfoot td{
    background:var(--slate-50);font-weight:700;padding:14px 10px;
    text-align:center;color:var(--ink);border-top:2px solid var(--slate-200);
}
.row-index{
    display:inline-flex;align-items:center;justify-content:center;
    width:25px;height:25px;border-radius:50%;
    background:var(--primary-100);color:var(--primary-dark);
    font-weight:700;font-size:12px;
}

.st{display:inline-block;padding:3px 13px;border-radius:999px;font-size:12px;font-weight:700;}
.st-delay{background:var(--rose-bg);color:var(--rose);}
.st-ok{background:var(--slate-100);color:var(--slate-500);}

.no-print{}

@media (max-width:680px){
    .info-grid{flex-direction:column;}
    .metric-card{min-width:42%;}
    .report{padding:26px 18px 32px;}
}

@media print{

    *{-webkit-print-color-adjust:exact;print-color-adjust:exact;}

    body{
        margin:0;
        padding:0;
        background:#fff;
    }

    .report{
        border-radius:0;
        box-shadow:none;
        padding:10px 6px;
    }

    .no-print{
        display:none;
    }

}

</style>

</head>

<body>

<?php

// عناوين ووصف كل نوع من تقارير التحليل الاربعة
$titles = [
    'hours'    => 'تقرير ساعات الدوام والتأخير',
    'no_entry' => 'الموظفين بدون بصمة حضور',
    'no_leave' => 'الموظفين بدون بصمة انصراف',
    'late'     => 'الموظفين المتأخرين صباحاً'
];

// يعرض وقت اليوم HH:MM من عدد الدقائق، او شرطة لو غير محسوب
$fmt_time = function($minutes){
    if ($minutes === null) {
        return '-';
    }
    return sprintf('%02d:%02d', floor($minutes / 60), $minutes % 60);
};

// يعرض مدة (ساعات دوام / تأخير) بصيغة ساعة:دقيقة
$fmt_duration = function($minutes){
    if ($minutes === null) {
        return '-';
    }
    return sprintf('%d:%02d', floor($minutes / 60), $minutes % 60);
};

$export_excel_url = base_url("hr_attendance/Attendance_departure/export_excel_analysis")
    ."?mode=".urlencode($mode)
    ."&from_date=".urlencode($from_date)
    ."&to_date=".urlencode($to_date)
    ."&emp_no=".urlencode($emp_no)
    ."&branch_id=".urlencode($branch_id)
    ."&sub_branch_id=".urlencode($sub_branch_id)
    ."&work_start=".urlencode($work_start)
    ."&work_end=".urlencode($work_end)
    ."&grace_minutes=".urlencode($grace_minutes)
    ."&page_act=".urlencode($page_act);

?>

<div class="page-wrap">

<div class="no-print toolbar">

<button class="btn btn-primary" onclick="window.print()">
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="6,9 6,2 18,2 18,9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
طباعة التقرير
</button>

<a class="btn btn-success" href="<?=$export_excel_url?>">
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2z"/><path d="M9.5 12.5 12 17l2.5-4.5"/><path d="M9.5 17 12 12.5 14.5 17"/></svg>
تصدير Excel
</a>

<button class="btn btn-secondary" onclick="window.close()">
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="5" y1="5" x2="19" y2="19"/><line x1="19" y1="5" x2="5" y2="19"/></svg>
إغلاق
</button>

</div>

<div class="report">

<div class="brand">
<div class="brand-icon">
<svg viewBox="0 0 24 24"><polygon points="13,2 4,14 11,14 9,22 20,9 12,9" fill="currentColor"/></svg>
</div>
<h1>شركة توزيع الكهرباء</h1>
<p><?=$titles[$mode]?></p>
</div>

<div class="info-grid">

<div class="info-card">
<div class="info-card-header">
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"/><path d="M15 2v5h5"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/></svg>
<span>بيانات التقرير</span>
</div>
<div class="info-body">

<div class="info-row">
<span class="info-label">الفترة</span>
<span class="info-value"><?=$from_date?> <span style="color:var(--slate-400);font-weight:400;">إلى</span> <span class="badge badge-amber"><?=$to_date?></span></span>
</div>

<div class="info-row">
<span class="info-label">تاريخ الطباعة</span>
<span class="info-value"><?=$print_date?></span>
</div>

<?php if ($mode == 'hours' || $mode == 'late') : ?>
<div class="info-row">
<span class="info-label">الدوام الرسمي</span>
<span class="info-value"><span class="badge badge-blue"><?=$work_start?><?=($mode=='hours' and $work_end!='')?' - '.$work_end:''?></span></span>
</div>
<?php endif; ?>

</div>
</div>

<div class="info-card">
<div class="info-card-header">
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.2"/><path d="M3.5 19c0-3 2.5-5 5.5-5s5.5 2 5.5 5"/><circle cx="17" cy="9" r="2.4"/><path d="M15 19c0-2.2 1.6-4 3.8-4.4"/></svg>
<span>بيانات البحث</span>
</div>
<div class="info-body">

<div class="info-row">
<span class="info-label">المقر</span>
<span class="info-value"><?=($branch_name!='')?$branch_name:'الكل'?></span>
</div>

<div class="info-row">
<span class="info-label">المقر الفرعي</span>
<span class="info-value"><?=($sub_branch_name!='')?$sub_branch_name:'الكل'?></span>
</div>

<div class="info-row">
<span class="info-label">الموظف</span>
<span class="info-value"><?=($emp_name!='')?$emp_name:'جميع الموظفين'?></span>
</div>

</div>
</div>

</div>

<?php

$total_delay_minutes = 0;
$total_work_minutes = 0;
$work_minutes_count = 0;

foreach ($rows as $r) {
    if (isset($r['TOTAL_DELAY_MINUTES'])) {
        $total_delay_minutes += $r['TOTAL_DELAY_MINUTES'];
    } elseif (isset($r['DELAY_MINUTES'])) {
        $total_delay_minutes += $r['DELAY_MINUTES'];
    }
    if (isset($r['TOTAL_WORK_MINUTES'])) {
        $total_work_minutes += $r['TOTAL_WORK_MINUTES'];
        $work_minutes_count++;
    } elseif (isset($r['WORK_MINUTES'])) {
        $total_work_minutes += $r['WORK_MINUTES'];
        $work_minutes_count++;
    }
}

?>

<div class="section-header">
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="20" x2="6" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="18" y1="20" x2="18" y2="14"/></svg>
<span><?=$titles[$mode]?></span>
</div>

<div class="metrics-grid">

<div class="metric-card" style="--mc:#2563eb;--mc-bg:#dbeafe;">
<div class="metric-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="20" x2="6" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="18" y1="20" x2="18" y2="14"/></svg></div>
<div class="metric-label"><?=$is_summary?'عدد الموظفين':'عدد السجلات'?></div>
<div class="metric-value"><?=count($rows)?></div>
</div>

<?php if ($mode == 'hours') : ?>
<div class="metric-card" style="--mc:#b45309;--mc-bg:#fef3c7;">
<div class="metric-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><polyline points="12,7 12,12 16,14"/></svg></div>
<div class="metric-label">إجمالي الوقت بعد الدوام الرسمي</div>
<div class="metric-value" style="font-size:18px;"><?=$fmt_duration($total_delay_minutes)?></div>
</div>
<div class="metric-card" style="--mc:#0f766e;--mc-bg:#ccfbf1;">
<div class="metric-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
<div class="metric-label"><?=$is_summary?'إجمالي ساعات الدوام':'متوسط ساعات الدوام'?></div>
<div class="metric-value" style="font-size:18px;"><?=$is_summary?$fmt_duration($total_work_minutes):$fmt_duration($work_minutes_count?round($total_work_minutes/$work_minutes_count):null)?></div>
</div>
<?php elseif ($mode == 'late') : ?>
<div class="metric-card" style="--mc:#be123c;--mc-bg:#ffe4e6;">
<div class="metric-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><polyline points="12,7 12,12 16,14"/></svg></div>
<div class="metric-label"><?=$is_summary?'إجمالي دقائق التأخير':'متوسط دقائق التأخير'?></div>
<div class="metric-value" style="font-size:18px;"><?=$is_summary?$fmt_duration($total_delay_minutes):$fmt_duration(count($rows)?round($total_delay_minutes/count($rows)):null)?></div>
</div>
<?php endif; ?>

</div>

<div class="table-wrap">
<table class="main-table">

<thead>
<tr>
<th>#</th>
<th>المقر</th>
<th>المقر الفرعي</th>
<th>الرقم الوظيفي</th>
<th>اسم الموظف</th>

<?php if ($is_summary) : ?>

    <?php if ($mode == 'hours') : ?>
    <th>عدد أيام التأخير</th>
    <th>إجمالي ساعات الدوام</th>
    <th>إجمالي الوقت بعد الدوام الرسمي</th>
    <?php elseif ($mode == 'no_entry') : ?>
    <th>عدد الأيام بدون بصمة حضور</th>
    <?php elseif ($mode == 'no_leave') : ?>
    <th>عدد الأيام بدون بصمة انصراف</th>
    <?php elseif ($mode == 'late') : ?>
    <th>عدد أيام التأخير</th>
    <th>إجمالي دقائق التأخير</th>
    <?php endif; ?>

<?php else : ?>

    <th>التاريخ</th>

    <?php if ($mode == 'hours') : ?>
    <th>وقت الحضور</th>
    <th>وقت الانصراف</th>
    <th>ساعات الدوام</th>
    <th>الوقت بعد نهاية الدوام الرسمي</th>
    <?php elseif ($mode == 'no_entry') : ?>
    <th>وقت الانصراف المسجل</th>
    <?php elseif ($mode == 'no_leave') : ?>
    <th>وقت الحضور المسجل</th>
    <?php elseif ($mode == 'late') : ?>
    <th>وقت الحضور</th>
    <th>مدة التأخير</th>
    <?php endif; ?>

<?php endif; ?>
</tr>
</thead>

<tbody>

<?php $i = 1; foreach ($rows as $row) : ?>
<tr>
<td><span class="row-index"><?=$i++?></span></td>
<td><?=$row['BRANCH_NAME']?></td>
<td><?=$row['SUB_BRANCH_NAME']?></td>
<td><?=$row['EMPLOYEE_ID']?></td>
<td><?=$row['EMPLOYEE_NAME']?></td>

<?php if ($is_summary) : ?>

    <?php if ($mode == 'hours') : ?>
    <td><?=$row['DAYS_COUNT']?></td>
    <td><?=$fmt_duration($row['TOTAL_WORK_MINUTES'])?></td>
    <td><span class="st st-delay"><?=$fmt_duration($row['TOTAL_DELAY_MINUTES'])?></span></td>
    <?php elseif ($mode == 'no_entry' || $mode == 'no_leave') : ?>
    <td><?=$row['DAYS_COUNT']?></td>
    <?php elseif ($mode == 'late') : ?>
    <td><?=$row['DAYS_COUNT']?></td>
    <td><span class="st st-delay"><?=$fmt_duration($row['TOTAL_DELAY_MINUTES'])?></span></td>
    <?php endif; ?>

<?php else : ?>

    <td><?=$row['ATTENDANCE_DATE']?></td>

    <?php if ($mode == 'hours') : ?>
    <td><?=$fmt_time($row['FIRST_ENTRY'])?></td>
    <td><?=$fmt_time($row['LAST_LEAVE'])?></td>
    <td><?=$fmt_duration($row['WORK_MINUTES'])?></td>
    <td><span class="st st-delay"><?=$fmt_duration($row['DELAY_MINUTES'])?></span></td>
    <?php elseif ($mode == 'no_entry') : ?>
    <td><?=$fmt_time($row['LAST_LEAVE'])?></td>
    <?php elseif ($mode == 'no_leave') : ?>
    <td><?=$fmt_time($row['FIRST_ENTRY'])?></td>
    <?php elseif ($mode == 'late') : ?>
    <td><?=$fmt_time($row['FIRST_ENTRY'])?></td>
    <td><span class="st st-delay"><?=$fmt_duration($row['DELAY_MINUTES'])?></span></td>
    <?php endif; ?>

<?php endif; ?>

</tr>
<?php endforeach; ?>

</tbody>

<tfoot>
<tr>
<td colspan="10">عدد السجلات : <?=count($rows)?></td>
</tr>
</tfoot>

</table>
</div>

</div>

</div>

<script>

window.onload=function(){

    setTimeout(function(){

        window.print();

    },500);

};

</script>

</body>
</html>
