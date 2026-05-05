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

$pendingCount = $conn->query("
    SELECT COUNT(*) AS total FROM payment_status WHERE payment_status IN ('pending','to_review')
")->fetch_assoc()['total'] ?? 0;

$netBalance = $totalCollection - $totalDisbursement;

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

// Monthly collections trend (last 6 months)
$monthlyTrend = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('n', strtotime("-$i months"));
    $y = date('Y', strtotime("-$i months"));
    $label = date('M', strtotime("-$i months"));
    $amt = $conn->query("
        SELECT COALESCE(SUM(amount+bir_tax),0) AS total FROM payments
        WHERE MONTH(COALESCE(payment_date, DATE(created_at)))=$m
        AND YEAR(COALESCE(payment_date, DATE(created_at)))=$y
    ")->fetch_assoc()['total'] ?? 0;
    $monthlyTrend[] = ['label' => $label, 'total' => floatval($amt)];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Treasurer Dashboard - Barangay Sto. Rosario</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; }

        /* Content header override for dashboard */
        .content-header h1 { color: #0f172a; font-size: 20px; font-weight: 800; }
        .content-header p { color: #64748b; font-size: 13px; margin-top: 3px; }

        /* KPI Cards - Horizontal layout */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .kpi-card {
            background: #fff;
            border-radius: 16px;
            padding: 18px 20px;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }

        .kpi-icon {
            width: 48px;
            height: 48px;
            min-width: 48px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
        }

        .kpi-text { flex: 1; min-width: 0; }
        .kpi-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #94a3b8; margin-bottom: 4px; }
        .kpi-value { font-size: 21px; font-weight: 800; color: #0f172a; line-height: 1.1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .kpi-sub { font-size: 11px; color: #94a3b8; margin-top: 2px; }

        .kpi-green .kpi-icon { background: #dcfce7; color: #16a34a; }
        .kpi-red .kpi-icon   { background: #fee2e2; color: #dc2626; }
        .kpi-blue .kpi-icon  { background: #dbeafe; color: #2563eb; }
        .kpi-amber .kpi-icon { background: #fef3c7; color: #d97706; }
        .kpi-purple .kpi-icon{ background: #ede9fe; color: #7c3aed; }
        .kpi-navy .kpi-icon  { background: #e0e7ff; color: #1F3A93; }

        /* Grid layouts */
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px; }
        @media (max-width: 900px) { .two-col { grid-template-columns: 1fr; } }

        /* Cards */
        .dash-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        .dash-card-header {
            padding: 15px 20px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .dash-card-header h3 {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .dash-card-header h3 i { color: #1F3A93; }
        .dash-card-body { padding: 18px 20px; }

        .view-all-link {
            font-size: 12px;
            color: #1F3A93;
            text-decoration: none;
            font-weight: 600;
        }
        .view-all-link:hover { text-decoration: underline; }

        /* Mini Table */
        .mini-table { width: 100%; border-collapse: collapse; }
        .mini-table thead th {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #94a3b8;
            background: transparent;
            padding: 0 0 8px;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
        }
        .mini-table tbody td {
            padding: 9px 0;
            font-size: 12px;
            color: #374151;
            border-bottom: 1px solid #f8fafc;
        }
        .mini-table tbody tr:last-child td { border-bottom: none; }
        .mini-table tbody td:last-child { text-align: right; font-weight: 700; color: #0f172a; }

        /* Badge */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .badge-blue { background: #dbeafe; color: #1d4ed8; }
        .badge-green { background: #dcfce7; color: #15803d; }
        .badge-amber { background: #fef3c7; color: #b45309; }

        /* Balance display */
        .balance-display {
            background: linear-gradient(135deg, #1F3A93 0%, #1e3a8a 100%);
            border-radius: 16px;
            padding: 22px 26px;
            color: white;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .balance-display h2 { font-size: 11px; font-weight: 600; opacity: 0.7; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 1px; }
        .balance-display .amount { font-size: 30px; font-weight: 900; letter-spacing: -1px; }
        .balance-display .dividers { display: flex; gap: 28px; }
        .balance-display .dividers div { text-align: right; }
        .balance-display .dividers label { font-size: 10px; opacity: 0.6; display: block; margin-bottom: 3px; }
        .balance-display .dividers span { font-size: 15px; font-weight: 700; }

        /* Quick actions */
        .quick-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .quick-action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            padding: 14px 8px;
            border-radius: 12px;
            text-decoration: none;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #374151;
            transition: all 0.2s;
            font-size: 10px;
            font-weight: 700;
            text-align: center;
        }
        .quick-action-btn:hover { background: #1F3A93; color: white; border-color: #1F3A93; transform: translateY(-2px); }
        .quick-action-btn:hover i { color: white; }
        .quick-action-btn i { font-size: 18px; color: #1F3A93; }

        @media (max-width: 640px) {
            .content-body { padding: 14px; }
            .balance-display { flex-direction: column; }
            .balance-display .dividers { justify-content: flex-start; }
            .quick-actions { grid-template-columns: repeat(2, 1fr); }
            .kpi-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include "partials/sidebar.php"; ?>

        <main class="main-content">
            <div class="content-header">
                <h1>Treasurer Dashboard</h1>
                <p>Welcome back, <strong><?= htmlspecialchars($_SESSION['name']) ?></strong> &mdash; <?= date('l, F j, Y') ?></p>
            </div>

            <div class="content-body">

                <!-- Balance Summary Banner -->
                <div class="balance-display">
                    <div>
                        <h2>IRA Net Balance</h2>
                        <div class="amount">₱<?= number_format($netBalance, 2) ?></div>
                    </div>
                    <div class="dividers">
                        <div>
                            <label>Total Collections</label>
                            <span>₱<?= number_format($totalCollection, 2) ?></span>
                        </div>
                        <div>
                            <label>Total Disbursements</label>
                            <span>₱<?= number_format($totalDisbursement, 2) ?></span>
                        </div>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="kpi-grid">
                    <div class="kpi-card kpi-green">
                        <div class="kpi-icon"><i class="fas fa-coins"></i></div>
                        <div class="kpi-text">
                            <div class="kpi-label">Total Collections</div>
                            <div class="kpi-value">₱<?= number_format($totalCollection, 0) ?></div>
                            <div class="kpi-sub">All-time collected</div>
                        </div>
                    </div>
                    <div class="kpi-card kpi-red">
                        <div class="kpi-icon"><i class="fas fa-money-check-alt"></i></div>
                        <div class="kpi-text">
                            <div class="kpi-label">Total Disbursements</div>
                            <div class="kpi-value">₱<?= number_format($totalDisbursement, 0) ?></div>
                            <div class="kpi-sub">All-time disbursed</div>
                        </div>
                    </div>
                    <div class="kpi-card kpi-blue">
                        <div class="kpi-icon"><i class="fas fa-id-card"></i></div>
                        <div class="kpi-text">
                            <div class="kpi-label">Cedula Issued</div>
                            <div class="kpi-value"><?= number_format($totalCedula) ?></div>
                            <div class="kpi-sub">All-time records</div>
                        </div>
                    </div>
                    <div class="kpi-card kpi-amber">
                        <div class="kpi-icon"><i class="fas fa-hourglass-half"></i></div>
                        <div class="kpi-text">
                            <div class="kpi-label">Pending Payments</div>
                            <div class="kpi-value"><?= number_format($pendingCount) ?></div>
                            <div class="kpi-sub"><a href="pending_payments/list.php" style="color:#d97706; font-weight:600;">Review now →</a></div>
                        </div>
                    </div>
                    <div class="kpi-card kpi-navy">
                        <div class="kpi-icon"><i class="fas fa-chart-line"></i></div>
                        <div class="kpi-text">
                            <div class="kpi-label">This Month</div>
                            <div class="kpi-value">₱<?= number_format($totalMonthlyCollections, 0) ?></div>
                            <div class="kpi-sub"><?= date('F Y') ?></div>
                        </div>
                    </div>
                    <div class="kpi-card kpi-purple">
                        <div class="kpi-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                        <div class="kpi-text">
                            <div class="kpi-label">Documentary Stamp</div>
                            <div class="kpi-value">₱<?= number_format($documentaryStampFees, 0) ?></div>
                            <div class="kpi-sub">This month BIR tax</div>
                        </div>
                    </div>
                </div>

                <!-- Chart + Quick Actions -->
                <div class="two-col">
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3><i class="fas fa-chart-bar"></i> 6-Month Collection Trend</h3>
                            <a href="collections/monthly.php" class="view-all-link">View Report →</a>
                        </div>
                        <div class="dash-card-body">
                            <canvas id="trendChart" height="140"></canvas>
                        </div>
                    </div>
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                        </div>
                        <div class="dash-card-body">
                            <div class="quick-actions">
                                <a href="payments/add.php" class="quick-action-btn">
                                    <i class="fas fa-file-invoice"></i>
                                    New Certificate
                                </a>
                                <a href="cedula/add.php" class="quick-action-btn">
                                    <i class="fas fa-id-card"></i>
                                    Issue Cedula
                                </a>
                                <a href="disbursement/add.php" class="quick-action-btn">
                                    <i class="fas fa-hand-holding-usd"></i>
                                    Disbursement
                                </a>
                                <a href="pending_payments/list.php" class="quick-action-btn">
                                    <i class="fas fa-hourglass-half"></i>
                                    Pending Status
                                </a>
                                <a href="collections/monthly.php" class="quick-action-btn">
                                    <i class="fas fa-chart-line"></i>
                                    Monthly Report
                                </a>
                                <a href="collections/annual.php" class="quick-action-btn">
                                    <i class="fas fa-calendar-alt"></i>
                                    Annual Report
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="two-col">
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3><i class="fas fa-money-bill"></i> Recent Payments</h3>
                            <a href="payments/list.php" class="view-all-link">View All →</a>
                        </div>
                        <div class="dash-card-body">
                            <table class="mini-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Payer</th>
                                        <th>Service</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recentPayments->num_rows > 0): ?>
                                        <?php while ($payment = $recentPayments->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= date('M d', strtotime($payment['payment_date'])) ?></td>
                                                <td><?= htmlspecialchars(substr($payment['payer_name'], 0, 18)) ?></td>
                                                <td><span class="badge badge-blue"><?= htmlspecialchars(substr($payment['service_type'] ?? 'N/A', 0, 14)) ?></span></td>
                                                <td>₱<?= number_format($payment['amount'], 2) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:20px;">No recent payments</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3><i class="fas fa-hand-holding-usd"></i> Recent Disbursements</h3>
                            <a href="disbursement/list.php" class="view-all-link">View All →</a>
                        </div>
                        <div class="dash-card-body">
                            <table class="mini-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Payee</th>
                                        <th>DV No.</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recentDisbursements->num_rows > 0): ?>
                                        <?php while ($disbursement = $recentDisbursements->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= date('M d', strtotime($disbursement['disburse_date'])) ?></td>
                                                <td><?= htmlspecialchars(substr($disbursement['payee'], 0, 18)) ?></td>
                                                <td><span class="badge badge-amber"><?= htmlspecialchars($disbursement['dv_no'] ?? 'N/A') ?></span></td>
                                                <td>₱<?= number_format($disbursement['release_amount'], 2) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:20px;">No recent disbursements</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div><!-- end content-body -->
        </main>
    </div>

    <script>
        const trendLabels = <?= json_encode(array_column($monthlyTrend, 'label')) ?>;
        const trendData   = <?= json_encode(array_column($monthlyTrend, 'total')) ?>;

        new Chart(document.getElementById('trendChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'Collections',
                    data: trendData,
                    backgroundColor: 'rgba(31, 58, 147, 0.15)',
                    borderColor: '#1F3A93',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => '₱' + ctx.parsed.y.toLocaleString('en-PH', {minimumFractionDigits: 2})
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            font: { size: 11 },
                            callback: v => '₱' + Number(v).toLocaleString()
                        }
                    }
                }
            }
        });
    </script>
    <script src="../assets/js/logout-confirm.js"></script>
</body>
</html>