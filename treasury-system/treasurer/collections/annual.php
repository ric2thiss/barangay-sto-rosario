<?php
include "../../config/database.php";
include "../../config/session.php";

// Get year from filter
$year = $_GET['year'] ?? date('Y');
$otherCollectionTypes = "'Donation', 'Garbage', 'Rental'";
$serviceTypeExpression = "CASE WHEN TRIM(service_type) = 'Community Tax Certificate' THEN 'Rental' ELSE COALESCE(NULLIF(TRIM(service_type), ''), 'Unspecified') END";

// Initialize arrays for monthly data
$monthlyData = [
    'certificates' => [],
    'otherCollections' => [],
    'documentaryFees' => [],
    'totals' => []
];

// Get data for each month
for ($month = 1; $month <= 12; $month++) {
    // Certificates (excluding other collections)
    $certificates = $conn->query("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE MONTH(COALESCE(payment_date, DATE(created_at))) = $month 
        AND YEAR(COALESCE(payment_date, DATE(created_at))) = $year
        AND {$serviceTypeExpression} NOT IN ($otherCollectionTypes)
    ")->fetch_assoc()['total'] ?? 0;

    $monthlyData['certificates'][$month] = $certificates;

    // Other Collections: Donation, Garbage, Rental
    $otherCollections = $conn->query("
        SELECT COALESCE(SUM(amount), 0) as total
        FROM payments
        WHERE MONTH(COALESCE(payment_date, DATE(created_at))) = $month
        AND YEAR(COALESCE(payment_date, DATE(created_at))) = $year
        AND {$serviceTypeExpression} IN ($otherCollectionTypes)
    ")->fetch_assoc()['total'] ?? 0;

    $monthlyData['otherCollections'][$month] = $otherCollections;

    // Documentary Stamp Fees (BIR tax)
    $documentaryFees = $conn->query("
        SELECT COALESCE(SUM(bir_tax), 0) as total 
        FROM payments 
        WHERE MONTH(COALESCE(payment_date, DATE(created_at))) = $month 
        AND YEAR(COALESCE(payment_date, DATE(created_at))) = $year
    ")->fetch_assoc()['total'] ?? 0;

    $monthlyData['documentaryFees'][$month] = $documentaryFees;
    
    // Monthly total
    $monthlyData['totals'][$month] =
        $monthlyData['certificates'][$month] +
        $monthlyData['otherCollections'][$month] +
        $monthlyData['documentaryFees'][$month];
}

// Calculate yearly totals
$yearlyCertificates = array_sum($monthlyData['certificates']);
$yearlyOtherCollections = array_sum($monthlyData['otherCollections']);
$yearlyDocumentaryFees = array_sum($monthlyData['documentaryFees']);
$yearlyGrandTotal = array_sum($monthlyData['totals']);

$monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annual Report - Barangay Sto. Rosario</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            .print-header {
                display: block !important;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }

        .print-header {
            display: none;
            text-align: center;
            margin-bottom: 30px;
        }

        .annual-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .annual-table th,
        .annual-table td {
            padding: 12px;
            border: 1px solid #dee2e6;
            text-align: right;
        }

        .annual-table th {
            background: #1F3A93;
            color: white;
            font-weight: 600;
            text-align: center;
        }

        .annual-table td:first-child {
            text-align: left;
            font-weight: 600;
        }

        .annual-table tbody tr:hover {
            background: #f8f9fa;
        }

        .total-row {
            background: #1F3A93 !important;
            color: white !important;
            font-weight: 700;
            font-size: 16px;
        }

        .total-row td {
            color: #ffffff !important;
        }

        .category-header {
            background: #f0f4f8 !important;
            color: #1e3a5f !important;
            font-weight: 700;
            text-align: left !important;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php
        $path_prefix = '../';
        include "../partials/sidebar.php";
        ?>

        <main class="main-content">
            <div class="content-header no-print">
                <h1><i class="fas fa-calendar-alt"></i> Annual Collection Report</h1>
            </div>

            <div class="content-body">
                <!-- Print Header -->
                <div class="print-header">
                    <div
                        style="display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 20px;">
                        <img src="../../assets/images/logo.jpg" alt="Barangay Logo"
                            style="width: 100px; height: 100px; border-radius: 50%;">
                        <div>
                            <h2 style="color: #1e3a5f; margin-bottom: 5px;">BARANGAY STO. ROSARIO</h2>
                            <p style="color: #666;">Magallanes, Agusan del Norte</p>
                        </div>
                    </div>
                    <h3 style="margin-top: 20px; color: #1e3a5f;">Annual Collection Report</h3>
                    <p style="color: #666; font-size: 16px;">Calendar Year
                        <?= $year ?>
                    </p>
                </div>

                <!-- Filter Section -->
                <div class="card no-print">
                    <div class="card-header">
                        <h3><i class="fas fa-filter"></i> Select Year</h3>
                    </div>
                    <form method="GET" style="display: flex; gap: 15px; align-items: end;">
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label for="year">Year</label>
                            <select id="year" name="year">
                                <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" style="flex: 0.5;">
                            <i class="fas fa-search"></i> Generate
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="window.print()" style="flex: 0.5;">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </form>
                </div>

                <!-- Report Content -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-file-invoice-dollar"></i> Year
                            <?= $year ?> - Monthly Breakdown
                        </h3>
                    </div>

                    <table class="annual-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Certificates</th>
                                <th>Other Collections</th>
                                <th>Documentary Stamp Fees</th>
                                <th>Monthly Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($month = 1; $month <= 12; $month++): ?>
                            <tr>
                                <td><?= $monthNames[$month] ?></td>
                                <td>₱<?= number_format($monthlyData['certificates'][$month], 2) ?>
                                </td>
                                <td>₱<?= number_format($monthlyData['otherCollections'][$month], 2) ?>
                                </td>
                                <td>₱<?= number_format($monthlyData['documentaryFees'][$month], 2) ?>
                                </td>
                                <td><strong>₱<?= number_format($monthlyData['totals'][$month], 2) ?></strong>
                                </td>
                            </tr>
                            <?php endfor; ?>
                            <tr class="total-row">
                                <td>ANNUAL TOTAL</td>
                                <td>₱<?= number_format($yearlyCertificates, 2) ?>
                                </td>
                                <td>₱<?= number_format($yearlyOtherCollections, 2) ?>
                                </td>
                                <td>₱<?= number_format($yearlyDocumentaryFees, 2) ?>
                                </td>
                                <td>₱<?= number_format($yearlyGrandTotal, 2) ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Summary Cards -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 30px;"
                    class="no-print">
                    <div class="card"
                        style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                        <div style="padding: 20px;">
                            <h4 style="margin: 0 0 10px 0; opacity: 0.9;"><i class="fas fa-receipt"></i> Certificates
                            </h4>
                            <p style="font-size: 28px; font-weight: 700; margin: 0;">
                                ₱<?= number_format($yearlyCertificates, 2) ?>
                            </p>
                        </div>
                    </div>

                    <div class="card"
                        style="background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); color: #1e3a5f;">
                        <div style="padding: 20px;">
                            <h4 style="margin: 0 0 10px 0; opacity: 0.9;"><i class="fas fa-cash-register"></i> Other
                                Collections</h4>
                            <p style="font-size: 28px; font-weight: 700; margin: 0;">
                                ₱<?= number_format($yearlyOtherCollections, 2) ?>
                            </p>
                        </div>
                    </div>

                    <div class="card"
                        style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
                        <div style="padding: 20px;">
                            <h4 style="margin: 0 0 10px 0; opacity: 0.9;"><i class="fas fa-stamp"></i> Documentary Stamp
                                Fees</h4>
                            <p style="font-size: 28px; font-weight: 700; margin: 0;">
                                ₱<?= number_format($yearlyDocumentaryFees, 2) ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Grand Total Card -->
                <div class="card no-print"
                    style="background: linear-gradient(135deg, #1F3A93 0%, #1e3a5f 100%); color: white; margin-top: 20px;">
                    <div style="padding: 30px; text-align: center;">
                        <h3 style="margin: 0 0 15px 0; opacity: 0.9;"><i class="fas fa-chart-line"></i> TOTAL ANNUAL
                            COLLECTIONS</h3>
                        <p style="font-size: 48px; font-weight: 700; margin: 0;">
                            ₱<?= number_format($yearlyGrandTotal, 2) ?>
                        </p>
                        <p style="margin: 10px 0 0 0; opacity: 0.8;">Calendar Year
                            <?= $year ?>
                        </p>
                    </div>
                </div>

                <!-- Signature Section for Print -->
                <div class="print-header" style="margin-top: 60px; text-align: left;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
                        <div>
                            <p style="margin-bottom: 50px;">Prepared by:</p>
                            <div
                                style="border-top: 2px solid #333; padding-top: 5px; display: inline-block; min-width: 250px;">
                                <strong>BARANGAY TREASURER</strong>
                            </div>
                        </div>
                        <div>
                            <p style="margin-bottom: 50px;">Noted by:</p>
                            <div
                                style="border-top: 2px solid #333; padding-top: 5px; display: inline-block; min-width: 250px;">
                                <strong>BARANGAY CAPTAIN</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../../assets/js/logout-confirm.js"></script>
</body>

</html>