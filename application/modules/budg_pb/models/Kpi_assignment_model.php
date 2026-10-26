<?php

/**
 * User: lanakh
 * Date: 18/11/17
 * Time: 09:22 ص
 */

class Kpi_assignment_model extends MY_Model
{
    var $PKG_NAME = "BUDG_PB_PKG";
    var $TABLE_NAME = 'KPI_ASSIGNMENT';

    function __construct()
    {
        parent::__construct();
        $this->load->model('Root/New_rmodel');
    }

    function kpi_assignment($year, $id, $objective, $goal, $goal_t)
    {
        $params = array(

            array('name' => ':YEAR', 'value' => $year, 'type' => '', 'length' => -1),
            array('name' => ':ID', 'value' => $id, 'type' => '', 'length' => -1),
            array('name' => ':OBJECTIVE', 'value' => $objective, 'type' => '', 'length' => -1),
            array('name' => ':GOAL', 'value' => $goal, 'type' => '', 'length' => -1),
            array('name' => ':GOAL_T', 'value' => $goal_t, 'type' => '', 'length' => -1),
            array('name' => ':MSG_OUT', 'value' => 'MSG_OUT', 'type' => '', 'length' => 500)
        );
        $result = $this->New_rmodel->general_get($this->PKG_NAME, $this->TABLE_NAME . '_INSERT', $params);
        return $result['MSG_OUT'];
    }
}
