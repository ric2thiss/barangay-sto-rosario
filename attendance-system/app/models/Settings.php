<?php
require_once __DIR__ . '/Model.php';

class Settings extends Model {
    protected $table = "settings";
    protected $fillable = ["key", "value", "type", "description", "updated_by"];

    /**
     * Get setting by key
     *
     * @param string $key
     * @return array|object|null
     */
    public static function getByKey(string $key)
    {
        return static::query()
            // `key` is a reserved word in MySQL/MariaDB, so quote it.
            ->whereRaw("`key` = ?", [$key])
            ->first();
    }

    /**
     * Get setting value by key (returns just the value, or default)
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = static::getByKey($key);
        
        if (!$setting) {
            return $default;
        }

        // Convert object to array if needed
        if (is_object($setting)) {
            $setting = json_decode(json_encode($setting), true);
        }

        $value = $setting['value'];
        $type = $setting['type'] ?? 'string';

        // Type conversion
        switch ($type) {
            case 'boolean':
                return (bool) $value;
            case 'integer':
                return (int) $value;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Set setting value
     *
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @param int|null $updatedBy
     * @return bool
     */
    public static function setValue(string $key, $value, string $type = 'string', ?int $updatedBy = null): bool
    {
        // Convert value based on type
        switch ($type) {
            case 'boolean':
                $value = $value ? '1' : '0';
                break;
            case 'integer':
                $value = (string) $value;
                break;
            case 'json':
                $value = json_encode($value);
                break;
            default:
                $value = (string) $value;
        }

        // Check if setting exists
        $existing = static::getByKey($key);
        
        if ($existing) {
            // Update existing
            if (is_object($existing)) {
                $existing = json_decode(json_encode($existing), true);
            }
            
            $data = [
                'value' => $value,
                'updated_by' => $updatedBy
            ];
            
            $result = static::query()
                // `key` is a reserved word in MySQL/MariaDB, so quote it.
                ->whereRaw("`key` = ?", [$key])
                ->update($data);

            // QueryBuilder::update() returns affected row count (int).
            // A 0 here means "no change" (same value) but the query still succeeded.
            return $result !== false;
        } else {
            // Create new
            $data = [
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'updated_by' => $updatedBy
            ];
            
            return static::query()->insert($data) !== false;
        }
    }

    /**
     * Check if maintenance mode is enabled
     *
     * @return bool
     */
    public static function isMaintenanceMode(): bool
    {
        return static::getValue('maintenance_mode', false);
    }
}
