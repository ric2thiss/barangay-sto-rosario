<?php

/**
 * Permanently removes rows soft-deleted for 30+ days. Invoked from bootstrap (no cron).
 */
class SoftDeletedLogsPurge
{
    public static function run(?PDO $pdo = null): void
    {
        try {
            if ($pdo === null) {
                if (!class_exists('Database')) {
                    return;
                }
                $pdo = (new Database())->connect();
            }
            $pdo->exec(
                'DELETE FROM attendances WHERE deleted_at IS NOT NULL AND deleted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)'
            );
            $pdo->exec(
                'DELETE FROM visitor_logs WHERE deleted_at IS NOT NULL AND deleted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)'
            );
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Unknown column') && str_contains($msg, 'deleted_at')) {
                return;
            }
            error_log('SoftDeletedLogsPurge: ' . $msg);
        }
    }
}
