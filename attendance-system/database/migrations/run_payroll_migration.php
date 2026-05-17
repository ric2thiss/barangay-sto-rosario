<?php
/**
 * Payroll Tables Migration Runner
 * Run this file via browser or CLI to execute the migration
 * 
 * Browser: http://localhost/attendance-system/database/migrations/run_payroll_migration.php
 * CLI: php database/migrations/run_payroll_migration.php
 */

require_once __DIR__ . '/../../bootstrap.php';

header("Content-Type: text/html; charset=utf-8");

echo "<!DOCTYPE html><html><head><title>Payroll Migration Runner</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    pre { background: #fff; padding: 15px; border-radius: 5px; border: 1px solid #ddd; }
    h1 { color: #333; }
    .success { color: #28a745; }
    .error { color: #dc3545; }
    .info { color: #17a2b8; }
</style></head><body>";
echo "<h1>Payroll Tables Migration</h1>";
echo "<pre>";

try {
    $db = (new Database())->connect();
    
    // Read migration SQL file
    $sqlFile = __DIR__ . '/../payroll_tables.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: {$sqlFile}");
    }
    
    echo "Reading migration file: {$sqlFile}\n";
    echo "========================================\n\n";
    
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
        
        // Extract table/operation name for display
        $statementType = '';
        if (preg_match('/CREATE TABLE/i', $statement)) {
            preg_match('/`?(\w+)`?/i', $statement, $matches);
            $statementType = $matches[1] ?? 'table';
            echo "Creating table: {$statementType}\n";
        } elseif (preg_match('/INSERT/i', $statement)) {
            $statementType = 'Inserting default salary data';
            echo "Inserting default salary data...\n";
        }
        
        try {
            $db->exec($statement);
            $successCount++;
            echo "  ✓ Success\n";
        } catch (PDOException $e) {
            // Check if error is "table already exists" - that's okay
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $skippedCount++;
                echo "  ℹ Already exists (skipped)\n";
            } else {
                $errorCount++;
                echo "  ✗ Error: " . $e->getMessage() . "\n";
                echo "  Statement preview: " . substr($statement, 0, 100) . "...\n";
            }
        }
        echo "\n";
    }
    
    echo "========================================\n";
    echo "Migration Summary:\n";
    echo "  Success: {$successCount}\n";
    echo "  Skipped (already exists): {$skippedCount}\n";
    echo "  Errors: {$errorCount}\n";
    echo "========================================\n\n";
    
    if ($errorCount === 0) {
        echo "✅ Migration completed successfully!\n\n";
        echo "Created tables:\n";
        echo "  - employee_salaries (stores employee salary information)\n";
        echo "  - payruns (stores payroll run periods)\n";
        echo "  - payroll_records (stores individual employee payroll records)\n\n";
        echo "Default salary data has been inserted for existing employees.\n";
        echo "\nYou can now use the payroll management system.\n";
    } else {
        echo "⚠️ Migration completed with errors. Please review above.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
echo "</body></html>";
