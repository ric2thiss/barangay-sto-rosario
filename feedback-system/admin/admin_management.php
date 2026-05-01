<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireAdmin();

// Handle create admin form submission
if (isset($_POST['create_admin'])) {
    // Security check
    if ($_SESSION['user_type'] !== 'superadmin') {
        $message = "Access denied: Only Super Admins can create administrators.";
        $message_type = "error";
    } else {
        $firstname = trim($conn->real_escape_string($_POST['firstname']));
        $middlename = trim($conn->real_escape_string($_POST['middlename']));
    $lastname = trim($conn->real_escape_string($_POST['lastname']));
    $username = trim($conn->real_escape_string($_POST['username']));
    $email = trim($conn->real_escape_string($_POST['email']));
    $user_type = trim($conn->real_escape_string($_POST['user_type'] ?? 'admin'));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validation
    if (empty($firstname)) {
        $errors['firstname'] = 'First name is required';
    } elseif (strlen($firstname) < 2) {
        $errors['firstname'] = 'First name must be at least 2 characters';
    }
    
    if (empty($lastname)) {
        $errors['lastname'] = 'Last name is required';
    } elseif (strlen($lastname) < 2) {
        $errors['lastname'] = 'Last name must be at least 2 characters';
    }
    
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (strlen($username) < 4) {
        $errors['username'] = 'Username must be at least 4 characters';
    } else {
        $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors['username'] = 'Username already exists';
        }
        $stmt->close();
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    } else {
        $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors['email'] = 'Email already registered';
        }
        $stmt->close();
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Password must contain at least one uppercase letter and one number';
    }
    
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("
            INSERT INTO admins 
            (firstname, middlename, lastname, username, email, password, user_type, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param(
            "sssssss", 
            $firstname, 
            $middlename, 
            $lastname, 
            $username, 
            $email, 
            $hashed_password,
            $user_type
        );
        
        if ($stmt->execute()) {
            $message = "Admin account created successfully!";
            $message_type = "success";
        } else {
            $message = "Error creating admin account: " . $stmt->error;
            $message_type = "error";
        }
        
        $stmt->close();
    } else {
        // Store errors in session to display in modal
        $_SESSION['create_admin_errors'] = $errors;
        $_SESSION['create_admin_values'] = [
            'firstname' => $firstname,
            'middlename' => $middlename,
            'lastname' => $lastname,
            'username' => $username,
            'email' => $email,
            'user_type' => $user_type
        ];
        $_SESSION['show_create_modal'] = true;
    }
}
}

// Handle edit admin form submission
if (isset($_POST['edit_admin'])) {
    // Security check
    if ($_SESSION['user_type'] !== 'superadmin') {
        $message = "Access denied: Only Super Admins can edit administrators.";
        $message_type = "error";
    } else {
        $admin_id = intval($_POST['admin_id']);
        $firstname = trim($conn->real_escape_string($_POST['firstname']));
    $middlename = trim($conn->real_escape_string($_POST['middlename']));
    $lastname = trim($conn->real_escape_string($_POST['lastname']));
    $username = trim($conn->real_escape_string($_POST['username']));
    $email = trim($conn->real_escape_string($_POST['email']));
    $user_type = trim($conn->real_escape_string($_POST['user_type']));
    $change_password = isset($_POST['change_password']) && $_POST['change_password'] == '1';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validation
    if (empty($firstname)) {
        $errors['firstname'] = 'First name is required';
    } elseif (strlen($firstname) < 2) {
        $errors['firstname'] = 'First name must be at least 2 characters';
    }
    
    if (empty($lastname)) {
        $errors['lastname'] = 'Last name is required';
    } elseif (strlen($lastname) < 2) {
        $errors['lastname'] = 'Last name must be at least 2 characters';
    }
    
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (strlen($username) < 4) {
        $errors['username'] = 'Username must be at least 4 characters';
    } else {
        $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $username, $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors['username'] = 'Username already exists';
        }
        $stmt->close();
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    } else {
        $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors['email'] = 'Email already registered';
        }
        $stmt->close();
    }
    
    if ($change_password) {
        if (empty($password)) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Password must contain at least one uppercase letter and one number';
        }
        
        if ($password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match';
        }
    }
    
    if (empty($errors)) {
        if ($change_password) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                UPDATE admins SET 
                firstname = ?, 
                middlename = ?, 
                lastname = ?, 
                username = ?, 
                email = ?, 
                password = ?, 
                user_type = ?,
                updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->bind_param(
                "sssssssi", 
                $firstname, 
                $middlename, 
                $lastname, 
                $username, 
                $email, 
                $hashed_password, 
                $user_type,
                $admin_id
            );
        } else {
            $stmt = $conn->prepare("
                UPDATE admins SET 
                firstname = ?, 
                middlename = ?, 
                lastname = ?, 
                username = ?, 
                email = ?, 
                user_type = ?,
                updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->bind_param(
                "ssssssi", 
                $firstname, 
                $middlename, 
                $lastname, 
                $username, 
                $email, 
                $user_type,
                $admin_id
            );
        }
        
        if ($stmt->execute()) {
            $message = "Admin account updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating admin account: " . $stmt->error;
            $message_type = "error";
        }
        
        $stmt->close();
    } else {
        // Store errors in session to display in modal
        $_SESSION['edit_admin_errors'] = $errors;
        $_SESSION['edit_admin_values'] = [
            'firstname' => $firstname,
            'middlename' => $middlename,
            'lastname' => $lastname,
            'username' => $username,
            'email' => $email,
            'user_type' => $user_type,
            'change_password' => $change_password
        ];
        $_SESSION['edit_admin_id'] = $admin_id;
        $_SESSION['show_edit_modal'] = true;
    }
}
}

// Handle delete admin
if (isset($_POST['delete_admin'])) {
    $admin_id = intval($_POST['admin_id']);
    $current_admin_id = $_SESSION['user_id'];
    
    // Security check
    if ($_SESSION['user_type'] !== 'superadmin') {
        $message = "Access denied: Only Super Admins can delete administrators.";
        $message_type = "error";
    }
    // Prevent self-deletion
    elseif ($admin_id == $current_admin_id) {
        $message = "You cannot delete your own account!";
        $message_type = "error";
    } else {
        // Check if admin exists
        $check_sql = "SELECT id, username, firstname, lastname FROM admins WHERE id = $admin_id";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $admin_data = $check_result->fetch_assoc();
            $delete_sql = "DELETE FROM admins WHERE id = $admin_id";
            if ($conn->query($delete_sql)) {
                $message = "Administrator '" . htmlspecialchars($admin_data['username']) . "' deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Error deleting administrator: " . $conn->error;
                $message_type = "error";
            }
        } else {
            $message = "Administrator not found!";
            $message_type = "error";
        }
    }
}

// Search functionality
$search = '';
$where_conditions = [];

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $where_conditions[] = "(username LIKE '%$search%' OR email LIKE '%$search%' OR firstname LIKE '%$search%' OR lastname LIKE '%$search%')";
}

// Build WHERE clause
$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM admins $where_clause";
$count_result = $conn->query($count_sql);
$total_admins = $count_result->fetch_assoc()['total'];

// Pagination
$per_page = 10;
$total_pages = ceil($total_admins / $per_page);
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$current_page = max(1, min($current_page, $total_pages));
$offset = ($current_page - 1) * $per_page;

// Get admins with pagination
$admins_sql = "SELECT * FROM admins $where_clause ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
$admins_result = $conn->query($admins_sql);

// Get current admin info
$current_admin_id = $_SESSION['user_id'];
$current_admin_sql = "SELECT * FROM admins WHERE id = $current_admin_id";
$current_admin_result = $conn->query($current_admin_sql);
$current_admin = $current_admin_result->fetch_assoc();

// Get admin statistics
$stats_sql = "SELECT 
                COUNT(*) as total_admins,
                SUM(CASE WHEN user_type = 'superadmin' THEN 1 ELSE 0 END) as superadmin_count,
                SUM(CASE WHEN user_type = 'admin' THEN 1 ELSE 0 END) as admin_count,
                MIN(created_at) as first_admin_created
              FROM admins";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Check if we should show the create admin modal
$show_create_modal = isset($_SESSION['show_create_modal']) && $_SESSION['show_create_modal'];
$create_admin_errors = isset($_SESSION['create_admin_errors']) ? $_SESSION['create_admin_errors'] : [];
$create_admin_values = isset($_SESSION['create_admin_values']) ? $_SESSION['create_admin_values'] : [];

// Check if we should show the edit admin modal
$show_edit_modal = isset($_SESSION['show_edit_modal']) && $_SESSION['show_edit_modal'];
$edit_admin_errors = isset($_SESSION['edit_admin_errors']) ? $_SESSION['edit_admin_errors'] : [];
$edit_admin_values = isset($_SESSION['edit_admin_values']) ? $_SESSION['edit_admin_values'] : [];
$edit_admin_id = isset($_SESSION['edit_admin_id']) ? $_SESSION['edit_admin_id'] : '';

