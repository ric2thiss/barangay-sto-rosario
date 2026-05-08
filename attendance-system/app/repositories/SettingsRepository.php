<?php

require_once __DIR__ . '/BaseRepository.php';

class SettingsRepository extends BaseRepository {
    protected function getModelClass(): string {
        return Settings::class;
    }

    /**
     * Get all settings
     * 
     * @return array
     */
    public function getAll(): array
    {
        $settings = Settings::query()->get();
        
        if (empty($settings)) {
            return [];
        }

        // Convert objects to arrays
        return array_map(function($setting) {
            if (is_object($setting)) {
                $setting = json_decode(json_encode($setting), true);
            }
            
            // Convert value based on type
            $value = $setting['value'];
            $type = $setting['type'] ?? 'string';
            
            switch ($type) {
                case 'boolean':
                    $setting['value'] = (bool) $value;
                    break;
                case 'integer':
                    $setting['value'] = (int) $value;
                    break;
                case 'json':
                    $setting['value'] = json_decode($value, true);
                    break;
            }
            
            return $setting;
        }, $settings);
    }

    /**
     * Get setting by key
     * 
     * @param string $key
     * @return array|null
     */
    public function getByKey(string $key): ?array
    {
        $setting = Settings::getByKey($key);
        
        if (!$setting) {
            return null;
        }

        if (is_object($setting)) {
            $setting = json_decode(json_encode($setting), true);
        }

        // Convert value based on type
        $value = $setting['value'];
        $type = $setting['type'] ?? 'string';
        
        switch ($type) {
            case 'boolean':
                $setting['value'] = (bool) $value;
                break;
            case 'integer':
                $setting['value'] = (int) $value;
                break;
            case 'json':
                $setting['value'] = json_decode($value, true);
                break;
        }

        return $setting;
    }

    /**
     * Update setting by key
     * 
     * @param string $key
     * @param mixed $value
     * @param int|null $updatedBy
     * @return bool
     */
    public function updateSetting(string $key, $value, ?int $updatedBy = null): bool
    {
        $setting = Settings::getByKey($key);
        
        // If the key doesn't exist yet (e.g., migrations not seeded), create it.
        if (!$setting) {
            // Best-effort type inference based on incoming value
            $type = 'string';
            if (is_bool($value) || $value === 0 || $value === 1 || $value === '0' || $value === '1') {
                // For checkboxes we expect 0/1
                $type = 'boolean';
            } elseif (is_int($value)) {
                $type = 'integer';
            } elseif (is_array($value) || is_object($value)) {
                $type = 'json';
            } elseif (is_string($value) && preg_match('/^-?\d+$/', $value)) {
                $type = 'integer';
            }

            return Settings::setValue($key, $value, $type, $updatedBy);
        }

        if (is_object($setting)) {
            $setting = json_decode(json_encode($setting), true);
        }

        $type = $setting['type'] ?? 'string';
        return Settings::setValue($key, $value, $type, $updatedBy);
    }

    /**
     * Check if maintenance mode is enabled
     * 
     * @return bool
     */
    public function isMaintenanceMode(): bool
    {
        return Settings::isMaintenanceMode();
    }
}
