<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: lanakh
 * Date: 29/03/18
 * Time: 12:12 م
 */
$MODULE_NAME = 'planning';
$TB_NAME = 'planning_unit';
$P_Stratgic = 'StrategicPlan';
$isCreate = isset($s_objective) && count($s_objective)  > 0 ? false : true;
$delete_url_details = base_url("$MODULE_NAME/$TB_NAME/delete_annual_objective");
$HaveRs1 = (count($s_objective)  > 0) ? true : false;
$child_count = 0;
$father_ref_id = 0;
$adopt_url = base_url("$MODULE_NAME/$TB_NAME/adopt_");

$isCreate = isset($child_objective) && count($child_objective)  > 0 ? true : false;

/*$delete_url_details= base_url("$MODULE_NAME/$TB_NAME/delete_details");
$child_count_distrbute=0;
*/

$save_part_url = base_url("$MODULE_NAME/$TB_NAME/create_edit_annual_goals");

/*echo '<pre>';

 /*echo '<pre>';
print_r($s_objective);
echo '</pre>';*/
/*
 echo '<pre>';
print_r($details);
echo '</pre>';*/
//die;
//echo $id;
?>

<div class="tb_container">
    <table class="table" id="details_tb1" data-container="container" align="center">
        <thead>
            <tr>

                <th style="width:20%">الهدف الإستراتيجي</th>
                <th style="width:4%" hidden>رمز الهدف السنوي</th>
                <th style="width:20%">اسم الهدف السنوي</th>
                <th style="width:3%" hidden>الوزن النسبي</th>
                <!--<th style="width: 3%">اعتماد</th>-->
                <!-- <th style="width: 3%">حذف</th> -->

                <?php
                if ($refrence != $P_Stratgic) {
                ?>
                    <?php if (HaveAccess($delete_url_details) && ($HaveRs1)) :
                        if ($HaveRs1) {
                    ?>
                            <th style="width: 3%">حذف</th>
                    <?php }
                    endif; ?>
                <?php } ?>
            </tr>
        </thead>
        <tbody>
            <?php if (count($s_objective) <= 0) { /*echo 1;*/ ?>
                <tr>

                    <td>
                        <select name="id_father_name[]" data-val="true" data-val-required="حقل مطلوب" id="dp_id_father_name_<?= $child_count ?>" class="form-control">
                            <option></option>
                            <?php foreach ($child_objective as $row20) : ?>
                                <option value="<?= $row20['ID'] ?>"><?= $row20['ID_NAME'] ?></option>

                            <?php endforeach; ?>

                        </select>
                        <input type="hidden" name="h_count[]" id="h_count_<?= $child_count ?>" />
                    </td>
                    <td hidden>
                        <input type="text" data-val="true" data-val-required="حقل مطلوب" name="id_label[]" id="txt_id_label_<?= $child_count ?>" class="form-control" dir="rtl">
                        <span class="field-validation-valid" data-valmsg-for="id_label[]" data-valmsg-replace="true"></span>
                        <input type="hidden" data-val="true" data-val-required="حقل مطلوب" name="id[]" id="txt_id_<?= $child_count ?>" value="0" class="form-control">
                        <input type="hidden" data-val="true" data-val-required="حقل مطلوب" name="plan_no[]" id="txt_plan_no_<?= $child_count ?>" value="<?= $plan; ?>" class="form-control">
                        <input type="hidden" data-val="true" data-val-required="حقل مطلوب" name="id_father_ref[]" id="txt_id_father_ref_<?= $child_count ?>" value="<?= $id; ?>" class="form-control"> -->
                        <input type="hidden" name="h_count[]" id="h_count_<?= $child_count ?>" />
                    </td>

                    <td>
                        <input type="text" data-val="true" data-val-required="حقل مطلوب" name="id_name[]" id="txt_id_name_<?= $child_count ?>" class="form-control" dir="rtl">
                        <span class="field-validation-valid" data-valmsg-for="id_name[]" data-valmsg-replace="true"></span>
                    </td>
                    <td hidden>
                        <input class="form-control" name="weight[]" id="txt_weight_<?= $child_count ?>" data-val="true" data-val-required="حقل مطلوب" />
                        <span class="field-validation-valid" data-valmsg-for="weight[]" data-valmsg-replace="true"></span>

                    </td>
                    <?php
                    if ($refrence != $P_Stratgic) {
                    ?>
                        <?php if (HaveAccess($delete_url_details) && ($HaveRs1)) :
                            if ($HaveRs1) {
                        ?>
                                <td>


                                </td>
                        <?php }
                        endif; ?>
                    <?php } ?>
                    <!-- <td>

                    <button type="button" id="btn_save_<?= $child_count ?>" class="btn btn-primary" name="save[]">حفظ</button>
                </td>
                <td>
                    <button type="button" id="btn_active_<?= $child_count ?>" class="btn btn-warning hidden" data-ser1='<?= @$row['SEQ'] ?>'  name="active[]">الاهداف الاستراتيجية</button>
                </td>

                <td>
                    <button type="button" id="btn_adopt_<?= $child_count ?>" class="btn btn-info hidden" name="adopt[]">اعتماد</button>

                </td>-->

                </tr>

                <?php
                $child_count++;
            } else if (count($s_objective) > 0) {
                //echo 2;
                $child_count = 0;
                count($s_objective);
                foreach ($s_objective as $row10) {
                    $father_ref_id = $row10['ID_FATHER_REF'];

                ?>

                    <tr <?php if ($row10['INSERT_USER'] != $this->user->id) echo 'style="background-color: #FFF9E6;"'; ?>>

                        <td>
                            <select name="id_father_name[]" data-val="true" data-val-required="حقل مطلوب" id="dp_id_father_name_<?= $child_count ?>" class="form-control">
                                <option></option>
                                <?php foreach ($child_objective as $row20) : ?>
                                    <option value="<?= $row20['ID'] ?>" <?PHP if ($row20['ID'] == $row10['ID_FATHER']) {
                                                                            echo " selected";
                                                                        } ?>><?= $row20['ID_NAME'] ?></option>

                                <?php endforeach; ?>

                            </select>
                            <input type="hidden" name="h_count[]" id="h_count_<?= $child_count ?>" />
                        </td>
                        <td hidden>
                            <input type="text" data-val="true" data-val-required="حقل مطلوب" name="id_label[]" id="txt_id_label_<?= $child_count ?>" value="<?= $row10['ID_LABEL'] ?>" class="form-control" dir="rtl">
                            <span class="field-validation-valid" data-valmsg-for="id_label[]" data-valmsg-replace="true"></span>
                            <input type="hidden" data-val="true" data-val-required="حقل مطلوب" name="id[]" id="txt_id_<?= $child_count ?>" value="<?= $row10['ID'] ?>" class="form-control">
                            <input type="hidden" data-val="true" data-val-required="حقل مطلوب" name="plan_no[]" id="txt_plan_no_<?= $child_count ?>" value="<?= $plan; ?>" class="form-control">
                            <input type="hidden" data-val="true" data-val-required="حقل مطلوب" name="id_father_ref[]" id="txt_id_father_ref_<?= $child_count ?>" value="<?= $id; ?>" class="form-control">
                            <input type="hidden" name="h_count[]" id="h_count_<?= $child_count ?>" value="<?= $child_count ?>" />
                        </td>

                        <td>
                            <input type="text" data-val="true" data-val-required="حقل مطلوب" name="id_name[]" id="txt_id_name_<?= $child_count ?>" value="<?= $row10['ID_NAME'] ?>" class="form-control" dir="rtl">
                            <span class="field-validation-valid" data-valmsg-for="id_name[]" data-valmsg-replace="true"></span>
                        </td>
                        <td hidden>
                            <input class="form-control" name="weight[]" id="txt_weight_<?= $child_count ?>" value="<?= $row10['WEIGHT'] ?>" data-val="true" data-val-required="حقل مطلوب" />
                            <span class="field-validation-valid" data-valmsg-for="weight[]" data-valmsg-replace="true"></span>
                        </td>
                        <!-- <td>
                    <a onclick="javascript:delete_row_del('<?= $row10['ID'] ?>','<?= $row10['ID_NAME'] ?>');"  href='javascript:;'><i class='glyphicon glyphicon-remove'></i></a>


                </td> -->

                        <?php
                        if ($refrence != $P_Stratgic) {
                        ?>
                            <?php if (HaveAccess($delete_url_details) && ($HaveRs1)) :
                                if ($HaveRs1) {
                            ?>

                                    <td>
                                        <a onclick="javascript:delete_row_del('<?= $row10['ID'] ?>','<?= $row10['ID_NAME'] ?>');" href='javascript:;'><i class='glyphicon glyphicon-remove'></i></a>


                                    </td>
                            <?php }
                            endif; ?>
                        <?php } ?>

                    </tr>
            <?php
                    $child_count++;
                }
            }

            ?>
        </tbody>

        <tfoot>
            <tr>
                <?php
                if ($refrence != $P_Stratgic) {

                ?>
                    <?php if (HaveAccess($save_part_url) && ($isCreate || ($child_objective[0]['ADOPT'] == 1 and isset($can_edit) ? $can_edit : false))) : ?>

                        <th>
                            <a onclick="javascript:add_row(this,'input:text,input:hidden[name^=id],select',false);" onfocus="javascript:add_row(this,'input:text,input:hidden[name^=id],select',false);" href="javascript:;" class="new"><i class="glyphicon glyphicon-plus"></i>جديد</a>
                        </th>
                    <?php endif; ?>
                <?php } ?>
                <th></th>

                <th hidden>
                    الاجمالي الكلي لاوزان
                </th>
                <th id="total_weight" hidden></th>
                <?php
                if ($refrence != $P_Stratgic) {
                ?>

                    <?php if (HaveAccess($delete_url_details) && ($HaveRs1)) :
                        if ($HaveRs1) {
                    ?>
                            <th></th>
                    <?php }
                    endif; ?>
                <?php } ?>
            </tr>

        </tfoot>
    </table>
