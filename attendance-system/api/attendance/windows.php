<?php
require_once __DIR__ . "/../../bootstrap.php";

header("Content-Type: application/json");

$method = $_SERVER["REQUEST_METHOD"];

/**
 * GET /api/attendance/windows.php - Get available attendance windows
 */
if ($method === "GET") {
    $attendanceController = new AttendanceController();
    echo json_encode($attendanceController->windows());
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);
