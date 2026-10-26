<?php

$MODULE_NAME= 'freecheck';
$TABLE_NAME= 'Free_check';
$MODULE_NAME2= 'visitation';
$TABLE_NAME2= 'sentence_transaction';
$count = 0 ;
$get_page_url = base_url("$MODULE_NAME/$TABLE_NAME/get_page");
$get_page_url1 = base_url("$MODULE_NAME/$TABLE_NAME/get_page_inside");
$get_page_url2 = base_url("$MODULE_NAME/$TABLE_NAME/get_page_sub");
$createAll = base_url("$MODULE_NAME/$TABLE_NAME/createAll");
$createNotes = base_url("$MODULE_NAME/$TABLE_NAME/subCheckSelectNote");
$editPush_url= base_url("$MODULE_NAME2/$TABLE_NAME2/editPush");
$post_url_fees= base_url("$MODULE_NAME2/$TABLE_NAME2/createFees");
$addPaidValue_url= 'addPaidValue';
$Push_url= 'Paids';

$parts = explode("/", $_SERVER['REQUEST_URI']);
?>
<!-- begin:: Content -->
<div class="kt-content  kt-grid__item kt-grid__item--fluid" id="kt_content">
    <!--begin::Portlet-->
    <div class="kt-portlet">
        <div class="kt-portlet__body">
            <!--begin::Form-->
            <form id="<?= $TABLE_NAME; ?>_form" name="<?= $TABLE_NAME; ?>_form"  class="horizontal-form <?= $TABLE_NAME; ?>_form"
            action=""  method="post" role="form" novalidate="novalidate">
                <div class="kt-portlet__body">
                	<!--Begin :: row---->
                    <div class="row">
                       <!-------->
                       <div class="form-group col-md-3">
                       </div>
                       <!-------->
                        <div class="form-group col-md-3">
                        <label for="example-text-input" class="col-12 col-form-label">رقم الإشتراك</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control TXT_SUBSCRIBER_Free" id="TXT_SUBSCRIBER_Free" 
                                        name="TXT_SUBSCRIBER_Free" placeholder="رقم الاشتراك" 
                                        value="<?= $HaveRs ? $rs['REQUEST_SUBSCRIBER'] : "" ?>"  />
                                        <input type="hidden" id="HIDDURL" name="HIDDURL" value="<?= $HaveRs ? end($parts) : "" ?>"  />
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3">
                        <label for="example-text-input" class="col-12 col-form-label">رقم الهوية</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control TXT_ID_Free" id="TXT_ID_Free" 
                                        name="TXT_ID_Free" placeholder="رقم الهوية" />
                                        <input type="hidden" name="TXT_REQUEST_HIDDEN" id="TXT_REQUEST_HIDDEN" 
                                        value="<?= $HaveRs ? end($parts) : "" ?>"   />
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                    </div>
                    <!--End :: row---->
                    <!--Begin :: row---->
                    <div class="row">
                    	<!-------->
                        <div class="form-group col-md-3">
                        <label for="example-text-input" class="col-12 col-form-label">اسم المشترك الأول</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control TXT_NAME_Free_FIRST" id="TXT_NAME_Free_FIRST" 
                        				name="TXT_NAME_Free_FIRST" placeholder="اسم المشترك الأول" />
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3">
                        <label for="example-text-input" class="col-12 col-form-label">اسم الأب</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control TXT_NAME_Free_TWO" id="TXT_NAME_Free_TWO" 
                        				name="TXT_NAME_Free_TWO" placeholder="اسم الأب" />
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3">
                        <label for="example-text-input" class="col-12 col-form-label">اسم الجد</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control TXT_NAME_Free_THREE" id="TXT_NAME_Free_THREE" 
                        				name="TXT_NAME_Free_THREE" placeholder="اسم الجد" />
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3">
                        <label for="example-text-input" class="col-12 col-form-label">اسم العائلة</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control TXT_NAME_Free_FOUR" id="TXT_NAME_Free_FOUR" 
                        				name="TXT_NAME_Free_FOUR" placeholder="اسم العائلة" />
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                    </div>
                    <!--End :: row---->
                    <!--Begin :: row---->
                    <div class="row">
                    	<div class="form-group col-md-5">
                        </div>
                    	<div class="form-group col-md-5">
                                <label for="example-text-input"  class="col-8 col-form-label"></label>
                                <div class="col-12">
                                    <button type="button" class="btn btn-success"  id="btn_ad_<?= $TABLE_NAME; ?>" 
                                    onclick="javascript:searchsCheck();" >استعلام</button>
                                    <button type="button" class="btn btn-info" id="btn_clearr" 
                                    onclick="javascript:clear_form_internal();"  >تفريغ </button>
                                </div>
                         </div>
                    </div>
                    <!--End :: row---->
                    <!--Begin :: row---->
                    <div class="row">
                    	<!--begin::Portlet-->
                        <div class="kt-portlet kt-portlet--tabs">
                            <div class="kt-portlet__head">
                                <div class="kt-portlet__head-toolbar">
                                    <ul class="nav nav-tabs nav-tabs-line nav-tabs-line-success" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" data-toggle="tab" href="#kt_portlet_base_demo_1_7_tab_content" 
                                            role="tab" data-clicks="7">
                                                <i class="fa fa-cog"></i>بيانات التفتيش
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="tab" href="#kt_portlet_base_demo_1_8_tab_content" 
                                            role="tab" data-clicks="8">
                                                <i class="fa fa-search"></i>بيانات الفوترة والتحصيل
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="tab" href="#kt_portlet_base_demo_1_9_tab_content" 
                                            role="tab" data-clicks="9">
                                                <i class="fa fa-book"></i>بيانات الاستهلاك الشهري
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="tab" href="#kt_portlet_base_demo_1_10_tab_content" 
                                            role="tab" data-clicks="10">
                                                <i class="fa fa-server"></i>بيانات القطع و الوصل
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="tab" href="#kt_portlet_base_demo_1_11_tab_content" 
                                            role="tab" data-clicks="11">
                                                <i class="fa fa-people-carry"></i>بيانات القضايا القانونية
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="kt-portlet__body">
                                <div class="tab-content">
                                    <!------------>
                                    <div class="tab-pane active" id="kt_portlet_base_demo_1_7_tab_content" role="tabpanel">
                                        <?php  include($_SERVER['DOCUMENT_ROOT']."/Commercial/application/modules/general_freecheck/views/new_free/sub_request_One.php"); ?>
                                    </div>
                                    <!------------>
                                    <div class="tab-pane" id="kt_portlet_base_demo_1_8_tab_content" role="tabpanel">
                                        <?php  include($_SERVER['DOCUMENT_ROOT']."/Commercial/application/modules/general_freecheck/views/new_free/sub_request_Two.php"); ?>
                                    </div>
                                    <!------------>
                                    <div class="tab-pane" id="kt_portlet_base_demo_1_9_tab_content" role="tabpanel">
                                        <?php  include($_SERVER['DOCUMENT_ROOT']."/Commercial/application/modules/general_freecheck/views/new_free/sub_request_Three.php"); ?>
                                    </div>
                                    <!------------>
                                    <div class="tab-pane" id="kt_portlet_base_demo_1_10_tab_content" role="tabpanel">
                                        <?php  include($_SERVER['DOCUMENT_ROOT']."/Commercial/application/modules/general_freecheck/views/new_free/sub_request_Four.php"); ?>
                                    </div>
                                    <!------------>
                                    <div class="tab-pane" id="kt_portlet_base_demo_1_11_tab_content" role="tabpanel">
                                        <?php  include($_SERVER['DOCUMENT_ROOT']."/Commercial/application/modules/general_freecheck/views/new_free/sub_request_Five.php"); ?>
                                    </div>
                                    <!------------>
                                </div>
                            </div>
                        </div>
            
                        <!--end::Portlet-->
                    </div>
                    <!--End :: row---->
                </div>
            </form>
            <!--End::Form-->
        </div>
    </div>
    <!--end::Portlet-->
