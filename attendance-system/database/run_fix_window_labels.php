<?php
/**
 * Script to normalize window labels in the database
 * This script fixes inconsistent window label casing
 */

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Window Labels</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .warning { color: orange; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Window Label Normalization Script</h1>
    <p>This script will normalize all window labels to lowercase for consistency.</p>
    <hr>";

try {
    $db = (new Database())->connect();
    
    if (!$db) {
        throw new Exception("Failed to connect to database");
    }
    
    // First, show what needs to be fixed
    echo "<h2>Before Fix:</h2>";
    
    $stmt = $db->query("SELECT DISTINCT `label` FROM `attendance_windows` ORDER BY `label`");
    $labels = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p class='info'><strong>attendance_windows labels:</strong> " . implode(', ', $labels) . "</p>";
    
    $stmt = $db->query("SELECT DISTINCT `window` FROM `attendances` ORDER BY `window`");
    $windows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p class='info'><strong>attendances window values:</strong> " . implode(', ', $windows) . "</p>";
    
    // Show records that need fixing
    $stmt = $db->query("
        SELECT id, employee_id, window, created_at 
        FROM attendances 
        WHERE window != LOWER(TRIM(window))
        ORDER BY id
    ");
    $needsFixing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($needsFixing) > 0) {
        echo "<h3 class='warning'>Records that need fixing:</h3>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Employee ID</th><th>Current Window</th><th>Created At</th></tr>";
        foreach ($needsFixing as $row) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['employee_id']}</td>";
            echo "<td><strong>{$row['window']}</strong> → <span class='success'>" . strtolower(trim($row['window'])) . "</span></td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<h2>Step 1: Normalizing attendance_windows table</h2>";
    
    // Step 1: Normalize attendance_windows table
    $stmt1 = $db->prepare("
        UPDATE `attendance_windows` 
        SET `label` = LOWER(TRIM(`label`))
        WHERE `label` != LOWER(TRIM(`label`))
    ");
    $stmt1->execute();
    $affected1 = $stmt1->rowCount();
    
    echo "<p class='success'>✓ Updated <strong>{$affected1}</strong> record(s) in attendance_windows table</p>";
    
    echo "<h2>Step 2: Normalizing attendances table</h2>";
    
    // Step 2: Normalize attendances table - use a more explicit approach
    // First, let's update all records to lowercase
    $stmt2 = $db->prepare("
        UPDATE `attendances` 
        SET `window` = LOWER(TRIM(`window`))
    ");
    $stmt2->execute();
    $affected2 = $stmt2->rowCount();
    
    echo "<p class='success'>✓ Updated <strong>{$affected2}</strong> record(s) in attendances table</p>";
    
    echo "<hr>";
    echo "<h2>After Fix:</h2>";
    
    // Show results
    $stmt = $db->query("SELECT DISTINCT `label` FROM `attendance_windows` ORDER BY `label`");
    $labels = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p class='success'><strong>attendance_windows labels:</strong> " . implode(', ', $labels) . "</p>";
    
    $stmt = $db->query("SELECT DISTINCT `window` FROM `attendances` ORDER BY `window`");
    $windows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p class='success'><strong>attendances window values:</strong> " . implode(', ', $windows) . "</p>";
    
    echo "<hr>";
    echo "<h2 class='success'>✓ Migration completed successfully!</h2>";
    echo "<p>All window labels have been normalized to lowercase.</p>";
    echo "<p><a href='javascript:window.close()'>Close this window</a></p>";
    
} catch (Exception $e) {
    echo "<h2 class='error'>✗ Error occurred</h2>";
    echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
?>
