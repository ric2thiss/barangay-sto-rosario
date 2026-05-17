<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "treasurer_management";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM payment_status ORDER BY created_at DESC LIMIT 10";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "ID | Resident | Certificate | Purpose | Status | Created At\n";
    echo str_repeat("-", 80) . "\n";
    while($row = $result->fetch_assoc()) {
        echo $row["id"] . " | " . $row["resident_fname"] . " | " . $row["certificate_type"] . " | " . $row["purpose"] . " | " . $row["payment_status"] . " | " . $row["created_at"] . "\n";
    }
} else {
    echo "Table is empty.\n";
}

$conn->close();
?>
