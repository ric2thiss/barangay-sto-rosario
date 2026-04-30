<?php
/**
 * Maintenance Mode Check API Endpoint
 * Public endpoint to check if system is in maintenance mode
 * Used by login.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../bootstrap.php';

try {
    $settingsController = new SettingsController();
    $result = $settingsController->checkMaintenanceMode();
    echo json_encode($result);
} catch (Exception $e) {
    error_log("Maintenance Check API Error: " . $e->getMessage());
    echo json_encode([
        "maintenance_mode" => false,
        "message" => "Unable to check maintenance status"
    ]);
}
