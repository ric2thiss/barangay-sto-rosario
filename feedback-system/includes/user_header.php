<?php if (!isset($_SESSION['user_id'])) return; ?>
<header>
    <div class="container header-container">
        <div class="logo">
            <i class="fas fa-comments"></i>
            User Dashboard
        </div>
        <nav>
            <ul>
                <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Home
                </a></li>
                <li><a href="my_feedback.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_feedback.php' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i> My Feedback
                </a></li>
                <li class="user-menu">
                    <a href="#">
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </a>
                    <ul class="dropdown">
                        <li><a href="#"><i class="fas fa-user"></i> My Profile</a></li>
                        <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </nav>
    </div>
</header>