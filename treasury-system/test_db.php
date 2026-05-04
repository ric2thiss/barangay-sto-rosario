<?php
$conn = new mysqli("localhost", "root", "");
$result = $conn->query("SHOW DATABASES");
while ($row = $result->fetch_row()) {
    echo $row[0] . "\n";
}
