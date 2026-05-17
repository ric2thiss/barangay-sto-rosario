-- --------------------------------------------------------
-- Table structure for table `admins`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `role` enum('administrator','manager','staff') DEFAULT 'staff',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Seed data: Default Administrator Account
-- --------------------------------------------------------
-- Default credentials:
-- Username: admin
-- Password: password123
-- Email: admin@attendance-system.local
-- 
-- Password is hashed using password_hash() with PASSWORD_DEFAULT
-- Hash: $2y$10$DqQbP29xzsrvZGaYuLHv0OnUVLPghaYBfJqUrhKtuUBFO3vJR2EDy
-- This corresponds to password: password123
-- --------------------------------------------------------

INSERT INTO `admins` (`username`, `email`, `password`, `full_name`, `role`, `is_active`) VALUES
('admin', 'admin@attendance-system.local', '$2y$10$DqQbP29xzsrvZGaYuLHv0OnUVLPghaYBfJqUrhKtuUBFO3vJR2EDy', 'System Administrator', 'administrator', 1);

