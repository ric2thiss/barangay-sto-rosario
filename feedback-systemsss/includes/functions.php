<?php
// Common functions used across the system
function checkMaintenanceMode() {
    global $conn;
    
    // Skip check for admin users
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
        return true;
    }
    
    // Get maintenance mode status
    $sql = "SELECT value FROM settings WHERE name = 'maintenance_mode'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $maintenance_mode = $row['value'];
        
        if ($maintenance_mode == '1') {
            // Allow access for admins (already handled above)
            // Check if user is logged in
            if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
                // Determine correct path to maintenance.php
                $redirect_path = file_exists('maintenance.php') ? 'maintenance.php' : '../maintenance.php';
                header("Location: $redirect_path");
                exit();
            }
        }
    }
    
    return true;
}
// Get category icon class
function getCategoryIcon($icon_char) {
    $icon_map = [
        '💧' => 'fas fa-tint',
        '🗑️' => 'fas fa-trash-alt',
        '💡' => 'fas fa-lightbulb',
        '🛣️' => 'fas fa-road',
        '🧹' => 'fas fa-broom',
        '📋' => 'fas fa-clipboard-list'
    ];
    
    return isset($icon_map[$icon_char]) ? $icon_map[$icon_char] : 'fas fa-clipboard';
}

// Display rating stars
function displayRating($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= $i <= $rating ? '⭐' : '☆';
    }
    return $stars . ' (' . $rating . '/5)';
}

// Get sentiment badge class
// function getSentimentBadge($sentiment) {
//     $class = strtolower($sentiment);
//     return "<span class='badge badge-$class'>$sentiment</span>";
// }
// Add to your functions.php file
function getSentimentBadge($sentiment) {
    $badges = [
        'Positive' => '<span style="background: #1F3A93; color: white; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Positive</span>',
        'Negative' => '<span style="background: #ef4444; color: white; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Negative</span>',
        'Neutral' => '<span style="background: #f59e0b; color: white; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Neutral</span>'
    ];
    
    return $badges[$sentiment] ?? '<span style="background: #6b7280; color: white; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Unknown</span>';
}

// Format date
function formatDate($date) {
    return date('M d, Y H:i', strtotime($date));
}

// Language Helper Functions
function loadLanguage() {
    $lang = $_COOKIE['app_lang'] ?? 'en';
    $valid_langs = ['en', 'bisaya'];
    
    if (!in_array($lang, $valid_langs)) {
        $lang = 'en';
    }
    
    $lang_file = __DIR__ . "/languages/{$lang}.php";
    if (file_exists($lang_file)) {
        return require $lang_file;
    }
    return require __DIR__ . "/languages/en.php";
}

function __($key) {
    global $lang_strings;
    if (!isset($lang_strings)) {
        $lang_strings = loadLanguage();
    }
    return $lang_strings[$key] ?? $key;
}

// Initialize language strings
$lang_strings = loadLanguage();

// ========== PERSONNEL MANAGEMENT FUNCTIONS ==========

/**
 * Get available personnel by specialization (category)
 */
function getAvailablePersonnel($conn, $category_id, $exclude_id = null) {
    // Start building query
    $sql = "SELECT * FROM personnel WHERE specialization_id = ? AND is_available = 1";
    $types = "i";
    $params = [$category_id];

    // Exclude specific ID if provided
    if ($exclude_id) {
        $sql .= " AND id != ?";
        $types .= "i";
        $params[] = $exclude_id;
    }
    
    // Exclude personnel who are currently 'In Progress'
    // We check if their ID exists in assignments with 'In Progress' status
    $sql .= " AND id NOT IN (
        SELECT personnel_id FROM feedback_assignments 
        WHERE status = 'In Progress' AND personnel_id IS NOT NULL
    )";
    
    $sql .= " ORDER BY star_rating DESC, total_completed ASC LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Assign personnel to a feedback
 */
function assignPersonnelToFeedback($conn, $feedback_id, $personnel_id = null) {
    // Check if already assigned
    $check_sql = "SELECT id FROM feedback_assignments WHERE feedback_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $feedback_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        return false; // Already assigned
    }
    
    $status = 'Pending';
    if ($personnel_id === null) {
        $status = 'Waiting';
        $sql = "INSERT INTO feedback_assignments (feedback_id, personnel_id, status, assigned_at) VALUES (?, NULL, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $feedback_id, $status);
    } else {
        $sql = "INSERT INTO feedback_assignments (feedback_id, personnel_id, status, assigned_at) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $feedback_id, $personnel_id, $status);
    }
    
    return $stmt->execute();
}

/**
 * Update assignment status
 */
