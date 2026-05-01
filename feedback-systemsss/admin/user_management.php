<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireAdmin();

// Handle create user form submission
if (isset($_POST['create_user'])) {
    $firstname = trim($conn->real_escape_string($_POST['firstname']));
    $lastname = trim($conn->real_escape_string($_POST['lastname']));
    $purok = trim($conn->real_escape_string($_POST['purok']));
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

    if (empty($purok)) {
        $errors['purok'] = 'Purok is required';
    }

    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (strlen($username) < 4) {
        $errors['username'] = 'Username must be at least 4 characters';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors['username'] = 'Username already exists';
        }
        $stmt->close();
    }

    if (!empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors['email'] = 'Email already registered';
            }
            $stmt->close();
        }
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            INSERT INTO users 
            (firstname, lastname, purok, username, email, password, user_type, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'user', NOW())
        ");

        $stmt->bind_param(
            "ssssss",
            $firstname,
            $lastname,
            $purok,
            $username,
            $email,
            $hashed_password
        );

        if ($stmt->execute()) {
            $message = "User account created successfully!";
            $message_type = "success";
        } else {
            $message = "Error creating user account: " . $stmt->error;
            $message_type = "error";
        }

        $stmt->close();
    } else {
        // Store errors in session to display in modal
        $_SESSION['create_user_errors'] = $errors;
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

// Handle edit user form submission
if (isset($_POST['edit_user'])) {
    $user_id = intval($_POST['user_id']);
    $firstname = trim($conn->real_escape_string($_POST['firstname']));
    $lastname = trim($conn->real_escape_string($_POST['lastname']));
    $purok = trim($conn->real_escape_string($_POST['purok']));
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

    if (empty($purok)) {
        $errors['purok'] = 'Purok is required';
    }

    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (strlen($username) < 4) {
        $errors['username'] = 'Username must be at least 4 characters';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $username, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors['username'] = 'Username already exists';
        }
        $stmt->close();
    }

    if (!empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors['email'] = 'Email already registered';
            }
            $stmt->close();
        }
    }

    if ($change_password) {
        if (empty($password)) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        }

        if ($password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match';
        }
    }

    if (empty($errors)) {
        if ($change_password) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                UPDATE users SET 
                firstname = ?, 
                lastname = ?, 
                purok = ?, 
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
                $lastname,
                $purok,
                $username,
                $email,
                $hashed_password,
                $user_type,
                $user_id
            );
        } else {
            $stmt = $conn->prepare("
                UPDATE users SET 
                firstname = ?, 
                lastname = ?, 
                purok = ?, 
                username = ?, 
                email = ?, 
                user_type = ?, 
                updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->bind_param(
                "ssssssi",
                $firstname,
                $lastname,
                $purok,
                $username,
                $email,
                $user_type,
                $user_id
            );
        }

        if ($stmt->execute()) {
            $message = "User account updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating user account: " . $stmt->error;
            $message_type = "error";
        }

        $stmt->close();
    } else {
        // Store errors in session to display in modal
        $_SESSION['edit_user_errors'] = $errors;
        $_SESSION['edit_user_values'] = [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'purok' => $purok,
            'username' => $username,
            'email' => $email,
            'user_type' => $user_type,
            'change_password' => $change_password
        ];
        $_SESSION['edit_user_id'] = $user_id;
        $_SESSION['show_edit_modal'] = true;
    }
}

// Handle delete user
if (isset($_POST['delete_user'])) {
    $user_id = intval($_POST['user_id']);

    // Check if user exists
    $check_sql = "SELECT id, username, firstname, lastname, purok FROM users WHERE id = $user_id";
    $check_result = $conn->query($check_sql);

    if ($check_result->num_rows > 0) {
        $user_data = $check_result->fetch_assoc();
        $delete_sql = "DELETE FROM users WHERE id = $user_id";
        if ($conn->query($delete_sql)) {
            $message = "User '" . htmlspecialchars($user_data['username']) . "' deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Error deleting user: " . $conn->error;
            $message_type = "error";
        }
    } else {
        $message = "User not found!";
        $message_type = "error";
    }
}

// Search functionality
$search = '';
$where_conditions = [];

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $where_conditions[] = "(first_name LIKE '%$search%' OR surname LIKE '%$search%' OR purok LIKE '%$search%' OR username LIKE '%$search%' OR email LIKE '%$search%')";
}

// Build WHERE clause
$where_clause = "WHERE user_role = 'resident'";
if (!empty($where_conditions)) {
    $where_clause .= ' AND ' . implode(' AND ', $where_conditions);
}

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM `profiling-system`.residents $where_clause";
$count_result = $conn->query($count_sql);
$total_users = $count_result->fetch_assoc()['total'];

// Pagination
$per_page = 10;
$total_pages = ceil($total_users / $per_page);
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$current_page = max(1, min($current_page, $total_pages));
$offset = ($current_page - 1) * $per_page;

if ($offset < 0) {
    $offset = 0;
}

// Get users with pagination
$users_sql = "SELECT *, id AS resident_id, id AS user_id, created_at as account_created FROM `profiling-system`.residents $where_clause ORDER BY surname ASC LIMIT $per_page OFFSET $offset";
$users_result = $conn->query($users_sql);

// Get user statistics
$stats_sql = "SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN username IS NOT NULL AND account_status = 'active' THEN 1 ELSE 0 END) as admin_count,
                COUNT(DISTINCT purok) as purok_count,
                MIN(created_at) as first_user_created
              FROM `profiling-system`.residents WHERE user_role = 'resident'";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Get unique puroks for filter
$puroks_sql = "SELECT DISTINCT purok FROM `profiling-system`.residents WHERE purok IS NOT NULL AND purok != '' ORDER BY purok";
$puroks_result = $conn->query($puroks_sql);
$puroks = [];
while ($row = $puroks_result->fetch_assoc()) {
    $puroks[] = $row['purok'];
}

// Check if we should show the create user modal
$show_create_modal = isset($_SESSION['show_create_modal']) && $_SESSION['show_create_modal'];
$create_user_errors = isset($_SESSION['create_user_errors']) ? $_SESSION['create_user_errors'] : [];
$create_user_values = isset($_SESSION['create_user_values']) ? $_SESSION['create_user_values'] : [];

