<?php
session_start();

// Access Control - Check if user came from forgot_password.php and answered security questions
if (!isset($_SESSION['can_reset_password']) || $_SESSION['can_reset_password'] !== true || !isset($_SESSION['reset_email'])) {
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
    <title>VERIFY OTP - STEAM Vladimir Lahora</title>
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

        .otp-input-container {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 30px;
        }

        .otp-box {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            border: 1px solid #000;
            border-radius: 2px;
            background: #23262e;
            color: #fff;
            outline: none;
            transition: all 0.2s;
        }

        .otp-box:focus {
            border-color: #66c0f4;
            box-shadow: 0 0 10px rgba(102, 192, 244, 0.2);
        }

        .timer-section {
            text-align: center;
            margin-bottom: 30px;
        }

        #countdown {
            font-size: 32px;
            font-weight: 800;
            color: #66c0f4;
            letter-spacing: 2px;
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

        .verify-btn {
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
        
        .status-message {
            margin-top: 20px;
            text-align: center;
            font-size: 13px;
            padding: 10px;
            border-radius: 2px;
            display: none;
        }
        
        .status-message.error {
            background: rgba(205, 84, 52, 0.1);
            color: #ff4d4d;
            border: 1px solid #cd5434;
        }
        
        .status-message.success {
            background: rgba(102, 192, 244, 0.1);
            color: #66c0f4;
            border: 1px solid #66c0f4;
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
            <h2>VERIFY OTP</h2>

            <div class="user-info">
                <div><span style="color: #8f98a0;">USER:</span> <?php echo htmlspecialchars($username); ?></div>
                <div><span style="color: #8f98a0;">ID:</span> <?php echo htmlspecialchars($idNo); ?></div>
            </div>

            <div class="timer-section">
                <div id="countdown">05:00</div>
                <div style="font-size: 11px; color: #8f98a0; margin-top: 5px; text-transform: uppercase;">Time Remaining</div>
            </div>

            <form id="otpForm">
                <div class="otp-input-container">
                    <input type="text" id="otp_1" name="otp_1" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="Digit 1" autocomplete="one-time-code">
                    <input type="text" id="otp_2" name="otp_2" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="Digit 2" autocomplete="one-time-code">
                    <input type="text" id="otp_3" name="otp_3" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="Digit 3" autocomplete="one-time-code">
                    <input type="text" id="otp_4" name="otp_4" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="Digit 4" autocomplete="one-time-code">
                    <input type="text" id="otp_5" name="otp_5" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="Digit 5" autocomplete="one-time-code">
                    <input type="text" id="otp_6" name="otp_6" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="Digit 6" autocomplete="one-time-code">
                </div>
                <input type="hidden" id="otpInput" name="otp">

                <div style="display: flex; gap: 10px; margin-top: 30px;">
                    <button type="button" class="btnn back-btn" id="resendBtn" style="flex: 1;">RESEND OTP</button>
                    <button type="submit" class="btnn verify-btn" id="verifyBtn" disabled style="flex: 1;">VERIFY</button>
                </div>
            </form>

            <div id="statusMessage" class="status-message"></div>
            <span id="otp_error" style="display:none;"></span>
            <span id="otp_success" style="display:none;"></span>
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

    <script src="../javascript/verify_otp.js"></script>
</body>

</html>