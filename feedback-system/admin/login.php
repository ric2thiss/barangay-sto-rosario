<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
// Check if already logged in
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'admin') {
    header('Location: index.php');
    exit();
}

// Create login_logs table if it doesn't exist
$createTable = "CREATE TABLE IF NOT EXISTS `login_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `admin_id` int(11) NOT NULL,
    `name` varchar(100) NOT NULL,
    `ip_address` varchar(45) NOT NULL,
    `time_in` datetime NOT NULL,
    `time_out` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `admin_id` (`admin_id`),
    KEY `time_in` (`time_in`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
$conn->query($createTable);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Initialize login attempts in session if not set
    if (!isset($_SESSION['admin_login_attempts'])) {
        $_SESSION['admin_login_attempts'] = 0;
        $_SESSION['admin_lockout_time'] = null;
        $_SESSION['admin_lockout_round'] = 0;
    }

    $max_attempts = 3;

    // Check if admin is locked out
    if ($_SESSION['admin_lockout_time'] !== null) {
        $time_remaining = $_SESSION['admin_lockout_time'] - time();
        if ($time_remaining > 0) {
            $error = "Too many failed attempts. Please wait before trying again.";
            $lockout_remaining = $time_remaining;
        } else {
            // Lockout expired, reset attempts
            $_SESSION['admin_login_attempts'] = 0;
            $_SESSION['admin_lockout_time'] = null;
        }
    }

    // Validate inputs
    if (!isset($error) && (empty($username) || empty($password))) {
        $error = "Please enter both username and password";
    }

    // Proceed with login if not locked out and inputs are valid
    if (!isset($error)) {
        $sql = "SELECT * FROM admins WHERE (username = ? OR email = ?) AND user_type IN ('admin', 'superadmin')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                // Reset all login attempts on successful login
                $_SESSION['admin_login_attempts'] = 0;
                $_SESSION['admin_lockout_time'] = null;
                $_SESSION['admin_lockout_round'] = 0;

                // Check if password needs rehash
                if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $updateStmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
                    $updateStmt->bind_param("si", $newHash, $user['id']);
                    $updateStmt->execute();
                    $updateStmt->close();
                }

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['firstname'] = $user['firstname'];
                $_SESSION['lastname'] = $user['lastname'];
                $_SESSION['fullname'] = $user['firstname'] . ' ' . $user['lastname'];
                $_SESSION['login_time'] = time();

                // Log the login
                $fullname = $user['firstname'] . ' ' . $user['lastname'];
                $ip_address = $_SERVER['REMOTE_ADDR'];
                if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
                } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                    $ip_address = $_SERVER['HTTP_CLIENT_IP'];
                }

                $logStmt = $conn->prepare("INSERT INTO login_logs (admin_id, name, ip_address, time_in) VALUES (?, ?, ?, NOW())");
                $logStmt->bind_param("iss", $user['id'], $fullname, $ip_address);
                $logStmt->execute();
                $_SESSION['login_log_id'] = $conn->insert_id;
                $logStmt->close();

                logAdminActivity($conn, $user['id'], 'Login', 'Admin logged into the system.');

                header('Location: index.php');
                exit();
            } else {
                $_SESSION['admin_login_attempts']++;
                $attempts_left = $max_attempts - $_SESSION['admin_login_attempts'];

                if ($attempts_left <= 0) {
                    $_SESSION['admin_lockout_round']++;
                    $lockout_duration = 30 * $_SESSION['admin_lockout_round'];
                    $_SESSION['admin_lockout_time'] = time() + $lockout_duration;
                    $lockout_remaining = $lockout_duration;
                    $error = "Too many failed attempts. Please wait before trying again.";
                } else {
                    $error = "Invalid username or password. {$attempts_left} attempt(s) left.";
                }
            }
        } else {
            $_SESSION['admin_login_attempts']++;
            $attempts_left = $max_attempts - $_SESSION['admin_login_attempts'];

            if ($attempts_left <= 0) {
                $_SESSION['admin_lockout_round']++;
                $lockout_duration = 30 * $_SESSION['admin_lockout_round'];
                $_SESSION['admin_lockout_time'] = time() + $lockout_duration;
                $lockout_remaining = $lockout_duration;
                $error = "Too many failed attempts. Please wait before trying again.";
            } else {
                $error = "Invalid username or password. {$attempts_left} attempt(s) left.";
            }
        }

        $stmt->close();
    }
}

