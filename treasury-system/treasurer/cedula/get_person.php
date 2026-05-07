<?php
include "../../config/database.php";
include "../../config/session.php";

header('Content-Type: application/json');

$name = isset($_GET['name']) ? trim($_GET['name']) : '';

if (empty($name)) {
    echo json_encode(['error' => 'Name is required']);
    exit;
}

// pangitaon sa cedula ang first (most complete info)
$stmt = $conn->prepare("
    SELECT full_name, surname, first_name, middle_name, address, birth_date, age, sex, birth_place, civil_status,
           citizenship, icr_no, occupation, tin, height, weight, place_of_issue, year_issued
    FROM cedula 
    WHERE full_name = ?
    ORDER BY issued_date DESC
    LIMIT 1
");
$stmt->bind_param("s", $name);
$stmt->execute();
$result = $stmt->get_result();

if ($person = $result->fetch_assoc()) {
    echo json_encode($person);
    exit;
}

// If not in cedula, return empty (no other tables have this info)
echo json_encode(['error' => 'Person not found']);






