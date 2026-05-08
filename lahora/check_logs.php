<?php
/**
 * Check recent log entries from web requests
 */

$conn = new mysqli('localhost', 'root', '', 'optic_system');

if ($conn->connect_error) {
    echo "Database connection failed: " . $conn->connect_error . "\n";
    exit();
}

echo "=== Recent Log Entries Analysis ===\n";

// Get recent log entries
$query = "SELECT * FROM user_logs ORDER BY id DESC LIMIT 10";
$result = $conn->query($query);

if ($result->num_rows === 0) {
    echo "No log entries found\n";
} else {
    echo "Recent log entries:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-5s %-15s %-20s %-15s %-25s %-20s %-20s\n", 
           "ID", "User", "Action", "IP", "Device", "Browser", "Created");
    echo str_repeat("-", 80) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        printf("%-5s %-15s %-20s %-15s %-25s %-20s %-20s\n",
               $row['id'],
               $row['user_name'],
               $row['action'],
               $row['ip_address'] ?? 'NULL',
               substr($row['device'] ?? 'NULL', 0, 25),
               substr($row['browser'] ?? 'NULL', 0, 20),
               $row['created_at']
        );
    }
}

// Check specifically for NULL values
echo "\n=== NULL Values Analysis ===\n";
$null_query = "SELECT COUNT(*) as total,
                      SUM(CASE WHEN ip_address IS NULL THEN 1 ELSE 0 END) as null_ip,
                      SUM(CASE WHEN device IS NULL THEN 1 ELSE 0 END) as null_device,
                      SUM(CASE WHEN browser IS NULL THEN 1 ELSE 0 END) as null_browser
               FROM user_logs";
$null_result = $conn->query($null_query);

if ($stats = $null_result->fetch_assoc()) {
    echo "Total entries: " . $stats['total'] . "\n";
    echo "NULL IP addresses: " . $stats['null_ip'] . "\n";
    echo "NULL devices: " . $stats['null_device'] . "\n";
    echo "NULL browsers: " . $stats['null_browser'] . "\n";
}

// Check entries with actual data
echo "\n=== Entries with Device Data ===\n";
$device_query = "SELECT * FROM user_logs WHERE device IS NOT NULL AND device != '' ORDER BY id DESC LIMIT 5";
$device_result = $conn->query($device_query);

if ($device_result->num_rows === 0) {
    echo "No entries with device data found\n";
} else {
    echo "Entries with device information:\n";
    while ($row = $device_result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " - Device: " . $row['device'] . " - Browser: " . $row['browser'] . "\n";
    }
}

$conn->close();
?>
