<?php
$employeeId = $_GET['employee_id'] ?? null;
$residentId = $_GET['resident_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biometric Registration Success - Attendance System</title>
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Use Inter font family and custom styles -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7f9fc;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Success Card -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8 text-center">
            <!-- Success Icon -->
            <div class="mb-6 flex justify-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>

            <!-- Success Message -->
            <h1 class="text-2xl font-semibold text-gray-800 mb-2">Biometric Registered Successfully</h1>
            <p class="text-gray-500 mb-6">Your biometric data has been securely registered in the system.</p>

            <!-- ID Information Card -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6 border border-gray-200">
                <?php if ($employeeId): ?>
                    <div class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span class="text-sm text-gray-600">Employee ID:</span>
                        <span class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($employeeId) ?></span>
                    </div>
                <?php elseif ($residentId): ?>
                    <div class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span class="text-sm text-gray-600">Resident ID:</span>
                        <span class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($residentId) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- System Branding -->
            <div class="pt-6 border-t border-gray-200">
                <div class="flex items-center justify-center space-x-2 mb-2">
                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-sm">A</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-700">Attendance System</span>
                </div>
                <p class="text-xs text-gray-400">Your biometric data is now active</p>
            </div>
        </div>

        <!-- Additional Info Card (Optional) -->
        <div class="mt-4 bg-blue-50 rounded-lg p-4 border border-blue-200">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="text-sm text-blue-800">
                    <p class="font-medium mb-1">What's Next?</p>
                    <p class="text-blue-700">You can now use your biometric data for verification.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>