<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth/helpers.php';
requireAuth();

include_once __DIR__ . '/../shared/components/Sidebar.php';
include_once __DIR__ . '/../shared/components/Breadcrumb.php';

$currentUser = currentUser();
$userName = $currentUser ? ($currentUser['full_name'] ?? $currentUser['username']) : 'Guest';

$brandingName = 'Barangay';
try {
    $settingsController = new SettingsController();
    $settingsResult = $settingsController->getAll();
    if (!empty($settingsResult['success']) && !empty($settingsResult['settings']['app_name']['value'])) {
        $brandingName = (string) $settingsResult['settings']['app_name']['value'];
    }
} catch (Exception $e) {
    error_log('attendance-analytics branding: ' . $e->getMessage());
}

$logoUrl = rtrim(BASE_URL, '/') . '/utils/img/logo.png';
$analyticsApi = rtrim(BASE_URL, '/') . '/api/attendance/analytics.php';

$hoursReportFrom = isset($_GET['hours_from']) ? trim((string) $_GET['hours_from']) : date('Y-m-01');
$hoursReportTo = isset($_GET['hours_to']) ? trim((string) $_GET['hours_to']) : date('Y-m-t');
$hoursReportType = isset($_GET['hours_type']) ? trim((string) $_GET['hours_type']) : 'attendance-position';
$hoursTypes = ['attendance-position', 'attendance-chairmanship', 'attendance-employee', 'attendance-daily'];
if (!in_array($hoursReportType, $hoursTypes, true)) {
    $hoursReportType = 'attendance-position';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>">
    <title>Attendance Analytics</title>
    <link rel="stylesheet" href="../utils/styles/global.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <style>
        body { overflow-x: hidden; }
        .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }

        /* Help popover styles: global.css (.help-popover-*) */
        /* Avoid clipping popovers; scroll only the table body wrapper */
        #insights-panel .insights-card-clip {
            overflow-x: auto;
        }
        #insights-panel .insights-card-shell {
            overflow: visible;
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen">
        <?= Sidebar('Attendance Analytics', null, $logoUrl) ?>

        <main class="flex-1 md:ml-64 p-6 transition-all duration-300">
            <header class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-800">Attendance Analytics</h1>
                <p class="text-gray-500 text-sm"><?= htmlspecialchars(getGreeting($userName), ENT_QUOTES, 'UTF-8') ?> — Compliance, insights, gap fill, Chart.js views, and total-hours reports (D3) in one place.</p>
                <?php Breadcrumb([
                    ['label' => 'Dashboard', 'link' => 'dashboard.php'],
                    ['label' => 'Attendance Analytics', 'link' => 'attendance-analytics.php'],
                ]); ?>
            </header>

            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 mb-6">
                <div class="flex flex-col xl:flex-row xl:flex-wrap xl:items-end gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                        <select id="filter-period" class="pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="daily">Daily (today)</option>
                            <option value="weekly">Weekly (this week)</option>
                            <option value="monthly" selected>Monthly (this month)</option>
                            <option value="yearly">Yearly (YTD)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
                        <input type="date" id="filter-from" class="block w-full md:w-44 pl-3 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                        <input type="date" id="filter-to" class="block w-full md:w-44 pl-3 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
                        <select id="filter-employee" class="w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All employees</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status (table)</label>
                        <select id="filter-status" class="pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="all">All</option>
                            <option value="Incomplete">Incomplete</option>
                            <option value="Absent">Absent</option>
                            <option value="Late">Late</option>
                            <option value="Undertime">Undertime</option>
                            <option value="Overtime">Overtime</option>
                            <option value="Complete">Complete</option>
                        </select>
                    </div>
                    <div class="min-w-[220px] flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Activity / event</label>
                        <select id="filter-activity" class="w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All activities</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Limits logs to that activity tag; summary matches the same filter.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rows per page</label>
                        <select id="filter-per-page" class="pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div>
                        <button type="button" id="btn-refresh" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-sm text-sm mt-6 xl:mt-0">
                            Refresh
                        </button>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-3" id="range-label"></p>
            </div>

            <div id="summary-cards" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
                <!-- populated by JS -->
            </div>

            <div id="insights-panel" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- populated by JS -->
            </div>

            <div id="charts-panel" class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-xl border border-gray-100 shadow p-5 flex flex-col">
                    <h3 class="text-lg font-semibold text-gray-800">Compliance by employee</h3>
                    <p class="text-xs text-gray-500 mt-1 mb-3">Percent of days in range with complete logs, on time, and no undertime (overtime does not reduce this score).</p>
                    <div class="h-64 min-h-[16rem] relative">
                        <canvas id="chart-analytics-compliance"></canvas>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow p-5 flex flex-col">
                    <h3 class="text-lg font-semibold text-gray-800">Late / undertime / overtime (day occurrences)</h3>
                    <p class="text-xs text-gray-500 mt-1 mb-3">Counts of employee-days flagged in the selected range (same rules as the summary cards).</p>
                    <div class="h-64 min-h-[16rem] relative flex items-center justify-center">
                        <canvas id="chart-analytics-issues-pie"></canvas>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow p-5 flex flex-col">
                    <h3 class="text-lg font-semibold text-gray-800">Perfect vs needs attention</h3>
                    <p class="text-xs text-gray-500 mt-1 mb-3">Employees with perfect streak vs at least one absence, incomplete, late, or undertime day (overtime excluded).</p>
                    <div class="h-64 min-h-[16rem] relative">
                        <canvas id="chart-analytics-perfect-bar"></canvas>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow p-5 flex flex-col">
                    <h3 class="text-lg font-semibold text-gray-800">Employees by position</h3>
                    <p class="text-xs text-gray-500 mt-1 mb-3">Distribution of roster positions (profiling-system) for employees in the current filter.</p>
                    <div class="h-64 min-h-[16rem] relative flex items-center justify-center">
                        <canvas id="chart-analytics-position-pie"></canvas>
                    </div>
                </div>
            </div>

            <section id="hours-reports" class="mb-6 scroll-mt-24">
                <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 mb-4">
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-4">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800">Total hours &amp; summaries</h2>
                            <p class="text-sm text-gray-500 mt-1">Same reports as the former Attendance Reports page: hours by position, chairmanship, employee, and daily rows. Uses <code class="text-xs bg-gray-100 px-1 rounded">/api/reports/index.php</code>.</p>
                        </div>
                        <button type="button" id="hours-report-export" class="shrink-0 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-md text-sm">
                            Export CSV
                        </button>
                    </div>
                    <div class="flex flex-col xl:flex-row xl:flex-wrap xl:items-end gap-4">
                        <div class="w-full xl:w-[min(100%,22rem)] xl:flex-1 min-w-[200px]">
                            <label for="hours-report-type" class="block text-sm font-medium text-gray-700 mb-1">Report type</label>
                            <select id="hours-report-type" class="w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <option value="attendance-position" <?= $hoursReportType === 'attendance-position' ? 'selected' : '' ?>>Total hours by position</option>
                                <option value="attendance-chairmanship" <?= $hoursReportType === 'attendance-chairmanship' ? 'selected' : '' ?>>Total hours by chairmanship</option>
                                <option value="attendance-employee" <?= $hoursReportType === 'attendance-employee' ? 'selected' : '' ?>>Total hours by employee</option>
                                <option value="attendance-daily" <?= $hoursReportType === 'attendance-daily' ? 'selected' : '' ?>>Daily attendance summary</option>
                            </select>
                        </div>
                        <div>
                            <label for="hours-report-start" class="block text-sm font-medium text-gray-700 mb-1">Start date</label>
                            <input type="date" id="hours-report-start" value="<?= htmlspecialchars($hoursReportFrom, ENT_QUOTES, 'UTF-8') ?>" class="block w-full md:w-44 pl-3 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="hours-report-end" class="block text-sm font-medium text-gray-700 mb-1">End date</label>
                            <input type="date" id="hours-report-end" value="<?= htmlspecialchars($hoursReportTo, ENT_QUOTES, 'UTF-8') ?>" class="block w-full md:w-44 pl-3 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <button type="button" id="hours-report-run" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-md text-sm mt-0 xl:mt-6">
                                Run report
                            </button>
                        </div>
                    </div>
                </div>

                <div id="hours-report-loading" class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 mb-4 text-center hidden">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p class="mt-2 text-gray-600 text-sm">Loading report data…</p>
                </div>

                <div id="hours-report-chart-section" class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 mb-4 hidden">
                    <h3 id="hours-report-chart-title" class="text-lg font-semibold text-gray-800 mb-4">Report data</h3>
                    <div id="hours-report-chart-container" class="w-full overflow-x-auto min-h-[200px]"></div>
                </div>

                <div id="hours-report-table-section" class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 hidden">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Detailed report data</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr id="hours-report-table-header"></tr>
                            </thead>
                            <tbody id="hours-report-table-body" class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">Click &quot;Run report&quot; to load.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Employee attendance</h2>
                <p class="text-sm text-gray-500 mb-3">Incomplete and action-needed rows are listed first. Existing logs are read-only; use “Fill gap” only for missing windows.</p>
                <div class="table-container">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Employee</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Date / shift</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Windows</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Logged times</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Status</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Action</th>
                            </tr>
                        </thead>
                        <tbody id="analytics-tbody" class="divide-y divide-gray-200 bg-white">
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="analytics-pagination" class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-sm text-gray-600"></div>
            </div>
        </main>
    </div>

    <div id="attention-detail-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-start justify-between gap-3 shrink-0">
                <div>
                    <h3 id="attention-detail-title" class="text-lg font-semibold text-gray-800">Details</h3>
                    <p id="attention-detail-sub" class="text-xs text-gray-500 mt-1"></p>
                </div>
                <button type="button" id="attention-detail-close" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100" aria-label="Close">✕</button>
            </div>
            <div class="overflow-auto flex-1 px-5 py-3">
                <table class="min-w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
                    <thead id="attention-detail-thead" class="bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wide"></thead>
                    <tbody id="attention-detail-tbody" class="divide-y divide-gray-100 bg-white"></tbody>
                </table>
            </div>
            <div id="attention-detail-pagination" class="px-5 py-3 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-sm text-gray-600 shrink-0"></div>
        </div>
    </div>

    <div id="gap-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Fill missing log</h3>
            <p class="text-sm text-gray-600 mb-4" id="gap-modal-desc"></p>
            <form id="gap-form" class="space-y-3">
                <input type="hidden" id="gap-employee-id">
                <input type="hidden" id="gap-date">
                <input type="hidden" id="gap-window">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Time (24h)</label>
                    <input type="time" id="gap-time" step="1" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div class="flex gap-2 justify-end pt-2">
                    <button type="button" id="gap-cancel" class="px-4 py-2 text-sm rounded-lg border border-gray-300 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm rounded-lg bg-blue-600 text-white hover:bg-blue-700">Save (missing only)</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.BASE_URL = <?= json_encode(BASE_URL, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.ATTENDANCE_ANALYTICS_API = <?= json_encode($analyticsApi, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script type="module" src="./js/attendance-analytics/main.js"></script>
</body>
</html>
