<?php
include "../../config/database.php";
include "../../config/session.php";

$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : "";

if ($searchQuery !== "") {
    $searchParam = "%{$searchQuery}%";
    $stmt = $conn->prepare("
        SELECT * FROM cedula
        WHERE issued_by IS NOT NULL
            AND (
                full_name LIKE ?
                OR cedula_no LIKE ?
                OR tin LIKE ?
                OR address LIKE ?
                OR occupation LIKE ?
            )
        ORDER BY issued_date DESC
    ");
    $stmt->bind_param("sssss", $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM cedula WHERE issued_by IS NOT NULL ORDER BY issued_date DESC");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cedula Records - Barangay Sto. Rosario</title>
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
                    <details class="sidebar-dropdown">
                        <summary><i class="fas fa-money-bill-wave"></i> Payments <i
                                class="fas fa-chevron-right dropdown-caret"></i></summary>
                        <ul class="submenu">
                            <li><a href="../payments/list.php"><i class="fas fa-list"></i> All Payments</a></li>
                            <li><a href="../payments/add.php"><i class="fas fa-plus"></i> Certificate</a></li>
                            <li><a href="../payments/manual.php?type=donation"><i class="fas fa-heart"></i> Donation</a>
                            </li>
                            <li><a href="../payments/manual.php?type=garbage"><i class="fas fa-trash"></i> Garbage</a>
                            </li>
                            <li><a href="../payments/manual.php?type=rental"><i class="fas fa-building"></i> Rental</a>
                            </li>
                        </ul>
                    </details>
                </li>
                <li><a href="../pending_payments/list.php"><i class="fas fa-hourglass-half"></i> Pending Status</a></li>
                <li><a href="list.php" class="active"><i class="fas fa-id-card"></i> Cedula</a></li>
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
                <h1><i class="fas fa-id-card"></i> Cedula (Community Tax Certificate)</h1>
            </div>

            <div class="content-body">
                <?php if (isset($_GET['success'])): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i> Cedula issued successfully!
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['updated'])): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i> Cedula record updated successfully!
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header"
                        style="display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                        <h3><i class="fas fa-list"></i> All Cedula Records</h3>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <form method="GET" action="list.php" style="display: flex; gap: 8px;">
                                <input type="text" name="search" placeholder="Search name, cedula, or TIN..."
                                    value="<?= htmlspecialchars($searchQuery) ?>"
                                    style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 6px; min-width: 240px;">
                                <button type="submit" class="btn btn-secondary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </form>
                            <a href="add.php" class="btn btn-success">
                                <i class="fas fa-plus"></i> Issue New Cedula
                            </a>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date Issued</th>
                                    <th>Cedula #</th>
                                    <th>Full Name</th>
                                    <th>Address</th>
                                    <th>Age</th>
                                    <th>Occupation</th>
                                    <th>TIN</th>
                                    <th>Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= date('M d, Y', strtotime($row['issued_date'])) ?>
                                            </td>
                                            <td><strong><?= htmlspecialchars($row['cedula_no'] ?? 'N/A') ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($row['full_name']) ?>
                                            </td>
                                            <td><?= htmlspecialchars($row['address']) ?>
                                            </td>
                                            <td><?= $row['age'] ?>
                                            </td>
                                            <td><?= htmlspecialchars($row['occupation']) ?>
                                            </td>
                                            <td><?= htmlspecialchars($row['tin'] ?? 'N/A') ?>
                                            </td>
                                            <td><strong>₱<?= number_format($row['amount'], 2) ?></strong>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a class="btn btn-sm btn-primary" href="edit.php?id=<?= $row['id'] ?>"
                                                        title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-secondary"
                                                        onclick="viewCedula(<?= $row['id'] ?>)">
                                                        <i class="fas fa-print"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger"
                                                        onclick="deleteCedula(<?= $row['id'] ?>)">
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
                                            <p style="margin-top: 15px; color: #999;">No cedula records found</p>
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
                <p><strong>Warning:</strong> You are about to permanently delete this cedula record.</p>
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

        function viewCedula(id) {
            window.open('print.php?id=' + id, '_blank');
        }

        function deleteCedula(id) {
            deleteId = id;
            // Get the row data to show in modal
            const row = event.target.closest('tr');
            const cedulaNo = row.cells[1].textContent.trim();
            const fullName = row.cells[2].textContent.trim();
            const amount = row.cells[7].textContent.trim();

            document.getElementById('deleteDetails').innerHTML =
                `<strong>Cedula Number:</strong> ${cedulaNo}<br>` +
                `<strong>Name:</strong> ${fullName}<br>` +
                `<strong>Amount:</strong> ${amount}`;

            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            deleteId = null;
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
            if (deleteId) {
                window.location.href = 'save.php?action=delete&id=' + deleteId;
            }
        });

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>
    <script src="../../assets/js/logout-confirm.js"></script>
</body>

</html>