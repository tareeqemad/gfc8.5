<?php

/**
 * Created by PhpStorm.
 * User: lanakh
 * Date: 15/09/18
 * Time: 12:50 م
 */

$MODULE_NAME = 'indicator';
$MODULE_NAME2 = 'planning';
$TB_NAME = 'manage_indecatior_info';

$TB_NAME2 = 'indecator_info'; //controller
$TB_NAME3 = 'planning';

$get_page_url = base_url("$MODULE_NAME/$TB_NAME/public_get_page");
$create_url = base_url("$MODULE_NAME/$TB_NAME/create");
$get_all_goal = base_url("$MODULE_NAME2/$TB_NAME3/public_get_goal");
$page = 1;

echo AntiForgeryToken();
?>

<div class="row">
    <div class="toolbar">

        <div class="caption"><?= $title; ?></div>
        <ul>


            <?php if (HaveAccess($create_url)):  ?><li><a href="<?= $create_url ?>"> <i class="glyphicon glyphicon-plus"></i>جديد</a> </li><?php endif; ?>
            <li><a onclick="<?= $help ?>" href="javascript:;" class="help"><i class="icon icon-question-circle"></i></a> </li>

        </ul>

    </div>



    <div class="form-body">
        <div class="modal-body inline_form">
        </div>

        <form class="form-vertical" id="<?= $TB_NAME ?>_form">
            <div class="modal-body inline_form">


                <div class="form-group col-sm-1">
                    <label class="control-label">العام</label>
                    <div>
                        <input type="text" data-val="true" data-val-required="حقل مطلوب" name="year" id="txt_year"
                            class="form-control" dir="rtl" value="<?php echo $year_;/*$year_paln;*/ ?>">
                    </div>
                </div>

                <div class="form-group col-sm-2">

                    <label class="control-label">المحور</label>
                    <div>
                        <select name="objective" data-val="true" data-val-required="حقل مطلوب" id="dp_objective"
                            class="form-control">
                            <option value="">-</option>
                            <?php foreach ($all_objective as $row) : ?>
                                <option value="<?= $row['ID'] ?>"><?= $row['ID_NAME'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="field-validation-valid" data-valmsg-for="objective" data-valmsg-replace="true">
                    </div>
                </div>


                <div class="form-group col-sm-2">

                    <!--<label class="control-label">الهدف الاستراتيجي(العام)</label>-->
                    <label class="control-label">الغاية</label>
                    <div>
                        <select name="goal" data-val="true" data-val-required="حقل مطلوب" id="dp_goal"
                            class="form-control">
                            <option value=""></option>
                        </select>
                        <span class="field-validation-valid" data-valmsg-for="goal" data-valmsg-replace="true">
                    </div>
                </div>


                <div class="form-group col-sm-3">

                    <!-- <label class="control-label">الهدف الاستراتيجي(الخاص)</label> -->
                    <label class="control-label">الهدف الاستراتيجي</label>
                    <div>
                        <select name="goal_t" id="dp_goal_t" data-val="true" data-val-required="حقل مطلوب"
                            class="form-control">
                            <option value=""></option>
                        </select>
                        <span class="field-validation-valid" data-valmsg-for="goal" data-valmsg-replace="true">
                    </div>
                </div>


                <div class="form-group col-sm-1">
                    <label class="col-sm-1 control-label">الحالة</label>
                    <div>
                        <select name="indecator_flag" data-val="true" data-val-required="حقل مطلوب" id="dp_indecator_flag" class="form-control select2">
                            <option></option>
                            <?php foreach ($indecator_flag as $row) : ?>
                                <option value="<?= $row['CON_NO'] ?>">
                                    <?= $row['CON_NAME'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="field-validation-valid" data-valmsg-for="indecator_flag" data-valmsg-replace="true"></span>




                    </div>
                </div>

                <div class="form-group col-sm-1">
                    <label class="col-sm-1 control-label">الاعتماد</label>
                    <div>
                        <select name="adopt" data-val="true" data-val-required="حقل مطلوب" id="dp_adopt" class="form-control select2">
                            <option></option>

                            <?php foreach ($adopt_all as $row) : ?>

                                <option value="<?= $row['CON_NO']   ?>"><?= $row['CON_NAME'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="field-validation-valid" data-valmsg-for="adopt" data-valmsg-replace="true"></span>




                    </div>
                </div>

                <div class="form-group col-sm-1">
                    <label class="col-sm-1 control-label">تقييم</label>
                    <div>
                        <select name="effect" data-val="true" data-val-required="حقل مطلوب" id="dp_effect" class="form-control select2">
                            <option></option>
                            <?php foreach ($effect as $row) : ?>
                                <option value="<?= $row['CON_NO'] ?>"><?= $row['CON_NAME'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="field-validation-valid" data-valmsg-for="effect" data-valmsg-replace="true"></span>




                    </div>
                </div>

                <div class="form-group col-sm-2">
                    <label class="col-sm-12 control-label">طريقة الحساب</label>
                    <div>
                        <select name="entry_target_way" data-val="true" data-val-required="حقل مطلوب" id="dp_entry_target_way" class="form-control select2">
                            <option></option>

                            <?php foreach ($entry_target_way as $row) : ?>
                                <?php //if ($row['CON_NO'] != 2 ){
                                ?>
                                <option value="<?= $row['CON_NO']   ?>"><?= $row['CON_NAME'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="field-validation-valid" data-valmsg-for="entry_target_way" data-valmsg-replace="true"></span>




                    </div>
                </div>
                <div class="form-group col-sm-3">
                    <label class="control-label">اسم المعلومة</label>
                    <div>
                        <input type="text" name="indecator_name" id="txt_indecator_name" class="form-control" data-val-required="حقل مطلوب">
                    </div>
                </div>


            </div>


        </form>


        <div class="modal-footer">

            <button type="button" onclick="javascript:search();" class="btn btn-success"> إستعلام</button>
            <button type="button" onclick="javascript:clear_form();" class="btn btn-default"> تفريغ الحقول</button>


        </div>
        <div id="msg_container"></div>

        <div id="container">
            <?php echo '';/*modules::run($get_page_url,$page);*/ ?>
        </div>
    </div>

</div>

<?php
$scripts = <<<SCRIPT
<script type="text/javascript">
function search(){


       var values= {page:1,sector:$('#dp_objective').val(),class:$('#dp_goal').val(),second_class:$('#dp_goal_t').val(),indecator_flag:$('#dp_indecator_flag').val(),adopt:$('#dp_adopt').val(),effect:$('#dp_effect').val(),entry_target_way:$('#dp_entry_target_way').val(),indecator_name:$('#txt_indecator_name').val(),year:$('#txt_year').val()};
        get_data('{$get_page_url}',values ,function(data){

            $('#container').html(data);
        },'html');
    }

    function LoadingData(){
   var values= {page:1,sector:$('#dp_objective').val(),class:$('#dp_goal').val(),second_class:$('#dp_goal_t').val(),indecator_flag:$('#dp_indecator_flag').val(),adopt:$('#dp_adopt').val(),effect:$('#dp_effect').val(),entry_target_way:$('#dp_entry_target_way').val(),indecator_name:$('#txt_indecator_name').val(),year:$('#txt_year').val()};
              ajax_pager_data('#page_tb > tbody',values);
    }

$(document).ready(function() {
$('#dp_second_class').select2();
$('.select2').select2();
 $("#txt_year").on('change',function(){
    get_data('{$get_all_goal}',{no: 0,year:$('#txt_year').val()},function(data){

           
			$('#dp_objective').html('');
			 $('#dp_objective').append('<option></option>');
			 $('#dp_goal').html('');
             $('#dp_goal').append('<option></option>');
			 $('#dp_goal_t').html('');
             $('#dp_goal_t').append('<option></option>');
             $("#dp_objective").select2('val','');
			 $("#dp_goal").select2('val','');
			 $("#dp_goal_t").select2('val','');
            
            $.each(data,function(index, item)
            {
             $('#dp_objective').append('<option value=' + item.ID + '>' + item.ID_NAME + '</option>');
            });
            
            });
});

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



});



</script>

SCRIPT;

sec_scripts($scripts);

?>