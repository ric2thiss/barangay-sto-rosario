<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireUser();

// Add maintenance mode check (moved to the top)
checkMaintenanceMode();

// Add this after line 7 (after requireUser())
// Fetch user data from database
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT id, first_name as firstname, surname as lastname, user_role as user_type, NULL as image_path FROM `profiling-system`.residents WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Check if we're opening the modal
$category_id = $_GET['category'] ?? 0;
$show_modal = isset($_GET['modal']) && $_GET['modal'] == 'feedback' && $category_id > 0;

// Fetch category details if modal is requested
if ($show_modal) {
    $sql = "SELECT * FROM categories WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $category_result = $stmt->get_result();
    $category = $category_result->fetch_assoc();

    if (!$category) {
        header('Location: index.php');
        exit();
    }
}

// Fetch unrated feedback count to enforce maximum 3 limit
$unrated_sql = "SELECT COUNT(*) as count FROM feedback WHERE user_id = ? AND is_resolved = 1 AND rating = 0";
$unrated_stmt = $conn->prepare($unrated_sql);
$unrated_stmt->bind_param("i", $user_id);
$unrated_stmt->execute();
$unrated_result = $unrated_stmt->get_result()->fetch_assoc();
$unrated_count = $unrated_result['count'];
$can_submit_feedback = ($unrated_count < 3);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    if (!$can_submit_feedback) {
        $_SESSION['error_message'] = "You cannot submit new feedback. You have $unrated_count unresolved ratings required. Please rate them first.";
        header('Location: my_feedback.php');
        exit();
    }

    if (!isset($_POST['data_privacy_feedback'])) {
        $_SESSION['error_message'] = "You must agree to the Data Privacy Consent to submit feedback.";
        header('Location: index.php');
        exit();
    }

    $category_id = $_POST['category_id'];
    $rating = 0; // Default to 0 (unrated)
    $comment = $_POST['comment'];
    $user_id = $_SESSION['user_id'];

    // Analyze sentiment
    $sentiment = analyzeSentiment($comment);

    // Handle file upload
    $attachment_path = null;
    $upload_result = handleFileUpload($_FILES['attachment']);

    if ($upload_result['success']) {
        $attachment_path = $upload_result['path'];
    } elseif ($upload_result['message'] !== 'No file uploaded') {
        // Handle upload error (optional: display error)
        // For now, we'll continue but log it or maybe just ignore if it's not critical
        // But user might want to know why image wasn't attached
        // Let's set error
        // $error = $upload_result['message']; // Can't easily pass this back if redirecting
    }

    // Insert feedback
    $sql = "INSERT INTO feedback (user_id, category_id, rating, comment, sentiment, attachment_path) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiisss", $user_id, $category_id, $rating, $comment, $sentiment, $attachment_path);

    if ($stmt->execute()) {
        $feedback_id = $conn->insert_id;

        // Auto-assign personnel for negative feedback
        $assigned = autoAssignPersonnel($conn, $feedback_id, $category_id, $rating, $sentiment);

        if ($assigned) {
            $_SESSION['success_message'] = "Feedback submitted successfully! A specialist has been automatically assigned to address your concern.";
        } else {
            $_SESSION['success_message'] = "Feedback submitted successfully! Sentiment detected: $sentiment";
        }

        header('Location: index.php');
        exit();
    } else {
        $error = "Error submitting feedback. Please try again.";
    }
}

// Get user stats for dashboard
$user_id = $_SESSION['user_id'];
$stats_sql = "SELECT 
                COUNT(*) as total,
                AVG(rating) as avg_rating,
                SUM(CASE WHEN sentiment = 'Positive' THEN 1 ELSE 0 END) as positive,
                SUM(CASE WHEN sentiment = 'Negative' THEN 1 ELSE 0 END) as negative
              FROM feedback 
              WHERE user_id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Get recent feedback with assignment details
