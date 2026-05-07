<?php
/**
 * Read-only dashboard insights: top employees by log volume, visitor age buckets.
 * Does not modify data. Separate from C# biometric API surface.
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../auth/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$filter = $_GET['filter'] ?? 'month';
$now = new DateTime();
$endDate = new DateTime();

switch ($filter) {
    case 'today':
        $startDate = (new DateTime())->setTime(0, 0, 0);
        break;
    case 'week':
        $startDate = (clone $now)->modify('monday this week')->setTime(0, 0, 0);
        break;
    case 'year':
        $startDate = (clone $now)->modify('first day of January this year')->setTime(0, 0, 0);
        break;
    case 'month':
    default:
        $filter = 'month';
        $startDate = (clone $now)->modify('first day of this month')->setTime(0, 0, 0);
        break;
}

$startDateStr = $startDate->format('Y-m-d H:i:s');
$endDateStr = $endDate->format('Y-m-d H:i:s');

$pdo = (new Database())->connect();
$prof = defined('PROFILING_DB_NAME') ? PROFILING_DB_NAME : 'profiling-system';

$topEmployees = [];
try {
    $sql = "SELECT a.employee_id AS employee_id, COUNT(*) AS log_count,
            TRIM(CONCAT(COALESCE(r.first_name, ''), ' ', COALESCE(r.surname, ''))) AS full_name
            FROM attendances a
            LEFT JOIN employees e ON e.employee_id = a.employee_id
            LEFT JOIN `{$prof}`.residents r ON r.id = e.resident_id
            WHERE a.deleted_at IS NULL AND a.created_at >= ? AND a.created_at <= ?
            GROUP BY a.employee_id, r.first_name, r.surname
            ORDER BY log_count DESC
            LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$startDateStr, $endDateStr]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $name = trim((string) ($row['full_name'] ?? ''));
        if ($name === '') {
            $name = (string) ($row['employee_id'] ?? '');
        }
        $topEmployees[] = [
            'employee_id' => (string) ($row['employee_id'] ?? ''),
            'full_name' => $name,
            'log_count' => (int) ($row['log_count'] ?? 0),
        ];
    }
} catch (Throwable $e) {
    error_log('dashboard extra-charts top employees: ' . $e->getMessage());
    $topEmployees = [];
}

$visitorAgeLabels = [];
$visitorAgeCounts = [];
try {
    $sql = "SELECT bracket, COUNT(*) AS cnt FROM (
        SELECT
            CASE
                WHEN birthdate IS NULL OR birthdate = '0000-00-00' THEN 'Unknown'
                WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) < 18 THEN 'Under 18'
                WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) <= 30 THEN '18–30'
                WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) <= 45 THEN '31–45'
                WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) <= 60 THEN '46–60'
                ELSE '61+'
            END AS bracket
        FROM visitor_logs
        WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ?
    ) t
    GROUP BY bracket
    ORDER BY FIELD(bracket, 'Under 18', '18–30', '31–45', '46–60', '61+', 'Unknown')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$startDateStr, $endDateStr]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $visitorAgeLabels[] = (string) ($row['bracket'] ?? '');
        $visitorAgeCounts[] = (int) ($row['cnt'] ?? 0);
    }
} catch (Throwable $e) {
    error_log('dashboard extra-charts visitor age: ' . $e->getMessage());
}

$visitorResidentSplit = ['labels' => ['Resident', 'Non-Resident'], 'counts' => [0, 0]];
$visitorVisitType = ['labels' => ['Walk-in', 'Online appointment'], 'counts' => [0, 0]];
$visitorServicesLabels = [];
$visitorServicesCounts = [];
try {
    $stmt = $pdo->prepare("
        SELECT
            SUM(CASE WHEN is_resident = 1 THEN 1 ELSE 0 END) AS c_res,
            SUM(CASE WHEN is_resident = 0 THEN 1 ELSE 0 END) AS c_non
        FROM visitor_logs
        WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ?
    ");
    $stmt->execute([$startDateStr, $endDateStr]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $visitorResidentSplit['counts'] = [
        (int) ($row['c_res'] ?? 0),
        (int) ($row['c_non'] ?? 0),
    ];

    $stmt = $pdo->prepare("
        SELECT
            SUM(CASE WHEN had_booking = 0 THEN 1 ELSE 0 END) AS c_walk,
            SUM(CASE WHEN had_booking = 1 THEN 1 ELSE 0 END) AS c_onl
        FROM visitor_logs
        WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ?
    ");
    $stmt->execute([$startDateStr, $endDateStr]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $visitorVisitType['counts'] = [
        (int) ($row['c_walk'] ?? 0),
        (int) ($row['c_onl'] ?? 0),
    ];

    $stmt = $pdo->prepare("
        SELECT purpose AS p, COUNT(*) AS c
        FROM visitor_logs
        WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ?
          AND TRIM(COALESCE(purpose, '')) <> ''
        GROUP BY purpose
        ORDER BY c DESC
        LIMIT 12
    ");
    $stmt->execute([$startDateStr, $endDateStr]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $visitorServicesLabels[] = (string) ($row['p'] ?? '');
        $visitorServicesCounts[] = (int) ($row['c'] ?? 0);
    }
} catch (Throwable $e) {
    error_log('dashboard extra-charts visitor insights: ' . $e->getMessage());
}

echo json_encode([
    'success' => true,
    'filter' => $filter,
    'top_employees' => $topEmployees,
    'visitor_age' => [
        'labels' => $visitorAgeLabels,
        'counts' => $visitorAgeCounts,
    ],
    'visitor_resident_split' => $visitorResidentSplit,
    'visitor_visit_type' => $visitorVisitType,
    'visitor_services' => [
        'labels' => $visitorServicesLabels,
        'counts' => $visitorServicesCounts,
    ],
], JSON_UNESCAPED_UNICODE);
