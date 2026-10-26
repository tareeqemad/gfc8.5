<?php
/**
 * Created by PhpStorm.
 * User: mbadawi
 * Date: 16/01/2020
 * Time: 08:47 ص
 */
$MODULE_NAME= 'subscriberupdate';
$TABLE_NAME = 'RequestUpdate';

//Assign Work 
$get_page_url = base_url("$MODULE_NAME/$TABLE_NAME/get_page");
$get_id_url = base_url("$MODULE_NAME/$TABLE_NAME/public_get_details");
$general_adopt = base_url("requests/Requests/general_adopt1");
//
$report_url = gh_gfc_domain() . "/Trading/JsperReport/showreport?sys=Trading/Public_Service_Center";
$report_sn= report_sn();
//

if($action == 'index') :
?>
var table = 'RequestUpdate';
var count = 0;

$('#DL_SERVICE_TYPE, #DL_SERVICE_ID, #DL_STAGE_DEFINITION_ID,#DL_BRANCH,#DL_IS_CAMPAIGN,#DL_REQUEST_STATUS').select2();
$('#DL_PHASE,#DP_PAID_TYPE,#DP_REQUEST_TYPE').select2();

// Clear Form 
function clear_form(){
    clearForm($('#'+table+'_form'));
}

/// Function Search
function searchs(){
	$("#page_tbl").DataTable().clear().destroy();
    $(".requests_Lists").append('');
    $("#page_tbl").remove();
    ///
    KTApp.block('.card-body', {
        overlayColor: '#000000',
        type: 'v2',
        state: 'primary',
        message: 'جاري جلب البيانات...'
    });
    ///
    var REQUEST_APP_SERIAL = $('#TXT_REQUEST_APP_SERIAL').val();
    var SUBSCRIBER = $('#TXT_SUBSCRIBER').val();
    var BRANCH_ID = $('#DL_BRANCH').val();
    var PHASE_TYPE = $('#DL_PHASE').val();
    var SERVICE_ID = $('#DL_SERVICE_ID').val();
    var PAID_TYPE = $('#DP_PAID_TYPE').val();
    var REQUEST_TYPE = $('#DP_REQUEST_TYPE').val();
    var APPLICANT_NAME = $('#TXT_APPLICANT_NAME').val();
    var FROM_DATE = $("input[name='TXT_FROM_DATE']").val();
    var TO_DATE = $("input[name='TXT_TO_DATE']").val();
    var REQUEST_STATUS = $('#DL_REQUEST_STATUS').val();
    var IS_CAMPAIGN = $('#DL_IS_CAMPAIGN').val();
    
    if(REQUEST_APP_SERIAL == '' && SUBSCRIBER == '' && BRANCH_ID == 0 && PHASE_TYPE == 0 && PAID_TYPE == 0 && APPLICANT_NAME == '' ){
    	danger_msg('تحذير..','لا بد من اختيار محدد واحد على الأقل');
        KTApp.unblock('.card-body');
    }else{
    
        var newArr = {REQUEST_APP_SERIAL:REQUEST_APP_SERIAL,SUBSCRIBER:SUBSCRIBER,BRANCH_ID:BRANCH_ID,PHASE_TYPE:PHASE_TYPE,SERVICE_ID:SERVICE_ID,
        PAID_TYPE:PAID_TYPE,REQUEST_TYPE:REQUEST_TYPE,APPLICANT_NAME:APPLICANT_NAME,FROM_DATE:FROM_DATE,TO_DATE:TO_DATE,REQUEST_STATUS:REQUEST_STATUS,IS_CAMPAIGN:IS_CAMPAIGN};
         
        get_data('<?=$get_page_url?>',newArr ,function(data){
             $("#container").html(data);
             $("#page_tbl",$("#container")).DataTable({
                    "oLanguage": {
                        "sProcessing": "جارٍ التحميل...",
                        "sLengthMenu": "أظهر _MENU_ مدخلات",
                        "sZeroRecords": "لم يعثر على أية سجلات",
                        "sInfo": "إظهار _START_ إلى _END_ من أصل _TOTAL_ مدخل",
                        "sInfoEmpty": "يعرض 0 إلى 0 من أصل 0 سجل",
                        "sInfoFiltered": "(منتقاة من مجموع _MAX_ مُدخل)",
                        "sInfoPostFix": "",
                        "sSearch": "ابحث:",
                        "sUrl": "",
                        "oPaginate": {
                            "sFirst": "الأول",
                            "sPrevious": "السابق",
                            "sNext": "التالي",
                            "sLast": "الأخير"
                        }
                    }
             });
             KTApp.unblock('.card-body');
        },'html'); 
    }
}
////
$("#DL_PHASE").change(function(){
      var NewVal = $(this).val();
      var VID = 'option.SERVICE_ID';
      var VNAME = 'option.SERVICE_NAME';
      getDataForSelectByTwo('TSG_SETTING_PKG' , 'SERVICES_GET_BY_TYPEPHASE' , NewVal , 3 , 'DL_SERVICE_ID' , VID , VNAME);
})
<?php
  endif;
  if($action == 'PageCreate' || $action == 'PageEdit') :
