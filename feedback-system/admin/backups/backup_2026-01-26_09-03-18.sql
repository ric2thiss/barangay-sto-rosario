-- Feedback System Database Backup
-- Generated: 2026-01-26 09:03:18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Table structure for table `admins`
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Dumping data for table `admins`
INSERT INTO `admins` VALUES
("1","Reniel","Pontejos","Bantilan","reniel.bantilan","reniel.bantilan@csucc.edu.ph","$2y$10$bxfCh2T4yTF2VcJHnhrwHepdLiwYX268vkxaPVvG2GRDIP.gjEiOi","2025-12-27 20:44:12","superadmin","","1","2026-01-22 11:37:59"),
("2","Jonah May","S","Hinautan","jonahmay.hinautan","jonahmay.hinautan@csucc.edu.ph","$2y$10$hozBORg3Cwg.GRDJexke7udo/91z9v1n4u6nw26myjTkMqyd56S5y","2025-12-27 22:49:07","admin","","1","2026-01-24 17:30:25"),
("3","Aldrin","","Cabilic","aldrin.cabilic","aldrin.cabilic@gmail.com","$2y$10$uuLmx2ku7/ojRNTKfGK1NONetbbpKLRxMfMbfLdBUG3/RMEEiAcuq","2026-01-22 11:32:47","admin","","1","2026-01-22 11:32:47");

-- Table structure for table `categories`
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(10) DEFAULT '?',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `categories`
INSERT INTO `categories` VALUES
("1","Water","Water supply, quality, and related services","💧","1","2025-12-19 10:41:55"),
("2","Waste","Waste management and garbage collection","🗑️","1","2025-12-19 10:41:55"),
("3","Streetlight","Street lighting and public lighting","💡","1","2025-12-19 10:41:55"),
("4","Roads","Road conditions, maintenance, and repairs","🛣️","1","2025-12-19 10:41:55"),
("5","Sanitation","Public sanitation facilities and cleanliness","🧹","1","2025-12-19 10:41:55"),
("6","Health","Health services and facilities","🏥","1","2025-12-19 10:41:55"),
("8","Security","Public safety and security","👮","1","2025-12-19 10:41:55"),
("9","Others","Other public services and concerns","📋","1","2025-12-19 10:41:55");

-- Table structure for table `feedback`
DROP TABLE IF EXISTS `feedback`;
CREATE TABLE `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`id`),
  KEY `idx_feedback_user` (`user_id`),
  KEY `idx_feedback_category` (`category_id`),
  KEY `idx_feedback_sentiment` (`sentiment`),
  KEY `idx_feedback_created` (`created_at`),
  CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `feedback`
INSERT INTO `feedback` VALUES
("38","10","3","Pending","1","Pangit ang color sa streetlight diri sa purok 1","Negative","","1","Reniel Bantilan","2026-01-24 19:25:44","","","","2026-01-24 10:43:59","2026-01-24 19:25:44"),
("40","8","3","Pending","1","Pangit ang sugo sa purok4","Negative","","1","Ana Garcia","2026-01-24 19:25:51","","","","2026-01-24 10:52:05","2026-01-24 19:25:51"),
("42","7","3","Pending","5","pangit inyong streetlight","Negative","","1","Reniel Bantilan","2026-01-24 19:26:45","","","","2026-01-24 10:55:06","2026-01-24 20:40:48"),
("51","7","1","Pending","5","hinay ang agas hugaw pa jud mga 3days nani","Negative","","1","Maria Santos","2026-01-24 19:30:09","","","","2026-01-24 19:29:10","2026-01-24 19:35:30"),
("53","7","2","Pending","0","pangit hugaw","Negative","uploads/feedback_attachments/feedback_6974b17b233e8_1769255291.jpg","0","","","","","","2026-01-24 19:48:11","2026-01-24 19:48:11");

-- Table structure for table `feedback_assignments`
DROP TABLE IF EXISTS `feedback_assignments`;
CREATE TABLE `feedback_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `feedback_id` int(11) NOT NULL,
  `personnel_id` int(11) DEFAULT NULL,
  `status` enum('Pending','In Progress','Resolved','Waiting') DEFAULT 'Pending',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_feedback_assignment` (`feedback_id`),
  KEY `idx_assignment_personnel` (`personnel_id`),
  KEY `idx_assignment_status` (`status`),
  CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`feedback_id`) REFERENCES `feedback` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `feedback_assignments`
INSERT INTO `feedback_assignments` VALUES
("13","38","23","Resolved","2026-01-24 10:43:59","2026-01-24 10:44:14","2026-01-24 19:25:34",""),
("15","40","4","Resolved","2026-01-24 10:52:05","2026-01-24 10:54:46","2026-01-24 10:59:25",""),
("17","42","23","Resolved","2026-01-24 19:26:16","2026-01-24 19:28:08","2026-01-24 19:28:34",""),
("25","51","10","Resolved","2026-01-24 19:29:10","2026-01-24 19:29:47","2026-01-24 19:30:03",""),
("26","53","3","Pending","2026-01-24 19:48:11","","","");

-- Table structure for table `login_attempts`
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_success` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ip_address` (`ip_address`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Dumping data for table `login_attempts`
INSERT INTO `login_attempts` VALUES
("1","::1","2026-01-11 08:22:32","0"),
("2","::1","2026-01-11 08:22:35","0"),
("3","::1","2026-01-11 08:22:38","0"),
("4","::1","2026-01-11 08:22:41","0");

-- Table structure for table `password_resets`
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Dumping data for table `password_resets`
-- No data for table `password_resets`

-- Table structure for table `personnel`
DROP TABLE IF EXISTS `personnel`;
CREATE TABLE `personnel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `specialization_id` int(11) NOT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `star_rating` decimal(2,1) DEFAULT 0.0,
  `total_completed` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_personnel_specialization` (`specialization_id`),
  KEY `idx_personnel_availability` (`is_available`),
  CONSTRAINT `personnel_ibfk_1` FOREIGN KEY (`specialization_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `personnel`
