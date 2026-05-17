<?php
session_start();
include "config/database.php";
include "config/mailer.php";

$success = "";
$error = "";
$name = "";
$username = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if ($name === "" || $username === "" || $email === "" || $password === "" || $confirmPassword === "") {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        $check = $conn->prepare("SELECT username, email FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $result = $check->get_result();

        $duplicateUsername = false;
        $duplicateEmail = false;

        while ($row = $result->fetch_assoc()) {
            if ($row["username"] === $username) {
                $duplicateUsername = true;
            }
            if ($row["email"] === $email) {
                $duplicateEmail = true;
            }
        }

        if ($duplicateUsername) {
            $error = "Username is already taken.";
        } elseif ($duplicateEmail) {
            $error = "Email is already registered.";
        } else {
            $token = bin2hex(random_bytes(16));
            $expiresAt = date("Y-m-d H:i:s", time() + 86400);
            $passwordHash = md5($password);
            $role = "treasurer";

            $insert = $conn->prepare(
                "INSERT INTO users (username, email, password, name, role, email_verification_token, email_verification_expires_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $insert->bind_param("sssssss", $username, $email, $passwordHash, $name, $role, $token, $expiresAt);

            if ($insert->execute()) {
                $userId = $conn->insert_id;

                $scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
                $host = $_SERVER["HTTP_HOST"];
                $basePath = rtrim(dirname($_SERVER["PHP_SELF"]), "/\\");
                $basePath = $basePath === "/" ? "" : $basePath;
                $verifyLink = $scheme . "://" . $host . $basePath . "/verify_email.php?token=" . urlencode($token);

                $mailError = "";
                if (!sendVerificationEmail($email, $name, $verifyLink, $mailError)) {
                    $delete = $conn->prepare("DELETE FROM users WHERE id = ?");
                    $delete->bind_param("i", $userId);
                    $delete->execute();
                    $error = "Verification email could not be sent. Please try again later.";
                } else {
                    $success = "Registration successful. Please check your email to verify your account.";
                    $name = "";
                    $username = "";
                    $email = "";
                }
            } else {
                $error = "Registration failed. Please try again.";
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
    <title>Register</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="header-banner">
        <h1>TREASURER MANAGEMENT SYSTEM</h1>
    </div>

    <div class="login-container">
        <h3><i class="fas fa-user-plus"></i> Create Account</h3>

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
            <a href="resend_verification.php">Resend verification email</a>
        </div>
        <div style="margin-top: 8px; text-align: center;">
            <a href="treasurer_login.php">Back to login</a>
        </div>
        <?php else: ?>
        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label for="name"><i class="fas fa-id-card"></i> Full Name</label>
                <input type="text" id="name" name="name" placeholder="Enter your full name" required
                    value="<?= htmlspecialchars($name) ?>"
                    autocomplete="name">
            </div>
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username</label>
                <input type="text" id="username" name="username" placeholder="Choose a username" required
                    value="<?= htmlspecialchars($username) ?>"
                    autocomplete="username">
            </div>
            <div class="form-group">
                <label for="email"><i class="fas fa-at"></i> Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required
                    value="<?= htmlspecialchars($email) ?>"
                    autocomplete="email">
            </div>
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" placeholder="Create a password" required
                        autocomplete="new-password">
                    <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-check-circle"></i> Confirm Password</label>
                <div class="password-field">
                    <input type="password" id="confirm_password" name="confirm_password" required
                        placeholder="Re-enter your password" autocomplete="new-password">
                    <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Register
            </button>
        </form>
        <div style="margin-top: 15px; text-align: center;">
            <a href="treasurer_login.php">Back to login</a>
        </div>
        <?php endif; ?>
    </div>
    <script src="assets/js/password-toggle.js"></script>
</body>

</html>