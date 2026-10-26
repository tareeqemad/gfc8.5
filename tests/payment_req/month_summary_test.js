// Offline regression checks against the actual view JavaScript; no server or Oracle.
// node tests/payment_req/month_summary_test.js <php-cli> [preview-output-directory]
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const assert = require('assert');
const {execFileSync} = require('child_process');
const root = path.resolve(__dirname, '../..');
const views = path.join(root, 'application/modules/payment_req/views');
const index = fs.readFileSync(path.join(views, 'payment_req_index.php'), 'utf8');
const show = fs.readFileSync(path.join(views, 'payment_req_show.php'), 'utf8');
const php = process.argv[2] || 'php';

function functionSource(source, name) {
    const match = new RegExp('function\\s+' + name + '\\s*\\(').exec(source);
    assert(match, 'Missing actual view function: ' + name);
    let start = source.indexOf('{', match.index), depth = 0, quote = '', comment = '';
    for (let i = start; i < source.length; i++) {
        const ch = source[i], next = source[i + 1];
        if (comment === 'line') { if (ch === '\n') comment = ''; continue; }
        if (comment === 'block') { if (ch === '*' && next === '/') {comment = ''; i++;} continue; }
        if (quote) { if (ch === '\\') {i++; continue;} if (ch === quote) quote = ''; continue; }
        if (ch === '/' && next === '/') {comment = 'line'; i++; continue;}
        if (ch === '/' && next === '*') {comment = 'block'; i++; continue;}
        if (ch === "'" || ch === '"' || ch.charCodeAt(0) === 96) {quote = ch; continue;}
        if (ch === '{') depth++;
        if (ch === '}' && --depth === 0) return source.slice(match.index, i + 1);
    }
    throw new Error('Unclosed function: ' + name);
}
const indexCode = ['_buildWalletCards', '_walletNumber', '_walletFormat', '_nf']
    .map(name => functionSource(index, name)).join('\n');
const showCodeRaw = ['_monthValue', '_monthFormat', '_renderWalletSummary', 'wizUpdateSelected']
    .map(name => functionSource(show, name)).join('\n');
const showCode = execFileSync(php, ['-n'], {
    input: '<?php $is_cancelled = false; ?>' + showCodeRaw, encoding: 'utf8'
});
const fixture = {
    calculated: true, total_323: 2000, total_net: 2000, total_base_used: 1000,
    total_allowance_used: 250, total_cash_used: 1250,
    total_draft: 500, total_approved: 250, total_paid: 500, emp_count: 2
};
const output = {};
let selected = [{EMP_NO: 101, CALC_AMOUNT: 400, CON_323_AMOUNT: 300}];
function jquery(selector) {
    if (typeof selector === 'object') return {data: key => key === 'amt' ? selector.CALC_AMOUNT : null};
    const isRows = selector.indexOf('.wiz-emp-chk') === 0;
    const rows = selector.indexOf(':checked') >= 0 ? selected : [
        {EMP_NO: 101, CALC_AMOUNT: 400}, {EMP_NO: 102, CALC_AMOUNT: 200}
    ];
    const chain = {
        length: isRows ? rows.length : 1,
        each: fn => {rows.forEach(row => fn.call(row)); return chain;},
        text: value => {output[selector] = value; return chain;},
        html: value => {output[selector] = value; return chain;},
        prop: () => chain, toggle: () => chain,
        val: () => '2'
    };
    return chain;
}
jquery.post = (url, parameters, callback) => callback(fixture);
const context = vm.createContext({
    Number, String, Math, isFinite, parseFloat, parseInt,
    nf: value => Number(value).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}),
    $: jquery, jQuery: jquery, salCheckUrl: '/offline', _walletMonth: '202610',
    _showMonthCalc: fixture, _wizPreviewReady: true,
    _wizParams: () => ({req_type: '2', type2_remaining_mode: 0}),
    _wizSelectedRows: () => selected
});
vm.runInContext(indexCode + '\n' + showCode, context);
const cards = context._buildWalletCards(fixture);
assert(cards.includes('2,000.00') && cards.includes('1,250.00') && cards.includes('250.00'));
assert(cards.includes('المتبقي من الأساس</div><div class="c-val">1,000.00'), '325 must not consume the salary base twice');
assert(cards.includes('صافي الراتب الرسمي + بند 323'), 'The salary base must not be labelled raw 323');
assert(!cards.includes('750.00'), 'Do not subtract total cash, including 325, from the salary base');
const missing = context._buildWalletCards({calculated:true});
assert(missing.includes('—') && !missing.includes('NaN') && !missing.includes('0.00'), 'Unknown totals must remain unknown');
const zero = context._buildWalletCards({...fixture, total_323:0, total_net:9999, total_base_used:0});
assert(!zero.includes('9,999.00'), 'A real zero must not fall back to another basis');
const over = context._buildWalletCards({...fixture, total_base_used:2100});
assert(over.includes('الاستهلاك يتجاوز') && over.includes('100.00'), 'Overconsumption must be explained');
context._renderWalletSummary(400, 300);
const savedCards = output['#showWalletSummary'];
assert(savedCards.includes('المتبقي من الأساس</div><div class="c-val">1,000.00'));
assert(savedCards.includes('ضمن الإجمالي'), 'A saved request must not be added to the monthly cash a second time');
context.wizUpdateSelected();
assert.strictEqual(output['#wizMonthRequestCash'], '400.00');
assert.strictEqual(output['#wizMonthAccrued'], '300.00');
assert.strictEqual(output['#wizMonthRemaining'], '600.00');
selected = [];
context.wizUpdateSelected();
assert.strictEqual(output['#wizMonthRequestCash'], '0.00');
assert.strictEqual(output['#wizMonthAccrued'], '0.00');
assert.strictEqual(output['#wizMonthRemaining'], '1,000.00');
fixture.total_base_used = null;
context.wizUpdateSelected();
assert.strictEqual(output['#wizMonthRemaining'], '—');
fixture.total_base_used = 1000;

if (process.argv[3]) {
    const destination = path.resolve(process.argv[3]);
    fs.mkdirSync(destination, {recursive:true});
    const style = fs.readFileSync(path.join(views, 'payment_req_month_summary_styles.php'), 'utf8');
    const html = '<!doctype html><html lang="ar" dir="rtl"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        + '<title>مراجعة ملخص الصرف — بيانات تجريبية</title>'
        + '<style>body{font-family:Tahoma,Arial,sans-serif;margin:24px;background:#f5f7fa;color:#0f172a}section{margin:20px 0;padding:20px;background:white;border-radius:12px}.text-muted{color:#64748b}.text-danger{color:#b91c1c}h1{font-size:23px}h2{font-size:18px}</style>'
        + style + '<h1>مراجعة محلية — بيانات تجريبية فقط</h1><p>أساس الشهر 2,000؛ المصروف والمحجوز 1,250، منه 325 بقيمة 250؛ المتبقي من الأساس 1,000.</p>'
        + '<section><h2>قائمة الطلبات</h2>' + cards + '</section>'
        + '<section><h2>داخل طلب محفوظ</h2>' + savedCards + '</section>'
        + '<section><h2>عند غياب تفصيل الأرقام</h2>' + missing + '</section></html>';
    fs.writeFileSync(path.join(destination, 'month-summary.html'), html, 'utf8');
}
console.log('PASS: month summaries reconcile cash/base/325; zero and missing data; selection updates.');
