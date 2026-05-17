<?php if (!isset($_SESSION['user_id'])) return; ?>
<?php
// Default sidebar state
$sidebarClass = isset($_COOKIE['admin_sidebar_state']) ? $_COOKIE['admin_sidebar_state'] : 'expanded';
?>

<style>
/* Admin Sidebar Styles - Clean Design */
.admin-sidebar-container {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 250px;
    background: #ffffff;
    color: #333;
    transition: all 0.3s ease;
    z-index: 1000;
    box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1);
    border-right: 1px solid #e0e0e0;
    display: flex;
    flex-direction: column;
}

.admin-sidebar-container.collapsed {
    width: 70px;
}

.admin-sidebar-header {
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f8f9fa;
}

.admin-sidebar-header .logo {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 20px;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    color: #2c3e50;
}

.admin-sidebar-container.collapsed .logo span {
    display: none;
}

.admin-burger-icon {
    background: #e9ecef;
    border: none;
    color: #495057;
    font-size: 18px;
    cursor: pointer;
    padding: 8px;
    border-radius: 6px;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.admin-burger-icon:hover {
    background: #dee2e6;
    transform: rotate(90deg);
}

.admin-sidebar-nav {
    flex: 1;
    padding: 20px 0;
    overflow-y: auto;
}

.admin-sidebar-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.admin-sidebar-nav li {
    position: relative;
}

/* Main navigation items - ALL ON SEPARATE ROWS */
.admin-sidebar-nav a {
    display: flex;
    align-items: center;
    padding: 14px 20px;
    color: #495057;
    text-decoration: none;
    transition: all 0.3s ease;
    white-space: nowrap;
    gap: 15px;
    font-size: 15px;
    font-weight: 500;
    border-left: 3px solid transparent;
    margin: 2px 0;
}

.admin-sidebar-container.collapsed .admin-sidebar-nav a span {
    display: none;
}

.admin-sidebar-nav a:hover {
    background: #f8f9fa;
    color: #2c3e50;
    border-left-color: #adb5bd;
}

.admin-sidebar-nav a.active {
    background: #e7f5ff;
    color: #1864ab;
    border-left: 3px solid #1864ab;
}

.admin-sidebar-nav a i {
    font-size: 18px;
    min-width: 24px;
    text-align: center;
}

/* Category Separator */
.nav-separator {
    height: 1px;
    background: #e0e0e0;
    margin: 15px 20px;
    opacity: 0.5;
}

.admin-sidebar-container.collapsed .nav-separator {
    margin: 10px 15px;
}

/* Logout item - special styling */
.admin-sidebar-nav a.logout-item {
    color: #dc3545;
}

.admin-sidebar-nav a.logout-item:hover {
    background: #fff5f5;
    color: #c82333;
    border-left-color: #dc3545;
}

.admin-sidebar-nav a.logout-item i {
    color: #dc3545;
}

/* Admin user section */
.admin-sidebar-user {
    padding: 20px;
    border-top: 1px solid #e0e0e0;
    background: #f8f9fa;
}

.admin-sidebar-user-content {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    border-radius: 8px;
}

.admin-sidebar-user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1864ab 0%, #0b3c6d 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: white;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.admin-sidebar-user-info {
    flex: 1;
    min-width: 0;
}

.admin-sidebar-container.collapsed .admin-sidebar-user-info {
    display: none;
}

.admin-username {
    font-weight: 600;
    font-size: 14px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #2c3e50;
}

.admin-user-role {
    font-size: 12px;
    color: #6c757d;
    background: #e9ecef;
    padding: 2px 8px;
    border-radius: 12px;
    display: inline-block;
    margin-top: 4px;
}

.admin-user-role.role-superadmin {
    background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
    color: white;
    font-weight: 600;
}

.admin-user-role.role-admin {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    color: white;
    font-weight: 600;
}

/* Main content area */
.admin-main-content {
    margin-left: 250px;
    padding: 30px;
    transition: all 0.3s ease;
    min-height: 100vh;
    background: #f8fafc;
    width: calc(100vw - 250px);
    max-width: 100%;
    overflow-x: hidden;
    box-sizing: border-box;
}

.admin-sidebar-container.collapsed ~ .admin-main-content {
    margin-left: 70px;
    width: calc(100vw - 70px);
}

