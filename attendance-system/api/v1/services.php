<?php
/**
 * Backward Compatibility Router - services.php
 * 
 * This file maintains backward compatibility with the old API structure.
 * It routes requests to the new modular API structure.
 * 
 * OLD ENDPOINT: /api/services.php?resource={resource}
 * NEW ENDPOINTS:
 *   - /api/attendance/index.php (GET/POST for attendances)
 *   - /api/attendance/windows.php (GET for attendance-windows)
 *   - /api/templates/index.php (GET for templates)
 *   - /api/employees/index.php (GET for employees)
 */

require_once __DIR__ . "/../bootstrap.php";
header("Content-Type: application/json");

$resource = $_GET["resource"] ?? null;
$method = $_SERVER["REQUEST_METHOD"];

if (!isset($_GET['resource'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing resource"]);
    exit;
}

// Route to new modular structure
switch ($resource) {
    case 'attendances':
        // OLD: /api/services.php?resource=attendances
        // NEW: /api/attendance/index.php
        require_once __DIR__ . "/attendance/index.php";
        break;
    
    case 'templates':
        // OLD: /api/services.php?resource=templates
        // NEW: /api/templates/index.php
        require_once __DIR__ . "/templates/index.php";
        break;
    
    case 'attendance-windows':
        // OLD: /api/services.php?resource=attendance-windows
        // NEW: /api/attendance/windows.php
        require_once __DIR__ . "/attendance/windows.php";
        break;
    
    case 'employees':
        // OLD: /api/services.php?resource=employees
        // NEW: /api/employees/index.php
        require_once __DIR__ . "/employees/index.php";
        break;
    
    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not supported"]);
        break;
}
