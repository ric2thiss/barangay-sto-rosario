<?php
/**
 * Visitor Analytics API — READ-ONLY (SELECT only).
 * Aggregates visitor_logs with optional join to profiling residents for demographics.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../auth/helpers.php';

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$fromDate = $_GET['from'] ?? date('Y-m-01');
$toDate = $_GET['to'] ?? date('Y-m-t');
$purposeFilter = isset($_GET['purpose']) ? trim((string) $_GET['purpose']) : '';
$genderFilter = isset($_GET['gender']) ? trim((string) $_GET['gender']) : '';
$purokFilter = isset($_GET['purok']) ? trim((string) $_GET['purok']) : '';
$trendGranularity = $_GET['trend'] ?? 'day';
if (!in_array($trendGranularity, ['day', 'week', 'month'], true)) {
    $trendGranularity = 'day';
}

$profilingDb = defined('PROFILING_DB_NAME') ? PROFILING_DB_NAME : 'profiling-system';
$residentsTable = '`' . $profilingDb . '`.`residents`';

/**
 * SQL fragment: visitor identity for distinct / repeat counts
 */
$visitorKeySql = <<<SQL
CASE
    WHEN vl.is_resident = 1 AND vl.resident_id IS NOT NULL THEN CONCAT('R:', vl.resident_id)
    ELSE CONCAT(
        'N:',
        LOWER(TRIM(COALESCE(vl.first_name, ''))),
        '|',
        LOWER(TRIM(COALESCE(vl.last_name, ''))),
        '|',
        COALESCE(CAST(vl.birthdate AS CHAR), '')
    )
END
SQL;

