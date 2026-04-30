<?php
require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/helpers.php";

// If already authenticated, redirect to dashboard
if (AuthController::check()) {
    header("Location: " . BASE_URL . "/admin/dashboard.php");
    exit;
}

$error = '';
$message = null;

if (isset($_GET['error'])) {
    if ($_GET['error'] === 'maintenance') {
        $error = "The system is currently under maintenance.";
    } elseif ($_GET['error'] === 'unauthorized') {
        $error = "You are not authorized to access that page.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $authController = new AuthController();
        $result = $authController->login($username, $password);
        
        if ($result['success']) {
            header("Location: " . BASE_URL . "/admin/dashboard.php");
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Attendance System</title>

    <!-- Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom Config to match system design -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af', // Sidebar color match
                            900: '#1e3a8a',
                        },
                        secondary: {
                            400: '#facc15', // Active link accent
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7f9fc;
        }
    </style>
</head>

<body class="text-gray-800 antialiased flex flex-col min-h-screen">

    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="flex-shrink-0 flex items-center gap-3">
                        <!-- Logo -->
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white shadow-md">
                            <img src="<?= BASE_URL ?>/utils/img/logo.png" alt="Logo">
                        </div>
                        <div class="flex flex-col justify-center">
                            <span class="font-bold text-lg leading-tight text-gray-900">Attendance System</span>
                            <span class="text-xs font-semibold tracking-wide text-primary-600 uppercase">Management
                                Information System</span>
                        </div>
                    </a>
                </div>

                <!-- Right Side Actions -->
                <div class="hidden md:flex items-center space-x-4">
                    <a href="../../index.php" class="text-gray-500 hover:text-primary-800 font-medium transition-colors">Home</a>
                    <a href="#" class="text-gray-500 hover:text-primary-800 font-medium transition-colors">Help</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div
        class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gray-50 relative overflow-hidden">

        <!-- Background Elements -->
        <div class="absolute inset-0 z-0 opacity-30">
            <div class="absolute top-0 right-0 -mr-20 -mt-20 w-96 h-96 rounded-full bg-primary-100 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 rounded-full bg-blue-50 blur-3xl"></div>
        </div>

        <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-2xl shadow-xl border border-gray-100 relative z-10">
            <div class="text-center">
                <h2 class="mt-2 text-3xl font-extrabold text-gray-900 tracking-tight">
                    Welcome Back
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Sign in to your account
                </p>
            </div>

            <form class="mt-8 space-y-6" action="" method="POST">
                <?php if (!empty($error)): ?>
                    <div class="rounded-lg bg-red-50 p-4 border border-red-100">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">
                                    <?php echo htmlspecialchars($error); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="space-y-4">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                        <div class="mt-1">
                            <input id="username" name="username" type="text" autocomplete="username" required
                                class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary-600 focus:border-primary-600 sm:text-sm transition-colors"
                                placeholder="Enter your username">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <div class="mt-1">
                            <input id="password" name="password" type="password" autocomplete="current-password"
                                required
                                class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary-600 focus:border-primary-600 sm:text-sm transition-colors"
                                placeholder="Enter your password">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember-me" name="remember-me" type="checkbox"
                            class="h-4 w-4 text-primary-600 focus:ring-primary-600 border-gray-300 rounded">
                        <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                            Remember me
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="#" class="font-medium text-primary-600 hover:text-primary-800 transition-colors">
                            Forgot password?
                        </a>
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-md text-sm font-medium text-white bg-primary-800 hover:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-600 transition-all duration-200 transform hover:-translate-y-0.5">
                        Sign in
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-auto">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="md:flex md:items-center md:justify-between">
                <div class="flex justify-center md:justify-start">
                    <p class="text-base text-gray-400">
                        &copy; <?= date('Y') ?> Attendance System. All rights reserved.
                    </p>
                </div>
                <div class="mt-4 flex justify-center md:mt-0 md:justify-end space-x-6">
                    <a href="#" class="text-gray-400 hover:text-gray-500">
                        Privacy Policy
                    </a>
                    <a href="#" class="text-gray-400 hover:text-gray-500">
                        Terms of Service
                    </a>
                </div>
            </div>
        </div>
    </footer>

</body>

</html>