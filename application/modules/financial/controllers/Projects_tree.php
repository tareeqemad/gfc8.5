<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Projects_tree extends MY_Controller
{
    // constant groups in CONSTANT_TB
    const TB_PROJECT_TYPE    = 551;
    const TB_ACTIVITY_NATURE = 552;

    const MAX_LEVEL = 3;

    function __construct()
    {
        parent::__construct();
        $this->load->model('projects_tree_model');
    }

    function index()
    {
        $this->load->helper('generate_list');

        $options = array('template_head' => '<ul>', 'template_foot' => '</ul>', 'use_top_wrapper' => false);
        $template = '<li><span data-id="{NODE_ID}" data-level="{NODE_LEVEL}" ondblclick="projects_tree_get(\'{NODE_ID}\');"><i class="fa fa-minus"></i>{NODE_NAME}{BALANCE_HTML}</span>{SUBS}</li>';
        $data['tree'] = '<ul class="tree" id="projects_tree"><li><span data-id="0" data-level="0"><i class="fa fa-minus"></i>شجرة المشاريع</span><ul>'
            . generate_list($this->_build_tree(), $options, $template) . '</ul></li></ul>';
        $data['title'] = 'شجرة المشاريع';
        $data['content'] = 'projects_tree_index';
        $data['help'] = $this->help;
        $this->_lookups($data);
        $this->load->view('template/template1', $data);
    }

    // dropdown sources for the form
    private function _lookups(&$data)
    {
        $this->load->model('settings/constant_details_model');
        $this->load->model('settings/gcc_branches_model');
        $this->load->model('employees/employees_model');

        $as_rows = function ($result) { return is_array($result) ? $result : array(); };

        $data['project_type_cons']    = $as_rows($this->constant_details_model->get_list(self::TB_PROJECT_TYPE));
        $data['activity_nature_cons'] = $as_rows($this->constant_details_model->get_list(self::TB_ACTIVITY_NATURE));
        $data['branches']             = $as_rows($this->gcc_branches_model->get_all());
        $data['supervisors']          = $as_rows($this->employees_model->get_all());
    }

    // builds the tree in one call
    private function _build_tree()
    {
        $rows = $this->projects_tree_model->get_all();
        if (!is_array($rows)) return array();

        $balances = $this->_balances();
        $rows_by_parent = array();
        foreach ($rows as $row) {
            if (!isset($row['NODE_ID'])) continue;
            $row['NODE_ID'] = (string)(int)$row['NODE_ID'];
            $row['NODE_LEVEL'] = (int)$row['NODE_LEVEL'];
            // generate_list treats braces as placeholders
            $row['NODE_NAME'] = str_replace(array('{', '}'), array('&#123;', '&#125;'),
                htmlspecialchars($row['NODE_NAME'], ENT_QUOTES, 'UTF-8'));
            $row['BALANCE_HTML'] = $this->_balance_badge($row, $balances);
            $parent_id = (string)(int)(isset($row['PARENT_ID']) ? $row['PARENT_ID'] : 0);
            $rows_by_parent[$parent_id][] = $row;
        }

        return $this->_attach_subs($rows_by_parent, '0', 0);
    }

    // project number to balance, null on failure
    private function _balances()
    {
        $rows = $this->projects_tree_model->get_balances();
        if (!is_array($rows)) return null;

        $balances = array();
        foreach ($rows as $row) {
            if (!isset($row['PROJECT_NO'])) continue;
            $balances[trim((string)$row['PROJECT_NO'])] = (float)$row['BALANCE'];
        }
        return $balances;
    }

    // balance badge, level 3 only
    private function _balance_badge($row, $balances)
    {
        if ($balances === null || $row['NODE_LEVEL'] !== Projects_tree_model::PICK_LEVEL) return '';

        $project_no = isset($row['PROJECT_NO']) ? trim((string)$row['PROJECT_NO']) : '';
        $balance = round(isset($balances[$project_no]) ? $balances[$project_no] : 0, 2);
        // avoids printing -0.00
        if ($balance == 0) $balance = 0;

        $color = $balance < 0 ? 'text-danger' : 'text-dark';
        return '<span class="badge bg-light border mx-2 ltr ' . $color . '" title="رصيد المشروع">'
            . number_format($balance, 2) . '</span>';
    }

    // nests children under each node
    private function _attach_subs(&$rows_by_parent, $parent_id, $depth)
    {
        if ($depth >= self::MAX_LEVEL || !isset($rows_by_parent[$parent_id])) return array();

        $nodes = $rows_by_parent[$parent_id];
        foreach ($nodes as &$node) {
            $node['subs'] = $this->_attach_subs($rows_by_parent, $node['NODE_ID'], $depth + 1);
        }
        unset($node);
        return $nodes;
    }

    // project picker popup for journal rows
    function public_select_project($txt = '')
    {
        // echoed into JS as an element id
        $data['txt'] = preg_replace('~[^A-Za-z0-9_]~', '', (string)$txt);
        $data['title'] = 'اختيار المشروع';
        $data['content'] = 'projects_tree_select';
        $data['pick_level'] = Projects_tree_model::PICK_LEVEL;
        $data['nodes'] = $this->_tree_order();
        $this->load->view('template/view', $data);
    }

    // every node, parents before their children
    private function _tree_order()
    {
        $rows = $this->projects_tree_model->get_all();
        if (!is_array($rows)) return array();

        $rows_by_parent = array();
        foreach ($rows as $row) {
            if (!isset($row['NODE_ID'])) continue;
            $parent_id = (string)(int)(isset($row['PARENT_ID']) ? $row['PARENT_ID'] : 0);
            $rows_by_parent[$parent_id][] = $row;
        }

        $ordered = array();
        $this->_walk_tree($rows_by_parent, '0', 0, $ordered);
        return $ordered;
    }

    // depth first walk into a flat list
    private function _walk_tree(&$rows_by_parent, $parent_id, $depth, &$ordered)
    {
        if ($depth >= self::MAX_LEVEL || !isset($rows_by_parent[$parent_id])) return;

        foreach ($rows_by_parent[$parent_id] as $row) {
            $ordered[] = $row;
            $this->_walk_tree($rows_by_parent, (string)(int)$row['NODE_ID'], $depth + 1, $ordered);
        }
    }

    function get_id()
    {
        if (!$this->_require_post()) return;
        $id = $this->_node_id('node_id');
        if ($id === null) return $this->return_json('رقم العنصر غير صحيح.');
        $this->return_json($this->projects_tree_model->get($id));
    }

    function create()
    {
        if (!$this->_require_post(true)) return;
        $parent = $this->_node_id('parent_id', true);
        if ($parent === null) return $this->return_json('اختر العنصر الأب.');
        $data = $this->_postedData('PARENT_ID', $parent);
        if (!is_array($data)) return $this->return_json($data);
        $result = $this->projects_tree_model->create($data);
        $created = json_decode($result, true);
        if (is_array($created) && isset($created['msg'], $created['id']) && (int)$created['msg'] === 1) {
            $this->return_json($created);
        } else {
            $this->return_json($result);
        }
    }

    function edit()
    {
        if (!$this->_require_post(true)) return;
        $id = $this->_node_id('node_id');
        if ($id === null) return $this->return_json('اختر العنصر المطلوب تعديله.');
        $data = $this->_postedData('NODE_ID', $id);
        if (!is_array($data)) return $this->return_json($data);
        $result = $this->projects_tree_model->edit($data);
        $this->return_json(trim((string)$result) === '1' ? 1 : $result);
    }

    function delete()
    {
        if (!$this->_require_post(true)) return;
        $id = $this->_node_id('node_id');
        if ($id === null) return $this->return_json('اختر العنصر المطلوب حذفه.');
        $result = $this->projects_tree_model->delete($id);
        $this->return_json(trim((string)$result) === '1' ? 1 : $result);
    }

    // POST only, optional CSRF check
    private function _require_post($check_token = false)
    {
        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->output->set_status_header(405);
            $this->return_json('طريقة الطلب غير مسموحة.');
            return false;
        }
        if ($check_token) $this->IsAuthorized();
        return true;
    }

    // posted node id, null if invalid
    private function _node_id($field, $allow_root = false)
    {
        $value = $this->input->post($field);
        if (!is_scalar($value)) return null;
        $value = trim((string)$value);
        if (!ctype_digit($value) || strlen($value) > 12) return null;
        if (!$allow_root && (int)$value === 0) return null;
        return (string)(int)$value;
    }

    // validated fields, or an error message
    // order must match the procedure signature
    private function _postedData($key, $id)
    {
        $fields = array(
            'NODE_NAME'          => array('text', 250, 'اسم العنصر / المشروع'),
            'PROJECT_NO'         => array('text', 30,  'رقم المشروع'),
            'PROJECT_TYPE'       => array('key',  5,   'نوع المشروع'),
            'ACTIVITY_NATURE'    => array('key',  5,   'طبيعة النشاط'),
            'PROJECT_OPEN_DATE'  => array('date', 10,  'تاريخ فتح المشروع'),
            'BRANCH'             => array('key',  5,   'الفرع'),
            'PROJECT_LOCATION'   => array('text', 500, 'موقع المشروع'),
            'PROJECT_SUPERVISOR' => array('key',  10,  'مشرف المشروع')
        );

        $data = array(array('name' => $key, 'value' => $id, 'type' => '', 'length' => -1));

        foreach ($fields as $name => $options) {
            list($kind, $max_length, $label) = $options;

            $value = $this->input->post(strtolower($name));
            if ($value !== null && !is_scalar($value)) return 'قيمة غير صحيحة في ' . $label;
            $value = trim((string)$value);

            if ($name === 'NODE_NAME' && $value === '') return 'اسم العنصر / المشروع مطلوب.';
            if (mb_strlen($value, 'UTF-8') > $max_length) return $label . ': تجاوز الحد المسموح.';

            if ($kind === 'key' && $value !== '' && !ctype_digit($value)) {
                return $label . ': اختر قيمة من القائمة.';
            }

            if ($kind === 'date' && $value !== '') {
                if (!preg_match('~^(\d{2})/(\d{2})/(\d{4})$~', $value, $date)
                    || !checkdate((int)$date[2], (int)$date[1], (int)$date[3])) {
                    return $label . ' غير صحيح؛ استخدم يوم/شهر/سنة.';
                }
            }

            $data[] = array('name' => $name, 'value' => $value === '' ? null : $value, 'type' => '', 'length' => -1);
        }

        return $data;
    }
}
