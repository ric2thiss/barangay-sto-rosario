<?php
require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/../auth/helpers.php";
requireAuth(); // Require authentication
requireRole(['administrator', 'admin', 'Administrator', 'Admin']); // Only administrators can access settings

include_once '../shared/components/Sidebar.php';
include_once '../shared/components/Breadcrumb.php';

// Get current user for greeting
$currentUser = currentUser();
$userName = $currentUser ? ($currentUser['full_name'] ?? $currentUser['username']) : 'Guest';

$timezoneIdentifiers = DateTimeZone::listIdentifiers();
natcasesort($timezoneIdentifiers);
$timezoneIdentifiers = array_values($timezoneIdentifiers);

$systemDbName = '—';
$systemMysqlVer = '—';
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $systemDbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
        $systemMysqlVer = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
    } catch (Throwable $e) {
        // leave placeholders
    }
}
$systemPhpVer = PHP_VERSION;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings</title>
    <link rel="stylesheet" href="../utils/styles/global.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            overflow-x: hidden;
        }
        /* Tab nav: underline only (no bullet/radio-style markers) */
        .settings-tab {
            border-bottom: 2px solid transparent;
            margin-bottom: -1px; /* sit flush on the nav container bottom rule */
            transition: color 0.15s ease, border-bottom-color 0.15s ease;
        }
        .settings-tab:hover:not(.settings-tab-active) {
            border-bottom-color: #d1d5db;
        }
        .settings-tab-active {
            color: #111827;
            font-weight: 600;
            border-bottom-color: #2563eb;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #3b82f6;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        input:disabled + .slider {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body>

    <!-- Main Container -->
    <div class="flex min-h-screen">

        <?=Sidebar("Settings", null)?>

        <!-- MAIN CONTENT AREA -->
        <main class="flex-1 md:ml-64 p-6 transition-all duration-300">

            <!-- Top Header Bar -->
            <header class="mb-6">
                <div class="flex justify-between items-center mb-1">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">System Settings</h1>
                        <p class="text-gray-500 text-sm"><?= getGreeting($userName) ?></p>
                    </div>
                </div>
                <?php Breadcrumb([
                    ['label' => 'Dashboard', 'link' => 'dashboard.php'],
                    ['label' => 'Settings', 'link' => 'settings.php']
                ]); ?>
            </header>

            <!-- Settings Navigation Tabs -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex flex-wrap gap-x-8 gap-y-1" id="settings-tabs" aria-label="Settings sections">
                    <a href="#" onclick="showTab('general'); return false;" class="settings-tab whitespace-nowrap py-3 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 settings-tab-active" data-tab="general">
                        General
                    </a>
                    <a href="#" onclick="showTab('attendance-logs'); return false;" class="settings-tab whitespace-nowrap py-3 px-1 text-sm font-medium text-gray-500 hover:text-gray-700" data-tab="attendance-logs">
                        Attendance Logs
                    </a>
                    <a href="#" onclick="showTab('visitor-logs'); return false;" class="settings-tab whitespace-nowrap py-3 px-1 text-sm font-medium text-gray-500 hover:text-gray-700" data-tab="visitor-logs">
                        Visitor Logs
                    </a>
                    <a href="#" onclick="showTab('system-logs'); return false;" class="settings-tab whitespace-nowrap py-3 px-1 text-sm font-medium text-gray-500 hover:text-gray-700" data-tab="system-logs">
                        System Logs
                    </a>
                    <a href="#" onclick="showTab('maintenance'); return false;" class="settings-tab whitespace-nowrap py-3 px-1 text-sm font-medium text-gray-500 hover:text-gray-700" data-tab="maintenance">
                        Maintenance Mode
                    </a>
                    <a href="#" onclick="showTab('security'); return false;" class="settings-tab whitespace-nowrap py-3 px-1 text-sm font-medium text-gray-500 hover:text-gray-700" data-tab="security">
                        Security
                    </a>
                </nav>
            </div>

            <!-- Success/Error Messages -->
            <div id="message-container" class="mb-4 hidden"></div>

            <!-- SETTINGS CONTENT -->
            <div id="settings-content">

                <!-- 1. General Settings Tab (Default Active) -->
                <div id="general" class="settings-tab-content bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                    <h2 class="text-xl font-semibold text-gray-800 mb-5 border-b pb-3">General Configuration</h2>
                    
                    <div class="space-y-6">
                        <!-- Application Name -->
                        <div>
                            <label for="app_name" class="block text-sm font-medium text-gray-700 mb-1">Application Name</label>
                            <input type="text" id="app_name" class="mt-1 block w-full md:w-1/2 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2">
                            <p class="mt-1 text-xs text-gray-500">The name displayed throughout the application</p>
                        </div>

                        <!-- Timezone Selector (PHP timezone identifiers) -->
                        <div>
                            <label for="timezone" class="block text-sm font-medium text-gray-700 mb-1">Default Time Zone</label>
                            <select id="timezone" class="mt-1 block w-full md:w-1/2 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-lg border max-w-full">
                                <?php foreach ($timezoneIdentifiers as $tzId): ?>
                                    <option value="<?= htmlspecialchars($tzId) ?>" <?= $tzId === 'Asia/Manila' ? 'selected' : '' ?>><?= htmlspecialchars($tzId) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Applied system-wide after save (PHP default timezone).</p>
                        </div>
                    </div>
                    
                    <div class="mt-8 pt-4 border-t">
                        <button onclick="saveGeneralSettings()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-md transition-colors">
                            Save General Settings
                        </button>
                    </div>
                </div>

                <!-- Attendance logs -->
                <div id="attendance-logs" class="settings-tab-content bg-white p-6 rounded-xl shadow-lg border border-gray-100 hidden">
                    <h2 class="text-xl font-semibold text-gray-800 mb-5 border-b pb-3">Attendance Logs</h2>
                    <p class="text-sm text-gray-600 mb-4">Export active (non-deleted) logs. Deletion requires your password and marks rows for removal; they are permanently purged after 30 days (automatic on each request, no scheduler).</p>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="space-y-3">
                            <h3 class="font-medium text-gray-800">Export date range (optional)</h3>
                            <p class="text-xs text-gray-500">Only applies to export downloads below.</p>
                            <div class="flex gap-2 flex-wrap items-center">
                                <input type="date" id="att_export_from" class="border rounded-lg p-2 text-sm" aria-label="Export from date">
                                <span class="text-gray-400 text-sm">to</span>
                                <input type="date" id="att_export_to" class="border rounded-lg p-2 text-sm" aria-label="Export to date">
                            </div>
                            <h3 class="font-medium text-gray-800 pt-2">Export</h3>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" data-export-att="sql" class="bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded text-sm">SQL</button>
                                <button type="button" data-export-att="pdf" class="bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded text-sm">PDF</button>
                                <button type="button" data-export-att="docx" class="bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded text-sm">DOCX</button>
                                <button type="button" data-export-att="xlsx" class="bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded text-sm">Excel</button>
                                <button type="button" data-export-att="zip" class="bg-blue-100 hover:bg-blue-200 px-3 py-1.5 rounded text-sm font-medium">ZIP (all types)</button>
                            </div>
                        </div>
                        <div class="space-y-3 border border-red-100 bg-red-50/40 rounded-lg p-4">
                            <h3 class="font-medium text-red-800">Delete logs (soft-delete)</h3>
                            <p class="text-xs text-gray-600">Requires username and password. Matching rows get <code>deleted_at</code> set; hidden from reports until purged after 30 days.</p>
                            <h4 class="text-sm font-medium text-gray-800 pt-1">Delete date range (optional)</h4>
                            <p class="text-xs text-gray-500">Separate from export. Leave empty to include all active logs.</p>
                            <div class="flex gap-2 flex-wrap items-center">
                                <input type="date" id="att_del_from" class="border rounded-lg p-2 text-sm bg-white" aria-label="Delete from date">
                                <span class="text-gray-400 text-sm">to</span>
                                <input type="date" id="att_del_to" class="border rounded-lg p-2 text-sm bg-white" aria-label="Delete to date">
                            </div>
                            <input type="text" id="att_del_user" autocomplete="username" placeholder="Username" class="w-full border rounded-lg p-2 text-sm">
                            <input type="password" id="att_del_pass" autocomplete="current-password" placeholder="Password" class="w-full border rounded-lg p-2 text-sm">
                            <button type="button" id="att_del_btn" class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium py-2 px-4 rounded-lg">Confirm deletion</button>
                        </div>
                    </div>
                </div>

                <!-- Visitor logs -->
                <div id="visitor-logs" class="settings-tab-content bg-white p-6 rounded-xl shadow-lg border border-gray-100 hidden">
                    <h2 class="text-xl font-semibold text-gray-800 mb-5 border-b pb-3">Visitor Logs</h2>
                    <p class="text-sm text-gray-600 mb-4">Same rules as attendance logs: password for deletion, 30-day retention before permanent removal.</p>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="space-y-3">
                            <h3 class="font-medium text-gray-800">Export date range (optional)</h3>
                            <p class="text-xs text-gray-500">Only applies to export downloads below.</p>
                            <div class="flex gap-2 flex-wrap items-center">
                                <input type="date" id="vis_export_from" class="border rounded-lg p-2 text-sm" aria-label="Visitor export from date">
                                <span class="text-gray-400 text-sm">to</span>
                                <input type="date" id="vis_export_to" class="border rounded-lg p-2 text-sm" aria-label="Visitor export to date">
                            </div>
                            <h3 class="font-medium text-gray-800 pt-2">Export</h3>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" data-export-vis="sql" class="bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded text-sm">SQL</button>
                                <button type="button" data-export-vis="pdf" class="bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded text-sm">PDF</button>
                                <button type="button" data-export-vis="docx" class="bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded text-sm">DOCX</button>
                                <button type="button" data-export-vis="xlsx" class="bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded text-sm">Excel</button>
                                <button type="button" data-export-vis="zip" class="bg-blue-100 hover:bg-blue-200 px-3 py-1.5 rounded text-sm font-medium">ZIP (all types)</button>
                            </div>
                        </div>
                        <div class="space-y-3 border border-red-100 bg-red-50/40 rounded-lg p-4">
                            <h3 class="font-medium text-red-800">Delete logs (soft-delete)</h3>
                            <h4 class="text-sm font-medium text-gray-800 pt-1">Delete date range (optional)</h4>
                            <p class="text-xs text-gray-500">Separate from export. Leave empty to include all active logs.</p>
                            <div class="flex gap-2 flex-wrap items-center">
                                <input type="date" id="vis_del_from" class="border rounded-lg p-2 text-sm bg-white" aria-label="Visitor delete from date">
                                <span class="text-gray-400 text-sm">to</span>
                                <input type="date" id="vis_del_to" class="border rounded-lg p-2 text-sm bg-white" aria-label="Visitor delete to date">
                            </div>
                            <input type="text" id="vis_del_user" autocomplete="username" placeholder="Username" class="w-full border rounded-lg p-2 text-sm">
                            <input type="password" id="vis_del_pass" autocomplete="current-password" placeholder="Password" class="w-full border rounded-lg p-2 text-sm">
                            <button type="button" id="vis_del_btn" class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium py-2 px-4 rounded-lg">Confirm deletion</button>
                        </div>
                    </div>
                </div>

                <!-- System logs -->
                <div id="system-logs" class="settings-tab-content bg-white p-6 rounded-xl shadow-lg border border-gray-100 hidden">
                    <h2 class="text-xl font-semibold text-gray-800 mb-5 border-b pb-3">System Logs</h2>
                    <p class="text-sm text-gray-600 mb-6">Runtime snapshot and a full logical SQL backup of this application&rsquo;s database. Store backups in a secure location; they contain all attendance and visitor data.</p>

                    <div class="grid md:grid-cols-2 gap-4 mb-8">
                        <div class="border border-gray-100 rounded-lg p-4 bg-gray-50/80">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Database</p>
                            <p class="text-sm font-mono text-gray-900 mt-1"><?= htmlspecialchars($systemDbName) ?></p>
                        </div>
                        <div class="border border-gray-100 rounded-lg p-4 bg-gray-50/80">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">MySQL version</p>
                            <p class="text-sm font-mono text-gray-900 mt-1"><?= htmlspecialchars($systemMysqlVer) ?></p>
                        </div>
                        <div class="border border-gray-100 rounded-lg p-4 bg-gray-50/80">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">PHP version</p>
                            <p class="text-sm font-mono text-gray-900 mt-1"><?= htmlspecialchars($systemPhpVer) ?></p>
                        </div>
                        <div class="border border-gray-100 rounded-lg p-4 bg-gray-50/80">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Server time</p>
                            <p class="text-sm font-mono text-gray-900 mt-1"><?= htmlspecialchars(date('Y-m-d H:i:s T')) ?></p>
                        </div>
                    </div>

                    <div class="border border-blue-100 bg-blue-50/50 rounded-xl p-5 mb-6">
                        <h3 class="font-semibold text-gray-900 mb-2">Full database backup (.sql)</h3>
                        <p class="text-sm text-gray-600 mb-4">Downloads schema and data for every table and view in the connected database (same credentials as this app). Restore with MySQL client or phpMyAdmin import.</p>
                        <button type="button" id="system_db_backup_btn" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-sm transition-colors">
                            Download SQL backup
                        </button>
                    </div>

                    <div class="text-sm text-gray-600 space-y-2 border-t pt-5">
                        <p><strong>Login audit</strong> and <strong>Apache access log preview</strong> are under the <button type="button" class="text-blue-600 hover:underline font-medium" onclick="showTab('security'); return false;">Security</button> tab.</p>
                    </div>
                </div>

                <!-- 2. Maintenance Mode Tab (Hidden by default) -->
                <div id="maintenance" class="settings-tab-content bg-white p-6 rounded-xl shadow-lg border border-gray-100 hidden">
                    <h2 class="text-xl font-semibold text-gray-800 mb-5 border-b pb-3">Maintenance Mode</h2>
                    
                    <div class="space-y-6">
                        <!-- Maintenance Mode Toggle -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                            <div class="flex items-start">
                                <svg class="w-6 h-6 text-yellow-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-yellow-800 mb-2">Maintenance Mode</h3>
                                    <p class="text-sm text-yellow-700 mb-4">
                                        When enabled, only <strong>Administrator</strong>, <strong>Admin</strong> (profiling), and <strong>Barangay Secretary</strong> roles may use the system. Other users are blocked until maintenance is disabled.
                                    </p>
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-medium text-gray-800">Enable Maintenance Mode</p>
                                            <p class="text-xs text-gray-600 mt-1">Toggle maintenance mode on or off</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" id="maintenance_mode" onchange="onMaintenanceToggle()">
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Maintenance Message -->
                        <div>
                            <label for="maintenance_message" class="block text-sm font-medium text-gray-700 mb-1">Maintenance Message</label>
                            <textarea id="maintenance_message" rows="3" class="mt-1 block w-full md:w-2/3 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2" placeholder="Enter a message to display during maintenance mode"></textarea>
                            <p class="mt-1 text-xs text-gray-500">Shown to users blocked during maintenance when they hit a protected page.</p>
                        </div>
                    </div>
                    
                    <div class="mt-8 pt-4 border-t">
                        <button onclick="saveMaintenanceSettings()" class="bg-yellow-600 hover:bg-yellow-700 text-white font-medium py-2 px-4 rounded-lg shadow-md transition-colors">
                            Save Maintenance Settings
                        </button>
                    </div>
                </div>

                <!-- 3. Security Settings Tab (Hidden by default) -->
                <div id="security" class="settings-tab-content bg-white p-6 rounded-xl shadow-lg border border-gray-100 hidden">
                    <h2 class="text-xl font-semibold text-gray-800 mb-5 border-b pb-3">Security</h2>

                    <div class="space-y-8">
                        <div>
                            <h3 class="text-lg font-medium text-gray-800 mb-2">User access control</h3>
                            <p class="text-sm text-gray-600 mb-3">Choose which account categories may <strong>log in</strong>. Changes apply on the next login attempt only.</p>
                            <div class="space-y-2 max-w-lg">
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" id="uac_attendance_admins" class="rounded border-gray-300"> Attendance system administrators</label>
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" id="uac_profiling_admin" class="rounded border-gray-300"> Profiling &ldquo;admin&rdquo; accounts</label>
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" id="uac_barangay_officials" class="rounded border-gray-300"> Barangay officials</label>
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" id="uac_residents" class="rounded border-gray-300"> Residents</label>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-lg font-medium text-gray-800 mb-2">Apache access log (XAMPP)</h3>
                            <p class="text-sm text-gray-600 mb-2">Optional path to <code>access.log</code>. Leave blank to auto-detect (e.g. <code>C:/xampp/apache/logs/access.log</code>).</p>
                            <input type="text" id="apache_access_log_path" class="w-full max-w-2xl border rounded-lg p-2 text-sm font-mono" placeholder="C:/xampp/apache/logs/access.log">
                            <button type="button" id="reload_access_log" class="mt-2 bg-gray-100 hover:bg-gray-200 text-sm px-3 py-1.5 rounded">Reload preview</button>
                            <pre id="access_log_preview" class="mt-3 text-xs bg-gray-900 text-green-400 p-3 rounded-lg overflow-x-auto max-h-64 overflow-y-auto whitespace-pre-wrap"></pre>
                            <p id="access_log_meta" class="text-xs text-gray-500 mt-1"></p>
                        </div>

                        <div>
                            <h3 class="text-lg font-medium text-gray-800 mb-2">Login audit log</h3>
                            <p class="text-sm text-gray-600 mb-2">Recorded by this application when <code>AuthController::login</code> is used.</p>
                            <div class="overflow-x-auto border rounded-lg">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50 text-left">
                                        <tr>
                                            <th class="p-2">Time</th>
                                            <th class="p-2">User</th>
                                            <th class="p-2">OK</th>
                                            <th class="p-2">Source</th>
                                            <th class="p-2">Role</th>
                                            <th class="p-2">Message</th>
                                        </tr>
                                    </thead>
                                    <tbody id="login_logs_tbody"></tbody>
                                </table>
                            </div>
                            <div class="flex gap-2 mt-2 items-center">
                                <button type="button" id="login_logs_prev" class="text-sm px-2 py-1 border rounded">Prev</button>
                                <button type="button" id="login_logs_next" class="text-sm px-2 py-1 border rounded">Next</button>
                                <span id="login_logs_page_info" class="text-xs text-gray-500"></span>
                            </div>
                        </div>

                        <div class="pt-4 border-t">
                            <button type="button" onclick="saveSecuritySettings()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-md transition-colors">
                                Save security settings
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script type="module" src="js/settings/main.js"></script>
    
    <!-- App Name Updater -->
    <script src="js/shared/appName.js"></script>

</body>
</html>
