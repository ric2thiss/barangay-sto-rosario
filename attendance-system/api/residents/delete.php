<?php
require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../auth/helpers.php";

header("Content-Type: application/json");

// Require authentication for delete operations
requireAuth();

$method = $_SERVER["REQUEST_METHOD"];

/**
 * DELETE /api/residents/delete.php?id={id} - Delete a resident
 * POST /api/residents/delete.php?id={id} - Delete a resident (alternative method)
 */
if ($method === "DELETE" || $method === "POST") {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "error" => "Residents are managed by profiling-system. Deletion is not allowed here."
    ]);
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);
