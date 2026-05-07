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

// Pagination and filtering
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$user_filter = isset($_GET['user']) ? $_GET['user'] : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';

require_once 'connection.php';

// Count pending registrations (for badge)
$pending_reg_count = 0;
$reg_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM users WHERE status = 'pending'");
$reg_stmt->execute();
$pending_reg_count = $reg_stmt->get_result()->fetch_assoc()['cnt'];

// Build query with filters
$where_conditions = ["l1.action = 'LOGIN'"];
$params = [];
$types = '';

if (!empty($user_filter)) {
    $where_conditions[] = "l1.user_name LIKE ?";
    $params[] = '%' . $user_filter . '%';
    $types .= 's';
}

if (!empty($from_date)) {
    $where_conditions[] = "DATE(l1.created_at) >= ?";
    $params[] = $from_date;
    $types .= 's';
}

if (!empty($to_date)) {
    $where_conditions[] = "DATE(l1.created_at) <= ?";
    $params[] = $to_date;
    $types .= 's';
}

if (!empty($role_filter)) {
    $where_conditions[] = "u.role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM user_logs l1 LEFT JOIN users u ON l1.user_name = u.username $where_clause";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_logs = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_logs / $per_page);

// Fetch logs with pagination
$logs_query = "
    SELECT 
        l1.id,
        l1.user_name,
        u.idNo,
        l1.created_at as time_in,
        (
            SELECT MIN(l2.created_at)
            FROM user_logs l2
            WHERE l2.user_name = l1.user_name 
            AND l2.action = 'LOGOUT'
            AND l2.created_at >= l1.created_at
        ) as time_out,
        l1.device,
        l1.browser,
        l1.ip_address,
        u.role as user_role
    FROM user_logs l1
    LEFT JOIN users u ON l1.user_name = u.username
    $where_clause
    ORDER BY l1.created_at DESC 
    LIMIT ? OFFSET ?
";
$logs_stmt = $conn->prepare($logs_query);

$pagination_params = array_merge($params, [$per_page, $offset]);
$pagination_types = $types . 'ii';

if (!empty($pagination_params)) {
    $logs_stmt->bind_param($pagination_types, ...$pagination_params);
}
$logs_stmt->execute();
$logs_result = $logs_stmt->get_result();
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
    <title>AUTHENTICATION LOGS - STEAM Vladimir Lahora</title>
</head>

