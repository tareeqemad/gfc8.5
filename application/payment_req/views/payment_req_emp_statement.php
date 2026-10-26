<?php
$MODULE_NAME = 'payment_req';
$TB_NAME     = 'payment_req';
$data_url    = base_url("$MODULE_NAME/$TB_NAME/emp_statement");
$export_url  = base_url("$MODULE_NAME/$TB_NAME/emp_statement_export");
echo AntiForgeryToken();
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
                            <label>الموظف</label>
                            <select id="stmt_emp" class="form-control sel2">
                                <option value="">— اختر موظف —</option>
                                <?php foreach ($emp_no_cons as $row): ?>
                                    <option value="<?= $row['EMP_NO'] ?>" <?= (isset($emp_no) && $emp_no == $row['EMP_NO']) ? 'selected' : '' ?>><?= $row['EMP_NO'].' - '.$row['EMP_NAME'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group col-md-2">
                            <label>من شهر <small class="text-muted" style="font-size:.65rem">(YYYYMM)</small></label>
                            <input type="text" id="stmt_from" class="form-control" placeholder="مثال: 202601" maxlength="6">
                        </div>

                        <div class="form-group col-md-2">
                            <label>إلى شهر <small class="text-muted" style="font-size:.65rem">(YYYYMM)</small></label>
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
                        <button type="button" onclick="stmtExport()" class="btn btn-success">
                            <i class="fa fa-file-excel-o"></i> تصدير Excel
                        </button>
                        <button type="button" onclick="stmtPrintFancy()" class="btn btn-dark">
                            <i class="fa fa-print"></i> طباعة A4
                        </button>
                    </div>
                    <hr>
                </form>

        <!-- ═══ منطقة العرض (للطباعة) ═══ -->
        <div id="printArea">

            <!-- بطاقة معلومات الموظف -->
            <div id="es_empcard_wrap" style="display:none"></div>

            <!-- ملخص الأرصدة -->
            <div id="es_balance_wrap" style="display:none">
                <div class="es-sec">
                    <h6><i class="fa fa-balance-scale"></i> ملخص الأرصدة <span class="es-cnt">رصيد المستحقات</span></h6>
                    <div class="es-bal" id="es_balance"></div>
                </div>
            </div>

            <!-- الحسابات -->
            <div id="es_accounts_wrap" style="display:none">
                <div class="es-sec">
                    <h6><i class="fa fa-credit-card"></i> حسابات الصرف <span class="es-cnt" id="es_acc_cnt">0</span></h6>
                    <div class="es-accs" id="es_accounts"></div>
                </div>
            </div>

            <!-- الدفعات المحتسبة (محجوز) -->
            <div id="es_pending_wrap" style="display:none">
                <div class="es-sec" style="background:#fffbeb;border-color:#fde68a">
                    <h6><i class="fa fa-clock-o" style="color:#d97706"></i> دفعات محتسبة لم تُنفذ بعد <span class="es-cnt" id="es_pen_cnt">0</span></h6>
                    <div class="es-pend" id="es_pending"></div>
                </div>
            </div>

            <!-- 🆕 الدفعات والتوزيع -->
            <div id="es_batches_wrap" style="display:none">
                <div class="es-sec">
                    <h6><i class="fa fa-sitemap"></i> الدفعات والتوزيع <span class="es-cnt" id="es_bat_cnt">0</span>
                        <small class="text-muted" style="font-weight:400;font-size:.72rem;margin-right:6px">من قيمة كل دفعة: كم للبنك / المحفظة / الوريث</small>
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
                    <h6><i class="fa fa-list-alt"></i> سجل الطلبات والدفعات <span class="es-cnt" id="es_req_cnt">0</span></h6>
                    <div class="table-responsive">
                    <table class="table table-bordered table-sm stmt-tbl mb-0" id="stmtTable">
                        <thead>
                        <tr>
                            <th style="width:30px">#</th>
                            <th style="width:90px">رقم الطلب</th>
                            <th style="width:75px">الشهر</th>
                            <th>نوع الطلب</th>
                            <th class="text-end" style="width:100px">المبلغ</th>
                            <th>بند الصرف</th>
                            <th style="width:90px">الحالة</th>
                            <th style="width:90px">تاريخ الصرف</th>
                            <th>ملاحظة</th>
                        </tr>
                        </thead>
                        <tbody id="stmtBody"></tbody>
                        <tfoot>
                        <tr style="background:#1e293b;color:#fff;font-weight:800">
                            <td colspan="4" class="text-end">الإجمالي</td>
                            <td class="text-end" id="stmtTotal" style="direction:ltr">0</td>
                            <td colspan="4"></td>
                        </tr>
                        </tfoot>
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
            <div style="margin-top:.5rem">لا توجد طلبات لهذا الموظف</div>
        </div>

            </div><!-- /card-body -->
        </div><!-- /card -->
    </div><!-- /col-lg-12 -->
</div><!-- /row -->

<?php ob_start(); ?>
<script type="text/javascript">
$(function(){
    $('.sel2:not("[id^=\'s2\']")').select2();
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

function stmtLoad(){
    var emp = $('#stmt_emp').val();
    if(!emp){ danger_msg('تحذير','اختر الموظف'); return; }

    $('#stmt_loading').show();
    $('#stmt_empty_init,#stmt_empty').hide();
    $('#es_empcard_wrap,#es_balance_wrap,#es_accounts_wrap,#es_pending_wrap,#es_batches_wrap,#es_requests_wrap').hide();

    jQuery.post('<?= $data_url ?>', {
        emp_no: emp,
        month_from: $('#stmt_from').val()||'',
        month_to:   $('#stmt_to').val()||''
    }, function(resp){
        $('#stmt_loading').hide();
        var j = (typeof resp === 'string') ? JSON.parse(resp) : resp;
        if(!j.ok){ danger_msg('خطأ', j.msg); return; }

        // 1) بطاقة الموظف
        _renderEmpCard(j.emp_info || {});

        // 2) ملخص الأرصدة
        _renderBalance(j.summary || {});

        // 3) الحسابات
        _renderAccounts(j.accounts || []);

        // 4) الدفعات المحتسبة
        _renderPending(j.pending || []);

        // 5) 🆕 الدفعات والتوزيع (كل الدفعات + توزيع الإجمالي على بنك/محفظة/وريث)
        _renderBatches(j.batches || []);

        // 6) الطلبات
        _renderRequests(j.data || []);
    }, 'json');
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

function _renderRequests(rows){
    if(!rows || rows.length === 0){
        $('#stmt_empty').show();
        $('#es_requests_wrap').hide();
        return;
    }
    var html = '', total = 0;
    rows.forEach(function(d, i){
        var amt = parseFloat(d.REQ_AMOUNT || 0);
        total += amt;
        var thm = (d.THE_MONTH || '').toString();
        if(thm.length == 6) thm = thm.substr(4,2) + '/' + thm.substr(0,4);

        html += '<tr>';
        html += '<td class="text-center text-muted">' + (i+1) + '</td>';
        html += '<td><a href="<?= base_url($MODULE_NAME.'/'.$TB_NAME.'/get/') ?>' + d.REQ_ID + '" target="_blank" class="fw-bold" style="color:#1e40af">' + escHtml(d.REQ_NO || '') + '</a></td>';
        html += '<td>' + escHtml(thm) + '</td>';
        html += '<td>' + escHtml(d.REQ_TYPE_NAME || '') + '</td>';
        html += '<td class="text-end amt">' + nf(amt) + '</td>';
        html += '<td style="font-size:.74rem">' + escHtml(d.PAY_TYPE_NAME || '') + '</td>';
        html += '<td>' + _stBadge(parseInt(d.DETAIL_STATUS || 0)) + '</td>';
        html += '<td style="font-size:.74rem">' + escHtml(d.PAY_DATE || '') + '</td>';
        html += '<td class="text-muted" style="font-size:.74rem">' + escHtml(d.NOTE || '') + '</td>';
        html += '</tr>';
    });
    $('#stmtBody').html(html);
    $('#stmtTotal').text(nf(total));
    $('#es_req_cnt').text(rows.length);
    $('#es_requests_wrap').show();
}

function stmtExport(){
    var emp = $('#stmt_emp').val();
    if(!emp){ danger_msg('تحذير','اختر الموظف'); return; }
    window.location = '<?= $export_url ?>?emp_no=' + emp +
                      '&month_from=' + ($('#stmt_from').val()||'') +
                      '&month_to=' + ($('#stmt_to').val()||'');
}

// تفريغ الحقول
function stmtClear(){
    $('#stmt_emp').val('').trigger('change');
    $('#stmt_from').val('');
    $('#stmt_to').val('');
    $('#es_empcard_wrap,#es_balance_wrap,#es_accounts_wrap,#es_pending_wrap,#es_batches_wrap,#es_requests_wrap').hide();
    $('#stmt_empty,#stmt_empty_init,#stmt_loading').hide();
    $('#stmt_empty_init').show();
}

// 🆕 فتح صفحة الطباعة الاحترافية A4 في تاب جديد
function stmtPrintFancy(){
    var emp = $('#stmt_emp').val();
    if(!emp){ danger_msg('تحذير','اختر الموظف أولاً'); return; }
    var mf = ($('#stmt_from').val() || '0').trim() || '0';
    var mt = ($('#stmt_to').val()   || '0').trim() || '0';
    var url = '<?= base_url($MODULE_NAME.'/'.$TB_NAME.'/emp_statement_print/') ?>'
            + emp + '/' + mf + '/' + mt + '?auto=1';
    window.open(url, '_blank');
}
</script>
<?php sec_scripts(ob_get_clean()); ?>
