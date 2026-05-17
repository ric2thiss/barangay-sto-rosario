<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../auth/helpers.php';

if (!isAuthenticated()) {
    http_response_code(401);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Unauthorized';
    exit;
}

$format = strtolower(trim((string) ($_GET['format'] ?? 'csv')));
if (!in_array($format, ['csv', 'doc', 'html'], true)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid format (use csv, doc, or html)';
    exit;
}

$mode = strtolower(trim((string) ($_GET['mode'] ?? 'logs'))) === 'event' ? 'event' : 'logs';
$search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
$fromDate = isset($_GET['from']) ? trim((string) $_GET['from']) : null;
$toDate = isset($_GET['to']) ? trim((string) $_GET['to']) : null;
if ($fromDate === '') {
    $fromDate = null;
}
if ($toDate === '') {
    $toDate = null;
}

$sort = (string) ($_GET['sort'] ?? 'timestamp');
$order = (string) ($_GET['order'] ?? 'desc');
$allowedSort = ['timestamp', 'employee_id', 'full_name'];
if (!in_array($sort, $allowedSort, true)) {
    $sort = 'timestamp';
}
$order = strtolower($order) === 'asc' ? 'asc' : 'desc';

$activityFilter = null;
$rawAct = isset($_GET['activity_id']) ? trim((string) $_GET['activity_id']) : '';
if ($rawAct === '0') {
    $activityFilter = 0;
} elseif ($rawAct !== '' && ctype_digit($rawAct)) {
    $activityFilter = (int) $rawAct;
}

$eventId = isset($_GET['event_id']) ? (int) $_GET['event_id'] : 0;

$pdo = (new Database())->connect();
$attRepo = new AttendanceRepository($pdo);
$repRepo = new AttendanceReportRepository($pdo);

$logoUrl = rtrim(BASE_URL, '/') . '/utils/img/logo.png';
$title = 'Attendance Report';
$rangeLabel = '';

$rows = [];
$headers = [];

