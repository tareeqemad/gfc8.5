<!--Begin :: row---->
<div class="row">
    <!-------->
    <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">اسم المشترك الجديد
    <span class="required" style="color:#F00;">*</span></label>
            <div class="col-12">
                <div class="input-group">
                    <input type="text" class="form-control TXT_NAMES_NEW valid_name" id="TXT_NAMES_NEW" 
                    name="TXT_NAMES_NEW" placeholder="اسم المشترك الجديد" tabindex="54" 
                    value="<?= $HaveRs ? ($rs['SERVICE_ID'] == 15 or $rs['SERVICE_ID'] ==16) ? 
					$HaveRse ? $res['SUBSCRIBER_NAME'] : "" : "" : "" ?>" />
                    <div id="help-block"></div>
                </div>
            </div>
    </div>
    <!-------->
</div>
<!--End :: row---->