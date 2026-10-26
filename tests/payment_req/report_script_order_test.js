// Offline load-order regression: run report_contract_test.php --render first.
// node tests/payment_req/report_script_order_test.js --output=FIXTURE_DIRECTORY
// jQuery is unavailable until its external script tag; ready callbacks are queued,
// never executed. This checks template integration, not report rendering or AJAX.
const fs = require('fs');
const path = require('path');
const os = require('os');
const vm = require('vm');
const assert = require('assert');

const root = path.resolve(__dirname, '../..');
const outputArg = process.argv.slice(2).find(arg => arg.startsWith('--output='));
const output = outputArg ? path.resolve(outputArg.slice(9))
    : path.join(os.tmpdir(), 'gfc-payment-req-report-fixtures');
const jqueryUrl = /(?:^|\/)jquery\.min\.js(?:[?#].*)?$/i;

function scriptBlocks(html) {
    return [...html.matchAll(/<script\b([^>]*)>([\s\S]*?)<\/script\s*>/gi)].map(match => {
        const src = /\bsrc\s*=\s*(["'])(.*?)\1/i.exec(match[1]);
        return {src: src ? src[2] : null, code: match[2], start: match.index, tag: match[0]};
    });
}

function executeInOrder(html, name) {
    const scripts = scriptBlocks(html);
    assert.strictEqual(scripts.filter(script => script.src && jqueryUrl.test(script.src)).length,
        1, name + ': expected exactly one external jQuery');
    const queued = [];
    const sandbox = {
        document: {}, console, HTMLFormElement: class HTMLFormElement {},
        setTimeout() { throw new Error(name + ': boot executed instead of waiting for ready'); },
        clearTimeout() {}
    };
    sandbox.window = sandbox;
    const context = vm.createContext(sandbox);
    let currentScript = -1, jqueryIndex = -1, helperIndex = -1;
    scripts.forEach((script, index) => {
        currentScript = index;
        if (script.src) {
            assert(jqueryUrl.test(script.src), name + ': unexpected external script ' + script.src);
            assert.strictEqual(typeof context.jQuery, 'undefined', name + ': jQuery was installed early');
            assert.strictEqual(typeof context.$, 'undefined', name + ': $ was installed early');
            assert(context.PaymentReportOptions, name + ': shared vanilla JS must load in content');
            jqueryIndex = index;
            function jquery(callback) {
                assert.strictEqual(typeof callback, 'function', name + ': unexpected eager DOM access');
                queued.push({callback, script: currentScript});
                return jquery;
            }
            jquery.fn = {};
            context.$ = context.jQuery = jquery;
            return;
        }
        vm.runInContext(script.code, context, {filename: name + ':script-' + index, timeout: 1000});
        if (helperIndex < 0 && context.PaymentReportOptions) helperIndex = index;
    });
    assert(helperIndex >= 0 && helperIndex < jqueryIndex,
        name + ': shared options must work before jQuery');
    assert.strictEqual(typeof context.PaymentReportOptions.mount, 'function');
    assert.strictEqual(typeof context.PaymentReportOptions.renderTable, 'function');
    assert(queued.every(entry => entry.script > jqueryIndex), name + ': ready registered before jQuery');
    return {scripts, queued, jqueryIndex};
}

// Anchor the fixture order to the real production template, which renders content
// first and emits sec_scripts registrations only after loading jQuery.
const template = fs.readFileSync(path.join(root, 'application/modules/template/views/template1.php'), 'utf8');
const contentAt = template.indexOf('$this->load->view($content)');
const jqueryAt = template.indexOf('assets-n/js/jquery.min.js');
const deferredAt = template.indexOf("put_headers('js')");
assert(contentAt >= 0 && contentAt < jqueryAt && jqueryAt < deferredAt,
    'Production template must render content, load jQuery, then emit deferred view scripts');

let negativeChecks = 0;
for (const name of ['report.html', 'statement.html', 'report-other-user.html']) {
    const html = fs.readFileSync(path.join(output, name), 'utf8');
    const result = executeInOrder(html, name);
    const isStatement = name === 'statement.html';
    assert.strictEqual(result.queued.length, isStatement ? 1 : 2,
        name + ': expected one view boot plus the general-report fixture ready callback');
    const boots = result.queued.filter(entry => /PaymentReportOptions\.mount\(/.test(String(entry.callback)));
    assert.strictEqual(boots.length, 1, name + ': view must queue its boot exactly once');
    assert(String(boots[0].callback).includes(isStatement ? 'stmt_options' : 'rpt_options'),
        name + ': wrong view boot was queued');
    if (!isStatement) {
        const starts = result.queued.filter(entry => String(entry.callback).includes("trigger('submit')"));
        assert.strictEqual(starts.length, 1, name + ': missing fixture start callback');
        assert(boots[0].script < starts[0].script, name + ': fixture start preceded the view boot');

        // Recreate the production failure in memory, without changing fixtures:
        // move the general initializer out of the footer to just before jQuery.
        const initializer = result.scripts[boots[0].script];
        const jquery = result.scripts[result.jqueryIndex];
        const withoutInitializer = html.slice(0, initializer.start)
            + html.slice(initializer.start + initializer.tag.length);
        const broken = withoutInitializer.slice(0, jquery.start) + initializer.tag
            + withoutInitializer.slice(jquery.start);
        assert.throws(() => executeInOrder(broken, name + ':former-order'),
            error => error && error.name === 'ReferenceError' && /jQuery/.test(error.message),
            name + ': former immediate initializer must fail with jQuery unavailable');
        negativeChecks++;
    }
}
console.log('PASS: 3 pages queue one view boot after exactly one external jQuery; '
    + 'shared options load before it; production template order verified; '
    + negativeChecks + ' former-order regressions rejected. Ready callbacks were not executed.');
