<?php

$MODULE_NAME = 'hr_attendance';
$TB_NAME     = 'Transport_allowance';

$create_url   = base_url("$MODULE_NAME/$TB_NAME/create");
$get_page_url = base_url("$MODULE_NAME/$TB_NAME/get_page");
$delete_url   = base_url("$MODULE_NAME/$TB_NAME/delete");
$export_url   = base_url("$MODULE_NAME/$TB_NAME/public_export_excel");

echo AntiForgeryToken();
?>

<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <span class="main-content-title mg-b-0 mg-b-lg-1"><?= $title ?></span>
    </div>
    <div class="justify-content-center mt-2">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page"><?= $title ?></li>
        </ol>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header align-items-center d-flex py-3">
                <div class="mb-0 flex-grow-1 card-title">
                    <?= $title ?>
                </div>
                <?php if (HaveAccess($create_url)) : ?>
                    <div class="flex-shrink-0">
                        <a class="btn btn-success" href="<?= $create_url ?>">
                            جديد <i class="fa fa-plus"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card-body">
                <form class="form-vertical" id="<?= $TB_NAME ?>_form" onsubmit="return false;">
                    <div class="row align-items-end">

                        <div class="form-group col-md-2">
                            <label>نوع البدل</label>
                            <select name="transport_type" id="dp_transport_type" class="form-control sel2">
                                <option value="">_________</option>
                                <option value="1">1 - داخلي</option>
                                <option value="2">2 - خارجي</option>
                            </select>
                        </div>

                        <div class="form-group col-md-3">
                            <label>من منطقة</label>
                            <select name="from_region" id="dp_from_region" class="form-control sel2">
                                <option value="">_________</option>
                                <?php foreach ($regions as $row) : ?>
                                    <option value="<?= $row['CON_NO'] ?>"><?= $row['CON_NAME'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group col-md-3">
                            <label>إلى منطقة</label>
                            <select name="to_region" id="dp_to_region" class="form-control sel2">
                                <option value="">_________</option>
                                <?php foreach ($regions as $row) : ?>
                                    <option value="<?= $row['CON_NO'] ?>"><?= $row['CON_NAME'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group col-md-2">
                            <label>ساري بتاريخ</label>
                            <input type="date" name="on_date" id="txt_on_date" class="form-control">
                        </div>

                        <div class="col-md-12"><hr></div>

                        <div class="form-group col-md-12 d-flex gap-2" style="justify-content:left;">
                            <button type="button" onclick="javascript:search();" class="btn btn-primary">
                                إستعلام <i class="fa fa-search"></i>
                            </button>
                            <button type="button" onclick="javascript:export_excel();" class="btn btn-success">
                                تصدير إكسل <i class="fa fa-file-excel-o"></i>
                            </button>
                            <button type="button" onclick="javascript:clear_form();" class="btn btn-cyan-light">
                                تفريغ <i class="fa fa-eraser"></i>
                            </button>
                        </div>

                    </div>
                </form>
                <hr>
                <div id="container"></div>
            </div>
        </div>
    </div>
</div>

<?php

$scripts = <<<SCRIPT
<script type="text/javascript">

    $(function(){
        $('.sel2').select2();
        search();
    });

    function values_search(){
        return {
            transport_type: $('#dp_transport_type').val(),
            from_region: $('#dp_from_region').val(),
            to_region: $('#dp_to_region').val(),
            on_date: $('#txt_on_date').val()
        };
    }

    function search(){
        get_data('{$get_page_url}', values_search(), function(data){
            $('#container').html(data);
        }, 'html');
    }

    function LoadingData(){
        ajax_pager_data('#transport_allowance_tb > tbody', values_search());
    }

    function export_excel(){
        window.location = '{$export_url}?' + $.param(values_search());
    }

    function clear_form(){
        $('#dp_transport_type, #dp_from_region, #dp_to_region').select2('val', '');
        $('#txt_on_date').val('');
        search();
    }

    function delete_row(btn, ser){

        if($(btn).hasClass('disabled')){
            return;
        }

        if(!confirm('هل تريد حذف هذا البدل نهائياً؟')){
            return;
        }

        $(btn).addClass('disabled');

        $.ajax({
            url: '{$delete_url}',
            type: 'POST',
            data: {ser: ser},
            dataType: 'html'
        }).done(function(data){
            if(parseInt(data) >= 1){
                success_msg('رسالة', 'تم الحذف بنجاح');
                search();
            }else{
                $(btn).removeClass('disabled');
                danger_msg('تحذير', data);
            }
        }).fail(function(xhr){
            $(btn).removeClass('disabled');
            danger_msg('تحذير', 'فشل في العملية<br>' + xhr.responseText);
        });
    }

</script>
SCRIPT;

sec_scripts($scripts);
