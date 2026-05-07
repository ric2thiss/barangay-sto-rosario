-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 20, 2026 at 02:40 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12
--
-- Database: `attendance-system`
-- Version: 1.0.1
-- 
-- ⚠️ IMPORTANT: This is a refactored version following microservice architecture
-- - Removed profiling-related tables (residents, addresses, etc.)
-- - employees.resident_id now references profiling-system.residents.id (cross-database)
-- - No foreign key constraints on resident_id (cross-database FKs not supported)
-- - Application code must enforce referential integrity
-- - Renamed `fingerprints` table to `employee_fingerprints` for clarity

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `attendance-system`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_types`
--

CREATE TABLE `activity_types` (
  `activity_types_id` int(11) NOT NULL,
  `activity_name` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_types`
--

INSERT INTO `activity_types` (`activity_types_id`, `activity_name`, `created_at`) VALUES
(1, 'Business Trip', '2025-11-02 07:07:44');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `role` enum('administrator','manager','staff') DEFAULT 'staff',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `full_name`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@gmail.com', '$2y$10$xYqeDye1PKDDkd6QsZkp9ujq2ujgEOrRtQK6ocFYWftsmi36K0nvC', 'System Administrator', 'administrator', 1, '2026-01-19 08:57:01', '2025-12-13 23:36:27', '2026-01-19 08:57:01');

-- --------------------------------------------------------

--
-- Table structure for table `archive_employees`
-- ⚠️ resident_id references profiling-system.residents.id (cross-database, no FK constraint)
--