if ($mode === 'event' && $eventId > 0) {
    $rosterSort = $sort === 'employee_id' ? 'employee_id' : 'name';
    $bundle = $repRepo->getEventRosterPage($eventId, $search, $rosterSort, $order, 1, 20000);
    $act = $bundle['activity'] ?? null;
    if (!$act) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Event not found';
        exit;
    }
    $rangeLabel = ($act['activity_date'] ?? '') . ' · ' . ($act['name'] ?? '');
    $headers = ['Employee Name', 'Date', 'Morning In', 'Morning Out', 'Afternoon In', 'Afternoon Out', 'Status', 'Event'];

    // For each roster row, fetch per-window times
    $delFilter = SchemaColumnCache::attendancesHasDeletedAt() ? 'a.deleted_at IS NULL AND ' : '';
    foreach ($bundle['rows'] as $r) {
        $morningIn = '—';
        $morningOut = '—';
        $afternoonIn = '—';
        $afternoonOut = '—';

        try {
            $winQuery = $pdo->prepare("
                SELECT a.window, a.timestamp
                FROM attendances AS a
                WHERE {$delFilter}a.employee_id = ?
                  AND DATE(COALESCE(a.timestamp, a.created_at)) = ?
                ORDER BY a.timestamp ASC
            ");
            $winQuery->execute([$r['employee_id'], $r['date']]);
            $winRows = $winQuery->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($winRows as $wr) {
                $wNorm = AttendanceAnalyticsService::normalizeLabel((string)($wr['window'] ?? ''));
                $wTs = (string)($wr['timestamp'] ?? '');
                if ($wTs === '') continue;
                try {
                    $wDt = new DateTime($wTs, new DateTimeZone('Asia/Manila'));
                    $wTime = $wDt->format('h:i:s A');
                } catch (Exception $ex) {
                    $wTime = $wTs;
                }
                if ($wNorm === 'morning_in') $morningIn = $wTime;
                elseif ($wNorm === 'morning_out') $morningOut = $wTime;
                elseif ($wNorm === 'afternoon_in') $afternoonIn = $wTime;
                elseif ($wNorm === 'afternoon_out') $afternoonOut = $wTime;
            }
        } catch (Throwable $ex) {
            if ($r['time_in'] !== '—') $morningIn = $r['time_in'];
            if ($r['time_out'] !== '—') $afternoonOut = $r['time_out'];
        }

        $rows[] = [
            $r['full_name'],
            $r['date'],
            $morningIn,
            $morningOut,
            $afternoonIn,
            $afternoonOut,
            $r['status'],
            $r['event_name'],
        ];
    }
} else {
    $rangeLabel = trim(($fromDate ?? '') . ' to ' . ($toDate ?? ''));
    $list = $attRepo->getReportLogsExport($search, $fromDate, $toDate, $activityFilter, $sort, $order, 5000);
    $showEvent = ($activityFilter !== null);
    $headers = $showEvent
        ? ['Employee Name', 'Date', 'Morning In', 'Morning Out', 'Afternoon In', 'Afternoon Out', 'Event']
        : ['Employee Name', 'Date', 'Morning In', 'Morning Out', 'Afternoon In', 'Afternoon Out'];

    // Group by employee+date
    $grouped = [];
    foreach ($list as $att) {
        $a = is_object($att) ? json_decode(json_encode($att), true) : $att;
        $ts = (string) ($a['attendance_time'] ?? '');
        $datePart = '—';
        if ($ts !== '') {
            try {
                $dt = new DateTime($ts, new DateTimeZone('Asia/Manila'));
                $datePart = $dt->format('Y-m-d');
            } catch (Exception $e) {}
        }
        $empId = (string) ($a['employee_id'] ?? '');
        $fullName = (string) ($a['full_name'] ?? '');
        $wl = (string) ($a['window_label'] ?? $a['window'] ?? '');
        $norm = AttendanceAnalyticsService::normalizeLabel($wl);
        $actName = (string) ($a['activity_name'] ?? '—');

        $key = $empId . '|' . $datePart;
        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'employee_id' => $empId,
                'full_name' => $fullName,
                'date' => $datePart,
                'morning_in' => '—',
                'morning_out' => '—',
                'afternoon_in' => '—',
                'afternoon_out' => '—',
                'activity_name' => $actName,
            ];
        }

        $timePart = '—';
        if ($ts !== '') {
            try {
                $dt = new DateTime($ts, new DateTimeZone('Asia/Manila'));
                $timePart = $dt->format('h:i:s A');
            } catch (Exception $e) {
                $timePart = $ts;
            }
        }

        if ($norm === 'morning_in') {
            $grouped[$key]['morning_in'] = $timePart;
        } elseif ($norm === 'morning_out') {
            $grouped[$key]['morning_out'] = $timePart;
        } elseif ($norm === 'afternoon_in') {
            $grouped[$key]['afternoon_in'] = $timePart;
        } elseif ($norm === 'afternoon_out') {
            $grouped[$key]['afternoon_out'] = $timePart;
        } elseif ($timePart !== '—' && $grouped[$key]['morning_in'] === '—') {
            $grouped[$key]['morning_in'] = $timePart;
        }

        if ($actName !== '—') {
            $grouped[$key]['activity_name'] = $actName;
        }
    }

    foreach ($grouped as $g) {
        $row = [
            $g['full_name'],
            $g['date'],
            $g['morning_in'],
            $g['morning_out'],
            $g['afternoon_in'],
            $g['afternoon_out'],
        ];
        if ($showEvent) {
            $row[] = $g['activity_name'];
        }
        $rows[] = $row;
    }
}

function h($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="attendance_report_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, [$title]);
    fputcsv($out, ['Period / event: ' . $rangeLabel]);
    fputcsv($out, []);
    fputcsv($out, $headers);
    foreach ($rows as $r) {
        fputcsv($out, $r);
    }
    fclose($out);
    exit;
}


// Determine if we're showing event or status columns
$showEvent = false;
$showStatus = false;
if ($mode === 'event') {
    $showEvent = true;
    $showStatus = true;
} elseif ($activityFilter !== null) {
    $showEvent = true;
}

// Build the grouped-header HTML table matching the reference design
function buildGroupedTable($rows, $showEvent, $showStatus) {
    $extraCols = 0;
    if ($showEvent) $extraCols++;
    if ($showStatus) $extraCols++;

    $t  = '<table>';
    $t .= '<thead>';

    // Row 1: grouped headers
    $t .= '<tr>';
    $t .= '<th rowspan="2" class="th-main">Employee Name</th>';
    $t .= '<th rowspan="2" class="th-main">Date &darr;</th>';
    $t .= '<th colspan="2" class="th-group">Morning</th>';
    $t .= '<th colspan="2" class="th-group">Afternoon</th>';
    if ($showStatus) $t .= '<th rowspan="2" class="th-main">Status</th>';
    if ($showEvent)  $t .= '<th rowspan="2" class="th-main">Event</th>';
    $t .= '</tr>';

    // Row 2: sub-headers
    $t .= '<tr>';
    $t .= '<th class="th-sub">In</th>';
    $t .= '<th class="th-sub">Out</th>';
    $t .= '<th class="th-sub">In</th>';
    $t .= '<th class="th-sub">Out</th>';
    $t .= '</tr>';

    $t .= '</thead><tbody>';

    if (empty($rows)) {
        $colspan = 6 + $extraCols;
        $t .= '<tr><td colspan="' . $colspan . '" style="text-align:center; padding:20px; color:#888;">No attendance records found.</td></tr>';
    } else {
        foreach ($rows as $r) {
            $t .= '<tr>';
            // Employee Name
            $t .= '<td>' . h($r[0]) . '</td>';
            // Date
            $t .= '<td>' . h($r[1]) . '</td>';
            // Morning In
            $t .= '<td>' . h($r[2]) . '</td>';
            // Morning Out
            $t .= '<td>' . h($r[3]) . '</td>';
            // Afternoon In
            $t .= '<td>' . h($r[4]) . '</td>';
            // Afternoon Out
            $t .= '<td>' . h($r[5]) . '</td>';
            // Status (event mode only)
            if ($showStatus) {
                $t .= '<td>' . h($r[6] ?? '') . '</td>';
            }
            // Event
            if ($showEvent) {
                $evIdx = $showStatus ? 7 : 6;
                $t .= '<td>' . h($r[$evIdx] ?? '') . '</td>';
            }
            $t .= '</tr>';
        }
    }

    $t .= '</tbody></table>';
    return $t;
}

