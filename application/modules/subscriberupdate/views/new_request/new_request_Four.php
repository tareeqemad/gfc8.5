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
            <form id="<?= $TABLE_NAME; ?>_form_four" name="<?= $TABLE_NAME; ?>_form_four"  class="horizontal-form <?= $TABLE_NAME; ?>_form_four"
            action=""  method="post" role="form" novalidate="novalidate">
                <div class="kt-portlet__body">
                	<!--Begin :: row---->
                    <div class="row">
                        <!-------->
                        <div class="form-group col-md-3"><label for="example-text-input" class="col-12 col-form-label">التعهدات المطلوبة
                                <span class="required" style="color:#F00;">*</span></label>
                                <div class="col-12">
                                    <div class="input-group">
                                        <select name="process_no" id="dp_process_no" class="form-control" tabindex="12">
                                        </select>
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
                                <div class="col-12">
                                    <div class="input-group">
                                        <button type="button" class="btn btn-success" onclick="javascript:printLegalCommi();" >التعهّد</button>
                                        <div id="help-block"></div>
                                    </div>
                                </div>
                        </div>
                        <!-------->
                    </div>
                    <!--End :: row---->
                    <div class="row">
                         <table class="table table-striped table-bordered table-hover">
                          <tr>
                             <td>الوثائق المطلوبة</td>
                          </tr>
                          <tr>
                           <td>
                            <div class="col-md-12">
                               <div class="form-group" id="FileDetService">
                                    <label class="control-label FileService" style="color:#8A7053;"></label>
                                    <br/>
                               </div>
                             </div>
                              </td>
                           </tr>
                         </table>
                     </div>
                </div>
            </form>
            <!--End::Form-->
        </div>
    </div>
    <!--end::Portlet-->
</div>
<!-- end:: Content -->