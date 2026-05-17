<?php
/**
 * Adds attendance_windows.late_grace_minutes if missing.
 * CLI: php database/run_late_grace_migration.php
 * Browser: open while logged in as admin.
 */
$root = dirname(__DIR__);
require_once $root . '/bootstrap.php';

$cli = php_sapi_name() === 'cli';
if (!$cli) {
    require_once $root . '/auth/helpers.php';
    if (!isAuthenticated()) {
        http_response_code(401);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Unauthorized';
        exit;
    }
    header('Content-Type: text/plain; charset=utf-8');
}

try {
    $pdo = (new Database())->connect();
    $stmt = $pdo->query("SHOW COLUMNS FROM `attendance_windows` LIKE 'late_grace_minutes'");
    if ($stmt && $stmt->fetch()) {
        echo "OK: column late_grace_minutes already exists.\n";
        exit(0);
    }
    $sql = file_get_contents(__DIR__ . '/migrations/add_attendance_windows_late_grace.sql');
    if ($sql === false || trim($sql) === '') {
        echo "ERROR: could not read migration SQL.\n";
        exit(1);
    }
    $pdo->exec($sql);
    echo "OK: added attendance_windows.late_grace_minutes\n";
    exit(0);
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}
