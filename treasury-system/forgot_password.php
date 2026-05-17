<?php
session_start();
include "config/database.php";
include "config/mailer.php";

$success = "";
$error = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, email_verified_at FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = null;

        if ($user = $result->fetch_assoc()) {
            if (empty($user["email_verified_at"])) {
                $error = "Email is not verified. Please verify your email first.";
            }
        }

        if ($error === "" && $user) {
            $invalidate = $conn->prepare("UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL");
            $invalidate->bind_param("i", $user["id"]);
            $invalidate->execute();

            $otp = (string) random_int(100000, 999999);
            $otpHash = password_hash($otp, PASSWORD_DEFAULT);

            $insert = $conn->prepare(
                "INSERT INTO password_resets (user_id, otp_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))"
            );
            $insert->bind_param("is", $user["id"], $otpHash);
            $insert->execute();

            $mailError = "";
            if (!sendOtpEmail($user["email"], $user["name"], $otp, $mailError)) {
                $error = "Email service is not configured. Please contact the administrator.";
            }
        }

        $_SESSION["reset_email"] = $email;
        if ($error === "") {
            $success = "If the email is registered, a 6-digit OTP was sent. It expires in 10 minutes.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="header-banner">
        <h1>TREASURER MANAGEMENT SYSTEM</h1>
    </div>

    <div class="login-container">
        <h3><i class="fas fa-envelope"></i> Forgot Password</h3>

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
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Send OTP
            </button>
        </form>

        <div style="margin-top: 15px; text-align: center;">
            <a href="verify_otp.php">Already have an OTP?</a>
        </div>
        <div style="margin-top: 8px; text-align: center;">
            <a href="treasurer_login.php">Back to login</a>
        </div>
    </div>
</body>

</html>