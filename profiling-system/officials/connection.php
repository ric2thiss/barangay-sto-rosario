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