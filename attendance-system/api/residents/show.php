<?php
require_once __DIR__ . "/../../bootstrap.php";

header("Content-Type: application/json");

$method = $_SERVER["REQUEST_METHOD"];

/**
 * GET /api/residents/show.php?id={id} - Get one resident by ID
 */
if ($method === "GET") {
    $id = $_GET["id"] ?? null;

    if (empty($id)) {
        http_response_code(400);
        echo json_encode(["error" => "Resident ID is required"]);
        exit;
    }

    $residentsController = new ResidentController();
    $resident = $residentsController->getAllResidents($id);

    if ($resident) {
        echo json_encode($resident);
        exit;
    } else {
        http_response_code(404);
        echo json_encode(["message" => "Resident not found"]);
        exit;
    }
}

http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);
