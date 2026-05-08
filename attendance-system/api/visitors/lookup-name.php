<?php
/**
 * API: Suggest profiling residents and previous non-resident visitors while typing
 * GET /api/visitors/lookup-name.php?q=search&limit=15
 */
require_once __DIR__ . "/../../bootstrap.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Method not allowed"]);
    exit;
}

$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$limit = isset($_GET['limit']) ? max(1, min(25, (int) $_GET['limit'])) : 12;

if (mb_strlen($q) < 2) {
    echo json_encode([
        'success' => true,
        'profiling' => [],
        'previous_visitors' => [],
        'message' => 'Query too short'
    ]);
    exit;
}

$profilingDb = defined('PROFILING_DB_NAME') ? PROFILING_DB_NAME : 'profiling-system';
$like = '%' . $q . '%';

try {
    $db = (new Database())->connect();
    $p = '`' . str_replace('`', '', $profilingDb) . '`';

    $sqlRes = "
        SELECT
            r.id AS resident_id,
            r.first_name,
            r.middle_name,
            r.surname AS last_name,
            r.birthdate,
            r.purok,
            r.barangay,
            r.municipality,
            r.province
        FROM {$p}.`residents` r
        WHERE
            CONCAT(COALESCE(r.first_name,''), ' ', COALESCE(r.middle_name,''), ' ', COALESCE(r.surname,'')) LIKE :like
            OR CAST(r.id AS CHAR) LIKE :like2
        ORDER BY r.surname ASC, r.first_name ASC
        LIMIT " . (int) $limit;

    $stmt = $db->prepare($sqlRes);
    $stmt->execute([':like' => $like, ':like2' => $q . '%']);
    $resRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $profiling = [];
    foreach ($resRows as $row) {
        $parts = array_filter([
            $row['first_name'] ?? '',
            $row['middle_name'] ?? '',
            $row['last_name'] ?? '',
        ]);
        $full = preg_replace('/\s+/', ' ', trim(implode(' ', $parts)));
        $addrParts = array_filter([
            !empty($row['purok']) ? $row['purok'] : '',
            !empty($row['barangay']) ? 'Brgy. ' . $row['barangay'] : '',
            $row['municipality'] ?? '',
            $row['province'] ?? '',
        ]);
        $profiling[] = [
            'source' => 'profiling',
            'resident_id' => (int) $row['resident_id'],
            'first_name' => $row['first_name'] ?? '',
            'middle_name' => $row['middle_name'] ?? '',
            'last_name' => $row['last_name'] ?? '',
            'full_name' => $full,
            'birthdate' => $row['birthdate'] ?? null,
            'address_hint' => implode(', ', $addrParts),
        ];
    }

    $hasDeletedAt = false;
    try {
        $chk = $db->query("SHOW COLUMNS FROM `visitor_logs` LIKE 'deleted_at'");
        $hasDeletedAt = $chk && $chk->rowCount() > 0;
    } catch (Exception $e) {
        $hasDeletedAt = false;
    }

    $delClause = $hasDeletedAt ? ' AND (deleted_at IS NULL) ' : '';

    $sqlPrev = "
        SELECT first_name, middle_name, last_name, birthdate, address
        FROM `visitor_logs`
        WHERE is_resident = 0
        {$delClause}
          AND (
            CONCAT(COALESCE(first_name,''), ' ', COALESCE(middle_name,''), ' ', COALESCE(last_name,'')) LIKE :like
          )
        ORDER BY created_at DESC
        LIMIT 50
    ";
    $st2 = $db->prepare($sqlPrev);
    $st2->execute([':like' => $like]);
    $prevRaw = $st2->fetchAll(PDO::FETCH_ASSOC);

    $seen = [];
    $previousVisitors = [];
    foreach ($prevRaw as $row) {
        $key = strtolower(trim(($row['first_name'] ?? '') . '|' . ($row['last_name'] ?? '') . '|' . ($row['birthdate'] ?? '')));
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $parts = array_filter([
            $row['first_name'] ?? '',
            $row['middle_name'] ?? '',
            $row['last_name'] ?? '',
        ]);
        $full = preg_replace('/\s+/', ' ', trim(implode(' ', $parts)));
        $previousVisitors[] = [
            'source' => 'visitor_log',
            'first_name' => $row['first_name'] ?? '',
            'middle_name' => $row['middle_name'] ?? '',
            'last_name' => $row['last_name'] ?? '',
            'full_name' => $full,
            'birthdate' => $row['birthdate'] ?? null,
            'address' => $row['address'] ?? '',
        ];
        if (count($previousVisitors) >= $limit) {
            break;
        }
    }

    echo json_encode([
        'success' => true,
        'profiling' => $profiling,
        'previous_visitors' => $previousVisitors,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Lookup failed',
        'message' => $e->getMessage()
    ]);
}
