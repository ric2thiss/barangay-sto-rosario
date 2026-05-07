<?php
/**
 * staff_actions.php — Handles deactivate, activate, reset_password
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php");
    exit();
}
include("connection.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: staff_management.php");
    exit();
}

$action = trim($_POST['action'] ?? '');
$id = intval($_POST['id'] ?? 0);

if (!in_array($action, ['deactivate', 'activate', 'reset_password']) || $id <= 0) {
    $_SESSION['staff_error'] = 'Invalid action.';
    header("Location: staff_management.php");
    exit();
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$admin_user = $conn->real_escape_string($_SESSION['username'] ?? 'Admin');
$admin_id = (int) $_SESSION['user_id'];

switch ($action) {
    case 'deactivate':
        $stmt = $conn->prepare("UPDATE staff SET status='Inactive' WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['staff_success'] = 'Staff account deactivated.';
        } else {
            $_SESSION['staff_error'] = 'Failed to deactivate.';
        }
        $stmt->close();

        $log = $conn->prepare("INSERT INTO staff_audit_log (staff_id, staff_username, action, target_type, target_id, details, ip_address) VALUES (?, ?, 'deactivate_staff', 'staff', ?, ?, ?)");
        $detail = json_encode(['action' => 'deactivated']);
        $log->bind_param("isiss", $admin_id, $admin_user, $id, $detail, $ip);
        $log->execute();
        $log->close();
        break;

    case 'activate':
        $stmt = $conn->prepare("UPDATE staff SET status='Active' WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['staff_success'] = 'Staff account reactivated.';
        } else {
            $_SESSION['staff_error'] = 'Failed to activate.';
        }
        $stmt->close();

        $log = $conn->prepare("INSERT INTO staff_audit_log (staff_id, staff_username, action, target_type, target_id, details, ip_address) VALUES (?, ?, 'activate_staff', 'staff', ?, ?, ?)");
        $detail = json_encode(['action' => 'activated']);
        $log->bind_param("isiss", $admin_id, $admin_user, $id, $detail, $ip);
        $log->execute();
        $log->close();
        break;

    case 'reset_password':
        $default_pw = password_hash('Staff@123', PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $conn->prepare("UPDATE staff SET password=? WHERE id=?");
        $stmt->bind_param("si", $default_pw, $id);
        if ($stmt->execute()) {
            $_SESSION['staff_success'] = 'Password reset to default (Staff@123).';
        } else {
            $_SESSION['staff_error'] = 'Failed to reset password.';
        }
        $stmt->close();

        $log = $conn->prepare("INSERT INTO staff_audit_log (staff_id, staff_username, action, target_type, target_id, details, ip_address) VALUES (?, ?, 'reset_password', 'staff', ?, ?, ?)");
        $detail = json_encode(['action' => 'password_reset']);
        $log->bind_param("isiss", $admin_id, $admin_user, $id, $detail, $ip);
        $log->execute();
        $log->close();
        break;
}

$conn->close();
header("Location: staff_management.php");
exit();
?>