<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: fmamluk
 * Date: 02/06/26
 * Time: 12:00 م
 */

class Attendance_departure_model extends MY_Model{

    var $PKG_NAME= "HR_ATTENDANCE_PKG";
    var $TABLE_NAME= 'ATTENDANCE_DEPATURE_TB';


    function __construct(){
        parent::__construct();
        $this->load->model('Root/New_rmodel');
    }


    function get_list($sql,$offset,$row){
        $params =array(
            array('name'=>':INXSQL','value'=>"{$sql}",'type'=>'','length'=>-1),
            array('name'=>':OFFSET','value'=>$offset,'type'=>'','length'=>-1),
            array('name'=>':ROW','value'=>$row,'type'=>'','length'=>-1),
            array('name'=>':GET_ROW_LOG','value'=>'cursor','type'=>OCI_B_CURSOR),
            array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>'','length'=>500)
        );
        $result = $this->New_rmodel->general_get($this->PKG_NAME,$this->TABLE_NAME.'_LIST',$params);
        return $result;
    }

    function create($data){

        $params = array(
            array('name'=>':BRANCH_IN','value'=>$data['branch'],'type'=>'','length'=>100),
            array('name'=>':SUB_BRANCH_IN','value'=>$data['sub_branch'],'type'=>'','length'=>10),
            array('name'=>':USER_ID_IN','value'=>$data['user_id'],'type'=>'','length'=>50),
            array('name'=>':EMPLOYEE_ID_IN','value'=>$data['employee_id'],'type'=>'','length'=>50),
            array('name'=>':EMPLOYEE_NAME_IN','value'=>$data['name'],'type'=>'','length'=>200),
            array('name'=>':ATTENDANCE_DATE_IN','value'=>$data['date'],'type'=>'','length'=>20),
            array('name'=>':ATTENDANCE_TIME_IN','value'=>date('H:i:s',strtotime($data['time'])),'type'=>'','length'=>20),
            array('name'=>':STATUS_IN','value'=>$data['status'],'type'=>'','length'=>20),
            array('name'=>':FUNCTION_KEY_IN','value'=>$data['function_key'],'type'=>'','length'=>50),
            array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>'','length'=>500)
        );

        $result = $this->conn->excuteProcedures(
            $this->PKG_NAME,
            $this->TABLE_NAME.'_INSERT',
            $params
        );

        return $result['MSG_OUT'];
    }

    function get_all_for_excel($sql){

        $params = array(

            array('name'=>':INXSQL','value'=>$sql,'type'=>'','length'=>-1),
            array('name'=>':OFFSET','value'=>0,'type'=>'','length'=>-1),
            array('name'=>':ROW','value'=>99999,'type'=>'','length'=>-1),
            array('name'=>':GET_ROW_LOG','value'=>'cursor','type'=>OCI_B_CURSOR),
            array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>'','length'=>500)

        );

        $result = $this->New_rmodel->general_get(
            $this->PKG_NAME,
            $this->TABLE_NAME.'_LIST',
            $params
        );

        return $result;
    }
}
