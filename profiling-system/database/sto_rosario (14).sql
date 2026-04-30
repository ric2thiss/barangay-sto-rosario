-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 13, 2026 at 10:58 AM
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
-- Database: `sto_rosario`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('admin','resident','staff') NOT NULL DEFAULT 'resident',
  `username` varchar(100) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `action` enum('login','logout','session_expired') NOT NULL DEFAULT 'login',
  `login_at` datetime NOT NULL DEFAULT current_timestamp(),
  `logout_at` datetime DEFAULT NULL,
  `duration_sec` int(11) DEFAULT NULL COMMENT 'logout_at - login_at in seconds',
  `ip_address` varchar(45) NOT NULL DEFAULT '' COMMENT 'IPv4 or IPv6',
  `country` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,6) DEFAULT NULL,
  `longitude` decimal(10,6) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `device_type` enum('Desktop','Mobile','Tablet','Unknown') NOT NULL DEFAULT 'Unknown',
  `os` varchar(100) DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `status` enum('online','offline','expired') NOT NULL DEFAULT 'online',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `user_type`, `username`, `full_name`, `action`, `login_at`, `logout_at`, `duration_sec`, `ip_address`, `country`, `city`, `region`, `latitude`, `longitude`, `user_agent`, `device_type`, `os`, `browser`, `status`, `created_at`) VALUES
