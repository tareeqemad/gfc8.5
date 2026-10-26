<?php

$MODULE_NAME= 'subscriberupdate';
$TABLE_NAME= 'RequestUpdate';
$post_url_A= base_url("$MODULE_NAME/$TABLE_NAME/createA");
$edit_url_A= base_url("$MODULE_NAME/$TABLE_NAME/editA");
?>
<!-- begin:: Content -->
<div class="kt-content  kt-grid__item kt-grid__item--fluid" id="kt_content">
    <!--begin::Portlet-->
    <div class="kt-portlet">
        <div class="kt-portlet__body">
            <!--begin::Form-->
            <form id="<?= $TABLE_NAME; ?>_form_three" name="<?= $TABLE_NAME; ?>_form_three"  
            class="horizontal-form <?= $TABLE_NAME; ?>_form_three" 
            <?php if($HaveRs) { ?> action="<?= $edit_url_A; ?>" <?php }else{ ?> 
        	action="<?= $post_url_A; ?>" <?php } ?> method="post" role="form" novalidate="novalidate">
                <div class="kt-portlet__body">
                    <!--Begin :: row---->
                    <div class="row">
                    	<!-------->
                        <div class="form-group col-md-3"><label for="example-text-input" 
                        class="col-12 col-form-label">رقم هوية المشترك</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control TXT_ID" id="TXT_ID" name="TXT_ID" 
                                        placeholder="رقم هوية المشترك" 
                                        tabindex="16"  readonly value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['ID'] : "" : "" ?>" />
                                        <input type="text" class="form-control" id="TXT_SUBSCRIBER_D" name="TXT_SUBSCRIBER_D" 
                                        value="<?= $HaveRs ? $rs['SUBSCRIBER'] : '' ?>" />
                                        <div class="input-group-append"><span class="input-group-text" id="basic-addon2">
                                        <i class="fa fa-book"></i></span></div>
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">اسم المشترك</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control TXT_SUBSCRIBER_NAME" id="TXT_SUBSCRIBER_NAME" 
                                        name="TXT_SUBSCRIBER_NAME" placeholder="اسم المشترك" tabindex="17"  readonly 
                                        value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['NAME'] : "" : "" ?>" />
                                        <div class="input-group-append"><span class="input-group-text" id="basic-addon2">
                                        <i class="fa fa-book"></i></span></div>
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">اسم المنتفع</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control TXT_SUBSCRIBER_BEN" id="TXT_SUBSCRIBER_BEN" 
                                        name="TXT_SUBSCRIBER_BEN" placeholder="اسم المنتفع" tabindex="18"  readonly 
                                        value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['BENIFICARY_NAME'] : "" : "" ?>" />
                                        <div class="input-group-append"><span class="input-group-text" id="basic-addon2">
                                        <i class="fa fa-book"></i></span></div>
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">نوع الاشتراك</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control TXT_TYPE_NAME" id="TXT_TYPE_NAME" 
                                        name="TXT_TYPE_NAME" placeholder="نوع الاشتراك" tabindex="19"  readonly 
                                        value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['TYPE_NAME'] : "" : "" ?>" />
                                        
                                        <input type="hidden" class="form-control TXT_TYPE_ID" id="TXT_TYPE_ID"
                                        name="TXT_TYPE_ID" value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['TYPE'] : "" : "" ?>"/>
                                        
                                        <div class="input-group-append"><span class="input-group-text" id="basic-addon2">
                                        <i class="fa fa-book"></i></span></div>
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
                        <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">طبيعة الاشتراك</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control TXT_REQUEST_APP_TYPE_NAME" id="TXT_REQUEST_APP_TYPE_NAME"
                                         name="TXT_REQUEST_APP_TYPE_NAME" placeholder="طبيعة الاشتراك" tabindex="20"  readonly 
                                         value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['SUB_TYPE_NAME'] : "" : "" ?>" />
                                         
                                         <input type="hidden" class="form-control TXT_REQUEST_APP_TYPE" id="TXT_REQUEST_APP_TYPE"
                                        name="TXT_REQUEST_APP_TYPE" 
                                        value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['TYPE'] : "" : "" ?>" />
                                        <div class="input-group-append"><span class="input-group-text" id="basic-addon2">
                                        <i class="fa fa-book"></i></span></div>
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">رقم الموقع</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control TXT_LOCATION_NO" id="TXT_LOCATION_NO" name="TXT_LOCATION_NO" 
                                        placeholder="رقم الموقع" tabindex="21"  readonly 
                                        value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['LOCATION_NO'] : "" : "" ?>" />
                                        <div class="input-group-append"><span class="input-group-text" id="basic-addon2">
                                        <i class="fa fa-book"></i></span></div>
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3"><label for="example-text-input" 
                        class="col-12 col-form-label">التصنيف الرئيسي</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control TXT_USE_TYPE_NAME" id="TXT_USE_TYPE_NAME" 
                                        name="TXT_USE_TYPE_NAME" placeholder="التصنيف الرئيسي" tabindex="22"  readonly 
                                        value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['USE_TYPE_NAME'] : "" : "" ?>" />
                                        
                                        <input type="hidden" class="form-control TXT_USE_TYPE" id="TXT_USE_TYPE" name="TXT_USE_TYPE"  
                                        value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['USE_TYPE'] : "" : "" ?>" />
                                        <div class="input-group-append"><span class="input-group-text" id="basic-addon2">
                                        <i class="fa fa-book"></i></span></div>
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">التصنيف الفرعي</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control TXT_ORG_NAME" id="TXT_ORG_NAME" name="TXT_ORG_NAME" 
                                        placeholder="التصنيف الفرعي" tabindex="23"  readonly 
                                        value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['ORG_NAME'] : "" : "" ?>" />
                                        
                                        <input type="hidden" class="form-control TXT_ORG" id="TXT_ORG" name="TXT_ORG"  
                                        value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['ORG'] : "" : "" ?>" />
                                        <div class="input-group-append"><span class="input-group-text" id="basic-addon2">
                                        <i class="fa fa-book"></i></span></div>
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
                        <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">نوع الربط</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control TXT_LINK_TYPE_NAME" id="TXT_LINK_TYPE_NAME" 
                                        name="TXT_LINK_TYPE_NAME" placeholder="نوع الربط" tabindex="24"  readonly 
                                        value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['LINK_TYPE_NAME'] : "" : "" ?>" />
                                        
                                        <input type="hidden" id="TXT_LINK_TYPESS" name="TXT_LINK_TYPESS" 
                                        value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['LINK_TYPE'] : "" : "" ?>" />
                                        <div class="input-group-append"><span class="input-group-text" id="basic-addon2">
                                        <i class="fa fa-book"></i></span></div>
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">قوة الاشتراك</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control TXT_AMBIR_NAME" id="TXT_AMBIR_NAME" name="TXT_AMBIR_NAME" 
                                        placeholder="قوة الاشتراك" tabindex="25"  readonly value="<?php
                                        	if($HaveRs){
												if(count($prog) > 0){
													if($prog[0]['AMBIR'] == 0){
														echo "40 A";
													}else{
														echo $prog[0]['AMBIR_NAME'];
													}
												}else{
													echo "";
												}
											}else{
											  echo "";
											}
										?>" />
                                        
                                        <input type="hidden" class="form-control TXT_AMBIR" id="TXT_AMBIR" name="TXT_AMBIR"  
                                        value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['AMBIR'] : "" : "" ?>" />
                                        <div class="input-group-append"><span class="input-group-text" id="basic-addon2">
                                        <i class="fa fa-book"></i></span></div>
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">المحافظة/البلدية</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control TXT_DISTRICT" id="TXT_DISTRICT" name="TXT_DISTRICT" 
                                        placeholder="المحافظة/البلدية" tabindex="26"  readonly 
                                        value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['DISTRICT_NAME'] : "" : "" ?>" />
                                        
                                        <input type="hidden" class="form-control TXT_DISTRICT_ID" id="TXT_DISTRICT_ID" 
                                        name="TXT_DISTRICT_ID" 
                                        value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['DISTRICT'] : "" : "" ?>" />
                                        
                                        <div class="input-group-append"><span class="input-group-text" id="basic-addon2">
                                        <i class="fa fa-book"></i></span></div>
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">العنوان</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control TXT_BLOCK_CODE" id="TXT_BLOCK_CODE" name="TXT_BLOCK_CODE" 
                                        placeholder="العنوان" tabindex="27"  readonly 
                                        value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['ADDRESS'] : "" : "" ?>" />
                                        <div class="input-group-append"><span class="input-group-text" id="basic-addon2">
                                        <i class="fa fa-book"></i></span></div>
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
                        <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">تاريخ الإشتراك</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control TXT_SUBSCRIBE_DATE" id="TXT_SUBSCRIBE_DATE" 
                                        name="TXT_SUBSCRIBE_DATE" placeholder="تاريخ الإشتراك" tabindex="28"  readonly 
                                        value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['SUBSCRIBE_DATE'] : "" : "" ?>" />
                                        <div class="input-group-append"><span class="input-group-text" id="basic-addon2">
                                        <i class="fa fa-book"></i></span></div>
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">التعرفة</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control TXT_TAREFA" id="TXT_TAREFA" name="TXT_TAREFA" 
                                        placeholder="التعرفة" tabindex="29"  readonly 
                                        value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['KW_PRICE'] : "" : "" ?>" />
                                        <div class="input-group-append"><span class="input-group-text" id="basic-addon2">
                                        <i class="fa fa-book"></i></span></div>
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">الرسم الثابت</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control TXT_RASEM" id="TXT_RASEM" name="TXT_RASEM" 
                                        placeholder="الرسم الثابت" tabindex="30"  readonly 
                                        value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['SUBSCRIBE_FEES'] : "" : "" ?>" />
                                        <div class="input-group-append"><span class="input-group-text" id="basic-addon2">
                                        <i class="fa fa-book"></i></span></div>
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">حالة الإشتراك</label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control TXT_IS_ACTIVE" id="TXT_IS_ACTIVE" name="TXT_IS_ACTIVE" 
                                        placeholder="حالة الإشتراك" tabindex="31"  readonly 
                                        value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['IS_ACTIVE_NAME'] : "" : "" ?>" />
                                        <div class="input-group-append"><span class="input-group-text" id="basic-addon2">
                                        <i class="fa fa-book"></i></span></div>
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                    </div>
                    <!--End :: row---->
                    <hr/>
                    <!----------ChangeTNa---------->
                    <div class="row">
                       <div class="col-md-12 ChangeTNa" style="display:none;">
                        	<?php require_once('subsc_req/update_one.php'); ?>
                        </div>
                    </div>   
                    <!----------ChangeTNa---------->
                    
                    <!----------CorrectName---------->
                    <div class="row">
                        <div class="col-md-12 CorrectName" style="display:none;">
                        	<?php require_once('subsc_req/update_two.php'); ?>
                    	</div>
                    </div>
                    <!----------CorrectName---------->
                    
                    <!----------CorrectAddress---------->
                    <div class="row">
                        <div class="col-md-12 CorrectAddress" style="display:none;">
                        	<?php require_once('subsc_req/update_three.php'); ?>
                        </div>
                    </div>
                    <!----------CorrectAddress---------->
                    
                    <!----------LinkType---------->
                    <div class="row">
                        <div class="col-md-12 LinkType" style="display:none;">
                        	<?php require_once('subsc_req/update_four.php'); ?>
                        </div>
                    </div>
                    <!----------LinkType---------->
                    
                    <?php  if(HaveAccess($post_url_A)) { ?>
                    <!--Begin :: row---->
                    <div class="row">
                    	<div class="form-group col-md-5">
                        </div>
                    	<div class="form-group col-md-5">
                                <label for="example-text-input"  class="col-8 col-form-label"></label>
                                <div class="col-12">
                                    <button type="button" class="btn btn-success"  
                                    id="btn_add_A_<?= $TABLE_NAME; ?>" >حفظ</button>
                                </div>
                         </div>
                    </div>
                    <!--End :: row---->
                    <?php } ?>
                </div>
            </form>
            <!--End::Form-->
        </div>
    </div>
    <!--end::Portlet-->
</div>
<!-- end:: Content -->
