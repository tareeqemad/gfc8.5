// Run with Node. Exercise the actual shared options component without a browser
// or database. The DOM mock implements only the controls/events used by mount.
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const assert = require('assert');

class Element {
    constructor(attributes = {}) {
        this.attributes = { ...attributes };
        this.listeners = {};
        this.checked = false;
        this.value = '';
        this.textContent = '';
    }
    setAttribute(name, value) { this.attributes[name] = String(value); }
    getAttribute(name) { return this.attributes[name]; }
    addEventListener(name, handler) { (this.listeners[name] ||= []).push(handler); }
    fire(name) { (this.listeners[name] || []).forEach(handler => handler({ type: name, target: this })); }
}
class Host extends Element {
    set innerHTML(value) {
        this.html = value;
        this.columns = [...value.matchAll(/data-column="([^"]+)"/g)].map(match => new Element({ 'data-column': match[1] }));
        this.filters = [...value.matchAll(/data-filter="([^"]+)"/g)].map(match => new Element({ 'data-filter': match[1] }));
        this.presets = [...value.matchAll(/data-preset="([^"]+)"/g)].map(match => new Element({ 'data-preset': match[1] }));
        this.announcement = new Element();
        this.count = new Element();
        this.reset = new Element();
        this.selectAll = new Element();
    }
    querySelectorAll(selector) {
        if (selector === '[data-column]') return this.columns;
        if (selector === '[data-column]:checked') return this.columns.filter(node => node.checked);
        if (selector === '[data-filter]') return this.filters;
        if (selector === '[data-preset]') return this.presets;
        throw new Error(`Unexpected selector: ${selector}`);
    }
    querySelector(selector) {
        if (selector === '.pro-announcement') return this.announcement;
        if (selector === '.pro-column-count') return this.count;
        if (selector === '[data-reset]') return this.reset;
        if (selector === '[data-select-all]') return this.selectAll;
        const match = selector.match(/^\[(data-filter|data-preset)="([^"]+)"\]$/);
        if (match) return this.querySelectorAll(`[${match[1]}]`).find(node => node.getAttribute(match[1]) === match[2]);
        throw new Error(`Unexpected selector: ${selector}`);
    }
}
const saved = new Map();
const storage = {
    failRead: false, failWrite: false,
    getItem(key) { if (this.failRead) throw new Error('Storage blocked'); return saved.get(key) || null; },
    setItem(key, value) { if (this.failWrite) throw new Error('Storage quota'); saved.set(key, value); }
};
const hosts = new Map();
const context = vm.createContext({ window: { localStorage: storage }, document: { getElementById: id => hosts.get(id) } });
const source = fs.readFileSync(path.resolve(__dirname, '../../application/modules/payment_req/views/payment_req_report_options.php'), 'utf8');
vm.runInContext(source.match(/<script>([\s\S]*?)<\/script>/)[1], context, { filename: 'payment_req_report_options.php' });
const component = context.window.PaymentReportOptions;
const config = {
    columns: [
        { id: 'seq', label: 'م', kind: 'sequence', field: null },
        { id: 'req_no', label: 'رقم الطلب', kind: 'text', field: 'REQ_NO' },
        { id: 'calc_basis', label: 'أساس الاحتساب', kind: 'text', field: 'CALC_BASIS_LABEL' },
        { id: 'amount', label: 'المبلغ', kind: 'money', field: 'REPORT_AMOUNT' }
    ],
    presets: { compact: ['seq', 'amount'], detailed: ['seq', 'req_no', 'calc_basis', 'amount'] },
    measure_options: [{ value: 'total', label: 'الإجمالي' }, { value: 'base', label: 'المبلغ دون 325' }, { value: 'allowance', label: 'علاوة 325' }],
    default_options: { columns: ['seq', 'req_no', 'calc_basis', 'amount'], measure: 'total', mode: 'all', allowance: 'all', detail_status: 'all' }
};
function plain(value) { return JSON.parse(JSON.stringify(value)); }
let count = 0;
function mount(key) {
    const hostId = `fixture-${++count}`, host = new Host(), changes = [];
    hosts.set(hostId, host);
    const api = component.mount({ hostId, config, storageKey: key, onChange: options => changes.push(plain(options)) });
    return { api, host, changes };
}
function select(fixture, name, value) {
    const node = fixture.host.querySelector(`[data-filter="${name}"]`);
    node.value = value;
    node.fire('change');
}
const keyA = 'payment-report-options:v1:991001:general';
const first = mount(keyA);
assert.deepStrictEqual(plain(first.api.getOptions()), config.default_options);
assert.equal(first.changes.length, 0, 'Mount must not trigger an unsolicited load');
assert.equal(first.api.getRequestParams().report_measure, 'total');
assert(first.host.html.includes('المبلغ المعروض') && first.host.html.includes('تصفية 325'), 'Measure and row filter must have distinct labels');
select(first, 'measure', 'base');
select(first, 'mode', 'remaining');
select(first, 'allowance', 'with');
select(first, 'detail_status', '2');
first.host.querySelector('[data-preset="compact"]').fire('click');
assert.deepStrictEqual(plain(first.api.getOptions().columns), ['seq', 'amount']);
assert.equal(first.api.getRequestParams().report_columns, 'seq,amount');
assert.equal(first.api.getRequestParams().report_measure, 'base');
assert.equal(first.api.getRequestParams().report_mode, 'remaining');
assert.equal(first.api.getRequestParams().report_allowance, 'with');
assert.equal(first.api.getRequestParams().report_detail_status, '2');

