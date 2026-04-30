<?php
require_once __DIR__ . '/_require_settings_admin.php';

header('Content-Type: application/json; charset=utf-8');

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 25)));

try {
    $repo = new LoginLogRepository((new Database())->connect());
    $data = $repo->getPaged($page, $perPage);
    $rows = [];
    foreach ($data['rows'] as $r) {
        if (is_object($r)) {
            $r = json_decode(json_encode($r), true);
        }
        $rows[] = $r;
    }
    echo json_encode([
        'success' => true,
        'rows' => $rows,
        'total' => $data['total'],
        'page' => $page,
        'per_page' => $perPage,
    ]);
} catch (Throwable $e) {
    error_log('login-logs: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load login logs']);
}
