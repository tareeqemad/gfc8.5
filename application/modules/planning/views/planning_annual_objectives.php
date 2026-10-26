<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: lanakh
 * Date: 29/03/18
 * Time: 09:45 ص
 */
$MODULE_NAME = 'planning';
$TB_NAME = 'planning';
$TB_NAME_ = 'planning_unit';
$GOALS_TB_NAME = 'public_get_goal_';
$get_all_goal = base_url("$MODULE_NAME/$TB_NAME/public_get_goal");
$manage_exe_branch = base_url("$MODULE_NAME/$TB_NAME/public_get_mange");
$cycle_exe_branch = base_url("$MODULE_NAME/$TB_NAME/public_get_cycle");
$dep_exe_branch = base_url("$MODULE_NAME/$TB_NAME/public_get_dep");
$select_items_url = base_url("$MODULE_NAME/$TB_NAME_/public_get_Objective_/1");
$select_spfic_statgic_url = base_url("$MODULE_NAME/$TB_NAME_/public_get_stratgic_/1");
$select_spfic_year_url = base_url("$MODULE_NAME/$TB_NAME_/public_get_annual/1");


$post_url = '';
$rs = array();
$HaveRs = (!$isCreate) ? true : false;
echo AntiForgeryToken();

?>

<div class="row">
    <div class="toolbar">

        <div class="caption"><?= $title ?></div>

    </div>
