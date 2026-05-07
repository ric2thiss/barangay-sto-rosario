<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

require_once 'connection.php';
require_once 'user_logger.php';

// Only set header if not already set
if (!headers_sent()) {
    header('Content-Type: application/json');
}

$response = ['success' => false, 'message' => ''];

try {
    $action = $_POST['action'] ?? '';
    $userId = $_POST['user_id'] ?? '';

    if (empty($userId)) {
        throw new Exception('Invalid user ID');
    }

    // Prevent users from performing destructive actions on themselves
    $destructive_actions = ['block', 'unblock', 'delete', 'approve', 'reject'];
    if ($userId == $_SESSION['user_id'] && in_array($action, $destructive_actions)) {
        throw new Exception('You cannot perform this action on your own account');
    }

    // Convert userId to string if it's numeric to match database VARCHAR type
    $userId = (string) $userId;

    switch ($action) {
        case 'block':
            // Get user details for logging and validation
            $user_stmt = $conn->prepare("SELECT username, role FROM users WHERE idNo = ?");
            $user_stmt->bind_param("s", $userId);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            $target_user = $user_result->fetch_assoc();
            
            if ($_SESSION['role'] === 'admin' && $target_user && $target_user['role'] === 'super_admin') {
                throw new Exception('Admins cannot block Super Admin accounts');
            }

            $stmt = $conn->prepare("UPDATE users SET status = 'blocked' WHERE idNo = ?");
            $stmt->bind_param("s", $userId);
            if ($stmt->execute()) {
                if ($target_user) {
                    logAction('BLOCK_USER', "User {$_SESSION['username']} blocked user {$target_user['username']}");
                }
                $response = ['success' => true, 'message' => 'User blocked successfully'];
            } else {
                throw new Exception('Failed to block user');
            }
            break;

        case 'unblock':
            // Get user details for logging and validation
            $user_stmt = $conn->prepare("SELECT username, role FROM users WHERE idNo = ?");
            $user_stmt->bind_param("s", $userId);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            $target_user = $user_result->fetch_assoc();
            
            if ($_SESSION['role'] === 'admin' && $target_user && $target_user['role'] === 'super_admin') {
                throw new Exception('Admins cannot unblock Super Admin accounts');
            }

            $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE idNo = ?");
            $stmt->bind_param("s", $userId);
            if ($stmt->execute()) {
                if ($target_user) {
                    logAction('UNBLOCK_USER', "User {$_SESSION['username']} unblocked user {$target_user['username']}");
                }
                $response = ['success' => true, 'message' => 'User unblocked successfully'];
            } else {
                throw new Exception('Failed to unblock user');
            }
            break;

        case 'approve':
            $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE idNo = ?");
            $stmt->bind_param("s", $userId);
            if ($stmt->execute()) {
                logAction('APPROVE_USER', "User {$_SESSION['username']} approved user ID {$userId}");
                $response = ['success' => true, 'message' => 'User approved successfully'];
            } else {
                throw new Exception('Failed to approve user');
            }
            break;

        case 'reject':
            $stmt = $conn->prepare("DELETE FROM users WHERE idNo = ?");
            $stmt->bind_param("s", $userId);
            if ($stmt->execute()) {
                logAction('REJECT_USER', "User {$_SESSION['username']} rejected user ID {$userId}");
                $response = ['success' => true, 'message' => 'User rejected successfully'];
            } else {
                throw new Exception('Failed to reject user');
            }
            break;

        case 'delete':
            // Only super_admin can delete
            if ($_SESSION['role'] !== 'super_admin') {
                throw new Exception('Only super admins can delete user accounts');
            }

            // Get user details
            $user_stmt = $conn->prepare("SELECT username, role FROM users WHERE idNo = ?");
            $user_stmt->bind_param("s", $userId);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            $target_user = $user_result->fetch_assoc();

            if (!$target_user) {
                throw new Exception('User not found');
            }

            // Super admin cannot delete their own account (already checked above but for safety)
            if ($userId === $_SESSION['user_id']) {
                throw new Exception('You cannot delete your own account');
            }

            // Delete the user directly
            $del_stmt = $conn->prepare("DELETE FROM users WHERE idNo = ?");
            $del_stmt->bind_param("s", $userId);
            if ($del_stmt->execute()) {
                logAction('DELETE_USER', "User {$_SESSION['username']} deleted user {$target_user['username']} (ID: {$userId})");
                $response = ['success' => true, 'message' => "User {$target_user['username']} has been deleted successfully"];
            } else {
                throw new Exception('Failed to delete user');
            }
            break;

        case 'edit':
            // Get full user data for both edit and view modal
            $stmt = $conn->prepare("SELECT idNo, username, firstName, middleName, lastName, extension, birthday, age, sex, emailAddress, purok, barangay, municipality, province, country, zipCode, role, status FROM users WHERE idNo = ?");
            $stmt->bind_param("s", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($user = $result->fetch_assoc()) {
                $response = [
                    'success' => true,
                    'message' => 'User data retrieved',
                    'user' => $user
                ];
            } else {
                throw new Exception('User not found');
            }
            break;

        case 'update':
            // Update user information
            $username = $_POST['username'] ?? '';
            $firstName = $_POST['firstName'] ?? '';
            $middleName = $_POST['middleName'] ?? '';
            $lastName = $_POST['lastName'] ?? '';
            $extension = $_POST['extension'] ?? '';
            $sex = $_POST['sex'] ?? '';
            $birthday = $_POST['birthday'] ?? '';
            $email = $_POST['email'] ?? '';
            $role = $_POST['role'] ?? '';
            $purok = $_POST['purok'] ?? '';
            $barangay = $_POST['barangay'] ?? '';
            $municipality = $_POST['municipality'] ?? '';
            $province = $_POST['province'] ?? '';
            $country = $_POST['country'] ?? '';
            $zipCode = $_POST['zipCode'] ?? '';
            $new_password = $_POST['new_password'] ?? '';

            if (empty($username) || empty($firstName) || empty($lastName) || empty($email) || empty($role) || empty($sex) || empty($birthday) || empty($purok) || empty($barangay) || empty($municipality) || empty($province) || empty($country) || empty($zipCode)) {
                throw new Exception('All required fields must be filled');
            }

            // Calculate age
            $dob = new DateTime($birthday);
            $now = new DateTime();
            $age = $dob->diff($now)->y;

            // Validate role
            if (!in_array($role, ['admin', 'super_admin', 'customer'])) {
                throw new Exception('Invalid role');
            }
            if ($_SESSION['role'] === 'admin' && $role === 'super_admin') {
                throw new Exception('Admins cannot grant Super Admin role');
            }
            
            // Check target user's current role
            $stmt_check = $conn->prepare("SELECT role FROM users WHERE idNo = ?");
            $stmt_check->bind_param("s", $userId);
            $stmt_check->execute();
            $target_user_role = $stmt_check->get_result()->fetch_assoc()['role'] ?? '';
            
            if ($_SESSION['role'] === 'admin' && $target_user_role === 'super_admin') {
                throw new Exception('Admins cannot modify Super Admin accounts');
            }

            // Handle optional password update (super_admin only)
            if (!empty($new_password)) {
                if ($_SESSION['role'] !== 'super_admin') {
                    throw new Exception('Only Super Admins can update user passwords');
                }
                if (strlen($new_password) < 8) {
                    throw new Exception('Password must be at least 8 characters');
                }
                $hashed_pw = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username = ?, firstName = ?, middleName = ?, lastName = ?, extension = ?, sex = ?, birthday = ?, age = ?, emailAddress = ?, role = ?, purok = ?, barangay = ?, municipality = ?, province = ?, country = ?, zipCode = ?, password = ? WHERE idNo = ?");
                $stmt->bind_param("sssssssissssssssss", $username, $firstName, $middleName, $lastName, $extension, $sex, $birthday, $age, $email, $role, $purok, $barangay, $municipality, $province, $country, $zipCode, $hashed_pw, $userId);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username = ?, firstName = ?, middleName = ?, lastName = ?, extension = ?, sex = ?, birthday = ?, age = ?, emailAddress = ?, role = ?, purok = ?, barangay = ?, municipality = ?, province = ?, country = ?, zipCode = ? WHERE idNo = ?");
                $stmt->bind_param("sssssssisssssssss", $username, $firstName, $middleName, $lastName, $extension, $sex, $birthday, $age, $email, $role, $purok, $barangay, $municipality, $province, $country, $zipCode, $userId);
            }

            if ($stmt->execute()) {
                $pw_msg = !empty($new_password) ? ' (password updated)' : '';
                logAction('UPDATE_USER', "User {$_SESSION['username']} updated user {$username}{$pw_msg}");
                $response = ['success' => true, 'message' => 'User updated successfully' . $pw_msg];
            } else {
                throw new Exception('Failed to update user');
            }
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
