<?php
require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../auth/helpers.php";
requireAuth(); // Require authentication - redirects to login if not authenticated

// Employees are owned/managed by profiling-system (barangay_official). attendance-system is read-only.
header("Location: ../employees.php?error=" . urlencode("Employee editing is disabled. Manage employees in the profiling-system database."));
exit;

include_once '../../shared/components/Sidebar.php';
include_once '../../shared/components/Breadcrumb.php';

// Get current user for greeting
$currentUser = currentUser();
$userName = $currentUser ? ($currentUser['full_name'] ?? $currentUser['username']) : 'Guest';

// Get employee ID from query parameter
$employeeId = $_GET['id'] ?? null;

if (empty($employeeId)) {
    header("Location: ../employees.php?error=No employee ID provided");
    exit;
}

// Get employee data
$employeeController = new EmployeeController();
$employee = $employeeController->getEmployeeById($employeeId);

if (!$employee) {
    header("Location: ../employees.php?error=Employee not found");
    exit;
}

$positions = (new PositionController())->getAllPosition();
$departmentLists = (new DepartmentController())->getDepartmentLists();

$error = "";
$success = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = [
        'department_id' => !empty($_POST['department_id']) ? intval($_POST['department_id']) : null,
        'position_id' => !empty($_POST['position_id']) ? intval($_POST['position_id']) : null,
        'hired_date' => trim($_POST['hired_date'] ?? ''),
    ];
    
    $result = $employeeController->update($employeeId, $data);
    
    if ($result["success"]) {
        $success = $result["message"];
        // Refresh employee data
        $employee = $employeeController->getEmployeeById($employeeId);
        // Redirect after 2 seconds
        header("Refresh: 2; url=../employees.php");
    } else {
        $error = $result["error"] ?? "Failed to update employee.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee</title>
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
    </style>
</head>
<body>

    <!-- Main Container -->
    <div class="flex min-h-screen">

        <?=Sidebar("Employees", null, "../../utils/img/logo.png")?>

        <!-- 2. MAIN CONTENT AREA -->
        <main class="flex-1 md:ml-64 p-6 transition-all duration-300">

            <!-- Top Header Bar -->
            <header class="mb-6">
                <div class="flex justify-between items-center mb-1">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Edit Employee</h1>
                        <p class="text-gray-500 text-sm"><?= getGreeting($userName) ?> - Update the employee information below.</p>
                    </div>
                </div>
                <?php Breadcrumb([
                    ['label' => 'Dashboard', 'link' => '../dashboard.php'],
                    ['label' => 'Employees', 'link' => '../employees.php'],
                    ['label' => 'Edit Employee', 'link' => 'edit.php?id=' . htmlspecialchars($employeeId)]
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

            <!-- Edit Form -->
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                <form method="POST" action="" id="employeeForm" class="space-y-6">
                    
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Employee Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Employee ID (Read-only) -->
                        <div>
                            <label for="employee_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Employee ID
                            </label>
                            <input type="text" 
                                id="employee_id" 
                                value="<?= htmlspecialchars($employee['employee_id'] ?? '') ?>" 
                                disabled
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600 cursor-not-allowed">
                            <p class="mt-1 text-xs text-gray-500">Employee ID cannot be changed</p>
                        </div>

                        <!-- Employee Name (Read-only) -->
                        <div>
                            <label for="employee_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Employee Name
                            </label>
                            <input type="text" 
                                id="employee_name" 
                                value="<?= htmlspecialchars(trim(($employee['first_name'] ?? '') . ' ' . ($employee['middle_name'] ?? '') . ' ' . ($employee['last_name'] ?? '') . ' ' . ($employee['suffix'] ?? ''))) ?>" 
                                disabled
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600 cursor-not-allowed">
                            <p class="mt-1 text-xs text-gray-500">Name is linked to resident record</p>
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
                                    <option value="<?=$departmentList->department_id?>" <?= (isset($employee['department_id']) && $employee['department_id'] == $departmentList->department_id) ? 'selected' : '' ?>><?=$departmentList->department_name?></option>
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
                                    <option value="<?=$position->position_id?>" <?= (isset($employee['position_id']) && $employee['position_id'] == $position->position_id) ? 'selected' : '' ?>><?=$position->position_name?></option>
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
                                value="<?= htmlspecialchars($employee['hired_date'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-between items-center pt-4 border-t border-gray-200 mt-6">
                        <a href="../employees.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-md">
                            Update Employee
                        </button>
                    </div>
                </form>
            </div>

        </main>
    </div>

    <!-- JavaScript for Sidebar Toggle -->
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
    </script>
</body>
</html>
