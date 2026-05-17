<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../auth/helpers.php';
requireAuth();

header('Content-Type: application/json');

http_response_code(410);
echo json_encode([
    "success" => false,
    "message" => "Civil status is managed by profiling-system and is not available in attendance-system."
]);
