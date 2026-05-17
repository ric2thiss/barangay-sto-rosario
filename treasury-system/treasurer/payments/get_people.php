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

// Search from profiling system (Residents)
$stmt = $conn->prepare("
    SELECT id, CONCAT(first_name, ' ', IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), surname) as name, 'Resident' as source
    FROM " . DB_PROFILING . ".residents 
    WHERE CONCAT(first_name, ' ', IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), surname) LIKE ? AND is_deleted = 0
    LIMIT 10
");
$stmt->bind_param("s", $searchParam);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $people[] = $row;
}

// Search from profiling system (Officials)
$stmt = $conn->prepare("
    SELECT id, CONCAT(first_name, ' ', IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), surname) as name, 'Official' as source
    FROM " . DB_PROFILING . ".barangay_official 
    WHERE CONCAT(first_name, ' ', IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), surname) LIKE ?
    LIMIT 10
");
$stmt->bind_param("s", $searchParam);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $people[] = $row;
}

// Remove duplicates by ID and Source
$uniquePeople = [];
$seen = [];
foreach ($people as $person) {
    $key = $person['source'] . '_' . $person['id'];
    if (!in_array($key, $seen)) {
        $uniquePeople[] = $person;
        $seen[] = $key;
    }
}

echo json_encode($uniquePeople);





