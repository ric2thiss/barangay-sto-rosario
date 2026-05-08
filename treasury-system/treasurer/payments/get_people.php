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

// Search from payments
$stmt = $conn->prepare("
    SELECT DISTINCT payer_name as name, 'payment' as source
    FROM payments 
    WHERE payer_name LIKE ?
    LIMIT 10
");
$stmt->bind_param("s", $searchParam);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $people[] = $row;
}

// Search from cedula (full details)
$stmt = $conn->prepare("
    SELECT DISTINCT full_name as name, 'cedula' as source
    FROM cedula 
    WHERE full_name LIKE ?
    LIMIT 10
");
$stmt->bind_param("s", $searchParam);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $people[] = $row;
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





