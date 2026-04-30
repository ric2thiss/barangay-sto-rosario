<?php

/**
 * Login-time access: which account categories may authenticate (Option A — checked at login only).
 */
class UserAccessControlSettings
{
    private static function defaults(): array
    {
        return [
            'attendance_admins' => true,
            'profiling_admin' => true,
            'barangay_officials' => true,
            'residents' => true,
        ];
    }

    public static function get(): array
    {
        try {
            if (!class_exists('Settings')) {
                return self::defaults();
            }
            $raw = Settings::getValue('user_access_control', self::defaults());
            if (!is_array($raw)) {
                return self::defaults();
            }
            return array_merge(self::defaults(), $raw);
        } catch (Throwable $e) {
            return self::defaults();
        }
    }

    public static function isLoginAllowed(string $source): bool
    {
        $cfg = self::get();
        switch ($source) {
            case 'attendance_admin':
                return !empty($cfg['attendance_admins']);
            case 'profiling_admin':
                return !empty($cfg['profiling_admin']);
            case 'profiling_barangay_official':
                return !empty($cfg['barangay_officials']);
            case 'profiling_resident':
                return !empty($cfg['residents']);
            default:
                return true;
        }
    }
}
