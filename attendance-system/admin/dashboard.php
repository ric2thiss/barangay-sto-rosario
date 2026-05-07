
<?php
require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/../auth/helpers.php";
requireAuth(); // Require authentication - redirects to login if not authenticated

$getdata = (new AttendanceController())->index();

// Get total counts for residents, employees, and visitors
$db = (new Database())->connect();

// Profiling system database (source of truth for residents/employees)
$profilingDbName = defined("PROFILING_DB_NAME") ? PROFILING_DB_NAME : "profiling-system";

// Get accurate total residents count from profiling-system database
try {
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM `{$profilingDbName}`.`residents`");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalResidents = (int) ($result['total'] ?? 0);
} catch (Exception $e) {
    // Fallback value if query fails (do not query local attendance-system tables)
    $totalResidents = 0;
    error_log("Error fetching resident count: " . $e->getMessage());
}

// Get accurate total employees count from profiling-system database (barangay_official)
try {
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM `{$profilingDbName}`.`barangay_official`");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalEmployees = (int) ($result['total'] ?? 0);
} catch (Exception $e) {
    // Fallback value if query fails (do not query local attendance-system tables)
    $totalEmployees = 0;
    error_log("Error fetching employee count: " . $e->getMessage());
}

// Get visitor counts (default: this month to match default filter, will be updated via JavaScript/AJAX)
$now = new DateTime();
$startDate = (clone $now)->modify('first day of this month')->setTime(0, 0, 0);
$endDate = new DateTime();
$startDateStr = $startDate->format('Y-m-d H:i:s');
$endDateStr = $endDate->format('Y-m-d H:i:s');

