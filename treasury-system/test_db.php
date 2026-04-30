<?php
// Test database connection and check users
include "config/database.php";

echo "<h2>Database Connection Test</h2>";

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "✓ Database connected successfully<br><br>";

echo "<h3>Users in database:</h3>";
$result = $conn->query("SELECT id, username, name, role FROM users");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Role</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No users found in database!</p>";
    echo "<p>Run this SQL to create a treasurer user:</p>";
    echo "<pre>";
    echo "INSERT INTO users (username, password, name, role) VALUES\n";
    echo "('treasurer', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Barangay Treasurer', 'treasurer');\n";
    echo "</pre>";
    echo "<p>Username: <strong>treasurer</strong><br>Password: <strong>treasurer123</strong></p>";
}

echo "<br><h3>Test Password Hash:</h3>";
$testPassword = 'treasurer123';
$testHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$verify = password_verify($testPassword, $testHash);
echo "Password: $testPassword<br>";
echo "Verify result: " . ($verify ? "✓ MATCH" : "✗ NO MATCH") . "<br>";

$conn->close();
