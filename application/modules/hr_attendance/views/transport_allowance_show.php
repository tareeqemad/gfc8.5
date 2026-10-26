<?php

$MODULE_NAME = 'hr_attendance';
$TB_NAME     = 'Transport_allowance';

$back_url   = base_url("$MODULE_NAME/$TB_NAME");
$post_url   = base_url("$MODULE_NAME/$TB_NAME/" . ($isCreate ? 'create' : 'edit'));
$change_url = base_url("$MODULE_NAME/$TB_NAME/public_change_amount");

$can_save = HaveAccess($post_url);

$is_edit = !$isCreate;
$rs      = $is_edit ? $master_tb_data[0] : array();
$is_open = !$is_edit || $rs['END_DATE_STR'] == '';

echo AntiForgeryToken();
?>

<div class="breadcrumb-header justify-content-between">
    <div class="left-content">
        <span class="main-content-title mg-b-0 mg-b-lg-1"><?= $title ?></span>
    </div>
    <div class="justify-content-center mt-2">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $back_url ?>">بدل التنقل حسب المناطق</a></li>
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
                <div class="flex-shrink-0">
                    <a class="btn btn-secondary" href="<?= $back_url ?>">
                        رجوع <i class="fa fa-backward"></i>
                    </a>
                </div>
            </div>

            <div class="card-body">
                <form class="form-vertical" id="<?= $TB_NAME ?>_form">

                    <?php if ($is_edit) : ?>
                        <input type="hidden" name="ser" value="<?= $rs['SER'] ?>">
                    <?php endif; ?>

                    <div class="row">

                        <div class="form-group col-md-3">
                            <label>من منطقة</label>
                            <select name="from_region" id="dp_from_region" class="form-control sel2">
                                <option value="">_________</option>
                                <?php foreach ($regions as $row) : ?>
                                    <option value="<?= $row['CON_NO'] ?>" <?= ($is_edit && $rs['FROM_REGION'] == $row['CON_NO']) ? 'selected' : '' ?>>
                                        <?= $row['CON_NAME'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group col-md-3">
                            <label>إلى منطقة</label>
                            <select name="to_region" id="dp_to_region" class="form-control sel2">
                                <option value="">_________</option>
                                <?php foreach ($regions as $row) : ?>
                                    <option value="<?= $row['CON_NO'] ?>" <?= ($is_edit && $rs['TO_REGION'] == $row['CON_NO']) ? 'selected' : '' ?>>
                                        <?= $row['CON_NAME'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group col-md-2">
                            <label>البدل اليومي (شيكل)</label>
                            <input type="number" step="0.01" min="0" name="daily_amount" id="txt_daily_amount"
                                   class="form-control" value="<?= $is_edit ? $rs['DAILY_AMOUNT'] : '' ?>">
                        </div>

                        <div class="form-group col-md-2">
                            <label>تاريخ البدء</label>
                            <input type="date" name="start_date" id="txt_start_date" class="form-control"
                                   value="<?= $is_edit ? $rs['START_DATE_STR'] : '' ?>">
                        </div>

                        <div class="form-group col-md-2">
                            <label>تاريخ الانتهاء</label>
                            <input type="date" name="end_date" id="txt_end_date" class="form-control"
                                   value="<?= $is_edit ? $rs['END_DATE_STR'] : '' ?>" <?= $is_open ? 'disabled' : '' ?>>
                        </div>

                    </div>

                    <div class="row mt-2">

                        <div class="form-group col-md-9">
                            <label>ملاحظات</label>
                            <input type="text" name="note" id="txt_note" class="form-control" maxlength="500"
                                   value="<?= $is_edit ? htmlspecialchars($rs['NOTE'] ?? '') : '' ?>">
                        </div>

                        <div class="form-group col-md-3 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" id="chk_open_end" <?= $is_open ? 'checked' : '' ?>>
                                <label class="form-check-label" for="chk_open_end">فترة مفتوحة (بدون تاريخ انتهاء)</label>
                            </div>
                        </div>

                    </div>

                    <?php if ($is_edit && $can_save) : ?>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label class="d-block">طريقة حفظ المبلغ</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="save_mode" id="rd_correct" checked>
                                    <label class="form-check-label" for="rd_correct">تصحيح السجل الحالي</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="save_mode" id="rd_change">
                                    <label class="form-check-label" for="rd_change">تغيير البدل من تاريخ</label>
                                </div>
                            </div>

                            <div class="form-group col-md-3 mt-2" id="effective_wrap" style="display:none;">
                                <label>يسري من</label>
                                <input type="date" id="txt_effective_date" class="form-control" min="<?= $rs['START_DATE_STR'] ?>">
                            </div>
                        </div>
                    <?php endif; ?>

                </form>

                <?php if ($can_save) : ?>
                    <hr>
                    <div style="text-align:left;">
                        <button type="button" id="btn_save" onclick="javascript:save();" class="btn btn-primary">
                            حفظ البيانات <i class="fa fa-save"></i>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php

$IS_EDIT_JS = $is_edit ? 'true' : 'false';

$scripts = <<<SCRIPT
<script type="text/javascript">

    var is_edit = {$IS_EDIT_JS};
    var saving = false;
    var locked_fields = ['#dp_from_region', '#dp_to_region', '#txt_start_date', '#txt_end_date', '#txt_note', '#chk_open_end'];
    var original_values = {};

    $('.sel2').select2();

    $.each(locked_fields, function(i, id){
        original_values[id] = $(id).is(':checkbox') ? $(id).is(':checked') : $(id).val();
    });

    $('#chk_open_end').on('change', function(){
        if($(this).is(':checked')){
            $('#txt_end_date').val('').prop('disabled', true);
        }else{
            $('#txt_end_date').prop('disabled', false).focus();
        }
    });

    $('input[name="save_mode"]').on('change', apply_save_mode);

    function is_change_mode(){
        return is_edit && $('#rd_change').is(':checked');
    }

    function apply_save_mode(){

        var change = is_change_mode();

        $('#effective_wrap').toggle(change);

        $.each(locked_fields, function(i, id){

            var field = $(id);

            if(change){
                if(field.is(':checkbox')){
                    field.prop('checked', original_values[id]);
                }else if(field.hasClass('sel2')){
                    field.select2('val', original_values[id]);
                }else{
                    field.val(original_values[id]);
                }
            }

            if(field.hasClass('sel2')){
                field.select2('enable', !change);
            }else{
                field.prop('disabled', change);
            }
        });

        if(!change){
            $('#txt_end_date').prop('disabled', $('#chk_open_end').is(':checked'));
        }
    }

    function is_valid_amount(){
        return $('#txt_daily_amount').val() != '' && parseFloat($('#txt_daily_amount').val()) > 0;
    }

    function validate_form(){

        if($('#dp_from_region').val() == '' || $('#dp_to_region').val() == ''){
            danger_msg('رسالة', 'يجب اختيار منطقة السكن ومنطقة العمل');
            return false;
        }

        if(!is_valid_amount()){
            danger_msg('رسالة', 'يجب إدخال بدل يومي صحيح');
            return false;
        }

        if($('#txt_start_date').val() == ''){
            danger_msg('رسالة', 'يجب إدخال تاريخ البدء');
            return false;
        }

        if(!$('#chk_open_end').is(':checked')){

            if($('#txt_end_date').val() == ''){
                danger_msg('رسالة', 'يجب إدخال تاريخ الانتهاء أو تحديد الفترة كمفتوحة');
                return false;
            }

            if($('#txt_end_date').val() < $('#txt_start_date').val()){
                danger_msg('رسالة', 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء');
                return false;
            }
        }

        return true;
    }

    function validate_change(){

        if(!is_valid_amount()){
            danger_msg('رسالة', 'يجب إدخال بدل يومي صحيح');
            return false;
        }

        if($('#txt_effective_date').val() == ''){
            danger_msg('رسالة', 'يجب إدخال تاريخ السريان');
            return false;
        }

        return true;
    }

    function set_saving(state){
        saving = state;

        $('#btn_save').prop('disabled', state).html(state
            ? 'جاري الحفظ... <i class="fa fa-spinner fa-spin"></i>'
            : 'حفظ البيانات <i class="fa fa-save"></i>');

        if(state){
            showLoading();
        }else{
            HideLoading();
        }
    }

    function save(){

        if(saving){
            return;
        }

        var change = is_change_mode();

        if(change ? !validate_change() : !validate_form()){
            return;
        }

        if(!confirm('هل تريد الحفظ؟')){
            return;
        }

        var url  = change ? '{$change_url}' : '{$post_url}';
        var data = change
            ? {ser: $('input[name="ser"]').val(), new_amount: $('#txt_daily_amount').val(), effective_date: $('#txt_effective_date').val()}
            : $('#{$TB_NAME}_form').serialize();

        set_saving(true);

        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            dataType: 'html'
        }).done(function(result){
            if(parseInt(result) >= 1){
                success_msg('رسالة', 'تم حفظ البيانات بنجاح');
                get_to_link('{$back_url}');
            }else{
                set_saving(false);
                danger_msg('تحذير', result);
            }
        }).fail(function(xhr){
            set_saving(false);
            danger_msg('تحذير', 'فشل في العملية<br>' + xhr.responseText);
        });
    }

</script>
SCRIPT;

sec_scripts($scripts);
