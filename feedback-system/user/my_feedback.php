<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireUser();

// Add maintenance mode check
checkMaintenanceMode();

// Add maintenance mode check


// Add this after line 7 (after requireUser())
// Fetch user data from database
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT id, first_name as firstname, surname as lastname, user_role as user_type, NULL as image_path, email, username, password, purok FROM `profiling-system`.residents WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

$user_id = $_SESSION['user_id'];

// Pagination
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total records
$count_sql = "SELECT COUNT(*) as total FROM feedback WHERE user_id = ?";
$stmt = $conn->prepare($count_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$count_result = $stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Get feedback with pagination
$sql = "SELECT f.*, c.name as category_name, c.icon as category_icon,
        fa.status as assignment_status, fa.assigned_at, fa.started_at, fa.completed_at,
        p.name as personnel_name, sc.name as personnel_specialization
        FROM feedback f 
        JOIN categories c ON f.category_id = c.id 
        LEFT JOIN feedback_assignments fa ON f.id = fa.feedback_id
        LEFT JOIN personnel p ON fa.personnel_id = p.id
        LEFT JOIN categories sc ON p.specialization_id = sc.id
        WHERE f.user_id = ? 
        ORDER BY f.created_at DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Feedback - Resident Feedback and Survey System</title>
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

        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }

        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .alert-error i {
            font-size: 18px;
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
            font-weight: 700;
        }

        .title-icon {
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

        /* Mobile Feedback Cards */
        .feedback-card {
            background: var(--card-light);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-light);
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
            transition: transform 0.2s;
            color: var(--text-light);
        }

        /* Dashboard Card */
        .dashboard-card {
            background: var(--card-light);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            margin-bottom: 30px;
            border: 1px solid var(--border-light);
            color: var(--text-light);
        }

        .dashboard-card h2 {
            margin-bottom: 20px;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
        }

        table th,
        table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-light);
        }

        table th {
            font-weight: 600;
        }

        /* table tr:hover removed to allow theme.css to handle it */

        .category-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .category-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .rating-stars {
            color: #fbbf24;
        }

        .sentiment-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            min-width: 80px;
            text-align: center;
        }

        /* Badges styles removed to allow theme.css to handle colors */

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 5px;
        }

        /* Status badges styles removed - handled by theme.css */

        .feedback-comment {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 14px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 16px;
            background: linear-gradient(90deg, #1F3A93, #3a56b5);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 500;
            border: none;
            cursor: pointer;
            font-size: 13px;
        }

        .btn:hover {
            background: linear-gradient(90deg, #1a317d, #1F3A93);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(31, 58, 147, 0.2);
        }

        .btn-success {
            background: linear-gradient(90deg, #1F3A93, #3a56b5);
        }

        .btn-success:hover {
            background: linear-gradient(90deg, #1a317d, #1F3A93);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-top: 30px;
            padding: 20px 0;
        }

        .page-link {
            padding: 10px 20px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            color: #4b5563;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .page-link:hover {
            background: #f0f7ff;
            color: #1a317d;
            border-color: #1F3A93;
        }

        .page-info {
            padding: 10px 20px;
            background: #f0f7ff;
            border-radius: 8px;
            color: #1a317d;
            font-weight: 500;
        }

        /* Table Actions */
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .table-header h2 {
            color: #1a317d;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-header h2 i {
            color: #1F3A93;
        }

        .result-count {
            background: #f0f7ff;
            padding: 8px 15px;
            border-radius: 8px;
            color: #1a317d;
            font-weight: 500;
            border: 1px solid #bae6fd;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
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
            color: #6b7280;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .empty-state p {
            color: #9ca3af;
            margin-bottom: 30px;
            font-size: 14px;
        }

        /* Modal */
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
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
            color: #6b7280;
            cursor: pointer;
            background: #f9fafb;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .close-modal:hover {
            background: #ef4444;
            color: white;
        }

        .modal-details {
            margin-top: 20px;
        }

        .detail-row {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }

        .detail-label {
            font-weight: 600;
            color: #1a317d;
            width: 120px;
            flex-shrink: 0;
        }

        .detail-value {
            flex: 1;
            color: #4b5563;
            line-height: 1.6;
        }

        /* Star Rating Editor */
        .rating-edit i {
            cursor: pointer;
            transition: all 0.2s ease;
            margin: 0 2px;
        }

        .rating-edit i:hover {
            transform: scale(1.2);
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

            .table-header {
                flex-direction: column;
                align-items: flex-start;
            }

            table {
                display: block;
                overflow-x: auto;
            }

            table th,
            table td {
                padding: 12px 10px;
                font-size: 13px;
            }

            .pagination {
                flex-wrap: wrap;
            }

            .toggle-btn {
                display: flex !important;
            }

            .modal-content {
                width: 95%;
                padding: 20px;
            }

            .detail-row {
                flex-direction: column;
                align-items: flex-start;
            }

            .detail-label {
                width: 100%;
                margin-bottom: 5px;
            }
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
        }

        /* Modal Styles (Add if not present) */
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
            max-height: 80vh;
            overflow-y: auto;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        /* ============================================
        RESPONSIVE TABLE STYLES
        ============================================ */

        /* Table container with horizontal scroll on mobile */
        .table-container {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 10px;
            margin-top: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            background: white;
        }

        /* Ensure table has minimum width for proper display */
        table {
            min-width: 800px;
            /* Minimum width before scrolling */
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        /* Mobile card layout (hidden by default) */
        .feedback-cards {
            display: none;
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
        }

        .feedback-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .feedback-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            border-color: #1F3A93;
        }

        /* Card header */
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f3f4f6;
        }

        .card-category {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-category-icon {
            width: 35px;
            height: 35px;
            background: #f0f7ff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1F3A93;
            flex-shrink: 0;
        }

        .card-category-name {
            font-weight: 600;
            color: #1a317d;
            font-size: 16px;
        }

        .card-date {
            color: #6b7280;
            font-size: 13px;
            background: #f9fafb;
            padding: 4px 10px;
            border-radius: 6px;
            white-space: nowrap;
        }

        /* Card body */
        .card-body {
            margin-bottom: 15px;
        }

        .card-rating {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .card-rating-stars {
            color: #fbbf24;
            font-size: 16px;
        }

        .card-sentiment {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .card-comment {
            color: #4b5563;
            line-height: 1.6;
            font-size: 14px;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Card footer */
        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #f3f4f6;
        }

        .card-status {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Pagination responsive styles */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            padding: 20px 0;
            flex-wrap: wrap;
        }

        .page-link {
            padding: 8px 16px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            color: #4b5563;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-size: 14px;
            white-space: nowrap;
        }

        .page-info {
            padding: 8px 16px;
            background: #f0f7ff;
            border-radius: 8px;
            color: #1a317d;
            font-weight: 500;
            border: 1px solid #bae6fd;
            font-size: 14px;
        }

        /* Responsive breakpoints */
        @media (max-width: 1024px) {
            table {
                min-width: 700px;
                font-size: 14px;
            }

            table th,
            table td {
                padding: 12px 8px;
            }

            .feedback-comment {
                max-width: 200px;
            }
        }

        @media (max-width: 768px) {

            /* Hide table, show cards on mobile */
            .table-container {
                display: none;
            }

            .feedback-cards {
                display: flex;
            }

            /* Adjust card layout for smaller screens */
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .card-date {
                align-self: flex-start;
            }

            .card-rating {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .card-footer {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }

            .card-footer .btn {
                width: 100%;
                justify-content: center;
            }

            /* Adjust pagination for mobile */
            .pagination {
                gap: 8px;
            }

            .page-link {
                padding: 8px 12px;
                font-size: 13px;
            }

            .page-info {
                padding: 8px 12px;
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .feedback-card {
                padding: 15px;
            }

            .card-category {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .card-sentiment {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .card-comment {
                -webkit-line-clamp: 4;
            }

            /* Make pagination more compact */
            .pagination {
                flex-direction: column;
                gap: 10px;
            }

            .page-link {
                width: 100%;
                justify-content: center;
            }

            .page-info {
                width: 100%;
                text-align: center;
            }
        }

        /* Desktop: Show table, hide cards */
        @media (min-width: 769px) {
            .table-container {
                display: block;
            }

            .feedback-cards {
                display: none;
            }
        }

        /* Dark Mode Overrides */
        body.dark-mode .dashboard-card,
        body.dark-mode .feedback-card {
            background: #1f2937;
            border-color: #374151;
            color: #e5e7eb;
        }

        body.dark-mode .dashboard-card h2 {
            color: #e5e7eb;
        }

        body.dark-mode table {
            background: #1f2937;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        body.dark-mode table th,
        body.dark-mode table td {
            border-color: #374151;
            color: #e5e7eb;
        }

        body.dark-mode table th {
            background: #111827;
            color: #ffffff;
        }

        body.dark-mode .page-link {
            background: #1f2937;
            border-color: #374151;
            color: #e5e7eb;
        }

        body.dark-mode .page-link:hover {
            background: #374151;
            border-color: #3b82f6;
            color: #3b82f6;
        }

        body.dark-mode .page-info {
            background: #111827;
            color: #e5e7eb;
        }

        body.dark-mode .empty-state {
            background: #1f2937;
            border-color: #374151;
        }

        body.dark-mode .empty-state h3 {
            color: #e5e7eb;
        }

        body.dark-mode .modal-content {
            background: #1f2937;
            color: #e5e7eb;
        }

        body.dark-mode .detail-label {
            color: #9ca3af;
        }

        body.dark-mode .detail-value {
            color: #e5e7eb;
        }

        body.dark-mode .close-modal {
            background: #374151;
            color: #e5e7eb;
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
                    class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? '' : ''; ?>"
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
            <!-- <li class="menu-item">
                <a href="feedback_form.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'feedback_form.php' ? '' : ''; ?>" data-tooltip="Submit Feedback">
                    <i class="fas fa-plus-circle menu-icon"></i>
                    <span class="menu-text"><?php echo __('submit_feedback'); ?></span>
                </a>
            </li> -->

            <li class="menu-item">
                <a href="settings.php"
                    class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? '' : ''; ?>"
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
            <h1 class="page-title">
                <i class="fas fa-history title-icon"></i>
                <?php echo __('my_feedback_history'); ?>
            </h1>
            <div class="date-display">
                <i class="far fa-calendar-alt"></i>
                <span id="currentDate"><?php echo date('F j, Y'); ?></span>
            </div>
        </div>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $_SESSION['error_message'];
                unset($_SESSION['error_message']); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert" style="background-color: #d1e7dd; color: #0f5132; border-color: #badbcc;">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $_SESSION['success_message'];
                unset($_SESSION['success_message']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Dashboard Card -->
        <div class="dashboard-card">
            <div class="table-header">
                <h2><i class="fas fa-comments"></i> <?php echo __('all_submitted_feedback'); ?></h2>
                <div class="result-count">
                    <?php echo sprintf(__('total_entries'), $total_records); ?>
                </div>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <!-- Desktop Table View -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th><?php echo __('category'); ?></th>
                                <th><?php echo __('rating'); ?></th>
                                <th><?php echo __('comment'); ?></th>
                                <th><?php echo __('status'); ?></th>
                                <th>Assignment</th>
                                <th><?php echo __('date'); ?></th>
                                <th><?php echo __('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Reset result pointer for table
                            $result->data_seek(0);
                            while ($row = $result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td>
                                        <div class="category-cell">
                                            <div class="category-icon">
                                                <i class="<?php echo getCategoryIcon($row['category_icon']); ?>"></i>
                                            </div>
                                            <span><?php echo htmlspecialchars($row['category_name']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="rating-stars" id="rating-container-desktop-<?php echo $row['id']; ?>">
                                            <?php echo displayRating($row['rating']); ?>
                                            <?php if (($row['is_resolved'] || $row['sentiment'] === 'Positive') && !$row['is_updated_rating']): ?>
                                                <button class="btn btn-success"
                                                    style="font-size: 11px; padding: 4px 8px; margin-top: 5px; width: 100%; white-space: normal; text-align: center;"
                                                    onclick="viewFeedback(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['category_name']); ?>', '<?php echo $row['rating']; ?>', '<?php echo htmlspecialchars(addslashes($row['comment'])); ?>', '<?php echo $row['sentiment']; ?>', '<?php echo formatDate($row['created_at']); ?>', '<?php echo $row['is_resolved']; ?>', '<?php echo isset($row['resolved_by']) ? htmlspecialchars(addslashes($row['resolved_by'])) : ''; ?>', '<?php echo isset($row['resolved_at']) ? formatDate($row['resolved_at']) : ''; ?>', '<?php echo $row['personnel_name'] ? htmlspecialchars(addslashes($row['personnel_name'])) : ''; ?>', '<?php echo $row['personnel_specialization'] ? htmlspecialchars(addslashes($row['personnel_specialization'])) : ''; ?>', '<?php echo $row['assignment_status'] ? htmlspecialchars($row['assignment_status']) : ''; ?>', '<?php echo $row['assigned_at'] ? formatDate($row['assigned_at']) : ''; ?>', '<?php echo $row['started_at'] ? formatDate($row['started_at']) : ''; ?>', '<?php echo $row['completed_at'] ? formatDate($row['completed_at']) : ''; ?>', '<?php echo $row['attachment_path'] ? htmlspecialchars($row['attachment_path']) : ''; ?>', '<?php echo $row['is_updated_rating']; ?>')">
                                                    <i class="fas fa-star"></i>
                                                    <?php echo $row['rating'] > 0 ? 'Update Rating' : 'Rate Now'; ?>
                                                </button>
                                            <?php elseif (($row['is_resolved'] || $row['sentiment'] === 'Positive') && $row['is_updated_rating']): ?>
                                                <div
                                                    style="font-size: 10px; color: #10b981; font-weight: 500; white-space: nowrap; margin-top: 5px;">
                                                    <i class="fas fa-check-circle"></i> Rated
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="feedback-comment" title="<?php echo htmlspecialchars($row['comment']); ?>">
                                            <?php echo htmlspecialchars(substr($row['comment'], 0, 80)); ?>...
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($row['is_resolved']): ?>
                                            <div class="status-badge status-resolved">
                                                <i class="fas fa-check-circle"></i> <?php echo __('resolved'); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="status-badge status-pending">
                                                <i class="fas fa-clock"></i> <?php echo __('under_review'); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['personnel_name']): ?>
                                            <div style="font-size: 13px;">
                                                <div style="font-weight: 600; color: #1a317d;">
                                                    <i class="fas fa-user-hard-hat"></i>
                                                    <?php echo htmlspecialchars($row['personnel_name']); ?>
                                                </div>
                                                <div style="font-size: 11px; color: #6b7280; margin-bottom: 2px;">
                                                    <?php echo htmlspecialchars($row['personnel_specialization']); ?>
                                                </div>
                                                <?php echo getAssignmentStatusBadge($row['assignment_status']); ?>
                                            </div>
                                        <?php elseif ($row['assignment_status'] === 'Waiting'): ?>
                                            <div style="font-size: 13px;">
                                                <span style="color: #d97706; font-weight: 600; font-size: 12px;">
                                                    <i class="fas fa-hourglass-half"></i> TBA (To Be Assigned)
                                                </span>
                                                <div style="margin-top: 3px;">
                                                    <?php echo getAssignmentStatusBadge($row['assignment_status']); ?>
                                                </div>
                                            </div>
                                        <?php elseif ($row['assignment_status'] === 'In Progress'): ?>
                                            <div style="font-size: 13px;">
                                                <span style="color: #1e40af; font-weight: 600; font-size: 12px;">
                                                    <i class="fas fa-spinner fa-spin"></i> TBA (To Be Announced)
                                                </span>
                                                <div style="margin-top: 3px;">
                                                    <?php echo getAssignmentStatusBadge($row['assignment_status']); ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #9ca3af; font-size: 12px;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatDate($row['created_at']); ?></td>
                                    <td>
                                        <button class="btn" onclick="viewFeedback(
                                            <?php echo $row['id']; ?>, 
                                            '<?php echo htmlspecialchars($row['category_name']); ?>', 
                                            '<?php echo $row['rating']; ?>', 
                                            '<?php echo htmlspecialchars(addslashes($row['comment'])); ?>', 
                                            '<?php echo $row['sentiment']; ?>', 
                                            '<?php echo formatDate($row['created_at']); ?>', 
                                            '<?php echo $row['is_resolved']; ?>', 
                                            '<?php echo isset($row['resolved_by']) ? htmlspecialchars(addslashes($row['resolved_by'])) : ''; ?>', 
                                            '<?php echo isset($row['resolved_at']) ? formatDate($row['resolved_at']) : ''; ?>',
                                            '<?php echo $row['personnel_name'] ? htmlspecialchars(addslashes($row['personnel_name'])) : ''; ?>',
                                            '<?php echo $row['personnel_specialization'] ? htmlspecialchars(addslashes($row['personnel_specialization'])) : ''; ?>',
                                            '<?php echo $row['assignment_status'] ? htmlspecialchars($row['assignment_status']) : ''; ?>',
                                            '<?php echo $row['assigned_at'] ? formatDate($row['assigned_at']) : ''; ?>',
                                            '<?php echo $row['started_at'] ? formatDate($row['started_at']) : ''; ?>',
                                            '<?php echo $row['completed_at'] ? formatDate($row['completed_at']) : ''; ?>',
                                            '<?php echo $row['attachment_path'] ? htmlspecialchars($row['attachment_path']) : ''; ?>',
                                            '<?php echo $row['is_updated_rating']; ?>'
                                        )">
                                            <i class="fas fa-eye"></i> <?php echo __('view'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="feedback-cards">
                    <?php
                    // Reset result pointer for cards
                    $result->data_seek(0);
                    while ($row = $result->fetch_assoc()):
                        $sentiment_class = '';
                        if ($row['sentiment'] == 'Positive') {
                            $sentiment_class = 'sentiment-positive';
                        } elseif ($row['sentiment'] == 'Negative') {
                            $sentiment_class = 'sentiment-negative';
                        } else {
                            $sentiment_class = 'sentiment-neutral';
                        }
                        ?>
                        <div class="feedback-card">
                            <div class="card-header">
                                <div class="card-category">
                                    <div class="card-category-icon">
                                        <i class="<?php echo getCategoryIcon($row['category_icon']); ?>"></i>
                                    </div>
                                    <div class="card-category-name">
                                        <?php echo htmlspecialchars($row['category_name']); ?>
                                    </div>
                                </div>
                                <div class="card-date">
                                    <?php echo formatDate($row['created_at']); ?>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="card-rating">
                                    <div class="card-rating-stars" id="rating-container-mobile-<?php echo $row['id']; ?>">
                                        <?php echo displayRating($row['rating']); ?>
                                        <?php if (($row['is_resolved'] || $row['sentiment'] === 'Positive') && !$row['is_updated_rating']): ?>
                                            <button class="btn btn-success"
                                                style="font-size: 11px; padding: 4px 8px; margin-top: 5px; width: 100%; white-space: normal; text-align: center;"
                                                onclick="viewFeedback(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['category_name']); ?>', '<?php echo $row['rating']; ?>', '<?php echo htmlspecialchars(addslashes($row['comment'])); ?>', '<?php echo $row['sentiment']; ?>', '<?php echo formatDate($row['created_at']); ?>', '<?php echo $row['is_resolved']; ?>', '<?php echo isset($row['resolved_by']) ? htmlspecialchars(addslashes($row['resolved_by'])) : ''; ?>', '<?php echo isset($row['resolved_at']) ? formatDate($row['resolved_at']) : ''; ?>', '<?php echo $row['personnel_name'] ? htmlspecialchars(addslashes($row['personnel_name'])) : ''; ?>', '<?php echo $row['personnel_specialization'] ? htmlspecialchars(addslashes($row['personnel_specialization'])) : ''; ?>', '<?php echo $row['assignment_status'] ? htmlspecialchars($row['assignment_status']) : ''; ?>', '<?php echo $row['assigned_at'] ? formatDate($row['assigned_at']) : ''; ?>', '<?php echo $row['started_at'] ? formatDate($row['started_at']) : ''; ?>', '<?php echo $row['completed_at'] ? formatDate($row['completed_at']) : ''; ?>', '<?php echo $row['attachment_path'] ? htmlspecialchars($row['attachment_path']) : ''; ?>', '<?php echo $row['is_updated_rating']; ?>')">
                                                <i class="fas fa-star"></i>
                                                <?php echo $row['rating'] > 0 ? 'Update Rating' : 'Rate Now'; ?>
                                            </button>
                                        <?php elseif (($row['is_resolved'] || $row['sentiment'] === 'Positive') && $row['is_updated_rating']): ?>
                                            <div
                                                style="font-size: 10px; color: #10b981; font-weight: 500; white-space: normal; margin-top: 5px;">
                                                <i class="fas fa-check-circle"></i> Rated
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-sentiment">
                                        <?php if ($row['is_resolved']): ?>
                                            <div class="status-badge status-resolved">
                                                <i class="fas fa-check-circle"></i> <?php echo __('resolved'); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="status-badge status-pending">
                                                <i class="fas fa-clock"></i> <?php echo __('under_review'); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="card-comment" title="<?php echo htmlspecialchars($row['comment']); ?>">
                                    <?php echo htmlspecialchars(substr($row['comment'], 0, 150)); ?>
                                    <?php if (strlen($row['comment']) > 150): ?>...<?php endif; ?>
                                </div>
                            </div>

                            <div class="card-footer">
                                <?php if ($row['personnel_name']): ?>
                                    <div
                                        style="width: 100%; margin-bottom: 15px; padding: 10px; background: #f0f7ff; border-radius: 8px; border: 1px solid #bae6fd;">
                                        <div style="font-size: 11px; color: #6b7280; margin-bottom: 5px;">ASSIGNED PERSONNEL</div>
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <div>
                                                <div style="font-weight: 600; color: #1a317d; font-size: 14px;">
                                                    <i class="fas fa-user-hard-hat"></i>
                                                    <?php echo htmlspecialchars($row['personnel_name']); ?>
                                                </div>
                                                <div style="font-size: 12px; color: #4b5563;">
                                                    <?php echo htmlspecialchars($row['personnel_specialization']); ?>
                                                </div>
                                            </div>
                                            <?php echo getAssignmentStatusBadge($row['assignment_status']); ?>
                                        </div>
                                    </div>
                                <?php elseif ($row['assignment_status'] === 'Waiting'): ?>
                                    <div
                                        style="width: 100%; margin-bottom: 15px; padding: 10px; background: #fffbeb; border-radius: 8px; border: 1px solid #fcd34d;">
                                        <div style="font-size: 11px; color: #92400e; margin-bottom: 5px;">ASSIGNMENT STATUS</div>
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <div>
                                                <div style="font-weight: 600; color: #b45309; font-size: 14px;">
                                                    <i class="fas fa-hourglass-half"></i> TBA (To Be Assigned)
                                                </div>
                                                <div style="font-size: 12px; color: #92400e;">
                                                    Waiting for available personnel
                                                </div>
                                            </div>
                                            <?php echo getAssignmentStatusBadge($row['assignment_status']); ?>
                                        </div>
                                    </div>
                                <?php elseif ($row['assignment_status'] === 'In Progress'): ?>
                                    <div
                                        style="width: 100%; margin-bottom: 15px; padding: 10px; background: #eff6ff; border-radius: 8px; border: 1px solid #93c5fd;">
                                        <div style="font-size: 11px; color: #1e40af; margin-bottom: 5px;">ASSIGNMENT STATUS</div>
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <div>
                                                <div style="font-weight: 600; color: #1e40af; font-size: 14px;">
                                                    <i class="fas fa-spinner fa-spin"></i> TBA (To Be Announced)
                                                </div>
                                                <div style="font-size: 12px; color: #1e40af;">
                                                    Personnel details will be announced soon
                                                </div>
                                            </div>
                                            <?php echo getAssignmentStatusBadge($row['assignment_status']); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="card-status">
                                    <span style="color: #6b7280; font-size: 13px;">
                                        ID: #<?php echo $row['id']; ?>
                                    </span>
                                </div>
                                <button class="btn" onclick="viewFeedback(
                                    <?php echo $row['id']; ?>, 
                                    '<?php echo htmlspecialchars($row['category_name']); ?>', 
                                    '<?php echo $row['rating']; ?>', 
                                    '<?php echo htmlspecialchars(addslashes($row['comment'])); ?>', 
                                    '<?php echo $row['sentiment']; ?>', 
                                    '<?php echo formatDate($row['created_at']); ?>', 
                                    '<?php echo $row['is_resolved']; ?>', 
                                    '<?php echo isset($row['resolved_by']) ? htmlspecialchars(addslashes($row['resolved_by'])) : ''; ?>', 
                                    '<?php echo isset($row['resolved_at']) ? formatDate($row['resolved_at']) : ''; ?>',
                                    '<?php echo $row['personnel_name'] ? htmlspecialchars(addslashes($row['personnel_name'])) : ''; ?>',
                                    '<?php echo $row['personnel_specialization'] ? htmlspecialchars(addslashes($row['personnel_specialization'])) : ''; ?>',
                                    '<?php echo $row['assignment_status'] ? htmlspecialchars($row['assignment_status']) : ''; ?>',
                                    '<?php echo $row['assigned_at'] ? formatDate($row['assigned_at']) : ''; ?>',
                                    '<?php echo $row['started_at'] ? formatDate($row['started_at']) : ''; ?>',
                                    '<?php echo $row['completed_at'] ? formatDate($row['completed_at']) : ''; ?>',
                                    '<?php echo $row['attachment_path'] ? htmlspecialchars($row['attachment_path']) : ''; ?>'
                                )">
                                    <i class="fas fa-eye"></i> <?php echo __('view_details'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i> <?php echo __('previous'); ?>
                            </a>
                        <?php endif; ?>

                        <span class="page-info">
                            <?php echo sprintf(__('page_info'), $page, $total_pages); ?>
                        </span>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="page-link">
                                <?php echo __('next'); ?> <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-comments-slash"></i>
                    <h3><?php echo __('no_feedback_yet'); ?></h3>
                    <p><?php echo __('no_feedback_desc'); ?></p>
                    <a href="index.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> <?php echo __('submit_feedback'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Feedback Details Modal -->
    <div id="feedbackModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2 style="color: #1a317d; margin-bottom: 20px;">
                <i class="fas fa-eye"></i> <?php echo __('view_details'); ?>
            </h2>
            <div class="modal-details" id="modalContent">
                <!-- Dynamic content will be inserted here -->
            </div>
        </div>
    </div>

    <script>
        // DOM Elements
        const sidebar = document.getElementById('sidebar');

        // Translations for JS
        const translations = {
            resolved: "<?php echo __('resolved'); ?>",
            under_review: "<?php echo __('under_review'); ?>",
            resolved_by: "<?php echo __('resolved_by'); ?>",
            resolved_on: "<?php echo __('resolved_on'); ?>",
            status: "<?php echo __('status'); ?>",
            administrator: "<?php echo __('administrator'); ?>"
        };
        const toggleBtn = document.getElementById('toggleBtn');
        const mobileToggleBtn = document.getElementById('mobileToggleBtn');
        const mainContent = document.getElementById('mainContent');
        const menuLinks = document.querySelectorAll('.menu-link');
        const overlay = document.getElementById('overlay');
        const modal = document.getElementById('feedbackModal');

        // Logout Modal Functions
        function openLogoutModal() {
            const logoutModal = document.getElementById('logoutModal');
            logoutModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeLogoutModal() {
            const logoutModal = document.getElementById('logoutModal');
            logoutModal.style.display = 'none';
            document.body.style.overflow = '';
        }

        // Update the existing window click event listener
        window.onclick = function (event) {
            const logoutModal = document.getElementById('logoutModal');
            const feedbackModal = document.getElementById('feedbackModal');

            if (event.target == logoutModal) {
                closeLogoutModal();
            }
            if (event.target == feedbackModal) {
                closeModal();
            }
        }

        // Update the existing Escape key listener
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                const logoutModal = document.getElementById('logoutModal');
                const feedbackModal = document.getElementById('feedbackModal');

                if (logoutModal.style.display === 'flex') {
                    closeLogoutModal();
                }
                if (feedbackModal.style.display === 'flex') {
                    closeModal();
                }
            }
        });

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

        // View Feedback Details
        function viewFeedback(id, category, rating, comment, sentiment, date, isResolved, resolvedBy, resolvedDate,
            personnelName, personnelSpec, assignStatus, assignedDate, startedDate, completedDate, attachmentPath, isUpdatedRating) {
            const content = document.getElementById('modalContent');

            // Create stars for rating
            let stars = '';
            for (let i = 1; i <= 5; i++) {
                if (i <= rating) {
                    stars += '<i class="fas fa-star" style="color: #fbbf24;"></i>';
                } else {
                    stars += '<i class="far fa-star" style="color: #d1d5db;"></i>';
                }
            }

            // Set sentiment badge class
            let sentimentClass = '';
            if (sentiment === 'Positive') {
                sentimentClass = 'sentiment-positive';
            } else if (sentiment === 'Negative') {
                sentimentClass = 'sentiment-negative';
            } else {
                sentimentClass = 'sentiment-neutral';
            }

            // Add resolved status if applicable
            let resolvedStatus = '';
            let isResolvedBool = isResolved === '1' || isResolved === true || isResolved === 'true';

            if (isResolvedBool) {
                let resolverInfo = '';
                if (resolvedBy && resolvedBy.trim() !== '') {
                    resolverInfo = `
                        <div class="detail-row">
                            <div class="detail-label">${translations.resolved_by}:</div>
                            <div class="detail-value">
                                <strong>${resolvedBy}</strong>
                            </div>
                        </div>
                    `;
                } else {
                    resolverInfo = `
                        <div class="detail-row">
                            <div class="detail-label">${translations.resolved_by}:</div>
                            <div class="detail-value">
                                <em>${translations.administrator}</em>
                            </div>
                        </div>
                    `;
                }

                // Add resolution date if available
                let resolutionDateInfo = '';
                if (resolvedDate && resolvedDate.trim() !== '') {
                    resolutionDateInfo = `
                        <div class="detail-row">
                            <div class="detail-label">${translations.resolved_on}:</div>
                            <div class="detail-value">${resolvedDate}</div>
                        </div>
                    `;
                }

                resolvedStatus = `
                    <div class="detail-row">
                        <div class="detail-label">${translations.status}:</div>
                        <div class="detail-value">
                            <span class="status-badge status-resolved">
                                <i class="fas fa-check-circle"></i> ${translations.resolved}
                            </span>
                        </div>
                    </div>
                    ${resolverInfo}
                    ${resolutionDateInfo}
                `;
            } else {
                resolvedStatus = `
                    <div class="detail-row">
                        <div class="detail-label">${translations.status}:</div>
                        <div class="detail-value">
                            <span class="status-badge status-pending">
                                <i class="fas fa-clock"></i> ${translations.under_review}
                            </span>
                        </div>
                    </div>
                `;
            }

            // Add personnel assignment info
            let assignmentInfo = '';
            if (assignStatus === 'In Progress' && (!personnelName || personnelName === '')) {
                assignmentInfo = `
                    <div style="margin-top: 20px; margin-bottom: 20px; padding: 15px; background: #eff6ff; border-radius: 10px; border: 1px solid #93c5fd;">
                        <h4 style="color: #1e40af; margin-bottom: 10px; font-size: 15px; border-bottom: 1px solid #93c5fd; padding-bottom: 5px;">
                            <i class="fas fa-tasks"></i> Issue Assignment
                        </h4>
                        <div class="detail-row">
                            <div class="detail-label">Assigned To:</div>
                            <div class="detail-value">
                                <span style="font-weight: 600; color: #1e40af;">
                                    <i class="fas fa-spinner fa-spin"></i> TBA (To Be Announced)
                                </span>
                                <span style="display: block; font-size: 12px; color: #3b82f6; margin-top: 3px;">Personnel details will be announced soon</span>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Status:</div>
                            <div class="detail-value"><span class="status-badge" style="background: #dbeafe; color: #1e40af;"><i class="fas fa-spinner fa-spin"></i> In Progress</span></div>
                        </div>
                    </div>
                `;
            } else if (personnelName && personnelName !== '') {
                // Determine status badge
                let statusBadge = '';
                if (assignStatus === 'Pending') {
                    statusBadge = '<span class="status-badge status-pending" style="background: #fef3c7; color: #92400e;"><i class="fas fa-clock"></i> Pending</span>';
                } else if (assignStatus === 'In Progress') {
                    statusBadge = '<span class="status-badge" style="background: #dbeafe; color: #1e40af;"><i class="fas fa-spinner fa-spin"></i> In Progress</span>';
                } else if (assignStatus === 'Resolved') {
                    statusBadge = '<span class="status-badge status-resolved"><i class="fas fa-check-circle"></i> Resolved</span>';
                } else {
                    statusBadge = `<span class="status-badge">${assignStatus}</span>`;
                }

                let timeline = '';
                if (assignedDate) timeline += `<div style="margin-top: 5px;"><small>Assigned: ${assignedDate}</small></div>`;
                if (startedDate) timeline += `<div><small>Started: ${startedDate}</small></div>`;
                if (completedDate) timeline += `<div><small>Completed: ${completedDate}</small></div>`;

                assignmentInfo = `
                    <div style="margin-top: 20px; margin-bottom: 20px; padding: 15px; background: #f0f7ff; border-radius: 10px; border: 1px solid #bae6fd;">
                        <h4 style="color: #1a317d; margin-bottom: 10px; font-size: 15px; border-bottom: 1px solid #bae6fd; padding-bottom: 5px;">
                            <i class="fas fa-tasks"></i> Issue Assignment
                        </h4>
                        <div class="detail-row">
                            <div class="detail-label">Assigned To:</div>
                            <div class="detail-value">
                                <strong>${personnelName}</strong>
                                <span style="display: block; font-size: 12px; color: #6b7280;">${personnelSpec}</span>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Status:</div>
                            <div class="detail-value">${statusBadge}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Timeline:</div>
                            <div class="detail-value" style="color: #4b5563;">
                                ${timeline}
                            </div>
                        </div>
                    </div>
                `;
            }

            // Add edit rating section - ONLY if resolved and not yet updated
            let editRatingSection = '';

            let canRate = isResolvedBool || sentiment === 'Positive';

            if (canRate && parseInt(isUpdatedRating) !== 1) {
                editRatingSection = `
                <div class="detail-row">
                    <div class="detail-label">Update Rating:</div>
                    <div class="detail-value">
                        <div style="margin-bottom: 10px;">
                            <div class="rating-edit" style="font-size: 20px; color: #d1d5db; margin-bottom: 10px;">
                                <i class="far fa-star" data-value="1" onclick="updateStarRating(1)"></i>
                                <i class="far fa-star" data-value="2" onclick="updateStarRating(2)"></i>
                                <i class="far fa-star" data-value="3" onclick="updateStarRating(3)"></i>
                                <i class="far fa-star" data-value="4" onclick="updateStarRating(4)"></i>
                                <i class="far fa-star" data-value="5" onclick="updateStarRating(5)"></i>
                            </div>
                            <input type="hidden" id="newRating" value="${rating}">
                            <input type="hidden" id="feedbackId" value="${id}">
                            <button class="btn" style="margin-top: 10px;" onclick="saveRating()">
                                <i class="fas fa-save"></i> Update Rating
                            </button>
                            <div id="rateStatusText" style="margin-top: 5px; color: #10b981; font-size: 13px; font-weight: 500;">
                                <i class="fas fa-check-circle"></i> Please rate this feedback.
                            </div>
                            <span id="ratingSuccess" style="display: none; color: #1F3A93; margin-left: 10px;">
                                <i class="fas fa-check"></i> Rating updated!
                            </span>
                        </div>
                    </div>
                </div>
                `;
            }

            content.innerHTML = `
                <div class="detail-row">
                    <div class="detail-label">Feedback ID:</div>
                    <div class="detail-value">#${id}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Category:</div>
                    <div class="detail-value">${category}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Current Rating:</div>
                    <div class="detail-value">
                        ${stars} (${rating}/5)
                    </div>
                </div>

                ${assignmentInfo}
                ${resolvedStatus}
                <div class="detail-row">
                    <div class="detail-label">Date Submitted:</div>
                    <div class="detail-value">${date}</div>
                </div>
                ${editRatingSection}
                <div class="detail-row">
                    <div class="detail-label">Feedback:</div>
                    <div class="detail-value" style="white-space: pre-wrap; line-height: 1.6; padding: 15px; background: #f9fafb; border-radius: 8px; border-left: 4px solid #1F3A93;">
                        ${comment}
                    </div>
                </div>
                
                ${attachmentPath ? `
                <div class="detail-row">
                    <div class="detail-label">Attachment:</div>
                    <div class="detail-value">
                        <img src="../${attachmentPath}" alt="Attachment" style="max-width: 100%; border-radius: 8px; border: 1px solid #e5e7eb; margin-top: 5px;">
                    </div>
                </div>
                ` : ''}
                <div style="text-align: center; margin-top: 30px;">
                    <button class="btn" onclick="closeModal()">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            `;

            // Initialize star rating for editing
            initializeStarRating(rating);

            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        // Initialize star rating display for editing
        function initializeStarRating(rating) {
            const stars = document.querySelectorAll('.rating-edit i');
            stars.forEach(star => {
                const value = parseInt(star.getAttribute('data-value'));
                if (value <= rating) {
                    star.classList.remove('far');
                    star.classList.add('fas');
                    star.style.color = '#fbbf24';
                }
            });
        }

        // Update star rating display when clicking stars
        function updateStarRating(rating) {
            const stars = document.querySelectorAll('.rating-edit i');
            stars.forEach(star => {
                const value = parseInt(star.getAttribute('data-value'));
                if (value <= rating) {
                    star.classList.remove('far');
                    star.classList.add('fas');
                    star.style.color = '#fbbf24';
                } else {
                    star.classList.remove('fas');
                    star.classList.add('far');
                    star.style.color = '#d1d5db';
                }
            });
            document.getElementById('newRating').value = rating;
        }

        // Save updated rating via AJAX
        function saveRating() {
            const feedbackId = document.getElementById('feedbackId').value;
            const newRating = document.getElementById('newRating').value;

            // Send AJAX request to update rating
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_rating.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onload = function () {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Show success message
                        document.getElementById('ratingSuccess').style.display = 'inline';

                        // Update the displayed rating in modal
                        const ratingElement = document.querySelector('.detail-row:nth-child(3) .detail-value');
                        let stars = '';
                        for (let i = 1; i <= 5; i++) {
                            if (i <= newRating) {
                                stars += '<i class="fas fa-star" style="color: #fbbf24;"></i>';
                            } else {
                                stars += '<i class="far fa-star" style="color: #d1d5db;"></i>';
                            }
                        }
                        ratingElement.innerHTML = `${stars} (${newRating}/5)`;

                        // Update the list view ratings dynamically
                        const desktopContainer = document.getElementById('rating-container-desktop-' + feedbackId);
                        const mobileContainer = document.getElementById('rating-container-mobile-' + feedbackId);

                        // Keep the button but change its text instead of replacing with just "Rated"
                        const updatedHTML = stars + `
                        <button class="btn btn-success"
                            style="font-size: 11px; padding: 4px 8px; margin-top: 5px; width: 100%; white-space: normal; text-align: center;"
                            onclick="document.querySelector('#rating-container-desktop-${feedbackId} button, #rating-container-mobile-${feedbackId} button').click()">
                            <i class="fas fa-star"></i> Update Rating
                        </button>
                        `;

                        if (desktopContainer) {
                            // Only update the stars, keep the button as it binds to viewFeedback
                            const button = desktopContainer.querySelector('button');
                            if (button) {
                                button.innerHTML = '<i class="fas fa-star"></i> Update Rating';
                                desktopContainer.innerHTML = stars;
                                desktopContainer.appendChild(button);
                            } else {
                                desktopContainer.innerHTML = updatedHTML;
                            }
                        }

                        if (mobileContainer) {
                            const button = mobileContainer.querySelector('button');
                            if (button) {
                                button.innerHTML = '<i class="fas fa-star"></i> Update Rating';
                                mobileContainer.innerHTML = stars;
                                mobileContainer.appendChild(button);
                            } else {
                                mobileContainer.innerHTML = updatedHTML;
                            }
                        }

                        // Update status text in modal
                        const statusElement = document.getElementById('rateStatusText');
                        if (statusElement) {
                            statusElement.innerHTML = '<i class="fas fa-check-circle"></i> Rated.';
                        }

                        // Hide success message after 3 seconds
                        setTimeout(() => {
                            document.getElementById('ratingSuccess').style.display = 'none';
                        }, 3000);
                    } else {
                        alert('Error updating rating: ' + response.message);
                    }
                }
            };

            xhr.send(`feedback_id=${feedbackId}&rating=${newRating}`);
        }

        function closeModal() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            if (event.target == modal) {
                closeModal();
            }
        }

        // Set current date
        const now = new Date();
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };
        document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', options);

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