<?php
include "../../config/database.php";
include "../../config/session.php";

$iraAllocation = $conn->query("
    SELECT COALESCE(SUM(amount + bir_tax), 0) AS total
    FROM payments
")->fetch_assoc()['total'] ?? 0;
$iraDisbursements = $conn->query("
    SELECT COALESCE(SUM(COALESCE(bir_gross, amount, 0)), 0) AS total
    FROM disbursements
")->fetch_assoc()['total'] ?? 0;
$iraRemaining = $iraAllocation - $iraDisbursements;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Disbursement - Barangay Sto. Rosario</title>
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
                <h1><i class="fas fa-hand-holding-usd"></i> Record New Disbursement</h1>
            </div>

            <div class="content-body">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-file-invoice-dollar"></i> Disbursement Information</h3>
                        <p style="color: #666; font-size: 14px; margin-top: 5px;">Complete all required disbursement details</p>
                    </div>

                    <form method="POST" action="save.php">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="disburse_date"><i class="fas fa-calendar"></i> Date *</label>
                                <input type="date" id="disburse_date" name="date" value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="check_no"><i class="fas fa-money-check"></i> CH CH # (Check Number) *</label>
                                <input type="text" id="check_no" name="check_no" placeholder="e.g., 724747" required>
                            </div>

                            <div class="form-group">
                                <label for="dv_no"><i class="fas fa-file-alt"></i> DV No. *</label>
                                <input type="text" id="dv_no" name="dv_no" placeholder="e.g., 001" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="payee"><i class="fas fa-user"></i> Payee Name *</label>
                            <input type="text" id="payee" name="payee" placeholder="Enter payee name" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="payee_address"><i class="fas fa-map-marker-alt"></i> Address</label>
                                <textarea id="payee_address" name="payee_address" rows="2"
                                    placeholder="Payee address"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="payee_tin"><i class="fas fa-id-card"></i> TIN No.</label>
                                <input type="text" id="payee_tin" name="payee_tin" placeholder="e.g., 000-000-000-000">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="amount"><i class="fas fa-money-bill"></i> Amount *</label>
                                <input type="number" id="amount" name="amount" step="0.01" min="0" placeholder="0.00" required>
                            </div>

                            <div class="form-group">
                                <label for="fund"><i class="fas fa-piggy-bank"></i> Fund *</label>
                                <input type="text" id="fund" name="fund" value="IRA - Internal Revenue Allotment" readonly required
                                    style="background:#e8f0ff; font-weight:600;">
                                <small style="color:#666;">All collected money is deposited into the IRA account. Disbursements deduct from this fund.</small>
                            </div>
                        </div>

                        <div class="card"
                            style="border:1px solid #dbe4ff; padding:18px; border-radius:8px; margin-bottom:20px; background:#f7f9ff;">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
                                <div>
                                    <h4 style="margin:0 0 6px 0; color:#1e3a5f;">
                                        <i class="fas fa-landmark"></i> IRA Account - Internal Revenue Allotment
                                    </h4>
                                    <small style="color:#666;">All collections are deposited here. This disbursement will be deducted from IRA.</small>
                                </div>
                                <div style="text-align:right;">
                                    <div style="font-size:13px; color:#666;">Current IRA Balance</div>
                                    <strong style="font-size:20px; color:#1F3A93;">PHP <?= number_format($iraRemaining, 2) ?></strong>
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:12px; margin-top:14px;">
                                <div>
                                    <div style="font-size:13px; color:#666;">Total Money Collected</div>
                                    <strong>PHP <?= number_format($iraAllocation, 2) ?></strong>
                                </div>
                                <div>
                                    <div style="font-size:13px; color:#666;">Already Disbursed</div>
                                    <strong>PHP <?= number_format($iraDisbursements, 2) ?></strong>
                                </div>
                                <div>
                                    <div style="font-size:13px; color:#666;">This Deduction</div>
                                    <strong id="iraDeductionPreview">PHP 0.00</strong>
                                </div>
                                <div>
                                    <div style="font-size:13px; color:#666;">Balance After Save</div>
                                    <strong id="iraAfterPreview">PHP <?= number_format($iraRemaining, 2) ?></strong>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="payroll"><i class="fas fa-users"></i> Payroll (Optional)</label>
                                <input type="number" id="payroll" name="payroll" min="0" step="1" placeholder="0">
                                <small style="color:#666;">Numeric amount added to BIR computation</small>
                            </div>
                        </div>

                        <!-- BIR Percentage Computation (same as BIR add form) -->
                        <div class="card" style="border:1px solid #e0e0e0; padding:20px; border-radius:8px; margin-bottom:20px; background:#fafbff;">
                            <div class="card-header" style="margin-bottom:15px;">
                                <h4 style="margin:0; color:#1e3a5f;"><i class="fas fa-percent"></i> BIR Percentage Computation</h4>
                                <small style="color:#666;">Auto-computes withholding tax; pre-filled from disbursement amount</small>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="bir_vat_type"><i class="fas fa-tags"></i> VAT Type</label>
                                    <select id="bir_vat_type" name="bir_vat_type" onchange="computeBIR()" style="font-weight:bold; font-size:15px;">
                                        <option value="Non-VAT Supplies">Non-VAT Supplies (gross × 1% and 3%)</option>
                                        <option value="Non-VAT Services">Non-VAT Services (gross × 2% and 3%)</option>
                                        <option value="Reg. VAT">Reg. VAT (gross ÷ 1.12 × 6%)</option>
                                    </select>
                                    <small style="color:#666; display:block; margin-top:4px;">Non-VAT Supplies: gross × 1% and 3% &nbsp;|&nbsp; Non-VAT Services: gross × 2% and 3% &nbsp;|&nbsp; Reg. VAT: (gross ÷ 1.12) × 6% &rarr; separated into 5% VAT + 1% EWT</small>
                                </div>

                                <div class="form-group">
                                    <label for="bir_gross"><i class="fas fa-money-bill-wave"></i> BIR Gross Amount</label>
                                    <input type="number" id="bir_gross" name="bir_gross" step="0.01" min="0" placeholder="0.00" oninput="computeBIR()" style="background:#e8f0ff;">
                                    <small style="color:#666;">Pre-filled from disbursement amount; edit if different</small>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label id="lbl_bir_1pct"><i class="fas fa-percent"></i> 1% Expanded Withholding Tax</label>
                                    <input type="number" id="bir_withholding_a" name="bir_withholding_a" step="0.01" readonly style="background:#f0f4f8;">
                                    <small id="hint_bir_1pct" style="color:#666; display:block; margin-top:4px;">Auto-calculated (1% of base)</small>
                                </div>

                                <div class="form-group">
                                    <label id="lbl_bir_5pct"><i class="fas fa-percent"></i> 5% Withholding Tax</label>
                                    <input type="number" id="bir_withholding_b" name="bir_withholding_b" step="0.01" readonly style="background:#f0f4f8;">
                                    <small id="hint_bir_5pct" style="color:#666; display:block; margin-top:4px;">Auto-calculated (5% of gross)</small>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fas fa-receipt"></i> Total Withholding Tax</label>
                                    <input type="number" id="bir_total_display" step="0.01" readonly
                                        style="background:#1F3A93; color:#fff; font-weight:bold; font-size:16px;">
                                    <small style="color:#666; display:block; margin-top:4px;">Auto-calculated total withheld</small>
                                    <!-- hidden field submitted with the form -->
                                    <input type="hidden" id="bir" name="bir">
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-hand-holding-usd"></i> Net Amount to Payee</label>
                                    <input type="number" id="bir_net_amount" step="0.01" readonly
                                        style="background:#28a745; color:#fff; font-weight:bold; font-size:18px;">
                                    <small style="color:#666; display:block; margin-top:4px;">Amount released after withholding</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="purpose"><i class="fas fa-info-circle"></i> Particular/Purpose *</label>
                            <textarea id="purpose" name="purpose" rows="3" placeholder="e.g., Cable service, Office supplies, Salary" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="release_amount"><i class="fas fa-hand-holding-usd"></i> Release Amount *</label>
                            <input type="number" id="release_amount" name="release" step="0.01" min="0" placeholder="0.00" readonly required style="background:#f0f4f8;">
                            <small style="color: #666;">Auto-calculated from BIR net amount</small>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="or_no"><i class="fas fa-receipt"></i> OR No.</label>
                                <input type="text" id="or_no" name="or_no" placeholder="e.g., OR-2026-001">
                            </div>
                            <div class="form-group">
                                <label for="received_date"><i class="fas fa-calendar"></i> Date Received</label>
                                <input type="date" id="received_date" name="received_date" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <div class="card" style="border:1px solid #e0e0e0; padding:20px; border-radius:8px; margin-bottom:20px; background:#fafbff;">
                            <div class="card-header" style="margin-bottom:15px; display:flex; align-items:center; justify-content:space-between;">
                                <div>
                                    <h4 style="margin:0; color:#1e3a5f;"><i class="fas fa-list"></i> Accounting Entries</h4>
                                    <small style="color:#666;">Fill in each row and click + to add another entry</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="addAccountingRow()">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <table style="width:100%; border-collapse: collapse;">
                                    <thead>
                                        <tr>
                                            <th style="border:1px solid #e0e0e0; padding:8px; text-align:left;">Account Name</th>
                                            <th style="border:1px solid #e0e0e0; padding:8px; text-align:left; width:140px;">Account Code</th>
                                            <th style="border:1px solid #e0e0e0; padding:8px; text-align:left; width:120px;">Debit</th>
                                            <th style="border:1px solid #e0e0e0; padding:8px; text-align:left; width:120px;">Credit</th>
                                            <th style="border:1px solid #e0e0e0; padding:8px; width:60px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="accounting-rows">
                                        <tr>
                                            <td style="border:1px solid #e0e0e0; padding:6px;">
                                                <input type="text" name="account_name[]" placeholder="Account name" style="width:100%;">
                                            </td>
                                            <td style="border:1px solid #e0e0e0; padding:6px;">
                                                <input type="text" name="account_code[]" placeholder="Code" style="width:100%;">
                                            </td>
                                            <td style="border:1px solid #e0e0e0; padding:6px;">
                                                <input type="number" step="0.01" name="account_debit[]" placeholder="0.00" style="width:100%;">
                                            </td>
                                            <td style="border:1px solid #e0e0e0; padding:6px;">
                                                <input type="number" step="0.01" name="account_credit[]" placeholder="0.00" style="width:100%;">
                                            </td>
                                            <td style="border:1px solid #e0e0e0; padding:6px; text-align:center;">
                                                <button type="button" class="btn btn-sm btn-danger" onclick="removeAccountingRow(this)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="card" style="border:1px solid #e0e0e0; padding:20px; border-radius:8px; margin-bottom:20px; background:#fafbff;">
                            <div class="card-header" style="margin-bottom:15px;">
                                <h4 style="margin:0; color:#1e3a5f;"><i class="fas fa-pen-fancy"></i> Signatories</h4>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="signatory_a">A. Certified (Appropriation)</label>
                                    <input type="text" id="signatory_a" name="signatory_a" placeholder="Full name">
                                </div>
                                <div class="form-group">
                                    <label for="signatory_b">B. Certified (Treasurer)</label>
                                    <input type="text" id="signatory_b" name="signatory_b" placeholder="Full name">
                                </div>
                                <div class="form-group">
                                    <label for="signatory_c">C. Approved (Punong Barangay)</label>
                                    <input type="text" id="signatory_c" name="signatory_c" placeholder="Full name">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="signatory_prepared_by">Prepared by</label>
                                    <input type="text" id="signatory_prepared_by" name="signatory_prepared_by" placeholder="Full name">
                                </div>
                                <div class="form-group">
                                    <label for="signatory_checked_by">Checked by</label>
                                    <input type="text" id="signatory_checked_by" name="signatory_checked_by" placeholder="Full name">
                                </div>
                                <div class="form-group">
                                    <label for="signatory_approved_by">Approved by</label>
                                    <input type="text" id="signatory_approved_by" name="signatory_approved_by" placeholder="Full name">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="signatory_received_by">Received by</label>
                                    <input type="text" id="signatory_received_by" name="signatory_received_by" placeholder="Full name">
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 10px; margin-top: 25px;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;">
                                <i class="fas fa-save"></i> Save Disbursement
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
    <script src="../../assets/js/logout-confirm.js"></script>
</body>
</html>

<script>
    const iraRemainingBalance = <?= json_encode((float) $iraRemaining) ?>;

    function formatPhp(amount) {
        return 'PHP ' + amount.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function updateIraPreview() {
        const amount = parseFloat(document.getElementById('amount').value) || 0;
        const payroll = parseFloat(document.getElementById('payroll').value) || 0;
        const deduction = amount + payroll;
        const after = iraRemainingBalance - deduction;
        document.getElementById('iraDeductionPreview').textContent = formatPhp(deduction);
        document.getElementById('iraAfterPreview').textContent = formatPhp(after);
        document.getElementById('iraAfterPreview').style.color = after < 0 ? '#e53e3e' : '#1F3A93';
    }

    function computeBIR() {
        const gross = parseFloat(document.getElementById('bir_gross').value) || 0;
        const vatType = document.getElementById('bir_vat_type').value;
        let oneP = 0, fiveP = 0;

        if (vatType === 'Reg. VAT') {
            // Reg. VAT: total withholding = (gross ÷ 1.12) × 6%
            // That 6% is composed of 5% VAT withholding + 1% EWT — separate them:
            const base = gross / 1.12;
            oneP  = base * 0.01;   // 1% EWT  (separated from the 6%)
            fiveP = base * 0.05;   // 5% VAT  (the remaining 5% of the 6%)
            document.getElementById('lbl_bir_1pct').innerHTML  = '<i class="fas fa-percent"></i> 1% Expanded Withholding Tax <small style="color:#e74c3c">(separated from 6%)</small>';
            document.getElementById('hint_bir_1pct').textContent = 'Auto-calculated: (gross ÷ 1.12) × 1%  [1% portion of the 6%]';
            document.getElementById('lbl_bir_5pct').innerHTML  = '<i class="fas fa-percent"></i> 5% VAT Withholding <small style="color:#e74c3c">(separated from 6%)</small>';
            document.getElementById('hint_bir_5pct').textContent = 'Auto-calculated: (gross ÷ 1.12) × 5%  [5% portion of the 6%]';
        } else if (vatType === 'Non-VAT Supplies') {
            // Non-VAT Supplies: gross × 1% and 3%
            oneP  = gross * 0.01;
            fiveP = gross * 0.03;
            document.getElementById('lbl_bir_1pct').innerHTML  = '<i class="fas fa-percent"></i> 1% Withholding Tax';
            document.getElementById('hint_bir_1pct').textContent = 'Auto-calculated: gross × 1%';
            document.getElementById('lbl_bir_5pct').innerHTML  = '<i class="fas fa-percent"></i> 3% Withholding Tax';
            document.getElementById('hint_bir_5pct').textContent = 'Auto-calculated: gross × 3%';
        } else {
            // Non-VAT Services: gross × 2% and 3%
            oneP  = gross * 0.02;
            fiveP = gross * 0.03;
            document.getElementById('lbl_bir_1pct').innerHTML  = '<i class="fas fa-percent"></i> 2% Withholding Tax';
            document.getElementById('hint_bir_1pct').textContent = 'Auto-calculated: gross × 2%';
            document.getElementById('lbl_bir_5pct').innerHTML  = '<i class="fas fa-percent"></i> 3% Withholding Tax';
            document.getElementById('hint_bir_5pct').textContent = 'Auto-calculated: gross × 3%';
        }

        const total = oneP + fiveP;   // = base×0.06 for Reg.VAT, gross×0.04 or gross×0.05 for Non-VAT
        const net   = gross - total;
        document.getElementById('bir_withholding_a').value       = oneP.toFixed(2);
        document.getElementById('bir_withholding_b').value      = fiveP.toFixed(2);
        document.getElementById('bir_total_display').value = total.toFixed(2);
        document.getElementById('bir').value               = total.toFixed(2);
        document.getElementById('bir_net_amount').value    = net.toFixed(2);
        document.getElementById('release_amount').value    = net.toFixed(2);
    }

    function updateBirGrossFromInputs() {
        const amount = parseFloat(document.getElementById('amount').value) || 0;
        const payroll = parseFloat(document.getElementById('payroll').value) || 0;
        const totalGross = amount + payroll;
        document.getElementById('bir_gross').value = totalGross.toFixed(2);
        updateIraPreview();
        computeBIR();
    }

    // Pre-fill BIR gross from disbursement amount + payroll on input
    document.getElementById('amount').addEventListener('input', updateBirGrossFromInputs);
    document.getElementById('payroll').addEventListener('input', updateBirGrossFromInputs);
    updateIraPreview();

    function addAccountingRow() {
        const tbody = document.getElementById('accounting-rows');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td style="border:1px solid #e0e0e0; padding:6px;">
                <input type="text" name="account_name[]" placeholder="Account name" style="width:100%;">
            </td>
            <td style="border:1px solid #e0e0e0; padding:6px;">
                <input type="text" name="account_code[]" placeholder="Code" style="width:100%;">
            </td>
            <td style="border:1px solid #e0e0e0; padding:6px;">
                <input type="number" step="0.01" name="account_debit[]" placeholder="0.00" style="width:100%;">
            </td>
            <td style="border:1px solid #e0e0e0; padding:6px;">
                <input type="number" step="0.01" name="account_credit[]" placeholder="0.00" style="width:100%;">
            </td>
            <td style="border:1px solid #e0e0e0; padding:6px; text-align:center;">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeAccountingRow(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    }

    function removeAccountingRow(button) {
        const tbody = document.getElementById('accounting-rows');
        const rows = tbody.querySelectorAll('tr');
        if (rows.length === 1) {
            rows[0].querySelectorAll('input').forEach(input => input.value = '');
            return;
        }
        button.closest('tr').remove();
    }

    const disburseDateInput = document.getElementById('disburse_date');
    const receivedDateInput = document.getElementById('received_date');
    let lastDisburseDate = disburseDateInput.value;

    disburseDateInput.addEventListener('change', function () {
        if (receivedDateInput.value === lastDisburseDate) {
            receivedDateInput.value = this.value;
        }
        lastDisburseDate = this.value;
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





