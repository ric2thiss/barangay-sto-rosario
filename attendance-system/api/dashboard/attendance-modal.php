<?php
/**
 * Dashboard attendance analytics modal (read-only SELECT).
 * GET: filter=today|week|month|year, card=present|absent|late|overtime, limit
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
$card = $_GET['card'] ?? 'present';
$allowedCards = ['present', 'absent', 'late', 'overtime'];
if (!in_array($card, $allowedCards, true)) {
    $card = 'present';
}

$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 600;
$limit = max(1, min($limit, 1000));

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

$LATE_WHERE = "(
    (a.window = 'morning_in' AND TIME(a.timestamp) > '08:00:00')
    OR (a.window = 'afternoon_in' AND TIME(a.timestamp) > '14:00:00')
)";
$OT_WHERE = "(
    (a.window = 'afternoon_out' AND TIME(a.timestamp) > '17:00:00')
    OR (a.window = 'morning_out' AND TIME(a.timestamp) > '13:00:00')
)";
$LATE_WHERE_A0 = str_replace('a.', 'a0.', $LATE_WHERE);
$LATE_WHERE_A1 = str_replace('a.', 'a1.', $LATE_WHERE);
$LATE_WHERE_A2 = str_replace('a.', 'a2.', $LATE_WHERE);
$OT_WHERE_A0 = str_replace('a.', 'a0.', $OT_WHERE);
$OT_WHERE_A1 = str_replace('a.', 'a1.', $OT_WHERE);
$OT_WHERE_A2 = str_replace('a.', 'a2.', $OT_WHERE);

$pdo = (new Database())->connect();
$prof = defined('PROFILING_DB_NAME') ? PROFILING_DB_NAME : 'profiling-system';
$residents = '`' . $prof . '`.`residents`';
/** Cross-DB: same source as AttendanceRepository / dashboard employee count (read-only). */
$barangayTable = '`' . $prof . '`.`barangay_official`';

$employeeRepository = new EmployeeRepository($pdo);

$officialsTableUsable = false;
try {
    $pdo->query("SELECT 1 FROM {$barangayTable} LIMIT 1");
    $officialsTableUsable = true;
} catch (PDOException $e) {
    $msg = strtolower($e->getMessage());
    if (($e->getCode() === '42S02') || str_contains($msg, "doesn't exist")) {
        $officialsTableUsable = false;
    } else {
        throw $e;
    }
}

$totalEmployees = 0;
if ($officialsTableUsable) {
    try {
        $cntStmt = $pdo->query("SELECT COUNT(*) AS c FROM {$barangayTable}");
        $rowCnt = $cntStmt ? $cntStmt->fetch(PDO::FETCH_ASSOC) : null;
        $totalEmployees = (int) ($rowCnt['c'] ?? 0);
    } catch (Throwable $e) {
        error_log('attendance-modal barangay_official count: ' . $e->getMessage());
        $totalEmployees = 0;
    }
}
if ($totalEmployees === 0) {
    $totalEmployees = $employeeRepository->getEmployeeCount();
}

/** Legacy attendance-system.employees + profiling residents (secondary fallback). */
$employeesTableUsable = false;
try {
    $pdo->query('SELECT 1 FROM employees LIMIT 1');
    $employeesTableUsable = true;
} catch (PDOException $e) {
    $msg = strtolower($e->getMessage());
    if (($e->getCode() === '42S02') || (str_contains($msg, 'employees') && str_contains($msg, "doesn't exist"))) {
        $employeesTableUsable = false;
    } else {
        throw $e;
    }
}

$presentStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT employee_id) AS c FROM attendances
    WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ?
");
$presentStmt->execute([$startDateStr, $endDateStr]);
$totalPresentPeriod = (int) ($presentStmt->fetchColumn() ?: 0);
$totalAbsentPeriod = max(0, $totalEmployees - $totalPresentPeriod);

$trendLabels = [];
$trendPresent = [];
$trendAbsent = [];
$trendLate = [];
$trendOt = [];

