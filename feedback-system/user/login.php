<?php
require_once '../config/config.php';
// Add maintenance mode check function
require_once '../includes/functions.php';

require '../includes/PHPMailer/src/Exception.php';
require '../includes/PHPMailer/src/PHPMailer.php';
require '../includes/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check maintenance mode before login
checkMaintenanceMode();

// If maintenance mode is on, show notice but allow login

$success_message = '';

// Handle forgot password
$forgot_message = '';
$forgot_error = '';
if (isset($_POST['forgot_password'])) {
    // Strip all whitespaces safely (including non-breaking spaces)
    $raw_input = preg_replace('/\s+/u', '', $_POST['identifier']);
    $identifier = $conn->real_escape_string($raw_input);
    
    if (empty($identifier)) {
        $forgot_error = "Please enter your username or email";
        $_SESSION['show_forgot_modal'] = true;
    } else {
        $stmt = $conn->prepare("SELECT email FROM `profiling-system`.residents WHERE (username = ? OR email = ?) AND user_role = 'resident'");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            $email = $user_data['email'];
            // User exists
            // Generate OTP
            $token = sprintf("%06d", mt_rand(1, 999999));
            $expiry = date('Y-m-d H:i:s', strtotime('+2 minutes'));
            
            // Store token in database
            $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $token, $expiry);
            
            if ($stmt->execute()) {
                // Generate link
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $reset_link = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
                
                $mail = new PHPMailer(true);
                
                try {
                    //Server settings
                    $mail->isSMTP();
                    $mail->Host       = SMTP_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = SMTP_USER;
                    $mail->Password   = SMTP_PASS;
                    $mail->SMTPSecure = SMTP_SECURE;
                    $mail->Port       = SMTP_PORT;

                    //Recipients
                    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                    $mail->addAddress($email);

                    //Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset OTP Request';
                    $mail->Body    = "Hello,<br><br>You requested a password reset. Your OTP is:<br><br><strong style='font-size:24px; letter-spacing:5px;'>$token</strong><br><br>This code expires in 2 minutes.<br><br>If you did not request this, please ignore this email.";
                    $mail->AltBody = "Hello,\n\nYou requested a password reset. Your OTP code is: $token\n\nThis code expires in 2 minutes.\n\nIf you did not request this, please ignore this email.";

                    $mail->send();
                    $_SESSION['otp_email'] = $email;
                    $_SESSION['show_otp_modal'] = true;
                    unset($_SESSION['show_forgot_modal']);
                } catch (Exception $e) {
                    $forgot_error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                    $_SESSION['show_forgot_modal'] = true;
                }
            } else {
                $forgot_error = "Error generating reset token. Please try again.";
                $_SESSION['show_forgot_modal'] = true;
            }
        } else {
             $forgot_error = "Email address not found.";
             $_SESSION['show_forgot_modal'] = true;
        }
        $stmt->close();
    }
}

// Handle OTP Verification
$otp_error = '';
if (isset($_POST['verify_otp'])) {
    $otp = trim($conn->real_escape_string($_POST['otp']));
    $email = $_SESSION['otp_email'] ?? '';
    
    if (empty($email)) {
        header("Location: login.php");
        exit();
    }
    
    $stmt = $conn->prepare("SELECT id FROM password_resets WHERE email = ? AND token = ? AND expires_at > NOW()");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        unset($_SESSION['show_otp_modal']);
        unset($_SESSION['otp_email']);
        header("Location: reset_password.php?token=" . urlencode($otp));
        exit();
    } else {
        $otp_error = "Invalid or expired OTP. Please try again or request a new one.";
        $_SESSION['show_otp_modal'] = true;
    }
    $stmt->close();
}

// Check if we should show the forgot password modal
$show_forgot_modal = isset($_SESSION['show_forgot_modal']) && $_SESSION['show_forgot_modal'];

// Check if we should show the OTP modal
$show_otp_modal = isset($_SESSION['show_otp_modal']) && $_SESSION['show_otp_modal'];

