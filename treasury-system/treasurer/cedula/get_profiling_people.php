<?php
include "../../config/database.php";
include "../../config/session.php";

header('Content-Type: application/json');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (empty($search)) {
    echo json_encode([]);
    exit;
}

$searchParam = "%{$search}%";
$people = [];

// Search from profiling system ONLY
$stmt = $conn->prepare("
    SELECT id, CONCAT(first_name, ' ', IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), surname) as name, 'profiling' as source
    FROM " . DB_PROFILING . ".residents 
    WHERE CONCAT(first_name, ' ', IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), surname) LIKE ?
       OR first_name LIKE ?
       OR surname LIKE ?
    LIMIT 20
");
$stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $people[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'source' => $row['source']
    ];
}

// Remove duplicates
$uniquePeople = [];
$seen = [];
foreach ($people as $person) {
    if (!in_array($person['name'], $seen)) {
        $uniquePeople[] = $person;
        $seen[] = $person['name'];
    }
}

echo json_encode($uniquePeople);
