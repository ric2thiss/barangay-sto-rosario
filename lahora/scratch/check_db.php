<?php
require_once 'c:/xampp/htdocs/paquibot/php/connection.php';
$res = $conn->query("DESCRIBE services");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
