<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: mkilani
 * Date: 07/08/18
 * Time: 09:22 ص
 */

// ser, emp_no, year, entry_date, branch_id, function_key

class clock_data extends MY_Controller{

    var $MODEL_NAME= 'clock_data_model';
    var $PAGE_URL= 'hr_attendance/clock_data/get_page';
    var $PAGE_ACT;
    var $attendance_source = '';
    private $attendance_import_fetch_error = null;

    function  __construct(){
        parent::__construct();
        $this->load->model($this->MODEL_NAME);

        $this->page_act= $this->input->post('page_act');
        $this->ser= $this->input->post('ser');
        $this->emp_no= $this->input->post('emp_no');
        $this->year= $this->input->post('year');
        $this->entry_date= $this->input->post('entry_date');
        $this->entry_date_2= $this->input->post('entry_date_2');
        $this->function_key= $this->input->post('function_key');
        $this->branch_id= $this->input->post('branch_id');
        $this->emp_type= $this->input->post('emp_type');
        $this->attendance_source= $this->input->post('attendance_source');
        $this->delay= $this->input->post('delay');
        $this->no_leave= $this->input->post('no_leave');
        $this->no_entry= $this->input->post('no_entry');

        /*
        my - شاشة حضور الموظف
        manager - شاشة المدير المباشر او الاعلى للاعتماد
        move_admin - شاشة الشؤون الادارية حسب المقر
        hr_admin - شاشة للشؤون الادارية تظهر كل الموظفين
        */

        $this->PAGE_ACT= $this->uri->segment(5);

        if(isset($this->PAGE_ACT) and $this->PAGE_ACT!='' and ($this->uri->segment(3)== 'index' or $this->uri->segment(3)== 'get') ){

            if(!HaveAccess(base_url("hr_attendance/clock_data/index/1/".$this->PAGE_ACT))){
                die('Error: No Permission '.$this->PAGE_ACT);
            }

            if($this->PAGE_ACT=='my'){

            }elseif($this->PAGE_ACT=='manager'){
                die('Error01');
            }elseif($this->PAGE_ACT=='move_admin'){

            }elseif($this->PAGE_ACT=='hr_admin'){

            }else{
                die('PAGE_ACT');
            }
        }elseif($this->uri->segment(3)== 'index'){
            die('index');
        }
    }

