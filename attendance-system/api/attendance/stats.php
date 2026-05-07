<?php
require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../auth/helpers.php";

header("Content-Type: application/json");

// Require authentication
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$filter = $_GET["filter"] ?? "month";

try {
    // Get date range based on filter
    $now = new DateTime();
    $startDate = null;
    $endDate = new DateTime();

    switch($filter) {
        case 'today':
            $startDate = (new DateTime())->setTime(0, 0, 0);
            break;
        case 'week':
            $startDate = (clone $now)->modify('monday this week')->setTime(0, 0, 0);
            break;
        case 'month':
            $startDate = (clone $now)->modify('first day of this month')->setTime(0, 0, 0);
            break;
        case 'year':
            $startDate = (clone $now)->modify('first day of January this year')->setTime(0, 0, 0);
            break;
        default:
            $startDate = (clone $now)->modify('first day of this month')->setTime(0, 0, 0);
    }

    $startDateStr = $startDate->format('Y-m-d H:i:s');
    $endDateStr = $endDate->format('Y-m-d H:i:s');

    $pdo = (new Database())->connect();

    // Build optional soft-delete filter (column may not exist if migration not applied)
    $delFilter = SchemaColumnCache::attendancesHasDeletedAt() ? 'deleted_at IS NULL AND ' : '';

    // Get total employees count
    $employeeRepository = new EmployeeRepository($pdo);
    $totalEmployees = $employeeRepository->getEmployeeCount();

    // Count Present: Employees who have at least one attendance record in the period
    $presentStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT employee_id) as count
        FROM attendances
        WHERE {$delFilter}created_at >= ? AND created_at <= ?
    ");
    $presentStmt->execute([$startDateStr, $endDateStr]);
    $presentResult = $presentStmt->fetch(PDO::FETCH_OBJ);
    $totalPresent = $presentResult ? (int)$presentResult->count : 0;

    // Absent = Total Employees - Present (for the period)
    $totalAbsent = max(0, $totalEmployees - $totalPresent);

    // Count Late: Attendance records where time_in (morning_in or afternoon_in) is after expected time
    $lateStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT id) as count
        FROM attendances
        WHERE {$delFilter}created_at >= ? AND created_at <= ?
        AND (
            (window = 'morning_in' AND TIME(timestamp) > '08:00:00')
            OR (window = 'afternoon_in' AND TIME(timestamp) > '14:00:00')
        )
    ");
    $lateStmt->execute([$startDateStr, $endDateStr]);
    $lateResult = $lateStmt->fetch(PDO::FETCH_OBJ);
    $totalLate = $lateResult ? (int)$lateResult->count : 0;

    // Count Over-Time: Attendance records for afternoon_out after 17:00 (5 PM)
    $overtimeStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT id) as count
        FROM attendances
        WHERE {$delFilter}created_at >= ? AND created_at <= ?
        AND (
            window = 'afternoon_out' AND TIME(timestamp) > '17:00:00'
            OR window = 'morning_out' AND TIME(timestamp) > '13:00:00'
        )
    ");
    $overtimeStmt->execute([$startDateStr, $endDateStr]);
    $overtimeResult = $overtimeStmt->fetch(PDO::FETCH_OBJ);
    $totalOvertime = $overtimeResult ? (int)$overtimeResult->count : 0;

    echo json_encode([
        "success" => true,
        "filter" => $filter,
        "total_employees" => $totalEmployees,
        "total_present" => $totalPresent,
        "total_absent" => $totalAbsent,
        "total_late" => $totalLate,
        "total_overtime" => $totalOvertime
    ]);
} catch (Throwable $e) {
    error_log("api/attendance/stats.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Internal server error",
        "total_employees" => 0,
        "total_present" => 0,
        "total_absent" => 0,
        "total_late" => 0,
        "total_overtime" => 0
    ]);
}
