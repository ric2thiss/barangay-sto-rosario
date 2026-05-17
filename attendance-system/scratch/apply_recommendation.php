<?php
require_once __DIR__ . '/../bootstrap.php';
$pdo = (new Database())->connect();

function addColumnIfNotExists($pdo, $table, $column, $definition) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($stmt->rowCount() === 0) {
            echo "Adding $column to $table...\n";
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            return true;
        } else {
            echo "$column already exists in $table.\n";
            return false;
        }
    } catch (Exception $e) {
        echo "Error on $table ($column): " . $e->getMessage() . "\n";
        return false;
    }
}

// 1. attendances
addColumnIfNotExists($pdo, 'attendances', 'deleted_at', 'DATETIME NULL DEFAULT NULL');

// 2. visitor_logs (should already exist but safe to check)
addColumnIfNotExists($pdo, 'visitor_logs', 'deleted_at', 'DATETIME NULL DEFAULT NULL');

// 3. activities
addColumnIfNotExists($pdo, 'activities', 'deleted_at', 'DATETIME NULL DEFAULT NULL');

// 4. attendance_windows
addColumnIfNotExists($pdo, 'attendance_windows', 'deleted_at', 'DATETIME NULL DEFAULT NULL');

// 5. login_logs table
echo "Checking login_logs table...\n";
$pdo->exec("CREATE TABLE IF NOT EXISTS `login_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(512) DEFAULT NULL,
  `auth_source` varchar(64) DEFAULT NULL,
  `role` varchar(128) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_login_logs_created` (`created_at`),
  KEY `idx_login_logs_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// 6. settings
echo "Updating settings...\n";
$settingsToInsert = [
    ['user_access_control', '{"attendance_admins":true,"profiling_admin":true,"barangay_officials":true,"residents":true}', 'json', 'Which account categories may log in (checked at login only)'],
    ['apache_access_log_path', '', 'string', 'Optional full path to Apache access.log (empty = auto-detect XAMPP / common paths)']
];

foreach ($settingsToInsert as $s) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE `key` = ?");
    $stmt->execute([$s[0]]);
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`, `type`, `description`) VALUES (?, ?, ?, ?)");
        $stmt->execute($s);
        echo "Inserted setting: {$s[0]}\n";
    } else {
        echo "Setting already exists: {$s[0]}\n";
    }
}

echo "Migration completed.\n";
