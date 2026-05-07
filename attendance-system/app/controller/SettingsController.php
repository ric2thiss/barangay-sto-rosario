<?php

class SettingsController
{
    protected $settingsRepository;

    public function __construct() {
        $db = (new Database())->connect();
        $this->settingsRepository = new SettingsRepository($db);
    }

    /**
     * Get all settings
     *
     * @return array
     */
    public function getAll(): array
    {
        try {
            $settings = $this->settingsRepository->getAll();
            
            // Convert to key-value pairs for easier frontend access
            $settingsMap = [];
            foreach ($settings as $setting) {
                $settingsMap[$setting['key']] = $setting;
            }
            
            return [
                "success" => true,
                "settings" => $settingsMap
            ];
        } catch (Exception $e) {
            error_log("SettingsController::getAll - Error: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Failed to fetch settings: " . $e->getMessage()
            ];
        }
    }

    /**
     * Update settings
     *
     * @param array $settingsData
     * @param int|null $updatedBy
     * @return array
     */
    public function update(array $settingsData, ?int $updatedBy = null): array
    {
        try {
            $updated = [];
            $errors = [];

            foreach ($settingsData as $key => $value) {
                try {
                    $success = $this->settingsRepository->updateSetting($key, $value, $updatedBy);
                    if ($success) {
                        $updated[] = $key;
                    } else {
                        $errors[] = "Failed to update setting: $key";
                    }
                } catch (Exception $e) {
                    error_log("SettingsController::update - Error updating $key: " . $e->getMessage());
                    $errors[] = "Error updating $key: " . $e->getMessage();
                }
            }

            if (!empty($errors) && empty($updated)) {
                return [
                    "success" => false,
                    "message" => "Failed to update settings",
                    "errors" => $errors
                ];
            }

            return [
                "success" => true,
                "message" => "Settings updated successfully",
                "updated" => $updated,
                "errors" => $errors
            ];
        } catch (Exception $e) {
            error_log("SettingsController::update - Error: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Failed to update settings: " . $e->getMessage()
            ];
        }
    }

    /**
     * Check maintenance mode
     *
     * @return array
     */
    public function checkMaintenanceMode(): array
    {
        try {
            $isMaintenance = $this->settingsRepository->isMaintenanceMode();
            $message = Settings::getValue('maintenance_message', 'The system is currently under maintenance. Please try again later.');
            
            return [
                "maintenance_mode" => $isMaintenance,
                "message" => $message
            ];
        } catch (Exception $e) {
            error_log("SettingsController::checkMaintenanceMode - Error: " . $e->getMessage());
            return [
                "maintenance_mode" => false,
                "message" => "Unable to check maintenance status"
            ];
        }
    }
}
