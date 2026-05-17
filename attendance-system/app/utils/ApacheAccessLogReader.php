<?php

/**
 * Reads the tail of Apache combined/common access logs (e.g. XAMPP).
 */
class ApacheAccessLogReader
{
    public static function resolvePath(): ?string
    {
        try {
            if (class_exists('Settings')) {
                $custom = Settings::getValue('apache_access_log_path', '');
                if (is_string($custom) && $custom !== '' && is_readable($custom)) {
                    return $custom;
                }
            }
        } catch (Throwable $e) {
        }

        $candidates = [];
        if (PHP_OS_FAMILY === 'Windows') {
            $candidates[] = 'C:/xampp/apache/logs/access.log';
            $candidates[] = 'C:\\xampp\\apache\\logs\\access.log';
        }
        $candidates[] = '/var/log/apache2/access.log';
        $candidates[] = '/var/log/httpd/access_log';

        foreach ($candidates as $p) {
            if (is_readable($p)) {
                return $p;
            }
        }
        return null;
    }

    /**
     * @return array{lines: array<int, string>, path: string|null, error: string|null}
     */
    public static function tailLines(int $maxLines = 200): array
    {
        $maxLines = max(1, min(2000, $maxLines));
        $path = self::resolvePath();
        if (!$path) {
            return ['lines' => [], 'path' => null, 'error' => 'Access log not found or not readable. Set path in Settings.'];
        }

        try {
            $file = new SplFileObject($path, 'r');
            $file->seek(PHP_INT_MAX);
            $last = $file->key();
            $start = max(0, $last - $maxLines);
            $lines = [];
            $file->seek($start);
            while (!$file->eof()) {
                $line = $file->current();
                if (is_string($line) && $line !== '') {
                    $lines[] = rtrim($line, "\r\n");
                }
                $file->next();
            }
            return ['lines' => $lines, 'path' => $path, 'error' => null];
        } catch (Throwable $e) {
            return ['lines' => [], 'path' => $path, 'error' => $e->getMessage()];
        }
    }
}
