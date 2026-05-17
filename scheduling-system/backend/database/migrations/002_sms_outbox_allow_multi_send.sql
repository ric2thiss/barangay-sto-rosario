-- Migration: Allow multiple sms_outbox rows per event+mobile (immediate + auto-notify)
-- Run this once against pss_db

ALTER TABLE `sms_outbox`
  DROP INDEX IF EXISTS `uq_sms_outbox_event_mobile`;

-- Add send_type to distinguish immediate vs auto-notify rows
ALTER TABLE `sms_outbox`
  ADD COLUMN IF NOT EXISTS `send_type` ENUM('immediate','auto') NOT NULL DEFAULT 'immediate'
  AFTER `send_at`;
