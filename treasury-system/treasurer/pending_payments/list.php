<?php
include "../../config/database.php";
include "../../config/session.php";

$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : "";
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : "open";

$allowedFilters = [
    'pending' => ['pending'],
    'rejected' => ['rejected'],
    'open' => ['pending', 'to_review'],
];

if (!array_key_exists($statusFilter, $allowedFilters)) {
    $statusFilter = 'open';
}

$statusValues = $allowedFilters[$statusFilter];
$statusSql = "'" . implode("','", $statusValues) . "'";

if ($searchQuery !== "") {
    $searchParam = "%{$searchQuery}%";
    $stmt = $conn->prepare("
        SELECT * FROM payment_status
        WHERE payment_status IN ($statusSql)
            AND (
                resident_fname LIKE ?
                OR certificate_type LIKE ?
                OR purpose LIKE ?
            )
        ORDER BY created_at DESC, id DESC
    ");
    $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("
        SELECT * FROM payment_status
        WHERE payment_status IN ($statusSql)
        ORDER BY created_at DESC, id DESC
    ");
}

$success = "";
if (isset($_GET['paid'])) {
    $success = "Pending payment marked as paid.";
} elseif (isset($_GET['updated'])) {
    $success = "Pending payment updated.";
} elseif (isset($_GET['deleted'])) {
    $success = "Pending payment deleted.";
}
$error = isset($_GET['error']) ? $_GET['error'] : "";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Status - Barangay Sto. Rosario</title>
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
                <h1><i class="fas fa-hourglass-half"></i> Pending & To Review Payments</h1>
            </div>

            <div class="content-body">
                <?php if ($success): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header"
                        style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <h3><i class="fas fa-list"></i> Pending & To Review</h3>
                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <div style="display: flex; gap: 8px;">
                                <a class="btn btn-secondary" href="list.php">All</a>
                                <a class="btn btn-secondary" href="list.php?status=pending">Pending</a>
                                <a class="btn btn-secondary" href="list.php?status=rejected">Rejected</a>
                            </div>
                            <form method="GET" action="list.php" style="display: flex; gap: 8px;">
                                <input type="hidden" name="status"
                                    value="<?= htmlspecialchars($statusFilter) ?>">
                                <input type="text" name="search" placeholder="Search resident or purpose..."
                                    value="<?= htmlspecialchars($searchQuery) ?>"
                                    style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 6px; min-width: 220px;">
                                <button type="submit" class="btn btn-secondary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Resident</th>
                                    <th>Certificate Type</th>
                                    <th>Purpose</th>
                                    <th>Amount</th>
                                    <th>BIR Tax</th>
                                    <th>Total</th>
                                    <th>Proof</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <?php
                                    if ($row['payment_status'] === 'rejected') {
                                        $statusText = 'Rejected';
                                        $statusClass = 'badge-danger';
                                    } else {
                                        $statusText = $row['payment_status'] === 'to_review' ? 'To Review' : 'Pending';
                                        $statusClass = $row['payment_status'] === 'to_review' ? 'badge-review' : 'badge-warning';
                                    }
                                    ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($row['created_at'])) ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['resident_fname']) ?>
                                    </td>
                                    <td><span
                                            class="badge badge-info"><?= htmlspecialchars($row['certificate_type']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($row['purpose']) ?>
                                    </td>
                                    <td>₱<?= number_format($row['amount'] - $row['bir_tax'], 2) ?>
                                    </td>
                                    <td>₱<?= number_format($row['bir_tax'], 2) ?>
                                    </td>
                                    <td><strong>₱<?= number_format($row['amount'], 2) ?></strong>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['proof_path'])): ?>
                                        <a class="btn btn-sm btn-secondary"
                                            href="../../<?= htmlspecialchars($row['proof_path']) ?>"
                                            target="_blank" title="View Proof">
                                            <i class="fas fa-receipt"></i>
                                        </a>
                                        <?php else: ?>
                                        <span class="text-muted">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span
                                            class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a class="btn btn-sm btn-secondary"
                                                href="edit.php?id=<?= $row['id'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-sm btn-success"
                                                onclick="confirmPaid(<?= $row['id'] ?>, this)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger"
                                                onclick="confirmReject(<?= $row['id'] ?>, this)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger"
                                                onclick="confirmDelete(<?= $row['id'] ?>, this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="10" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-inbox" style="font-size: 48px; color: #ccc;"></i>
                                        <p style="margin-top: 15px; color: #999;">No pending payments found</p>
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
                <button class="btn btn-secondary" onclick="closePaidModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn btn-success" id="confirmPaidBtn">
                    <i class="fas fa-check"></i> Yes, Mark Paid
                </button>
            </div>
        </div>
    </div>

    <!-- Reject Confirmation Modal -->
    <div id="rejectModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-times-circle" style="color: #dc3545; font-size: 48px;"></i>
                <h2>Reject Payment</h2>
            </div>
            <div class="modal-body">
                <p><strong>Please provide a reason for rejection.</strong></p>
                <p id="rejectDetails"></p>
                <div class="form-group">
                    <label for="rejectRemarks"><i class="fas fa-comment-slash"></i> Remarks</label>
                    <textarea id="rejectRemarks" rows="3" placeholder="Reason for rejection"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeRejectModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn btn-danger" id="confirmRejectBtn">
                    <i class="fas fa-times"></i> Reject
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-trash" style="color: #dc3545; font-size: 48px;"></i>
                <h2>Delete Payment</h2>
            </div>
            <div class="modal-body">
                <p><strong>Are you sure?</strong> This will permanently delete the record.</p>
                <p id="deleteDetails"></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i> Yes, Delete
                </button>
            </div>
        </div>
    </div>

    <form id="markPaidForm" method="POST" action="save.php" style="display: none;">
        <input type="hidden" name="action" value="mark_paid">
        <input type="hidden" name="id" id="markPaidId">
    </form>

    <form id="rejectForm" method="POST" action="save.php" style="display: none;">
        <input type="hidden" name="action" value="reject">
        <input type="hidden" name="id" id="rejectId">
        <input type="hidden" name="rejection_remarks" id="rejectRemarksInput">
    </form>

    <form id="deleteForm" method="POST" action="save.php" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>

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
        let markPaidId = null;
        let rejectId = null;
        let deleteId = null;

        function confirmPaid(id, button) {
            markPaidId = id;
            const row = button.closest('tr');
            const resident = row.cells[1].textContent.trim();
            const certificateType = row.cells[2].textContent.trim();
            const amount = row.cells[6].textContent.trim();

            document.getElementById('paidDetails').innerHTML =
                `<strong>Resident:</strong> ${resident}<br>` +
                `<strong>Certificate:</strong> ${certificateType}<br>` +
                `<strong>Amount:</strong> ${amount}`;

            document.getElementById('paidModal').style.display = 'flex';
        }

        function closePaidModal() {
            document.getElementById('paidModal').style.display = 'none';
            markPaidId = null;
        }

        function confirmReject(id, button) {
            rejectId = id;
            const row = button.closest('tr');
            const resident = row.cells[1].textContent.trim();
            const certificateType = row.cells[2].textContent.trim();
            const amount = row.cells[6].textContent.trim();

            document.getElementById('rejectDetails').innerHTML =
                `<strong>Resident:</strong> ${resident}<br>` +
                `<strong>Certificate:</strong> ${certificateType}<br>` +
                `<strong>Amount:</strong> ${amount}`;
            document.getElementById('rejectRemarks').value = '';
            document.getElementById('rejectModal').style.display = 'flex';
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
            rejectId = null;
        }

        function confirmDelete(id, button) {
            deleteId = id;
            const row = button.closest('tr');
            const resident = row.cells[1].textContent.trim();
            const certificateType = row.cells[2].textContent.trim();
            const amount = row.cells[6].textContent.trim();

            document.getElementById('deleteDetails').innerHTML =
                `<strong>Resident:</strong> ${resident}<br>` +
                `<strong>Certificate:</strong> ${certificateType}<br>` +
                `<strong>Amount:</strong> ${amount}`;

            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            deleteId = null;
        }

        document.getElementById('confirmPaidBtn').addEventListener('click', function() {
            if (markPaidId) {
                document.getElementById('markPaidId').value = markPaidId;
                document.getElementById('markPaidForm').submit();
            }
        });

        document.getElementById('confirmRejectBtn').addEventListener('click', function() {
            const remarks = document.getElementById('rejectRemarks').value.trim();
            if (!rejectId) {
                return;
            }
            if (!remarks) {
                alert('Please provide a rejection remark.');
                return;
            }
            document.getElementById('rejectId').value = rejectId;
            document.getElementById('rejectRemarksInput').value = remarks;
            document.getElementById('rejectForm').submit();
        });

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (!deleteId) {
                return;
            }
            document.getElementById('deleteId').value = deleteId;
            document.getElementById('deleteForm').submit();
        });

        window.addEventListener('click', function(event) {
            const paidModal = document.getElementById('paidModal');
            const rejectModal = document.getElementById('rejectModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target === paidModal) {
                closePaidModal();
            } else if (event.target === rejectModal) {
                closeRejectModal();
            } else if (event.target === deleteModal) {
                closeDeleteModal();
            }
        });
    </script>
    <script src="../../assets/js/logout-confirm.js"></script>
</body>

</html>





