<?php

/**
 * Caches whether optional columns exist (e.g. before soft-delete migration runs).
 */
class SchemaColumnCache
{
    private static ?bool $attendancesDeletedAt = null;
    private static ?bool $visitorLogsDeletedAt = null;

    public static function attendancesHasDeletedAt(): bool
    {
        if (self::$attendancesDeletedAt !== null) {
            return self::$attendancesDeletedAt;
        }
        self::$attendancesDeletedAt = self::columnExists('attendances', 'deleted_at');

        return self::$attendancesDeletedAt;
    }

    public static function visitorLogsHasDeletedAt(): bool
    {
        if (self::$visitorLogsDeletedAt !== null) {
            return self::$visitorLogsDeletedAt;
        }
        self::$visitorLogsDeletedAt = self::columnExists('visitor_logs', 'deleted_at');

        return self::$visitorLogsDeletedAt;
    }

    public static function resetCache(): void
    {
        self::$attendancesDeletedAt = null;
        self::$visitorLogsDeletedAt = null;
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
