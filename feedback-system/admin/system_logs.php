<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireAdmin();

// Fetch Logs
$sql = "SELECT al.*, a.username, a.firstname, a.lastname 
        FROM activity_logs al 
        LEFT JOIN admins a ON al.admin_id = a.id 
        ORDER BY al.created_at DESC LIMIT 500";
$logs_result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - Resident Feedback and Survey System</title>
    <link rel="icon" href="../img/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin_dark_mode.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <style>
        /* Baseline CSS similar to other admin pages */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { display: flex; min-height: 100vh; background-color: #f8fafc; transition: all 0.3s ease; }
        
        /* SIDEBAR (reduced CSS for brevity but maintains exact look) */
        .sidebar { width: 280px; background: #1F3A93; color: #ffffff; height: 100vh; padding: 20px 0; position: fixed; left: 0; top: 0; transition: all 0.4s; z-index: 100; overflow-y: auto; display: flex; flex-direction: column; }
        .logo-area { padding: 0 20px 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); margin-bottom: 25px; }
        .logo-wrapper { display: flex; align-items: center; justify-content: space-between; }
        .logo { display: flex; align-items: center; gap: 12px; }
        .logo-icon { width: 45px; height: 45px; border-radius: 12px; background: white; padding: 2px; }
        .logo-icon img { width: 100%; height: 100%; object-fit: contain; }
        .logo-text { font-size: 20px; font-weight: 700; color: #ffffff; }
        
        .login-indicator { padding: 0 20px; margin-bottom: 30px; }
        .user-info { display: flex; align-items: center; gap: 12px; padding: 15px; background: rgba(255, 255, 255, 0.05); border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.1); }
        .user-avatar { width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #ffffff, #bae6fd); display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 600; color: #1F3A93; }
        .user-details { overflow: hidden; }
        .user-name { font-weight: 600; font-size: 16px; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-role { font-size: 13px; color: rgba(255, 255, 255, 0.8); }
        
        .menu-items { list-style: none; padding: 0 15px; flex: 1; margin-bottom:0 !important; }
        .menu-item { margin-bottom: 5px; }
        .menu-link { display: flex; align-items: center; gap: 15px; padding: 15px; color: rgba(255, 255, 255, 0.7); text-decoration: none; border-radius: 10px; transition: all 0.3s; }
        .menu-link:hover { background: rgba(255, 255, 255, 0.1); color: #ffffff; }
        .menu-link.active { background: #ffffff; color: #1F3A93; font-weight: 600; }
        .menu-icon { font-size: 20px; width: 24px; text-align: center; }
        
        .logout-section { padding: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1); margin-top: auto; }
        .logout-link { display: flex; align-items: center; gap: 15px; padding: 15px; color: rgba(255, 255, 255, 0.9); text-decoration: none; border-radius: 10px; border: 1px solid rgba(255, 255, 255, 0.1); }
        .logout-link:hover { background: #ef4444; border-color: #ef4444; color: #ffffff; }
        
        /* content */
        .main-content { flex: 1; padding: 30px; margin-left: 280px; min-height: 100vh; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: white; padding: 20px 25px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03); }
        .page-title { font-size: 28px; color: #1a317d; font-weight: 700; }
        
        .table-container { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #edf2f7; }
        th { background: #f8fafc; font-weight: 600; color: #1a317d; border-top-left-radius:10px; border-top-right-radius:10px; }
        tr:hover { background: #f8fafc; }
        .badge { display: inline-block; padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; background: #e0e7ff; color: #3730a3; }
        .action-cell { font-weight: 600; color: #ef4444; }
        .action-cell.success { color: #10b981; }
        
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .sidebar .logo-text, .sidebar .user-details, .sidebar .menu-text, .sidebar .logout-text { display: none; }
            .main-content { margin-left: 80px; }
        }
    </style>
</head>
<body>
    <nav class="sidebar" id="sidebar">
        <div class="logo-area">
            <div class="logo-wrapper">
                <div class="logo">
                    <div class="logo-icon"><img src="../img/logo.png" alt="Logo"></div>
                    <div class="logo-text">Admin Panel</div>
                </div>
            </div>
        </div>

        <div class="login-indicator">
            <div class="user-info">
                <div class="user-avatar">
                    <span><?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?></span>
                    <div class="status-dot"></div>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                    <div class="user-role">Admin</div>
                </div>
            </div>
        </div>

        <ul class="menu-items">
            <li class="menu-item"><a href="index.php" class="menu-link" data-tooltip="Dashboard"><i class="fas fa-tachometer-alt menu-icon"></i><span class="menu-text">Dashboard</span></a></li>
            <li class="menu-item"><a href="manage_feedback.php" class="menu-link" data-tooltip="Manage Feedback"><i class="fas fa-list-alt menu-icon"></i><span class="menu-text">Manage Feedback</span></a></li>
            <li class="menu-item"><a href="surveys.php" class="menu-link" data-tooltip="Surveys"><i class="fas fa-poll menu-icon"></i><span class="menu-text">Surveys</span></a></li>
            <li class="menu-item"><a href="admin_settings.php" class="menu-link" data-tooltip="Admin Settings"><i class="fas fa-cog menu-icon"></i><span class="menu-text">Admin Settings</span></a></li>
            <li class="menu-item"><a href="user_management.php" class="menu-link" data-tooltip="User Management"><i class="fas fa-users-cog menu-icon"></i><span class="menu-text">User Management</span></a></li>
            <li class="menu-item"><a href="system_logs.php" class="menu-link active" data-tooltip="System Logs"><i class="fas fa-history menu-icon"></i><span class="menu-text">System Logs</span></a></li>
        </ul>

        <div class="logout-section">
            <a href="logout.php" class="logout-link" data-tooltip="Logout"><i class="fas fa-sign-out-alt menu-icon"></i><span class="logout-text">Logout</span></a>
        </div>
    </nav>

    <main class="main-content">
        <div class="header">
            <h1 class="page-title"><i class="fas fa-history title-icon"></i> System Activity Logs</h1>
            <div style="display: flex; gap: 15px; align-items: center;">
                <div class="date-display"><i class="far fa-calendar-alt"></i> <span id="currentDate"><?php echo date('F j, Y'); ?></span></div>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Admin User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($logs_result && $logs_result->num_rows > 0): ?>
                        <?php while($row = $logs_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></td>
                                <td><?php echo $row['firstname'] ? htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) : 'System'; ?></td>
                                <td><span class="badge"><?php echo htmlspecialchars($row['action']); ?></span></td>
                                <td><?php echo htmlspecialchars($row['details']); ?></td>
                                <td><code><?php echo htmlspecialchars($row['ip_address']); ?></code></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center;">No activity logs found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
