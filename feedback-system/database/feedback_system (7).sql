-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 05, 2026 at 10:03 AM
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
-- Database: `feedback_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `admin_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'Login', 'Admin logged into the system.', '::1', '2026-04-21 13:41:28'),
(2, 1, 'Login', 'Admin logged into the system.', '::1', '2026-04-27 00:51:46'),
(3, 1, 'Delete Feedback', 'Deleted feedback ID 72 from user james_smith48', '::1', '2026-04-27 01:21:01'),
(4, 1, 'Delete Feedback', 'Deleted feedback ID 71 from user renielbantilan00', '::1', '2026-04-27 01:21:07'),
(5, 1, 'Delete Feedback', 'Deleted feedback ID 73 from user james_smith48', '::1', '2026-04-27 01:31:29'),
(6, 1, 'Delete Feedback', 'Deleted feedback ID 69 from user renielbantilan00', '::1', '2026-04-27 01:31:34'),
(7, 1, 'Delete Feedback', 'Deleted feedback ID 65 from user renielbantilan00', '::1', '2026-04-27 01:31:38'),
(8, 1, 'Delete Feedback', 'Deleted feedback ID 64 from user renielbantilan00', '::1', '2026-04-27 01:31:43'),
(9, 1, 'Delete Feedback', 'Deleted feedback ID 75 from user renielbantilan00', '::1', '2026-04-27 02:05:09'),
(10, 1, 'Delete Feedback', 'Deleted feedback ID 74 from user renielbantilan00', '::1', '2026-04-27 02:05:14'),
(11, 1, 'Delete Feedback', 'Deleted feedback ID 77 from user renielbantilan00', '::1', '2026-04-27 03:01:15'),
(12, 1, 'Delete Feedback', 'Deleted feedback ID 78 from user renielbantilan00', '::1', '2026-04-27 03:02:31'),
(13, 1, 'Login', 'Admin logged into the system.', '::1', '2026-04-28 13:39:45'),
(14, 1, 'Login', 'Admin logged into the system.', '::1', '2026-04-30 16:41:12'),
(15, 1, 'Logout', 'Admin logged out securely.', '::1', '2026-04-30 16:41:16'),
(16, 1, 'Login', 'Admin logged into the system.', '::1', '2026-04-30 16:42:21'),
(17, 1, 'Logout', 'Admin logged out securely.', '::1', '2026-04-30 16:53:20'),
(18, 1, 'Login', 'Admin logged into the system.', '::1', '2026-04-30 17:11:22'),
(19, 1, 'Logout', 'Admin logged out securely.', '::1', '2026-04-30 17:12:39'),
(20, 1, 'Login', 'Admin logged into the system.', '::1', '2026-04-30 17:22:16'),
(21, 1, 'Logout', 'Admin logged out securely.', '::1', '2026-04-30 17:22:25'),
(22, 1, 'Login', 'Admin logged into the system.', '::1', '2026-04-30 17:23:03'),
(23, 1, 'Logout', 'Admin logged out securely.', '::1', '2026-04-30 17:23:16'),
(24, 1, 'Login', 'Admin logged into the system.', '::1', '2026-04-30 17:38:55'),
(25, 1, 'Logout', 'Admin logged out securely.', '::1', '2026-04-30 17:47:11'),
(26, 1, 'Login', 'Admin logged into the system.', '::1', '2026-04-30 17:52:07'),
(27, 1, 'Logout', 'Admin logged out securely.', '::1', '2026-04-30 17:52:25'),
(28, 1, 'Login', 'Admin logged into the system.', '::1', '2026-04-30 17:53:41'),
(29, 1, 'Logout', 'Admin logged out securely.', '::1', '2026-04-30 17:53:52'),
(30, 1, 'Login', 'Admin logged into the system.', '::1', '2026-05-05 05:03:56'),
(31, 1, 'Logout', 'Admin logged out securely.', '::1', '2026-05-05 05:04:48'),
(32, 1, 'Login', 'Admin logged into the system.', '::1', '2026-05-05 05:04:57'),
(33, 1, 'Logout', 'Admin logged out securely.', '::1', '2026-05-05 05:05:06'),
(34, 1, 'Login', 'Admin logged into the system.', '::1', '2026-05-05 05:05:14'),
(35, 1, 'Logout', 'Admin logged out securely.', '::1', '2026-05-05 05:05:46'),
(36, 1, 'Login', 'Admin logged into the system.', '::1', '2026-05-05 05:05:59'),
(37, 1, 'Logout', 'Admin logged out securely.', '::1', '2026-05-05 05:06:05'),
(38, 1, 'Login', 'Admin logged into the system.', '::1', '2026-05-05 05:06:13');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `middlename` varchar(50) DEFAULT NULL,
  `lastname` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_type` varchar(20) NOT NULL DEFAULT 'admin',
  `specialization_id` int(11) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `firstname`, `middlename`, `lastname`, `username`, `email`, `password`, `created_at`, `user_type`, `specialization_id`, `is_available`, `updated_at`) VALUES
(1, 'Reniel', 'Pontejos', 'Bantilan', 'reniel.bantilan', 'reniel.bantilan@csucc.edu.ph', '$2y$10$bxfCh2T4yTF2VcJHnhrwHepdLiwYX268vkxaPVvG2GRDIP.gjEiOi', '2025-12-27 12:44:12', 'superadmin', NULL, 1, '2026-01-22 03:37:59'),
(2, 'Jonah May', 'S', 'Hinautan', 'jonahmay.hinautan', 'jonahmay.hinautan@csucc.edu.ph', '$2y$10$hozBORg3Cwg.GRDJexke7udo/91z9v1n4u6nw26myjTkMqyd56S5y', '2025-12-27 14:49:07', 'admin', NULL, 1, '2026-01-24 09:30:25'),
(3, 'Aldrin', '', 'Cabilic', 'aldrin.cabilic', 'aldrin.cabilic@gmail.com', '$2y$10$uuLmx2ku7/ojRNTKfGK1NONetbbpKLRxMfMbfLdBUG3/RMEEiAcuq', '2026-01-22 03:32:47', 'admin', NULL, 1, '2026-01-22 03:32:47');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(10) DEFAULT '?',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `icon`, `is_active`, `created_at`) VALUES
(1, 'Water', 'Water supply, quality, and related services', '💧', 1, '2025-12-19 02:41:55'),
(2, 'Waste', 'Waste management and garbage collection', '🗑️', 1, '2025-12-19 02:41:55'),
(3, 'Streetlight', 'Street lighting and public lighting', '💡', 1, '2025-12-19 02:41:55'),
(4, 'Roads', 'Road conditions, maintenance, and repairs', '🛣️', 1, '2025-12-19 02:41:55'),
(5, 'Sanitation', 'Public sanitation facilities and cleanliness', '🧹', 1, '2025-12-19 02:41:55'),
(6, 'Health', 'Health services and facilities', '🏥', 1, '2025-12-19 02:41:55'),
(8, 'Security', 'Public safety and security', '👮', 1, '2025-12-19 02:41:55'),
(9, 'Others', 'Other public services and concerns', '📋', 1, '2025-12-19 02:41:55');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `status` enum('Pending','Assigned','In Progress','Resolved') DEFAULT 'Pending',
  `rating` int(11) NOT NULL,
  `comment` text NOT NULL,
  `sentiment` enum('Positive','Negative','Neutral') DEFAULT 'Neutral',
  `attachment_path` varchar(255) DEFAULT NULL,
  `is_resolved` tinyint(1) DEFAULT 0,
  `resolved_by` varchar(255) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_updated_rating` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `user_id`, `category_id`, `status`, `rating`, `comment`, `sentiment`, `attachment_path`, `is_resolved`, `resolved_by`, `resolved_at`, `resolution_notes`, `assigned_to`, `assigned_at`, `created_at`, `updated_at`, `is_updated_rating`) VALUES
