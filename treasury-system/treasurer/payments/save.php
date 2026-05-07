<?php
include "../../config/database.php";
include "../../config/session.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update' && !empty($_POST['id'])) {
        $paymentId = intval($_POST['id']);
        $payer_name = $_POST['payer_name'];
        $service_type = $_POST['service_type'];
        $purpose = $_POST['purpose'];
        $amount = $_POST['amount'];
        $bir_tax = $_POST['bir_tax'];
        $receipt_no = $_POST['receipt_no'];
        $payment_date = $_POST['payment_date'];
        $remarks = $_POST['remarks'] ?? '';

        $stmt = $conn->prepare("
            UPDATE payments
            SET receipt_no = ?, payment_date = ?, payer_name = ?, service_type = ?, purpose = ?, amount = ?, bir_tax = ?, remarks = ?
            WHERE id = ?
        ");

        $stmt->bind_param("sssssddsi", $receipt_no, $payment_date, $payer_name, $service_type, $purpose, $amount, $bir_tax, $remarks, $paymentId);

        if ($stmt->execute()) {
            header("Location: list.php?updated=1");
        } else {
            header("Location: edit.php?id=" . $paymentId . "&error=Failed to update payment");
        }
        exit;
    }

    $payer_name = $_POST['payer_name'];
    $service_type = $_POST['service_type'];
    $purpose = $_POST['purpose'];
    $amount = $_POST['amount'];
    $bir_tax = $_POST['bir_tax'];
    $receipt_no = $_POST['receipt_no'];
    $payment_date = $_POST['payment_date'];
    $remarks = $_POST['remarks'] ?? '';

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

    $stmt = $conn->prepare("
        INSERT INTO payments (payer_name, service_type, purpose, amount, bir_tax, receipt_no, payment_date, remarks, received_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->bind_param("sssddsssi", $payer_name, $service_type, $purpose, $amount, $bir_tax, $receipt_no, $payment_date, $remarks, $received_by);
    
    if ($stmt->execute()) {
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





