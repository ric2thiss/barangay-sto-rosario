<?php
include "../../config/database.php";
include "../../config/session.php";

$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : "";

if ($searchQuery !== "") {
    $searchParam = "%{$searchQuery}%";
    $stmt = $conn->prepare("
        SELECT * FROM disbursements
        WHERE check_no LIKE ?
            OR payee LIKE ?
            OR dv_no LIKE ?
            OR purpose LIKE ?
            OR fund LIKE ?
            OR payroll LIKE ?
            OR bir LIKE ?
        ORDER BY disburse_date DESC, id DESC
    ");
    $stmt->bind_param("sssssss", $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM disbursements ORDER BY disburse_date DESC, id DESC");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disbursements - Barangay Sto. Rosario</title>
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
                <h1><i class="fas fa-hand-holding-usd"></i> Records of Disbursement</h1>
            </div>

            <div class="content-body">
                <?php if (isset($_GET['success'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> Disbursement recorded successfully!
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['updated'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> Disbursement record updated successfully!
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['deleted'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> Disbursement record deleted successfully!
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header"
                        style="display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                        <h3><i class="fas fa-list"></i> All Disbursement Records</h3>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <form method="GET" action="list.php" style="display: flex; gap: 8px;">
                                <input type="text" name="search" placeholder="Search payee, DV, check, fund..."
                                    value="<?= htmlspecialchars($searchQuery) ?>"
                                    style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 6px; min-width: 260px;">
                                <button type="submit" class="btn btn-secondary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </form>
                            <a href="add.php" class="btn btn-success">
                                <i class="fas fa-plus"></i> New Disbursement
                            </a>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>CH CH #</th>
                                    <th>Payee</th>
                                    <th>DV No.</th>
                                    <th>Amount</th>
                                    <th>Fund</th>
                                    <th>Payroll</th>
                                    <th>BIR</th>
                                    <th>Particular</th>
                                    <th>Release Amnt</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($row['disburse_date'])) ?>
                                    </td>
                                    <td><strong><?= htmlspecialchars($row['check_no']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($row['payee']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['dv_no']) ?>
                                    </td>
                                    <td>₱<?= number_format($row['amount'], 2) ?>
                                    </td>
                                    <td><span
                                            class="badge badge-info"><?= htmlspecialchars($row['fund'] ?? 'N/A') ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($row['payroll'] ?? 'N/A') ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['bir'] ?? 'N/A') ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['purpose']) ?>
                                    </td>
                                    <td><strong>₱<?= number_format($row['release_amount'], 2) ?></strong>
                                    </td>
                                    <td>
                                        <div class="action-buttons">

                                            <a class="btn btn-sm btn-primary"
                                                href="edit.php?id=<?= $row['id'] ?>"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <a class="btn btn-sm btn-secondary"
                                                href="print.php?id=<?= $row['id'] ?>"
                                                target="_blank" title="Print">
                                                <i class="fas fa-print"></i>
                                            </a>

                                            <button class="btn btn-sm btn-danger"
                                                onclick="deleteDisbursement(<?= $row['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="11" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-inbox" style="font-size: 48px; color: #ccc;"></i>
                                        <p style="margin-top: 15px; color: #999;">No disbursement records found</p>
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
                <p><strong>Warning:</strong> You are about to permanently delete this disbursement record.</p>
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

        function viewDisbursement(id) {
            alert('View disbursement ID: ' + id);
        }

        function deleteDisbursement(id) {
            deleteId = id;
            const row = event.target.closest('tr');
            const checkNo = row.cells[1].textContent.trim();
            const payee = row.cells[2].textContent.trim();
            const amount = row.cells[4].textContent.trim();

            document.getElementById('deleteDetails').innerHTML =
                `<strong>Check No:</strong> ${checkNo}<br>` +
                `<strong>Payee:</strong> ${payee}<br>` +
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

        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>
    <script src="../../assets/js/logout-confirm.js"></script>
</body>

</html>





