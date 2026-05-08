<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../auth/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$pdo = (new Database())->connect();
$service = new AttendanceAnalyticsService($pdo);
$profilingDb = defined('PROFILING_DB_NAME') ? PROFILING_DB_NAME : 'profiling-system';

if ($method === 'GET') {
    $filter = $_GET['filter'] ?? 'monthly';
    $from = isset($_GET['from']) ? trim((string) $_GET['from']) : null;
    $to = isset($_GET['to']) ? trim((string) $_GET['to']) : null;
    if ($from === '') {
        $from = null;
    }
    if ($to === '') {
        $to = null;
    }
    $range = AttendanceAnalyticsService::resolveDateRange($filter, $from, $to);
    $activityRaw = isset($_GET['activity_id']) ? trim((string) $_GET['activity_id']) : '';
    if ($activityRaw !== '' && strtolower($activityRaw) !== 'all' && $activityRaw !== '0' && ctype_digit($activityRaw)) {
        $aid = (int) $activityRaw;
        if ($aid > 0) {
            $actRepo = new ActivityRepository($pdo);
            if (!$actRepo->existsById($aid)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid activity_id']);
                exit;
            }
        }
    }
    $filters = [
        'employee_id' => isset($_GET['employee_id']) ? trim((string) $_GET['employee_id']) : '',
        'status' => isset($_GET['status']) ? trim((string) $_GET['status']) : '',
        'activity_id' => $activityRaw,
        'page' => isset($_GET['page']) ? (int) $_GET['page'] : 1,
        'per_page' => isset($_GET['per_page']) ? (int) $_GET['per_page'] : 25,
    ];

    $detailRaw = isset($_GET['detail']) ? trim((string) $_GET['detail']) : '';
    if ($detailRaw !== '') {
        $allowedDetail = ['late', 'undertime', 'overtime', 'incomplete', 'absences'];
        if (!in_array($detailRaw, $allowedDetail, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid detail parameter']);
            exit;
        }
        $detailPage = isset($_GET['detail_page']) ? max(1, (int) $_GET['detail_page']) : 1;
        $detailPer = isset($_GET['detail_per_page']) ? max(1, min(100, (int) $_GET['detail_per_page'])) : 50;
        $detailFilters = [
            'employee_id' => $filters['employee_id'],
            'activity_id' => $filters['activity_id'],
        ];
        echo json_encode(
            $service->buildAttentionDetail($range, $detailFilters, $detailRaw, $detailPage, $detailPer),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    echo json_encode($service->buildReport($range, $filters), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $data = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($data)) {
        $data = $_POST;
    }

    $employeeId = isset($data['employee_id']) ? trim((string) $data['employee_id']) : '';
    $windowRaw = isset($data['window']) ? trim((string) $data['window']) : '';
    $dateYmd = isset($data['date']) ? trim((string) $data['date']) : '';
    $timeRaw = isset($data['time']) ? trim((string) $data['time']) : '';

    if ($employeeId === '' || $windowRaw === '' || $dateYmd === '' || $timeRaw === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'employee_id, window, date, and time are required']);
        exit;
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateYmd)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Invalid date format (use Y-m-d)']);
        exit;
    }

    $windowNorm = AttendanceAnalyticsService::normalizeLabel($windowRaw);
    $windowRepo = new AttendanceWindowRepository($pdo);
    $windows = $windowRepo->getWindowsArray();
    $labels = array_map('strtolower', array_column($windows, 'label'));
    if (!in_array($windowNorm, $labels, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid window for master list']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM `{$profilingDb}`.`barangay_official` WHERE id = ? LIMIT 1");
        $stmt->execute([$employeeId]);
        if (!$stmt->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Employee not found']);
            exit;
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Lookup failed']);
        exit;
    }

    $repo = new AttendanceRepository($pdo);
    if ($repo->existsForWindowOnDate($employeeId, $windowNorm, $dateYmd)) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'A log already exists for this window and date']);
        exit;
    }

    if (preg_match('/^\d{2}:\d{2}$/', $timeRaw)) {
        $timeRaw .= ':00';
    }
    if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $timeRaw)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Invalid time (use H:i or H:i:s)']);
        exit;
    }

    $tz = new DateTimeZone('Asia/Manila');
    try {
        $dt = new DateTime($dateYmd . ' ' . $timeRaw, $tz);
    } catch (Exception $e) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Invalid date/time combination']);
        exit;
    }

    $now = (new DateTime('now', $tz))->format('Y-m-d H:i:s');
    $ts = $dt->format('Y-m-d H:i:s');

    $resolvedActivityId = null;
    if (array_key_exists('activity_id', $data)) {
        $aRaw = $data['activity_id'];
        if ($aRaw !== null && $aRaw !== '' && strtolower((string) $aRaw) !== 'null') {
            $aid = (int) $aRaw;
            if ($aid > 0) {
                $actRepo = new ActivityRepository($pdo);
                if (!$actRepo->existsById($aid)) {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'error' => 'Invalid activity_id']);
                    exit;
                }
                $resolvedActivityId = $aid;
            }
        }
    }

    try {
        $id = $repo->create([
            'employee_id' => $employeeId,
            'window' => $windowNorm,
            'timestamp' => $ts,
            'created_at' => $now,
            'updated_at' => $now,
            'activity_id' => $resolvedActivityId,
        ]);
        echo json_encode([
            'success' => true,
            'message' => 'Missing log recorded',
            'id' => $id,
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save', 'details' => $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
