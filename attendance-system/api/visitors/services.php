<?php
/**
 * API Endpoint: Get available services for visitor logging
 * GET /api/visitors/services.php
 *
 * Loads certificate types from barangay_services2.certificate_types and appends Blotter.
 */
require_once __DIR__ . "/../../bootstrap.php";

header("Content-Type: application/json");

$method = $_SERVER["REQUEST_METHOD"];

if ($method !== "GET") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

$dbName = defined('BARANGAY_SERVICES2_DB_NAME') ? BARANGAY_SERVICES2_DB_NAME : 'barangay_services2';

// When is_resident=0, only non-resident-allowed services are returned (Blotter only).
// Certificate services require proof of residency and are not available for non-residents.
$isResident = isset($_GET['is_resident']) ? (int) $_GET['is_resident'] : 1;

try {
    $db = (new Database())->connect();
    $q = '`' . str_replace('`', '', $dbName) . '`';

    $services = [];

    // Certificate services are only available for residents
    if ($isResident) {
        $sql = "
            SELECT certificate_type_id, certificate_name, price
            FROM {$q}.`certificate_types`
            ORDER BY certificate_name ASC
        ";
        $stmt = $db->query($sql);
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        foreach ($rows as $row) {
            $services[] = [
                'service_id' => (int) $row['certificate_type_id'],
                'service_name' => $row['certificate_name'],
                'description' => '',
                'duration' => '—',
                'fee' => isset($row['price']) ? (float) $row['price'] : 0.0,
            ];
        }
    }

    // Non-resident services logic
    if (!$isResident) {
        $sqlNonRes = "SELECT service_id, service_name FROM non_resident_services WHERE is_allowed = 1";
        $stmtNonRes = $db->query($sqlNonRes);
        $nonResRows = $stmtNonRes ? $stmtNonRes->fetchAll(PDO::FETCH_ASSOC) : [];

        foreach ($nonResRows as $row) {
            $services[] = [
                'service_id' => $row['service_id'],
                'service_name' => $row['service_name'],
                'description' => '',
                'duration' => '—',
                'fee' => 0.0,
            ];
        }
    } else {
        // Blotter is available for both residents and non-residents
        $services[] = [
            'service_id' => 'blotter',
            'service_name' => 'Blotter',
            'description' => 'Blotter / complaint filing',
            'duration' => '—',
            'fee' => 0.0,
        ];
    }

    echo json_encode([
        'success' => true,
        'services' => $services,
        'count' => count($services)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch services',
        'message' => $e->getMessage()
    ]);
}
