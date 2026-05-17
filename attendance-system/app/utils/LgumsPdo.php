<?php

/**
 * Shared PDO connection to the LGUMS database.
 */
class LgumsPdo
{
    private static ?PDO $conn = null;

    public static function get(): ?PDO
    {
        if (self::$conn !== null) {
            return self::$conn;
        }

        try {
            $host = defined('DB_HOST') ? DB_HOST : "localhost";
            $dbname = defined("LGUMS_DB_NAME") ? LGUMS_DB_NAME : "lgums";
            $username = defined('LGUMS_DB_USER') ? LGUMS_DB_USER : "root";
            $password = defined('LGUMS_DB_PASS') ? LGUMS_DB_PASS : "";
            $charset = defined('DB_CHARSET') ? DB_CHARSET : "utf8mb4";

            $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
            self::$conn = new PDO($dsn, $username, $password);
            self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            return self::$conn;
        } catch (PDOException $e) {
            error_log("LgumsPdo connection failed: " . $e->getMessage());
            return null;
        }
    }
}
