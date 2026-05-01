<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireUser();

// Add maintenance mode check
checkMaintenanceMode();

$user_id = $_SESSION['user_id'];

// Get user data for sidebar
$user_sql = "SELECT *, first_name as firstname, surname as lastname, user_role as user_type FROM `profiling-system`.residents WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Fetch Active Surveys
$sql = "SELECT * FROM surveys 
        WHERE status = 'Active' 
        AND start_date <= CURDATE() 
        AND end_date >= CURDATE() 
        ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('surveys'); ?> - <?php echo __('user_dashboard'); ?></title>
    <link rel="icon" href="../img/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="../css/theme.css">
    <style>
        /* Reusing user theme styles */
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
            color: var(--text-light);
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

        /* Survey Card Styles */
        .survey-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            border: 1px solid #e5e7eb;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }

        .survey-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(31, 58, 147, 0.1);
            border-color: #1F3A93;
        }

        .survey-info h3 {
            color: #1a317d;
            margin-bottom: 10px;
            font-size: 20px;
            font-weight: 600;
        }

        .survey-info p {
            color: #64748b;
            font-size: 15px;
            margin-bottom: 15px;
            max-width: 700px;
            line-height: 1.6;
        }

        .survey-meta {
            font-size: 13px;
            color: #6b7280;
            display: flex;
            gap: 20px;
            background: #f9fafb;
            padding: 8px 15px;
            border-radius: 8px;
            display: inline-flex;
        }

        .survey-meta span {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .survey-meta i {
            color: #1F3A93;
        }

        .btn-start {
            background: linear-gradient(90deg, #1F3A93, #3a56b5);
            color: white;
            padding: 12px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            box-shadow: 0 5px 15px rgba(31, 58, 147, 0.2);
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .btn-start:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(31, 58, 147, 0.3);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
            border: 1px solid #e5e7eb;
        }

        .empty-state i {
            font-size: 64px;
            color: #bae6fd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #1a317d;
            margin-bottom: 10px;
            font-size: 20px;
        }

        .empty-state p {
            color: #9ca3af;
        }

        /* Mobile Styles */
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
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
        }

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

            .survey-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }

            .btn-start {
                width: 100%;
                justify-content: center;
            }

            .toggle-btn {
                display: flex !important;
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

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-success {
            background: linear-gradient(135deg, #1F3A93, #152c71);
            color: white;
            box-shadow: 0 5px 15px rgba(31, 58, 147, 0.3);
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #1a317d, #1e3a8a);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(31, 58, 147, 0.4);
        }

        .btn-secondary {
            background: #f9fafb;
            color: #4b5563;
            border: 2px solid #e5e7eb;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .btn-secondary:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            color: #374151;
        }

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

        @media (max-width: 768px) {
            .logout-modal-actions {
                flex-direction: column;
            }

            .logout-modal-actions .btn {
                min-width: 100%;
                width: 100%;
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

                <p class="logout-modal-message">
                    <?php echo __('logout_message'); ?>
                </p>

                <div class="logout-modal-actions">
                    <a href="logout.php" class="btn btn-success">
                        <i class="fas fa-sign-out-alt"></i> <?php echo __('yes_logout'); ?>
                    </a>
                    <button type="button" class="btn btn-secondary" onclick="closeLogoutModal()">
                        <i class="fas fa-times"></i> <?php echo __('cancel'); ?>
                    </button>
                </div>
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
                    <div class="user-role"><?php echo ucfirst($user['user_type']) . ' ' . __('account'); ?></div>
                </div>
            </div>
        </div>

        <!-- Menu Items -->
        <ul class="menu-items">
            <li class="menu-item">
                <a href="index.php" class="menu-link" data-tooltip="<?php echo __('dashboard'); ?>">
                    <i class="fas fa-tachometer-alt menu-icon"></i>
                    <span class="menu-text"><?php echo __('dashboard'); ?></span>
                </a>
            </li>
            <li class="menu-item">
                <a href="my_feedback.php" class="menu-link" data-tooltip="<?php echo __('my_feedback'); ?>">
                    <i class="fas fa-list-alt menu-icon"></i>
                    <span class="menu-text"><?php echo __('my_feedback'); ?></span>
                </a>
            </li>
            <li class="menu-item">
                <a href="surveys.php" class="menu-link active" data-tooltip="<?php echo __('surveys'); ?>">
                    <i class="fas fa-poll menu-icon"></i>
                    <span class="menu-text"><?php echo __('surveys'); ?></span>
                </a>
            </li>
            <li class="menu-item">
                <a href="profile.php" class="menu-link" data-tooltip="<?php echo __('my_profile'); ?>">
                    <i class="fas fa-user-circle menu-icon"></i>
                    <span class="menu-text"><?php echo __('my_profile'); ?></span>
                </a>
            </li>
            <li class="menu-item">
                <a href="settings.php" class="menu-link" data-tooltip="<?php echo __('settings'); ?>">
                    <i class="fas fa-cog menu-icon"></i>
                    <span class="menu-text"><?php echo __('settings'); ?></span>
                </a>
            </li>
            <li class="menu-item">
                <a href="help.php" class="menu-link" data-tooltip="<?php echo __('help_support'); ?>">
                    <i class="fas fa-question-circle menu-icon"></i>
                    <span class="menu-text"><?php echo __('help_support'); ?></span>
                </a>
            </li>
        </ul>

        <!-- Logout Section -->
        <div class="logout-section">
            <a href="#" class="logout-link" data-tooltip="<?php echo __('logout'); ?>"
                onclick="openLogoutModal(); return false;">
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
                <i class="fas fa-poll title-icon"></i>
                <?php echo __('available_surveys'); ?>
            </h1>
            <div class="date-display">
                <i class="far fa-calendar-alt"></i>
                <span><?php echo date('F j, Y'); ?></span>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert"
                style="background: #bae6fd; border: 1px solid #bae6fd; color: #1a317d; padding: 15px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-check-circle"></i>
                <?php echo $_SESSION['success_message'];
                unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert"
                style="background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 15px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Content -->
        <?php if ($result->num_rows > 0): ?>
            <div class="surveys-list">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="survey-card">
                        <div class="survey-info">
                            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                            <p><?php echo htmlspecialchars($row['description']); ?></p>
                            <div class="survey-meta">
                                <span><i class="far fa-calendar-alt"></i> <?php echo __('ends'); ?>:
                                    <strong><?php echo date('M d, Y', strtotime($row['end_date'])); ?></strong></span>
                            </div>
                        </div>
                        <?php
                        // Check if user already took this survey
                        $check_sql = "SELECT id FROM survey_responses WHERE user_id = ? AND survey_id = ? LIMIT 1";
                        $check_stmt = $conn->prepare($check_sql);
                        $check_stmt->bind_param("ii", $user_id, $row['id']);
                        $check_stmt->execute();
                        $check_result = $check_stmt->get_result();
                        $is_taken = $check_result->num_rows > 0;
                        ?>

                        <?php if ($is_taken): ?>
                            <button class="btn-start" style="background: #cbd5e1; cursor: default; box-shadow: none;">
                                <i class="fas fa-check"></i> <?php echo __('completed'); ?>
                            </button>
                        <?php else: ?>
                            <a href="take_survey.php?id=<?php echo $row['id']; ?>" class="btn-start">
                                <?php echo __('take_survey'); ?> <i class="fas fa-arrow-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3><?php echo __('no_active_surveys'); ?></h3>
                <p><?php echo __('check_back_later'); ?></p>
            </div>
        <?php endif; ?>
    </main>

    <script>
        // DOM Elements
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggleBtn');
        const mobileToggleBtn = document.getElementById('mobileToggleBtn');
        const mainContent = document.getElementById('mainContent');
        const menuLinks = document.querySelectorAll('.menu-link');
        const overlay = document.getElementById('overlay');
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
        }

        function closeLogoutModal() {
            logoutModal.style.display = 'none';
            document.body.style.overflow = '';
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

        // Close modal when clicking outside
        window.addEventListener('click', function (e) {
            if (e.target === logoutModal) {
                closeLogoutModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && logoutModal.style.display === 'flex') {
                closeLogoutModal();
            }
        });

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
    </script>
    <script src="../js/theme.js"></script>
</body>

</html>