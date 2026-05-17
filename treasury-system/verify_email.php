<?php
session_start();
include "config/database.php";

$success = "";
$error = "";
$token = trim($_GET["token"] ?? "");

if ($token === "") {
    $error = "Verification token is missing.";
} else {
    $stmt = $conn->prepare(
        "SELECT id, email_verified_at, email_verification_expires_at
         FROM users
         WHERE email_verification_token = ?
         LIMIT 1"
    );
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (!empty($row["email_verified_at"])) {
            $success = "Email is already verified.";
        } elseif (strtotime($row["email_verification_expires_at"]) < time()) {
            $error = "Verification link has expired. Please request a new one.";
        } else {
            $update = $conn->prepare(
                "UPDATE users
                 SET email_verified_at = NOW(), email_verification_token = NULL, email_verification_expires_at = NULL
                 WHERE id = ?"
            );
            $update->bind_param("i", $row["id"]);
            if ($update->execute()) {
                $success = "Email verified successfully. You can now log in.";
            } else {
                $error = "Failed to verify email. Please try again.";
            }
        }
    } else {
        $error = "Invalid verification link.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="header-banner">
        <h1>TREASURER MANAGEMENT SYSTEM</h1>
    </div>

    <div class="login-container">
        <h3><i class="fas fa-envelope-open"></i> Email Verification</h3>

        <?php if ($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <div style="margin-top: 15px; text-align: center;">
            <a href="resend_verification.php">Resend verification email</a>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <div style="margin-top: 15px; text-align: center;">
            <a href="treasurer_login.php">Back to login</a>
        </div>
    </div>
</body>

</html>