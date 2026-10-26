<!--Begin :: row---->
<div class="row">
	<!-------->
    <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">المهنة
    <span class="required" style="color:#F00;">*</span></label>
            <div class="col-12">
                <div class="input-group">
                    <?= form_dropdown("DL_JOBS",$JOBS,
                    $HaveRs ? ($rs['SERVICE_ID'] == 19 or $rs['SERVICE_ID'] ==20  or $rs['SERVICE_ID'] ==21 or $rs['SERVICE_ID'] ==22) ? 
					$HaveRse ? $res['JOB'] : "" : "" : "",
                    'class="form-control kt-select2" id = "DL_JOBS" tabindex="63"');?>
                    <div id="help-block"></div>
                </div>
            </div>
    </div>
    <!-------->
    <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">التصنيف الرئيسي الجديد
    <span class="required" style="color:#F00;">*</span></label>
            <div class="col-12">
                <div class="input-group">
                    <?= form_dropdown("DL_NEW_USE_TYPE",$use_types,
                    $HaveRs ? ($rs['SERVICE_ID'] == 19 or $rs['SERVICE_ID'] ==20  or $rs['SERVICE_ID'] ==21 or $rs['SERVICE_ID'] ==22) ? 
					$HaveRse ? $res['NEW_USE_TYPE'] : "" : "" : "",
					'class="form-control kt-select2" id = "DL_NEW_USE_TYPE" tabindex="65"');?>
                    <div id="help-block"></div>
                </div>
            </div>
    </div>
    <!-------->
    <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">التصنيف الفرعي الجديد
    <span class="required" style="color:#F00;">*</span></label>
            <div class="col-12">
                <div class="input-group">
                    <select name="DL_NEW_ORG" class="form-control kt-select2" id="DL_NEW_ORG" tabindex="66">
                    <?php
                        if($HaveRs && $HaveRse){ 
							$new_use_type = $res['NEW_USE_TYPE'];
							$this->Rmodel->package = 'TSG_SETTING_PKG' ;
							$retGenio = $this->Rmodel->getTwoColum('CONSTANTS_DETAIL_BRANCH_GET',12,$new_use_type);
							
							if(count($retGenio) > 0){
								
                            
                     ?>
                     <option value="0">---اختر---</option>
                     <?php   
                        foreach($retGenio as $rowGeni){
                                
                     ?>
                     <option value="<?php echo $rowGeni['VID']; ?>" 
                     <?php if($res['NEW_ORG'] == $rowGeni['VID']) { ?> selected="selected" 
                     <?php } ?>>
                     <?php echo $rowGeni['VNAME']; ?></option>
                     <?php } } }else{  ?>
                     <option value="0">---اختر---</option>
                     <?php } ?>
                    </select>
                    <div id="help-block"></div>
                </div>
            </div>
    </div>
    <!-------->
    <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">عدد الوحدات السكنية المغذية
    <span class="required" style="color:#F00;">*</span></label>
            <div class="col-12">
                <div class="input-group">
                    <input type="text" class="form-control TXT_HOUSING_UNITS_NO valid_number" id="TXT_HOUSING_UNITS_NO" 
                    name="TXT_HOUSING_UNITS_NO" placeholder="عدد الوحدات السكنية المغذية" tabindex="73" 
                    value="<?= $HaveRs ? ($rs['SERVICE_ID'] == 19 or $rs['SERVICE_ID'] ==20  or $rs['SERVICE_ID'] ==21 or 
					$rs['SERVICE_ID'] ==22) ? $HaveRse ? $res['HOUSING_UNITS_NO'] : "" : "" : "" ?>" />
                    
                    <input type="hidden" name="TXT_SER1" 
                    value="<?= $HaveRs ? ($rs['SERVICE_ID'] == 21 or $rs['SERVICE_ID'] ==22 or $rs['SERVICE_ID'] ==19 or 
					$rs['SERVICE_ID'] ==20) ? $HaveRse ? $res['SER'] : "" : "" : "" ?>" />
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
    <div class="form-group col-md-12"><label for="example-text-input" class="col-12 col-form-label">البيان
    <span class="required" style="color:#F00;">*</span></label>
            <div class="col-12">
                <div class="input-group">
                    <input type="text" class="form-control TXT_Link_Reason valid_text" id="TXT_Link_Reason" 
                    name="TXT_Link_Reason" placeholder="البيان" tabindex="74" 
                    value="<?= $HaveRs ? ($rs['SERVICE_ID'] == 19 or $rs['SERVICE_ID'] ==20  or $rs['SERVICE_ID'] ==21 or 
					$rs['SERVICE_ID'] ==22) ? $HaveRse ? $res['LINK_REASON'] : "" : "" : "" ?>" />
                    <div id="help-block"></div>
                </div>
            </div>
    </div>
    <!-------->
</div>
<!--End :: row---->