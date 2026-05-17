<?php
$servername = "localhost"; // Server name or IP address
$username = "root";        // Database username
$password = "";            // Database password
$dbname = "profiling-system"; // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>