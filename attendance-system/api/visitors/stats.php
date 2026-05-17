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

// Get visitor counts from visitor_logs table
$pdo = (new Database())->connect();

// Total visitors in date range
$totalVisitors = VisitorLog::query()
    ->whereRaw('(deleted_at IS NULL)')
    ->whereBetween('created_at', [$startDateStr, $endDateStr])
    ->count();

// Resident visitors: visitors where is_resident = 1
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM visitor_logs
    WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ?
    AND is_resident = 1
");
$stmt->execute([$startDateStr, $endDateStr]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalResidentVisitors = $result ? (int)($result['count'] ?? 0) : 0;

// Non-resident visitors: visitors where is_resident = 0
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM visitor_logs
    WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ?
    AND is_resident = 0
");
$stmt->execute([$startDateStr, $endDateStr]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalNonResidentVisitors = $result ? (int)($result['count'] ?? 0) : 0;

// Count Online Appointment visitors: visitors where had_booking = 1
$onlineStmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM visitor_logs
    WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ?
    AND had_booking = 1
");
$onlineStmt->execute([$startDateStr, $endDateStr]);
$onlineResult = $onlineStmt->fetch(PDO::FETCH_ASSOC);
$totalOnlineAppointment = $onlineResult ? (int)($onlineResult['count'] ?? 0) : 0;

// Count Walk-in visitors: visitors where had_booking = 0
$walkinStmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM visitor_logs
    WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ?
    AND had_booking = 0
");
$walkinStmt->execute([$startDateStr, $endDateStr]);
$walkinResult = $walkinStmt->fetch(PDO::FETCH_ASSOC);
$totalWalkin = $walkinResult ? (int)($walkinResult['count'] ?? 0) : 0;

echo json_encode([
    "success" => true,
    "filter" => $filter,
    "total_visitors" => $totalVisitors,
    "resident_visitors" => $totalResidentVisitors,
    "non_resident_visitors" => $totalNonResidentVisitors,
    "walkin" => $totalWalkin,
    "online_appointment" => $totalOnlineAppointment
]);
