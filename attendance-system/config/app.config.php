<?php
/**
 * Application Configuration
 * This file contains all configuration settings for the attendance system
 */

// BASE_PATH is already defined in config.php, so we don't redefine it here
if (!defined("BASE_PATH")) {
    define("BASE_PATH", __DIR__ . "/../");
}

/**
 * Deployment configuration (single file to edit on deploy).
 *
 * Edit: config/deploy.config.json
 * - baseUrl: optional absolute base URL override (ex: "https://yourdomain.com/attendance-system")
 * - database: host/name/user/pass/charset
 * - websocket: host/port
 * - security: apiKey/verificationSecretKey
 */
$__deployConfigPath = __DIR__ . '/deploy.config.json';
$__deploy = [];
if (is_file($__deployConfigPath) && is_readable($__deployConfigPath)) {
    try {
        $__raw = file_get_contents($__deployConfigPath);
        $__decoded = json_decode($__raw, true);
        if (is_array($__decoded)) {
            $__deploy = $__decoded;
        }
    } catch (Throwable $e) {
        // If config can't be read/parsed, fallback to auto-detection/defaults below.
        $__deploy = [];
    }
}

// Security (config-driven; safe defaults for local dev)
if (!defined("API_KEY")) {
    define("API_KEY", (string)($__deploy['security']['apiKey'] ?? "HELLOWORLD"));
}
if (!defined("VERIFICATION_SECRET_KEY")) {
    define("VERIFICATION_SECRET_KEY", (string)($__deploy['security']['verificationSecretKey'] ?? "MY_SECRET_KEY"));
}

/**
 * Dynamically detect base URL from current request
 * Works regardless of folder name or domain
 */
if (!defined("BASE_URL")) {
    $configuredBaseUrl = trim((string)($__deploy['baseUrl'] ?? ''));
    if ($configuredBaseUrl !== '') {
        define("BASE_URL", rtrim($configuredBaseUrl, '/'));
    }
}

if (!defined("BASE_URL")) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = '';
    
    // Method 1: Use SCRIPT_NAME to detect project root (most reliable)
    // This works from any script location in the project
    if (isset($_SERVER['SCRIPT_NAME']) && !empty($_SERVER['SCRIPT_NAME'])) {
        $scriptPath = $_SERVER['SCRIPT_NAME'];
        
        // Remove leading slash and split into parts
        $parts = explode('/', trim($scriptPath, '/'));
        
        // The first part is always the project folder name
        // e.g., /attendance-system/auth/logout.php -> ['attendance-system', 'auth', 'logout.php']
        // e.g., /attendance/admin/dashboard.php -> ['attendance', 'admin', 'dashboard.php']
        if (!empty($parts[0])) {
            $basePath = '/' . $parts[0];
        }
    }
    
    // Method 2: Fallback to REQUEST_URI if SCRIPT_NAME didn't work
    if (empty($basePath) && isset($_SERVER['REQUEST_URI'])) {
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if ($requestUri) {
            $parts = explode('/', trim($requestUri, '/'));
            if (!empty($parts[0])) {
                $basePath = '/' . $parts[0];
            }
        }
    }
    
    // Method 3: Fallback to document root calculation
    if (empty($basePath)) {
        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        $configFile = __FILE__;
        
        // Calculate relative path from document root to project root
        // config.php is in config/ directory, so project root is one level up
        if ($docRoot && strpos($configFile, $docRoot) === 0) {
            // Get the directory containing config.php (which is config/), then go up one level for project root
            $projectRoot = dirname(dirname($configFile));
            // Calculate relative path from document root
            $relativePath = str_replace('\\', '/', substr($projectRoot, strlen($docRoot)));
            $basePath = rtrim($relativePath, '/');
        }
    }
    
    // Build base URL (basePath is already prefixed with / if not empty)
    $baseUrl = $protocol . '://' . $host . $basePath;
    define("BASE_URL", $baseUrl);
}

// WebSocket Configuration
if (!defined("WEBSOCKET_HOST")) {
    define("WEBSOCKET_HOST", (string)($__deploy['websocket']['host'] ?? "localhost"));
}
if (!defined("WEBSOCKET_PORT")) {
    define("WEBSOCKET_PORT", (int)($__deploy['websocket']['port'] ?? 8081));
}
define("WEBSOCKET_URL", "ws://" . WEBSOCKET_HOST . ":" . WEBSOCKET_PORT);

// API Configuration
define("API_BASE_URL", BASE_URL . "/api");

// OLD ROUTER ENDPOINTS (kept for backward compatibility)
// These routers redirect to the new modular endpoints
define("API_SERVICES_URL", API_BASE_URL . "/services.php"); // Router - redirects to modular endpoints
define("API_V1_URL", API_BASE_URL . "/v1/request.php"); // Router - redirects to modular endpoints

