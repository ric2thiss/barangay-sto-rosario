<?php
header('Content-Type: application/json');
session_start();
require_once 'connection.php';
require_once 'user_logger.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Username and password are required.'
        ]);
        exit;
    }

    // Query user from database
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password (if you store hashed passwords, use password_verify)
        if (password_verify($password, $user['password'])) {
            if ($user['status'] === 'pending') {
                echo json_encode([
                    'success' => true,
                    'message' => 'Account is pending approval.',
                    'redirect' => 'pending_account.php'
                ]);
                exit;
            } elseif ($user['status'] === 'blocked') {
                echo json_encode([
                    'success' => true,
                    'message' => 'Your account is blocked.',
                    'redirect' => 'account_status.php'
                ]);
                exit;
            }

            // Set session with user data including role
            $_SESSION['user_id'] = $user['idNo'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Log successful login
            logAction('LOGIN', "User {$username} logged in successfully");
            
            // Set is_logged_in to 1 and store IP/Device
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $device_used = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $update_stmt = $conn->prepare("UPDATE users SET is_logged_in = 1, ip_address = ?, device_used = ? WHERE idNo = ?");
            $update_stmt->bind_param("sss", $ip_address, $device_used, $user['idNo']);
            $update_stmt->execute();
            $update_stmt->close();

            // Determine redirect based on role
            $redirect_url = '';
            
            if ($user['status'] === 'incomplete') {
                $redirect_url = 'complete_profile.php';
            } else {
                switch ($user['role']) {
                    case 'super_admin':
                    case 'admin':
                        $redirect_url = 'dashboard.php';
                        break;
                    case 'customer':
                        $redirect_url = 'customer_dashboard.php'; // Changed from landingpage.php to match requirement
                        break;
                    default:
                        $redirect_url = 'landingpage.php';
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'Login successful.',
                'redirect' => $redirect_url,
                'role' => $user['role']
            ]);
        } else {
            // Log failed login attempt with correct username
            logAction('FAILED_LOGIN', "Failed login attempt for user '{$username}' (Invalid password)", $username);
            
            echo json_encode([
                'success' => false,
                'message' => 'Invalid password.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'User not found.'
        ]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}
