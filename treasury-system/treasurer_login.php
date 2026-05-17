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

function verify_password(string $input, string $stored): bool
{
    if ($stored === '') return false;
    if (preg_match('/^(\$2y\$|\$2b\$|\$argon2)/i', $stored)) {
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

    $user = null;
    $role = '';
    $name = '';

    // 1. Check admin table
    $stmt = $conn->prepare("SELECT id, username, password, full_name as name FROM " . DB_PROFILING . ".admin WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $user = $row;
        $role = 'admin';
        $name = $row['name'];
    }
    $stmt->close();

    // 2. Check staff table
    if (!$user) {
        $stmt = $conn->prepare("SELECT id, username, password, first_name, surname, position, status FROM " . DB_PROFILING . ".staff WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (strtolower($row['status'] ?? 'active') === 'active') {
                $user = $row;
                $role = 'treasurer'; // Staff default to treasurer role
                $name = trim($row['first_name'] . ' ' . $row['surname']);
            } else {
                $error = "Account is inactive. Please contact the administrator.";
            }
        }
        $stmt->close();
    }

    // 3. Check barangay_official table
    if (!$user && empty($error)) {
        $stmt = $conn->prepare("SELECT id, username, password, first_name, surname, position, status FROM " . DB_PROFILING . ".barangay_official WHERE username = ? AND username != '' LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (strtolower($row['status'] ?? 'active') === 'active') {
                $user = $row;
                // Grant admin access to Captain/Secretary, otherwise treasurer
                $is_secretary = (stripos($row['position'], 'Secretary') !== false || stripos($row['position'], 'Captain') !== false);
                $role = $is_secretary ? 'admin' : 'treasurer';
                $name = trim($row['first_name'] . ' ' . $row['surname']);
            } else {
                $error = "Account is inactive. Please contact the administrator.";
            }
        }
        $stmt->close();
    }

    if ($user) {
        if (verify_password($password, $user['password'])) {
            if ($role === 'treasurer' || $role === 'admin') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $name;
                $_SESSION['role'] = $role;

                header("Location: treasurer/dashboard.php");
                exit;
            } else {
                $error = "This account is not authorized for treasurer login.";
            }
        } else {
            $error = "Invalid password";
        }
    } elseif (empty($error)) {
        $error = "User not found";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Treasurer Login - Sto. Rosario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --brand-primary: #1f3a93; --brand-secondary: #2e4fc7; }
        body { background: #f4f6fb; font-family: 'Inter', sans-serif; }
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
        .btn-login:hover { opacity: .92; transform: translateY(-1px); color: #fff; }
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
                <a href="index.php" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none mb-4 opacity-75 hover-opacity-100 transition-all">
                    <i class="bi bi-arrow-left-circle"></i> Back to Home
                </a>
                <div class="d-flex align-items-center gap-3 mb-4">
                    <img src="assets/images/logo.jpg" alt="Logo" style="width:48px;height:48px;border-radius:50%;background:rgba(255,255,255,.15);padding:2px;">
                    <div class="text-white">
                        <div class="fw-bold lh-1" style="font-size:1.1rem;">Treasury System</div>
                        <div class="opacity-75" style="font-size:.8rem;">Financial Record Management</div>
                    </div>
                </div>
                <h2 class="text-white fw-bold mb-2" style="font-size:clamp(1.5rem,2.5vw,2.2rem);line-height:1.25;">
                    Transparent Finance,<br><span style="color:#93c5fd;">Digital Treasury</span>
                </h2>
                <p class="text-white opacity-75 mb-4" style="font-size:.9rem;max-width:350px;">
                    Authorized personnel portal for managing barangay collections, disbursements, and financial records.
                </p>
                <div>
                    <div class="login-feature-item"><span class="login-feature-dot"></span> Revenue Collection Tracking</div>
                    <div class="login-feature-item"><span class="login-feature-dot"></span> Disbursement Management</div>
                    <div class="login-feature-item"><span class="login-feature-dot"></span> Real-time Financial Reporting</div>
                    <div class="login-feature-item"><span class="login-feature-dot"></span> Resident Payment Verification</div>
                </div>
            </div>
        </div>

        <div class="login-form-panel">
            <div class="d-md-none mb-3 w-100 text-center" style="max-width:400px;">
                <a href="index.php" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none small opacity-75 hover-opacity-100 transition-all">
                    <i class="bi bi-arrow-left-circle"></i> Back to Home
                </a>
            </div>
            <div class="login-card">
                <div class="text-center mb-4">
                    <img src="assets/images/logo.jpg" alt="Logo" class="mb-3" style="width:64px;height:64px;border-radius:50%;box-shadow:0 4px 16px rgba(31,58,147,.25);">
                    <h4 class="fw-bold mb-1">Treasurer Access</h4>
                    <p class="text-muted small">Sign in to manage barangay finances</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2 py-2" role="alert" style="font-size: 0.85rem; border-radius: 10px;">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div><?= htmlspecialchars($error) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" name="username" placeholder="Username" required autofocus>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" name="password" placeholder="Password" required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <a href="forgot_password.php" class="small text-decoration-none" style="color: var(--brand-primary);">Forgot password?</a>
                        <a href="resident/login.php" class="small text-decoration-none fw-bold" style="color: var(--brand-secondary);">Resident Login</a>
                    </div>
                    <button class="btn btn-login w-100 py-2 rounded-3" type="submit">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                    </button>
                </form>
            </div>
            <div class="text-muted small mt-4" style="opacity:.6;">
                &copy; <?php echo date('Y'); ?> Treasury System. All rights reserved.
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>