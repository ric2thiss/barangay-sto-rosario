<?php
require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/../auth/helpers.php";
requireAuth(); // Require authentication - redirects to login if not authenticated

include_once '../shared/components/Sidebar.php';
include_once '../shared/components/Breadcrumb.php';
include_once '../shared/components/HelpPopover.php';

// Get current user for greeting
$currentUser = currentUser();
$userName = $currentUser ? ($currentUser['full_name'] ?? $currentUser['username']) : 'Guest';

// Get date range parameters (default to current month)
$fromDate = isset($_GET['from']) ? trim($_GET['from']) : date('Y-m-01');
$toDate = isset($_GET['to']) ? trim($_GET['to']) : date('Y-m-t');
$reportType = isset($_GET['type']) ? trim($_GET['type']) : 'total-visitors';
$filterPurpose = isset($_GET['purpose']) ? trim($_GET['purpose']) : '';
$filterGender = isset($_GET['gender']) ? trim($_GET['gender']) : '';
$filterPurok = isset($_GET['purok']) ? trim($_GET['purok']) : '';
$trendGranularity = isset($_GET['trend']) ? trim($_GET['trend']) : 'day';
if (!in_array($trendGranularity, ['day', 'week', 'month'], true)) {
    $trendGranularity = 'day';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Analytics</title>
    <link rel="stylesheet" href="../utils/styles/global.css">
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Load Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        /* Prevent body horizontal scroll */
        body {
            overflow-x: hidden;
        }
        .analytics-chart-box {
            position: relative;
            height: 280px;
        }
        .analytics-chart-box--tall {
            height: 320px;
        }
    </style>
</head>
<body>
    <script>
        window.__visitorAnalyticsInitial = {
            purpose: <?= json_encode($filterPurpose, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            gender: <?= json_encode($filterGender, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            purok: <?= json_encode($filterPurok, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            trend: <?= json_encode($trendGranularity, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
        };
    </script>

    <!-- Main Container -->
    <div class="flex min-h-screen">

        <?=Sidebar("Visitor Analytics", null)?>

        <!-- 2. MAIN CONTENT AREA -->
        <main class="flex-1 md:ml-64 p-6 transition-all duration-300">

            <!-- Top Header Bar -->
            <header class="mb-6">
                <div class="flex justify-between items-center mb-1">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Visitor Analytics</h1>
                        <p class="text-gray-500 text-sm"><?= getGreeting($userName) ?> — Summary, charts, and detailed reports.</p>
                    </div>
                    <div>
                        <button type="button" id="exportAnalyticsBtn" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-md transition-colors">
                            <svg class="w-5 h-5 inline mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Export Analytics
                        </button>
                    </div>
                </div>
                <?php Breadcrumb([
                    ['label' => 'Dashboard', 'link' => 'dashboard.php'],
                    ['label' => 'Visitor Analytics', 'link' => 'visitor-reports.php']
                ]); ?>
            </header>

            <!-- FILTERS: analytics + report -->
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 mb-8">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4 flex items-center gap-2 flex-wrap">
                    Filters
                    <?= help_popover(
                        'Filters',
                        'Report type drives the detailed report table and chart below. Start and end dates apply to both the analytics dashboard and that report. Trend grouping only affects how the Visitors over time chart is grouped (by day, week, or month). Purpose, gender, and purok apply to the analytics summary and charts; the detailed report table still uses date range and report type when you click Run / Refresh.',
                        'va-filters'
                    ) ?>
                </h2>
                <div class="flex flex-col gap-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 items-end">
                        <div class="lg:col-span-2">
                            <label for="reportType" class="block text-sm font-medium text-gray-700 mb-1">Report type (table &amp; export below)</label>
                            <select id="reportType" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-lg border">
                                <option value="total-visitors" <?= $reportType === 'total-visitors' ? 'selected' : '' ?>>Total Visitors</option>
                                <option value="services-availed" <?= $reportType === 'services-availed' ? 'selected' : '' ?>>Services Availed by Visitors</option>
                                <option value="visitor-types" <?= $reportType === 'visitor-types' ? 'selected' : '' ?>>Types of Visitors (Residents/Non-Residents)</option>
                                <option value="appointment-types" <?= $reportType === 'appointment-types' ? 'selected' : '' ?>>Appointment Types (Online/Walk-in)</option>
                                <option value="gender-distribution" <?= $reportType === 'gender-distribution' ? 'selected' : '' ?>>Gender Distribution</option>
                                <option value="age-services" <?= $reportType === 'age-services' ? 'selected' : '' ?>>Age Groups &amp; Services Availed</option>
                            </select>
                        </div>
                        <div>
                            <label for="startDate" class="block text-sm font-medium text-gray-700 mb-1">Start date</label>
                            <input type="date" id="startDate" value="<?= htmlspecialchars($fromDate) ?>" class="mt-1 block w-full pl-3 pr-3 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-lg border">
                        </div>
                        <div>
                            <label for="endDate" class="block text-sm font-medium text-gray-700 mb-1">End date</label>
                            <input type="date" id="endDate" value="<?= htmlspecialchars($toDate) ?>" class="mt-1 block w-full pl-3 pr-3 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-lg border">
                        </div>
                        <div>
                            <label for="filterTrend" class="block text-sm font-medium text-gray-700 mb-1">Trend grouping</label>
                            <select id="filterTrend" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-lg border">
                                <option value="day" <?= $trendGranularity === 'day' ? 'selected' : '' ?>>By day</option>
                                <option value="week" <?= $trendGranularity === 'week' ? 'selected' : '' ?>>By week</option>
                                <option value="month" <?= $trendGranularity === 'month' ? 'selected' : '' ?>>By month</option>
                            </select>
                        </div>
                        <div>
                            <button id="runReportBtn" type="button" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-md transition-colors">
                                Run / Refresh
                            </button>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        <div>
                            <label for="filterPurpose" class="block text-sm font-medium text-gray-700 mb-1">Purpose (analytics)</label>
                            <select id="filterPurpose" class="mt-1 block w-full pl-3 pr-10 py-2 text-sm border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-lg border">
                                <option value="">All</option>
                            </select>
                        </div>
                        <div>
                            <label for="filterGender" class="block text-sm font-medium text-gray-700 mb-1">Gender (analytics)</label>
                            <select id="filterGender" class="mt-1 block w-full pl-3 pr-10 py-2 text-sm border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-lg border">
                                <option value="">All</option>
                            </select>
                        </div>
                        <div>
                            <label for="filterPurok" class="block text-sm font-medium text-gray-700 mb-1">Purok (analytics)</label>
                            <select id="filterPurok" class="mt-1 block w-full pl-3 pr-10 py-2 text-sm border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-lg border">
                                <option value="">All</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ANALYTICS -->
            <section class="mb-10" id="visitorAnalyticsSection" aria-label="Visitor analytics dashboard">
                <div id="analyticsLoading" class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 mb-6 text-center hidden">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p class="mt-2 text-gray-600 text-sm">Loading analytics…</p>
                </div>
                <div id="analyticsError" class="hidden mb-6 p-4 rounded-lg bg-red-50 text-red-700 text-sm border border-red-100"></div>

                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2 flex-wrap">
                    Summary
                    <?= help_popover(
                        'Summary cards',
                        'Total visits counts visits in the selected range and filters. Unique visitors counts distinct people (matched by resident ID or name). Repeat visitors are people with more than one visit. Average visits per person divides total visits by unique visitors.',
                        'va-summary'
                    ) ?>
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-100">
                        <p class="text-xs font-medium text-gray-500 uppercase">Total visits</p>
                        <p id="analyticsCardTotal" class="text-2xl font-semibold text-gray-900 mt-1">—</p>
                        <p class="text-xs text-gray-400 mt-1">In selected range &amp; filters</p>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-100">
                        <p class="text-xs font-medium text-gray-500 uppercase">Unique visitors</p>
                        <p id="analyticsCardUnique" class="text-2xl font-semibold text-gray-900 mt-1">—</p>
                        <p class="text-xs text-gray-400 mt-1">Distinct people (resident ID or name)</p>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-100">
                        <p class="text-xs font-medium text-gray-500 uppercase">Repeat visitors</p>
                        <p id="analyticsCardRepeat" class="text-2xl font-semibold text-gray-900 mt-1">—</p>
                        <p class="text-xs text-gray-400 mt-1">People with more than one visit</p>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-100">
                        <p class="text-xs font-medium text-gray-500 uppercase">Avg visits / person</p>
                        <p id="analyticsCardAvgFreq" class="text-2xl font-semibold text-gray-900 mt-1">—</p>
                        <p class="text-xs text-gray-400 mt-1">Visit frequency</p>
                    </div>
                </div>

                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2 flex-wrap">
                    Visitor trends
                    <?= help_popover(
                        'Visitor trends',
                        'Visitors over time respects trend grouping (day/week/month). Peak hours and day-of-week charts aggregate visit timestamps from the same filtered dataset.',
                        'va-trends'
                    ) ?>
                </h2>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Visitors over time</h3>
                        <div class="analytics-chart-box--tall"><canvas id="chartVisitorTrend" aria-label="Visitors over time bar chart"></canvas></div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Peak hours</h3>
                        <div class="analytics-chart-box--tall"><canvas id="chartPeakHours" aria-label="Peak hours bar chart"></canvas></div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Visits by day of week</h3>
                        <div class="analytics-chart-box--tall"><canvas id="chartDayOfWeek" aria-label="Day of week bar chart"></canvas></div>
                    </div>
                </div>

                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2 flex-wrap">
                    Purpose
                    <?= help_popover(
                        'Purpose analytics',
                        'Charts summarize the visit purpose field from visitor logs: most common purposes and distribution (top five plus other).',
                        'va-purpose'
                    ) ?>
                </h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Most common purposes</h3>
                        <div class="analytics-chart-box"><canvas id="chartPurposeTop"></canvas></div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Purpose distribution (top five + other)</h3>
                        <div class="analytics-chart-box"><canvas id="chartPurposeDist"></canvas></div>
                    </div>
                </div>

                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2 flex-wrap">
                    Demographics
                    <?= help_popover(
                        'Demographics',
                        'Gender, purok, barangay, and civil status use profiling data for resident visitors when linked; non-residents appear as labeled buckets where applicable.',
                        'va-demo'
                    ) ?>
                </h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Visitors by gender</h3>
                        <div class="analytics-chart-box"><canvas id="chartGender"></canvas></div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Civil status (residents)</h3>
                        <div class="analytics-chart-box"><canvas id="chartCivilStatus"></canvas></div>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Visitors by age group</h3>
                        <div class="analytics-chart-box"><canvas id="chartAgeGroups"></canvas></div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Visitors by purok</h3>
                        <div class="analytics-chart-box"><canvas id="chartPurok"></canvas></div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 mb-8">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Visitors by barangay (residents)</h3>
                    <div class="analytics-chart-box--tall"><canvas id="chartBarangay"></canvas></div>
                </div>

                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2 flex-wrap">
                    Insights
                    <?= help_popover(
                        'Insights',
                        'Frequent visitors lists the top visitors by visit count. Average visit duration is computed when both time-in and time-out are present on records.',
                        'va-insights'
                    ) ?>
                </h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-4">
                    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Frequent visitors (top 10)</h3>
                        <ol id="insightFrequentList" class="list-none space-y-0 pl-0"></ol>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Average visit duration</h3>
                        <p id="insightDuration" class="text-gray-700 text-sm">—</p>
                        <p class="text-xs text-gray-500 mt-2">Requires time-in and time-out fields when available.</p>
                    </div>
                </div>
            </section>

            <!-- REPORT TABLE + CHART (existing) -->
            <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2 flex-wrap">
                Detailed report
                <?= help_popover(
                    'Detailed report',
                    'The chart and data table below reflect the selected report type and date range. Use Run / Refresh after changing filters. Export table downloads the current rows as CSV.',
                    'va-detail'
                ) ?>
            </h2>

            <!-- Loading Indicator -->
            <div id="loadingIndicator" class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 mb-8 text-center hidden">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2 text-gray-600">Loading report data...</p>
            </div>

            <!-- CHART VISUALIZATION SECTION -->
            <div id="chartSection" class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 mb-8">
                <h2 id="chartTitle" class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2 flex-wrap">
                    Report Data
                    <?= help_popover(
                        'Report chart',
                        'Visualization for the active report type. Updates when you Run / Refresh with the same parameters as the table.',
                        'va-chart'
                    ) ?>
                </h2>
                <div id="chartContainer" class="w-full overflow-x-auto" style="position: relative; height: 400px;">
                    <canvas id="reportChart"></canvas>
                </div>
            </div>

            <!-- RAW DATA TABLE SECTION -->
            <div id="tableSection" class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2 flex-wrap">
                        Detailed Report Data
                        <?= help_popover(
                            'Report table',
                            'Column layout depends on report type. Data is loaded for the selected start and end dates.',
                            'va-table'
                        ) ?>
                    </h2>
                    <button type="button" id="exportTableBtn" class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium py-2 px-4 rounded-lg border border-gray-200 transition-colors">
                        Export table (CSV)
                    </button>
                </div>

                <!-- Table Header -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr id="tableHeader">
                                <!-- Headers will be populated by JavaScript -->
                            </tr>
                        </thead>
                        <tbody id="tableBody" class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    <p class="text-sm">Select a report type and click &quot;Run / Refresh&quot; to view data.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- JavaScript Module for Visitor Reports -->
    <script type="module" src="js/visitor-reports/main.js"></script>

</body>
</html>
