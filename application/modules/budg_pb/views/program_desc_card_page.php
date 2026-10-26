<?php
$MODULE_NAME = 'budg_pb';
$TB_NAME = 'program_desc_card';
$get_url = base_url("$MODULE_NAME/$TB_NAME/get");
?>

<table class="table table-bordered table-hover">
    <thead>
    <tr>
        <th>#</th>
        <th>رقم البرنامج</th>
        <th>اسم البرنامج</th>
        <th>وصف ملخص</th>
        <th>إجراءات</th>
    </tr>
    </thead>
    <tbody>
    <?php $i = $offset ?? 1; if (isset($page_rows)) foreach ($page_rows as $row): ?>
        <tr>
            <td><?php echo $i++; ?></td>
            <td><?php echo $row['PROGRAM_NO']; ?></td>
            <td><?php echo $row['PROGRAM_NAME']; ?></td>
            <td><?php echo $row['PROGRAM_DESC']; ?></td>
            <td>
                <a href="<?php echo $get_url . '/' . $row['PROGRAM_ID']; ?>" class="btn btn-xs btn-info">عرض/تعديل</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php echo $this->pagination->create_links(); ?>
