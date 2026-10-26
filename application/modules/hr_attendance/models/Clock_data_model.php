<?php
/**
 * Created by PhpStorm.
 * User: mkilani
 * Date: 07/08/18
 * Time: 09:19 ص
 */

class clock_data_model extends MY_Model{
    var $PKG_NAME= "HR_ATTENDANCE_PKG";
    var $TABLE_NAME= 'CLOCK_DATA_VW';

    function __construct(){
        parent::__construct();
        $this->load->model('Root/New_rmodel');
    }

    function get_list($sql,$offset,$row,$sql2=''){

        $params =array(
            array('name'=>':INXSQL','value'=>"{$sql}",'type'=>'','length'=>-1),
            array('name'=>':INXSQL2','value'=>"{$sql2}",'type'=>'','length'=>-1),
            array('name'=>':OFFSET','value'=>$offset,'type'=>'','length'=>-1),
            array('name'=>':ROW','value'=>$row,'type'=>'','length'=>-1),
            array('name'=>':GET_ROW_LOG','value'=>'cursor','type'=>OCI_B_CURSOR),
            array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>'','length'=>500)
        );
        $result = $this->New_rmodel->general_get($this->PKG_NAME,$this->TABLE_NAME.'_LIST',$params);
        return $result;
    }

    /** Import external punches directly into DATA.ATTENDANCE. */
    function import_attendance($data){
        if (!is_array($data)) {
            return 'بيانات البصمة غير صالحة.';
        }
        // An empty employee number may use the device user number, as approved
        // for this feed. Never replace a present employee number or invent one.
        if (!array_key_exists('employee_id', $data) || $data['employee_id'] === null
            || (is_string($data['employee_id']) && trim($data['employee_id']) === '')) {
            $fallback_id = isset($data['user_id']) ? $data['user_id'] : null;
            if ((!is_string($fallback_id) && !is_int($fallback_id))
                || !preg_match('/^[1-9][0-9]{0,37}$/D', (string)$fallback_id)) {
                return 'رقم الموظف فارغ ولا يوجد user_id صالح لاستخدامه بدلاً منه.';
            }
            $data['employee_id'] = $fallback_id;
        }
        foreach (['branch', 'sub_branch', 'employee_id', 'date', 'time', 'function_key'] as $field) {
            if (!isset($data[$field]) || (!is_string($data[$field]) && !is_int($data[$field]))) {
                return 'بيانات البصمة غير مكتملة: '.$field;
            }
        }
        foreach (['branch'=>2, 'sub_branch'=>2, 'employee_id'=>38] as $field=>$digits) {
            if (!preg_match('/^[1-9][0-9]{0,'.($digits - 1).'}$/D', (string)$data[$field])) {
                return 'رقم الموظف أو المقر أو المقر الفرعي غير صالح.';
            }
        }
        $date = (string)$data['date'];
        if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $date)
            || !checkdate((int)substr($date, 5, 2), (int)substr($date, 8, 2), (int)substr($date, 0, 4))) {
            return 'تاريخ البصمة غير صالح؛ الصيغة المطلوبة YYYY-MM-DD.';
        }
        $time = trim((string)$data['time']);
        // Apps Script serializes Google time cells as UTC timestamps anchored in
        // 1899. Match the old importer's date('H:i:s', strtotime(...)) using the
        // configured timezone and the embedded date (including its historic offset).
        // The separate attendance date above remains the actual punch day.
        if (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})T([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9](?:\.[0-9]{1,6})?Z$/D', $time, $iso_parts)
            && checkdate((int)$iso_parts[2], (int)$iso_parts[3], (int)$iso_parts[1])) {
            $timestamp = strtotime($time);
            if ($timestamp !== false) {
                $time = date('H:i:s', $timestamp);
            }
        }
        // Google Sheet can omit the leading zero or display a 12-hour clock.
        // Plain clock values need no timezone conversion.
        $time_error = 'وقت البصمة غير صالح؛ مثال صحيح: 9:05:30 أو 09:05:30، ويمكن استخدام AM/PM.';
        if (!preg_match('/^([0-9]{1,2}):([0-5][0-9])(?::([0-5][0-9]))?(?:[ \t]*([AP]M))?$/iD', $time, $time_parts)) {
            return $time_error;
        }
        $hour = (int)$time_parts[1];
        $meridiem = isset($time_parts[4]) ? strtoupper($time_parts[4]) : '';
        if ($meridiem !== '') {
            if ($hour < 1 || $hour > 12) return $time_error;
            $hour = $hour % 12 + ($meridiem === 'PM' ? 12 : 0);
        } elseif ($hour > 23) {
            return $time_error;
        }
        $second = isset($time_parts[3]) && $time_parts[3] !== '' ? (int)$time_parts[3] : 0;
        $time = sprintf('%02d:%02d:%02d', $hour, (int)$time_parts[2], $second);
        $function_key = trim((string)$data['function_key']);
        if (!in_array(strtoupper($function_key), [
            'ENTRY', 'F1', 'LEAVE WITH FORM', 'F2', 'ENTRY WITH FORM', 'F3', 'LEAVE', 'LEFT', 'F4', 'NONE'
        ], true)) {
            // Include the actual source value so a rejected movement can be
            // corrected without guessing entry/leave from the device status.
            $display_key = json_encode(substr($function_key, 0, 100), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            return 'نوع البصمة غير معروف؛ قيمة FUNCTION_KEY: '.$display_key.'؛ يجب تصحيح نوع الحركة في المصدر.';
        }
        $device_user = isset($data['user_id']) ? $data['user_id'] : null;
        if ($device_user === '') $device_user = null;
        if ($device_user !== null && ((!is_string($device_user) && !is_int($device_user))
            || !preg_match('/^[0-9]{1,38}$/D', (string)$device_user))) {
            return 'رقم مستخدم جهاز البصمة غير صالح.';
        }
        foreach (['name', 'status'] as $field) {
            if (isset($data[$field]) && !is_string($data[$field]) && !is_int($data[$field])) {
                return 'بيانات البصمة غير صالحة: '.$field;
            }
        }

        // Keep the source payload signature; numeric attendance status comes from
        // FUNCTION_KEY (NONE stays unspecified) and ENTRY_USER comes from the authenticated Oracle session.
        $params = [
            ['name'=>':BRANCH_IN', 'value'=>$data['branch'], 'type'=>'', 'length'=>38],
            ['name'=>':SUB_BRANCH_IN', 'value'=>$data['sub_branch'], 'type'=>'', 'length'=>38],
            ['name'=>':USER_ID_IN', 'value'=>$device_user, 'type'=>'', 'length'=>38],
            ['name'=>':EMPLOYEE_ID_IN', 'value'=>$data['employee_id'], 'type'=>'', 'length'=>38],
            ['name'=>':EMPLOYEE_NAME_IN', 'value'=>isset($data['name']) ? (string)$data['name'] : null, 'type'=>'', 'length'=>-1],
            ['name'=>':ATTENDANCE_DATE_IN', 'value'=>$date, 'type'=>'', 'length'=>20],
            ['name'=>':ATTENDANCE_TIME_IN', 'value'=>$time, 'type'=>'', 'length'=>20],
            ['name'=>':STATUS_IN', 'value'=>isset($data['status']) ? (string)$data['status'] : null, 'type'=>'', 'length'=>-1],
            ['name'=>':FUNCTION_KEY_IN', 'value'=>$function_key, 'type'=>'', 'length'=>50],
            ['name'=>':MSG_OUT', 'value'=>'MSG_OUT', 'type'=>'', 'length'=>4000]
        ];
        $failure = 'تعذر حفظ البصمة في جدول الحضور والانصراف.';
        $result = null;
        // DBConn prints Oracle errors and commits each call. Preserve valid JSON
        // and never count an unchanged OUT sentinel as a successful insert.
        ob_start();
        try {
            $result = $this->conn->excuteProcedures($this->PKG_NAME, 'ATTENDANCE_IMPORT_INSERT', $params);
        } catch (Throwable $error) {
            return $failure;
        } finally {
            $transport_output = ob_get_clean();
        }
        if (!is_array($result) || (isset($result['success']) && !$result['success'])) {
            return isset($result['err_msg']) && is_string($result['err_msg']) && trim($result['err_msg']) !== ''
                ? $result['err_msg'] : $failure;
        }
        if (trim($transport_output) !== '' || !isset($result['MSG_OUT']) || !is_string($result['MSG_OUT'])) {
            return $failure;
        }
        $message = trim($result['MSG_OUT']);
        return $message !== '' && $message !== 'MSG_OUT' ? $message : $failure;
    }

    function trans_no_entry_leave($ser, $emp_no, $entry_date, $status)
    {
        $params = array(
            array('name' => ':SER', 'value' => $ser, 'type' => '', 'length' => -1),
            array('name' => ':EMP_NO', 'value' => $emp_no, 'type' => '', 'length' => -1),
            array('name' => ':ENTRY_DATE', 'value' => $entry_date, 'type' => '', 'length' => -1),
            array('name' => ':STATUS', 'value' => $status, 'type' => '', 'length' => -1),
            array('name' => ':MSG_OUT', 'value' => 'MSG_OUT', 'type' => '', 'length' => -1)
        );
        $result = $this->conn->excuteProcedures('TRANSACTION_PKG', 'NO_ENTRY_LEAVE_TB_TRANS', $params);
        return $result['MSG_OUT'];
    }

    function edit_status($ser, $emp_no, $status){
        $params =array(
            array('name'=>':SER','value'=>$ser,'type'=>'','length'=>-1),
            array('name'=>':EMP_NO','value'=>$emp_no,'type'=>'','length'=>-1),
            array('name'=>':STATUS','value'=>$status,'type'=>'','length'=>-1),
            array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>'','length'=>-1)
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'DATA_ATTENDANCE_UPDATE',$params);
        return $result['MSG_OUT'];
    }

}
