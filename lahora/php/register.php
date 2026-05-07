<?php
require_once 'connection.php'; // connects to $conn

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required = ['idNo', 'firstname', 'lastname', 'username', 'email', 'password', 'purokstreet', 'brgy', 'municipal', 'province', 'country', 'zipcode', 'sex', 'birthdate', 'question1', 'answer1', 'question2', 'answer2', 'question3', 'answer3'];

    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo "Missing field: $field";
            exit;
        }
    }

    // Sanitize inputs
    $id = $_POST['idNo'];
    $fname = $_POST['firstname'];
    $mname = $_POST['middlename'] ?? null;
    $lname = $_POST['lastname'];
    $suffix = $_POST['suffix'] ?? null;
    $birthdate = $_POST['birthdate'];
    $age = $_POST['age'] ?? null;
    $sex = $_POST['sex'];
    $email = $_POST['email'];
    $purok = $_POST['purokstreet'];
    $barangay = $_POST['brgy'];
    $municipality = $_POST['municipal'];
    $province = $_POST['province'];
    $country = $_POST['country'];
    $zip = $_POST['zipcode'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Security questions
    $question1 = $_POST['question1'];
    $answer1 = $_POST['answer1'];
    $question2 = $_POST['question2'];
    $answer2 = $_POST['answer2'];
    $question3 = $_POST['question3'];
    $answer3 = $_POST['answer3'];

    // 🔒 Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Hash security answers
    $hashedAnswer1 = password_hash($answer1, PASSWORD_DEFAULT);
    $hashedAnswer2 = password_hash($answer2, PASSWORD_DEFAULT);
    $hashedAnswer3 = password_hash($answer3, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (
        idNo, firstName, middleName, lastName, extension, birthday,  age, sex,
        emailAddress, purok, barangay, municipality, province, country, zipCode,
        username, password, security_question1, answer1, security_question2, answer2, security_question3, answer3, role, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo "SQL prepare error: " . $conn->error;
        exit;
    }

    // Set default role as customer
    $role = 'customer';

    $stmt->bind_param(
        "ssssssssssssssssssssssss",
        $id,
        $fname,
        $mname,
        $lname,
        $suffix,
        $birthdate,
        $age,
        $sex,
        $email,
        $purok,
        $barangay,
        $municipality,
        $province,
        $country,
        $zip,
        $username,
        $hashedPassword,
        $question1,
        $hashedAnswer1,
        $question2,
        $hashedAnswer2,
        $question3,
        $hashedAnswer3,
        $role
    );

    try {
        if ($stmt->execute()) {
            require_once 'user_logger.php';
            logAction('USER_REGISTRATION', "New user registered with username '{$username}' and ID '{$id}'", $username);
            echo "success";
        } else {
            if (str_contains($stmt->error, 'Duplicate')) {
                echo "Duplicate email or username.";
            } else {
                echo "Database error: " . $stmt->error;
            }
        }
    } catch (mysqli_sql_exception $e) {
        if (str_contains($e->getMessage(), 'Duplicate')) {
            echo "Duplicate email or username.";
        } else {
            echo "Database error: " . $e->getMessage();
        }
    }

    $stmt->close();
}
?>
