<?php
require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../auth/helpers.php";
requireAuth(); // Require authentication - redirects to login if not authenticated

// Employees are owned/managed by profiling-system (barangay_official). attendance-system is read-only.
header("Location: ../employees.php?error=" . urlencode("Employee creation is disabled. Manage employees in the profiling-system database."));
exit;

include_once '../../shared/components/Sidebar.php';
include_once '../../shared/components/Breadcrumb.php';

// Get current user for greeting
$currentUser = currentUser();
$userName = $currentUser ? ($currentUser['full_name'] ?? $currentUser['username']) : 'Guest';

$positions = (new PositionController())->getAllPosition();
$departmentLists = (new DepartmentController())->getDepartmentLists();
$lastEmployeeId = (new EmployeeController())->getLastEmployeeId();
$residents = (new ResidentController())->getAllResidentNotEmployee();

$error = "";
$success = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $employeeController = new EmployeeController();
    
    $data = [
        'employee_id' => trim($_POST['employee_id'] ?? ''),
        'resident_id' => !empty($_POST['resident_id']) ? intval($_POST['resident_id']) : null,
        'department_id' => !empty($_POST['department_id']) ? intval($_POST['department_id']) : null,
        'position_id' => !empty($_POST['position_id']) ? intval($_POST['position_id']) : null,
        'hired_date' => trim($_POST['hired_date'] ?? ''),
    ];
    
    $result = $employeeController->store($data);
    
    if ($result["success"]) {
        $success = $result["message"];
        // Redirect after 2 seconds
        header("Refresh: 2; url=../employees.php");
    } else {
        $error = $result["error"] ?? "Failed to create employee.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register New Employee</title>
    <!-- Load global css -->
    <link rel="stylesheet" href="../../utils/styles/global.css">
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
        /* Resident search dropdown */
        .resident-search-container {
            position: relative;
        }
        .resident-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            max-height: 300px;
            overflow-y: auto;
            z-index: 50;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            display: none;
        }
        .resident-results.show {
            display: block;
        }
        .resident-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            transition: background-color 0.2s;
        }
        .resident-item:hover {
            background-color: #f9fafb;
        }
        .resident-item:last-child {
            border-bottom: none;
        }
        .resident-item.selected {
            background-color: #dbeafe;
        }
        .resident-item.empty {
            padding: 1rem;
            text-align: center;
            color: #6b7280;
            cursor: default;
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
                        <h1 class="text-2xl font-semibold text-gray-800">Register New Employee</h1>
                        <p class="text-gray-500 text-sm"><?= getGreeting($userName) ?> - Fill out the form below to register a new employee.</p>
                    </div>
                </div>
                <?php Breadcrumb([
                    ['label' => 'Dashboard', 'link' => '../dashboard.php'],
                    ['label' => 'Employees', 'link' => '../employees.php'],
                    ['label' => 'Register New Employee', 'link' => 'create.php']
                ]); ?>
            </header>

            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">
                    <p class="font-medium"><?= htmlspecialchars($success) ?></p>
                    <p class="text-sm mt-1">Redirecting to employees list...</p>
                </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                    <p class="font-medium"><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                <form method="POST" action="" id="employeeForm" class="space-y-6">
                    
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Employee Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Employee ID -->
                        <div>
                            <label for="employee_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Employee ID <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="employee_id" id="employee_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                placeholder="<?= $lastEmployeeId ? 'Last ID: ' . htmlspecialchars($lastEmployeeId) : 'Enter employee ID' ?>"
                                value="<?= htmlspecialchars($_POST['employee_id'] ?? '') ?>">
                            <?php if ($lastEmployeeId): ?>
                                <p class="mt-1 text-xs text-gray-500">Last created employee ID: <span class="font-semibold text-gray-700"><?= htmlspecialchars($lastEmployeeId) ?></span></p>
                            <?php endif; ?>
                        </div>

                        <!-- Resident Search -->
                        <div class="resident-search-container">
                            <label for="resident_search" class="block text-sm font-medium text-gray-700 mb-2">
                                Search and Select Resident <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                id="resident_search" 
                                autocomplete="off"
                                placeholder="Type to search residents..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <input type="hidden" name="resident_id" id="resident_id" required>
                            <div id="resident_results" class="resident-results"></div>
                            <p class="mt-1 text-xs text-gray-500" id="selected_resident_display"></p>
                        </div>

                        <!-- Department -->
                        <div>
                            <label for="department_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Department
                            </label>
                            <select name="department_id" id="department_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Department (Optional)</option>
                                <?php foreach($departmentLists as $departmentList):?>
                                    <option value="<?=$departmentList->department_id?>" <?= (isset($_POST['department_id']) && $_POST['department_id'] == $departmentList->department_id) ? 'selected' : '' ?>><?=$departmentList->department_name?></option>
                                <?php endforeach ?>
                            </select>
                        </div>

                        <!-- Position -->
                        <div>
                            <label for="position_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Position <span class="text-red-500">*</span>
                            </label>
                            <select name="position_id" id="position_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Position</option>
                                <?php foreach($positions as $position):?>
                                    <option value="<?=$position->position_id?>" <?= (isset($_POST['position_id']) && $_POST['position_id'] == $position->position_id) ? 'selected' : '' ?>><?=$position->position_name?></option>
                                <?php endforeach ?>
                            </select>
                        </div>

                        <!-- Hired Date -->
                        <div>
                            <label for="hired_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Hired Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="hired_date" id="hired_date" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                value="<?= htmlspecialchars($_POST['hired_date'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-between items-center pt-4 border-t border-gray-200 mt-6">
                        <a href="../employees.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-md">
                            Register Employee
                        </button>
                    </div>
                </form>
            </div>

        </main>
    </div>

    <!-- JavaScript for Sidebar Toggle and Resident Search -->
    <script>
        // --- Mobile Sidebar Toggle Logic ---
        const sidebar = document.getElementById('sidebar');
        const toggleButton = document.getElementById('sidebar-toggle');
        const mainContent = document.querySelector('main');

        if (toggleButton) {
            toggleButton.addEventListener('click', () => {
                if (sidebar.classList.contains('-translate-x-full')) {
                    sidebar.classList.remove('-translate-x-full');
                    sidebar.classList.add('translate-x-0');
                    mainContent.classList.add('opacity-50', 'pointer-events-none');
                } else {
                    sidebar.classList.remove('translate-x-0');
                    sidebar.classList.add('-translate-x-full');
                    mainContent.classList.remove('opacity-50', 'pointer-events-none');
                }
            });
        }

        // Close sidebar if main content is clicked on mobile
        if (mainContent) {
            mainContent.addEventListener('click', () => {
                if (window.innerWidth < 768 && sidebar.classList.contains('translate-x-0')) {
                    sidebar.classList.remove('translate-x-0');
                    sidebar.classList.add('-translate-x-full');
                    mainContent.classList.remove('opacity-50', 'pointer-events-none');
                }
            });
        }

        // --- Resident Search Functionality ---
        const residentSearch = document.getElementById('resident_search');
        const residentResults = document.getElementById('resident_results');
        const residentIdInput = document.getElementById('resident_id');
        const selectedResidentDisplay = document.getElementById('selected_resident_display');
        let searchTimeout = null;
        let allResidents = [];
        let selectedResident = null;

        // Fetch all residents not yet employees from PHP
        function fetchResidents() {
            // Residents are passed from PHP
            allResidents = <?= json_encode(array_map(function($r) {
                return [
                    'resident_id' => $r->resident_id ?? $r['resident_id'] ?? null,
                    'first_name' => $r->first_name ?? $r['first_name'] ?? '',
                    'middle_name' => $r->middle_name ?? $r['middle_name'] ?? '',
                    'last_name' => $r->last_name ?? $r['last_name'] ?? ''
                ];
            }, $residents)) ?>;
        }

        // Search residents
        function searchResidents(query) {
            if (!query || query.trim().length < 2) {
                residentResults.classList.remove('show');
                return;
            }

            const searchTerm = query.toLowerCase().trim();
            const filtered = allResidents.filter(resident => {
                const fullName = `${resident.first_name || ''} ${resident.middle_name || ''} ${resident.last_name || ''}`.toLowerCase();
                const residentId = String(resident.resident_id || '').toLowerCase();
                return fullName.includes(searchTerm) || residentId.includes(searchTerm);
            });

            displayResults(filtered);
        }

        // Display search results
        function displayResults(residents) {
            residentResults.innerHTML = '';

            if (residents.length === 0) {
                residentResults.innerHTML = '<div class="resident-item empty">No residents found</div>';
                residentResults.classList.add('show');
                return;
            }

            residents.forEach(resident => {
                const item = document.createElement('div');
                item.className = 'resident-item';
                const fullName = `${resident.first_name || ''} ${resident.middle_name || ''} ${resident.last_name || ''}`.trim();
                item.innerHTML = `
                    <div class="font-medium text-gray-900">${fullName}</div>
                    <div class="text-sm text-gray-500">ID: ${resident.resident_id}</div>
                `;
                item.addEventListener('click', () => selectResident(resident));
                residentResults.appendChild(item);
            });

            residentResults.classList.add('show');
        }

        // Select a resident
        function selectResident(resident) {
            selectedResident = resident;
            const fullName = `${resident.first_name || ''} ${resident.middle_name || ''} ${resident.last_name || ''}`.trim();
            residentSearch.value = fullName;
            residentIdInput.value = resident.resident_id;
            selectedResidentDisplay.textContent = `Selected: ${fullName} (ID: ${resident.resident_id})`;
            selectedResidentDisplay.className = 'mt-1 text-xs text-green-600 font-medium';
            residentResults.classList.remove('show');
        }

        // Event listeners
        residentSearch.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchResidents(e.target.value);
            }, 300);
        });

        // Close results when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.resident-search-container')) {
                residentResults.classList.remove('show');
            }
        });

        // Clear selection when search is cleared
        residentSearch.addEventListener('focus', () => {
            if (selectedResident && residentSearch.value === `${selectedResident.first_name || ''} ${selectedResident.middle_name || ''} ${selectedResident.last_name || ''}`.trim()) {
                residentSearch.value = '';
            }
        });

        // Initialize
        fetchResidents();

        // Form validation
        const form = document.getElementById('employeeForm');
        form.addEventListener('submit', (e) => {
            if (!residentIdInput.value) {
                e.preventDefault();
                alert('Please select a resident from the search results.');
                residentSearch.focus();
                return false;
            }
        });
    </script>
</body>
</html>
