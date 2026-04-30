<?php
/**
 * promote_president.php
 * Promotes or demotes a resident to/from Purok President.
 * Only superadmins (admin, secretary) can use this.
 */
session_start();
include("connection.php");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: index.php"); exit();
}

$is_superadmin = ($_SESSION['user_type'] === 'admin') || (!empty($_SESSION['is_superadmin']));
if (!$is_superadmin) {
    $_SESSION['error'] = "You do not have permission to manage Purok Presidents.";
    header("Location: resident.php"); exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: resident.php"); exit();
}

$id     = intval($_POST['id'] ?? 0);
$action = trim($_POST['action'] ?? '');

if ($id <= 0 || !in_array($action, ['promote', 'demote'])) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: resident.php"); exit();
}

$new_val = ($action === 'promote') ? 1 : 0;

// Fetch resident info for logging
$stmt = $conn->prepare("SELECT first_name, surname, purok FROM residents WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) {
    $_SESSION['error'] = "Resident not found.";
    header("Location: resident.php"); exit();
}

// If promoting, check if purok already has a president
if ($action === 'promote') {
    $chk = $conn->prepare("SELECT id, first_name, surname FROM residents WHERE purok = ? AND is_purok_president = 1 AND is_deleted = 0 AND id != ? LIMIT 1");
    $chk->bind_param('si', $res['purok'], $id);
    $chk->execute();
    $existing = $chk->get_result()->fetch_assoc();
    $chk->close();
    if ($existing) {
        $_SESSION['error'] = "Purok '{$res['purok']}' already has a president: {$existing['first_name']} {$existing['surname']}. Demote them first.";
        header("Location: resident.php"); exit();
    }
}

$stmt = $conn->prepare("UPDATE residents SET is_purok_president = ? WHERE id = ?");
$stmt->bind_param('ii', $new_val, $id);
$stmt->execute();
$stmt->close();

$res_name = $res['first_name'] . ' ' . $res['surname'];
$action_label = ($action === 'promote') ? 'Promoted' : 'Demoted';
$_SESSION['success'] = "$action_label $res_name as Purok President of {$res['purok']}.";

// Log the action
$log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, username, full_name, action, details, ip_address, login_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'offline')");
$log_utype   = $_SESSION['user_type'] ?? 'admin';
$log_uname   = $_SESSION['username'] ?? 'Admin';
$log_fname   = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Admin';
$log_action  = ($action === 'promote') ? 'promote_purok_president' : 'demote_purok_president';
$log_details = "$action_label $res_name (ID: $id) as Purok President of {$res['purok']}";
$log_ip      = $_SERVER['REMOTE_ADDR'] ?? '';
$log_stmt->bind_param('issssss', $_SESSION['user_id'], $log_utype, $log_uname, $log_fname, $log_action, $log_details, $log_ip);
$log_stmt->execute();
$log_stmt->close();

$conn->close();
header("Location: resident.php");
exit();
?>
