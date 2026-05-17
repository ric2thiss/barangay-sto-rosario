<?php
require_once __DIR__ . '/../bootstrap.php';
$pdo = (new Database())->connect();
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

$results = [];
foreach ($tables as $table) {
    $stmt = $pdo->query("DESCRIBE `$table` ");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hasDeletedAt = false;
    foreach ($cols as $col) {
        if (strtolower($col['Field']) === 'deleted_at') {
            $hasDeletedAt = true;
            break;
        }
    }
    $results[$table] = $hasDeletedAt ? 'YES' : 'NO';
}

echo json_encode($results, JSON_PRETTY_PRINT);
