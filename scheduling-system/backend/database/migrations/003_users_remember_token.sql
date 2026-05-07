-- Migration: Add remember_token columns to users table for "Remember me" login
-- Run this once against pss_db

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `remember_token` VARCHAR(64) DEFAULT NULL AFTER `is_active`,
  ADD COLUMN IF NOT EXISTS `remember_token_expires` DATETIME DEFAULT NULL AFTER `remember_token`;

ALTER TABLE `users`
  ADD INDEX IF NOT EXISTS `idx_users_remember_token` (`remember_token`);
