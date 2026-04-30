<?php
/**
 * restore_resident.php
 * Restores a soft-deleted resident back to active status.
 */
session_start();
include("connection.php");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: index.php"); exit();
}

$allowed_types = ['admin', 'staff', 'official', 'resident'];
if (!in_array($_SESSION['user_type'], $allowed_types)) {
    header("Location: index.php"); exit();
}

// Purok Presidents can only restore residents in their own purok
$is_purok_president = (($_SESSION['staff_position'] ?? '') === 'Purok President');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header("Location: deleted_residents.php"); exit();
}

$id = intval($_POST['id']);

// Fetch resident info for validation and logging
$stmt = $conn->prepare("SELECT first_name, surname, purok, image_path FROM residents WHERE id = ? AND is_deleted = 1 LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    $_SESSION['error'] = "Resident not found or already active.";
    header("Location: deleted_residents.php"); exit();
}

// Purok President enforcement: cannot restore residents outside their purok
if ($is_purok_president && $row['purok'] !== ($_SESSION['purok'] ?? '')) {
    $_SESSION['error'] = "You can only restore residents within your purok ({$_SESSION['purok']}).";
    header("Location: deleted_residents.php"); exit();
}

// Restore the resident
$stmt = $conn->prepare("UPDATE residents SET is_deleted = 0, deleted_at = NULL WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    $res_name = $row['first_name'] . ' ' . $row['surname'];
    $_SESSION['success'] = "Resident $res_name has been restored successfully.";

    // Log the restoration
    $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, username, full_name, action, details, ip_address, login_at, status) VALUES (?, ?, ?, ?, 'restore_resident', ?, ?, NOW(), 'offline')");
    $log_utype   = $_SESSION['user_type'] ?? 'admin';
    $log_uname   = $_SESSION['username'] ?? 'Admin';
    $log_fname   = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Admin';
    $log_details = "Restored resident: $res_name (ID: $id)";
    $log_ip      = $_SERVER['REMOTE_ADDR'] ?? '';
    $log_stmt->bind_param('isssss', $_SESSION['user_id'], $log_utype, $log_uname, $log_fname, $log_details, $log_ip);
    $log_stmt->execute();
    $log_stmt->close();
} else {
    $_SESSION['error'] = "Failed to restore resident: " . $conn->error;
}

$stmt->close();
$conn->close();
header("Location: deleted_residents.php");
exit();
?>
