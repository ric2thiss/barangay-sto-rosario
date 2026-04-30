<?php
include "../../config/database.php";
include "../../config/session.php";

$types = [
    'donation' => [
        'title' => 'Donation',
        'icon' => 'fa-heart',
        'service' => 'Donation',
        'ref_prefix' => 'DON',
        'table' => 'donation',
        'ref_column' => 'donation_ref',
        'date_column' => 'donation_date',
        'details_label' => 'To Whom / What Activities',
        'details_placeholder' => 'Specify the recipient or activities benefiting from this donation',
        'amount_label' => 'Donation Amount',
    ],
    'garbage' => [
        'title' => 'Garbage Payment',
        'icon' => 'fa-trash',
        'service' => 'Garbage',
        'ref_prefix' => 'GAR',
        'table' => 'garbage',
        'ref_column' => 'garbage_ref',
        'date_column' => 'garbage_date',
        'details_label' => 'Garbage Collection Details',
        'details_placeholder' => 'Specify the billing period or garbage collection details',
        'amount_label' => 'Garbage Payment Amount',
    ],
    'rental' => [
        'title' => 'Rental',
        'icon' => 'fa-building',
        'service' => 'Rental',
        'ref_prefix' => 'RENT',
    ],
];

$type = strtolower(trim($_GET['type'] ?? $_POST['type'] ?? 'donation'));
if (!isset($types[$type])) {
    $type = 'donation';
}

$config = $types[$type];
$isRental = $type === 'rental';
$error = '';

$lastReceipt = $conn->query("SELECT receipt_no FROM payments ORDER BY id DESC LIMIT 1")->fetch_assoc();
$nextReceipt = $lastReceipt ? (intval($lastReceipt['receipt_no']) + 1) : 100001;

$CHAIR_PRICE = 5.00;
$COURT_PRICE = 100.00;

