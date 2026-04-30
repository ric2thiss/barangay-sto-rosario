-- ══════════════════════════════════════════════════════════════════════════
-- Migration: Add LGBTQ+ identity fields to residents, barangay_official,
--            and pending_registrations tables
-- Date: 2026-04-14
-- ══════════════════════════════════════════════════════════════════════════

-- ── 1. residents table ──────────────────────────────────────────────────
ALTER TABLE `residents`
  MODIFY COLUMN `sex` VARCHAR(20) NOT NULL DEFAULT 'Male',
  ADD COLUMN `lgbtq_identity` VARCHAR(100) DEFAULT NULL AFTER `sex`,
  ADD COLUMN `lgbtq_other_text` VARCHAR(200) DEFAULT NULL AFTER `lgbtq_identity`;

-- ── 2. barangay_official table ──────────────────────────────────────────
ALTER TABLE `barangay_official`
  MODIFY COLUMN `sex` VARCHAR(20) NOT NULL DEFAULT 'Male',
  ADD COLUMN `lgbtq_identity` VARCHAR(100) DEFAULT NULL AFTER `sex`,
  ADD COLUMN `lgbtq_other_text` VARCHAR(200) DEFAULT NULL AFTER `lgbtq_identity`;

-- ── 3. pending_registrations table ──────────────────────────────────────
ALTER TABLE `pending_registrations`
  ADD COLUMN `lgbtq_identity` VARCHAR(100) DEFAULT NULL AFTER `sex`,
  ADD COLUMN `lgbtq_other_text` VARCHAR(200) DEFAULT NULL AFTER `lgbtq_identity`;
