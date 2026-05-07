<?php
require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/../auth/helpers.php";
requireAuth();

include_once '../shared/components/Sidebar.php';
include_once '../shared/components/Breadcrumb.php';

$currentUser = currentUser();
$userName = $currentUser ? ($currentUser['full_name'] ?? $currentUser['username']) : 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <link rel="stylesheet" href="../utils/styles/global.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .btn-primary { background-color: #007bff; transition: background-color 0.2s; }
        .btn-primary:hover { background-color: #0056b3; }
        body { overflow-x: hidden; }
        main { overflow-x: hidden; max-width: 100%; }
    </style>
</head>
<body>
    <div class="flex min-h-screen">
        <?=Sidebar("Accounts", null)?>

        <main class="flex-1 md:ml-64 p-6 transition-all duration-300">
            <header class="mb-6">
                <div class="flex justify-between items-center mb-1">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Create New Account</h1>
                        <p class="text-gray-500 text-sm"><?= getGreeting($userName) ?> - Add a new system user.</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <p class="text-sm text-gray-500" id="current-date"></p>
                    </div>
                </div>
                <?php Breadcrumb([
                    ['label' => 'Dashboard', 'link' => 'dashboard.php'],
                    ['label' => 'Accounts', 'link' => 'accounts.php'],
                    ['label' => 'Create', 'link' => 'account-create.php']
                ]); ?>
            </header>

            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 max-w-2xl">
                <form id="createAccountForm" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                    </div>

                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" id="full_name" name="full_name"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <input type="hidden" id="role" name="role" value="administrator">

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                        <input type="password" id="password" name="password" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Creates a system administrator in the profiling-system <span class="font-medium">admin</span> database table.</p>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" id="is_active" name="is_active" value="1" checked
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Account Active</span>
                        </label>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-end gap-3 pt-2">
                        <a href="accounts.php" class="w-full sm:w-auto px-6 py-2 text-center text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                            Cancel
                        </a>
                        <button type="submit" id="submitBtn" class="w-full sm:w-auto px-6 py-2 text-sm font-semibold text-white rounded-lg btn-primary transition">
                            Create Account
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script type="module" src="./js/accounts/create.js"></script>
</body>
</html>

