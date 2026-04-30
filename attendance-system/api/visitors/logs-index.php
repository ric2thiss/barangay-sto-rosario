<?php
/**
 * Authenticated visitor logs / Visitor Reports list (paginated, READ-ONLY SELECT).
 * GET /api/visitors/logs-index.php?date_from=Y-m-d&date_to=Y-m-d&limit=&offset=
 * Optional: search, purpose, gender, purok, sort_dir=ASC|DESC
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../auth/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

requireAuth();

try {
    $dateFrom = isset($_GET['date_from']) ? trim((string) $_GET['date_from']) : '';
    $dateTo = isset($_GET['date_to']) ? trim((string) $_GET['date_to']) : '';

    if ($dateFrom === '' || $dateTo === '') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'date_from and date_to are required (Y-m-d).',
        ]);
        exit;
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid date format. Use Y-m-d.',
        ]);
        exit;
    }

    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    $limit = max(1, min($limit, 2000));
    $offset = max(0, $offset);

    $sortDir = isset($_GET['sort_dir']) ? strtoupper(trim((string) $_GET['sort_dir'])) : 'DESC';
    if ($sortDir !== 'ASC' && $sortDir !== 'DESC') {
        $sortDir = 'DESC';
    }

    $filters = [
        'date_from' => $dateFrom . ' 00:00:00',
        'date_to' => $dateTo . ' 23:59:59',
    ];

    $search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
    if ($search !== '') {
        $filters['search'] = $search;
    }
    if (!empty($_GET['purpose'])) {
        $filters['purpose'] = trim((string) $_GET['purpose']);
    }
    if (!empty($_GET['gender'])) {
        $filters['gender'] = trim((string) $_GET['gender']);
    }
    if (!empty($_GET['purok'])) {
        $filters['purok'] = trim((string) $_GET['purok']);
    }

    $controller = new VisitorLogController();
    $result = $controller->indexForReports($filters, $limit, $offset, $sortDir);

    if (empty($result['success'])) {
        http_response_code(500);
        echo json_encode($result);
        exit;
    }

    if (!empty($result['data']) && is_array($result['data'])) {
        foreach ($result['data'] as &$log) {
            if (is_object($log)) {
                $log = json_decode(json_encode($log), true);
            }
            $fn = trim((string) ($log['first_name'] ?? ''));
            $mn = trim((string) ($log['middle_name'] ?? ''));
            $ln = trim((string) ($log['last_name'] ?? ''));
            $parts = array_filter([$fn, $mn, $ln]);
            $log['full_name'] = implode(' ', $parts);
        }
        unset($log);
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch visitor logs',
        'message' => $e->getMessage(),
    ]);
}
