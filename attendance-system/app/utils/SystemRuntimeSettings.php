<?php

/**
 * Applies DB-backed runtime settings (timezone) once per request after DB is available.
 */
class SystemRuntimeSettings
{
    public static function apply(): void
    {
        try {
            if (!class_exists('Settings')) {
                return;
            }
            $tz = Settings::getValue('timezone', 'Asia/Manila');
            if (!is_string($tz) || $tz === '') {
                $tz = 'Asia/Manila';
            }
            if (!in_array($tz, timezone_identifiers_list(), true)) {
                $tz = 'Asia/Manila';
            }
            date_default_timezone_set($tz);
        } catch (Throwable $e) {
            error_log('SystemRuntimeSettings::apply: ' . $e->getMessage());
        }
    }
}
