<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'connection.php';
require_once 'user_logger.php';

$session_user_id = $_SESSION['user_id'];
$session_role = $_SESSION['role'];

// Support admin completing profile for another user
$target_user_id = $session_user_id;
if (isset($_GET['id']) && in_array($session_role, ['admin', 'super_admin'])) {
    $target_user_id = $_GET['id'];
}

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE idNo = ?");
$stmt->bind_param("s", $target_user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header("Location: login.php");
    exit();
}
$user_data = $result->fetch_assoc();

// If not incomplete and not an admin viewing/editing, redirect
if ($user_data['status'] !== 'incomplete' && !isset($_GET['id'])) {
    if (in_array($session_role, ['admin', 'super_admin'])) {
        header("Location: dashboard.php");
    } else {
        header("Location: customer_dashboard.php");
    }
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question1 = $_POST['question1'] ?? '';
    $answer1 = $_POST['answer1'] ?? '';
    $question2 = $_POST['question2'] ?? '';
    $answer2 = $_POST['answer2'] ?? '';
    $question3 = $_POST['question3'] ?? '';
    $answer3 = $_POST['answer3'] ?? '';

    if (empty($question1) || empty($answer1) || empty($question2) || empty($answer2) || empty($question3) || empty($answer3)) {
        $error_message = "Please fill in all required security questions.";
    } else {
        $hashedAnswer1 = password_hash($answer1, PASSWORD_DEFAULT);
        $hashedAnswer2 = password_hash($answer2, PASSWORD_DEFAULT);
        $hashedAnswer3 = password_hash($answer3, PASSWORD_DEFAULT);

        $update_sql = "UPDATE users SET 
            security_question1=?, answer1=?, security_question2=?, answer2=?, security_question3=?, answer3=?, 
            status='active' 
            WHERE idNo=?";

        $upd_stmt = $conn->prepare($update_sql);
        $upd_stmt->bind_param(
            "sssssss",
            $question1,
            $hashedAnswer1,
            $question2,
            $hashedAnswer2,
            $question3,
            $hashedAnswer3,
            $target_user_id
        );

        if ($upd_stmt->execute()) {
            logAction('PROFILE_COMPLETE', "Profile completed for user ID: {$target_user_id}");

            if ($target_user_id !== $session_user_id) {
                header("Location: user_management.php?success=1");
            } else {
                if (in_array($user_data['role'], ['admin', 'super_admin'])) {
                    header("Location: dashboard.php");
                } else {
                    header("Location: customer_dashboard.php");
                }
            }
            exit();
        } else {
            $error_message = "Database Error: " . $conn->error;
        }
    }
}
?>

