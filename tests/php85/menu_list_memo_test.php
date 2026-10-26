<?php
/** Offline regression checks for request-local menu query reuse. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$project = dirname(__DIR__, 2);
define('BASEPATH', $project . '/system/');
defined('SQLT_CHR') || define('SQLT_CHR', 1);
defined('OCI_B_CURSOR') || define('OCI_B_CURSOR', 116);

class MenuMemoSessionFixture
{
    public $values = [];
    public function userdata($key) { return $this->values[$key] ?? null; }
}
class MenuMemoLoaderFixture
{
    public $models = [];
    public function model($name) { $this->models[] = $name; }
}
class MenuMemoConnectionFixture
{
    public $calls = [];
    public function excuteProcedures($package, $procedure, $params)
    {
        $this->calls[] = [$package, $procedure, $params];
        return ['MSG_OUT' => '1'];
    }
}
class MenuMemoQueryFixture
{
    public $calls = [];
    public $handler;
    public $result = [['MENU_NO' => '20', 'MENU_FULL_CODE' => 'module/action', 'MENU_ADD' => 'Menu']];
    public function general_get($package, $procedure, $params)
    {
        $this->calls[] = [$package, $procedure, $params];
        if ($this->handler !== null) { return ($this->handler)($params); }
        return $this->result;
    }
}

$menuMemoApp = new stdClass();
$menuMemoApp->conn = new MenuMemoConnectionFixture(); // No database implementation is present.
$menuMemoApp->session = new MenuMemoSessionFixture();
$menuMemoApp->session->values = ['db_instance' => 'fixture-a', 'user_data' => (object)['id' => 7]];
$menuMemoApp->load = new MenuMemoLoaderFixture();
$menuMemoApp->New_rmodel = new MenuMemoQueryFixture();
function &get_instance() { global $menuMemoApp; return $menuMemoApp; }
function &get_my_instance() { global $menuMemoApp; return $menuMemoApp; }

require BASEPATH . 'core/Model.php';
require $project . '/application/core/MY_Model.php';
require $project . '/application/modules/settings/models/Sysmenus_model.php';

$checks = 0;
function menu_memo_check($condition, $message)
{
    global $checks;
    if (!$condition) { throw new RuntimeException($message); }
    ++$checks;
}

$model = new Sysmenus_model();
$query = $menuMemoApp->New_rmodel;
$base = [10, 1, 2, 7, 'menu', 5];
$original = $model->getList(...$base);
menu_memo_check($model->getList(...$base) === $original && count($query->calls) === 1,
    'Repeated menu reads must reuse the first result in this request.');
menu_memo_check($menuMemoApp->load->models === ['Root/New_rmodel'],
    'The actual model constructor must retain its existing dependency.');
menu_memo_check($query->calls[0][0] === 'setting_pkg' && $query->calls[0][1] === 'SYSTEM_MENU_TB_GET_LIST',
    'The first read must execute the original procedure.');
menu_memo_check(array_column($query->calls[0][2], 'value') === ['10', '2', '1', '7', 'menu', '5', 'cursor', 'MSG_OUT'],
    'The original bind order and string conversions must remain unchanged.');

// The tree renderer changes rows after reading them; the memo must keep raw rows.
$original[0]['MENU_FULL_CODE'] = 'https://fixture.invalid/module/action';
$original[0]['ICONS'] = '<i></i>';
$original[0]['subs'] = [['MENU_NO' => '21']];
menu_memo_check($model->getList(...$base) === $query->result,
    'Renderer edits to returned arrays must not leak into subsequent reads.');

// Even values that Oracle receives as the same string have distinct typed keys.
foreach (array_keys($base) as $index) {
    foreach ([null, 0, '0', '', false] as $value) {
        $args = $base;
        $args[$index] = $value;
        $before = count($query->calls);
        $model->getList(...$args);
        $model->getList(...$args);
        menu_memo_check(count($query->calls) === $before + 1,
            'Each typed value of argument ' . $index . ' must have an independent memo entry.');
    }
}

$before = count($query->calls);
$menuMemoApp->session->values['db_instance'] = 'fixture-b';
$model->getList(...$base);
$menuMemoApp->session->values['user_data'] = (object)['id' => 8];
$model->getList(...$base);
$menuMemoApp->session->values['user_data'] = (object)['id' => '8'];
$model->getList(...$base);
menu_memo_check(count($query->calls) === $before + 3,
    'Database and authenticated user context, including ID type, must be isolated.');
$menuMemoApp->session->values['user_data'] = false;
$model->getList(...$base);
$model->getList(...$base);
menu_memo_check(count($query->calls) === $before + 4,
    'An absent authenticated user must be supported without warnings.');
$menuMemoApp->session->values = ['db_instance' => 'fixture-a', 'user_data' => (object)['id' => 7]];
$model->getList(...$base);
menu_memo_check(count($query->calls) === $before + 4,
    'Returning to an existing context must reuse its own entries.');

$emptyArgs = [999, 1, 2, 7, 'menu', 5];
$query->result = [];
$before = count($query->calls);
menu_memo_check($model->getList(...$emptyArgs) === [] && $model->getList(...$emptyArgs) === []
    && count($query->calls) === $before + 1, 'Empty leaf results must also be reused.');

$query->result = [['MENU_NO' => '30', 'MENU_FULL_CODE' => 'changed/action', 'MENU_ADD' => 'Changed']];
$model->clearMenuListMemo();
$before = count($query->calls);
menu_memo_check($model->getList(...$base) === $query->result
    && $model->getList(...$emptyArgs) === $query->result && count($query->calls) === $before + 2,
    'Invalidation must refresh both populated and empty entries after mutations.');

$otherModel = new Sysmenus_model();
$before = count($query->calls);
$otherModel->getList(...$base);
menu_memo_check(count($query->calls) === $before + 1,
    'A new model instance must not inherit request memo entries.');

foreach ([null, false] as $failure) {
    $model->clearMenuListMemo();
    $query->result = $failure;
    $before = count($query->calls);
    menu_memo_check($model->getList(...$base) === $failure && $model->getList(...$base) === $failure
        && count($query->calls) === $before + 2, 'Failed/non-array results must not be cached.');
}

// A sorted synthetic result models the documented procedure filters without Oracle.
$rows = [
    ['MENU_NO' => '90', 'MENU_PARENT_NO' => null, 'SORT' => 1, 'VIEW_MENU' => 1, 'MAIN_MENU' => 0, 'ID_SYSTEM' => 5, 'USERS' => [7]],
    ['MENU_NO' => '12', 'MENU_PARENT_NO' => '10', 'SORT' => 2, 'VIEW_MENU' => 1, 'MAIN_MENU' => 0, 'ID_SYSTEM' => 5, 'USERS' => [7]],
    ['MENU_NO' => '10', 'MENU_PARENT_NO' => '0', 'SORT' => 3, 'VIEW_MENU' => 1, 'MAIN_MENU' => 0, 'ID_SYSTEM' => 5, 'USERS' => [7]],
    ['MENU_NO' => '11', 'MENU_PARENT_NO' => '10', 'SORT' => 3, 'VIEW_MENU' => 1, 'MAIN_MENU' => 0, 'ID_SYSTEM' => 5, 'USERS' => [7]],
    ['MENU_NO' => '21', 'MENU_PARENT_NO' => '20', 'SORT' => 4, 'VIEW_MENU' => 1, 'MAIN_MENU' => 0, 'ID_SYSTEM' => 5, 'USERS' => [7]],
    ['MENU_NO' => '88', 'MENU_PARENT_NO' => '10', 'SORT' => 5, 'VIEW_MENU' => 1, 'MAIN_MENU' => 0, 'ID_SYSTEM' => 5, 'USERS' => [8]],
    ['MENU_NO' => '31', 'MENU_PARENT_NO' => '10', 'SORT' => 6, 'VIEW_MENU' => 1, 'MAIN_MENU' => 0, 'ID_SYSTEM' => 6, 'USERS' => [7]],
    ['MENU_NO' => '41', 'MENU_PARENT_NO' => '10', 'SORT' => 7, 'VIEW_MENU' => 0, 'MAIN_MENU' => 1, 'ID_SYSTEM' => 5, 'USERS' => [7]],
];
$query->handler = static function ($params) use ($rows) {
    [$parent, $main, $view, $user, $code, $system] = array_column($params, 'value');
    return array_values(array_filter($rows, static function ($row) use ($parent, $main, $view, $user, $code, $system) {
        if ($parent !== '' && $parent !== '-1' && $code === '-1'
            && ($row['MENU_PARENT_NO'] === null || (float)$row['MENU_PARENT_NO'] !== (float)$parent)) return false;
        if ($code !== '-1' && $code !== '' && $row['MENU_PARENT_NO'] !== '10') return false;
        if ($main !== '-1' && $main !== '' && (string)$row['MAIN_MENU'] !== $main) return false;
        if ($view !== '-1' && $view !== '' && (string)$row['VIEW_MENU'] !== $view
            && (string)$row['MAIN_MENU'] !== $main) return false;
        if ($user !== '-1' && $user !== '' && !in_array((int)$user, $row['USERS'], true)) return false;
        return $system === '' || $system === '0' || (string)$row['ID_SYSTEM'] === $system;
    }));
};
$model->clearMenuListMemo();
$query->calls = [];
$root = $model->getList(0, null, null, null, -1, 5);
menu_memo_check(count($query->calls) === 1 && $query->calls[0][2][0]['value'] === '0'
    && array_column($root, 'MENU_NO') === ['10'], 'The root lookup with no user filter must stay a direct query.');
$children = $model->getList(10, 1, -1, 7, -1, 5);
menu_memo_check(array_column($children, 'MENU_NO') === ['12', '11'],
    'Bulk buckets must preserve the procedure ordering and all user/view/main/system filters.');
menu_memo_check(count($query->calls) === 2
    && array_column($query->calls[1][2], 'value') === ['-1', '-1', '1', '7', '-1', '5', 'cursor', 'MSG_OUT'],
    'Only parent may change to -1 in the single filtered bulk query.');
menu_memo_check(array_column($model->getList('20', 1, -1, 7, -1, 5), 'MENU_NO') === ['21']
    && $model->getList(21, 1, -1, 7, -1, 5) === [] && count($query->calls) === 2,
    'Different parents and empty leaves must reuse the same filtered bulk query.');
menu_memo_check(array_column($model->getList(0, 1, -1, 7, -1, 5), 'MENU_NO') === ['10'],
    'SQL NULL parent rows must never leak into the parent-zero bucket.');
$children[0]['MENU_NO'] = 'changed';
$children[0]['subs'] = [];
menu_memo_check(array_column($model->getList(10, 1, -1, 7, -1, 5), 'MENU_NO') === ['12', '11'],
    'Returned bucket edits must not mutate cached raw rows.');

$before = count($query->calls);
menu_memo_check(array_column($model->getList(10, 1, -1, 8, -1, 5), 'MENU_NO') === ['88']
    && count($query->calls) === $before + 1, 'Different target users must use separate filtered bulk results.');
menu_memo_check(array_column($model->getList(10, 1, -1, 7, -1, 6), 'MENU_NO') === ['31'],
    'Different systems must not reuse another system bulk result.');
menu_memo_check(array_column($model->getList(10, 0, 1, 7, -1, 5), 'MENU_NO') === ['41'],
    'View and main filters must remain intact in bulk retrieval.');
$before = count($query->calls);
$model->getList(10, 1, -1, 7, '-1', 5);
menu_memo_check(count($query->calls) === $before + 1,
    'Integer and string code filters must retain independent typed bulk keys.');
$menuMemoApp->session->values['db_instance'] = 'fixture-b';
$model->getList(10, 1, -1, 7, -1, 5);
$menuMemoApp->session->values['user_data'] = (object)['id' => 9];
$model->getList(10, 1, -1, 7, -1, 5);
menu_memo_check(count($query->calls) === $before + 3,
    'Database and current authenticated user changes must isolate bulk caches too.');

$model->clearMenuListMemo();
foreach ([null, '', false, 1.5, '01', '+1', '1.0', -1, '-1'] as $parent) {
    $before = count($query->calls);
    $model->getList($parent, 1, -1, 7, -1, 5);
    menu_memo_check(count($query->calls) === $before + 1
        && $query->calls[$before][2][0]['value'] === (string)$parent,
        'Noncanonical or no-filter parents must retain their original direct query.');
}
menu_memo_check(count($model->getList(null, 1, -1, 7, -1, 5)) === 5,
    'A NULL parent must retain Oracle no-parent-filter semantics.');
$before = count($query->calls);
$codeRows = $model->getList(20, 1, -1, 7, 'finance', 5);
menu_memo_check(array_column($codeRows, 'MENU_NO') === ['12', '11'] && count($query->calls) === $before + 1
    && $query->calls[$before][2][0]['value'] === '20',
    'A code filter must use the original query, which can ignore the numeric parent.');
foreach (['', false] as $user) {
    $before = count($query->calls);
    $model->getList(0, 1, -1, $user, -1, 5);
    menu_memo_check(count($query->calls) === $before + 1 && $query->calls[$before][2][0]['value'] === '0',
        'Empty user filters must not trigger unfiltered bulk loading.');
}

// Missing parent metadata must fall back to the old query, not invent an empty menu.
$model->clearMenuListMemo();
$query->handler = static function ($params) {
    return $params[0]['value'] === '-1' ? [['MENU_NO' => 'unexpected']]
        : [['MENU_NO' => 'fallback-' . $params[0]['value']]];
};
$before = count($query->calls);
menu_memo_check($model->getList(10, 1, -1, 7, -1, 5) === [['MENU_NO' => 'fallback-10']]
    && count($query->calls) === $before + 2, 'Missing bulk metadata must execute the original parent query.');
menu_memo_check($model->getList(20, 1, -1, 7, -1, 5) === [['MENU_NO' => 'fallback-20']]
    && count($query->calls) === $before + 3, 'Unusable bulk metadata must fall back for every distinct parent.');
foreach (['01', '1.0', 1.0, true] as $unexpectedParent) {
    $model->clearMenuListMemo();
    $query->handler = static function ($params) use ($unexpectedParent) {
        return $params[0]['value'] === '-1' ? [['MENU_NO' => 'unexpected', 'MENU_PARENT_NO' => $unexpectedParent]]
            : [['MENU_NO' => 'fallback']];
    };
    $before = count($query->calls);
    menu_memo_check($model->getList(1, 1, -1, 7, -1, 5) === [['MENU_NO' => 'fallback']]
        && count($query->calls) === $before + 2,
        'Noncanonical parent metadata must fall back to Oracle numeric comparison.');
}
$model->clearMenuListMemo();
$query->handler = static function ($params) { return []; };
$before = count($query->calls);
menu_memo_check($model->getList(10, 1, -1, 7, -1, 5) === []
    && $model->getList(20, 1, -1, 7, -1, 5) === [] && count($query->calls) === $before + 1,
    'An empty filtered bulk result must satisfy all parent buckets without more queries.');
$model->clearMenuListMemo();
$query->handler = static function ($params) { return [['MENU_NO' => 'fresh', 'MENU_PARENT_NO' => '10', 'SORT' => '1']]; };
$before = count($query->calls);
menu_memo_check($model->getList(10, 1, -1, 7, -1, 5) === [['MENU_NO' => 'fresh', 'MENU_PARENT_NO' => '10', 'SORT' => '1']]
    && count($query->calls) === $before + 1, 'Mutation invalidation must clear previously empty bulk buckets.');

$sortRows = [
    ['MENU_NO' => 'a', 'MENU_PARENT_NO' => '10', 'SORT' => '1'],
    ['MENU_NO' => 'b', 'MENU_PARENT_NO' => '10', 'SORT' => 1],
    ['MENU_NO' => 'c', 'MENU_PARENT_NO' => '20', 'SORT' => '2'],
    ['MENU_NO' => 'd', 'MENU_PARENT_NO' => '20', 'SORT' => '3'],
    ['MENU_NO' => 'e', 'MENU_PARENT_NO' => '30', 'SORT' => null],
    ['MENU_NO' => 'f', 'MENU_PARENT_NO' => '30', 'SORT' => null],
    ['MENU_NO' => 'g', 'MENU_PARENT_NO' => '40'],
    ['MENU_NO' => 'null-root', 'MENU_PARENT_NO' => null, 'SORT' => '1'],
    ['MENU_NO' => 'root', 'MENU_PARENT_NO' => '0', 'SORT' => '1'],
    ['MENU_NO' => 'h', 'MENU_PARENT_NO' => '50', 'SORT' => null],
    ['MENU_NO' => 'i', 'MENU_PARENT_NO' => '50', 'SORT' => '0'],
];
$query->handler = static function ($params) use ($sortRows) {
    $parent = $params[0]['value'];
    if ($parent === '-1') return $sortRows;
    // A parent query can produce a different order for ties; neither query defines a tie-breaker.
    return array_reverse(array_values(array_filter($sortRows, static function ($row) use ($parent) {
        return $row['MENU_PARENT_NO'] === $parent;
    })));
};
$model->clearMenuListMemo();
$before = count($query->calls);
menu_memo_check(array_column($model->getList(10, 1, -1, 7, -1, 5), 'MENU_NO') === ['a', 'b']
    && count($query->calls) === $before + 1, 'Tied sibling SORT values must retain the bulk procedure order without additional queries.');
$model->getList(10, 1, -1, 7, -1, 5);
menu_memo_check(count($query->calls) === $before + 1, 'Repeated tied-parent results must be memoized too.');
menu_memo_check(array_column($model->getList(20, 1, -1, 7, -1, 5), 'MENU_NO') === ['c', 'd']
    && count($query->calls) === $before + 1, 'Unique sibling SORT values must use the same bulk result without another query.');
menu_memo_check(array_column($model->getList(30, 1, -1, 7, -1, 5), 'MENU_NO') === ['e', 'f']
    && count($query->calls) === $before + 1, 'NULL SORT ties must retain bulk order without PHP sorting.');
menu_memo_check(array_column($model->getList(40, 1, -1, 7, -1, 5), 'MENU_NO') === ['g']
    && count($query->calls) === $before + 1, 'Grouping needs no SORT field because ordering remains the procedure responsibility.');
menu_memo_check(array_column($model->getList(0, 1, -1, 7, -1, 5), 'MENU_NO') === ['root']
    && count($query->calls) === $before + 1, 'A NULL parent must not enter parent zero even when SORT values match.');
menu_memo_check(array_column($model->getList(50, 1, -1, 7, -1, 5), 'MENU_NO') === ['h', 'i']
    && count($query->calls) === $before + 1, 'PHP must not reinterpret or reorder NULL and zero SORT values.');

// Exercise the actual mutation methods with a connection stub, including previously empty leaves.
$revision = 0;
$query->handler = static function ($params) use (&$revision) {
    $result = [['MENU_NO' => 'version-' . $revision, 'MENU_PARENT_NO' => '20', 'SORT' => '1']];
    if ($revision > 0) {
        $result[] = ['MENU_NO' => 'new-' . $revision, 'MENU_PARENT_NO' => (string)(100 + $revision), 'SORT' => '1'];
    }
    return $result;
};
$model->clearMenuListMemo();
foreach ([
    ['create', [[]], 'SYSTEM_MENU_TB_INSERT'],
    ['edit', [[]], 'SYSTEM_MENU_TB_UPDATE'],
    ['sort', [20, 1], 'SYSTEM_MENU_TB_SORT_UPDATE'],
    ['delete', [20], 'SYSTEM_MENU_TB_DELETE'],
] as [$method, $arguments, $procedure]) {
    $model->getList(20, 1, -1, 7, -1, 5);
    $newParent = 101 + $revision;
    menu_memo_check($model->getList($newParent, 1, -1, 7, -1, 5) === [],
        $method . ': the new parent starts as a cached empty leaf.');
    ++$revision;
    $before = count($query->calls);
    $writesBefore = count($menuMemoApp->conn->calls);
    menu_memo_check($model->{$method}(...$arguments) === '1'
        && count($menuMemoApp->conn->calls) === $writesBefore + 1
        && $menuMemoApp->conn->calls[$writesBefore][1] === $procedure,
        $method . ': invalidation must preserve the original procedure and return value.');
    menu_memo_check(array_column($model->getList(20, 1, -1, 7, -1, 5), 'MENU_NO') === ['version-' . $revision]
        && count($query->calls) === $before + 1,
        $method . ': both row memo and bulk buckets must refresh after the actual mutation.');
    menu_memo_check(array_column($model->getList($newParent, 1, -1, 7, -1, 5), 'MENU_NO') === ['new-' . $revision]
        && count($query->calls) === $before + 1,
        $method . ': invalidation must also discard cached empty leaves.');
}

echo 'Menu list memo: ' . $checks . " checks passed (offline).\n";
