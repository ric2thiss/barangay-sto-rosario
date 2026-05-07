<?php
/**
 * API Endpoint: Get resident address
 * GET /api/visitors/resident-address.php?resident_id={id}
 * 
 * Returns formatted address for a resident
 */

// Start output buffering and suppress errors
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Suppress any warnings/notices that might output HTML
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Log errors but don't output them
    error_log("API Error [{$errno}]: {$errstr} in {$errfile} on line {$errline}");
    return true; // Suppress error output
});

require_once __DIR__ . "/../../bootstrap.php";

// Clear any output that might have been generated (bootstrap might output something)
$bootstrapOutput = ob_get_clean();
ob_start(); // Start fresh buffer for JSON

header("Content-Type: application/json");

$method = $_SERVER["REQUEST_METHOD"] ?? 'GET';

if ($method !== "GET") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

$residentId = isset($_GET['resident_id']) ? $_GET['resident_id'] : null;

if (empty($residentId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'resident_id parameter is required'
    ]);
    exit;
}

try {
    $residentController = new ResidentController();
    $residents = $residentController->getAllResidents($residentId);
    
    if (empty($residents)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Resident not found'
        ]);
        exit;
    }

    // Handle different return formats from getAllResidents
    // When ID is provided, it returns a single associative array (not array of arrays)
    // When no ID, it returns an array of resident arrays
    if (is_array($residents)) {
        // Check if it's a numeric array (multiple residents) or associative array (single resident)
        if (isset($residents[0]) && is_array($residents[0])) {
            // Numeric array - multiple residents, get first one
            $resident = $residents[0];
        } elseif (isset($residents['resident_id'])) {
            // Associative array - single resident (when ID provided)
            $resident = $residents;
        } else {
            // Try to get first element
            $resident = reset($residents);
        }
    } else {
        // If it's an object, convert to array
        $resident = is_object($residents) ? (array)$residents : $residents;
    }
    
    // Ensure we have an array
    if (is_object($resident)) {
        $resident = (array)$resident;
    }
    
    // Validate we have resident data
    if (empty($resident) || !isset($resident['resident_id'])) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Resident data not found'
        ]);
        exit;
    }
    
    // Format address from addresses table
    $addressParts = [];
    
    // Check if address data exists (handle both array and object access)
    $houseNumber = is_array($resident) ? ($resident['house_number'] ?? '') : ($resident->house_number ?? '');
    $streetName = is_array($resident) ? ($resident['street_name'] ?? '') : ($resident->street_name ?? '');
    $subdivision = is_array($resident) ? ($resident['subdivision_village'] ?? '') : ($resident->subdivision_village ?? '');
    $purok = is_array($resident) ? ($resident['purok'] ?? '') : ($resident->purok ?? '');
    $barangay = is_array($resident) ? ($resident['barangay'] ?? '') : ($resident->barangay ?? '');
    $city = is_array($resident) ? ($resident['municipality_city'] ?? '') : ($resident->municipality_city ?? '');
    $province = is_array($resident) ? ($resident['province'] ?? '') : ($resident->province ?? '');
    
    if (!empty($houseNumber)) {
        $addressParts[] = $houseNumber;
    }
    
    if (!empty($streetName)) {
        $addressParts[] = $streetName;
    }
    
    if (!empty($subdivision)) {
        $addressParts[] = $subdivision;
    }
    
    if (!empty($purok)) {
        $addressParts[] = stripos($purok, 'purok') === false ? 'Purok ' . $purok : $purok;
    }
    
    if (!empty($barangay)) {
        $addressParts[] = 'Brgy. ' . $barangay;
    }
    
    if (!empty($city)) {
        $addressParts[] = $city;
    }
    
    if (!empty($province)) {
        $addressParts[] = $province;
    }
    
    $formattedAddress = !empty($addressParts) ? implode(', ', $addressParts) : 'Address not available';
    
    // Ensure clean JSON output
    ob_clean();
    
    echo json_encode([
        'success' => true,
        'address' => $formattedAddress,
        'address_parts' => [
            'house_number' => $houseNumber ?: null,
            'street_name' => $streetName ?: null,
            'subdivision_village' => $subdivision ?: null,
            'purok' => $purok ?: null,
            'barangay' => $barangay ?: null,
            'municipality_city' => $city ?: null,
            'province' => $province ?: null,
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch address',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Error $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch address',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
