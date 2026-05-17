<?php
require_once __DIR__ . "/../../bootstrap.php";

header("Content-Type: application/json");

$method = $_SERVER["REQUEST_METHOD"];

/**
 * GET /api/templates/index.php - Get all fingerprint templates
 */
if ($method === "GET") {
    $fingerprintsController = new FingerprintsController();
    echo json_encode($fingerprintsController->index());
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);
