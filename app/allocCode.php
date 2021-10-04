<?php
require '/var/www/tnda-manager/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// $inputFileType = 'Xls';
$inputFileType = 'Xlsx';
//    $inputFileType = 'Xml';
//    $inputFileType = 'Ods';
//    $inputFileType = 'Slk';
//    $inputFileType = 'Gnumeric';
//    $inputFileType = 'Csv';
$inputFileName = '/var/www/tnda-manager/public/files/code_gen_2021-09-28.xlsx';

/**  Create a new Reader of the type defined in $inputFileType  **/
$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
/**  Load $inputFileName to a Spreadsheet Object  **/
$spreadsheet = $reader->load($inputFileName);

$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('B1', '676903');
$writer = new Xlsx($spreadsheet);
$writer->save('hello world.xlsx');