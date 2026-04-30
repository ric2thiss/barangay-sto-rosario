<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (isset($_SESSION['user_id'])) {
    $role = strtolower(trim($_SESSION['role'] ?? ''));
    if ($role === 'treasurer' || $role === 'admin') {
        header("Location: ../treasurer/dashboard.php");
        exit;
    }
}

include "../config/database.php";

if (isset($_SESSION['resident_id'])) {
    header("Location: pending_payments.php");
    exit;
}

$error = "";
$notice = "";

if (isset($_GET['error']) && $_GET['error'] === 'session') {
    $notice = "Please login to continue.";
} elseif (isset($_GET['error']) && $_GET['error'] === 'account') {
    $error = "Account not found or inactive. Please contact the barangay office.";
}

function build_resident_name(array $resident, string $middleMode = 'full'): string
{
    $first = trim($resident['first_name'] ?? '');
    $middle = trim($resident['middle_name'] ?? '');
    $surname = trim($resident['surname'] ?? '');
    $suffix = trim($resident['suffix'] ?? '');

    $parts = [];
    if ($first !== '') {
        $parts[] = $first;
    }

    if ($middle !== '') {
        if ($middleMode === 'initial') {
            $parts[] = strtoupper(substr($middle, 0, 1)) . '.';
        } elseif ($middleMode === 'full') {
            $parts[] = $middle;
        }
    }

    if ($surname !== '') {
        $parts[] = $surname;
    }

    if ($suffix !== '') {
        $parts[] = $suffix;
    }

    return trim(implode(' ', $parts));
}

function verify_resident_password(string $input, string $stored): bool
{
    if ($stored === '') {
        return false;
    }

    if (preg_match('/^(\$2y\$|\$argon2)/i', $stored)) {
        return password_verify($input, $stored);
    }

    if (preg_match('/^[a-f0-9]{32}$/i', $stored)) {
        return md5($input) === strtolower($stored);
    }

    return hash_equals($stored, $input);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Please enter your username and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, first_name, middle_name, surname, suffix, username, password, account_status, lockout_until, login_attempts FROM residents WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $resident = $result->fetch_assoc();
        $stmt->close();

        if (!$resident) {
            $error = "Invalid username or password.";
        } else {
            $status = strtolower(trim($resident['account_status'] ?? 'active'));
            if ($status !== 'active') {
                $error = "Account status is $status. Please contact the barangay office.";
            } else {
                $lockoutUntil = $resident['lockout_until'] ?? null;
                if (!empty($lockoutUntil) && strtotime($lockoutUntil) > time()) {
                    $error = "Account is temporarily locked. Try again after " . date('M d, Y h:i A', strtotime($lockoutUntil)) . ".";
                } else {
                    $storedPassword = $resident['password'] ?? '';
                    if (verify_resident_password($password, $storedPassword)) {
                        $update = $conn->prepare("UPDATE residents SET last_login = NOW(), login_attempts = 0, lockout_until = NULL WHERE id = ?");
                        $update->bind_param("i", $resident['id']);
                        $update->execute();
                        $update->close();

                        $_SESSION['resident_id'] = $resident['id'];
                        $_SESSION['resident_name'] = build_resident_name($resident, 'full');
                        $_SESSION['resident_username'] = $resident['username'];
                        $_SESSION['user_type'] = 'resident';

                        header("Location: pending_payments.php");
                        exit;
                    }

                    $attempts = intval($resident['login_attempts'] ?? 0) + 1;
                    $lockoutValue = null;
                    if ($attempts >= 5) {
                        $lockoutValue = date('Y-m-d H:i:s', time() + 900);
                        $attempts = 0;
                    }

                    $update = $conn->prepare("UPDATE residents SET login_attempts = ?, lockout_until = ? WHERE id = ?");
                    $update->bind_param("isi", $attempts, $lockoutValue, $resident['id']);
                    $update->execute();
                    $update->close();

                    if ($lockoutValue !== null) {
                        $error = "Too many attempts. Account locked for 15 minutes.";
                    } else {
                        $error = "Invalid username or password.";
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Login - Barangay Sto. Rosario</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body.resident-portal {
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
            margin-top: 0;
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

<body class="resident-portal">
    <div class="header-banner">
        <h1>RESIDENT PORTAL</h1>
    </div>

    <div class="main-content-wrapper">
        <div class="logo-container">
            <div class="logo-wrapper">
                <img src="../assets/images/logo.jpg" alt="Barangay Logo" class="logo-img">
            </div>
            <div class="branding">
                <h2>Barangay</h2>
                <h1>STO. ROSARIO</h1>
                <p>Magallanes, Agusan del Norte</p>
            </div>
        </div>

        <div class="login-container">
            <h3><i class="fas fa-user"></i> Resident Login</h3>

            <?php if ($notice): ?>
            <div class="success-message">
                <i class="fas fa-info-circle"></i>
                <?= htmlspecialchars($notice) ?>
            </div>
            <?php endif; ?>

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
                <a href="../treasurer_login.php">Login as treasurer</a>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>&copy; 2025 Barangay Sto. Rosario, Magallanes, Agusan del Norte</p>
        <p>Treasurer Financial Record Management System | All Rights Reserved</p>
    </div>

    <script src="../assets/js/password-toggle.js"></script>
</body>

</html>