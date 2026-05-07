<?php

require_once __DIR__ . '/BaseRepository.php';

class AdminRepository extends BaseRepository {
    protected function getModelClass(): string {
        return Admin::class;
    }

    /**
     * Find admin by username
     * 
     * @param string $username
     * @return object|array|null
     */
    public function findByUsername(string $username) {
        return Admin::findByUsername($username);
    }

    /**
     * Find admin by email
     * 
     * @param string $email
     * @return object|array|null
     */
    public function findByEmail(string $email) {
        return Admin::findByEmail($email);
    }

    /**
     * Verify password
     * 
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public function verifyPassword(string $password, string $hash): bool {
        return Admin::verifyPassword($password, $hash);
    }

    /**
     * Hash password
     * 
     * @param string $password
     * @return string
     */
    public function hashPassword(string $password): string {
        return Admin::hashPassword($password);
    }

    /**
     * Update last login timestamp
     * 
     * @param int $adminId
     * @return bool
     */
    public function updateLastLogin(int $adminId): bool {
        return Admin::updateLastLogin($adminId);
    }
}
