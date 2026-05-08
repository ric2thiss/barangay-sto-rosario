<?php
/**
 * Dashboard visitor analytics modal payload (read-only SELECT).
 * GET: filter, card, limit (default 50, max 200), offset, skip_charts=1 (table-only for pagination)
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
$card = $_GET['card'] ?? 'total';
$allowedCards = ['total', 'resident', 'non_resident', 'walkin', 'online'];
if (!in_array($card, $allowedCards, true)) {
    $card = 'total';
}

$skipCharts = isset($_GET['skip_charts']) && $_GET['skip_charts'] === '1';

$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
$limit = max(1, min($limit, 200));
$offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
$offset = max(0, $offset);

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

$cardPlain = '';
$cardVl = '';
switch ($card) {
    case 'resident':
        $cardPlain = ' AND is_resident = 1';
        $cardVl = ' AND vl.is_resident = 1';
        break;
    case 'non_resident':
        $cardPlain = ' AND is_resident = 0';
        $cardVl = ' AND vl.is_resident = 0';
        break;
    case 'walkin':
        $cardPlain = ' AND had_booking = 0';
        $cardVl = ' AND vl.had_booking = 0';
        break;
    case 'online':
        $cardPlain = ' AND had_booking = 1';
        $cardVl = ' AND vl.had_booking = 1';
        break;
    default:
        break;
}

$pdo = (new Database())->connect();
$prof = defined('PROFILING_DB_NAME') ? PROFILING_DB_NAME : 'profiling-system';
$residents = '`' . $prof . '`.`residents`';

/**
 * Visitor trend buckets (aligned with api/visitors/chart.php) with optional card filter.
 *
 * @return array{labels: string[], counts: int[]}
 */
