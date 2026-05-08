<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if user has admin or super_admin role
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: landingpage.php');
    exit();
}

// Get user information
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Fetch metric counts
require_once 'connection.php';
$metrics_query = "SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN is_logged_in = 1 THEN 1 ELSE 0 END) as current_logins,
    SUM(CASE WHEN status = 'incomplete' THEN 1 ELSE 0 END) as incomplete_profiles,
    SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked_users
    FROM users";
$metrics_result = $conn->query($metrics_query);
$metrics = $metrics_result->fetch_assoc();
$total_users = $metrics['total_users'] ?? 0;
$current_logins = $metrics['current_logins'] ?? 0;
$incomplete_profiles = $metrics['incomplete_profiles'] ?? 0;
$blocked_users = $metrics['blocked_users'] ?? 0;

// Count pending registrations (for badge)
$pending_reg_count = 0;
$reg_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM users WHERE status = 'pending'");
$reg_stmt->execute();
$pending_reg_count = $reg_stmt->get_result()->fetch_assoc()['cnt'];

// Fetch active logged in users
$active_users_query = "SELECT idNo, firstName, lastName, username, device_used, ip_address FROM users WHERE is_logged_in = 1 ORDER BY lastName, firstName";
$active_users_result = $conn->query($active_users_query);


// Helper function to parse user agent
function getFriendlyDeviceName($user_agent)
{
    if (!$user_agent || $user_agent === 'Unknown')
        return 'Unknown';

    $os = "Unknown OS";
    if (preg_match('/windows|win32/i', $user_agent)) {
        $os = 'Windows';
    } elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
        $os = 'Mac';
    } elseif (preg_match('/android/i', $user_agent)) {
        $os = 'Android';
    } elseif (preg_match('/iphone|ipad|ipod/i', $user_agent)) {
        $os = 'iOS';
    } elseif (preg_match('/linux/i', $user_agent)) {
        $os = 'Linux';
    }

    $browser = "Unknown Browser";
    if (preg_match('/edge|edg/i', $user_agent)) {
        $browser = 'Edge';
    } elseif (preg_match('/opera|opr/i', $user_agent)) {
        $browser = 'Opera';
    } elseif (preg_match('/chrome/i', $user_agent)) {
        $browser = 'Chrome';
    } elseif (preg_match('/safari/i', $user_agent)) {
        $browser = 'Safari';
    } elseif (preg_match('/firefox/i', $user_agent)) {
        $browser = 'Firefox';
    } elseif (preg_match('/msie|trident/i', $user_agent)) {
        $browser = 'IE';
    } elseif (preg_match('/mozilla/i', $user_agent)) {
        $browser = 'Mozilla';
    }

    return $browser . ' / ' . $os;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/steam_theme.css">
    <link rel="stylesheet" type="text/css" href="../css/dashboard.css">
    <title>DASHBOARD - STEAM Vladimir Lahora</title>
</head>

<body class="admin-body">
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i> Menu
    </button>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="steam-logo-container" style="margin-bottom: 20px; justify-content: center;">
                <img src="../IMAGE/Steam_icon_logo.svg.png" alt="Steam Logo" style="height: 40px;">
            </div>
            <div style="color: white; font-weight: 800; font-size: 18px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 2px; text-align: center;">
                STEAM PORTAL
            </div>
            <div class="user-info-sidebar">
                <div style="color: #66c0f4; font-weight: 600;"><?php echo htmlspecialchars($username); ?></div>
                <span class="role-badge" style="background: #2a475e; color: #c7d5e0; font-size: 10px; padding: 2px 8px; border-radius: 2px;"><?php echo strtoupper(htmlspecialchars($role)); ?></span>
            </div>
        </div>

        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-item active">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>

            <a href="user_management.php" class="nav-item">
                <i class="fas fa-users-cog"></i> User Management
            </a>
            <a href="pending_registrations.php" class="nav-item">
                <i class="fas fa-user-clock"></i> Pending Accounts
                <?php if ($pending_reg_count > 0): ?>
                    <span class="badge-count" style="background:#cd5434; color:#fff; font-size:11px; font-weight:700; min-width:20px; height:20px; border-radius:2px; padding:0 6px; margin-left:6px; display:inline-flex; align-items:center; justify-content:center;"><?php echo $pending_reg_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="user_logs.php" class="nav-item">
                <i class="fas fa-history"></i> Logs
            </a>

            <a href="profile.php" class="nav-item">
                <i class="fas fa-user-circle"></i> Profile
            </a>
        </nav>

        <div class="logout-section">
            <form method="POST" action="logout.php">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-header">
            <h1><i class="fas fa-chart-line" style="color:#66c0f4; margin-right:8px;"></i> System Overview</h1>
            <p>Welcome back, <?php echo htmlspecialchars($username); ?>. Monitor system activity and user metrics.</p>
        </div>

        <!-- Dashboard Metrics Cards -->
        <section class="metrics-grid">
            <div class="metric-card">
                <div class="metric-info">
                    <span class="metric-label">TOTAL USERS</span>
                    <span class="metric-value"><?php echo $total_users; ?></span>
                </div>
                <div class="metric-icon blue"><i class="fas fa-users"></i></div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <span class="metric-label">CURRENT LOGINS</span>
                    <span class="metric-value"><?php echo $current_logins; ?></span>
                </div>
                <div class="metric-icon green"><i class="fas fa-user-check"></i></div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <span class="metric-label">INCOMPLETE PROFILES</span>
                    <span class="metric-value"><?php echo $incomplete_profiles; ?></span>
                </div>
                <div class="metric-icon orange"><i class="fas fa-user-edit"></i></div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <span class="metric-label">BLOCKED USERS</span>
                    <span class="metric-value"><?php echo $blocked_users; ?></span>
                </div>
                <div class="metric-icon red"><i class="fas fa-user-slash"></i></div>
            </div>
        </section>

        <!-- Current Active Logins Section -->
        <section class="active-users-box">
            <h2 class="section-title">
                <i class="fas fa-circle" style="color: #5c7e10; font-size: 10px;"></i> CURRENTLY ONLINE
            </h2>

            <div class="table-container">
                <table class="steam-table">
                    <thead>
                        <tr>
                            <th>USER ID</th>
                            <th>FULL NAME</th>
                            <th>USERNAME</th>
                            <th>STATUS</th>
                            <th>DEVICE</th>
                            <th>IP ADDRESS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($active_users_result && $active_users_result->num_rows > 0): ?>
                            <?php while ($active_user = $active_users_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($active_user['idNo']); ?></td>
                                    <td><?php echo htmlspecialchars($active_user['firstName'] . ' ' . $active_user['lastName']); ?></td>
                                    <td><?php echo htmlspecialchars($active_user['username']); ?></td>
                                    <td><span class="status-online">Online</span></td>
                                    <td><?php echo htmlspecialchars(getFriendlyDeviceName($active_user['device_used'])); ?></td>
                                    <td>
                                        <?php
                                        $ip = $active_user['ip_address'] ?? 'Unknown';
                                        echo ($ip === '::1') ? '127.0.0.1' : htmlspecialchars($ip);
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty-table">No active users at the moment.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <div id="toast" class="toast"></div>

    <script src="../javascript/dashboard.js"></script>
</body>

</html>