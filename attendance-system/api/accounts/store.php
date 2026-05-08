<?php
require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../auth/helpers.php";

header("Content-Type: application/json");

// Require authentication
requireAuth();

$method = $_SERVER["REQUEST_METHOD"];

/**
 * POST /api/accounts/store.php - Create new account
 */
if ($method === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (empty($input)) {
        $input = $_POST;
    }

    try {
        $accountController = new AccountController();
        $result = $accountController->create($input);

        $status = $result["status"] ?? 200;
        unset($result["status"]);
        http_response_code($status);
        echo json_encode($result);
        exit;

    } catch (Exception $err) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "error" => "Something went wrong - " . $err->getMessage()
        ]);
        exit;
    }
}

http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);
