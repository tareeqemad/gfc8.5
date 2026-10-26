<?php

/**
 * Created by PhpStorm.
 * User: lanakh
 * Date: 18/11/17
 * Time: 09:09 ص
 */

$MODULE_NAME = 'planning';
$TB_NAME = 'planning';
$manage_follow_branch = base_url("$MODULE_NAME/$TB_NAME/public_get_mange");
$get_url = base_url("$MODULE_NAME/$TB_NAME/get_id");
$create_annual_Objectives_url = base_url("$MODULE_NAME/$TB_NAME/create_annual_Objectives");
$create_url = base_url("$MODULE_NAME/$TB_NAME/create");
$edit_url = base_url("$MODULE_NAME/$TB_NAME/edit");
$delete_url = base_url("$MODULE_NAME/$TB_NAME/delete");
$delete_unit_url = base_url("$MODULE_NAME/$TB_NAME/delete_unit");
$get_page_url = base_url("$MODULE_NAME/$TB_NAME/get_page");
$back_budget_tech_url = base_url("budget/Projects/archive");
$get_all_goal = base_url("$MODULE_NAME/$TB_NAME/public_get_goal");
$manage_exe_branch = base_url("$MODULE_NAME/$TB_NAME/public_get_mange");
$cycle_exe_branch = base_url("$MODULE_NAME/$TB_NAME/public_get_cycle");
$dep_exe_branch = base_url("$MODULE_NAME/$TB_NAME/public_get_dep");
$page = 1;
$year_ =  date('Y') + 1;

echo AntiForgeryToken();
?>
<script>
    var show_page = true;
