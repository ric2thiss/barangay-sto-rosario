<?php
require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../auth/helpers.php";
requireAuth(); // Require authentication - redirects to login if not authenticated

// Residents are managed by profiling-system
header("Location: ../residents.php?error=Residents are managed by profiling-system.");
exit;

include_once '../../shared/components/Sidebar.php';
include_once '../../shared/components/Breadcrumb.php';

// Get current user for greeting
$currentUser = currentUser();
$userName = $currentUser ? ($currentUser['full_name'] ?? $currentUser['username']) : 'Guest';

// Get resident ID from query parameter
$residentId = $_GET['id'] ?? null;

if (empty($residentId)) {
    header("Location: ../residents.php?error=No resident ID provided");
    exit;
}

// Ensure residentId is an integer
$residentId = intval($residentId);
if ($residentId <= 0) {
    header("Location: ../residents.php?error=Invalid resident ID");
    exit;
}

// Load existing resident data
$residentController = new ResidentController();
$resident = $residentController->getAllResidents($residentId);

// Check if resident exists
if ($resident === null || empty($resident)) {
    header("Location: ../residents.php?error=Resident not found");
    exit;
}

// Calculate age
$age = '-';
if (!empty($resident['birthdate'])) {
    $dob = new DateTime($resident['birthdate']);
    $today = new DateTime(date("Y-m-d"));
    $age = $today->diff($dob)->y;
}

// Format full name
$fullName = trim(($resident['first_name'] ?? '') . ' ' . ($resident['middle_name'] ?? '') . ' ' . ($resident['last_name'] ?? '') . ' ' . ($resident['suffix'] ?? ''));

// Format place of birth
$placeOfBirth = [];
if (!empty($resident['place_of_birth_city'])) $placeOfBirth[] = $resident['place_of_birth_city'];
if (!empty($resident['place_of_birth_province'])) $placeOfBirth[] = $resident['place_of_birth_province'];
$placeOfBirthStr = !empty($placeOfBirth) ? implode(', ', $placeOfBirth) : 'N/A';

