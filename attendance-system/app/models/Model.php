<?php
require_once __DIR__ . '/../../app/query/QueryBuilder.php';

/**
 * Base Model class for Laravel-style ORM
 */
class Model extends QueryBuilder
{
    /**
     * Table associated with the model
     *
     * @var string
     */
    protected $table;

    /**
     * Primary key column name
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Mass assignable fields
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * Shared PDO connection for all models
     *
     * @var PDO|null
     */
    protected static $pdo = null;

    /**
     * Constructor
     *
     * @param PDO|null $dbconn
     * @throws Exception
     */
    public function __construct(?PDO $dbconn = null)
    {
        if ($dbconn instanceof PDO) {
            static::$pdo = $dbconn;
            parent::__construct($dbconn);
            return;
        }

        if (!static::$pdo) {
            throw new Exception("No PDO connection set. Call Model::setConnection(\$pdo) first.");
        }

        parent::__construct(static::$pdo);
    }

    /**
     * Set shared PDO connection
     *
     * @param PDO $pdo
     */
    public static function setConnection(PDO $pdo): void
    {
        static::$pdo = $pdo;
    }

    /**
     * Get table name
     *
     * @return string
     */
    public static function tableName(): string
    {
        return (new static(static::$pdo))->table;
    }

    /**
     * Start a new query for the model
     *
     * @return QueryBuilder
     */
    public static function query(): QueryBuilder
    {
        return (new static(static::$pdo))->table(static::tableName());
    }

    /**
     * Get all rows
     *
     * @return array
     */
    // public static function all(): array
    // {
    //     return static::query()->get();
    // }

    /**
     * Get all rows, optionally selecting specific columns
     *
     * @param array $columns
     * @return array
     */
    public static function all(array $columns = ["*"]): array
    {
        return static::query()->select(...$columns)->get();
    }

    /**
     * Get primary key column name
     *
     * @return string
     */
    public static function getPrimaryKey(): string
    {
        return (new static(static::$pdo))->primaryKey;
    }

    /**
     * Find row by primary key
     *
     * @param mixed $id
     * @return array|null|object
     */
    public static function find($id)
    {
        $instance = new static(static::$pdo);
        return static::query()->where($instance->primaryKey, $id)->first();
    }

    /**
     * Create a new record (fillable protection)
     *
     * @param array $data
     * @return int Last insert ID
     * @throws Exception
     */
    public static function create(array $data)
    {
        $instance = new static(static::$pdo);
        $filtered = array_intersect_key($data, array_flip($instance->fillable));

        if (empty($filtered)) {
            throw new Exception("No valid fillable fields provided.");
        }

        return static::query()->insert($filtered);
    }


    /**
     * Update rows using fillable protection (non-static)
     *
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function update(array $data): bool
    {
        $fillableData = array_intersect_key($data, array_flip($this->fillable));

        if (empty($fillableData)) {
            throw new Exception("No valid fillable fields provided for update.");
        }

        return parent::update($fillableData);
    }

    /**
     * Static helper to update by primary key (Laravel style)
     *
     * @param mixed $id
     * @param array $data
     * @return bool
     */
    public static function updateById($id, array $data): bool
    {
        $instance = new static(static::$pdo);
        return static::query()->where($instance->primaryKey, $id)->update($data);
    }

    /**
     * Save the current instance (insert or update depending on existence)
     *
     * @return bool|int
     * @throws Exception
     */
    public function save()
    {
        if (isset($this->id)) {
            return $this->update((array)$this);
        } else {
            return static::create((array)$this);
        }
    }

    /**
     * Define one-to-one relationship
     *
     * @param string $relatedModel
     * @param string $foreignKey
     * @param string $localKey
     * @return QueryBuilder
     */
    public function hasOne(string $relatedModel, string $foreignKey, string $localKey = "id"): QueryBuilder
    {
        $relatedInstance = new $relatedModel(static::$pdo);
        $localValue = $this->{$localKey};
        return $relatedInstance->query()->where($foreignKey, $localValue)->limit(1);
    }

    /**
     * Define one-to-many relationship
     *
     * @param string $relatedModel
     * @param string $foreignKey
     * @param string $localKey
     * @return QueryBuilder
     */
    public function hasMany(string $relatedModel, string $foreignKey, string $localKey = "id"): QueryBuilder
    {
        $relatedInstance = new $relatedModel(static::$pdo);
        $localValue = $this->{$localKey};
        return $relatedInstance->query()->where($foreignKey, $localValue);
    }

    /**
     * Define inverse one-to-one or one-to-many relationship
     *
     * @param string $relatedModel
     * @param string $foreignKey
     * @param string $ownerKey
     * @return QueryBuilder
     */
    public function belongsTo(string $relatedModel, string $foreignKey, string $ownerKey = "id"): QueryBuilder
    {
        $relatedInstance = new $relatedModel(static::$pdo);
        $foreignValue = $this->{$foreignKey};
        return $relatedInstance->query()->where($ownerKey, $foreignValue)->limit(1);
    }

    /**
     * Forward static calls to QueryBuilder to allow chainable Laravel-style queries
     *
     * @param string $method
     * @param array  $arguments
     * @return mixed
     */
    public static function __callStatic($method, $arguments)
    {
        $instance = new static(static::$pdo);

        // If the method exists on the model itself (like updateById), call it
        if (method_exists($instance, $method)) {
            return $instance->$method(...$arguments);
        }

        // Otherwise, forward to a QueryBuilder instance
        $query = (new static(static::$pdo))->table($instance->table);
        return $query->$method(...$arguments);
    }

}
