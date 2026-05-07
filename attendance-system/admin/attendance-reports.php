<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth/helpers.php';
requireAuth();

include_once __DIR__ . '/../shared/components/Sidebar.php';
include_once __DIR__ . '/../shared/components/Breadcrumb.php';
include_once __DIR__ . '/../shared/components/HelpPopover.php';

$currentUser = currentUser();
$userName = $currentUser ? ($currentUser['full_name'] ?? $currentUser['username']) : 'Guest';

$logoUrl = rtrim(BASE_URL, '/') . '/utils/img/logo.png';
$exportBase = rtrim(BASE_URL, '/') . '/api/attendance/report-export.php';
$finesApi = rtrim(BASE_URL, '/') . '/api/attendance/event-fines.php';

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? max(5, min(100, (int) $_GET['per_page'])) : 25;
$search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
$fromDate = isset($_GET['from']) ? trim((string) $_GET['from']) : '';
$toDate = isset($_GET['to']) ? trim((string) $_GET['to']) : '';
if ($fromDate === '') {
    $fromDate = null;
}
if ($toDate === '') {
    $toDate = null;
}

$eventId = isset($_GET['event_id']) ? (int) $_GET['event_id'] : 0;

$activityFilterInput = isset($_GET['activity_id']) ? trim((string) $_GET['activity_id']) : '';
$activityFilter = null;
if ($eventId <= 0) {
    if ($activityFilterInput === '0') {
        $activityFilter = 0;
    } elseif ($activityFilterInput !== '' && ctype_digit($activityFilterInput)) {
        $activityFilter = (int) $activityFilterInput;
    }
}

$sort = isset($_GET['sort']) ? trim((string) $_GET['sort']) : 'timestamp';
$order = isset($_GET['order']) ? trim((string) $_GET['order']) : 'desc';

$controller = new AttendanceReportController();
$view = $controller->getPageData(
    $eventId > 0 ? 'event' : 'logs',
    $page,
    $perPage,
    $search,
    $fromDate,
    $toDate,
    $activityFilter,
    $eventId,
    $sort,
    $order
);

$mode = $view['mode'];
$activityList = $view['activity_list'] ?? [];
$roster = $view['roster'];
$logsData = $view['logs'];

$finesMeetings = [];
try {
    $fineRepo = new EventFineRepository((new Database())->connect());
    $finesMeetings = $fineRepo->listActivitiesWithFineAmounts();
} catch (Throwable $e) {
    $finesMeetings = [];
}
$finesMeetingsWithRate = 0;
$finesConfiguredTotalPhp = 0.0;
foreach ($finesMeetings as $fm) {
    $a = (float) ($fm['fine_amount'] ?? 0);
    if ($a > 0) {
        $finesMeetingsWithRate++;
        $finesConfiguredTotalPhp += $a;
    }
}

$chartPie = null;
$chartBar = null;
if ($mode === 'event' && $roster && !empty($roster['summary'])) {
    $s = $roster['summary'];
    $chartPie = [
        'labels' => ['Present', 'Absent', 'Incomplete'],
        'values' => [(int) ($s['present'] ?? 0), (int) ($s['absent'] ?? 0), (int) ($s['incomplete'] ?? 0)],
    ];
    $barLabels = [];
    $barVals = [];
    foreach ($roster['rows'] as $rr) {
        if (($rr['fine'] ?? 0) > 0) {
            $barLabels[] = $rr['full_name'];
            $barVals[] = (float) $rr['fine'];
        }
    }
    $chartBar = ['labels' => $barLabels, 'values' => $barVals];
}