// Format address
$addressParts = [];
if (!empty($resident['house_number'])) $addressParts[] = $resident['house_number'];
if (!empty($resident['street_name'])) $addressParts[] = $resident['street_name'];
if (!empty($resident['barangay'])) $addressParts[] = $resident['barangay'];
if (!empty($resident['municipality_city'])) $addressParts[] = $resident['municipality_city'];
if (!empty($resident['province'])) $addressParts[] = $resident['province'];
$fullAddress = !empty($addressParts) ? implode(', ', $addressParts) : 'N/A';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Resident - <?= htmlspecialchars($fullName) ?></title>
    <!-- Load global css -->
    <link rel="stylesheet" href="../../utils/styles/global.css">
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Professional compact design */
        .info-label {
            font-size: 0.875rem; /* 14px */
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .info-value {
            font-size: 0.9375rem; /* 15px */
            color: #111827;
            padding: 0.25rem 0;
            font-weight: 500;
        }
        .section-header {
            font-size: 1.125rem; /* 18px */
            font-weight: 700;
        }
        .info-section {
            margin-bottom: 1.5rem;
            padding: 1.25rem;
            background: #f9fafb;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <!-- Main Container -->
    <div class="flex min-h-screen">
        <?=Sidebar("Residents", null, "../../utils/img/logo.png")?>

        <!-- 2. MAIN CONTENT AREA -->
        <main class="flex-1 md:ml-64 p-6 transition-all duration-300">
            <!-- Top Header Bar -->
            <header class="mb-4">
                <div class="flex justify-between items-center mb-3">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Resident Information</h1>
                        <p class="text-sm text-gray-500 mt-1"><?= getGreeting($userName) ?> - Viewing resident details.</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="edit.php?id=<?= $residentId ?>" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Edit
                        </a>
                        <a href="../residents.php" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to List
                        </a>
                    </div>
                </div>
                <?php Breadcrumb([
                    ['label' => 'Dashboard', 'link' => '../dashboard.php'],
                    ['label' => 'Residents', 'link' => '../residents.php'],
                    ['label' => 'View Resident', 'link' => 'view.php?id=' . $residentId]
                ]); ?>
            </header>

            <!-- Resident Information Card -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                
                <!-- Profile Header -->
                <div class="flex flex-col md:flex-row gap-4 mb-6 pb-4 border-b border-gray-200">
                    <div class="flex-shrink-0">
                        <?php 
                        $photoPath = $resident['photo_path'] ?? null;
                        $photoUrl = $photoPath ? '../../' . $photoPath : 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'120\' height=\'120\'%3E%3Crect width=\'120\' height=\'120\' fill=\'%23e5e7eb\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' fill=\'%239ca3af\' font-size=\'12\'%3ENo Photo%3C/text%3E%3C/svg%3E';
                        ?>
                        <img src="<?= htmlspecialchars($photoUrl) ?>" 
                             alt="Profile Picture" 
                             class="w-32 h-32 object-cover rounded-lg shadow-sm border-2 border-blue-500"
                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'120\' height=\'120\'%3E%3Crect width=\'120\' height=\'120\' fill=\'%23e5e7eb\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' fill=\'%239ca3af\' font-size=\'12\'%3ENo Photo%3C/text%3E%3C/svg%3E'">
                    </div>
                    <div class="flex-grow">
                        <h2 class="text-xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($fullName) ?></h2>
                        <div class="flex flex-wrap gap-2 mb-2">
                            <span class="px-2.5 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">
                                ID: <?= htmlspecialchars($resident['resident_id'] ?? 'N/A') ?>
                            </span>
                            <?php if (!empty($resident['status_type'])): ?>
                                <span class="px-2.5 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">
                                    <?= htmlspecialchars($resident['status_type']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if (isset($resident['is_active'])): ?>
                                <?php if ($resident['is_active'] == 1): ?>
                                    <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-xs font-semibold rounded">
                                        ✓ Alive
                                    </span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded">
                                        Deceased
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <p class="text-sm text-gray-600">
                            <span class="font-medium">Age:</span> <?= htmlspecialchars($age) ?> years old
                        </p>
                    </div>
                </div>

                <!-- Personal Information Section -->
                <div class="info-section">
                    <h3 class="section-header text-gray-800 mb-4 pb-2 border-b border-gray-300">
                        Personal Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <div class="info-label">Resident ID</div>
                            <div class="info-value"><?= htmlspecialchars($resident['resident_id'] ?? 'N/A') ?></div>
                        </div>
                        <?php if (!empty($resident['phil_sys_number'])): ?>
                        <div>
                            <div class="info-label">PhilSys Number</div>
                            <div class="info-value"><?= htmlspecialchars($resident['phil_sys_number']) ?></div>
                        </div>
                        <?php endif; ?>
                        <div>
                            <div class="info-label">First Name</div>
                            <div class="info-value"><?= htmlspecialchars($resident['first_name'] ?? 'N/A') ?></div>
                        </div>
                        <div>
                            <div class="info-label">Middle Name</div>
                            <div class="info-value"><?= htmlspecialchars($resident['middle_name'] ?? 'N/A') ?></div>
                        </div>
                        <div>
                            <div class="info-label">Last Name</div>
                            <div class="info-value"><?= htmlspecialchars($resident['last_name'] ?? 'N/A') ?></div>
                        </div>
                        <?php if (!empty($resident['suffix'])): ?>
                        <div>
                            <div class="info-label">Suffix</div>
                            <div class="info-value"><?= htmlspecialchars($resident['suffix']) ?></div>
                        </div>
                        <?php endif; ?>
                        <div>
                            <div class="info-label">Gender</div>
                            <div class="info-value"><?= htmlspecialchars($resident['gender'] ?? 'N/A') ?></div>
                        </div>
                        <div>
                            <div class="info-label">Birthdate</div>
                            <div class="info-value">
                                <?php 
                                if (!empty($resident['birthdate'])) {
                                    $birthdate = new DateTime($resident['birthdate']);
                                    echo htmlspecialchars($birthdate->format('F d, Y'));
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </div>
                        </div>
                        <div>
                            <div class="info-label">Place of Birth</div>
                            <div class="info-value"><?= htmlspecialchars($placeOfBirthStr) ?></div>
                        </div>
                        <div>
                            <div class="info-label">Civil Status</div>
                            <div class="info-value"><?= htmlspecialchars($resident['status_name'] ?? $resident['civil_status'] ?? 'N/A') ?></div>
                        </div>
                        <div>
                            <div class="info-label">Blood Type</div>
                            <div class="info-value"><?= htmlspecialchars($resident['blood_type'] ?? 'N/A') ?></div>
                        </div>
                    </div>
                </div>

                <!-- Address Information Section -->
                <div class="info-section">
                    <h3 class="section-header text-gray-800 mb-4 pb-2 border-b border-gray-300">
                        Address Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-3">
                            <div class="info-label">Full Address</div>
                            <div class="info-value"><?= htmlspecialchars($fullAddress) ?></div>
                        </div>
                        <?php if (!empty($resident['house_number'])): ?>
                        <div>
                            <div class="info-label">House Number</div>
                            <div class="info-value"><?= htmlspecialchars($resident['house_number']) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($resident['street_name'])): ?>
                        <div>
                            <div class="info-label">Street Name</div>
                            <div class="info-value"><?= htmlspecialchars($resident['street_name']) ?></div>
                        </div>
                        <?php endif; ?>
                        <div>
                            <div class="info-label">Barangay</div>
                            <div class="info-value"><?= htmlspecialchars($resident['barangay'] ?? 'N/A') ?></div>
                        </div>
                        <div>
                            <div class="info-label">Municipality/City</div>
                            <div class="info-value"><?= htmlspecialchars($resident['municipality_city'] ?? 'N/A') ?></div>
                        </div>
                        <div>
                            <div class="info-label">Province</div>
                            <div class="info-value"><?= htmlspecialchars($resident['province'] ?? 'N/A') ?></div>
                        </div>
                        <?php if (!empty($resident['postal_code'])): ?>
                        <div>
                            <div class="info-label">Postal Code</div>
                            <div class="info-value"><?= htmlspecialchars($resident['postal_code']) ?></div>
                        </div>
                        <?php endif; ?>
                        <div>
                            <div class="info-label">Property Owner</div>
                            <div class="info-value">
                                <?php 
                                if (isset($resident['is_owner'])) {
                                    echo $resident['is_owner'] == 1 ? 'Yes' : 'No';
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </div>
                        </div>
                        <?php if (!empty($resident['months_of_residency'])): ?>
                        <div>
                            <div class="info-label">Months of Residency</div>
                            <div class="info-value"><?= htmlspecialchars($resident['months_of_residency']) ?> months</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Contact & Occupation Section -->
                <div class="info-section">
                    <h3 class="section-header text-gray-800 mb-4 pb-2 border-b border-gray-300">
                        Contact & Occupation
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <div class="info-label">Mobile Number</div>
                            <div class="info-value"><?= htmlspecialchars($resident['contact_value'] ?? 'N/A') ?></div>
                        </div>
                        <div>
                            <div class="info-label">Job Title</div>
                            <div class="info-value"><?= htmlspecialchars($resident['job_title'] ?? 'N/A') ?></div>
                        </div>
                        <div>
                            <div class="info-label">Employer</div>
                            <div class="info-value"><?= htmlspecialchars($resident['employer'] ?? 'N/A') ?></div>
                        </div>
                        <div>
                            <div class="info-label">Income Bracket</div>
                            <div class="info-value"><?= htmlspecialchars($resident['income_bracket'] ?? 'N/A') ?></div>
                        </div>
                    </div>
                </div>

                <!-- Resident Status Section -->
                <div class="info-section">
                    <h3 class="section-header text-gray-800 mb-4 pb-2 border-b border-gray-300">
                        Resident Status
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div class="info-label">Status Type</div>
                            <div class="info-value">
                                <?php if (!empty($resident['status_type'])): ?>
                                    <span class="px-2 py-0.5 bg-green-100 text-green-800 font-semibold rounded text-xs">
                                        <?= htmlspecialchars($resident['status_type']) ?>
                                    </span>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <div class="info-label">Life Status</div>
                            <div class="info-value">
                                <?php if (isset($resident['is_active'])): ?>
                                    <?php if ($resident['is_active'] == 1): ?>
                                        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 font-semibold rounded text-xs">
                                            ✓ Alive
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 bg-red-100 text-red-800 font-semibold rounded text-xs">
                                            Deceased
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Information Section -->
                <?php if (!empty($resident['id_type']) || !empty($resident['biometric_type']) || !empty($resident['relatives'])): ?>
                <div class="info-section">
                    <h3 class="section-header text-gray-800 mb-4 pb-2 border-b border-gray-300">
                        Additional Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <?php if (!empty($resident['id_type'])): ?>
                        <div>
                            <div class="info-label">ID Type</div>
                            <div class="info-value"><?= htmlspecialchars($resident['id_type']) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($resident['id_number'])): ?>
                        <div>
                            <div class="info-label">ID Number</div>
                            <div class="info-value"><?= htmlspecialchars($resident['id_number']) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($resident['biometric_type'])): ?>
                        <div>
                            <div class="info-label">Biometric Type</div>
                            <div class="info-value"><?= htmlspecialchars($resident['biometric_type']) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($resident['relatives']) && is_array($resident['relatives'])): ?>
                        <div class="md:col-span-3">
                            <div class="info-label">Relatives</div>
                            <div class="info-value">
                                <ul class="list-disc list-inside space-y-1">
                                    <?php foreach ($resident['relatives'] as $relative): ?>
                                        <li class="text-sm">
                                            <?= htmlspecialchars(($relative['first_name'] ?? '') . ' ' . ($relative['last_name'] ?? '')) ?>
                                            <?php if (!empty($relative['relationship_type'])): ?>
                                                <span class="text-gray-500">(<?= htmlspecialchars($relative['relationship_type']) ?>)</span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="mt-6 pt-4 border-t border-gray-200 flex flex-col sm:flex-row gap-3 justify-end">
                    <a href="../residents.php" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors shadow-sm text-center">
                        ← Back to List
                    </a>
                    <a href="edit.php?id=<?= $residentId ?>" class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm text-center">
                        Edit Resident
                    </a>
                </div>
            </div>
        </main>
    </div>
    <script src="../../shared/components/Sidebar.js"></script>
</body>
</html>
