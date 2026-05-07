<?php
// Database Configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "treasurer_management";

// Define Profiling Database name
define('DB_PROFILING', '`profiling-system`');

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");