function buildVisitorTrend(PDO $pdo, string $filter, DateTime $startDate, DateTime $endDate, DateTime $now, string $cardPlain): array {
    $labels = [];
    $counts = [];

    if ($filter === 'today') {
        for ($hour = 0; $hour < 24; $hour++) {
            $labels[] = str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00';
            $hourStart = (clone $startDate)->setTime($hour, 0, 0)->format('Y-m-d H:i:s');
            $hourEnd = (clone $startDate)->setTime($hour, 59, 59)->format('Y-m-d H:i:s');
            $stmt = $pdo->prepare("
                SELECT COUNT(*) AS c FROM visitor_logs
                WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ? {$cardPlain}
            ");
            $stmt->execute([$hourStart, $hourEnd]);
            $counts[] = (int) ($stmt->fetchColumn() ?: 0);
        }
    } elseif ($filter === 'week') {
        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $startOfWeek = clone $startDate;
        for ($i = 0; $i < 7; $i++) {
            $dayStart = (clone $startOfWeek)->modify("+{$i} days")->format('Y-m-d');
            $labels[] = $days[$i];
            $stmt = $pdo->prepare("
                SELECT COUNT(*) AS c FROM visitor_logs
                WHERE deleted_at IS NULL AND DATE(created_at) = ? {$cardPlain}
            ");
            $stmt->execute([$dayStart]);
            $counts[] = (int) ($stmt->fetchColumn() ?: 0);
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
            $labels[] = 'Week ' . ($week + 1);
            $stmt = $pdo->prepare("
                SELECT COUNT(*) AS c FROM visitor_logs
                WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ? {$cardPlain}
            ");
            $stmt->execute([$weekStart->format('Y-m-d H:i:s'), $weekEnd->format('Y-m-d H:i:s')]);
            $counts[] = (int) ($stmt->fetchColumn() ?: 0);
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
            $labels[] = $months[$month - 1];
            $stmt = $pdo->prepare("
                SELECT COUNT(*) AS c FROM visitor_logs
                WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ? {$cardPlain}
            ");
            $stmt->execute([$monthStart->format('Y-m-d H:i:s'), $monthEnd->format('Y-m-d H:i:s')]);
            $counts[] = (int) ($stmt->fetchColumn() ?: 0);
        }
    }

    return ['labels' => $labels, 'counts' => $counts];
}

$trend = ['labels' => [], 'counts' => []];
$residentVsNon = ['labels' => ['Resident', 'Non-Resident'], 'counts' => [0, 0]];
$purok = ['labels' => [], 'counts' => []];
$services = ['labels' => [], 'counts' => []];
$walkOnline = ['labels' => ['Walk-in', 'Online'], 'counts' => [0, 0]];
$cityOrigin = ['labels' => [], 'counts' => []];
$rows = [];
$totalRows = 0;

try {
    if (!$skipCharts) {
        $trend = buildVisitorTrend($pdo, $filter, $startDate, $endDate, $now, $cardPlain);

        $stmt = $pdo->prepare("
        SELECT
            SUM(CASE WHEN is_resident = 1 THEN 1 ELSE 0 END) AS c_res,
            SUM(CASE WHEN is_resident = 0 THEN 1 ELSE 0 END) AS c_non
        FROM visitor_logs
        WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ? {$cardPlain}
    ");
        $stmt->execute([$startDateStr, $endDateStr]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $residentVsNon['counts'] = [(int) ($r['c_res'] ?? 0), (int) ($r['c_non'] ?? 0)];

        $stmt = $pdo->prepare("
        SELECT
            SUM(CASE WHEN had_booking = 0 THEN 1 ELSE 0 END) AS c_w,
            SUM(CASE WHEN had_booking = 1 THEN 1 ELSE 0 END) AS c_o
        FROM visitor_logs
        WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ? {$cardPlain}
    ");
        $stmt->execute([$startDateStr, $endDateStr]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $walkOnline['counts'] = [(int) ($r['c_w'] ?? 0), (int) ($r['c_o'] ?? 0)];

        $stmt = $pdo->prepare("
        SELECT vl.purpose AS p, COUNT(*) AS c
        FROM visitor_logs vl
        WHERE vl.deleted_at IS NULL AND vl.created_at >= ? AND vl.created_at <= ? {$cardVl}
          AND TRIM(COALESCE(vl.purpose, '')) <> ''
        GROUP BY vl.purpose
        ORDER BY c DESC
        LIMIT 12
    ");
        $stmt->execute([$startDateStr, $endDateStr]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $services['labels'][] = (string) ($row['p'] ?? '');
            $services['counts'][] = (int) ($row['c'] ?? 0);
        }

        $stmt = $pdo->prepare("
        SELECT
            COALESCE(NULLIF(TRIM(r.purok), ''), 'Unknown') AS lb,
            COUNT(*) AS c
        FROM visitor_logs vl
        LEFT JOIN {$residents} r ON vl.resident_id = r.id AND vl.is_resident = 1
        WHERE vl.deleted_at IS NULL AND vl.created_at >= ? AND vl.created_at <= ? {$cardVl}
          AND vl.is_resident = 1
        GROUP BY lb
        ORDER BY c DESC
        LIMIT 12
    ");
        $stmt->execute([$startDateStr, $endDateStr]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $purok['labels'][] = (string) ($row['lb'] ?? '');
            $purok['counts'][] = (int) ($row['c'] ?? 0);
        }

        $stmt = $pdo->prepare("
        SELECT
            CASE
                WHEN TRIM(COALESCE(vl.address, '')) = '' THEN 'Unknown'
                ELSE LEFT(TRIM(SUBSTRING_INDEX(vl.address, ',', -1)), 80)
            END AS lb,
            COUNT(*) AS c
        FROM visitor_logs vl
        WHERE vl.deleted_at IS NULL AND vl.created_at >= ? AND vl.created_at <= ? {$cardVl}
          AND vl.is_resident = 0
        GROUP BY lb
        ORDER BY c DESC
        LIMIT 15
    ");
        $stmt->execute([$startDateStr, $endDateStr]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cityOrigin['labels'][] = (string) ($row['lb'] ?? '');
            $cityOrigin['counts'][] = (int) ($row['c'] ?? 0);
        }
    }

    $countSql = "
        SELECT COUNT(*) FROM visitor_logs vl
        WHERE vl.deleted_at IS NULL AND vl.created_at >= ? AND vl.created_at <= ? {$cardVl}
    ";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute([$startDateStr, $endDateStr]);
    $totalRows = (int) ($stmt->fetchColumn() ?: 0);

    $listSql = "
        SELECT
            vl.first_name,
            vl.middle_name,
            vl.last_name,
            vl.is_resident,
            vl.purpose,
            vl.had_booking,
            vl.address,
            vl.created_at,
            r.purok AS resident_purok
        FROM visitor_logs vl
        LEFT JOIN {$residents} r ON vl.resident_id = r.id AND vl.is_resident = 1
        WHERE vl.deleted_at IS NULL AND vl.created_at >= ? AND vl.created_at <= ? {$cardVl}
        ORDER BY vl.created_at DESC
        LIMIT {$limit} OFFSET {$offset}
    ";
    $stmt = $pdo->prepare($listSql);
    $stmt->execute([$startDateStr, $endDateStr]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $fn = trim((string) ($row['first_name'] ?? ''));
        $mn = trim((string) ($row['middle_name'] ?? ''));
        $ln = trim((string) ($row['last_name'] ?? ''));
        $name = trim(implode(' ', array_filter([$fn, $mn, $ln])));
        if ($name === '') {
            $name = '—';
        }
        $isRes = (int) ($row['is_resident'] ?? 0) === 1;
        $purokVal = trim((string) ($row['resident_purok'] ?? ''));
        $addr = trim((string) ($row['address'] ?? ''));
        $cityHint = $addr;
        if ($addr !== '' && str_contains($addr, ',')) {
            $parts = array_map('trim', explode(',', $addr));
            $last = end($parts);
            if ($last !== false && $last !== '') {
                $cityHint = $last;
            }
        }
        if ($cityHint === '') {
            $cityHint = '—';
        }
        $purokCity = $isRes
            ? ($purokVal !== '' ? $purokVal : 'Unknown')
            : (strlen($cityHint) > 80 ? substr($cityHint, 0, 77) . '…' : $cityHint);

        $rows[] = [
            'full_name' => $name,
            'visitor_type' => $isRes ? 'Resident' : 'Non-Resident',
            'purok_or_city' => $purokCity,
            'service' => (string) ($row['purpose'] ?? '—'),
            'visit_type' => (int) ($row['had_booking'] ?? 0) === 1 ? 'Online appointment' : 'Walk-in',
            'created_at' => $row['created_at'] ?? null,
        ];
    }
} catch (Throwable $e) {
    error_log('visitor-modal: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load visitor analytics']);
    exit;
}

$out = [
    'success' => true,
    'filter' => $filter,
    'card' => $card,
    'rows' => $rows,
    'total_rows' => $totalRows,
    'limit' => $limit,
    'offset' => $offset,
];
if (!$skipCharts) {
    $out['charts'] = [
        'trend' => $trend,
        'resident_vs_non' => $residentVsNon,
        'purok' => $purok,
        'services' => $services,
        'walkin_vs_online' => $walkOnline,
        'city_origin' => $cityOrigin,
    ];
} else {
    $out['skip_charts'] = true;
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);
