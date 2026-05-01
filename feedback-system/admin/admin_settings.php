<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireAdmin();

// Handle form submissions
$message = '';
$message_type = '';

// Update admin email
if (isset($_POST['update_email'])) {
    $new_email = $conn->real_escape_string($_POST['admin_email']);
    $sql = "UPDATE settings SET value = '$new_email' WHERE name = 'admin_email'";
    if ($conn->query($sql)) {
        $message = "Admin email updated successfully!";
        $message_type = "success";
    } else {
        $message = "Error updating email: " . $conn->error;
        $message_type = "error";
    }
}

// Update maintenance mode
if (isset($_POST['update_maintenance'])) {
    $maintenance_mode = isset($_POST['maintenance_mode']) ? '1' : '0';
    $sql = "UPDATE settings SET value = '$maintenance_mode' WHERE name = 'maintenance_mode'";
    if ($conn->query($sql)) {
        $message = "Maintenance mode updated successfully!";
        $message_type = "success";
    } else {
        $message = "Error updating maintenance mode: " . $conn->error;
        $message_type = "error";
    }
}

// Update password - FIXED to use admins table
if (isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Verify current password
    $admin_id = $_SESSION['user_id'];

    // First, check if admins table has updated_at column
    $check_column = "SHOW COLUMNS FROM admins LIKE 'updated_at'";
    $result = $conn->query($check_column);
    $has_updated_at = $result->num_rows > 0;

    // Get current admin password from admins table
    $sql = "SELECT password FROM admins WHERE id = $admin_id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        if (password_verify($current_password, $admin['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 8) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    // Update password in admins table with updated_at timestamp
                    if ($has_updated_at) {
                        $sql = "UPDATE admins SET password = '$hashed_password', updated_at = NOW() WHERE id = $admin_id";
                    } else {
                        $sql = "UPDATE admins SET password = '$hashed_password' WHERE id = $admin_id";
                    }

                    if ($conn->query($sql)) {
                        $message = "Password updated successfully!";
                        $message_type = "success";
                    } else {
                        $message = "Error updating password: " . $conn->error;
                        $message_type = "error";
                    }
                } else {
                    $message = "New password must be at least 8 characters long!";
                    $message_type = "error";
                }
            } else {
                $message = "New passwords do not match!";
                $message_type = "error";
            }
        } else {
            $message = "Current password is incorrect!";
            $message_type = "error";
        }
    } else {
        $message = "Admin account not found!";
        $message_type = "error";
    }
}

// Handle create admin form submission
if (isset($_POST['create_admin'])) {
    $firstname = trim($conn->real_escape_string($_POST['firstname']));
    $middlename = trim($conn->real_escape_string($_POST['middlename']));
    $lastname = trim($conn->real_escape_string($_POST['lastname']));
    $username = trim($conn->real_escape_string($_POST['username']));
    $email = trim($conn->real_escape_string($_POST['email']));
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

        // Check if admins table has updated_at column
        $check_column = "SHOW COLUMNS FROM admins LIKE 'updated_at'";
        $result = $conn->query($check_column);
        $has_updated_at = $result->num_rows > 0;

        if ($has_updated_at) {
            $stmt = $conn->prepare("
                INSERT INTO admins 
                (firstname, middlename, lastname, username, email, password, user_type, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'admin', NOW(), NOW())
            ");
        } else {
            $stmt = $conn->prepare("
                INSERT INTO admins 
                (firstname, middlename, lastname, username, email, password, user_type, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'admin', NOW())
            ");
        }

        $stmt->bind_param(
            "ssssss",
            $firstname,
            $middlename,
            $lastname,
            $username,
            $email,
            $hashed_password
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
            'email' => $email
        ];
        $_SESSION['show_create_modal'] = true;
    }
}

// Load current settings
$settings_sql = "SELECT * FROM settings";
$settings_result = $conn->query($settings_sql);
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['name']] = $row['value'];
}

// Load admin info with updated_at if available
$admin_id = $_SESSION['user_id'];
// Check if admins table has updated_at column
$check_column = "SHOW COLUMNS FROM admins LIKE 'updated_at'";
$result = $conn->query($check_column);
$has_updated_at = $result->num_rows > 0;

if ($has_updated_at) {
    $admin_sql = "SELECT username, email, created_at, updated_at FROM admins WHERE id = $admin_id";
} else {
    $admin_sql = "SELECT username, email, created_at FROM admins WHERE id = $admin_id";
}
$admin_result = $conn->query($admin_sql);
$admin = $admin_result->fetch_assoc();

// Check if we should show the create admin modal
$show_create_modal = isset($_SESSION['show_create_modal']) && $_SESSION['show_create_modal'];
$create_admin_errors = isset($_SESSION['create_admin_errors']) ? $_SESSION['create_admin_errors'] : [];
$create_admin_values = isset($_SESSION['create_admin_values']) ? $_SESSION['create_admin_values'] : [];

// Clear session data after retrieving
unset($_SESSION['show_create_modal']);
unset($_SESSION['create_admin_errors']);
unset($_SESSION['create_admin_values']);

