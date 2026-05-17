<?php
include "config/database.php";
$res = $conn->query("SELECT id, payer_name, resident_id FROM payments ORDER BY id DESC LIMIT 5");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
