<?php
$MODULE_NAME = 'payflow';
$TB_NAME = 'Salarycalculation';
$count = $offset;
$report_url = base_url("JsperReport/showreport?sys=hr/salaries");
?>
<style>
    /* تدرج لوني خفيف في خلفية رأس الجدول */
    .table thead th {
        background: linear-gradient(to right, #fafafa, #f2f2f2);
        color: #333;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
    }

    /* عند تمرير الفأرة على الصف */
    .table tbody tr:hover {
        background-color: #f9fafb;
    }

    /* تنسيق لخلايا صافي الراتب */
    .net-salary {
        font-weight: bold;
        color: #198754; /* درجة من الأخضر */
    }

    /* مثال لتنسيق أزرار الطباعة */
    .btn-print {
        background-color: #4e73df;
        border: none;
        color: #fff;
    }
    .btn-print:hover {
        background-color: #224abe;
        color: #fff;
    }
</style>
<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered text-center" id="page_tb">
        <thead>
        <tr>
            <th>#</th>
            <th><i class="fa fa-user"></i> رقم الموظف</th>
            <th><i class="fa fa-id-card"></i> اسم الموظف</th>
            <th><i class="fa fa-calendar"></i> الشهر</th>
            <th><i class="fa fa-briefcase"></i> نوع التعيين</th>
            <th><i class="fa fa-building"></i> المقر</th>
            <th><i class="fa fa-sitemap"></i> الإدارة</th>
            <th><i class="fa fa-university"></i> البنك</th>
            <th><i class="fa fa-credit-card"></i> الحساب</th>
            <th><i class="fa fa-user-tag"></i> المسمى</th>
            <th><i class="fa fa-money-bill-wave"></i> صافي الراتب</th>
            <th><i class="fa fa-print"></i> طباعة</th>
        </tr>
        </thead>
        <tbody>
        <?php if($page > 1): ?>
            <tr>
                <td colspan="12" id="page-<?= $page ?>" class="page-sector" data-page="<?= $page ?>"></td>
            </tr>
        <?php endif; ?>
        <?php foreach($page_rows as $row) :?>
                <tr ondblclick="show_row_details('<?=$row['EMP_NO']?>', '<?=$row['MONTH']?>');">
                    <td><?=$count?></td>
                    <td><?= $row['EMP_NO'] ?></td>
                    <td><?= $row['EMP_NO_NAME'] ?></td>
                    <td><?= $row['MONTH'] ?></td>
                    <td><?= $row['EMP_TYPE_NAME'] ?></td>
                    <td><?= $row['BRANCH_NAME'] ?></td>
                    <td><?= $row['DEPARTMENT_NAME'] ?></td>
                    <td><?= $row['BANK_NAME'] ?></td>
                    <td><?= $row['ACCOUNT'] ?></td>
                    <td><?= $row['W_NO_NAME'] ?></td>
                    <td class="net-salary"><?= number_format($row['NET_SALARY'], 2) ?> 💵</td>
                    <td>
                        <button class="btn btn-sm btn-print" onclick="print_report(<?= $row['EMP_NO'] ?>, <?= $row['MONTH'] ?>);">
                            <i class="fa fa-print"></i>
                        </button>
                    </td>
                    <?php $count++; ?>
                </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php echo $this->pagination->create_links(); ?>
<script>
    if (typeof initFunctions == 'function') {
        initFunctions();
    }
    if (typeof ajax_pager == 'function') {
        ajax_pager();
    }
</script>



