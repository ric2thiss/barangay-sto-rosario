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
        // 1. Check Residents Table
        $stmt = $conn->prepare("SELECT id, first_name, middle_name, surname, suffix, username, password, account_status, lockout_until, login_attempts FROM " . DB_PROFILING . ".residents WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $resident = $result->fetch_assoc();
        $stmt->close();

        if ($resident) {
            $status = strtolower(trim($resident['account_status'] ?? 'active'));
            if ($status !== 'active') {
                $error = "Account status is $status. Please contact the barangay office.";
            } else {
                $lockoutUntil = $resident['lockout_until'] ?? null;
                if (!empty($lockoutUntil) && strtotime($lockoutUntil) > time()) {
                    $error = "Account is temporarily locked. Try again after " . date('M d, Y h:i A', strtotime($lockoutUntil)) . ".";
                } else {
                    if (verify_resident_password($password, $resident['password'])) {
                        $update = $conn->prepare("UPDATE " . DB_PROFILING . ".residents SET last_login = NOW(), login_attempts = 0, lockout_until = NULL WHERE id = ?");
                        $update->bind_param("i", $resident['id']);
                        $update->execute();
                        $update->close();

                        $_SESSION['resident_id'] = $resident['id'];
                        $_SESSION['resident_name'] = build_resident_name($resident, 'full');
                        $_SESSION['resident_username'] = $resident['username'];
                        $_SESSION['user_type'] = 'resident';

                        header("Location: pending_payments.php");
                        exit;
                    } else {
                        // Increment attempts
                        $attempts = intval($resident['login_attempts'] ?? 0) + 1;
                        $lockoutValue = null;
                        if ($attempts >= 5) {
                            $lockoutValue = date('Y-m-d H:i:s', time() + 900);
                            $attempts = 0;
                        }
                        $update = $conn->prepare("UPDATE " . DB_PROFILING . ".residents SET login_attempts = ?, lockout_until = ? WHERE id = ?");
                        $update->bind_param("isi", $attempts, $lockoutValue, $resident['id']);
                        $update->execute();
                        $update->close();
                        $error = $lockoutValue ? "Too many attempts. Account locked for 15 minutes." : "Invalid username or password.";
                    }
                }
            }
        } else {
            // 2. Check Barangay Official Table
            $stmt = $conn->prepare("SELECT id, first_name, middle_name, surname, suffix, username, password, status as account_status FROM " . DB_PROFILING . ".barangay_official WHERE username = ? LIMIT 1");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $official = $result->fetch_assoc();
            $stmt->close();

            if ($official) {
                $status = strtolower(trim($official['account_status'] ?? 'active'));
                if ($status !== 'active') {
                    $error = "Account status is $status. Please contact the barangay office.";
                } else {
                    if (verify_resident_password($password, $official['password'])) {
                        // Official login successful
                        $_SESSION['resident_id'] = $official['id']; // We use resident_id session key for compatibility
                        $_SESSION['official_id'] = $official['id'];
                        $_SESSION['resident_name'] = build_resident_name($official, 'full');
                        $_SESSION['resident_username'] = $official['username'];
                        $_SESSION['user_type'] = 'official';

                        header("Location: pending_payments.php");
                        exit;
                    } else {
                        $error = "Invalid username or password.";
                    }
                }
            } else {
                $error = "Invalid username or password.";
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
    <title>Resident Login - Sto. Rosario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --brand-primary: #1f3a93;
            --brand-secondary: #2e4fc7;
        }

        body {
            background: #f4f6fb;
            font-family: 'Inter', sans-serif;
        }

        .login-wrap {
            min-height: 100vh;
            display: flex;
        }

        .login-brand {
            flex: 0 0 42%;
            background: linear-gradient(155deg, var(--brand-primary) 0%, var(--brand-secondary) 55%, #1a56db 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding: 3rem 3.5rem;
            position: relative;
            overflow: hidden;
        }

        .login-brand::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 10% 80%, rgba(255, 255, 255, .08) 0%, transparent 55%),
                radial-gradient(ellipse at 90% 10%, rgba(255, 255, 255, .05) 0%, transparent 45%);
            pointer-events: none;
        }

        .login-brand-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, .06);
        }

        .lbc-1 {
            width: 380px;
            height: 380px;
            bottom: -120px;
            right: -100px;
        }

        .lbc-2 {
            width: 200px;
            height: 200px;
            top: -50px;
            right: 20px;
        }

        .login-brand-content {
            position: relative;
            z-index: 2;
        }

        .login-feature-item {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin-bottom: .85rem;
            opacity: .85;
            color: white;
            font-size: 0.9rem;
        }

        .login-feature-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #93c5fd;
            flex-shrink: 0;
        }

        .login-form-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2.5rem 2rem;
            background: #f4f6fb;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 40px rgba(31, 58, 147, .1);
            padding: 2.5rem;
        }

        .form-control:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 .2rem rgba(31, 58, 147, .18);
        }

        .btn-login {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            color: #fff;
            border: none;
            font-weight: 600;
            letter-spacing: .3px;
            transition: all .15s;
        }

        .btn-login:hover {
            opacity: .92;
            transform: translateY(-1px);
            color: #fff;
        }

        @media (max-width: 767px) {
            .login-brand {
                display: none;
            }

            .login-form-panel {
                background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 100%);
                padding: 1.5rem 1rem;
            }

            .login-card {
                box-shadow: 0 12px 48px rgba(0, 0, 0, .25);
            }
        }
    </style>