?>
var table = 'RequestUpdate';
var base_lin = 'Commercial';
var count = 0;
 <?php  if($action == 'PageCreate'){ ?> 
 var RequestAppSerial = -1; var ServiceID = -1 ; 
 <?php }elseif($action == 'PageEdit'){ ?> 
 var linkURL = window.location.href;
 var URLS = linkURL.substring(linkURL.lastIndexOf("/") + 1);
 var RequestAppSerial = URLS; 
 <?php } ?> 

$('#DL_APPLICANT , #DL_DOCUMENT_TYPE, #TXT_PRVX , #DL_APPLICANT_JOB , #DL_SERVICE_ID , #TXT_TYPE_PAID , #DL_Junior_No , #DL_Great_R_No ').select2();
//$('#DL_DISTRICT , #DL_BANKS, #DL_BANK_BRANCH , #DL_EMP_TYPE ').select2();
//$('#DL_DOCUMENT_TYPE_SUB , #DL_JOB, #DL_REQUEST_APP_SERIAL_TYPE , #DL_APPROVED ').select2();

///////////////////////////////// Transform between Tap Wizard /////////	
$('li:has([data-toggle="tab"])').click(function(e){
   tit = $(e.target).attr("data-clicks");
   if(RequestAppSerial == -1){
   	return false;
   }
   if(tit==2){
   		var TYPE_PAID = $('#'+table+'_form').find("input[name='TXT_TYPE_PAID_H']").val();
        var SUBSCRIBER = $('#'+table+'_form').find("input[name='TXT_SUBSCRIBER']").val();
        if(TYPE_PAID == 1){  
              var new_arr = [
                {"TXT_BANK_NO" : "data[0].ACCOUNT_NO"} , 
                {"TXT_BANKS" : "data[0].M_BANK_NAME"} , 
                {"TXT_BANK_BRANCH" : "data[0].B_BANK_NAME"} , 
                {"TXT_EMP_TYPE" : "data[0].IS_ACTIVE_NAME"}   
              ];
              fetchDataFromSql('EBLLC_PKG' , 'BANK_SUBSCRIBERS_GET' , SUBSCRIBER , new_arr );
              $('.Sadad').css('display','block');
              $('.Mosbaq').css('display','none');
        }else if(TYPE_PAID == 2){
        	  /*var new_arr = [
                {"TXT_SEND_NO" : "data[0].SEND_NO"} , 
                {"TXT_THE_YEAR" : "data[0].THE_YEAR"} , 
                {"TXT_REMAINDER_VAL" : "data[0].REMAINDER_VAL"} , 
                {"TXT_MONTHLY_COMMITTED" : "data[0].MONTHLY_COMMITTED"} ,
                {"kt_datepicker_1" : "data[0].FIRST_DATE_PAID"}   
              ];
              fetchDataFromSql('SERVICES_PKG' , 'DEBT_SEND_TB_GET' , SUBSCRIBER , new_arr );*/
              $('.Sadad').css('display','none');
              $('.Mosbaq').css('display','block');
        }else{
            $('.Sadad').css('display','none');
            $('.Mosbaq').css('display','none');
        }
   }
   if(tit==3){
   		var servs_id = $('#'+table+'_form').find("select[name='DL_SERVICE_ID']").val();
        if(servs_id == 11 || servs_id == 12 || servs_id == 13 || servs_id == 14 ){
        	$('.ChangeTNa').css('display','block');
            $('.CorrectName').css('display','none');
            $('.CorrectAddress').css('display','none');
            $('.LinkType').css('display','none');
        }else if(servs_id == 15 || servs_id == 16 ){
        	$('.ChangeTNa').css('display','none');
            $('.CorrectName').css('display','block');
            $('.CorrectAddress').css('display','none');
            $('.LinkType').css('display','none');
        }else if(servs_id == 17 || servs_id == 18 ){
        	$('.ChangeTNa').css('display','none');
            $('.CorrectName').css('display','none');
            $('.CorrectAddress').css('display','block');
            $('.LinkType').css('display','none');
        }else if(servs_id == 19 || servs_id == 20 || servs_id == 21 || servs_id == 22 ){
        	$('.ChangeTNa').css('display','none');
            $('.CorrectName').css('display','none');
            $('.CorrectAddress').css('display','none');
            $('.LinkType').css('display','block');
        }
   }
   if(tit==4){
    <?php  if($action == 'PageCreate'){ ?>
          getLegalCommi(RequestAppSerial , ServiceID);
          var org_id = $('#'+table+'_form_three').find("input[name='TXT_ORG']").val();
          var type_id = $('#'+table+'_form_three').find("select[name='DL_REQUEST_APP_SERIAL_TYPE']").val();
          getLegalCommi_1(ServiceID , org_id , type_id);
    <?php }elseif($action == 'PageEdit'){ ?>
    	var serv_id = $('#'+table+'_form').find("select[name='DL_SERVICE_ID']").val();
        var org_id = $('#'+table+'_form_three').find("input[name='TXT_ORG']").val();
        var type_id = $('#'+table+'_form_three').find("select[name='DL_REQUEST_APP_SERIAL_TYPE']").val();
        getLegalCommi(RequestAppSerial , serv_id);
        getLegalCommi_1(serv_id , org_id , type_id);
    <?php } ?>
   }
   if(tit==5) {
        var NewVal = $('#kt_maxlength_41').val();
        /// FAMILY
		getDataFromService(NewVal , RequestAppSerial);		
    }
    if(tit==22) {
    	<?php  if($action == 'PageCreate'){ ?>
    		var serv_id = $('#'+table+'_form').find("select[name='DL_SERVICE_ID']").val();
            $('#'+table+'_form_ten').find("a[id='ID_COVER']").removeAttr("onclick");
            $('#'+table+'_form_ten').find("a[id='ID_FREE']").removeAttr("onclick");
            
            $('#'+table+'_form_ten').find("a[id='ID_COVER']").attr('onclick','javascript:print_report_general('+RequestAppSerial+','+serv_id+',0);');
            $('#'+table+'_form_ten').find("a[id='ID_FREE']").attr('onclick','javascript:print_report_general_2('+RequestAppSerial+',"discharge");');
        <?php } ?>
    }
});
////
$("#TXT_SUBSCRIBER").change(function(){
      var NewVal = $(this).val();
      if(NewVal != ''){
          var new_arr = [
            {"txt_fname" : "data[0].NAME"} ,
            {"txt_type_n" : "data[0].TYPE_NAME"} , 
            {"TXT_ID" : "data[0].ID"} , 
            {"TXT_SUBSCRIBER_NAME" : "data[0].NAME"} , 
            {"TXT_SUBSCRIBER_BEN" : "data[0].BENIFICARY_NAME"} ,
            {"TXT_TYPE_NAME" : "data[0].TYPE_NAME"} ,
            {"TXT_TYPE_ID" : "data[0].TYPE"} ,
            {"TXT_REQUEST_APP_TYPE_NAME" : "data[0].SUB_TYPE_NAME"} ,
            {"TXT_LOCATION_NO" : "data[0].LOCATION_NO"} ,
            {"TXT_USE_TYPE_NAME" : "data[0].USE_TYPE_NAME"} ,
            {"TXT_USE_TYPE" : "data[0].USE_TYPE"} ,
            {"TXT_ORG_NAME" : "data[0].ORG_NAME"} ,
            {"TXT_ORG" : "data[0].ORG"} ,
            {"TXT_LINK_TYPE_NAME" : "data[0].LINK_TYPE_NAME"} ,
            {"TXT_LINK_TYPESS" : "data[0].LINK_TYPE"} ,
            {"TXT_AMBIR_NAME" : "data[0].AMBIR_NAME"} ,
            {"TXT_AMBIR" : "data[0].AMBIR"} ,
            {"TXT_DISTRICT" : "data[0].DISTRICT_NAME"} ,
            {"TXT_DISTRICT_ID" : "data[0].DISTRICT"} ,
            {"TXT_BLOCK_CODE" : "data[0].ADDRESS"} ,
            {"TXT_SUBSCRIBE_DATE" : "data[0].SUBSCRIBE_DATE"} ,
            {"TXT_TAREFA" : "data[0].KW_PRICE"} ,
            {"TXT_RASEM" : "data[0].SUBSCRIBE_FEES"} ,
            {"TXT_IS_ACTIVE" : "data[0].IS_ACTIVE_NAME"} ,
            {"TXT_O_FILE_NO" : "data[0].FILENO"} ,
            {"TXT_PAYMENT_WAY" : "data[0].PAYMENT_WAY_NO_NAME"} ,
            {"TXT_TYPE_PAID_H" : "data[0].PAYMENT_WAY_NO"} ,
            {"TXT_TYPE_TYPE_H" : "data[0].TYPE"}  ,
            {"TXT_PHASE_TYPE" : "data[0].PHASE_TYPE_NAME"} ,
            {"DL_PHASE" : "data[0].PHASE_TYPE"} 
          ];
          fetchDataFromSqlByServiceType('EBLLC_PKG' , 'SUBSCRIBERS_TB_GET' , NewVal , new_arr , 3 );
     }else{
     	  danger_msg('تحذير..','الرجاء ادخال رقم الإشتراك');
     }
});

