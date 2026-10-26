<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * User: mkilani
 * Date: 24/05/2026
 */

class Budget_groups extends MY_Controller
{

    var $MODEL_NAME = 'Budget_groups_model';
    var $PAGE_URL   = 'budg_pb/budget_groups/get_page';

    // أرقام جداول الثوابت في GFC.CONSTANT_TB
    const TB_GROUP_TYPE   = 548;   // نوع العضو
    const TB_PROGRAM_TYPE = 549;   // نوع الربط

    function __construct()
    {
        parent::__construct();
        $this->load->model($this->MODEL_NAME);
        $this->load->model('programs_model');
        $this->load->model('Activities_model');

        // الحقول التشغيلية للسجل
        $this->ser          = $this->input->post('ser');
        $this->emp_no       = $this->input->post('emp_no');
        $this->national_id  = $this->input->post('national_id');
        $this->name         = $this->input->post('name');
        $this->group_type   = $this->input->post('group_type');
        $this->program_type = $this->input->post('program_type');
        $this->program_no   = $this->input->post('program_no');
        $this->email        = $this->input->post('email');
        $this->mobile_no    = $this->input->post('mobile_no');
        $this->notes        = $this->input->post('notes');

        // فلاتر البحث
        $this->f_program_type = $this->input->post('f_program_type');
        $this->f_program_no   = $this->input->post('f_program_no');
        $this->f_group_type   = $this->input->post('f_group_type');
        $this->f_emp_no       = $this->input->post('f_emp_no');
        $this->f_name         = $this->input->post('f_name');
    }

    function index($page = 1){
        add_css('select2_metro_rtl.css');
        add_js('select2.min.js');

        $data['title']   = 'أعضاء لجنة التخطيط والموازنة';
        $data['content'] = 'budget_groups_index';
        $data['page']    = $page;
        $data['action']  = 'edit';
        $this->_look_ups($data);

        $this->load->view('template/template', $data);
    }

    function get_page($page = 1){
        $data['page_rows'] = $this->{$this->MODEL_NAME}->search(
            $this->f_program_type,
            $this->f_program_no,
            $this->f_group_type,
            $this->f_emp_no,
            $this->f_name
        );
        $data['page'] = $page;
        $this->load->view('budget_groups_page', $data);
    }

    function get($id = 0){
        $result = $this->{$this->MODEL_NAME}->get($id);
        if (!(count($result) == 1))
            die('get');
        $this->return_json($result[0]);
    }

    function get_activities_list($program_id = 0){
        $result = $this->Activities_model->get_list($program_id);
        $this->return_json($result);
    }

    function public_members_box($program_type = 0, $program_no = 0){
        $data['rows'] = $this->{$this->MODEL_NAME}->search($program_type, $program_no, null, null, null);
        $this->load->view('budget_groups_members_box', $data);
    }

    function create(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST'){
            $this->ser = $this->{$this->MODEL_NAME}->create($this->_postedData('create'));
            if (intval($this->ser) <= 0){
                $this->print_error(' لم يتم الحفظ '.'<br>'.$this->ser);
            } else {
                echo intval($this->ser);
            }
        }
    }

    function edit(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST'){
            $res = $this->{$this->MODEL_NAME}->edit($this->_postedData());
            if (intval($res) <= 0){
                $this->print_error('لم يتم الحفظ'.'<br>'.$res);
            } else {
                echo 1;
            }
        }
    }

    function delete(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST'){
            $ser = $this->input->post('ser');
            $res = $this->{$this->MODEL_NAME}->delete($ser);
            if (intval($res) <= 0){
                $this->print_error('لم يتم الحذف'.'<br>'.$res);
            } else {
                echo 1;
            }
        }
    }

    function _look_ups(&$data){
        $this->load->model('hr_attendance/hr_attendance_model');
        $this->load->model('settings/constant_details_model');

        $data['emp_no_cons']   = $this->hr_attendance_model->get_child($this->user->emp_no, 'hr_admin');
        $data['programs']      = $this->programs_model->get_all();

        // ثوابت من GFC.CONSTANT_DETAILS_TB
        $data['group_types']   = $this->constant_details_model->get_list(self::TB_GROUP_TYPE);
        $data['program_types'] = $this->constant_details_model->get_list(self::TB_PROGRAM_TYPE);
    }

    function _postedData($typ = null){
        $result = array(
            array('name'=>'SER','value'=>$this->ser ,'type'=>'','length'=>-1),
            array('name'=>'EMP_NO','value'=>$this->emp_no ,'type'=>'','length'=>-1),
            array('name'=>'ID','value'=>$this->national_id ,'type'=>'','length'=>-1),
            array('name'=>'NAME','value'=>$this->name ,'type'=>'','length'=>-1),
            array('name'=>'GROUP_TYPE','value'=>$this->group_type ,'type'=>'','length'=>-1),
            array('name'=>'PROGRAM_TYPE','value'=>$this->program_type ,'type'=>'','length'=>-1),
            array('name'=>'PROGRAM_NO','value'=>$this->program_no ,'type'=>'','length'=>-1),
            array('name'=>'EMAIL','value'=>$this->email ,'type'=>'','length'=>-1),
            array('name'=>'MOBILE_NO','value'=>$this->mobile_no ,'type'=>'','length'=>-1),
            array('name'=>'NOTES','value'=>$this->notes ,'type'=>'','length'=>-1),
        );
        if ($typ == 'create')
            unset($result[0]);
        return $result;
    }

}
