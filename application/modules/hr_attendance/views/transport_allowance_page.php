<?php

$MODULE_NAME = 'hr_attendance';
$TB_NAME     = 'Transport_allowance';

$edit_url   = base_url("$MODULE_NAME/$TB_NAME/get");
$can_edit   = HaveAccess($edit_url);
$can_delete = HaveAccess(base_url("$MODULE_NAME/$TB_NAME/delete"));

$state_classes = array(
    'active'   => 'bg-success',
    'upcoming' => 'bg-info',
    'expired'  => 'bg-warning'
);

$count = $offset;

?>

<div class="table-responsive tableRoundedCorner">
    <table class="table mg-b-0 text-md-nowrap table-hover table-bordered roundedTable"
           id="transport_allowance_tb" data-container="container">

        <thead class="table-light text-center">
        <tr>
            <th>#</th>
            <th>من منطقة</th>
            <th>إلى منطقة</th>
            <th>النوع</th>
            <th>البدل اليومي (شيكل)</th>
            <th>تاريخ البدء</th>
            <th>تاريخ الانتهاء</th>
            <th>الحالة</th>
            <th>ملاحظات</th>
            <th>الإجراءات</th>
        </tr>
        </thead>

        <tbody class="text-center">

        <?php if ($page > 1) : ?>
            <tr>
                <td colspan="10" id="page-<?= $page ?>" class="page-sector" data-page="<?= $page ?>"></td>
            </tr>
        <?php endif; ?>

        <?php if (count($page_rows) == 0) : ?>
            <tr>
                <td colspan="10" class="text-muted p-4">لا توجد بيانات</td>
            </tr>
        <?php endif; ?>

        <?php foreach ($page_rows as $row) : ?>
            <?php $row_edit_url = $edit_url . '/' . $row['SER']; ?>
            <tr<?php if ($can_edit) : ?> ondblclick="javascript:get_to_link('<?= $row_edit_url ?>');" style="cursor:pointer;"<?php endif; ?>>
                <td><?= $count++ ?></td>
                <td><?= $row['FROM_REGION_NAME'] ?></td>
                <td><?= $row['TO_REGION_NAME'] ?></td>
                <td>
                    <span class="badge <?= $row['IS_INTERNAL'] ? 'bg-info' : 'bg-primary' ?>"><?= $row['TYPE_NAME'] ?></span>
                </td>
                <td><strong class="text-primary"><?= n_format($row['DAILY_AMOUNT']) ?></strong></td>
                <td><?= $row['START_DATE_STR'] ?></td>
                <td>
                    <?php if ($row['END_DATE_STR'] == '') : ?>
                        <span class="badge bg-primary">مفتوح</span>
                    <?php else : ?>
                        <?= $row['END_DATE_STR'] ?>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge <?= $state_classes[$row['STATE']] ?>"><?= $row['STATE_NAME'] ?></span>
                </td>
                <td><?= htmlspecialchars($row['NOTE'] ?? '') ?></td>
                <td>
                    <?php if ($can_edit) : ?>
                        <a href="javascript:;" class="btn btn-sm btn-outline-primary" title="تعديل"
                           onclick="javascript:get_to_link('<?= $row_edit_url ?>');">
                            <i class="fa fa-pencil"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($can_delete) : ?>
                        <a href="javascript:;" class="btn btn-sm btn-outline-danger" title="حذف"
                           onclick="javascript:delete_row(this, <?= $row['SER'] ?>);">
                            <i class="fa fa-trash"></i>
                        </a>
                    <?php endif; ?>
                </td>
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
