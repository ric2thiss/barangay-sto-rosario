<?php
include "../../config/database.php";
include "../../config/session.php";

$year = intval($_GET['year'] ?? date('Y'));

$totalCollected = $conn->query("SELECT COALESCE(SUM(amount + bir_tax), 0) AS total FROM payments")->fetch_assoc()['total'] ?? 0;
$totalDisbursed = $conn->query("SELECT COALESCE(SUM(COALESCE(bir_gross, amount, 0)), 0) AS total FROM disbursements")->fetch_assoc()['total'] ?? 0;
$iraBalance = $totalCollected - $totalDisbursed;
$pendingTotal = $conn->query("SELECT COALESCE(SUM(amount + bir_tax), 0) AS total FROM payment_status WHERE payment_status IN ('pending', 'to_review')")->fetch_assoc()['total'] ?? 0;

$monthlyRows = [];
for ($month = 1; $month <= 12; $month++) {
    $collections = $conn->query("
        SELECT COALESCE(SUM(amount + bir_tax), 0) AS total
        FROM payments
        WHERE MONTH(COALESCE(payment_date, DATE(created_at))) = $month
        AND YEAR(COALESCE(payment_date, DATE(created_at))) = $year
    ")->fetch_assoc()['total'] ?? 0;
    $disbursements = $conn->query("
        SELECT COALESCE(SUM(COALESCE(bir_gross, amount, 0)), 0) AS total
        FROM disbursements
        WHERE MONTH(disburse_date) = $month
        AND YEAR(disburse_date) = $year
    ")->fetch_assoc()['total'] ?? 0;
    $monthlyRows[] = [
        'month' => $month,
        'collections' => floatval($collections),
        'disbursements' => floatval($disbursements),
        'net' => floatval($collections) - floatval($disbursements),
    ];
}

$serviceRows = [];
$serviceResult = $conn->query("
    SELECT COALESCE(NULLIF(TRIM(service_type), ''), 'Unspecified') AS service_type,
           COUNT(*) AS count_total,
           COALESCE(SUM(amount + bir_tax), 0) AS total
    FROM payments
    GROUP BY COALESCE(NULLIF(TRIM(service_type), ''), 'Unspecified')
    ORDER BY total DESC
    LIMIT 10
");
while ($row = $serviceResult->fetch_assoc()) {
    $serviceRows[] = $row;
}

$yearlyRows = [];
$yearlyCollectionResult = $conn->query("
    SELECT YEAR(COALESCE(payment_date, DATE(created_at))) AS year_no,
           COALESCE(SUM(amount + bir_tax), 0) AS total
    FROM payments
    GROUP BY YEAR(COALESCE(payment_date, DATE(created_at)))
    ORDER BY year_no ASC
");
while ($row = $yearlyCollectionResult->fetch_assoc()) {
    $yearNo = intval($row['year_no']);
    $yearlyRows[$yearNo] = [
        'year' => $yearNo,
        'collections' => floatval($row['total']),
        'disbursements' => 0,
        'net' => floatval($row['total']),
    ];
}

$yearlyDisbursementResult = $conn->query("
    SELECT YEAR(disburse_date) AS year_no,
           COALESCE(SUM(COALESCE(bir_gross, amount, 0)), 0) AS total
    FROM disbursements
    GROUP BY YEAR(disburse_date)
    ORDER BY year_no ASC
");
while ($row = $yearlyDisbursementResult->fetch_assoc()) {
    $yearNo = intval($row['year_no']);
    if (!isset($yearlyRows[$yearNo])) {
        $yearlyRows[$yearNo] = [
            'year' => $yearNo,
            'collections' => 0,
            'disbursements' => 0,
            'net' => 0,
        ];
    }
    $yearlyRows[$yearNo]['disbursements'] = floatval($row['total']);
    $yearlyRows[$yearNo]['net'] = $yearlyRows[$yearNo]['collections'] - $yearlyRows[$yearNo]['disbursements'];
}
ksort($yearlyRows);
$yearlyRows = array_values($yearlyRows);

$iraMovementRows = [];
$iraCollectionsByMonth = [];
$iraCollectionResult = $conn->query("
    SELECT YEAR(COALESCE(payment_date, DATE(created_at))) AS year_no,
           MONTH(COALESCE(payment_date, DATE(created_at))) AS month_no,
           COALESCE(SUM(amount + bir_tax), 0) AS total
    FROM payments
    GROUP BY YEAR(COALESCE(payment_date, DATE(created_at))), MONTH(COALESCE(payment_date, DATE(created_at)))
");
while ($row = $iraCollectionResult->fetch_assoc()) {
    $key = intval($row['year_no']) . '-' . intval($row['month_no']);
    $iraCollectionsByMonth[$key] = floatval($row['total']);
}

$iraDisbursementsByMonth = [];
$iraDisbursementResult = $conn->query("
    SELECT YEAR(disburse_date) AS year_no,
           MONTH(disburse_date) AS month_no,
           COALESCE(SUM(COALESCE(bir_gross, amount, 0)), 0) AS total
    FROM disbursements
    GROUP BY YEAR(disburse_date), MONTH(disburse_date)
");
while ($row = $iraDisbursementResult->fetch_assoc()) {
    $key = intval($row['year_no']) . '-' . intval($row['month_no']);
    $iraDisbursementsByMonth[$key] = floatval($row['total']);
}

$iraMovementKeys = array_unique(array_merge(array_keys($iraCollectionsByMonth), array_keys($iraDisbursementsByMonth)));
usort($iraMovementKeys, function ($a, $b) {
    [$yearA, $monthA] = array_map('intval', explode('-', $a));
    [$yearB, $monthB] = array_map('intval', explode('-', $b));

    return ($yearB <=> $yearA) ?: ($monthB <=> $monthA);
});

foreach ($iraMovementKeys as $key) {
    [$yearNo, $monthNo] = array_map('intval', explode('-', $key));
    $collections = $iraCollectionsByMonth[$key] ?? 0;
    $disbursements = $iraDisbursementsByMonth[$key] ?? 0;
    $iraMovementRows[] = [
        'label' => date('F Y', mktime(0, 0, 0, $monthNo, 1, $yearNo)),
        'collections' => $collections,
        'disbursements' => $disbursements,
        'net' => $collections - $disbursements,
    ];
}

$monthLabels = array_map(fn($row) => date('M', mktime(0, 0, 0, $row['month'], 1)), $monthlyRows);
$monthlyCollectionsData = array_map(fn($row) => $row['collections'], $monthlyRows);
$monthlyDisbursementsData = array_map(fn($row) => $row['disbursements'], $monthlyRows);
$monthlyNetData = array_map(fn($row) => $row['net'], $monthlyRows);
$yearLabels = array_map(fn($row) => (string) $row['year'], $yearlyRows);
$yearlyCollectionsData = array_map(fn($row) => $row['collections'], $yearlyRows);
$yearlyDisbursementsData = array_map(fn($row) => $row['disbursements'], $yearlyRows);
$yearlyNetData = array_map(fn($row) => $row['net'], $yearlyRows);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IRA/DV Analytics - Barangay Sto. Rosario</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="dashboard-container">
        <?php
        $path_prefix = '../';
        include "../partials/sidebar.php";
        ?>

        <main class="main-content">
            <div class="content-header">
                <h1><i class="fas fa-landmark"></i> IRA/DV Analytics</h1>
            </div>

            <div class="content-body">
                <div class="card">
                    <form method="GET" style="display:flex; gap:15px; align-items:end;">
                        <div class="form-group" style="flex:1; margin-bottom:0;">
                            <label for="year">Year</label>
                            <select id="year" name="year">
                                <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" style="flex:0.3;">Generate</button>
                    </form>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <h4>Total Money Collected</h4>
                        <div class="stat-value">PHP <?= number_format($totalCollected, 2) ?></div>
                    </div>
                    <div class="stat-card red">
                        <h4>Total DV/Disbursements</h4>
                        <div class="stat-value">PHP <?= number_format($totalDisbursed, 2) ?></div>
                    </div>
                    <div class="stat-card green">
                        <h4>IRA Balance</h4>
                        <div class="stat-value">PHP <?= number_format($iraBalance, 2) ?></div>
                    </div>
                    <div class="stat-card blue">
                        <h4>Pending/Review</h4>
                        <div class="stat-value">PHP <?= number_format($pendingTotal, 2) ?></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle"></i> Account Basis</h3>
                    </div>
                    <p style="color:#4a5568; line-height:1.6;">
                        The IRA account is where all collected payments are deposited. Every DV/disbursement is deducted
                        from this running IRA balance.
                    </p>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-line"></i> Monthly IRA/DV Line Chart - <?= $year ?></h3>
                        </div>
                        <canvas id="monthlyChart" height="140"></canvas>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-line"></i> Yearly Graph</h3>
                        </div>
                        <canvas id="yearlyChart" height="140"></canvas>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar"></i> Monthly IRA/DV Movement - <?= $year ?></h3>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Collections</th>
                                    <th>Disbursements</th>
                                    <th>Net</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthlyRows as $row): ?>
                                <tr>
                                    <td><?= date('F', mktime(0, 0, 0, $row['month'], 1)) ?></td>
                                    <td>PHP <?= number_format($row['collections'], 2) ?></td>
                                    <td>PHP <?= number_format($row['disbursements'], 2) ?></td>
                                    <td><strong>PHP <?= number_format($row['net'], 2) ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-landmark"></i> IRA Account Movement - All Records</h3>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Collections Added</th>
                                    <th>DV/Disbursements Deducted</th>
                                    <th>Net Movement</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($iraMovementRows)): ?>
                                <?php foreach ($iraMovementRows as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['label']) ?></td>
                                    <td>PHP <?= number_format($row['collections'], 2) ?></td>
                                    <td>PHP <?= number_format($row['disbursements'], 2) ?></td>
                                    <td><strong>PHP <?= number_format($row['net'], 2) ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align:center; padding:30px;">No IRA/DV movement found.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-ranking-star"></i> Top Payment Categories</h3>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Records</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($serviceRows as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['service_type']) ?></td>
                                    <td><?= number_format($row['count_total']) ?></td>
                                    <td>PHP <?= number_format($row['total'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        const pesoFormatter = new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'PHP',
            minimumFractionDigits: 2
        });

        function moneyTick(value) {
            return 'PHP ' + Number(value).toLocaleString();
        }

        const chartOptions = {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + pesoFormatter.format(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: moneyTick
                    }
                }
            }
        };

        new Chart(document.getElementById('monthlyChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: <?= json_encode($monthLabels) ?>,
                datasets: [{
                        label: 'Collections',
                        data: <?= json_encode($monthlyCollectionsData) ?>,
                        backgroundColor: 'rgba(31, 58, 147, 0.12)',
                        borderColor: '#1a3280',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.25
                    },
                    {
                        label: 'DV/Disbursements',
                        data: <?= json_encode($monthlyDisbursementsData) ?>,
                        backgroundColor: 'rgba(245, 101, 101, 0.12)',
                        borderColor: '#e53e3e',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.25
                    },
                    {
                        label: 'Net',
                        data: <?= json_encode($monthlyNetData) ?>,
                        borderColor: '#48bb78',
                        backgroundColor: 'rgba(72, 187, 120, 0.12)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.25
                    }
                ]
            },
            options: chartOptions
        });

        new Chart(document.getElementById('yearlyChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: <?= json_encode($yearLabels) ?>,
                datasets: [{
                        label: 'Collections',
                        data: <?= json_encode($yearlyCollectionsData) ?>,
                        borderColor: '#1F3A93',
                        backgroundColor: 'rgba(31, 58, 147, 0.12)',
                        fill: true,
                        tension: 0.25
                    },
                    {
                        label: 'DV/Disbursements',
                        data: <?= json_encode($yearlyDisbursementsData) ?>,
                        borderColor: '#f56565',
                        backgroundColor: 'rgba(245, 101, 101, 0.12)',
                        fill: true,
                        tension: 0.25
                    },
                    {
                        label: 'Net',
                        data: <?= json_encode($yearlyNetData) ?>,
                        borderColor: '#48bb78',
                        backgroundColor: 'rgba(72, 187, 120, 0.12)',
                        fill: true,
                        tension: 0.25
                    }
                ]
            },
            options: chartOptions
        });
    </script>
    <script src="../../assets/js/logout-confirm.js"></script>
</body>

</html>



