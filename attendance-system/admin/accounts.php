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

// Get filters
$filters = [];
if (isset($_GET['role']) && !empty($_GET['role'])) {
    $filters['role'] = $_GET['role'];
}
if (isset($_GET['is_active']) && $_GET['is_active'] !== '') {
    $filters['is_active'] = $_GET['is_active'];
}

// Fetch data from controller
$accountController = new AccountController();
$data = $accountController->getPaginatedAccounts($currentPage, $perPage, $searchQuery, $filters);

$accounts = $data['accounts'];
$pagination = $data['pagination'];
$searchQuery = $data['searchQuery'];

$totalRecords = $pagination['totalRecords'];
$totalPages = $pagination['totalPages'];
$startRecord = $pagination['startRecord'];
$endRecord = $pagination['endRecord'];

// Get current user ID to prevent self-deletion/locking
$currentUserId = $currentUser ? ($currentUser['id'] ?? null) : null;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$sessionAuthSource = $_SESSION['auth_source'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Management</title>
    <!-- Load global css -->
    <link rel="stylesheet" href="../utils/styles/global.css">
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .btn-primary {
            background-color: #007bff;
            transition: background-color 0.2s;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
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
    </style>
</head>
<body>

    <!-- Main Container -->
    <div class="flex min-h-screen">

        <?=Sidebar("Accounts", null)?>

        <!-- MAIN CONTENT AREA -->
        <main class="flex-1 md:ml-64 p-6 transition-all duration-300">

            <!-- Top Header Bar -->
            <header class="mb-6">
                <div class="flex justify-between items-center mb-1">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Account Management</h1>
                        <p class="text-gray-500 text-sm"><?= getGreeting($userName) ?> - Manage all system accounts.</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <p class="text-sm text-gray-500" id="current-date"></p>
                    </div>
                </div>
                <?php Breadcrumb([
                    ['label' => 'Dashboard', 'link' => 'dashboard.php'],
                    ['label' => 'Accounts', 'link' => 'accounts.php']
                ]); ?>
            </header>

            <!-- ACCOUNT MANAGEMENT SECTION -->
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
                                    placeholder="Search username, email, name, or position..." 
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
                            <?php if (isset($_GET['role']) && !empty($_GET['role'])): ?>
                                <input type="hidden" name="role" value="<?= htmlspecialchars($_GET['role']) ?>">
                            <?php endif; ?>
                            <?php if (isset($_GET['is_active']) && $_GET['is_active'] !== ''): ?>
                                <input type="hidden" name="is_active" value="<?= htmlspecialchars($_GET['is_active']) ?>">
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

                    <!-- Add Account Button (non-JS fallback via href) -->
                    <a href="account-create.php" id="addAccountBtn" class="w-full sm:w-auto px-6 py-2 text-white font-semibold rounded-lg btn-primary shadow-md flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Add New Account
                    </a>
                </div>

                <!-- Account Table -->
                <div class="table-container rounded-lg border border-gray-200">
                    <div class="inline-block min-w-full align-middle">
                        <table class="min-w-full divide-y divide-gray-200" style="min-width: 880px; width: 100%;">
                        <thead class="table-header">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Username</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Email</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Full Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Role / Position</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-200" id="accountsTableBody">
                            <?php if (empty($accounts)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    <p class="text-sm">No accounts found.</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($accounts as $account): 
                                $account = is_object($account) ? (array) $account : $account;
                                $accountId = $account['id'] ?? null;
                                $username = $account['username'] ?? '';
                                $email = $account['email'] ?? '';
                                $fullName = $account['full_name'] ?? '';
                                $role = $account['role'] ?? 'staff';
                                $roleDisplay = $account['role_display'] ?? ($role === 'manager' ? 'Barangay Official' : ucfirst((string) $role));
                                $roleLabel = ($account['source'] ?? '') === 'profiling_barangay_official'
                                    ? (string) $roleDisplay
                                    : ($role === 'manager' ? 'Official' : ucfirst((string) $role));
                                $isActive = (int) ($account['is_active'] ?? 1);
                                $source = $account['source'] ?? '';
                                $numericId = (int) ($account['numeric_id'] ?? 0);
                                $actionsEditable = !empty($account['actions_editable']);
                                $isCurrentUser = false;
                                if ($currentUserId && $numericId === (int) $currentUserId) {
                                    if ($source === 'profiling_admin' && $sessionAuthSource === 'profiling_admin') {
                                        $isCurrentUser = true;
                                    }
                                    if ($source === 'profiling_barangay_official' && $sessionAuthSource === 'profiling_barangay_official') {
                                        $isCurrentUser = true;
                                    }
                                }
                            ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <?= htmlspecialchars($username) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <?= htmlspecialchars($email) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <?= htmlspecialchars($fullName ?: 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php 
                                        echo $role === 'administrator' ? 'bg-purple-100 text-purple-800' : 
                                            ($role === 'manager' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800');
                                        ?>">
                                        <?= htmlspecialchars($roleLabel) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $isActive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $isActive ? 'Active' : 'Locked' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($isCurrentUser): ?>
                                        <span class="text-sm text-gray-500">Your account</span>
                                    <?php elseif ($actionsEditable): ?>
                                    <div class="flex flex-wrap items-center justify-center gap-2">
                                        <button 
                                            class="editBtn inline-flex items-center px-3 py-1.5 text-xs font-medium text-green-700 bg-green-50 rounded-lg hover:bg-green-100 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1" 
                                            data-id="<?= htmlspecialchars((string) $accountId) ?>"
                                            data-username="<?= htmlspecialchars($username) ?>"
                                            data-email="<?= htmlspecialchars($email) ?>"
                                            data-full-name="<?= htmlspecialchars($fullName) ?>"
                                            data-role="<?= htmlspecialchars($account['role_display'] ?? $role) ?>"
                                            data-is-active="<?= $isActive ?>"
                                            title="Edit Account">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            Edit
                                        </button>
                                        <button 
                                            class="lockBtn inline-flex items-center px-3 py-1.5 text-xs font-medium <?= $isActive ? 'text-orange-700 bg-orange-50 hover:bg-orange-100' : 'text-blue-700 bg-blue-50 hover:bg-blue-100' ?> rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-1" 
                                            data-id="<?= htmlspecialchars((string) $accountId) ?>"
                                            data-is-active="<?= $isActive ?>"
                                            data-username="<?= htmlspecialchars($username) ?>"
                                            title="<?= $isActive ? 'Lock Account' : 'Unlock Account' ?>"
                                        >
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $isActive ? 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' : 'M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z' ?>"></path>
                                            </svg>
                                            <?= $isActive ? 'Lock' : 'Unlock' ?>
                                        </button>
                                        <button 
                                            class="deleteBtn inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 rounded-lg hover:bg-red-100 transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1" 
                                            data-id="<?= htmlspecialchars((string) $accountId) ?>"
                                            data-username="<?= htmlspecialchars($username) ?>"
                                            title="Delete Account"
                                        >
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                    <?php else: ?>
                                    <div class="flex items-center justify-center">
                                        <button
                                            type="button"
                                            class="officialPortalBtn inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 <?= $isActive ? 'text-amber-800 bg-amber-50 hover:bg-amber-100 focus:ring-amber-500' : 'text-indigo-800 bg-indigo-50 hover:bg-indigo-100 focus:ring-indigo-500' ?>"
                                            data-id="<?= htmlspecialchars((string) $accountId) ?>"
                                            data-username="<?= htmlspecialchars($username) ?>"
                                            data-allow="<?= $isActive ? '0' : '1' ?>"
                                            title="<?= $isActive ? 'Set portal status to Inactive (cannot use system)' : 'Set portal status to Active (can sign in)' ?>"
                                        >
                                            <?php if ($isActive): ?>
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                            Revoke system access
                                            <?php else: ?>
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            Allow system access
                                            <?php endif; ?>
                                        </button>
                                    </div>
                                    <?php endif; ?>
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
                        if (!empty($filters['role'])) {
                            $queryParams[] = 'role=' . urlencode($filters['role']);
                        }
                        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
                            $queryParams[] = 'is_active=' . $filters['is_active'];
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

            <!-- Add/Edit Account Modal -->
            <div id="accountModal" class="fixed modal inset-0 z-50 hidden overflow-y-auto" aria-labelledby="account-modal-title" role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity"></div>

                <div class="flex items-center justify-center min-h-screen p-4 sm:p-6">
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md transition-all transform sm:my-8">
                        <form id="accountForm">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-xl font-semibold text-gray-800" id="account-modal-title">
                                        Add New Account
                                    </h3>
                                    <button type="button" id="closeAccountModal" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                                
                                <input type="hidden" id="accountId" name="id" value="">
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
                                        <input type="text" id="username" name="username" required
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                        <input type="email" id="email" name="email" required
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    
                                    <div>
                                        <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                        <input type="text" id="full_name" name="full_name"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    
                                    <div>
                                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                                        <select id="role" name="role" required
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                            <option value="administrator">Administrator</option>
                                        </select>
                                    </div>
                                    
                                    <div id="passwordField">
                                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                                        <input type="password" id="password" name="password"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <p class="text-xs text-gray-500 mt-1" id="passwordHint">Leave blank to keep current password (edit mode)</p>
                                    </div>
                                    
                                    <div>
                                        <label class="flex items-center">
                                            <input type="checkbox" id="is_active" name="is_active" value="1" checked
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-700">Account Active</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-3 mt-6">
                                    <button type="button" 
                                        id="cancelAccountBtn"
                                        class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none">
                                        Cancel
                                    </button>
                                    <button type="submit" 
                                        id="submitAccountBtn"
                                        class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none transition-colors">
                                        Save Account
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div id="deleteAccountModal" class="fixed modal inset-0 z-50 hidden overflow-y-auto" aria-labelledby="delete-modal-title" role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity"></div>

                <div class="flex items-center justify-center min-h-screen p-4 sm:p-6">
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md transition-all transform sm:my-8">
                        <div class="p-6">
                            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-800 text-center mb-2" id="delete-modal-title">
                                Delete Account
                            </h3>
                            <p class="text-sm text-gray-600 text-center mb-6">
                                Are you sure you want to delete the account for <span class="font-semibold text-gray-900" id="delete-account-username"></span>?<br>
                                This action cannot be undone.
                            </p>
                            <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-3">
                                <button type="button" 
                                    id="cancelDeleteBtn"
                                    class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none">
                                    Cancel
                                </button>
                                <button type="button" 
                                    id="confirmDeleteBtn"
                                    class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none transition-colors">
                                    Delete Account
                                </button>
                            </div>
                        </div>
                    </div>
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
                                    Filter Accounts
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

                                <!-- Role Filter -->
                                <div>
                                    <label for="filter_role" class="block text-sm font-medium text-gray-700 mb-2">
                                        Role
                                    </label>
                                    <select name="role" id="filter_role" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">All Roles</option>
                                        <option value="administrator" <?= (isset($filters['role']) && $filters['role'] === 'administrator') ? 'selected' : '' ?>>System administrator</option>
                                        <option value="manager" <?= (isset($filters['role']) && $filters['role'] === 'manager') ? 'selected' : '' ?>>Barangay official (portal)</option>
                                    </select>
                                </div>

                                <!-- Status Filter -->
                                <div>
                                    <label for="filter_is_active" class="block text-sm font-medium text-gray-700 mb-2">
                                        Status
                                    </label>
                                    <select name="is_active" id="filter_is_active" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">All Status</option>
                                        <option value="1" <?= (isset($filters['is_active']) && $filters['is_active'] == 1) ? 'selected' : '' ?>>Active</option>
                                        <option value="0" <?= (isset($filters['is_active']) && $filters['is_active'] == 0) ? 'selected' : '' ?>>Locked</option>
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

        </main>
    </div>

    <!-- Modular JavaScript Entry Point -->
    <script type="module" src="./js/accounts/main.js"></script>
</body>
</html>
