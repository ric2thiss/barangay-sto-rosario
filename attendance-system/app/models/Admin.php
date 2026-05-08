<?php
require_once __DIR__ . '/Model.php';

class Admin extends Model {
    protected $table = "admins";
    protected $fillable = ["username", "email", "password", "full_name", "role", "is_active", "last_login"];

    /**
     * Find admin by username
     *
     * @param string $username
     * @return array|null
     */
    public static function findByUsername(string $username)
    {
        return static::query()
            ->where("username", $username)
            ->where("is_active", 1)
            ->first();
    }

    /**
     * Find admin by email
     *
     * @param string $email
     * @return array|null
     */
    public static function findByEmail(string $email)
    {
        return static::query()
            ->where("email", $email)
            ->where("is_active", 1)
            ->first();
    }

    /**
     * Verify password
     *
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Hash password
     *
     * @param string $password
     * @return string
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Update last login timestamp
     *
     * @param int $adminId
     * @return bool
     */
    public static function updateLastLogin(int $adminId): bool
    {
        return static::query()
            ->where("id", $adminId)
            ->update(["last_login" => date("Y-m-d H:i:s")]);
    }
}