function updateAssignmentStatus($conn, $assignment_id, $status, $admin_notes = null) {
    $updates = ["status = ?"];
    $params = [$status];
    $types = "s";
    
    if ($status == 'In Progress') {
        $updates[] = "started_at = NOW()";
    } elseif ($status == 'Resolved') {
        $updates[] = "completed_at = NOW()";
    }
    
    if ($admin_notes !== null) {
        $updates[] = "admin_notes = ?";
        $params[] = $admin_notes;
        $types .= "s";
    }
    
    $params[] = $assignment_id;
    $types .= "i";
    
    $sql = "UPDATE feedback_assignments SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $success = $stmt->execute();
    
    // If resolved, update personnel rating
    if ($success && $status == 'Resolved') {
        // Get assignment details
        $get_sql = "SELECT fa.personnel_id, f.rating FROM feedback_assignments fa 
                    JOIN feedback f ON fa.feedback_id = f.id WHERE fa.id = ?";
        $get_stmt = $conn->prepare($get_sql);
        $get_stmt->bind_param("i", $assignment_id);
        $get_stmt->execute();
        $assignment = $get_stmt->get_result()->fetch_assoc();
        if ($assignment) {
            updatePersonnelRating($conn, $assignment['personnel_id']);
        }
    }
    
    return $success;
}

/**
 * Get assignment details for a feedback
 */
function getAssignmentByFeedback($conn, $feedback_id) {
    $sql = "SELECT fa.*, p.name as personnel_name, p.star_rating as personnel_rating, 
            c.name as specialization_name
            FROM feedback_assignments fa 
            JOIN personnel p ON fa.personnel_id = p.id 
            JOIN categories c ON p.specialization_id = c.id
            WHERE fa.feedback_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $feedback_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Update personnel star rating based on completed assignments
 */
function updatePersonnelRating($conn, $personnel_id) {
    // Calculate average rating from completed feedback
    $sql = "SELECT AVG(f.rating) as avg_rating, COUNT(*) as total 
            FROM feedback_assignments fa 
            JOIN feedback f ON fa.feedback_id = f.id 
            WHERE fa.personnel_id = ? AND fa.status = 'Resolved'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $personnel_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    $avg_rating = $result['avg_rating'] ?? 0;
    $total_completed = $result['total'] ?? 0;
    
    $update_sql = "UPDATE personnel SET star_rating = ?, total_completed = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("dii", $avg_rating, $total_completed, $personnel_id);
    return $update_stmt->execute();
}

/**
 * Get all personnel with their specialization info
 */
function getAllPersonnel($conn) {
    $sql = "SELECT p.*, c.name as specialization_name, c.icon as specialization_icon,
            (SELECT COUNT(*) FROM feedback_assignments fa WHERE fa.personnel_id = p.id AND fa.status = 'Pending') as pending_count,
            (SELECT COUNT(*) FROM feedback_assignments fa WHERE fa.personnel_id = p.id AND fa.status = 'In Progress') as in_progress_count,
            (SELECT COUNT(*) FROM feedback_assignments fa WHERE fa.personnel_id = p.id AND fa.status = 'In Progress') > 0 as is_busy
            FROM personnel p 
            JOIN categories c ON p.specialization_id = c.id 
            ORDER BY p.star_rating DESC, p.name ASC";
    $result = $conn->query($sql);
    $personnel = [];
    while ($row = $result->fetch_assoc()) {
        $personnel[] = $row;
    }
    return $personnel;
}

/**
 * Auto-assign personnel to negative feedback
 */
function autoAssignPersonnel($conn, $feedback_id, $category_id, $rating, $sentiment) {
    // Only auto-assign for negative feedback
    // If rating is provided (> 0), use existing logic (rating <= 2 or negative sentiment)
    // If rating is NOT provided (0 or null), use ONLY sentiment
    
    if ($rating > 0) {
        if ($rating > 2 && $sentiment !== 'Negative') {
            return false;
        }
    } else {
        // No rating provided, rely solely on sentiment
        if ($sentiment !== 'Negative') {
            return false;
        }
    }
    
    // Find available personnel for this category
    $personnel = getAvailablePersonnel($conn, $category_id);
    
    if ($personnel) {
        return assignPersonnelToFeedback($conn, $feedback_id, $personnel['id']);
    } else {
        // No personnel available - Assign to Waiting (NULL personnel)
        return assignPersonnelToFeedback($conn, $feedback_id, null);
    }
    
    return false;
}

/**
 * Display personnel star rating
 */
function displayPersonnelRating($rating) {
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
    
    $html = '<span class="personnel-rating">';
    for ($i = 0; $i < $full_stars; $i++) {
        $html .= '<i class="fas fa-star" style="color: #fbbf24;"></i>';
    }
    if ($half_star) {
        $html .= '<i class="fas fa-star-half-alt" style="color: #fbbf24;"></i>';
    }
    for ($i = 0; $i < $empty_stars; $i++) {
        $html .= '<i class="far fa-star" style="color: #d1d5db;"></i>';
    }
    $html .= ' <span style="color: #6b7280; font-size: 12px;">(' . number_format($rating, 1) . ')</span></span>';
    
    return $html;
}

/**
 * Get assignment status badge HTML
 */
function getAssignmentStatusBadge($status) {
    $badges = [
        'Pending' => '<span style="background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;"><i class="fas fa-clock"></i> Pending</span>',
        'In Progress' => '<span style="background: #dbeafe; color: #1e40af; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;"><i class="fas fa-spinner fa-spin"></i> In Progress</span>',
        'Resolved' => '<span style="background: #d1fae5; color: #065f46; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;"><i class="fas fa-check-circle"></i> Resolved</span>',
        'Waiting' => '<span style="background: #fee2e2; color: #991b1b; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;"><i class="fas fa-hourglass-half"></i> Waiting for Personnel</span>'
    ];
    
    return $badges[$status] ?? '<span style="background: #e5e7eb; color: #4b5563; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;">Unknown</span>';
}

/**
 * Add new personnel
 */
function addPersonnel($conn, $name, $specialization_id, $description) {
    $sql = "INSERT INTO personnel (name, specialization_id, description, is_available, star_rating, total_completed) 
            VALUES (?, ?, ?, 1, 0.0, 0)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sis", $name, $specialization_id, $description);
    return $stmt->execute();
}

/**
 * Reset personnel statistics
 */
function resetPersonnelStats($conn) {
    // Reset total_completed to 0 and reset rating to 0.0
    $sql = "UPDATE personnel SET total_completed = 0, star_rating = 0.0";
    return $conn->query($sql);
}

/**
 * Reassign personnel to feedback
 * Returns: array ['success' => bool, 'message' => string]
 */
function reassignPersonnel($conn, $assignment_id, $specific_personnel_id = null) {
    // Get current assignment details
    $sql = "SELECT fa.*, f.category_id 
            FROM feedback_assignments fa 
            JOIN feedback f ON fa.feedback_id = f.id 
            WHERE fa.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    
    if (!$current) {
        return ['success' => false, 'message' => 'Assignment not found.'];
    }
    
    // Check if status is 'In Progress' - Do NOT reassign if so
    if ($current['status'] == 'In Progress') {
        return ['success' => false, 'message' => 'Cannot reassign feedback that is already In Progress.'];
    }
    
    $new_personnel = null;
    
    if ($specific_personnel_id) {
        // Manual reassignment: Verify the specific personnel
        $check_sql = "SELECT * FROM personnel WHERE id = ? AND is_available = 1";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $specific_personnel_id);
        $check_stmt->execute();
        $new_personnel = $check_stmt->get_result()->fetch_assoc();
        
        if (!$new_personnel) {
            return ['success' => false, 'message' => 'Selected personnel is not available or does not exist.'];
        }
    } else {
        // Auto reassignment: Find alternative personnel (excluding current)
        $new_personnel = getAvailablePersonnel($conn, $current['category_id'], $current['personnel_id']);
    }
    
    if ($new_personnel) {
        // Found new personnel - Update assignment
        $update_sql = "UPDATE feedback_assignments SET personnel_id = ?, status = 'Pending', assigned_at = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $new_personnel['id'], $assignment_id);
        
        if ($update_stmt->execute()) {
            return ['success' => true, 'message' => 'Reassigned to ' . $new_personnel['name']];
        } else {
            return ['success' => false, 'message' => 'Database error during reassignment.'];
        }
    } else {
        // No alternative personnel found - Set to Waiting with NULL personnel
        $update_sql = "UPDATE feedback_assignments SET status = 'Waiting', personnel_id = NULL WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $assignment_id);
        
        if ($update_stmt->execute()) {
            return ['success' => true, 'message' => 'No other personnel available. Status set to Waiting.'];
        } else {
            return ['success' => false, 'message' => 'Database error updating status.'];
        }
    }
}

