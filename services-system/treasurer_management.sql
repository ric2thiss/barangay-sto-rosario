-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 16, 2026 at 01:42 AM
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
-- Database: `treasurer_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `bir_records`
--

CREATE TABLE `bir_records` (
  `id` int(11) NOT NULL,
  `tin` varchar(50) NOT NULL,
  `payee` varchar(150) NOT NULL,
  `vat_type` varchar(20) DEFAULT 'Non-VAT',
  `record_date` date NOT NULL,
  `gross_amount` decimal(12,2) NOT NULL COMMENT 'Total amount paid (includes taxes)',
  `one_percent` decimal(10,2) NOT NULL COMMENT '1% withholding tax',
  `five_percent` decimal(10,2) NOT NULL COMMENT '5% withholding tax',
  `total_amount` decimal(12,2) NOT NULL COMMENT 'Total withholding (1% + 5%)',
  `net_amount` decimal(12,2) NOT NULL COMMENT 'Net amount to payee (gross - total_amount)',
  `remarks` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bir_records`
--

INSERT INTO `bir_records` (`id`, `tin`, `payee`, `vat_type`, `record_date`, `gross_amount`, `one_percent`, `five_percent`, `total_amount`, `net_amount`, `remarks`, `recorded_by`, `created_at`) VALUES
(1, '900-123-456-000', 'Uncle Ben Meatshop', 'Non-VAT', '2026-01-15', 2062.00, 18.41, 92.05, 110.46, 1951.54, 'Meat supplies for barangay feeding program', 1, '2026-04-30 00:09:40'),
(2, '900-234-567-000', 'ABC Hardware Supply', 'Non-VAT', '2026-01-16', 5000.00, 44.64, 223.21, 267.85, 4732.15, 'Construction materials for barangay hall repair', 1, '2026-04-30 00:09:40'),
(3, '900-345-678-000', 'XYZ Office Supplies', 'Non-VAT', '2026-01-17', 3500.00, 31.25, 156.25, 187.50, 3312.50, 'Office supplies and equipment', 1, '2026-04-30 00:09:40'),
(4, '900-456-789-000', 'Sto. Rosario Water Station', 'Non-VAT', '2026-01-18', 4200.00, 37.50, 187.50, 225.00, 3975.00, 'Water service supplies', 1, '2026-04-30 00:09:40'),
(5, '900-567-890-000', 'Magallanes Print Hub', 'Non-VAT', '2026-01-19', 1800.00, 16.07, 80.36, 96.43, 1703.57, 'Printing of barangay forms', 1, '2026-04-30 00:09:40');

-- --------------------------------------------------------

--
-- Table structure for table `cedula`
--

