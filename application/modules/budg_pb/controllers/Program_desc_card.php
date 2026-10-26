<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 * كونترولر مديول بطاقة وصف البرنامج (PROGRAM_DESC_CARD)
 * أُعيد تسميته من Programs بناءً على طلب المستخدم
 * يطابق نمط الشاشة الرئيسية (fieldsets + modules::run للجداول الفرعية الحقيقية)
 * المؤكد من القالب المرجعي المرفوع (نمط extract-like master show)
 */
class Program_desc_card extends MY_Controller
{

    var $MODEL_NAME = 'Program_desc_card_model';
    var $MODEL_ACTIVITIES_NAME = 'Activities_model';
    var $MODEL_PROGRAMS_NAME = 'Programs_model';
    var $MODEL_PROJECTS_NAME = 'Projects_model';
    var $MODEL_UNITS_NAME = 'Units_model';
    var $PAGE_URL = 'budg_pb/program_desc_card/get_page';

    function __construct()
    {
        parent::__construct();
        $this->load->model($this->MODEL_NAME);
        $this->load->model($this->MODEL_ACTIVITIES_NAME);
        $this->load->model($this->MODEL_PROGRAMS_NAME);
        $this->load->model($this->MODEL_PROJECTS_NAME);
        $this->load->model($this->MODEL_UNITS_NAME);
    }

    // الصفحة الرئيسية لعرض قائمة البرامج
    function index()
    {
        $data['title'] = 'إدارة بطاقات وصف البرامج';
        $data['help'] = $this->help;
        $data['content'] = 'program_desc_card_index';
        add_css('select2_metro_rtl.css');
        add_js('select2.min.js');
        $this->load->view('template/template', $data);
    }

    //////////////////////////////////////////////////////////////////////////////
    // عرض قائمة البرامج مع البحث الديناميكي والصفحات
    function get_page($page = 1)
    {
        $this->load->library('pagination');
        $where_sql = " where 1=1 ";
        $where_sql .= isset($this->p_program_no) && $this->p_program_no != null ? " AND  M.PROGRAM_NO ={$this->p_program_no}  " : "";
        $where_sql .= isset($this->p_program_name) && $this->p_program_name != null ? " AND  M.PROGRAM_NAME LIKE '%{$this->p_program_name}%' " : "";

        $config['base_url'] = base_url($this->PAGE_URL);
        $count_rs = $this->get_table_count("PROGRAMS_TB", $where_sql);
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
        $data['page_rows'] = $this->{$this->MODEL_NAME}->get_list_all($where_sql, $offset, $row);
        $data['offset'] = $offset + 1;
        $data['page'] = $page;
        $this->_look_ups($data);
        $this->load->view('program_desc_card_page', $data);
    }

    //////////////////////////////////////////////////////////////////////////////
    // تحميل الثوابت (Lookups) المطلوبة لتغذية القوائم المنسدلة في الشاشة
    // ⭐ أرقام الثوابت الفعلية (constant_details_model->get_list(ID)) يجب
    //   تأكيدها من صاحب الصلاحية قبل الاعتماد النهائي — الأسطر أدناه توضيحية
    function _look_ups(&$data)
    {
        $this->load->model('settings/constant_details_model');
        $data['help'] = $this->help;

        // ⭐ قائمة كل البرامج لتغذية القائمة المنسدلة الرئيسية (dp_program_id)
        // بنفس نمط Budget_groups.php: $this->programs_model->get_all()
        $data['programs_cons'] = $this->{$this->MODEL_PROGRAMS_NAME}->get_all();

        // الغاية -> قائمة منسدلة (GOAL_ID) — رقم الثابت يُحدَّد لاحقًا
        // $data['goal_cons'] = $this->constant_details_model->get_list([رقم ثابت الغايات]);

        // الهدف الاستراتيجي -> قائمة منسدلة (STRATEGIC_OBJECTIVE_ID) — رقم الثابت يُحدَّد لاحقًا
        // $data['strategic_objective_cons'] = $this->constant_details_model->get_list([رقم الثابت]);

        // الخدمات -> قائمة منسدلة متعددة الاختيار — رقم الثابت يُحدَّد لاحقًا
        // $data['services_cons'] = $this->constant_details_model->get_list([رقم ثابت الخدمات]);

        // الشركاء -> قائمة منسدلة متعددة الاختيار — رقم الثابت يُحدَّد لاحقًا
        // $data['partners_cons'] = $this->constant_details_model->get_list([رقم ثابت الشركاء]);
    }

