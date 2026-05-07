<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Access control - ensure user has valid session
if (!isset($_SESSION['can_reset_password']) || $_SESSION['can_reset_password'] !== true || !isset($_SESSION['reset_email'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied. Please start the password reset process again.']);
    exit();
}

require_once 'connection.php';
require_once '../vendor/autoload.php';
require_once '../config/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Get the action from the request
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'generate_otp':
        generateOTP();
        break;
    case 'verify_otp':
        verifyOTP();
        break;
    case 'resend_otp':
        resendOTP();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function generateOTP() {
    global $conn;
    
    $email = $_SESSION['reset_email'];
    
    // Generate 6-digit OTP
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Hash the OTP for secure storage
    $hashed_otp = password_hash($otp, PASSWORD_DEFAULT);
    
    // Set expiry time (5 minutes from now)
    $expiry_time = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    // Update database with new OTP
    $stmt = $conn->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE emailAddress = ?");
    $stmt->bind_param("sss", $hashed_otp, $expiry_time, $email);
    
    if ($stmt->execute()) {
        // Send OTP via email
        if (sendOTPEmail($email, $otp)) {
            echo json_encode([
                'success' => true,
                'message' => 'OTP has been sent to your email address.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'OTP generated but failed to send email. Please try again.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to generate OTP. Please try again.'
        ]);
    }
    
    $stmt->close();
}

function verifyOTP() {
    global $conn;
    
    $email = $_SESSION['reset_email'];
    $input_otp = $_POST['otp'] ?? '';
    
    if (empty($input_otp)) {
        echo json_encode(['success' => false, 'message' => 'OTP is required.']);
        return;
    }
    
    // Get user's OTP data from database
    $stmt = $conn->prepare("SELECT otp_code, otp_expiry FROM users WHERE emailAddress = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        $stored_otp = $user_data['otp_code'];
        $expiry_time = $user_data['otp_expiry'];
        
        // Check if OTP exists
        if (empty($stored_otp)) {
            echo json_encode([
                'success' => false,
                'message' => 'No OTP found. Please request a new OTP.',
                'expired' => true
            ]);
            return;
        }
        
        // Check if OTP has expired
        if (strtotime($expiry_time) < time()) {
            echo json_encode([
                'success' => false,
                'message' => 'Your OTP has expired. Please resend a new OTP.',
                'expired' => true
            ]);
            return;
        }
        
        // Verify the OTP
        if (password_verify($input_otp, $stored_otp)) {
            // Clear OTP data after successful verification
            $clear_stmt = $conn->prepare("UPDATE users SET otp_code = NULL, otp_expiry = NULL WHERE emailAddress = ?");
            $clear_stmt->bind_param("s", $email);
            $clear_stmt->execute();
            $clear_stmt->close();
            
            // Set session for password reset
            $_SESSION['otp_verified'] = true;
            
            echo json_encode([
                'success' => true,
                'message' => 'OTP verified successfully. Redirecting to password reset...'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'The OTP you entered is incorrect.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'User not found.'
        ]);
    }
    
    $stmt->close();
}

function resendOTP() {
    // Resend OTP is the same as generating a new one
    generateOTP();
}

function sendOTPEmail($email, $otp) {
    try {
        $mail = new PHPMailer(true);
        
        // Get email configuration
        $email_config = require '../config/email_config.php';
        
        // Development mode - just log the OTP
        if ($email_config['development_mode']) {
            if ($email_config['log_otp_in_dev']) {
                error_log("DEVELOPMENT MODE - OTP for {$email}: {$otp}");
            }
            return true;
        }
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $email_config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $email_config['username'];
        $mail->Password   = $email_config['password'];
        $mail->SMTPSecure = $email_config['smtp_secure'];
        $mail->Port       = $email_config['port'];
        
        // Recipients
        $mail->setFrom($email_config['from_email'], $email_config['from_name']);
        $mail->addAddress($email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset OTP - Veterinary Clinic';
        
        $mail->Body = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Password Reset OTP - Veterinary Clinic</title>
        </head>
        <body style='margin:0; padding:0; background-color:#f4f4f5; font-family: Inter, Arial, sans-serif;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#f4f4f5; padding: 40px 20px;'>
                <tr>
                    <td align='center'>
                        <table width='600' cellpadding='0' cellspacing='0' style='max-width:600px; width:100%; background-color:#ffffff; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;'>

                            <!-- Header -->
                            <tr>
                                <td style='background-color:#2b2b2b; padding: 30px 40px; text-align: left;'>
                                    <p style='margin:0; font-size:20px; font-weight:700; color:#ffffff; letter-spacing:0.03em;'>🐾 Veterinary Clinic</p>
                                    <p style='margin:8px 0 0; font-size:13px; color:#9ca3af; font-weight:400;'>Secure Patient & Owner Portal</p>
                                </td>
                            </tr>

                            <!-- Title Bar -->
                            <tr>
                                <td style='background-color:#0d9488; padding: 12px 40px;'>
                                    <p style='margin:0; font-size:13px; font-weight:600; color:#ffffff; text-transform:uppercase; letter-spacing:0.08em;'>Password Reset — One-Time Password</p>
                                </td>
                            </tr>

                            <!-- Body -->
                            <tr>
                                <td style='padding: 40px;'>
                                    <p style='margin:0 0 16px; font-size:15px; color:#1f2937;'>Hello,</p>
                                    <p style='margin:0 0 28px; font-size:15px; color:#4b5563; line-height:1.6;'>We received a request to reset your password for your Veterinary Clinic account. Use the OTP code below to continue. This code is valid for <strong style='color:#0d9488;'>5 minutes</strong> and can only be used once.</p>

                                    <!-- OTP Box -->
                                    <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:28px;'>
                                        <tr>
                                            <td align='center'>
                                                <div style='display:inline-block; background-color:#f0fdfa; color:#0d9488; font-size:36px; font-weight:800; letter-spacing:8px; padding:20px 40px; font-family: Courier New, Courier, monospace; border: 2px dashed #0d9488; border-radius:8px; text-align:center;'>{$otp}</div>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Warning box -->
                                    <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:24px; border-left: 4px solid #0d9488; background-color:#f9fafb;'>
                                        <tr>
                                            <td style='padding:16px 20px;'>
                                                <p style='margin:0; font-size:13px; color:#6b7280; line-height:1.6;'>⚠️ <strong style='color:#1f2937;'>Security Notice:</strong> Never share this OTP with anyone. Veterinary Clinic will never ask for your OTP via phone or chat.</p>
                                            </td>
                                        </tr>
                                    </table>

                                    <p style='margin:0; font-size:13px; color:#9ca3af;'>If you did not request a password reset, you can safely ignore this email. Your account remains secure.</p>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style='padding: 24px 40px; background-color:#f9fafb; border-top: 1px solid #e5e7eb;'>
                                    <p style='margin:0 0 4px; font-size:12px; color:#9ca3af; text-align:center;'>&copy; 2026 Veterinary Clinic. All rights reserved.</p>
                                    <p style='margin:0; font-size:12px; color:#d1d5db; text-align:center;'>This is an automated message. Please do not reply to this email.</p>
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>";
        
        $mail->AltBody = "Hello,\n\nYou have requested to reset your password for your Veterinary Clinic account.\n\nYour One-Time Password (OTP): {$otp}\n\nThis OTP will expire in 5 minutes. Do not share this code with anyone.\n\nIf you did not request a password reset, please ignore this email.\n\n© 2026 Veterinary Clinic. All Rights Reserved.";
        
        $mail->send();
        
        // Log successful email sending
        error_log("OTP email sent successfully to {$email}");
        return true;
        
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        
        // Fallback to development mode if email fails
        error_log("Email failed, logging OTP for development: OTP for {$email}: {$otp}");
        return true; // Return true for testing, change to false in production
    }
}

$conn->close();
?>