CREATE TABLE `cedula` (
  `id` int(11) NOT NULL,
  `cedula_no` varchar(50) DEFAULT NULL,
  `or_number` varchar(50) DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `year_issued` int(11) DEFAULT NULL,
  `place_of_issue` varchar(150) DEFAULT NULL,
  `full_name` varchar(150) NOT NULL,
  `surname` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `address` text NOT NULL,
  `birth_date` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `sex` enum('Male','Female') DEFAULT NULL,
  `birth_place` varchar(100) DEFAULT NULL,
  `civil_status` varchar(20) DEFAULT NULL,
  `citizenship` varchar(100) DEFAULT 'Filipino',
  `icr_no` varchar(50) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `tin` varchar(50) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `annual_income` decimal(12,2) DEFAULT 0.00,
  `basic_tax` decimal(10,2) DEFAULT 5.00,
  `additional_tax_business` decimal(10,2) DEFAULT 0.00,
  `additional_tax_profession` decimal(10,2) DEFAULT 0.00,
  `additional_tax_property` decimal(10,2) DEFAULT 0.00,
  `community_tax_due` decimal(10,2) DEFAULT 0.00,
  `interest` decimal(10,2) DEFAULT 0.00,
  `amount` decimal(10,2) NOT NULL,
  `nature_of_collection` varchar(100) DEFAULT 'Community Tax',
  `amount_in_words` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `issued_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cedula`
--

INSERT INTO `cedula` (`id`, `cedula_no`, `or_number`, `issued_date`, `year_issued`, `place_of_issue`, `full_name`, `surname`, `first_name`, `middle_name`, `address`, `birth_date`, `age`, `sex`, `birth_place`, `civil_status`, `citizenship`, `icr_no`, `occupation`, `tin`, `height`, `weight`, `annual_income`, `basic_tax`, `additional_tax_business`, `additional_tax_profession`, `additional_tax_property`, `community_tax_due`, `interest`, `amount`, `nature_of_collection`, `amount_in_words`, `remarks`, `resident_id`, `issued_by`, `created_at`) VALUES
(1, 'CTC-2026-001', 'OR-2026-101', '2026-01-15', NULL, NULL, 'Juan Dela Cruz', NULL, NULL, NULL, 'Purok 1, Sto. Rosario, Magallanes, Agusan del Norte', '1985-05-12', 40, 'Male', 'Butuan City', 'Married', 'Filipino', NULL, 'Driver', '123-456-789-000', 1.70, 70.00, 0.00, 5.00, 0.00, 0.00, 0.00, 0.00, 0.00, 35.00, 'Community Tax', NULL, 'Regular cedula', NULL, 1, '2026-04-30 00:09:40'),
(2, 'CTC-2026-002', 'OR-2026-102', '2026-01-16', NULL, NULL, 'Maria Santos', NULL, NULL, NULL, 'Purok 2, Sto. Rosario, Magallanes, Agusan del Norte', '1990-08-22', 35, 'Female', 'Magallanes', 'Single', 'Filipino', NULL, 'Teacher', '234-567-890-000', 1.60, 55.00, 0.00, 5.00, 0.00, 0.00, 0.00, 0.00, 0.00, 30.00, 'Community Tax', NULL, 'For employment', NULL, 1, '2026-04-30 00:09:40'),
(3, 'CTC-2026-003', 'OR-2026-103', '2026-01-17', NULL, NULL, 'Pedro Reyes', NULL, NULL, NULL, 'Purok 3, Sto. Rosario, Magallanes, Agusan del Norte', '1978-03-15', 47, 'Male', 'Butuan City', 'Married', 'Filipino', NULL, 'Businessman', '345-678-901-000', 1.75, 80.00, 0.00, 5.00, 0.00, 0.00, 0.00, 0.00, 0.00, 50.00, 'Community Tax', NULL, 'Business owner', NULL, 1, '2026-04-30 00:09:40'),
(4, 'CTC-2026-004', 'OR-2026-104', '2026-01-18', NULL, NULL, 'Ana Garcia', NULL, NULL, NULL, 'Purok 4, Sto. Rosario, Magallanes, Agusan del Norte', '1995-11-30', 30, 'Female', 'Magallanes', 'Married', 'Filipino', NULL, 'Housewife', '456-789-012-000', 1.58, 52.00, 0.00, 5.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25.00, 'Community Tax', NULL, 'Regular', NULL, 1, '2026-04-30 00:09:40'),
(5, 'CTC-2026-005', 'OR-2026-105', '2026-01-19', NULL, NULL, 'Roberto Cruz', NULL, NULL, NULL, 'Purok 5, Sto. Rosario, Magallanes, Agusan del Norte', '1988-07-08', 37, 'Male', 'Butuan City', 'Single', 'Filipino', NULL, 'Farmer', '567-890-123-000', 1.68, 65.00, 0.00, 5.00, 0.00, 0.00, 0.00, 0.00, 0.00, 30.00, 'Community Tax', NULL, 'Regular cedula', NULL, 1, '2026-04-30 00:09:40'),
(6, '1', '134545', '2026-05-08', 2026, 'Magallanes, Agusan del Norte', 'Nacario, Ben Nacario', 'Nacario', 'Ben', 'Nacario', 'Purok 3, Santo Rosario, Magallanes, Agusan Del Norte', '2023-11-11', 2, 'Male', 'Magallanes, Agusan del Norte', 'Single', 'Filipino', '', 'Toddler', '', 160.00, 53.00, 0.00, 5.00, 0.00, 0.00, 0.00, 5.00, 0.00, 5.00, 'Community Tax', '', '', NULL, NULL, '2026-05-08 20:18:13'),
(7, '2', '', '2026-05-08', 2026, 'Magallanes, Agusan Del Norte', 'Baliling, Jelo', 'Baliling', 'Jelo', '', 'Household No. 001, Purok Purok 7, Buhang, Magallanes, Agusan Del Norte', '2001-08-31', 24, 'Male', 'Butuan City, Agusan del Norte', 'Single', 'Filipino', '', 'Student', '', 0.00, 0.00, 11.88, 5.00, 0.00, 0.00, 0.00, 5.00, 0.00, 5.00, 'Community Tax', '', '', 1851, NULL, '2026-05-08 21:30:50'),
(8, '3', '', '2026-05-09', 2026, 'Magallanes, Agusan Del Norte', 'Baliling, Jelo', 'Baliling', 'Jelo', '', 'Household No. 001, Purok Purok 7, Buhang, Magallanes, Agusan Del Norte', '2001-08-31', 24, 'Male', 'Butuan City, Agusan del Norte', 'Single', 'Filipino', '', 'Student', '', 0.00, 0.00, 11.88, 5.00, 0.00, 0.00, 0.00, 5.00, 0.00, 5.00, 'Community Tax', '', '', 1851, NULL, '2026-05-08 22:02:21'),
(13, '4', '', '2026-05-11', 2026, 'Magallanes, Agusan Del Norte', 'Baliling, Jelo', 'Baliling', 'Jelo', '', 'Household No. 001, Purok Purok 7, Buhang, Magallanes, Agusan Del Norte', '2001-08-31', 24, 'Male', 'Butuan City, Agusan del Norte', 'Single', 'Filipino', '', 'Student', '', 160.00, 53.00, 800000.00, 5.00, 0.00, 800.00, 0.00, 805.00, 0.00, 805.00, 'Community Tax', '', '', 1851, NULL, '2026-05-11 00:00:45'),
(14, '5', '123', '2026-05-11', 2026, 'Magallanes, Agusan Del Norte', 'Baliling, Jelo', 'Baliling', 'Jelo', '', 'Purok 7, Buhang, Magallanes, Agusan Del Norte', '2001-08-31', 24, 'Male', 'Butuan City, Agusan del Norte', 'Single', 'Filipino', '', 'Developer', '', 160.00, 53.00, 800000.00, 5.00, 0.00, 800.00, 0.00, 805.00, 0.00, 805.00, 'Community Tax', 'Eight hundred and five pesos', '', NULL, NULL, '2026-05-11 00:50:21'),
(15, '6', '', '2026-05-11', 2026, 'Magallanes, Agusan Del Norte', 'Paquibot, Ric Charles Lucar', 'Paquibot', 'Ric Charles', 'Lucar', 'Household No. 3, Purok Purok 2, Santo Rosario, Magallanes, Agusan Del Norte', '2001-07-23', 24, 'Male', 'Butuan City', 'Single', 'Filipino', '', 'Student', '', 160.00, 53.00, 0.00, 5.00, 0.00, 0.00, 0.00, 5.00, 0.00, 5.00, 'Community Tax', '', '', 1860, NULL, '2026-05-11 01:47:15'),
(16, '7', '', '2026-05-13', 2026, 'Magallanes, Agusan Del Norte', 'Paquibot, Ric Charles Lucar', 'Paquibot', 'Ric Charles', 'Lucar', 'Household No. 3, Purok Purok 2, Santo Rosario, Magallanes, Agusan Del Norte', '2001-07-23', 24, 'Male', 'Butuan City', 'Single', 'Filipino', '', 'Student', '', 160.00, 53.00, 0.00, 5.00, 0.00, 0.00, 0.00, 5.00, 0.00, 5.00, 'Community Tax', '', '', 1860, NULL, '2026-05-13 08:02:06');

-- --------------------------------------------------------

--
-- Table structure for table `disbursements`
--

CREATE TABLE `disbursements` (
  `id` int(11) NOT NULL,
  `disburse_date` date NOT NULL,
  `check_no` varchar(50) NOT NULL,
  `or_no` varchar(50) DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `payee` varchar(150) NOT NULL,
  `payee_address` text DEFAULT NULL,
  `payee_tin` varchar(50) DEFAULT NULL,
  `dv_no` varchar(50) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `fund` varchar(100) DEFAULT NULL,
  `payroll` varchar(100) DEFAULT NULL,
  `bir` varchar(100) DEFAULT NULL,
  `bir_vat_type` varchar(20) DEFAULT NULL,
  `bir_gross` decimal(12,2) DEFAULT NULL,
  `bir_withholding_a` decimal(12,2) DEFAULT NULL,
  `bir_withholding_b` decimal(12,2) DEFAULT NULL,
  `purpose` text NOT NULL,
  `release_amount` decimal(12,2) NOT NULL,
  `accounting_entries` text DEFAULT NULL,
  `signatory_a` varchar(150) DEFAULT NULL,
  `signatory_b` varchar(150) DEFAULT NULL,
  `signatory_c` varchar(150) DEFAULT NULL,
  `signatory_prepared_by` varchar(150) DEFAULT NULL,
  `signatory_checked_by` varchar(150) DEFAULT NULL,
  `signatory_approved_by` varchar(150) DEFAULT NULL,
  `signatory_received_by` varchar(150) DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `disbursements`
--

INSERT INTO `disbursements` (`id`, `disburse_date`, `check_no`, `or_no`, `received_date`, `payee`, `payee_address`, `payee_tin`, `dv_no`, `amount`, `fund`, `payroll`, `bir`, `bir_vat_type`, `bir_gross`, `bir_withholding_a`, `bir_withholding_b`, `purpose`, `release_amount`, `accounting_entries`, `signatory_a`, `signatory_b`, `signatory_c`, `signatory_prepared_by`, `signatory_checked_by`, `signatory_approved_by`, `signatory_received_by`, `processed_by`, `created_at`) VALUES
(1, '2026-01-15', 'CHK-001-2026', 'OR-2026-201', '2026-01-15', 'Juan Dela Cruz', 'Purok 1, Sto. Rosario, Magallanes', '123-456-789-000', 'DV-2026-001', 5000.00, 'General Fund', 'Salary - January', '110.46', NULL, NULL, NULL, NULL, 'Salary payment for Barangay Tanod', 4889.54, 'Advances for payroll|1-03-05-020|5000.00|0', 'Margie M. Gabato', 'Paz A. Bacolod', 'Cleopatra R. Roces', 'Paz A. Bacolod', 'Cristhel Rhea S. Dela Fuerta', 'Aura R. Cadenas, CPA', 'Juan Dela Cruz', 1, '2026-04-30 00:09:40'),
(2, '2026-01-16', 'CHK-002-2026', 'OR-2026-202', '2026-01-16', 'Uncle Ben Meatshop', 'Purok 2, Sto. Rosario, Magallanes', '900-123-456-000', 'DV-2026-002', 2062.00, 'Special Fund', '', '110.46', NULL, NULL, NULL, NULL, 'Payment for meat supplies', 1951.54, '', 'Margie M. Gabato', 'Paz A. Bacolod', 'Cleopatra R. Roces', 'Paz A. Bacolod', 'Cristhel Rhea S. Dela Fuerta', 'Aura R. Cadenas, CPA', 'Uncle Ben Meatshop', 1, '2026-04-30 00:09:40'),
(3, '2026-01-17', 'CHK-003-2026', 'OR-2026-203', '2026-01-17', 'ABC Hardware Supply', 'Purok 3, Sto. Rosario, Magallanes', '900-234-567-000', 'DV-2026-003', 5000.00, 'General Fund', '', '267.85', NULL, NULL, NULL, NULL, 'Construction materials', 4732.15, '', 'Margie M. Gabato', 'Paz A. Bacolod', 'Cleopatra R. Roces', 'Paz A. Bacolod', 'Cristhel Rhea S. Dela Fuerta', 'Aura R. Cadenas, CPA', 'ABC Hardware Supply', 1, '2026-04-30 00:09:40'),
(4, '2026-01-18', 'CHK-004-2026', 'OR-2026-204', '2026-01-18', 'Maria Santos', 'Purok 4, Sto. Rosario, Magallanes', '234-567-890-000', 'DV-2026-004', 8000.00, 'General Fund', 'Salary - January', '180.00', NULL, NULL, NULL, NULL, 'Salary payment for Barangay Secretary', 7820.00, '', 'Margie M. Gabato', 'Paz A. Bacolod', 'Cleopatra R. Roces', 'Paz A. Bacolod', 'Cristhel Rhea S. Dela Fuerta', 'Aura R. Cadenas, CPA', 'Maria Santos', 1, '2026-04-30 00:09:40'),
(5, '2026-01-19', 'CHK-005-2026', 'OR-2026-205', '2026-01-19', 'PLDT Home', 'Purok 5, Sto. Rosario, Magallanes', '567-890-123-000', 'DV-2026-005', 1500.00, 'General Fund', '', '', NULL, NULL, NULL, NULL, 'Internet and telephone bills', 1500.00, '', 'Margie M. Gabato', 'Paz A. Bacolod', 'Cleopatra R. Roces', 'Paz A. Bacolod', 'Cristhel Rhea S. Dela Fuerta', 'Aura R. Cadenas, CPA', 'PLDT Home', 1, '2026-04-30 00:09:40');

-- --------------------------------------------------------

--
-- Table structure for table `donation`
--

CREATE TABLE `donation` (
  `id` int(11) NOT NULL,
  `donation_ref` varchar(50) DEFAULT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `resident_name` varchar(150) NOT NULL,
  `donation_date` date NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `recipient_activities` text NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `garbage`
--

CREATE TABLE `garbage` (
  `id` int(11) NOT NULL,
  `garbage_ref` varchar(50) DEFAULT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `resident_name` varchar(150) NOT NULL,
  `garbage_date` date NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `recipient_activities` text NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monthly_manual_entries`
--

CREATE TABLE `monthly_manual_entries` (
  `id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `entry_name` varchar(200) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `entry_type` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `monthly_manual_entries`
--

INSERT INTO `monthly_manual_entries` (`id`, `month`, `year`, `entry_name`, `amount`, `entry_type`, `created_at`) VALUES
(1, 1, 2026, 'Share on Real Property Tax', 15000.00, 'Tax Revenue', '2026-04-30 00:09:40'),
(2, 1, 2026, 'Share on Internal Revenue Allotment', 50000.00, 'Tax on Goods & Services', '2026-04-30 00:09:40'),
(3, 1, 2026, 'National Tax Allotment', 25000.00, 'Tax on Goods & Services', '2026-04-30 00:09:40'),
(4, 1, 2026, 'Service Income - Xerox', 500.00, 'Operating & Services', '2026-04-30 00:09:40'),
(5, 1, 2026, 'Donations and Grants', 10000.00, 'Other', '2026-04-30 00:09:40'),
(6, 1, 2026, 'Miscellaneous Income', 2000.00, 'Other', '2026-04-30 00:09:40');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `otp_hash`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 1, '7f6813c9dc797fd63c9bbbd830e5439c', '2026-04-30 08:24:40', NULL, '2026-04-30 00:09:40'),
(2, 2, '19c4c9447c19734f3614d15277549629', '2026-04-30 08:19:40', NULL, '2026-04-30 00:09:40'),
(3, 3, '06d18699b635b67f83ce8b6abdcd070e', '2026-04-30 08:29:40', NULL, '2026-04-30 00:09:40'),
(4, 4, '0fc3d85cd282850c46e9d616b9a717f7', '2026-04-30 08:39:40', NULL, '2026-04-30 00:09:40'),
(5, 5, '499859fa544b0ed774e2ca5ddf94985b', '2026-04-30 08:34:40', NULL, '2026-04-30 00:09:40');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `receipt_no` varchar(50) NOT NULL,
  `payment_date` date NOT NULL,
  `payer_name` varchar(150) NOT NULL,
  `service_type` varchar(100) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `operating_services` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `bir_tax` decimal(10,2) DEFAULT 0.00,
  `remarks` text DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `receipt_no`, `payment_date`, `payer_name`, `service_type`, `purpose`, `operating_services`, `amount`, `bir_tax`, `remarks`, `received_by`, `resident_id`, `created_at`) VALUES
(1, 'OR-2026-001', '2026-01-15', 'Juan Dela Cruz', 'Barangay Clearance', 'Barangay Clearance for Employment', 'Barangay Clearance', 150.00, 0.00, 'For overseas employment', 1, NULL, '2026-04-30 00:09:40'),
(2, 'OR-2026-002', '2026-01-16', 'Maria Santos', 'Barangay Clearance', 'Barangay Clearance for Business Permit', 'Barangay Clearance', 150.00, 0.00, 'Sari-sari store permit', 1, NULL, '2026-04-30 00:09:40'),
(3, 'OR-2026-003', '2026-01-17', 'Pedro Reyes', 'Business Permit', 'Business Permit Renewal', 'Business Permit Fee', 500.00, 0.00, 'Carinderia business', 1, NULL, '2026-04-30 00:09:40'),
(4, 'OR-2026-004', '2026-01-18', 'Ana Garcia', 'Barangay ID', 'Barangay ID Issuance', 'ID Processing Fee', 50.00, 0.00, 'New resident', 1, NULL, '2026-04-30 00:09:40'),
(5, 'OR-2026-005', '2026-01-19', 'Roberto Cruz', 'Barangay Clearance', 'Barangay Clearance for Loan Application', 'Barangay Clearance', 150.00, 0.00, 'Bank loan requirement', 1, NULL, '2026-04-30 00:09:40'),
(6, '1', '2026-05-04', 'Jelo Baliling', 'Barangay Permit', 'Jobseeker', NULL, 30.00, 30.00, 'Pending Status #12', 1, 1851, '2026-05-04 10:19:00'),
(7, '134545', '2026-05-08', 'Nacario, Ben Nacario', 'Cedula', 'Cedula Issuance', NULL, 5.00, 0.00, '', NULL, NULL, '2026-05-08 20:18:13'),
(8, '134546', '2026-05-09', 'Baliling, Jelo', 'Cedula', 'Cedula Request', NULL, 5.00, 0.00, 'Pending Status #20', NULL, 1851, '2026-05-08 22:06:57'),
(9, '134547', '2026-05-09', 'Baliling, Jelo', 'Cedula', 'Cedula Request', NULL, 5.00, 0.00, 'Pending Status #19', NULL, 1851, '2026-05-08 22:07:01'),
(10, '134548', '2026-05-11', 'Ric Charles Lucar Paquibot', 'Barangay Permit', 'For Building permit', NULL, 180.00, 30.00, 'Pending Status #27', NULL, 1860, '2026-05-10 22:25:54'),
(11, '134549', '2026-05-11', 'Ric Charles Lucar Paquibot', 'Barangay Clearance', 'For employment', NULL, 80.00, 30.00, 'Pending Status #30', NULL, 1860, '2026-05-10 23:08:55'),
(12, '134550', '2026-05-11', 'Ric Charles Lucar Paquibot', 'Barangay Clearance', 'For employment', NULL, 80.00, 30.00, 'Pending Status #31', NULL, 1860, '2026-05-10 23:16:05'),
(13, '134551', '2026-05-11', 'Baliling, Jelo', 'Cedula', 'Cedula Request', NULL, 805.00, 0.00, 'Pending Status #32', NULL, 1851, '2026-05-11 00:01:24'),
(14, '123', '2026-05-11', 'Baliling, Jelo', 'Cedula', 'Cedula Issuance', NULL, 805.00, 0.00, '', NULL, NULL, '2026-05-11 00:50:21'),
(15, '124', '2026-05-11', 'Paquibot, Ric Charles Lucar', 'Cedula', 'Cedula Request', NULL, 5.00, 0.00, 'Pending Status #33', NULL, 1860, '2026-05-11 01:48:41'),
(16, '125', '2026-05-11', 'Ric Charles Lucar Paquibot', 'Barangay Permit', 'For building', NULL, 180.00, 30.00, '', NULL, NULL, '2026-05-11 03:03:03'),
(17, '126', '2026-05-11', 'Jelo Baliling', 'Barangay Clearance', 'Business', NULL, 80.00, 30.00, '', NULL, 1851, '2026-05-11 20:30:39'),
(18, '127', '2026-05-12', 'Ric Charles Lucar Paquibot', 'Barangay Clearance', 'For Business', NULL, 80.00, 30.00, 'Pending Status #34', NULL, 1860, '2026-05-12 00:58:37'),
(19, '128', '2026-05-12', 'Ric Charles Lucar Paquibot', 'Good Moral Certificate', 'for employment', NULL, 160.00, 30.00, 'Pending Status #18', NULL, 1855, '2026-05-12 00:58:41'),
(20, '129', '2026-05-12', 'Ric Charles Lucar Paquibot', 'Barangay Permit', 'business', NULL, 330.00, 30.00, 'Pending Status #16', NULL, 1855, '2026-05-12 00:58:44'),
(21, '130', '2026-05-12', 'Ric Charles Lucar Paquibot', 'Barangay Clearance', 'Jobseeker', NULL, 130.00, 30.00, 'Pending Status #15', NULL, 1855, '2026-05-12 00:58:46'),
(22, '131', '2026-05-13', 'Paquibot, Ric Charles Lucar', 'Cedula', 'Cedula Request', NULL, 5.00, 0.00, 'Pending Status #38', NULL, 1860, '2026-05-13 08:02:43'),
(23, '132', '2026-05-13', 'Ric Charles Lucar Paquibot', 'Barangay Clearance', 'For employment', NULL, 80.00, 30.00, 'Pending Status #37', NULL, 1860, '2026-05-13 08:41:42'),
(24, '133', '2026-05-13', 'Ric Charles Lucar Paquibot', 'Barangay Permit', 'asda', NULL, 180.00, 30.00, 'Pending Status #36', NULL, 1860, '2026-05-13 08:41:57'),
(25, '134', '2026-05-13', 'Ric Charles Lucar Paquibot', 'Barangay Clearance', 'For employment', NULL, 80.00, 30.00, 'Pending Status #35', NULL, 1860, '2026-05-13 08:41:59');

-- --------------------------------------------------------

--
-- Table structure for table `payment_status`
--

CREATE TABLE `payment_status` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `certificate_type` varchar(100) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `resident_fname` varchar(150) NOT NULL,
  `payment_status` varchar(20) NOT NULL DEFAULT 'pending',
  `rejection_remarks` text DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `bir_tax` decimal(10,2) DEFAULT 0.00,
  `proof_path` varchar(255) DEFAULT NULL,
  `proof_uploaded_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_status`
--

INSERT INTO `payment_status` (`id`, `resident_id`, `certificate_type`, `purpose`, `resident_fname`, `payment_status`, `rejection_remarks`, `rejected_at`, `amount`, `bir_tax`, `proof_path`, `proof_uploaded_at`, `created_at`) VALUES
(1, NULL, 'Barangay Clearance', 'Employment requirement', 'Jenny D. Flores', 'pending', NULL, NULL, 150.00, 0.00, NULL, NULL, '2026-04-30 00:09:40'),
(2, NULL, 'Business Permit', 'New sari-sari store', 'Marvin C. Uy', 'pending', NULL, NULL, 500.00, 0.00, NULL, NULL, '2026-04-30 00:09:40'),
(3, NULL, 'Barangay ID', 'New ID request', 'Carmela P. Reyes', 'paid', NULL, NULL, 50.00, 0.00, NULL, NULL, '2026-04-30 00:09:40'),
(4, NULL, 'Barangay Clearance', 'Loan application', 'Rogelio M. Perez', 'pending', NULL, NULL, 150.00, 0.00, NULL, NULL, '2026-04-30 00:09:40'),
(5, NULL, 'Barangay Clearance', 'Travel requirement', 'Liza T. Ramos', 'paid', NULL, NULL, 150.00, 0.00, NULL, NULL, '2026-04-30 00:09:40'),
(6, NULL, 'Cedula', 'Cedula Request', 'Luis P. Garcia', 'pending', NULL, NULL, 5.00, 0.00, NULL, NULL, '2026-04-10 01:15:00'),
(7, NULL, 'Cedula', 'Cedula Request', 'Luis P. Garcia', 'pending', NULL, NULL, 5.00, 0.00, NULL, NULL, '2026-04-12 06:40:00'),
(8, NULL, 'Barangay Clearance', 'Employment requirement', 'Luis P. Garcia', 'pending', NULL, NULL, 150.00, 0.00, NULL, NULL, '2026-04-13 00:20:00'),
(9, NULL, 'Barangay ID', 'New ID request', 'Luis P. Garcia', 'pending', NULL, NULL, 50.00, 0.00, NULL, NULL, '2026-04-14 02:05:00'),
(10, NULL, 'Business Permit', 'Small business registration', 'Luis P. Garcia', 'pending', NULL, NULL, 500.00, 0.00, NULL, NULL, '2026-04-15 08:30:00'),
(11, 1851, 'Barangay Clearance', 'Jobseeker', 'Jelo Baliling', 'Paid', NULL, NULL, 30.00, 30.00, NULL, NULL, '2026-05-04 10:12:34'),
(12, 1851, 'Barangay Permit', 'Jobseeker', 'Jelo Baliling', 'paid', NULL, NULL, 30.00, 30.00, NULL, NULL, '2026-05-04 10:19:00'),
(13, 1851, 'Certificate of Residency', 'Jobseeker', 'Jelo Baliling', 'Pending', NULL, NULL, 30.00, 30.00, NULL, NULL, '2026-05-05 07:35:43'),
(14, 1851, 'Good Moral Certificate', 'Jobseeker', 'Jelo Baliling', 'Pending', NULL, NULL, 30.00, 30.00, NULL, NULL, '2026-05-07 07:05:21'),
(15, 1855, 'Barangay Clearance', 'Jobseeker', 'Ric Charles Lucar Paquibot', 'paid', NULL, NULL, 130.00, 30.00, NULL, NULL, '2026-05-12 00:58:46'),
(16, 1855, 'Barangay Permit', 'business', 'Ric Charles Lucar Paquibot', 'paid', NULL, NULL, 330.00, 30.00, NULL, NULL, '2026-05-12 00:58:44'),
(17, 1855, 'Indigency Certificate', 'AICS', 'Ric Charles Lucar Paquibot', 'Paid', NULL, NULL, 0.00, 0.00, NULL, NULL, '2026-05-07 08:52:51'),
(18, 1855, 'Good Moral Certificate', 'for employment', 'Ric Charles Lucar Paquibot', 'paid', NULL, NULL, 160.00, 30.00, NULL, NULL, '2026-05-12 00:58:41'),
(19, 1851, 'Cedula', 'Cedula Request', 'Baliling, Jelo', 'paid', NULL, NULL, 5.00, 0.00, 'uploads/payment_proofs/proof_19_1778275950.png', '2026-05-09 05:32:30', '2026-05-08 22:07:01'),
(20, 1851, 'Cedula', 'Cedula Request', 'Baliling, Jelo', 'paid', NULL, NULL, 5.00, 0.00, NULL, NULL, '2026-05-08 22:06:57'),
(22, 1851, 'Barangay Clearance', 'For employment', 'Jelo Baliling', 'Pending', NULL, NULL, 130.00, 30.00, NULL, NULL, '2026-05-09 12:06:15'),
(27, 1860, 'Barangay Permit', 'For Building permit', 'Ric Charles Lucar Paquibot', 'paid', NULL, NULL, 180.00, 30.00, 'uploads/payment_proofs/proof_1778422190_1860.jpg', '2026-05-10 22:09:50', '2026-05-10 22:25:54'),
(30, 1860, 'Barangay Clearance', 'For employment', 'Ric Charles Lucar Paquibot', 'paid', NULL, NULL, 80.00, 30.00, 'uploads/payment_proofs/proof_1778451881_1860.jpg', '2026-05-11 06:24:41', '2026-05-10 23:08:55'),
(31, 1860, 'Barangay Clearance', 'For employment', 'Ric Charles Lucar Paquibot', 'paid', NULL, NULL, 80.00, 30.00, 'uploads/payment_proofs/proof_1778454914_1860.jpg', '2026-05-11 07:15:14', '2026-05-10 23:16:05'),
(32, 1851, 'Cedula', 'Cedula Request', 'Baliling, Jelo', 'paid', NULL, NULL, 805.00, 0.00, 'uploads/payment_proofs/proof_32_1778457654.jpg', '2026-05-11 08:00:54', '2026-05-11 00:01:24'),
(33, 1860, 'Cedula', 'Cedula Request', 'Paquibot, Ric Charles Lucar', 'paid', NULL, NULL, 5.00, 0.00, 'uploads/payment_proofs/proof_33_1778464043.jpg', '2026-05-11 09:47:23', '2026-05-11 01:48:41'),
(34, 1860, 'Barangay Clearance', 'For Business', 'Ric Charles Lucar Paquibot', 'paid', NULL, NULL, 80.00, 30.00, 'uploads/payment_proofs/proof_1778547171_1860.jpg', '2026-05-12 08:52:51', '2026-05-12 00:58:37'),
(35, 1860, 'Barangay Clearance', 'For employment', 'Ric Charles Lucar Paquibot', 'paid', NULL, NULL, 80.00, 30.00, 'uploads/payment_proofs/proof_1778550118_1860.jpg', '2026-05-12 09:41:58', '2026-05-13 08:41:59'),
(36, 1860, 'Barangay Permit', 'asda', 'Ric Charles Lucar Paquibot', 'paid', NULL, NULL, 180.00, 30.00, 'uploads/payment_proofs/proof_1778550869_1860.jpg', '2026-05-12 09:54:29', '2026-05-13 08:41:57'),
(37, 1860, 'Barangay Clearance', 'For employment', 'Ric Charles Lucar Paquibot', 'paid', NULL, NULL, 80.00, 30.00, 'uploads/payment_proofs/proof_1778658150_1860.jpg', '2026-05-13 15:42:30', '2026-05-13 08:41:42'),
(38, 1860, 'Cedula', 'Cedula Request', 'Paquibot, Ric Charles Lucar', 'paid', NULL, NULL, 5.00, 0.00, NULL, NULL, '2026-05-13 08:02:43'),
(39, 1860, 'Barangay Permit', 'for employment', 'Ric Charles Lucar Paquibot', 'to_review', NULL, NULL, 180.00, 30.00, 'uploads/payment_proofs/proof_1778706706_1860.jpg', '2026-05-14 05:11:46', '2026-05-13 21:11:46'),
(40, 1860, 'Certificate of Residency', 'Travel', 'Ric Charles Lucar Paquibot', 'pending', NULL, NULL, 130.00, 30.00, NULL, NULL, '2026-05-13 21:17:10');

-- --------------------------------------------------------

--
-- Table structure for table `rental`
--

CREATE TABLE `rental` (
  `id` int(11) NOT NULL,
  `rental_ref` varchar(50) DEFAULT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `resident_name` varchar(150) NOT NULL,
  `rental_date` date NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rental_items`
--

CREATE TABLE `rental_items` (
  `id` int(11) NOT NULL,
  `rental_id` int(11) NOT NULL,
  `item_type` varchar(50) NOT NULL COMMENT 'chair or covered_court',
  `quantity` int(11) DEFAULT 1 COMMENT 'Number of chairs (1 for covered court)',
  `unit_price` decimal(10,2) NOT NULL COMMENT 'Price per item',
  `subtotal` decimal(10,2) NOT NULL,
  `usage_date` date NOT NULL COMMENT 'Date the item will be used',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `email_verification_token` varchar(64) DEFAULT NULL,
  `email_verification_expires_at` datetime DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` varchar(50) DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `email_verified_at`, `email_verification_token`, `email_verification_expires_at`, `password`, `name`, `role`, `created_at`, `updated_at`) VALUES
(1, 'treasurer', 'treasurer@example.com', '2026-04-30 08:09:40', NULL, NULL, '8f1f8ef5fcdb0c5521a875083176e7ac', 'Barangay Treasurer', 'treasurer', '2026-04-30 00:09:40', '2026-04-30 00:09:40'),
(2, 'admin', 'admin@example.com', '2026-04-30 08:09:40', NULL, NULL, '0192023a7bbd73250516f069df18b500', 'System Administrator', 'admin', '2026-04-30 00:09:40', '2026-04-30 00:09:40'),
(3, 'staff1', 'staff1@example.com', '2026-04-30 08:09:40', NULL, NULL, 'de9bf5643eabf80f4a56fda3bbb84483', 'Ana L. Ramos', 'staff', '2026-04-30 00:09:40', '2026-04-30 00:09:40'),
(4, 'staff2', 'staff2@example.com', '2026-04-30 08:09:40', NULL, NULL, 'de9bf5643eabf80f4a56fda3bbb84483', 'Leo M. Padilla', 'staff', '2026-04-30 00:09:40', '2026-04-30 00:09:40'),
(5, 'collector', 'collector@example.com', '2026-04-30 08:09:40', NULL, NULL, 'a1f5706761102820b4019f9cf24933da', 'Nina S. Cruz', 'staff', '2026-04-30 00:09:40', '2026-04-30 00:09:40'),
(6, 'resident1', 'resident1@example.com', '2026-04-30 08:09:40', NULL, NULL, '4e74612466aa473adba4e4f77e14e50a', 'Luis P. Garcia', 'resident', '2026-04-30 00:09:40', '2026-04-30 00:09:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bir_records`
--
ALTER TABLE `bir_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `cedula`
--
ALTER TABLE `cedula`
  ADD PRIMARY KEY (`id`),
  ADD KEY `issued_by` (`issued_by`),
  ADD KEY `idx_cedula_resident_id` (`resident_id`);

--
-- Indexes for table `disbursements`
--
ALTER TABLE `disbursements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Indexes for table `donation`
--
ALTER TABLE `donation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `donation_ref` (`donation_ref`),
  ADD KEY `idx_donation_resident_id` (`resident_id`);

--
-- Indexes for table `garbage`
--
ALTER TABLE `garbage`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `garbage_ref` (`garbage_ref`),
  ADD KEY `idx_garbage_resident_id` (`resident_id`);

--
-- Indexes for table `monthly_manual_entries`
--
ALTER TABLE `monthly_manual_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_entry` (`month`,`year`,`entry_name`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_password_resets_user_id` (`user_id`),
  ADD KEY `idx_password_resets_expires_at` (`expires_at`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `received_by` (`received_by`),
  ADD KEY `idx_payments_resident_id` (`resident_id`);

--
-- Indexes for table `payment_status`
--
ALTER TABLE `payment_status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_status_resident_id` (`resident_id`);

--
-- Indexes for table `rental`
--
ALTER TABLE `rental`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rental_ref` (`rental_ref`),
  ADD KEY `idx_rental_resident_id` (`resident_id`);

--
-- Indexes for table `rental_items`
--
ALTER TABLE `rental_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rental_items_rental_id` (`rental_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bir_records`
--
ALTER TABLE `bir_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cedula`
--
ALTER TABLE `cedula`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `disbursements`
--
ALTER TABLE `disbursements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `donation`
--
ALTER TABLE `donation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `garbage`
--
ALTER TABLE `garbage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `monthly_manual_entries`
--
ALTER TABLE `monthly_manual_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `payment_status`
--
ALTER TABLE `payment_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `rental`
--
ALTER TABLE `rental`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rental_items`
--
ALTER TABLE `rental_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bir_records`
--
ALTER TABLE `bir_records`
  ADD CONSTRAINT `bir_records_ibfk_1` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cedula`
--
ALTER TABLE `cedula`
  ADD CONSTRAINT `cedula_ibfk_1` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cedula_resident_id` FOREIGN KEY (`resident_id`) REFERENCES `profiling-system`.`residents` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `disbursements`
--
ALTER TABLE `disbursements`
  ADD CONSTRAINT `disbursements_ibfk_1` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `donation`
--
ALTER TABLE `donation`
  ADD CONSTRAINT `fk_donation_resident_id` FOREIGN KEY (`resident_id`) REFERENCES `profiling-system`.`residents` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `garbage`
--
ALTER TABLE `garbage`
  ADD CONSTRAINT `fk_garbage_resident_id` FOREIGN KEY (`resident_id`) REFERENCES `profiling-system`.`residents` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_resident_id` FOREIGN KEY (`resident_id`) REFERENCES `profiling-system`.`residents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rental`
--
ALTER TABLE `rental`
  ADD CONSTRAINT `fk_rental_resident_id` FOREIGN KEY (`resident_id`) REFERENCES `profiling-system`.`residents` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rental_items`
--
ALTER TABLE `rental_items`
  ADD CONSTRAINT `rental_items_ibfk_1` FOREIGN KEY (`rental_id`) REFERENCES `rental` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
