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

// Check for pending donation
$hasPendingDonation = false;
$pendingStmt = $conn->prepare("
    SELECT id FROM payment_status 
    WHERE resident_id = ? AND certificate_type = 'Donation' AND payment_status = 'pending'
    LIMIT 1
");
$pendingStmt->bind_param("i", $residentId);
$pendingStmt->execute();
$pendingResult = $pendingStmt->get_result();
if ($pendingResult->num_rows > 0) {
    $hasPendingDonation = true;
}
$pendingStmt->close();

// Handle donation submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($hasPendingDonation) {
        $error = 'You already have a pending donation request. Please wait for the treasurer to process it.';
    } else {
        $purpose = htmlspecialchars($_POST['purpose'] ?? '');
        $recipient = htmlspecialchars($_POST['recipient'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);

        if (empty($purpose) || empty($recipient) || $amount <= 0) {
            $error = 'Please fill in all required fields.';
        } else {
            $conn->begin_transaction();

            // Generate donation reference
            $donationRef = 'DON-' . date('YmdHis') . '-' . $residentId;
            $donationDate = date('Y-m-d');

            // Insert into donation table
            $donationStmt = $conn->prepare("
                INSERT INTO donation (donation_ref, resident_id, resident_name, donation_date, purpose, recipient_activities, amount)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $donationStmt->bind_param("sissssd", $donationRef, $residentId, $residentName, $donationDate, $purpose, $recipient, $amount);
            $donationOk = $donationStmt->execute();
            $donationStmt->close();

            // Insert into payment_status
            $paymentOk = false;
            if ($donationOk) {
                $certificateType = 'Donation';
                $paymentPurpose = $purpose;
                $birTax = 0.00;

                $payStmt = $conn->prepare("
                    INSERT INTO payment_status (resident_id, certificate_type, purpose, resident_fname, payment_status, amount, bir_tax, created_at)
                    VALUES (?, ?, ?, ?, 'pending', ?, ?, NOW())
                ");
                $payStmt->bind_param("isssdd", $residentId, $certificateType, $paymentPurpose, $residentName, $amount, $birTax);
                $paymentOk = $payStmt->execute();
                $payStmt->close();
            }

            if ($donationOk && $paymentOk) {
                $conn->commit();
                header('Location: pending_payments.php?submitted=1');
                exit;
            }

            $conn->rollback();
            $error = 'Failed to submit donation. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation - Resident Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .donation-container {
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
                <li><a href="donation.php" class="active"><i class="fas fa-heart"></i> Make Donation</a></li>
                <li><a href="garbage.php"><i class="fas fa-trash"></i> Garbage Payment</a></li>
                <li><a href="rental.php"><i class="fas fa-building"></i> Rent Facilities</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="content-header">
                <h1><i class="fas fa-heart"></i> Make a Donation</h1>
                <p>Welcome, <?= htmlspecialchars($residentName) ?>
                </p>
            </div>

            <div class="content-body">
                <div class="donation-container">

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

                    <?php if ($hasPendingDonation): ?>
                    <div class="alert alert-warning">
                        ⏳ You already have a pending donation request. Please wait for the treasurer to process it
                        before making
                        another donation.
                    </div>
                    <?php endif; ?>

                    <form method="POST" id="donationForm">
                        <!-- Donor Information Section -->
                        <div class="form-section">
                            <div class="section-header">👤 Donor Information</div>

                            <div class="form-group">
                                <label for="donorName">Full Name *</label>
                                <input type="text" id="donorName" name="donor_name"
                                    value="<?php echo htmlspecialchars($residentName); ?>"
                                    disabled>
                            </div>

                            <div class="form-group">
                                <label for="donationDate">Donation Date *</label>
                                <input type="date" id="donationDate" name="donation_date"
                                    value="<?php echo date('Y-m-d'); ?>"
                                    disabled>
                            </div>
                        </div>

                        <!-- Donation Details Section -->
                        <div class="form-section">
                            <div class="section-header">💰 Donation Details</div>

                            <div class="form-group">
                                <label for="purpose">Purpose of Donation *</label>
                                <input type="text" id="purpose" name="purpose"
                                    placeholder="e.g., Barangay Event, School Supplies, Emergency Relief"
                                    value="<?php echo htmlspecialchars($_POST['purpose'] ?? ''); ?>"
                                    <?php echo $hasPendingDonation ? 'disabled' : ''; ?>
                                required>
                            </div>

                            <div class="form-group">
                                <label for="recipient">To Whom / What Activities *</label>
                                <textarea id="recipient" name="recipient"
                                    placeholder="Specify the recipient or activities benefiting from this donation"
                                    <?php echo $hasPendingDonation ? 'disabled' : ''; ?> required><?php echo htmlspecialchars($_POST['recipient'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="amount">Donation Amount (₱) *</label>
                                <input type="number" id="amount" name="amount" step="0.01" min="0"
                                    placeholder="Enter amount"
                                    value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>"
                                    <?php echo $hasPendingDonation ? 'disabled' : ''; ?>
                                required>
                            </div>

                            <?php if (!$hasPendingDonation): ?>
                            <div class="highlight-box">
                                <div class="highlight-amount" id="amountDisplay">₱0.00</div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Action Buttons -->
                        <?php if (!$hasPendingDonation): ?>
                        <div class="form-actions">
                            <button type="button" class="btn btn-primary" onclick="openConfirmationModal()">Submit
                                Donation</button>
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
                        <div class="modal-header">✓ Confirm Donation Submission</div>

                        <div class="modal-body">
                            <div class="confirmation-item">
                                <span class="confirmation-label">Donor Name:</span>
                                <span
                                    class="confirmation-value"><?php echo htmlspecialchars($residentName); ?></span>
                            </div>
                            <div class="confirmation-item">
                                <span class="confirmation-label">Donation Date:</span>
                                <span class="confirmation-value"
                                    id="modalDate"><?php echo date('M d, Y'); ?></span>
                            </div>
                            <div class="confirmation-item">
                                <span class="confirmation-label">Purpose:</span>
                                <span class="confirmation-value" id="modalPurpose">-</span>
                            </div>
                            <div class="confirmation-item">
                                <span class="confirmation-label">Recipient/Activities:</span>
                                <span class="confirmation-value" id="modalRecipient">-</span>
                            </div>
                            <div class="confirmation-item"
                                style="border-top: 2px solid #0066cc; padding-top: 15px; font-weight: bold; font-size: 16px;">
                                <span class="confirmation-label">Donation Amount:</span>
                                <span class="confirmation-value" id="modalAmount"
                                    style="color: #0066cc; font-size: 18px;">₱0.00</span>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-cancel"
                                onclick="closeConfirmationModal()">Cancel</button>
                            <button type="button" class="btn btn-confirm" onclick="submitDonation()">Confirm &
                                Submit</button>
                        </div>
                    </div>
                </div>

                <script>
                    // Update amount display in real-time
                    document.getElementById('amount').addEventListener('input', function() {
                        const amount = parseFloat(this.value) || 0;
                        const formatted = new Intl.NumberFormat('en-PH', {
                            style: 'currency',
                            currency: 'PHP',
                            minimumFractionDigits: 2
                        }).format(amount);
                        document.getElementById('amountDisplay').textContent = formatted;
                    });

                    function openConfirmationModal() {
                        const purpose = document.getElementById('purpose').value.trim();
                        const recipient = document.getElementById('recipient').value.trim();
                        const amount = parseFloat(document.getElementById('amount').value) || 0;

                        if (!purpose || !recipient || amount <= 0) {
                            alert('Please fill in all required fields.');
                            return;
                        }

                        document.getElementById('modalPurpose').textContent = purpose;
                        document.getElementById('modalRecipient').textContent = recipient;

                        const formatted = new Intl.NumberFormat('en-PH', {
                            style: 'currency',
                            currency: 'PHP',
                            minimumFractionDigits: 2
                        }).format(amount);
                        document.getElementById('modalAmount').textContent = formatted;

                        document.getElementById('confirmationModal').classList.add('open');
                    }

                    function closeConfirmationModal() {
                        document.getElementById('confirmationModal').classList.remove('open');
                    }

                    function submitDonation() {
                        document.getElementById('donationForm').submit();
                    }

                    // Close modal when clicking outside
                    window.addEventListener('click', function(event) {
                        const modal = document.getElementById('confirmationModal');
                        if (event.target === modal) {
                            closeConfirmationModal();
                        }
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
