<?php
/**
 * Comprehensive test: verify all 3 dashboard API endpoints return valid JSON.
 */
session_start();
$_SESSION['is_authenticated'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';
$_SESSION['admin_full_name'] = 'Admin User';
$_SESSION['admin_role'] = 'Administrator';

$endpoints = [
    'attendance-modal' => ['filter' => 'month', 'card' => 'present'],
    'extra-charts'     => ['filter' => 'month'],
    'visitor-modal'    => ['filter' => 'month', 'card' => 'total'],
];

$allPassed = true;

foreach ($endpoints as $name => $params) {
    $_GET = $params;
    
    ob_start();
    try {
        // Reset state for each include
        header_remove();
        include __DIR__ . "/../api/dashboard/{$name}.php";
    } catch (Throwable $e) {
        echo json_encode(['_php_error' => $e->getMessage()]);
    }
    $output = ob_get_clean();
    
    $data = json_decode($output, true);
    $jsonValid = json_last_error() === JSON_ERROR_NONE;
    $success = $jsonValid && !empty($data['success']);
    
    $status = $success ? 'PASS' : 'FAIL';
    if (!$success) $allPassed = false;
    
    echo "[{$status}] {$name}.php\n";
    if (!$jsonValid) {
        echo "  JSON Error: " . json_last_error_msg() . "\n";
        echo "  Raw output (first 300 chars): " . substr($output, 0, 300) . "\n";
    } elseif (!$success) {
        echo "  Response: " . json_encode($data) . "\n";
    } else {
        // Show key metrics
        if (isset($data['row_count'])) echo "  Rows: {$data['row_count']}\n";
        if (isset($data['total_rows'])) echo "  Total rows: {$data['total_rows']}\n";
        if (isset($data['top_employees'])) echo "  Top employees: " . count($data['top_employees']) . "\n";
        if (isset($data['charts'])) echo "  Charts: " . implode(', ', array_keys($data['charts'])) . "\n";
    }
    echo "\n";
}

// Verify schema column cache
echo "--- Schema Column Cache ---\n";
echo "attendances.deleted_at: " . (SchemaColumnCache::attendancesHasDeletedAt() ? 'EXISTS' : 'MISSING') . "\n";
echo "visitor_logs.deleted_at: " . (SchemaColumnCache::visitorLogsHasDeletedAt() ? 'EXISTS' : 'MISSING') . "\n";
echo "activities.deleted_at: " . (SchemaColumnCache::activitiesHasDeletedAt() ? 'EXISTS' : 'MISSING') . "\n";
echo "attendance_windows.deleted_at: " . (SchemaColumnCache::attendanceWindowsHasDeletedAt() ? 'EXISTS' : 'MISSING') . "\n";

echo "\n" . ($allPassed ? "ALL TESTS PASSED" : "SOME TESTS FAILED") . "\n";
