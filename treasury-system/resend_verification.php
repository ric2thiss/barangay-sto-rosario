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
        $user = $result->fetch_assoc();

        if ($user && empty($user["email_verified_at"])) {
            $token = bin2hex(random_bytes(16));
            $expiresAt = date("Y-m-d H:i:s", time() + 86400);

            $update = $conn->prepare(
                "UPDATE users SET email_verification_token = ?, email_verification_expires_at = ? WHERE id = ?"
            );
            $update->bind_param("ssi", $token, $expiresAt, $user["id"]);
            $update->execute();

            $scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
            $host = $_SERVER["HTTP_HOST"];
            $basePath = rtrim(dirname($_SERVER["PHP_SELF"]), "/\\");
            $basePath = $basePath === "/" ? "" : $basePath;
            $verifyLink = $scheme . "://" . $host . $basePath . "/verify_email.php?token=" . urlencode($token);

            $mailError = "";
            if (!sendVerificationEmail($email, $user["name"], $verifyLink, $mailError)) {
                $error = "Verification email could not be sent. Please try again later.";
            }
        }

        if ($error === "") {
            $success = "If the email is registered and unverified, a verification link was sent.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Verification</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="header-banner">
        <h1>TREASURER MANAGEMENT SYSTEM</h1>
    </div>

    <div class="login-container">
        <h3><i class="fas fa-paper-plane"></i> Resend Verification</h3>

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
                <i class="fas fa-envelope"></i> Send Verification Link
            </button>
        </form>

        <div style="margin-top: 15px; text-align: center;">
            <a href="treasurer_login.php">Back to login</a>
        </div>
    </div>
</body>

</html>