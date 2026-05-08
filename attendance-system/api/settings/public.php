<?php
/**
 * Public Settings API Endpoint
 * GET: Fetch public settings (app_name, etc.) - accessible by all authenticated users
 */

// Turn off error display, log errors instead
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON header first
header('Content-Type: application/json');

// Start output buffering to catch any errors
ob_start();

try {
    require_once __DIR__ . '/../../bootstrap.php';

    // Only allow authenticated users (not restricted to admin)
    require_once __DIR__ . '/../../auth/helpers.php';
    requireAuth();

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    if ($method !== 'GET') {
        ob_clean();
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "message" => "Method not allowed"
        ]);
        exit;
    }

    // Check if SettingsController class exists
    if (!class_exists('SettingsController')) {
        throw new Exception("SettingsController class not found");
    }
    
    $settingsController = new SettingsController();
    $result = $settingsController->getAll();
    
    if ($result['success'] && isset($result['settings'])) {
        // Return only public settings (app_name)
        $publicSettings = [
            'success' => true,
            'data' => [
                'app_name' => $result['settings']['app_name'] ?? [
                    'value' => 'Attendance System',
                    'type' => 'string'
                ]
            ]
        ];
        
        ob_clean();
        echo json_encode($publicSettings);
    } else {
        // Return default if settings not found
        ob_clean();
        echo json_encode([
            'success' => true,
            'data' => [
                'app_name' => [
                    'value' => 'Attendance System',
                    'type' => 'string'
                ]
            ]
        ]);
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Public Settings API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Clean any output and return default settings
    ob_clean();
    echo json_encode([
        'success' => true,
        'data' => [
            'app_name' => [
                'value' => 'Attendance System',
                'type' => 'string'
            ]
        ]
    ]);
} catch (Error $e) {
    // Catch PHP 7+ errors
    error_log("Public Settings API Fatal Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'data' => [
            'app_name' => [
                'value' => 'Attendance System',
                'type' => 'string'
            ]
        ]
    ]);
}

// End output buffering
ob_end_flush();
