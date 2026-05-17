<?php

// ── connection.php ────────────────────────────────────────────────────────
// Never expose errors to the browser
ini_set('display_errors', 0);
error_reporting(0);

define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // Change in production
define('DB_PASS', '');          // Change in production — never leave blank on live server
define('DB_NAME', 'profiling-system');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    // Log internally, never show details to user
    error_log('[DB] Connection failed: ' . $e->getMessage());
    http_response_code(503);
    die(json_encode(['success' => false, 'message' => 'Service temporarily unavailable.']));
}

// ── Live Privilege Refresh for Officials ──────────────────────────────────
// Automatically enforces privilege limits instantly across all pages.
if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'official' && isset($_SESSION['user_id'])) {
    try {
        $stmt_priv = $conn->prepare("SELECT can_view_residents, can_add_resident, can_edit_resident, can_approve, can_delete, can_export, can_manage_staff, can_view_logs FROM barangay_official WHERE id = ?");
        if ($stmt_priv) {
            $stmt_priv->bind_param('i', $_SESSION['user_id']);
            $stmt_priv->execute();
            $res_priv = $stmt_priv->get_result();
            if ($res_priv && $res_priv->num_rows > 0) {
                $row_priv = $res_priv->fetch_assoc();
                $_SESSION['can_view_residents'] = (int)$row_priv['can_view_residents'];
                $_SESSION['can_add_resident'] = (int)$row_priv['can_add_resident'];
                $_SESSION['can_edit_resident'] = (int)$row_priv['can_edit_resident'];
                $_SESSION['can_approve'] = (int)$row_priv['can_approve'];
                $_SESSION['can_delete'] = (int)$row_priv['can_delete'];
                $_SESSION['can_export'] = (int)$row_priv['can_export'];
                $_SESSION['can_manage_staff'] = (int)$row_priv['can_manage_staff'];
                $_SESSION['can_view_logs'] = (int)$row_priv['can_view_logs'];
            }
            $stmt_priv->close();
        }
    } catch (Exception $e) {
        error_log('[DB] Privilege refresh failed: ' . $e->getMessage());
    }
}