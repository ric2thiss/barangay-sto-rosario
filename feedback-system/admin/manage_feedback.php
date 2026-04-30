<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/config.php';
require_once '../includes/functions.php';
requireAdmin();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check database connection
if (!$conn || $conn->connect_error) {
    die("Database connection failed: " . ($conn ? $conn->connect_error : "No connection"));
}

// Filter parameters with sanitization
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$sentiment_filter = isset($_GET['sentiment']) ? trim($_GET['sentiment']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get all personnel for the list view
$personnel_list = getAllPersonnel($conn);

// Mark as resolved - now for all feedback types
if (isset($_POST['resolve_feedback']) && isset($_POST['resolve_id']) && is_numeric($_POST['resolve_id'])) {
    $resolve_id = intval($_POST['resolve_id']);
    $resolved_by = trim($_POST['resolved_by']);
    $resolution_notes = trim($_POST['resolution_notes'] ?? '');

    if (empty($resolved_by)) {
        $_SESSION['warning_message'] = "Please enter the name of the person who handled the issue.";
        header('Location: manage_feedback.php?' . http_build_query($_GET));
        exit();
    }

    try {
        // Get feedback to check if it's already resolved
        $check_sql = "SELECT is_resolved, sentiment FROM feedback WHERE id = ?";
        $check_stmt = $conn->prepare($check_sql);

        if (!$check_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $check_stmt->bind_param("i", $resolve_id);

        if (!$check_stmt->execute()) {
            throw new Exception("Execute failed: " . $check_stmt->error);
        }

        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $feedback = $check_result->fetch_assoc();

            if ($feedback['is_resolved'] == 1) {
                $_SESSION['warning_message'] = "This feedback is already marked as resolved.";
            } else {
                // Update feedback with resolution details
                $resolve_sql = "UPDATE feedback SET 
                                is_resolved = 1, 
                                resolved_by = ?, 
                                resolution_notes = ?,
                                resolved_at = NOW(), 
                                updated_at = NOW() 
                                WHERE id = ?";
                $resolve_stmt = $conn->prepare($resolve_sql);

                if (!$resolve_stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }

                $resolve_stmt->bind_param("ssi", $resolved_by, $resolution_notes, $resolve_id);

                if ($resolve_stmt->execute()) {
                    $sentiment = $feedback['sentiment'];
                    $_SESSION['success_message'] = ucfirst($sentiment) . " feedback marked as resolved by " . htmlspecialchars($resolved_by) . "!";
                } else {
                    throw new Exception("Update failed: " . $resolve_stmt->error);
                }
            }
        } else {
            $_SESSION['warning_message'] = "Feedback not found.";
        }

        $check_stmt->close();
        if (isset($resolve_stmt)) {
            $resolve_stmt->close();
        }

    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        error_log("Resolve Feedback Error: " . $e->getMessage());
    }

    header('Location: manage_feedback.php?' . http_build_query($_GET));
    exit();
}

// Mark as unresolved
if (isset($_GET['unresolve_id']) && is_numeric($_GET['unresolve_id'])) {
    $unresolve_id = intval($_GET['unresolve_id']);

    try {
        $unresolve_sql = "UPDATE feedback SET 
                         is_resolved = 0, 
                         resolved_by = NULL, 
                         resolution_notes = NULL,
                         resolved_at = NULL, 
                         updated_at = NOW() 
                         WHERE id = ?";
        $unresolve_stmt = $conn->prepare($unresolve_sql);

        if (!$unresolve_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $unresolve_stmt->bind_param("i", $unresolve_id);

        if ($unresolve_stmt->execute()) {
            $_SESSION['success_message'] = "Feedback marked as unresolved!";
        } else {
            throw new Exception("Update failed: " . $unresolve_stmt->error);
        }

        $unresolve_stmt->close();

    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        error_log("Unresolve Feedback Error: " . $e->getMessage());
    }

    $query_params = $_GET;
    unset($query_params['unresolve_id']);
    header('Location: manage_feedback.php?' . http_build_query($query_params));
    exit();
}

// Delete single feedback
if (isset($_POST['delete_feedback']) && isset($_POST['delete_id']) && is_numeric($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);

    try {
        // Get feedback info for message
        $check_sql = "SELECT f.id, f.comment, f.sentiment, u.username 
                      FROM feedback f 
                      JOIN `profiling-system`.residents u ON f.user_id = u.id 
                      WHERE f.id = ?";
        $check_stmt = $conn->prepare($check_sql);

        if (!$check_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $check_stmt->bind_param("i", $delete_id);

        if (!$check_stmt->execute()) {
            throw new Exception("Execute failed: " . $check_stmt->error);
        }

        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $feedback = $check_result->fetch_assoc();

            $delete_sql = "DELETE FROM feedback WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_sql);

            if (!$delete_stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $delete_stmt->bind_param("i", $delete_id);

            if ($delete_stmt->execute()) {
                $_SESSION['success_message'] = "Feedback from '" . htmlspecialchars($feedback['username']) . "' deleted successfully!";
            } else {
                throw new Exception("Delete failed: " . $delete_stmt->error);
            }

            $delete_stmt->close();
        } else {
            $_SESSION['error_message'] = "Feedback not found!";
        }

        $check_stmt->close();

    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        error_log("Delete Feedback Error: " . $e->getMessage());
    }

    // Redirect back with current filters
    $query_params = $_GET;
    header('Location: manage_feedback.php?' . http_build_query($query_params));
    exit();
}

// Bulk delete feedback
if (isset($_POST['bulk_delete_feedback']) && isset($_POST['bulk_delete_ids']) && !empty($_POST['bulk_delete_ids'])) {
    $delete_ids = array_map('intval', explode(',', $_POST['bulk_delete_ids']));
    $delete_ids = array_filter($delete_ids); // Remove any zeros

    if (!empty($delete_ids)) {
        try {
            // Create placeholders for prepared statement
            $placeholders = implode(',', array_fill(0, count($delete_ids), '?'));

            // Get feedback info for message
            $check_sql = "SELECT f.id, f.comment, f.sentiment, u.username 
                          FROM feedback f 
                          JOIN `profiling-system`.residents u ON f.user_id = u.id 
                          WHERE f.id IN ($placeholders)";
            $check_stmt = $conn->prepare($check_sql);

            if (!$check_stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            // Bind parameters
            $types = str_repeat('i', count($delete_ids));
            $check_stmt->bind_param($types, ...$delete_ids);

            if (!$check_stmt->execute()) {
                throw new Exception("Execute failed: " . $check_stmt->error);
            }

            $check_result = $check_stmt->get_result();
            $deleted_count = $check_result->num_rows;

            // Now delete the feedback
            $delete_sql = "DELETE FROM feedback WHERE id IN ($placeholders)";
            $delete_stmt = $conn->prepare($delete_sql);

            if (!$delete_stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $delete_stmt->bind_param($types, ...$delete_ids);

            if ($delete_stmt->execute()) {
                $_SESSION['success_message'] = "$deleted_count feedback items deleted successfully!";
            } else {
                throw new Exception("Delete failed: " . $delete_stmt->error);
            }

            $delete_stmt->close();
            $check_stmt->close();

        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
            error_log("Bulk Delete Feedback Error: " . $e->getMessage());
        }
    } else {
        $_SESSION['error_message'] = "No valid feedback IDs selected for deletion.";
    }

    // Redirect back with current filters
    $query_params = $_GET;
    header('Location: manage_feedback.php?' . http_build_query($query_params));
    exit();
}

// Update Assignment Status
if (isset($_POST['update_assignment_status']) && isset($_POST['assignment_id'])) {
    $assignment_id = intval($_POST['assignment_id']);
    $new_status = $_POST['status'];
    $admin_notes = $_POST['notes'] ?? '';

    // Check if valid status
    $valid_statuses = ['Pending', 'In Progress', 'Resolved', 'Waiting'];
    if (in_array($new_status, $valid_statuses)) {
        if (updateAssignmentStatus($conn, $assignment_id, $new_status, $admin_notes)) {
            $_SESSION['success_message'] = "Assignment status updated to " . $new_status;
        } else {
            $_SESSION['error_message'] = "Failed to update assignment status.";
        }
    } else {
        $_SESSION['error_message'] = "Invalid status selected.";
    }

    header('Location: manage_feedback.php?' . http_build_query($_GET));
    exit();
    header('Location: manage_feedback.php?' . http_build_query($_GET));
    exit();
}

// Reassign Personnel
if (isset($_POST['reassign_personnel']) && isset($_POST['assignment_id'])) {
    $assignment_id = intval($_POST['assignment_id']);
    $new_personnel_id = isset($_POST['new_personnel_id']) ? intval($_POST['new_personnel_id']) : null;

    $result = reassignPersonnel($conn, $assignment_id, $new_personnel_id);

    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
    } else {
        $_SESSION['error_message'] = $result['message'];
    }

    header('Location: manage_feedback.php?' . http_build_query($_GET));
    exit();
    header('Location: manage_feedback.php?' . http_build_query($_GET));
    exit();
}

// Delete Personnel
if (isset($_POST['delete_personnel']) && isset($_POST['delete_personnel_id'])) {
    $personnel_id = intval($_POST['delete_personnel_id']);

    $result = deletePersonnel($conn, $personnel_id);

    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
    } else {
        $_SESSION['error_message'] = $result['message'];
    }

    header('Location: manage_feedback.php?' . http_build_query($_GET));
    exit();
}

// Add Personnel
if (isset($_POST['add_personnel'])) {
    $name = trim($_POST['name']);
    $specialization_id = intval($_POST['specialization_id']);
    $description = trim($_POST['description']);

    if (empty($name)) {
        $_SESSION['error_message'] = "Personnel name is required.";
    } else {
        if (addPersonnel($conn, $name, $specialization_id, $description)) {
            $_SESSION['success_message'] = "New personnel added successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to add personnel: " . $conn->error;
        }
    }

    header('Location: manage_feedback.php?' . http_build_query($_GET));
    exit();
}

// Reset Personnel Stats
if (isset($_POST['reset_personnel_stats'])) {
    if (resetPersonnelStats($conn)) {
        $_SESSION['success_message'] = "Personnel statistics (completed count & ratings) have been reset.";
    } else {
        $_SESSION['error_message'] = "Failed to reset statistics: " . $conn->error;
    }

    header('Location: manage_feedback.php?' . http_build_query($_GET));
    exit();
}


// Search functionality
$sql = "SELECT f.*, u.first_name as firstname, u.surname as lastname, u.email, u.purok, c.name as category_name, c.icon as category_icon,
        fa.id as assignment_id, fa.status as assignment_status, fa.assigned_at, fa.started_at, fa.completed_at,
        p.name as personnel_name, p.star_rating as personnel_rating, sc.name as personnel_specialization
        FROM feedback f 
        JOIN `profiling-system`.residents u ON f.user_id = u.id 
        JOIN categories c ON f.category_id = c.id 
        LEFT JOIN feedback_assignments fa ON f.id = fa.feedback_id
        LEFT JOIN personnel p ON fa.personnel_id = p.id
        LEFT JOIN categories sc ON p.specialization_id = sc.id
        WHERE 1=1";

$params = [];
$types = '';

if ($category_filter > 0) {
    $sql .= " AND f.category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

if ($sentiment_filter && in_array($sentiment_filter, ['Positive', 'Negative', 'Neutral'])) {
    $sql .= " AND f.sentiment = ?";
    $params[] = $sentiment_filter;
    $types .= 's';
}

if ($date_from && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $sql .= " AND DATE(f.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $sql .= " AND DATE(f.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

if ($search) {
    $sql .= " AND (f.comment LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR f.resolution_notes LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ssss';
}

$sql .= " ORDER BY f.created_at DESC";

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM feedback f 
              JOIN `profiling-system`.residents u ON f.user_id = u.id 
              JOIN categories c ON f.category_id = c.id 
              WHERE 1=1";

$count_params = [];
$count_types = '';

if ($category_filter > 0) {
    $count_sql .= " AND f.category_id = ?";
    $count_params[] = $category_filter;
    $count_types .= 'i';
}

if ($sentiment_filter && in_array($sentiment_filter, ['Positive', 'Negative', 'Neutral'])) {
    $count_sql .= " AND f.sentiment = ?";
    $count_params[] = $sentiment_filter;
    $count_types .= 's';
}

if ($date_from && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $count_sql .= " AND DATE(f.created_at) >= ?";
    $count_params[] = $date_from;
    $count_types .= 's';
}

if ($date_to && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $count_sql .= " AND DATE(f.created_at) <= ?";
    $count_params[] = $date_to;
    $count_types .= 's';
}

if ($search) {
    $count_sql .= " AND (f.comment LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR f.resolution_notes LIKE ?)";
    $search_param = "%$search%";
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_types .= 'ssss';
}

// Execute count query
$total_records = 0;
if ($count_params) {
    $count_stmt = $conn->prepare($count_sql);
    if ($count_stmt) {
        $count_stmt->bind_param($count_types, ...$count_params);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_row = $count_result->fetch_assoc();
        $total_records = $total_row['total'];
        $count_stmt->close();
    }
} else {
    $count_result = $conn->query($count_sql);
    if ($count_result) {
        $total_row = $count_result->fetch_assoc();
        $total_records = $total_row['total'];
    }
}

$total_pages = ceil($total_records / $limit);

// Add pagination to main query
$main_sql = $sql . " LIMIT ? OFFSET ?";
$main_params = array_merge($params, [$limit, $offset]);
$main_types = $types . 'ii';

// Prepare and execute main query
try {
    $stmt = $conn->prepare($main_sql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    if ($main_params) {
        $stmt->bind_param($main_types, ...$main_params);
    }

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();

} catch (Exception $e) {
    $_SESSION['error_message'] = "Error loading feedback: " . $e->getMessage();
    error_log("Main Query Error: " . $e->getMessage());
    $result = false;
}

// Get categories for filter dropdown
$categories_result = $conn->query("SELECT * FROM categories ORDER BY name");

// Get all personnel for the personnel list section
$personnel_list = getAllPersonnel($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Feedback - Admin Dashboard</title>
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

        /* Delete Modal Styles - Made Smaller with Green Border */
        .modal-danger {
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

        .feedback-info-box {
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

        .feedback-info-box h5 {
            color: #991b1b;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
        }

        .feedback-info-box .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            padding-bottom: 4px;
            border-bottom: 1px dashed #fecaca;
            font-size: 13px;
        }

        .feedback-info-box .info-label {
            font-weight: 600;
            color: #374151;
        }

        .feedback-info-box .info-value {
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

        /* Bulk Delete Modal - Made Smaller with Green Border */
        .bulk-delete-modal .modal-content {
            max-width: 450px;
            max-height: 85vh;
            border: 2px solid #1F3A93;
            /* Green border */
            border-radius: 15px;
        }

        .bulk-delete-modal .modal-header {
            padding: 20px 25px;
        }

        .bulk-delete-modal .warning-icon {
            width: 60px;
            height: 60px;
            font-size: 30px;
        }

        .bulk-delete-modal .delete-confirmation {
            max-height: 50vh;
            overflow-y: auto;
        }

        .bulk-delete-items {
            max-height: 150px;
            overflow-y: auto;
            margin: 12px 0;
            padding: 8px;
            background: #f9fafb;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            border-left: 3px solid #1F3A93;
            /* Green accent */
        }

        .bulk-delete-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 8px;
            margin-bottom: 4px;
            background: white;
            border-radius: 4px;
            border: 1px solid #e5e7eb;
            font-size: 12px;
            transition: all 0.2s;
        }

        .bulk-delete-item:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }

        .bulk-delete-item:last-child {
            margin-bottom: 0;
        }

        .bulk-delete-info {
            flex: 1;
        }

        .bulk-delete-id {
            font-weight: 600;
            color: #991b1b;
            margin-right: 8px;
        }

        .bulk-delete-user {
            color: #4b5563;
            font-size: 12px;
        }

        .bulk-delete-comment {
            color: #6b7280;
            font-size: 11px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px;
        }

        /* Scrollbar styling for modals */
        .delete-confirmation::-webkit-scrollbar,
        .feedback-info-box::-webkit-scrollbar,
        .bulk-delete-items::-webkit-scrollbar {
            width: 6px;
        }

        .delete-confirmation::-webkit-scrollbar-track,
        .feedback-info-box::-webkit-scrollbar-track,
        .bulk-delete-items::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .delete-confirmation::-webkit-scrollbar-thumb,
        .feedback-info-box::-webkit-scrollbar-thumb,
        .bulk-delete-items::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .delete-confirmation::-webkit-scrollbar-thumb:hover,
        .feedback-info-box::-webkit-scrollbar-thumb:hover,
        .bulk-delete-items::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Smaller modal content */
        .modal-danger .modal-content {
            max-width: 450px;
        }

        .modal-header h2 {
            font-size: 20px;
        }

        .modal-footer .btn {
            padding: 10px 20px;
            font-size: 14px;
        }

        /* Mobile responsive adjustments for modals */
        @media (max-width: 768px) {
            .modal-danger .modal-content {
                max-width: 95%;
                margin: 10px;
            }

            .delete-confirmation {
                max-height: 60vh;
            }

            .feedback-info-box {
                max-height: 150px;
            }

            .bulk-delete-items {
                max-height: 120px;
            }
        }

        .bulk-delete-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 10px;
            margin-bottom: 5px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }

        .bulk-delete-item:last-child {
            margin-bottom: 0;
        }

        .bulk-delete-info {
            flex: 1;
        }

        .bulk-delete-id {
            font-weight: 600;
            color: #991b1b;
            margin-right: 10px;
        }

        .bulk-delete-user {
            color: #4b5563;
            font-size: 13px;
        }

        .bulk-delete-comment {
            color: #6b7280;
            font-size: 12px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
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

        .user-info .user-name {
            font-weight: 600;
            font-size: 16px;
            color: #ffffff !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-info .user-role {
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

        /* Manage Feedback Styles */
        .filter-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            margin-bottom: 25px;
            border: 1px solid #e5e7eb;
        }

        .filter-card h2 {
            color: #1a317d;
            margin-bottom: 20px;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-card h2 i {
            color: #1F3A93;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #4b5563;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: #1F3A93;
            box-shadow: 0 0 0 3px rgba(31, 58, 147, 0.1);
        }

        .search-box {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .search-box .form-control {
            flex: 1;
        }

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

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .btn-danger {
            background: linear-gradient(90deg, #ef4444, #f87171);
        }

        .btn-danger:hover {
            background: linear-gradient(90deg, #dc2626, #ef4444);
        }

        .btn-success {
            background: linear-gradient(90deg, #1a317d, #4ade80);
        }

        .btn-success:hover {
            background: linear-gradient(90deg, #16a34a, #1a317d);
        }

        .btn-warning {
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
            color: white;
        }

        .btn-warning:hover {
            background: linear-gradient(90deg, #d97706, #f59e0b);
        }

        .btn-info {
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
            color: white;
        }

        .btn-info:hover {
            background: linear-gradient(90deg, #2563eb, #3b82f6);
        }

        .btn-small {
            padding: 8px 15px;
            font-size: 13px;
        }

        .btn-icon {
            width: 40px;
            height: 40px;
            padding: 0;
            justify-content: center;
            border-radius: 8px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        /* Table Styles */
        .table-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            margin-bottom: 25px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

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

        .table-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .result-count {
            background: #f0f7ff;
            padding: 8px 15px;
            border-radius: 8px;
            color: #1a317d;
            font-weight: 500;
            font-size: 14px;
            border: 1px solid #bae6fd;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            table-layout: fixed;
        }

        table th,
        table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        table th {
            background-color: #f0f7ff;
            font-weight: 600;
            color: #1a317d;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        table tr:hover {
            background-color: #f9fafb;
        }

        /* Highlight for resolved feedback */
        .resolved-row {
            background-color: #f0f7ff !important;
        }

        .resolved-row:hover {
            background-color: #e6f9ef !important;
        }

        /* Fixed column widths for better alignment */
        table th:nth-child(1),
        table td:nth-child(1) {
            width: 50px;
        }

        /* Checkbox/ID */
        table th:nth-child(2),
        table td:nth-child(2) {
            width: 150px;
        }

        /* User */
        table th:nth-child(3),
        table td:nth-child(3) {
            width: 120px;
        }

        /* Category */
        table th:nth-child(4),
        table td:nth-child(4) {
            width: 80px;
        }

        /* Rating */
        table th:nth-child(5),
        table td:nth-child(5) {
            width: 220px;
        }

        /* Comment */
        table th:nth-child(6),
        table td:nth-child(6) {
            width: 100px;
        }

        /* Sentiment */
        table th:nth-child(7),
        table td:nth-child(7) {
            width: 150px;
        }

        /* Assignment */
        table th:nth-child(8),
        table td:nth-child(8) {
            width: 100px;
        }

        /* Date */
        table th:nth-child(9),
        table td:nth-child(9) {
            width: 150px;
        }

        /* Actions - FIXED WIDTH */

        .checkbox-cell {
            width: 50px;
            text-align: center;
        }

        .actions-cell {
            width: 180px;
            text-align: center;
        }

        .user-info-cell {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .user-name {
            font-weight: 600;
            color: #1a317d;
            font-size: 14px;
        }

        .user-email {
            font-size: 12px;
            color: #6b7280;
        }

        .category-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .category-icon {
            width: 30px;
            height: 30px;
            background: #f0f7ff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1F3A93;
            font-size: 14px;
        }

        .rating-stars {
            color: #fbbf24;
            font-size: 14px;
        }

        .sentiment-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            min-width: 80px;
        }

        .sentiment-positive {
            background: #bae6fd;
            color: #1a317d;
            border: 1px solid #bae6fd;
        }

        .sentiment-negative {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .sentiment-neutral {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 5px;
        }

        .status-resolved {
            background: #bae6fd;
            color: #1a317d;
            border: 1px solid #bae6fd;
        }

        .comment-preview {
            max-width: 240px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 14px;
            line-height: 1.4;
        }

        .view-more {
            color: #1F3A93;
            text-decoration: none;
            font-size: 12px;
            cursor: pointer;
            display: inline-block;
            margin-top: 5px;
            font-weight: 500;
        }

        .view-more:hover {
            text-decoration: underline;
        }

        /* Date cell styling */
        .date-cell {
            font-size: 13px;
            color: #6b7280;
        }

        /* Resolved info styling */
        .resolved-info {
            font-size: 11px;
            color: #6b7280;
            margin-top: 3px;
        }

        .resolved-info i {
            color: #1F3A93;
            margin-right: 3px;
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
            font-size: 14px;
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
            font-size: 14px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }

        .empty-state i {
            font-size: 64px;
            color: #d1d5db;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #6b7280;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .empty-state p {
            color: #9ca3af;
            font-size: 14px;
        }

        /* Alert */
        .alert {
            padding: 15px 20px;
            background: #bae6fd;
            border: 1px solid #bae6fd;
            border-radius: 10px;
            color: #1a317d;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .alert i {
            color: #1F3A93;
        }

        .alert-warning {
            background: #fef3c7;
            border: 1px solid #fde68a;
            color: #92400e;
        }

        .alert-warning i {
            color: #f59e0b;
        }

        .alert-error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .alert-error i {
            color: #ef4444;
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
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: fadeIn 0.3s ease;
        }

        .modal.show {
            display: flex;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            animation: slideIn 0.3s ease;
            overflow: hidden;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #1F3A93, #3a56b5);
            color: white;
            padding: 25px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #bae6fd;
        }

        .modal-header h2 {
            font-size: 22px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .close-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 28px;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .modal-body {
            padding: 30px;
            background: #f8fafc;
            max-height: 70vh;
            overflow-y: auto;
        }

        .modal-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .modal-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }

        .modal-item:hover {
            border-color: #1F3A93;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(31, 58, 147, 0.1);
        }

        .modal-label {
            font-size: 14px;
            font-weight: 600;
            color: #1a317d;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-label i {
            color: #1F3A93;
        }

        .modal-value {
            font-size: 16px;
            color: #4b5563;
            line-height: 1.5;
        }

        #modalComment {
            white-space: pre-wrap;
            word-wrap: break-word;
            grid-column: span 2;
            min-height: 100px;
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #1F3A93;
        }

        .modal-footer {
            background: white;
            padding: 20px 30px;
            border-top: 1px solid #e5e7eb;
            text-align: right;
        }

        .close-modal {
            padding: 12px 30px;
            font-size: 16px;
        }

        /* Resolve Modal Specific Styles */
        .resolve-modal .modal-content {
            max-width: 500px;
        }

        .resolve-form {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #4b5563;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1F3A93;
            box-shadow: 0 0 0 3px rgba(31, 58, 147, 0.1);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        /* Make comment full width on smaller screens */
        @media (max-width: 768px) {
            .modal-grid {
                grid-template-columns: 1fr;
            }

            #modalComment {
                grid-column: span 1;
            }

            .modal-content {
                margin: 10px;
                max-height: 90vh;
                overflow-y: auto;
            }
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

            .filter-grid {
                grid-template-columns: 1fr;
            }

            .table-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .table-actions {
                width: 100%;
                justify-content: flex-start;
            }

            table {
                display: block;
                overflow-x: auto;
                table-layout: auto;
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

            /* Reset fixed widths on mobile */
            table th:nth-child(1),
            table td:nth-child(1) {
                width: auto;
            }

            table th:nth-child(2),
            table td:nth-child(2) {
                width: auto;
            }

            table th:nth-child(3),
            table td:nth-child(3) {
                width: auto;
            }

            table th:nth-child(4),
            table td:nth-child(4) {
                width: auto;
            }

            table th:nth-child(5),
            table td:nth-child(5) {
                width: auto;
            }

            table th:nth-child(6),
            table td:nth-child(6) {
                width: auto;
            }

            table th:nth-child(7),
            table td:nth-child(7) {
                width: auto;
            }

            table th:nth-child(8),
            table td:nth-child(8) {
                width: auto;
            }

            .btn-icon {
                width: 35px;
                height: 35px;
            }

            .actions-row {
                flex-wrap: wrap;
                justify-content: center;
                gap: 5px;
            }
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

        /* Checkbox styling */
        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        /* Date display in header */
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
            font-size: 14px;
        }

        .date-display i {
            color: #1F3A93;
        }

        /* Make sure table rows don't break */
        table tr {
            white-space: nowrap;
        }

        /* Ensure content in table cells doesn't overflow */
        table td {
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .actions-row {
            display: flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
        }

        /* Resolution notes preview */
        .resolution-preview {
            max-width: 240px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 12px;
            color: #6b7280;
            margin-top: 3px;
            font-style: italic;
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

        /* Responsive Feedback Cards for Mobile */
        .feedback-cards-mobile {
            display: none;
        }

        .feedback-table-desktop {
            display: block;
        }

        .feedback-card-mobile-item {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 15px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            transition: all 0.3s;
        }

        .feedback-card-mobile-item.resolved-card {
            background: #f0f7ff;
            border-color: #bae6fd;
        }

        .feedback-card-mobile-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border-color: #1F3A93;
        }

        .mobile-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }

        .mobile-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .mobile-user-avatar {
            width: 45px;
            height: 45px;
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

        .mobile-user-name {
            font-weight: 600;
            color: #1a317d;
            font-size: 15px;
        }

        .mobile-user-email {
            font-size: 12px;
            color: #6b7280;
        }

        .mobile-card-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .mobile-card-body {
            padding: 15px;
        }

        .mobile-feedback-info {
            margin-bottom: 15px;
        }

        .mobile-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px dashed #e5e7eb;
        }

        .mobile-info-row:last-child {
            border-bottom: none;
        }

        .mobile-info-row.resolved-info {
            background: #f0f7ff;
            padding: 10px;
            border-radius: 6px;
            margin-top: 5px;
            border-bottom: none;
        }

        .mobile-info-label {
            font-weight: 600;
            color: #4b5563;
            font-size: 13px;
            flex: 1;
        }

        .mobile-info-value {
            text-align: right;
            flex: 1;
            font-size: 13px;
            color: #1a317d;
        }

        .mobile-category {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #4b5563;
        }

        .mobile-category i {
            color: #1F3A93;
            font-size: 14px;
        }

        .mobile-resolved-details {
            margin-top: 4px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .mobile-resolved-details small {
            color: #6b7280;
            font-size: 11px;
        }

        .mobile-comment-section {
            background: #f9fafb;
            border-radius: 8px;
            padding: 12px;
            margin-top: 10px;
            border-left: 3px solid #1F3A93;
        }

        .mobile-comment-label {
            font-weight: 600;
            color: #4b5563;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .mobile-comment-preview {
            color: #4b5563;
            line-height: 1.4;
            font-size: 13px;
            margin-bottom: 10px;
        }

        .mobile-view-more {
            background: none;
            border: none;
            color: #1F3A93;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            padding: 0;
            text-decoration: underline;
        }

        .mobile-view-more:hover {
            color: #152c71;
        }

        .mobile-card-footer {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }

        .mobile-action-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .mobile-action-btn {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .mobile-action-btn:hover {
            transform: translateY(-2px);
        }

        /* Mobile Responsive Adjustments */
        @media (max-width: 768px) {
            .feedback-cards-mobile {
                display: block;
            }

            .feedback-table-desktop {
                display: none;
            }

            /* Adjust table header for mobile */
            .table-header {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }

            .table-actions {
                flex-direction: column;
                gap: 10px;
            }

            .result-count {
                text-align: center;
                width: 100%;
            }

            /* Make pagination responsive */
            .pagination {
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
            }

            .page-link {
                padding: 8px 15px;
                font-size: 13px;
            }

            .page-info {
                padding: 8px 15px;
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .feedback-card-mobile-item {
                border-radius: 10px;
            }

            .mobile-card-header {
                padding: 12px;
            }

            .mobile-user-avatar {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }

            .mobile-user-name {
                font-size: 14px;
            }

            .mobile-user-email {
                font-size: 11px;
            }

            .mobile-card-body {
                padding: 12px;
            }

            .mobile-info-label,
            .mobile-info-value {
                font-size: 12px;
            }

            .mobile-comment-section {
                padding: 10px;
            }

            .mobile-comment-preview {
                font-size: 12px;
            }

            .mobile-action-buttons {
                flex-wrap: wrap;
                justify-content: space-around;
            }

            .mobile-action-btn {
                width: 36px;
                height: 36px;
                font-size: 12px;
            }
        }

        body.dark-mode .table-header h2,
        body.dark-mode .filter-card h2,
        body.dark-mode .page-title {
            color: #ffffff !important;
        }

        body.dark-mode .table-header h2 i,
        body.dark-mode .filter-card h2 i,
        body.dark-mode .page-title i {
            color: #60a5fa !important;
        }

        body.dark-mode .user-name {
            color: #ffffff !important;
        }

        body.dark-mode .user-email {
            color: #d1d5db !important;
        }

        body.dark-mode .date-cell {
            color: #d1d5db !important;
        }

        body.dark-mode .comment-preview {
            color: #e5e7eb !important;
        }

        body.dark-mode .view-more {
            color: #60a5fa !important;
        }

        body.dark-mode .category-cell {
            color: #e5e7eb !important;
        }

        body.dark-mode .category-icon {
            background: #374151 !important;
            color: #60a5fa !important;
        }

        body.dark-mode table th {
            background-color: #374151 !important;
            color: #ffffff !important;
            border-bottom-color: #4b5563 !important;
        }

        body.dark-mode .table-card,
        body.dark-mode .filter-card {
            background: #1f2937 !important;
            border-color: #374151 !important;
        }

        body.dark-mode .form-label {
            color: #e5e7eb !important;
        }

        body.dark-mode .result-count {
            background: #374151 !important;
            color: #ffffff !important;
            border-color: #4b5563 !important;
        }

        body.dark-mode .rating-stars {
            color: #fbbf24 !important;
        }

        body.dark-mode .mobile-user-name {
            color: #ffffff !important;
        }

        body.dark-mode .mobile-user-email {
            color: #d1d5db !important;
        }

        body.dark-mode .mobile-info-label {
            color: #9ca3af !important;
        }

        body.dark-mode .mobile-info-value {
            color: #e5e7eb !important;
        }

        body.dark-mode .mobile-category {
            color: #e5e7eb !important;
        }

        body.dark-mode .mobile-comment-label {
            color: #e5e7eb !important;
        }

        body.dark-mode .mobile-comment-preview {
            color: #d1d5db !important;
        }

        body.dark-mode .mobile-view-more {
            color: #60a5fa !important;
        }

        body.dark-mode .mobile-card-header {
            background: #374151 !important;
            border-bottom-color: #4b5563 !important;
        }

        body.dark-mode .feedback-card-mobile-item {
            background: #1f2937 !important;
            border-color: #374151 !important;
        }

        body.dark-mode .mobile-card-footer {
            border-top-color: #4b5563 !important;
        }

        body.dark-mode .mobile-info-row {
            border-bottom-color: #4b5563 !important;
        }

        body.dark-mode .mobile-comment-section {
            background: #374151 !important;
            border-left-color: #60a5fa !important;
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
                <a href="/htdocs/dashboard.php" class="modal-btn modal-btn-primary">
                    <i class="fas fa-sign-out-alt"></i> Yes, Logout
                </a>
            </div>
        </div>
    </div>
    <!-- ========== END LOGOUT MODAL ========== -->

    <!-- Delete Feedback Modal -->
    <div id="deleteFeedbackModal" class="modal">
        <div class="modal-content modal-danger">
            <div class="modal-header">
                <h2>
                    <i class="fas fa-exclamation-triangle"></i> Delete Feedback
                </h2>
                <button class="close-btn" id="closeDeleteModalBtn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="delete-confirmation">
                    <div class="warning-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>

                    <h4>Confirm Deletion</h4>

                    <p>Are you sure you want to delete this feedback?</p>
                    <p>This action <strong>cannot be undone</strong> and will permanently remove the feedback record.
                    </p>

                    <div class="feedback-info-box" id="deleteFeedbackInfo">
                        <!-- Feedback info will be populated here by JavaScript -->
                    </div>

                    <div class="warning-note">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>Warning:</strong> Deleting this feedback will permanently remove it from the system.
                        This action is irreversible.
                    </div>

                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>"
                        id="deleteFeedbackForm">
                        <input type="hidden" name="delete_id" id="delete_feedback_id" value="">
                        <input type="hidden" name="delete_feedback" value="1">
                    </form>
                </div>
            </div>
            <div class="modal-footer danger">
                <button type="button" class="btn btn-secondary" id="cancelDeleteBtn">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" form="deleteFeedbackForm" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete Feedback
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Feedback Modal - Made Smaller with Green Border -->
    <div id="deleteFeedbackModal" class="modal">
        <div class="modal-content modal-danger">
            <div class="modal-header">
                <h2>
                    <i class="fas fa-exclamation-triangle"></i> Delete Feedback
                </h2>
                <button class="close-btn" id="closeDeleteModalBtn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="delete-confirmation">
                    <div class="warning-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>

                    <h4>Confirm Deletion</h4>

                    <p>Are you sure you want to delete this feedback?</p>
                    <p>This action <strong>cannot be undone</strong>.</p>

                    <div class="feedback-info-box" id="deleteFeedbackInfo">
                        <!-- Feedback info will be populated here by JavaScript -->
                    </div>

                    <div class="warning-note">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>Warning:</strong> Deleting feedback is permanent and cannot be recovered.
                    </div>

                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>"
                        id="deleteFeedbackForm">
                        <input type="hidden" name="delete_id" id="delete_feedback_id" value="">
                        <input type="hidden" name="delete_feedback" value="1">
                    </form>
                </div>
            </div>
            <div class="modal-footer danger">
                <button type="button" class="btn btn-secondary" id="cancelDeleteBtn">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" form="deleteFeedbackForm" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Feedback Modal - Made Smaller with Green Border -->
    <div id="bulkDeleteModal" class="modal bulk-delete-modal">
        <div class="modal-content modal-danger">
            <div class="modal-header">
                <h2>
                    <i class="fas fa-exclamation-triangle"></i> Delete Multiple
                </h2>
                <button class="close-btn" id="closeBulkDeleteModalBtn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="delete-confirmation">
                    <div class="warning-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>

                    <h4>Confirm Bulk Deletion</h4>

                    <p>Are you sure you want to delete <span id="bulkDeleteCount">0</span> selected items?</p>
                    <p>This action <strong>cannot be undone</strong>.</p>

                    <div class="bulk-delete-items" id="bulkDeleteItems">
                        <!-- Selected feedback items will be listed here -->
                    </div>

                    <div class="warning-note">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>Warning:</strong> Deleting these items is permanent and cannot be recovered.
                    </div>

                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>"
                        id="bulkDeleteForm">
                        <input type="hidden" name="bulk_delete_ids" id="bulk_delete_ids" value="">
                        <input type="hidden" name="bulk_delete_feedback" value="1">
                    </form>
                </div>
            </div>
            <div class="modal-footer danger">
                <button type="button" class="btn btn-secondary" id="cancelBulkDeleteBtn">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" form="bulkDeleteForm" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete (<span id="bulkDeleteCountBtn">0</span>)
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
        </ul>

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
                <i class="fas fa-list-alt title-icon"></i>
                Manage Feedback
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

        <!-- Error Message -->
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $_SESSION['error_message'];
                unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Success Message -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert">
                <i class="fas fa-check-circle"></i>
                <?php echo $_SESSION['success_message'];
                unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Warning Message -->
        <?php if (isset($_SESSION['warning_message'])): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $_SESSION['warning_message'];
                unset($_SESSION['warning_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Filter Card -->
        <div class="filter-card">
            <h2><i class="fas fa-filter"></i> Filter Feedback</h2>

            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="form-group">
                        <label class="form-label" for="category">Category</label>
                        <select class="form-control" id="category" name="category">
                            <option value="">All Categories</option>
                            <?php
                            // Reset pointer to beginning of result set
                            if ($categories_result && $categories_result->num_rows > 0) {
                                $categories_result->data_seek(0);
                                while ($cat = $categories_result->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($category_filter == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endwhile;
                            } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="sentiment">Sentiment</label>
                        <select class="form-control" id="sentiment" name="sentiment">
                            <option value="">All Sentiments</option>
                            <option value="Positive" <?php echo ($sentiment_filter == 'Positive') ? 'selected' : ''; ?>>
                                Positive</option>
                            <option value="Negative" <?php echo ($sentiment_filter == 'Negative') ? 'selected' : ''; ?>>
                                Negative</option>
                            <option value="Neutral" <?php echo ($sentiment_filter == 'Neutral') ? 'selected' : ''; ?>>
                                Neutral</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="date_from">From Date</label>
                        <input type="date" class="form-control" id="date_from" name="date_from"
                            value="<?php echo $date_from; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="date_to">To Date</label>
                        <input type="date" class="form-control" id="date_to" name="date_to"
                            value="<?php echo $date_to; ?>">
                    </div>
                </div>

                <div class="search-box">
                    <input type="text" class="form-control" name="search"
                        placeholder="Search in comments, usernames, or resolution notes..."
                        value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="manage_feedback.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Clear Filters
                    </a>
                </div>
            </form>
        </div>

        <!-- Feedback Table Card -->
        <div class="table-card">
            <div class="table-header">
                <h2><i class="fas fa-comments"></i> Feedback Results</h2>
                <div class="table-actions">
                    <button class="btn btn-small" onclick="selectAll()">
                        <i class="fas fa-check-square"></i> Select All
                    </button>
                    <button class="btn btn-small btn-danger" onclick="deleteSelected()">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                    <div class="result-count">
                        <?php echo $total_records; ?> feedback entries found
                    </div>
                </div>
            </div>

            <?php if ($result && $result->num_rows > 0): ?>
                <!-- Mobile View (Cards) - Hidden on Desktop -->
                <div class="feedback-cards-mobile" id="mobileFeedbackView">
                    <?php
                    // Reset the result pointer to loop again
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
                        $fullname = htmlspecialchars($row['firstname'] . ' ' . $row['lastname']);
                        ?>
                        <div class="feedback-card-mobile-item <?php echo $row['is_resolved'] ? 'resolved-card' : ''; ?>"
                            id="mobile-row-<?php echo $row['id']; ?>">
                            <div class="mobile-card-header">
                                <div class="mobile-user-info">
                                    <div class="mobile-user-avatar">
                                        <?php echo strtoupper(substr($row['firstname'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="mobile-user-name"><?php echo $fullname; ?></div>
                                        <div class="mobile-user-email"><?php echo htmlspecialchars($row['email'] ?? ''); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="mobile-card-actions">
                                    <input type="checkbox" class="mobile-row-checkbox" value="<?php echo $row['id']; ?>"
                                        onchange="updateMobileSelection()">
                                </div>
                            </div>

                            <div class="mobile-card-body">
                                <div class="mobile-feedback-info">
                                    <div class="mobile-info-row">
                                        <span class="mobile-info-label">Category:</span>
                                        <span class="mobile-info-value">
                                            <div class="mobile-category">
                                                <?php
                                                $icon_class = getCategoryIcon($row['category_icon'] ?? '');
                                                if ($icon_class): ?>
                                                    <i class="<?php echo $icon_class; ?>"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-folder"></i>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($row['category_name']); ?>
                                            </div>
                                        </span>
                                    </div>

                                    <div class="mobile-info-row">
                                        <span class="mobile-info-label">Rating:</span>
                                        <span class="mobile-info-value">
                                            <?php echo displayRating($row['rating']); ?>
                                        </span>
                                    </div>

                                    <div class="mobile-info-row">
                                        <span class="mobile-info-label">Sentiment:</span>
                                        <span class="mobile-info-value">
                                            <span class="sentiment-badge <?php echo $sentiment_class; ?>">
                                                <?php echo $row['sentiment']; ?>
                                            </span>
                                        </span>
                                    </div>

                                    <div class="mobile-info-row">
                                        <span class="mobile-info-label">Date:</span>
                                        <span class="mobile-info-value"><?php echo formatDate($row['created_at']); ?></span>
                                    </div>

                                    <?php if ($row['is_resolved'] && !empty($row['resolved_by'])): ?>
                                        <div class="mobile-info-row resolved-info">
                                            <span class="mobile-info-label">Status:</span>
                                            <span class="mobile-info-value">
                                                <span class="status-badge status-resolved">
                                                    <i class="fas fa-check-circle"></i> Resolved
                                                </span>
                                                <div class="mobile-resolved-details">
                                                    <small>By <?php echo htmlspecialchars($row['resolved_by']); ?></small>
                                                    <?php if (!empty($row['resolved_at'])): ?>
                                                        <small><?php echo formatDate($row['resolved_at']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mobile-comment-section">
                                    <div class="mobile-comment-label">Comment:</div>
                                    <div class="mobile-comment-preview">
                                        <?php echo htmlspecialchars(substr($row['comment'], 0, 120)); ?>...
                                    </div>
                                    <button
                                        onclick="showDetails(<?php echo $row['id']; ?>, '<?php echo $row['attachment_path'] ? htmlspecialchars($row['attachment_path']) : ''; ?>')"
                                        class="mobile-view-more">
                                        View Full Comment
                                    </button>
                                </div>

                                <div class="mobile-card-footer">
                                    <div class="mobile-action-buttons">
                                        <button class="mobile-action-btn btn-icon"
                                            onclick="showDetails(<?php echo $row['id']; ?>, '<?php echo $row['attachment_path'] ? htmlspecialchars($row['attachment_path']) : ''; ?>')"
                                            title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="mobile-action-btn btn-danger btn-icon delete-btn"
                                            data-id="<?php echo $row['id']; ?>" data-username="<?php echo $fullname; ?>"
                                            data-email="<?php echo htmlspecialchars($row['email'] ?? ''); ?>"
                                            data-comment="<?php echo htmlspecialchars(substr($row['comment'], 0, 80)); ?>"
                                            data-sentiment="<?php echo $row['sentiment']; ?>"
                                            data-category="<?php echo htmlspecialchars($row['category_name']); ?>"
                                            data-date="<?php echo formatDate($row['created_at']); ?>" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php if ($row['is_resolved']): ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['unresolve_id' => $row['id']])); ?>"
                                                class="mobile-action-btn btn-warning btn-icon" title="Mark as Unresolved">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="mobile-action-btn btn-success btn-icon resolve-btn"
                                                data-id="<?php echo $row['id']; ?>"
                                                data-comment="<?php echo htmlspecialchars(substr($row['comment'], 0, 50)); ?>..."
                                                data-sentiment="<?php echo $row['sentiment']; ?>" title="Mark as Resolved">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Desktop View (Table) - Hidden on Mobile -->
                <div class="feedback-table-desktop" id="desktopFeedbackView">
                    <table id="feedbackTable">
                        <thead>
                            <tr>
                                <th class="checkbox-cell">
                                    <input type="checkbox" id="selectAll">
                                </th>
                                <th>ID</th>
                                <th>User</th>
                                <th>Category</th>
                                <th>Rating</th>
                                <th>Comment</th>
                                <th>Sentiment</th>
                                <th>Assignment</th>
                                <th>Date</th>
                                <th class="actions-cell">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Reset again for desktop table
                            $result->data_seek(0);
                            while ($row = $result->fetch_assoc()): ?>
                                <tr id="row-<?php echo $row['id']; ?>"
                                    class="<?php echo $row['is_resolved'] ? 'resolved-row' : ''; ?>">
                                    <td class="checkbox-cell">
                                        <input type="checkbox" class="row-checkbox" value="<?php echo $row['id']; ?>">
                                    </td>
                                    <td><?php echo $row['id']; ?></td>
                                    <td>
                                        <div class="user-info-cell">
                                            <?php $fullname = htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?>
                                            <span class="user-name"><?php echo $fullname; ?></span>
                                            <span class="user-email"><?php echo htmlspecialchars($row['email'] ?? ''); ?></span>
                                            <span class="user-purok"
                                                style="display:none;"><?php echo htmlspecialchars($row['purok'] ?? ''); ?></span>
                                            <?php if ($row['is_resolved'] && !empty($row['resolved_by'])): ?>
                                                <div class="resolved-info">
                                                    <i class="fas fa-user-check"></i>
                                                    Resolved by <?php echo htmlspecialchars($row['resolved_by']); ?>
                                                    <?php if (!empty($row['resolved_at'])): ?>
                                                        on <?php echo formatDate($row['resolved_at']); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!empty($row['resolution_notes'])): ?>
                                                    <div class="resolution-preview"
                                                        title="<?php echo htmlspecialchars($row['resolution_notes']); ?>">
                                                        <i class="fas fa-sticky-note"></i>
                                                        <?php echo htmlspecialchars(substr($row['resolution_notes'], 0, 50)); ?>...
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="category-cell">
                                            <div class="category-icon">
                                                <?php
                                                $icon_class = getCategoryIcon($row['category_icon'] ?? '');
                                                if ($icon_class): ?>
                                                    <i class="<?php echo $icon_class; ?>"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-folder"></i>
                                                <?php endif; ?>
                                            </div>
                                            <span><?php echo htmlspecialchars($row['category_name']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="rating-stars">
                                            <?php echo displayRating($row['rating']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="comment-preview" title="<?php echo htmlspecialchars($row['comment']); ?>">
                                            <?php echo htmlspecialchars(substr($row['comment'], 0, 80)); ?>...
                                        </div>
                                        <a href="#"
                                            onclick="showDetails(<?php echo $row['id']; ?>, '<?php echo $row['attachment_path'] ? htmlspecialchars($row['attachment_path']) : ''; ?>'); return false;"
                                            class="view-more">
                                            View More
                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                        $sentiment_class = '';
                                        if ($row['sentiment'] == 'Positive') {
                                            $sentiment_class = 'sentiment-positive';
                                        } elseif ($row['sentiment'] == 'Negative') {
                                            $sentiment_class = 'sentiment-negative';
                                        } else {
                                            $sentiment_class = 'sentiment-neutral';
                                        }
                                        ?>
                                        <span class="sentiment-badge <?php echo $sentiment_class; ?>">
                                            <?php echo $row['sentiment']; ?>
                                        </span>

                                        <?php if ($row['is_resolved']): ?>
                                            <div class="status-badge status-resolved">
                                                <i class="fas fa-check-circle"></i> Resolved
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['assignment_status'] == 'Waiting'): ?>
                                            <div style="font-size: 13px;">
                                                <div style="font-weight: 600; color: #b45309;">
                                                    <i class="fas fa-clock"></i> Unassigned
                                                </div>
                                                <div style="font-size: 11px; color: #6b7280; margin-bottom: 2px;">
                                                    Waiting for Personnel
                                                </div>
                                                <?php echo getAssignmentStatusBadge($row['assignment_status']); ?>
                                            </div>
                                        <?php elseif ($row['personnel_name']): ?>
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
                                        <?php else: ?>
                                            <span style="color: #9ca3af; font-size: 12px;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="date-cell"><?php echo formatDate($row['created_at']); ?></td>
                                    <td class="actions-cell">
                                        <div class="actions-row">
                                            <button class="btn btn-icon"
                                                onclick="showDetails(<?php echo $row['id']; ?>, '<?php echo $row['attachment_path'] ? htmlspecialchars($row['attachment_path']) : ''; ?>')"
                                                title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <!-- Changed to button for modal delete -->
                                            <button class="btn btn-danger btn-icon delete-btn"
                                                data-id="<?php echo $row['id']; ?>" data-username="<?php echo $fullname; ?>"
                                                data-email="<?php echo htmlspecialchars($row['email'] ?? ''); ?>"
                                                data-comment="<?php echo htmlspecialchars(substr($row['comment'], 0, 80)); ?>"
                                                data-sentiment="<?php echo $row['sentiment']; ?>"
                                                data-category="<?php echo htmlspecialchars($row['category_name']); ?>"
                                                data-date="<?php echo formatDate($row['created_at']); ?>" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php if ($row['is_resolved']): ?>
                                                <a href="?<?php echo http_build_query(array_merge($_GET, ['unresolve_id' => $row['id']])); ?>"
                                                    class="btn btn-warning btn-icon" title="Mark as Unresolved">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-success btn-icon resolve-btn"
                                                    data-id="<?php echo $row['id']; ?>"
                                                    data-comment="<?php echo htmlspecialchars(substr($row['comment'], 0, 50)); ?>..."
                                                    data-sentiment="<?php echo $row['sentiment']; ?>"
                                                    data-assignment-id="<?php echo $row['assignment_id']; ?>"
                                                    data-personnel="<?php echo $row['personnel_name'] ? htmlspecialchars($row['personnel_name']) : ''; ?>"
                                                    title="Mark as Resolved">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($row['assignment_id']): ?>
                                                <button class="btn btn-icon"
                                                    onclick='openAssignmentModal(<?php echo json_encode($row); ?>)'
                                                    title="Manage Assignment"
                                                    style="background: #eff6ff; color: #1e40af; border: 1px solid #dbeafe;">
                                                    <i class="fas fa-tasks"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>

                        <span class="page-info">
                            Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                        </span>

                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-link">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-comments-slash"></i>
                    <h3>No Feedback Found</h3>
                    <p>No feedback matches your filter criteria. Try adjusting your filters.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Personnel List Section -->
        <div class="table-card" style="margin-top: 30px;">
            <div class="table-header">
                <h2><i class="fas fa-users"></i> Personnel</h2>
                <div class="table-actions">
                    <button class="btn btn-primary" onclick="openAddPersonnelModal()">
                        <i class="fas fa-plus"></i> Add Personnel
                    </button>
                    <form method="POST" action="" style="display:inline;"
                        onsubmit="return confirm('Are you sure you want to reset ALL personnel statistics? This will set completed counts to 0 and ratings to 5.0.');">
                        <input type="hidden" name="reset_personnel_stats" value="1">
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset Stats
                        </button>
                    </form>
                    <div class="result-count">
                        <?php echo count($personnel_list); ?> personnel available
                    </div>
                </div>
            </div>

            <?php if (!empty($personnel_list)): ?>
                <div
                    style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-top: 20px;">
                    <?php foreach ($personnel_list as $person): ?>
                        <div class="personnel-card"
                            style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; transition: all 0.3s; <?php echo $person['is_available'] ? '' : 'opacity: 0.7;'; ?>">
                            <div style="display: flex; align-items: flex-start; gap: 15px;">
                                <div
                                    style="width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #1F3A93, #3a56b5); color: white; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 600; flex-shrink: 0;">
                                    <?php echo strtoupper(substr($person['name'], 0, 1)); ?>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                        <h3 style="margin: 0; color: #1a317d; font-size: 16px; font-weight: 600;">
                                            <?php echo htmlspecialchars($person['name']); ?>
                                        </h3>
                                        <?php if ($person['is_available']): ?>
                                            <?php if (isset($person['is_busy']) && $person['is_busy']): ?>
                                                <span
                                                    style="background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 600;">Unavailable
                                                    (In Progress)</span>
                                            <?php else: ?>
                                                <span
                                                    style="background: #d1fae5; color: #065f46; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 600;">Available</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span
                                                style="background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 600;">Unavailable</span>
                                        <?php endif; ?>
                                        <div style="flex: 1;"></div>
                                        <form method="POST" action=""
                                            onsubmit="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($person['name']); ?>?');"
                                            style="margin: 0;">
                                            <input type="hidden" name="delete_personnel_id"
                                                value="<?php echo $person['id']; ?>">
                                            <input type="hidden" name="delete_personnel" value="1">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete Personnel"
                                                style="padding: 2px 8px; font-size: 12px; border-radius: 4px;">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>

                                    <div style="display: flex; align-items: center; gap: 5px; margin-top: 5px;">
                                        <?php
                                        $icon_class = getCategoryIcon($person['specialization_icon'] ?? '');
                                        ?>
                                        <i class="<?php echo $icon_class; ?>" style="color: #1F3A93; font-size: 12px;"></i>
                                        <span
                                            style="color: #6b7280; font-size: 13px;"><?php echo htmlspecialchars($person['specialization_name']); ?>
                                            Specialist</span>
                                    </div>

                                    <div style="margin-top: 8px;">
                                        <?php echo displayPersonnelRating($person['star_rating']); ?>
                                    </div>

                                    <?php if (!empty($person['description'])): ?>
                                        <p style="margin: 10px 0 0; color: #6b7280; font-size: 12px; line-height: 1.4;">
                                            <?php echo htmlspecialchars(substr($person['description'], 0, 80)); ?>...
                                        </p>
                                    <?php endif; ?>

                                    <div
                                        style="display: flex; gap: 15px; margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb;">
                                        <div style="text-align: center;">
                                            <div style="font-size: 18px; font-weight: 700; color: #f59e0b;">
                                                <?php echo $person['pending_count']; ?>
                                            </div>
                                            <div style="font-size: 10px; color: #6b7280;">Pending</div>
                                        </div>
                                        <div style="text-align: center;">
                                            <div style="font-size: 18px; font-weight: 700; color: #3b82f6;">
                                                <?php echo $person['in_progress_count']; ?>
                                            </div>
                                            <div style="font-size: 10px; color: #6b7280;">In Progress</div>
                                        </div>
                                        <div style="text-align: center;">
                                            <div style="font-size: 18px; font-weight: 700; color: #10b981;">
                                                <?php echo $person['total_completed']; ?>
                                            </div>
                                            <div style="font-size: 10px; color: #6b7280;">Completed</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users-slash"></i>
                    <h3>No Personnel Found</h3>
                    <p>No personnel have been added to the system yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal for Feedback Details -->
    <div id="feedbackModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-eye"></i> Feedback Details</h2>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-grid">
                    <div class="modal-item">
                        <div class="modal-label"><i class="fas fa-user"></i> User</div>
                        <div class="modal-value" id="modalUser"></div>
                        <div class="modal-value" id="modalPurok"
                            style="font-size: 0.9em; color: #6b7280; margin-top: 4px;"></div>
                    </div>
                    <div class="modal-item">
                        <div class="modal-label"><i class="fas fa-star"></i> Rating</div>
                        <div class="modal-value" id="modalRating"></div>
                    </div>
                    <div class="modal-item">
                        <div class="modal-label"><i class="fas fa-smile"></i> Sentiment</div>
                        <div class="modal-value" id="modalSentiment"></div>
                    </div>
                    <div class="modal-item">
                        <div class="modal-label"><i class="fas fa-check-circle"></i> Status</div>
                        <div class="modal-value" id="modalStatus"></div>
                    </div>
                    <div class="modal-item">
                        <div class="modal-label"><i class="far fa-calendar-alt"></i> Date</div>
                        <div class="modal-value" id="modalDate"></div>
                    </div>
                    <div class="modal-item">
                        <div class="modal-label"><i class="fas fa-comment"></i> Comment</div>
                        <div class="modal-value" id="modalComment"></div>
                    </div>
                    <div class="modal-item" id="modalResolutionNotesContainer" style="display: none;">
                        <div class="modal-label"><i class="fas fa-sticky-note"></i> Resolution Notes</div>
                        <div class="modal-value" id="modalResolutionNotes"></div>
                    </div>
                    <div class="modal-item" id="modalAttachmentContainer" style="display: none;">
                        <div class="modal-label"><i class="fas fa-paperclip"></i> Attachment</div>
                        <div class="modal-value" id="modalAttachment"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary close-modal">Close</button>
            </div>
        </div>
    </div>

    <!-- Modal for Resolve Confirmation -->
    <div id="resolveModal" class="modal resolve-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-check-circle"></i> Mark as Resolved</h2>
                <button class="close-btn">&times;</button>
            </div>
            <form id="resolveForm" method="POST" action="">
                <div class="modal-body">
                    <div class="resolve-form">
                        <div id="resolveHeader"
                            style="margin-bottom: 20px; padding: 15px; border-radius: 8px; border-left: 4px solid #1F3A93; background: #f0f7ff;">
                            <p style="margin: 0; color: #1a317d; font-weight: 500;">
                                <i class="fas fa-info-circle"></i>
                                You are about to mark this
                                <span id="resolveSentiment" style="font-weight: 600;"></span>
                                feedback as resolved.
                            </p>
                        </div>

                        <div class="form-group">
                            <label for="resolved_by">Handler's Name *</label>
                            <input type="text" id="resolved_by" name="resolved_by"
                                placeholder="Enter name of person who handled the feedback" required
                                value="<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : ''; ?>">
                            <small style="color: #6b7280; font-size: 12px; margin-top: 5px; display: block;">
                                This name will be recorded along with the resolution date.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="resolution_notes">Resolution Notes (Optional)</label>
                            <textarea id="resolution_notes" name="resolution_notes"
                                placeholder="Add any notes about how this feedback was resolved..."></textarea>
                            <small style="color: #6b7280; font-size: 12px; margin-top: 5px; display: block;">
                                Optional: Add details about actions taken or outcomes.
                            </small>
                        </div>

                        <input type="hidden" id="resolve_id" name="resolve_id" value="">
                        <input type="hidden" name="resolve_feedback" value="1">

                        <div id="resolvePreview"
                            style="background: #f9fafb; padding: 15px; border-radius: 8px; margin-top: 15px; border-left: 4px solid #f59e0b;">
                            <p style="margin: 0; font-size: 14px; color: #4b5563;">
                                <strong>Feedback Preview:</strong> <span id="resolveComment"></span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-resolve-modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Mark as Resolved
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Assignment Management Modal -->
    <div id="assignmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-tasks"></i> Manage Assignment</h2>
                <button class="close-btn" onclick="closeAssignmentModal()">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div
                        style="background: #f0f7ff; padding: 15px; border-radius: 8px; border-left: 4px solid #1F3A93; margin-bottom: 20px;">
                        <h4 style="margin: 0 0 10px 0; color: #1a317d;">Current Assignment</h4>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <strong id="assignPersonnelName" style="font-size: 16px;"></strong>
                                <div id="assignPersonnelSpec" style="font-size: 13px; color: #6b7280;"></div>
                            </div>
                            <div id="assignCurrentStatus"></div>
                        </div>
                    </div>

                    <div class="form-group" id="reassignContainer"
                        style="display: none; background: #fffbeb; padding: 10px; border-radius: 6px; border: 1px solid #fcd34d; margin-bottom: 15px;">
                        <label for="reassignSelect" style="color: #92400e;">Reassign To (Optional)</label>
                        <select class="form-control" id="reassignSelect" name="new_personnel_id">
                            <option value="">-- Auto-select Best Available --</option>
                        </select>
                        <small style="color: #92400e; margin-top: 5px; display: block;">
                            <i class="fas fa-info-circle"></i>
                            Leave specific selection empty to let the system auto-select.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="assignStatus">Update Status</label>
                        <select class="form-control" id="assignStatus" name="status">
                            <option value="Pending">Pending</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Resolved">Resolved</option>
                            <option value="Waiting">Waiting</option>
                        </select>
                        <small style="color: #6b7280; margin-top: 5px; display: block;">
                            <i class="fas fa-info-circle"></i>
                            Marking as "Resolved" will automatically verify the feedback resolution.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="assignNotes">Admin Notes (Optional)</label>
                        <textarea class="form-control" id="assignNotes" name="notes" rows="3"
                            placeholder="Add notes about this status update..."></textarea>
                    </div>

                    <input type="hidden" name="assignment_id" id="assignId">
                    <input type="hidden" name="update_assignment_status" value="1">
                </div>
                <div class="modal-footer" style="justify-content: space-between;">
                    <div>
                        <button type="button" id="reassignBtn" class="btn btn-warning" style="margin-right: auto;">
                            <i class="fas fa-random"></i> Reassign
                        </button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary" onclick="closeAssignmentModal()">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Update Status
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Personnel Modal -->
    <div id="addPersonnelModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Add New Personnel</h2>
                <button class="close-btn" onclick="closeAddPersonnelModal()">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="p_name">Full Name *</label>
                        <input type="text" class="form-control" id="p_name" name="name" required
                            placeholder="e.g. Juan Dela Cruz">
                    </div>

                    <div class="form-group">
                        <label for="p_specialization">Specialization *</label>
                        <select class="form-control" id="p_specialization" name="specialization_id" required>
                            <?php
                            // Fetch categories for specialization dropdown
                            if ($categories_result && $categories_result->num_rows > 0) {
                                $categories_result->data_seek(0);
                                while ($cat = $categories_result->fetch_assoc()) {
                                    echo '<option value="' . $cat['id'] . '">' . htmlspecialchars($cat['name']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="p_description">Description / Role (Optional)</label>
                        <textarea class="form-control" id="p_description" name="description" rows="3"
                            placeholder="Brief description of responsibilities..."></textarea>
                    </div>

                    <input type="hidden" name="add_personnel" value="1">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddPersonnelModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Personnel
                    </button>
                </div>
            </form>
        </div>
    </div>

    </form>
    </div>
    </div>

    <script>
        // Inject Personnel List
        const allPersonnel = <?php echo json_encode($personnel_list); ?>;

        // DOM Elements
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggleBtn');
        const mobileToggleBtn = document.getElementById('mobileToggleBtn');
        const mainContent = document.getElementById('mainContent');
        const menuLinks = document.querySelectorAll('.menu-link');
        const overlay = document.getElementById('overlay');

        // Modal Elements
        const modal = document.getElementById('feedbackModal');
        const modalUser = document.getElementById('modalUser');
        const modalPurok = document.getElementById('modalPurok');
        const modalRating = document.getElementById('modalRating');
        const modalComment = document.getElementById('modalComment');
        const modalSentiment = document.getElementById('modalSentiment');
        const modalStatus = document.getElementById('modalStatus');
        const modalDate = document.getElementById('modalDate');
        const modalResolutionNotes = document.getElementById('modalResolutionNotes');
        const modalResolutionNotesContainer = document.getElementById('modalResolutionNotesContainer');
        const modalAttachment = document.getElementById('modalAttachment');
        const modalAttachmentContainer = document.getElementById('modalAttachmentContainer');

        // Resolve Modal Elements
        const resolveModal = document.getElementById('resolveModal');
        const resolveForm = document.getElementById('resolveForm');
        const resolveIdInput = document.getElementById('resolve_id');
        const resolveCommentPreview = document.getElementById('resolveComment');
        const resolveSentimentDisplay = document.getElementById('resolveSentiment');
        const resolveHeader = document.getElementById('resolveHeader');
        const resolvedByInput = document.getElementById('resolved_by');
        const resolutionNotesInput = document.getElementById('resolution_notes');

        // Delete Modal Elements
        const deleteModal = document.getElementById('deleteFeedbackModal');
        const deleteFeedbackForm = document.getElementById('deleteFeedbackForm');
        const deleteFeedbackId = document.getElementById('delete_feedback_id');
        const deleteFeedbackInfo = document.getElementById('deleteFeedbackInfo');
        const closeDeleteModalBtn = document.getElementById('closeDeleteModalBtn');
        const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

        // Bulk Delete Modal Elements
        const bulkDeleteModal = document.getElementById('bulkDeleteModal');
        const bulkDeleteCount = document.getElementById('bulkDeleteCount');
        const bulkDeleteCountBtn = document.getElementById('bulkDeleteCountBtn');
        const bulkDeleteItems = document.getElementById('bulkDeleteItems');
        const bulkDeleteForm = document.getElementById('bulkDeleteForm');
        const closeBulkDeleteModalBtn = document.getElementById('closeBulkDeleteModalBtn');
        const cancelBulkDeleteBtn = document.getElementById('cancelBulkDeleteBtn');
        // ========== LOGOUT MODAL FUNCTIONALITY ==========
        // Add this at the end of existing JavaScript

        // Get logout modal elements
        const logoutModal = document.getElementById('logoutModal');
        const logoutTrigger = document.getElementById('logoutTrigger');
        const closeLogoutModalBtn = document.getElementById('closeLogoutModal');
        const cancelLogoutBtn = document.getElementById('cancelLogout');

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

        // Event listeners for logout modal
        if (logoutTrigger) {
            logoutTrigger.addEventListener('click', openLogoutModal);
        }

        if (closeLogoutModalBtn) {
            closeLogoutModalBtn.addEventListener('click', closeLogoutModal);
        }

        if (cancelLogoutBtn) {
            cancelLogoutBtn.addEventListener('click', closeLogoutModal);
        }

        // Close logout modal when clicking outside
        if (logoutModal) {
            logoutModal.addEventListener('click', function (e) {
                if (e.target === logoutModal) {
                    closeLogoutModal();
                }
            });
        }

        // Close logout modal with Escape key
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

        // Function to show delete modal
        function showDeleteModal(id, username, email, comment, sentiment, category, date) {
            deleteFeedbackId.value = id;

            // Populate feedback info in modal
            deleteFeedbackInfo.innerHTML = `
                <h5>Feedback Details:</h5>
                <div class="info-row">
                    <span class="info-label">ID:</span>
                    <span class="info-value">${id}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">User:</span>
                    <span class="info-value">${username}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">${email}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Category:</span>
                    <span class="info-value">${category}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Sentiment:</span>
                    <span class="info-value"><span class="sentiment-badge sentiment-${sentiment.toLowerCase()}">${sentiment}</span></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date:</span>
                    <span class="info-value">${date}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Comment:</span>
                    <span class="info-value" style="white-space: normal;">${comment.substring(0, 80)}...</span>
                </div>
            `;

            // Update form action to include current query parameters
            const currentParams = new URLSearchParams(window.location.search);
            deleteFeedbackForm.action = 'manage_feedback.php?' + currentParams.toString();

            deleteModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

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

        // Modal Functions
        // Assignment Modal
        // Assignment Modal
        function openAssignmentModal(data) {
            document.getElementById('assignId').value = data.assignment_id;

            // Handle unassigned/waiting state
            if (data.assignment_status === 'Waiting' || !data.personnel_name) {
                document.getElementById('assignPersonnelName').textContent = 'Unassigned';
                document.getElementById('assignPersonnelName').style.color = '#b45309';
                document.getElementById('assignPersonnelSpec').textContent = 'Waiting for Personnel';
            } else {
                document.getElementById('assignPersonnelName').textContent = data.personnel_name;
                document.getElementById('assignPersonnelName').style.color = '#1a317d';
                document.getElementById('assignPersonnelSpec').textContent = data.personnel_specialization;
            }

            // Set current status badge
            let badge = '';
            let status = data.assignment_status;
            if (status === 'Pending') {
                badge = '<span class="status-badge status-pending" style="background: #fef3c7; color: #92400e;"><i class="fas fa-clock"></i> Pending</span>';
            } else if (status === 'In Progress') {
                badge = '<span class="status-badge" style="background: #dbeafe; color: #1e40af;"><i class="fas fa-spinner fa-spin"></i> In Progress</span>';
            } else if (status === 'Resolved') {
                badge = '<span class="status-badge status-resolved"><i class="fas fa-check-circle"></i> Resolved</span>';
            } else if (status === 'Waiting') {
                badge = '<span class="status-badge status-waiting" style="background: #fee2e2; color: #991b1b;"><i class="fas fa-hourglass-half"></i> Waiting</span>';
            } else {
                badge = `<span class="status-badge">${status}</span>`;
            }
            document.getElementById('assignCurrentStatus').innerHTML = badge;

            // Set select value
            const select = document.getElementById('assignStatus');
            select.value = status;

            document.getElementById('assignmentModal').style.display = 'flex';

            document.getElementById('assignmentModal').style.display = 'flex';

            // Reset Reassign Dropdown
            const reassignContainer = document.getElementById('reassignContainer');
            const reassignSelect = document.getElementById('reassignSelect');
            reassignContainer.style.display = 'none';
            reassignSelect.innerHTML = '<option value="">-- Auto-select Best Available --</option>';

            // Show/Hide Reassign Button based on status
            const reassignBtn = document.getElementById('reassignBtn');
            if (status === 'In Progress' || status === 'Resolved') {
                reassignBtn.style.display = 'none';
            } else {
                reassignBtn.style.display = 'inline-block';

                // Populate Reassign Dropdown (filtered by category, exclude current)
                // Use feedback category_name to match personnel specialization_name
                const feedbackCategory = data.category_name;
                const currentName = data.personnel_name || ''; // Could be empty if Waiting

                const availablePersonnel = allPersonnel.filter(p =>
                    p.specialization_name === feedbackCategory &&
                    p.name !== currentName &&
                    p.is_available == 1
                );

                availablePersonnel.forEach(p => {
                    const option = document.createElement('option');
                    option.value = p.id;
                    option.textContent = `${p.name} (${p.star_rating}⭐)`;
                    reassignSelect.appendChild(option);
                });
            }
        }

        function closeAssignmentModal() {
            document.getElementById('assignmentModal').style.display = 'none';
        }



        // Add Personnel Modal Functions
        function openAddPersonnelModal() {
            document.getElementById('addPersonnelModal').style.display = 'flex';
        }

        function closeAddPersonnelModal() {
            document.getElementById('addPersonnelModal').style.display = 'none';
        }

        function showModal(feedback) {
            modalUser.innerHTML = feedback.user;
            modalPurok.textContent = feedback.purok;
            modalRating.innerHTML = feedback.rating;
            modalComment.textContent = feedback.comment;
            modalDate.textContent = feedback.date;

            // Create sentiment badge
            const sentimentClass = feedback.sentiment === 'Positive' ? 'sentiment-positive' :
                feedback.sentiment === 'Negative' ? 'sentiment-negative' : 'sentiment-neutral';
            modalSentiment.innerHTML = `<span class="sentiment-badge ${sentimentClass}">${feedback.sentiment}</span>`;

            // Create status badge and resolution info
            let statusHtml = '';
            if (feedback.isResolved) {
                statusHtml = `<span class="status-badge status-resolved"><i class="fas fa-check-circle"></i> Resolved</span>`;
                if (feedback.resolvedBy) {
                    statusHtml += `<br><small style="color: #6b7280; font-size: 12px; margin-top: 5px; display: block;">
                        <i class="fas fa-user-check"></i> Resolved by ${feedback.resolvedBy}
                        ${feedback.resolvedDate ? ' on ' + feedback.resolvedDate : ''}
                    </small>`;
                }

                // Show resolution notes if available
                if (feedback.resolutionNotes) {
                    modalResolutionNotes.textContent = feedback.resolutionNotes;
                    modalResolutionNotesContainer.style.display = 'block';
                } else {
                    modalResolutionNotesContainer.style.display = 'none';
                }
            } else {
                statusHtml = `<span style="color: #6b7280; font-size: 14px;"><i class="fas fa-clock"></i> Under Review</span>`;
                modalResolutionNotesContainer.style.display = 'none';
            }
            modalStatus.innerHTML = statusHtml;

            // Show attachment if available
            if (feedback.attachmentPath) {
                modalAttachment.innerHTML = `<img src="../${feedback.attachmentPath}" alt="Attachment" style="max-width: 100%; max-height: 400px; border-radius: 8px; border: 1px solid #e5e7eb; display: block; margin: 0 auto;">`;
                modalAttachmentContainer.style.display = 'block';
            } else {
                modalAttachmentContainer.style.display = 'none';
            }

            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }

        // Delete Modal Functions
        function showDeleteModal(id, username, email, comment, sentiment, category, date) {
            deleteFeedbackId.value = id;

            // Populate feedback info in modal
            deleteFeedbackInfo.innerHTML = `
                <h5>Feedback Details:</h5>
                <div class="info-row">
                    <span class="info-label">Feedback ID:</span>
                    <span class="info-value">${id}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">User:</span>
                    <span class="info-value">${username}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">${email}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Category:</span>
                    <span class="info-value">${category}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Sentiment:</span>
                    <span class="info-value"><span class="sentiment-badge sentiment-${sentiment.toLowerCase()}">${sentiment}</span></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date:</span>
                    <span class="info-value">${date}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Comment:</span>
                    <span class="info-value" style="max-width: 200px; white-space: normal;">${comment}...</span>
                </div>
            `;

            // Update form action to include current query parameters
            const currentParams = new URLSearchParams(window.location.search);
            deleteFeedbackForm.action = 'manage_feedback.php?' + currentParams.toString();

            deleteModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteModal() {
            deleteModal.classList.remove('show');
            document.body.style.overflow = '';
        }

        // Bulk Delete Modal Functions
        function showBulkDeleteModal() {
            const checkboxes = document.querySelectorAll('.row-checkbox:checked');

            if (checkboxes.length === 0) {
                alert('Please select feedback to delete.');
                return;
            }

            const ids = Array.from(checkboxes).map(cb => cb.value);
            bulkDeleteForm.querySelector('input[name="bulk_delete_ids"]').value = ids.join(',');
            bulkDeleteCount.textContent = ids.length;
            bulkDeleteCountBtn.textContent = ids.length;

            // Clear previous items
            bulkDeleteItems.innerHTML = '';

            // Populate with selected feedback items
            ids.forEach(id => {
                const row = document.getElementById('row-' + id);
                if (row) {
                    const username = row.querySelector('.user-name').textContent;
                    const email = row.querySelector('.user-email').textContent;
                    const comment = row.querySelector('.comment-preview').getAttribute('title') ||
                        row.querySelector('.comment-preview').textContent.replace('...', '');
                    const date = row.querySelector('.date-cell').textContent;

                    const item = document.createElement('div');
                    item.className = 'bulk-delete-item';
                    item.innerHTML = `
                        <div class="bulk-delete-info">
                            <span class="bulk-delete-id">ID: ${id}</span>
                            <span class="bulk-delete-user">${username} (${email})</span>
                            <div class="bulk-delete-comment" title="${comment}">${comment.substring(0, 60)}...</div>
                        </div>
                        <small class="date-cell">${date}</small>
                    `;
                    bulkDeleteItems.appendChild(item);
                }
            });

            // Update form action
            const currentParams = new URLSearchParams(window.location.search);
            bulkDeleteForm.action = 'manage_feedback.php?' + currentParams.toString();

            bulkDeleteModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeBulkDeleteModal() {
            bulkDeleteModal.classList.remove('show');
            document.body.style.overflow = '';
        }

        // Resolve Modal Functions
        function showResolveModal(id, comment, sentiment, personnelName) {
            resolveIdInput.value = id;
            resolveCommentPreview.textContent = comment;
            resolveSentimentDisplay.textContent = sentiment;

            // Set handler name: Use assigned personnel if available, otherwise keep default (session user)
            if (personnelName && personnelName.trim() !== '') {
                resolvedByInput.value = personnelName;
            } else {
                // Reset to session user if no personnel assigned (optional, or keep generic)
                resolvedByInput.value = "<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : ''; ?>";
            }

            // Update header color based on sentiment
            const headerColors = {
                'Positive': '#1F3A93',
                'Negative': '#ef4444',
                'Neutral': '#f59e0b'
            };

            const borderColor = headerColors[sentiment] || '#1F3A93';
            resolveHeader.style.borderLeftColor = borderColor;
            resolveHeader.style.background = sentiment === 'Positive' ? '#f0f7ff' :
                sentiment === 'Negative' ? '#fef2f2' : '#fef3c7';

            // Add current query parameters to the form action
            const currentParams = new URLSearchParams(window.location.search);
            resolveForm.action = 'manage_feedback.php?' + currentParams.toString();

            resolveModal.classList.add('show');
            document.body.style.overflow = 'hidden';

            // Focus on the input field
            setTimeout(() => {
                resolvedByInput.focus();
            }, 300);
        }

        function closeResolveModal() {
            resolveModal.classList.remove('show');
            document.body.style.overflow = '';
        }

        function showDetails(id, attachmentPath) {
            const row = document.getElementById('row-' + id);
            if (!row) return;

            // Get the data from the table row
            const userCell = row.querySelector('.user-info-cell');
            const ratingCell = row.querySelector('.rating-stars');
            const commentPreview = row.closest('tr').querySelector('.comment-preview');
            const sentimentBadge = row.querySelector('.sentiment-badge');
            const dateCell = row.querySelector('.date-cell');

            // Get the full comment from title attribute
            const fullComment = commentPreview.getAttribute('title') ||
                commentPreview.textContent.replace('...', '');

            // Get user info
            const userName = userCell.querySelector('.user-name').textContent;
            const userEmail = userCell.querySelector('.user-email').textContent;
            const userPurok = userCell.querySelector('.user-purok').textContent;

            // Get resolved info if exists
            const resolvedInfo = userCell.querySelector('.resolved-info');
            const resolutionNotesPreview = userCell.querySelector('.resolution-preview');

            let resolvedBy = '';
            let resolvedDate = '';
            let resolutionNotes = '';

            if (resolvedInfo) {
                const resolvedText = resolvedInfo.textContent;
                if (resolvedText.includes('Resolved by')) {
                    const match = resolvedText.match(/Resolved by (.+?)( on (.+))?$/);
                    if (match) {
                        resolvedBy = match[1];
                        if (match[3]) {
                            resolvedDate = match[3].trim();
                        }
                    }
                }
            }

            if (resolutionNotesPreview) {
                resolutionNotes = resolutionNotesPreview.getAttribute('title') ||
                    resolutionNotesPreview.textContent.replace('...', '').replace('', '').trim();
            }

            // Check if feedback is resolved
            const isResolved = row.classList.contains('resolved-row');

            const feedback = {
                user: `${userName}<br><small style="color: #6b7280;">${userEmail}</small>`,
                purok: userPurok ? `${userPurok}` : '',
                rating: ratingCell.innerHTML,
                comment: fullComment,
                sentiment: sentimentBadge ? sentimentBadge.textContent : 'Unknown',
                isResolved: isResolved,
                resolvedBy: resolvedBy,
                resolvedDate: resolvedDate,
                resolutionNotes: resolutionNotes,
                date: dateCell ? dateCell.textContent : '',
                attachmentPath: attachmentPath
            };

            showModal(feedback);
        }



        // Update select all checkbox when individual checkboxes change
        document.getElementById('selectAll').addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Update select all checkbox when individual checkboxes are clicked
        document.querySelectorAll('.row-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                const allCheckboxes = document.querySelectorAll('.row-checkbox');
                const checkedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
                document.getElementById('selectAll').checked =
                    allCheckboxes.length === checkedCheckboxes.length;
            });
        });

        // Event Listeners for delete buttons
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const username = this.getAttribute('data-username');
                const email = this.getAttribute('data-email');
                const comment = this.getAttribute('data-comment');
                const sentiment = this.getAttribute('data-sentiment');
                const category = this.getAttribute('data-category');
                const date = this.getAttribute('data-date');

                showDeleteModal(id, username, email, comment, sentiment, category, date);
            });
        });

        // Event Listeners for resolve buttons
        document.querySelectorAll('.resolve-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const comment = this.getAttribute('data-comment');
                const sentiment = this.getAttribute('data-sentiment');
                const personnel = this.getAttribute('data-personnel');
                showResolveModal(id, comment, sentiment, personnel);
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
        document.querySelectorAll('.close-btn, .close-modal').forEach(btn => {
            btn.addEventListener('click', closeModal);
        });

        document.querySelectorAll('.close-resolve-modal').forEach(btn => {
            btn.addEventListener('click', closeResolveModal);
        });

        // Delete modal event listeners
        closeDeleteModalBtn.addEventListener('click', closeDeleteModal);
        cancelDeleteBtn.addEventListener('click', closeDeleteModal);

        // Bulk delete modal event listeners
        closeBulkDeleteModalBtn.addEventListener('click', closeBulkDeleteModal);
        cancelBulkDeleteBtn.addEventListener('click', closeBulkDeleteModal);

        // Close modal when clicking outside
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        const asmModal = document.getElementById('assignmentModal');
        asmModal.addEventListener('click', function (e) {
            if (e.target === asmModal) {
                closeAssignmentModal();
            }
        });

        // Reassign button handler
        document.getElementById('reassignBtn').addEventListener('click', function () {
            const reassignContainer = document.getElementById('reassignContainer');

            // Toggle dropdown visibility
            if (reassignContainer.style.display === 'none') {
                reassignContainer.style.display = 'block';
                return; // Just show the dropdown first
            }

            const assignmentId = document.getElementById('assignId').value;
            const reassignSelect = document.getElementById('reassignSelect');
            const selectedPerson = reassignSelect.value;
            const selectedName = reassignSelect.options[reassignSelect.selectedIndex].text;

            let msg = 'Are you sure you want to reassign this feedback?';
            if (selectedPerson) {
                msg = `Are you sure you want to reassign this feedback to ${selectedName}?`;
            } else {
                msg = 'Are you sure you want to reassign this feedback to the best available personnel?';
            }

            if (confirm(msg)) {
                // Create a hidden form to submit the reassignment request
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';

                const inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'assignment_id';
                inputId.value = assignmentId;

                const inputTrigger = document.createElement('input');
                inputTrigger.type = 'hidden';
                inputTrigger.name = 'reassign_personnel';
                inputTrigger.value = '1';

                form.appendChild(inputId);
                form.appendChild(inputTrigger);

                if (selectedPerson) {
                    const inputPerson = document.createElement('input');
                    inputPerson.type = 'hidden';
                    inputPerson.name = 'new_personnel_id';
                    inputPerson.value = selectedPerson;
                    form.appendChild(inputPerson);
                }

                document.body.appendChild(form);
                form.submit();
            }
        });



        const addPModal = document.getElementById('addPersonnelModal');
        addPModal.addEventListener('click', function (e) {
            if (e.target === addPModal) {
                closeAddPersonnelModal();
            }
        });

        resolveModal.addEventListener('click', function (e) {
            if (e.target === resolveModal) {
                closeResolveModal();
            }
        });

        deleteModal.addEventListener('click', function (e) {
            if (e.target === deleteModal) {
                closeDeleteModal();
            }
        });

        bulkDeleteModal.addEventListener('click', function (e) {
            if (e.target === bulkDeleteModal) {
                closeBulkDeleteModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                if (modal.classList.contains('show')) {
                    closeModal();
                }
                if (resolveModal.classList.contains('show')) {
                    closeResolveModal();
                }
                if (deleteModal.classList.contains('show')) {
                    closeDeleteModal();
                }
                if (bulkDeleteModal.classList.contains('show')) {
                    closeBulkDeleteModal();
                }
            }
        });

        // Form validation for resolve form
        resolveForm.addEventListener('submit', function (e) {
            const resolvedBy = resolvedByInput.value.trim();
            if (!resolvedBy) {
                e.preventDefault();
                alert('Please enter the name of the person who handled the feedback.');
                resolvedByInput.focus();
                return;
            }

            // Confirm before submitting
            const sentiment = resolveSentimentDisplay.textContent;
            if (!confirm(`Are you sure you want to mark this ${sentiment.toLowerCase()} feedback as resolved?`)) {
                e.preventDefault();
            }
        });

        // Confirm before deleting in modal
        deleteFeedbackForm.addEventListener('submit', function (e) {
            if (!confirm('Are you absolutely sure you want to delete this feedback? This action cannot be undone.')) {
                e.preventDefault();
            }
        });

        // Confirm before bulk deleting in modal
        bulkDeleteForm.addEventListener('submit', function (e) {
            const count = bulkDeleteCount.textContent;
            if (!confirm(`Are you absolutely sure you want to delete ${count} feedback items? This action cannot be undone.`)) {
                e.preventDefault();
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

        // Set current date
        const now = new Date();
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };
        document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', options);

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

        // Function to update mobile selection
        function updateMobileSelection() {
            const mobileCheckboxes = document.querySelectorAll('.mobile-row-checkbox:checked');
            const desktopCheckboxes = document.querySelectorAll('.row-checkbox');

            // Sync mobile checkboxes with desktop checkboxes
            mobileCheckboxes.forEach(mobileCb => {
                const id = mobileCb.value;
                const desktopCb = document.querySelector(`.row-checkbox[value="${id}"]`);
                if (desktopCb) {
                    desktopCb.checked = mobileCb.checked;
                }
            });

            // Update select all checkbox
            const allDesktopCheckboxes = document.querySelectorAll('.row-checkbox');
            const allMobileCheckboxes = document.querySelectorAll('.mobile-row-checkbox');
            const checkedDesktop = document.querySelectorAll('.row-checkbox:checked').length;
            const checkedMobile = document.querySelectorAll('.mobile-row-checkbox:checked').length;

            document.getElementById('selectAll').checked =
                (allDesktopCheckboxes.length > 0 && allDesktopCheckboxes.length === checkedDesktop) ||
                (allMobileCheckboxes.length > 0 && allMobileCheckboxes.length === checkedMobile);
        }

        // Function to select all mobile items
        function selectAllMobile() {
            const mobileCheckboxes = document.querySelectorAll('.mobile-row-checkbox');
            const desktopCheckboxes = document.querySelectorAll('.row-checkbox');
            const selectAll = document.getElementById('selectAll');
            const isChecked = selectAll.checked;

            mobileCheckboxes.forEach(cb => {
                cb.checked = !isChecked;
            });

            desktopCheckboxes.forEach(cb => {
                cb.checked = !isChecked;
            });

            selectAll.checked = !isChecked;
        }

        // Update existing selectAll function
        function selectAll() {
            if (window.innerWidth <= 768) {
                selectAllMobile();
            } else {
                const checkboxes = document.querySelectorAll('.row-checkbox');
                const selectAll = document.getElementById('selectAll');
                const isChecked = selectAll.checked;

                checkboxes.forEach(checkbox => {
                    checkbox.checked = !isChecked;
                });

                // Also update mobile checkboxes if they exist
                const mobileCheckboxes = document.querySelectorAll('.mobile-row-checkbox');
                mobileCheckboxes.forEach(checkbox => {
                    checkbox.checked = !isChecked;
                });

                selectAll.checked = !isChecked;
            }
        }

        // Update event listeners for delete buttons on mobile cards
        document.addEventListener('DOMContentLoaded', function () {
            // This will be handled by the existing delete-btn event listeners
            // as they use event delegation on dynamically created content

            // Add event listener for mobile checkboxes
            document.addEventListener('change', function (e) {
                if (e.target.classList.contains('mobile-row-checkbox')) {
                    updateMobileSelection();
                }
            });

            // Update mobile view when window resizes
            window.addEventListener('resize', function () {
                // Sync checkbox states when switching views
                if (window.innerWidth <= 768) {
                    // Mobile view - sync desktop checkboxes to mobile
                    const desktopCheckboxes = document.querySelectorAll('.row-checkbox');
                    desktopCheckboxes.forEach(dc => {
                        const id = dc.value;
                        const mobileCb = document.querySelector(`.mobile-row-checkbox[value="${id}"]`);
                        if (mobileCb) {
                            mobileCb.checked = dc.checked;
                        }
                    });
                } else {
                    // Desktop view - sync mobile checkboxes to desktop
                    const mobileCheckboxes = document.querySelectorAll('.mobile-row-checkbox');
                    mobileCheckboxes.forEach(mc => {
                        const id = mc.value;
                        const desktopCb = document.querySelector(`.row-checkbox[value="${id}"]`);
                        if (desktopCb) {
                            desktopCb.checked = mc.checked;
                        }
                    });
                }
            });
        });

        // Update deleteSelected function to work with mobile view
        function deleteSelected() {
            let checkboxes;

            if (window.innerWidth <= 768) {
                // Mobile view
                checkboxes = document.querySelectorAll('.mobile-row-checkbox:checked');
            } else {
                // Desktop view
                checkboxes = document.querySelectorAll('.row-checkbox:checked');
            }

            if (checkboxes.length === 0) {
                alert('Please select feedback to delete.');
                return;
            }

            if (checkboxes.length === 1) {
                // Single item
                const id = checkboxes[0].value;
                let deleteBtn;

                if (window.innerWidth <= 768) {
                    // Mobile view button
                    deleteBtn = document.querySelector(`#mobile-row-${id} .delete-btn`);
                } else {
                    // Desktop view button
                    deleteBtn = document.querySelector(`.delete-btn[data-id="${id}"]`);
                }

                if (deleteBtn) {
                    deleteBtn.click();
                }
            } else {
                // Multiple items
                showBulkDeleteModal();
            }
        }

        setupTooltips();
    </script>
    <script src="../js/theme.js"></script>
</body>

</html>