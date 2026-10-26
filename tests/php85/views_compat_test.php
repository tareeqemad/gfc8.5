<?php
/** Offline PHP 8.5 rendering probes. Never loads CodeIgniter, Oracle, or network clients. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
define('BASEPATH', dirname(__DIR__, 2) . '/system/');
$root = dirname(__DIR__, 2);
$checks = 0;
function view_assert($condition, $message)
{
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    ++$checks;
}
function base_url($path = '') { return '/gfc/' . $path; }
function HaveAccess($path) { return true; }
function date_format_exp() { return ''; }
function sec_scripts($scripts) {}
class modules
{
    public static function run(...$arguments) { return ''; }
}
function render_compat_view($relative, array $data)
{
    global $root;
    extract($data, EXTR_SKIP);
    ob_start();
    try {
        require $root . '/application/modules/' . $relative;
        return ob_get_contents();
    } finally {
        ob_end_clean();
    }
}

foreach (['hr/views/evaluation_emps_structure_print.php', 'settings/views/get_user_menus_page.php'] as $view) {
    $html = render_compat_view($view, ['page_rows' => []]);
    view_assert(str_contains($html, '<table'), $view . ': empty results must keep the table structure.');
    view_assert(!str_contains($html, '<td>'), $view . ': empty results must contain no invented rows.');
    $html = render_compat_view($view, ['page_rows' => [
        ['L1' => 'الإدارة', 'L2' => 'قسم 1', 'L3' => null],
        ['L1' => 'الإدارة', 'L2' => 'قسم 2', 'L3' => null],
    ]]);
    view_assert(substr_count($html, '<th>') === 2, $view . ': hide only wholly empty hierarchy columns.');
    view_assert(substr_count($html, '<td>') === 4, $view . ': retain both populated rows and columns.');
    view_assert(str_contains($html, 'قسم 1') && str_contains($html, 'قسم 2'), $view . ': preserve Arabic labels.');
}

$job = ['SER' => '7', 'ATTACHMENT_SER' => '17', 'ADS_NAME' => 'مهندس', 'DEADLINE' => '31/10/2026', 'ADS_DESCRIPTION' => null];
$html = render_compat_view('internal_jobs/views/job_ads_show.php', [
    'master_tb_data' => [$job], 'title' => 'إعلان', 'action' => 'edit',
]);
view_assert(preg_match('/<textarea[^>]*name="ads_description"[^>]*>\s*<\/textarea>/', $html) === 1,
    'An optional Oracle NULL job description must render as an empty textarea.');
$job['ADS_DESCRIPTION'] = '  شروط الوظيفة  ';
$html = render_compat_view('internal_jobs/views/job_ads_show.php', [
    'master_tb_data' => [$job], 'title' => 'إعلان', 'action' => 'edit',
]);
view_assert(str_contains($html, 'شروط الوظيفة'), 'A populated job description must remain visible.');
$html = render_compat_view('internal_jobs/views/job_ads_show.php', [
    'title' => 'إعلان', 'action' => 'index',
]);
view_assert(str_contains($html, '/job_ads/create'), 'The new job form must preserve its create action.');

// The dependent view declares a date helper, so render once with null and normal date rows.
$dependent = array_fill_keys(['SER', 'IDNO_RELATIVE', 'FNAME_ARB', 'SNAME_ARB', 'TNAME_ARB', 'LNAME_ARB',
    'SEX_CD_NAME', 'RELATIVE_CD_NAME', 'SOCIAL_STATUS_CD_NAME', 'DATE_STATUS', 'STATUS_LIVE', 'DETH_DT',
    'ADOPT_NAME', 'EMP_ID'], '');
$dependent += ['ADOPT' => 0, 'FROM_DATE' => null, 'TO_DATE' => null];
$dated = array_replace($dependent, ['FROM_DATE' => '01/10/2026', 'TO_DATE' => '31/12/2026']);
$html = render_compat_view('hr/views/dependent_page.php', ['page_rows' => [$dependent, $dated]]);
view_assert(substr_count($html, 'value=""') >= 2, 'Missing dependent dates must remain blank.');
view_assert(str_contains($html, 'value="10/2026"') && str_contains($html, 'value="12/2026"'),
    'Dependent dates must retain their existing month/year display.');

// These three view headers declare identically named barcode helpers. Separate namespaces
// let us exercise the real source without loading any controller or redefining functions.
foreach (['output', 'transport', 'return'] as $kind) {
    $view = $root . '/application/modules/stores/views/stores_class_' . $kind . '_details.php';
    $source = file_get_contents($view);
    $headerEnd = strpos($source, '?>');
    $namespace = 'Php85ViewBarcode_' . $kind;
    $output_details = $transport_details = $return_details = [];
    eval('namespace ' . $namespace . ';' . substr($source, 5, $headerEnd - 5));
    $selectCodes = $namespace . '\\select_codes_ser';
    $html = $selectCodes(null, null);
    view_assert(str_contains($html, '<select') && !str_contains($html, '<option'),
        $kind . ': absent barcode lists must render an empty selector.');
    $html = $selectCodes('001,002', null);
    view_assert(substr_count($html, '<option') === 2 && str_contains($html, 'value="001"'),
        $kind . ': preserve leading zeros and available codes.');
    $html = $selectCodes('001,002', '002');
    view_assert(substr_count($html, '<option') === 2 && str_contains($html, 'value="002" selected'),
        $kind . ': selected codes must remain selected and appear once.');
    if ($kind !== 'return') {
        $countCodes = $namespace . '\\cnt_barcode';
        view_assert($countCodes(null) === 0 && $countCodes('') === 0 && $countCodes('  ') === 0,
            $kind . ': null, empty and whitespace barcode lists must count as zero.');
        view_assert($countCodes('001,002') === 2 && $countCodes('0') === 1,
            $kind . ': barcode counting must preserve numeric strings and populated lists.');
    }
}

echo 'View compatibility: ' . $checks . ' checks passed on PHP ' . PHP_VERSION . ".\n";
