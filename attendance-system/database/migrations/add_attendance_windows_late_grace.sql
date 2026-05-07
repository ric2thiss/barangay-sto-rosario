-- Per-window late grace: minutes after start_time that still count as on-time for *_in logs.
-- NULL = use global Settings key attendance_late_grace_minutes (default 15).

ALTER TABLE `attendance_windows`
ADD COLUMN `late_grace_minutes` INT UNSIGNED NULL DEFAULT NULL
COMMENT 'Late threshold minutes after start for clock-in; NULL uses global setting'
AFTER `end_time`;
