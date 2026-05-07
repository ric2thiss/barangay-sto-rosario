-- Soft-delete columns for log retention (30-day purge handled in PHP on each request)
ALTER TABLE `attendances`
  ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL;

ALTER TABLE `visitor_logs`
  ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `login_logs` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `settings` (`key`, `value`, `type`, `description`) VALUES
('user_access_control', '{"attendance_admins":true,"profiling_admin":true,"barangay_officials":true,"residents":true}', 'json', 'Which account categories may log in (checked at login only)'),
('apache_access_log_path', '', 'string', 'Optional full path to Apache access.log (empty = auto-detect XAMPP / common paths)');
