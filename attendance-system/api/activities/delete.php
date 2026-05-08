<?php
/**
 * POST /api/activities/delete.php
 * JSON: { "id": int } — local activities only
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../auth/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

requireAuth();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

$id = isset($data['id']) ? (int) $data['id'] : 0;
if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid id']);
    exit;
}

$controller = new ActivityController();
$result = $controller->deleteLocal($id);
if (empty($result['success'])) {
    http_response_code(400);
}
echo json_encode($result);
