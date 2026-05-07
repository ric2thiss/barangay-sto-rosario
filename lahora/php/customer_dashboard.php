<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if user is customer (redirect admins to admin dashboard)
if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: dashboard.php');
    exit();
}

// Get user information
$username = $_SESSION['username'];
$user_id_session = $_SESSION['user_id'] ?? ''; // This is the idNo (e.g., "2026-0001")

// Fetch user ID and profile information from database
require_once 'connection.php';
$user_query = "SELECT id, idNo, firstName, lastName, emailAddress, profile_picture FROM users WHERE username = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("s", $username);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();

// Get the actual user IDs from database
$user_id = $user_data['id'] ?? 0;           // Auto-increment ID (e.g., 6)
$user_idNo = $user_data['idNo'] ?? '';       // ID Number string (e.g., "2026-0001")

// Debug: Log user ID information
error_log("Session user_id (idNo): " . $user_id_session . ", Database id: " . $user_id . ", Database idNo: " . $user_idNo);

// Use session user_id if no idNo from database
if (empty($user_idNo) && !empty($user_id_session)) {
    $user_idNo = $user_id_session;
    error_log("Using session user_id as fallback for idNo: " . $user_idNo);
}

// Set profile data
$profile = $user_data;

// If no user found, redirect to login
if (!$user_data || $user_id == 0) {
    header('Location: login.php');
    exit();
}



// Handle profile update (inherited from original)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $email = $_POST['email'] ?? '';
    $errors = [];

    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    if (empty($errors)) {
        // Check if email is already taken by another user
        $email_check_query = "SELECT id FROM users WHERE emailAddress = ? AND id != ?";
        $email_check_stmt = $conn->prepare($email_check_query);
        $email_check_stmt->bind_param("ss", $email, $user_id);
        $email_check_stmt->execute();
        $email_check_result = $email_check_stmt->get_result();

        if ($email_check_result->num_rows > 0) {
            $errors[] = 'Email is already taken by another user';
        } else {
            // Update email
            $update_query = "UPDATE users SET emailAddress = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ss", $email, $user_id);

            if ($update_stmt->execute()) {
                require_once 'user_logger.php';
                logAction('UPDATE_PROFILE', "User {$username} updated their profile email to {$email}");
                $success_message = "Profile updated successfully!";
                $profile['emailAddress'] = $email;
            } else {
                $errors[] = "Failed to update profile. Please try again.";
            }
        }
    }
}

// Handle profile picture upload (inherited from original)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_picture') {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = 'Only JPG, PNG, and GIF images are allowed';
        } elseif ($file['size'] > $max_size) {
            $errors[] = 'File size must be less than 5MB';
        } else {
            $upload_dir = '../uploads/profile_pictures/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'user_' . $user_id . '_' . time() . '.' . $extension;
            $filepath = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $update_pic_query = "UPDATE users SET profile_picture = ? WHERE id = ?";
                $update_pic_stmt = $conn->prepare($update_pic_query);
                $update_pic_stmt->bind_param("ss", $filename, $user_id);

                if ($update_pic_stmt->execute()) {
                    require_once 'user_logger.php';
                    logAction('UPDATE_PROFILE_PICTURE', "User {$username} updated their profile picture");
                    $success_message = "Profile picture updated successfully!";
                    $profile['profile_picture'] = $filename;
                } else {
                    $errors[] = "Failed to update profile picture in database.";
                }
            } else {
                $errors[] = "Failed to upload profile picture.";
            }
        }
    } else {
        $errors[] = "Please select a valid image file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/steam_theme.css">
    <link rel="stylesheet" type="text/css" href="../css/customer_dashboard.css">
    <title>LIBRARY - STEAM Vladimir Lahora</title>
</head>

<body class="library-body">
    <header class="steam-header">
        <div class="steam-logo-container">
            <img src="../IMAGE/Steam_icon_logo.svg.png" alt="Steam Logo" class="steam-logo-img">
            <span class="steam-brand-text">STEAM</span>
        </div>
        <nav class="steam-nav">
            <a href="landingpage.php">HOME</a>
        </nav>
    </header>

    <div class="library-container">
        <!-- Sidebar -->
        <aside class="library-sidebar">
            <div class="sidebar-search">
                <input type="text" placeholder="Search by name">
            </div>
            <div class="sidebar-list">
                <div class="sidebar-group">
                    <span class="group-title">NAVIGATION</span>
                    <a href="landingpage.php" class="sidebar-item">
                        <i class="fas fa-home"></i>
                        <span>Home</span>
                    </a>
                    <a href="customer_dashboard.php" class="sidebar-item active">
                        <i class="fas fa-th-large"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="profile.php" class="sidebar-item">
                        <i class="fas fa-user-circle"></i>
                        <span>Profile</span>
                    </a>
                    <a href="logout.php" class="sidebar-item" onclick="return confirm('Are you sure you want to logout?')">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content (Steam Game Details Style) -->
        <main class="library-main">
            <div class="game-header">
                <div class="game-banner">
                    <img src="../IMAGE/steam_share_image.jpg" alt="Banner">
                    <div class="game-banner-overlay">
                        <div class="game-title-info">
                            <h1>Vladimir Lahora Portal</h1>
                            <div class="game-stats">
                                <span><i class="fas fa-clock"></i> LAST ACCESSED: Today</span>
                                <span><i class="fas fa-hourglass-half"></i> TIME SPENT: <?php echo rand(10, 100); ?> hours</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="game-actions">
                    <a href="profile.php" class="steam-btn play-btn">MANAGE PROFILE</a>
                    <div class="action-icons">
                        <i class="far fa-star"></i>
                        <i class="fas fa-info-circle"></i>
                        <i class="fas fa-cog"></i>
                    </div>
                </div>
            </div>

            <div class="game-content-grid">
                <div class="content-left">
                    <section class="activity-section">
                        <h3>RECENT ACTIVITY</h3>
                        <div class="activity-card">
                            <div class="activity-icon"><i class="fas fa-user-edit"></i></div>
                            <div class="activity-info">
                                <span class="activity-date">Today</span>
                                <p>You accessed the management portal</p>
                            </div>
                        </div>
                    </section>

                    <section class="achievements-section">
                        <h3>FRIENDS WHO PLAY</h3>
                        <div class="friends-list">
                            <div class="friend-item" title="Vladimir Lahora">
                                <div class="friend-avatar">VL</div>
                            </div>
                            <div class="friend-item">
                                <div class="friend-avatar">+</div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="content-right">
                    <section class="info-section">
                        <h3>ACCOUNT STATUS</h3>
                        <div class="status-box">
                            <div class="status-row">
                                <span class="label">STATUS:</span>
                                <span class="value active">ACTIVE</span>
                            </div>
                            <div class="status-row">
                                <span class="label">ID NO:</span>
                                <span class="value"><?php echo htmlspecialchars($user_data['idNo']); ?></span>
                            </div>
                            <div class="status-row">
                                <span class="label">EMAIL:</span>
                                <span class="value"><?php echo htmlspecialchars($user_data['emailAddress']); ?></span>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>

    <footer class="steam-footer">
        <div class="footer-content">
            <div class="footer-left">
                <img src="../IMAGE/footerLogo_valve_new.png" alt="Valve Logo" class="footer-logo-valve">
                <p class="footer-text">&copy; <?php echo date("Y"); ?> Vladimir Lahora. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="../javascript/customer_dashboard.js"></script>
</body>

</html>