// Persist only display preferences, isolated by both user and report context.
const payload = JSON.parse(saved.get(keyA));
assert.deepStrictEqual(Object.keys(payload).sort(), ['options', 'version']);
assert.deepStrictEqual(Object.keys(payload.options).sort(), ['allowance', 'columns', 'detail_status', 'measure', 'mode']);
assert.deepStrictEqual(plain(mount(keyA).api.getOptions()), payload.options);
assert.deepStrictEqual(plain(mount('payment-report-options:v1:991002:general').api.getOptions()), config.default_options);
assert.deepStrictEqual(plain(mount('payment-report-options:v1:991001:statement').api.getOptions()), config.default_options);
select(first, 'measure', 'allowance');
assert.equal(mount(keyA).api.getOptions().measure, 'allowance', 'Selected measure must survive reload');
assert.equal(first.api.getOptions().allowance, 'with', 'Choosing a measure must preserve the independent row filter');

// A user can hide columns, but cannot leave an unusable empty table.
first.host.querySelector('[data-preset="detailed"]').fire('click');
assert.deepStrictEqual(plain(first.api.getOptions().columns), config.presets.detailed);
first.host.columns.slice(0, -1).forEach(node => { node.checked = false; node.fire('change'); });
const last = first.host.columns[first.host.columns.length - 1], beforeLast = first.changes.length;
last.checked = false; last.fire('change');
assert(last.checked, 'Last selected column must be restored');
assert.deepStrictEqual(plain(first.api.getOptions().columns), ['amount']);
assert.equal(first.changes.length, beforeLast, 'Rejected empty selection must not reload');
assert(first.host.announcement.textContent, 'Rejected selection needs an accessible explanation');
first.host.selectAll.fire('click');
assert.deepStrictEqual(plain(first.api.getOptions().columns), config.presets.detailed);

// Reset includes the monetary measure and row filters, and updates saved preferences.
first.host.reset.fire('click');
assert.deepStrictEqual(plain(first.api.getOptions()), config.default_options);
assert.deepStrictEqual(JSON.parse(saved.get(keyA)).options, config.default_options);
assert.equal(first.api.getRequestParams().report_measure, 'total');
assert(first.host.announcement.textContent, 'Reset must be announced');

