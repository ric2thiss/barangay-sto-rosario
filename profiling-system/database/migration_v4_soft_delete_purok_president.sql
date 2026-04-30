-- ═══════════════════════════════════════════════════════════════════════════
-- Migration v4 — Soft Delete + Purok President RBAC
-- Run this ONCE against the sto_rosario database.
-- Safe to re-run: uses IF NOT EXISTS / column existence checks.
-- ═══════════════════════════════════════════════════════════════════════════

-- 1. Add is_deleted flag (TINYINT default 0)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'sto_rosario' AND TABLE_NAME = 'residents' AND COLUMN_NAME = 'is_deleted');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE residents ADD COLUMN is_deleted TINYINT(1) NOT NULL DEFAULT 0 AFTER image_path',
    'SELECT "Column is_deleted already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Add deleted_at timestamp
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'sto_rosario' AND TABLE_NAME = 'residents' AND COLUMN_NAME = 'deleted_at');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE residents ADD COLUMN deleted_at DATETIME DEFAULT NULL AFTER is_deleted',
    'SELECT "Column deleted_at already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. Add is_purok_president flag
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'sto_rosario' AND TABLE_NAME = 'residents' AND COLUMN_NAME = 'is_purok_president');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE residents ADD COLUMN is_purok_president TINYINT(1) NOT NULL DEFAULT 0 AFTER deleted_at',
    'SELECT "Column is_purok_president already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. Index for fast soft-delete filtering
SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = 'sto_rosario' AND TABLE_NAME = 'residents' AND INDEX_NAME = 'idx_residents_is_deleted');
SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE residents ADD INDEX idx_residents_is_deleted (is_deleted)',
    'SELECT "Index idx_residents_is_deleted already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. Index for purok president lookups
SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = 'sto_rosario' AND TABLE_NAME = 'residents' AND INDEX_NAME = 'idx_residents_purok_president');
SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE residents ADD INDEX idx_residents_purok_president (is_purok_president)',
    'SELECT "Index idx_residents_purok_president already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'Migration v4 complete ✓' AS result;
