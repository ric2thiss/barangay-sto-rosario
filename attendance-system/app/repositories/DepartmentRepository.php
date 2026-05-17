<?php

require_once __DIR__ . '/BaseRepository.php';

class DepartmentRepository extends BaseRepository {
    protected function getModelClass(): string {
        return Department::class;
    }
}
