<?php
/**
 * GET: log_type=attendance|visitor, format=sql|pdf|docx|xlsx|zip, date_from?, date_to?, zip_formats=sql,xlsx,...
 */
require_once __DIR__ . '/_require_settings_admin.php';

$logType = strtolower(trim($_GET['log_type'] ?? 'attendance'));
$format = strtolower(trim($_GET['format'] ?? 'xlsx'));
$from = trim($_GET['date_from'] ?? '');
$to = trim($_GET['date_to'] ?? '');

if (!in_array($logType, ['attendance', 'visitor'], true)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Invalid log_type']);
    exit;
}

if ($from !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Invalid date_from']);
    exit;
}
if ($to !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Invalid date_to']);
    exit;
}

$base = ($logType === 'visitor' ? 'visitor_logs' : 'attendance_logs')
    . '_' . date('Y-m-d_His');

try {
    $svc = new LogExportService();
    $rows = $logType === 'visitor'
        ? $svc->fetchVisitorRows($from !== '' ? $from : null, $to !== '' ? $to : null)
        : $svc->fetchAttendanceRows($from !== '' ? $from : null, $to !== '' ? $to : null);
    $table = $logType === 'visitor' ? 'visitor_logs' : 'attendances';
    $headers = $rows !== [] ? array_keys($rows[0]) : [];

    if ($format === 'zip') {
        $zipParts = array_filter(array_map('trim', explode(',', $_GET['zip_formats'] ?? 'sql,xlsx,pdf,docx')));
        if ($zipParts === []) {
            $zipParts = ['sql', 'xlsx', 'pdf', 'docx'];
        }
        $bin = $svc->toZip($base, $logType, $zipParts, $from !== '' ? $from : null, $to !== '' ? $to : null);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $base . '.zip"');
        echo $bin;
        exit;
    }

    if ($format === 'sql') {
        $body = $svc->toSqlInserts($table, $rows);
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $base . '.sql"');
        echo $body;
        exit;
    }

    if ($format === 'pdf') {
        $body = $svc->toPdf($base, $headers, $rows);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $base . '.pdf"');
        echo $body;
        exit;
    }

    if ($format === 'xlsx') {
        $body = $svc->toXlsx($base, $headers, $rows);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $base . '.xlsx"');
        echo $body;
        exit;
    }

    if ($format === 'docx') {
        $body = $svc->toDocx($base, $headers, $rows);
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $base . '.docx"');
        echo $body;
        exit;
    }

    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Invalid format']);
} catch (Throwable $e) {
    error_log('log-export: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Export failed']);
}