</div>
<!-- end:: Content -->
<!-- remote datatable modal -->
<div id="kt_modal_KTDatatable_remote" class="modal fade <?= $TABLE_NAME; ?>Modal1_Free" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-xl">
		<div class="modal-content" style="min-height: 590px;">
			<div class="modal-header">
				<h5 class="modal-title">
					عرض الدفعات
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body modal-body-fit">
               	<div class="kt-portlet kt-portlet--mobile">
                		<div class="kt-portlet__body <?= $TABLE_NAME; ?>_ListIns">
                            <!--begin: Datatable -->
                                <table class="table table-striped- table-bordered table-hover table-checkable" id="kt_table_1">
                                    <thead>
                                       <tr>
                                            <th style="width:10%">رقم القضية</th>
                                            <th style="width:10%">قيمة الدفعة</th>
                                            <th style="width:10%">رقم الايصال</th>
                                            <th style="width:10%">تاريخ الدفع</th>
                                            <th style="width:10%">غرامة</th>
                                            <th style="width:10%">فرق استهلاك</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    	
                                    </tbody>
                                    <tfoot>
                                       <tr bgcolor="#EFEFEF">
                                            <th>المجموع</th>
                                            <th colspan="3" class="valRet" align="center"></th>
                                            <th class="demRet"></th>
                                            <th class="consRet"></th>
                                        </tr>
                                        <tr>
                                            <th>المضاف ع الفاتورة</th>
                                            <th colspan="3" class="valBill" align="center"></th>
                                            <th></th>
                                            <th></th>
                                        </tr>
                                        <tr bgcolor="#EFEFEF">
                                            <th>المجموع الكلي</th>
                                            <th colspan="3" class="valBiRet" align="center"></th>
                                            <th></th>
                                            <th></th>
                                        </tr>
                                        <tr>
                                            <th>المتبقي</th>
                                            <th colspan="3" class="valBaqi" align="center"></th>
                                            <th></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                                <!--end: Datatable -->
                        </div>
                </div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-clean btn-bold btn-upper btn-font-sm" data-dismiss="modal">إلغاء</button>
			</div>
		</div>
	</div>
