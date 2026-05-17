<?php

require_once __DIR__ . '/RepositoryInterface.php';

/**
 * Base Repository
 * Provides common repository functionality for all repositories
 */
abstract class BaseRepository implements RepositoryInterface {
    protected $model;
    protected $pdo;
    protected $modelClass;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->modelClass = $this->getModelClass();
        // Set connection for model static methods
        $this->modelClass::setConnection($pdo);
    }

    /**
     * Conditionally add a 'deleted_at IS NULL' filter if the table has the column.
     *
     * @param QueryBuilder $q
     * @param string|null $tableAlias Optional alias if the query uses one.
     * @param string|null $tableName Override the default model table name.
     */
    protected function applySoftDelete($q, ?string $tableAlias = null, ?string $tableName = null): void
    {
        $table = $tableName ?? $this->modelClass::tableName();
        if (SchemaColumnCache::hasDeletedAt($table)) {
            $col = ($tableAlias !== null && $tableAlias !== '') ? "{$tableAlias}.deleted_at" : 'deleted_at';
            $q->whereRaw("({$col} IS NULL)");
        }
    }

    /**
     * Get the model class name
     * 
     * @return string Model class name
     */
    abstract protected function getModelClass(): string;

    /**
     * Get all records
     * 
     * @return array
     */
    public function findAll(): array {
        $q = $this->modelClass::query();
        $this->applySoftDelete($q);
        return $q->get();
    }

    /**
     * Find record by ID
     * 
     * @param mixed $id
     * @return object|array|null
     */
    public function findById($id) {
        $primaryKey = $this->modelClass::getPrimaryKey();
        $q = $this->modelClass::query()->where($primaryKey, $id);
        $this->applySoftDelete($q);
        return $q->first();
    }

    /**
     * Create a new record
     * 
     * @param array $data
     * @return mixed Last insert ID or false on failure
     */
    public function create(array $data) {
        return $this->modelClass::create($data);
    }

    /**
     * Update record by ID
     * 
     * @param mixed $id
     * @param array $data
     * @return bool
     */
    public function update($id, array $data): bool {
        return $this->modelClass::updateById($id, $data);
    }

    /**
     * Delete record by primary key
     * 
     * @param mixed $id
     * @return bool
     */
    public function delete($id): bool {
        $primaryKey = $this->modelClass::getPrimaryKey();
        return $this->modelClass::query()->where($primaryKey, $id)->delete();
    }

    /**
     * Find record by column
     * 
     * @param string $column
     * @param mixed $value
     * @return object|array|null
     */
    public function findBy(string $column, $value) {
        $q = $this->modelClass::query()->where($column, $value);
        $this->applySoftDelete($q);
        return $q->first();
    }

    /**
     * Find records where conditions match
     * 
     * @param array $conditions
     * @return array
     */
    public function findWhere(array $conditions): array {
        $q = $this->modelClass::query()->where($conditions);
        $this->applySoftDelete($q);
        return $q->get();
    }

    /**
     * Count records
     * 
     * @return int
     */
    public function count(): int {
        $q = $this->modelClass::query();
        $this->applySoftDelete($q);
        return (int) $q->count();
    }

    /**
     * Check if record exists
     * 
     * @param mixed $id
     * @return bool
     */
    public function exists($id): bool {
        $record = $this->findById($id);
        return $record !== null;
    }
}
