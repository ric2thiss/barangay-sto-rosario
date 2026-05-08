-- Activity/event tagging: local + LGUMS-imported activities, optional link on attendances.
-- Run once against the attendance-system database. Adjust if tables already exist.

CREATE TABLE IF NOT EXISTS `activities` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `source` enum('LGUMS','LOCAL') NOT NULL DEFAULT 'LOCAL',
  `external_id` varchar(64) DEFAULT NULL COMMENT 'schedule_events.id when source=LGUMS',
  `activity_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_activity_date` (`activity_date`),
  KEY `idx_source_external` (`source`,`external_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Link attendance rows to an activity (nullable). Skip if `activity_id` already exists.
ALTER TABLE `attendances`
  ADD COLUMN `activity_id` bigint(20) DEFAULT NULL AFTER `window`,
  ADD KEY `idx_attendances_activity_id` (`activity_id`);

-- Add FK only if not present (may fail if constraint name exists; drop manually if re-running).
ALTER TABLE `attendances`
  ADD CONSTRAINT `fk_attendances_activity`
  FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE SET NULL;

INSERT IGNORE INTO `settings` (`key`, `value`, `type`, `description`) VALUES
('active_attendance_activity_id', '', 'string', 'Activity ID applied when attendance API request omits activity_id (e.g. biometric client)');
