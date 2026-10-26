<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: fmamluk
 * Date: 03/06/26
 * Time: 11:40 ص
 */


class Attendance_departure extends MY_Controller{

    var $MODEL_NAME= 'Attendance_departure_model';
    var $PAGE_URL= 'hr_attendance/Attendance_departure/get_page';
    var $PAGE_ACT;

    function  __construct(){

        parent::__construct();
        $this->load->model('Attendance_departure_model');
        $this->load->model('root/rmodel');

        $this->from_date = $this->input->post('from_date');
        $this->to_date   = $this->input->post('to_date');
        $this->emp_no = $this->input->post('emp_no');
        $this->branch_id = $this->input->post('branch_id');
        $this->sub_branch_id = $this->input->post('sub_branch_id');
        $this->status = $this->input->post('status');
        $this->work_start = $this->input->post('work_start');
        $this->work_end = $this->input->post('work_end');
        $this->grace_minutes = $this->input->post('grace_minutes');

        $this->page_act = $this->input->post('page_act');
        $this->PAGE_ACT = $this->uri->segment(5);

        if (
        isset($this->PAGE_ACT)
        and $this->PAGE_ACT != ''
            and ($this->uri->segment(3) == 'index' or $this->uri->segment(3) == 'get_page')
        ) {

        if (!HaveAccess(base_url("hr_attendance/Attendance_departure/index/1/".$this->PAGE_ACT))) {
            die('Error: No Permission '.$this->PAGE_ACT);
        }

        if ($this->PAGE_ACT == 'my') {

        } elseif ($this->PAGE_ACT == 'move_admin') {

        } elseif ($this->PAGE_ACT == 'hr_admin') {

        } else {
            die('PAGE_ACT');
        }

        } elseif ($this->uri->segment(3) == 'index') {
            die('index');
        }
    }

    function index($page = 1, $page_act = -1){

        $data['page_act'] = $page_act;
        $data['content']='attendance_departure_index';
        $data['title']='شاشة استيراد البصمات';
        $data['page'] = $page;
        $data['page_act'] = $page_act;
        $data['isCreate']= true;
        $data['action'] = 'index';
        $this->_look_ups($data);
        $this->load->view('template/template1',$data);
    }

    function get_page($page = 1, $page_act = -1){
            
        $this->load->library('pagination');

        $page_act = ($this->page_act) ? $this->page_act : $page_act;
        $page_act = ($page_act == -1) ? '' : $page_act;

        if (!HaveAccess(base_url("hr_attendance/Attendance_departure/index/1/" . $page_act))) {
            die('Error: No Permission ' . $page_act);
        }

        $where_sql = $this->_build_attendance_where_sql(
            $page_act,
            $this->from_date,
            $this->to_date,
            $this->emp_no,
            $this->branch_id,
            $this->sub_branch_id,
            $this->status
        );

        $config['base_url'] = base_url("hr_attendance/Attendance_departure/get_page/");

        $config['use_page_numbers'] = TRUE;

        $config['total_rows'] = count(
            $this->{$this->MODEL_NAME}->get_all_for_excel($where_sql)
        );

        $config['per_page'] = $this->page_size;
        $config['num_links'] = 20;
        $config['cur_page'] = $page;

        $config['full_tag_open'] = '<div class="pagination-container"><ul class="pagination">';
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

        $config['cur_tag_open'] = '<li class="active"><span><b>';
        $config['cur_tag_close'] = '</b></span></li>';

        $this->pagination->initialize($config);

        $offset = (($page - 1) * $config['per_page']);
        $row = ($page * $config['per_page']);

        $data['page_rows'] = $this->{$this->MODEL_NAME}->get_list(
            $where_sql,
            $offset,
            $row
        );

        $data['offset'] = $offset + 1;
        $data['page'] = $page;

        $this->load->view('attendance_departure_page', $data);
    }

