<?php
require 'php/connection.php';
echo "--- SERVICES ---\n";
$res = $conn->query('SELECT * FROM services');
while($row = $res->fetch_assoc()) print_r($row);

echo "\n--- USERS ---\n";
$res = $conn->query('SELECT username, role, firstName, lastName FROM users');
while($row = $res->fetch_assoc()) print_r($row);
?>