CREATE TABLE `archive_employees` (
  `employee_id` varchar(50) NOT NULL,
  `resident_id` int(11) NOT NULL COMMENT 'References profiling-system.residents.id',
  `position_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `hired_date` date NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `archived_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archive_employees`
--

INSERT INTO `archive_employees` (`employee_id`, `resident_id`, `position_id`, `department_id`, `hired_date`, `created_at`, `updated_at`, `archived_at`) VALUES
('20201188', 3, 1, 1, '2025-10-14', '2026-01-13 08:40:38', '2026-01-13 08:40:38', '2026-01-13 08:40:38'),
('20201197', 2, 1, NULL, '2026-01-14', '2026-01-14 07:33:01', '2026-01-14 07:33:01', '2026-01-14 07:33:01'),
('2021', 2, 2, NULL, '2025-10-11', '2026-01-14 07:12:54', '2026-01-14 07:12:54', '2026-01-14 07:12:54'),
('20211', 10, 1, NULL, '2026-01-13', '2026-01-13 08:41:57', '2026-01-13 08:41:57', '2026-01-13 08:41:57'),
('2022', 10, 1, NULL, '2026-01-14', '2026-01-14 06:50:38', '2026-01-14 06:50:38', '2026-01-14 06:50:38');

-- --------------------------------------------------------

--
-- Table structure for table `attendances`
--

CREATE TABLE `attendances` (
  `id` bigint(20) NOT NULL,
  `employee_id` varchar(255) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `window` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendances`
--

INSERT INTO `attendances` (`id`, `employee_id`, `timestamp`, `created_at`, `updated_at`, `window`) VALUES
(62, '20201197', '2026-01-14 23:46:44', '2026-01-14 23:46:44', '2026-01-14 23:46:44', 'morning_in'),
(63, '20201197', '2026-01-15 03:01:13', '2026-01-15 03:01:13', '2026-01-15 03:01:13', 'morning_out'),
(64, '20201197', '2026-01-16 04:51:46', '2026-01-16 04:51:46', '2026-01-16 04:51:46', 'morning_out'),
(65, '20201197', '2026-01-16 05:41:35', '2026-01-16 05:41:35', '2026-01-16 05:41:35', 'afternoon_in'),
(66, '20201197', '2026-01-16 09:18:49', '2026-01-16 09:18:49', '2026-01-16 09:18:49', 'afternoon_out'),
(67, '20201198', '2026-01-16 09:39:23', '2026-01-16 09:39:23', '2026-01-16 09:39:23', 'afternoon_out'),
(68, '20201197', '2026-01-19 08:59:56', '2026-01-19 08:59:56', '2026-01-19 08:59:56', 'afternoon_out');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_windows`
--

CREATE TABLE `attendance_windows` (
  `window_id` int(11) NOT NULL,
  `label` varchar(50) NOT NULL COMMENT 'Window label (e.g., morning_in, morning_out)',
  `start_time` time NOT NULL COMMENT 'Start time of the window',
  `end_time` time NOT NULL COMMENT 'End time of the window',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_windows`
--

INSERT INTO `attendance_windows` (`window_id`, `label`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES
(1, 'morning_in', '06:00:00', '11:00:00', '2026-01-13 21:45:52', '2026-01-14 11:30:12'),
(2, 'morning_out', '11:00:00', '12:59:00', '2026-01-13 21:45:52', '2026-01-14 11:30:20'),
(3, 'afternoon_in', '13:00:00', '15:59:00', '2026-01-13 21:45:52', '2026-01-14 11:30:27'),
(4, 'afternoon_out', '16:00:00', '19:00:00', '2026-01-13 21:45:52', '2026-01-14 11:30:34');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`, `created_at`) VALUES
(1, 'Finance', '2025-11-02 22:32:40'),
(2, 'Peace and Order', '2025-11-02 22:32:53');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
-- ⚠️ resident_id references profiling-system.residents.id (cross-database, no FK constraint)
--

CREATE TABLE `employees` (
  `employee_id` varchar(50) NOT NULL,
  `resident_id` int(11) NOT NULL COMMENT 'References profiling-system.residents.id',
  `position_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `hired_date` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `resident_id`, `position_id`, `department_id`, `hired_date`, `created_at`, `updated_at`) VALUES
('20201197', 2, 1, 1, '2026-01-14', '2026-01-14 07:33:43', '2026-01-14 11:18:02'),
('20201198', 13, 1, 1, '2026-01-16', '2026-01-16 17:38:57', '2026-01-16 17:38:57');

-- --------------------------------------------------------

--
-- Table structure for table `employee_activity`
--

CREATE TABLE `employee_activity` (
  `employee_activity_id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `activity_types_id` int(11) NOT NULL,
  `start` datetime DEFAULT NULL,
  `end` datetime DEFAULT NULL,
  `created_by` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_fingerprints`
-- Stores biometric fingerprint templates for employees
--

CREATE TABLE `employee_fingerprints` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(256) NOT NULL,
  `template` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_fingerprints`
--

INSERT INTO `employee_fingerprints` (`id`, `employee_id`, `template`, `created_at`, `updated_at`) VALUES
(7, '20201197', 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48RmlkPjxCeXRlcz5SazFTQUNBeU1BQUJ1QUF6L3Y4QUFBRmxBWWdBeFFERkFRQUFBRlpFUU40QkFvbGdnT2NBUXFaZVFMa0F6b1ZiUU1FQUdsaGJnTFlCSHloYlFIY0FteGxhUUxvQWFLZFpnSHdBMFNkWmdIWUFkQmRaUUcwQXk0dFhnSWtBRW1sWGdLb0FySVZXUU4wQWk1aFdRSThBbkh4VFFHSUJMYUpTUUxBQU9WNVJnSjRCQzNaUmdDRUFlMzlRUU1FQUo2NVFnUHNBYUVwUFFNVUF0b3RQUUc4QU1CRlBnRzRBMkMxTmdQa0JBb2RNUVBJQXhZdExRTXdCT254TFFJNEFRd1ZLZ09JQTBqTktnRElBYngxS2dKOEE2bjFLUUpJQk5sNUtRTmNBcTQ5SmdIb0ErQ2xJZ0hjQkVEaElRS29BUnJCSFFCc0FuQ0pIUUYwQko2UkhRS2tBb290RmdEVUJBRE5GUUR3QVhucEZnSlVBRGdkRWdOSUF2b2xFZ1B3QktZZEVnUGdCUW9SRWdFRUFaSDFEZ0k4QWxvRkRRRDhBdlg5RFFFWUE2ak5EZ0RjQStKQkRRSklCU0JsQ1FSVUEwNUpCUU0wQlRpZEJRUFVCS1MxQmdKSUJMQmRBUUI4QXNZVkFRSGNCTHdZL2dCMEFpaUkvUU9nQXZUUS9RRkFCREpJK1FDOEFyU1E5Z0swQk5IUTlnUTRBbTVrOVFLTUJNQ1U5Z0NNQXNvTThRQ29BdUNnOFFDa0F6aWs4Z1BnQWUwazhnSU1CRElVN0FBQT08L0J5dGVzPjxGb3JtYXQ+MTc2OTQ3MzwvRm9ybWF0PjxWZXJzaW9uPjEuMC4wPC9WZXJzaW9uPjwvRmlkPg==', '2026-01-13 23:33:43', '2026-01-14 07:33:43'),
(8, '20201198', 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48RmlkPjxCeXRlcz5SazFTQUNBeU1BQUJ1QUF6L3Y4QUFBRmxBWWdBeFFERkFRQUFBRlpFZ0xBQXg0RmtRR0VBbVNGaGdKa0FzbjlkZ0lNQTZDZGJRRjBCQUkxWmdHZ0JGakpZZ0x3QkZYOVdRUElBd1lsV1FGVUFTeGxWUUdjQVloZFVRS2tCSEhoVWdLUUFLZ1pUZ0tzQUtGdFNRQzhBd0NWU1FJSUJCUzFTZ05JQXBvbFJRUEVBOUMxUlFQTUJLaWRSUUZ3QWRIcFFnSVlBZ1JGUWdORUE2SU5RUVF3QTU0eE9RRzRCQUl4T2dId0JIelpPZ0NNQWZueE5RQm9BbmlOTmdQa0FpVUJNUU93QlNIeE1nQ3dBbnlCTVFLUUFxNEpNUUswQk1udE1RTFFBTUxCTGdRd0F6SXhMUUM0QVduaEtnSVlCRUNWSlFQd0FrWjVJZ1BVQU8wbEhnSFlCVkUxRlFTTUFnNkZFUVFjQVdrMUVRUVFBZXFCRVFQNEFzWXhFZ0tNQW5uWkRRUVVBWjZGQmdIb0JTZ3hCUVJrQVQwTkJnU0FBZUVKQlFQOEJLbjVBZ1JJQWUwRkFRS01CUTNWQWdESUFTU0EvZ1FjQWY1NC9RUlFBMDVJL2dQOEFOcVkrZ1NNQVhKcytnUVFCTW44K1FRSUJDWWc5UUdzQlZMSTlRU01BYWowOWdQd0FVVXc4UVFnQTlDODhRUThBd0RnN1FJd0JLaUE3UVEwQVNGRTdnTzBCSVlJN1FINEJVQTA3UVBnQVdLVTZRUE1CQUlJNkFBQT08L0J5dGVzPjxGb3JtYXQ+MTc2OTQ3MzwvRm9ybWF0PjxWZXJzaW9uPjEuMC4wPC9WZXJzaW9uPjwvRmlkPg==', '2026-01-16 09:38:57', '2026-01-16 17:38:57');

-- --------------------------------------------------------

--
-- --------------------------------------------------------

--
-- Table structure for table `position`
--

CREATE TABLE `position` (
  `position_id` int(11) NOT NULL,
  `position_name` varchar(50) NOT NULL,
  `created_at` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `position`
--

INSERT INTO `position` (`position_id`, `position_name`, `created_at`) VALUES
(1, 'Kagawad', '2025-11-05'),
(2, 'Chairman', '2025-11-05');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `type` enum('string','boolean','integer','json') DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `type`, `description`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'app_name', 'Attendance System', 'string', 'Application name', NULL, '2026-01-15 03:59:38', NULL),
(2, 'maintenance_mode', '0', 'boolean', 'Enable maintenance mode (1 = enabled, 0 = disabled)', 1, '2026-01-15 03:59:38', '2026-01-16 04:53:17'),
(3, 'maintenance_message', 'The system is currently under maintenance. Please try again later.', 'string', 'Message shown during maintenance mode', 1, '2026-01-15 03:59:38', '2026-01-15 14:39:40'),
(4, 'timezone', 'Asia/Manila', 'string', 'Default timezone', NULL, '2026-01-15 03:59:38', NULL),
(5, 'data_retention_days', '365', 'integer', 'Number of days to retain attendance logs before archival', NULL, '2026-01-15 03:59:38', NULL),
(6, 'allow_registration', '1', 'boolean', 'Allow new user registration (1 = enabled, 0 = disabled)', NULL, '2026-01-15 03:59:38', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `verification_log`
--

CREATE TABLE `verification_log` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `device_id` varchar(255) NOT NULL,
  `ip_address` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `verification_tokens`
--

CREATE TABLE `verification_tokens` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visitor_logs`
-- ⚠️ resident_id references profiling-system.residents.id (cross-database, no FK constraint)
--

CREATE TABLE `visitor_logs` (
  `id` bigint(20) NOT NULL,
  `resident_id` int(11) DEFAULT NULL COMMENT 'References profiling-system.residents.id',
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `purpose` varchar(255) NOT NULL,
  `is_resident` tinyint(1) NOT NULL DEFAULT 0,
  `had_booking` tinyint(1) DEFAULT 0,
  `booking_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Visitor attendance and logging records';

--
-- Dumping data for table `visitor_logs`
--

INSERT INTO `visitor_logs` (`id`, `resident_id`, `first_name`, `middle_name`, `last_name`, `birthdate`, `address`, `purpose`, `is_resident`, `had_booking`, `booking_id`, `created_at`, `updated_at`) VALUES
(5, 2, 'Ric Charles', 'Lucar', 'Paquibot', NULL, 'Brgy. ampayon, a1, a2', 'Barangay Clearance', 1, 0, NULL, '2026-01-14 01:00:59', NULL),
(6, 2, 'Ric Charles', 'Lucar', 'Paquibot', NULL, 'Brgy. ampayon, a1, a2', 'Barangay Clearance', 1, 0, NULL, '2026-01-14 02:03:31', NULL),
(7, 2, 'Ric Charles', 'Lucar', 'Paquibot', NULL, 'Brgy. ampayon, a1, a2', 'Business Permit', 1, 0, NULL, '2026-01-14 13:50:35', NULL),
(8, 2, 'Ric Charles', 'Lucar', 'Paquibot', NULL, 'Brgy. ampayon, a1, a2', 'Cedula', 1, 0, NULL, '2026-01-16 15:48:34', NULL),
(9, 2, 'Ric Charles', 'Lucar', 'Paquibot', NULL, 'Brgy. ampayon, a1, a2', 'Barangay Clearance', 1, 0, NULL, '2026-01-19 09:03:04', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_types`
--
ALTER TABLE `activity_types`
  ADD PRIMARY KEY (`activity_types_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `archive_employees`
--
ALTER TABLE `archive_employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD KEY `archived_at` (`archived_at`),
  ADD KEY `resident_id` (`resident_id`),
  ADD KEY `position_id` (`position_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `attendances`
--
ALTER TABLE `attendances`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance_windows`
--
ALTER TABLE `attendance_windows`
  ADD PRIMARY KEY (`window_id`),
  ADD UNIQUE KEY `unique_label` (`label`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD KEY `resident_id` (`resident_id`),
  ADD KEY `position_id` (`position_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `employee_activity`
--
ALTER TABLE `employee_activity`
  ADD PRIMARY KEY (`employee_activity_id`),
  ADD KEY `employee_id` (`employee_id`,`activity_types_id`,`created_by`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `employee_current_activity` (`activity_types_id`);

--
-- Indexes for table `employee_fingerprints`
--
ALTER TABLE `employee_fingerprints`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `position`
--
ALTER TABLE `position`
  ADD PRIMARY KEY (`position_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `verification_log`
--
ALTER TABLE `verification_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `verification_tokens`
--
ALTER TABLE `verification_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `token` (`token`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `visitor_logs`
--
ALTER TABLE `visitor_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resident_id` (`resident_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_is_resident` (`is_resident`),
  ADD KEY `idx_purpose` (`purpose`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_types`
--
ALTER TABLE `activity_types`
  MODIFY `activity_types_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `attendances`
--
ALTER TABLE `attendances`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `attendance_windows`
--
ALTER TABLE `attendance_windows`
  MODIFY `window_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `employee_activity`
--
ALTER TABLE `employee_activity`
  MODIFY `employee_activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employee_fingerprints`
--
ALTER TABLE `employee_fingerprints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
--
-- AUTO_INCREMENT for table `position`
--
ALTER TABLE `position`
  MODIFY `position_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `verification_log`
--
ALTER TABLE `verification_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `verification_tokens`
--
ALTER TABLE `verification_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visitor_logs`
--
ALTER TABLE `visitor_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
-- Note: Foreign key constraints removed for cross-database references
--

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_2` FOREIGN KEY (`position_id`) REFERENCES `position` (`position_id`),
  ADD CONSTRAINT `employees_ibfk_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `employee_activity`
--
ALTER TABLE `employee_activity`
  ADD CONSTRAINT `employee_activity_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `employees` (`employee_id`),
  ADD CONSTRAINT `employee_activity_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`),
  ADD CONSTRAINT `employee_activity_ibfk_3` FOREIGN KEY (`activity_types_id`) REFERENCES `activity_types` (`activity_types_id`);

--
-- Constraints for table `settings`
--
ALTER TABLE `settings`
  ADD CONSTRAINT `settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