    /**
     * تقارير تحليل البصمات (ساعات الدوام / بدون حضور / بدون انصراف / التأخير الصباحي)
     * الحساب بالكامل PHP فوق بيانات ATTENDANCE_DEPATURE_TB لأن أوقات الدوام الرسمية تُدخل يدوياً بالشاشة
     * ولا ترتبط بجدول أو باكج بقاعدة البيانات، لتسهيل تغييرها بأي وقت
     * الشاشة دايماً تفصيلية (زي كشف الحركات بالضبط)، والملخص/التفصيل بيتقرر بس بالطباعة
     */
    public function attendance_analysis(){

        $mode = $this->input->post('mode');

        $page_act = ($this->page_act) ? $this->page_act : '';

        if (!HaveAccess(base_url("hr_attendance/Attendance_departure/index/1/" . $page_act))) {
            die('Error: No Permission ' . $page_act);
        }

        list($mode, $rows, $is_summary) = $this->_resolve_analysis_rows(
            $mode, $page_act, $this->from_date, $this->to_date, $this->emp_no,
            $this->branch_id, $this->sub_branch_id, $this->work_start, $this->work_end, $this->grace_minutes,
            true
        );

        $data['rows'] = $rows;
        $data['mode'] = $mode;
        $data['is_summary'] = $is_summary;

        $this->load->view('attendance_departure_analysis', $data);
    }

    /**
     * نسخة قابلة للطباعة لتقارير تحليل البصمات، بنفس تصميم print_report
     */
    public function print_analysis(){

        extract($this->_read_report_filters());

        list($mode, $rows, $is_summary) = $this->_resolve_analysis_rows(
            $mode, $page_act, $from_date, $to_date, $emp_no,
            $branch_id, $sub_branch_id, $work_start, $work_end, $grace_minutes
        );

        $names = $this->_resolve_attendance_filter_names($branch_id, $sub_branch_id, $emp_no, $page_act);

        $data['mode'] = $mode;
        $data['rows'] = $rows;
        $data['is_summary'] = $is_summary;
        $data['from_date'] = $from_date;
        $data['to_date'] = $to_date;
        $data['work_start'] = $work_start;
        $data['work_end'] = $work_end;
        $data['grace_minutes'] = $grace_minutes;
        $data['page_act'] = $page_act;
        $data['branch_id'] = $branch_id;
        $data['sub_branch_id'] = $sub_branch_id;
        $data['emp_no'] = $emp_no;
        $data['branch_name'] = $names['branch_name'];
        $data['sub_branch_name'] = $names['sub_branch_name'];
        $data['emp_name'] = $names['emp_name'];
        $data['print_date'] = date('Y-m-d H:i:s');

        $this->load->view('attendance_analysis_print', $data);
    }

