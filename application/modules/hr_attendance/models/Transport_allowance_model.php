<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Transport_allowance_model extends MY_Model{

    var $PKG_NAME   = 'HR_ATTENDANCE_PKG';
    var $TABLE_NAME = 'TRANSPORT_ALLOWANCE_TB';

    function __construct(){
        parent::__construct();
        $this->load->model('Root/New_rmodel');
    }

    function get_list($sql, $offset, $row){

        $params = array(
            array('name'=>':INSQL','value'=>$sql,'type'=>'','length'=>-1),
            array('name'=>':OFFSET','value'=>$offset,'type'=>'','length'=>-1),
            array('name'=>':ROW','value'=>$row,'type'=>'','length'=>-1),
            array('name'=>':REF_CUR_OUT','value'=>'cursor','type'=>OCI_B_CURSOR),
            array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>'','length'=>500)
        );

        return $this->New_rmodel->general_get($this->PKG_NAME, $this->TABLE_NAME.'_LIST', $params);
    }

    function get($id){

        $params = array(
            array('name'=>':ID','value'=>$id,'type'=>'','length'=>-1),
            array('name'=>':REF_CUR_OUT','value'=>'cursor','type'=>OCI_B_CURSOR),
            array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>'','length'=>500)
        );

        return $this->New_rmodel->general_get($this->PKG_NAME, $this->TABLE_NAME.'_GET', $params);
    }

    function create($data){
        return $this->_execute('_INSERT', $data);
    }

    function edit($data){
        return $this->_execute('_UPDATE', $data);
    }

    function change_amount($data){
        return $this->_execute('_AMOUNT', $data);
    }

    function delete($id){

        $params = array(
            array('name'=>':ID','value'=>$id,'type'=>'','length'=>-1),
            array('name'=>':MSG_OUT','value'=>'MSG_OUT','type'=>'','length'=>500)
        );

        $result = $this->conn->excuteProcedures($this->PKG_NAME, $this->TABLE_NAME.'_DELETE', $params);

        return $result['MSG_OUT'];
    }

    private function _execute($suffix, $data){

        $params = array();
        $this->_extract_data($params, $data);

        $result = $this->conn->excuteProcedures($this->PKG_NAME, $this->TABLE_NAME.$suffix, $params);

        return $result['MSG_OUT'];
    }
}
