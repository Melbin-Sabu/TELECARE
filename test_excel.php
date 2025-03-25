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
    echo "File created successfully!";
} else {
    echo "File creation failed!";
}
?>
