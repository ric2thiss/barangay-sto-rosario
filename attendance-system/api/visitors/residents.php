<?php
/**
 * API Endpoint: Get residents with photos for face recognition
 * GET /api/visitors/residents.php
 * 
 * Returns residents with their photo paths formatted for face recognition
 */
require_once __DIR__ . "/../../bootstrap.php";

header("Content-Type: application/json");

$method = $_SERVER["REQUEST_METHOD"];

if ($method !== "GET") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

try {
    $residentController = new ResidentController();
    $residents = $residentController->getAllResidents();
    
    /**
     * Build an absolute origin (scheme + host) like: http://localhost
     */
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $origin = $scheme . '://' . $host;

    /**
     * Resolve a stored photo path/filename to a public URL.
     *
     * In profiling-system, DB fields commonly store:
     * - just a filename: "1767951351_updated_Formal ID_.png"
     * - or a relative path: "uploads/residents/<filename>"
     *
     * Actual files live under (examples):
     * - /profiling-system/officials/uploads/residents/<filename>
     * - /profiling-system/residentsto.rosario/uploads/residents/<filename>
     *
     * This resolver tries known profiling-system locations and picks the first existing file.
     */
    $htdocsRoot = realpath(__DIR__ . '/../../../..'); // .../htdocs

    $resolvePhotoUrl = function (?string $raw) use ($origin, $htdocsRoot): ?string {
        if ($raw === null) return null;
        $raw = trim($raw);
        if ($raw === '') return null;

        // Skip placeholder paths like "/to/photo" or "/path/to/photo"
        if (preg_match('/^\/to\/|^\/path\/to\//i', $raw)) {
            return null;
        }

        // Block directory traversal or weird schemes
        if (str_contains($raw, '..') || preg_match('/^[a-zA-Z]+:\/\//', $raw) && !preg_match('/^https?:\/\//i', $raw)) {
            return null;
        }

        // If already absolute http(s), keep as-is.
        if (preg_match('/^https?:\/\//i', $raw)) {
            return $raw;
        }

        // Normalize leading slash for later.
        $rawNoLeadingSlash = ltrim($raw, '/');

        // If already points to profiling-system, just make it absolute.
        if (str_starts_with($rawNoLeadingSlash, 'profiling-system/')) {
            return $origin . '/' . $rawNoLeadingSlash;
        }

        // Candidate public relative paths (web-accessible) we will test via file_exists.
        $candidates = [];

        // If it's a bare filename, map into known uploads folders.
        if (!str_contains($rawNoLeadingSlash, '/')) {
            $fileName = basename($rawNoLeadingSlash);
            $candidates[] = "profiling-system/officials/uploads/residents/{$fileName}";
            $candidates[] = "profiling-system/residentsto.rosario/uploads/residents/{$fileName}";
            $candidates[] = "profiling-system/resident/uploads/residents/{$fileName}";
        } else {
            // If it looks like profiling-system's stored relative path
            // e.g., "uploads/residents/<file>"
            if (str_starts_with($rawNoLeadingSlash, 'uploads/')) {
                $candidates[] = "profiling-system/officials/{$rawNoLeadingSlash}";
                $candidates[] = "profiling-system/residentsto.rosario/{$rawNoLeadingSlash}";
                $candidates[] = "profiling-system/resident/{$rawNoLeadingSlash}";
            }

            // Or if it's already like "officials/uploads/residents/<file>"
            $candidates[] = "profiling-system/{$rawNoLeadingSlash}";
        }

        // Pick first existing file on disk.
        foreach ($candidates as $rel) {
            if (!$htdocsRoot) {
                break;
            }
            $fsPath = $htdocsRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
            if (is_file($fsPath)) {
                return $origin . '/' . $rel;
            }
        }

        // Fallback: default to profiling-system/officials/uploads/residents/<filename> when possible
        if (!str_contains($rawNoLeadingSlash, '/') && $rawNoLeadingSlash !== '') {
            return $origin . '/profiling-system/officials/uploads/residents/' . rawurlencode($rawNoLeadingSlash);
        }

        return $origin . '/' . $rawNoLeadingSlash;
    };
    
    // Format residents for face recognition
    $formattedResidents = [];
    
    foreach ($residents as $resident) {
        // Get photo path (handle both JSON array and single path)
        $photoPath = $resident['photo_path'] ?? null;
        $photoUrls = [];
        
        if (!empty($photoPath)) {
            // Try to decode as JSON (new format - array of photos)
            $decoded = json_decode($photoPath, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !empty($decoded)) {
                // Use all photos (3 angles) for face recognition
                foreach ($decoded as $path) {
                    if (!empty($path) && is_string($path)) {
                        $url = $resolvePhotoUrl($path);
                        if ($url) $photoUrls[] = $url;
                    }
                }
            } else {
                // Single path (old format) - convert to array for consistency
                $singlePath = trim($photoPath);
                $url = $resolvePhotoUrl($singlePath);
                if ($url) $photoUrls[] = $url;
            }
        }
        
        // Only include residents with at least one photo
        if (!empty($photoUrls)) {
            $fullName = trim(($resident['first_name'] ?? '') . ' ' . ($resident['middle_name'] ?? '') . ' ' . ($resident['last_name'] ?? ''));
            $fullName = preg_replace('/\s+/', ' ', $fullName); // Clean up multiple spaces
            
            $formattedResidents[] = [
                'id' => (int)$resident['resident_id'],
                'name' => $fullName,
                'imgs' => $photoUrls, // Array of all photos (3 angles)
                'img' => $photoUrls[0], // First photo for display purposes
                'resident_id' => (int)$resident['resident_id'],
                'phil_sys_number' => $resident['phil_sys_number'] ?? null,
                'first_name' => $resident['first_name'] ?? '',
                'middle_name' => $resident['middle_name'] ?? '',
                'last_name' => $resident['last_name'] ?? '',
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'residents' => $formattedResidents,
        'count' => count($formattedResidents)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch residents',
        'message' => $e->getMessage()
    ]);
}
