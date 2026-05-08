<?php
require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/../auth/helpers.php";
requireAuth(); // Require authentication - redirects to login if not authenticated

include_once '../shared/components/Sidebar.php';
include_once '../shared/components/Breadcrumb.php';

// Get current user for greeting
$currentUser = currentUser();
$userName = $currentUser ? ($currentUser['full_name'] ?? $currentUser['username']) : 'Guest';

// Pagination and search parameters
$perPage = 10; // Records per page
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Filters (profiling-system strings)
$filters = [];
$filters['chairmanship'] = isset($_GET['chairmanship']) ? trim($_GET['chairmanship']) : '';
$filters['position'] = isset($_GET['position']) ? trim($_GET['position']) : '';

// Fetch employees from profiling-system (source of truth). Attendance-system is read-only consumer.
$db = (new Database())->connect();
$profilingDbName = defined("PROFILING_DB_NAME") ? PROFILING_DB_NAME : "profiling-system";
$profilingEmployeesTable = "`{$profilingDbName}`.`barangay_official`";

// Build filter option lists directly from profiling-system (no local departments/positions tables)
$chairmanshipOptions = [];
$positionOptions = [];
try {
    $stmt = $db->prepare("
        SELECT DISTINCT bo.chairmanship
        FROM {$profilingEmployeesTable} AS bo
        WHERE bo.chairmanship IS NOT NULL AND TRIM(bo.chairmanship) <> ''
        ORDER BY bo.chairmanship ASC
    ");
    $stmt->execute();
    $chairmanshipOptions = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
} catch (Exception $e) {
    $chairmanshipOptions = [];
}

try {
    $stmt = $db->prepare("
        SELECT DISTINCT bo.position
        FROM {$profilingEmployeesTable} AS bo
        WHERE bo.position IS NOT NULL AND TRIM(bo.position) <> ''
        ORDER BY bo.position ASC
    ");
    $stmt->execute();
    $positionOptions = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
} catch (Exception $e) {
    $positionOptions = [];
}

$where = [];
$params = [];

if (!empty($searchQuery)) {
    $where[] = "(CONCAT(bo.first_name, ' ', bo.surname) LIKE :q OR CAST(bo.id AS CHAR) LIKE :q OR bo.position LIKE :q)";
    $params[':q'] = "%{$searchQuery}%";
}

// Apply filters (directly from profiling-system fields)
if (!empty($filters['chairmanship'])) {
    $where[] = "bo.chairmanship = :chairmanship";
    $params[':chairmanship'] = $filters['chairmanship'];
}

if (!empty($filters['position'])) {
    $where[] = "bo.position = :position";
    $params[':position'] = $filters['position'];
}

$whereSql = !empty($where) ? (" WHERE " . implode(" AND ", $where)) : "";
$offset = ($currentPage - 1) * $perPage;

// Total count for pagination
$countSql = "SELECT COUNT(*) as total FROM {$profilingEmployeesTable} AS bo{$whereSql}";
$countStmt = $db->prepare($countSql);
foreach ($params as $key => $val) {
    $countStmt->bindValue($key, $val, PDO::PARAM_STR);
}
$countStmt->execute();
$countRow = $countStmt->fetch(PDO::FETCH_ASSOC);
$totalRecords = (int) ($countRow['total'] ?? 0);
$totalPages = $totalRecords > 0 ? (int) ceil($totalRecords / $perPage) : 1;

// Page records
$listSql = "
    SELECT
        bo.id as employee_id,
        bo.first_name,
        bo.middle_name,
        bo.surname as last_name,
        bo.chairmanship as department_name,
        bo.position as position_name,
        bo.status as activity_name
    FROM {$profilingEmployeesTable} AS bo
    {$whereSql}
    ORDER BY bo.id DESC
    LIMIT :limit OFFSET :offset
";
$listStmt = $db->prepare($listSql);
foreach ($params as $key => $val) {
    $listStmt->bindValue($key, $val, PDO::PARAM_STR);
}
$listStmt->bindValue(':limit', (int) $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
$listStmt->execute();
$employees = $listStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

// Determine fingerprint registration status using attendance-system data only (employee_fingerprints)
$employeeIds = [];
foreach ($employees as $emp) {
    if (isset($emp->employee_id)) {
        $employeeIds[] = (string) $emp->employee_id;
    }
}

$enrolledEmployeeIds = [];
if (!empty($employeeIds)) {
    $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
    $fpStmt = $db->prepare("SELECT employee_id FROM employee_fingerprints WHERE employee_id IN ({$placeholders})");
    $fpStmt->execute($employeeIds);
    $enrolledEmployeeIds = array_flip(array_map('strval', $fpStmt->fetchAll(PDO::FETCH_COLUMN) ?: []));
}

foreach ($employees as $emp) {
    $eid = isset($emp->employee_id) ? (string) $emp->employee_id : '';
    $emp->has_fingerprint = ($eid !== '' && isset($enrolledEmployeeIds[$eid]));
}

$pagination = [
    "currentPage" => $currentPage,
    "totalPages" => $totalPages,
    "totalRecords" => $totalRecords,
    "perPage" => $perPage,
    "startRecord" => $totalRecords > 0 ? ($offset + 1) : 0,
    "endRecord" => $totalRecords > 0 ? min($offset + $perPage, $totalRecords) : 0,
];

$startRecord = $pagination['startRecord'];
$endRecord = $pagination['endRecord'];

// For employee table (keeping original structure for compatibility)
$employeesData = ["employees" => $employees];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Directory</title>
    <!-- Load global css -->
    <link rel="stylesheet" href="../utils/styles/global.css">
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Use Inter font family and custom styles from the dashboard -->
    <style>
        /* Style for the action buttons */
        .btn-primary {
            background-color: #007bff; /* Primary blue */
            transition: background-color 0.2s;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        /* Prevent body horizontal scroll */
        body {
            overflow-x: hidden;
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
    </style>
</head>
<body>

    <!-- Main Container -->
    <div class="flex min-h-screen">

        <?=Sidebar("Employees", null)?>

        <!-- 2. MAIN CONTENT AREA -->
        <main class="flex-1 md:ml-64 p-6 transition-all duration-300">

            <!-- Top Header Bar -->
            <header class="mb-6">
                <div class="flex justify-between items-center mb-1">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Employee Directory</h1>
                        <p class="text-gray-500 text-sm"><?= getGreeting($userName) ?> - Manage all current and past employees in one place.</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <p class="text-sm text-gray-500" id="current-date">September 28, 2025</p>
                    </div>
                </div>
                <?php Breadcrumb([
                    ['label' => 'Dashboard', 'link' => 'dashboard.php'],
                    ['label' => 'Employees', 'link' => 'employees.php']
                ]); ?>
            </header>

            <!-- EMPLOYEE MANAGEMENT SECTION -->
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                
                <!-- Controls: Search, Filter, and Add Button -->
                <div class="flex flex-col sm:flex-row justify-between items-center mb-6 space-y-4 sm:space-y-0">
                    
                    <!-- Search Input, Search Button, and Filter -->
                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                        <form method="GET" action="" class="relative flex-1 sm:w-96 lg:w-[500px] flex items-center gap-2" id="searchForm">
                            <div class="relative flex-1">
                                <input type="text" 
                                    name="search" 
                                    id="searchInput"
                                    placeholder="Search employee name or position…" 
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
                            <!-- Preserve filter parameters -->
                            <?php if (!empty($filters['chairmanship'])): ?>
                                <input type="hidden" name="chairmanship" value="<?= htmlspecialchars($filters['chairmanship']) ?>">
                            <?php endif; ?>
                            <?php if (!empty($filters['position'])): ?>
                                <input type="hidden" name="position" value="<?= htmlspecialchars($filters['position']) ?>">
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

                    <!-- Add Employee Button removed (attendance-system is read-only for employee data) -->
                </div>

                <!-- Employee Table -->
                <div class="table-container rounded-lg border border-gray-200">
                    <div class="inline-block min-w-full align-middle">
                        <table class="min-w-full divide-y divide-gray-200" style="min-width: 880px; width: 100%;">
                        <thead class="table-header">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Chairmanship</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Position</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($employeesData["employees"])): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    <p class="text-sm">No employees found.</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($employeesData["employees"] as $employee): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <?= htmlspecialchars(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <?= htmlspecialchars($employee->department_name ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <?= htmlspecialchars($employee->position_name ?? '') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                     <?= htmlspecialchars($employee->activity_name ? $employee->activity_name : "Office") ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <?php if (!empty($employee->has_fingerprint)): ?>
                                            <a href="biometric-registration.php?search=<?= urlencode((string)($employee->employee_id ?? '')) ?>"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-green-700 bg-green-50 rounded-lg hover:bg-green-100 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1" 
                                                title="View Employee">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                                View
                                            </a>
                                        <?php else: ?>
                                            <a href="biometric-registration.php?search=<?= urlencode((string)($employee->employee_id ?? '')) ?>"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1" 
                                                title="Enroll Fingerprint">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4a6 6 0 00-6 6v3a2 2 0 01-2 2h0"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 10v3a6 6 0 01-6 6h-1"></path>
                                                </svg>
                                                Enroll
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>

                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="mt-6 flex flex-col sm:flex-row justify-between items-center gap-4 text-sm text-gray-600">
                    <div>
                        Showing <span class="font-medium"><?= $startRecord ?></span> to <span class="font-medium"><?= $endRecord ?></span> of <span class="font-medium"><?= $totalRecords ?></span> records
                        <?php if (!empty($searchQuery) || !empty($filters)): ?>
                            <span class="text-gray-500">(filtered)</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($totalPages > 1): ?>
                    <nav class="flex space-x-1" aria-label="Pagination">
                        <?php
                        // Build query string for pagination links
                        $queryParams = [];
                        if (!empty($searchQuery)) {
                            $queryParams[] = 'search=' . urlencode($searchQuery);
                        }
                        if (!empty($filters['chairmanship'])) {
                            $queryParams[] = 'chairmanship=' . urlencode($filters['chairmanship']);
                        }
                        if (!empty($filters['position'])) {
                            $queryParams[] = 'position=' . urlencode($filters['position']);
                        }
                        $queryString = !empty($queryParams) ? '&' . implode('&', $queryParams) : '';
                        ?>
                        
                        <!-- Previous Button -->
                        <?php if ($currentPage > 1): ?>
                            <a href="?page=<?= $currentPage - 1 ?><?= $queryString ?>" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">
                                Previous
                            </a>
                        <?php else: ?>
                            <span class="px-3 py-2 border border-gray-300 rounded-lg text-gray-400 cursor-not-allowed">Previous</span>
                        <?php endif; ?>
                        
                        <!-- Page Numbers -->
                        <?php
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        
                        // Show first page if not in range
                        if ($startPage > 1): ?>
                            <a href="?page=1<?= $queryString ?>" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">1</a>
                            <?php if ($startPage > 2): ?>
                                <span class="px-3 py-2 text-gray-500">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <?php if ($i == $currentPage): ?>
                                <span class="px-3 py-2 border border-gray-300 rounded-lg bg-blue-600 text-white font-medium"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?page=<?= $i ?><?= $queryString ?>" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <!-- Show last page if not in range -->
                        <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?>
                                <span class="px-3 py-2 text-gray-500">...</span>
                            <?php endif; ?>
                            <a href="?page=<?= $totalPages ?><?= $queryString ?>" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors"><?= $totalPages ?></a>
                        <?php endif; ?>
                        
                        <!-- Next Button -->
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?page=<?= $currentPage + 1 ?><?= $queryString ?>" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">
                                Next
                            </a>
                        <?php else: ?>
                            <span class="px-3 py-2 border border-gray-300 rounded-lg text-gray-400 cursor-not-allowed">Next</span>
                        <?php endif; ?>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>


                <!-- Filter Modal -->
                <div id="filterModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="filter-modal-title" role="dialog" aria-modal="true">
                    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" id="filterModalBackdrop" aria-hidden="true"></div>

                    <div class="flex items-center justify-center min-h-screen p-4 sm:p-6">
                        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md transition-all transform sm:my-8" id="filterModalContent">
                            <form method="GET" action="">
                                <div class="flex items-center justify-between p-5 border-b border-gray-200">
                                    <h3 class="text-xl font-semibold text-gray-900" id="filter-modal-title">
                                        Filter Employees
                                    </h3>
                                    <button type="button" id="closeFilterModal" class="text-gray-400 hover:text-gray-600 focus:outline-none p-1 rounded-full hover:bg-gray-100 transition">
                                        <span class="sr-only">Close modal</span>
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>

                                <div class="p-6 space-y-4">
                                    <!-- Preserve search query -->
                                    <?php if (!empty($searchQuery)): ?>
                                        <input type="hidden" name="search" value="<?= htmlspecialchars($searchQuery) ?>">
                                    <?php endif; ?>

                                    <!-- Chairmanship / Department Filter -->
                                    <div>
                                        <label for="filter_chairmanship" class="block text-sm font-medium text-gray-700 mb-2">
                                            Chairmanship (Department)
                                        </label>
                                        <select name="chairmanship" id="filter_chairmanship" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">All</option>
                                            <?php foreach($chairmanshipOptions as $opt): ?>
                                                <option value="<?= htmlspecialchars((string)$opt) ?>" <?= (!empty($filters['chairmanship']) && $filters['chairmanship'] === (string)$opt) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars((string)$opt) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Position Filter -->
                                    <div>
                                        <label for="filter_position" class="block text-sm font-medium text-gray-700 mb-2">
                                            Position
                                        </label>
                                        <select name="position" id="filter_position" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">All</option>
                                            <?php foreach($positionOptions as $opt): ?>
                                                <option value="<?= htmlspecialchars((string)$opt) ?>" <?= (!empty($filters['position']) && $filters['position'] === (string)$opt) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars((string)$opt) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row justify-end p-5 space-y-3 sm:space-y-0 sm:space-x-3 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                                    <a href="?" class="w-full sm:w-auto px-6 py-2 text-center text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                                        Clear Filters
                                    </a>
                                    <button type="submit" class="w-full sm:w-auto px-6 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
                                        Apply Filters
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- End Filter Modal -->

        </main>
    </div>

    <!-- Modular JavaScript Entry Point -->
    <script type="module" src="./js/employees/main.js"></script>
    <script>
        // Filter Modal functionality
        const filterModal = document.getElementById('filterModal');
        const filterButton = document.getElementById('filterButton');
        const closeFilterModal = document.getElementById('closeFilterModal');
        const filterModalBackdrop = document.getElementById('filterModalBackdrop');

        // Open filter modal
        if (filterButton) {
            filterButton.addEventListener('click', () => {
                filterModal.classList.remove('hidden');
            });
        }

        // Close filter modal
        if (closeFilterModal) {
            closeFilterModal.addEventListener('click', () => {
                filterModal.classList.add('hidden');
            });
        }

        // Close on backdrop click
        if (filterModalBackdrop) {
            filterModalBackdrop.addEventListener('click', () => {
                filterModal.classList.add('hidden');
            });
        }

        // Close on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !filterModal.classList.contains('hidden')) {
                filterModal.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