try {
    $db = (new Database())->connect();

    $baseParams = [$fromDate, $toDate];
    if ($purposeFilter !== '') {
        $baseParams[] = $purposeFilter;
    }

    $genderClause = '';
    $purokClause = '';
    $extraParams = [];
    if ($genderFilter !== '') {
        $genderClause = ' AND COALESCE(r.sex, IF(vl.is_resident = 1, \'Unknown\', \'Non-resident\')) = ?';
        $extraParams[] = $genderFilter;
    }
    if ($purokFilter !== '') {
        $purokClause = ' AND COALESCE(NULLIF(TRIM(r.purok), \'\'), IF(vl.is_resident = 1, \'Unknown\', \'Non-resident\')) = ?';
        $extraParams[] = $purokFilter;
    }

    $join = 'FROM visitor_logs vl LEFT JOIN ' . $residentsTable . ' r ON vl.resident_id = r.id AND vl.is_resident = 1';

    $whereDatePurpose = 'WHERE vl.deleted_at IS NULL AND DATE(vl.created_at) BETWEEN ? AND ?';
    $summaryParams = [$fromDate, $toDate];
    if ($purposeFilter !== '') {
        $whereDatePurpose .= ' AND vl.purpose = ?';
        $summaryParams[] = $purposeFilter;
    }
    $whereFull = $whereDatePurpose . $genderClause . $purokClause;
    $allParams = array_merge($summaryParams, $extraParams);

    // --- Summary ---
    $summarySql = "
        SELECT
            COUNT(*) AS total_visits,
            COUNT(DISTINCT ({$visitorKeySql})) AS unique_visitors
        {$join}
        {$whereFull}
    ";
    $stmt = $db->prepare($summarySql);
    $stmt->execute($allParams);
    $sumRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_visits' => 0, 'unique_visitors' => 0];
    $totalVisits = (int) ($sumRow['total_visits'] ?? 0);
    $uniqueVisitors = (int) ($sumRow['unique_visitors'] ?? 0);

    $repeatSql = "
        SELECT COUNT(*) AS repeat_people FROM (
            SELECT 1
            {$join}
            {$whereFull}
            GROUP BY ({$visitorKeySql})
            HAVING COUNT(*) > 1
        ) t
    ";
    $stmt = $db->prepare($repeatSql);
    $stmt->execute($allParams);
    $repeatPeople = (int) ($stmt->fetchColumn() ?: 0);

    $avgVisitsPerPerson = $uniqueVisitors > 0 ? round($totalVisits / $uniqueVisitors, 2) : 0;

    // --- Trends (bar) ---
    if ($trendGranularity === 'day') {
        $bucketExpr = 'DATE(vl.created_at)';
        $labelExpr = 'DATE(vl.created_at)';
    } elseif ($trendGranularity === 'week') {
        $bucketExpr = "DATE_FORMAT(vl.created_at, '%x-%v')";
        $labelExpr = "CONCAT('W', DATE_FORMAT(vl.created_at, '%v'), ' ', DATE_FORMAT(vl.created_at, '%Y'))";
    } else {
        $bucketExpr = "DATE_FORMAT(vl.created_at, '%Y-%m')";
        $labelExpr = "DATE_FORMAT(vl.created_at, '%Y-%m')";
    }

    $trendSql = "
        SELECT {$labelExpr} AS label, COUNT(*) AS cnt
        {$join}
        {$whereFull}
        GROUP BY {$bucketExpr}, {$labelExpr}
        ORDER BY {$bucketExpr} ASC
    ";
    $stmt = $db->prepare($trendSql);
    $stmt->execute($allParams);
    $trends = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Peak hours ---
    $hourSql = "
        SELECT HOUR(vl.created_at) AS hr, COUNT(*) AS cnt
        {$join}
        {$whereFull}
        GROUP BY HOUR(vl.created_at)
        ORDER BY hr ASC
    ";
    $stmt = $db->prepare($hourSql);
    $stmt->execute($allParams);
    $peakHours = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Day of week ---
    $dowSql = "
        SELECT DAYNAME(vl.created_at) AS day_name, DAYOFWEEK(vl.created_at) AS dow, COUNT(*) AS cnt
        {$join}
        {$whereFull}
        GROUP BY DAYOFWEEK(vl.created_at), DAYNAME(vl.created_at)
        ORDER BY DAYOFWEEK(vl.created_at) ASC
    ";
    $stmt = $db->prepare($dowSql);
    $stmt->execute($allParams);
    $dowRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Frequent visitors (top 10) ---
    $freqSql = "
        SELECT
            ({$visitorKeySql}) AS vkey,
            COUNT(*) AS visits,
            MAX(
                CASE
                    WHEN vl.is_resident = 1 AND vl.resident_id IS NOT NULL THEN
                        TRIM(CONCAT(COALESCE(r.first_name, vl.first_name, ''), ' ', COALESCE(r.surname, vl.last_name, '')))
                    ELSE TRIM(CONCAT(COALESCE(vl.first_name, ''), ' ', COALESCE(vl.last_name, '')))
                END
            ) AS display_name
        {$join}
        {$whereFull}
        GROUP BY ({$visitorKeySql})
        ORDER BY visits DESC
        LIMIT 10
    ";
    $stmt = $db->prepare($freqSql);
    $stmt->execute($allParams);
    $frequent = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Purpose ---
    $purposeSql = "
        SELECT COALESCE(NULLIF(TRIM(vl.purpose), ''), 'Not specified') AS label, COUNT(*) AS cnt
        {$join}
        {$whereFull}
        GROUP BY COALESCE(NULLIF(TRIM(vl.purpose), ''), 'Not specified')
        ORDER BY cnt DESC
    ";
    $stmt = $db->prepare($purposeSql);
    $stmt->execute($allParams);
    $purposes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Gender ---
    $genderSql = "
        SELECT COALESCE(r.sex, IF(vl.is_resident = 1, 'Unknown', 'Non-resident')) AS label, COUNT(*) AS cnt
        {$join}
        {$whereFull}
        GROUP BY COALESCE(r.sex, IF(vl.is_resident = 1, 'Unknown', 'Non-resident'))
        ORDER BY cnt DESC
    ";
    $stmt = $db->prepare($genderSql);
    $stmt->execute($allParams);
    $genderRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Age groups (visitor birthdate) ---
    $ageBucket = "
            CASE
                WHEN vl.birthdate IS NULL THEN 'Unknown'
                WHEN TIMESTAMPDIFF(YEAR, vl.birthdate, CURDATE()) < 18 THEN '0-17'
                WHEN TIMESTAMPDIFF(YEAR, vl.birthdate, CURDATE()) BETWEEN 18 AND 25 THEN '18-25'
                WHEN TIMESTAMPDIFF(YEAR, vl.birthdate, CURDATE()) BETWEEN 26 AND 40 THEN '26-40'
                ELSE '41+'
            END
    ";
    $ageSql = "
        SELECT ({$ageBucket}) AS label, COUNT(*) AS cnt
        {$join}
        {$whereFull}
        GROUP BY ({$ageBucket})
        ORDER BY
            CASE ({$ageBucket})
                WHEN '0-17' THEN 1
                WHEN '18-25' THEN 2
                WHEN '26-40' THEN 3
                WHEN '41+' THEN 4
                ELSE 5
            END
    ";
    $stmt = $db->prepare($ageSql);
    $stmt->execute($allParams);
    $ageGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Purok ---
    $purokSql = "
        SELECT
            COALESCE(NULLIF(TRIM(r.purok), ''), IF(vl.is_resident = 1, 'Unknown', 'Non-resident')) AS label,
            COUNT(*) AS cnt
        {$join}
        {$whereFull}
        GROUP BY COALESCE(NULLIF(TRIM(r.purok), ''), IF(vl.is_resident = 1, 'Unknown', 'Non-resident'))
        ORDER BY cnt DESC
        LIMIT 30
    ";
    $stmt = $db->prepare($purokSql);
    $stmt->execute($allParams);
    $purokRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Barangay (residents); non-resident bucket ---
    $brgySql = "
        SELECT
            COALESCE(NULLIF(TRIM(r.barangay), ''), IF(vl.is_resident = 1, 'Unknown', 'Non-resident')) AS label,
            COUNT(*) AS cnt
        {$join}
        {$whereFull}
        GROUP BY COALESCE(NULLIF(TRIM(r.barangay), ''), IF(vl.is_resident = 1, 'Unknown', 'Non-resident'))
        ORDER BY cnt DESC
        LIMIT 25
    ";
    $stmt = $db->prepare($brgySql);
    $stmt->execute($allParams);
    $barangayRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Civil status ---
    $civilSql = "
        SELECT
            COALESCE(NULLIF(TRIM(r.civil_status), ''), IF(vl.is_resident = 1, 'Unknown', 'Non-resident')) AS label,
            COUNT(*) AS cnt
        {$join}
        {$whereFull}
        GROUP BY COALESCE(NULLIF(TRIM(r.civil_status), ''), IF(vl.is_resident = 1, 'Unknown', 'Non-resident'))
        ORDER BY cnt DESC
        LIMIT 20
    ";
    $stmt = $db->prepare($civilSql);
    $stmt->execute($allParams);
    $civilRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Filter options (same date range, purpose-only base for lists) ---
    $optPurposeSql = "
        SELECT DISTINCT vl.purpose FROM visitor_logs vl
        WHERE vl.deleted_at IS NULL AND DATE(vl.created_at) BETWEEN ? AND ? AND TRIM(COALESCE(vl.purpose, '')) <> ''
        ORDER BY vl.purpose ASC
    ";
    $stmt = $db->prepare($optPurposeSql);
    $stmt->execute([$fromDate, $toDate]);
    $purposeOptions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $optGenderSql = "
        SELECT DISTINCT COALESCE(r.sex, IF(vl.is_resident = 1, 'Unknown', 'Non-resident')) AS g
        {$join}
        WHERE vl.deleted_at IS NULL AND DATE(vl.created_at) BETWEEN ? AND ?
        ORDER BY g ASC
    ";
    $stmt = $db->prepare($optGenderSql);
    $stmt->execute([$fromDate, $toDate]);
    $genderOptions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $optPurokSql = "
        SELECT DISTINCT COALESCE(NULLIF(TRIM(r.purok), ''), IF(vl.is_resident = 1, 'Unknown', 'Non-resident')) AS p
        {$join}
        WHERE vl.deleted_at IS NULL AND DATE(vl.created_at) BETWEEN ? AND ?
        ORDER BY p ASC
    ";
    $stmt = $db->prepare($optPurokSql);
    $stmt->execute([$fromDate, $toDate]);
    $purokOptions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'from' => $fromDate,
        'to' => $toDate,
        'filters' => [
            'purpose' => $purposeFilter,
            'gender' => $genderFilter,
            'purok' => $purokFilter,
            'trend' => $trendGranularity,
        ],
        'filter_options' => [
            'purposes' => array_values(array_filter($purposeOptions)),
            'genders' => array_values(array_filter($genderOptions, static function ($g) {
                return $g !== null && $g !== '';
            })),
            'puroks' => array_values(array_filter($purokOptions)),
        ],
        'summary' => [
            'total_visits' => $totalVisits,
            'unique_visitors' => $uniqueVisitors,
            'repeat_visitor_people' => $repeatPeople,
            'avg_visits_per_person' => $avgVisitsPerPerson,
            'avg_duration_minutes' => null,
            'duration_available' => false,
        ],
        'trends' => array_map(static function ($r) {
            return ['label' => (string) $r['label'], 'count' => (int) $r['cnt']];
        }, $trends),
        'peak_hours' => array_map(static function ($r) {
            return ['hour' => (int) $r['hr'], 'count' => (int) $r['cnt']];
        }, $peakHours),
        'day_of_week' => array_map(static function ($r) {
            return ['day' => (string) $r['day_name'], 'count' => (int) $r['cnt']];
        }, $dowRows),
        'frequent_visitors' => array_map(static function ($r) {
            $name = trim((string) ($r['display_name'] ?? ''));
            if ($name === '') {
                $name = 'Visitor';
            }
            return ['name' => $name, 'visits' => (int) $r['visits']];
        }, $frequent),
        'purposes' => array_map(static function ($r) {
            return ['label' => (string) $r['label'], 'count' => (int) $r['cnt']];
        }, $purposes),
        'gender' => array_map(static function ($r) {
            return ['label' => (string) $r['label'], 'count' => (int) $r['cnt']];
        }, $genderRows),
        'age_groups' => array_map(static function ($r) {
            return ['label' => (string) $r['label'], 'count' => (int) $r['cnt']];
        }, $ageGroups),
        'purok' => array_map(static function ($r) {
            return ['label' => (string) $r['label'], 'count' => (int) $r['cnt']];
        }, $purokRows),
        'barangay' => array_map(static function ($r) {
            return ['label' => (string) $r['label'], 'count' => (int) $r['cnt']];
        }, $barangayRows),
        'civil_status' => array_map(static function ($r) {
            return ['label' => (string) $r['label'], 'count' => (int) $r['cnt']];
        }, $civilRows),
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => $e->getMessage(),
    ]);
}
