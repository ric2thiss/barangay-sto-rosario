<?php
/**
 * Update Last Activity Timestamp
 * 
 * Updates the last activity timestamp for password confirmation session.
 * Called periodically to track user activity and prevent idle timeout.
 * 
 * POST /api/auth/update-activity.php
 * 
 * Response: { "success": true }
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../auth/helpers.php";

header('Content-Type: application/json');

// Require existing authentication
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Not authenticated."
    ]);
    exit;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Update last activity timestamp
if (isset($_SESSION['payroll_password_confirmed']) && $_SESSION['payroll_password_confirmed'] === true) {
    $_SESSION['payroll_last_activity'] = time();
    echo json_encode([
        "success" => true
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Password not confirmed."
    ]);
}
