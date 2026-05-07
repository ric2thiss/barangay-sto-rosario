-- Create settings table for system configuration
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `type` enum('string','boolean','integer','json') DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default settings (ignore if already exist)
INSERT IGNORE INTO `settings` (`key`, `value`, `type`, `description`) VALUES
('app_name', 'Attendance System', 'string', 'Application name'),
('maintenance_mode', '0', 'boolean', 'Enable maintenance mode (1 = enabled, 0 = disabled)'),
('maintenance_message', 'The system is currently under maintenance. Please try again later.', 'string', 'Message shown during maintenance mode'),
('timezone', 'Asia/Manila', 'string', 'Default timezone'),
('data_retention_days', '365', 'integer', 'Number of days to retain attendance logs before archival'),
('allow_registration', '1', 'boolean', 'Allow new user registration (1 = enabled, 0 = disabled)');
