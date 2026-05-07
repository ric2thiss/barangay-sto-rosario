<?php
include "../config/database.php";
include "../config/resident_session.php";

function build_resident_name(array $resident, string $middleMode = 'full'): string
{
    $first = trim($resident['first_name'] ?? '');
    $middle = trim($resident['middle_name'] ?? '');
    $surname = trim($resident['surname'] ?? '');
    $suffix = trim($resident['suffix'] ?? '');

    $parts = [];
    if ($first !== '') {
        $parts[] = $first;
    }

    if ($middle !== '') {
        if ($middleMode === 'initial') {
            $parts[] = strtoupper(substr($middle, 0, 1)) . '.';
        } elseif ($middleMode === 'full') {
            $parts[] = $middle;
        }
    }

    if ($surname !== '') {
        $parts[] = $surname;
    }

    if ($suffix !== '') {
        $parts[] = $suffix;
    }

    return trim(implode(' ', $parts));
}

$residentId = intval($_SESSION['resident_id']);
$stmt = $conn->prepare("SELECT id, first_name, middle_name, surname, suffix, username, barangay, account_status FROM " . DB_PROFILING . ".residents WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $residentId);
$stmt->execute();
$result = $stmt->get_result();
$resident = $result->fetch_assoc();
$stmt->close();

if (!$resident) {
    session_unset();
    session_destroy();
    header("Location: login.php?error=session");
    exit;
}

$status = strtolower(trim($resident['account_status'] ?? 'active'));
if ($status !== 'active') {
    session_unset();
    session_destroy();
    header("Location: login.php?error=account");
    exit;
}

$fullName = build_resident_name($resident, 'full');

$success = '';
$error = '';

if (isset($_GET['submitted'])) {
    $success = 'Payment proof submitted. Please wait for treasurer review.';
} elseif (isset($_GET['cedula_updated'])) {
    $success = 'Cedula request updated. Please wait for treasurer review.';
} elseif (isset($_GET['deleted'])) {
    $success = 'Request cancelled.';
} elseif (isset($_GET['error'])) {
    $error = $_GET['error'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_proof') {
    $paymentId = intval($_POST['payment_id'] ?? 0);

    if ($paymentId <= 0) {
        $error = 'Invalid payment reference.';
    } elseif (!isset($_FILES['proof_file']) || $_FILES['proof_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please upload a valid proof image.';
    } else {
        $fileTmp = $_FILES['proof_file']['tmp_name'];
        $fileSize = intval($_FILES['proof_file']['size'] ?? 0);
        $fileName = $_FILES['proof_file']['name'] ?? '';
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExt = ['jpg', 'jpeg', 'png'];
        if (!in_array($extension, $allowedExt, true)) {
            $error = 'Proof must be a JPG or PNG image.';
        } elseif ($fileSize > 5 * 1024 * 1024) {
            $error = 'Proof must be 5MB or smaller.';
        } else {
            $checkStmt = $conn->prepare("SELECT id FROM payment_status WHERE id = ? AND resident_id = ? AND payment_status IN ('pending', 'to_review', 'rejected')");
            $checkStmt->bind_param("ii", $paymentId, $residentId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $hasRow = $checkResult->num_rows > 0;
            $checkStmt->close();

            if (!$hasRow) {
                $error = 'Payment is not available for proof upload.';
            } else {
                $uploadDir = __DIR__ . '/../uploads/payment_proofs';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $safeName = 'proof_' . $paymentId . '_' . time() . '.' . $extension;
                $targetPath = $uploadDir . '/' . $safeName;
                $relativePath = 'uploads/payment_proofs/' . $safeName;

                if (move_uploaded_file($fileTmp, $targetPath)) {
                    $updateStmt = $conn->prepare("UPDATE payment_status SET proof_path = ?, proof_uploaded_at = NOW(), payment_status = 'to_review' WHERE id = ? AND resident_id = ?");
                    $updateStmt->bind_param("sii", $relativePath, $paymentId, $residentId);
                    $updateOk = $updateStmt->execute();
                    $updateStmt->close();

                    if ($updateOk) {
                        header('Location: pending_payments.php?submitted=1');
                        exit;
                    }
                }

                $error = 'Failed to upload proof. Please try again.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_request') {
    $paymentId = intval($_POST['payment_id'] ?? 0);

    if ($paymentId <= 0) {
        $error = 'Invalid payment reference.';
    } else {
        $checkStmt = $conn->prepare("SELECT certificate_type, proof_path FROM payment_status WHERE id = ? AND resident_id = ? AND payment_status IN ('pending', 'to_review', 'rejected')");
        $checkStmt->bind_param("ii", $paymentId, $residentId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows === 0) {
            $checkStmt->close();
            $error = 'Request not found or cannot be cancelled.';
        } else {
            $row = $checkResult->fetch_assoc();
            $checkStmt->close();

            $conn->begin_transaction();

            $deleteCedulaOk = true;
            if (strcasecmp(trim((string) $row['certificate_type']), 'Cedula') === 0) {
                $cedulaStmt = $conn->prepare("DELETE FROM cedula WHERE resident_id = ? AND issued_by IS NULL ORDER BY id DESC LIMIT 1");
                $cedulaStmt->bind_param("i", $residentId);
                $deleteCedulaOk = $cedulaStmt->execute();
                $cedulaStmt->close();
            }

            $deleteStmt = $conn->prepare("DELETE FROM payment_status WHERE id = ? AND resident_id = ?");
            $deleteStmt->bind_param("ii", $paymentId, $residentId);
            $deleteOk = $deleteStmt->execute();
            $deleteStmt->close();

            if ($deleteOk && $deleteCedulaOk) {
                $conn->commit();

                $proofPath = trim((string) ($row['proof_path'] ?? ''));
                if ($proofPath !== '') {
                    $baseDir = realpath(__DIR__ . '/..');
                    $fullPath = $baseDir ? realpath($baseDir . '/' . $proofPath) : false;
                    if ($fullPath && $baseDir && strpos($fullPath, $baseDir) === 0 && is_file($fullPath)) {
                        @unlink($fullPath);
                    }
                }

                header('Location: pending_payments.php?deleted=1');
                exit;
            }

            $conn->rollback();
            $error = 'Failed to cancel request. Please try again.';
        }
    }
}

$searchQuery = trim($_GET['search'] ?? '');
$searchParam = "%{$searchQuery}%";

$sql = "SELECT * FROM payment_status WHERE payment_status IN ('pending', 'to_review', 'rejected') AND resident_id = ?";
$params = [$residentId];
$types = "i";

if ($searchQuery !== '') {
    $sql .= " AND (certificate_type LIKE ? OR purpose LIKE ?)";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

$sql .= " ORDER BY created_at DESC, id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
$amountTotal = 0;
$birTotal = 0;

while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
    if (($row['payment_status'] ?? '') !== 'rejected') {
        $amountTotal += floatval($row['amount'] ?? 0);
        $birTotal += floatval($row['bir_tax'] ?? 0);
    }
}

$stmt->close();
$totalCount = count($rows);
$grandTotal = $amountTotal + $birTotal;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Payments - Resident Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                <li><a href="pending_payments.php" class="active"><i class="fas fa-hourglass-half"></i> Pending
                        Payments</a></li>
                <li><a href="payment_history.php"><i class="fas fa-receipt"></i> Payment History</a></li>
                <li><a href="request_cedula.php"><i class="fas fa-id-card"></i> Request Cedula</a></li>
                <li><a href="donation.php"><i class="fas fa-heart"></i> Make Donation</a></li>
                <li><a href="garbage.php"><i class="fas fa-trash"></i> Garbage Payment</a></li>
                <li><a href="rental.php"><i class="fas fa-building"></i> Rent Facilities</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="content-header">
                <h1><i class="fas fa-hourglass-half"></i> Pending Payments</h1>
                <p>Welcome, <?= htmlspecialchars($fullName) ?></p>
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
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <h4>Pending Items</h4>
                        <div class="stat-value">
                            <?= number_format($totalCount) ?>
                        </div>
                    </div>
                    <div class="stat-card green">
                        <h4>Total Amount To pay</h4>
                        <div class="stat-value">PHP
                            <?= number_format($amountTotal, 2) ?>
                        </div>
                    </div>

                </div>

                <div class="card">
                    <div class="card-header resident-payments-header">
                        <h3><i class="fas fa-list"></i> Your Pending Payments</h3>
                        <form method="GET" action="pending_payments.php" class="resident-search-form">
                            <input type="text" name="search" placeholder="Search certificate or purpose"
                                value="<?= htmlspecialchars($searchQuery) ?>"
                                class="resident-search-input">
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </form>
                    </div>

                    <div style="margin-bottom: 15px; color: #4a5568; font-size: 14px;">
                        Showing records linked to your resident account. If you see missing items, please contact the
                        treasurer.
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Certificate Type</th>
                                    <th>Purpose</th>
                                    <th>Amount</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                    <th class="action-cell">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($totalCount > 0): ?>
                                <?php foreach ($rows as $row): ?>
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
                                    <td><span
                                            class="badge badge-info"><?= htmlspecialchars($row['certificate_type']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($row['purpose']) ?>
                                    </td>
                                    <td>PHP
                                        <?= number_format($row['amount'], 2) ?>
                                    </td>
                                    <td><strong>PHP
                                            <?= number_format($row['amount'] + $row['bir_tax'], 2) ?></strong>
                                    </td>
                                    <td><span
                                            class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                                    </td>
                                    <td class="remarks-cell">
                                        <?php if ($row['payment_status'] === 'rejected' && !empty($row['rejection_remarks'])): ?>
                                        <?= htmlspecialchars($row['rejection_remarks']) ?>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="action-cell">
                                        <div class="resident-action-buttons">
                                            <?php
                                            $proofPath = trim((string) ($row['proof_path'] ?? ''));
                                    $hasProof = $proofPath !== '';
                                    $proofUrl = $hasProof ? '../' . $proofPath : '';
                                    ?>
                                            <?php if ($row['payment_status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-sm btn-primary pay-btn"
                                                data-id="<?= $row['id'] ?>"
                                                data-certificate="<?= htmlspecialchars($row['certificate_type']) ?>"
                                                data-purpose="<?= htmlspecialchars($row['purpose']) ?>"
                                                data-total="PHP <?= number_format($row['amount'] + $row['bir_tax'], 2) ?>"
                                                data-mode="pay" title="Pay" aria-label="Pay">
                                                <i class="fas fa-qrcode"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger delete-btn"
                                                data-id="<?= $row['id'] ?>"
                                                data-certificate="<?= htmlspecialchars($row['certificate_type']) ?>"
                                                data-purpose="<?= htmlspecialchars($row['purpose']) ?>"
                                                title="Cancel" aria-label="Cancel">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php elseif ($row['payment_status'] === 'to_review'): ?>
                                            <button type="button" class="btn btn-sm btn-secondary pay-btn"
                                                data-id="<?= $row['id'] ?>"
                                                data-certificate="<?= htmlspecialchars($row['certificate_type']) ?>"
                                                data-purpose="<?= htmlspecialchars($row['purpose']) ?>"
                                                data-total="PHP <?= number_format($row['amount'] + $row['bir_tax'], 2) ?>"
                                                data-mode="update"
                                                data-proof="<?= htmlspecialchars($proofUrl) ?>"
                                                title="Update Proof" aria-label="Update Proof">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <?php if ($hasProof): ?>
                                            <a class="btn btn-sm btn-secondary"
                                                href="<?= htmlspecialchars($proofUrl) ?>"
                                                target="_blank" rel="noopener" title="View Proof"
                                                aria-label="View Proof">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-danger delete-btn"
                                                data-id="<?= $row['id'] ?>"
                                                data-certificate="<?= htmlspecialchars($row['certificate_type']) ?>"
                                                data-purpose="<?= htmlspecialchars($row['purpose']) ?>"
                                                title="Cancel" aria-label="Cancel">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php elseif ($row['payment_status'] === 'rejected'): ?>
                                            <button type="button" class="btn btn-sm btn-secondary pay-btn"
                                                data-id="<?= $row['id'] ?>"
                                                data-certificate="<?= htmlspecialchars($row['certificate_type']) ?>"
                                                data-purpose="<?= htmlspecialchars($row['purpose']) ?>"
                                                data-total="PHP <?= number_format($row['amount'] + $row['bir_tax'], 2) ?>"
                                                data-mode="update"
                                                data-proof="<?= htmlspecialchars($proofUrl) ?>"
                                                title="Update Proof" aria-label="Update Proof">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <?php if ($hasProof): ?>
                                            <a class="btn btn-sm btn-secondary"
                                                href="<?= htmlspecialchars($proofUrl) ?>"
                                                target="_blank" rel="noopener" title="View Proof"
                                                aria-label="View Proof">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if (strcasecmp(trim((string) $row['certificate_type']), 'Cedula') === 0): ?>
                                            <a class="btn btn-sm btn-primary" href="request_cedula.php?edit=1"
                                                title="Edit Cedula" aria-label="Edit Cedula">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-danger delete-btn"
                                                data-id="<?= $row['id'] ?>"
                                                data-certificate="<?= htmlspecialchars($row['certificate_type']) ?>"
                                                data-purpose="<?= htmlspecialchars($row['purpose']) ?>"
                                                title="Cancel" aria-label="Cancel">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php else: ?>
                                            <span class="text-muted">Submitted</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-inbox" style="font-size: 48px; color: #ccc;"></i>
                                        <p style="margin-top: 15px; color: #999;">No pending payments found</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user"></i> Resident Details</h3>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
                        <div><strong>Name:</strong>
                            <?= htmlspecialchars($fullName) ?>
                        </div>
                        <div><strong>Username:</strong>
                            <?= htmlspecialchars($resident['username']) ?>
                        </div>
                        <div><strong>Barangay:</strong>
                            <?= htmlspecialchars($resident['barangay'] ?? 'N/A') ?>
                        </div>
                        <div><strong>Matched Total:</strong> PHP
                            <?= number_format($grandTotal, 2) ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div id="payModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-qrcode" style="color: #1e3a5f; font-size: 40px;"></i>
                <h2 id="payModalTitle">Pay & Upload Proof</h2>
            </div>
            <div class="modal-body">
                <div class="qr-wrapper">
                    <img src="../assets/images/qr.jpg" alt="Instapay QR Code" class="qr-image">
                    <p class="qr-note" id="payModalNote">Scan the QR code to pay, then upload your proof below.</p>
                </div>
                <div class="payment-summary" id="paymentSummary"></div>
                <div id="proofPreview" style="display: none; margin-top: 12px;">
                    <img id="proofPreviewImg" alt="Uploaded proof"
                        style="width: 100%; border-radius: 12px; border: 1px solid #e2e8f0;">
                </div>
                <form method="POST" enctype="multipart/form-data" class="proof-form">
                    <input type="hidden" name="action" value="submit_proof">
                    <input type="hidden" name="payment_id" id="payPaymentId">
                    <div class="form-group">
                        <label for="proof_file"><i class="fas fa-file-upload"></i> Proof of Payment</label>
                        <input type="file" id="proof_file" name="proof_file" accept="image/png, image/jpeg" required>
                        <small style="color: #666;">Accepted: JPG or PNG (max 5MB)</small>
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="button" class="btn btn-secondary" id="closePayModal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Submit Proof
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <form id="deleteForm" method="POST" action="pending_payments.php" style="display: none;">
        <input type="hidden" name="action" value="delete_request">
        <input type="hidden" name="payment_id" id="deletePaymentId">
    </form>

    <script>
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.querySelector('.sidebar');
        const payModal = document.getElementById('payModal');
        const payButtons = document.querySelectorAll('.pay-btn');
        const deleteButtons = document.querySelectorAll('.delete-btn');
        const paymentSummary = document.getElementById('paymentSummary');
        const payPaymentId = document.getElementById('payPaymentId');
        const closePayModal = document.getElementById('closePayModal');
        const payModalTitle = document.getElementById('payModalTitle');
        const payModalNote = document.getElementById('payModalNote');
        const proofPreview = document.getElementById('proofPreview');
        const proofPreviewImg = document.getElementById('proofPreviewImg');
        const deleteForm = document.getElementById('deleteForm');
        const deletePaymentId = document.getElementById('deletePaymentId');

        if (mobileMenuBtn && sidebar) {
            mobileMenuBtn.addEventListener('click', () => {
                sidebar.classList.toggle('active');
            });
        }

        payButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const mode = button.dataset.mode || 'pay';
                const proofUrl = button.dataset.proof || '';
                payPaymentId.value = button.dataset.id;
                paymentSummary.innerHTML =
                    `<strong>Certificate:</strong> ${button.dataset.certificate}<br>` +
                    `<strong>Purpose:</strong> ${button.dataset.purpose}<br>` +
                    `<strong>Total:</strong> ${button.dataset.total}`;
                if (mode === 'update') {
                    payModalTitle.textContent = 'Update Proof of Payment';
                    payModalNote.textContent = 'Upload a clearer proof image for review.';
                    if (proofUrl) {
                        proofPreviewImg.src = proofUrl;
                        proofPreview.style.display = 'block';
                    } else {
                        proofPreview.style.display = 'none';
                        proofPreviewImg.removeAttribute('src');
                    }
                } else {
                    payModalTitle.textContent = 'Pay & Upload Proof';
                    payModalNote.textContent = 'Scan the QR code to pay, then upload your proof below.';
                    proofPreview.style.display = 'none';
                    proofPreviewImg.removeAttribute('src');
                }
                payModal.style.display = 'flex';
            });
        });

        deleteButtons.forEach((button) => {
            button.addEventListener('click', () => {
                if (!deleteForm || !deletePaymentId) {
                    return;
                }
                const certificate = button.dataset.certificate || 'this request';
                const purpose = button.dataset.purpose || '';
                const label = purpose ? `${certificate} - ${purpose}` : certificate;
                const confirmed = confirm(`Cancel ${label}? This will delete the request.`);
                if (!confirmed) {
                    return;
                }
                deletePaymentId.value = button.dataset.id;
                deleteForm.submit();
            });
        });

        if (closePayModal) {
            closePayModal.addEventListener('click', () => {
                payModal.style.display = 'none';
            });
        }

        window.addEventListener('click', (event) => {
            if (event.target === payModal) {
                payModal.style.display = 'none';
            }
        });
    </script>
</body>

</html>
