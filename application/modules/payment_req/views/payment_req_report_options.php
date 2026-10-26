<style>
.pro-options{direction:rtl;background:#f8fafc;border:1px solid #dbe3ed;border-radius:12px;padding:16px;margin:0 0 18px;color:#172b4d}
.pro-options legend{float:none;width:auto;font-size:.95rem;font-weight:700;margin:0 0 10px;padding:0}
.pro-options .pro-toolbar{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-bottom:12px}
.pro-options .pro-button{border:1px solid #b8c6d8;background:#fff;color:#243b53;border-radius:7px;padding:6px 12px;font:inherit;font-size:.82rem;cursor:pointer}
.pro-options .pro-button[aria-pressed="true"]{background:#174a78;border-color:#174a78;color:#fff}
.pro-options .pro-button:focus-visible,.pro-options input:focus-visible,.pro-options select:focus-visible,.pro-options summary:focus-visible{outline:3px solid #3984bb;outline-offset:3px}
.pro-options .pro-reset{margin-inline-start:auto}
.pro-options .pro-filters{display:grid;grid-template-columns:repeat(4,minmax(145px,1fr));gap:12px}
.pro-options .pro-field label{display:block;font-size:.8rem;font-weight:700;margin-bottom:5px}
.pro-options .pro-field select{width:100%;min-height:36px;background:#fff;border:1px solid #b8c6d8;border-radius:6px;padding:6px;color:#172b4d;font:inherit;font-size:.82rem}
.pro-options details{border-top:1px solid #dbe3ed;margin-top:14px;padding-top:12px}
.pro-options summary{cursor:pointer;font-size:.83rem;font-weight:700;padding:3px 0}
.pro-options .pro-column-tools{display:flex;align-items:center;gap:10px;margin:10px 0;font-size:.77rem;color:#526579}
.pro-options .pro-columns{display:grid;grid-template-columns:repeat(auto-fit,minmax(145px,1fr));gap:8px 14px}
.pro-options .pro-column{display:flex;align-items:flex-start;gap:7px;font-size:.82rem;font-weight:400;cursor:pointer;margin:0}
.pro-options .pro-column input{margin-top:3px;accent-color:#174a78;flex-shrink:0}
.pro-options .pro-announcement{font-size:.78rem;color:#8a4b08;min-height:1.2em;margin-top:8px}
.pro-summary{direction:rtl;margin-bottom:16px}
.pro-summary .pro-summary-cards{display:grid;grid-template-columns:minmax(200px,2fr) minmax(140px,1fr);gap:10px}
.pro-summary .pro-summary-card{border:1px solid #dbe3ed;border-radius:10px;padding:12px 14px;background:#fff}
.pro-summary .pro-summary-card.pro-primary{background:#eef6fc;border-color:#bad5e9}
.pro-summary .pro-card-label{font-size:.8rem;color:#435b73;margin-bottom:5px}
.pro-summary .pro-card-value{font-size:1.35rem;font-weight:700;color:#173d60;font-variant-numeric:tabular-nums;line-height:1.5}
.pro-summary .pro-status-totals{display:flex;flex-wrap:wrap;gap:7px;margin-top:10px}
.pro-summary .pro-status-total{border:1px solid #dbe3ed;border-radius:7px;padding:6px 9px;background:#fff;font-size:.78rem}
.pro-summary .pro-unknown{color:#945900}
.pro-report-table{width:100%;font-size:.82rem;border-collapse:collapse;direction:rtl}
.pro-report-table th,.pro-report-table td{padding:9px 10px;border:1px solid #dbe3ed;vertical-align:middle}
.pro-report-table thead th{background:#173d60;color:#fff;white-space:nowrap;font-weight:600}
.pro-report-table tbody tr:nth-child(even){background:#f8fafc}
.pro-report-table .pro-money{font-variant-numeric:tabular-nums;white-space:nowrap;text-align:end}
.pro-report-table .pro-money bdi{direction:ltr}
.pro-report-table .pro-empty{padding:26px;text-align:center;color:#526579;background:#fff}
.pro-report-table tfoot{background:#edf3f8;font-weight:700}
.pro-report-table .pro-total-label{text-align:start;color:#243b53}
.pro-report-table .pro-nowrap{white-space:nowrap}
.pro-status{display:inline-block;border-radius:5px;padding:3px 7px;font-size:.74rem;white-space:nowrap;background:#eef2f6;color:#334155}
.pro-status-0{background:#fff1cf;color:#7b4d00}.pro-status-1{background:#e0efff;color:#164b81}.pro-status-2{background:#dcf5e7;color:#17633c}
.pro-status-3{background:#eee7ff;color:#573a8c}.pro-status-4{background:#d8f5f1;color:#136359}.pro-status-9{background:#ffe4e4;color:#8b2626}
@media(max-width:1000px){.pro-options .pro-filters{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:650px){.pro-options .pro-filters,.pro-summary .pro-summary-cards{grid-template-columns:1fr}.pro-options .pro-reset{margin-inline-start:0}.pro-options{padding:12px}.pro-options .pro-columns{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
<script>
(function(window, document){
    'use strict';
    if(window.PaymentReportOptions) return;
    var nextId = 0;
    var statusNames = {'0':'مسودة','1':'معتمد','2':'منفّذ للصرف','3':'معتمد جزئياً','4':'محتسب','9':'ملغى'};
    function escapeHtml(value){
        return String(value == null ? '' : value).replace(/[&<>"']/g, function(c){
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    }
    function numberKnown(value){ return value !== null && value !== undefined && String(value).trim() !== '' && isFinite(Number(value)); }
    function money(value){ return numberKnown(value) ? Number(value).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}) : 'غير متوفر'; }
    function sameColumns(a,b){ return a.length === b.length && a.every(function(id,i){ return id === b[i]; }); }
    function resolveHost(host){ return typeof host === 'string' ? document.getElementById(host.replace(/^#/,'')) : host; }
    function statusBadge(code, label){
        var key = String(code == null ? '' : code);
        return '<span class="pro-status'+(Object.prototype.hasOwnProperty.call(statusNames,key) ? ' pro-status-'+key : '')+'">'+escapeHtml(label || statusNames[key] || 'غير متوفر')+'</span>';
    }
    function cellHtml(def, row, index, options){
        var value = row[def.field], text;
        if(def.id === 'seq' || def.kind === 'sequence') return String(index+1);
        if(def.kind === 'money') return '<bdi>'+escapeHtml(money(value))+'</bdi>';
        if(def.kind === 'month'){
            text = String(value == null ? '' : value);
            return escapeHtml(/^\d{6}$/.test(text) ? text.slice(4)+'/'+text.slice(0,4) : (text || '—'));
        }
        if(def.kind === 'status') return statusBadge(row[def.id === 'request_status' ? 'STATUS' : 'DETAIL_STATUS'],value);
        text = escapeHtml(value === null || value === undefined || value === '' ? '—' : value);
        if(def.id === 'req_no' && options.requestUrl && /^\d+$/.test(String(row.REQ_ID || ''))){
            return '<a href="'+escapeHtml(options.requestUrl+encodeURIComponent(row.REQ_ID))+'" target="_blank" rel="noopener">'+text+'</a>';
        }
        return text;
    }
    function tableHtml(rows, report, options){
        rows = Array.isArray(rows) ? rows : []; report = report || {}; options = options || {};
        var columns = Array.isArray(report.columns) ? report.columns : [], summary = report.summary || {};
        if(!columns.length) return '<tbody><tr><td class="pro-empty">لا توجد أعمدة متاحة للعرض.</td></tr></tbody>';
        var html = '<thead><tr>';
        columns.forEach(function(def){ html += '<th scope="col"'+(def.kind === 'money' ? ' class="pro-money"' : '')+'>'+escapeHtml(def.label)+'</th>'; });
        html += '</tr></thead><tbody>';
        rows.forEach(function(row,index){
            html += '<tr>';
            columns.forEach(function(def){ html += '<td'+(def.kind === 'money' ? ' class="pro-money"' : '')+'>'+cellHtml(def,row,index,options)+'</td>'; });
            html += '</tr>';
        });
        if(!rows.length) html += '<tr><td colspan="'+columns.length+'" class="pro-empty">لا توجد سجلات تطابق الفلاتر الحالية.</td></tr>';
        html += '</tbody><tfoot><tr><th class="pro-total-label" colspan="'+columns.length+'">'+escapeHtml(summary.amount_label || 'المبلغ')+' · '+escapeHtml(numberKnown(summary.count) ? summary.count : rows.length)+' سجل</th></tr>';
        if(columns.some(function(def){ return def.id === 'amount'; })){
            html += '<tr>';
            columns.forEach(function(def){
                var isAmount = def.id === 'amount';
                html += '<td'+(isAmount ? ' class="pro-money"' : '')+'>'+(isAmount ? '<bdi>'+escapeHtml(money(summary.amount))+'</bdi>' : '—')+'</td>';
            });
            html += '</tr>';
        }
        return html+'</tfoot>';
    }
    function summaryHtml(summary){
        summary = summary || {};
        var html = '<div class="pro-summary-cards">';
        html += '<div class="pro-summary-card pro-primary"><div class="pro-card-label">'+escapeHtml(summary.amount_label || 'المبلغ')+'</div><div class="pro-card-value'+(!numberKnown(summary.amount) ? ' pro-unknown' : '')+'"><bdi>'+escapeHtml(money(summary.amount))+'</bdi></div></div>';
        html += '<div class="pro-summary-card"><div class="pro-card-label">عدد السجلات</div><div class="pro-card-value"><bdi>'+escapeHtml(numberKnown(summary.count) ? summary.count : 0)+'</bdi></div></div></div>';
        var byStatus = summary.by_status || {}, keys = Object.keys(byStatus);
        if(keys.length){
            html += '<div class="pro-status-totals" aria-label="المبلغ حسب حالة سطر الموظف">';
            keys.forEach(function(code){
                var item = byStatus[code] || {};
                html += '<div class="pro-status-total">'+statusBadge(code)+' · '+escapeHtml(item.count || 0)+' سجل · <bdi>'+escapeHtml(money(item.amount))+'</bdi></div>';
            });
            html += '</div>';
        }
        return html;
    }
    function renderTable(host, rows, report, options){
        host = resolveHost(host); if(!host) return;
        if(host.classList) host.classList.add('pro-report-table');
        host.innerHTML = tableHtml(rows,report,options);
    }
    function renderSummary(host, summary){
        host = resolveHost(host); if(!host) return;
        if(host.classList) host.classList.add('pro-summary');
        host.innerHTML = summaryHtml(summary);
    }
    function mount(settings){
        settings = settings || {};
        var host = resolveHost(settings.hostId), config = settings.config || {};
        if(!host) throw new Error('Report options host is missing');
        var prefix = 'pro-'+(++nextId)+'-', columns = (config.columns || []).filter(function(c){ return c && /^[a-z][a-z0-9_]*$/.test(c.id); });
        var ids = columns.map(function(c){ return c.id; }), presets = config.presets || {};
        var lists = {
            measure:config.measure_options || [{value:'total',label:'الإجمالي'},{value:'base',label:'المبلغ دون 325'},{value:'allowance',label:'علاوة 325 فقط'}],
            mode:config.mode_options || [{value:'all',label:'كل أسس الاحتساب'},{value:'salary',label:'النوع 2 · نسبة من راتب الشهر'},{value:'remaining',label:'النوع 2 · نسبة من المتبقي'}],
            allowance:config.allowance_options || [{value:'all',label:'كل السجلات'},{value:'with',label:'حصة 325 أكبر من صفر'},{value:'without',label:'حصة 325 تساوي صفرًا'},{value:'unknown',label:'حصة 325 غير متوفرة'}],
            detail_status:config.detail_status_options || [{value:'all',label:'كل حالات السطر'}].concat(Object.keys(statusNames).map(function(k){ return {value:k,label:statusNames[k]}; }))
        };
        function canonicalColumns(value){
            var selected = Array.isArray(value) ? value : (typeof value === 'string' ? value.split(',') : []);
            selected = selected.map(function(id){ return ['base','allowance','total'].indexOf(id) !== -1 ? 'amount' : id; });
            return ids.filter(function(id){ return selected.indexOf(id) !== -1; });
        }
        var detailed = canonicalColumns(presets.detailed); if(!detailed.length) detailed = ids.slice();
        var compact = canonicalColumns(presets.compact); if(!compact.length) compact = detailed.slice();
        function normalize(value){
            value = value || {};
            var selected = canonicalColumns(value.columns);
            var result = {columns:selected.length ? selected : detailed.slice()};
            ['measure','mode','allowance','detail_status'].forEach(function(key){
                var fallback = key === 'measure' ? 'total' : 'all';
                var val = String(value[key] == null ? fallback : value[key]);
                result[key] = lists[key].some(function(item){ return String(item.value) === val; }) ? val : fallback;
            });
            return result;
        }
        var defaults = normalize(config.default_options || {columns:detailed}), current = normalize(defaults);
        if(settings.storageKey){
            try{
                var saved = JSON.parse(window.localStorage.getItem(settings.storageKey) || 'null');
                if(saved && saved.version === 1 && saved.options) current = normalize(saved.options);
            }catch(ignore){}
        }
        function clone(){ return {columns:current.columns.slice(),measure:current.measure,mode:current.mode,allowance:current.allowance,detail_status:current.detail_status}; }
        function persist(){
            if(!settings.storageKey) return;
            try{ window.localStorage.setItem(settings.storageKey,JSON.stringify({version:1,options:clone()})); }catch(ignore){}
        }
        function announce(text){ host.querySelector('.pro-announcement').textContent = text || ''; }
        function update(){
            Array.prototype.forEach.call(host.querySelectorAll('[data-column]'),function(input){ input.checked = current.columns.indexOf(input.getAttribute('data-column')) !== -1; });
            ['measure','mode','allowance','detail_status'].forEach(function(key){ host.querySelector('[data-filter="'+key+'"]').value = current[key]; });
            host.querySelector('[data-preset="compact"]').setAttribute('aria-pressed',String(sameColumns(current.columns,compact)));
            host.querySelector('[data-preset="detailed"]').setAttribute('aria-pressed',String(sameColumns(current.columns,detailed)));
            host.querySelector('.pro-column-count').textContent = current.columns.length+' من '+columns.length;
        }
        function changed(){ persist(); update(); if(typeof settings.onChange === 'function') settings.onChange(clone()); }
        var html = '<fieldset class="pro-options"><legend>خيارات التقرير</legend><div class="pro-toolbar"><button type="button" class="pro-button" data-preset="compact" aria-pressed="false">عرض مختصر</button><button type="button" class="pro-button" data-preset="detailed" aria-pressed="false">عرض تفصيلي</button><button type="button" class="pro-button pro-reset" data-reset>استعادة الافتراضي</button></div><div class="pro-filters">';
        [{key:'measure',label:'المبلغ المعروض'},{key:'mode',label:'أساس احتساب النوع 2'},{key:'allowance',label:'تصفية 325'},{key:'detail_status',label:'حالة سطر الموظف'}].forEach(function(filter){
            html += '<div class="pro-field"><label for="'+prefix+filter.key+'">'+filter.label+'</label><select id="'+prefix+filter.key+'" data-filter="'+filter.key+'">';
            lists[filter.key].forEach(function(item){ html += '<option value="'+escapeHtml(item.value)+'">'+escapeHtml(item.label)+'</option>'; });
            html += '</select></div>';
        });
        html += '</div><details><summary>الأعمدة المعروضة · <span class="pro-column-count"></span></summary><div class="pro-column-tools"><button type="button" class="pro-button" data-select-all>تحديد الكل</button><span>أبقِ عمودًا واحدًا على الأقل</span></div><div class="pro-columns">';
        columns.forEach(function(c){ html += '<label class="pro-column" for="'+prefix+'col-'+c.id+'"><input type="checkbox" id="'+prefix+'col-'+c.id+'" data-column="'+c.id+'"><span>'+escapeHtml(c.label)+'</span></label>'; });
        html += '</div></details><div class="pro-announcement" role="status" aria-live="polite"></div></fieldset>';
        host.innerHTML = html;
        Array.prototype.forEach.call(host.querySelectorAll('[data-column]'),function(input){
            input.addEventListener('change',function(){
                var chosen = [];
                Array.prototype.forEach.call(host.querySelectorAll('[data-column]:checked'),function(check){ chosen.push(check.getAttribute('data-column')); });
                if(!chosen.length){ input.checked = true; announce('يجب إبقاء عمود واحد على الأقل.'); return; }
                current.columns = canonicalColumns(chosen); announce(''); changed();
            });
        });
        Array.prototype.forEach.call(host.querySelectorAll('[data-filter]'),function(select){ select.addEventListener('change',function(){ current[select.getAttribute('data-filter')] = select.value; announce(''); changed(); }); });
        Array.prototype.forEach.call(host.querySelectorAll('[data-preset]'),function(button){ button.addEventListener('click',function(){ current.columns = (button.getAttribute('data-preset') === 'compact' ? compact : detailed).slice(); announce(''); changed(); }); });
        host.querySelector('[data-select-all]').addEventListener('click',function(){ current.columns = ids.slice(); announce(''); changed(); });
        host.querySelector('[data-reset]').addEventListener('click',function(){ current = normalize(defaults); announce('تمت استعادة خيارات العرض الافتراضية.'); changed(); });
        update();
        return {
            getOptions:clone,
            getRequestParams:function(){ return {report_columns:current.columns.join(','),report_measure:current.measure,report_mode:current.mode,report_allowance:current.allowance,report_detail_status:current.detail_status}; },
            applyOptions:function(value, flags){ flags = flags || {}; current = normalize(value); update(); if(flags.persist) persist(); if(!flags.silent && typeof settings.onChange === 'function') settings.onChange(clone()); },
            setBusy:function(busy){ host.setAttribute('aria-busy',busy ? 'true' : 'false'); }
        };
    }
    window.PaymentReportOptions = {mount:mount,renderTable:renderTable,renderSummary:renderSummary,tableHtml:tableHtml,summaryHtml:summaryHtml,money:money,escapeHtml:escapeHtml};
})(window, document);
</script>
