<?php
/**
 * Test a single dashboard endpoint via CLI.
 * Usage: php test_single.php <endpoint> [filter] [card]
 */
session_start();
$_SESSION['is_authenticated'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';
$_SESSION['admin_full_name'] = 'Admin User';
$_SESSION['admin_role'] = 'Administrator';

$endpoint = $argv[1] ?? 'attendance-modal';
$_GET['filter'] = $argv[2] ?? 'month';
if (isset($argv[3])) $_GET['card'] = $argv[3];

ob_start();
include __DIR__ . "/../api/dashboard/{$endpoint}.php";
$output = ob_get_clean();

$data = json_decode($output, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    fwrite(STDERR, "FAIL: Invalid JSON - " . json_last_error_msg() . "\n");
    fwrite(STDERR, "Raw: " . substr($output, 0, 500) . "\n");
    exit(1);
}

if (empty($data['success'])) {
    fwrite(STDERR, "FAIL: success=false - " . ($data['error'] ?? 'unknown') . "\n");
    exit(1);
}

echo "PASS\n";
exit(0);
