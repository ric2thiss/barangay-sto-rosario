<?php
include "../config/database.php";
include "../config/session.php";

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (md5($current_password) !== $user['password']) {
            $error = "Current password is incorrect";
        } else {
            // Update password
            $new_password_hash = md5($new_password);
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_password_hash, $_SESSION['user_id']);
            
            if ($update_stmt->execute()) {
                $success = "Password changed successfully!";
            } else {
                $error = "Failed to update password. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Barangay Sto. Rosario</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/images/logo.jpg" alt="Barangay Logo"
                    style="width: 80px; height: 80px; border-radius: 50%; margin-bottom: 10px; border: 3px solid #ffffff;">
                <h2>BARANGAY STO. ROSARIO</h2>
                <p>Treasurer Module</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><details class="sidebar-dropdown"><summary><i class="fas fa-money-bill-wave"></i> Payments <i class="fas fa-chevron-right dropdown-caret"></i></summary><ul class="submenu"><li><a href="payments/list.php"><i class="fas fa-list"></i> All Payments</a></li><li><a href="payments/add.php"><i class="fas fa-plus"></i> Certificate</a></li><li><a href="payments/manual.php?type=donation"><i class="fas fa-heart"></i> Donation</a></li><li><a href="payments/manual.php?type=garbage"><i class="fas fa-trash"></i> Garbage</a></li><li><a href="payments/manual.php?type=rental"><i class="fas fa-building"></i> Rental</a></li></ul></details></li>
                <li><a href="pending_payments/list.php"><i class="fas fa-hourglass-half"></i> Pending Status</a></li>
                <li><a href="cedula/list.php"><i class="fas fa-id-card"></i> Cedula</a></li>
                <li><a href="disbursement/list.php"><i class="fas fa-hand-holding-usd"></i> Disbursements</a></li>
                <li><a href="collections/monthly.php"><i class="fas fa-chart-line"></i> Monthly Collections</a></li>
                <li><a href="collections/analytics.php"><i class="fas fa-landmark"></i> IRA/DV Analytics</a></li>
                <li><a href="collections/annual.php"><i class="fas fa-calendar-alt"></i> Annual Report</a></li>
                <li><a href="change_password.php" class="active"><i class="fas fa-key"></i> Change Password</a></li>
                <li><a href="../logout.php" data-logout-link="true"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="content-header">
                <h1><i class="fas fa-key"></i> Change Password</h1>
                <p>Update your account password</p>
            </div>

            <div class="content-body">
                <div class="card" style="max-width: 600px; margin: 0 auto;">
                    <div class="card-header">
                        <h3><i class="fas fa-lock"></i> Password Settings</h3>
                    </div>

                    <div class="card-body" style="padding: 30px;">
                        <?php if ($success): ?>
                        <div class="success-message">
                            <i class="fas fa-check-circle"></i>
                            <?= htmlspecialchars($success) ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                        <?php endif; ?>

                        <form method="POST" autocomplete="off">
                            <div class="form-group">
                                <label for="current_password">
                                    <i class="fas fa-lock"></i> Current Password
                                </label>
                                <div class="password-field">
                                    <input type="password" id="current_password" name="current_password"
                                        placeholder="Enter your current password" required autocomplete="off">
                                    <button type="button" class="password-toggle" aria-label="Show password"
                                        aria-pressed="false">
                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="new_password">
                                    <i class="fas fa-key"></i> New Password
                                </label>
                                <div class="password-field">
                                    <input type="password" id="new_password" name="new_password"
                                        placeholder="Enter your new password (min. 6 characters)" required
                                        autocomplete="new-password">
                                    <button type="button" class="password-toggle" aria-label="Show password"
                                        aria-pressed="false">
                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <small style="color: #666; font-size: 12px;">
                                    Password must be at least 6 characters long
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">
                                    <i class="fas fa-check-circle"></i> Confirm New Password
                                </label>
                                <div class="password-field">
                                    <input type="password" id="confirm_password" name="confirm_password"
                                        placeholder="Re-enter your new password" required autocomplete="new-password">
                                    <button type="button" class="password-toggle" aria-label="Show password"
                                        aria-pressed="false">
                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>

                            <div style="margin-top: 30px; display: flex; gap: 10px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Change Password
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card" style="max-width: 600px; margin: 30px auto 0;">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle"></i> Password Guidelines</h3>
                    </div>
                    <div class="card-body">
                        <ul style="list-style: none; padding: 0;">
                            <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                                <i class="fas fa-check" style="color: #28a745;"></i>
                                Use at least 6 characters
                            </li>
                            <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                                <i class="fas fa-check" style="color: #28a745;"></i>
                                Use a mix of letters, numbers, and symbols
                            </li>
                            <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                                <i class="fas fa-check" style="color: #28a745;"></i>
                                Avoid using common words or personal information
                            </li>
                            <li style="padding: 10px 0;">
                                <i class="fas fa-check" style="color: #28a745;"></i>
                                Don't share your password with anyone
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/password-toggle.js"></script>
    <script src="../assets/js/logout-modal.js"></script>
    <script src="../assets/js/logout-confirm.js"></script>
</body>

</html>





