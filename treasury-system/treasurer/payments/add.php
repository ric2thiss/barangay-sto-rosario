<?php
include "../../config/database.php";
include "../../config/session.php";

// Get last receipt number
$lastReceipt = $conn->query("SELECT receipt_no FROM payments ORDER BY id DESC LIMIT 1")->fetch_assoc();
$nextReceipt = $lastReceipt ? (intval($lastReceipt['receipt_no']) + 1) : 100001;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Certificate Payment - Barangay Sto. Rosario</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
  <div class="dashboard-container">
    <!-- Sidebar -->
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
            <summary><i class="fas fa-money-bill-wave"></i> Payments <i class="fas fa-chevron-right dropdown-caret"></i>
            </summary>
            <ul class="submenu">
              <li><a href="list.php"><i class="fas fa-list"></i> All Payments</a></li>
              <li><a href="add.php" class="active"><i class="fas fa-plus"></i> Certificate</a></li>
              <li><a href="manual.php?type=donation"><i class="fas fa-heart"></i> Donation</a></li>
              <li><a href="manual.php?type=garbage"><i class="fas fa-trash"></i> Garbage</a></li>
              <li><a href="manual.php?type=rental"><i class="fas fa-building"></i> Rental</a></li>
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

    <!-- Main Content -->
    <main class="main-content">
      <div class="content-header">
        <h1><i class="fas fa-money-bill-wave"></i> Record Certificate Payment</h1>
      </div>

      <div class="content-body">
        <div class="card">
          <div class="card-header">
            <h3><i class="fas fa-plus-circle"></i> Certificate Payment Information</h3>
          </div>

          <form method="POST" action="save.php">
            <div class="form-row">
              <div class="form-group">
                <label for="receipt_no"><i class="fas fa-receipt"></i> Receipt Number *</label>
                <input type="text" id="receipt_no" name="receipt_no"
                  value="<?= $nextReceipt ?>" required>
                <small style="color: #666; font-size: 12px;">You can edit this number if needed</small>
              </div>

              <div class="form-group">
                <label for="payment_date"><i class="fas fa-calendar"></i> Payment Date *</label>
                <input type="date" id="payment_date" name="payment_date"
                  value="<?= date('Y-m-d') ?>"
                  required>
              </div>
            </div>

            <div class="form-group">
              <label for="payer_name"><i class="fas fa-user"></i> Payer Name * <small style="color: #666;">(Type to
                  search existing records)</small></label>
              <input type="text" id="payer_name" name="payer_name" placeholder="Enter payer's full name" required
                autocomplete="off">
              <div id="suggestions"
                style="position: absolute; background: white; border: 1px solid #ddd; max-height: 200px; overflow-y: auto; width: calc(100% - 40px); z-index: 1000; display: none; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="service_type"><i class="fas fa-tag"></i> Service Type *</label>
                <select id="service_type" name="service_type" required>
                  <option value="">Select Service</option>
                  <option value="Barangay Clearance">Barangay Clearance</option>
                  <option value="Certificate of Indigency">Certificate of Indigency</option>
                  <option value="Certificate of Residency">Certificate of Residency</option>
                  <option value="Business Permit">Business Permit</option>
                  <option value="Cedula">Cedula</option>
                  <option value="Other">Other</option>
                </select>
              </div>

              <div class="form-group">
                <label for="purpose"><i class="fas fa-info-circle"></i> Purpose *</label>
                <input type="text" id="purpose" name="purpose" placeholder="e.g., Employment, Business, Travel"
                  required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="amount"><i class="fas fa-peso-sign"></i> Amount *</label>
                <input type="number" id="amount" name="amount" step="0.01" min="0" placeholder="0.00" required>
              </div>

              <div class="form-group">
                <label for="bir_tax"><i class="fas fa-percent"></i> BIR Tax/Fee *</label>
                <input type="number" id="bir_tax" name="bir_tax" step="0.01" min="0" value="30" placeholder="0.00"
                  required>
              </div>

              <div class="form-group">
                <label for="total"><i class="fas fa-calculator"></i> Total Amount</label>
                <input type="number" id="total" name="total" step="0.01" readonly
                  style="font-weight: bold; font-size: 18px; background: #e8f0ff;">
              </div>
            </div>

            <div class="form-group">
              <label for="remarks"><i class="fas fa-comment"></i> Remarks</label>
              <textarea id="remarks" name="remarks" rows="3"
                placeholder="Enter any additional notes or remarks..."></textarea>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 25px;">
              <button type="submit" class="btn btn-primary" style="flex: 1;">
                <i class="fas fa-save"></i> Save Certificate Payment
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
    // Calculate total automatically
    function calculateTotal() {
      const amount = parseFloat(document.getElementById('amount').value) || 0;
      const birTax = parseFloat(document.getElementById('bir_tax').value) || 0;
      const total = amount + birTax;
      document.getElementById('total').value = total.toFixed(2);
    }

    document.getElementById('amount').addEventListener('input', calculateTotal);
    document.getElementById('bir_tax').addEventListener('input', calculateTotal);

    // Autocomplete for payer name
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

              // Add click handlers
              document.querySelectorAll('.suggestion-item').forEach(item => {
                item.addEventListener('click', function() {
                  const name = this.dataset.name;
                  payerInput.value = name;
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

    // Hide suggestions when clicking outside
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



