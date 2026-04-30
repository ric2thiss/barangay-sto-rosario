<?php
require_once __DIR__ . "/../../bootstrap.php";

header("Content-Type: application/json");

$method = $_SERVER["REQUEST_METHOD"];

/**
 * GET /api/attendance/between.php?from=YYYY-MM-DD&to=YYYY-MM-DD - Get attendance between dates
 */
if ($method === "GET") {
    $from = $_GET["from"] ?? null;
    $to   = $_GET["to"] ?? null;

    if ($from && $to) {
        $attendanceController = new AttendanceController();
        $result = $attendanceController->getAttendanceBetween($from, $to);
        echo json_encode($result);
        exit;
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Missing 'from' or 'to' parameters"]);
        exit;
    }
}

http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);