</div>
<!-- remote datatable modal -->
<div id="kt_modal_KTDatatable_remote" class="modal fade <?= $TABLE_NAME; ?>Modal2_Free" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-xl">
		<div class="modal-content" style="min-height: 590px;">
			<div class="modal-header">
				<h5 class="modal-title">
					بيانات التحصيل
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body modal-body-fit">
               	<div class="kt-portlet kt-portlet--mobile">
                		<div class="kt-portlet__body">
                            <form id="_Form_Table_<?= $TABLE_NAME; ?>" class="form-horizontal Form_Table_<?= $TABLE_NAME; ?>" 
                            name="Form_Table_<?= $TABLE_NAME; ?>"
                            role="form" novalidate="novalidate" action="<?= $createAll; ?>">
                            <!-------------------------------------->
                            <?php  include($_SERVER['DOCUMENT_ROOT']."/Commercial/application/modules/general_freecheck/views/new_free/sub_free/sub_free_One.php"); ?>
                            <!-------------------------------------->
                            </form>
                        </div>
                </div>
			</div>
			<div class="modal-footer">
            	<?php  if(HaveAccess($createAll)) { ?>
            	<button type="button" class="btn btn-primary" 
                data-dismiss="modal" id="SAVE<?= $TABLE_NAME; ?>">حفظ</button>
                <?php } ?>
				<button type="button" class="btn btn-clean btn-bold btn-upper btn-font-sm"
                data-dismiss="modal">إلغاء</button>
			</div>
		</div>
	</div>
</div>
<!-- remote datatable modal -->
<div id="kt_modal_KTDatatable_remote" class="modal fade <?= $TABLE_NAME; ?>Modal52_Free" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-xl">
		<div class="modal-content" style="min-height: 590px;">
			<div class="modal-header">
				<h5 class="modal-title">
					نتائج البحث
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body modal-body-fit">
               	<div class="kt-portlet kt-portlet--mobile">
                		<div class="kt-portlet__body <?= $TABLE_NAME; ?>_ListIns_INTERNAL">
                            <!--begin: Datatable -->
                                <table class="table table-striped- table-bordered table-hover table-checkable" id="kt_table_1">
                                    <thead>
                                       <tr>
                                            <th style="width:5%"><input type="checkbox" class="form-control" id="CheckAll" name="CheckAll"  /></th>
                                            <th style="width:10%">رقم الاشتراك</th>
                                            <th style="width:10%">رقم الهوية</th>
                                            <th style="width:30%">اسم المشترك</th>
                                            <th style="width:30%">اسم المنتفع</th>
                                            <th style="width:10%">المقر</th>
                                            <th style="width:10%">نوع القضية</th>
                                            <th style="width:10%">خيارات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    	
                                    </tbody>
                                </table>
                                <!--end: Datatable -->
                        </div>
                </div>
			</div>
			<div class="modal-footer">
            	<button type="button" class="btn btn-primary" data-dismiss="modal" id="select_checked">اختيار المحدد</button>
				<button type="button" class="btn btn-clean btn-bold btn-upper btn-font-sm" data-dismiss="modal">إلغاء</button>
			</div>
		</div>
	</div>
