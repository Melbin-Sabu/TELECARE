<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Hello World!');

// Save the Excel file
$fileName = 'hello_world.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($fileName);

// Check if the file was created
if (file_exists($fileName)) {
    echo "File created successfully!<br>";

    // Force download the file in the browser
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
    readfile($fileName);
    exit;
} else {
    echo "File creation failed!";
}
?>
