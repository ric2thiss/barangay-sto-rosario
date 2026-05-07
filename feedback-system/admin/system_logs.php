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

        /* ========== LOGOUT MODAL STYLES ========== */
        .logout-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(5px); z-index: 9999; align-items: center; justify-content: center; padding: 20px; animation: fadeInOverlay 0.4s ease; }
        .logout-modal-overlay.active { display: flex; }
        .logout-modal-overlay .logout-modal-container { background: white; border-radius: 20px; width: 100%; max-width: 450px; overflow: hidden; box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15); animation: slideInUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1); border: 1px solid #e5e7eb; transform-origin: center bottom; }
        .logout-modal-overlay .logout-modal-header { background: linear-gradient(135deg, #1F3A93, #152c71, #1e3a8a); background-size: 200% 200%; color: white; padding: 28px 30px; display: flex; align-items: center; gap: 18px; position: relative; overflow: hidden; animation: gradientShift 8s ease infinite; }
        .logout-modal-overlay .logout-modal-header::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #1F3A93, #3a56b5, #1F3A93); animation: shimmer 3s linear infinite; background-size: 200% 100%; }
        .logout-modal-overlay .logout-modal-header h2 { font-size: 24px; font-weight: 700; flex: 1; letter-spacing: -0.5px; text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); margin: 0; }
        .logout-modal-overlay .logout-modal-icon { background: rgba(255, 255, 255, 0.2); width: 90px; height: 90px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 34px; backdrop-filter: blur(10px); border: 3px solid #bae6fd; animation: iconPulse 2s ease-in-out infinite; box-shadow: 0 10px 30px rgba(31, 58, 147, 0.15); margin: 0 auto; color: #1F3A93; }
        .logout-modal-overlay .logout-modal-close { position: absolute; top: 20px; right: 20px; background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.1); color: white; width: 38px; height: 38px; border-radius: 12px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); font-size: 20px; backdrop-filter: blur(5px); }
        .logout-modal-overlay .logout-modal-close:hover { background: rgba(255, 255, 255, 0.3); transform: rotate(90deg) scale(1.1); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); }
        .logout-modal-overlay .logout-modal-body { padding: 32px; animation: fadeInBody 0.6s ease 0.2s both; border: 1px solid #e5e7eb; border-radius: 10px; margin: 20px; background: #f9fafb; }
        .logout-modal-overlay .logout-modal-body p { margin-bottom: 16px; line-height: 1.7; color: #4b5563; font-size: 16.5px; }
        .logout-modal-overlay .logout-modal-subtext { font-size: 14.5px; color: #6b7280; font-style: italic; margin-top: 8px; border: 1px solid #d1d5db; border-radius: 6px; padding: 10px 12px; background: rgba(209, 213, 219, 0.1); }
        .logout-modal-overlay .logout-modal-footer { padding: 24px 30px; background: #f9fafb; border-top: 1px solid #e5e7eb; display: flex; gap: 16px; justify-content: flex-end; }
        .logout-modal-overlay .logout-modal-btn { padding: 15px 32px; border-radius: 12px; font-weight: 600; font-size: 15.5px; cursor: pointer; transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); text-decoration: none; display: inline-flex; align-items: center; gap: 10px; border: 2px solid transparent; }
        .logout-modal-overlay .logout-modal-btn-secondary { background: white; color: #6b7280; border-color: #d1d5db; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); }
        .logout-modal-overlay .logout-modal-btn-secondary:hover { background: linear-gradient(135deg, #f0f7ff, #e0f2fe); color: #1a317d; border-color: #86efac; transform: translateY(-3px) scale(1.02); box-shadow: 0 8px 20px rgba(31, 58, 147, 0.15); }
        .logout-modal-overlay .logout-modal-btn-primary { background: linear-gradient(135deg, #1F3A93, #152c71, #1e3a8a); background-size: 200% 200%; color: white; border: none; box-shadow: 0 4px 12px rgba(31, 58, 147, 0.3); animation: gradientShift 8s ease infinite; }
        .logout-modal-overlay .logout-modal-btn-primary:hover { background: linear-gradient(135deg, #152c71, #1e3a8a, #1a317d); transform: translateY(-3px) scale(1.02); box-shadow: 0 10px 25px rgba(31, 58, 147, 0.4); }
        .logout-modal-overlay .logout-modal-h4 { text-align: center; color: #1a317d; }

        @keyframes iconPulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
        @keyframes fadeInBody { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInOverlay { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes gradientShift { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }

        @media (max-width: 480px) {
            .logout-modal-overlay .logout-modal-container { max-width: 95%; border-radius: 16px; }
            .logout-modal-overlay .logout-modal-header { padding: 24px 20px; gap: 15px; flex-direction: column; }
            .logout-modal-overlay .logout-modal-icon { width: 48px; height: 48px; font-size: 22px; }
            .logout-modal-overlay .logout-modal-body { padding: 20px 16px; margin: 15px; }
            .logout-modal-overlay .logout-modal-footer { padding: 20px; flex-direction: column; }
            .logout-modal-overlay .logout-modal-btn { width: 100%; justify-content: center; padding: 14px 24px; }
        }
        /* ========== END LOGOUT MODAL STYLES ========== */
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
            <a href="#" class="logout-link" id="logoutTrigger" data-tooltip="Logout"><i class="fas fa-sign-out-alt menu-icon"></i><span class="logout-text">Logout</span></a>
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

    <!-- ========== LOGOUT MODAL ========== -->
    <div class="logout-modal-overlay" id="logoutModal">
        <div class="logout-modal-container">
            <div class="logout-modal-header">
                <i class="fas fa-sign-out-alt"></i>
                <h2>Confirm Logout</h2>
                <button class="logout-modal-close" id="closeLogoutModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <br>
            <div class="logout-modal-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div><br>
            <h4 class="logout-modal-h4">Ready to Leave?</h4>
            <div class="logout-modal-body">
                <p>Are you sure you want to logout from the admin panel?</p>
                <p class="logout-modal-subtext">You will need to login again to access the admin dashboard.</p>
            </div>
            <div class="logout-modal-footer">
                <button class="logout-modal-btn logout-modal-btn-secondary" id="cancelLogout">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <a href="logout.php" class="logout-modal-btn logout-modal-btn-primary">
                    <i class="fas fa-sign-out-alt"></i> Yes, Logout
                </a>
            </div>
        </div>
    </div>
    <!-- ========== END LOGOUT MODAL ========== -->

    <script>
        (function() {
            const logoutModal = document.getElementById('logoutModal');
            const logoutTrigger = document.getElementById('logoutTrigger');
            const closeModalBtn = document.getElementById('closeLogoutModal');
            const cancelLogout = document.getElementById('cancelLogout');

            function openLogoutModal(e) {
                if (e) e.preventDefault();
                logoutModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeLogoutModalFn() {
                logoutModal.classList.remove('active');
                document.body.style.overflow = '';
            }

            if (logoutTrigger) {
                logoutTrigger.addEventListener('click', openLogoutModal);
            }
            if (closeModalBtn) {
                closeModalBtn.addEventListener('click', closeLogoutModalFn);
            }
            if (cancelLogout) {
                cancelLogout.addEventListener('click', closeLogoutModalFn);
            }
            if (logoutModal) {
                logoutModal.addEventListener('click', function(e) {
                    if (e.target === logoutModal) closeLogoutModalFn();
                });
            }
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && logoutModal && logoutModal.classList.contains('active')) {
                    closeLogoutModalFn();
                }
            });
        })();
    </script>
</body>
</html>
