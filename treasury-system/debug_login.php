<?php
session_start();
include "config/database.php";

echo "<h2>Login Debug</h2>";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    echo "<strong>Input received:</strong><br>";
    echo "Username: '$username'<br>";
    echo "Password: '$password'<br><br>";
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        echo "<strong>✓ User found in database</strong><br>";
        echo "User ID: " . $user['id'] . "<br>";
        echo "Name: " . $user['name'] . "<br>";
        echo "Role: " . $user['role'] . "<br>";
        echo "Password hash in DB: " . $user['password'] . "<br><br>";
        
        echo "<strong>Testing password verification:</strong><br>";
        $verify = password_verify($password, $user['password']);
        
        if ($verify) {
            echo "✓ PASSWORD MATCH!<br>";
            echo "Login should work. Redirecting...<br>";
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            
            if ($user['role'] === 'treasurer') {
                echo "<a href='treasurer/dashboard.php'>Click here if not redirected</a>";
                header("Location: treasurer/dashboard.php");
                exit;
            }
        } else {
            echo "✗ PASSWORD DOES NOT MATCH<br>";
            echo "The password you entered doesn't match the hash in database.<br>";
        }
        
    } else {
        echo "<strong>✗ User not found</strong><br>";
        echo "No user with username '$username' exists in database.<br>";
    }

} else {
    echo "<p>POST form to test login. Or use the main login page.</p>";
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Debug Login</title>
</head>

<body>
    <h3>Test Login Form</h3>
    <form method="POST">
        <label>Username: <input type="text" name="username" value="treasurer"></label><br>
        <label>Password: <input type="password" name="password" value="treasurer123"></label><br>
        <button type="submit">Test Login</button>
    </form>
    <br>
    <a href="treasurer_login.php">Back to main login page</a>
</body>

</html>