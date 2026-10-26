<!--Begin :: row---->
<div class="row">
    <!-------->
    <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">هوية المشترك الجديد
    <span class="required" style="color:#F00;">*</span></label>
            <div class="col-12">
                <div class="input-group">
                    <input type="text" class="form-control TXT_SSN_NEW" id="TXT_SSN_NEW"  name="TXT_SSN_NEW" 
                    placeholder="رقم هوية المشترك الجديد"  tabindex="42" maxlength="9" 
                    value="<?= $HaveRs ? ($rs['SERVICE_ID'] == 11 or $rs['SERVICE_ID'] ==12  or $rs['SERVICE_ID'] ==13  or 
					$rs['SERVICE_ID'] ==14) ? $HaveRse ? $res['NEW_SUBSCRIBER_ID'] : "" : "" : "" ?>" />
                    <div class="input-group-append"><span class="input-group-text" id="basic-addon2"><i class="fa fa-book"></i></span></div>
                    <div id="help-block"></div>
                </div>
            </div>
    </div>
    <!-------->
    <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">اسم المشترك الجديد
    <span class="required" style="color:#F00;">*</span></label>
            <div class="col-12">
                <div class="input-group">
                    <input type="text" class="form-control TXT_NAME_NEW valid_name" id="TXT_NAME_NEW" 
                    name="TXT_NAME_NEW" placeholder="اسم المشترك الجديد" tabindex="43" 
                    value="<?= $HaveRs ? ($rs['SERVICE_ID'] == 11 or $rs['SERVICE_ID'] ==12  or $rs['SERVICE_ID'] ==13 
                     or $rs['SERVICE_ID'] ==14) ? $HaveRse ? $res['NEW_SUBSCRIBER_NAME'] : "" : "" : "" ?>" />
                     
                     <input type="hidden" name="TXT_SER" 
                    value="<?= $HaveRs ? ($rs['SERVICE_ID'] == 11 or $rs['SERVICE_ID'] ==12  or $rs['SERVICE_ID'] ==13 
                     or $rs['SERVICE_ID'] ==14) ? $HaveRse ? $res['SER'] : "" : "" : "" ?>" />
                    <div class="input-group-append"><span class="input-group-text" id="basic-addon2"><i class="fa fa-book"></i></span></div>
                    <div id="help-block"></div>
                </div>
            </div>
    </div>
    <!-------->
    <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">اسم المنتفع الجديد
    <span class="required" style="color:#F00;">*</span></label>
            <div class="col-12">
                <div class="input-group">
                    <input type="text" class="form-control TXT_B_NAME_NEW valid_name" id="TXT_B_NAME_NEW" 
                    name="TXT_B_NAME_NEW" placeholder="اسم المنتفع الجديد" tabindex="44"  
                    value="<?= $HaveRs ? ($rs['SERVICE_ID'] == 11 or $rs['SERVICE_ID'] ==12  or $rs['SERVICE_ID'] ==13 
                     or $rs['SERVICE_ID'] ==14) ? $HaveRse ? $res['NEW_BENIFICARY_NAME'] : "" : "" : "" ?>" />
                    <div class="input-group-append"><span class="input-group-text" id="basic-addon2"><i class="fa fa-book"></i></span></div>
                    <div id="help-block"></div>
                </div>
            </div>
    </div>
    <!-------->
    <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">المهنة
    <span class="required" style="color:#F00;">*</span></label>
            <div class="col-12">
                <div class="input-group">
                     <?= form_dropdown("DL_JOB",$JOBS,$HaveRs ? ($rs['SERVICE_ID'] == 11 or $rs['SERVICE_ID'] ==12 or 
					 $rs['SERVICE_ID'] ==13 or $rs['SERVICE_ID'] ==14) ? $HaveRse ? $res['JOB'] : "" : "" : "",
					 'class="form-control kt-select2" id = "DL_JOB" tabindex="45"');?>
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
    <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">صفة الملتزم بالاتفاقية
    <span class="required" style="color:#F00;">*</span></label>
            <div class="col-12">
                <div class="input-group">
                    <?= form_dropdown("DL_AGREEMENT",$AGREEMENT,$HaveRs ? ($rs['SERVICE_ID'] == 11 or $rs['SERVICE_ID'] ==12 or 
					$rs['SERVICE_ID'] ==13 or $rs['SERVICE_ID'] ==14) ? $HaveRse ? $res['AGREEMENT_SUB_SELF'] : "" : "" : "",
					'class="form-control kt-select2" id = "DL_AGREEMENT" tabindex="46"');?>
                    <div id="help-block"></div>
                </div>
            </div>
    </div>
    <!-------->
    <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">رقم هوية الملتزم بالاتفاقية
    <span class="required" style="color:#F00;">*</span></label>
            <div class="col-12">
                <div class="input-group">
                    <input type="text" class="form-control TXT_SSN_AGREEMENT" id="TXT_SSN_AGREEMENT" 
                    name="TXT_SSN_AGREEMENT" placeholder="رقم هوية الملتزم بالاتفاقية" tabindex="47" maxlength="9" 
                    value="<?= $HaveRs ? ($rs['SERVICE_ID'] == 11 or $rs['SERVICE_ID'] ==12  or $rs['SERVICE_ID'] ==13 or 
					$rs['SERVICE_ID'] ==14) ? $HaveRse ? $res['AGREEMENT_SUB_ID'] : "" : "" : "" ?>" />
                    <div class="input-group-append"><span class="input-group-text" id="basic-addon2"><i class="fa fa-book"></i></span></div>
                    <div id="help-block"></div>
                </div>
            </div>
    </div>
    <!-------->
    <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">اسم الملتزم بالاتفاقية
    <span class="required" style="color:#F00;">*</span></label>
            <div class="col-12">
                <div class="input-group">
                    <input type="text" class="form-control TXT_NAME_AGREEMENT valid_name" id="TXT_NAME_AGREEMENT" 
                    name="TXT_NAME_AGREEMENT" placeholder="اسم الملتزم بالاتفاقية" tabindex="48" 
                    value="<?= $HaveRs ? ($rs['SERVICE_ID'] == 11 or $rs['SERVICE_ID'] ==12  or $rs['SERVICE_ID'] ==13 
                     or $rs['SERVICE_ID'] ==14) ? $HaveRse ? $res['AGREEMENT_SUB_NAME'] : "" : "" : "" ?>" />
                    <div class="input-group-append"><span class="input-group-text" id="basic-addon2"><i class="fa fa-book"></i></span></div>
                    <div id="help-block"></div>
                </div>
            </div>
    </div>
    <!-------->
    <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">رقم الملف القديم
    <span class="required" style="color:#F00;">*</span></label>
            <div class="col-12">
                <div class="input-group">
                    <input type="text" class="form-control TXT_O_FILE_NO valid_name" id="TXT_O_FILE_NO" name="TXT_O_FILE_NO" 
                    placeholder="رقم الملف القديم" tabindex="49" readonly />
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
    <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">رقم الملف الجديد
    <span class="required" style="color:#F00;">*</span></label>
            <div class="col-12">
                <div class="input-group">
                    <input type="text" class="form-control TXT_N_FILE_NO valid_name" id="TXT_N_FILE_NO" 
					name="TXT_N_FILE_NO" placeholder="رقم الملف الجديد" tabindex="50" value="0" readonly />
                    <div id="help-block"></div>
                </div>
            </div>
    </div>
    <!-------->
    <!-------->
    <div class="form-group col-md-6"><label for="example-text-input" class="col-12 col-form-label">سبب التغيير/ملاحظات
    <span class="required" style="color:#F00;">*</span></label>
            <div class="col-12">
                <div class="input-group">
                    <input type="text" class="form-control TXT_CHANGE_REASON valid_text" id="TXT_CHANGE_REASON" 
                    name="TXT_CHANGE_REASON" placeholder="سبب التغيير" tabindex="51" 
                    value="<?= $HaveRs ? ($rs['SERVICE_ID'] == 11 or $rs['SERVICE_ID'] ==12  or $rs['SERVICE_ID'] ==13 
                     or $rs['SERVICE_ID'] ==14) ? $HaveRse ? $res['CHANGE_REASON'] : "" : "" : "" ?>" />
                     <div class="input-group-append"><span class="input-group-text" id="basic-addon2"><i class="fa fa-book"></i></span></div>
                    <div id="help-block"></div>
                </div>
            </div>
    </div>
    <!-------->
</div>
<!--End :: row---->