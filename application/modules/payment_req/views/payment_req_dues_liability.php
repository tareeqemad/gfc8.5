<?php
$MODULE_NAME = 'payment_req';
$TB_NAME     = 'payment_req';
$data_url    = base_url("$MODULE_NAME/$TB_NAME/dues_liability_data");
$export_url  = base_url("$MODULE_NAME/$TB_NAME/dues_liability_export");
$stmt_url    = base_url("$MODULE_NAME/$TB_NAME/emp_statement");
$is_hq       = ($this->user->branch == 1);
echo AntiForgeryToken();
?>
<style>
.dl-card{border:1px solid #e2e8f0;border-radius:10px;padding:.6rem 1.1rem;min-width:160px;text-align:center;background:#fff}
.dl-card .lbl{font-size:.72rem;color:#64748b;font-weight:600;margin-bottom:2px}
.dl-card .val{font-size:1.2rem;font-weight:700;color:#1e293b}
.dl-card .cnt{font-size:.68rem;color:#94a3b8}
.dl-sec-title{font-size:.85rem;font-weight:700;color:#334155;margin:1.1rem 0 .5rem;display:flex;align-items:center;gap:.4rem}
.dl-tbl th{background:#f8fafc;font-size:.74rem;white-space:nowrap;vertical-align:middle}
.dl-tbl td{font-size:.78rem;vertical-align:middle;white-space:nowrap}
.dl-tbl tfoot td{font-weight:700;background:#f8fafc}
.dl-pos{color:#059669}
.dl-neg{color:#dc2626}
</style>

<div class="page-header">
    <div><h1 class="page-title"><?= $title ?></h1></div>
    <div class="ms-auto pageheader-btn">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url("$MODULE_NAME/$TB_NAME") ?>">صرف الرواتب</a></li>
            <li class="breadcrumb-item active"><?= $title ?></li>
        </ol>
    </div>
</div>

<div class="row"><div class="col-lg-12"><div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fa fa-balance-scale me-1"></i> <?= $title ?></h3>
    </div>
    <div class="card-body">
        <!-- فلاتر -->
        <div class="row g-2 mb-3">
            <div class="col-md-2">
                <label class="fw-bold" style="font-size:.78rem">الحركة من شهر</label>
                <input type="text" id="dl_month_from" class="form-control" placeholder="YYYYMM"
                       value="<?= date('Ym', mktime(0, 0, 0, date('n') - 11, 1, date('Y'))) ?>">
            </div>
            <div class="col-md-2">
                <label class="fw-bold" style="font-size:.78rem">إلى شهر</label>
                <input type="text" id="dl_month_to" class="form-control" placeholder="YYYYMM" value="<?= date('Ym') ?>">
            </div>
            <?php if ($is_hq): ?>
            <div class="col-md-2">
                <label class="fw-bold" style="font-size:.78rem">المقر</label>
                <select id="dl_branch" class="form-select">
                    <option value="">الكل</option>
                    <?php foreach ($branches as $b): ?><option value="<?= htmlspecialchars((string)$b['NO'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($b['NAME'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-3 d-flex align-items-end gap-1">
                <button class="btn btn-primary" onclick="dlLoad()"><i class="fa fa-search"></i> عرض</button>
                <button class="btn btn-outline-success" onclick="dlExport()" title="تصدير Excel (3 أوراق)"><i class="fa fa-file-excel-o"></i> Excel</button>
            </div>
        </div>

        <!-- بطاقات الصورة الكلية -->
        <div id="dlCards" class="d-flex gap-2 flex-wrap mb-2" style="display:none!important"></div>
        <div id="dlQuality" class="mb-2"></div>

        <!-- الحركة الشهرية -->
        <div id="dlMoveSec" style="display:none">
            <div class="dl-sec-title"><i class="fa fa-line-chart"></i> الحركة الشهرية (افتتاحي + استحقاق + إضافات − تسديدات = ختامي)</div>
            <div class="table-responsive" style="max-height:340px;overflow-y:auto">
                <table class="table table-bordered table-sm dl-tbl" id="dlMoveTable">
                    <thead><tr>
                        <th>الشهر</th><th class="text-end">رصيد افتتاحي</th>
                        <th class="text-end" title="بنود مجموعة المستحقات 48 من الرواتب، ومنها 323 و325">استحقاق</th>
                        <th class="text-end" title="إضافات يدوية بسجل المستحقات">إضافات</th>
                        <th class="text-end" title="تسديدات (الصرف الآلي + الترحيل اليدوي والليجاسي)">تسديدات</th>
                        <th class="text-end">صافي الحركة</th><th class="text-end">رصيد ختامي</th>
                    </tr></thead>
                    <tbody id="dlMoveBody"></tbody>
                </table>
            </div>
        </div>

        <?php if ($is_hq): ?>
        <!-- حسب المقر -->
        <div id="dlBranchSec" style="display:none">
            <div class="dl-sec-title"><i class="fa fa-building-o"></i> توزيع الرصيد حسب المقر</div>
            <div class="table-responsive" style="max-height:280px;overflow-y:auto">
                <table class="table table-bordered table-sm dl-tbl" id="dlBranchTable">
                    <thead><tr>
                        <th>المقر</th><th class="text-center">أصحاب رصيد</th>
                        <th class="text-end">الرصيد الدفتري</th><th class="text-end">محجوز بطلبات</th><th class="text-end">المتاح</th>
                    </tr></thead>
                    <tbody id="dlBranchBody"></tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- الموظفون -->
        <div id="dlEmpSec" style="display:none">
            <div class="dl-sec-title">
                <i class="fa fa-users"></i> أرصدة الموظفين
                <input type="text" id="dlEmpSearch" class="form-control form-control-sm ms-2"
                       placeholder="بحث بالرقم أو الاسم..." style="width:220px;font-size:.78rem" oninput="dlFilterEmps()">
                <span id="dlEmpCount" class="text-muted" style="font-size:.72rem"></span>
            </div>
            <div class="table-responsive" style="max-height:480px;overflow-y:auto">
                <table class="table table-bordered table-sm dl-tbl" id="dlEmpTable">
                    <thead><tr>
                        <th style="width:36px">#</th><th>الموظف</th><th>المقر</th>
                        <th class="text-end" title="بنود مجموعة المستحقات من الرواتب">الأساس</th>
                        <th class="text-end">إضافات</th><th class="text-end">تسديدات</th>
                        <th class="text-end">الرصيد الدفتري</th>
                        <th class="text-end" title="طلبات معتمدة لم تُصرف بعد">محجوز</th>
                        <th class="text-end" title="المتاح للتسديد — نفس رقم شاشة التسديد">المتاح</th>
                        <th style="width:88px"></th>
                    </tr></thead>
                    <tbody id="dlEmpBody"></tbody>
                    <tfoot><tr id="dlEmpFoot"></tr></tfoot>
                </table>
            </div>
        </div>

        <div id="dl_empty" style="display:none" class="text-center py-4 text-muted">لا توجد بيانات</div>
    </div>
</div></div></div>

<!-- مودال كشف الموظف الشهري -->
<div class="modal fade" id="dlEmpModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title"><i class="fa fa-calendar me-1"></i> <span id="dlEmpModalTitle"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="dlEmpModalLoading" class="text-center py-3"><i class="fa fa-spinner fa-spin fa-2x"></i></div>
                <div class="table-responsive" style="display:none" id="dlEmpModalWrap">
                    <table class="table table-bordered table-sm dl-tbl">
                        <thead>
                        <tr>
                            <th rowspan="2">الشهر</th>
                            <th colspan="5" class="text-center" style="background:#eff6ff">حركة رصيد المستحقات</th>
                            <th rowspan="2" class="text-end" style="background:#f0fdf4" title="الرصيد بعد حركة الشهر">الرصيد التراكمي</th>
                            <th colspan="4" class="text-center" style="background:#fefce8" title="من طلبات الصرف بكل أنواعها — للاطلاع، أثرها بالرصيد محسوب ضمن الحركة">جانب الطلبات (كل الأنواع)</th>
                        </tr>
                        <tr>
                            <th class="text-end">افتتاحي</th>
                            <th class="text-end" title="بنود مجموعة المستحقات 48 من الرواتب، ومنها 323 و325">استحق له</th>
                            <th class="text-end">إضافات</th>
                            <th class="text-end">تسديدات</th>
                            <th class="text-end">صافي</th>
                            <th class="text-end" title="نقد قبضه فعلياً بكل أنواع الطلبات">قبض نقداً</th>
                            <th class="text-end" title="مبالغ مسدّدة عبر طلبي تسديد المستحقات ومستحقات الشهر (النوعان 3 و4)">تسديد نوع 3/4</th>
                            <th class="text-end" title="مبلغ المستحقات المسدّد عبر دفعات الراتب (نوع 2)؛ أثره محسوب ضمن التسديدات">تسديد نوع 2</th>
                            <th class="text-end" title="محجوز بطلبات معتمدة لم تُصرف">محجوز</th>
                        </tr>
                        </thead>
                        <tbody id="dlEmpModalBody"></tbody>
                    </table>
                </div>
                <div class="text-end mt-2">
                    <a id="dlEmpStmtLink" href="#" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="fa fa-file-text-o"></i> كشف الموظف الكامل (كل الطلبات)
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function nf(n){ return parseFloat(n||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function fmtMon(m){ m = (m==null?'':String(m)); if(m==='0'||m==='') return 'غير مؤرّخ'; return m.length==6 ? m.substr(4,2)+'/'+m.substr(0,4) : m; }
function amtCls(v){ return parseFloat(v||0) < 0 ? ' dl-neg' : ''; }

var _dlEmps = [];

function _dlParams(extra){
    var p = {
        month_from: $('#dl_month_from').val() || '',
        month_to:   $('#dl_month_to').val() || '',
        branch_no:  $('#dl_branch').length ? ($('#dl_branch').val() || '') : ''
    };
    if(extra) for(var k in extra) p[k] = extra[k];
    return p;
}

function dlLoad(){
    $('#dlCards').hide(); $('#dlMoveSec,#dlEmpSec,#dlBranchSec,#dl_empty').hide();

    // ① البطاقات
    get_dataWithOutLoading('<?= $data_url ?>', _dlParams({part:'summary'}), function(j){
        var s = (j.ok && j.data && j.data[0]) ? j.data[0] : null;
        if(!s) return;
        var html = '';
        html += '<div class="dl-card" style="background:#1e293b;border-color:#1e293b"><div class="lbl" style="color:#cbd5e1">الرصيد الدفتري (الدين الكلي)</div><div class="val" style="color:#fff">'+nf(s.BOOK_BALANCE)+'</div><div class="cnt" style="color:#94a3b8">'+nf(s.TOTAL_BASE)+' أساس + '+nf(s.TOTAL_ADD)+' إضافات − '+nf(s.TOTAL_DED)+' تسديدات</div></div>';
        html += '<div class="dl-card" style="background:#fffbeb;border-color:#fde68a"><div class="lbl" style="color:#92400e">محجوز بطلبات قائمة</div><div class="val" style="color:#92400e">'+nf(s.TOTAL_RESERVED)+'</div><div class="cnt">معتمدة لم تُصرف بعد</div></div>';
        html += '<div class="dl-card" style="background:#f0fdf4;border-color:#bbf7d0"><div class="lbl" style="color:#166534">المتاح للتسديد</div><div class="val" style="color:#059669">'+nf(s.TOTAL_AVAILABLE)+'</div><div class="cnt">نفس أساس شاشة التسديد</div></div>';
        html += '<div class="dl-card"><div class="lbl">أصحاب رصيد</div><div class="val">'+parseInt(s.EMP_COUNT||0).toLocaleString('en-US')+'</div><div class="cnt">موظف برصيد &gt; 0</div></div>';
        $('#dlCards').html(html).css('display','flex');
        if(parseInt(s.NEG_EMP_COUNT||0) > 0){
            $('#dlQuality').html('<div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:8px;padding:.4rem .8rem;font-size:.75rem"><i class="fa fa-exclamation-triangle"></i> جودة بيانات: <b>'+s.NEG_EMP_COUNT+'</b> موظف برصيد دفتري سالب (إجمالي '+nf(s.NEG_TOTAL)+') — تسديدات أكثر من الاستحقاق، تظهر بجدول الموظفين بالأحمر</div>');
        } else { $('#dlQuality').html(''); }
    }, 'json');

    // ② الحركة الشهرية
    get_dataWithOutLoading('<?= $data_url ?>', _dlParams({part:'movement'}), function(j){
        var rows = (j.ok && j.data) ? j.data : [];
        if(rows.length){
            var html = '';
            rows.forEach(function(r){
                var net = parseFloat(r.NET||0);
                html += '<tr><td class="fw-bold">'+fmtMon(r.MON)+'</td>';
                html += '<td class="text-end">'+nf(r.OPENING)+'</td>';
                html += '<td class="text-end dl-pos">'+nf(r.ACCRUED)+'</td>';
                html += '<td class="text-end">'+nf(r.ADDS)+'</td>';
                html += '<td class="text-end dl-neg">'+nf(r.DEDS)+'</td>';
                html += '<td class="text-end fw-bold'+(net<0?' dl-neg':' dl-pos')+'">'+nf(net)+'</td>';
                html += '<td class="text-end fw-bold">'+nf(r.CLOSING)+'</td></tr>';
            });
            $('#dlMoveBody').html(html); $('#dlMoveSec').show();
        }
    }, 'json');

    <?php if ($is_hq): ?>
    // ③ المقرات
    get_dataWithOutLoading('<?= $data_url ?>', _dlParams({part:'branches'}), function(j){
        var rows = (j.ok && j.data) ? j.data : [];
        if(rows.length){
            var html = '';
            rows.forEach(function(r){
                html += '<tr><td>'+(r.BR_NAME||('مقر '+r.BR_NO))+'</td>';
                html += '<td class="text-center">'+parseInt(r.EMP_COUNT||0)+'</td>';
                html += '<td class="text-end fw-bold">'+nf(r.BOOK_BALANCE)+'</td>';
                html += '<td class="text-end">'+nf(r.TOTAL_RESERVED)+'</td>';
                html += '<td class="text-end dl-pos">'+nf(r.TOTAL_AVAILABLE)+'</td></tr>';
            });
            $('#dlBranchBody').html(html); $('#dlBranchSec').show();
        }
    }, 'json');
    <?php endif; ?>

    // ④ الموظفون — الأثقل: مع شاشة التحميل الموحدة
    get_data('<?= $data_url ?>', _dlParams({part:'employees'}), function(j){
        _dlEmps = (j.ok && j.data) ? j.data : [];
        if(_dlEmps.length){ dlRenderEmps(_dlEmps); $('#dlEmpSec').show(); }
        else { $('#dl_empty').show(); }
    }, 'json');
}

function dlRenderEmps(rows){
    var html = '', tB = 0, tR = 0, tA = 0;
    rows.forEach(function(r, i){
        var book = parseFloat(r.BOOK_BALANCE||0);
        tB += book; tR += parseFloat(r.RESERVED||0); tA += parseFloat(r.AVAILABLE||0);
        html += '<tr'+(book<0?' style="background:#fef2f2"':'')+'>';
        html += '<td class="text-muted">'+(i+1)+'</td>';
        html += '<td><span class="fw-bold">'+r.EMP_NO+'</span> - '+(r.EMP_NAME||'')+'</td>';
        html += '<td>'+(r.BRANCH_NAME||'')+'</td>';
        html += '<td class="text-end">'+nf(r.BASE_AMT)+'</td>';
        html += '<td class="text-end">'+nf(r.ADD_AMT)+'</td>';
        html += '<td class="text-end">'+nf(r.DED_AMT)+'</td>';
        html += '<td class="text-end fw-bold'+amtCls(book)+'">'+nf(book)+'</td>';
        html += '<td class="text-end" style="color:#92400e">'+nf(r.RESERVED)+'</td>';
        html += '<td class="text-end fw-bold dl-pos">'+nf(r.AVAILABLE)+'</td>';
        html += '<td class="text-center">';
        html += '<button class="btn btn-sm btn-outline-primary py-0" title="الكشف الشهري" onclick="dlOpenEmp('+r.EMP_NO+', \''+String(r.EMP_NAME||'').replace(/'/g,'').replace(/"/g,'')+'\')"><i class="fa fa-calendar"></i></button> ';
        html += '<a class="btn btn-sm btn-outline-secondary py-0" title="كشف الموظف الكامل" target="_blank" href="<?= $stmt_url ?>/'+r.EMP_NO+'"><i class="fa fa-file-text-o"></i></a>';
        html += '</td></tr>';
    });
    $('#dlEmpBody').html(html);
    $('#dlEmpFoot').html('<td colspan="6" class="text-end">الإجمالي ('+rows.length+' موظف)</td><td class="text-end'+amtCls(tB)+'">'+nf(tB)+'</td><td class="text-end">'+nf(tR)+'</td><td class="text-end dl-pos">'+nf(tA)+'</td><td></td>');
    $('#dlEmpCount').text(rows.length + ' موظف');
}

function dlFilterEmps(){
    var q = ($('#dlEmpSearch').val() || '').toLowerCase().trim();
    if(!q){ dlRenderEmps(_dlEmps); return; }
    dlRenderEmps(_dlEmps.filter(function(r){
        return String(r.EMP_NO).indexOf(q) !== -1 || String(r.EMP_NAME||'').toLowerCase().indexOf(q) !== -1;
    }));
}

function dlOpenEmp(empNo, empName){
    $('#dlEmpModalTitle').text('الكشف الشهري — ' + empNo + ' ' + (empName||''));
    $('#dlEmpStmtLink').attr('href', '<?= $stmt_url ?>/' + empNo);
    $('#dlEmpModalWrap').hide(); $('#dlEmpModalLoading').show();
    var modal = new bootstrap.Modal(document.getElementById('dlEmpModal'));
    modal.show();

    get_dataWithOutLoading('<?= $data_url ?>', {part:'emp_monthly', emp_no: empNo}, function(j){
        $('#dlEmpModalLoading').hide();
        var rows = (j.ok && j.data) ? j.data : [];
        var html = '';
        if(!rows.length){
            html = '<tr><td colspan="11" class="text-center text-muted py-3">لا توجد حركة لهذا الموظف</td></tr>';
        } else {
            rows.forEach(function(r){
                html += '<tr><td class="fw-bold">'+fmtMon(r.MON)+'</td>';
                html += '<td class="text-end text-muted">'+nf(r.OPENING)+'</td>';
                html += '<td class="text-end dl-pos">'+nf(r.ACCRUED)+'</td>';
                html += '<td class="text-end">'+nf(r.ADDS)+'</td>';
                html += '<td class="text-end dl-neg">'+nf(r.DEDS)+'</td>';
                var net = parseFloat(r.NET||0);
                html += '<td class="text-end fw-bold'+(net<0?' dl-neg':' dl-pos')+'">'+nf(net)+'</td>';
                html += '<td class="text-end fw-bold" style="background:#f0fdf4">'+nf(r.CLOSING)+'</td>';
                html += '<td class="text-end">'+nf(r.CASH_PAID)+'</td>';
                html += '<td class="text-end">'+nf(r.SETTLED_34)+'</td>';
                html += '<td class="text-end" style="color:#6b21a8">'+nf(r.CARRIED_T2)+'</td>';
                html += '<td class="text-end" style="color:#92400e">'+nf(r.PENDING_RSV)+'</td></tr>';
            });
        }
        $('#dlEmpModalBody').html(html); $('#dlEmpModalWrap').show();
    }, 'json');
}

function dlExport(){
    var p = _dlParams();
    window.location = '<?= $export_url ?>?' + $.param(p);
}

// تحميل تلقائي بعد اكتمال الصفحة — jQuery يُحمَّل بآخر القالب،
// لذلك لا نستخدم $() هنا (كانت تكسر بـ "$ is not defined")
window.addEventListener('load', function(){ dlLoad(); });
</script>
