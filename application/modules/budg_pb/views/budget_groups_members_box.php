<?php if (empty($rows)) : ?>
    <div class="alert alert-info text-center" style="margin:10px 0">
        لا يوجد أعضاء مرتبطون
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
                <tr style="background:#f5f5f5">
                    <th>#</th>
                    <th>نوع العضو</th>
                    <th>رقم وظيفي</th>
                    <th>اسم العضو</th>
                    <th>البريد الإلكتروني</th>
                    <th>رقم الموبايل</th>
                    <th>ملاحظات</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($rows as $row) : ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= $row['GROUP_TYPE_NAME'] ?? '-' ?></td>
                        <td><?= ($row['EMP_NO'] > 0) ? $row['EMP_NO'] : '-' ?></td>
                        <td><?= $row['NAME'] ?></td>
                        <td><?= $row['EMAIL'] ?></td>
                        <td><?= $row['MOBILE_NO'] ?></td>
                        <td><?= $row['NOTES'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
