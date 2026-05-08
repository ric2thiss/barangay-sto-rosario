<?php
include "../../config/database.php";
include "../../config/session.php";

$paymentId = intval($_GET['id'] ?? 0);
if ($paymentId <= 0) {
    header("Location: list.php?error=Invalid payment ID.");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM payments WHERE id = ?");
$stmt->bind_param("i", $paymentId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: list.php?error=Payment not found.");
    exit;
}
$payment = $result->fetch_assoc();
$stmt->close();

$error = $_GET['error'] ?? '';
$paymentDate = !empty($payment['payment_date'])
    ? date('Y-m-d', strtotime($payment['payment_date']))
    : date('Y-m-d');
$amountValue = number_format((float) $payment['amount'], 2, '.', '');
$birValue = number_format((float) $payment['bir_tax'], 2, '.', '');
$totalValue = number_format(((float) $payment['amount']) + ((float) $payment['bir_tax']), 2, '.', '');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Payment - Barangay Sto. Rosario</title>
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
                <h1><i class="fas fa-money-bill-wave"></i> Edit Payment</h1>
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
                        <h3><i class="fas fa-pen-to-square"></i> Certificate Payment Information</h3>
                    </div>

                    <form method="POST" action="save.php">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id"
                            value="<?= $paymentId ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="receipt_no"><i class="fas fa-receipt"></i> Receipt Number *</label>
                                <input type="text" id="receipt_no" name="receipt_no"
                                    value="<?= htmlspecialchars($payment['receipt_no']) ?>"
                                    required>
                                <small style="color: #666; font-size: 12px;">You can edit this number if needed</small>
                            </div>

                            <div class="form-group">
                                <label for="payment_date"><i class="fas fa-calendar"></i> Payment Date *</label>
                                <input type="date" id="payment_date" name="payment_date"
                                    value="<?= htmlspecialchars($paymentDate) ?>"
                                    required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="payer_name"><i class="fas fa-user"></i> Payer Name *</label>
                            <input type="text" id="payer_name" name="payer_name"
                                value="<?= htmlspecialchars($payment['payer_name']) ?>"
                                required autocomplete="off">
                            <div id="suggestions"
                                style="position: absolute; background: white; border: 1px solid #ddd; max-height: 200px; overflow-y: auto; width: calc(100% - 40px); z-index: 1000; display: none; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="service_type"><i class="fas fa-tag"></i> Service Type *</label>
                                <select id="service_type" name="service_type" required>
                                    <option value="">Select Service</option>
                                    <option value="Barangay Clearance" <?= $payment['service_type'] === 'Barangay Clearance' ? 'selected' : '' ?>>Barangay
                                        Clearance</option>
                                    <option value="Certificate of Indigency" <?= $payment['service_type'] === 'Certificate of Indigency' ? 'selected' : '' ?>>Certificate
                                        of Indigency</option>
                                    <option value="Certificate of Residency" <?= $payment['service_type'] === 'Certificate of Residency' ? 'selected' : '' ?>>Certificate
                                        of Residency</option>
                                    <option value="Business Permit" <?= $payment['service_type'] === 'Business Permit' ? 'selected' : '' ?>>Business
                                        Permit</option>
                                    <option value="Donation" <?= $payment['service_type'] === 'Donation' ? 'selected' : '' ?>>Donation
                                    </option>
                                    <option value="Garbage" <?= $payment['service_type'] === 'Garbage' ? 'selected' : '' ?>>Garbage
                                    </option>
                                    <option value="Rental" <?= ($payment['service_type'] === 'Rental' || $payment['service_type'] === 'Community Tax Certificate') ? 'selected' : '' ?>>Rental
                                    </option>
                                    <option value="Cedula" <?= $payment['service_type'] === 'Cedula' ? 'selected' : '' ?>>Cedula
                                    </option>
                                    <option value="Other" <?= $payment['service_type'] === 'Other' ? 'selected' : '' ?>>Other
                                    </option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="purpose"><i class="fas fa-info-circle"></i> Purpose *</label>
                                <input type="text" id="purpose" name="purpose"
                                    value="<?= htmlspecialchars($payment['purpose']) ?>"
                                    required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="amount"><i class="fas fa-peso-sign"></i> Amount *</label>
                                <input type="number" id="amount" name="amount" step="0.01" min="0"
                                    value="<?= htmlspecialchars($amountValue) ?>"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="bir_tax"><i class="fas fa-percent"></i> BIR Tax/Fee *</label>
                                <input type="number" id="bir_tax" name="bir_tax" step="0.01" min="0"
                                    value="<?= htmlspecialchars($birValue) ?>"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="total"><i class="fas fa-calculator"></i> Total Amount</label>
                                <input type="number" id="total" name="total" step="0.01"
                                    value="<?= htmlspecialchars($totalValue) ?>"
                                    readonly style="font-weight: bold; font-size: 18px; background: #e8f0ff;">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="remarks"><i class="fas fa-comment"></i> Remarks</label>
                            <textarea id="remarks" name="remarks" rows="3"
                                placeholder="Enter any additional notes or remarks..."><?= htmlspecialchars($payment['remarks'] ?? '') ?></textarea>
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

    <script>
        function calculateTotal() {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const birTax = parseFloat(document.getElementById('bir_tax').value) || 0;
            const total = amount + birTax;
            document.getElementById('total').value = total.toFixed(2);
        }

        document.getElementById('amount').addEventListener('input', calculateTotal);
        document.getElementById('bir_tax').addEventListener('input', calculateTotal);

        const payerInput = document.getElementById('payer_name');
        const suggestionsDiv = document.getElementById('suggestions');
        let debounceTimer;

        payerInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const searchTerm = this.value.trim();

            if (searchTerm.length < 2) {
                suggestionsDiv.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`get_people.php?search=${encodeURIComponent(searchTerm)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            suggestionsDiv.innerHTML = data.map(person =>
                                `<div class="suggestion-item" style="padding: 10px; cursor: pointer; border-bottom: 1px solid #eee;" data-name="${person.name}">
                                  <i class="fas fa-user"></i> ${person.name}
                                  <small style="color: #666; margin-left: 10px;">(${person.source})</small>
                                </div>`
                            ).join('');
                            suggestionsDiv.style.display = 'block';

                            document.querySelectorAll('.suggestion-item').forEach(item => {
                                item.addEventListener('click', function() {
                                    payerInput.value = this.dataset.name;
                                    suggestionsDiv.style.display = 'none';
                                });

                                item.addEventListener('mouseenter', function() {
                                    this.style.background = '#f0f4f8';
                                });

                                item.addEventListener('mouseleave', function() {
                                    this.style.background = 'white';
                                });
                            });
                        } else {
                            suggestionsDiv.style.display = 'none';
                        }
                    });
            }, 300);
        });

        document.addEventListener('click', function(e) {
            if (e.target !== payerInput && e.target !== suggestionsDiv) {
                suggestionsDiv.style.display = 'none';
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





