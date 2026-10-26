<?php
 $count = $offset;
 $TABLE_NAME= 'RequestUpdate';
 $general_adopt = base_url("requests/Requests/general_adopt1");
 ?>
<div class="table-responsive-xl">
    <!--begin: Datatable -->
    <table class="table table-hover table-bordered" id="page_tbl">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>رقم الطلب</th>
                <th>رقم الاشتراك</th>
                <th>اسم المشترك</th>
                <th>مقدم الطلب</th>
                <th>المقر</th>
                <th>مرحلة الطلب</th>
                <th>نوع الخدمة</th>
                <th>نوع الفاز</th>
                <th>اسم الخدمة</th>
                <th>آلية التسديد</th>
                <th>طبيعة الاشتراك</th>
                <th>نوع الحملة</th>
                <th>حالة الطلب</th>
                <th>تاريخ الطلب</th>
                <th>خيارات</th>
            </tr>
        </thead>
        <tbody>
          <?php foreach($rows as $row) :
          
          ?>
          <tr>
                <td>
					<?=$count?>
                </td>
                <td>
                     <?=$row['REQUEST_APP_SERIAL']?>
                </td>
                <td>
                    <?=$row['SUBSCRIBER']?>
                </td>
                <td>
                    <?=$row['SUBSCRIBER_NAME']?>
                </td>
                <td>
                    <?=$row['APPLICANT_NAME']?>
                </td>
                <td>
                    <?=$row['BRANCH_ID_NAME']?>
                </td>
                <td>
                    <?=$row['STAGE_DEFINITION_ID_NAME']?>
                </td>
                <td>
                    <?=$row['SERVICE_TYPE_NAME']?>
                </td>
                <td>
                    <?=$row['PHASE_TYPE_NAME']?>
                </td>
                <td>
                    <?=$row['SERVICE_ID_NAME']?>
                </td>
                <td>
                    <?=$row['PAYMENT_WAY_NAME']?>
                </td>
                <td>
                    
                </td>
                <td>
                    <?=$row['IS_CAMPAIGN_NAME']?>
                </td>
                <td>
                    <?=$row['STATUS_NAME']?>
                </td>
                <td>
                    <?=$row['REQUEST_APP_DATE']?>
                </td>
                <td>
                	<?php if($row['STATUS'] != -1 && $row['STATUS'] != 3 && HaveAccess('EditRequestUpdate')) { ?>
                    <a href="<?php echo base_url()?>EditRequestUpdate/<?=$row['REQUEST_APP_SERIAL']?>" 
                    class="btn btn-sm btn-clean btn-icon btn-icon-md">
                       <i class="fa fa-edit" data-pk="<?=$row['REQUEST_APP_SERIAL']?> " title="عرض السجل"></i>
                    </a>
                    <?php } ?>
                    <?php  if(HaveAccess('RequestAdoptStage') && $row['STATUS'] != -1 && $row['STATUS'] != 3){  ?>
                    <a href="javascript:;" 
                    class="btn btn-sm btn-clean btn-icon btn-icon-md" title="اعتماد" id="PreventDBclick" 
                    onclick="javascript:onclickFunc(<?=$row['REQUEST_APP_SERIAL']?>,'RequestUpdate');">
                    <i class="fa fa-check-circle" style="color:green"></i>
                    </a>
                    <?php } ?>        
                    <a href="javascript:;" onclick="javascript:print_report_general_2(<?=$row['REQUEST_APP_SERIAL']?>,'discharge');"
                        class="btn btn-sm btn-clean btn-icon btn-icon-md" title="إخلاء الطرف">
                        <i class="fa fa-print" data-pk="<?=$row['REQUEST_APP_SERIAL']?>" style="color:red"></i>
                    </a> 
                    <a href="javascript:;" onclick="javascript:print_report_general(<?=$row['REQUEST_APP_SERIAL']?>,<?=$row['SERVICE_ID']?>,<?=$row['ENLARGE_TYPE']?>);" class="btn btn-sm btn-clean btn-icon btn-icon-md" title="طباعة">
                    <i class="fa fa-print" data-pk="<?=$row['REQUEST_APP_SERIAL']?>" style="color:blue"></i>
                    </a>   
                </td>
          </tr>
          <?php $count++; endforeach;?>
        </tbody>
    </table>
    <!--end: Datatable -->
</div>
<!-- end:: Content --> 
<script>
//
$('#PreventDBclick').dblclick( function(e){ 
    e.preventDefault();
	
});
//
function onclickFunc(REQUEST_APP_SERIAL ,REDIRECT ){
	if (isDoubleClicked($(this))) return;
	var newArr = {REQUEST_APP_SERIAL:REQUEST_APP_SERIAL,REDIRECT:REDIRECT};
	get_data('<?=$general_adopt?>',newArr ,function(data){
		$(data).each(function(index, element) {
			if(element['o_success'] == 0){
				 danger_msg('تحذير..',element['o_msgtxt']);
			}
			if(element['o_success'] == 1){
				 ShowSuccessMessage();
				 get_to_link(window.location.href);
			}
		});
	},'json');
}
</script>