<body class="admin-body">
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i> Menu
    </button>

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
            <a href="dashboard.php" class="nav-item"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="user_management.php" class="nav-item"><i class="fas fa-users-cog"></i> User Management</a>
            <a href="pending_registrations.php" class="nav-item">
                <i class="fas fa-user-clock"></i> Pending Accounts
                <?php if ($pending_reg_count > 0): ?>
                    <span class="badge-count" style="background:#cd5434; color:#fff; font-size:11px; font-weight:700; min-width:20px; height:20px; border-radius:2px; padding:0 6px; margin-left:6px; display:inline-flex; align-items:center; justify-content:center;"><?php echo $pending_reg_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="user_logs.php" class="nav-item active"><i class="fas fa-history"></i> Logs</a>
            <a href="profile.php" class="nav-item"><i class="fas fa-user-circle"></i> Profile</a>
        </nav>
        <div class="logout-section">
            <form method="POST" action="logout.php">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>
    </aside>

    <main class="main-content">
        <div class="content-header" style="margin-bottom: 30px;">
            <h1><i class="fas fa-shield-alt" style="color:#66c0f4; margin-right:8px;"></i> Authentication Logs</h1>
            <p>Monitor system security activity and user session history.</p>
        </div>

        <!-- Filters Section -->
        <section class="filters-section" style="background: rgba(0,0,0,0.2); padding: 20px; border-radius: 4px; margin-bottom: 30px;">
            <h2 class="section-title" style="color: #fff; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px;">
                <i class="fas fa-filter" style="color: #66c0f4; margin-right: 10px;"></i> Search & Filters
            </h2>

            <form method="GET" class="filter-form" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                <div class="filter-group" style="flex: 1; min-width: 150px; display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-size: 11px; color: #8f98a0; font-weight: 700; text-transform: uppercase;">User Name:</label>
                    <input type="text" name="user" id="user" value="<?php echo htmlspecialchars($user_filter); ?>" placeholder="Username..." style="padding: 10px; background: #23262e; border: 1px solid #000; border-radius: 2px; color: white;">
                </div>

                <div class="filter-group" style="width: 130px; display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-size: 11px; color: #8f98a0; font-weight: 700; text-transform: uppercase;">Role:</label>
                    <select name="role" id="role" style="padding: 10px; background: #23262e; border: 1px solid #000; border-radius: 2px; color: white;">
                        <option value="">All Roles</option>
                        <option value="super_admin" <?php echo $role_filter === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                        <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="customer" <?php echo $role_filter === 'customer' ? 'selected' : ''; ?>>Customer</option>
                    </select>
                </div>

                <div class="filter-group" style="width: 150px; display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-size: 11px; color: #8f98a0; font-weight: 700; text-transform: uppercase;">From Date:</label>
                    <input type="date" name="from_date" id="from_date" value="<?php echo htmlspecialchars($from_date); ?>" style="padding: 10px; background: #23262e; border: 1px solid #000; border-radius: 2px; color: white;">
                </div>

                <div class="filter-group" style="width: 150px; display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-size: 11px; color: #8f98a0; font-weight: 700; text-transform: uppercase;">To Date:</label>
                    <input type="date" name="to_date" id="to_date" value="<?php echo htmlspecialchars($to_date); ?>" style="padding: 10px; background: #23262e; border: 1px solid #000; border-radius: 2px; color: white;">
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="steam-btn" style="height: 40px; padding: 0 20px;">APPLY</button>
                    <a href="user_logs.php" class="steam-btn steam-btn-secondary" style="height: 40px; padding: 0 20px; display: flex; align-items: center; text-decoration: none;">CLEAR</a>
                </div>
            </form>
        </section>

        <!-- Logs Table Section -->
        <section class="logs-section">
            <h2 class="section-title" style="color: #fff; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px;">
                <i class="fas fa-list-alt" style="color: #66c0f4; margin-right: 10px;"></i> System Records
                <?php if ($total_logs > 0): ?>
                    <span style="font-size: 12px; color: #8f98a0; margin-left:10px;">(<?php echo number_format($total_logs); ?> total)</span>
                <?php endif; ?>
            </h2>

            <div class="table-container">
                <table class="steam-table">
                    <thead>
                        <tr>
                            <th>USER</th>
                            <th>ROLE</th>
                            <th>DATE</th>
                            <th>LOGIN</th>
                            <th>LOGOUT</th>
                            <th>DEVICE / BROWSER</th>
                            <th>IP ADDRESS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($logs_result && $logs_result->num_rows > 0): ?>
                            <?php while ($log = $logs_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $log['user_name'] ? htmlspecialchars($log['user_name']) : 'System'; ?></strong>
                                        <div style="font-size: 12px; color: #6b7280;">ID:
                                            <?php echo htmlspecialchars($log['idNo'] ?? 'Unknown'); ?></div>
                                    </td>
                                    <td>
                                        <span
                                            class="role-badge role-<?php echo htmlspecialchars($log['user_role'] ?? 'unknown'); ?>">
                                            <?php echo htmlspecialchars($log['user_role'] ?? 'Unknown'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($log['time_in'])); ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-sign-in-alt" style="color: #10b981; font-size: 11px;"></i>
                                        <?php echo date('h:i:s A', strtotime($log['time_in'])); ?>
                                    </td>
                                    <td>
                                        <?php if ($log['time_out']): ?>
                                            <i class="fas fa-sign-out-alt" style="color: #ef4444; font-size: 11px;"></i>
                                            <?php echo date('h:i:s A', strtotime($log['time_out'])); ?>
                                        <?php else: ?>
                                            <span class="status-badge status-active">Still Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color: #4b5563; font-size: 13px;">
                                        <?php
                                        $os_name = $log['device'] ?? 'Unknown';
                                        if (strpos($os_name, 'Device: ') === 0)
                                            $os_name = substr($os_name, 8);
                                        $os_name = str_replace([' PC', ' Computer'], '', $os_name);
                                        if ($os_name === 'Localhost Development' || $os_name === 'Localhost')
                                            $os_name = 'Windows';

                                        $browser_raw = $log['browser'] ?? 'Unknown';
                                        $browser_name = preg_replace('/\s+[\d\.]+$/', '', $browser_raw);
                                        $browser_name = str_replace(['Google ', 'Mozilla ', 'Microsoft '], '', $browser_name);
                                        if (empty($browser_name) || $browser_name === 'Unknown')
                                            $browser_name = 'Browser';

                                        echo '<span style="font-weight:600;">' . htmlspecialchars($os_name) . '</span>';
                                        echo ' <span style="color:#d1d5db;">/</span> ';
                                        echo htmlspecialchars($browser_name);
                                        ?>
                                    </td>
                                    <td>
                                        <code
                                            style="background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-size: 12px; color: #374151;">
                                                    <?php
                                                    $ip = $log['ip_address'] ?? 'N/A';
                                                    echo $ip === '::1' ? '127.0.0.1' : htmlspecialchars($ip);
                                                    ?>
                                                </code>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: #6b7280;">
                                    No activity logs found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination" style="display: flex; justify-content: flex-end; margin-top: 20px; gap: 8px;">
                    <?php if ($page > 1): ?>
                        <a class="pagination-link"
                            href="?page=<?php echo $page - 1; ?>&user=<?php echo urlencode($user_filter); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&role=<?php echo urlencode($role_filter); ?>">&laquo;
                            Previous</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="pagination-link current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a class="pagination-link"
                                href="?page=<?php echo $i; ?>&user=<?php echo urlencode($user_filter); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&role=<?php echo urlencode($role_filter); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a class="pagination-link"
                            href="?page=<?php echo $page + 1; ?>&user=<?php echo urlencode($user_filter); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&role=<?php echo urlencode($role_filter); ?>">Next
                            &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
    </script>
    <script src="../javascript/dashboard.js"></script>
</body>

</html>