// NEW MODULAR API ENDPOINTS
define("API_ENDPOINT_TEMPLATES", API_BASE_URL . "/templates/index.php");
define("API_ENDPOINT_ATTENDANCES", API_BASE_URL . "/attendance/index.php");
define("API_ENDPOINT_ATTENDANCE_WINDOWS", API_BASE_URL . "/attendance/windows.php");
define("API_ENDPOINT_EMPLOYEES", API_BASE_URL . "/employees/index.php");
define("API_ENDPOINT_EMPLOYEES_STORE", API_BASE_URL . "/employees/store.php");
define("API_ENDPOINT_ATTENDANCE_STATS", API_BASE_URL . "/attendance/stats.php");
define("API_ENDPOINT_ATTENDANCE_BETWEEN", API_BASE_URL . "/attendance/between.php");
define("API_ENDPOINT_ACTIVITIES_OPTIONS", API_BASE_URL . "/activities/options.php");
define("API_ENDPOINT_ACTIVITIES_ACTIVE", API_BASE_URL . "/activities/active.php");
define("API_ENDPOINT_RESIDENTS", API_BASE_URL . "/residents/index.php");
define("API_ENDPOINT_RESIDENTS_SHOW", API_BASE_URL . "/residents/show.php");
define("API_ENDPOINT_RESIDENTS_DELETE", API_BASE_URL . "/residents/delete.php");
define("API_ENDPOINT_VISITORS_STATS", API_BASE_URL . "/visitors/stats.php");

// OLD ENDPOINT CONSTANTS (backward compatible - point to router which redirects)
// define("API_ENDPOINT_TEMPLATES_OLD", API_SERVICES_URL . "?resource=templates");
// define("API_ENDPOINT_ATTENDANCES_OLD", API_SERVICES_URL . "?resource=attendances");
// define("API_ENDPOINT_ATTENDANCE_WINDOWS_OLD", API_SERVICES_URL . "?resource=attendance-windows");
// define("API_ENDPOINT_EMPLOYEES_OLD", API_SERVICES_URL . "?resource=employees");

// Direct PHP Endpoints
define("ENROLL_ENDPOINT", BASE_URL . "/enroll.php");
define("BIOMETRIC_VERIFICATION_ENDPOINT", BASE_URL . "/biometricVerification.php");
define("VERIFY_ENDPOINT", BASE_URL . "/verify.php");
define("BIOMETRIC_SUCCESS_ENDPOINT", BASE_URL . "/biometric-success.php");

// Database Configuration (config-driven)
if (!defined("DB_HOST")) {
    define("DB_HOST", (string)($__deploy['database']['host'] ?? "localhost"));
}
if (!defined("DB_NAME")) {
    define("DB_NAME", (string)($__deploy['database']['name'] ?? "attendance-system"));
}
if (!defined("DB_USER")) {
    define("DB_USER", (string)($__deploy['database']['user'] ?? "root"));
}
if (!defined("DB_PASS")) {
    define("DB_PASS", (string)($__deploy['database']['pass'] ?? ""));
}
if (!defined("DB_CHARSET")) {
    define("DB_CHARSET", (string)($__deploy['database']['charset'] ?? "utf8mb4"));
}

// External profiling system database (read-only for attendance-system)
if (!defined("PROFILING_DB_NAME")) {
    define("PROFILING_DB_NAME", (string)($__deploy['databases']['profiling'] ?? "profiling-system"));
}

// Barangay services (certificate requests, blotter, certificate types) — read-only from attendance-system
if (!defined("BARANGAY_SERVICES2_DB_NAME")) {
    define("BARANGAY_SERVICES2_DB_NAME", (string)($__deploy['databases']['barangayServices2'] ?? "barangay_services2"));
}

// LGUMS scheduling database (read-only; schedule_events). Same MySQL server as attendance-system.
if (!defined("LGUMS_DB_NAME")) {
    define("LGUMS_DB_NAME", (string)($__deploy['databases']['lgums'] ?? "lgums"));
}
// Adjust these if your lgums.schedule_events columns differ.
if (!defined("LGUMS_SCHEDULE_EVENTS_TABLE")) {
    define("LGUMS_SCHEDULE_EVENTS_TABLE", "schedule_events");
}
if (!defined("LGUMS_SCHEDULE_EVENT_ID_COL")) {
    define("LGUMS_SCHEDULE_EVENT_ID_COL", "id");
}
if (!defined("LGUMS_SCHEDULE_EVENT_NAME_COL")) {
    define("LGUMS_SCHEDULE_EVENT_NAME_COL", "title");
}
if (!defined("LGUMS_SCHEDULE_EVENT_DATE_COL")) {
    define("LGUMS_SCHEDULE_EVENT_DATE_COL", "event_date");
}

// Application Settings
define("APP_NAME", "Attendance System");
define("APP_VERSION", "1.0.0");

// Timezone
date_default_timezone_set("Asia/Manila"); // Change to your timezone
