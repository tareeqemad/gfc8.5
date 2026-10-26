<?php

$MODULE_NAME= 'subscriberupdate';
$TABLE_NAME= 'RequestUpdate';
$get_page_url = base_url("$MODULE_NAME/$TABLE_NAME/get_page");
?>
<!-- begin:: Subheader -->
<div class="kt-subheader   kt-grid__item" id="kt_subheader">
    <div class="kt-subheader__main">
        <h3 class="kt-subheader__title">
            الرئيسية </h3>
        <span class="kt-subheader__separator kt-hidden"></span>
        <div class="kt-subheader__breadcrumbs">
            <a href="<?=  site_url('cpanel'); ?>" class="kt-subheader__breadcrumbs-home"><i class="flaticon2-shelter"></i></a>
            <span class="kt-subheader__breadcrumbs-separator"></span>
            <a href="#" class="kt-subheader__breadcrumbs-link">
                إدارة النظام </a>
            <span class="kt-subheader__breadcrumbs-separator"></span>
            <a href="<?=  site_url('RequestUpdate'); ?>" class="kt-subheader__breadcrumbs-link">
                طلب خدمة تعديل اشتراك </a>
        </div>
    </div>
</div>
<!-- end:: Subheader -->
<!-- begin:: Content -->
<div class="kt-content">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <div class="mb-0 flex-grow-1 card-title">استعلام | طلب خدمة تعديل اشتراك</div>
                    <div class="flex-shrink-0">
						<?php  if (HaveAccess('CreateRequestUpdate')){  ?>
                        <a href="<?= site_url('CreateRequestUpdate'); ?>" class="btn btn-brand btn-elevate btn-icon-sm">
                            <i class="la la-plus"></i>
                            جديد
                        </a>
                        <?php } ?> 
                    </div>
                </div><!-- end card header -->
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-md-2">
                            <label for="txt_request_app_serial">المقر التابع</label>
                            <?= form_dropdown("DL_BRANCH",$BRANCHES,'',
                            'class="form-control" id = "DL_BRANCH" tabindex="1"');?>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="txt_subscriber">رقم الطلب</label>
                            <input type="text" placeholder="رقم الطلب" class="form-control" id="TXT_REQUEST_APP_SERIAL" 
                            name="TXT_REQUEST_APP_SERIAL" tabindex="2" />
                        </div>
                        <div class="form-group col-md-2">
                            <label for="txt_subscriber_name">رقم الاشتراك</label>
                            <input type="text" placeholder="رقم الاشتراك" class="form-control" id="TXT_SUBSCRIBER" 
                            name="TXT_SUBSCRIBER" tabindex="3" />
                        </div>
                        <div class="form-group col-md-2">
                            <label for="txt_applicant_name">نوع الفاز</label>
                            <?= form_dropdown("DL_PHASE",$PHASE_TYPES,'',
                                'class="form-control" id = "DL_PHASE" tabindex="4"');?>
                        </div>  
                        <div class="form-group col-md-4">
                            <label for="txt_applicant_name">الخدمة</label>
                            <select name="DL_SERVICE_ID" id="DL_SERVICE_ID" class="form-control" tabindex="5">
                                <option value="0">---اختر---</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="txt_applicant_name">اسم مقدم الطلب</label>
                            <input type="text" placeholder="اسم مقدم الطلب" class="form-control" id="TXT_APPLICANT_NAME" 
                            name="TXT_APPLICANT_NAME" tabindex="6" />
                        </div>
                        <div class="form-group col-md-2">
                            <label for="txt_applicant_name">آلية التسديد</label>
                            <?= form_dropdown("DP_PAID_TYPE",$PAID_TYPE,'',
                                'class="form-control" id = "DP_PAID_TYPE" tabindex="7"');?>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="txt_applicant_name">طبيعة الاشتراك</label>
                            <?= form_dropdown("DP_REQUEST_TYPE",$REQUEST_TYPE,'',
                                'class="form-control" id = "DP_REQUEST_TYPE" tabindex="8"');?>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="txt_applicant_name">نوع الحملة</label>
                            <?= form_dropdown("DL_IS_CAMPAIGN",$IS_CAMPAIGN,'',
                                    'class="form-control" id = "DL_IS_CAMPAIGN" tabindex="9"');?>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="txt_applicant_name">حالة الطلب</label>
                            <?= form_dropdown("DL_REQUEST_STATUS",$REQUEST_STATUS,'',
                                    'class="form-control" id = "DL_REQUEST_STATUS" tabindex="10"');?>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="txt_applicant_name">من تاريخ</label>
                            <div class="input-group date">
                                <input type="text" class="form-control date-input" id="kt_datepicker_3" 
                                name="TXT_FROM_DATE" />
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <i class="la la-calendar-check-o"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="txt_applicant_name">الى تاريخ</label>
                            <div class="input-group date">
                                <input type="text" class="form-control date-input" id="kt_datepicker_3" 
                                name="TXT_TO_DATE" />
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <i class="la la-calendar-check-o"></i>
                                    </span>
                                </div>
                            </div>
                        </div>                      
                    </div><!--end row -->
                    <div class="row">
                        <button type="button" class="btns btns-primary" onclick="javascript:searchs();">
                            <i class="fa fa-search"></i>
                            استعلام
                        </button> &nbsp;
                        <button type="button" class="btns btns-secondary" id="btn_clearr"
                                name="btn_clear" onclick="javascript:clear_form();">
                            <i class="fas fa-undo"></i> تفريغ
                        </button>
                    </div>
                    <hr>
                    <div id="container">
                        <?php echo modules::run($get_page_url); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!----end :: content--------->
<!-- remote datatable modal -->
<div class="modal fade <?= $TABLE_NAME; ?>Modal1" id="kt_modal_1" tabindex="-1" role="dialog" 
aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">شاشة تأكيد الحذف</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <div class="modal-body">
                <p>هل تريد حذف هذا العنصر</p>
                 <!--begin::Form-->
                <form class="FFFF5" name="FFFF5" 
                id="FFFF5" action="<?=base_url(array("delete", ''))?>" >
                    <div class="kt-portlet__body">
                    
                       <div class="row">
                            <div class="form-group col-md-3">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="example-text-input" class="col-12 col-form-label">الرقم المتسلسل
                                </label>
                                <div class="col-12">
                                    <input type="text" placeholder="الرقم المتسلسل" class="form-control" 
                                    id="example-text-input" name="numbers" readonly >
                                    <p class="form-control-static"></p>
                                </div>
                            </div>
                       </div>
                       
                       
                     </div>        
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" id="Delete<?= $TABLE_NAME; ?>">حذف العنصر</button></button>
            </div>
        </div>
    </div>
</div>