<?php
include "../../config/database.php";
include "../../config/session.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update' && !empty($_POST['id'])) {
        $paymentId = intval($_POST['id']);
        $payer_name = trim($_POST['payer_name']);
        $service_type = trim($_POST['service_type']);
        $purpose = trim($_POST['purpose']);
        $baseAmount = floatval($_POST['amount']);
        $bir_tax = floatval($_POST['bir_tax']);
        $totalAmount = $baseAmount + $bir_tax;
        $receipt_no = trim($_POST['receipt_no']);
        $payment_date = $_POST['payment_date'];
        $remarks = trim($_POST['remarks'] ?? '');

        $resident_id = null;
        if (isset($_POST['payer_id']) && ($_POST['payer_source'] === 'Resident' || $_POST['payer_source'] === 'Official')) {
            $resident_id = intval($_POST['payer_id']);
        }

        $stmt = $conn->prepare("
            UPDATE payments
            SET receipt_no = ?, payment_date = ?, payer_name = ?, service_type = ?, purpose = ?, amount = ?, bir_tax = ?, remarks = ?, resident_id = ?
            WHERE id = ?
        ");

        $stmt->bind_param("sssssddsii", $receipt_no, $payment_date, $payer_name, $service_type, $purpose, $totalAmount, $bir_tax, $remarks, $resident_id, $paymentId);

        if ($stmt->execute()) {
            header("Location: list.php?updated=1");
        } else {
            header("Location: edit.php?id=" . $paymentId . "&error=Failed to update payment");
        }
        exit;
    }

    $payer_name = trim($_POST['payer_name']);
    $service_type = trim($_POST['service_type']);
    $purpose = trim($_POST['purpose']);
    $baseAmount = floatval($_POST['amount']);
    $bir_tax = floatval($_POST['bir_tax']);
    $totalAmount = $baseAmount + $bir_tax;
    $receipt_no = trim($_POST['receipt_no']);
    $payment_date = $_POST['payment_date'];
    $remarks = trim($_POST['remarks'] ?? '');

    // Safely resolve received_by — use NULL if session user doesn't exist in users table
    $received_by = null;
    if (!empty($_SESSION['user_id'])) {
        $userCheck = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $userCheck->bind_param("i", $_SESSION['user_id']);
        $userCheck->execute();
        $userCheck->store_result();
        if ($userCheck->num_rows > 0) {
            $received_by = intval($_SESSION['user_id']);
        }
        $userCheck->close();
    }

    $resident_id = null;
    if (isset($_POST['payer_id']) && ($_POST['payer_source'] === 'Resident' || $_POST['payer_source'] === 'Official')) {
        $resident_id = intval($_POST['payer_id']);
    }

    $stmt = $conn->prepare("
        INSERT INTO payments (payer_name, service_type, purpose, amount, bir_tax, receipt_no, payment_date, remarks, received_by, created_at, resident_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
    ");

    $stmt->bind_param("sssddssssi", $payer_name, $service_type, $purpose, $totalAmount, $bir_tax, $receipt_no, $payment_date, $remarks, $received_by, $resident_id);

    if ($stmt->execute()) {
        // Sync to services-system if it's a resident and has a certificate type
        if (
            isset($_POST['payer_id'], $_POST['payer_source'], $_POST['certificate_type_id']) &&
            $_POST['payer_source'] === 'Resident' &&
            !empty($_POST['certificate_type_id'])
        ) {

            $resident_id = intval($_POST['payer_id']);
            $cert_type_id = intval($_POST['certificate_type_id']);

            // Insert into services-system.certificate_requests
            $syncStmt = $conn->prepare("
                INSERT INTO " . DB_SERVICES . ".certificate_requests 
                (resident_id, certificate_type_id, purpose, status, payment_status, amount_due, bir_tax, date_requested, requested_by, created_at, updated_at)
                VALUES (?, ?, ?, 'Approved', 'Paid', ?, ?, ?, 'Treasurer (Walk-in)', NOW(), NOW())
            ");

            // Note: $baseAmount and $bir_tax are already calculated above
            $syncStmt->bind_param("iisdds", $resident_id, $cert_type_id, $purpose, $baseAmount, $bir_tax, $payment_date);
            $syncStmt->execute();
            $syncStmt->close();
        }

        header("Location: list.php?success=1");
    } else {
        header("Location: add.php?error=Failed to save payment");
    }
    exit;
}

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM payments WHERE id = $id");
    header("Location: list.php?deleted=1");
    exit;
}

header("Location: list.php");
exit;