<<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COMPLETE PROFILE - STEAM Vladimir Lahora</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/steam_theme.css">
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

        .formreg {
            background: #171a21 !important;
            padding: 50px !important;
            border-radius: 4px !important;
            border: 1px solid #3d4450 !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
            max-width: 1000px !important;
            margin: 60px auto !important;
            width: 90%;
        }

        .formreg h2 {
            font-size: 24px;
            font-weight: 800;
            color: #fff;
            text-align: center;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .formreg p {
            color: #8f98a0 !important;
            font-size: 15px !important;
            text-align: center !important;
            margin-bottom: 40px !important;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #66c0f4;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding-bottom: 10px;
            border-bottom: 1px solid #3d4450;
            margin: 30px 0 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 11px;
            font-weight: 700;
            color: #8f98a0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #000;
            border-radius: 2px;
            font-size: 14px;
            background: #23262e;
            color: #fff;
            outline: none;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #66c0f4;
        }

        .btnn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(to right, #47bfff 5%, #1a44c2 60%) !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 2px !important;
            font-size: 14px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            cursor: pointer !important;
            margin-top: 40px !important;
            transition: filter 0.2s;
        }

        .btnn:hover {
            filter: brightness(1.1);
        }

        .security-questions-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .security-card {
            background-color: rgba(0,0,0,0.2);
            padding: 20px;
            border-radius: 4px;
            border: 1px solid #3d4450;
        }

        @media (max-width: 768px) {
            .security-questions-container {
                grid-template-columns: 1fr;
            }
        }

        .password-wrapper {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            z-index: 10;
            color: #8f98a0;
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

    <div class="main">
        <form class="formreg" method="POST">
            <h2>COMPLETE YOUR PROFILE</h2>
            <p>Set up your security questions to secure your account.</p>

            <?php if ($error_message): ?>
                <div style="background-color: rgba(205, 84, 52, 0.2); color: #ff4d4d; padding: 12px; border-radius: 2px; margin-bottom: 25px; text-align: center; font-size: 14px; font-weight: 600; border: 1px solid #cd5434;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <h3 class="section-title">Security Recovery</h3>
            <div class="security-questions-container">
                <div class="security-card">
                    <div class="form-group">
                        <label>Question 1 <span style="color:#cd5434;">*</span></label>
                        <select name="question1" id="question1" required>
                            <option value="">Select a question</option>
                            <option value="Who was your childhood hero?" <?php echo ($user_data['security_question1'] == 'Who was your childhood hero?') ? 'selected' : ''; ?>>Who was your childhood hero?</option>
                            <option value="What was the name of your first pet?" <?php echo ($user_data['security_question1'] == 'What was the name of your first pet?') ? 'selected' : ''; ?>>What was the name of your first pet?</option>
                            <option value="What is your favorite book?" <?php echo ($user_data['security_question1'] == 'What is your favorite book?') ? 'selected' : ''; ?>>What is your favorite book?</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Recovery Answer</label>
                        <div class="password-wrapper">
                            <input type="password" id="answer1" name="answer1" placeholder="Enter answer" required>
                            <span class="toggle-password" onclick="toggleSecurityAnswer('answer1', this)">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="security-card">
                    <div class="form-group">
                        <label>Question 2 <span style="color:#cd5434;">*</span></label>
                        <select name="question2" id="question2" required>
                            <option value="">Select a question</option>
                            <option value="What is the name of the street you grew up on?" <?php echo ($user_data['security_question2'] == 'What is the name of the street you grew up on?') ? 'selected' : ''; ?>>What is the name of the street you grew up on?</option>
                            <option value="What school did you attend for sixth grade?" <?php echo ($user_data['security_question2'] == 'What school did you attend for sixth grade?') ? 'selected' : ''; ?>>What school did you attend for sixth grade?</option>
                            <option value="What was your dream job as a child?" <?php echo ($user_data['security_question2'] == 'What was your dream job as a child?') ? 'selected' : ''; ?>>What was your dream job as a child?</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Recovery Answer</label>
                        <div class="password-wrapper">
                            <input type="password" id="answer2" name="answer2" placeholder="Enter answer" required>
                            <span class="toggle-password" onclick="toggleSecurityAnswer('answer2', this)">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="security-card">
                    <div class="form-group">
                        <label>Question 3 <span style="color:#cd5434;">*</span></label>
                        <select name="question3" id="question3" required>
                            <option value="">Select a question</option>
                            <option value="In what city did your parents meet?" <?php echo ($user_data['security_question3'] == 'In what city did your parents meet?') ? 'selected' : ''; ?>>In what city did your parents meet?</option>
                            <option value="What was the name of your first teacher?" <?php echo ($user_data['security_question3'] == 'What was the name of your first teacher?') ? 'selected' : ''; ?>>What was the name of your first teacher?</option>
                            <option value="What is your favorite movie?" <?php echo ($user_data['security_question3'] == 'What is your favorite movie?') ? 'selected' : ''; ?>>What is your favorite movie?</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Recovery Answer</label>
                        <div class="password-wrapper">
                            <input type="password" id="answer3" name="answer3" placeholder="Enter answer" required>
                            <span class="toggle-password" onclick="toggleSecurityAnswer('answer3', this)">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btnn">COMPLETE PROFILE</button>
        </form>
    </div>

    <footer>
        <div style="max-width: 1200px; margin: 0 auto;">
            <img src="../IMAGE/footerLogo_valve_new.png" alt="Valve Logo">
            <p style="font-size: 12px; color: #8f98a0; margin: 0; line-height: 1.6;">
                &copy; 2026 STEAM Vladimir Lahora. All rights reserved. <br>
                All trademarks are property of their respective owners in the US and other countries.
            </p>
        </div>
    </footer>

    <script src="../javascript/complete_profile_validation.js"></script>
    <script>
        const toggleSecurityAnswer = (fieldId, toggleElement) => {
            const input = document.getElementById(fieldId);
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            const icon = toggleElement.querySelector('i');
            icon.className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
        };
    </script>
</body>

</html>
ml>