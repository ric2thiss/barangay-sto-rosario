<?php
include "../config/database.php";
include "../config/session.php";

// Get statistics
$totalCollection = $conn->query("
    SELECT COALESCE(SUM(amount), 0) AS total FROM payments
")->fetch_assoc()['total'] ?? 0;

$totalDisbursement = $conn->query("
    SELECT COALESCE(SUM(release_amount), 0) AS total FROM disbursements
")->fetch_assoc()['total'] ?? 0;

$totalCedula = $conn->query("
    SELECT COUNT(*) AS total FROM cedula
")->fetch_assoc()['total'] ?? 0;

$totalBrgyClearance = $conn->query("
    SELECT COUNT(*) AS total FROM payments WHERE service_type = 'Barangay Clearance'
")->fetch_assoc()['total'] ?? 0;


// Get recent transactions
$recentPayments = $conn->query("
    SELECT * FROM payments 
    ORDER BY payment_date DESC 
    LIMIT 5
");

$recentDisbursements = $conn->query("
    SELECT * FROM disbursements 
    ORDER BY disburse_date DESC 
    LIMIT 5
");

// Get collection categories data for chart (based on current month)
$chartMonth = (int) date('n');
$chartYear = (int) date('Y');


// Certificates (payments only)
$certificates = $conn->query("
    SELECT COALESCE(SUM(amount), 0) as total
    FROM payments
    WHERE MONTH(COALESCE(payment_date, DATE(created_at))) = $chartMonth
    AND YEAR(COALESCE(payment_date, DATE(created_at))) = $chartYear
")->fetch_assoc()['total'] ?? 0;

// Documentary Stamp Fees (BIR tax)
$documentaryStampFees = $conn->query("
    SELECT COALESCE(SUM(bir_tax), 0) as total
    FROM payments
    WHERE MONTH(COALESCE(payment_date, DATE(created_at))) = $chartMonth
    AND YEAR(COALESCE(payment_date, DATE(created_at))) = $chartYear
")->fetch_assoc()['total'] ?? 0;

$totalMonthlyCollections = $certificates + $documentaryStampFees;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Treasurer Dashboard - Barangay Sto. Rosario</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/images/logo.jpg" alt="Barangay Logo"
                    style="width: 80px; height: 80px; border-radius: 50%; margin-bottom: 10px; border: 3px solid #ffffff;">
                <h2>BARANGAY STO. ROSARIO</h2>
                <p>Treasurer Module</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><details class="sidebar-dropdown"><summary><i class="fas fa-money-bill-wave"></i> Payments <i class="fas fa-chevron-right dropdown-caret"></i></summary><ul class="submenu"><li><a href="payments/list.php"><i class="fas fa-list"></i> All Payments</a></li><li><a href="payments/add.php"><i class="fas fa-plus"></i> Certificate</a></li><li><a href="payments/manual.php?type=donation"><i class="fas fa-heart"></i> Donation</a></li><li><a href="payments/manual.php?type=garbage"><i class="fas fa-trash"></i> Garbage</a></li><li><a href="payments/manual.php?type=rental"><i class="fas fa-building"></i> Rental</a></li></ul></details></li>
                <li><a href="pending_payments/list.php"><i class="fas fa-hourglass-half"></i> Pending Status</a></li>
                <li><a href="cedula/list.php"><i class="fas fa-id-card"></i> Cedula</a></li>
                <li><a href="disbursement/list.php"><i class="fas fa-hand-holding-usd"></i> Disbursements</a></li>
                <li><a href="collections/monthly.php"><i class="fas fa-chart-line"></i> Monthly Collections</a></li>
                <li><a href="collections/analytics.php"><i class="fas fa-landmark"></i> IRA/DV Analytics</a></li>
                <li><a href="collections/annual.php"><i class="fas fa-calendar-alt"></i> Annual Report</a></li>
                <li><a href="change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <h1><i class="fas fa-tachometer-alt"></i> Treasurer Dashboard</h1>
                <p class="text-white">Welcome,
                    <?= htmlspecialchars($_SESSION['name']) ?>!
                </p>
            </div>

            <div class="content-body">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h4><i class="fas fa-coins"></i> Total Collections</h4>
                        <div class="stat-value">
                            ₱<?= number_format($totalCollection, 2) ?>
                        </div>
                    </div>
                    <div class="stat-card red">
                        <h4><i class="fas fa-money-check-alt"></i> Total Disbursements</h4>
                        <div class="stat-value">
                            ₱<?= number_format($totalDisbursement, 2) ?>
                        </div>
                    </div>
                    <div class="stat-card blue">
                        <h4><i class="fas fa-id-card"></i> Cedula Issued</h4>
                        <div class="stat-value">
                            <?= number_format($totalCedula) ?>
                        </div>
                    </div>

                </div>

                <!-- Chart Section -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-bar"></i> Collections by Category</h3>
                    </div>
                    <canvas id="categoryChart" height="80"></canvas>
                </div>

                <!-- Recent Transactions -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <!-- Recent Payments -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-money-bill"></i> Recent Payments</h3>
                        </div>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Purpose</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recentPayments->num_rows > 0): ?>
                                    <?php while ($payment = $recentPayments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= date('M d, Y', strtotime($payment['payment_date'])) ?>
                                        </td>
                                        <td><?= htmlspecialchars($payment['purpose']) ?>
                                        </td>
                                        <td>₱<?= number_format($payment['amount'], 2) ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center;">No recent payments</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Recent Disbursements -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-hand-holding-usd"></i> Recent Disbursements</h3>
                        </div>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Payee</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recentDisbursements->num_rows > 0): ?>
                                    <?php while ($disbursement = $recentDisbursements->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= date('M d, Y', strtotime($disbursement['disburse_date'])) ?>
                                        </td>
                                        <td><?= htmlspecialchars($disbursement['payee']) ?>
                                        </td>
                                        <td>₱<?= number_format($disbursement['release_amount'], 2) ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center;">No recent disbursements</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Category Chart
        const ctx = document.getElementById('categoryChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Certificates', 'Docu Stamp Total', 'TOTAL MONTHLY COLLECTIONS'],
                datasets: [{
                    label: 'Collections by Category',
                    data: [
                        <?= $certificates ?>
                        ,
                        <?= $documentaryStampFees ?>
                        ,
                        <?= $totalMonthlyCollections ?>
                    ],
                    backgroundColor: [
                        '#1F3A93',
                        '#4A7FCB',
                        '#6BA3E0'
                    ],
                    borderColor: [
                        '#1a3280',
                        '#3d6cb3',
                        '#5890c9'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
    <script src="../assets/js/logout-confirm.js"></script>
</body>

</html>





