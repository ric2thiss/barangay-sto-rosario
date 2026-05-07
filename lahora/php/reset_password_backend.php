<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Access control - ensure user has valid session
if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true || !isset($_SESSION['reset_email'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied. Please start the password reset process again.']);
    exit();
}

require_once 'connection.php';

header('Content-Type: application/json');

// Get the action from the request
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'reset_password':
        resetPassword();
        break;
    case 'cancel_reset':
        cancelReset();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function resetPassword() {
    global $conn;
    
    $email = $_SESSION['reset_email'];
    $new_password = $_POST['new_password'] ?? '';
    
    if (empty($new_password)) {
        echo json_encode(['success' => false, 'message' => 'New password is required.']);
        return;
    }
    
    // Validate password requirements
    if (!validatePasswordRequirements($new_password)) {
        echo json_encode(['success' => false, 'message' => 'Password does not meet all requirements.']);
        return;
    }
    
    try {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password in database
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE emailAddress = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        
        if ($stmt->execute()) {
            // Log the password reset before clearing session
            require_once 'user_logger.php';
            $reset_username = $_SESSION['reset_username'] ?? 'User';
            logAction('PASSWORD_RESET', "User {$reset_username} reset their password via Forgot Password", $reset_username);

            // Clear all password reset session data
            unset($_SESSION['otp_verified']);
            unset($_SESSION['can_reset_password']);
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_username']);
            
            error_log("Password reset successfully for email: {$email}");
            
            echo json_encode([
                'success' => true,
                'message' => 'Password reset successfully! Redirecting to login...'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to reset password. Please try again.'
            ]);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Password reset error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while resetting password.'
        ]);
    }
}

function cancelReset() {
    // Clear all password reset session data
    unset($_SESSION['otp_verified']);
    unset($_SESSION['can_reset_password']);
    unset($_SESSION['reset_email']);
    unset($_SESSION['reset_username']);
    
    echo json_encode(['success' => true, 'message' => 'Password reset cancelled.']);
}

function validatePasswordRequirements($password) {
    // Minimum length of 8 characters
    if (strlen($password) < 8) {
        return false;
    }
    
    // At least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    // At least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    // At least one number
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    // At least one special character
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return false;
    }
    
    return true;
}

$conn->close();
?>
