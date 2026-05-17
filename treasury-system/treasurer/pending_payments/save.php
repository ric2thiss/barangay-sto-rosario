<?php
include "../../config/database.php";
include "../../config/session.php";

function resolve_received_by($conn)
{
    if (!empty($_SESSION['user_id'])) {
        $userCheck = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $userCheck->bind_param("i", $_SESSION['user_id']);
        $userCheck->execute();
        $userCheck->store_result();
        if ($userCheck->num_rows > 0) {
            $userCheck->close();
            return intval($_SESSION['user_id']);
        }
        $userCheck->close();
    }

    return null;
}

function update_services_system_payment_status($conn, $residentId, $certificateType, $purpose, $newStatus = 'Paid', $remarks = null)
{
    if (!$residentId) return;

    $srvQuery = $conn->prepare("
        SELECT cr.request_id 
        FROM `services-system`.certificate_requests cr
        JOIN `services-system`.certificate_types ct ON cr.certificate_type_id = ct.certificate_type_id
        WHERE cr.resident_id = ? AND ct.certificate_name = ? AND cr.purpose = ? AND cr.payment_status != ?
        ORDER BY cr.request_id DESC LIMIT 1
    ");
    
    if (!$srvQuery) return; // Silent fail if database doesn't exist or no permission
    
    $srvQuery->bind_param("isss", $residentId, $certificateType, $purpose, $newStatus);
    $srvQuery->execute();
    $srvResult = $srvQuery->get_result();
    
    if ($srvResult->num_rows > 0) {
        $reqId = $srvResult->fetch_assoc()['request_id'];
        $srvUpdate = $conn->prepare("UPDATE `services-system`.certificate_requests SET payment_status = ?, payment_remarks = ? WHERE request_id = ?");
        if ($srvUpdate) {
            $srvUpdate->bind_param("ssi", $newStatus, $remarks, $reqId);
            $srvUpdate->execute();
            $srvUpdate->close();
        }
    }
    $srvQuery->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_paid') {
    $pendingId = intval($_POST['id'] ?? 0);

    if ($pendingId <= 0) {
        header("Location: list.php?error=Invalid pending payment ID.");
        exit;
    }

    $pendingStmt = $conn->prepare("SELECT * FROM payment_status WHERE id = ? AND payment_status IN ('pending', 'to_review')");
    $pendingStmt->bind_param("i", $pendingId);
    $pendingStmt->execute();
    $pendingResult = $pendingStmt->get_result();

    if ($pendingResult->num_rows === 0) {
        $pendingStmt->close();
        header("Location: list.php?error=Pending payment not found or already paid.");
        exit;
    }

    $pending = $pendingResult->fetch_assoc();
    $pendingStmt->close();

    $lastReceipt = $conn->query("SELECT receipt_no FROM payments ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextReceipt = $lastReceipt ? (intval($lastReceipt['receipt_no']) + 1) : 100001;
    $paymentDate = date('Y-m-d');

    $received_by = resolve_received_by($conn);
    $residentId = !empty($pending['resident_id']) ? intval($pending['resident_id']) : null;

    $remarks = "Pending Status #" . $pendingId;

    $conn->begin_transaction();

    $insertStmt = $conn->prepare("
        INSERT INTO payments (receipt_no, payment_date, payer_name, service_type, purpose, amount, bir_tax, remarks, received_by, resident_id, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $insertStmt->bind_param(
        "sssssddsii",
        $nextReceipt,
        $paymentDate,
        $pending['resident_fname'],
        $pending['certificate_type'],
        $pending['purpose'],
        $pending['amount'],
        $pending['bir_tax'],
        $remarks,
        $received_by,
        $residentId
    );
    $insertOk = $insertStmt->execute();
    $insertStmt->close();

    $updateOk = false;
    $cedulaOk = true;
    if ($insertOk) {
        $updateStmt = $conn->prepare("UPDATE payment_status SET payment_status = 'paid', created_at = NOW() WHERE id = ?");
        $updateStmt->bind_param("i", $pendingId);
        $updateOk = $updateStmt->execute();
        $updateStmt->close();

        if ($updateOk) {
            update_services_system_payment_status($conn, $residentId, $pending['certificate_type'], $pending['purpose']);
        }

        if ($updateOk && strcasecmp(trim((string) $pending['certificate_type']), 'Cedula') === 0) {
            $yearIssued = intval(date('Y', strtotime($paymentDate)));
            $cedulaStmt = $conn->prepare("
                UPDATE cedula
                SET issued_by = ?, issued_date = ?, year_issued = ?
                WHERE issued_by IS NULL 
                  AND (full_name = ? OR (resident_id = ? AND resident_id IS NOT NULL AND resident_id != 0))
                ORDER BY id DESC
                LIMIT 1
            ");
            $cedulaStmt->bind_param("isisi", $received_by, $paymentDate, $yearIssued, $pending['resident_fname'], $residentId);
            $cedulaOk = $cedulaStmt->execute();
            $cedulaStmt->close();
        }
    }

    if ($insertOk && $updateOk && $cedulaOk) {
        $conn->commit();
        header("Location: list.php?paid=1");
    } else {
        $conn->rollback();
        header("Location: list.php?error=Failed to mark as paid.");
    }

    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reject') {
    $pendingId = intval($_POST['id'] ?? 0);
    $rejectionRemarks = trim($_POST['rejection_remarks'] ?? '');

    if ($pendingId <= 0) {
        header("Location: list.php?error=Invalid pending payment ID.");
        exit;
    }

    if ($rejectionRemarks === '') {
        header("Location: list.php?error=Please provide a rejection remark.");
        exit;
    }

    $checkStmt = $conn->prepare("SELECT * FROM payment_status WHERE id = ?");
    $checkStmt->bind_param("i", $pendingId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    if ($checkResult->num_rows === 0) {
        $checkStmt->close();
        header("Location: list.php?error=Pending payment not found.");
        exit;
    }
    $pendingRow = $checkResult->fetch_assoc();
    $currentStatus = $pendingRow['payment_status'];
    $rejectResidentId = $pendingRow['resident_id'];
    $rejectCertificateType = $pendingRow['certificate_type'];
    $rejectPurpose = $pendingRow['purpose'];
    $checkStmt->close();

    if ($currentStatus === 'paid') {
        header("Location: list.php?error=Payment is already marked as paid.");
        exit;
    }

    $updateStmt = $conn->prepare("
        UPDATE payment_status
        SET payment_status = 'rejected', rejection_remarks = ?, rejected_at = NOW()
        WHERE id = ?
    ");
    $updateStmt->bind_param("si", $rejectionRemarks, $pendingId);
    $updateOk = $updateStmt->execute();
    $updateStmt->close();

    if ($updateOk) {
        update_services_system_payment_status($conn, $rejectResidentId, $rejectCertificateType, $rejectPurpose, 'Rejected', $rejectionRemarks);
        header("Location: list.php?updated=1");
    } else {
        header("Location: list.php?error=Failed to reject payment.");
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $pendingId = intval($_POST['id'] ?? 0);

    if ($pendingId <= 0) {
        header("Location: list.php?error=Invalid pending payment ID.");
        exit;
    }

    $checkStmt = $conn->prepare("SELECT payment_status, proof_path FROM payment_status WHERE id = ?");
    $checkStmt->bind_param("i", $pendingId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    if ($checkResult->num_rows === 0) {
        $checkStmt->close();
        header("Location: list.php?error=Pending payment not found.");
        exit;
    }
    $row = $checkResult->fetch_assoc();
    $checkStmt->close();

    if (!in_array($row['payment_status'], ['pending', 'to_review', 'rejected'], true)) {
        header("Location: list.php?error=Cannot delete a paid payment.");
        exit;
    }

    $proofPath = trim((string) $row['proof_path']);

    $deleteStmt = $conn->prepare("DELETE FROM payment_status WHERE id = ?");
    $deleteStmt->bind_param("i", $pendingId);
    $deleteOk = $deleteStmt->execute();
    $deleteStmt->close();

    if ($deleteOk) {
        if ($proofPath !== '') {
            $baseDir = realpath(__DIR__ . "/../..");
            $fullPath = $baseDir ? realpath($baseDir . "/" . $proofPath) : false;
            if ($fullPath && $baseDir && strpos($fullPath, $baseDir) === 0 && is_file($fullPath)) {
                @unlink($fullPath);
            }
        }
        header("Location: list.php?deleted=1");
    } else {
        header("Location: list.php?error=Failed to delete payment.");
    }

    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $pendingId = intval($_POST['id'] ?? 0);
    $residentName = trim($_POST['resident_fname'] ?? '');
    $residentIdInput = trim($_POST['resident_id'] ?? '');
    $residentId = $residentIdInput === '' ? null : intval($residentIdInput);
    $certificateType = trim($_POST['certificate_type'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $baseAmount = floatval($_POST['amount'] ?? 0);
    $birTax = floatval($_POST['bir_tax'] ?? 0);
    $totalAmount = $baseAmount + $birTax;
    $status = strtolower(trim($_POST['payment_status'] ?? 'pending'));
    $rejectionRemarks = trim($_POST['rejection_remarks'] ?? '');

    if ($pendingId <= 0) {
        header("Location: list.php?error=Invalid pending payment ID.");
        exit;
    }

    if ($residentName === '' || $certificateType === '' || $purpose === '') {
        header("Location: edit.php?id=$pendingId&error=Please fill in all required fields.");
        exit;
    }

    if ($amount < 0 || $birTax < 0) {
        header("Location: edit.php?id=$pendingId&error=Amounts must be zero or greater.");
        exit;
    }

    if (!in_array($status, ['pending', 'to_review', 'paid', 'rejected'], true)) {
        header("Location: edit.php?id=$pendingId&error=Invalid payment status.");
        exit;
    }

    if ($status === 'rejected' && $rejectionRemarks === '') {
        header("Location: edit.php?id=$pendingId&error=Please provide a rejection remark.");
        exit;
    }

    $checkStmt = $conn->prepare("SELECT payment_status FROM payment_status WHERE id = ?");
    $checkStmt->bind_param("i", $pendingId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    if ($checkResult->num_rows === 0) {
        $checkStmt->close();
        header("Location: list.php?error=Pending payment not found.");
        exit;
    }
    $currentStatus = $checkResult->fetch_assoc()['payment_status'];
    $checkStmt->close();

    if ($status !== 'paid') {
        if ($status === 'rejected') {
            $updateStmt = $conn->prepare("
                UPDATE payment_status
                SET certificate_type = ?, purpose = ?, resident_fname = ?, resident_id = ?, payment_status = ?, rejection_remarks = ?, rejected_at = NOW(), amount = ?, bir_tax = ?
                WHERE id = ?
            ");
            $updateStmt->bind_param("ssssssddi", $certificateType, $purpose, $residentName, $residentId, $status, $rejectionRemarks, $totalAmount, $birTax, $pendingId);
        } else {
            $updateStmt = $conn->prepare("
                UPDATE payment_status
                SET certificate_type = ?, purpose = ?, resident_fname = ?, resident_id = ?, payment_status = ?, rejection_remarks = NULL, rejected_at = NULL, amount = ?, bir_tax = ?
                WHERE id = ?
            ");
            $updateStmt->bind_param("sssisddi", $certificateType, $purpose, $residentName, $residentId, $status, $totalAmount, $birTax, $pendingId);
        }
        $updateOk = $updateStmt->execute();
        $updateStmt->close();

        if ($updateOk) {
            $formattedStatus = ($status === 'to_review') ? 'To Review' : ucfirst($status);
            $remarksPass = ($status === 'rejected') ? $rejectionRemarks : null;
            update_services_system_payment_status($conn, $residentId, $certificateType, $purpose, $formattedStatus, $remarksPass);
            header("Location: list.php?updated=1");
        } else {
            header("Location: edit.php?id=$pendingId&error=Failed to update pending payment.");
        }
        exit;
    }

    if ($currentStatus === 'paid') {
        header("Location: list.php?error=Payment is already marked as paid.");
        exit;
    }

    $lastReceipt = $conn->query("SELECT receipt_no FROM payments ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextReceipt = $lastReceipt ? (intval($lastReceipt['receipt_no']) + 1) : 100001;
    $paymentDate = date('Y-m-d');
    $received_by = resolve_received_by($conn);
    $remarks = "Pending Status #" . $pendingId;

    $conn->begin_transaction();

    $updateStmt = $conn->prepare("
        UPDATE payment_status
        SET certificate_type = ?, purpose = ?, resident_fname = ?, resident_id = ?, payment_status = 'paid', amount = ?, bir_tax = ?, created_at = NOW()
        WHERE id = ?
    ");
    $updateStmt->bind_param("sssiddi", $certificateType, $purpose, $residentName, $residentId, $totalAmount, $birTax, $pendingId);
    $updateOk = $updateStmt->execute();
    $updateStmt->close();

    if ($updateOk) {
        update_services_system_payment_status($conn, $residentId, $certificateType, $purpose);
    }

    $insertOk = false;
    $cedulaOk = true;
    if ($updateOk) {
        $insertStmt = $conn->prepare("
                INSERT INTO payments (receipt_no, payment_date, payer_name, service_type, purpose, amount, bir_tax, remarks, received_by, resident_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
        $insertStmt->bind_param(
            "sssssddsii",
            $nextReceipt,
            $paymentDate,
            $residentName,
            $certificateType,
            $purpose,
            $totalAmount,
            $birTax,
            $remarks,
            $received_by,
            $residentId
        );
        $insertOk = $insertStmt->execute();
        $insertStmt->close();

        if ($insertOk && strcasecmp(trim($certificateType), 'Cedula') === 0) {
            $yearIssued = intval(date('Y', strtotime($paymentDate)));
            $cedulaStmt = $conn->prepare("
                UPDATE cedula
                SET issued_by = ?, issued_date = ?, year_issued = ?
                WHERE issued_by IS NULL 
                  AND (full_name = ? OR (resident_id = ? AND resident_id IS NOT NULL AND resident_id != 0))
                ORDER BY id DESC
                LIMIT 1
            ");
            $cedulaStmt->bind_param("isisi", $received_by, $paymentDate, $yearIssued, $residentName, $residentId);
            $cedulaOk = $cedulaStmt->execute();
            $cedulaStmt->close();
        }
    }

    if ($updateOk && $insertOk && $cedulaOk) {
        $conn->commit();
        header("Location: list.php?paid=1");
    } else {
        $conn->rollback();
        header("Location: edit.php?id=$pendingId&error=Failed to mark as paid.");
    }

    exit;
}

header("Location: list.php");
exit;






