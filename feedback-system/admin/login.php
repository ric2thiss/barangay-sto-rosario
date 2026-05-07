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

$error = null;
$lockout_remaining = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

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
            $error = "Too many failed attempts. Please wait.";
            $lockout_remaining = $time_remaining;
        } else {
            // Lockout expired, reset attempts
            $_SESSION['admin_login_attempts'] = 0;
            $_SESSION['admin_lockout_time'] = null;
        }
    }

    // Validate inputs
    if (!$error && (empty($username) || empty($password))) {
        $error = "Please enter both username and password";
    }

    // Proceed with login if not locked out and inputs are valid
    if (!$error) {
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
                    $error = "Too many failed attempts.";
                } else {
                    $error = "Invalid credentials. {$attempts_left} attempt(s) left.";
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
                $error = "Too many failed attempts.";
            } else {
                $error = "Invalid credentials. {$attempts_left} attempt(s) left.";
            }
        }
        $stmt->close();
    }
}

// Check lockout on page load
if (isset($_SESSION['admin_lockout_time']) && $_SESSION['admin_lockout_time'] !== null) {
    $time_remaining = $_SESSION['admin_lockout_time'] - time();
    if ($time_remaining > 0) {
        $lockout_remaining = $time_remaining;
        $error = $error ?? "Too many failed attempts.";
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
    <title>Admin Login - Sto. Rosario Feedback</title>
    <link rel="icon" href="../img/logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --brand-primary: #1f3a93; --brand-secondary: #2e4fc7; }
        body { background: #f4f6fb; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }
        .login-wrap { min-height: 100vh; display: flex; }
        .login-brand {
            flex: 0 0 42%;
            background: linear-gradient(155deg, var(--brand-primary) 0%, var(--brand-secondary) 55%, #1a56db 100%);
            display: flex; flex-direction: column; justify-content: center; align-items: flex-start;
            padding: 3rem 3.5rem; position: relative; overflow: hidden;
        }
        .login-brand::before {
            content: ""; position: absolute; inset: 0;
            background: radial-gradient(ellipse at 10% 80%, rgba(255,255,255,.08) 0%, transparent 55%),
                        radial-gradient(ellipse at 90% 10%, rgba(255,255,255,.05) 0%, transparent 45%);
            pointer-events: none;
        }
        .login-brand-circle { position: absolute; border-radius: 50%; background: rgba(255,255,255,.06); }
        .lbc-1 { width:380px; height:380px; bottom:-120px; right:-100px; }
        .lbc-2 { width:200px; height:200px; top:-50px; right:20px; }
        .login-brand-content { position: relative; z-index: 2; }
        .login-feature-item { display: flex; align-items: center; gap: .75rem; margin-bottom: .85rem; opacity: .85; color: white; font-size: 0.9rem; }
        .login-feature-dot { width: 8px; height: 8px; border-radius: 50%; background: #93c5fd; flex-shrink: 0; }
        .login-form-panel { flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 2.5rem 2rem; background: #f4f6fb; }
        .login-card { width: 100%; max-width: 400px; background: #fff; border-radius: 20px; box-shadow: 0 8px 40px rgba(31,58,147,.1); padding: 2.5rem; }
        .form-control:focus { border-color: var(--brand-primary); box-shadow: 0 0 0 .2rem rgba(31,58,147,.18); }
        .btn-login { background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary)); color: #fff; border: none; font-weight: 600; letter-spacing: .3px; transition: all .15s; }
        .btn-login:hover:not(:disabled) { opacity: .92; transform: translateY(-1px); color: #fff; }
        .btn-login:disabled { opacity: .6; cursor: not-allowed; }
        @media (max-width: 767px) {
            .login-brand { display: none; }
            .login-form-panel { background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 100%); padding: 1.5rem 1rem; }
            .login-card { box-shadow: 0 12px 48px rgba(0,0,0,.25); }
        }
    </style>
</head>
<body>
    <div class="login-wrap">
        <div class="login-brand">
            <div class="login-brand-circle lbc-1"></div>
            <div class="login-brand-circle lbc-2"></div>
            <div class="login-brand-content">
                <a href="../index.php" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none mb-4 opacity-75 hover-opacity-100 transition-all">
                    <i class="bi bi-arrow-left-circle"></i> Back to Home
                </a>
                <div class="d-flex align-items-center gap-3 mb-4">
                    <img src="../img/logo.png" alt="Logo" style="width:48px;height:48px;border-radius:50%;background:rgba(255,255,255,.15);padding:2px;">
                    <div class="text-white">
                        <div class="fw-bold lh-1" style="font-size:1.1rem;">Feedback System</div>
                        <div class="opacity-75" style="font-size:.8rem;">Resident Voices & Surveys</div>
                    </div>
                </div>
                <h2 class="text-white fw-bold mb-2" style="font-size:clamp(1.5rem,2.5vw,2.2rem);line-height:1.25;">
                    Empowering Community,<br><span style="color:#93c5fd;">Driving Progress</span>
                </h2>
                <p class="text-white opacity-75 mb-4" style="font-size:.9rem;max-width:350px;">
                    Authorized portal for administrators to manage resident feedback, analyze survey results, and improve barangay services.
                </p>
                <div>
                    <div class="login-feature-item"><span class="login-feature-dot"></span> Real-time Feedback Monitoring</div>
                    <div class="login-feature-item"><span class="login-feature-dot"></span> Advanced Survey Analytics</div>
                    <div class="login-feature-item"><span class="login-feature-dot"></span> Resident Sentiment Tracking</div>
                    <div class="login-feature-item"><span class="login-feature-dot"></span> Automated Service Reporting</div>
                </div>
            </div>
        </div>

        <div class="login-form-panel">
            <div class="d-md-none mb-3 w-100 text-center" style="max-width:400px;">
                <a href="../index.php" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none small opacity-75 hover-opacity-100 transition-all">
                    <i class="bi bi-arrow-left-circle"></i> Back to Home
                </a>
            </div>
            <div class="login-card">
                <div class="text-center mb-4">
                    <img src="../img/logo.png" alt="Logo" class="mb-3" style="width:64px;height:64px;border-radius:50%;box-shadow:0 4px 16px rgba(31,58,147,.25);">
                    <h4 class="fw-bold mb-1">Admin Access</h4>
                    <p class="text-muted small">Sign in to manage feedback & surveys</p>
                </div>

                <?php if ($error): ?>
                <div class="alert <?= $lockout_remaining > 0 ? 'alert-warning' : 'alert-danger' ?> small mb-3" style="border-radius:10px;">
                    <i class="bi <?= $lockout_remaining > 0 ? 'bi-clock-history' : 'bi-exclamation-circle' ?> me-2"></i>
                    <strong><?= $lockout_remaining > 0 ? 'Account Locked' : 'Login Failed' ?></strong><br>
                    <span id="errorMessage"><?= htmlspecialchars($error) ?></span>
                    <?php if ($lockout_remaining > 0): ?>
                        <b id="countdown"><?= (int)$lockout_remaining ?></b>s remaining.
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Username or Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" name="username" placeholder="Enter username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" <?= $lockout_remaining > 0 ? 'disabled' : '' ?>>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" name="password" id="pwdField" placeholder="Enter password" required <?= $lockout_remaining > 0 ? 'disabled' : '' ?>>
                            <button class="btn btn-outline-secondary" type="button" id="togglePwd"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    
                    <button class="btn btn-login w-100 py-2 rounded-3 mt-3" type="submit" <?= $lockout_remaining > 0 ? 'disabled' : '' ?>>
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                    </button>
                </form>
            </div>
            <div class="text-muted small mt-4" style="opacity:.6;">
                &copy; <?= date('Y') ?> Feedback System. Secure Administrative Portal.
            </div>
        </div>
    </div>

    <script>
    // Password Toggle
    const pwd = document.getElementById('pwdField');
    const btn = document.getElementById('togglePwd');
    if(btn) {
        btn.addEventListener('click', () => {
            const show = pwd.type === 'password';
            pwd.type = show ? 'text' : 'password';
            btn.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        });
    }

    // Lockout Countdown
    let lockoutTime = <?= (int)$lockout_remaining ?>;
    if (lockoutTime > 0) {
        const countdownEl = document.getElementById('countdown');
        const timer = setInterval(() => {
            lockoutTime--;
            if (countdownEl) countdownEl.textContent = lockoutTime;
            
            if (lockoutTime <= 0) {
                clearInterval(timer);
                window.location.reload(); // Refresh to re-enable inputs
            }
        }, 1000);
    }
    </script>
</body>
</html>