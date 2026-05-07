<?php
// frontend/includes/sidebar.php

$currentPage = $_GET['page'] ?? 'dashboard';

function isActive($page, $currentPage)
{
    return $page === $currentPage ? ' active' : '';
}
?>

<header class="bpss-topbar shadow-sm">
    <div class="container-fluid d-flex align-items-center justify-content-between">

        <!-- LEFT: LOGOS + BRAND NAME -->
        <div class="d-flex align-items-center gap-2">
            <div class="d-flex align-items-center bpss-logos">
                <img src="frontend/assets/images/Magallanes-Logo.jpg" alt="Municipality Logo" class="bpss-logo">
                <img src="frontend/assets/images/seal-nobg.png" alt="Barangay Seal" class="bpss-logo">
            </div>
            <div class="bpss-brand-text d-none d-sm-block">
                <div class="bpss-brand-name">Barangay PSS</div>
                <div class="bpss-brand-sub">Planning &amp; Scheduling System</div>
            </div>
        </div>

        <!-- RIGHT: AVATAR TOGGLE (avatar IS the dropdown trigger) -->
        <?php
        $pic = $_SESSION['profile_picture'] ?? '';
        $initial = strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1));
        ?>
        <div class="dropdown">

            <!-- Avatar circle — acts as the dropdown toggle -->
            <button type="button" id="topbarAvatar" class="bpss-avatar-toggle dropdown-toggle" data-bs-toggle="dropdown"
                data-bs-display="static" aria-expanded="false" aria-label="Open menu">
                <!-- photo or initial -->
                <?php if ($pic): ?>
                    <img src="<?= htmlspecialchars($pic) ?>"
                        style="width:100%;height:100%;object-fit:cover;border-radius:50%;" alt="Avatar"
                        id="topbarAvatarImg">
                <?php else: ?>
                    <span id="topbarAvatarInitial"><?= $initial ?></span>
                <?php endif; ?>
                <!-- small triangle badge (Facebook-style) -->
                <span class="bpss-avatar-badge">&#9660;</span>
            </button>

            <ul class="dropdown-menu dropdown-menu-end shadow-sm bpss-dropdown bpss-dropdown-anim"
                aria-labelledby="topbarAvatar">

                <!-- Profile -->
                <li>
                    <a class="dropdown-item bpss-vertical-item<?= isActive('profile', $currentPage) ?>"
                        href="index.php?page=profile">
                        <i class="bi bi-person-circle"></i>
                        <span>Profile</span>
                    </a>
                </li>

                <!-- Settings -->
                <li>
                    <a class="dropdown-item bpss-vertical-item<?= isActive('settings', $currentPage) ?>"
                        href="index.php?page=settings">
                        <i class="bi bi-gear"></i>
                        <span>Settings</span>
                    </a>
                </li>

                <li>
                    <hr class="dropdown-divider my-1">
                </li>

                <!-- Dashboard -->
                <li>
                    <a class="dropdown-item bpss-vertical-item<?= isActive('dashboard', $currentPage) ?>"
                        href="index.php?page=dashboard">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <!-- Schedule -->
                <li>
                    <a class="dropdown-item bpss-vertical-item<?= isActive('create_event', $currentPage) ?>"
                        href="index.php?page=create_event">
                        <i class="bi bi-calendar-event"></i>
                        <span>Schedule</span>
                    </a>
                </li>

                <!-- Minutes -->
                <li>
                    <a class="dropdown-item bpss-vertical-item<?= isActive('minutes', $currentPage) ?>"
                        href="index.php?page=minutes">
                        <i class="bi bi-journal-text"></i>
                        <span>Minutes</span>
                    </a>
                </li>

                <!-- Form -->
                <li>
                    <a class="dropdown-item bpss-vertical-item<?= isActive('form', $currentPage) ?>"
                        href="index.php?page=form">
                        <i class="bi bi-file-earmark-ruled"></i>
                        <span>Form</span>
                    </a>
                </li>

                <!-- SMS -->
                <li>
                    <a class="dropdown-item bpss-vertical-item<?= isActive('sms', $currentPage) ?>"
                        href="index.php?page=sms">
                        <i class="bi bi-chat-dots"></i>
                        <span>SMS</span>
                    </a>
                </li>

                <!-- Officials -->
                <li>
                    <a class="dropdown-item bpss-vertical-item<?= isActive('officials', $currentPage) ?>"
                        href="index.php?page=officials">
                        <i class="bi bi-person-badge"></i>
                        <span>Officials</span>
                    </a>
                </li>

                <!-- Accounts (admin only) -->
                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                    <li>
                        <a class="dropdown-item bpss-vertical-item<?= isActive('accounts', $currentPage) ?>"
                            href="index.php?page=accounts">
                            <i class="bi bi-person-gear"></i>
                            <span>Accounts</span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Reports -->
                <li>
                    <a class="dropdown-item bpss-vertical-item<?= isActive('reports', $currentPage) ?>"
                        href="index.php?page=reports">
                        <i class="bi bi-clipboard-data"></i>
                        <span>Reports</span>
                    </a>
                </li>

                <!-- Contacts -->
                <li>
                    <a class="dropdown-item bpss-vertical-item<?= isActive('contacts', $currentPage) ?>"
                        href="index.php?page=contacts">
                        <i class="bi bi-people"></i>
                        <span>Contacts</span>
                    </a>
                </li>

                <!-- Logs -->
                <li>
                    <a class="dropdown-item bpss-vertical-item<?= isActive('logs', $currentPage) ?>"
                        href="index.php?page=logs">
                        <i class="bi bi-clock-history"></i>
                        <span>Logs</span>
                    </a>
                </li>

                <li>
                    <hr class="dropdown-divider my-1">
                </li>

                <!-- Logout -->
                <li>
                    <form method="POST" action="backend/auth/logout_api.php" class="m-0">
                        <button type="submit"
                            class="dropdown-item bpss-vertical-item bpss-logout border-0 bg-transparent w-100">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </li>

            </ul>
        </div><!-- /.dropdown -->

    </div>
</header>

<main class="bpss-content">
    <div class="container-fluid py-3">