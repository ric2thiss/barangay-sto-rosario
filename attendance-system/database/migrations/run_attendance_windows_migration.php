<?php
/**
 * Migration Runner: Create attendance_windows table
 * Run this file via browser or CLI to execute the migration
 * 
 * Browser: http://localhost/attendance-system/database/migrations/run_attendance_windows_migration.php
 * CLI: php database/migrations/run_attendance_windows_migration.php
 */

require_once __DIR__ . '/../../bootstrap.php';

header("Content-Type: text/html; charset=utf-8");

echo "<!DOCTYPE html><html><head><title>Attendance Windows Migration</title></head><body>";
echo "<h1>Attendance Windows Table Migration</h1>";
echo "<pre>";

try {
    $db = (new Database())->connect();
    
    // Read migration SQL file
    $sqlFile = __DIR__ . '/create_attendance_windows_table.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: {$sqlFile}");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Remove comments and split by semicolon
    $sql = preg_replace('/--.*$/m', '', $sql);
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*$/', $stmt);
        }
    );
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            $db->exec($statement);
            $successCount++;
            echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
        } catch (PDOException $e) {
            // Check if error is "table already exists" - that's okay
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "ℹ " . $e->getMessage() . " (skipping)\n";
                $successCount++;
            } else {
                $errorCount++;
                echo "✗ Error: " . $e->getMessage() . "\n";
                echo "  Statement: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }
    
    echo "\n";
    echo "========================================\n";
    echo "Migration Summary:\n";
    echo "  Success: {$successCount}\n";
    echo "  Errors: {$errorCount}\n";
    echo "========================================\n";
    
    if ($errorCount === 0) {
        echo "\n✅ Migration completed successfully!\n";
        echo "\nYou can now manage attendance windows in Master Lists.\n";
    } else {
        echo "\n⚠️ Migration completed with errors. Please review above.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
echo "</body></html>";
