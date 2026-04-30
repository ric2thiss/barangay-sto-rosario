<?php
session_start();
include "config/database.php";

$success = "";
$error = "";
$email = $_SESSION["reset_email"] ?? "";
$otp = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $otp = trim($_POST["otp"] ?? "");

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!preg_match("/^[0-9]{6}$/", $otp)) {
        $error = "Please enter the 6-digit OTP.";
    } else {
        $stmt = $conn->prepare(
            "SELECT pr.id, pr.otp_hash, pr.expires_at, pr.used_at, u.id AS user_id
             FROM password_resets pr
             JOIN users u ON u.id = pr.user_id
             WHERE u.email = ? AND pr.used_at IS NULL
             ORDER BY pr.created_at DESC
             LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $isExpired = strtotime($row["expires_at"]) < time();
            if ($isExpired) {
                $error = "The OTP has expired. Please request a new one.";
            } elseif (!password_verify($otp, $row["otp_hash"])) {
                $error = "Invalid OTP. Please try again.";
            } else {
                $_SESSION["password_reset_user_id"] = (int) $row["user_id"];
                $_SESSION["password_reset_id"] = (int) $row["id"];
                $_SESSION["reset_email"] = $email;
                header("Location: reset_password.php");
                exit;
            }
        } else {
            $error = "Invalid OTP or email.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="header-banner">
        <h1>TREASURER MANAGEMENT SYSTEM</h1>
    </div>

    <div class="login-container">
        <h3><i class="fas fa-key"></i> Verify OTP</h3>

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
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label for="email"><i class="fas fa-at"></i> Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your registered email" required
                    value="<?= htmlspecialchars($email) ?>"
                    autocomplete="email">
            </div>
            <div class="form-group">
                <label for="otp"><i class="fas fa-shield-alt"></i> OTP</label>
                <input type="text" id="otp" name="otp" placeholder="Enter 6-digit OTP" required maxlength="6"
                    value="<?= htmlspecialchars($otp) ?>"
                    inputmode="numeric" pattern="[0-9]{6}">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-check"></i> Verify OTP
            </button>
        </form>

        <div style="margin-top: 15px; text-align: center;">
            <a href="forgot_password.php">Resend OTP</a>
        </div>
        <div style="margin-top: 8px; text-align: center;">
            <a href="treasurer_login.php">Back to login</a>
        </div>
    </div>
</body>

</html>