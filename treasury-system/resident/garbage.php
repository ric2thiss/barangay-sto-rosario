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

// Check for pending garbage
$hasPendingGarbage = false;
$pendingStmt = $conn->prepare("
    SELECT id FROM payment_status 
    WHERE resident_id = ? AND certificate_type = 'Garbage' AND payment_status = 'pending'
    LIMIT 1
");
$pendingStmt->bind_param("i", $residentId);
$pendingStmt->execute();
$pendingResult = $pendingStmt->get_result();
if ($pendingResult->num_rows > 0) {
    $hasPendingGarbage = true;
}
$pendingStmt->close();

// Handle garbage submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($hasPendingGarbage) {
        $error = 'You already have a pending garbage request. Please wait for the treasurer to process it.';
    } else {
        $purpose = htmlspecialchars($_POST['purpose'] ?? '');
        $recipient = htmlspecialchars($_POST['recipient'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);

        if (empty($purpose) || empty($recipient) || $amount <= 0) {
            $error = 'Please fill in all required fields.';
        } else {
            $conn->begin_transaction();

            // Generate garbage reference
            $garbageRef = 'GAR-' . date('YmdHis') . '-' . $residentId;
            $garbageDate = date('Y-m-d');

            // Insert into garbage table
            $garbageStmt = $conn->prepare("
                INSERT INTO garbage (garbage_ref, resident_id, resident_name, garbage_date, purpose, recipient_activities, amount)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $garbageStmt->bind_param("sissssd", $garbageRef, $residentId, $residentName, $garbageDate, $purpose, $recipient, $amount);
            $garbageOk = $garbageStmt->execute();
            $garbageStmt->close();

            // Insert into payment_status
            $paymentOk = false;
            if ($garbageOk) {
                $certificateType = 'Garbage';
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

            if ($garbageOk && $paymentOk) {
                $conn->commit();
                header('Location: pending_payments.php?submitted=1');
                exit;
            }

            $conn->rollback();
            $error = 'Failed to submit garbage payment. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Garbage Payment - Resident Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        .garbage-container {
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
    </style>
</head>

<body class="resident-portal">
    <div class="dashboard-container">
        <?php include "partials/sidebar.php"; ?>

        <main class="main-content">
            <div class="content-header">
                <h1><i class="fas fa-trash"></i> Garbage Payment</h1>
                <p>Welcome, <?= htmlspecialchars($residentName) ?></p>
            </div>

            <div class="content-body">
                <div class="garbage-container">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($hasPendingGarbage): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-clock"></i> You already have a pending garbage payment request. Please wait for the treasurer to process it before making another payment.
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="garbageForm">
                        <div class="form-section">
                            <div class="section-header"><i class="fas fa-user-circle"></i> Resident Information</div>
                            <div class="form-group">
                                <label for="residentName">Full Name</label>
                                <input type="text" id="residentName" value="<?php echo htmlspecialchars($residentName); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label for="garbageDate">Payment Date</label>
                                <input type="date" id="garbageDate" value="<?php echo date('Y-m-d'); ?>" disabled>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="section-header"><i class="fas fa-receipt"></i> Garbage Details</div>
                            <div class="form-group">
                                <label for="purpose">Purpose of Garbage Payment *</label>
                                <input type="text" id="purpose" name="purpose" 
                                       placeholder="e.g., Monthly Garbage Collection, Special Disposal"
                                       value="<?php echo htmlspecialchars($_POST['purpose'] ?? ''); ?>"
                                       <?php echo $hasPendingGarbage ? 'disabled' : ''; ?> required>
                            </div>
                            <div class="form-group">
                                <label for="recipient">Garbage Collection Details *</label>
                                <textarea id="recipient" name="recipient" 
                                          placeholder="Specify the billing period or garbage collection details"
                                          <?php echo $hasPendingGarbage ? 'disabled' : ''; ?> required><?php echo htmlspecialchars($_POST['recipient'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="amount">Payment Amount (₱) *</label>
                                <input type="number" id="amount" name="amount" step="0.01" min="1"
                                       placeholder="0.00"
                                       value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>"
                                       <?php echo $hasPendingGarbage ? 'disabled' : ''; ?> required>
                            </div>

                            <?php if (!$hasPendingGarbage): ?>
                                <div class="highlight-box">
                                    <div class="highlight-amount" id="amountDisplay">₱0.00</div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-actions">
                            <?php if (!$hasPendingGarbage): ?>
                                <button type="button" class="btn btn-primary" onclick="openConfirmationModal()">
                                    <i class="fas fa-paper-plane"></i> Submit Garbage Payment
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
                <i class="fas fa-check-circle" style="color: #27ae60;"></i> Confirm Garbage Payment
            </div>
            <div class="modal-body">
                <div class="confirmation-item">
                    <span class="confirmation-label">Resident:</span>
                    <span class="confirmation-value"><?php echo htmlspecialchars($residentName); ?></span>
                </div>
                <div class="confirmation-item">
                    <span class="confirmation-label">Date:</span>
                    <span class="confirmation-value"><?php echo date('M d, Y'); ?></span>
                </div>
                <div class="confirmation-item">
                    <span class="confirmation-label">Purpose:</span>
                    <span class="confirmation-value" id="modalPurpose">-</span>
                </div>
                <div class="confirmation-item">
                    <span class="confirmation-label">Details:</span>
                    <span class="confirmation-value" id="modalRecipient">-</span>
                </div>
                <div class="confirmation-item" style="border-top: 2px solid #3498db; margin-top: 15px; padding-top: 15px;">
                    <span class="confirmation-label" style="color: #2c3e50;">Total Amount:</span>
                    <span class="confirmation-value" id="modalAmount" style="color: #2980b9; font-size: 20px; font-weight: 800;">₱0.00</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeConfirmationModal()">Cancel</button>
                <button type="button" class="btn btn-primary" style="background-color: #27ae60;" onclick="submitGarbage()">
                    Confirm & Submit
                </button>
            </div>
        </div>
    </div>

    <script>
        const amountInput = document.getElementById('amount');
        const amountDisplay = document.getElementById('amountDisplay');

        if (amountInput && amountDisplay) {
            amountInput.addEventListener('input', function() {
                const amount = parseFloat(this.value) || 0;
                amountDisplay.textContent = new Intl.NumberFormat('en-PH', {
                    style: 'currency',
                    currency: 'PHP'
                }).format(amount);
            });
        }

        function openConfirmationModal() {
            const purpose = document.getElementById('purpose').value.trim();
            const recipient = document.getElementById('recipient').value.trim();
            const amount = parseFloat(document.getElementById('amount').value) || 0;

            if (!purpose || !recipient || amount <= 0) {
                alert('Please fill in all required fields and enter a valid amount.');
                return;
            }

            document.getElementById('modalPurpose').textContent = purpose;
            document.getElementById('modalRecipient').textContent = recipient;
            document.getElementById('modalAmount').textContent = new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP'
            }).format(amount);

            document.getElementById('confirmationModal').classList.add('open');
        }

        function closeConfirmationModal() {
            document.getElementById('confirmationModal').classList.remove('open');
        }

        function submitGarbage() {
            document.getElementById('garbageForm').submit();
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