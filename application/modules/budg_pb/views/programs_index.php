<?php
$MODULE_NAME = 'budg_pb';
$TB_NAME = 'programs';

$get_url = base_url("$MODULE_NAME/$TB_NAME/get");
$create_url = base_url("$MODULE_NAME/$TB_NAME/create");
$edit_url = base_url("$MODULE_NAME/$TB_NAME/edit");
$get_page_url = base_url("$MODULE_NAME/$TB_NAME/get_page");
$delete_unit_url = base_url("$MODULE_NAME/$TB_NAME/delete_unit");
$members_box_url = base_url("$MODULE_NAME/budget_groups/public_members_box");

if (HaveAccess($edit_url))
    $edit = 'edit';
else
    $edit = '';

echo AntiForgeryToken();
?>

<div class="row">
    <div class="toolbar">
        <div class="caption"><?= $title ?></div>

        <ul>
            <?php if (1 || HaveAccess($create_url)):  ?><li><a href="<?= $create_url ?>"><i class="glyphicon glyphicon-plus"></i>برنامج جديد </a> </li><?php endif; ?>
        </ul>
    </div>

    <div class="form-body">

        <form style="display: none" class="form-vertical" id="<?= $TB_NAME ?>_form">
            <div class="modal-body inline_form">

                <div class="form-group col-sm-1">
                    <label class="control-label">رقم السند</label>
                    <div>
                        <input type="text" name="ser" id="txt_ser" class="form-control">
                    </div>
                </div>

            </div>
        </form>


        <div style="display: none" class="modal-footer">
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


<div class="modal fade" id="membersModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content _950">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="members_modal_title">أعضاء لجنة التخطيط والموازنة</h4>
            </div>
            <div class="modal-body">
                <div id="members_box_container">
                    <div class="text-center" style="padding:20px">جاري التحميل...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="unitsModal">
    <div class="modal-dialog">
        <div class="modal-content _750">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <h4 class="modal-title"> اضافة وحدة تنظيمية</h4>
            </div>
            <form class="form-horizontal" id="units_from" method="post" action="<?= base_url('budg_pb/programs/create_unit') ?>" role="form" novalidate="novalidate">
                <div class="modal-body">

                    <div class="form-group">
                        <label class="col-sm-3 control-label"> الوحدة التنظيمية</label>
                        <div class="col-sm-8">
                            <style>
                                .unit-spin {
                                    display: inline-block;
                                    animation: unit-spin 1s linear infinite;
                                }

                                @keyframes unit-spin {
                                    from {
                                        transform: rotate(0deg);
                                    }

                                    to {
                                        transform: rotate(360deg);
                                    }
                                }
                            </style>
                            <input type="hidden" name="unit" id="txt_unit">
                            <div id="unit_loading" style="padding:10px; color:#4a90e2;">
                                <span class="glyphicon glyphicon-refresh unit-spin"></span> جاري تحميل الوحدات التنظيمية...
                            </div>
                            <div id="unit_tree_box" style="display:none; max-height:260px; overflow:auto; border:1px solid #ccc; border-radius:3px; padding:6px; background:#fff;">
                                <ul id="unit_tree" style="margin:0; padding:0;"></ul>
                            </div>
                            <div id="unit_selected" style="margin-top:6px;"></div>
                            <span class="field-validation-valid" data-valmsg-for="unit" data-valmsg-replace="true"></span>
                        </div>
                    </div>

                    <input type="hidden" value="0" name="parent_type" id="h_parent_type" />
                    <input type="hidden" value="0" name="parent_id" id="h_parent_id" />

                </div>
                <div class="modal-footer">
                    <button type="submit" data-action="submit" class="btn btn-primary">حفظ البيانات</button>
                    <button type="button" onclick="javascript:unit_modal_close();" class="btn btn-default" data-dismiss="modal">إغلاق</button>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" id="groupProjectModal">
    <div class="modal-dialog">
        <div class="modal-content _750">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">اضافة مشروع</h4>
            </div>
            <form class="form-horizontal" id="group_project_form" method="post" action="<?= base_url('budg_pb/programs/create_group_project') ?>" role="form" novalidate="novalidate">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">اسم المشروع</label>
                        <div class="col-sm-8"><input type="text" name="project_name" class="form-control" data-val="true" data-val-required="حقل مطلوب"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">الوصف</label>
                        <div class="col-sm-8"><textarea name="project_description" class="form-control"></textarea></div>
                    </div>
                    <input type="hidden" name="program_id" id="h_gp_program_id" value="0" />
                    <input type="hidden" name="group_id" id="h_gp_group_id" value="0" />
                </div>
                <div class="modal-footer">
                    <button type="submit" data-action="submit-project" class="btn btn-primary">حفظ البيانات</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">إغلاق</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$units_tree_url = base_url('settings/gcc_structure/public_get_structure_json');
