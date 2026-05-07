<?php
require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/../auth/helpers.php";
requireAuth(); // Require authentication - redirects to login if not authenticated

include_once '../shared/components/Sidebar.php';
include_once '../shared/components/Breadcrumb.php';

// Get current user for greeting
$currentUser = currentUser();
$userName = $currentUser ? ($currentUser['full_name'] ?? $currentUser['username']) : 'Guest';

// Get pagination, search, and filter parameters
$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$perPage = 10; // Records per page

// Get filters
$filters = [];
if (isset($_GET['is_pwd']) && !empty($_GET['is_pwd'])) {
    $filters['is_pwd'] = $_GET['is_pwd'];
}
if (isset($_GET['is_deceased']) && !empty($_GET['is_deceased'])) {
    $filters['is_deceased'] = $_GET['is_deceased'];
}

// Get data from controller
$residentController = new ResidentController();
$data = $residentController->getPaginatedResidents($currentPage, $perPage, $searchQuery, $filters);

// Extract data for view
$residents = $data['residents'];
$pagination = $data['pagination'];
$searchQuery = $data['searchQuery'];

// Determine fingerprint registration status using attendance-system data ONLY.
// - Residents can be enrolled directly (resident_fingerprints) OR via employee enrollment (employee_fingerprints) if linked.
try {
    $db = (new Database())->connect();

    $residentIds = [];
    if (is_array($residents)) {
        foreach ($residents as $r) {
            if (!empty($r['resident_id'])) {
                $residentIds[] = (int) $r['resident_id'];
            }
        }
    }

    // employees table may not exist (employees are owned by profiling-system).
    // For fingerprint status, treat resident_id as the employee_fingerprints key when present.
    $employeeIdByResidentId = [];
    if (!empty($residentIds)) {
        foreach ($residentIds as $rid) {
            $employeeIdByResidentId[(int) $rid] = (string) $rid;
        }
    }

    $employeeIds = array_values(array_unique(array_filter(array_values($employeeIdByResidentId))));
    $enrolledEmployeeIds = [];
    if (!empty($employeeIds)) {
        $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
        $stmt = $db->prepare("SELECT employee_id FROM employee_fingerprints WHERE employee_id IN ({$placeholders})");
        $stmt->execute($employeeIds);
        $enrolledEmployeeIds = array_flip(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []));
    }

    $enrolledResidentIds = [];
    if (!empty($residentIds)) {
        $placeholders = implode(',', array_fill(0, count($residentIds), '?'));
        $stmt = $db->prepare("SELECT resident_id FROM resident_fingerprints WHERE resident_id IN ({$placeholders})");
        $stmt->execute($residentIds);
        $enrolledResidentIds = array_flip(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []));
    }

    if (is_array($residents)) {
        foreach ($residents as &$resident) {
            $rid = !empty($resident['resident_id']) ? (int) $resident['resident_id'] : 0;
            $employeeId = $rid && isset($employeeIdByResidentId[$rid]) ? $employeeIdByResidentId[$rid] : null;
            $resident['employee_id'] = $employeeId;
            $residentHas = ($rid && isset($enrolledResidentIds[(string) $rid]));
            $employeeHas = ($employeeId !== null && isset($enrolledEmployeeIds[(string) $employeeId]));
            $resident['has_fingerprint'] = ($residentHas || $employeeHas);
        }
        unset($resident);
    }
} catch (Exception $e) {
    if (is_array($residents)) {
        foreach ($residents as &$resident) {
            $resident['employee_id'] = $resident['employee_id'] ?? null;
            $resident['has_fingerprint'] = false;
        }
        unset($resident);
    }
    error_log("Error determining resident fingerprint status: " . $e->getMessage());
}

// $employeeCurrentActivities = (new EmployeeController())->getEmployeeCurrentActivity();
// $departmentLists = (new DepartmentController())->getDepartmentLists();

// $positions = (new PositionController())->getAllPosition();