</div>




</div>
<?php
if ($refrence != $P_Stratgic) {
?>
    <div class="modal-footer">
        <?php /*if ( $isCreate or $child_objective[0]['ADOPT'] == 1) : */ ?>
        <?php if (HaveAccess($save_part_url)) : ?>
            <button type="button" data-action="submit" class="btn btn-primary save_part">حفظ البيانات</button>

        <?php endif; ?>

        <?php if (HaveAccess($adopt_url . '10') and count($s_objective) > 0 /*and !$isCreate and $adopt == 1 and $ser != ""*/) : ?>
            <button type="button" id="btn_adopt_10" onclick='adopt_(10);'
                class="btn btn-success btn_adopt_class">
                اعتماد المعد
            </button>
        <?php endif; ?>

        <!--<button type="button" id="btn_adopt__10" onclick='adopt_("_10");'
                            class="btn btn-danger btn_adopt_class">
                       ارجاع الكل
                    </button>-->
        <?php if (HaveAccess($adopt_url . '20') and count($s_objective) > 0/*and !$isCreate and $adopt == 10 and $ser != ""*/) : ?>
            <button type="button" id="btn_adopt_20" onclick='adopt_(20);'
                class="btn btn-success btn_adopt_class">
                اعتماد
                التخطيط
            </button>
        <?php endif; ?>
        <?php if (HaveAccess($adopt_url . '_20') and count($s_objective) > 0 /*and !$isCreate and $rs['ADOPT'] == 20 and $ser != ""*/) : ?>
            <button type="button" id="btn_adopt__20" onclick='adopt_("_20");'
                class="btn btn-danger btn_adopt_class">
                الغاء
                الاعتماد التخطيط
            </button>
        <?php endif; ?>



        <!-- <button type="button" data-action="submit" class="btn btn-info save_part">اعتماد</button>-->
        <?php /*endif;*/ ?>

    </div>
<?php } ?>


