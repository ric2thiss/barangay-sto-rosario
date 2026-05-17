<?php
/**
 * fetch_user_day_logs.php — AJAX endpoint
 * Returns all activity_logs for a specific username on a specific date.
 * Response: JSON array of log entries.
 */
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']); exit();
}

include("connection.php");

$username = trim($_GET['username'] ?? '');
$date     = trim($_GET['date']     ?? '');

if (empty($username) || empty($date)) {
    echo json_encode(['error' => 'Missing parameters']); exit();
}

$stmt = $conn->prepare("
    SELECT action, details, status, login_at, logout_at, duration_sec,
           device_type, browser, os, ip_address, city, country
    FROM activity_logs
    WHERE username = ? AND DATE(login_at) = ?
    ORDER BY login_at DESC
");
$stmt->bind_param('ss', $username, $date);
$stmt->execute();
$result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

echo json_encode($result);