    /**
     * تصدير اكسل لتقارير تحليل البصمات، بنفس اسلوب export_excel_print
     * force_detail=1 معناها الطلب جاي من زر التصدير بالشاشة (تفصيلي دايماً زي كشف الحركات)
     * لو مش موجود بيتقرر ملخص/تفصيلي حسب الموظف، تماماً متل رابط الاكسل جوا صفحة الطباعة
     */
    public function export_excel_analysis(){

        extract($this->_read_report_filters());

        list($mode, $rows, $is_summary) = $this->_resolve_analysis_rows(
            $mode, $page_act, $from_date, $to_date, $emp_no,
            $branch_id, $sub_branch_id, $work_start, $work_end, $grace_minutes,
            ($force_detail == '1')
        );

        $titles = [
            'hours'    => 'تقرير ساعات الدوام والتأخير',
            'no_entry' => 'الموظفين بدون بصمة حضور',
            'no_leave' => 'الموظفين بدون بصمة انصراف',
            'late'     => 'الموظفين المتأخرين صباحاً'
        ];

        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=attendance_analysis_".date('YmdHis').".xls");

        echo "\xEF\xBB\xBF";

        echo '<table border="1">';

        echo '<tr><th colspan="6">'.$titles[$mode].'</th></tr>';
        echo '<tr><td>الفترة</td><td colspan="5">'.$from_date.' إلى '.$to_date.'</td></tr>';
        echo '<tr><td colspan="6"></td></tr>';

        echo '<tr>
            <th>#</th>
            <th>المقر</th>
            <th>المقر الفرعي</th>
            <th>الرقم الوظيفي</th>
            <th>اسم الموظف</th>';

        if ($is_summary) {

            if ($mode == 'hours') {
                echo '<th>عدد أيام التأخير</th><th>إجمالي ساعات الدوام</th><th>إجمالي الوقت بعد الدوام الرسمي</th>';
            } elseif ($mode == 'no_entry') {
                echo '<th>عدد الأيام بدون بصمة حضور</th>';
            } elseif ($mode == 'no_leave') {
                echo '<th>عدد الأيام بدون بصمة انصراف</th>';
            } elseif ($mode == 'late') {
                echo '<th>عدد أيام التأخير</th><th>إجمالي دقائق التأخير</th>';
            }

        } else {

            echo '<th>التاريخ</th>';

            if ($mode == 'hours') {
                echo '<th>وقت الحضور</th><th>وقت الانصراف</th><th>ساعات الدوام</th><th>الوقت بعد نهاية الدوام الرسمي</th>';
            } elseif ($mode == 'no_entry') {
                echo '<th>وقت الانصراف المسجل</th>';
            } elseif ($mode == 'no_leave') {
                echo '<th>وقت الحضور المسجل</th>';
            } elseif ($mode == 'late') {
                echo '<th>وقت الحضور</th><th>مدة التأخير</th>';
            }
        }

        echo '</tr>';

        $i = 1;

        foreach ($rows as $row) {

            echo '<tr>
                <td>'.$i++.'</td>
                <td>'.$row['BRANCH_NAME'].'</td>
                <td>'.$row['SUB_BRANCH_NAME'].'</td>
                <td>'.$row['EMPLOYEE_ID'].'</td>
                <td>'.$row['EMPLOYEE_NAME'].'</td>';

            if ($is_summary) {

                if ($mode == 'hours') {
                    echo '
                    <td>'.$row['DAYS_COUNT'].'</td>
                    <td>'.$this->_minutes_to_hhmm($row['TOTAL_WORK_MINUTES']).'</td>
                    <td>'.$this->_minutes_to_hhmm($row['TOTAL_DELAY_MINUTES']).'</td>';
                } elseif ($mode == 'no_entry' || $mode == 'no_leave') {
                    echo '<td>'.$row['DAYS_COUNT'].'</td>';
                } elseif ($mode == 'late') {
                    echo '
                    <td>'.$row['DAYS_COUNT'].'</td>
                    <td>'.$this->_minutes_to_hhmm($row['TOTAL_DELAY_MINUTES']).'</td>';
                }

            } else {

                echo '<td>'.$row['ATTENDANCE_DATE'].'</td>';

                if ($mode == 'hours') {
                    echo '
                    <td>'.$this->_minutes_to_hhmm($row['FIRST_ENTRY']).'</td>
                    <td>'.$this->_minutes_to_hhmm($row['LAST_LEAVE']).'</td>
                    <td>'.$this->_minutes_to_hhmm($row['WORK_MINUTES']).'</td>
                    <td>'.$this->_minutes_to_hhmm($row['DELAY_MINUTES']).'</td>';
                } elseif ($mode == 'no_entry') {
                    echo '<td>'.$this->_minutes_to_hhmm($row['LAST_LEAVE']).'</td>';
                } elseif ($mode == 'no_leave') {
                    echo '<td>'.$this->_minutes_to_hhmm($row['FIRST_ENTRY']).'</td>';
                } elseif ($mode == 'late') {
                    echo '
                    <td>'.$this->_minutes_to_hhmm($row['FIRST_ENTRY']).'</td>
                    <td>'.$this->_minutes_to_hhmm($row['DELAY_MINUTES']).'</td>';
                }
            }

            echo '</tr>';
        }

        echo '<tr><td colspan="8">عدد السجلات : '.count($rows).'</td></tr>';

        echo '</table>';

        exit;
    }

    /**
     * يحسب صفوف تقرير تحليل البصمات المطلوب حسب mode، مشتركة بين attendance_analysis/print_analysis/export_excel_analysis
     * لو ما في موظف محدد بيرجع ملخص مجمّع لكل موظف بدل التفصيل اليومي
     */
    private function _resolve_analysis_rows($mode, $page_act, $from_date, $to_date, $emp_no, $branch_id, $sub_branch_id, $work_start, $work_end, $grace_minutes, $force_detail = false){

        $where_sql = $this->_build_attendance_where_sql(
            $page_act, $from_date, $to_date, $emp_no, $branch_id, $sub_branch_id, ''
        );

        $rows = $this->{$this->MODEL_NAME}->get_all_for_excel($where_sql);

        $groups = $this->_group_attendance_rows($rows);

        $work_start_min = $this->_time_to_minutes($work_start);
        $work_end_min = $this->_time_to_minutes($work_end);
        $grace = intval($grace_minutes);

        switch ($mode) {

            case 'no_entry':
                $mode = 'no_entry';
                $rows = $this->_filter_no_entry($groups);
                break;

            case 'no_leave':
                $mode = 'no_leave';
                $rows = $this->_filter_no_leave($groups);
                break;

            case 'late':
                $mode = 'late';
                $rows = $this->_filter_late($groups, $work_start_min, $grace);
                break;

            default:
                $mode = 'hours';
                $rows = $this->_filter_hours($groups, $work_end_min);
                break;
        }

        $is_summary = (!$force_detail && $emp_no == '');

        if ($is_summary) {
            $rows = $this->_summarize_by_employee($rows);
        }

        return [$mode, $rows, $is_summary];
    }

