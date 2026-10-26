<?php

/**
 * Created by PhpStorm.
 * User: lanakh
 * Date: 18/11/17
 * Time: 09:09 ص
 */

$MODULE_NAME = 'budg_pb';
$PLAN_MODULE_NAME = 'planning';
$TB_NAME = 'Kpi_assignment';
$PLAN_TB_NAME = 'planning';
$STRATGIC_NAME = 'Strategic_planning';

$get_page_url = base_url("$MODULE_NAME/$TB_NAME/get_page");
$get_all_goal = base_url("$PLAN_MODULE_NAME/$PLAN_TB_NAME/public_get_goal");
$Assignment_url = base_url("$MODULE_NAME/$TB_NAME/assignment");
$get_to_url = base_url("$MODULE_NAME/$TB_NAME/index");



$page = 1;




echo AntiForgeryToken();
?>
<script>

</script>
<div class="row">
    <div class="toolbar">

        <div class="caption">تنسيب المؤشرات</div>

        <ul>
            <!--<li><a onclick='javascript:merge();' href='javascript:;'><i class='glyphicon glyphicon-download'></i>دمج المستخلصات</a> </li>"; ?>-->
            <li><a onclick='javascript:Assignment();' href='javascript:;'><i class='glyphicon glyphicon-download'></i>تنسيب المؤشرات</a> </li>
            <li><a onclick="<?= $help ?>" href="javascript:;" class="help"><i class="icon icon-question-circle"></i></a> </li>
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
                                class="form-control" dir="rtl" value="<?php echo date('Y'); ?>">
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

                        <label class="control-label">الهدف الاستراتيجي</label>
                        <div>
                            <select name="goal_t" id="dp_goal_t" data-val="true" data-val-required="حقل مطلوب"
                                class="form-control">
                                <option value=""></option>
                            </select>
                            <span class="field-validation-valid" data-valmsg-for="goal" data-valmsg-replace="true">
                        </div>
                    </div>

                </div>



        </form>


        <div class="modal-footer">



        </div>
        <div id="msg_container"></div>

        <div id="container">

        </div>
    </div>

</div>

<?php
$scripts = <<<SCRIPT
<script type = "text/javascript" >

function search() {
    if ($('#txt_year').val() == '' || $('#txt_year').val() == null) {
        danger_msg('يتوجب ادخال العام !!');
    } else {
        var values = {
            page: 1,
            year: $('#txt_year').val(),
            dp_objective: $('#dp_objective').val(),
            dp_goal: $('#dp_goal').val(),
            dp_goal_t: $('#dp_goal_t').val()
        };
        get_data('{$get_page_url}', values, function(data) {

            $('#container').html(data);
        }, 'html');
    }
}

function LoadingData() {
    var values = {
        page: 1,
        year: $('#txt_year').val(),
        dp_objective: $('#dp_objective').val(),
        dp_goal: $('#dp_goal').val(),
        dp_goal_t: $('#dp_goal_t').val()
    };
    ajax_pager_data('#page_tb > tbody', values);
}


$(document).ready(function() {

            $("#txt_year").on('change', function() {

                get_data('{$get_all_goal}', {
                    no: 0,
                    year: $('#txt_year').val()
                }, function(data) {


                    $('#dp_objective').html('');
                    $('#dp_objective').append('<option></option>');
                    $('#dp_goal').html('');
                    $('#dp_goal').append('<option></option>');
                    $('#dp_goal_t').html('');
                    $('#dp_goal_t').append('<option></option>');
                    $("#dp_objective").select2('val', '');
                    $("#dp_goal").select2('val', '');
                    $("#dp_goal_t").select2('val', '');

                    $.each(data, function(index, item) {
                        $('#dp_objective').append('<option value=' + item.ID + '>' + item.ID_NAME + '</option>');
                    });
                });
                search();
            });

            $('#dp_objective').select2().on('change', function() {

                //  checkBoxChanged();
                get_data('{$get_all_goal}', {
                    no: $(this).val(),
                    year: $('#txt_year').val()
                }, function(data) {

                    $('#dp_goal').html('');
                    $('#dp_goal').append('<option></option>');
                    $("#dp_goal").select2('val', '');
                    $("#dp_goal_t").select2('val', '');

                    $.each(data, function(index, item) {
                        $('#dp_goal').append('<option value=' + item.ID + '>' + item.ID_NAME + '</option>');
                    });
                });
                search();
            });

            $('#dp_goal').select2().on('change', function() {

                //  checkBoxChanged();
                get_data('{$get_all_goal}', {
                    no: $(this).val(),
                    year: $('#txt_year').val()
                }, function(data) {
                    $('#dp_goal_t').html('');
                    $('#dp_goal_t').append('<option></option>');
                    $("#dp_goal_t").select2('val', '');

                    $.each(data, function(index, item) {
                        $('#dp_goal_t').append('<option value=' + item.ID + '>' + item.ID_NAME + '</option>');
                    });
                });
                search();
            });


            $('#dp_goal_t').select2().on('change', function() {

                //  checkBoxChanged();
                search();
            });



});





function Assignment() {
	var url = '{$Assignment_url}';
	var tbl = '#page_tb';

	var container = $('#' + $(tbl).attr('data-container'));
	var val = [];

	var x;
	$(tbl + ' .checkboxes:checked').each(function(i) {
		val[i] = $(this).val();



	});
	/*x = val[0];
	for (v = 1; v < val.length; v++) {
		x = x + ',' + val[v];

	}*/

	if (val.length >= 1) {
		if (confirm('هل تريد بالتأكيد تنسيب ' + val.length + ' المؤشرات في السجلات المحددة ؟!!')) {
			/////////////////
			get_data('{$Assignment_url}', {
				id: val,
				year: $('#txt_year').val(),
				objective: $('#dp_objective').val(),
				goal: $('#dp_goal').val(),
				goal_t: $('#dp_goal_t').val()
			}, function(data) {
				if (Number(data) > 0)

					setTimeout(function() {
						success_msg('رسالة', 'تمت عملية التنسيب بنجاح ..');
						get_to_link('{$get_to_url}');

					}, 1000);

			}, 'html');
			//////////////////


		}
	} else
		alert('يجب تحديد السجلات المراد تنسيبها');
}




</script>

SCRIPT;

sec_scripts($scripts);

?>