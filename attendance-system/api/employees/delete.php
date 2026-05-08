<?php
require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../auth/helpers.php";

header("Content-Type: application/json");

// Require authentication for delete operations
requireAuth();

$method = $_SERVER["REQUEST_METHOD"];

/**
 * DELETE /api/employees/delete.php?id={employee_id} - Delete employee
 */
if ($method === "DELETE" || $method === "POST") {
    $employeeId = $_GET['id'] ?? $_POST['id'] ?? null;

    if (empty($employeeId)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Employee ID is required"
        ]);
        exit;
    }

    try {
        $employeeController = new EmployeeController();
        $result = $employeeController->delete($employeeId);

        $status = $result["status"] ?? 200;
        unset($result["status"]);
        http_response_code($status);
        echo json_encode($result);
        exit;

    } catch (Exception $err) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "error" => "Something went wrong - " . $err->getMessage()
        ]);
        exit;
    }
}

http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);
