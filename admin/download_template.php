<?php
// Start session and turn off output buffering and compression
ob_clean();
session_start();
include('../includes/db.php');

// Require PhpSpreadsheet autoload
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

try {
    // Create a new spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Book Upload Template');

    // Headers
    $headers = [
        'A1' => 'Title',
        'B1' => 'Author',
        'C1' => 'Genre',
        'D1' => 'Serial Number',
        'E1' => 'Pages',
        'F1' => 'Publication Details',
        'G1' => 'ISBN',
        'H1' => 'Edition',
        'I1' => 'Section',
        'J1' => 'Call Number',
        'K1' => 'Rack Number'
    ];

    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }

    // Style for headers
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 12
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4472C4']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ];
    $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

    // Auto-size columns
    foreach (range('A', 'K') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Sample data
    $sampleData = [
        'A2' => 'Sample Book Title',
        'B2' => 'Sample Author',
        'C2' => 'Fiction',
        'D2' => 'BK001',
        'E2' => '250',
        'F2' => 'Publisher Name, 2024',
        'G2' => '978-0-123456-78-9',
        'H2' => '1st Edition',
        'I2' => 'A',
        'J2' => '813.54',
        'K2' => 'R001'
    ];
    foreach ($sampleData as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }

    // Style sample row
    $sampleStyle = [
        'font' => ['italic' => true, 'color' => ['rgb' => '666666']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F2F2']],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'CCCCCC']
            ]
        ]
    ];
    $sheet->getStyle('A2:K2')->applyFromArray($sampleStyle);

    // Instructions sheet
    $instructionSheet = $spreadsheet->createSheet();
    $instructionSheet->setTitle('Instructions');

    $instructions = [
        'A1' => 'BULK UPLOAD INSTRUCTIONS',
        'A3' => 'Required Fields (must not be empty):',
        'A4' => '• Title (Column A)',
        'A5' => '• Author (Column B)',
        'A6' => '• Genre (Column C)',
        'A7' => '• Serial Number (Column D) - Must be unique',
        'A8' => '• Section (Column I)',
        'A9' => '• Rack Number (Column K)',
        'A11' => 'Optional Fields:',
        'A12' => '• Pages (Column E) - Numeric value',
        'A13' => '• Publication Details (Column F)',
        'A14' => '• ISBN (Column G)',
        'A15' => '• Edition (Column H)',
        'A16' => '• Call Number (Column J)',
        'A18' => 'Important Notes:',
        'A19' => '• First row contains headers - do not modify',
        'A20' => '• Sample data in row 2 - replace with your data',
        'A21' => '• Serial numbers must be unique across all books',
        'A22' => '• Save file as .xlsx or .xls format',
        'A23' => '• Maximum file size: 10MB',
        'A24' => '• Rows with errors will be skipped',
        'A26' => 'Sample Genres: Fiction, Non-Fiction, Science, History, Biography, etc.',
        'A27' => 'Sample Sections: A, B, C, Fiction, Reference, etc.',
        'A28' => 'Sample Rack Numbers: R001, R002, Rack-A1, etc.'
    ];

    foreach ($instructions as $cell => $text) {
        $instructionSheet->setCellValue($cell, $text);
    }

    // Style instructions
    $instructionSheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '4472C4']]
    ]);
    $instructionSheet->getStyle('A3:A28')->applyFromArray([
        'font' => ['size' => 11]
    ]);
    $instructionSheet->getColumnDimension('A')->setWidth(80);

    // Set active sheet to the first one
    $spreadsheet->setActiveSheetIndex(0);

    // Output file
    $filename = 'Book_Upload_Template_' . date('Y-m-d') . '.xlsx';

    // Send headers
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Expires: 0');
    header('Pragma: public');

    // Write file to output
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

    // Clean up
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);
    exit;

} catch (Exception $e) {
    $_SESSION['error_message'] = "Error generating template: " . $e->getMessage();
    header("Location: bulk_upload.php");
    exit;
}
?>
