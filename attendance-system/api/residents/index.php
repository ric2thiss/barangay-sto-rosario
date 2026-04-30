<?php
require_once __DIR__ . "/../../bootstrap.php";

header("Content-Type: application/json");

$method = $_SERVER["REQUEST_METHOD"];

/**
 * GET /api/residents/index.php - Get all residents
 */
if ($method === "GET") {
    $residentsController = new ResidentController();
    $residents = $residentsController->getAllResidents();
    echo json_encode($residents);
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);