// Get admin data for edit modal if needed
$edit_admin_data = [];
if ($edit_admin_id) {
    $stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->bind_param("i", $edit_admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_admin_data = $result->fetch_assoc();
    $stmt->close();
}

// Clear session data after retrieving
unset($_SESSION['show_create_modal']);
unset($_SESSION['create_admin_errors']);
unset($_SESSION['create_admin_values']);
unset($_SESSION['show_edit_modal']);
unset($_SESSION['edit_admin_errors']);
unset($_SESSION['edit_admin_values']);
unset($_SESSION['edit_admin_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - Resident Feedback and Survey System</title>
    <link rel="icon" href="../img/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin_dark_mode.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <style>
        /* Additional styles for edit modal */
        .change-password-container {
            background: #f8fafc;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            margin-bottom: 20px;
        }
        
        .change-password-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            user-select: none;
        }
        
        .change-password-toggle input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        
        .change-password-toggle label {
            font-weight: 600;
            color: #374151;
            cursor: pointer;
        }
        
        .password-fields {
            margin-top: 15px;
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .password-fields.show {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .user-type-select {
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            width: 100%;
            transition: all 0.3s;
            background: white;
        }
        
        .user-type-select:focus {
            outline: none;
            border-color: #1F3A93;
            box-shadow: 0 0 0 3px rgba(31, 58, 147, 0.1);
        }
        
        .badge-purple {
            background: #ddd6fe;
            color: #5b21b6;
            border: 1px solid #c4b5fd;
        }

        /* Delete Modal Styles */
        .modal-danger {
            border-color: #ef4444;
        }
        
        .modal-danger .modal-header {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        
        .warning-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: #fee2e2;
            border-radius: 50%;
            font-size: 40px;
            color: #dc2626;
            border: 3px solid #fecaca;
        }
        
        .delete-confirmation {
            text-align: center;
            padding: 20px;
        }
        
        .delete-confirmation h4 {
            color: #991b1b;
            margin-bottom: 15px;
            font-size: 22px;
        }
        
        .delete-confirmation p {
            color: #374151;
            margin-bottom: 10px;
            line-height: 1.6;
        }
        
        .admin-info-box {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
        }
        
        .admin-info-box h5 {
            color: #991b1b;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .admin-info-box .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding-bottom: 5px;
            border-bottom: 1px dashed #fecaca;
        }
        
        .admin-info-box .info-label {
            font-weight: 600;
            color: #374151;
        }
        
        .admin-info-box .info-value {
            color: #4b5563;
        }
        
        .warning-note {
            background: #fef3c7;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            color: #92400e;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .modal-footer.danger {
            background: #fef2f2;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: #f8fafc;
            transition: all 0.3s ease;
        }

        
        
        
        
        
        /* FIXED STICKY SIDEBAR */
        .sidebar {
            width: 280px;
            background: #1F3A93;
            color: #ffffff;
            height: 100vh;
            padding: 20px 0;
            position: fixed;
            left: 0;
            top: 0;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
            box-shadow: 5px 0 25px rgba(0, 0, 0, 0.15);
            z-index: 100;
            overflow-y: auto;
            overflow-x: hidden;
            border-right: none;
            display: flex;
            flex-direction: column;
        }

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }

        .sidebar.closed {
            width: 80px;
        }

        /* Logo Area */
        .logo-area {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 25px;
            position: sticky;
            top: 0;
            background: #1F3A93;
            z-index: 10;
        }

        .logo-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            flex: 1;
        }

        .logo-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
            background: white;
            padding: 2px;
        }

        .logo-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .logo-text {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            transition: all 0.3s;
            white-space: nowrap;
            overflow: visible;
            flex: 1;
            min-width: 0;
        }

        .sidebar.closed .logo-text {
            opacity: 0;
            visibility: hidden;
            width: 0;
            margin: 0;
            flex: 0;
        }

        /* Toggle Button */
        .toggle-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex !important;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s;
            flex-shrink: 0;
            position: relative;
            z-index: 11;
            margin-left: 10px;
        }

        .toggle-btn:hover {
            background: #ffffff;
            color: #1F3A93;
        }

        .toggle-btn .fa-times {
            display: block;
        }

        .toggle-btn .fa-bars {
            display: none;
        }

        .sidebar.closed .toggle-btn .fa-times {
            display: none;
        }

        .sidebar.closed .toggle-btn .fa-bars {
            display: block;
        }

        /* Login Indicator */
        .login-indicator {
            padding: 0 20px;
            margin-bottom: 30px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .user-info:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ffffff, #bae6fd);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 600;
            color: #1F3A93;
            position: relative;
            flex-shrink: 0;
        }

        .status-dot {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #1a317d;
            border: 2px solid #1F3A93;
            box-shadow: 0 0 0 2px #1a317d;
        }

        .user-details {
            transition: all 0.3s;
            overflow: hidden;
            flex: 1;
            min-width: 0;
        }

        .sidebar.closed .user-details {
            opacity: 0;
            visibility: hidden;
            width: 0;
            flex: 0;
        }

        .user-name {
            font-weight: 600;
            font-size: 16px;
            color: #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.8);
            background: rgba(255, 255, 255, 0.1);
            padding: 3px 10px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 3px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            white-space: nowrap;
        }

        /* Menu Items */
        .menu-items {
            list-style: none;
            padding: 0 15px;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .menu-item {
            margin-bottom: 5px;
        }

        .menu-link {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }

        .menu-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            transform: translateX(5px);
        }

        .menu-link.active {
            background: #ffffff;
            color: #1F3A93;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .menu-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: #ffffff;
            border-radius: 0 3px 3px 0;
        }

        .menu-icon {
            font-size: 20px;
            width: 24px;
            text-align: center;
            flex-shrink: 0;
        }

        .menu-link.active .menu-icon {
            transform: scale(1.1);
        }

        .menu-text {
            transition: all 0.3s;
            font-weight: 500;
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }

        .sidebar.closed .menu-text {
            opacity: 0;
            visibility: hidden;
            width: 0;
            flex: 0;
        }

        /* Logout Section */
        .logout-section {
            padding: 20px;
            background: #1F3A93;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: auto;
            position: sticky;
            bottom: 0;
        }

        .logout-link {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            white-space: nowrap;
            width: 100%;
            cursor: pointer;
            font-family: inherit;
            font-size: inherit;
        }

        .logout-link:hover {
            background: #ef4444;
            color: #ffffff;
            border-color: #ef4444;
            transform: translateX(5px);
        }

        .logout-text {
            transition: all 0.3s;
            font-weight: 500;
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }

        .sidebar.closed .logout-text {
            opacity: 0;
            visibility: hidden;
            width: 0;
            flex: 0;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px;
            transition: all 0.3s;
            overflow-y: auto;
            background: #f8fafc;
            margin-left: 280px;
            min-height: 100vh;
        }

        .sidebar.closed ~ .main-content {
            margin-left: 80px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
        }

        .page-title {
            font-size: 28px;
            color: #1a317d;
            font-weight: 700;
        }

        .title-icon {
            color: #1F3A93;
            margin-right: 10px;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #bae6fd;
            border: 1px solid #bae6fd;
            color: #1a317d;
        }

        .alert-error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .alert i {
            font-size: 18px;
        }

        /* Stats Cards */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            color: #1a317d;
            padding: 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(31, 58, 147, 0.1);
            border-color: #1F3A93;
        }

        .stat-icon {
            font-size: 30px;
            background: #f0f7ff;
            color: #1F3A93;
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #bae6fd;
        }

        .stat-info h3 {
            font-size: 28px;
            font-weight: 700;
            color: #1a317d;
        }

        .stat-info p {
            color: #6b7280;
            font-size: 14px;
        }

        /* Search Bar */
        .search-bar {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            border: 1px solid #e5e7eb;
        }

        .search-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .form-group {
            flex: 1;
            min-width: 300px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: #1F3A93;
            box-shadow: 0 0 0 3px rgba(31, 58, 147, 0.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(90deg, #1F3A93, #3a56b5);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            font-weight: 500;
            border: none;
            cursor: pointer;
            font-size: 15px;
        }

        .btn:hover {
            background: linear-gradient(90deg, #1a317d, #1F3A93);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(31, 58, 147, 0.2);
        }

        .btn i {
            font-size: 16px;
        }

        .btn-secondary {
            background: linear-gradient(90deg, #6b7280, #9ca3af);
        }

        .btn-secondary:hover {
            background: linear-gradient(90deg, #4b5563, #6b7280);
            box-shadow: 0 5px 15px rgba(107, 114, 128, 0.2);
        }

        .btn-danger {
            background: linear-gradient(90deg, #ef4444, #f87171);
        }

        .btn-danger:hover {
            background: linear-gradient(90deg, #dc2626, #ef4444);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.2);
        }

        .btn-primary {
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #2563eb, #3b82f6);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.2);
        }

        .btn-success {
            background: linear-gradient(90deg, #1F3A93, #3a56b5);
        }

        .btn-success:hover {
            background: linear-gradient(90deg, #1a317d, #1F3A93);
            box-shadow: 0 5px 15px rgba(31, 58, 147, 0.2);
        }

        /* Table Styles */
        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            border: 1px solid #e5e7eb;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            background: #f0f7ff;
            padding: 18px 20px;
            text-align: left;
            font-weight: 600;
            color: #1a317d;
            border-bottom: 2px solid #bae6fd;
            font-size: 15px;
        }

        table td {
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
        }

        table tr:last-child td {
            border-bottom: none;
        }

        table tr:hover {
            background: #f9fafb;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-success {
            background: #bae6fd;
            color: #1a317d;
            border: 1px solid #bae6fd;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-small {
            padding: 8px 12px;
            font-size: 13px;
            border-radius: 8px;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            padding: 20px;
        }

        .pagination-btn {
            padding: 10px 16px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            color: #374151;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 14px;
        }

        .pagination-btn:hover {
            background: #f0f7ff;
            border-color: #1F3A93;
            color: #1a317d;
        }

        .pagination-btn.active {
            background: linear-gradient(90deg, #1F3A93, #3a56b5);
            color: white;
            border-color: #1F3A93;
        }

        .pagination-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f9fafb;
        }

        /* Date Display */
        .date-display {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #6b7280;
            font-weight: 500;
            background: #f9fafb;
            padding: 10px 15px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }

        .date-display i {
            color: #1F3A93;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 60px;
            color: #d1d5db;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #374151;
        }

        /* Current User Highlight */
        .current-user-row {
            background: #f0f7ff !important;
            border-left: 4px solid #1F3A93;
        }

        .current-user-row td:first-child {
            position: relative;
        }

        .current-user-row td:first-child::before {
            content: 'You';
            position: absolute;
            left: -45px;
            top: 50%;
            transform: translateY(-50%);
            background: #1F3A93;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
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
        }

        .modal-overlay.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalSlideIn 0.4s ease;
            border: 3px solid #1F3A93;
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
            position: absolute;
            right: 20px;
            top: 20px;
        }

        .modal-header .close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 30px;
        }

        .admin-badge {
            background: #1F3A93;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-left: 10px;
        }

        .is-invalid {
            border-color: #ef4444 !important;
        }

        .is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
        }

        .invalid-feedback {
            color: #ef4444;
            font-size: 13px;
            margin-top: 5px;
        }

        .text-muted {
            color: #6b7280 !important;
            font-size: 13px;
        }

        .required::after {
            content: " *";
            color: #ef4444;
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

        /* Mobile Toggle Button */
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 99;
            background: #1F3A93;
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 10px;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(31, 58, 147, 0.3);
            z-index: 1000;
        }

        /* Overlay for mobile */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 99;
        }

        .overlay.active {
            display: block;
        }

        /* Tooltip for collapsed sidebar */
        .sidebar.closed .menu-link,
        .sidebar.closed .logout-link,
        .sidebar.closed .user-info {
            position: relative;
        }
        
        .sidebar.closed .menu-link::after,
        .sidebar.closed .logout-link::after,
        .sidebar.closed .user-info::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: #333;
            color: white;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 13px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
            pointer-events: none;
            z-index: 1100;
            margin-left: 10px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
        }
        
        .sidebar.closed .menu-link:hover::after,
        .sidebar.closed .logout-link:hover::after,
        .sidebar.closed .user-info:hover::after {
            opacity: 1;
            visibility: visible;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: -280px;
                width: 280px;
                transition: left 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
                box-shadow: 5px 0 30px rgba(0, 0, 0, 0.1);
            }

            .sidebar.open {
                left: 0;
            }

            .sidebar.closed {
                left: -280px;
                width: 280px;
            }

            .main-content {
                margin-left: 0 !important;
                width: 100%;
                padding: 80px 20px 30px;
            }

            .mobile-toggle {
                display: flex;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .search-form {
                flex-direction: column;
            }

            .form-group {
                min-width: 100%;
            }

            .table-container {
                overflow-x: auto;
            }

            table {
                min-width: 800px;
            }

            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }

            .btn-small {
                width: 100%;
                justify-content: center;
            }

            .pagination {
                flex-wrap: wrap;
            }

            .current-user-row td:first-child::before {
                left: 5px;
                top: 5px;
                transform: none;
            }

            .modal {
                width: 95%;
                max-height: 95vh;
            }

            .modal-body {
                padding: 20px;
            }

            .modal-footer {
                flex-direction: column;
            }

            .modal-footer .btn {
                width: 100%;
                justify-content: center;
            }
        }
        /* ========== LOGOUT MODAL STYLES - GREEN THEME ========== */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: fadeInOverlay 0.4s ease;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-overlay.closing {
            animation: fadeOutOverlay 0.3s ease forwards;
        }

        .modal-container {
            background: white;
            border-radius: 20px;
            width: 100%;
            max-width: 450px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            animation: slideInUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            border: 1px solid #e5e7eb;
            transform-origin: center bottom;
        }

        .modal-container.closing {
            animation: slideOutDown 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        .modal-header {
            background: linear-gradient(135deg, #1F3A93, #152c71, #1e3a8a);
            background-size: 200% 200%;
            color: white;
            padding: 28px 30px;
            display: flex;
            align-items: center;
            gap: 18px;
            position: relative;
            overflow: hidden;
            animation: gradientShift 8s ease infinite;
        }

        .modal-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1F3A93, #3a56b5, #1F3A93);
            animation: shimmer 3s linear infinite;
            background-size: 200% 100%;
        }

        .modal-header h2 {
            font-size: 24px;
            font-weight: 700;
            flex: 1;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h4{
            text-align: center;
            color: #1a317d;
        }

        .modal-icon {
            background: rgba(255, 255, 255, 0.2);
            width: 90px;
            height: 90px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            backdrop-filter: blur(10px);
            border: 3px solid #bae6fd;
            animation: iconPulse 2s ease-in-out infinite;
            box-shadow: 0 10px 30px rgba(31, 58, 147, 0.15);
            margin: 0 auto; /* Center the icon */
            color: #1F3A93;
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            font-size: 20px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg) scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .modal-body {
            padding: 32px;
            animation: fadeInBody 0.6s ease 0.2s both;
            border: 1px solid #e5e7eb; /* Added border */
            border-radius: 10px; /* Optional: rounded corners */
            margin: 20px; /* Optional: space around the border */
            background: #f9fafb; /* Optional: light background for better contrast */
        }

        .modal-body p {
            margin-bottom: 16px;
            line-height: 1.7;
            color: #4b5563;
            font-size: 16.5px;
            animation: textSlideUp 0.6s ease 0.3s both;
        }

        .modal-subtext {
            font-size: 14.5px;
            color: #6b7280;
            font-style: italic;
            margin-top: 8px;
            opacity: 0;
            animation: fadeInText 0.6s ease 0.4s forwards;
            border: 1px solid #d1d5db; /* Added border */
            border-radius: 6px; /* Optional: rounded corners */
            padding: 10px 12px; /* Optional: padding inside border */
            background: rgba(209, 213, 219, 0.1); /* Optional: very light background */
        }

        .modal-footer {
            padding: 24px 30px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 16px;
            justify-content: flex-end;
            animation: fadeInFooter 0.6s ease 0.5s both;
        }

        .modal-btn {
            padding: 15px 32px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15.5px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .modal-btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        .modal-btn:active::after {
            animation: ripple 0.6s ease-out;
        }

        .modal-btn-secondary {
            background: white;
            color: #6b7280;
            border-color: #d1d5db;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .modal-btn-secondary:hover {
            background: linear-gradient(135deg, #f0f7ff, #e0f2fe);
            color: #1a317d;
            border-color: #86efac;
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 20px rgba(31, 58, 147, 0.15);
        }

        .modal-btn-primary {
            background: linear-gradient(135deg, #1F3A93, #152c71, #1e3a8a);
            background-size: 200% 200%;
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(31, 58, 147, 0.3);
            animation: gradientShift 8s ease infinite;
        }

        .modal-btn-primary:hover {
            background: linear-gradient(135deg, #152c71, #1e3a8a, #1a317d);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 10px 25px rgba(31, 58, 147, 0.4);
            animation: gradientShiftFast 4s ease infinite;
        }

        .modal-btn-primary:active {
            transform: translateY(-1px) scale(1.01);
            transition: all 0.1s ease;
        }

        /* ========== ANIMATIONS ========== */
        @keyframes fadeInOverlay {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeOutOverlay {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        @keyframes slideInUp {
            0% {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }
            50% {
                transform: translateY(-8px) scale(1.02);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes slideOutDown {
            0% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
            50% {
                transform: translateY(-8px) scale(1.02);
            }
            100% {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes gradientShiftFast {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes iconPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        @keyframes fadeInBody {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes textSlideUp {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInText {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeInFooter {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            20% {
                transform: scale(25, 25);
                opacity: 0.3;
            }
            100% {
                transform: scale(40, 40);
                opacity: 0;
            }
        }

        /* Micro-interactions */
        .modal-container:hover {
            transform: translateY(-2px);
            transition: transform 0.3s ease;
        }

        /* Ensure modal works on mobile */
        @media (max-width: 480px) {
            .modal-container {
                max-width: 95%;
                border-radius: 16px;
                animation: slideInUpMobile 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            }
            
            .modal-header {
                padding: 24px 20px;
                gap: 15px;
                flex-direction: column; /* Stack icon and text vertically */
            }
            
            .modal-icon {
                width: 48px;
                height: 48px;
                font-size: 22px;
                margin: 0 auto; /* Keep centered */
            }
            
            .modal-body {
                padding: 20px 16px; /* Adjusted padding */
                margin: 15px; /* Adjusted margin */
            }
            
            .modal-subtext {
                padding: 8px 10px; /* Adjusted padding */
            }
            
            .modal-footer {
                padding: 20px;
                flex-direction: column;
            }
            
            .modal-btn {
                width: 100%;
                justify-content: center;
                padding: 14px 24px;
            }
            
            @keyframes slideInUpMobile {
                0% {
                    opacity: 0;
                    transform: translateY(30px) scale(0.98);
                }
                100% {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
            }
        }

        /* Reduced motion preferences */
        @media (prefers-reduced-motion: reduce) {
            .modal-overlay,
            .modal-container,
            .modal-header,
            .modal-icon,
            .modal-body,
            .modal-footer,
            .modal-btn {
                animation: none !important;
                transition: none !important;
            }
        }
        /* ========== END LOGOUT MODAL STYLES - GREEN THEME ========== */

        /* Responsive Admin Cards for Mobile */
        .admin-cards-mobile {
            display: none;
        }

        .admin-table-desktop {
            display: block;
        }

        .admin-card-mobile-item {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 15px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            transition: all 0.3s;
        }

        .admin-card-mobile-item.current-admin-card {
            background: #f0f7ff;
            border-color: #bae6fd;
            border-left: 4px solid #1F3A93;
        }

        .admin-card-mobile-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border-color: #1F3A93;
        }

        .mobile-admin-card-header {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            gap: 15px;
        }

        .mobile-admin-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1F3A93, #152c71);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 18px;
            flex-shrink: 0;
        }

        .mobile-admin-info-main {
            flex: 1;
            min-width: 0;
        }

        .mobile-admin-name {
            font-weight: 600;
            color: #1a317d;
            font-size: 16px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .current-user-badge {
            background: #1F3A93;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .mobile-admin-details {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .mobile-admin-username,
        .mobile-admin-email {
            font-size: 12px;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .mobile-admin-username i,
        .mobile-admin-email i {
            color: #1F3A93;
            font-size: 11px;
        }

        .mobile-admin-type {
            flex-shrink: 0;
        }

        .mobile-admin-card-body {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        .mobile-admin-detail-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 8px 0;
        }

        .mobile-admin-detail-row:not(:last-child) {
            border-bottom: 1px dashed #e5e7eb;
        }

        .mobile-detail-label {
            font-size: 13px;
            color: #4b5563;
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
        }

        .mobile-detail-label i {
            color: #1F3A93;
            font-size: 12px;
        }

        .mobile-detail-value {
            font-size: 13px;
            color: #1a317d;
            font-weight: 500;
            text-align: right;
            flex: 1;
        }

        .mobile-admin-card-footer {
            padding: 15px;
        }

        .mobile-action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .mobile-action-btn {
            flex: 1;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 40px;
        }

        .mobile-action-btn.btn-primary {
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
            color: white;
        }

        .mobile-action-btn.btn-primary:hover {
            background: linear-gradient(90deg, #2563eb, #3b82f6);
            transform: translateY(-2px);
        }

        .mobile-action-btn.btn-danger {
            background: linear-gradient(90deg, #ef4444, #f87171);
            color: white;
        }

        .mobile-action-btn.btn-danger:hover {
            background: linear-gradient(90deg, #dc2626, #ef4444);
            transform: translateY(-2px);
        }

        .mobile-action-btn.btn-secondary {
            background: linear-gradient(90deg, #6b7280, #9ca3af);
            color: white;
            cursor: default;
        }

        .mobile-action-btn.btn-secondary:hover {
            transform: none;
        }

        /* Mobile Responsive Adjustments */
        @media (max-width: 768px) {
            .admin-cards-mobile {
                display: block;
            }
            
            .admin-table-desktop {
                display: none;
            }
            
            .table-container {
                overflow-x: visible;
                padding: 10px;
            }
            
            /* Adjust table header for mobile */
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
            
            .btn-small {
                width: 100%;
                justify-content: center;
            }
            
            /* Adjust create admin button */
            .create-admin-header {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }
            
            #openCreateAdminModal {
                width: 100%;
                justify-content: center;
            }
            
            /* Adjust search bar */
            .search-form {
                flex-direction: column;
            }
            
            .form-group {
                min-width: 100%;
            }
        }

        @media (max-width: 480px) {
            .admin-card-mobile-item {
                border-radius: 10px;
            }
            
            .mobile-admin-card-header {
                padding: 12px;
                gap: 12px;
            }
            
            .mobile-admin-avatar {
                width: 45px;
                height: 45px;
                font-size: 16px;
            }
            
            .mobile-admin-name {
                font-size: 15px;
            }
            
            .mobile-admin-details {
                font-size: 11px;
            }
            
            .mobile-admin-card-body {
                padding: 12px;
            }
            
            .mobile-admin-detail-row {
                padding: 7px 0;
            }
            
            .mobile-detail-label,
            .mobile-detail-value {
                font-size: 12px;
            }
            
            .mobile-admin-card-footer {
                padding: 12px;
            }
            
            .mobile-action-buttons {
                flex-direction: column;
                gap: 8px;
            }
            
            .mobile-action-btn {
                width: 100%;
                padding: 12px 15px;
            }
            
            .current-user-row td:first-child::before {
                display: none;
            }
        }

        /* Badge Purple for Super Admin */
        .badge-purple {
            background: #ddd6fe;
            color: #5b21b6;
            border: 1px solid #c4b5fd;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    <!-- Mobile Toggle Button -->
    <button class="mobile-toggle" id="mobileToggleBtn">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Overlay for mobile -->
    <div class="overlay" id="overlay"></div>

    <!-- ========== LOGOUT MODAL ========== -->
    <div class="modal-overlay" id="logoutModal">
        <div class="modal-container">
            <div class="modal-header">
                <i class="fas fa-sign-out-alt"></i>
                <h2>Confirm Logout</h2>
                <button class="modal-close" id="closeModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <br>
            <div class="modal-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div><br>
            <h4>Ready to Leave?</h4>
            
            <div class="modal-body">
                <p>Are you sure you want to logout from the admin panel?</p>
                <p class="modal-subtext">You will need to login again to access the admin dashboard.</p>
            </div>
            
            <div class="modal-footer">
                <button class="modal-btn modal-btn-secondary" id="cancelLogout">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <a href="logout.php" class="modal-btn modal-btn-primary">
                    <i class="fas fa-sign-out-alt"></i> Yes, Logout
                </a>
            </div>
        </div>
    </div>
    <!-- ========== END LOGOUT MODAL ========== -->

    <!-- Create Admin Modal -->
    <div class="modal-overlay <?php echo $show_create_modal ? 'active' : ''; ?>" id="createAdminModal">
        <div class="modal">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-user-shield"></i> Create Administrator Account
                    <span class="admin-badge">ADMIN ONLY</span>
                </h3>
                <button class="close-btn" id="closeCreateModalBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <?php if (!empty($create_admin_errors) && !isset($create_admin_errors['database'])): ?>
                    <div class="alert alert-error" style="margin-bottom: 20px;">
                        <i class="fas fa-exclamation-circle"></i>
                        Please fix the errors below.
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-success" style="background: #f0f7ff; border-color: #bae6fd; color: #1a317d; margin-bottom: 20px;">
                    <i class="fas fa-info-circle"></i> This form creates <strong>Administrator Accounts</strong> only. These accounts will have full system access and privileges.
                </div>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="createAdminForm">
                    <div class="row mb-3" style="display: flex; flex-wrap: wrap; margin-bottom: 20px;">
                        <div style="flex: 1; min-width: 250px; padding: 0 10px;">
                            <label for="firstname" class="form-label required">First Name</label>
                            <input type="text" class="form-control <?php echo isset($create_admin_errors['firstname']) ? 'is-invalid' : ''; ?>" 
                                   id="firstname" name="firstname" 
                                   value="<?php echo htmlspecialchars($create_admin_values['firstname'] ?? ''); ?>" required>
                            <?php if (isset($create_admin_errors['firstname'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $create_admin_errors['firstname']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div style="flex: 1; min-width: 250px; padding: 0 10px;">
                            <label for="middlename" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="middlename" name="middlename" 
                                   value="<?php echo htmlspecialchars($create_admin_values['middlename'] ?? ''); ?>">
                            <small class="text-muted">Optional</small>
                        </div>
                        
                        <div style="flex: 1; min-width: 250px; padding: 0 10px;">
                            <label for="lastname" class="form-label required">Last Name</label>
                            <input type="text" class="form-control <?php echo isset($create_admin_errors['lastname']) ? 'is-invalid' : ''; ?>" 
                                   id="lastname" name="lastname" 
                                   value="<?php echo htmlspecialchars($create_admin_values['lastname'] ?? ''); ?>" required>
                            <?php if (isset($create_admin_errors['lastname'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $create_admin_errors['lastname']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3" style="display: flex; flex-wrap: wrap; margin-bottom: 20px;">
                        <div style="flex: 1; min-width: 300px; padding: 0 10px;">
                            <label for="username" class="form-label required">Username</label>
                            <input type="text" class="form-control <?php echo isset($create_admin_errors['username']) ? 'is-invalid' : ''; ?>" 
                                   id="username" name="username" 
                                   value="<?php echo htmlspecialchars($create_admin_values['username'] ?? ''); ?>" required>
                            <?php if (isset($create_admin_errors['username'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $create_admin_errors['username']; ?>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted">Minimum 4 characters</small>
                        </div>
                        
                        <div style="flex: 1; min-width: 300px; padding: 0 10px;">
                            <label for="email" class="form-label required">Email Address</label>
                            <input type="email" class="form-control <?php echo isset($create_admin_errors['email']) ? 'is-invalid' : ''; ?>" 
                                   id="email" name="email" 
                                   value="<?php echo htmlspecialchars($create_admin_values['email'] ?? ''); ?>" required>
                            <?php if (isset($create_admin_errors['email'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $create_admin_errors['email']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3" style="display: flex; flex-wrap: wrap; margin-bottom: 20px;">
                        <div style="flex: 1; min-width: 300px; padding: 0 10px;">
                            <label for="create_user_type" class="form-label required">User Type</label>
                            <select class="user-type-select" id="create_user_type" name="user_type">
                                <option value="admin" <?php echo (($create_admin_values['user_type'] ?? '') == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                <option value="superadmin" <?php echo (($create_admin_values['user_type'] ?? '') == 'superadmin') ? 'selected' : ''; ?>>Super Admin</option>
                            </select>
                            <small class="text-muted">Super Admins have additional privileges</small>
                        </div>
                    </div>
                    
                    <div class="row mb-4" style="display: flex; flex-wrap: wrap; margin-bottom: 30px;">
                        <div style="flex: 1; min-width: 300px; padding: 0 10px;">
                            <label for="password" class="form-label required">Password</label>
                            <input type="password" class="form-control <?php echo isset($create_admin_errors['password']) ? 'is-invalid' : ''; ?>" 
                                   id="password" name="password" required>
                            <?php if (isset($create_admin_errors['password'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $create_admin_errors['password']; ?>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted">Minimum 8 characters with uppercase and number</small>
                        </div>
                        
                        <div style="flex: 1; min-width: 300px; padding: 0 10px;">
                            <label for="confirm_password" class="form-label required">Confirm Password</label>
                            <input type="password" class="form-control <?php echo isset($create_admin_errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                   id="confirm_password" name="confirm_password" required>
                            <?php if (isset($create_admin_errors['confirm_password'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $create_admin_errors['confirm_password']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="resetCreateFormBtn">
                    <i class="fas fa-undo"></i> Clear Form
                </button>
                <button type="submit" form="createAdminForm" name="create_admin" class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Create Administrator
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Admin Modal -->
    <div class="modal-overlay <?php echo $show_edit_modal ? 'active' : ''; ?>" id="editAdminModal">
        <div class="modal">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-user-edit"></i> Edit Administrator Account
                    <span class="admin-badge" id="editAdminBadge">
                        <?php echo isset($edit_admin_values['user_type']) ? strtoupper($edit_admin_values['user_type']) : 
                               (isset($edit_admin_data['user_type']) ? strtoupper($edit_admin_data['user_type']) : 'ADMIN'); ?>
                    </span>
                </h3>
                <button class="close-btn" id="closeEditModalBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <?php if (!empty($edit_admin_errors) && !isset($edit_admin_errors['database'])): ?>
                    <div class="alert alert-error" style="margin-bottom: 20px;">
                        <i class="fas fa-exclamation-circle"></i>
                        Please fix the errors below.
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="editAdminForm">
                    <input type="hidden" name="admin_id" id="edit_admin_id" 
                           value="<?php echo $edit_admin_id ?: (isset($_GET['edit']) ? intval($_GET['edit']) : ''); ?>">
                    
                    <div class="row mb-3" style="display: flex; flex-wrap: wrap; margin-bottom: 20px;">
                        <div style="flex: 1; min-width: 250px; padding: 0 10px;">
                            <label for="edit_firstname" class="form-label required">First Name</label>
                            <input type="text" class="form-control <?php echo isset($edit_admin_errors['firstname']) ? 'is-invalid' : ''; ?>" 
                                   id="edit_firstname" name="firstname" 
                                   value="<?php echo htmlspecialchars($edit_admin_values['firstname'] ?? $edit_admin_data['firstname'] ?? ''); ?>" required>
                            <?php if (isset($edit_admin_errors['firstname'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $edit_admin_errors['firstname']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div style="flex: 1; min-width: 250px; padding: 0 10px;">
                            <label for="edit_middlename" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="edit_middlename" name="middlename" 
                                   value="<?php echo htmlspecialchars($edit_admin_values['middlename'] ?? $edit_admin_data['middlename'] ?? ''); ?>">
                            <small class="text-muted">Optional</small>
                        </div>
                        
                        <div style="flex: 1; min-width: 250px; padding: 0 10px;">
                            <label for="edit_lastname" class="form-label required">Last Name</label>
                            <input type="text" class="form-control <?php echo isset($edit_admin_errors['lastname']) ? 'is-invalid' : ''; ?>" 
                                   id="edit_lastname" name="lastname" 
                                   value="<?php echo htmlspecialchars($edit_admin_values['lastname'] ?? $edit_admin_data['lastname'] ?? ''); ?>" required>
                            <?php if (isset($edit_admin_errors['lastname'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $edit_admin_errors['lastname']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3" style="display: flex; flex-wrap: wrap; margin-bottom: 20px;">
                        <div style="flex: 1; min-width: 300px; padding: 0 10px;">
                            <label for="edit_username" class="form-label required">Username</label>
                            <input type="text" class="form-control <?php echo isset($edit_admin_errors['username']) ? 'is-invalid' : ''; ?>" 
                                   id="edit_username" name="username" 
                                   value="<?php echo htmlspecialchars($edit_admin_values['username'] ?? $edit_admin_data['username'] ?? ''); ?>" required>
                            <?php if (isset($edit_admin_errors['username'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $edit_admin_errors['username']; ?>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted">Minimum 4 characters</small>
                        </div>
                        
                        <div style="flex: 1; min-width: 300px; padding: 0 10px;">
                            <label for="edit_email" class="form-label required">Email Address</label>
                            <input type="email" class="form-control <?php echo isset($edit_admin_errors['email']) ? 'is-invalid' : ''; ?>" 
                                   id="edit_email" name="email" 
                                   value="<?php echo htmlspecialchars($edit_admin_values['email'] ?? $edit_admin_data['email'] ?? ''); ?>" required>
                            <?php if (isset($edit_admin_errors['email'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $edit_admin_errors['email']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3" style="display: flex; flex-wrap: wrap; margin-bottom: 20px;">
                        <div style="flex: 1; min-width: 300px; padding: 0 10px;">
                            <label for="edit_user_type" class="form-label required">User Type</label>
                            <select class="user-type-select" id="edit_user_type" name="user_type">
                                <option value="admin" <?php echo (($edit_admin_values['user_type'] ?? $edit_admin_data['user_type'] ?? '') == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                <option value="superadmin" <?php echo (($edit_admin_values['user_type'] ?? $edit_admin_data['user_type'] ?? '') == 'superadmin') ? 'selected' : ''; ?>>Super Admin</option>
                            </select>
                            <?php if (isset($edit_admin_errors['user_type'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $edit_admin_errors['user_type']; ?>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted">Super Admins have additional privileges</small>
                        </div>
                    </div>
                    
                    <!-- Change Password Section -->
                    <div class="change-password-container">
                        <div class="change-password-toggle" onclick="togglePasswordFields()">
                            <input type="checkbox" id="change_password" name="change_password" value="1" 
                                   <?php echo (isset($edit_admin_values['change_password']) && $edit_admin_values['change_password']) ? 'checked' : ''; ?>>
                            <label for="change_password">Change Password</label>
                        </div>
                        
                        <div class="password-fields <?php echo (isset($edit_admin_values['change_password']) && $edit_admin_values['change_password']) ? 'show' : ''; ?>" id="passwordFields">
                            <div class="row" style="display: flex; flex-wrap: wrap; margin-top: 15px;">
                                <div style="flex: 1; min-width: 300px; padding: 0 10px;">
                                    <label for="edit_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control <?php echo isset($edit_admin_errors['password']) ? 'is-invalid' : ''; ?>" 
                                           id="edit_password" name="password">
                                    <?php if (isset($edit_admin_errors['password'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $edit_admin_errors['password']; ?>
                                        </div>
                                    <?php endif; ?>
                                    <small class="text-muted">Minimum 8 characters with uppercase and number</small>
                                </div>
                                
                                <div style="flex: 1; min-width: 300px; padding: 0 10px;">
                                    <label for="edit_confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control <?php echo isset($edit_admin_errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                           id="edit_confirm_password" name="confirm_password">
                                    <?php if (isset($edit_admin_errors['confirm_password'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $edit_admin_errors['confirm_password']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-success" style="background: #f0f7ff; border-color: #bae6fd; color: #1a317d; margin-top: 20px;">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Account Information:</strong><br>
                        • Created on <?php 
                        if (isset($edit_admin_data['created_at'])) {
                            echo date('F j, Y', strtotime($edit_admin_data['created_at']));
                        } else {
                            echo 'N/A';
                        }
                        ?><br>
                        <?php if (isset($edit_admin_data['updated_at']) && $edit_admin_data['updated_at'] != '0000-00-00 00:00:00'): ?>
                            • Last updated on <?php echo date('F j, Y', strtotime($edit_admin_data['updated_at'])); ?><br>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="resetEditFormBtn">
                    <i class="fas fa-undo"></i> Reset Changes
                </button>
                <button type="submit" form="editAdminForm" name="edit_admin" class="btn btn-success">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Admin Modal -->
    <div class="modal-overlay" id="deleteAdminModal">
        <div class="modal modal-danger">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-exclamation-triangle"></i> Delete Administrator
                </h3>
                <button class="close-btn" id="closeDeleteModalBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="delete-confirmation">
                    <div class="warning-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    
                    <h4>Confirm Deletion</h4>
                    
                    <p>Are you sure you want to delete this administrator account?</p>
                    <p>This action <strong>cannot be undone</strong> and will permanently remove the account.</p>
                    
                    <div class="admin-info-box" id="deleteAdminInfo">
                        <!-- Admin info will be populated here by JavaScript -->
                    </div>
                    
                    <div class="warning-note">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>Warning:</strong> Deleting this administrator will remove all their access to the system immediately. This action is irreversible.
                    </div>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="deleteAdminForm">
                        <input type="hidden" name="admin_id" id="delete_admin_id" value="">
                    </form>
                </div>
            </div>
            <div class="modal-footer danger">
                <button type="button" class="btn btn-secondary" id="cancelDeleteBtn">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" form="deleteAdminForm" name="delete_admin" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete Administrator
                </button>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <!-- Logo Area -->
        <div class="logo-area">
            <div class="logo-wrapper">
                <div class="logo">
                    <div class="logo-icon">
                        <img src="../img/logo.png" alt="Logo">
                    </div>
                    <h1 class="logo-text">Admin Panel</h1>
                </div>
                <!-- Burger icon toggle button -->
                <button class="toggle-btn" id="toggleBtn">
                    <i class="fas fa-bars"></i>
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Login Indicator -->
        <div class="login-indicator">
            <div class="user-info" data-tooltip="<?php echo htmlspecialchars($_SESSION['username']); ?>">
                <div class="user-avatar">
                    <span><?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?></span>
                    <div class="status-dot"></div>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                    <div class="user-role"><?php echo (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin') ? 'Super Admin' : 'Admin'; ?></div>
                </div>
            </div>
        </div>

        <!-- Menu Items -->
        <ul class="menu-items">
            <li class="menu-item">
                <a href="index.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" data-tooltip="Dashboard">
                    <i class="fas fa-tachometer-alt menu-icon"></i>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="manage_feedback.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_feedback.php' ? 'active' : ''; ?>" data-tooltip="Manage Feedback">
                    <i class="fas fa-list-alt menu-icon"></i>
                    <span class="menu-text">Manage Feedback</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="surveys.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'surveys.php' ? 'active' : ''; ?>" data-tooltip="Surveys">
                    <i class="fas fa-poll menu-icon"></i>
                    <span class="menu-text">Surveys</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="admin_settings.php" class="menu-link active" data-tooltip="Admin Settings">
                    <i class="fas fa-cog menu-icon"></i>
                    <span class="menu-text">Admin Settings</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="user_management.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'user_management.php' ? 'active' : ''; ?>" data-tooltip="User Management">
                    <i class="fas fa-users-cog menu-icon"></i>
                    <span class="menu-text">User Management</span>
                </a>
            </li>
        </ul>

        <!-- Logout Section -->
        <div class="logout-section">
            <a href="#" class="logout-link" id="logoutTrigger" data-tooltip="Logout">
                <i class="fas fa-sign-out-alt menu-icon"></i>
                <span class="logout-text">Logout</span>
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <!-- Header -->
        <div class="header">
            <h1 class="page-title">
                <i class="fas fa-user-shield title-icon"></i>
                Administrator Management
            </h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="theme-toggle-container">
                    <button id="themeToggleBtn" class="btn">
                        <i class="fas fa-moon"></i>
                        <span>Dark Mode</span>
                    </button>
                </div>
                <div class="date-display">
                    <i class="far fa-calendar-alt"></i>
                    <span id="currentDate"><?php echo date('F j, Y'); ?></span>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_admins']; ?></h3>
                    <p>Total Administrators</p>
                </div>
            </div>
            
            <?php if ($stats['superadmin_count'] > 0): ?>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['superadmin_count']; ?></h3>
                    <p>Super Administrators</p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($stats['admin_count'] > 0): ?>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['admin_count']; ?></h3>
                    <p>Regular Administrators</p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($stats['first_admin_created']): ?>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-info">
                    <h3>
                        <?php 
                        echo date('M Y', strtotime($stats['first_admin_created']));
                        ?>
                    </h3>
                    <p>First Admin Created</p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Search Bar -->
        <div class="search-bar">
            <form method="GET" action="" class="search-form">
                <div class="form-group">
                    <label for="search">Search Administrators</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Search by name, username, or email..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="form-group" style="display: flex; gap: 10px;">
                    <button type="submit" class="btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="admin_settings.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>

                <!-- Create New Admin Button -->
        <div class="create-admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
            <h3 style="color: #1a317d; font-size: 20px;">
                <i class="fas fa-list"></i> Administrator List
            </h3>
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin'): ?>
            <button type="button" class="btn btn-success" id="openCreateAdminModal">
                <i class="fas fa-user-plus"></i> Create New Administrator
            </button>
            <?php endif; ?>
        </div>

               <!-- Admin Table -->
        <div class="table-container">
            <!-- Mobile View (Cards) -->
            <div class="admin-cards-mobile" id="mobileAdminView">
                <?php 
                if ($admins_result->num_rows > 0):
                    $admins_result->data_seek(0); // Reset pointer
                    while($admin = $admins_result->fetch_assoc()): 
                ?>
                    <div class="admin-card-mobile-item <?php echo $admin['id'] == $current_admin_id ? 'current-admin-card' : ''; ?>" 
                         id="mobile-admin-<?php echo $admin['id']; ?>">
                        <div class="mobile-admin-card-header">
                            <div class="mobile-admin-avatar">
                                <?php echo strtoupper(substr($admin['firstname'], 0, 1) . substr($admin['lastname'], 0, 1)); ?>
                            </div>
                            <div class="mobile-admin-info-main">
                                <div class="mobile-admin-name">
                                    <?php echo htmlspecialchars($admin['firstname'] . ' ' . $admin['lastname']); ?>
                                    <?php if($admin['id'] == $current_admin_id): ?>
                                        <span class="current-user-badge">You</span>
                                    <?php endif; ?>
                                </div>
                                <div class="mobile-admin-details">
                                    <span class="mobile-admin-username">
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($admin['username']); ?>
                                    </span>
                                    <span class="mobile-admin-email">
                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($admin['email']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="mobile-admin-type">
                                <?php if($admin['user_type'] == 'superadmin'): ?>
                                    <span class="badge badge-purple">Super Admin</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Admin</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mobile-admin-card-body">
                            <?php if(!empty($admin['middlename'])): ?>
                                <div class="mobile-admin-detail-row">
                                    <div class="mobile-detail-label">
                                        <i class="fas fa-user-circle"></i> Middle Name:
                                    </div>
                                    <div class="mobile-detail-value">
                                        <?php echo htmlspecialchars($admin['middlename']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mobile-admin-detail-row">
                                <div class="mobile-detail-label">
                                    <i class="far fa-calendar-alt"></i> Created:
                                </div>
                                <div class="mobile-detail-value">
                                    <?php echo date('M d, Y', strtotime($admin['created_at'])); ?>
                                </div>
                            </div>
                            
                            <div class="mobile-admin-detail-row">
                                <div class="mobile-detail-label">
                                    <i class="fas fa-sync-alt"></i> Updated:
                                </div>
                                <div class="mobile-detail-value">
                                    <?php if(!empty($admin['updated_at']) && $admin['updated_at'] != '0000-00-00 00:00:00'): ?>
                                        <?php echo date('M d, Y', strtotime($admin['updated_at'])); ?>
                                        <br><small><?php echo date('h:i A', strtotime($admin['updated_at'])); ?></small>
                                    <?php else: ?>
                                        <span style="color: #9ca3af;">Never</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mobile-admin-detail-row">
                                <div class="mobile-detail-label">
                                    <i class="fas fa-id-card"></i> Admin ID:
                                </div>
                                <div class="mobile-detail-value">
                                    #<?php echo $admin['id']; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mobile-admin-card-footer">
                            <div class="mobile-action-buttons">
                                <?php if($admin['id'] == $current_admin_id): ?>
                                    <span class="mobile-action-btn btn-secondary" style="cursor: default;">
                                        <i class="fas fa-user"></i> Current Account
                                    </span>

                                <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin'): ?>
                                    <button type="button" class="mobile-action-btn btn-primary edit-admin-btn" 
                                            data-admin-id="<?php echo $admin['id']; ?>"
                                            data-firstname="<?php echo htmlspecialchars($admin['firstname']); ?>"
                                            data-middlename="<?php echo htmlspecialchars($admin['middlename'] ?? ''); ?>"
                                            data-lastname="<?php echo htmlspecialchars($admin['lastname']); ?>"
                                            data-username="<?php echo htmlspecialchars($admin['username']); ?>"
                                            data-email="<?php echo htmlspecialchars($admin['email']); ?>"
                                            data-user-type="<?php echo htmlspecialchars($admin['user_type']); ?>"
                                            data-created-at="<?php echo htmlspecialchars($admin['created_at']); ?>"
                                            data-updated-at="<?php echo htmlspecialchars($admin['updated_at'] ?? ''); ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button type="button" class="mobile-action-btn btn-danger delete-admin-btn" 
                                            data-admin-id="<?php echo $admin['id']; ?>"
                                            data-username="<?php echo htmlspecialchars($admin['username']); ?>"
                                            data-firstname="<?php echo htmlspecialchars($admin['firstname']); ?>"
                                            data-lastname="<?php echo htmlspecialchars($admin['lastname']); ?>"
                                            data-email="<?php echo htmlspecialchars($admin['email']); ?>"
                                            data-user-type="<?php echo htmlspecialchars($admin['user_type']); ?>"
                                            data-created-at="<?php echo htmlspecialchars($admin['created_at']); ?>">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-user-slash"></i>
                        <h3>No Administrators Found</h3>
                        <p>No administrator accounts match your search criteria.</p>
                        <a href="admin_settings.php" class="btn" style="margin-top: 20px;">
                            <i class="fas fa-redo"></i> Reset Filters
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Desktop View (Table) -->
            <div class="admin-table-desktop" id="desktopAdminView">
                <?php if ($admins_result->num_rows > 0): ?>
                    <?php $admins_result->data_seek(0); // Reset pointer for desktop table ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Administrator</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Type</th>
                                <th>Created</th>
                                <th>Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($admin = $admins_result->fetch_assoc()): ?>
                                <tr class="<?php echo $admin['id'] == $current_admin_id ? 'current-user-row' : ''; ?>">
                                    <td>#<?php echo $admin['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($admin['firstname'] . ' ' . $admin['lastname']); ?></strong>
                                        <?php if(!empty($admin['middlename'])): ?>
                                            <br><small><?php echo htmlspecialchars($admin['middlename']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td>
                                        <?php if($admin['user_type'] == 'superadmin'): ?>
                                            <span class="badge badge-purple">Super Admin</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Admin</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                                    <td>
                                        <?php if(!empty($admin['updated_at']) && $admin['updated_at'] != '0000-00-00 00:00:00'): ?>
                                            <?php echo date('M d, Y', strtotime($admin['updated_at'])); ?>
                                            <br><small><?php echo date('h:i A', strtotime($admin['updated_at'])); ?></small>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if($admin['id'] == $current_admin_id): ?>
                                                <span class="btn btn-small btn-secondary" style="cursor: default;">
                                                    <i class="fas fa-user"></i> Current
                                                </span>

                                            <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin'): ?>
                                                <button type="button" class="btn btn-small btn-primary edit-admin-btn" 
                                                        data-admin-id="<?php echo $admin['id']; ?>"
                                                        data-firstname="<?php echo htmlspecialchars($admin['firstname']); ?>"
                                                        data-middlename="<?php echo htmlspecialchars($admin['middlename'] ?? ''); ?>"
                                                        data-lastname="<?php echo htmlspecialchars($admin['lastname']); ?>"
                                                        data-username="<?php echo htmlspecialchars($admin['username']); ?>"
                                                        data-email="<?php echo htmlspecialchars($admin['email']); ?>"
                                                        data-user-type="<?php echo htmlspecialchars($admin['user_type']); ?>"
                                                        data-created-at="<?php echo htmlspecialchars($admin['created_at']); ?>"
                                                        data-updated-at="<?php echo htmlspecialchars($admin['updated_at'] ?? ''); ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-small btn-danger delete-admin-btn" 
                                                        data-admin-id="<?php echo $admin['id']; ?>"
                                                        data-username="<?php echo htmlspecialchars($admin['username']); ?>"
                                                        data-firstname="<?php echo htmlspecialchars($admin['firstname']); ?>"
                                                        data-lastname="<?php echo htmlspecialchars($admin['lastname']); ?>"
                                                        data-email="<?php echo htmlspecialchars($admin['email']); ?>"
                                                        data-user-type="<?php echo htmlspecialchars($admin['user_type']); ?>"
                                                        data-created-at="<?php echo htmlspecialchars($admin['created_at']); ?>">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-user-slash"></i>
                        <h3>No Administrators Found</h3>
                        <p>No administrator accounts match your search criteria.</p>
                        <a href="admin_settings.php" class="btn" style="margin-top: 20px;">
                            <i class="fas fa-redo"></i> Reset Filters
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                    <a href="?page=1&search=<?php echo urlencode($search); ?>" 
                       class="pagination-btn">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search); ?>" 
                       class="pagination-btn">
                        <i class="fas fa-angle-left"></i>
                    </a>
                <?php else: ?>
                    <span class="pagination-btn disabled">
                        <i class="fas fa-angle-double-left"></i>
                    </span>
                    <span class="pagination-btn disabled">
                        <i class="fas fa-angle-left"></i>
                    </span>
                <?php endif; ?>

                <?php
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                       class="pagination-btn <?php echo $i == $current_page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search); ?>" 
                       class="pagination-btn">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>" 
                       class="pagination-btn">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                <?php else: ?>
                    <span class="pagination-btn disabled">
                        <i class="fas fa-angle-right"></i>
                    </span>
                    <span class="pagination-btn disabled">
                        <i class="fas fa-angle-double-right"></i>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Info Alert -->
        <div class="alert alert-success">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Note:</strong> This page shows all administrator accounts. Regular users are managed in the <a href="user_management.php" style="color: #1a317d; font-weight: 600;">User Management</a> section.
                You cannot delete your own account for security reasons.
            </div>
        </div>
    </main>

    <script>
        // DOM Elements
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggleBtn');
        const mobileToggleBtn = document.getElementById('mobileToggleBtn');
        const mainContent = document.getElementById('mainContent');
        const menuLinks = document.querySelectorAll('.menu-link');
        const currentDateElement = document.getElementById('currentDate');
        const overlay = document.getElementById('overlay');
        
        // Modal elements
        const createAdminModal = document.getElementById('createAdminModal');
        const editAdminModal = document.getElementById('editAdminModal');
        const deleteAdminModal = document.getElementById('deleteAdminModal');
        const openCreateAdminModalBtn = document.getElementById('openCreateAdminModal');
        const closeCreateModalBtn = document.getElementById('closeCreateModalBtn');
        const closeEditModalBtn = document.getElementById('closeEditModalBtn');
        const closeDeleteModalBtn = document.getElementById('closeDeleteModalBtn');
        const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
        const resetCreateFormBtn = document.getElementById('resetCreateFormBtn');
        const resetEditFormBtn = document.getElementById('resetEditFormBtn');
        const createAdminForm = document.getElementById('createAdminForm');
        const editAdminForm = document.getElementById('editAdminForm');
        const deleteAdminForm = document.getElementById('deleteAdminForm');
        const editAdminBadge = document.getElementById('editAdminBadge');
        const editUserType = document.getElementById('edit_user_type');
        const changePasswordCheckbox = document.getElementById('change_password');
        const passwordFields = document.getElementById('passwordFields');
        const editAdminButtons = document.querySelectorAll('.edit-admin-btn');
        const deleteAdminButtons = document.querySelectorAll('.delete-admin-btn');
        const deleteAdminInfo = document.getElementById('deleteAdminInfo');
        const deleteAdminIdInput = document.getElementById('delete_admin_id');
        // ========== LOGOUT MODAL FUNCTIONALITY ==========
        // Add this at the end of existing JavaScript

        // Get modal elements
        const logoutModal = document.getElementById('logoutModal');
        const logoutTrigger = document.getElementById('logoutTrigger');
        const closeModal = document.getElementById('closeModal');
        const cancelLogout = document.getElementById('cancelLogout');

        // Function to open logout modal
        function openLogoutModal() {
            logoutModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        // Function to close logout modal
        function closeLogoutModal() {
            logoutModal.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Event listeners
        if (logoutTrigger) {
            logoutTrigger.addEventListener('click', openLogoutModal);
        }

        if (closeModal) {
            closeModal.addEventListener('click', closeLogoutModal);
        }

        if (cancelLogout) {
            cancelLogout.addEventListener('click', closeLogoutModal);
        }

        // Close modal when clicking outside
        if (logoutModal) {
            logoutModal.addEventListener('click', function(e) {
                if (e.target === logoutModal) {
                    closeLogoutModal();
                }
            });
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && logoutModal && logoutModal.classList.contains('active')) {
                closeLogoutModal();
            }
        });

        // Update setupTooltips to include logout button
        const originalSetupTooltips = setupTooltips;
        setupTooltips = function() {
            if (originalSetupTooltips) originalSetupTooltips();
            
            // Add tooltip for logout button
            const logoutItem = document.getElementById('logoutTrigger');
            if (logoutItem) {
                const text = logoutItem.querySelector('.logout-text');
                if (text) {
                    logoutItem.setAttribute('data-tooltip', text.textContent);
                }
            }
        };

        // Call the updated function
        setupTooltips();
        // ========== END LOGOUT MODAL FUNCTIONALITY ==========

        // Toggle Sidebar Function for desktop
        function toggleSidebar() {
            if (window.innerWidth > 768) {
                sidebar.classList.toggle('closed');
                
                // Update title based on state
                if (sidebar.classList.contains('closed')) {
                    toggleBtn.setAttribute('title', 'Expand Sidebar');
                } else {
                    toggleBtn.setAttribute('title', 'Collapse Sidebar');
                }
                
                // Save state to localStorage
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('closed'));
            }
        }

        // Mobile sidebar functions
        function openMobileSidebar() {
            sidebar.classList.add('open');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeMobileSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Modal functions
        function openCreateAdminModal() {
            createAdminModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeCreateAdminModal() {
            createAdminModal.classList.remove('active');
            document.body.style.overflow = '';
        }

        function openEditAdminModal() {
            editAdminModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeEditAdminModal() {
            editAdminModal.classList.remove('active');
            document.body.style.overflow = '';
        }

        function openDeleteAdminModal() {
            deleteAdminModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteAdminModal() {
            deleteAdminModal.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Toggle password fields in edit modal
        function togglePasswordFields() {
            if (changePasswordCheckbox.checked) {
                passwordFields.classList.add('show');
            } else {
                passwordFields.classList.remove('show');
            }
        }

        // Load admin data into edit modal
        function loadAdminDataToEditModal(adminData) {
            // Set form values
            document.getElementById('edit_admin_id').value = adminData.adminId;
            document.getElementById('edit_firstname').value = adminData.firstname;
            document.getElementById('edit_middlename').value = adminData.middlename;
            document.getElementById('edit_lastname').value = adminData.lastname;
            document.getElementById('edit_username').value = adminData.username;
            document.getElementById('edit_email').value = adminData.email;
            document.getElementById('edit_user_type').value = adminData.userType;
            
            // Update badge
            editAdminBadge.textContent = adminData.userType.toUpperCase();
            
            // Clear password fields
            document.getElementById('edit_password').value = '';
            document.getElementById('edit_confirm_password').value = '';
            changePasswordCheckbox.checked = false;
            passwordFields.classList.remove('show');
            
            // Open modal
            openEditAdminModal();
        }

        // Load admin data into delete modal
        function loadAdminDataToDeleteModal(adminData) {
            // Set the admin ID in the form
            deleteAdminIdInput.value = adminData.adminId;
            
            // Format created date
            const createdDate = new Date(adminData.createdAt);
            const formattedDate = createdDate.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            // Create admin info HTML
            const adminInfoHTML = `
                <h5>Administrator Details:</h5>
                <div class="info-row">
                    <span class="info-label">ID:</span>
                    <span class="info-value">#${adminData.adminId}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value">${adminData.firstname} ${adminData.lastname}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Username:</span>
                    <span class="info-value">${adminData.username}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">${adminData.email}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">User Type:</span>
                    <span class="info-value">${adminData.userType === 'superadmin' ? 'Super Admin' : 'Admin'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Created:</span>
                    <span class="info-value">${formattedDate}</span>
                </div>
            `;
            
            // Update the info box
            deleteAdminInfo.innerHTML = adminInfoHTML;
            
            // Open modal
            openDeleteAdminModal();
        }

        // Format and Display Current Date
        function displayCurrentDate() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            currentDateElement.textContent = now.toLocaleDateString('en-US', options);
        }

        // Event Listeners
        toggleBtn.addEventListener('click', toggleSidebar);
        
        mobileToggleBtn.addEventListener('click', openMobileSidebar);
        
        overlay.addEventListener('click', closeMobileSidebar);

        // Close sidebar when clicking on main content (mobile only)
        mainContent.addEventListener('click', function(e) {
            if (window.innerWidth <= 768 && sidebar.classList.contains('open') && 
                !e.target.closest('.mobile-toggle')) {
                closeMobileSidebar();
            }
        });

        // Close sidebar when clicking on a menu link (mobile)
        menuLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    closeMobileSidebar();
                }
            });
        });

        // Modal event listeners
        openCreateAdminModalBtn.addEventListener('click', openCreateAdminModal);
        closeCreateModalBtn.addEventListener('click', closeCreateAdminModal);
        closeEditModalBtn.addEventListener('click', closeEditAdminModal);
        closeDeleteModalBtn.addEventListener('click', closeDeleteAdminModal);
        cancelDeleteBtn.addEventListener('click', closeDeleteAdminModal);
        
        // Close modal when clicking outside
        createAdminModal.addEventListener('click', function(e) {
            if (e.target === createAdminModal) {
                closeCreateAdminModal();
            }
        });
        
        editAdminModal.addEventListener('click', function(e) {
            if (e.target === editAdminModal) {
                closeEditAdminModal();
            }
        });
        
        deleteAdminModal.addEventListener('click', function(e) {
            if (e.target === deleteAdminModal) {
                closeDeleteAdminModal();
            }
        });

        // Reset form buttons
        resetCreateFormBtn.addEventListener('click', function() {
            createAdminForm.reset();
        });
        
        resetEditFormBtn.addEventListener('click', function() {
            // Reload the original admin data from the button
            const editBtn = document.querySelector(`.edit-admin-btn[data-admin-id="${document.getElementById('edit_admin_id').value}"]`);
            if (editBtn) {
                const adminData = {
                    adminId: editBtn.getAttribute('data-admin-id'),
                    firstname: editBtn.getAttribute('data-firstname'),
                    middlename: editBtn.getAttribute('data-middlename'),
                    lastname: editBtn.getAttribute('data-lastname'),
                    username: editBtn.getAttribute('data-username'),
                    email: editBtn.getAttribute('data-email'),
                    userType: editBtn.getAttribute('data-user-type')
                };
                loadAdminDataToEditModal(adminData);
            }
        });

        // Edit admin button listeners
        editAdminButtons.forEach(button => {
            button.addEventListener('click', function() {
                const adminData = {
                    adminId: this.getAttribute('data-admin-id'),
                    firstname: this.getAttribute('data-firstname'),
                    middlename: this.getAttribute('data-middlename'),
                    lastname: this.getAttribute('data-lastname'),
                    username: this.getAttribute('data-username'),
                    email: this.getAttribute('data-email'),
                    userType: this.getAttribute('data-user-type')
                };
                loadAdminDataToEditModal(adminData);
            });
        });

        // Delete admin button listeners
        deleteAdminButtons.forEach(button => {
            button.addEventListener('click', function() {
                const adminData = {
                    adminId: this.getAttribute('data-admin-id'),
                    username: this.getAttribute('data-username'),
                    firstname: this.getAttribute('data-firstname'),
                    lastname: this.getAttribute('data-lastname'),
                    email: this.getAttribute('data-email'),
                    userType: this.getAttribute('data-user-type'),
                    createdAt: this.getAttribute('data-created-at')
                };
                loadAdminDataToDeleteModal(adminData);
            });
        });

        // Change password checkbox listener
        if (changePasswordCheckbox) {
            changePasswordCheckbox.addEventListener('change', togglePasswordFields);
        }

        // Update admin type badge when changed
        if (editUserType) {
            editUserType.addEventListener('change', function() {
                editAdminBadge.textContent = this.value.toUpperCase();
            });
        }

        // Initialize
        displayCurrentDate();

        // Load saved sidebar state
        function loadSidebarState() {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (window.innerWidth > 768) {
                if (isCollapsed) {
                    sidebar.classList.add('closed');
                    toggleBtn.setAttribute('title', 'Expand Sidebar');
                } else {
                    sidebar.classList.remove('closed');
                    toggleBtn.setAttribute('title', 'Collapse Sidebar');
                }
            }
        }

        // Handle window resize
        function handleResize() {
            if (window.innerWidth > 768) {
                // Reset mobile states
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
                mobileToggleBtn.style.display = 'none';
                
                // Make sure toggle button is visible
                toggleBtn.style.display = 'flex';
                
                // Reset sidebar position for desktop
                sidebar.style.left = '0';
                
                // Load saved state
                loadSidebarState();
            } else {
                // Mobile view
                mobileToggleBtn.style.display = 'flex';
                sidebar.classList.remove('closed');
                
                // Make sure toggle button is visible in mobile sidebar
                toggleBtn.style.display = 'flex';
            }
        }

        // Initialize on load
        loadSidebarState();
        handleResize();
        window.addEventListener('resize', handleResize);

        // Handle active menu items - Force Admin Settings to be active
        document.addEventListener('DOMContentLoaded', function() {
            const adminSettingsLink = document.querySelector('a[href="admin_settings.php"]');
            if (adminSettingsLink) {
                // Remove active class from all menu links
                menuLinks.forEach(link => {
                    link.classList.remove('active');
                });
                // Add active class to Admin Settings
                adminSettingsLink.classList.add('active');
            }
        });

        // Add tooltips for collapsed sidebar items
        function setupTooltips() {
            const menuItems = sidebar.querySelectorAll('.menu-link');
            const logoutItem = sidebar.querySelector('.logout-link');
            const userInfo = sidebar.querySelector('.user-info');
            
            menuItems.forEach(item => {
                const text = item.querySelector('.menu-text');
                if (text) {
                    item.setAttribute('data-tooltip', text.textContent);
                }
            });
            
            if (logoutItem) {
                const text = logoutItem.querySelector('.logout-text');
                if (text) {
                    logoutItem.setAttribute('data-tooltip', text.textContent);
                }
            }
            
            if (userInfo) {
                const name = userInfo.querySelector('.user-name');
                if (name) {
                    userInfo.setAttribute('data-tooltip', name.textContent);
                }
            }
        }

        setupTooltips();

        // Add animation to stat cards
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.transform = 'translateY(0)';
                    card.style.opacity = '1';
                }, index * 100);
            });
        });

        // Auto-focus search input if search parameter exists
        <?php if (!empty($search)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('search');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            });
        <?php endif; ?>

        // Password validation for create admin form
        if (createAdminForm) {
            createAdminForm.addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    return false;
                }
                
                if (password.length < 8) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters long!');
                    return false;
                }
                
                if (!/[A-Z]/.test(password) || !/[0-9]/.test(password)) {
                    e.preventDefault();
                    alert('Password must contain at least one uppercase letter and one number!');
                    return false;
                }
                
                return true;
            });
        }

        // Password validation for edit admin form
        if (editAdminForm) {
            editAdminForm.addEventListener('submit', function(e) {
                if (changePasswordCheckbox.checked) {
                    const password = document.getElementById('edit_password').value;
                    const confirmPassword = document.getElementById('edit_confirm_password').value;
                    
                    if (password !== confirmPassword) {
                        e.preventDefault();
                        alert('Passwords do not match!');
                        return false;
                    }
                    
                    if (password.length < 8) {
                        e.preventDefault();
                        alert('Password must be at least 8 characters long!');
                        return false;
                    }
                    
                    if (!/[A-Z]/.test(password) || !/[0-9]/.test(password)) {
                        e.preventDefault();
                        alert('Password must contain at least one uppercase letter and one number!');
                        return false;
                    }
                }
                
                return true;
            });
        }

        // Auto-open modal if there are errors
        <?php if ($show_create_modal): ?>
            document.addEventListener('DOMContentLoaded', function() {
                openCreateAdminModal();
            });
        <?php endif; ?>
        
        <?php if ($show_edit_modal): ?>
            document.addEventListener('DOMContentLoaded', function() {
                openEditAdminModal();
            });
        <?php endif; ?>

        // Handle window resize for responsive admin cards
        function handleAdminCardsResize() {
            const mobileView = document.getElementById('mobileAdminView');
            const desktopView = document.getElementById('desktopAdminView');
            
            if (window.innerWidth <= 768) {
                // Mobile view
                if (mobileView) mobileView.style.display = 'block';
                if (desktopView) desktopView.style.display = 'none';
            } else {
                // Desktop view
                if (mobileView) mobileView.style.display = 'none';
                if (desktopView) desktopView.style.display = 'block';
            }
        }

        // Initialize on load and resize
        handleAdminCardsResize();
        window.addEventListener('resize', handleAdminCardsResize);

        // Update existing edit and delete button event listeners to work with mobile cards
        document.addEventListener('click', function(e) {
            // Handle edit button clicks for mobile cards
            if (e.target.closest('.edit-admin-btn')) {
                const button = e.target.closest('.edit-admin-btn');
                const adminData = {
                    adminId: button.getAttribute('data-admin-id'),
                    firstname: button.getAttribute('data-firstname'),
                    middlename: button.getAttribute('data-middlename'),
                    lastname: button.getAttribute('data-lastname'),
                    username: button.getAttribute('data-username'),
                    email: button.getAttribute('data-email'),
                    userType: button.getAttribute('data-user-type')
                };
                loadAdminDataToEditModal(adminData);
            }
            
            // Handle delete button clicks for mobile cards
            if (e.target.closest('.delete-admin-btn')) {
                const button = e.target.closest('.delete-admin-btn');
                const adminData = {
                    adminId: button.getAttribute('data-admin-id'),
                    username: button.getAttribute('data-username'),
                    firstname: button.getAttribute('data-firstname'),
                    lastname: button.getAttribute('data-lastname'),
                    email: button.getAttribute('data-email'),
                    userType: button.getAttribute('data-user-type'),
                    createdAt: button.getAttribute('data-created-at')
                };
                loadAdminDataToDeleteModal(adminData);
            }
        });

        // Also update the existing button event listeners to prevent duplication
        document.addEventListener('DOMContentLoaded', function() {
            // Update the existing event listeners to only work on desktop
            const editButtons = document.querySelectorAll('.edit-admin-btn');
            const deleteButtons = document.querySelectorAll('.delete-admin-btn');
            
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Only process if we're on desktop view
                    if (window.innerWidth > 768) {
                        const adminData = {
                            adminId: this.getAttribute('data-admin-id'),
                            firstname: this.getAttribute('data-firstname'),
                            middlename: this.getAttribute('data-middlename'),
                            lastname: this.getAttribute('data-lastname'),
                            username: this.getAttribute('data-username'),
                            email: this.getAttribute('data-email'),
                            userType: this.getAttribute('data-user-type')
                        };
                        loadAdminDataToEditModal(adminData);
                    }
                });
            });
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Only process if we're on desktop view
                    if (window.innerWidth > 768) {
                        const adminData = {
                            adminId: this.getAttribute('data-admin-id'),
                            username: this.getAttribute('data-username'),
                            firstname: this.getAttribute('data-firstname'),
                            lastname: this.getAttribute('data-lastname'),
                            email: this.getAttribute('data-email'),
                            userType: this.getAttribute('data-user-type'),
                            createdAt: this.getAttribute('data-created-at')
                        };
                        loadAdminDataToDeleteModal(adminData);
                    }
                });
            });
        });
    </script>
    <script src="../js/theme.js"></script>
</body>
</html>