-- ═══════════════════════════════════════════════════════════════════
-- Migration V2 — Educational Attainment + Staff Management
-- Run this on the sto_rosario database via phpMyAdmin
-- Date: 2026-04-15
-- ═══════════════════════════════════════════════════════════════════

-- ── 1. Add education columns to `residents` ──────────────────────
ALTER TABLE `residents`
  ADD COLUMN `course` VARCHAR(150) DEFAULT NULL AFTER `school_name`,
  ADD COLUMN `course_other` VARCHAR(150) DEFAULT NULL AFTER `course`,
  ADD COLUMN `graduation_date` DATE DEFAULT NULL AFTER `course_other`,
  ADD COLUMN `eligibility` VARCHAR(100) DEFAULT NULL AFTER `graduation_date`,
  ADD COLUMN `eligibility_other` VARCHAR(150) DEFAULT NULL AFTER `eligibility`;

-- ── 2. Add education columns to `pending_registrations` ──────────
ALTER TABLE `pending_registrations`
  ADD COLUMN `course` VARCHAR(150) DEFAULT NULL AFTER `school_name`,
  ADD COLUMN `course_other` VARCHAR(150) DEFAULT NULL AFTER `course`,
  ADD COLUMN `graduation_date` DATE DEFAULT NULL AFTER `course_other`,
  ADD COLUMN `eligibility` VARCHAR(100) DEFAULT NULL AFTER `graduation_date`,
  ADD COLUMN `eligibility_other` VARCHAR(150) DEFAULT NULL AFTER `eligibility`;

-- ── 3. Add education columns to `barangay_official` ──────────────
ALTER TABLE `barangay_official`
  ADD COLUMN `course` VARCHAR(150) DEFAULT NULL AFTER `school_name`,
  ADD COLUMN `course_other` VARCHAR(150) DEFAULT NULL AFTER `course`,
  ADD COLUMN `graduation_date` DATE DEFAULT NULL AFTER `course_other`,
  ADD COLUMN `eligibility` VARCHAR(100) DEFAULT NULL AFTER `graduation_date`,
  ADD COLUMN `eligibility_other` VARCHAR(150) DEFAULT NULL AFTER `eligibility`;

-- ── 4. Create `staff` table ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS `staff` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100) DEFAULT NULL,
  `surname` VARCHAR(100) NOT NULL,
  `suffix` VARCHAR(10) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `contact_no` VARCHAR(20) DEFAULT NULL,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `position` VARCHAR(100) DEFAULT NULL COMMENT 'Job title / role label',
  -- Privilege flags
  `can_view_residents` TINYINT(1) NOT NULL DEFAULT 1,
  `can_add_resident` TINYINT(1) NOT NULL DEFAULT 0,
  `can_edit_resident` TINYINT(1) NOT NULL DEFAULT 0,
  `can_approve` TINYINT(1) NOT NULL DEFAULT 0,
  `can_delete` TINYINT(1) NOT NULL DEFAULT 0,
  `can_export` TINYINT(1) NOT NULL DEFAULT 0,
  -- Meta
  `status` ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  `image_path` VARCHAR(255) DEFAULT 'default.jpg',
  `created_by` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_staff_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 5. Create `staff_audit_log` table ────────────────────────────
CREATE TABLE IF NOT EXISTS `staff_audit_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `staff_id` INT(11) NOT NULL,
  `staff_username` VARCHAR(50) NOT NULL,
  `action` VARCHAR(100) NOT NULL COMMENT 'e.g. approve_registration, reject_registration, add_resident, edit_resident, login',
  `target_type` VARCHAR(50) DEFAULT NULL COMMENT 'e.g. resident, pending_registration, staff',
  `target_id` INT(11) DEFAULT NULL,
  `details` TEXT DEFAULT NULL COMMENT 'JSON or free-text description',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_staff` (`staff_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 6. Add indexes for new filter columns ────────────────────────
ALTER TABLE `residents`
  ADD KEY `idx_res_course` (`course`(50)),
  ADD KEY `idx_res_eligibility` (`eligibility`),
  ADD KEY `idx_res_grad_date` (`graduation_date`),
  ADD KEY `idx_res_edu` (`educational_attainment`);

ALTER TABLE `pending_registrations`
  ADD KEY `idx_pr_edu` (`educational_attainment`(50));
