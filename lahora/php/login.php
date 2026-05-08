<?php
session_start();
if (isset($_SESSION['user_id']) || isset($_SESSION['username'])) {
    // Redirect based on user role
    if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'super_admin'])) {
        header("Location: dashboard.php");
    } else {
        header("Location: landingpage.php");
    }
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="../css/steam_theme.css">
    <link rel="stylesheet" type="text/css" href="../css/login.css">
    <title>Login - STEAM Vladimir Lahora</title>
</head>

<body>
    <header class="steam-header">
        <div class="steam-logo-container">
            <img src="../IMAGE/Steam_icon_logo.svg.png" alt="Steam Logo" class="steam-logo-img">
            <span class="steam-brand-text">STEAM</span>
        </div>
        <nav class="steam-nav">
            <a href="landingpage.php">HOME</a>
            <a href="registration.php">REGISTER</a>
        </nav>
    </header>

    <main class="login-container">
        <div class="login-box">
            <div class="login-left">
                <h1 class="login-title">SIGN IN</h1>
                <p class="login-subtitle">with a Steam account name</p>
                
                <small id="error-message" style="color: var(--steam-error); margin-bottom: 15px; display: block;"></small>
                
                <form id="loginForm" method="POST">
                    <div class="input-group">
                        <label>STEAM ACCOUNT NAME</label>
                        <input type="text" name="username" id="usernameInput" placeholder="">
                        <small id="username_error" style="color:var(--steam-error);"></small>
                    </div>

                    <div class="input-group">
                        <label>PASSWORD</label>
                        <div class="password-wrapper">
                            <input type="password" id="passwordInput" name="password" placeholder="">
                            <span id="togglePassword">
                                <i class="fas fa-eye" id="eyeIcon"></i>
                            </span>
                        </div>
                        <small id="password_error" style="color:var(--steam-error);"></small>
                    </div>

                    <div class="remember-me">
                        <input type="checkbox" id="remember">
                        <label for="remember">Remember me</label>
                    </div>

                    <button type="submit" class="steam-btn login-submit">Sign In</button>
                    
                    <div class="login-footer-links">
                        <a class="forgotpass" id="forgotpass" href="./forgot_password.php" style="display: none;">Help, I can't sign in</a>
                    </div>
                </form>
            </div>

            <div class="login-right" style="display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.05);">
                <div class="logo-fill" style="text-align: center; padding: 40px;">
                    <img src="../IMAGE/Steam_icon_logo.svg.png" alt="Steam Logo" style="width: 100%; max-width: 180px; opacity: 0.8; filter: drop-shadow(0 0 10px rgba(102, 192, 244, 0.2));">
                    <div style="margin-top: 20px; color: #66c0f4; font-weight: 800; letter-spacing: 4px; font-size: 24px; text-transform: uppercase;">STEAM</div>
                </div>
            </div>
        </div>

        <div class="join-steam">
            <p>New to Steam?</p>
            <a href="../php/registration.php" class="steam-btn-secondary steam-btn">Create an Account</a>
            <p class="join-desc">It's free and easy to use. Discover thousands of games to play with millions of new friends.</p>
        </div>
    </main>

    <footer class="steam-footer">
        <div class="footer-content">
            <div class="footer-left">
                <img src="../IMAGE/footerLogo_valve_new.png" alt="Valve Logo" class="footer-logo-valve">
                <p class="footer-text">&copy; <?php echo date("Y"); ?> Vladimir Lahora. All rights reserved. All trademarks are property of their respective owners in the US and other countries.</p>
                <p class="footer-text">VAT included in all prices where applicable.</p>
            </div>
            <div class="footer-links">
                <a href="#">Privacy Policy</a> | <a href="#">Legal</a> | <a href="#">Steam Subscriber Agreement</a> | <a href="#">Refunds</a>
            </div>
        </div>
    </footer>

    <script src="../javascript/login.js"></script>
</body>

</html>