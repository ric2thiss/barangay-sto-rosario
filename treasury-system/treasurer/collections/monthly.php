<?php
include "../../config/database.php";
include "../../config/session.php";

// Get current month and year or from filter
$month = intval($_GET['month'] ?? date('m'));
$year = intval($_GET['year'] ?? date('Y'));
$otherCollectionTypes = "'Donation', 'Garbage', 'Rental'";
$serviceTypeExpression = "CASE WHEN TRIM(service_type) = 'Community Tax Certificate' THEN 'Rental' ELSE COALESCE(NULLIF(TRIM(service_type), ''), 'Unspecified') END";

// Certificates (from payments, excluding non-certificate collections)
$certificateCollections = $conn->query("
    SELECT COALESCE(SUM(amount), 0) as total 
    FROM payments 
    WHERE MONTH(COALESCE(payment_date, DATE(created_at))) = $month 
    AND YEAR(COALESCE(payment_date, DATE(created_at))) = $year
    AND {$serviceTypeExpression} NOT IN ($otherCollectionTypes)
")->fetch_assoc()['total'] ?? 0;

$documentaryStampFees = $conn->query("
    SELECT COALESCE(SUM(bir_tax), 0) as total
    FROM payments
    WHERE MONTH(COALESCE(payment_date, DATE(created_at))) = $month
    AND YEAR(COALESCE(payment_date, DATE(created_at))) = $year
")->fetch_assoc()['total'] ?? 0;

// Certificates breakdown by service type
$certificateBreakdown = [];
$certificateBreakdownResult = $conn->query("
    SELECT {$serviceTypeExpression} AS service_type,
           COALESCE(SUM(amount), 0) as total
    FROM payments
    WHERE MONTH(COALESCE(payment_date, DATE(created_at))) = $month
    AND YEAR(COALESCE(payment_date, DATE(created_at))) = $year
    AND {$serviceTypeExpression} NOT IN ($otherCollectionTypes)
    GROUP BY {$serviceTypeExpression}
    ORDER BY service_type
");
while ($row = $certificateBreakdownResult->fetch_assoc()) {
    $certificateBreakdown[] = $row;
}

// Other Collections: Donation, Garbage, and Rental are not certificates.
$otherCollections = $conn->query("
    SELECT COALESCE(SUM(amount), 0) as total
    FROM payments
    WHERE MONTH(COALESCE(payment_date, DATE(created_at))) = $month
    AND YEAR(COALESCE(payment_date, DATE(created_at))) = $year
    AND {$serviceTypeExpression} IN ($otherCollectionTypes)
")->fetch_assoc()['total'] ?? 0;

$otherCollectionsBreakdown = [];
$otherBreakdownResult = $conn->query("
    SELECT {$serviceTypeExpression} AS service_type,
           COALESCE(SUM(amount), 0) as total
    FROM payments
    WHERE MONTH(COALESCE(payment_date, DATE(created_at))) = $month
    AND YEAR(COALESCE(payment_date, DATE(created_at))) = $year
    AND {$serviceTypeExpression} IN ($otherCollectionTypes)
    GROUP BY {$serviceTypeExpression}
    ORDER BY FIELD({$serviceTypeExpression}, 'Donation', 'Garbage', 'Rental')
");
while ($row = $otherBreakdownResult->fetch_assoc()) {
    $otherCollectionsBreakdown[] = $row;
}

// Withholding tax from disbursements, shown separately for treasurer review.
$withholdingTaxRows = [];
$withholdingTaxTotals = [
    'gross' => 0,
    'withholding_a' => 0,
    'withholding_b' => 0,
    'total' => 0,
];
$withholdingTaxResult = $conn->query("
    SELECT disburse_date,
           payee,
           dv_no,
           bir_vat_type,
           COALESCE(bir_gross, amount, 0) AS gross_amount,
           COALESCE(bir_withholding_a, 0) AS withholding_a,
           COALESCE(bir_withholding_b, 0) AS withholding_b,
           COALESCE(NULLIF(CAST(bir AS DECIMAL(12, 2)), 0), COALESCE(bir_withholding_a, 0) + COALESCE(bir_withholding_b, 0)) AS total_withheld
    FROM disbursements
    WHERE MONTH(disburse_date) = $month
    AND YEAR(disburse_date) = $year
    AND (
        COALESCE(bir_withholding_a, 0) > 0
        OR COALESCE(bir_withholding_b, 0) > 0
        OR COALESCE(NULLIF(CAST(bir AS DECIMAL(12, 2)), 0), 0) > 0
    )
    ORDER BY disburse_date ASC, id ASC
");
while ($row = $withholdingTaxResult->fetch_assoc()) {
    $withholdingTaxRows[] = $row;
    $withholdingTaxTotals['gross'] += floatval($row['gross_amount'] ?? 0);
    $withholdingTaxTotals['withholding_a'] += floatval($row['withholding_a'] ?? 0);
    $withholdingTaxTotals['withholding_b'] += floatval($row['withholding_b'] ?? 0);
    $withholdingTaxTotals['total'] += floatval($row['total_withheld'] ?? 0);
}

$totalCollections = $certificateCollections + $otherCollections + $documentaryStampFees;
$monthName = date('F Y', mktime(0, 0, 0, $month, 1, $year));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Collections - Barangay Sto. Rosario</title>
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
        }

        .print-header {
            display: none;
            text-align: center;
            margin-bottom: 30px;
        }

        .report-section {
            margin-bottom: 30px;
        }

        .report-table {
            width: 100%;
            margin-top: 15px;
        }

        .report-table td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        .report-table td:first-child {
            font-weight: 600;
        }

        .report-table td:last-child {
            text-align: right;
            font-weight: 700;
        }

        .total-row td {
            background: #1F3A93;
            font-size: 18px;
            padding: 15px 10px !important;
            border-top: 2px solid #1F3A93;
            color: #ffffff;
        }

        .tax-details {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
        }

        .tax-details summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            cursor: pointer;
            color: #1e3a5f;
            font-weight: 700;
            list-style: none;
            user-select: none;
        }

        .tax-details summary::-webkit-details-marker {
            display: none;
        }

        .tax-details summary::marker {
            content: "";
        }

        .tax-details .tax-caret {
            transition: transform 0.2s ease;
        }

        .tax-details[open] .tax-caret {
            transform: rotate(90deg);
        }

        .tax-details-body {
            border-top: 1px solid #e2e8f0;
            padding: 0 16px 16px;
            animation: reportDropdownIn 0.18s ease-out;
        }

        @keyframes reportDropdownIn {
            from {
                opacity: 0;
                transform: translateY(-4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <aside class="sidebar no-print">
            <div class="sidebar-header">
                <img src="../../assets/images/logo.jpg" alt="Barangay Logo"
                    style="width: 80px; height: 80px; border-radius: 50%; margin-bottom: 10px; border: 3px solid #ffffff;">
                <h2>BARANGAY STO. ROSARIO</h2>
                <p>Treasurer Module</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><details class="sidebar-dropdown"><summary><i class="fas fa-money-bill-wave"></i> Payments <i class="fas fa-chevron-right dropdown-caret"></i></summary><ul class="submenu"><li><a href="../payments/list.php"><i class="fas fa-list"></i> All Payments</a></li><li><a href="../payments/add.php"><i class="fas fa-plus"></i> Certificate</a></li><li><a href="../payments/manual.php?type=donation"><i class="fas fa-heart"></i> Donation</a></li><li><a href="../payments/manual.php?type=garbage"><i class="fas fa-trash"></i> Garbage</a></li><li><a href="../payments/manual.php?type=rental"><i class="fas fa-building"></i> Rental</a></li></ul></details></li>
                <li><a href="../pending_payments/list.php"><i class="fas fa-hourglass-half"></i> Pending Status</a></li>
                <li><a href="../cedula/list.php"><i class="fas fa-id-card"></i> Cedula</a></li>
                <li><a href="../disbursement/list.php"><i class="fas fa-hand-holding-usd"></i> Disbursements</a></li>
                <li><a href="monthly.php" class="active"><i class="fas fa-chart-line"></i> Monthly Collections</a></li>
                <li><a href="analytics.php"><i class="fas fa-landmark"></i> IRA/DV Analytics</a></li>
                <li><a href="annual.php"><i class="fas fa-calendar-alt"></i> Annual Report</a></li>
                <li><a href="../change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
                <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="content-header no-print">
                <h1><i class="fas fa-chart-line"></i> Statement of Itemized Monthly Collection</h1>
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
                    <h3 style="margin-top: 20px; color: #1e3a5f;">Statement of Itemized Monthly Collection</h3>
                    <p style="color: #666; font-size: 16px;">
                        <?= $monthName ?>
                    </p>
                </div>

                <!-- Filter Section -->
                <div class="card no-print">
                    <div class="card-header">
                        <h3><i class="fas fa-filter"></i> Select Month & Year</h3>
                    </div>
                    <form method="GET" style="display: flex; gap: 15px; align-items: end;">
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label for="month">Month</label>
                            <select id="month" name="month">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option
                                    value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>"
                                    <?= $m == $month ? 'selected' : '' ?>>
                                    <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
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
                        <h3><i class="fas fa-file-invoice-dollar"></i>
                            <?= $monthName ?>
                        </h3>
                    </div>

                    <!-- Certificates -->
                    <div class="report-section">
                        <h4
                            style="color: #1e3a5f; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 2px solid #1F3A93;">
                            <i class="fas fa-receipt"></i> CERTIFICATES
                        </h4>
                        <table class="report-table">
                            <tbody>
                                <?php if (!empty($certificateBreakdown)): ?>
                                <?php foreach ($certificateBreakdown as $bd): ?>
                                <tr>
                                    <td style="padding-left: 30px; color: #555;">
                                        <i class="fas fa-chevron-right" style="font-size:11px; margin-right:6px;"></i>
                                        <?= htmlspecialchars($bd['service_type'] ?: 'Unspecified') ?>
                                    </td>
                                    <td>₱<?= number_format($bd['total'], 2) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td style="font-weight: 600;">
                                        <em>Subtotal - Certificates</em>
                                    </td>
                                    <td>₱<?= number_format($certificateCollections, 2) ?>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <tr>
                                    <td>Certificates</td>
                                    <td>₱<?= number_format($certificateCollections, 2) ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr class="total-row">
                                    <td>TOTAL CERTIFICATES</td>
                                    <td>₱<?= number_format($certificateCollections, 2) ?>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>

                    <!-- Other Collections -->
                    <div class="report-section">
                        <h4
                            style="color: #1e3a5f; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 2px solid #1F3A93;">
                            <i class="fas fa-cash-register"></i> OTHER COLLECTIONS
                        </h4>
                        <table class="report-table">
                            <tbody>
                                <?php if (!empty($otherCollectionsBreakdown)): ?>
                                <?php foreach ($otherCollectionsBreakdown as $bd): ?>
                                <tr>
                                    <td style="padding-left: 30px; color: #555;">
                                        <i class="fas fa-chevron-right" style="font-size:11px; margin-right:6px;"></i>
                                        <?= htmlspecialchars($bd['service_type'] ?: 'Unspecified') ?>
                                    </td>
                                    <td>₱<?= number_format($bd['total'], 2) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td style="font-weight: 600;">
                                        <em>Subtotal - Other Collections</em>
                                    </td>
                                    <td>₱<?= number_format($otherCollections, 2) ?>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <tr>
                                    <td>Donation, Garbage, and Rental</td>
                                    <td>₱<?= number_format($otherCollections, 2) ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr class="total-row">
                                    <td>TOTAL OTHER COLLECTIONS</td>
                                    <td>₱<?= number_format($otherCollections, 2) ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!--total docu stamp fees-->
                    <div class="report-section">
                        <h4
                            style="color: #1e3a5f; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 2px solid #1F3A93;">
                            <i class="fas fa-receipt"></i> Documentary Stamp
                        </h4>
                        <table class="report-table">
                            <tbody>

                                <tr class="total-row">
                                    <td>TOTAL FEES</td>
                                    <td>₱<?= number_format($documentaryStampFees, 2) ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Grand Total -->
                    <div class="report-section" style="margin-top: 40px;">
                        <table class="report-table">
                            <tbody>
                                <tr style="background: #1F3A93; font-size: 20px;">
                                    <td style="padding: 20px 10px !important; color: #ffffff;">
                                        <i class="fas fa-calculator"></i> TOTAL MONTHLY COLLECTION
                                    </td>
                                    <td style="padding: 20px 10px !important; color: #ffffff;">
                                        ₱<?= number_format($totalCollections, 2) ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>



                    <!-- Withholding Tax -->
                    <div class="report-section">
                        <details class="tax-details">
                            <summary>
                                <span>
                                    <i class="fas fa-percent"></i>
                                    TOTAL WITHHOLDING TAX: PHP <?= number_format($withholdingTaxTotals['total'], 2) ?>
                                </span>
                                <i class="fas fa-chevron-right tax-caret"></i>
                            </summary>

                            <div class="tax-details-body">
                                <table class="report-table">
                                    <thead>
                                        <tr>
                                            <th style="text-align:left;">Date</th>
                                            <th style="text-align:left;">Payee</th>
                                            <th style="text-align:left;">DV No.</th>
                                            <th style="text-align:left;">VAT Type</th>
                                            <th style="text-align:right;">Gross</th>
                                            <th style="text-align:right;">Withholding A</th>
                                            <th style="text-align:right;">Withholding B</th>
                                            <th style="text-align:right;">Total Withheld</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($withholdingTaxRows)): ?>
                                        <?php foreach ($withholdingTaxRows as $taxRow): ?>
                                        <tr>
                                            <td><?= date('M d, Y', strtotime($taxRow['disburse_date'])) ?></td>
                                            <td><?= htmlspecialchars($taxRow['payee']) ?></td>
                                            <td><?= htmlspecialchars($taxRow['dv_no']) ?></td>
                                            <td><?= htmlspecialchars($taxRow['bir_vat_type'] ?: 'N/A') ?></td>
                                            <td>PHP <?= number_format($taxRow['gross_amount'], 2) ?></td>
                                            <td>PHP <?= number_format($taxRow['withholding_a'], 2) ?></td>
                                            <td>PHP <?= number_format($taxRow['withholding_b'], 2) ?></td>
                                            <td>PHP <?= number_format($taxRow['total_withheld'], 2) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="total-row">
                                            <td colspan="4">TOTAL WITHHOLDING TAX</td>
                                            <td>PHP <?= number_format($withholdingTaxTotals['gross'], 2) ?></td>
                                            <td>PHP <?= number_format($withholdingTaxTotals['withholding_a'], 2) ?></td>
                                            <td>PHP <?= number_format($withholdingTaxTotals['withholding_b'], 2) ?></td>
                                            <td>PHP <?= number_format($withholdingTaxTotals['total'], 2) ?></td>
                                        </tr>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="8" style="text-align:center; color:#777;">No withholding tax records for this month.</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                                <p style="margin-top: 8px; color: #666; font-size: 13px;">
                                    This table is for tax monitoring only and is not added to the total monthly collection.
                                </p>
                            </div>
                        </details>
                    </div>


                    <!-- Signature Section for Print -->
                    <div class="print-header" style="margin-top: 60px; text-align: left;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
                            <div>
                                <p style="margin-bottom: 30px;">Prepared by:</p>
                                <p
                                    style="border-bottom: 1px solid #000; display: inline-block; min-width: 200px; margin-bottom: 5px;">
                                </p>
                                <p style="font-weight: 600;">Barangay Treasurer</p>
                            </div>
                            <div>
                                <p style="margin-bottom: 30px;">Approved by:</p>
                                <p
                                    style="border-bottom: 1px solid #000; display: inline-block; min-width: 200px; margin-bottom: 5px;">
                                </p>
                                <p style="font-weight: 600;">Barangay Captain</p>
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








