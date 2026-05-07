-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 08, 2026 at 03:27 AM
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
(26, 3, 'login', 'users', 3, 'Logged in: delven1', NULL, NULL, '2026-03-03 20:56:18'),
(27, 3, 'login', 'users', 3, 'Logged in: delven1', NULL, NULL, '2026-03-03 21:02:00'),
(28, 3, 'login', 'users', 3, 'Logged in: delven1', NULL, NULL, '2026-03-03 21:02:13'),
(29, 3, 'create', 'users', 4, 'Created account: staff', NULL, NULL, '2026-03-03 21:03:01'),
(30, 4, 'login', 'users', 4, 'Logged in: staff', NULL, NULL, '2026-03-03 21:03:34'),
(31, 3, 'login', 'users', 3, 'Logged in: delven1', NULL, NULL, '2026-03-03 21:17:20'),
(32, 3, 'login', 'users', 3, 'Logged in: delven1', NULL, NULL, '2026-03-07 18:48:25'),
(33, 3, 'login', 'users', 3, 'Logged in: delven1', NULL, NULL, '2026-03-07 19:22:14'),
(34, 3, 'login', 'users', 3, 'Logged in: delven1', NULL, NULL, '2026-03-08 08:49:00');

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
(4, 3, 'dasda', 3, 'adasasdasd', 'dasd', 1, '2026-02-25', '2026-02-27', 1, '2026-02-26 23:10:24', '2026-02-28 23:07:01'),
(5, 1, 'sadasdasdasd', 4, 'xxxxxxxxx', 'sadasd', 1, '2026-02-28', '2026-02-28', 1, '2026-02-28 23:08:46', '2026-02-28 23:08:46'),
(6, NULL, 'vvvv', NULL, 'vvcvcvc', NULL, 1, '2026-02-19', '2026-02-19', 1, '2026-02-28 23:14:10', NULL),
(7, NULL, 'HON. DELVEN BERGOSA', 5, 'SK Chairman', NULL, 1, '2026-03-01', '2026-03-12', 1, '2026-03-01 13:43:57', '2026-03-01 15:32:27');

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
(1, 'delven', '+639483933934', '3', 1, '2026-02-24 21:56:47', '2026-03-02 23:20:01'),
(3, 'deel', '09815906449', '3', 1, '2026-02-25 00:34:54', NULL),
(6, 'swd', '098657567567', '3', 1, '2026-02-25 00:42:31', NULL),
(7, 'eeeee', '99999999', 'SS', 1, '2026-02-25 00:42:50', NULL);

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
(1, 'barangay official', '2026-02-24 21:56:46'),
(2, 'barangay kagawad', '2026-02-24 21:57:07');

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
(5, 3, 1, '2026-02-25 00:34:54'),
(7, 6, 2, '2026-02-25 00:42:31'),
(8, 7, 1, '2026-02-25 00:42:50'),
(9, 1, 2, '2026-03-02 23:20:01'),
(10, 1, 1, '2026-03-02 23:20:01');

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
(4, 5, NULL, 'signature_nobg.png', 'sig_5_6918cb73cad656a0.png', 'backend/uploads/esignatures/sig_5_6918cb73cad656a0.png', 'image/png', '2026-02-28 23:08:46'),
(5, 7, NULL, 'signature_nobg.png', 'sig_7_67fc4099e09dc34f.png', 'backend/uploads/esignatures/sig_7_67fc4099e09dc34f.png', 'image/png', '2026-03-01 13:43:57');

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
(6, 14, 1, 'pahina', NULL, 'pending', '2026-03-02 23:31:05', NULL),
(7, 14, 2, 'meeting', NULL, 'pending', '2026-03-02 23:31:05', NULL),
(8, 14, 3, 'snack', NULL, 'pending', '2026-03-02 23:31:05', NULL),
(9, 15, 1, 'basketball', NULL, 'pending', '2026-03-02 23:36:35', NULL),
(10, 15, 2, 'palaro', NULL, 'pending', '2026-03-02 23:36:35', NULL),
(11, 15, 3, 'clean-up drive', NULL, 'pending', '2026-03-02 23:36:35', NULL);

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
(33, 19, 3, 'image', '23f60177-5b0b-4570-a0dc-dd6012e2895e.jpg', '38be9e6b0acef04576092da76a0d7ac7.jpg', 'uploads/minutes/19/38be9e6b0acef04576092da76a0d7ac7.jpg', 'image/jpeg', 966738, '2026-03-02 19:25:55');

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
(19, NULL, 3, 'Minutes of Meeting', 'extracted', '2026-03-02 19:25:54', '2026-03-02 19:26:36');

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
(16, 19, 1, '{\n  \"session_title\": \"18th Regular Session\",\n  \"session_date\": \"September 8, 2023\",\n  \"session_time\": \"\",\n\n  \"present_list\": [\n    { \"name\": \"Hon. Cleopatra C. Roces\", \"position\": \"Punong Barangay\" },\n    { \"name\": \"Hon. Allan D. Duran\", \"position\": \"Barangay Kagawad\" },\n    { \"name\": \"Hon. Rodrigo C. Abao, Jr.\", \"position\": \"Barangay Kagawad\" },\n    { \"name\": \"Hon. Margie M. Gabato\", \"position\": \"Barangay Kagawad\" },\n    { \"name\": \"Hon. Ruel M. Bueno\", \"position\": \"Barangay Kagawad\" },\n    { \"name\": \"Hon. Arnel N. Orlandez\", \"position\": \"Barangay Kagawad\" },\n    { \"name\": \"Hon. Lodovico C. Sato, Jr.\", \"position\": \"Barangay Kagawad\" },\n    { \"name\": \"Hon. Leonardo L. Cruza\", \"position\": \"Barangay Kagawad\" },\n    { \"name\": \"Hon. Charles Nino Rey L. Jamito\", \"position\": \"SK Chairperson\" }\n  ],\n  \"absent_list\": [],\n  \"others_present\": \"\",\n\n  \"presiding_officer\": \"\",\n  \"prayer_leader\": \"\",\n  \"adjournment_time\": \"\",\n  \"present_count\": null,\n  \"has_quorum\": null,\n\n  \"approval_of_minutes\": \"Excerpt from the minutes of the 17th Regular Session of the Sangguniang Barangay of Brgy. Sto. Rosario, Magallanes, Agusan del Norte held on August 1, 2023, 9 o\'clock in the morning (9am) at the Barangay Session Hall.\",\n  \"agenda\": \"\",\n  \"matters_for_information\": \"\",\n  \"privilege_hour\": \"6.1 Question Hour\",\n  \"first_reading_resolution\": \"Reso. No. __ - 2023: Requesting...\",\n  \"first_reading_referral\": \"7.1 - Reso. No. __ - 2023: Requesting... Movant - SBM __\",\n  \"committee_report\": \"\",\n  \"calendar_of_business\": \"9.1 Unfinished Business\\n9.2 Business of the Day\\n9.3 Unassigned Business\",\n  \"third_reading\": \"\",\n  \"other_matters\": \"\",\n  \"announcement\": \"\",\n\n  \"adjournment_movant\": \"\",\n  \"adjournment_seconder\": \"\",\n\n  \"call_to_order\": \"\",\n  \"roll_call\": \"\",\n  \"adjournment\": \"\"\n}', 'gemini-3-flash-preview', NULL, '2026-03-02 19:26:36');

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
(14, 3, 'pahina', 'meeting', 'please bring guna and sako', 'purok 3 soriano', '2026-03-02 23:39:00', '2026-03-03 00:39:00', 0, 'Subject: pahina\r\nTo: delven\r\nWhen: Mar 02, 2026 11:39 PM - Mar 03, 2026 12:39 AM\r\nWhere: purok 3 soriano\r\nDetails: please bring guna and sako\r\n\r\nAgenda:\r\n1. pahina\r\n2. meeting\r\n3. snack\r\n\r\n- admin1', 1, 0, 60, 'scheduled', '2026-03-02 23:31:05', NULL),
(15, 3, 'meeting', 'meeting', 'bring notebook', 'brgy. hall', '2026-03-02 23:45:00', '2026-03-03 00:45:00', 0, 'Subject: meeting\r\nTo: delven\r\nWhen: Mar 02, 2026 11:45 PM - Mar 03, 2026 12:45 AM\r\nWhere: brgy. hall\r\nDetails: bring notebook\r\n\r\nAgenda:\r\n1. basketball\r\n2. palaro\r\n3. clean-up drive\r\n\r\n- admin1', 1, 0, 60, 'scheduled', '2026-03-02 23:36:35', NULL);

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
(7, 13, 1, '09952387215', 'Subject: asdad\r\nTo: barangay kagawad\r\nWhen: Mar 02, 2026 8:51 PM - 9:51 PM\r\n\r\nAgenda:\r\n1. clean\r\n\r\n- admin1', '2026-03-02 13:42:22', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-02 20:42:22', '2026-03-02 23:10:06'),
(8, 13, 6, '098657567567', 'Subject: asdad\r\nTo: barangay kagawad\r\nWhen: Mar 02, 2026 8:51 PM - 9:51 PM\r\n\r\nAgenda:\r\n1. clean\r\n\r\n- admin1', '2026-03-02 13:42:22', 'immediate', 'failed', NULL, NULL, NULL, '2026-03-02 20:42:22', '2026-03-02 23:10:07'),
(9, 13, 1, '+639483933934', 'Subject: asdad\r\nTo: delven\r\nWhen: Mar 03, 2026 3:51 AM - 4:51 AM\r\nWhere: balay\r\nDetails: des\r\n\r\nAgenda:\r\n1. clean\r\n2. eat\r\n\r\n- admin1', '2026-03-02 16:21:03', 'immediate', 'sent', NULL, NULL, NULL, '2026-03-02 23:21:04', NULL),
(11, 13, 1, '+639483933934', 'Subject: asdad\nTo: delven\nWhen: Mar 03, 2026 10:51 AM - 11:51 AM\nWhere: balay\n\nAgenda:\n1. clean\n2. eat\n\n- admin1', '2026-03-02 16:22:22', 'immediate', 'sent', NULL, NULL, NULL, '2026-03-02 23:22:24', NULL),
(12, 13, 1, '+639483933934', 'Subject: asdad\r\nTo: delven\r\nWhen: Mar 03, 2026 10:51 AM - 11:51 AM\r\nWhere: balay\r\n\r\nAgenda:\r\n1. clean\r\n2. eat\r\n\r\n- admin1', '2026-03-03 10:45:00', 'auto', 'sent', NULL, NULL, NULL, '2026-03-02 23:22:24', '2026-03-03 19:26:02'),
(13, 14, 1, '+639483933934', 'Subject: pahina\r\nTo: delven\r\nWhen: Mar 02, 2026 11:39 PM - Mar 03, 2026 12:39 AM\r\nWhere: purok 3 soriano\r\nDetails: please bring guna and sako\r\n\r\nAgenda:\r\n1. pahina\r\n2. meeting\r\n3. snack\r\n\r\n- admin1', '2026-03-02 16:31:05', 'immediate', 'sent', NULL, NULL, NULL, '2026-03-02 23:31:06', NULL),
(14, 15, 1, '+639483933934', 'Subject: meeting\r\nTo: delven\r\nWhen: Mar 02, 2026 11:45 PM - Mar 03, 2026 12:45 AM\r\nWhere: brgy. hall\r\nDetails: bring notebook\r\n\r\nAgenda:\r\n1. basketball\r\n2. palaro\r\n3. clean-up drive\r\n\r\n- admin1', '2026-03-02 16:36:35', 'immediate', 'sent', NULL, NULL, NULL, '2026-03-02 23:36:36', NULL);

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
(3, 'delven1', '$2y$10$R8V5J6Oa1mq5vbWSTuwYge0Ro7iLEkoNWYMJCZTHBzgUJv/07IIs2', 'admin1', 'admin', NULL, NULL, NULL, 1, NULL, NULL, '2026-02-22 15:28:47', '2026-03-03 21:03:22'),
(4, 'staff', '$2y$10$T6bzDeHsBC.5iWR1nNxctOr.RuyVfI5c8s3qoBpuOS99DlJXKtFOC', 'staff', 'staff', NULL, NULL, NULL, 1, NULL, NULL, '2026-03-03 21:03:01', '2026-03-03 21:03:01');

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `contact_groups`
--
ALTER TABLE `contact_groups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contact_group_members`
--
ALTER TABLE `contact_group_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `esignatures`
--
ALTER TABLE `esignatures`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `event_agendas`
--
ALTER TABLE `event_agendas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `minutes_documents`
--
ALTER TABLE `minutes_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `minutes_extractions`
--
ALTER TABLE `minutes_extractions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `schedule_events`
--
ALTER TABLE `schedule_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `schedule_participants`
--
ALTER TABLE `schedule_participants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_outbox`
--
ALTER TABLE `sms_outbox`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
