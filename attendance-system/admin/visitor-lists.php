<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth/helpers.php';
requireAuth();

include_once __DIR__ . '/../shared/components/Sidebar.php';
include_once __DIR__ . '/../shared/components/Breadcrumb.php';

$currentUser = currentUser();
$userName = $currentUser ? ($currentUser['full_name'] ?? $currentUser['username']) : 'Guest';

$brandingName = 'Barangay';
try {
    $settingsController = new SettingsController();
    $settingsResult = $settingsController->getAll();
    if (!empty($settingsResult['success']) && !empty($settingsResult['settings']['app_name']['value'])) {
        $brandingName = (string) $settingsResult['settings']['app_name']['value'];
    }
} catch (Exception $e) {
    error_log('visitor-lists branding: ' . $e->getMessage());
}

$logoUrl = rtrim(BASE_URL, '/') . '/utils/img/logo.png';
$defaultDateTo = date('Y-m-d');
$defaultDateFrom = date('Y-m-01');
$isAdmin = hasRole('administrator');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>">
    <title>Visitor Reports</title>
    <link rel="stylesheet" href="../utils/styles/global.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <style>
        body { overflow-x: hidden; }
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen">
        <?= Sidebar('Visitor Reports', null) ?>

        <main class="flex-1 md:ml-64 p-6 transition-all duration-300">
            <header class="mb-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:justify-between lg:items-start mb-1">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Visitor Reports</h1>
                        <p class="text-gray-500 text-sm"><?= htmlspecialchars(getGreeting($userName), ENT_QUOTES, 'UTF-8') ?> — Filter, review, and export visitor log entries (read-only).</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <label class="text-sm text-gray-600 whitespace-nowrap" for="export-paper-size">Paper size (DOCX / PDF)</label>
                        <select id="export-paper-size" class="pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="a4" selected>A4</option>
                            <option value="letter">Letter</option>
                            <option value="legal">Legal</option>
                            <option value="a3">A3</option>
                        </select>
                    </div>
                </div>
                <?php Breadcrumb([
                    ['label' => 'Dashboard', 'link' => 'dashboard.php'],
                    ['label' => 'Visitor Reports', 'link' => 'visitor-lists.php'],
                ]); ?>
            </header>

            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 mb-6">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Filters</h2>
                <div class="flex flex-col gap-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                        <div>
                            <label for="filter-date-from" class="block text-sm font-medium text-gray-700 mb-1">From <span class="text-red-500">*</span></label>
                            <input type="date" id="filter-date-from" required value="<?= htmlspecialchars($defaultDateFrom, ENT_QUOTES, 'UTF-8') ?>"
                                   class="block w-full pl-3 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="filter-date-to" class="block text-sm font-medium text-gray-700 mb-1">To <span class="text-red-500">*</span></label>
                            <input type="date" id="filter-date-to" required value="<?= htmlspecialchars($defaultDateTo, ENT_QUOTES, 'UTF-8') ?>"
                                   class="block w-full pl-3 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="lg:col-span-2">
                            <label for="filter-search" class="block text-sm font-medium text-gray-700 mb-1">Search (name or keyword)</label>
                            <input type="search" id="filter-search" placeholder="Name, purpose, address…"
                                   class="block w-full pl-3 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                        <div>
                            <label for="filter-purpose" class="block text-sm font-medium text-gray-700 mb-1">Purpose</label>
                            <select id="filter-purpose" class="block w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All</option>
                            </select>
                        </div>
                        <div>
                            <label for="filter-gender" class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                            <select id="filter-gender" class="block w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All</option>
                            </select>
                        </div>
                        <div>
                            <label for="filter-purok" class="block text-sm font-medium text-gray-700 mb-1">Purok</label>
                            <select id="filter-purok" class="block w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All</option>
                            </select>
                        </div>
                        <div>
                            <label for="filter-sort-dir" class="block text-sm font-medium text-gray-700 mb-1">Sort by visit date</label>
                            <select id="filter-sort-dir" class="block w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <option value="DESC" selected>Newest first</option>
                                <option value="ASC">Oldest first</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" id="btn-apply-filter" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-sm transition-colors text-sm">
                            Apply filters
                        </button>
                        <button type="button" id="btn-load-more" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium py-2 px-4 rounded-lg text-sm hidden">
                            Load more
                        </button>
                    </div>
                </div>
            </div>

            <div id="summary-strip" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white p-4 rounded-xl shadow border border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase">Total records</p>
                    <p id="summary-total" class="text-2xl font-semibold text-gray-900 mt-1">—</p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow border border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase">Unique visitors</p>
                    <p id="summary-unique" class="text-2xl font-semibold text-gray-900 mt-1">—</p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow border border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase">Selected date range</p>
                    <p id="summary-range" class="text-lg font-medium text-gray-800 mt-1">—</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">Visitor records</h2>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" id="btn-print" class="bg-gray-700 hover:bg-gray-800 text-white font-medium py-2 px-3 rounded-lg text-sm transition-colors">
                            Print report
                        </button>
                        <button type="button" id="btn-export-csv" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2 px-3 rounded-lg text-sm transition-colors">
                            Export as CSV
                        </button>
                        <button type="button" id="btn-export-pdf" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-3 rounded-lg text-sm transition-colors">
                            Export as PDF
                        </button>
                        <button type="button" id="btn-export-docx" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-3 rounded-lg text-sm transition-colors">
                            Export as DOCX
                        </button>
                    </div>
                </div>
                <p id="table-summary" class="text-sm text-gray-500 mb-3"></p>
                <div class="table-container">
                    <table id="visitor-logs-table" class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Name</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Gender</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Age / Birthdate</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Purok / Address</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Purpose</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Time in</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Date</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="visitor-logs-tbody" class="divide-y divide-gray-200 bg-white">
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500">Loading…</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- View details modal -->
    <div id="detail-modal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
        <div class="absolute inset-0 bg-black/40" id="detail-modal-backdrop"></div>
        <div class="absolute inset-4 md:inset-auto md:left-1/2 md:top-1/2 md:-translate-x-1/2 md:-translate-y-1/2 md:max-w-lg md:w-full bg-white rounded-xl shadow-xl border border-gray-200 p-6 overflow-y-auto max-h-[90vh]">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Visitor details</h3>
                <button type="button" id="detail-modal-close" class="text-gray-500 hover:text-gray-800 text-xl leading-none">&times;</button>
            </div>
            <dl id="detail-modal-body" class="space-y-2 text-sm text-gray-700"></dl>
        </div>
    </div>

    <script>
        window.BASE_URL = <?= json_encode(BASE_URL, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.VISITOR_REPORTS_CONFIG = <?= json_encode([
            'barangayName' => $brandingName,
            'logoUrl' => $logoUrl,
            'reportTitle' => 'Visitor Report',
            'isAdmin' => $isAdmin,
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script type="module" src="./js/visitor-lists/main.js"></script>
</body>
</html>
