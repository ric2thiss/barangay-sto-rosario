<?php
require_once __DIR__ . '/../bootstrap.php';
$pdo = (new Database())->connect();
$stmt = $pdo->query("DESCRIBE attendances");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($cols, JSON_PRETTY_PRINT);
