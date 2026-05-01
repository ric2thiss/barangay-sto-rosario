<?php
// check_maintenance.php
require_once 'config/config.php';

session_start();

header('Content-Type: application/json');

$response = ['maintenance' => false];

$sql = "SELECT value FROM settings WHERE name = 'maintenance_mode'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $maintenance_mode = $row['value'];
    
    // Check if maintenance is enabled
    if ($maintenance_mode == '1') {
        // Check if user is admin
        if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
            $response['maintenance'] = false; // Admin can access
        } else {
            $response['maintenance'] = true;
            $response['message'] = 'System is in maintenance mode';
        }
    } else {
        $response['maintenance'] = false;
        $response['message'] = 'System is operational';
    }
} else {
    $response['maintenance'] = false;
    $response['message'] = 'Unable to determine system status';
}

echo json_encode($response);
?>