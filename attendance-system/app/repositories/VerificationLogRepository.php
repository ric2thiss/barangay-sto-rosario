<?php

require_once __DIR__ . '/BaseRepository.php';

class VerificationLogRepository extends BaseRepository {
    protected function getModelClass(): string {
        return VerificationLog::class;
    }
}