function current_user_id(mysqli $conn): ?int
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $userId = intval($_SESSION['user_id']);
    $userCheck = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $userCheck->bind_param("i", $userId);
    $userCheck->execute();
    $userCheck->store_result();
    $exists = $userCheck->num_rows > 0;
    $userCheck->close();

    return $exists ? $userId : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiptNo = trim($_POST['receipt_no'] ?? '');
    $paymentDate = trim($_POST['payment_date'] ?? '');
    $payerName = trim($_POST['payer_name'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $birTax = floatval($_POST['bir_tax'] ?? 0);
    $receivedBy = current_user_id($conn);

    if ($receiptNo === '' || $paymentDate === '' || $payerName === '' || $purpose === '') {
        $error = 'Please fill in all required fields.';
    } elseif ($isRental) {
        $rentCourt = isset($_POST['rent_court']);
        $rentChairs = isset($_POST['rent_chairs']);
        $chairQuantity = intval($_POST['chair_quantity'] ?? 0);
        $usageDate = trim($_POST['usage_date'] ?? '');

        if (!$rentCourt && !$rentChairs) {
            $error = 'Please select at least one facility to rent.';
        } elseif ($rentChairs && $chairQuantity <= 0) {
            $error = 'Please specify the number of chairs.';
        } elseif ($usageDate === '') {
            $error = 'Please select the date of use.';
        } else {
            $totalAmount = 0.00;
            $items = [];

            if ($rentCourt) {
                $totalAmount += $COURT_PRICE;
                $items[] = ['type' => 'covered_court', 'qty' => 1, 'price' => $COURT_PRICE, 'subtotal' => $COURT_PRICE];
            }

            if ($rentChairs) {
                $chairSubtotal = $chairQuantity * $CHAIR_PRICE;
                $totalAmount += $chairSubtotal;
                $items[] = ['type' => 'chair', 'qty' => $chairQuantity, 'price' => $CHAIR_PRICE, 'subtotal' => $chairSubtotal];
            }
        }
    } else {
        $details = trim($_POST['details'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);

        if ($details === '' || $amount <= 0) {
            $error = 'Please fill in all required fields.';
        }
    }

    if ($error === '') {
        $conn->begin_transaction();

        $detailOk = false;
        $paymentOk = false;
        $paymentPurpose = $purpose;

        if ($isRental) {
            $rentalRef = $config['ref_prefix'] . '-' . date('YmdHis') . '-MANUAL';
            $rentalDate = $paymentDate;

            $rentalStmt = $conn->prepare("
                INSERT INTO rental (rental_ref, resident_id, resident_name, rental_date, purpose, total_amount, remarks)
                VALUES (?, NULL, ?, ?, ?, ?, ?)
            ");
            $rentalStmt->bind_param("ssssds", $rentalRef, $payerName, $rentalDate, $purpose, $totalAmount, $remarks);
            $detailOk = $rentalStmt->execute();
            $rentalId = $conn->insert_id;
            $rentalStmt->close();

            if ($detailOk) {
                foreach ($items as $item) {
                    $itemStmt = $conn->prepare("
                        INSERT INTO rental_items (rental_id, item_type, quantity, unit_price, subtotal, usage_date)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $itemStmt->bind_param("isidds", $rentalId, $item['type'], $item['qty'], $item['price'], $item['subtotal'], $usageDate);
                    if (!$itemStmt->execute()) {
                        $detailOk = false;
                    }
                    $itemStmt->close();
                }
            }

            $amountForPayment = $totalAmount;
        } else {
            $manualRef = $config['ref_prefix'] . '-' . date('YmdHis') . '-MANUAL';
            $detailDate = $paymentDate;
            $table = $config['table'];
            $refColumn = $config['ref_column'];
            $dateColumn = $config['date_column'];

            $detailStmt = $conn->prepare("
                INSERT INTO {$table} ({$refColumn}, resident_id, resident_name, {$dateColumn}, purpose, recipient_activities, amount, remarks)
                VALUES (?, NULL, ?, ?, ?, ?, ?, ?)
            ");
            $detailStmt->bind_param("sssssds", $manualRef, $payerName, $detailDate, $purpose, $details, $amount, $remarks);
            $detailOk = $detailStmt->execute();
            $detailStmt->close();

            $amountForPayment = $amount;
        }

        if ($detailOk) {
            $serviceType = $config['service'];
            $payStmt = $conn->prepare("
                INSERT INTO payments (payer_name, service_type, purpose, amount, bir_tax, receipt_no, payment_date, remarks, received_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $payStmt->bind_param("sssddsssi", $payerName, $serviceType, $paymentPurpose, $amountForPayment, $birTax, $receiptNo, $paymentDate, $remarks, $receivedBy);
            $paymentOk = $payStmt->execute();
            $payStmt->close();
        }

        if ($detailOk && $paymentOk) {
            $conn->commit();
            header("Location: list.php?success=1");
            exit;
        }

        $conn->rollback();
        $error = 'Failed to save payment. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($config['title']) ?> - Barangay Sto. Rosario</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../../assets/images/logo.jpg" alt="Barangay Logo"
                    style="width: 80px; height: 80px; border-radius: 50%; margin-bottom: 10px; border: 3px solid #ffffff;">
                <h2>BARANGAY STO. ROSARIO</h2>
                <p>Treasurer Module</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li>
                    <details class="sidebar-dropdown active">
                        <summary><i class="fas fa-money-bill-wave"></i> Payments <i class="fas fa-chevron-right dropdown-caret"></i></summary>
                        <ul class="submenu">
                            <li><a href="list.php"><i class="fas fa-list"></i> All Payments</a></li>
                            <li><a href="add.php"><i class="fas fa-plus"></i> Certificate</a></li>
                            <li><a href="manual.php?type=donation" class="<?= $type === 'donation' ? 'active' : '' ?>"><i class="fas fa-heart"></i> Donation</a></li>
                            <li><a href="manual.php?type=garbage" class="<?= $type === 'garbage' ? 'active' : '' ?>"><i class="fas fa-trash"></i> Garbage</a></li>
                            <li><a href="manual.php?type=rental" class="<?= $type === 'rental' ? 'active' : '' ?>"><i class="fas fa-building"></i> Rental</a></li>
                        </ul>
                    </details>
                </li>
                <li><a href="../pending_payments/list.php"><i class="fas fa-hourglass-half"></i> Pending Status</a></li>
                <li><a href="../cedula/list.php"><i class="fas fa-id-card"></i> Cedula</a></li>
                <li><a href="../disbursement/list.php"><i class="fas fa-hand-holding-usd"></i> Disbursements</a></li>
                <li><a href="../collections/monthly.php"><i class="fas fa-chart-line"></i> Monthly Collections</a></li>
                <li><a href="../collections/analytics.php"><i class="fas fa-landmark"></i> IRA/DV Analytics</a></li>
                <li><a href="../collections/annual.php"><i class="fas fa-calendar-alt"></i> Annual Report</a></li>
                <li><a href="../change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
                <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="content-header">
                <h1><i class="fas <?= htmlspecialchars($config['icon']) ?>"></i> Record <?= htmlspecialchars($config['title']) ?></h1>
            </div>

            <div class="content-body">
                <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-cash-register"></i> Face-to-Face Payment</h3>
                    </div>

                    <form method="POST" action="manual.php?type=<?= htmlspecialchars($type) ?>">
                        <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="receipt_no"><i class="fas fa-receipt"></i> Receipt Number *</label>
                                <input type="text" id="receipt_no" name="receipt_no" value="<?= htmlspecialchars($_POST['receipt_no'] ?? $nextReceipt) ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="payment_date"><i class="fas fa-calendar"></i> Payment Date *</label>
                                <input type="date" id="payment_date" name="payment_date" value="<?= htmlspecialchars($_POST['payment_date'] ?? date('Y-m-d')) ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="payer_name"><i class="fas fa-user"></i> Resident / Payer Name *</label>
                            <input type="text" id="payer_name" name="payer_name" placeholder="Enter resident or payer full name" value="<?= htmlspecialchars($_POST['payer_name'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="purpose"><i class="fas fa-info-circle"></i> Purpose *</label>
                            <input type="text" id="purpose" name="purpose" placeholder="<?= $isRental ? 'e.g., Birthday Party, Community Meeting' : 'Enter payment purpose' ?>" value="<?= htmlspecialchars($_POST['purpose'] ?? '') ?>" required>
                        </div>

                        <?php if ($isRental): ?>
                        <div class="form-group">
                            <label>Select Facilities to Rent *</label>
                            <div style="background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 12px;">
                                <div>Chair: PHP <?= number_format($CHAIR_PRICE, 2) ?> each</div>
                                <div>Covered Court: PHP <?= number_format($COURT_PRICE, 2) ?> per use</div>
                            </div>
                            <label style="display: inline-flex; align-items: center; gap: 8px; margin-right: 20px;">
                                <input type="checkbox" id="rentCourt" name="rent_court" value="1" <?= isset($_POST['rent_court']) ? 'checked' : '' ?>> Covered Court
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 8px;">
                                <input type="checkbox" id="rentChairs" name="rent_chairs" value="1" <?= isset($_POST['rent_chairs']) ? 'checked' : '' ?>> Chairs
                            </label>
                        </div>

                        <div class="form-row">
                            <div class="form-group" id="chairQuantityField">
                                <label for="chairQuantity">Number of Chairs *</label>
                                <input type="number" id="chairQuantity" name="chair_quantity" min="1" value="<?= htmlspecialchars($_POST['chair_quantity'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="usageDate">Date of Use *</label>
                                <input type="date" id="usageDate" name="usage_date" value="<?= htmlspecialchars($_POST['usage_date'] ?? '') ?>">
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="form-group">
                            <label for="details"><i class="fas fa-align-left"></i> <?= htmlspecialchars($config['details_label']) ?> *</label>
                            <textarea id="details" name="details" rows="3" placeholder="<?= htmlspecialchars($config['details_placeholder']) ?>" required><?= htmlspecialchars($_POST['details'] ?? '') ?></textarea>
                        </div>
                        <?php endif; ?>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="amount"><i class="fas fa-peso-sign"></i> <?= $isRental ? 'Rental Amount' : htmlspecialchars($config['amount_label']) ?> *</label>
                                <input type="number" id="amount" name="amount" step="0.01" min="0" value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>" <?= $isRental ? 'readonly' : 'required' ?> style="<?= $isRental ? 'font-weight: bold; background: #e8f0ff;' : '' ?>">
                            </div>

                            <div class="form-group">
                                <label for="bir_tax"><i class="fas fa-percent"></i> BIR Tax/Fee *</label>
                                <input type="number" id="bir_tax" name="bir_tax" step="0.01" min="0" value="<?= htmlspecialchars($_POST['bir_tax'] ?? '0') ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="total"><i class="fas fa-calculator"></i> Total Paid</label>
                                <input type="number" id="total" name="total" step="0.01" readonly style="font-weight: bold; font-size: 18px; background: #e8f0ff;">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="remarks"><i class="fas fa-comment"></i> Remarks</label>
                            <textarea id="remarks" name="remarks" rows="3" placeholder="Enter any additional notes or remarks..."><?= htmlspecialchars($_POST['remarks'] ?? '') ?></textarea>
                        </div>

                        <div style="display: flex; gap: 10px; margin-top: 25px;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;">
                                <i class="fas fa-save"></i> Save Paid Payment
                            </button>
                            <a href="list.php" class="btn btn-secondary" style="flex: 1; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        const isRental = <?= $isRental ? 'true' : 'false' ?>;
        const chairPrice = <?= json_encode($CHAIR_PRICE) ?>;
        const courtPrice = <?= json_encode($COURT_PRICE) ?>;
        const amountInput = document.getElementById('amount');
        const birTaxInput = document.getElementById('bir_tax');
        const totalInput = document.getElementById('total');

        function calculateRentalAmount() {
            if (!isRental) {
                return;
            }

            const rentCourt = document.getElementById('rentCourt').checked;
            const rentChairs = document.getElementById('rentChairs').checked;
            const chairQty = parseInt(document.getElementById('chairQuantity').value, 10) || 0;
            const chairField = document.getElementById('chairQuantityField');
            let amount = 0;

            chairField.style.display = rentChairs ? 'block' : 'none';

            if (rentCourt) {
                amount += courtPrice;
            }

            if (rentChairs && chairQty > 0) {
                amount += chairQty * chairPrice;
            }

            amountInput.value = amount.toFixed(2);
        }

        function calculateTotal() {
            calculateRentalAmount();
            const amount = parseFloat(amountInput.value) || 0;
            const birTax = parseFloat(birTaxInput.value) || 0;
            totalInput.value = (amount + birTax).toFixed(2);
        }

        amountInput.addEventListener('input', calculateTotal);
        birTaxInput.addEventListener('input', calculateTotal);

        if (isRental) {
            document.getElementById('rentCourt').addEventListener('change', calculateTotal);
            document.getElementById('rentChairs').addEventListener('change', calculateTotal);
            document.getElementById('chairQuantity').addEventListener('input', calculateTotal);
        }

        calculateTotal();
    </script>
    <script src="../../assets/js/logout-confirm.js"></script>
</body>

</html>