$recent_sql = "SELECT f.*, c.name as category_name,
                fa.id as assignment_id, fa.status as assignment_status,
                fa.assigned_at, fa.started_at, fa.completed_at,
                p.name as personnel_name, p.star_rating as personnel_rating
                FROM feedback f 
                JOIN categories c ON f.category_id = c.id 
                LEFT JOIN feedback_assignments fa ON f.id = fa.feedback_id
                LEFT JOIN personnel p ON fa.personnel_id = p.id
                WHERE f.user_id = ? 
                ORDER BY f.created_at DESC 
                LIMIT 5";
$recent_stmt = $conn->prepare($recent_sql);
$recent_stmt->bind_param("i", $user_id);
$recent_stmt->execute();
$recent_result = $recent_stmt->get_result();

// Get all categories
$categories_sql = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Resident Feedback and Survey System</title>
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

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, #2c3e50 0%, #4a235a 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .welcome-section h1 {
            font-size: 32px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .welcome-section .subtitle {
            font-size: 16px;
            opacity: 0.9;
            max-width: 600px;
        }

        /* Categories Grid */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .category-card {
            background: var(--card-light);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            text-decoration: none;
            color: var(--text-light);
            display: block;
            transition: all 0.3s;
            border: 1px solid var(--border-light);
            text-align: center;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(31, 58, 147, 0.1);
            border-color: #1F3A93;
        }

        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #1F3A93, #3a56b5);
        }

        .category-icon {
            font-size: 48px;
            color: #1F3A93;
            margin-bottom: 20px;
            background: #f0f7ff;
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border: 1px solid #bae6fd;
        }

        .category-card h3 {
            color: #1a317d;
            font-size: 20px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .category-card p {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .category-card .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(90deg, #1F3A93, #3a56b5);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }

        .category-card .btn:hover {
            background: linear-gradient(90deg, #1a317d, #1F3A93);
            transform: translateY(-2px);
        }

        /* Dashboard Cards */
        .dashboard-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            margin-bottom: 30px;
            border: 1px solid #e5e7eb;
        }

        .dashboard-card h2 {
            margin-bottom: 20px;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: white;
            color: #1a317d;
            padding: 25px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            transition: all 0.3s;
            text-decoration: none;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(31, 58, 147, 0.1);
            border-color: #1F3A93;
        }

        .stat-icon {
            font-size: 32px;
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
            font-size: 32px;
            font-weight: 700;
            color: #1a317d;
            line-height: 1;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #6b7280;
            font-size: 14px;
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
            border-bottom: 1px solid #e5e7eb;
        }

        table th {
            font-weight: 600;
        }

        /* table tr:hover removed */

        .rating-stars {
            color: #fbbf24;
        }

        .sentiment-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .sentiment-positive {
            /* background: #bae6fd; */
            /* color: #1a317d; */
        }

        /* Status badges styles removed - handled by theme.css */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
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
            font-size: 14px;
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

        .btn-secondary {
            background: #6b7280;
        }

        .btn-secondary:hover {
            background: #4b5563;
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

        .alert-error {
            background: #fee2e2;
            border-color: #fecaca;
            color: #991b1b;
        }

        .alert i {
            color: #1F3A93;
        }

        .alert-error i {
            color: #ef4444;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }

        .empty-state i {
            font-size: 64px;
            color: #bae6fd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #6b7280;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #9ca3af;
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

        /* Feedback Modal */
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
            max-width: 600px;
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

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: #4b5563;
            font-size: 14px;
        }

        .rating-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .rating-stars-input {
            display: flex;
            gap: 5px;
            justify-content: center;
            margin-bottom: 10px;
        }

        .rating-stars-input input {
            display: none;
        }

        .rating-stars-input label {
            font-size: 32px;
            color: #d1d5db;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }

        .rating-stars-input label:hover,
        .rating-stars-input label:hover~label {
            color: #fbbf24;
        }

        .rating-stars-input input:checked~label {
            color: #fbbf24;
        }

        .rating-label {
            text-align: center;
            color: #6b7280;
            font-size: 13px;
            min-height: 20px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            background: white;
            resize: vertical;
        }

        .form-control:focus {
            outline: none;
            border-color: #1F3A93;
            box-shadow: 0 0 0 3px rgba(31, 58, 147, 0.1);
        }

        .char-counter {
            display: block;
            text-align: right;
            margin-top: 5px;
            font-size: 12px;
            color: #6b7280;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .guidelines {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }

        .guidelines h3 {
            color: #1a317d;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .guidelines ul {
            padding-left: 20px;
            color: #6b7280;
        }

        .guidelines li {
            margin-bottom: 8px;
            line-height: 1.5;
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

            .categories-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }

            table {
                display: block;
                overflow-x: auto;
            }

            .toggle-btn {
                display: flex !important;
            }

            .modal-content {
                width: 95%;
                padding: 15px;
            }

            .modal-header {
                padding: 20px;
            }

            .modal-body {
                padding: 20px;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions .btn {
                width: 100%;
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
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        /* Category Dropdown */
        .category-select-wrapper {
            position: relative;
        }

        .category-select-wrapper select {
            width: 100%;
            padding: 14px 18px;
            padding-right: 42px;
            border: 2px solid #bae6fd;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 500;
            color: #1a317d;
            background-color: #f0f7ff;
            appearance: none;
            -webkit-appearance: none;
            cursor: pointer;
            outline: none;
            transition: border-color 0.25s, box-shadow 0.25s;
        }

        .category-select-wrapper select:focus {
            border-color: #1F3A93;
            box-shadow: 0 0 0 3px rgba(31, 58, 147, 0.12);
            background-color: #fff;
        }

        .category-select-wrapper::after {
            content: "\f078";
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #1F3A93;
            pointer-events: none;
            font-size: 13px;
        }

        /* Feedback fields reveal animation */
        #feedbackFields {
            overflow: hidden;
            max-height: 0;
            opacity: 0;
            transition: max-height 0.45s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.35s ease;
        }

        #feedbackFields.revealed {
            max-height: 1000px;
            opacity: 1;
        }

        .category-selected-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f0f7ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 10px 14px;
            margin-top: 10px;
            font-size: 13px;
            color: #1a317d;
            font-weight: 600;
        }

        .category-selected-badge i {
            color: #1F3A93;
        }

        /* Simple responsive fix for existing table */
        @media (max-width: 768px) {
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                background: white;
            }

            table {
                min-width: 600px;
                /* Force horizontal scroll on small screens */
                margin-bottom: 0;
            }

            /* Make stats cards stack vertically on mobile */
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .stat-card {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }

            .stat-icon {
                margin-bottom: 15px;
            }

            .stat-info h3 {
                font-size: 28px;
            }
        }

        /* For very small screens */
        @media (max-width: 480px) {
            table {
                min-width: 500px;
                font-size: 13px;
            }

            table th,
            table td {
                padding: 10px 8px;
            }

            .sentiment-badge {
                font-size: 11px;
                padding: 3px 8px;
            }
        }

        /* Add to your CSS, inside the @media (max-width: 768px) section */
        @media (max-width: 768px) {
            /* ... existing mobile styles ... */

            /* Fix for recent feedback table on mobile */
            .feedback-list {
                display: flex;
                flex-direction: column;
                gap: 15px;
            }

            .feedback-item {
                background: white;
                border-radius: 12px;
                padding: 20px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
                border: 1px solid #e5e7eb;
            }

            .feedback-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
                padding-bottom: 15px;
                border-bottom: 1px solid #f3f4f6;
            }

            .feedback-category {
                font-weight: 600;
                color: #1a317d;
                font-size: 16px;
            }

            .feedback-date {
                color: #6b7280;
                font-size: 14px;
                background: #f9fafb;
                padding: 4px 10px;
                border-radius: 6px;
            }

            .feedback-content {
                margin-bottom: 15px;
                line-height: 1.6;
            }

            .feedback-footer {
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 10px;
            }

            .feedback-rating {
                color: #fbbf24;
                font-size: 16px;
            }

            /* Hide table on mobile, show card layout */
            table {
                display: none;
            }

            .feedback-list {
                display: block;
            }

            /* Show table on desktop, hide card layout */
            @media (min-width: 769px) {
                table {
                    display: table;
                }

                .feedback-list {
                    display: none;
                }
            }

            /* Fix for stats grid on smaller mobile screens */
            @media (max-width: 480px) {
                .stats-grid {
                    grid-template-columns: 1fr;
                }

                .stat-card {
                    flex-direction: column;
                    text-align: center;
                    padding: 20px;
                }

                .stat-icon {
                    margin-bottom: 15px;
                }

                .stat-info h3 {
                    font-size: 28px;
                }
            }
        }

        /* Add this to your main CSS (not inside media query) */
        .feedback-list {
            display: none;
        }

        /* Make table more responsive */
        table {
            min-width: 600px;
            /* Minimum width before scrolling */
        }

        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            margin-top: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            background: white;
        }

        /* Better table responsiveness */
        @media (max-width: 1024px) {
            table {
                font-size: 14px;
            }

            table th,
            table td {
                padding: 12px 10px;
            }
        }

        /* Stats card responsiveness improvements */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .stat-card {
                padding: 20px;
            }

            .stat-info h3 {
                font-size: 28px;
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
                <h2><i class="fas fa-sign-out-alt"></i> Confirm Logout</h2>
                <button class="close-modal" onclick="closeLogoutModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="logout-modal-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>

                <h3 class="logout-modal-title">Ready to Leave?</h3>

                <p class="logout-modal-message">
                    Are you sure you want to logout from your account?<br>
                    You'll need to sign in again to access your dashboard and feedback.
                </p>

                <div class="logout-modal-actions">
                    <a href="logout.php" class="btn btn-success">
                        <i class="fas fa-sign-out-alt"></i> Yes, Logout
                    </a>
                    <button type="button" class="btn btn-secondary" onclick="closeLogoutModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>

                <div style="text-align: center; margin-top: 25px;">
                    <p style="color: #9ca3af; font-size: 12px;">
                        <i class="fas fa-info-circle"></i> You can always log back in with your credentials
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
            <!-- <li class="menu-item">
                <a href="#" onclick="openFeedbackModal(0)" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'feedback_form.php' ? '' : ''; ?>" data-tooltip="Submit Feedback">
                    <i class="fas fa-plus-circle menu-icon"></i>
                    <span class="menu-text">Submit Feedback</span>
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
                <i class="fas fa-tachometer-alt title-icon"></i>
                <?php echo __('user_dashboard'); ?>
            </h1>
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

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $_SESSION['error_message'];
                unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1><?php echo __('welcome_back'); ?>,
                <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>!
            </h1>
            <p class="subtitle"><?php echo __('dashboard_subtitle'); ?></p>
        </div>

        <!-- Submit Feedback CTA -->
        <div class="dashboard-card"
            style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px; padding: 28px 30px;">
            <div style="display: flex; align-items: center; gap: 18px;">
                <div
                    style="width: 54px; height: 54px; background: linear-gradient(135deg, #1F3A93, #3a56b5); border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 6px 16px rgba(31,58,147,0.25);">
                    <i class="fas fa-paper-plane" style="color: #fff; font-size: 22px;"></i>
                </div>
                <div>
                    <h2 style="font-size: 18px; margin-bottom: 4px;"><?php echo __('submit_feedback'); ?></h2>
                    <p style="color: #6b7280; font-size: 13px; margin: 0;"><?php echo __('choose_category_desc'); ?></p>
                </div>
            </div>
            <button class="btn btn-success" onclick="openFeedbackModal(0)"
                style="padding: 12px 28px; font-size: 15px; border-radius: 10px; white-space: nowrap;">
                <i class="fas fa-plus"></i> <?php echo __('submit_feedback'); ?>
            </button>
        </div>


    </main>

    <!-- Feedback Modal -->
    <div id="feedbackModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"><i class="fas fa-comments"></i> Submit Feedback</h2>
                <button class="close-modal" onclick="closeFeedbackModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="feedbackForm" method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" id="category_id" name="category_id" value="">
                    <input type="hidden" name="rating" value="0">

                    <!-- Step 1: Category Dropdown -->
                    <div class="form-group">
                        <label class="form-label" for="categorySelect">
                            <i class="fas fa-folder-open" style="color:#1F3A93; margin-right:6px;"></i>
                            Select Category
                        </label>
                        <div class="category-select-wrapper">
                            <select id="categorySelect" onchange="onCategorySelect(this)" required>
                                <option value="" disabled selected>— Choose a category —</option>
                                <?php
                                // Re-use the categories already fetched
                                $categories_result->data_seek(0);
                                while ($cat = $categories_result->fetch_assoc()):
                                    $cat_icon = getCategoryIcon($cat['icon']);
                                    ?>
                                    <option value="<?php echo $cat['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($cat['name']); ?>"
                                        data-desc="<?php echo htmlspecialchars($cat['description']); ?>"
                                        data-icon="<?php echo $cat_icon; ?>">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <!-- Selected category badge (shown after selection) -->
                        <div id="categoryBadge" class="category-selected-badge" style="display:none;">
                            <i id="categoryBadgeIcon" class="fas fa-tag"></i>
                            <span id="categoryBadgeName"></span>
                            <span style="color:#6b7280; font-weight:400; font-size:12px;" id="categoryBadgeDesc"></span>
                        </div>
                    </div>

                    <!-- Step 2: Fields (hidden until category selected) -->
                    <div id="feedbackFields">
                        <div class="form-group">
                            <label class="form-label" for="comment">Your Feedback / Suggestion:</label>
                            <textarea class="form-control" id="comment" name="comment"
                                placeholder="Please provide detailed feedback about the service. The system will automatically analyze the sentiment of your feedback..."
                                rows="5" required></textarea>
                            <small class="char-counter" id="charCounter">0 characters</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="attachment">Attach Image (Optional)</label>
                            <input type="file" class="form-control" id="attachment" name="attachment" accept="image/*">
                            <small style="color: #6b7280; font-size: 12px;">Supported formats: JPG, PNG, GIF. Max size:
                                5MB.</small>
                        </div>

                        <div class="guidelines">
                            <h3><i class="fas fa-lightbulb"></i> Tips for Good Feedback:</h3>
                            <ul>
                                <li>Be specific about what you like or dislike</li>
                                <li>Provide constructive suggestions for improvement</li>
                                <li>Mention location and time if relevant</li>
                                <li>Keep your feedback respectful and helpful</li>
                            </ul>
                        </div>

                        <div class="form-group"
                            style="display: flex; align-items: flex-start; gap: 10px; margin-top: 20px; background: #f0f7ff; padding: 15px; border-radius: 10px; border: 1px solid #bae6fd;">
                            <input type="checkbox" id="data_privacy_feedback" name="data_privacy_feedback" required
                                style="margin-top: 5px; cursor: pointer;">
                            <label for="data_privacy_feedback"
                                style="font-size: 0.85rem; color: #1a317d; line-height: 1.5; cursor: pointer; text-align: justify; flex: 1;">
                                I consent to the collection and processing of my data under the <strong>Data Privacy Act
                                    of 2012</strong>.
                                <span class="required" style="color: #ef4444;">*</span>
                            </label>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="submit_feedback" class="btn btn-success">
                                <i class="fas fa-paper-plane"></i> Submit Feedback
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="closeFeedbackModal()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Rating Limit Modal -->
    <div id="limitModal" class="modal">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header" style="background: #fee2e2; border-bottom: 1px solid #fecaca;">
                <h2 style="color: #991b1b;"><i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i> Limit
                    Reached</h2>
                <button class="close-modal" onclick="closeLimitModal()"
                    style="background: white; border: 1px solid #fecaca; color: #ef4444;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" style="text-align: center; padding: 40px 30px;">
                <div style="font-size: 60px; color: #ef4444; margin-bottom: 20px;">
                    <i class="fas fa-ban"></i>
                </div>
                <h3 style="color: #1a317d; margin-bottom: 15px; font-size: 20px;">Cannot Submit New Feedback</h3>
                <p style="color: #4b5563; margin-bottom: 25px; line-height: 1.6;">
                    You have <strong id="unratedCountDisplay" style="color: #ef4444; font-size: 18px;"></strong>
                    previous feedback(s) that are resolved but unrated.
                    <br><br>
                    Please rate your previous feedbacks before submitting a new one.
                </p>
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <button type="button" class="btn btn-secondary" onclick="closeLimitModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <a href="my_feedback.php" class="btn btn-success"
                        style="background: linear-gradient(90deg, #10b981, #059669);">
                        <i class="fas fa-star"></i> Rate Now
                    </a>
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
        const overlay = document.getElementById('overlay');
        const feedbackModal = document.getElementById('feedbackModal');
        const commentTextarea = document.getElementById('comment');
        const charCounter = document.getElementById('charCounter');

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

        // Helper: reveal / hide the feedback fields
        function revealFeedbackFields(show) {
            const fields = document.getElementById('feedbackFields');
            if (show) {
                fields.classList.add('revealed');
            } else {
                fields.classList.remove('revealed');
            }
        }

        // Called when user picks a category from the dropdown
        function onCategorySelect(selectEl) {
            const opt = selectEl.options[selectEl.selectedIndex];
            const id = opt.value;
            const name = opt.dataset.name;
            const desc = opt.dataset.desc;
            const icon = opt.dataset.icon;

            document.getElementById('category_id').value = id;

            // Update badge
            document.getElementById('categoryBadgeIcon').className = icon;
            document.getElementById('categoryBadgeName').textContent = name;
            document.getElementById('categoryBadgeDesc').textContent = desc;
            document.getElementById('categoryBadge').style.display = 'flex';

            // Update modal title
            document.getElementById('modalTitle').innerHTML = `<i class="${icon}"></i> Submit Feedback: ${name}`;

            // Reveal the form fields
            revealFeedbackFields(true);

            // Focus textarea after animation
            setTimeout(() => { commentTextarea.focus(); }, 460);
        }

        // Feedback Modal Functions
        function openFeedbackModal(categoryId, categoryName = '', categoryDescription = '', categoryIcon = '') {
            const unratedCount = <?php echo $unrated_count; ?>;
            const maxUnrated = 3;

            if (unratedCount >= maxUnrated) {
                document.getElementById('unratedCountDisplay').textContent = unratedCount;
                openLimitModal();
                return;
            }

            const modal = document.getElementById('feedbackModal');
            const categoryIdInput = document.getElementById('category_id');
            const modalTitle = document.getElementById('modalTitle');
            const categorySelect = document.getElementById('categorySelect');

            // Reset form
            document.getElementById('feedbackForm').reset();
            updateCharCounter();

            // Hide badge & fields by default
            document.getElementById('categoryBadge').style.display = 'none';
            revealFeedbackFields(false);

            if (categoryId > 0 && categoryName) {
                // Pre-select the dropdown option matching this category
                for (let i = 0; i < categorySelect.options.length; i++) {
                    if (categorySelect.options[i].value == categoryId) {
                        categorySelect.selectedIndex = i;
                        break;
                    }
                }

                // Set hidden input and badge
                categoryIdInput.value = categoryId;
                document.getElementById('categoryBadgeIcon').className = categoryIcon;
                document.getElementById('categoryBadgeName').textContent = categoryName;
                document.getElementById('categoryBadgeDesc').textContent = categoryDescription;
                document.getElementById('categoryBadge').style.display = 'flex';

                modalTitle.innerHTML = `<i class="${categoryIcon}"></i> Submit Feedback: ${categoryName}`;

                // Show fields immediately
                revealFeedbackFields(true);

                // Focus textarea
                setTimeout(() => { commentTextarea.focus(); }, 300);
            } else {
                // Generic open: reset dropdown, hide fields
                categorySelect.selectedIndex = 0;
                categoryIdInput.value = '';
                modalTitle.innerHTML = '<i class="fas fa-comments"></i> Submit Feedback';
            }

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeFeedbackModal() {
            feedbackModal.classList.remove('active');
            document.body.style.overflow = '';
        }

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

        // Limit Modal Functions
        function openLimitModal() {
            const limitModal = document.getElementById('limitModal');
            limitModal.classList.add('active');
            limitModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeLimitModal() {
            const limitModal = document.getElementById('limitModal');
            limitModal.classList.remove('active');
            limitModal.style.display = 'none';
            document.body.style.overflow = '';
        }

        // Update the existing window click event listener
        window.onclick = function (event) {
            const logoutModal = document.getElementById('logoutModal');
            const feedbackModal = document.getElementById('feedbackModal');
            const limitModal = document.getElementById('limitModal');

            if (event.target == logoutModal) {
                closeLogoutModal();
            }
            if (event.target == feedbackModal) {
                closeModal(); // Not sure where this is defined, may mean closeFeedbackModal() but let's just keep original calling code
            }
            if (event.target == limitModal) {
                closeLimitModal();
            }
        }

        // Update the existing Escape key listener
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                const logoutModal = document.getElementById('logoutModal');
                const feedbackModal = document.getElementById('feedbackModal');
                const limitModal = document.getElementById('limitModal');

                if (logoutModal && logoutModal.style.display === 'flex') {
                    closeLogoutModal();
                }
                if (feedbackModal && feedbackModal.classList.contains('active')) {
                    closeFeedbackModal();
                }
                if (limitModal && limitModal.classList.contains('active')) {
                    closeLimitModal();
                }
            }
        });

        // Rating functionality removed from initial submission
        // const ratingInputs = document.querySelectorAll('input[name="rating"]');
        // const ratingLabel = document.getElementById('ratingLabel');

        // ratingInputs.forEach(input => {
        //     input.addEventListener('change', function() {
        //         const rating = this.value;
        //         const labels = {
        //             '1': 'Very Poor',
        //             '2': 'Poor',
        //             '3': 'Average',
        //             '4': 'Good',
        //             '5': 'Excellent'
        //         };
        //         ratingLabel.textContent = `${rating}/5 - ${labels[rating]}`;
        //     });
        // });

        // Character counter
        function updateCharCounter() {
            const length = commentTextarea.value.length;
            charCounter.textContent = `${length} characters`;

            if (length < 10) {
                charCounter.style.color = '#dc3545';
            } else if (length < 50) {
                charCounter.style.color = '#ffc107';
            } else {
                charCounter.style.color = '#1a317d';
            }
        }

        commentTextarea.addEventListener('input', updateCharCounter);

        // Auto-expand textarea
        commentTextarea.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Form validation
        document.getElementById('feedbackForm').addEventListener('submit', function (e) {
            if (commentTextarea.value.length < 10) {
                e.preventDefault();
                alert('Please enter at least 10 characters for your feedback.');
                commentTextarea.focus();
                return false;
            }

            // Rating validation removed since it's now optional/hidden
            // if (!document.querySelector('input[name="rating"]:checked')) {
            //     e.preventDefault();
            //     alert('Please select a rating.');
            //     return false;
            // }

            if (!document.getElementById('category_id').value) {
                e.preventDefault();
                alert('Please select a category from the dashboard first.');
                return false;
            }

            return true;
        });

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

        // Close modal when clicking outside
        window.addEventListener('click', function (e) {
            if (e.target === feedbackModal) {
                closeFeedbackModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && feedbackModal.classList.contains('active')) {
                closeFeedbackModal();
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

        // Auto-open modal if category is specified in URL
        <?php if ($show_modal && $category): ?>
            window.addEventListener('load', function () {
                setTimeout(() => {
                    openFeedbackModal(
                        <?php echo $category['id']; ?>,
                        '<?php echo htmlspecialchars($category['name']); ?>',
                        '<?php echo htmlspecialchars($category['description']); ?>',
                        '<?php echo getCategoryIcon($category['icon']); ?>'
                    );
                }, 500);
            });
        <?php endif; ?>

        // Handle responsive table/card switching
        function handleResponsiveLayout() {
            const tableContainer = document.querySelector('.table-container');
            const feedbackList = document.querySelector('.feedback-list');

            if (window.innerWidth <= 768) {
                if (tableContainer) tableContainer.style.display = 'none';
                if (feedbackList) feedbackList.style.display = 'block';
            } else {
                if (tableContainer) tableContainer.style.display = 'block';
                if (feedbackList) feedbackList.style.display = 'none';
            }
        }

        // Update existing resize handler
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

            // Handle responsive layout for tables/cards
            handleResponsiveLayout();
        }

        // Call it on initial load
        window.addEventListener('load', function () {
            handleResponsiveLayout();

            // Also call it when the page fully loads
            setTimeout(handleResponsiveLayout, 100);
        });
    </script>
    <script src="../js/theme.js"></script>
</body>

</html>