<?php
/**
 * Password Verification API Endpoint
 * 
 * Used for re-authentication before accessing sensitive areas like Payroll Management.
 * Requires existing session authentication and verifies password server-side.
 * 
 * POST /api/auth/verify-password.php
 * Body: { "password": "user_password" }
 * 
 * Response: { "success": true/false, "message": "..." }
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../auth/helpers.php";

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed. Use POST."
    ]);
    exit;
}

// Require existing authentication
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Not authenticated. Please login first."
    ]);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$password = $input['password'] ?? '';

// Validate password input
if (empty($password)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Password is required."
    ]);
    exit;
}

// Get current authenticated user
$user = currentUser();
if (!$user || !isset($user['id'])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "User session not found."
    ]);
    exit;
}

try {
    // Get admin record from database
    $db = (new Database())->connect();
    $adminRepository = new AdminRepository($db);
    $admin = $adminRepository->findById($user['id']);

    if (!$admin) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Admin account not found."
        ]);
        exit;
    }

    // Convert object to array if needed
    if (is_object($admin)) {
        $admin = json_decode(json_encode($admin), true);
    }

    // Verify password
    if (!$adminRepository->verifyPassword($password, $admin['password'])) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Invalid password."
        ]);
        exit;
    }

    // Check if admin is still active
    if (isset($admin['is_active']) && !$admin['is_active']) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "message" => "Account is deactivated."
        ]);
        exit;
    }

    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Set password confirmation in session
    $_SESSION['payroll_password_confirmed'] = true;
    $_SESSION['payroll_confirmed_at'] = time();
    $_SESSION['payroll_last_activity'] = time(); // Track last activity for idle timeout

    echo json_encode([
        "success" => true,
        "message" => "Password verified successfully."
    ]);

} catch (Exception $e) {
    error_log("Password verification error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "An error occurred during password verification."
    ]);
}
