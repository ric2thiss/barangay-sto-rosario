<?php
ini_set('display_errors', 0);
error_reporting(0);

session_start();

require_once 'connection.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'check_username':
        checkUsername();
        break;
    case 'verify_answers':
        verifyAnswers();
        break;
    case 'validate_answer':
        validateSingleAnswer();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

// ================================================================
// STEP 1 — Look up user by username
// ================================================================
function checkUsername()
{
    global $conn;

    $username = trim($_POST['username'] ?? '');

    if (empty($username)) {
        echo json_encode(['success' => false, 'message' => 'Username is required.']);
        return;
    }

    $stmt = $conn->prepare(
        "SELECT username, idNo, emailAddress, security_question1, security_question2, security_question3
         FROM users WHERE username = ? LIMIT 1"
    );
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();

        // Don't expose the email in the response
        $safe = [
            'username'           => $user_data['username'],
            'idNo'               => $user_data['idNo'],
            'security_question1' => $user_data['security_question1'],
            'security_question2' => $user_data['security_question2'],
            'security_question3' => $user_data['security_question3'],
        ];

        echo json_encode([
            'success'   => true,
            'message'   => 'Username found.',
            'user_data' => $safe
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Username not found in our system.']);
    }

    $stmt->close();
}

// ================================================================
// STEP 2 — Final verify: all 3 answers (at least 2 correct)
// ================================================================
function verifyAnswers()
{
    global $conn;

    $username = trim($_POST['username'] ?? '');
    $answer1  = $_POST['answer1'] ?? '';
    $answer2  = $_POST['answer2'] ?? '';
    $answer3  = $_POST['answer3'] ?? '';

    if (empty($username) || empty($answer1) || empty($answer2) || empty($answer3)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        return;
    }

    $stmt = $conn->prepare(
        "SELECT answer1, answer2, answer3, emailAddress, idNo FROM users WHERE username = ? LIMIT 1"
    );
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();

        $a1_ok = password_verify(trim($answer1), $user_data['answer1']);
        $a2_ok = password_verify(trim($answer2), $user_data['answer2']);
        $a3_ok = password_verify(trim($answer3), $user_data['answer3']);

        $correct_count = ($a1_ok ? 1 : 0) + ($a2_ok ? 1 : 0) + ($a3_ok ? 1 : 0);

        if ($correct_count >= 2) {
            // Set session so reset_password.php knows verification passed
            $_SESSION['reset_username']        = $username;
            $_SESSION['reset_idNo']            = $user_data['idNo'];
            $_SESSION['reset_email']           = $user_data['emailAddress']; // kept for OTP flow
            $_SESSION['can_reset_password']    = true;

            echo json_encode([
                'success'  => true,
                'message'  => 'Security answers verified successfully.',
                'redirect' => 'verify_otp.php'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Not enough correct answers. Please try again.'
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
    }

    $stmt->close();
}

// ================================================================
// Real-time single-answer validation
// ================================================================
function validateSingleAnswer()
{
    global $conn;

    $username        = trim($_POST['username'] ?? '');
    $question_number = $_POST['question_number'] ?? '';
    $answer          = $_POST['answer'] ?? '';

    if (empty($username) || empty($question_number) || empty($answer)) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
        return;
    }

    if (!in_array($question_number, ['1', '2', '3'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid question number.']);
        return;
    }

    $answer_field = 'answer' . $question_number;
    $stmt = $conn->prepare("SELECT `$answer_field` FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row        = $result->fetch_assoc();
        $is_correct = password_verify(trim($answer), $row[$answer_field]);

        echo json_encode([
            'success'         => true,
            'is_correct'      => $is_correct,
            'question_number' => $question_number
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
    }

    $stmt->close();
}

$conn->close();
?>