</div>
<div class="form-body">
    <div id="msg_container"></div>
    <div id="container">
        <form class="form-vertical" id="<?= $TB_NAME ?>_form" method="post" action="<?= $post_url ?>" role="form" novalidate="novalidate">
            <div class="modal-body inline_form">
                <fieldset hidden>
                    <legend>المحاور و الأهداف</legend>

                    <div class="form-group col-sm-1">
                        <label class="col-sm-2 control-label">عام الخطة</label>
                        <div>
                            <input type="text" data-val="true" data-val-required="حقل مطلوب" name="year" id="txt_year"
                                class="form-control" dir="rtl" readonly
                                value="<?php echo $year_paln; ?>"
                                readonly>
                            <span class="field-validation-valid" data-valmsg-for="year"
                                data-valmsg-replace="true"></span>
                            <input type="hidden" data-val="true" data-val-required="حقل مطلوب" name="seq" id="txt_seq"
                                value="<?php echo (count($rs) > 0) ? $rs['SEQ'] : ''; ?>" class="form-control">

                        </div>
                    </div>

                    <div class="form-group col-sm-3">
                        <label class="col-sm-2 control-label">المحور</label>
                        <div>

                            <select name="objective" data-val="true" data-val-required="حقل مطلوب" id="dp_objective"
                                class="form-control">

                                <option></option>
                                <?php foreach ($all_objective as $row): ?>
                                    <?php if ($no_edit == 2) {
                                        if ($row['ID'] == $objective_id) { ?>
                                            <option value="<?= $row['ID'] ?>" <?PHP if ($row['ID'] == $objective_id) {
                                                                                    echo " selected";
                                                                                } ?>><?= $row['ID_NAME'] ?></option>
                                        <?php
                                        }
                                    } else { ?>
                                        <option value="<?= $row['ID'] ?>" <?php if ($row['ID'] == $objective_id) {
                                                                                echo " selected";
                                                                            } ?>><?= $row['ID_NAME'] ?></option>
                                    <?php
                                    } ?>


                                <?php
                                endforeach; ?>


                            </select>

                            <span class="field-validation-valid" data-valmsg-for="objective"
                                data-valmsg-replace="true"></span>


                        </div>
                    </div>


                    <div class="form-group col-sm-3">
                        <!-- <label class="col-sm-6 control-label">الهدف الاستراتيجي(العام)</label> -->
                        <label class="col-sm-6 control-label">الغاية</label>
                        <div>

                            <select name="goal" data-val="true" data-val-required="حقل مطلوب" id="dp_goal"
                                class="form-control">

                                <option></option>
                                <?php foreach ($goal_list as $row): ?>
                                    <?php if ($no_edit == 2) {
                                        if ($row['ID'] == $goal_select) { ?>
                                            <option value="<?= $row['ID'] ?>" <?PHP if ($row['ID'] == $goal_select) {
                                                                                    echo " selected";
                                                                                } ?>><?= $row['ID_NAME'] ?></option>
                                        <?php
                                        }
                                    } else { ?>
                                        <option value="<?= $row['ID'] ?>" <?php if ($row['ID'] == $goal_select) {
                                                                                echo " selected";
                                                                            } ?>><?= $row['ID_NAME'] ?></option>
                                    <?php
                                    } ?>
                                <?php
                                endforeach; ?>
                            </select>

                            <span class="field-validation-valid" data-valmsg-for="goal"
                                data-valmsg-replace="true"></span>

                        </div>
                    </div>


                    <div class="form-group col-sm-3">
                        <!-- <label class="col-sm-6 control-label">الهدف الاستراتيجي(الخاص)</label> -->
                         <label class="col-sm-6 control-label">الهدف الاستراتيجي</label>
                        <div>

                            <select name="goal_t" id="dp_goal_t" data-val="true" data-val-required="حقل مطلوب"
                                class="form-control">

                                <option></option>
                                <?php foreach ($goal_t_list as $row): ?>
                                    <?php if ($no_edit == 2) {
                                        if ($row['ID'] == $goal_t_select) { ?>
                                            <option value="<?= $row['ID'] ?>" <?PHP if ($row['ID'] == $goal_t_select) {
                                                                                    echo " selected";
                                                                                } ?>><?= $row['ID_NAME'] ?></option>
                                        <?php
                                        }
                                    } else { ?>
                                        <option value="<?= $row['ID'] ?>" <?php if ($row['ID'] == $goal_t_select) {
                                                                                echo " selected";
                                                                            } ?>><?= $row['ID_NAME'] ?></option>
                                    <?php
                                    } ?>

                                <?php
                                endforeach; ?>
                            </select>

                            <span class="field-validation-valid" data-valmsg-for="goal_t"
                                data-valmsg-replace="true"></span>

                        </div>
                    </div>


                </fieldset>
            </div>


            </fieldset>
           <!-- <hr /> -->
            <fieldset hidden>
                <legend>الوحدة التنظيمية</legend>
                <div class="form-group col-sm-1">

                    <label class="control-label">مسؤلية التنفيذ</label>
                    <div>
                        <select name="branch" id="dp_branch" data-curr="false" class="form-control" data-val="true"
                            data-val-required="حقل مطلوب">
                            <option value="">-</option>
                            <?php foreach ($branches as $row) : ?>
                                <option value="<?= $row['NO'] ?>">
                                    <?= $row['NAME'] ?>
                                </option>


                            <?php endforeach; ?>
                        </select>
                        <span class="field-validation-valid" data-valmsg-for="branch"
                            data-valmsg-replace="true"></span>
                    </div>
                </div>
                <div class="form-group col-sm-3">

                    <label class="control-label">الادارة</label>
                    <div>
                        <select name="manage_exe_id" data-val="true" data-val-required="حقل مطلوب"
                            id="dp_manage_exe_id" class="form-control">
                            <option value=""></option>

                        </select>
                        <span class="field-validation-valid" data-valmsg-for="manage_exe_id"
                            data-valmsg-replace="true">
                    </div>
                </div>
                <div class="form-group col-sm-3">

                    <label class="control-label">الدائرة</label>
                    <div>
                        <select name="cycle_exe_id" id="dp_cycle_exe_id" class="form-control">
                            <option value=""></option>
                        </select>
                        <span class="field-validation-valid" data-valmsg-for="cycle_exe_id"
                            data-valmsg-replace="true">
                    </div>
                </div>
                <div class="form-group col-sm-3">

                    <label class="control-label">القسم</label>
                    <div>
                        <select name="department_exe_id" id="dp_department_exe_id" class="form-control">
                            <option value=""></option>
                        </select>
                        <span class="field-validation-valid" data-valmsg-for="department_exe_id"
                            data-valmsg-replace="true">
                    </div>
                </div>
            </fieldset>
           <!-- <hr /> -->



            <fieldset>

                <legend>الأهداف السنوية</legend>

                <div class="form-group col-sm-12">
                    <?php echo modules::run("$MODULE_NAME/$TB_NAME_/$GOALS_TB_NAME", $HaveRs ? $rs['SER'] : $SER_PLAN); ?>
                </div>


                <hr />
                <div class="modal-footer">

                </div>





    </div>


    </form>
