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
    $data['page_rows'] = $this->{$this->MODEL_NAME}->get_all();
    $activities_program = [];
    $projects_program = [];
    $projects_program_ = [];
    $activities_units = [];
    $projects_units = [];

    foreach ($data['page_rows'] as $r){
        $activities_program[  $r['PROGRAM_ID']  ] = $this->Activities_model->get_list( $r['PROGRAM_ID'] );
        $projects_program  [  $r['PROGRAM_ID']  ] = $this->Projects_model->get_list( $r['PROGRAM_ID'], null );
    }

    foreach ($activities_program as $program) {
        foreach ($program as $activity) {
            $activities_units[  $activity['ACTIVITY_ID']  ] = $this->Units_model->activity_unit_get_list( $activity['ACTIVITY_ID'] );
        }
    }

    foreach ($projects_program as $program) {
        foreach ($program as $project) {
            $projects_units[  $project['PROJECT_ID']  ] = $this->Units_model->project_unit_get_list( $project['PROJECT_ID'] );
            // مشاريع الخطة المرتبطة بهذا المشروع (نفس الوحدة التنظيمية)
            $projects_program_[ $project['PROJECT_ID'] ] = $this->Projects_model->get_list( $project['PROGRAM_ID'], $project['PROJECT_ID'] );
        }
    }

    $groups_program = [];   // [رقم البرنامج][رقم السجل] = وحدات السجل
    $groups_projects = [];  // [رقم السجل] = مشاريع السجل
    foreach ($data['page_rows'] as $r) {
        foreach ($this->Units_model->group_units_get_list( $r['PROGRAM_ID'] ) as $g) {
            if (!isset($groups_program[ $r['PROGRAM_ID'] ][ $g['GROUP_ID'] ])) {
                $groups_program[ $r['PROGRAM_ID'] ][ $g['GROUP_ID'] ] = [];
                $groups_projects[ $g['GROUP_ID'] ] = $this->Units_model->group_projects_get_list( $g['GROUP_ID'] );
            }
            if (!empty($g['UNIT_ID'])) {
                $groups_program[ $r['PROGRAM_ID'] ][ $g['GROUP_ID'] ][] = $g;
            }
        }
    }

    $data['activities_program'] = $activities_program;
    $data['projects_program'] = $projects_program;
    $data['projects_program_'] = $projects_program_;
    $data['groups_program'] = $groups_program;
    $data['groups_projects'] = $groups_projects;
    $data['activities_units'] = $activities_units;
    $data['projects_units'] = $projects_units;
    $data['page'] = $page;

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

            // يمكن اختيار اكثر من وحدة (ارقام مفصولة بفاصلة)
            $units = array_values(array_filter(array_map('trim', explode(',', $unit)), 'strlen'));
            $unit = implode(',', $units);
            if(count($units) == 0){
                $this->print_error('الرجاء اختيار وحدة تنظيمية');
                return;
            }

            $post_data = array(
                array('name'=>'ACTIVITY_PROJECT_ID','value'=>$parent_id ,'type'=>'','length'=>-1),
                array('name'=>'UNIT','value'=>$unit ,'type'=>'','length'=>-1),
            );

            if($parent_type=='ACTIVITY'){
                foreach($units as $one_unit){
                    $post_data[1]['value'] = $one_unit;
                    $res = $this->Units_model->create_activity_unit($post_data);
                    if(intval($res) <= 0) break;
                }
            }elseif ($parent_type=='PROJECT'){
                foreach($units as $one_unit){
                    $post_data[1]['value'] = $one_unit;
                    $res = $this->Units_model->create_project_unit($post_data);
                    if(intval($res) <= 0) break;
                }
            }elseif ($parent_type=='PROGRAM_PROJECT'){
                $res = $this->Units_model->create_group(array(
                    array('name'=>'PROGRAM_ID','value'=>$parent_id ,'type'=>'','length'=>-1),
                    array('name'=>'UNIT','value'=>$unit ,'type'=>'','length'=>-1),
                ));
            }elseif ($parent_type=='GROUP'){
                $res = $this->Units_model->create_group_unit(array(
                    array('name'=>'GROUP_ID','value'=>$parent_id ,'type'=>'','length'=>-1),
                    array('name'=>'UNIT','value'=>$unit ,'type'=>'','length'=>-1),
                ));
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
            }elseif ($parent_type=='GROUP'){
                $res = $this->Units_model->delete_group_unit($unit_ser);
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


    function create_group_project(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST'){
            $group_id   = $this->input->post('group_id');
            $program_id = $this->input->post('program_id');

            // وحدات السجل: لا يُضاف مشروع إلا بوجود وحدة تنظيمية واحدة على الأقل
            $group_units = [];
            foreach ($this->Units_model->group_units_get_list($program_id) as $g) {
                if ($g['GROUP_ID'] == $group_id && !empty($g['UNIT_ID'])) {
                    $group_units[] = $g['UNIT_ID'];
                }
            }
            if (count($group_units) == 0) {
                $this->print_error('لا يمكن اضافة مشروع قبل اضافة وحدة تنظيمية واحدة على الاقل');
                return;
            }

            $ser = $this->Projects_model->create(array(
                array('name'=>'PROGRAM_ID','value'=>$program_id ,'type'=>'','length'=>-1),
                array('name'=>'PROJECT_NAME','value'=>$this->input->post('project_name') ,'type'=>'','length'=>-1),
                array('name'=>'PROJECT_DESCRIPTION','value'=>$this->input->post('project_description') ,'type'=>'','length'=>-1),
            ));

            if(intval($ser) <= 0){
                $this->print_error('لم يتم الحفظ'.'<br>'.$ser);
                return;
            }

            // نسب المشروع الجديد لكل وحدات السجل
            $failed = '';
            foreach ($group_units as $unit_id) {
                $res = $this->Units_model->create_project_unit(array(
                    array('name'=>'ACTIVITY_PROJECT_ID','value'=>intval($ser) ,'type'=>'','length'=>-1),
                    array('name'=>'UNIT','value'=>$unit_id ,'type'=>'','length'=>-1),
                ));
                if(intval($res) <= 0){
                    $failed .= '<br>'.$res;
                }
            }

            if($failed != ''){
                $this->print_error('تم حفظ المشروع ولم يتم نسبه لكل الوحدات'.$failed);
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