// Render real server responses using the actual shared view JavaScript, without a DB.
// First run report_contract_test.php --render --output=DIR, then this script with the same output.
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const assert = require('assert');
const os = require('os');
const outputArg = process.argv.slice(2).find(arg => arg.startsWith('--output='));
const preview = outputArg ? path.resolve(outputArg.slice(9)) : path.join(os.tmpdir(), 'gfc-payment-req-report-fixtures');
const view = fs.readFileSync(path.join(__dirname, '../../application/modules/payment_req/views/payment_req_report_options.php'), 'utf8');
const script = [...view.matchAll(/<script\b[^>]*>([\s\S]*?)<\/script>/g)].map(m => m[1]).find(s => s.includes('function tableHtml('));
assert(script, 'Actual shared report renderer missing');
const context = vm.createContext({ window: {}, document: {}, console });
vm.runInContext(script, context);
const api = context.window.PaymentReportOptions;
const fixtures = {};
for (const name of ['report','statement']) {
    fixtures[name] = JSON.parse(fs.readFileSync(path.join(preview,name+'-responses.json'),'utf8'));
    const response = fixtures[name].responses['total|all|all|all'];
    const table = api.tableHtml(response.data, response.report);
    const headers = table.split('</thead>')[0].match(/<th\b/g) || [];
    assert.equal(headers.length, name === 'report' ? 12 : 10);
    const body = table.split('<tbody>')[1].split('</tbody>')[0];
    assert.equal((body.match(/<tr>/g) || []).length, 6);
    assert(body.includes('1,250.00') && body.includes('300.00'));
    assert(body.includes('من المتبقي') && body.includes('من الراتب'));
    const footer = table.split('<tfoot>')[1];
    assert(footer.includes('5,600.00') && !footer.includes('4,600.00') && !footer.includes('550.00'));
    const cards = api.summaryHtml(response.report.summary);
    for (const total of ['3,750.00','600.00','150.00','1,100.00']) assert(cards.includes(total));
    assert(!cards.includes('معتمد جزئياً'), 'Master status leaked into detail summary');
    for (const [measure,label,total,paid,first,nonmonthly] of [
        ['total','الإجمالي','5,600.00','3,750.00','1,250.00','300.00'],
        ['base','المبلغ دون 325','5,050.00','3,300.00','1,000.00','300.00'],
        ['allowance','علاوة 325 فقط','550.00','450.00','250.00','0.00']]) {
        const result=fixtures[name].responses[measure+'|all|all|all'];
        const rendered=api.tableHtml(result.data,result.report);
        assert(rendered.split('</thead>')[0].includes(label));
        const cells=rendered.split('<tbody>')[1].split('</tbody>')[0];
        assert(cells.includes(first) && cells.includes(nonmonthly));
        assert(rendered.split('<tfoot>')[1].includes(total) && rendered.split('<tfoot>')[1].includes(label));
        const summary=api.summaryHtml(result.report.summary);
        assert(summary.includes(label) && summary.includes(total) && summary.includes(paid));
        if(measure!=='total') assert(!summary.includes('5,600.00') && !summary.includes('3,750.00'), 'Unselected cash totals leaked into summary');
    }
    const filtered = fixtures[name].responses['allowance|remaining|with|all'];
    const selected = JSON.parse(JSON.stringify(filtered.report));
    selected.columns = selected.columns.filter(c => ['req_no','amount'].includes(c.id));
    const selectedTable = api.tableHtml(filtered.data,selected);
    assert.equal((selectedTable.split('</thead>')[0].match(/<th\b/g)||[]).length,2);
    assert(selectedTable.includes('TEST-9001') && !selectedTable.includes('TEST-9002'));
    assert(!selectedTable.includes('1,250.00') && selectedTable.includes('250.00'));
    const empty = fixtures[name].responses['allowance|remaining|all|0'];
    const emptyTable = api.tableHtml(empty.data,empty.report);
    assert(emptyTable.includes('لا توجد سجلات') && emptyTable.includes('0.00'));
    assert(!emptyTable.includes('TEST-9001'), 'Stale request survives empty result');
}
const missing = JSON.parse(JSON.stringify(fixtures.report.responses['base|all|all|all']));
missing.data[0].PAYMENT_BREAKDOWN_AVAILABLE=false;
missing.data[0].BASE_PAYMENT=missing.data[0].PAYMENT_ALLOWANCE_325=null;
missing.report.summary.base=missing.report.summary.allowance=null;
missing.data[0].REPORT_AMOUNT=null;
missing.report.summary.amount=null;
const unknown = api.tableHtml(missing.data,missing.report);
assert(unknown.includes('غير متوفر') && !unknown.includes('5,600.00'));
assert(api.summaryHtml(missing.report.summary).includes('غير متوفر'));
missing.data[0].EMP_NAME='<img src=x onerror=alert(1)>';
assert(!api.tableHtml(missing.data,missing.report).includes('<img'), 'Report text must escape HTML');
for (const value of [null,undefined,'',NaN,Infinity]) assert.equal(api.money(value),'غير متوفر');
assert.equal(api.money(0),'0.00');
console.log('PASS: actual shared HTML renderers; selected measure/columns/totals/status, nonmonthly cash, filtered/empty/missing data and escaping.');
