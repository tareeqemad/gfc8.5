// Offline regression for the initial HTML and AJAX table replacement paths.
// No application bootstrap, database, network, or attendance updates are used.
// Run: node tests/php85/clock_data_script_order_test.js [--modules path/to/modules]
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = path.resolve(__dirname, '../..');
const args = process.argv.slice(2);
assert(args.length === 0 || (args.length === 2 && args[0] === '--modules'),
    'Usage: node clock_data_script_order_test.js [--modules path/to/modules]');
const modulesRoot = args.length ? path.resolve(args[1]) : path.join(root, 'application/modules');
const view = fs.readFileSync(path.join(modulesRoot,
    'hr_attendance/views/clock_data_page.php'), 'utf8');
const template = fs.readFileSync(path.join(modulesRoot, 'template/views/template.php'), 'utf8');
const contentAt = template.indexOf('$this->load->view($content)');
const jqueryAt = template.indexOf('assets/js/jquery-1.11.1.min.js');
assert(contentAt >= 0 && jqueryAt > contentAt,
    'The regression must exercise the production content-before-jQuery order');
const blocks = [...view.matchAll(/<script\b[^>]*>([\s\S]*?)<\/script\s*>/gi)];
assert.strictEqual(blocks.length, 1, 'Expected one table initializer');
const script = blocks[0][1].replace(/<\?=\$edit_status_url\?>/g, '/offline/edit_status');
assert(!script.includes('<?'), 'All PHP fragments must be resolved before testing JavaScript');

function fixture({readyState = 'complete', jqueryInitiallyPresent = true,
    helpersInitiallyPresent = true, showPage = true, nodesPresent = true} = {}) {
    const ready = new Set();
    const bound = [{selector: '#tb_delay_month thead', event: 'click.otherFeature', handler() {}}];
    const table = {style: {display: ''}};
    const pagination = {style: {display: ''}};
    let toggles = 0;
    let requests = 0;
    let functionInitializations = 0;
    let pagerInitializations = 0;
    const sandbox = {
        document: {
            readyState,
            addEventListener(event, callback) {
                assert.strictEqual(event, 'DOMContentLoaded');
                ready.add(callback);
            },
            removeEventListener(event, callback) {
                assert.strictEqual(event, 'DOMContentLoaded');
                ready.delete(callback);
            },
            getElementById(id) {
                assert.strictEqual(id, 'page_tb');
                return nodesPresent ? table : null;
            },
            getElementsByClassName(name) {
                assert.strictEqual(name, 'pagination');
                return nodesPresent ? [pagination] : [];
            },
        },
        get_data() { requests++; throw new Error('Initialization must never update attendance'); }
    };
    if (showPage) sandbox.show_page = true;
    function jquery(selector) {
        return {
            off(event) {
                for (let i = bound.length - 1; i >= 0; i--) {
                    if (bound[i].selector === selector && (!event || bound[i].event === event)) {
                        bound.splice(i, 1);
                    }
                }
                return this;
            },
            on(event, handler) { bound.push({selector, event, handler}); return this; },
            // Retain the old shorthand APIs so duplicate-binding regressions
            // fail behavior assertions rather than merely missing mock methods.
            click(handler) { bound.push({selector, event: 'click', handler}); },
            change(handler) { bound.push({selector, event: 'change', handler}); },
            toggle() {
                assert.strictEqual(selector, '#tb_delay_month tbody');
                toggles++;
            }
        };
    }
    function installHelpers() {
        sandbox.initFunctions = () => { functionInitializations++; };
        sandbox.ajax_pager = () => { pagerInitializations++; };
    }
    sandbox.window = sandbox;
    if (jqueryInitiallyPresent) sandbox.jQuery = sandbox.$ = jquery;
    if (helpersInitiallyPresent) installHelpers();
    const context = vm.createContext(sandbox);
    return {
        ready, bound, table, pagination,
        run(source = script) {
            vm.runInContext(source, context, {filename: 'clock_data_page.php:inline', timeout: 1000});
        },
        installJQuery() { sandbox.jQuery = sandbox.$ = jquery; },
        installHelpers,
        fireReady() {
            sandbox.document.readyState = 'complete';
            [...ready].forEach(callback => callback());
        },
        verify(functionCalls, pagerCalls) {
            assert.strictEqual(ready.size, 0, 'Completed initializers must remove their ready listener');
            assert.strictEqual(bound.filter(item => item.event === 'click.otherFeature').length, 1,
                'Rebinding this table must preserve unrelated event handlers');
            const own = bound.filter(item => item.event !== 'click.otherFeature');
            assert.deepStrictEqual(own.map(item => [item.selector, item.event.split('.')[0]]), [
                ['#tb_delay_month thead', 'click'], ['.function_key_edit', 'change']
            ], 'Existing handlers must be registered exactly once');
            const oldToggles = toggles;
            own[0].handler();
            assert.strictEqual(toggles, oldToggles + 1, 'The delay summary must still expand/collapse');
            assert.strictEqual(functionInitializations, functionCalls,
                'Initial HTML must not repeat shared initialization; AJAX must initialize its partial');
            assert.strictEqual(pagerInitializations, pagerCalls, 'Each completed partial initializes its pager');
            assert.strictEqual(requests, 0, 'Opening a table must not update attendance');
        }
    };
}

const initial = fixture({readyState: 'loading', jqueryInitiallyPresent: false,
    helpersInitiallyPresent: false});
initial.run();
assert.strictEqual(initial.ready.size, 1, 'Initial HTML must wait until jQuery has loaded');
assert.strictEqual(initial.bound.length, 1, 'No early bindings before the footer');
initial.installJQuery();
initial.installHelpers();
initial.fireReady();
initial.verify(0, 1);

const ajax = fixture();
ajax.run();
ajax.verify(1, 1);
ajax.run();
ajax.verify(2, 2); // Repeated partial initialization must not duplicate handlers.

const earlyJquery = fixture({readyState: 'loading'});
earlyJquery.run();
assert.strictEqual(earlyJquery.bound.length, 1, 'jQuery availability alone does not mean DOM ready');
earlyJquery.fireReady();
earlyJquery.verify(1, 1);

const hidden = fixture({showPage: false});
hidden.run();
hidden.verify(1, 1);
assert.strictEqual(hidden.table.style.display, 'none');
assert.strictEqual(hidden.pagination.style.display, 'none');
assert.strictEqual(ajax.table.style.display, '', 'Normal result tables remain visible');

const missingNodes = fixture({showPage: false, nodesPresent: false});
missingNodes.run();
missingNodes.verify(1, 1);

// Prove the fixture catches the former eager access rather than merely accepting
// any script that runs without registering the expected handlers.
assert.throws(() => vm.runInNewContext(
    "$('#tb_delay_month thead').click(function(){});", {}),
    error => error.name === 'ReferenceError' && /\$/.test(error.message));

console.log('PASS: attendance initial/AJAX readiness, shared/partial initialization, repeat binding, '
    + 'unrelated handlers, visibility and missing nodes; no attendance data requests.');