</div>
<!-- remote datatable modal -->
<div id="kt_modal_KTDatatable_remote" class="modal fade <?= $TABLE_NAME; ?>Modal89_Free" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-xl">
		<div class="modal-content" style="min-height: 590px;">
			<div class="modal-header">
				<h5 class="modal-title">
					عرض الفواتير
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body modal-body-fit">
               	<div class="kt-portlet kt-portlet--mobile">
                		<div class="kt-portlet__body <?= $TABLE_NAME; ?>_ListIns89">
                            <!--begin: Datatable -->
                                <table class="table table-striped- table-bordered table-hover table-checkable" id="kt_table_1">
                                    <thead>
                                       <tr>
                                            <th style="width:10%">رقم الاشتراك</th>
                                            <th style="width:10%">تاريخ الفاتورة</th>
                                            <th style="width:10%">قيمة الفاتورة</th>
                                            <th style="width:10%">قيمة الدفعة</th>
                                            <th style="width:10%">تاريخ الدفعة</th>
                                            <th style="width:10%">اجمالي السداد</th>
                                            <th style="width:40%">البنك</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    	
                                    </tbody>
                                </table>
                                <!--end: Datatable -->
                        </div>
                </div>
			</div>
			<div class="modal-footer">
            	<button type="button" class="btn btn-clean btn-bold btn-upper btn-font-sm" data-dismiss="modal">إلغاء</button>
			</div>
		</div>
	</div>
</div>
<!-- remote datatable modal -->
<div id="kt_modal_KTDatatable_remote" class="modal fade <?= $TABLE_NAME; ?>ModalPrepaid_Free" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-xl">
		<div class="modal-content" style="min-height: 590px;">
			<div class="modal-header">
				<h5 class="modal-title">
					عرض شحنات مسبق الدفع
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body modal-body-fit">
               	<div class="kt-portlet kt-portlet--mobile">
                		<div class="kt-portlet__body <?= $TABLE_NAME; ?>_ListIns89">
                            <!--begin: Datatable -->
                                <table class="table table-striped- table-bordered table-hover table-checkable" id="kt_table_1">
                                    <thead>
                                       <tr>
                                            <th style="width:10%">رقم الاشتراك</th>
                                            <th style="width:10%">تاريخ الشحنة</th>
                                            <th style="width:10%">قيمة الشحنة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    	
                                    </tbody>
                                </table>
                                <!--end: Datatable -->
                        </div>
                </div>
			</div>
			<div class="modal-footer">
            	<button type="button" class="btn btn-clean btn-bold btn-upper btn-font-sm" data-dismiss="modal">إلغاء</button>
			</div>
		</div>
	</div>
</div>
<!-- remote datatable modal -->
<div id="kt_modal_KTDatatable_remote" class="modal fade FormModal<?= $TABLE_NAME2; ?>" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-xl">
		<div class="modal-content" style="min-height: 590px;">
			<div class="modal-header">
				<h5 class="modal-title">
					الدفعات
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body modal-body-fit">
               	<div class="kt-portlet kt-portlet--mobile">
                		<div class="kt-portlet__body">
                            <form id="Form<?= $TABLE_NAME2; ?>Modal" name="Form<?= $TABLE_NAME2; ?>Modal"  
                            class="horizontal-form Form<?= $TABLE_NAME2; ?>Modal"
                              action="<?= $editPush_url; ?>" method="post" role="form" novalidate="novalidate">
                            <!-------------------------------------->
                            <?php  include($_SERVER['DOCUMENT_ROOT']."/Commercial/application/modules/general_freecheck/views/new_free/sub_free/sub_free_Six.php"); ?>
                            <!-------------------------------------->
                            </form>
                        </div>
                </div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-clean btn-bold btn-upper btn-font-sm" data-dismiss="modal">إلغاء</button>
			</div>
		</div>
	</div>
