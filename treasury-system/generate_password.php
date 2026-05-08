<?php
// Password Hash Generator

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['new_password'])) {
    $password = $_POST['new_password'];
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    echo "<div style='background: #d4edda; padding: 20px; border: 1px solid #28a745; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>✓ Password Hash Generated!</h3>";
    echo "<p><strong>Password:</strong> $password</p>";
    echo "<p><strong>Hash:</strong> <code style='background: #fff; padding: 5px; display: block; word-break: break-all;'>$hash</code></p>";
    echo "<hr>";
    echo "<p><strong>SQL to update database:</strong></p>";
    echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
    echo "UPDATE users \nSET password = '$hash'\nWHERE username = 'treasurer';";
    echo "</pre>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Password Hash Generator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f0f4f8;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }

        button {
            background: #1e3a5f;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }

        button:hover {
            background: #FFD700;
            color: #1e3a5f;
        }

        h1 {
            color: #1e3a5f;
        }

        .info {
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #FFD700;
            margin: 20px 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🔐 Password Hash Generator</h1>

        <div class="info">
            <strong>Current Issue:</strong> Your database has an incorrect password hash.<br>
            Use this tool to generate a new hash, then run the SQL to fix it.
        </div>

        <form method="POST">
            <label><strong>Enter your desired password:</strong></label>
            <input type="text" name="new_password" placeholder="e.g., treasurer123" required>
            <button type="submit">Generate Hash</button>
        </form>

        <hr style="margin: 30px 0;">

        <h3>Quick Fix:</h3>
        <p>Or use this pre-made SQL to set password to <strong>treasurer123</strong>:</p>
        <pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;'>UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE username = 'treasurer';</pre>

        <p><a href="debug_login.php">← Test Login</a> | <a href="treasurer_login.php">Main Login Page →</a></p>
    </div>
</body>

</html>