// Clear session data based on request method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['forgot_password'])) {
        $show_forgot_modal = false;
        unset($_SESSION['show_forgot_modal']);
    }
    if (!isset($_POST['verify_otp']) && !isset($_POST['forgot_password'])) {
        $show_otp_modal = false;
        unset($_SESSION['show_otp_modal']);
    }
} else {
    if ($show_forgot_modal) {
        unset($_SESSION['show_forgot_modal']);
    }
    if ($show_otp_modal) {
        unset($_SESSION['show_otp_modal']);
    }
}


if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header('Location: ../admin/index.php');
    } else {
        header('Location: index.php');
    }
    if ($_SESSION['user_type'] === 'admin') {
        header('Location: ../admin/index.php');
    } else {
        header('Location: index.php');
    }
    exit();
}

if (isset($_GET['password_reset']) && $_GET['password_reset'] == 'success') {
    $success_message = "Password successfully reset! You can now login with your new password.";
}

if (isset($_POST['login'])) {
    // Strip all whitespaces safely for login user inputs (also helps with mobile keyboards adding spaces)
    $username = preg_replace('/\s+/u', '', $_POST['username']);
    $password = $_POST['password'];
    
    // Initialize login attempts in session if not set
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['lockout_time'] = null;
        $_SESSION['lockout_round'] = 0;
    }
    
    $max_attempts = 3;
    
    // Check if user is locked out
    if ($_SESSION['lockout_time'] !== null) {
        $time_remaining = $_SESSION['lockout_time'] - time();
        if ($time_remaining > 0) {
            $error = "Too many failed attempts. Please wait before trying again.";
            $lockout_remaining = $time_remaining;
        } else {
            // Lockout expired, reset attempts
            $_SESSION['login_attempts'] = 0;
            $_SESSION['lockout_time'] = null;
        }
    }
    
    // Proceed with login if not locked out
    if (!isset($error)) {
        $sql = "SELECT * FROM `profiling-system`.residents WHERE (username = ? OR email = ?) AND user_role = 'resident'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Reset login attempts on successful login
                $_SESSION['login_attempts'] = 0;
                $_SESSION['lockout_time'] = null;
                $_SESSION['lockout_round'] = 0;
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = 'user'; // Hardcode as user for feedback system compatibility
                $_SESSION['user_email'] = $user['email'];
                
                header('Location: index.php');
                exit();
            } else {
                $_SESSION['login_attempts']++;
                $attempts_left = $max_attempts - $_SESSION['login_attempts'];
                
                if ($attempts_left <= 0) {
                    $_SESSION['lockout_round']++;
                    $lockout_duration = 30 * $_SESSION['lockout_round'];
                    $_SESSION['lockout_time'] = time() + $lockout_duration;
                    $lockout_remaining = $lockout_duration;
                    $error = "Too many failed attempts. Please wait before trying again.";
                } else {
                    $error = "Invalid username or password. {$attempts_left} attempt(s) left.";
                }
            }
        } else {
            $_SESSION['login_attempts']++;
            $attempts_left = $max_attempts - $_SESSION['login_attempts'];
            
            if ($attempts_left <= 0) {
                $_SESSION['lockout_round']++;
                $lockout_duration = 30 * $_SESSION['lockout_round'];
                $_SESSION['lockout_time'] = time() + $lockout_duration;
                $lockout_remaining = $lockout_duration;
                $error = "Too many failed attempts. Please wait before trying again.";
            } else {
                $error = "Invalid username or password. {$attempts_left} attempt(s) left.";
            }
        }
    }
}

