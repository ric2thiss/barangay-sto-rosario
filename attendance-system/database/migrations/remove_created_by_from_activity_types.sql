-- Migration: Remove created_by column from activity_types table
-- This removes the foreign key constraint that prevents employee deletion
-- Date: 2025-12-14

-- Step 1: Drop the foreign key constraint
ALTER TABLE `activity_types`
  DROP FOREIGN KEY `activity_types_ibfk_1`;

-- Step 2: Drop the index on created_by (if it exists separately)
ALTER TABLE `activity_types`
  DROP INDEX `created_by`;

-- Step 3: Remove the created_by column
ALTER TABLE `activity_types`
  DROP COLUMN `created_by`;
