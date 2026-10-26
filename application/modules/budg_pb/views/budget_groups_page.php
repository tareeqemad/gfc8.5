
<div class="tbl_container">
    <table class="table table-striped" id="page_tb" data-container="container">
        <thead>
        <tr>
            <th>#</th>
            <th>نوع الربط</th>
            <th>اسم البرنامج / النشاط</th>
            <th>رقم وظيفي</th>
            <th>اسم العضو</th>
            <th>نوع العضو</th>
            <th>البريد الإلكتروني</th>
            <th>رقم الموبايل</th>
            <th>ملاحظات</th>
            <th>إجراءات</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($page_rows)) : $i = 1; ?>
            <?php foreach ($page_rows as $row) : ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= $row['PROGRAM_TYPE_NAME'] ?? '-' ?></td>
                    <td><?= $row['PROGRAM_NAME'] ?? $row['PROGRAM_NO'] ?></td>
                    <td><?= ($row['EMP_NO'] > 0) ? $row['EMP_NO'] : '-' ?></td>
                    <td><?= $row['NAME'] ?></td>
                    <td><?= $row['GROUP_TYPE_NAME'] ?? '-' ?></td>
                    <td><?= $row['EMAIL'] ?></td>
                    <td><?= $row['MOBILE_NO'] ?></td>
                    <td><?= $row['NOTES'] ?></td>
                    <td>
                        <a href="javascript:;" onclick="edit_group(<?= $row['SER'] ?>)" title="تعديل">
                            <i class="glyphicon glyphicon-edit"></i>
                        </a>
                        &nbsp;
                        <a href="javascript:;" onclick="delete_group(<?= $row['SER'] ?>)" title="حذف" style="color:red">
                            <i class="glyphicon glyphicon-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="10" class="text-center">لا توجد بيانات</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
