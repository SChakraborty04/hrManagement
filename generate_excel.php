<?php
require 'vendor/autoload.php';
require_once 'db_connect.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Get the timestamp from the URL parameter
$timestamp = isset($_GET['timestamp']) ? $_GET['timestamp'] : date('Y-m-d-H-i-s');

// Fetch trainees data
$stmt = $pdo->query("SELECT t.*, u.id as user_id, u.username, u.email, u.phone, u.role,
                     f.username as faculty_name
                     FROM trainees t 
                     LEFT JOIN users u ON t.user_id = u.id 
                     LEFT JOIN users f ON t.faculty_id = f.id");
$trainees = $stmt->fetchAll();

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();

// Set document properties
$spreadsheet->getProperties()->setCreator('HR Management System')
    ->setLastModifiedBy('HR Management System')
    ->setTitle('Trainees Data')
    ->setSubject('Trainees Data')
    ->setDescription('Trainees data exported from HR Management System');

// Add some data
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'ID');
$sheet->setCellValue('B1', 'User ID');
$sheet->setCellValue('C1', 'Username');
$sheet->setCellValue('D1', 'Role');
$sheet->setCellValue('E1', 'Email');
$sheet->setCellValue('F1', 'Phone');
$sheet->setCellValue('G1', 'Name');
$sheet->setCellValue('H1', 'Faculty');
$sheet->setCellValue('I1', 'Project');
$sheet->setCellValue('J1', 'Project Completed');
$sheet->setCellValue('K1', 'Certificate Issued');
$sheet->setCellValue('L1', 'Access Code');
$sheet->setCellValue('M1', 'Access Email');
$sheet->setCellValue('N1', 'Access Phone');

// Add data from database
$row = 2;
foreach ($trainees as $trainee) {
    $sheet->setCellValue('A' . $row, $trainee['id']);
    $sheet->setCellValue('B' . $row, $trainee['user_id']);
    $sheet->setCellValue('C' . $row, $trainee['username']);
    $sheet->setCellValue('D' . $row, $trainee['role']);
    $sheet->setCellValue('E' . $row, $trainee['email']);
    $sheet->setCellValue('F' . $row, $trainee['phone']);
    $sheet->setCellValue('G' . $row, $trainee['name']);
    $sheet->setCellValue('H' . $row, $trainee['faculty_name'] ?? 'Not Assigned');
    $sheet->setCellValue('I' . $row, $trainee['project'] ?? 'Not Assigned');
    $sheet->setCellValue('J' . $row, $trainee['project_completed'] ? 'Yes' : 'No');
    $sheet->setCellValue('K' . $row, $trainee['certificate_issued'] ? 'Yes' : 'No');
    $sheet->setCellValue('L' . $row, $trainee['access_code']);
    $sheet->setCellValue('M' . $row, $trainee['access_email']);
    $sheet->setCellValue('N' . $row, $trainee['access_phone']);
    $row++;
}

// Auto-size columns
foreach (range('A', 'N') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Set the header for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="trainees_data_' . $timestamp . '.xlsx"');
header('Cache-Control: max-age=0');

// Create Excel file
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

