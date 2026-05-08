<?php
session_start();
if (isset($_SESSION['user_id']) || isset($_SESSION['username'])) {
    // Redirect to landing page if already logged in
    header("Location: landingpage.php");
    exit();
}

if (isset($_SESSION['lockout_active']) && $_SESSION['lockout_active'] === true) {
    header("Location: login.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CREATE ACCOUNT - STEAM Vladimir Lahora</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/steam_theme.css">
    <link rel="stylesheet" type="text/css" href="../css/register.css">
</head>

<body class="register-body">

    <header class="steam-header">
        <div class="steam-logo-container">
            <img src="../IMAGE/Steam_icon_logo.svg.png" alt="Steam Logo" class="steam-logo-img">
            <span class="steam-brand-text">STEAM</span>
        </div>
        <nav class="steam-nav">
            <a href="landingpage.php">HOME</a>
            <a href="login.php">LOGIN</a>
        </nav>
    </header>

    <main class="register-container">
        <div class="register-box">
            <h1 class="register-title">CREATE YOUR ACCOUNT</h1>
            
            <form class="formreg" name="myform" id="myform" method="POST">
                <!-- Step 1: Personal Information -->
                <div id="step1" class="registration-step">
                    <div class="section">
                        <h3 class="section-subtitle">PERSONAL INFORMATION</h3>
                        <div class="register-grid">
                            <div class="input-group">
                                <label>ACCOUNT ID (XXXX-XXXX)</label>
                                <input type="text" id="idNo" name="idNo" placeholder="" maxlength="9">
                                <small id="idNo-error" class="error-text"></small>
                            </div>
                            <div class="input-group">
                                <label>FIRST NAME</label>
                                <input type="text" id="firstname" name="firstname" placeholder="">
                                <small id="firstname-error" class="error-text"></small>
                            </div>
                            <div class="input-group">
                                <label>MIDDLE NAME</label>
                                <input type="text" id="middlename" name="middlename" placeholder="">
                                <small id="middlename-error" class="error-text"></small>
                            </div>
                            <div class="input-group">
                                <label>LAST NAME</label>
                                <input type="text" id="lastname" name="lastname" placeholder="">
                                <small id="lastname-error" class="error-text"></small>
                            </div>
                            <div class="input-group">
                                <label>EXTENSION (JR, SR, III...)</label>
                                <input type="text" id="extension" name="extension" placeholder="">
                                <small id="extension-error" class="error-text"></small>
                            </div>
                            <div class="input-group">
                                <label>SEX</label>
                                <select id="sex" name="sex">
                                    <option value="" disabled selected hidden>Select</option>
                                    <option value="Female">Female</option>
                                    <option value="Male">Male</option>
                                </select>
                                <small id="sex-error" class="error-text"></small>
                            </div>
                            <div class="input-group">
                                <label>BIRTHDAY</label>
                                <input type="date" name="birthdate" id="birthdate" oninput="calculateAge()" max="<?= date('Y-m-d') ?>">
                                <small id="birthdate-error" class="error-text"></small>
                            </div>
                            <div class="input-group">
                                <label>AGE</label>
                                <input type="text" name="age" id="age" placeholder="" readonly>
                                <small id="age-error" class="error-text"></small>
                            </div>
                        </div>

                        <h3 class="section-subtitle" style="margin-top: 30px;">ADDRESS INFORMATION</h3>
                        <div class="register-grid">
                            <div class="input-group">
                                <label>PUROK / STREET</label>
                                <input type="text" id="purokstreet" name="purokstreet" placeholder="">
                                <small id="purokstreet-error" class="error-text"></small>
                            </div>
                            <div class="input-group">
                                <label>BARANGAY</label>
                                <input type="text" id="brgy" name="brgy" placeholder="">
                                <small id="brgy-error" class="error-text"></small>
                            </div>
                            <div class="input-group">
                                <label>MUNICIPALITY</label>
                                <input type="text" id="municipal" name="municipal" placeholder="">
                                <small id="municipal-error" class="error-text"></small>
                            </div>
                            <div class="input-group">
                                <label>PROVINCE</label>
                                <input type="text" id="province" name="province" placeholder="">
                                <small id="province-error" class="error-text"></small>
                            </div>
                            <div class="input-group">
                                <label>COUNTRY</label>
                                <input type="text" id="country" name="country" placeholder="">
                                <small id="country-error" class="error-text"></small>
                            </div>
                            <div class="input-group">
                                <label>ZIP CODE</label>
                                <input type="text" id="zipcode" name="zipcode" placeholder="" maxlength="4">
                                <small id="zipcode-error" class="error-text"></small>
                            </div>
                        </div>
                    </div>

                    <div class="button-row">
                        <button type="button" id="nextBtn" class="steam-btn">Continue</button>
                    </div>
                </div>

                <!-- Step 2: Security & Account -->
                <div id="step2" class="registration-step" style="display: none;">
                    <div class="section">
                        <h3 class="section-subtitle">ACCOUNT DETAILS</h3>
                        <div class="register-grid">
                            <div class="input-group">
                                <label>STEAM ACCOUNT NAME</label>
                                <input type="text" id="username" name="username" placeholder="">
                                <small id="username-error" class="error-text"></small>
                            </div>
                            <div class="input-group">
                                <label>EMAIL ADDRESS</label>
                                <input type="text" name="email" id="email" placeholder="">
                                <small id="email-error" class="error-text"></small>
                            </div>
                            <div class="input-group">
                                <label>PASSWORD</label>
                                <input id="password" name="password" type="password" placeholder="">
                                <small id="password-error" class="error-text"></small>
                            </div>
                            <div class="input-group">
                                <label>CONFIRM PASSWORD</label>
                                <input type="password" name="confirmPassword" id="confirm_password" placeholder="">
                                <small id="confirm_password-error" class="error-text"></small>
                            </div>
                        </div>

                        <h3 class="section-subtitle" style="margin-top: 30px;">SECURITY SETUP</h3>
                        <div class="security-questions register-grid">
                            <div class="input-group">
                                <label>SECURITY QUESTION 1</label>
                                <select id="question1" name="question1">
                                    <option value="" disabled selected>Select a question</option>
                                    <option value="Who is your bestfriend in elementary?">Who is your bestfriend in elementary?</option>
                                    <option value="What is the name of your favorite pet?">What is the name of your favorite pet?</option>
                                    <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                                    <option value="What was the name of your first school?">What was the name of your first school?</option>
                                </select>
                                <div style="position: relative;">
                                    <input type="password" id="answer1" name="answer1" placeholder="Your Answer" style="margin-top: 10px; width: 100%;">
                                    <span onclick="toggleSecurityAnswer('answer1', this)" style="position: absolute; right: 10px; top: 20px; cursor: pointer; color: #8f98a0;">
                                        <i class="fas fa-eye-slash"></i>
                                    </span>
                                </div>
                                <small id="question1-error" class="error-text"></small>
                            </div>
                            
                            <div class="input-group">
                                <label>SECURITY QUESTION 2</label>
                                <select id="question2" name="question2">
                                    <option value="" disabled selected>Select a question</option>
                                    <option value="Who is your bestfriend in elementary?">Who is your bestfriend in elementary?</option>
                                    <option value="What is the name of your favorite pet?">What is the name of your favorite pet?</option>
                                    <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                                    <option value="What was the name of your first school?">What was the name of your first school?</option>
                                </select>
                                <div style="position: relative;">
                                    <input type="password" id="answer2" name="answer2" placeholder="Your Answer" style="margin-top: 10px; width: 100%;">
                                    <span onclick="toggleSecurityAnswer('answer2', this)" style="position: absolute; right: 10px; top: 20px; cursor: pointer; color: #8f98a0;">
                                        <i class="fas fa-eye-slash"></i>
                                    </span>
                                </div>
                                <small id="question2-error" class="error-text"></small>
                            </div>

                            <div class="input-group">
                                <label>SECURITY QUESTION 3</label>
                                <select id="question3" name="question3">
                                    <option value="" disabled selected>Select a question</option>
                                    <option value="Who is your bestfriend in elementary?">Who is your bestfriend in elementary?</option>
                                    <option value="What is the name of your favorite pet?">What is the name of your favorite pet?</option>
                                    <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                                    <option value="What was the name of your first school?">What was the name of your first school?</option>
                                </select>
                                <div style="position: relative;">
                                    <input type="password" id="answer3" name="answer3" placeholder="Your Answer" style="margin-top: 10px; width: 100%;">
                                    <span onclick="toggleSecurityAnswer('answer3', this)" style="position: absolute; right: 10px; top: 20px; cursor: pointer; color: #8f98a0;">
                                        <i class="fas fa-eye-slash"></i>
                                    </span>
                                </div>
                                <small id="question3-error" class="error-text"></small>
                            </div>
                        </div>
                    </div>

                    <div class="button-row">
                        <button type="button" id="prevBtn" class="steam-btn steam-btn-secondary">Back</button>
                        <button type="submit" id="registerBtn" class="steam-btn">Complete Sign-up</button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <footer class="steam-footer">
        <div class="footer-content">
            <div class="footer-left">
                <img src="../IMAGE/footerLogo_valve_new.png" alt="Valve Logo" class="footer-logo-valve">
                <p class="footer-text">&copy; <?php echo date("Y"); ?> Vladimir Lahora. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="../javascript/register.js"></script>
    <script src="../javascript/global_functions.js"></script>
</body>

</html>