    /**
     * يجمّع صفوف التفصيل اليومي بصف واحد لكل موظف (عدد الأيام + إجمالي الدقائق) لعرض ملخص الفترة كاملة
     */
    private function _summarize_by_employee($rows){

        $summary = [];

        foreach ($rows as $row) {

            $emp = $row['EMPLOYEE_ID'];

            if (!isset($summary[$emp])) {

                $summary[$emp] = [
                    'EMPLOYEE_ID'          => $row['EMPLOYEE_ID'],
                    'EMPLOYEE_NAME'        => $row['EMPLOYEE_NAME'],
                    'BRANCH_NAME'          => $row['BRANCH_NAME'],
                    'SUB_BRANCH_NAME'      => $row['SUB_BRANCH_NAME'],
                    'DAYS_COUNT'           => 0,
                    'TOTAL_WORK_MINUTES'   => 0,
                    'TOTAL_DELAY_MINUTES'  => 0
                ];
            }

            $summary[$emp]['DAYS_COUNT']++;
            $summary[$emp]['TOTAL_WORK_MINUTES']  += isset($row['WORK_MINUTES']) ? $row['WORK_MINUTES'] : 0;
            $summary[$emp]['TOTAL_DELAY_MINUTES'] += isset($row['DELAY_MINUTES']) ? $row['DELAY_MINUTES'] : 0;
        }

        return array_values($summary);
    }

    /**
     * يحول عدد الدقائق الى نص ساعة:دقيقة لعرضه بتصدير الاكسل
     */
    private function _minutes_to_hhmm($minutes){

        if ($minutes === null) {
            return '-';
        }

        return sprintf('%d:%02d', floor($minutes / 60), $minutes % 60);
    }

    /**
     * يجمع صفوف البصمات حسب الموظف واليوم ليحدد أول بصمة حضور وآخر بصمة انصراف بكل يوم
     */
    private function _group_attendance_rows($rows){

        $groups = [];

        foreach ($rows as $row) {

            $key = $row['EMPLOYEE_ID'] . '_' . $row['ATTENDANCE_DATE'];

            if (!isset($groups[$key])) {

                $groups[$key] = [
                    'EMPLOYEE_ID'     => $row['EMPLOYEE_ID'],
                    'EMPLOYEE_NAME'   => $row['EMPLOYEE_NAME'],
                    'BRANCH_NAME'     => $row['BRANCH_NAME'],
                    'SUB_BRANCH_NAME' => $row['SUB_BRANCH_NAME'],
                    'ATTENDANCE_DATE' => $row['ATTENDANCE_DATE'],
                    'FIRST_ENTRY'     => null,
                    'LAST_LEAVE'      => null
                ];
            }

            $minutes = $this->_time_to_minutes($row['ATTENDANCE_TIME']);

            if ($this->_is_entry_status($row['FUNCTION_KEY'])) {

                if ($groups[$key]['FIRST_ENTRY'] === null || $minutes < $groups[$key]['FIRST_ENTRY']) {
                    $groups[$key]['FIRST_ENTRY'] = $minutes;
                }

            } elseif ($this->_is_leave_status($row['FUNCTION_KEY'])) {

                if ($groups[$key]['LAST_LEAVE'] === null || $minutes > $groups[$key]['LAST_LEAVE']) {
                    $groups[$key]['LAST_LEAVE'] = $minutes;
                }
            }
        }

        return $groups;
    }

    /**
     * حركات الحضور المعتمدة لاحتساب أول بصمة دخول باليوم
     */
    private function _is_entry_status($function_key){
        return in_array($function_key, ['Entry', 'F1']);
    }

    /**
     * حركات الانصراف المعتمدة لاحتساب آخر بصمة خروج باليوم
     */
    private function _is_leave_status($function_key){
        return in_array($function_key, ['Leave', 'left', 'F4']);
    }

