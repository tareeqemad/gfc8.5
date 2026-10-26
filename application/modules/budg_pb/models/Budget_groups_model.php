<?php
/**
 * User: mkilani
 * Date: 24/05/2026
 */

class Budget_groups_model extends MY_Model{
    var $PKG_NAME   = "BUDG_PB_PKG";
    var $TABLE_NAME = 'BUDGET_GROUPS';

    function __construct(){
        parent::__construct();
        $this->load->model('Root/New_rmodel');
    }

    function get($id= 0){
        $params =array(
            array('name'=>':ID','value'=>$id ,'type'=>'','length'=>-1),
            array('name'=>':REF_CUR_OUT','value'=>'cursor','type'=>OCI_B_CURSOR),
            array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>'','length'=>500)
        );
        $result = $this->New_rmodel->general_get($this->PKG_NAME, $this->TABLE_NAME.'_GET',$params);
        return $result;
    }

    function search($program_type=null, $program_no=null, $group_type=null, $emp_no=null, $name=null){
        $params =array(
            array('name'=>':PROGRAM_TYPE','value'=>$program_type ,'type'=>'','length'=>-1),
            array('name'=>':PROGRAM_NO','value'=>$program_no ,'type'=>'','length'=>-1),
            array('name'=>':GROUP_TYPE','value'=>$group_type ,'type'=>'','length'=>-1),
            array('name'=>':EMP_NO','value'=>$emp_no ,'type'=>'','length'=>-1),
            array('name'=>':NAME','value'=>$name ,'type'=>'','length'=>-1),
            array('name'=>':GET_ROW_LOG','value'=>'cursor','type'=>OCI_B_CURSOR),
            array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>'','length'=>500)
        );
        $result = $this->New_rmodel->general_get($this->PKG_NAME, $this->TABLE_NAME.'_SEARCH',$params);
        return $result;
    }

    function create($data){
        $params =array();
        $this->_extract_data($params,$data);
        $result = $this->conn->excuteProcedures($this->PKG_NAME, $this->TABLE_NAME.'_INSERT',$params);
        return $result['MSG_OUT'];
    }

    function edit($data){
        $params =array();
        $this->_extract_data($params,$data);
        $result = $this->conn->excuteProcedures($this->PKG_NAME, $this->TABLE_NAME.'_UPDATE',$params);
        return $result['MSG_OUT'];
    }

    function delete($id){
        $params =array(
            array('name'=>':SER','value'=>$id,'type'=>'','length'=>-1),
            array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>'','length'=>-1)
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, $this->TABLE_NAME.'_DELETE',$params);
        return $result['MSG_OUT'];
    }

}
