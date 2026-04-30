<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (isset($_SESSION['user_id'])) {
    $role = strtolower(trim($_SESSION['role'] ?? ''));
    if ($role === 'treasurer' || $role === 'admin') {
        header("Location: treasurer/dashboard.php");
        exit;
    }
}

if (isset($_SESSION['resident_id'])) {
    header("Location: resident/pending_payments.php");
    exit;
}

include "config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {

        // MD5 password check
        if (md5($password) === $user['password']) {

            // role-based access: only treasurer/admin can log in here
            $role = strtolower(trim($user['role'] ?? ''));
            if ($role === 'treasurer' || $role === 'admin') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $role;

                header("Location: treasurer/dashboard.php");
                exit;
            }

            $error = "This account is not authorized for treasurer login.";
        } else {
            $error = "Invalid password";
        }

    } else {
        $error = "User not found";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Sto. Rosario - Treasurer System Login</title>
    <link rel="icon" type="image/x-icon" href="assets/images/logo.jpg">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(to bottom, #f0f4f8 0%, #d9e6f2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
            padding: 0;
        }

        .header-banner {
            animation: slideDown 0.6s ease-out;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Main content wrapper for side-by-side layout */
        .main-content-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 50px;
            padding: 60px 20px;
            flex: 1;
            animation: fadeIn 0.8s ease-out 0.2s both;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        /* Logo container - compact for side placement */
        .logo-container {
            background: var(--white);
            padding: 40px 35px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(30, 58, 95, 0.15);
            text-align: center;
            max-width: 350px;
            border: 1px solid rgba(31, 58, 147, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .logo-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(30, 58, 95, 0.2);
        }

        .logo-wrapper {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        .logo-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 45%;
            display: block;
            overflow: hidden;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .branding h2 {
            font-size: 18px;
            color: var(--text-light);
            font-weight: 400;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .branding h1 {
            font-size: 36px;
            color: var(--primary-blue);
            font-weight: 700;
            letter-spacing: 3px;
            border-bottom: 4px solid #1F3A93;
            display: inline-block;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .branding p {
            font-size: 14px;
            color: var(--text-light);
            margin-top: 10px;
            font-weight: 500;
        }

        .login-container {
            animation: fadeInUp 1s ease-out 0.4s both;
            box-shadow: 0 8px 32px rgba(30, 58, 95, 0.15);
            max-width: 450px;
            width: 100%;
            border: 1px solid rgba(31, 58, 147, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(30, 58, 95, 0.2);
        }

        .login-container h3 {
            font-size: 22px;
            margin-bottom: 25px;
            color: var(--primary-blue);
        }

        .btn-primary:active {
            transform: scale(0.98);
        }

        .form-group input:focus {
            transform: scale(1.01);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive - stack vertically on smaller screens */
        @media (max-width: 968px) {
            .main-content-wrapper {
                flex-direction: column;
                gap: 30px;
                padding: 30px 20px;
            }

            .logo-container {
                max-width: 420px;
                width: 100%;
            }

            .branding h1 {
                font-size: 28px;
            }

            .branding h2 {
                font-size: 18px;
            }
        }

        @media (max-width: 480px) {
            .logo-wrapper {
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }

            .branding h1 {
                font-size: 24px;
            }

            .login-container h3 {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>
    <!-- Header Banner -->
    <div class="header-banner">
        <h1>TREASURER MANAGEMENT SYSTEM</h1>
    </div>

    <!-- Main Content Wrapper -->
    <div class="main-content-wrapper">
        <!-- Logo and Branding Section -->
        <div class="logo-container">
            <div class="logo-wrapper">
                <img src="assets/images/logo.jpg" alt="Barangay Logo" class="logo-img">
            </div>
            <div class="branding">
                <h2>Barangay</h2>
                <h1>STO. ROSARIO</h1>
                <p>Magallanes, Agusan del Norte</p>
            </div>
        </div>

        <!-- Login Form -->
        <div class="login-container">
            <h3><i class="fas fa-sign-in-alt"></i> LOGIN TO YOUR ACCOUNT</h3>

            <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required
                        autocomplete="username" autofocus>
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required
                            autocomplete="current-password">
                        <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> LOGIN
                </button>
            </form>
            <div style="margin-top: 15px; text-align: center;">
                <a href="forgot_password.php">Forgot password?</a>
            </div>
            <div style="margin-top: 8px; text-align: center;">
                <!--  <a href="register.php">Create an account</a>-->
            </div>
            <div style="margin-top: 8px; text-align: center;">
                <a href="resident/login.php">Login as resident</a>
            </div>
            <div style="margin-top: 8px; text-align: center;">
                <a href="resend_verification.php">Resend verification email</a>
            </div>
            <div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid rgba(31, 58, 147, 0.1); text-align: center;">
                <a href="index.php" style="color: var(--primary-blue); font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2025 Barangay Sto. Rosario, Magallanes, Agusan del Norte</p>
        <p>Treasurer Management System | All Rights Reserved</p>
    </div>

    <script src="assets/js/password-toggle.js"></script>
</body>

</html>