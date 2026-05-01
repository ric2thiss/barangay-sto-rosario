-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 12, 2026 at 09:33 PM
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `user_id`, `category_id`, `status`, `rating`, `comment`, `sentiment`, `attachment_path`, `is_resolved`, `resolved_by`, `resolved_at`, `resolution_notes`, `assigned_to`, `assigned_at`, `created_at`, `updated_at`) VALUES
(64, 7, 3, 'Pending', 5, 'pangit lain kaau hugaw', 'Negative', NULL, 1, 'Ariel Jomoc', '2026-02-22 23:00:00', '', NULL, NULL, '2026-02-22 14:58:56', '2026-02-22 15:00:07'),
(65, 7, 3, 'Pending', 2, 'pangit hugaw', 'Negative', NULL, 1, 'Ariel Jomoc', '2026-02-22 23:00:58', '', NULL, NULL, '2026-02-22 15:00:38', '2026-02-22 15:01:05'),
(67, 1593, 1, 'Pending', 0, 'Hugaw ang tubig dri sa p-8', 'Negative', NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-04-12 17:48:06', '2026-04-12 17:48:06'),
(68, 1592, 3, 'Pending', 0, 'ngit-ngit dri sa purok 2 lain kaau sa dalan dili makita', 'Negative', NULL, 0, NULL, NULL, NULL, NULL, NULL, '2026-04-12 18:04:33', '2026-04-12 18:04:33');

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
(33, 64, 27, 'Resolved', '2026-02-22 14:58:56', '2026-02-22 14:59:37', '2026-02-22 14:59:48', ''),
(34, 65, 27, 'Resolved', '2026-02-22 15:00:38', NULL, '2026-02-22 15:00:54', ''),
(35, 67, NULL, 'Waiting', '2026-04-12 17:48:06', NULL, NULL, NULL),
(36, 68, 27, 'Pending', '2026-04-12 18:04:33', NULL, NULL, NULL);

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
(15, 1, 'Reniel Bantilan', '::1', '2026-04-13 03:21:32', NULL);

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
(25, 'Reniel Bantilan', '', 8, 1, 0.0, 0, '2026-02-22 14:18:33', '2026-02-22 15:13:29'),
(26, 'Aldrin Cabilic', '', 6, 1, 0.0, 0, '2026-02-22 14:18:49', '2026-02-22 15:13:29'),
(27, 'Ariel Jomoc', '', 3, 1, 0.0, 0, '2026-02-22 14:19:13', '2026-02-22 15:13:29'),
(28, 'John Digal', '', 5, 1, 0.0, 0, '2026-02-22 14:22:08', '2026-02-22 15:13:29');

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
(21, 'Example', 'example', 'Active', 1, NULL, '2025-04-13 00:00:00', '2026-04-14 00:00:00', '2026-04-12 18:03:23', '2026-04-12 18:03:23', NULL);

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
(56, 21, 'Firstname', 'text', NULL, 1, 1, '2026-04-12 18:03:38');

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
(10, 'Juan', 'Dela Cruz', '2', 'juan.delacruz', '$2y$10$iviaA0EX7f6buKQMcYykDu2u/zx1sriqhldLcYc11YFAxi1UNz.8K', 'juan@gmail.com', 'user', '2026-01-24 02:32:08', 1, NULL, 'light', 'en');

--
-- Indexes for dumped tables
--

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `feedback_assignments`
--
ALTER TABLE `feedback_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `personnel`
--
ALTER TABLE `personnel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `surveys`
--
ALTER TABLE `surveys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `survey_questions`
--
ALTER TABLE `survey_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `survey_responses`
--
ALTER TABLE `survey_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

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
