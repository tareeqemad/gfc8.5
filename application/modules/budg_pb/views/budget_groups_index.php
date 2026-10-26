<?php
$MODULE_NAME = 'budg_pb';
$TB_NAME     = 'budget_groups';

$get_url            = base_url("$MODULE_NAME/$TB_NAME/get");
$create_url         = base_url("$MODULE_NAME/$TB_NAME/create");
$edit_url           = base_url("$MODULE_NAME/$TB_NAME/edit");
$delete_url         = base_url("$MODULE_NAME/$TB_NAME/delete");
$get_page_url       = base_url("$MODULE_NAME/$TB_NAME/get_page");
$get_activities_url = base_url("$MODULE_NAME/$TB_NAME/get_activities_list");

// مؤقتاً — معطّل لحين إضافة صلاحيات الموديول في SYSTEM_MENU_TB
// if (HaveAccess($edit_url))
//     $edit = 'edit';
// else
//     $edit = '';
$edit = 'edit';

echo AntiForgeryToken();
?>

<div class="row">
    <div class="toolbar">
        <div class="caption"><?= $title ?></div>

        <ul>
            <?php /* مؤقتاً — معطّل لحين إضافة صلاحيات الموديول
            if (HaveAccess($create_url)): ?>
                <li><a href="javascript:;" onclick="open_group_modal();"><i class="glyphicon glyphicon-plus"></i> عضو جديد </a></li>
            <?php endif; */ ?>
            <li><a href="javascript:;" onclick="open_group_modal();"><i class="glyphicon glyphicon-plus"></i> عضو جديد </a></li>
        </ul>
    </div>

    <div class="form-body">

        <form class="form-vertical" id="<?= $TB_NAME ?>_form">
            <div class="modal-body inline_form">

                <div class="form-group col-sm-2">
                    <label class="control-label">نوع الربط</label>
                    <div>
                        <select name="f_program_type" id="sel_f_program_type" class="form-control sel2" data-placeholder="-الكل-">
                            <option value=""></option>
                            <?php foreach ($program_types as $r): ?>
                                <option value="<?= $r['CON_NO'] ?>"><?= $r['CON_NAME'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group col-sm-3">
                    <label class="control-label">البرنامج</label>
                    <div>
                        <select name="f_program_no_master" id="sel_f_program_master" class="form-control sel2" data-placeholder="-الكل-">
                            <option value=""></option>
                            <?php foreach ($programs as $p): ?>
                                <option value="<?= $p['PROGRAM_ID'] ?>"><?= $p['PROG_NAME'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group col-sm-3">
                    <label class="control-label">النشاط</label>
                    <div>
                        <select name="f_activity_no" id="sel_f_activity" class="form-control sel2" data-placeholder="-الكل-">
                            <option value=""></option>
                        </select>
                    </div>
                </div>

                <input type="hidden" name="f_program_no" id="h_f_program_no" />

                <div class="form-group col-sm-2">
                    <label class="control-label">نوع العضو</label>
                    <div>
                        <select name="f_group_type" id="sel_f_group_type" class="form-control sel2" data-placeholder="-الكل-">
                            <option value=""></option>
                            <?php foreach ($group_types as $r): ?>
                                <option value="<?= $r['CON_NO'] ?>"><?= $r['CON_NAME'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group col-sm-1">
                    <label class="control-label">رقم وظيفي</label>
                    <div>
                        <input type="text" name="f_emp_no" id="txt_f_emp_no" class="form-control">
                    </div>
                </div>

                <div class="form-group col-sm-3">
                    <label class="control-label">اسم العضو</label>
                    <div>
                        <input type="text" name="f_name" id="txt_f_name" class="form-control">
                    </div>
                </div>

            </div>
        </form>


        <div class="modal-footer">
            <button type="button" onclick="javascript:search();" class="btn btn-success"> إستعلام</button>
            <button type="button" onclick="$('#page_tb').tableExport({type:'excel',escape:'false'});" class="btn btn-success"> اكسل </button>
            <button type="button" onclick="javascript:clear_form();" class="btn btn-default"> تفريغ الحقول</button>
        </div>

        <div id="msg_container"></div>

        <div id="container">
            <?= modules::run($get_page_url, $page); ?>
        </div>

    </div>

</div>


<div class="modal fade" id="groupModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content _950">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <h4 class="modal-title" id="group_modal_title"> إضافة عضو </h4>
            </div>
            <form class="form-vertical" id="group_form" method="post" action="<?= $create_url ?>" role="form" novalidate="novalidate">
                <div class="modal-body inline_form">

                    <input type="hidden" value="" name="ser" id="h_ser" />

                    <div class="form-group col-sm-4">
                        <label class="control-label">نوع الربط <span class="text-danger">*</span></label>
                        <div>
                            <select required name="program_type" id="sel_program_type" class="form-control sel2-modal">
                                <option value="">-اختر-</option>
                                <?php foreach ($program_types as $r): ?>
                                    <option value="<?= $r['CON_NO'] ?>"><?= $r['CON_NAME'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group col-sm-4" id="program_master_group">
                        <label class="control-label">البرنامج <span class="text-danger">*</span></label>
                        <div>
                            <select required name="program_master" id="sel_program_master" class="form-control sel2-modal">
                                <option value="">-اختر-</option>
                                <?php foreach ($programs as $p): ?>
                                    <option value="<?= $p['PROGRAM_ID'] ?>"><?= $p['PROG_NAME'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group col-sm-4" id="grp_activity" style="display:none">
                        <label class="control-label">النشاط <span class="text-danger">*</span></label>
                        <div>
                            <select name="activity_no" id="sel_activity" class="form-control sel2-modal">
                                <option value="">-اختر-</option>
                            </select>
                        </div>
                    </div>

                    <input type="hidden" value="" name="program_no" id="h_program_no" />

                    <div class="form-group col-sm-4">
                        <label class="control-label">نوع العضو <span class="text-danger">*</span></label>
                        <div>
                            <select required name="group_type" id="sel_group_type" class="form-control sel2-modal">
                                <option value="">-اختر-</option>
                                <?php foreach ($group_types as $r): ?>
                                    <option value="<?= $r['CON_NO'] ?>"><?= $r['CON_NAME'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group col-sm-4">
                        <label class="control-label">البريد الإلكتروني</label>
                        <div>
                            <input type="email" name="email" id="txt_email" class="form-control" />
                        </div>
                    </div>

                    <div class="form-group col-sm-4">
                        <label class="control-label">رقم الموبايل</label>
                        <div>
                            <input type="text" maxlength="13" name="mobile_no" id="txt_mobile_no" class="form-control" />
                        </div>
                    </div>

                    <div class="form-group col-sm-12">
                        <div class="checkbox" style="margin:8px 0 0 0">
                            <label><input type="checkbox" id="chk_not_employee"> العضو ليس موظفاً بالشركة</label>
                        </div>
                    </div>

                    <div class="form-group col-sm-12" id="grp_emp_select">
                        <label class="control-label">الموظف <span class="text-danger">*</span></label>
                        <div>
                            <select name="emp_select" id="sel_emp" class="form-control sel2-modal">
                                <option value="">- اختر موظف -</option>
                                <?php foreach ($emp_no_cons as $row): ?>
                                    <option value="<?= $row['EMP_NO'] ?>"
                                        data-name="<?= htmlspecialchars($row['EMP_NAME'], ENT_QUOTES) ?>">
                                        <?= $row['EMP_NO'] . ' - ' . $row['EMP_NAME'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div id="grp_manual" style="display:none">
                        <div class="form-group col-sm-2">
                            <label class="control-label">رقم وظيفي</label>
                            <div>
                                <input type="text" value="0" name="emp_no_manual" id="txt_emp_no_manual" class="form-control" />
                            </div>
                        </div>
                        <div class="form-group col-sm-4">
                            <label class="control-label">رقم الهوية</label>
                            <div>
                                <input type="text" name="national_id" id="txt_national_id" class="form-control" />
                            </div>
                        </div>
                        <div class="form-group col-sm-6">
                            <label class="control-label">اسم العضو <span class="text-danger">*</span></label>
                            <div>
                                <input type="text" name="name_manual" id="txt_name_manual" class="form-control" />
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="emp_no" id="h_emp_no" />
                    <input type="hidden" name="name" id="h_name" />

                    <div class="form-group col-sm-12">
                        <label class="control-label">ملاحظات</label>
                        <div>
                            <textarea name="notes" id="txt_notes" rows="2" class="form-control"></textarea>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" data-action="submit" class="btn btn-primary">حفظ البيانات</button>
                    <button type="button" onclick="javascript:group_modal_close();" class="btn btn-default" data-dismiss="modal">إغلاق</button>
                </div>
            </form>
        </div>
    </div>
</div>


<?php
$scripts = <<<SCRIPT
    <script>

        var GET_URL            = '{$get_url}';
        var CREATE_URL         = '{$create_url}';
        var EDIT_URL           = '{$edit_url}';
        var DELETE_URL         = '{$delete_url}';
        var GET_ACTIVITIES_URL = '{$get_activities_url}';

        $(function(){
            $('.sel2').select2({ placeholder: '-الكل-', allowClear: true, width: '100%' });

            $('#sel_f_program_master').on('change', function(){
                var pid = $(this).val();
                f_load_activities(pid);
                update_f_program_no();
            });
            $('#sel_f_activity').on('change', update_f_program_no);
            $('#sel_f_program_type').on('change', update_f_program_no);
        });

        function f_load_activities(program_id){
            $('#sel_f_activity').empty().append('<option value=""></option>');
            if(!program_id) return;
            get_data(GET_ACTIVITIES_URL + '/' + program_id, {}, function(data){
                var arr = (typeof data === 'string') ? JSON.parse(data) : data;
                $.each(arr, function(i, row){
                    $('#sel_f_activity').append('<option value="'+row.ACTIVITY_ID+'">'+row.ACTIVITY_NAME+'</option>');
                });
            });
        }

        function update_f_program_no(){
            var pt  = $('#sel_f_program_type').val();
            var pid = $('#sel_f_program_master').val();
            var aid = $('#sel_f_activity').val();
            if(pt == '2') $('#h_f_program_no').val(aid || '');
            else if(pt == '1') $('#h_f_program_no').val(pid || '');
            else $('#h_f_program_no').val('');
        }

        function values_search(){
            update_f_program_no();
            return {
                f_program_type: $('#sel_f_program_type').val() || '',
                f_program_no:   $('#h_f_program_no').val()     || '',
                f_group_type:   $('#sel_f_group_type').val()   || '',
                f_emp_no:       $('#txt_f_emp_no').val()       || '',
                f_name:         $('#txt_f_name').val()         || ''
            };
        }

        function search(){
            get_data('{$get_page_url}', values_search(), function(data){
                $('#container').html(data);
            }, 'html');
        }

        function clear_form(){
            clearForm($('#{$TB_NAME}_form'));
            $('.sel2').select2('val', '');
            $('#h_f_program_no').val('');
        }

        function init_modal_select2(){
            $('#groupModal .sel2-modal').each(function(){
                if(!$(this).data('select2')){
                    $(this).select2({
                        placeholder: '-اختر-',
                        allowClear: true,
                        width: '100%',
                        dropdownParent: $('#groupModal')
                    });
                }
            });
        }

        function open_group_modal(row){
            row = row || {};
            $('#group_modal_title').text(row.SER ? 'تعديل عضو' : 'إضافة عضو');

            $('#group_form')[0].reset();
            $('#groupModal .sel2-modal').val('').trigger('change');
            $('#h_ser').val(row.SER || '');
            $('#chk_not_employee').prop('checked', false);
            toggle_employee_mode();
           
            if(row.SER){
                $('#sel_program_type').val(row.PROGRAM_TYPE);
                 
                if(row.PROGRAM_TYPE == '2'){
                    get_data(GET_ACTIVITIES_URL + '/' + (row.MASTER_PROGRAM_ID || 0), {}, function(data){
                        var arr = (typeof data === 'string') ? JSON.parse(data) : data;
                        var sel = $('#sel_activity').empty().append('<option value="">-اختر-</option>');
                        $.each(arr, function(i, r){
                            sel.append('<option value="'+r.ACTIVITY_ID+'">'+r.ACTIVITY_NAME+'</option>');
                        });
                        sel.val(row.PROGRAM_NO);
                    });
                    $('#sel_program_master').val(row.MASTER_PROGRAM_ID || '');
                } else {
                    $('#sel_program_master').val(row.PROGRAM_NO);
                }

                toggle_program_type();

                if(parseInt(row.EMP_NO) > 0){
                    $('#chk_not_employee').prop('checked', false);
                    $('#sel_emp').val(row.EMP_NO);
                } else {
                    $('#chk_not_employee').prop('checked', true);
                    $('#txt_emp_no_manual').val(row.EMP_NO || 0);
                    $('#txt_national_id').val(row.ID || '');
                    $('#txt_name_manual').val(row.NAME || '');
                }
                toggle_employee_mode();

                $('#sel_group_type').val(row.GROUP_TYPE);
                $('#txt_email').val(row.EMAIL || '');
                $('#txt_mobile_no').val(row.MOBILE_NO || '');
                $('#txt_notes').val(row.NOTES || '');
            }

            $('#groupModal').modal('show');
        }

        $('#groupModal').on('shown.bs.modal', function(){
            init_modal_select2();
            $('#groupModal .sel2-modal').each(function(){
                var v = $(this).val();
                if(v) $(this).trigger('change.select2');
            });
        });

        function group_modal_close(){
            $('#groupModal').modal('hide');
        }

        function toggle_program_type(){
            var pt = $('#sel_program_type').val();
            if(pt == '2'){
                $('#grp_activity').show();
                $('#program_master_group').show();
            } else if(pt == '3'){
                $('#program_master_group').hide();
                $('#grp_activity').hide();
                $('#sel_activity').val('');
            } else {
                $('#grp_activity').hide();
                $('#sel_activity').val('');
                $('#program_master_group').show();
            }
        }

        function toggle_employee_mode(){
            if($('#chk_not_employee').is(':checked')){
                $('#grp_emp_select').hide();
                $('#grp_manual').show();
                $('#sel_emp').val('');
            } else {
                $('#grp_emp_select').show();
                $('#grp_manual').hide();
                $('#txt_emp_no_manual').val('0');
                $('#txt_national_id').val('');
                $('#txt_name_manual').val('');
            }
        }

        $(document).on('change', '#sel_program_type', toggle_program_type);
        $(document).on('change', '#sel_program_master', function(){
            var pid = $(this).val();
            $('#sel_activity').empty().append('<option value="">-اختر-</option>');
            if(!pid) return;
            get_data(GET_ACTIVITIES_URL + '/' + pid, {}, function(data){
                var arr = (typeof data === 'string') ? JSON.parse(data) : data;
                $.each(arr, function(i, r){
                    $('#sel_activity').append('<option value="'+r.ACTIVITY_ID+'">'+r.ACTIVITY_NAME+'</option>');
                });
            });
        });
        $(document).on('change', '#chk_not_employee', toggle_employee_mode);

        $('button[data-action="submit"]').click(function(e){
            e.preventDefault();

            var pt = $('#sel_program_type').val();
            if(pt == '2'){
                $('#h_program_no').val($('#sel_activity').val());
                if(!$('#sel_activity').val()){ danger_msg('تحذير..', 'اختر النشاط'); return; }
            } else {
                $('#h_program_no').val($('#sel_program_master').val());
            }

            if($('#chk_not_employee').is(':checked')){
                $('#h_emp_no').val($('#txt_emp_no_manual').val() || 0);
                $('#h_name').val($('#txt_name_manual').val());
                if(!$('#txt_name_manual').val()){ danger_msg('تحذير..', 'أدخل اسم العضو'); return; }
            } else {
                var emp = $('#sel_emp').val();
                if(!emp){ danger_msg('تحذير..', 'اختر الموظف'); return; }
                $('#h_emp_no').val(emp);
                $('#h_name').val($('#sel_emp option:selected').data('name') || '');
            }

            var form = $(this).closest('form');
            var ser  = $('#h_ser').val();
            form.attr('action', ser ? EDIT_URL : CREATE_URL);

            $(this).attr('disabled', 'disabled');
            ajax_insert_update(form, function(data){
                if(parseInt(data) >= 1){
                    success_msg('رسالة', 'تم حفظ البيانات بنجاح ..');
                    $('#groupModal').modal('hide');
                    search();
                } else {
                    danger_msg('تحذير..', data);
                }
            }, 'html');

            setTimeout(function(){ $('button[data-action="submit"]').removeAttr('disabled'); }, 3000);
        });

        function delete_group(ser){
            if(confirm('هل أنت متأكد من الحذف؟')){
                get_data(DELETE_URL, {ser: ser}, function(data){
                    if(data == 1){
                        success_msg('تم الحذف بنجاح..!');
                        search();
                    } else {
                        danger_msg('لم يتم الحذف..!!');
                    }
                });
            }
        }

        function edit_group(ser){
            get_data(GET_URL + '/' + ser, {}, function(data){
                var row = (typeof data === 'string') ? JSON.parse(data) : data;
                open_group_modal(row);
            });
        }

    </script>
SCRIPT;
sec_scripts($scripts);
?>