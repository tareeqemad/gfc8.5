<?php

$MODULE_NAME= 'subscriberupdate';
$TABLE_NAME= 'RequestUpdate';
?>
<!-- begin:: Content -->
<div class="kt-content  kt-grid__item kt-grid__item--fluid" id="kt_content">
    <!--begin::Portlet-->
    <div class="kt-portlet">
        <div class="kt-portlet__body">
            <!--begin::Form-->
            <form id="<?= $TABLE_NAME; ?>_form_two" name="<?= $TABLE_NAME; ?>_form_two"  class="horizontal-form <?= $TABLE_NAME; ?>_form_two"
            method="post" role="form" novalidate="novalidate">
                <div class="kt-portlet__body">
                    <div class="row">
					<div class="col-md-12 Sadad" style="display:none;" >
                    <!--Begin :: row---->
                    <div class="row">
                        <!-------->
                        <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">رقم حساب البنك</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="TXT_BANK_NO" name="TXT_BANK_NO" placeholder="رقم الحساب البنكي" 
                                        value="<?= $HaveRs ? count($prog1) > 0 ? $prog1[0]['ACCOUNT_NO'] : "" : "" ?>" />
                                        <div class="input-group-append"><span class="input-group-text" id="basic-addon2"><i class="fa fa-book"></i></span></div>
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">البنك</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="TXT_BANKS" name="TXT_BANKS" placeholder="البنك" 
                                        value="<?= $HaveRs ? count($prog1) > 0 ? $prog1[0]['M_BANK_NAME'] : "" : "" ?>" />
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">فرع البنك</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="TXT_BANK_BRANCH" name="TXT_BANK_BRANCH" placeholder="فرع البنك" 
                                        value="<?= $HaveRs ? count($prog1) > 0 ? $prog1[0]['B_BANK_NAME'] : "" : "" ?>" />
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">حالة الحساب</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="TXT_EMP_TYPE" name="TXT_EMP_TYPE" placeholder="نوع الموظف" 
                                        value="<?= $HaveRs ? count($prog1) > 0 ? $prog1[0]['IS_ACTIVE_NAME'] : "" : "" ?>" />
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                    </div>
                    <!--End :: row---->
                     </div>
   					 <div class="col-md-12 Mosbaq" style="display:none;" >
                    <!--Begin :: row---->
                    <div class="row">
                        <!-------->
                        <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">رقم السند</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control valid_number" id="TXT_SEND_NO" name="TXT_SEND_NO" placeholder="رقم السند" 
                                        value="" />
                                        <div class="input-group-append"><span class="input-group-text" id="basic-addon2"><i class="fa fa-book"></i></span></div>
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">السنة</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control valid_number" id="TXT_THE_YEAR" name="TXT_THE_YEAR" placeholder="السنة" 
                           				value="" />
                                        <div class="input-group-append"><span class="input-group-text" id="basic-addon2"><i class="fa fa-book"></i></span></div>
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">مبلغ المتأخرات</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="TXT_REMAINDER_VAL" name="TXT_REMAINDER_VAL" placeholder="مبلغ المتأخرات" 
                            			value="" />
                                        <div class="input-group-append"><span class="input-group-text" id="basic-addon2"><i class="fa fa-book"></i></span></div>
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">قيمة الالتزام الشهري</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control valid_number" id="TXT_MONTHLY_COMMITTED" name="TXT_MONTHLY_COMMITTED" placeholder="قيمة الالتزام الشهري" 
                           			    value="" />
                                        <div class="input-group-append"><span class="input-group-text" id="basic-addon2"><i class="fa fa-book"></i></span></div>
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                    </div>
                    <!--End :: row---->
                    <!--Begin :: row---->
                    <div class="row Mosbaq" style="display:none;">
                    	<!-------->
                        <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">تاريخ أول دفعة</label>
                                <div class="col-12">
                                   <div class="input-group date">
                                        <input type="text" class="form-control date-input" id="kt_datepicker_1" name="TXT_INVENTORY_DATE" tabindex="10" 
                                        value="" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="la la-calendar-check-o"></i>
                                            </span>
                                        </div>
                                    </div> 
                                </div>
                                    
                        </div>
                        <!-------->
                    </div>
                    <!--End :: row---->
                    </div>
                    </div>
                    
                </div>
            </form>
            <!--End::Form-->
        </div>
    </div>
    <!--end::Portlet-->
</div>
<!-- end:: Content -->
