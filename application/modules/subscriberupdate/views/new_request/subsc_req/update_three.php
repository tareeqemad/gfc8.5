<!--Begin :: row---->
<div class="row">
	<!-------->
    <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">المحافظة/البلدية
    <span class="required" style="color:#F00;">*</span></label>
            <div class="col-12">
                <div class="input-group">
                    <?php  if($this->user->branch != 1) {
						echo form_dropdown("DL_DISTRICTS",$DISTRICTS,
						$HaveRs ? ($rs['SERVICE_ID'] == 17 or $rs['SERVICE_ID'] ==18) ? $HaveRse ? $res['DISTRICT'] : "" : "" : "",
						'class="form-control kt-select2" id = "DL_DISTRICTS"');
					}else{?>
					<select class="form-control" name="DL_DISTRICT_NULL" id="DL_DISTRICT_NULL" tabindex="55"  ></select>
					<?php } ?>
                    <div id="help-block"></div>
                </div>
            </div>
    </div>
    <!-------->
    <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">رقم القطعة
    <span class="required" style="color:#F00;">*</span></label>
            <div class="col-12">
                <div class="input-group">
                    <input type="text" class="form-control TXT_BLOCK_CODE" id="TXT_BLOCK_CODE" 
                    name="TXT_BLOCK_CODE" placeholder="رقم القطعة" tabindex="56" 
                    value="<?= $HaveRs ? ($rs['SERVICE_ID'] == 17 or $rs['SERVICE_ID'] ==18) ? 
					$HaveRse ? $res['BLOCK_CODE'] : "" : "" : "" ?>" />
                    <div id="help-block"></div>
                </div>
            </div>
    </div>
    <!-------->
    <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">رقم القسيمة
    <span class="required" style="color:#F00;">*</span></label>
            <div class="col-12">
                <div class="input-group">
                    <input type="text" class="form-control TXT_POS_CODE valid_number" id="TXT_POS_CODE" 
                    name="TXT_POS_CODE" placeholder="رقم القسيمة" tabindex="57" 
                    value="<?= $HaveRs ? ($rs['SERVICE_ID'] == 17 or $rs['SERVICE_ID'] ==18) ? 
					$HaveRse ? $res['POS_CODE'] : "" : "" : "" ?>" />
                    <div id="help-block"></div>
                </div>
            </div>
    </div>
    <!-------->
    <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">رقم البناية/المنزل
    <span class="required" style="color:#F00;">*</span></label>
            <div class="col-12">
                <div class="input-group">
                    <input type="text" class="form-control TXT_HOUSE_NO valid_number" id="TXT_HOUSE_NO" 
                     name="TXT_HOUSE_NO" placeholder="رقم البناية/المنزل" tabindex="58" 
                     value="<?= $HaveRs ? ($rs['SERVICE_ID'] == 17 or $rs['SERVICE_ID'] ==18) ? 
					 $HaveRse ? $res['HOUSE_NO'] : "" : "" : "" ?>" />
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
    <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">اسم البناية
    <span class="required" style="color:#F00;">*</span></label>
            <div class="col-12">
                <div class="input-group">
                    <input type="text" class="form-control TXT_HOUSE_NAME valid_name" id="TXT_HOUSE_NAME" 
                    name="TXT_HOUSE_NAME" placeholder="اسم البناية" tabindex="59" 
                    value="<?= $HaveRs ? ($rs['SERVICE_ID'] == 17 or $rs['SERVICE_ID'] ==18) ? 
					$HaveRse ? $res['HOUSE_NAME'] : "" : "" : "" ?>" />
                    <div id="help-block"></div>
                </div>
            </div>
    </div>
    <!-------->
    <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">الشارع/الحي
    <span class="required" style="color:#F00;">*</span></label>
            <div class="col-12">
                <div class="input-group">
                    <input type="text" class="form-control TXT_ADDRESS valid_name" id="TXT_ADDRESS" 
                    name="TXT_ADDRESS" placeholder="الشارع/الحي" tabindex="60" 
                    value="<?= $HaveRs ? ($rs['SERVICE_ID'] == 17 or $rs['SERVICE_ID'] ==18) ? 
					$HaveRse ? $res['ADDRESS'] : "" : "" : "" ?>" />
                    <div id="help-block"></div>
                </div>
            </div>
    </div>
    <!-------->
    <div class="form-group col-md-6"><label for="example-text-input" class="col-12 col-form-label">رقم اشتراك الجار
    <span class="required" style="color:#F00;">*</span></label>
            <div class="col-12">
                <div class="input-group">
                   <input type="text" class="form-control TXT_NEIGHBOR_NO" id="TXT_NEIGHBOR_NO"  tabindex="61" 
                     name="TXT_NEIGHBOR_NO" placeholder="رقم اشتراك الجار"  ondblclick="ShowNEIGHBOR_NO();"
                     value="<?= $HaveRs ? ($rs['SERVICE_ID'] == 17 or $rs['SERVICE_ID'] ==18) ? 
					 $HaveRse ? $res['NEIGHBOR_NO'] : "" : "" : "" ?>" />
                   <div id="help-block"></div>
                </div>
            </div>
    </div>
    <!-------->
    <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">رقم موقع الجار
    <span class="required" style="color:#F00;">*</span></label>
            <div class="col-12">
                <div class="input-group">
                    <input type="text" class="form-control TXT_NEIGHBOR_LOC" id="TXT_NEIGHBOR_LOC" tabindex="62"  
                     name="TXT_NEIGHBOR_LOC" placeholder="رقم موقع الجار" 
                     value="<?= $HaveRs ? ($rs['SERVICE_ID'] == 17 or $rs['SERVICE_ID'] ==18) ? 
					 $HaveRse ? $res['NEIGHBOR_LOC'] : "" : "" : "" ?>" />
                    
                    
                    <div id="help-block"></div>
                </div>
            </div>
    </div>
    <!-------->
</div>
<!--End :: row---->