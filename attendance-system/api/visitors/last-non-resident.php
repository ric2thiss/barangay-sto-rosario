<?php
/**
 * API: Latest non-resident visitor log row (for pre-filling manual form)
 * GET /api/visitors/last-non-resident.php
 */
require_once __DIR__ . "/../../bootstrap.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Method not allowed"]);
    exit;
}

try {
    $db = (new Database())->connect();

    $hasDeletedAt = false;
    try {
        $chk = $db->query("SHOW COLUMNS FROM `visitor_logs` LIKE 'deleted_at'");
        $hasDeletedAt = $chk && $chk->rowCount() > 0;
    } catch (Exception $e) {
        $hasDeletedAt = false;
    }

    $delClause = $hasDeletedAt ? ' AND (deleted_at IS NULL) ' : '';

    $sql = "
        SELECT first_name, middle_name, last_name, birthdate, address
        FROM `visitor_logs`
        WHERE is_resident = 0
        {$delClause}
        ORDER BY created_at DESC
        LIMIT 1
    ";
    $row = $db->query($sql)->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => true, 'visitor' => null]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'visitor' => [
            'first_name' => $row['first_name'] ?? '',
            'middle_name' => $row['middle_name'] ?? '',
            'last_name' => $row['last_name'] ?? '',
            'birthdate' => $row['birthdate'] ?? null,
            'address' => $row['address'] ?? '',
        ],
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load last visitor',
        'message' => $e->getMessage()
    ]);
}