try {
    if ($filter === 'today') {
        for ($hour = 0; $hour < 24; $hour++) {
            $trendLabels[] = str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00';
            $hourStart = (clone $startDate)->setTime($hour, 0, 0)->format('Y-m-d H:i:s');
            $hourEnd = (clone $startDate)->setTime($hour, 59, 59)->format('Y-m-d H:i:s');

            $stmt = $pdo->prepare("SELECT COUNT(DISTINCT employee_id) AS c FROM attendances WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ?");
            $stmt->execute([$hourStart, $hourEnd]);
            $trendPresent[] = (int) ($stmt->fetchColumn() ?: 0);
            $trendAbsent[] = 0;

            $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM attendances a WHERE a.deleted_at IS NULL AND a.created_at >= ? AND a.created_at <= ? AND {$LATE_WHERE}");
            $stmt->execute([$hourStart, $hourEnd]);
            $trendLate[] = (int) ($stmt->fetchColumn() ?: 0);

            $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM attendances a WHERE a.deleted_at IS NULL AND a.created_at >= ? AND a.created_at <= ? AND {$OT_WHERE}");
            $stmt->execute([$hourStart, $hourEnd]);
            $trendOt[] = (int) ($stmt->fetchColumn() ?: 0);
        }
    } elseif ($filter === 'week') {
        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $startOfWeek = clone $startDate;
        for ($i = 0; $i < 7; $i++) {
            $dayStart = (clone $startOfWeek)->modify("+{$i} days")->setTime(0, 0, 0)->format('Y-m-d H:i:s');
            $dayEnd = (clone $startOfWeek)->modify("+{$i} days")->setTime(23, 59, 59)->format('Y-m-d H:i:s');
            $trendLabels[] = $days[$i];

            $stmt = $pdo->prepare('SELECT COUNT(DISTINCT employee_id) AS c FROM attendances WHERE deleted_at IS NULL AND DATE(created_at) = DATE(?)');
            $stmt->execute([$dayStart]);
            $p = (int) ($stmt->fetchColumn() ?: 0);
            $trendPresent[] = $p;
            $trendAbsent[] = max(0, $totalEmployees - $p);

            $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM attendances a WHERE a.deleted_at IS NULL AND a.created_at >= ? AND a.created_at <= ? AND {$LATE_WHERE}");
            $stmt->execute([$dayStart, $dayEnd]);
            $trendLate[] = (int) ($stmt->fetchColumn() ?: 0);

            $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM attendances a WHERE a.deleted_at IS NULL AND a.created_at >= ? AND a.created_at <= ? AND {$OT_WHERE}");
            $stmt->execute([$dayStart, $dayEnd]);
            $trendOt[] = (int) ($stmt->fetchColumn() ?: 0);
        }
    } elseif ($filter === 'month') {
        $startOfMonth = clone $startDate;
        $endOfMonth = (clone $endDate)->modify('last day of this month');
        $weeksInMonth = (int) ceil(($startOfMonth->diff($endOfMonth)->days + 1) / 7);
        for ($week = 0; $week < min(4, $weeksInMonth); $week++) {
            $weekStart = (clone $startOfMonth)->modify("+{$week} weeks")->setTime(0, 0, 0);
            $weekEnd = (clone $weekStart)->modify('+6 days')->setTime(23, 59, 59);
            if ($weekEnd > $endOfMonth) {
                $weekEnd = $endOfMonth;
            }
            $trendLabels[] = 'Week ' . ($week + 1);
            $ws = $weekStart->format('Y-m-d H:i:s');
            $we = $weekEnd->format('Y-m-d H:i:s');

            $stmt = $pdo->prepare('SELECT COUNT(DISTINCT employee_id) AS c FROM attendances WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ?');
            $stmt->execute([$ws, $we]);
            $p = (int) ($stmt->fetchColumn() ?: 0);
            $trendPresent[] = $p;
            $trendAbsent[] = max(0, $totalEmployees - $p);

            $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM attendances a WHERE a.deleted_at IS NULL AND a.created_at >= ? AND a.created_at <= ? AND {$LATE_WHERE}");
            $stmt->execute([$ws, $we]);
            $trendLate[] = (int) ($stmt->fetchColumn() ?: 0);

            $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM attendances a WHERE a.deleted_at IS NULL AND a.created_at >= ? AND a.created_at <= ? AND {$OT_WHERE}");
            $stmt->execute([$ws, $we]);
            $trendOt[] = (int) ($stmt->fetchColumn() ?: 0);
        }
    } else {
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $currentYear = $now->format('Y');
        for ($month = 1; $month <= 12; $month++) {
            $monthStart = new DateTime("{$currentYear}-{$month}-01");
            $monthEnd = (clone $monthStart)->modify('last day of this month')->setTime(23, 59, 59);
            if ($monthStart > $endDate) {
                break;
            }
            if ($monthEnd > $endDate) {
                $monthEnd = clone $endDate;
            }
            $trendLabels[] = $months[$month - 1];
            $ms = $monthStart->format('Y-m-d H:i:s');
            $me = $monthEnd->format('Y-m-d H:i:s');

            $stmt = $pdo->prepare('SELECT COUNT(DISTINCT employee_id) AS c FROM attendances WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ?');
            $stmt->execute([$ms, $me]);
            $p = (int) ($stmt->fetchColumn() ?: 0);
            $trendPresent[] = $p;
            $trendAbsent[] = max(0, $totalEmployees - $p);

            $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM attendances a WHERE a.deleted_at IS NULL AND a.created_at >= ? AND a.created_at <= ? AND {$LATE_WHERE}");
            $stmt->execute([$ms, $me]);
            $trendLate[] = (int) ($stmt->fetchColumn() ?: 0);

            $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM attendances a WHERE a.deleted_at IS NULL AND a.created_at >= ? AND a.created_at <= ? AND {$OT_WHERE}");
            $stmt->execute([$ms, $me]);
            $trendOt[] = (int) ($stmt->fetchColumn() ?: 0);
        }
    }
} catch (Throwable $e) {
    error_log('attendance-modal buckets: ' . $e->getMessage());
}

