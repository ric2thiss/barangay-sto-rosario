<?php
include "config/database.php";
$res = $conn->query("SHOW COLUMNS FROM payments");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
