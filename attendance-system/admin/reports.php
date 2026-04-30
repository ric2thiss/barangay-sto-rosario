<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth/helpers.php';
requireAuth();

include_once __DIR__ . '/../shared/components/Sidebar.php';
include_once __DIR__ . '/../shared/components/Breadcrumb.php';

$currentUser = currentUser();
$userName = $currentUser ? ($currentUser['full_name'] ?? $currentUser['username']) : 'Guest';

$logoUrl = rtrim(BASE_URL, '/') . '/utils/img/logo.png';
$aa = 'attendance-analytics.php';
$vl = 'visitor-lists.php';
$va = 'visitor-analytics.php';
$dtr = 'dtr.php';
$pay = 'payroll.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link rel="stylesheet" href="../utils/styles/global.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { overflow-x: hidden; }</style>
</head>
<body>
    <div class="flex min-h-screen">
        <?= Sidebar('Reports', null, $logoUrl) ?>

        <main class="flex-1 md:ml-64 p-6 transition-all duration-300">
            <header class="mb-8">
                <h1 class="text-2xl font-semibold text-gray-800">Reports</h1>
                <p class="text-gray-500 text-sm mt-1"><?= htmlspecialchars(getGreeting($userName), ENT_QUOTES, 'UTF-8') ?> — Open the report or export you need. Attendance hours charts and CSV export live under Attendance Analytics.</p>
                <?php Breadcrumb([
                    ['label' => 'Dashboard', 'link' => 'dashboard.php'],
                    ['label' => 'Reports', 'link' => 'reports.php'],
                ]); ?>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-5xl">
                <a href="attendance-reports.php" class="block bg-white rounded-xl border border-gray-100 shadow p-6 hover:border-blue-200 hover:shadow-md transition-all">
                    <h2 class="text-lg font-semibold text-gray-800">Attendance Reports (print &amp; fines)</h2>
                    <p class="text-sm text-gray-600 mt-2">Event roster, absent fines, CSV / Word export, and PDF. Separate from live attendance screen.</p>
                    <span class="inline-block mt-4 text-sm font-medium text-blue-600">Open →</span>
                </a>

                <a href="<?= htmlspecialchars($aa, ENT_QUOTES, 'UTF-8') ?>#hours-reports" class="block bg-white rounded-xl border border-gray-100 shadow p-6 hover:border-blue-200 hover:shadow-md transition-all">
                    <h2 class="text-lg font-semibold text-gray-800">Attendance — hours &amp; summaries</h2>
                    <p class="text-sm text-gray-600 mt-2">Total hours by position, chairmanship, or employee; daily summary table; CSV export (D3 bar chart). Same data as the former standalone attendance reports page.</p>
                    <span class="inline-block mt-4 text-sm font-medium text-blue-600">Open in Attendance Analytics →</span>
                </a>

                <a href="<?= htmlspecialchars($aa, ENT_QUOTES, 'UTF-8') ?>" class="block bg-white rounded-xl border border-gray-100 shadow p-6 hover:border-blue-200 hover:shadow-md transition-all">
                    <h2 class="text-lg font-semibold text-gray-800">Attendance Analytics</h2>
                    <p class="text-sm text-gray-600 mt-2">Compliance, perfect attendance, needs-attention drill-downs, Chart.js dashboards, master-list window table, and gap fill.</p>
                    <span class="inline-block mt-4 text-sm font-medium text-blue-600">Open →</span>
                </a>

                <a href="<?= htmlspecialchars($vl, ENT_QUOTES, 'UTF-8') ?>" class="block bg-white rounded-xl border border-gray-100 shadow p-6 hover:border-blue-200 hover:shadow-md transition-all">
                    <h2 class="text-lg font-semibold text-gray-800">Visitor reports</h2>
                    <p class="text-sm text-gray-600 mt-2">Visitor logs listing, filters, and printable-style views.</p>
                    <span class="inline-block mt-4 text-sm font-medium text-blue-600">Open →</span>
                </a>

                <a href="<?= htmlspecialchars($va, ENT_QUOTES, 'UTF-8') ?>" class="block bg-white rounded-xl border border-gray-100 shadow p-6 hover:border-blue-200 hover:shadow-md transition-all">
                    <h2 class="text-lg font-semibold text-gray-800">Visitor Analytics</h2>
                    <p class="text-sm text-gray-600 mt-2">Visitor traffic charts and analytics dashboard.</p>
                    <span class="inline-block mt-4 text-sm font-medium text-blue-600">Open →</span>
                </a>

                <a href="<?= htmlspecialchars($dtr, ENT_QUOTES, 'UTF-8') ?>" class="block bg-white rounded-xl border border-gray-100 shadow p-6 hover:border-blue-200 hover:shadow-md transition-all">
                    <h2 class="text-lg font-semibold text-gray-800">DTR</h2>
                    <p class="text-sm text-gray-600 mt-2">Daily time records and related views for officials.</p>
                    <span class="inline-block mt-4 text-sm font-medium text-blue-600">Open →</span>
                </a>

                <a href="<?= htmlspecialchars($pay, ENT_QUOTES, 'UTF-8') ?>" class="block bg-white rounded-xl border border-gray-100 shadow p-6 hover:border-blue-200 hover:shadow-md transition-all">
                    <h2 class="text-lg font-semibold text-gray-800">Payroll</h2>
                    <p class="text-sm text-gray-600 mt-2">Payroll period reports and exports where configured.</p>
                    <span class="inline-block mt-4 text-sm font-medium text-blue-600">Open →</span>
                </a>
            </div>
        </main>
    </div>
    <script type="module" src="js/reports/hub.js"></script>
</body>
</html>
