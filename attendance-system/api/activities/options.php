<?php
/**
 * GET /api/activities/options.php?date=Y-m-d
 * Syncs today's LGUMS schedule_events into activities (deduped), returns dropdown options.
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

$date = isset($_GET['date']) ? trim((string) $_GET['date']) : '';
if ($date === '') {
    $date = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
}

$controller = new ActivityController();
echo json_encode($controller->optionsForAttendance($date));
