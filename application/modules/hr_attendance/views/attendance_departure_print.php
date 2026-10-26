<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تقرير الحضور والانصراف</title>

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

/* ===== Toolbar ===== */
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

/* ===== Report card ===== */
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

/* ===== Info cards ===== */
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

/* ===== Section header ===== */
.section-header{display:flex;align-items:center;gap:8px;margin:4px 0 16px;}
.section-header svg{width:17px;height:17px;color:var(--primary);}
.section-header span{font-size:15px;font-weight:700;color:var(--ink);}

/* ===== Metric cards ===== */
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

/* ===== Main table ===== */
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
.chip{
    display:inline-block;padding:4px 16px;border-radius:8px;
    background:var(--primary-100);color:var(--primary-dark);
    font-weight:800;font-size:13px;
}

.st{display:inline-block;padding:3px 13px;border-radius:999px;font-size:12px;font-weight:700;}
.st-entry{background:var(--green-bg);color:var(--green);}
.st-exit{background:var(--rose-bg);color:var(--rose);}
.st-leave-form{background:var(--amber-bg);color:var(--amber);}
.st-entry-form{background:var(--indigo-bg);color:var(--indigo);}
.st-na{background:var(--slate-100);color:var(--slate-500);}

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

<div class="page-wrap">

<div class="no-print toolbar">

<button class="btn btn-primary" onclick="window.print()">
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="6,9 6,2 18,2 18,9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
طباعة التقرير
</button>

<?php

$export_excel_url = base_url("hr_attendance/Attendance_departure/export_excel_print")
    ."?from_date=".urlencode($from_date)
    ."&to_date=".urlencode($to_date)
    ."&emp_no=".urlencode($emp_no)
    ."&branch_id=".urlencode($branch_id)
    ."&sub_branch_id=".urlencode($sub_branch_id)
    ."&status=".urlencode($status)
    ."&page_act=".urlencode($page_act);

?>

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

<?php

$is_details = !empty($emp_no);

?>

<div class="brand">
<div class="brand-icon">
<svg viewBox="0 0 24 24"><polygon points="13,2 4,14 11,14 9,22 20,9 12,9" fill="currentColor"/></svg>
</div>
<h1>شركة توزيع الكهرباء</h1>
<p>تقرير الحضور والانصراف</p>
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

<div class="info-row">
<span class="info-label">نوع التقرير</span>
<span class="info-value"><span class="badge <?=$is_details?'badge-blue':'badge-indigo'?>"><?=$is_details?'تفصيلي':'ملخص'?></span></span>
</div>

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

<?php if(!$is_details): ?>

<?php

$summary=[];

foreach($rows as $r){

    $emp=$r['EMPLOYEE_ID'];

    if(!isset($summary[$emp])){

        $summary[$emp]=[
            'EMPLOYEE_ID'=>$r['EMPLOYEE_ID'],
            'EMPLOYEE_NAME'=>$r['EMPLOYEE_NAME'],
            'BRANCH_NAME'=>$r['BRANCH_NAME'],
            'DAYS'=>[]
        ];

    }

    $summary[$emp]['DAYS'][$r['ATTENDANCE_DATE']]=1;

}

$total_emp=count($summary);

$total_days=0;

foreach($summary as $s){

    $total_days+=count($s['DAYS']);

}

?>

<div class="section-header">
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="20" x2="6" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="18" y1="20" x2="18" y2="14"/></svg>
<span>ملخص الحضور والانصراف</span>
</div>

<div class="metrics-grid">

<div class="metric-card" style="--mc:#2563eb;--mc-bg:#dbeafe;">
<div class="metric-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.2"/><path d="M3.5 19c0-3 2.5-5 5.5-5s5.5 2 5.5 5"/><circle cx="17" cy="9" r="2.4"/><path d="M15 19c0-2.2 1.6-4 3.8-4.4"/></svg></div>
<div class="metric-label">عدد الموظفين</div>
<div class="metric-value"><?=$total_emp?></div>
</div>

<div class="metric-card" style="--mc:#4338ca;--mc-bg:#e0e7ff;">
<div class="metric-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="8" y1="3" x2="8" y2="7"/><line x1="16" y1="3" x2="16" y2="7"/></svg></div>
<div class="metric-label">إجمالي أيام الدوام</div>
<div class="metric-value"><?=$total_days?></div>
</div>

<div class="metric-card" style="--mc:#b45309;--mc-bg:#fef3c7;">
<div class="metric-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3,17 9,11 13,15 21,6"/><polyline points="15,6 21,6 21,12"/></svg></div>
<div class="metric-label">متوسط الدوام</div>
<div class="metric-value"><?=$total_emp?round($total_days/$total_emp,1):0?></div>
</div>

<div class="metric-card" style="--mc:#0f766e;--mc-bg:#ccfbf1;">
<div class="metric-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><polyline points="12,7 12,12 16,14"/></svg></div>
<div class="metric-label">الفترة</div>
<div class="metric-value" style="font-size:14px;"><?=$from_date?><br><?=$to_date?></div>
</div>