    function index($page= 1, $page_act= -1, $delay= -1, $no_leave= -1, $no_entry= -1, $ser= -1, $emp_no= -1, $year= -1, $entry_date= -1, $entry_date_2= -1, $branch_id= -1, $function_key= -1, $emp_type= -1, $attendance_source= '' ){

        $yesterday = new DateTime('-1 day');
        $yesterday= $yesterday->format('d/m/Y');

        if(isset($this->PAGE_ACT)){
            if($this->PAGE_ACT=='my'){
                $data['title']='حضور وانصراف - الموظف';
                $entry_date=($entry_date!=-1)?$entry_date:date('1/m/Y');
            }elseif($this->PAGE_ACT=='manager'){
                $data['title']='حضور وانصراف - المدير';
            }elseif($this->PAGE_ACT=='move_admin'){
                $data['title']='حضور وانصراف - الشؤون الادارية - مسؤول الحركة في المقر';
                if($delay==1){
                    $data['title']='التأخير الصباحي - الشؤون الادارية - مسؤول الحركة في المقر';
                }elseif($no_leave==1){
                    $entry_date=($entry_date!=-1)?$entry_date:$yesterday;
                    $entry_date_2=($entry_date_2!=-1)?$entry_date_2:$entry_date;
                    $data['title']='بدون بصمة انصراف - الشؤون الادارية - مسؤول الحركة في المقر';
                }elseif($no_entry==1){
                    $entry_date=($entry_date!=-1)?$entry_date:$yesterday;
                    $entry_date_2=($entry_date_2!=-1)?$entry_date_2:$entry_date;
                    $data['title']='بدون بصمة حضور - الشؤون الادارية - مسؤول الحركة في المقر';
                }
                $entry_date=($entry_date!=-1)?$entry_date:date('d/m/Y');

            }elseif($this->PAGE_ACT=='hr_admin'){
                $data['title']='حضور وانصراف - الشؤون الادارية';
                if($delay==1){
                    $data['title']='التأخير الصباحي - الشؤون الادارية';
                }elseif($no_leave==1){
                    $entry_date=($entry_date!=-1)?$entry_date:$yesterday;
                    $entry_date_2=($entry_date_2!=-1)?$entry_date_2:$entry_date;
                    $data['title']='بدون بصمة انصراف - الشؤون الادارية';
                }elseif($no_entry==1){
                    $entry_date=($entry_date!=-1)?$entry_date:$yesterday;
                    $entry_date_2=($entry_date_2!=-1)?$entry_date_2:$entry_date;
                    $data['title']='بدون بصمة حضور - الشؤون الادارية';
                }
                $entry_date=($entry_date!=-1)?$entry_date:date('d/m/Y');

            }else{
                $data['title']=' حضور وانصراف؟؟';
            }
        }

        $data['content']='clock_data_index';

        //$data['entry_user_all'] = $this->get_entry_users('CLOCK_DATA_VW');

        $data['page']=$page;
        $data['page_act']= $page_act;
        $data['ser']= $ser;
        $data['emp_no']= $emp_no;
        $data['year']= $year;
        $data['entry_date']= $entry_date;
        $data['entry_date_2']= $entry_date_2;
        $data['branch_id']= $branch_id;
        $data['function_key']= $function_key;
        $data['emp_type']= $emp_type;
        $data['attendance_source']= $this->check_vars($attendance_source, 'attendance_source');
        $data['delay']= $delay;
        $data['no_leave']= $no_leave;
        $data['no_entry']= $no_entry;

        $data['help'] = $this->help;
        $data['action'] = 'edit';
        $this->_look_ups($data);

        $this->load->view('template/template',$data);

    }

