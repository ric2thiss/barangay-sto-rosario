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

// Handle create user form submission
$success_message = '';
if (isset($_POST['create_user'])) {
    $firstname = trim($conn->real_escape_string($_POST['firstname']));
    $lastname = trim($conn->real_escape_string($_POST['lastname']));
    $purok = trim($conn->real_escape_string($_POST['purok']));
    $username = trim($conn->real_escape_string($_POST['username']));
    $email = trim($conn->real_escape_string($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $create_errors = [];
    
    // Validation
    if (empty($firstname)) {
        $create_errors['firstname'] = 'First name is required';
    } elseif (strlen($firstname) < 2) {
        $create_errors['firstname'] = 'First name must be at least 2 characters';
    }
    
    if (empty($lastname)) {
        $create_errors['lastname'] = 'Last name is required';
    } elseif (strlen($lastname) < 2) {
        $create_errors['lastname'] = 'Last name must be at least 2 characters';
    }
    
    if (empty($purok)) {
        $create_errors['purok'] = 'Purok is required';
    }
    
    if (empty($username)) {
        $create_errors['username'] = 'Username is required';
    } elseif (strlen($username) < 4) {
        $create_errors['username'] = 'Username must be at least 4 characters';
    } else {
        $stmt = $conn->prepare("SELECT id FROM `profiling-system`.residents WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $create_errors['username'] = 'Username already exists';
        }
        $stmt->close();
    }
    
    if (!empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $create_errors['email'] = 'Please enter a valid email address';
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $create_errors['email'] = 'Email already registered';
            }
            $stmt->close();
        }
    }
    
    if (empty($password)) {
        $create_errors['password'] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $create_errors['password'] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirm_password) {
        $create_errors['confirm_password'] = 'Passwords do not match';
    }
    
    if (empty($create_errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            INSERT INTO `profiling-system`.residents 
            (first_name, surname, purok, username, password, user_role, account_status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'resident', 'active', NOW())
        ");
        
        $stmt->bind_param(
            "sssss", 
            $firstname, 
            $lastname, 
            $purok, 
            $username, 
            $hashed_password
        );
        
        if ($stmt->execute()) {
            $success_message = "Account created successfully! You can now login.";
            
            // Clear session data
            unset($_SESSION['show_create_modal']);
            unset($_SESSION['create_user_errors']);
            unset($_SESSION['create_user_values']);
        } else {
            $create_errors['database'] = "Error creating account: " . $stmt->error;
            $_SESSION['create_user_errors'] = $create_errors;
            $_SESSION['show_create_modal'] = true;
        }
        
        $stmt->close();
    } else {
        // Store errors in session to display in modal
        $_SESSION['create_user_errors'] = $create_errors;
        $_SESSION['create_user_values'] = [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'purok' => $purok,
            'username' => $username,
            'email' => $email
        ];
        $_SESSION['show_create_modal'] = true;
    }
}

// Check if we should show the create user modal
$show_create_modal = isset($_SESSION['show_create_modal']) && $_SESSION['show_create_modal'];
$create_user_errors = isset($_SESSION['create_user_errors']) ? $_SESSION['create_user_errors'] : [];
$create_user_values = isset($_SESSION['create_user_values']) ? $_SESSION['create_user_values'] : [];

// Handle forgot password
$forgot_message = '';
$forgot_error = '';
if (isset($_POST['forgot_password'])) {
    // Strip all whitespaces safely (including non-breaking spaces)
    $raw_input = preg_replace('/\s+/u', '', $_POST['identifier']);
    $identifier = $conn->real_escape_string($raw_input);
    
    if (empty($identifier)) {
        $forgot_error = "Please enter your username";
        $_SESSION['show_forgot_modal'] = true;
    } else {
        $stmt = $conn->prepare("SELECT id FROM `profiling-system`.residents WHERE username = ? AND user_role = 'resident'");
        $stmt->bind_param("s", $identifier);
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
    // Hide modals if the current POST request is not related to them
    if (!isset($_POST['create_user'])) {
        $show_create_modal = false;
        unset($_SESSION['show_create_modal'], $_SESSION['create_user_errors'], $_SESSION['create_user_values']);
    }
    if (!isset($_POST['forgot_password'])) {
        $show_forgot_modal = false;
        unset($_SESSION['show_forgot_modal']);
    }
    if (!isset($_POST['verify_otp']) && !isset($_POST['forgot_password'])) {
        $show_otp_modal = false;
        unset($_SESSION['show_otp_modal']);
    }
} else {
    // Clear session data on GET requests after retrieving it for display
    if ($show_create_modal) {
        unset($_SESSION['show_create_modal'], $_SESSION['create_user_errors'], $_SESSION['create_user_values']);
    }
    if ($show_forgot_modal) {
        unset($_SESSION['show_forgot_modal']);
    }
    if ($show_otp_modal) {
        unset($_SESSION['show_otp_modal']);
    }
}


if (isset($_SESSION['user_id']) && !isset($_GET['preview'])) {
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
        $sql = "SELECT id, username, password, user_role AS user_type FROM `profiling-system`.residents WHERE username = ? AND user_role = 'resident' AND account_status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
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
                $_SESSION['user_type'] = $user['user_type'];
                
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
    <title>User Login - Feedback System</title>
    <link rel="icon" href="../img/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            background: #f0f7ff;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border: 2px solid #bae6fd;
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
        }

        .alert-error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 10px;
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
            font-weight: 500;
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
        }

        .form-control:focus {
            outline: none;
            border-color: #1F3A93;
            background: white;
            box-shadow: 0 0 0 3px rgba(31, 58, 147, 0.1);
        }

        .form-control::placeholder {
            color: #9ca3af;
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
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(31, 58, 147, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .demo-credentials {
            background: #f0f7ff;
            padding: 20px;
            border-radius: 12px;
            margin-top: 25px;
            border: 1px solid #bae6fd;
        }

        .demo-credentials p {
            margin-bottom: 8px;
            color: #1a317d;
        }

        .demo-credentials strong {
            color: #1a317d;
            font-weight: 600;
        }

        .demo-credentials code {
            background: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            color: #1F3A93;
            font-weight: 600;
            border: 1px solid #bae6fd;
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
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .back-to-home a:hover {
            color: #152c71;
            text-decoration: underline;
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
        }

        /* Animation */
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

        /* Ripple effect for button */
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
    /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            backdrop-filter: blur(3px);
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalSlideIn 0.4s ease;
            border: 3px solid #1F3A93;
            display: flex;
            flex-direction: column;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #1F3A93, #152c71);
            color: white;
            padding: 25px 30px;
            border-radius: 17px 17px 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .modal-header h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 22px;
            margin: 0;
            font-weight: 600;
        }

        .modal-header .close-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
            transition: all 0.3s;
        }

        .modal-header .close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 30px;
            overflow-y: auto;
        }

        .modal-footer {
            padding: 20px 30px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            gap: 15px;
            border-radius: 0 0 17px 17px;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -10px;
            margin-left: -10px;
        }

        .col {
            flex: 1;
            padding: 0 10px;
            min-width: 250px;
        }

        .mb-3 { margin-bottom: 1rem; }
        .mb-4 { margin-bottom: 1.5rem; }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #1a317d;
            font-weight: 500;
        }

        .required::after {
            content: " *";
            color: #ef4444;
        }

        .text-muted {
            color: #6b7280;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
        }

        .is-invalid {
            border-color: #ef4444 !important;
        }

        .invalid-feedback {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .alert-success {
            background: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .register-link {
            text-align: center;
            margin-top: 15px;
        }
        
        .register-link a {
            color: #1F3A93;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }
        
        .register-link a:hover {
            text-decoration: underline;
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
                <h2>User Login</h2>
                <p>Access your feedback account</p>
            </div>
            
            <?php if (!empty($success_message) || !empty($forgot_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo !empty($success_message) ? $success_message : $forgot_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error" id="errorAlert">
                    <i class="fas fa-exclamation-circle"></i>
                    <span id="errorMessage"><?php echo $error; ?></span>
                    <?php if ($lockout_remaining > 0): ?>
                        <span id="countdownTimer" style="font-weight: bold;"></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="username">Username or Email:</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Enter username or email" value="" required <?php echo $lockout_remaining > 0 ? 'disabled' : ''; ?>>
                </div>
                
                <div class="form-group">
                    <label for="password">Password: <a href="#" id="openForgotModalLink" style="float: right; font-size: 0.85rem; color: #1F3A93; text-decoration: none;">Forgot Password?</a></label>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Enter password" value="" required <?php echo $lockout_remaining > 0 ? 'disabled' : ''; ?>>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="login" id="loginBtn" class="btn" style="width: 100%; position: relative; overflow: hidden;" <?php echo $lockout_remaining > 0 ? 'disabled' : ''; ?>>
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </div>
                
                <div class="register-link">
                    <p>Don't have an account? <a id="openCreateUserModalLink" <?php echo $lockout_remaining > 0 ? 'style="pointer-events: none; opacity: 0.5;"' : ''; ?>>Create New Account</a></p>
                </div>
            </form>
            
            <!-- <div class="demo-credentials">
                <p><strong>Demo Credentials:</strong></p>
                <p>Username: <code>user1</code> or <code>user2</code></p>
                <p>Password: <code>password123</code></p>
            </div>
             -->
            <div class="back-to-home">
                <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal-overlay <?php echo $show_create_modal ? 'active' : ''; ?>" id="createUserModal">
        <div class="modal">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-user-plus"></i> Create New Account
                </h3>
                <button class="close-btn" id="closeCreateModalBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <?php if (!empty($create_user_errors) && !isset($create_user_errors['database'])): ?>
                    <div class="alert alert-error" style="margin-bottom: 20px;">
                        <i class="fas fa-exclamation-circle"></i>
                        Please fix the errors below.
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-success" style="background: #f0f7ff; border-color: #bae6fd; color: #1a317d; margin-bottom: 20px;">
                    <i class="fas fa-info-circle"></i> Fill out the form below to create your account.
                </div>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="createUserForm">
                    <div class="row mb-3">
                        <div class="col">
                            <label for="firstname" class="form-label required">First Name</label>
                            <input type="text" class="form-control <?php echo isset($create_user_errors['firstname']) ? 'is-invalid' : ''; ?>" 
                                   id="firstname" name="firstname" 
                                   value="<?php echo htmlspecialchars($create_user_values['firstname'] ?? ''); ?>" required>
                            <?php if (isset($create_user_errors['firstname'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $create_user_errors['firstname']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col">
                            <label for="lastname" class="form-label required">Last Name</label>
                            <input type="text" class="form-control <?php echo isset($create_user_errors['lastname']) ? 'is-invalid' : ''; ?>" 
                                   id="lastname" name="lastname" 
                                   value="<?php echo htmlspecialchars($create_user_values['lastname'] ?? ''); ?>" required>
                            <?php if (isset($create_user_errors['lastname'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $create_user_errors['lastname']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col">
                            <label for="purok" class="form-label required">Purok/Zone</label>
                            <input type="text" class="form-control <?php echo isset($create_user_errors['purok']) ? 'is-invalid' : ''; ?>" 
                                   id="purok" name="purok" 
                                   value="<?php echo htmlspecialchars($create_user_values['purok'] ?? ''); ?>" required>
                            <?php if (isset($create_user_errors['purok'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $create_user_errors['purok']; ?>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted">Enter the purok/zone where you reside</small>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col">
                            <label for="username_reg" class="form-label required">Username</label>
                            <input type="text" class="form-control <?php echo isset($create_user_errors['username']) ? 'is-invalid' : ''; ?>" 
                                   id="username_reg" name="username" 
                                   value="<?php echo htmlspecialchars($create_user_values['username'] ?? ''); ?>" required>
                            <?php if (isset($create_user_errors['username'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $create_user_errors['username']; ?>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted">Minimum 4 characters</small>
                        </div>
                        
                        <div class="col">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control <?php echo isset($create_user_errors['email']) ? 'is-invalid' : ''; ?>" 
                                   id="email" name="email" 
                                   value="<?php echo htmlspecialchars($create_user_values['email'] ?? ''); ?>">
                            <?php if (isset($create_user_errors['email'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $create_user_errors['email']; ?>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted">Optional</small>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col">
                            <label for="password_reg" class="form-label required">Password</label>
                            <input type="password" class="form-control <?php echo isset($create_user_errors['password']) ? 'is-invalid' : ''; ?>" 
                                   id="password_reg" name="password" required>
                            <?php if (isset($create_user_errors['password'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $create_user_errors['password']; ?>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        
                        <div class="col">
                            <label for="confirm_password" class="form-label required">Confirm Password</label>
                            <input type="password" class="form-control <?php echo isset($create_user_errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                   id="confirm_password" name="confirm_password" required>
                            <?php if (isset($create_user_errors['confirm_password'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $create_user_errors['confirm_password']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="resetCreateFormBtn" style="width: auto; padding: 12px 24px;">
                    <i class="fas fa-undo"></i> Clear Form
                </button>
                <button type="submit" form="createUserForm" name="create_user" class="btn btn-success" style="width: auto; padding: 12px 24px;">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal-overlay <?php echo $show_forgot_modal ? 'active' : ''; ?>" id="forgotPasswordModal">
        <div class="modal" style="max-width: 500px;">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-key"></i> Forgot Password
                </h3>
                <button class="close-btn" id="closeForgotModalBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <?php if (!empty($forgot_error)): ?>
                    <div class="alert alert-error" style="margin-bottom: 20px;">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $forgot_error; ?>
                    </div>
                <?php endif; ?>
                
                <p style="margin-bottom: 20px; color: #4b5563;">Enter your username or email below and we'll send an OTP to your registered email address.</p>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="forgotPasswordForm">
                    <div class="form-group">
                        <label for="forgot_identifier" class="form-label required">Username or Email</label>
                        <input type="text" class="form-control" id="forgot_identifier" name="identifier" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelForgotBtn" style="width: auto; padding: 12px 24px;">
                    Cancel
                </button>
                <button type="submit" form="forgotPasswordForm" name="forgot_password" class="btn btn-success" style="width: auto; padding: 12px 24px;">
                    <i class="fas fa-paper-plane"></i> Send Link
                </button>
            </div>
        </div>
    </div>

    <!-- OTP Verification Modal -->
    <div class="modal-overlay <?php echo $show_otp_modal ? 'active' : ''; ?>" id="otpModal">
        <div class="modal" style="max-width: 400px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #10b981, #059669);">
                <h3>
                    <i class="fas fa-shield-alt"></i> Enter OTP
                </h3>
                <button class="close-btn" id="closeOtpModalBtn" onclick="window.location.href='login.php'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" style="text-align: center;">
                <?php if (!empty($otp_error)): ?>
                    <div class="alert alert-error" style="margin-bottom: 20px;">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $otp_error; ?>
                    </div>
                <?php endif; ?>
                
                <p style="margin-bottom: 20px; color: #4b5563;">We've sent a 6-digit code to your registered email address.</p>
                
                <h4 id="otpTimer" style="font-size: 2rem; color: #ef4444; margin-bottom: 20px;">02:00</h4>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="verifyOtpForm">
                    <div class="form-group">
                        <input type="text" class="form-control" id="otp_input" name="otp" 
                               placeholder="Enter 6-digit OTP" 
                               maxlength="6" 
                               style="text-align: center; font-size: 1.5rem; letter-spacing: 5px; font-weight: bold;" required>
                    </div>
                </form>
                
                <div id="resendOtpContainer" style="display: none; margin-top: 15px;">
                    <p style="color: #6b7280; font-size: 0.9rem;">Didn't receive the code or expired?</p>
                    <button type="button" class="btn btn-secondary" style="width: 100%; margin-top: 10px;" onclick="document.getElementById('openForgotModalLink').click(); document.getElementById('otpModal').classList.remove('active');">
                        Request New OTP
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" form="verifyOtpForm" name="verify_otp" id="verifyOtpBtn" class="btn btn-success" style="width: 100%; padding: 12px 24px;">
                    <i class="fas fa-check-circle"></i> Verify OTP
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const otpModal = document.getElementById('otpModal');
            if (otpModal && otpModal.classList.contains('active')) {
                let timeLeft = 120; // 2 minutes
                const timerDisplay = document.getElementById('otpTimer');
                const otpInput = document.getElementById('otp_input');
                const verifyBtn = document.getElementById('verifyOtpBtn');
                const resendContainer = document.getElementById('resendOtpContainer');
                
                // Focus the input safely
                setTimeout(() => { if(otpInput) otpInput.focus(); }, 100);
                
                // Verify only numbers
                if (otpInput) {
                    otpInput.addEventListener('input', function() {
                        this.value = this.value.replace(/[^0-9]/g, '');
                    });
                }

                const countdown = setInterval(function() {
                    if (timeLeft <= 0) {
                        clearInterval(countdown);
                        if (timerDisplay) {
                            timerDisplay.textContent = "00:00";
                            timerDisplay.style.color = "#9ca3af";
                        }
                        if (otpInput) otpInput.disabled = true;
                        if (verifyBtn) {
                            verifyBtn.disabled = true;
                            verifyBtn.style.opacity = '0.5';
                        }
                        if (resendContainer) resendContainer.style.display = 'block';
                    } else {
                        let m = Math.floor(timeLeft / 60);
                        let s = timeLeft % 60;
                        if (timerDisplay) {
                            timerDisplay.textContent = '0' + m + ':' + (s < 10 ? '0' : '') + s;
                            
                            // Change color if less than 30 seconds
                            if (timeLeft < 30) {
                                timerDisplay.style.color = '#dc2626';
                                timerDisplay.style.animation = 'pulse 1s infinite alternate';
                            }
                        }
                        timeLeft--;
                    }
                }, 1000);
                
                // CSS for pulse
                if (!document.getElementById('pulseStyle')) {
                    const style = document.createElement('style');
                    style.id = 'pulseStyle';
                    style.innerHTML = `@keyframes pulse { from { opacity: 1; } to { opacity: 0.5; } }`;
                    document.head.appendChild(style);
                }
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add ripple effect to login button
            const loginBtn = document.querySelector('.btn');
            loginBtn.addEventListener('click', function(e) {
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

            // Add focus effect to form inputs
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('focused');
                });
            });

            // Add animation to form on load
            const formGroups = document.querySelectorAll('.form-group');
            formGroups.forEach((group, index) => {
                group.style.animation = `fadeInUp 0.6s ease-out ${index * 0.1 + 0.2}s both`;
            });

            // Add typing animation to placeholder text
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            
            let usernamePlaceholders = ['user1', 'user2', 'Enter your username...'];
            let passwordPlaceholders = ['password123', 'Enter your password...'];
            let currentPlaceholder = 0;
            
            function cyclePlaceholders() {
                currentPlaceholder = (currentPlaceholder + 1) % usernamePlaceholders.length;
                usernameInput.setAttribute('placeholder', usernamePlaceholders[currentPlaceholder]);
                passwordInput.setAttribute('placeholder', passwordPlaceholders[currentPlaceholder % passwordPlaceholders.length]);
            }
            
            // Change placeholder every 3 seconds if input is empty
            setInterval(() => {
                if (!usernameInput.value && !passwordInput.value) {
                    cyclePlaceholders();
                }
            }, 3000);

            // Modal Functionality
            const createUserModal = document.getElementById('createUserModal');
            const openCreateUserModalLink = document.getElementById('openCreateUserModalLink');
            const closeCreateModalBtn = document.getElementById('closeCreateModalBtn');
            const resetCreateFormBtn = document.getElementById('resetCreateFormBtn');
            const createUserForm = document.getElementById('createUserForm');

            function openModal() {
                createUserModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeModal() {
                createUserModal.classList.remove('active');
                document.body.style.overflow = '';
            }

            openCreateUserModalLink.addEventListener('click', openModal);
            closeCreateModalBtn.addEventListener('click', closeModal);

            // Close modal when clicking outside
            createUserModal.addEventListener('click', function(e) {
                if (e.target === createUserModal) {
                    closeModal();
                }
            });

            // Reset form
            resetCreateFormBtn.addEventListener('click', function() {
                createUserForm.reset();
            });

            // Forgot Password Functionality
            const forgotPasswordModal = document.getElementById('forgotPasswordModal');
            const openForgotModalLink = document.getElementById('openForgotModalLink');
            const closeForgotModalBtn = document.getElementById('closeForgotModalBtn');
            const cancelForgotBtn = document.getElementById('cancelForgotBtn');

            function openForgotModal(e) {
                if (e) e.preventDefault();
                forgotPasswordModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeForgotModal() {
                forgotPasswordModal.classList.remove('active');
                document.body.style.overflow = '';
            }

            openForgotModalLink.addEventListener('click', openForgotModal);
            closeForgotModalBtn.addEventListener('click', closeForgotModal);
            cancelForgotBtn.addEventListener('click', closeForgotModal);

            forgotPasswordModal.addEventListener('click', function(e) {
                if (e.target === forgotPasswordModal) {
                    closeForgotModal();
                }
            });

            // Password validation
            createUserForm.addEventListener('submit', function(e) {
                const password = document.getElementById('password_reg').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    return false;
                }
                
                if (password.length < 6) {
                    e.preventDefault();
                    alert('Password must be at least 6 characters long!');
                    return false;
                }
                
                return true;
            });

            // Name validation and capitalization
            function setupNameValidation(inputId) {
                const input = document.getElementById(inputId);
                if (!input) return;

                // Create error container if it doesn't exist
                let errorDiv = input.parentNode.querySelector('.invalid-feedback-client');
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback invalid-feedback-client';
                    errorDiv.style.display = 'none';
                    errorDiv.style.color = '#ef4444';
                    errorDiv.style.fontSize = '0.875rem';
                    errorDiv.style.marginTop = '0.25rem';
                    input.parentNode.appendChild(errorDiv);
                }

                input.addEventListener('input', function() {
                    let val = this.value;
                    let cursorPosition = this.selectionStart;
                    
                    // Automatic capitalization
                    const words = val.split(' ');
                    for (let i = 0; i < words.length; i++) {
                        if (words[i].length > 0) {
                            words[i] = words[i].charAt(0).toUpperCase() + words[i].slice(1);
                        }
                    }
                    const capitalized = words.join(' ');
                    
                    if (val !== capitalized) {
                        this.value = capitalized;
                        this.setSelectionRange(cursorPosition, cursorPosition);
                        val = capitalized;
                    }

                    // Validation
                    const invalidChars = /[^a-zA-ZñÑ\s]/g;
                    if (invalidChars.test(val)) {
                        this.classList.add('is-invalid');
                        errorDiv.textContent = 'Only letters (a-z, ñ/Ñ) are allowed.';
                        errorDiv.style.display = 'block';
                    } else {
                        // Only remove invalid class if it was added by this script
                        // logic: checking if php error exists might be complex, 
                        // but generally if valid now, we can remove the red border from client side check
                        this.classList.remove('is-invalid');
                        errorDiv.style.display = 'none';
                    }
                });
            }

            setupNameValidation('firstname');
            setupNameValidation('lastname');

            // Purok/Zone numeric validation
            function setupNumericValidation(inputId) {
                const input = document.getElementById(inputId);
                if (!input) return;

                // Create error container if it doesn't exist
                let errorDiv = input.parentNode.querySelector('.invalid-feedback-client-numeric');
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback invalid-feedback-client-numeric';
                    errorDiv.style.display = 'none';
                    errorDiv.style.color = '#ef4444';
                    errorDiv.style.fontSize = '0.875rem';
                    errorDiv.style.marginTop = '0.25rem';
                    input.parentNode.appendChild(errorDiv);
                }

                input.addEventListener('input', function() {
                    let val = this.value;
                    
                    // Allow only digits
                    const numericVal = val.replace(/[^0-9]/g, '');
                    
                    if (val !== numericVal) {
                        this.value = numericVal;
                        this.classList.add('is-invalid');
                        errorDiv.textContent = 'Only numbers are allowed.';
                        errorDiv.style.display = 'block';
                    } else {
                        // Only remove invalid class if it was added by this script (or check if valid)
                        // Simple toggle for now based on current input
                        if (val.length > 0) {
                             this.classList.remove('is-invalid');
                             errorDiv.style.display = 'none';
                        }
                    }
                });
            }

            setupNumericValidation('purok');

            // Username validation
            function setupUsernameValidation(inputId) {
                const input = document.getElementById(inputId);
                if (!input) return;

                // Create error container if it doesn't exist
                let errorDiv = input.parentNode.querySelector('.invalid-feedback-client-username');
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback invalid-feedback-client-username';
                    errorDiv.style.display = 'none';
                    errorDiv.style.color = '#ef4444';
                    errorDiv.style.fontSize = '0.875rem';
                    errorDiv.style.marginTop = '0.25rem';
                    input.parentNode.appendChild(errorDiv);
                }

                input.addEventListener('input', function() {
                    let val = this.value;
                    
                    // Allow letters (A-Z, a-z), numbers (0-9), underscore (_), and dot (.)
                    const validVal = val.replace(/[^a-zA-Z0-9_.]/g, '');
                    
                    if (val !== validVal) {
                        this.value = validVal;
                        this.classList.add('is-invalid');
                        errorDiv.textContent = 'Only letters, numbers, underscore (_), and dot (.) are allowed. No spaces.';
                        errorDiv.style.display = 'block';
                    } else {
                         if (val.length > 0) {
                             this.classList.remove('is-invalid');
                             errorDiv.style.display = 'none';
                         }
                    }
                });
            }

            setupUsernameValidation('username_reg');

            // Email validation
            function setupEmailValidation(inputId) {
                const input = document.getElementById(inputId);
                if (!input) return;

                // Create error container if it doesn't exist
                let errorDiv = input.parentNode.querySelector('.invalid-feedback-client-email');
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback invalid-feedback-client-email';
                    errorDiv.style.display = 'none';
                    errorDiv.style.color = '#ef4444';
                    errorDiv.style.fontSize = '0.875rem';
                    errorDiv.style.marginTop = '0.25rem';
                    input.parentNode.appendChild(errorDiv);
                }

                input.addEventListener('input', function() {
                    let val = this.value;
                    let originalVal = val;
                    
                    // Remove ALL spaces (leading, trailing, middle, double)
                    val = val.replace(/\s/g, '');
                    
                    // Remove other disallowed characters
                    val = val.replace(/[^a-zA-Z0-9@._-]/g, '');
                    
                    // Prevent double dots
                    val = val.replace(/\.\./g, '.');

                    if (val !== originalVal) {
                        this.value = val;
                        this.classList.add('is-invalid');
                        errorDiv.textContent = 'Spaces, special symbols (except @ . _ -), and double dots are not allowed.';
                        errorDiv.style.display = 'block';
                    } else {
                        if (val.length > 0) {
                            this.classList.remove('is-invalid');
                            errorDiv.style.display = 'none';
                        }
                    }
                });
            }

            setupEmailValidation('email');
            setupEmailValidation('forgot_identifier'); // Allows all valid chars for both username and email

            // Password validation
            function setupPasswordValidation(passwordId, confirmId) {
                const passwordInput = document.getElementById(passwordId);
                const confirmInput = document.getElementById(confirmId);

                if (!passwordInput || !confirmInput) return;

                // Create error container
                const createErrorDiv = (input, className) => {
                    let div = input.parentNode.querySelector('.' + className);
                    if (!div) {
                        div = document.createElement('div');
                        div.className = 'invalid-feedback ' + className;
                        div.style.display = 'none';
                        div.style.color = '#ef4444';
                        div.style.fontSize = '0.875rem';
                        div.style.marginTop = '0.25rem';
                        input.parentNode.appendChild(div);
                    }
                    return div;
                };

                const passError = createErrorDiv(passwordInput, 'invalid-feedback-client-password');
                const confirmError = createErrorDiv(confirmInput, 'invalid-feedback-client-confirm');

                function validatePassword() {
                    let val = passwordInput.value;
                    const originalVal = val;

                    // Remove spaces
                    val = val.replace(/\s/g, '');
                    if (val !== originalVal) {
                        passwordInput.value = val;
                    }

                    // Only validate if there is input
                    if (val.length === 0) {
                         passwordInput.classList.remove('is-invalid');
                         passError.style.display = 'none';
                         // Re-validate match if password cleared
                         validateMatch();
                         return; 
                    }

                    let errors = [];
                    // Length 8-12
                    if (val.length < 8 || val.length > 12) {
                        errors.push("8-12 characters");
                    }
                    // Uppercase
                    if (!/[A-Z]/.test(val)) {
                        errors.push("uppercase letter");
                    }
                    // Lowercase
                    if (!/[a-z]/.test(val)) {
                        errors.push("lowercase letter");
                    }
                    // Number
                    if (!/[0-9]/.test(val)) {
                        errors.push("number");
                    }
                    // Special char
                    if (!/[!@#$%^&*(),.?":{}|<>]/.test(val)) {
                        errors.push("special character");
                    }

                    if (errors.length > 0) {
                        passwordInput.classList.add('is-invalid');
                        passError.textContent = 'Must include: ' + errors.join(', ');
                        passError.style.display = 'block';
                    } else {
                        passwordInput.classList.remove('is-invalid');
                        passError.style.display = 'none';
                    }
                    
                    // Always validate match when password changes
                    if (confirmInput.value.length > 0) validateMatch();
                }

                function validateMatch() {
                    let val = confirmInput.value;
                    const originalVal = val;
                    
                    // Remove spaces from confirm password too
                    val = val.replace(/\s/g, '');
                    if (val !== originalVal) {
                        confirmInput.value = val;
                    }

                    if (val.length > 0 && val !== passwordInput.value) {
                        confirmInput.classList.add('is-invalid');
                        confirmError.textContent = 'Passwords do not match.';
                        confirmError.style.display = 'block';
                    } else {
                        confirmInput.classList.remove('is-invalid');
                        confirmError.style.display = 'none';
                    }
                }

                passwordInput.addEventListener('input', validatePassword);
                confirmInput.addEventListener('input', validateMatch);
            }

            setupPasswordValidation('password_reg', 'confirm_password');
        });
        
        // Lockout countdown timer
        (function() {
            var lockoutRemaining = <?php echo $lockout_remaining; ?>;
            
            if (lockoutRemaining > 0) {
                var countdownEl = document.getElementById('countdownTimer');
                var usernameInput = document.getElementById('username');
                var passwordInput = document.getElementById('password');
                var loginBtn = document.getElementById('loginBtn');
                var createAccountLink = document.getElementById('openCreateUserModalLink');
                var forgotPasswordLink = document.getElementById('openForgotModalLink');
                
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
                        if (createAccountLink) {
                            createAccountLink.style.pointerEvents = '';
                            createAccountLink.style.opacity = '';
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