<?php
include "../config/database.php";
include "../config/resident_session.php";

function build_resident_name(array $resident, string $middleMode = 'full'): string
{
    $first = trim($resident['first_name'] ?? '');
    $middle = trim($resident['middle_name'] ?? '');
    $surname = trim($resident['surname'] ?? '');
    $suffix = trim($resident['suffix'] ?? '');

    $parts = [];
    if ($first !== '') {
        $parts[] = $first;
    }

    if ($middle !== '') {
        if ($middleMode === 'initial') {
            $parts[] = strtoupper(substr($middle, 0, 1)) . '.';
        } elseif ($middleMode === 'full') {
            $parts[] = $middle;
        }
    }

    if ($surname !== '') {
        $parts[] = $surname;
    }

    if ($suffix !== '') {
        $parts[] = $suffix;
    }

    return trim(implode(' ', $parts));
}

$residentId = intval($_SESSION['resident_id']);
$stmt = $conn->prepare("SELECT id, first_name, middle_name, surname, suffix, username, barangay, account_status FROM " . DB_PROFILING . ".residents WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $residentId);
$stmt->execute();
$result = $stmt->get_result();
$resident = $result->fetch_assoc();
$stmt->close();

if (!$resident) {
    session_unset();
    session_destroy();
    header("Location: login.php?error=session");
    exit;
}

$status = strtolower(trim($resident['account_status'] ?? 'active'));
if ($status !== 'active') {
    session_unset();
    session_destroy();
    header("Location: login.php?error=account");
    exit;
}

$fullName = build_resident_name($resident, 'full');
$fullNameAlt = trim(($resident['surname'] ?? '') . ', ' . ($resident['first_name'] ?? '') . ' ' . ($resident['middle_name'] ?? ''));
$fullNameShort = trim(($resident['first_name'] ?? '') . ' ' . ($resident['surname'] ?? ''));
$fullNameShortAlt = trim(($resident['surname'] ?? '') . ', ' . ($resident['first_name'] ?? ''));
$namePattern = '%' . ($resident['surname'] ?? '') . '%' . ($resident['first_name'] ?? '') . '%';

$searchQuery = trim($_GET['search'] ?? '');
$searchParam = "%{$searchQuery}%";

$sql = "SELECT * FROM payments WHERE (resident_id = ? OR (resident_id IS NULL AND (payer_name = ? OR payer_name = ? OR payer_name = ? OR payer_name = ? OR payer_name LIKE ?)))";
$params = [$residentId, $fullName, $fullNameAlt, $fullNameShort, $fullNameShortAlt, $namePattern];
$types = "isssss";

if ($searchQuery !== '') {
    $sql .= " AND (receipt_no LIKE ? OR service_type LIKE ? OR purpose LIKE ?)";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

$sql .= " ORDER BY payment_date DESC, id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
$amountTotal = 0;

while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
    $amountTotal += floatval($row['amount'] ?? 0);
}

$stmt->close();
$totalCount = count($rows);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - Resident Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>

<body class="resident-portal">
    <div class="dashboard-container">
        <?php include "partials/sidebar.php"; ?>

        <main class="main-content">
            <div class="content-header">
                <h1><i class="fas fa-receipt"></i> Payment History</h1>
                <p>Welcome, <?= htmlspecialchars($fullName) ?></p>
            </div>

            <div class="content-body">
                <div class="card">
                    <div class="card-header resident-payments-header">
                        <h3><i class="fas fa-list"></i> Payment Transactions</h3>
                        <form method="GET" action="payment_history.php" class="resident-search-form">
                            <input type="text" name="search" placeholder="Search receipt or purpose"
                                value="<?= htmlspecialchars($searchQuery) ?>"
                                class="resident-search-input">
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Receipt #</th>
                                    <th>Service</th>
                                    <th>Purpose</th>
                                    <th>Amount</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($totalCount > 0): ?>
                                <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= !empty($row['payment_date']) ? date('M d, Y', strtotime($row['payment_date'])) : date('M d, Y') ?>
                                    </td>
                                    <td><strong><?= htmlspecialchars($row['receipt_no']) ?></strong>
                                    </td>
                                    <td><span
                                            class="badge badge-info"><?= htmlspecialchars($row['service_type']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($row['purpose']) ?>
                                    </td>
                                    <td>PHP
                                        <?= number_format($row['amount'] - $row['bir_tax'], 2) ?>
                                    </td>
                                    <td><strong>PHP
                                            <?= number_format($row['amount'], 2) ?></strong>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-inbox" style="font-size: 48px; color: #ccc;"></i>
                                        <p style="margin-top: 15px; color: #999;">No payment history found</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/logout-confirm.js"></script>
</body>

</html>
