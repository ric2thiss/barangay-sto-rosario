<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireUser();

// Add maintenance mode check
checkMaintenanceMode();


// Fetch user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT *, first_name as firstname, surname as lastname, user_role as user_type FROM `profiling-system`.residents WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header('Location: index.php');
    exit();
}

// Handle form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstname = trim($conn->real_escape_string($_POST['firstname']));
    $lastname = trim($conn->real_escape_string($_POST['lastname']));
    $purok = trim($conn->real_escape_string($_POST['purok'] ?? ''));
    $email = trim($conn->real_escape_string($_POST['email']));

    $errors = [];

    // Validation
    if (empty($firstname)) {
        $errors['firstname'] = 'First name is required';
    }

    if (empty($lastname)) {
        $errors['lastname'] = 'Last name is required';
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }

    // Check if email is already taken by another user
    if (!empty($email) && $email !== $user['email']) {
        $check_email_sql = "SELECT id FROM `profiling-system`.residents WHERE email = ? AND id != ?";
        $check_stmt = $conn->prepare($check_email_sql);
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $errors['email'] = 'Email already registered';
        }
        $check_stmt->close();
    }

    // Handle image upload
    $image_path = $user['image_path'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['profile_image']['type'];
        $file_size = $_FILES['profile_image']['size'];

        if (!in_array($file_type, $allowed_types)) {
            $errors['profile_image'] = 'Only JPG, PNG, GIF, and WebP images are allowed';
        } elseif ($file_size > 5 * 1024 * 1024) { // 5MB limit
            $errors['profile_image'] = 'Image size must be less than 5MB';
        } else {
            // Create uploads directory if it doesn't exist
            $upload_dir = '../uploads/profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Generate unique filename
            $file_ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $filename = 'user_' . $user_id . '_' . time() . '.' . $file_ext;
            $destination = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $destination)) {
                // Delete old image if exists
                if ($image_path && file_exists($image_path)) {
                    unlink($image_path);
                }
                $image_path = $destination;
            } else {
                $errors['profile_image'] = 'Failed to upload image';
            }
        }
    }

    // Handle password change
    $password_updated = false;
    if (!empty($_POST['new_password'])) {
        if (strlen($_POST['new_password']) < 8) {
            $errors['new_password'] = 'Password must be at least 8 characters long';
        } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
            $errors['confirm_password'] = 'Passwords do not match';
        } else {
            $hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $password_updated = true;
        }
    }

    if (empty($errors)) {
        try {
            // Start transaction
            $conn->begin_transaction();

            // Update user profile
            if ($password_updated) {
                $update_sql = "UPDATE `profiling-system`.residents SET first_name = ?, surname = ?, purok = ?, email = ?, image_path = ?, password = ? WHERE id = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("ssssssi", $firstname, $lastname, $purok, $email, $image_path, $hashed_password, $user_id);
            } else {
                $update_sql = "UPDATE `profiling-system`.residents SET first_name = ?, surname = ?, purok = ?, email = ?, image_path = ? WHERE id = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("sssssi", $firstname, $lastname, $purok, $email, $image_path, $user_id);
            }

            if ($stmt->execute()) {
                $conn->commit();

                // Update session with new name if changed
                if ($firstname . ' ' . $lastname !== $_SESSION['username']) {
                    $_SESSION['username'] = $firstname . ' ' . $lastname;
                }

                $message = 'Profile updated successfully!';
                $message_type = 'success';

                // Refresh user data
                $sql = "SELECT *, first_name as firstname, surname as lastname, user_role as user_type FROM `profiling-system`.residents WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
            } else {
                throw new Exception('Error updating profile: ' . $stmt->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $message = $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = 'Please fix the errors below';
        $message_type = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Feedback System</title>
    <link rel="icon" href="../img/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="../css/theme.css">
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
            background-color: var(--light-bg);
            transition: all 0.3s ease;
            color: var(--text-light);
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
            background: var(--light-bg);
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
            background: var(--card-light);
            padding: 20px 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            color: var(--text-light);
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
            background: var(--card-light);
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            margin-bottom: 25px;
            border: 1px solid var(--border-light);
            overflow: hidden;
            color: var(--text-light);
        }

        .settings-header {
            padding: 25px 30px;
            background: linear-gradient(135deg, #f0f7ff, #e0f2fe);
            border-bottom: 1px solid var(--border-light);
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

        /* Profile Image Styles */
        .profile-image-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-image-container {
            position: relative;
            display: inline-block;
            margin-bottom: 15px;
        }

        .profile-image {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #bae6fd;
            box-shadow: 0 10px 30px rgba(31, 58, 147, 0.1);
        }

        .profile-image-placeholder {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1F3A93, #152c71);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 60px;
            font-weight: 600;
            border: 5px solid #bae6fd;
            margin: 0 auto;
        }

        .image-upload-label {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: #1F3A93;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 3px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .image-upload-label:hover {
            background: #1a317d;
            transform: scale(1.1);
        }

        .image-upload-label i {
            font-size: 20px;
        }

        .image-upload-input {
            display: none;
        }

        .image-preview {
            display: none;
            margin-top: 10px;
        }

        .image-preview img {
            max-width: 200px;
            border-radius: 10px;
            margin-top: 10px;
            border: 2px solid #bae6fd;
        }

        /* Password Section */
        .password-section {
            background: #f9fafb;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
            border: 1px solid #e5e7eb;
        }

        .password-section h3 {
            color: #1a317d;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .password-section h3 i {
            color: #1F3A93;
        }

        /* Read-only fields */
        .readonly-field {
            background: #f9fafb;
            color: #6b7280;
            border: 1px solid #e5e7eb;
            padding: 14px 16px;
            border-radius: 10px;
            font-size: 15px;
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

        /* Error styling */
        .error {
            color: #ef4444;
            font-size: 13px;
            margin-top: 5px;
        }

        .is-invalid {
            border-color: #ef4444 !important;
        }

        .is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
        }

        /* Logout Modal Specific Styles */
        .logout-modal-icon {
            font-size: 64px;
            color: #1F3A93;
            background: #f0f7ff;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            border: 3px solid #bae6fd;
            box-shadow: 0 10px 30px rgba(31, 58, 147, 0.15);
        }

        .logout-modal-title {
            color: #1a317d;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 15px;
            text-align: center;
        }

        .logout-modal-message {
            color: #6b7280;
            text-align: center;
            line-height: 1.6;
            margin-bottom: 35px;
            font-size: 15px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        .logout-modal-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
            padding-top: 25px;
            border-top: 1px solid #e5e7eb;
        }

        .logout-modal-actions .btn {
            min-width: 160px;
            padding: 14px 25px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .logout-modal-actions .btn-success {
            background: linear-gradient(135deg, #1F3A93, #152c71);
            border: none;
            box-shadow: 0 5px 15px rgba(31, 58, 147, 0.3);
        }

        .logout-modal-actions .btn-success:hover {
            background: linear-gradient(135deg, #1a317d, #1e3a8a);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(31, 58, 147, 0.4);
        }

        .logout-modal-actions .btn-secondary {
            background: #f9fafb;
            color: #4b5563;
            border: 2px solid #e5e7eb;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .logout-modal-actions .btn-secondary:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            color: #374151;
        }

        /* Animation for logout modal */
        @keyframes pulseLogout {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        .logout-modal-icon i {
            animation: pulseLogout 2s infinite ease-in-out;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            padding: 25px 30px;
            background: #f0f7ff;
            border-bottom: 1px solid #bae6fd;
            border-radius: 15px 15px 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header h2 {
            color: #1a317d;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-header h2 i {
            color: #1F3A93;
        }

        .close-modal {
            background: #f0f7ff;
            border: 1px solid #bae6fd;
            color: #1F3A93;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s;
        }

        .close-modal:hover {
            background: #1F3A93;
            color: white;
        }

        .modal-body {
            padding: 30px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        /* Mobile responsive for logout modal */
        @media (max-width: 768px) {
            .logout-modal-actions {
                flex-direction: column;
            }

            .logout-modal-actions .btn {
                min-width: 100%;
                width: 100%;
            }

            .logout-modal-icon {
                width: 100px;
                height: 100px;
                font-size: 48px;
            }

            .logout-modal-title {
                font-size: 20px;
            }

            .logout-modal-message {
                font-size: 14px;
                padding: 0 15px;
            }

            /* Mobile Responsive */
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

            .profile-image {
                width: 150px;
                height: 150px;
            }

            .profile-image-placeholder {
                width: 150px;
                height: 150px;
            }
        }

        /* Dark Mode Overrides */
        body.dark-mode .settings-card {
            background: #1f2937;
            border-color: #374151;
            color: #e5e7eb;
        }

        body.dark-mode .settings-header {
            background: #111827;
            border-color: #374151;
        }

        body.dark-mode .settings-header h2 {
            color: #e5e7eb;
        }

        body.dark-mode .form-group label {
            color: #d1d5db;
        }

        body.dark-mode .form-group input[type="text"],
        body.dark-mode .form-group input[type="email"],
        body.dark-mode .form-group input[type="password"] {
            background: #374151;
            border-color: #4b5563;
            color: #ffffff;
        }

        body.dark-mode .form-group input:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.2);
        }

        body.dark-mode .readonly-field {
            background: #374151;
            border-color: #4b5563;
            color: #d1d5db;
        }

        body.dark-mode .setting-description {
            color: #9ca3af;
        }

        body.dark-mode .card-footer {
            background: #1f2937;
            border-color: #374151;
        }

        body.dark-mode .date-display {
            background: #1f2937;
            border-color: #374151;
            color: #e5e7eb;
        }

        body.dark-mode .profile-image-section .image-upload-label {
            border-color: #1f2937;
        }

        body.dark-mode .profile-image {
            border-color: #374151;
        }

        body.dark-mode .profile-image-placeholder {
            border-color: #374151;
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

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <!-- Logo Area -->
        <div class="logo-area">
            <div class="logo-wrapper">
                <div class="logo">
                    <div class="logo-icon">
                        <img src="../img/logo.png" alt="Logo">
                    </div>
                    <h1 class="logo-text"><?php echo __('user_panel'); ?></h1>
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
            <div class="user-info"
                data-tooltip="<?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>">
                <div class="user-avatar">
                    <?php if ($user['image_path']): ?>
                        <img src="<?php echo htmlspecialchars('../../profiling-system/officials/uploads/residents/' . basename($user['image_path'])); ?>"
                            alt="Profile" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <span><?php echo strtoupper(substr($user['firstname'], 0, 1) . substr($user['lastname'], 0, 1)); ?></span>
                    <?php endif; ?>
                    <div class="status-dot"></div>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
                    </div>
                    <div class="user-role"><?php echo ucfirst($user['user_type']); ?> Account</div>
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
                    <span class="menu-text"><?php echo __('dashboard'); ?></span>
                </a>
            </li>
            <li class="menu-item">
                <a href="my_feedback.php"
                    class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'my_feedback.php' ? 'active' : ''; ?>"
                    data-tooltip="My Feedback">
                    <i class="fas fa-list-alt menu-icon"></i>
                    <span class="menu-text"><?php echo __('my_feedback'); ?></span>
                </a>
            </li>
            <li class="menu-item">
                <a href="surveys.php"
                    class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'surveys.php' ? '' : ''; ?>"
                    data-tooltip="Surveys">
                    <i class="fas fa-poll menu-icon"></i>
                    <span class="menu-text">Surveys</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="profile.php"
                    class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>"
                    data-tooltip="My Profile">
                    <i class="fas fa-user-circle menu-icon"></i>
                    <span class="menu-text"><?php echo __('my_profile'); ?></span>
                </a>
            </li>
            <li class="menu-item">
                <a href="settings.php"
                    class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>"
                    data-tooltip="Settings">
                    <i class="fas fa-cog menu-icon"></i>
                    <span class="menu-text"><?php echo __('settings'); ?></span>
                </a>
            </li>
            <li class="menu-item">
                <a href="help.php"
                    class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'help.php' ? 'active' : ''; ?>"
                    data-tooltip="Help & Support">
                    <i class="fas fa-question-circle menu-icon"></i>
                    <span class="menu-text"><?php echo __('help_support'); ?></span>
                </a>
            </li>
        </ul>

        <!-- Logout Section -->
        <div class="logout-section">
            <a href="#" class="logout-link" data-tooltip="Logout" onclick="openLogoutModal(); return false;">
                <i class="fas fa-sign-out-alt menu-icon"></i>
                <span class="logout-text"><?php echo __('logout'); ?></span>
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <!-- Header -->
        <div class="header">
            <h1 class="page-title">
                <i class="fas fa-user-circle title-icon"></i>
                <?php echo __('my_profile'); ?>
            </h1>
            <div class="date-display">
                <i class="far fa-calendar-alt"></i>
                <span id="currentDate"><?php echo date('F j, Y'); ?></span>
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
            <!-- Profile Settings -->
            <div class="settings-card">
                <div class="settings-header">
                    <h2><i class="fas fa-user-edit"></i> <?php echo __('edit_profile'); ?></h2>
                </div>
                <div class="settings-body">
                    <form method="POST" action="" enctype="multipart/form-data" id="profileForm">
                        <!-- Profile Image Section -->
                        <div class="profile-image-section">
                            <div class="profile-image-container">
                                <?php if ($user['image_path']): ?>
                                    <img src="<?php echo htmlspecialchars('../../profiling-system/officials/uploads/residents/' . basename($user['image_path'])); ?>"
                                        alt="Profile Picture" class="profile-image" id="profileImage">
                                <?php else: ?>
                                    <div class="profile-image-placeholder" id="profilePlaceholder">
                                        <?php echo strtoupper(substr($user['firstname'], 0, 1) . substr($user['lastname'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>

                                <label class="image-upload-label" for="profile_image"
                                    title="<?php echo __('upload_new'); ?>">
                                    <i class="fas fa-camera"></i>
                                    <input type="file" class="image-upload-input" id="profile_image"
                                        name="profile_image" accept="image/*">
                                </label>
                            </div>

                            <div class="image-preview" id="imagePreview">
                                <img id="previewImage" alt="Preview">
                            </div>

                            <?php if (isset($errors['profile_image'])): ?>
                                <div class="error"><?php echo $errors['profile_image']; ?></div>
                            <?php endif; ?>

                            <p class="setting-description">
                                <?php echo __('click_to_upload'); ?> <?php echo __('max_size'); ?><br>
                                <?php echo __('supported_formats'); ?>
                            </p>
                        </div>

                        <!-- Personal Information -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstname"><?php echo __('first_name'); ?></label>
                                <input type="text" id="firstname" name="firstname"
                                    value="<?php echo htmlspecialchars($user['firstname']); ?>"
                                    class="<?php echo isset($errors['firstname']) ? 'is-invalid' : ''; ?>" required>
                                <?php if (isset($errors['firstname'])): ?>
                                    <div class="error"><?php echo $errors['firstname']; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="lastname"><?php echo __('last_name'); ?></label>
                                <input type="text" id="lastname" name="lastname"
                                    value="<?php echo htmlspecialchars($user['lastname']); ?>"
                                    class="<?php echo isset($errors['lastname']) ? 'is-invalid' : ''; ?>" required>
                                <?php if (isset($errors['lastname'])): ?>
                                    <div class="error"><?php echo $errors['lastname']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="purok"><?php echo __('purok'); ?></label>
                                <input type="text" id="purok" name="purok"
                                    value="<?php echo htmlspecialchars($user['purok'] ?? ''); ?>"
                                    placeholder="<?php echo __('enter_purok'); ?>">
                            </div>

                            <div class="form-group">
                                <label for="email"><?php echo __('email_address'); ?></label>
                                <input type="email" id="email" name="email"
                                    value="<?php echo htmlspecialchars($user['email']); ?>"
                                    class="<?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="error"><?php echo $errors['email']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Account Information (Read-only) -->
                        <div class="form-row">
                            <div class="form-group">
                                <label><?php echo __('username'); ?></label>
                                <div class="readonly-field">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </div>
                                <p class="setting-description"><?php echo __('username_no_change'); ?></p>
                            </div>

                            <div class="form-group">
                                <label><?php echo __('account_type'); ?></label>
                                <div class="readonly-field">
                                    <?php echo ucfirst($user['user_type']); ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><?php echo __('account_created'); ?></label>
                            <div class="readonly-field">
                                <?php echo date('F j, Y g:i A', strtotime($user['created_at'])); ?>
                            </div>
                        </div>

                        <!-- Password Change Section -->
                        <div class="password-section">
                            <h3><i class="fas fa-key"></i> <?php echo __('change_password'); ?></h3>
                            <p class="setting-description"><?php echo __('leave_blank_password'); ?></p>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="new_password"><?php echo __('new_password'); ?></label>
                                    <input type="password" id="new_password" name="new_password"
                                        class="<?php echo isset($errors['new_password']) ? 'is-invalid' : ''; ?>"
                                        placeholder="<?php echo __('leave_blank_password'); ?>">
                                    <?php if (isset($errors['new_password'])): ?>
                                        <div class="error"><?php echo $errors['new_password']; ?></div>
                                    <?php endif; ?>
                                    <p class="setting-description"><?php echo __('min_password_length'); ?></p>
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password"><?php echo __('confirm_password'); ?></label>
                                    <input type="password" id="confirm_password" name="confirm_password"
                                        class="<?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>"
                                        placeholder="<?php echo __('confirm_password'); ?>">
                                    <?php if (isset($errors['confirm_password'])): ?>
                                        <div class="error"><?php echo $errors['confirm_password']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                <i class="fas fa-undo"></i> <?php echo __('reset'); ?>
                            </button>
                            <button type="submit" name="update_profile" class="btn btn-success">
                                <i class="fas fa-save"></i> <?php echo __('save_changes'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Logout Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-sign-out-alt"></i> <?php echo __('confirm_logout'); ?></h2>
                <button class="close-modal" onclick="closeLogoutModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="logout-modal-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>

                <h3 class="logout-modal-title"><?php echo __('ready_to_leave'); ?></h3>

                <?php echo __('logout_message'); ?>

                <div class="logout-modal-actions">
                    <a href="logout.php" class="btn btn-success">
                        <i class="fas fa-sign-out-alt"></i> <?php echo __('yes_logout'); ?>
                    </a>
                    <button type="button" class="btn btn-secondary" onclick="closeLogoutModal()">
                        <i class="fas fa-times"></i> <?php echo __('cancel'); ?>
                    </button>
                </div>

                <div style="text-align: center; margin-top: 25px;">
                    <p style="color: #9ca3af; font-size: 12px;">
                        <i class="fas fa-info-circle"></i> <?php echo __('logout_info'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // DOM Elements
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggleBtn');
        const mobileToggleBtn = document.getElementById('mobileToggleBtn');
        const mainContent = document.getElementById('mainContent');
        const menuLinks = document.querySelectorAll('.menu-link');
        const currentDateElement = document.getElementById('currentDate');
        const overlay = document.getElementById('overlay');
        const profileImageInput = document.getElementById('profile_image');
        const imagePreview = document.getElementById('imagePreview');
        const previewImage = document.getElementById('previewImage');
        const profileImage = document.getElementById('profileImage');
        const profilePlaceholder = document.getElementById('profilePlaceholder');
        const profileForm = document.getElementById('profileForm');
        const logoutModal = document.getElementById('logoutModal');

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

        // Logout Modal Functions
        function openLogoutModal() {
            logoutModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            // Force reflow to trigger animation
            setTimeout(() => {
                logoutModal.classList.add('active');
            }, 10);
        }

        function closeLogoutModal() {
            logoutModal.classList.remove('active');
            setTimeout(() => {
                logoutModal.style.display = 'none';
                document.body.style.overflow = '';
            }, 300);
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

        // Image Preview Functionality
        profileImageInput.addEventListener('change', function (e) {
            const file = e.target.files[0];

            if (file) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    previewImage.src = e.target.result;
                    imagePreview.style.display = 'block';

                    // Update main profile image preview
                    if (profileImage) {
                        profileImage.src = e.target.result;
                    }

                    if (profilePlaceholder) {
                        profilePlaceholder.style.display = 'none';
                    }
                };

                reader.readAsDataURL(file);
            }
        });

        // Reset Form
        function resetForm() {
            if (confirm('Are you sure you want to reset all changes?')) {
                profileForm.reset();
                imagePreview.style.display = 'none';

                // Restore original profile image
                if (profileImage && '<?php echo $user['image_path']; ?>') {
                    profileImage.src = '<?php echo htmlspecialchars($user['image_path']); ?>';
                    if (profilePlaceholder) profilePlaceholder.style.display = 'none';
                } else if (profilePlaceholder) {
                    profilePlaceholder.style.display = 'flex';
                    if (profileImage) profileImage.style.display = 'none';
                }
            }
        }

        // Form Validation
        profileForm.addEventListener('submit', function (e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword && newPassword.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return false;
            }

            if (newPassword && newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }

            return true;
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

        // Close modals when clicking outside
        window.addEventListener('click', function (event) {
            if (event.target == logoutModal && logoutModal.classList.contains('active')) {
                closeLogoutModal();
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                if (logoutModal.classList.contains('active')) {
                    closeLogoutModal();
                }
            }
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

        // Image file size validation
        profileImageInput.addEventListener('change', function () {
            const file = this.files[0];
            const maxSize = 5 * 1024 * 1024; // 5MB

            if (file && file.size > maxSize) {
                alert('Image size must be less than 5MB');
                this.value = '';
                imagePreview.style.display = 'none';

                // Restore original image
                if (profileImage && '<?php echo $user['image_path']; ?>') {
                    profileImage.src = '<?php echo htmlspecialchars($user['image_path']); ?>';
                    if (profilePlaceholder) profilePlaceholder.style.display = 'none';
                } else if (profilePlaceholder) {
                    profilePlaceholder.style.display = 'flex';
                    if (profileImage) profileImage.style.display = 'none';
                }
            }
        });

        // Show confirmation before leaving page if form has changes
        let formChanged = false;
        const formInputs = profileForm.querySelectorAll('input, textarea, select');

        formInputs.forEach(input => {
            input.addEventListener('input', () => {
                formChanged = true;
            });

            input.addEventListener('change', () => {
                formChanged = true;
            });
        });

        profileForm.addEventListener('submit', () => {
            formChanged = false;
        });

        // Prevent beforeunload when clicking logout link
        document.querySelector('.logout-link').addEventListener('click', function (e) {
            if (formChanged) {
                formChanged = false;
            }
        });

        // Also prevent when clicking logout button in modal
        document.querySelector('#logoutModal .btn-success').addEventListener('click', function () {
            if (formChanged) {
                formChanged = false;
            }
        });

        window.addEventListener('beforeunload', (e) => {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        // Name validation and capitalization
        function setupNameValidation(inputId) {
            const input = document.getElementById(inputId);
            if (!input) return;

            // Create error container if it doesn't exist
            let errorDiv = input.parentNode.querySelector('.invalid-feedback-client');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'error invalid-feedback-client'; // Using .error class as seen in this file
                errorDiv.style.display = 'none';
                // Styles are already handled by .error class mostly, but ensuring visibility toggle
                input.parentNode.appendChild(errorDiv);
            }

            input.addEventListener('input', function () {
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
                errorDiv.className = 'error invalid-feedback-client-numeric'; // Using .error class for consistency in profile.php
                errorDiv.style.display = 'none';
                input.parentNode.appendChild(errorDiv);
            }

            input.addEventListener('input', function () {
                let val = this.value;

                // Allow only digits
                const numericVal = val.replace(/[^0-9]/g, '');

                if (val !== numericVal) {
                    this.value = numericVal;
                    this.classList.add('is-invalid');
                    errorDiv.textContent = 'Only numbers are allowed.';
                    errorDiv.style.display = 'block';
                } else {
                    if (val.length > 0) {
                        this.classList.remove('is-invalid');
                        errorDiv.style.display = 'none';
                    }
                }
            });
        }

        setupNumericValidation('purok');

        // Email validation
        function setupEmailValidation(inputId) {
            const input = document.getElementById(inputId);
            if (!input) return;

            // Create error container if it doesn't exist
            let errorDiv = input.parentNode.querySelector('.invalid-feedback-client-email');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'error invalid-feedback-client-email';
                errorDiv.style.display = 'none';
                input.parentNode.appendChild(errorDiv);
            }

            input.addEventListener('input', function () {
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
                    div.className = 'error ' + className; // Using .error class for consistency
                    div.style.display = 'none';
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

                // For profile change password, empty means don't change
                if (val.length === 0) {
                    passwordInput.classList.remove('is-invalid');
                    passError.style.display = 'none';

                    // Reset match validation on confirm if password cleared
                    // If confirm has value, it's now mismatch vs empty? Or valid?
                    // Usually if pass is empty, we act as if not changing.
                    if (confirmInput.value.length > 0) {
                        // If they typed something in confirm but cleared password, mismatch
                        // But practically, user might be clearing both.
                        validateMatch();
                    } else {
                        confirmInput.classList.remove('is-invalid');
                        confirmError.style.display = 'none';
                    }
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

                if (confirmInput.value.length > 0) validateMatch();
            }

            function validateMatch() {
                let val = confirmInput.value;
                const originalVal = val;

                // Remove spaces from confirm too
                val = val.replace(/\s/g, '');
                if (val !== originalVal) {
                    confirmInput.value = val;
                }

                // If password input is empty, and proper rules for profile: 
                // changing password is optional.
                // If password is empty, confirm shouldn't be matched against strict rules, 
                // but usually confirm should also be empty.
                if (passwordInput.value.length === 0) {
                    if (val.length > 0) {
                        // They typed in confirm but pass is empty. Technically mismatch or invalid state.
                        confirmInput.classList.add('is-invalid');
                        confirmError.textContent = 'Please enter a new password first.';
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

        setupPasswordValidation('new_password', 'confirm_password');
    </script>
    <script src="../js/theme.js"></script>
</body>

</html>