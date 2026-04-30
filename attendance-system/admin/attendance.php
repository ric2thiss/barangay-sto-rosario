<?php
require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/../auth/helpers.php";
requireAuth(); // Require authentication - redirects to login if not authenticated

include_once '../shared/components/Sidebar.php';
include_once '../shared/components/Breadcrumb.php';

// Get current user for greeting
$currentUser = currentUser();
$userName = $currentUser ? ($currentUser['full_name'] ?? $currentUser['username']) : 'Guest';

// Get pagination and search parameters
$currentPage = isset($_GET['page']) ? $_GET['page'] : 1;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$fromDate = isset($_GET['from']) ? trim($_GET['from']) : null;
$toDate = isset($_GET['to']) ? trim($_GET['to']) : null;
$perPage = 10; // Records per page

$activityFilterInput = isset($_GET['activity_id']) ? trim((string) $_GET['activity_id']) : '';
$activityFilter = null;
if ($activityFilterInput === '0') {
    $activityFilter = 0;
} elseif ($activityFilterInput !== '' && ctype_digit($activityFilterInput)) {
    $activityFilter = (int) $activityFilterInput;
}

// Get data from controller
$attendanceController = new AttendanceController();
$data = $attendanceController->getPaginatedAttendances($currentPage, $perPage, $searchQuery, $fromDate, $toDate, $activityFilter);

try {
    $activityFilterList = Activity::query()->orderByRaw('activity_date DESC, id DESC')->limit(200)->get();
} catch (Throwable $e) {
    $activityFilterList = [];
    error_log('attendance.php activities filter: ' . $e->getMessage());
}

