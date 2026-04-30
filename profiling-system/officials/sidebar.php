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
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="./image/logo.jpg" alt="Logo" onerror="this.style.display='none'">
        <h2><i class="fas fa-city"></i> Barangay Sto. Rosario</h2>
        <div style="color:rgba(255,255,255,.5);font-size:.78rem;margin-top:-4px">
            Welcome, <span style="color:#38bdf8;font-weight:600">@<?= htmlspecialchars($sidebar_username) ?></span>
            <?php if ($sidebar_position): ?>
            <br><span style="color:#fbbf24;font-size:.72rem"><i class="fas fa-id-badge"></i> <?= htmlspecialchars($sidebar_position) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <nav><ul>
        <?php if (!$sidebar_is_purok_president): ?>
        <li><a href="dashboard.php"<?= ($current_page ?? '')==='dashboard' ? ' class="active"' : '' ?>><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="barangay_officials.php"<?= ($current_page ?? '')==='officials' ? ' class="active"' : '' ?>><i class="fas fa-user-tie"></i> Barangay Officials</a></li>
        <?php endif; ?>
        <li><a href="resident.php"<?= ($current_page ?? '')==='residents' ? ' class="active"' : '' ?>><i class="fas fa-users"></i> Residents</a></li>
        <?php if ($show_deleted): ?>
        <li><a href="deleted_residents.php"<?= ($current_page ?? '')==='deleted_residents' ? ' class="active"' : '' ?>>
            <i class="fas fa-user-slash"></i> Deleted Residents
            <?php if ($deleted_count > 0): ?><span class="nav-badge" style="background:#64748b"><?= $deleted_count ?></span><?php endif; ?>
        </a></li>
        <?php endif; ?>
        <?php if (!$sidebar_is_purok_president && $show_pending): ?>
        <li><a href="pending_registrations.php"<?= ($current_page ?? '')==='pending' ? ' class="active"' : '' ?>>
            <i class="fas fa-user-clock"></i> Pending Approvals
            <?php if (($pending_reg ?? 0) > 0): ?><span class="nav-badge"><?= $pending_reg ?></span><?php endif; ?>
        </a></li>
        <?php endif; ?>
        <?php if (!$sidebar_is_purok_president && $show_profile_updates): ?>
        <li><a href="profile_update_requests.php"<?= ($current_page ?? '')==='profile_updates' ? ' class="active"' : '' ?>>
            <i class="fas fa-user-edit"></i> Profile Updates
            <?php if (($pending_profile_updates ?? 0) > 0): ?><span class="nav-badge"><?= $pending_profile_updates ?></span><?php endif; ?>
        </a></li>
        <?php endif; ?>
        <?php if (!$sidebar_is_purok_president && $show_staff_mgmt): ?>
        <li><a href="staff_management.php"<?= ($current_page ?? '')==='staff' ? ' class="active"' : '' ?>><i class="fas fa-user-shield"></i> Staff Management</a></li>
        <?php endif; ?>
        <?php if (!$sidebar_is_purok_president && $show_activity_logs): ?>
        <li><a href="admin_logs.php"<?= ($current_page ?? '')==='logs' ? ' class="active"' : '' ?>><i class="fas fa-clipboard-list"></i> Activity Logs</a></li>
        <?php endif; ?>
        <li><a href="logout.php" onclick="return confirm('Logout?')"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul></nav>
</aside>