</script>
<div class="row">
    <div class="toolbar">

        <div class="caption">ادارة الأهداف السنوية</div>

        <ul>
            <li><a href="<?= $create_annual_Objectives_url ?>" target="_blank"><i class="glyphicon glyphicon-plus"></i>جديد</a>
                <?php if (HaveAccess($create_annual_Objectives_url)): ?>
            <li><a href="<?= $create_annual_Objectives_url ?>" target="_blank"><i class="glyphicon glyphicon-plus"></i>جديد</a>
            </li><?php endif; ?>
        <?php if (HaveAccess($delete_url)) echo "<li><a onclick='javascript:{$TB_NAME}_delete();' href='javascript:;'><i class='glyphicon glyphicon-remove'></i>حذف</a> </li>"; ?>
        <?php if (HaveAccess($delete_unit_url)) echo "<li><a onclick='javascript:{$TB_NAME}_delete_unit();' href='javascript:;'><i class='glyphicon glyphicon-remove'></i>حذف وحدة التخطيط</a> </li>"; ?>
        <li><a href="<?= base_url('uploads/statgic_plan_user_manual.pdf') ?>" target="_blank"><i class="icon icon-question-circle"></i>دليل المستخدم</a>

        </li>

        </ul>

    </div>


    <div class="form-body">
        <div class="modal-body inline_form">
        </div>

        <form class="form-vertical" id="<?= $TB_NAME ?>_form">
            <div class="modal-body inline_form">
                <div class="row">
                    <div class="form-group col-sm-1">
                        <label class="control-label">العام</label>
                        <div>
                            <input type="text" data-val="true" data-val-required="حقل مطلوب" name="year" id="txt_year"
                                class="form-control" dir="rtl" value="<?php echo $year_;/*$year_paln;*/ ?>">
                        </div>
                    </div>

                    <div class="form-group col-sm-3">

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
                    <div class="form-group col-sm-3">

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
                        <label class="control-label">الهدف الاستراتيجي(العام)</label>
                        <div>
                            <select name="goal_t" id="dp_goal_t" data-val="true" data-val-required="حقل مطلوب"
                                class="form-control">
                                <option value=""></option>
                            </select>
                            <span class="field-validation-valid" data-valmsg-for="goal" data-valmsg-replace="true">
                        </div>
                    </div>

                </div>
                <div class="row">
                    <div class="form-group col-sm-2 hidden">

                        <label class="control-label"> الغاية </label>
                        <div>
                            <select name="objective" id="dp_objective" data-curr="false" class="form-control">
                                <option value="">-</option>
                                <?php foreach ($all_objective as $row) : ?>
                                    <option value="<?= $row['ID'] ?>"><?php echo $row['ID_NAME'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="field-validation-valid" data-valmsg-for="objective" data-valmsg-replace="true">
                        </div>
                    </div>


                    <div class="form-group col-sm-2 hidden">

                        <label class="control-label">الهدف الاستراتيجي</label>
                        <div>
                            <select name="goal" id="dp_goal" data-curr="false" class="form-control">
                                <option value="">-</option>
                                <?php foreach ($goal_list as $row) : ?>
                                    <option value="<?= $row['ID'] ?>"><?php echo $row['ID_NAME'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="field-validation-valid" data-valmsg-for="goal" data-valmsg-replace="true">
                        </div>
                    </div>

                    <div class="form-group col-sm-2 hidden">

                        <label class="control-label">الهدف التشغيلي</label>
                        <div>
                            <select name="goal_t" id="dp_goal_t" data-curr="false" class="form-control">
                                <option value="">-</option>
                                <?php foreach ($goal_t_list as $row) : ?>
                                    <option value="<?= $row['ID'] ?>"><?php echo $row['ID_NAME'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="field-validation-valid" data-valmsg-for="goal_t" data-valmsg-replace="true">
                        </div>
                    </div>


                    <div class="form-group col-sm-1 hidden">

                        <label class="control-label">مقر مسؤلية المتابعة</label>
                        <div>
                            <select name="branch_follow" id="dp_branch_follow" data-curr="false" class="form-control">
                                <option value="">-</option>
                                <?php foreach ($branches_follow as $row) : ?>
                                    <option value="<?= $row['NO'] ?>">
                                        <?= $row['NAME'] ?>
                                    </option>


                                <?php endforeach; ?>
                            </select>

                        </div>
                    </div>
                    <div class="form-group col-sm-2 hidden">

                        <label class="control-label">مسؤلية المتابعة</label>
                        <div>
                            <select name="branch_follow_name" id="dp_branch_follow_name" data-curr="false"
                                class="form-control">
                                <option value="">-</option>
                            </select>

                        </div>
                    </div>
                </div>
                <div class="row">
                    <!-- <br>-->
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
                </div>

        </form>


        <div class="modal-footer">

            <button type="button" onclick="javascript:search();" class="btn btn-success"> إستعلام</button>
            <button type="button" onclick="$('#page_tb').tableExport({type:'excel',escape:'false'});"
                class="btn btn-danger"> اكسل
            </button>
            <button type="button" onclick="javascript:clear_form();" class="btn btn-default"> تفريغ الحقول</button>


        </div>
        <div id="msg_container"></div>

        <div id="container">

        </div>
    </div>

</div>

<?php
$scripts = <<<SCRIPT
<script type="text/javascript">


 $('.pagination li').click(function(e){
        e.preventDefault();
    });

//search();

function {$TB_NAME}_delete(){
        var url = '{$delete_url}';
        var tbl = '#page_tb';
        //var tbl1 = '#{$TB_NAME}';
        var container = $('#' + $(tbl).attr('data-container'));
         var val = [];
        //  var val2 = [];
     
        $(tbl + ' .checkboxes:checked').each(function (i) {
            
            val[i] = $(this).val();
           // val2[i]=$(this).attr('data-id') ;

        });

        if(val.length > 0){
            if(confirm('هل تريد بالتأكيد حذف '+val.length+' سجلات وحذف تفاصيلها ؟!!')){
              ajax_delete(url, val ,function(data){
     if(data==1)
         {
             success_msg('رسالة','تم حذف السجلات بنجاح ..');
                     search();
         }
                    else
                        {
        
                              
                            danger_msg('', data);
                            search();
                        
                                 }
                    
                      

             
                });
            }
        }else
            alert('يجب تحديد السجلات المراد حذفها');
    }
   function {$TB_NAME}_delete_unit(){
        var url = '{$delete_unit_url}';
        var tbl = '#page_tb';
        //var tbl1 = '#{$TB_NAME}';
        var container = $('#' + $(tbl).attr('data-container'));
         var val = [];
        //  var val2 = [];
     
        $(tbl + ' .unitcheckboxes:checked').each(function (i) {
            
            val[i] = $(this).val();
           // val2[i]=$(this).attr('data-id') ;

        });

        if(val.length > 0){
            if(confirm('هل تريد بالتأكيد حذف '+val.length+' سجلات وحذف تفاصيلها ؟!!')){
              ajax_delete(url, val ,function(data){
                if(data==1)
                    {
                         success_msg('رسالة','تم حذف السجلات بنجاح ..');
                         search();
                    }
                 else
                    {
                        danger_msg('', data);
                        search();
                        
                    }
                
                });
            }
        }else
            alert('يجب تحديد السجلات المراد حذفها');
    } 
    
 function clear_form(){
      clearForm($('#{$TB_NAME}_form'));
          }
          function search(){
$('#container').html('');
   
    var values= {page:1,activity_no:$('#txt_activity_no').val(),year:$('#txt_year').val(),class_name:$('#dp_class_name').val(),type:$('#dp_type').val(),project_name:$('#txt_project_name').val(),activity_name:$('#txt_activity_name').val(),finance:$('#dp_finance').val(),branch:$('#dp_branch').val(),from_month:$('#dp_from_month').val(),branch_follow_id:$('#dp_branch_follow').val(),manage_follow_id:$('#dp_branch_follow_name').val(),dp_manage_exe_id:$('#dp_manage_exe_id').val(),dp_cycle_exe_id:$('#dp_cycle_exe_id').val(),dp_department_exe_id:$('#dp_department_exe_id').val(),dp_objective:$('#dp_objective').val(),dp_goal:$('#dp_goal').val(),dp_goal_t:$('#dp_goal_t').val(),dp_type_project:$('#dp_type_project').val(),income:$('#txt_income').val(),total_price:$('#txt_total_price').val(),PROJECT_ID:$('#txt_PROJECT_ID').val(),achive_period:$('#dp_achive_period').val(),planning_dir:$('#dp_planning_dir').val(),type_emp:$('#dp_type_emp').val()


};
        get_data('{$get_page_url}',values ,function(data){

            $('#container').html(data);
        },'html');
    }

    function LoadingData(){
 
    var values= {page:1,activity_no:$('#txt_activity_no').val(),year:$('#txt_year').val(),class_name:$('#dp_class_name').val(),type:$('#dp_type').val(),project_name:$('#txt_project_name').val(),activity_name:$('#txt_activity_name').val(),finance:$('#dp_finance').val(),branch:$('#dp_branch').val(),from_month:$('#dp_from_month').val(),branch_follow_id:$('#dp_branch_follow').val(),manage_follow_id:$('#dp_branch_follow_name').val(),dp_manage_exe_id:$('#dp_manage_exe_id').val(),dp_cycle_exe_id:$('#dp_cycle_exe_id').val(),dp_department_exe_id:$('#dp_department_exe_id').val(),dp_objective:$('#dp_objective').val(),dp_goal:$('#dp_goal').val(),dp_goal_t:$('#dp_goal_t').val(),dp_type_project:$('#dp_type_project').val(),income:$('#txt_income').val(),total_price:$('#txt_total_price').val(),PROJECT_ID:$('#txt_PROJECT_ID').val(),achive_period:$('#dp_achive_period').val(),planning_dir:$('#dp_planning_dir').val(),type_emp:$('#dp_type_emp').val()


};
          ajax_pager_data('#page_tb > tbody',values);
    }
 $(document).ready(function() {
 $('#dp_branch').select2().on('change',function(){

        });
        $('#dp_finance').select2().on('change',function(){

        });
		$('#dp_type_project').select2().on('change',function(){

       //  checkBoxChanged();

        });

            $('#dp_type,.sel2').select2().on('change',function(){

        });
              $('#dp_class_name').select2().on('change',function(){
			  if($("#dp_class_name").val()=='')
            {
			$("#txt_activity_no").prop("readonly", true);
			$("#txt_activity_no").val('');
            }
			else
			{
			$("#txt_activity_no").prop("readonly", false);
			}
        });
        $('#dp_from_month').select2().on('change',function(){

        });
          $('#dp_branch_follow').select2().on('change',function(){
  get_data('{$manage_follow_branch}',{no: $(this).val()},function(data){
            $('#dp_branch_follow_name').html('');
              $('#dp_branch_follow_name').append('<option></option>');
              $("#dp_branch_follow_name").select2('val','');

            $.each(data,function(index, item)
            {
            $('#dp_branch_follow_name').append('<option value=' + item.ST_ID + '>' + item.ST_NAME + '</option>');
            });
            });
        });

  $('#dp_branch_follow_name').select2().on('change',function(){



        });
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



        });
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