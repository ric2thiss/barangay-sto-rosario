<?php

require_once __DIR__ . '/BaseRepository.php';

class CivilStatusRepository extends BaseRepository {
    protected function getModelClass(): string {
        return CivilStatus::class;
    }
}
