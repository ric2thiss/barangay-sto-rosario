<?php

/**
 * Streams a MySQL logical backup (schema + data) as SQL for download.
 * Uses FOREIGN_KEY_CHECKS=0 and drops views before tables where needed.
 */
class DatabaseBackupExporter
{
    public static function streamToOutput(PDO $pdo): void
    {
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
        echo "-- Attendance system full database backup\n";
        echo '-- Generated (UTC): ' . gmdate('Y-m-d H:i:s') . "\n";
        echo '-- Database: ' . self::commentLine($dbName) . "\n\n";

        echo "SET NAMES utf8mb4;\n";
        echo "SET FOREIGN_KEY_CHECKS=0;\n";
        echo "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n";

        $stmt = $pdo->query('SHOW FULL TABLES');
        $tables = [];
        $views = [];
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $name = (string) $row[0];
            $type = (string) ($row[1] ?? 'BASE TABLE');
            if ($type === 'VIEW') {
                $views[] = $name;
            } else {
                $tables[] = $name;
            }
        }

        foreach ($views as $v) {
            echo 'DROP VIEW IF EXISTS ' . self::qi($v) . ";\n";
        }
        if ($views !== []) {
            echo "\n";
        }
        foreach ($tables as $t) {
            echo 'DROP TABLE IF EXISTS ' . self::qi($t) . ";\n";
        }
        if ($tables !== []) {
            echo "\n";
        }

        foreach ($tables as $t) {
            self::streamTable($pdo, $t);
        }
        foreach ($views as $v) {
            self::streamView($pdo, $v);
        }

        echo "\nSET FOREIGN_KEY_CHECKS=1;\n";
    }

    private static function commentLine(string $s): string
    {
        return str_replace(["\n", "\r"], ' ', $s);
    }

    private static function qi(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    private static function streamTable(PDO $pdo, string $table): void
    {
        $q = self::qi($table);
        $row = $pdo->query('SHOW CREATE TABLE ' . $q)->fetch(PDO::FETCH_NUM);
        if (!$row || empty($row[1])) {
            return;
        }
        echo "\n" . $row[1] . ";\n\n";
        self::streamInserts($pdo, $table);
    }

    private static function streamView(PDO $pdo, string $view): void
    {
        $q = self::qi($view);
        $row = $pdo->query('SHOW CREATE VIEW ' . $q)->fetch(PDO::FETCH_NUM);
        if (!$row || empty($row[1])) {
            return;
        }
        echo "\n" . $row[1] . ";\n\n";
    }

    private static function streamInserts(PDO $pdo, string $table): void
    {
        $q = self::qi($table);
        $count = (int) $pdo->query('SELECT COUNT(*) FROM ' . $q)->fetchColumn();
        if ($count === 0) {
            return;
        }

        $chunk = 200;
        $offset = 0;
        $colList = null;

        while ($offset < $count) {
            $sql = 'SELECT * FROM ' . $q . ' LIMIT ' . (int) $chunk . ' OFFSET ' . (int) $offset;
            $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            if ($rows === []) {
                break;
            }
            if ($colList === null) {
                $cols = array_keys($rows[0]);
                $colList = implode(', ', array_map([self::class, 'qi'], $cols));
            } else {
                $cols = array_keys($rows[0]);
            }

            echo 'INSERT INTO ' . $q . ' (' . $colList . ") VALUES\n";
            $lines = [];
            foreach ($rows as $row) {
                $vals = [];
                foreach ($cols as $c) {
                    $vals[] = self::literal($pdo, array_key_exists($c, $row) ? $row[$c] : null);
                }
                $lines[] = '(' . implode(', ', $vals) . ')';
            }
            echo implode(",\n", $lines) . ";\n\n";
            $offset += $chunk;
        }
    }

    private static function literal(PDO $pdo, $v): string
    {
        if ($v === null) {
            return 'NULL';
        }
        if (is_bool($v)) {
            return $v ? '1' : '0';
        }
        if (is_string($v) && $v !== '' && !mb_check_encoding($v, 'UTF-8')) {
            return '0x' . bin2hex($v);
        }
        return $pdo->quote($v);
    }
}
