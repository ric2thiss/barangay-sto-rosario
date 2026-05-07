<?php
session_start();

$is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['username']);
$username = $is_logged_in ? $_SESSION['username'] : 'Guest';
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Check if user is admin/superadmin - still redirect them to dashboard
if ($is_logged_in && isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header("Location: dashboard.php");
    exit();
}

// Debug: Log session information
error_log("Landing page - Session user_id: " . $user_id . ", Username: " . $username);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/steam_theme.css">
    <link rel="stylesheet" href="../css/landingpage.css">
    <title>STEAM - Vladimir Lahora</title>
    <script>
        const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
    </script>
</head>

<body>
    <header class="steam-header">
        <div class="steam-logo-container">
            <img src="../IMAGE/Steam_icon_logo.svg.png" alt="Steam Logo" class="steam-logo-img">
            <span class="steam-brand-text">STEAM</span>
        </div>
        <nav class="steam-nav">
            <?php if ($is_logged_in): ?>
                <a href="landingpage.php">HOME</a>
                <a href="customer_dashboard.php">LIBRARY</a>
                <a href="profile.php">PROFILE</a>
                <a href="logout.php" onclick="return confirm('Are you sure you want to logout?')">LOGOUT</a>
            <?php else: ?>
                <a href="landingpage.php">HOME</a>
                <a href="login.php">LOGIN</a>
                <a href="registration.php">REGISTER</a>
            <?php endif; ?>
        </nav>
    </header>

    <!-- Featured Section (Steam Store Style) -->
    <main class="store-container">
        <section class="featured-section">
            <h2 class="section-title">FEATURED & RECOMMENDED</h2>
            <div class="featured-capsule">
                <div class="capsule-main">
                    <img src="../IMAGE/steam_share_image.jpg" alt="Featured Image">
                </div>
                <div class="capsule-info">
                    <h3>Vladimir Lahora System</h3>
                    <div class="capsule-screenshots">
                        <div class="ss-item"></div>
                        <div class="ss-item"></div>
                        <div class="ss-item"></div>
                        <div class="ss-item"></div>
                    </div>
                    <div class="capsule-tags">
                        <span class="tag">Management</span>
                        <span class="tag">Portal</span>
                        <span class="tag">Professional</span>
                    </div>
                    <div class="capsule-footer">
                        <?php if ($is_logged_in): ?>
                            <a href="customer_dashboard.php" class="steam-btn">Open Library</a>
                        <?php else: ?>
                            <a href="login.php" class="steam-btn">Sign In to Access</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Welcome Section -->
        <section class="welcome-banner">
            <div class="welcome-content">
                <?php if ($is_logged_in): ?>
                    <h2>Welcome back, <?php echo htmlspecialchars($username); ?></h2>
                    <p>Your portal is ready. Check your dashboard for latest updates.</p>
                <?php else: ?>
                    <h2>Experience the new STEAM Portal</h2>
                    <p>Manage your records with ease and security. Sign in or create an account to get started.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="steam-footer">
        <div class="footer-content">
            <div class="footer-left">
                <img src="../IMAGE/footerLogo_valve_new.png" alt="Valve Logo" class="footer-logo-valve">
                <p class="footer-text">&copy; <?php echo date("Y"); ?> Vladimir Lahora. All rights reserved. Steam branding used for demonstration.</p>
            </div>
        </div>
    </footer>

    <script src="../javascript/landingpage.js"></script>
</body>

</html>