</div>


<?php
$scripts = <<<SCRIPT
<script type="text/javascript">
$('#dp_objective').select2().on('change',function(){

     //  checkBoxChanged();
             get_data('{$get_all_goal}',{no: $(this).val(),year:$('#txt_year').val()},function(data){

            $('#dp_goal').html('');
             $('#dp_goal').append('<option></option>');
             $("#dp_goal").select2('val','');
             $("#dp_goal_t").select2('val','');
            $.each(data,function(index, item)
            {
            $('#dp_goal').append('<option value=' + item.ID + '>' + item.ID_NAME + '</option>');
            });
            });

        });

           $('#dp_goal').select2().on('change',function(){

       //  checkBoxChanged();
          get_data('{$get_all_goal}',{no: $(this).val(),year:$('#txt_year').val()},function(data){
            $('#dp_goal_t').html('');
             $('#dp_goal_t').append('<option></option>');
             $("#dp_goal_t").select2('val','');
            $.each(data,function(index, item)
            {
            $('#dp_goal_t').append('<option value=' + item.ID + '>' + item.ID_NAME + '</option>');
            });
            });

        });


  $('#dp_goal_t').select2().on('change',function(){

       //  checkBoxChanged();

        });
        $(document).ready(function() {
 $('#dp_branch').select2().on('change',function(){

          get_data('{$manage_exe_branch}',{no: $(this).val()},function(data){
            $('#dp_manage_exe_id').html('');
              $('#dp_manage_exe_id').append('<option></option>');
              $("#dp_manage_exe_id").select2('val','');
                 $("#dp_cycle_exe_id").select2('val','');
                 $("#dp_department_exe_id").select2('val','');
            $.each(data,function(index, item)
            {
            $('#dp_manage_exe_id').append('<option value=' + item.ST_ID + '>' + item.ST_NAME + '</option>');
            });
            });

        });
		
		$('#dp_manage_exe_id').select2().on('change',function(){

       //  checkBoxChanged();
        //  checkBoxChanged();
             get_data('{$cycle_exe_branch}',{no: $(this).val()},function(data){
            $('#dp_cycle_exe_id').html('');
              $('#dp_cycle_exe_id').append('<option></option>');
              $("#dp_cycle_exe_id").select2('val','');
             $("#dp_department_exe_id").select2('val','');
            $.each(data,function(index, item)
            {
            $('#dp_cycle_exe_id').append('<option value=' + item.ST_ID + '>' + item.ST_NAME + '</option>');
            });
            });

        });


         $('#dp_cycle_exe_id').select2().on('change',function(){

       //  checkBoxChanged();
        //  checkBoxChanged();

       //  checkBoxChanged();
             get_data('{$dep_exe_branch}',{no: $(this).val()},function(data){
           $('#dp_department_exe_id').html('');
           $('#dp_department_exe_id').append('<option></option>');
            $("#dp_department_exe_id").select2('val','');

            $.each(data,function(index, item)
            {
            $('#dp_department_exe_id').append('<option value=' + item.ST_ID + '>' + item.ST_NAME + '</option>');
            });
            });

        });

         $('#dp_department_exe_id').select2().on('change',function(){

       //  checkBoxChanged();



        }); });
        function show_doc(id,plan_no){
if(id == 0 || id =='' || plan_no ==0 || plan_no=='' )
 {
  danger_msg('يتوجب عليك حفظ المحور اولا!!');
  }
  else
  {
_showReport('$select_items_url/'+id+'/'+plan_no);

}
}
//////////////////////////////////
function show_doc_spefic(id,plan_no){
if(id == 0 || id =='' || plan_no ==0 || plan_no=='' )
 {
  danger_msg('يتوجب عليك حفظ المحور اولا!!');
  }
  else
  {
_showReport('$select_spfic_statgic_url/'+id+'/'+plan_no);
//alert(id);

}
}

//////////////////////////////////
function show_doc_annual(id,plan_no){
if(id == 0 || id =='' || plan_no ==0 || plan_no=='' )
 {
  danger_msg('يتوجب عليك حفظ المحور اولا!!');
  }
  else
  {
_showReport('$select_spfic_year_url/'+id+'/'+plan_no);
//alert(id);

}
}
</script>

SCRIPT;

sec_scripts($scripts);

?>