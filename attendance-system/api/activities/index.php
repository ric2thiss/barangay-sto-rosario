<?php
/**
 * GET /api/activities/index.php — paginated list (admin)
 * POST /api/activities/index.php — create local activity
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../auth/helpers.php';

header('Content-Type: application/json');

requireAuth();

$controller = new ActivityController();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? max(1, min(100, (int) $_GET['per_page'])) : 20;
    $search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
    $from = isset($_GET['from']) ? trim((string) $_GET['from']) : null;
    $to = isset($_GET['to']) ? trim((string) $_GET['to']) : null;
    if ($from === '') {
        $from = null;
    }
    if ($to === '') {
        $to = null;
    }
    echo json_encode($controller->listPaginated($page, $perPage, $search, $from, $to));
    exit;
}

if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = $_POST;
    }
    $user = currentUser();
    $updatedBy = isset($user['id']) ? (int) $user['id'] : null;
    $result = $controller->createLocal($data, $updatedBy);
    if (empty($result['success'])) {
        http_response_code(422);
    } else {
        http_response_code(201);
    }
    echo json_encode($result);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
