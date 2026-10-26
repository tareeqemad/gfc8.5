<?php
/** Offline regression for the program-card activity partial; no CI/Oracle bootstrap. */
if (PHP_SAPI !== 'cli' || extension_loaded('oci8')) {
    fwrite(STDERR, "Run with PHP CLI -n; OCI must be disabled.\n");
    exit(1);
}
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$args = array_slice($argv, 1);
if ($args && (count($args) !== 2 || $args[0] !== '--modules')) {
    fwrite(STDERR, "Usage: php -n budget_activity_partial_test.php [--modules path/to/modules]\n");
    exit(1);
}
$modulesRoot = $args ? realpath($args[1]) : dirname(__DIR__, 2) . '/application/modules';
if (!$modulesRoot || !is_dir($modulesRoot)) throw new RuntimeException('Modules directory not found.');
define('BASEPATH', dirname(__DIR__, 2) . '/system/');

// Deliberately omit the actual MY_Controller and model constructors: those connect
// to Oracle. Only the real partial action and view below execute against fixtures.
class MY_Controller
{
    public $load;
    public $Activities_model;
    public $Projects_model;
    public $Units_model;
}
function base_url($path = '') { return '/gfc/' . $path; }
$permissionAllowed = false;
$permissionCalls = [];
function HaveAccess($path)
{
    global $permissionAllowed, $permissionCalls;
    $permissionCalls[] = $path;
    return $permissionAllowed;
}
class BudgetActivityListFixture
{
    public $rows;
    public $calls = [];
    public function __construct(array $rows) { $this->rows = $rows; }
    public function get_list($programId)
    {
        $this->calls[] = $programId;
        return $this->rows;
    }
}
class BudgetUnitsFixture
{
    public $activityCalls = [];
    public $projectCalls = [];
    public function activity_unit_get_list($activityId)
    {
        $this->activityCalls[] = $activityId;
        $units = [
            11 => [['SER' => '801', 'UNIT_NAME' => 'وحدة النشاط الأول']],
            22 => [['SER' => '902', 'UNIT_NAME' => 'وحدة النشاط الثاني']],
        ];
        if (!isset($units[$activityId])) throw new RuntimeException('Unexpected activity unit request.');
        return $units[$activityId];
    }
    public function project_unit_get_list($projectId)
    {
        $this->projectCalls[] = $projectId;
        return [['SER' => '777', 'UNIT_NAME' => 'وحدة مشروع مستقلة']];
    }
}
class BudgetPartialLoaderFixture
{
    public $data;
    public $html;
    public function view($view, array $data)
    {
        global $modulesRoot;
        if ($view !== 'program_desc_card_activities_details') throw new RuntimeException('Unexpected view.');
        $this->data = $data;
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $modulesRoot . '/budg_pb/views/' . $view . '.php';
            $this->html = ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}
require $modulesRoot . '/budg_pb/controllers/Program_desc_card.php';

$checks = 0;
function budget_activity_check($condition, $description)
{
    global $checks;
    if (!$condition) throw new RuntimeException($description);
    ++$checks;
}
function budget_partial_fixture(array $activities, array $projects)
{
    $controller = (new ReflectionClass('Program_desc_card'))->newInstanceWithoutConstructor();
    $controller->Activities_model = new BudgetActivityListFixture($activities);
    $controller->Projects_model = new BudgetActivityListFixture($projects);
    $controller->Units_model = new BudgetUnitsFixture();
    $controller->load = new BudgetPartialLoaderFixture();
    return $controller;
}

foreach ([false, true] as $allowed) {
    $permissionAllowed = $allowed;
    $permissionCalls = [];
    $controller = budget_partial_fixture([
        ['ACTIVITY_ID' => 11, 'ACTIVITY_NAME' => 'النشاط الأول'],
        ['ACTIVITY_ID' => 22, 'ACTIVITY_NAME' => 'النشاط الثاني'],
    ], [['PROJECT_ID' => 33]]);
    $controller->public_get_program_activities_details('007');
    $html = $controller->load->html;
    budget_activity_check($permissionCalls === ['/gfc/budg_pb/program_desc_card/add_activity'],
        'Retain the exact existing edit-permission check.');
    budget_activity_check($controller->load->data['can_edit'] === $allowed,
        'Retain both allowed and denied permission results.');
    budget_activity_check($controller->Activities_model->calls === ['007'],
        'Read activities for the supplied program once, preserving the identifier.');
    budget_activity_check($controller->Projects_model->calls === ['007'],
        'Retain the existing project lookup argument.');
    budget_activity_check($controller->Units_model->activityCalls === [11, 22],
        'Read units for each returned activity ID.');
    budget_activity_check($controller->Units_model->projectCalls === [33],
        'Retain the existing project unit lookup argument.');
    budget_activity_check($controller->load->data['program_id'] === '007',
        'Pass the unchanged program identifier to the view.');
    preg_match('/<tbody>(.*?)<\/tbody>/s', $html, $body);
    preg_match_all('/<tr>(.*?)<\/tr>/s', $body[1], $rows);
    budget_activity_check(count($rows[1]) === 2, 'Render exactly the two supplied activities.');
    foreach ([[0, '801', 'وحدة النشاط الأول', 'وحدة النشاط الثاني'],
        [1, '902', 'وحدة النشاط الثاني', 'وحدة النشاط الأول']] as $expected) {
        [$position, $serial, $ownUnit, $otherUnit] = $expected;
        $row = $rows[1][$position];
        budget_activity_check(strpos($row, $ownUnit) !== false && strpos($row, $otherUnit) === false,
            'Each row must display its own activity units only.');
        budget_activity_check(strpos($row, "id='U_ACTIVITY_{$serial}'") !== false,
            'The unit element ID must use the row SER field.');
        budget_activity_check(preg_match("/delete_unit\('ACTIVITY',\s*{$serial}\s*\)/", $row) === 1,
            'Retain the existing delete handler with the same unit SER.');
    }
    budget_activity_check(strpos($html, 'وحدة مشروع مستقلة') === false,
        'Do not mix project units into activity rows.');
    budget_activity_check((strpos($html, 'id="txt_new_activity_name_007"') !== false) === $allowed,
        'The existing permission result controls the add-activity field.');
}

$permissionAllowed = false;
$permissionCalls = [];
$empty = budget_partial_fixture([], []);
$empty->public_get_program_activities_details('007');
preg_match('/<tbody>(.*?)<\/tbody>/s', $empty->load->html, $body);
budget_activity_check(strpos($body[1], '<tr>') === false, 'Empty activities render an empty table.');
budget_activity_check($empty->Units_model->activityCalls === [], 'Do not query units for missing activities.');
budget_activity_check($empty->Units_model->projectCalls === [], 'Do not query units for missing projects.');
budget_activity_check($permissionCalls === ['/gfc/budg_pb/program_desc_card/add_activity'],
    'Empty results preserve the permission check.');

echo "PASS: {$checks} budget activity partial checks; real action/view, synthetic models, no database/network.\n";
