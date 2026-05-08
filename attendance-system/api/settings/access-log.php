<?php
require_once __DIR__ . '/_require_settings_admin.php';

header('Content-Type: application/json; charset=utf-8');

$lines = isset($_GET['lines']) ? (int) $_GET['lines'] : 200;

try {
    $result = ApacheAccessLogReader::tailLines($lines);
    echo json_encode([
        'success' => true,
        'path' => $result['path'],
        'lines' => $result['lines'],
        'error' => $result['error'],
    ]);
} catch (Throwable $e) {
    error_log('access-log: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to read access log']);
}
