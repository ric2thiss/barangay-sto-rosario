<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../auth/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$pdo = (new Database())->connect();
$repo = new EventFineRepository($pdo);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    if (isset($_GET['overview']) && (string) $_GET['overview'] === '1') {
        try {
            $meetings = $repo->listActivitiesWithFineAmounts();
        } catch (Throwable $e) {
            error_log('event-fines overview: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Could not load fines overview.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode(['success' => true, 'meetings' => $meetings], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $aid = isset($_GET['activity_id']) ? (int) $_GET['activity_id'] : 0;
    if ($aid <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'activity_id required']);
        exit;
    }
    $act = Activity::find($aid);
    if (!$act) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Activity not found']);
        exit;
    }
    try {
        $amount = $repo->getAmountByActivityId($aid);
    } catch (Throwable $e) {
        error_log('event-fines GET: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Could not load fine settings.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode([
        'success' => true,
        'activity_id' => $aid,
        'fine_amount' => $amount,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST' || $method === 'PUT') {
    $raw = file_get_contents('php://input');
    $data = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($data)) {
        $data = $_POST;
    }
    $aid = isset($data['activity_id']) ? (int) $data['activity_id'] : 0;
    $amount = isset($data['fine_amount']) ? (float) $data['fine_amount'] : -1;
    if ($aid <= 0 || $amount < 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'activity_id and fine_amount (>= 0) required']);
        exit;
    }
    $act = Activity::find($aid);
    if (!$act) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Activity not found']);
        exit;
    }
    try {
        $repo->upsert($aid, $amount);
        echo json_encode([
            'success' => true,
            'activity_id' => $aid,
            'fine_amount' => round($amount, 2),
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        error_log('event-fines upsert: ' . $e->getMessage());
        http_response_code(500);
        $hint = $e->getMessage();
        if (strlen($hint) > 240) {
            $hint = 'Database error while saving. If your MySQL user cannot CREATE tables, run database/migrations/create_event_fines.sql manually.';
        }
        echo json_encode(['success' => false, 'error' => $hint], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
