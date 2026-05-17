<?php
require_once __DIR__ . "/../../bootstrap.php";

header("Content-Type: application/json");

$method = $_SERVER["REQUEST_METHOD"];

/**
 * GET /api/employees/index.php - Get all employees
 */
if ($method === "GET") {
    require_once __DIR__ . "/../../auth/helpers.php";
    requireAuth();

    $employeesController = new EmployeeController();
    echo json_encode($employeesController->getAllEmployees());
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);
