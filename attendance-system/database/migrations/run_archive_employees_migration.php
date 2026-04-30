<?php
/**
 * Migration Runner: Create archive_employees table
 * Run this file via browser or CLI to execute the migration
 * 
 * Browser: http://localhost/attendance-system/database/migrations/run_archive_employees_migration.php
 * CLI: php database/migrations/run_archive_employees_migration.php
 */

require_once __DIR__ . '/../../bootstrap.php';

header("Content-Type: text/html; charset=utf-8");

echo "<!DOCTYPE html><html><head><title>Archive Employees Migration</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;}pre{background:#f5f5f5;padding:15px;border-radius:5px;}</style>";
echo "</head><body>";
echo "<h1>Archive Employees Table Migration</h1>";
echo "<pre>";

try {
    $db = (new Database())->connect();
    
    // Read migration SQL file
    $sqlFile = __DIR__ . '/create_archive_employees_table.sql';
    
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
    
    echo "Starting migration...\n";
    echo "========================================\n\n";
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            $db->exec($statement);
            $successCount++;
            echo "✓ Executed: " . substr($statement, 0, 80) . "...\n";
        } catch (PDOException $e) {
            // Check if error is "table already exists" - that's okay
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "ℹ Table already exists (skipping)\n";
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
        echo "\nThe archive_employees table has been created.\n";
        echo "You can now delete employees, and they will be archived automatically.\n";
    } else {
        echo "\n⚠️ Migration completed with errors. Please review above.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
echo "</body></html>";
