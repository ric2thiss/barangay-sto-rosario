<?php
/**
 * export_children_masterlist.php
 * Generates an Excel (.xlsx) file matching the official
 * "Child-Friendly Local Governance Assessment – Audit Year 2023" format.
 * Uses the exact column layout: AGE | NO. OF MALE | NO. OF FEMALE | TOTAL
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../vendor/autoload.php';
include("connection.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$conn->set_charset('utf8mb4');

// ── Dynamic audit year ──────────────────────────────────────────────────
$audit_year = date('Y');

// ── Fetch children counts per age 0-17, split by sex ────────────────────
$union_sql = "
    SELECT age, sex FROM residents WHERE age >= 0 AND age <= 17 AND is_deleted = 0
    UNION ALL
    SELECT age, sex FROM barangay_official WHERE status = 'Active' AND age >= 0 AND age <= 17
";

$age_labels = [
    0 => '0-11 MONTHS',
    1 => '1 YEAR OLD',
    2 => '2 YEARS OLD',
    3 => '3 YEARS OLD',
    4 => '4 YEARS OLD',
    5 => '5 YEARS OLD',
    6 => '6 YEARS OLD',
    7 => '7 YEARS OLD',
    8 => '8 YEARS OLD',
    9 => '9 YEARS OLD',
    10 => '10 YEARS OLD',
    11 => '11 YEARS OLD',
    12 => '12 YEARS OLD',
    13 => '13 YEARS OLD',
    14 => '14 YEARS OLD',
    15 => '15 YEARS OLD',
    16 => '16 YEARS OLD',
    17 => '17 YEARS OLD',
];

// Initialize counts
$data = [];
for ($a = 0; $a <= 17; $a++) {
    $data[$a] = ['male' => 0, 'female' => 0];
}

$result = $conn->query("SELECT age, sex, COUNT(*) AS cnt FROM ($union_sql) AS children GROUP BY age, sex");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $age = (int) $row['age'];
        if ($age >= 0 && $age <= 17) {
            if ($row['sex'] === 'Male') {
                $data[$age]['male'] = (int) $row['cnt'];
            } else {
                $data[$age]['female'] += (int) $row['cnt'];
            }
        }
    }
}

$conn->close();

// ── Build spreadsheet ───────────────────────────────────────────────────
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Children 0-17 Masterlist');

// Column widths
$sheet->getColumnDimension('A')->setWidth(28);
$sheet->getColumnDimension('B')->setWidth(18);
$sheet->getColumnDimension('C')->setWidth(18);
$sheet->getColumnDimension('D')->setWidth(16);

// ── HEADER SECTION ──────────────────────────────────────────────────────
// Row 1: Republic
$sheet->mergeCells('A1:D1');
$sheet->setCellValue('A1', 'Republic of the Philippines');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['size' => 10, 'name' => 'Arial'],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

// Row 2: Province
$sheet->mergeCells('A2:D2');
$sheet->setCellValue('A2', 'Province of Agusan del Norte');
$sheet->getStyle('A2')->applyFromArray([
    'font' => ['size' => 10, 'name' => 'Arial'],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

// Row 3: Municipality
$sheet->mergeCells('A3:D3');
$sheet->setCellValue('A3', 'Municipality of Magallanes');
$sheet->getStyle('A3')->applyFromArray([
    'font' => ['size' => 10, 'name' => 'Arial'],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

// Row 4: Barangay
$sheet->mergeCells('A4:D4');
$sheet->setCellValue('A4', 'BARANGAY STO. ROSARIO');
$sheet->getStyle('A4')->applyFromArray([
    'font' => ['bold' => true, 'size' => 12, 'name' => 'Arial'],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

// Row 5: spacer
$sheet->mergeCells('A5:D5');

// Row 6: Report title
$sheet->mergeCells('A6:D6');
$sheet->setCellValue('A6', 'CHILD-FRIENDLY LOCAL GOVERNANCE ASSESSMENT');
$sheet->getStyle('A6')->applyFromArray([
    'font' => ['bold' => true, 'size' => 11, 'name' => 'Arial', 'color' => ['argb' => 'FF1565C0']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

// Row 7: Audit year
$sheet->mergeCells('A7:D7');
$sheet->setCellValue('A7', 'AUDIT YEAR ' . $audit_year);
$sheet->getStyle('A7')->applyFromArray([
    'font' => ['bold' => true, 'size' => 11, 'name' => 'Arial'],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

// Row 8: spacer
$sheet->mergeCells('A8:D8');

// Row 9: Masterlist title
$sheet->mergeCells('A9:D9');
$sheet->setCellValue('A9', 'MASTERLIST OF CHILDREN 0-17 YEARS OLD ARE REGISTERED AT BIRTH WITH SEX DISAGGREGATION');
$sheet->getStyle('A9')->applyFromArray([
    'font' => ['bold' => true, 'size' => 9, 'name' => 'Arial', 'color' => ['argb' => 'FFFFFFFF']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1565C0']],
]);
$sheet->getRowDimension(9)->setRowHeight(28);

// ── TABLE HEADER (Row 11) ───────────────────────────────────────────────
$headerRow = 11;
$headers = ['AGE', 'NO. OF MALE', 'NO. OF FEMALE', 'TOTAL'];
$cols = ['A', 'B', 'C', 'D'];

foreach ($headers as $i => $h) {
    $sheet->setCellValue($cols[$i] . $headerRow, $h);
}

$sheet->getStyle("A{$headerRow}:D{$headerRow}")->applyFromArray([
    'font' => ['bold' => true, 'size' => 10, 'name' => 'Arial'],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEF9C3']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);
$sheet->getRowDimension($headerRow)->setRowHeight(24);

// ── DATA ROWS (Rows 12-29) ──────────────────────────────────────────────
$grandMale = 0;
$grandFemale = 0;
$grandTotal = 0;

$dataStartRow = $headerRow + 1;
for ($age = 0; $age <= 17; $age++) {
    $row = $dataStartRow + $age;
    $male = $data[$age]['male'];
    $female = $data[$age]['female'];
    $total = $male + $female;

    $grandMale += $male;
    $grandFemale += $female;
    $grandTotal += $total;

    $sheet->setCellValue("A{$row}", $age_labels[$age]);
    $sheet->setCellValue("B{$row}", $male);
    $sheet->setCellValue("C{$row}", $female);
    $sheet->setCellValue("D{$row}", $total);

    // Style age label column
    $sheet->getStyle("A{$row}")->applyFromArray([
        'font' => ['bold' => true, 'size' => 10, 'name' => 'Arial', 'color' => ['argb' => 'FF92400E']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFBEB']],
    ]);

    // Style data cells
    $sheet->getStyle("B{$row}")->applyFromArray([
        'font' => ['size' => 10, 'name' => 'Arial', 'color' => ['argb' => 'FF1E40AF']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);
    $sheet->getStyle("C{$row}")->applyFromArray([
        'font' => ['size' => 10, 'name' => 'Arial', 'color' => ['argb' => 'FFBE185D']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);
    $sheet->getStyle("D{$row}")->applyFromArray([
        'font' => ['bold' => true, 'size' => 10, 'name' => 'Arial', 'color' => ['argb' => 'FF065F46']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFECFDF5']],
    ]);
}

// Apply borders to all data rows
$dataEndRow = $dataStartRow + 17;
$sheet->getStyle("A{$dataStartRow}:D{$dataEndRow}")->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);

// ── GRAND TOTAL ROW ─────────────────────────────────────────────────────
$totalRow = $dataEndRow + 1;
$sheet->setCellValue("A{$totalRow}", 'GRAND TOTAL');
$sheet->setCellValue("B{$totalRow}", $grandMale);
$sheet->setCellValue("C{$totalRow}", $grandFemale);
$sheet->setCellValue("D{$totalRow}", $grandTotal);

$sheet->getStyle("A{$totalRow}:D{$totalRow}")->applyFromArray([
    'font' => ['bold' => true, 'size' => 11, 'name' => 'Arial', 'color' => ['argb' => 'FFFFFFFF']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);
$sheet->getRowDimension($totalRow)->setRowHeight(26);

// ── LOGO (if exists and GD extension available) ─────────────────────────
$logoPath = __DIR__ . '/image/logo.jpg';
if (file_exists($logoPath) && extension_loaded('gd')) {
    try {
        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Barangay Logo');
        $drawing->setPath($logoPath);
        $drawing->setHeight(55);
        $drawing->setCoordinates('A1');
        $drawing->setOffsetX(5);
        $drawing->setOffsetY(2);
        $drawing->setWorksheet($sheet);
    } catch (\Exception $e) {
        // Skip logo if it fails
    }
}

// ── Footer note ─────────────────────────────────────────────────────────
$footerRow = $totalRow + 2;
$sheet->mergeCells("A{$footerRow}:D{$footerRow}");
$sheet->setCellValue("A{$footerRow}", "Generated on " . date('F j, Y h:i A') . " — Barangay Sto. Rosario Resident Information System");
$sheet->getStyle("A{$footerRow}")->applyFromArray([
    'font' => ['italic' => true, 'size' => 8, 'name' => 'Arial', 'color' => ['argb' => 'FF6B7280']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

// ── Print setup ─────────────────────────────────────────────────────────
$sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
$sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_LEGAL);
$sheet->getPageSetup()->setFitToWidth(1);
$sheet->getPageSetup()->setFitToHeight(0);
$sheet->getPageMargins()->setTop(0.5);
$sheet->getPageMargins()->setBottom(0.5);
$sheet->getPageMargins()->setLeft(0.5);
$sheet->getPageMargins()->setRight(0.5);

// ── Output ──────────────────────────────────────────────────────────────
$filename = 'Children_0-17_Masterlist_Brgy_StoRosario_' . $audit_year . '.xlsx';

// Create exports folder if it doesn't exist
$exportDir = __DIR__ . '/exports';
if (!is_dir($exportDir)) {
    mkdir($exportDir, 0755, true);
}

// Save to exports folder
$filePath = $exportDir . '/' . $filename;
$writer = new Xlsx($spreadsheet);
$writer->save($filePath);

$fileSize = round(filesize($filePath) / 1024);

// Discard any buffered output
while (ob_get_level()) ob_end_clean();

// Show download page
?>
<!DOCTYPE html>
<html>
<head>
    <title>Export Ready</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <style>
        body { background: #0f172a; display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: 'Plus Jakarta Sans', sans-serif; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 48px; text-align: center; max-width: 500px; }
        .icon { font-size: 64px; color: #22c55e; margin-bottom: 16px; }
        h2 { color: #f8fafc; margin-bottom: 8px; }
        p { color: #94a3b8; margin-bottom: 24px; }
        .filename { color: #38bdf8; font-weight: 600; word-break: break-all; }
        .size { color: #64748b; font-size: 14px; }
        .btn-download { background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; padding: 14px 32px; border-radius: 12px; font-size: 16px; font-weight: 700; text-decoration: none; display: inline-block; transition: transform .2s; }
        .btn-download:hover { transform: scale(1.05); color: #fff; }
        .btn-back { color: #94a3b8; text-decoration: none; display: inline-block; margin-top: 16px; }
        .btn-back:hover { color: #f8fafc; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon"><i class="fas fa-check-circle"></i></div>
        <h2>Export Ready!</h2>
        <p class="filename"><?= htmlspecialchars($filename) ?></p>
        <p class="size"><?= $fileSize ?> KB</p>
        <a href="download.php?file=<?= rawurlencode($filename) ?>" class="btn-download">
            <i class="fas fa-download"></i> Download File
        </a>
        <br>
        <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html>
<?php exit(); ?>