    /**
     * يحول وقت نصي HH:MM الى عدد دقائق لتسهيل عمليات الطرح والمقارنة
     */
    private function _time_to_minutes($time){

        if ($time == '' || $time === null) {
            return null;
        }

        $parts = explode(':', $time);

        return (intval($parts[0]) * 60) + intval($parts[1]);
    }

    /**
     * الموظفين الذين لهم وقت زائد بعد نهاية الدوام الرسمي بس (اللي ما عندهم تأخير ما بيظهروا)
     * فترة السماح لا تُطرح هون لأنها مخصصة للتأخير الصباحي بس (_filter_late)
     */
    private function _filter_hours($groups, $work_end){

        $result = [];

        foreach ($groups as $group) {

            if ($group['FIRST_ENTRY'] === null || $group['LAST_LEAVE'] === null) {
                continue;
            }

            $group['WORK_MINUTES'] = max(0, $group['LAST_LEAVE'] - $group['FIRST_ENTRY']);

            $group['DELAY_MINUTES'] = ($work_end !== null)
                ? max(0, $group['LAST_LEAVE'] - $work_end)
                : 0;

            if ($group['DELAY_MINUTES'] <= 0) {
                continue;
            }

            $result[] = $group;
        }

        return $result;
    }

    /**
     * الموظفين الذين لديهم بصمة انصراف بدون بصمة حضور مقابلة بنفس اليوم
     */
    private function _filter_no_entry($groups){

        $result = [];

        foreach ($groups as $group) {

            if ($group['FIRST_ENTRY'] === null && $group['LAST_LEAVE'] !== null) {
                $result[] = $group;
            }
        }

        return $result;
    }

    /**
     * الموظفين الذين لديهم بصمة حضور بدون بصمة انصراف مقابلة بنفس اليوم
     */
    private function _filter_no_leave($groups){

        $result = [];

        foreach ($groups as $group) {

            if ($group['LAST_LEAVE'] === null && $group['FIRST_ENTRY'] !== null) {
                $result[] = $group;
            }
        }

        return $result;
    }

    /**
     * الموظفين المتأخرين عن بداية الدوام الرسمي بعد خصم فترة السماح
     */
    private function _filter_late($groups, $work_start, $grace){

        $result = [];

        if ($work_start === null) {
            return $result;
        }

        foreach ($groups as $group) {

            if ($group['FIRST_ENTRY'] === null) {
                continue;
            }

            $delay = $group['FIRST_ENTRY'] - $work_start - $grace;

            if ($delay > 0) {
                $group['DELAY_MINUTES'] = $delay;
                $result[] = $group;
            }
        }

        return $result;
    }

    public function export_excel(){

        extract($this->_read_report_filters());

        $where_sql = $this->_build_attendance_where_sql(
            $page_act, $from_date, $to_date, $emp_no, $branch_id, $sub_branch_id, $status, false
        );

        $rows = $this->{$this->MODEL_NAME}->get_all_for_excel($where_sql);

        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=attendance_".date('YmdHis').".xls");

        echo "\xEF\xBB\xBF";

        echo '<table border="1">';

        echo '
        <tr>
            <th>المقر</th>
            <th>المقر الفرعي</th>
            <th>الرقم الوظيفي</th>
            <th>اسم الموظف</th>
            <th>التاريخ</th>
            <th>الوقت</th>
            <th>الحالة</th>
            <th>تاريخ الترحيل</th>
        </tr>';

        foreach($rows as $row){

            $row_status = $this->_attendance_status_label($row['FUNCTION_KEY']);

            echo '
            <tr>
                <td>'.$row['BRANCH_NAME'].'</td>
                <td>'.$row['SUB_BRANCH_NAME'].'</td>
                <td>'.$row['EMPLOYEE_ID'].'</td>
                <td>'.$row['EMPLOYEE_NAME'].'</td>
                <td>'.$row['ATTENDANCE_DATE'].'</td>
                <td>'.$row['ATTENDANCE_TIME'].'</td>
                <td>'.$row_status.'</td>
                <td>'.$row['CREATED_AT'].'</td>

            </tr>';
        }

        echo '</table>';

        exit;
    }

