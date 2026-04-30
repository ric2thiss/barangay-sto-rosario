<?php
require_once __DIR__ . "/../../bootstrap.php";

header("Content-Type: application/json");

$method = $_SERVER["REQUEST_METHOD"];

/**
 * GET /api/attendance/index.php - Get all attendance records
 * POST /api/attendance/index.php - Create new attendance record
 */
$attendanceController = new AttendanceController();

if ($method === "GET") {
    echo json_encode($attendanceController->index());
    exit;
}

if ($method === "POST") {
    $raw  = file_get_contents("php://input");
    $data = json_decode($raw, true);

    // Fallback: if not JSON, use $_POST
    if (is_null($data)) {
        $data = $_POST;
    }

    $attendanceController->store($data);
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);
