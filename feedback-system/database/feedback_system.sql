-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 27, 2026 at 01:36 PM
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
(38, 10, 3, 'Pending', 1, 'Pangit ang color sa streetlight diri sa purok 1', 'Negative', NULL, 1, 'Reniel Bantilan', '2026-01-24 19:25:44', '', NULL, NULL, '2026-01-24 02:43:59', '2026-01-24 11:25:44'),
(40, 8, 3, 'Pending', 1, 'Pangit ang sugo sa purok4', 'Negative', NULL, 1, 'Ana Garcia', '2026-01-24 19:25:51', '', NULL, NULL, '2026-01-24 02:52:05', '2026-01-24 11:25:51'),
(42, 7, 3, 'Pending', 5, 'pangit inyong streetlight', 'Negative', NULL, 1, 'Reniel Bantilan', '2026-01-24 19:26:45', '', NULL, NULL, '2026-01-24 02:55:06', '2026-01-24 12:40:48'),
(51, 7, 1, 'Pending', 5, 'hinay ang agas hugaw pa jud mga 3days nani', 'Negative', NULL, 1, 'Maria Santos', '2026-01-24 19:30:09', '', NULL, NULL, '2026-01-24 11:29:10', '2026-01-24 11:35:30'),
(53, 7, 2, 'Pending', 0, 'pangit hugaw', 'Negative', 'uploads/feedback_attachments/feedback_6974b17b233e8_1769255291.jpg', 0, NULL, NULL, NULL, NULL, NULL, '2026-01-24 11:48:11', '2026-01-24 11:48:11');

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
(13, 38, 23, 'Resolved', '2026-01-24 02:43:59', '2026-01-24 02:44:14', '2026-01-24 11:25:34', ''),
(15, 40, 4, 'Resolved', '2026-01-24 02:52:05', '2026-01-24 02:54:46', '2026-01-24 02:59:25', ''),
(17, 42, 23, 'Resolved', '2026-01-24 11:26:16', '2026-01-24 11:28:08', '2026-01-24 11:28:34', ''),
(25, 51, 10, 'Resolved', '2026-01-24 11:29:10', '2026-01-24 11:29:47', '2026-01-24 11:30:03', ''),
(26, 53, 3, 'Pending', '2026-01-24 11:48:11', NULL, NULL, NULL);

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
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

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
(3, 'Pedro Reyes', 'Waste management coordinator', 2, 1, 5.0, 0, '2026-01-22 12:20:49', '2026-01-22 14:10:33'),
(4, 'Ana Garcia', 'Electrical technician for street lighting', 3, 1, 1.0, 1, '2026-01-22 12:20:49', '2026-01-24 02:59:25'),
(5, 'Jose Mendoza', 'Sanitation and cleanliness officer', 5, 1, 5.0, 0, '2026-01-22 12:20:49', '2026-01-22 14:10:33'),
(6, 'Rosa Lopez', 'Health services coordinator', 6, 1, 5.0, 0, '2026-01-22 12:20:49', '2026-01-22 14:10:33'),
(9, 'Juan Dela Cruz', 'Road maintenance specialist with 5 years experience', 4, 1, 5.0, 0, '2026-01-22 13:01:35', '2026-01-22 14:10:33'),
(10, 'Maria Santos', 'Water system technician and plumber', 1, 1, 0.0, 1, '2026-01-22 13:01:35', '2026-01-24 11:30:03'),
(15, 'Carlos Rivera', 'Security and safety personnel', 8, 1, 5.0, 0, '2026-01-22 13:01:35', '2026-01-22 14:10:33'),
(16, 'Elena Cruz', 'General services - handles miscellaneous concerns', 9, 1, 5.0, 0, '2026-01-22 13:01:35', '2026-01-22 14:10:33'),
(23, 'Reniel Bantilan', '', 3, 1, 1.0, 2, '2026-01-23 00:30:38', '2026-01-24 11:26:40');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

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
(7, 'Aldrin', 'Bantilan', '2', 'renielbantilan00', '$2y$10$Rg/km79oJcwEVjh2/ivtKOKYVMTVLFqPadQqS6ZEMSyWZk6OD0PoO', 'renielbantlan69@gmail.com', 'user', '2025-12-28 21:47:12', 1, '../uploads/profiles/user_7_1766965382.jpg', 'dark', 'bisaya'),
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `feedback_assignments`
--
ALTER TABLE `feedback_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `personnel`
--
ALTER TABLE `personnel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `surveys`
--
ALTER TABLE `surveys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `survey_questions`
--
ALTER TABLE `survey_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `survey_responses`
--
ALTER TABLE `survey_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

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
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
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
  ADD CONSTRAINT `survey_responses_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `survey_responses_ibfk_3` FOREIGN KEY (`question_id`) REFERENCES `survey_questions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