    public function import_google_sheet(){

        $from_date = $this->input->post('from_date');
        $to_date   = $this->input->post('to_date');

        $url = "https://script.google.com/macros/s/AKfycbxPMumAM7M4eBLvseK8C1-ZfaJ3krLADgqXlDbrUmGKEUBY7lpyX_G3WX6l6paEe8lq/exec";

        $json = file_get_contents($url);

        $rows = json_decode($json, true);

        $count = 0;

        foreach ($rows as $row)
        {
            $attendance_date = date(
                'Y-m-d',
                strtotime($row['date'])
            );

            if(!empty($from_date) && $attendance_date < $from_date){
                continue;
            }

            if(!empty($to_date) && $attendance_date > $to_date){
                continue;
            }

            $this->Attendance_departure_model->create($row);

            $count++;
        }

        echo json_encode([
            'success' => true,
            'count'   => $count
        ]);

        exit;
    }

    function _look_ups(&$data){

        $this->load->model('settings/constant_details_model');
        $this->load->model('settings/gcc_branches_model');
        $this->load->model('hr_attendance_model');

        $data['sub_branches'] = $this->constant_details_model->get_list(550);
        $data['branches'] = $this->gcc_branches_model->get_all();
        //$data['emp_no_cons'] = $this->hr_attendance_model->get_child( $this->user->emp_no ,'hr_admin' );
        $data['emp_no_cons'] = $this->hr_attendance_model->get_child($this->user->emp_no,$data['page_act']);
        add_css('datepicker3.css');
        add_js('moment.js');
        add_js('bootstrap-datetimepicker.js');
    }

    /**
     * يقرأ فلاتر التقرير من GET ويفحص الصلاحية، مشتركة بين export_excel/print_report/export_excel_print
     */
    private function _read_report_filters(){

        $filters = [
            'from_date'     => $this->input->get('from_date'),
            'to_date'       => $this->input->get('to_date'),
            'emp_no'        => $this->input->get('emp_no'),
            'branch_id'     => $this->input->get('branch_id'),
            'sub_branch_id' => $this->input->get('sub_branch_id'),
            'status'        => $this->input->get('status'),
            'page_act'      => $this->input->get('page_act'),
            'mode'          => $this->input->get('mode'),
            'work_start'    => $this->input->get('work_start'),
            'work_end'      => $this->input->get('work_end'),
            'grace_minutes' => $this->input->get('grace_minutes'),
            'force_detail'  => $this->input->get('force_detail')
        ];

        if (!HaveAccess(base_url("hr_attendance/Attendance_departure/index/1/" . $filters['page_act']))) {
            die('Error: No Permission ' . $filters['page_act']);
        }

        return $filters;
    }

    /**
     * يبني شرط WHERE المشترك بين get_page/export_excel/print_report/export_excel_print
     * $quote_ids=false يحافظ على سلوك export_excel القديم (كان بدون quotes حول القيم الرقمية)
     */
    private function _build_attendance_where_sql($page_act, $from_date, $to_date, $emp_no, $branch_id, $sub_branch_id, $status, $quote_ids = true){

        $where_sql = '';

        if ($page_act == 'my') {

            $where_sql .= " AND EMPLOYEE_ID = {$this->user->emp_no} ";

        } elseif ($page_act == 'move_admin') {

            $where_sql .= " AND DECODE(BRANCH,8,2,BRANCH) = {$this->user->branch} ";

        } elseif ($page_act == 'hr_admin') {

            $where_sql .= " AND 1 = 1 ";

        } else {

            $where_sql .= " AND 1 = 2 ";
        }

        if ($from_date != '') {
            $where_sql .= " AND ATTENDANCE_DATE >= TO_DATE('{$from_date}','YYYY-MM-DD') ";
        }

        if ($to_date != '') {
            $where_sql .= " AND ATTENDANCE_DATE <= TO_DATE('{$to_date}','YYYY-MM-DD') ";
        }

        if ($emp_no != '') {
            $where_sql .= $quote_ids
                ? " AND EMPLOYEE_ID = '{$emp_no}' "
                : " AND EMPLOYEE_ID = {$emp_no} ";
        }

        if ($branch_id != '') {
            $where_sql .= $quote_ids
                ? " AND EMP_PKG.GET_EMP_BRANCH(EMPLOYEE_ID) = '{$branch_id}' "
                : " AND EMP_PKG.GET_EMP_BRANCH(EMPLOYEE_ID) = {$branch_id} ";
        }

        if ($sub_branch_id != '') {
            $where_sql .= $quote_ids
                ? " AND SUB_BRANCH = '{$sub_branch_id}' "
                : " AND SUB_BRANCH = {$sub_branch_id} ";
        }

        if ($status != '') {
            $where_sql .= " AND FUNCTION_KEY = '{$status}' ";
        }

        return $where_sql;
    }

