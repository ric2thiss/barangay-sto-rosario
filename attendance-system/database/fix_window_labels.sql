-- Fix inconsistent window labels in attendances and attendance_windows tables
-- This script normalizes all window values to lowercase for consistency
-- Run this script to fix existing data

-- Step 1: Normalize attendance_windows table labels first (master data)
UPDATE `attendance_windows` 
SET `label` = LOWER(TRIM(`label`))
WHERE `label` != LOWER(TRIM(`label`));

-- Step 2: Normalize attendances table window values
UPDATE `attendances` 
SET `window` = LOWER(TRIM(`window`))
WHERE `window` != LOWER(TRIM(`window`));

-- Verify the changes
-- SELECT DISTINCT `label` FROM `attendance_windows` ORDER BY `label`;
-- SELECT DISTINCT `window` FROM `attendances` ORDER BY `window`;