// Extract data for view
$attendances = $data['attendances'];
$pagination = $data['pagination'];
$searchQuery = $data['searchQuery'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Attendance</title>
    <meta name="base-url" content="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="../utils/styles/global.css">
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Use Inter font family -->
    <style>
        /* Prevent body horizontal scroll */
        body {
            overflow-x: hidden;
        }
        /* Custom darker button color for the new Attendance Now style */
        .btn-dark {
            background-color: #374151; /* A deep slate color */
        }
        .btn-dark:hover {
            background-color: #1f2937;
        }
        /* Table styles */
        .table-header {
            background-color: #e5e7eb; /* Light gray for table header */
        }
        /* Scrollable table container */
        .table-container {
            overflow-x: auto;
            overflow-y: visible;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e0 #f7fafc;
            width: 100%;
            position: relative;
        }
        .table-container::-webkit-scrollbar {
            height: 8px;
        }
        .table-container::-webkit-scrollbar-track {
            background: #f7fafc;
            border-radius: 4px;
        }
        .table-container::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 4px;
        }
        .table-container::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }
        /* Add border to each table row */
        tbody tr {
            border-bottom: 1px solid #e5e7eb;
        }
        tbody tr:last-child {
            border-bottom: none;
        }
        /* Ensure main content doesn't overflow */
        main {
            overflow-x: hidden;
            max-width: 100%;
        }
        /* Toast Notification Styles */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 400px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            transform: translateX(400px);
            opacity: 0;
            transition: all 0.3s ease-in-out;
        }
        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }
        .toast.hide {
            transform: translateX(400px);
            opacity: 0;
        }
        .toast-icon {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .toast-icon.success {
            background: #10b981;
        }
        .toast-icon.error {
            background: #ef4444;
        }
        .toast.success {
            border-left: 4px solid #10b981;
        }
        .toast.error {
            border-left: 4px solid #ef4444;
        }
        .toast-content {
            flex: 1;
        }
        .toast-title {
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
        }
        .toast-message {
            font-size: 14px;
            color: #6b7280;
        }
    </style>
</head>
<body>

    <!-- Main Container -->
    <div class="flex min-h-screen">

        
        <?=Sidebar("Attendance", null)?>

        <!-- 2. MAIN CONTENT AREA -->
        <main class="flex-1 md:ml-64 p-6 transition-all duration-300">

            <!-- Top Header Bar -->
            <header class="mb-6">
                <div class="flex justify-between items-center mb-1">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Employee Attendance</h1>
                        <p class="text-gray-500 text-sm"><?= getGreeting($userName) ?></p>
                    </div>
                    <div class="flex items-center gap-4">
                        <p class="text-sm text-gray-500" id="current-date">September 28, 2025</p>
                        <a href="attendance-standalone.php" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                            <span>Open in another tab</span>
                        </a>
                    </div>
                </div>
                <?php Breadcrumb([
                    ['label' => 'Dashboard', 'link' => 'dashboard.php'],
                    ['label' => 'Attendance', 'link' => 'attendance.php']
                ]); ?>

                <!-- Top actions: event dropdown + Attendance Now + Export (single row) -->
                <div class="flex flex-nowrap items-end justify-end gap-3 overflow-x-auto pb-1 mt-4">
                        <div class="flex flex-col shrink-0 min-w-[200px] max-w-[280px]">
                            <label for="attendance-activity-select" class="text-xs font-medium text-gray-600 mb-1">Activity / event</label>
                            <select id="attendance-activity-select" class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 bg-white shadow-sm h-[38px]">
                                <option value="">Loading…</option>
                            </select>
                        </div>
                        <button type="button" onclick="window.location.href='biometrics://identify'" class="flex items-center shrink-0 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-md text-sm h-[38px]">
                            <svg class="w-4 h-4 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Attendance Now
                        </button>
                        <button type="button" class="flex items-center shrink-0 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition-colors shadow-md text-sm h-[38px]">
                            <svg class="w-4 h-4 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Export Attendance
                        </button>
                </div>
            </header>
            
            <!-- NEW TWO-COLUMN CONTENT GRID -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- LEFT COLUMN (Realtime Insight & Filter) - Takes 1/3 width on desktop -->
                <div class="lg:col-span-1 space-y-6">
                    
                    <!-- Realtime Insight Card (UPDATED SECTION) -->
                    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                        <h2 class="font-semibold text-gray-800 mb-4">Realtime Insight</h2>
                        
                        <!-- Connection Status Indicator -->
                        <div class="mb-4 flex items-center space-x-2 text-sm">
                            <span id="ws-status-indicator" class="inline-block w-3 h-3 rounded-full bg-gray-400"></span>
                            <span id="ws-status-text" class="text-gray-600">Connecting...</span>
                        </div>
                        <div class="mb-4 pt-3 border-t border-gray-200">
                            <label class="flex items-start gap-3 cursor-pointer text-sm text-gray-700">
                                <input type="checkbox" id="attendance-speech-toggle" class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4 shrink-0" checked>
                                <span>
                                    <span class="font-medium text-gray-800">Read attendance alerts aloud</span>
                                    <span class="block text-xs text-gray-500 mt-0.5">Success: speaks title and employee name. Errors: title only. New alerts stop the previous voice.</span>
                                </span>
                            </label>
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
                        <button onclick="window.location.href='biometrics://identify'" class="w-full py-3 btn-dark hover:bg-gray-700 text-white font-semibold rounded-lg transition-colors shadow-md text-lg flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Attendance Now
                        </button>
                    </div>

                    <!-- Filter Card -->
                    <div id="filterCard" class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 hidden">
                        <h2 class="font-semibold text-gray-800 mb-4">Filter</h2>
                        
                        <form method="GET" action="" id="filterForm">
                            <div class="space-y-4">
                                <!-- From Date -->
                                <div>
                                    <label for="filter-from" class="block text-sm font-medium text-gray-500 mb-1">From</label>
                                    <div class="relative">
                                        <input type="date" id="filter-from" name="from" value="<?= htmlspecialchars($fromDate ?? '') ?>"
                                            class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 transition">
                                    </div>
                                </div>

                                <!-- To Date -->
                                <div>
                                    <label for="filter-to" class="block text-sm font-medium text-gray-500 mb-1">To</label>
                                    <div class="relative">
                                        <input type="date" id="filter-to" name="to" value="<?= htmlspecialchars($toDate ?? '') ?>"
                                            class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 transition">
                                    </div>
                                </div>

                                <div>
                                    <label for="filter-activity" class="block text-sm font-medium text-gray-500 mb-1">Activity</label>
                                    <select id="filter-activity" name="activity_id" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 bg-white">
                                        <option value="">All activities</option>
                                        <option value="0" <?= $activityFilterInput === '0' ? 'selected' : '' ?>>No event</option>
                                        <?php foreach ($activityFilterList as $act): ?>
                                            <?php
                                            $aid = is_object($act) ? (int) ($act->id ?? 0) : (int) ($act['id'] ?? 0);
                                            $aname = is_object($act) ? (string) ($act->name ?? '') : (string) ($act['name'] ?? '');
                                            $adate = is_object($act) ? (string) ($act->activity_date ?? '') : (string) ($act['activity_date'] ?? '');
                                            $asrc = is_object($act) ? (string) ($act->source ?? '') : (string) ($act['source'] ?? '');
                                            ?>
                                            <option value="<?= $aid ?>" <?= (string) $aid === $activityFilterInput ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($aname . ' (' . $adate . ', ' . $asrc . ')') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Preserve search parameter -->
                                <?php if (!empty($searchQuery)): ?>
                                    <input type="hidden" name="search" value="<?= htmlspecialchars($searchQuery) ?>">
                                <?php endif; ?>

                                <div class="flex gap-2">
                                    <button type="submit" class="flex-1 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-sm text-sm">
                                        Apply Filter
                                    </button>
                                    <a href="?<?= !empty($searchQuery) ? 'search=' . urlencode($searchQuery) : '' ?>" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition-colors shadow-sm text-sm flex items-center justify-center">
                                        Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
                
                <!-- RIGHT COLUMN (Recent Log & Attendance Records) - Takes 2/3 width on desktop -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Recent Log/Details Card (Combined Top Right section) -->
                    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 flex flex-col md:flex-row items-stretch">
                        
                        <!-- Fingerprint/Photo Area -->
                        <div class="flex flex-shrink-0 mb-4 md:mb-0 md:mr-6 items-start">
                            <div class="w-36 h-36 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center">
                                <img src="./logo.png" alt="" id="employee_photo" class="w-full h-full object-cover">
                            </div>
                        </div>

                        <!-- Employee Details -->
                        <div class="flex-grow grid grid-cols-2 gap-x-6 gap-y-2 border-t md:border-t-0 pt-4 md:pt-0">
                            
                            <div class="col-span-2">
                                <h3 class="text-xl font-bold text-gray-900" id="name">-</h3>
                                <p class="text-sm text-gray-500">Employee</p>
                            </div>

                            <div class="col-span-1">
                                <p class="text-xs text-gray-500 uppercase">Role</p>
                                <p class="font-medium text-gray-700" id="role">-</p>
                            </div>
                            <div class="col-span-1">
                                <p class="text-xs text-gray-500 uppercase">Employee ID</p>
                                <p class="font-medium text-gray-700" id="employee_id">-</p>
                            </div>

                            <div class="col-span-1">
                                <p class="text-xs text-gray-500 uppercase">Time In</p>
                                <p class="font-medium text-gray-700" id="time_in">-</p>
                            </div>
                            <div class="col-span-1">
                                <p class="text-xs text-gray-500 uppercase">Time Out</p>
                                <p class="font-medium text-gray-700" id="time_out">-</p>
                            </div>

                            <!-- Morning In Status -->
                            <div class="col-span-2 mt-2">
                                <span class="inline-flex items-center text-green-600 font-semibold text-sm">
                                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span id="window">-</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Records Table Card -->
                    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Attendances Records</h2>
                        
                        <!-- Search & Search Button (Updated layout) -->
                        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto mb-4">
                            <form method="GET" action="" class="relative flex-1 sm:w-96 lg:w-[500px] flex items-center gap-2" id="searchForm">
                                <div class="relative flex-1">
                                    <input type="text" 
                                        name="search" 
                                        id="search-employee-record"
                                        placeholder="Search employee name, ID, or status..." 
                                        value="<?= htmlspecialchars($searchQuery) ?>"
                                        class="w-full py-2 pl-10 pr-10 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    <?php if (!empty($searchQuery)): ?>
                                    <a href="?" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap">
                                    Search
                                </button>
                                <!-- Preserve date filter parameters -->
                                <?php if (!empty($fromDate)): ?>
                                    <input type="hidden" name="from" value="<?= htmlspecialchars($fromDate) ?>">
                                <?php endif; ?>
                                <?php if (!empty($toDate)): ?>
                                    <input type="hidden" name="to" value="<?= htmlspecialchars($toDate) ?>">
                                <?php endif; ?>
                                <?php if ($activityFilterInput !== ''): ?>
                                    <input type="hidden" name="activity_id" value="<?= htmlspecialchars($activityFilterInput) ?>">
                                <?php endif; ?>
                            </form>
                            <!-- Filter Button -->
                            <button type="button" id="filterButton" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition-colors flex items-center gap-2 whitespace-nowrap">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                Filters
                            </button>
                        </div>
                        
                        <!-- Table Wrapper for Horizontal Scroll on small screens -->
                        <div class="table-container rounded-lg border border-gray-200">
                            <div class="block w-full align-middle">
                                <table class="w-full divide-y divide-gray-200">
                                <thead class="table-header">
                                    <tr>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date / Time</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activity</th>
                                    </tr>
                                </thead>
                                <tbody id="attendance-table-body" class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($attendances)): ?>
                                    <tr id="no-records-row">
                                        <td colspan="5" class="px-3 py-8 text-center text-gray-500">
                                            <p class="text-sm">No attendance records found.</p>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach($attendances as $attendance):?>
                                    <?php
                                    // Format the date/time to "Jan 13, 2026 08:57:57 AM" format
                                    $formattedDateTime = '';
                                    if (!empty($attendance->attendance_time)) {
                                        try {
                                            $dateTime = new DateTime($attendance->attendance_time);
                                            $formattedDateTime = $dateTime->format('M j, Y h:i:s A');
                                        } catch (Exception $e) {
                                            $formattedDateTime = $attendance->attendance_time;
                                        }
                                    }
                                    ?>
                                    <tr class="hover:bg-gray-50 transition duration-150">
                                        <td class="px-3 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($attendance->employee_id ?? '') ?></td>
                                        <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($attendance->full_name ?? '') ?></td>
                                        <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($formattedDateTime) ?></td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800"><?= htmlspecialchars($attendance->window_label ?? $attendance->window ?? '') ?></span>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-600"><?= htmlspecialchars($attendance->activity_name ?? '—') ?></td>
                                    </tr>
                                    <?php endforeach ?>
                                    <?php endif; ?>
                                
                                </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6 flex flex-col sm:flex-row justify-between items-center gap-4 text-sm text-gray-600">
                            <div>
                                Showing <span class="font-medium"><?= $pagination['startRecord'] ?></span> to <span class="font-medium"><?= $pagination['endRecord'] ?></span> of <span class="font-medium"><?= $pagination['totalRecords'] ?></span> records
                                <?php if (!empty($searchQuery) || !empty($fromDate) || !empty($toDate) || $activityFilterInput !== ''): ?>
                                    <span class="text-gray-500">(filtered)</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($pagination['totalPages'] > 1): ?>
                            <nav class="flex space-x-1" aria-label="Pagination">
                                <!-- Previous Button -->
                                <?php 
                                // Build query string for pagination links
                                $queryParams = [];
                                if (!empty($searchQuery)) {
                                    $queryParams[] = 'search=' . urlencode($searchQuery);
                                }
                                if (!empty($fromDate)) {
                                    $queryParams[] = 'from=' . urlencode($fromDate);
                                }
                                if (!empty($toDate)) {
                                    $queryParams[] = 'to=' . urlencode($toDate);
                                }
                                if ($activityFilterInput !== '') {
                                    $queryParams[] = 'activity_id=' . urlencode($activityFilterInput);
                                }
                                $queryString = !empty($queryParams) ? '&' . implode('&', $queryParams) : '';
                                ?>
                                <?php if ($pagination['currentPage'] > 1): ?>
                                    <a href="?page=<?= $pagination['currentPage'] - 1 ?><?= $queryString ?>" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">
                                        Previous
                                    </a>
                                <?php else: ?>
                                    <span class="px-3 py-2 border border-gray-300 rounded-lg text-gray-400 cursor-not-allowed">Previous</span>
                                <?php endif; ?>
                                
                                <!-- Page Numbers -->
                                <?php
                                $startPage = max(1, $pagination['currentPage'] - 2);
                                $endPage = min($pagination['totalPages'], $pagination['currentPage'] + 2);
                                
                                // Show first page if not in range
                                if ($startPage > 1): ?>
                                    <a href="?page=1<?= $queryString ?>" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">1</a>
                                    <?php if ($startPage > 2): ?>
                                        <span class="px-3 py-2 text-gray-500">...</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <?php if ($i == $pagination['currentPage']): ?>
                                        <span class="px-3 py-2 border border-gray-300 rounded-lg bg-blue-600 text-white font-medium"><?= $i ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?= $i ?><?= $queryString ?>" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors"><?= $i ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <!-- Show last page if not in range -->
                                <?php if ($endPage < $pagination['totalPages']): ?>
                                    <?php if ($endPage < $pagination['totalPages'] - 1): ?>
                                        <span class="px-3 py-2 text-gray-500">...</span>
                                    <?php endif; ?>
                                    <a href="?page=<?= $pagination['totalPages'] ?><?= $queryString ?>" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors"><?= $pagination['totalPages'] ?></a>
                                <?php endif; ?>
                                
                                <!-- Next Button -->
                                <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                                    <a href="?page=<?= $pagination['currentPage'] + 1 ?><?= $queryString ?>" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">
                                        Next
                                    </a>
                                <?php else: ?>
                                    <span class="px-3 py-2 border border-gray-300 rounded-lg text-gray-400 cursor-not-allowed">Next</span>
                                <?php endif; ?>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <!-- Toast Notification (Success/Error) -->
    <div id="attendance-toast" class="toast">
        <div class="toast-icon success">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <div class="toast-content">
            <div class="toast-title" id="toast-title">Attendance Logged Successfully</div>
            <div class="toast-message" id="toast-message">New attendance record has been added.</div>
        </div>
    </div>

    <!-- Pass PHP config values to JavaScript via meta tags -->
    <meta name="websocket-url" content="<?php echo htmlspecialchars(WEBSOCKET_URL); ?>">
    <meta name="attendance-api-url" content="<?php echo htmlspecialchars(API_ENDPOINT_ATTENDANCES); ?>">
    <meta name="activities-options-url" content="<?php echo htmlspecialchars(API_ENDPOINT_ACTIVITIES_OPTIONS); ?>">
    <meta name="activities-active-url" content="<?php echo htmlspecialchars(API_ENDPOINT_ACTIVITIES_ACTIVE); ?>">
    
    <!-- Modular JavaScript Entry Point -->
    <script type="module" 
            data-websocket-url="<?php echo htmlspecialchars(WEBSOCKET_URL); ?>"
            data-attendance-api-url="<?php echo htmlspecialchars(API_ENDPOINT_ATTENDANCES); ?>"
            data-activities-options-url="<?php echo htmlspecialchars(API_ENDPOINT_ACTIVITIES_OPTIONS); ?>"
            data-activities-active-url="<?php echo htmlspecialchars(API_ENDPOINT_ACTIVITIES_ACTIVE); ?>"
            src="./js/attendance/main.js"></script>

    <!-- Filter Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterButton = document.getElementById('filterButton');
            const filterCard = document.getElementById('filterCard');
            
            if (filterButton && filterCard) {
                filterButton.addEventListener('click', function() {
                    filterCard.classList.toggle('hidden');
                });
            }
        });
    </script>

</body>
</html>