(67, 1593, 1, 'Pending', 0, 'Hugaw ang tubig dri sa p-8', 'Negative', NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-04-12 17:48:06', '2026-04-12 17:48:06', 0),
(68, 1592, 3, 'Pending', 0, 'ngit-ngit dri sa purok 2 lain kaau sa dalan dili makita', 'Negative', NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-04-12 18:04:33', '2026-04-12 18:04:33', 0),
(76, 7, 3, 'Pending', 3, 'pangit ang suga dre', 'Negative', NULL, 1, NULL, '2026-04-27 10:10:30', NULL, NULL, NULL, '2026-04-27 02:10:14', '2026-04-27 02:27:42', 1),
(79, 1852, 3, 'Pending', 0, 'Pangit ang streetlight dire sa purok 2 hinay kaau', 'Negative', NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-04-30 17:38:27', '2026-04-30 17:38:27', 0),
(80, 1853, 1, 'Pending', 0, 'Hugaw ang tubig diri sa purok 10 paki trabaho palihog kay gamitonon na ang tubig', 'Negative', NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-04-30 17:53:23', '2026-04-30 17:53:23', 0);

-- --------------------------------------------------------

--
-- Table structure for table `feedback_assignments`
--

CREATE TABLE `feedback_assignments` (
  `id` int(11) NOT NULL,
  `feedback_id` int(11) NOT NULL,
  `personnel_id` int(11) DEFAULT NULL,
  `status` enum('Pending','In Progress','Resolved','Waiting') DEFAULT 'Pending',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `admin_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback_assignments`
--

INSERT INTO `feedback_assignments` (`id`, `feedback_id`, `personnel_id`, `status`, `assigned_at`, `started_at`, `completed_at`, `admin_notes`) VALUES
(35, 67, NULL, 'Waiting', '2026-04-12 17:48:06', NULL, NULL, NULL),
(36, 68, 27, 'Pending', '2026-04-12 18:04:33', NULL, NULL, NULL),
(43, 76, 27, 'Resolved', '2026-04-27 02:10:14', NULL, '2026-04-27 02:10:30', ''),
(44, 79, 27, 'Pending', '2026-04-30 17:38:27', NULL, NULL, NULL),
(45, 80, 30, 'Pending', '2026-04-30 17:53:23', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) UNSIGNED NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_success` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `ip_address`, `attempt_time`, `is_success`) VALUES
(1, '::1', '2026-01-11 00:22:32', 0),
(2, '::1', '2026-01-11 00:22:35', 0),
(3, '::1', '2026-01-11 00:22:38', 0),
(4, '::1', '2026-01-11 00:22:41', 0);

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `time_in` datetime NOT NULL,
  `time_out` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`id`, `admin_id`, `name`, `ip_address`, `time_in`, `time_out`) VALUES
(1, 2, 'Jonah May Hinautan', '::1', '2026-01-27 21:00:54', '2026-01-27 21:01:53'),
(2, 1, 'Reniel Bantilan', '::1', '2026-02-03 17:19:40', '2026-02-03 17:20:49'),
(3, 1, 'Reniel Bantilan', '::1', '2026-02-03 17:20:42', NULL),
(4, 1, 'Reniel Bantilan', '::1', '2026-02-19 14:44:32', NULL),
(5, 1, 'Reniel Bantilan', '::1', '2026-02-22 22:09:18', NULL),
(6, 1, 'Reniel Bantilan', '::1', '2026-02-26 22:44:39', NULL),
(7, 1, 'Reniel Bantilan', '::1', '2026-03-19 11:24:01', NULL),
(8, 1, 'Reniel Bantilan', '::1', '2026-03-19 20:17:20', NULL),
(9, 1, 'Reniel Bantilan', '::1', '2026-03-20 11:52:34', NULL),
(10, 1, 'Reniel Bantilan', '::1', '2026-03-24 22:26:42', NULL),
(11, 1, 'Reniel Bantilan', '::1', '2026-04-05 21:12:22', '2026-04-05 21:19:18'),
(12, 1, 'Reniel Bantilan', '::1', '2026-04-05 21:20:38', NULL),
(13, 1, 'Reniel Bantilan', '::1', '2026-04-06 16:03:25', NULL),
(14, 1, 'Reniel Bantilan', '::1', '2026-04-12 23:33:04', '2026-04-12 23:45:05'),
(15, 1, 'Reniel Bantilan', '::1', '2026-04-13 03:21:32', NULL),
(16, 1, 'Reniel Bantilan', '::1', '2026-04-15 03:58:09', '2026-04-15 04:14:55'),
(17, 1, 'Reniel Bantilan', '::1', '2026-04-15 04:15:38', NULL),
(18, 1, 'Reniel Bantilan', '::1', '2026-04-15 06:41:53', NULL),
(19, 1, 'Reniel Bantilan', '::1', '2026-04-20 21:56:36', '2026-04-20 21:59:45'),
(20, 1, 'Reniel Bantilan', '::1', '2026-04-20 22:33:01', NULL),
(21, 1, 'Reniel Bantilan', '::1', '2026-04-21 21:41:27', NULL),
(22, 1, 'Reniel Bantilan', '::1', '2026-04-27 08:51:46', NULL),
(23, 1, 'Reniel Bantilan', '::1', '2026-04-28 21:39:45', NULL),
(24, 1, 'Reniel Bantilan', '::1', '2026-04-30 23:31:00', NULL),
(25, 1, 'Reniel Bantilan', '::1', '2026-05-01 00:25:55', NULL),
(26, 1, 'Reniel Bantilan', '::1', '2026-05-01 00:41:12', '2026-05-01 00:41:16'),
(27, 1, 'Reniel Bantilan', '::1', '2026-05-01 00:42:21', '2026-05-01 00:53:20'),
(28, 1, 'Reniel Bantilan', '::1', '2026-05-01 01:11:22', '2026-05-01 01:12:39'),
(29, 1, 'Reniel Bantilan', '::1', '2026-05-01 01:22:16', '2026-05-01 01:22:25'),
(30, 1, 'Reniel Bantilan', '::1', '2026-05-01 01:23:03', '2026-05-01 01:23:16'),
(31, 1, 'Reniel Bantilan', '::1', '2026-05-01 01:38:55', '2026-05-01 01:47:11'),
(32, 1, 'Reniel Bantilan', '::1', '2026-05-01 01:52:07', '2026-05-01 01:52:25'),
(33, 1, 'Reniel Bantilan', '::1', '2026-05-01 01:53:41', '2026-05-01 01:53:52'),
(34, 1, 'Reniel Bantilan', '::1', '2026-05-05 13:03:56', '2026-05-05 13:04:48'),
(35, 1, 'Reniel Bantilan', '::1', '2026-05-05 13:04:57', '2026-05-05 13:05:06'),
(36, 1, 'Reniel Bantilan', '::1', '2026-05-05 13:05:14', '2026-05-05 13:05:46'),
(37, 1, 'Reniel Bantilan', '::1', '2026-05-05 13:05:58', '2026-05-05 13:06:05'),
(38, 1, 'Reniel Bantilan', '::1', '2026-05-05 13:06:13', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(4, 'renielbantlan69@gmail.com', 'cf532892a2e7888b705b2064c6732d447e57256c948b78ddae20d44fca8268e1', '2026-03-24 21:00:41', '2026-03-24 12:00:41'),
(5, 'renielbantlan69@gmail.com', '115515', '2026-03-24 20:14:15', '2026-03-24 12:12:15'),
(6, 'renielbantlan69@gmail.com', '064360', '2026-03-24 20:18:20', '2026-03-24 12:16:20'),
(7, 'renielbantlan69@gmail.com', '429416', '2026-03-24 20:23:31', '2026-03-24 12:21:31'),
(8, 'renielbantlan69@gmail.com', '489206', '2026-03-24 20:23:35', '2026-03-24 12:21:35'),
(9, 'aldrin.cabilic@gmail.com', '639397', '2026-03-24 20:24:45', '2026-03-24 12:22:45'),
(10, 'renielbantlan69@gmail.com', '823318', '2026-03-24 20:31:59', '2026-03-24 12:29:59'),
(11, 'renielbantilan69@gmail.com', '695764', '2026-03-24 20:36:27', '2026-03-24 12:34:27');

-- --------------------------------------------------------

--
-- Table structure for table `personnel`
--

CREATE TABLE `personnel` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `specialization_id` int(11) NOT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `star_rating` decimal(2,1) DEFAULT 0.0,
  `total_completed` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personnel`
--

INSERT INTO `personnel` (`id`, `name`, `description`, `specialization_id`, `is_available`, `star_rating`, `total_completed`, `created_at`, `updated_at`) VALUES
(25, 'Reniel Bantilan', '', 8, 1, 0.0, 1, '2026-02-22 14:18:33', '2026-04-27 01:09:09'),
(26, 'Aldrin Cabilic', '', 6, 1, 0.0, 0, '2026-02-22 14:18:49', '2026-02-22 15:13:29'),
(27, 'Ariel Jomoc', '', 3, 1, 3.0, 1, '2026-02-22 14:19:13', '2026-04-27 02:27:42'),
(28, 'John Digal', '', 5, 1, 0.0, 0, '2026-02-22 14:22:08', '2026-02-22 15:13:29'),
(30, 'Yannah Salazar', '', 1, 1, 0.0, 1, '2026-04-20 13:58:32', '2026-04-27 01:32:35'),
(31, 'John Digal', '', 8, 1, 0.0, 0, '2026-04-27 01:03:34', '2026-04-27 01:03:34');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `name`, `value`, `created_at`, `updated_at`) VALUES
(6, 'maintenance_mode', '0', '2026-01-03 05:02:50', '2026-01-25 10:50:33');

-- --------------------------------------------------------

--
-- Table structure for table `surveys`
--

CREATE TABLE `surveys` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Draft','Active','Closed') DEFAULT 'Draft',
  `created_by` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_fields`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `surveys`
--

INSERT INTO `surveys` (`id`, `title`, `description`, `status`, `created_by`, `assigned_to`, `start_date`, `end_date`, `created_at`, `updated_at`, `custom_fields`) VALUES
(21, 'Example', 'example', 'Closed', 1, NULL, '2025-04-13 00:00:00', '2026-04-14 00:00:00', '2026-04-12 18:03:23', '2026-04-14 22:42:05', NULL),
(22, 'Health Worker Workplace Assessment Survey', 'This survey is designed to gather feedback from health workers regarding their work environment, workload, training, patient care, and overall well-being. The purpose is to identify strengths and areas for improvement in the workplace to enhance service delivery and support for health workers.', 'Active', 1, NULL, '2026-04-15 00:00:00', '2026-05-15 00:00:00', '2026-04-14 22:46:19', '2026-04-14 22:46:19', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `survey_questions`
--

CREATE TABLE `survey_questions` (
  `id` int(11) NOT NULL,
  `survey_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('text','rating','multiple_choice','yes_no') DEFAULT 'text',
  `options` text DEFAULT NULL COMMENT 'JSON array of options for multiple_choice',
  `order_num` int(11) DEFAULT 0,
  `is_required` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `survey_questions`
--

INSERT INTO `survey_questions` (`id`, `survey_id`, `question_text`, `question_type`, `options`, `order_num`, `is_required`, `created_at`) VALUES
(56, 21, 'Firstname', 'text', NULL, 1, 1, '2026-04-12 18:03:38'),
(57, 22, 'Do you have access to the necessary equipment to perform your duties effectively?', 'yes_no', NULL, 1, 1, '2026-04-14 22:46:58'),
(58, 22, 'Are emergency protocols clearly communicated in your workplace?', 'yes_no', NULL, 2, 1, '2026-04-14 22:47:20'),
(59, 22, 'Do you receive timely updates about health policies and guidelines?', 'yes_no', NULL, 3, 1, '2026-04-14 22:47:52'),
(60, 22, 'Is communication among staff clear and effective?', 'yes_no', NULL, 4, 1, '2026-04-14 22:48:07'),
(61, 22, 'Do you feel valued and recognized for your work?', 'yes_no', NULL, 5, 1, '2026-04-14 22:48:23'),
(62, 22, 'Are patient records handled efficiently in your facility?', 'yes_no', NULL, 6, 1, '2026-04-14 22:48:37'),
(63, 22, 'Do you have access to mental health or stress support services?', 'yes_no', NULL, 7, 1, '2026-04-14 22:48:50'),
(65, 22, 'Are infection control measures strictly followed in your workplace?', 'text', NULL, 8, 1, '2026-04-14 22:50:10');

-- --------------------------------------------------------

--
-- Table structure for table `survey_responses`
--

CREATE TABLE `survey_responses` (
  `id` int(11) NOT NULL,
  `survey_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `purok` varchar(100) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `user_type` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `image_path` varchar(255) DEFAULT NULL,
  `theme_preference` varchar(10) DEFAULT 'light',
  `language` varchar(10) DEFAULT 'en'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `purok`, `username`, `password`, `email`, `user_type`, `created_at`, `is_active`, `image_path`, `theme_preference`, `language`) VALUES
(7, 'Aldrin', 'Bantilan', '2', 'renielbantilan00', '$2y$10$au9Gh4G2ERuItHr0OFbs1O2fswjL2e3RI246oM0.AQ5UnfUu/XJ..', 'renielbantilan69@gmail.com', 'user', '2025-12-28 21:47:12', 1, '../uploads/profiles/user_7_1766965382.jpg', 'dark', 'bisaya'),
(8, 'Aldrin', 'Cabilic', '4', 'aldrin.cabilic', '$2y$10$12ZZ3IiwEhdIzTLCyu0Qw.rqc1kcZRJg2VBY.IP1XiaBFwsGh8u3K', 'aldrin.cabilic@gmail.com', 'user', '2026-01-02 11:44:05', 0, '../uploads/profiles/user_8_1767403248.webp', 'light', 'en'),
(10, 'Juan', 'Dela Cruz', '2', 'juan.delacruz', '$2y$10$iviaA0EX7f6buKQMcYykDu2u/zx1sriqhldLcYc11YFAxi1UNz.8K', 'juan@gmail.com', 'user', '2026-01-24 02:32:08', 1, NULL, 'light', 'en'),
(11, 'Admin', 'User', 'Purok 1', 'admin_test', '$2y$10$XxqpRK/2ueSN6O3j5AS/WefgrDLm9TvUnXnn6oxKaofe6iPPy4ky6', 'admin_test@gmail.com', 'admin', '2026-04-14 06:26:19', 1, NULL, 'light', 'en'),
(53, 'Sarah', 'Wilson', 'Purok 1', 'sarah_wilson71', '$2y$10$wNM4FyjaNdyctugisnbGyuIRqljdKEv1fSLqi1cStCfHq0cJouWyW', 'sarah.wilson@gmail.com', 'user', '2026-04-14 20:11:05', 1, NULL, 'light', 'en'),
(54, 'Laura', 'Miller', 'Purok 6', 'laura_miller85', '$2y$10$1glF.trg5RD/8iGtTkEFTOjzxcdCkQLrK4gUZZ4dhz6aN1Qw19.AS', 'laura.miller@gmail.com', 'user', '2026-04-14 20:11:05', 1, NULL, 'light', 'en'),
(55, 'Rachel', 'Jackson', 'Purok 2', 'rachel_jackson59', '$2y$10$5KsjW.uPcFq/oK8TAp/rmeSssBf.rpCuLDgVW//hwxzYUS75c9DoS', 'rachel.jackson@gmail.com', 'user', '2026-04-14 20:11:06', 1, NULL, 'light', 'en'),
(56, 'Emily', 'White', 'Purok 4', 'emily_white95', '$2y$10$5J0ddjYqGN34c2VVHu/ZNebspXfacs6O0ehCVhCx60kMwd7afj2zS', 'emily.white@gmail.com', 'user', '2026-04-14 20:11:06', 1, NULL, 'light', 'en'),
(57, 'Laura', 'Thompson', 'Purok 7', 'laura_thompson27', '$2y$10$YibJEEYrHvM6oWtOJojrMOnqXFXP4a.OYRYb2sdyFK/f.jv.yeTNa', 'laura.thompson@gmail.com', 'user', '2026-04-14 20:11:06', 1, NULL, 'light', 'en'),
(58, 'Emily', 'Martin', 'Purok 7', 'emily_martin33', '$2y$10$ropFwRLwryO46M/IshvJtu1Z2mV3roXS5u6oktJJfwAuqXdhaFxOa', 'emily.martin@gmail.com', 'user', '2026-04-14 20:11:06', 1, NULL, 'light', 'en'),
(59, 'Megan', 'Anderson', 'Purok 3', 'megan_anderson67', '$2y$10$5SsWo7a7OcwzDg1rR1/YI.ysUJO.P4UwH15HO1jkky7Pf0um92dvq', 'megan.anderson@gmail.com', 'user', '2026-04-14 20:11:06', 1, NULL, 'light', 'en'),
(60, 'Joseph', 'Thompson', 'Purok 6', 'joseph_thompson65', '$2y$10$M.C/NXT7jhyma0JUx1BulOGysl5OQvGrlWqQ/AY9SYXZfOYbUPfAS', 'joseph.thompson@gmail.com', 'user', '2026-04-14 20:11:06', 1, NULL, 'light', 'en'),
(61, 'Melissa', 'Jackson', 'Purok 5', 'melissa_jackson60', '$2y$10$40WFtWUZgVxOkQp1JvGVquk/9PAbQF3reExbMBQCO1.9VnW1m9nnG', 'melissa.jackson@gmail.com', 'user', '2026-04-14 20:11:07', 1, NULL, 'light', 'en'),
(62, 'Rebecca', 'Jones', 'Purok 4', 'rebecca_jones33', '$2y$10$5IhVSPzgzk46ZigmvpcpkuYOquUFs/A7HAUfdafDqvaCUUSO.3MdS', 'rebecca.jones@gmail.com', 'user', '2026-04-14 20:11:07', 1, NULL, 'light', 'en'),
(63, 'Michelle', 'Brown', 'Purok 6', 'michelle_brown40', '$2y$10$29/bEMagZ5Q/2y0yTNLlsu4n/ZmcytwgxhdlqAad9uGHR.lvAD3Im', 'michelle.brown@gmail.com', 'user', '2026-04-14 20:11:07', 1, NULL, 'light', 'en'),
(64, 'Michael', 'Anderson', 'Purok 4', 'michael_anderson26', '$2y$10$/jw43DQUoVdyoCzD6GtCnuDwfyi3Xa9TgeFnPbrsRHuTxlg6XDNH.', 'michael.anderson@gmail.com', 'user', '2026-04-14 20:11:07', 1, NULL, 'light', 'en'),
(65, 'Ashley', 'Jones', 'Purok 7', 'ashley_jones26', '$2y$10$c7ZbTrNCGEByCIU5zzWD1.KQX7YckDq0GPtRayCAva8gK0Vrpf/8e', 'ashley.jones@gmail.com', 'user', '2026-04-14 20:11:07', 1, NULL, 'light', 'en'),
(66, 'William', 'Jackson', 'Purok 1', 'william_jackson31', '$2y$10$OyGgc.lcKOOtHRVkwo8n0Oqs4HSGPSf2tJ/bD8tzWMsgUOEGmNx/a', 'william.jackson@gmail.com', 'user', '2026-04-14 20:11:07', 1, NULL, 'light', 'en'),
(67, 'William', 'Moore', 'Purok 6', 'william_moore41', '$2y$10$i5tMTk1eD6Y7wrWoeo/0jukBiH2nsT20ev2o3EjnTWl4Tc.Nyrrim', 'william.moore@gmail.com', 'user', '2026-04-14 20:11:08', 1, NULL, 'light', 'en'),
(68, 'Thomas', 'Moore', 'Purok 7', 'thomas_moore58', '$2y$10$DLvEO2hPcY/SYPGQfpS4hujroRyy9Oh1u2tNqIijzs4bMlNnslygy', 'thomas.moore@gmail.com', 'user', '2026-04-14 20:11:08', 1, NULL, 'light', 'en'),
(69, 'Rachel', 'Moore', 'Purok 3', 'rachel_moore87', '$2y$10$7PoZu4I18VwniXE64ywsZOvdpIBdfZNiEZDcPUsc4WX2c6m1nFOGq', 'rachel.moore@gmail.com', 'user', '2026-04-14 20:11:08', 1, NULL, 'light', 'en'),
(70, 'James', 'Smith', 'Purok 1', 'james_smith48', '$2y$10$HeGn4QHsK/tCxoqXlbnfR.KakPIhE5nPP7HTKzTgE5aZHEefud34i', 'james.smith@gmail.com', 'user', '2026-04-14 20:11:08', 1, NULL, 'light', 'en'),
(71, 'Jessica', 'Thomas', 'Purok 5', 'jessica_thomas52', '$2y$10$fqEcUjqqEqGtg2tOwueNRuzCzQdRvLKXGdrUPrBhAAqwJiBq3rhUS', 'jessica.thomas@gmail.com', 'user', '2026-04-14 20:11:08', 1, NULL, 'light', 'en'),
(72, 'Laura', 'Jones', 'Purok 3', 'laura_jones80', '$2y$10$uIvq2XSvhaLB6ZeERPLPB.DP5Dw5Z6LtANHh.3A2R5wpBebpko31K', 'laura.jones@gmail.com', 'user', '2026-04-14 20:11:08', 1, NULL, 'light', 'en');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_feedback_user` (`user_id`),
  ADD KEY `idx_feedback_category` (`category_id`),
  ADD KEY `idx_feedback_sentiment` (`sentiment`),
  ADD KEY `idx_feedback_created` (`created_at`);

--
-- Indexes for table `feedback_assignments`
--
ALTER TABLE `feedback_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_feedback_assignment` (`feedback_id`),
  ADD KEY `idx_assignment_personnel` (`personnel_id`),
  ADD KEY `idx_assignment_status` (`status`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ip_address` (`ip_address`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `time_in` (`time_in`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `personnel`
--
ALTER TABLE `personnel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_personnel_specialization` (`specialization_id`),
  ADD KEY `idx_personnel_availability` (`is_available`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `surveys`
--
ALTER TABLE `surveys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `survey_questions`
--
ALTER TABLE `survey_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `survey_id` (`survey_id`);

--
-- Indexes for table `survey_responses`
--
ALTER TABLE `survey_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `survey_id` (`survey_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_type` (`user_type`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `feedback_assignments`
--
ALTER TABLE `feedback_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `personnel`
--
ALTER TABLE `personnel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `surveys`
--
ALTER TABLE `surveys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `survey_questions`
--
ALTER TABLE `survey_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `survey_responses`
--
ALTER TABLE `survey_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback_assignments`
--
ALTER TABLE `feedback_assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`feedback_id`) REFERENCES `feedback` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`);

--
-- Constraints for table `personnel`
--
ALTER TABLE `personnel`
  ADD CONSTRAINT `personnel_ibfk_1` FOREIGN KEY (`specialization_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `surveys`
--
ALTER TABLE `surveys`
  ADD CONSTRAINT `surveys_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `surveys_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `survey_questions`
--
ALTER TABLE `survey_questions`
  ADD CONSTRAINT `survey_questions_ibfk_1` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `survey_responses`
--
ALTER TABLE `survey_responses`
  ADD CONSTRAINT `survey_responses_ibfk_1` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `survey_responses_ibfk_3` FOREIGN KEY (`question_id`) REFERENCES `survey_questions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
