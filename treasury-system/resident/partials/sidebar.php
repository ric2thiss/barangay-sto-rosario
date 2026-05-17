<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Mobile Header -->
<header class="mobile-header no-print">
    <div class="mobile-menu-toggle" id="mobileMenuOpen">
        <i class="fas fa-bars"></i>
    </div>
    <div class="mobile-logo-wrap">
        <img src="../assets/images/logo.jpg" alt="Logo">
        <span>Treasury System</span>
    </div>
    <div style="width: 40px;"></div> <!-- Balancer -->
</header>

<!-- Mobile Overlay -->
<div class="mobile-overlay" id="mobileOverlay"></div>

<!-- Sidebar -->
<aside class="admin-sidebar-container no-print" id="adminSidebar">
    <div class="admin-sidebar-header">
        <div class="logo">
            <img src="../assets/images/logo.jpg" alt="Logo">
            <span>Treasury System</span>
        </div>
        <button class="admin-burger-icon" id="toggleSidebar">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <nav class="admin-sidebar-nav">
        <ul>
            <div class="nav-group-label">Resident Portal</div>
            <li>
                <a href="pending_payments.php" class="<?= $current_page == 'pending_payments.php' ? 'active' : '' ?>" data-tooltip="Pending Payments">
                    <i class="fas fa-hourglass-half"></i>
                    <span>Pending Payments</span>
                </a>
            </li>
            <li>
                <a href="payment_history.php" class="<?= $current_page == 'payment_history.php' ? 'active' : '' ?>" data-tooltip="Payment History">
                    <i class="fas fa-receipt"></i>
                    <span>Payment History</span>
                </a>
            </li>
            <li>
                <a href="request_cedula.php" class="<?= $current_page == 'request_cedula.php' ? 'active' : '' ?>" data-tooltip="Request Cedula">
                    <i class="fas fa-id-card"></i>
                    <span>Request Cedula</span>
                </a>
            </li>
            <li>
                <a href="donation.php" class="<?= $current_page == 'donation.php' ? 'active' : '' ?>" data-tooltip="Make Donation">
                    <i class="fas fa-heart"></i>
                    <span>Make Donation</span>
                </a>
            </li>
            <li>
                <a href="garbage.php" class="<?= $current_page == 'garbage.php' ? 'active' : '' ?>" data-tooltip="Garbage Payment">
                    <i class="fas fa-trash"></i>
                    <span>Garbage Payment</span>
                </a>
            </li>
            <li>
                <a href="rental.php" class="<?= $current_page == 'rental.php' ? 'active' : '' ?>" data-tooltip="Rent Facilities">
                    <i class="fas fa-building"></i>
                    <span>Rent Facilities</span>
                </a>
            </li>

            <div class="nav-separator"></div>

            <li>
                <a href="logout.php" class="logout-item" onclick="return confirm('Are you sure you want to logout?')" data-tooltip="Logout">
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
                    $names = explode(' ', $_SESSION['name'] ?? 'Resident');
                    foreach($names as $n) $initials .= strtoupper(substr($n, 0, 1));
                    echo substr($initials, 0, 2);
                ?>
            </div>
            <div class="admin-sidebar-user-info">
                <div class="admin-username"><?= htmlspecialchars($_SESSION['name'] ?? 'Resident') ?></div>
                <div class="admin-user-role">Resident</div>
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