$scripts = <<<SCRIPT
    <script>

        function show_row_details(id){
            get_to_link('{$get_url}/'+id+'/{$page_act}');
        }
    
        function clear_form(){
            clearForm($('#{$TB_NAME}_form'));
            $('.sel2').select2('val','');
        }
        
        function unit_create(typ, id){
            $('#h_parent_type').val(typ);
            $('#h_parent_id').val(id);            
            unit_tree_reset();
            $('#unitsModal').modal();
        }

        var selected_unit_id = '';   // تخزين معرف الوحدة المختارة حالياً (واحدة فقط)
        var selected_unit_text = ''; // تخزين اسم الوحدة المختارة

        // تحديث الحقل المخفي والشاشة بالوحدة المختارة
        function unit_refresh_selected(){
            var box = $('#unit_selected');
            box.empty();
            
            if (selected_unit_id) {
                var chip = $('<span class="label label-primary"></span>').css({'display': 'inline-block', 'margin': '2px', 'padding': '4px 8px', 'font-size': '12px'}).text(selected_unit_text + ' ');
                var x = $('<a href="javascript:;" style="color:#fff; font-weight:bold;">&times;</a>').click(function(){
                    selected_unit_id = '';
                    selected_unit_text = '';
                    $('#unit_tree .unit-label').css({'background': '', 'color': '#333'});
                    unit_refresh_selected();
                });
                chip.append(x);
                box.append(chip);
            }
            $('#txt_unit').val(selected_unit_id);
        }

        function unit_tree_reset(){
            selected_unit_id = '';
            selected_unit_text = '';
            $('#unit_tree .unit-label').css({'background': '', 'color': '#333'});
            unit_refresh_selected();
        }

        // رسم شجرة الوحدات التنظيمية بالنقر المباشر (بدون Checkboxes وبوحدة واحدة فقط)
        function unit_tree_render(nodes, box, parent_id, depth) {
            $.each(nodes, function (i, node) {
                var li = $('<li></li>').css({
                    'list-style': 'none',
                    'margin': '3px 0'
                });

                var tog = $('<span></span>').css({
                    'display': 'inline-block',
                    'width': '18px',
                    'color': '#4a90e2'
                });

                // تصميم عنصر النص ليكون قابلاً للنقر والتحديد مباشرة
                var label = $('<span class="unit-label"></span>')
                    .css({
                        'cursor': depth > 0 ? 'pointer' : 'default',
                        'padding': '3px 6px',
                        'border-radius': '3px',
                        'font-weight': depth === 0 ? 'bold' : 'normal',
                        'display': 'inline-block'
                    })
                    .text(node.text);

                li.append(tog);

                // السماح بالنقر على المستويات الفرعية فقط (أو الجذر حسب الرغبة، هنا عمق > 0 كالسابق)
                if (depth > 0) {
                    label.on('click', function () {
                        // إزالة التحديد عن جميع العناصر في الشجرة
                        $('#unit_tree .unit-label').css({'background': '', 'color': '#333'});
                        
                        // تحديد العنصر الحالي
                        selected_unit_id = node.id;
                        selected_unit_text = node.text;
                        
                        label.css({'background': '#143956', 'color': '#fff'});
                        unit_refresh_selected();
                    });
                }

                li.append(label);

                var children = $.isArray(node.children) ? node.children : [];

                if (children.length > 0) {
                    var sub = $('<ul></ul>').css({
                        'display': 'block',
                        'margin': '0',
                        'padding': '0',
                        'padding-inline-start': '18px'
                    });

                    tog.html('<span class="glyphicon glyphicon-minus-sign"></span>');

                    tog.css('cursor', 'pointer').on('click', function () {
                        if (sub.is(':visible')) {
                            sub.hide();
                            tog.find('span').attr('class', 'glyphicon glyphicon-plus-sign');
                        } else {
                            sub.show();
                            tog.find('span').attr('class', 'glyphicon glyphicon-minus-sign');
                        }
                    });

                    unit_tree_render(children, sub, node.id, depth + 1);

                    li.append(sub);
                }

                box.append(li);
            });
        }

        // تحميل الهيكل التنظيمي مرة واحدة فقط عند فتح النافذة
        $(function () {
            $('#unit_loading').show();
            $('#unit_tree_box').hide();
            $('#unit_tree').empty();

            selected_unit_id = '';
            selected_unit_text = '';

            $.ajax({
                url: '{$units_tree_url}',
                dataType: 'text'
            }).done(function (res) {
                var nodes = [];

                try {
                    nodes = $.parseJSON(res);
                } catch (err) {
                    $('#unit_loading').html(
                        '<span style="color:#c00;">تعذر قراءة بيانات الهيكل التنظيمي</span>'
                    );
                    return;
                }

                if (!$.isArray(nodes)) {
                    nodes = nodes ? [nodes] : [];
                }

                if (nodes.length === 0) {
                    $('#unit_loading').html(
                        '<span style="color:#c00;">لا توجد وحدات تنظيمية</span>'
                    );
                    return;
                }

                unit_tree_render(nodes, $('#unit_tree'), null, 0);

                $('#unit_loading').hide();
                $('#unit_tree_box').show();

                unit_refresh_selected();
            }).fail(function () {
                $('#unit_loading').html(
                    '<span style="color:#c00;">تعذر تحميل الهيكل التنظيمي</span>'
                );
            });
        });

        // تحديث جدول البرامج مباشرة بعد الحفظ (بدون إعادة تحميل الصفحة)
        function refresh_programs_page(){
            $('#container').css('opacity', 0.5);
            get_data('{$get_page_url}/{$page}', {}, function(data){
                $('#container').html(data).css('opacity', 1);
            }, 'html');
        }
            
        $('button[data-action="submit"]').click(function(e){
            e.preventDefault();
            var form = $(this).closest('form');

            if ($('#txt_unit').val() == '') {
                danger_msg('الرجاء اختيار وحدة تنظيمية');
                return;
            }
    
            ajax_insert_update(form,function(data){
                //var obj = jQuery.parseJSON(data);
                success_msg('رسالة','تم حفظ البيانات بنجاح ..');
                $('#unitsModal').modal('hide');
                refresh_programs_page();
            },"json");
    
        });
        
        function unit_modal_close(){
            get_to_link(window.location.href);
        }

        function group_project_create(program_id, group_id){
            $('#h_gp_program_id').val(program_id);
            $('#h_gp_group_id').val(group_id);
            $('#groupProjectModal').modal();
        }

        $('button[data-action="submit-project"]').click(function(e){
            e.preventDefault();
            var form = $(this).closest('form');

            ajax_insert_update(form,function(data){
                success_msg('رسالة','تم حفظ البيانات بنجاح ..');
                $('#groupProjectModal').modal('hide');
                //get_to_link(window.location.href);
                refresh_programs_page();
            },"json");
        });

        function show_members(program_type, program_no, title){
            $('#members_modal_title').text(title);
            $('#members_box_container').html('<div class="text-center" style="padding:20px">جاري التحميل...</div>');
            $('#membersModal').modal('show');
            get_data('{$members_box_url}/' + program_type + '/' + program_no, {}, function(data){
                $('#members_box_container').html(data);
            }, 'html');
        }

        function delete_unit(typ, ser) {
            if(confirm("هل أنت متأكد من الحذف؟")) {
                get_data('{$delete_unit_url}',{ser:ser,parent_type:typ},function(data){
                 if(data==1 ){
                     success_msg('تم الحذف بنجاح..!');
                     $('#U_'+typ+'_'+ser).hide(500);
                 }else{
                     danger_msg('لم يتم الحذف..!!');
                    }
               });
            } // if confirm
        }
    </script>
SCRIPT;
sec_scripts($scripts);
?>