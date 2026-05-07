-- Migration script to update bir_records table
-- Run this to update the existing database structure

-- Add net_amount column
ALTER TABLE bir_records 
ADD COLUMN net_amount DECIMAL(12, 2) DEFAULT 0 COMMENT 'Net amount to payee (gross - total_amount)';

-- Remove base_amount column (no longer needed)
ALTER TABLE bir_records 
DROP COLUMN base_amount;

-- Update existing records to calculate net_amount
UPDATE bir_records 
SET net_amount = gross_amount - total_amount 
WHERE net_amount = 0;
