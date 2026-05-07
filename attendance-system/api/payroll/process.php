<?php
/**
 * Process Payrun API
 * 
 * Creates a new payrun based on attendance data and employee salaries
 * POST /api/payroll/process.php
 * Body: { "period_start": "2024-11-01", "period_end": "2024-11-15" }
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