</div>
<!-- remote datatable modal -->
<div id="kt_modal_KTDatatable_remote" class="modal fade FormModalCollect<?= $TABLE_NAME2; ?>" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-xl">
		<div class="modal-content" style="min-height: 590px;">
			<div class="modal-header">
				<h5 class="modal-title">
					عرض المبالغ المراد تحصيلها
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body modal-body-fit">
               	<div class="kt-portlet kt-portlet--mobile">
                		<div class="kt-portlet__body">
                            <form id="Form<?= $TABLE_NAME2; ?>ModalCollect" name="Form<?= $TABLE_NAME2; ?>ModalCollect"  
                            class="horizontal-form Form<?= $TABLE_NAME2; ?>ModalCollect"
                              action="<?= $post_url_fees; ?>" method="post" role="form" novalidate="novalidate">
                            <!-------------------------------------->
                            <?php  include($_SERVER['DOCUMENT_ROOT']."/Commercial/application/modules/general_freecheck/views/new_free/sub_free/sub_free_Seven.php"); ?>
                            <!-------------------------------------->
                            </form>
                        </div>
                </div>
			</div>
			<div class="modal-footer">				
				<button type="button" class="btn btn-clean btn-bold btn-upper btn-font-sm" data-dismiss="modal">إلغاء</button>
			</div>
		</div>
	</div>
</div>
<!-- remote datatable modal -->
<div id="kt_modal_KTDatatable_remote" class="modal fade <?= $TABLE_NAME; ?>ModalNote" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-xl">
		<div class="modal-content" style="min-height: 590px;">
			<div class="modal-header">
				<h5 class="modal-title">
					ملاحظات
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body modal-body-fit">
               	<div class="kt-portlet kt-portlet--mobile">
                		<div class="kt-portlet__body">
                            <form id="Note_Table_<?= $TABLE_NAME; ?>" 
                            class="form-horizontal Note_Table_<?= $TABLE_NAME; ?>" 
                            name="Note_Table_<?= $TABLE_NAME; ?>"
                            role="form" novalidate="novalidate" action="<?= $createNotes; ?>">
                            <!-------------------------------------->
                            <div class="row">
                            	<!-------->
                                <div class="form-group col-md-6">
                                <label for="example-text-input" class="col-12 col-form-label">رقم الطلب</label>
                                        <div class="col-12">
                                            <div class="input-group">
                                                <input type="text" class="form-control" 
                                                name="TXT_REQUEST_APP_SERIAL_NOTE" 
                                                id="TXT_REQUEST_APP_SERIAL_NOTE" readonly />
                                                <div id="help-block"></div>
                                            </div>
                                        </div>
                                </div>
                                <!-------->
                                <div class="form-group col-md-6">
                                <label for="example-text-input" class="col-12 col-form-label">رقم الاشتراك</label>
                                        <div class="col-12">
                                            <div class="input-group">
                                                <input type="text" class="form-control" 
                                                name="TXT_SUBSCRIBER_NOTE" 
                                                id="TXT_SUBSCRIBER_NOTE" readonly />
                                                <div id="help-block"></div>
                                            </div>
                                        </div>
                                </div>
                            </div>
                            <!-------------------------------------->
                            <div class="row">
                            	<!-------->
                                <div class="form-group col-md-6">
                                <label for="example-text-input" class="col-12 col-form-label">ملاحظات سابقة</label>
                                        <div class="col-12">
                                            <div class="input-group">
                                                <textarea name="TXT_NOTE_LAST" id="TXT_NOTE_LAST" 
                                                class="form-control" rows="4" cols="6"></textarea>
                                                <div id="help-block"></div>
                                            </div>
                                        </div>
                                </div>
                                <!-------->
                                <!-------->
                                <div class="form-group col-md-6">
                                <label for="example-text-input" class="col-12 col-form-label">ادخل ملاحظاتك</label>
                                        <div class="col-12">
                                            <div class="input-group">
                                                <textarea name="TXT_NOTE_DET" id="TXT_NOTE_DET" 
                                                class="form-control" rows="4" cols="6"></textarea>
                                                <div id="help-block"></div>
                                            </div>
                                        </div>
                                </div>
                                <!-------->
                            </div>
                            <!-------------------------------------->
                            </form>
                        </div>
                </div>
			</div>
			<div class="modal-footer">
            	<?php  if(HaveAccess($createNotes)) { ?>
            	<button type="button" class="btn btn-primary" 
                id="SAVE_NOTE<?= $TABLE_NAME; ?>" data-dismiss="modal">حفظ</button>
                <?php } ?>
				<button type="button" class="btn btn-clean btn-bold btn-upper btn-font-sm" 
                data-dismiss="modal">إلغاء</button>
			</div>
		</div>
	</div>
</div>