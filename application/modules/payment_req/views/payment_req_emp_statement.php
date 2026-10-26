<?php
$MODULE_NAME = 'payment_req';
$TB_NAME     = 'payment_req';
$data_url    = base_url("$MODULE_NAME/$TB_NAME/emp_statement");
$export_url  = base_url("$MODULE_NAME/$TB_NAME/emp_statement_export");
$report_config = isset($report_config) && is_array($report_config) ? $report_config : [];
echo AntiForgeryToken();
require __DIR__ . '/payment_req_report_options.php';
?>
<link rel="stylesheet" href="<?= base_url() ?>assets-n/css/modules/payment_req/emp_statement.css" type="text/css" />
<div class="page-header">
    <div><h1 class="page-title"><?= $title ?></h1></div>
    <div class="ms-auto pageheader-btn">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url("$MODULE_NAME/$TB_NAME") ?>">صرف الرواتب</a></li>
            <li class="breadcrumb-item active"><?= $title ?></li>
        </ol>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?= $title ?></h3>
                <div class="ms-auto d-flex gap-1 flex-wrap align-items-center">
                    <a class="btn btn-info btn-sm text-white" href="<?= base_url("$MODULE_NAME/$TB_NAME") ?>">
                        <i class="fa fa-list"></i> صرف الرواتب
                    </a>
                </div>
            </div>
            <div class="card-body">

                <!-- ═══ شريط الفلتر — نمط index قياسي ═══ -->
                <form id="<?= $TB_NAME ?>_stmt_form" class="no-print" onsubmit="return false;">
                    <div class="row">
                        <div class="form-group col-md-5">
                            <label for="stmt_emp">الموظف</label>
                            <select id="stmt_emp" class="form-control sel2">
                                <option value="">— اختر موظف —</option>
                                <?php foreach ($emp_no_cons as $row): ?>
                                    <option value="<?= $row['EMP_NO'] ?>" <?= (isset($emp_no) && $emp_no == $row['EMP_NO']) ? 'selected' : '' ?>><?= $row['EMP_NO'].' - '.$row['EMP_NAME'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group col-md-2">
                            <label for="stmt_from">من شهر <small class="text-muted" style="font-size:.65rem">(YYYYMM)</small></label>
                            <input type="text" id="stmt_from" class="form-control" placeholder="مثال: 202601" maxlength="6">
                        </div>

                        <div class="form-group col-md-2">
                            <label for="stmt_to">إلى شهر <small class="text-muted" style="font-size:.65rem">(YYYYMM)</small></label>
                            <input type="text" id="stmt_to" class="form-control" placeholder="مثال: 202612" maxlength="6">
                        </div>
                    </div>
                    <hr>
                    <div class="flex-shrink-0">
                        <button type="button" onclick="stmtLoad()" class="btn btn-primary">
                            <i class="fa fa-search"></i> استعلام
                        </button>
                        <button type="button" onclick="stmtClear()" class="btn btn-cyan-light">
                            <i class="fa fa-eraser"></i> تفريغ الحقول
                        </button>
                        <button type="button" id="stmt_export_btn" onclick="stmtExport()" class="btn btn-success" disabled>
                            <i class="fa fa-file-excel-o"></i> تصدير Excel
                        </button>
                        <button type="button" id="stmt_print_btn" onclick="stmtPrintFancy()" class="btn btn-dark" disabled>
                            <i class="fa fa-print"></i> طباعة A4
                        </button>
                    </div>
                    <div id="stmt_export_hint" class="small text-muted mt-2" aria-live="polite"></div>
                    <hr>
                </form>
                <div id="stmt_options" class="no-print"></div>

        <!-- ═══ منطقة العرض (للطباعة) ═══ -->
        <div id="printArea">

            <!-- بطاقة معلومات الموظف -->
            <div id="es_empcard_wrap" style="display:none"></div>

            <div id="es_request_summary_wrap" style="display:none">
                <div class="es-sec">
                    <h6 id="stmt_report_heading">ملخص الطلبات</h6>
                    <p id="stmt_applied_filters" class="small text-muted"></p>
                    <div id="stmt_report_summary"></div>
                </div>
            </div>
            <!-- ملخص الأرصدة -->
            <div id="es_balance_wrap" style="display:none">
                <div class="es-sec">
                    <h6><i class="fa fa-balance-scale"></i> ملخص الأرصدة <span class="es-cnt">كل الفترات</span></h6>
                    <div class="es-bal" id="es_balance"></div>
                </div>
            </div>

            <!-- الحسابات -->
            <div id="es_accounts_wrap" style="display:none">
                <div class="es-sec">
                    <h6><i class="fa fa-credit-card"></i> حسابات الصرف — كل الفترات <span class="es-cnt" id="es_acc_cnt">0</span></h6>
                    <div class="es-accs" id="es_accounts"></div>
                </div>
            </div>

            <!-- الدفعات المحتسبة (محجوز) -->
            <div id="es_pending_wrap" style="display:none">
                <div class="es-sec" style="background:#fffbeb;border-color:#fde68a">
                    <h6><i class="fa fa-clock-o" style="color:#d97706"></i> دفعات محتسبة لم تُنفذ بعد — كل الفترات <span class="es-cnt" id="es_pen_cnt">0</span></h6>
                    <div class="es-pend" id="es_pending"></div>
                </div>
            </div>

            <!-- 🆕 الدفعات والتوزيع -->
            <div id="es_batches_wrap" style="display:none">
                <div class="es-sec">
                    <h6><i class="fa fa-sitemap"></i> الدفعات والتوزيع — كل الفترات <span class="es-cnt" id="es_bat_cnt">0</span>
                    </h6>
                    <div class="table-responsive">
                    <table class="table table-bordered table-sm stmt-tbl mb-0" id="esBatchesTable">
                        <thead>
                        <tr>
                            <th style="width:30px">#</th>
                            <th style="width:100px">رقم الدفعة</th>
                            <th style="width:95px">تاريخ الإنشاء</th>
                            <th style="width:95px">تاريخ الصرف</th>
                            <th style="width:80px">الحالة</th>
                            <th class="text-end" style="width:100px">الإجمالي</th>
                            <th class="text-end" style="width:90px">للبنك</th>
                            <th class="text-end" style="width:90px">للمحفظة</th>
                            <th class="text-end" style="width:90px">للوريث</th>
                            <th class="text-center no-print" style="width:165px">إجراءات</th>
                        </tr>
                        </thead>
                        <tbody id="esBatchesBody"></tbody>
                    </table>
                    </div>
                </div>
            </div>

            <!-- جدول الطلبات -->
            <div id="es_requests_wrap" style="display:none">
                <div class="es-sec">
                    <h6><i class="fa fa-list-alt"></i> <span id="stmt_requests_heading">سجل الطلبات</span> <span class="es-cnt" id="es_req_cnt">0</span></h6>
                    <div class="table-responsive">
                    <table class="table table-bordered table-sm stmt-tbl mb-0" id="stmtTable">
                        <thead></thead>
                        <tbody id="stmtBody"></tbody>
                        <tfoot></tfoot>
                    </table>
                    </div>
                </div>
            </div>

        </div><!-- /printArea -->

        <div id="stmt_loading" class="text-center py-4" style="display:none">
            <i class="fa fa-spinner fa-spin fa-2x text-primary"></i>
            <div style="margin-top:.5rem;color:#64748b;font-size:.85rem">جاري تحميل بيانات الموظف...</div>
        </div>
        <div id="stmt_empty_init" class="text-center py-5 text-muted">
            <i class="fa fa-user-circle fa-3x" style="opacity:.3"></i>
            <div style="margin-top:.5rem">اختر موظف لعرض كشف حسابه</div>
        </div>
        <div id="stmt_empty" class="text-center py-4 text-muted" style="display:none">
            <i class="fa fa-inbox fa-2x" style="opacity:.4"></i>
            <div style="margin-top:.5rem">لا توجد طلبات مطابقة للفترة وخيارات العرض المختارة.</div>
        </div>

            </div><!-- /card-body -->
        </div><!-- /card -->
    </div><!-- /col-lg-12 -->
</div><!-- /row -->

<?php ob_start(); ?>
<script type="text/javascript">
var _stmtOptions = null, _stmtRequest = null, _stmtGeneration = 0, _stmtLastApplied = null, _stmtBusy = false, _stmtClearing = false, _stmtTimer = null;
$(function(){
    $('.sel2:not("[id^=\'s2\']")').select2();
    _stmtOptions = PaymentReportOptions.mount({
        hostId: 'stmt_options',
        config: <?= json_encode($report_config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        storageKey: <?= json_encode(!empty($this->user->id) ? 'payment-report-options:v1:' . (string)$this->user->id . ':statement' : null) ?>,
        onChange: _stmtScheduleLoad
    });
    $('#stmt_emp').on('change', function(){
        if(_stmtClearing) return;
        if($('#stmt_emp').val()) stmtLoad(); else stmtClear();
    });
    $('#stmt_from,#stmt_to').on('input change', _stmtScheduleLoad);
    <?php if (!empty($emp_no)): ?>stmtLoad();<?php endif; ?>
});

function nf(n){ return parseFloat(n||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function escHtml(s){ return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){
    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }

var _statusBadges = {
    0:['مسودة','#fef3c7','#92400e'],
    1:['معتمد','#dbeafe','#1e40af'],
    2:['منفّذ','#d1fae5','#065f46'],
    3:['معتمد جزئي','#e0e7ff','#3730a3'],
    4:['محتسب','#ccfbf1','#0f766e'],
    9:['ملغى','#fee2e2','#991b1b']
};
function _stBadge(st){
    var b = _statusBadges[st] || ['—','#f1f5f9','#475569'];
    return '<span style="background:'+b[1]+';color:'+b[2]+';padding:2px 8px;border-radius:5px;font-size:.7rem;font-weight:700">'+b[0]+'</span>';
}

// خرائط حالة الموظف (4 حالات)
var _empStatusMap = {
    1: {label:'فعّال',     icon:'fa-check-circle', cls:'s1'},
    0: {label:'متقاعد',    icon:'fa-clock-o',      cls:'s0'},
    2: {label:'متوفى',     icon:'fa-times-circle', cls:'s2'},
    4: {label:'حساب مغلق', icon:'fa-ban',          cls:'s4'}
};

function _stmtCurrentParams(){
    return $.extend({emp_no: $('#stmt_emp').val() || '', month_from: ($('#stmt_from').val() || '').trim(), month_to: ($('#stmt_to').val() || '').trim()}, _stmtOptions ? _stmtOptions.getRequestParams() : {});
}

function _stmtHasCurrentSnapshot(){
    return !!_stmtLastApplied && JSON.stringify(_stmtCurrentParams()) === JSON.stringify(_stmtLastApplied);
}

function _stmtSetBusy(busy){
    _stmtBusy = busy;
    if(_stmtOptions) _stmtOptions.setBusy(busy);
    $('#stmt_export_btn,#stmt_print_btn').prop('disabled', busy || !_stmtHasCurrentSnapshot());
    $('#stmt_loading').toggle(busy);
    $('#stmt_export_hint').text(busy ? 'جارٍ التحميل…' : '');
}

function _stmtInvalidate(){
    ++_stmtGeneration;
    if(_stmtRequest && _stmtRequest.abort) _stmtRequest.abort();
    _stmtRequest = null;
    _stmtLastApplied = null;
    $('#es_empcard_wrap,#es_request_summary_wrap,#es_balance_wrap,#es_accounts_wrap,#es_pending_wrap,#es_batches_wrap,#es_requests_wrap').hide();
    _stmtSetBusy(false);
}

function _stmtScheduleLoad(){
    if(_stmtClearing) return;
    if(_stmtTimer) window.clearTimeout(_stmtTimer);
    _stmtTimer = null;
    _stmtInvalidate();
    if(!$('#stmt_emp').val()) return;
    $('#stmt_export_hint').text('جارٍ التحديث…');
    _stmtTimer = window.setTimeout(function(){ stmtLoad(false); }, 250);
}

function stmtLoad(showMessage){
    if(_stmtTimer) window.clearTimeout(_stmtTimer);
    _stmtTimer = null;
    _stmtInvalidate();
    var params = _stmtCurrentParams(), error = '';
    if(!params.emp_no) error = 'اختر الموظف';
    else if([params.month_from, params.month_to].some(function(month){ return month !== '' && (!/^[0-9]{4}(0[1-9]|1[0-2])$/.test(month) || month.substr(0,4) === '0000'); })) error = 'أدخل شهراً صحيحاً بصيغة YYYYMM، أو اترك الفترة فارغة.';
    else if(params.month_from && params.month_to && params.month_from > params.month_to) error = 'شهر البداية يجب ألا يتجاوز شهر النهاية.';
    if(error){ $('#stmt_export_hint').text(error); if(showMessage !== false) danger_msg('تحذير', error); return; }
    var generation = _stmtGeneration;
    _stmtSetBusy(true);
    $('#stmt_empty_init,#stmt_empty').hide();

    _stmtRequest = jQuery.post('<?= $data_url ?>', params, function(resp){
        if(generation !== _stmtGeneration) return;
        if(JSON.stringify(_stmtCurrentParams()) !== JSON.stringify(params)){ _stmtScheduleLoad(); return; }
        var j;
        try { j = (typeof resp === 'string') ? JSON.parse(resp) : resp; }
        catch(e) { _stmtSetBusy(false); danger_msg('خطأ','تعذر قراءة بيانات الكشف. أعد المحاولة.'); return; }
        if(!j || !j.ok || !j.report || !j.report.options || !j.report.summary || !Array.isArray(j.report.columns)){
            _stmtSetBusy(false); danger_msg('خطأ', (j && j.msg) || 'بيانات خيارات التقرير غير متوفرة. أعد تحميل الصفحة.'); return;
        }
        _stmtOptions.applyOptions(j.report.options, {silent:true,persist:false});
        _stmtLastApplied = $.extend({}, params, _stmtOptions.getRequestParams());
        _renderEmpCard(j.emp_info || {});
        _renderBalance(j.summary || {});
        _renderAccounts(j.accounts || []);
        _renderPending(j.pending || []);
        _renderBatches(j.batches || []);
        _renderRequests(j.data || [], j.report);
        var period = (_stmtLastApplied.month_from || 'البداية') + ' — ' + (_stmtLastApplied.month_to || 'حتى الآن');
        $('#stmt_applied_filters').text((j.report.source_label ? j.report.filter_label : 'الفترة: ' + period + ' | ' + (j.report.filter_label || '')) + ' | عرض ' + j.report.summary.count + ' من ' + j.report.source_count + ' سجل في الفترة.');
        $('#es_request_summary_wrap').show();
        _stmtSetBusy(false);
    }, 'json').fail(function(xhr, status){
        if(generation !== _stmtGeneration || status === 'abort') return;
        _stmtSetBusy(false); danger_msg('خطأ','تعذر تحميل الكشف. أعد المحاولة.');
    });
}

// 🆕 رسم جدول الدفعات + توزيع الإجمالي
function _renderBatches(batches){
    // استثناء الدفعات الملغاة (BATCH_STATUS=9) — لم تُصرف فعلاً، فلا تظهر في كشف الموظف
    batches = (batches || []).filter(function(b){ return parseInt(b.BATCH_STATUS) !== 9; });
    if(!batches || batches.length === 0){
        $('#es_batches_wrap').hide();
        return;
    }
    var html = '';
    var stMap = {0:['محتسبة','#ccfbf1','#0f766e'], 2:['منفّذة','#d1fae5','#065f46'], 9:['ملغاة','#fee2e2','#991b1b']};
    batches.forEach(function(b, i){
        var bid    = b.BATCH_ID;
        var bno    = b.BATCH_NO || ('#' + bid);
        var bdate  = b.BATCH_DATE || '—';
        var pdate  = b.PAY_DATE || '—';
        var amt    = parseFloat(b.TOTAL_AMOUNT || 0);
        var bank   = parseFloat(b.BANK_AMT     || 0);
        var wallet = parseFloat(b.WALLET_AMT   || 0);
        var benef  = parseFloat(b.BENEF_AMT    || 0);
        var st     = parseInt(b.BATCH_STATUS);
        var sm     = stMap[st] || [b.STATUS_NAME || '—', '#f1f5f9', '#475569'];

        var emp_no = parseInt($('#stmt_emp').val()) || 0;

        html += '<tr>'
              + '<td class="text-center text-muted">' + (i+1) + '</td>'
              + '<td><a href="<?= base_url($MODULE_NAME.'/'.$TB_NAME.'/batch_detail/') ?>' + bid + '" target="_blank" class="fw-bold" style="color:#1e40af">' + escHtml(bno) + '</a></td>'
              + '<td class="text-center text-muted" style="font-size:.78rem">' + escHtml(bdate) + '</td>'
              + '<td class="text-center text-muted" style="font-size:.78rem">' + escHtml(pdate) + '</td>'
              + '<td class="text-center"><span style="background:'+sm[1]+';color:'+sm[2]+';padding:2px 8px;border-radius:5px;font-size:.7rem;font-weight:700">'+sm[0]+'</span></td>'
              + '<td class="text-end fw-bold" style="color:#0f172a;direction:ltr">' + nf(amt) + '</td>'
              + '<td class="text-end" style="color:#1e40af;direction:ltr">' + (bank   > 0 ? nf(bank)   : '<span class="text-muted">—</span>') + '</td>'
              + '<td class="text-end" style="color:#6d28d9;direction:ltr">' + (wallet > 0 ? nf(wallet) : '<span class="text-muted">—</span>') + '</td>'
              + '<td class="text-end" style="color:#d97706;direction:ltr">' + (benef  > 0 ? nf(benef)  : '<span class="text-muted">—</span>') + '</td>'
              + '<td class="text-center no-print">'
              +    '<button type="button" class="btn btn-sm btn-outline-primary p-0 px-2" '
              +    '   onclick="esBatchBreakdown(' + bid + ',' + emp_no + ')" title="تفاصيل التوزيع">'
              +    '   <i class="fa fa-search-plus"></i> تفاصيل'
              +    '</button> '
              +    '<a href="<?= base_url($MODULE_NAME.'/'.$TB_NAME.'/emp_batch_breakdown_print/') ?>' + bid + '/' + emp_no + '" target="_blank" '
              +    '   class="btn btn-sm btn-outline-secondary p-0 px-2" title="طباعة كشف A4">'
              +    '   <i class="fa fa-print"></i> A4'
              +    '</a>'
              + '</td>'
              + '</tr>';
    });
    $('#esBatchesBody').html(html);
    $('#es_bat_cnt').text(batches.length);
    $('#es_batches_wrap').show();
}

// 🆕 فتح Modal التفاصيل (نفس الموجود في batch_detail) في صفحة بسيطة
function esBatchBreakdown(batchId, empNo){
    if (!batchId || !empNo) return;
    // افتح كشف A4 في تاب جديد (أبسط من بناء modal كامل هنا)
    var url = '<?= base_url($MODULE_NAME.'/'.$TB_NAME.'/emp_batch_breakdown_print/') ?>' + batchId + '/' + empNo;
    window.open(url, '_blank');
}

function _renderEmpCard(info){
    if(!info || !info.EMP_NO){ return; }
    var st = parseInt(info.DISPLAY_STATUS || 1);
    var stMeta = _empStatusMap[st] || _empStatusMap[1];
    var initial = String(info.EMP_NAME || '?').trim().charAt(0);
    var html = '<div class="es-empcard s-' + st + '">' +
        '<div class="es-avatar">' + escHtml(initial) + '</div>' +
        '<div class="es-info">' +
            '<div class="es-name">' +
                '<span class="es-status ' + stMeta.cls + '"><i class="fa ' + stMeta.icon + '"></i> ' + stMeta.label + '</span> ' +
                escHtml(info.EMP_NAME || '') +
                ' <span style="color:#94a3b8;font-size:.85rem">— رقم ' + info.EMP_NO + '</span>' +
            '</div>' +
            '<div class="es-meta">' +
                (info.NAT_ID    ? '<span><i class="fa fa-id-card"></i><b>' + escHtml(info.NAT_ID) + '</b></span>' : '') +
                (info.BRANCH_NAME ? '<span><i class="fa fa-building-o"></i> المقر: <b>' + escHtml(info.BRANCH_NAME) + '</b></span>' : '') +
                (info.HR_BANK_NAME ? '<span><i class="fa fa-bank"></i> بنك HR: <b>' + escHtml(info.HR_BANK_NAME) + '</b></span>' : '') +
                (info.TEL       ? '<span><i class="fa fa-phone"></i> <b>' + escHtml(info.TEL) + '</b></span>' : '') +
            '</div>' +
        '</div></div>';
    $('#es_empcard_wrap').html(html).show();
}

function _renderBalance(s){
    var html = '';
    html += '<div class="es-bal-card"><div class="lbl">الأساسي</div><div class="val">' + nf(s.DUES_BASE) + '</div></div>';
    html += '<div class="es-bal-card c-add"><div class="lbl">+ إضافات</div><div class="val">' + nf(s.DUES_ADD) + '</div></div>';
    html += '<div class="es-bal-card c-ded"><div class="lbl">− خصومات</div><div class="val">' + nf(s.DUES_DED) + '</div></div>';
    html += '<div class="es-bal-card c-rsv"><div class="lbl">محجوز</div><div class="val">' + nf(s.DUES_RESERVED) + '</div></div>';
    html += '<div class="es-bal-card c-paid"><div class="lbl">مصروف سابقاً</div><div class="val">' + nf(s.DUES_PAID) + '</div></div>';
    html += '<div class="es-bal-card c-avl"><div class="lbl">المتاح للصرف</div><div class="val">' + nf(s.DUES_AVAILABLE) + '</div></div>';
    $('#es_balance').html(html);
    $('#es_balance_wrap').show();
}

function _renderAccounts(accs){
    if(!accs || accs.length === 0){
        $('#es_accounts').html('<div class="text-muted text-center w-100 py-2" style="font-size:.8rem"><i class="fa fa-info-circle me-1"></i> لا توجد حسابات صرف مسجلة لهذا الموظف</div>');
        $('#es_acc_cnt').text(0);
        $('#es_accounts_wrap').show();
        return;
    }
    var html = '';
    var splitTypes = {
        1: {label:function(v){return 'نسبة ' + (parseFloat(v)||0) + '%';}, cls:'t1'},
        2: {label:function(v){return 'مبلغ ثابت ' + nf(v);},               cls:'t2'},
        3: {label:function(v){return 'كامل الباقي';},                       cls:'t3'}
    };
    accs.forEach(function(a){
        var isWallet  = parseInt(a.PROVIDER_TYPE) === 2;
        var isDef     = parseInt(a.IS_DEFAULT) === 1;
        var isInact   = parseInt(a.IS_ACTIVE) !== 1;
        var st        = parseInt(a.SPLIT_TYPE) || 3;
        var stMeta    = splitTypes[st] || splitTypes[3];
        var cls       = 'es-acc';
        if(isInact) cls += ' is-inactive';
        else if(isDef) cls += ' is-default';

        html += '<div class="' + cls + '">';
        html += '<div class="ac-head">';
        html += '<span class="ac-prov ' + (isWallet ? 'wallet' : 'bank') + '">' +
                '<i class="fa fa-' + (isWallet ? 'mobile' : 'bank') + '"></i> ' + escHtml(a.PROVIDER_NAME || '') +
                (isDef && !isInact ? ' <span class="ac-tag t-default">⭐ افتراضي</span>' : '') +
                (isInact ? ' <span class="ac-tag t-inact">موقوف</span>' : '') +
                '</span>';
        html += '<span class="ac-split ' + stMeta.cls + '">' + stMeta.label(a.SPLIT_VALUE) + '</span>';
        html += '</div>';

        var accNum = a.ACCOUNT_NO || a.WALLET_NUMBER || '—';
        html += '<div class="ac-line"><i class="fa fa-hashtag"></i> ' + escHtml(accNum);
        if(a.BANK_BRANCH_NAME) html += ' — ' + escHtml(a.BANK_BRANCH_NAME);
        html += '</div>';

        if(a.IBAN && !isWallet) html += '<div class="ac-line">' + escHtml(a.IBAN) + '</div>';

        if(a.OWNER_NAME){
            var ownerLine = a.OWNER_NAME;
            if(a.REL_NAME)    ownerLine += ' (' + escHtml(a.REL_NAME) + ')';
            if(a.OWNER_ID_NO) ownerLine += ' — هوية ' + escHtml(a.OWNER_ID_NO);
            html += '<div class="ac-owner"><i class="fa fa-user-o"></i> ' + ownerLine + '</div>';
        }

        html += '</div>';
    });
    $('#es_accounts').html(html);
    $('#es_acc_cnt').text(accs.length);
    $('#es_accounts_wrap').show();
}

function _renderPending(rows){
    if(!rows || rows.length === 0){ return; }
    var html = '';
    rows.forEach(function(b){
        var bid = b.BATCH_ID;
        var bno = b.BATCH_NO;
        var amt = parseFloat(b.EMP_AMOUNT || 0);
        html += '<div class="es-pend-row">';
        html += '<div><i class="fa fa-warning text-warning me-1"></i> ' +
                '<a href="<?= base_url($MODULE_NAME.'/'.$TB_NAME.'/batch_detail/') ?>' + bid + '" target="_blank" class="pn-no">' + escHtml(bno) + '</a>' +
                ' <span class="text-muted" style="font-size:.72rem">— ' + escHtml(b.BATCH_DATE || '') + '</span></div>';
        html += '<div class="pn-amt">' + nf(amt) + ' ₪</div>';
        html += '</div>';
    });
    $('#es_pending').html(html);
    $('#es_pen_cnt').text(rows.length);
    $('#es_pending_wrap').show();
}

function _renderRequests(rows, report){
    var label = report.measure && report.measure.label ? report.measure.label : (report.summary.amount_label || 'المبلغ');
    $('#stmt_report_heading').text('ملخص الطلبات — ' + label);
    $('#stmt_requests_heading').text('سجل الطلبات — ' + label);
    PaymentReportOptions.renderTable(document.getElementById('stmtTable'), rows, report, {requestUrl:'<?= base_url($MODULE_NAME.'/'.$TB_NAME.'/get/') ?>'});
    PaymentReportOptions.renderSummary(document.getElementById('stmt_report_summary'), report.summary);
    $('#es_req_cnt').text(report.summary.count);
    $('#es_requests_wrap').show();
}

function _stmtSubmitSnapshot(url, extra){
    if(_stmtBusy || !_stmtHasCurrentSnapshot()){ _stmtSetBusy(_stmtBusy); danger_msg('تنبيه','انتظر اكتمال عرض الكشف بالخيارات الحالية أولاً.'); return; }
    var params = $.extend({}, _stmtLastApplied, extra || {});
    var form = document.createElement('form');
    form.method = 'POST'; form.action = url; form.target = '_blank'; form.style.display = 'none';
    params.__AntiForgeryToken = $('input[name="__AntiForgeryToken"]').first().val() || '';
    Object.keys(params).forEach(function(key){
        var input = document.createElement('input'); input.type = 'hidden'; input.name = key; input.value = params[key]; form.appendChild(input);
    });
    document.body.appendChild(form); form.submit(); document.body.removeChild(form);
}

function stmtExport(){ _stmtSubmitSnapshot('<?= $export_url ?>'); }

// تفريغ الحقول
function stmtClear(){
    if(_stmtTimer) window.clearTimeout(_stmtTimer);
    _stmtTimer = null;
    _stmtInvalidate();
    _stmtClearing = true;
    $('#stmt_emp').val('').trigger('change');
    $('#stmt_from').val('');
    $('#stmt_to').val('');
    _stmtClearing = false;
    $('#es_empcard_wrap,#es_request_summary_wrap,#es_balance_wrap,#es_accounts_wrap,#es_pending_wrap,#es_batches_wrap,#es_requests_wrap').hide();
    $('#stmt_empty,#stmt_empty_init,#stmt_loading').hide();
    $('#stmt_empty_init').show();
    _stmtSetBusy(false);
}

// 🆕 فتح صفحة الطباعة الاحترافية A4 في تاب جديد
function stmtPrintFancy(){
    if(_stmtBusy || !_stmtHasCurrentSnapshot()){ _stmtSetBusy(_stmtBusy); danger_msg('تنبيه','انتظر اكتمال عرض الكشف بالخيارات الحالية أولاً.'); return; }
    var emp = _stmtLastApplied.emp_no;
    var mf = _stmtLastApplied.month_from || '0';
    var mt = _stmtLastApplied.month_to || '0';
    var url = '<?= base_url($MODULE_NAME.'/'.$TB_NAME.'/emp_statement_print/') ?>'
            + encodeURIComponent(emp) + '/' + encodeURIComponent(mf) + '/' + encodeURIComponent(mt);
    _stmtSubmitSnapshot(url, {auto:1});
}
</script>
<?php sec_scripts(ob_get_clean()); ?>
