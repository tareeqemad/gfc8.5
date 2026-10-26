<?php

 $MODULE_NAME= 'subscriberupdate';
$TABLE_NAME= 'RequestUpdate';
//////
$isCreate =isset($result) && count($result)  > 0 ?false:true;
if (!isset($result))
    $result = array();
$HaveRs = count($result) > 0;
$rs = $HaveRs ? $result[0] : $result;
//////
if (!isset($result1))
    $result1 = array();
$HaveRse = count($result1) > 0;
$res = $HaveRse ? $result1[0] : $result1;
//////
$count = 0 ;
//////
if($HaveRs){
$prog= $this->Rmodel->getID('EBLLC_PKG', 'SUBSCRIBERS_TB_GET',$rs['SUBSCRIBER']);
$prog1= $this->Rmodel->getID('EBLLC_PKG', 'BANK_SUBSCRIBERS_GET',$rs['SUBSCRIBER']);
$where_sql = ($rs['REQUEST_APP_SERIAL']!= null)? $rs['REQUEST_APP_SERIAL'] : 0;
$ResultAsS = $this->Rmodel->getID('SERVICES_PKG','REQUEST_APP_SUBSC_GET',$where_sql);
$rertunReq = $this->Rmodel->getID('TSG_SETTING_PKG','SERVICES_TRACTOR_DEF_REQ2_GET',$where_sql);
  $STAGE_REASON = '';
  if(count($rertunReq) > 0){
	 foreach($rertunReq as $rowSR){
		   $STAGE_REASON .= $rowSR['STAGE_REASON']."|------|";
	 }
  }
}
?>
<!-- begin:: Subheader -->
<div class="kt-subheader   kt-grid__item" id="kt_subheader">
    <div class="kt-subheader__main">
        <h3 class="kt-subheader__title">
            الرئيسية </h3>
        <span class="kt-subheader__separator kt-hidden"></span>
        <div class="kt-subheader__breadcrumbs">
            <a href="<?=  site_url('cpanel'); ?>" class="kt-subheader__breadcrumbs-home"><i class="flaticon2-shelter"></i></a>
            <span class="kt-subheader__breadcrumbs-separator"></span>
            <a href="#" class="kt-subheader__breadcrumbs-link">
                إدارة النظام </a>
            <span class="kt-subheader__breadcrumbs-separator"></span>
            <a href="<?=  site_url('RequestUpdate'); ?>" class="kt-subheader__breadcrumbs-link">
                طلب خدمة تعديل اشتراك </a>
        </div>
    </div>
</div>
<!-- end:: Subheader -->
<!-- begin:: Content -->
<div class="kt-content  kt-grid__item kt-grid__item--fluid" id="kt_content">
    <div class="row">
        <div class="col-xl-12">

            <!--begin::Portlet-->
            <div class="kt-portlet kt-portlet--tabs">
                <div class="kt-portlet__head">
                    <div class="kt-portlet__head-toolbar">
                        <ul class="nav nav-tabs nav-tabs-line nav-tabs-line-success" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab" href="#kt_portlet_base_demo_1_1_tab_content" role="tab"
                                data-clicks="1">
                                    <i class="fa fa-cog"></i>بيانات الطلب
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#kt_portlet_base_demo_1_2_tab_content" role="tab" 
                                data-clicks="2">
                                    <i class="fa fa-search"></i>بيانات السداد
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#kt_portlet_base_demo_1_3_tab_content" role="tab" 
                                data-clicks="3">
                                    <i class="fa fa-book"></i>بيانات الإشتراك
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#kt_portlet_base_demo_1_4_tab_content" role="tab" 
                                data-clicks="4">
                                    <i class="fa fa-server"></i>الوثائق والتعهدات القانونية
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#kt_portlet_base_demo_1_5_tab_content" role="tab" 
                                data-clicks="5">
                                    <i class="fa fa-people-carry"></i>بيانات الأسرة
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#kt_portlet_base_demo_1_6_tab_content" role="tab" 
                                data-clicks="6">
                                    <i class="fa fa-shopping-basket"></i>خلو الطرف
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#kt_portlet_base_demo_1_22_tab_content" role="tab" 
                                data-clicks="22">
                                    <i class="fa fa-shopping-basket"></i>خيارات
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="kt-portlet__body">
                    <div class="tab-content">
                        <!------------>
                        <div class="tab-pane active" id="kt_portlet_base_demo_1_1_tab_content" role="tabpanel">
                            <?php require_once('new_request/new_request_One.php'); ?>
                        </div>
                        <!------------>
                        <div class="tab-pane" id="kt_portlet_base_demo_1_2_tab_content" role="tabpanel">
                            <?php require_once('new_request/new_request_Two.php'); ?>
                        </div>
                        <!------------>
                        <div class="tab-pane" id="kt_portlet_base_demo_1_3_tab_content" role="tabpanel">
                            <?php require_once('new_request/new_request_Three.php'); ?>
                        </div>
                        <!------------>
                        <div class="tab-pane" id="kt_portlet_base_demo_1_4_tab_content" role="tabpanel">
                            <?php require_once('new_request/new_request_Four.php'); ?>
                        </div>
                        <!------------>
                        <div class="tab-pane" id="kt_portlet_base_demo_1_5_tab_content" role="tabpanel">
                            <?php require_once('new_request/new_request_Five.php'); ?>
                        </div>
                        <!------------>
                        <div class="tab-pane" id="kt_portlet_base_demo_1_6_tab_content" role="tabpanel">
                            <?php require_once('new_request/new_request_Six.php'); ?>
                        </div>
                        <!------------>
                        <div class="tab-pane" id="kt_portlet_base_demo_1_22_tab_content" role="tabpanel">
                            <?php require_once('new_request/new_request_Ten.php'); ?>
                        </div>
                        <!------------>
                    </div>
                </div>
            </div>

            <!--end::Portlet-->
        </div>
    </div>
</div>