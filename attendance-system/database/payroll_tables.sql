-- Payroll System Database Tables
-- Philippine Government Standard Payroll Structure

-- Table: employee_salaries
-- Stores base salary information for each employee
CREATE TABLE IF NOT EXISTS `employee_salaries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(50) NOT NULL,
  `base_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `daily_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `allowances` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  KEY `idx_employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: payruns
-- Stores payroll run/payroll period information
CREATE TABLE IF NOT EXISTS `payruns` (
  `payrun_id` int(11) NOT NULL AUTO_INCREMENT,
  `payrun_date` date NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `status` enum('draft','processing','completed','cancelled') NOT NULL DEFAULT 'draft',
  `total_gross_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_net_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
  `employees_count` int(11) NOT NULL DEFAULT 0,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`payrun_id`),
  KEY `idx_payrun_date` (`payrun_date`),
  KEY `idx_status` (`status`),
  KEY `idx_period` (`period_start`, `period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: payroll_records
-- Stores individual employee payroll records for each payrun
CREATE TABLE IF NOT EXISTS `payroll_records` (
  `record_id` int(11) NOT NULL AUTO_INCREMENT,
  `payrun_id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `days_worked` decimal(5,2) NOT NULL DEFAULT 0.00,
  `hours_worked` decimal(6,2) NOT NULL DEFAULT 0.00,
  `overtime_hours` decimal(6,2) NOT NULL DEFAULT 0.00,
  `gross_pay` decimal(10,2) NOT NULL DEFAULT 0.00,
  `allowances` decimal(10,2) NOT NULL DEFAULT 0.00,
  `overtime_pay` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sss` decimal(10,2) NOT NULL DEFAULT 0.00,
  `philhealth` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pagibig` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `other_deductions` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_pay` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`record_id`),
  KEY `idx_payrun_id` (`payrun_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_payrun_employee` (`payrun_id`, `employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default salary data for existing employees (if not exists)
INSERT IGNORE INTO `employee_salaries` (`employee_id`, `base_salary`, `daily_rate`, `hourly_rate`, `allowances`) VALUES
('2021', 25000.00, 833.33, 104.17, 2000.00),
('20201188', 30000.00, 1000.00, 125.00, 2500.00),
('20201197', 28000.00, 933.33, 116.67, 2200.00);