</div>

<div class="table-wrap">
<table class="main-table">

<thead>

<tr>

<th>#</th>

<th>الرقم الوظيفي</th>

<th>اسم الموظف</th>

<th>المقر</th>

<th>عدد أيام الدوام</th>

</tr>

</thead>

<tbody>

<?php $i=1; foreach($summary as $row): ?>

<tr>

<td><span class="row-index"><?=$i++?></span></td>

<td><?=$row['EMPLOYEE_ID']?></td>

<td><?=$row['EMPLOYEE_NAME']?></td>

<td><?=$row['BRANCH_NAME']?></td>

<td><span class="chip"><?=count($row['DAYS'])?></span></td>

</tr>

<?php endforeach; ?>

</tbody>

<tfoot>

<tr>

<td colspan="5">

عدد الموظفين : <?=$total_emp?>

&nbsp;&nbsp;|

&nbsp;&nbsp;

إجمالي أيام الدوام : <?=$total_days?>

</td>

</tr>

</tfoot>

</table>
</div>

<?php else: ?>

<?php

$entry=0;
$leave=0;
$form_leave=0;
$form_entry=0;

foreach($rows as $r){

    switch($r['FUNCTION_KEY']){

        case 'Entry':
        case 'F1':
            $entry++;
            break;

        case 'Leave':
        case 'left':
        case 'F4':
            $leave++;
            break;

        case 'Leave with form':
        case 'F2':
            $form_leave++;
            break;

        case 'Entry with form':
        case 'F3':
            $form_entry++;
            break;

    }

}

?>

<div class="section-header">
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="20" x2="6" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="18" y1="20" x2="18" y2="14"/></svg>
<span>ملخص الحركات</span>
</div>

<div class="metrics-grid">

<div class="metric-card" style="--mc:#2563eb;--mc-bg:#dbeafe;">
<div class="metric-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="2,12 7,12 9,18 15,6 17,12 22,12"/></svg></div>
<div class="metric-label">عدد الحركات</div>
<div class="metric-value"><?=count($rows)?></div>
</div>

<div class="metric-card" style="--mc:#15803d;--mc-bg:#dcfce7;">
<div class="metric-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="9,8 13,12 9,16"/><line x1="13" y1="12" x2="2" y2="12"/></svg></div>
<div class="metric-label">الدخول</div>
<div class="metric-value"><?=$entry?></div>
</div>

<div class="metric-card" style="--mc:#be123c;--mc-bg:#ffe4e6;">
<div class="metric-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h4"/><polyline points="15,16 19,12 15,8"/><line x1="19" y1="12" x2="8" y2="12"/></svg></div>
<div class="metric-label">الخروج</div>
<div class="metric-value"><?=$leave?></div>
</div>

<div class="metric-card" style="--mc:#b45309;--mc-bg:#fef3c7;">
<div class="metric-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"/><path d="M15 2v5h5"/><polyline points="8,14 10.5,16.5 16,11"/></svg></div>
<div class="metric-label">الأذونات</div>
<div class="metric-value"><?=$form_leave+$form_entry?></div>
</div>

</div>

<div class="table-wrap">
<table class="main-table">

<thead>

<tr>

<th>#</th>

<th>التاريخ</th>

<th>الوقت</th>

<th>الحالة</th>

</tr>

</thead>

<tbody>

<?php

$i=1;

foreach($rows as $row):

$status=$row['FUNCTION_KEY'];
$status_class='st-na';

switch($status){

case 'Entry':
case 'F1':
$status='دخول';
$status_class='st-entry';
break;

case 'Leave':
case 'left':
case 'F4':
$status='خروج';
$status_class='st-exit';
break;

case 'Leave with form':
case 'F2':
$status='خروج بإذن';
$status_class='st-leave-form';
break;

case 'Entry with form':
case 'F3':
$status='عودة إذن';
$status_class='st-entry-form';
break;

default:
$status='غير محدد';
$status_class='st-na';

}

?>

<tr>

<td><span class="row-index"><?=$i++?></span></td>

<td><?=$row['ATTENDANCE_DATE']?></td>

<td><?=$row['ATTENDANCE_TIME']?></td>

<td><span class="st <?=$status_class?>"><?=$status?></span></td>

</tr>

<?php endforeach; ?>

</tbody>

<tfoot>

<tr>

<td colspan="4">

عدد الحركات : <?=count($rows)?>

</td>

</tr>

</tfoot>

</table>
</div>

<?php endif; ?>
</div>

</div>

<script>

window.onload=function(){

    setTimeout(function(){

        window.print();

    },500);

};

window.onafterprint=function(){

    // إذا أردت إغلاق الصفحة بعد الطباعة أزل التعليق عن السطر التالي
    // window.close();

};

</script>

</body>
</html>