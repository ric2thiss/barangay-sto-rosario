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
            $host = defined('DB_HOST') ? DB_HOST : "localhost";
            $dbname = defined("PROFILING_DB_NAME") ? PROFILING_DB_NAME : "profiling-system";
            $username = defined('PROFILING_DB_USER') ? PROFILING_DB_USER : "root";
            $password = defined('PROFILING_DB_PASS') ? PROFILING_DB_PASS : "";
            $charset = defined('DB_CHARSET') ? DB_CHARSET : "utf8mb4";

            $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
            self::$conn = new PDO($dsn, $username, $password);
            self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            return self::$conn;
        } catch (PDOException $e) {
            error_log("ProfilingPdo connection failed: " . $e->getMessage());
            return null;
        }
    }

    public static function fetchResidentsByIds(array $ids): array
    {
        if (empty($ids) || !self::get())
            return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = self::get()->prepare("SELECT id, first_name, middle_name, surname as last_name, sex as gender, birthdate, birthplace as place_of_birth_city, age, purok, barangay, municipality as municipality_city, province, image_path FROM residents WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $results = [];
        foreach ($stmt->fetchAll() as $row) {
            $results[$row['id']] = $row;
        }
        return $results;
    }

    public static function searchResidents(string $searchQuery): array
    {
        if (empty($searchQuery) || !self::get())
            return [];
        $stmt = self::get()->prepare("SELECT id FROM residents WHERE CONCAT(first_name, ' ', surname) LIKE ?");
        $stmt->execute(["%{$searchQuery}%"]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function fetchOfficialsByIds(array $ids): array
    {
        if (empty($ids) || !self::get())
            return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = self::get()->prepare("SELECT id, first_name, surname FROM barangay_official WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $results = [];
        foreach ($stmt->fetchAll() as $row) {
            $results[$row['id']] = $row;
        }
        return $results;
    }

    public static function searchOfficials(string $searchQuery): array
    {
        if (empty($searchQuery) || !self::get())
            return [];
        $stmt = self::get()->prepare("SELECT id FROM barangay_official WHERE CONCAT(first_name, ' ', surname) LIKE ?");
        $stmt->execute(["%{$searchQuery}%"]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