INSERT INTO `personnel` VALUES
("3","Pedro Reyes","Waste management coordinator","2","1","5.0","0","2026-01-22 20:20:49","2026-01-22 22:10:33"),
("4","Ana Garcia","Electrical technician for street lighting","3","1","1.0","1","2026-01-22 20:20:49","2026-01-24 10:59:25"),
("5","Jose Mendoza","Sanitation and cleanliness officer","5","1","5.0","0","2026-01-22 20:20:49","2026-01-22 22:10:33"),
("6","Rosa Lopez","Health services coordinator","6","1","5.0","0","2026-01-22 20:20:49","2026-01-22 22:10:33"),
("9","Juan Dela Cruz","Road maintenance specialist with 5 years experience","4","1","5.0","0","2026-01-22 21:01:35","2026-01-22 22:10:33"),
("10","Maria Santos","Water system technician and plumber","1","1","0.0","1","2026-01-22 21:01:35","2026-01-24 19:30:03"),
("15","Carlos Rivera","Security and safety personnel","8","1","5.0","0","2026-01-22 21:01:35","2026-01-22 22:10:33"),
("16","Elena Cruz","General services - handles miscellaneous concerns","9","1","5.0","0","2026-01-22 21:01:35","2026-01-22 22:10:33"),
("23","Reniel Bantilan","","3","1","1.0","2","2026-01-23 08:30:38","2026-01-24 19:26:40");

-- Table structure for table `settings`
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Dumping data for table `settings`
INSERT INTO `settings` VALUES
("6","maintenance_mode","0","2026-01-03 13:02:50","2026-01-25 18:50:33");

-- Table structure for table `survey_questions`
DROP TABLE IF EXISTS `survey_questions`;
CREATE TABLE `survey_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `survey_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('text','rating','multiple_choice','yes_no') DEFAULT 'text',
  `options` text DEFAULT NULL COMMENT 'JSON array of options for multiple_choice',
  `order_num` int(11) DEFAULT 0,
  `is_required` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `survey_id` (`survey_id`),
  CONSTRAINT `survey_questions_ibfk_1` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Dumping data for table `survey_questions`
-- No data for table `survey_questions`

-- Table structure for table `survey_responses`
DROP TABLE IF EXISTS `survey_responses`;
CREATE TABLE `survey_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `survey_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `survey_id` (`survey_id`),
  KEY `user_id` (`user_id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `survey_responses_ibfk_1` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE,
  CONSTRAINT `survey_responses_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `survey_responses_ibfk_3` FOREIGN KEY (`question_id`) REFERENCES `survey_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Dumping data for table `survey_responses`
-- No data for table `survey_responses`

-- Table structure for table `surveys`
DROP TABLE IF EXISTS `surveys`;
CREATE TABLE `surveys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Draft','Active','Closed') DEFAULT 'Draft',
  `created_by` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `assigned_to` (`assigned_to`),
  CONSTRAINT `surveys_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `surveys_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Dumping data for table `surveys`
-- No data for table `surveys`

-- Table structure for table `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `language` varchar(10) DEFAULT 'en',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_type` (`user_type`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `users`
INSERT INTO `users` VALUES
("7","Aldrin","Bantilan","2","renielbantilan00","$2y$10$Rg/km79oJcwEVjh2/ivtKOKYVMTVLFqPadQqS6ZEMSyWZk6OD0PoO","renielbantlan69@gmail.com","user","2025-12-29 05:47:12","1","../uploads/profiles/user_7_1766965382.jpg","dark","bisaya"),
("8","Aldrin","Cabilic","4","aldrin.cabilic","$2y$10$12ZZ3IiwEhdIzTLCyu0Qw.rqc1kcZRJg2VBY.IP1XiaBFwsGh8u3K","aldrin.cabilic@gmail.com","user","2026-01-02 19:44:05","0","../uploads/profiles/user_8_1767403248.webp","light","en"),
("10","Juan","Dela Cruz","2","juan.delacruz","$2y$10$iviaA0EX7f6buKQMcYykDu2u/zx1sriqhldLcYc11YFAxi1UNz.8K","juan@gmail.com","user","2026-01-24 10:32:08","1","","light","en");

