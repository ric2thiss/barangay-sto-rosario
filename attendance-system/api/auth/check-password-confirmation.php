<?php
/**
 * Check Password Confirmation Status
 * 
 * Checks if password confirmation is still valid (not expired due to idle time).
 * 
 * GET /api/auth/check-password-confirmation.php
 * 
 * Response: { "success": true, "confirmed": true/false }
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../auth/helpers.php";

header('Content-Type: application/json');

// Require existing authentication
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "confirmed" => false,
        "message" => "Not authenticated."
    ]);
    exit;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if password was confirmed
$confirmed = isset($_SESSION['payroll_password_confirmed']) && $_SESSION['payroll_password_confirmed'] === true;

if ($confirmed) {
    // Check if confirmation has expired due to idle time (4 minutes = 240 seconds)
    $idleTimeout = 240; // 4 minutes in seconds
    $lastActivity = $_SESSION['payroll_last_activity'] ?? $_SESSION['payroll_confirmed_at'] ?? 0;
    $timeSinceActivity = time() - $lastActivity;

    if ($timeSinceActivity > $idleTimeout) {
        // Expired due to idle time
        $confirmed = false;
        unset($_SESSION['payroll_password_confirmed']);
        unset($_SESSION['payroll_confirmed_at']);
        unset($_SESSION['payroll_last_activity']);
    }
}

echo json_encode([
    "success" => true,
    "confirmed" => $confirmed
]);
