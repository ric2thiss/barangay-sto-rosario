<?php
/**
 * API Endpoint: Visitors API Health Check
 * GET /api/visitors/health.php
 *
 * Confirms:
 * - profiling-system residents table is reachable (read-only source of truth)
 * - attendance-system visitor_logs table is reachable (write target)
 */

// Start output buffering and suppress any accidental output
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Visitors Health API Error [{$errno}]: {$errstr} in {$errfile} on line {$errline}");
    return true; // suppress output
});

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../auth/helpers.php";

// Clear any output from bootstrap
ob_get_clean();

header("Content-Type: application/json");

// Require authentication (same as other visitor analytics endpoints)
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit;
}

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "GET") {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Method not allowed"]);
    exit;
}

$profilingDbName = defined("PROFILING_DB_NAME") ? PROFILING_DB_NAME : "profiling-system";

$result = [
    "success" => true,
    "profiling_db_name" => $profilingDbName,
    "checks" => [
        "profiling_residents_readable" => false,
        "visitor_logs_readable" => false,
    ],
    "details" => [],
];

try {
    $pdo = (new Database())->connect();

    // 1) Check profiling-system residents table
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `{$profilingDbName}`.`residents`");
        $count = (int) $stmt->fetchColumn();
        $result["checks"]["profiling_residents_readable"] = true;
        $result["details"]["profiling_residents_count"] = $count;
    } catch (Exception $e) {
        $result["details"]["profiling_residents_error"] = $e->getMessage();
    }

    // 2) Check attendance-system visitor_logs table
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `visitor_logs`");
        $count = (int) $stmt->fetchColumn();
        $result["checks"]["visitor_logs_readable"] = true;
        $result["details"]["visitor_logs_count"] = $count;
    } catch (Exception $e) {
        $result["details"]["visitor_logs_error"] = $e->getMessage();
    }

    // Overall status: fail if either core check fails
    if (!$result["checks"]["profiling_residents_readable"] || !$result["checks"]["visitor_logs_readable"]) {
        $result["success"] = false;
        http_response_code(500);
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Server error",
        "message" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

