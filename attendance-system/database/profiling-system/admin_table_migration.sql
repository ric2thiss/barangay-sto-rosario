-- =============================================================================
-- profiling-system.admin — structure upgrade + seed (run against `profiling-system`)
-- =============================================================================
-- Apply in phpMyAdmin or: mysql -u root profiling-system < admin_table_migration.sql
-- If a statement fails with "Duplicate column" or "Duplicate key name", skip that line.
-- =============================================================================

ALTER TABLE `admin`
  ADD COLUMN `email` varchar(255) NULL DEFAULT NULL AFTER `username`;

ALTER TABLE `admin`
  ADD COLUMN `role` varchar(50) NOT NULL DEFAULT 'administrator' AFTER `password`;

ALTER TABLE `admin`
  ADD COLUMN `is_active` tinyint(1) NOT NULL DEFAULT 1 AFTER `role`;

ALTER TABLE `admin`
  ADD COLUMN `last_login` datetime NULL DEFAULT NULL AFTER `is_active`;

ALTER TABLE `admin`
  ADD COLUMN `created_at` timestamp NOT NULL DEFAULT current_timestamp() AFTER `last_login`;

ALTER TABLE `admin`
  ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `created_at`;

ALTER TABLE `admin` MODIFY `password` varchar(255) NOT NULL;

-- Clear legacy rows before adding unique constraints (resolves duplicate username/email)
DELETE FROM `admin`;

ALTER TABLE `admin` ADD UNIQUE KEY `uq_admin_username` (`username`);
ALTER TABLE `admin` ADD UNIQUE KEY `uq_admin_email` (`email`);

-- Seeded account: username and password are both `admin` (password stored as bcrypt)
INSERT INTO `admin` (`full_name`, `username`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`)
VALUES (
  'System Administrator',
  'admin',
  'admin@local.invalid',
  '$2y$10$bJ0AI6DNRydaqgQ3zJxq7.uT6X3goYEMa0Hk/ZihZzRBFs7JbCGhS',
  'administrator',
  1,
  NOW(),
  NOW()
);
