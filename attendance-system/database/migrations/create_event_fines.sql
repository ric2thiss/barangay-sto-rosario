-- Per-event fine amount for missed attendance (optional FK if activities table uses InnoDB).
CREATE TABLE IF NOT EXISTS `event_fines` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `activity_id` INT NOT NULL,
  `fine_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event_fines_activity` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: add FK manually when activities.id is compatible:
-- ALTER TABLE `event_fines` ADD CONSTRAINT `fk_event_fines_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE;
