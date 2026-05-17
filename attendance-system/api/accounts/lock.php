<?php
require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../auth/helpers.php";

header("Content-Type: application/json");

// Require authentication
requireAuth();

$method = $_SERVER["REQUEST_METHOD"];

/**
 * POST /api/accounts/lock.php?id={id}&lock={0|1} - Lock/Unlock account
 */
if ($method === "POST" || $method === "PUT") {
    $id = $_GET['id'] ?? $_POST['id'] ?? null;
    $lock = isset($_GET['lock']) ? filter_var($_GET['lock'], FILTER_VALIDATE_BOOLEAN) : 
            (isset($_POST['lock']) ? filter_var($_POST['lock'], FILTER_VALIDATE_BOOLEAN) : null);

    if (empty($id) || $lock === null) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Account ID and lock status are required"
        ]);
        exit;
    }

    try {
        $accountController = new AccountController();
        $result = $accountController->toggleLock($id, $lock);

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
