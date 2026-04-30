<?php
session_start();
include "config/database.php";

$success = "";
$error = "";

$resetUserId = $_SESSION["password_reset_user_id"] ?? null;
$resetId = $_SESSION["password_reset_id"] ?? null;

if (!$resetUserId || !$resetId) {
    header("Location: forgot_password.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newPassword = $_POST["new_password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if (strlen($newPassword) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        $check = $conn->prepare("SELECT user_id, expires_at, used_at FROM password_resets WHERE id = ?");
        $check->bind_param("i", $resetId);
        $check->execute();
        $result = $check->get_result();
        $row = $result->fetch_assoc();

        if (!$row || (int) $row["user_id"] !== (int) $resetUserId) {
            $error = "Reset request is invalid.";
        } elseif (!empty($row["used_at"])) {
            $error = "This reset request has already been used.";
        } elseif (strtotime($row["expires_at"]) < time()) {
            $error = "Reset request has expired. Please request a new OTP.";
        } else {
            $passwordHash = md5($newPassword);
            $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->bind_param("si", $passwordHash, $resetUserId);

            if ($update->execute()) {
                $mark = $conn->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = ?");
                $mark->bind_param("i", $resetId);
                $mark->execute();

                unset($_SESSION["password_reset_user_id"], $_SESSION["password_reset_id"]);
                $success = "Password has been reset. You can now log in.";
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
    <title>Reset Password</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="header-banner">
        <h1>TREASURER MANAGEMENT SYSTEM</h1>
    </div>

    <div class="login-container">
        <h3><i class="fas fa-lock"></i> Reset Password</h3>

        <?php if ($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($success) ?>
        </div>
        <div style="margin-top: 15px; text-align: center;">
            <a href="treasurer_login.php">Back to login</a>
        </div>
        <?php else: ?>
        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label for="new_password"><i class="fas fa-key"></i> New Password</label>
                <div class="password-field">
                    <input type="password" id="new_password" name="new_password" required
                        placeholder="Enter new password (min. 6 characters)" autocomplete="new-password">
                    <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-check-circle"></i> Confirm Password</label>
                <div class="password-field">
                    <input type="password" id="confirm_password" name="confirm_password" required
                        placeholder="Re-enter new password" autocomplete="new-password">
                    <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Reset Password
            </button>
        </form>
        <?php endif; ?>
    </div>
    <script src="assets/js/password-toggle.js"></script>
</body>

</html>