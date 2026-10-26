<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Projects_tree_model extends MY_Model
{
    var $PKG_NAME = 'GFC_PAK.FINANCIAL_PKG';

    // only level 3 is a real project
    const PICK_LEVEL = 3;

    function __construct()
    {
        parent::__construct();
        $this->load->model('Root/New_rmodel');
    }

    // children of one node
    function getList($parent = 0)
    {
        $params = array(
            array('name' => ':PARENT_ID_IN', 'value' => $parent, 'type' => '', 'length' => -1),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor', 'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT', 'value' => 'MSG_OUT', 'type' => SQLT_CHR, 'length' => -1)
        );
        return $this->New_rmodel->general_get($this->PKG_NAME, 'FIN_PROJECTS_TREE_TB_GET_LIST', $params);
    }

    // every node in one call
    function get_all()
    {
        $params = array(
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor', 'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT', 'value' => 'MSG_OUT', 'type' => SQLT_CHR, 'length' => -1)
        );
        return $this->New_rmodel->general_get($this->PKG_NAME, 'FIN_PROJECTS_TREE_TB_GET_ALL', $params);
    }

    // project balances in base currency
    function get_balances()
    {
        $params = array(
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor', 'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT', 'value' => 'MSG_OUT', 'type' => SQLT_CHR, 'length' => -1)
        );
        return $this->New_rmodel->general_get($this->PKG_NAME, 'FIN_PROJECTS_TREE_TB_BALANCES', $params);
    }

    // project numbers journal lines may use
    function pickable_project_nos()
    {
        $pickable = array();
        $rows = $this->get_all();
        if (!is_array($rows)) return $pickable;

        foreach ($rows as $row) {
            $project_no = isset($row['PROJECT_NO']) ? trim((string)$row['PROJECT_NO']) : '';
            if ($project_no !== '' && (int)$row['NODE_LEVEL'] === self::PICK_LEVEL) {
                $pickable[$project_no] = true;
            }
        }
        return $pickable;
    }

    // first bad row number, 0 when all fine
    function invalid_project_row($project_nos)
    {
        if (!is_array($project_nos)) return 0;

        $pickable = null;
        foreach ($project_nos as $i => $value) {
            // a field that was not posted is nothing to check
            if ($value === null) continue;

            $value = is_scalar($value) ? trim((string)$value) : '-';
            if ($value === '') continue;

            if ($pickable === null) $pickable = $this->pickable_project_nos();
            if (!isset($pickable[$value])) return (int)$i + 1;
        }
        return 0;
    }

    function get($id)
    {
        $params = array(
            array('name' => ':NODE_ID_IN', 'value' => $id, 'type' => '', 'length' => -1),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor', 'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT', 'value' => 'MSG_OUT', 'type' => SQLT_CHR, 'length' => -1)
        );
        return $this->New_rmodel->general_get($this->PKG_NAME, 'FIN_PROJECTS_TREE_TB_GET', $params);
    }

    function create($data)
    {
        $params = array();
        $this->_extract_data($params, $data);
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'FIN_PROJECTS_TREE_TB_INSERT', $params);
        return $result['MSG_OUT'];
    }

    function edit($data)
    {
        $params = array();
        $this->_extract_data($params, $data);
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'FIN_PROJECTS_TREE_TB_UPDATE', $params);
        return $result['MSG_OUT'];
    }

    function delete($id)
    {
        $params = array(
            array('name' => ':NODE_ID_IN', 'value' => $id, 'type' => '', 'length' => -1),
            array('name' => ':MSG_OUT', 'value' => 'MSG_OUT', 'type' => SQLT_CHR, 'length' => 500)
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, 'FIN_PROJECTS_TREE_TB_DELETE', $params);
        return $result['MSG_OUT'];
    }
}
