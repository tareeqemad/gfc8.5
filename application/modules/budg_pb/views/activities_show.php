<?php

$MODULE_NAME= 'budg_pb';
$TB_NAME= 'activities';
$back_url=base_url("$MODULE_NAME/$TB_NAME/index");
$post_url= base_url("$MODULE_NAME/$TB_NAME/".($action == 'index'?'create':$action));
$get_url= base_url("$MODULE_NAME/$TB_NAME/get");

$gfc_domain= gh_gfc_domain();

$isCreate =isset($master_tb_data) && count($master_tb_data)  > 0 ?false:true;
$HaveRs = (!$isCreate)? true:false;
$rs=$isCreate ?array() : $master_tb_data[0];

?>

<div class="row">
    <div class="toolbar">
        <div class="caption"><?=$title.(($HaveRs)?'':'')?></div>
        <ul>
            <?php if( 0 and HaveAccess($back_url)):  ?><li><a  href="<?= $back_url ?>"><i class="icon icon-reply"></i> </a> </li><?php  endif; ?>
        </ul>
    </div>
</div>

<div class="form-body">

    <div id="msg_container"></div>
    <div id="container">
        <form class="form-vertical" id="<?=$TB_NAME?>_form" method="post" action="<?=$post_url?>" role="form" novalidate="novalidate">
            <div class="modal-body inline_form">

                <div class="form-group col-sm-1">
                    <label class="control-label">رقم النشاط </label>
                    <div>
                        <input type="text" value="<?=$HaveRs?$rs['ACTIVITY_ID']:''?>" readonly id="txt_activity_id" class="form-control" />
                        <?php if (( isset($can_edit)?$can_edit:false)) : ?>
                            <input type="hidden" value="<?=$HaveRs?$rs['ACTIVITY_ID']:''?>" name="activity_id" id="h_activity_id" />
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group col-sm-1">
                    <label class="control-label">رقم البرنامج </label>
                    <div>
                        <input type="text" value="<?=$HaveRs?$rs['PROGRAM_ID']:$program_id?>" readonly id="txt_program_id" class="form-control" />
                        <?php if (( 1)) : ?>
                            <input type="hidden" value="<?=$HaveRs?$rs['PROGRAM_ID']:$program_id?>" name="program_id" id="h_program_id" />
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group col-sm-4">
                    <label class="control-label">اسم النشاط</label>
                    <div>
                        <input required type="text" value="<?=$HaveRs?$rs['ACTIVITY_NAME']:""?>" name="activity_name" id="txt_activity_name" class="form-control" />
                    </div>
                </div>

                <div class="form-group col-sm-6">
                    <label class="control-label">وصف النشاط</label>
                    <div>
                        <input type="text" value="<?=$HaveRs?$rs['ACTIVITY_DESCRIPTION']:""?>" name="activity_description" id="txt_activity_description" class="form-control" />
                    </div>
                </div>

            </div>


            <div class="modal-footer">
                <?php if ( !HaveAccess($post_url) && ($isCreate || ( isset($can_edit)?$can_edit:false) )  ) : ?>
                    <button type="submit" data-action="submit" class="btn btn-primary">حفظ البيانات</button>
                <?php endif; ?>
            </div>

    </form>
</div>
</div>


<?php
$scripts = <<<SCRIPT
<script>

    //reBind();
    
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

if($isCreate){
    $scripts = <<<SCRIPT
    {$scripts}

    function clear_form(){
        clearForm($('#{$TB_NAME}_form'));
    }

    </script>
SCRIPT;

}else{ // get or edit
    $scripts = <<<SCRIPT
    {$scripts}


    </script>
SCRIPT;
}

sec_scripts($scripts);

?>
