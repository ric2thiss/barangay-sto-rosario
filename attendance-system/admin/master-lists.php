<?php
require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/../auth/helpers.php";
requireAuth();

include_once '../shared/components/Sidebar.php';
include_once '../shared/components/Breadcrumb.php';
include_once '../shared/components/HelpPopover.php';

// Get current user for greeting
$currentUser = currentUser();
$userName = $currentUser ? ($currentUser['full_name'] ?? $currentUser['username']) : 'Guest';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Lists</title>
    <link rel="stylesheet" href="../utils/styles/global.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .btn-primary {
            background-color: #007bff;
            transition: background-color 0.2s;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .table-header {
            background-color: #e5e7eb;
        }
        tbody tr {
            border-bottom: 1px solid #e5e7eb;
        }
        tbody tr:last-child {
            border-bottom: none;
        }
        .tab-button {
            border-bottom-width: 2px;
        }
        .tab-button.active {
            border-bottom-color: #3b82f6;
            color: #2563eb;
        }
        .tab-button:not(.active) {
            border-bottom-color: transparent;
            color: #6b7280;
        }
        .tab-button:not(.active):hover {
            color: #374151;
            border-bottom-color: #d1d5db;
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
        /* Prevent body horizontal scroll */
        body {
            overflow-x: hidden;
        }
        /* Ensure main content doesn't overflow */
        main {
            overflow-x: hidden;
            max-width: 100%;
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen">
        <?=Sidebar("Master Lists", null, "../utils/img/logo.png")?>

        <main class="flex-1 md:ml-64 p-6 transition-all duration-300">
            <header class="mb-6">
                <div class="flex justify-between items-center mb-1">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Master Lists</h1>
                        <p class="text-gray-500 text-sm"><?= getGreeting($userName) ?> - Manage attendance windows.</p>
                    </div>
                </div>
                <?php Breadcrumb([
                    ['label' => 'Dashboard', 'link' => 'dashboard.php'],
                    ['label' => 'Master Lists', 'link' => 'master-lists.php']
                ]); ?>
            </header>

            <!-- Success/Error Messages -->
            <div id="messageContainer" class="mb-4 hidden">
                <div id="successMessage" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4 hidden">
                    <p class="font-medium" id="successText"></p>
                </div>
                <div id="errorMessage" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 hidden">
                    <p class="font-medium" id="errorText"></p>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                <!-- Tab Navigation -->
                <div class="border-b border-gray-200 mb-6">
                    <nav class="flex space-x-8" aria-label="Tabs">
                        <button type="button" class="tab-button active whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors" data-tab="attendance-windows" id="tab-attendance-windows">
                            <span class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Attendance Windows
                            </span>
                        </button>
                        <button type="button" class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors border-transparent text-gray-500" data-tab="late-thresholds" id="tab-late-thresholds">
                            <span class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Late thresholds
                            </span>
                        </button>
                        <button type="button" class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors border-transparent text-gray-500" data-tab="non-resident-services" id="tab-non-resident-services">
                            <span class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                Non-Resident Services
                            </span>
                        </button>
                    </nav>
                </div>

                <!-- Tab Content: Attendance Windows -->
                <div class="tab-content" id="content-attendance-windows">
                    <div class="flex justify-between items-center mb-4 flex-wrap gap-3">
                        <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2 flex-wrap">
                            Attendance Windows
                            <?= help_popover(
                                'Attendance windows',
                                'Master list of attendance windows (label, start time, end time). These labels are used when recording logs and when evaluating late, undertime, and complete-day rules in analytics.',
                                'ml-windows'
                            ) ?>
                        </h2>
                        <button onclick="openModal('attendance-windows', null)" class="px-6 py-2 text-white font-semibold rounded-lg btn-primary shadow-md flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Add Window
                        </button>
                    </div>
                    <div class="table-container rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="table-header">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Label</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Start Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">End Time</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="attendance-windows-table" class="bg-white divide-y divide-gray-200">
                                <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab Content: Per-window late grace (clock-in threshold after official start) -->
                <div class="tab-content hidden" id="content-late-thresholds">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2 flex-wrap">
                            Late thresholds per window
                            <?= help_popover(
                                'Late thresholds per window',
                                'For each clock-in window (labels ending in _in), set how many minutes after the window start time still count as on time. Example: start 8:00 AM with 30 minutes here allows check-in until 8:30 AM; 8:31 AM is late. Leave empty to use the global default from Settings (attendance_late_grace_minutes, usually 15).',
                                'ml-late'
                            ) ?>
                        </h2>
                    </div>
                    <div class="table-container rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="table-header">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Label</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Start time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Extra minutes (grace)</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody id="late-thresholds-table" class="bg-white divide-y divide-gray-200">
                                <tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab Content: Non-Resident Allowed Services -->
                <div class="tab-content hidden" id="content-non-resident-services">
                    <div class="flex justify-between items-center mb-4 flex-wrap gap-3">
                        <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2 flex-wrap">
                            Non-Resident Allowed Services
                            <?= help_popover(
                                'Non-resident services',
                                'Manage services that are available for non-resident visitors (e.g., Blotter). Only these services will be shown in the logbook for recognized non-residents.',
                                'ml-non-resident'
                            ) ?>
                        </h2>
                        <button onclick="openModal('non-resident-services', null)" class="px-6 py-2 text-white font-semibold rounded-lg btn-primary shadow-md flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Add Service
                        </button>
                    </div>
                    <div class="table-container rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="table-header">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Service ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Service Name</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="non-resident-services-table" class="bg-white divide-y divide-gray-200">
                                <tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal -->
    <div id="modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900" id="modal-title">Add Item</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <form id="modal-form" onsubmit="handleSubmit(event)">
                    <input type="hidden" id="modal-id" name="id">
                    <input type="hidden" id="modal-type" name="type">
                    <div id="modal-form-content">
                        <!-- Default form content -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2" id="modal-label">Name</label>
                            <input type="text" id="modal-input" name="name" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Enter name">
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Delete</h3>
                <p class="text-gray-600 mb-4" id="delete-message">Are you sure you want to delete this item?</p>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeDeleteModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50">
                        Cancel
                    </button>
                    <button onclick="confirmDelete()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const API_BASE = '../api/master-lists/';
        let currentTab = 'attendance-windows';
        let deleteItem = { type: null, id: null };
        /** @type {Record<string, { label: string, start_time: string, end_time: string }>} */
        let lateThresholdCache = {};

        const tabConfig = {
            'attendance-windows': {
                endpoint: 'attendance-windows.php',
                nameField: 'label',
                label: 'Window Label',
                title: 'Attendance Window',
                isSimple: false
            },
            'late-thresholds': {
                endpoint: 'attendance-windows.php',
                nameField: 'label',
                label: 'Window Label',
                title: 'Late threshold',
                isSimple: false
            },
            'non-resident-services': {
                endpoint: 'non-resident-services.php',
                nameField: 'service_name',
                label: 'Service Name',
                title: 'Non-Resident Service',
                isSimple: false
            }
        };

        // Tab Navigation
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                const tab = button.getAttribute('data-tab');
                showTab(tab);
            });
        });

        function showTab(tab) {
            currentTab = tab;
            document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active', 'border-blue-500', 'text-blue-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });

            document.getElementById(`content-${tab}`).classList.remove('hidden');
            document.getElementById(`tab-${tab}`).classList.add('active', 'border-blue-500', 'text-blue-600');
            document.getElementById(`tab-${tab}`).classList.remove('border-transparent', 'text-gray-500');

            loadData(tab);
        }

        // Load data
        async function loadData(type) {
            const config = tabConfig[type];
            const tableBody = document.getElementById(`${type === 'late-thresholds' ? 'late-thresholds' : type}-table`);
            
            try {
                const response = await fetch(`${API_BASE}${config.endpoint}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const items = Array.isArray(result.data) ? result.data : [result.data];
                    
                    if (items.length === 0) {
                        const colspan = type === 'attendance-windows' ? '5' : (type === 'late-thresholds' ? '4' : '3');
                        tableBody.innerHTML = `<tr><td colspan="${colspan}" class="px-6 py-4 text-center text-gray-500">No items found. Click "Add" to create one.</td></tr>`;
                        return;
                    }

                    if (type === 'late-thresholds') {
                        lateThresholdCache = {};
                        tableBody.innerHTML = items.map((item) => {
                            const id = isObject(item) ? (item.window_id ?? item.id) : (item.window_id ?? item.id);
                            const label = isObject(item) ? item.label : item.label;
                            const startTime = isObject(item) ? item.start_time : item.start_time;
                            const endTime = isObject(item) ? item.end_time : item.end_time;
                            const rawGrace = isObject(item) ? item.late_grace_minutes : item.late_grace_minutes;
                            const graceVal = rawGrace !== null && rawGrace !== undefined && rawGrace !== '' ? String(rawGrace) : '';
                            lateThresholdCache[String(id)] = {
                                label: String(label),
                                start_time: normalizeTimeForApi(startTime),
                                end_time: normalizeTimeForApi(endTime),
                            };
                            const isInWindow = /_in$/i.test(String(label).replace(/\s+/g, '_').replace(/-/g, '_'));
                            const hint = isInWindow
                                ? ''
                                : '<p class="text-xs text-amber-700 mt-1">This label is not an <code>_in</code> window; late threshold applies only to clock-in windows in analytics.</p>';
                            return `
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">${escapeHtml(String(label))}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">${escapeHtml(String(startTime || ''))}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <input type="number" id="late-grace-${id}" min="0" max="720" step="1" placeholder="Global default"
                                            value="${graceVal ? escapeHtml(graceVal) : ''}"
                                            class="w-32 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                                        <span class="text-xs text-gray-500 block mt-1">0–720 min after start, or empty = global</span>
                                        ${hint}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button type="button" onclick="saveLateThreshold(${id})"
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                                            Save
                                        </button>
                                    </td>
                                </tr>
                            `;
                        }).join('');
                        return;
                    }

                    if (type === 'attendance-windows') {
                        // Special handling for attendance windows table
                        tableBody.innerHTML = items.map(item => {
                            const id = isObject(item) ? item.window_id : item.id;
                            const label = isObject(item) ? item.label : item.label;
                            const startTime = isObject(item) ? item.start_time : item.start_time;
                            const endTime = isObject(item) ? item.end_time : item.end_time;
                            return `
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${id}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">${escapeHtml(label)}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${escapeHtml(startTime)}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${escapeHtml(endTime)}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            <button onclick="openModal('${type}', ${id})" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-green-700 bg-green-50 rounded-lg hover:bg-green-100 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1" title="Edit">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                                Edit
                                            </button>
                                            <button onclick="openDeleteModal('${type}', ${id}, '${escapeHtml(label)}')" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 rounded-lg hover:bg-red-100 transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1" title="Delete">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        }).join('');
                    }

                    if (type === 'non-resident-services') {
                        tableBody.innerHTML = items.map(item => {
                            const id = isObject(item) ? item.id : item.id;
                            const serviceId = isObject(item) ? item.service_id : item.service_id;
                            const serviceName = isObject(item) ? item.service_name : item.service_name;
                            const isAllowed = isObject(item) ? item.is_allowed : item.is_allowed;
                            
                            return `
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">${escapeHtml(serviceId)}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">${escapeHtml(serviceName)}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${isAllowed ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                            ${isAllowed ? 'Allowed' : 'Disabled'}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            <button onclick="openModal('${type}', ${id})" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-green-700 bg-green-50 rounded-lg hover:bg-green-100 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1" title="Edit">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                                Edit
                                            </button>
                                            <button onclick="openDeleteModal('${type}', ${id}, '${escapeHtml(serviceName)}')" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 rounded-lg hover:bg-red-100 transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1" title="Delete">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        }).join('');
                    }
                } else {
                    const colspan = type === 'late-thresholds' ? '4' : '5';
                    tableBody.innerHTML = `<tr><td colspan="${colspan}" class="px-6 py-4 text-center text-gray-500">Error loading data.</td></tr>`;
                }
            } catch (error) {
                console.error('Error loading data:', error);
                const colspan = type === 'late-thresholds' ? '4' : '5';
                tableBody.innerHTML = `<tr><td colspan="${colspan}" class="px-6 py-4 text-center text-red-500">Error loading data.</td></tr>`;
            }
        }

        /** HH:MM or HH:MM:SS → HH:MM:SS for API */
        function normalizeTimeForApi(t) {
            if (!t) return '00:00:00';
            const s = String(t).trim();
            if (s.length === 5 && s.indexOf(':') === 2) return s + ':00';
            return s;
        }

        async function saveLateThreshold(windowId) {
            const c = lateThresholdCache[String(windowId)];
            if (!c) {
                showMessage('Could not find window data. Reload the tab.', false);
                return;
            }
            const input = document.getElementById(`late-grace-${windowId}`);
            const raw = input ? input.value.trim() : '';
            let lateGrace = null;
            if (raw !== '') {
                const n = parseInt(raw, 10);
                if (Number.isNaN(n) || n < 0 || n > 720) {
                    showMessage('Enter a whole number from 0 to 720, or leave empty for global default.', false);
                    return;
                }
                lateGrace = n;
            }
            const url = `${API_BASE}attendance-windows.php`;
            const body = {
                id: windowId,
                label: c.label,
                start_time: c.start_time,
                end_time: c.end_time,
                late_grace_minutes: lateGrace,
            };
            try {
                const response = await fetch(url, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body),
                });
                const result = await response.json();
                if (result.success) {
                    showMessage(result.message || 'Saved.', true);
                    loadData('late-thresholds');
                } else {
                    showMessage(result.message || 'Save failed.', false);
                }
            } catch (e) {
                console.error(e);
                showMessage('An error occurred. Please try again.', false);
            }
        }

        function isObject(item) {
            return typeof item === 'object' && item !== null && !Array.isArray(item);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Modal functions
        function openModal(type, id) {
            const config = tabConfig[type];
            const modal = document.getElementById('modal');
            const title = document.getElementById('modal-title');
            const typeInput = document.getElementById('modal-type');
            const idInput = document.getElementById('modal-id');
            const formContent = document.getElementById('modal-form-content');

            typeInput.value = type;
            idInput.value = id || '';
            title.textContent = id ? `Edit ${config.title}` : `Add ${config.title}`;

            if (type === 'attendance-windows') {
                // Custom form for attendance windows
                formContent.innerHTML = `
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Window Label</label>
                        <input type="text" id="modal-label-input" name="label" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                            placeholder="e.g., morning_in">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Start Time</label>
                        <input type="time" id="modal-start-time" name="start_time" required step="1"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">End Time</label>
                        <input type="time" id="modal-end-time" name="end_time" required step="1"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                `;

                if (id) {
                    // Load existing data
                    fetch(`${API_BASE}${config.endpoint}?id=${id}`)
                        .then(res => res.json())
                        .then(result => {
                            if (result.success && result.data) {
                                const item = result.data;
                                const startTime = isObject(item) ? item.start_time : item.start_time;
                                const endTime = isObject(item) ? item.end_time : item.end_time;
                                
                                document.getElementById('modal-label-input').value = isObject(item) ? item.label : item.label;
                                document.getElementById('modal-start-time').value = startTime || '';
                                document.getElementById('modal-end-time').value = endTime || '';
                            }
                        });
                } else {
                    // Clear inputs for new window
                    document.getElementById('modal-label-input').value = '';
                    document.getElementById('modal-start-time').value = '';
                    document.getElementById('modal-end-time').value = '';
                }
            } else if (type === 'non-resident-services') {
                // Custom form for non-resident services
                formContent.innerHTML = `
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Service ID</label>
                        <input type="text" id="modal-service-id" name="service_id" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                            placeholder="e.g., BLOTTER">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Service Name</label>
                        <input type="text" id="modal-service-name" name="service_name" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                            placeholder="e.g., Barangay Blotter">
                    </div>
                    <div class="mb-4 flex items-center">
                        <input type="checkbox" id="modal-is-allowed" name="is_allowed" value="1" checked
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label class="ml-2 block text-sm text-gray-900">Allowed for non-residents</label>
                    </div>
                `;

                if (id) {
                    fetch(`${API_BASE}${config.endpoint}?id=${id}`)
                        .then(res => res.json())
                        .then(result => {
                            if (result.success && result.data) {
                                const item = result.data;
                                document.getElementById('modal-service-id').value = item.service_id || '';
                                document.getElementById('modal-service-name').value = item.service_name || '';
                                document.getElementById('modal-is-allowed').checked = String(item.is_allowed) === '1';
                            }
                        });
                }
            } else {
                // Default form for simple entities
                formContent.innerHTML = `
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2" id="modal-label">${config.label}</label>
                        <input type="text" id="modal-input" name="name" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Enter ${config.label.toLowerCase()}">
                    </div>
                `;

                const input = document.getElementById('modal-input');
                if (id) {
                    // Load existing data
                    fetch(`${API_BASE}${config.endpoint}?id=${id}`)
                        .then(res => res.json())
                        .then(result => {
                            if (result.success && result.data) {
                                const item = result.data;
                                input.value = isObject(item) ? item[config.nameField] : item.name;
                            }
                        });
                } else {
                    input.value = '';
                }
            }

            modal.classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('modal').classList.add('hidden');
        }

        async function handleSubmit(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            const type = formData.get('type');
            const id = formData.get('id');
            const config = tabConfig[type];
            
            let data;
            if (type === 'attendance-windows') {
                // Ensure time format is HH:MM:SS (HTML5 time inputs return HH:MM)
                let startTime = formData.get('start_time');
                let endTime = formData.get('end_time');
                if (startTime && !startTime.includes(':')) {
                    startTime = startTime + ':00';
                } else if (startTime && startTime.split(':').length === 2) {
                    startTime = startTime + ':00';
                }
                if (endTime && !endTime.includes(':')) {
                    endTime = endTime + ':00';
                } else if (endTime && endTime.split(':').length === 2) {
                    endTime = endTime + ':00';
                }
                data = {
                    label: formData.get('label'),
                    start_time: startTime,
                    end_time: endTime
                };
            } else if (type === 'non-resident-services') {
                data = {
                    service_id: formData.get('service_id'),
                    service_name: formData.get('service_name'),
                    is_allowed: formData.get('is_allowed') === '1' ? 1 : 0
                };
            } else {
                data = { [config.nameField]: formData.get('name') };
            }

            const url = `${API_BASE}${config.endpoint}`;
            const method = id ? 'PUT' : 'POST';
            const body = id ? JSON.stringify({ id, ...data }) : JSON.stringify(data);

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: body
                });

                const result = await response.json();
                if (result.success) {
                    showMessage(result.message, true);
                    closeModal();
                    loadData(type);
                } else {
                    showMessage(result.message, false);
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('An error occurred. Please try again.', false);
            }
        }

        // Delete modal functions
        function openDeleteModal(type, id, name) {
            deleteItem = { type, id };
            const config = tabConfig[type];
            document.getElementById('delete-message').textContent = `Are you sure you want to delete "${name}"?`;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            deleteItem = { type: null, id: null };
        }

        async function confirmDelete() {
            if (!deleteItem.type || !deleteItem.id) return;

            const config = tabConfig[deleteItem.type];
            const url = `${API_BASE}${config.endpoint}`;

            try {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: deleteItem.id })
                });

                const result = await response.json();
                if (result.success) {
                    showMessage(result.message, true);
                    closeDeleteModal();
                    loadData(deleteItem.type);
                } else {
                    showMessage(result.message, false);
                    closeDeleteModal();
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('An error occurred. Please try again.', false);
                closeDeleteModal();
            }
        }

        function showMessage(message, isSuccess) {
            const container = document.getElementById('messageContainer');
            const successDiv = document.getElementById('successMessage');
            const errorDiv = document.getElementById('errorMessage');
            const successText = document.getElementById('successText');
            const errorText = document.getElementById('errorText');

            container.classList.remove('hidden');
            if (isSuccess) {
                successText.textContent = message;
                successDiv.classList.remove('hidden');
                errorDiv.classList.add('hidden');
            } else {
                errorText.textContent = message;
                errorDiv.classList.remove('hidden');
                successDiv.classList.add('hidden');
            }

            setTimeout(() => {
                container.classList.add('hidden');
            }, 5000);
        }

        // Sidebar toggle (from existing pages)
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

        // Load initial data
        loadData('attendance-windows');
    </script>
    
    <!-- App Name Updater -->
    <script src="js/shared/appName.js"></script>
</body>
</html>
