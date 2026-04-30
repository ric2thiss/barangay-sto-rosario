<?php

/**
 * Shared PDO connection to the profiling-system database (same server/credentials as {@see Database}).
 */
class ProfilingPdo
{
    private static ?PDO $conn = null;

    public static function get(): ?PDO
    {
        if (self::$conn !== null) {
            return self::$conn;
        }

        try {
            $host = "localhost";
            $dbname = defined("PROFILING_DB_NAME") ? PROFILING_DB_NAME : "profiling-system";
            $username = "root";
            $password = "";

            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
            self::$conn = new PDO($dsn, $username, $password);
            self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            return self::$conn;
        } catch (PDOException $e) {
            error_log("ProfilingPdo connection failed: " . $e->getMessage());
            return null;
        }
    }
}
