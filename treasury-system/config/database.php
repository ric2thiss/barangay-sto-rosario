<?php
// Database Configuration
// IMPORTANT: Update these for Hostinger deployment
$host = "localhost";
$username = "root";
$password = "";
$database = "treasurer_management";

// Define Profiling Database name
define('DB_PROFILING', '`profiling-system`');
define('DB_SERVICES', '`services-system`');
define('SSO_SECRET', 'base64:v0BjU4RsYAW9sNc3mmW0LVm6ZD0ser7DnxhuIYl9fP4='); // Matches Laravel APP_KEY

// Disable mysqli exceptions for production compatibility (PHP 8.1+)
mysqli_report(MYSQLI_REPORT_OFF);

// Create connection
$conn = @new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    // In production, you might want to log this instead of die()
    die("Database Connection failed. Please check your config/database.php credentials.");
}

// Set charset
$conn->set_charset("utf8mb4");