//
$('#kt_maxlength_41').change(function(){
    /*var NewVal = $(this).val();
    var APPLICANT = $('#DL_APPLICANT').val();
    if(APPLICANT == 1 ){
    	var textField = ["TXT_APPLICANT_NAME"];
    }else{
    	var textField = ["TXT_APPLICANT_NAME"];
    }
    getDataBySSN(NewVal , textField);*/
});
//
$('#TXT_SSN_NEW').change(function(){
    var NewVal = $(this).val();
    var textField = ["TXT_NAME_NEW" , "TXT_B_NAME_NEW"];
    getDataBySSN(NewVal , textField);
});
//
$('#TXT_SSN_AGREEMENT').change(function(){
    var NewVal = $(this).val();
    var textField = ["TXT_NAME_AGREEMENT"];
    getDataBySSN(NewVal , textField);
});
//
$('#TXT_NEIGHBOR_NO').change(function(){
	var NewVal = $(this).val();
    var new_arr = [{"TXT_NEIGHBOR_LOC" : "data[0].LOCATION_NO"}];
    fetchDataFromSql('EBLLC_PKG' , 'SUBSCRIBERS_TB_GET' , NewVal , new_arr);
});
////
$("#DL_Junior_No").change(function(){
      var NewVal = $(this).val();
      var VID = 'option.VID';
      var VNAME = 'option.VNAME';
      /*** ما تنسى تبدل رقم 2 باليشن تبغ اليوزر **/
      getDataForSelectByTwo('TSG_SETTING_PKG' , 'CONSTANTS_DETAIL_TB_COMPOUND' , NewVal , <?= $this->user->branch; ?> , 'DL_Great_R_No' , VID , VNAME);
});
//
$('#DL_NEW_USE_TYPE').change(function(){ 
    var NewVal = $(this).val();
	var VID = 'option.VID';
    var VNAME = 'option.VNAME';
    getDataForSelectByTwo('TSG_SETTING_PKG' , 'CONSTANTS_DETAIL_BRANCH_GET' , 12 , NewVal , 'DL_NEW_ORG' , VID , VNAME);  
});
///// Add In Database
$('#btn_add_'+table).click(function(){
	if (isDoubleClicked($(this))) return;
    ID_APP = $("#kt_maxlength_41").val();
    DOCUMENT_TYPE = $("#DL_DOCUMENT_TYPE").val();
    NewVal = $("#TXT_ID").val();
    DOCUMENT_TYPE_SUB = $("#DL_DOCUMENT_TYPE_SUB").val();
    ///
    if(DOCUMENT_TYPE == 1){
    	if (checkidno(ID_APP)){
        	swal.fire({
                title: 'هل أنت متأكد من حفظ البيانات المدرجة',
                text: "عزيزي المستخدم, في حال قمت بالضغط على زر التأكيد ,سيقوم النظام بإدراج البيانات",
                type: 'warning',
                showCancelButton: true,
                confirmButtonText: 'تأكيد'
            }).then(function(result) {
                if(result.value){
                    CreatePostedElement();
                }
            });	
       }else{
          swal.fire("تحذير!","رقم الهوية غير صحيح يرجى إدخال الرقم صحيح","error");
       }
    }else{
    		swal.fire({
                title: 'هل أنت متأكد من حفظ البيانات المدرجة',
                text: "عزيزي المستخدم, في حال قمت بالضغط على زر التأكيد ,سيقوم النظام بإدراج البيانات",
                type: 'warning',
                showCancelButton: true,
                confirmButtonText: 'تأكيد'
            }).then(function(result) {
                if(result.value){
                    CreatePostedElement();
                }
            });		
    }
});
// function create
function CreatePostedElement(){
    $.post($('#'+table+'_form').attr('action'),$('#'+table+'_form').serialize(), function(r){
        $(r).each(function(index, element) {
            if(element['o_success'] == 0){
                 danger_msg('تحذير..',element['o_msgtxt']);
            }
            if(element['o_success'] == 1){
                 ShowSuccessMessage();
                 <?php  if($action == 'PageCreate'){ ?>
                 RequestAppSerial = element['o_id'];
                 ServiceID = $('#DL_SERVICE_ID').val();
                 $('#'+table+'_form').find("input[name='TXT_REQUEST_APP_SERIAL']").val(RequestAppSerial);
                 $('#Free_check_form').find("input[name='HIDDURL']").val(RequestAppSerial);
                 $('#Free_check_form').find("input[name='TXT_REQUEST_HIDDEN']").val(RequestAppSerial);
                 <?php } ?>
            }
        })
    },'json');
    return false;
}
///// Add In Database
$('#btn_add_A_'+table).click(function(){
	    if (isDoubleClicked($(this))) return;
        <?php  if($action == 'PageCreate'){ ?>
        	var service_id = ServiceID ;
        <?php }elseif($action == 'PageEdit'){ ?>
    		var service_id = $('#'+table+'_form').find("select[name='DL_SERVICE_ID']").val();
        <?php } ?>
        
        swal.fire({
            title: 'هل أنت متأكد من حفظ البيانات المدرجة',
            text: "عزيزي المستخدم, في حال قمت بالضغط على زر التأكيد ,سيقوم النظام بإدراج البيانات",
            type: 'warning',
            showCancelButton: true,
            confirmButtonText: 'تأكيد'
        }).then(function(result) {
            if(result.value){
                $.post($('#'+table+'_form_three').attr('action')+'/'+RequestAppSerial+'/'+service_id,
                $('#'+table+'_form_three').serialize(), function(r){
                    $(r).each(function(index, element) {
                        if(element['o_success'] == 0){
                             danger_msg('تحذير..',element['o_msgtxt']);
                        }
                        if(element['o_success'] == 1){
                             ShowSuccessMessage();
                        }
                    })
                },'json');
                return false;
            }
        });	
});
// function getLegalCommi
function getLegalCommi(RequestAppSerial , ServiceID){
      var cou = 1 ;
      // First Get Legal
      $('#dp_process_no option').remove();
      $.ajax({
		url: '<?=$get_id_url?>/REP_PKG/SERVICES_REP_TB_GET/'+ServiceID,
		type: 'POST',
		datatype: 'json',
		success: function (data) { 
			if(data.length ==0){ } else{ 
              $.each(data,function (i,option) {
                 $("#dp_process_no").append($('<option/>').attr("value", option.PROCESS_NO).attr("data-thepath", option.THE_PATH).attr("data-processno", option.PROCESS_NO).attr("data-requestappserial", RequestAppSerial).text(cou+'.'+option.PROCESS));
                 cou = cou + 1 ;
              })
           }
		 },
		error: function (jqXHR, textStatus, errorThrown) {  }
	}); 
}
///
function printLegalCommi(){
	   var p_id = RequestAppSerial;
       var processno = $('#dp_process_no').val();
       var thepath = $('#dp_process_no').find(':selected').data("thepath");
       if(processno==0){
            danger_msg('تحذير..','اختر التعهد');
       }else{
            _showReport('https://gs.gedco.ps/Trading/JsperReport/showreport?sys=Trading/Legal_Commitments&type=pdf&report='+thepath+'&p_id='+p_id+'&p_processno='+processno+'&sn=<?=$report_sn?>');
           
       }
}
// function getLegalCommi_1
function getLegalCommi_1(serv_id , org_id , type_id){
    $(".FileService option").remove(); 
    $.ajax({
        url: '<?=$get_id_url?>/TSG_SETTING_PKG/SERVICES_DET_FILE_TB_GET/'+serv_id,
        type: 'POST',
        datatype: 'json',
        success: function (data) { 
           if(data.length ==0){ $(".FileService option").remove(); }else{
               $.each(data,function (i,option) {
                    //if(option.ORG == org_id && option.REQUEST_APP_SERVICE_TYPE == type_id){
                        $(".FileService").append($('<option/>').attr("value", option.FILE_NO).text(option.FILE_NO+'.'+option.FILE_NAME));
                    //}
                })
                
           }
         },
         error: function (jqXHR, textStatus, errorThrown) {  }
    });
}
//
$('#CH_IS_CREATE_CHANGE,#CH_IS_CREATE_CHECK,#CH_IS_CREATE_STAMP,#CH_IS_CHANGE_LINK').change(function(){
    if($(this).is(':checked') ){
    	$(".kt-radio-inline label").removeAttr("class");
        $(".kt-radio-inline label").attr("class","kt-radio");
        $('input[type="radio"]').removeAttr("disabled");
    }else{
    	$(".kt-radio-inline label").removeAttr("class");
        $(".kt-radio-inline label").attr("class","kt-radio kt-radio--disabled");
        $('input[type="radio"]').attr("disabled","disabled");
    }
});
///// Add In Database
$('#btn_add_Related_Request').click(function(){
	if (isDoubleClicked($(this))) return;
    
    swal.fire({
        title: 'هل أنت متأكد من حفظ البيانات المدرجة',
        text: "عزيزي المستخدم, في حال قمت بالضغط على زر التأكيد ,سيقوم النظام بإدراج البيانات",
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'تأكيد'
    }).then(function(result) {
        if(result.value){
            CreatePostedElement1();
        }
    });
});
// function create
function CreatePostedElement1(){
    $.post($('#'+table+'_form_ten').attr('action'),$('#'+table+'_form_ten').serialize(), function(r){
        $(r).each(function(index, element) {
            if(element['o_success'] == 0){
                 danger_msg('تحذير..',element['o_msgtxt']);
            }
            if(element['o_success'] == 1){
                 ShowSuccessMessage();
            }
        })
    },'json');
    return false;
}
///// Add In Database
$('#btn_adopt_Request').click(function(){
	if (isDoubleClicked($(this))) return;
    
    var REQUEST_APP = $('#'+table+'_form_ten').find("input[name='TXT_TEN_REQUEST_APP_SERIAL']").val(); 
    
    swal.fire({
        title: 'هل أنت متأكد من اعتماد وترحيل المعاملة',
        text: "عزيزي المستخدم, في حال قمت بالضغط على زر التأكيد ,سيقوم النظام بإدراج البيانات",
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'تأكيد'
    }).then(function(result) {
        if(result.value){
            var REQUEST_APP_SERIAL = RequestAppSerial;
            var REDIRECT = 'RequestUpdate';
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
    });
});
<?php
  endif;
?>
// دوال طباعة تقارير الجاسبر: كانت معرّفة globally في نظام Trading ولم تُنسخ مع الموديول
// الحارس typeof حتى لا نلغي التعريف الأصلي إن كان موجوداً على السيرفر
if (typeof print_report_general_2 !== 'function') {
    window.print_report_general_2 = function(p_id , rep){
        _showReport('<?=$report_url?>&type=pdf&report='+rep+'&p_id='+p_id+'&sn=<?=$report_sn?>');
    };
}
if (typeof print_report_general !== 'function') {
    window.print_report_general = function(p_id , serv_id , enlarge_type){
        // اسم تقرير "نموذج الطلب" حسب الخدمة غير معروف بعد في هذا النظام
        danger_msg('تحذير..','تقرير نموذج الطلب غير مربوط بعد في هذا النظام');
    };
}