<?php
/**
 * check_username.php  (resident/ folder)
 * ─────────────────────────────────────────────────────────────────────────
 * AJAX endpoint called by the registration form to check username availability.
 * Checks BOTH residents AND pending_registrations tables.
 * Returns: { "available": true } or { "available": false }
 */
header('Content-Type: application/json');
include("../officials/connection.php");

$username = isset($_POST['username']) ? trim($_POST['username']) : '';

if (empty($username) || strlen($username) < 4 || strlen($username) > 20 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    echo json_encode(['available' => false]);
    exit();
}

$username_safe = $conn->real_escape_string($username);

// Check in residents (approved accounts)
$r1 = $conn->query("SELECT id FROM residents WHERE username='$username_safe' LIMIT 1");
if ($r1 && $r1->num_rows > 0) {
    echo json_encode(['available' => false]);
    $conn->close(); exit();
}

// Check in pending_registrations (pending + rejected accounts)
$r2 = $conn->query("SELECT id FROM pending_registrations WHERE username='$username_safe' LIMIT 1");
if ($r2 && $r2->num_rows > 0) {
    echo json_encode(['available' => false]);
    $conn->close(); exit();
}

$conn->close();
echo json_encode(['available' => true]);
?>