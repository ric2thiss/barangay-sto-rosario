<?php
require_once __DIR__ . "/../../bootstrap.php";

header("Content-Type: application/json");

$method = $_SERVER["REQUEST_METHOD"];

/**
 * GET /api/employees/index.php - Get all employees
 * Supports API key authentication via x-api-key header
 */
if ($method === "GET") {
    $headers = getallheaders();
    $apiKey = $headers["x-api-key"] ?? null;

    if (!$apiKey) {
        http_response_code(400);
        echo json_encode(["error" => "Missing API key"]);
        exit;
    }

    if (constant("API_KEY") !== $apiKey) {
        http_response_code(401);
        echo json_encode(["error" => "Invalid API key"]);
        exit;
    }

    $employeesController = new EmployeeController();
    echo json_encode($employeesController->getAllEmployees());
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);
