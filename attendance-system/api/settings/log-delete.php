<?php
/**
 * POST JSON: username, password, log_type (attendance|visitor), date_from?, date_to? (Y-m-d)
 * Soft-deletes matching non-deleted rows (deleted_at = NOW()).
 */
require_once __DIR__ . '/_require_settings_admin.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$username = trim((string) ($input['username'] ?? ''));
$password = (string) ($input['password'] ?? '');
$logType = strtolower(trim((string) ($input['log_type'] ?? '')));
$from = isset($input['date_from']) ? trim((string) $input['date_from']) : '';
$to = isset($input['date_to']) ? trim((string) $input['date_to']) : '';

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Username and password are required']);
    exit;
}

if (!in_array($logType, ['attendance', 'visitor'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid log_type']);
    exit;
}

$auth = new AuthController();
if (!$auth->verifyCredentials($username, $password)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    exit;
}

$migrationHint = 'Run database/run_settings_logs_migration.php (or apply database/migrations/settings_logs_soft_delete.sql).';
if ($logType === 'attendance' && !SchemaColumnCache::attendancesHasDeletedAt()) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "Soft-delete is not available yet (column deleted_at missing on attendances). {$migrationHint}"]);
    exit;
}
if ($logType === 'visitor' && !SchemaColumnCache::visitorLogsHasDeletedAt()) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "Soft-delete is not available yet (column deleted_at missing on visitor_logs). {$migrationHint}"]);
    exit;
}

$pdo = (new Database())->connect();

try {
    if ($logType === 'attendance') {
        $sql = "UPDATE attendances SET deleted_at = NOW() WHERE deleted_at IS NULL";
        $params = [];
        if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $sql .= ' AND DATE(COALESCE(`timestamp`, created_at)) >= ?';
            $params[] = $from;
        }
        if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $sql .= ' AND DATE(COALESCE(`timestamp`, created_at)) <= ?';
            $params[] = $to;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $affected = $stmt->rowCount();
    } else {
        $sql = "UPDATE visitor_logs SET deleted_at = NOW() WHERE deleted_at IS NULL";
        $params = [];
        if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $sql .= ' AND DATE(created_at) >= ?';
            $params[] = $from;
        }
        if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $sql .= ' AND DATE(created_at) <= ?';
            $params[] = $to;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $affected = $stmt->rowCount();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Logs marked for deletion. They will be permanently removed after 30 days.',
        'affected_rows' => $affected,
    ]);
} catch (Throwable $e) {
    error_log('log-delete: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
