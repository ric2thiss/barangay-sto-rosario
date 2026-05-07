<?php
/**
 * Settings Table Migration Runner
 * Run this file via browser or CLI to execute the migration
 * 
 * Browser: http://localhost/attendance-system/database/migrations/run_settings_migration.php
 * CLI: php database/migrations/run_settings_migration.php
 */

require_once __DIR__ . '/../../bootstrap.php';

header("Content-Type: text/html; charset=utf-8");

echo "<!DOCTYPE html><html><head><title>Settings Table Migration</title>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } pre { background: #f5f5f5; padding: 15px; border-radius: 5px; } .success { color: #28a745; } .error { color: #dc3545; } .info { color: #17a2b8; }</style>";
echo "</head><body>";
echo "<h1>Settings Table Migration</h1>";
echo "<pre>";

try {
    $db = (new Database())->connect();
    
    if (!$db) {
        throw new Exception("Failed to connect to database");
    }
    
    echo "✓ Database connection established\n\n";
    
    // Check if admins table exists (required for foreign key)
    $checkAdmins = $db->query("SHOW TABLES LIKE 'admins'");
    if ($checkAdmins->rowCount() === 0) {
        echo "<span class='error'>✗ Error: 'admins' table does not exist. Please create it first using database/admins_table.sql</span>\n";
        echo "</pre></body></html>";
        exit(1);
    }
    echo "✓ 'admins' table exists\n\n";
    
    // Read migration SQL file
    $sqlFile = __DIR__ . '/create_settings_table.sql';
    
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
    $skippedCount = 0;
    
    foreach ($statements as $index => $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            // Use exec for DDL statements (CREATE TABLE, ALTER TABLE)
            // Use prepare/execute for INSERT statements to handle errors better
            if (stripos($statement, 'INSERT') === 0) {
                $stmt = $db->prepare($statement);
                $stmt->execute();
                echo "<span class='success'>✓ Executed: INSERT statement " . ($index + 1) . "</span>\n";
            } else {
                $db->exec($statement);
                $tableName = '';
                if (preg_match('/CREATE TABLE[^`]*`([^`]+)`/i', $statement, $matches)) {
                    $tableName = $matches[1];
                }
                if ($tableName) {
                    echo "<span class='success'>✓ Executed: CREATE TABLE `{$tableName}`</span>\n";
                } else {
                    echo "<span class='success'>✓ Executed: Statement " . ($index + 1) . "</span>\n";
                }
            }
            $successCount++;
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            
            // Check if error is "table already exists" - that's okay
            if (strpos($errorMsg, 'already exists') !== false || 
                strpos($errorMsg, 'Duplicate entry') !== false ||
                strpos($errorMsg, 'Duplicate key') !== false) {
                echo "<span class='info'>ℹ Skipped (already exists): " . substr($statement, 0, 60) . "...</span>\n";
                $skippedCount++;
            } else {
                $errorCount++;
                echo "<span class='error'>✗ Error: " . htmlspecialchars($errorMsg) . "</span>\n";
                echo "  Statement: " . htmlspecialchars(substr($statement, 0, 100)) . "...\n";
            }
        }
    }
    
    echo "\n";
    echo "========================================\n";
    echo "Migration Summary:\n";
    echo "  Success: {$successCount}\n";
    echo "  Skipped (already exists): {$skippedCount}\n";
    echo "  Errors: {$errorCount}\n";
    echo "========================================\n";
    
    // Verify the table was created
    if ($errorCount === 0) {
        try {
            $verify = $db->query("SELECT COUNT(*) as count FROM `settings`");
            $result = $verify->fetch(PDO::FETCH_ASSOC);
            $settingCount = $result['count'] ?? 0;
            
            echo "\n<span class='success'>✅ Migration completed successfully!</span>\n";
            echo "\nSettings table verification:\n";
            echo "  - Table exists: ✓\n";
            echo "  - Settings count: {$settingCount}\n";
            echo "\nYou can now access the settings page at: admin/settings.php\n";
        } catch (PDOException $e) {
            echo "\n<span class='error'>⚠️ Warning: Could not verify settings table: " . $e->getMessage() . "</span>\n";
        }
    } else {
        echo "\n<span class='error'>⚠️ Migration completed with errors. Please review above.</span>\n";
    }
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Fatal Error: " . htmlspecialchars($e->getMessage()) . "</span>\n";
    echo "\nStack trace:\n" . htmlspecialchars($e->getTraceAsString()) . "\n";
}

echo "</pre>";
echo "</body></html>";
