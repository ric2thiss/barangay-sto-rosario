<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth/helpers.php';
requireAuth();

include_once __DIR__ . '/../shared/components/Sidebar.php';
include_once __DIR__ . '/../shared/components/Breadcrumb.php';

$currentUser = currentUser();
$userName = $currentUser ? ($currentUser['full_name'] ?? $currentUser['username']) : 'Guest';

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.config.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>">
    <title>Activities &amp; events</title>
    <link rel="stylesheet" href="../utils/styles/global.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { overflow-x: hidden; }
    </style>
</head>
<body>
<div class="flex min-h-screen">
    <?= Sidebar('Activities', null) ?>

    <main class="flex-1 md:ml-64 p-6 transition-all duration-300">
        <header class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">Activities &amp; events</h1>
            <p class="text-gray-500 text-sm"><?= getGreeting($userName) ?> — Create local activities or review LGUMS imports.</p>
            <?php Breadcrumb([
                ['label' => 'Dashboard', 'link' => 'dashboard.php'],
                ['label' => 'Activities', 'link' => 'activities.php'],
            ]); ?>
        </header>

        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Create local activity</h2>
            <p class="text-sm text-gray-600 mb-4">Duplicates are blocked when the same name and date already exist (local). LGUMS rows are imported automatically on the Attendance page and cannot be edited here.</p>
            <form id="form-create-activity" class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-3xl">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="act-name">Name</label>
                    <input id="act-name" name="name" required maxlength="255"
                           class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="act-date">Date</label>
                    <input type="date" id="act-date" name="activity_date" required
                           class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="act-desc">Description (optional)</label>
                    <textarea id="act-desc" name="description" rows="2"
                              class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Create</button>
                    <span id="create-activity-msg" class="ml-3 text-sm text-gray-600"></span>
                </div>
            </form>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
            <div class="flex flex-col md:flex-row md:items-end gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="filter-from">From</label>
                    <input type="date" id="filter-from" class="p-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="filter-to">To</label>
                    <input type="date" id="filter-to" class="p-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div class="flex-1 min-w-[12rem]">
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="filter-search">Search name</label>
                    <input type="text" id="filter-search" placeholder="Search…" class="w-full p-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <button type="button" id="btn-load-activities" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-lg hover:bg-gray-900">Load</button>
            </div>

            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">ID</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">Name</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">Date</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">Source</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">External ID</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                    </tr>
                    </thead>
                    <tbody id="activities-tbody" class="bg-white divide-y divide-gray-200 text-sm"></tbody>
                </table>
            </div>
            <p id="activities-summary" class="mt-3 text-sm text-gray-600"></p>
        </div>
    </main>
</div>

<script type="module" src="./js/activities/main.js"></script>
</body>
</html>
