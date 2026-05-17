<?php
// Mock session for isAuthenticated()
session_start();
$_SESSION['is_authenticated'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';
$_SESSION['admin_full_name'] = 'Admin User';
$_SESSION['admin_role'] = 'Administrator';

// Set GET params
$_GET['filter'] = 'month';
$_GET['card'] = 'present';

// Buffering output
ob_start();
include __DIR__ . '/../api/dashboard/attendance-modal.php';
$output = ob_get_clean();

echo "API Output:\n";
echo $output;

$data = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "\n\nJSON is valid. Success: " . ($data['success'] ? 'Yes' : 'No');
} else {
    echo "\n\nJSON is INVALID: " . json_last_error_msg();
}
