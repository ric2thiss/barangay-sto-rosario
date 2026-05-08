<?php
include("connection.php");
header('Content-Type: application/json');

$first_name = trim($_POST['first_name'] ?? '');
$surname    = trim($_POST['surname']    ?? '');
$birthdate  = trim($_POST['birthdate']  ?? '');

if (empty($first_name) || empty($surname) || empty($birthdate)) {
    echo json_encode(['duplicate' => false]);
    exit();
}

$stmt = $conn->prepare("
    SELECT id, username, middle_name
    FROM residents
    WHERE first_name = ? AND surname = ? AND birthdate = ? AND is_deleted = 0
    LIMIT 1
");
$stmt->bind_param("sss", $first_name, $surname, $birthdate);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'duplicate' => true,
        'username'  => $row['username'],
        'id'        => $row['id']
    ]);
} else {
    echo json_encode(['duplicate' => false]);
}
$stmt->close();
$conn->close();
?>