// Check lockout on page load (not just POST)
$lockout_remaining = 0;
if (isset($_SESSION['lockout_time']) && $_SESSION['lockout_time'] !== null) {
    $time_remaining = $_SESSION['lockout_time'] - time();
    if ($time_remaining > 0) {
        $lockout_remaining = $time_remaining;
    } else {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['lockout_time'] = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login - Resident Feedback and Survey System</title>
    <meta name="description" content="Login to the Barangay Sto. Rosario Resident Feedback and Survey System.">
    <link rel="icon" href="../img/logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --brand: #1f3a93; --brand2: #2e4fc7; }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #f4f6fb; font-family: 'Inter', sans-serif; min-height: 100vh; display: flex; }

        .login-wrap { display: flex; min-height: 100vh; width: 100%; }

        /* Brand panel */
        .login-brand {
            flex: 0 0 42%;
            background: linear-gradient(155deg, var(--brand) 0%, var(--brand2) 55%, #1a56db 100%);
            display: flex; flex-direction: column; justify-content: center; align-items: flex-start;
            padding: 3rem 3.5rem; position: relative; overflow: hidden;
        }
        .login-brand::before {
            content: ""; position: absolute; inset: 0;
            background: radial-gradient(ellipse at 10% 80%, rgba(255,255,255,.08) 0%, transparent 55%),
                        radial-gradient(ellipse at 90% 10%, rgba(255,255,255,.05) 0%, transparent 45%);
            pointer-events: none;
        }
        .brand-circle { position: absolute; border-radius: 50%; background: rgba(255,255,255,.06); }
        .bc-1 { width: 380px; height: 380px; bottom: -120px; right: -100px; }
        .bc-2 { width: 200px; height: 200px; top: -50px; right: 20px; }
        .brand-content { position: relative; z-index: 2; }
        .brand-back { display: inline-flex; align-items: center; gap: .5rem; color: rgba(255,255,255,.75); text-decoration: none; font-size: .875rem; margin-bottom: 2rem; transition: color .2s; }
        .brand-back:hover { color: #fff; }
        .brand-logo { display: flex; align-items: center; gap: .85rem; margin-bottom: 2rem; }
        .brand-logo img { width: 48px; height: 48px; border-radius: 50%; background: rgba(255,255,255,.15); padding: 2px; object-fit: contain; }
        .brand-logo-text .sys { font-size: 1.05rem; font-weight: 700; color: #fff; line-height: 1; }
        .brand-logo-text .sub { font-size: .78rem; color: rgba(255,255,255,.7); }
        .brand-headline { font-size: clamp(1.5rem, 2.5vw, 2.1rem); font-weight: 800; color: #fff; line-height: 1.25; margin-bottom: .75rem; }
        .brand-headline span { color: #93c5fd; }
        .brand-desc { color: rgba(255,255,255,.75); font-size: .88rem; max-width: 340px; margin-bottom: 2rem; line-height: 1.65; }
        .feature-list { list-style: none; }
        .feature-list li { display: flex; align-items: center; gap: .75rem; color: rgba(255,255,255,.85); font-size: .88rem; margin-bottom: .75rem; }
        .feat-dot { width: 8px; height: 8px; border-radius: 50%; background: #93c5fd; flex-shrink: 0; }

        /* Form panel */
        .login-form-panel {
            flex: 1; display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            padding: 2.5rem 2rem; background: #f4f6fb;
        }
        .login-card {
            width: 100%; max-width: 420px;
            background: #fff; border-radius: 20px;
            box-shadow: 0 8px 40px rgba(31,58,147,.1);
            padding: 2.5rem;
            animation: fadeInUp .5s ease both;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .card-logo { text-align: center; margin-bottom: 1.25rem; }
        .card-logo img { width: 64px; height: 64px; border-radius: 50%; box-shadow: 0 4px 16px rgba(31,58,147,.25); object-fit: contain; }
        .card-title { text-align: center; margin-bottom: 1.5rem; }
        .card-title h1 { font-size: 1.35rem; font-weight: 700; color: #111827; margin-bottom: .25rem; }
        .card-title p  { font-size: .875rem; color: #6b7280; }

        /* Alerts */
        .alert { padding: .85rem 1rem; border-radius: 10px; font-size: .875rem; margin-bottom: 1.1rem; display: flex; align-items: flex-start; gap: .6rem; }
        .alert i { font-size: 1rem; flex-shrink: 0; margin-top: 2px; }
        .alert-success { background: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; }
        .alert-error   { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }

        /* Form */
        .form-label { display: block; font-size: .875rem; font-weight: 600; color: #374151; margin-bottom: .4rem; }
        .form-group  { margin-bottom: 1.1rem; }
        .input-wrap  { position: relative; display: flex; align-items: center; }
        .input-icon  { position: absolute; left: .9rem; color: #9ca3af; font-size: 1rem; pointer-events: none; }
        .form-control {
            width: 100%; padding: .72rem .9rem .72rem 2.5rem;
            border: 2px solid #e5e7eb; border-radius: 10px;
            font-size: .95rem; font-family: inherit;
            background: #f9fafb; color: #111827;
            transition: border-color .2s, box-shadow .2s;
        }
        .form-control:focus { outline: none; border-color: var(--brand); background: #fff; box-shadow: 0 0 0 3px rgba(31,58,147,.12); }
        .form-control:disabled { opacity: .55; cursor: not-allowed; }
        .toggle-pwd {
            position: absolute; right: .75rem; background: none; border: none;
            color: #9ca3af; cursor: pointer; font-size: 1rem; padding: .25rem;
            transition: color .2s;
        }
        .toggle-pwd:hover { color: var(--brand); }

        .form-meta { display: flex; justify-content: flex-end; margin-bottom: 1.1rem; }
        .form-meta a { font-size: .825rem; color: var(--brand); text-decoration: none; font-weight: 500; }
        .form-meta a:hover { text-decoration: underline; }

        .btn-login {
            width: 100%; padding: .85rem;
            background: linear-gradient(135deg, var(--brand), var(--brand2));
            color: #fff; border: none; border-radius: 10px;
            font-size: .95rem; font-weight: 600; font-family: inherit;
            cursor: pointer; transition: opacity .15s, transform .15s;
            display: flex; align-items: center; justify-content: center; gap: .5rem;
            position: relative; overflow: hidden;
        }
        .btn-login:hover:not(:disabled) { opacity: .9; transform: translateY(-1px); }
        .btn-login:disabled { opacity: .55; cursor: not-allowed; transform: none; }

        .ripple { position: absolute; border-radius: 50%; background: rgba(255,255,255,.45); transform: scale(0); animation: rippleAnim .55s linear; pointer-events: none; }
        @keyframes rippleAnim { to { transform: scale(4); opacity: 0; } }

        .card-footer-link { text-align: center; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid #f3f4f6; }
        .card-footer-link a { color: var(--brand); text-decoration: none; font-size: .875rem; font-weight: 500; display: inline-flex; align-items: center; gap: .4rem; }
        .card-footer-link a:hover { text-decoration: underline; }

        /* Modals */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1000; backdrop-filter: blur(3px); align-items: center; justify-content: center; padding: 1rem; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: #fff; border-radius: 18px; width: 100%; max-width: 470px; box-shadow: 0 20px 60px rgba(0,0,0,.2); animation: modalIn .35s ease; overflow: hidden; }
        @keyframes modalIn { from { opacity: 0; transform: translateY(-18px); } to { opacity: 1; transform: translateY(0); } }
        .modal-head { background: linear-gradient(135deg, var(--brand), #152c71); color: #fff; padding: 1.25rem 1.5rem; display: flex; align-items: center; justify-content: space-between; }
        .modal-head h3 { font-size: 1.05rem; font-weight: 600; display: flex; align-items: center; gap: .6rem; margin: 0; }
        .modal-head-green { background: linear-gradient(135deg, #10b981, #059669); }
        .modal-close-btn { background: rgba(255,255,255,.2); border: none; color: #fff; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: .95rem; transition: background .2s, transform .2s; }
        .modal-close-btn:hover { background: rgba(255,255,255,.3); transform: rotate(90deg); }
        .modal-body { padding: 1.6rem; }
        .modal-footer { padding: 1rem 1.5rem; background: #f9fafb; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: .75rem; }
        .btn-secondary { padding: .6rem 1.25rem; background: #6b7280; color: #fff; border: none; border-radius: 8px; font-size: .875rem; font-weight: 600; font-family: inherit; cursor: pointer; transition: background .2s; }
        .btn-secondary:hover { background: #4b5563; }
        .btn-confirm { padding: .6rem 1.25rem; background: linear-gradient(135deg, #10b981, #059669); color: #fff; border: none; border-radius: 8px; font-size: .875rem; font-weight: 600; font-family: inherit; cursor: pointer; transition: opacity .2s; }
        .btn-confirm:hover { opacity: .88; }
        .btn-confirm:disabled { opacity: .55; cursor: not-allowed; }
        .otp-timer { font-size: 2rem; font-weight: 700; color: #ef4444; text-align: center; margin: .5rem 0 1.25rem; }
        .otp-input { text-align: center; font-size: 1.6rem; letter-spacing: 6px; font-weight: 700; padding: .65rem 1rem; }
        .is-invalid { border-color: #ef4444 !important; }
        .invalid-feedback { color: #ef4444; font-size: .8rem; margin-top: .25rem; }

        /* Mobile */
        @media (max-width: 767px) {
            .login-brand { display: none; }
            .login-form-panel { background: linear-gradient(135deg, var(--brand) 0%, var(--brand2) 100%); padding: 1.5rem 1rem; }
            .login-card { box-shadow: 0 12px 48px rgba(0,0,0,.25); }
            .mobile-back { display: flex !important; }
        }
        .mobile-back { display: none; align-items: center; gap: .5rem; color: rgba(255,255,255,.8); text-decoration: none; font-size: .875rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
<div class="login-wrap">

    <!-- Brand Panel -->
    <div class="login-brand">
        <div class="brand-circle bc-1"></div>
        <div class="brand-circle bc-2"></div>
        <div class="brand-content">
            <a href="../../index.php" class="brand-back"><i class="bi bi-arrow-left-circle"></i> Back to Portal</a>
            <div class="brand-logo">
                <img src="../img/logo.png" alt="Logo">
                <div class="brand-logo-text">
                    <div class="sys">Feedback System</div>
                    <div class="sub">Resident Feedback &amp; Survey</div>
                </div>
            </div>
            <h1 class="brand-headline">Your Voice,<br><span>Our Priority</span></h1>
            <p class="brand-desc">Submit feedback, track responses, and help improve barangay services — all in one secure portal.</p>
            <ul class="feature-list">
                <li><span class="feat-dot"></span> Submit Feedback &amp; Surveys</li>
                <li><span class="feat-dot"></span> Real-time Response Tracking</li>
                <li><span class="feat-dot"></span> Secure Resident Accounts</li>
                <li><span class="feat-dot"></span> Barangay Service Ratings</li>
            </ul>
        </div>
    </div>

    <!-- Form Panel -->
    <div class="login-form-panel">
        <a href="../../index.php" class="mobile-back"><i class="bi bi-arrow-left-circle"></i> Back to Portal</a>

        <div class="login-card">
            <div class="card-logo">
                <img src="../img/logo.png" alt="Logo">
            </div>
            <div class="card-title">
                <h1>Resident Login</h1>
                <p>Sign in to access your feedback account</p>
            </div>

            <?php if (!empty($success_message) || !empty($forgot_message)): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?php echo !empty($success_message) ? $success_message : $forgot_message; ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error" id="errorAlert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span>
                        <span id="errorMessage"><?php echo $error; ?></span>
                        <?php if ($lockout_remaining > 0): ?>
                            <strong id="countdownTimer"></strong>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label class="form-label" for="username">Username or Email</label>
                    <div class="input-wrap">
                        <i class="bi bi-person input-icon"></i>
                        <input type="text" class="form-control" id="username" name="username"
                               placeholder="Enter username or email" required
                               <?php echo $lockout_remaining > 0 ? 'disabled' : ''; ?>>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrap">
                        <i class="bi bi-lock input-icon"></i>
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Enter your password" required
                               <?php echo $lockout_remaining > 0 ? 'disabled' : ''; ?>>
                        <button type="button" class="toggle-pwd" id="togglePwd" tabindex="-1">
                            <i class="bi bi-eye" id="togglePwdIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-meta">
                    <a href="#" id="openForgotModalLink">Forgot Password?</a>
                </div>

                <button type="submit" name="login" id="loginBtn" class="btn-login"
                        <?php echo $lockout_remaining > 0 ? 'disabled' : ''; ?>>
                    <i class="bi bi-box-arrow-in-right"></i> Sign In
                </button>
            </form>

            <div class="card-footer-link">
                <a href="../../index.php"><i class="bi bi-arrow-left"></i> Back to Home</a>
            </div>
        </div>
    </div>
</div>

<!-- Forgot Password Modal -->
<div class="modal-overlay <?php echo $show_forgot_modal ? 'active' : ''; ?>" id="forgotPasswordModal">
    <div class="modal-box">
        <div class="modal-head">
            <h3><i class="bi bi-key"></i> Forgot Password</h3>
            <button class="modal-close-btn" id="closeForgotModalBtn"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body">
            <?php if (!empty($forgot_error)): ?>
                <div class="alert alert-error" style="margin-bottom:1rem;">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?php echo $forgot_error; ?></span>
                </div>
            <?php endif; ?>
            <p style="color:#4b5563;font-size:.9rem;margin-bottom:1.1rem;">Enter your username or email and we'll send a one-time password to your registered email.</p>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="forgotPasswordForm">
                <div class="form-group">
                    <label class="form-label" for="forgot_identifier">Username or Email</label>
                    <div class="input-wrap">
                        <i class="bi bi-envelope input-icon"></i>
                        <input type="text" class="form-control" id="forgot_identifier" name="identifier" required>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" id="cancelForgotBtn">Cancel</button>
            <button type="submit" form="forgotPasswordForm" name="forgot_password" class="btn-confirm">
                <i class="bi bi-send"></i> Send OTP
            </button>
        </div>
    </div>
</div>

<!-- OTP Modal -->
<div class="modal-overlay <?php echo $show_otp_modal ? 'active' : ''; ?>" id="otpModal">
    <div class="modal-box">
        <div class="modal-head modal-head-green">
            <h3><i class="bi bi-shield-check"></i> Enter OTP</h3>
            <button class="modal-close-btn" id="closeOtpModalBtn" onclick="window.location.href='login.php'"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body" style="text-align:center;">
            <?php if (!empty($otp_error)): ?>
                <div class="alert alert-error" style="margin-bottom:1rem;text-align:left;">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?php echo $otp_error; ?></span>
                </div>
            <?php endif; ?>
            <p style="color:#4b5563;font-size:.9rem;margin-bottom:.5rem;">We've sent a 6-digit code to your registered email.</p>
            <div class="otp-timer" id="otpTimer">02:00</div>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="verifyOtpForm">
                <div class="form-group">
                    <input type="text" class="form-control otp-input" id="otp_input" name="otp"
                           placeholder="000000" maxlength="6" required>
                </div>
            </form>
            <div id="resendOtpContainer" style="display:none;margin-top:1rem;">
                <p style="color:#6b7280;font-size:.85rem;">Code expired?</p>
                <button type="button" class="btn-secondary" style="margin-top:.5rem;width:100%;"
                        onclick="document.getElementById('openForgotModalLink').click();document.getElementById('otpModal').classList.remove('active');">
                    Request New OTP
                </button>
            </div>
        </div>
        <div class="modal-footer" style="justify-content:center;">
            <button type="submit" form="verifyOtpForm" name="verify_otp" id="verifyOtpBtn" class="btn-confirm" style="width:100%;padding:.75rem;">
                <i class="bi bi-check-circle"></i> Verify OTP
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // Password toggle
    var pwd = document.getElementById('password');
    var toggleBtn = document.getElementById('togglePwd');
    var toggleIcon = document.getElementById('togglePwdIcon');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            var show = pwd.type === 'password';
            pwd.type = show ? 'text' : 'password';
            toggleIcon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    }

    // Ripple on sign in button
    var loginBtn = document.getElementById('loginBtn');
    if (loginBtn) {
        loginBtn.addEventListener('click', function (e) {
            var rect = this.getBoundingClientRect();
            var size = Math.max(rect.width, rect.height);
            var ripple = document.createElement('span');
            ripple.className = 'ripple';
            ripple.style.cssText = 'width:'+size+'px;height:'+size+'px;top:'+(e.clientY-rect.top-size/2)+'px;left:'+(e.clientX-rect.left-size/2)+'px;';
            this.appendChild(ripple);
            setTimeout(function(){ ripple.remove(); }, 600);
        });
    }

    // Forgot password modal
    var forgotModal   = document.getElementById('forgotPasswordModal');
    var openForgot    = document.getElementById('openForgotModalLink');
    var closeForgot   = document.getElementById('closeForgotModalBtn');
    var cancelForgot  = document.getElementById('cancelForgotBtn');

    function openForgotModal(e) { if(e) e.preventDefault(); forgotModal.classList.add('active'); document.body.style.overflow='hidden'; }
    function closeForgotModal() { forgotModal.classList.remove('active'); document.body.style.overflow=''; }

    if (openForgot)   openForgot.addEventListener('click', openForgotModal);
    if (closeForgot)  closeForgot.addEventListener('click', closeForgotModal);
    if (cancelForgot) cancelForgot.addEventListener('click', closeForgotModal);
    if (forgotModal)  forgotModal.addEventListener('click', function(e){ if(e.target===forgotModal) closeForgotModal(); });

    // OTP modal timer
    var otpModal = document.getElementById('otpModal');
    if (otpModal && otpModal.classList.contains('active')) {
        var timeLeft   = 120;
        var timerEl    = document.getElementById('otpTimer');
        var otpInput   = document.getElementById('otp_input');
        var verifyBtn  = document.getElementById('verifyOtpBtn');
        var resendBox  = document.getElementById('resendOtpContainer');

        if (otpInput) { setTimeout(function(){ otpInput.focus(); }, 100); }
        if (otpInput) otpInput.addEventListener('input', function(){ this.value = this.value.replace(/[^0-9]/g,''); });

        var countdown = setInterval(function() {
            if (timeLeft <= 0) {
                clearInterval(countdown);
                if (timerEl) { timerEl.textContent = '00:00'; timerEl.style.color = '#9ca3af'; }
                if (otpInput) otpInput.disabled = true;
                if (verifyBtn) verifyBtn.disabled = true;
                if (resendBox) resendBox.style.display = 'block';
            } else {
                var m = Math.floor(timeLeft / 60), s = timeLeft % 60;
                if (timerEl) {
                    timerEl.textContent = '0' + m + ':' + (s < 10 ? '0' : '') + s;
                    if (timeLeft < 30) timerEl.style.color = '#dc2626';
                }
                timeLeft--;
            }
        }, 1000);
    }

    // Forgot identifier sanitisation
    var forgotId = document.getElementById('forgot_identifier');
    if (forgotId) {
        forgotId.addEventListener('input', function () {
            var v = this.value.replace(/\s/g,'').replace(/[^a-zA-Z0-9@._-]/g,'').replace(/\.\./g,'.');
            if (v !== this.value) this.value = v;
        });
    }
});

// Lockout countdown
(function () {
    var rem = <?php echo $lockout_remaining; ?>;
    if (rem > 0) {
        var el  = document.getElementById('countdownTimer');
        var unm = document.getElementById('username');
        var pw  = document.getElementById('password');
        var btn = document.getElementById('loginBtn');
        function tick() {
            if (rem > 0) {
                if (el) el.textContent = ' (' + rem + 's)';
                rem--;
                setTimeout(tick, 1000);
            } else {
                if (unm) unm.disabled = false;
                if (pw)  pw.disabled  = false;
                if (btn) btn.disabled = false;
                var msg = document.getElementById('errorMessage');
                if (msg) msg.textContent = 'You can now try again.';
                if (el)  el.textContent = '';
                window.location.reload();
            }
        }
        tick();
    }
})();
</script>
</body>
</html>
