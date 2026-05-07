<?php
require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../auth/helpers.php";

header("Content-Type: application/json");

// Require authentication
requireAuth();

$method = $_SERVER["REQUEST_METHOD"];

/**
 * GET /api/accounts/index.php - Get paginated accounts
 */
if ($method === "GET") {
    $perPage = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 10;
    $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    // Get filters
    $filters = [];
    if (isset($_GET['role']) && !empty($_GET['role'])) {
        $filters['role'] = $_GET['role'];
    }
    if (isset($_GET['is_active']) && $_GET['is_active'] !== '') {
        $filters['is_active'] = $_GET['is_active'];
    }

    try {
        $accountController = new AccountController();
        $result = $accountController->getPaginatedAccounts($currentPage, $perPage, $searchQuery, $filters);

        http_response_code(200);
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