// Check if we should show the edit user modal
$show_edit_modal = isset($_SESSION['show_edit_modal']) && $_SESSION['show_edit_modal'];
$edit_user_errors = isset($_SESSION['edit_user_errors']) ? $_SESSION['edit_user_errors'] : [];
$edit_user_values = isset($_SESSION['edit_user_values']) ? $_SESSION['edit_user_values'] : [];
$edit_user_id = isset($_SESSION['edit_user_id']) ? $_SESSION['edit_user_id'] : '';

// Get user data for edit modal if needed
$edit_user_data = [];
if ($edit_user_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $edit_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_user_data = $result->fetch_assoc();
    $stmt->close();
}

// Clear session data after retrieving
unset($_SESSION['show_create_modal']);
unset($_SESSION['create_user_errors']);
unset($_SESSION['create_user_values']);
unset($_SESSION['show_edit_modal']);
unset($_SESSION['edit_user_errors']);
unset($_SESSION['edit_user_values']);
unset($_SESSION['edit_user_id']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Feedback System</title>
    <link rel="icon" href="../img/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin_dark_mode.css?v=<?php echo time(); ?>">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
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
            min-width: 250px;
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

        .badge-primary {
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

        .user-badge {
            background: #3b82f6;
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

        /* Purok Filter */
        .purok-filter {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }

        .purok-tag {
            background: #e0f2fe;
            color: #0369a1;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid #bae6fd;
        }

        .purok-tag:hover {
            background: #bae6fd;
            transform: translateY(-2px);
        }

        .purok-tag.active {
            background: #0ea5e9;
            color: white;
            border-color: #0ea5e9;
        }

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
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
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

        /* Delete Modal Styles */
        .modal-danger {
            border-color: #1F3A93;
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

        .user-info-box {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
        }

        .user-info-box h5 {
            color: #991b1b;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .user-info-box .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding-bottom: 5px;
            border-bottom: 1px dashed #fecaca;
        }

        .user-info-box .info-label {
            font-weight: 600;
            color: #374151;
        }

        .user-info-box .info-value {
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
        }

        /* ========== END LOGOUT MODAL STYLES - GREEN THEME ========== */

        /* Responsive User Cards for Mobile */
        .user-cards-mobile {
            display: none;
        }

        .user-table-desktop {
            display: block;
        }

        .user-card-mobile-item {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 15px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            transition: all 0.3s;
        }

        .user-card-mobile-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border-color: #1F3A93;
        }

        .mobile-user-card-header {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            gap: 15px;
        }

        .mobile-user-avatar {
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

        .mobile-user-info-main {
            flex: 1;
            min-width: 0;
        }

        .mobile-user-name {
            font-weight: 600;
            color: #1a317d;
            font-size: 16px;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .mobile-user-details {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .mobile-user-username,
        .mobile-user-email {
            font-size: 12px;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .mobile-user-username i,
        .mobile-user-email i {
            color: #1F3A93;
            font-size: 11px;
        }

        .mobile-user-type {
            flex-shrink: 0;
        }

        .mobile-user-card-body {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        .mobile-user-detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
        }

        .mobile-user-detail-row:not(:last-child) {
            border-bottom: 1px dashed #e5e7eb;
        }

        .mobile-detail-label {
            font-size: 13px;
            color: #4b5563;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .mobile-detail-label i {
            color: #1F3A93;
            font-size: 12px;
        }

        .mobile-detail-value {
            font-size: 13px;
            color: #1a317d;
            font-weight: 500;
        }

        .mobile-user-card-footer {
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

        /* Mobile Responsive Adjustments */
        @media (max-width: 768px) {
            .user-cards-mobile {
                display: block;
            }

            .user-table-desktop {
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

            /* Adjust create user button */
            .create-user-header {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }

            #openCreateUserModal {
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
            .user-card-mobile-item {
                border-radius: 10px;
            }

            .mobile-user-card-header {
                padding: 12px;
                gap: 12px;
            }

            .mobile-user-avatar {
                width: 45px;
                height: 45px;
                font-size: 16px;
            }

            .mobile-user-name {
                font-size: 15px;
            }

            .mobile-user-details {
                font-size: 11px;
            }

            .mobile-user-card-body {
                padding: 12px;
            }

            .mobile-user-detail-row {
                padding: 7px 0;
            }

            .mobile-detail-label,
            .mobile-detail-value {
                font-size: 12px;
            }

            .mobile-user-card-footer {
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

            .purok-filter {
                flex-wrap: wrap;
            }

            .purok-tag {
                font-size: 12px;
                padding: 5px 10px;
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


    <!-- Create User Modal -->
    <div class="modal-overlay <?php echo $show_create_modal ? 'active' : ''; ?>" id="createUserModal">
        <div class="modal">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-user-plus"></i> Create New User Account
                    <span class="user-badge">REGULAR USER</span>
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

                <div class="alert alert-success"
                    style="background: #f0f7ff; border-color: #bae6fd; color: #1a317d; margin-bottom: 20px;">
                    <i class="fas fa-info-circle"></i> This form creates <strong>Regular User Accounts</strong>. These
                    accounts will have access to submit feedback and view their submissions.
                </div>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="createUserForm">
                    <div class="row mb-3" style="display: flex; flex-wrap: wrap; margin-bottom: 20px;">
                        <div style="flex: 1; min-width: 250px; padding: 0 10px;">
                            <label for="firstname" class="form-label required">First Name</label>
                            <input type="text"
                                class="form-control <?php echo isset($create_user_errors['firstname']) ? 'is-invalid' : ''; ?>"
                                id="firstname" name="firstname"
                                value="<?php echo htmlspecialchars($create_user_values['firstname'] ?? ''); ?>"
                                required>
                            <?php if (isset($create_user_errors['firstname'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $create_user_errors['firstname']; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="flex: 1; min-width: 250px; padding: 0 10px;">
                            <label for="lastname" class="form-label required">Last Name</label>
                            <input type="text"
                                class="form-control <?php echo isset($create_user_errors['lastname']) ? 'is-invalid' : ''; ?>"
                                id="lastname" name="lastname"
                                value="<?php echo htmlspecialchars($create_user_values['lastname'] ?? ''); ?>" required>
                            <?php if (isset($create_user_errors['lastname'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $create_user_errors['lastname']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row mb-3" style="display: flex; flex-wrap: wrap; margin-bottom: 20px;">
                        <div style="flex: 1; min-width: 300px; padding: 0 10px;">
                            <label for="purok" class="form-label required">Purok/Zone</label>
                            <input type="text"
                                class="form-control <?php echo isset($create_user_errors['purok']) ? 'is-invalid' : ''; ?>"
                                id="purok" name="purok"
                                value="<?php echo htmlspecialchars($create_user_values['purok'] ?? ''); ?>" required>
                            <?php if (isset($create_user_errors['purok'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $create_user_errors['purok']; ?>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted">Enter the purok/zone where the user resides</small>
                        </div>
                    </div>

                    <div class="row mb-3" style="display: flex; flex-wrap: wrap; margin-bottom: 20px;">
                        <div style="flex: 1; min-width: 300px; padding: 0 10px;">
                            <label for="username" class="form-label required">Username</label>
                            <input type="text"
                                class="form-control <?php echo isset($create_user_errors['username']) ? 'is-invalid' : ''; ?>"
                                id="username" name="username"
                                value="<?php echo htmlspecialchars($create_user_values['username'] ?? ''); ?>" required>
                            <?php if (isset($create_user_errors['username'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $create_user_errors['username']; ?>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted">Minimum 4 characters</small>
                        </div>

                        <div style="flex: 1; min-width: 300px; padding: 0 10px;">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email"
                                class="form-control <?php echo isset($create_user_errors['email']) ? 'is-invalid' : ''; ?>"
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

                    <div class="row mb-4" style="display: flex; flex-wrap: wrap; margin-bottom: 30px;">
                        <div style="flex: 1; min-width: 300px; padding: 0 10px;">
                            <label for="password" class="form-label required">Password</label>
                            <input type="password"
                                class="form-control <?php echo isset($create_user_errors['password']) ? 'is-invalid' : ''; ?>"
                                id="password" name="password" required>
                            <?php if (isset($create_user_errors['password'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $create_user_errors['password']; ?>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>

                        <div style="flex: 1; min-width: 300px; padding: 0 10px;">
                            <label for="confirm_password" class="form-label required">Confirm Password</label>
                            <input type="password"
                                class="form-control <?php echo isset($create_user_errors['confirm_password']) ? 'is-invalid' : ''; ?>"
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
                <button type="button" class="btn btn-secondary" id="resetCreateFormBtn">
                    <i class="fas fa-undo"></i> Clear Form
                </button>
                <button type="submit" form="createUserForm" name="create_user" class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Create User
                </button>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal-overlay <?php echo $show_edit_modal ? 'active' : ''; ?>" id="editUserModal">
        <div class="modal">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-user-edit"></i> Edit User Account
                    <span class="user-badge" id="editUserBadge">
                        <?php echo isset($edit_user_values['user_type']) ? strtoupper($edit_user_values['user_type']) :
                            (isset($edit_user_data['user_type']) ? strtoupper($edit_user_data['user_type']) : 'USER'); ?>
                    </span>
                </h3>
                <button class="close-btn" id="closeEditModalBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <?php if (!empty($edit_user_errors) && !isset($edit_user_errors['database'])): ?>
                    <div class="alert alert-error" style="margin-bottom: 20px;">
                        <i class="fas fa-exclamation-circle"></i>
                        Please fix the errors below.
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="editUserForm">
                    <input type="hidden" name="user_id" id="edit_user_id"
                        value="<?php echo $edit_user_id ?: (isset($_GET['edit']) ? intval($_GET['edit']) : ''); ?>">

                    <div class="row mb-3" style="display: flex; flex-wrap: wrap; margin-bottom: 20px;">
                        <div style="flex: 1; min-width: 250px; padding: 0 10px;">
                            <label for="edit_firstname" class="form-label required">First Name</label>
                            <input type="text"
                                class="form-control <?php echo isset($edit_user_errors['firstname']) ? 'is-invalid' : ''; ?>"
                                id="edit_firstname" name="firstname"
                                value="<?php echo htmlspecialchars($edit_user_values['firstname'] ?? $edit_user_data['firstname'] ?? ''); ?>"
                                required>
                            <?php if (isset($edit_user_errors['firstname'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $edit_user_errors['firstname']; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="flex: 1; min-width: 250px; padding: 0 10px;">
                            <label for="edit_lastname" class="form-label required">Last Name</label>
                            <input type="text"
                                class="form-control <?php echo isset($edit_user_errors['lastname']) ? 'is-invalid' : ''; ?>"
                                id="edit_lastname" name="lastname"
                                value="<?php echo htmlspecialchars($edit_user_values['lastname'] ?? $edit_user_data['lastname'] ?? ''); ?>"
                                required>
                            <?php if (isset($edit_user_errors['lastname'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $edit_user_errors['lastname']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row mb-3" style="display: flex; flex-wrap: wrap; margin-bottom: 20px;">
                        <div style="flex: 1; min-width: 300px; padding: 0 10px;">
                            <label for="edit_purok" class="form-label required">Purok/Zone</label>
                            <input type="text"
                                class="form-control <?php echo isset($edit_user_errors['purok']) ? 'is-invalid' : ''; ?>"
                                id="edit_purok" name="purok"
                                value="<?php echo htmlspecialchars($edit_user_values['purok'] ?? $edit_user_data['purok'] ?? ''); ?>"
                                required>
                            <?php if (isset($edit_user_errors['purok'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $edit_user_errors['purok']; ?>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted">Enter the purok/zone where the user resides</small>
                        </div>

                        <div style="flex: 1; min-width: 200px; padding: 0 10px;">
                            <label for="edit_user_type" class="form-label required">User Type</label>
                            <select class="user-type-select" id="edit_user_type" name="user_type">
                                <option value="user" <?php echo (($edit_user_values['user_type'] ?? $edit_user_data['user_type'] ?? '') == 'user') ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo (($edit_user_values['user_type'] ?? $edit_user_data['user_type'] ?? '') == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            </select>
                            <?php if (isset($edit_user_errors['user_type'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $edit_user_errors['user_type']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row mb-3" style="display: flex; flex-wrap: wrap; margin-bottom: 20px;">
                        <div style="flex: 1; min-width: 300px; padding: 0 10px;">
                            <label for="edit_username" class="form-label required">Username</label>
                            <input type="text"
                                class="form-control <?php echo isset($edit_user_errors['username']) ? 'is-invalid' : ''; ?>"
                                id="edit_username" name="username"
                                value="<?php echo htmlspecialchars($edit_user_values['username'] ?? $edit_user_data['username'] ?? ''); ?>"
                                required>
                            <?php if (isset($edit_user_errors['username'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $edit_user_errors['username']; ?>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted">Minimum 4 characters</small>
                        </div>

                        <div style="flex: 1; min-width: 300px; padding: 0 10px;">
                            <label for="edit_email" class="form-label">Email Address</label>
                            <input type="email"
                                class="form-control <?php echo isset($edit_user_errors['email']) ? 'is-invalid' : ''; ?>"
                                id="edit_email" name="email"
                                value="<?php echo htmlspecialchars($edit_user_values['email'] ?? $edit_user_data['email'] ?? ''); ?>">
                            <?php if (isset($edit_user_errors['email'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $edit_user_errors['email']; ?>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted">Optional</small>
                        </div>
                    </div>

                    <!-- Change Password Section -->
                    <div class="change-password-container">
                        <div class="change-password-toggle" onclick="togglePasswordFields()">
                            <input type="checkbox" id="change_password" name="change_password" value="1" <?php echo (isset($edit_user_values['change_password']) && $edit_user_values['change_password']) ? 'checked' : ''; ?>>
                            <label for="change_password">Change Password</label>
                        </div>

                        <div class="password-fields <?php echo (isset($edit_user_values['change_password']) && $edit_user_values['change_password']) ? 'show' : ''; ?>"
                            id="passwordFields">
                            <div class="row" style="display: flex; flex-wrap: wrap; margin-top: 15px;">
                                <div style="flex: 1; min-width: 300px; padding: 0 10px;">
                                    <label for="edit_password" class="form-label">New Password</label>
                                    <input type="password"
                                        class="form-control <?php echo isset($edit_user_errors['password']) ? 'is-invalid' : ''; ?>"
                                        id="edit_password" name="password">
                                    <?php if (isset($edit_user_errors['password'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $edit_user_errors['password']; ?>
                                        </div>
                                    <?php endif; ?>
                                    <small class="text-muted">Leave blank to keep current password</small>
                                </div>

                                <div style="flex: 1; min-width: 300px; padding: 0 10px;">
                                    <label for="edit_confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password"
                                        class="form-control <?php echo isset($edit_user_errors['confirm_password']) ? 'is-invalid' : ''; ?>"
                                        id="edit_confirm_password" name="confirm_password">
                                    <?php if (isset($edit_user_errors['confirm_password'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $edit_user_errors['confirm_password']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-success"
                        style="background: #f0f7ff; border-color: #bae6fd; color: #1a317d; margin-top: 20px;">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> User was created on
                        <?php
                        if (isset($edit_user_data['created_at'])) {
                            echo date('F j, Y', strtotime($edit_user_data['created_at']));
                        } else {
                            echo 'N/A';
                        }
                        ?>
                        <?php if (isset($edit_user_data['updated_at']) && $edit_user_data['updated_at'] != '0000-00-00 00:00:00'): ?>
                            <br>Last updated on <?php echo date('F j, Y', strtotime($edit_user_data['updated_at'])); ?>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="resetEditFormBtn">
                    <i class="fas fa-undo"></i> Reset Changes
                </button>
                <button type="submit" form="editUserForm" name="edit_user" class="btn btn-success">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal-overlay" id="deleteUserModal">
        <div class="modal modal-danger">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-exclamation-triangle"></i> Delete User
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

                    <p>Are you sure you want to delete this user account?</p>
                    <p>This action <strong>cannot be undone</strong> and will permanently remove the account and all
                        associated feedback submissions.</p>

                    <div class="user-info-box" id="deleteUserInfo">
                        <!-- User info will be populated here by JavaScript -->
                    </div>

                    <div class="warning-note">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>Warning:</strong> Deleting this user will permanently remove their account and all their
                        feedback submissions. This action is irreversible.
                    </div>

                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>"
                        id="deleteUserForm">
                        <input type="hidden" name="user_id" id="delete_user_id" value="">
                    </form>
                </div>
            </div>
            <div class="modal-footer danger">
                <button type="button" class="btn btn-secondary" id="cancelDeleteBtn">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" form="deleteUserForm" name="delete_user" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete User
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
                <a href="user_management.php" class="menu-link active" data-tooltip="User Management">
                    <i class="fas fa-users-cog menu-icon"></i>
                    <span class="menu-text">User Management</span>
                </a>
            </li>
        </ul>

        <div class="logout-section">
            <a href="#" class="logout-link" id="logoutTrigger" data-tooltip="Logout">
                <i class="fas fa-sign-out-alt menu-icon"></i>
                <span class="logout-text">Logout</span>
            </a>
        </div>
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
                    <a href="/htdocs/dashboard.php" class="modal-btn modal-btn-primary">
                        <i class="fas fa-sign-out-alt"></i> Yes, Logout
                    </a>
                </div>
            </div>
        </div>
        <!-- ========== END LOGOUT MODAL ========== -->
    </nav>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <!-- Header -->
        <div class="header">
            <h1 class="page-title">
                <i class="fas fa-users title-icon"></i>
                User Management
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
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_users']; ?></h3>
                    <p>Total Residents</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['purok_count']; ?></h3>
                    <p>Puroks/Zones</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-info">
                    <h3>
                        <?php
                        if ($stats['first_user_created']) {
                            echo date('M Y', strtotime($stats['first_user_created']));
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </h3>
                    <p>First Account</p>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="search-bar">
            <form method="GET" action="" class="search-form">
                <div class="form-group">
                    <label for="search">Search Users</label>
                    <input type="text" class="form-control" id="search" name="search"
                        placeholder="Search by name, username, email, or purok..."
                        value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <div class="form-group" style="display: flex; gap: 10px;">
                    <button type="submit" class="btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="user_management.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>

            <!-- Purok Filter -->
            <?php if (!empty($puroks)): ?>
                <div style="margin-top: 20px;">
                    <label
                        style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151; font-size: 14px;">Filter
                        by Purok:</label>
                    <div class="purok-filter">
                        <?php foreach ($puroks as $purok): ?>
                            <span class="purok-tag" data-purok="<?php echo htmlspecialchars($purok); ?>">
                                <?php echo htmlspecialchars($purok); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Action Buttons -->
        <div class="create-user-header"
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
            <h3 style="color: #1a317d; font-size: 20px;">
                <i class="fas fa-list"></i> User List
            </h3>
            <div style="display: flex; gap: 10px;">
                <a href="../user/login.php?preview=true" class="btn btn-primary" target="_blank">
                    <i class="fas fa-sign-in-alt"></i> Go to User Login
                </a>
                <button type="button" class="btn btn-success" id="openCreateUserModal">
                    <i class="fas fa-user-plus"></i> Create New User
                </button>
            </div>
        </div>

        <!-- User Table -->
        <div class="table-container">
            <!-- Mobile View (Cards) -->
            <div class="user-cards-mobile" id="mobileUserView">
                <?php
                if ($users_result->num_rows > 0):
                    $users_result->data_seek(0); // Reset pointer
                    while ($user = $users_result->fetch_assoc()):
                        ?>
                        <div class="user-card-mobile-item" id="mobile-resident-<?php echo $user['resident_id']; ?>">
                            <div class="mobile-user-card-header">
                                <div class="mobile-user-avatar">
                                    <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['surname'], 0, 1)); ?>
                                </div>
                                <div class="mobile-user-info-main">
                                    <div class="mobile-user-name">
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['surname']); ?>
                                    </div>
                                    <div class="mobile-user-details">
                                        <?php if (!empty($user['username'])): ?>
                                            <span class="mobile-user-username">
                                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['username']); ?>
                                            </span>
                                            <?php if (!empty($user['email'])): ?>
                                                <span class="mobile-user-email"
                                                    style="display:block;margin-top:5px;font-size:12px;color:#6b7280;">
                                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="mobile-user-username" style="color:#d1d5db;">
                                                <i class="fas fa-user-slash"></i> No Account
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="mobile-user-type">
                                    <?php if (!empty($user['username']) && $user['account_status'] === 'active'): ?>
                                        <span class="badge badge-success">Active Account</span>
                                    <?php else: ?>
                                        <span class="badge" style="background:#f3f4f6;color:#6b7280;border:1px solid #d1d5db;">No
                                            Account</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mobile-user-card-body">
                                <div class="mobile-user-detail-row">
                                    <div class="mobile-detail-label">
                                        <i class="fas fa-map-marker-alt"></i> Purok:
                                    </div>
                                    <div class="mobile-detail-value">
                                        <?php if (!empty($user['purok'])): ?>
                                            <span class="badge badge-primary"><?php echo htmlspecialchars($user['purok']); ?></span>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">N/A</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mobile-user-detail-row">
                                    <div class="mobile-detail-label">
                                        <i class="fas fa-venus-mars"></i> Age / Sex:
                                    </div>
                                    <div class="mobile-detail-value">
                                        <?php echo htmlspecialchars($user['age']); ?> yrs,
                                        <?php echo htmlspecialchars($user['sex']); ?>
                                    </div>
                                </div>

                                <div class="mobile-user-detail-row">
                                    <div class="mobile-detail-label">
                                        <i class="fas fa-id-card"></i> Resident ID:
                                    </div>
                                    <div class="mobile-detail-value">
                                        #<?php echo $user['resident_id']; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="mobile-user-card-footer">
                                <div class="mobile-action-buttons">
                                    <?php if (!empty($user['username']) && $user['account_status'] === 'active'): ?>
                                        <button type="button" class="mobile-action-btn btn-primary edit-user-btn"
                                            data-user-id="<?php echo $user['user_id']; ?>"
                                            data-firstname="<?php echo htmlspecialchars($user['first_name']); ?>"
                                            data-lastname="<?php echo htmlspecialchars($user['surname']); ?>"
                                            data-purok="<?php echo htmlspecialchars($user['purok']); ?>"
                                            data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                            data-email="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" data-user-type="user"
                                            data-created-at="<?php echo htmlspecialchars($user['account_created'] ?? ''); ?>">
                                            <i class="fas fa-edit"></i> Edit Account
                                        </button>
                                        <button type="button" class="mobile-action-btn btn-danger delete-user-btn"
                                            data-user-id="<?php echo $user['user_id']; ?>"
                                            data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                            data-firstname="<?php echo htmlspecialchars($user['first_name']); ?>"
                                            data-lastname="<?php echo htmlspecialchars($user['surname']); ?>"
                                            data-email="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                            data-purok="<?php echo htmlspecialchars($user['purok'] ?? ''); ?>"
                                            data-user-type="user">
                                            <i class="fas fa-trash"></i> Delete Account
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="mobile-action-btn btn-success"
                                            onclick="document.getElementById('firstname').value='<?php echo addslashes($user['first_name']); ?>'; document.getElementById('lastname').value='<?php echo addslashes($user['surname']); ?>'; document.getElementById('purok').value='<?php echo addslashes($user['purok']); ?>'; new bootstrap.Modal(document.getElementById('createUserModal')).show();">
                                            <i class="fas fa-user-plus"></i> Create Account
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-user-slash"></i>
                        <h3>No Users Found</h3>
                        <p>No user accounts match your search criteria.</p>
                        <a href="user_management.php" class="btn" style="margin-top: 20px;">
                            <i class="fas fa-redo"></i> Reset Filters
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Desktop View (Table) -->
            <div class="user-table-desktop" id="desktopUserView">
                <?php if ($users_result->num_rows > 0): ?>
                    <?php $users_result->data_seek(0); // Reset pointer for desktop table ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Resident ID</th>
                                <th>Resident Name</th>
                                <th>Purok</th>
                                <th>Age/Sex</th>
                                <th>Feedback Username</th>
                                <th>Account Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $users_result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $user['resident_id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['surname']); ?></strong>
                                    </td>
                                    <td>
                                        <?php if (!empty($user['purok'])): ?>
                                            <span class="badge badge-primary"><?php echo htmlspecialchars($user['purok']); ?></span>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['age']); ?> /
                                        <?php echo htmlspecialchars($user['sex']); ?>
                                    </td>
                                    <td><?php echo !empty($user['username']) ? htmlspecialchars($user['username']) : '<span style="color:#d1d5db;">N/A</span>'; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($user['username']) && $user['account_status'] === 'active'): ?>
                                            <span class="badge badge-success">Active Account</span>
                                        <?php else: ?>
                                            <span class="badge"
                                                style="background:#f3f4f6;color:#6b7280;border:1px solid #d1d5db;">No Account</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if (!empty($user['username']) && $user['account_status'] === 'active'): ?>
                                                <button type="button" class="btn btn-small btn-primary edit-user-btn"
                                                    data-user-id="<?php echo $user['user_id']; ?>"
                                                    data-firstname="<?php echo htmlspecialchars($user['first_name']); ?>"
                                                    data-lastname="<?php echo htmlspecialchars($user['surname']); ?>"
                                                    data-purok="<?php echo htmlspecialchars($user['purok']); ?>"
                                                    data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                    data-email="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                                    data-user-type="user">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-small btn-danger delete-user-btn"
                                                    data-user-id="<?php echo $user['user_id']; ?>"
                                                    data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                    data-firstname="<?php echo htmlspecialchars($user['first_name']); ?>"
                                                    data-lastname="<?php echo htmlspecialchars($user['surname']); ?>"
                                                    data-user-type="user">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-small btn-success"
                                                    onclick="document.getElementById('firstname').value='<?php echo addslashes($user['first_name']); ?>'; document.getElementById('lastname').value='<?php echo addslashes($user['surname']); ?>'; document.getElementById('purok').value='<?php echo addslashes($user['purok']); ?>'; document.getElementById('username').focus(); new bootstrap.Modal(document.getElementById('createUserModal')).show();">
                                                    <i class="fas fa-plus"></i> Create Account
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
                        <h3>No Users Found</h3>
                        <p>No user accounts match your search criteria.</p>
                        <a href="user_management.php" class="btn" style="margin-top: 20px;">
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
                    <a href="?page=1&search=<?php echo urlencode($search); ?>" class="pagination-btn">
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
                    <a href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>" class="pagination-btn">
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
                <strong>Note:</strong> This page shows all regular user accounts. Administrator accounts are managed in
                the <a href="admin_settings.php" style="color: #1a317d; font-weight: 600;">Admin Settings</a> section.
                Deleting a user will permanently remove their account and all associated feedback submissions.
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
        const createUserModal = document.getElementById('createUserModal');
        const editUserModal = document.getElementById('editUserModal');
        const deleteUserModal = document.getElementById('deleteUserModal');
        const openCreateUserModalBtn = document.getElementById('openCreateUserModal');
        const closeCreateModalBtn = document.getElementById('closeCreateModalBtn');
        const closeEditModalBtn = document.getElementById('closeEditModalBtn');
        const closeDeleteModalBtn = document.getElementById('closeDeleteModalBtn');
        const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
        const resetCreateFormBtn = document.getElementById('resetCreateFormBtn');
        const resetEditFormBtn = document.getElementById('resetEditFormBtn');
        const createUserForm = document.getElementById('createUserForm');
        const editUserForm = document.getElementById('editUserForm');
        const deleteUserForm = document.getElementById('deleteUserForm');
        const purokTags = document.querySelectorAll('.purok-tag');
        const searchInput = document.getElementById('search');
        const editUserBadge = document.getElementById('editUserBadge');
        const editUserType = document.getElementById('edit_user_type');
        const changePasswordCheckbox = document.getElementById('change_password');
        const passwordFields = document.getElementById('passwordFields');
        const editUserButtons = document.querySelectorAll('.edit-user-btn');
        const deleteUserButtons = document.querySelectorAll('.delete-user-btn');
        const deleteUserInfo = document.getElementById('deleteUserInfo');
        const deleteUserIdInput = document.getElementById('delete_user_id');
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
        function openCreateUserModal() {
            createUserModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeCreateUserModal() {
            createUserModal.classList.remove('active');
            document.body.style.overflow = '';
        }

        function openEditUserModal() {
            editUserModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeEditUserModal() {
            editUserModal.classList.remove('active');
            document.body.style.overflow = '';
        }

        function openDeleteUserModal() {
            deleteUserModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteUserModal() {
            deleteUserModal.classList.remove('active');
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

        // Load user data into edit modal
        function loadUserDataToEditModal(userData) {
            // Set form values
            document.getElementById('edit_user_id').value = userData.userId;
            document.getElementById('edit_firstname').value = userData.firstname;
            document.getElementById('edit_lastname').value = userData.lastname;
            document.getElementById('edit_purok').value = userData.purok;
            document.getElementById('edit_username').value = userData.username;
            document.getElementById('edit_email').value = userData.email;
            document.getElementById('edit_user_type').value = userData.userType;

            // Update badge
            editUserBadge.textContent = userData.userType.toUpperCase();

            // Clear password fields
            document.getElementById('edit_password').value = '';
            document.getElementById('edit_confirm_password').value = '';
            changePasswordCheckbox.checked = false;
            passwordFields.classList.remove('show');

            // Open modal
            openEditUserModal();
        }

        // Load user data into delete modal
        function loadUserDataToDeleteModal(userData) {
            // Set the user ID in the form
            deleteUserIdInput.value = userData.userId;

            // Format created date
            const createdDate = new Date(userData.createdAt);
            const formattedDate = createdDate.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            // Create user info HTML
            const userInfoHTML = `
                <h5>User Details:</h5>
                <div class="info-row">
                    <span class="info-label">ID:</span>
                    <span class="info-value">#${userData.userId}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value">${userData.firstname} ${userData.lastname}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Username:</span>
                    <span class="info-value">${userData.username}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">${userData.email || 'N/A'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Purok:</span>
                    <span class="info-value">${userData.purok || 'N/A'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">User Type:</span>
                    <span class="info-value">${userData.userType === 'admin' ? 'Admin' : 'Regular User'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Created:</span>
                    <span class="info-value">${formattedDate}</span>
                </div>
            `;

            // Update the info box
            deleteUserInfo.innerHTML = userInfoHTML;

            // Open modal
            openDeleteUserModal();
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

        // Purok tag filter
        purokTags.forEach(tag => {
            tag.addEventListener('click', function () {
                const purok = this.getAttribute('data-purok');
                searchInput.value = purok;

                // Remove active class from all tags
                purokTags.forEach(t => t.classList.remove('active'));
                // Add active class to clicked tag
                this.classList.add('active');

                // Submit the form
                this.closest('form').submit();
            });
        });

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
        openCreateUserModalBtn.addEventListener('click', openCreateUserModal);
        closeCreateModalBtn.addEventListener('click', closeCreateUserModal);
        closeEditModalBtn.addEventListener('click', closeEditUserModal);
        closeDeleteModalBtn.addEventListener('click', closeDeleteUserModal);
        cancelDeleteBtn.addEventListener('click', closeDeleteUserModal);

        // Close modal when clicking outside
        createUserModal.addEventListener('click', function (e) {
            if (e.target === createUserModal) {
                closeCreateUserModal();
            }
        });

        editUserModal.addEventListener('click', function (e) {
            if (e.target === editUserModal) {
                closeEditUserModal();
            }
        });

        deleteUserModal.addEventListener('click', function (e) {
            if (e.target === deleteUserModal) {
                closeDeleteUserModal();
            }
        });

        // Reset form buttons
        resetCreateFormBtn.addEventListener('click', function () {
            createUserForm.reset();
        });

        resetEditFormBtn.addEventListener('click', function () {
            // Reload the original user data from the button
            const editBtn = document.querySelector(`.edit-user-btn[data-user-id="${document.getElementById('edit_user_id').value}"]`);
            if (editBtn) {
                const userData = {
                    userId: editBtn.getAttribute('data-user-id'),
                    firstname: editBtn.getAttribute('data-firstname'),
                    lastname: editBtn.getAttribute('data-lastname'),
                    purok: editBtn.getAttribute('data-purok'),
                    username: editBtn.getAttribute('data-username'),
                    email: editBtn.getAttribute('data-email'),
                    userType: editBtn.getAttribute('data-user-type')
                };
                loadUserDataToEditModal(userData);
            }
        });

        // Edit user button listeners
        editUserButtons.forEach(button => {
            button.addEventListener('click', function () {
                const userData = {
                    userId: this.getAttribute('data-user-id'),
                    firstname: this.getAttribute('data-firstname'),
                    lastname: this.getAttribute('data-lastname'),
                    purok: this.getAttribute('data-purok'),
                    username: this.getAttribute('data-username'),
                    email: this.getAttribute('data-email'),
                    userType: this.getAttribute('data-user-type')
                };
                loadUserDataToEditModal(userData);
            });
        });

        // Delete user button listeners
        deleteUserButtons.forEach(button => {
            button.addEventListener('click', function () {
                const userData = {
                    userId: this.getAttribute('data-user-id'),
                    username: this.getAttribute('data-username'),
                    firstname: this.getAttribute('data-firstname'),
                    lastname: this.getAttribute('data-lastname'),
                    email: this.getAttribute('data-email'),
                    purok: this.getAttribute('data-purok'),
                    userType: this.getAttribute('data-user-type'),
                    createdAt: this.getAttribute('data-created-at')
                };
                loadUserDataToDeleteModal(userData);
            });
        });

        // Change password checkbox listener
        if (changePasswordCheckbox) {
            changePasswordCheckbox.addEventListener('change', togglePasswordFields);
        }

        // Update user type badge when changed
        if (editUserType) {
            editUserType.addEventListener('change', function () {
                editUserBadge.textContent = this.value.toUpperCase();
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

        // Handle active menu items - Force User Management to be active
        document.addEventListener('DOMContentLoaded', function () {
            const userManagementLink = document.querySelector('a[href="user_management.php"]');
            if (userManagementLink) {
                // Remove active class from all menu links
                menuLinks.forEach(link => {
                    link.classList.remove('active');
                });
                // Add active class to User Management
                userManagementLink.classList.add('active');
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
        document.addEventListener('DOMContentLoaded', function () {
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
            document.addEventListener('DOMContentLoaded', function () {
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();

                    // Highlight active purok tag if search matches a purok
                    purokTags.forEach(tag => {
                        const purok = tag.getAttribute('data-purok');
                        if (purok === '<?php echo $search; ?>') {
                            tag.classList.add('active');
                        }
                    });
                }
            });
        <?php endif; ?>

        // ========== CLIENT-SIDE VALIDATION ========== 
        // Logic adapted from user/login.php and user/profile.php

        function setupNameValidation(inputId) {
            const input = document.getElementById(inputId);
            if (!input) return;

            // Create error container
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

            input.addEventListener('input', function () {
                let val = this.value;
                const originalVal = val;

                // Auto Capitalization
                let words = val.split(' ');
                for (let i = 0; i < words.length; i++) {
                    if (words[i].length > 0) {
                        words[i] = words[i][0].toUpperCase() + words[i].substring(1);
                    }
                }
                const capitalized = words.join(' ');

                // Only update value if capitalization changed it
                if (val !== capitalized) {
                    // Save cursor position
                    const start = this.selectionStart;
                    const end = this.selectionEnd;
                    this.value = capitalized;
                    this.setSelectionRange(start, end);
                    val = capitalized;
                }

                // Validation: Only letters (a-z, ñ/Ñ)
                // Regex: /[^a-zA-ZñÑ\s]/g
                const invalidChars = /[^a-zA-ZñÑ\s]/g;
                if (invalidChars.test(val)) {
                    this.classList.add('is-invalid');
                    errorDiv.textContent = 'Only letters (a-z, ñ/Ñ) are allowed.';
                    errorDiv.style.display = 'block';
                } else {
                    this.classList.remove('is-invalid');
                    errorDiv.style.display = 'none';
                }
            });
        }

        function setupNumericValidation(inputId) {
            const input = document.getElementById(inputId);
            if (!input) return;

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

            input.addEventListener('input', function () {
                let val = this.value;
                // Simply warn if non-numeric
                const invalidChars = /[^0-9]/g;

                if (invalidChars.test(val)) {
                    // Optionally strip them? User requirement summary says "Allowed only numeric values... Disallowed letters..." 
                    // and "Added numeric-only validation... removing...".
                    // Login.php logic was typically strict replacement or warning. 
                    // Let's do strict replacement for Purok as it's often an ID or simple number.
                    this.value = val.replace(/[^0-9]/g, '');

                    // Show temporal error saying only numbers allowed? 
                    // Or just strict replacement.
                    // The previous summary said "Error Message: Only numbers are allowed."
                    // So I should show error if they try to type bad stuff.

                    this.classList.add('is-invalid');
                    errorDiv.textContent = 'Only numbers are allowed.';
                    errorDiv.style.display = 'block';

                    // Hide after 2 seconds
                    setTimeout(() => {
                        this.classList.remove('is-invalid');
                        errorDiv.style.display = 'none';
                    }, 2000);
                } else {
                    this.classList.remove('is-invalid');
                    errorDiv.style.display = 'none';
                }
            });
        }

        function setupUsernameValidation(inputId) {
            const input = document.getElementById(inputId);
            if (!input) return;

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

            input.addEventListener('input', function () {
                let val = this.value;
                const originalVal = val;

                // Remove spaces strictly
                val = val.replace(/\s/g, '');
                if (val !== originalVal) {
                    this.value = val;
                }

                // Check for invalid chars (anything that isn't Letter, Number, Underscore, Dot)
                // Allowed: [a-zA-Z0-9_.]
                const invalidChars = /[^a-zA-Z0-9_.]/g;
                if (invalidChars.test(val)) {
                    this.classList.add('is-invalid');
                    errorDiv.textContent = 'Only letters, numbers, underscore (_), and dot (.) are allowed. No spaces.';
                    errorDiv.style.display = 'block';
                } else {
                    this.classList.remove('is-invalid');
                    errorDiv.style.display = 'none';
                }
            });
        }

        function setupEmailValidation(inputId) {
            const input = document.getElementById(inputId);
            if (!input) return;

            // Email usually relies on browser type="email" + strict cleaning
            input.addEventListener('input', function () {
                let val = this.value;
                let originalVal = val;

                // Strict cleaning
                val = val.replace(/\s/g, ''); // No spaces
                val = val.replace(/[^a-zA-Z0-9@._-]/g, ''); // Allowed chars only
                val = val.replace(/\.\./g, '.'); // No double dots

                if (val !== originalVal) {
                    this.value = val;
                }
            });
        }

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

                // Check if empty
                if (val.length === 0) {
                    passwordInput.classList.remove('is-invalid');
                    passError.style.display = 'none';

                    // If password cleared, re-check confirm match (it might now fail or be cleared)
                    if (confirmInput.value.length > 0) {
                        validateMatch();
                    } else {
                        confirmInput.classList.remove('is-invalid');
                        confirmError.style.display = 'none';
                    }
                    return;
                }

                let errors = [];
                // Length 8-12
                if (val.length < 8 || val.length > 12) errors.push("8-12 characters");
                // Complexity
                if (!/[A-Z]/.test(val)) errors.push("uppercase letter");
                if (!/[a-z]/.test(val)) errors.push("lowercase letter");
                if (!/[0-9]/.test(val)) errors.push("number");
                if (!/[!@#$%^&*(),.?":{}|<>]/.test(val)) errors.push("special character");

                if (errors.length > 0) {
                    passwordInput.classList.add('is-invalid');
                    passError.textContent = 'Must include: ' + errors.join(', ');
                    passError.style.display = 'block';
                } else {
                    passwordInput.classList.remove('is-invalid');
                    passError.style.display = 'none';
                }

                if (confirmInput.value.length > 0) validateMatch();
            }

            function validateMatch() {
                let val = confirmInput.value;
                const originalVal = val;

                // Remove spaces
                val = val.replace(/\s/g, '');
                if (val !== originalVal) {
                    confirmInput.value = val;
                }

                // If password is empty (e.g. not changing it in edit mode)
                // then confirm should essentially be empty too, or we warn them "Password is empty".
                if (passwordInput.value.length === 0) {
                    if (val.length > 0) {
                        confirmInput.classList.add('is-invalid');
                        confirmError.textContent = 'Please enter a password first.';
                        confirmError.style.display = 'block';
                    } else {
                        confirmInput.classList.remove('is-invalid');
                        confirmError.style.display = 'none';
                    }
                    return;
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

        // Initialize Validations
        document.addEventListener('DOMContentLoaded', function () {
            // --- Create User Form Validations ---
            setupNameValidation('firstname');
            setupNameValidation('lastname');
            setupNumericValidation('purok');
            setupUsernameValidation('username');
            setupEmailValidation('email');
            setupPasswordValidation('password', 'confirm_password');

            // --- Edit User Form Validations ---
            setupNameValidation('edit_firstname');
            setupNameValidation('edit_lastname');
            setupNumericValidation('edit_purok');
            setupUsernameValidation('edit_username');
            setupEmailValidation('edit_email');
            setupPasswordValidation('edit_password', 'edit_confirm_password');
        });

        // Auto-open modal if there are errors
        <?php if ($show_create_modal): ?>
            document.addEventListener('DOMContentLoaded', function () {
                openCreateUserModal();
            });
        <?php endif; ?>

        <?php if ($show_edit_modal): ?>
            document.addEventListener('DOMContentLoaded', function () {
                openEditUserModal();
            });
        <?php endif; ?>

        // Auto-fill purok from tag click in create modal
        document.querySelectorAll('.purok-tag').forEach(tag => {
            tag.addEventListener('click', function () {
                const purokInput = document.getElementById('purok');
                if (purokInput && !purokInput.value) {
                    purokInput.value = this.getAttribute('data-purok');
                }
            });
        });

        // Handle window resize for responsive user cards
        function handleUserCardsResize() {
            const mobileView = document.getElementById('mobileUserView');
            const desktopView = document.getElementById('desktopUserView');

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
        handleUserCardsResize();
        window.addEventListener('resize', handleUserCardsResize);

        // Update existing edit and delete button event listeners to work with mobile cards
        document.addEventListener('click', function (e) {
            // Handle edit button clicks for mobile cards
            if (e.target.closest('.edit-user-btn')) {
                const button = e.target.closest('.edit-user-btn');
                const userData = {
                    userId: button.getAttribute('data-user-id'),
                    firstname: button.getAttribute('data-firstname'),
                    lastname: button.getAttribute('data-lastname'),
                    purok: button.getAttribute('data-purok'),
                    username: button.getAttribute('data-username'),
                    email: button.getAttribute('data-email'),
                    userType: button.getAttribute('data-user-type')
                };
                loadUserDataToEditModal(userData);
            }

            // Handle delete button clicks for mobile cards
            if (e.target.closest('.delete-user-btn')) {
                const button = e.target.closest('.delete-user-btn');
                const userData = {
                    userId: button.getAttribute('data-user-id'),
                    username: button.getAttribute('data-username'),
                    firstname: button.getAttribute('data-firstname'),
                    lastname: button.getAttribute('data-lastname'),
                    email: button.getAttribute('data-email'),
                    purok: button.getAttribute('data-purok'),
                    userType: button.getAttribute('data-user-type'),
                    createdAt: button.getAttribute('data-created-at')
                };
                loadUserDataToDeleteModal(userData);
            }
        });
    </script>
    <script src="../js/theme.js"></script>
</body>

</html>