<?php

/**
 * Created by PhpStorm.
 * User: fmamluk
 * Date: 03/06/26
 * Time: 13:00 م
 */

$MODULE_NAME= 'hr_attendance';
$TB_NAME= 'Attendance_departure';

$get_page_url = base_url("$MODULE_NAME/$TB_NAME/get_page");
$create_url =base_url("$MODULE_NAME/$TB_NAME/import_google_sheet");
$page_act_json = json_encode($page_act);

?>

<!-- breadcrumb -->
<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <span class="main-content-title mg-b-0 mg-b-lg-1"><?=$title?></span>
    </div>
    <div class="justify-content-center mt-2">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page"><?=$title?></li>
        </ol>
    </div>
</div>
<!-- /breadcrumb -->
<!-- Row -->
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header align-items-center d-flex py-3">
                <div class="mb-0 flex-grow-1 card-title">
                    <?=$title?>
                </div>
                <div class="flex-shrink-0">
                    <?php if (HaveAccess($create_url)) : ?>
                        <button type="button" id="btn_import" class="btn btn-warning" onclick="import_attendance()">
                            <i class="fa fa-cloud-download"></i>
                            استيراد البصمات
                        </button>
                    <?php endif; ?>
                </div>
            </div><!-- end card header -->
            <div class="card-body">

                <form class="form-vertical" id="<?= $TB_NAME ?>_form">
                    <div class="row">


                        <div class="form-group col-md-2">
                            <label> المقر</label>
                            <select name="branch_id" id="dp_branch_id" class="form-control sel2">
                                <option value="">_______</option>
                                <?php foreach ($branches as $row) : ?>
                                    <option value="<?= $row['NO'] ?>"><?= $row['NAME'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group col-md-2">
                            <label>المقر الفرعي</label>
                            <select name="sub_branch_id" id="dp_sub_branch_id" class="form-control sel2">
                                <option value="">_________</option>
                                <?php foreach ($sub_branches as $row) : ?>
                                    <option value="<?= $row['CON_NO'] ?>"><?= $row['CON_NAME'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group col-sm-2">
                            <label class="control-label">الموظف</label>
                            <div>
                                <select name="emp_no" id="dp_emp_no" class="form-control sel2" >
                                    <option value="">_________</option>
                                    <?php foreach($emp_no_cons as $row) :?>
                                        <option value="<?=$row['EMP_NO']?>"><?=$row['EMP_NO'].': '.$row['EMP_NAME']?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group col-md-2">
                            <label>من تاريخ</label>
                            <input type="date" name="from_date" id="txt_from_date" class="form-control" value="<?=date('Y-m-01')?>">
                        </div>

                        <div class="form-group col-md-2">
                            <label>إلى تاريخ</label>
                            <input type="date" name="to_date" id="txt_to_date" class="form-control" value="<?=date('Y-m-d')?>">
                        </div>

                        <div class="form-group col-md-2">
                            <label>الحالة</label>
                            <select name="status" id="dp_status" class="form-control sel2">
                                <option value="">الكل</option>
                                <option value="Entry">دخول</option>
                                <option value="Leave">خروج</option>
                                <option value="Leave with form">خروج إذن</option>
                                <option value="Entry with form">عودة إذن</option>
                                <option value="NONE">غير محدد</option>
                            </select>
                        </div>

                    </div>

                    <div class="row">

                        <div class="form-group col-md-2">
                            <label>نوع التقرير</label>
                            <select id="dp_report_type" class="form-control sel2">
                                <option value="">_________</option>
                                <option value="1">كشف الحركات</option>
                                <option value="2">ساعات الدوام والتأخير</option>
                                <option value="3">بدون بصمة حضور</option>
                                <option value="4">بدون بصمة انصراف</option>
                                <option value="5">المتأخرين صباحاً</option>
                            </select>
                        </div>

                        <?php if ($page_act == 'hr_admin' || $page_act == 'move_admin') : ?>

                        <div class="form-group col-md-2">
                            <label>بداية الدوام الرسمي</label>
                            <input type="time" id="txt_work_start" class="form-control" value="09:00" readonly>
                        </div>

                        <div class="form-group col-md-2">
                            <label>نهاية الدوام الرسمي</label>
                            <input type="time" id="txt_work_end" class="form-control" value="14:00" readonly>
                        </div>

                        <div class="form-group col-md-2">
                            <label>فترة السماح (دقيقة)</label>
                            <input type="number" id="txt_grace_minutes" class="form-control" value="0" min="0" readonly>
                        </div>

                        <?php endif; ?>

                    </div>
                </form>
                <br>
                <div class="flex-shrink-0">
                    <button type="button" onclick="javascript:run_report();" class="btn btn-primary"><i
                            class="glyphicon glyphicon-search"></i> إستعلام
                    </button>

                    <button type="button" onclick="print_current_report();" class="btn btn-info">
                        <i class="fa fa-print"></i> طباعة التقرير
                    </button>

                    <button type="button" onclick="export_current_report();" class="btn btn-success">
                        <i class="fa fa-file-excel-o"></i> تصدير إكسل (تفصيلي)
                    </button>

                    <button type="button" onclick="javascript:clear_form();" class="btn btn-cyan-light"><i
                            class="fa fa-eraser"></i>تفريغ الحقول
                    </button>
                </div>
                <hr>
                <div id="container">

                </div>
            </div><!--end card body-->
        </div><!--end card --->
    </div><!--end col lg-12--->
</div><!--end row--->

<?php

$import_url = base_url('hr_attendance/Attendance_departure/import_google_sheet');
$export_url =  base_url('hr_attendance/Attendance_departure/export_excel') ;
$report_url =  base_url("hr_attendance/Attendance_departure/print_report") ;
$analysis_url = base_url('hr_attendance/Attendance_departure/attendance_analysis');
$print_analysis_url = base_url('hr_attendance/Attendance_departure/print_analysis');
$export_analysis_url = base_url('hr_attendance/Attendance_departure/export_excel_analysis');
$scripts = <<<SCRIPT

<script type="text/javascript">

    $('.sel2').select2();

    var import_url = '{$import_url}';
    var export_url ='{$export_url}';
    var report_url = '{$report_url}';
    var analysis_url = '{$analysis_url}';
    var print_analysis_url = '{$print_analysis_url}';
    var export_analysis_url = '{$export_analysis_url}';
    var page_act = {$page_act_json};

    if(page_act === 'my'){
        $('#dp_emp_no').prop('disabled', true);
        $('#dp_branch_id').prop('disabled', true);
        $('#dp_sub_branch_id').prop('disabled', true);
    }

    // قيم قائمة "نوع التقرير" أرقام بالواجهة، وهاد الجدول بيحولها لنفس أسماء الأوضاع (mode) يلي الكنترولر متعود عليها
    var report_type_map = {
        '1': 'list',
        '2': 'hours',
        '3': 'no_entry',
        '4': 'no_leave',
        '5': 'late'
    };

    // يتأكد إنه المستخدم اختار نوع تقرير قبل ما يسمح بأي عملية (استعلام/طباعة/تصدير)
    function selected_report_type(){

        var val = $('#dp_report_type').val();

        if(val == '' || !report_type_map[val]){
            danger_msg('رسالة','يجب اختيار نوع التقرير أولاً');
            return null;
        }

        return report_type_map[val];
    }

    // يوجّه زر "إستعلام" الموحد حسب نوع التقرير المختار بقائمة "نوع التقرير"
    function run_report(){

        var type = selected_report_type();

        if(type == null){
            return;
        }

        if(type == 'list'){
            search();
        }else{
            attendance_report(type);
        }
    }

    // يوجّه زر "طباعة التقرير" الموحد حسب نوع التقرير المختار
    function print_current_report(){

        var type = selected_report_type();

        if(type == null){
            return;
        }

        if(type == 'list'){
            print_report();
        }else{
            print_analysis(type);
        }
    }

    // يوجّه زر "تصدير إكسل" الموحد حسب نوع التقرير المختار
    function export_current_report(){

        var type = selected_report_type();

        if(type == null){
            return;
        }

        if(type == 'list'){
            export_excel();
        }else{
            export_excel_analysis(type);
        }
    }

    function values_search(add_page){

        var values = {
            page: 1,
            page_act: '{$page_act}',
            branch_id: $('#dp_branch_id').val(),
            sub_branch_id: $('#dp_sub_branch_id').val(),
            emp_no: $('#dp_emp_no').val(),
            from_date: $('#txt_from_date').val(),
            to_date: $('#txt_to_date').val(),
            status: $('#dp_status').val()
        };

        if(add_page == 0)
            delete values.page;

        return values;
    }

    function search(){

        var values = values_search(1);

        if($('#txt_from_date').val() == ''){
            danger_msg('رسالة','يجب إدخال تاريخ البداية');
            return;
        }

        if($('#txt_to_date').val() == ''){
            danger_msg('رسالة','يجب إدخال تاريخ النهاية');
            return;
        }

        get_data(
            '{$get_page_url}',
            values,
            function(data){
                $('#container').html(data);
            },
            'html'
        );

    }

    function LoadingData(){

        var values = values_search(0);

        ajax_pager_data(
            '#attendance_departure_tb > tbody',
            values
        );

    }

    function clear_form(){

        $('#txt_from_date').val('');
        $('#txt_to_date').val('');
        $('#dp_emp_no').select2('val',0);
        
    }


    function export_excel(){

        if($('#txt_from_date').val() == ''){
            danger_msg('رسالة','يجب إدخال تاريخ البداية');
            return;
        }

        if($('#txt_to_date').val() == ''){
            danger_msg('رسالة','يجب إدخال تاريخ النهاية');
            return;
        }

        var url =
            export_url
            + '?branch_id=' + $('#dp_branch_id').val()
            + '&sub_branch_id=' + $('#dp_sub_branch_id').val()
            + '&emp_no=' + $('#dp_emp_no').val()
            + '&from_date=' + $('#txt_from_date').val()
            + '&to_date=' + $('#txt_to_date').val()
            + '&status=' + $('#dp_status').val()
            + '&page_act={$page_act}';
        window.location = url;
        
    }

    function import_attendance(){

        $('#btn_import').prop('disabled', true);

        $('#btn_import').html(
            '<i class="fa fa-spinner fa-spin"></i> جاري الاستيراد...'
        );

        $.ajax({

            url: import_url,

            type: 'POST',

            data: {
                branch_id : $('#dp_branch_id').val(),
                sub_branch_id : $('#dp_sub_branch_id').val(),
                from_date : $('#txt_from_date').val(),
                to_date   : $('#txt_to_date').val()
            },

            success: function(response){

                $('#btn_import').prop('disabled', false);

                $('#btn_import').html(
                    'استيراد البصمات'
                );

                try{

                    var result = (typeof response === 'string')
                        ? JSON.parse(response)
                        : response;

                    if(result.success){

                        success_msg(
                            'نجاح',
                            'تم استيراد ' + result.count + ' سجل بنجاح'
                        );

                        search();

                    }else{

                        danger_msg(
                            'خطأ',
                            result.message || 'فشل استيراد البصمات'
                        );
                    }

                }catch(e){

                    console.log(response);

                    danger_msg(
                        'خطأ',
                        'استجابة غير صحيحة من الخادم'
                    );
                }
            },

            error: function(xhr){

                $('#btn_import').prop('disabled', false);

                $('#btn_import').html(
                    'استيراد البصمات'
                );

                console.log('STATUS:', xhr.status);
                console.log('RESPONSE:', xhr.responseText);

                danger_msg(
                    'خطأ',
                    'فشل الاتصال بالخادم'
                );
            }

        });

    }

    function print_report(){

        if($('#txt_from_date').val() == ''){
            danger_msg('رسالة','يجب إدخال تاريخ البداية');
            return;
        }

        if($('#txt_to_date').val() == ''){
            danger_msg('رسالة','يجب إدخال تاريخ النهاية');
            return;
        }

        var url =
            report_url
            + '?branch_id=' + encodeURIComponent($('#dp_branch_id').val())
            + '&sub_branch_id=' + encodeURIComponent($('#dp_sub_branch_id').val())
            + '&emp_no=' + encodeURIComponent($('#dp_emp_no').val())
            + '&from_date=' + encodeURIComponent($('#txt_from_date').val())
            + '&to_date=' + encodeURIComponent($('#txt_to_date').val())
            + '&status=' + encodeURIComponent($('#dp_status').val())
            + '&page_act=' + encodeURIComponent(page_act);

        window.open(url, '_blank');
    }

    function attendance_report(mode){

        if($('#txt_from_date').val() == ''){
            danger_msg('رسالة','يجب إدخال تاريخ البداية');
            return;
        }

        if($('#txt_to_date').val() == ''){
            danger_msg('رسالة','يجب إدخال تاريخ النهاية');
            return;
        }

        if(mode == 'late' && $('#txt_work_start').val() == ''){
            danger_msg('رسالة','يجب إدخال وقت بداية الدوام الرسمي');
            return;
        }

        if(mode == 'hours' && $('#txt_work_end').val() == ''){
            danger_msg('رسالة','يجب إدخال وقت نهاية الدوام الرسمي');
            return;
        }

        var values = {
            mode: mode,
            page_act: '{$page_act}',
            branch_id: $('#dp_branch_id').val(),
            sub_branch_id: $('#dp_sub_branch_id').val(),
            emp_no: $('#dp_emp_no').val(),
            from_date: $('#txt_from_date').val(),
            to_date: $('#txt_to_date').val(),
            work_start: $('#txt_work_start').val(),
            work_end: $('#txt_work_end').val(),
            grace_minutes: $('#txt_grace_minutes').val() || 0
        };

        get_data(
            analysis_url,
            values,
            function(data){
                $('#container').html(data);
            },
            'html'
        );
    }

    function analysis_query_string(mode){

        return '?mode=' + encodeURIComponent(mode)
            + '&branch_id=' + encodeURIComponent($('#dp_branch_id').val())
            + '&sub_branch_id=' + encodeURIComponent($('#dp_sub_branch_id').val())
            + '&emp_no=' + encodeURIComponent($('#dp_emp_no').val())
            + '&from_date=' + encodeURIComponent($('#txt_from_date').val())
            + '&to_date=' + encodeURIComponent($('#txt_to_date').val())
            + '&work_start=' + encodeURIComponent($('#txt_work_start').val())
            + '&work_end=' + encodeURIComponent($('#txt_work_end').val())
            + '&grace_minutes=' + encodeURIComponent($('#txt_grace_minutes').val() || 0)
            + '&page_act=' + encodeURIComponent(page_act);
    }

    function print_analysis(mode){
        window.open(print_analysis_url + analysis_query_string(mode), '_blank');
    }

    function export_excel_analysis(mode){
        window.location = export_analysis_url + analysis_query_string(mode) + '&force_detail=1';
    }

</script>

SCRIPT;

sec_scripts($scripts);

?>
