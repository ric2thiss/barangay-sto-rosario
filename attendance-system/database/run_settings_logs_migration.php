<?php
/**
 * Run: php database/run_settings_logs_migration.php
 * Or open in browser (one-time).
 */
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: text/html; charset=utf-8');

$db = (new Database())->connect();
$sqlFile = __DIR__ . '/migrations/settings_logs_soft_delete.sql';
$raw = file_get_contents($sqlFile);
$statements = array_filter(array_map('trim', preg_split('/;\s*\R/', $raw)));

foreach ($statements as $sql) {
    if ($sql === '' || str_starts_with($sql, '--')) {
        continue;
    }
    try {
        $db->exec($sql);
        echo "<p>OK: " . htmlspecialchars(substr($sql, 0, 80)) . "…</p>\n";
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate column')
            || str_contains($e->getMessage(), 'already exists')) {
            echo "<p>Skip (exists): " . htmlspecialchars(substr($sql, 0, 60)) . "…</p>\n";
            continue;
        }
        echo "<p style='color:red'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
}

echo "<p><strong>Done.</strong></p>";
