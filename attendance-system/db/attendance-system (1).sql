-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 12, 2026 at 04:41 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `source` enum('LGUMS','LOCAL') NOT NULL DEFAULT 'LOCAL',
  `external_id` varchar(64) DEFAULT NULL COMMENT 'schedule_events.id when source=LGUMS',
  `activity_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id`, `name`, `description`, `source`, `external_id`, `activity_date`, `created_at`, `updated_at`) VALUES
(1, 'Emergency Meeting', NULL, 'LOCAL', NULL, '2026-04-06', '2026-04-06 08:29:05', '2026-04-06 08:29:05'),
(2, 'Lahi', NULL, 'LOCAL', NULL, '2026-04-08', '2026-04-06 08:38:41', '2026-04-06 08:38:41');

-- --------------------------------------------------------

--
-- Table structure for table `activity_types`
--

CREATE TABLE `activity_types` (
  `activity_types_id` int(11) NOT NULL,
  `activity_name` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `archive_employees`
--

CREATE TABLE `archive_employees` (
  `employee_id` varchar(50) NOT NULL,
  `resident_id` int(11) NOT NULL COMMENT 'References sto_rosario.residents.id',
  `position_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `hired_date` date NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `archived_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `window` varchar(255) NOT NULL,
  `activity_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendances`
--

INSERT INTO `attendances` (`id`, `employee_id`, `timestamp`, `created_at`, `updated_at`, `window`, `activity_id`) VALUES
(1, '9', '2026-04-06 08:24:06', '2026-04-06 08:24:06', '2026-04-06 08:24:06', 'afternoon_out', NULL),
(2, '7', '2026-04-06 08:29:21', '2026-04-06 08:29:21', '2026-04-06 08:29:21', 'afternoon_out', NULL),
(3, '8', '2026-04-06 08:37:42', '2026-04-06 08:37:42', '2026-04-06 08:37:42', 'afternoon_out', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `attendance_windows`
--

CREATE TABLE `attendance_windows` (
  `window_id` int(11) NOT NULL,
  `label` varchar(50) NOT NULL COMMENT 'Window label (e.g., morning_in, morning_out)',
  `start_time` time NOT NULL COMMENT 'Start time of the window',
  `end_time` time NOT NULL COMMENT 'End time of the window',
  `late_grace_minutes` int(10) UNSIGNED DEFAULT NULL COMMENT 'Late threshold minutes after start for clock-in; NULL uses global setting',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_windows`
--

INSERT INTO `attendance_windows` (`window_id`, `label`, `start_time`, `end_time`, `late_grace_minutes`, `created_at`, `updated_at`) VALUES
(1, 'afternoon_out', '16:00:00', '19:00:00', NULL, '2026-04-06 16:23:22', '2026-04-06 16:23:22');

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
(1, '9', 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48RmlkPjxCeXRlcz5SazFTQUNBeU1BQUJ1QUF6L3Y4QUFBRmxBWWdBeFFERkFRQUFBRlpFZ0hZQTNtVmpnTnNBSEVGZmdJSUF2SGhiZ0UwQXlhcGFnTkFBNFNsWlFEQUFwSkJZUVFRQlFZVldRT0VCQ0NkVlFKVUFwWVZVZ1EwQXVwWlVnRVlBcFRKVFFKY0E2bmhTUU9JQlFYOVJRSDBBYW85TWdPNEJJSHhNUU9jQWVwRk1nTW9CTDNOTVFMUUFqb3RMZ080QkZIcExRRjBBMDBoSWdPRUJNSHBJUUhRQWRJZElRRFFCTWpOSFFJUUFGSzVGZ0hRQVZnWkZRTnNBZTR4RmdEWUE3Nk5GUUtZQlJueEZRUklBT1paRWdLWUE2SGxFZ05jQld5SkVnSDBBRkt0RVFGWUJEMDlDZ0hRQkttZEJRS1lBSWs5QmdHTUF0aTFBZ0o4QTdTUkFRUVVCR3loQVFGZ0JXMFpBUUM0QWRvTS9RUElBZ3pzL1FRZ0Ewb2cvUUVjQkJLdy9RRFFCRTR3L1FDTUE1eTgvUUg4QUxhaytRR01CWFVnK1FFWUJSNXMrUUxrQld5SStnUmdBclpNOVFSZ0JJSXM5UU9zQktpYzlRSzBCTmhzOVFSOEF3MEk4UUw0QkxCYzhnTGNCUm9NOFFMNEJTMzg4UU9zQlYzMDhRUDhCSjRNOFFRRUJMNGc3UUR3QlN6ODdnTHdCUVg4N2dFQUJWNk03UUg0QmJWZzZRSWdBSmFVNmdJSUFWcDQ2UVE4QXNaZzZRUlFBN1lvNkFBQT08L0J5dGVzPjxGb3JtYXQ+MTc2OTQ3MzwvRm9ybWF0PjxWZXJzaW9uPjEuMC4wPC9WZXJzaW9uPjwvRmlkPg==', '2026-04-06 08:21:26', '2026-04-06 16:21:26'),
(2, '7', 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48RmlkPjxCeXRlcz5SazFTQUNBeU1BQUJ1QUF6L3Y4QUFBRmxBWWdBeFFERkFRQUFBRlpFUUpNQWYySmFRSUlBcFFsYVFIRUJMSkpYUUZZQTAzOVhnTjBBclpsV2dGY0FWbXBXUUowQVRWNVZRR0VCVGpOVlFONEEwajlVUU9BQkJUNVRRSmNBbjJWU1FFSUEyWUZTUUlRQXZIRlJRUVFCUVlWUmdHY0FxSEJSUUlNQVJtTlFRTnNCV0M5UWdFY0JYVEZRUU9jQWIwaFBnR0VCUTVaUFFQVUFyRU5QZ0ZNQWd3OU9nSlVBeVhGT2dNc0FUYU5OUUlRQmFUcE1nSFlBVmdaTWdFWUFWd3RMZ1BNQklEOUpRRFVCR3k1SWdSVUF4NDlIUVJNQTRTVkZRT3NCSVpsRVFGQUJWNXBFZ1FnQTM0VkRnSDBCWFR4RFFDOEJCWWxEUUNVQXBISkNRU0FBdEpwQlFKQUE0WUZCUVE0Qk1EZEJnQzRBOVlGQVFESUE3SDQvUURvQktUTStnRVlCUnk4OVFQZ0JBakU4UVFJQTVKQThRQ29Ba1hFN2dDQUFuM1E3Z1JrQTdJTTdnUElBN1pBN2dNd0JMWjQ3Z1E4QTF6RTdRUjhBeFRzNVFLa0JiVWc1Z1JrQTNqVTRnUWNBMFpFNFFQSUJTNEU0Z09zQllpczRnT3NCR1pZMmdESUJMWW8yUUIwQXVIVTJRUVVBOHpZMmdDZ0FqaFExUUN3QWwzZzFnQ2dBMjNzMVFNb0JTemsxQURZQlI0dzFBTkVCVmpVMUFBQT08L0J5dGVzPjxGb3JtYXQ+MTc2OTQ3MzwvRm9ybWF0PjxWZXJzaW9uPjEuMC4wPC9WZXJzaW9uPjwvRmlkPg==', '2026-04-06 08:28:40', '2026-04-06 16:28:40'),
(3, '8', 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48RmlkPjxCeXRlcz5SazFTQUNBeU1BQUJmQUF6L3Y4QUFBRmxBWWdBeFFERkFRQUFBRlk2Z1F3QStYOWtRRHdCRXk5Z2dIMEJYVVplUUQ4QlVUNWNRRFVBdmlCWlFEY0Ewb0ZaUU1RQVdsWlpnRFlBcEhsWlFGQUJCSTFaUUpnQkZadFlRRUVBMjROWFFId0FrSEJXZ05vQk5nNVdnSlVBTjF4VlFMOEF0bFpUZ0pNQTNwSlNRSUlBN0pCUlFGRUFSMnRRZ040QTdqNVBRUFVCQ0M5UFFKa0FzWEZPUUhnQXZIcE9RT0lBdlVoTlFOSUJDRWhNUUtzQTU2QkxRTnNBL0VCTFFRY0FpcDVMZ1A4QThqWkxnUlVBdHBOSGdOY0FuazFHZ0xNQXZLNUZnRUlCYVV0RmdNd0FxcUpGUU1zQlVMQkVnTlFCVGdKRWdSZ0FqME05Z05JQlowMDlnSElBSlFZOGdRMEJBSDA4UUN3QldFYzhRTGtBdXFVOFFSTUJOeGs3UVJVQkRIazRBUklCQkRFMUFOWUJibEExQVE4QlV3MDBBUklBaVVReEFNMEJaRTh4QVNnQXhwWXdBTHdCYVUwd0FSOEFxNTR3QU9ZQmJRWXVBUlVCUVdzdEFDWUFkWEVzQVNnQXZrRXFBSVFCY1VzcUFKVUJjYUlxQVA4QmFnZ3BBQUE9PC9CeXRlcz48Rm9ybWF0PjE3Njk0NzM8L0Zvcm1hdD48VmVyc2lvbj4xLjAuMDwvVmVyc2lvbj48L0ZpZD4=', '2026-04-06 08:37:09', '2026-04-06 16:37:09');

-- --------------------------------------------------------

--
-- Table structure for table `event_fines`
--

CREATE TABLE `event_fines` (
  `id` int(10) UNSIGNED NOT NULL,
  `activity_id` int(11) NOT NULL,
  `fine_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(255) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(512) DEFAULT NULL,
  `auth_source` varchar(64) DEFAULT NULL,
  `role` varchar(128) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`id`, `username`, `success`, `ip_address`, `user_agent`, `auth_source`, `role`, `message`, `created_at`) VALUES
(1, 'Admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'profiling_admin', 'Administrator', NULL, '2026-04-06 07:28:31'),
(2, 'Admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'profiling_admin', 'Administrator', NULL, '2026-04-06 16:39:48'),
(3, 'Admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'profiling_admin', 'Administrator', NULL, '2026-04-06 17:21:46'),
(4, 'Admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'profiling_admin', 'Administrator', NULL, '2026-04-12 14:41:34');

-- --------------------------------------------------------

--
-- Table structure for table `resident_fingerprints`
--

CREATE TABLE `resident_fingerprints` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL COMMENT 'References sto_rosario.residents.id',
  `template` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
--

CREATE TABLE `visitor_logs` (
  `id` bigint(20) NOT NULL,
  `resident_id` int(11) DEFAULT NULL COMMENT 'References sto_rosario.residents.id',
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
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Visitor attendance and logging records';

--
-- Dumping data for table `visitor_logs`
--

INSERT INTO `visitor_logs` (`id`, `resident_id`, `first_name`, `middle_name`, `last_name`, `birthdate`, `address`, `purpose`, `is_resident`, `had_booking`, `booking_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1591, 'Clent Jhonaris', 'Mariscal', 'Jumon', '2003-11-11', 'Purok 6, Brgy. Santo Rosario, Magallanes, Agusan Del Norte', 'Barangay Clearance', 1, 0, NULL, '2026-04-06 17:23:32', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_date` (`activity_date`),
  ADD KEY `idx_source_external` (`source`,`external_id`);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attendances_activity_id` (`activity_id`);

--
-- Indexes for table `attendance_windows`
--
ALTER TABLE `attendance_windows`
  ADD PRIMARY KEY (`window_id`),
  ADD UNIQUE KEY `unique_label` (`label`);

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
-- Indexes for table `event_fines`
--
ALTER TABLE `event_fines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_event_fines_activity` (`activity_id`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_login_logs_created` (`created_at`),
  ADD KEY `idx_login_logs_username` (`username`);

--
-- Indexes for table `resident_fingerprints`
--
ALTER TABLE `resident_fingerprints`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resident_id` (`resident_id`);

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
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `activity_types`
--
ALTER TABLE `activity_types`
  MODIFY `activity_types_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendances`
--
ALTER TABLE `attendances`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `attendance_windows`
--
ALTER TABLE `attendance_windows`
  MODIFY `window_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employee_activity`
--
ALTER TABLE `employee_activity`
  MODIFY `employee_activity_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_fingerprints`
--
ALTER TABLE `employee_fingerprints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `event_fines`
--
ALTER TABLE `event_fines`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `resident_fingerprints`
--
ALTER TABLE `resident_fingerprints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendances`
--
ALTER TABLE `attendances`
  ADD CONSTRAINT `fk_attendances_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE SET NULL;

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
