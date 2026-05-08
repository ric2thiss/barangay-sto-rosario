<?php
$conn = new mysqli('localhost', 'root', '', 'realestate-project');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

echo "--- SERVICES ---\n";
$res = $conn->query("SELECT * FROM services");
while($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\n--- SERVICES SCHEMA ---\n";
$res = $conn->query("DESCRIBE services");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
