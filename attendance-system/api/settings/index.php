<?php
/**
 * Settings API Endpoint
 * GET: Fetch all settings
 * PUT/POST: Update settings
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

    // Only allow authenticated admins
    require_once __DIR__ . '/../../auth/helpers.php';
    requireAuth();

    $user = currentUser();
    $settingsRoles = ['administrator', 'admin', 'Administrator', 'Admin'];
    if (!$user || !hasRole($settingsRoles)) {
        ob_clean();
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "message" => "Access denied. Administrator role required."
        ]);
        exit;
    }

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    // Check if SettingsController class exists
    if (!class_exists('SettingsController')) {
        throw new Exception("SettingsController class not found");
    }
    
    $settingsController = new SettingsController();

    switch ($method) {
        case 'GET':
            $result = $settingsController->getAll();
            ob_clean();
            echo json_encode($result);
            break;

        case 'POST':
        case 'PUT':
            $raw = file_get_contents('php://input');
            $input = json_decode($raw, true);
            
            if (!$input || !is_array($input)) {
                // Fallback: some setups don't pass PUT/JSON bodies reliably.
                if (!empty($_POST) && is_array($_POST)) {
                    $input = $_POST;
                }
            }

            if (!$input || !is_array($input)) {
                ob_clean();
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid request data"
                ]);
                exit;
            }

            // `settings.updated_by` FK references `admins.id` only. Profiling/SSO users use IDs from
            // other databases and must not be written here or MySQL rejects the update (400).
            $updatedBy = null;
            if (!empty($user['id'])) {
                $localAdmin = Admin::find((int) $user['id']);
                if ($localAdmin) {
                    $updatedBy = (int) $user['id'];
                }
            }

            $result = $settingsController->update($input, $updatedBy);
            
            ob_clean();
            if (!$result['success']) {
                http_response_code(400);
            }
            
            echo json_encode($result);
            break;

        default:
            ob_clean();
            http_response_code(405);
            echo json_encode([
                "success" => false,
                "message" => "Method not allowed"
            ]);
            break;
    }
} catch (Exception $e) {
    // Log the error
    error_log("Settings API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Clean any output and send JSON error
    ob_clean();
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Internal server error",
        "error" => $e->getMessage()
    ]);
} catch (Error $e) {
    // Catch PHP 7+ errors
    error_log("Settings API Fatal Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    ob_clean();
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Internal server error",
        "error" => $e->getMessage()
    ]);
}

// End output buffering
ob_end_flush();
