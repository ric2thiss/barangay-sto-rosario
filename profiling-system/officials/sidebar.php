<?php
/**
 * sidebar.php — Unified sidebar include for all officials pages.
 * 
 * REQUIRES: session started, $current_page set before include.
 * OPTIONAL: sidebar_counts.php already included for $pending_reg, $pending_profile_updates.
 */
$is_superadmin = ($_SESSION['user_type'] === 'admin') || (!empty($_SESSION['is_superadmin']));
$sidebar_is_purok_president = (($_SESSION['staff_position'] ?? '') === 'Purok President');
$sidebar_username = $_SESSION['username'] ?? 'User';
$sidebar_position = $_SESSION['staff_position'] ?? '';

// Privilege checks (admin always has full access)
$show_staff_mgmt    = $is_superadmin || ($_SESSION['user_type'] === 'admin') || !empty($_SESSION['can_manage_staff']);
$show_activity_logs = $is_superadmin || ($_SESSION['user_type'] === 'admin') || !empty($_SESSION['can_view_logs']);
$show_profile_updates = $is_superadmin || ($_SESSION['user_type'] === 'admin') || !empty($_SESSION['can_manage_profile_updates']);
$show_pending       = $is_superadmin || ($_SESSION['user_type'] === 'admin') || !empty($_SESSION['can_approve']);
$show_deleted       = $is_superadmin || ($_SESSION['user_type'] === 'admin') || !empty($_SESSION['can_delete']);

// Count deleted residents for badge
$deleted_count = 0;
if (isset($conn) && $conn && $show_deleted) {
    $del_r = $conn->query("SELECT COUNT(*) c FROM residents WHERE is_deleted = 1");
    if ($del_r) $deleted_count = (int)$del_r->fetch_assoc()['c'];
}

$sidebarClass = isset($_COOKIE['admin_sidebar_state']) ? $_COOKIE['admin_sidebar_state'] : 'expanded';
?>

<style>
/* Admin Sidebar Styles - Blue Design */
.admin-sidebar-container {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 250px;
    background: #1F3A93;
    color: #ffffff;
    transition: all 0.3s ease;
    z-index: 1000;
    box-shadow: 5px 0 25px rgba(0, 0, 0, 0.15);
    border-right: none;
    display: flex;
    flex-direction: column;
}

.admin-sidebar-container.collapsed {
    width: 80px;
}

.admin-sidebar-header {
    padding: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #1F3A93;
}

.admin-sidebar-header .logo {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 18px;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    color: #ffffff;
}

.admin-sidebar-header .logo img {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    object-fit: cover;
    background: white;
    padding: 2px;
}

.admin-sidebar-container.collapsed .logo span {
    display: none;
}

.admin-burger-icon {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #ffffff;
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
    background: #ffffff;
    color: #1F3A93;
    transform: rotate(90deg);
}

.admin-sidebar-nav {
    flex: 1;
    padding: 20px 0;
    overflow-y: auto;
}

.admin-sidebar-nav ul {
    list-style: none;
    padding: 0 10px;
    margin: 0;
}

.admin-sidebar-nav li {
    position: relative;
    margin-bottom: 5px;
}

/* Main navigation items */
.admin-sidebar-nav a {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    white-space: nowrap;
    gap: 15px;
    font-size: 14px;
    font-weight: 500;
    border-radius: 10px;
}

.admin-sidebar-container.collapsed .admin-sidebar-nav a span {
    display: none;
}

.admin-sidebar-container.collapsed .admin-sidebar-nav a .nav-badge {
    display: none;
}

.admin-sidebar-nav a:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
    transform: translateX(5px);
}

.admin-sidebar-nav a.active {
    background: #ffffff;
    color: #1F3A93;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.admin-sidebar-nav a.active::before {
    content: '';
    position: absolute;
    left: -10px;
    top: 0;
    height: 100%;
    width: 4px;
    background: #ffffff;
    border-radius: 0 3px 3px 0;
}

.admin-sidebar-nav a i {
    font-size: 18px;
    min-width: 24px;
    text-align: center;
    transition: transform 0.3s ease;
}

.admin-sidebar-nav a.active i {
    transform: scale(1.1);
}

/* Category Separator */
.nav-separator {
    height: 1px;
    background: rgba(255, 255, 255, 0.1);
    margin: 15px 10px;
}

.admin-sidebar-container.collapsed .nav-separator {
    margin: 10px 5px;
}

/* Logout item - special styling */
.admin-sidebar-nav a.logout-item {
    color: rgba(255, 255, 255, 0.9);
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    margin-top: 10px;
}

