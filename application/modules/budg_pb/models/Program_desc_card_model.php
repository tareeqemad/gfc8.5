<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 * موديل مديول بطاقة وصف البرنامج (PROGRAM_DESC_CARD)
 * أُعيدت تسميته من Programs_model بناءً على طلب المستخدم
 * مبني بنفس نمط Extract_model.php المعتمد في النظام
 * PKG_NAME / TABLE_NAME حسب اتفاقية التسمية الموحّدة
 */
class Program_desc_card_model extends MY_Model
{
    var $PKG_NAME = "BUDG_PB_PKG";
    var $TABLE_NAME = 'PROGRAMS_TB';

    function __construct()
    {
        parent::__construct();
        $this->load->model('Root/New_rmodel');
    }

    // عرض بيانات برنامج واحد عبر PROGRAM_ID (يستدعي PROGRAMS_TB_GET)
    function get($id = 0)
    {
        $params = array(
            array('name' => ':PROGRAM_ID_in', 'value' => "{$id}", 'type' => '', 'length' => -1),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor', 'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT', 'value' => 'MSG_OUT', 'type' => SQLT_CHR, 'length' => -1),
        );

        $result = $this->New_rmodel->general_get($this->PKG_NAME, $this->TABLE_NAME . '_GET', $params);

        return $result;
    }

    // عرض جميع البرامج (بحث ديناميكي + صفحات) (يستدعي PROGRAMS_TB_GETLIST)
    function get_list_all($sql, $offset, $row)
    {
        $params = array(
            array('name' => ':INXSQL', 'value' => "{$sql}", 'type' => '', 'length' => -1),
            array('name' => ':OFFSET', 'value' => $offset, 'type' => '', 'length' => -1),
            array('name' => ':ROW', 'value' => $row, 'type' => '', 'length' => -1),
            array('name' => ':GET_ROW_LOG', 'value' => 'cursor', 'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT', 'value' => 'MSG_OUT', 'type' => '', 'length' => 500)
        );

        $result = $this->New_rmodel->general_get($this->PKG_NAME, $this->TABLE_NAME . '_GETLIST', $params);
        return $result;
    }

    // إضافة برنامج جديد (يستدعي PROGRAMS_TB_INSERT)
    function create($data)
    {
        $params = array();
        $this->_extract_data($params, $data);
        $result = $this->conn->excuteProcedures($this->PKG_NAME, $this->TABLE_NAME . '_INSERT', $params);

        return $result['MSG_OUT'];
    }

    // تعديل بيانات برنامج موجود (يستدعي PROGRAMS_TB_UPDATE)
    function edit($data)
    {
        $params = array();
        $this->_extract_data($params, $data);
        $result = $this->conn->excuteProcedures($this->PKG_NAME, $this->TABLE_NAME . '_UPDATE', $params);
        return $result['MSG_OUT'];
    }

    // حذف برنامج (حذف منطقي) (يستدعي PROGRAMS_TB_DELETE)
    function delete_program($id)
    {
        $params = array(
            array('name' => ':PROGRAM_ID', 'value' => $id, 'type' => '', 'length' => -1),
            array('name' => ':MSG_OUT', 'value' => 'MSG_OUT', 'type' => SQLT_CHR, 'length' => 500)
        );
        $result = $this->conn->excuteProcedures($this->PKG_NAME, $this->TABLE_NAME . '_DELETE', $params);

        return $result['MSG_OUT'];
    }

    // جلب مؤشرات قياس أداء برنامج معيّن لسنة محددة (يستدعي PROGRAM_INDICATORS_GETLIST)
    // PROGRAM_TYPE = 1 ثابت هنا لأن الاستدعاء من مديول البرامج نفسه (وليس الأنشطة)
    function get_program_indicators($program_id, $year)
    {
        $params = array(
            array('name' => ':PROGRAM_TYPE', 'value' => 1, 'type' => '', 'length' => -1),
            array('name' => ':PROGRAM_NO', 'value' => "{$program_id}", 'type' => '', 'length' => -1),
            array('name' => ':YEAR', 'value' => "{$year}", 'type' => '', 'length' => -1),
            array('name' => ':REF_CUR_OUT', 'value' => 'cursor', 'type' => OCI_B_CURSOR),
            array('name' => ':MSG_OUT', 'value' => 'MSG_OUT', 'type' => SQLT_CHR, 'length' => -1),
        );

        $result = $this->New_rmodel->general_get($this->PKG_NAME, 'PROGRAM_INDICATORS_GETLIST', $params);

        return $result;
    }

}