(1, 1573, 'resident', 'Lian', 'Lian Gonzaga', 'logout', '2026-03-22 14:10:35', '2026-03-22 14:16:48', 373, '::1', 'Local', 'Local', 'Local', NULL, NULL, '0', 'Desktop', 'Windows 10/11', 'Chrome 146', 'offline', '2026-03-22 13:10:35'),
(2, 1573, 'resident', 'Lian', 'Lian Gonzaga', 'logout', '2026-03-22 15:22:39', '2026-03-22 15:22:46', 7, '::1', 'Local', 'Local', 'Local', NULL, NULL, '0', 'Desktop', 'Windows 10/11', 'Chrome 146', 'offline', '2026-03-22 14:22:39'),
(3, 1, 'admin', 'Admin', 'Admin', 'session_expired', '2026-03-22 15:23:01', '2026-03-24 11:28:34', 158733, '::1', 'Local', 'Local', 'Local', NULL, NULL, '0', 'Desktop', 'Windows 10/11', 'Chrome 146', 'expired', '2026-03-22 14:23:01'),
(4, 1, 'admin', 'Admin', 'Admin', 'session_expired', '2026-03-24 04:02:48', '2026-03-30 13:25:56', 552188, '::1', 'Local', 'Local', 'Local', NULL, NULL, '0', 'Desktop', 'Windows 10/11', 'Chrome 146', 'expired', '2026-03-24 03:02:49'),
(5, 1, 'admin', 'Admin', 'Admin', 'session_expired', '2026-03-30 07:15:06', '2026-04-04 15:03:16', 460090, '::1', 'Local', 'Local', 'Local', NULL, NULL, '0', 'Desktop', 'Windows 10/11', 'Chrome 146', 'expired', '2026-03-30 05:15:06'),
(6, 1, 'admin', 'Admin', 'Admin', 'session_expired', '2026-04-04 08:48:06', '2026-04-05 13:54:28', 104782, '::1', 'Local', 'Local', 'Local', NULL, NULL, '0', 'Desktop', 'Windows 10/11', 'Chrome 146', 'expired', '2026-04-04 06:48:06'),
(7, 1, 'admin', 'Admin', 'Admin', 'session_expired', '2026-04-05 07:02:28', '2026-04-05 16:59:55', 35847, '::1', 'Local', 'Local', 'Local', NULL, NULL, '0', 'Desktop', 'Windows 10/11', 'Chrome 146', 'expired', '2026-04-05 05:02:28'),
(8, 1577, 'resident', 'Kian', 'Kian Saga', 'logout', '2026-04-05 07:49:22', '2026-04-05 07:50:35', 73, '::1', 'Local', 'Local', 'Local', NULL, NULL, '0', 'Desktop', 'Windows 10/11', 'Chrome 146', 'offline', '2026-04-05 05:49:22'),
(9, 1577, 'resident', 'Kian', 'Kian Saga', 'logout', '2026-04-05 07:50:41', '2026-04-05 07:51:00', 19, '::1', 'Local', 'Local', 'Local', NULL, NULL, '0', 'Desktop', 'Windows 10/11', 'Chrome 146', 'offline', '2026-04-05 05:50:41'),
(10, 1, 'admin', 'Admin', 'Admin', 'session_expired', '2026-04-05 10:56:56', '2026-04-06 06:54:27', 71851, '::1', 'Local', 'Local', 'Local', NULL, NULL, '0', 'Desktop', 'Windows 10/11', 'Chrome 146', 'expired', '2026-04-05 08:56:56'),
(11, 1588, 'resident', 'Nanase', 'Nanase Kuro', 'logout', '2026-04-05 11:21:06', '2026-04-05 13:17:26', 6980, '::1', 'Local', 'Local', 'Local', NULL, NULL, '0', 'Desktop', 'Windows 10/11', 'Chrome 146', 'offline', '2026-04-05 09:21:06'),
(12, 1, 'admin', 'Admin', 'Admin', 'logout', '2026-04-05 13:17:33', '2026-04-05 13:17:42', 9, '::1', 'Local', 'Local', 'Local', NULL, NULL, '0', 'Desktop', 'Windows 10/11', 'Chrome 146', 'offline', '2026-04-05 11:17:33'),
(13, 1, 'admin', 'Admin', 'Admin', 'session_expired', '2026-04-05 14:18:32', '2026-04-06 06:54:27', 59755, '::1', 'Local', 'Local', 'Local', NULL, NULL, '0', 'Desktop', 'Windows 10/11', 'Chrome 146', 'expired', '2026-04-05 12:18:32'),
(14, 1, 'admin', 'Admin', 'Admin', 'session_expired', '2026-04-05 23:41:10', '2026-04-06 07:43:19', 28929, '::1', 'Local', 'Local', 'Local', NULL, NULL, '0', 'Desktop', 'Windows 10/11', 'Chrome 146', 'expired', '2026-04-05 21:41:10'),
(15, 1590, 'resident', 'Kian1', 'Kian Saga', 'logout', '2026-04-06 01:11:54', '2026-04-06 03:01:09', 6555, '::1', 'Local', 'Local', 'Local', NULL, NULL, '0', 'Desktop', 'Windows 10/11', 'Chrome 146', 'offline', '2026-04-05 23:11:54'),
(16, 1, 'admin', 'Admin', 'Admin', 'session_expired', '2026-04-06 03:01:18', '2026-04-06 15:26:05', 44687, '::1', 'Local', 'Local', 'Local', NULL, NULL, '0', 'Desktop', 'Windows 10/11', 'Chrome 146', 'expired', '2026-04-06 01:01:18'),
(17, 1, 'admin', 'Admin', 'Admin', 'session_expired', '2026-04-06 06:23:17', '2026-04-06 15:26:05', 32568, '::1', 'Local', 'Local', 'Local', NULL, NULL, '0', 'Desktop', 'Windows 10/11', 'Chrome 146', 'expired', '2026-04-06 04:23:18'),
(18, 1, 'admin', 'Admin', 'Admin', 'session_expired', '2026-04-08 16:51:32', '2026-04-09 23:48:41', 111429, '::1', 'Local', 'Local', 'Local', NULL, NULL, '0', 'Desktop', 'Windows 10/11', 'Chrome 146', 'expired', '2026-04-08 14:51:32'),
(19, 1, 'admin', 'Admin', 'Admin', 'login', '2026-04-09 17:48:22', NULL, NULL, '::1', 'Local', 'Local', 'Local', NULL, NULL, '0', 'Desktop', 'Windows 10/11', 'Chrome 146', 'online', '2026-04-09 15:48:22'),
(20, 1, 'admin', 'Admin', 'Admin', 'login', '2026-04-09 17:57:28', NULL, NULL, '::1', 'Local', 'Local', 'Local', NULL, NULL, '0', 'Desktop', 'Windows 10/11', 'Chrome 146', 'online', '2026-04-09 15:57:28');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `full_name`, `username`, `password`, `last_login`, `last_login_ip`) VALUES
(1, 'Admin', 'Admin', '$2y$12$QQVKiPbPHamGK7NvBeVbXORjHHqk5tP2MFpZJDkq5rXWu61P.FYeq', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `barangay_official`
--

CREATE TABLE `barangay_official` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `surname` varchar(100) NOT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `birthdate` date NOT NULL,
  `birthplace` varchar(255) NOT NULL,
  `age` int(11) NOT NULL,
  `sex` enum('Male','Female') NOT NULL,
  `civil_status` enum('Single','Married','Widowed','Separated','Annulled','Live-in') NOT NULL,
  `nationality` varchar(100) DEFAULT 'Filipino',
  `contact_no` varchar(20) DEFAULT NULL,
  `purok` varchar(100) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `municipality` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `chairmanship` varchar(255) DEFAULT NULL,
  `occupation` varchar(255) DEFAULT NULL,
  `monthly_income` decimal(10,2) DEFAULT 0.00,
  `annual_income` decimal(10,2) DEFAULT 0.00,
  `household_no` varchar(50) DEFAULT NULL,
  `household_position` varchar(50) DEFAULT NULL,
  `total_household` int(11) DEFAULT 1,
  `voters_status` enum('Yes','No') DEFAULT 'No',
  `educational_attainment` varchar(100) NOT NULL,
  `term_start` date NOT NULL,
  `term_end` date NOT NULL,
  `status` enum('Active','Inactive','On Leave','Retired') DEFAULT 'Active',
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `image_path` varchar(255) DEFAULT 'default.jpg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `occupation_type` varchar(100) DEFAULT NULL,
  `socioeconomic_status` varchar(50) DEFAULT NULL,
  `pwd_type` varchar(200) DEFAULT NULL,
  `is_pwd` enum('Yes','No') NOT NULL DEFAULT 'No',
  `pwd_details` text DEFAULT NULL,
  `blood_type` varchar(10) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `ethnicity` varchar(100) DEFAULT NULL,
  `philhealth_no` varchar(50) DEFAULT NULL,
  `membership_type` varchar(50) DEFAULT NULL,
  `length_of_residency` int(11) DEFAULT NULL,
  `years_in_service` int(11) DEFAULT NULL,
  `grade_level` varchar(50) DEFAULT NULL,
  `school_name` varchar(255) DEFAULT NULL,
  `house_ownership` varchar(100) DEFAULT NULL,
  `house_material` varchar(100) DEFAULT NULL,
  `toilet_type` varchar(100) DEFAULT NULL,
  `water_source` varchar(100) DEFAULT NULL,
  `is_4ps` enum('Yes','No') NOT NULL DEFAULT 'No',
  `is_nhts` enum('Yes','No') NOT NULL DEFAULT 'No',
  `is_solo_parent` enum('Yes','No') NOT NULL DEFAULT 'No',
  `family_planning` enum('Yes','No') NOT NULL DEFAULT 'No',
  `is_deceased` enum('Yes','No') NOT NULL DEFAULT 'No',
  `date_of_death` date DEFAULT NULL,
  `is_smoker` enum('Yes','No') NOT NULL DEFAULT 'No',
  `is_binge_drinker` enum('Yes','No') NOT NULL DEFAULT 'No',
  `has_hypertension` enum('Yes','No') NOT NULL DEFAULT 'No',
  `has_diabetes` enum('Yes','No') NOT NULL DEFAULT 'No',
  `has_asthma` enum('Yes','No') NOT NULL DEFAULT 'No',
  `has_tb` enum('Yes','No') NOT NULL DEFAULT 'No',
  `has_cancer` enum('Yes','No') NOT NULL DEFAULT 'No',
  `has_mental_health` enum('Yes','No') NOT NULL DEFAULT 'No'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barangay_official`
--

INSERT INTO `barangay_official` (`id`, `first_name`, `middle_name`, `surname`, `suffix`, `birthdate`, `birthplace`, `age`, `sex`, `civil_status`, `nationality`, `contact_no`, `purok`, `barangay`, `municipality`, `province`, `position`, `chairmanship`, `occupation`, `monthly_income`, `annual_income`, `household_no`, `household_position`, `total_household`, `voters_status`, `educational_attainment`, `term_start`, `term_end`, `status`, `username`, `password`, `image_path`, `created_at`, `updated_at`, `occupation_type`, `socioeconomic_status`, `pwd_type`, `is_pwd`, `pwd_details`, `blood_type`, `religion`, `ethnicity`, `philhealth_no`, `membership_type`, `length_of_residency`, `years_in_service`, `grade_level`, `school_name`, `house_ownership`, `house_material`, `toilet_type`, `water_source`, `is_4ps`, `is_nhts`, `is_solo_parent`, `family_planning`, `is_deceased`, `date_of_death`, `is_smoker`, `is_binge_drinker`, `has_hypertension`, `has_diabetes`, `has_asthma`, `has_tb`, `has_cancer`, `has_mental_health`) VALUES
(7, 'Pedro', 'Mwa', 'Penduko', 'Jr.', '1992-11-11', 'Davao City', 33, 'Male', 'Married', 'Filipino', '09473560830', 'Purok 2', 'Santo Rosario', 'Magallanes', 'Agusan Del Norte', 'Barangay Health Worker', 'Health and Sanitation', 'Barangay Officials', 80000.00, 960000.00, '008', 'Father', 3, 'Yes', 'Post Graduate', '2024-01-01', '2028-01-01', 'Active', 'Pedro', '$2y$10$GBfbOft7LOa5HZvSh/1J/eZYYfqFvwEjy3ojlD1OsLWGRY7wYJ31m', '1775428877_69d2e50d8496b_cam.jpg', '2026-04-05 22:41:17', '2026-04-05 22:41:17', 'Government Employee', 'Upper Middle Income', NULL, 'No', NULL, 'AB+', 'Born Again Christian', 'Cebuano', NULL, NULL, 10, 2, NULL, NULL, 'Owned', 'Concrete', 'With Flush', 'Level 3 (Piped)', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No'),
(8, 'Helen', 'Mondragon', 'Gonzaga', NULL, '1991-11-11', 'Davao City', 34, 'Female', 'Married', 'Filipino', '+639473560822', 'Purok 2', 'Santo Rosario', 'Magallanes', 'Agusan Del Norte', 'Barangay Health Worker', 'Health and Sanitation', 'Barangay Officials', 80000.00, 960000.00, '002', 'Mother', 4, 'Yes', 'Post Graduate', '2024-11-11', '2028-11-11', 'Active', 'Helen', '$2y$10$1mdjUJUQK0IV7wcNwApmuO9WkeAgnQr7BTdaJVF8C/MTmei0.S//y', '1775434571_69d2fb4bced4b_cam.jpg', '2026-04-06 00:16:11', '2026-04-06 00:51:11', 'Government Employee', 'Upper Middle Income', NULL, 'No', NULL, 'O+', 'Roman Catholic', 'Cebuano', NULL, NULL, 8, 1, NULL, NULL, 'Owned', 'Concrete', 'With Flush', 'Level 3 (Piped)', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No'),
(9, 'Kian', 'Datahan', 'Saga', NULL, '2003-11-11', 'Sta.Cruz Manila', 22, 'Male', 'Single', 'Filipino', '+639473560830', 'Purok 5', 'Santo Rosario', 'Magallanes', 'Agusan Del Norte', 'Barangay Captain', 'Peace and Order', 'Barangay Officials', 90000.00, 1080000.00, '110', 'Son', 5, 'Yes', 'Post Graduate', '2024-11-11', '2026-11-22', 'Active', 'Kian3', '$2y$10$kPycPzI/nwUAZqas5r8oROyhIVQnXYH1ZHiKzbhRRboJ1pp2w44pK', '1775456389_69d35085c513d_cam.jpg', '2026-04-06 06:19:49', '2026-04-06 06:19:49', 'Government Employee', 'Upper Middle Income', NULL, 'No', NULL, 'Unknown', 'Iglesia ni Cristo', 'Tagalog', NULL, NULL, 7, 1, NULL, NULL, 'Owned', 'Concrete', 'With Flush', 'Level 3 (Piped)', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No');

-- --------------------------------------------------------

--
-- Table structure for table `pending_registrations`
--

CREATE TABLE `pending_registrations` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `surname` varchar(100) NOT NULL,
  `suffix` varchar(10) DEFAULT NULL COMMENT 'Name suffix: Jr., Sr., II, III, IV, V',
  `birthdate` date NOT NULL,
  `birthplace` varchar(150) DEFAULT NULL,
  `age` int(3) DEFAULT NULL,
  `sex` varchar(10) DEFAULT NULL,
  `civil_status` varchar(20) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT 'Filipino',
  `religion` varchar(100) DEFAULT NULL,
  `ethnicity` varchar(100) DEFAULT NULL,
  `blood_type` varchar(10) DEFAULT NULL,
  `philhealth_no` varchar(30) DEFAULT NULL,
  `length_of_residency` int(11) DEFAULT NULL,
  `house_ownership` varchar(30) DEFAULT NULL,
  `house_material` varchar(50) DEFAULT NULL,
  `toilet_type` varchar(30) DEFAULT NULL,
  `water_source` varchar(50) DEFAULT NULL,
  `is_4ps` varchar(3) DEFAULT 'No',
  `is_nhts` varchar(3) DEFAULT 'No',
  `is_solo_parent` varchar(3) DEFAULT 'No',
  `is_smoker` varchar(3) DEFAULT 'No',
  `is_binge_drinker` varchar(3) DEFAULT 'No',
  `has_hypertension` varchar(3) DEFAULT 'No',
  `has_diabetes` varchar(3) DEFAULT 'No',
  `has_asthma` varchar(3) DEFAULT 'No',
  `has_tb` varchar(3) DEFAULT 'No',
  `has_cancer` varchar(3) DEFAULT 'No',
  `has_mental_health` varchar(3) DEFAULT 'No',
  `membership_type` varchar(30) DEFAULT NULL,
  `family_planning` varchar(3) DEFAULT 'No',
  `purok` varchar(50) DEFAULT NULL,
  `household_no` varchar(20) DEFAULT NULL,
  `barangay` varchar(100) NOT NULL DEFAULT 'Santo Rosario' COMMENT 'Allowed: Buhang | Caloc-an | Guiasan | Marcos | Poblacion | Santo Niño | Santo Rosario | Taod-oy',
  `municipality` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `voters_status` varchar(5) DEFAULT 'No',
  `educational_attainment` varchar(100) DEFAULT NULL,
  `grade_level` varchar(50) DEFAULT NULL,
  `school_name` varchar(150) DEFAULT NULL,
  `total_household` int(3) DEFAULT 1,
  `is_pwd` varchar(5) DEFAULT 'No',
  `pwd_type` varchar(200) DEFAULT NULL COMMENT 'Structured disability type; required only when is_pwd = Yes',
  `is_deceased` varchar(5) DEFAULT 'No',
  `date_of_death` date DEFAULT NULL,
  `is_newborn` varchar(5) DEFAULT 'No',
  `contact_no` varchar(30) DEFAULT NULL,
  `occupation_type` varchar(100) DEFAULT NULL COMMENT 'Employment category: Employed, Self-employed, Student, Unemployed, Retired, Homemaker, Farmer, Informal Worker, OFW, Government Employee, PWD, Other',
  `occupation` varchar(150) DEFAULT NULL,
  `monthly_income` decimal(15,2) DEFAULT NULL COMMENT 'Self-reported monthly income in PHP',
  `annual_income` decimal(15,2) DEFAULT NULL COMMENT 'Auto-computed: monthly_income × 12 (PHP)',
  `socioeconomic_status` varchar(50) DEFAULT NULL COMMENT 'PSA-based SES: Poor | Low Income | Lower Middle Income | Middle Income | Upper Middle Income | High Income',
  `household_position` varchar(50) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT 'default.jpg',
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `rejection_reason` text DEFAULT NULL,
  `reviewed_by` varchar(100) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pending_registrations`
--

INSERT INTO `pending_registrations` (`id`, `username`, `password`, `first_name`, `middle_name`, `surname`, `suffix`, `birthdate`, `birthplace`, `age`, `sex`, `civil_status`, `nationality`, `religion`, `ethnicity`, `blood_type`, `philhealth_no`, `length_of_residency`, `house_ownership`, `house_material`, `toilet_type`, `water_source`, `is_4ps`, `is_nhts`, `is_solo_parent`, `is_smoker`, `is_binge_drinker`, `has_hypertension`, `has_diabetes`, `has_asthma`, `has_tb`, `has_cancer`, `has_mental_health`, `membership_type`, `family_planning`, `purok`, `household_no`, `barangay`, `municipality`, `province`, `voters_status`, `educational_attainment`, `grade_level`, `school_name`, `total_household`, `is_pwd`, `pwd_type`, `is_deceased`, `date_of_death`, `is_newborn`, `contact_no`, `occupation_type`, `occupation`, `monthly_income`, `annual_income`, `socioeconomic_status`, `household_position`, `image_path`, `status`, `rejection_reason`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(15, 'Nath', '$2y$10$VHQlDkPywVlKh1RmvtdjWuTM2umtHFXQ3sFquOr973u6xrC4lKNAS', 'Nathaniel', 'Bade', 'Badah', NULL, '2003-11-11', 'Sta.Cruz Manila', 22, 'Male', 'Single', 'Filipino', 'Roman Catholic', 'Cebuano', 'AB-', NULL, 6, 'Owned', 'Concrete', 'With Flush', 'Level 3 (Piped)', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'Purok 5', '005', 'Santo Rosario', 'Magallanes', 'Agusan Del Norte', 'No', 'College', '4th Year', 'CSUCC', 4, 'Yes', 'Psychosocial Disability', 'No', NULL, 'No', '09473560822', 'Student', 'Student', 5000.00, 60000.00, 'Poor', 'Son', '1775460294_69d35fc64dc32.jpeg', 'Pending', NULL, NULL, NULL, '2026-04-06 15:24:54', NULL),
(16, 'juan_delacruz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan', 'Santos', 'dela Cruz', NULL, '1985-03-14', 'Poblacion, Bukidnon', 39, 'Male', 'Married', 'Filipino', 'Roman Catholic', 'Visayan', 'O+', '12-345678901-2', 15, 'Owned', 'Concrete', 'Water-sealed', 'Level II Faucet', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', 'No', 'No', 'No', 'No', 'Indigent', 'No', 'Purok 1', 'HH-001', 'Poblacion', 'San Fernando', 'Bukidnon', 'Yes', 'Elementary Graduate', NULL, NULL, 5, 'No', NULL, 'No', NULL, 'No', '09171234501', 'Farmer', 'Rice Farmer', 8000.00, 96000.00, 'Low Income', 'Head', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:00:06', '2026-04-06 15:59:52', '2026-04-06 16:00:06'),
(17, 'maria_reyes88', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria', 'Lim', 'Reyes', NULL, '1988-07-22', 'Cagayan de Oro City', 36, 'Female', 'Married', 'Filipino', 'Roman Catholic', 'Cebuano', 'A+', '12-987654321-0', 10, 'Owned', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Employed', 'Yes', 'Purok 2', 'HH-002', 'Santo Niño', 'San Fernando', 'Bukidnon', 'Yes', 'College Graduate', NULL, NULL, 4, 'No', NULL, 'No', NULL, 'No', '09281234502', 'Government Employee', 'Elementary School Teacher', 24000.00, 288000.00, 'Lower Middle Income', 'Head', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:00:12', '2026-04-06 15:59:52', '2026-04-06 16:00:12'),
(18, 'roberto_manalo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Roberto', 'Cruz', 'Manalo', 'Jr.', '1992-11-05', 'Davao City', 32, 'Male', 'Single', 'Filipino', 'Roman Catholic', 'Bisaya', 'B+', NULL, 5, 'Rented', 'Light Materials', 'Unsanitary Pit', 'Level I Spring', 'No', 'No', 'No', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'Individually Paying', 'No', 'Purok 3', 'HH-003', 'Buhang', 'San Fernando', 'Bukidnon', 'Yes', 'High School Graduate', NULL, NULL, 2, 'No', NULL, 'No', NULL, 'No', '09391234503', 'Self-Employed', 'Tricycle Driver', 12000.00, 144000.00, 'Low Income', 'Head', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:00:18', '2026-04-06 15:59:52', '2026-04-06 16:00:18'),
(19, 'luisa_garcia72', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Luisa', 'Bautista', 'Garcia', NULL, '1972-01-30', 'Iligan City', 52, 'Female', 'Widowed', 'Filipino', 'Roman Catholic', 'Maranao', 'AB+', '12-111222333-4', 20, 'Owned', 'Light Materials', 'Unsanitary Pit', 'Level I Spring', 'Yes', 'Yes', 'Yes', 'No', 'No', 'Yes', 'No', 'No', 'No', 'No', 'No', 'Indigent', 'No', 'Purok 1', 'HH-004', 'Caloc-an', 'San Fernando', 'Bukidnon', 'Yes', 'Elementary Graduate', NULL, NULL, 6, 'Yes', 'Visual Impairment', 'No', NULL, 'No', '09451234504', 'Housewife/Homemaker', NULL, 0.00, 0.00, 'Poor', 'Head', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:00:23', '2026-04-06 15:59:52', '2026-04-06 16:00:23'),
(20, 'carlos_fern', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carlos', 'Dela Torre', 'Fernandez', NULL, '1990-06-18', 'Bukidnon', 34, 'Male', 'Married', 'Filipino', 'Born Again Christian', 'Higaonon', 'O-', '12-444555666-7', 12, 'Owned', 'Concrete', 'Water-sealed', 'Level II Faucet', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Individually Paying', 'Yes', 'Purok 4', 'HH-005', 'Guiasan', 'San Fernando', 'Bukidnon', 'Yes', 'High School Graduate', NULL, NULL, 5, 'No', NULL, 'No', NULL, 'No', '09561234505', 'Self-Employed', 'Carpenter', 18000.00, 216000.00, 'Lower Middle Income', 'Head', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:00:30', '2026-04-06 15:59:52', '2026-04-06 16:00:30'),
(21, 'ana_villanueva', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ana', 'Ramos', 'Villanueva', NULL, '1995-09-10', 'Malaybalay City', 29, 'Female', 'Single', 'Filipino', 'Roman Catholic', 'Visayan', 'A-', NULL, 7, 'Rented', 'Light Materials', 'Water-sealed', 'Level II Faucet', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Indigent', 'Yes', 'Purok 2', 'HH-006', 'Marcos', 'San Fernando', 'Bukidnon', 'Yes', 'High School Graduate', NULL, NULL, 3, 'No', NULL, 'No', NULL, 'No', '09671234506', 'Self-Employed', 'Market Vendor', 9500.00, 114000.00, 'Low Income', 'Head', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:00:35', '2026-04-06 15:59:52', '2026-04-06 16:00:35'),
(22, 'miguel_santos', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Miguel', 'Aquino', 'Santos', NULL, '1993-04-25', 'Quezon City', 31, 'Male', 'Single', 'Filipino', 'Roman Catholic', 'Tagalog', 'B-', '12-777888999-1', 3, 'Rented', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Employed', 'No', 'Purok 3', 'HH-007', 'Santo Rosario', 'San Fernando', 'Bukidnon', 'Yes', 'College Graduate', NULL, NULL, 2, 'No', NULL, 'No', NULL, 'No', '09781234507', 'Government Employee', 'Registered Nurse', 35000.00, 420000.00, 'Middle Income', 'Member', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:00:41', '2026-04-06 15:59:52', '2026-04-06 16:00:41'),
(23, 'rosa_pascual47', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rosa', 'Mendoza', 'Pascual', NULL, '1947-12-03', 'Bukidnon', 77, 'Female', 'Widowed', 'Filipino', 'Roman Catholic', 'Higaonon', 'O+', '12-000111222-3', 40, 'Owned', 'Concrete', 'Water-sealed', 'Level II Faucet', 'No', 'Yes', 'No', 'No', 'No', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'Indigent', 'No', 'Purok 1', 'HH-008', 'Taod-oy', 'San Fernando', 'Bukidnon', 'Yes', 'Elementary Graduate', NULL, NULL, 4, 'No', NULL, 'No', NULL, 'No', '09891234508', 'Senior Citizen/Retired', NULL, 0.00, 0.00, 'Poor', 'Member', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:00:45', '2026-04-06 15:59:52', '2026-04-06 16:00:45'),
(24, 'kevin_lim2003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kevin', 'Tan', 'Lim', NULL, '2003-08-15', 'Cagayan de Oro City', 21, 'Male', 'Single', 'Filipino', 'Roman Catholic', 'Chinese-Filipino', 'AB-', NULL, 2, 'Owned', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Dependent', 'No', 'Purok 2', 'HH-009', 'Poblacion', 'San Fernando', 'Bukidnon', 'No', 'College Level', '3rd Year', 'Bukidnon State University', 4, 'No', NULL, 'No', NULL, 'No', '09111234509', 'Student', NULL, 0.00, 0.00, 'Poor', 'Member', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:00:50', '2026-04-06 15:59:52', '2026-04-06 16:00:50'),
(25, 'elena_morales', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Elena', 'Torres', 'Morales', NULL, '1980-02-14', 'Malaybalay City', 44, 'Female', 'Married', 'Filipino', 'Roman Catholic', 'Visayan', 'A+', '12-321654987-5', 18, 'Owned', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Individually Paying', 'No', 'Purok 4', 'HH-010', 'Santo Niño', 'San Fernando', 'Bukidnon', 'Yes', 'College Graduate', NULL, NULL, 5, 'No', NULL, 'No', NULL, 'No', '09221234510', 'Business Owner', 'Sari-sari Store Owner', 85000.00, 1020000.00, 'Upper Middle Income', 'Head', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:00:55', '2026-04-06 15:59:52', '2026-04-06 16:00:55'),
(26, 'danilo_cruz55', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Danilo', 'Abad', 'Cruz', NULL, '1975-05-20', 'Bukidnon', 49, 'Male', 'Married', 'Filipino', 'Roman Catholic', 'Bisaya', 'O+', NULL, 25, 'Owned', 'Light Materials', 'Unsanitary Pit', 'Level I Spring', 'Yes', 'Yes', 'No', 'Yes', 'No', 'No', 'No', 'No', 'Yes', 'No', 'No', 'Indigent', 'Yes', 'Purok 3', 'HH-011', 'Buhang', 'San Fernando', 'Bukidnon', 'Yes', 'Elementary Level', NULL, NULL, 7, 'No', NULL, 'No', NULL, 'No', '09331234511', 'Farmer', 'Fisherman', 7500.00, 90000.00, 'Poor', 'Head', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:01:00', '2026-04-06 15:59:52', '2026-04-06 16:01:00'),
(27, 'sheila_navarro', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sheila', 'De Leon', 'Navarro', NULL, '1987-10-08', 'Bukidnon', 37, 'Female', 'Married', 'Filipino', 'Roman Catholic', 'Bisaya', 'B+', '12-654321098-6', 15, 'Owned', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'OFW', 'Yes', 'Purok 1', 'HH-012', 'Caloc-an', 'San Fernando', 'Bukidnon', 'Yes', 'College Graduate', NULL, NULL, 4, 'No', NULL, 'No', NULL, 'No', '09441234512', 'OFW', 'Caregiver (Hong Kong)', 150000.00, 1800000.00, 'High Income', 'Head', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:01:04', '2026-04-06 15:59:52', '2026-04-06 16:01:04'),
(28, 'rodel_aguilar', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rodel', 'Perez', 'Aguilar', NULL, '2002-03-30', 'Bukidnon', 22, 'Male', 'Single', 'Filipino', 'Born Again Christian', 'Talaandig', 'O+', NULL, 22, 'Owned', 'Light Materials', 'Unsanitary Pit', 'Level I Spring', 'Yes', 'Yes', 'No', 'Yes', 'Yes', 'No', 'No', 'Yes', 'No', 'No', 'No', 'Indigent', 'No', 'Purok 2', 'HH-013', 'Guiasan', 'San Fernando', 'Bukidnon', 'Yes', 'High School Level', NULL, NULL, 3, 'No', NULL, 'No', NULL, 'No', '09551234513', 'Unemployed', NULL, 0.00, 0.00, 'Poor', 'Member', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:01:10', '2026-04-06 15:59:52', '2026-04-06 16:01:10'),
(29, 'cristina_delavega', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Cristina', 'Flores', 'Dela Vega', NULL, '1983-07-17', 'Bukidnon', 41, 'Female', 'Married', 'Filipino', 'Roman Catholic', 'Visayan', 'A+', '12-135792468-9', 20, 'Owned', 'Concrete', 'Water-sealed', 'Level II Faucet', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Employed', 'Yes', 'Purok 3', 'HH-014', 'Marcos', 'San Fernando', 'Bukidnon', 'Yes', 'College Level', NULL, NULL, 5, 'No', NULL, 'No', NULL, 'No', '09661234514', 'Government Employee', 'Barangay Health Worker', 19000.00, 228000.00, 'Lower Middle Income', 'Head', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:07:27', '2026-04-06 15:59:52', '2026-04-06 16:07:27'),
(30, 'felix_ocampo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Felix', 'Domingo', 'Ocampo', 'Sr.', '1965-11-11', 'Bukidnon', 59, 'Male', 'Married', 'Filipino', 'Roman Catholic', 'Higaonon', 'B+', '12-246813579-0', 35, 'Owned', 'Light Materials', 'Water-sealed', 'Level II Faucet', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', 'No', 'No', 'No', 'No', 'Indigent', 'No', 'Purok 4', 'HH-015', 'Taod-oy', 'San Fernando', 'Bukidnon', 'Yes', 'Elementary Graduate', NULL, NULL, 6, 'Yes', 'Physical Disability', 'No', NULL, 'No', '09771234515', 'Senior Citizen/Retired', NULL, 5000.00, 60000.00, 'Poor', 'Head', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:07:40', '2026-04-06 15:59:52', '2026-04-06 16:07:40'),
(31, 'baby_reyes_2024a', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Baby Girl', '', 'Reyes', NULL, '2024-12-20', 'San Fernando, Bukidnon', 0, 'Female', 'Single', 'Filipino', 'Roman Catholic', 'Visayan', 'A+', NULL, 0, 'Owned', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Dependent', 'No', 'Purok 2', 'HH-002', 'Santo Niño', 'San Fernando', 'Bukidnon', 'No', 'No Grade Completed', NULL, NULL, 1, 'No', NULL, 'No', NULL, 'Yes', '', 'Student', NULL, 0.00, 0.00, 'Poor', 'Member', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:23:27', '2026-04-06 16:22:44', '2026-04-06 16:23:27'),
(32, 'baby_delacruz_2025a', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Baby Boy', '', 'dela Cruz', NULL, '2025-01-01', 'Bukidnon Provincial Hospital', 0, 'Male', 'Single', 'Filipino', 'Roman Catholic', 'Higaonon', 'O+', NULL, 0, 'Owned', 'Concrete', 'Water-sealed', 'Level II Faucet', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Dependent', 'No', 'Purok 1', 'HH-001', 'Poblacion', 'San Fernando', 'Bukidnon', 'No', 'No Grade Completed', NULL, NULL, 1, 'No', NULL, 'No', NULL, 'Yes', '', 'Student', NULL, 0.00, 0.00, 'Poor', 'Member', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:23:32', '2026-04-06 16:22:44', '2026-04-06 16:23:32'),
(33, 'baby_manalo_2025b', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Baby Girl', '', 'Manalo', NULL, '2025-01-03', 'Cagayan de Oro City', 0, 'Female', 'Single', 'Filipino', 'Roman Catholic', 'Bisaya', 'B+', NULL, 0, 'Rented', 'Light Materials', 'Unsanitary Pit', 'Level I Spring', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Dependent', 'No', 'Purok 3', 'HH-003', 'Buhang', 'San Fernando', 'Bukidnon', 'No', 'No Grade Completed', NULL, NULL, 1, 'Yes', 'Chronic Illness', 'No', NULL, 'Yes', '', 'Student', NULL, 0.00, 0.00, 'Poor', 'Member', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:23:36', '2026-04-06 16:22:44', '2026-04-06 16:23:36'),
(34, 'baby_santos_2024c', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Baby Boy', '', 'Santos', NULL, '2024-12-05', 'San Fernando, Bukidnon', 0, 'Male', 'Single', 'Filipino', 'Born Again Christian', 'Talaandig', 'O-', NULL, 0, 'Rented', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Dependent', 'No', 'Purok 3', 'HH-007', 'Santo Rosario', 'San Fernando', 'Bukidnon', 'No', 'No Grade Completed', NULL, NULL, 1, 'No', NULL, 'No', NULL, 'Yes', '', 'Student', NULL, 0.00, 0.00, 'Poor', 'Member', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:23:40', '2026-04-06 16:22:44', '2026-04-06 16:23:40'),
(35, 'baby_ocampo_2025d', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Baby Girl', '', 'Ocampo', NULL, '2024-12-28', 'Bukidnon Provincial Hospital', 0, 'Female', 'Single', 'Filipino', 'Roman Catholic', 'Higaonon', 'A-', NULL, 0, 'Owned', 'Light Materials', 'Water-sealed', 'Level II Faucet', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Dependent', 'No', 'Purok 4', 'HH-015', 'Taod-oy', 'San Fernando', 'Bukidnon', 'No', 'No Grade Completed', NULL, NULL, 1, 'Yes', 'Intellectual Disability', 'No', NULL, 'Yes', '', 'Student', NULL, 0.00, 0.00, 'Poor', 'Member', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:23:46', '2026-04-06 16:22:44', '2026-04-06 16:23:46'),
(36, 'ernesto_vill1942', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ernesto', 'Bacalso', 'Villanueva', 'Sr.', '1942-04-10', 'Bukidnon', 82, 'Male', 'Widowed', 'Filipino', 'Roman Catholic', 'Visayan', 'O+', '12-101010101-1', 50, 'Owned', 'Concrete', 'Water-sealed', 'Level II Faucet', 'No', 'Yes', 'No', 'Yes', 'No', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'Indigent', 'No', 'Purok 1', 'HH-016', 'Taod-oy', 'San Fernando', 'Bukidnon', 'Yes', 'Elementary Graduate', NULL, NULL, 4, 'No', NULL, 'Yes', '2024-11-15', 'No', '', 'Senior Citizen/Retired', NULL, 0.00, 0.00, 'Poor', 'Head', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:23:49', '2026-04-06 16:22:44', '2026-04-06 16:23:49'),
(37, 'lourdes_cast1955', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lourdes', 'Uy', 'Castillo', NULL, '1955-09-23', 'Cagayan de Oro City', 69, 'Female', 'Widowed', 'Filipino', 'Roman Catholic', 'Cebuano', 'B+', '12-202020202-2', 30, 'Owned', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Yes', 'No', 'Indigent', 'No', 'Purok 2', 'HH-017', 'Marcos', 'San Fernando', 'Bukidnon', 'Yes', 'High School Graduate', NULL, NULL, 5, 'No', NULL, 'Yes', '2024-10-03', 'No', '', 'Senior Citizen/Retired', NULL, 0.00, 0.00, 'Poor', 'Member', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:23:58', '2026-04-06 16:22:44', '2026-04-06 16:23:58'),
(38, 'andres_baut1968', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Andres', 'Sicat', 'Bautista', NULL, '1968-06-05', 'Bukidnon', 56, 'Male', 'Married', 'Filipino', 'Roman Catholic', 'Talaandig', 'A+', '12-303030303-3', 20, 'Owned', 'Light Materials', 'Unsanitary Pit', 'Level I Spring', 'Yes', 'Yes', 'No', 'Yes', 'No', 'No', 'No', 'No', 'Yes', 'No', 'No', 'Indigent', 'No', 'Purok 4', 'HH-018', 'Guiasan', 'San Fernando', 'Bukidnon', 'Yes', 'Elementary Level', NULL, NULL, 6, 'No', NULL, 'Yes', '2024-08-19', 'No', '', 'Farmer', 'Corn Farmer', 0.00, 0.00, 'Poor', 'Head', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:23:52', '2026-04-06 16:22:44', '2026-04-06 16:23:52'),
(39, 'remedios_flor1938', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Remedios', 'Dizon', 'Flores', NULL, '1938-02-14', 'Bukidnon', 87, 'Female', 'Widowed', 'Filipino', 'Roman Catholic', 'Higaonon', 'AB+', '12-404040404-4', 60, 'Owned', 'Concrete', 'Water-sealed', 'Level II Faucet', 'No', 'Yes', 'No', 'No', 'No', 'Yes', 'No', 'No', 'No', 'No', 'No', 'Indigent', 'No', 'Purok 1', 'HH-019', 'Caloc-an', 'San Fernando', 'Bukidnon', 'Yes', 'No Grade Completed', NULL, NULL, 5, 'No', NULL, 'Yes', '2024-12-01', 'No', '', 'Senior Citizen/Retired', NULL, 0.00, 0.00, 'Poor', 'Member', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:30:38', '2026-04-06 16:22:44', '2026-04-06 16:30:38'),
(40, 'bayani_sarm2001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bayani', 'Magno', 'Sarmiento', NULL, '2001-07-14', 'San Fernando, Bukidnon', 23, 'Male', 'Single', 'Filipino', 'Born Again Christian', 'Bisaya', 'O+', NULL, 23, 'Owned', 'Light Materials', 'Water-sealed', 'Level II Faucet', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Indigent', 'No', 'Purok 3', 'HH-020', 'Buhang', 'San Fernando', 'Bukidnon', 'Yes', 'High School Graduate', NULL, NULL, 4, 'No', NULL, 'Yes', '2024-09-22', 'No', '', 'Self-Employed', 'Motorcycle Delivery Rider', 0.00, 0.00, 'Poor', 'Member', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:23:55', '2026-04-06 16:22:44', '2026-04-06 16:23:55'),
(41, 'gerardo_tolen', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Gerardo', 'Espiritu', 'Tolentino', NULL, '1978-03-12', 'Bukidnon', 46, 'Male', 'Married', 'Filipino', 'Roman Catholic', 'Bisaya', 'O+', '12-505050505-5', 18, 'Owned', 'Concrete', 'Water-sealed', 'Level II Faucet', 'No', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Indigent', 'No', 'Purok 2', 'HH-021', 'Santo Niño', 'San Fernando', 'Bukidnon', 'Yes', 'High School Graduate', NULL, NULL, 5, 'Yes', 'Hearing Impairment', 'No', NULL, 'No', '09111234521', 'Self-Employed', 'Furniture Maker', 11000.00, 132000.00, 'Low Income', 'Head', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:24:06', '2026-04-06 16:22:44', '2026-04-06 16:24:06'),
(42, 'priscilla_dom', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Priscilla', 'Navarro', 'Domingo', NULL, '1991-11-28', 'Malaybalay City', 33, 'Female', 'Single', 'Filipino', 'Roman Catholic', 'Visayan', 'A+', '12-606060606-6', 10, 'Rented', 'Light Materials', 'Unsanitary Pit', 'Level I Spring', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Indigent', 'No', 'Purok 4', 'HH-022', 'Poblacion', 'San Fernando', 'Bukidnon', 'Yes', 'Elementary Graduate', NULL, NULL, 3, 'Yes', 'Speech Impairment', 'No', NULL, 'No', '09221234522', 'Housewife/Homemaker', NULL, 0.00, 0.00, 'Poor', 'Head', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:24:01', '2026-04-06 16:22:44', '2026-04-06 16:24:01'),
(43, 'arsenio_madri', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Arsenio', 'Cañete', 'Madrigal', NULL, '1969-08-07', 'Bukidnon', 55, 'Male', 'Married', 'Filipino', 'Roman Catholic', 'Higaonon', 'B-', '12-707070707-7', 30, 'Owned', 'Light Materials', 'Water-sealed', 'Level I Spring', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', 'No', 'No', 'No', 'No', 'Indigent', 'No', 'Purok 1', 'HH-023', 'Guiasan', 'San Fernando', 'Bukidnon', 'Yes', 'Elementary Graduate', NULL, NULL, 6, 'Yes', 'Orthopedic Disability', 'No', NULL, 'No', '09331234523', 'Farmer', 'Vegetable Farmer', 6500.00, 78000.00, 'Poor', 'Head', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:30:42', '2026-04-06 16:22:44', '2026-04-06 16:30:42'),
(44, 'josefina_alca', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Josefina', 'Buenaventura', 'Alcantara', NULL, '1984-05-19', 'Cagayan de Oro City', 40, 'Female', 'Single', 'Filipino', 'Roman Catholic', 'Cebuano', 'O-', '12-808080808-8', 8, 'Rented', 'Light Materials', 'Unsanitary Pit', 'Level I Spring', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Yes', 'Indigent', 'No', 'Purok 2', 'HH-024', 'Caloc-an', 'San Fernando', 'Bukidnon', 'Yes', 'High School Level', NULL, NULL, 4, 'Yes', 'Psychosocial Disability', 'No', NULL, 'No', '09441234524', 'Unemployed', NULL, 0.00, 0.00, 'Poor', 'Member', 'default.jpg', 'Rejected', 'DLI TAGA STO.ROSARIO', 'Admin', '2026-04-06 16:30:58', '2026-04-06 16:22:44', '2026-04-06 16:30:58'),
(45, 'teofilo_ramos', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Teofilo', 'Marquez', 'Ramos', 'Jr.', '1960-01-25', 'Iligan City', 65, 'Male', 'Married', 'Filipino', 'Roman Catholic', 'Maranao', 'A-', '12-909090909-9', 22, 'Owned', 'Concrete', 'Water-sealed', 'Level II Faucet', 'No', 'Yes', 'No', 'No', 'No', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'Indigent', 'No', 'Purok 3', 'HH-025', 'Marcos', 'San Fernando', 'Bukidnon', 'Yes', 'High School Graduate', NULL, NULL, 5, 'Yes', 'Chronic Illness', 'No', NULL, 'No', '09551234525', 'Senior Citizen/Retired', NULL, 5500.00, 66000.00, 'Poor', 'Head', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:31:04', '2026-04-06 16:22:44', '2026-04-06 16:31:04'),
(46, 'nimfa_padilla2014', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nimfa', 'Soriano', 'Padilla', NULL, '2014-06-11', 'San Fernando, Bukidnon', 10, 'Female', 'Single', 'Filipino', 'Roman Catholic', 'Bisaya', 'B+', NULL, 10, 'Owned', 'Light Materials', 'Water-sealed', 'Level II Faucet', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'Yes', 'No', 'No', 'No', 'Dependent', 'No', 'Purok 1', 'HH-026', 'Santo Rosario', 'San Fernando', 'Bukidnon', 'No', 'Elementary Level', 'Grade 4', 'San Fernando Central School', 5, 'Yes', 'Learning Disability', 'No', NULL, 'No', '', 'Student', NULL, 0.00, 0.00, 'Poor', 'Member', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:24:15', '2026-04-06 16:22:44', '2026-04-06 16:24:15'),
(47, 'cornelio_agra', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Cornelio', 'Ilustre', 'Agrava', 'Sr.', '1950-10-30', 'Bukidnon', 74, 'Male', 'Widowed', 'Filipino', 'Roman Catholic', 'Higaonon', 'O+', '12-111213141-5', 45, 'Owned', 'Light Materials', 'Unsanitary Pit', 'Level I Spring', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', 'No', 'No', 'No', 'No', 'Indigent', 'No', 'Purok 4', 'HH-027', 'Buhang', 'San Fernando', 'Bukidnon', 'Yes', 'No Grade Completed', NULL, NULL, 3, 'Yes', 'Multiple Disability', 'No', NULL, 'No', '09661234526', 'Senior Citizen/Retired', NULL, 3000.00, 36000.00, 'Poor', 'Head', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:31:06', '2026-04-06 16:22:44', '2026-04-06 16:31:06'),
(48, 'hazel_concep2007', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hazel', 'Labrador', 'Concepcion', NULL, '2007-03-04', 'San Fernando, Bukidnon', 17, 'Female', 'Single', 'Filipino', 'Roman Catholic', 'Visayan', 'A+', NULL, 17, 'Owned', 'Concrete', 'Water-sealed', 'Level II Faucet', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Dependent', 'No', 'Purok 2', 'HH-028', 'Taod-oy', 'San Fernando', 'Bukidnon', 'No', 'Elementary Level', 'Grade 6', 'San Fernando Central School', 6, 'Yes', 'Intellectual Disability', 'No', NULL, 'No', '', 'Student', NULL, 0.00, 0.00, 'Poor', 'Member', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:31:09', '2026-04-06 16:22:44', '2026-04-06 16:31:09'),
(49, 'renato_esgue', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Renato', 'Almario', 'Esguerra', NULL, '1996-12-15', 'Cagayan de Oro City', 28, 'Male', 'Single', 'Filipino', 'Roman Catholic', 'Cebuano', 'B+', '12-161718192-0', 6, 'Rented', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Indigent', 'No', 'Purok 3', 'HH-029', 'Poblacion', 'San Fernando', 'Bukidnon', 'Yes', 'College Level', NULL, NULL, 3, 'Yes', 'Physical Disability', 'No', NULL, 'No', '09771234527', 'Self-Employed', 'Online Freelancer', 8500.00, 102000.00, 'Low Income', 'Member', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:24:12', '2026-04-06 16:22:44', '2026-04-06 16:24:12'),
(50, 'paz_villacort1948', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Paz', 'Adriano', 'Villacorta', NULL, '1948-08-02', 'Bukidnon', 76, 'Female', 'Widowed', 'Filipino', 'Roman Catholic', 'Higaonon', 'O+', '12-212223242-5', 50, 'Owned', 'Light Materials', 'Water-sealed', 'Level I Spring', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'Indigent', 'No', 'Purok 1', 'HH-030', 'Santo Niño', 'San Fernando', 'Bukidnon', 'Yes', 'Elementary Graduate', NULL, NULL, 4, 'Yes', 'Visual Impairment', 'No', NULL, 'No', '09881234528', 'Senior Citizen/Retired', NULL, 0.00, 0.00, 'Poor', 'Member', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:31:12', '2026-04-06 16:22:44', '2026-04-06 16:31:12'),
(51, 'natividad_buen', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Natividad', 'Pedrosa', 'Buenaventura', NULL, '1979-04-16', 'Bukidnon', 45, 'Female', 'Married', 'Filipino', 'Roman Catholic', 'Bisaya', 'A+', NULL, 20, 'Owned', 'Light Materials', 'Unsanitary Pit', 'Level I Spring', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Indigent', 'Yes', 'Purok 2', 'HH-031', 'Guiasan', 'San Fernando', 'Bukidnon', 'Yes', 'Elementary Graduate', NULL, NULL, 8, 'No', NULL, 'No', NULL, 'No', '09991234529', 'Housewife/Homemaker', NULL, 0.00, 0.00, 'Poor', 'Member', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:31:15', '2026-04-06 16:22:44', '2026-04-06 16:31:15'),
(52, 'leonard_bondoc', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Leonard', 'Hizon', 'Bondoc', NULL, '1986-10-21', 'San Fernando, Bukidnon', 38, 'Male', 'Married', 'Filipino', 'Roman Catholic', 'Visayan', 'B+', '12-262728293-0', 14, 'Owned', 'Concrete', 'Water-sealed', 'Level II Faucet', 'No', 'No', 'No', 'No', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'Employed', 'No', 'Purok 3', 'HH-032', 'Caloc-an', 'San Fernando', 'Bukidnon', 'Yes', 'High School Graduate', NULL, NULL, 5, 'No', NULL, 'No', NULL, 'No', '09101234530', 'Government Employee', 'Barangay Tanod', 10000.00, 120000.00, 'Low Income', 'Head', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:31:18', '2026-04-06 16:22:44', '2026-04-06 16:31:18'),
(53, 'diwata_macar', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Diwata', 'Bayao', 'Macaraeg', NULL, '1982-07-30', 'Bukidnon', 42, 'Female', 'Married', 'Filipino', 'Indigenous Beliefs', 'Talaandig', 'O+', NULL, 35, 'Owned', 'Light Materials', 'Unsanitary Pit', 'Level I Spring', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Indigent', 'No', 'Purok 4', 'HH-033', 'Marcos', 'San Fernando', 'Bukidnon', 'Yes', 'Elementary Level', NULL, NULL, 7, 'No', NULL, 'No', NULL, 'No', '09201234531', 'Farmer', 'Banana Grower', 7000.00, 84000.00, 'Poor', 'Head', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:31:20', '2026-04-06 16:22:44', '2026-04-06 16:31:20'),
(54, 'patrick_dioni', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Patrick', 'Macapagal', 'Dionisio', NULL, '1994-01-09', 'Davao del Norte', 31, 'Single', 'Single', 'Filipino', 'Born Again Christian', 'Bisaya', 'A-', '12-313233343-5', 4, 'Rented', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Employed', 'No', 'Purok 1', 'HH-034', 'Taod-oy', 'San Fernando', 'Bukidnon', 'Yes', 'High School Graduate', NULL, NULL, 2, 'No', NULL, 'No', NULL, 'No', '09301234532', 'Private Employee', 'Security Guard', 16000.00, 192000.00, 'Lower Middle Income', 'Member', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:31:24', '2026-04-06 16:22:44', '2026-04-06 16:31:24'),
(55, 'gloria_espino', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Gloria', 'Ferrer', 'Espinosa', NULL, '1958-03-03', 'Bukidnon', 66, 'Female', 'Widowed', 'Filipino', 'Roman Catholic', 'Visayan', 'O+', '12-363738394-0', 38, 'Owned', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'No', 'No', 'Yes', 'No', 'No', 'No', 'No', 'No', 'Employed', 'No', 'Purok 2', 'HH-035', 'Buhang', 'San Fernando', 'Bukidnon', 'Yes', 'College Graduate', NULL, NULL, 3, 'No', NULL, 'No', NULL, 'No', '09401234533', 'Senior Citizen/Retired', 'Retired Teacher', 20000.00, 240000.00, 'Lower Middle Income', 'Head', 'default.jpg', 'Pending', NULL, NULL, NULL, '2026-04-06 16:22:44', NULL),
(56, 'rodrigo_delap', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rodrigo', 'Pascual', 'Dela Peña', NULL, '1971-11-17', 'Bukidnon', 53, 'Male', 'Married', 'Filipino', 'Roman Catholic', 'Cebuano', 'B+', '12-414243444-5', 25, 'Owned', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Employed', 'No', 'Purok 3', 'HH-036', 'Santo Rosario', 'San Fernando', 'Bukidnon', 'Yes', 'College Graduate', NULL, NULL, 6, 'No', NULL, 'No', NULL, 'No', '09501234534', 'Government Employee', 'Barangay Captain', 90000.00, 1080000.00, 'Upper Middle Income', 'Head', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:24:22', '2026-04-06 16:22:44', '2026-04-06 16:24:22'),
(57, 'emilia_cordo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emilia', 'Agustin', 'Cordova', NULL, '1997-05-24', 'Bukidnon', 27, 'Female', 'Single', 'Filipino', 'Roman Catholic', 'Bisaya', 'A+', NULL, 9, 'Rented', 'Light Materials', 'Unsanitary Pit', 'Level I Spring', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Indigent', 'No', 'Purok 4', 'HH-037', 'Guiasan', 'San Fernando', 'Bukidnon', 'Yes', 'High School Graduate', NULL, NULL, 3, 'No', NULL, 'No', NULL, 'No', '09601234535', 'Self-Employed', 'Laundry Woman', 5000.00, 60000.00, 'Poor', 'Head', 'default.jpg', 'Pending', NULL, NULL, NULL, '2026-04-06 16:22:44', NULL),
(58, 'alvin_casta2009', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alvin', 'Yap', 'Castañeda', NULL, '2009-02-18', 'San Fernando, Bukidnon', 15, 'Male', 'Single', 'Filipino', 'Roman Catholic', 'Chinese-Filipino', 'O-', NULL, 15, 'Owned', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Yes', 'No', 'No', 'No', 'Dependent', 'No', 'Purok 1', 'HH-038', 'Poblacion', 'San Fernando', 'Bukidnon', 'No', 'High School Level', 'Grade 9', 'San Fernando National High School', 4, 'No', NULL, 'No', NULL, 'No', '', 'Student', NULL, 0.00, 0.00, 'Poor', 'Member', 'default.jpg', 'Approved', NULL, 'Admin', '2026-04-06 16:24:18', '2026-04-06 16:22:44', '2026-04-06 16:24:18'),
(59, 'marisol_tabuga', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Marisol', 'Ybañez', 'Tabuga', NULL, '1989-09-09', 'Cagayan de Oro City', 35, 'Female', 'Married', 'Filipino', 'Roman Catholic', 'Cebuano', 'AB+', '12-464748495-0', 11, 'Rented', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Employed', 'Yes', 'Purok 2', 'HH-039', 'Caloc-an', 'San Fernando', 'Bukidnon', 'Yes', 'College Graduate', NULL, NULL, 4, 'No', NULL, 'No', NULL, 'No', '09701234536', 'Government Employee', 'Rural Health Midwife', 28000.00, 336000.00, 'Lower Middle Income', 'Head', 'default.jpg', 'Pending', NULL, NULL, NULL, '2026-04-06 16:22:44', NULL),
(60, 'domingo_zabal', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Domingo', 'Crisostomo', 'Zabala', NULL, '1976-06-28', 'Bukidnon', 48, 'Male', 'Married', 'Filipino', 'Roman Catholic', 'Bisaya', 'O+', NULL, 22, 'Owned', 'Light Materials', 'Water-sealed', 'Level I Spring', 'No', 'Yes', 'No', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', 'No', 'Indigent', 'No', 'Purok 3', 'HH-040', 'Marcos', 'San Fernando', 'Bukidnon', 'Yes', 'Elementary Graduate', NULL, NULL, 6, 'No', NULL, 'No', NULL, 'No', '09801234537', 'Farmer', 'Sugar Cane Worker', 9000.00, 108000.00, 'Low Income', 'Head', 'default.jpg', 'Pending', NULL, NULL, NULL, '2026-04-06 16:22:44', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `profile_update_requests`
--

CREATE TABLE `profile_update_requests` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `monthly_income` double(10,2) DEFAULT NULL,
  `civil_status` varchar(20) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `purok` varchar(50) DEFAULT NULL,
  `household_position` varchar(50) DEFAULT NULL,
  `educational_attainment` varchar(100) DEFAULT NULL,
  `new_image_path` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `rejection_reason` text DEFAULT NULL,
  `resident_note` text DEFAULT NULL COMMENT 'Optional note from resident explaining the change',
  `reviewed_by` varchar(100) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `profile_update_requests`
--

INSERT INTO `profile_update_requests` (`id`, `resident_id`, `contact_no`, `occupation`, `monthly_income`, `civil_status`, `religion`, `purok`, `household_position`, `educational_attainment`, `new_image_path`, `status`, `rejection_reason`, `resident_note`, `reviewed_by`, `reviewed_at`, `created_at`) VALUES
(2, 1590, '+639473560830', 'Web Developer', 100000.00, 'Single', 'Iglesia ni Cristo', 'Purok 1', 'Son', 'College', NULL, 'Approved', NULL, 'New year New me', 'Admin', '2026-04-06 07:13:54', '2026-04-05 23:13:09'),
(3, 1590, '09473560777', 'Web Developer', 100000.00, 'Single', 'Iglesia ni Cristo', 'Purok 1', 'Son', 'College', NULL, 'Pending', NULL, '', NULL, NULL, '2026-04-05 23:15:53');

-- --------------------------------------------------------

--
-- Table structure for table `residents`
--

CREATE TABLE `residents` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `surname` varchar(100) NOT NULL,
  `suffix` varchar(10) DEFAULT NULL COMMENT 'Name suffix: Jr., Sr., II, III, IV, V',
  `birthdate` date NOT NULL COMMENT 'Full birthdate in YYYY-MM-DD format',
  `birthplace` varchar(150) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `sex` enum('Male','Female') NOT NULL,
  `civil_status` enum('Single','Married','Widowed','Divorced') DEFAULT NULL,
  `nationality` varchar(50) DEFAULT 'Filipino',
  `religion` varchar(100) DEFAULT NULL,
  `ethnicity` varchar(100) DEFAULT NULL,
  `blood_type` varchar(10) DEFAULT NULL,
  `philhealth_no` varchar(30) DEFAULT NULL,
  `length_of_residency` int(11) DEFAULT NULL,
  `house_ownership` varchar(30) DEFAULT NULL,
  `house_material` varchar(50) DEFAULT NULL,
  `toilet_type` varchar(30) DEFAULT NULL,
  `water_source` varchar(50) DEFAULT NULL,
  `is_4ps` varchar(3) DEFAULT 'No',
  `is_nhts` varchar(3) DEFAULT 'No',
  `is_solo_parent` varchar(3) DEFAULT 'No',
  `is_smoker` varchar(3) DEFAULT 'No',
  `is_binge_drinker` varchar(3) DEFAULT 'No',
  `has_hypertension` varchar(3) DEFAULT 'No',
  `has_diabetes` varchar(3) DEFAULT 'No',
  `has_asthma` varchar(3) DEFAULT 'No',
  `has_tb` varchar(3) DEFAULT 'No',
  `has_cancer` varchar(3) DEFAULT 'No',
  `has_mental_health` varchar(3) DEFAULT 'No',
  `membership_type` varchar(30) DEFAULT NULL,
  `family_planning` varchar(3) DEFAULT 'No',
  `is_pwd` enum('Yes','No') DEFAULT 'No',
  `pwd_type` varchar(200) DEFAULT NULL COMMENT 'Structured disability type; required only when is_pwd = Yes',
  `pwd_id_no` varchar(50) DEFAULT NULL,
  `is_deceased` enum('Yes','No') DEFAULT 'No',
  `date_of_death` date DEFAULT NULL,
  `is_newborn` enum('Yes','No') DEFAULT 'No',
  `purok` varchar(50) DEFAULT NULL,
  `household_no` varchar(20) DEFAULT NULL,
  `barangay` varchar(100) NOT NULL DEFAULT 'Santo Rosario' COMMENT 'Allowed: Buhang | Caloc-an | Guiasan | Marcos | Poblacion | Santo Niño | Santo Rosario | Taod-oy',
  `municipality` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `household_position` varchar(50) DEFAULT NULL,
  `total_household` int(11) DEFAULT NULL,
  `voters_status` enum('Yes','No') DEFAULT 'No',
  `educational_attainment` enum('None','Elementary','High School','Senior High','College','Vocational','Post Graduate') DEFAULT NULL,
  `grade_level` varchar(50) DEFAULT NULL,
  `school_name` varchar(150) DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `occupation_type` varchar(100) DEFAULT NULL COMMENT 'Employment category: Employed, Self-employed, Student, Unemployed, Retired, Homemaker, Farmer, Informal Worker, OFW, Government Employee, PWD, Other',
  `occupation` varchar(100) DEFAULT NULL COMMENT 'Occupation or student status',
  `monthly_income` decimal(15,2) DEFAULT NULL COMMENT 'Self-reported monthly income in PHP',
  `annual_income` decimal(15,2) DEFAULT NULL COMMENT 'Auto-computed: monthly_income × 12 (PHP)',
  `socioeconomic_status` varchar(50) DEFAULT NULL COMMENT 'PSA-based SES: Poor | Low Income | Lower Middle Income | Middle Income | Upper Middle Income | High Income',
  `image_path` varchar(255) NOT NULL,
  `id_front_image` varchar(255) DEFAULT NULL,
  `id_back_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `account_status` enum('active','inactive','suspended') DEFAULT 'active',
  `user_role` enum('resident','admin','staff') DEFAULT 'resident',
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(3) NOT NULL DEFAULT 0,
  `lockout_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `residents`
--

INSERT INTO `residents` (`id`, `first_name`, `middle_name`, `surname`, `suffix`, `birthdate`, `birthplace`, `age`, `sex`, `civil_status`, `nationality`, `religion`, `ethnicity`, `blood_type`, `philhealth_no`, `length_of_residency`, `house_ownership`, `house_material`, `toilet_type`, `water_source`, `is_4ps`, `is_nhts`, `is_solo_parent`, `is_smoker`, `is_binge_drinker`, `has_hypertension`, `has_diabetes`, `has_asthma`, `has_tb`, `has_cancer`, `has_mental_health`, `membership_type`, `family_planning`, `is_pwd`, `pwd_type`, `pwd_id_no`, `is_deceased`, `date_of_death`, `is_newborn`, `purok`, `household_no`, `barangay`, `municipality`, `province`, `household_position`, `total_household`, `voters_status`, `educational_attainment`, `grade_level`, `school_name`, `contact_no`, `occupation_type`, `occupation`, `monthly_income`, `annual_income`, `socioeconomic_status`, `image_path`, `id_front_image`, `id_back_image`, `created_at`, `updated_at`, `username`, `password`, `account_status`, `user_role`, `last_login`, `login_attempts`, `lockout_until`) VALUES
(1590, 'Kian', 'Datahan', 'Saga', NULL, '2003-11-11', 'Sta.Cruz Manila', 22, 'Male', 'Single', 'Filipino', 'Iglesia ni Cristo', 'Cebuano', 'Unknown', NULL, 5, 'Owned', 'Concrete', 'With Flush', 'Level 3 (Piped)', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'Yes', 'Speech Impairment', NULL, 'No', NULL, 'No', 'Purok 1', '001', 'Santo Rosario', 'Magallanes', 'Agusan Del Norte', 'Son', 5, 'Yes', 'College', '4th Year', 'CSUCC', '+639473560830', 'Student', 'Web Developer', 100000.00, 1200000.00, 'Upper Middle Income', '1775424999_69d2d5e7821a8_cam.jpg', NULL, NULL, '2026-04-05 21:36:39', '2026-04-05 23:13:54', 'Kian1', '$2y$10$836icPq6Z6jzhF33DeNAx.ELLQd6d2n59zleDdMFNXae2QlgeXv6S', 'active', 'resident', NULL, 0, NULL),
(1591, 'Clent Jhonaris', 'Mariscal', 'Jumon', NULL, '2003-11-11', 'Davao City', 22, 'Male', 'Single', 'Filipino', 'Born Again Christian', 'Cebuano', 'O-', NULL, 5, 'Owned', 'Concrete', 'With Flush', 'Level 3 (Piped)', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'No', NULL, NULL, 'No', NULL, 'No', 'Purok 6', '112', 'Santo Rosario', 'Magallanes', 'Agusan Del Norte', 'Son', 3, 'Yes', 'Post Graduate', NULL, NULL, '+639473560822', 'Employed', 'Businessman', 80000.00, 960000.00, 'Upper Middle Income', '1775433424_69d2f6d073bc6_cam.jpg', NULL, NULL, '2026-04-05 23:57:04', '2026-04-05 23:57:04', 'Clent', '$2y$10$Fv5XGigBQKovRn6BszZ6kukf85JyyQi43C8Bkk2ljzgyHVneB.mFK', 'active', 'resident', NULL, 0, NULL),
(1592, 'Juan', 'Santos', 'dela Cruz', NULL, '1985-03-14', 'Poblacion, Bukidnon', 39, 'Male', 'Married', 'Filipino', 'Roman Catholic', 'Visayan', 'O+', '12-345678901-2', 15, 'Owned', 'Concrete', 'Water-sealed', 'Level II Faucet', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'No', NULL, NULL, 'No', NULL, 'No', 'Purok 1', 'HH-001', 'Poblacion', 'San Fernando', 'Bukidnon', 'Head', 5, 'Yes', '', NULL, NULL, '09171234501', 'Farmer', 'Rice Farmer', 8000.00, 96000.00, 'Poor', '1775462786_69d369826db08_cam.jpg', NULL, NULL, '2026-04-06 07:59:52', '2026-04-06 08:06:26', 'juan_delacruz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1593, 'Maria', 'Lim', 'Reyes', NULL, '1988-07-22', 'Cagayan de Oro City', 36, 'Female', 'Married', 'Filipino', 'Roman Catholic', 'Cebuano', 'A+', '12-987654321-0', 10, 'Owned', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'Yes', 'No', NULL, NULL, 'No', NULL, 'No', 'Purok 2', 'HH-002', 'Santo Niño', 'San Fernando', 'Bukidnon', 'Head', 4, 'Yes', '', NULL, NULL, '09281234502', 'Government Employee', 'Elementary School Teacher', 24000.00, 288000.00, 'Lower Middle Income', '1775462778_69d3697a09a28_cam.jpg', NULL, NULL, '2026-04-06 07:59:52', '2026-04-06 08:06:18', 'maria_reyes88', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1594, 'Roberto', 'Cruz', 'Manalo', 'Jr.', '1992-11-05', 'Davao City', 32, 'Male', 'Single', 'Filipino', 'Roman Catholic', 'Bisaya', 'B+', NULL, 5, 'Rented', 'Light Materials', 'Unsanitary Pit', 'Level I Spring', 'No', 'No', 'No', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'No', NULL, NULL, 'No', NULL, 'No', 'Purok 3', 'HH-003', 'Buhang', 'San Fernando', 'Bukidnon', 'Head', 2, 'Yes', '', NULL, NULL, '09391234503', 'Self-Employed', 'Tricycle Driver', 12000.00, 144000.00, 'Low Income', '1775462768_69d3697067546_cam.jpg', NULL, NULL, '2026-04-06 07:59:52', '2026-04-06 08:06:08', 'roberto_manalo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1595, 'Luisa', 'Bautista', 'Garcia', NULL, '1972-01-30', 'Iligan City', 52, 'Female', 'Widowed', 'Filipino', 'Roman Catholic', 'Maranao', 'AB+', '12-111222333-4', 20, 'Owned', 'Light Materials', 'Unsanitary Pit', 'Level I Spring', 'Yes', 'Yes', 'Yes', 'No', 'No', 'Yes', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'Yes', 'Visual Impairment', NULL, 'No', NULL, 'No', 'Purok 1', 'HH-004', 'Caloc-an', 'San Fernando', 'Bukidnon', 'Head', 6, 'Yes', '', NULL, NULL, '09451234504', 'Housewife/Homemaker', NULL, 0.00, 0.00, 'Poor', '1775462757_69d36965a5d00_cam.jpg', NULL, NULL, '2026-04-06 07:59:52', '2026-04-06 08:05:57', 'luisa_garcia72', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1596, 'Carlos', 'Dela Torre', 'Fernandez', NULL, '1990-06-18', 'Bukidnon', 34, 'Male', 'Married', 'Filipino', 'Born Again Christian', 'Higaonon', 'O-', '12-444555666-7', 12, 'Owned', 'Concrete', 'Water-sealed', 'Level II Faucet', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'Yes', 'No', NULL, NULL, 'No', NULL, 'No', 'Purok 4', 'HH-005', 'Guiasan', 'San Fernando', 'Bukidnon', 'Head', 5, 'Yes', '', NULL, NULL, '09561234505', 'Self-Employed', 'Carpenter', 18000.00, 216000.00, 'Low Income', '1775462745_69d369595eea0_cam.jpg', NULL, NULL, '2026-04-06 07:59:52', '2026-04-06 08:05:45', 'carlos_fern', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1597, 'Ana', 'Ramos', 'Villanueva', NULL, '1995-09-10', 'Malaybalay City', 29, 'Female', 'Single', 'Filipino', 'Roman Catholic', 'Visayan', 'A-', NULL, 7, 'Rented', 'Light Materials', 'Water-sealed', 'Level II Faucet', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'Yes', 'No', NULL, NULL, 'No', NULL, 'No', 'Purok 2', 'HH-006', 'Marcos', 'San Fernando', 'Bukidnon', 'Head', 3, 'Yes', '', NULL, NULL, '09671234506', 'Self-Employed', 'Market Vendor', 9500.00, 114000.00, 'Poor', '1775462685_69d3691ded3e0_cam.jpg', NULL, NULL, '2026-04-06 07:59:52', '2026-04-06 08:04:45', 'ana_villanueva', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1598, 'Miguel', 'Aquino', 'Santos', NULL, '1993-04-25', 'Quezon City', 31, 'Male', 'Single', 'Filipino', 'Roman Catholic', 'Tagalog', 'B-', '12-777888999-1', 3, 'Rented', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'No', NULL, NULL, 'No', NULL, 'No', 'Purok 3', 'HH-007', 'Santo Rosario', 'San Fernando', 'Bukidnon', 'Son', 2, 'Yes', '', NULL, NULL, '09781234507', 'Government Employee', 'Registered Nurse', 35000.00, 420000.00, 'Lower Middle Income', '1775462729_69d36949cac3b_cam.jpg', NULL, NULL, '2026-04-06 07:59:52', '2026-04-06 08:05:29', 'miguel_santos', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1599, 'Rosa', 'Mendoza', 'Pascual', NULL, '1947-12-03', 'Bukidnon', 77, 'Female', 'Widowed', 'Filipino', 'Roman Catholic', 'Higaonon', 'O+', '12-000111222-3', 40, 'Owned', 'Concrete', 'Water-sealed', 'Level II Faucet', 'No', 'Yes', 'No', 'No', 'No', 'Yes', 'Yes', 'No', 'No', 'No', 'No', NULL, 'No', 'No', NULL, NULL, 'No', NULL, 'No', 'Purok 1', 'HH-008', 'Taod-oy', 'San Fernando', 'Bukidnon', 'Son', 4, 'Yes', '', NULL, NULL, '09891234508', 'Senior Citizen/Retired', NULL, 0.00, 0.00, 'Poor', '1775462665_69d369098d033_cam.jpg', NULL, NULL, '2026-04-06 07:59:52', '2026-04-06 08:04:25', 'rosa_pascual47', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1600, 'Kevin', 'Tan', 'Lim', NULL, '2003-08-15', 'Cagayan de Oro City', 21, 'Male', 'Single', 'Filipino', 'Roman Catholic', 'Chinese-Filipino', 'AB-', NULL, 2, 'Owned', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'No', NULL, NULL, 'No', NULL, 'No', 'Purok 2', 'HH-009', 'Poblacion', 'San Fernando', 'Bukidnon', 'Son', 4, 'No', '', '3rd Year', 'Bukidnon State University', '09111234509', 'Student', NULL, 0.00, 0.00, 'Poor', '1775462611_69d368d3a1124_cam.jpg', NULL, NULL, '2026-04-06 07:59:52', '2026-04-06 08:03:31', 'kevin_lim2003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1601, 'Elena', 'Torres', 'Morales', NULL, '1980-02-14', 'Malaybalay City', 44, 'Female', 'Married', 'Filipino', 'Roman Catholic', 'Visayan', 'A+', '12-321654987-5', 18, 'Owned', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'No', NULL, NULL, 'No', NULL, 'No', 'Purok 4', 'HH-010', 'Santo Niño', 'San Fernando', 'Bukidnon', 'Head', 5, 'Yes', '', NULL, NULL, '09221234510', 'Business Owner', 'Sari-sari Store Owner', 85000.00, 1020000.00, 'Upper Middle Income', '1775462585_69d368b99903e_cam.jpg', NULL, NULL, '2026-04-06 07:59:52', '2026-04-06 08:03:05', 'elena_morales', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1602, 'Danilo', 'Abad', 'Cruz', NULL, '1975-05-20', 'Bukidnon', 49, 'Male', 'Married', 'Filipino', 'Roman Catholic', 'Bisaya', 'O+', NULL, 25, 'Owned', 'Light Materials', 'Unsanitary Pit', 'Level I Spring', 'Yes', 'Yes', 'No', 'Yes', 'No', 'No', 'No', 'No', 'Yes', 'No', 'No', NULL, 'Yes', 'No', NULL, NULL, 'No', NULL, 'No', 'Purok 3', 'HH-011', 'Buhang', 'San Fernando', 'Bukidnon', 'Head', 7, 'Yes', '', NULL, NULL, '09331234511', 'Farmer', 'Fisherman', 7500.00, 90000.00, 'Poor', '1775462564_69d368a45533d_cam.jpg', NULL, NULL, '2026-04-06 07:59:52', '2026-04-06 08:02:44', 'danilo_cruz55', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1603, 'Sheila', 'De Leon', 'Navarro', NULL, '1987-10-08', 'Bukidnon', 37, 'Female', 'Married', 'Filipino', 'Roman Catholic', 'Bisaya', 'B+', '12-654321098-6', 15, 'Owned', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'OFW', 'Yes', 'No', NULL, NULL, 'No', NULL, 'No', 'Purok 1', 'HH-012', 'Caloc-an', 'San Fernando', 'Bukidnon', 'Head', 4, 'Yes', '', NULL, NULL, '09441234512', 'OFW', 'Caregiver (Hong Kong)', 150000.00, 1800000.00, 'High Income', '1775462547_69d368938ba8f_cam.jpg', NULL, NULL, '2026-04-06 07:59:52', '2026-04-06 08:02:27', 'sheila_navarro', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1604, 'Rodel', 'Perez', 'Aguilar', NULL, '2002-03-30', 'Bukidnon', 22, 'Male', 'Single', 'Filipino', 'Born Again Christian', 'Talaandig', 'O+', NULL, 22, 'Owned', 'Light Materials', 'Unsanitary Pit', 'Level I Spring', 'Yes', 'Yes', 'No', 'Yes', 'Yes', 'No', 'No', 'Yes', 'No', 'No', 'No', NULL, 'No', 'No', NULL, NULL, 'No', NULL, 'No', 'Purok 2', 'HH-013', 'Guiasan', 'San Fernando', 'Bukidnon', 'Son', 3, 'Yes', '', NULL, NULL, '09551234513', 'Unemployed', NULL, 0.00, 0.00, 'Poor', '1775462525_69d3687dc802a_cam.jpg', NULL, NULL, '2026-04-06 07:59:52', '2026-04-06 08:02:05', 'rodel_aguilar', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1605, 'Cristina', 'Flores', 'Dela Vega', NULL, '1983-07-17', 'Bukidnon', 41, 'Female', 'Married', 'Filipino', 'Roman Catholic', 'Visayan', 'A+', '12-135792468-9', 20, 'Owned', 'Concrete', 'Water-sealed', 'Level II Faucet', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'Yes', 'No', NULL, NULL, 'No', NULL, 'No', 'Purok 3', 'HH-014', 'Marcos', 'San Fernando', 'Bukidnon', 'Head', 5, 'Yes', '', NULL, NULL, '09661234514', 'Government Employee', 'Barangay Health Worker', 19000.00, 228000.00, 'Low Income', '1775462920_69d36a0813f61_cam.jpg', NULL, NULL, '2026-04-06 07:59:52', '2026-04-06 08:08:40', 'cristina_delavega', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1606, 'Felix', 'Domingo', 'Ocampo', 'Sr.', '1965-11-11', 'Bukidnon', 59, 'Male', 'Married', 'Filipino', 'Roman Catholic', 'Higaonon', 'B+', '12-246813579-0', 35, 'Owned', 'Light Materials', 'Water-sealed', 'Level II Faucet', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'Yes', 'Physical Disability', NULL, 'No', NULL, 'No', 'Purok 4', 'HH-015', 'Taod-oy', 'San Fernando', 'Bukidnon', 'Head', 6, 'Yes', '', NULL, NULL, '09771234515', 'Senior Citizen/Retired', NULL, 5000.00, 60000.00, 'Poor', '1775462907_69d369fb1133c_cam.jpg', NULL, NULL, '2026-04-06 07:59:52', '2026-04-06 08:08:27', 'felix_ocampo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1607, 'Baby Girl', '', 'Reyes', NULL, '2024-12-20', 'San Fernando, Bukidnon', 0, 'Female', 'Single', 'Filipino', 'Roman Catholic', 'Visayan', 'A+', NULL, 0, 'Owned', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'No', NULL, NULL, 'No', NULL, 'Yes', 'Purok 2', 'HH-002', 'Santo Niño', 'San Fernando', 'Bukidnon', 'Father', 1, 'No', '', NULL, NULL, '', 'Student', NULL, 0.00, 0.00, 'Poor', '1775464181_69d36ef57553a_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:29:41', 'baby_reyes_2024a', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1608, 'Baby Boy', '', 'dela Cruz', NULL, '2025-01-01', 'Bukidnon Provincial Hospital', 0, 'Male', 'Single', 'Filipino', 'Roman Catholic', 'Higaonon', 'O+', NULL, 0, 'Owned', 'Concrete', 'Water-sealed', 'Level II Faucet', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'No', NULL, NULL, 'No', NULL, 'Yes', 'Purok 1', 'HH-001', 'Poblacion', 'San Fernando', 'Bukidnon', 'Uncle', 1, 'No', '', NULL, NULL, '', 'Student', NULL, 0.00, 0.00, 'Poor', '1775464166_69d36ee66ba26_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:29:26', 'baby_delacruz_2025a', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1609, 'Baby Girl', '', 'Manalo', NULL, '2025-01-03', 'Cagayan de Oro City', 0, 'Female', 'Single', 'Filipino', 'Roman Catholic', 'Bisaya', 'B+', NULL, 0, 'Rented', 'Light Materials', 'Unsanitary Pit', 'Level I Spring', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'Yes', 'Chronic Illness', NULL, 'No', NULL, 'Yes', 'Purok 3', 'HH-003', 'Buhang', 'San Fernando', 'Bukidnon', 'Mother', 1, 'No', '', NULL, NULL, '', 'Student', NULL, 0.00, 0.00, 'Poor', '1775464148_69d36ed43ad11_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:29:08', 'baby_manalo_2025b', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1610, 'Baby Boy', '', 'Santos', NULL, '2024-12-05', 'San Fernando, Bukidnon', 0, 'Male', 'Single', 'Filipino', 'Born Again Christian', 'Talaandig', 'O-', NULL, 0, 'Rented', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'No', NULL, NULL, 'No', NULL, 'Yes', 'Purok 3', 'HH-007', 'Santo Rosario', 'San Fernando', 'Bukidnon', 'Sister', 1, 'No', '', NULL, NULL, '', 'Student', NULL, 0.00, 0.00, 'Poor', '1775464134_69d36ec6286b5_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:28:54', 'baby_santos_2024c', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1611, 'Baby Girl', '', 'Ocampo', NULL, '2024-12-28', 'Bukidnon Provincial Hospital', 0, 'Female', 'Single', 'Filipino', 'Roman Catholic', 'Higaonon', 'A-', NULL, 0, 'Owned', 'Light Materials', 'Water-sealed', 'Level II Faucet', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'Yes', 'Intellectual Disability', NULL, 'No', NULL, 'Yes', 'Purok 4', 'HH-015', 'Taod-oy', 'San Fernando', 'Bukidnon', 'Sister', 1, 'No', '', NULL, NULL, '', 'Student', NULL, 0.00, 0.00, 'Poor', '1775464110_69d36eae71b6f_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:28:30', 'baby_ocampo_2025d', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1612, 'Ernesto', 'Bacalso', 'Villanueva', 'Sr.', '1942-04-10', 'Bukidnon', 82, 'Male', 'Widowed', 'Filipino', 'Roman Catholic', 'Visayan', 'O+', '12-101010101-1', 50, 'Owned', 'Concrete', 'Water-sealed', 'Level II Faucet', 'No', 'Yes', 'No', 'Yes', 'No', 'Yes', 'Yes', 'No', 'No', 'No', 'No', NULL, 'No', 'No', NULL, NULL, 'Yes', '2024-11-15', 'No', 'Purok 1', 'HH-016', 'Taod-oy', 'San Fernando', 'Bukidnon', 'Head', 4, 'Yes', '', NULL, NULL, '', 'Senior Citizen/Retired', NULL, 0.00, 0.00, 'Poor', '1775464094_69d36e9e41835_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:28:14', 'ernesto_vill1942', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1613, 'Andres', 'Sicat', 'Bautista', NULL, '1968-06-05', 'Bukidnon', 56, 'Male', 'Married', 'Filipino', 'Roman Catholic', 'Talaandig', 'A+', '12-303030303-3', 20, 'Owned', 'Light Materials', 'Unsanitary Pit', 'Level I Spring', 'Yes', 'Yes', 'No', 'Yes', 'No', 'No', 'No', 'No', 'Yes', 'No', 'No', NULL, 'No', 'No', NULL, NULL, 'Yes', '2024-08-19', 'No', 'Purok 4', 'HH-018', 'Guiasan', 'San Fernando', 'Bukidnon', 'Head', 6, 'Yes', '', NULL, NULL, '', 'Farmer', 'Corn Farmer', 0.00, 0.00, 'Poor', '1775464083_69d36e930f71a_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:28:03', 'andres_baut1968', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1614, 'Bayani', 'Magno', 'Sarmiento', NULL, '2001-07-14', 'San Fernando, Bukidnon', 23, 'Male', 'Single', 'Filipino', 'Born Again Christian', 'Bisaya', 'O+', NULL, 23, 'Owned', 'Light Materials', 'Water-sealed', 'Level II Faucet', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'No', NULL, NULL, 'Yes', '2024-09-22', 'No', 'Purok 3', 'HH-020', 'Buhang', 'San Fernando', 'Bukidnon', 'Mother', 4, 'Yes', '', NULL, NULL, '', 'Self-Employed', 'Motorcycle Delivery Rider', 0.00, 0.00, 'Poor', '1775464041_69d36e692d301_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:27:21', 'bayani_sarm2001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1615, 'Lourdes', 'Uy', 'Castillo', NULL, '1955-09-23', 'Cagayan de Oro City', 69, 'Female', 'Widowed', 'Filipino', 'Roman Catholic', 'Cebuano', 'B+', '12-202020202-2', 30, 'Owned', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Yes', 'No', NULL, 'No', 'No', NULL, NULL, 'Yes', '2024-10-03', 'No', 'Purok 2', 'HH-017', 'Marcos', 'San Fernando', 'Bukidnon', 'Son', 5, 'Yes', '', NULL, NULL, '', 'Senior Citizen/Retired', NULL, 0.00, 0.00, 'Poor', '1775464019_69d36e536109f_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:26:59', 'lourdes_cast1955', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1616, 'Priscilla', 'Navarro', 'Domingo', NULL, '1991-11-28', 'Malaybalay City', 33, 'Female', 'Single', 'Filipino', 'Roman Catholic', 'Visayan', 'A+', '12-606060606-6', 10, 'Rented', 'Light Materials', 'Unsanitary Pit', 'Level I Spring', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'Yes', 'Speech Impairment', NULL, 'No', NULL, 'No', 'Purok 4', 'HH-022', 'Poblacion', 'San Fernando', 'Bukidnon', 'Head', 3, 'Yes', '', NULL, NULL, '09221234522', 'Housewife/Homemaker', NULL, 0.00, 0.00, 'Poor', '1775463998_69d36e3eb58f5_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:26:38', 'priscilla_dom', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1617, 'Gerardo', 'Espiritu', 'Tolentino', NULL, '1978-03-12', 'Bukidnon', 46, 'Male', 'Married', 'Filipino', 'Roman Catholic', 'Bisaya', 'O+', '12-505050505-5', 18, 'Owned', 'Concrete', 'Water-sealed', 'Level II Faucet', 'No', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'Yes', 'Hearing Impairment', NULL, 'No', NULL, 'No', 'Purok 2', 'HH-021', 'Santo Niño', 'San Fernando', 'Bukidnon', 'Head', 5, 'Yes', '', NULL, NULL, '09111234521', 'Self-Employed', 'Furniture Maker', 11000.00, 132000.00, 'Low Income', '1775463989_69d36e355091a_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:26:29', 'gerardo_tolen', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1618, 'Renato', 'Almario', 'Esguerra', NULL, '1996-12-15', 'Cagayan de Oro City', 28, 'Male', 'Single', 'Filipino', 'Roman Catholic', 'Cebuano', 'B+', '12-161718192-0', 6, 'Rented', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'Yes', 'Physical Disability', NULL, 'No', NULL, 'No', 'Purok 3', 'HH-029', 'Poblacion', 'San Fernando', 'Bukidnon', 'Son', 3, 'Yes', '', NULL, NULL, '09771234527', 'Self-Employed', 'Online Freelancer', 8500.00, 102000.00, 'Poor', '1775463978_69d36e2a16cd4_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:26:18', 'renato_esgue', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1619, 'Nimfa', 'Soriano', 'Padilla', NULL, '2014-06-11', 'San Fernando, Bukidnon', 10, 'Female', 'Single', 'Filipino', 'Roman Catholic', 'Bisaya', 'B+', NULL, 10, 'Owned', 'Light Materials', 'Water-sealed', 'Level II Faucet', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'Yes', 'No', 'No', 'No', NULL, 'No', 'Yes', 'Learning Disability', NULL, 'No', NULL, 'No', 'Purok 1', 'HH-026', 'Santo Rosario', 'San Fernando', 'Bukidnon', 'Son', 5, 'No', '', 'Grade 4', 'San Fernando Central School', '', 'Student', NULL, 0.00, 0.00, 'Poor', '1775463952_69d36e1080eaf_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:25:52', 'nimfa_padilla2014', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1620, 'Alvin', 'Yap', 'Castañeda', NULL, '2009-02-18', 'San Fernando, Bukidnon', 15, 'Male', 'Single', 'Filipino', 'Roman Catholic', 'Chinese-Filipino', 'O-', NULL, 15, 'Owned', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Yes', 'No', 'No', 'No', NULL, 'No', 'No', NULL, NULL, 'No', NULL, 'No', 'Purok 1', 'HH-038', 'Poblacion', 'San Fernando', 'Bukidnon', 'Son', 4, 'No', '', 'Grade 9', 'San Fernando National High School', '', 'Student', NULL, 0.00, 0.00, 'Poor', '1775463934_69d36dfe15f06_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:25:34', 'alvin_casta2009', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1621, 'Rodrigo', 'Pascual', 'Dela Peña', NULL, '1971-11-17', 'Bukidnon', 53, 'Male', 'Married', 'Filipino', 'Roman Catholic', 'Cebuano', 'B+', '12-414243444-5', 25, 'Owned', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'No', NULL, NULL, 'No', NULL, 'No', 'Purok 3', 'HH-036', 'Santo Rosario', 'San Fernando', 'Bukidnon', 'Head', 6, 'Yes', '', NULL, NULL, '09501234534', 'Government Employee', 'Barangay Captain', 90000.00, 1080000.00, 'Upper Middle Income', '1775463910_69d36de6d2279_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:25:10', 'rodrigo_delap', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1622, 'Remedios', 'Dizon', 'Flores', NULL, '1938-02-14', 'Bukidnon', 87, 'Female', 'Widowed', 'Filipino', 'Roman Catholic', 'Higaonon', 'AB+', '12-404040404-4', 60, 'Owned', 'Concrete', 'Water-sealed', 'Level II Faucet', 'No', 'Yes', 'No', 'No', 'No', 'Yes', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'No', NULL, NULL, 'Yes', '2024-12-01', 'No', 'Purok 1', 'HH-019', 'Caloc-an', 'San Fernando', 'Bukidnon', 'Sister', 5, 'Yes', '', NULL, NULL, '', 'Senior Citizen/Retired', NULL, 0.00, 0.00, 'Poor', '1775464465_69d3701135b9b_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:34:25', 'remedios_flor1938', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1623, 'Arsenio', 'Cañete', 'Madrigal', NULL, '1969-08-07', 'Bukidnon', 55, 'Male', 'Married', 'Filipino', 'Roman Catholic', 'Higaonon', 'B-', '12-707070707-7', 30, 'Owned', 'Light Materials', 'Water-sealed', 'Level I Spring', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'Yes', 'Orthopedic Disability', NULL, 'No', NULL, 'No', 'Purok 1', 'HH-023', 'Guiasan', 'San Fernando', 'Bukidnon', 'Head', 6, 'Yes', '', NULL, NULL, '09331234523', 'Farmer', 'Vegetable Farmer', 6500.00, 78000.00, 'Poor', '1775464449_69d3700169f35_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:34:09', 'arsenio_madri', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1624, 'Teofilo', 'Marquez', 'Ramos', 'Jr.', '1960-01-25', 'Iligan City', 65, 'Male', 'Married', 'Filipino', 'Roman Catholic', 'Maranao', 'A-', '12-909090909-9', 22, 'Owned', 'Concrete', 'Water-sealed', 'Level II Faucet', 'No', 'Yes', 'No', 'No', 'No', 'Yes', 'Yes', 'No', 'No', 'No', 'No', NULL, 'No', 'Yes', 'Chronic Illness', NULL, 'No', NULL, 'No', 'Purok 3', 'HH-025', 'Marcos', 'San Fernando', 'Bukidnon', 'Head', 5, 'Yes', '', NULL, NULL, '09551234525', 'Senior Citizen/Retired', NULL, 5500.00, 66000.00, 'Poor', '1775464441_69d36ff94c371_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:34:01', 'teofilo_ramos', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1625, 'Cornelio', 'Ilustre', 'Agrava', 'Sr.', '1950-10-30', 'Bukidnon', 74, 'Male', 'Widowed', 'Filipino', 'Roman Catholic', 'Higaonon', 'O+', '12-111213141-5', 45, 'Owned', 'Light Materials', 'Unsanitary Pit', 'Level I Spring', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'Yes', 'Multiple Disability', NULL, 'No', NULL, 'No', 'Purok 4', 'HH-027', 'Buhang', 'San Fernando', 'Bukidnon', 'Head', 3, 'Yes', '', NULL, NULL, '09661234526', 'Senior Citizen/Retired', NULL, 3000.00, 36000.00, 'Poor', '1775464432_69d36ff0bf1c4_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:33:52', 'cornelio_agra', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1626, 'Hazel', 'Labrador', 'Concepcion', NULL, '2007-03-04', 'San Fernando, Bukidnon', 17, 'Female', 'Single', 'Filipino', 'Roman Catholic', 'Visayan', 'A+', NULL, 17, 'Owned', 'Concrete', 'Water-sealed', 'Level II Faucet', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'Yes', 'Intellectual Disability', NULL, 'No', NULL, 'No', 'Purok 2', 'HH-028', 'Taod-oy', 'San Fernando', 'Bukidnon', 'Nephew', 6, 'No', '', 'Grade 6', 'San Fernando Central School', '', 'Student', NULL, 0.00, 0.00, 'Poor', '1775464421_69d36fe5b24df_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:33:41', 'hazel_concep2007', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1627, 'Paz', 'Adriano', 'Villacorta', NULL, '1948-08-02', 'Bukidnon', 76, 'Female', 'Widowed', 'Filipino', 'Roman Catholic', 'Higaonon', 'O+', '12-212223242-5', 50, 'Owned', 'Light Materials', 'Water-sealed', 'Level I Spring', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'Yes', 'No', 'No', 'No', 'No', NULL, 'No', 'Yes', 'Visual Impairment', NULL, 'No', NULL, 'No', 'Purok 1', 'HH-030', 'Santo Niño', 'San Fernando', 'Bukidnon', 'Aunt', 4, 'Yes', '', NULL, NULL, '09881234528', 'Senior Citizen/Retired', NULL, 0.00, 0.00, 'Poor', '1775464392_69d36fc8d1b27_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:33:12', 'paz_villacort1948', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1628, 'Natividad', 'Pedrosa', 'Buenaventura', NULL, '1979-04-16', 'Bukidnon', 45, 'Female', 'Married', 'Filipino', 'Roman Catholic', 'Bisaya', 'A+', NULL, 20, 'Owned', 'Light Materials', 'Unsanitary Pit', 'Level I Spring', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'Yes', 'No', NULL, NULL, 'No', NULL, 'No', 'Purok 2', 'HH-031', 'Guiasan', 'San Fernando', 'Bukidnon', 'Sister', 8, 'Yes', '', NULL, NULL, '09991234529', 'Housewife/Homemaker', NULL, 0.00, 0.00, 'Poor', '1775464356_69d36fa43b2b6_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:32:36', 'natividad_buen', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1629, 'Leonard', 'Hizon', 'Bondoc', NULL, '1986-10-21', 'San Fernando, Bukidnon', 38, 'Male', 'Married', 'Filipino', 'Roman Catholic', 'Visayan', 'B+', '12-262728293-0', 14, 'Owned', 'Concrete', 'Water-sealed', 'Level II Faucet', 'No', 'No', 'No', 'No', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'No', NULL, NULL, 'No', NULL, 'No', 'Purok 3', 'HH-032', 'Caloc-an', 'San Fernando', 'Bukidnon', 'Head', 5, 'Yes', '', NULL, NULL, '09101234530', 'Government Employee', 'Barangay Tanod', 10000.00, 120000.00, 'Poor', '1775464324_69d36f843bf4c_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:32:19', 'leonard_bondoc', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1630, 'Diwata', 'Bayao', 'Macaraeg', NULL, '1982-07-30', 'Bukidnon', 42, 'Female', 'Married', 'Filipino', 'Indigenous Beliefs', 'Talaandig', 'O+', NULL, 35, 'Owned', 'Light Materials', 'Unsanitary Pit', 'Level I Spring', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'No', NULL, NULL, 'No', NULL, 'No', 'Purok 4', 'HH-033', 'Marcos', 'San Fernando', 'Bukidnon', 'Head', 7, 'Yes', '', NULL, NULL, '09201234531', 'Farmer', 'Banana Grower', 7000.00, 84000.00, 'Poor', '1775464313_69d36f797bd32_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:31:53', 'diwata_macar', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL),
(1631, 'Patrick', 'Macapagal', 'Dionisio', NULL, '1994-01-09', 'Davao del Norte', 31, 'Male', 'Single', 'Filipino', 'Born Again Christian', 'Bisaya', 'A-', '12-313233343-5', 4, 'Rented', 'Concrete', 'Water-sealed', 'Level III Pipeline', 'No', 'No', 'No', 'Yes', 'No', 'No', 'No', 'No', 'No', 'No', 'No', NULL, 'No', 'No', NULL, NULL, 'No', NULL, 'No', 'Purok 1', 'HH-034', 'Taod-oy', 'San Fernando', 'Bukidnon', 'Daughter', 2, 'Yes', '', NULL, NULL, '09301234532', 'Private Employee', 'Security Guard', 16000.00, 192000.00, 'Low Income', '1775464303_69d36f6f70afc_cam.jpg', NULL, NULL, '2026-04-06 08:22:44', '2026-04-06 08:31:43', 'patrick_dioni', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'resident', NULL, 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`,`user_type`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_login_at` (`login_at`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `barangay_official`
--
ALTER TABLE `barangay_official`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_name` (`first_name`,`surname`),
  ADD KEY `idx_position` (`position`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_barangay` (`barangay`),
  ADD KEY `idx_voters` (`voters_status`);

--
-- Indexes for table `pending_registrations`
--
ALTER TABLE `pending_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_pr_ses` (`socioeconomic_status`),
  ADD KEY `idx_pr_pwd` (`is_pwd`,`pwd_type`(50));

--
-- Indexes for table `profile_update_requests`
--
ALTER TABLE `profile_update_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resident_id` (`resident_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `residents`
--
ALTER TABLE `residents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_username` (`username`),
  ADD KEY `idx_pwd_status` (`is_pwd`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_res_ses` (`socioeconomic_status`),
  ADD KEY `idx_res_pwd` (`is_pwd`,`pwd_type`(50));

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `barangay_official`
--
ALTER TABLE `barangay_official`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `pending_registrations`
--
ALTER TABLE `pending_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `profile_update_requests`
--
ALTER TABLE `profile_update_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `residents`
--
ALTER TABLE `residents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1632;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `profile_update_requests`
--
ALTER TABLE `profile_update_requests`
  ADD CONSTRAINT `fk_pur_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
