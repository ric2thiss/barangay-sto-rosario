<?php

require_once __DIR__ . '/BaseRepository.php';

class PositionRepository extends BaseRepository {
    protected function getModelClass(): string {
        return Position::class;
    }
}