    /**
     * يحل أسماء المقر/المقر الفرعي/الموظف من الأرقام، مشترك بين print_report و export_excel_print
     */
    private function _resolve_attendance_filter_names($branch_id, $sub_branch_id, $emp_no, $page_act){

        $this->load->model('settings/gcc_branches_model');
        $this->load->model('settings/constant_details_model');
        $this->load->model('hr_attendance_model');

        $names = [
            'branch_name'     => '',
            'sub_branch_name' => '',
            'emp_name'        => ''
        ];

        if ($branch_id != '') {
            foreach ($this->gcc_branches_model->get_all() as $b) {
                if ($b['NO'] == $branch_id) {
                    $names['branch_name'] = $b['NAME'];
                    break;
                }
            }
        }

        if ($sub_branch_id != '') {
            foreach ($this->constant_details_model->get_list(550) as $s) {
                if ($s['CON_NO'] == $sub_branch_id) {
                    $names['sub_branch_name'] = $s['CON_NAME'];
                    break;
                }
            }
        }

        if ($emp_no != '') {
            foreach ($this->hr_attendance_model->get_child($this->user->emp_no, $page_act) as $e) {
                if ($e['EMP_NO'] == $emp_no) {
                    $names['emp_name'] = $e['EMP_NAME'];
                    break;
                }
            }
        }

        return $names;
    }

    /**
     * ترجمة FUNCTION_KEY لنص عربي، مشتركة بين export_excel و export_excel_print
     * لضمان نفس النص في كل التصديرات
     */
    private function _attendance_status_label($function_key){

        switch ($function_key) {

            case 'Entry':
            case 'F1':
                return 'دخول';

            case 'Leave':
            case 'left':
            case 'F4':
                return 'خروج';

            case 'Leave with form':
            case 'F2':
                return 'خروج بإذن';

            case 'Entry with form':
            case 'F3':
                return 'عودة إذن';

            default:
                return 'غير محدد';
        }
    }


    function print_report(){

        extract($this->_read_report_filters());

        $where_sql = $this->_build_attendance_where_sql(
            $page_act, $from_date, $to_date, $emp_no, $branch_id, $sub_branch_id, $status
        );

        $data['rows'] = $this->{$this->MODEL_NAME}->get_all_for_excel($where_sql);

        $data['from_date'] = $from_date;
        $data['to_date'] = $to_date;
        $data['page_act'] = $page_act;
        $data['emp_no'] = $emp_no;
        $data['branch_id'] = $branch_id;
        $data['sub_branch_id'] = $sub_branch_id;
        $data['status'] = $status;
        $data['print_date'] = date('Y-m-d H:i:s');

        // أسماء الفلاتر لعرضها في التقرير
        $names = $this->_resolve_attendance_filter_names($branch_id, $sub_branch_id, $emp_no, $page_act);

        $data['branch_name'] = $names['branch_name'];
        $data['sub_branch_name'] = $names['sub_branch_name'];
        $data['emp_name'] = $names['emp_name'];

        $this->load->view('attendance_departure_print', $data);
    }

