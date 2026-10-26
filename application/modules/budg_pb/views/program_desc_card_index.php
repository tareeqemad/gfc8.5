<?php
$MODULE_NAME = 'budg_pb';
$TB_NAME = 'program_desc_card';
$get_page_url = base_url("$MODULE_NAME/$TB_NAME/get_page");
$create_url = base_url("$MODULE_NAME/$TB_NAME/create");
?>

<div class="row">
    <div class="toolbar">
        <div class="caption"><?=$title?></div>
        <ul>
            <?php if (HaveAccess($create_url)): ?>
                <li><a href="<?=$create_url?>" class="btn btn-primary btn-sm">إضافة برنامج جديد</a></li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<div class="form-body">
    <form class="form-inline" id="search_form" method="get" action="<?=$get_page_url?>">
        <div class="form-group">
            <label>رقم البرنامج</label>
            <input type="text" name="program_no" id="txt_search_program_no" class="form-control"/>
        </div>
        <div class="form-group">
            <label>اسم البرنامج</label>
            <input type="text" name="program_name" id="txt_search_program_name" class="form-control"/>
        </div>
        <button type="submit" class="btn btn-default">بحث</button>
    </form>

    <div id="page_container">
        <?php $this->load->view('program_desc_card_page', $data ?? array()); ?>
    </div>
</div>
