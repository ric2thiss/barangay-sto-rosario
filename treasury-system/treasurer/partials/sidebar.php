<?php
$current_page = basename($_SERVER['PHP_SELF']);
$path_prefix = $path_prefix ?? '';
?>

<!-- Mobile Header -->
<header class="mobile-header no-print">
    <div class="mobile-menu-toggle" id="mobileMenuOpen">
        <i class="fas fa-bars"></i>
    </div>
    <div class="mobile-logo-wrap">
        <img src="<?= $path_prefix ?>../assets/images/logo.jpg" alt="Logo">
        <span>Treasury System</span>
    </div>
    <div class="w-[40px]"></div> <!-- Balancer -->
</header>

<!-- Mobile Overlay -->
<div class="mobile-overlay" id="mobileOverlay"></div>

<!-- Sidebar -->
<aside class="admin-sidebar-container no-print" id="adminSidebar">
    <div class="admin-sidebar-header">
        <div class="logo">
            <img src="<?= $path_prefix ?>../assets/images/logo.jpg" alt="Logo">
            <span>Treasury System</span>
        </div>
        <button class="admin-burger-icon" id="toggleSidebar">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <nav class="admin-sidebar-nav">
        <ul>
            <div class="nav-group-label">Main Menu</div>
            <li>
                <a href="<?= $path_prefix ?>dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>" data-tooltip="Dashboard">
                    <i class="fas fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <div class="nav-group-label">Financial Management</div>
            <li>
                <a href="<?= $path_prefix ?>payments/list.php" class="<?= strpos($_SERVER['PHP_SELF'], '/payments/') !== false ? 'active' : '' ?>" data-tooltip="Payments">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Payments</span>
                </a>
            </li>
            <li>
                <a href="<?= $path_prefix ?>pending_payments/list.php" class="<?= strpos($_SERVER['PHP_SELF'], '/pending_payments/') !== false ? 'active' : '' ?>" data-tooltip="Pending Status">
                    <i class="fas fa-hourglass-half"></i>
                    <span>Pending Status</span>
                </a>
            </li>
            <li>
                <a href="<?= $path_prefix ?>disbursement/list.php" class="<?= strpos($_SERVER['PHP_SELF'], '/disbursement/') !== false ? 'active' : '' ?>" data-tooltip="Disbursements">
                    <i class="fas fa-hand-holding-usd"></i>
                    <span>Disbursements</span>
                </a>
            </li>

            <div class="nav-group-label">Documents</div>
            <li>
                <a href="<?= $path_prefix ?>cedula/list.php" class="<?= strpos($_SERVER['PHP_SELF'], '/cedula/') !== false ? 'active' : '' ?>" data-tooltip="Cedula">
                    <i class="fas fa-id-card"></i>
                    <span>Cedula</span>
                </a>
            </li>

            <div class="nav-group-label">Reports & Analytics</div>
            <li>
                <a href="<?= $path_prefix ?>collections/monthly.php" class="<?= strpos($_SERVER['PHP_SELF'], '/collections/monthly.php') !== false ? 'active' : '' ?>" data-tooltip="Monthly Collections">
                    <i class="fas fa-chart-line"></i>
                    <span>Monthly Collections</span>
                </a>
            </li>
            <li>
                <a href="<?= $path_prefix ?>collections/analytics.php" class="<?= strpos($_SERVER['PHP_SELF'], '/collections/analytics.php') !== false ? 'active' : '' ?>" data-tooltip="Analytics">
                    <i class="fas fa-landmark"></i>
                    <span>IRA/DV Analytics</span>
                </a>
            </li>
            <li>
                <a href="<?= $path_prefix ?>collections/annual.php" class="<?= strpos($_SERVER['PHP_SELF'], '/collections/annual.php') !== false ? 'active' : '' ?>" data-tooltip="Annual Report">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Annual Report</span>
                </a>
            </li>

            <div class="nav-separator"></div>

            <li>
                <a href="<?= $path_prefix ?>change_password.php" class="<?= $current_page == 'change_password.php' ? 'active' : '' ?>" data-tooltip="Settings">
                    <i class="fas fa-key"></i>
                    <span>Change Password</span>
                </a>
            </li>
            <li>
                <a href="<?= $path_prefix ?>../logout.php" class="logout-item" onclick="return confirm('Are you sure you want to logout?')" data-tooltip="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="admin-sidebar-user">
        <div class="admin-sidebar-user-content">
            <div class="admin-sidebar-user-avatar">
                <?php 
                    $initials = '';
                    $names = explode(' ', $_SESSION['name']);
                    foreach($names as $n) $initials .= strtoupper(substr($n, 0, 1));
                    echo substr($initials, 0, 2);
                ?>
            </div>
            <div class="admin-sidebar-user-info">
                <div class="admin-username"><?= htmlspecialchars($_SESSION['name']) ?></div>
                <div class="admin-user-role">Treasurer</div>
            </div>
        </div>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('adminSidebar');
    const toggleBtn = document.getElementById('toggleSidebar');
    const mobileOpenBtn = document.getElementById('mobileMenuOpen');
    const mobileOverlay = document.getElementById('mobileOverlay');

    function toggleSidebar() {
        if (window.innerWidth <= 1024) {
            sidebar.classList.remove('mobile-open');
            mobileOverlay.classList.remove('show');
            document.body.style.overflow = '';
        } else {
            const isCollapsed = sidebar.classList.toggle('collapsed');
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-bars', isCollapsed);
                icon.classList.toggle('fa-times', !isCollapsed);
            }
            document.cookie = "treasury_sidebar_collapsed=" + isCollapsed + "; path=/; max-age=" + (30 * 24 * 60 * 60);
        }
    }

    if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
    if (mobileOpenBtn) {
        mobileOpenBtn.addEventListener('click', function() {
            sidebar.classList.add('mobile-open');
            mobileOverlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        });
    }
    if (mobileOverlay) {
        mobileOverlay.addEventListener('click', function() {
            sidebar.classList.remove('mobile-open');
            mobileOverlay.classList.remove('show');
            document.body.style.overflow = '';
        });
    }

    // Handle initial collapsed state from cookie
    if (document.cookie.includes('treasury_sidebar_collapsed=true') && window.innerWidth > 1024) {
        sidebar.classList.add('collapsed');
        const icon = toggleBtn.querySelector('i');
        if (icon) {
            icon.classList.replace('fa-times', 'fa-bars');
        }
    }
});
</script>
