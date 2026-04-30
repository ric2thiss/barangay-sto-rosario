<?php
/**
 * Payruns List API
 * 
 * Returns list of payruns for the payroll history table
 * GET /api/payroll/payruns.php?limit=10
 */

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../auth/helpers.php";

header('Content-Type: application/json');

// Require authentication
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Not authenticated"]);
    exit;
}

try {
    $db = (new Database())->connect();
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    $stmt = $db->prepare("
        SELECT 
            payrun_id,
            payrun_date,
            period_start,
            period_end,
            status,
            total_gross_pay,
            total_deductions,
            total_net_pay,
            employees_count,
            created_at
        FROM payruns
        ORDER BY payrun_date DESC, created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    $payruns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates and amounts
    foreach ($payruns as &$payrun) {
        $payrun['payrun_date_formatted'] = date('M d, Y', strtotime($payrun['payrun_date']));
        $payrun['period_covered'] = date('M d', strtotime($payrun['period_start'])) . ' - ' . date('M d, Y', strtotime($payrun['period_end']));
        $payrun['total_gross_pay'] = (float)$payrun['total_gross_pay'];
        $payrun['total_deductions'] = (float)$payrun['total_deductions'];
        $payrun['total_net_pay'] = (float)$payrun['total_net_pay'];
        $payrun['employees_count'] = (int)$payrun['employees_count'];
    }
    
    echo json_encode([
        "success" => true,
        "data" => $payruns
    ]);
    
} catch (Exception $e) {
    error_log("Payruns list error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error fetching payruns"
    ]);
}
