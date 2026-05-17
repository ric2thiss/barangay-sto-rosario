<?php
include "../../config/database.php";
include "../../config/session.php";

header('Content-Type: application/json');

$name = isset($_GET['name']) ? trim($_GET['name']) : '';

if (empty($name)) {
    echo json_encode(['error' => 'Name is required']);
    exit;
}

// Search profiling-system exclusively
$stmt = $conn->prepare("
    SELECT 
        CONCAT(first_name, ' ', IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), surname) as full_name,
        surname, 
        first_name, 
        middle_name, 
        CONCAT(purok, ', ', barangay, ', ', municipality, ', ', province) as address, 
        birthdate as birth_date, 
        age, 
        sex, 
        birthplace as birth_place, 
        civil_status,
        nationality as citizenship, 
        NULL as icr_no, 
        COALESCE(NULLIF(occupation, ''), occupation_type) as occupation, 
        NULL as tin, 
        height, 
        weight, 
        annual_income,
        CONCAT(municipality, ', ', province) as place_of_issue, 
        NULL as year_issued
    FROM " . DB_PROFILING . ".residents 
    WHERE CONCAT(first_name, ' ', IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), surname) = ?
    LIMIT 1
");
$stmt->bind_param("s", $name);
$stmt->execute();
$result = $stmt->get_result();

if ($person = $result->fetch_assoc()) {
    echo json_encode($person);
    exit;
}

// If not in profiling, return empty
echo json_encode(['error' => 'Person not found']);






