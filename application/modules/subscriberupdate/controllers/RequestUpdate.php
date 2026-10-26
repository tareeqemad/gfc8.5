<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
 
  class RequestUpdate extends MY_Controller{
	/// Defination Element And Model
	public $TitleIndex = 'طلب خدمة تعديل اشتراك';
	public $ContentIndex = 'subscriberupdate/requests_update_index';
	public $ContentPage = 'subscriberupdate/requests_update_page';
	public $ContentShow = 'subscriberupdate/requests_update_show';
	public $PackageConstant = 'TSG_SETTING_PKG';
	public $PackageService = 'SERVICES_PKG';
	public $Module = 'subscriberupdate';
	public $Controller = 'RequestUpdate';
	public $BranchID ;
	// Function Construct
	function  __construct(){
		parent::__construct();
		// Load General Module
		$this->load->module('template/general');
		$this->load->library('ftp'); 
		// Definitation 
		$this->general->TitleIndex =   $this->TitleIndex;
		$this->general->ContentIndex = $this->ContentIndex;
		$this->general->ContentPage =  $this->ContentPage;
		$this->general->ContentShow =  $this->ContentShow;
		$this->general->PackageConstant =  $this->PackageConstant;
		$this->general->PackageSend =  $this->PackageService;
		$this->general->Module =  $this->Module;
		$this->general->Controller =  $this->Controller;
		// Get Master Parameter Post
		$this->REQUEST_APP_SERIAL = $this->input->post('TXT_REQUEST_APP_SERIAL'); //رقم الطلب
		$this->SUBSCRIBER = $this->input->post('TXT_SUBSCRIBER'); //رقم الاشتراك
		$this->REQUEST_CASE = $this->input->post('TXT_REQUEST_CASE'); //حالة الطلب(ترتيب المرحلة)
		$this->STAGE_DEFINITION_ID = $this->input->post('DL_STAGE_DEFINITION_ID'); //رقم المرحلة
		$this->SERVICE_TYPE = $this->input->post('DL_SERVICE_TYPE'); //رقم نوع الخدمة
		$this->SERVICE_ID = $this->input->post('DL_SERVICE_ID'); //رقم الخدمة
		$this->APPLICANT = $this->input->post('DL_APPLICANT'); //مقدم الطلب
		$this->APPLICANT_NAME = $this->input->post('TXT_APPLICANT_NAME'); //اسم مقدم الطلب
		$this->APPLICANT_ID = $this->input->post('TXT_ID_APP'); //رقم هوية مقدم الطلب
		$this->DOCUMENT_TYPE = $this->input->post('DL_DOCUMENT_TYPE'); //نوع الوثيقة
		$this->APPLICANT_JOB = $this->input->post('DL_APPLICANT_JOB'); //مهنة مقدم الطلب
		$this->JAWWAL = $this->input->post('TXT_JAWWAL'); //بيانات الاتصال: جوال
		$this->TEL = $this->input->post('TXT_TEL'); //بيانات الاتصال: هاتف
		$this->EMAIL = $this->input->post('TXT_EMAIL'); //بيانات الاتصال: بريد إلكترونى
		$this->PHASE_TYPE = $this->input->post('DL_PHASE'); //نوع الفاز ثوابت
		$this->GREAT_REGION = $this->input->post('DL_Great_R_No'); //المنطقة الكبرى ثوابت
		$this->JUNIOR_REGION = $this->input->post('DL_Junior_No'); //المنطقة الصغرى ثوابت
		$this->DECLARATION = $this->input->post('TXT_DECLARATION'); //بيان الطلب ثوابت
		$this->REQUEST_ID = $this->input->post('TXT_REQUEST_ID'); //رقم تسلسل الاشتراك
		$this->BRANCH_ID = $this->input->post('DL_BRANCH'); //المقر
		$this->PAYMENT_WAY = $this->input->post('TXT_TYPE_PAID_H'); //المقر
		$this->SUBSCRIBER_NAME = $this->input->post('txt_fname'); //اسم المشترك
		$this->SUBSCRIBER_D = $this->input->post('TXT_SUBSCRIBER_D'); //رقم الاشتراك
		$this->RELATIONSHIP = $this->input->post('TXT_RELATIONSHIP'); //
		
		$this->IS_MOTAKHERT = $this->input->post('IS_MOTAKHERT'); //
		$this->IS_TAFTESH = $this->input->post('IS_TAFTESH'); //
		$this->PREFIX = $this->input->post('TXT_PRVX'); //المقر
		// Get Detail Parameter Post
		$this->PAID_TYPE = $this->input->post('DL_TYPE_PUSH'); //نوع السداد  1-سداد آلى, 2-مسبق دفع
		$this->ID = $this->input->post('TXT_ID'); //رقم هوية المشترك الحالى
		$this->SUBSCRIBER_NAME = $this->input->post('TXT_SUBSCRIBER_NAME'); //اسم المشترك الحالى
		$this->JOB = $this->input->post('DL_JOB'); //المهنة 
		$this->BENIFICARY_NAME = $this->input->post('TXT_SUBSCRIBER_BEN'); //اسم المنتفع الحالى
		$this->NEW_SUBSCRIBER_ID = $this->input->post('TXT_SSN_NEW'); //رقم هوية المشترك الجديد
		$this->NEW_SUBSCRIBER_NAME = $this->input->post('TXT_NAME_NEW'); //اسم المشترك الجديد
		$this->NEW_BENIFICARY_NAME = $this->input->post('TXT_B_NAME_NEW'); //اسم المنتفع الجديد
		$this->AGREEMENT_SUB_SELF = $this->input->post('DL_AGREEMENT'); //صفة الملتزم بالاتفاق
		$this->AGREEMENT_SUB_ID = $this->input->post('TXT_SSN_AGREEMENT'); //رقم هوية الملتزم بالاتفاق
		$this->AGREEMENT_SUB_NAME = $this->input->post('TXT_NAME_AGREEMENT'); //اسم الملتزم بالاتفاق
		$this->CHANGE_REASON = $this->input->post('TXT_CHANGE_REASON'); //سبب التغيير
		$this->CHANGE_TYPE = 0; //نوع التغيير 
		$this->O_FILE_NO = $this->input->post('TXT_O_FILE_NO'); //رقم الملف القديم
		$this->N_FILE_NO = $this->input->post('TXT_N_FILE_NO'); //رقم الملف الجديد
		$this->N_USE_TYPE = 0; //نوع الاستخدام الجديد
		$this->N_ORG = 0; //المؤسسة الجديدة
		//
		$this->SER = $this->input->post('TXT_SER'); //SER
		$this->SER1 = $this->input->post('TXT_SER1'); //SER
		// Get Detail Parameter Post
		$this->JOBS = $this->input->post('DL_JOBS'); //المهنة
		$this->NEW_ORG = $this->input->post('DL_NEW_ORG'); //المؤسسة الجديدة
		$this->NEW_LINK_TYPE = 0; //نوع الربط الجديد
		$this->NEW_USE_TYPE = $this->input->post('DL_NEW_USE_TYPE'); //نوع الاستخدام الجديد
		$this->STOREY_NO = 0; //عدد الطوابق
		$this->FLAT_NO = 0; //عدد الشقق
		$this->QUOTIENTS_NO = 0; //عدد الحواصل
		$this->SUBSCRIBER10_NO = 0; //عدد اشتراكات 1 فاز 
		$this->SUBSCRIBER30_NO = 0; //عدد اشتراكات 3 فاز
		$this->IS_SHARING_SERVICE = 0; //يغذي العمارة
		$this->HOUSING_UNITS_NO = $this->input->post('TXT_HOUSING_UNITS_NO'); //عدد الوحدات السكنية المغذية
		$this->LINK_REASON = $this->input->post('TXT_Link_Reason'); //البيان
		$this->ID_NOW = $this->input->post('TXT_ID'); //رقم هوية المشترك
		$this->TYPE = $this->input->post('TXT_TYPE_ID'); //نوع الاشتراك
		$this->ORG = $this->input->post('TXT_ORG'); //المؤسسة القديمة
		$this->LINK_TYPE = $this->input->post('TXT_LINK_TYPESS'); //نوع الربط القديم
		$this->USE_TYPE = $this->input->post('TXT_USE_TYPE'); //نوع الاستخدام القديم
		// تصحيح اسم المشترك
		$this->SUBSCRIBER_NAMES = $this->input->post('TXT_NAMES_NEW'); //تصحيح اسم المشترك
		// تصحيح عنوان المشترك
		$this->DISTRICT = $this->input->post('DL_DISTRICTS'); //المحافظة/البلدية  فوترة
		$this->BLOCK_CODE = $this->input->post('TXT_BLOCK_CODE'); //رقم القطعة
		$this->POS_CODE = $this->input->post('TXT_POS_CODE'); //رقم القسيمة
		$this->HOUSE_NO = $this->input->post('TXT_HOUSE_NO'); //رقم البناية/المنزل
		$this->HOUSE_NAME = $this->input->post('TXT_HOUSE_NAME'); //اسم البناية
		$this->ADDRESS = $this->input->post('TXT_ADDRESS'); //الشارع/الحى
		$this->NEIGHBOR_NO = $this->input->post('TXT_NEIGHBOR_NO'); //رقم اشتراك الجار
		$this->NEIGHBOR_LOC = $this->input->post('TXT_NEIGHBOR_LOC'); //رقم موقع الجار
		// المرفقات
		$this->REQUEST_APP = $this->input->post('REQ_N'); //رقم الطلب
		$this->REQUEST_APP_LEGAL = $this->input->post('REQ_N_Legal'); //رقم الطلب
		$this->SER_ID = $this->input->post('TXT_NO_SER'); //رقم المتسلسل للملف
		$this->FILE_ID = $this->input->post('TXT_ID_FILE'); //رقم الملف
		$this->FILE_TYPE = $this->input->post('DL_FILE_TYPE'); //الملف
		$this->FILE_TYPES = $this->input->post('DL_FILE_TYPES'); //الملف
		$this->FILE_NOTE = $this->input->post('TXT_FILE_NOTE'); //ملاحظات
		// سداد آلي
		$this->SER_BANK = $this->input->post('TXT_SER_BANK'); //رقم المتسلسل البنكي
		$this->ACCOUNT_ID = $this->input->post('TXT_BANK_NO'); //رقم الحساب البنكي
		$this->M_BANK = $this->input->post('DL_BANKS'); //البنك
		$this->B_BANK = $this->input->post('DL_BANK_BRANCH'); //فرع البنك 
		$this->JOB_TYPE = $this->input->post('DL_EMP_TYPE'); //نوع الموظف
	    $this->BRANCH_ID = $this->input->post('TXT_REQUEST_BRANCH_ID'); //رقم المقر التابع
		$this->TYPE_PAID_H = $this->input->post('TXT_TYPE_PAID_H'); //نوع السداد
		// سند دين منظم
		$this->SER_SEND = $this->input->post('TXT_SER_SEND'); //رقم المتسلسل السند
		$this->SEND_NO = $this->input->post('TXT_SEND_NO'); //رقم السند
		$this->THE_YEAR = $this->input->post('TXT_THE_YEAR'); //السنة
		$this->REMAINDER_VAL = $this->input->post('TXT_REMAINDER_VAL'); //مبلغ المتأخرات 
		$this->MONTHLY_COMMITTED = $this->input->post('TXT_MONTHLY_COMMITTED'); //قيمة الالتزام الشهري
		$this->FIRST_DATE_PAID = $this->input->post('TXT_FIRST_DATE_PAID'); //تاريخ أول دفعة
		
		
		////Related
		$this->TEN_REQUEST_APP_SERIAL = $this->input->post('TXT_TEN_REQUEST_APP_SERIAL'); //
		$this->TEN_SERVICE_ID = $this->input->post('TXT_TEN_SERVICE_ID'); //
		$this->TEN_PAYMENT_WAY = $this->input->post('TXT_TEN_PAYMENT_WAY'); //
		$this->IS_CREATE_CHANGE = $this->input->post('radio[]'); //
		$this->IS_CREATE_CHECK = $this->input->post('radio1[]'); //
		$this->IS_CREATE_STAMP = $this->input->post('radio2[]'); //
		$this->IS_CHANGE_LINK = $this->input->post('radio3[]'); //
		$this->ENLARGE_TYPE = $this->input->post('TXT_IS_LARGE_TYPE'); //
		
	}
	// Index Function
	function index(){
	   $this->general->page = 1;
	   // Define Constant And Call In Drop Down List
		$ConstArr = array(
		   array('Name'=>'serv_type','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>4,'Branch'=>0),
		   array('Name'=>'BRANCHES','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>10,'Branch'=>0),
		   array('Name'=>'PHASE_TYPES','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>15,'Branch'=>0),
		   array('Name'=>'PAID_TYPE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>92,'Branch'=>0),
		   array('Name'=>'IS_ACTIVE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>54,'Branch'=>0),
		   array('Name'=>'REQUEST_TYPE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>25,'Branch'=>0),
		   array('Name'=>'IS_CAMPAIGN','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>381,'Branch'=>0),
		   array('Name'=>'REQUEST_STATUS','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>353,'Branch'=>0)
		);
		// Emptys Constant Array
		$this->general->Constantarray = array();
		// Add Value Of Array In General Array
	    $this->general->Constantarray = $ConstArr;
		// Define Tables And Call In Drop Down List
		$TableArr = array(
			
		);
		// Emptys Tables Array
		$this->general->Tablesarray = array();
		// Add Value Of Array In General Array
	    $this->general->Tablesarray = $TableArr;
		// Check Access to page
		if (HaveAccess('RequestUpdate')){
			// Call Index Function
	    	$this->general->index();
		}else{
			redirect('cpanel');
		}
	}
	// Get Page Function
	function get_page($ArrParm = array()){
		$where = 'where 1=1';
		// Get Branch ID From General Function Get Branch Where User
		$rets = $this->Rmodel->getID('TSG_QF_PKG','CURRENT_BRANCHES',$this->user->branch);
		foreach($rets as $rows){
		  $this->BranchID = $rows['VID'];	
		}
		//
		if($_POST){
			array_push($ArrParm,array('Name'=>'M.REQUEST_APP_SERIAL','Value'=>$this->p_REQUEST_APP_SERIAL,'Type'=>'equal'));
			array_push($ArrParm,array('Name'=>'M.SUBSCRIBER','Value'=>$this->p_SUBSCRIBER,'Type'=>'equal'));
			array_push($ArrParm,array('Name'=>'M.BRANCH_ID','Value'=>$this->p_BRANCH_ID,'Type'=>'equal'));
			array_push($ArrParm,array('Name'=>'M.PHASE_TYPE','Value'=>$this->p_PHASE_TYPE,'Type'=>'equal'));
			array_push($ArrParm,array('Name'=>'M.SERVICE_ID','Value'=>$this->p_SERVICE_ID,'Type'=>'equal'));
		    array_push($ArrParm,array('Name'=>'M.PAYMENT_WAY','Value'=>$this->p_PAID_TYPE,'Type'=>'equal'));
			array_push($ArrParm,array('Name'=>'D.REQUEST_APP_SERVICE_TYPE','Value'=>$this->p_REQUEST_TYPE,'Type'=>'equal'));
			array_push($ArrParm,array('Name'=>'M.APPLICANT_NAME','Value'=>$this->p_APPLICANT_NAME,'Type'=>'like'));
			array_push($ArrParm,array('Name'=>'M.REQUEST_APP_DATE','Value'=>$this->p_FROM_DATE,'Type'=>'date>='));
			array_push($ArrParm,array('Name'=>'M.REQUEST_APP_DATE','Value'=>$this->p_TO_DATE,'Type'=>'date<='));
			array_push($ArrParm,array('Name'=>'M.STATUS','Value'=>$this->p_REQUEST_STATUS ,'Type'=>'equal'));
			array_push($ArrParm,array('Name'=>'M.IS_CAMPAIGN','Value'=>$this->p_IS_CAMPAIGN ,'Type'=>'equal'));
		}else{
			$where = 'where 1=0';	
		}
		$this->general->page = 1;
		array_push($ArrParm,array('Name'=>'M.STAGE_DEFINITION_ID','Value'=>1, 'Type'=>'equal'));
		array_push($ArrParm,array('Name'=>'M.SERVICE_TYPE','Value'=>3, 'Type'=>'equal'));
		array_push($ArrParm,array('Name'=>'M.FIELD_DETECTION_CASE','Value'=>1, 'Type'=>'equal'));
		if($this->user->branch != 1){
			array_push($ArrParm,array('Name'=>'M.BRANCH_ID','Value'=>$this->BranchID,'Type'=>'or'
					,'SubVals'=>" and ( M.BRANCH_ID =".$this->BranchID." or M.SUBSC_BRANCH = ".$this->BranchID." )" ));
		}
		$this->general->PackageSend =  $this->PackageService;
		// Define NEW MASTER ARRAY
		$this->general->get_page_all('REQUEST_APP_TB','REQUEST_APP_TB_LIST ',$ArrParm,$where);
	}
	// Create Page Function 
	function PageCreate(){
		// Get Branch ID From General Function Get Branch Where User
		$rets = $this->Rmodel->getID('TSG_QF_PKG','CURRENT_BRANCHES',$this->user->branch);
		foreach($rets as $rows){
		  $this->BranchID = $rows['VID'];	
		}
		// Define Constant And Call In Drop Down List
		$ConstArr = array(
		   array('Name'=>'serv_type','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>4,'Branch'=>0),
		   array('Name'=>'COUNTER_TYPE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>23,'Branch'=>0),
		   array('Name'=>'link_types','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>14,'Branch'=>0),
		   array('Name'=>'use_types','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>13,'Branch'=>0),
		   array('Name'=>'org_types','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>12,'Branch'=>0),
		   array('Name'=>'ambir','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>16,'Branch'=>0),
		   array('Name'=>'new_ambir','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>193,'Branch'=>0),
		   array('Name'=>'DISTRICTS','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>17,'Branch'=>$this->BranchID),
		   array('Name'=>'SUBSCRIBERS_TYPES','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>11,'Branch'=>0),
		   array('Name'=>'PHASE_TYPES','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>15,'Branch'=>0),
		   array('Name'=>'JOBS','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>19,'Branch'=>0),
		   array('Name'=>'REQUEST_TYPE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>25,'Branch'=>0),
		   array('Name'=>'Reason_Case','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>26,'Branch'=>0),
		   array('Name'=>'TYPE_PUSH','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>49,'Branch'=>0),
		   array('Name'=>'AGREEMENT','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>50,'Branch'=>0),
		   array('Name'=>'CHANGE_TYPE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>51,'Branch'=>0),
		   array('Name'=>'Test_Location','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>43,'Branch'=>0),
		   array('Name'=>'REQUEST_APP_TYPE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>37,'Branch'=>0),
		   array('Name'=>'COMP','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>46,'Branch'=>0),
		   array('Name'=>'Methode','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>45,'Branch'=>0),
		   array('Name'=>'LARGE_TYPE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>52,'Branch'=>0),
		   array('Name'=>'Test_Result','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>44,'Branch'=>0),
		   array('Name'=>'IS_ACTIVE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>54,'Branch'=>0),
		   array('Name'=>'APPLICANT','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>95,'Branch'=>0),
		   array('Name'=>'DOCUMENT_TYPE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>96,'Branch'=>0),
		   array('Name'=>'YESORNO','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>55,'Branch'=>0),
		   array('Name'=>'TYPE_PAID','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>92,'Branch'=>0),
		   array('Name'=>'BANKS','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>99,'Branch'=>0),
		   array('Name'=>'EMP_TYPE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>101,'Branch'=>0),
		   array('Name'=>'BRANCH_LARGE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>20,'Branch'=>$this->BranchID),
		   array('Name'=>'FFES_TYPE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>60,'Branch'=>0),
		   array('Name'=>'THE_EXCEPTION','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>164,'Branch'=>0),
		   array('Name'=>'EMP_SIDE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>165,'Branch'=>0),
		   array('Name'=>'PIN_CODE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>177,'Branch'=>0),
		   array('Name'=>'REQ_FE_TYPE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>92,'Branch'=>0)
		);
		// Emptys Constant Array
		$this->general->Constantarray = array();
		// Add Value Of Array In General Array
	    $this->general->Constantarray = $ConstArr;
		// Define Tables And Call In Drop Down List
		$TableArr = array(
			array('Name'=>'BRANCH_JUNIOR','JSON'=>0,'Package'=>'TSG_SETTING_PKG',
			'Procedur'=>'CONSTANTS_TB_BRANCH_GETS','RowID'=>'VID','RowName'=>'VNAME',
			'where_sql'=>'AND M.BRANCH = '.$this->BranchID )
		);
		// Emptys Tables Array
		$this->general->Tablesarray = array();
		// Add Value Of Array In General Array
	    $this->general->Tablesarray = $TableArr;
		// Check Access to page
		if (HaveAccess('CreateRequestUpdate')){
			// Call Function Create From General Controller
			$this->general->get(0 , '' , '' , '');
		}else{
			redirect('cpanel');
		} 
	}
	// Edit Page Function 
	function PageEdit($ID){
		// Get Branch ID From General Function Get Branch Where User
		$rets = $this->Rmodel->getID('TSG_QF_PKG','CURRENT_BRANCHES',$this->user->branch);
		foreach($rets as $rows){
		  $this->BranchID = $rows['VID'];	
		}
		// Define Constant And Call In Drop Down List
		$ConstArr = array(
		   array('Name'=>'serv_type','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>4,'Branch'=>0),
		   array('Name'=>'COUNTER_TYPE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>23,'Branch'=>0),
		   array('Name'=>'link_types','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>14,'Branch'=>0),
		   array('Name'=>'use_types','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>13,'Branch'=>0),
		   array('Name'=>'org_types','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>12,'Branch'=>0),
		   array('Name'=>'ambir','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>16,'Branch'=>0),
		   array('Name'=>'new_ambir','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>193,'Branch'=>0),
		   array('Name'=>'DISTRICTS','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>17,'Branch'=>$this->BranchID),
		   array('Name'=>'SUBSCRIBERS_TYPES','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>11,'Branch'=>0),
		   array('Name'=>'PHASE_TYPES','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>15,'Branch'=>0),
		   array('Name'=>'JOBS','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>19,'Branch'=>0),
		   array('Name'=>'REQUEST_TYPE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>25,'Branch'=>0),
		   array('Name'=>'Reason_Case','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>26,'Branch'=>0),
		   array('Name'=>'TYPE_PUSH','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>49,'Branch'=>0),
		   array('Name'=>'AGREEMENT','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>50,'Branch'=>0),
		   array('Name'=>'CHANGE_TYPE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>51,'Branch'=>0),
		   array('Name'=>'Test_Location','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>43,'Branch'=>0),
		   array('Name'=>'REQUEST_APP_TYPE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>37,'Branch'=>0),
		   array('Name'=>'COMP','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>46,'Branch'=>0),
		   array('Name'=>'Methode','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>45,'Branch'=>0),
		   array('Name'=>'LARGE_TYPE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>52,'Branch'=>0),
		   array('Name'=>'Test_Result','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>44,'Branch'=>0),
		   array('Name'=>'IS_ACTIVE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>54,'Branch'=>0),
		   array('Name'=>'APPLICANT','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>95,'Branch'=>0),
		   array('Name'=>'DOCUMENT_TYPE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>96,'Branch'=>0),
		   array('Name'=>'YESORNO','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>55,'Branch'=>0),
		   array('Name'=>'TYPE_PAID','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>92,'Branch'=>0),
		   array('Name'=>'BANKS','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>99,'Branch'=>0),
		   array('Name'=>'EMP_TYPE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>101,'Branch'=>0),
		   array('Name'=>'BRANCH_LARGE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>20,'Branch'=>$this->BranchID),
		   array('Name'=>'FFES_TYPE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>60,'Branch'=>0),
		   array('Name'=>'THE_EXCEPTION','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>164,'Branch'=>0),
		   array('Name'=>'EMP_SIDE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>165,'Branch'=>0),
		   array('Name'=>'PIN_CODE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>177,'Branch'=>0),
		   array('Name'=>'REQ_FE_TYPE','Procedure'=>'CONSTANTS_DETAIL_BRANCH_GET','VID'=>92,'Branch'=>0)
		);
		// Emptys Constant Array
		$this->general->Constantarray = array();
		// Add Value Of Array In General Array
	    $this->general->Constantarray = $ConstArr;
		// Define Tables And Call In Drop Down List
		$TableArr = array(
			array('Name'=>'BRANCH_JUNIOR','JSON'=>0,'Package'=>'TSG_SETTING_PKG',
			'Procedur'=>'CONSTANTS_TB_BRANCH_GETS','RowID'=>'VID','RowName'=>'VNAME',
			'where_sql'=>'AND M.BRANCH = '.$this->BranchID )
		);
		// Emptys Tables Array
		$this->general->Tablesarray = array();
		// Add Value Of Array In General Array
	    $this->general->Tablesarray = $TableArr;
		// Check ID
		$where_sql = 'where STAGE_DEFINITION_ID in(1) and REQUEST_CASE in(1) and FIELD_DETECTION_CASE=1';
		$where_sql.= ($ID!= null)? " and REQUEST_APP_SERIAL= '{$ID}' " : '';
		$this->Rmodel->package = $this->PackageService; 
		$Result = $this->Rmodel->getList('REQUEST_APP_TB_LIST',$where_sql, 0, 10);
		if(intval($Result) <= 0){
			if (HaveAccess('RequestUpdate')){
				redirect('RequestUpdate');
			}else{
				redirect('cpanel');
			}
		}else{
			if (HaveAccess('EditRequestUpdate')){
				if($Result[0]['SERVICE_ID'] == 11 or $Result[0]['SERVICE_ID'] == 12 or $Result[0]['SERVICE_ID'] == 13 or 
				$Result[0]['SERVICE_ID'] == 14){
					// Call Function Create From General Controller
					$this->general->get(1 , 'REQUEST_APP_TB_GET' , $ID , 'SUBSCRIBER_CHANGE_NAME_GET');
				}elseif($Result[0]['SERVICE_ID'] == 15 or $Result[0]['SERVICE_ID'] == 16){
					// Call Function Create From General Controller
					$this->general->get(1 , 'REQUEST_APP_TB_GET' , $ID , 'REQUEST_APP_SUBSC_GET');
				}elseif($Result[0]['SERVICE_ID'] == 17 or $Result[0]['SERVICE_ID'] == 18){
					// Call Function Create From General Controller
					$this->general->get(1 , 'REQUEST_APP_TB_GET' , $ID , 'REQUEST_APP_SUBSC_GET');
				}elseif($Result[0]['SERVICE_ID'] == 19 or $Result[0]['SERVICE_ID'] == 20 or $Result[0]['SERVICE_ID'] == 21 or 
				$Result[0]['SERVICE_ID'] == 22){
					// Call Function Create From General Controller
					$this->general->get(1 , 'REQUEST_APP_TB_GET' , $ID , 'UPDATE_LINK_TYPE_TB_GET');
				}else{
					
				}
			}else{
				redirect('cpanel');
			}
		}		
	}
	// Create Function
	function create(){
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
	      // Call Validation Function
		  $arrayIns = $this->_post_validation();
		  if($arrayIns ==0){
			$MasterArray = array('REQUEST_APP_SERIAL'=>$this->REQUEST_APP_SERIAL,'REQUEST_CASE'=>1,
			'SERVICE_TYPE'=>3,'SERVICE_ID'=>$this->SERVICE_ID,'APPLICANT'=>$this->APPLICANT,
			'APPLICANT_NAME'=>$this->APPLICANT_NAME,'JAWWAL'=>$this->JAWWAL,
			'TEL'=>$this->TEL,'EMAIL'=>$this->EMAIL,'DOCUMENT_TYPE'=>$this->DOCUMENT_TYPE,
			'STAGE_DEFINITION_ID'=>1,'GREAT_REGION'=>$this->GREAT_REGION,'JUNIOR_REGION'=>$this->JUNIOR_REGION,
			'PHASE_TYPE'=>$this->PHASE_TYPE,'DECLARATION'=>$this->DECLARATION,
			'FIELD_DETECTION_CASE'=>1,'APPLICANT_ID'=>$this->APPLICANT_ID,'APPLICANT_JOB'=>$this->APPLICANT_JOB,
			'PAYMENT_WAY'=>$this->PAYMENT_WAY,'REQUEST_SUBSCRIBER'=>$this->SUBSCRIBER,'PREFIX'=>$this->PREFIX);
			// Emptys Master Array
			$this->general->Masterarray = array();
			// Add Value Of General Master Array
			$this->general->Masterarray = $MasterArray;
			///INSERT
			$this->Rmodel->package = $this->PackageService;
			if($this->REQUEST_APP_SERIAL == 0){
				$Request = $this->Rmodel->insert('REQUEST_APP_TB_INSERT',$this->general->_postedData('create'));
				// CHECK IF NOT INSERT
				if(intval($Request) <= 0){
					// RETURN MESSAGE ERROR
					echo json_encode(array('o_success'=>0,'o_msgtxt'=>'لم يتم الحفظ في الجدول الرئيسي'));
				}else{
					echo json_encode(array('o_success'=>1,'o_id'=>$Request));
						
					//insert tractor
					$this->general->InsertIntoTractorFirstStage($Request);
					
					// تخزين بيانات الإشتراكات القديمة
					$this->general->InsertIntoREQUESTOLDSUBS($Request,$this->SUBSCRIBER);
				}
				// Define NEW MASTER ARRAY
				$MasterArray = array('ID'=>$this->APPLICANT_ID,'NAME'=>$this->APPLICANT_NAME,
				'RELATIONSHIP'=>$this->RELATIONSHIP);
				// Emptys Master Array	
				$this->general->Masterarray = array();
				// Add Value Of General Master Array
				$this->general->Masterarray = $MasterArray;
				if($this->APPLICANT_NAME != NULL){
					///INSERT
					$this->Rmodel->package = 'SERVICE_NEW';
					$Requests = $this->Rmodel->insert('NAME_LOOK_TB_INSERT1',$this->general->_postedData());
				}
			}elseif($this->REQUEST_APP_SERIAL != 0){
				
			}
		  }else{
			echo json_encode($arrayIns);
		  }
		}
	}
	// Create Function
	function createA($PID , $SERVICE){
		header("Content-type: application/json");
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			$prog = $this->Rmodel->getID('EBLLC_PKG', 'SUBSCRIBERS_TB_GET',$this->SUBSCRIBER_D);
			$prog1= $this->Rmodel->getID('SERVICES_PKG', 'REQUEST_APP_TB_GET',$PID);
			if($SERVICE == 11 or $SERVICE == 12 or $SERVICE == 13 or $SERVICE == 14){ // تغيير اسم / منتفع
					// Define NEW MASTER ARRAY
					$MasterArray = array('SER'=>$this->SER,'REQUEST_APP_SERIAL'=>$PID,
					'PAID_TYPE'=>count($prog1) > 0 ? $prog1[0]['PAYMENT_WAY'] : '','ID'=>count($prog) > 0 ? $prog[0]['ID'] : '',
					'SUBSCRIBER_NAME'=>count($prog) > 0 ? $prog[0]['NAME'] : '','JOB'=>$this->JOB,
					'BENIFICARY_NAME'=>count($prog) > 0 ? $prog[0]['BENIFICARY_NAME'] : '',
					'NEW_SUBSCRIBER_ID'=>$this->NEW_SUBSCRIBER_ID,'NEW_SUBSCRIBER_NAME'=>$this->NEW_SUBSCRIBER_NAME,
					'NEW_BENIFICARY_NAME'=>$this->NEW_BENIFICARY_NAME,'AGREEMENT_SUB_SELF'=>$this->AGREEMENT_SUB_SELF,
					'AGREEMENT_SUB_ID'=>$this->AGREEMENT_SUB_ID,'AGREEMENT_SUB_NAME'=>$this->AGREEMENT_SUB_NAME,
					'CHANGE_REASON'=>$this->CHANGE_REASON,'CHANGE_TYPE'=>$this->SERVICE_ID,
					'O_FILE_NO'=>count($prog) > 0 ? $prog[0]['FILENO'] : '','N_FILE_NO'=>count($prog1) > 0 ? $prog1[0]['FILE_NO'] : '',
					'N_USE_TYPE'=>$this->N_USE_TYPE,'N_ORG'=>$this->N_ORG);
					// Emptys Master Array
					$this->general->Masterarray = array();
					// Add Value Of General Master Array
					$this->general->Masterarray = $MasterArray;
					$this->Rmodel->package = $this->PackageService;
					///INSERT
					$retMaster = $this->Rmodel->insert('SUBSCRIBER_CHANGE_NAME_INSERT',$this->general->_postedData('create'));
					if(intval($retMaster) <= 0){
						// RETURN MESSAGE ERROR
						echo json_encode(array('o_success'=>0,'o_msgtxt'=>'لم يتم الحفظ في الجدول الفرعي'));
					}else{
						echo json_encode(array('o_success'=>1));
					}
				}else if($SERVICE == 15 or $SERVICE == 16 ){ // تصحيح اسم مشترك
					// Define NEW MASTER ARRAY
					$MasterArray = array('REQUEST_APP_SERIAL'=>$PID,'SUBSCRIBER_NAME'=>$this->SUBSCRIBER_NAMES);
					// Emptys Master Array
					$this->general->Masterarray = array();
					// Add Value Of General Master Array
					$this->general->Masterarray = $MasterArray;
					$this->Rmodel->package = $this->PackageService;
					///INSERT
					$retMaster = $this->Rmodel->insert('REQUEST_APP_SUBSC_N_NAME',$this->general->_postedData());
					if(intval($retMaster) <= 0){
						// RETURN MESSAGE ERROR
						echo json_encode(array('o_success'=>0,'o_msgtxt'=>'لم يتم الحفظ في الجدول الفرعي'));
					}else{
						echo json_encode(array('o_success'=>1));
					}
				}else if($SERVICE == 17 or $SERVICE == 18 ){ // تصحيح عنوان مشترك
					// Define NEW MASTER ARRAY
					$MasterArray = array('REQUEST_APP_SERIAL'=>$PID,'DISTRICT'=>$this->DISTRICT,'BLOCK_CODE'=>$this->BLOCK_CODE,
					'POS_CODE'=>$this->POS_CODE,'HOUSE_NO'=>$this->HOUSE_NO,'HOUSE_NAME'=>$this->HOUSE_NAME,'ADDRESS'=>$this->ADDRESS,
					'NEIGHBOR_NO'=>$this->NEIGHBOR_NO,'NEIGHBOR_LOC'=>$this->NEIGHBOR_LOC);
					// Emptys Master Array
					$this->general->Masterarray = array();
					// Add Value Of General Master Array
					$this->general->Masterarray = $MasterArray;
					$this->Rmodel->package = $this->PackageService;
					///INSERT
					$retMaster = $this->Rmodel->insert('REQUEST_APP_SUBSC_N_DISTRICT',$this->general->_postedData());
					
					if(intval($retMaster) <= 0){
						// RETURN MESSAGE ERROR
						echo json_encode(array('o_success'=>0,'o_msgtxt'=>'لم يتم الحفظ في الجدول الفرعي'));
					}else{
						echo json_encode(array('o_success'=>1));
					}
				}else if($SERVICE == 19 or $SERVICE == 20 or $SERVICE == 21 or $SERVICE == 22  ){ 
					//تعديل نوع الربط/ تصنيف المشترك
					// Define NEW MASTER ARRAY
					$MasterArray = array('SER'=>$this->SER1,'REQUEST_APP_SERIAL'=>$PID,
					'ID'=>count($prog) > 0 ? $prog[0]['ID'] : '','JOB'=>$this->JOBS,
					'SUB_TYPE'=>count($prog) > 0 ? $prog[0]['TYPE'] : '','NEW_ORG'=>$this->NEW_ORG,'NEW_LINK_TYPE'=>$this->NEW_LINK_TYPE,
					'NEW_USE_TYPE'=>$this->NEW_USE_TYPE,'STOREY_NO'=>$this->STOREY_NO,'FLAT_NO'=>$this->FLAT_NO,
					'QUOTIENTS_NO'=>$this->QUOTIENTS_NO,'SUBSCRIBER10_NO'=>$this->SUBSCRIBER10_NO,
					'SUBSCRIBER30_NO'=>$this->SUBSCRIBER30_NO,'IS_SHARING_SERVICE'=>$this->IS_SHARING_SERVICE,
					'HOUSING_UNITS_NO'=>$this->HOUSING_UNITS_NO,
					'OLD_LINK_TYPE'=>count($prog) > 0 ? $prog[0]['LINK_TYPE'] : '','OLD_ORG'=>count($prog) > 0 ? $prog[0]['ORG'] : '',
					'OLD_USE_TYPE'=>count($prog) > 0 ? $prog[0]['USE_TYPE'] : '','LINK_REASON'=>$this->LINK_REASON);
					// Emptys Master Array
					$this->general->Masterarray = array();
					// Add Value Of General Master Array
					$this->general->Masterarray = $MasterArray;
					$this->Rmodel->package = $this->PackageService;
					///INSERT
					$retMaster = $this->Rmodel->insert('UPDATE_LINK_TYPE_TB_INSERT',$this->general->_postedData('create'));
					if(intval($retMaster) <= 0){
						// RETURN MESSAGE ERROR
						echo json_encode(array('o_success'=>0,'o_msgtxt'=>'لم يتم الحفظ في الجدول الفرعي'));
					}else{
						echo json_encode(array('o_success'=>1));
					}
				}
		}
	}
	// Edit Function
	function edit(){
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			// Define NEW MASTER ARRAY
			$MasterArray = array('REQUEST_APP_SERIAL'=>$this->REQUEST_APP_SERIAL,'REQUEST_CASE'=>1,
			'SERVICE_TYPE'=>3,'SERVICE_ID'=>$this->SERVICE_ID,'SUBSCRIBER'=>$this->SUBSCRIBER,
			'SUBSCRIBER'=>$this->SUBSCRIBER,'APPLICANT'=>$this->APPLICANT,'APPLICANT_NAME'=>$this->APPLICANT_NAME,
			'JAWWAL'=>$this->JAWWAL,'TEL'=>$this->TEL,'EMAIL'=>$this->EMAIL,'DOCUMENT_TYPE'=>$this->DOCUMENT_TYPE,
			'STAGE_DEFINITION_ID'=>1,'GREAT_REGION'=>$this->GREAT_REGION,'JUNIOR_REGION'=>$this->JUNIOR_REGION,
			'PHASE_TYPE'=>$this->PHASE_TYPE,'DECLARATION'=>$this->DECLARATION,'FIELD_DETECTION_CASE'=>1,
			'APPLICANT_ID'=>$this->APPLICANT_ID,'APPLICANT_JOB'=>$this->APPLICANT_JOB,
			'PAYMENT_WAY'=>$this->PAYMENT_WAY,'REQUEST_SUBSCRIBER'=>$this->SUBSCRIBER,'PREFIX'=>$this->PREFIX);
			// Emptys Master Array
			$this->general->Masterarray = array();
			// Add Value Of General Master Array
			$this->general->Masterarray = $MasterArray;			
			///UPDATE
			$this->Rmodel->package = $this->PackageService;
			$Request = $this->Rmodel->update('REQUEST_APP_TB_UPDATE',$this->general->_postedData());
			// CHECK IF NOT INSERT
			if(intval($Request) <= 0){
				// RETURN MESSAGE ERROR
				echo json_encode(array('o_success'=>0,'o_msgtxt'=>'لم يتم الحفظ في الجدول الرئيسي'));
			}else{
				echo json_encode(array('o_success'=>1,'o_id'=>$Request));
			}
		}
	}
	// Edit Function
	function editA($PID , $SERVICE){
		header("Content-type: application/json");
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
				$prog = $this->Rmodel->getID('EBLLC_PKG', 'SUBSCRIBERS_TB_GET',$this->SUBSCRIBER_D);
			    $prog1= $this->Rmodel->getID('SERVICES_PKG', 'REQUEST_APP_TB_GET',$PID);			
				if($SERVICE == 11 or $SERVICE == 12 or $SERVICE == 13 or $SERVICE == 14){ // تغيير اسم / منتفع
					$prog3= $this->Rmodel->getID('SERVICES_PKG', 'SUBSCRIBER_CHANGE_NAME_GET',$PID);
					// Define NEW MASTER ARRAY
					$MasterArray = array('SER'=>$this->SER,'REQUEST_APP_SERIAL'=>$PID,
					'PAID_TYPE'=>count($prog1) > 0 ? $prog1[0]['PAYMENT_WAY'] : '','ID'=>count($prog) > 0 ? $prog[0]['ID'] : '',
					'SUBSCRIBER_NAME'=>count($prog) > 0 ? $prog[0]['NAME'] : '','JOB'=>$this->JOB,
					'BENIFICARY_NAME'=>count($prog) > 0 ? $prog[0]['BENIFICARY_NAME'] : '','NEW_SUBSCRIBER_ID'=>$this->NEW_SUBSCRIBER_ID,
					'NEW_SUBSCRIBER_NAME'=>$this->NEW_SUBSCRIBER_NAME,'NEW_BENIFICARY_NAME'=>$this->NEW_BENIFICARY_NAME,
					'AGREEMENT_SUB_SELF'=>$this->AGREEMENT_SUB_SELF,'AGREEMENT_SUB_ID'=>$this->AGREEMENT_SUB_ID,
					'AGREEMENT_SUB_NAME'=>$this->AGREEMENT_SUB_NAME,'CHANGE_REASON'=>$this->CHANGE_REASON,
					'CHANGE_TYPE'=>$this->SERVICE_ID,'O_FILE_NO'=>count($prog) > 0 ? $prog[0]['FILENO'] : '',
					'N_FILE_NO'=>count($prog1) > 0 ? $prog1[0]['FILE_NO'] : '','N_USE_TYPE'=>$this->N_USE_TYPE,'N_ORG'=>$this->N_ORG);
					// Emptys Master Array
					$this->general->Masterarray = array();
					// Add Value Of General Master Array
					$this->general->Masterarray = $MasterArray;
					$this->Rmodel->package = $this->PackageService;
					if(count($prog3) > 0 ){
						///UPDATE
						$retMaster = $this->Rmodel->update('SUBSCRIBER_CHANGE_NAME_UPDATE',$this->general->_postedData());
					}else{
						///INSERT
					    $retMaster = $this->Rmodel->insert('SUBSCRIBER_CHANGE_NAME_INSERT',$this->general->_postedData('create'));
					}
					if(intval($retMaster) <= 0){
						// RETURN MESSAGE ERROR
						echo json_encode(array('o_success'=>0,'o_msgtxt'=>'لم يتم الحفظ في الجدول الفرعي'));
					}else{
						
						echo json_encode(array('o_success'=>1));
					}
				}elseif($SERVICE == 15 or $SERVICE == 16){ // تصحيح اسم مشترك
					$prog4= $this->Rmodel->getID('SERVICES_PKG', 'REQUEST_APP_SUBSC_GET',$PID);
					// Define NEW MASTER ARRAY
					$MasterArray = array('REQUEST_APP_SERIAL'=>$PID,'SUBSCRIBER_NAME'=>$this->SUBSCRIBER_NAMES);
					// Emptys Master Array
					$this->general->Masterarray = array();
					// Add Value Of General Master Array
					$this->general->Masterarray = $MasterArray;
					$this->Rmodel->package = $this->PackageService;
					if(count($prog4) >  0 ) {
						///update
						$retMaster = $this->Rmodel->update('REQUEST_APP_SUBSC_UP_NAME',$this->general->_postedData());
					}else{
						$retMaster = $this->Rmodel->insert('REQUEST_APP_SUBSC_N_NAME',$this->general->_postedData());
					}
					if(intval($retMaster) <= 0){
						// RETURN MESSAGE ERROR
						echo json_encode(array('o_success'=>0,'o_msgtxt'=>'لم يتم الحفظ في الجدول الفرعي'));
					}else{
						echo json_encode(array('o_success'=>1));
					}
				}elseif($SERVICE == 17 or $SERVICE == 18){ // تصحيح عنوان مشترك
					$prog5= $this->Rmodel->getID('SERVICES_PKG', 'REQUEST_APP_SUBSC_GET',$PID);
					// Define NEW MASTER ARRAY
					$MasterArray = array('REQUEST_APP_SERIAL'=>$PID,'DISTRICT'=>$this->DISTRICT,'BLOCK_CODE'=>$this->BLOCK_CODE,
					'POS_CODE'=>$this->POS_CODE,'HOUSE_NO'=>$this->HOUSE_NO,'HOUSE_NAME'=>$this->HOUSE_NAME,'ADDRESS'=>$this->ADDRESS,
					'NEIGHBOR_NO'=>$this->NEIGHBOR_NO,'NEIGHBOR_LOC'=>$this->NEIGHBOR_LOC);
					// Emptys Master Array
					$this->general->Masterarray = array();
					// Add Value Of General Master Array
					$this->general->Masterarray = $MasterArray;
					$this->Rmodel->package = $this->PackageService;
					if(count($prog5) >  0 ) {
						///update
						$retMaster = $this->Rmodel->update('REQUEST_APP_SUBSC_UP_DISTRICT',$this->general->_postedData());
					}else{
						///INSERT
						$retMaster = $this->Rmodel->insert('REQUEST_APP_SUBSC_N_DISTRICT',$this->general->_postedData());
					}
					if(intval($retMaster) <= 0){
						// RETURN MESSAGE ERROR
						echo json_encode(array('o_success'=>0,'o_msgtxt'=>'لم يتم الحفظ في الجدول الفرعي'));
					}else{
						echo json_encode(array('o_success'=>1));
					}
				}elseif($SERVICE == 19 or $SERVICE == 20 or $SERVICE == 21 or $SERVICE == 22){ 
				    //   تعديل نوع الربط / تصنيف المشترك
					$prog6= $this->Rmodel->getID('SERVICES_PKG', 'UPDATE_LINK_TYPE_TB_GET',$PID);
					// Define NEW MASTER ARRAY
					$MasterArray = array('SER'=>$this->SER1,'REQUEST_APP_SERIAL'=>$PID,
					'ID'=>count($prog) > 0 ? $prog[0]['ID'] : '','JOB'=>$this->JOBS,
					'SUB_TYPE'=>count($prog) > 0 ? $prog[0]['TYPE'] : '','NEW_ORG'=>$this->NEW_ORG,'NEW_LINK_TYPE'=>$this->NEW_LINK_TYPE,
					'NEW_USE_TYPE'=>$this->NEW_USE_TYPE,
					'STOREY_NO'=>$this->STOREY_NO,'FLAT_NO'=>$this->FLAT_NO,'QUOTIENTS_NO'=>$this->QUOTIENTS_NO,
					'SUBSCRIBER10_NO'=>$this->SUBSCRIBER10_NO,'SUBSCRIBER30_NO'=>$this->SUBSCRIBER30_NO,
					'IS_SHARING_SERVICE'=>$this->IS_SHARING_SERVICE,
					'HOUSING_UNITS_NO'=>$this->HOUSING_UNITS_NO,'OLD_LINK_TYPE'=>count($prog) > 0 ? $prog[0]['LINK_TYPE'] : '',
					'OLD_ORG'=>count($prog) > 0 ? $prog[0]['ORG'] : '',
					'OLD_USE_TYPE'=>count($prog) > 0 ? $prog[0]['USE_TYPE'] : '',
					'LINK_REASON'=>$this->LINK_REASON);
					// Emptys Master Array
					$this->general->Masterarray = array();
					// Add Value Of General Master Array
					$this->general->Masterarray = $MasterArray;
					$this->Rmodel->package = $this->PackageService;
					if(count($prog6) >  0 ) {
						///UPDATE
						$retMaster = $this->Rmodel->update('UPDATE_LINK_TYPE_TB_UPDATE',$this->general->_postedData());
					}else{
						///INSERT
						$retMaster = $this->Rmodel->insert('UPDATE_LINK_TYPE_TB_INSERT',$this->general->_postedData('create'));
					}
					if(intval($retMaster) <= 0){
						// RETURN MESSAGE ERROR
						echo json_encode(array('o_success'=>0,'o_msgtxt'=>'لم يتم الحفظ في الجدول الفرعي'));
					}else{
						echo json_encode(array('o_success'=>1));
					}
				}
		}
	}
	// Edit related Function
	function createRelatedRequest(){
		header("Content-type: application/json");
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			if($this->TEN_SERVICE_ID == 11 || $this->TEN_SERVICE_ID == 12){ // تغيير اسم / تنازل
				if($this->TEN_PAYMENT_WAY == 3){
					// Define NEW MASTER ARRAY
					$MasterArray = array('REQUEST_APP_SERIAL'=>$this->TEN_REQUEST_APP_SERIAL,'SERVICE_TYPE'=>3,
					'SERVICE_ID'=>$this->IS_CREATE_CHANGE[0],'PAYMENT_WAY'=>$this->TEN_PAYMENT_WAY,'ENLARGE_TYPE'=>0,
					'SERVICE_TYPE_INP'=>4);
					// Emptys Master Array
					$this->general->Masterarray = array();
					// Add Value Of General Master Array
					$this->general->Masterarray = $MasterArray;
					$this->Rmodel->package = 'SERVICE_NEW';
					///INSERT
					$retMaster = $this->Rmodel->insert('REQUEST_APP_TB_RELATED',$this->general->_postedData());
					if(intval($retMaster) <= 0){
						// RETURN MESSAGE ERROR
						echo json_encode(array('o_success'=>0,'o_msgtxt'=>'لم يتم الحفظ في الجدول الفرعي'));
					}else{
						echo json_encode(array('o_success'=>1));
					}
				}else{
					// Define NEW MASTER ARRAY
					$MasterArray = array('REQUEST_APP_SERIAL'=>$this->TEN_REQUEST_APP_SERIAL,'SERVICE_TYPE'=>3,
					'SERVICE_ID'=>$this->IS_CREATE_CHECK[0],'PAYMENT_WAY'=>$this->TEN_PAYMENT_WAY,'ENLARGE_TYPE'=>0,
					'SERVICE_TYPE_INP'=>6);
					// Emptys Master Array
					$this->general->Masterarray = array();
					// Add Value Of General Master Array
					$this->general->Masterarray = $MasterArray;
					$this->Rmodel->package = 'SERVICE_NEW';
					///INSERT
					$retMaster = $this->Rmodel->insert('REQUEST_APP_TB_RELATED',$this->general->_postedData());
					//print_r($retMaster); 
					///////////////////////////////////////////////////////////////////////////////////////////
					if(intval($retMaster) <= 0){
						// RETURN MESSAGE ERROR
						echo json_encode(array('o_success'=>0,'o_msgtxt'=>'لم يتم الحفظ في الجدول الفرعي'));
					}else{
						// Define NEW MASTER ARRAY
						$MasterArray = array('REQUEST_APP_SERIAL'=>$this->TEN_REQUEST_APP_SERIAL,'SERVICE_TYPE'=>3,
						'SERVICE_ID'=>$this->IS_CREATE_STAMP[0],'PAYMENT_WAY'=>$this->TEN_PAYMENT_WAY,'ENLARGE_TYPE'=>0,
						'SERVICE_TYPE_INP'=>6);
						// Emptys Master Array
						$this->general->Masterarray = array();
						// Add Value Of General Master Array
						$this->general->Masterarray = $MasterArray;
						$this->Rmodel->package = 'SERVICE_NEW';
						///INSERT
						$retMaster = $this->Rmodel->insert('REQUEST_APP_TB_RELATED',$this->general->_postedData());
						//print_r($retMaster); die();
						if(intval($retMaster) <= 0){
							// RETURN MESSAGE ERROR
							echo json_encode(array('o_success'=>0,'o_msgtxt'=>'لم يتم الحفظ في الجدول الفرعي'));
						}else{
							echo json_encode(array('o_success'=>1));
						}
					}
				}
			}elseif($this->TEN_SERVICE_ID == 21 || $this->TEN_SERVICE_ID == 22){ // تعديل نوع الربط
					// Define NEW MASTER ARRAY
					$MasterArray = array('REQUEST_APP_SERIAL'=>$this->TEN_REQUEST_APP_SERIAL,'SERVICE_TYPE'=>3,
					'SERVICE_ID'=>$this->IS_CHANGE_LINK[0],'PAYMENT_WAY'=>$this->TEN_PAYMENT_WAY,'ENLARGE_TYPE'=>0,
					'SERVICE_TYPE_INP'=>3);
					// Emptys Master Array
					$this->general->Masterarray = array();
					// Add Value Of General Master Array
					$this->general->Masterarray = $MasterArray;
					$this->Rmodel->package = 'SERVICE_NEW';
					///INSERT
					$retMaster = $this->Rmodel->insert('REQUEST_APP_TB_RELATED',$this->general->_postedData());
					if(intval($retMaster) <= 0){
						// RETURN MESSAGE ERROR
						echo json_encode(array('o_success'=>0,'o_msgtxt'=>'لم يتم الحفظ في الجدول الفرعي'));
					}else{
						echo json_encode(array('o_success'=>1));
					}
			}
		}
	}
	// Validation Function 
	function _post_validation($isEdit = false){
		$return = array();
		//فحص اذا الإشتراك فعال
		$prog = $this->Rmodel->getID('EBLLC_PKG', 'SUBSCRIBERS_TB_GET',$this->SUBSCRIBER);
		if(count($prog) > 0 && $prog[0]['IS_ACTIVE'] != 1){
			//فحص اذا اله معاملة تفعيل اشتراك
			$check_active = $this->Rmodel->getID('SERVICE_NEW', 'REQUEST_TB_GET_SUBSCRIBER1',$this->SUBSCRIBER);
			if(count($check_active) > 0 ){
				
			}else{			
				array_push($return,array('o_success'=>0,'o_msgtxt'=>'الإشتراك غير فعال'));
			}
		}
		/// فحص اذا مش معاملة تغيير اسم / تنازل
		if($this->SERVICE_ID != 11 && $this->SERVICE_ID != 12){
			/*if(count($prog) > 0 ){
				if($prog[0]['ID'] != 0 || $prog[0]['ID'] != NULL){ // فحص صاحب الإشتراك اذا متوفى ولا لا
					$resFunc = $this->general->getIDSSN($prog[0]['ID']);
					if($resFunc == 1){
						//فحص اذا اله معاملة تغيير اسم
						$check_update = $this->Rmodel->getID('SERVICE_NEW', 'REQUEST_APP_TB_GET5',$this->SUBSCRIBER);
						if(count($check_update) > 0 ){ }else{
							array_push($return,array('o_success'=>0,
							'o_msgtxt'=>'صاحب الإشتراك المدرج متوفي , ولا يوجد له أي معاملة تغيير اسم أو المعاملة المقدمة مرفوضة'));
						}
					}
				}
			}
			if($this->APPLICANT_ID != ''){ // فحص حالة مقدم الطلب اذا متوفى ولا لا
				$resFunc = $this->general->getIDSSN($this->APPLICANT_ID);
				if($resFunc == 1){
					array_push($return,array('o_success'=>0,'o_msgtxt'=>'رقم الهويةالمدرج صاحبه متوفى'));
				}
			}*/
		}
		if(!$isEdit){
			$this->Rmodel->package = 'SERVICE_NEW' ;
        	$ret= $this->Rmodel->getTwoColum('REQUEST_APP_APPLICANT_ID_GET',$this->APPLICANT_ID,$this->SERVICE_ID);
			if(count($ret) > 0){
				if($this->GREAT_REGION == $ret[0]['GREAT_REGION'] && $this->JUNIOR_REGION ==  $ret[0]['JUNIOR_REGION']){
					if($this->SUBSCRIBER == $ret[0]['SUBSCRIBER']){
						array_push($return,array('o_success'=>0,'o_msgtxt'=>'يوجد طلب مسبق بنفس المدخلات'));
					}else{
						
					}
				}
			}
			if($this->APPLICANT==''){
				array_push($return,array('o_success'=>0,'o_msgtxt'=>'يجب اختيار مقدم الطلب'));
			}elseif($this->APPLICANT_NAME==''){
				array_push($return,array('o_success'=>0,'o_msgtxt'=>'يجب ادخال مقدم الطلب'));
			}elseif(strlen($this->APPLICANT_NAME) <= 2){
				array_push($return,array('o_success'=>0,'o_msgtxt'=>'يجب أن يكون اسم مقدم الطلب اكبر من حرفين'));
			}elseif($this->DOCUMENT_TYPE=='' && $this->APPLICANT != 3){
				array_push($return,array('o_success'=>0,'o_msgtxt'=>'يجب اختيار نوع الوثيقة'));
			}elseif($this->APPLICANT_ID=='' && $this->APPLICANT != 3){
				array_push($return,array('o_success'=>0,'o_msgtxt'=>'يجب ادخال الرقم التعريفي للوثيقة'));
			}elseif($this->APPLICANT_JOB==0){
				array_push($return,array('o_success'=>0,'o_msgtxt'=>'يجب اختيار نوع المهنة'));
			}elseif($this->SERVICE_ID==''){
				array_push($return,array('o_success'=>0,'o_msgtxt'=>'يجب اختيار الخدمة'));
			}elseif($this->JAWWAL==''){
				array_push($return,array('o_success'=>0,'o_msgtxt'=>'يجب ادخال رقم الجوال'));
			}elseif($this->GREAT_REGION==0){
				array_push($return,array('o_success'=>0,'o_msgtxt'=>'يجب اختيار المنطقة الكبرى'));
			}elseif($this->JUNIOR_REGION==0){
				array_push($return,array('o_success'=>0,'o_msgtxt'=>'يجب اختيار المنطقة الصغرى'));
			}elseif($this->PHASE_TYPE==0){
				array_push($return,array('o_success'=>0,'o_msgtxt'=>'يجب اختيار نوع الفاز'));
			}
		}
		if(count($return)> 0){ return $return; }else{ return 0; }
	}
	// Public Function Get Id
	function public_get_details($package,$procd,$id){
		 $this->general->public_get_details($package,$procd,$id);
	}
 }
?>