<?php
$scripts = <<<SCRIPT
<script type="text/javascript">
///////////////
function delete_row_del(id, name) {
  if (confirm(' هل تريد بالتأكيد حذف  '+name+' ؟!!')) {

        var values = {id: id};
        get_data('{$delete_url_details}', values, function (data) {

            if (data == 1) {

                success_msg('تمت عملية الحذف بنجاح');
                get_to_link(window.location.href);
            }

            else {
                danger_msg('لم يتم الحذف', data);
            }

        }, 'html');

    }

}
    //////////////////////////////////////////////////////////////
reBind_pram(0);
        calcall();

$('.save_part').on('click',  function (e) {

/*

                                      if($('#total_weight').text()!=100)
{
 danger_msg('لم يتم الحفظ يجب ان يكون المجوع الكلي للاوزان = 100');
}
else
{*/

        var url = '{$save_part_url}';
        var tbl = '#details_tb1';
        var container = $('#' + $(tbl).attr('data-container'));
         var id_label = [];
         var id = [];
         var plan_no = [];
         var id_name = [];
         var id_father = [];
         var id_father_ref=[];
         var weight =[];

        $('input[name="id_name[]"]').each(function (i) {
        if($(this).closest('tr').find('input[name="id_name[]"]').val() !='')
        {
          id_label[i]=$(this).closest('tr').find('input[name="id_label[]"]').val();
          id[i]=$(this).closest('tr').find('input[name="id[]"]').val();
          plan_no[i]={$plan};
          id_name[i]=$(this).closest('tr').find('input[name="id_name[]"]').val();
          id_father[i]=$(this).closest('tr').find('select[name="id_father_name[]"]').val();
          id_father_ref[i]={$id};
          weight[i]=$(this).closest('tr').find('input[name="weight[]"]').val();

        }
        });

      if(id_name.length > 0){
      get_data(url,{id:id,id_label:id_label,plan_no:plan_no,id_name:id_name,id_father:id_father,id_father_ref:id_father_ref,weight:weight},function(data){
             if(data>=1)
             {
             
            success_msg('رسالة','تم حفظ البيانات بنجاح ..');
             get_to_link(window.location.href);



             }

                 else
                    {
                    alert(data);
                  danger_msg('لم يتم الحفظ', data);
                get_to_link(window.location.href);
                }


            });


        }else
            alert('لا يوجد سجلات ممكن حفظها');
       // }

        });

        function calcall() {

    var total_weight = 0;

    $('input[name="weight[]"]').each(function () {

        var weight = $(this).closest('tr').find('input[name="weight[]"]').val();
        total_weight += Number(weight);
        if(Number(total_weight)>100)
                {
                    danger_msg('لقد تجاوزت الحد المسموح');
                    $(this).closest('tr').find('input[name="weight[]"]').val(0);
                }
                else

        $('#total_weight').text(isNaNVal(Number(total_weight)));
    });



}
function reBind_pram(cnt){
$('.select2-container',$('#details_tb1')).remove();

$('select[name="id_father_name[]"]').select2().on('change',function(){

       //  checkBoxChanged();

        });

 $('input[name="weight[]"]').keyup(function (e) {
 calcall();
    });
    }

    /////////
