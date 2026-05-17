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

// Get civil status options using QueryBuilder directly
require_once __DIR__ . "/../../app/query/QueryBuilder.php";
$pdo = (new Database())->connect();
$queryBuilder = new QueryBuilder($pdo);
$civilStatuses = $queryBuilder->table("civil_status")->select("*")->get();

$error = "";
$success = "";

// Helper function to handle file uploads
function handleFileUpload($file, $uploadDir, $maxSize = 5242880) { // 5MB default
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return ['error' => 'File size exceeds maximum allowed size'];
    }
    
    // Create upload directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . '/' . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filepath;
    }
    
    return ['error' => 'Failed to upload file'];
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $residentController = new ResidentController();
    
    // Handle file uploads - 3 photos
    $photoPaths = [];
    $uploadDir = __DIR__ . '/../../storage/img/residents';
    
    // Upload up to 3 photos
    for ($i = 1; $i <= 3; $i++) {
        $fileKey = 'photo_' . $i;
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $result = handleFileUpload($_FILES[$fileKey], $uploadDir, 5242880); // 5MB
            if (is_string($result)) {
                $photoPaths[] = str_replace(__DIR__ . '/../../', '', $result);
            } elseif (isset($result['error'])) {
                $error = isset($error) ? $error . ' ' . $result['error'] : $result['error'];
            }
        }
    }
    
    // Store photos as JSON array (use first photo for backward compatibility if needed)
    $photoPath = !empty($photoPaths) ? json_encode($photoPaths) : null;
    
    // Convert empty phil_sys_number to null to avoid UNIQUE constraint violation
    $philSysNumber = trim($_POST['phil_sys_number'] ?? '');
    $philSysNumber = !empty($philSysNumber) ? $philSysNumber : null;
    
    $data = [
        'phil_sys_number' => $philSysNumber,
        'first_name' => trim($_POST['first_name'] ?? ''),
        'middle_name' => trim($_POST['middle_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'suffix' => trim($_POST['suffix'] ?? ''),
        'gender' => $_POST['gender'] ?? '',
        'birthdate' => $_POST['birthdate'] ?? '',
        'place_of_birth_city' => trim($_POST['place_of_birth_city'] ?? ''),
        'place_of_birth_province' => trim($_POST['place_of_birth_province'] ?? ''),
        'blood_type' => trim($_POST['blood_type'] ?? ''),
        'civil_status_id' => !empty($_POST['civil_status_id']) ? intval($_POST['civil_status_id']) : null,
        'photo_path' => $photoPath,
    ];
    
    // Address data
    $addressData = [
        'address_type' => $_POST['address_type'] ?? 'Permanent',
        'house_number' => trim($_POST['house_number'] ?? ''),
        'building_name' => trim($_POST['building_name'] ?? ''),
        'street_name' => trim($_POST['street_name'] ?? ''),
        'subdivision_village' => trim($_POST['subdivision_village'] ?? ''),
        'purok' => trim($_POST['purok'] ?? ''),
        'sitio' => trim($_POST['sitio'] ?? ''),
        'barangay' => trim($_POST['barangay'] ?? ''),
        'district' => trim($_POST['district'] ?? ''),
        'municipality_city' => trim($_POST['municipality_city'] ?? ''),
        'province' => trim($_POST['province'] ?? ''),
        'region' => trim($_POST['region'] ?? ''),
        'postal_code' => trim($_POST['postal_code'] ?? ''),
        'months_of_residency' => !empty($_POST['months_of_residency']) ? intval($_POST['months_of_residency']) : null,
        'is_owner' => isset($_POST['is_owner']) ? intval($_POST['is_owner']) : 0,
    ];
    
    // Resident status data
    $statusData = [
        'status_type' => $_POST['status_type'] ?? '',
        'is_active' => isset($_POST['is_active']) ? intval($_POST['is_active']) : 1,
    ];
    
    $result = $residentController->store($data, $addressData, $statusData, null);
    
    if ($result["success"]) {
        $success = $result["message"];
        // Redirect after 2 seconds
        header("Refresh: 2; url=../residents.php");
    } else {
        $error = $result["message"];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register New Resident</title>
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

        <?=Sidebar("Residents", null, "../../utils/img/logo.png")?>

        <!-- 2. MAIN CONTENT AREA -->
        <main class="flex-1 md:ml-64 p-6 transition-all duration-300">

            <!-- Top Header Bar -->
            <header class="mb-6">
                <div class="flex justify-between items-center mb-1">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Register New Resident</h1>
                        <p class="text-gray-500 text-sm"><?= getGreeting($userName) ?> - Fill out the form below to register a new resident.</p>
                    </div>
                </div>
                <?php Breadcrumb([
                    ['label' => 'Dashboard', 'link' => '../dashboard.php'],
                    ['label' => 'Residents', 'link' => '../residents.php'],
                    ['label' => 'Register New Resident', 'link' => 'create.php']
                ]); ?>
            </header>

            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">
                    <p class="font-medium"><?= htmlspecialchars($success) ?></p>
                    <p class="text-sm mt-1">Redirecting to residents list...</p>
                </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                    <p class="font-medium"><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                <form method="POST" action="" id="residentForm" enctype="multipart/form-data" class="space-y-6">
                    
                    <!-- Tab Navigation -->
                    <div class="border-b border-gray-200 mb-6">
                        <nav class="flex space-x-8" aria-label="Tabs">
                            <button type="button" 
                                class="tab-button active whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                                data-tab="personal"
                                id="tab-personal">
                                <span class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Personal Information
                                </span>
                            </button>
                            <button type="button" 
                                class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                                data-tab="address"
                                id="tab-address">
                                <span class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    Address Information
                                </span>
                            </button>
                            <button type="button" 
                                class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                                data-tab="status"
                                id="tab-status">
                                <span class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Resident Status
                                </span>
                            </button>
                            <button type="button" 
                                class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                                data-tab="documents"
                                id="tab-documents">
                                <span class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Photos
                                </span>
                            </button>
                        </nav>
                    </div>

                    <!-- Tab Content -->
                    <!-- Personal Information Section -->
                    <div class="tab-content" id="content-personal">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Personal Information</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- PhilSys Number -->
                            <div>
                                <label for="phil_sys_number" class="block text-sm font-medium text-gray-700 mb-2">
                                    PhilSys Number
                                </label>
                                <input type="text" name="phil_sys_number" id="phil_sys_number" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter PhilSys number">
                            </div>

                            <!-- Civil Status -->
                            <div>
                                <label for="civil_status_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Civil Status
                                </label>
                                <select name="civil_status_id" id="civil_status_id" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Civil Status</option>
                                    <?php foreach ($civilStatuses as $status): ?>
                                        <?php 
                                        // Handle both object and array results
                                        if (is_object($status)) {
                                            $statusId = $status->civil_status_id;
                                            $statusName = $status->status_name;
                                        } else {
                                            $statusId = $status['civil_status_id'];
                                            $statusName = $status['status_name'];
                                        }
                                        ?>
                                        <option value="<?= $statusId ?>"><?= htmlspecialchars($statusName) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- First Name -->
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    First Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="first_name" id="first_name" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter first name"
                                    value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                            </div>

                            <!-- Middle Name -->
                            <div>
                                <label for="middle_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Middle Name
                                </label>
                                <input type="text" name="middle_name" id="middle_name"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter middle name"
                                    value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>">
                            </div>

                            <!-- Last Name -->
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Last Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="last_name" id="last_name" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter last name"
                                    value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                            </div>

                            <!-- Suffix -->
                            <div>
                                <label for="suffix" class="block text-sm font-medium text-gray-700 mb-2">
                                    Suffix (Jr., Sr., etc.)
                                </label>
                                <input type="text" name="suffix" id="suffix"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Jr., Sr., III, etc."
                                    value="<?= htmlspecialchars($_POST['suffix'] ?? '') ?>">
                            </div>

                            <!-- Gender -->
                            <div>
                                <label for="gender" class="block text-sm font-medium text-gray-700 mb-2">
                                    Gender <span class="text-red-500">*</span>
                                </label>
                                <select name="gender" id="gender" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?= (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= (isset($_POST['gender']) && $_POST['gender'] === 'Other') ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>

                            <!-- Birthdate -->
                            <div>
                                <label for="birthdate" class="block text-sm font-medium text-gray-700 mb-2">
                                    Birthdate <span class="text-red-500">*</span>
                                </label>
                                <input type="date" name="birthdate" id="birthdate" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    value="<?= htmlspecialchars($_POST['birthdate'] ?? '') ?>">
                            </div>

                            <!-- Place of Birth City -->
                            <div>
                                <label for="place_of_birth_city" class="block text-sm font-medium text-gray-700 mb-2">
                                    Place of Birth (City)
                                </label>
                                <input type="text" name="place_of_birth_city" id="place_of_birth_city"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter city"
                                    value="<?= htmlspecialchars($_POST['place_of_birth_city'] ?? '') ?>">
                            </div>

                            <!-- Place of Birth Province -->
                            <div>
                                <label for="place_of_birth_province" class="block text-sm font-medium text-gray-700 mb-2">
                                    Place of Birth (Province)
                                </label>
                                <input type="text" name="place_of_birth_province" id="place_of_birth_province"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter province"
                                    value="<?= htmlspecialchars($_POST['place_of_birth_province'] ?? '') ?>">
                            </div>

                            <!-- Blood Type -->
                            <div>
                                <label for="blood_type" class="block text-sm font-medium text-gray-700 mb-2">
                                    Blood Type
                                </label>
                                <select name="blood_type" id="blood_type"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Blood Type</option>
                                    <option value="A+" <?= (isset($_POST['blood_type']) && $_POST['blood_type'] === 'A+') ? 'selected' : '' ?>>A+</option>
                                    <option value="A-" <?= (isset($_POST['blood_type']) && $_POST['blood_type'] === 'A-') ? 'selected' : '' ?>>A-</option>
                                    <option value="B+" <?= (isset($_POST['blood_type']) && $_POST['blood_type'] === 'B+') ? 'selected' : '' ?>>B+</option>
                                    <option value="B-" <?= (isset($_POST['blood_type']) && $_POST['blood_type'] === 'B-') ? 'selected' : '' ?>>B-</option>
                                    <option value="AB+" <?= (isset($_POST['blood_type']) && $_POST['blood_type'] === 'AB+') ? 'selected' : '' ?>>AB+</option>
                                    <option value="AB-" <?= (isset($_POST['blood_type']) && $_POST['blood_type'] === 'AB-') ? 'selected' : '' ?>>AB-</option>
                                    <option value="O+" <?= (isset($_POST['blood_type']) && $_POST['blood_type'] === 'O+') ? 'selected' : '' ?>>O+</option>
                                    <option value="O-" <?= (isset($_POST['blood_type']) && $_POST['blood_type'] === 'O-') ? 'selected' : '' ?>>O-</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Address Information Section -->
                    <div class="tab-content hidden" id="content-address">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Address Information</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Address Type -->
                            <div>
                                <label for="address_type" class="block text-sm font-medium text-gray-700 mb-2">
                                    Address Type
                                </label>
                                <select name="address_type" id="address_type"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="Permanent" <?= (isset($_POST['address_type']) && $_POST['address_type'] === 'Permanent') ? 'selected' : 'selected' ?>>Permanent</option>
                                    <option value="Present" <?= (isset($_POST['address_type']) && $_POST['address_type'] === 'Present') ? 'selected' : '' ?>>Present</option>
                                    <option value="Work" <?= (isset($_POST['address_type']) && $_POST['address_type'] === 'Work') ? 'selected' : '' ?>>Work</option>
                                    <option value="Other" <?= (isset($_POST['address_type']) && $_POST['address_type'] === 'Other') ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>

                            <!-- House Number -->
                            <div>
                                <label for="house_number" class="block text-sm font-medium text-gray-700 mb-2">
                                    House Number
                                </label>
                                <input type="text" name="house_number" id="house_number"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter house number"
                                    value="<?= htmlspecialchars($_POST['house_number'] ?? '') ?>">
                            </div>

                            <!-- Building Name -->
                            <div>
                                <label for="building_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Building Name
                                </label>
                                <input type="text" name="building_name" id="building_name"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter building name"
                                    value="<?= htmlspecialchars($_POST['building_name'] ?? '') ?>">
                            </div>

                            <!-- Street Name -->
                            <div>
                                <label for="street_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Street Name
                                </label>
                                <input type="text" name="street_name" id="street_name"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter street name"
                                    value="<?= htmlspecialchars($_POST['street_name'] ?? '') ?>">
                            </div>

                            <!-- Subdivision/Village -->
                            <div>
                                <label for="subdivision_village" class="block text-sm font-medium text-gray-700 mb-2">
                                    Subdivision/Village
                                </label>
                                <input type="text" name="subdivision_village" id="subdivision_village"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter subdivision or village"
                                    value="<?= htmlspecialchars($_POST['subdivision_village'] ?? '') ?>">
                            </div>

                            <!-- Purok -->
                            <div>
                                <label for="purok" class="block text-sm font-medium text-gray-700 mb-2">
                                    Purok
                                </label>
                                <input type="text" name="purok" id="purok"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter purok"
                                    value="<?= htmlspecialchars($_POST['purok'] ?? '') ?>">
                            </div>

                            <!-- Sitio -->
                            <div>
                                <label for="sitio" class="block text-sm font-medium text-gray-700 mb-2">
                                    Sitio
                                </label>
                                <input type="text" name="sitio" id="sitio"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter sitio"
                                    value="<?= htmlspecialchars($_POST['sitio'] ?? '') ?>">
                            </div>

                            <!-- Barangay -->
                            <div>
                                <label for="barangay" class="block text-sm font-medium text-gray-700 mb-2">
                                    Barangay <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="barangay" id="barangay" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter barangay"
                                    value="<?= htmlspecialchars($_POST['barangay'] ?? '') ?>">
                            </div>

                            <!-- District -->
                            <div>
                                <label for="district" class="block text-sm font-medium text-gray-700 mb-2">
                                    District
                                </label>
                                <input type="text" name="district" id="district"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter district"
                                    value="<?= htmlspecialchars($_POST['district'] ?? '') ?>">
                            </div>

                            <!-- Municipality/City -->
                            <div>
                                <label for="municipality_city" class="block text-sm font-medium text-gray-700 mb-2">
                                    Municipality/City <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="municipality_city" id="municipality_city" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter municipality or city"
                                    value="<?= htmlspecialchars($_POST['municipality_city'] ?? '') ?>">
                            </div>

                            <!-- Province -->
                            <div>
                                <label for="province" class="block text-sm font-medium text-gray-700 mb-2">
                                    Province <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="province" id="province" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter province"
                                    value="<?= htmlspecialchars($_POST['province'] ?? '') ?>">
                            </div>

                            <!-- Region -->
                            <div>
                                <label for="region" class="block text-sm font-medium text-gray-700 mb-2">
                                    Region
                                </label>
                                <input type="text" name="region" id="region"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter region"
                                    value="<?= htmlspecialchars($_POST['region'] ?? '') ?>">
                            </div>

                            <!-- Postal Code -->
                            <div>
                                <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2">
                                    Postal Code
                                </label>
                                <input type="text" name="postal_code" id="postal_code"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter postal code"
                                    value="<?= htmlspecialchars($_POST['postal_code'] ?? '') ?>">
                            </div>

                            <!-- Months of Residency -->
                            <div>
                                <label for="months_of_residency" class="block text-sm font-medium text-gray-700 mb-2">
                                    Months of Residency
                                </label>
                                <input type="number" name="months_of_residency" id="months_of_residency" min="0"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter months"
                                    value="<?= htmlspecialchars($_POST['months_of_residency'] ?? '') ?>">
                            </div>

                            <!-- Is Owner -->
                            <div>
                                <label for="is_owner" class="block text-sm font-medium text-gray-700 mb-2">
                                    Property Owner
                                </label>
                                <select name="is_owner" id="is_owner"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="0" <?= (isset($_POST['is_owner']) && $_POST['is_owner'] == '0') ? 'selected' : 'selected' ?>>No</option>
                                    <option value="1" <?= (isset($_POST['is_owner']) && $_POST['is_owner'] == '1') ? 'selected' : '' ?>>Yes</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Resident Status Section -->
                    <div class="tab-content hidden" id="content-status">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Resident Status</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Status Type -->
                            <div>
                                <label for="status_type" class="block text-sm font-medium text-gray-700 mb-2">
                                    Status Type
                                </label>
                                <select name="status_type" id="status_type"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Status Type</option>
                                    <option value="Senior Citizen" <?= (isset($_POST['status_type']) && $_POST['status_type'] === 'Senior Citizen') ? 'selected' : '' ?>>Senior Citizen</option>
                                    <option value="PWD" <?= (isset($_POST['status_type']) && $_POST['status_type'] === 'PWD') ? 'selected' : '' ?>>PWD</option>
                                    <option value="Solo Parent" <?= (isset($_POST['status_type']) && $_POST['status_type'] === 'Solo Parent') ? 'selected' : '' ?>>Solo Parent</option>
                                    <option value="Indigent" <?= (isset($_POST['status_type']) && $_POST['status_type'] === 'Indigent') ? 'selected' : '' ?>>Indigent</option>
                                    <option value="Other" <?= (isset($_POST['status_type']) && $_POST['status_type'] === 'Other') ? 'selected' : '' ?>>Other</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Select the type of resident status (e.g., Senior Citizen, PWD, etc.)</p>
                            </div>

                            <!-- Life Status (is_active) -->
                            <div>
                                <label for="is_active" class="block text-sm font-medium text-gray-700 mb-2">
                                    Life Status <span class="text-red-500">*</span>
                                </label>
                                <select name="is_active" id="is_active" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="1" <?= (!isset($_POST['is_active']) || (isset($_POST['is_active']) && $_POST['is_active'] == '1')) ? 'selected' : '' ?>>Alive</option>
                                    <option value="0" <?= (isset($_POST['is_active']) && $_POST['is_active'] == '0') ? 'selected' : '' ?>>Deceased</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Indicate if the resident is alive or deceased</p>
                            </div>
                        </div>
                    </div>

                    <!-- Documents Section -->
                    <div class="tab-content hidden" id="content-documents">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Photos</h2>
                        
                        <div class="space-y-6">
                            <!-- Resident Photos (3 photos) -->
                            <div>
                                <h3 class="text-md font-medium text-gray-700 mb-4">Resident Photos</h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <?php for ($i = 1; $i <= 3; $i++): ?>
                                    <div>
                                        <label for="photo_<?= $i ?>" class="block text-sm font-medium text-gray-700 mb-2">
                                            Photo <?= $i ?>
                                        </label>
                                        <div class="space-y-2">
                                            <div class="flex justify-center">
                                                <img id="photo_<?= $i ?>_preview" class="h-32 w-32 object-cover border-2 border-gray-300 rounded-lg" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='128' height='128'%3E%3Crect width='128' height='128' fill='%23e5e7eb'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' fill='%239ca3af' font-size='14'%3ENo Image%3C/text%3E%3C/svg%3E" alt="Photo <?= $i ?> preview">
                                            </div>
                                            <input type="file" 
                                                name="photo_<?= $i ?>" 
                                                id="photo_<?= $i ?>" 
                                                accept="image/*"
                                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                                onchange="previewImage(this, 'photo_<?= $i ?>_preview')">
                                            <p class="text-xs text-gray-500 text-center">JPG, PNG or GIF. Max 5MB</p>
                                        </div>
                                    </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-between items-center pt-4 border-t border-gray-200 mt-6">
                        <a href="../residents.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                            Cancel
                        </a>
                        <div class="flex space-x-3">
                            <button type="button" id="prevBtn" class="hidden px-6 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                                <span class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                    Previous
                                </span>
                            </button>
                            <button type="button" id="nextBtn" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-md">
                                <span class="flex items-center">
                                    Next
                                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </span>
                            </button>
                            <button type="submit" id="submitBtn" class="hidden px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-md">
                                Register Resident
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </main>
    </div>

    <!-- JavaScript for Sidebar Toggle and Tab Navigation -->
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

        // --- Tab Navigation Logic ---
        let currentTab = 0;
        const tabs = ['personal', 'address', 'status', 'documents'];
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const submitBtn = document.getElementById('submitBtn');

        // Tab button click handlers
        tabButtons.forEach((button, index) => {
            button.addEventListener('click', () => {
                if (index <= currentTab) {
                    showTab(index);
                }
            });
        });

        // Previous button
        prevBtn.addEventListener('click', () => {
            if (currentTab > 0) {
                showTab(currentTab - 1);
            }
        });

        // Next button
        nextBtn.addEventListener('click', () => {
            if (validateCurrentTab()) {
                if (currentTab < tabs.length - 1) {
                    showTab(currentTab + 1);
                }
            }
        });

        function showTab(index) {
            currentTab = index;

            // Hide all tab contents
            tabContents.forEach(content => {
                content.classList.add('hidden');
            });

            // Show current tab content
            document.getElementById(`content-${tabs[index]}`).classList.remove('hidden');

            // Update tab buttons
            tabButtons.forEach((btn, i) => {
                if (i === index) {
                    btn.classList.add('active', 'border-blue-500', 'text-blue-600');
                    btn.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                } else if (i < index) {
                    btn.classList.add('border-green-500', 'text-green-600');
                    btn.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300', 'active');
                } else {
                    btn.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                    btn.classList.remove('active', 'border-blue-500', 'text-blue-600', 'border-green-500', 'text-green-600');
                }
            });

            // Update navigation buttons
            if (currentTab === 0) {
                prevBtn.classList.add('hidden');
            } else {
                prevBtn.classList.remove('hidden');
            }

            if (currentTab === tabs.length - 1) {
                nextBtn.classList.add('hidden');
                submitBtn.classList.remove('hidden');
            } else {
                nextBtn.classList.remove('hidden');
                submitBtn.classList.add('hidden');
            }
        }

        function validateCurrentTab() {
            const currentTabContent = document.getElementById(`content-${tabs[currentTab]}`);
            const requiredFields = currentTabContent.querySelectorAll('[required]');
            let isValid = true;
            let firstInvalidField = null;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('border-red-500');
                    field.classList.remove('border-gray-300');
                    
                    // Add error message if not exists
                    if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('error-message')) {
                        const errorMsg = document.createElement('p');
                        errorMsg.className = 'error-message text-red-500 text-xs mt-1';
                        errorMsg.textContent = 'This field is required';
                        field.parentNode.appendChild(errorMsg);
                    }

                    if (!firstInvalidField) {
                        firstInvalidField = field;
                    }
                } else {
                    field.classList.remove('border-red-500');
                    field.classList.add('border-gray-300');
                    
                    // Remove error message if exists
                    const errorMsg = field.parentNode.querySelector('.error-message');
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                }
            });

            // Scroll to first invalid field
            if (!isValid && firstInvalidField) {
                firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalidField.focus();
            }

            return isValid;
        }

        // Remove error styling on input
        document.querySelectorAll('input, select').forEach(field => {
            field.addEventListener('input', function() {
                if (this.classList.contains('border-red-500')) {
                    this.classList.remove('border-red-500');
                    this.classList.add('border-gray-300');
                    const errorMsg = this.parentNode.querySelector('.error-message');
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                }
            });
        });

        // Form submission handler
        const form = document.getElementById('residentForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Validate all tabs before submission
                let allTabsValid = true;
                for (let i = 0; i < tabs.length; i++) {
                    const tabContent = document.getElementById(`content-${tabs[i]}`);
                    const requiredFields = tabContent.querySelectorAll('[required]');
                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            allTabsValid = false;
                            field.classList.add('border-red-500');
                            field.classList.remove('border-gray-300');
                        }
                    });
                }
                
                if (!allTabsValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields in all tabs before submitting.');
                    // Go to first tab with error
                    for (let i = 0; i < tabs.length; i++) {
                        const tabContent = document.getElementById(`content-${tabs[i]}`);
                        const requiredFields = tabContent.querySelectorAll('[required]');
                        const hasError = Array.from(requiredFields).some(field => !field.value.trim());
                        if (hasError) {
                            showTab(i);
                            break;
                        }
                    }
                    return false;
                }
            });
        }

        // Initialize first tab
        showTab(0);

        // Image preview function
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.src = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80'%3E%3Crect width='80' height='80' fill='%23e5e7eb'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' fill='%239ca3af' font-size='12'%3ENo Image%3C/text%3E%3C/svg%3E";
            }
        }
    </script>

    <style>
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
    </style>
</body>
</html>

