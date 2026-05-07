<?php
require_once __DIR__ . '/../../bootstrap.php';

$db = (new Database())->connect();

echo "Settings Table Verification\n";
echo str_repeat("=", 50) . "\n\n";

// Check table structure
echo "Table Structure:\n";
$stmt = $db->query("DESCRIBE settings");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($cols as $col) {
    echo sprintf("  - %s (%s)%s\n", $col['Field'], $col['Type'], $col['Null'] === 'YES' ? ' NULL' : ' NOT NULL');
}

// Check settings count
echo "\nSettings Count:\n";
$count = $db->query("SELECT COUNT(*) as cnt FROM settings")->fetch(PDO::FETCH_ASSOC);
echo "  Total: " . $count['cnt'] . "\n";

// List all settings
echo "\nDefault Settings:\n";
$stmt = $db->query("SELECT `key`, `value`, `type`, `description` FROM settings ORDER BY `key`");
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($settings as $s) {
    echo sprintf("  %s: %s (%s)\n", $s['key'], $s['value'], $s['type']);
}

// Check foreign key
echo "\nForeign Key Constraint:\n";
$stmt = $db->query("SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'settings' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL");
$fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($fks) > 0) {
    foreach($fks as $fk) {
        echo sprintf("  ✓ %s.%s -> %s.%s\n", 
            $fk['TABLE_NAME'], 
            $fk['COLUMN_NAME'], 
            $fk['REFERENCED_TABLE_NAME'], 
            $fk['REFERENCED_COLUMN_NAME']
        );
    }
} else {
    echo "  ⚠ No foreign key constraints found\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ Verification complete!\n";
