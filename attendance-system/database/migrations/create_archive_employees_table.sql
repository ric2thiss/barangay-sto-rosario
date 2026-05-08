-- Migration: Create archive_employees table for audit trail
-- This table stores archived/deleted employee records
-- Run this SQL to create the archive_employees table

CREATE TABLE IF NOT EXISTS `archive_employees` (
  `employee_id` varchar(50) NOT NULL,
  `resident_id` int(50) NOT NULL,
  `position_id` int(11) NOT NULL,
  `department_id` int(11) NULL DEFAULT NULL,
  `hired_date` date NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `archived_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`employee_id`),
  KEY `archived_at` (`archived_at`),
  KEY `resident_id` (`resident_id`),
  KEY `position_id` (`position_id`),
  KEY `department_id` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
