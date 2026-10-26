<?php
$MODULE_NAME= 'subscriberupdate';
$TABLE_NAME= 'RequestUpdate';
$post_url_Ten= base_url("$MODULE_NAME/$TABLE_NAME/createRelatedRequest");
?>
<!-- begin:: Content -->
<div class="kt-content  kt-grid__item kt-grid__item--fluid" id="kt_content">
    <!--begin::Portlet-->
    <div class="kt-portlet">
        <div class="kt-portlet__body">
            <!--begin::Form-->
            <form id="<?= $TABLE_NAME; ?>_form_ten" name="<?= $TABLE_NAME; ?>_form_ten"  
            class="horizontal-form <?= $TABLE_NAME; ?>_form_ten" 
            action="<?= $post_url_Ten; ?>" method="post" role="form" novalidate="novalidate">
              <input type="hidden" name="TXT_TEN_SERVICE_ID" value="<?= $HaveRs ? $rs['SERVICE_ID'] : "" ?>"  />
              <input type="hidden" name="TXT_TEN_REQUEST_APP_SERIAL" value="<?= $HaveRs ? $rs['REQUEST_APP_SERIAL'] : "" ?>"  />
              <input type="hidden" name="TXT_TEN_PAYMENT_WAY" value="<?= $HaveRs ? $rs['PAYMENT_WAY'] : "" ?>"  />
              <input type="hidden" name="TXT_IS_LARGE_TYPE" value="0"  />
              <div class="kt-portlet__body">
				<?php 
                if($HaveRs && ( $rs['SERVICE_ID'] == 11 || $rs['SERVICE_ID'] == 12 ) ) { // تغيير اسم / تنازل
                if($HaveRs && $rs['PAYMENT_WAY'] == 3){ ?>
                <!--Begin :: row---->
                <div class="row">
                    <div class="form-group col-md-2">
                            <label for="example-text-input"  class="col-8 col-form-label">تغيير عداد</label>
                            <div class="col-12">
                                <span class="kt-switch kt-switch--lg kt-switch--icon">
                                    <label>
                                        <input type="checkbox" name="CH_IS_CREATE_CHANGE" id="CH_IS_CREATE_CHANGE">
                                        <span></span>
                                    </label>
                                </span>
                            </div>
                     </div>
                     <div class="form-group col-md-10">
                            <label for="example-text-input"  class="col-8 col-form-label">الخدمة المقترنة</label>
                            <div class="kt-radio-inline">
                                <?php if($HaveRs && $rs['PHASE_TYPE'] == 10){ 
                                    if($HaveRs && count($prog) > 0 && $prog[0]['TYPE'] > 299){ // ذكي
                                ?>
                                    <label class="kt-radio kt-radio--disabled">
                                        <input type="radio" name="radio[]" disabled value="39"> تغيير عداد ذكي لعداد ذكي
                                        <span></span>
                                    </label>
                                    <label class="kt-radio kt-radio--disabled">
                                        <input type="radio" name="radio[]" disabled value="130"> تغيير عداد ذكي لعداد مسبق دفع
                                        <span></span>
                                    </label>
                                <?php }elseif($HaveRs && count($prog) > 0 && ( $prog[0]['TYPE'] < 300 && $prog[0]['TYPE'] > 199)){ // مسبق ?>
                                    <label class="kt-radio kt-radio--disabled">
                                        <input type="radio" name="radio[]" disabled value="29"> تغيير عداد مسبق دفع لعداد مسبق دفع
                                        <span></span>
                                    </label>
                                    <label class="kt-radio kt-radio--disabled">
                                        <input type="radio" name="radio[]" disabled value="35"> تغيير عداد مسبق دفع لعداد ذكي
                                        <span></span>
                                    </label>
                                    <label class="kt-radio kt-radio--disabled">
                                        <input type="radio" name="radio[]" disabled value="108"> تغيير عداد مسبق دفع لعداد ميكانيكي
                                        <span></span>
                                    </label>
                                <?php }else{ // ميكانيكي ?>
                                    <label class="kt-radio kt-radio--disabled">
                                        <input type="radio" name="radio[]" disabled value="27"> تغيير عداد ميكانيكي لعداد مسبق دفع
                                        <span></span>
                                    </label>
                                    <label class="kt-radio kt-radio--disabled">
                                        <input type="radio" name="radio[]" disabled value="31"> تغيير عداد ميكانيكي لعداد ذكي
                                        <span></span>
                                    </label>
                                <?php } } ?>
                                <?php if($HaveRs && $rs['PHASE_TYPE'] == 20){ 
                                    if($HaveRs && count($prog) > 0 && $prog[0]['TYPE'] > 299){ // ذكي
                                 ?>
                                    <label class="kt-radio kt-radio--disabled">
                                        <input type="radio" name="radio[]" disabled value="40"> تغيير عداد ذكي لعداد ذكي
                                        <span></span>
                                    </label>
                                    <label class="kt-radio kt-radio--disabled">
                                        <input type="radio" name="radio[]" disabled value="131"> تغيير عداد ذكي لعداد مسبق دفع
                                        <span></span>
                                    </label>
                                 <?php }elseif($HaveRs && count($prog) > 0 && ( $prog[0]['TYPE'] < 300 && $prog[0]['TYPE'] > 199)){ // مسبق ?>
                                    <label class="kt-radio kt-radio--disabled">
                                        <input type="radio" name="radio[]" disabled value="30"> تغيير عداد مسبق دفع لعداد مسبق دفع
                                        <span></span>
                                    </label>
                                    <label class="kt-radio kt-radio--disabled">
                                        <input type="radio" name="radio[]" disabled value="36"> تغيير عداد مسبق دفع لعداد ذكي	
                                        <span></span>
                                    </label>
                                    <label class="kt-radio kt-radio--disabled">
                                        <input type="radio" name="radio[]" disabled value="109"> تغيير عداد مسبق دفع لعداد ميكانيكي	
                                        <span></span>
                                    </label>
                                 <?php }else{ // ميكانيكي ?>
                                    <label class="kt-radio kt-radio--disabled">
                                        <input type="radio" name="radio[]" disabled value="28"> تغيير عداد ميكانيكي لعداد مسبق دفع	
                                        <span></span>
                                    </label>
                                    
                                    <label class="kt-radio kt-radio--disabled">
                                        <input type="radio" name="radio[]" disabled value="32"> تغيير عداد ميكانيكي لعداد ذكي
                                        <span></span>
                                    </label>                    
                                <?php } } ?> 
                            </div>
                     </div>
                </div>
                <!--End :: row---->
                <?php }else{ ?> 
                <!--Begin :: row---->
                <div class="row">
                    <div class="form-group col-md-2">
                            <label for="example-text-input"  class="col-8 col-form-label">فحص عداد</label>
                            <div class="col-12">
                                <span class="kt-switch kt-switch--lg kt-switch--icon">
                                    <label>
                                        <input type="checkbox" name="CH_IS_CREATE_CHECK" id="CH_IS_CREATE_CHECK">
                                        <span></span>
                                    </label>
                                </span>
                            </div>
                     </div>
                     <div class="form-group col-md-10">
                            <label for="example-text-input"  class="col-8 col-form-label">الخدمة المقترنة</label>
                            <div class="kt-radio-inline">
                            <?php if($HaveRs && $rs['PHASE_TYPE'] == 10){ ?>
                                <label class="kt-radio kt-radio--disabled">
                                    <input type="radio" name="radio1[]" disabled value="80"> فحص عداد	
                                    <span></span>
                                </label>
                            <?php } ?>
                            <?php if($HaveRs && $rs['PHASE_TYPE'] == 20){ ?>
                                <label class="kt-radio kt-radio--disabled">
                                    <input type="radio" name="radio1[]" disabled value="81"> فحص عداد	
                                    <span></span>
                                </label>
                            <?php } ?>
                            </div>
                     </div>
                </div>
                <!--End :: row---->
                <!--Begin :: row---->
                <div class="row">
                    <div class="form-group col-md-2">
                            <label for="example-text-input"  class="col-8 col-form-label">ختم عداد</label>
                            <div class="col-12">
                                <span class="kt-switch kt-switch--lg kt-switch--icon">
                                    <label>
                                        <input type="checkbox" name="CH_IS_CREATE_STAMP" id="CH_IS_CREATE_STAMP">
                                        <span></span>
                                    </label>
                                </span>
                            </div>
                     </div>
                     <div class="form-group col-md-10">
                            <label for="example-text-input"  class="col-8 col-form-label">الخدمة المقترنة</label>
                            <div class="kt-radio-inline">
                            <?php if($HaveRs && $rs['PHASE_TYPE'] == 10){ ?>
                                <label class="kt-radio kt-radio--disabled">
                                    <input type="radio" name="radio2[]" disabled value="82"> ختم عداد	
                                    <span></span>
                                </label>
                            <?php } ?>
                            <?php if($HaveRs && $rs['PHASE_TYPE'] == 20){ ?>
                                <label class="kt-radio kt-radio--disabled">
                                    <input type="radio" name="radio2[]" disabled value="83"> ختم عداد	
                                    <span></span>
                                </label>
                            <?php } ?>
                            </div>
                     </div>
                </div>
                <!--End :: row---->
                <?php } 
                }elseif($HaveRs && ( $rs['SERVICE_ID'] == 21 || $rs['SERVICE_ID'] == 22 )){ // تعديل نوع الربط
                ?> 
                <!--Begin :: row---->
                <div class="row">
                    <div class="form-group col-md-2">
                            <label for="example-text-input"  class="col-8 col-form-label">تعديل تصنيف اشتراك</label>
                            <div class="col-12">
                                <span class="kt-switch kt-switch--lg kt-switch--icon">
                                    <label>
                                        <input type="checkbox" name="CH_IS_CHANGE_LINK" id="CH_IS_CHANGE_LINK">
                                        <span></span>
                                    </label>
                                </span>
                            </div>
                     </div>
                     <div class="form-group col-md-10">
                            <label for="example-text-input"  class="col-8 col-form-label">الخدمة المقترنة</label>
                            <div class="kt-radio-inline">
                            <?php if($HaveRs && $rs['PHASE_TYPE'] == 10){ ?>
                                <label class="kt-radio kt-radio--disabled">
                                    <input type="radio" name="radio3[]" disabled value="19"> تعديل تصنيف المشترك	
                                    <span></span>
                                </label>
                            <?php } ?>
                            <?php if($HaveRs && $rs['PHASE_TYPE'] == 20){ ?>
                                <label class="kt-radio kt-radio--disabled">
                                    <input type="radio" name="radio3[]" disabled value="20"> تعديل تصنيف المشترك	
                                    <span></span>
                                </label>
                            <?php } ?>
                            </div>
                     </div>
                </div>
                <!--End :: row---->
                <?php } ?>
                <!--Begin :: row---->
                <div class="row">
                    <!--begin: Datatable -->
                    <table class="table table-striped- table-bordered table-hover table-checkable" 
                    id="sample_3">
                        <thead class="table-light">
                            <tr>
                                <th>البيان</th>
                                <th>الحركة</th> 
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>حفظ البيانات</td>
                                <td>
                                	<button type="button" class="btns btns-primary btns-md" id="btn_add_Related_Request" >حفظ</button>
                                </td> 
                            </tr>
							<tr>
                                <td>رفع ملفات</td>
                                <?php if($HaveRs){ ?> 
                                <td><a href="javascript:scan_file(<?=$rs['REQUEST_APP_SERIAL']?>,<?=$rs['BRANCH_ID']?>);" 
                                class="btns btns-success btns-md" id="ID_SCAN"   > 
                                    رفع ملفات 
                                </a> &nbsp;<a href="javascript:show_file_scan(<?=$rs['REQUEST_APP_SERIAL']?>);" 
                                class="btns btns-info btns-md" id="ID_SCAN_SHOW"   > 
                                   عرض ملفات 
                                </a></td><?php } ?>
                            </tr>
                            <tr>
                                <td>نموذج الطلب</td>
                                <td>
                                	<a  href="javascript:void(0)" class="btns btns-check btns-md" id="ID_COVER"
                        			<?php if($HaveRs){ ?> 
                        				onclick="javascript:print_report_general(<?=$rs['REQUEST_APP_SERIAL']?>,<?=$rs['SERVICE_ID']?>,<?=$rs['ENLARGE_TYPE']?>);" <?php } ?>>
                            			<i class="fas fa-money-check"></i>
                            				طباعة نموذج الطلب
                        			</a>
                                </td> 
                            </tr>
                            <tr>
                                <td>نموذج خلو الطرف</td>
                                <td><a href="javascript:void(0)" class="btns btns-bill btns-md" id="ID_FREE" 
								<?php if($HaveRs){ ?>  
                                onclick="javascript:print_report_general_2(<?=$rs['REQUEST_APP_SERIAL']?>,'discharge');"<?php } ?>>
                                    <i class="fas fa-file-invoice"></i>
                                    طباعة خلو الطرف
                                </a></td> 
                            </tr>
                            <tr>
                                <td>اعتماد الطلب</td>
                                <td>
                                	<button type="button" class="btns btns-secondary btns-md" id="btn_adopt_Request" >اعتماد</button>
                                </td> 
                            </tr> 
                            <tr>
                                <td>طلب استثناء</td>
                                <td><?php if($HaveRs){ ?>
                                <a href="javascript:add_exception_manual(<?=$rs['REQUEST_APP_SERIAL']?>,<?=$rs['SUBSCRIBER']?>);" 
                                class="btns btns-info btns-md" id="ID_exception"  > 
                                    طلب استثناء 
                                </a>
                                <?php } ?></td> 
                            </tr>
                            <tr>
                                <td>طلب إفادة ميدانية</td>
                                <td><?php if($HaveRs){ ?>
                                <a href="javascript:req_technical_tip(<?=$rs['REQUEST_APP_SERIAL']?>,<?=$rs['SUBSCRIBER']?>);" 
                                class="btns btns-success btns-md" id="ID_exception"  > 
                                    إفادة ميدانية 
                                </a>
                                <?php } ?></td> 
                            </tr> 

							<?php if($HaveRs){ ?>
                            <tr>
                            	<td>تقييم الخدمة</td>
                            	<td>
                                <a href="<?php echo base_url()?>EvaluationIndex/<?=$rs['REQUEST_APP_SERIAL']?>" 
                                class="btns btns-success btns-md" id="ID_exception" target="_blank">تقييم</a></td>
                            </tr>
                            <?php } ?>							
                        </tbody>
                    </table>
                    <!--end: Datatable --> 
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