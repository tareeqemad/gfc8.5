<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Strategic_framework extends MY_Controller
{


    // الشاشة الرئيسية
    public function index()
    {
        $data['title'] = "الإطار الاستراتيجي والمؤشرات";
        // تحميل الهيكل في الموديل
        $this->load->view('strategic_framework_view', $data);
    }
}
