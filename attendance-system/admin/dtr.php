<?php
require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/../auth/helpers.php";
requireAuth(); // Require authentication - redirects to login if not authenticated

include_once '../shared/components/Sidebar.php';
include_once '../shared/components/Breadcrumb.php';

// Get current user for greeting
$currentUser = currentUser();
$userName = $currentUser ? ($currentUser['full_name'] ?? $currentUser['username']) : 'Guest';

// Get employees for dropdown:
// - IDs from employee_fingerprints, attendances, and profiling barangay_official (so officials appear even before first clock-in)
// - Enrich names from profiling DB when available
// - Only filter attendances by deleted_at when that column exists (same as SchemaColumnCache elsewhere)
try {
    $pdo = (new Database())->connect();
    $profilingDbName = defined("PROFILING_DB_NAME") ? PROFILING_DB_NAME : "profiling-system";
    $profDbQ = '`' . str_replace('`', '``', $profilingDbName) . '`';

    $attendanceIdsSql = SchemaColumnCache::attendancesHasDeletedAt()
        ? 'SELECT DISTINCT employee_id FROM attendances WHERE deleted_at IS NULL'
        : 'SELECT DISTINCT employee_id FROM attendances';

    $sql = "
        SELECT
            t.employee_id AS employee_id,
            bo.first_name,
            bo.middle_name,
            bo.surname AS last_name,
            NULL AS suffix
        FROM (
            SELECT DISTINCT employee_id FROM employee_fingerprints
            UNION
            {$attendanceIdsSql}
            UNION
            SELECT DISTINCT CAST(bo_all.id AS CHAR) AS employee_id
            FROM {$profDbQ}.`barangay_official` AS bo_all
        ) AS t
        LEFT JOIN {$profDbQ}.`barangay_official` AS bo
            ON CAST(t.employee_id AS CHAR) = CAST(bo.id AS CHAR)
        ORDER BY (bo.surname IS NULL) ASC, bo.surname ASC, bo.first_name ASC, t.employee_id ASC
        LIMIT 500
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log("DTR Page: Error fetching employees for dropdown: " . $e->getMessage());
    $employees = [];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Time Record (DTR)</title>
    <link rel="stylesheet" href="../utils/styles/global.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body {
            overflow-x: hidden;
        }
        .table-header {
            background-color: #e5e7eb;
        }
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
        tbody tr {
            border-bottom: 1px solid #e5e7eb;
        }
        tbody tr:last-child {
            border-bottom: none;
        }
        main {
            overflow-x: hidden;
            max-width: 100%;
        }
        /* Print styles for DTR form - Pixel-accurate Civil Service Form No. 48 */
        #dtr-print-content {
            display: none;
            position: absolute;
            left: -9999px;
            top: -9999px;
        }
        @media print {
            @page {
                size: A4 portrait;
                margin: 0.5cm;
            }
            body {
                margin: 0;
                padding: 0;
                background: white;
            }
            /* Hide everything except print content */
            body > *:not(#dtr-print-content) {
                display: none !important;
            }
            #dtr-print-content {
                display: block !important;
                visibility: visible !important;
                position: static !important;
                left: auto !important;
                top: auto !important;
                width: 100% !important;
                height: 100vh !important;
                margin: 0 !important;
                padding: 5px 8px !important;
                box-sizing: border-box;
                background: white !important;
                font-family: 'Times New Roman', Times, serif !important;
                page-break-inside: avoid !important;
            }
            #dtr-print-content * {
                visibility: visible !important;
                display: block;
            }
            #dtr-print-content > div {
                display: flex !important;
            }
            #dtr-print-content table {
                display: table !important;
            }
            #dtr-print-content tr {
                display: table-row !important;
            }
            #dtr-print-content td,
            #dtr-print-content th {
                display: table-cell !important;
            }
            /* Ensure side-by-side forms stay together */
            #dtr-print-content > div {
                width: 100%;
                display: flex;
                justify-content: space-between;
                gap: 10px;
                overflow: visible;
            }
            #dtr-print-content > div > div {
                flex: 0 0 48%;
                width: 48%;
                page-break-inside: avoid !important;
                page-break-after: avoid !important;
                page-break-before: avoid !important;
                box-sizing: border-box;
                display: flex !important;
                flex-direction: column;
                height: 100%;
                overflow: hidden;
                margin: 0 3px;
                padding: 0 6px;
            }
            /* Fixed table widths for consistency */
            #dtr-print-content table {
                width: 100% !important;
                border-collapse: collapse;
                table-layout: fixed;
                margin-left: auto !important;
                margin-right: auto !important;
            }
            /* Thick outer borders, thin inner borders */
            #dtr-print-content table {
                border: 2px solid #000;
            }
            #dtr-print-content table th,
            #dtr-print-content table td {
                border: 1px solid #000;
            }
            /* Prevent page breaks inside rows and table */
            #dtr-print-content table {
                page-break-inside: avoid !important;
            }
            #dtr-print-content table thead {
                display: table-header-group !important;
            }
            #dtr-print-content table tbody {
                display: table-row-group !important;
            }
            #dtr-print-content table tr {
                page-break-inside: avoid !important;
                page-break-after: auto !important;
            }
            #dtr-print-content table td,
            #dtr-print-content table th {
                page-break-inside: avoid !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <!-- Main Container -->
    <div class="flex min-h-screen">

        <?=Sidebar("DTR", null)?>

        <!-- MAIN CONTENT AREA -->
        <main class="flex-1 md:ml-64 p-6 transition-all duration-300">

            <!-- Top Header Bar -->
            <header class="mb-6">
                <div class="flex justify-between items-center mb-1">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Daily Time Record (DTR)</h1>
                        <p class="text-gray-500 text-sm"><?= getGreeting($userName) ?></p>
                    </div>
                </div>
                <?php Breadcrumb([
                    ['label' => 'Dashboard', 'link' => 'dashboard.php'],
                    ['label' => 'DTR', 'link' => 'dtr.php']
                ]); ?>
            </header>

            <!-- Employee Selection and Filters Card -->
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 mb-6 no-print">
                <h2 class="font-semibold text-gray-800 mb-4">Employee Selection & Filters</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Employee Selection -->
                    <div>
                        <label for="employee-select" class="block text-sm font-medium text-gray-500 mb-1">Select Employee</label>
                        <select id="employee-select" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- Select Employee --</option>
                            <?php if (!empty($employees)): ?>
                                <?php foreach ($employees as $employee): ?>
                                    <?php
                                    // Handle both object and array formats (QueryBuilder returns objects)
                                    $empId = '';
                                    $firstName = '';
                                    $lastName = '';
                                    $middleName = '';
                                    $suffix = '';
                                    
                                    if (is_object($employee)) {
                                        $empId = $employee->employee_id ?? '';
                                        $firstName = $employee->first_name ?? '';
                                        $lastName = $employee->last_name ?? '';
                                        $middleName = $employee->middle_name ?? '';
                                        $suffix = $employee->suffix ?? '';
                                    } else {
                                        $empId = $employee['employee_id'] ?? '';
                                        $firstName = $employee['first_name'] ?? '';
                                        $lastName = $employee['last_name'] ?? '';
                                        $middleName = $employee['middle_name'] ?? '';
                                        $suffix = $employee['suffix'] ?? '';
                                    }
                                    
                                    // Build full name
                                    $nameParts = array_filter([$firstName, $middleName, $lastName, $suffix]);
                                    $fullName = trim(implode(' ', $nameParts));
                                    
                                    // Skip if no employee ID
                                    if (empty($empId)) continue;
                                    ?>
                                    <option value="<?= htmlspecialchars($empId) ?>">
                                        <?= htmlspecialchars($empId) ?><?= !empty($fullName) ? ' - ' . htmlspecialchars($fullName) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>No employees found in database</option>
                            <?php endif; ?>
                        </select>
                        <?php if (empty($employees)): ?>
                            <p class="mt-1 text-xs text-red-600">No employees found. Please add employees first.</p>
                        <?php endif; ?>
                    </div>

                    <!-- From Date -->
                    <div>
                        <label for="from-date" class="block text-sm font-medium text-gray-500 mb-1">From Date</label>
                        <input type="date" id="from-date" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- To Date -->
                    <div>
                        <label for="to-date" class="block text-sm font-medium text-gray-500 mb-1">To Date</label>
                        <input type="date" id="to-date" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div class="mt-4 flex gap-2">
                    <button id="load-data-btn" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-sm text-sm">
                        Load Attendance Data
                    </button>
                    <button id="view-dtr-btn" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors shadow-sm text-sm" disabled>
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        View DTR
                    </button>
                    <button id="print-dtr-btn" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors shadow-sm text-sm" disabled>
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Print DTR
                    </button>
                </div>
            </div>

            <!-- Employee Info Card -->
            <div id="employee-info-card" class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 mb-6 hidden no-print">
                <h2 class="font-semibold text-gray-800 mb-2" id="employee-name-display">-</h2>
                <p class="text-sm text-gray-500" id="employee-id-display">Employee ID: -</p>
            </div>

            <!-- Charts Section -->
            <div id="charts-section" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 hidden no-print">
                <!-- Bar Chart: Hours Rendered Per Day -->
                <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                    <h2 class="font-semibold text-gray-800 mb-4">Hours Rendered Per Day</h2>
                    <div class="h-80">
                        <canvas id="hours-chart"></canvas>
                    </div>
                </div>

                <!-- Pie Chart: Attendance Status Breakdown -->
                <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                    <h2 class="font-semibold text-gray-800 mb-4">Attendance Status Breakdown</h2>
                    <div class="h-80">
                        <canvas id="status-chart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Main Attendance Table -->
            <div id="attendance-table-section" class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 mb-6 hidden no-print">
                <h2 class="font-semibold text-gray-800 mb-4">Attendance Records</h2>
                
                <div class="table-container rounded-lg border border-gray-200 w-full">
                    <div class="w-full align-middle">
                        <table class="w-full divide-y divide-gray-200">
                            <thead class="table-header">
                                <tr>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Morning In</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Morning Out</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Afternoon In</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Afternoon Out</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Hours</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody id="attendance-table-body" class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td colspan="7" class="px-3 py-8 text-center text-gray-500">
                                        <p class="text-sm">Select an employee and date range to view attendance records.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Attendance Pagination -->
                <div id="attendance-pagination" class="mt-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3 hidden">
                    <div class="text-sm text-gray-600">
                        <span id="attendance-range"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <label for="attendance-page-size" class="text-sm text-gray-600">Rows</label>
                        <select id="attendance-page-size" class="p-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        <button id="attendance-prev" type="button" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            Prev
                        </button>
                        <span id="attendance-page-info" class="text-sm text-gray-700 min-w-[90px] text-center"></span>
                        <button id="attendance-next" type="button" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            Next
                        </button>
                    </div>
                </div>
            </div>

            <!-- Anomalies Table -->
            <div id="anomalies-section" class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 mb-6 hidden no-print">
                <h2 class="font-semibold text-gray-800 mb-4">Anomalies & Irregularities</h2>
                
                <div class="table-container rounded-lg border border-gray-200 w-full">
                    <div class="w-full align-middle">
                        <table class="w-full divide-y divide-gray-200">
                            <thead class="table-header">
                                <tr>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Anomalies</th>
                                </tr>
                            </thead>
                            <tbody id="anomalies-table-body" class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td colspan="2" class="px-3 py-8 text-center text-gray-500">
                                        <p class="text-sm">No anomalies detected.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Anomalies Pagination -->
                <div id="anomalies-pagination" class="mt-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3 hidden">
                    <div class="text-sm text-gray-600">
                        <span id="anomalies-range"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <label for="anomalies-page-size" class="text-sm text-gray-600">Rows</label>
                        <select id="anomalies-page-size" class="p-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="5" selected>5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                        </select>
                        <button id="anomalies-prev" type="button" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            Prev
                        </button>
                        <span id="anomalies-page-info" class="text-sm text-gray-700 min-w-[90px] text-center"></span>
                        <button id="anomalies-next" type="button" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            Next
                        </button>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- Print DTR Content (Hidden until print) -->
    <div id="dtr-print-content"></div>

    <!-- View DTR Modal -->
    <div id="view-dtr-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Modal Header -->
            <div class="flex justify-between items-center p-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Daily Time Record (DTR)</h2>
                <button id="close-view-dtr-modal" class="text-gray-500 hover:text-gray-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-4 bg-gray-50">
                <div id="view-dtr-content" class="bg-white p-4 rounded-lg">
                    <!-- DTR content will be rendered here -->
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Modules -->
    <script type="module" src="js/dtr/main.js"></script>
    
    <!-- App Name Updater (standalone) -->
    <script src="js/shared/appName.js"></script>

    <!-- Sidebar Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('sidebar');
            
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('-translate-x-full');
                });
            }
        });
    </script>

</body>
</html>
