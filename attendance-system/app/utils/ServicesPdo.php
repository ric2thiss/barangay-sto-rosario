<?php

/**
 * Shared PDO connection to the Barangay Services 2 database.
 */
class ServicesPdo
{
    private static ?PDO $conn = null;

    public static function get(): ?PDO
    {
        if (self::$conn !== null) {
            return self::$conn;
        }

        try {
            $host = defined('DB_HOST') ? DB_HOST : "localhost";
            $dbname = defined("BARANGAY_SERVICES2_DB_NAME") ? BARANGAY_SERVICES2_DB_NAME : "barangay_services2";
            $username = defined('BARANGAY_SERVICES2_DB_USER') ? BARANGAY_SERVICES2_DB_USER : "root";
            $password = defined('BARANGAY_SERVICES2_DB_PASS') ? BARANGAY_SERVICES2_DB_PASS : "";
            $charset = defined('DB_CHARSET') ? DB_CHARSET : "utf8mb4";

            $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
            self::$conn = new PDO($dsn, $username, $password);
            self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            return self::$conn;
        } catch (PDOException $e) {
            error_log("ServicesPdo connection failed: " . $e->getMessage());
            return null;
        }
    }
}
