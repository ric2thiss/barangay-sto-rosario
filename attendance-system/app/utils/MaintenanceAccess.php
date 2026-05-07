<?php

/**
 * Roles allowed to use the system while maintenance_mode is on.
 */
class MaintenanceAccess
{
    private const EXEMPT_ROLES = [
        'administrator',
        'admin',
        'barangay secretary',
    ];

    public static function isExempt(?string $role): bool
    {
        if ($role === null || $role === '') {
            return false;
        }
        $n = mb_strtolower(trim($role));
        foreach (self::EXEMPT_ROLES as $allowed) {
            if ($n === $allowed) {
                return true;
            }
        }
        return false;
    }
}