///////////////////////////////////////////////////////////////////
    var btn__= '';
    $('#btn_adopt_0,#btn_adopt_10,#btn_adopt__10,#btn_adopt_20,#btn_adopt__20').click( function(){
        btn__ = $(this);
    });
///////////////////////////////////////////////////////////////////
    function adopt_(no){
       
        var msg= 'هل تريد الاعتماد ؟!';
      
        if(no=='_10') msg= '!!هل تريد بالتأكيد ارجاع كافة الأهداف السنوية للمعد ؟لا يمكن التراجع عن هذه العملية';
        if(no=='_20')msg= '!!هل تريد بالتأكيد الغاء اعتماد الأهداف السنوية ؟لا يمكن التراجع عن هذه العملية';
        
        if(confirm(msg)){

            var values= {ser: "{$plan}",father_id_ref:"{$father_ref_id}"};
            
            get_data('{$adopt_url}'+no, values, function(ret){
                if(ret==1){
                    success_msg('رسالة','تمت العملية بنجاح..');
                    $('button').attr('disabled','disabled');
                    btn__ = '';

                    setTimeout(function(){
                        get_to_link(window.location.href);
                    },1000);
                }else{
                    danger_msg('تحذير..',data);
                }
            }, 'html');
        }
    }

</script>

SCRIPT;

sec_scripts($scripts);

?>