/* Mobile responsive */
@media (max-width: 768px) {
    .admin-sidebar-container {
        width: 250px;
        transform: translateX(-100%);
    }
    
    .admin-sidebar-container.mobile-open {
        transform: translateX(0);
    }
    
    .admin-sidebar-container.collapsed {
        width: 250px;
        transform: translateX(-100%);
    }
    
    .admin-main-content {
        margin-left: 0 !important;
        width: 100vw !important;
        padding: 20px;
    }
    
    .admin-sidebar-container.collapsed ~ .admin-main-content {
        margin-left: 0 !important;
        width: 100vw !important;
    }
    
    .admin-mobile-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
    }
    
    .admin-mobile-overlay.show {
        display: block;
    }
    
    .admin-mobile-menu-btn {
        position: fixed;
        top: 15px;
        left: 15px;
        background: #1864ab;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 10px 12px;
        font-size: 18px;
        cursor: pointer;
        z-index: 998;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        display: none;
    }
    
    .admin-mobile-menu-btn i {
        display: block;
    }
}

/* Tooltip for collapsed state */
.admin-sidebar-container.collapsed .admin-sidebar-nav a,
.admin-sidebar-container.collapsed .admin-sidebar-user-content {
    position: relative;
}

.admin-sidebar-container.collapsed .admin-sidebar-nav a::after,
.admin-sidebar-container.collapsed .admin-sidebar-user-content::after {
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

.admin-sidebar-container.collapsed .admin-sidebar-nav a:hover::after,
.admin-sidebar-container.collapsed .admin-sidebar-user-content:hover::after {
    opacity: 1;
    visibility: visible;
}

/* Prevent horizontal scroll */
html, body {
    overflow-x: hidden;
    margin: 0;
    padding: 0;
}

/* Animation for sidebar */
.admin-sidebar-container {
    animation: none;
}

.admin-sidebar-container:not(.collapsed) {
    animation: slideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes slideIn {
    from {
        opacity: 0.8;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Add this to prevent any child element from causing overflow */
.admin-main-content > * {
    max-width: 100%;
}
</style>

<!-- Mobile Menu Button -->
<button class="admin-mobile-menu-btn" id="adminMobileMenuBtn">
    <i class="fas fa-bars"></i>
</button>

<!-- Mobile Overlay -->
<div class="admin-mobile-overlay" id="adminMobileOverlay"></div>

<!-- Admin Sidebar Container -->
<aside class="admin-sidebar-container <?php echo $sidebarClass; ?>" id="adminSidebar">
    <div class="admin-sidebar-header">
        <div class="logo">
            <i class="fas fa-shield-alt" style="color: #1864ab;"></i>
            <span>Admin Panel</span>
        </div>
        <button class="admin-burger-icon" id="adminToggleSidebar">
            <i class="fas <?php echo $sidebarClass === 'collapsed' ? 'fa-bars' : 'fa-times'; ?>"></i>
        </button>
    </div>
    
    <nav class="admin-sidebar-nav">
        <ul>
            <!-- EVERY ITEM ON ITS OWN SEPARATE ROW -->
            <li>
                <a href="index.php" 
                   class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"
                   data-tooltip="Dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li>
                <a href="manage_feedback.php" 
                   class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_feedback.php' ? 'active' : ''; ?>"
                   data-tooltip="Manage Feedback">
                    <i class="fas fa-list-alt"></i>
                    <span>Manage Feedback</span>
                </a>
            </li>
            
            <li>
                <a href="admin_settings.php" 
                   class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_settings.php' ? 'active' : ''; ?>"
                   data-tooltip="Admin Settings">
                    <i class="fas fa-cog"></i>
                    <span>Admin Settings</span>
                </a>
            </li>
            
            <div class="nav-separator"></div>
            
            <li>
                <a href="user_management.php" 
                   class="<?php echo basename($_SERVER['PHP_SELF']) == 'user_management.php' ? 'active' : ''; ?>"
                   data-tooltip="User Management">
                    <i class="fas fa-users-cog"></i>
                    <span>User Management</span>
                </a>
            </li>
            
            <li>
                <a href="analytics.php" 
                   class="<?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>"
                   data-tooltip="Analytics">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics</span>
                </a>
            </li>
            
            <div class="nav-separator"></div>
            
            <li>
                <a href="logout.php" class="logout-item"
                   data-tooltip="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="admin-sidebar-user">
        <div class="admin-sidebar-user-content">
            <div class="admin-sidebar-user-avatar">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="admin-sidebar-user-info">
                <div class="admin-username"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                <?php 
                $userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'admin';
                $roleLabel = ($userType === 'superadmin') ? 'Super Admin' : 'Admin';
                $roleClass = ($userType === 'superadmin') ? 'role-superadmin' : 'role-admin';
                ?>
                <div class="admin-user-role <?php echo $roleClass; ?>"><?php echo $roleLabel; ?></div>
            </div>
        </div>
    </div>
</aside>

<!-- Main Content Area -->
<main class="admin-main-content" id="adminMainContent">
    <div class="content-container">
        <!-- Your admin page content goes here -->
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const adminSidebar = document.getElementById('adminSidebar');
    const adminToggleBtn = document.getElementById('adminToggleSidebar');
    const adminMobileMenuBtn = document.getElementById('adminMobileMenuBtn');
    const adminMobileOverlay = document.getElementById('adminMobileOverlay');
    const adminMainContent = document.getElementById('adminMainContent');
    
    // Toggle sidebar function
    function toggleAdminSidebar() {
        const isCollapsed = adminSidebar.classList.contains('collapsed');
        const newState = isCollapsed ? 'expanded' : 'collapsed';
        
        // Update sidebar class
        adminSidebar.classList.toggle('collapsed');
        
        // Update burger icon
        const icon = adminToggleBtn.querySelector('i');
        icon.className = isCollapsed ? 'fas fa-times' : 'fas fa-bars';
        
        // Update mobile button icon if sidebar is expanded on mobile
        if (window.innerWidth <= 768 && !isCollapsed) {
            adminMobileMenuBtn.innerHTML = '<i class="fas fa-times"></i>';
        } else if (window.innerWidth <= 768) {
            adminMobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
        }
        
        // Save state to cookie (expires in 30 days)
        document.cookie = `admin_sidebar_state=${newState}; path=/; max-age=${30 * 24 * 60 * 60}`;
    }
    
    // Mobile sidebar functions
    function openMobileAdminSidebar() {
        adminSidebar.classList.add('mobile-open');
        adminMobileOverlay.classList.add('show');
        document.body.style.overflow = 'hidden';
        adminMobileMenuBtn.innerHTML = '<i class="fas fa-times"></i>';
    }
    
    function closeMobileAdminSidebar() {
        adminSidebar.classList.remove('mobile-open');
        adminMobileOverlay.classList.remove('show');
        document.body.style.overflow = '';
        adminMobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
    }
    
    // Event listeners
    adminToggleBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        if (window.innerWidth <= 768) {
            closeMobileAdminSidebar();
        } else {
            toggleAdminSidebar();
        }
    });
    
    // Mobile menu button
    adminMobileMenuBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        if (adminSidebar.classList.contains('mobile-open')) {
            closeMobileAdminSidebar();
        } else {
            openMobileAdminSidebar();
        }
    });
    
    adminMobileOverlay.addEventListener('click', closeMobileAdminSidebar);
    
    // Close mobile sidebar when clicking on main content
    adminMainContent.addEventListener('click', function() {
        if (window.innerWidth <= 768 && adminSidebar.classList.contains('mobile-open')) {
            closeMobileAdminSidebar();
        }
    });
    
    // Handle window resize
    function handleAdminResize() {
        if (window.innerWidth > 768) {
            adminSidebar.classList.remove('mobile-open');
            adminMobileOverlay.classList.remove('show');
            document.body.style.overflow = '';
            adminMobileMenuBtn.style.display = 'none';
        } else {
            adminMobileMenuBtn.style.display = 'flex';
            if (!adminSidebar.classList.contains('mobile-open')) {
                adminMobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
            }
        }
    }
    
    // Initialize mobile button visibility
    handleAdminResize();
    window.addEventListener('resize', handleAdminResize);
    
    // Setup tooltips for collapsed sidebar
    function setupTooltips() {
        const links = adminSidebar.querySelectorAll('.admin-sidebar-nav a');
        links.forEach(link => {
            const text = link.querySelector('span');
            if (text) {
                link.setAttribute('data-tooltip', text.textContent);
            }
        });
    }
    
    setupTooltips();
});
</script>