    function get_page($page= 1, $page_act= -1, $delay= -1, $no_leave= -1, $no_entry= -1, $ser= -1, $emp_no= -1, $year= -1, $entry_date= -1, $entry_date_2= -1, $branch_id= -1, $function_key= -1, $emp_type= -1, $attendance_source= '' ){
        $this->load->library('pagination');

        $structure_tb= ' GFC_HR.HR_EMPS_STRUCTURE_TB ';

        $page_act= $this->check_vars($page_act,'page_act');
        $ser= $this->check_vars($ser,'ser');
        $emp_no= $this->check_vars($emp_no,'emp_no');
        $year= $this->check_vars($year,'year');
        $entry_date= $this->check_vars($entry_date,'entry_date');
        $entry_date_2= $this->check_vars($entry_date_2,'entry_date_2');
        $branch_id= $this->check_vars($branch_id,'branch_id');
        $function_key= $this->check_vars($function_key,'function_key');
        $emp_type= $this->check_vars($emp_type,'emp_type');
        $attendance_source= $this->check_vars($attendance_source,'attendance_source');
        $delay= $this->check_vars($delay,'delay');
        $no_leave= $this->check_vars($no_leave,'no_leave');
        $no_entry= $this->check_vars($no_entry,'no_entry');

        $where_sql= " where 1=1 ";
        $where_sql2= " ";

        $this->PAGE_ACT =$page_act;

        if(isset($this->PAGE_ACT)){
            if($this->PAGE_ACT=='my'){
                $where_sql.= " and emp_no= {$this->user->emp_no} ";
            }elseif($this->PAGE_ACT=='manager'){
                $where_sql.= " and emp_no in
                ( SELECT A.EMPLOYEE_NO
                FROM $structure_tb A
                WHERE A.EMPLOYEE_NO     != {$this->user->emp_no}
                START WITH A.EMPLOYEE_NO = {$this->user->emp_no}
                CONNECT BY PRIOR  A.EMPLOYEE_NO =A.MANAGER_NO )
                and adopt >= 10 ";
            }elseif($this->PAGE_ACT=='move_admin'){
                // تم دمج مقر الصيانة مع مقر غزة 202210
                $where_sql.= " and DECODE(branch_id,8,2,branch_id)= {$this->user->branch} and adopt >= 10 ";
            }elseif($this->PAGE_ACT=='hr_admin'){
                $where_sql.= " and 1= 1 ";
            }else{
                $where_sql.= " and 1= 2 ";
            }
        }else{
            $where_sql.= " and 1= 2 ";
        }

        $where_sql.= ($emp_type== 2)? " and emp_no >= 70000 " : " and emp_no < 70000 ";

        $where_sql.= ($ser!= null)? " and ser= '{$ser}' " : '';
        $where_sql.= ($emp_no!= null)? " and emp_no= '{$emp_no}' " : '';
        $where_sql.= in_special_emps($this->user->emp_no, 'emp_no');
        $where_sql.= ($year!= null)? " and year= '{$year}' " : '';
        $where_sql.= ($entry_date!= null or $entry_date_2!= null)? " and TRUNC(entry_date) between nvl('{$entry_date}','01/01/1000') and nvl('{$entry_date_2}','01/01/3000') " : '';
        $where_sql.= ($branch_id!= null)? " and branch_id= '{$branch_id}' " : '';
        $where_sql.= $function_key === 'NONE' ? ' and m.FUNCTION_KEY IS NULL '
            : (($function_key!= null)? " and function_key= '{$function_key}' " : '');
        $where_sql.= $this->_attendance_source_where_sql($attendance_source);

        // عرض المتأخرين عن الحضور
        $where_sql.= ($delay==1)? " and function_key= 1  and  hr_attendance_pkg.OFF_WORK_HOURS_DELAY_START( hr_attendance_pkg.OFF_WORK_HOURS_DETECT_ROW(1, entry_date) , entry_date ) = 1 " : '';
        // عرض اول سجل حضور فقط بدون تكرار السجلات الاخرى
        $where_sql2.= ($delay==1)? " and CNT_DUPLICATE= 1 " : '';

        // عرض من له بصمة حضور بدون بصمة انصراف
        $where_sql.= ($no_leave==1)? " and function_key= 1 and NOT EXISTS ( SELECT 1 from CLOCK_DATA_VW c where  m.emp_no= c.emp_no and TRUNC(m.entry_date) = TRUNC(c.entry_date) and c.function_key = 4 ) " : '';
        // استثناء من له اذن خروج حتى نهاية الدوام
        //$where_sql.= ($no_leave==1)? " and ( select count(0) from gfc_hr.exit_permission_tb e  where e.adopt >= 10 and TRUNC(e.p_ret_time)= e.p_date and ( TO_CHAR(e.p_ret_time,'HH24MI') = 1400 or ( TO_CHAR(e.p_ret_time,'HH24MI') = 1300 and qf_pkg.get_day_name_en(e.p_date)= 'THURSDAY' ) )  and e.emp_no= m.emp_no and e.p_date= TRUNC(m.entry_date) ) != 1 " : '';
        $where_sql.= ($no_leave==1)? " and ( select count(0) from gfc_hr.exit_permission_tb e  where e.adopt >= 10 and TRUNC(e.p_ret_time)= e.p_date and TO_CHAR(e.p_ret_time,'HH24MI') =  REPLACE( HR_ATTENDANCE_PKG.OFF_WORK_HOURS_END_TIME( HR_ATTENDANCE_PKG.OFF_WORK_HOURS_DETECT_ROW(1, e.p_date ) )  , ':', '' )   and e.emp_no= m.emp_no and e.p_date= TRUNC(m.entry_date) ) != 1 " : '';
        // عرض اول سجل حضور فقط بدون تكرار السجلات الاخرى
        $where_sql2.= ($no_leave==1)? " and CNT_DUPLICATE= 1 " : '';

        // عرض من له بصمة انصراف بدون بصمة حضور
        $where_sql.= ($no_entry==1)? " and function_key= 4 and NOT EXISTS ( SELECT 1 from CLOCK_DATA_VW c where  m.emp_no= c.emp_no and TRUNC(m.entry_date) = TRUNC(c.entry_date) and c.function_key = 1 ) " : '';
        // عرض اول سجل حضور فقط بدون تكرار السجلات الاخرى
        $where_sql2.= ($no_entry==1)? " and CNT_DUPLICATE= 1 " : '';


        $config['base_url'] = base_url($this->PAGE_URL);
        $count_rs = $this->get_table_count(' CLOCK_DATA_VW m '.$where_sql);
        $config['use_page_numbers'] = TRUE;
        $config['total_rows'] = count($count_rs)? $count_rs[0]['NUM_ROWS']:0 ;
        $config['per_page'] = $this->page_size;
        $config['num_links'] = 20;
        $config['cur_page']=$page;

        $config['full_tag_open'] = '<div class="pagination-container"><ul class="pagination">';
        $config['full_tag_close'] = '</ul></div>';
        $config['first_tag_open'] = $config['last_tag_open']= $config['next_tag_open']= $config['prev_tag_open'] = $config['num_tag_open'] = '<li>';
        $config['first_tag_close'] = $config['last_tag_close']= $config['next_tag_close']= $config['prev_tag_close'] = $config['num_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="active"><span><b>';
        $config['cur_tag_close'] = "</b></span></li>";

        $this->pagination->initialize($config);

        $offset = ((($page-1) * $config['per_page']) );
        $row = (($page * $config['per_page'])  );

        $data['page_rows'] = $this->{$this->MODEL_NAME}->get_list($where_sql, $offset , $row, $where_sql2 );

        $data['offset']=$offset+1;
        $data['page']=$page;

        $data['no_entry']= $no_entry;
        $data['no_leave']= $no_leave;

        $this->load->model('settings/constant_details_model');
        $data['function_key_edit'] = $this->constant_details_model->get_list(225);

        if( $this->PAGE_ACT=='move_admin' and HaveAccess(base_url("hr_attendance/clock_data/edit_status")) ){
            $data['CAN_EDIT_STATUS']= 1;
        }else{
            $data['CAN_EDIT_STATUS']= 0;
        }

        if($this->PAGE_ACT=='hr_admin' and $delay==1 and $emp_no!= null){
            $data['show_delay_month']= 1;
        }else{
            $data['show_delay_month']= 0;
        }

        $this->load->view('clock_data_page',$data);

    }

    private function _attendance_source_where_sql($source){
        if ($source === '' || $source === null) return '';
        if (!is_string($source) || !in_array($source, ['google_sheet', 'other'], true)) {
            return ' AND 1=2 ';
        }
        // Both direct imports and the one-time staging transfer carry this marker.
        // Correlate the full punch so an unrelated view row with the same SER
        // cannot be classified as imported. Keep the existing view and row scope.
        $match = "EXISTS (SELECT 1 FROM DATA.ATTENDANCE source_att
            WHERE source_att.NO = m.SER
              AND source_att.EMPLOYEENO = m.EMP_NO
              AND source_att.TTIME = m.ENTRY_DATE
              AND (source_att.STATUS = m.FUNCTION_KEY
                   OR (source_att.STATUS IS NULL AND m.FUNCTION_KEY IS NULL))
              AND source_att.TERMINAL = 'GFC attendance import')";
        return ' AND '.($source === 'other' ? 'NOT ' : '').$match.' ';
    }

