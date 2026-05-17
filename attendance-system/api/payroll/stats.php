<?php
/**
 * Payroll Statistics API
 * 
 * Returns payroll summary statistics for the dashboard
 * GET /api/payroll/stats.php?period=last_month|current_month|last_payrun
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../auth/helpers.php";

header('Content-Type: application/json');
http_response_code(410);
echo json_encode([
    "success" => false,
    "message" => "Payroll is disabled. attendance-system no longer contains employees/positions/departments; employee data is sourced from profiling-system."
]);
exit;
// (disabled)
// *** End of File
