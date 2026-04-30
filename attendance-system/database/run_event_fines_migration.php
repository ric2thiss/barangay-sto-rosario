<?php
/**
 * One-time: create event_fines table (idempotent).
 * Run from browser while logged in as admin, or: php database/run_event_fines_migration.php
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

$pdo = (new Database())->connect();

$sql = <<<SQL
CREATE TABLE IF NOT EXISTS `event_fines` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `activity_id` INT NOT NULL,
  `fine_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event_fines_activity` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

try {
    $pdo->exec($sql);
    $msg = "OK: event_fines table is ready.\n";
} catch (Throwable $e) {
    http_response_code(500);
    $msg = 'Error: ' . $e->getMessage() . "\n";
}

echo $msg;
