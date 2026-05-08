<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireUser();

// Add maintenance mode check
checkMaintenanceMode();


// Add maintenance mode check


// Fetch user data from database
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT id, first_name as firstname, surname as lastname, user_role as user_type, NULL as image_path, email, username, password, purok FROM `profiling-system`.residents WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Resident Feedback and Survey System</title>
    <link rel="icon" href="../img/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="../css/theme.css?v=<?php echo time(); ?>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
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
            z-index: 1000;
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
            z-index: 1010;
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

        /* Force Blue Sidebar in Dark Mode to match other pages */
        body.dark-mode .sidebar {
            background: #1F3A93 !important;
            border-right: none !important;
        }

        body.dark-mode .logo-area {
            background: #1F3A93 !important;
            border-bottom-color: rgba(255, 255, 255, 0.1) !important;
        }

        body.dark-mode .logo-text {
            color: #ffffff !important;
        }

        body.dark-mode .toggle-btn {
            background: rgba(255, 255, 255, 0.1) !important;
            border-color: rgba(255, 255, 255, 0.2) !important;
            color: #ffffff !important;
        }

        body.dark-mode .toggle-btn:hover {
            background: #ffffff !important;
            color: #1F3A93 !important;
        }

        body.dark-mode .user-info {
            background: rgba(255, 255, 255, 0.05) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        body.dark-mode .user-name {
            color: #ffffff !important;
        }

        body.dark-mode .user-role {
            background: rgba(255, 255, 255, 0.1) !important;
            color: rgba(255, 255, 255, 0.8) !important;
            border-color: rgba(255, 255, 255, 0.2) !important;
        }

        body.dark-mode .menu-link {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        body.dark-mode .menu-link:hover {
            background: rgba(255, 255, 255, 0.1) !important;
            color: #ffffff !important;
        }

        body.dark-mode .menu-link.active {
            background: #ffffff !important;
            color: #1F3A93 !important;
        }

        body.dark-mode .logout-section {
            background: #1F3A93 !important;
            border-top-color: rgba(255, 255, 255, 0.1) !important;
        }

        body.dark-mode .logout-link {
            background: rgba(255, 255, 255, 0.05) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
            color: rgba(255, 255, 255, 0.9) !important;
        }

        body.dark-mode .logout-link:hover {
            background: #ef4444 !important;
            color: #ffffff !important;
            border-color: #ef4444 !important;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px;
            transition: all 0.3s;
            overflow-y: auto;
            min-height: 100vh;
            margin-left: 280px;
        }

        .sidebar.closed~.main-content {
            margin-left: 80px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
        }

        .title-icon {
            color: var(--primary-color);
            margin-right: 10px;
        }

        .date-display {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            padding: 10px 15px;
            border-radius: 10px;
            border: 1px solid var(--border-light);
        }

        /* Settings Cards */
        .settings-card {
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            margin-bottom: 30px;
            border: 1px solid var(--border-light);
        }

        .settings-card h2 {
            margin-bottom: 25px;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f7ff;
        }

        body.dark-mode .settings-card h2 {
            border-bottom-color: #374151;
        }

        .settings-card h2 i {
            color: var(--primary-color);
        }

        /* Settings Sections */
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
        }

        .section-title i {
            color: var(--primary-color);
            width: 20px;
        }

        .setting-group {
            background: #f9fafb;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            margin-bottom: 20px;
        }

        body.dark-mode .setting-group {
            background: #374151;
            border-color: #4b5563;
        }

        .setting-description {
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        /* Form Controls */
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
        }

        .radio-group label:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .radio-group input:checked+label {
            box-shadow: 0 5px 15px rgba(31, 58, 147, 0.2);
        }

        .radio-group label i {
            font-size: 18px;
        }

        .select-wrapper {
            position: relative;
            max-width: 300px;
        }

        .select-wrapper::after {
            content: '\f078';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            pointer-events: none;
        }

        .select-wrapper select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            appearance: none;
            padding-right: 40px;
        }

        body.dark-mode .select-wrapper select {
            border-color: #4b5563;
        }

        .select-wrapper select:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(31, 58, 147, 0.1);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 10px;
            transition: all 0.3s;
            font-weight: 500;
            border: none;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
        }

        .btn-success {
            background: linear-gradient(90deg, var(--primary-color), #3a56b5);
            color: white;
        }

        .btn-success:hover {
            background: linear-gradient(90deg, #1a317d, #1F3A93);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(31, 58, 147, 0.2);
        }

        .btn-danger {
            background: linear-gradient(90deg, var(--danger-color), #f87171);
            color: white;
        }

        .btn-danger:hover {
            background: linear-gradient(90deg, var(--danger-dark), var(--danger-color));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.2);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }

        /* Alert */
        .alert {
            padding: 15px 20px;
            background: #bae6fd;
            border: 1px solid #bae6fd;
            border-radius: 10px;
            color: #1a317d;
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        body.dark-mode .alert {
            background: #1a317d;
            border-color: #1a317d;
            color: #bae6fd;
        }

        .alert-error {
            background: #fee2e2;
            border-color: #fecaca;
            color: #991b1b;
        }

        body.dark-mode .alert-error {
            background: #7f1d1d;
            border-color: #991b1b;
            color: #fecaca;
        }

        .alert i {
            color: var(--primary-color);
        }

        .alert-error i {
            color: var(--danger-color);
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

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--card-light);
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        }

        body.dark-mode .modal-content {
            background: var(--card-dark);
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

        body.dark-mode .modal-header {
            background: #1f2937;
            border-bottom-color: #374151;
        }

        .modal-header h2 {
            color: var(--primary-color);
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-header h2 i {
            color: var(--primary-color);
        }

        .close-modal {
            background: #f0f7ff;
            border: 1px solid #bae6fd;
            color: var(--primary-color);
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

        body.dark-mode .close-modal {
            background: #1f2937;
            border-color: #374151;
        }

        .close-modal:hover {
            background: var(--primary-color);
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

        body.dark-mode #themeToggleBtn {
            border-color: #4b5563;
            color: #d1d5db;
        }

        #themeToggleBtn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
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
            border-color: var(--primary-color);
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
            background: var(--primary-color);
            border-radius: 5px;
        }

        .theme-label {
            text-align: center;
            margin-top: 5px;
            font-size: 14px;
            font-weight: 500;
        }

        /* Mobile Toggle Button */
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 99;
            background: var(--primary-color);
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

            .theme-toggle-container {
                margin-left: 0;
                width: 100%;
                justify-content: flex-end;
            }

            .radio-group {
                flex-direction: column;
            }

            .radio-group label {
                width: 100%;
                justify-content: center;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions .btn {
                width: 100%;
            }

            .toggle-btn {
                display: flex !important;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
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
                        <img src="<?php echo htmlspecialchars($user['image_path']); ?>" alt="Profile"
                            style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
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
                    class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'my_feedback.php' ? '' : ''; ?>"
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
                <a href="settings.php"
                    class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>"
                    data-tooltip="Settings">
                    <i class="fas fa-cog menu-icon"></i>
                    <span class="menu-text"><?php echo __('settings'); ?></span>
                </a>
            </li>
            <li class="menu-item">
                <a href="help.php"
                    class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'help.php' ? '' : ''; ?>"
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
            <div style="display: flex; align-items: center; gap: 15px;">
                <h1 class="page-title">
                    <i class="fas fa-cog title-icon"></i>
                    <?php echo __('settings'); ?>
                </h1>
                <div class="theme-toggle-container">
                    <button id="themeToggleBtn" class="btn">
                        <i class="fas fa-moon"></i>
                        <span>Dark Mode</span>
                    </button>
                </div>
            </div>
            <div class="date-display">
                <i class="far fa-calendar-alt"></i>
                <span id="currentDate"><?php echo date('F j, Y'); ?></span>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert">
                <i class="fas fa-check-circle"></i>
                <?php echo $_SESSION['success_message'];
                unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Settings Card -->
        <div class="settings-card">
            <h2><i class="fas fa-sliders-h"></i> Appearance Settings</h2>

            <!-- Theme Section -->
            <div class="settings-section">
                <h3 class="section-title">
                    <i class="fas fa-palette"></i>
                    <?php echo __('theme_settings'); ?>
                </h3>
                <div class="setting-group">
                    <p class="setting-description">
                        <?php echo __('theme_description'); ?>
                    </p>

                    <div class="quick-settings">
                        <div>
                            <div class="theme-preview light active" data-theme="light">
                                <div class="theme-label"><?php echo __('light_mode'); ?></div>
                            </div>
                        </div>
                        <div>
                            <div class="theme-preview dark" data-theme="dark">
                                <div class="theme-label"><?php echo __('dark_mode'); ?></div>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 20px;">
                        <div class="radio-group" id="themeSelector">
                            <input type="radio" id="theme-light" name="theme" value="light" checked>
                            <label for="theme-light">
                                <i class="fas fa-sun"></i>
                                <?php echo __('light_mode'); ?>
                            </label>
                            <input type="radio" id="theme-dark" name="theme" value="dark">
                            <label for="theme-dark">
                                <i class="fas fa-moon"></i>
                                <?php echo __('dark_mode'); ?>
                            </label>
                            <input type="radio" id="theme-auto" name="theme" value="auto">
                            <label for="theme-auto">
                                <i class="fas fa-adjust"></i>
                                <?php echo __('auto_mode'); ?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Language Section -->
            <div class="settings-section">
                <h3 class="section-title">
                    <i class="fas fa-language"></i>
                    <?php echo __('language_title'); ?>
                </h3>
                <div class="setting-group">
                    <p class="setting-description">
                        <?php echo __('language_description'); ?>
                    </p>

                    <div class="select-wrapper">
                        <select name="language" id="language">
                            <option value="en" <?php echo (isset($_COOKIE['app_lang']) && $_COOKIE['app_lang'] == 'en') ? 'selected' : ''; ?>>English</option>
                            <option value="bisaya" <?php echo (isset($_COOKIE['app_lang']) && $_COOKIE['app_lang'] == 'bisaya') ? 'selected' : ''; ?>>Bisaya</option>
                        </select>
                    </div>
                </div>
            </div>


        </div>
    </main>

    <!-- Confirmation Modals -->


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

    <script src="../js/theme.js"></script>
    <script>
        // DOM Elements
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggleBtn');
        const mobileToggleBtn = document.getElementById('mobileToggleBtn');
        const mainContent = document.getElementById('mainContent');
        const overlay = document.getElementById('overlay');
        const currentDateEl = document.getElementById('currentDate');

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
            } else {
                // On mobile, the X button should close the sidebar
                closeMobileSidebar();
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

        // Modal functions
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }



        // Set current date
        const now = new Date();
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };
        currentDateEl.textContent = now.toLocaleDateString('en-US', options);

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

        // Close modals when clicking outside
        window.addEventListener('click', function (e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(modal => {
                    modal.classList.remove('active');
                });
                document.body.style.overflow = '';
            }
        });

        // Load saved sidebar state
        function loadSidebarState() {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (window.innerWidth > 768 && isCollapsed) {
                sidebar.classList.add('closed');
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
                toggleBtn.style.display = 'flex';
                sidebar.style.left = '0';
                loadSidebarState();
            } else {
                // Mobile view
                mobileToggleBtn.style.display = 'flex';
                sidebar.classList.remove('closed');
                toggleBtn.style.display = 'flex';
            }
        }

        // Initialize on load
        loadSidebarState();
        handleResize();
        window.addEventListener('resize', handleResize);

        // Add tooltips for collapsed sidebar items
        function setupTooltips() {
            if (sidebar.classList.contains('closed')) {
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
        }

        setupTooltips();

        // Language Selector
        const languageSelect = document.getElementById('language');
        if (languageSelect) {
            languageSelect.addEventListener('change', function () {
                const lang = this.value;
                document.cookie = "app_lang=" + lang + "; path=/; max-age=" + (86400 * 30); // 30 days
                location.reload();
            });
        }
    </script>
</body>

</html>