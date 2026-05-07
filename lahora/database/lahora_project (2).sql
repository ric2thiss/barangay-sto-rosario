-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 04, 2026 at 05:19 PM
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
-- Database: `lahora_project`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `customer_id` varchar(20) NOT NULL,
  `service_id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','confirmed','completed','cancelled','rejected','declined') DEFAULT 'pending',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `customer_id`, `service_id`, `customer_name`, `service_name`, `booking_date`, `status`, `notes`) VALUES
(1, '2026-0007', 1, 'keneth', 'Property Inspection', '2026-04-24 19:27:38', 'confirmed', 'test'),
(2, '2026-0007', 1, 'keneth', 'Property Inspection', '2026-04-27 02:38:14', 'confirmed', 'asdsad');

-- --------------------------------------------------------

--
-- Table structure for table `delete_requests`
--

CREATE TABLE `delete_requests` (
  `id` int(11) NOT NULL,
  `target_user_id` varchar(20) NOT NULL COMMENT 'idNo of user to be deleted',
  `target_username` varchar(100) NOT NULL,
  `target_role` varchar(20) NOT NULL,
  `requested_by` varchar(20) NOT NULL COMMENT 'idNo of super admin who requested',
  `requested_by_username` varchar(100) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` varchar(20) DEFAULT NULL COMMENT 'idNo of super admin who approved/rejected',
  `approved_by_username` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `status` enum('available','not_available') DEFAULT 'available',
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `title`, `description`, `price`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Property Inspection', 'Service', 14560.00, 'available', '0', '2026-04-24 14:05:14', '2026-04-24 14:05:14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `idNo` varchar(20) DEFAULT NULL,
  `firstName` varchar(100) NOT NULL,
  `middleName` varchar(100) DEFAULT NULL,
  `lastName` varchar(70) NOT NULL,
  `extension` varchar(10) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `sex` enum('Female','Male') DEFAULT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `emailAddress` varchar(100) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `purok` varchar(90) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `municipality` varchar(60) NOT NULL,
  `province` varchar(100) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `zipCode` varchar(10) DEFAULT NULL,
  `security_question1` varchar(255) DEFAULT NULL,
  `answer1` varchar(255) DEFAULT NULL,
  `security_question2` varchar(255) DEFAULT NULL,
  `answer2` varchar(255) DEFAULT NULL,
  `security_question3` varchar(255) DEFAULT NULL,
  `answer3` varchar(255) DEFAULT NULL,
  `otp_code` varchar(255) DEFAULT NULL,
  `otp_expiry` timestamp NULL DEFAULT NULL,
  `role` enum('super_admin','admin','customer') NOT NULL DEFAULT 'customer',
  `status` enum('active','blocked','pending','incomplete') DEFAULT 'pending',
  `is_logged_in` tinyint(1) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `device_used` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `idNo`, `firstName`, `middleName`, `lastName`, `extension`, `birthday`, `age`, `sex`, `username`, `emailAddress`, `profile_picture`, `password`, `purok`, `barangay`, `municipality`, `province`, `country`, `zipCode`, `security_question1`, `answer1`, `security_question2`, `answer2`, `security_question3`, `answer3`, `otp_code`, `otp_expiry`, `role`, `status`, `is_logged_in`, `ip_address`, `device_used`) VALUES