function ar_sort_link(string $col, string $label, string $currentSort, string $currentOrder, string $mode): string {
    $nextOrder = ($currentSort === $col && $currentOrder === 'asc') ? 'desc' : 'asc';
    $params = array_merge($_GET, ['sort' => $col, 'order' => $nextOrder, 'page' => 1]);
    $qs = http_build_query($params);
    $arrow = $currentSort === $col ? ($currentOrder === 'asc' ? ' ↑' : ' ↓') : '';
    return '<a href="?' . htmlspecialchars($qs, ENT_QUOTES, 'UTF-8') . '" class="text-blue-700 hover:underline">' . htmlspecialchars($label . $arrow, ENT_QUOTES, 'UTF-8') . '</a>';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>">
    <title>Attendance Reports</title>
    <link rel="stylesheet" href="../utils/styles/global.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <style>
        body { overflow-x: hidden; }
        .table-plain th, .table-plain td { border: 1px solid #d1d5db; padding: 8px 10px; text-align: left; font-size: 0.875rem; }
        .table-plain { border-collapse: collapse; width: 100%; }
        .table-plain thead th { background: #f3f4f6; font-weight: 600; }
        @media print {
            @page { size: A4 landscape; margin: 10mm; }
            body { background: #fff !important; }
            body .flex.min-h-screen > aside { display: none !important; }
            body .flex.min-h-screen > main {
                margin-left: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                padding: 12px !important;
            }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <?= Sidebar('Attendance Reports', null, $logoUrl) ?>

        <main class="flex-1 md:ml-64 p-6 transition-all duration-300 max-w-full">
            <header class="mb-6 no-print">
                <h1 class="text-2xl font-semibold text-gray-800">Attendance Reports</h1>
                <p class="text-gray-500 text-sm flex flex-wrap items-center gap-1.5">
                    <span><?= htmlspecialchars(getGreeting($userName), ENT_QUOTES, 'UTF-8') ?> — Printable listings, event roster, and per-meeting fines.</span>
                    <?= help_popover(
                        'Attendance Reports',
                        'Printable attendance listing, event roster, and per-meeting absence fines. Fine amounts are set in the Fines per meeting section below.',
                        'ar-header'
                    ) ?>
                </p>
                <?php Breadcrumb([
                    ['label' => 'Dashboard', 'link' => 'dashboard.php'],
                    ['label' => 'Attendance Reports', 'link' => 'attendance-reports.php'],
                ]); ?>
            </header>

            <form method="get" action="" class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 mb-6 space-y-4 no-print">
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4 items-end">
                    <div class="xl:col-span-2">
                        <label for="event_id" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1">
                            <span>Event / activity (roster view)</span>
                            <?= help_popover(
                                'Event / activity (roster view)',
                                'Select an event to list every employee as Present, Absent, or Incomplete for that event date. Fine amounts are configured in the fines section below.',
                                'ar-event'
                            ) ?>
                        </label>
                        <select name="event_id" id="event_id" class="w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-lg">
                            <option value="">— All attendance logs (use date range below) —</option>
                            <?php foreach ($activityList as $act): ?>
                                <?php
                                $aid = is_object($act) ? (int) ($act->id ?? 0) : (int) ($act['id'] ?? 0);
                                if ($aid <= 0) {
                                    continue;
                                }
                                $aname = is_object($act) ? (string) ($act->name ?? '') : (string) ($act['name'] ?? '');
                                $adate = is_object($act) ? (string) ($act->activity_date ?? '') : (string) ($act['activity_date'] ?? '');
                                ?>
                                <option value="<?= $aid ?>" <?= $eventId === $aid ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($aname . ' (' . $adate . ')', ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="from" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1">
                            <span>From (logs view)</span>
                            <?= help_popover(
                                'From (logs view)',
                                'Used when viewing all attendance logs (no event selected). Date fields are read-only while an event roster is open.',
                                'ar-from'
                            ) ?>
                        </label>
                        <input type="date" name="from" id="from" value="<?= htmlspecialchars((string) ($fromDate ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            class="w-full pl-3 pr-3 py-2 text-sm border border-gray-300 rounded-lg" <?= $eventId > 0 ? 'readonly' : '' ?>>
                    </div>
                    <div>
                        <label for="to" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1">
                            <span>To (logs view)</span>
                            <?= help_popover(
                                'To (logs view)',
                                'End of date range for the all-logs view. Ignored when a specific event roster is selected.',
                                'ar-to'
                            ) ?>
                        </label>
                        <input type="date" name="to" id="to" value="<?= htmlspecialchars((string) ($toDate ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            class="w-full pl-3 pr-3 py-2 text-sm border border-gray-300 rounded-lg" <?= $eventId > 0 ? 'readonly' : '' ?>>
                    </div>
                </div>
                <?php if ($eventId <= 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div class="md:col-span-1">
                        <label for="activity_id" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1">
                            <span>Activity tag (logs only)</span>
                            <?= help_popover(
                                'Activity tag (logs only)',
                                'Filter raw attendance logs by linked activity, including entries with no event (untagged). Not used for the event roster table.',
                                'ar-act'
                            ) ?>
                        </label>
                        <select name="activity_id" id="activity_id" class="w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-lg">
                            <option value="">All activities</option>
                            <option value="0" <?= $activityFilterInput === '0' ? 'selected' : '' ?>>No event (untagged)</option>
                            <?php foreach ($activityList as $act): ?>
                                <?php
                                $aid = is_object($act) ? (int) ($act->id ?? 0) : (int) ($act['id'] ?? 0);
                                if ($aid <= 0) {
                                    continue;
                                }
                                $aname = is_object($act) ? (string) ($act->name ?? '') : (string) ($act['name'] ?? '');
                                $adate = is_object($act) ? (string) ($act->activity_date ?? '') : (string) ($act['activity_date'] ?? '');
                                ?>
                                <option value="<?= $aid ?>" <?= (string) $aid === $activityFilterInput ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($aname . ' (' . $adate . ')', ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="per_page" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1">
                            <span>Rows per page</span>
                            <?= help_popover(
                                'Rows per page',
                                'How many rows appear on each page of the report table below. Applies to both roster and log views.',
                                'ar-perpage'
                            ) ?>
                        </label>
                        <select name="per_page" id="per_page" class="w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-lg">
                            <?php foreach ([10, 25, 50, 100] as $n): ?>
                                <option value="<?= $n ?>" <?= $perPage === $n ? 'selected' : '' ?>><?= $n ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php else: ?>
                    <input type="hidden" name="per_page" value="<?= (int) $perPage ?>">
                <?php endif; ?>
                <div class="flex flex-wrap gap-2 items-center">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg text-sm">Apply</button>
                    <a href="attendance-reports.php" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Reset</a>
                </div>
            </form>

            <section class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 mb-6 no-print" aria-labelledby="fines-overview-heading">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <div>
                        <h2 id="fines-overview-heading" class="text-lg font-semibold text-gray-800 flex flex-wrap items-center gap-1.5">
                            <span>Fines per meeting</span>
                            <?= help_popover(
                                'Fines per meeting',
                                'Absence fine (PHP) applied when an employee is marked absent on the event roster. Use search to find a meeting, then open the roster or set the amount.',
                                'ar-fines'
                            ) ?>
                        </h2>
                        <p class="text-sm text-gray-500 mt-0.5">Configure per-meeting absence fines; search the table to find a meeting quickly.</p>
                    </div>
                    <button type="button" id="btn-fines-modal" class="shrink-0 px-4 py-2 border border-amber-300 bg-amber-50 text-amber-900 rounded-lg text-sm font-medium hover:bg-amber-100">Set fine…</button>
                </div>
                <div class="mb-3">
                    <label for="fines-overview-search" class="sr-only">Search meetings</label>
                    <input type="search" id="fines-overview-search" placeholder="Search by meeting name, date, or ID…" autocomplete="off"
                        class="w-full max-w-md pl-3 pr-3 py-2 text-sm border border-gray-300 rounded-lg">
                </div>
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full text-sm" id="fines-overview-table">
                        <thead>
                            <tr class="bg-gray-50 text-left text-gray-700">
                                <th class="px-3 py-2 font-semibold border-b border-gray-200">Date</th>
                                <th class="px-3 py-2 font-semibold border-b border-gray-200">Meeting</th>
                                <th class="px-3 py-2 font-semibold border-b border-gray-200 whitespace-nowrap">Fine (PHP)</th>
                                <th class="px-3 py-2 font-semibold border-b border-gray-200 whitespace-nowrap">Last updated</th>
                                <th class="px-3 py-2 font-semibold border-b border-gray-200 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($finesMeetings)): ?>
                                <tr><td colspan="5" class="px-3 py-8 text-center text-gray-500">No activities yet. Create activities elsewhere, then set absence fines here.</td></tr>
                            <?php else: ?>
                                <?php foreach ($finesMeetings as $fm): ?>
                                    <?php
                                    $fid = (int) ($fm['activity_id'] ?? 0);
                                    $fname = (string) ($fm['activity_name'] ?? '');
                                    $fdate = $fm['activity_date'] ?? '';
                                    $fam = (float) ($fm['fine_amount'] ?? 0);
                                    $fup = $fm['fine_updated_at'] ?? '';
                                    $searchHay = strtolower($fname . ' ' . ($fdate ?: '') . ' ' . (string) $fid);
                                    ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50/80" data-search="<?= htmlspecialchars($searchHay, ENT_QUOTES, 'UTF-8') ?>">
                                        <td class="px-3 py-2 text-gray-700 whitespace-nowrap"><?= $fdate !== '' ? htmlspecialchars($fdate, ENT_QUOTES, 'UTF-8') : '—' ?></td>
                                        <td class="px-3 py-2 text-gray-900"><?= htmlspecialchars($fname, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="px-3 py-2 tabular-nums <?= $fam > 0 ? 'text-amber-900 font-medium' : 'text-gray-500' ?>"><?= htmlspecialchars(number_format($fam, 2), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="px-3 py-2 text-gray-600 text-xs whitespace-nowrap"><?= $fup !== '' ? htmlspecialchars($fup, ENT_QUOTES, 'UTF-8') : '—' ?></td>
                                        <td class="px-3 py-2 text-right whitespace-nowrap">
                                            <a href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['event_id' => $fid, 'page' => 1])), ENT_QUOTES, 'UTF-8') ?>" class="text-blue-700 hover:underline mr-2">Roster</a>
                                            <button type="button" class="text-amber-900 hover:underline font-medium js-open-fines-modal" data-activity-id="<?= $fid ?>">Set amount</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (!empty($finesMeetings)): ?>
                    <p class="text-xs text-gray-500 mt-3 flex flex-wrap items-center gap-1.5">
                        <span><?= (int) $finesMeetingsWithRate ?> meeting(s) with a non-zero fine · sum of amounts: <?= htmlspecialchars(number_format($finesConfiguredTotalPhp, 2), ENT_QUOTES, 'UTF-8') ?> PHP</span>
                        <?= help_popover(
                            'Total of configured fines',
                            'This sum is the total of configured per-absence fine amounts across meetings, not a payroll total. The roster applies the fine once per absent employee for that event.',
                            'ar-finesum'
                        ) ?>
                    </p>
                <?php endif; ?>
            </section>

            <?php if ($mode === 'event' && $roster && !empty($roster['activity'])): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 no-print">
                    <div class="bg-white rounded-xl border border-gray-100 shadow p-4">
                        <p class="text-xs font-medium text-gray-500 uppercase">Present</p>
                        <p class="text-2xl font-bold text-emerald-700"><?= (int) ($roster['summary']['present'] ?? 0) ?></p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow p-4">
                        <p class="text-xs font-medium text-gray-500 uppercase">Absent</p>
                        <p class="text-2xl font-bold text-red-700"><?= (int) ($roster['summary']['absent'] ?? 0) ?></p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow p-4">
                        <p class="text-xs font-medium text-gray-500 uppercase">Incomplete</p>
                        <p class="text-2xl font-bold text-amber-700"><?= (int) ($roster['summary']['incomplete'] ?? 0) ?></p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow p-4">
                        <p class="text-xs font-medium text-gray-500 uppercase">Total fines (PHP)</p>
                        <p class="text-2xl font-bold text-gray-800"><?= htmlspecialchars(number_format((float) ($roster['summary']['total_fines'] ?? 0), 2), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-xs text-gray-500 mt-1">Employees with fine: <?= (int) ($roster['summary']['employees_with_fine'] ?? 0) ?></p>
                    </div>
                </div>
                <?php if ($chartPie && array_sum($chartPie['values']) > 0): ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 no-print">
                    <div class="bg-white rounded-xl border border-gray-100 shadow p-5">
                        <h3 class="text-sm font-semibold text-gray-800 mb-2 flex flex-wrap items-center gap-1.5">
                            <span>Attendance distribution</span>
                            <?= help_popover(
                                'Attendance distribution',
                                'Shows Present, Absent, and Incomplete counts for the selected event roster as a pie chart.',
                                'ar-pie'
                            ) ?>
                        </h3>
                        <div class="h-56 relative">
                            <canvas id="chart-ar-pie"></canvas>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow p-5">
                        <h3 class="text-sm font-semibold text-gray-800 mb-2 flex flex-wrap items-center gap-1.5">
                            <span>Fines by employee (absent)</span>
                            <?= help_popover(
                                'Fines by employee (absent)',
                                'Bar chart of fine amounts per employee who was marked absent and has a non-zero fine for this event.',
                                'ar-bar'
                            ) ?>
                        </h3>
                        <div class="h-56 relative">
                            <canvas id="chart-ar-bar"></canvas>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 mb-4">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
                    <h2 class="text-lg font-semibold text-gray-800 no-print flex flex-wrap items-center gap-1.5">
                        <span>Report data</span>
                        <?= help_popover(
                            'Report data',
                            'Search narrows the table below. Use Export for CSV, Word, or PDF. Download PDF uses the visible table including the header block.',
                            'ar-report'
                        ) ?>
                    </h2>
                    <div class="flex flex-wrap gap-2 no-print" id="export-actions">
                        <?php
                        $ex = [
                            'format' => 'csv',
                            'mode' => $mode,
                            'search' => $search,
                            'sort' => $sort,
                            'order' => $order,
                        ];
                        if ($mode === 'event') {
                            $ex['event_id'] = $eventId;
                        } else {
                            $ex['from'] = $fromDate ?? '';
                            $ex['to'] = $toDate ?? '';
                            if ($activityFilterInput !== '') {
                                $ex['activity_id'] = $activityFilterInput;
                            }
                        }
                        $exQs = http_build_query($ex);
                        ?>
                        <a class="px-3 py-2 text-sm rounded-lg border border-gray-300 hover:bg-gray-50" href="<?= htmlspecialchars($exportBase . '?' . $exQs, ENT_QUOTES, 'UTF-8') ?>">Export CSV</a>
                        <?php $ex['format'] = 'doc'; $exQs = http_build_query($ex); ?>
                        <a class="px-3 py-2 text-sm rounded-lg border border-gray-300 hover:bg-gray-50" href="<?= htmlspecialchars($exportBase . '?' . $exQs, ENT_QUOTES, 'UTF-8') ?>">Export Word (.doc)</a>
                        <?php $ex['format'] = 'html'; $exQs = http_build_query($ex); ?>
                        <a class="px-3 py-2 text-sm rounded-lg border border-gray-300 hover:bg-gray-50" target="_blank" href="<?= htmlspecialchars($exportBase . '?' . $exQs, ENT_QUOTES, 'UTF-8') ?>">Print / PDF view</a>
                        <button type="button" id="btn-pdf-download" class="px-3 py-2 text-sm rounded-lg bg-gray-800 text-white hover:bg-gray-900">Download PDF</button>
                    </div>
                </div>

                <div id="pdf-source">
                    <div class="flex items-center gap-4 mb-4 print:flex">
                        <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" class="h-14 w-auto object-contain">
                        <div>
                            <p class="text-lg font-bold text-gray-900">Attendance Report</p>
                            <p class="text-sm text-gray-600">
                                <?php if ($mode === 'event' && $roster && !empty($roster['activity'])): ?>
                                    Event: <?= htmlspecialchars($roster['activity']['name'] ?? '', ENT_QUOTES, 'UTF-8') ?> · Date: <?= htmlspecialchars($roster['activity']['activity_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                <?php else: ?>
                                    Range: <?= htmlspecialchars(($fromDate ?? '—') . ' to ' . ($toDate ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <form method="get" action="" class="mb-4 flex flex-wrap gap-2 items-center no-print" data-html2canvas-ignore>
                        <?php foreach ($_GET as $pk => $pv): ?>
                            <?php
                            if ($pk === 'search' || $pk === 'page') {
                                continue;
                            }
                            ?>
                            <input type="hidden" name="<?= htmlspecialchars((string) $pk, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string) $pv, ENT_QUOTES, 'UTF-8') ?>">
                        <?php endforeach; ?>
                        <input type="hidden" name="page" value="1">
                        <label class="text-sm text-gray-600">Search</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Name or ID" class="pl-3 pr-3 py-2 text-sm border border-gray-300 rounded-lg w-64 max-w-full">
                        <button type="submit" class="px-3 py-2 bg-blue-600 text-white text-sm rounded-lg">Search</button>
                    </form>

                    <div class="overflow-x-auto">
                        <?php
                        // Determine if event column should be shown:
                        // - In event mode: always show
                        // - In logs mode: only when an activity filter is applied
                        $showEventCol = ($mode === 'event') || ($activityFilter !== null && $activityFilter !== '');
                        $totalCols = $showEventCol ? 7 : 6;
                        ?>
                        <table class="table-plain min-w-full">
                            <thead>
                                <?php if ($mode === 'event'): ?>
                                    <tr>
                                        <th rowspan="2" style="vertical-align:bottom"><?= ar_sort_link('full_name', 'Employee Name', $sort, $order, $mode) ?></th>
                                        <th rowspan="2" style="vertical-align:bottom">Date</th>
                                        <th colspan="2" style="text-align:center; border-bottom:1px solid #d1d5db">Morning</th>
                                        <th colspan="2" style="text-align:center; border-bottom:1px solid #d1d5db">Afternoon</th>
                                        <th rowspan="2" style="vertical-align:bottom">Status</th>
                                        <th rowspan="2" style="vertical-align:bottom">Event</th>
                                    </tr>
                                    <tr>
                                        <th>In</th>
                                        <th>Out</th>
                                        <th>In</th>
                                        <th>Out</th>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <th rowspan="2" style="vertical-align:bottom"><?= ar_sort_link('full_name', 'Employee Name', $sort, $order, $mode) ?></th>
                                        <th rowspan="2" style="vertical-align:bottom"><?= ar_sort_link('timestamp', 'Date', $sort, $order, $mode) ?></th>
                                        <th colspan="2" style="text-align:center; border-bottom:1px solid #d1d5db">Morning</th>
                                        <th colspan="2" style="text-align:center; border-bottom:1px solid #d1d5db">Afternoon</th>
                                        <?php if ($showEventCol): ?>
                                            <th rowspan="2" style="vertical-align:bottom">Event</th>
                                        <?php endif; ?>
                                    </tr>
                                    <tr>
                                        <th>In</th>
                                        <th>Out</th>
                                        <th>In</th>
                                        <th>Out</th>
                                    </tr>
                                <?php endif; ?>
                            </thead>
                            <tbody>
                                <?php if ($mode === 'event' && $roster): ?>
                                    <?php if (empty($roster['rows'])): ?>
                                        <tr><td colspan="8" class="text-center text-gray-500 py-8">No employees or invalid event.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($roster['rows'] as $r): ?>
                                            <?php
                                            // derive window data
                                            $morningIn = '—';
                                            $morningOut = '—';
                                            $afternoonIn = '—';
                                            $afternoonOut = '—';
                                            
                                            $eid = $r['employee_id'];
                                            $eDate = $r['date'];
                                            
                                            try {
                                                $delRoster = SchemaColumnCache::attendancesHasDeletedAt() ? 'a.deleted_at IS NULL AND ' : '';
                                                if (!isset($rosterPdo)) {
                                                    $rosterPdo = (new Database())->connect();
                                                }
                                                $winQuery = $rosterPdo->prepare("
                                                    SELECT a.window, a.timestamp
                                                    FROM attendances AS a
                                                    WHERE {$delRoster}a.employee_id = ?
                                                      AND DATE(COALESCE(a.timestamp, a.created_at)) = ?
                                                    ORDER BY a.timestamp ASC
                                                ");
                                                $winQuery->execute([$eid, $eDate]);
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
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($r['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars($r['date'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars($morningIn, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars($morningOut, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars($afternoonIn, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars($afternoonOut, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars($r['event_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php elseif ($logsData && !empty($logsData['attendances'])): ?>
                                    <?php
                                    $grouped = [];
                                    foreach ($logsData['attendances'] as $att) {
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
                                        
                                        if ($norm === 'morning_in') $grouped[$key]['morning_in'] = $timePart;
                                        elseif ($norm === 'morning_out') $grouped[$key]['morning_out'] = $timePart;
                                        elseif ($norm === 'afternoon_in') $grouped[$key]['afternoon_in'] = $timePart;
                                        elseif ($norm === 'afternoon_out') $grouped[$key]['afternoon_out'] = $timePart;
                                        elseif ($timePart !== '—' && $grouped[$key]['morning_in'] === '—') $grouped[$key]['morning_in'] = $timePart;
                                        
                                        if ($actName !== '—') $grouped[$key]['activity_name'] = $actName;
                                    }
                                    ?>
                                    <?php foreach ($grouped as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($row['date'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($row['morning_in'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($row['morning_out'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($row['afternoon_in'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($row['afternoon_out'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <?php if ($showEventCol): ?>
                                                <td><?= htmlspecialchars($row['activity_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="<?= $totalCols ?>" class="text-center text-gray-500 py-8">No records found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php
                $pag = $mode === 'event' ? ($roster['pagination'] ?? null) : ($logsData['pagination'] ?? null);
                if ($pag && ($pag['totalPages'] ?? 1) > 1):
                    $baseParams = $_GET;
                    ?>
                    <nav class="mt-6 flex flex-wrap justify-center gap-2 text-sm no-print">
                        <?php for ($p = 1; $p <= (int) $pag['totalPages']; $p++): ?>
                            <?php
                            $baseParams['page'] = $p;
                            $pq = http_build_query($baseParams);
                            ?>
                            <a href="?<?= htmlspecialchars($pq, ENT_QUOTES, 'UTF-8') ?>"
                               class="px-3 py-1 rounded border <?= $p === (int) $pag['currentPage'] ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-50' ?>"><?= $p ?></a>
                        <?php endfor; ?>
                    </nav>
                    <p class="text-center text-sm text-gray-500 mt-2 no-print">
                        Showing <?= (int) ($pag['startRecord'] ?? 0) ?>–<?= (int) ($pag['endRecord'] ?? 0) ?> of <?= (int) ($pag['totalRecords'] ?? 0) ?>
                    </p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div id="fines-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4 no-print">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-2 flex items-center gap-1.5">
                <span>Event fines</span>
                <?= help_popover(
                    'Event fines',
                    'Set the fine amount (PHP) for employees who are absent for the selected event. The amount is linked to that activity only.',
                    'ar-modal-fines'
                ) ?>
            </h3>
            <p class="text-sm text-gray-600 mb-4">Enter an amount and save for the chosen activity.</p>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Activity</label>
                    <select id="fines-activity-id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <?php if (empty($activityList)): ?>
                            <option value="">No activities in system</option>
                        <?php endif; ?>
                        <?php foreach ($activityList as $act): ?>
                            <?php
                            $aid = is_object($act) ? (int) ($act->id ?? 0) : (int) ($act['id'] ?? 0);
                            if ($aid <= 0) {
                                continue;
                            }
                            $aname = is_object($act) ? (string) ($act->name ?? '') : (string) ($act['name'] ?? '');
                            ?>
                            <option value="<?= $aid ?>" <?= $eventId === $aid ? 'selected' : '' ?>><?= htmlspecialchars($aname, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fine amount (PHP)</label>
                    <input type="number" id="fines-amount" min="0" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="0.00">
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" id="fines-cancel" class="px-4 py-2 text-sm border border-gray-300 rounded-lg">Close</button>
                <button type="button" id="fines-save" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg">Save</button>
            </div>
        </div>
    </div>

    <script>
        window.ATTENDANCE_REPORTS_FINES_API = <?= json_encode($finesApi, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.ATTENDANCE_REPORTS_CHART_PIE = <?= json_encode($chartPie, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.ATTENDANCE_REPORTS_CHART_BAR = <?= json_encode($chartBar, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script type="module" src="js/attendance-reports/main.js"></script>
</body>
</html>
