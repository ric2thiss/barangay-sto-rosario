<?php
require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/../auth/helpers.php";
requireAuth(); // Require authentication - redirects to login if not authenticated

include_once '../shared/components/Sidebar.php';
include_once '../shared/components/Breadcrumb.php';

// Get current user for greeting
$currentUser = currentUser();
$userName = $currentUser ? ($currentUser['full_name'] ?? $currentUser['username']) : 'Guest';

// Get search query
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Combined search: residents + employees (barangay officials), both sourced from profiling-system.
// Fingerprint templates remain stored in attendance-system.employee_fingerprints, keyed by employee_id.
$pdo = (new Database())->connect(); // attendance-system DB connection (can query cross-db via fully-qualified names)
$people = [];

if ($searchQuery !== '') {
    $profilingDbName = defined("PROFILING_DB_NAME") ? PROFILING_DB_NAME : "profiling-system";
    $profilingResidentsTable = "`{$profilingDbName}`.`residents`";
    $profilingOfficialsTable = "`{$profilingDbName}`.`barangay_official`";
    $q = "%{$searchQuery}%";

    // 1) Residents (profiling-system).
    // Legacy rows may store prints in employee_fingerprints with employee_id equal to resident_id; enrollment for
    // residents still uses resident_id (resident_fingerprints). Do not expose resident id as "Employee ID" in UI.
    $residentsSql = "
        SELECT
            r.id AS resident_id,
            r.first_name,
            r.middle_name,
            r.surname AS last_name,
            NULL AS suffix
        FROM {$profilingResidentsTable} AS r
        WHERE
            (CONCAT(r.first_name, ' ', IFNULL(r.middle_name, ''), ' ', r.surname) LIKE :q)
            OR (CAST(r.id AS CHAR) LIKE :q)
        ORDER BY r.id DESC
        LIMIT 50
    ";
    $resStmt = $pdo->prepare($residentsSql);
    $resStmt->bindValue(':q', $q, PDO::PARAM_STR);
    $resStmt->execute();
    $residentRows = $resStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // 2) Employees / Barangay officials (profiling-system.barangay_official). These use bo.id directly as employee_id for enrollment.
    $employeesSql = "
        SELECT
            bo.id AS employee_id,
            bo.first_name,
            bo.middle_name,
            bo.surname AS last_name,
            NULL AS suffix
        FROM {$profilingOfficialsTable} AS bo
        WHERE
            (CONCAT(bo.first_name, ' ', IFNULL(bo.middle_name, ''), ' ', bo.surname) LIKE :q)
            OR (CAST(bo.id AS CHAR) LIKE :q)
        ORDER BY bo.id DESC
        LIMIT 50
    ";
    $empStmt = $pdo->prepare($employeesSql);
    $empStmt->bindValue(':q', $q, PDO::PARAM_STR);
    $empStmt->execute();
    $employeeRows = $empStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // 3) Determine enrollment status:
    // - Employees (officials): enrolled if bo.id exists in employee_fingerprints.
    // - Residents: enrolled if resident_id exists in resident_fingerprints OR (legacy) same id exists in employee_fingerprints.

    $residentEmployeeIds = [];
    foreach ($residentRows as $r) {
        if (!empty($r['resident_id'])) {
            $residentEmployeeIds[] = (string) (int) $r['resident_id'];
        }
    }

    $residentIdsToCheck = [];
    foreach ($residentRows as $r) {
        if (!empty($r['resident_id'])) {
            $residentIdsToCheck[] = (int) $r['resident_id'];
        }
    }

    $officialEmployeeIds = [];
    foreach ($employeeRows as $e) {
        if (!empty($e['employee_id'])) {
            $officialEmployeeIds[] = (string) $e['employee_id'];
        }
    }

    $allEmployeeIdsToCheck = array_values(array_unique(array_filter(array_merge($residentEmployeeIds, $officialEmployeeIds))));
    $enrolledEmployeeIds = [];
    if (!empty($allEmployeeIdsToCheck)) {
        $placeholders = implode(',', array_fill(0, count($allEmployeeIdsToCheck), '?'));
        $fpStmt = $pdo->prepare("SELECT employee_id FROM employee_fingerprints WHERE employee_id IN ({$placeholders})");
        $fpStmt->execute($allEmployeeIdsToCheck);
        $enrolledEmployeeIds = array_flip(array_map('strval', $fpStmt->fetchAll(PDO::FETCH_COLUMN) ?: []));
    }

    $enrolledResidentIds = [];
    if (!empty($residentIdsToCheck)) {
        $placeholders = implode(',', array_fill(0, count($residentIdsToCheck), '?'));
        $rfStmt = $pdo->prepare("SELECT resident_id FROM resident_fingerprints WHERE resident_id IN ({$placeholders})");
        $rfStmt->execute($residentIdsToCheck);
        $enrolledResidentIds = array_flip(array_map('strval', $rfStmt->fetchAll(PDO::FETCH_COLUMN) ?: []));
    }

    foreach ($employeeRows as $e) {
        $eid = isset($e['employee_id']) ? (string) $e['employee_id'] : '';
        $people[] = [
            'type' => 'employee',
            'resident_id' => null,
            'employee_id' => $eid,
            'first_name' => $e['first_name'] ?? '',
            'middle_name' => $e['middle_name'] ?? '',
            'last_name' => $e['last_name'] ?? '',
            'suffix' => $e['suffix'] ?? null,
            'has_fingerprint' => ($eid !== '' && isset($enrolledEmployeeIds[$eid])),
            'can_enroll' => ($eid !== ''),
        ];
    }

    foreach ($residentRows as $r) {
        $rid = isset($r['resident_id']) ? (int) $r['resident_id'] : null;
        $residentHasFingerprint = ($rid !== null && isset($enrolledResidentIds[(string) $rid]));
        $employeeHasFingerprint = ($rid !== null && isset($enrolledEmployeeIds[(string) $rid]));
        $people[] = [
            'type' => 'resident',
            'resident_id' => $rid,
            'employee_id' => null,
            'first_name' => $r['first_name'] ?? '',
            'middle_name' => $r['middle_name'] ?? '',
            'last_name' => $r['last_name'] ?? '',
            'suffix' => $r['suffix'] ?? null,
            'has_fingerprint' => ($residentHasFingerprint || $employeeHasFingerprint),
            // Enrollment by resident_id is allowed (stored in resident_fingerprints if not an employee).
            'can_enroll' => ($rid !== null),
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biometric Registration</title>
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
    </style>
</head>
<body>

    <!-- Main Container -->
    <div class="flex min-h-screen">

        <?=Sidebar("Biometric Registration", null, "../utils/img/logo.png")?>

        <!-- 2. MAIN CONTENT AREA -->
        <main class="flex-1 md:ml-64 p-6 transition-all duration-300">

            <!-- Top Header Bar -->
            <header class="mb-6">
                <div class="flex justify-between items-center mb-1">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Biometric Registration</h1>
                        <p class="text-gray-500 text-sm"><?= getGreeting($userName) ?> - Register fingerprints for employees and residents.</p>
                    </div>
                </div>
                <?php Breadcrumb([
                    ['label' => 'Dashboard', 'link' => 'dashboard.php'],
                    ['label' => 'Biometric Registration', 'link' => 'biometric-registration.php']
                ]); ?>
            </header>

            <!-- Search and Selection Section -->
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Search and Select Person</h2>
                
                <!-- Search Form -->
                <form method="GET" action="" class="mb-6">
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <input type="text" 
                                name="search" 
                                id="searchInput"
                                placeholder="Search by name, resident ID, or employee ID..." 
                                value="<?= htmlspecialchars($searchQuery) ?>"
                                class="w-full py-2 pl-10 pr-10 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <?php if (!empty($searchQuery)): ?>
                            <a href="?" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </a>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap">
                            Search
                        </button>
                    </div>
                </form>

                <!-- Selected Resident Display -->
                <div id="selectedResident" class="hidden mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-800">Selected Person:</h3>
                            <p class="text-sm text-gray-600" id="selectedResidentName"></p>
                        <p class="text-xs text-gray-500" id="selectedResidentId"></p>
                        </div>
                        <button type="button" id="clearSelection" class="text-sm text-gray-600 hover:text-gray-800">
                            Clear Selection
                        </button>
                    </div>
                </div>

                <!-- Residents List -->
                <?php if (empty($searchQuery)): ?>
                    <div class="text-center py-8 text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <p class="text-lg font-medium">Search for a resident or employee to begin</p>
                        <p class="text-sm mt-2">Enter a name, resident ID, or employee ID in the search box above.</p>
                    </div>
                <?php elseif (empty($people)): ?>
                    <div class="text-center py-8 text-gray-500">
                        <p class="text-lg font-medium">No results found</p>
                        <p class="text-sm mt-2">Try a different search term.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        <?php foreach ($people as $person): ?>
                            <?php
                                $type = $person['type'] ?? 'resident';
                                $canEnroll = !empty($person['can_enroll']);
                                $classes = "resident-item p-4 border border-gray-200 rounded-lg transition-colors";
                                $classes .= $canEnroll ? " hover:bg-gray-50 cursor-pointer" : " bg-gray-50 opacity-60 cursor-not-allowed";
                            ?>
                            <div class="<?= $classes ?>"
                                 data-type="<?= htmlspecialchars($type) ?>"
                                 data-resident-id="<?= htmlspecialchars((string)($person['resident_id'] ?? '')) ?>"
                                 data-employee-id="<?= htmlspecialchars((string)($person['employee_id'] ?? '')) ?>"
                                 data-resident-name="<?= htmlspecialchars(($person['first_name'] ?? '') . ' ' . ($person['middle_name'] ?? '') . ' ' . ($person['last_name'] ?? '') . ' ' . ($person['suffix'] ?? '')) ?>"
                                 data-has-fingerprint="<?= !empty($person['has_fingerprint']) ? '1' : '0' ?>"
                                 data-can-enroll="<?= $canEnroll ? '1' : '0' ?>">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h3 class="font-medium text-gray-800">
                                            <?= htmlspecialchars(trim(($person['first_name'] ?? '') . ' ' . ($person['middle_name'] ?? '') . ' ' . ($person['last_name'] ?? '') . ' ' . ($person['suffix'] ?? ''))) ?>
                                        </h3>
                                        <p class="text-sm text-gray-600">
                                            <?php if (($type ?? '') === 'resident'): ?>
                                                Resident ID: <?= htmlspecialchars((string)($person['resident_id'] ?? '')) ?>
                                            <?php else: ?>
                                                Employee ID: <?= htmlspecialchars((string)($person['employee_id'] ?? '')) ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="ml-4">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-1 text-[10px] font-semibold rounded-full bg-gray-100 text-gray-700">
                                                <?= ($type === 'employee') ? 'Employee' : 'Resident' ?>
                                            </span>
                                            <?php if (!empty($person['has_fingerprint'])): ?>
                                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                Enrolled
                                            </span>
                                            <?php else: ?>
                                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                Not Enrolled
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Fingerprint Registration Section -->
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Fingerprint Registration</h2>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <div id="registrationInstructions" class="space-y-4">
                        <p class="text-sm text-gray-700">
                            <strong>Instructions:</strong>
                        </p>
                        <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700 ml-4">
                            <li>Search and select a resident or employee from the list above</li>
                            <li>Click the "Register Fingerprint" button below</li>
                            <li>The fingerprint enrollment application will launch</li>
                            <li>Follow the on-screen instructions to complete the registration</li>
                        </ol>
                    </div>

                    <div id="selectedResidentInfo" class="hidden mt-4 p-4 bg-white rounded-lg border border-blue-300">
                        <p class="text-sm font-medium text-gray-800 mb-2">Ready to register fingerprint for:</p>
                        <p class="text-sm text-gray-700" id="registrationResidentName"></p>
                        <p class="text-xs text-gray-500 mt-1" id="registrationResidentId"></p>
                        <p class="text-xs mt-2 hidden" id="fingerprintStatusMessage"></p>
                    </div>

                    <div class="mt-6">
                        <button type="button" 
                                id="registerFingerprintBtn" 
                                disabled
                                class="px-6 py-3 bg-gray-400 text-white font-medium rounded-lg cursor-not-allowed transition-colors">
                            <span class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"></path>
                                </svg>
                                Register Fingerprint
                            </span>
                        </button>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- JavaScript -->
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

        if (mainContent) {
            mainContent.addEventListener('click', () => {
                if (window.innerWidth < 768 && sidebar.classList.contains('translate-x-0')) {
                    sidebar.classList.remove('translate-x-0');
                    sidebar.classList.add('-translate-x-full');
                    mainContent.classList.remove('opacity-50', 'pointer-events-none');
                }
            });
        }

        // Resident/Employee Selection Logic
        let selectedResidentId = null;
        let selectedEmployeeId = null;
        let selectedType = null; // 'resident' | 'employee'
        let selectedResidentName = null;
        let selectedHasFingerprint = false;

        // Handle resident item clicks
        document.querySelectorAll('.resident-item').forEach(item => {
            item.addEventListener('click', function() {
                const canEnroll = this.getAttribute('data-can-enroll') === '1';
                if (!canEnroll) {
                    return; // not selectable
                }

                // Remove previous selection
                document.querySelectorAll('.resident-item').forEach(i => {
                    i.classList.remove('bg-blue-100', 'border-blue-400');
                    i.classList.add('border-gray-200');
                });

                // Add selection to clicked item
                this.classList.add('bg-blue-100', 'border-blue-400');
                this.classList.remove('border-gray-200');

                // Get selected person data
                selectedType = this.getAttribute('data-type') || null;
                selectedResidentId = this.getAttribute('data-resident-id') || null;
                selectedEmployeeId = this.getAttribute('data-employee-id') || null;
                selectedResidentName = this.getAttribute('data-resident-name');
                selectedHasFingerprint = this.getAttribute('data-has-fingerprint') === '1';

                // Show selected resident info
                document.getElementById('selectedResident').classList.remove('hidden');
                document.getElementById('selectedResidentName').textContent = selectedResidentName;

                let idLabel = '';
                if (selectedType === 'employee') {
                    idLabel = `Employee ID: ${selectedEmployeeId}`;
                } else {
                    idLabel = selectedEmployeeId
                        ? `Resident ID: ${selectedResidentId} | Employee ID: ${selectedEmployeeId}`
                        : `Resident ID: ${selectedResidentId}`;
                }
                document.getElementById('selectedResidentId').textContent = idLabel;

                // Show registration info
                document.getElementById('selectedResidentInfo').classList.remove('hidden');
                document.getElementById('registrationResidentName').textContent = selectedResidentName;
                document.getElementById('registrationResidentId').textContent = idLabel;

                // Update status + enable/disable register button
                const registerBtn = document.getElementById('registerFingerprintBtn');
                const statusEl = document.getElementById('fingerprintStatusMessage');

                if (statusEl) {
                    statusEl.classList.remove('hidden', 'text-green-700', 'text-yellow-700');
                    if (selectedHasFingerprint) {
                        statusEl.textContent = 'Fingerprint already registered. Registration is disabled.';
                        statusEl.classList.add('text-green-700');
                    } else {
                        statusEl.textContent = 'No fingerprint registered yet.';
                        statusEl.classList.add('text-yellow-700');
                    }
                }

                if (selectedHasFingerprint) {
                    registerBtn.disabled = true;
                    registerBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
                    registerBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700', 'cursor-pointer');
                } else {
                    registerBtn.disabled = false;
                    registerBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
                    registerBtn.classList.add('bg-blue-600', 'hover:bg-blue-700', 'cursor-pointer');
                }
            });
        });

        // Clear selection
        document.getElementById('clearSelection')?.addEventListener('click', function() {
            selectedResidentId = null;
            selectedEmployeeId = null;
            selectedType = null;
            selectedResidentName = null;
            selectedHasFingerprint = false;

            // Remove selection styling
            document.querySelectorAll('.resident-item').forEach(i => {
                i.classList.remove('bg-blue-100', 'border-blue-400');
                i.classList.add('border-gray-200');
            });

            // Hide selected resident info
            document.getElementById('selectedResident').classList.add('hidden');
            document.getElementById('selectedResidentInfo').classList.add('hidden');
            const statusEl = document.getElementById('fingerprintStatusMessage');
            if (statusEl) {
                statusEl.textContent = '';
                statusEl.classList.add('hidden');
                statusEl.classList.remove('text-green-700', 'text-yellow-700');
            }

            // Disable register button
            const registerBtn = document.getElementById('registerFingerprintBtn');
            registerBtn.disabled = true;
            registerBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
            registerBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700', 'cursor-pointer');
        });

        // Register fingerprint button
        document.getElementById('registerFingerprintBtn')?.addEventListener('click', function() {
            if (!selectedType) {
                alert('Please select a resident or employee first.');
                return;
            }
            if (selectedHasFingerprint) {
                alert('This person already has a registered fingerprint.');
                return;
            }

            // Launch Enrollment.exe using the biometrics:// protocol
            // The protocol should be registered in Windows to work properly
            // Note: Enrollment.cs sends employee_id if provided; otherwise resident_id.
            let protocolUrl = '';
            if (selectedType === 'employee') {
                protocolUrl = 'biometrics://enroll?employee_id=' + encodeURIComponent(selectedEmployeeId || '');
            } else {
                protocolUrl = 'biometrics://enroll?resident_id=' + encodeURIComponent(selectedResidentId || '');
            }
            
            // Log for debugging
            console.log('Launching enrollment with URL:', protocolUrl);
            console.log('Selected type:', selectedType);
            console.log('Resident ID:', selectedResidentId);
            console.log('Employee ID:', selectedEmployeeId);
            
            try {
                // Try to launch using the custom protocol
                // Using window.location.href to trigger the protocol handler
                window.location.href = protocolUrl;
                
                // Show confirmation message after a short delay
                setTimeout(() => {
                    console.log('Protocol handler triggered');
                }, 100);
            } catch (error) {
                // Fallback: show instructions
                console.error('Error launching enrollment:', error);
                alert('Unable to launch enrollment application automatically.\n\nError: ' + error.message + '\n\nPlease manually launch Enrollment.exe with:\n\n' + protocolUrl);
            }
        });
    </script>
    
    <!-- App Name Updater -->
    <script src="js/shared/appName.js"></script>
</body>
</html>
