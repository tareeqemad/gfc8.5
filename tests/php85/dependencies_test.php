<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Run with PHP 8.5.12 and the project's php.ini. No database/network is used.
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return false;
});

$root = dirname(__DIR__, 2);
require $root . '/application/third_party/phpoffice/vendor/autoload.php';

function dependency_check($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$output = tempnam($root . '/temp', 'gfc-php85-');
try {
    $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $book->getActiveSheet();
    $sheet->setTitle('Payroll');
    $sheet->setRightToLeft(true);
    $sheet->setCellValue('A1', 'الموظف');
    $sheet->setCellValueExplicit('A2', '001234', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValue([2, 2], 123.45);
    $sheet->setCellValue('C2', '=SUM(B2,10)');
    $sheet->getStyle('B2')->getNumberFormat()->setFormatCode('0.00');
    $sheet->getStyle('A1:C1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1E293B');
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($output);
    $loaded = \PhpOffice\PhpSpreadsheet\IOFactory::load($output);
    $result = $loaded->getActiveSheet();
    dependency_check($result->getCell('A1')->getValue() === 'الموظف', 'Arabic label round trip failed');
    dependency_check($result->getCell('A2')->getValue() === '001234', 'Leading-zero identifier round trip failed');
    dependency_check(abs($result->getCell('C2')->getCalculatedValue() - 133.45) < 0.001, 'Spreadsheet formula round trip failed');
    dependency_check($result->getRightToLeft(), 'RTL setting round trip failed');
    $loaded->disconnectWorksheets();
    $book->disconnectWorksheets();

    require $root . '/application/libraries/xlsxwriter.php';
    $writer = new XLSXWriter();
    $writer->writeSheet([['الموظف', '001234', 123.45]], 'Legacy export');
    $writer->writeToFile($output);
    $legacy = \PhpOffice\PhpSpreadsheet\IOFactory::load($output);
    dependency_check($legacy->getActiveSheet()->getCell('B1')->getValue() === '001234', 'Legacy writer lost leading zeros');
    $legacy->disconnectWorksheets();

    require $root . '/barcode/class/BCGcode128.barcode.php';
    require $root . '/barcode/class/BCGDrawing.php';
    $barcode = new BCGcode128();
    $barcode->parse([CODE128_A, '1234', [CODE128_B, 'ABCD']]);
    $drawing = new BCGDrawing($output, new BCGColor('white'));
    $drawing->setBarcode($barcode);
    $drawing->draw();
    $drawing->finish(BCGDrawing::IMG_FORMAT_PNG);
    dependency_check(getimagesize($output)[2] === IMAGETYPE_PNG, 'Code128 PNG generation failed');
    $drawing->destroy();
    unset($drawing);

    require $root . '/barcode/qrcode/qrlib.php';
    QRcode::png('GFC-PHP85-001234', $output);
    dependency_check(getimagesize($output)[2] === IMAGETYPE_PNG, 'QR PNG generation failed');

    $_GET = ['text' => '001234', 'filepath' => $output];
    require $root . '/barcode.php';
    dependency_check(getimagesize($output)[2] === IMAGETYPE_PNG, 'Barcode endpoint PNG generation failed');

    $client = new \Jaspersoft\Client\Client('http://localhost/unused', 'test', 'test');
    dependency_check($client->getURL() === 'http://localhost/unused/rest_v2', 'Jasper client construction failed');
    foreach (['repositoryService', 'userService', 'organizationService', 'roleService', 'jobService', 'permissionService', 'optionsService', 'reportService', 'importExportService', 'queryService'] as $service) {
        dependency_check(is_object($client->$service()), 'Jasper service construction failed: ' . $service);
    }
    $request = $client->getService();
    $request->setVerb('INVALID');
    try {
        $request->execute(); // Invalid verb fails before network I/O, exercising cURL cleanup.
        throw new RuntimeException('Jasper invalid verb was accepted');
    } catch (InvalidArgumentException $expected) {
        // Expected: no network request has been made.
    }

    define('BASEPATH', $root . '/system/');
    $encryptionCi = new stdClass();
    $encryptionCi->config = new class {
        public function item($name) { return 'php85-test-key'; }
    };
    $encryptionCi->load = new class {
        public function config($name, $sections) {}
    };
    function &get_instance() { return $GLOBALS['encryptionCi']; }
    require $root . '/application/libraries/Encrypt.php';
    $encrypt = new CI_Encrypt();
    $ciphertext = $encrypt->encode('GFC-001234');
    dependency_check($encrypt->decode($ciphertext) === 'GFC-001234', 'Existing encryption round trip failed');
    dependency_check($encrypt->decode($ciphertext, 'wrong-key') === false, 'Wrong encryption key was accepted');
    dependency_check($encrypt->decode('invalid.base64') === false, 'Invalid encrypted input was accepted');
    echo "PASS: spreadsheet round trip, legacy XLSX writer, Code128, QR code, barcode endpoint, Jasper client and encryption\n";
} finally {
    if (is_file($output)) {
        unlink($output);
    }
}
