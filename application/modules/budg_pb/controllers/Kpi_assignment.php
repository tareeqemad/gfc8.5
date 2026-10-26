<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: lanakh
 * Date: 18/11/17
 * Time: 09:22 ص
 */
class Kpi_assignment extends MY_Controller
{

    var $MODEL_NAME = 'Extract_model';
    var $PAGE_URL = 'budg_pb/Kpi_assignment/get_page';

    function __construct()
    {
        parent::__construct();
        $this->load->model('planning/plan_model');
        $this->load->model('indicator/indicator_model');
    }

    function index($page = 1)
    {
        $data['title'] = 'تنسيب المؤشرات';
        $data['content'] = 'kpi_assignment_index';
        $data['help'] = $this->help;
        $this->_look_ups($data);
        $this->load->view('template/template', $data);
    }

    /***************************************************************************************************************************************/

    function get_page($page = 1)
    {



        $this->load->library('pagination');
        $where_sql = " AND  EFFECT=1 ";

        /*$where_sql .= isset($this->p_FROM_YEAR) && $this->p_FROM_YEAR != null ? " AND  FROM_YEAR  ={$this->p_FROM_YEAR}  " : "";
        $where_sql .= isset($this->p_TO_YEAR) && $this->p_TO_YEAR != null ? " AND  TO_YEAR  ={$this->p_TO_YEAR}  " : "";
        $where_sql .= isset($this->p_dp_plan) && $this->p_dp_plan != null ? " AND  DP_PLAN  ={$this->p_dp_plan}  " : "";
        $where_sql .= isset($this->p_dp_objective) && $this->p_dp_objective != null ? " AND  DP_OBJECTIVE  ={$this->p_dp_objective}  " : "";
        $where_sql .= isset($this->p_dp_goal) && $this->p_dp_goal != null ? " AND  DP_GOAL  ={$this->p_dp_goal}  " : "";
        $where_sql .= isset($this->p_dp_goal_t) && $this->p_dp_goal_t != null ? " AND  DP_GOAL_T  ={$this->p_dp_goal_t}  " : "";*/

        $config['base_url'] = base_url($this->PAGE_URL);
        $count_rs = $this->get_table_count('INDECATOR_INFO_TB', $where_sql);
        $config['use_page_numbers'] = TRUE;
        $config['total_rows'] = count($count_rs) ? $count_rs[0]['NUM_ROWS'] : 0;
        $config['per_page'] = $this->page_size;
        $config['num_links'] = 20;
        $config['cur_page'] = $page;

        $config['full_tag_open'] = '<div class="pagination-container"><ul class="pagination">';
        $config['full_tag_close'] = '</ul></div>';
        $config['first_tag_open'] = $config['last_tag_open'] = $config['next_tag_open'] = $config['prev_tag_open'] = $config['num_tag_open'] = '<li>';
        $config['first_tag_close'] = $config['last_tag_close'] = $config['next_tag_close'] = $config['prev_tag_close'] = $config['num_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="active"><span><b>';
        $config['cur_tag_close'] = "</b></span></li>";

        $this->pagination->initialize($config);

        $offset = (((($page) - 1) * $config['per_page']));
        $row = ((($page) * $config['per_page']));
        $data['page_rows'] = $this->indicator_model->get_list_indicatior($where_sql, $offset, $row);
        $data['offset'] = $offset + 1;
        $data['page'] = $page;
        $this->_look_ups($data);
        //$this->load->view('indicator/indicatior_page', $data);
        $this->load->view('kpi_assignment_page', $data);
    }
    /*******************************************************************************************************************************/
    function assignment()
    {

        $id = $this->input->post('id');
        $year = $this->input->post('year');
        $objective = $this->input->post('objective');
        $goal = $this->input->post('goal');
        $goal_t = $this->input->post('goal_t');
        if (!isset($id) && !isset($year) && !isset($objective) && !isset($goal) && !isset($goal_t)) {
            $this->print_error('يجب تحديد جميع البيانات الازمة -(عام-محور-الغاية-الهدف الاستراتيجي)!!)');
        }
        for ($i = 0; $i < count($id); $i++) {
            $result = $this->{$this->MODEL_NAME}->kpi_assignment($year, $id[$i], $objective, $goal, $goal_t);
            if (intval($result) <= 0) {
                $this->print_error('لم يتم الحفظ');
            }
        }


        echo intval($result);
    }

    /*******************************************************************************************************************************/
    function _look_ups(&$data)
    {

        add_js('moment.js');
        add_js('bootstrap.min.js');
        add_js('select2.min.js');
        add_css('select2_metro_rtl.css');
        add_css('font-awesome.min.css');
        add_js('jquery.hotkeys.js');
        add_css('jquery.dataTables.css');
        add_js('jquery.dataTables.js');


        $data['all_objective'] = $this->plan_model->get_objective('', 0, date('Y'));
    }
}
