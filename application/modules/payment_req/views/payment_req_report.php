<?php
$MODULE_NAME = 'payment_req';
$TB_NAME = 'payment_req';
$data_url = base_url("$MODULE_NAME/$TB_NAME/report_monthly_data");
$export_url = base_url("$MODULE_NAME/$TB_NAME/report_monthly_export");
// POST through the existing report route preserves its menu permission.
$print_url = base_url("$MODULE_NAME/$TB_NAME/report_monthly");
$request_url = base_url("$MODULE_NAME/$TB_NAME/get/");
$report_user_id = isset($this->user->id) ? (string)$this->user->id : '';
$report_config = isset($report_config) && is_array($report_config) ? $report_config : [];
$rpt_json_flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
echo AntiForgeryToken();
require __DIR__ . '/payment_req_report_options.php';
?>
<style>
.payment-report-page{direction:rtl}.payment-report-page .rpt-base-filters{display:grid;grid-template-columns:repeat(5,minmax(125px,1fr));gap:12px;margin-bottom:14px}
.payment-report-page .rpt-base-filters label{display:block;font-size:.8rem;font-weight:700;margin-bottom:5px;color:#243b53}
.payment-report-page .rpt-actions{display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:16px}
.payment-report-page .rpt-table-scroll{overflow:auto;max-height:68vh}.payment-report-page .rpt-table-scroll thead th{position:sticky;top:0;z-index:1}
.payment-report-page .rpt-status-message{padding:10px 12px;border-radius:7px;background:#eef4f8;color:#243b53;margin-bottom:12px;font-size:.82rem}
.payment-report-page .rpt-status-message[data-error="true"]{background:#fff1e0;color:#8a4b08}
.payment-report-page .rpt-context{color:#526579;font-size:.78rem;line-height:1.7;margin-bottom:12px}
@media(max-width:900px){.payment-report-page .rpt-base-filters{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
<div class="page-header">
    <div><h1 class="page-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1></div>
    <div class="ms-auto pageheader-btn"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= base_url("$MODULE_NAME/$TB_NAME") ?>">صرف الرواتب</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></li>
    </ol></div>
</div>
<div class="payment-report-page"><div class="card">
    <div class="card-header"><h2 class="card-title" id="rpt_heading">تقرير الصرف الشهري</h2></div>
    <div class="card-body">
        <form id="rpt_filter_form">
            <div class="rpt-base-filters">
                <div><label for="rpt_month_from">من شهر</label><input type="text" id="rpt_month_from" class="form-control" inputmode="numeric" maxlength="6" placeholder="YYYYMM" value="<?= date('Ym') ?>"></div>
                <div><label for="rpt_month_to">إلى شهر</label><input type="text" id="rpt_month_to" class="form-control" inputmode="numeric" maxlength="6" placeholder="نفس شهر البداية"></div>
                <?php if ($this->user->branch == 1): ?>
                <div><label for="rpt_branch">المقر</label><select id="rpt_branch" class="form-select"><option value="">كل المقار</option>
                    <?php foreach ($branches as $b): ?><option value="<?= htmlspecialchars((string)$b['NO'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($b['NAME'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                </select></div>
                <?php endif; ?>
                <div><label for="rpt_req_type">نوع الطلب</label><select id="rpt_req_type" class="form-select"><option value="">كل الأنواع</option>
                    <?php foreach ($req_type_cons as $r): ?><option value="<?= htmlspecialchars((string)$r['CON_NO'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($r['CON_NAME'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                </select></div>
                <div><label for="rpt_status">حالة الطلب</label><select id="rpt_status" class="form-select"><option value="">كل حالات الطلب</option>
                    <?php foreach ($status_cons as $s): ?><option value="<?= htmlspecialchars((string)$s['CON_NO'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($s['CON_NAME'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                </select></div>
            </div>
            <div id="rpt_options"></div>
            <div class="rpt-actions">
                <button type="submit" id="rpt_load" class="btn btn-primary"><i class="fa fa-refresh" aria-hidden="true"></i> تحديث النتائج</button>
                <button type="button" id="rpt_print" class="btn btn-outline-secondary" disabled><i class="fa fa-print" aria-hidden="true"></i> طباعة التقرير</button>
                <button type="button" id="rpt_export" class="btn btn-outline-success" disabled><i class="fa fa-file-excel-o" aria-hidden="true"></i> تصدير Excel</button>
            </div>
        </form>
        <div id="rpt_message" class="rpt-status-message" role="status" aria-live="polite">جارٍ تجهيز التقرير.</div>
        <div id="rpt_filter_label" class="rpt-context"></div>
        <div id="rpt_summary" class="pro-summary" hidden></div>
        <div id="rpt_results" class="rpt-table-scroll" aria-busy="false">
            <table class="pro-report-table" id="rptTable" aria-label="تقرير الصرف الشهري" hidden></table>
        </div>
    </div>
</div></div>
<?php ob_start(); ?>
<script>
(function($){
    'use strict';
    var config = <?= json_encode($report_config, $rpt_json_flags) ?>;
    var userId = <?= json_encode($report_user_id, $rpt_json_flags) ?>;
    var dataUrl = <?= json_encode($data_url, $rpt_json_flags) ?>;
    var exportUrl = <?= json_encode($export_url, $rpt_json_flags) ?>;
    var printUrl = <?= json_encode($print_url, $rpt_json_flags) ?>;
    var requestUrl = <?= json_encode($request_url, $rpt_json_flags) ?>;
    var options, pending = null, timer = null, revision = 0, applied = null, loading = false;

    function baseParams(){
        return {month_from:$.trim($('#rpt_month_from').val() || ''),month_to:$.trim($('#rpt_month_to').val() || $('#rpt_month_from').val() || ''),branch_no:$('#rpt_branch').val() || '',req_type:$('#rpt_req_type').val() || '',status:$('#rpt_status').val() || ''};
    }
    function currentParams(){ return $.extend({},baseParams(),options.getRequestParams()); }
    function signature(params){ return JSON.stringify(params); }
    function updateActions(){
        var canDownload = !loading && applied && signature(currentParams()) === applied.signature;
        $('#rpt_print,#rpt_export').prop('disabled',!canDownload);
        $('#rpt_results').attr('aria-busy',loading ? 'true' : 'false');
        options.setBusy(loading);
    }
    function message(text,error){ $('#rpt_message').text(text).attr('data-error',error ? 'true' : 'false').show(); }
    function invalidate(){
        revision++;
        if(pending){ pending.abort(); pending = null; }
        applied = null; loading = false;
        document.getElementById('rptTable').hidden = true;
        document.getElementById('rpt_summary').hidden = true;
        $('#rpt_heading').text('تقرير الصرف الشهري');
        $('#rpt_filter_label').text('');
        updateActions();
    }
    function scheduleLoad(){
        if(timer) window.clearTimeout(timer);
        invalidate();
        message('جارٍ التحديث…',false);
        timer = window.setTimeout(load,250);
    }
    function load(){
        if(timer){ window.clearTimeout(timer); timer = null; }
        invalidate();
        var params = currentParams();
        if(!/^\d{4}(0[1-9]|1[0-2])$/.test(params.month_from) || !/^\d{4}(0[1-9]|1[0-2])$/.test(params.month_to)){
            message('أدخل شهرًا صحيحًا بصيغة YYYYMM.',true);
            return;
        }
        if(params.month_from > params.month_to){ message('شهر البداية يجب ألا يتجاوز شهر النهاية.',true); return; }
        var requestRevision = revision;
        loading = true; updateActions();
        message('جارٍ تحميل النتائج…',false);
        document.getElementById('rptTable').hidden = true;
        document.getElementById('rpt_summary').hidden = true;
        $('#rpt_filter_label').text('');
        var requestData = $.extend({},params);
        var token = $('input[name="__AntiForgeryToken"]').first().val();
        if(token) requestData.__AntiForgeryToken = token;
        pending = $.ajax({url:dataUrl,type:'POST',dataType:'json',data:requestData});
        pending.done(function(response){
            if(requestRevision !== revision) return;
            if(!response || !response.ok || !Array.isArray(response.data) || !response.report || !response.report.options || !Array.isArray(response.report.columns)){
                message(response && response.msg ? response.msg : 'تعذر تحميل التقرير. أعد المحاولة.',true);
                return;
            }
            options.applyOptions(response.report.options,{silent:true,persist:true});
            var appliedParams = $.extend({},params,options.getRequestParams());
            applied = {params:appliedParams,signature:signature(appliedParams)};
            PaymentReportOptions.renderTable(document.getElementById('rptTable'),response.data,response.report,{requestUrl:requestUrl});
            PaymentReportOptions.renderSummary(document.getElementById('rpt_summary'),response.report.summary);
            document.getElementById('rptTable').hidden = false;
            document.getElementById('rpt_summary').hidden = false;
            var measureLabel = response.report.measure && response.report.measure.label;
            $('#rpt_heading').text('تقرير الصرف الشهري'+(measureLabel ? ' · '+measureLabel : ''));
            $('#rpt_filter_label').text(response.report.filter_label || '');
            var count = response.report.summary && response.report.summary.count != null ? response.report.summary.count : response.data.length;
            message(count+' سجل',false);
        }).fail(function(xhr,status){
            if(requestRevision !== revision || status === 'abort') return;
            message('تعذر الاتصال لتحميل التقرير. أعد المحاولة.',true);
        }).always(function(){
            if(requestRevision !== revision) return;
            pending = null; loading = false; updateActions();
        });
    }
    function submitApplied(url,extra){
        if(loading || !applied || signature(currentParams()) !== applied.signature){
            message('انتظر اكتمال تحديث النتائج قبل الطباعة أو التصدير.',true);
            updateActions(); return;
        }
        var params = $.extend({},applied.params,extra || {}), form = document.createElement('form');
        form.method = 'POST'; form.action = url; form.target = '_blank'; form.setAttribute('rel','noopener'); form.style.display = 'none';
        var token = $('input[name="__AntiForgeryToken"]').first().val();
        if(token) params.__AntiForgeryToken = token;
        Object.keys(params).forEach(function(key){ var input = document.createElement('input'); input.type = 'hidden'; input.name = key; input.value = params[key] == null ? '' : String(params[key]); form.appendChild(input); });
        document.body.appendChild(form); form.submit(); document.body.removeChild(form);
    }
    $(function(){
        options = PaymentReportOptions.mount({hostId:'rpt_options',config:config,storageKey:userId ? 'payment-report-options:v1:'+userId+':general' : null,onChange:scheduleLoad});
        $('#rpt_filter_form').on('submit',function(event){ event.preventDefault(); load(); });
        $('#rpt_month_from,#rpt_month_to').on('input change',scheduleLoad);
        $('#rpt_branch,#rpt_req_type,#rpt_status').on('change',scheduleLoad);
        $('#rpt_print').on('click',function(){ submitApplied(printUrl,{report_format:'print'}); });
        $('#rpt_export').on('click',function(){ submitApplied(exportUrl); });
        load();
    });
})(jQuery);
</script>
<?php sec_scripts(ob_get_clean()); ?>
