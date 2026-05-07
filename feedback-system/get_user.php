<?php
require_once 'config/config.php';
$result = $conn->query("SELECT id FROM users LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    echo "Valid User ID: " . $row['id'];
} else {
    echo "No users found.";
}
?>
