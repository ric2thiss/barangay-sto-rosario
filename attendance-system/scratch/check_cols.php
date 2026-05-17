<?php
require_once __DIR__ . '/../bootstrap.php';
$pdo = (new Database())->connect();
$prof = defined('PROFILING_DB_NAME') ? PROFILING_DB_NAME : 'profiling-system';
$barangayTable = '`' . $prof . '`.`barangay_official`';
$stmt = $pdo->query("DESCRIBE $barangayTable");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($cols, JSON_PRETTY_PRINT);
