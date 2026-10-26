<?php
/**
 * User: mkilani
 * Date: 23/04/2026
 */

class Units_model extends MY_Model{
    var $PKG_NAME= "BUDG_PB_PKG";
    var $TABLE_NAME= '';

    function __construct(){
        parent::__construct();
        $this->load->model('Root/New_rmodel');
    }

    function create_activity_unit($data){
        $params =array();
        $this->_extract_data($params,$data);
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'ACTIVITIES_UNITS_INSERT',$params);
        return $result['MSG_OUT'];
    }

    function create_project_unit($data){
        $params =array();
        $this->_extract_data($params,$data);
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'PROJECTS_UNITS_INSERT',$params);
        return $result['MSG_OUT'];
    }

    function activity_unit_get_list($id){
        $params =array(
            array('name'=>':ID','value'=>$id ,'type'=>'','length'=>-1),
            array('name'=>':GET_ROW_LOG','value'=>'cursor','type'=>OCI_B_CURSOR),
            array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>'','length'=>500)
        );
        $result = $this->New_rmodel->general_get($this->PKG_NAME,'ACTIVITIES_UNITS_GET_LIST',$params);
        return $result;
    }

    function project_unit_get_list($id){
        $params =array(
            array('name'=>':ID','value'=>$id ,'type'=>'','length'=>-1),
            array('name'=>':GET_ROW_LOG','value'=>'cursor','type'=>OCI_B_CURSOR),
            array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>'','length'=>500)
        );
        $result = $this->New_rmodel->general_get($this->PKG_NAME,'PROJECTS_UNITS_GET_LIST',$params);
        return $result;
    }


    function delete_activity_unit($id){
        $params =array(
            array('name'=>':SER','value'=>$id,'type'=>'','length'=>-1),
            array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>'','length'=>-1)
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'ACTIVITIES_UNITS_DELETE',$params);
        return $result['MSG_OUT'];
    }

    function delete_project_unit($id){
        $params =array(
            array('name'=>':SER','value'=>$id,'type'=>'','length'=>-1),
            array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>'','length'=>-1)
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'PROJECTS_UNITS_DELETE',$params);
        return $result['MSG_OUT'];
    }

    function group_units_get_list($program_id){
        $params =array(
            array('name'=>':ID','value'=>$program_id ,'type'=>'','length'=>-1),
            array('name'=>':GET_ROW_LOG','value'=>'cursor','type'=>OCI_B_CURSOR),
            array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>'','length'=>500)
        );
        $result = $this->New_rmodel->general_get($this->PKG_NAME,'PROGRAM_GROUPS_UNITS_GET_LIST',$params);
        return $result;
    }

    function group_projects_get_list($group_id){
        $params =array(
            array('name'=>':ID','value'=>$group_id ,'type'=>'','length'=>-1),
            array('name'=>':GET_ROW_LOG','value'=>'cursor','type'=>OCI_B_CURSOR),
            array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>'','length'=>500)
        );
        $result = $this->New_rmodel->general_get($this->PKG_NAME,'PROGRAM_GROUP_PROJECTS_LIST',$params);
        return $result;
    }

    function create_group($data){
        $params =array();
        $this->_extract_data($params,$data);
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'PROGRAM_GROUP_INSERT',$params);
        return $result['MSG_OUT'];
    }

    function create_group_unit($data){
        $params =array();
        $this->_extract_data($params,$data);
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'PROGRAM_GROUP_UNITS_INSERT',$params);
        return $result['MSG_OUT'];
    }

    function delete_group_unit($id){
        $params =array(
            array('name'=>':SER','value'=>$id,'type'=>'','length'=>-1),
            array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>'','length'=>-1)
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'PROGRAM_GROUP_UNITS_DELETE',$params);
        return $result['MSG_OUT'];
    }

}