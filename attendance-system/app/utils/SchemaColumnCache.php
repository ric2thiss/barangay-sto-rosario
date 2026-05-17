<?php

/**
 * Caches whether optional columns exist (e.g. before soft-delete migration runs).
 */
class SchemaColumnCache
{
    private static ?bool $attendancesDeletedAt = null;
    private static ?bool $visitorLogsDeletedAt = null;
    private static array $tableHasDeletedAt = [];

    public static function hasDeletedAt(string $table): bool
    {
        if (isset(self::$tableHasDeletedAt[$table])) {
            return self::$tableHasDeletedAt[$table];
        }
        self::$tableHasDeletedAt[$table] = self::columnExists($table, 'deleted_at');
        return self::$tableHasDeletedAt[$table];
    }

    public static function attendancesHasDeletedAt(): bool
    {
        return self::hasDeletedAt('attendances');
    }

    public static function visitorLogsHasDeletedAt(): bool
    {
        return self::hasDeletedAt('visitor_logs');
    }

    public static function activitiesHasDeletedAt(): bool
    {
        return self::hasDeletedAt('activities');
    }

    public static function attendanceWindowsHasDeletedAt(): bool
    {
        return self::hasDeletedAt('attendance_windows');
    }

    public static function resetCache(): void
    {
        self::$attendancesDeletedAt = null;
        self::$visitorLogsDeletedAt = null;
        self::$tableHasDeletedAt = [];
    }

    private static function columnExists(string $table, string $column): bool
    {
        try {
            $pdo = (new Database())->connect();
            $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
            if (!$dbName) {
                return false;
            }
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $stmt->execute([$dbName, $table, $column]);

            return (int) $stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }
}
