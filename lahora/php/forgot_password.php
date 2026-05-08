<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/steam_theme.css">
    <title>FORGOT PASSWORD - STEAM Vladimir Lahora</title>
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

        /* ===== Step Indicator ===== */
        .step-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 40px;
            gap: 0;
        }

        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            position: relative;
            flex: 1;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 2px;
            background: #23262e;
            color: #8f98a0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 700;
            border: 1px solid #000;
            transition: all 0.3s;
            position: relative;
            z-index: 1;
        }

        .step-label {
            font-size: 10px;
            font-weight: 700;
            color: #8f98a0;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: center;
        }

        .step-connector {
            flex: 1;
            height: 2px;
            background: #000;
            margin-top: -25px;
            position: relative;
            z-index: 0;
        }

        .step-item.active .step-circle {
            background: #66c0f4;
            color: #fff;
            border-color: #66c0f4;
            box-shadow: 0 0 15px rgba(102, 192, 244, 0.3);
        }

        .step-item.active .step-label {
            color: #66c0f4;
        }

        .step-item.completed .step-circle {
            background: #1a44c2;
            border-color: #1a44c2;
            color: #fff;
        }

        .step-connector.completed {
            background: #1a44c2;
        }

        /* Form styling */
        .label {
            display: block;
            margin-bottom: 8px;
            font-size: 11px;
            font-weight: 700;
            color: #8f98a0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        input[type="text"], input[type="password"] {
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

        .user-info {
            background: rgba(0,0,0,0.2);
            padding: 15px;
            border-radius: 2px;
            border: 1px solid #3d4450;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
        }

        .user-info-avatar {
            width: 40px;
            height: 40px;
            background: #2a475e;
            border-radius: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 700;
            color: #66c0f4;
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

        .eye-toggle {
            background: none;
            border: none;
            color: #8f98a0;
            cursor: pointer;
            padding: 0;
            margin-left: -35px;
            z-index: 5;
            position: relative;
        }

        .input-wrapper {
            display: flex;
            align-items: center;
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
            <h2>RECOVER ACCOUNT</h2>

            <!-- ===== STEP INDICATOR ===== -->
            <div class="step-indicator">
                <div class="step-item active" id="step1Item">
                    <div class="step-circle" id="step1Circle">
                        <i class="fas fa-user"></i>
                    </div>
                    <span class="step-label">Username</span>
                </div>

                <div class="step-connector" id="stepConnector"></div>

                <div class="step-item" id="step2Item">
                    <div class="step-circle" id="step2Circle">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <span class="step-label">Security</span>
                </div>
            </div>

            <!-- ===== STEP 1: Username Verification ===== -->
            <div id="usernameTab" class="tab-content active">
                <form id="usernameForm">
                    <label class="label" for="usernameInput">Enter your Username</label>
                    <input type="text" id="usernameInput" name="username" placeholder="Username" autocomplete="username">
                    <small id="username_error" style="color:#ff4d4d;"></small>

                    <div style="display: flex; gap: 10px; margin-top: 30px;">
                        <button type="button" class="btnn back-btn" onclick="window.location.href='login.php'" style="flex: 1;">CANCEL</button>
                        <button type="submit" class="btnn verify-btn" id="verifyUsernameBtn" style="flex: 1;">NEXT</button>
                    </div>
                </form>
            </div>

            <!-- ===== STEP 2: Security Questions ===== -->
            <div id="securityTab" class="tab-content" style="display: none;">
                <div class="user-info">
                    <div class="user-info-avatar" id="userAvatar">?</div>
                    <div class="user-info-text">
                        <div style="font-weight: 700; color: #fff;" id="userUsername"></div>
                        <div style="font-size: 12px; color: #8f98a0;">ID: <span id="userIdNo"></span></div>
                    </div>
                </div>

                <div style="font-size: 12px; color: #66c0f4; margin-bottom: 20px; display: flex; gap: 8px; align-items: flex-start;">
                    <i class="fas fa-info-circle" style="margin-top: 2px;"></i>
                    <span>Answer 2 of 3 security questions to proceed.</span>
                </div>

                <form id="securityForm">
                    <div class="security-questions">
                        <!-- Q1 -->
                        <div style="margin-bottom: 20px;">
                            <label class="label" id="question1"></label>
                            <div class="input-wrapper">
                                <input type="password" id="answer1" name="answer1" placeholder="Answer">
                                <button type="button" class="eye-toggle" onclick="togglePassword('answer1', this)">
                                    <i class="fas fa-eye eye-icon"></i>
                                    <i class="fas fa-eye-slash eye-off-icon" style="display:none;"></i>
                                </button>
                                <span id="icon1" style="display:none; margin-left: 10px;"></span>
                            </div>
                        </div>

                        <!-- Q2 -->
                        <div style="margin-bottom: 20px;">
                            <label class="label" id="question2"></label>
                            <div class="input-wrapper">
                                <input type="password" id="answer2" name="answer2" placeholder="Answer">
                                <button type="button" class="eye-toggle" onclick="togglePassword('answer2', this)">
                                    <i class="fas fa-eye eye-icon"></i>
                                    <i class="fas fa-eye-slash eye-off-icon" style="display:none;"></i>
                                </button>
                                <span id="icon2" style="display:none; margin-left: 10px;"></span>
                            </div>
                        </div>

                        <!-- Q3 -->
                        <div style="margin-bottom: 20px;">
                            <label class="label" id="question3"></label>
                            <div class="input-wrapper">
                                <input type="password" id="answer3" name="answer3" placeholder="Answer">
                                <button type="button" class="eye-toggle" onclick="togglePassword('answer3', this)">
                                    <i class="fas fa-eye eye-icon"></i>
                                    <i class="fas fa-eye-slash eye-off-icon" style="display:none;"></i>
                                </button>
                                <span id="icon3" style="display:none; margin-left: 10px;"></span>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 30px;">
                        <button type="button" class="btnn back-btn" id="findAnotherUsername" style="flex: 1;">BACK</button>
                        <button type="submit" class="btnn verify-btn" id="verifyAnswers" disabled style="flex: 1;">VERIFY</button>
                    </div>
                </form>
            </div>

            <small id="error-message" style="color:#ff4d4d; margin-top:15px; display: block; text-align: center;"></small>
            <small id="success-message" style="color:#66c0f4; margin-top:15px; display: block; text-align: center;"></small>
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

    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const eyeIcon = btn.querySelector('.eye-icon');
            const eyeOffIcon = btn.querySelector('.eye-off-icon');
            if (input.type === 'password') {
                input.type = 'text';
                eyeIcon.style.display = 'none';
                eyeOffIcon.style.display = 'inline-block';
            } else {
                input.type = 'password';
                eyeIcon.style.display = 'inline-block';
                eyeOffIcon.style.display = 'none';
            }
        }
    </script>
    <script src="../javascript/forgot_password.js"></script>
</body>

</html>