(1, '2026-0001', 'Ric Charles', '', 'Paquibot', '', '2001-07-23', 24, 'Male', 'ric2thiss', 'diana.villacorta@csucc.edu.ph', NULL, '$2y$10$4bAo7S.WXjbM6qgEqUs0m.A2RVvAotj0srUdznoeB7kh4Z9dmgVf.', 'Purok 2A', 'Ampayon', 'Butuan City', 'Agusan', 'Philippines', '8600', 'Who is your bestfriend in elementary?', '$2y$10$O1cYlpagInHkGsmP9.dJ8eG2YQQWzfOAHmDFUAT3ulTtkAC4XM4oS', 'What is the name of your favorite pet?', '$2y$10$EsYdrTbOloyOcpe5Qq1FOO7G6qbIh.v935xNJvsEF1AUVFo7oIcQG', 'Who is your favorite teacher in highschool?', '$2y$10$iTk2n0GpzT.mRzVaRfM8X.iVFynePsazsLtRSh40YJcIQHbeazyAK', '$2y$10$yIHHqy7kT.YMX4mqu2FGNuLAJ4QM.OrhJmSxkEuXLQW.Zr5RDcyb6', '2026-05-04 09:21:33', 'super_admin', 'active', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(2, '2026-0002', 'Trixxie Nicole', '', 'Petalcorin', '', '2002-10-16', 23, 'Female', 'trixxie', 'xielle021221@gmail.com', NULL, '$2y$10$1QFH5ggq2vDMJh75yPArPOxSSggD7.OhcjfmicYzXBfupBiqQiUH2', 'Purok 2', 'Ampayon', 'Butuan City', 'Agusan', 'Philippines', '8600', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin', 'incomplete', 0, NULL, NULL),
(3, '2026-0003', 'Angel Charm Xia', '', 'Paquibot', '', '2016-11-24', 9, 'Female', 'angel', 'angelcharm@gmail.com', NULL, '$2y$10$NvIWkZzdKmU.JM.mP3.V4Odzg0yqnYhNJu7OhrEPe2PIrATxgtU2S', 'Purok 2', 'Ampayon', 'Butuan City', 'Agusan', 'Philippines', '8600', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin', 'incomplete', 0, NULL, NULL),
(4, '2026-0004', 'Chariz Anne', '', 'Paquibot', '', '2000-08-12', 25, 'Female', 'charizanne', 'charizanne@gmail.com', NULL, '$2y$10$T2nY4E1SP415f72N4oss/O.tStrJAxWyUKI6UvYaSmbMRwWbdmOGC', 'Purok 3', 'Ampayon', 'Butuan City', 'Agusan', 'Philippines', '8605', 'Who was your childhood hero?', '$2y$10$HMEurfJRPflD1AZLbvOAMuvfrGkcaIcUD/Ty2iD.S8RZgF/azV5dC', 'What is the name of the street you grew up on?', '$2y$10$ndNXgHe0vAOFzQU6MLO/tu5YPo7cebsFeesbO3q7zl9FUeaXWaF1q', 'What is your favorite movie?', '$2y$10$GzkFIp/YRxQc8r/K3Vs/P.LWmEzmOc4Q/9lDKc9TssJFAom7XtrQO', NULL, NULL, 'admin', 'active', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(5, '2026-0005', 'Chayzel Rich', '', 'Paquibot', '', '2007-07-07', 18, 'Female', 'chayzelrich', 'rich@gmail.com', NULL, '$2y$10$lX.Eru1coYyn9XuTLzCLr.UeO3Xc02UtWHnmXASt85XrXOpoz6nPO', 'Purok 2', 'Ampayon', 'Butuan City', 'Agusan', 'Philippines', '8600', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin', 'incomplete', 0, NULL, NULL),
(6, '2026-0006', 'Chazseybless', '', 'Paquibot', '', '2004-09-15', 21, 'Female', 'chazseybless', 'chazseybless@gmail.com', NULL, '$2y$10$VTosURv7vq9Gs0qThlpyfuc9ckHRla3n32.L74be0gt1VxE5SkQMO', 'Purok 2', 'Ampayon', 'Butuan City', 'Agusan', 'Philippines', '8600', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin', 'incomplete', 0, NULL, NULL),
(7, '2026-0007', 'Keneth', '', 'Arsolon', '', '2000-02-14', 26, 'Male', 'keneth', 'keneth.arsolon@csucc.edu.ph', NULL, '$2y$10$D366HkRtq1cWqw425n44aOe.OkHVTUgOD35g8yHaXTpV3kurVstru', 'Purok 5', 'Ampayon', 'Butuan City', 'Agusan', 'Philippines', '8600', 'Who is your bestfriend in elementary?', '$2y$10$hkvf.3rWQxN1rjtCKJ2HC.DuqzD/mi09tSfs92W8LxpN3wv2miwKq', 'What is the name of your favorite pet?', '$2y$10$9I3jIrXAM1LRsf/7QPXBD.wNpk9ZTcLdqA4YvhoC9AaOEeYpTR8Dy', 'Who is your favorite teacher in highschool?', '$2y$10$UGEctTxzOaP1xTWCadOQz.6yP6FjpTOESbKbkNnibtEFScwNf.g9S', NULL, NULL, 'customer', 'active', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36'),
(8, '2026-0008', 'Charity', '', 'Locar', '', '1979-03-10', 47, 'Female', 'charity', 'charity@gmail.com', NULL, '$2y$10$IqnspLkK.lrcW1jBYhXs7uNcE7vthk4XxKstgGiXhT7VOna/XE8Gq', 'Purok 2', 'Ampayon', 'Butuan City', 'Agusan', 'Philippines', '8600', 'Who is your bestfriend in elementary?', '$2y$10$UscYl5DfcXFQdAKlm1WsReftPuMT7Fl6KSAaTAMSgi7vV2yNZXMVm', 'What is the name of your favorite pet?', '$2y$10$.L70RG96xFUI3y5QUgQ1Q.4G87mo8LC0lT9chKMa6f7e.uLmUXYoC', 'Who is your favorite teacher in highschool?', '$2y$10$z3cAdg3HmlTIByLX9jZhv.1I7POg7tnSefn90entaXE5msGSYY1dy', NULL, NULL, 'customer', 'active', 0, NULL, NULL),
(9, '2026-0009', 'John', '', 'Digal', '', '2001-01-02', 25, 'Male', 'johndigal', 'johndigal@gmail.com', NULL, '$2y$10$8k8imZTwbuc.vf1vwLRlD.ET8.zoMkBgu1nrBf9XciBnKyOT7K5KG', 'Purok', 'Barangay', 'Municipality', 'Province', 'Philippines', '8620', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin', 'incomplete', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_logs`
--

CREATE TABLE `user_logs` (
  `id` int(11) NOT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `device` varchar(100) DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_logs`
--

INSERT INTO `user_logs` (`id`, `user_name`, `action`, `description`, `ip_address`, `device`, `browser`, `created_at`) VALUES
(1, 'ric2thiss', 'LOGIN', 'User ric2thiss logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-24 13:04:24'),
(2, 'ric2thiss', 'UPDATE_USER', 'User ric2thiss updated user ric2thiss', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-24 13:05:39'),
(3, 'ric2thiss', 'CREATE_SERVICE', 'User ric2thiss created new service \'Property Inspection\'', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-24 14:05:14'),
(4, 'ric2thiss', 'LOGOUT', 'User ric2thiss logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-24 16:40:57'),
(5, 'ric2thiss', 'LOGIN', 'User ric2thiss logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-24 16:41:45'),
(6, 'ric2thiss', 'CREATE_USER', 'User ric2thiss created new user trixxie', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-24 16:51:03'),
(7, 'ric2thiss', 'CREATE_USER', 'User ric2thiss created new user angel', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-24 16:53:51'),
(8, 'ric2thiss', 'CREATE_USER', 'User ric2thiss created new user charizanne', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-24 17:09:33'),
(9, 'ric2thiss', 'LOGOUT', 'User ric2thiss logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-24 17:09:37'),
(10, 'charizanne', 'LOGIN', 'User charizanne logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-24 17:09:55'),
(11, 'charizanne', 'PROFILE_COMPLETE', 'Profile completed for user ID: 2026-0004', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-24 17:13:24'),
(12, 'charizanne', 'CREATE_USER', 'User charizanne created new user chayzelrich', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-24 17:17:20'),
(13, 'charizanne', 'CREATE_USER', 'User charizanne created new user chazseybless', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-24 17:19:48'),
(14, 'charizanne', 'LOGOUT', 'User charizanne logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-24 17:42:26'),
(15, 'ric2thiss', 'LOGIN', 'User ric2thiss logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-24 17:44:35'),
(16, 'ric2thiss', 'APPROVE_USER', 'User ric2thiss approved user ID 2026-0007', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-24 17:45:13'),
(17, 'ric2thiss', 'LOGOUT', 'User ric2thiss logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-24 17:45:24'),
(18, 'ric2thiss', 'LOGIN', 'User ric2thiss logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-24 17:45:32'),
(19, 'ric2thiss', 'LOGOUT', 'User ric2thiss logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-24 18:00:31'),
(20, 'keneth', 'LOGIN', 'User keneth logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-24 18:00:39'),
(21, 'keneth', 'LOGOUT', 'User keneth logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-24 19:27:44'),
(22, 'ric2thiss', 'LOGIN', 'User ric2thiss logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-24 19:28:00'),
(23, 'ric2thiss', 'LOGIN', 'User ric2thiss logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 08:09:18'),
(24, 'ric2thiss', 'UPDATE_USER', 'User ric2thiss updated user charizanne', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 08:10:41'),
(25, 'ric2thiss', 'UPDATE_USER', 'User ric2thiss updated user charizanne', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 08:21:20'),
(26, 'ric2thiss', 'LOGOUT', 'User ric2thiss logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 08:30:00'),
(27, 'ric2thiss', 'LOGIN', 'User ric2thiss logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 08:30:11'),
(28, 'ric2thiss', 'LOGOUT', 'User ric2thiss logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 08:30:28'),
(29, 'ric2thiss', 'LOGIN', 'User ric2thiss logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 08:30:33'),
(30, 'ric2thiss', 'LOGOUT', 'User ric2thiss logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 08:30:41'),
(31, 'charizanne', 'LOGIN', 'User charizanne logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 08:30:56'),
(32, 'charizanne', 'LOGOUT', 'User charizanne logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 08:39:34'),
(33, 'ric2thiss', 'LOGIN', 'User ric2thiss logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 08:45:51'),
(34, 'ric2thiss', 'LOGOUT', 'User ric2thiss logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 08:47:06'),
(35, 'keneth', 'LOGIN', 'User keneth logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 08:51:18'),
(36, 'keneth', 'LOGOUT', 'User keneth logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 08:54:07'),
(37, 'ric2thiss', 'FAILED_LOGIN', 'Failed login attempt for user \'ric2thiss\' (Invalid password)', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 08:54:18'),
(38, 'ric2thiss', 'FAILED_LOGIN', 'Failed login attempt for user \'ric2thiss\' (Invalid password)', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 08:54:20'),
(39, 'ric2thiss', 'FAILED_LOGIN', 'Failed login attempt for user \'ric2thiss\' (Invalid password)', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 08:54:22'),
(40, 'ric2thiss', 'LOGIN', 'User ric2thiss logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 08:58:22'),
(41, 'ric2thiss', 'BLOCK_USER', 'User ric2thiss blocked user charizanne', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 08:59:56'),
(42, 'ric2thiss', 'UNBLOCK_USER', 'User ric2thiss unblocked user charizanne', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 09:00:22'),
(43, 'ric2thiss', 'LOGOUT', 'User ric2thiss logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 09:00:29'),
(44, 'charity', 'USER_REGISTRATION', 'New user registered with username \'charity\' and ID \'2026-0008\'', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 09:02:15'),
(45, 'ric2thiss', 'FAILED_LOGIN', 'Failed login attempt for user \'ric2thiss\' (Invalid password)', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 09:02:26'),
(46, 'ric2thiss', 'LOGIN', 'User ric2thiss logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 09:02:33'),
(47, 'ric2thiss', 'LOGOUT', 'User ric2thiss logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-25 09:03:57'),
(48, 'ric2thiss', 'LOGIN', 'User ric2thiss logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-26 00:39:13'),
(49, 'ric2thiss', 'LOGOUT', 'User ric2thiss logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-26 02:28:18'),
(50, 'ric2thiss', 'LOGIN', 'User ric2thiss logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-27 01:59:04'),
(51, 'ric2thiss', 'LOGOUT', 'User ric2thiss logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-27 01:59:34'),
(52, 'ric2thiss', 'FAILED_LOGIN', 'Failed login attempt for user \'ric2thiss\' (Invalid password)', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-27 01:59:39'),
(53, 'ric2thiss', 'FAILED_LOGIN', 'Failed login attempt for user \'ric2thiss\' (Invalid password)', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-27 01:59:41'),
(54, 'ric2thiss', 'LOGIN', 'User ric2thiss logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-27 01:59:48'),
(55, 'ric2thiss', 'LOGOUT', 'User ric2thiss logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-27 02:26:59'),
(56, 'keneth', 'LOGIN', 'User keneth logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-27 02:27:31'),
(57, 'keneth', 'LOGOUT', 'User keneth logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-27 02:32:50'),
(58, 'ric2thiss', 'LOGIN', 'User ric2thiss logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-27 02:32:59'),
(59, 'ric2thiss', 'LOGOUT', 'User ric2thiss logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-27 02:34:55'),
(60, 'keneth', 'LOGIN', 'User keneth logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-27 02:35:04'),
(61, 'keneth', 'LOGOUT', 'User keneth logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-27 02:36:03'),
(62, 'keneth', 'LOGIN', 'User keneth logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-27 02:38:04'),
(63, 'keneth', 'CREATE_BOOKING', 'User keneth booked service \'Property Inspection\' for keneth', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-27 02:38:14'),
(64, 'keneth', 'LOGOUT', 'User keneth logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-27 02:38:35'),
(65, 'ric2thiss', 'LOGIN', 'User ric2thiss logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-27 02:40:11'),
(66, 'ric2thiss', 'CONFIRM_BOOKING', 'User ric2thiss confirmed booking for keneth - Property Inspection', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-27 02:49:57'),
(67, 'ric2thiss', 'CONFIRM_BOOKING', 'User ric2thiss confirmed booking for keneth - Property Inspection', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-27 02:50:01'),
(68, 'ric2thiss', 'LOGOUT', 'User ric2thiss logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-27 02:50:04'),
(69, 'keneth', 'LOGIN', 'User keneth logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-27 02:50:11'),
(70, 'keneth', 'LOGOUT', 'User keneth logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-27 02:50:50'),
(71, 'ric2thiss', 'LOGIN', 'User ric2thiss logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-27 02:50:55'),
(72, 'ric2thiss', 'APPROVE_USER', 'User ric2thiss approved user ID 2026-0008', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-27 04:11:38'),
(73, 'charizanne', 'LOGIN', 'User charizanne logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-28 03:41:22'),
(74, 'charizanne', 'LOGOUT', 'User charizanne logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-04-28 03:42:43'),
(75, 'ric2thiss', 'LOGOUT', 'User ric2thiss logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-05-04 11:40:20'),
(76, 'ric2thiss', 'LOGIN', 'User ric2thiss logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-05-04 11:47:04'),
(77, 'ric2thiss', 'LOGOUT', 'User ric2thiss logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-05-04 11:51:06'),
(78, 'ric2thiss', 'LOGIN', 'User ric2thiss logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-05-04 11:57:08'),
(79, 'ric2thiss', 'LOGOUT', 'User ric2thiss logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-05-04 12:29:42'),
(80, 'ric2thiss', 'LOGIN', 'User ric2thiss logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-05-04 12:33:18'),
(81, 'ric2thiss', 'LOGOUT', 'User ric2thiss logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-05-04 12:33:28'),
(82, 'ric2thiss', 'LOGIN', 'User ric2thiss logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-05-04 12:35:29'),
(83, 'ric2thiss', 'LOGOUT', 'User ric2thiss logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-05-04 12:35:37'),
(84, 'keneth', 'LOGIN', 'User keneth logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-05-04 12:37:46'),
(85, 'keneth', 'LOGOUT', 'User keneth logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-05-04 12:39:23'),
(86, 'ric2thiss', 'LOGIN', 'User ric2thiss logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-05-04 12:39:41'),
(87, 'ric2thiss', 'CREATE_USER', 'User ric2thiss created new user johndigal', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-05-04 12:42:13'),
(88, 'ric2thiss', 'LOGOUT', 'User ric2thiss logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-05-04 12:48:53'),
(89, 'keneth', 'LOGIN', 'User keneth logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-05-04 12:49:02'),
(90, 'keneth', 'LOGOUT', 'User keneth logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-05-04 12:54:19'),
(91, 'ric2thiss', 'FAILED_LOGIN', 'Failed login attempt for user \'ric2thiss\' (Invalid password)', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-05-04 13:20:46'),
(92, 'ric2thiss', 'PASSWORD_RESET', 'User ric2thiss reset their password via Forgot Password', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-05-04 13:27:25'),
(93, 'ric2thiss', 'LOGIN', 'User ric2thiss logged in successfully', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-05-04 13:48:15'),
(94, 'ric2thiss', 'LOGOUT', 'User ric2thiss logged out', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-05-04 13:48:34'),
(95, 'ric2thiss', 'FAILED_LOGIN', 'Failed login attempt for user \'ric2thiss\' (Invalid password)', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-05-04 14:31:47'),
(96, 'ric2thiss', 'FAILED_LOGIN', 'Failed login attempt for user \'ric2thiss\' (Invalid password)', '::1', 'Windows', 'Google Chrome 147.0.0.0', '2026-05-04 14:31:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `delete_requests`
--
ALTER TABLE `delete_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_target` (`target_user_id`),
  ADD KEY `idx_requester` (`requested_by`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_address` (`emailAddress`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `idNo` (`idNo`);

--
-- Indexes for table `user_logs`
--
ALTER TABLE `user_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_name` (`user_name`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `delete_requests`
--
ALTER TABLE `delete_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_logs`
--
ALTER TABLE `user_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
