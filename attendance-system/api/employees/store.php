<?php
require_once __DIR__ . "/../../bootstrap.php";

header("Content-Type: application/json");

$method = $_SERVER["REQUEST_METHOD"];

/**
 * POST /api/employees/store.php - Create new employee
 */
if ($method === "POST") {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (!$data) {
        $data = $_POST;
    }

    if (empty($data)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid or missing data"]);
        exit;
    }

    try {
        $employeesController = new EmployeeController();
        $result = $employeesController->store($data);

        $status = $result["status"] ?? 200;
        unset($result["status"]); 
        http_response_code($status);
        echo json_encode($result);
        exit;

    } catch (Exception $err) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "error"   => "Something went wrong - " . $err->getMessage()
        ]);
        exit;
    }
}

http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);