    public function export_excel_print(){

        extract($this->_read_report_filters());

        $where_sql = $this->_build_attendance_where_sql(
            $page_act, $from_date, $to_date, $emp_no, $branch_id, $sub_branch_id, $status
        );

        $rows = $this->{$this->MODEL_NAME}->get_all_for_excel($where_sql);

        $names = $this->_resolve_attendance_filter_names($branch_id, $sub_branch_id, $emp_no, $page_act);

        $branch_name = $names['branch_name'];
        $sub_branch_name = $names['sub_branch_name'];
        $emp_name = $names['emp_name'];

        $is_details = !empty($emp_no);
        $col_count = $is_details ? 4 : 5;

        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=attendance_report_".date('YmdHis').".xls");

        echo "\xEF\xBB\xBF";

        echo '<table border="1">';

        echo '<tr><th colspan="'.$col_count.'">تقرير الحضور والانصراف</th></tr>';
        echo '<tr><td>الفترة</td><td colspan="'.($col_count-1).'">'.$from_date.' إلى '.$to_date.'</td></tr>';
        echo '<tr><td>تاريخ الطباعة</td><td colspan="'.($col_count-1).'">'.date('Y-m-d H:i:s').'</td></tr>';
        echo '<tr><td>المقر</td><td colspan="'.($col_count-1).'">'.($branch_name!=''?$branch_name:'الكل').'</td></tr>';
        echo '<tr><td>المقر الفرعي</td><td colspan="'.($col_count-1).'">'.($sub_branch_name!=''?$sub_branch_name:'الكل').'</td></tr>';
        echo '<tr><td>الموظف</td><td colspan="'.($col_count-1).'">'.($emp_name!=''?$emp_name:'جميع الموظفين').'</td></tr>';
        echo '<tr><td>نوع التقرير</td><td colspan="'.($col_count-1).'">'.($is_details?'تفصيلي':'ملخص').'</td></tr>';
        echo '<tr><td colspan="'.$col_count.'"></td></tr>';

        if (!$is_details) {

            $summary = [];

            foreach ($rows as $r) {

                $emp = $r['EMPLOYEE_ID'];

                if (!isset($summary[$emp])) {

                    $summary[$emp] = [
                        'EMPLOYEE_ID'   => $r['EMPLOYEE_ID'],
                        'EMPLOYEE_NAME' => $r['EMPLOYEE_NAME'],
                        'BRANCH_NAME'   => $r['BRANCH_NAME'],
                        'DAYS'          => []
                    ];
                }

                $summary[$emp]['DAYS'][$r['ATTENDANCE_DATE']] = 1;
            }

            $total_emp = count($summary);
            $total_days = 0;

            foreach ($summary as $s) {
                $total_days += count($s['DAYS']);
            }

            echo '<tr><th>عدد الموظفين</th><th>إجمالي أيام الدوام</th><th>متوسط الدوام</th></tr>';
            echo '<tr><td>'.$total_emp.'</td><td>'.$total_days.'</td><td>'.($total_emp?round($total_days/$total_emp,1):0).'</td></tr>';
            echo '<tr><td colspan="'.$col_count.'"></td></tr>';

            echo '<tr>
                <th>#</th>
                <th>الرقم الوظيفي</th>
                <th>اسم الموظف</th>
                <th>المقر</th>
                <th>عدد أيام الدوام</th>
            </tr>';

            $i = 1;

            foreach ($summary as $row) {

                echo '<tr>
                    <td>'.$i++.'</td>
                    <td>'.$row['EMPLOYEE_ID'].'</td>
                    <td>'.$row['EMPLOYEE_NAME'].'</td>
                    <td>'.$row['BRANCH_NAME'].'</td>
                    <td>'.count($row['DAYS']).'</td>
                </tr>';
            }

            echo '<tr>
                <td colspan="4">عدد الموظفين : '.$total_emp.'</td>
                <td>'.$total_days.'</td>
            </tr>';

        } else {

            $entry = 0;
            $leave = 0;
            $form_leave = 0;
            $form_entry = 0;

            foreach ($rows as $r) {

                switch ($this->_attendance_status_label($r['FUNCTION_KEY'])) {

                    case 'دخول':
                        $entry++;
                        break;

                    case 'خروج':
                        $leave++;
                        break;

                    case 'خروج بإذن':
                        $form_leave++;
                        break;

                    case 'عودة إذن':
                        $form_entry++;
                        break;
                }
            }

            echo '<tr><th>عدد الحركات</th><th>الدخول</th><th>الخروج</th><th>الأذونات</th></tr>';
            echo '<tr><td>'.count($rows).'</td><td>'.$entry.'</td><td>'.$leave.'</td><td>'.($form_leave+$form_entry).'</td></tr>';
            echo '<tr><td colspan="'.$col_count.'"></td></tr>';

            echo '<tr>
                <th>#</th>
                <th>التاريخ</th>
                <th>الوقت</th>
                <th>الحالة</th>
            </tr>';

            $i = 1;

            foreach ($rows as $row) {

                $rstatus = $this->_attendance_status_label($row['FUNCTION_KEY']);

                echo '<tr>
                    <td>'.$i++.'</td>
                    <td>'.$row['ATTENDANCE_DATE'].'</td>
                    <td>'.$row['ATTENDANCE_TIME'].'</td>
                    <td>'.$rstatus.'</td>
                </tr>';
            }

            echo '<tr>
                <td colspan="4">عدد الحركات : '.count($rows).'</td>
            </tr>';
        }

        echo '</table>';

        exit;
    }
}
