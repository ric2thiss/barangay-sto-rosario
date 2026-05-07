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

    $labels = [];
    $presentData = [];
    $absentData = [];

    // Get total employees count
    $employeeRepository = new EmployeeRepository($pdo);
    $totalEmployees = $employeeRepository->getEmployeeCount();

    if ($filter === 'today') {
        // Today's data by hours
        for ($hour = 0; $hour < 24; $hour++) {
            $hourStr = str_pad($hour, 2, '0', STR_PAD_LEFT);
            $labels[] = $hourStr . ':00';
            
            $hourStart = (clone $startDate)->setTime($hour, 0, 0)->format('Y-m-d H:i:s');
            $hourEnd = (clone $startDate)->setTime($hour, 59, 59)->format('Y-m-d H:i:s');
            
            // Count distinct employees who have attendance in this hour
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT employee_id) as count
                FROM attendances
                WHERE {$delFilter}created_at >= ? AND created_at <= ?
            ");
            $stmt->execute([$hourStart, $hourEnd]);
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            $presentData[] = $result ? (int)$result->count : 0;
            
            // Absent = 0 for hourly view (not meaningful)
            $absentData[] = 0;
        }
    } else if ($filter === 'week') {
        // This week's data by days
        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $startOfWeek = (clone $startDate);
        
        for ($i = 0; $i < 7; $i++) {
            $dayStart = (clone $startOfWeek)->modify("+{$i} days")->setTime(0, 0, 0)->format('Y-m-d H:i:s');
            $dayEnd = (clone $startOfWeek)->modify("+{$i} days")->setTime(23, 59, 59)->format('Y-m-d H:i:s');
            
            $labels[] = $days[$i];
            
            // Count distinct employees who have attendance on this day
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT employee_id) as count
                FROM attendances
                WHERE {$delFilter}DATE(created_at) = DATE(?)
            ");
            $stmt->execute([$dayStart]);
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            $present = $result ? (int)$result->count : 0;
            $presentData[] = $present;
            
            // Absent = Total Employees - Present (approximation)
            $absentData[] = max(0, $totalEmployees - $present);
        }
    } else if ($filter === 'month') {
        // This month's data by weeks
        $startOfMonth = (clone $startDate);
        $endOfMonth = (clone $endDate)->modify('last day of this month');
        $weeksInMonth = ceil(($startOfMonth->diff($endOfMonth)->days + 1) / 7);
        
        for ($week = 0; $week < min(4, $weeksInMonth); $week++) {
            $weekStart = (clone $startOfMonth)->modify("+{$week} weeks")->setTime(0, 0, 0);
            $weekEnd = (clone $weekStart)->modify('+6 days')->setTime(23, 59, 59);
            
            // Make sure we don't go beyond end of month
            if ($weekEnd > $endOfMonth) {
                $weekEnd = $endOfMonth;
            }
            
            $labels[] = 'Week ' . ($week + 1);
            
            $weekStartStr = $weekStart->format('Y-m-d H:i:s');
            $weekEndStr = $weekEnd->format('Y-m-d H:i:s');
            
            // Count distinct employees who have attendance in this week
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT employee_id) as count
                FROM attendances
                WHERE {$delFilter}created_at >= ? AND created_at <= ?
            ");
            $stmt->execute([$weekStartStr, $weekEndStr]);
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            $present = $result ? (int)$result->count : 0;
            $presentData[] = $present;
            
            // Absent = Total Employees - Present (approximation)
            $absentData[] = max(0, $totalEmployees - $present);
        }
    } else if ($filter === 'year') {
        // This year's data by months
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $currentYear = $now->format('Y');
        
        for ($month = 1; $month <= 12; $month++) {
            $monthStart = new DateTime("{$currentYear}-{$month}-01");
            $monthEnd = (clone $monthStart)->modify('last day of this month')->setTime(23, 59, 59);
            
            // Only include months up to current month
            if ($monthStart > $endDate) {
                break;
            }
            
            // Adjust end date if beyond current date
            if ($monthEnd > $endDate) {
                $monthEnd = $endDate;
            }
            
            $labels[] = $months[$month - 1];
            
            $monthStartStr = $monthStart->format('Y-m-d H:i:s');
            $monthEndStr = $monthEnd->format('Y-m-d H:i:s');
            
            // Count distinct employees who have attendance in this month
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT employee_id) as count
                FROM attendances
                WHERE {$delFilter}created_at >= ? AND created_at <= ?
            ");
            $stmt->execute([$monthStartStr, $monthEndStr]);
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            $present = $result ? (int)$result->count : 0;
            $presentData[] = $present;
            
            // Absent = Total Employees - Present (approximation)
            $absentData[] = max(0, $totalEmployees - $present);
        }
    }

    echo json_encode([
        "success" => true,
        "filter" => $filter,
        "labels" => $labels,
        "presentData" => $presentData,
        "absentData" => $absentData
    ]);
} catch (Throwable $e) {
    error_log("api/attendance/chart.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Internal server error",
        "labels" => [],
        "presentData" => [],
        "absentData" => []
    ]);
}
