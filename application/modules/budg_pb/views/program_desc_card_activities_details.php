<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
 * جدول فرعي حقيقي (HMVC) — الأنشطة والوحدات التنظيمية المرتبطة بالبرنامج
 * يُستدعى عبر modules::run() من داخل program_desc_card_show.php
 * يستقبل $program_id فقط كمعامل من الشاشة الرئيسية
 */
?>
<table class="table table-bordered" id="activities_table">
    <thead>
        <tr>
            <th>#</th>
            <th>اسم النشاط</th>
            <th>الوحدة التنظيمية المرتبطة</th>
        </tr>
    </thead>
    <tbody>
        <?php if (isset($activities_rows)) foreach ($activities_rows as $i => $act): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= $act['ACTIVITY_NAME'] ?></td>
                <td><?php
                    foreach ($activities_units[$act['ACTIVITY_ID']] ?? [] as $unit) {
                        echo "<li id='U_ACTIVITY_" . $unit['SER'] . "' class='unit-item'>
                                        <span class='unit-name'>" . $unit['UNIT_NAME'] . "</span>
                                        <span class='delete-icon' onclick=\" delete_unit('ACTIVITY', " . $unit['SER'] . " )\">
                        <span class='glyphicon glyphicon-trash'></span>
                        </span>
                    </li>";
                    } ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if (isset($can_edit) && $can_edit): ?>
    <div class="form-group col-sm-8">
        <input type="text" id="txt_new_activity_name_<?= $program_id ?>" class="form-control"
            placeholder="اسم النشاط الجديد" />
    </div>
    <div class="form-group col-sm-4">
        <button type="button" class="btn btn-default"
            onclick="add_program_activity(<?= $program_id ?>)">إضافة نشاط
        </button>
    </div>

    <script>
        function add_program_activity(program_id) {
            var activity_name = $('#txt_new_activity_name_' + program_id).val();
            $.post('<?= base_url('budg_pb/program_desc_card/add_activity') ?>', {
                program_id: program_id,
                activity_name: activity_name
            }, function(data) {
                if (parseInt(data) > 0) {
                    success_msg('رسالة', 'تمت إضافة النشاط بنجاح ..');
                    get_to_link(window.location.href);
                } else {
                    danger_msg('تحذير..', 'لم يتم إضافة النشاط');
                }
            });
        }
    </script>
<?php endif; ?>