$htmlTable = buildGroupedTable($rows, $showEvent, $showStatus);

$docHeadXml = '<!--[if gte mso 9]><xml><w:WordDocument><w:View>Print</w:View><w:Zoom>100</w:Zoom><w:Orientation>Landscape</w:Orientation></w:WordDocument></xml><![endif]-->';
$docStyles = '<style>@page Section1 { size: 11in 8.5in; mso-page-orientation: landscape; } div.Section1 { page: Section1; } .center { text-align: center; } table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #ccc; padding: 8px 12px; text-align: left; } th { background-color: #f9fafb; }</style>';
$docHeader = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word"><head><meta charset="utf-8"><title>' . h($title) . '</title>' . $docStyles . '</head><body>' . $docHeadXml;
$docHeader .= '<div class="Section1"><div class="center">';
$docHeader .= '<img src="' . h($logoUrl) . '" width="100" height="auto"><br>';
$docHeader .= '<h1>' . h($title) . '</h1>';
$docHeader .= '<p>Selected range / event: ' . h($rangeLabel) . '</p>';
$docHeader .= '</div>' . $htmlTable . '</div></body></html>';

if ($format === 'doc') {
    header('Content-Type: application/msword; charset=utf-8');
    header('Content-Disposition: attachment; filename="attendance_report_' . date('Y-m-d') . '.doc"');
    echo $docHeader;
    exit;
}

// html = printable plain view
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= h($title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0; padding: 32px;
            color: #000; background: #fff;
        }
        .header-content {
            text-align: center; margin-bottom: 28px;
        }
        .logo { max-height: 72px; width: auto; margin: 0 auto 12px; display: block; }
        h1 { font-size: 22px; font-weight: 700; color: #000; margin-bottom: 4px; }
        .meta { font-size: 13px; color: #333; font-weight: 500; margin-bottom: 8px; }
        .record-count { font-size: 12px; color: #555; }

        .table-wrapper {
            width: 100%; overflow-x: auto;
        }
        table {
            border-collapse: collapse; width: 100%;
            font-size: 13px; line-height: 1.5;
        }
        th, td {
            border: 1px solid #333;
            padding: 8px 12px;
            text-align: left;
            color: #000;
        }
        thead th {
            background-color: #f2f2f2;
            font-weight: 600;
            white-space: nowrap;
        }
        thead th.th-group {
            text-align: center;
            font-weight: 700;
        }
        thead th.th-sub {
            font-weight: 600;
        }
        thead th.th-main {
            font-weight: 600;
        }
        tbody tr:hover { background-color: #fafafa; }

        .no-print {
            margin-bottom: 16px; text-align: right;
        }
        .btn-print {
            padding: 8px 20px; border: 1px solid #999;
            background: #fff; border-radius: 4px; cursor: pointer;
            font-size: 13px; font-weight: 500; color: #000;
        }
        .btn-print:hover { background: #f0f0f0; }

        @media print {
            .no-print { display: none !important; }
            @page { size: A4 landscape; margin: 10mm; }
            body { margin: 0; padding: 12px; }
            thead th { background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" class="btn-print" onclick="window.print()">🖨️ Print Report</button>
    </div>
    <div class="header-content">
        <img class="logo" src="<?= h($logoUrl) ?>" alt="Logo">
        <h1><?= h($title) ?></h1>
        <div class="meta"><?= h($rangeLabel) ?: 'All records' ?></div>
        <div class="record-count"><?= count($rows) ?> record<?= count($rows) !== 1 ? 's' : '' ?> found</div>
    </div>
    <div class="table-wrapper">
        <?= $htmlTable ?>
    </div>
</body>
</html>