try {
    // Count visitors for this month using visitor_logs table
    $totalVisitors = VisitorLog::query()
        ->whereRaw('(deleted_at IS NULL)')
        ->whereBetween('created_at', [$startDateStr, $endDateStr])
        ->count();
    
    // Get resident visitors count for this month (is_resident = 1)
    $stmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM visitor_logs
        WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ?
        AND is_resident = 1
    ");
    $stmt->execute([$startDateStr, $endDateStr]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalResidentVisitors = $result ? (int)($result['count'] ?? 0) : 0;
    
    // Get non-resident visitors count for this month (is_resident = 0)
    $stmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM visitor_logs
        WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ?
        AND is_resident = 0
    ");
    $stmt->execute([$startDateStr, $endDateStr]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalNonResidentVisitors = $result ? (int)($result['count'] ?? 0) : 0;
    
    // Count online appointment visitors for this month (had_booking = 1)
    $onlineStmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM visitor_logs
        WHERE created_at >= ? AND created_at <= ?
        AND had_booking = 1
    ");
    $onlineStmt->execute([$startDateStr, $endDateStr]);
    $onlineResult = $onlineStmt->fetch(PDO::FETCH_ASSOC);
    $totalOnlineAppointment = $onlineResult ? (int)($onlineResult['count'] ?? 0) : 0;
    
    // Count walk-in visitors for this month (had_booking = 0)
    $walkinStmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM visitor_logs
        WHERE deleted_at IS NULL AND created_at >= ? AND created_at <= ?
        AND had_booking = 0
    ");
    $walkinStmt->execute([$startDateStr, $endDateStr]);
    $walkinResult = $walkinStmt->fetch(PDO::FETCH_ASSOC);
    $totalWalkin = $walkinResult ? (int)($walkinResult['count'] ?? 0) : 0;
} catch (Exception $e) {
    // Fallback values if query fails
    $totalVisitors = 0;
    $totalResidentVisitors = 0;
    $totalNonResidentVisitors = 0;
    $totalWalkin = 0;
    $totalOnlineAppointment = 0;
    error_log("Error fetching visitor counts: " . $e->getMessage());
}

// print_r($getdata["attendancesTodayCount"]);
include_once '../shared/components/Sidebar.php';
include_once '../shared/components/Breadcrumb.php';

// Get current user for greeting
$currentUser = currentUser();
$userName = $currentUser ? ($currentUser['full_name'] ?? $currentUser['username']) : 'Guest';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Overview</title>
    <link rel="stylesheet" href="../utils/styles/global.css">
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Load Chart.js for graphs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <!-- Use Inter font family -->
    <style>

        /* Custom darker button color for Attendance Now */
        .btn-dark {
            background-color: #374151; /* A deep slate color */
        }
        .btn-dark:hover {
            background-color: #1f2937;
        }
    </style>
</head>
<body>

    <!-- Main Container -->
    <div class="flex min-h-screen">

        <?=Sidebar("Dashboard", null)?>

        <!-- 2. MAIN CONTENT AREA -->
        <main class="flex-1 md:ml-64 p-6 transition-all duration-300">

            <!-- Top Header Bar -->
            <header class="mb-6">
                <div class="flex justify-between items-center mb-1">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Dashboard Overview</h1>
                        <p class="text-gray-500 text-sm"><?= getGreeting($userName) ?></p>
                    </div>
                    <p class="text-sm text-gray-500" id="current-date">September 28, 2025</p>
                </div>
                <?php Breadcrumb([['label' => 'Dashboard', 'link' => 'dashboard.php']]); ?>
            </header>

            <!-- DASHBOARD GRID LAYOUT -->
            <div class="space-y-6">

                <!-- TOP ROW: Realtime Insight, Total Residents, Total Employees -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <!-- Realtime Insight Card -->
                    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Realtime Insight</h2>

                        <!-- Connection status (markup aligned with attendance.php; colors from websocket.js) -->
                        <div class="mb-4 flex items-center space-x-2 text-sm">
                            <span id="ws-status-indicator" class="inline-block w-3 h-3 rounded-full bg-gray-400" aria-hidden="true"></span>
                            <span id="ws-status-text" class="text-gray-600">Connecting...</span>
                        </div>
                        
                        <!-- Clock & Insight (Dynamic weather icon) -->
                        <div class="flex items-center space-x-2">
                            <!-- Weather/Clock Icon (Dynamic based on weather) -->
                            <div id="weather-icon" class="w-6 h-6">
                                <!-- Default sun icon - will be replaced by JavaScript -->
                                <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            </div>
                            <span class="text-4xl font-extrabold text-gray-900" id="realtime-clock">
                                10:20 : 28 AM
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mb-6 mt-1">Realtime Insight</p>

                        <!-- Today's Date (Separated the "Today:" label) -->
                        <p class="text-base text-gray-500">Today:</p>
                        <p class="text-lg font-bold text-gray-700 mb-2" id="today-date-insight">
                            28th September 2025
                        </p>

                        <!-- Weather Forecast Section -->
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <p class="text-base text-gray-500 mb-2">Weather Forecast:</p>
                            <div id="weather-info" class="text-sm text-gray-600">
                                <div class="flex items-center space-x-2 mb-1">
                                    <span id="weather-condition" class="font-medium">Loading...</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span id="weather-temperature" class="text-lg font-semibold text-gray-800">-</span>
                                    <span id="weather-details" class="text-xs text-gray-500">-</span>
                                </div>
                            </div>
                        </div>

                        <!-- Attendance Button (Dark Blue/Slate Style) -->
                        <button onclick="window.location.href='biometrics://identify'" class="w-full py-3 btn-dark hover:bg-gray-700 text-white font-semibold rounded-lg transition-colors shadow-md text-lg flex items-center justify-center mt-8">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Attendance Now
                        </button>
                    </div>

                    <!-- Total Residents -->
                    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 flex flex-col justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Total Residents</h2>
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-4xl sm:text-5xl font-bold text-blue-600 tabular-nums" id="total-residents-main-count"><?= $totalResidents ?></span>
                                <svg class="w-12 h-12 text-blue-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <p class="text-sm text-gray-500 mb-4">Visitor activity below uses the selected period (same filters as other admin reports).</p>

                            <div class="pt-4 border-t border-gray-200">
                                <div class="mb-4">
                                    <label for="visitor-filter-dropdown" class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                                    <select id="visitor-filter-dropdown" class="block w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-lg bg-white text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="today">Today</option>
                                        <option value="week">This Week</option>
                                        <option value="month" selected>This Month</option>
                                        <option value="year">This Year</option>
                                    </select>
                                </div>

                                <div class="space-y-0 divide-y divide-gray-200 border border-gray-200 rounded-lg overflow-hidden">
                                    <div role="button" tabindex="0" data-dashboard-analytics="visitor" data-visitor-card="total" class="flex items-center justify-between px-3 py-2.5 bg-gray-50/80 cursor-pointer hover:bg-gray-100/80 transition-colors focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-400 rounded-t-lg">
                                        <span class="text-sm font-medium text-gray-700">Total Visitors</span>
                                        <span class="text-xl font-bold text-purple-600 tabular-nums" id="total-visitors-count"><?= $totalVisitors ?></span>
                                    </div>
                                    <div class="flex items-center justify-between px-3 py-2.5 bg-white">
                                        <span class="text-sm text-gray-600">Resident visitors</span>
                                        <span class="text-lg font-bold text-orange-600 tabular-nums" id="resident-visitors-count"><?= $totalResidentVisitors ?></span>
                                    </div>
                                    <div class="flex items-center justify-between px-3 py-2.5 bg-white">
                                        <span class="text-sm text-gray-600">Non-resident visitors</span>
                                        <span class="text-lg font-bold text-red-600 tabular-nums" id="non-resident-visitors-count"><?= $totalNonResidentVisitors ?></span>
                                    </div>
                                    <div class="flex items-center justify-between px-3 py-2.5 bg-white">
                                        <span class="text-sm text-gray-600">Walk-in</span>
                                        <span class="text-lg font-bold text-pink-600 tabular-nums" id="walkin-count"><?= $totalWalkin ?></span>
                                    </div>
                                    <div class="flex items-center justify-between px-3 py-2.5 bg-white rounded-b-lg">
                                        <span class="text-sm text-gray-600">Online appointment</span>
                                        <span class="text-lg font-bold text-teal-600 tabular-nums" id="online-appointment-count"><?= $totalOnlineAppointment ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Employees -->
                    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 flex flex-col justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Total Employees</h2>
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-4xl sm:text-5xl font-bold text-indigo-600 tabular-nums" id="total-employees-main-count"><?= $totalEmployees ?></span>
                                <svg class="w-12 h-12 text-indigo-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <p class="text-sm text-gray-500 mb-4">Attendance totals below for the selected period (same source as attendance stats API).</p>

                            <div class="pt-4 border-t border-gray-200">
                                <div class="mb-4">
                                    <label for="employee-attendance-filter-dropdown" class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                                    <select id="employee-attendance-filter-dropdown" class="block w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-lg bg-white text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="today">Today</option>
                                        <option value="week">This Week</option>
                                        <option value="month" selected>This Month</option>
                                        <option value="year">This Year</option>
                                    </select>
                                </div>

                                <div class="space-y-0 divide-y divide-gray-200 border border-gray-200 rounded-lg overflow-hidden">
                                    <div role="button" tabindex="0" data-dashboard-analytics="attendance" data-attendance-card="present" class="flex items-center justify-between px-3 py-2.5 bg-gray-50/80 cursor-pointer hover:bg-gray-100/80 transition-colors focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-400 rounded-t-lg">
                                        <span class="text-sm font-medium text-gray-700">Total Present</span>
                                        <span class="text-xl font-bold text-green-600 tabular-nums" id="total-present-count">0</span>
                                    </div>
                                    <div class="flex items-center justify-between px-3 py-2.5 bg-white">
                                        <span class="text-sm text-gray-600">Total Absent</span>
                                        <span class="text-lg font-bold text-red-600 tabular-nums" id="total-absent-count">0</span>
                                    </div>
                                    <div class="flex items-center justify-between px-3 py-2.5 bg-white">
                                        <span class="text-sm text-gray-600">Total Late</span>
                                        <span class="text-lg font-bold text-yellow-600 tabular-nums" id="total-late-count">0</span>
                                    </div>
                                    <div class="flex items-center justify-between px-3 py-2.5 bg-white rounded-b-lg">
                                        <span class="text-sm text-gray-600">Total Over-Time</span>
                                        <span class="text-lg font-bold text-indigo-600 tabular-nums" id="total-overtime-count">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Main trend charts: text headers like attendance-analytics (no colored banner bars) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 flex flex-col">
                        <h3 class="text-lg font-semibold text-gray-800">Employee Attendance Metrics</h3>
                        <p class="text-xs text-gray-500 mt-1 mb-4">Bar chart of distinct employees with attendance logs vs approximate absent count by hour, day, week, or month.</p>
                        <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-end sm:justify-between gap-4 pb-4 mb-4 border-b border-gray-100">
                            <div class="min-w-[140px] flex-1 sm:max-w-xs">
                                <label for="attendance-filter" class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                                <select id="attendance-filter" class="block w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-lg bg-white text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="today">Today</option>
                                    <option value="week">This Week</option>
                                    <option value="month" selected>This Month</option>
                                    <option value="year">This Year</option>
                                </select>
                            </div>
                        </div>
                        <div class="h-80 min-h-[16rem] flex-1 relative">
                            <canvas id="employeeAttendanceChart" aria-label="Employee attendance metrics bar chart"></canvas>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 flex flex-col">
                        <h3 class="text-lg font-semibold text-gray-800">Visitor &amp; Traffic Metrics</h3>
                        <p class="text-xs text-gray-500 mt-1 mb-4">Bar chart of visitor log volume over the same period buckets (today by hour, week by day, etc.).</p>
                        <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-end sm:justify-between gap-4 pb-4 mb-4 border-b border-gray-100">
                            <div class="min-w-[140px] flex-1 sm:max-w-xs">
                                <label for="visitor-filter" class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                                <select id="visitor-filter" class="block w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-lg bg-white text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="today">Today</option>
                                    <option value="week">This Week</option>
                                    <option value="month" selected>This Month</option>
                                    <option value="year">This Year</option>
                                </select>
                            </div>
                        </div>
                        <div class="h-80 min-h-[16rem] flex-1 relative">
                            <canvas id="visitorTrafficChart" aria-label="Visitor and traffic metrics bar chart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Supplemental bar charts: single card, aligned with other admin pages -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 pb-6 mb-6 border-b border-gray-100">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">Attendance &amp; visitor insights</h2>
                            <p class="text-sm text-gray-500 mt-1">Compliance, top employees, age groups, plus visitor composition and services (same period).</p>
                        </div>
                        <div class="w-full lg:w-auto lg:min-w-[200px]">
                            <label for="dashboard-insights-filter" class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                            <select id="dashboard-insights-filter" class="block w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-lg bg-white text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="today">Today</option>
                                <option value="week">This Week</option>
                                <option value="month" selected>This Month</option>
                                <option value="year">This Year</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                        <div class="flex flex-col border border-gray-100 rounded-lg p-4 bg-gray-50/30">
                            <h3 class="text-sm font-semibold text-gray-800">Attendance compliance</h3>
                            <p class="text-xs text-gray-500 mt-1 mb-4">Share of employees with at least one log per bucket (same ranges as the attendance chart).</p>
                            <div class="h-72 min-h-[12rem] flex-1">
                                <canvas id="dashboardComplianceChart" aria-label="Attendance compliance bar chart"></canvas>
                            </div>
                        </div>
                        <div class="flex flex-col border border-gray-100 rounded-lg p-4 bg-gray-50/30">
                            <h3 class="text-sm font-semibold text-gray-800">Employee attendance comparison</h3>
                            <p class="text-xs text-gray-500 mt-1 mb-4">Top employees by attendance log count in the selected period.</p>
                            <div class="h-72 min-h-[12rem] flex-1">
                                <canvas id="dashboardTopEmployeesChart" aria-label="Top employees bar chart"></canvas>
                            </div>
                        </div>
                        <div class="flex flex-col border border-gray-100 rounded-lg p-4 bg-gray-50/30">
                            <h3 class="text-sm font-semibold text-gray-800">Visitors by age group</h3>
                            <p class="text-xs text-gray-500 mt-1 mb-4">From visitor birthdate on file (read-only).</p>
                            <div class="h-72 min-h-[12rem] flex-1">
                                <canvas id="dashboardVisitorAgeChart" aria-label="Visitor age distribution bar chart"></canvas>
                            </div>
                        </div>
                        <div class="flex flex-col border border-gray-100 rounded-lg p-4 bg-gray-50/30">
                            <h3 class="text-sm font-semibold text-gray-800">Resident vs non-resident visitors</h3>
                            <p class="text-xs text-gray-500 mt-1 mb-4">Visitor log counts in the selected period.</p>
                            <div class="h-72 min-h-[12rem] flex-1">
                                <canvas id="dashboardVisitorResidentChart" aria-label="Resident vs non-resident visitors bar chart"></canvas>
                            </div>
                        </div>
                        <div class="flex flex-col border border-gray-100 rounded-lg p-4 bg-gray-50/30">
                            <h3 class="text-sm font-semibold text-gray-800">Walk-in vs online appointment</h3>
                            <p class="text-xs text-gray-500 mt-1 mb-4">Based on booking flag on visitor logs.</p>
                            <div class="h-72 min-h-[12rem] flex-1">
                                <canvas id="dashboardVisitorVisitTypeChart" aria-label="Walk-in vs online appointment bar chart"></canvas>
                            </div>
                        </div>
                        <div class="flex flex-col border border-gray-100 rounded-lg p-4 bg-gray-50/30">
                            <h3 class="text-sm font-semibold text-gray-800">Services availed</h3>
                            <p class="text-xs text-gray-500 mt-1 mb-4">Top purposes from visitor logs (read-only).</p>
                            <div class="h-72 min-h-[12rem] flex-1">
                                <canvas id="dashboardVisitorServicesChart" aria-label="Services availed bar chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <!-- Dashboard analytics modals (visitor / attendance detail; opened from Total Visitors and Total Present only) -->
    <div id="dashboard-analytics-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4" role="dialog" aria-modal="true" aria-labelledby="dashboard-analytics-modal-title">
        <div class="bg-white rounded-xl shadow-xl max-w-6xl w-full max-h-[92vh] flex flex-col overflow-hidden border border-gray-100">
            <div class="px-5 py-4 border-b border-gray-100 flex items-start justify-between gap-3 shrink-0">
                <div>
                    <h3 id="dashboard-analytics-modal-title" class="text-lg font-semibold text-gray-800">Analytics</h3>
                    <p id="dashboard-analytics-modal-sub" class="text-xs text-gray-500 mt-1"></p>
                </div>
                <button type="button" id="dashboard-analytics-modal-close" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 shrink-0" aria-label="Close">✕</button>
            </div>
            <div id="dashboard-analytics-modal-loading" class="hidden px-5 py-12 text-center text-sm text-gray-500">Loading…</div>
            <div id="dashboard-analytics-modal-error" class="hidden px-5 py-8 text-center text-sm text-red-600"></div>
            <div id="dashboard-analytics-modal-body" class="hidden overflow-y-auto flex-1 px-5 py-4">
                <div id="dashboard-analytics-visitor-panel" class="hidden space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="border border-gray-100 rounded-lg p-3 bg-gray-50/30">
                            <p class="text-xs font-medium text-gray-600 mb-2">Visitor trend</p>
                            <div class="h-48 relative"><canvas id="dm-v-chart-trend" aria-hidden="true"></canvas></div>
                        </div>
                        <div class="border border-gray-100 rounded-lg p-3 bg-gray-50/30">
                            <p class="text-xs font-medium text-gray-600 mb-2">Resident vs non-resident</p>
                            <div class="h-48 relative"><canvas id="dm-v-chart-resnon" aria-hidden="true"></canvas></div>
                        </div>
                        <div class="border border-gray-100 rounded-lg p-3 bg-gray-50/30">
                            <p class="text-xs font-medium text-gray-600 mb-2">Visitors per purok (residents)</p>
                            <div class="h-48 relative"><canvas id="dm-v-chart-purok" aria-hidden="true"></canvas></div>
                        </div>
                        <div class="border border-gray-100 rounded-lg p-3 bg-gray-50/30">
                            <p class="text-xs font-medium text-gray-600 mb-2">Services availed</p>
                            <div class="h-48 relative"><canvas id="dm-v-chart-services" aria-hidden="true"></canvas></div>
                        </div>
                        <div class="border border-gray-100 rounded-lg p-3 bg-gray-50/30">
                            <p class="text-xs font-medium text-gray-600 mb-2">Walk-in vs online</p>
                            <div class="h-48 relative"><canvas id="dm-v-chart-booking" aria-hidden="true"></canvas></div>
                        </div>
                        <div class="border border-gray-100 rounded-lg p-3 bg-gray-50/30">
                            <p class="text-xs font-medium text-gray-600 mb-2">Non-resident origin (from address)</p>
                            <div class="h-48 relative"><canvas id="dm-v-chart-city" aria-hidden="true"></canvas></div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-800 mb-2">Visitor list</h4>
                        <div class="overflow-x-auto border border-gray-200 rounded-lg">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wide">
                                    <tr>
                                        <th class="px-3 py-2">Name</th>
                                        <th class="px-3 py-2">Type</th>
                                        <th class="px-3 py-2">Purok / City</th>
                                        <th class="px-3 py-2">Service</th>
                                        <th class="px-3 py-2">Visit</th>
                                        <th class="px-3 py-2">Date</th>
                                    </tr>
                                </thead>
                                <tbody id="dm-v-table-body" class="divide-y divide-gray-100 bg-white text-gray-700"></tbody>
                            </table>
                        </div>
                        <p id="dm-v-table-note" class="text-xs text-gray-500 mt-2"></p>
                        <div id="dm-v-table-pagination" class="hidden mt-3 flex flex-col sm:flex-row sm:flex-wrap sm:items-center sm:justify-between gap-2 text-sm text-gray-600"></div>
                    </div>
                </div>
                <div id="dashboard-analytics-attendance-panel" class="hidden space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="border border-gray-100 rounded-lg p-3 bg-gray-50/30 md:col-span-2">
                            <p class="text-xs font-medium text-gray-600 mb-2">Attendance trend (distinct employees with logs)</p>
                            <div class="h-48 relative"><canvas id="dm-a-chart-trend" aria-hidden="true"></canvas></div>
                        </div>
                        <div class="border border-gray-100 rounded-lg p-3 bg-gray-50/30">
                            <p class="text-xs font-medium text-gray-600 mb-2">Present vs absent (period totals)</p>
                            <div class="h-48 relative"><canvas id="dm-a-chart-presabs" aria-hidden="true"></canvas></div>
                        </div>
                        <div class="border border-gray-100 rounded-lg p-3 bg-gray-50/30">
                            <p class="text-xs font-medium text-gray-600 mb-2">Late events by period</p>
                            <div class="h-48 relative"><canvas id="dm-a-chart-late" aria-hidden="true"></canvas></div>
                        </div>
                        <div class="border border-gray-100 rounded-lg p-3 bg-gray-50/30 md:col-span-2">
                            <p class="text-xs font-medium text-gray-600 mb-2">Overtime events by period</p>
                            <div class="h-48 relative"><canvas id="dm-a-chart-ot" aria-hidden="true"></canvas></div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-800 mb-2">Employees</h4>
                        <div class="overflow-x-auto border border-gray-200 rounded-lg">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wide">
                                    <tr>
                                        <th class="px-3 py-2">Name</th>
                                        <th class="px-3 py-2">Status</th>
                                        <th class="px-3 py-2">Last log</th>
                                    </tr>
                                </thead>
                                <tbody id="dm-a-table-body" class="divide-y divide-gray-100 bg-white text-gray-700"></tbody>
                            </table>
                        </div>
                        <p id="dm-a-table-note" class="text-xs text-gray-500 mt-2"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Module for Dashboard -->
    <script>
        // Set WebSocket URL as global variable for main.js module
        window.WEBSOCKET_URL = "<?php echo WEBSOCKET_URL; ?>";
    </script>
    <script type="module" src="js/dashboard/main.js"></script>
</body>
</html>
