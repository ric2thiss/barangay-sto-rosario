<?php
include "../../config/database.php";
include "../../config/session.php";

$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : "";

if ($searchQuery !== "") {
    $searchParam = "%{$searchQuery}%";
    $stmt = $conn->prepare("
        SELECT * FROM payments
        WHERE service_type <> 'Cedula'
            AND (
                receipt_no LIKE ?
                OR payer_name LIKE ?
                OR service_type LIKE ?
                OR purpose LIKE ?
            )
        ORDER BY id DESC
    ");
    $stmt->bind_param("ssss", $searchParam, $searchParam, $searchParam, $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM payments WHERE service_type <> 'Cedula' ORDER BY id DESC");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments List - Barangay Sto. Rosario</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="dashboard-container">
        <?php 
        $path_prefix = '../';
        include "../partials/sidebar.php"; 
        ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <h1><i class="fas fa-money-bill-wave"></i> Payment Records</h1>
            </div>

            <div class="content-body">
                <?php if (isset($_GET['success'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> Payment recorded successfully!
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['updated'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> Payment updated successfully!
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header"
                        style="display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                        <h3><i class="fas fa-list"></i> All Payments</h3>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <form method="GET" action="list.php" style="display: flex; gap: 8px;">
                                <input type="text" name="search" placeholder="Search payments..."
                                    value="<?= htmlspecialchars($searchQuery) ?>"
                                    style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 6px; min-width: 220px;">
                                <button type="submit" class="btn btn-secondary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </form>
                            <a href="add.php" class="btn btn-success">
                                <i class="fas fa-plus"></i> New Certificate
                            </a>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Receipt #</th>
                                    <th>Payer</th>
                                    <th>Service</th>
                                    <th>Purpose</th>
                                    <th>Amount</th>
                                    <th>BIR Tax</th>
                                    <th>Total</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= isset($row['payment_date']) ? date('M d, Y', strtotime($row['payment_date'])) : date('M d, Y') ?>
                                    </td>
                                    <td><strong><?= htmlspecialchars($row['receipt_no']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($row['payer_name']) ?>
                                    </td>
                                    <td><span
                                            class="badge badge-info"><?= htmlspecialchars($row['service_type']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($row['purpose']) ?>
                                    </td>
                                    <td>₱<?= number_format($row['amount'], 2) ?>
                                    </td>
                                    <td>₱<?= number_format($row['bir_tax'], 2) ?>
                                    </td>
                                    <td><strong>₱<?= number_format($row['amount'] + $row['bir_tax'], 2) ?></strong>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a class="btn btn-sm btn-secondary"
                                                href="print.php?id=<?= $row['id'] ?>"
                                                title="Print">
                                                <i class="fas fa-print"></i>
                                            </a>

                                            <a class="btn btn-sm btn-primary"
                                                href="edit.php?id=<?= $row['id'] ?>"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <button class="btn btn-sm btn-danger"
                                                onclick="deletePayment(<?= $row['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-inbox" style="font-size: 48px; color: #ccc;"></i>
                                        <p style="margin-top: 15px; color: #999;">No payment records found</p>
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

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-exclamation-triangle" style="color: #dc3545; font-size: 48px;"></i>
                <h2>Confirm Delete</h2>
            </div>
            <div class="modal-body">
                <p><strong>Warning:</strong> You are about to permanently delete this payment record.</p>
                <p id="deleteDetails"></p>
                <p style="color: #dc3545; font-weight: bold;">This action cannot be undone!</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i> Delete Permanently
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
        let deleteId = null;

        function viewPayment(id) {
            alert('View payment details for ID: ' + id);
        }

        function deletePayment(id) {
            deleteId = id;
            // Get the row data to show in modal
            const row = event.target.closest('tr');
            const receiptNo = row.cells[1].textContent.trim();
            const payerName = row.cells[2].textContent.trim();
            const amount = row.cells[4].textContent.trim();

            document.getElementById('deleteDetails').innerHTML =
                `<strong>Receipt No:</strong> ${receiptNo}<br>` +
                `<strong>Payer:</strong> ${payerName}<br>` +
                `<strong>Amount:</strong> ${amount}`;

            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            deleteId = null;
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (deleteId) {
                window.location.href = 'save.php?action=delete&id=' + deleteId;
            }
        });

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>
    <script src="../../assets/js/logout-confirm.js"></script>
</body>

</html>





