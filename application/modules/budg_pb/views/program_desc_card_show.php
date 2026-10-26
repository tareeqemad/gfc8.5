<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

$MODULE_NAME = 'budg_pb';
$TB_NAME = 'program_desc_card';
$post_url = base_url("$MODULE_NAME/$TB_NAME/" . ($action == 'index' ? 'create' : $action));

/* روابط الجداول الفرعية الحقيقية فقط (الأنشطة/الوحدات + مؤشرات الأداء) */
$activities_details_url = "$MODULE_NAME/$TB_NAME/public_get_program_activities_details";
$indicators_details_url = "$MODULE_NAME/$TB_NAME/public_get_program_indicators_details";
// ⭐ رابط JSON لتحديث جدول الأنشطة فوريًا عند اختيار برنامج (بنمط Budget_groups.php)
$get_activities_list_url = base_url("$MODULE_NAME/$TB_NAME/get_activities_list");

if (!isset($master_tb_data)) $master_tb_data = array();
$HaveRs = count($master_tb_data) > 0;
$rs = $HaveRs ? $master_tb_data[0] : array();

echo AntiForgeryToken();
?>

<div class="row">
    <div class="toolbar">
        <div class="caption"><?= $title ?></div>
        <ul>
            <?php if (HaveAccess($back_url)): ?>
                <li><a href="<?= $back_url ?>?order_purpose=<?= @$order_purpose ?>"><i class="icon icon-reply"></i></a></li>
            <?php endif; ?>
            <li><a onclick="<?= $help ?>" href="javascript:" class="help"><i class="icon icon-question-circle"></i></a></li>
        </ul>
    </div>
</div>

