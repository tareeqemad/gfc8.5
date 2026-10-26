<?php
$MODULE_NAME= 'budg_pb';
$TB_NAME= 'programs';

$get_url =base_url("$MODULE_NAME/$TB_NAME/get");
$create_url =base_url("$MODULE_NAME/$TB_NAME/create");
$edit_url =base_url("$MODULE_NAME/$TB_NAME/edit");
$get_page_url = base_url("$MODULE_NAME/$TB_NAME/get_page");
$delete_unit_url = base_url("$MODULE_NAME/$TB_NAME/delete_unit");
$members_box_url = base_url("$MODULE_NAME/budget_groups/public_members_box");

if(HaveAccess($edit_url))
    $edit= 'edit';
else
    $edit= '';

echo AntiForgeryToken();
?>

<div class="row">
    <div class="toolbar">
        <div class="caption"><?= $title ?></div>

        <ul>
            <?php if(1 || HaveAccess($create_url)):  ?><li><a  href="<?=$create_url?>"><i class="glyphicon glyphicon-plus"></i>برنامج جديد </a> </li><?php endif; ?>
        </ul>
    </div>

    <div class="form-body">

        <form style="display: none"  class="form-vertical" id="<?=$TB_NAME?>_form" >
            <div class="modal-body inline_form">

                <div class="form-group col-sm-1">
                    <label class="control-label">رقم السند</label>
                    <div>
                        <input type="text" name="ser" id="txt_ser" class="form-control">
                    </div>
                </div>

            </div>
        </form>


        <div style="display: none"  class="modal-footer">
            <button type="button" onclick="javascript:search();" class="btn btn-success"> إستعلام</button>
            <button type="button" onclick="$('#page_tb').tableExport({type:'excel',escape:'false'});" class="btn btn-success">  اكسل </button>
            <button type="button" onclick="javascript:clear_form();"  class="btn btn-default"> تفريغ الحقول</button>
        </div>

        <div id="msg_container"></div>

        <div id="container">
            <?=modules::run($get_page_url, $page);?>
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
            <form class="form-horizontal" id="units_from" method="post" action="<?=base_url('budg_pb/programs/create_unit')?>" role="form" novalidate="novalidate">
                <div class="modal-body">

                    <div class="form-group">
                        <label class="col-sm-3 control-label"> الوحدة التنظيمية</label>
                        <div class="col-sm-8">
                            <input type="text" data-val="true"  data-val-required="حقل مطلوب" name="unit" id="txt_unit" class="form-control easyui-combotree" data-options="url:'<?= base_url('settings/gcc_structure/public_get_structure_json')?>',method:'get',animate:true,lines:true,required:true">
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

<?php
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
            $('#unitsModal').modal();
        }
       
        $('button[data-action="submit"]').click(function(e){
            e.preventDefault();
            var form = $(this).closest('form');
    
            ajax_insert_update(form,function(data){
                //var obj = jQuery.parseJSON(data);
                success_msg('رسالة','تم حفظ البيانات بنجاح ..');
            },"json");
    
        });
        
        function unit_modal_close(){
            get_to_link(window.location.href);
        }

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
