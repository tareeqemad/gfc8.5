<?php

/**
 * Created by PhpStorm.
 * User: fmamluk
 * Date: 03/06/26
 * Time: 13:00 م
 */

// عناوين التقارير الاربعة حسب mode القادم من الكنترولر
$titles = [
    'hours'    => 'تقرير ساعات الدوام والتأخير',
    'no_entry' => 'الموظفين بدون بصمة حضور',
    'no_leave' => 'الموظفين بدون بصمة انصراف',
    'late'     => 'الموظفين المتأخرين صباحاً'
];

// يعرض وقت اليوم HH:MM من عدد الدقائق، أو شرطة لو غير محسوب
$fmt_time = function($minutes){
    if ($minutes === null) {
        return '-';
    }
    return sprintf('%02d:%02d', floor($minutes / 60), $minutes % 60);
};

// يعرض مدة (ساعات دوام/تأخير) بصيغة ساعة:دقيقة من عدد الدقائق
$fmt_duration = function($minutes){
    if ($minutes === null) {
        return '-';
    }
    return sprintf('%d:%02d', floor($minutes / 60), $minutes % 60);
};

$count = 1;

?>

<div style="margin-bottom:16px;">
    <b><?= $titles[$mode] ?></b>
    &nbsp;-&nbsp;
    عدد السجلات: <?= count($rows) ?>
</div>

<div class="table-responsive tableRoundedCorner">
    <table class="table mg-b-0 text-md-nowrap table-hover table-bordered roundedTable"
           id="attendance_analysis_tb">

        <thead class="table-light text-center">
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

        <tbody class="text-center">

        <?php foreach ($rows as $row) : ?>
            <tr>
                <td><?= $count++ ?></td>
                <td><?= $row['BRANCH_NAME'] ?></td>
                <td><?= $row['SUB_BRANCH_NAME'] ?></td>
                <td><?= $row['EMPLOYEE_ID'] ?></td>
                <td><?= $row['EMPLOYEE_NAME'] ?></td>

                <?php if ($is_summary) : ?>

                    <?php if ($mode == 'hours') : ?>
                        <td><?= $row['DAYS_COUNT'] ?></td>
                        <td><?= $fmt_duration($row['TOTAL_WORK_MINUTES']) ?></td>
                        <td style="color:#BF0000;font-weight:bold"><?= $fmt_duration($row['TOTAL_DELAY_MINUTES']) ?></td>
                    <?php elseif ($mode == 'no_entry' || $mode == 'no_leave') : ?>
                        <td><?= $row['DAYS_COUNT'] ?></td>
                    <?php elseif ($mode == 'late') : ?>
                        <td><?= $row['DAYS_COUNT'] ?></td>
                        <td style="color:#BF0000;font-weight:bold"><?= $fmt_duration($row['TOTAL_DELAY_MINUTES']) ?></td>
                    <?php endif; ?>

                <?php else : ?>

                    <td><?= $row['ATTENDANCE_DATE'] ?></td>

                    <?php if ($mode == 'hours') : ?>
                        <td><?= $fmt_time($row['FIRST_ENTRY']) ?></td>
                        <td><?= $fmt_time($row['LAST_LEAVE']) ?></td>
                        <td><?= $fmt_duration($row['WORK_MINUTES']) ?></td>
                        <td style="color:#BF0000;font-weight:bold"><?= $fmt_duration($row['DELAY_MINUTES']) ?></td>
                    <?php elseif ($mode == 'no_entry') : ?>
                        <td><?= $fmt_time($row['LAST_LEAVE']) ?></td>
                    <?php elseif ($mode == 'no_leave') : ?>
                        <td><?= $fmt_time($row['FIRST_ENTRY']) ?></td>
                    <?php elseif ($mode == 'late') : ?>
                        <td><?= $fmt_time($row['FIRST_ENTRY']) ?></td>
                        <td style="color:#BF0000;font-weight:bold"><?= $fmt_duration($row['DELAY_MINUTES']) ?></td>
                    <?php endif; ?>

                <?php endif; ?>
            </tr>
        <?php endforeach; ?>

        </tbody>

    </table>
</div>