<div class="form-body">

    <div id="msg_container"></div>
    <div id="container">

        <form class="form-vertical" id="<?= $TB_NAME ?>_form" method="post" action="<?= $post_url ?>" role="form"
            novalidate="novalidate">
            <div class="modal-body inline_form">

                <!-- ⭐ لم يعد هناك حاجة لحقل مخفي منفصل لـ PROGRAM_ID —
                     القائمة المنسدلة dp_program_id أدناه ترسله مباشرة (name="program_id") -->

                <!-- ============================================================
                     Fieldset 1: البرنامج — الترتيب: رقم البرنامج، ثم القائمة
                     المنسدلة، ثم وصف البرنامج
                     ============================================================ -->
                <fieldset>
                    <legend>البرنامج</legend>

                    <!-- رقم البرنامج -> يُعبَّأ تلقائيًا (قراءة فقط) عند اختيار البرنامج أدناه -->
                    <div class="form-group col-sm-2">
                        <label class="control-label">رقم البرنامج</label>
                        <input type="text" value="<?= $HaveRs ? $rs['PROGRAM_ID'] : '' ?>"
                            id="txt_program_no" class="form-control" readonly />
                    </div>

                    <!-- البرنامج -> قائمة منسدلة واحدة موحّدة (تعرض رقم واسم البرنامج معًا) -->
                    <!-- ⭐ في وضع التعديل: القائمة تُعطَّل (لا يمكن تغيير البرنامج المرتبط ببطاقة موجودة)，
                         ويُضاف حقل مخفي مرافق لإرسال القيمة فعليًا (لأن العناصر المعطّلة disabled لا تُرسل) -->
                    <div class="form-group col-sm-5">
                        <label class="control-label">البرنامج</label>
                        <select required name="program_id" id="dp_program_id" class="form-control select2"
                            <?= $HaveRs ? 'disabled' : '' ?>>
                            <option value="">اختر البرنامج...</option>
                            <?php if (isset($programs_cons)) foreach ($programs_cons as $item): ?>
                                <option value="<?= $item['PROGRAM_ID'] ?>"
                                    data-program-no="<?= $item['PROGRAM_ID'] ?>"
                                    data-program-desc="<?= htmlspecialchars($item['PROG_DESCRIPTION'], ENT_QUOTES) ?>"
                                    <?= ($HaveRs && $rs['PROGRAM_ID'] == $item['PROGRAM_ID']) ? 'selected' : '' ?>>
                                    <?= $item['PROGRAM_NO'] . ' - ' . $item['PROG_NAME'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($HaveRs): ?>
                            <input type="hidden" name="program_id" value="<?= $rs['PROGRAM_ID'] ?>" id="h_program_id" />
                        <?php endif; ?>
                    </div>

                    <!-- وصف ملخص للبرنامج -> يُعبَّأ تلقائيًا (قراءة فقط) عند اختيار البرنامج أعلاه -->
                    <div class="form-group col-sm-5">
                        <label class="control-label">وصف ملخص للبرنامج</label>
                        <input type="text" value="<?= $HaveRs ? $rs['PROG_DESCRIPTION'] : "" ?>"
                            id="txt_program_desc" class="form-control" readonly />
                    </div>
                </fieldset>

                <hr />
                <!-- ============================================================
                     Fieldset 2: الأنشطة والوحدات التنظيمية — تظهر دائمًا (حتى في
                     وضع الإنشاء)، تُحدَّث فوريًا عبر Ajax فور اختيار برنامج، وتعرض
                     أمام كل نشاط الوحدات التنظيمية المرتبطة به
                     ============================================================ -->
                <fieldset>
                    <legend>الأنشطة والوحدات التنظيمية</legend>
                    <div id="activities_container" style="clear: both">
                        <?php if ($HaveRs): ?>
                            <?php echo modules::run($activities_details_url, $rs['PROGRAM_ID']); ?>
                        <?php else: ?>
                            <p class="text-muted">اختر برنامجًا أعلاه لعرض أنشطته.</p>
                        <?php endif; ?>
                    </div>
                </fieldset>

                <hr />
                <!-- ============================================================
                     Fieldset 3: الغاية والأهداف الاستراتيجية التي يساهم في تحقيقها البرنامج
                     ============================================================ -->
                <fieldset>
                    <legend>الغاية والأهداف الاستراتيجية التي يساهم في تحقيقها البرنامج</legend>

                    <!-- الغاية -> قائمة منسدلة (GOAL_ID) بدل نص حر -->
                    <div class="form-group col-sm-6">
                        <label class="control-label">الغاية التي يساهم في تحقيقها البرنامج</label>
                        <select name="goal_id" id="dp_goal_id" class="form-control select2">
                            <option value="">اختر...</option>
                            <?php if (isset($goal_cons)) foreach ($goal_cons as $item): ?>
                                <option value="<?= $item['ID'] ?>" <?= ($HaveRs && $rs['GOAL_ID'] == $item['ID']) ? 'selected' : '' ?>>
                                    <?= $item['NAME'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- الهدف الاستراتيجي -> قائمة منسدلة (STRATEGIC_OBJECTIVE_ID) -->
                    <div class="form-group col-sm-6">
                        <label class="control-label">الهدف الاستراتيجي الذي يساهم في تحقيقه البرنامج</label>
                        <select name="strategic_objective_id" id="dp_strategic_objective_id"
                            class="form-control select2">
                            <option value="">اختر...</option>
                            <?php if (isset($strategic_objective_cons)) foreach ($strategic_objective_cons as $item): ?>
                                <option value="<?= $item['ID'] ?>" <?= ($HaveRs && $rs['STRATEGIC_OBJECTIVE_ID'] == $item['ID']) ? 'selected' : '' ?>>
                                    <?= $item['NAME'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </fieldset>

                <hr />
                <!-- ============================================================
                     Fieldset 4: الخدمات التي يقدمها البرنامج
                     ============================================================ -->
                <fieldset>
                    <legend>الخدمات التي يقدمها البرنامج</legend>
                    <div class="form-group col-sm-12">
                        <select multiple name="services[]" id="dp_services" class="form-control select2">
                            <?php
                            $selected_services = $HaveRs ? explode(',', $rs['SERVICES']) : array();
                            if (isset($services_cons)) foreach ($services_cons as $item): ?>
                                <option value="<?= $item['ID'] ?>" <?= in_array($item['ID'], $selected_services) ? 'selected' : '' ?>>
                                    <?= $item['NAME'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </fieldset>

                <hr />
                <!-- ============================================================
                     Fieldset 5: الشركاء
                     ============================================================ -->
                <fieldset>
                    <legend>الشركاء</legend>
                    <div class="form-group col-sm-12">
                        <select multiple name="partners[]" id="dp_partners" class="form-control select2">
                            <?php
                            $selected_partners = $HaveRs ? explode(',', $rs['PARTNERS']) : array();
                            if (isset($partners_cons)) foreach ($partners_cons as $item): ?>
                                <option value="<?= $item['ID'] ?>" <?= in_array($item['ID'], $selected_partners) ? 'selected' : '' ?>>
                                    <?= $item['NAME'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </fieldset>

                <?php if ($HaveRs): ?>
                    <hr />
                    <!-- ============================================================
                         Fieldset 6: مؤشرات أداء قياس البرنامج — جدول فرعي حقيقي
                         (عرض فقط -> modules::run)
                         ============================================================ -->
                    <fieldset>
                        <legend>مؤشرات أداء قياس البرنامج</legend>
                        <div style="clear: both">
                            <?php echo modules::run($indicators_details_url, $rs['PROGRAM_ID']); ?>
                        </div>
                    </fieldset>
                <?php endif; ?>

            </div>

            <div class="modal-footer">
                <?php if (!HaveAccess($post_url) && ($HaveRs == false || (isset($can_edit) ? $can_edit : false))): ?>
                    <button type="submit" data-action="submit" class="btn btn-primary">حفظ البيانات</button>
                <?php endif; ?>
            </div>

        </form>
    </div>
</div>

<?php
$scripts = <<<SCRIPT
<script>

    var GET_ACTIVITIES_LIST_URL = '{$get_activities_list_url}';

    $('.select2').select2();

    // ⭐ عند اختيار برنامج من القائمة: تعبئة رقم/وصف البرنامج تلقائيًا (قراءة فقط)
    // وتحديث جدول الأنشطة فورًا عبر Ajax (بنفس نمط get_data المعتمد فعليًا في
    // budget_groups_index.php لتحميل القوائم المتسلسلة)
    $('#dp_program_id').on('change', function(){
        var selected = $(this).find('option:selected');
        var programDesc = selected.data('program-desc') || '';
        var programNo = selected.data('program-no') || '';
        var programId = $(this).val();

        $('#txt_program_no').val(programNo);
        $('#txt_program_desc').val(programDesc);

        load_program_activities(programId);
    });

    function load_program_activities(program_id){
        var container = $('#activities_container');
        if(!program_id){
            container.html('<p class="text-muted">اختر برنامجًا أعلاه لعرض أنشطته.</p>');
            return;
        }
        get_data(GET_ACTIVITIES_LIST_URL + '/' + program_id, {}, function(data){
            var arr = (typeof data === 'string') ? JSON.parse(data) : data;
            if(!arr || !arr.length){
                container.html('<p class="text-muted">لا توجد أنشطة مرتبطة بهذا البرنامج بعد.</p>');
                return;
            }
            var html = '<table class="table table-bordered"><thead><tr><th>#</th><th>اسم النشاط</th><th>الوحدات التنظيمية المرتبطة</th></tr></thead><tbody>';
            $.each(arr, function(i, row){
                html += '<tr><td>' + (i + 1) + '</td><td>' + row.ACTIVITY_NAME + '</td><td>' + (row.UNITS_LIST || '-') + '</td></tr>';
            });
            html += '</tbody></table>';
            container.html(html);
        });
    }

    $('button[data-action="submit"]').click(function(e){
        e.preventDefault();
        var msg= 'هل تريد حفظ الطلب ؟!';
        if(confirm(msg)){
            $(this).attr('disabled','disabled');
            var form = $(this).closest('form');
            ajax_insert_update(form,function(data){
                if(parseInt(data)>1){
                    success_msg('رسالة','تم حفظ البيانات بنجاح ..');
                    get_to_link(window.location.href);
                }else if(data==1){
                    success_msg('رسالة','تم حفظ البيانات بنجاح ..');
                    get_to_link(window.location.href);
                }else{
                    danger_msg('تحذير..',data);
                }
            },'html');
        }
        setTimeout(function() {
            $('button[data-action="submit"]').removeAttr('disabled');
        }, 3000);
    });

SCRIPT;

if ($HaveRs == false) {
    $scripts = <<<SCRIPT
    {$scripts}

    function clear_form(){
        clearForm($('#{$TB_NAME}_form'));
    }

    </script>
SCRIPT;
} else {
    $scripts = <<<SCRIPT
    {$scripts}

    </script>
SCRIPT;
}

sec_scripts($scripts);
?>