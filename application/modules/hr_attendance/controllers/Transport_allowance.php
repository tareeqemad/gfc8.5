<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Transport_allowance extends MY_Controller{

    var $MODEL_NAME     = 'Transport_allowance_model';
    var $PAGE_URL       = 'hr_attendance/Transport_allowance/get_page';
    var $REGIONS_CON_NO = 553;

    function __construct(){

        parent::__construct();

        $this->load->model($this->MODEL_NAME);
        $this->load->model('settings/constant_details_model');

        $this->ser            = $this->input->post('ser');
        $this->from_region    = $this->input->post('from_region');
        $this->to_region      = $this->input->post('to_region');
        $this->transport_type = $this->input->post('transport_type');
        $this->daily_amount   = $this->input->post('daily_amount');
        $this->start_date     = $this->input->post('start_date');
        $this->end_date       = $this->input->post('end_date');
        $this->note           = $this->input->post('note');
        $this->on_date        = $this->input->post('on_date');
        $this->new_amount     = $this->input->post('new_amount');
        $this->effective_date = $this->input->post('effective_date');
    }

    function index(){

        $data['title']   = 'بدل التنقل حسب المناطق';
        $data['content'] = 'transport_allowance_index';

        $this->_look_ups($data);
        $this->load->view('template/template1', $data);
    }

    function get_page($page = 1){

        $this->load->library('pagination');

        $where_sql = $this->_build_where_sql();

        $count_rs = $this->get_table_count(' GFC.TRANSPORT_ALLOWANCE_TB M ' . $where_sql);

        $config['base_url']         = base_url($this->PAGE_URL);
        $config['use_page_numbers'] = TRUE;
        $config['total_rows']       = (is_array($count_rs) && count($count_rs) > 0) ? $count_rs[0]['NUM_ROWS'] : 0;
        $config['per_page']         = $this->page_size;
        $config['num_links']        = 20;
        $config['cur_page']         = $page;

        $config['full_tag_open']  = '<div class="pagination-container"><ul class="pagination">';
        $config['full_tag_close'] = '</ul></div>';

        $config['first_tag_open'] =
        $config['last_tag_open'] =
        $config['next_tag_open'] =
        $config['prev_tag_open'] =
        $config['num_tag_open'] = '<li>';

        $config['first_tag_close'] =
        $config['last_tag_close'] =
        $config['next_tag_close'] =
        $config['prev_tag_close'] =
        $config['num_tag_close'] = '</li>';

        $config['cur_tag_open']  = '<li class="active"><span><b>';
        $config['cur_tag_close'] = '</b></span></li>';

        $this->pagination->initialize($config);

        $offset = ($page - 1) * $config['per_page'];
        $row    = $page * $config['per_page'];

        $data['page_rows'] = $this->_prepare_rows($this->{$this->MODEL_NAME}->get_list($where_sql, $offset, $row));
        $data['offset']    = $offset + 1;
        $data['page']      = $page;

        $this->load->view('transport_allowance_page', $data);
    }

    function create(){

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $this->_validate_post();

            $res = $this->{$this->MODEL_NAME}->create($this->_postedData());

            if (is_numeric($res) && intval($res) > 0) {
                echo intval($res);
            } else {
                $this->print_error($res);
            }

        } else {

            $data['title']    = 'إضافة بدل تنقل';
            $data['content']  = 'transport_allowance_show';
            $data['isCreate'] = true;

            $this->_look_ups($data);
            $this->load->view('template/template1', $data);
        }
    }

    function get($id){

        $result = $this->{$this->MODEL_NAME}->get($id);

        if (!(is_array($result) && count($result) == 1)) {
            die('get');
        }

        $data['master_tb_data'] = $result;
        $data['title']          = 'تعديل بدل تنقل';
        $data['content']        = 'transport_allowance_show';
        $data['isCreate']       = false;

        $this->_look_ups($data);
        $this->load->view('template/template1', $data);
    }

    function edit(){

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $this->_validate_post(true);

            $res = $this->{$this->MODEL_NAME}->edit($this->_postedData(true));

            if (is_numeric($res) && intval($res) >= 1) {
                echo 1;
            } else {
                $this->print_error($res == '0' ? 'السجل غير موجود' : $res);
            }
        }
    }

    function public_change_amount(){

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            if (!HaveAccess(base_url('hr_attendance/Transport_allowance/edit'))) {
                $this->print_error('لا تملك صلاحية التعديل');
            }

            if (!ctype_digit((string)$this->ser)) {
                $this->print_error('رقم السجل غير صحيح');
            }

            if (!is_numeric($this->new_amount) || floatval($this->new_amount) <= 0) {
                $this->print_error('يجب إدخال بدل يومي صحيح');
            }

            if (!$this->_is_valid_date($this->effective_date)) {
                $this->print_error('يجب إدخال تاريخ سريان صحيح');
            }

            $res = $this->{$this->MODEL_NAME}->change_amount(array(
                array('name'=>'SER','value'=>$this->ser,'type'=>'','length'=>-1),
                array('name'=>'NEW_AMOUNT','value'=>$this->new_amount,'type'=>'','length'=>-1),
                array('name'=>'EFFECTIVE_DATE','value'=>$this->effective_date,'type'=>'','length'=>20)
            ));

            if (is_numeric($res) && intval($res) > 0) {
                echo 1;
            } else {
                $this->print_error($res);
            }
        }
    }

    function delete(){

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && ctype_digit((string)$this->ser)) {

            $res = $this->{$this->MODEL_NAME}->delete($this->ser);

            if (is_numeric($res) && intval($res) >= 1) {
                echo 1;
            } else {
                $this->print_error($res == '0' ? 'السجل غير موجود' : $res);
            }
        }
    }

    function public_export_excel(){

        if (!HaveAccess(base_url('hr_attendance/Transport_allowance'))) {
            die('Error: No Permission');
        }

        $this->transport_type = $this->input->get('transport_type');
        $this->from_region    = $this->input->get('from_region');
        $this->to_region      = $this->input->get('to_region');
        $this->on_date        = $this->input->get('on_date');

        $rows = $this->_prepare_rows($this->{$this->MODEL_NAME}->get_list($this->_build_where_sql(), 0, 99999));

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename=transport_allowance_' . date('YmdHis') . '.xls');

        echo "\xEF\xBB\xBF";

        echo '<table border="1">';
        echo '<tr><th colspan="9">بدل التنقل حسب المناطق</th></tr>';
        echo '<tr>
            <th>#</th>
            <th>من منطقة</th>
            <th>إلى منطقة</th>
            <th>النوع</th>
            <th>البدل اليومي (شيكل)</th>
            <th>تاريخ البدء</th>
            <th>تاريخ الانتهاء</th>
            <th>الحالة</th>
            <th>ملاحظات</th>
        </tr>';

        $i = 1;

        foreach ($rows as $row) {

            echo '<tr>
                <td>' . $i++ . '</td>
                <td>' . htmlspecialchars($row['FROM_REGION_NAME']) . '</td>
                <td>' . htmlspecialchars($row['TO_REGION_NAME']) . '</td>
                <td>' . $row['TYPE_NAME'] . '</td>
                <td>' . $row['DAILY_AMOUNT'] . '</td>
                <td>' . $row['START_DATE_STR'] . '</td>
                <td>' . ($row['END_DATE_STR'] != '' ? $row['END_DATE_STR'] : 'مفتوح') . '</td>
                <td>' . $row['STATE_NAME'] . '</td>
                <td>' . htmlspecialchars($row['NOTE'] ?? '') . '</td>
            </tr>';
        }

        echo '<tr><td colspan="9">عدد السجلات : ' . count($rows) . '</td></tr>';
        echo '</table>';

        exit;
    }

    function _look_ups(&$data){

        $data['regions'] = $this->constant_details_model->get_all($this->REGIONS_CON_NO);
    }

    function _prepare_rows($rows){

        $today = date('Y-m-d');
        $rows  = is_array($rows) ? $rows : array();

        foreach ($rows as &$row) {

            $row['IS_INTERNAL'] = ($row['FROM_REGION'] == $row['TO_REGION']);
            $row['TYPE_NAME']   = $row['IS_INTERNAL'] ? 'داخلي' : 'خارجي';

            if ($row['START_DATE_STR'] > $today) {
                $row['STATE'] = 'upcoming';
                $row['STATE_NAME'] = 'قادم';
            } elseif ($row['END_DATE_STR'] != '' && $row['END_DATE_STR'] < $today) {
                $row['STATE'] = 'expired';
                $row['STATE_NAME'] = 'منتهي';
            } else {
                $row['STATE'] = 'active';
                $row['STATE_NAME'] = 'ساري';
            }
        }
        unset($row);

        return $rows;
    }

    function _build_where_sql(){

        $where_sql = ' WHERE 1 = 1 ';

        if ($this->transport_type === '1') {
            $where_sql .= ' AND M.FROM_REGION = M.TO_REGION ';
        } elseif ($this->transport_type === '2') {
            $where_sql .= ' AND M.FROM_REGION <> M.TO_REGION ';
        }

        $from = ctype_digit((string)$this->from_region) ? intval($this->from_region) : null;
        $to   = ctype_digit((string)$this->to_region) ? intval($this->to_region) : null;

        if ($from !== null && $to !== null) {
            $where_sql .= " AND ((M.FROM_REGION = {$from} AND M.TO_REGION = {$to}) OR (M.FROM_REGION = {$to} AND M.TO_REGION = {$from})) ";
        } elseif ($from !== null || $to !== null) {
            $region = ($from !== null) ? $from : $to;
            $where_sql .= " AND (M.FROM_REGION = {$region} OR M.TO_REGION = {$region}) ";
        }

        if ($this->_is_valid_date($this->on_date)) {
            $where_sql .= " AND M.START_DATE <= TO_DATE('{$this->on_date}','YYYY-MM-DD')
                            AND NVL(M.END_DATE, DATE '9999-12-31') >= TO_DATE('{$this->on_date}','YYYY-MM-DD') ";
        }

        return $where_sql;
    }

    function _validate_post($is_edit = false){

        if ($is_edit && !ctype_digit((string)$this->ser)) {
            $this->print_error('رقم السجل غير صحيح');
        }

        if (!ctype_digit((string)$this->from_region) || !ctype_digit((string)$this->to_region)) {
            $this->print_error('يجب اختيار منطقة السكن ومنطقة العمل');
        }

        if (!is_numeric($this->daily_amount) || floatval($this->daily_amount) <= 0) {
            $this->print_error('يجب إدخال بدل يومي صحيح');
        }

        if (!$this->_is_valid_date($this->start_date)) {
            $this->print_error('يجب إدخال تاريخ البدء');
        }

        if ($this->end_date != '') {

            if (!$this->_is_valid_date($this->end_date)) {
                $this->print_error('تاريخ الانتهاء غير صحيح');
            }

            if ($this->end_date < $this->start_date) {
                $this->print_error('تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء');
            }
        }
    }

    function _is_valid_date($date){

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date)) {
            return false;
        }

        list($y, $m, $d) = explode('-', $date);

        return checkdate((int)$m, (int)$d, (int)$y);
    }

    function _postedData($is_edit = false){

        $result = array(
            array('name'=>'FROM_REGION','value'=>$this->from_region,'type'=>'','length'=>-1),
            array('name'=>'TO_REGION','value'=>$this->to_region,'type'=>'','length'=>-1),
            array('name'=>'DAILY_AMOUNT','value'=>$this->daily_amount,'type'=>'','length'=>-1),
            array('name'=>'START_DATE','value'=>$this->start_date,'type'=>'','length'=>20),
            array('name'=>'END_DATE','value'=>$this->end_date,'type'=>'','length'=>20),
            array('name'=>'NOTE','value'=>$this->note,'type'=>'','length'=>500)
        );

        if ($is_edit) {
            array_unshift($result, array('name'=>'SER','value'=>$this->ser,'type'=>'','length'=>-1));
        }

        return $result;
    }
}
