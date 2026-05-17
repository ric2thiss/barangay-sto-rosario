<?php
$conn = new mysqli('localhost', 'root', '', 'profiling-system');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$result = $conn->query("DESCRIBE residents");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
$conn->close();
?>
