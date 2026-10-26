// Execute the real index/archive report handlers with synthetic identifiers.
// No browser, database, network, or file writes are used.
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const assert = require('assert');

const views = path.resolve(__dirname, '../../application/modules/payment/views');
const rows = fs.readFileSync(path.join(views, 'financial_payment_page.php'), 'utf8');
const rowHandlers = [...new Set([...rows.matchAll(/onclick="javascript:(print_\w+_report)\(/g)].map(match => match[1]))];
const reports = {
    print_financial_payment_report: ['financial_payment_rep', 'P_FINANCIAL_PAYMENT_ID'],
    print_hewalah_report: ['HEWALAH', 'p_FINANCIAL_PAYMENT_ID'],
    print_cash_report: ['transfer_cash_payment', 'p_FINANCIAL_PAYMENT_ID']
};
assert.deepStrictEqual(rowHandlers.slice().sort(), Object.keys(reports).sort(),
    'Cover every Jasper handler used by the shared payment rows');

let checks = 0;
for (const name of ['financial_payment_index.php', 'financial_payment_archive.php']) {
    const view = fs.readFileSync(path.join(views, name), 'utf8');
    const block = /\$scripts\s*=\s*<<<SCRIPT\s*([\s\S]*?)\r?\nSCRIPT;/.exec(view);
    assert(block, name + ': deferred script missing');
    const script = /<script>([\s\S]*?)<\/script>/.exec(block[1]);
    assert(script, name + ': script block missing');
    // Substitute only PHP heredoc values, preserving all production JS functions.
    const code = script[1]
        .split('{$report_jasper_url}').join('http://localhost/gfc/JsperReport/showreport?sys=financial/archives')
        .split('{$report_sn}').join('fixture-serial')
        .split('{$get_page_url}').join('http://localhost/unused-query')
        .split('{$case}').join('1')
        .split('{$action}').join('edit');
    assert(!/\{\$[a-zA-Z_]/.test(code), name + ': unexpected PHP interpolation');

    const opened = [];
    const context = vm.createContext({
        $(callback) {
            assert.strictEqual(typeof callback, 'function', 'Only queue the page-ready callback');
        },
        _showReport(url) { opened.push(url); }
    });
    vm.runInContext(code, context, {filename: name, timeout: 1000});

    for (const handler of rowHandlers) {
        assert.strictEqual(typeof context[handler], 'function', name + ': missing row handler ' + handler);
        const before = opened.length;
        context[handler](900001);
        assert.strictEqual(opened.length, before + 1, name + ': expected one report modal');
        const [report, parameter] = reports[handler];
        assert.strictEqual(opened[before], 'http://localhost/gfc/JsperReport/showreport?sys=financial/archives'
            + '&report_type=pdf&report=' + report + '&' + parameter + '=900001&sn=fixture-serial',
        name + ': report parameters changed for ' + handler);
        checks++;
    }
}

console.log('PASS: ' + checks + ' actual payment report handlers open the expected modal URL; no network or database access.');
