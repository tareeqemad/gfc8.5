<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: lanakh
 * Date: 16/09/26
 * Time: 12:15 م
 */
$MODULE_NAME= 'planning';
$TB_NAME= 'planning';
$isCreate =isset($target_details) && count($target_details)  > 0 ?false:true;
$count_target=0;
$extrct_months = $months; // نسخ البيانات

$months_values = array_map(
    'intval',
    array_column($extrct_months, 'MONTH')
);

$current_month = (int) date('n');

if (in_array($current_month, $months_values, true)) {
    $data_val = "true";
    $data_val_required = "";
    $readonly_ = "";

} else {
    $data_val = "false";
    $data_val_required = "حقل مطلوب";
    $readonly_ = "readonly";

}
?>
<div class="tb_container">
<table class="table" id="t_details_tb1" data-container="container"  align="center">
<thead>
<tr>
    <th style="width: 15%">اسم المؤشر</th>
    <th style="width: 10%">وحدة القياس</th>
    <th style="width: 10%">الصيغة</th>
    <th style="width: 8%">المستهف السنوي</th>
    <th style="width: 8%">القيمة المحققة</th>
    <th style="width: 8%">الشهر</th>
   
  
   
    <?php /*if ( HaveAccess($delete_url_details) && (!$isCreate )) :
    if($adopt==1)
    {
    ?>
        <th style="width: 3%">حذف</th>
    <?php }
    endif;*/ ?>
</tr>
</thead>

