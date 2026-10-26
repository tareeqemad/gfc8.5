<?php
$MODULE_NAME = 'payment_accounts';
$TB_NAME     = 'payment_accounts';

$get_page_url     = base_url("$MODULE_NAME/$TB_NAME/get_page");
$export_excel_url = base_url("$MODULE_NAME/$TB_NAME/export_excel");
$providers_url    = base_url("$MODULE_NAME/$TB_NAME/providers");
$validation_url   = base_url("$MODULE_NAME/$TB_NAME/validation");
$emp_url_base     = base_url("$MODULE_NAME/$TB_NAME/emp");

echo AntiForgeryToken();
?>

<link rel="stylesheet" href="<?= base_url() ?>assets-n/css/modules/payment_accounts/index.css" type="text/css" />
    <!-- PAGE-HEADER -->
    <div class="page-header">
        <div>
            <h1 class="page-title"><?= $title ?></h1>
        </div>
        <div class="ms-auto pageheader-btn">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0);">الرواتب والمستحقات</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= $title ?></li>
            </ol>
        </div>
    </div>
    <!-- PAGE-HEADER END -->

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?= $title ?></h3>
                    <div class="ms-auto d-flex gap-1 flex-wrap align-items-center">
                        <?php if (HaveAccess($providers_url)): ?>
                            <a class="btn btn-info btn-sm text-white" href="<?= $providers_url ?>">
                                <i class="fa fa-bank"></i> المزودون
                            </a>
                        <?php endif; ?>
                        <?php if (HaveAccess($validation_url)): ?>
                            <a class="btn btn-warning btn-sm" href="<?= $validation_url ?>">
                                <i class="fa fa-stethoscope"></i> تحقّق التوزيع
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <form id="<?= $TB_NAME ?>_form" onsubmit="return false;">
                        <div class="row">
                            <?php if ($this->user->branch == 1) { ?>
                                <div class="form-group col-md-2">
                                    <label>المقر</label>
                                    <select name="branch_no" id="dp_branch_no" class="form-control">
                                        <option value="">— الكل —</option>
                                        <?php foreach ($branches as $row): ?>
                                            <option value="<?= $row['NO'] ?>"><?= $row['NAME'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php } else { ?>
                                <input type="hidden" name="branch_no" id="dp_branch_no" value="<?= $this->user->branch ?>">
                            <?php } ?>

                            <div class="form-group col-md-3">
                                <label>الموظف</label>
                                <select name="emp_no" id="dp_emp_no" class="form-control sel2">
                                    <option value="">— الكل —</option>
                                    <?php foreach ((isset($emp_no_cons) && is_array($emp_no_cons) ? $emp_no_cons : []) as $row): ?>
                                        <option value="<?= $row['EMP_NO'] ?>"><?= $row['EMP_NO'] . ' - ' . $row['EMP_NAME'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group col-md-2">
                                <label>حالة التوظيف</label>
                                <select name="is_active" id="dp_is_active" class="form-control">
                                    <option value="">— الكل —</option>
                                    <option value="1" selected>فعّال</option>
                                    <option value="0">متقاعد</option>
                                    <option value="2">متوفى</option>
                                    <option value="4">حسابه مغلق من البنك</option>
                                </select>
                            </div>

                            <div class="form-group col-md-2">
                                <label>الحسابات</label>
                                <select name="has_acc" id="dp_has_acc" class="form-control">
                                    <option value="">— الكل —</option>
                                    <option value="1" selected>عنده حساب نشط</option>
                                    <option value="0">بدون حساب نشط</option>
                                    <option value="2">📱 عنده محفظة</option>
                                    <option value="3">🏦 عنده بنك</option>
                                </select>
                            </div>

                            <div class="form-group col-md-2">
                                <label>المستفيدون</label>
                                <select name="has_benef" id="dp_has_benef" class="form-control">
                                    <option value="">— الكل —</option>
                                    <option value="1">عنده مستفيد</option>
                                    <option value="0">بدون مستفيد</option>
                                </select>
                            </div>

                            <div class="form-group col-md-3">
                                <label>المزود <small class="text-muted" style="font-size:.65rem">(بنك / محفظة)</small></label>
                                <select name="provider_id" id="dp_provider_id" class="form-control sel2"
                                        title="عرض الموظفين الذين عندهم حساب نشط مع هذا المزود فقط">
                                    <option value="">— الكل —</option>
                                    <?php
                                    $providers_for_filter = (isset($providers_list) && is_array($providers_list)) ? $providers_list : [];
                                    // فصل البنوك والمحافظ في optgroup
                                    $banks   = array_filter($providers_for_filter, function($p){ return (int)($p['PROVIDER_TYPE'] ?? 0) === 1; });
                                    $wallets = array_filter($providers_for_filter, function($p){ return (int)($p['PROVIDER_TYPE'] ?? 0) === 2; });
                                    ?>
                                    <?php if (!empty($banks)): ?>
                                        <optgroup label="🏦 البنوك">
                                            <?php foreach ($banks as $p): ?>
                                                <option value="<?= (int)$p['PROVIDER_ID'] ?>"><?= htmlspecialchars($p['PROVIDER_NAME']) ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endif; ?>
                                    <?php if (!empty($wallets)): ?>
                                        <optgroup label="📱 المحافظ">
                                            <?php foreach ($wallets as $p): ?>
                                                <option value="<?= (int)$p['PROVIDER_ID'] ?>"><?= htmlspecialchars($p['PROVIDER_NAME']) ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="form-group col-md-2">
                                <label>الشهر <small class="text-muted" style="font-size:.65rem">(YYYYMM)</small></label>
                                <input type="text" name="the_month" id="txt_the_month" class="form-control"
                                       placeholder="مثال: 202511" maxlength="6"
                                       title="لو تركته فارغاً → يعرض الحالة الحالية. لو حددته → يعرض حالة الموظف في ذلك الشهر من EMPLOYEES_MONTH">
                            </div>
                        </div>
                        <hr>
                        <div class="flex-shrink-0 d-flex flex-wrap align-items-center gap-2">
                            <button type="button" onclick="javascript:search();" class="btn btn-primary">
                                <i class="fa fa-search"></i> استعلام
                            </button>
                            <button type="button" onclick="javascript:clear_form();" class="btn btn-cyan-light">
                                <i class="fa fa-eraser"></i> تفريغ الحقول
                            </button>
                            <button type="button" onclick="javascript:exportExcel();" class="btn btn-success">
                                <i class="fa fa-file-excel-o"></i> تصدير Excel
                            </button>

                            <!-- فاصل بصري -->
                            <span class="d-inline-block" style="width:1px;height:30px;background:#dee2e6;margin:0 4px"></span>

                            <!-- فتح موظف بالرقم يدوياً (للموظفين الجدد غير الظاهرين في القائمة) -->
                            <div class="input-group" style="width:auto;max-width:340px"
                                 title="استخدمه لفتح صفحة موظف غير ظاهر في قائمة 'الموظف' أعلاه (موظف جديد لم يُضف للهيكل الإداري بعد)">
                                <span class="input-group-text" style="font-size:.78rem;background:#fff3cd;border-color:#ffe69c">
                                    <i class="fa fa-user-plus text-warning"></i>
                                </span>
                                <input type="number" id="txt_manual_emp_no" class="form-control"
                                       placeholder="رقم موظف يدوياً (مثال: 1565)" min="1"
                                       onkeydown="if(event.keyCode===13){event.preventDefault();openEmpManual();}">
                                <button type="button" onclick="javascript:openEmpManual();" class="btn btn-warning">
                                    <i class="fa fa-external-link"></i> فتح حسابات الموظف
                                </button>
                            </div>
                        </div>
                        <hr>

                        <!-- بطاقات إحصائيات سريعة (البطاقات قابلة للنقر — تفلتر النتائج) -->
                        <div class="pa-row mb-3" id="paSummary" style="display:none">
                            <div class="pa-card"><div class="c-label"><i class="fa fa-users"></i> إجمالي الموظفين</div><div class="c-val" id="paTotal">—</div></div>
                            <div class="pa-card c-bank"><div class="c-label"><i class="fa fa-bank"></i> حسابات بنكية</div><div class="c-val" id="paBankCount">—</div></div>
                            <div class="pa-card c-wallet"><div class="c-label"><i class="fa fa-mobile"></i> محافظ</div><div class="c-val" id="paWalletCount">—</div></div>
                            <div class="pa-card c-benef pa-card-clickable" onclick="filterByBenef(); return false;" title="انقر لعرض الموظفين الذين عندهم مستفيدون فقط">
                                <div class="c-label"><i class="fa fa-user-circle-o"></i> مستفيدون <i class="fa fa-filter" style="font-size:.65rem;opacity:.6"></i></div>
                                <div class="c-val" id="paBenefCount">—</div>
                            </div>
                        </div>

                        <div id="container">
                            <div class="alert alert-light text-center text-muted py-4" id="paInitMsg">
                                <i class="fa fa-search fa-2x d-block mb-2" style="opacity:.4"></i>
                                اضغط <b>استعلام</b> لعرض النتائج
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php
$scripts = <<<SCRIPT
<script type="text/javascript">

    function reBind(){
        if(typeof initFunctions == 'function') initFunctions();
        initTooltips();
        if(typeof ajax_pager == 'function') ajax_pager(values_search(0));
        paRefreshSummary();
    }

    function LoadingData(){
        if (!$('#page_tb').length) return;
        ajax_pager_data('#page_tb > tbody', values_search(0));
    }

    function initTooltips(){
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });
    }

    $(function(){
        $('.sel2:not("[id^=\'s2\']")').select2();
        initTooltips();

        $('#{$TB_NAME}_form').on('keydown', function(e){
            if(e.keyCode === 13){ e.preventDefault(); search(); }
        });
    });

    function values_search(add_page){
        var values = {
            page: 1,
            branch_no:   $('#dp_branch_no').val()   || '',
            emp_no:      $('#dp_emp_no').val()      || '',
            is_active:   $('#dp_is_active').val()   || '',
            has_acc:     $('#dp_has_acc').val()     || '',
            has_benef:   $('#dp_has_benef').val()   || '',
            provider_id: $('#dp_provider_id').val() || '',
            the_month:   ($('#txt_the_month').val() || '').trim()
        };
        if(add_page == 0) delete values.page;
        return values;
    }

    // اختصار: تفلتر النتائج لتعرض الموظفين الذين عندهم مستفيدون فقط
    function filterByBenef(){
        $('#dp_has_benef').val('1');
        $('#dp_is_active').val(''); // الكل (لأن المستفيدين أحياناً للمتوفين)
        search();
    }

    function search(){
        var values = values_search(1);
        get_data('{$get_page_url}', values, function(data){
            $('#container').html(data);
            $('#paSummary').show();
            reBind();
        }, 'html');
    }

    function loadData(){
        var values = values_search(1);
        get_data('{$get_page_url}', values, function(data){
            $('#container').html(data);
            $('#paSummary').show();
            reBind();
        }, 'html');
    }

    function clear_form(){
        clearForm($('#{$TB_NAME}_form'));
        $('.sel2').select2('val', '');
        $('#dp_is_active').val('1');
        $('#dp_has_acc').val('1');
        $('#dp_has_benef').val('');
        $('#dp_provider_id').val('').trigger('change');
        $('#txt_the_month').val('');
        $('#paSummary').hide();
        $('#container').html('<div class="alert alert-light text-center text-muted py-4" id="paInitMsg"><i class="fa fa-search fa-2x d-block mb-2" style="opacity:.4"></i>اضغط <b>استعلام</b> لعرض النتائج</div>');
    }

    // فتح صفحة موظف بالرقم يدوياً — للموظفين الجدد غير الظاهرين في dropdown
    // (لأن قائمة 'الموظف' تُبنى من الهيكل الإداري للمستخدم الحالي عبر EVA_EMPS_STRUCTURE_CHILD_GET،
    //  والموظف الجديد ممكن ما يكون انضاف للهيكل بعد)
    function openEmpManual(){
        var v = ($('#txt_manual_emp_no').val() || '').toString().trim();
        if(!v){
            if(typeof error_msg === 'function') error_msg('أدخل رقم الموظف أولاً');
            else alert('أدخل رقم الموظف أولاً');
            $('#txt_manual_emp_no').focus();
            return;
        }
        var n = parseInt(v, 10);
        if(!n || n <= 0){
            if(typeof error_msg === 'function') error_msg('رقم الموظف غير صالح');
            else alert('رقم الموظف غير صالح');
            $('#txt_manual_emp_no').focus();
            return;
        }
        // الانتقال لصفحة الموظف — لو ما موجود EMPLOYEE_GET في DATA.EMPLOYEES ستظهر 404 من الكنترولر
        var url = '{$emp_url_base}/' + n;
        window.location.href = url;
    }

    function exportExcel(){
        var values = values_search(0);
        var token = $('input[name="__AntiForgeryToken"]').val() || '';
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '{$export_excel_url}';
        form.style.display = 'none';
        if(token){
            var ti = document.createElement('input');
            ti.type = 'hidden'; ti.name = '__AntiForgeryToken'; ti.value = token;
            form.appendChild(ti);
        }
        for(var k in values){
            if(values[k] !== '' && values[k] !== null && values[k] !== undefined){
                var input = document.createElement('input');
                input.type = 'hidden'; input.name = k; input.value = values[k];
                form.appendChild(input);
            }
        }
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    function paRefreshSummary(){
        var t = $('#paTotals');
        if(!t.length){
            $('#paTotal,#paBankCount,#paWalletCount,#paBenefCount').text('—');
            return;
        }
        var fmt = function(n){ return (parseInt(n)||0).toLocaleString('en-US'); };
        $('#paTotal').text(fmt(t.data('total')));
        $('#paBankCount').text(fmt(t.data('bank')));
        $('#paWalletCount').text(fmt(t.data('wallet')));
        $('#paBenefCount').text(fmt(t.data('benef')));
    }

</script>
SCRIPT;

sec_scripts($scripts);
?>
