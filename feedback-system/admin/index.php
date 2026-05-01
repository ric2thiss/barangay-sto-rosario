<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireAdmin();

// Get statistics
$stats_sql = "SELECT 
                COUNT(*) as total_feedback,
                COUNT(DISTINCT user_id) as total_users,
                AVG(rating) as avg_rating,
                SUM(CASE WHEN sentiment = 'Positive' THEN 1 ELSE 0 END) as positive_count,
                SUM(CASE WHEN sentiment = 'Negative' THEN 1 ELSE 0 END) as negative_count,
                SUM(CASE WHEN sentiment = 'Neutral' THEN 1 ELSE 0 END) as neutral_count,
                COUNT(DISTINCT category_id) as categories_used
              FROM feedback";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Get recent feedback count (last 7 days)
$recent_sql = "SELECT COUNT(*) as recent_count 
              FROM feedback 
              WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$recent_result = $conn->query($recent_sql);

$recent = $recent_result->fetch_assoc();

// Get Survey Statistics
$survey_stats_sql = "SELECT 
                        (SELECT COUNT(*) FROM surveys WHERE status = 'Active') as active_surveys,
                        (SELECT COUNT(*) FROM survey_responses) as total_responses";
$survey_stats = $conn->query($survey_stats_sql)->fetch_assoc();

// Get recent feedback for table
$recent_feedback_sql = "SELECT f.*, u.username, c.name as category_name 
                      FROM feedback f 
                      JOIN `profiling-system`.residents u ON f.user_id = u.id 
                      JOIN categories c ON f.category_id = c.id 
                      ORDER BY f.created_at DESC 
                      LIMIT 5";
$recent_feedback_result = $conn->query($recent_feedback_sql);

// Get feedback for report generation (Limit 50 for performance)
$report_sql = "SELECT f.*, u.username, c.name as category_name 
               FROM feedback f 
               JOIN `profiling-system`.residents u ON f.user_id = u.id 
               JOIN categories c ON f.category_id = c.id 
               ORDER BY f.created_at DESC 
               LIMIT 50";