    ///////////////////////////////////////////////////////////////
    // شاشة إنشاء برنامج جديد (GET) أو حفظه (POST)
    function create($id = '')
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->program_id = $this->{$this->MODEL_NAME}->create($this->_postedData('create'));

            if (intval($this->program_id) <= 0) {
                $this->print_error('لم يتم الحفظ' . '<br>' . $this->program_id);
            }
            echo intval($this->program_id);
        } else {
            $data['content'] = 'program_desc_card_show';
            $data['title'] = 'إضافة بطاقة وصف برنامج جديدة';
            $data['action'] = 'index'; // تُستخدم داخل الشاشة لتحديد أن الحفظ عبر create
            $data['back_url'] = base_url('budg_pb/program_desc_card/index');
            $data['order_purpose'] = '';
            add_css('select2_metro_rtl.css');
            add_js('select2.min.js');
            $this->_look_ups($data);
            $this->load->view('template/template', $data);
        }
    }

    /////////////////////////////////
    // عرض بيانات برنامج موجود (نموذج التعديل) — يُمرَّر master_tb_data فقط
    function get($id)
    {
        $data['master_tb_data'] = $this->{$this->MODEL_NAME}->get($id);
        $data['content'] = 'program_desc_card_show';
        $data['title'] = 'تعديل بطاقة وصف البرنامج';
        $data['action'] = 'edit';
        $data['help'] = $this->help;
        $data['back_url'] = base_url('budg_pb/program_desc_card/index');
        $data['order_purpose'] = '';
        $data['can_edit'] = HaveAccess(base_url('budg_pb/program_desc_card/edit'));

        $this->_look_ups($data);
        $this->load->view('template/template', $data);
    }

    /// ///////////////////////////////
    // حفظ التعديلات على برنامج موجود
    function edit()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->program_id = $this->{$this->MODEL_NAME}->edit($this->_postedData());

            if (intval($this->program_id) <= 0) {
                $this->print_error('لم يتم الحفظ' . '<br>' . $this->program_id);
            }
            echo intval($this->program_id);
        }
    }

    ////////////////////
    // حذف برنامج (حذف منطقي)
    function delete_program($id)
    {
        $result = $this->{$this->MODEL_NAME}->delete_program($id);
        echo $result;
    }

    ////////////////////////////////////////////////////////////////////////
    // ============== جداول فرعية حقيقية (تُستدعى عبر modules::run) ==========
    ////////////////////////////////////////////////////////////////////////

    // جدول فرعي: الأنشطة والوحدات التنظيمية المرتبطة بالبرنامج
    // يُستدعى من داخل program_desc_card_show.php عبر modules::run(..., $program_id)
    function public_get_program_activities_details($program_id = 0)
    {
        $data['program_id'] = $program_id;
        $data['activities_rows'] = $this->{$this->MODEL_ACTIVITIES_NAME}->get_list($program_id);
        $data['can_edit'] = HaveAccess(base_url('budg_pb/program_desc_card/add_activity'));
        $activities_program = [];
        $projects_program = [];
        $activities_units = [];
        $projects_units = [];

        $activities_program[$program_id] = $data['activities_rows'];
        $projects_program[$program_id] = $this->Projects_model->get_list($program_id);

        foreach ($activities_program as $program) {
            foreach ($program as $activity) {
                $activities_units[$activity['ACTIVITY_ID']] = $this->Units_model->activity_unit_get_list($activity['ACTIVITY_ID']);
            }
        }

        foreach ($projects_program as $program) {
            foreach ($program as $project) {
                $projects_units[$project['PROJECT_ID']] = $this->Units_model->project_unit_get_list($project['PROJECT_ID']);
            }
        }


        $data['activities_program'] = $activities_program;
        $data['projects_program'] = $projects_program;
        $data['activities_units'] = $activities_units;
        $data['projects_units'] = $projects_units;
        $this->load->view('program_desc_card_activities_details', $data);
    }

    // ⭐ نفس عقد Budget_groups.php: إرجاع أنشطة برنامج كـ JSON مباشر (وليس View كامل)
    // يُستخدم لتحديث قائمة/جدول الأنشطة فور اختيار برنامج من القائمة المنسدلة (قبل الحفظ حتى)
    function get_activities_list($program_id = 0)
    {
        $result = $this->{$this->MODEL_ACTIVITIES_NAME}->get_list($program_id);
        $this->return_json($result);
    }

    // جدول فرعي: مؤشرات قياس أداء البرنامج (عرض فقط)
    function public_get_program_indicators_details($program_id = 0)
    {
        $data['program_id'] = $program_id;
        $data['indicators_rows'] = $this->{$this->MODEL_NAME}->get_program_indicators($program_id, date('Y'));
        $this->load->view('program_desc_card_indicators_details', $data);
    }

    // إضافة نشاط جديد تابع لبرنامج (Ajax من داخل الجدول الفرعي)
    function add_activity()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                array('name' => 'PROGRAM_ID', 'value' => $this->input->post('program_id'), 'type' => '', 'length' => -1),
                array('name' => 'ACTIVITY_NAME', 'value' => $this->input->post('activity_name'), 'type' => '', 'length' => -1),
            );
            $result = $this->{$this->MODEL_ACTIVITIES_NAME}->create($data);
            echo intval($result);
        }
    }

    ///////////////////////////////////////////////////////////////
    // البيانات المُرسلة من نموذج program_desc_card_show (إضافة/تعديل)
    // أسماء الحقول مطابقة لأعمدة PROGRAMS_TB كما وردت في 10_New_Tables_and_Relations.sql
    // ⭐ SERVICES/PARTNERS تصل كقوائم (Multi-select) وتُحوَّل لسلسلة مفصولة بفاصلة
    // ⭐ بعد تحويل "البرنامج" إلى قائمة منسدلة لبرنامج موجود مسبقًا، لم تعد
    //   PROGRAM_NO/PROGRAM_NAME/PROGRAM_DESC حقولاً قابلة للتحرير من هذه الشاشة
    //   (تُعرض فقط للعرض) — يُرسل PROGRAM_ID دائمًا (اختيار إلزامي)، وتُحدَّث
    //   فقط الحقول الوصفية الخاصة ببطاقة البرنامج
    function _postedData($typ = null)
    {
        $services_val = is_array($this->input->post('services')) ? implode(',', $this->input->post('services')) : $this->p_services;
        $partners_val = is_array($this->input->post('partners')) ? implode(',', $this->input->post('partners')) : $this->p_partners;

        $result = array(
            array('name' => 'PROGRAM_ID', 'value' => $this->p_program_id, 'type' => '', 'length' => -1),
            array('name' => 'GOAL_ID', 'value' => $this->p_goal_id, 'type' => '', 'length' => -1),
            array('name' => 'STRATEGIC_OBJECTIVE_ID', 'value' => $this->p_strategic_objective_id, 'type' => '', 'length' => -1),
            array('name' => 'SERVICES', 'value' => $services_val, 'type' => '', 'length' => -1),
            array('name' => 'PARTNERS', 'value' => $partners_val, 'type' => '', 'length' => -1),
        );

        return $result;
    }
}