.admin-sidebar-nav a.logout-item:hover {
    background: #ef4444;
    color: #ffffff;
    border-color: #ef4444;
}

.admin-sidebar-nav a.logout-item i {
    color: inherit;
}

.nav-badge {
    margin-left: auto;
    background: #e02424;
    color: #fff;
    font-size: 11px;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 20px;
    line-height: 1.4;
}

/* Admin user section */
.admin-sidebar-user {
    padding: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    background: #1a317d;
}

.admin-sidebar-user-content {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s;
}

.admin-sidebar-user-content:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
}

.admin-sidebar-user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ffffff, #bae6fd);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: #1F3A93;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
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
    color: #ffffff;
}

.admin-user-role {
    font-size: 11px;
    color: rgba(255, 255, 255, 0.8);
    display: block;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
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

/* Adjusting main content area dynamically */
.admin-sidebar-container.collapsed ~ .main-content {
    margin-left: 70px !important;
    width: calc(100% - 70px) !important;
    max-width: calc(100% - 70px) !important;
}

.admin-sidebar-container:not(.collapsed) ~ .main-content {
    margin-left: 250px !important;
    width: calc(100% - 250px) !important;
    max-width: calc(100% - 250px) !important;
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
    
    .admin-sidebar-container ~ .main-content {
        margin-left: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        padding: 20px !important;
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
            <img src="./image/logo.jpg" alt="Logo" onerror="this.style.display='none'">
            <span>Profiling System</span>
        </div>
        <button class="admin-burger-icon" id="adminToggleSidebar">
            <i class="fas <?php echo $sidebarClass === 'collapsed' ? 'fa-bars' : 'fa-times'; ?>"></i>
        </button>
    </div>
    
    <nav class="admin-sidebar-nav">
        <ul>
            <?php if (!$sidebar_is_purok_president): ?>
            <li>
                <a href="dashboard.php" class="<?= ($current_page ?? '')==='dashboard' ? 'active' : '' ?>" data-tooltip="Dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="barangay_officials.php" class="<?= ($current_page ?? '')==='officials' ? 'active' : '' ?>" data-tooltip="Barangay Officials">
                    <i class="fas fa-user-tie"></i>
                    <span>Barangay Officials</span>
                </a>
            </li>
            <?php endif; ?>
            
            <li>
                <a href="resident.php" class="<?= ($current_page ?? '')==='residents' ? 'active' : '' ?>" data-tooltip="Residents">
                    <i class="fas fa-users"></i>
                    <span>Residents</span>
                </a>
            </li>
            
            <?php if ($show_deleted): ?>
            <li>
                <a href="deleted_residents.php" class="<?= ($current_page ?? '')==='deleted_residents' ? 'active' : '' ?>" data-tooltip="Deleted Residents">
                    <i class="fas fa-user-slash"></i>
                    <span>Deleted Residents</span>
                    <?php if ($deleted_count > 0): ?><span class="nav-badge" style="background:#64748b"><?= $deleted_count ?></span><?php endif; ?>
                </a>
            </li>
            <?php endif; ?>
            
            <div class="nav-separator"></div>
            
            <?php if (!$sidebar_is_purok_president && $show_pending): ?>
            <li>
                <a href="pending_registrations.php" class="<?= ($current_page ?? '')==='pending' ? 'active' : '' ?>" data-tooltip="Pending Approvals">
                    <i class="fas fa-user-clock"></i>
                    <span>Pending Approvals</span>
                    <?php if (($pending_reg ?? 0) > 0): ?><span class="nav-badge"><?= $pending_reg ?></span><?php endif; ?>
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (!$sidebar_is_purok_president && $show_profile_updates): ?>
            <li>
                <a href="profile_update_requests.php" class="<?= ($current_page ?? '')==='profile_updates' ? 'active' : '' ?>" data-tooltip="Profile Updates">
                    <i class="fas fa-user-edit"></i>
                    <span>Profile Updates</span>
                    <?php if (($pending_profile_updates ?? 0) > 0): ?><span class="nav-badge"><?= $pending_profile_updates ?></span><?php endif; ?>
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (!$sidebar_is_purok_president && $show_staff_mgmt): ?>
            <li>
                <a href="staff_management.php" class="<?= ($current_page ?? '')==='staff' ? 'active' : '' ?>" data-tooltip="Staff Management">
                    <i class="fas fa-user-shield"></i>
                    <span>Staff Management</span>
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (!$sidebar_is_purok_president && $show_activity_logs): ?>
            <li>
                <a href="admin_logs.php" class="<?= ($current_page ?? '')==='logs' ? 'active' : '' ?>" data-tooltip="Activity Logs">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Activity Logs</span>
                </a>
            </li>
            <?php endif; ?>
            
            <div class="nav-separator"></div>
            
            <li>
                <a href="logout.php" class="logout-item" data-tooltip="Logout" onclick="return confirm('Logout?')">
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
                <div class="admin-username"><?= htmlspecialchars($sidebar_username) ?></div>
                <?php 
                $roleLabel = 'Admin';
                $roleClass = 'role-admin';
                if ($is_superadmin) {
                    $roleLabel = 'Super Admin';
                    $roleClass = 'role-superadmin';
                } elseif ($sidebar_position) {
                    $roleLabel = htmlspecialchars($sidebar_position);
                    $roleClass = '';
                }
                ?>
                <div class="admin-user-role <?= $roleClass ?>"><?= $roleLabel ?></div>
            </div>
        </div>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const adminSidebar = document.getElementById('adminSidebar');
    const adminToggleBtn = document.getElementById('adminToggleSidebar');
    const adminMobileMenuBtn = document.getElementById('adminMobileMenuBtn');
    const adminMobileOverlay = document.getElementById('adminMobileOverlay');
    
    // Toggle sidebar function
    function toggleAdminSidebar() {
        const isCollapsed = adminSidebar.classList.contains('collapsed');
        const newState = isCollapsed ? 'expanded' : 'collapsed';
        
        // Update sidebar class
        adminSidebar.classList.toggle('collapsed');
        
        // Update burger icon
        const icon = adminToggleBtn.querySelector('i');
        if (icon) icon.className = isCollapsed ? 'fas fa-times' : 'fas fa-bars';
        
        // Update mobile button icon if sidebar is expanded on mobile
        if (window.innerWidth <= 768 && !isCollapsed) {
            if (adminMobileMenuBtn) adminMobileMenuBtn.innerHTML = '<i class="fas fa-times"></i>';
        } else if (window.innerWidth <= 768) {
            if (adminMobileMenuBtn) adminMobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
        }
        
        // Save state to cookie (expires in 30 days)
        document.cookie = `admin_sidebar_state=${newState}; path=/; max-age=${30 * 24 * 60 * 60}`;
    }
    
    // Mobile sidebar functions
    function openMobileAdminSidebar() {
        if (!adminSidebar || !adminMobileOverlay) return;
        adminSidebar.classList.add('mobile-open');
        adminMobileOverlay.classList.add('show');
        document.body.style.overflow = 'hidden';
        if (adminMobileMenuBtn) adminMobileMenuBtn.innerHTML = '<i class="fas fa-times"></i>';
    }
    
    function closeMobileAdminSidebar() {
        if (!adminSidebar || !adminMobileOverlay) return;
        adminSidebar.classList.remove('mobile-open');
        adminMobileOverlay.classList.remove('show');
        document.body.style.overflow = '';
        if (adminMobileMenuBtn) adminMobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
    }
    
    // Event listeners
    if (adminToggleBtn) {
        adminToggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (window.innerWidth <= 768) {
                closeMobileAdminSidebar();
            } else {
                toggleAdminSidebar();
            }
        });
    }
    
    // Mobile menu button
    if (adminMobileMenuBtn) {
        adminMobileMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (adminSidebar.classList.contains('mobile-open')) {
                closeMobileAdminSidebar();
            } else {
                openMobileAdminSidebar();
            }
        });
    }
    
    if (adminMobileOverlay) {
        adminMobileOverlay.addEventListener('click', closeMobileAdminSidebar);
    }
    
    // Close mobile sidebar when clicking on main content (if exists)
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.addEventListener('click', function() {
            if (window.innerWidth <= 768 && adminSidebar.classList.contains('mobile-open')) {
                closeMobileAdminSidebar();
            }
        });
    }
    
    // Handle window resize
    function handleAdminResize() {
        if (window.innerWidth > 768) {
            if (adminSidebar) adminSidebar.classList.remove('mobile-open');
            if (adminMobileOverlay) adminMobileOverlay.classList.remove('show');
            document.body.style.overflow = '';
            if (adminMobileMenuBtn) adminMobileMenuBtn.style.display = 'none';
        } else {
            if (adminMobileMenuBtn) adminMobileMenuBtn.style.display = 'flex';
            if (adminSidebar && !adminSidebar.classList.contains('mobile-open')) {
                if (adminMobileMenuBtn) adminMobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
            }
        }
    }
    
    // Initialize mobile button visibility
    handleAdminResize();
    window.addEventListener('resize', handleAdminResize);
    
    // Setup tooltips for collapsed sidebar
    function setupTooltips() {
        if (!adminSidebar) return;
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

