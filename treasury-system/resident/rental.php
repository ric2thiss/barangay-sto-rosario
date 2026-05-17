<?php
include "../config/database.php";
include "../config/resident_session.php";

$error = '';
$success = '';

// Get resident data
$residentId = $_SESSION['resident_id'];
$resident = null;

$residentStmt = $conn->prepare("SELECT id, first_name, middle_name, surname FROM " . DB_PROFILING . ".residents WHERE id = ?");
$residentStmt->bind_param("i", $residentId);
$residentStmt->execute();
$residentResult = $residentStmt->get_result();
if ($residentResult->num_rows > 0) {
    $resident = $residentResult->fetch_assoc();
}
$residentStmt->close();

if (!$resident) {
    die("Resident information not found.");
}

// Build resident name
$residentName = trim(($resident['first_name'] ?? '') . ' ' . ($resident['middle_name'] ?? '') . ' ' . ($resident['surname'] ?? ''));
$residentName = preg_replace('/\s+/', ' ', $residentName);

// Check for pending rental
$hasPendingRental = false;
$pendingStmt = $conn->prepare("
    SELECT id FROM payment_status 
    WHERE resident_id = ? AND certificate_type = 'Rental' AND payment_status = 'pending'
    LIMIT 1
");
$pendingStmt->bind_param("i", $residentId);
$pendingStmt->execute();
$pendingResult = $pendingStmt->get_result();
if ($pendingResult->num_rows > 0) {
    $hasPendingRental = true;
}
$pendingStmt->close();

// Pricing constants
$CHAIR_PRICE = 5.00;
$COURT_PRICE = 100.00;

// Handle rental submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($hasPendingRental) {
        $error = 'You already have a pending rental request. Please wait for the treasurer to process it.';
    } else {
        $purpose = htmlspecialchars($_POST['purpose'] ?? '');
        $rentCourt = isset($_POST['rent_court']) ? true : false;
        $rentChairs = isset($_POST['rent_chairs']) ? true : false;
        $chairQuantity = intval($_POST['chair_quantity'] ?? 0);
        $usageDate = $_POST['usage_date'] ?? '';

        if (empty($purpose) || !($rentCourt || $rentChairs) || empty($usageDate)) {
            $error = 'Please fill in all required fields.';
        } elseif ($rentChairs && $chairQuantity <= 0) {
            $error = 'Please specify the number of chairs.';
        } else {
            // Calculate total amount
            $totalAmount = 0;
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

            $conn->begin_transaction();

            // Generate rental reference
            $rentalRef = 'RENT-' . date('YmdHis') . '-' . $residentId;
            $rentalDate = date('Y-m-d');

            // Insert into rental table
            $rentalStmt = $conn->prepare("
                INSERT INTO rental (rental_ref, resident_id, resident_name, rental_date, purpose, total_amount)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $rentalStmt->bind_param("sisssd", $rentalRef, $residentId, $residentName, $rentalDate, $purpose, $totalAmount);
            $rentalOk = $rentalStmt->execute();
            $rentalId = $conn->insert_id;
            $rentalStmt->close();

            // Insert rental items
            $itemsOk = true;
            if ($rentalOk) {
                foreach ($items as $item) {
                    $itemStmt = $conn->prepare("
                        INSERT INTO rental_items (rental_id, item_type, quantity, unit_price, subtotal, usage_date)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $itemStmt->bind_param("isidds", $rentalId, $item['type'], $item['qty'], $item['price'], $item['subtotal'], $usageDate);
                    if (!$itemStmt->execute()) {
                        $itemsOk = false;
                    }
                    $itemStmt->close();
                }
            }

            // Insert into payment_status
            $paymentOk = false;
            if ($rentalOk && $itemsOk) {
                $certificateType = 'Rental';
                $paymentPurpose = $purpose;
                $birTax = 0;

                $payStmt = $conn->prepare("
                    INSERT INTO payment_status (resident_id, certificate_type, purpose, resident_fname, payment_status, amount, bir_tax, created_at)
                    VALUES (?, ?, ?, ?, 'pending', ?, ?, NOW())
                ");
                $payStmt->bind_param("isssdd", $residentId, $certificateType, $paymentPurpose, $residentName, $totalAmount, $birTax);
                $paymentOk = $payStmt->execute();
                $payStmt->close();
            }

            if ($rentalOk && $itemsOk && $paymentOk) {
                $conn->commit();
                header('Location: pending_payments.php?submitted=1');
                exit;
            }

            $conn->rollback();
            $error = 'Failed to submit rental request. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental - Resident Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        .rental-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 8px;
        }

        .section-header {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #34495e;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group input[type="number"],
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #dcdde1;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #3498db;
            outline: none;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group input:disabled,
        .form-group textarea:disabled {
            background-color: #f5f6fa;
            color: #7f8c8d;
            cursor: not-allowed;
        }

        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .alert {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary { background-color: #3498db; color: white; }
        .btn-primary:hover { background-color: #2980b9; transform: translateY(-1px); }
        .btn-secondary { background-color: #95a5a6; color: white; }
        .btn-secondary:hover { background-color: #7f8c8d; }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
        }

        .modal.open {
            display: flex !important;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 550px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .modal-header {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-body { margin-bottom: 30px; }

        .confirmation-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .confirmation-label { font-weight: 600; color: #7f8c8d; }
        .confirmation-value { color: #2c3e50; font-weight: 500; }

        .highlight-box {
            background-color: #ebf5fb;
            border: 2px dashed #3498db;
            padding: 20px;
            border-radius: 8px;
            margin-top: 10px;
        }

        .highlight-amount {
            font-size: 24px;
            font-weight: 800;
            color: #2980b9;
            text-align: center;
        }

        .price-info {
            font-size: 13px;
            color: #7f8c8d;
            margin-top: 5px;
        }

        .conditional-field {
            display: none;
            margin-top: 10px;
            padding-left: 28px;
        }

        .conditional-field.show {
            display: block;
        }
    </style>
</head>

<body class="resident-portal">
    <div class="dashboard-container">
        <?php include "partials/sidebar.php"; ?>

        <main class="main-content">
            <div class="content-header">
                <h1><i class="fas fa-building"></i> Rent Facilities</h1>
                <p>Welcome, <?= htmlspecialchars($residentName) ?></p>
            </div>

            <div class="content-body">
                <div class="rental-container">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($hasPendingRental): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-clock"></i> You already have a pending rental request. Please wait for the treasurer to process it before making another rental.
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="rentalForm">
                        <div class="form-section">
                            <div class="section-header"><i class="fas fa-user-circle"></i> Renter Information</div>
                            <div class="form-group">
                                <label for="renterName">Full Name</label>
                                <input type="text" id="renterName" value="<?php echo htmlspecialchars($residentName); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label for="rentalDate">Request Date</label>
                                <input type="date" id="rentalDate" value="<?php echo date('Y-m-d'); ?>" disabled>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="section-header"><i class="fas fa-map-marked-alt"></i> Rental Details</div>
                            
                            <div class="form-group">
                                <label for="purpose">Purpose of Rental *</label>
                                <input type="text" id="purpose" name="purpose" 
                                       placeholder="e.g., Birthday Party, Community Meeting, Sports Event"
                                       value="<?php echo htmlspecialchars($_POST['purpose'] ?? ''); ?>"
                                       <?php echo $hasPendingRental ? 'disabled' : ''; ?> required>
                            </div>

                            <div class="form-group">
                                <label>Select Facilities to Rent *</label>
                                <div class="checkbox-group">
                                    <div class="checkbox-item">
                                        <input type="checkbox" id="rentCourt" name="rent_court" value="1"
                                               <?php echo $hasPendingRental ? 'disabled' : ''; ?>>
                                        <label for="rentCourt">Covered Court <span class="price-info">(₱<?php echo number_format($COURT_PRICE, 2); ?> per use)</span></label>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" id="rentChairs" name="rent_chairs" value="1"
                                               onchange="toggleChairQuantity()"
                                               <?php echo $hasPendingRental ? 'disabled' : ''; ?>>
                                        <label for="rentChairs">Chairs <span class="price-info">(₱<?php echo number_format($CHAIR_PRICE, 2); ?> each)</span></label>
                                    </div>
                                    <div id="chairQuantityField" class="conditional-field">
                                        <label for="chairQuantity">Quantity of Chairs</label>
                                        <input type="number" id="chairQuantity" name="chair_quantity" min="1"
                                               placeholder="0"
                                               value="<?php echo htmlspecialchars($_POST['chair_quantity'] ?? ''); ?>"
                                               <?php echo $hasPendingRental ? 'disabled' : ''; ?>
                                               oninput="updateTotal()">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="usageDate">Date of Use *</label>
                                <input type="date" id="usageDate" name="usage_date"
                                       value="<?php echo htmlspecialchars($_POST['usage_date'] ?? ''); ?>"
                                       <?php echo $hasPendingRental ? 'disabled' : ''; ?> required>
                            </div>

                            <?php if (!$hasPendingRental): ?>
                                <div class="highlight-box">
                                    <div style="font-weight: 600; margin-bottom: 8px; color: #3498db;">Total Amount:</div>
                                    <div class="highlight-amount" id="totalAmount">₱0.00</div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-actions">
                            <?php if (!$hasPendingRental): ?>
                                <button type="button" class="btn btn-primary" onclick="openConfirmationModal()">
                                    <i class="fas fa-paper-plane"></i> Submit Rental Request
                                </button>
                                <a href="pending_payments.php" class="btn btn-secondary">Cancel</a>
                            <?php else: ?>
                                <a href="pending_payments.php" class="btn btn-secondary">Back to Portal</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-check-circle" style="color: #27ae60;"></i> Confirm Rental
            </div>
            <div class="modal-body">
                <div class="confirmation-item">
                    <span class="confirmation-label">Renter:</span>
                    <span class="confirmation-value"><?php echo htmlspecialchars($residentName); ?></span>
                </div>
                <div class="confirmation-item">
                    <span class="confirmation-label">Purpose:</span>
                    <span class="confirmation-value" id="modalPurpose">-</span>
                </div>
                <div class="confirmation-item">
                    <span class="confirmation-label">Date of Use:</span>
                    <span class="confirmation-value" id="modalUsageDate">-</span>
                </div>
                <div id="itemsList" style="margin-top: 10px; border-top: 1px solid #f0f0f0;">
                    <!-- Items will be inserted here -->
                </div>
                <div class="confirmation-item" style="border-top: 2px solid #3498db; margin-top: 15px; padding-top: 15px;">
                    <span class="confirmation-label" style="color: #2c3e50;">Total Amount:</span>
                    <span class="confirmation-value" id="modalTotal" style="color: #2980b9; font-size: 20px; font-weight: 800;">₱0.00</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeConfirmationModal()">Cancel</button>
                <button type="button" class="btn btn-primary" style="background-color: #27ae60;" onclick="submitRental()">
                    Confirm & Submit
                </button>
            </div>
        </div>
    </div>

    <script>
        const CHAIR_PRICE = <?php echo $CHAIR_PRICE; ?>;
        const COURT_PRICE = <?php echo $COURT_PRICE; ?>;

        function toggleChairQuantity() {
            const rentChairs = document.getElementById('rentChairs').checked;
            const chairField = document.getElementById('chairQuantityField');
            if (rentChairs) {
                chairField.classList.add('show');
            } else {
                chairField.classList.remove('show');
                document.getElementById('chairQuantity').value = '';
            }
            updateTotal();
        }

        function updateTotal() {
            const rentCourt = document.getElementById('rentCourt').checked;
            const rentChairs = document.getElementById('rentChairs').checked;
            const chairQty = parseInt(document.getElementById('chairQuantity').value) || 0;

            let total = 0;
            if (rentCourt) total += COURT_PRICE;
            if (rentChairs) total += chairQty * CHAIR_PRICE;

            document.getElementById('totalAmount').textContent = new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP'
            }).format(total);
        }

        document.getElementById('rentCourt').addEventListener('change', updateTotal);

        function openConfirmationModal() {
            const purpose = document.getElementById('purpose').value.trim();
            const rentCourt = document.getElementById('rentCourt').checked;
            const rentChairs = document.getElementById('rentChairs').checked;
            const chairQty = parseInt(document.getElementById('chairQuantity').value) || 0;
            const usageDate = document.getElementById('usageDate').value;

            if (!purpose || (!rentCourt && !rentChairs) || !usageDate) {
                alert('Please fill in all required fields.');
                return;
            }

            if (rentChairs && chairQty <= 0) {
                alert('Please specify the quantity of chairs.');
                return;
            }

            document.getElementById('modalPurpose').textContent = purpose;
            document.getElementById('modalUsageDate').textContent = new Date(usageDate).toLocaleDateString('en-PH', {
                year: 'numeric', month: 'long', day: 'numeric'
            });

            let itemsHtml = '';
            let total = 0;
            if (rentCourt) {
                itemsHtml += `<div class="confirmation-item"><span class="confirmation-label">Covered Court:</span><span class="confirmation-value">₱${COURT_PRICE.toFixed(2)}</span></div>`;
                total += COURT_PRICE;
            }
            if (rentChairs) {
                const chairTotal = chairQty * CHAIR_PRICE;
                itemsHtml += `<div class="confirmation-item"><span class="confirmation-label">Chairs (${chairQty}x):</span><span class="confirmation-value">₱${chairTotal.toFixed(2)}</span></div>`;
                total += chairTotal;
            }
            document.getElementById('itemsList').innerHTML = itemsHtml;
            document.getElementById('modalTotal').textContent = new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP'
            }).format(total);

            document.getElementById('confirmationModal').classList.add('open');
        }

        function closeConfirmationModal() {
            document.getElementById('confirmationModal').classList.remove('open');
        }

        function submitRental() {
            document.getElementById('rentalForm').submit();
        }

        window.onclick = function(event) {
            const modal = document.getElementById('confirmationModal');
            if (event.target == modal) {
                closeConfirmationModal();
            }
        }
    </script>
    <script src="../assets/js/logout-confirm.js"></script>
</body>
</html>
