<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * User: mkilani
 * Date: 24/03/2026
 */

class Programs extends MY_Controller
{

    var $MODEL_NAME = 'programs_model';
    var $PAGE_URL = 'budg_pb/programs/get_page';

    function __construct()
    {
        parent::__construct();
        $this->load->model($this->MODEL_NAME);
        $this->load->model('Activities_model');
        $this->load->model('Projects_model');
        $this->load->model('Units_model');

        $this->program_id = $this->input->post('program_id');
        $this->prog_name = $this->input->post('prog_name');
        $this->prog_description = $this->input->post('prog_description');

    }

    function index($page= 1){
        $data['title']='برامج الموازنة';
        $data['content']='programs_index';
        $data['page']=$page;
        $data['action'] = 'edit';
        add_css('combotree.css');
        //$this->_look_ups($data);

        $this->load->view('template/template',$data);
    }

    function get_page($page= 1)
    {
        //$this->load->library('pagination');
        //$config['base_url'] = base_url($this->PAGE_URL);

        $data['page_rows'] = $this->{$this->MODEL_NAME}->get_all();
        $activities_program = [];
        $projects_program = [];
        $activities_units = [];
        $projects_units = [];

        foreach ($data['page_rows'] as $r){
            $activities_program[  $r['PROGRAM_ID']  ] = $this->Activities_model->get_list( $r['PROGRAM_ID'] );
            $projects_program  [  $r['PROGRAM_ID']  ] = $this->Projects_model->get_list( $r['PROGRAM_ID'] );
        }

        foreach ($activities_program as $program) {
            foreach ($program as $activity) {
                $activities_units[  $activity['ACTIVITY_ID']  ] = $this->Units_model->activity_unit_get_list( $activity['ACTIVITY_ID'] );
            }
        }

        foreach ($projects_program as $program) {
            foreach ($program as $project) {
                $projects_units[  $project['PROJECT_ID']  ] = $this->Units_model->project_unit_get_list( $project['PROJECT_ID'] );
            }
        }


        $data['activities_program'] = $activities_program;
        $data['projects_program'] = $projects_program;
        $data['activities_units'] = $activities_units;
        $data['projects_units'] = $projects_units;
        //$data['offset']=$offset+1;
        $data['page']=$page;

        $this->load->view('programs_page',$data);
    }

    function get($id=0){
        $result= $this->{$this->MODEL_NAME}->get($id);
        if(!(count($result)==1))
            die('get');
        $data['master_tb_data']=$result;
        $data['can_edit'] = 1;
        $data['action'] = 'edit';
        $data['content']='programs_show';
        $data['title']='بيانات البرنامج ';
        //$this->_look_ups($data);
        $this->load->view('template/template',$data);
    }


    function create(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            //$this->_post_validation();
            $this->ser= $this->{$this->MODEL_NAME}->create($this->_postedData('create'));
            if(intval($this->ser) <= 0){
                $this->print_error(' لم يتم الحفظ الجدول الاساسي '.'<br>'.$this->ser);
            }else{
                echo intval($this->ser);
            }
        }else{

            $data['content']='programs_show';
            $data['title']='اضافة برنامج ';
            $data['isCreate']= true;
            $data['action'] = 'index';
            //$this->_look_ups($data);
            $this->load->view('template/template',$data);
        }
    }

    function create_unit(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST'){
            $parent_type= $this->input->post('parent_type');
            $parent_id= $this->input->post('parent_id');
            $unit= $this->input->post('unit');

            $post_data = array(
                array('name'=>'ACTIVITY_PROJECT_ID','value'=>$parent_id ,'type'=>'','length'=>-1),
                array('name'=>'UNIT','value'=>$unit ,'type'=>'','length'=>-1),
            );

            if($parent_type=='ACTIVITY'){
                $res = $this->Units_model->create_activity_unit($post_data);
            }elseif ($parent_type=='PROJECT'){
                $res = $this->Units_model->create_project_unit($post_data);
            }else{
                $res=0;
            }

            if(intval($res) <= 0){
                if (strpos($res, 'ORA-00001') !== false) {
                    $res='لا يمكن ادخال هذه الوحدة لانها مضافة مسبقا';
                }
                $this->print_error('لم يتم الحفظ'.'<br>'.$res);
            }else{
                echo 1;
            }
        }
    }

    function delete_unit(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST'){
            $parent_type= $this->input->post('parent_type');
            $unit_ser= $this->input->post('ser');

            if($parent_type=='ACTIVITY'){
                $res = $this->Units_model->delete_activity_unit($unit_ser);
            }elseif ($parent_type=='PROJECT'){
                $res = $this->Units_model->delete_project_unit($unit_ser);
            }else{
                $res=0;
            }

            if(intval($res) <= 0){
                $this->print_error('لم يتم الحذف'.'<br>'.$res);
            }else{
                echo 1;
            }
        }
    }


    function edit(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST'){
            //$this->_post_validation(true);
            $res = $this->{$this->MODEL_NAME}->edit($this->_postedData());
            if(intval($res) <= 0){
                $this->print_error('لم يتم الحفظ'.'<br>'.$res);
            }else{
                echo 1;
            }
        }
    }

    function _postedData($typ= null){
        $result = array(
            array('name'=>'PROGRAM_ID','value'=>$this->program_id ,'type'=>'','length'=>-1),
            array('name'=>'PROG_NAME','value'=>$this->prog_name ,'type'=>'','length'=>-1),
            array('name'=>'PROG_DESCRIPTION','value'=>$this->prog_description ,'type'=>'','length'=>-1),
        );
        if($typ=='create')
            unset($result[0]);
        return $result;
    }

}