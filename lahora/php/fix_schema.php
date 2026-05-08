<?php
$conn = new mysqli('localhost', 'root', '', 'realestate-project');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

echo "Altering services table...\n";
if ($conn->query("ALTER TABLE services MODIFY created_by VARCHAR(50)")) {
    echo "Successfully modified created_by to VARCHAR(50).\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

// Optional: Fix the existing data if we know who created it.
// Since we don't know for sure, we might just clear it or leave it as '0'.
// But '0' will no longer match every user because now it's a string comparison '0' == 'username'.
?>
