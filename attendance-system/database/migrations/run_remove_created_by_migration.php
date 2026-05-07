<?php
/**
 * Migration Runner: Remove created_by column from activity_types table
 * Run this file via browser or CLI to execute the migration
 * 
 * Browser: http://localhost/attendance-system/database/migrations/run_remove_created_by_migration.php
 * CLI: php database/migrations/run_remove_created_by_migration.php
 */

require_once __DIR__ . '/../../bootstrap.php';

header("Content-Type: text/html; charset=utf-8");

echo "<!DOCTYPE html><html><head><title>Migration Runner</title></head><body>";
echo "<h1>Remove created_by from activity_types Migration</h1>";
echo "<pre>";

try {
    $db = (new Database())->connect();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    echo "Connected to database successfully.\n\n";
    
    // Step 1: Drop the foreign key constraint
    echo "Step 1: Dropping foreign key constraint...\n";
    try {
        $db->exec("ALTER TABLE `activity_types` DROP FOREIGN KEY `activity_types_ibfk_1`");
        echo "✓ Foreign key constraint dropped successfully.\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Unknown key') !== false || strpos($e->getMessage(), "doesn't exist") !== false) {
            echo "ℹ Foreign key constraint doesn't exist (skipping).\n\n";
        } else {
            throw $e;
        }
    }
    
    // Step 2: Drop the index on created_by
    echo "Step 2: Dropping index on created_by...\n";
    try {
        $db->exec("ALTER TABLE `activity_types` DROP INDEX `created_by`");
        echo "✓ Index dropped successfully.\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Unknown key') !== false || strpos($e->getMessage(), "doesn't exist") !== false) {
            echo "ℹ Index doesn't exist separately (skipping).\n\n";
        } else {
            throw $e;
        }
    }
    
    // Step 3: Remove the created_by column
    echo "Step 3: Removing created_by column...\n";
    try {
        $db->exec("ALTER TABLE `activity_types` DROP COLUMN `created_by`");
        echo "✓ Column removed successfully.\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Unknown column') !== false || strpos($e->getMessage(), "doesn't exist") !== false) {
            echo "ℹ Column doesn't exist (already removed).\n\n";
        } else {
            throw $e;
        }
    }
    
    echo "========================================\n";
    echo "✅ Migration completed successfully!\n";
    echo "========================================\n";
    echo "\nThe created_by column has been removed from activity_types table.\n";
    echo "You can now delete employees without foreign key constraint errors.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
echo "</body></html>";
