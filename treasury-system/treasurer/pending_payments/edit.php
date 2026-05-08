<?php
include "../../config/database.php";
include "../../config/session.php";

$pendingId = intval($_GET['id'] ?? 0);
if ($pendingId <= 0) {
    header("Location: list.php?error=Invalid pending payment ID.");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM payment_status WHERE id = ?");
$stmt->bind_param("i", $pendingId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: list.php?error=Pending payment not found.");
    exit;
}
$pending = $result->fetch_assoc();
$stmt->close();

$error = isset($_GET['error']) ? $_GET['error'] : "";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pending Payment - Barangay Sto. Rosario</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="dashboard-container">
        <?php
        $path_prefix = '../';
        include "../partials/sidebar.php";
        ?>

        <main class="main-content">
            <div class="content-header">
                <h1><i class="fas fa-edit"></i> Edit Pending Payment</h1>
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
                        <h3><i class="fas fa-file-pen"></i> Update Details</h3>
                    </div>

                    <form id="editPendingForm" method="POST" action="save.php">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id"
                            value="<?= $pending['id'] ?>">

                        <div class="form-group">
                            <label for="resident_fname"><i class="fas fa-user"></i> Resident Name *</label>
                            <input type="text" id="resident_fname" name="resident_fname"
                                value="<?= htmlspecialchars($pending['resident_fname']) ?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="resident_id"><i class="fas fa-id-badge"></i> Resident ID</label>
                            <input type="number" id="resident_id" name="resident_id" min="1"
                                value="<?= htmlspecialchars($pending['resident_id'] ?? '') ?>"
                                placeholder="Enter resident ID (optional)">
                        </div>

                        <?php if (!empty($pending['proof_path'])): ?>
                        <div class="form-group">
                            <label><i class="fas fa-receipt"></i> Payment Proof</label>
                            <div>
                                <a class="btn btn-sm btn-secondary"
                                    href="../../<?= htmlspecialchars($pending['proof_path']) ?>"
                                    target="_blank">
                                    <i class="fas fa-eye"></i> View Proof
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="certificate_type"><i class="fas fa-certificate"></i> Certificate Type
                                    *</label>
                                <input type="text" id="certificate_type" name="certificate_type"
                                    value="<?= htmlspecialchars($pending['certificate_type']) ?>"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="payment_status"><i class="fas fa-clipboard-check"></i> Status *</label>
                                <select id="payment_status" name="payment_status" required>
                                    <option value="pending" <?= $pending['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending
                                    </option>
                                    <option value="to_review" <?= $pending['payment_status'] === 'to_review' ? 'selected' : '' ?>>To
                                        Review
                                    </option>
                                    <option value="paid" <?= $pending['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid
                                    </option>
                                    <option value="rejected" <?= $pending['payment_status'] === 'rejected' ? 'selected' : '' ?>>Rejected
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="rejection_remarks"><i class="fas fa-comment-slash"></i> Rejection
                                Remarks</label>
                            <textarea id="rejection_remarks" name="rejection_remarks" rows="3"
                                placeholder="Reason for rejection (required if rejected)"><?= htmlspecialchars($pending['rejection_remarks'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="purpose"><i class="fas fa-info-circle"></i> Purpose *</label>
                            <input type="text" id="purpose" name="purpose"
                                value="<?= htmlspecialchars($pending['purpose']) ?>"
                                required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="amount"><i class="fas fa-peso-sign"></i> Amount *</label>
                                <input type="number" id="amount" name="amount" step="0.01" min="0"
                                    value="<?= number_format($pending['amount'], 2, '.', '') ?>"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="bir_tax"><i class="fas fa-percent"></i> BIR Tax *</label>
                                <input type="number" id="bir_tax" name="bir_tax" step="0.01" min="0"
                                    value="<?= number_format($pending['bir_tax'], 2, '.', '') ?>"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="total"><i class="fas fa-calculator"></i> Total</label>
                                <input type="number" id="total" name="total" step="0.01" readonly
                                    value="<?= number_format($pending['amount'] + $pending['bir_tax'], 2, '.', '') ?>"
                                    style="font-weight: bold; font-size: 16px; background: #e8f0ff;">
                            </div>
                        </div>

                        <div style="display: flex; gap: 10px; margin-top: 25px;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <a href="list.php" class="btn btn-secondary"
                                style="flex: 1; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Mark Paid Confirmation Modal -->
    <div id="paidModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-check-circle" style="color: #28a745; font-size: 48px;"></i>
                <h2>Confirm Mark as Paid</h2>
            </div>
            <div class="modal-body">
                <p><strong>Are you sure?</strong> This will move the record to Payments.</p>
                <p id="paidDetails"></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelPaidBtn" type="button">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn btn-success" id="confirmPaidBtn" type="button">
                    <i class="fas fa-check"></i> Yes, Mark Paid
                </button>
            </div>
        </div>
    </div>

    <style>
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s ease;
        }

        .modal-header {
            padding: 30px;
            text-align: center;
            border-bottom: 2px solid #f0f0f0;
        }

        .modal-header h2 {
            margin: 15px 0 0 0;
            color: #333;
            font-size: 24px;
        }

        .modal-body {
            padding: 30px;
        }

        .modal-body p {
            margin: 10px 0;
            line-height: 1.6;
        }

        .modal-footer {
            padding: 20px 30px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            border-top: 2px solid #f0f0f0;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>

    <script>
        const amountInput = document.getElementById('amount');
        const birTaxInput = document.getElementById('bir_tax');
        const totalInput = document.getElementById('total');
        const statusSelect = document.getElementById('payment_status');
        const form = document.getElementById('editPendingForm');
        const paidModal = document.getElementById('paidModal');
        const confirmPaidBtn = document.getElementById('confirmPaidBtn');
        const cancelPaidBtn = document.getElementById('cancelPaidBtn');

        function updateTotal() {
            const amount = parseFloat(amountInput.value) || 0;
            const birTax = parseFloat(birTaxInput.value) || 0;
            totalInput.value = (amount + birTax).toFixed(2);
        }

        amountInput.addEventListener('input', updateTotal);
        birTaxInput.addEventListener('input', updateTotal);

        form.addEventListener('submit', function(event) {
            if (statusSelect.value === 'paid') {
                event.preventDefault();
                const resident = document.getElementById('resident_fname').value.trim();
                const certificate = document.getElementById('certificate_type').value.trim();
                const total = totalInput.value;
                document.getElementById('paidDetails').innerHTML =
                    `<strong>Resident:</strong> ${resident}<br>` +
                    `<strong>Certificate:</strong> ${certificate}<br>` +
                    `<strong>Total:</strong> ₱${total}`;
                paidModal.style.display = 'flex';
            }
        });

        confirmPaidBtn.addEventListener('click', function() {
            paidModal.style.display = 'none';
            form.submit();
        });

        cancelPaidBtn.addEventListener('click', function() {
            paidModal.style.display = 'none';
        });

        window.addEventListener('click', function(event) {
            if (event.target === paidModal) {
                paidModal.style.display = 'none';
            }
        });

        (function() {
            const forms = Array.from(document.querySelectorAll('form'));
            if (!forms.length) {
                return;
            }

            function serializeForm(form) {
                const data = new FormData(form);
                const params = new URLSearchParams();
                for (const [key, value] of data.entries()) {
                    params.append(key, value);
                }
                return params.toString();
            }

            const formSnapshots = new Map();
            forms.forEach((form) => {
                formSnapshots.set(form, serializeForm(form));
                form.addEventListener('submit', () => {
                    form.dataset.submitting = 'true';
                });
            });

            window.addEventListener('beforeunload', function(event) {
                const hasUnsaved = forms.some((form) => {
                    if (form.dataset.submitting === 'true') {
                        return false;
                    }
                    return serializeForm(form) !== formSnapshots.get(form);
                });

                if (!hasUnsaved) {
                    return;
                }

                event.preventDefault();
                event.returnValue = '';
            });
        })();
    </script>
    <script src="../../assets/js/logout-confirm.js"></script>
</body>

</html>





