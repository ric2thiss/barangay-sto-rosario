<?php
/**
 * API Endpoint: Get recent visitor logs
 * GET /api/visitors/recent-logs.php?limit={n}
 */
require_once __DIR__ . "/../../bootstrap.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

try {
    $limit = isset($_GET['limit']) ? max(1, min((int) $_GET['limit'], 50)) : 10;

    $db = (new Database())->connect();

    $stmt = $db->prepare("
        SELECT 
            id,
            resident_id,
            first_name,
            middle_name,
            last_name,
            birthdate,
            address,
            purpose,
            is_resident,
            had_booking,
            booking_id,
            created_at
        FROM visitor_logs
        WHERE deleted_at IS NULL
        ORDER BY created_at DESC
        LIMIT :lim
    ");
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $origin = $scheme . '://' . $host;

    foreach ($logs as &$log) {
        $log['photo_url'] = null;
        if (!empty($log['resident_id'])) {
            try {
                $imgStmt = $db->prepare("SELECT image_path FROM `" . PROFILING_DB_NAME . "`.`residents` WHERE id = ? LIMIT 1");
                $imgStmt->execute([$log['resident_id']]);
                $imgRow = $imgStmt->fetch(PDO::FETCH_ASSOC);
                if ($imgRow && !empty($imgRow['image_path'])) {
                    $log['photo_url'] = $origin . '/profiling-system/officials/uploads/residents/' . rawurlencode($imgRow['image_path']);
                }
            } catch (Exception $e) {
                // Photo lookup is non-critical
            }
        }
        $log['full_name'] = trim(($log['first_name'] ?? '') . ' ' . ($log['middle_name'] ?? '') . ' ' . ($log['last_name'] ?? ''));
        $log['full_name'] = preg_replace('/\s+/', ' ', $log['full_name']);
    }
    unset($log);

    echo json_encode([
        'success' => true,
        'logs' => $logs,
        'count' => count($logs)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch visitor logs',
        'message' => $e->getMessage()
    ]);
}
