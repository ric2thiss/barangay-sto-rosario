<?php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../auth/helpers.php';

requireAuth();

$user = currentUser();
$settingsRoles = ['administrator', 'admin', 'Administrator', 'Admin'];
if (!$user || !hasRole($settingsRoles)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Access denied. Administrator role required.']);
    exit;
}
