<?php
/**
 * GET: full database SQL backup (administrator settings role required).
 */
require_once __DIR__ . '/_require_settings_admin.php';

$pdo = (new Database())->connect();
if (!$pdo) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$fname = 'attendance_system_full_backup_' . date('Y-m-d_His') . '.sql';

try {
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    header('Cache-Control: no-store');
    DatabaseBackupExporter::streamToOutput($pdo);
} catch (Throwable $e) {
    error_log('database-backup: ' . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Backup failed']);
    }
}
