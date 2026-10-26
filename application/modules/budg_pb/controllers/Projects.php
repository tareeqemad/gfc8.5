<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * User: mkilani
 * Date: 12/04/2026
 */

class Projects extends MY_Controller
{

    var $MODEL_NAME = 'Projects_model';
    var $PAGE_URL = 'budg_pb/Projects/get_page';

    function __construct()
    {
        parent::__construct();
        $this->load->model($this->MODEL_NAME);

        $this->project_id = $this->input->post('project_id');
        $this->program_id = $this->input->post('program_id');
        $this->project_name = $this->input->post('project_name');
        $this->project_description = $this->input->post('project_description');

    }

    function get($id=0){
        $result= $this->{$this->MODEL_NAME}->get($id);
        if(!(count($result)==1))
            die('get');
        $data['master_tb_data']=$result;
        $data['can_edit'] = 1;
        $data['action'] = 'edit';
        $data['content']='projects_show';
        $data['title']='بيانات المشروع ';
        //$this->_look_ups($data);
        $this->load->view('template/template',$data);
    }

    function get_list($program_id=0){
        return  $this->{$this->MODEL_NAME}->get_list($program_id);
    }


    function create($program_id=0){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            //$this->_post_validation();
            $this->ser= $this->{$this->MODEL_NAME}->create($this->_postedData('create'));
            if(intval($this->ser) <= 0){
                $this->print_error(' لم يتم الحفظ الجدول الاساسي '.'<br>'.$this->ser);
            }else{
                echo intval($this->ser);
            }
        }else{

            $data['content']='projects_show';
            $data['title']='اضافة مشروع ';
            $data['isCreate']= true;
            $data['program_id']= $program_id;
            $data['action'] = 'index';
            //$this->_look_ups($data);
            $this->load->view('template/template',$data);
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
            array('name'=>'PROJECT_ID','value'=>$this->project_id ,'type'=>'','length'=>-1),
            array('name'=>'PROGRAM_ID','value'=>$this->program_id ,'type'=>'','length'=>-1),
            array('name'=>'PROJECT_NAME','value'=>$this->project_name ,'type'=>'','length'=>-1),
            array('name'=>'PROJECT_DESCRIPTION','value'=>$this->project_description ,'type'=>'','length'=>-1),
        );
        if($typ=='create')
            unset($result[0]);
        else
            unset($result[1]);
        return $result;
    }


}