<?php
/**
 * POST /api/activities/active.php
 * JSON: { "activity_id": number | null } — default activity for biometric/API logs when activity_id is omitted.
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

$activityId = null;
if (array_key_exists('activity_id', $data)) {
    $v = $data['activity_id'];
    if ($v === '' || $v === null) {
        $activityId = null;
    } elseif (is_numeric($v)) {
        $activityId = (int) $v;
        if ($activityId <= 0) {
            $activityId = null;
        }
    }
}

$user = currentUser();
$updatedBy = isset($user['id']) ? (int) $user['id'] : null;

$controller = new ActivityController();
$result = $controller->setActiveActivity($activityId, $updatedBy);
if (empty($result['success'])) {
    http_response_code(400);
}
echo json_encode($result);
