<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'connection.php';

$field = $_GET['field'] ?? '';
$value = $_GET['value'] ?? '';
$username = $_GET['username'] ?? ''; // Optional for password check

$allowed = ['idNo', 'emailAddress', 'username'];

if (!in_array($field, $allowed)) {
    echo json_encode(['exists' => false]);
    exit;
}



// Generic uniqueness check for idno/email/username
$stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE $field = ?");
$stmt->bind_param("s", $value);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

echo json_encode(['exists' => $count > 0]);