/**
 * Delete personnel
 * Returns: array ['success' => bool, 'message' => string]
 */
function deletePersonnel($conn, $personnel_id) {
    // Check for existing assignments
    $sql = "SELECT COUNT(*) as count FROM feedback_assignments WHERE personnel_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $personnel_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        return ['success' => false, 'message' => 'Cannot delete personnel with associated assignments. Please reassign their tasks first.'];
    }
    
    // Proceed with deletion
    $sql = "DELETE FROM personnel WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $personnel_id);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Personnel deleted successfully.'];
    } else {
        return ['success' => false, 'message' => 'Database error during deletion.'];
    }
}
/**
 * Handle file upload
 * Returns: array ['success' => bool, 'path' => string|null, 'message' => string]
 */
function handleFileUpload($file, $upload_dir = '../uploads/feedback_attachments/') {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'path' => null, 'message' => 'No file uploaded'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'path' => null, 'message' => 'File upload error code: ' . $file['error']];
    }

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);

    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'path' => null, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.'];
    }

    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'path' => null, 'message' => 'File too large. Maximum size is 5MB.'];
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('feedback_') . '_' . time() . '.' . $extension;
    
    // Create directory if not exists
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $target_path = $upload_dir . $filename;
    
    // Determine relative path for DB storage (relative to root)
    // If $upload_dir starts with '../', remove it for DB storage if we want path from root
    // But usually we store path relative to where it's accessed or from root.
    // Let's store: uploads/feedback_attachments/filename.jpg
    $db_path = str_replace('../', '', $target_path);

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return ['success' => true, 'path' => $db_path, 'message' => 'File uploaded successfully'];
    } else {
        return ['success' => false, 'path' => null, 'message' => 'Failed to move uploaded file.'];
    }
}
?>