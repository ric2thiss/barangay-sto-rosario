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
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .rental-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .section-header {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group input[type="number"],
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-group input:disabled,
        .form-group textarea:disabled {
            background-color: #e8e8e8;
            cursor: not-allowed;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .checkbox-item label {
            margin-bottom: 0;
            font-weight: 500;
            cursor: pointer;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 4px solid;
        }

        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }

        .alert-error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #545b62;
        }

        .btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal.open {
            display: flex !important;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }

        .modal-body {
            margin-bottom: 25px;
        }

        .confirmation-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .confirmation-item:last-child {
            border-bottom: none;
        }

        .confirmation-label {
            font-weight: 600;
            color: #555;
        }

        .confirmation-value {
            color: #333;
            text-align: right;
        }

        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-confirm {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
        }

        .btn-confirm:hover {
            background-color: #218838;
        }

        .btn-cancel {
            background-color: #dc3545;
            color: white;
            padding: 10px 20px;
        }

        .btn-cancel:hover {
            background-color: #c82333;
        }

        .highlight-box {
            background-color: #e7f3ff;
            border: 2px solid #0066cc;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }

        .highlight-amount {
            font-size: 20px;
            font-weight: bold;
            color: #0066cc;
            text-align: center;
        }

        .price-list {
            background-color: #f0f8ff;
            padding: 10px 15px;
            border-radius: 4px;
            font-size: 13px;
            color: #333;
            margin-bottom: 15px;
        }

        .price-list-item {
            padding: 5px 0;
        }

        .conditional-field {
            display: none;
        }

        .conditional-field.show {
            display: block;
        }
    </style>
</head>

<body class="resident-portal">
    <div class="mobile-topbar">
        <button class="mobile-menu-btn" type="button" id="mobileMenuBtn" aria-label="Open menu">
            <i class="fas fa-bars"></i>
        </button>
        <div class="portal-title">Resident Portal</div>
    </div>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/images/logo.jpg" alt="Barangay Logo"
                    style="width: 80px; height: 80px; border-radius: 50%; margin-bottom: 10px; border: 3px solid #ffffff;">
                <h2>BARANGAY STO. ROSARIO</h2>
                <p>Resident Portal</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="pending_payments.php"><i class="fas fa-hourglass-half"></i> Pending Payments</a></li>
                <li><a href="payment_history.php"><i class="fas fa-receipt"></i> Payment History</a></li>
                <li><a href="request_cedula.php"><i class="fas fa-id-card"></i> Request Cedula</a></li>
                <li><a href="donation.php"><i class="fas fa-heart"></i> Make Donation</a></li>
                <li><a href="garbage.php"><i class="fas fa-trash"></i> Garbage Payment</a></li>
                <li><a href="rental.php" class="active"><i class="fas fa-building"></i> Rent Facilities</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="content-header">
                <h1><i class="fas fa-building"></i> Rent Barangay Facilities</h1>
                <p>Welcome, <?= htmlspecialchars($residentName) ?>
                </p>
            </div>

            <div class="content-body">
                <div class="rental-container">

                    <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($hasPendingRental): ?>
                    <div class="alert alert-warning">
                        ⏳ You already have a pending rental request. Please wait for the treasurer to process it before
                        making
                        another rental.
                    </div>
                    <?php endif; ?>

                    <form method="POST" id="rentalForm">
                        <!-- Renter Information Section -->
                        <div class="form-section">
                            <div class="section-header">👤 Renter Information</div>

                            <div class="form-group">
                                <label for="renterName">Full Name *</label>
                                <input type="text" id="renterName" name="renter_name"
                                    value="<?php echo htmlspecialchars($residentName); ?>"
                                    disabled>
                            </div>

                            <div class="form-group">
                                <label for="rentalDate">Rental Date *</label>
                                <input type="date" id="rentalDate" name="rental_date"
                                    value="<?php echo date('Y-m-d'); ?>"
                                    disabled>
                            </div>
                        </div>

                        <!-- Rental Details Section -->
                        <div class="form-section">
                            <div class="section-header">🏢 Rental Details</div>

                            <div class="form-group">
                                <label for="purpose">Purpose of Rental *</label>
                                <input type="text" id="purpose" name="purpose"
                                    placeholder="e.g., Birthday Party, Community Meeting, Wedding Reception"
                                    value="<?php echo htmlspecialchars($_POST['purpose'] ?? ''); ?>"
                                    <?php echo $hasPendingRental ? 'disabled' : ''; ?>
                                required>
                            </div>

                            <!-- Facility Selection -->
                            <div class="form-group">
                                <label>Select Facilities to Rent *</label>
                                <div class="price-list">
                                    <div class="price-list-item">💺 Chair:
                                        ₱<?php echo number_format($CHAIR_PRICE, 2); ?>
                                        each</div>
                                    <div class="price-list-item">🏛️ Covered Court:
                                        ₱<?php echo number_format($COURT_PRICE, 2); ?>
                                        per use</div>
                                </div>

                                <div class="checkbox-group">
                                    <div class="checkbox-item">
                                        <input type="checkbox" id="rentCourt" name="rent_court" value="1"
                                            <?php echo $hasPendingRental ? 'disabled' : ''; ?>>
                                        <label for="rentCourt">Covered Court</label>
                                    </div>

                                    <div class="checkbox-item">
                                        <input type="checkbox" id="rentChairs" name="rent_chairs" value="1"
                                            onchange="toggleChairQuantity()"
                                            <?php echo $hasPendingRental ? 'disabled' : ''; ?>>
                                        <label for="rentChairs">Chairs</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Chair Quantity (shown only when chairs selected) -->
                            <div id="chairQuantityField" class="form-group conditional-field">
                                <label for="chairQuantity">Number of Chairs *</label>
                                <input type="number" id="chairQuantity" name="chair_quantity" min="1"
                                    placeholder="Enter number of chairs"
                                    value="<?php echo htmlspecialchars($_POST['chair_quantity'] ?? ''); ?>"
                                    <?php echo $hasPendingRental ? 'disabled' : ''; ?>
                                onchange="updateTotal()">
                            </div>

                            <!-- Usage Date -->
                            <div class="form-group">
                                <label for="usageDate">Date of Use *</label>
                                <input type="date" id="usageDate" name="usage_date"
                                    value="<?php echo htmlspecialchars($_POST['usage_date'] ?? ''); ?>"
                                    <?php echo $hasPendingRental ? 'disabled' : ''; ?>
                                required>
                            </div>

                            <?php if (!$hasPendingRental): ?>
                            <div class="highlight-box">
                                <div style="font-weight: 600; margin-bottom: 8px; color: #0066cc;">Rental Summary:</div>
                                <div style="font-size: 13px; margin-bottom: 8px;" id="itemsSummary">No items selected
                                </div>
                                <div class="highlight-amount" id="totalAmount">₱0.00</div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Action Buttons -->
                        <?php if (!$hasPendingRental): ?>
                        <div class="form-actions">
                            <button type="button" class="btn btn-primary" onclick="openConfirmationModal()">Submit
                                Rental
                                Request</button>
                            <a href="pending_payments.php" class="btn btn-secondary"
                                style="text-decoration: none; display: inline-block;">Cancel</a>
                        </div>
                        <?php else: ?>
                        <div class="form-actions">
                            <a href="pending_payments.php" class="btn btn-secondary"
                                style="text-decoration: none; display: inline-block;">Back
                                to Portal</a>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Confirmation Modal -->
                <div id="confirmationModal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">✓ Confirm Rental Request</div>

                        <div class="modal-body">
                            <div class="confirmation-item">
                                <span class="confirmation-label">Renter Name:</span>
                                <span
                                    class="confirmation-value"><?php echo htmlspecialchars($residentName); ?></span>
                            </div>
                            <div class="confirmation-item">
                                <span class="confirmation-label">Purpose:</span>
                                <span class="confirmation-value" id="modalPurpose">-</span>
                            </div>
                            <div class="confirmation-item">
                                <span class="confirmation-label">Rental Date:</span>
                                <span class="confirmation-value"
                                    id="modalRentalDate"><?php echo date('M d, Y'); ?></span>
                            </div>
                            <div class="confirmation-item">
                                <span class="confirmation-label">Date of Use:</span>
                                <span class="confirmation-value" id="modalUsageDate">-</span>
                            </div>
                            <div id="itemsList" style="border-bottom: 1px solid #eee; padding: 12px 0;">
                                <!-- Items will be inserted here -->
                            </div>
                            <div class="confirmation-item"
                                style="border-top: 2px solid #0066cc; padding-top: 15px; font-weight: bold; font-size: 16px;">
                                <span class="confirmation-label">Total Amount:</span>
                                <span class="confirmation-value" id="modalTotal"
                                    style="color: #0066cc; font-size: 18px;">₱0.00</span>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-cancel"
                                onclick="closeConfirmationModal()">Cancel</button>
                            <button type="button" class="btn btn-confirm" onclick="submitRental()">Confirm &
                                Submit</button>
                        </div>
                    </div>
                </div>

                <script>
                    const CHAIR_PRICE = <?php echo $CHAIR_PRICE; ?> ;
                    const COURT_PRICE = <?php echo $COURT_PRICE; ?> ;

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
                        let items = [];

                        if (rentCourt) {
                            total += COURT_PRICE;
                            items.push('Covered Court');
                        }

                        if (rentChairs && chairQty > 0) {
                            total += chairQty * CHAIR_PRICE;
                            items.push(`${chairQty} Chair${chairQty > 1 ? 's' : ''}`);
                        }

                        const formatted = new Intl.NumberFormat('en-PH', {
                            style: 'currency',
                            currency: 'PHP',
                            minimumFractionDigits: 2
                        }).format(total);

                        document.getElementById('totalAmount').textContent = formatted;
                        document.getElementById('itemsSummary').textContent = items.length > 0 ? items.join(', ') :
                            'No items selected';
                    }

                    document.getElementById('rentCourt').addEventListener('change', updateTotal);
                    document.getElementById('rentChairs').addEventListener('change', toggleChairQuantity);
                    document.getElementById('chairQuantity').addEventListener('input', updateTotal);

                    function openConfirmationModal() {
                        const purpose = document.getElementById('purpose').value.trim();
                        const rentCourt = document.getElementById('rentCourt').checked;
                        const rentChairs = document.getElementById('rentChairs').checked;
                        const chairQty = parseInt(document.getElementById('chairQuantity').value) || 0;
                        const usageDate = document.getElementById('usageDate').value;

                        if (!purpose) {
                            alert('Please enter the purpose of rental.');
                            return;
                        }

                        if (!rentCourt && !rentChairs) {
                            alert('Please select at least one facility to rent.');
                            return;
                        }

                        if (rentChairs && chairQty <= 0) {
                            alert('Please specify the number of chairs.');
                            return;
                        }

                        if (!usageDate) {
                            alert('Please select the date of use.');
                            return;
                        }

                        // Populate modal
                        document.getElementById('modalPurpose').textContent = purpose;

                        const usageDateObj = new Date(usageDate);
                        const formattedUsageDate = usageDateObj.toLocaleDateString('en-PH', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                        });
                        document.getElementById('modalUsageDate').textContent = formattedUsageDate;

                        // Build items list
                        let itemsList = '';
                        let total = 0;

                        if (rentCourt) {
                            itemsList += `<div class="confirmation-item">
                    <span class="confirmation-label">Covered Court:</span>
                    <span class="confirmation-value">₱${COURT_PRICE.toFixed(2)}</span>
                </div>`;
                            total += COURT_PRICE;
                        }

                        if (rentChairs && chairQty > 0) {
                            const chairTotal = chairQty * CHAIR_PRICE;
                            itemsList += `<div class="confirmation-item">
                    <span class="confirmation-label">Chairs (${chairQty}x @ ₱${CHAIR_PRICE.toFixed(2)}):</span>
                    <span class="confirmation-value">₱${chairTotal.toFixed(2)}</span>
                </div>`;
                            total += chairTotal;
                        }

                        document.getElementById('itemsList').innerHTML = itemsList;

                        const formatted = new Intl.NumberFormat('en-PH', {
                            style: 'currency',
                            currency: 'PHP',
                            minimumFractionDigits: 2
                        }).format(total);
                        document.getElementById('modalTotal').textContent = formatted;

                        document.getElementById('confirmationModal').classList.add('open');
                    }

                    function closeConfirmationModal() {
                        document.getElementById('confirmationModal').classList.remove('open');
                    }

                    function submitRental() {
                        document.getElementById('rentalForm').submit();
                    }

                    // Close modal when clicking outside
                    window.addEventListener('click', function(event) {
                        const modal = document.getElementById('confirmationModal');
                        if (event.target === modal) {
                            closeConfirmationModal();
                        }
                    });

                    // Initialize on page load
                    window.addEventListener('DOMContentLoaded', function() {
                        updateTotal();
                        toggleChairQuantity();
                    });
                </script>

                <script>
                    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
                    const sidebar = document.querySelector('.sidebar');

                    if (mobileMenuBtn && sidebar) {
                        mobileMenuBtn.addEventListener('click', () => {
                            sidebar.classList.toggle('active');
                        });
                    }
                </script>
        </main>
    </div>
</body>

</html>
