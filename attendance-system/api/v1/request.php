<?php
/**
 * Backward Compatibility Router - v1/request.php
 * 
 * This file maintains backward compatibility with the old API structure.
 * It routes requests to the new modular API structure.
 * 
 * OLD ENDPOINTS:
 *   - /api/v1/request.php?query=residents&id={id} (GET/DELETE)
 *   - /api/v1/request.php?query=employees&filter=all (GET)
 *   - /api/v1/request.php?query=employees (POST)
 *   - /api/v1/request.php?query=attendance&from={date}&to={date} (GET)
 *   - /api/v1/request.php?query=attendance&filter=all (GET)
 * 
 * NEW ENDPOINTS:
 *   - /api/residents/index.php (GET all)
 *   - /api/residents/show.php?id={id} (GET one)
 *   - /api/residents/delete.php?id={id} (DELETE)
 *   - /api/employees/index.php (GET all)
 *   - /api/employees/store.php (POST)
 *   - /api/attendance/between.php?from={date}&to={date} (GET between dates)
 *   - /api/attendance/index.php?filter=all (GET all)
 */

header("Content-Type: application/json");

$method = $_SERVER["REQUEST_METHOD"];
$query  = $_GET["query"] ?? null;
$filter = $_GET["filter"] ?? null;
$id = $_GET["id"] ?? null;

/**
 * Utility function for JSON responses
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Validate query parameter
 */
if (empty($query)) {
    jsonResponse(["error" => "Missing query"], 400);
}

/**
 * ---------------------------------------------------------------
 * ROUTING LOGIC - Routes to new modular structure
 * ---------------------------------------------------------------
 */
try {
    switch ($query) {
        /**
         * ---------------------------------------------------------------
         * RESIDENTS API
         * ---------------------------------------------------------------
         */
        case "residents":
            // OLD: /api/v1/request.php?query=residents&id=2
            // NEW: /api/residents/show.php?id={id} (GET) or /api/residents/index.php (GET all)
            //      /api/residents/delete.php?id={id} (DELETE)
            
            if ($method === "GET") {
                if (!empty($id)) {
                    // Route to show.php for single resident
                    require_once __DIR__ . "/../residents/show.php";
                } else {
                    // Route to index.php for all residents
                    require_once __DIR__ . "/../residents/index.php";
                }
                break;
            }

            if ($method === "DELETE" || $method === "POST") {
                // Route to delete.php
                require_once __DIR__ . "/../residents/delete.php";
                break;
            }

            jsonResponse(["error" => "Bad request"], 400);
            break;

        /**
         * ---------------------------------------------------------------
         * EMPLOYEES API
         * ---------------------------------------------------------------
         */
        case "employees":
            // OLD: /api/v1/request.php?query=employees&filter=all
            // NEW: /api/employees/index.php (GET) or /api/employees/store.php (POST)
            
            if ($method === "GET") {
                if ($filter === "all") {
                    require_once __DIR__ . "/../employees/index.php";
                } else {
                    jsonResponse(["error" => "Missing or invalid filter"], 400);
                }
                break;
            }

            if ($method === "POST") {
                require_once __DIR__ . "/../employees/store.php";
                break;
            }

            jsonResponse(["error" => "Bad request"], 400);
            break;

        /**
         * ---------------------------------------------------------------
         * ATTENDANCE API
         * ---------------------------------------------------------------
         */
        case "attendance":
            // OLD: /api/v1/request.php?query=attendance&from={date}&to={date}
            //      /api/v1/request.php?query=attendance&filter=all
            // NEW: /api/attendance/between.php?from={date}&to={date} (GET between dates)
            //      /api/attendance/index.php (GET all)
            
            $from = $_GET["from"] ?? null;
            $to   = $_GET["to"] ?? null;

            if ($from && $to) {
                // Route to between.php for date range query
                require_once __DIR__ . "/../attendance/between.php";
                break;
            }

            if ($filter === "all") {
                // Route to index.php for all attendance
                require_once __DIR__ . "/../attendance/index.php";
                break;
            }

            jsonResponse(["error" => "Missing 'from'/'to' parameters or 'filter=all'"], 400);
            break;

        /**
         * ---------------------------------------------------------------
         * TEST API Endpoint (kept for backward compatibility)
         * ---------------------------------------------------------------
         */
        case "test":
            require_once __DIR__ . "/../../bootstrap.php";
            $employeesController = new EmployeeController();

            // Example test data
            $data = [
                "resident_id" => 1,
                "position" => "Manager",
                "department" => "HR"
            ];

            // Run the store method
            $employeesController->store($data);
            jsonResponse(["message" => "Test endpoint executed"], 200);
            break;

        /**
         * ---------------------------------------------------------------
         * UNKNOWN QUERY
         * ---------------------------------------------------------------
         */
        default:
            jsonResponse(["error" => "Unknown query parameter"], 404);
    }

} catch (Exception $e) {
    jsonResponse([
        "error" => "Server error",
        "message" => $e->getMessage()
    ], 500);
}