</head>

<body>
    <div class="login-wrap">
        <div class="login-brand">
            <div class="login-brand-circle lbc-1"></div>
            <div class="login-brand-circle lbc-2"></div>
            <div class="login-brand-content">
                <a href="../../index.php"
                    class="d-inline-flex align-items-center gap-2 text-white text-decoration-none mb-4 opacity-75 hover-opacity-100 transition-all">
                    <i class="bi bi-arrow-left-circle"></i> Back to Main Systems
                </a>
                <div class="d-flex align-items-center gap-3 mb-4">
                    <img src="../assets/images/logo.jpg" alt="Logo"
                        style="width:48px;height:48px;border-radius:50%;background:rgba(255,255,255,.15);padding:2px;">
                    <div class="text-white">
                        <div class="fw-bold lh-1" style="font-size:1.1rem;">Resident Portal</div>
                        <div class="opacity-75" style="font-size:.8rem;">Barangay Sto. Rosario</div>
                    </div>
                </div>
                <h2 class="text-white fw-bold mb-2" style="font-size:clamp(1.5rem,2.5vw,2.2rem);line-height:1.25;">
                    Access Your<br><span style="color:#93c5fd;">Resident Records</span>
                </h2>
                <p class="text-white opacity-75 mb-4" style="font-size:.9rem;max-width:350px;">
                    View your payment history, request documents, and manage your community transactions online.
                </p>
                <div>
                    <div class="login-feature-item"><span class="login-feature-dot"></span> View Pending Payments</div>
                    <div class="login-feature-item"><span class="login-feature-dot"></span> Payment History Access</div>
                    <div class="login-feature-item"><span class="login-feature-dot"></span> Online Cedula Request</div>
                    <div class="login-feature-item"><span class="login-feature-dot"></span> Personal Transaction Log
                    </div>
                </div>
            </div>
        </div>

        <div class="login-form-panel">
            <div class="d-md-none mb-3 w-100 text-center" style="max-width:400px;">
                <a href="../../index.php"
                    class="d-inline-flex align-items-center gap-2 text-white text-decoration-none small opacity-75 hover-opacity-100 transition-all">
                    <i class="bi bi-arrow-left-circle"></i> Back to Main Systems
                </a>
            </div>
            <div class="login-card">
                <div class="text-center mb-4">
                    <img src="../assets/images/logo.jpg" alt="Logo" class="mb-3"
                        style="width:64px;height:64px;border-radius:50%;box-shadow:0 4px 16px rgba(31,58,147,.25);">
                    <h4 class="fw-bold mb-1">Resident Login</h4>
                    <p class="text-muted small">Sign in to your resident account</p>
                </div>

                <?php if ($notice): ?>
                    <div class="alert alert-info d-flex align-items-center gap-2 py-2" role="alert"
                        style="font-size: 0.85rem; border-radius: 10px;">
                        <i class="bi bi-info-circle-fill"></i>
                        <div><?= htmlspecialchars($notice) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2 py-2" role="alert"
                        style="font-size: 0.85rem; border-radius: 10px;">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div><?= htmlspecialchars($error) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" name="username" placeholder="Username" required
                                autofocus>
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
                        <a href="../treasurer_login.php" class="small text-decoration-none fw-bold"
                            style="color: var(--brand-primary);">Treasurer Login</a>
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