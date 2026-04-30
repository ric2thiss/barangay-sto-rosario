<?php
/**
 * Payroll Password Confirmation Page
 * 
 * This page must be accessed BEFORE payroll.php to verify the admin's identity.
 * After successful password confirmation, redirects to payroll.php.
 */

require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/../auth/helpers.php";
requireAuth(); // Require authentication - redirects to login if not authenticated

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if password is already confirmed (within idle timeout)
$idleTimeout = 240; // 4 minutes in seconds
$passwordConfirmed = isset($_SESSION['payroll_password_confirmed']) && $_SESSION['payroll_password_confirmed'] === true;

if ($passwordConfirmed) {
    // Check if confirmation has expired due to idle time
    $lastActivity = $_SESSION['payroll_last_activity'] ?? $_SESSION['payroll_confirmed_at'] ?? 0;
    $timeSinceActivity = time() - $lastActivity;
    
    if ($timeSinceActivity <= $idleTimeout) {
        // Still valid - redirect to payroll.php
        header("Location: payroll.php");
        exit;
    } else {
        // Expired - require re-confirmation
        unset($_SESSION['payroll_password_confirmed']);
        unset($_SESSION['payroll_confirmed_at']);
        unset($_SESSION['payroll_last_activity']);
    }
}

// Handle password submission
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $password = $_POST['password'] ?? '';
    
    if (empty($password)) {
        $error = 'Password is required.';
    } else {
        // Get current user
        $user = currentUser();
        if (!$user || !isset($user['id'])) {
            $error = 'User session not found. Please login again.';
        } else {
            try {
                // Get admin record from database
                $db = (new Database())->connect();
                $adminRepository = new AdminRepository($db);
                $admin = $adminRepository->findById($user['id']);

                if (!$admin) {
                    $error = 'Admin account not found.';
                } else {
                    // Convert object to array if needed
                    if (is_object($admin)) {
                        $admin = json_decode(json_encode($admin), true);
                    }

                    // Verify password
                    if (!$adminRepository->verifyPassword($password, $admin['password'])) {
                        $error = 'Invalid password. Please try again.';
                    } else {
                        // Check if admin is still active
                        if (isset($admin['is_active']) && !$admin['is_active']) {
                            $error = 'Account is deactivated. Please contact administrator.';
                        } else {
                            // Password verified successfully - set session confirmation
                            $_SESSION['payroll_password_confirmed'] = true;
                            $_SESSION['payroll_confirmed_at'] = time();
                            $_SESSION['payroll_last_activity'] = time();
                            
                            // Redirect to payroll.php
                            header("Location: payroll.php");
                            exit;
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Password verification error: " . $e->getMessage());
                $error = 'An error occurred during password verification. Please try again.';
            }
        }
    }
}

// Get current user for display
$currentUser = currentUser();
$userName = $currentUser ? ($currentUser['full_name'] ?? $currentUser['username']) : 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Confirmation Required - Payroll Management</title>
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Use Inter font family -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7f9fc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-brand {
            background-color: #374151;
        }
        .btn-brand:hover {
            background-color: #1f2937;
        }
    </style>
</head>
<body>
    <div class="w-full max-w-md mx-4">
        <div class="bg-white rounded-xl shadow-2xl p-8 border border-gray-100">
            <!-- Logo & Header -->
            <div class="text-center mb-8">
                <div class="flex items-center justify-center space-x-3 mb-4">
                    <img src="../utils/img/logo.png" alt="Logo" class="w-12 h-12 object-contain">
                    <h1 class="text-2xl font-bold text-gray-800">Attendance System</h1>
                </div>
                
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-50 mb-4">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Payroll Security Check</h2>
                <p class="text-sm text-gray-600">
                    Please confirm your password to access <strong>Payroll Management</strong>
                </p>
            </div>

            <!-- User Info -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6 border border-gray-100">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 mr-3">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-semibold">Confirming as</p>
                        <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($userName) ?></p>
                    </div>
                </div>
            </div>

            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm flex items-start">
                    <svg class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Password Form -->
            <form method="POST" action="" id="passwordForm" class="space-y-5">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Admin Password
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <input 
                            type="password" 
                            id="password" 
                            name="password"
                            required
                            autocomplete="current-password"
                            autofocus
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                            placeholder="••••••••"
                        />
                    </div>
                </div>

                <button 
                    type="submit" 
                    id="submitButton"
                    class="w-full btn-brand text-white font-semibold py-3 px-4 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed shadow-md"
                >
                    <span id="buttonText">Unlock Payroll</span>
                    <span id="buttonSpinner" class="hidden inline-block ml-2">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                </button>
            </form>

            <!-- Back Link -->
            <div class="mt-8 text-center">
                <a href="dashboard.php" class="text-sm font-medium text-gray-500 hover:text-gray-800 transition-colors flex items-center justify-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Dashboard
                </a>
            </div>
        </div>
        
        <!-- Footer Info -->
        <p class="mt-8 text-center text-xs text-gray-400">
            For security purposes, you must re-authenticate to access sensitive payroll data.<br>
            Session expires after 4 minutes of inactivity.
        </p>
    </div>

    <script>
        // Handle form submission with loading state
        const form = document.getElementById('passwordForm');
        const submitButton = document.getElementById('submitButton');
        const buttonText = document.getElementById('buttonText');
        const buttonSpinner = document.getElementById('buttonSpinner');
        const passwordInput = document.getElementById('password');

        form.addEventListener('submit', function(e) {
            // Show loading state
            submitButton.disabled = true;
            buttonText.classList.add('hidden');
            buttonSpinner.classList.remove('hidden');
        });

        // Focus password input on load
        passwordInput.focus();
    </script>
</body>
</html>