// foreach($positions as $position){
//     echo $position->position_name;
// }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Directory</title>
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
        /* Table styles */
        .table-header {
            background-color: #e5e7eb; /* Light gray for table header */
        }
        /* Add border to each table row */
        tbody tr {
            border-bottom: 1px solid #e5e7eb;
        }
        tbody tr:last-child {
            border-bottom: none;
        }
        /* Prevent body horizontal scroll */
        body {
            overflow-x: hidden;
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

        <?=Sidebar("Residents", null)?>

        <!-- 2. MAIN CONTENT AREA -->
        <main class="flex-1 md:ml-64 p-6 transition-all duration-300">

            <!-- Top Header Bar -->
            <header class="mb-6">
                <div class="flex justify-between items-center mb-1">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Resident Directory</h1>
                        <p class="text-gray-500 text-sm"><?= getGreeting($userName) ?> - Read-only list from profiling-system.</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <p class="text-sm text-gray-500" id="current-date">September 28, 2025</p>
                    </div>
                </div>
                <?php Breadcrumb([
                    ['label' => 'Dashboard', 'link' => 'dashboard.php'],
                    ['label' => 'Residents', 'link' => 'residents.php']
                ]); ?>
            </header>

            <!-- RESIDENT MANAGEMENT SECTION -->
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                <?php if (!empty($_GET['error'])): ?>
                    <div class="mb-4 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                        <?= htmlspecialchars($_GET['error']) ?>
                    </div>
                <?php endif; ?>
                
                <!-- Controls: Search, Filter, and Add Button -->
                <div class="flex flex-col sm:flex-row justify-between items-center mb-6 space-y-4 sm:space-y-0">
                    
                    <!-- Search Input, Search Button, and Filter -->
                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                        <form method="GET" action="" class="relative flex-1 sm:w-96 lg:w-[500px] flex items-center gap-2" id="searchForm">
                            <div class="relative flex-1">
                                <input type="text" 
                                    name="search" 
                                    id="searchInput"
                                    placeholder="Search resident name or ID..." 
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
                            <?php if (isset($_GET['is_pwd']) && !empty($_GET['is_pwd'])): ?>
                                <input type="hidden" name="is_pwd" value="<?= htmlspecialchars($_GET['is_pwd']) ?>">
                            <?php endif; ?>
                            <?php if (isset($_GET['is_deceased']) && !empty($_GET['is_deceased'])): ?>
                                <input type="hidden" name="is_deceased" value="<?= htmlspecialchars($_GET['is_deceased']) ?>">
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
                </div>

                <!-- Resident Table -->
                <div class="table-container rounded-lg border border-gray-200">
                    <div class="block w-full align-middle">
                        <table class="w-full divide-y divide-gray-200">
                        <thead class="table-header">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">First Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Middle Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Last Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Age</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Address</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($residents)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    <p class="text-sm">No residents found.</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($residents as $resident): ?>
                                <!-- Main Resident Row -->
                                <tr class="bg-white hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($resident['first_name'] ?? '') ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($resident['middle_name'] ?? '') ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($resident['last_name'] ?? '') ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        <?php 
                                            if (!empty($resident['birthdate'])) {
                                                $dob = new DateTime($resident['birthdate']);
                                                $today = new DateTime(date("Y-m-d"));
                                                echo $today->diff($dob)->y;
                                            } else {
                                                echo '-';
                                            }
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        <?= ucfirst($resident['barangay'] ?? '') ?><?= isset($resident['municipality_city']) ? ', ' . ucfirst($resident['municipality_city']) : '' ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            <?php if (!empty($resident['has_fingerprint'])): ?>
                                                <button
                                                    class="view inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
                                                    data-id="<?= htmlspecialchars($resident['resident_id'] ?? '') ?>"
                                                    title="View Details">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    View
                                                </button>
                                            <?php else: ?>
                                                <button
                                                    type="button"
                                                    onclick="window.location.href='biometrics://enroll?resident_id=<?= htmlspecialchars($resident['resident_id'] ?? '') ?>'"
                                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
                                                    title="Enroll Fingerprint">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4a6 6 0 00-6 6v3a2 2 0 01-2 2h0"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 10v3a6 6 0 01-6 6h-1"></path>
                                                    </svg>
                                                    Enroll
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Relatives Row -->
                                <!-- <?php if (!empty($resident['relatives'])): ?>
                                    <tr class="bg-gray-50">
                                        <td colspan="10" class="px-10 py-3 text-sm text-gray-700">
                                            <strong>Relatives:</strong>
                                            <ul class="list-disc ml-5">
                                                <?php foreach ($resident['relatives'] as $relative): ?>
                                                    <li>
                                                        <?= $relative['first_name'] ?> <?= $relative['last_name'] ?> 
                                                        (<?= ucfirst($relative['relationship_type']) ?>)
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </td>
                                    </tr>
                                <?php endif; ?> -->
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="mt-6 flex flex-col sm:flex-row justify-between items-center gap-4 text-sm text-gray-600">
                    <div>
                        Showing <span class="font-medium"><?= $pagination['startRecord'] ?></span> to <span class="font-medium"><?= $pagination['endRecord'] ?></span> of <span class="font-medium"><?= $pagination['totalRecords'] ?></span> records
                        <?php if (!empty($searchQuery)): ?>
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
                        if (!empty($filters['is_pwd'])) {
                            $queryParams[] = 'is_pwd=' . urlencode($filters['is_pwd']);
                        }
                        if (!empty($filters['is_deceased'])) {
                            $queryParams[] = 'is_deceased=' . urlencode($filters['is_deceased']);
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


            
                <!--Modal : Show Resident-->

                <div id="ShowResidentModal" class="fixed modal inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" id="showResidentModalBackdrop" aria-hidden="true"></div>

                    <div class="flex items-center justify-center min-h-screen p-4 sm:p-6">
                        <div class="bg-white rounded-xl shadow-2xl w-full max-w-7xl transition-all transform sm:my-8">

                            <div class="flex items-center justify-between p-5 border-b border-gray-200">
                                <h3 class="text-2xl font-bold text-gray-900" id="modal-title">
                                    <span class="text-blue-600">Information</span>
                                    <span class="text-gray-500 font-medium ml-2 text-base">of <span id="header_resident_name"></span></span>
                                </h3>
                                <button type="button" id="closeShowResidentModal" class="text-gray-400 hover:text-gray-600 focus:outline-none p-1 rounded-full hover:bg-gray-100 transition">
                                    <span class="sr-only">Close modal</span>
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>

                            <div class="p-6">
                                <!-- <p class="text-base text-gray-600 mb-6 border-b pb-4">
                                    Review the existing **Resident Details** on the left, then fill in the specific **Employee Registration** fields on the right.
                                </p> -->

                                <div class="grid grid-cols-1 gap-8">
                                    
                                    <div class="lg:col-span-2 space-y-6 p-4 border rounded-lg bg-gray-50">
                                        <h2 class="text-xl font-semibold text-gray-800 border-b pb-2">✅ Resident Profile Details</h2>
                                        
                                        <div class="flex flex-col sm:flex-row gap-6">
                                            <div class="flex-shrink-0">
                                                <img id="resident_photo" 
                                                    src="../utils/img/logo.png" 
                                                    alt="Resident profile picture" 
                                                    class="w-48 h-48 object-cover rounded-xl shadow-lg border-4 border-white ring-2 ring-blue-500"
                                                    onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'192\' height=\'192\'%3E%3Crect width=\'192\' height=\'192\' fill=\'%23e5e7eb\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' fill=\'%239ca3af\' font-size=\'14\'%3ENo Photo%3C/text%3E%3C/svg%3E'"
                                                />
                                                <div class="mt-2 text-center">
                                                    <span id="resident_status" class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800"></span>
                                                </div>
                                            </div>
                                            
                                            <div class="flex-grow grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                                                <div class="col-span-1">
                                                    <h3 class="text-lg font-bold text-gray-900 mb-2">Personal Information</h3>
                                                    <p class="text-sm text-gray-700"><span class="font-medium">Name:</span> <span id="name"></span></p> 
                                                    <p class="text-sm text-gray-700"><span class="font-medium">ID:</span> <span id="id"></span></p>
                                                    <p class="text-sm text-gray-700"><span class="font-medium">PhilSys No.:</span> <span id="philsys_no"></span></p>
                                                    <p class="text-sm text-gray-700"><span class="font-medium">Gender:</span> <span id="gender"></span></p>
                                                    <p class="text-sm text-gray-700"><span class="font-medium">Birth Date:</span> <span id="bod"></span></p>
                                                    <p class="text-sm text-gray-700"><span class="font-medium">Place Of Birth:</span> <span id="pod"></span></p>
                                                    <p class="text-sm text-gray-700"><span class="font-medium">Civil Status:</span> <span id="civil_status"></span></p>
                                                    <p class="text-sm text-gray-700"><span class="font-medium">Blood Type:</span> <span id="blood_type"></span></p>
                                                </div>

                                                <div class="col-span-1 space-y-4">
                                                    <div>
                                                        <h3 class="text-lg font-bold text-gray-900 mb-2">Contact & Occupation</h3>
                                                        <p class="text-sm text-gray-700"><span class="font-medium">Mobile:</span> <span id="contact"></span></p>
                                                        <p class="text-sm text-gray-700"><span class="font-medium">Job Title:</span> <span id="position"></span></p>
                                                        <p class="text-sm text-gray-700"><span class="font-medium">Employer:</span> <span id="employeer_name"></span></p>
                                                        <p class="text-sm text-gray-700"><span class="font-medium">Income Bracket:</span> <span id="income_bracket"></span></p>
                                                    </div>

                                                    <div>
                                                        <h3 class="text-lg font-bold text-gray-900 mb-2">Residence Status</h3>
                                                        <p class="text-sm text-gray-700"><span class="font-medium">Owner of House:</span> <span id="ooh"></span></p>
                                                        <p class="text-sm text-gray-700"><span class="font-medium">Residency:</span> <span id="residency"></span></p>
                                                        <p class="text-sm text-gray-700"><span class="font-medium">Barangay:</span> <span id="barangay"></span></p>
                                                        <p class="text-sm text-gray-700"><span class="font-medium">Postal Code:</span> <span id="postal_code"></span></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-6 border-t pt-4">
                                            <details>
                                                <summary class="font-semibold text-gray-800 cursor-pointer hover:text-blue-600">▶ Show Secondary/Supporting Details</summary>
                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4 p-3 bg-white rounded-lg shadow-inner">
                                                    <div>
                                                        <h4 class="text-base font-bold text-gray-900">Resident ID's</h4>
                                                        <p class="text-sm text-gray-700"><span id="id_type"></span> <span id="id_number"></span></p>
                                                        <p class="text-sm text-gray-700">Issue Date: <span id="issue_date"></span></p>
                                                        <p class="text-sm text-gray-700">Expiry Date: <span id="expiry_date"></span></p>
                                                    </div>
                                                    <div>
                                                        <h4 class="text-base font-bold text-gray-900">Biometric</h4>
                                                        <p class="text-sm text-gray-700">Type: <span id="resident_biometrics"></span></p>
                                                        <p class="text-sm text-gray-700">Capture Date: 12/02/2013</p>
                                                        <p class="text-sm text-gray-700">Recapture Due: 12/02/2025</p>
                                                    </div>
                                                    <div>
                                                        <h4 class="text-base font-bold text-gray-900">Family</h4>
                                                        <p class="text-sm text-gray-700">Relative/s : <span id="relative_name"></span></p>
                                                        </div>
                                                </div>
                                            </details>
                                        </div>
                                    </div>

                                    <!-- <div class="lg:col-span-1 space-y-6 p-4 border-2 border-blue-200 rounded-lg bg-white shadow-lg">
                                        <h2 class="text-xl font-semibold text-blue-700 border-b pb-2">📝 New Employee Fields</h2>
                                        
                                        <form class="space-y-6">
                                            <div>
                                                <label for="employee_id" class="block text-sm font-semibold text-gray-700">Employee ID <span class="text-red-500">*</span></label>
                                                <input type="text" name="employee_id" id="employee_id" placeholder="Enter unique employee number" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 text-lg focus:ring-blue-500 focus:border-blue-500 transition">
                                            </div>

                                            <div>
                                                <label for="hired_date" class="block text-sm font-semibold text-gray-700">Date Hired <span class="text-red-500">*</span></label>
                                                <input type="date" name="hired_date" id="hired_date" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 text-lg focus:ring-blue-500 focus:border-blue-500 transition">
                                            </div>

                                            <div>
                                                <label for="department" class="block text-sm font-semibold text-gray-700">Department / Office <span class="text-red-500">*</span></label>
                                                <select id="department" name="department" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 text-lg focus:ring-blue-500 focus:border-blue-500 transition">
                                                    <option value="">-- Select Department --</option>
                                                    <option value="admin">Administration</option>
                                                    <option value="hr">Human Resources</option>
                                                    <option value="finance">Finance</option>
                                                    </select>
                                            </div>
                                        </form>
                                    </div> -->
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row justify-end p-5 space-y-3 sm:space-y-0 sm:space-x-4 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                                <button type="button" id="cancelShowResidentModal" class="w-full sm:w-auto px-6 py-3 text-base font-semibold text-gray-700 bg-white border border-gray-300 rounded-xl shadow-sm hover:bg-gray-100 transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Close
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- End modal -->

                <!-- Edit Employee modal removed (attendance-system is read-only for resident/employee master data) -->

                <!-- Filter Modal -->
                <div id="filterModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="filter-modal-title" role="dialog" aria-modal="true">
                    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" id="filterModalBackdrop" aria-hidden="true"></div>

                    <div class="flex items-center justify-center min-h-screen p-4 sm:p-6">
                        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md transition-all transform sm:my-8" id="filterModalContent">
                            <form method="GET" action="">
                                <div class="flex items-center justify-between p-5 border-b border-gray-200">
                                    <h3 class="text-xl font-semibold text-gray-900" id="filter-modal-title">
                                        Filter Residents
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

                                    <!-- PWD Filter -->
                                    <div>
                                        <label for="filter_is_pwd" class="block text-sm font-medium text-gray-700 mb-2">
                                            PWD
                                        </label>
                                        <select name="is_pwd" id="filter_is_pwd" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">All</option>
                                            <option value="Yes" <?= (isset($filters['is_pwd']) && $filters['is_pwd'] === 'Yes') ? 'selected' : '' ?>>Yes</option>
                                            <option value="No" <?= (isset($filters['is_pwd']) && $filters['is_pwd'] === 'No') ? 'selected' : '' ?>>No</option>
                                        </select>
                                    </div>

                                    <!-- Status Filter (is_deceased in profiling-system) -->
                                    <div>
                                        <label for="filter_is_deceased" class="block text-sm font-medium text-gray-700 mb-2">
                                            Status
                                        </label>
                                        <select name="is_deceased" id="filter_is_deceased" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">All</option>
                                            <option value="Yes" <?= (isset($filters['is_deceased']) && $filters['is_deceased'] === 'Yes') ? 'selected' : '' ?>>Yes</option>
                                            <option value="No" <?= (isset($filters['is_deceased']) && $filters['is_deceased'] === 'No') ? 'selected' : '' ?>>No</option>
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
    <script type="module" src="./js/residents/main.js"></script>
</body>
</html>