    // ترحيل سجل بدون بصمة حضور لموظف - طارق
    function trans_no_entry(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $res= $this->{$this->MODEL_NAME}->trans_no_entry_leave($this->ser, $this->emp_no, $this->entry_date, 4);
            if(intval($res) == 1 ){
                echo 1;
            }else {
                $this->print_error('Error_'.$res);
            }
        }
    }

    function check_vars($var, $c_var){
        // if post take it, else take the parameter
        $var= ($this->{$c_var})? $this->{$c_var}:$var;
        // if val is -1 then null, else take the val
        $var= $var == -1 ?null:$var;
        return $var;
    }

    // تعديل نوع حركة الساعة
    function edit_status(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST'  and  $this->ser!='' and $this->emp_no!='' and $this->function_key!='' ){
            $res= $this->{$this->MODEL_NAME}->edit_status($this->ser, $this->emp_no, $this->function_key);
            if(intval($res) <= 0){
                $this->print_error('لم يتم الحفظ'.'<br>'.$res);
            }else{
                echo 1;
            }
        }
    }

    /** Import new punches directly into DATA.ATTENDANCE through its dedicated procedure. */
    public function import_google_sheet(){
        if (strtoupper((string)$this->input->server('REQUEST_METHOD')) !== 'POST') {
            return $this->_attendance_import_response(405, ['success'=>false, 'message'=>'الاستيراد متاح بطلب POST فقط.']);
        }

        if ($this->input->post('page_act') !== 'hr_admin'
            || !HaveAccess(base_url('hr_attendance/clock_data/index/1/hr_admin'))
            || !HaveAccess(base_url('hr_attendance/clock_data/import_google_sheet'))) {
            return $this->_attendance_import_response(403, ['success'=>false, 'message'=>'لا توجد صلاحية لاستيراد البصمات.']);
        }

        $posted_token = $this->input->post('__AntiForgeryToken');
        $expected_token = $this->AntiForgeryToken();
        if (!is_string($posted_token) || $posted_token === ''
            || !is_string($expected_token) || $expected_token === ''
            || !hash_equals($expected_token, $posted_token)) {
            return $this->_attendance_import_response(403, ['success'=>false, 'message'=>'تعذر التحقق من صلاحية الطلب. حدّث الصفحة وأعد المحاولة.']);
        }

        $from_date = $this->input->post('from_date');
        $to_date = $this->input->post('to_date');
        if (!$this->_is_attendance_import_date($from_date) || !$this->_is_attendance_import_date($to_date)) {
            return $this->_attendance_import_response(400, ['success'=>false, 'message'=>'يجب إدخال تاريخ بداية ونهاية صحيحين بصيغة YYYY-MM-DD.']);
        }
        if ($from_date > $to_date) {
            return $this->_attendance_import_response(400, ['success'=>false, 'message'=>'تاريخ البداية يجب أن يسبق تاريخ النهاية أو يساويه.']);
        }

        $this->attendance_import_fetch_error = null;
        try {
            $json = $this->_fetch_attendance_import_json();
        } catch (Throwable $error) {
            $json = false;
        }
        if (!is_string($json) || trim($json) === '') {
            $failure = $this->attendance_import_fetch_error;
            if (!is_array($failure)) {
                $failure = ['fetch_error'=>'connection', 'message'=>'تعذر اتصال الخادم بمصدر Google Sheet. أعد المحاولة، وإذا استمرت المشكلة افحص اتصال الخادم بالمصدر.'];
            }
            $failure['message'] .= ' لم يتم حفظ أي سجل.';
            return $this->_attendance_import_response(502, $failure);
        }

        $rows = json_decode($json, true, 512, JSON_BIGINT_AS_STRING);
        // Checking the opening bracket distinguishes the empty list [] from an object {}.
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($rows) || substr(ltrim($json), 0, 1) !== '[') {
            return $this->_attendance_import_response(502, ['success'=>false, 'fetch_error'=>'invalid_payload', 'message'=>'مصدر Google Sheet لم يرجع قائمة بصمات JSON صحيحة. افحص نتيجة نشر Apps Script. لم يتم حفظ أي سجل.']);
        }

        // Validate the entire feed envelope before any procedure call. Row business rules
        // (employee, sub-branch, time and movement) are validated by the model/procedure.
        foreach ($rows as $index => $row) {
            $valid_shape = is_array($row);
            foreach (['branch', 'sub_branch', 'date', 'time', 'function_key'] as $field) {
                if (!$valid_shape || !array_key_exists($field, $row)
                    || (!is_string($row[$field]) && !is_int($row[$field]) && !is_float($row[$field]))) {
                    $valid_shape = false;
                    break;
                }
            }
            // Empty/missing employee_id is a row-level case: the model may use
            // a valid device user_id. Keep malformed structured values invalid.
            if ($valid_shape && array_key_exists('employee_id', $row) && $row['employee_id'] !== null
                && !is_string($row['employee_id']) && !is_int($row['employee_id']) && !is_float($row['employee_id'])) {
                $valid_shape = false;
            }
            if ($valid_shape) {
                foreach (['user_id', 'name', 'status'] as $field) {
                    if (array_key_exists($field, $row) && $row[$field] !== null
                        && !is_string($row[$field]) && !is_int($row[$field])) {
                        $valid_shape = false;
                        break;
                    }
                }
            }
            $attendance_date = $valid_shape ? $this->_normalize_attendance_source_date($row['date']) : null;
            if ($attendance_date === null) {
                return $this->_attendance_import_response(502, [
                    'success'=>false,
                    'fetch_error'=>'invalid_row',
                    'message'=>'صيغة أو تاريخ السجل رقم '.($index + 1).' في مصدر البصمات غير صحيح. لم يتم حفظ أي سجل.'
                ]);
            }
            // Use the same calendar date for period filtering and the Oracle bind.
            $rows[$index]['date'] = $attendance_date;
        }

        $count = 0;
        $duplicates = 0;
        $failed = 0;
        $skipped = 0;
        $first_error = '';
        foreach ($rows as $index => $row) {
            if ($row['date'] < $from_date || $row['date'] > $to_date) {
                $skipped++;
                continue;
            }

            try {
                $result = $this->{$this->MODEL_NAME}->import_attendance($row);
            } catch (Throwable $error) {
                $result = 'تعذر حفظ السجل.';
            }
            if ($result === 'SUCCESS') {
                $count++;
            } elseif ($result === 'DUPLICATE_IGNORED') {
                $duplicates++;
            } else {
                $failed++;
                if ($first_error === '') {
                    $detail = is_string($result) && trim($result) !== '' ? trim($result) : 'تعذر قراءة نتيجة حفظ السجل.';
                    $first_error = 'السجل رقم '.($index + 1).': '.substr($detail, 0, 300);
                }
            }
        }

        // Each procedure call is committed independently by DBConn; report saved rows
        // even if a later row failed, so the user can safely retry after fixing it.
        // The screen formats the counters once; reserve this message for the
        // failure detail so it remains readable instead of repeating the totals.
        $message = '';
        if ($failed > 0) {
            $message = ($count > 0 ? 'السجلات التي نجح حفظها بقيت محفوظة. ' : '').$first_error;
        }
        return $this->_attendance_import_response(200, [
            'stage'=>'save',
            'success'=>$failed === 0,
            'count'=>$count,
            'duplicates'=>$duplicates,
            'failed'=>$failed,
            'skipped'=>$skipped,
            'message'=>$message
        ]);
    }

    protected function _fetch_attendance_import_json(){
        // The source is fixed on the server; never accept a URL from the request.
        $url = 'https://script.google.com/macros/s/AKfycbxPMumAM7M4eBLvseK8C1-ZfaJ3krLADgqXlDbrUmGKEUBY7lpyX_G3WX6l6paEe8lq/exec';
        $response = $this->_attendance_import_transport($url);
        $code = isset($response['error']) ? $response['error'] : '';
        $status = isset($response['status']) ? (int)$response['status'] : 0;
        $body = isset($response['body']) && is_string($response['body']) ? $response['body'] : '';
        $type = isset($response['type']) ? (string)$response['type'] : '';
        $host = isset($response['effective_url']) ? parse_url($response['effective_url'], PHP_URL_HOST) : '';

        if ($code === '') {
            if ($status === 401 || $status === 403 || $host === 'accounts.google.com') $code = 'http_auth';
            elseif ($status === 404 || $status === 410) $code = 'http_not_found';
            elseif ($status === 429) $code = 'http_rate_limit';
            elseif ($status >= 500) $code = 'http_server';
            elseif ($status < 200 || $status >= 300) $code = 'http_status';
            elseif (trim($body) === '') $code = 'empty_response';
            elseif (stripos($type, 'text/html') !== false || preg_match('/^\s*(?:<!doctype\s+html|<html\b|<head\b|<body\b)/i', $body)) $code = 'html_response';
        }
        if ($code === '') return $body;

        // Only controlled diagnostics reach the browser, never raw warnings, URLs or feed data.
        $messages = [
            'timeout'=>'انتهت مهلة جلب البصمات من Google Sheet بعد انتظار الخادم. أعد المحاولة؛ إذا تكرر الخطأ افحص زمن استجابة Apps Script.',
            'dns'=>'تعذر على الخادم الوصول إلى عنوان Google. افحص DNS واتصال الخادم بالإنترنت.',
            'tls'=>'تعذر التحقق من شهادة HTTPS لمصدر Google. افحص شهادات CA وإعدادات SSL على الخادم.',
            'redirect'=>'تعذر إكمال إعادة توجيه مصدر Google Sheet. افحص نشر Apps Script وإمكانية الوصول إلى script.googleusercontent.com من الخادم.',
            'transport_disabled'=>'جلب Google Sheet غير متاح على الخادم: فعّل امتداد PHP cURL أو إعداد allow_url_fopen.',
            'connection'=>'تعذر اتصال الخادم بمصدر Google Sheet. افحص اتصال الخادم بالمصدر وأعد المحاولة.',
            'http_auth'=>'مصدر Google Sheet رفض الوصول أو طلب تسجيل الدخول. راجع إعدادات وصول نشر Apps Script وطريقة مصادقة الخادم.',
            'http_not_found'=>'رابط نشر Apps Script غير موجود أو لم يعد متاحاً. تحقق من رابط النشر المعرّف في الخادم.',
            'http_rate_limit'=>'مصدر Google Sheet أوقف الطلب مؤقتاً لكثرة الطلبات. انتظر قليلاً وأعد المحاولة.',
            'http_server'=>'أرجع مصدر Google Sheet خطأ HTTP '.$status.'. أعد المحاولة وافحص سجل تنفيذ Apps Script إذا تكرر الخطأ.',
            'http_status'=>'أرجع مصدر Google Sheet استجابة HTTP غير ناجحة'.($status > 0 ? ' ('.$status.')' : '').'. تحقق من توفر نشر Apps Script.',
            'empty_response'=>'رجع مصدر Google Sheet برد فارغ. افحص نتيجة تنفيذ Apps Script.',
            'html_response'=>'رجع مصدر Google Sheet بصفحة HTML بدلاً من بيانات JSON؛ قد تكون صفحة دخول أو خطأ في النشر. افحص وصول الخادم ونشر Apps Script.'
        ];
        if (!isset($messages[$code])) $code = 'connection';
        $this->attendance_import_fetch_error = ['fetch_error'=>$code, 'message'=>$messages[$code]];
        return false;
    }

    /** Separate transport boundary permits offline tests without HTTP or database access. */
    protected function _attendance_import_transport($url){
        // Match the existing Attendance_departure importer on the same server.
        // Its working transport is file_get_contents, even when cURL is installed.
        if (filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
            return $this->_attendance_import_stream_transport($url);
        }
        return $this->_attendance_import_curl_transport($url);
    }

    protected function _attendance_import_curl_transport($url){
        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            if ($handle === false) return ['error'=>'connection'];
            set_error_handler(function () { return true; });
            try {
                $configured = curl_setopt_array($handle, [
                    CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>true, CURLOPT_MAXREDIRS=>5,
                    CURLOPT_CONNECTTIMEOUT=>15, CURLOPT_TIMEOUT=>90,
                    CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2,
                    CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS, CURLOPT_REDIR_PROTOCOLS=>CURLPROTO_HTTPS,
                    CURLOPT_HTTPHEADER=>['Accept: application/json']
                ]);
                if (!$configured) return ['error'=>'connection'];
                $body = curl_exec($handle);
                $errno = curl_errno($handle);
                $error = '';
                if ($errno === 28) $error = 'timeout';
                elseif ($errno === 5 || $errno === 6) $error = 'dns';
                elseif (in_array($errno, [35, 51, 58, 60, 77, 80, 82, 83, 90, 91], true)) $error = 'tls';
                elseif ($errno === 47) $error = 'redirect';
                elseif ($errno !== 0 || $body === false) $error = 'connection';
                return ['body'=>$body, 'error'=>$error, 'status'=>curl_getinfo($handle, CURLINFO_HTTP_CODE),
                    'type'=>curl_getinfo($handle, CURLINFO_CONTENT_TYPE), 'effective_url'=>curl_getinfo($handle, CURLINFO_EFFECTIVE_URL)];
            } finally {
                restore_error_handler();
                curl_close($handle);
            }
        }

        return ['error'=>'transport_disabled'];
    }

    protected function _attendance_import_stream_transport($url){
        $context = stream_context_create([
            'http'=>['timeout'=>90, 'follow_location'=>1, 'max_redirects'=>5, 'ignore_errors'=>true,
                'header'=>"Accept: application/json\r\n"],
            'ssl'=>['verify_peer'=>true, 'verify_peer_name'=>true, 'allow_self_signed'=>false]
        ]);
        $warnings = [];
        $http_response_header = [];
        set_error_handler(function ($severity, $message) use (&$warnings) { $warnings[] = $message; return true; });
        try {
            $body = file_get_contents($url, false, $context);
        } finally {
            restore_error_handler();
        }
        $status = 0;
        $type = '';
        foreach ($http_response_header as $header) {
            if (preg_match('~^HTTP/\S+\s+(\d{3})~i', $header, $match)) { $status = (int)$match[1]; $type = ''; }
            elseif (stripos($header, 'Content-Type:') === 0) $type = trim(substr($header, 13));
        }
        $error = '';
        if ($body === false) {
            $warning = implode('\n', $warnings);
            if (preg_match('/timed?\s*out|timeout/i', $warning)) $error = 'timeout';
            elseif (preg_match('/getaddrinfo|name resolution|host is known/i', $warning)) $error = 'dns';
            elseif (preg_match('/certificate|ssl|crypto/i', $warning)) $error = 'tls';
            elseif (stripos($warning, 'redirect') !== false) $error = 'redirect';
            else $error = 'connection';
        }
        return ['body'=>$body, 'error'=>$error, 'status'=>$status, 'type'=>$type];
    }

    private function _is_attendance_import_date($value){
        return is_string($value) && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $value)
            && checkdate((int)substr($value, 5, 2), (int)substr($value, 8, 2), (int)substr($value, 0, 4));
    }

    private function _normalize_attendance_source_date($value){
        // Preserve the legacy importer's year-first calendar-date normalization.
        // Do not infer day/month order or shift dates through a server timezone.
        if (!is_string($value)
            || !preg_match('~^([0-9]{4})([-/])([0-9]{1,2})\2([0-9]{1,2})$~D', trim($value), $parts)
            || !checkdate((int)$parts[3], (int)$parts[4], (int)$parts[1])) {
            return null;
        }
        return sprintf('%04d-%02d-%02d', (int)$parts[1], (int)$parts[3], (int)$parts[4]);
    }

    private function _attendance_import_response($status, $result){
        $stage = isset($result['fetch_error']) ? 'source' : 'request';
        $result = array_merge(['success'=>false, 'stage'=>$stage, 'count'=>0, 'duplicates'=>0, 'failed'=>0, 'skipped'=>0, 'message'=>''], $result);
        return $this->output->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE));
    }

    function _look_ups(&$data){
        $this->load->model('settings/gcc_branches_model');
        $this->load->model('settings/constant_details_model');
        $this->load->model('hr_attendance_model');
        $data['branches']= $this->gcc_branches_model->get_all();
        $data['function_key_cons'] = $this->constant_details_model->get_list(225);
        $data['emp_no_cons'] = $this->hr_attendance_model->get_child( $this->user->emp_no , $this->PAGE_ACT );

        add_css('select2_metro_rtl.css');
        add_js('select2.min.js');

        add_css('datepicker3.css');
        add_js('moment.js');
        add_js('bootstrap-datetimepicker.js');
    }

}
