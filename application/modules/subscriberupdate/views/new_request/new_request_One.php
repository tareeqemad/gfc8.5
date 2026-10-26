<?php

$MODULE_NAME= 'subscriberupdate';
$TABLE_NAME= 'RequestUpdate';
$post_url= base_url("$MODULE_NAME/$TABLE_NAME/create");
$edit_url= base_url("$MODULE_NAME/$TABLE_NAME/edit");
?>
<!-- begin:: Content -->
<div class="cards-body">
    <div class="cards">
        <div class="card-body">
            <!--begin::Form-->
            <form id="<?= $TABLE_NAME; ?>_form" name="<?= $TABLE_NAME; ?>_form"  class="horizontal-form <?= $TABLE_NAME; ?>_form"
            <?php if($HaveRs) { ?> action="<?= $edit_url; ?>" <?php }else{ ?> action="<?= $post_url; ?>" <?php } ?>  
            method="post" role="form" novalidate="novalidate"> 
                    <!--Begin :: row---->
                    <div class="form-row">
                        <!-------->
                        <div class="form-group col-md-2">
                            <label for="txt_project_serial" style="color: red;">رقم الطلب</label>
                            <input type="text" class="form-control" id="TXT_REQUEST_APP_SERIAL" name="TXT_REQUEST_APP_SERIAL" 
                            value="<?= $HaveRs ? $rs['REQUEST_APP_SERIAL'] : 0 ?>" readonly />
                        </div>
                        <!-------->
                        <div class="form-group col-md-2">
                            <label for="txt_project_serial">التاريخ</label>
                            <input type="text" class="form-control" id="TXT_REQUEST_DATE" name="TXT_REQUEST_DATE" 
                            readonly value="<?= $HaveRs ? $rs['REQUEST_APP_DATE'] : date('Y/m/d') ?>"/>
                        </div>
                        <!-------->
                        <div class="form-group col-md-2">
                            <label for="txt_project_serial" style="color: red;">رقم الاشتراك
                            <span class="required" style="color:#F00;">*</span></label>
                            <input type="text" class="form-control" id="TXT_SUBSCRIBER" name="TXT_SUBSCRIBER" 
                            value="<?= $HaveRs ? $rs['SUBSCRIBER'] : '' ?>" tabindex="1" />
                        </div>
                        <!-------->
                        <div class="form-group col-md-2">
                            <label for="txt_project_serial">اسم المشترك</label>
                            <input type="text" placeholder="الاسم المشترك" class="form-control" 
                            id="txt_fname" name="txt_fname" readonly style="color:red;"
                            value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['NAME'] : "" : "" ?>"/>
                        </div>
                        <!-------->
                        <div class="form-group col-md-2">
                            <label for="txt_project_serial">نوع الإشتراك</label>
                            <input type="text" placeholder="نوع الإشتراك" class="form-control" 
                            id="txt_type_n" name="txt_type_n" readonly style="color:red;"
                            value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['TYPE_NAME'] : "" : "" ?>"/>
                        </div>
                        <!-------->
                        <div class="form-group col-md-2">
                            <label for="txt_project_serial">تسلسل الاشتراك</label>
                            <input type="text" class="form-control" id="TXT_REQUEST_ID" name="TXT_REQUEST_ID" 
                            value="<?= $HaveRs ? $rs['REQUEST_ID'] : 0 ?>" readonly />
                        </div>
                        <!-------->
                    </div>
                    <!--End :: row---->
                    <!--Begin :: row---->
                    <div class="row">
                        <!-------->
                        <div class="form-group col-md-2">
                            <label for="txt_project_serial">مقدم الطلب
                            <span class="required" style="color:#F00;">*</span></label>
                            <?= form_dropdown("DL_APPLICANT",$APPLICANT,$HaveRs ? $rs['APPLICANT'] : 1,
                            'class="form-control" id = "DL_APPLICANT" tabindex="1"');?>
                        </div>
                        <!-------->
                        <div class="form-group col-md-2">
                            <label for="txt_project_serial">نوع الوثيقة
                            <span class="required" style="color:#F00;">*</span></label>
                            <?= form_dropdown("DL_DOCUMENT_TYPE",$DOCUMENT_TYPE,$HaveRs ? $rs['DOCUMENT_TYPE'] : 1,
                            'class="form-control" id = "DL_DOCUMENT_TYPE" tabindex="2"');?>
                        </div>
                        <!-------->
                        <div class="form-group col-md-2">
                            <label for="txt_project_serial" style="color: red;">الرقم التعريفي
                            <span class="required" style="color:#F00;">*</span></label>
                            <div class="col-12">
                                <div class="input-group">
                            <input type="text" class="form-control valid_phone" id="kt_maxlength_41" 
                            name="TXT_ID_APP" maxlength="9" tabindex="3" value="<?= $HaveRs ? $rs['APPLICANT_ID'] : "" ?>" />
                                </div>
                            </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-4">
                            <label for="txt_project_serial">إسم مقدم الطلب</label>
                            <div class="col-12">
                                <div class="input-group">
                            <input type="text" class="form-control valid_name" id="TXT_APPLICANT_NAME" style="color:red;" 
                            name="TXT_APPLICANT_NAME" tabindex="4" value="<?= $HaveRs ? $rs['APPLICANT_NAME'] : "" ?>" />
                                </div>
                            </div>
                        </div>
                        <!-------->
                        <?php 
							if($HaveRs){
								$rert_ssn = $this->Rmodel->getID('SERVICE_NEW','NAME_LOOK_TB_GET',$rs['APPLICANT_ID']);
							} 
						?>
                        <div class="form-group col-md-2">
                            <label for="txt_project_serial">الحالة الاجتماعية</label>
                            <div class="col-12">
                                <div class="input-group">
                            <input type="text" class="form-control TXT_RELATIONSHIP" id="TXT_RELATIONSHIP" 
                            name="TXT_RELATIONSHIP" placeholder="الحالة الاجتماعية" readonly 
                            value="<?=  $HaveRs ? count($rert_ssn) > 0 ? $rert_ssn[0]['RELATIONSHIP'] : "" : "" ?>" />
                                </div>
                            </div>
                        </div>
                        <!-------->
                    </div>
                    <!--End :: row---->
                    <!--Begin :: row---->
                    <div class="row">
                    	<!-------->
                        <div class="form-group col-md-2">
                        <label for="example-text-input" class="col-12 col-form-label">الموبايل
                                <span class="required" style="color:#F00;">*</span></label>
                                <div class="col-12">
                                   <div class="input-group">
                                        <input type="text" class="form-control" id="kt_maxlength_4" name="TXT_JAWWAL" 
                                        maxlength="7" tabindex="5" 
                                        value="<?= $HaveRs ? $rs['JAWWAL'] : "" ?>" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fa fa-phone"></i>
                                            </span>
                                        </div>
                                        <div id="help-block"></div>
                                    </div> 
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-2">
                        <label for="example-text-input" class="col-12 col-form-label">المقدمة
                                <span class="required" style="color:#F00;">*</span></label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <?= form_dropdown("TXT_PRVX",$PIN_CODE,$HaveRs ? $rs['PREFIX'] : 1,
                                        'class="form-control kt-select2" id = "TXT_PRVX" tabindex="6"');?>
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-2">
                        <label for="example-text-input" class="col-12 col-form-label">الهاتف
                                </label>
                                <div class="col-12">
                                   <div class="input-group">
                                       <input type="text" class="form-control valid_phone" id="kt_maxlength_41" 
                                       name="TXT_TEL" maxlength="9" tabindex="7" 
                                       value="<?= $HaveRs ? $rs['TEL'] : "" ?>" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fa fa-phone"></i>
                                            </span>
                                        </div>
                                        <div id="help-block"></div>
                                    </div> 
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3">
                        <label for="example-text-input" class="col-12 col-form-label">البريد الإلكتروني
                                </label>
                                <div class="col-12">
                                   <div class="input-group">
                                       <input type="text" class="form-control valid_email" id="TXT_EMAIL" name="TXT_EMAIL" tabindex="8" 
                                       value="<?= $HaveRs ? $rs['EMAIL'] : "" ?>" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fa fa-book"></i>
                                            </span>
                                        </div>
                                        <div id="help-block"></div>
                                    </div> 
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3">
                        <label for="example-text-input" class="col-12 col-form-label">مهنة مقدم الطلب
                                <span class="required" style="color:#F00;">*</span></label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <?= form_dropdown("DL_APPLICANT_JOB",$JOBS,$HaveRs ? $rs['APPLICANT_JOB'] : '',
                                        'class="form-control kt-select2" id = "DL_APPLICANT_JOB" tabindex="9"');?>
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
                        <div class="form-group col-md-2">
                            <label for="example-text-input" class="col-12 col-form-label">نوع الفاز
                            <span class="required" style="color:#F00;">*</span></label>
                            <div class="col-12">
                                <input type="text" class="form-control" id="TXT_PHASE_TYPE" 
                                name="TXT_PHASE_TYPE" tabindex="10" readonly
                                value="<?= $HaveRs ? $rs['PHASE_TYPE_NAME'] : "" ?>" />
                                
                                <input type="hidden" class="form-control" id="DL_PHASE" 
                                name="DL_PHASE" tabindex="10" 
                                value="<?= $HaveRs ? $rs['PHASE_TYPE'] : "" ?>" />
                                <div id="help-block"></div>
                            </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3">
                            <label for="example-text-input" class="col-12 col-form-label" style="color: red;">الخدمة
                            <span class="required" style="color:#F00;">*</span></label>
                            <div class="col-12">
                                <select name="DL_SERVICE_ID" id="DL_SERVICE_ID" class="form-control kt-select2" tabindex="11">
                                	<?php
									if($HaveRs){
										$this->Rmodel->package = 'SERVICES_T_PKG' ;
										$rets = $this->Rmodel->getTwoColum('SERVICE_GET_BY_TYPE_PHASE',$rs['SERVICE_TYPE'],$rs['PHASE_TYPE']);
										if(count($rets) > 0){
								?>
                                <option value="0">---اختر---</option>
                                <?php foreach($rets as $rows){ ?>
                                <option value="<?php echo $rows['SERVICE_ID'] ?>" <?php if($rs['SERVICE_ID'] == $rows['SERVICE_ID']) { ?> selected="selected" <?php } ?>>
								<?php echo $rows['SERVICE_NAME'] ?></option>
                                <?php } } }else{ ?>
                                	<option value="0">---اختر---</option>
                                <?php } ?>
                                </select>
                                <div id="help-block"></div>
                            </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-2">
                            <label for="example-text-input" class="col-12 col-form-label">آلية السداد
                            <span class="required" style="color:#F00;">*</span></label>
                            <div class="col-12">
                                <input type="text" class="form-control TXT_PAYMENT_WAY" id="TXT_PAYMENT_WAY" name="TXT_PAYMENT_WAY" 
                                placeholder="آلية السداد" tabindex="12"  readonly 
                                value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['PAYMENT_WAY_NO_NAME'] : "" : "" ?>" />
                                <input type="hidden" name="TXT_TYPE_PAID_H" id="TXT_TYPE_PAID_H" tabindex="12" 
                                value="<?= $HaveRs ? count($prog) > 0 ? $prog[0]['PAYMENT_WAY_NO'] : "" : "" ?>" />
                                <div id="help-block"></div>
                            </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-3">
                            <label for="example-text-input" class="col-12 col-form-label">المنطقة الصغرى
                            <span class="required" style="color:#F00;">*</span></label>
                            <div class="col-12">
                                <?= form_dropdown("DL_Junior_No",$BRANCH_JUNIOR,
								$HaveRs ? $rs['JUNIOR_REGION'] : '','class="form-control  kt-select2" id = "DL_Junior_No" 
								tabindex="13"');?>
                                <div id="help-block"></div>
                            </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-2">
                            <label for="example-text-input" class="col-12 col-form-label">المنطقة الكبرى
                            <span class="required" style="color:#F00;">*</span></label>
                            <div class="col-12">
                                <select name="DL_Great_R_No" id="DL_Great_R_No" class="form-control kt-select2" tabindex="14">
                                	<?php
											if($HaveRs && $rs['JUNIOR_REGION'] != ''){
												$this->Rmodel->package = 'TSG_SETTING_PKG' ;
												$retGenio = $this->Rmodel->getTwoColum('CONSTANTS_DETAIL_BRANCH_GET',20,$this->user->branch);
												if(count($retGenio) > 0){
									 ?>
									 <option value="0">---اختر---</option>
                                     <?php   
									 	foreach($retGenio as $rowGeni){
												$this->Rmodel->package = 'TSG_SETTING_PKG' ;
												$retLarge = $this->Rmodel->getTwoColum('CONSTANT_DETAILS_TB_GET_NAMES',21,$rs['JUNIOR_REGION']);
												foreach($retLarge as $rowLarg){
													if($rowLarg['BRANCH'] == $rowGeni['VID']){
									 ?>
                                     <option value="<?php echo $rowGeni['VID']; ?>" <?php if($rs['GREAT_REGION'] == $rowGeni['VID']) { ?> selected="selected" <?php } ?>>
									 <?php echo $rowGeni['VNAME']; ?></option>
                                     <?php } } } } }else{ ?>
                                        <option value="0">---اختر---</option>
                                     <?php } ?>
                                </select>
                                <div id="help-block"></div>
                            </div>
                        </div>
                        <!-------->
                    </div>
                    <!--End :: row---->
                    <!--Begin :: row---->
                    <div class="row">
                    	<!-------->
                        <div class="form-group col-md-6">
                        <label for="example-text-input" class="col-12 col-form-label">ملاحظات
                                <span class="required" style="color:#F00;">*</span></label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control valid_text" id="TXT_DECLARATION" 
                                        name="TXT_DECLARATION" tabindex="15" 
                                        value="<?= $HaveRs ? $rs['DECLARATION'] : "" ?>" />
                                        <div class="input-group-append"><span class="input-group-text" id="basic-addon2">
                                        <i class="fa fa-book"></i></span></div>
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                        <div class="form-group col-md-6">
                        <label for="example-text-input" class="col-12 col-form-label">سبب الإرجاع
                                </label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="text" class="form-control valid_text" id="TXT_DECLARATION_RETURN" 
                                        name="TXT_DECLARATION_RETURN" tabindex="16" 
                                        value="<?= $HaveRs ? $STAGE_REASON : "" ?>" readonly />
                                        <div class="input-group-append"><span class="input-group-text" id="basic-addon2">
                                        <i class="fa fa-book"></i></span></div>
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                    </div>
                    <!--End :: row---->
                    <?php  if(HaveAccess($post_url)) { ?>
                    <!--Begin :: row---->
                    <div class="row">
                    	<div class="form-group col-md-5">
                        </div>
                    	<div class="form-group col-md-5">
                                <label for="example-text-input"  class="col-8 col-form-label"></label>
                                <div class="col-12">
                                    <button type="button" class="btn btn-success"  
                                    id="btn_add_<?= $TABLE_NAME; ?>" >حفظ</button>
                                </div>
                         </div>
                    </div>
                    <!--End :: row---->
                    <?php } ?> 
            </form>
            <!--End::Form-->
        </div> <!--end inside cards body -->
    </div><!--end inside cards -->
</div> <!--end card body -->
<!-- end:: Content -->
