-- Migration: Create attendance_windows table
-- Description: Stores customizable attendance time windows
-- Date: 2025-01-XX

CREATE TABLE IF NOT EXISTS `attendance_windows` (
  `window_id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(50) NOT NULL COMMENT 'Window label (e.g., morning_in, morning_out)',
  `start_time` time NOT NULL COMMENT 'Start time of the window',
  `end_time` time NOT NULL COMMENT 'End time of the window',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`window_id`),
  UNIQUE KEY `unique_label` (`label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default attendance windows
INSERT INTO `attendance_windows` (`label`, `start_time`, `end_time`) VALUES
('morning_in', '06:00:00', '11:59:00'),
('morning_out', '12:00:00', '12:59:00'),
('afternoon_in', '13:00:00', '15:59:00'),
('afternoon_out', '16:00:00', '18:30:00')
ON DUPLICATE KEY UPDATE 
  `start_time` = VALUES(`start_time`),
  `end_time` = VALUES(`end_time`);
