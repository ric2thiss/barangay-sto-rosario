-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 24, 2026 at 06:27 PM
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
-- Database: `pss_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(150) NOT NULL,
  `entity_type` varchar(80) DEFAULT NULL,
  `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 3, 'create', 'schedule_events', 5, 'Created event: adasd', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/145.0.0.0', '2026-02-22 16:50:03'),
(2, 3, 'create', 'schedule_events', 6, 'Created event: this is subject', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-22 17:15:52'),
(3, 3, 'create', 'schedule_events', 7, 'Created event: AAA', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-22 18:16:05'),
(4, 3, 'update', 'schedule_event', 7, 'Updated schedule event', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/145.0.0.0', '2026-02-22 18:51:55'),
(5, 3, 'update', 'schedule_event', 7, 'Updated schedule event', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/145.0.0.0', '2026-02-22 18:52:05'),
(6, 3, 'update', 'schedule_event', 7, 'Updated schedule event', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-22 20:51:59'),
(7, 3, 'update', 'schedule_event', 7, 'Updated schedule event', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-22 20:56:36'),
(8, 3, 'update', 'schedule_event', 7, 'Updated schedule event', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-22 20:57:33'),
(9, 3, 'create', 'schedule_events', 8, 'Created event: ssss', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-24 23:20:46'),
(10, 3, 'create', 'schedule_events', 9, 'Created event: test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-25 00:16:52'),
(11, 3, 'create', 'schedule_events', 10, 'Created event: sad', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-25 00:45:41'),
(12, 3, 'delete', 'schedule_events', 7, 'Deleted schedule event ID: 7', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/145.0.0.0', '2026-02-25 02:31:41'),
(13, 3, 'delete', 'schedule_events', 8, 'Deleted schedule event ID: 8', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/145.0.0.0', '2026-02-25 02:32:22'),
(14, 3, 'delete', 'schedule_events', 9, 'Deleted schedule event ID: 9', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-25 02:32:29'),
(15, 3, 'delete', 'schedule_events', 10, 'Deleted schedule event ID: 10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-25 02:33:04'),
(16, 3, 'delete', 'schedule_events', 5, 'Deleted schedule event ID: 5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-26 18:42:31'),
(17, 3, 'delete', 'schedule_events', 6, 'Deleted schedule event ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-26 20:30:08'),
(18, 3, 'create', 'schedule_events', 11, 'Created event: sdadasda', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-26 20:31:15'),
(19, 3, 'delete', 'schedule_events', 11, 'Deleted schedule event ID: 11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-26 21:03:32'),
(20, 3, 'create', 'schedule_events', 12, 'Created event: sadsa', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-02-26 21:03:45'),
(21, 3, 'create', 'schedule_events', 13, 'Created event: asdad', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-02 20:42:22'),
(22, 3, 'update', 'schedule_events', 13, 'Updated schedule event', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-02 23:21:04'),
(23, 3, 'update', 'schedule_events', 13, 'Updated schedule event', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/145.0.0.0', '2026-03-02 23:22:24'),
(24, 3, 'create', 'schedule_events', 14, 'Created event: pahina', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-02 23:31:06'),
(25, 3, 'create', 'schedule_events', 15, 'Created event: meeting', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-02 23:36:36'),
(29, 3, 'create', 'users', 4, 'Created account: staff', NULL, NULL, '2026-03-03 21:03:01'),
(30, 4, 'login', 'users', 4, 'Logged in: staff', NULL, NULL, '2026-03-03 21:03:34'),
(35, 3, 'update', 'schedule_events', 14, 'Updated schedule event', NULL, NULL, '2026-03-08 11:56:34'),
(37, 3, 'update', 'schedule_events', 15, 'Updated schedule event', NULL, NULL, '2026-03-08 21:32:01'),
(38, 3, 'update', 'schedule_events', 14, 'Updated schedule event', NULL, NULL, '2026-03-08 21:38:49'),
(42, 3, 'create', 'users', 5, 'Created account: admin2026', NULL, NULL, '2026-03-21 15:06:23'),
(43, 5, 'login', 'users', 5, 'Logged in: admin2026', NULL, NULL, '2026-03-21 15:06:43'),
(44, 5, 'update', 'users', 5, 'Updated profile picture', NULL, NULL, '2026-03-21 15:06:58'),
(45, 5, 'update', 'users', 5, 'Updated profile info', NULL, NULL, '2026-03-21 15:07:00'),
(46, 5, 'create', 'schedule_events', 16, 'Created event: Project presentation.', NULL, NULL, '2026-03-21 15:08:58'),
(47, 5, 'update', 'contacts', 3, 'Updated contact: Kate Bishop', NULL, NULL, '2026-03-21 15:16:11'),
(48, 5, 'update', 'contacts', 7, 'Updated contact: Jhon Doe', NULL, NULL, '2026-03-21 15:16:28'),
(49, 5, 'update', 'contacts', 6, 'Updated contact: Maria Makiling', NULL, NULL, '2026-03-21 15:16:43'),
(50, 5, 'create', 'contacts', 8, 'Created contact: Pretche Orquillas', NULL, NULL, '2026-03-21 15:17:45'),
(51, 5, 'create', 'users', 6, 'Created account: pretche1', NULL, NULL, '2026-03-21 15:23:19'),
(52, 5, 'update', 'barangay_officials', 4, 'Updated official: KATE BISHOP', NULL, NULL, '2026-03-21 15:45:52'),
(53, 5, 'update', 'barangay_officials', 4, 'Updated official: KATE BISHOP', NULL, NULL, '2026-03-21 15:47:10'),
(54, 6, 'login', 'users', 6, 'Logged in: pretche1', NULL, NULL, '2026-03-21 15:56:13'),
(55, 6, 'update', 'barangay_officials', 6, 'Updated official: PRETCHE ORQUILLAS', NULL, NULL, '2026-03-21 16:51:43'),
(56, 6, 'update', 'barangay_officials', 5, 'Updated official: JHON DOE', NULL, NULL, '2026-03-21 16:52:26'),
(57, 5, 'login', 'users', 5, 'Logged in: admin2026', NULL, NULL, '2026-03-21 17:00:22'),
(58, 6, 'login', 'users', 6, 'Logged in: pretche1', NULL, NULL, '2026-03-21 17:03:09'),
(59, 6, 'update', 'users', 6, 'Updated profile picture', NULL, NULL, '2026-03-21 17:03:24'),
(60, 6, 'update', 'users', 6, 'Updated profile picture', NULL, NULL, '2026-03-21 17:05:20'),
(61, 6, 'update', 'users', 6, 'Updated profile info', NULL, NULL, '2026-03-21 17:05:24'),
(62, 6, 'create', 'schedule_events', 17, 'Created event: Alumni', NULL, NULL, '2026-03-21 18:53:26'),
(63, 6, 'login', 'users', 6, 'Logged in: pretche1', NULL, NULL, '2026-03-22 22:57:17'),
(64, 6, 'create', 'contacts', 9, 'Created contact: Carrie Naig', NULL, NULL, '2026-03-22 22:58:25'),
(65, 6, 'create', 'schedule_events', 18, 'Created event: Project Testing', NULL, NULL, '2026-03-22 23:12:00'),
(66, 6, 'login', 'users', 6, 'Logged in: pretche1', NULL, NULL, '2026-03-23 07:45:34'),
(67, 6, 'update', 'schedule_events', 18, 'Updated schedule event', NULL, NULL, '2026-03-23 07:47:23'),
(68, 6, 'update', 'schedule_events', 18, 'Updated schedule event', NULL, NULL, '2026-03-23 07:51:12'),
(69, 6, 'update', 'schedule_events', 18, 'Updated schedule event', NULL, NULL, '2026-03-23 07:55:10'),
(70, 6, 'update', 'schedule_events', 18, 'Updated schedule event', NULL, NULL, '2026-03-23 07:56:50'),
(71, 6, 'update', 'schedule_events', 18, 'Updated schedule event', NULL, NULL, '2026-03-23 08:18:58'),
(72, 6, 'update', 'schedule_events', 16, 'Updated schedule event', NULL, NULL, '2026-03-23 08:20:27'),
(73, 6, 'update', 'schedule_events', 16, 'Updated schedule event', NULL, NULL, '2026-03-23 08:22:25'),
(74, 5, 'login', 'users', 5, 'Logged in: admin2026', NULL, NULL, '2026-03-24 09:34:03'),
(75, 5, 'create', 'schedule_events', 19, 'Created event: Evalution of purok per barangay', NULL, NULL, '2026-03-24 09:43:13'),
(76, 5, 'login', 'users', 5, 'Logged in: admin2026', NULL, NULL, '2026-04-01 10:01:58'),
(77, 5, 'login', 'users', 5, 'Logged in: admin2026', NULL, NULL, '2026-04-01 18:29:11'),
(78, 5, 'create', 'schedule_events', 20, 'Created event: Maundy Thursday', NULL, NULL, '2026-04-01 18:32:36'),
(79, 5, 'login', 'users', 5, 'Logged in: admin2026', NULL, NULL, '2026-04-01 20:55:58'),
(80, 6, 'login', 'users', 6, 'Logged in: pretche1', NULL, NULL, '2026-04-01 20:58:30'),
(81, 5, 'login', 'users', 5, 'Logged in: admin2026', NULL, NULL, '2026-04-01 20:59:59'),
(82, 6, 'login', 'users', 6, 'Logged in: pretche1', NULL, NULL, '2026-04-01 21:03:52'),
(83, 6, 'create', 'schedule_events', 21, 'Created event: Team building', NULL, NULL, '2026-04-01 21:07:21'),
(84, 6, 'update', 'schedule_events', 21, 'Updated schedule event', NULL, NULL, '2026-04-01 21:08:53'),
(85, 5, 'login', 'users', 5, 'Logged in: admin2026', NULL, NULL, '2026-04-03 17:23:31'),
(86, 5, 'login', 'users', 5, 'Logged in: admin2026', NULL, NULL, '2026-04-03 21:51:02'),
(87, 5, 'create', 'users', 7, 'Created account: alas123', NULL, NULL, '2026-04-03 22:02:47'),
(88, 5, 'create', 'users', 8, 'Created account: admin2', NULL, NULL, '2026-04-03 22:03:29'),
(89, 5, 'create', 'users', 9, 'Created account: admin3', NULL, NULL, '2026-04-03 22:04:04'),
(90, 5, 'create', 'users', 10, 'Created account: staff2', NULL, NULL, '2026-04-03 22:04:46'),
(91, 5, 'update', 'users', 4, 'Updated account: staff', NULL, NULL, '2026-04-03 22:05:16'),
(92, 5, 'update', 'users', 10, 'Reset password for account ID: 10', NULL, NULL, '2026-04-03 22:05:54'),
(93, 5, 'update', 'users', 7, 'Account disabled (ID: 7)', NULL, NULL, '2026-04-03 22:06:35'),
(94, 5, 'create', 'contacts', 10, 'Created contact: Jhonaris Mariscal', NULL, NULL, '2026-04-03 22:11:31'),
(95, 5, 'update', 'contacts', 7, 'Updated contact: Jhon Doe', NULL, NULL, '2026-04-03 22:11:52'),
(96, 5, 'delete', 'contacts', 7, 'Deleted contact ID: 7', NULL, NULL, '2026-04-03 22:12:13'),
(97, 5, 'update', 'users', 5, 'Updated profile picture', NULL, NULL, '2026-04-03 22:14:18'),
(98, 5, 'update', 'users', 5, 'Updated profile picture', NULL, NULL, '2026-04-03 22:15:02'),
(99, 5, 'update', 'users', 5, 'Updated profile info', NULL, NULL, '2026-04-03 22:15:22'),
(100, 5, 'login', 'users', 5, 'Logged in: admin2026', NULL, NULL, '2026-04-11 23:39:17'),
(101, 6, 'login', 'users', 6, 'Logged in: pretche1', NULL, NULL, '2026-04-11 23:44:06'),
(102, 5, 'login', 'users', 5, 'Logged in: admin2026', NULL, NULL, '2026-04-11 23:44:23'),
(103, 5, 'update', 'users', 3, 'Reset password for account ID: 3', NULL, NULL, '2026-04-11 23:45:03'),
(104, 3, 'login', 'users', 3, 'Logged in: admin1', NULL, NULL, '2026-04-11 23:45:15'),
(105, 3, 'update', 'users', 3, 'Updated profile picture', NULL, NULL, '2026-04-11 23:47:32'),
(106, 3, 'update', 'users', 3, 'Updated profile info', NULL, NULL, '2026-04-11 23:47:46'),
(107, 3, 'update', 'contacts', 8, 'Updated contact: Pretche Orquillas', NULL, NULL, '2026-04-11 23:50:46'),
(108, 3, 'update', 'contacts', 6, 'Updated contact: Maria Makiling', NULL, NULL, '2026-04-11 23:51:02'),
(109, 3, 'update', 'minutes_documents', 23, 'Saved edits for minutes document ID: 23', NULL, NULL, '2026-04-12 00:04:00'),
(110, 5, 'login', 'users', 5, 'Logged in: admin2026', NULL, NULL, '2026-04-13 14:51:10'),
(111, 5, 'create', 'schedule_events', 22, 'Created event: Defense', NULL, NULL, '2026-04-13 15:00:23'),
(112, 5, 'update', 'schedule_events', 22, 'Updated schedule event', NULL, NULL, '2026-04-13 15:00:43'),
(113, 3, 'login', 'users', 3, 'Logged in: admin1', NULL, NULL, '2026-04-13 15:21:41'),
(114, 6, 'login', 'users', 6, 'Logged in: pretche1', NULL, NULL, '2026-04-13 15:21:59'),
(115, 5, 'login', 'users', 5, 'Logged in: admin2026', NULL, NULL, '2026-04-14 06:46:28'),
(116, 5, 'create', 'schedule_events', 23, 'Created event: Defense-Day 3', NULL, NULL, '2026-04-14 06:50:06'),
(117, 5, 'update', 'minutes_documents', 25, 'Saved edits for minutes document ID: 25', NULL, NULL, '2026-04-14 06:53:22'),
(118, 6, 'login', 'users', 6, 'Logged in: pretche1', NULL, NULL, '2026-04-14 07:25:44'),
(119, 6, 'create', 'contacts', 11, 'Created contact: Leonardo Caprio', NULL, NULL, '2026-04-14 07:26:54'),
(120, 6, 'create', 'schedule_events', 24, 'Created event: Special meeting', NULL, NULL, '2026-04-14 07:29:54'),
(121, 6, 'login', 'users', 6, 'Logged in: pretche1', NULL, NULL, '2026-04-14 07:34:28'),
(122, 5, 'login', 'users', 5, 'Logged in: admin2026', NULL, NULL, '2026-04-14 14:15:56'),
(123, 5, 'create', 'schedule_events', 25, 'Created event: Defense', NULL, NULL, '2026-04-14 14:18:34'),
(124, 5, 'update', 'minutes_documents', 24, 'Saved edits for minutes document ID: 24', NULL, NULL, '2026-04-14 14:48:22'),
(125, 5, 'login', 'users', 5, 'Logged in: admin2026', NULL, NULL, '2026-04-25 00:03:12'),
(126, 5, 'create', 'schedule_events', 26, 'Created event: Special Meeting', NULL, NULL, '2026-04-25 00:07:48'),
(127, 5, 'create', 'schedule_events', 27, 'Created event: Meeting for the upcoming alumni', NULL, NULL, '2026-04-25 00:16:12');

-- --------------------------------------------------------

--
-- Table structure for table `agenda_remarks`
--

CREATE TABLE `agenda_remarks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `agenda_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `remark_date` date NOT NULL,
  `remarks` text NOT NULL,
  `action_items` text DEFAULT NULL,
  `next_steps` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `barangay_officials`
--

CREATE TABLE `barangay_officials` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `esignature_id` bigint(20) UNSIGNED DEFAULT NULL,
  `official_title` varchar(150) NOT NULL,
  `committee` varchar(150) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 1,
  `term_start` date DEFAULT NULL,
  `term_end` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `barangay_officials`
--

INSERT INTO `barangay_officials` (`id`, `contact_id`, `full_name`, `esignature_id`, `official_title`, `committee`, `display_order`, `term_start`, `term_end`, `is_active`, `created_at`, `updated_at`) VALUES
(4, 3, 'KATE BISHOP', 3, 'Treasurer', NULL, 1, '2026-02-25', '2026-02-27', 1, '2026-02-26 23:10:24', '2026-03-21 15:47:10'),
(5, 7, 'JHON DOE', 4, 'Barangay Official', NULL, 1, '2026-02-28', '2026-02-28', 1, '2026-02-28 23:08:46', '2026-03-21 16:52:26'),
(6, 8, 'PRETCHE ORQUILLAS', NULL, 'SK Official', NULL, 1, '2026-02-19', '2026-02-19', 1, '2026-02-28 23:14:10', '2026-03-21 16:51:43');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `full_name`, `mobile`, `address`, `is_active`, `created_at`, `updated_at`) VALUES
(3, 'Kate Bishop', '09815906449', '3', 1, '2026-02-25 00:34:54', '2026-03-21 15:16:11'),
(6, 'Maria Makiling', '09865756756', '3', 1, '2026-02-25 00:42:31', '2026-04-11 23:51:02'),
(7, 'Jhon Doe', '99999999122', 'SS', 0, '2026-02-25 00:42:50', '2026-04-03 22:12:13'),
(8, 'Pretche Orquillas', '09503648981', 'Purok 2, Barangay Sto. Rosario, Magallanes, ADN', 1, '2026-03-21 15:17:45', '2026-04-11 23:50:46'),
(9, 'Carrie Naig', '09455620159', 'Wsaaaa', 1, '2026-03-22 22:58:25', NULL),
(10, 'Jhonaris Mariscal', '09915348397', 'Purok 9, Barangay Sto. Rosario, Magallanes, ADN', 1, '2026-04-03 22:11:31', NULL),
(11, 'Leonardo Caprio', '09500763972', 'Purok 3, Magallanes, ADN', 1, '2026-04-14 07:26:54', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `contact_groups`
--

CREATE TABLE `contact_groups` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `group_name` varchar(120) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_groups`
--

INSERT INTO `contact_groups` (`id`, `group_name`, `created_at`) VALUES
(1, 'Barangay Official', '2026-02-24 21:56:46'),
(2, 'Barangay Kagawad', '2026-02-24 21:57:07'),
(3, 'SK Official', '2026-03-21 15:17:33');

-- --------------------------------------------------------

--
-- Table structure for table `contact_group_members`
--

CREATE TABLE `contact_group_members` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `contact_id` bigint(20) UNSIGNED NOT NULL,
  `group_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_group_members`
--

INSERT INTO `contact_group_members` (`id`, `contact_id`, `group_id`, `created_at`) VALUES
(11, 3, 1, '2026-03-21 15:16:11'),
(15, 9, 3, '2026-03-22 22:58:25'),
(16, 10, 3, '2026-04-03 22:11:31'),
(17, 7, 1, '2026-04-03 22:11:52'),
(18, 8, 3, '2026-04-11 23:50:46'),
(19, 6, 2, '2026-04-11 23:51:02'),
(20, 11, 1, '2026-04-14 07:26:54');

-- --------------------------------------------------------

--
-- Table structure for table `esignatures`
--

CREATE TABLE `esignatures` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `official_id` bigint(20) UNSIGNED NOT NULL,
  `signature_name` varchar(120) DEFAULT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `stored_path` varchar(500) NOT NULL,
  `mime_type` varchar(120) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `esignatures`
--

INSERT INTO `esignatures` (`id`, `official_id`, `signature_name`, `original_name`, `stored_name`, `stored_path`, `mime_type`, `created_at`) VALUES
(3, 4, NULL, 'IMG_3165.JPG', 'sig_4_1ed78bc930fd84fb.jpg', 'backend/uploads/esignatures/sig_4_1ed78bc930fd84fb.jpg', 'image/jpeg', '2026-02-28 23:07:01'),
(4, 5, NULL, 'signature_nobg.png', 'sig_5_6918cb73cad656a0.png', 'backend/uploads/esignatures/sig_5_6918cb73cad656a0.png', 'image/png', '2026-02-28 23:08:46');

-- --------------------------------------------------------

--
-- Table structure for table `event_agendas`
--

CREATE TABLE `event_agendas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `schedule_event_id` bigint(20) UNSIGNED NOT NULL,
  `agenda_no` int(11) NOT NULL DEFAULT 1,
  `agenda_title` varchar(200) NOT NULL,
  `agenda_details` text DEFAULT NULL,
  `status` enum('pending','done','deferred','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_agendas`
--

INSERT INTO `event_agendas` (`id`, `schedule_event_id`, `agenda_no`, `agenda_title`, `agenda_details`, `status`, `created_at`, `updated_at`) VALUES
(4, 13, 1, 'clean', NULL, 'pending', '2026-03-02 23:22:22', NULL),
(5, 13, 2, 'eat', NULL, 'pending', '2026-03-02 23:22:22', NULL),
(15, 15, 1, 'basketball', NULL, 'pending', '2026-03-08 21:32:00', NULL),
(16, 15, 2, 'palaro', NULL, 'pending', '2026-03-08 21:32:00', NULL),
(17, 15, 3, 'clean-up drive', NULL, 'pending', '2026-03-08 21:32:00', NULL),
(18, 14, 1, 'pahina', NULL, 'done', '2026-03-08 21:38:49', NULL),
(19, 14, 2, 'meeting', NULL, 'done', '2026-03-08 21:38:49', NULL),
(20, 14, 3, 'snack', NULL, 'done', '2026-03-08 21:38:49', NULL),
(29, 18, 1, 'Suggestions and feedbacks', NULL, 'pending', '2026-03-23 08:18:56', NULL),
(31, 16, 1, 'Project Approval', NULL, 'pending', '2026-03-23 08:22:25', NULL),
(34, 21, 1, 'Barangay improvement', NULL, 'pending', '2026-04-01 21:08:52', NULL),
(35, 21, 2, 'Others', NULL, 'pending', '2026-04-01 21:08:52', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `event_attachments`
--

CREATE TABLE `event_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `schedule_event_id` bigint(20) UNSIGNED NOT NULL,
  `uploaded_by` bigint(20) UNSIGNED NOT NULL,
  `file_type` enum('image','pdf','doc','docx','xls','xlsx','other') NOT NULL DEFAULT 'other',
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `stored_path` varchar(500) NOT NULL,
  `mime_type` varchar(120) DEFAULT NULL,
  `file_size` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_attachments`
--

INSERT INTO `event_attachments` (`id`, `schedule_event_id`, `uploaded_by`, `file_type`, `original_name`, `stored_name`, `stored_path`, `mime_type`, `file_size`, `created_at`) VALUES
(1, 13, 3, 'pdf', 'Brgy_Sto.Rosario_Minutes.pdf', '13_1772455342_ab246775.pdf', 'backend/uploads/event_attachments/13_1772455342_ab246775.pdf', 'application/pdf', 2688963, '2026-03-02 20:42:22'),
(2, 13, 3, 'pdf', 'Brgy_Sto.Rosario_Minutes_2.pdf', '13_1772455342_a385f73d.pdf', 'backend/uploads/event_attachments/13_1772455342_a385f73d.pdf', 'application/pdf', 2568847, '2026-03-02 20:42:22'),
(3, 14, 3, 'image', 'ab4828ce-5776-4853-b6d4-9cf93e3a00ff.jpg', '14_1772465465_8ff6992f.jpg', 'backend/uploads/event_attachments/14_1772465465_8ff6992f.jpg', 'image/jpeg', 1065399, '2026-03-02 23:31:05'),
(4, 14, 3, 'image', '23f60177-5b0b-4570-a0dc-dd6012e2895e.jpg', '14_1772465465_a4fa16fe.jpg', 'backend/uploads/event_attachments/14_1772465465_a4fa16fe.jpg', 'image/jpeg', 966738, '2026-03-02 23:31:05');

-- --------------------------------------------------------

--
-- Table structure for table `event_signatures`
--

CREATE TABLE `event_signatures` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `schedule_event_id` bigint(20) UNSIGNED NOT NULL,
  `contact_id` bigint(20) UNSIGNED NOT NULL,
  `esignature_id` bigint(20) UNSIGNED DEFAULT NULL,
  `signed_at` datetime DEFAULT NULL,
  `sign_status` enum('pending','signed','declined') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `minutes_attachments`
--

CREATE TABLE `minutes_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `minutes_document_id` bigint(20) UNSIGNED NOT NULL,
  `uploaded_by` bigint(20) UNSIGNED NOT NULL,
  `file_type` enum('image','pdf','doc','docx','xls','xlsx','other') NOT NULL DEFAULT 'other',
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `stored_path` varchar(500) NOT NULL,
  `mime_type` varchar(120) DEFAULT NULL,
  `file_size` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `minutes_attachments`
--

INSERT INTO `minutes_attachments` (`id`, `minutes_document_id`, `uploaded_by`, `file_type`, `original_name`, `stored_name`, `stored_path`, `mime_type`, `file_size`, `created_at`) VALUES
(32, 19, 3, 'image', 'ab4828ce-5776-4853-b6d4-9cf93e3a00ff.jpg', 'ec9b8b2d6774418ac65fd1839012a4b1.jpg', 'uploads/minutes/19/ec9b8b2d6774418ac65fd1839012a4b1.jpg', 'image/jpeg', 1065399, '2026-03-02 19:25:54'),
(33, 19, 3, 'image', '23f60177-5b0b-4570-a0dc-dd6012e2895e.jpg', '38be9e6b0acef04576092da76a0d7ac7.jpg', 'uploads/minutes/19/38be9e6b0acef04576092da76a0d7ac7.jpg', 'image/jpeg', 966738, '2026-03-02 19:25:55'),
(34, 20, 5, 'image', '23f60177-5b0b-4570-a0dc-dd6012e2895e.jpg', '61c489501899f5d6f20350d096b92f0d.jpg', 'uploads/minutes/20/61c489501899f5d6f20350d096b92f0d.jpg', 'image/jpeg', 966738, '2026-03-21 15:11:16'),
(35, 21, 5, 'image', '23f60177-5b0b-4570-a0dc-dd6012e2895e.jpg', '5108dcc2b4b58f8880e1107491b1fcad.jpg', 'uploads/minutes/21/5108dcc2b4b58f8880e1107491b1fcad.jpg', 'image/jpeg', 966738, '2026-04-01 10:03:05'),
(36, 22, 3, 'image', '70ded1b8-cb9f-4e7e-ae80-a50cb5d25100.jpg', '3fcf808e8007ebdc429c67c5e1d14db0.jpg', 'uploads/minutes/22/3fcf808e8007ebdc429c67c5e1d14db0.jpg', 'image/jpeg', 425887, '2026-04-11 23:58:42'),
(37, 23, 3, 'image', 'Minutes-Feb11.jpg', 'ab818e61197208eb20be2dfaa7eed33e.jpg', 'uploads/minutes/23/ab818e61197208eb20be2dfaa7eed33e.jpg', 'image/jpeg', 425887, '2026-04-11 23:59:59'),
(38, 24, 3, 'image', 'Minutes-Feb11.jpg', 'cf66751c76e0185a43772699953929c5.jpg', 'uploads/minutes/24/cf66751c76e0185a43772699953929c5.jpg', 'image/jpeg', 425887, '2026-04-12 00:05:07'),
(39, 24, 3, 'image', 'Minutes-Feb11-Continuation.jpg', '2233388e28cc940d32d5e05f4a38d3c3.jpg', 'uploads/minutes/24/2233388e28cc940d32d5e05f4a38d3c3.jpg', 'image/jpeg', 415960, '2026-04-12 00:05:07'),
(40, 25, 5, 'image', 'Minutes-Feb11.jpg', '5bf994235c7f4e16e3b3919853f906b4.jpg', 'uploads/minutes/25/5bf994235c7f4e16e3b3919853f906b4.jpg', 'image/jpeg', 425887, '2026-04-14 06:51:31'),
(41, 26, 5, 'image', 'ab4828ce-5776-4853-b6d4-9cf93e3a00ff.jpg', 'b4399c8f61efcd682e8ec8bf5cd0b9eb.jpg', 'uploads/minutes/26/b4399c8f61efcd682e8ec8bf5cd0b9eb.jpg', 'image/jpeg', 1065399, '2026-04-14 14:25:07'),
(42, 26, 5, 'image', '23f60177-5b0b-4570-a0dc-dd6012e2895e.jpg', '430e234146d7db99ca1120918c873d58.jpg', 'uploads/minutes/26/430e234146d7db99ca1120918c873d58.jpg', 'image/jpeg', 966738, '2026-04-14 14:25:07'),
(43, 27, 5, 'image', 'Minutes-Feb11.jpg', '815be931efe42e4f7e21672c438d85c0.jpg', 'uploads/minutes/27/815be931efe42e4f7e21672c438d85c0.jpg', 'image/jpeg', 425887, '2026-04-25 00:17:51'),
(44, 27, 5, 'image', 'Minutes-Feb11-Continuation.jpg', '9d52b2e88c4cc1bc69e19470792ba914.jpg', 'uploads/minutes/27/9d52b2e88c4cc1bc69e19470792ba914.jpg', 'image/jpeg', 415960, '2026-04-25 00:17:51');

-- --------------------------------------------------------

--
-- Table structure for table `minutes_documents`
--

CREATE TABLE `minutes_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `schedule_event_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL DEFAULT 'Minutes of Meeting',
  `status` enum('draft','extracted','final') NOT NULL DEFAULT 'draft',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `minutes_documents`
--

INSERT INTO `minutes_documents` (`id`, `schedule_event_id`, `created_by`, `title`, `status`, `created_at`, `updated_at`) VALUES
(19, NULL, 3, 'Minutes of Meeting', 'extracted', '2026-03-02 19:25:54', '2026-03-02 19:26:36'),
(20, NULL, 5, 'Minutes of Meeting', 'extracted', '2026-03-21 15:11:16', '2026-03-21 15:11:43'),
(21, NULL, 5, 'Minutes of Meeting', 'draft', '2026-04-01 10:03:05', NULL),
(22, NULL, 3, 'Minutes of Meeting', 'draft', '2026-04-11 23:58:41', NULL),
(23, NULL, 3, 'Minutes of Meeting as of February 11, 2026', 'final', '2026-04-11 23:59:59', '2026-04-12 00:04:00'),
(24, NULL, 3, 'Minutes of Meeting', 'final', '2026-04-12 00:05:07', '2026-04-14 14:48:22'),
(25, NULL, 5, 'Feb11 Minutes', 'final', '2026-04-14 06:51:31', '2026-04-14 06:53:22'),
(26, NULL, 5, 'Regular Meeting', 'extracted', '2026-04-14 14:25:07', '2026-04-14 14:41:23'),
(27, NULL, 5, '4th Regular Session', 'extracted', '2026-04-25 00:17:51', '2026-04-25 00:19:06');

-- --------------------------------------------------------

--
-- Table structure for table `minutes_extractions`
--

CREATE TABLE `minutes_extractions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `minutes_document_id` bigint(20) UNSIGNED NOT NULL,
  `extraction_version` int(11) NOT NULL DEFAULT 1,
  `extracted_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`extracted_json`)),
  `model_name` varchar(120) DEFAULT NULL,
  `confidence_score` decimal(5,2) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `minutes_extractions`
--

INSERT INTO `minutes_extractions` (`id`, `minutes_document_id`, `extraction_version`, `extracted_json`, `model_name`, `confidence_score`, `created_at`) VALUES
(16, 19, 1, '{\n  \"session_title\": \"18th Regular Session\",\n  \"session_date\": \"September 8, 2023\",\n  \"session_time\": \"\",\n\n  \"present_list\": [\n    { \"name\": \"Hon. Cleopatra C. Roces\", \"position\": \"Punong Barangay\" },\n    { \"name\": \"Hon. Allan D. Duran\", \"position\": \"Barangay Kagawad\" },\n    { \"name\": \"Hon. Rodrigo C. Abao, Jr.\", \"position\": \"Barangay Kagawad\" },\n    { \"name\": \"Hon. Margie M. Gabato\", \"position\": \"Barangay Kagawad\" },\n    { \"name\": \"Hon. Ruel M. Bueno\", \"position\": \"Barangay Kagawad\" },\n    { \"name\": \"Hon. Arnel N. Orlandez\", \"position\": \"Barangay Kagawad\" },\n    { \"name\": \"Hon. Lodovico C. Sato, Jr.\", \"position\": \"Barangay Kagawad\" },\n    { \"name\": \"Hon. Leonardo L. Cruza\", \"position\": \"Barangay Kagawad\" },\n    { \"name\": \"Hon. Charles Nino Rey L. Jamito\", \"position\": \"SK Chairperson\" }\n  ],\n  \"absent_list\": [],\n  \"others_present\": \"\",\n\n  \"presiding_officer\": \"\",\n  \"prayer_leader\": \"\",\n  \"adjournment_time\": \"\",\n  \"present_count\": null,\n  \"has_quorum\": null,\n\n  \"approval_of_minutes\": \"Excerpt from the minutes of the 17th Regular Session of the Sangguniang Barangay of Brgy. Sto. Rosario, Magallanes, Agusan del Norte held on August 1, 2023, 9 o\'clock in the morning (9am) at the Barangay Session Hall.\",\n  \"agenda\": \"\",\n  \"matters_for_information\": \"\",\n  \"privilege_hour\": \"6.1 Question Hour\",\n  \"first_reading_resolution\": \"Reso. No. __ - 2023: Requesting...\",\n  \"first_reading_referral\": \"7.1 - Reso. No. __ - 2023: Requesting... Movant - SBM __\",\n  \"committee_report\": \"\",\n  \"calendar_of_business\": \"9.1 Unfinished Business\\n9.2 Business of the Day\\n9.3 Unassigned Business\",\n  \"third_reading\": \"\",\n  \"other_matters\": \"\",\n  \"announcement\": \"\",\n\n  \"adjournment_movant\": \"\",\n  \"adjournment_seconder\": \"\",\n\n  \"call_to_order\": \"\",\n  \"roll_call\": \"\",\n  \"adjournment\": \"\"\n}', 'gemini-3-flash-preview', NULL, '2026-03-02 19:26:36'),
(17, 20, 1, '{\n  \"session_title\": \"17th Regular Session\",\n  \"session_date\": \"August 1, 2025\",\n  \"session_time\": \"9:00 AM\",\n  \"present_list\": [\n    {\n      \"name\": \"Hon. Cleopatra C. Roces\",\n      \"position\": \"Punong Barangay\"\n    },\n    {\n      \"name\": \"Hon. Allan D. Duran\",\n      \"position\": \"Barangay Kagawad\"\n    },\n    {\n      \"name\": \"Hon. Rodrigo C. Abao, Jr.\",\n      \"position\": \"Barangay Kagawad\"\n    },\n    {\n      \"name\": \"Hon. Margie M. Gabato\",\n      \"position\": \"Barangay Kagawad\"\n    },\n    {\n      \"name\": \"Hon. Ruel M. Bueno\",\n      \"position\": \"Barangay Kagawad\"\n    },\n    {\n      \"name\": \"Hon. Arnel N. Orlandez\",\n      \"position\": \"Barangay Kagawad\"\n    },\n    {\n      \"name\": \"Hon. Lodovico C. Sato, Jr.\",\n      \"position\": \"Barangay Kagawad\"\n    },\n    {\n      \"name\": \"Hon. Leonardo L. Cruza\",\n      \"position\": \"Barangay Kagawad\"\n    },\n    {\n      \"name\": \"Hon. Charles Nino Rey L. Jamito\",\n      \"position\": \"SK Chairperson\"\n    }\n  ],\n  \"absent_list\": [],\n  \"others_present\": \"\",\n  \"presiding_officer\": \"\",\n  \"prayer_leader\": \"\",\n  \"adjournment_time\": \"\",\n  \"present_count\": null,\n  \"has_quorum\": null,\n  \"approval_of_minutes\": \"\",\n  \"agenda\": \"\",\n  \"matters_for_information\": \"\",\n  \"privilege_hour\": \"\",\n  \"first_reading_resolution\": \"\",\n  \"first_reading_referral\": \"\",\n  \"committee_report\": \"\",\n  \"calendar_of_business\": \"\",\n  \"third_reading\": \"\",\n  \"other_matters\": \"\",\n  \"announcement\": \"\",\n  \"adjournment_movant\": \"\",\n  \"adjournment_seconder\": \"\",\n  \"call_to_order\": \"\",\n  \"roll_call\": \"\",\n  \"adjournment\": \"\"\n}', 'gemini-3-flash-preview', NULL, '2026-03-21 15:11:43'),
(18, 23, 1, '{\"session_title\":\"4TH REGULAR SESSION\",\"session_date\":\"Feb. 14, 2024\",\"session_time\":\"\",\"present_list\":[{\"name\":\"Hon. Ruel M. Bueno\",\"position\":\"\"}],\"absent_list\":[{\"name\":\"Hon. Sol\",\"position\":\"\"}],\"others_present\":\"None.\",\"presiding_officer\":\"Hon. Ruel M. Bueno\",\"prayer_leader\":\"Hon. Ruel M. Bueno\",\"adjournment_time\":\"\",\"present_count\":null,\"has_quorum\":null,\"approval_of_minutes\":\"Orlando - ang circuit breaker asa kuhaan og budget. Gabato - sa O & M sa Kalubihan. Cruza - installation of st. light near Chiva res. & busted bulbs to all st. lights. Abao - to cover the MRF gate w/ tarpaulin. Dimam - Si Noel Tutor tagan nato og date para maayo jud ang P. Add. Margie / Abao.\",\"agenda\":\"Cruza - ang bulb palitun sa Area 1 tracker. 15W - 20 pcs, 10W - 20 pcs. Reso. No. 11-2024: A reso. to purchase bulbs. Dimam - ang motrabaho sa breaker. Reso. No. 12-2024: A reso. requesting the brgy. council to repair P7 pathway near Gonzales Res. - (Vansyul res). Brgy. Data Assistance kinahanglan na nato i-process. I-sign sa Date nga mag-commence pod ang brgy. Attorney Butuan mi mag-quote. Gabato - Blood letting Nov. 4, 2024, marriage mayore to donate. Food packs will be given. Reso. No. 13-2024: A reso. authorizing the brgy. to release the amt. of P10k for blood-letting activity to be taken from SPA. Reso. No. 14-2024: Feb 23 - 1 week - deworming (2k). Reso. No. 15-2024: Office supplies of BNS & brgy. Hall.\",\"matters_for_information\":\"None.\",\"privilege_hour\":\"None.\",\"first_reading_resolution\":\"Reso. No. 11-2024: A reso. to purchase bulbs.\\nReso. No. 12-2024: A reso. requesting the brgy. council to repair P7 pathway near Gonzales Res.\\nReso. No. 13-2024: A reso. authorizing the brgy. to release the amt. of P10k for blood-letting activity to be taken from SPA.\\nReso. No. 14-2024: Feb 23 - 1 week - deworming (2k).\\nReso. No. 15-2024: Office supplies of BNS & brgy. Hall.\",\"first_reading_referral\":\"None.\",\"committee_report\":\"None.\",\"calendar_of_business\":\"None.\",\"third_reading\":\"None.\",\"other_matters\":\"None.\",\"announcement\":\"None.\",\"adjournment_movant\":\"\",\"adjournment_seconder\":\"\",\"call_to_order\":\"Invo.: Hon. Ruel M. Bueno\",\"roll_call\":\"None.\",\"adjournment\":\"None.\",\"signature_list\":[{\"official_id\":null,\"name\":\"HON. CLEOPATRA C. ROCES\",\"title\":\"Punong Barangay\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"JANE A. ESMA\",\"title\":\"Barangay Secretary\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"HON. ALLAN D. DURAN\",\"title\":\"Sangguniang Barangay Member\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"HON. RODRIGO C. ABAO, JR.\",\"title\":\"Sangguniang Barangay Member\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"HON. MARGIE M. GABATO\",\"title\":\"Sangguniang Barangay Member\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"HON. RUEL M. BUENO\",\"title\":\"Sangguniang Barangay Member\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"HON. ARNEL N. ORLANDEZ\",\"title\":\"Sangguniang Barangay Member\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"HON. LODOVICO C. SATO, JR.\",\"title\":\"Sangguniang Barangay Member\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"HON. LEONARDO L. CRUZA\",\"title\":\"Sangguniang Barangay Member\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"HON. CHARLES NIÑO REY JAMITO\",\"title\":\"SK Chairperson\",\"signature_url\":null,\"show_signature\":false}],\"session_date_display\":\"February 14, 2024\"}', 'manual-edit', NULL, '2026-04-12 00:01:02'),
(19, 24, 1, '{\"session_title\":\"4TH REGULAR SESSION\",\"session_date\":\"February 11, 2024\",\"session_time\":\"\",\"present_list\":[{\"name\":\"Hon. Ruel M. Bueno\",\"position\":\"Sangguniang Barangay Member\"},{\"name\":\"Hon. Orlandez\",\"position\":\"Sangguniang Barangay Member\"},{\"name\":\"Hon. Gabato\",\"position\":\"Sangguniang Barangay Member\"},{\"name\":\"Hon. Cruza\",\"position\":\"Sangguniang Barangay Member\"},{\"name\":\"Hon. Abao\",\"position\":\"Sangguniang Barangay Member\"},{\"name\":\"Hon. Dumam\",\"position\":\"Sangguniang Barangay Member\"}],\"absent_list\":[{\"name\":\"Hon. Sula\",\"position\":\"Exempted\"}],\"others_present\":\"Noel Tutor\",\"presiding_officer\":\"\",\"prayer_leader\":\"Hon. Ruel M. Bueno\",\"adjournment_time\":\"12:11 PM\",\"present_count\":null,\"has_quorum\":true,\"approval_of_minutes\":\"Orlandez - circuit breaker budget. Gabato - O&M sa Kalubihan. Cruza - installation of St. light near Cluaves & busted bulbs. Abao - MRF gate tarpaulin. Dumam - Noel Tutor frozen rate.\",\"agenda\":\"Cruza - bulb purchase 15W-20pcs, 10W-20pcs. Reso. No. 11-2024: A reso. to purchase bulbs.\",\"matters_for_information\":\"Gabato - During Lupon meeting, Pres. Jonapal regarding repainting Paseo to beautify. Dumam - LGBTQ organizing.\",\"privilege_hour\":\"None.\",\"first_reading_resolution\":\"Reso. No. 11-2024: A reso. to purchase bulbs.\\nReso. No. 12-2024: A reso. requesting the brgy council to repair P7 pathway near Gonzales Res.\\nReso. No. 13-2024: A reso. authorizing the brgy to release the amt. of P10k for blood-letting activity to be taken from SPA.\\nReso. No. 14-2024: activity to be taken from SPA.\\nReso. No. 15: Office supplies of BNS & brgy hall.\\nReso. No. 16-2024: A resolution to release the amt. of P10k for Women\'s Mo. celebration to be taken from SPA.\\nReso. No. 17-2024: A reso. to rehabilitate the canal at P1 to be taken from 20% BDF.\",\"first_reading_referral\":\"The council discussed various resolutions including the purchase of light bulbs, pathway repairs, funding for blood-letting and Women\'s Month activities, office supplies for BNS, and canal rehabilitation.\",\"committee_report\":\"Dumam - LGU registration of organizations (Women, Sr. Citizen, Solo Parent, PWD). Gabato - Assignment of members to sectors: Orlandez (PWD, Women), Bueno (Sr. Citizen), Gabato (Solo Parent), Dumam (LGBTQ+).\",\"calendar_of_business\":\"None.\",\"third_reading\":\"None.\",\"other_matters\":\"The Brgy. Council agreed to give financial Ass. to Laura Garofino for her medical.\",\"announcement\":\"Feb. 14 - Valentines Day; Feb. 17 - New Year Chinese; Feb. 18 - Ed\'l Adha.\",\"adjournment_movant\":\"\",\"adjournment_seconder\":\"\",\"call_to_order\":\"None.\",\"roll_call\":\"None.\",\"adjournment\":\"None.\",\"signature_list\":[{\"official_id\":null,\"name\":\"HON. CLEOPATRA C. ROCES\",\"title\":\"Punong Barangay\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"JANE A. ESMA\",\"title\":\"Barangay Secretary\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"HON. ALLAN D. DURAN\",\"title\":\"Sangguniang Barangay Member\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"HON. RODRIGO C. ABAO, JR.\",\"title\":\"Sangguniang Barangay Member\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"HON. MARGIE M. GABATO\",\"title\":\"Sangguniang Barangay Member\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"HON. RUEL M. BUENO\",\"title\":\"Sangguniang Barangay Member\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"HON. ARNEL N. ORLANDEZ\",\"title\":\"Sangguniang Barangay Member\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"HON. LODOVICO C. SATO, JR.\",\"title\":\"Sangguniang Barangay Member\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"HON. LEONARDO L. CRUZA\",\"title\":\"Sangguniang Barangay Member\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"HON. CHARLES NIÑO REY JAMITO\",\"title\":\"SK Chairperson\",\"signature_url\":null,\"show_signature\":false}]}', 'manual-edit', NULL, '2026-04-12 00:05:43'),
(20, 25, 1, '{\"session_title\":\"4TH REGULAR SESSION\",\"session_date\":\"February 11, 2026\",\"session_time\":\"\",\"present_list\":[{\"name\":\"Hon. Ruel M. Bueno\",\"position\":\"\"}],\"absent_list\":[{\"name\":\"Hon. Sol\",\"position\":\"Exempted\"}],\"others_present\":\"None.\",\"presiding_officer\":\"Hon. Ruel M. Bueno\",\"prayer_leader\":\"\",\"adjournment_time\":\"\",\"present_count\":null,\"has_quorum\":null,\"approval_of_minutes\":\"Orlando - ang circuit breaker asa kuhaan og budget. Gabato - sa O & M sa Kalubihan. Cruza - installation of St. light near Chiva res. & busted bulbs to all st. lights. Abao to cover the MRF gate w/ tarpaulin. Duman - si Noel Tutor frozen rato ug diki para maayo jud ang P. Add. Margie / Abao\",\"signature_list\":[{\"official_id\":null,\"name\":\"HON. CLEOPATRA C. ROCES\",\"title\":\"Punong Barangay\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"JANE A. ESMA\",\"title\":\"Barangay Secretary\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"HON. ALLAN D. DURAN\",\"title\":\"Sangguniang Barangay Member\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"HON. RODRIGO C. ABAO, JR.\",\"title\":\"Sangguniang Barangay Member\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"HON. MARGIE M. GABATO\",\"title\":\"Sangguniang Barangay Member\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"HON. RUEL M. BUENO\",\"title\":\"Sangguniang Barangay Member\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"HON. ARNEL N. ORLANDEZ\",\"title\":\"Sangguniang Barangay Member\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"HON. LODOVICO C. SATO, JR.\",\"title\":\"Sangguniang Barangay Member\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"HON. LEONARDO L. CRUZA\",\"title\":\"Sangguniang Barangay Member\",\"signature_url\":null,\"show_signature\":false},{\"official_id\":null,\"name\":\"HON. CHARLES NIÑO REY JAMITO\",\"title\":\"SK Chairperson\",\"signature_url\":null,\"show_signature\":false}],\"call_to_order\":\"None.\",\"roll_call\":\"None.\",\"adjournment\":\"None.\",\"agenda\":\"None.\",\"matters_for_information\":\"None.\",\"privilege_hour\":\"None.\",\"first_reading_referral\":\"None.\",\"committee_report\":\"None.\",\"calendar_of_business\":\"None.\",\"third_reading\":\"None.\",\"other_matters\":\"None.\",\"announcement\":\"None.\"}', 'manual-edit', NULL, '2026-04-14 06:53:02'),
(21, 26, 1, '{\"session_title\":\"18th REGULAR SESSION\",\"session_date\":\"SEPTEMBER 8, 2023\",\"session_time\":\"\",\"present_list\":[{\"name\":\"Hon. Cleopatra C. Roces\",\"position\":\"Punong Barangay\"},{\"name\":\"Hon. Allan D. Duran\",\"position\":\"Barangay Kagawad\"},{\"name\":\"Hon. Rodrigo C. Abao, Jr.\",\"position\":\"Barangay Kagawad\"},{\"name\":\"Hon. Margie M. Gabato\",\"position\":\"Barangay Kagawad\"},{\"name\":\"Hon. Ruel M. Bueno\",\"position\":\"Barangay Kagawad\"},{\"name\":\"Hon. Arnel N. Orlandez\",\"position\":\"Barangay Kagawad\"},{\"name\":\"Hon. Lodovico C. Sato, Jr.\",\"position\":\"Barangay Kagawad\"},{\"name\":\"Hon. Leonardo L. Cruza\",\"position\":\"Barangay Kagawad\"},{\"name\":\"Hon. Charles Nino Rey L. Jamito\",\"position\":\"SK Chairperson\"}],\"absent_list\":[],\"others_present\":\"\",\"presiding_officer\":\"\",\"prayer_leader\":\"\",\"adjournment_time\":\"\",\"present_count\":null,\"has_quorum\":null,\"approval_of_minutes\":\"\",\"agenda\":\"\",\"matters_for_information\":\"\",\"privilege_hour\":\"\",\"first_reading_resolution\":\"Reso. No. __ - 2023: Requesting . . . Movant - SBM ____\",\"first_reading_referral\":\"\",\"committee_report\":\"\",\"calendar_of_business\":\"\",\"third_reading\":\"\",\"other_matters\":\"\",\"announcement\":\"\",\"adjournment_movant\":\"\",\"adjournment_seconder\":\"\",\"call_to_order\":\"I. CALL TO ORDER\",\"roll_call\":\"II. ROLL CALL\",\"adjournment\":\"XIII. ADJOURNMENT\"}', 'gemini-3-flash-preview', NULL, '2026-04-14 14:41:23'),
(22, 27, 1, '{\"session_title\":\"4TH REGULAR SESSION\",\"session_date\":\"February 14, 2024\",\"session_time\":\"\",\"present_list\":[{\"name\":\"Hon. Ruel M. Bueno\",\"position\":\"SBM\"},{\"name\":\"Orlandez\",\"position\":\"SBM\"},{\"name\":\"Gabato\",\"position\":\"SBM\"},{\"name\":\"Cruza\",\"position\":\"SBM\"},{\"name\":\"Abao\",\"position\":\"SBM\"},{\"name\":\"Duman\",\"position\":\"SBM\"},{\"name\":\"Margie\",\"position\":\"\"}],\"absent_list\":[{\"name\":\"Hon. Sola\",\"position\":\"Exempted\"}],\"others_present\":\"\",\"presiding_officer\":\"\",\"prayer_leader\":\"Hon. Ruel M. Bueno\",\"adjournment_time\":\"12:11 PM\",\"present_count\":null,\"has_quorum\":null,\"approval_of_minutes\":\"Orlandez - ang circuit breaker asa kuhaan og budget.\\nGabato - sa O & M sa Kalubihan.\\nCruza - installation of st. light near Chiva res. & busted bulbs to all st. lights.\\nAbao to cover the MRF gate w/ tarpaulin.\\nDuman - si Noel Tutor tagaan nato og dako para maayo jud ang P. Add.\\nMargie/Abao\",\"agenda\":\"Cruza - ang bulb ibilin sa Brgy. Hall 15w - 20 pcs, 10w - 20 pcs.\\nDuman - ang motrabaho sa breaker.\\nBrgy. Dale Assistance kinahanglan na nato i-process. I-remind sa Dale nga mag-comms pod ang brgy. Admin Butuan mi mag-quote.\\nGabato - Blood letting - Mar. 4, 2024, encourage everyone to donate. Food packs will be given.\\nFeb 23 - 1st male - deworming (2k)\\nSched: Public Hearing - Feb. 3rd Sunday of\\nOrlandez - magpalit para boots, rain coat, first aid kit, whistle. I-include rako ang 2 unit handheld radio.\\nThe Brgy. Council agreed to donate/give financial Ass. to Laura Garofini for her wedding.\",\"matters_for_information\":\"Gabato - During Lupon meeting, ingon si Pres. Jonapal mag-repaint sa Paseo og magbutang para flag let para ma-beautify. Motabang sila og butang or pahira sila.\\nDuman - Ang LGBTQ ako gamit krap para ma-organize sila.\",\"privilege_hour\":\"\",\"first_reading_resolution\":\"Reso. No. 11-2024: A reso. to purchase bulbs.\\nReso. No. 12-2024: A reso. requesting the brgy. council to repair P7 pathway near Gonzales Res. - Jonapal res.\\nReso. No. 13-2024: A reso. authorizing the brgy. to release the amt. of P10k for blood-letting\\nReso. No. 14 activity to be taken from SPA.\\nReso. No. 15: office supplies of BHS & brgy. Hall\\nReso. No. 16-2024: A resolution to release the amt. of P10k for Women\'s Mo. Celebration to be taken from SPA.\\nReso. No. 17-2024: A reso. to rehabilitate the canal at P1 to be taken from 20% BDF.\",\"first_reading_referral\":\"\",\"committee_report\":\"Duman - Ang gusto sa LGU nga ipa register sa SEC ang mga organizations like, Women\'s, Sr. Citizen, Solo Parent & PWD. Para kung may mga assistance maka benefit ang tanan.\\nGabato - Naa naman miyembro si SBM Orlandez itunol niya ang lain-lain responsibilidad sa ila.\\nOrlandez - Duman - PWD\\nSr. Citizen - Bueno\\nSolo Parent - Gabato\\nWomen\'s - Orlandez\\nLGBTQ+ - Duman\",\"calendar_of_business\":\"\",\"third_reading\":\"\",\"other_matters\":\"\",\"announcement\":\"Feb. 14 - Valentines Day\\nFeb. 17 - New Year\'s Chinese\\nFeb. 18 - Ed\'l Adha\",\"adjournment_movant\":\"Gabato\",\"adjournment_seconder\":\"Abao\",\"call_to_order\":\"Invo.: Hon. Ruel M. Bueno\",\"roll_call\":\"\",\"adjournment\":\"\"}', 'gemini-3-flash-preview', NULL, '2026-04-25 00:19:06');

-- --------------------------------------------------------

--
-- Table structure for table `schedule_events`
--

CREATE TABLE `schedule_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `schedule_type` enum('meeting','event','appointment','other') NOT NULL DEFAULT 'meeting',
  `description` text DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `is_all_day` tinyint(1) NOT NULL DEFAULT 0,
  `sms_message` text DEFAULT NULL,
  `send_sms_now` tinyint(1) NOT NULL DEFAULT 1,
  `allow_auto_notify` tinyint(1) NOT NULL DEFAULT 0,
  `notify_offset_minutes` int(11) NOT NULL DEFAULT 60,
  `status` enum('draft','scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schedule_events`
--

INSERT INTO `schedule_events` (`id`, `created_by`, `title`, `schedule_type`, `description`, `location`, `start_datetime`, `end_datetime`, `is_all_day`, `sms_message`, `send_sms_now`, `allow_auto_notify`, `notify_offset_minutes`, `status`, `created_at`, `updated_at`) VALUES
(12, 3, 'sadsa', 'meeting', 'asda', 'sada', '2026-02-26 21:13:00', '2026-02-26 22:13:00', 0, 'Subject: sadsa\r\nWhen: Feb 26, 2026 9:13 PM - 10:13 PM\r\nWhere: sada\r\nDetails: asda\r\n\r\n- admin1', 1, 0, 60, 'scheduled', '2026-02-26 21:03:45', NULL),
(13, 3, 'asdad', 'meeting', NULL, 'balay', '2026-03-03 10:51:00', '2026-03-03 11:51:00', 0, 'Subject: asdad\r\nTo: delven\r\nWhen: Mar 03, 2026 10:51 AM - 11:51 AM\r\nWhere: balay\r\n\r\nAgenda:\r\n1. clean\r\n2. eat\r\n\r\n- admin1', 1, 1, 6, 'scheduled', '2026-03-02 20:42:22', '2026-03-02 23:22:22'),
(14, 3, 'pahina', 'meeting', NULL, 'purok 3 soriano', '2026-03-03 13:39:00', '2026-03-03 14:39:00', 0, 'Subject: pahina\r\nTo: delven\r\nWhen: Mar 03, 2026 1:39 PM - 2:39 PM\r\nWhere: purok 3 soriano\r\n\r\nAgenda:\r\n1. pahina (done)\r\n2. meeting (done)\r\n3. snack (done)\r\n\r\n- admin1', 1, 0, 60, 'scheduled', '2026-03-02 23:31:05', '2026-03-08 21:38:48'),
(15, 3, 'meeting', 'meeting', NULL, 'brgy. hall', '2026-03-03 06:45:00', '2026-03-03 07:45:00', 0, 'Subject: meeting\r\nTo: delven\r\nWhen: Mar 03, 2026 6:45 AM - 7:45 AM\r\nWhere: brgy. hall\r\n\r\nAgenda:\r\n1. basketball\r\n2. palaro\r\n3. clean-up drive\r\n\r\n- admin1', 1, 0, 60, 'scheduled', '2026-03-02 23:36:35', '2026-03-08 21:32:00'),
(16, 5, 'Project presentation.', 'meeting', NULL, 'Online (Work from Home)', '2026-03-23 08:25:00', '2026-03-23 10:30:00', 0, 'Subject: Project presentation.\r\nTo: SK Official, Carrie Naig\r\nWhen: Mar 23, 2026 8:25 AM - 10:30 AM\r\nWhere: Online (Work from Home)\r\n\r\nAgenda:\r\n1. Project Approval\r\n\r\n- Pretche Orquillas', 1, 0, 60, 'scheduled', '2026-03-21 15:08:57', '2026-03-23 08:22:25'),
(17, 6, 'Alumni', 'meeting', 'Alumni 2026', 'Barangay Gymnasium', '2026-03-21 19:02:00', '2026-03-21 22:00:00', 0, 'Subject: Alumni\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nWhen: Mar 21, 2026 7:02 PM - 10:00 PM\r\nWhere: Barangay Gymnasium\r\nDetails: Alumni 2026\r\n\r\n- Pretche Orquillas', 1, 0, 60, 'scheduled', '2026-03-21 18:53:26', NULL),
(18, 6, 'Project Testing', 'meeting', NULL, 'Barangay Sto. Rosario, Magallanes', '2026-03-23 08:19:00', '2026-03-23 10:30:00', 0, 'Subject: Project Testing\r\nTo: Carrie Naig, Pretche Orquillas\r\nWhen: Mar 23, 2026 8:19 AM - 10:30 AM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', 1, 1, 30, 'scheduled', '2026-03-22 23:11:58', '2026-03-23 08:18:56'),
(19, 5, 'Evalution of purok per barangay', 'meeting', NULL, 'Barangay Sto. Rosario', '2026-03-29 07:00:00', '2026-03-30 17:00:00', 0, 'Subject: Evalution of purok per barangay\r\nTo: Barangay Kagawad, Barangay Official\r\nWhen: Mar 29, 2026 7:00 AM - Mar 30, 2026 5:00 PM\r\nWhere: Barangay Sto. Rosario\r\n\r\n- System Administrator', 1, 0, 60, 'scheduled', '2026-03-24 09:43:10', NULL),
(20, 5, 'Maundy Thursday', 'meeting', 'Mag gather tanan residents', 'Brgy Hall', '2026-04-01 19:00:00', '2026-04-02 10:00:00', 0, 'Subject: Maundy Thursday\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nWhen: Apr 01, 2026 7:00 PM - Apr 02, 2026 10:00 AM\r\nWhere: Brgy Hall\r\nDetails: Mag gather tanan residents\r\n\r\n- System Administrator', 1, 1, 10, 'scheduled', '2026-04-01 18:32:32', NULL),
(21, 6, 'Team building', 'meeting', NULL, 'Vista Edna Resort', '2026-04-03 21:15:00', '2026-04-05 12:00:00', 0, 'Subject: Team building\r\nTo: Kate Bishop\r\nWhen: Apr 03, 2026 9:15 PM - Apr 05, 2026 12:00 PM\r\nWhere: Vista Edna Resort\r\n\r\nAgenda:\r\n1. Barangay improvement\r\n2. Others\r\n\r\n- Pretche Orquillas', 1, 1, 60, 'scheduled', '2026-04-01 21:07:18', '2026-04-01 21:08:52'),
(22, 5, 'Defense', 'meeting', NULL, 'Brgy Session Hall', '2026-04-13 15:05:00', '2026-04-14 06:00:00', 0, 'Subject: Defense\r\nTo: Carrie Naig, Jhonaris Mariscal, Kate Bishop, Maria Makiling, Pretche Orquillas\r\nDate: Apr 13, 2026 - Apr 14, 2026\r\nTime: Apr 13, 2026 3:05 PM - Apr 14, 2026 6:00 AM\r\nLocation: Brgy Session Hall\r\n\r\n- System Admin', 1, 1, 30, 'scheduled', '2026-04-13 15:00:20', '2026-04-13 15:00:43'),
(23, 5, 'Defense-Day 3', 'meeting', 'Final Oral Defense-4th Year Students', 'Brgy Session Hall', '2026-04-14 07:30:00', '2026-04-17 12:00:00', 0, 'Subject: Defense-Day 3\r\nTo: Kate Bishop\r\nDate: Apr 14, 2026 - Apr 17, 2026\r\nTime: Apr 14, 2026 7:30 AM - Apr 17, 2026 12:00 PM\r\nLocation: Brgy Session Hall\r\nDetails: Final Oral Defense-4th Year Students\r\n\r\n- System Admin', 1, 1, 15, 'scheduled', '2026-04-14 06:50:06', NULL),
(24, 6, 'Special meeting', 'meeting', 'Afternoon snacks prepare', 'Brgy Hall', '2026-04-14 07:35:00', '2026-04-14 17:00:00', 0, 'Subject: Special meeting\r\nTo: Leonardo Caprio\r\nDate: Apr 14, 2026\r\nTime: 7:35 AM - 5:00 PM\r\nLocation: Brgy Hall\r\nDetails: Afternoon snacks prepare\r\n\r\n- Pretche Orquillas', 1, 1, 60, 'scheduled', '2026-04-14 07:29:53', NULL),
(25, 5, 'Defense', 'meeting', 'Final Oral Defense', 'Comlab', '2026-04-14 14:26:00', '2026-04-17 15:26:00', 0, 'Subject: Defense\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nDate: Apr 14, 2026 - Apr 17, 2026\r\nTime: Apr 14, 2026 2:26 PM - Apr 17, 2026 3:26 PM\r\nLocation: Comlab\r\nDetails: Final Oral Defense\r\n\r\n- System Admin', 1, 1, 60, 'scheduled', '2026-04-14 14:18:24', NULL),
(26, 5, 'Special Meeting', 'meeting', 'Important matters to be discussed', 'Barangay Session Hall', '2026-04-29 08:00:00', '2026-04-29 12:00:00', 0, 'Subject: Special Meeting\r\nTo: Kate Bishop, Leonardo Caprio, Maria Makiling\r\nDate: Apr 29, 2026\r\nTime: 8:00 AM - 12:00 PM\r\nLocation: Barangay Session Hall\r\nDetails: Important matters to be discussed\r\n\r\n- System Admin', 1, 1, 1440, 'scheduled', '2026-04-25 00:07:45', NULL),
(27, 5, 'Meeting for the upcoming alumni', 'meeting', 'Matters to be discussed', 'Barangay Gym', '2026-04-29 09:00:00', '2026-04-29 10:00:00', 0, 'Subject: Meeting for the upcoming alumni\r\nTo: SK Official, Jhonaris Mariscal, Pretche Orquillas\r\nDate: Apr 29, 2026\r\nTime: 9:00 AM - 10:00 AM\r\nLocation: Barangay Gym\r\nDetails: Matters to be discussed\r\n\r\n- System Admin', 1, 1, 60, 'scheduled', '2026-04-25 00:16:09', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `schedule_participants`
--

CREATE TABLE `schedule_participants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `schedule_event_id` bigint(20) UNSIGNED NOT NULL,
  `contact_id` bigint(20) UNSIGNED NOT NULL,
  `role_in_event` varchar(100) DEFAULT NULL,
  `attendance` enum('pending','present','absent','excused') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schedule_participants`
--

INSERT INTO `schedule_participants` (`id`, `schedule_event_id`, `contact_id`, `role_in_event`, `attendance`, `created_at`) VALUES
(1, 26, 3, NULL, 'pending', '2026-04-25 00:07:45'),
(2, 26, 11, NULL, 'pending', '2026-04-25 00:07:45'),
(3, 26, 6, NULL, 'pending', '2026-04-25 00:07:45'),
(4, 27, 10, NULL, 'pending', '2026-04-25 00:16:09'),
(5, 27, 8, NULL, 'pending', '2026-04-25 00:16:09'),
(6, 27, 9, NULL, 'pending', '2026-04-25 00:16:09');

-- --------------------------------------------------------

--
-- Table structure for table `sms_outbox`
--

CREATE TABLE `sms_outbox` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `schedule_event_id` bigint(20) UNSIGNED DEFAULT NULL,
  `contact_id` bigint(20) UNSIGNED DEFAULT NULL,
  `to_mobile` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `send_at` datetime NOT NULL,
  `send_type` enum('immediate','auto') NOT NULL DEFAULT 'immediate',
  `status` enum('queued','sent','failed','cancelled') NOT NULL DEFAULT 'queued',
  `provider` varchar(80) DEFAULT NULL,
  `provider_message_id` varchar(120) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `queued_at` datetime NOT NULL DEFAULT current_timestamp(),
  `sent_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms_outbox`
--

INSERT INTO `sms_outbox` (`id`, `schedule_event_id`, `contact_id`, `to_mobile`, `message`, `send_at`, `send_type`, `status`, `provider`, `provider_message_id`, `error_message`, `queued_at`, `sent_at`) VALUES
(7, 13, NULL, '09952387215', 'Subject: asdad\r\nTo: barangay kagawad\r\nWhen: Mar 02, 2026 8:51 PM - 9:51 PM\r\n\r\nAgenda:\r\n1. clean\r\n\r\n- admin1', '2026-03-02 13:42:22', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-02 20:42:22', '2026-03-02 23:10:06'),
(8, 13, 6, '098657567567', 'Subject: asdad\r\nTo: barangay kagawad\r\nWhen: Mar 02, 2026 8:51 PM - 9:51 PM\r\n\r\nAgenda:\r\n1. clean\r\n\r\n- admin1', '2026-03-02 13:42:22', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-02 20:42:22', '2026-03-02 23:10:07'),
(9, 13, NULL, '+639483933934', 'Subject: asdad\r\nTo: delven\r\nWhen: Mar 03, 2026 3:51 AM - 4:51 AM\r\nWhere: balay\r\nDetails: des\r\n\r\nAgenda:\r\n1. clean\r\n2. eat\r\n\r\n- admin1', '2026-03-02 16:21:03', 'immediate', 'sent', NULL, NULL, NULL, '2026-03-02 23:21:04', NULL),
(11, 13, NULL, '+639483933934', 'Subject: asdad\nTo: delven\nWhen: Mar 03, 2026 10:51 AM - 11:51 AM\nWhere: balay\n\nAgenda:\n1. clean\n2. eat\n\n- admin1', '2026-03-02 16:22:22', 'immediate', 'sent', NULL, NULL, NULL, '2026-03-02 23:22:24', NULL),
(12, 13, NULL, '+639483933934', 'Subject: asdad\r\nTo: delven\r\nWhen: Mar 03, 2026 10:51 AM - 11:51 AM\r\nWhere: balay\r\n\r\nAgenda:\r\n1. clean\r\n2. eat\r\n\r\n- admin1', '2026-03-03 10:45:00', 'auto', 'sent', NULL, NULL, NULL, '2026-03-02 23:22:24', '2026-03-03 19:26:02'),
(13, 14, NULL, '+639483933934', 'Subject: pahina\r\nTo: delven\r\nWhen: Mar 02, 2026 11:39 PM - Mar 03, 2026 12:39 AM\r\nWhere: purok 3 soriano\r\nDetails: please bring guna and sako\r\n\r\nAgenda:\r\n1. pahina\r\n2. meeting\r\n3. snack\r\n\r\n- admin1', '2026-03-02 16:31:05', 'immediate', 'sent', NULL, NULL, NULL, '2026-03-02 23:31:06', NULL),
(14, 15, NULL, '+639483933934', 'Subject: meeting\r\nTo: delven\r\nWhen: Mar 02, 2026 11:45 PM - Mar 03, 2026 12:45 AM\r\nWhere: brgy. hall\r\nDetails: bring notebook\r\n\r\nAgenda:\r\n1. basketball\r\n2. palaro\r\n3. clean-up drive\r\n\r\n- admin1', '2026-03-02 16:36:35', 'immediate', 'sent', NULL, NULL, NULL, '2026-03-02 23:36:36', NULL),
(15, 14, NULL, '+639483933934', 'Subject: pahina\r\nTo: delven\r\nWhen: Mar 03, 2026 6:39 AM - 7:39 AM\r\nWhere: purok 3 soriano\r\n\r\nAgenda:\r\n1. pahina (done)\r\n2. meeting (done)\r\n3. snack (done)\r\n\r\n- admin1', '2026-03-08 04:56:33', 'immediate', 'sent', NULL, NULL, NULL, '2026-03-08 11:56:34', NULL),
(16, 15, NULL, '+639483933934', 'Subject: meeting\r\nTo: delven\r\nWhen: Mar 03, 2026 6:45 AM - 7:45 AM\r\nWhere: brgy. hall\r\n\r\nAgenda:\r\n1. basketball\r\n2. palaro\r\n3. clean-up drive\r\n\r\n- admin1', '2026-03-08 14:32:00', 'immediate', 'sent', NULL, NULL, NULL, '2026-03-08 21:32:01', NULL),
(17, 14, NULL, '+639483933934', 'Subject: pahina\r\nTo: delven\r\nWhen: Mar 03, 2026 1:39 PM - 2:39 PM\r\nWhere: purok 3 soriano\r\n\r\nAgenda:\r\n1. pahina (done)\r\n2. meeting (done)\r\n3. snack (done)\r\n\r\n- admin1', '2026-03-08 14:38:49', 'immediate', 'cancelled', NULL, NULL, NULL, '2026-03-08 21:38:49', NULL),
(18, 16, 3, '09815906449', 'Subject: Project presentation.\r\nTo: barangay official\r\nWhen: Mar 30, 2026 7:30 AM - 10:30 AM\r\nWhere: Online (Work from Home)\r\nDetails: Prepare your presentations for evaluation.\r\n\r\nAgenda:\r\n1. Project Approval\r\n\r\n- System Administrator', '2026-03-21 08:08:57', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-21 15:08:58', NULL),
(20, 16, 7, '99999999', 'Subject: Project presentation.\r\nTo: barangay official\r\nWhen: Mar 30, 2026 7:30 AM - 10:30 AM\r\nWhere: Online (Work from Home)\r\nDetails: Prepare your presentations for evaluation.\r\n\r\nAgenda:\r\n1. Project Approval\r\n\r\n- System Administrator', '2026-03-21 08:08:57', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-21 15:08:58', NULL),
(22, 17, 3, '09815906449', 'Subject: Alumni\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nWhen: Mar 21, 2026 7:02 PM - 10:00 PM\r\nWhere: Barangay Gymnasium\r\nDetails: Alumni 2026\r\n\r\n- Pretche Orquillas', '2026-03-21 11:53:26', 'immediate', 'cancelled', NULL, NULL, NULL, '2026-03-21 18:53:26', NULL),
(23, 17, 6, '098657567567', 'Subject: Alumni\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nWhen: Mar 21, 2026 7:02 PM - 10:00 PM\r\nWhere: Barangay Gymnasium\r\nDetails: Alumni 2026\r\n\r\n- Pretche Orquillas', '2026-03-21 11:53:26', 'immediate', 'cancelled', NULL, NULL, NULL, '2026-03-21 18:53:26', NULL),
(24, 17, 7, '99999999', 'Subject: Alumni\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nWhen: Mar 21, 2026 7:02 PM - 10:00 PM\r\nWhere: Barangay Gymnasium\r\nDetails: Alumni 2026\r\n\r\n- Pretche Orquillas', '2026-03-21 11:53:26', 'immediate', 'cancelled', NULL, NULL, NULL, '2026-03-21 18:53:26', NULL),
(25, 17, 8, '09878765432', 'Subject: Alumni\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nWhen: Mar 21, 2026 7:02 PM - 10:00 PM\r\nWhere: Barangay Gymnasium\r\nDetails: Alumni 2026\r\n\r\n- Pretche Orquillas', '2026-03-21 11:53:26', 'immediate', 'cancelled', NULL, NULL, NULL, '2026-03-21 18:53:26', NULL),
(26, 18, 8, '09878765432', 'Subject: Project Testing\r\nTo: SK Official, Carrie Naig\r\nWhen: Mar 23, 2026 6:00 AM - 10:33 AM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\nDetails: Project demonstration and feedback gathering.\r\n\r\nAgenda:\r\n1. Project Demonstration\r\n2. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-22 16:11:58', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-22 23:11:59', NULL),
(27, 18, 8, '09878765432', 'Subject: Project Testing\r\nTo: SK Official, Carrie Naig\r\nWhen: Mar 23, 2026 6:00 AM - 10:33 AM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\nDetails: Project demonstration and feedback gathering.\r\n\r\nAgenda:\r\n1. Project Demonstration\r\n2. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 05:30:00', 'auto', 'failed', NULL, NULL, NULL, '2026-03-22 23:11:59', '2026-03-23 07:45:36'),
(28, 18, 9, '09455620159', 'Subject: Project Testing\r\nTo: SK Official, Carrie Naig\r\nWhen: Mar 23, 2026 6:00 AM - 10:33 AM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\nDetails: Project demonstration and feedback gathering.\r\n\r\nAgenda:\r\n1. Project Demonstration\r\n2. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-22 16:11:58', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-22 23:12:00', NULL),
(29, 18, 9, '09455620159', 'Subject: Project Testing\r\nTo: SK Official, Carrie Naig\r\nWhen: Mar 23, 2026 6:00 AM - 10:33 AM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\nDetails: Project demonstration and feedback gathering.\r\n\r\nAgenda:\r\n1. Project Demonstration\r\n2. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 05:30:00', 'auto', 'failed', NULL, NULL, NULL, '2026-03-22 23:12:00', '2026-03-23 07:45:36'),
(30, 18, 8, '09878765432', 'Subject: Project Testing\r\nTo: SK Official, Carrie Naig\r\nWhen: Mar 23, 2026 7:50 AM - 5:33 PM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Project Demonstration\r\n2. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 00:47:22', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-23 07:47:22', NULL),
(31, 18, 8, '09878765432', 'Subject: Project Testing\r\nTo: SK Official, Carrie Naig\r\nWhen: Mar 23, 2026 7:50 AM - 5:33 PM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Project Demonstration\r\n2. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 07:40:00', 'auto', 'failed', NULL, NULL, NULL, '2026-03-23 07:47:22', '2026-03-23 07:47:29'),
(32, 18, 9, '09455620159', 'Subject: Project Testing\r\nTo: SK Official, Carrie Naig\r\nWhen: Mar 23, 2026 7:50 AM - 5:33 PM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Project Demonstration\r\n2. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 00:47:22', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-23 07:47:23', NULL),
(33, 18, 9, '09455620159', 'Subject: Project Testing\r\nTo: SK Official, Carrie Naig\r\nWhen: Mar 23, 2026 7:50 AM - 5:33 PM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Project Demonstration\r\n2. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 07:40:00', 'auto', 'failed', NULL, NULL, NULL, '2026-03-23 07:47:23', '2026-03-23 07:47:29'),
(34, 18, 9, '09455620159', 'Subject: Project Testing\r\nTo: SK Official, Carrie Naig\r\nWhen: Mar 23, 2026 7:50 AM - 5:33 PM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Project Demonstration\r\n2. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 07:47:50', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-23 07:47:50', NULL),
(35, 18, 9, '09455620159', 'Subject: Project Testing\r\nTo: SK Official, Carrie Naig\r\nWhen: Mar 23, 2026 7:50 AM - 5:33 PM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Project Demonstration\r\n2. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 07:47:53', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-23 07:47:53', NULL),
(36, 18, 8, '09878765432', 'Subject: Project Testing\r\nTo: SK Official, Carrie Naig\r\nWhen: Mar 23, 2026 7:52 AM - 10:30 AM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 00:51:11', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-23 07:51:11', NULL),
(37, 18, 8, '09878765432', 'Subject: Project Testing\r\nTo: SK Official, Carrie Naig\r\nWhen: Mar 23, 2026 7:52 AM - 10:30 AM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 07:42:00', 'auto', 'failed', NULL, NULL, NULL, '2026-03-23 07:51:11', '2026-03-23 07:51:18'),
(38, 18, 9, '09455620159', 'Subject: Project Testing\r\nTo: SK Official, Carrie Naig\r\nWhen: Mar 23, 2026 7:52 AM - 10:30 AM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 00:51:11', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-23 07:51:12', NULL),
(39, 18, 9, '09455620159', 'Subject: Project Testing\r\nTo: SK Official, Carrie Naig\r\nWhen: Mar 23, 2026 7:52 AM - 10:30 AM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 07:42:00', 'auto', 'failed', NULL, NULL, NULL, '2026-03-23 07:51:12', '2026-03-23 07:51:19'),
(40, 18, 9, '09455620159', 'Subject: Project Testing\r\nTo: SK Official, Carrie Naig\r\nWhen: Mar 23, 2026 7:52 AM - 10:30 AM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 07:52:01', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-23 07:52:01', NULL),
(41, 18, 8, '09878765432', 'Subject: Project Testing\r\nTo: Carrie Naig, Pretche Orquillas\r\nWhen: Mar 23, 2026 7:56 AM - 10:30 AM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 00:55:09', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-23 07:55:10', NULL),
(42, 18, 8, '09878765432', 'Subject: Project Testing\r\nTo: Carrie Naig, Pretche Orquillas\r\nWhen: Mar 23, 2026 7:56 AM - 10:30 AM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 07:46:00', 'auto', 'failed', NULL, NULL, NULL, '2026-03-23 07:55:10', '2026-03-23 07:55:14'),
(43, 18, 9, '09455620159', 'Subject: Project Testing\r\nTo: Carrie Naig, Pretche Orquillas\r\nWhen: Mar 23, 2026 7:56 AM - 10:30 AM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 00:55:09', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-23 07:55:10', NULL),
(44, 18, 9, '09455620159', 'Subject: Project Testing\r\nTo: Carrie Naig, Pretche Orquillas\r\nWhen: Mar 23, 2026 7:56 AM - 10:30 AM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 07:46:00', 'auto', 'failed', NULL, NULL, NULL, '2026-03-23 07:55:10', '2026-03-23 07:55:15'),
(45, 18, 8, '09878765432', 'Subject: Project Testing\r\nTo: Carrie Naig, Pretche Orquillas\r\nWhen: Mar 23, 2026 7:56 AM - 10:30 AM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 07:55:54', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-23 07:55:54', NULL),
(46, 18, 8, '09878765432', 'Subject: Project Testing\r\nTo: SK Official, Carrie Naig\r\nWhen: Mar 23, 2026 7:58 AM - 10:30 AM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 00:56:49', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-23 07:56:49', NULL),
(47, 18, 9, '09455620159', 'Subject: Project Testing\r\nTo: SK Official, Carrie Naig\r\nWhen: Mar 23, 2026 7:58 AM - 10:30 AM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 00:56:49', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-23 07:56:50', NULL),
(48, 18, 9, '09455620159', 'Subject: Project Testing\r\nTo: SK Official, Carrie Naig\r\nWhen: Mar 23, 2026 7:58 AM - 10:30 AM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 07:58:09', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-23 07:58:09', NULL),
(49, 18, 9, '09455620159', 'Subject: Project Testing\r\nTo: SK Official, Carrie Naig\r\nWhen: Mar 23, 2026 7:58 AM - 10:30 AM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 07:58:12', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-23 07:58:12', NULL),
(50, 18, 8, '09878765432', 'Subject: Project Testing\r\nTo: Carrie Naig, Pretche Orquillas\r\nWhen: Mar 23, 2026 8:19 AM - 10:30 AM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 01:18:56', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-23 08:18:57', NULL),
(51, 18, 8, '09878765432', 'Subject: Project Testing\r\nTo: Carrie Naig, Pretche Orquillas\r\nWhen: Mar 23, 2026 8:19 AM - 10:30 AM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 07:49:00', 'auto', 'failed', NULL, NULL, NULL, '2026-03-23 08:18:57', '2026-03-23 08:19:44'),
(52, 18, 9, '09455620159', 'Subject: Project Testing\r\nTo: Carrie Naig, Pretche Orquillas\r\nWhen: Mar 23, 2026 8:19 AM - 10:30 AM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 01:18:56', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-23 08:18:58', NULL),
(53, 18, 9, '09455620159', 'Subject: Project Testing\r\nTo: Carrie Naig, Pretche Orquillas\r\nWhen: Mar 23, 2026 8:19 AM - 10:30 AM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 07:49:00', 'auto', 'failed', NULL, NULL, NULL, '2026-03-23 08:18:58', '2026-03-23 08:19:45'),
(54, 18, 9, '09455620159', 'Subject: Project Testing\r\nTo: Carrie Naig, Pretche Orquillas\r\nWhen: Mar 23, 2026 8:19 AM - 10:30 AM\r\nWhere: Barangay Sto. Rosario, Magallanes\r\n\r\nAgenda:\r\n1. Suggestions and feedbacks\r\n\r\n- Pretche Orquillas', '2026-03-23 08:19:34', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-23 08:19:34', NULL),
(55, 16, 8, '09878765432', 'Subject: Project presentation.\r\nTo: SK Official, Carrie Naig\r\nWhen: Mar 30, 2026 8:21 AM - 10:30 AM\r\nWhere: Online (Work from Home)\r\n\r\nAgenda:\r\n1. Project Approval\r\n\r\n- Pretche Orquillas', '2026-03-23 01:20:26', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-23 08:20:27', NULL),
(56, 16, 9, '09455620159', 'Subject: Project presentation.\r\nTo: SK Official, Carrie Naig\r\nWhen: Mar 30, 2026 8:21 AM - 10:30 AM\r\nWhere: Online (Work from Home)\r\n\r\nAgenda:\r\n1. Project Approval\r\n\r\n- Pretche Orquillas', '2026-03-23 01:20:26', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-23 08:20:27', NULL),
(57, 16, 8, '09878765432', 'Subject: Project presentation.\r\nTo: SK Official, Carrie Naig\r\nWhen: Mar 23, 2026 8:25 AM - 10:30 AM\r\nWhere: Online (Work from Home)\r\n\r\nAgenda:\r\n1. Project Approval\r\n\r\n- Pretche Orquillas', '2026-03-23 01:22:25', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-23 08:22:25', NULL),
(58, 16, 9, '09455620159', 'Subject: Project presentation.\r\nTo: SK Official, Carrie Naig\r\nWhen: Mar 23, 2026 8:25 AM - 10:30 AM\r\nWhere: Online (Work from Home)\r\n\r\nAgenda:\r\n1. Project Approval\r\n\r\n- Pretche Orquillas', '2026-03-23 01:22:25', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-23 08:22:25', NULL),
(59, 19, 3, '09815906449', 'Subject: Evalution of purok per barangay\r\nTo: Barangay Kagawad, Barangay Official\r\nWhen: Mar 29, 2026 7:00 AM - Mar 30, 2026 5:00 PM\r\nWhere: Barangay Sto. Rosario\r\n\r\n- System Administrator', '2026-03-24 02:43:10', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-24 09:43:12', NULL),
(60, 19, 6, '098657567567', 'Subject: Evalution of purok per barangay\r\nTo: Barangay Kagawad, Barangay Official\r\nWhen: Mar 29, 2026 7:00 AM - Mar 30, 2026 5:00 PM\r\nWhere: Barangay Sto. Rosario\r\n\r\n- System Administrator', '2026-03-24 02:43:10', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-24 09:43:13', NULL),
(61, 19, 7, '99999999', 'Subject: Evalution of purok per barangay\r\nTo: Barangay Kagawad, Barangay Official\r\nWhen: Mar 29, 2026 7:00 AM - Mar 30, 2026 5:00 PM\r\nWhere: Barangay Sto. Rosario\r\n\r\n- System Administrator', '2026-03-24 02:43:10', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-24 09:43:13', NULL),
(62, 20, 3, '09815906449', 'Subject: Maundy Thursday\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nWhen: Apr 01, 2026 7:00 PM - Apr 02, 2026 10:00 AM\r\nWhere: Brgy Hall\r\nDetails: Mag gather tanan residents\r\n\r\n- System Administrator', '2026-04-01 12:32:32', 'immediate', 'failed', NULL, NULL, NULL, '2026-04-01 18:32:33', NULL),
(63, 20, 3, '09815906449', 'Subject: Maundy Thursday\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nWhen: Apr 01, 2026 7:00 PM - Apr 02, 2026 10:00 AM\r\nWhere: Brgy Hall\r\nDetails: Mag gather tanan residents\r\n\r\n- System Administrator', '2026-04-01 18:50:00', 'auto', 'failed', NULL, NULL, NULL, '2026-04-01 18:32:33', '2026-04-01 20:55:59'),
(64, 20, 6, '098657567567', 'Subject: Maundy Thursday\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nWhen: Apr 01, 2026 7:00 PM - Apr 02, 2026 10:00 AM\r\nWhere: Brgy Hall\r\nDetails: Mag gather tanan residents\r\n\r\n- System Administrator', '2026-04-01 12:32:32', 'immediate', 'failed', NULL, NULL, NULL, '2026-04-01 18:32:34', NULL),
(65, 20, 6, '098657567567', 'Subject: Maundy Thursday\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nWhen: Apr 01, 2026 7:00 PM - Apr 02, 2026 10:00 AM\r\nWhere: Brgy Hall\r\nDetails: Mag gather tanan residents\r\n\r\n- System Administrator', '2026-04-01 18:50:00', 'auto', 'failed', NULL, NULL, NULL, '2026-04-01 18:32:34', '2026-04-01 20:56:00'),
(66, 20, 7, '99999999', 'Subject: Maundy Thursday\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nWhen: Apr 01, 2026 7:00 PM - Apr 02, 2026 10:00 AM\r\nWhere: Brgy Hall\r\nDetails: Mag gather tanan residents\r\n\r\n- System Administrator', '2026-04-01 12:32:32', 'immediate', 'failed', NULL, NULL, NULL, '2026-04-01 18:32:35', NULL),
(67, 20, 7, '99999999', 'Subject: Maundy Thursday\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nWhen: Apr 01, 2026 7:00 PM - Apr 02, 2026 10:00 AM\r\nWhere: Brgy Hall\r\nDetails: Mag gather tanan residents\r\n\r\n- System Administrator', '2026-04-01 18:50:00', 'auto', 'failed', NULL, NULL, NULL, '2026-04-01 18:32:35', '2026-04-01 20:56:01'),
(68, 20, 8, '09878765432', 'Subject: Maundy Thursday\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nWhen: Apr 01, 2026 7:00 PM - Apr 02, 2026 10:00 AM\r\nWhere: Brgy Hall\r\nDetails: Mag gather tanan residents\r\n\r\n- System Administrator', '2026-04-01 12:32:32', 'immediate', 'failed', NULL, NULL, NULL, '2026-04-01 18:32:35', NULL),
(69, 20, 8, '09878765432', 'Subject: Maundy Thursday\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nWhen: Apr 01, 2026 7:00 PM - Apr 02, 2026 10:00 AM\r\nWhere: Brgy Hall\r\nDetails: Mag gather tanan residents\r\n\r\n- System Administrator', '2026-04-01 18:50:00', 'auto', 'failed', NULL, NULL, NULL, '2026-04-01 18:32:35', '2026-04-01 20:56:02'),
(70, 20, 9, '09455620159', 'Subject: Maundy Thursday\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nWhen: Apr 01, 2026 7:00 PM - Apr 02, 2026 10:00 AM\r\nWhere: Brgy Hall\r\nDetails: Mag gather tanan residents\r\n\r\n- System Administrator', '2026-04-01 12:32:32', 'immediate', 'failed', NULL, NULL, NULL, '2026-04-01 18:32:36', NULL),
(71, 20, 9, '09455620159', 'Subject: Maundy Thursday\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nWhen: Apr 01, 2026 7:00 PM - Apr 02, 2026 10:00 AM\r\nWhere: Brgy Hall\r\nDetails: Mag gather tanan residents\r\n\r\n- System Administrator', '2026-04-01 18:50:00', 'auto', 'failed', NULL, NULL, NULL, '2026-04-01 18:32:36', '2026-04-01 20:56:02'),
(72, 20, 3, '09815906449', 'Subject: Maundy Thursday\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nWhen: Apr 01, 2026 7:00 PM - Apr 02, 2026 10:00 AM\r\nWhere: Brgy Hall\r\nDetails: Mag gather tanan residents\r\n\r\n- System Administrator', '2026-04-01 21:00:31', 'immediate', 'failed', NULL, NULL, NULL, '2026-04-01 21:00:31', NULL),
(73, 21, 3, '09815906449', 'Subject: Team building\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nWhen: Apr 03, 2026 9:00 AM - Apr 05, 2026 12:00 PM\r\nWhere: Vista Edna Resort\r\nDetails: To be discussed\r\n\r\nAgenda:\r\n1. Barangay improvement\r\n2. Others\r\n\r\n- Pretche Orquillas', '2026-04-01 21:07:19', 'immediate', 'failed', NULL, NULL, NULL, '2026-04-01 21:07:19', NULL),
(75, 21, 6, '098657567567', 'Subject: Team building\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nWhen: Apr 03, 2026 9:00 AM - Apr 05, 2026 12:00 PM\r\nWhere: Vista Edna Resort\r\nDetails: To be discussed\r\n\r\nAgenda:\r\n1. Barangay improvement\r\n2. Others\r\n\r\n- Pretche Orquillas', '2026-04-01 21:07:19', 'immediate', 'failed', NULL, NULL, NULL, '2026-04-01 21:07:19', NULL),
(77, 21, 7, '99999999', 'Subject: Team building\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nWhen: Apr 03, 2026 9:00 AM - Apr 05, 2026 12:00 PM\r\nWhere: Vista Edna Resort\r\nDetails: To be discussed\r\n\r\nAgenda:\r\n1. Barangay improvement\r\n2. Others\r\n\r\n- Pretche Orquillas', '2026-04-01 21:07:19', 'immediate', 'failed', NULL, NULL, NULL, '2026-04-01 21:07:20', NULL),
(79, 21, 8, '09878765432', 'Subject: Team building\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nWhen: Apr 03, 2026 9:00 AM - Apr 05, 2026 12:00 PM\r\nWhere: Vista Edna Resort\r\nDetails: To be discussed\r\n\r\nAgenda:\r\n1. Barangay improvement\r\n2. Others\r\n\r\n- Pretche Orquillas', '2026-04-01 21:07:19', 'immediate', 'failed', NULL, NULL, NULL, '2026-04-01 21:07:21', NULL),
(81, 21, 9, '09455620159', 'Subject: Team building\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nWhen: Apr 03, 2026 9:00 AM - Apr 05, 2026 12:00 PM\r\nWhere: Vista Edna Resort\r\nDetails: To be discussed\r\n\r\nAgenda:\r\n1. Barangay improvement\r\n2. Others\r\n\r\n- Pretche Orquillas', '2026-04-01 21:07:19', 'immediate', 'failed', NULL, NULL, NULL, '2026-04-01 21:07:21', NULL),
(83, 21, 3, '09815906449', 'Subject: Team building\r\nTo: Kate Bishop\r\nWhen: Apr 03, 2026 9:15 PM - Apr 05, 2026 12:00 PM\r\nWhere: Vista Edna Resort\r\n\r\nAgenda:\r\n1. Barangay improvement\r\n2. Others\r\n\r\n- Pretche Orquillas', '2026-04-01 21:08:52', 'immediate', 'failed', NULL, NULL, NULL, '2026-04-01 21:08:53', NULL),
(84, 21, 3, '09815906449', 'Subject: Team building\r\nTo: Kate Bishop\r\nWhen: Apr 03, 2026 9:15 PM - Apr 05, 2026 12:00 PM\r\nWhere: Vista Edna Resort\r\n\r\nAgenda:\r\n1. Barangay improvement\r\n2. Others\r\n\r\n- Pretche Orquillas', '2026-04-03 20:15:00', 'auto', 'failed', NULL, NULL, NULL, '2026-04-01 21:08:53', '2026-04-03 21:51:04'),
(85, 22, 3, '09815906449', 'Subject: Defense\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nDate: Apr 13, 2026 - Apr 14, 2026\r\nTime: Apr 13, 2026 3:05 PM - Apr 14, 2026 6:00 AM\r\nLocation: Brgy Session Hall\r\nDetails: Final Oral Defense\r\n\r\n- System Admin', '2026-04-13 15:00:20', 'immediate', 'sent', NULL, NULL, NULL, '2026-04-13 15:00:21', NULL),
(86, 22, 6, '09865756756', 'Subject: Defense\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nDate: Apr 13, 2026 - Apr 14, 2026\r\nTime: Apr 13, 2026 3:05 PM - Apr 14, 2026 6:00 AM\r\nLocation: Brgy Session Hall\r\nDetails: Final Oral Defense\r\n\r\n- System Admin', '2026-04-13 15:00:20', 'immediate', 'sent', NULL, NULL, NULL, '2026-04-13 15:00:21', NULL),
(87, 22, 8, '09503648981', 'Subject: Defense\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nDate: Apr 13, 2026 - Apr 14, 2026\r\nTime: Apr 13, 2026 3:05 PM - Apr 14, 2026 6:00 AM\r\nLocation: Brgy Session Hall\r\nDetails: Final Oral Defense\r\n\r\n- System Admin', '2026-04-13 15:00:20', 'immediate', 'sent', NULL, NULL, NULL, '2026-04-13 15:00:22', NULL),
(88, 22, 9, '09455620159', 'Subject: Defense\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nDate: Apr 13, 2026 - Apr 14, 2026\r\nTime: Apr 13, 2026 3:05 PM - Apr 14, 2026 6:00 AM\r\nLocation: Brgy Session Hall\r\nDetails: Final Oral Defense\r\n\r\n- System Admin', '2026-04-13 15:00:20', 'immediate', 'sent', NULL, NULL, NULL, '2026-04-13 15:00:23', NULL),
(89, 22, 10, '09915348397', 'Subject: Defense\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nDate: Apr 13, 2026 - Apr 14, 2026\r\nTime: Apr 13, 2026 3:05 PM - Apr 14, 2026 6:00 AM\r\nLocation: Brgy Session Hall\r\nDetails: Final Oral Defense\r\n\r\n- System Admin', '2026-04-13 15:00:20', 'immediate', 'sent', NULL, NULL, NULL, '2026-04-13 15:00:23', NULL),
(90, 22, 3, '09815906449', 'Subject: Defense\r\nTo: Carrie Naig, Jhonaris Mariscal, Kate Bishop, Maria Makiling, Pretche Orquillas\r\nDate: Apr 13, 2026 - Apr 14, 2026\r\nTime: Apr 13, 2026 3:05 PM - Apr 14, 2026 6:00 AM\r\nLocation: Brgy Session Hall\r\n\r\n- System Admin', '2026-04-13 15:00:43', 'immediate', 'cancelled', NULL, NULL, NULL, '2026-04-13 15:00:43', NULL),
(91, 22, 6, '09865756756', 'Subject: Defense\r\nTo: Carrie Naig, Jhonaris Mariscal, Kate Bishop, Maria Makiling, Pretche Orquillas\r\nDate: Apr 13, 2026 - Apr 14, 2026\r\nTime: Apr 13, 2026 3:05 PM - Apr 14, 2026 6:00 AM\r\nLocation: Brgy Session Hall\r\n\r\n- System Admin', '2026-04-13 15:00:43', 'immediate', 'cancelled', NULL, NULL, NULL, '2026-04-13 15:00:43', NULL),
(92, 22, 8, '09503648981', 'Subject: Defense\r\nTo: Carrie Naig, Jhonaris Mariscal, Kate Bishop, Maria Makiling, Pretche Orquillas\r\nDate: Apr 13, 2026 - Apr 14, 2026\r\nTime: Apr 13, 2026 3:05 PM - Apr 14, 2026 6:00 AM\r\nLocation: Brgy Session Hall\r\n\r\n- System Admin', '2026-04-13 15:00:43', 'immediate', 'cancelled', NULL, NULL, NULL, '2026-04-13 15:00:43', NULL),
(93, 22, 9, '09455620159', 'Subject: Defense\r\nTo: Carrie Naig, Jhonaris Mariscal, Kate Bishop, Maria Makiling, Pretche Orquillas\r\nDate: Apr 13, 2026 - Apr 14, 2026\r\nTime: Apr 13, 2026 3:05 PM - Apr 14, 2026 6:00 AM\r\nLocation: Brgy Session Hall\r\n\r\n- System Admin', '2026-04-13 15:00:43', 'immediate', 'cancelled', NULL, NULL, NULL, '2026-04-13 15:00:43', NULL),
(94, 22, 10, '09915348397', 'Subject: Defense\r\nTo: Carrie Naig, Jhonaris Mariscal, Kate Bishop, Maria Makiling, Pretche Orquillas\r\nDate: Apr 13, 2026 - Apr 14, 2026\r\nTime: Apr 13, 2026 3:05 PM - Apr 14, 2026 6:00 AM\r\nLocation: Brgy Session Hall\r\n\r\n- System Admin', '2026-04-13 15:00:43', 'immediate', 'cancelled', NULL, NULL, NULL, '2026-04-13 15:00:43', NULL),
(95, 23, 3, '09815906449', 'Subject: Defense-Day 3\r\nTo: Kate Bishop\r\nDate: Apr 14, 2026 - Apr 17, 2026\r\nTime: Apr 14, 2026 7:30 AM - Apr 17, 2026 12:00 PM\r\nLocation: Brgy Session Hall\r\nDetails: Final Oral Defense-4th Year Students\r\n\r\n- System Admin', '2026-04-14 06:50:06', 'immediate', 'cancelled', NULL, NULL, NULL, '2026-04-14 06:50:06', NULL),
(96, 23, 3, '09815906449', 'Subject: Defense-Day 3\r\nTo: Kate Bishop\r\nDate: Apr 14, 2026 - Apr 17, 2026\r\nTime: Apr 14, 2026 7:30 AM - Apr 17, 2026 12:00 PM\r\nLocation: Brgy Session Hall\r\nDetails: Final Oral Defense-4th Year Students\r\n\r\n- System Admin', '2026-04-14 07:15:00', 'auto', 'sent', NULL, NULL, NULL, '2026-04-14 06:50:06', '2026-04-14 07:25:46'),
(97, 23, 3, '09815906449', 'Subject: Defense-Day 3\r\nTo: Kate Bishop\r\nDate: Apr 14, 2026 - Apr 17, 2026\r\nTime: Apr 14, 2026 7:30 AM - Apr 17, 2026 12:00 PM\r\nLocation: Brgy Session Hall\r\nDetails: Final Oral Defense-4th Year Students\r\n\r\n- System Admin', '2026-04-14 07:24:15', 'immediate', 'sent', NULL, NULL, NULL, '2026-04-14 07:24:15', '2026-04-14 07:24:15'),
(98, 24, 11, '09500763972', 'Subject: Special meeting\r\nTo: Leonardo Caprio\r\nDate: Apr 14, 2026\r\nTime: 7:35 AM - 5:00 PM\r\nLocation: Brgy Hall\r\nDetails: Afternoon snacks prepare\r\n\r\n- Pretche Orquillas', '2026-04-14 07:29:53', 'immediate', 'sent', NULL, NULL, NULL, '2026-04-14 07:29:54', NULL),
(99, 25, 3, '09815906449', 'Subject: Defense\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nDate: Apr 14, 2026 - Apr 17, 2026\r\nTime: Apr 14, 2026 2:26 PM - Apr 17, 2026 3:26 PM\r\nLocation: Comlab\r\nDetails: Final Oral Defense\r\n\r\n- System Admin', '2026-04-14 14:18:24', 'immediate', 'sent', NULL, NULL, NULL, '2026-04-14 14:18:26', NULL),
(100, 25, 6, '09865756756', 'Subject: Defense\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nDate: Apr 14, 2026 - Apr 17, 2026\r\nTime: Apr 14, 2026 2:26 PM - Apr 17, 2026 3:26 PM\r\nLocation: Comlab\r\nDetails: Final Oral Defense\r\n\r\n- System Admin', '2026-04-14 14:18:24', 'immediate', 'sent', NULL, NULL, NULL, '2026-04-14 14:18:27', NULL),
(101, 25, 8, '09503648981', 'Subject: Defense\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nDate: Apr 14, 2026 - Apr 17, 2026\r\nTime: Apr 14, 2026 2:26 PM - Apr 17, 2026 3:26 PM\r\nLocation: Comlab\r\nDetails: Final Oral Defense\r\n\r\n- System Admin', '2026-04-14 14:18:24', 'immediate', 'sent', NULL, NULL, NULL, '2026-04-14 14:18:28', NULL),
(102, 25, 9, '09455620159', 'Subject: Defense\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nDate: Apr 14, 2026 - Apr 17, 2026\r\nTime: Apr 14, 2026 2:26 PM - Apr 17, 2026 3:26 PM\r\nLocation: Comlab\r\nDetails: Final Oral Defense\r\n\r\n- System Admin', '2026-04-14 14:18:24', 'immediate', 'sent', NULL, NULL, NULL, '2026-04-14 14:18:29', NULL),
(103, 25, 10, '09915348397', 'Subject: Defense\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nDate: Apr 14, 2026 - Apr 17, 2026\r\nTime: Apr 14, 2026 2:26 PM - Apr 17, 2026 3:26 PM\r\nLocation: Comlab\r\nDetails: Final Oral Defense\r\n\r\n- System Admin', '2026-04-14 14:18:24', 'immediate', 'sent', NULL, NULL, NULL, '2026-04-14 14:18:30', NULL),
(104, 25, 11, '09500763972', 'Subject: Defense\r\nTo: Barangay Kagawad, Barangay Official, SK Official\r\nDate: Apr 14, 2026 - Apr 17, 2026\r\nTime: Apr 14, 2026 2:26 PM - Apr 17, 2026 3:26 PM\r\nLocation: Comlab\r\nDetails: Final Oral Defense\r\n\r\n- System Admin', '2026-04-14 14:18:24', 'immediate', 'sent', NULL, NULL, NULL, '2026-04-14 14:18:34', NULL),
(105, 26, 3, '09815906449', 'Subject: Special Meeting\r\nTo: Kate Bishop, Leonardo Caprio, Maria Makiling\r\nDate: Apr 29, 2026\r\nTime: 8:00 AM - 12:00 PM\r\nLocation: Barangay Session Hall\r\nDetails: Important matters to be discussed\r\n\r\n- System Admin', '2026-04-25 00:07:45', 'immediate', 'failed', NULL, NULL, NULL, '2026-04-25 00:07:46', NULL),
(106, 26, 3, '09815906449', 'Subject: Special Meeting\r\nTo: Kate Bishop, Leonardo Caprio, Maria Makiling\r\nDate: Apr 29, 2026\r\nTime: 8:00 AM - 12:00 PM\r\nLocation: Barangay Session Hall\r\nDetails: Important matters to be discussed\r\n\r\n- System Admin', '2026-04-28 08:00:00', 'auto', 'queued', NULL, NULL, NULL, '2026-04-25 00:07:46', NULL),
(107, 26, 6, '09865756756', 'Subject: Special Meeting\r\nTo: Kate Bishop, Leonardo Caprio, Maria Makiling\r\nDate: Apr 29, 2026\r\nTime: 8:00 AM - 12:00 PM\r\nLocation: Barangay Session Hall\r\nDetails: Important matters to be discussed\r\n\r\n- System Admin', '2026-04-25 00:07:45', 'immediate', 'failed', NULL, NULL, NULL, '2026-04-25 00:07:47', NULL),
(108, 26, 6, '09865756756', 'Subject: Special Meeting\r\nTo: Kate Bishop, Leonardo Caprio, Maria Makiling\r\nDate: Apr 29, 2026\r\nTime: 8:00 AM - 12:00 PM\r\nLocation: Barangay Session Hall\r\nDetails: Important matters to be discussed\r\n\r\n- System Admin', '2026-04-28 08:00:00', 'auto', 'queued', NULL, NULL, NULL, '2026-04-25 00:07:47', NULL),
(109, 26, 11, '09500763972', 'Subject: Special Meeting\r\nTo: Kate Bishop, Leonardo Caprio, Maria Makiling\r\nDate: Apr 29, 2026\r\nTime: 8:00 AM - 12:00 PM\r\nLocation: Barangay Session Hall\r\nDetails: Important matters to be discussed\r\n\r\n- System Admin', '2026-04-25 00:07:45', 'immediate', 'failed', NULL, NULL, NULL, '2026-04-25 00:07:48', NULL),
(110, 26, 11, '09500763972', 'Subject: Special Meeting\r\nTo: Kate Bishop, Leonardo Caprio, Maria Makiling\r\nDate: Apr 29, 2026\r\nTime: 8:00 AM - 12:00 PM\r\nLocation: Barangay Session Hall\r\nDetails: Important matters to be discussed\r\n\r\n- System Admin', '2026-04-28 08:00:00', 'auto', 'queued', NULL, NULL, NULL, '2026-04-25 00:07:48', NULL),
(111, 26, 11, '09500763972', 'Subject: Special Meeting\r\nTo: Kate Bishop, Leonardo Caprio, Maria Makiling\r\nDate: Apr 29, 2026\r\nTime: 8:00 AM - 12:00 PM\r\nLocation: Barangay Session Hall\r\nDetails: Important matters to be discussed\r\n\r\n- System Admin', '2026-04-25 00:10:36', 'immediate', 'failed', NULL, NULL, NULL, '2026-04-25 00:10:36', NULL),
(112, 27, 8, '09503648981', 'Subject: Meeting for the upcoming alumni\r\nTo: SK Official, Jhonaris Mariscal, Pretche Orquillas\r\nDate: Apr 29, 2026\r\nTime: 9:00 AM - 10:00 AM\r\nLocation: Barangay Gym\r\nDetails: Matters to be discussed\r\n\r\n- System Admin', '2026-04-25 00:16:09', 'immediate', 'failed', NULL, NULL, NULL, '2026-04-25 00:16:10', NULL),
(113, 27, 8, '09503648981', 'Subject: Meeting for the upcoming alumni\r\nTo: SK Official, Jhonaris Mariscal, Pretche Orquillas\r\nDate: Apr 29, 2026\r\nTime: 9:00 AM - 10:00 AM\r\nLocation: Barangay Gym\r\nDetails: Matters to be discussed\r\n\r\n- System Admin', '2026-04-29 08:00:00', 'auto', 'queued', NULL, NULL, NULL, '2026-04-25 00:16:10', NULL),
(114, 27, 9, '09455620159', 'Subject: Meeting for the upcoming alumni\r\nTo: SK Official, Jhonaris Mariscal, Pretche Orquillas\r\nDate: Apr 29, 2026\r\nTime: 9:00 AM - 10:00 AM\r\nLocation: Barangay Gym\r\nDetails: Matters to be discussed\r\n\r\n- System Admin', '2026-04-25 00:16:09', 'immediate', 'failed', NULL, NULL, NULL, '2026-04-25 00:16:11', NULL),
(115, 27, 9, '09455620159', 'Subject: Meeting for the upcoming alumni\r\nTo: SK Official, Jhonaris Mariscal, Pretche Orquillas\r\nDate: Apr 29, 2026\r\nTime: 9:00 AM - 10:00 AM\r\nLocation: Barangay Gym\r\nDetails: Matters to be discussed\r\n\r\n- System Admin', '2026-04-29 08:00:00', 'auto', 'queued', NULL, NULL, NULL, '2026-04-25 00:16:11', NULL),
(116, 27, 10, '09915348397', 'Subject: Meeting for the upcoming alumni\r\nTo: SK Official, Jhonaris Mariscal, Pretche Orquillas\r\nDate: Apr 29, 2026\r\nTime: 9:00 AM - 10:00 AM\r\nLocation: Barangay Gym\r\nDetails: Matters to be discussed\r\n\r\n- System Admin', '2026-04-25 00:16:09', 'immediate', 'failed', NULL, NULL, NULL, '2026-04-25 00:16:12', NULL),
(117, 27, 10, '09915348397', 'Subject: Meeting for the upcoming alumni\r\nTo: SK Official, Jhonaris Mariscal, Pretche Orquillas\r\nDate: Apr 29, 2026\r\nTime: 9:00 AM - 10:00 AM\r\nLocation: Barangay Gym\r\nDetails: Matters to be discussed\r\n\r\n- System Admin', '2026-04-29 08:00:00', 'auto', 'queued', NULL, NULL, NULL, '2026-04-25 00:16:12', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(64) DEFAULT NULL,
  `remember_token_expires` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `role`, `mobile`, `email`, `profile_picture`, `is_active`, `remember_token`, `remember_token_expires`, `created_at`, `updated_at`) VALUES
(3, 'admin1', '$2y$10$ui3Ha7NrzanzfaXP1koAiuY7UUhCUnScEVBQCHS3f4P2KKyM3wxTq', 'admin1', 'admin', NULL, NULL, 'uploads/avatars/avatar_3_4175b41866a378a6.jpg', 1, NULL, NULL, '2026-02-22 15:28:47', '2026-04-11 23:47:46'),
(4, 'staff', '$2y$10$T6bzDeHsBC.5iWR1nNxctOr.RuyVfI5c8s3qoBpuOS99DlJXKtFOC', 'Staff', 'staff', NULL, NULL, NULL, 1, NULL, NULL, '2026-03-03 21:03:01', '2026-04-03 22:05:16'),
(5, 'admin2026', '$2y$10$9XWZvAw8r5m6UQ/jGgl0GeP3YXrxUUwoqfpSxacwgJOUMzsIdfaru', 'System Admin', 'admin', '09815906449', 'rosielle1129@gmail.com', 'uploads/avatars/avatar_5_3d7498db84682079.jpg', 1, NULL, NULL, '2026-03-21 15:06:23', '2026-04-03 22:15:22'),
(6, 'pretche1', '$2y$10$cf6KrlBjAIuhhLKgkswdWuSJgWxmZwKbGgp4hAoh/tQqN6L44pDiO', 'Pretche Orquillas', 'staff', '09503648981', 'orquillaspretche@gmail.com', 'uploads/avatars/avatar_6_857702f1b5aab545.png', 1, NULL, NULL, '2026-03-21 15:23:19', '2026-03-21 17:05:24'),
(7, 'alas123', '$2y$10$ZGsWPknfUPXJNuCp0Ad.oOhuUgr8FdX4PepieX/.5d/86SJ.6KEmu', 'Alas Obi', 'staff', '09234567891', 'alas123@gmail.com', NULL, 0, NULL, NULL, '2026-04-03 22:02:47', '2026-04-03 22:06:35'),
(8, 'admin2', '$2y$10$X2NbwUtyQSj5ddO6wBqxOubD7qT72fzqKO47B5XnqThTqjruk3/re', 'Admin2', 'admin', NULL, 'admin2@gmail.com', NULL, 1, NULL, NULL, '2026-04-03 22:03:29', '2026-04-03 22:03:29'),
(9, 'admin3', '$2y$10$e6ZIg65wKnanSODFoDOcZ.ixpjd/vYG2ymBTh4qpprJY9dL99rWRq', 'Admin3', 'admin', NULL, 'admin3@gmail.com', NULL, 1, NULL, NULL, '2026-04-03 22:04:04', '2026-04-03 22:04:04'),
(10, 'staff2', '$2y$10$rb/IPTbPCXn9V1fC8CYVkOZikbfPwj9ggJ4tDrA7LBQesOWD5iBB2', 'Staff2', 'staff', NULL, 'staff2@gmail.com', NULL, 1, NULL, NULL, '2026-04-03 22:04:46', '2026-04-03 22:05:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_logs_user` (`user_id`),
  ADD KEY `idx_logs_created` (`created_at`),
  ADD KEY `idx_logs_entity` (`entity_type`,`entity_id`);

--
-- Indexes for table `agenda_remarks`
--
ALTER TABLE `agenda_remarks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ar_user` (`created_by`),
  ADD KEY `idx_ar_date` (`remark_date`),
  ADD KEY `idx_ar_agenda` (`agenda_id`);

--
-- Indexes for table `barangay_officials`
--
ALTER TABLE `barangay_officials`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_official_contact_active` (`contact_id`,`is_active`),
  ADD KEY `fk_bo_esig` (`esignature_id`),
  ADD KEY `idx_bo_active_order` (`is_active`,`display_order`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_mobile` (`mobile`),
  ADD KEY `idx_contacts_name` (`full_name`),
  ADD KEY `idx_contacts_mobile` (`mobile`);

--
-- Indexes for table `contact_groups`
--
ALTER TABLE `contact_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_contact_groups_group_name` (`group_name`);

--
-- Indexes for table `contact_group_members`
--
ALTER TABLE `contact_group_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_contact_group` (`contact_id`,`group_id`),
  ADD KEY `idx_contact` (`contact_id`),
  ADD KEY `idx_group` (`group_id`);

--
-- Indexes for table `esignatures`
--
ALTER TABLE `esignatures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_esig_official` (`official_id`);

--
-- Indexes for table `event_agendas`
--
ALTER TABLE `event_agendas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_agenda_order` (`schedule_event_id`,`agenda_no`),
  ADD KEY `idx_agenda_event` (`schedule_event_id`),
  ADD KEY `idx_agenda_status` (`status`);

--
-- Indexes for table `event_attachments`
--
ALTER TABLE `event_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_attach_user` (`uploaded_by`),
  ADD KEY `idx_attach_event` (`schedule_event_id`);

--
-- Indexes for table `event_signatures`
--
ALTER TABLE `event_signatures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_event_contact_sign` (`schedule_event_id`,`contact_id`),
  ADD KEY `fk_es_contact` (`contact_id`),
  ADD KEY `fk_es_signature` (`esignature_id`),
  ADD KEY `idx_es_event` (`schedule_event_id`);

--
-- Indexes for table `minutes_attachments`
--
ALTER TABLE `minutes_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_minutes_document_id` (`minutes_document_id`),
  ADD KEY `idx_uploaded_by` (`uploaded_by`);

--
-- Indexes for table `minutes_documents`
--
ALTER TABLE `minutes_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_minutes_user` (`created_by`),
  ADD KEY `idx_minutes_event` (`schedule_event_id`);

--
-- Indexes for table `minutes_extractions`
--
ALTER TABLE `minutes_extractions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_minutes_version` (`minutes_document_id`,`extraction_version`),
  ADD KEY `idx_minutes_doc` (`minutes_document_id`);

--
-- Indexes for table `schedule_events`
--
ALTER TABLE `schedule_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_se_created_by` (`created_by`),
  ADD KEY `idx_se_dates` (`start_datetime`,`end_datetime`),
  ADD KEY `idx_se_status` (`status`),
  ADD KEY `idx_se_auto_notify` (`allow_auto_notify`,`start_datetime`);

--
-- Indexes for table `schedule_participants`
--
ALTER TABLE `schedule_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sp_event_contact` (`schedule_event_id`,`contact_id`),
  ADD KEY `idx_sp_event` (`schedule_event_id`),
  ADD KEY `idx_sp_contact` (`contact_id`);

--
-- Indexes for table `sms_outbox`
--
ALTER TABLE `sms_outbox`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sms_event_contact_sendat` (`schedule_event_id`,`contact_id`,`send_at`),
  ADD KEY `fk_sms_contact` (`contact_id`),
  ADD KEY `idx_sms_status_sendat` (`status`,`send_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_users_remember_token` (`remember_token`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=128;

--
-- AUTO_INCREMENT for table `agenda_remarks`
--
ALTER TABLE `agenda_remarks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `barangay_officials`
--
ALTER TABLE `barangay_officials`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `contact_groups`
--
ALTER TABLE `contact_groups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `contact_group_members`
--
ALTER TABLE `contact_group_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `esignatures`
--
ALTER TABLE `esignatures`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `event_agendas`
--
ALTER TABLE `event_agendas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `event_attachments`
--
ALTER TABLE `event_attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `event_signatures`
--
ALTER TABLE `event_signatures`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `minutes_attachments`
--
ALTER TABLE `minutes_attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `minutes_documents`
--
ALTER TABLE `minutes_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `minutes_extractions`
--
ALTER TABLE `minutes_extractions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `schedule_events`
--
ALTER TABLE `schedule_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `schedule_participants`
--
ALTER TABLE `schedule_participants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sms_outbox`
--
ALTER TABLE `sms_outbox`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `agenda_remarks`
--
ALTER TABLE `agenda_remarks`
  ADD CONSTRAINT `fk_ar_agenda` FOREIGN KEY (`agenda_id`) REFERENCES `event_agendas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ar_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `barangay_officials`
--
ALTER TABLE `barangay_officials`
  ADD CONSTRAINT `fk_bo_esig` FOREIGN KEY (`esignature_id`) REFERENCES `esignatures` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `contact_group_members`
--
ALTER TABLE `contact_group_members`
  ADD CONSTRAINT `fk_cgm_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cgm_group` FOREIGN KEY (`group_id`) REFERENCES `contact_groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `esignatures`
--
ALTER TABLE `esignatures`
  ADD CONSTRAINT `fk_esig_official` FOREIGN KEY (`official_id`) REFERENCES `barangay_officials` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_agendas`
--
ALTER TABLE `event_agendas`
  ADD CONSTRAINT `fk_agenda_event` FOREIGN KEY (`schedule_event_id`) REFERENCES `schedule_events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `event_attachments`
--
ALTER TABLE `event_attachments`
  ADD CONSTRAINT `fk_attach_event` FOREIGN KEY (`schedule_event_id`) REFERENCES `schedule_events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_attach_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `event_signatures`
--
ALTER TABLE `event_signatures`
  ADD CONSTRAINT `fk_es_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_es_event` FOREIGN KEY (`schedule_event_id`) REFERENCES `schedule_events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_es_signature` FOREIGN KEY (`esignature_id`) REFERENCES `esignatures` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `minutes_attachments`
--
ALTER TABLE `minutes_attachments`
  ADD CONSTRAINT `fk_minutes_attachments_doc` FOREIGN KEY (`minutes_document_id`) REFERENCES `minutes_documents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `minutes_documents`
--
ALTER TABLE `minutes_documents`
  ADD CONSTRAINT `fk_minutes_event` FOREIGN KEY (`schedule_event_id`) REFERENCES `schedule_events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_minutes_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `minutes_extractions`
--
ALTER TABLE `minutes_extractions`
  ADD CONSTRAINT `fk_me_minutes` FOREIGN KEY (`minutes_document_id`) REFERENCES `minutes_documents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `schedule_events`
--
ALTER TABLE `schedule_events`
  ADD CONSTRAINT `fk_se_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `schedule_participants`
--
ALTER TABLE `schedule_participants`
  ADD CONSTRAINT `fk_sp_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sp_event` FOREIGN KEY (`schedule_event_id`) REFERENCES `schedule_events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sms_outbox`
--
ALTER TABLE `sms_outbox`
  ADD CONSTRAINT `fk_sms_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sms_event` FOREIGN KEY (`schedule_event_id`) REFERENCES `schedule_events` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
