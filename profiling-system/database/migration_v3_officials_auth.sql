-- ═══════════════════════════════════════════════════════════════════
-- Migration V3 — Officials Privilege Flags + Last Login
-- Date: 2026-04-16
-- ═══════════════════════════════════════════════════════════════════

ALTER TABLE `barangay_official`
  ADD COLUMN `can_view_residents` TINYINT(1) NOT NULL DEFAULT 1 AFTER `password`,
  ADD COLUMN `can_add_resident` TINYINT(1) NOT NULL DEFAULT 0 AFTER `can_view_residents`,
  ADD COLUMN `can_edit_resident` TINYINT(1) NOT NULL DEFAULT 0 AFTER `can_add_resident`,
  ADD COLUMN `can_approve` TINYINT(1) NOT NULL DEFAULT 0 AFTER `can_edit_resident`,
  ADD COLUMN `can_delete` TINYINT(1) NOT NULL DEFAULT 0 AFTER `can_approve`,
  ADD COLUMN `can_export` TINYINT(1) NOT NULL DEFAULT 0 AFTER `can_delete`,
  ADD COLUMN `last_login` DATETIME DEFAULT NULL AFTER `can_export`;