<tbody>
<?php if(count($target_details) <= 0) {  // ادخال ?>
    <tr>
        <td>
           <select name="kpi_ser[]" data-val="<?=$data_val;?>"  data-val-required="<?=$data_val_required;?>"  id="dp_kpi_ser_<?=$count_target?>" class="form-control" <?=$readonly_;?> >
                <option></option>
                <?php foreach($kpi as $kpi_row) :?>
                    <option value="<?= $kpi_row['INDECATOR_SER'] ?>" >
                        <?= $kpi_row['INDECATOR_NAME'] ?></option>
                <?php endforeach; ?>

            </select>
             <input  type="hidden" name="h_activities_plan_kpi[]"  id="h_activities_plan_kpi_<?=$count_target?>" data-val="false" data-val-required="<?=$data_val_required;?>" >
             <input type="hidden" name="t_seq_kpi[]" id="t_seq_kpi[]" value="0"  />
             <input type="hidden" name="h_kpi_count[]" id="h_kpi_count_<?=$count_target?>" value="<?=$count_target?>" />
            <span class="field-validation-valid" data-valmsg-for="kpi_ser[]" data-valmsg-replace="true"></span>
        </td>

        <td>
            <input type="text"  data-val="<?=$data_val;?>"  data-val-required="<?=$data_val_required;?>"   name="unit_name[]" id="txt_unit_name_<?=$count_target?>" class="form-control" dir="rtl" readonly >
            <span class="field-validation-valid" data-valmsg-for="target[]" data-valmsg-replace="<?=$data_val;?>"></span>
        </td>

        <td>
            <input type="text"  data-val="<?=$data_val;?>"  data-val-required="<?=$data_val_required;?>"   name="scal_name[]" id="txt_scal_name_<?=$count_target?>" class="form-control" dir="rtl" readonly >
            <span class="field-validation-valid" data-valmsg-for="scal_name[]" data-valmsg-replace="<?=$data_val;?>"></span>
        </td>
        <td>
		<input type="text"  data-val="<?=$data_val;?>"  data-val-required="<?=$data_val_required;?>"   name="kpi_target_year[]" id="txt_kpi_target_year_<?=$count_target?>" class="form-control" dir="rtl" <?=$readonly_;?>>
            <span class="field-validation-valid" data-valmsg-for="kpi_target_year[]" data-valmsg-replace="<?=$data_val;?>"></span>
        </td>
        <td>
		<input type="text"  data-val="<?=$data_val;?>"  data-val-required="<?=$data_val_required;?>"   name="kpi_val_month[]" id="txt_kpi_val_month_<?=$count_target?>" class="form-control" dir="rtl" <?=$readonly_;?>>
            <span class="field-validation-valid" data-valmsg-for="kpi_val_month[]" data-valmsg-replace="<?=$data_val;?>"></span>
        </td>
               <td>
              <select name="kpi_month[]" id="dp_kpi_month_<?=$count_target?>" data-curr="false" class="form-control"  data-val ="<?=$data_val;?>"   data-val-required="<?=$data_val_required;?>"  <?=$readonly_;?>>
                   <option  value="">-</option>
                 <?php foreach ($months as $i)  {?>
                    <option value="<?= $i['MONTH'] ?>"><?= months($i['MONTH']) ?></option>
                <?php } ?>

            </select>
           <span class="field-validation-valid" data-valmsg-for="kpi_month[]" data-valmsg-replace="<?=$data_val;?>"></span>
       </td>
       

       

        <?php /*if ( HaveAccess($delete_url_details) && (!$isCreate )) :
        if($adopt==1)
        {
        ?>
            <td>


            </td>
        <?php }
        endif; */?>
    </tr>
<?php
}else if(count($target_details) > 0) { // تعديل
    $count_target = -1;
    foreach($target_details as $kpi_rows10) {
        ++$count_target+1;

       

        ?>

        <tr>
       
        <td>
           <select name="kpi_ser[]" data-val="<?=$data_val;?>"  data-val-required="<?=$data_val_required;?>"  id="dp_kpi_ser_<?=$count_target?>" class="form-control" <?=$readonly_;?> >
                <option></option>
                <?php foreach($kpi as $kpi_row) :?>
                    <option value="<?= $kpi_row['INDECATOR_SER'] ?>" <?PHP if ($kpi_row['INDECATOR_SER']==$kpi_rows10['KPI_SER']){ echo " selected"; } ?> >
                        <?= $kpi_row['INDECATOR_NAME'] ?></option>
                <?php endforeach; ?>

            </select>
             <input  type="hidden" name="h_activities_plan_kpi[]"  id="h_activities_plan_kpi_<?=$count_target?>" data-val="false" data-val-required="required" value="<?=$kpi_rows10['ACTIVITIES_PLAN_SER']?>" <?=$readonly_;?>>
             <input type="hidden" name="t_seq_kpi[]" id="t_seq_kpi[]" value="<?=$kpi_rows10['SER']?>"  />
             <input type="hidden" name="h_kpi_count[]" id="h_kpi_count_<?=$count_target?>"/>
            <span class="field-validation-valid" data-valmsg-for="kpi_ser[]" data-valmsg-replace="true"></span>
        </td>
          <td>
            <input type="text"  data-val="<?=$data_val;?>"  data-val-required="<?=$data_val_required;?>"   name="unit_name[]" id="txt_unit_name_<?=$count_target?>" class="form-control" dir="rtl" value="<?=$kpi_rows10['UNIT_NAME']?>" readonly >
            <span class="field-validation-valid" data-valmsg-for="target[]" data-valmsg-replace="true"></span>
        </td>

        <td>
            <input type="text"  data-val="<?=$data_val;?>"  data-val-required="<?=$data_val_required;?>"   name="scal_name[]" id="txt_scal_name_<?=$count_target?>" class="form-control" dir="rtl" value="<?=$kpi_rows10['SCAL_NAME']?>" readonly >
            <span class="field-validation-valid" data-valmsg-for="scal_name[]" data-valmsg-replace="true"></span>
        </td>
        <td>
		<input type="text"  data-val="<?=$data_val;?>"  data-val-required="<?=$data_val_required;?>"   name="kpi_target_year[]" id="txt_kpi_target_year_<?=$count_target?>" class="form-control" value="<?=$kpi_rows10['KPI_TARGET_YEAR']?>" dir="rtl" <?=$readonly_;?>>
            <span class="field-validation-valid" data-valmsg-for="kpi_target_year[]" data-valmsg-replace="true"></span>
        </td>
        <td>
		<input type="text"  data-val="<?=$data_val;?>"  data-val-required="<?=$data_val_required;?>"   name="kpi_val_month[]" id="txt_kpi_val_month_<?=$count_target?>" class="form-control" value="<?=$kpi_rows10['KPI_VAL_MONTH']?>" dir="rtl" <?=$readonly_;?>>
            <span class="field-validation-valid" data-valmsg-for="kpi_val_month[]" data-valmsg-replace="true"></span>
        </td>

         <td>
			
          <select name="kpi_month[]" id="dp_kpi_month_<?=$count_target?>" data-curr="<?=$data_val;?>" class="form-control"  data-val ="<?=$data_val;?>"   data-val-required="<?=$data_val_required;?>" <?=$readonly_;?> >
                    <option  value="">-</option>
                    <?php foreach ($months as $i)  {?>
                        <option value="<?= $i['MONTH'] ?>" <?PHP if ($i['MONTH']==$kpi_rows10['KPI_MONTH']){ echo " selected"; } ?>><?= months($i['MONTH']) ?></option>
                    <?php } ?>

                </select>
                <span class="field-validation-valid" data-valmsg-for="kpi_month[]" data-valmsg-replace="<?=$data_val;?>"></span>

       </td>




            <?php /*if ( HaveAccess($delete_url_details) && (!$isCreate ) )  :
                if($adopt==1)
                {
                ?>

                <td>
                    <a onclick="javascript:delete_t_row_del('<?=$t_row['f_seq1']?>','<?=$t_row['ACTIVITY_NAME']?>');"  href='javascript:;'><i class='glyphicon glyphicon-remove'></i></a>


                </td>
            <?php } endif; */?>
        </tr>
    <?php

    }
}

?>
</tbody>

<tfoot>
<tr>
    <th></th>
    <th></th>
    <th></th>
    <th></th>
    <th></th>
    <th></th>


  
    <?php /*if ( HaveAccess($delete_url_details) && (!$isCreate )) : ?>
        <th></th>
    <?php endif;*/ ?>
</tr>
<tr>
    <th>
        <a onclick="javascript:add_row(this,'input:text,input:hidden[name^=t_seq_kpi],textarea,select',false);"  href="javascript:;"><i class="glyphicon glyphicon-plus"></i>جديد</a>
    </th>
    <th></th>
    <th></th>
    <th></th>
    <th></th>
    <th></th>

  
    
    <?php /*if ( HaveAccess($delete_url_details) && (!$isCreate )) : ?>
        <th></th>
    <?php endif;*/ ?>
</tr>

</tfoot>
</table></div>