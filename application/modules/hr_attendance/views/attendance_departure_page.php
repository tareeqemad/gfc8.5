<?php

/**
 * Created by PhpStorm.
 * User: fmamluk
 * Date: 02/06/26
 * Time: 12:00 م
 */

$MODULE_NAME = 'hr_attendance';
$TB_NAME = 'Attendance_departure';

$count = $offset;

?>

<div style="margin-bottom:16px;">
    <b>كشف الحركات</b>
</div>

<div class="table-responsive tableRoundedCorner">
    <table class="table mg-b-0 text-md-nowrap table-hover table-bordered roundedTable"
           id="attendance_departure_tb">

        <thead class="table-light text-center">
        <tr>
            <th>#</th>
            <th>المقر</th>
            <th>المقر الفرعي</th>
            <th>الرقم الوظيفي</th>
            <th>اسم الموظف</th>
            <th>التاريخ</th>
            <th>الوقت</th>
            <th>الحالة</th>
            <th>تاريخ الترحيل</th>
        </tr>
        </thead>

        <tbody class="text-center">

        <?php foreach ($page_rows as $row) : ?>
            <?php

                $status = $row['FUNCTION_KEY'];

                switch ($status) {
                    case 'Entry':
                    case 'F1':
                        $status = 'دخول';
                        break;

                    case 'Leave with form':
                    case 'F2':
                        $status = 'خروج اذن';
                        break;

                    case 'Entry with form':
                    case 'F3':
                        $status = 'عودة اذن';
                        break;

                    case 'Leave':
                    case 'left':
                    case 'F4':
                        $status = 'خروج';
                        break;

                    case 'NONE':
                        $status = 'غير محدد';
                        break;
                }

            ?>
            <tr>
                <td><?= $count++ ?></td>
                <td><?= $row['BRANCH_NAME'] ?></td>
                <td><?= $row['SUB_BRANCH_NAME'] ?></td>
                <td><?= $row['EMPLOYEE_ID'] ?></td>
                <td><?= $row['EMPLOYEE_NAME'] ?></td>
                <td><?= $row['ATTENDANCE_DATE'] ?></td>
                <td><?= $row['ATTENDANCE_TIME'] ?></td>
                <td><?= $status ?></td>
                <td><?= $row['CREATED_AT'] ?></td>
            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>
</div>

<?php echo $this->pagination->create_links(); ?>

<script>
    if (typeof ajax_pager == 'function') {
        ajax_pager();
    }
</script>