<?php

require_once __DIR__ . '/BaseRepository.php';

class VerificationTokenRepository extends BaseRepository {
    protected function getModelClass(): string {
        return VerificationToken::class;
    }

    /**
     * Find token by token value
     * 
     * @param string $token
     * @return object|array|null
     */
    public function findByToken(string $token) {
        return VerificationToken::query()
            ->where("token", $token)
            ->first();
    }

    /**
     * Check if token is valid (not expired)
     * 
     * @param string $token
     * @param int $expirySeconds
     * @return bool
     */
    public function isTokenValid(string $token, int $expirySeconds = 60): bool {
        $record = $this->findByToken($token);
        
        if (!$record) {
            return false;
        }

        $createdAt = is_object($record) ? $record->created_at : $record['created_at'];
        $createdAtObj = new DateTime($createdAt);
        $now = new DateTime();
        $diff = $now->getTimestamp() - $createdAtObj->getTimestamp();

        return $diff <= $expirySeconds;
    }
}