// Check lockout on page load
$lockout_remaining = 0;
if (isset($_SESSION['admin_lockout_time']) && $_SESSION['admin_lockout_time'] !== null) {
    $time_remaining = $_SESSION['admin_lockout_time'] - time();
    if ($time_remaining > 0) {
        $lockout_remaining = $time_remaining;
    } else {
        $_SESSION['admin_login_attempts'] = 0;
        $_SESSION['admin_lockout_time'] = null;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Resident Feedback and Survey System</title>
    <link rel="icon" href="../img/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #1F3A93 0%, #152c71 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.3;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            z-index: 1;
            animation: fadeInUp 0.6s ease-out;
        }

        .login-box {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            width: 100%;
            border: 1px solid rgba(31, 58, 147, 0.1);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .login-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.25);
        }

        .login-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #1F3A93, #3a56b5);
        }

        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .login-icon {
            font-size: 3.5rem;
            color: #1F3A93;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #f0f7ff 0%, #bae6fd 100%);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border: 2px solid rgba(31, 58, 147, 0.2);
            box-shadow: 0 5px 15px rgba(31, 58, 147, 0.1);
            overflow: hidden;
        }

        .login-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .login-header h2 {
            color: #1a317d;
            margin-bottom: 10px;
            font-size: 2rem;
            font-weight: 600;
        }

        .login-header p {
            color: #6b7280;
            font-size: 1rem;
            font-weight: 500;
        }

        .alert-error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border: 1px solid #fca5a5;
            color: #991b1b;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s ease-in-out;
        }

        .alert-error i {
            font-size: 1.2rem;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #1a317d;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 15px;
            border: 2px solid #bae6fd;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f9fafb;
            color: #1a317d;
            font-weight: 500;
        }

        .form-control:focus {
            outline: none;
            border-color: #1F3A93;
            background: white;
            box-shadow: 0 0 0 3px rgba(31, 58, 147, 0.15);
        }

        .form-control::placeholder {
            color: #9ca3af;
            font-weight: normal;
        }

        .btn {
            background: linear-gradient(135deg, #1F3A93 0%, #152c71 100%);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(31, 58, 147, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .demo-credentials {
            background: linear-gradient(135deg, #f0f7ff 0%, #bae6fd 100%);
            padding: 20px;
            border-radius: 12px;
            margin-top: 25px;
            border: 1px solid #bae6fd;
            border-left: 4px solid #1F3A93;
        }

        .demo-credentials p {
            margin-bottom: 8px;
            color: #1a317d;
        }

        .demo-credentials strong {
            color: #1a317d;
            font-weight: 700;
        }

        .demo-credentials code {
            background: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            color: #1F3A93;
            font-weight: 600;
            border: 1px solid #bae6fd;
            box-shadow: 0 2px 5px rgba(31, 58, 147, 0.1);
        }

        .security-note {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #92400e;
            border: 1px solid #fbbf24;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .security-note i {
            color: #d97706;
            font-size: 1.1rem;
            margin-top: 2px;
        }

        .back-to-home {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e5e7eb;
        }

        .back-to-home a {
            color: #1F3A93;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            padding: 8px 16px;
            border-radius: 8px;
            background: #f0f7ff;
        }

        .back-to-home a:hover {
            color: #152c71;
            background: #bae6fd;
            text-decoration: none;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .login-box {
                padding: 30px;
            }

            .login-icon {
                width: 70px;
                height: 70px;
                font-size: 3rem;
            }

            .login-header h2 {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 15px;
            }

            .login-box {
                padding: 25px 20px;
            }

            .login-header h2 {
                font-size: 1.6rem;
            }

            .login-icon {
                width: 60px;
                height: 60px;
                font-size: 2.5rem;
            }

            .form-control {
                padding: 12px;
            }

            .btn {
                padding: 14px;
            }

            .security-note {
                font-size: 0.85rem;
                padding: 12px;
            }
        }

        /* Animations */
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

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            10%,
            30%,
            50%,
            70%,
            90% {
                transform: translateX(-5px);
            }

            20%,
            40%,
            60%,
            80% {
                transform: translateX(5px);
            }
        }

        /* Ripple effect */
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple 0.6s linear;
        }

        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }

        /* Glow effect for admin inputs */
        .form-group input:focus {
            animation: glow 1.5s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from {
                box-shadow: 0 0 5px rgba(31, 58, 147, 0.2),
                    0 0 10px rgba(31, 58, 147, 0.1),
                    0 0 15px rgba(31, 58, 147, 0.1);
            }

            to {
                box-shadow: 0 0 10px rgba(31, 58, 147, 0.4),
                    0 0 20px rgba(31, 58, 147, 0.2),
                    0 0 30px rgba(31, 58, 147, 0.1);
            }
        }

        /* Admin badge effect */
        .login-header::after {
            content: 'SECURE ADMIN ACCESS';
            display: block;
            margin-top: 10px;
            font-size: 0.75rem;
            font-weight: 700;
            color: #1F3A93;
            letter-spacing: 1px;
            text-transform: uppercase;
            background: #f0f7ff;
            padding: 4px 12px;
            border-radius: 20px;
            border: 1px solid #bae6fd;
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="login-icon">
                    <img src="../img/logo.png" alt="Logo">
                </div>
                <h2>Admin Login</h2>
                <p>Administrator Access Only</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert-error" id="errorAlert">
                    <i class="fas fa-exclamation-circle"></i>
                    <span id="errorMessage"><?php echo $error; ?></span>
                    <?php if ($lockout_remaining > 0): ?>
                        <span id="countdownTimer" style="font-weight: bold;"></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="username">Admin Username:</label>
                    <input type="text" class="form-control" id="username" name="username"
                        placeholder="Enter admin username" value="" required <?php echo $lockout_remaining > 0 ? 'disabled' : ''; ?>>
                </div>

                <div class="form-group">
                    <label for="password">Admin Password:</label>
                    <input type="password" class="form-control" id="password" name="password"
                        placeholder="Enter admin password" value="" required <?php echo $lockout_remaining > 0 ? 'disabled' : ''; ?>>
                </div>

                <div class="form-group">
                    <button type="submit" id="loginBtn" class="btn" style="width: 100%;" <?php echo $lockout_remaining > 0 ? 'disabled' : ''; ?>>
                        <i class="fas fa-lock"></i> Login as Admin
                    </button>
                </div>
            </form>

            <!-- <div class="demo-credentials">
                <p><strong>Admin Credentials:</strong></p>
                <p>Username: <code>admin</code></p>
                <p>Password: <code>admin123</code></p>
            </div>
            
            <div class="security-note">
                <i class="fas fa-info-circle"></i> 
                <span>This is a demo system. In production, use strong passwords and enable 2FA.</span>
            </div> -->

            <div class="back-to-home">
                <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Add ripple effect to login button
            const loginBtn = document.querySelector('.btn');
            loginBtn.addEventListener('click', function (e) {
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;

                const ripple = document.createElement('span');
                ripple.className = 'ripple';
                ripple.style.cssText = `
                    width: ${size}px;
                    height: ${size}px;
                    top: ${y}px;
                    left: ${x}px;
                `;

                this.appendChild(ripple);

                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });

            // Add security animation to form
            const form = document.querySelector('form');
            form.addEventListener('submit', function (e) {
                const inputs = this.querySelectorAll('input');
                let allValid = true;

                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        allValid = false;
                        input.style.borderColor = '#ef4444';
                        input.style.animation = 'shake 0.5s ease-in-out';
                        setTimeout(() => {
                            input.style.animation = '';
                        }, 500);
                    }
                });

                if (!allValid) {
                    e.preventDefault();
                }
            });

            // Add admin-specific styling
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function () {
                    this.style.background = 'linear-gradient(135deg, #f0f7ff 0%, #ffffff 100%)';
                });

                input.addEventListener('blur', function () {
                    this.style.background = '#f9fafb';
                });
            });

            // Add typing animation effect
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');

            // Simulate typing effect on focus
            usernameInput.addEventListener('focus', function () {
                this.style.letterSpacing = '1px';
            });

            usernameInput.addEventListener('blur', function () {
                this.style.letterSpacing = 'normal';
            });

            passwordInput.addEventListener('focus', function () {
                this.style.letterSpacing = '2px';
            });

            passwordInput.addEventListener('blur', function () {
                this.style.letterSpacing = 'normal';
            });

            // Add secure keypress effect for password
            passwordInput.addEventListener('keypress', function (e) {
                const key = e.key;
                if (key.length === 1) { // Only for character keys
                    const secureChar = '•';
                    this.setAttribute('data-typed', (this.getAttribute('data-typed') || '') + secureChar);

                    // Briefly show the character then hide it
                    const originalType = this.type;
                    this.type = 'text';
                    const originalValue = this.value;
                    this.value = originalValue + key;

                    setTimeout(() => {
                        this.type = originalType;
                        this.value = originalValue + key;
                    }, 100);
                }
            });

            // Add admin login sound effect (optional - commented out)

            loginBtn.addEventListener('click', function () {
                const audio = new Audio('data:audio/wav;base64,UklGRigAAABXQVZFZm10IBIAAAABAAEARKwAAIhYAQACABAAZGF0YQQAAAAAAA==');
                audio.volume = 0.3;
                audio.play().catch(e => console.log('Audio play failed:', e));
            });

        });

        // Lockout countdown timer
        (function () {
            var lockoutRemaining = <?php echo $lockout_remaining; ?>;

            if (lockoutRemaining > 0) {
                var countdownEl = document.getElementById('countdownTimer');
                var usernameInput = document.getElementById('username');
                var passwordInput = document.getElementById('password');
                var loginBtn = document.getElementById('loginBtn');
                var backToHomeLink = document.querySelector('.back-to-home a');

                function updateCountdown() {
                    if (lockoutRemaining > 0) {
                        if (countdownEl) {
                            countdownEl.textContent = ' (' + lockoutRemaining + 's)';
                        }
                        lockoutRemaining--;
                        setTimeout(updateCountdown, 1000);
                    } else {
                        // Re-enable form
                        if (usernameInput) usernameInput.disabled = false;
                        if (passwordInput) passwordInput.disabled = false;
                        if (loginBtn) loginBtn.disabled = false;
                        if (backToHomeLink) {
                            backToHomeLink.style.pointerEvents = '';
                            backToHomeLink.style.opacity = '';
                        }

                        // Update message
                        var errorMessage = document.getElementById('errorMessage');
                        if (errorMessage) {
                            errorMessage.textContent = 'You can now try again.';
                        }
                        if (countdownEl) {
                            countdownEl.textContent = '';
                        }

                        // Reload the page to reset session lockout
                        window.location.reload();
                    }
                }

                updateCountdown();
            }
        })();
    </script>
</body>

</html>