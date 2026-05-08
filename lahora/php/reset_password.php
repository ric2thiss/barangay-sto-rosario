<?php
session_start();

// Access Control - Check if user came from OTP verification
if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true || !isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

// Get user information from session
$email = $_SESSION['reset_email'];
$username = $_SESSION['reset_username'] ?? 'User';
$idNo = $_SESSION['reset_idNo'] ?? '';

// If username or idNo not in session, fetch from database
if (!isset($_SESSION['reset_username']) || !isset($_SESSION['reset_idNo'])) {
    require_once 'connection.php';
    $stmt = $conn->prepare("SELECT username, idNo FROM users WHERE emailAddress = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        $username = $user_data['username'];
        $idNo = $user_data['idNo'];
        $_SESSION['reset_username'] = $username;
        $_SESSION['reset_idNo'] = $idNo;
    }
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/steam_theme.css">
    <title>RESET PASSWORD - STEAM Vladimir Lahora</title>
    <style>
        body {
            background-color: #1b2838 !important;
            font-family: 'Inter', sans-serif !important;
            display: flex !important;
            flex-direction: column !important;
            min-height: 100vh !important;
            margin: 0 !important;
            color: #c7d5e0;
        }

        header {
            background-color: #171a21;
            border-bottom: 1px solid #1b2838;
            padding: 15px 40px;
            width: 100%;
        }

        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .form-container {
            background: #171a21 !important;
            padding: 50px !important;
            border-radius: 4px !important;
            border: 1px solid #3d4450 !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
            max-width: 500px !important;
            width: 100%;
        }

        .form-container h2 {
            font-size: 24px;
            font-weight: 800;
            color: #fff;
            text-align: center;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .user-info {
            background: rgba(0,0,0,0.2);
            padding: 15px;
            border-radius: 2px;
            border: 1px solid #3d4450;
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 25px;
            font-size: 13px;
        }

        .label {
            display: block;
            margin-bottom: 8px;
            font-size: 11px;
            font-weight: 700;
            color: #8f98a0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        input[type="password"] {
            width: 100%;
            padding: 12px;
            background: #23262e;
            border: 1px solid #000;
            border-radius: 2px;
            color: #fff;
            font-size: 14px;
            outline: none;
            margin-bottom: 5px;
        }

        input:focus {
            border-color: #66c0f4;
        }

        .password-requirements {
            background: rgba(0,0,0,0.2);
            padding: 15px;
            border-radius: 2px;
            margin: 20px 0;
            font-size: 12px;
        }

        .password-requirements h4 {
            color: #66c0f4;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }

        .requirement {
            color: #8f98a0;
            list-style: none;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .requirement.met {
            color: #66c0f4;
        }

        .requirement::before {
            content: '\f0c8';
            font-family: 'Font Awesome 6 Free';
            font-weight: 400;
            font-size: 8px;
        }

        .requirement.met::before {
            content: '\f14a';
            font-weight: 900;
        }

        .btnn {
            padding: 12px 20px;
            border: none;
            border-radius: 2px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: filter 0.2s;
        }

        .reset-btn {
            background: linear-gradient(to right, #47bfff 5%, #1a44c2 60%);
            color: #fff;
        }

        .back-btn {
            background: #3d4450;
            color: #c7d5e0;
        }

        .btnn:hover {
            filter: brightness(1.1);
        }

        .btnn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        footer {
            background-color: #171a21;
            padding: 40px 20px;
            text-align: center;
            margin-top: auto;
        }

        footer img {
            height: 25px;
            opacity: 0.6;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <header>
        <div style="max-width: 1400px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <img src="../IMAGE/Steam_icon_logo.svg.png" alt="Steam Logo" style="height: 30px;">
                <h1 style="font-size: 18px; font-weight: 800; color: #fff; margin: 0; text-transform: uppercase; letter-spacing: 2px;">STEAM PORTAL</h1>
            </div>
        </div>
    </header>

    <div class="main-content">
        <div class="form-container">
            <h2>RESET PASSWORD</h2>

            <!-- User Information Display -->
            <div class="user-info">
                <div><span style="color: #8f98a0;">USER:</span> <?php echo htmlspecialchars($username); ?></div>
                <div><span style="color: #8f98a0;">ID:</span> <?php echo htmlspecialchars($idNo); ?></div>
            </div>

            <!-- Password Reset Form -->
            <form id="resetPasswordForm">
                <label class="label" for="newPassword">New Password</label>
                <input type="password" id="newPassword" name="newPassword" placeholder="New Password" required>
                <div id="passwordStrength" style="height: 2px; background: #000; margin-bottom: 15px;">
                    <div id="strengthBar" style="height: 100%; width: 0; transition: width 0.3s;"></div>
                </div>

                <label class="label" for="confirmPassword">Confirm Password</label>
                <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm Password" required>
                <small id="passwordMatch" style="display: block; margin-top: 5px;"></small>

                <!-- Password Requirements -->
                <div class="password-requirements">
                    <h4>Requirements:</h4>
                    <ul style="padding: 0;">
                        <li id="length" class="requirement">At least 8 characters</li>
                        <li id="uppercase" class="requirement">At least 1 uppercase letter</li>
                        <li id="lowercase" class="requirement">At least 1 lowercase letter</li>
                        <li id="number" class="requirement">At least 1 number</li>
                        <li id="special" class="requirement">At least 1 special character</li>
                    </ul>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 30px;">
                    <button type="button" class="btnn back-btn" id="cancelBtn" style="flex: 1;">CANCEL</button>
                    <button type="submit" class="btnn reset-btn" id="resetBtn" disabled style="flex: 1;">RESET PASSWORD</button>
                </div>
            </form>

            <div id="statusMessage" style="margin-top: 20px; text-align: center; font-size: 13px;"></div>
        </div>
    </div>

    <footer>
        <div style="max-width: 1200px; margin: 0 auto;">
            <img src="../IMAGE/footerLogo_valve_new.png" alt="Valve Logo">
            <p style="font-size: 12px; color: #8f98a0; margin: 0; line-height: 1.6;">
                &copy; 2026 STEAM Vladimir Lahora. All rights reserved.
            </p>
        </div>
    </footer>

    <script src="../javascript/reset_password.js"></script>
</body>

</html>