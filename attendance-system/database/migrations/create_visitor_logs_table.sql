-- ============================================
-- Migration: Create visitor_logs table
-- Date: 2025-12-XX
-- Description: Add dedicated table for visitor attendance logging
-- ============================================

-- Create visitor_logs table
CREATE TABLE IF NOT EXISTS `visitor_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `resident_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `birthdate` date DEFAULT NULL,
  `address` text NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `is_resident` tinyint(1) NOT NULL DEFAULT 0,
  `had_booking` tinyint(1) DEFAULT 0,
  `booking_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_resident_id` (`resident_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_is_resident` (`is_resident`),
  KEY `idx_purpose` (`purpose`),
  CONSTRAINT `fk_visitor_logs_resident` 
    FOREIGN KEY (`resident_id`) 
    REFERENCES `residents` (`resident_id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add comment to table
ALTER TABLE `visitor_logs` COMMENT = 'Visitor attendance and logging records';