$report_result = $conn->query($report_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Resident Feedback and Survey System</title>
    <link rel="icon" href="../img/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
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
            margin-left: 280px; /* Updated from 260px */
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

        /* Dashboard Styles */
        .dashboard-header {
            background: linear-gradient(135deg, #2c3e50 0%, #4a235a 100%);
            color: white;
            padding: 30px;
            margin-bottom: 30px;
            border-radius: 10px;
        }
        
        .dashboard-header h1 {
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .dashboard-header p {
            margin: 5px 0;
            opacity: 0.9;
        }
        
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .stat-card h3 {
            margin-bottom: 10px;
            font-size: 1rem;
            color: #555;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .dashboard-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
            margin-bottom: 25px;
            border: 1px solid #e5e7eb;
        }
        
        .dashboard-card h2 {
            color: #1a317d;
            margin-bottom: 15px;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .dashboard-card h2 i {
            color: #1F3A93;
        }
        
        .dashboard-card p {
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .admin-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }
        
        .action-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
            border: 1px solid #e5e7eb;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(31, 58, 147, 0.1);
            border-color: #1F3A93;
        }
        
        .action-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .sentiment-chart {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            height: 200px;
            margin: 30px 0 60px 0; /* Added bottom margin for labels */
        }
        
        .sentiment-bar {
            flex: 1;
            background: #1a317d;
            border-radius: 5px 5px 0 0;
            position: relative;
            min-height: 20px;
        }
        
        .sentiment-bar.negative {
            background: #dc3545;
        }
        
        .sentiment-bar.neutral {
            background: #ffc107;
        }
        
        .sentiment-label {
            position: absolute;
            bottom: -50px; /* Pushed outside */
            left: 0;
            right: 0;
            text-align: center;
            font-weight: 600;
            color: #333;
            font-size: 13px;
            line-height: 1.4;
        }

        .sentiment-summary {
            text-align: center;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            color: #64748b;
            font-size: 14px;
        }

        /* Dark Mode Overrides for Sentiment */
        body.dark-mode .sentiment-label {
            color: #d1d5db !important;
        }

        body.dark-mode .sentiment-summary {
            color: #9ca3af !important;
            border-top-color: #4b5563 !important;
        }
        
        body.dark-mode .sentiment-summary strong {
            color: #e5e7eb !important;
        }

        /* Stats Cards */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }

        .stat-card-new {
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

        .stat-card-new:hover {
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

        .green-badge {
            background: #1F3A93;
            color: white;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
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
                left: -280px; /* Updated from -260px */
                width: 280px; /* Updated from 260px */
                transition: left 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
                box-shadow: 5px 0 30px rgba(0, 0, 0, 0.1);
            }

            .sidebar.open {
                left: 0;
            }

            .sidebar.closed {
                left: -280px; /* Updated from -260px */
                width: 280px; /* Updated from 260px */
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

            .dashboard-stats {
                grid-template-columns: 1fr 1fr;
            }
            
            .admin-actions {
                grid-template-columns: 1fr;
            }
            
            .stats {
                grid-template-columns: 1fr;
            }
            
            .toggle-btn {
                display: flex !important;
            }
            
            /* Adjust logo text for mobile */
            .logo-text {
                font-size: 18px;
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

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
        }
        
        table th, table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        table th {
            background-color: #f0f7ff;
            font-weight: 600;
            color: #1a317d;
        }
        
        table tr:hover {
            background-color: #f9fafb;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(90deg, #1F3A93, #3a56b5);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            font-weight: 500;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background: linear-gradient(90deg, #1a317d, #1F3A93);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(31, 58, 147, 0.2);
        }
        
        .alert {
            padding: 15px 20px;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            color: #856404;
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert i {
            color: #856404;
        }

        /* Footer */
        .footer {
            margin-top: 50px;
            padding: 20px 0;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
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

        /* Responsive Recent Feedback Styles */
.recent-feedback-mobile {
    display: none;
}

.feedback-card-mobile {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
    transition: all 0.3s;
}

.feedback-card-mobile:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    border-color: #1F3A93;
}

.feedback-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.feedback-user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.feedback-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1F3A93, #152c71);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
    flex-shrink: 0;
}

.feedback-username {
    font-weight: 600;
    color: #1a317d;
    font-size: 15px;
}

.feedback-category {
    font-size: 13px;
    color: #6b7280;
    background: #f3f4f6;
    padding: 3px 8px;
    border-radius: 20px;
    display: inline-block;
    margin-top: 3px;
}

.feedback-sentiment {
    font-size: 12px;
}

.feedback-rating {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f3f4f6;
}

.feedback-date {
    font-size: 12px;
    color: #6b7280;
    background: #f9fafb;
    padding: 4px 10px;
    border-radius: 20px;
    border: 1px solid #e5e7eb;
}

.feedback-comment {
    color: #4b5563;
    line-height: 1.5;
    font-style: italic;
    padding: 10px;
    background: #f9fafb;
    border-radius: 8px;
    border-left: 3px solid #1F3A93;
}

/* Responsive table styles */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
}

table th, table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
    white-space: nowrap;
}

/* Make specific columns wrap on small screens */
        table td.feedback-preview {
            white-space: normal;
            max-width: 200px;
        }

        /* Report Modal Specific Styles */
        .report-list {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin: 20px 0;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table th {
            position: sticky;
            top: 0;
            background: #f0f7ff;
            z-index: 10;
        }
        
        .report-table td, .report-table th {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .custom-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #1F3A93;
        }

table td.feedback-preview {
    white-space: normal;
    max-width: 200px;
}

table th {
    background-color: #f0f7ff;
    font-weight: 600;
    color: #1a317d;
}

table tr:hover {
    background-color: #f9fafb;
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    table th, table td {
        padding: 12px 10px;
        font-size: 14px;
    }
    
    table td.feedback-preview {
        max-width: 150px;
    }
}

@media (max-width: 768px) {
    .recent-feedback-table {
        display: none;
    }
    
    .recent-feedback-mobile {
        display: block;
    }
    
    table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }
    
    table th, table td {
        font-size: 13px;
        padding: 10px 8px;
    }
}

@media (max-width: 480px) {
    .feedback-card-mobile {
        padding: 15px;
    }
    
    .feedback-avatar {
        width: 40px;
        height: 40px;
        font-size: 14px;
    }
    
    .feedback-username {
        font-size: 14px;
    }
    
    .feedback-comment {
        font-size: 13px;
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

    <!-- ========== GENERATE REPORT MODAL ========== -->
    <div class="modal-overlay" id="reportModal">
        <div class="modal-container" style="max-width: 800px;">
            <div class="modal-header">
                <i class="fas fa-file-pdf"></i>
                <h2>Generate Report</h2>
                <button class="modal-close" id="closeReportModalBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form action="print_report.php" method="POST" target="_blank" id="reportForm">
                <div class="modal-body">
                    <p>Select the feedback entries you want to include in the report.</p>
                    
                    <div style="margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: 500; cursor: pointer;">
                            <input type="checkbox" id="selectAllReports" class="custom-checkbox">
                            Select All
                        </label>
                        <span style="font-size: 14px; color: #6b7280;">Showing last 50 entries</span>
                    </div>

                    <div class="report-list">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th width="40" style="text-align: center;">#</th>
                                    <th>Date</th>
                                    <th>User</th>
                                    <th>Category</th>
                                    <th>Rating</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($report_result->num_rows > 0): ?>
                                    <?php while($row = $report_result->fetch_assoc()): ?>
                                        <tr>
                                            <td style="text-align: center;">
                                                <input type="checkbox" name="feedback_ids[]" value="<?php echo $row['id']; ?>" class="report-checkbox custom-checkbox">
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                                            <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                            <td><?php echo displayRating($row['rating']); ?></td>
                                            <td>
                                                <?php if($row['is_resolved']): ?>
                                                    <span class="green-badge" style="margin: 0; font-size: 10px;">Resolved</span>
                                                <?php else: ?>
                                                    <span style="background: #fee2e2; color: #dc2626; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: 600;">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 20px;">No feedback available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="modal-btn modal-btn-secondary" id="cancelReportBtn">
                        Cancel
                    </button>
                    <button type="submit" class="modal-btn modal-btn-primary" id="generateBtn">
                        <i class="fas fa-print"></i> Generate & Print
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- ========== END GENERATE REPORT MODAL ========== -->

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
                <a href="admin_settings.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_settings.php' ? 'active' : ''; ?>" data-tooltip="Admin Settings">
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
            <li class="menu-item">
                <a href="system_logs.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'system_logs.php' ? 'active' : ''; ?>" data-tooltip="System Logs">
                    <i class="fas fa-history menu-icon"></i>
                    <span class="menu-text">System Logs</span>
                </a>
            </li>
            <!-- <li class="menu-item">
                <a href="analytics.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>" data-tooltip="Analytics">
                    <i class="fas fa-chart-bar menu-icon"></i>
                    <span class="menu-text">Analytics</span>
                </a>
            </li> -->
            <!-- <li class="menu-item">
                <a href="#" class="menu-link" data-tooltip="Reports">
                    <i class="fas fa-file-alt menu-icon"></i>
                    <span class="menu-text">Reports</span>
                </a>
            </li> -->
            <!-- <li class="menu-item">
                <a href="#" class="menu-link" data-tooltip="Notifications">
                    <i class="fas fa-bell menu-icon"></i>
                    <span class="menu-text">Notifications</span>
                    <span class="notification-badge">3</span>
                </a>
            </li> -->
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
                <i class="fas fa-tachometer-alt title-icon"></i>
                Admin Dashboard
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

        <!-- Welcome Message -->
        <div class="dashboard-header">
            <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['firstname']); ?>!</h1>
            <p>Last login: <?php echo date('F j, Y, g:i a'); ?></p>
        </div>

        <!-- Statistics -->
        <div class="dashboard-card">
            <h2><i class="fas fa-chart-bar"></i> System Overview</h2>
            <div class="dashboard-stats">
                <div class="stat-card-new">
                    <div class="stat-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_feedback']; ?></h3>
                        <p>Total Feedback</p>
                    </div>
                </div>
                
                <div class="stat-card-new">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_users']; ?></h3>
                        <p>Active Users</p>
                    </div>
                </div>
                
                <div class="stat-card-new">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['avg_rating'], 1); ?>/5</h3>
                        <p>Average Rating</p>
                    </div>
                </div>
                
                <div class="stat-card-new">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $recent['recent_count']; ?></h3>
                        <p>Recent (7 days)</p>
                    </div>

                </div>

                <div class="stat-card-new">
                    <div class="stat-icon">
                        <i class="fas fa-poll"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $survey_stats['active_surveys']; ?></h3>
                        <p>Active Surveys</p>
                    </div>
                </div>


            </div>
        </div>
        
        <!-- Sentiment Chart -->
        <div class="dashboard-card">
            <?php
            // Filter Logic
            $cat_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
            $sentiment_title = "Overall Sentiment";
            
            // Fetch Categories
            $categories_res = $conn->query("SELECT * FROM categories ORDER BY name ASC");
            
            // Build Query
            $sent_sql = "SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN sentiment = 'Positive' THEN 1 ELSE 0 END) as pos,
                            SUM(CASE WHEN sentiment = 'Neutral' THEN 1 ELSE 0 END) as neu,
                            SUM(CASE WHEN sentiment = 'Negative' THEN 1 ELSE 0 END) as neg
                         FROM feedback";
            
            if ($cat_filter > 0) {
                $sent_sql .= " WHERE category_id = $cat_filter";
                $cat_name_res = $conn->query("SELECT name FROM categories WHERE id = $cat_filter");
                if ($cat_name_res->num_rows > 0) {
                    $sentiment_title = "Sentiment: " . $cat_name_res->fetch_assoc()['name'];
                }
            }
            
            $sent_data = $conn->query($sent_sql)->fetch_assoc();
            
            // Prepare Data for View
            $s_total = $sent_data['total'];
            $s_pos = $sent_data['pos'] ?? 0;
            $s_neu = $sent_data['neu'] ?? 0;
            $s_neg = $sent_data['neg'] ?? 0;
            ?>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                <h2 style="margin-bottom: 0;"><i class="fas fa-chart-pie"></i> <?php echo htmlspecialchars($sentiment_title); ?></h2>
                
                <form method="GET" style="display: flex; align-items: center; gap: 10px;">
                    <select name="category" onchange="this.form.submit()" style="padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; color: #1a317d; font-size: 14px; outline: none; cursor: pointer; min-width: 150px;">
                        <option value="0" <?php echo $cat_filter == 0 ? 'selected' : ''; ?>>All Categories</option>
                        <?php 
                        if ($categories_res->num_rows > 0) {
                            while($cat = $categories_res->fetch_assoc()) {
                                $sel = $cat_filter == $cat['id'] ? 'selected' : '';
                                echo '<option value="'.$cat['id'].'" '.$sel.'>'.htmlspecialchars($cat['name']).'</option>';
                            }
                        }
                        ?>
                    </select>
                </form>
            </div>
            
            <?php if($s_total > 0): 
                $pos_pct = round(($s_pos / $s_total) * 100);
                $neu_pct = round(($s_neu / $s_total) * 100);
                $neg_pct = round(($s_neg / $s_total) * 100);
            ?>
            
            <div class="sentiment-chart">
                <div class="sentiment-bar" style="height: <?php echo ($pos_pct / 100) * 200; ?>px;">
                    <div class="sentiment-label">Positive<br><?php echo $s_pos; ?> (<?php echo $pos_pct; ?>%)</div>
                </div>
                <div class="sentiment-bar neutral" style="height: <?php echo ($neu_pct / 100) * 200; ?>px;">
                    <div class="sentiment-label">Neutral<br><?php echo $s_neu; ?> (<?php echo $neu_pct; ?>%)</div>
                </div>
                <div class="sentiment-bar negative" style="height: <?php echo ($neg_pct / 100) * 200; ?>px;">
                    <div class="sentiment-label">Negative<br><?php echo $s_neg; ?> (<?php echo $neg_pct; ?>%)</div>
                </div>
            </div>
            
            <div class="sentiment-summary">
                <p>Positive: <strong><?php echo $s_pos; ?></strong> | 
                   Neutral: <strong><?php echo $s_neu; ?></strong> | 
                   Negative: <strong><?php echo $s_neg; ?></strong></p>
            </div>
            
            <?php else: ?>
                <div style="text-align: center; padding: 20px; color: #94a3b8; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1;">
                    <i class="fas fa-chart-bar" style="font-size: 24px; margin-bottom: 10px; opacity: 0.5;"></i>
                    <p>No feedback available for analysis.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div class="dashboard-card">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div class="admin-actions">
                <a href="manage_feedback.php" class="action-card">
                    <i class="fas fa-list-alt"></i>
                    <h3>Manage Feedback</h3>
                    <p>View, filter, and manage all feedback submissions</p>
                </a>
                
                <a href="user_management.php" class="action-card">
                    <i class="fas fa-user-cog"></i>
                    <h3>User Management</h3>
                    <p>Manage user accounts and permissions</p>
                </a>
                
                <a href="#" class="action-card" id="openReportModalTrigger">
                    <i class="fas fa-file-pdf"></i>
                    <h3>Generate Reports</h3>
                    <p>Create detailed feedback reports</p>
                </a>
                
                <a href="surveys.php" class="action-card">
                    <i class="fas fa-poll-h"></i>
                    <h3>Manage Surveys</h3>
                    <p>Create and monitor surveys</p>
                </a>
                
                <a href="manage_feedback.php" class="action-card">
                    <i class="fas fa-tags"></i>
                    <h3>Categories</h3>
                    <p>Manage feedback categories</p>
                </a>
            </div>
        </div>
        
        <!-- Recent Feedback -->
        <!-- <div class="dashboard-card">
            <h2><i class="fas fa-clock"></i> Recent Feedback</h2>
            
            <?php if ($recent_feedback_result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Category</th>
                            <th>Rating</th>
                            <th>Feedback</th>
                            <th>Sentiment</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $recent_feedback_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                <td><?php echo displayRating($row['rating']); ?></td>
                                <td><?php echo htmlspecialchars(substr($row['comment'], 0, 50)); ?>...</td>
                                <td><?php echo getSentimentBadge($row['sentiment']); ?></td>
                                <td><?php echo formatDate($row['created_at']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="manage_feedback.php" class="btn">
                        <i class="fas fa-eye"></i> View All Feedback
                    </a>
                </div>
            <?php else: ?>
                <div class="alert">
                    <i class="fas fa-info-circle"></i> No feedback submitted yet.
                </div>
            <?php endif; ?>
        </div> -->

                <!-- Recent Feedback -->
        <div class="dashboard-card">
            <h2><i class="fas fa-clock"></i> Recent Feedback</h2>
            
            <?php if ($recent_feedback_result->num_rows > 0): ?>
                <!-- Mobile View (Cards) -->
                <div class="recent-feedback-mobile">
                    <?php 
                    // Reset the result pointer to loop again
                    $recent_feedback_result->data_seek(0);
                    while($row = $recent_feedback_result->fetch_assoc()): ?>
                        <div class="feedback-card-mobile">
                            <div class="feedback-card-header">
                                <div class="feedback-user-info">
                                    <div class="feedback-avatar">
                                        <?php echo strtoupper(substr($row['username'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="feedback-username"><?php echo htmlspecialchars($row['username']); ?></div>
                                        <div class="feedback-category"><?php echo htmlspecialchars($row['category_name']); ?></div>
                                    </div>
                                </div>
                                <div class="feedback-sentiment">
                                    <?php echo getSentimentBadge($row['sentiment']); ?>
                                </div>
                            </div>
                            
                            <div class="feedback-rating">
                                <?php echo displayRating($row['rating']); ?>
                                <span class="feedback-date"><?php echo formatDate($row['created_at']); ?></span>
                            </div>
                            
                            <div class="feedback-comment">
                                "<?php echo htmlspecialchars(substr($row['comment'], 0, 100)); ?><?php echo strlen($row['comment']) > 100 ? '...' : ''; ?>"
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Desktop View (Table) - Hidden on Mobile -->
                <div class="recent-feedback-table">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Category</th>
                                <th>Rating</th>
                                <th>Feedback</th>
                                <th>Sentiment</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Reset again for desktop table
                            $recent_feedback_result->data_seek(0);
                            while($row = $recent_feedback_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                    <td><?php echo displayRating($row['rating']); ?></td>
                                    <td class="feedback-preview"><?php echo htmlspecialchars(substr($row['comment'], 0, 50)); ?>...</td>
                                    <td><?php echo getSentimentBadge($row['sentiment']); ?></td>
                                    <td><?php echo formatDate($row['created_at']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="manage_feedback.php" class="btn">
                        <i class="fas fa-eye"></i> View All Feedback
                    </a>
                </div>
            <?php else: ?>
                <div class="alert">
                    <i class="fas fa-info-circle"></i> No feedback submitted yet.
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Resident Feedback and Survey System - Admin Portal</p>
            <p style="font-size: 0.9rem; color: #777;">Administrator Access Only</p>
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
// Call the updated function
setupTooltips();
// ========== END LOGOUT MODAL FUNCTIONALITY ==========

// ========== REPORT MODAL FUNCTIONALITY ==========
const reportModal = document.getElementById('reportModal');
const openReportBtn = document.getElementById('openReportModalTrigger');
const closeReportBtn = document.getElementById('closeReportModalBtn');
const cancelReportBtn = document.getElementById('cancelReportBtn');
const reportForm = document.getElementById('reportForm');
const selectAllReports = document.getElementById('selectAllReports');
const reportCheckboxes = document.querySelectorAll('.report-checkbox');

function openReportModal(e) {
    if(e) e.preventDefault();
    reportModal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeReportModal() {
    reportModal.classList.remove('active');
    document.body.style.overflow = '';
}

if(openReportBtn) openReportBtn.addEventListener('click', openReportModal);
if(closeReportBtn) closeReportBtn.addEventListener('click', closeReportModal);
if(cancelReportBtn) cancelReportBtn.addEventListener('click', closeReportModal);

// Close on click outside
if(reportModal) {
    reportModal.addEventListener('click', (e) => {
        if(e.target === reportModal) closeReportModal();
    });
}

// Select All logic
if(selectAllReports) {
    selectAllReports.addEventListener('change', function() {
        const isChecked = this.checked;
        reportCheckboxes.forEach(cb => cb.checked = isChecked);
    });
}

// Update "Select All" if individual boxes are changed
reportCheckboxes.forEach(cb => {
    cb.addEventListener('change', function() {
        if(!this.checked) {
            selectAllReports.checked = false;
        } else {
            // Check if all are checked
            const allChecked = Array.from(reportCheckboxes).every(c => c.checked);
            if(allChecked) selectAllReports.checked = true;
        }
    });
});

// Require at least one selection
if(reportForm) {
    reportForm.addEventListener('submit', function(e) {
        const selected = document.querySelectorAll('.report-checkbox:checked');
        if(selected.length === 0) {
            e.preventDefault();
            alert('Please select at least one feedback item to generate a report.');
        } else {
            // Optional: Close modal after short delay
            setTimeout(closeReportModal, 500);
        }
    });
}
// ========== END REPORT MODAL FUNCTIONALITY ==========

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

        // Add a subtle animation to stat cards on load
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card-new');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.transform = 'translateY(0)';
                    card.style.opacity = '1';
                }, index * 100);
            });
        });

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
    </script>
    <script src="../js/theme.js"></script>
</body>
</html>