// Invalid/corrupt/obsolete preferences cannot add unknown columns or filters.
saved.set('broken-json', '{not JSON');
assert.deepStrictEqual(plain(mount('broken-json').api.getOptions()), config.default_options);
saved.set('bad-values', JSON.stringify({ version: 1, options: { columns: ['__proto__', 'bogus'], measure: 'bogus', mode: 'hack', allowance: 'bad', detail_status: '-1' } }));
assert.deepStrictEqual(plain(mount('bad-values').api.getOptions()), config.default_options);
saved.set('empty-columns', JSON.stringify({ version: 1, options: { columns: [], mode: 'all' } }));
assert(mount('empty-columns').api.getOptions().columns.length > 0);
saved.set('reordered', JSON.stringify({ version: 1, options: { columns: ['amount', 'seq', 'amount'], measure: 'base', mode: 'remaining', allowance: 'unknown', detail_status: '9' } }));
const ordered = mount('reordered');
assert.deepStrictEqual(plain(ordered.api.getOptions().columns), ['seq', 'amount']);
assert.equal(ordered.api.getOptions().measure, 'base');
assert.equal(ordered.api.getOptions().allowance, 'unknown');

// Upgrade existing v1 preferences in place. Old selected components do not infer
// the monetary measure; all legacy monetary columns become one canonical amount.
const oldKey = 'payment-report-options:v1:991003:general';
saved.set(oldKey, JSON.stringify({ version: 1, options: { columns: ['total', 'seq', 'base', 'amount', 'allowance', 'req_no'], mode: 'remaining', allowance: 'without', detail_status: '2' } }));
const migrated = mount(oldKey);
assert.deepStrictEqual(plain(migrated.api.getOptions().columns), ['seq', 'req_no', 'amount']);
assert.equal(migrated.api.getOptions().measure, 'total');
assert.equal(migrated.api.getRequestParams().report_columns, 'seq,req_no,amount');
assert.equal(migrated.api.getOptions().mode, 'remaining');
select(migrated, 'measure', 'allowance');
assert.equal(JSON.parse(saved.get(oldKey)).version, 1);
assert.deepStrictEqual(JSON.parse(saved.get(oldKey)).options.columns, ['seq', 'req_no', 'amount']);
assert.equal(JSON.parse(saved.get(oldKey)).options.measure, 'allowance');
saved.set('legacy-base-only', JSON.stringify({ version: 1, options: { columns: ['base'] } }));
const baseOnly = mount('legacy-base-only');
assert.deepStrictEqual(plain(baseOnly.api.getOptions().columns), ['amount']);
assert.equal(baseOnly.api.getOptions().measure, 'total', 'Old base column selection must not silently switch the default measure');
saved.set('legacy-string-columns', JSON.stringify({ version: 1, options: { columns: 'allowance,seq,total,base', measure: 'invalid' } }));
const legacyString = mount('legacy-string-columns');
assert.deepStrictEqual(plain(legacyString.api.getOptions().columns), ['seq', 'amount']);
assert.equal(legacyString.api.getOptions().measure, 'total');

// Storage can be disabled by browser policy or quota without breaking the UI.
storage.failRead = true;
const blocked = mount(keyA);
assert.deepStrictEqual(plain(blocked.api.getOptions()), config.default_options);
storage.failRead = false; storage.failWrite = true;
assert.doesNotThrow(() => select(blocked, 'measure', 'base'));
assert.equal(blocked.api.getOptions().measure, 'base');
assert.equal(blocked.changes.length, 1);
storage.failWrite = false;

// Server-normalized options can be applied without reload/persistence loops.
const storedBefore = saved.get('reordered'), changesBefore = ordered.changes.length;
ordered.api.applyOptions(config.default_options, { silent: true, persist: false });
assert.equal(ordered.changes.length, changesBefore);
assert.equal(saved.get('reordered'), storedBefore);
ordered.api.setBusy(true);
assert.equal(ordered.host.getAttribute('aria-busy'), 'true');
ordered.api.setBusy(false);
assert.equal(ordered.host.getAttribute('aria-busy'), 'false');

console.log('PASS: actual options mount; measure selection/migration, per-user/report preferences, presets, reset, nonempty columns, normalization, inaccessible storage and silent server updates');
