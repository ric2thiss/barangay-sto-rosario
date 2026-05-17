<?php
/**
 * Reserved endpoint for future payroll integration (external systems).
 * Does not process payroll; returns a stable JSON contract for clients.
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../auth/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed',
    ]);
    exit;
}

requireAuth();

echo json_encode([
    'success' => true,
    'payroll_ui_available' => false,
    'message' => 'Payroll is not handled in this application UI. This endpoint is reserved for future payroll API integration.',
    'documentation' => 'Contact system administrator for integration details when available.',
]);
