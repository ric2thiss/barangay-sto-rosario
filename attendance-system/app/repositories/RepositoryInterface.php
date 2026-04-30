<?php

/**
 * Repository Interface
 * Defines contract for all repository implementations
 */
interface RepositoryInterface {
    public function findAll(): array;
    public function findById($id);
    public function create(array $data);
    public function update($id, array $data): bool;
    public function delete($id): bool;
}
