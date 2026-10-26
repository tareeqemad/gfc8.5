<style>
    .case_10 {
        background-color: #FFC9BB;
    }

    .large.tooltip-inner {
        max-width: 350px;
        width: 350px;
    }
</style>
<?php
/**
 * Created by PhpStorm.
 * User: lanakh
 * Date: 06/08/18
 * Time: 11:44 ص
 */

$MODULE_NAME = 'indicator';
$TB_NAME = 'indicator';
$edit_url = base_url("$MODULE_NAME/$TB_NAME/get");

$count = 1;
$page = 1;
function class_name($mode)
{

    if ($mode == 0) {

        return '#FFC9BB';
    } else
        return '#DFFFDF';
}
?>
<table class="table" id="page_tb" data-container="container">
    <thead>
        <tr>
            <th class="hidden">#</th>

            <th>م</th>

            <th>#</th>
            <th>السنة</th>
            <th>المحور</th>
            <th>الغاية</th>
            <th>الهدف الاستراتيجي</th>

            <th>اسم المؤشر</th>
            <th>الفارق المؤثر</th>
            <th>الصيغة</th>
            <th>وحدة القياس</th>
            <th>فترة القياس</th>

            <th>طريقة قياس المستهدف</th>


            <th>حالة الاعتماد</th>
            <th>الحالة</th>
            <?php if (HaveAccess(base_url("indicator/indecator_info/get_indecator_info/"))) { ?> <th>عرض/تحرير</th> <?php } ?>

            <th>ارجاع مؤشر</th>




        </tr>
    </thead>

    <tbody>
        <?php foreach ($page_rows as $row) :

        ?>

            <tr>
                <td><?= $count ?></td>
                <td><input type="checkbox" class="checkboxes" value="<?= $row['INDECATOR_SER'] ?>" /></td>
                <td><?= @$row['YEAR'] ?></td>
                <td><?= @$row['SECTOR_NAME'] ?></td>
                <td><?= @$row['CLASS_NAME'] ?></td>
                <td><?= @$row['SECOND_CLASS_NAME'] ?></td>

                <td>
                    <?php
                    if (@$row['NOTE'] == '')
                        @$row['NOTE'] = "لايوجد ملاحظات مدخلة";

                    ?>
                    <div class="row">
                        <div class="col-md-11"><?= @$row['INDECATOR_NAME']; ?></div>
                        <div class="col-md-1">
                            <a href="#" data-toggle="tooltip" data-placement="top" title="<?= @$row['NOTE'] ?>"><i class="icon icon-question-circle"></i></a>
                        </div>
                    </div>


                </td>
                <td>
                    <?= @$row['EFFECT_VALUE'] . '%'; ?>
                </td>

                <td><?= @$row['SCALE_NAME']; ?></td>
                <td><?= @$row['UNIT_NAME']; ?></td>
                <td><?= @$row['PERIOD_NAME']; ?></td>

                <td><?= @$row['ENTERY_TARGET_WAY_NAME']; ?></td>



                <td>
                    <?= @$row['ADOPT_NAME']; ?>
                </td>
                <td>
                    <?= @$row['INDECATOR_FLAGE_NAME']; ?>
                </td>




                <?php if ((HaveAccess(base_url("indicator/indecator_info/get_indecator_info/")))) { ?>
                    <td> <a href="<?= base_url("indicator/indecator_info/get_indecator_info/{$row['INDECATOR_SER']}") ?>"><i class='glyphicon glyphicon-share'></i></a> </td>
                <?php } ?>

                <td></td>




















                <?php $count++ ?>
            </tr>
        <?php endforeach; ?>

    </tbody>
</table>
</div>
<script type="text/javascript">
    $(function() {
        // $('#element').tooltip('show')
        $('[data-toggle="tooltip"]').tooltip({
            template: '<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner large"></div></div>'
        });

        $('#page_tb').dataTable({
            "lengthMenu": [
                [10, 20, 30, 40, 50, 100, -1],
                [10, 20, 30, 40, 50, 100, "الكل"]
            ],
            "sPaginationType": "full_numbers"
        });
    });
    if (typeof initFunctions == 'function') {
        initFunctions();
    }
    if (typeof ajax_pager == 'function') {
        ajax_pager();
    }
</script>