// Check for backup messages
if (isset($_SESSION['backup_message'])) {
    $message = $_SESSION['backup_message'];
    $message_type = $_SESSION['backup_message_type'];
    unset($_SESSION['backup_message']);
    unset($_SESSION['backup_message_type']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - Resident Feedback and Survey System</title>
    <link rel="icon" href="../img/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="../css/admin_dark_mode.css?v=<?php echo time(); ?>">
    <style>
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
            color: #ffffff !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.8);
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

        .sidebar.closed~.main-content {
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

        /* Settings Styles */
        .settings-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .settings-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            margin-bottom: 25px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        .settings-header {
            padding: 25px 30px;
            background: linear-gradient(135deg, #f0f7ff, #e0f2fe);
            border-bottom: 1px solid #bae6fd;
        }

        .settings-header h2 {
            color: #1a317d;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 22px;
        }

        .settings-header h2 i {
            color: #1F3A93;
        }

        .settings-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 15px;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            background: white;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1F3A93;
            box-shadow: 0 0 0 3px rgba(31, 58, 147, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #e5e7eb;
            transition: .4s;
            border-radius: 34px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.toggle-slider {
            background-color: #1F3A93;
        }

        input:checked+.toggle-slider:before {
            transform: translateX(30px);
        }

        .toggle-label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            cursor: pointer;
        }

        /* Quick Settings Section */
        .quick-settings {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .theme-preview {
            width: 120px;
            height: 80px;
            border-radius: 10px;
            overflow: hidden;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.3s;
        }

        .theme-preview.active {
            border-color: #1F3A93;
            box-shadow: 0 5px 15px rgba(31, 58, 147, 0.3);
        }

        .theme-preview.light {
            background: linear-gradient(135deg, #f8fafc, #ffffff);
            position: relative;
        }

        .theme-preview.dark {
            background: linear-gradient(135deg, #111827, #1f2937);
            position: relative;
        }

        .theme-preview::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            height: 20px;
            background: #e5e7eb;
            border-radius: 5px;
        }

        .theme-preview.dark::before {
            background: #374151;
        }

        .theme-preview::after {
            content: '';
            position: absolute;
            bottom: 10px;
            left: 10px;
            width: 40px;
            height: 10px;
            background: #1F3A93;
            border-radius: 5px;
        }

        .theme-label {
            text-align: center;
            margin-top: 5px;
            font-size: 14px;
            font-weight: 500;
        }

        .toggle-text {
            font-weight: 500;
            color: #374151;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 28px;
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

        .btn-danger {
            background: linear-gradient(90deg, #ef4444, #f87171);
        }

        .btn-danger:hover {
            background: linear-gradient(90deg, #dc2626, #ef4444);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.2);
        }

        .btn-secondary {
            background: linear-gradient(90deg, #6b7280, #9ca3af);
        }

        .btn-secondary:hover {
            background: linear-gradient(90deg, #4b5563, #6b7280);
            box-shadow: 0 5px 15px rgba(107, 114, 128, 0.2);
        }

        .btn-success {
            background: linear-gradient(90deg, #1F3A93, #3a56b5);
        }

        .btn-success:hover {
            background: linear-gradient(90deg, #1a317d, #1F3A93);
            box-shadow: 0 5px 15px rgba(31, 58, 147, 0.2);
        }

        .btn-primary {
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #2563eb, #3b82f6);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.2);
        }

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

        .setting-description {
            color: #6b7280;
            font-size: 14px;
            margin-top: 5px;
            line-height: 1.5;
        }

        .card-footer {
            padding: 20px 30px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }

        /* Admin Management Card */
        .admin-management-card {
            background: linear-gradient(135deg, #f0f7ff, #e0f2fe);
            border: 2px solid #1F3A93;
        }

        .admin-management-card .settings-header {
            background: linear-gradient(135deg, #1F3A93, #152c71);
        }

        .admin-management-card .settings-header h2 {
            color: white;
        }

        .admin-management-card .settings-header h2 i {
            color: white;
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
            max-width: 800px;
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

        .form-control {
            width: 100%;
            padding: 14px 16px;
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

            .settings-body {
                padding: 20px;
            }

            .card-footer {
                flex-direction: column;
            }

            .card-footer .btn {
                width: 100%;
                justify-content: center;
            }

            .toggle-btn {
                display: flex !important;
            }

            .logo-text {
                font-size: 18px;
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

        /* Additional Elements */
        .notification-badge {
            position: absolute;
            right: 15px;
            background: #ef4444;
            color: white;
            font-size: 11px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

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

        /* ========== DELETE BACKUP MODAL STYLES ========== */
        .modal-danger {
            background-color: #ffffff;
            border: 2px solid #1F3A93;
            /* Green border */
            border-radius: 15px;
            max-height: 85vh;
        }

        .modal-danger .modal-header {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            padding: 20px 25px;
        }

        .warning-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            background: #fee2e2;
            border-radius: 50%;
            font-size: 30px;
            color: #dc2626;
            border: 2px solid #fecaca;
        }

        .delete-confirmation {
            text-align: center;
            padding: 15px;
            max-height: 50vh;
            overflow-y: auto;
        }

        .delete-confirmation h4 {
            color: #991b1b;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .delete-confirmation p {
            color: #374151;
            margin-bottom: 8px;
            line-height: 1.5;
            font-size: 14px;
        }

        .backup-info-box {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            border-radius: 8px;
            padding: 12px;
            margin: 15px 0;
            text-align: left;
            max-height: 200px;
            overflow-y: auto;
            border-left: 3px solid #1F3A93;
            /* Green accent */
        }

        .backup-info-box h5 {
            color: #991b1b;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
        }

        .backup-info-box .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            padding-bottom: 4px;
            border-bottom: 1px dashed #fecaca;
            font-size: 13px;
        }

        .backup-info-box .info-label {
            font-weight: 600;
            color: #374151;
        }

        .backup-info-box .info-value {
            color: #4b5563;
            max-width: 60%;
            text-align: right;
            word-break: break-word;
        }

        .warning-note {
            background: #fef3c7;
            border: 1px solid #fde68a;
            border-radius: 6px;
            padding: 12px;
            margin-top: 15px;
            color: #92400e;
            font-size: 13px;
            line-height: 1.4;
            border-left: 3px solid #f59e0b;
            /* Orange accent */
        }

        .modal-footer.danger {
            background: #fef2f2;
            padding: 15px 25px;
            border-top: 1px solid #fee2e2;
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

        h4 {
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
            margin: 0 auto;
            /* Center the icon */
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
            border: 1px solid #e5e7eb;
            /* Added border */
            border-radius: 10px;
            /* Optional: rounded corners */
            margin: 20px;
            /* Optional: space around the border */
            background: #f9fafb;
            /* Optional: light background for better contrast */
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
            border: 1px solid #d1d5db;
            /* Added border */
            border-radius: 6px;
            /* Optional: rounded corners */
            padding: 10px 12px;
            /* Optional: padding inside border */
            background: rgba(209, 213, 219, 0.1);
            /* Optional: very light background */
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
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes fadeOutOverlay {
            from {
                opacity: 1;
            }

            to {
                opacity: 0;
            }
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
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        @keyframes gradientShiftFast {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        @keyframes iconPulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }

            100% {
                background-position: 200% 0;
            }
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
                flex-direction: column;
                /* Stack icon and text vertically */
            }

            .modal-icon {
                width: 48px;
                height: 48px;
                font-size: 22px;
                margin: 0 auto;
                /* Keep centered */
            }

            .modal-body {
                padding: 20px 16px;
                /* Adjusted padding */
                margin: 15px;
                /* Adjusted margin */
            }

            .modal-subtext {
                padding: 8px 10px;
                /* Adjusted padding */
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

            /* ========== DELETE MODAL STYLES (MATCHING MANAGE FEEDBACK) ========== */
            /* Delete Modal Styles - Made Smaller with Green Border */
            .modal-danger {
                border: 2px solid #1F3A93;
                /* Green border */
                border-radius: 15px;
                max-height: 85vh;
                background: white;
                width: 90%;
                max-width: 450px;
                animation: modalSlideIn 0.4s ease;
                position: relative;
                display: flex;
                flex-direction: column;
            }

            /* Ensure green border persists in dark mode */
            body.dark-mode .modal-danger {
                border: 2px solid #1F3A93;
                background: #1f2937;
            }

            .modal-danger .modal-header {
                background: linear-gradient(135deg, #ef4444, #dc2626) !important;
                padding: 20px 25px;
                border-radius: 13px 13px 0 0;
                display: flex;
                align-items: center;
                justify-content: space-between;
                color: white;
            }

            .modal-danger .modal-header h2 {
                font-size: 20px;
                font-weight: 700;
                margin: 0;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .modal-danger .close-btn {
                background: transparent;
                border: none;
                color: white;
                font-size: 24px;
                cursor: pointer;
                line-height: 1;
            }

            .warning-icon {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 60px;
                height: 60px;
                margin: 0 auto 15px;
                background: #fee2e2;
                border-radius: 50%;
                font-size: 30px;
                color: #dc2626;
                border: 2px solid #fecaca;
                text-align: center;
            }

            .delete-confirmation {
                text-align: center;
                padding: 15px;
                overflow-y: auto;
            }

            .delete-confirmation h4 {
                color: #991b1b;
                margin-bottom: 10px;
                font-size: 18px;
                font-weight: 700;
            }

            .delete-confirmation p {
                color: #374151;
                margin-bottom: 8px;
                line-height: 1.5;
                font-size: 14px;
            }

            .backup-info-box {
                background: #fef2f2;
                border: 1px solid #fee2e2;
                border-radius: 8px;
                padding: 12px;
                margin: 15px 0;
                text-align: left;
                max-height: 200px;
                overflow-y: auto;
                border-left: 3px solid #1F3A93;
                /* Green accent */
            }

            .backup-info-box h5 {
                color: #991b1b;
                margin-bottom: 8px;
                font-size: 14px;
                font-weight: 600;
            }

            .backup-info-box .info-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 4px;
                padding-bottom: 4px;
                border-bottom: 1px dashed #fecaca;
                font-size: 13px;
            }

            .backup-info-box .info-label {
                font-weight: 600;
                color: #374151;
            }

            .backup-info-box .info-value {
                color: #4b5563;
                max-width: 60%;
                text-align: right;
                word-break: break-word;
            }

            .warning-note {
                background: #fef3c7;
                border: 1px solid #fde68a;
                border-radius: 6px;
                padding: 12px;
                margin-top: 15px;
                color: #92400e;
                font-size: 13px;
                line-height: 1.4;
                border-left: 3px solid #f59e0b;
                /* Orange accent */
                text-align: left;
                font-weight: normal;
            }

            .modal-footer.danger {
                background: #fef2f2;
                padding: 15px 25px;
                border-top: 1px solid #fee2e2;
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                border-radius: 0 0 13px 13px;
            }

            /* Dark Mode adjustments for Danger Modal */
            body.dark-mode .modal-footer.danger {
                background: #1f2937;
                border-top-color: #374151;
            }

            .modal-footer.danger .btn {
                padding: 10px 20px;
                font-size: 14px;
            }


            /* ========== END LOGOUT MODAL STYLES - GREEN THEME ========== */

            /* Theme Settings Styles */
            .settings-section {
                margin-bottom: 35px;
            }

            .section-title {
                font-size: 18px;
                font-weight: 600;
                margin-bottom: 15px;
                display: flex;
                align-items: center;
                gap: 10px;
                color: #374151;
            }

            .section-title i {
                color: #1F3A93;
                width: 20px;
            }

            .setting-group {
                background: #f9fafb;
                padding: 20px;
                border-radius: 12px;
                border: 1px solid #e5e7eb;
                margin-bottom: 20px;
            }

            .quick-settings {
                display: flex;
                align-items: center;
                gap: 20px;
                margin-bottom: 30px;
            }

            .theme-preview {
                width: 120px;
                height: 80px;
                border-radius: 10px;
                overflow: hidden;
                cursor: pointer;
                border: 3px solid transparent;
                transition: all 0.3s;
            }

            .theme-preview.active {
                border-color: #1F3A93;
                box-shadow: 0 5px 15px rgba(31, 58, 147, 0.3);
            }

            .theme-preview.light {
                background: linear-gradient(135deg, #f8fafc, #ffffff);
                position: relative;
            }

            .theme-preview.dark {
                background: linear-gradient(135deg, #111827, #1f2937);
                position: relative;
            }

            .theme-preview::before {
                content: '';
                position: absolute;
                top: 10px;
                left: 10px;
                right: 10px;
                height: 20px;
                background: #e5e7eb;
                border-radius: 5px;
            }

            .theme-preview.dark::before {
                background: #374151;
            }

            .theme-preview::after {
                content: '';
                position: absolute;
                bottom: 10px;
                left: 10px;
                width: 40px;
                height: 10px;
                background: #1F3A93;
                border-radius: 5px;
            }

            .theme-label {
                text-align: center;
                margin-top: 5px;
                font-size: 14px;
                font-weight: 500;
                color: #6b7280;
            }

            .radio-group {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }

            .radio-group input {
                display: none;
            }

            .radio-group label {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 12px 20px;
                border-radius: 10px;
                cursor: pointer;
                transition: all 0.3s;
                font-weight: 500;
                background: white;
                border: 1px solid #e5e7eb;
                color: #4b5563;
            }

            .radio-group label:hover {
                border-color: #1F3A93;
                transform: translateY(-2px);
            }

            .radio-group input:checked+label {
                background: linear-gradient(135deg, #1F3A93, #152c71);
                color: white;
                border-color: #1F3A93;
                box-shadow: 0 5px 15px rgba(31, 58, 147, 0.2);
            }

            /* Theme Toggle Button */
            .theme-toggle-container {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-left: 20px;
            }

            #themeToggleBtn {
                background: transparent;
                border: 2px solid #e5e7eb;
                color: #6b7280;
                padding: 10px 20px;
                border-radius: 10px;
                cursor: pointer;
                transition: all 0.3s;
                display: flex;
                align-items: center;
                gap: 8px;
                font-weight: 500;
            }

            #themeToggleBtn:hover {
                border-color: #1F3A93;
                color: #1F3A93;
            }

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

    <!-- ========== DELETE BACKUP MODAL ========== -->
    <div class="modal-overlay" id="deleteBackupModal">
        <div class="modal-danger modal-content">
            <div class="modal-header">
                <h2>
                    <i class="fas fa-exclamation-triangle"></i> Delete Backup
                </h2>
                <button class="close-btn" id="closeDeleteModal">
                    &times;
                </button>
            </div>

            <div class="modal-body">
                <div class="delete-confirmation">
                    <div class="warning-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>

                    <h4>Confirm Deletion</h4>
                    <p>Are you sure you want to delete this backup?</p>
                    <p>This action <strong>cannot be undone</strong> and will permanently remove the backup record.</p>

                    <div class="backup-info-box" id="backupInfoBox">
                        <!-- Populated by JS -->
                    </div>

                    <div class="warning-note">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>Warning:</strong> Deleting this backup is permanent and cannot be recovered.
                    </div>
                </div>
            </div>

            <div class="modal-footer danger">
                <button type="button" class="btn btn-secondary" id="cancelDelete">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <a href="#" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i> Delete Backup
                </a>
            </div>
        </div>
    </div>
    <!-- ========== END DELETE BACKUP MODAL ========== -->

    <!-- Create Admin Modal -->
    <div class="modal-overlay <?php echo $show_create_modal ? 'active' : ''; ?>" id="createAdminModal">
        <div class="modal">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-user-shield"></i> Create Administrator Account
                    <span class="admin-badge">ADMIN ONLY</span>
                </h3>
                <button class="close-btn" id="closeModalBtn">
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

                <div class="alert alert-success"
                    style="background: #f0f7ff; border-color: #bae6fd; color: #1a317d; margin-bottom: 20px;">
                    <i class="fas fa-info-circle"></i> This form creates <strong>Administrator Accounts</strong> only.
                    These accounts will have full system access and privileges.
                </div>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="createAdminForm">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="firstname" class="form-label required">First Name</label>
                            <input type="text"
                                class="form-control <?php echo isset($create_admin_errors['firstname']) ? 'is-invalid' : ''; ?>"
                                id="firstname" name="firstname"
                                value="<?php echo htmlspecialchars($create_admin_values['firstname'] ?? ''); ?>"
                                required>
                            <?php if (isset($create_admin_errors['firstname'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $create_admin_errors['firstname']; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <label for="middlename" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="middlename" name="middlename"
                                value="<?php echo htmlspecialchars($create_admin_values['middlename'] ?? ''); ?>">
                            <small class="text-muted">Optional</small>
                        </div>

                        <div class="col-md-4">
                            <label for="lastname" class="form-label required">Last Name</label>
                            <input type="text"
                                class="form-control <?php echo isset($create_admin_errors['lastname']) ? 'is-invalid' : ''; ?>"
                                id="lastname" name="lastname"
                                value="<?php echo htmlspecialchars($create_admin_values['lastname'] ?? ''); ?>"
                                required>
                            <?php if (isset($create_admin_errors['lastname'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $create_admin_errors['lastname']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="username" class="form-label required">Username</label>
                            <input type="text"
                                class="form-control <?php echo isset($create_admin_errors['username']) ? 'is-invalid' : ''; ?>"
                                id="username" name="username"
                                value="<?php echo htmlspecialchars($create_admin_values['username'] ?? ''); ?>"
                                required>
                            <?php if (isset($create_admin_errors['username'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $create_admin_errors['username']; ?>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted">Minimum 4 characters</small>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label required">Email Address</label>
                            <input type="email"
                                class="form-control <?php echo isset($create_admin_errors['email']) ? 'is-invalid' : ''; ?>"
                                id="email" name="email"
                                value="<?php echo htmlspecialchars($create_admin_values['email'] ?? ''); ?>" required>
                            <?php if (isset($create_admin_errors['email'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $create_admin_errors['email']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="password" class="form-label required">Password</label>
                            <input type="password"
                                class="form-control <?php echo isset($create_admin_errors['password']) ? 'is-invalid' : ''; ?>"
                                id="password" name="password" required>
                            <?php if (isset($create_admin_errors['password'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $create_admin_errors['password']; ?>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted">Minimum 8 characters with uppercase and number</small>
                        </div>

                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label required">Confirm Password</label>
                            <input type="password"
                                class="form-control <?php echo isset($create_admin_errors['confirm_password']) ? 'is-invalid' : ''; ?>"
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
                <button type="button" class="btn btn-secondary" id="resetFormBtn">
                    <i class="fas fa-undo"></i> Clear Form
                </button>
                <button type="submit" form="createAdminForm" name="create_admin" class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Create Administrator
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
                    <div class="user-role">
                        <?php echo (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin') ? 'Super Admin' : 'Admin'; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menu Items -->
        <ul class="menu-items">
            <li class="menu-item">
                <a href="index.php"
                    class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"
                    data-tooltip="Dashboard">
                    <i class="fas fa-tachometer-alt menu-icon"></i>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="manage_feedback.php"
                    class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_feedback.php' ? 'active' : ''; ?>"
                    data-tooltip="Manage Feedback">
                    <i class="fas fa-list-alt menu-icon"></i>
                    <span class="menu-text">Manage Feedback</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="surveys.php"
                    class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'surveys.php' ? 'active' : ''; ?>"
                    data-tooltip="Surveys">
                    <i class="fas fa-poll menu-icon"></i>
                    <span class="menu-text">Surveys</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="admin_settings.php"
                    class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_settings.php' ? 'active' : ''; ?>"
                    data-tooltip="Admin Settings">
                    <i class="fas fa-cog menu-icon"></i>
                    <span class="menu-text">Admin Settings</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="user_management.php"
                    class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'user_management.php' ? 'active' : ''; ?>"
                    data-tooltip="User Management">
                    <i class="fas fa-users-cog menu-icon"></i>
                    <span class="menu-text">User Management</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="system_logs.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'system_logs.php' ? 'active' : ''; ?>" data-tooltip="System Logs">
                    <i class="fas fa-history menu-icon"></i>
                    <span class="menu-text">System Logs</span>
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
                <i class="fas fa-cog title-icon"></i>
                Admin Settings
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

        <div class="settings-container">
            <!-- Account Settings -->
            <div class="settings-card">
                <div class="settings-header">
                    <h2><i class="fas fa-user-shield"></i> Account Settings</h2>
                </div>
                <div class="settings-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="current_username">Username</label>
                            <input type="text" id="current_username"
                                value="<?php echo htmlspecialchars($admin['username'] ?? ''); ?>" disabled>
                            <p class="setting-description">Your admin username (cannot be changed).</p>
                        </div>
                        <div class="form-group">
                            <label for="current_email">Email</label>
                            <input type="email" id="current_email"
                                value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>" disabled>
                            <p class="setting-description">Your registered email address.</p>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="account_created">Account Created</label>
                            <input type="text" id="account_created"
                                value="<?php echo isset($admin['created_at']) ? date('F j, Y, h:i A', strtotime($admin['created_at'])) : 'N/A'; ?>"
                                disabled>
                        </div>
                        <?php if (isset($admin['updated_at']) && $admin['updated_at'] != '0000-00-00 00:00:00'): ?>
                            <div class="form-group">
                                <label for="last_updated">Last Updated</label>
                                <input type="text" id="last_updated"
                                    value="<?php echo date('F j, Y, h:i A', strtotime($admin['updated_at'])); ?>" disabled>
                                <p class="setting-description">Last time your account information was modified.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Appearance Settings -->
            <!-- Appearance Settings -->
            <div class="settings-card" style="padding: 30px;">
                <h2
                    style="margin-bottom: 25px; font-size: 22px; display: flex; align-items: center; gap: 10px; padding-bottom: 15px; border-bottom: 2px solid #f0f7ff; color: #1a317d;">
                    <i class="fas fa-sliders-h" style="color: #1F3A93;"></i> Appearance Settings
                </h2>

                <!-- Theme Section -->
                <div class="settings-section">
                    <h3 class="section-title"
                        style="font-size: 18px; font-weight: 600; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-palette" style="color: #1F3A93; width: 20px;"></i>
                        Theme Settings
                    </h3>
                    <div class="setting-group">
                        <p class="setting-description">
                            Choose your preferred theme. Changes are saved automatically and will persist across
                            sessions.
                        </p>

                        <div class="quick-settings">
                            <div>
                                <div class="theme-preview light" data-theme="light">
                                    <div class="theme-label">Light Mode</div>
                                </div>
                            </div>
                            <div>
                                <div class="theme-preview dark" data-theme="dark">
                                    <div class="theme-label">Dark Mode</div>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 20px;">
                            <div class="radio-group" id="themeSelector">
                                <input type="radio" id="theme-light" name="theme" value="light">
                                <label for="theme-light">
                                    <i class="fas fa-sun"></i>
                                    Light Mode
                                </label>
                                <input type="radio" id="theme-dark" name="theme" value="dark">
                                <label for="theme-dark">
                                    <i class="fas fa-moon"></i>
                                    Dark Mode
                                </label>
                                <input type="radio" id="theme-auto" name="theme" value="auto">
                                <label for="theme-auto">
                                    <i class="fas fa-adjust"></i>
                                    Auto (Follow system)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Change Password section -->
            <div class="settings-card">
                <div class="settings-header">
                    <h2><i class="fas fa-key"></i> Change Password</h2>
                </div>
                <div class="settings-body">
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" required>
                                <p class="setting-description">Enter your current password for verification.</p>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" required>
                                <p class="setting-description">Minimum 8 characters with uppercase and number.</p>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                                <p class="setting-description">Re-enter your new password to confirm.</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="update_password" class="btn">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                            <p class="setting-description" style="margin-top: 10px; color: #1F3A93; font-weight: 500;">
                                <i class="fas fa-info-circle"></i> After changing your password, the old password will
                                no longer work.
                                The update timestamp will be recorded in the system.
                            </p>
                        </div>
                    </form>
                </div>
            </div>

            <!-- System Settings -->
            <div class="settings-card">
                <div class="settings-header">
                    <h2><i class="fas fa-server"></i> System Settings</h2>
                </div>
                <div class="settings-body">
                    <form method="POST">
                        <div class="form-group">
                            <label class="toggle-label">
                                <div class="toggle-switch">
                                    <input type="checkbox" id="maintenance_mode" name="maintenance_mode" <?php echo ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </div>
                                <span class="toggle-text">Maintenance Mode</span>
                            </label>
                            <p class="setting-description">When enabled, only administrators can access the system.</p>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="update_maintenance" class="btn">
                                <i class="fas fa-save"></i> Update System Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Admin Management -->
            <div class="settings-card admin-management-card">
                <div class="settings-header">
                    <h2><i class="fas fa-users-cog"></i> Admin Management</h2>
                </div>
                <div class="settings-body">

                    <div class="form-group">
                        <h3 style="color: #1a317d; margin-bottom: 15px;">Manage Existing Admins</h3>
                        <p class="setting-description">View, edit, or remove existing administrator accounts from the
                            system.</p>
                        <a href="admin_management.php?filter=admins" class="btn btn-primary">
                            <i class="fas fa-users"></i> Manage Administrator Accounts
                        </a>
                    </div>
                </div>
            </div>

            <!-- Database Backup -->
            <div class="settings-card">
                <div class="settings-header">
                    <h2><i class="fas fa-database"></i> Database Backup & Archiving</h2>
                </div>
                <div class="settings-body">
                    <div class="setting-group" style="background: #f0f9ff; border-color: #bae6fd;">
                        <h3 style="color: #0369a1; font-size: 16px; margin-bottom: 10px;">
                            <i class="fas fa-info-circle"></i> About Backups
                        </h3>
                        <p class="setting-description" style="color: #0c4a6e;">
                            Regular backups are essential for data protection. You can generate a full database backup
                            here, download it for safe keeping, or restore it if needed (contact administrator for
                            restoration).
                        </p>
                    </div>

                    <div class="form-group">
                        <a href="backup_db.php?action=create" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Generate New Backup
                        </a>
                    </div>

                    <div style="margin-top: 25px;">
                        <h3 style="margin-bottom: 15px; font-size: 16px; color: #374151;">Existing Backups</h3>
                        <div class="table-responsive"
                            style="border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead style="background: #f9fafb;">
                                    <tr>
                                        <th
                                            style="text-align: left; padding: 12px 20px; font-weight: 600; font-size: 14px; color: #4b5563; border-bottom: 1px solid #e5e7eb;">
                                            Filename</th>
                                        <th
                                            style="padding: 12px 20px; font-weight: 600; font-size: 14px; color: #4b5563; border-bottom: 1px solid #e5e7eb; text-align: center;">
                                            Size</th>
                                        <th
                                            style="padding: 12px 20px; font-weight: 600; font-size: 14px; color: #4b5563; border-bottom: 1px solid #e5e7eb; text-align: center;">
                                            Date Created</th>
                                        <th
                                            style="padding: 12px 20px; font-weight: 600; font-size: 14px; color: #4b5563; border-bottom: 1px solid #e5e7eb; text-align: right;">
                                            Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $backup_dir = __DIR__ . '/backups/';
                                    if (!is_dir($backup_dir)) {
                                        @mkdir($backup_dir, 0755, true);
                                    }
                                    $files = glob($backup_dir . "*.sql");
                                    if ($files && count($files) > 0) {
                                        rsort($files); // Newest first
                                        foreach ($files as $file) {
                                            $filename = basename($file);
                                            $size = round(filesize($file) / 1024, 2) . ' KB';
                                            $date = date('F j, Y, g:i a', filemtime($file));
                                            echo '<tr>';
                                            echo '<td style="padding: 12px 20px; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #374151;">' . $filename . '</td>';
                                            echo '<td style="padding: 12px 20px; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #6b7280; text-align: center;">' . $size . '</td>';
                                            echo '<td style="padding: 12px 20px; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #6b7280; text-align: center;">' . $date . '</td>';
                                            echo '<td style="padding: 12px 20px; border-bottom: 1px solid #e5e7eb; text-align: right;">';
                                            echo '<a href="backup_db.php?action=download&file=' . $filename . '" class="btn" style="padding: 6px 12px; font-size: 12px; background: linear-gradient(90deg, #3b82f6, #60a5fa); margin-right: 5px;" title="Download"><i class="fas fa-download"></i></a>';
                                            echo '<button type="button" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;" onclick="confirmDeleteBackup(\'' . $filename . '\', \'' . $size . '\', \'' . $date . '\')" title="Delete"><i class="fas fa-trash"></i></button>';
                                            echo '</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="4" style="padding: 20px; text-align: center; color: #6b7280;">No backups found.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Admin Login Logs - Super Admin Only -->
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin'): ?>
                <div class="settings-card">
                    <div class="settings-header">
                        <h2><i class="fas fa-history"></i> Admin Login Logs</h2>
                    </div>
                    <div class="settings-body">
                        <div class="setting-group" style="background: #f0f9ff; border-color: #bae6fd;">
                            <h3 style="color: #0369a1; font-size: 16px; margin-bottom: 10px;">
                                <i class="fas fa-info-circle"></i> About Login Logs
                            </h3>
                            <p class="setting-description" style="color: #0c4a6e;">
                                This section displays login activity for all admin accounts. Only Super Admins can view this
                                information for security monitoring purposes.
                            </p>
                        </div>

                        <div style="margin-top: 25px;">
                            <h3 style="margin-bottom: 15px; font-size: 16px; color: #374151;">Recent Login Activity</h3>
                            <div class="table-responsive"
                                style="border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; max-height: 400px; overflow-y: auto;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <thead style="background: #f9fafb; position: sticky; top: 0;">
                                        <tr>
                                            <th
                                                style="text-align: left; padding: 12px 20px; font-weight: 600; font-size: 14px; color: #4b5563; border-bottom: 1px solid #e5e7eb;">
                                                Name</th>
                                            <th
                                                style="padding: 12px 20px; font-weight: 600; font-size: 14px; color: #4b5563; border-bottom: 1px solid #e5e7eb; text-align: center;">
                                                IP Address</th>
                                            <th
                                                style="padding: 12px 20px; font-weight: 600; font-size: 14px; color: #4b5563; border-bottom: 1px solid #e5e7eb; text-align: center;">
                                                Time In</th>
                                            <th
                                                style="padding: 12px 20px; font-weight: 600; font-size: 14px; color: #4b5563; border-bottom: 1px solid #e5e7eb; text-align: center;">
                                                Time Out</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Check if login_logs table exists
                                        $tableExists = $conn->query("SHOW TABLES LIKE 'login_logs'");
                                        if ($tableExists && $tableExists->num_rows > 0) {
                                            // Fetch login logs, ordered by most recent first
                                            $logs_sql = "SELECT name, ip_address, time_in, time_out FROM login_logs ORDER BY time_in DESC LIMIT 50";
                                            $logs_result = $conn->query($logs_sql);

                                            if ($logs_result && $logs_result->num_rows > 0) {
                                                while ($log = $logs_result->fetch_assoc()) {
                                                    $time_in = date('M d, Y h:i A', strtotime($log['time_in']));
                                                    $time_out = $log['time_out'] ? date('M d, Y h:i A', strtotime($log['time_out'])) : '<span style="color: #22c55e; font-weight: 500;"><i class="fas fa-circle" style="font-size: 8px;"></i> Online</span>';

                                                    echo '<tr>';
                                                    echo '<td style="padding: 12px 20px; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #374151; font-weight: 500;">' . htmlspecialchars($log['name']) . '</td>';
                                                    echo '<td style="padding: 12px 20px; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #6b7280; text-align: center; font-family: monospace;">' . htmlspecialchars($log['ip_address']) . '</td>';
                                                    echo '<td style="padding: 12px 20px; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #6b7280; text-align: center;">' . $time_in . '</td>';
                                                    echo '<td style="padding: 12px 20px; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #6b7280; text-align: center;">' . $time_out . '</td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="4" style="padding: 20px; text-align: center; color: #6b7280;">No login logs found.</td></tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="4" style="padding: 20px; text-align: center; color: #6b7280;">Login logs table not yet created. Logs will appear after an admin logs in.</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <script src="../js/theme.js"></script>
    <script>
        // DOM Elements
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggleBtn');
        const mobileToggleBtn = document.getElementById('mobileToggleBtn');
        const mainContent = document.getElementById('mainContent');
        const menuLinks = document.querySelectorAll('.menu-link');
        const currentDateElement = document.getElementById('currentDate');
        const overlay = document.getElementById('overlay');
        const createAdminModal = document.getElementById('createAdminModal');
        const openCreateAdminModalBtn = document.getElementById('openCreateAdminModal');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const resetFormBtn = document.getElementById('resetFormBtn');
        const createAdminForm = document.getElementById('createAdminForm');
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
            logoutModal.addEventListener('click', function (e) {
                if (e.target === logoutModal) {
                    closeLogoutModal();
                }
            });
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && logoutModal && logoutModal.classList.contains('active')) {
                closeLogoutModal();
            }
        });

        // Update setupTooltips to include logout button
        const originalSetupTooltips = setupTooltips;
        setupTooltips = function () {
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
        mainContent.addEventListener('click', function (e) {
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
        closeModalBtn.addEventListener('click', closeCreateAdminModal);

        // Close modal when clicking outside
        createAdminModal.addEventListener('click', function (e) {
            if (e.target === createAdminModal) {
                closeCreateAdminModal();
            }
        });

        // Reset form button
        resetFormBtn.addEventListener('click', function () {
            createAdminForm.reset();
        });

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

        // Handle active menu items
        const currentPage = window.location.pathname.split('/').pop();
        menuLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === currentPage || (currentPage === '' && href === 'index.php')) {
                link.classList.add('active');
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


        // Password validation for create admin form
        if (createAdminForm) {
            createAdminForm.addEventListener('submit', function (e) {
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

        // Password validation for change password form
        const passwordForm = document.querySelector('form[action*="update_password"]');
        if (passwordForm) {
            passwordForm.addEventListener('submit', function (e) {
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;

                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('New passwords do not match!');
                    return false;
                }

                if (newPassword.length < 8) {
                    e.preventDefault();
                    alert('New password must be at least 8 characters long!');
                    return false;
                }

                if (!/[A-Z]/.test(newPassword) || !/[0-9]/.test(newPassword)) {
                    e.preventDefault();
                    alert('New password must contain at least one uppercase letter and one number!');
                    return false;
                }

                return true;
            });
        }

        // ========== DELETE BACKUP MODAL FUNCTIONALITY ==========
        const deleteBackupModal = document.getElementById('deleteBackupModal');
        const closeDeleteModal = document.getElementById('closeDeleteModal');
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        const backupInfoBox = document.getElementById('backupInfoBox');

        function confirmDeleteBackup(filename, size, date) {
            // Populate info box
            backupInfoBox.innerHTML = `
                <h5 style="color: #991b1b; margin-bottom: 8px;">Backup Details:</h5>
                <div class="info-row">
                    <span class="info-label">Filename:</span>
                    <span class="info-value">${filename}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Size:</span>
                    <span class="info-value">${size}</span>
                </div>
                <div class="info-row" style="border-bottom: none;">
                    <span class="info-label">Date Created:</span>
                    <span class="info-value">${date}</span>
                </div>
            `;

            confirmDeleteBtn.href = 'backup_db.php?action=delete&file=' + filename;

            deleteBackupModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteBackupModal() {
            deleteBackupModal.classList.remove('active');
            document.body.style.overflow = '';
        }

        if (closeDeleteModal) closeDeleteModal.addEventListener('click', closeDeleteBackupModal);
        if (cancelDelete) cancelDelete.addEventListener('click', closeDeleteBackupModal);

        // Close on outside click
        if (deleteBackupModal) {
            deleteBackupModal.addEventListener('click', function (e) {
                if (e.target === deleteBackupModal) {
                    closeDeleteBackupModal();
                }
            });
        }

        // Close on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && deleteBackupModal && deleteBackupModal.classList.contains('active')) {
                closeDeleteBackupModal();
            }
        });
        // ========== END DELETE BACKUP MODAL FUNCTIONALITY ==========

        // Auto-open modal if there are errors
        <?php if ($show_create_modal): ?>


            document.addEventListener('DOMContentLoaded', function () {
                openCreateAdminModal();
            });
        <?php endif; ?>
    </script>
</body>

</html>