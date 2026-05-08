<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

// Update logout time in login_logs if session has login_log_id
if (isset($_SESSION['login_log_id']) && isset($conn)) {
    if (isset($_SESSION['user_id'])) {
        logAdminActivity($conn, $_SESSION['user_id'], 'Logout', 'Admin logged out securely.');
    }

    $log_id = $_SESSION['login_log_id'];
    $updateStmt = $conn->prepare("UPDATE login_logs SET time_out = NOW() WHERE id = ?");
    if ($updateStmt) {
        $updateStmt->bind_param("i", $log_id);
        $updateStmt->execute();
        $updateStmt->close();
    }
}

session_destroy();
header('Location: login.php');
exit();
?>