$rows = [];

// profiling-system.barangay_official: attendances.employee_id = bo.id (same as AttendanceRepository).
$nameBoSql = "TRIM(CONCAT_WS(' ', NULLIF(TRIM(COALESCE(bo.first_name, '')), ''), NULLIF(TRIM(COALESCE(bo.middle_name, '')), ''), NULLIF(TRIM(COALESCE(bo.surname, '')), '')))";
// Fallback: attendance-system.employees + profiling residents.
$nameSql = "TRIM(CONCAT(COALESCE(r.first_name, ''), ' ', COALESCE(r.surname, '')))";

$rangeParams = [$startDateStr, $endDateStr];

try {
    if ($card === 'present') {
        $stmt = null;
        if ($officialsTableUsable) {
            $sqlOfficials = "
            SELECT
                bo.id AS employee_id,
                {$nameBoSql} AS full_name,
                (SELECT CONCAT(DATE_FORMAT(a1.timestamp, '%b %e, %Y %l:%i %p'), ' · ', a1.window)
                 FROM attendances a1
                 WHERE a1.employee_id = bo.id
                   AND a1.deleted_at IS NULL
                   AND a1.created_at >= ? AND a1.created_at <= ?
                 ORDER BY a1.timestamp DESC
                 LIMIT 1) AS last_log
            FROM {$barangayTable} bo
            WHERE bo.id IN (
                SELECT DISTINCT a0.employee_id FROM attendances a0
                WHERE a0.deleted_at IS NULL AND a0.created_at >= ? AND a0.created_at <= ?
            )
            ORDER BY full_name ASC, bo.id ASC
            LIMIT {$limit}
        ";
            try {
                $stmt = $pdo->prepare($sqlOfficials);
                $stmt->execute(array_merge($rangeParams, $rangeParams));
            } catch (PDOException $e) {
                error_log('attendance-modal present (barangay_official): ' . $e->getMessage());
                $stmt = null;
            }
        }
        if ($stmt === null && $employeesTableUsable) {
            $sqlJoined = "
            SELECT
                e.employee_id,
                {$nameSql} AS full_name,
                (SELECT CONCAT(DATE_FORMAT(a1.timestamp, '%b %e, %Y %l:%i %p'), ' · ', a1.window)
                 FROM attendances a1
                 WHERE a1.employee_id = e.employee_id
                   AND a1.deleted_at IS NULL
                   AND a1.created_at >= ? AND a1.created_at <= ?
                 ORDER BY a1.timestamp DESC
                 LIMIT 1) AS last_log
            FROM employees e
            LEFT JOIN {$residents} r ON e.resident_id = r.id
            WHERE e.employee_id IN (
                SELECT DISTINCT a0.employee_id FROM attendances a0
                WHERE a0.deleted_at IS NULL AND a0.created_at >= ? AND a0.created_at <= ?
            )
            ORDER BY full_name ASC, e.employee_id ASC
            LIMIT {$limit}
        ";
            try {
                $stmt = $pdo->prepare($sqlJoined);
                $stmt->execute(array_merge($rangeParams, $rangeParams));
            } catch (PDOException $e) {
                error_log('attendance-modal present (employees): ' . $e->getMessage());
                $stmt = null;
            }
        }
        if ($stmt === null) {
            $sql = "
            SELECT
                eid.employee_id AS employee_id,
                eid.employee_id AS full_name,
                (SELECT CONCAT(DATE_FORMAT(a1.timestamp, '%b %e, %Y %l:%i %p'), ' · ', a1.window)
                 FROM attendances a1
                 WHERE a1.employee_id = eid.employee_id
                   AND a1.deleted_at IS NULL
                   AND a1.created_at >= ? AND a1.created_at <= ?
                 ORDER BY a1.timestamp DESC
                 LIMIT 1) AS last_log
            FROM (
                SELECT DISTINCT employee_id FROM attendances
                WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ?
            ) AS eid
            ORDER BY eid.employee_id ASC
            LIMIT {$limit}
        ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge($rangeParams, $rangeParams));
        }
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $nm = trim((string) ($row['full_name'] ?? ''));
            if ($nm === '') {
                $nm = (string) ($row['employee_id'] ?? '');
            }
            $rows[] = [
                'employee_name' => $nm,
                'status' => 'Present',
                'last_log' => $row['last_log'],
            ];
        }
    } elseif ($card === 'absent') {
        $stmt = null;
        if ($officialsTableUsable) {
            $sqlAbsentBo = "
            SELECT bo.id AS employee_id, {$nameBoSql} AS full_name
            FROM {$barangayTable} bo
            WHERE NOT EXISTS (
                SELECT 1 FROM attendances a
                WHERE a.deleted_at IS NULL
                  AND a.employee_id = bo.id
                  AND a.created_at >= ? AND a.created_at <= ?
            )
            ORDER BY full_name ASC, bo.id ASC
            LIMIT {$limit}
        ";
            try {
                $stmt = $pdo->prepare($sqlAbsentBo);
                $stmt->execute($rangeParams);
            } catch (PDOException $e) {
                error_log('attendance-modal absent (barangay_official): ' . $e->getMessage());
                $stmt = null;
            }
        }
        if ($stmt === null && $employeesTableUsable) {
            $sqlJoined = "
            SELECT e.employee_id, {$nameSql} AS full_name
            FROM employees e
            LEFT JOIN {$residents} r ON e.resident_id = r.id
            WHERE e.employee_id NOT IN (
                SELECT DISTINCT employee_id FROM attendances
                WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ?
            )
            ORDER BY {$nameSql} ASC, e.employee_id ASC
            LIMIT {$limit}
        ";
            $sqlIdsOnly = "
            SELECT e.employee_id, e.employee_id AS full_name
            FROM employees e
            WHERE e.employee_id NOT IN (
                SELECT DISTINCT employee_id FROM attendances
                WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ?
            )
            ORDER BY e.employee_id ASC
            LIMIT {$limit}
        ";
            try {
                $stmt = $pdo->prepare($sqlJoined);
                $stmt->execute([$startDateStr, $endDateStr]);
            } catch (PDOException $e) {
                error_log('attendance-modal absent (employees): ' . $e->getMessage());
                $stmt = $pdo->prepare($sqlIdsOnly);
                $stmt->execute([$startDateStr, $endDateStr]);
            }
        }
        if ($stmt !== null) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $nm = trim((string) ($row['full_name'] ?? ''));
                if ($nm === '') {
                    $nm = (string) ($row['employee_id'] ?? '');
                }
                $rows[] = [
                    'employee_name' => $nm,
                    'status' => 'Absent',
                    'last_log' => null,
                ];
            }
        }
    } elseif ($card === 'late') {
        $stmt = null;
        if ($officialsTableUsable) {
            $sqlLateBo = "
            SELECT
                bo.id AS employee_id,
                {$nameBoSql} AS full_name,
                (SELECT CONCAT(DATE_FORMAT(a1.timestamp, '%b %e, %Y %l:%i %p'), ' · ', a1.window)
                 FROM attendances a1
                 WHERE a1.employee_id = bo.id
                   AND a1.deleted_at IS NULL
                   AND a1.created_at >= ? AND a1.created_at <= ?
                   AND {$LATE_WHERE_A1}
                 ORDER BY a1.timestamp DESC
                 LIMIT 1) AS last_log
            FROM {$barangayTable} bo
            WHERE bo.id IN (
                SELECT DISTINCT a0.employee_id FROM attendances a0
                WHERE a0.deleted_at IS NULL AND a0.created_at >= ? AND a0.created_at <= ?
                  AND {$LATE_WHERE_A0}
            )
            ORDER BY full_name ASC, bo.id ASC
            LIMIT {$limit}
        ";
            try {
                $stmt = $pdo->prepare($sqlLateBo);
                $stmt->execute(array_merge($rangeParams, $rangeParams));
            } catch (PDOException $e) {
                error_log('attendance-modal late (barangay_official): ' . $e->getMessage());
                $stmt = null;
            }
        }
        if ($stmt === null && $employeesTableUsable) {
            $sqlJoined = "
            SELECT
                e.employee_id,
                {$nameSql} AS full_name,
                (SELECT CONCAT(DATE_FORMAT(a1.timestamp, '%b %e, %Y %l:%i %p'), ' · ', a1.window)
                 FROM attendances a1
                 WHERE a1.employee_id = e.employee_id
                   AND a1.deleted_at IS NULL
                   AND a1.created_at >= ? AND a1.created_at <= ?
                   AND {$LATE_WHERE_A1}
                 ORDER BY a1.timestamp DESC
                 LIMIT 1) AS last_log
            FROM employees e
            LEFT JOIN {$residents} r ON e.resident_id = r.id
            WHERE e.employee_id IN (
                SELECT DISTINCT a0.employee_id FROM attendances a0
                WHERE a0.deleted_at IS NULL AND a0.created_at >= ? AND a0.created_at <= ?
                  AND {$LATE_WHERE_A0}
            )
            ORDER BY full_name ASC, e.employee_id ASC
            LIMIT {$limit}
        ";
            try {
                $stmt = $pdo->prepare($sqlJoined);
                $stmt->execute(array_merge($rangeParams, $rangeParams));
            } catch (PDOException $e) {
                error_log('attendance-modal late (employees): ' . $e->getMessage());
                $stmt = null;
            }
        }
        if ($stmt === null) {
            $sql = "
            SELECT
                eid.employee_id AS employee_id,
                eid.employee_id AS full_name,
                (SELECT CONCAT(DATE_FORMAT(a1.timestamp, '%b %e, %Y %l:%i %p'), ' · ', a1.window)
                 FROM attendances a1
                 WHERE a1.employee_id = eid.employee_id
                   AND a1.deleted_at IS NULL
                   AND a1.created_at >= ? AND a1.created_at <= ?
                   AND {$LATE_WHERE_A1}
                 ORDER BY a1.timestamp DESC
                 LIMIT 1) AS last_log
            FROM (
                SELECT DISTINCT a0.employee_id AS employee_id FROM attendances a0
                WHERE a0.deleted_at IS NULL AND a0.created_at >= ? AND a0.created_at <= ?
                  AND {$LATE_WHERE_A0}
            ) AS eid
            ORDER BY eid.employee_id ASC
            LIMIT {$limit}
        ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge($rangeParams, $rangeParams));
        }
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $nm = trim((string) ($row['full_name'] ?? ''));
            if ($nm === '') {
                $nm = (string) ($row['employee_id'] ?? '');
            }
            $rows[] = [
                'employee_name' => $nm,
                'status' => 'Late',
                'last_log' => $row['last_log'],
            ];
        }
    } else {
        $stmt = null;
        if ($officialsTableUsable) {
            $sqlOtBo = "
            SELECT
                bo.id AS employee_id,
                {$nameBoSql} AS full_name,
                (SELECT CONCAT(DATE_FORMAT(a1.timestamp, '%b %e, %Y %l:%i %p'), ' · ', a1.window)
                 FROM attendances a1
                 WHERE a1.employee_id = bo.id
                   AND a1.deleted_at IS NULL
                   AND a1.created_at >= ? AND a1.created_at <= ?
                   AND {$OT_WHERE_A1}
                 ORDER BY a1.timestamp DESC
                 LIMIT 1) AS last_log
            FROM {$barangayTable} bo
            WHERE bo.id IN (
                SELECT DISTINCT a0.employee_id FROM attendances a0
                WHERE a0.deleted_at IS NULL AND a0.created_at >= ? AND a0.created_at <= ?
                  AND {$OT_WHERE_A0}
            )
            ORDER BY full_name ASC, bo.id ASC
            LIMIT {$limit}
        ";
            try {
                $stmt = $pdo->prepare($sqlOtBo);
                $stmt->execute(array_merge($rangeParams, $rangeParams));
            } catch (PDOException $e) {
                error_log('attendance-modal overtime (barangay_official): ' . $e->getMessage());
                $stmt = null;
            }
        }
        if ($stmt === null && $employeesTableUsable) {
            $sqlJoined = "
            SELECT
                e.employee_id,
                {$nameSql} AS full_name,
                (SELECT CONCAT(DATE_FORMAT(a1.timestamp, '%b %e, %Y %l:%i %p'), ' · ', a1.window)
                 FROM attendances a1
                 WHERE a1.employee_id = e.employee_id
                   AND a1.deleted_at IS NULL
                   AND a1.created_at >= ? AND a1.created_at <= ?
                   AND {$OT_WHERE_A1}
                 ORDER BY a1.timestamp DESC
                 LIMIT 1) AS last_log
            FROM employees e
            LEFT JOIN {$residents} r ON e.resident_id = r.id
            WHERE e.employee_id IN (
                SELECT DISTINCT a0.employee_id FROM attendances a0
                WHERE a0.deleted_at IS NULL AND a0.created_at >= ? AND a0.created_at <= ?
                  AND {$OT_WHERE_A0}
            )
            ORDER BY full_name ASC, e.employee_id ASC
            LIMIT {$limit}
        ";
            try {
                $stmt = $pdo->prepare($sqlJoined);
                $stmt->execute(array_merge($rangeParams, $rangeParams));
            } catch (PDOException $e) {
                error_log('attendance-modal overtime (employees): ' . $e->getMessage());
                $stmt = null;
            }
        }
        if ($stmt === null) {
            $sql = "
            SELECT
                eid.employee_id AS employee_id,
                eid.employee_id AS full_name,
                (SELECT CONCAT(DATE_FORMAT(a1.timestamp, '%b %e, %Y %l:%i %p'), ' · ', a1.window)
                 FROM attendances a1
                 WHERE a1.employee_id = eid.employee_id
                   AND a1.deleted_at IS NULL
                   AND a1.created_at >= ? AND a1.created_at <= ?
                   AND {$OT_WHERE_A1}
                 ORDER BY a1.timestamp DESC
                 LIMIT 1) AS last_log
            FROM (
                SELECT DISTINCT a0.employee_id AS employee_id FROM attendances a0
                WHERE a0.deleted_at IS NULL AND a0.created_at >= ? AND a0.created_at <= ?
                  AND {$OT_WHERE_A0}
            ) AS eid
            ORDER BY eid.employee_id ASC
            LIMIT {$limit}
        ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge($rangeParams, $rangeParams));
        }
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $nm = trim((string) ($row['full_name'] ?? ''));
            if ($nm === '') {
                $nm = (string) ($row['employee_id'] ?? '');
            }
            $rows[] = [
                'employee_name' => $nm,
                'status' => 'Overtime',
                'last_log' => $row['last_log'],
            ];
        }
    }
} catch (Throwable $e) {
    error_log('attendance-modal list: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load attendance analytics']);
    exit;
}

echo json_encode([
    'success' => true,
    'filter' => $filter,
    'card' => $card,
    'charts' => [
        'trend' => [
            'labels' => $trendLabels,
            'present' => $trendPresent,
            'absent' => $trendAbsent,
            'late' => $trendLate,
            'overtime' => $trendOt,
        ],
        'present_vs_absent' => [
            'labels' => ['Present', 'Absent'],
            'counts' => [$totalPresentPeriod, $totalAbsentPeriod],
        ],
    ],
    'rows' => $rows,
    'row_count' => count($rows),
], JSON_UNESCAPED_UNICODE);
